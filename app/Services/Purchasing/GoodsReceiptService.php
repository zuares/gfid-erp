<?php

namespace App\Services\Purchasing;

use App\Helpers\CodeGenerator;
use App\Models\Item;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
use App\Models\SupplierPrice;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;

class GoodsReceiptService
{
    public function __construct(
        protected InventoryService $inventory,
    ) {
    }

    /**
     * Buat GRN baru (status: draft).
     *
     * $payload contoh:
     * [
     *   'date'            => '2025-11-21',
     *   'supplier_id'     => 1,
     *   'warehouse_id'    => 1,
     *   'purchase_order_id' => 1,  // optional
     *   'discount'        => 0,
     *   'tax_percent'     => 11,
     *   'shipping_cost'   => 25000,
     *   'notes'           => 'Barang datang dari PO 001',
     *   'created_by'      => 1,
     *   'lines' => [
     *      [
     *          'item_id'       => 1,
     *          'qty_received'  => 100,
     *          'qty_reject'    => 0,
     *          'unit_price'    => 12000,
     *          'unit'          => 'kg',
     *          'notes'         => 'Roll 1-5',
     *          'lot_id'        => null, // optional
     *      ],
     *      // ...
     *   ],
     * ]
     */
    public function create(array $payload): PurchaseReceipt
    {
        return DB::transaction(function () use ($payload) {
            $linesData = $payload['lines'] ?? [];
            unset($payload['lines']);

            // generate kode GRN kalau belum ada
            if (empty($payload['code'] ?? null)) {
                $payload['code'] = CodeGenerator::generate('GRN');
            }

            // set default angka
            $payload['subtotal'] = 0;
            $payload['discount'] = $this->num($payload['discount'] ?? 0);
            $payload['tax_percent'] = $this->num($payload['tax_percent'] ?? 0);
            $payload['tax_amount'] = 0;
            $payload['shipping_cost'] = $this->num($payload['shipping_cost'] ?? 0);
            $payload['grand_total'] = 0;
            $payload['status'] = $payload['status'] ?? 'draft';

            /** @var PurchaseReceipt $grn */
            $grn = PurchaseReceipt::create($payload);

            // simpan detail + hitung subtotal
            $subtotal = $this->syncLines($grn, $linesData);

            // hitung total header
            $this->recalcTotals($grn, $subtotal);

            return $grn->fresh(['lines', 'supplier', 'warehouse']);
        });
    }

    /**
     * Update GRN (selama masih draft).
     */
    public function update(PurchaseReceipt $grn, array $payload): PurchaseReceipt
    {
        return DB::transaction(function () use ($grn, $payload) {
            if ($grn->status !== 'draft') {
                throw new \RuntimeException("Goods Receipt sudah {$grn->status}, tidak bisa diubah.");
            }

            $linesData = $payload['lines'] ?? [];
            unset($payload['lines'], $payload['code']); // kode tidak diubah

            // update header field yang boleh berubah
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
                if (array_key_exists($field, $payload)) {
                    if (in_array($field, ['discount', 'tax_percent', 'shipping_cost'])) {
                        $grn->{$field} = $this->num($payload[$field]);
                    } else {
                        $grn->{$field} = $payload[$field];
                    }
                }
            }

            $grn->save();

            // sync ulang lines
            $subtotal = $this->syncLines($grn, $linesData);

            // hitung ulang totals
            $this->recalcTotals($grn, $subtotal);

            return $grn->fresh(['lines', 'supplier', 'warehouse']);
        });
    }

    /**
     * POST GRN → stok masuk ke gudang.
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

            // muat lines dulu untuk keamanan
            $grn->loadMissing('lines');

            foreach ($grn->lines as $line) {
                if ($line->qty_received <= 0) {
                    continue;
                }

                // pakai InventoryService stockIn
                $this->inventory->stockIn(
                    warehouseId: $grn->warehouse_id,
                    itemId: $line->item_id,
                    qty: $line->qty_received,
                    date: $grn->date,
                    sourceType: 'purchase_receipt',
                    sourceId: $grn->id,
                    notes: "GRN {$grn->code} line {$line->id}",
                );
            }

            $grn->status = 'posted';
            $grn->save();

            return $grn->fresh(['lines', 'supplier', 'warehouse']);
        });
    }

    /**
     * UNPOST GRN → stok dikurangi lagi (reverse).
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

            $grn->loadMissing('lines');

            foreach ($grn->lines as $line) {
                if ($line->qty_received <= 0) {
                    continue;
                }

                // pakai InventoryService stockOut
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
            }

            $grn->status = 'draft';
            $grn->save();

            return $grn->fresh(['lines', 'supplier', 'warehouse']);
        });
    }

    /**
     * Hitung ulang total dari detail (kalau ada perubahan manual).
     */
    public function recalculate(PurchaseReceipt $grn): PurchaseReceipt
    {
        return DB::transaction(function () use ($grn) {
            $subtotal = $grn->lines()->sum('line_total');

            $this->recalcTotals($grn, (float) $subtotal);

            return $grn->fresh(['lines', 'supplier', 'warehouse']);
        });
    }

    // =====================================================================
    // HELPER INTERNAL
    // =====================================================================

    /**
     * Simpan ulang detail lines GRN.
     * Sederhana: hapus semua lalu insert ulang.
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

            if (!$itemId || $qtyReceived <= 0) {
                continue;
            }

            $lineTotal = round($qtyReceived * $unitPrice, 2);

            /** @var PurchaseReceiptLine $line */
            $line = PurchaseReceiptLine::create([
                'purchase_receipt_id' => $grn->id,
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

            // update harga terakhir di master & supplier
            $this->touchLastPrices($grn, $itemId, $unitPrice);
        }

        return round($subtotal, 2);
    }

    /**
     * Hitung subtotal, tax_amount, grand_total dan simpan ke header GRN.
     */
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

    /**
     * Update:
     *  - items.last_purchase_price
     *  - supplier_prices.last_price
     */
    protected function touchLastPrices(PurchaseReceipt $grn, int $itemId, float $unitPrice): void
    {
        $unitPrice = round($unitPrice, 2);

        // update master item
        Item::where('id', $itemId)->update([
            'last_purchase_price' => $unitPrice,
        ]);

        // update harga per supplier
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
     * Normalisasi angka dari input (string format Indonesia, dll).
     */
    protected function num($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        // Kalau sudah numeric (hasil validasi / cast Laravel), langsung saja
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        // Pastikan string
        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);

        // Kalau ada koma → anggap format Indonesia: "1.234,56" / "24,00"
        if (strpos($value, ',') !== false) {
            // Hilangkan titik ribuan
            $value = str_replace('.', '', $value);
            // Ganti koma jadi titik desimal
            $value = str_replace(',', '.', $value);
            return (float) $value;
        }

        // Kalau tidak ada koma, tapi pola ribuan: "1.234" atau "1.234.567"
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
            return (float) $value;
        }

        // Default: biarkan Laravel terjemahkan (mis. "1234.56")
        return (float) $value;
    }
}
