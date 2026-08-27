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

        // Cost fallback untuk item yang belum punya HPP master.
        $latestCost = DB::table('item_cost_snapshots')
            ->selectRaw('item_id, unit_cost')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('item_cost_snapshots')->groupBy('item_id');
            });

        $nonRmCodes = array_merge($wipCodes, $fgCodes, $rejectCodes);

        $inventoryByWarehouse = DB::table('inventory_stocks as s')
            ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->join('items as i', 'i.id', '=', 's.item_id')
            ->leftJoinSub($latestCost, 'cs', 'cs.item_id', '=', 's.item_id')
            ->where('s.qty', '>', 0)
            ->whereIn('w.code', $nonRmCodes)
            ->selectRaw('w.code as wh_code, ROUND(SUM(s.qty * COALESCE(NULLIF(i.hpp, 0), cs.unit_cost, 0)), 0) as total_value')
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

        $customerReceivableTotal = (float) DB::table('sales_invoices')
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['cancelled', 'canceled', 'void', 'voided']);
            })
            ->where(function ($q) {
                $q->whereNull('paid_at')
                    ->orWhereNull('payment_status')
                    ->orWhereNotIn('payment_status', ['paid', 'settled']);
            })
            ->where(function ($q) {
                $q->whereNull('channel')
                    ->orWhereNotIn('channel', ['marketplace', 'shopee', 'tokopedia', 'tiktok']);
            })
            ->sum('grand_total');

        $marketplaceInvoiceTotal = (float) DB::table('sales_invoices')
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['cancelled', 'canceled', 'void', 'voided']);
            })
            ->where(function ($q) {
                $q->whereNull('paid_at')
                    ->orWhereNull('payment_status')
                    ->orWhereNotIn('payment_status', ['paid', 'settled']);
            })
            ->whereIn('channel', ['marketplace', 'shopee', 'tokopedia', 'tiktok'])
            ->selectRaw('SUM(COALESCE(NULLIF(net_payout_actual, 0), grand_total, 0)) as total')
            ->value('total');

        $marketplaceOrderTotal = 0.0;
        if ($marketplaceInvoiceTotal <= 0) {
            $marketplaceOrderTotal = (float) DB::table('marketplace_orders')
                ->whereNull('cancelled_at')
                ->where(function ($q) {
                    $q->whereNull('status')
                        ->orWhereNotIn('status', ['cancelled', 'canceled', 'void', 'voided']);
                })
                ->where(function ($q) {
                    $q->whereNull('payment_status')
                        ->orWhereNotIn('payment_status', ['paid', 'settled']);
                })
                ->selectRaw('SUM(COALESCE(NULLIF(net_payout_estimated, 0), NULLIF(total_paid_customer, 0), NULLIF(total_amount, 0), 0)) as total')
                ->value('total');
        }
        $marketplaceReceivableTotal = round($marketplaceInvoiceTotal + $marketplaceOrderTotal, 0);

        $payrollLines = DB::table('piecework_payroll_lines')
            ->selectRaw('payroll_period_id, SUM(amount) as lines_total')
            ->groupBy('payroll_period_id');

        $payrollPeriodPayableTotal = (float) DB::table('piecework_payroll_periods as p')
            ->leftJoinSub($payrollLines, 'pl', 'pl.payroll_period_id', '=', 'p.id')
            ->whereNull('p.paid_at')
            ->whereIn('p.status', ['final', 'posted'])
            ->whereIn('p.module', ['cutting', 'sewing', 'finishing', 'packing'])
            ->selectRaw('SUM(COALESCE(NULLIF(p.total_amount, 0), pl.lines_total, 0)) as total')
            ->value('total');
        $payrollPeriodPayableTotal = round($payrollPeriodPayableTotal, 0);

        $payrollJournalPayableTotal = (float) DB::table('journals as j')
            ->join('journal_lines as jl', 'jl.journal_id', '=', 'j.id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereNull('j.voided_at')
            ->where('a.code', '2102')
            ->whereIn('j.source_type', [
                'cutting_job_wage',
                'cutting_wip',
                'sewing_pickup_wage',
                'sewing_return_ok',
                'sewing_reject_rework_ok',
                'piecework_payroll_period_accrual',
            ])
            ->where(function ($q) {
                $q->where('j.source_type', '!=', 'sewing_pickup_wage')
                    ->orWhereExists(function ($sub) {
                        $sub->selectRaw('1')
                            ->from('sewing_pickup_lines as spl')
                            ->join('sewing_pickups as sp', 'sp.id', '=', 'spl.sewing_pickup_id')
                            ->whereColumn('spl.id', 'j.source_id')
                            ->where('spl.status', '!=', 'void')
                            ->where('sp.status', '!=', 'void');
                    });
            })
            ->selectRaw('SUM(jl.credit - jl.debit) as balance')
            ->value('balance');
        $payrollJournalPayableTotal = round(max($payrollJournalPayableTotal, 0), 0);

        $unpostedSewingPickupWageTotal = (float) DB::table('sewing_pickup_lines as spl')
            ->join('sewing_pickups as sp', 'sp.id', '=', 'spl.sewing_pickup_id')
            ->where('spl.status', '!=', 'void')
            ->where('sp.status', '!=', 'void')
            ->where('spl.wage_per_pcs', '>', 0)
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('journals as j')
                    ->whereColumn('j.source_id', 'spl.id')
                    ->where('j.source_type', 'sewing_pickup_wage')
                    ->whereNull('j.voided_at');
            })
            ->sum(DB::raw('spl.qty_bundle * spl.wage_per_pcs'));
        $unpostedSewingPickupWageTotal = round($unpostedSewingPickupWageTotal, 0);

        $payrollPayableTotal = max($payrollPeriodPayableTotal, $payrollJournalPayableTotal + $unpostedSewingPickupWageTotal);

        // Detail breakdown untuk modal "Cek Detail".
        $inventoryDetailRows = function (array $codes) use ($latestCost) {
            return DB::table('inventory_stocks as s')
                ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
                ->join('items as i', 'i.id', '=', 's.item_id')
                ->leftJoinSub($latestCost, 'cs', 'cs.item_id', '=', 's.item_id')
                ->where('s.qty', '>', 0)
                ->whereIn('w.code', $codes)
                ->selectRaw('
                    w.code as wh_code,
                    i.code as item_code,
                    i.name as item_name,
                    s.qty,
                    COALESCE(NULLIF(i.hpp, 0), cs.unit_cost, 0) as unit_cost,
                    ROUND(s.qty * COALESCE(NULLIF(i.hpp, 0), cs.unit_cost, 0), 0) as total_value
                ')
                ->orderBy('w.code')
                ->orderByDesc('total_value')
                ->limit(30)
                ->get()
                ->map(fn($r) => [
                    'label' => trim(($r->item_code ?? '-') . ' · ' . ($r->item_name ?? '-')),
                    'sub' => trim(($r->wh_code ?? '-') . ' · ' . number_format((float) $r->qty, 2, ',', '.') . ' × Rp' . number_format((float) $r->unit_cost, 0, ',', '.')),
                    'value' => (float) $r->total_value,
                ])
                ->values();
        };

        $wipDetail = $inventoryDetailRows($wipCodes);
        $fgDetail = $inventoryDetailRows($fgCodes);

        // Piutang Dagang — top 20 invoice
        $piutangDagangRows = DB::table('sales_invoices')
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['cancelled', 'canceled', 'void', 'voided']);
            })
            ->where(function ($q) {
                $q->whereNull('paid_at')
                    ->orWhereNull('payment_status')
                    ->orWhereNotIn('payment_status', ['paid', 'settled']);
            })
            ->where(function ($q) {
                $q->whereNull('channel')
                    ->orWhereNotIn('channel', ['marketplace', 'shopee', 'tokopedia', 'tiktok']);
            })
            ->orderByDesc('grand_total')
            ->limit(20)
            ->get(['code', 'channel_order_no', 'grand_total', 'date', 'customer_id']);

        $piutangDagangDetail = $piutangDagangRows->map(fn($r) => [
            'label' => $r->code ?: $r->channel_order_no ?: '—',
            'sub'   => trim(($r->date ?? '-') . ' · Customer #' . ($r->customer_id ?: '-')),
            'value' => (float) $r->grand_total,
        ])->values();

        // Saldo Marketplace / Clearing — top 20 invoice/order
        $piutangMpRows = DB::table('sales_invoices')
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['cancelled', 'canceled', 'void', 'voided']);
            })
            ->where(function ($q) {
                $q->whereNull('paid_at')
                    ->orWhereNull('payment_status')
                    ->orWhereNotIn('payment_status', ['paid', 'settled']);
            })
            ->whereIn('channel', ['marketplace', 'shopee', 'tokopedia', 'tiktok'])
            ->orderByDesc('grand_total')
            ->limit(20)
            ->get(['code', 'channel_order_no', 'channel', 'net_payout_actual', 'grand_total', 'date', 'marketplace_status']);

        $piutangMpDetail = $piutangMpRows->map(fn($r) => [
            'label' => $r->channel_order_no ?: $r->code ?: '—',
            'sub'   => trim(($r->channel ?: '-') . ' · ' . ($r->marketplace_status ?: $r->date ?: '-')),
            'value' => (float) ($r->net_payout_actual ?: $r->grand_total),
        ])->values();

        // Jika invoice kosong, ambil dari marketplace_orders
        if ($piutangMpDetail->isEmpty() && $marketplaceOrderTotal > 0) {
            $mpOrderRows = DB::table('marketplace_orders')
                ->whereNull('cancelled_at')
                ->where(function ($q) {
                    $q->whereNull('status')
                        ->orWhereNotIn('status', ['cancelled', 'canceled', 'void', 'voided']);
                })
                ->where(function ($q) {
                    $q->whereNull('payment_status')
                        ->orWhereNotIn('payment_status', ['paid', 'settled']);
                })
                ->orderByDesc('total_amount')
                ->limit(20)
                ->get(['channel_order_id', 'external_order_id', 'buyer_name', 'buyer_username', 'status', 'order_status', 'net_payout_estimated', 'total_paid_customer', 'total_amount', 'order_date']);

            $piutangMpDetail = $mpOrderRows->map(fn($r) => [
                'label' => trim(($r->buyer_name ?: $r->buyer_username ?: 'Pembeli') . ' · ' . ($r->channel_order_id ?: $r->external_order_id ?: '—')),
                'sub'   => trim(($r->order_date ?: '-') . ' · ' . ($r->status ?: $r->order_status ?: '-')),
                'value' => (float) ($r->net_payout_estimated ?: $r->total_paid_customer ?: $r->total_amount),
            ])->values();
        }

        // Hutang Upah — payroll periods belum dibayar, per operator.
        $hutangPeriodDetail = DB::table('piecework_payroll_periods as p')
            ->join('piecework_payroll_lines as pl', 'pl.payroll_period_id', '=', 'p.id')
            ->leftJoin('employees as e', 'e.id', '=', 'pl.employee_id')
            ->leftJoin('items as i', 'i.id', '=', 'pl.item_id')
            ->whereNull('p.paid_at')
            ->whereIn('p.status', ['final', 'posted'])
            ->whereIn('p.module', ['cutting', 'sewing', 'finishing', 'packing'])
            ->groupBy('p.id', 'p.module', 'p.period_start', 'p.period_end', 'e.name', 'i.code', 'i.name', 'pl.rate_per_pcs')
            ->selectRaw("
                p.module,
                p.period_start,
                p.period_end,
                COALESCE(e.name, 'Tanpa Operator') as employee_name,
                COALESCE(i.code, '-') as item_code,
                COALESCE(i.name, '-') as item_name,
                SUM(pl.total_qty_ok) as total_qty,
                pl.rate_per_pcs,
                SUM(pl.amount) as total_amount
            ")
            ->orderByDesc('total_amount')
            ->limit(30)
            ->get()
            ->map(fn($r) => [
                'label' => $r->employee_name,
                'sub'   => trim(
                    ucfirst($r->module) . ' · ' .
                    ($r->item_code ?? '-') . ' · ' .
                    number_format((float) $r->total_qty, 0, ',', '.') . ' pcs × Rp' .
                    number_format((float) $r->rate_per_pcs, 0, ',', '.') .
                    ' · ' . ($r->period_start ?? '-') . ' - ' . ($r->period_end ?? '-')
                ),
                'value' => (float) $r->total_amount,
            ])->values();

        $cuttingJournalRows = DB::table('journals as j')
            ->join('journal_lines as jl', 'jl.journal_id', '=', 'j.id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->join('cutting_jobs as cj', 'cj.id', '=', 'j.source_id')
            ->whereNull('j.voided_at')
            ->where('a.code', '2102')
            ->whereIn('j.source_type', ['cutting_job_wage', 'cutting_wip'])
            ->selectRaw("
                j.id as journal_id,
                j.date,
                j.source_type,
                cj.id as job_id,
                cj.code as job_code,
                SUM(jl.credit - jl.debit) as total_amount
            ")
            ->groupBy('j.id', 'j.date', 'j.source_type', 'cj.id', 'cj.code')
            ->havingRaw('SUM(jl.credit - jl.debit) > 0')
            ->get();

        $cuttingBundleRows = DB::table('cutting_job_bundles as b')
            ->join('cutting_jobs as cj', 'cj.id', '=', 'b.cutting_job_id')
            ->leftJoin('employees as e', 'e.id', '=', DB::raw('COALESCE(b.operator_id, cj.operator_id)'))
            ->leftJoin('items as i', 'i.id', '=', 'b.finished_item_id')
            ->whereIn('b.cutting_job_id', $cuttingJournalRows->pluck('job_id')->unique()->values()->all() ?: [0])
            ->groupBy('b.cutting_job_id', 'e.name', 'i.code', 'i.name')
            ->selectRaw("
                b.cutting_job_id,
                COALESCE(e.name, 'Tanpa Operator') as employee_name,
                COALESCE(i.code, '-') as item_code,
                COALESCE(i.name, '-') as item_name,
                SUM(COALESCE(NULLIF(b.qty_qc_ok, 0), b.qty_pcs, 0)) as total_qty
            ")
            ->get()
            ->groupBy('cutting_job_id');

        $cuttingWageDetail = $cuttingJournalRows->flatMap(function ($journal) use ($cuttingBundleRows) {
            $bundles = collect($cuttingBundleRows->get($journal->job_id, []))
                ->filter(fn($row) => (float) $row->total_qty > 0)
                ->values();
            $totalQty = (float) $bundles->sum('total_qty');

            if ($totalQty <= 0) {
                return [[
                    'label' => 'Tanpa Operator',
                    'sub' => trim('Cutting · ' . ($journal->job_code ?? '-') . ' · Qty belum tersedia · ' . ($journal->date ?? '-')),
                    'value' => (float) $journal->total_amount,
                ]];
            }

            return $bundles->map(function ($row) use ($journal, $totalQty) {
                $qty = (float) $row->total_qty;
                $amount = round(((float) $journal->total_amount) * ($qty / $totalQty), 0);
                $rate = $qty > 0 ? $amount / $qty : 0;

                return [
                    'label' => $row->employee_name,
                    'sub' => trim(
                        'Cutting · ' . ($journal->job_code ?? '-') . ' · ' .
                        ($row->item_code ?? '-') . ' · ' .
                        number_format($qty, 0, ',', '.') . ' pcs × Rp' .
                        number_format($rate, 0, ',', '.') . ' · ' .
                        ($journal->date ?? '-')
                    ),
                    'value' => $amount,
                ];
            });
        });

        $sewingWageDetail = DB::table('journals as j')
            ->join('journal_lines as jl', 'jl.journal_id', '=', 'j.id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->join('sewing_pickup_lines as spl', 'spl.id', '=', 'j.source_id')
            ->join('sewing_pickups as sp', 'sp.id', '=', 'spl.sewing_pickup_id')
            ->leftJoin('employees as e', 'e.id', '=', 'sp.operator_id')
            ->leftJoin('items as i', 'i.id', '=', 'spl.finished_item_id')
            ->whereNull('j.voided_at')
            ->where('a.code', '2102')
            ->where('j.source_type', 'sewing_pickup_wage')
            ->where('spl.status', '!=', 'void')
            ->where('sp.status', '!=', 'void')
            ->selectRaw("
                j.date,
                sp.code as pickup_code,
                COALESCE(e.name, 'Tanpa Operator') as employee_name,
                COALESCE(i.code, '-') as item_code,
                spl.qty_bundle,
                spl.wage_per_pcs,
                SUM(jl.credit - jl.debit) as total_amount
            ")
            ->groupBy('j.id', 'j.date', 'sp.code', 'e.name', 'i.code', 'spl.qty_bundle', 'spl.wage_per_pcs')
            ->havingRaw('SUM(jl.credit - jl.debit) > 0')
            ->get()
            ->map(fn($r) => [
                'label' => $r->employee_name,
                'sub' => trim('Jahit · ' . ($r->pickup_code ?? '-') . ' · ' . ($r->item_code ?? '-') . ' · ' . number_format((float) $r->qty_bundle, 0, ',', '.') . ' pcs × Rp' . number_format((float) $r->wage_per_pcs, 0, ',', '.')),
                'value' => (float) $r->total_amount,
            ]);

        $unpostedSewingWageDetail = DB::table('sewing_pickup_lines as spl')
            ->join('sewing_pickups as sp', 'sp.id', '=', 'spl.sewing_pickup_id')
            ->leftJoin('employees as e', 'e.id', '=', 'sp.operator_id')
            ->leftJoin('items as i', 'i.id', '=', 'spl.finished_item_id')
            ->where('spl.status', '!=', 'void')
            ->where('sp.status', '!=', 'void')
            ->where('spl.wage_per_pcs', '>', 0)
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('journals as j')
                    ->whereColumn('j.source_id', 'spl.id')
                    ->where('j.source_type', 'sewing_pickup_wage')
                    ->whereNull('j.voided_at');
            })
            ->selectRaw("
                sp.code as pickup_code,
                sp.date,
                COALESCE(e.name, 'Tanpa Operator') as employee_name,
                COALESCE(i.code, '-') as item_code,
                spl.qty_bundle,
                spl.wage_per_pcs,
                spl.qty_bundle * spl.wage_per_pcs as total_amount
            ")
            ->orderByDesc('total_amount')
            ->limit(30)
            ->get()
            ->map(fn($r) => [
                'label' => $r->employee_name,
                'sub' => trim('Jahit · ' . ($r->pickup_code ?? '-') . ' · ' . ($r->item_code ?? '-') . ' · ' . number_format((float) $r->qty_bundle, 0, ',', '.') . ' pcs × Rp' . number_format((float) $r->wage_per_pcs, 0, ',', '.') . ' · belum terjurnal'),
                'value' => (float) $r->total_amount,
            ]);

        $hutangUpahDetail = $hutangPeriodDetail
            ->concat($cuttingWageDetail)
            ->concat($sewingWageDetail)
            ->concat($unpostedSewingWageDetail)
            ->sortByDesc('value')
            ->take(30)
            ->values();

        $hutangUpahNote = $hutangUpahDetail->count() >= 30
            ? 'Menampilkan 30 terbesar dari payroll dan jurnal produksi.'
            : null;

        $details = [
            '1202' => ['title' => 'Persediaan WIP per Gudang',         'rows' => $wipDetail,           'note' => null],
            '1203' => ['title' => 'Persediaan Barang Jadi per Gudang',  'rows' => $fgDetail,            'note' => null],
            '1301' => ['title' => 'Invoice Belum Lunas (Non-MP)',        'rows' => $piutangDagangDetail, 'note' => $piutangDagangDetail->count() >= 20 ? 'Menampilkan 20 terbesar.' : null],
            '1302' => ['title' => 'Saldo Marketplace Belum Ditarik',   'rows' => $piutangMpDetail,     'note' => $piutangMpDetail->count() >= 20 ? 'Menampilkan 20 terbesar.' : null],
            '2102' => ['title' => 'Hutang Upah Borongan Belum Dibayar', 'rows' => $hutangUpahDetail,    'note' => $hutangUpahNote],
        ];

        // Prefill HANYA untuk WIP/FG/Reject — utama dari HPP master item,
        // fallback ke snapshot cost terakhir jika HPP belum diisi.
        // 1201 (RM) TIDAK di-prefill karena sudah otomatis terjurnal via Stock Opname
        // (inventory_adjustment journal). Jika di-input ulang di sini akan double-count.
        $prefill = [];
        foreach ([
            ['1202', $wipTotal],
            ['1203', $fgTotal],
            ['1204', $rejectTotal],
            ['1301', $customerReceivableTotal],
            ['1302', $marketplaceReceivableTotal],
            ['2102', $payrollPayableTotal],
        ] as [$code, $val]) {
            $account = $accounts->firstWhere('code', $code);
            if ($account && $val > 0) {
                $prefill[$account->id] = $val;
            }
        }

        return view('accounting.opening_balances_batch.create', compact('accounts', 'prefill', 'details'));
    }

    public function detail(string $code)
    {
        $code = trim($code);
        abort_unless(in_array($code, ['1202', '1203', '1301', '1302', '2102'], true), 404);

        $createView = $this->create();
        $data = $createView->getData();
        $details = $data['details'] ?? [];
        $prefill = $data['prefill'] ?? [];
        $accounts = $data['accounts'] ?? collect();

        $account = collect($accounts)->firstWhere('code', $code);
        abort_unless($account, 404);

        $detail = $details[$code] ?? ['title' => $account->name, 'rows' => [], 'note' => null];
        $total = collect($detail['rows'] ?? [])->sum(fn($row) => (float) ($row['value'] ?? 0));
        $prefillValue = (float) ($prefill[$account->id] ?? $total);

        return view('accounting.opening_balances_batch.detail', compact('account', 'detail', 'total', 'prefillValue'));
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
