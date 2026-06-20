<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// item_role → account code mapping for inventory auto-fill
// raw_material → 1201, wip → 1202, finished_good → 1203

class OpeningBalanceBatchController extends Controller
{
    public function index(Request $request)
    {
        $q = Journal::query()
            ->whereIn('source_type', ['opening_balance_batch', 'opening_balance_batch_void'])
            ->with(['lines.account'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('from')) {
            $q->whereDate('date', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $q->whereDate('date', '<=', $request->date('to'));
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status === 'active') {
                $q->whereNull('voided_at');
            }

            if ($status === 'void') {
                $q->whereNotNull('voided_at');
            }

        }

        $journals = $q->paginate(30)->withQueryString();

        return view('accounting.opening_balances_batch.index', compact('journals'));
    }

    public function create()
    {
        $accounts = Account::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'is_cash']);

        // Auto-fill persediaan dari stok sistem — split by warehouse
        // RM warehouses  → 1201, WIP warehouses → 1202, FG warehouses → 1203
        $rmCodes     = ['RM'];
        $wipCodes    = ['WIP-CUT', 'WIP-SEW', 'WIP-FIN', 'WIP-PACK', 'WH-TRANSIT'];
        $fgCodes     = ['WH-RTS', 'FG', 'WH-PRD'];
        $rejectCodes = ['REJ-CUT', 'REJ-SEW', 'REJ-FIN', 'REJECT'];

