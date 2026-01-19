<?php

namespace App\Services\Purchasing;

use App\Helpers\CodeGenerator;
use App\Models\Account;
use App\Models\Item;
use App\Models\Lot;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
use App\Models\SupplierPrice;
use App\Services\Accounting\JournalService;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoodsReceiptService
{
    public function __construct(
        protected InventoryService $inventory,
        protected JournalService $journal,
    ) {
    }

    /**
     * Buat GRN baru (status: draft).
     */
    public function create(array $payload): PurchaseReceipt
    {
        return DB::transaction(function () use ($payload) {
            $linesData = $payload['lines'] ?? [];
            unset($payload['lines']);

            if (empty($payload['code'] ?? null)) {
                $payload['code'] = CodeGenerator::generate('GRN');
            }

            $payload['subtotal'] = 0;
            $payload['discount'] = $this->num($payload['discount'] ?? 0);
            $payload['tax_percent'] = $this->num($payload['tax_percent'] ?? 0);
            $payload['tax_amount'] = 0;
            $payload['shipping_cost'] = $this->num($payload['shipping_cost'] ?? 0);
            $payload['grand_total'] = 0;
            $payload['status'] = $payload['status'] ?? 'draft';

            /** @var PurchaseReceipt $grn */
            $grn = PurchaseReceipt::create($payload);

            $subtotal = $this->syncLines($grn, $linesData);
            $this->recalcTotals($grn, $subtotal);

            return $grn->fresh(['lines.item', 'supplier', 'warehouse']);
        });
    }

    public function update(PurchaseReceipt $grn, array $payload): PurchaseReceipt
    {
        return DB::transaction(function () use ($grn, $payload) {
            if ($grn->status !== 'draft') {
                throw new \RuntimeException("Goods Receipt sudah {$grn->status}, tidak bisa diubah.");
            }

            $linesData = $payload['lines'] ?? [];
            unset($payload['lines'], $payload['code']);

            $allowedFields = [
                'date',
                'supplier_id',
                'warehouse_id',
                'purchase_order_id',
                'discount',
                'tax_percent',
                'shipping_cost',
                'notes',
            ];

            foreach ($allowedFields as $field) {
                if (!array_key_exists($field, $payload)) {
                    continue;
                }

                if (in_array($field, ['discount', 'tax_percent', 'shipping_cost'], true)) {
                    $grn->{$field} = $this->num($payload[$field]);
                } else {
                    $grn->{$field} = $payload[$field];
                }
            }

            $grn->save();

            $subtotal = $this->syncLines($grn, $linesData);
            $this->recalcTotals($grn, $subtotal);

            return $grn->fresh(['lines.item', 'supplier', 'warehouse']);
        });
    }

    /**
     * POST GRN → stock in + jurnal GRN + (opsional) apply DP (1151)
     *
     * Catatan:
     * - Tidak pakai journal_id / posted_at (biar aman ke schema sekarang).
     * - DP apply dibuat "guarded" (tidak hard-depend ke method/relasi tertentu).
     */
    public function post(PurchaseReceipt $grn): PurchaseReceipt
    {
        return DB::transaction(function () use ($grn) {
            if ($grn->status !== 'draft') {
                throw new \RuntimeException("Goods Receipt tidak dalam status draft.");
            }
            if (!$grn->warehouse_id) {
                throw new \RuntimeException("Goods Receipt belum punya gudang tujuan.");
            }

            // Load minimal yang aman (order optional, jangan bikin error kalau relasi belum ada)
            $grn->loadMissing(['lines.item', 'supplier']);

            if ($grn->lines->count() === 0) {
                throw ValidationException::withMessages(['grn' => 'GRN tidak punya line.']);
            }

            $totalForJournal = (float) $grn->grand_total;
            if ($totalForJournal <= 0) {
                throw ValidationException::withMessages(['grn' => 'Total GRN harus > 0.']);
            }

            // ==========================
            // 1) STOCK IN (LOT + moving average)
            // ==========================
            foreach ($grn->lines as $line) {
                if ((float) $line->qty_received <= 0) {
                    continue;
                }

                // Pastikan LOT ada
                if ($line->lot_id) {
                    $lot = $line->lot ?? Lot::findOrFail($line->lot_id);
                } else {
                    $lot = Lot::create([
                        'code' => CodeGenerator::generate('LOT'),
                        'item_id' => (int) $line->item_id,
                        'initial_qty' => 0,
                        'initial_cost' => 0,
                        'qty_onhand' => 0,
                        'total_cost' => 0,
                        'avg_cost' => 0,
                        'status' => 'open',
                    ]);

                    $line->lot_id = $lot->id;
                    $line->save();
                }

                $this->inventory->stockIn(
                    warehouseId: (int) $grn->warehouse_id,
                    itemId: (int) $line->item_id,
                    qty: (float) $line->qty_received,
                    date: $grn->date,
                    sourceType: 'purchase_receipt',
                    sourceId: (int) $grn->id,
                    notes: "GRN {$grn->code} line {$line->id}",
                    lotId: (int) $lot->id,
                    unitCost: (float) $line->unit_price,
                );

                // update last price
                $this->touchLastPrices($grn, (int) $line->item_id, (float) $line->unit_price);
            }

            // ==========================
            // 2) SET STATUS POSTED
            // ==========================
            $grn->status = 'posted';
            $grn->save();

            // ==========================
            // 3) Resolve akun via CODE
            // ==========================
            $inventoryCode = (string) (config('accounting.inventory_account_code') ?: '1201');
            $apCode = (string) (config('accounting.ap_account_code') ?: '2101');
            $advanceCode = '1151';

            $inventoryAccountId = Account::where('code', $inventoryCode)->value('id');
            $apAccountId = Account::where('code', $apCode)->value('id');
            $advanceAccountId = Account::where('code', $advanceCode)->value('id');

            if (!$inventoryAccountId || !$apAccountId || !$advanceAccountId) {
                throw ValidationException::withMessages([
                    'grn' => "Akun tidak lengkap. Pastikan ada COA: Inventory {$inventoryCode}, AP {$apCode}, Uang Muka {$advanceCode}.",
                ]);
            }

            // ==========================
            // 4) POST JOURNAL GRN (Inventory vs AP)
            // ==========================
            $this->journal->post(
                date: $grn->date->format('Y-m-d'),
                sourceType: 'grn',
                sourceId: (int) $grn->id,
                description: "GRN {$grn->code} - {$grn->supplier?->name}",
                lines: [
                    ['account_id' => (int) $inventoryAccountId, 'debit' => $totalForJournal, 'credit' => 0],
                    ['account_id' => (int) $apAccountId, 'debit' => 0, 'credit' => $totalForJournal],
                ]
            );

            // ==========================
            // 5) APPLY DP (optional, guarded)
            // ==========================
            $dpApplied = $this->calculateDpAppliedAmountSafely($grn, $totalForJournal);

            if ($dpApplied > 0.0001) {
                $this->journal->post(
                    date: $grn->date->format('Y-m-d'),
                    sourceType: 'grn_apply_dp',
                    sourceId: (int) $grn->id,
                    description: "Apply DP ke GRN {$grn->code}",
                    lines: [
                        ['account_id' => (int) $apAccountId, 'debit' => $dpApplied, 'credit' => 0],
                        ['account_id' => (int) $advanceAccountId, 'debit' => 0, 'credit' => $dpApplied],
                    ]
                );
            }

            return $grn->fresh(['lines.item', 'supplier', 'warehouse']);
        });
    }

    /**
     * UNPOST GRN → reverse stock + void jurnal GRN + void jurnal apply DP
     */
    public function unpost(PurchaseReceipt $grn): PurchaseReceipt
    {
        return DB::transaction(function () use ($grn) {
            if ($grn->status !== 'posted') {
                throw new \RuntimeException("Hanya GRN yang sudah posted yang bisa di-unpost.");
            }
            if (!$grn->warehouse_id) {
                throw new \RuntimeException("Goods Receipt tidak punya gudang.");
            }

            $grn->loadMissing(['lines', 'supplier']);

            // ==========================
            // 1) reverse stock
            // ==========================
            foreach ($grn->lines as $line) {
                if ((float) $line->qty_received <= 0) {
                    continue;
                }

                $this->inventory->stockOut(
                    warehouseId: (int) $grn->warehouse_id,
                    itemId: (int) $line->item_id,
                    qty: (float) $line->qty_received,
                    date: now(),
                    sourceType: 'purchase_receipt_reverse',
                    sourceId: (int) $grn->id,
                    notes: "UNPOST GRN {$grn->code} line {$line->id}",
                    allowNegative: false,
                    lotId: $line->lot_id ?: null,
                );
            }

            // ==========================
            // 2) void journals by source
            // ==========================
            $this->journal->voidBySource('grn', (int) $grn->id);
            $this->journal->voidBySource('grn_apply_dp', (int) $grn->id);

            // ==========================
            // 3) set back to draft
            // ==========================
            $grn->status = 'draft';
            $grn->save();

            return $grn->fresh(['lines.item', 'supplier', 'warehouse']);
        });
    }

    public function recalculate(PurchaseReceipt $grn): PurchaseReceipt
    {
        return DB::transaction(function () use ($grn) {
            $subtotal = (float) $grn->lines()->sum('line_total');
            $this->recalcTotals($grn, $subtotal);
            return $grn->fresh(['lines.item', 'supplier', 'warehouse']);
        });
    }

    // =====================================================================
    // INTERNAL HELPERS
    // =====================================================================

    protected function syncLines(PurchaseReceipt $grn, array $linesData): float
    {
        $grn->lines()->delete();

        $subtotal = 0.0;

        foreach ($linesData as $row) {
            $itemId = $row['item_id'] ?? null;

            $qtyReceived = $this->num($row['qty_received'] ?? 0);
            $qtyReject = $this->num($row['qty_reject'] ?? 0);

            $unitPrice = $this->num($row['unit_price'] ?? 0);
            $unit = $row['unit'] ?? null;
            $notes = $row['notes'] ?? null;
            $lotId = $row['lot_id'] ?? null;
            $poLineId = $row['purchase_order_line_id'] ?? null;

            // skip hanya kalau dua-duanya nol
            if (!$itemId || ($qtyReceived <= 0 && $qtyReject <= 0)) {
                continue;
            }

            // Nilai persediaan hanya dari yang diterima
            $lineTotal = round(max(0, $qtyReceived) * $unitPrice, 2);

            PurchaseReceiptLine::create([
                'purchase_receipt_id' => $grn->id,
                'purchase_order_line_id' => $poLineId,

                'item_id' => (int) $itemId,
                'lot_id' => $lotId,

                'qty_received' => $qtyReceived,
                'qty_reject' => $qtyReject,

                'unit' => $unit,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'notes' => $notes,
            ]);

            $subtotal += $lineTotal;
        }

        return round($subtotal, 2);
    }

    protected function recalcTotals(PurchaseReceipt $grn, float $subtotal): void
    {
        $discount = $this->num($grn->discount);
        $taxPercent = $this->num($grn->tax_percent);
        $shippingCost = $this->num($grn->shipping_cost);

        $base = max(0, $subtotal - $discount);
        $taxAmount = round($base * $taxPercent / 100, 2);
        $grand = $base + $taxAmount + $shippingCost;

        $grn->subtotal = round($subtotal, 2);
        $grn->tax_amount = $taxAmount;
        $grn->grand_total = round($grand, 2);
        $grn->save();
    }

    protected function touchLastPrices(PurchaseReceipt $grn, int $itemId, float $unitPrice): void
    {
        $unitPrice = round($unitPrice, 2);

        Item::where('id', $itemId)->update([
            'last_purchase_price' => $unitPrice,
        ]);

        SupplierPrice::updateOrCreate(
            [
                'supplier_id' => $grn->supplier_id,
                'item_id' => $itemId,
            ],
            [
                'last_price' => $unitPrice,
            ]
        );
    }

    /**
     * Hitung DP yang boleh di-apply tanpa bikin error kalau relasi/metodenya belum ada.
     */
    protected function calculateDpAppliedAmountSafely(PurchaseReceipt $grn, float $totalForJournal): float
    {
        if (!$grn->purchase_order_id) {
            return 0.0;
        }

        // Coba ambil PO via relasi yang mungkin ada: order / purchaseOrder
        $order = null;

        if (method_exists($grn, 'order')) {
            try {
                $order = $grn->relationLoaded('order') ? $grn->getRelation('order') : $grn->order;
            } catch (\Throwable $e) {
                $order = null;
            }
        }

        if (!$order && method_exists($grn, 'purchaseOrder')) {
            try {
                $order = $grn->relationLoaded('purchaseOrder') ? $grn->getRelation('purchaseOrder') : $grn->purchaseOrder;
            } catch (\Throwable $e) {
                $order = null;
            }
        }

        if (!$order) {
            return 0.0;
        }

        // Cari DP via method yang mungkin ada.
        $dpTotal = 0.0;

        // 1) Kalau ada activePayments()
        if (method_exists($order, 'activePayments')) {
            try {
                $dpTotal = (float) $order->activePayments()
                    ->where('type', 'dp')
                    ->sum('amount');
            } catch (\Throwable $e) {
                $dpTotal = 0.0;
            }
        }

        // 2) fallback: relasi payments()
        if ($dpTotal <= 0.0001 && method_exists($order, 'payments')) {
            try {
                $dpTotal = (float) $order->payments()
                    ->where('type', 'dp')
                    ->sum('amount');
            } catch (\Throwable $e) {
                $dpTotal = 0.0;
            }
        }

        if ($dpTotal <= 0.0001) {
            return 0.0;
        }

        $dpApplied = min($dpTotal, $totalForJournal);
        return round($dpApplied, 2);
    }

    protected function num($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);

        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
            return (float) $value;
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
            return (float) $value;
        }

        return (float) $value;
    }
}
