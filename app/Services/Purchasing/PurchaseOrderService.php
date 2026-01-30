<?php

namespace App\Services\Purchasing;

use App\Helpers\CodeGenerator;
use App\Models\Account;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\SupplierPrice;
use App\Services\Accounting\JournalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function __construct(
        protected JournalService $journalService
    ) {}

    /**
     * Create Purchase Order baru + detail lines.
     */
    public function create(array $payload): PurchaseOrder
    {
        return DB::transaction(function () use ($payload) {
            $linesData = $payload['lines'] ?? [];
            unset($payload['lines']);

            $payload = $this->onlyHeaderFields($payload);

            if (empty($payload['code'] ?? null)) {
                $payload['code'] = CodeGenerator::generate('PO');
            }

            // normalize numbers
            $payload['subtotal'] = 0;
            $payload['discount'] = $this->toNumber($payload['discount'] ?? 0);
            $payload['tax_percent'] = $this->toNumber($payload['tax_percent'] ?? 0);
            $payload['tax_amount'] = 0;
            $payload['shipping_cost'] = $this->toNumber($payload['shipping_cost'] ?? 0);
            $payload['grand_total'] = 0;

            $payload['status'] = $payload['status'] ?? 'draft';
            $payload['order_type'] = $this->normalizeOrderType($payload['order_type'] ?? 'material');

            /** @var PurchaseOrder $order */
            $order = PurchaseOrder::create($payload);

            $subtotal = $this->syncLines($order, is_array($linesData) ? $linesData : []);
            $this->recalculateTotals($order, $subtotal);

            return $order->fresh(['lines.item', 'supplier', 'paymentMethod']);
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

            $payload = $this->onlyHeaderFields($payload);

            // code tidak boleh berubah lewat update
            unset($payload['code']);

            if (array_key_exists('date', $payload)) {
                $order->date = $payload['date'];
            }

            if (array_key_exists('supplier_id', $payload)) {
                $order->supplier_id = (int) $payload['supplier_id'];
            }

            if (array_key_exists('payment_method_id', $payload)) {
                $order->payment_method_id = $payload['payment_method_id'] ? (int) $payload['payment_method_id'] : null;
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
                $order->status = (string) $payload['status'];
            }

            // update order_type kalau dikirim (fallback existing)
            if (array_key_exists('order_type', $payload)) {
                $order->order_type = $this->normalizeOrderType($payload['order_type']);
            } else {
                $order->order_type = $this->normalizeOrderType($order->getAttribute('order_type') ?: 'material');
            }

            $order->save();

            $subtotal = $this->syncLines($order, is_array($linesData) ? $linesData : []);
            $this->recalculateTotals($order, $subtotal);

            return $order->fresh(['lines.item', 'supplier', 'paymentMethod']);
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

            return $order->fresh(['lines.item', 'supplier', 'paymentMethod']);
        });
    }

    // ======================================================================
    // APPROVE / CANCEL
    // ======================================================================

    /**
     * Approve PO + post EXPENSE lines (allocation=expense) ke jurnal:
     * Dr expense_account_id, Cr AP (2101).
     * Inventory/HPP lines tetap menunggu GRN.
     */
    public function approve(PurchaseOrder $order, int $approvedBy): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $approvedBy) {

            $order->load(['lines', 'supplier', 'paymentMethod']);

            if ($order->status !== 'draft') {
                return $order->fresh(['supplier', 'lines', 'paymentMethod']);
            }

            // feature flags
            $hasLineAllocation = Schema::hasColumn('purchase_order_lines', 'allocation');
            $hasLineExpenseAcc = Schema::hasColumn('purchase_order_lines', 'expense_account_id');

            // ✅ VALIDASI: expense line wajib punya akun biaya (biar GRN bisa jurnal expense)
            if ($hasLineAllocation && $hasLineExpenseAcc) {
                $bad = $order->lines
                    ->where('allocation', 'expense')
                    ->first(function ($ln) {
                        return empty($ln->expense_account_id);
                    });

                if ($bad) {
                    throw ValidationException::withMessages([
                        'lines' => 'Ada item Expense tapi akun biaya belum ter-set. Set default_expense_account_id pada master item / pilih akun biaya di baris PO.',
                    ]);
                }
            }

            // ✅ APPROVE ORDER (NO JOURNAL)
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

            // NOTE: kalau kamu mau cancel PO approved yg sudah mem-post expense journal:
            // kamu bisa void jurnal by source (SRC_PO_EXPENSE_APPROVE, po_id) di sini.
            // Tapi kamu bilang GRN nanti, jadi kita keep minimal.

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
            'order_type',
        ];

        return array_intersect_key($payload, array_flip($allowed));
    }

    /**
     * syncLines:
     * - simpan semua line (hpp + expense)
     * - allocation & expense_account_id otomatis dari master item (default_allocation, default_expense_account_id)
     * - line boleh override kalau dikirim
     * - expense account boleh null saat draft (validasi keras ada di approve())
     */
    protected function syncLines(PurchaseOrder $order, array $linesData): float
    {
        $order->lines()->delete();

        $subtotal = 0.0;
        $orderType = $this->normalizeOrderType($order->getAttribute('order_type') ?: 'material');

        // feature flags
        $hasLineAllocation = Schema::hasColumn('purchase_order_lines', 'allocation');
        $hasLineExpenseAcc = Schema::hasColumn('purchase_order_lines', 'expense_account_id');
        $hasItemDefaultAlloc = Schema::hasColumn('items', 'default_allocation');
        $hasItemDefaultExpAcc = Schema::hasColumn('items', 'default_expense_account_id');

        // preload items
        $itemIds = collect($linesData)
            ->pluck('item_id')
            ->filter(fn($v) => $v !== null && $v !== '')
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $itemsById = Item::query()
            ->select(array_values(array_filter([
                'id',
                'type',
                $hasItemDefaultAlloc ? 'default_allocation' : null,
                $hasItemDefaultExpAcc ? 'default_expense_account_id' : null,
            ])))
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        foreach ($linesData as $i => $row) {
            $itemId = $row['item_id'] ?? null;
            $itemId = ($itemId === null || $itemId === '') ? 0 : (int) $itemId;

            $qty = $this->toNumber($row['qty'] ?? 0);
            $unitPrice = $this->toNumber($row['unit_price'] ?? 0);
            $discount = $this->toNumber($row['discount'] ?? 0);
            $notes = $row['notes'] ?? null;
            $lotId = $row['lot_id'] ?? null;

            if ($itemId <= 0 || $qty <= 0.0001) {
                continue;
            }

            /** @var Item|null $item */
            $item = $itemsById->get($itemId);
            if (!$item) {
                continue;
            }

            // Guard item type must match order type
            if ((string) $item->type !== (string) $orderType) {
                throw ValidationException::withMessages([
                    "lines.$i.item_id" => "Item tidak sesuai jenis PO. PO: {$orderType}, Item: {$item->type}.",
                ]);
            }

            // Allocation (hpp/expense) - auto dari master item, line override kalau ada
            $allocation = 'hpp';
            if ($hasLineAllocation) {
                $fromLine = $row['allocation'] ?? null;

                if ($fromLine !== null && $fromLine !== '') {
                    $allocRaw = (string) $fromLine;
                } else {
                    $allocRaw = (string) ($hasItemDefaultAlloc ? ($item->default_allocation ?? 'hpp') : 'hpp');
                }

                $allocation = in_array($allocRaw, ['hpp', 'expense'], true) ? $allocRaw : 'hpp';
            }

            // Expense account if expense - auto dari master, line override kalau ada
            $expenseAccountId = null;
            if ($hasLineExpenseAcc && $allocation === 'expense') {
                $fromLine = $row['expense_account_id'] ?? null;

                if ($fromLine !== null && $fromLine !== '') {
                    $expenseAccountId = (int) $fromLine;
                    if ($expenseAccountId <= 0) {
                        $expenseAccountId = null;
                    }
                }

                if (!$expenseAccountId && $hasItemDefaultExpAcc && !empty($item->default_expense_account_id)) {
                    $expenseAccountId = (int) $item->default_expense_account_id;
                    if ($expenseAccountId <= 0) {
                        $expenseAccountId = null;
                    }
                }
            }

            $lineTotal = max(0, ($qty * $unitPrice) - $discount);
            $lineTotal = round($lineTotal, 2);

            $payload = [
                'item_id' => (int) $itemId,
                'lot_id' => $lotId ? (int) $lotId : null,
                'qty' => round($qty, 4),
                'unit_price' => round($unitPrice, 4),
                'discount' => round($discount, 2),
                'line_total' => $lineTotal,
                'notes' => $notes,
            ];

            if ($hasLineAllocation) {
                $payload['allocation'] = $allocation;
            }
            if ($hasLineExpenseAcc) {
                $payload['expense_account_id'] = ($allocation === 'expense') ? $expenseAccountId : null;
            }

            $order->lines()->create($payload);

            $subtotal = round($subtotal + $lineTotal, 2);

            // update last purchase price
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

        Item::whereKey($itemId)->update([
            'last_purchase_price' => $unitPrice,
        ]);

        SupplierPrice::updateOrCreate(
            ['supplier_id' => $order->supplier_id, 'item_id' => $itemId],
            ['last_price' => $unitPrice]
        );
    }

    protected function accountIdByCode(string $code): int
    {
        $acc = Account::query()
            ->where('code', $code)
            ->where('is_active', 1)
            ->first();

        if (!$acc) {
            throw ValidationException::withMessages([
                'account' => "Account code {$code} tidak ditemukan / tidak aktif.",
            ]);
        }

        return (int) $acc->id;
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

        // format indo: 1.234,56
        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
            return (float) $value;
        }

        // ribuan: 1.234.567
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
            return (float) $value;
        }

        return (float) $value;
    }
}
