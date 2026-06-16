<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repair GRN lines yang unit_price = 0.
 *
 * Urutan sumber harga (dari yang paling otoritatif):
 *   1. PO line.unit_price  (kalau > 0)
 *   2. supplier_prices.last_price untuk supplier+item yang sama
 *   3. items.last_purchase_price
 *
 * Usage:
 *   php artisan grn:repair-prices              # semua GRN draft/posted dengan harga 0
 *   php artisan grn:repair-prices --grn=46     # hanya GRN id tertentu
 *   php artisan grn:repair-prices --dry-run    # preview tanpa simpan
 */
class GrnRepairPrices extends Command
{
    protected $signature = 'grn:repair-prices
                            {--grn=  : ID GRN spesifik (opsional)}
                            {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Repair unit_price = 0 pada GRN lines. Fallback: PO price → supplier last price → item last price';

    public function handle(): int
    {
        $isDry = (bool) $this->option('dry-run');
        $grnId = $this->option('grn') ? (int) $this->option('grn') : null;

        $this->info($isDry
            ? '[DRY-RUN] Tidak ada perubahan yang disimpan.'
            : '[LIVE] Perubahan AKAN disimpan ke DB.'
        );
        $this->newLine();

        // Ambil semua GRN lines dengan unit_price = 0
        $query = DB::table('purchase_receipt_lines as prl')
            ->join('purchase_receipts as pr', 'pr.id', '=', 'prl.purchase_receipt_id')
            ->leftJoin('purchase_order_lines as pol', 'pol.id', '=', 'prl.purchase_order_line_id')
            // fallback 1: supplier_prices (supplier dari PO, item dari line)
            ->leftJoin('purchase_orders as po_ref', 'po_ref.id', '=', 'pr.purchase_order_id')
            ->leftJoin('supplier_prices as sp', function ($join) {
                $join->on('sp.item_id', '=', 'prl.item_id')
                     ->on('sp.supplier_id', '=', 'po_ref.supplier_id');
            })
            // fallback 2: item last_purchase_price
            ->leftJoin('items as itm', 'itm.id', '=', 'prl.item_id')
            ->whereRaw('CAST(prl.unit_price AS REAL) = 0')
            ->whereRaw('(prl.qty_received > 0 OR prl.qty_reject > 0)')
            ->select(
                'prl.id as line_id',
                'prl.purchase_receipt_id as grn_id',
                'pr.code as grn_code',
                'pr.status as grn_status',
                'pr.purchase_order_id as pr_po_id',
                'pr.supplier_id as pr_supplier_id',
                'prl.item_id',
                'prl.qty_received',
                'prl.qty_reject',
                'prl.unit_price as old_price',
                'prl.line_total as old_line_total',
                'pol.unit_price as po_price',
                'sp.last_price as supplier_price',
                'itm.last_purchase_price as item_last_price',
                'itm.code as item_code',
            );

        if ($grnId) {
            $query->where('prl.purchase_receipt_id', $grnId);
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->info('Tidak ada GRN line dengan unit_price = 0 yang perlu direpair.');
            return self::SUCCESS;
        }

        $byGrn = $rows->groupBy('grn_id');

        foreach ($byGrn as $receiptId => $lines) {
            $first     = $lines->first();
            $grnCode   = $first->grn_code;
            $grnStatus = $first->grn_status;

            $this->line("GRN #{$receiptId} [{$grnCode}] status={$grnStatus}:");

            $lineUpdates = [];

            foreach ($lines as $l) {
                // Pilih harga terbaik
                $newPrice = 0.0;
                $source   = '(tidak ditemukan)';

                if ((float) ($l->po_price ?? 0) > 0) {
                    $newPrice = (float) $l->po_price;
                    $source   = 'PO line';
                } elseif ((float) ($l->supplier_price ?? 0) > 0) {
                    $newPrice = (float) $l->supplier_price;
                    $source   = 'supplier_prices';
                } elseif ((float) ($l->item_last_price ?? 0) > 0) {
                    $newPrice = (float) $l->item_last_price;
                    $source   = 'item.last_purchase_price';
                }

                $newLineTotal = round((float) $l->qty_received * $newPrice, 2);

                $this->line(sprintf(
                    '  item=%s  qty=%.3f  harga: 0 → %s  (%s)  line_total → %s',
                    $l->item_code ?? $l->item_id,
                    $l->qty_received,
                    number_format($newPrice, 0, ',', '.'),
                    $source,
                    number_format($newLineTotal, 2, ',', '.'),
                ));

                if ($newPrice <= 0) {
                    $this->warn("    ⚠ Tidak ada sumber harga untuk item {$l->item_code}. Skip.");
                    continue;
                }

                $lineUpdates[] = [
                    'line_id'      => $l->line_id,
                    'new_price'    => $newPrice,
                    'new_total'    => $newLineTotal,
                ];
            }

            if ($isDry || empty($lineUpdates)) {
                $this->newLine();
                continue;
            }

            // LIVE: Update + recalculate
            DB::transaction(function () use ($receiptId, $lineUpdates) {
                foreach ($lineUpdates as $u) {
                    DB::table('purchase_receipt_lines')
                        ->where('id', $u['line_id'])
                        ->update([
                            'unit_price' => $u['new_price'],
                            'line_total' => $u['new_total'],
                            'updated_at' => now(),
                        ]);
                }

                // Recalculate GRN header totals
                $grn      = DB::table('purchase_receipts')->where('id', $receiptId)->first();
                $subtotal = (float) DB::table('purchase_receipt_lines')
                    ->where('purchase_receipt_id', $receiptId)
                    ->sum('line_total');

                $discount   = (float) ($grn->discount ?? 0);
                $taxPercent = (float) ($grn->tax_percent ?? 0);
                $shipping   = (float) ($grn->shipping_cost ?? 0);

                $base       = max(0, $subtotal - $discount);
                $taxAmount  = round($base * $taxPercent / 100, 2);
                $grand      = round($base + $taxAmount + $shipping, 2);

                DB::table('purchase_receipts')
                    ->where('id', $receiptId)
                    ->update([
                        'subtotal'    => round($subtotal, 2),
                        'tax_amount'  => $taxAmount,
                        'grand_total' => $grand,
                        'updated_at'  => now(),
                    ]);
            });

            // Show result
            $updated = DB::table('purchase_receipts')->where('id', $receiptId)->first();
            $this->info(sprintf(
                '  ✓ Tersimpan. subtotal=%s  grand_total=%s',
                number_format((float) $updated->subtotal, 2, ',', '.'),
                number_format((float) $updated->grand_total, 2, ',', '.'),
            ));
            $this->newLine();
        }

        if ($isDry) {
            $this->comment('Dry-run selesai. Jalankan tanpa --dry-run untuk menyimpan.');
        } else {
            $this->info('Selesai. ' . $rows->count() . ' baris diproses.');
        }

        return self::SUCCESS;
    }
}
