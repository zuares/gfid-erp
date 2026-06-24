<?php

namespace App\Http\Controllers\Purchasing;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Item;
use App\Models\InventoryStock;
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

    public function index(Request $request)
    {
        $query = PurchaseReturn::query()
            ->with(['grn.warehouse', 'supplier', 'order'])
            ->withCount('lines')
            ->withSum('lines as total_qty', 'qty')
            ->orderByDesc('date')
            ->orderByDesc('id');

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('grn', fn($qq) => $qq->where('code', 'like', "%{$search}%"))
                    ->orWhereHas('supplier', fn($qq) => $qq->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"));
            });
        }

        $status = (string) $request->input('status', '');
        if (in_array($status, ['draft', 'posted'], true)) {
            $query->where('status', $status)->whereNull('voided_at');
        } elseif ($status === 'void') {
            $query->whereNotNull('voided_at');
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->input('to_date'));
        }

        $returns = $query->paginate(15)->withQueryString();

        $summary = (clone $query)
            ->reorder()
            ->selectRaw('COUNT(*) as total_returns')
            ->selectRaw("SUM(CASE WHEN status = 'draft' AND voided_at IS NULL THEN 1 ELSE 0 END) as draft_count")
            ->selectRaw("SUM(CASE WHEN status = 'posted' AND voided_at IS NULL THEN 1 ELSE 0 END) as posted_count")
            ->selectRaw("SUM(CASE WHEN voided_at IS NOT NULL THEN 1 ELSE 0 END) as void_count")
            ->selectRaw('COALESCE(SUM(total), 0) as total_value')
            ->selectRaw('MAX(date) as last_date')
            ->first();

        return view('purchasing.purchase_returns.index', compact('returns', 'summary', 'search', 'status'));
    }

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
        $returnLineByGrnLine = $purchase_return->lines->keyBy('purchase_receipt_line_id');

        $warehouseId = (int) ($purchase_return->grn?->warehouse_id ?? 0);
        $grnItemIds = $purchase_return->grn?->lines?->pluck('item_id')->filter()->unique() ?? collect();
        $stockByItem = $warehouseId > 0
            ? InventoryStock::query()
                ->where('warehouse_id', $warehouseId)
                ->whereIn('item_id', $grnItemIds)
                ->pluck('qty', 'item_id')
            : collect();

        $stockByLot = DB::table('lots')
            ->whereIn('id', $purchase_return->grn?->lines?->pluck('lot_id')->filter()->unique() ?? collect())
            ->pluck('qty_onhand', 'id')
            ->map(fn($qty) => max(0, (float) $qty));
        $lineStockMap = [];
        $lineMaxMap = [];
        $lineInventoryMap = [];
        $returnRows = collect();

        $sourceLines = ($purchase_return->status === 'draft' && ! $purchase_return->voided_at)
            ? ($purchase_return->grn?->lines ?? collect())
            : $purchase_return->lines
                ->map(fn ($line) => $line->grnLine)
                ->filter()
                ->values();

        foreach ($sourceLines as $grnLine) {
            $line = $returnLineByGrnLine->get((int) $grnLine->id);
            $isInventory = $line
                ? $this->isHppLine($purchase_return, $line)
                : $this->isHppGrnLine($purchase_return, $grnLine);
            $itemId = (int) $grnLine->item_id;
            $returnable = (float) ($remainingMap[(int) $grnLine->id] ?? 0);
            $stock = (float) ($stockByItem[$itemId] ?? 0);
            $qty = (float) ($line?->qty ?? 0);

            if ($line) {
                $lineInventoryMap[$line->id] = $isInventory;
                $lineStockMap[$line->id] = $stock;
            }

            if (!$isInventory) {
                $max = $returnable;
                $lotAvailable = null;
            } else {
                $available = max(0, $stock);
                $lotId = (int) ($grnLine->lot_id ?? 0);
                $lotAvailable = $lotId > 0 ? (float) ($stockByLot[$lotId] ?? 0) : $available;
                $max = max(0, min($returnable, $available, $lotAvailable));
            }

            if ($line) {
                $lineMaxMap[$line->id] = $max;
            }

            $unitPrice = (float) ($line?->unit_price ?? $grnLine->unit_price ?? 0);

            $returnRows->push((object) [
                'line' => $line,
                'grnLine' => $grnLine,
                'item' => $line?->item ?? $grnLine->item,
                'purchase_receipt_line_id' => (int) $grnLine->id,
                'item_id' => $itemId,
                'lot_id' => $grnLine->lot_id ? (int) $grnLine->lot_id : null,
                'received' => (float) ($grnLine->qty_received ?? 0),
                'remaining' => $returnable,
                'stock' => $stock,
                'lot_stock' => $lotAvailable,
                'max_return' => $max,
                'is_inventory' => $isInventory,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => round($qty * $unitPrice, 2),
                'notes' => $line?->notes,
            ]);
        }

        $inventoryRows = $returnRows
            ->filter(fn($row) => (bool) $row->is_inventory && (float) $row->qty > 0.0001);

        $stockReady = $returnRows
            ->every(fn($row) => (float) $row->qty <= (float) $row->max_return + 0.0001);

        if ($stockReady) {
            $stockReady = $inventoryRows
                ->groupBy('item_id')
                ->every(fn($rows, $itemId) => (float) $rows->sum('qty')
                    <= (float) ($stockByItem[$itemId] ?? 0) + 0.0001);
        }

        if ($stockReady) {
            $stockReady = $inventoryRows
                ->filter(fn($row) => (int) ($row->lot_id ?? 0) > 0)
                ->groupBy('lot_id')
                ->every(fn($rows, $lotId) => (float) $rows->sum('qty')
                    <= (float) ($stockByLot[$lotId] ?? 0) + 0.0001);
        }

        $mutationCount = DB::table('inventory_mutations')
            ->where('source_type', 'purchase_return')
            ->where('source_id', (int) $purchase_return->id)
            ->count();
        $journalCount = DB::table('journals')
            ->whereIn('source_type', [JournalService::SRC_PURCHASE_RETURN_INV, JournalService::SRC_PURCHASE_RETURN_EXP])
            ->where('source_id', (int) $purchase_return->id)
            ->count();

        $journalEffect = $this->journalEffectPreview($purchase_return, $returnRows);

        return view('purchasing.purchase_returns.show', [
            'ret' => $purchase_return,
            'remainingMap' => $remainingMap,
            'lineStockMap' => $lineStockMap,
            'lineMaxMap' => $lineMaxMap,
            'lineInventoryMap' => $lineInventoryMap,
            'returnRows' => $returnRows,
            'stockReady' => $stockReady,
            'mutationCount' => $mutationCount,
            'journalCount' => $journalCount,
            'journalEffect' => $journalEffect,
        ]);
    }

    public function update(Request $request, PurchaseReturn $purchase_return)
    {
        if ($purchase_return->status !== 'draft' || $purchase_return->voided_at) {
            return back()->with('error', 'Return tidak bisa diubah (sudah posted/void).');
        }

        if (!$request->filled('date')) {
            $request->merge(['date' => (string) $purchase_return->date]);
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['array'],
            'lines.*.id' => ['nullable', 'integer'],
            'lines.*.purchase_receipt_line_id' => ['required', 'integer'],
            'lines.*.qty' => ['nullable', 'string'],
            'lines.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $purchase_return->loadMissing(['grn.lines']);
        $grnLines = $purchase_return->grn?->lines?->keyBy('id') ?? collect();

        $remainingMap = $this->remainingByGrnLine($purchase_return->grn, excludeReturnId: (int) $purchase_return->id);

        DB::transaction(function () use ($purchase_return, $data, $remainingMap, $grnLines) {
            $purchase_return->date = $data['date'];
            $purchase_return->notes = $data['notes'] ?? null;
            $purchase_return->save();

            foreach (($data['lines'] ?? []) as $row) {
                $grnLineId = (int) ($row['purchase_receipt_line_id'] ?? 0);
                $grnLine = $grnLines->get($grnLineId);
                if (!$grnLine) {
                    continue;
                }

                $qty = $this->toNumber($row['qty'] ?? 0);
                $qty = max(0, round($qty, 4));

                $max = (float) ($remainingMap[$grnLineId] ?? 0);

                if ($qty > $max + 0.0001) {
                    throw ValidationException::withMessages([
                        "lines.{$grnLineId}.qty" => "Qty melebihi sisa yang bisa diretur ({$max}).",
                    ]);
                }

                $line = $purchase_return->lines()
                    ->where('purchase_receipt_line_id', $grnLineId)
                    ->first();

                if ($line && $qty <= 0.0001) {
                    $line->delete();
                    continue;
                }

                if (!$line && $qty <= 0.0001) {
                    continue;
                }

                if (!$line) {
                    $line = new PurchaseReturnLine([
                        'purchase_receipt_line_id' => $grnLineId,
                        'item_id' => (int) $grnLine->item_id,
                        'lot_id' => $grnLine->lot_id ? (int) $grnLine->lot_id : null,
                        'unit_price' => (float) ($grnLine->unit_price ?? 0),
                    ]);
                    $line->purchase_return_id = (int) $purchase_return->id;
                }

                $unit = (float) ($line->unit_price ?? $grnLine->unit_price ?? 0);
                $line->item_id = (int) $grnLine->item_id;
                $line->lot_id = $grnLine->lot_id ? (int) $grnLine->lot_id : null;
                $line->unit_price = $unit;
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

        $warehouseId = (int) ($purchase_return->grn?->warehouse_id ?? 0);
        if ($warehouseId <= 0) {
            throw ValidationException::withMessages(['return' => 'Gudang penerimaan tidak ditemukan.']);
        }

        $stockRequired = [];
        foreach ($purchase_return->lines as $ln) {
            $qty = (float) $ln->qty;
            if ($qty <= 0.0001 || !$this->isHppLine($purchase_return, $ln)) {
                continue;
            }

            $itemId = (int) $ln->item_id;
            $stockRequired[$itemId] = (float) ($stockRequired[$itemId] ?? 0) + $qty;
        }

        if ($stockRequired) {
            $stocks = InventoryStock::query()
                ->where('warehouse_id', $warehouseId)
                ->whereIn('item_id', array_keys($stockRequired))
                ->pluck('qty', 'item_id');

            foreach ($stockRequired as $itemId => $requiredQty) {
                $available = (float) ($stocks[$itemId] ?? 0);
                if ($available + 0.0000001 < $requiredQty) {
                    $itemCode = (string) ($purchase_return->lines->firstWhere('item_id', $itemId)?->item?->code ?? ('Item #' . $itemId));
                    throw ValidationException::withMessages([
                        'stock' => "Stok {$itemCode} tidak cukup. Tersedia "
                            . number_format($available, 4, ',', '.')
                            . ', akan diretur ' . number_format($requiredQty, 4, ',', '.') . '.',
                    ]);
                }
            }
        }

        $lotRequired = $purchase_return->lines
            ->filter(fn($line) => (float) $line->qty > 0.0001
                && (int) ($line->lot_id ?? 0) > 0
                && $this->isHppLine($purchase_return, $line))
            ->groupBy('lot_id')
            ->map(fn($lines) => (float) $lines->sum('qty'));

        if ($lotRequired->isNotEmpty()) {
            $lotStocks = DB::table('lots')
                ->whereIn('id', $lotRequired->keys())
                ->pluck('qty_onhand', 'id');

            foreach ($lotRequired as $lotId => $requiredQty) {
                $available = (float) ($lotStocks[$lotId] ?? 0);
                if ($available + 0.0000001 < $requiredQty) {
                    throw ValidationException::withMessages([
                        'stock' => 'Saldo lot asal tidak cukup. Tersedia '
                            . number_format($available, 4, ',', '.')
                            . ', akan diretur ' . number_format($requiredQty, 4, ',', '.') . '.',
                    ]);
                }
            }
        }

        DB::transaction(function () use ($purchase_return) {
            $lockedReturn = PurchaseReturn::query()
                ->whereKey($purchase_return->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReturn->status !== 'draft' || $lockedReturn->voided_at) {
                throw ValidationException::withMessages([
                    'return' => 'Return sudah diproses. Muat ulang halaman.',
                ]);
            }

            $warehouseId = (int) $purchase_return->grn->warehouse_id;
            if ($warehouseId <= 0) {
                throw ValidationException::withMessages(['return' => 'GRN tidak punya warehouse.']);
            }

            // COA
            $inventoryAccountIds = Account::query()
                ->whereIn('code', [JournalService::CODE_INV_RAW, JournalService::CODE_INV_PACKAGING])
                ->pluck('id', 'code')
                ->map(fn($id) => (int) $id);
            $apId = (int) Account::where('code', '2101')->value('id');
            $claimId = (int) Account::where('code', '1305')->value('id');

            if (($inventoryAccountIds[JournalService::CODE_INV_RAW] ?? 0) <= 0 || $apId <= 0 || $claimId <= 0) {
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
            $invCreditByAcc = [];

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
                    $invCode = $this->inventoryAccountCodeForReturnLine($ln);
                    $invAccId = (int) ($inventoryAccountIds[$invCode] ?? $inventoryAccountIds[JournalService::CODE_INV_RAW] ?? 0);
                    $invCreditByAcc[$invAccId] = round((float) ($invCreditByAcc[$invAccId] ?? 0) + $amt, 2);
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
                    affectLotCost: true,
                    strictNonNegative: true,
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
                foreach ($invCreditByAcc as $accId => $amount) {
                    $amount = round((float) $amount, 2);
                    if ($amount > 0.0001) {
                        $linesInv[] = ['account_id' => (int) $accId, 'debit' => 0, 'credit' => $amount];
                    }
                }

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
            $lockedReturn = PurchaseReturn::query()
                ->whereKey($purchase_return->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReturn->status !== 'posted' || $lockedReturn->voided_at) {
                throw ValidationException::withMessages([
                    'return' => 'Return sudah berubah status. Muat ulang halaman.',
                ]);
            }

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

            // 4) Tahap 9 — jika return ini berasal dari QC, unlink QC
            if (!empty($purchase_return->qc_id)) {
                $qc = \App\Models\PurchaseReceiptQc::find((int) $purchase_return->qc_id);
                if ($qc && (int) ($qc->purchase_return_id ?? 0) === (int) $purchase_return->id) {
                    $voidNote = '[Return ' . $purchase_return->code . ' di-VOID pada ' . now()->format('d/m/Y H:i') . ']';
                    $existingNotes = $qc->resolution_notes ?? '';
                    $qc->update([
                        'purchase_return_id' => null,
                        'resolved_at'        => null,
                        // Pertahankan resolution_type agar user tahu rencana awal
                        // Tambahkan keterangan void di resolution_notes
                        'resolution_notes'   => trim($existingNotes . "\n" . $voidNote),
                    ]);
                }
            }
        });

        return back()->with('success', 'Return berhasil di-VOID.');
    }

    // ================================
    // Helpers
    // ================================

    protected function isHppLine(PurchaseReturn $ret, PurchaseReturnLine $ln): bool
    {
        return $this->isHppGrnLine($ret, $ln->grnLine, (int) $ln->item_id);
    }

    protected function isHppGrnLine(PurchaseReturn $ret, $grnLine, ?int $fallbackItemId = null): bool
    {
        // sumber utama: allocation dari purchase_order_lines
        $poLineId = (int) ($grnLine?->purchase_order_line_id ?? 0);

        if ($poLineId > 0 && Schema::hasColumn('purchase_order_lines', 'allocation')) {
            $alloc = (string) DB::table('purchase_order_lines')->where('id', $poLineId)->value('allocation');
            if ($alloc !== '') {
                return $alloc !== 'expense';
            }
        }

        // fallback: items.default_allocation
        if (Schema::hasColumn('items', 'default_allocation')) {
            $itemId = (int) ($grnLine?->item_id ?? $fallbackItemId ?? 0);
            $alloc = (string) Item::query()->whereKey($itemId)->value('default_allocation');
            if ($alloc !== '') {
                return $alloc !== 'expense';
            }
        }

        return true; // default hpp
    }

    protected function inventoryAccountCodeForReturnLine(PurchaseReturnLine $line): string
    {
        $role = strtolower((string) ($line->item?->item_role ?? ''));

        return match ($role) {
            'shipping_supply' => JournalService::CODE_INV_PACKAGING,
            'finished_good' => JournalService::CODE_INV_FG,
            'wip' => JournalService::CODE_INV_WIP,
            default => JournalService::CODE_INV_RAW,
        };
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

        // Kurangi return yang sudah posted (bukan void) agar return ke-2, ke-3 dst
        // tidak salah alokasi ke AP — seharusnya masuk Piutang Supplier (1305).
        $returned = (float) PurchaseReturn::query()
            ->where('purchase_order_id', $order->id)
            ->where('status', 'posted')
            ->whereNull('voided_at')
            ->sum('total');

        return max(0, round($debt - $paid - $dpApplied - $returned, 2));
    }

    protected function journalEffectPreview(PurchaseReturn $ret, $returnRows): array
    {
        $rows = collect($returnRows ?? [])->filter(fn($row) => (float) ($row->qty ?? 0) > 0.0001);

        $inventoryTotal = round((float) $rows
            ->filter(fn($row) => (bool) ($row->is_inventory ?? true))
            ->sum(fn($row) => (float) ($row->line_total ?? 0)), 2);

        $expenseTotal = round((float) $rows
            ->reject(fn($row) => (bool) ($row->is_inventory ?? true))
            ->sum(fn($row) => (float) ($row->line_total ?? 0)), 2);

        $total = round($inventoryTotal + $expenseTotal, 2);
        $apOutstanding = 0.0;

        if (($ret->status ?? '') === 'draft' && ! $ret->voided_at && $ret->order) {
            $apOutstanding = $this->calcApOutstandingByGrn($ret->order);
        }

        $apPortion = min($apOutstanding, $total);
        $claimPortion = max(0, round($total - $apPortion, 2));

        return [
            'total' => $total,
            'inventory_total' => $inventoryTotal,
            'expense_total' => $expenseTotal,
            'ap_outstanding' => round($apOutstanding, 2),
            'ap_portion' => round($apPortion, 2),
            'claim_portion' => round($claimPortion, 2),
        ];
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
