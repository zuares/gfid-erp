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
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class GoodsReceiptService
{
    public function __construct(
        protected InventoryService $inventory,
        protected JournalService $journal,
    ) {}

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

            // sync lines FULL (hpp + expense)
            $subtotalAll = $this->syncLines($grn, $linesData);

            // subtotal header = total semua line (jujur)
            $this->recalcTotals($grn, $subtotalAll);

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

            $subtotalAll = $this->syncLines($grn, $linesData);

            // subtotal header = total semua line (jujur)
            $this->recalcTotals($grn, $subtotalAll);

            return $grn->fresh(['lines.item', 'supplier', 'warehouse']);
        });
    }

    /**
     * POST GRN → stock-in (HPP only) + jurnal (split HPP/Expense) + (opsional) apply DP (1151).
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

            $grn->loadMissing(['lines.item', 'supplier']);

            if ($grn->lines->count() === 0) {
                throw ValidationException::withMessages(['grn' => 'GRN tidak punya line.']);
            }

            $grandTotal = (float) $grn->grand_total;
            if ($grandTotal <= 0) {
                throw ValidationException::withMessages(['grn' => 'Total GRN harus > 0.']);
            }

            // Map allocation + expense account
            $maps = $this->buildLineMetaMapsForGrn($grn);

            // ==========================
            // 1) STOCK IN (LOT + moving average) — hanya HPP
            // ==========================
            foreach ($grn->lines as $line) {
                if ((float) $line->qty_received <= 0) {
                    continue;
                }

                $isHpp = $this->isLineEligibleForStock(
                    $line->purchase_order_line_id,
                    (int) $line->item_id,
                    $maps['eligibility']
                );

                if (!$isHpp) {
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

                // update last price hanya untuk HPP (opsional tapi masuk akal)
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

            $inventoryAccountId = (int) (Account::where('code', $inventoryCode)->value('id') ?? 0);
            $apAccountId = (int) (Account::where('code', $apCode)->value('id') ?? 0);
            $advanceAccountId = (int) (Account::where('code', $advanceCode)->value('id') ?? 0);

            if ($inventoryAccountId <= 0 || $apAccountId <= 0 || $advanceAccountId <= 0) {
                throw ValidationException::withMessages([
                    'grn' => "Akun tidak lengkap. Pastikan ada COA: Inventory {$inventoryCode}, AP {$apCode}, Uang Muka {$advanceCode}.",
                ]);
            }

            // akun tambahan
            $shippingExpenseCode = '6102'; // Biaya Transport/Ongkir
            $taxInputCode = '1401'; // PPN Masukan (optional)
            $purchaseExpenseFallbackCode = '6110'; // fallback biaya pembelian / pembelian expense

            $shippingExpenseId = (int) (Account::where('code', $shippingExpenseCode)->value('id') ?? 0);
            $taxInputId = (int) (Account::where('code', $taxInputCode)->value('id') ?? 0); // boleh 0
            $purchaseExpenseFallbackId = (int) (Account::where('code', $purchaseExpenseFallbackCode)->value('id') ?? 0); // boleh 0, tapi sebaiknya ada

            // ==========================
            // 4) Build split amounts (HPP vs Expense) + prorate discount
            // ==========================
            $discount = (float) $grn->discount;
            $taxAmount = (float) $grn->tax_amount;
            $shippingCost = (float) $grn->shipping_cost;

            $totals = $this->calculateSplitTotals($grn, $maps);

            // total before discount (base for prorate)
            $totalBeforeDiscount = $totals['hpp_total'] + $totals['expense_total'];

            // apply discount prorata
            $discount = min($discount, max(0, $totalBeforeDiscount));
            $hppAfterDiscount = $totals['hpp_total'];
            $expenseAfterDiscountByAcc = $totals['expense_by_account'];

            if ($discount > 0.0001 && $totalBeforeDiscount > 0.0001) {
                $hppShare = $totals['hpp_total'] / $totalBeforeDiscount;
                $hppDisc = round($discount * $hppShare, 2);
                $hppAfterDiscount = max(0, round($totals['hpp_total'] - $hppDisc, 2));

                $remainingDisc = round($discount - $hppDisc, 2);

                // prorata ke tiap akun expense
                $expenseAfterDiscountByAcc = [];
                $expTotal = (float) $totals['expense_total'];

                if ($expTotal > 0.0001 && $remainingDisc > 0.0001) {
                    $running = 0.0;
                    $accIds = array_keys($totals['expense_by_account']);
                    $lastAccId = end($accIds);

                    foreach ($totals['expense_by_account'] as $accId => $amt) {
                        $amt = (float) $amt;
                        $share = $amt / $expTotal;
                        $accDisc = round($remainingDisc * $share, 2);

                        // terakhir: adjust biar total diskon pas
                        if ((int) $accId === (int) $lastAccId) {
                            $accDisc = round($remainingDisc - $running, 2);
                        } else {
                            $running = round($running + $accDisc, 2);
                        }

                        $expenseAfterDiscountByAcc[$accId] = max(0, round($amt - $accDisc, 2));
                    }
                } else {
                    // tidak ada expense atau diskon sisa
                    $expenseAfterDiscountByAcc = $totals['expense_by_account'];
                }
            }

            // ==========================
            // 5) POST JOURNAL GRN (Split)
            // - Debit Inventory: HPP after discount
            // - Debit Expense: per account (after discount)
            // - Debit Tax Input: tax_amount (if exists else fallback inventory)
            // - Debit Shipping: 6102
            // - Credit AP: grand_total
            // ==========================
            $journalLines = [];

            // Debit Inventory (hpp)
            if ($hppAfterDiscount > 0.0001) {
                $journalLines[] = ['account_id' => $inventoryAccountId, 'debit' => $hppAfterDiscount, 'credit' => 0];
            }

            // Debit Expense per account
            foreach ($expenseAfterDiscountByAcc as $accId => $amt) {
                $accId = (int) $accId;
                $amt = (float) $amt;
                if ($amt <= 0.0001) {
                    continue;
                }

                // fallback jika akun tidak ada
                if ($accId <= 0) {
                    if ($purchaseExpenseFallbackId <= 0) {
                        throw ValidationException::withMessages([
                            'grn' => "Expense line ada tapi tidak ada akun biaya. Set expense_account_id di PO line, atau buat COA {$purchaseExpenseFallbackCode} sebagai fallback.",
                        ]);
                    }
                    $accId = $purchaseExpenseFallbackId;
                }

                $journalLines[] = ['account_id' => $accId, 'debit' => $amt, 'credit' => 0];
            }

            // Debit PPN Masukan (opsional)
            if ($taxAmount > 0.0001) {
                $journalLines[] = [
                    'account_id' => ($taxInputId > 0 ? $taxInputId : $inventoryAccountId),
                    'debit' => $taxAmount,
                    'credit' => 0,
                ];
            }

            // Debit Ongkir (6102)
            if ($shippingCost > 0.0001) {
                if ($shippingExpenseId <= 0) {
                    throw ValidationException::withMessages([
                        'grn' => "Akun ongkir belum ada. Pastikan COA {$shippingExpenseCode} (Biaya Transport/Ongkir).",
                    ]);
                }
                $journalLines[] = ['account_id' => $shippingExpenseId, 'debit' => $shippingCost, 'credit' => 0];
            }

            // Credit AP = grand_total
            $journalLines[] = ['account_id' => $apAccountId, 'debit' => 0, 'credit' => $grandTotal];

            $this->journal->post(
                date: $grn->date->format('Y-m-d'),
                sourceType: 'grn',
                sourceId: (int) $grn->id,
                description: "GRN {$grn->code} - {$grn->supplier?->name}",
                lines: $journalLines
            );

            return $grn->fresh(['lines.item', 'supplier', 'warehouse']);
        });
    }

    /**
     * UNPOST GRN → reverse stock (hpp only) + void journals.
     */
    public function unpost(PurchaseReceipt $grn): PurchaseReceipt
    {
        return DB::transaction(function () use ($grn) {
            if ($grn->status !== 'posted') {
                throw new \RuntimeException("Hanya GRN yang sudah posted yang bisa di-unpost.");
            }

            // ✅ BLOCK kalau ada payment aktif di PO terkait
            if ($this->hasActivePaymentsForOrder($grn->purchase_order_id)) {
                throw ValidationException::withMessages([
                    'grn' => 'Tidak bisa UNPOST karena sudah ada Payment/DP aktif pada PO ini. Void payment dulu, baru unpost.',
                ]);
            }

            if (!$grn->warehouse_id) {
                throw new \RuntimeException("Goods Receipt tidak punya gudang.");
            }

            $grn->loadMissing(['lines', 'supplier']);

            $maps = $this->buildLineMetaMapsForGrn($grn);

            foreach ($grn->lines as $line) {
                // ... reverse stock (punya kamu)
            }

            $this->journal->voidBySource('grn', (int) $grn->id);
            $this->journal->voidBySource('grn_apply_dp', (int) $grn->id);

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

    /**
     * syncLines FULL: simpan semua line (hpp + expense).
     * subtotal yang dikembalikan = total semua line_total (jujur).
     */
    protected function syncLines(PurchaseReceipt $grn, array $linesData): float
    {
        $grn->lines()->delete();

        $subtotal = 0.0;

        foreach ($linesData as $row) {
            $itemId = $row['item_id'] ?? null;
            $itemId = ($itemId === null || $itemId === '') ? 0 : (int) $itemId;

            $qtyReceived = $this->num($row['qty_received'] ?? 0);
            $qtyReject = $this->num($row['qty_reject'] ?? 0);

            $unitPrice = $this->num($row['unit_price'] ?? 0);
            $unit = $row['unit'] ?? null;
            $notes = $row['notes'] ?? null;
            $lotId = $row['lot_id'] ?? null;

            $poLineId = $row['purchase_order_line_id'] ?? null;
            $poLineId = ($poLineId === null || $poLineId === '') ? null : (int) $poLineId;

            if ($itemId <= 0 || ($qtyReceived <= 0 && $qtyReject <= 0)) {
                continue;
            }

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
            ['supplier_id' => $grn->supplier_id, 'item_id' => $itemId],
            ['last_price' => $unitPrice]
        );
    }

    /**
     * Build meta maps:
     * - eligibility: allocation from PO line > item default > fallback hpp
     * - expense_account_id: from PO line if exists, else 0
     */
    protected function buildLineMetaMapsForGrn(PurchaseReceipt $grn): array
    {
        $eligibility = $this->buildEligibilityMapsForGrnLines($grn);

        // expense account map (optional)
        $hasLineExpenseAcc = Schema::hasColumn('purchase_order_lines', 'expense_account_id');
        $expenseAccByPoLineId = collect();

        if ($hasLineExpenseAcc) {
            $poLineIds = $grn->lines
                ->pluck('purchase_order_line_id')
                ->filter()
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            if (!empty($poLineIds)) {
                $expenseAccByPoLineId = DB::table('purchase_order_lines')
                    ->whereIn('id', $poLineIds)
                    ->pluck('expense_account_id', 'id'); // [po_line_id => expense_account_id]
            }
        }

        return [
            'eligibility' => $eligibility,
            'expenseAccByPoLineId' => $expenseAccByPoLineId,
        ];
    }

    /**
     * Hitung totals split:
     * - hpp_total (sum line_total untuk HPP)
     * - expense_by_account (sum line_total untuk expense per akun)
     * - expense_total (sum expense_by_account)
     */
    protected function calculateSplitTotals(PurchaseReceipt $grn, array $maps): array
    {
        $elig = $maps['eligibility'] ?? ['poAllocByLineId' => collect(), 'itemAllocById' => collect()];
        $expenseAccByPoLineId = $maps['expenseAccByPoLineId'] ?? collect();

        $hppTotal = 0.0;
        $expenseByAcc = [];

        foreach ($grn->lines as $line) {
            $amt = (float) ($line->line_total ?? 0);
            if ($amt <= 0.0001) {
                continue;
            }

            $isHpp = $this->isLineEligibleForStock($line->purchase_order_line_id, (int) $line->item_id, $elig);

            if ($isHpp) {
                $hppTotal = round($hppTotal + $amt, 2);
                continue;
            }

            // expense
            $accId = 0;
            $poLineId = $line->purchase_order_line_id ? (int) $line->purchase_order_line_id : 0;

            if ($poLineId > 0) {
                $accId = (int) ($expenseAccByPoLineId[$poLineId] ?? 0);
            }

            $expenseByAcc[$accId] = round((float) ($expenseByAcc[$accId] ?? 0) + $amt, 2);
        }

        $expenseTotal = 0.0;
        foreach ($expenseByAcc as $accId => $amt) {
            $expenseTotal = round($expenseTotal + (float) $amt, 2);
        }

        return [
            'hpp_total' => round($hppTotal, 2),
            'expense_by_account' => $expenseByAcc,
            'expense_total' => round($expenseTotal, 2),
        ];
    }

    /**
     * Eligibility maps untuk GRN lines (dipakai di post/unpost).
     * - poAllocByLineId: [purchase_order_line_id => allocation]
     * - itemAllocById:   [item_id => default_allocation]
     */
    protected function buildEligibilityMapsForGrnLines(PurchaseReceipt $grn): array
    {
        $hasPoLineAlloc = Schema::hasColumn('purchase_order_lines', 'allocation');
        $hasItemDefaultAlloc = Schema::hasColumn('items', 'default_allocation');

        $poAllocByLineId = collect();
        $itemAllocById = collect();

        if ($hasPoLineAlloc) {
            $poLineIds = $grn->lines
                ->pluck('purchase_order_line_id')
                ->filter()
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            if (!empty($poLineIds)) {
                $poAllocByLineId = DB::table('purchase_order_lines')
                    ->whereIn('id', $poLineIds)
                    ->pluck('allocation', 'id');
            }
        }

        if ($hasItemDefaultAlloc) {
            $itemIds = $grn->lines
                ->pluck('item_id')
                ->filter()
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            if (!empty($itemIds)) {
                $itemAllocById = Item::query()
                    ->whereIn('id', $itemIds)
                    ->pluck('default_allocation', 'id');
            }
        }

        return [
            'poAllocByLineId' => $poAllocByLineId,
            'itemAllocById' => $itemAllocById,
        ];
    }

    protected function isLineEligibleForStock($purchaseOrderLineId, int $itemId, array $maps): bool
    {
        $poAllocByLineId = $maps['poAllocByLineId'] ?? collect();
        $itemAllocById = $maps['itemAllocById'] ?? collect();

        $poLineId = ($purchaseOrderLineId === null || $purchaseOrderLineId === '') ? null : (int) $purchaseOrderLineId;

        return $this->isEligibleFromMaps($poLineId, $itemId, $poAllocByLineId, $itemAllocById);
    }

    /**
     * Decide eligibility from preloaded maps.
     * - if PO line says expense => NOT eligible
     * - else if item default says expense => NOT eligible
     * - else eligible (hpp)
     */
    protected function isEligibleFromMaps(?int $poLineId, int $itemId, $poAllocByLineId, $itemAllocById): bool
    {
        if ($poLineId) {
            $alloc = (string) ($poAllocByLineId[$poLineId] ?? 'hpp');
            return $alloc !== 'expense';
        }

        $alloc = (string) ($itemAllocById[$itemId] ?? 'hpp');
        return $alloc !== 'expense';
    }

    /**
     * Hitung DP yang boleh di-apply tanpa bikin error kalau relasi/metodenya belum ada.
     */
    protected function calculateDpAppliedAmountSafely(PurchaseReceipt $grn, float $totalForJournal): float
    {
        if (!$grn->purchase_order_id) {
            return 0.0;
        }

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

        $dpTotal = 0.0;

        if (method_exists($order, 'activePayments')) {
            try {
                $dpTotal = (float) $order->activePayments()
                    ->where('type', 'dp')
                    ->sum('amount');
            } catch (\Throwable $e) {
                $dpTotal = 0.0;
            }
        }

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

    protected function hasActivePaymentsForOrder(?int $purchaseOrderId): bool
    {
        if (!$purchaseOrderId) {
            return false;
        }

        // ganti nama tabel kalau beda (mis: purchase_order_payments)
        return DB::table('purchase_payments')
            ->where('purchase_order_id', $purchaseOrderId)
            ->whereNull('voided_at')
            ->exists();
    }

}