        // Cost source untuk WIP/FG/Reject: item_cost_snapshots (dari closing produksi)
        $latestCost = DB::table('item_cost_snapshots')
            ->selectRaw('item_id, unit_cost')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('item_cost_snapshots')->groupBy('item_id');
            });

        $nonRmCodes = array_merge($wipCodes, $fgCodes, $rejectCodes);

        $inventoryByWarehouse = DB::table('inventory_stocks as s')
            ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->joinSub($latestCost, 'cs', 'cs.item_id', '=', 's.item_id')
            ->where('s.qty', '>', 0)
            ->whereNotNull('cs.unit_cost')
            ->whereIn('w.code', $nonRmCodes)
            ->selectRaw('w.code as wh_code, ROUND(SUM(s.qty * cs.unit_cost), 0) as total_value')
            ->groupBy('w.code')
            ->pluck('total_value', 'wh_code');

        // Cost source untuk RM: avg cost dari GRN (inventory_mutations direction=in),
        // fallback ke last_purchase_price dari master item.
        $rmAvgCost = DB::table('inventory_mutations as m')
            ->join('warehouses as w', 'w.id', '=', 'm.warehouse_id')
            ->where('w.code', 'RM')
            ->where('m.direction', 'in')
            ->where('m.unit_cost', '>', 0)
            ->groupBy('m.item_id')
            ->selectRaw('m.item_id, ROUND(SUM(m.total_cost) / NULLIF(SUM(m.qty_change), 0), 4) as avg_unit_cost')
            ->pluck('avg_unit_cost', 'item_id');

        $rmTotal = (float) DB::table('inventory_stocks as s')
            ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->join('items as i', 'i.id', '=', 's.item_id')
            ->where('w.code', 'RM')
            ->where('s.qty', '>', 0)
            ->get(['s.item_id', 's.qty', 'i.last_purchase_price'])
            ->sum(function ($row) use ($rmAvgCost) {
                $cost = (float) ($rmAvgCost[$row->item_id] ?? $row->last_purchase_price ?? 0);
                return $row->qty * $cost;
            });
        $rmTotal = round($rmTotal, 0);

        $wipTotal    = collect($wipCodes)->sum(fn($c) => (float) ($inventoryByWarehouse[$c] ?? 0));
        $fgTotal     = collect($fgCodes)->sum(fn($c) => (float) ($inventoryByWarehouse[$c] ?? 0));
        $rejectTotal = collect($rejectCodes)->sum(fn($c) => (float) ($inventoryByWarehouse[$c] ?? 0));

        // Prefill HANYA untuk WIP/FG/Reject — dari item_cost_snapshots.
        // 1201 (RM) TIDAK di-prefill karena sudah otomatis terjurnal via Stock Opname
        // (inventory_adjustment journal). Jika di-input ulang di sini akan double-count.
        $prefill = [];
        foreach ([['1202', $wipTotal], ['1203', $fgTotal], ['1204', $rejectTotal]] as [$code, $val]) {
            $account = $accounts->firstWhere('code', $code);
            if ($account && $val > 0) {
                $prefill[$account->id] = $val;
            }
        }

        return view('accounting.opening_balances_batch.create', compact('accounts', 'prefill'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],

            'account_id' => ['required', 'array'],
            'account_id.*' => ['nullable', 'integer', 'exists:accounts,id'],

            'debit' => ['required', 'array'],
            'credit' => ['required', 'array'],
        ]);

        $accIds = $data['account_id'];
        $debits = $data['debit'];
        $credits = $data['credit'];

        if (count($accIds) !== count($debits) || count($accIds) !== count($credits)) {
            throw ValidationException::withMessages([
                'account_id' => 'Format baris opening tidak valid.',
            ]);
        }

        // build lines, skip empty rows
        $lines = [];
        $sumD = 0.0;
        $sumC = 0.0;

        foreach ($accIds as $i => $aid) {
            $d = (float) ($debits[$i] ?? 0);
            $c = (float) ($credits[$i] ?? 0);

            // normalize: tidak boleh dua-duanya isi
            if ($d > 0 && $c > 0) {
                throw ValidationException::withMessages([
                    "debit.$i" => 'Pilih salah satu: debit atau credit.',
                ]);
            }

            if ($d <= 0 && $c <= 0) {
                continue;
            }

            if (empty($aid)) {
                throw ValidationException::withMessages([
                    "account_id.$i" => 'Akun wajib dipilih untuk baris yang punya debit atau kredit.',
                ]);
            }

            $lines[] = [
                'account_id' => (int) $aid,
                'debit' => max(0, $d),
                'credit' => max(0, $c),
            ];

            $sumD += max(0, $d);
            $sumC += max(0, $c);
        }

        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'account_id' => 'Minimal 2 baris opening balance.',
            ]);
        }

        // harus balance
        if (round($sumD, 2) !== round($sumC, 2)) {
            throw ValidationException::withMessages([
                'account_id' => "Tidak balance. Total Debit " . number_format($sumD, 2) .
                " != Total Credit " . number_format($sumC, 2),
            ]);
        }

        return DB::transaction(function () use ($data, $lines, $sumD) {
            // Cegah dobel opening batch aktif di tanggal yang sama (opsional, tapi aku sarankan)
            $exists = Journal::query()
                ->where('source_type', 'opening_balance_batch')
                ->whereDate('date', $data['date'])
                ->whereNull('voided_at')
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'date' => 'Opening balance batch aktif pada tanggal ini sudah ada. VOID dulu jika mau ganti.',
                ]);
            }

            $desc = $data['description'] ?: 'Opening Balance (Batch)';

            $j = Journal::create([
                'date' => $data['date'],
                'description' => $desc,
                'source_type' => 'opening_balance_batch',
                'source_id' => null,
                'posted_at' => now(),
                'voided_at' => null,
            ]);

            foreach ($lines as $ln) {
                JournalLine::create([
                    'journal_id' => $j->id,
                    'account_id' => $ln['account_id'],
                    'debit' => $ln['debit'],
                    'credit' => $ln['credit'],
                ]);
            }

            return redirect()
                ->route('accounting.opening-balances-batch.index')
                ->with('status', 'ok')
                ->with('message', 'Opening Balance (batch) berhasil diposting.');
        });
    }

    public function void(Request $request, Journal $journal)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($journal, $data) {
            $locked = Journal::query()->whereKey($journal->id)->lockForUpdate()->firstOrFail();

            if ($locked->source_type !== 'opening_balance_batch') {
                throw ValidationException::withMessages(['journal' => 'Journal ini bukan opening balance batch.']);
            }
            if (!$locked->posted_at) {
                throw ValidationException::withMessages(['journal' => 'Journal belum POSTED.']);
            }
            if ($locked->voided_at) {
                return back()->with('status', 'ok')->with('message', 'Opening Balance sudah VOID.');
            }

            $already = Journal::query()
                ->where('source_type', 'opening_balance_batch_void')
                ->where('source_id', $locked->id)
                ->exists();

            if ($already) {
                $locked->update(['voided_at' => now()]);
                return back()->with('status', 'ok')->with('message', 'Opening Balance sudah pernah di-VOID.');
            }

            $locked->load('lines');

            if ($locked->lines->isEmpty()) {
                throw ValidationException::withMessages(['journal' => 'Opening balance tidak punya lines.']);
            }

            // tandai void original
            $locked->update(['voided_at' => now()]);

            $desc = 'REVERSAL: ' . ($locked->description ?: 'Opening Balance (Batch)');
            if (!empty($data['reason'])) {
                $desc .= ' | ' . $data['reason'];
            }

            $rev = Journal::create([
                'date' => $locked->date,
                'description' => $desc,
                'source_type' => 'opening_balance_batch_void',
                'source_id' => $locked->id,
                'posted_at' => now(),
                'voided_at' => null,
            ]);

            // reversal: swap debit/credit per line
            foreach ($locked->lines as $ln) {
                JournalLine::create([
                    'journal_id' => $rev->id,
                    'account_id' => $ln->account_id,
                    'debit' => (float) $ln->credit,
                    'credit' => (float) $ln->debit,
                ]);
            }

            return redirect()
                ->route('accounting.opening-balances-batch.index')
                ->with('status', 'ok')
                ->with('message', 'Opening Balance berhasil di-VOID (reversal batch dibuat).');
        });
    }
}
