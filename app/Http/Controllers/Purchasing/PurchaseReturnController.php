<?php

namespace App\Http\Controllers\Purchasing;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnLine;
use App\Services\Accounting\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseReturnController extends Controller
{
    public function __construct(
        protected JournalService $journal,
        protected \App\Services\Inventory\InventoryService $inventory, // sesuaikan class kamu
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

        // preload lines dengan remaining qty
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
                'qty' => round($rem, 4), // default = remaining (boleh kamu ubah jadi 0 kalau prefer)
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

    public function post(Request $request, PurchaseReturn $purchase_return)
    {
        if ($purchase_return->voided_at) {
            return back()->with('error', 'Return sudah void.');
        }

        if ($purchase_return->status !== 'draft') {
            return back()->with('error', 'Return sudah posted.');
        }

        $purchase_return->loadMissing(['grn.warehouse', 'grn.lines', 'order', 'lines.grnLine']);

        if (!$purchase_return->grn || $purchase_return->grn->status !== 'posted') {
            return back()->with('error', 'GRN belum posted / tidak valid.');
        }

        // validasi remaining per line (final)
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

        DB::transaction(function () use ($purchase_return, $total) {

            $warehouseId = (int) $purchase_return->grn->warehouse_id;
            if ($warehouseId <= 0) {
                throw ValidationException::withMessages(['return' => 'GRN tidak punya warehouse.']);
            }

            // 1) STOCK OUT per line (lot aware)
            foreach ($purchase_return->lines as $ln) {
                $qty = (float) $ln->qty;
                if ($qty <= 0.0001) {
                    continue;
                }

                $this->inventory->stockOut(
                    warehouseId: (int) $purchase_return->grn->warehouse_id,
                    itemId: (int) $ln->item_id,
                    qty: (float) $ln->qty,
                    date: $purchase_return->date,
                    sourceType: 'purchase_return',
                    sourceId: (int) $purchase_return->id,
                    notes: "Return {$purchase_return->code} (GRN {$purchase_return->grn->code}) line {$ln->id}",
                    allowNegative: false,
                    lotId: $ln->lot_id ? (int) $ln->lot_id : null,
                    unitCostOverride: $ln->unit_price !== null ? (float) $ln->unit_price : null,
                    affectLotCost: false, // penting: return jangan ngacak moving average lot kain
                );
            }

            // 2) JOURNAL (inventory turun, AP turun / claim naik)
            $invId = (int) Account::where('code', '1201')->value('id');
            $apId = (int) Account::where('code', '2101')->value('id');
            $claimId = (int) Account::where('code', '1305')->value('id');

            if ($invId <= 0 || $apId <= 0 || $claimId <= 0) {
                throw ValidationException::withMessages([
                    'return' => 'COA belum lengkap. Pastikan ada 1201, 2101, dan 1305.',
                ]);
            }

            $order = $purchase_return->order;
            $apOutstanding = $order ? $this->calcApOutstandingByGrn($order) : 0.0;

            $apPortion = min($apOutstanding, $total);
            $claimPortion = max(0, round($total - $apPortion, 2));

            $lines = [];
            // credit inventory
            $lines[] = ['account_id' => $invId, 'debit' => 0, 'credit' => $total];

            if ($apPortion > 0.0001) {
                $lines[] = ['account_id' => $apId, 'debit' => $apPortion, 'credit' => 0];
            }
            if ($claimPortion > 0.0001) {
                $lines[] = ['account_id' => $claimId, 'debit' => $claimPortion, 'credit' => 0];
            }

            $j = $this->journal->post(
                $purchase_return->date,
                JournalService::SRC_PURCHASE_RETURN,
                (int) $purchase_return->id,
                "Purchase Return {$purchase_return->code} (GRN {$purchase_return->grn->code})",
                $lines
            );

            $purchase_return->forceFill([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => (int) auth()->id(),
                'total' => round($total, 2),
                'journal_id' => $j->id,
            ])->save();
        });

        return back()->with('success', 'Return berhasil diposting.');
    }

    public function void(Request $request, PurchaseReturn $purchase_return)
    {
        if ($purchase_return->voided_at) {
            return back()->with('error', 'Return sudah void.');
        }

        if ($purchase_return->status !== 'posted') {
            return back()->with('error', 'Return belum posted.');
        }

        $purchase_return->loadMissing(['grn', 'lines']);

        DB::transaction(function () use ($purchase_return) {

            // 1) balikkan stok (stock in)
            $warehouseId = (int) $purchase_return->grn->warehouse_id;

            foreach ($purchase_return->lines as $ln) {
                $qty = (float) $ln->qty;
                if ($qty <= 0.0001) {
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

            // 2) void jurnal by source (atau by journal_id)
            if (!empty($purchase_return->journal_id)) {
                $this->journal->voidJournal((int) $purchase_return->journal_id, "VOID Purchase Return {$purchase_return->code}");
            } else {
                $this->journal->voidBySource(JournalService::SRC_PURCHASE_RETURN, (int) $purchase_return->id, "VOID Purchase Return {$purchase_return->code}");
            }

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
