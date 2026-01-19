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
    public function create(array $payload): PurchaseOrder
    {
        return DB::transaction(function () use ($payload) {
            $linesData = $payload['lines'] ?? [];
            unset($payload['lines']);

            if (empty($payload['code'] ?? null)) {
                $payload['code'] = CodeGenerator::generate('PO');
            }

            $payload['subtotal'] = 0;
            $payload['discount'] = $this->toNumber($payload['discount'] ?? 0);
            $payload['tax_percent'] = $this->toNumber($payload['tax_percent'] ?? 0);
            $payload['tax_amount'] = 0;
            $payload['shipping_cost'] = $this->toNumber($payload['shipping_cost'] ?? 0);
            $payload['grand_total'] = 0;

            $payload['status'] = $payload['status'] ?? 'draft';

            // NEW: jenis PO (fallback material)
            $payload['order_type'] = $this->normalizeOrderType($payload['order_type'] ?? 'material');

            /** @var PurchaseOrder $order */
            $order = PurchaseOrder::create($payload);

            $subtotal = $this->syncLines($order, $linesData);
            $this->recalculateTotals($order, $subtotal);

            return $order->fresh(['lines', 'supplier']);
        });
    }

    public function update(PurchaseOrder $order, array $payload): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $payload) {
            $linesData = $payload['lines'] ?? [];
            unset($payload['lines'], $payload['code']);

            // NEW: jenis PO (ambil dari payload kalau ada, kalau tidak pakai existing, fallback material)
            $orderType = $this->normalizeOrderType(
                $payload['order_type'] ?? ($order->getAttribute('order_type') ?: 'material')
            );

            if (array_key_exists('date', $payload)) {
                $order->date = $payload['date'];
            }

            if (array_key_exists('supplier_id', $payload)) {
                $order->supplier_id = $payload['supplier_id'];
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

            // simpan order_type kalau kolom ada (kalau kolom tidak ada, Eloquent bisa error saat save)
            // Jadi: hanya set kalau attribute exists di model (biasanya aman kalau kolom sudah ada).
            // Kalau kolom belum ada, kamu boleh comment 2 baris ini.
            $order->order_type = $orderType;

            $order->save();

            $subtotal = $this->syncLines($order, $linesData, $orderType);
            $this->recalculateTotals($order, $subtotal);

            return $order->fresh(['lines', 'supplier']);
        });
    }

    public function recalculate(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order) {
            $subtotal = (float) $order->lines()->sum('line_total');
            $this->recalculateTotals($order, $subtotal);
            return $order->fresh(['lines', 'supplier']);
        });
    }

    // ======================================================================
    // INTERNAL
    // ======================================================================

    protected function syncLines(PurchaseOrder $order, array $linesData, ?string $orderType = null): float
    {
        $order->lines()->delete();

        $subtotal = 0.0;
        $orderType = $this->normalizeOrderType($orderType ?? ($order->getAttribute('order_type') ?: 'material'));

        foreach ($linesData as $i => $row) {
            $itemId = isset($row['item_id']) ? (int) $row['item_id'] : 0;
            $qty = $this->toNumber($row['qty'] ?? 0);
            $unitPrice = $this->toNumber($row['unit_price'] ?? 0);
            $discount = $this->toNumber($row['discount'] ?? 0);
            $notes = $row['notes'] ?? null;
            $lotId = $row['lot_id'] ?? null;

            if ($itemId <= 0 || $qty <= 0) {
                continue;
            }

            /** @var Item|null $item */
            $item = Item::query()->select('id', 'type')->find($itemId);
            if (!$item) {
                continue;
            }

            // ✅ Guard: item type harus match orderType
            if ($item->type !== $orderType) {
                throw ValidationException::withMessages([
                    "lines.$i.item_id" => "Item yang dipilih tidak sesuai jenis PO. PO: {$orderType}, Item: {$item->type}.",
                ]);
            }

            $lineTotal = max(0, ($qty * $unitPrice) - $discount);
            $lineTotal = round($lineTotal, 2);

            $order->lines()->create([
                'item_id' => $itemId,
                'lot_id' => $lotId,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'line_total' => $lineTotal,
                'notes' => $notes,
            ]);

            $subtotal += $lineTotal;

            $this->touchLastPrices($order, $itemId, $unitPrice);
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

        $item = Item::find($itemId);
        if ($item) {
            $item->last_purchase_price = $unitPrice;
            $item->save();
        }

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

    public function approve(PurchaseOrder $order, int $approvedBy): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $approvedBy) {
            if ($order->status !== 'draft') {
                return $order->fresh(['supplier', 'lines']);
            }

            $order->status = 'approved';
            $order->approved_by = $approvedBy;
            $order->approved_at = now();
            $order->save();

            return $order->fresh(['supplier', 'lines']);
        });
    }

    public function cancel(PurchaseOrder $order, int $cancelledBy): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $cancelledBy) {
            if (!in_array($order->status, ['draft', 'approved'], true)) {
                return $order->fresh(['supplier', 'lines']);
            }

            if ($order->purchaseReceipts()->exists()) {
                return $order->fresh(['supplier', 'lines', 'purchaseReceipts']);
            }

            $order->status = 'cancelled';
            $order->cancelled_by = $cancelledBy;
            $order->cancelled_at = now();
            $order->save();

            return $order->fresh(['supplier', 'lines', 'cancelledBy']);
        });
    }
}
