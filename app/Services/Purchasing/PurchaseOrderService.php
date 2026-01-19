<?php

namespace App\Services\Purchasing;

use App\Helpers\CodeGenerator;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\SupplierPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    /**
     * Create Purchase Order baru + detail lines.
     */
    public function create(array $payload): PurchaseOrder
    {
        return DB::transaction(function () use ($payload) {
            $linesData = $payload['lines'] ?? [];
            unset($payload['lines']);

            // ✅ whitelist header fields (biar aman)
            $payload = $this->onlyHeaderFields($payload);

            // Generate kode jika belum diisi
            if (empty($payload['code'] ?? null)) {
                $payload['code'] = CodeGenerator::generate('PO');
            }

            // Default angka (dinormalisasi)
            $payload['subtotal'] = 0;
            $payload['discount'] = $this->toNumber($payload['discount'] ?? 0);
            $payload['tax_percent'] = $this->toNumber($payload['tax_percent'] ?? 0);
            $payload['tax_amount'] = 0;
            $payload['shipping_cost'] = $this->toNumber($payload['shipping_cost'] ?? 0);
            $payload['grand_total'] = 0;

            $payload['status'] = $payload['status'] ?? 'draft';

            // ✅ jenis PO (fallback material)
            $payload['order_type'] = $this->normalizeOrderType($payload['order_type'] ?? 'material');

            /** @var PurchaseOrder $order */
            $order = PurchaseOrder::create($payload);

            // Sync detail & hitung subtotal
            $subtotal = $this->syncLines($order, is_array($linesData) ? $linesData : []);

            // Hitung total header
            $this->recalculateTotals($order, $subtotal);

            return $order->fresh([
                'lines.item',
                'supplier',
                'paymentMethod',
            ]);
        });
    }

    /**
     * Update Purchase Order + detail lines.
     */
    public function update(PurchaseOrder $order, array $payload): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $payload) {
            $linesData = $payload['lines'] ?? [];
            unset($payload['lines']);

            // ✅ whitelist header fields (hindari field liar)
            $payload = $this->onlyHeaderFields($payload);

            // code tidak boleh berubah lewat update
            unset($payload['code']);

            if (array_key_exists('date', $payload)) {
                $order->date = $payload['date'];
            }

            if (array_key_exists('supplier_id', $payload)) {
                $order->supplier_id = $payload['supplier_id'];
            }

            if (array_key_exists('payment_method_id', $payload)) {
                $order->payment_method_id = $payload['payment_method_id'];
            }

            if (array_key_exists('discount', $payload)) {
                $order->discount = $this->toNumber($payload['discount']);
            }

            if (array_key_exists('tax_percent', $payload)) {
                $order->tax_percent = $this->toNumber($payload['tax_percent']);
            }

            if (array_key_exists('shipping_cost', $payload)) {
                $order->shipping_cost = $this->toNumber($payload['shipping_cost']);
            }

            if (array_key_exists('notes', $payload)) {
                $order->notes = $payload['notes'];
            }

            if (array_key_exists('status', $payload)) {
                $order->status = $payload['status'];
            }

            // ✅ update order_type kalau dikirim (fallback existing)
            if (array_key_exists('order_type', $payload)) {
                $order->order_type = $this->normalizeOrderType($payload['order_type']);
            } else {
                // pastikan nilai existing tetap valid
                $order->order_type = $this->normalizeOrderType($order->getAttribute('order_type') ?: 'material');
            }

            $order->save();

            // Sync detail & hitung subtotal
            $subtotal = $this->syncLines($order, is_array($linesData) ? $linesData : []);

            // Hitung ulang total header
            $this->recalculateTotals($order, $subtotal);

            return $order->fresh([
                'lines.item',
                'supplier',
                'paymentMethod',
            ]);
        });
    }

    /**
     * Force hitung ulang subtotal, tax, grand_total dari database.
     */
    public function recalculate(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order) {
            $subtotal = (float) $order->lines()->sum('line_total');
            $this->recalculateTotals($order, $subtotal);

            return $order->fresh([
                'lines.item',
                'supplier',
                'paymentMethod',
            ]);
        });
    }

    // ======================================================================
    // APPROVE / CANCEL
    // ======================================================================

    public function approve(PurchaseOrder $order, int $approvedBy): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $approvedBy) {
            if ($order->status !== 'draft') {
                return $order->fresh(['supplier', 'lines', 'paymentMethod']);
            }

            $order->status = 'approved';
            $order->approved_by = $approvedBy;
            $order->approved_at = now();
            $order->save();

            return $order->fresh(['supplier', 'lines', 'paymentMethod']);
        });
    }

    public function cancel(PurchaseOrder $order, int $cancelledBy): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $cancelledBy) {
            if (!in_array($order->status, ['draft', 'approved'], true)) {
                return $order->fresh(['supplier', 'lines', 'paymentMethod']);
            }

            if ($order->purchaseReceipts()->exists()) {
                return $order->fresh(['supplier', 'lines', 'purchaseReceipts', 'paymentMethod']);
            }

            $order->status = 'cancelled';
            $order->cancelled_by = $cancelledBy;
            $order->cancelled_at = now();
            $order->save();

            return $order->fresh(['supplier', 'lines', 'cancelledBy', 'paymentMethod']);
        });
    }

    // ======================================================================
    // INTERNAL HELPERS
    // ======================================================================

    /**
     * Field header yang diizinkan lewat service.
     */
    protected function onlyHeaderFields(array $payload): array
    {
        $allowed = [
            'code',
            'date',
            'supplier_id',
            'payment_method_id',
            'discount',
            'tax_percent',
            'shipping_cost',
            'notes',
            'created_by',
            'status',
            'order_type', // ✅ penting buat PO barang jadi
        ];

        return array_intersect_key($payload, array_flip($allowed));
    }

    /**
     * Simpan ulang detail lines: hapus semua lalu insert ulang.
     * Return subtotal.
     */
    protected function syncLines(PurchaseOrder $order, array $linesData): float
    {
        $order->lines()->delete();

        $subtotal = 0.0;

        $orderType = $this->normalizeOrderType($order->getAttribute('order_type') ?: 'material');

        foreach ($linesData as $i => $row) {
            $itemId = $row['item_id'] ?? null;

            $qty = $this->toNumber($row['qty'] ?? 0);
            $unitPrice = $this->toNumber($row['unit_price'] ?? 0);
            $discount = $this->toNumber($row['discount'] ?? 0);
            $notes = $row['notes'] ?? null;
            $lotId = $row['lot_id'] ?? null;

            if (!$itemId || $qty <= 0) {
                continue;
            }

            /** @var Item|null $item */
            $item = Item::query()->select('id', 'type')->find($itemId);
            if (!$item) {
                continue;
            }

            // ✅ Guard: item type harus match orderType
            if ((string) $item->type !== (string) $orderType) {
                throw ValidationException::withMessages([
                    "lines.$i.item_id" => "Item yang dipilih tidak sesuai jenis PO. PO: {$orderType}, Item: {$item->type}.",
                ]);
            }

            $lineTotal = max(0, ($qty * $unitPrice) - $discount);
            $lineTotal = round($lineTotal, 2);

            $order->lines()->create([
                'item_id' => (int) $itemId,
                'lot_id' => $lotId ? (int) $lotId : null,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'line_total' => $lineTotal,
                'notes' => $notes,
            ]);

            $subtotal += $lineTotal;

            $this->touchLastPrices($order, (int) $itemId, (float) $unitPrice);
        }

        return round($subtotal, 2);
    }

    protected function recalculateTotals(PurchaseOrder $order, float $subtotal): void
    {
        $discount = $this->toNumber($order->discount);
        $taxPercent = $this->toNumber($order->tax_percent);
        $shippingCost = $this->toNumber($order->shipping_cost);

        $base = max(0, $subtotal - $discount);
        $taxAmount = round($base * $taxPercent / 100, 2);
        $grandTotal = $base + $taxAmount + $shippingCost;

        $order->subtotal = round($subtotal, 2);
        $order->tax_amount = $taxAmount;
        $order->grand_total = round($grandTotal, 2);
        $order->save();
    }

    protected function touchLastPrices(PurchaseOrder $order, int $itemId, float $unitPrice): void
    {
        $unitPrice = round($unitPrice, 2);

        // Update master item (tanpa load besar)
        Item::whereKey($itemId)->update([
            'last_purchase_price' => $unitPrice,
        ]);

        SupplierPrice::updateOrCreate(
            ['supplier_id' => $order->supplier_id, 'item_id' => $itemId],
            ['last_price' => $unitPrice]
        );
    }

    protected function normalizeOrderType(?string $value): string
    {
        $v = strtolower(trim((string) $value));
        return in_array($v, ['material', 'finished_good'], true) ? $v : 'material';
    }

    /**
     * Normalisasi angka (menerima string indo / numeric).
     */
    protected function toNumber($value): float
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
