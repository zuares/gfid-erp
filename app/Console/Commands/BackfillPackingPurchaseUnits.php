<?php

namespace App\Console\Commands;

use App\Models\ItemRole;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseReceiptLine;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BackfillPackingPurchaseUnits extends Command
{
    protected $signature = 'purchasing:backfill-packing-units
        {--apply : Terapkan hanya koreksi yang aman pada dokumen belum posted}
        {--item= : Batasi audit ke item ID tertentu}';

    protected $description = 'Audit dan normalisasi snapshot unit pembelian perlengkapan packing';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $itemId = (int) ($this->option('item') ?: 0);
        $rows = [];
        $updated = 0;

        PurchaseOrderLine::query()
            ->with([
                'item.role',
                'purchaseOrder',
                'receiptLines.receipt',
            ])
            ->whereHas('item', fn (Builder $query) => $this->wherePackagingItem($query))
            ->when($itemId > 0, fn (Builder $query) => $query->where('item_id', $itemId))
            ->chunkById(200, function ($lines) use (&$rows, &$updated, $apply): void {
                foreach ($lines as $line) {
                    $item = $line->item;
                    if (! $item || ! $this->needsNormalization(
                        $line->purchase_unit,
                        $line->stock_unit,
                        $line->conversion_factor,
                        $item,
                    )) {
                        continue;
                    }

                    $hasPostedReceipt = $line->receiptLines->contains(
                        fn ($receiptLine): bool => $receiptLine->receipt?->status === 'posted'
                    );
                    $order = $line->purchaseOrder;
                    $safe = ! $hasPostedReceipt
                        && $order
                        && ! $order->isClosed()
                        && in_array($order->status, ['draft', 'approved'], true);

                    if ($apply && $safe) {
                        $this->normalizePurchaseOrderLine($line, $item);
                        $updated++;
                    }

                    $rows[] = [
                        'Jenis' => 'PO',
                        'ID' => '#'.$line->id,
                        'Item' => $item->code ?: $item->name,
                        'Saat ini' => $this->snapshotLabel($line->purchase_unit, $line->stock_unit, $line->conversion_factor),
                        'Master' => $this->snapshotLabel($item->purchaseUnit(), $item->stockUnit(), $item->purchaseConversionFactor()),
                        'Aksi' => $safe ? ($apply ? 'Diperbaiki' : 'Aman --apply') : 'Review manual (history)',
                    ];
                }
            });

        PurchaseReceiptLine::query()
            ->with([
                'item.role',
                'receipt',
                'purchaseOrderLine.purchaseOrder',
                'purchaseOrderLine.receiptLines.receipt',
            ])
            ->whereHas('item', fn (Builder $query) => $this->wherePackagingItem($query))
            ->when($itemId > 0, fn (Builder $query) => $query->where('item_id', $itemId))
            ->chunkById(200, function ($lines) use (&$rows, &$updated, $apply): void {
                foreach ($lines as $line) {
                    $item = $line->item;
                    if (! $item || ! $this->needsNormalization(
                        $line->purchase_unit ?: $line->unit,
                        $line->stock_unit,
                        $line->conversion_factor,
                        $item,
                    )) {
                        continue;
                    }

                    $poLine = $line->purchaseOrderLine;
                    $poHasPostedReceipt = $poLine?->receiptLines->contains(
                        fn ($receiptLine): bool => $receiptLine->receipt?->status === 'posted'
                    ) ?? false;
                    $safe = $line->receipt?->status === 'draft' && ! $poHasPostedReceipt;

                    if ($apply && $safe) {
                        DB::transaction(function () use ($line, $item, $poLine): void {
                            if ($poLine) {
                                $this->normalizePurchaseOrderLine($poLine, $item);
                            }

                            $factor = $item->purchaseConversionFactor();
                            $line->update([
                                'unit' => $item->purchaseUnit(),
                                'purchase_unit' => $item->purchaseUnit(),
                                'stock_unit' => $item->stockUnit(),
                                'conversion_factor' => $factor,
                                'stock_qty_received' => round((float) $line->qty_received * $factor, 6),
                                'stock_qty_reject' => round((float) $line->qty_reject * $factor, 6),
                                'line_total' => round((float) $line->qty_received * (float) $line->unit_price, 2),
                            ]);
                        });
                        $updated++;
                    }

                    $rows[] = [
                        'Jenis' => 'GRN',
                        'ID' => '#'.$line->id,
                        'Item' => $item->code ?: $item->name,
                        'Saat ini' => $this->snapshotLabel(
                            $line->purchase_unit ?: $line->unit,
                            $line->stock_unit,
                            $line->conversion_factor,
                        ),
                        'Master' => $this->snapshotLabel($item->purchaseUnit(), $item->stockUnit(), $item->purchaseConversionFactor()),
                        'Aksi' => $safe ? ($apply ? 'Diperbaiki' : 'Aman --apply') : 'Review manual (posted)',
                    ];
                }
            });

        if ($rows !== []) {
            $this->table(array_keys($rows[0]), $rows);
        } else {
            $this->info('Tidak ada snapshot unit packing yang perlu dinormalisasi.');
        }

        if ($apply) {
            $this->info("Selesai. {$updated} baris aman diperbaiki.");
            $this->warn('Baris yang sudah posted tetap tidak diubah; lakukan koreksi melalui alur unpost/reversal dan audit stok-jurnal.');
        } else {
            $this->warn('Mode dry-run: belum ada data yang diubah. Gunakan --apply setelah hasil audit ditinjau.');
        }

        return self::SUCCESS;
    }

    private function wherePackagingItem(Builder $query): Builder
    {
        return $query
            ->where('purchase_conversion_factor', '>', 1)
            ->where(function (Builder $query): void {
                $query
                    ->where('item_role', 'shipping_supply')
                    ->orWhereHas('role', fn (Builder $role): Builder => $role->where('code', ItemRole::PKG))
                    ->orWhereHas('category', fn (Builder $category): Builder => $category->where('kind', 'packaging'));
            });
    }

    private function needsNormalization(?string $purchaseUnit, ?string $stockUnit, mixed $factor, $item): bool
    {
        return strcasecmp(trim((string) $purchaseUnit), $item->purchaseUnit()) !== 0
            || strcasecmp(trim((string) $stockUnit), $item->stockUnit()) !== 0
            || abs((float) $factor - $item->purchaseConversionFactor()) > 0.000001;
    }

    private function normalizePurchaseOrderLine(PurchaseOrderLine $line, $item): void
    {
        $line->update([
            'purchase_unit' => $item->purchaseUnit(),
            'stock_unit' => $item->stockUnit(),
            'conversion_factor' => $item->purchaseConversionFactor(),
        ]);
    }

    private function snapshotLabel(?string $purchaseUnit, ?string $stockUnit, mixed $factor): string
    {
        return sprintf(
            '%s -> %s x %s',
            trim((string) $purchaseUnit) ?: '-',
            trim((string) $stockUnit) ?: '-',
            rtrim(rtrim(number_format((float) $factor, 6, '.', ''), '0'), '.') ?: '0',
        );
    }
}
