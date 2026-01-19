<?php

namespace App\Services\Purchasing;

use App\Helpers\CodeGenerator;
use App\Models\Item;
use App\Models\Lot;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
use App\Models\SupplierPrice;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;

class GoodsReceiptService
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

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

            return $grn->fresh(['lines', 'supplier', 'warehouse']);
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

            return $grn->fresh(['lines', 'supplier', 'warehouse']);
        });
    }

    public function post(PurchaseReceipt $grn): PurchaseReceipt
    {
        return DB::transaction(function () use ($grn) {
            if ($grn->status !== 'draft') {
                throw new \RuntimeException("Goods Receipt tidak dalam status draft.");
            }

            if (!$grn->warehouse_id) {
                throw new \RuntimeException("Goods Receipt belum punya gudang tujuan.");
            }

            $grn->loadMissing('lines.item');

            foreach ($grn->lines as $line) {
                // stock masuk hanya dari qty_received
                if ($line->qty_received <= 0) {
                    continue;
                }

                // 1) Pastikan LOT
                if ($line->lot_id) {
                    $lot = $line->lot ?? Lot::findOrFail($line->lot_id);
                } else {
                    $lot = Lot::create([
                        'code' => CodeGenerator::generate('LOT'),
                        'item_id' => $line->item_id,
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

                // 2) StockIn
                $this->inventory->stockIn(
                    warehouseId: $grn->warehouse_id,
                    itemId: $line->item_id,
                    qty: $line->qty_received,
                    date: $grn->date,
                    sourceType: 'purchase_receipt',
                    sourceId: $grn->id,
                    notes: "GRN {$grn->code} line {$line->id}",
                    lotId: $lot->id,
                    unitCost: $line->unit_price,
                );

                // 3) last price
                $this->touchLastPrices($grn, (int) $line->item_id, (float) $line->unit_price);
            }

            $grn->status = 'posted';
            $grn->save();

            return $grn->fresh(['lines', 'supplier', 'warehouse']);
        });
    }

    public function unpost(PurchaseReceipt $grn): PurchaseReceipt
    {
        return DB::transaction(function () use ($grn) {
            if ($grn->status !== 'posted') {
                throw new \RuntimeException("Hanya GRN yang sudah posted yang bisa di-unpost.");
            }

            if (!$grn->warehouse_id) {
                throw new \RuntimeException("Goods Receipt tidak punya gudang.");
            }

            $grn->loadMissing('lines');

            foreach ($grn->lines as $line) {
                if ($line->qty_received <= 0) {
                    continue;
                }

                if (!$line->lot_id) {
                    $this->inventory->stockOut(
                        warehouseId: $grn->warehouse_id,
                        itemId: $line->item_id,
                        qty: $line->qty_received,
                        date: now(),
                        sourceType: 'purchase_receipt_reverse',
                        sourceId: $grn->id,
                        notes: "UNPOST GRN {$grn->code} line {$line->id}",
                        allowNegative: false,
                    );
                    continue;
                }

                $this->inventory->stockOut(
                    warehouseId: $grn->warehouse_id,
                    itemId: $line->item_id,
                    qty: $line->qty_received,
                    date: now(),
                    sourceType: 'purchase_receipt_reverse',
                    sourceId: $grn->id,
                    notes: "UNPOST GRN {$grn->code} line {$line->id}",
                    allowNegative: false,
                    lotId: $line->lot_id,
                );
            }

            $grn->status = 'draft';
            $grn->save();

            return $grn->fresh(['lines', 'supplier', 'warehouse']);
        });
    }

    public function recalculate(PurchaseReceipt $grn): PurchaseReceipt
    {
        return DB::transaction(function () use ($grn) {
            $subtotal = (float) $grn->lines()->sum('line_total');
            $this->recalcTotals($grn, $subtotal);
            return $grn->fresh(['lines', 'supplier', 'warehouse']);
        });
    }

    // =====================================================================
    // INTERNAL
    // =====================================================================

    /**
     * Simpan ulang detail lines GRN.
     * ✅ Tidak skip reject-only.
     * ✅ Simpan purchase_order_line_id.
     */
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

            // ✅ skip hanya kalau dua-duanya nol
            if (!$itemId || ($qtyReceived <= 0 && $qtyReject <= 0)) {
                continue;
            }

            // Nilai persediaan hanya dari yang diterima
            $lineTotal = round(max(0, $qtyReceived) * $unitPrice, 2);

            PurchaseReceiptLine::create([
                'purchase_receipt_id' => $grn->id,
                'purchase_order_line_id' => $poLineId,

                'item_id' => $itemId,
                'lot_id' => $lotId,

                'qty_received' => $qtyReceived,
                'qty_reject' => $qtyReject,

                'unit' => $unit,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'notes' => $notes,
            ]);

            $subtotal += $lineTotal;

            // update last price (boleh tetap, walau qty_received=0 tapi reject>0 — aman)
            $this->touchLastPrices($grn, (int) $itemId, (float) $unitPrice);
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
