<?php

namespace App\Http\Controllers\Purchasing;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnLine;
use App\Services\Accounting\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PurchaseReturnController extends Controller
{
    public function __construct(
        protected JournalService $journal,
        protected \App\Services\Inventory\InventoryService $inventory,
    ) {}

    public function createFromGrn(PurchaseReceipt $purchase_receipt)
    {
        $purchase_receipt->loadMissing(['lines.item', 'order', 'supplier', 'warehouse']);

        if ($purchase_receipt->status !== 'posted') {
            return back()->with('error', 'Return hanya boleh dari GRN yang sudah POSTED.');
        }

        $ret = PurchaseReturn::create([
            'code' => CodeGenerator::generate('PRTN'),
            'date' => now()->toDateString(),
            'purchase_receipt_id' => (int) $purchase_receipt->id,
            'purchase_order_id' => (int) ($purchase_receipt->purchase_order_id ?? $purchase_receipt->order?->id ?? 0) ?: null,
            'supplier_id' => (int) ($purchase_receipt->supplier_id ?? $purchase_receipt->supplier?->id ?? 0) ?: null,
            'status' => 'draft',
            'created_by' => (int) auth()->id(),
        ]);

        $remainingMap = $this->remainingByGrnLine($purchase_receipt);

        foreach ($purchase_receipt->lines as $ln) {
            $rem = (float) ($remainingMap[$ln->id] ?? 0);
            if ($rem <= 0.0001) {
                continue;
            }

            PurchaseReturnLine::create([
                'purchase_return_id' => $ret->id,
                'purchase_receipt_line_id' => (int) $ln->id,
                'item_id' => (int) $ln->item_id,
                'lot_id' => $ln->lot_id ? (int) $ln->lot_id : null,
                'qty' => round($rem, 4),
                'unit_price' => (float) $ln->unit_price,
                'line_total' => round($rem * (float) $ln->unit_price, 2),
                'notes' => null,
            ]);
        }

        return redirect()
            ->route('purchasing.purchase_returns.show', $ret->id)
            ->with('success', 'Draft Return dibuat dari GRN.');
    }

    public function show(PurchaseReturn $purchase_return)
    {
        $purchase_return->loadMissing(['grn.warehouse', 'grn.lines.item', 'lines.item', 'lines.grnLine', 'supplier', 'order']);
        $remainingMap = $this->remainingByGrnLine($purchase_return->grn, excludeReturnId: (int) $purchase_return->id);

        return view('purchasing.purchase_returns.show', [
            'ret' => $purchase_return,
            'remainingMap' => $remainingMap,
        ]);
    }

    public function update(Request $request, PurchaseReturn $purchase_return)
    {
        if ($purchase_return->status !== 'draft' || $purchase_return->voided_at) {
            return back()->with('error', 'Return tidak bisa diubah (sudah posted/void).');
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['array'],
            'lines.*.id' => ['required', 'integer'],
            'lines.*.qty' => ['nullable', 'string'],
            'lines.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $purchase_return->loadMissing(['grn.lines']);

        $remainingMap = $this->remainingByGrnLine($purchase_return->grn, excludeReturnId: (int) $purchase_return->id);

        DB::transaction(function () use ($purchase_return, $data, $remainingMap) {
            $purchase_return->date = $data['date'];
            $purchase_return->notes = $data['notes'] ?? null;
            $purchase_return->save();

            foreach (($data['lines'] ?? []) as $row) {
                $line = $purchase_return->lines()->whereKey((int) $row['id'])->first();
                if (!$line) {
                    continue;
                }

                $qty = $this->toNumber($row['qty'] ?? 0);
                $qty = max(0, round($qty, 4));

                $grnLineId = (int) $line->purchase_receipt_line_id;
                $max = (float) ($remainingMap[$grnLineId] ?? 0);

                if ($qty > $max + 0.0001) {
                    throw ValidationException::withMessages([
                        "lines.{$line->id}.qty" => "Qty melebihi remaining returnable ({$max}).",
                    ]);
                }

                $unit = (float) $line->unit_price;
                $line->qty = $qty;
                $line->line_total = round($qty * $unit, 2);
                $line->notes = $row['notes'] ?? null;
                $line->save();
            }

            $total = (float) $purchase_return->lines()->sum('line_total');
            $purchase_return->total = round($total, 2);
            $purchase_return->save();
        });

        return back()->with('success', 'Draft Return tersimpan.');
    }

    /**
     * POST Return:
     * - INV lines: stockOut + journal INV (Cr 1201, Dr 2101/1305)
     * - EXP lines: no stock + journal EXP (Cr expense, Dr 2101/1305)
     */
    public function post(Request $request, PurchaseReturn $purchase_return)
    {
        if ($purchase_return->voided_at) {
            return back()->with('error', 'Return sudah void.');
        }

        if ($purchase_return->status !== 'draft') {
            return back()->with('error', 'Return sudah posted.');
        }

        $purchase_return->loadMissing(['grn.warehouse', 'grn.lines', 'order', 'lines.grnLine', 'lines.item']);

        if (!$purchase_return->grn || $purchase_return->grn->status !== 'posted') {
            return back()->with('error', 'GRN belum posted / tidak valid.');
        }

        $remainingMap = $this->remainingByGrnLine($purchase_return->grn, excludeReturnId: (int) $purchase_return->id);

        foreach ($purchase_return->lines as $ln) {
            $qty = (float) $ln->qty;
            if ($qty <= 0.0001) {
                continue;
            }

            $max = (float) ($remainingMap[(int) $ln->purchase_receipt_line_id] ?? 0);
            if ($qty > $max + 0.0001) {
                throw ValidationException::withMessages([
                    'lines' => "Qty return melebihi remaining pada salah satu line.",
                ]);
            }
        }

        $total = (float) $purchase_return->lines()->sum('line_total');
        if ($total <= 0.0001) {
            return back()->with('error', 'Total return harus > 0.');
        }

        DB::transaction(function () use ($purchase_return) {

            $warehouseId = (int) $purchase_return->grn->warehouse_id;
            if ($warehouseId <= 0) {
                throw ValidationException::withMessages(['return' => 'GRN tidak punya warehouse.']);
            }

            // COA
            $invId = (int) Account::where('code', '1201')->value('id');
            $apId = (int) Account::where('code', '2101')->value('id');
            $claimId = (int) Account::where('code', '1305')->value('id');

            if ($invId <= 0 || $apId <= 0 || $claimId <= 0) {
                throw ValidationException::withMessages([
                    'return' => 'COA belum lengkap. Pastikan ada 1201, 2101, dan 1305.',
                ]);
            }

            // hitung AP outstanding (sementara masih basis GRN posted)
            $order = $purchase_return->order;
            $apOutstanding = $order ? $this->calcApOutstandingByGrn($order) : 0.0;

            // split total INV vs EXP
            $invTotal = 0.0;
            $expTotal = 0.0;

            // map expense account by po_line
            $expAccMap = $this->expenseAccountMapFromOrderLines($purchase_return);

            foreach ($purchase_return->lines as $ln) {
                $qty = (float) $ln->qty;
                if ($qty <= 0.0001) {
                    continue;
                }

                $amt = (float) ($ln->line_total ?? 0);
                if ($amt <= 0.0001) {
                    continue;
                }

                $isHpp = $this->isHppLine($purchase_return, $ln);

                if ($isHpp) {
                    $invTotal = round($invTotal + $amt, 2);
                } else {
                    $expTotal = round($expTotal + $amt, 2);
                }

            }

            // =====================================================
            // 1) STOCK OUT (INV ONLY)
            // =====================================================
            foreach ($purchase_return->lines as $ln) {
                $qty = (float) $ln->qty;
                if ($qty <= 0.0001) {
                    continue;
                }

                if (!$this->isHppLine($purchase_return, $ln)) {
                    continue; // expense -> tidak pernah masuk stok
                }

                $this->inventory->stockOut(
                    warehouseId: $warehouseId,
                    itemId: (int) $ln->item_id,
                    qty: (float) $ln->qty,
                    date: $purchase_return->date,
                    sourceType: 'purchase_return',
                    sourceId: (int) $purchase_return->id,
                    notes: "Return {$purchase_return->code} (GRN {$purchase_return->grn->code}) line {$ln->id}",
                    allowNegative: false,
                    lotId: $ln->lot_id ? (int) $ln->lot_id : null,
                    unitCostOverride: $ln->unit_price !== null ? (float) $ln->unit_price : null,
                    affectLotCost: false,
                );
            }

            // =====================================================
            // 2) JOURNAL INV (jika ada invTotal)
            // Cr Inventory; Dr AP/Claim
            // =====================================================
            if ($invTotal > 0.0001) {
                $apPortion = min($apOutstanding, $invTotal);
                $claimPortion = max(0, round($invTotal - $apPortion, 2));

                $linesInv = [];
                $linesInv[] = ['account_id' => $invId, 'debit' => 0, 'credit' => round($invTotal, 2)];

                if ($apPortion > 0.0001) {
                    $linesInv[] = ['account_id' => $apId, 'debit' => round($apPortion, 2), 'credit' => 0];
                }

                if ($claimPortion > 0.0001) {
                    $linesInv[] = ['account_id' => $claimId, 'debit' => round($claimPortion, 2), 'credit' => 0];
                }

                $this->journal->post(
                    $purchase_return->date,
                    JournalService::SRC_PURCHASE_RETURN_INV,
                    (int) $purchase_return->id,
                    "Purchase Return {$purchase_return->code} - Inventory (GRN {$purchase_return->grn->code})",
                    $linesInv
                );

                // kurangi AP outstanding yang tersisa untuk expense portion
                $apOutstanding = max(0, round($apOutstanding - $apPortion, 2));
            }

            // =====================================================
            // 3) JOURNAL EXP (jika ada expTotal)
            // Cr Expense per account; Dr AP/Claim
            // =====================================================
            if ($expTotal > 0.0001) {
                $apPortion = min($apOutstanding, $expTotal);
                $claimPortion = max(0, round($expTotal - $apPortion, 2));

                // group credit expense by account
                $creditByAcc = [];

                foreach ($purchase_return->lines as $ln) {
                    $qty = (float) $ln->qty;
                    if ($qty <= 0.0001) {
                        continue;
                    }

                    $amt = (float) ($ln->line_total ?? 0);
                    if ($amt <= 0.0001) {
                        continue;
                    }

                    if ($this->isHppLine($purchase_return, $ln)) {
                        continue;
                    }
                    // only expense lines

                    $poLineId = (int) ($ln->grnLine?->purchase_order_line_id ?? 0);
                    $accId = (int) ($expAccMap[$poLineId] ?? 0);

                    // fallback: 6110 kalau kosong
                    if ($accId <= 0) {
                        $accId = (int) (Account::where('code', '6110')->value('id') ?? 0);
                        if ($accId <= 0) {
                            throw ValidationException::withMessages([
                                'return' => 'Expense account tidak ditemukan. Set expense_account_id pada PO line, atau buat COA 6110.',
                            ]);
                        }
                    }

                    $creditByAcc[$accId] = round((float) ($creditByAcc[$accId] ?? 0) + $amt, 2);
                }

                $linesExp = [];
                // debit AP/Claim
                if ($apPortion > 0.0001) {
                    $linesExp[] = ['account_id' => $apId, 'debit' => round($apPortion, 2), 'credit' => 0];
                }

                if ($claimPortion > 0.0001) {
                    $linesExp[] = ['account_id' => $claimId, 'debit' => round($claimPortion, 2), 'credit' => 0];
                }

                // credit expense accounts
                foreach ($creditByAcc as $accId => $amt) {
                    if ($amt <= 0.0001) {
                        continue;
                    }

                    $linesExp[] = ['account_id' => (int) $accId, 'debit' => 0, 'credit' => round($amt, 2)];
                }

                if (count($linesExp) < 2) {
                    throw ValidationException::withMessages([
                        'return' => 'Jurnal return expense tidak valid (lines < 2).',
                    ]);
                }

                $this->journal->post(
                    $purchase_return->date,
                    JournalService::SRC_PURCHASE_RETURN_EXP,
                    (int) $purchase_return->id,
                    "Purchase Return {$purchase_return->code} - Expense (GRN {$purchase_return->grn->code})",
                    $linesExp
                );
            }

            // =====================================================
            // 4) Mark posted
            // =====================================================
            $purchase_return->forceFill([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => (int) auth()->id(),
                'total' => round((float) $purchase_return->lines()->sum('line_total'), 2),
            ])->save();
        });

        return back()->with('success', 'Return berhasil diposting.');
    }

    /**
     * VOID Return:
     * - Balikin stok hanya untuk INV lines
     * - Void jurnal inv + exp by source
     */
    public function void(Request $request, PurchaseReturn $purchase_return)
    {
        if ($purchase_return->voided_at) {
            return back()->with('error', 'Return sudah void.');
        }

        if ($purchase_return->status !== 'posted') {
            return back()->with('error', 'Return belum posted.');
        }

        $purchase_return->loadMissing(['grn', 'lines.grnLine', 'lines.item']);

        DB::transaction(function () use ($purchase_return) {

            $warehouseId = (int) $purchase_return->grn->warehouse_id;

            // 1) balikkan stok hanya untuk INV lines
            foreach ($purchase_return->lines as $ln) {
                $qty = (float) $ln->qty;
                if ($qty <= 0.0001) {
                    continue;
                }

                if (!$this->isHppLine($purchase_return, $ln)) {
                    continue;
                }

                $this->inventory->stockIn(
                    warehouseId: $warehouseId,
                    itemId: (int) $ln->item_id,
                    qty: $qty,
                    date: $purchase_return->date,
                    sourceType: 'purchase_return_void',
                    sourceId: (int) $purchase_return->id,
                    notes: "VOID Return {$purchase_return->code} line {$ln->id}",
                    lotId: $ln->lot_id ? (int) $ln->lot_id : null,
                    unitCost: (float) $ln->unit_price,
                );
            }

            // 2) void 2 jurnal
            $this->journal->voidBySource(JournalService::SRC_PURCHASE_RETURN_INV, (int) $purchase_return->id, "VOID Purchase Return {$purchase_return->code}");
            $this->journal->voidBySource(JournalService::SRC_PURCHASE_RETURN_EXP, (int) $purchase_return->id, "VOID Purchase Return {$purchase_return->code}");

            // 3) mark void
            $purchase_return->forceFill([
                'voided_at' => now(),
                'voided_by' => (int) auth()->id(),
            ])->save();
        });

        return back()->with('success', 'Return berhasil di-VOID.');
    }

    // ================================
    // Helpers
    // ================================

    protected function isHppLine(PurchaseReturn $ret, PurchaseReturnLine $ln): bool
    {
        // sumber utama: allocation dari purchase_order_lines
        $poLineId = (int) ($ln->grnLine?->purchase_order_line_id ?? 0);

        if ($poLineId > 0 && Schema::hasColumn('purchase_order_lines', 'allocation')) {
            $alloc = (string) DB::table('purchase_order_lines')->where('id', $poLineId)->value('allocation');
            if ($alloc !== '') {
                return $alloc !== 'expense';
            }
        }

        // fallback: items.default_allocation
        if (Schema::hasColumn('items', 'default_allocation')) {
            $alloc = (string) Item::query()->whereKey((int) $ln->item_id)->value('default_allocation');
            if ($alloc !== '') {
                return $alloc !== 'expense';
            }
        }

        return true; // default hpp
    }

    protected function expenseAccountMapFromOrderLines(PurchaseReturn $ret): array
    {
        // 1) ambil po_line_id yang dipakai oleh return lines
        $poLineIds = $ret->lines
            ->map(fn($l) => (int) ($l->grnLine?->purchase_order_line_id ?? 0))
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        // Map hasil akhir: [po_line_id => expense_account_id]
        $map = [];

        // 2) dari PO line (paling utama)
        if (!empty($poLineIds) && Schema::hasColumn('purchase_order_lines', 'expense_account_id')) {
            $map = DB::table('purchase_order_lines')
                ->whereIn('id', $poLineIds)
                ->pluck('expense_account_id', 'id')
                ->map(fn($v) => (int) ($v ?? 0))
                ->all();
        }

        // 3) fallback dari item master jika PO line kosong
        //    butuh mapping po_line_id -> item_id
        if (!empty($poLineIds) && Schema::hasColumn('items', 'default_expense_account_id')) {
            $poLineItemMap = DB::table('purchase_order_lines')
                ->whereIn('id', $poLineIds)
                ->pluck('item_id', 'id') // [po_line_id => item_id]
                ->map(fn($v) => (int) ($v ?? 0))
                ->all();

            $itemIds = array_values(array_filter(array_unique(array_values($poLineItemMap))));
            $itemDefaultExp = [];

            if (!empty($itemIds)) {
                $itemDefaultExp = Item::query()
                    ->whereIn('id', $itemIds)
                    ->pluck('default_expense_account_id', 'id') // [item_id => acc_id]
                    ->map(fn($v) => (int) ($v ?? 0))
                    ->all();
            }

            foreach ($poLineItemMap as $poLineId => $itemId) {
                $poLineId = (int) $poLineId;
                $itemId = (int) $itemId;

                if (($map[$poLineId] ?? 0) > 0) {
                    continue; // sudah ada dari PO line
                }

                $fallbackAcc = (int) ($itemDefaultExp[$itemId] ?? 0);
                if ($fallbackAcc > 0) {
                    $map[$poLineId] = $fallbackAcc;
                }
            }
        }

        return $map;
    }

    protected function remainingByGrnLine(PurchaseReceipt $grn, ?int $excludeReturnId = null): array
    {
        $grn->loadMissing(['lines']);

        $received = [];
        foreach ($grn->lines as $ln) {
            $received[(int) $ln->id] = (float) $ln->qty_received;
        }

        $q = PurchaseReturnLine::query()
            ->join('purchase_returns as pr', 'pr.id', '=', 'purchase_return_lines.purchase_return_id')
            ->whereNull('pr.voided_at')
            ->where('pr.status', 'posted')
            ->where('pr.purchase_receipt_id', (int) $grn->id);

        if ($excludeReturnId) {
            $q->where('pr.id', '!=', (int) $excludeReturnId);
        }

        $returned = $q->selectRaw('purchase_receipt_line_id, COALESCE(SUM(qty),0) as qty')
            ->groupBy('purchase_receipt_line_id')
            ->pluck('qty', 'purchase_receipt_line_id')
            ->map(fn($v) => (float) $v)
            ->all();

        $remaining = [];
        foreach ($received as $grnLineId => $qtyRecv) {
            $qtyRet = (float) ($returned[$grnLineId] ?? 0);
            $remaining[$grnLineId] = max(0, round($qtyRecv - $qtyRet, 4));
        }

        return $remaining;
    }

    protected function calcApOutstandingByGrn(PurchaseOrder $order): float
    {
        $debt = (float) $order->purchaseReceipts()->where('status', 'posted')->sum('grand_total');

        $paid = (float) $order->activePayments()
            ->selectRaw("COALESCE(SUM(CASE WHEN type='payment' THEN amount ELSE 0 END),0) as n")
            ->value('n');

        $dpApplied = (float) $order->activePayments()
            ->selectRaw("COALESCE(SUM(CASE WHEN type='dp_apply' THEN amount ELSE 0 END),0) as n")
            ->value('n');

        return max(0, round($debt - $paid - $dpApplied, 2));
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
            return (float) str_replace('.', '', $value);
        }

        return (float) $value;
    }
}
