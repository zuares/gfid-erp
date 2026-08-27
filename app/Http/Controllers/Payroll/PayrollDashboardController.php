<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\Payroll\PieceRateService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Dashboard Payroll Borongan (jahit + cutting).
 *
 * Diduplikasi dari ProductionDashboardController (tab Penjahit & Cutting) agar
 * dapat berdiri sendiri di domain payroll, lalu ditambah tab "Keseluruhan"
 * (gabungan semua transaksi jahit + cutting dalam satu tabel).
 *
 * Tab:
 *  - Keseluruhan : gabungan transaksi jahit + cutting (filter peran/operator/SKU).
 *  - Penjahit    : aktivitas jahit per transaksi (ambil & setor) + upah borongan
 *                  berbasis Ambil Jahit.
 *  - Cutting     : aktivitas cutting per transaksi (hasil potong) + upah borongan.
 */
class PayrollDashboardController extends Controller
{
    /** Daftar tab valid. */
    private const TABS = ['keseluruhan', 'penjahit', 'cutting'];

    public function __construct(
        private PieceRateService $pieceRate,
    ) {}

    /**
     * Shell dashboard: render kerangka + HANYA tab awal (lazy).
     * Tab lain di-fetch via API saat dibuka (lihat data()).
     */
    public function index(Request $request): View
    {
        $filters = $this->resolveFilters($request);

        $initialTab = $request->input('tab');
        if (! in_array($initialTab, self::TABS, true)) {
            $initialTab = 'keseluruhan';
        }

        $today = Carbon::today();
        $defaults = [
            'date_from' => $today->copy()->subDays(6)->toDateString(),
            'date_to' => $today->toDateString(),
        ];

        return view('payroll.dashboard.index', array_merge(
            [
                'filters' => $filters,
                'defaults' => $defaults,
                'initialTab' => $initialTab,
                'initialPartial' => $this->partialFor($initialTab),
                'periodLabel' => $this->periodLabel($filters),
            ],
            $this->tabData($initialTab, $filters),
        ));
    }

    /**
     * API: kembalikan HTML satu tab + meta (untuk AJAX lazy-load & filter).
     */
    public function data(Request $request): \Illuminate\Http\JsonResponse
    {
        $tab = $request->input('tab');
        if (! in_array($tab, self::TABS, true)) {
            return response()->json(['message' => 'Tab tidak dikenal.'], 422);
        }

        $filters = $this->resolveFilters($request);
        $html = view($this->partialFor($tab), $this->tabData($tab, $filters))->render();

        return response()->json([
            'tab' => $tab,
            'html' => $html,
            'meta' => [
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
                'operator_id' => $filters['operator_id'],
                'item_id' => $filters['item_id'],
                'category_id' => $filters['category_id'],
                'period_label' => $this->periodLabel($filters),
            ],
        ]);
    }

    /**
     * Slip upah borongan per operator (siap cetak). Reuse view slip produksi.
     * Sumber upah = nilai REAL yang dibayar:
     *   - sewing  → ambil jahit (qty_ambil × rate)
     *   - cutting → hasil potong lolos QC (qty_qc_ok × rate)
     */
    public function slip(Request $request): View
    {
        $module = $request->query('module') === 'cutting' ? 'cutting' : 'sewing';
        $basis = $module === 'sewing' && $request->query('basis') !== 'setor' ? 'ambil' : 'setor';
        $code = trim((string) $request->query('operator', ''));

        $employee = $code !== '' ? Employee::where('code', $code)->first() : null;
        abort_if(! $employee, 404, 'Operator tidak ditemukan.');

        $f = $this->resolveFilters($request);
        $f['operator_id'] = $employee->id;

        $activity = $module === 'cutting'
            ? $this->buildCuttingActivity($f)
            : $this->buildPenjahitActivity($f);

        $rows = $module === 'cutting'
            ? $activity
            : $activity->where('type', $basis === 'ambil' ? 'Ambil' : 'Setor')->values();

        $ambil = collect();

        $groupBy = function ($items, callable $qtyOf, callable $amountOf) {
            return $items->groupBy('category')
                ->map(function ($g, $cat) use ($qtyOf, $amountOf) {
                    $lines = $g->groupBy('sku')
                        ->map(function ($s) use ($qtyOf, $amountOf) {
                            $qty = (float) $s->sum($qtyOf);
                            $amount = (float) $s->sum($amountOf);
                            $first = $s->first();
                            $pickupDates = $s->pluck('pickup_date')->filter()->unique()->sort()->values();
                            $pickupFrom = $pickupDates->first();
                            $pickupTo = $pickupDates->last();

                            return (object) [
                                'sku' => $first->sku,
                                'product_name' => $first->product_name,
                                'pickup_from' => $pickupFrom,
                                'pickup_to' => $pickupTo,
                                'qty' => $qty,
                                'rate' => $qty > 0 ? $amount / $qty : (float) $first->rate,
                                'amount' => $amount,
                            ];
                        })
                        ->sortBy('sku')
                        ->values();

                    return (object) [
                        'category' => $cat,
                        'lines' => $lines,
                        'qty' => (float) $g->sum($qtyOf),
                        'amount' => (float) $g->sum($amountOf),
                    ];
                })
                ->sortBy('category')
                ->values();
        };

        $groups = $basis === 'ambil'
            ? $groupBy($rows, fn ($r) => $r->qty, fn ($r) => $r->rate * $r->qty)
            : $groupBy($rows, fn ($r) => $r->qty_ok, fn ($r) => $r->amount);
        $estGroups = collect();
        $estQty = 0.0;
        $estAmount = 0.0;

        $recap = $groups
            ->groupBy('category')
            ->map(fn ($g, $cat) => (object) [
                'category' => $cat,
                'qty' => (float) $g->sum('qty'),
                'amount' => (float) $g->sum('amount'),
            ])
            ->sortByDesc('amount')
            ->values();

        $role = $module === 'cutting' ? 'Pemotong' : 'Penjahit';

        $pickupDates = ($basis === 'ambil' ? $rows : $activity)->pluck('pickup_date')->filter()->unique()->sort()->values();
        $pickupFrom = $pickupDates->first();
        $pickupTo = $pickupDates->last();

        $clean = fn (string $s) => preg_replace('/[^A-Za-z0-9]+/', '', $s);
        $range = Carbon::parse($f['date_from'])->format('Ymd').'-'.Carbon::parse($f['date_to'])->format('Ymd');
        $basisLabel = $basis === 'ambil' ? 'Ambil' : 'Setor';
        $fileName = trim($clean($employee->name).'_'.$role.'_'.$basisLabel.'_'.$range, '_');

        return view('production.dashboard.slip', [
            'module' => $module,
            'moduleLabel' => $module === 'cutting' ? 'Cutting (Potong)' : 'Jahit ('.$basisLabel.')',
            'slipBasis' => $basis,
            'role' => $role,
            'employee' => $employee,
            'period' => $this->periodLabel($f),
            'dateFrom' => $f['date_from'],
            'dateTo' => $f['date_to'],
            'pickupFrom' => $pickupFrom,
            'pickupTo' => $pickupTo,
            'groups' => $groups,
            'grandQty' => (float) ($basis === 'ambil' ? $rows->sum('qty') : $rows->sum('qty_ok')),
            'grandAmount' => (float) ($basis === 'ambil' ? $rows->sum(fn ($r) => $r->rate * $r->qty) : $rows->sum('amount')),
            'estGroups' => $estGroups,
            'estQty' => $estQty,
            'estAmount' => $estAmount,
            'recap' => $recap,
            'printedAt' => Carbon::now(),
            'fileName' => $fileName,
        ]);
    }

    /** Parse & normalisasi filter (default: 7 hari terakhir). */
    private function resolveFilters(Request $request): array
    {
        $today = Carbon::today();
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if (! $dateFrom && ! $dateTo) {
            $dateTo = $today->toDateString();
            $dateFrom = $today->copy()->subDays(6)->toDateString();
        } elseif ($dateFrom && ! $dateTo) {
            $dateTo = $dateFrom;
        } elseif (! $dateFrom && $dateTo) {
            $dateFrom = $dateTo;
        }
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'operator_id' => null,
            'item_id' => null,
            'category_id' => null,
        ];
    }

    /** Label periode untuk header. */
    private function periodLabel(array $f): string
    {
        return Carbon::parse($f['date_from'])->format('d M Y')
            .' – '.Carbon::parse($f['date_to'])->format('d M Y');
    }

    /** Nama blade partial untuk sebuah tab. */
    private function partialFor(string $tab): string
    {
        return 'payroll.dashboard.partials._'.$tab;
    }

    /** Hitung HANYA data yang diperlukan tab terkait. */
    private function tabData(string $tab, array $f): array
    {
        return match ($tab) {
            'penjahit' => ['rows' => $this->buildPenjahitActivity($f)],
            'cutting' => ['rows' => $this->buildCuttingActivity($f)],
            default => ['rows' => $this->buildKeseluruhan($f)],
        };
    }

    /**
     * Gabungan semua transaksi jahit + cutting dalam satu koleksi.
     * Tiap baris ditandai module/role/kind agar bisa difilter & dibedakan di tabel.
     */
    private function buildKeseluruhan(array $f): Collection
    {
        $sewing = $this->buildPenjahitActivity($f)->map(function ($r) {
            $r->module = 'sewing';
            $r->role = 'Jahit';
            $r->kind = $r->type; // Ambil / Setor

            return $r;
        });

        $cutting = $this->buildCuttingActivity($f)->map(function ($r) {
            $r->module = 'cutting';
            $r->role = 'Cutting';
            $r->kind = 'Potong';
            $r->type = 'Potong';
            $r->qty_outstanding = 0.0;

            return $r;
        });

        return $sewing->concat($cutting)
            ->sortBy([['date', 'desc'], ['created_at', 'desc']])
            ->values();
    }

    /**
     * Daftar aktivitas penjahit per transaksi (ambil + setor) + upah borongan.
     * Upah RIIL hanya dari setoran yang lolos QC (qty_ok × rate).
     */
    private function buildPenjahitActivity(array $f): Collection
    {
        $from = $f['date_from'];
        $to = $f['date_to'];

        // ---- Ambil jahit (pickup lines) ----
        $pick = DB::table('sewing_pickup_lines as pl')
            ->join('sewing_pickups as p', 'p.id', '=', 'pl.sewing_pickup_id')
            ->join('employees as e', 'e.id', '=', 'p.operator_id')
            ->join('items as it', 'it.id', '=', 'pl.finished_item_id')
            ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
            ->whereNull('p.voided_at')
            ->where('p.status', '!=', 'void')
            ->whereNull('pl.voided_at')
            ->where('pl.status', '!=', 'void')
            ->whereRaw('DATE(p.date) BETWEEN ? AND ?', [$from, $to]);
        if ($f['operator_id']) {
            $pick->where('p.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $pick->where('pl.finished_item_id', $f['item_id']);
        }
        if ($f['category_id']) {
            $pick->where('it.item_category_id', $f['category_id']);
        }
        $pick = $pick->selectRaw("
                'Ambil' as type, p.code as code, DATE(p.date) as date, p.created_at as created_at,
                DATE(p.date) as pickup_date,
                e.id as operator_emp_id, e.code as operator_code, e.name as operator_name,
                it.id as item_id, it.code as sku, it.name as product_name, COALESCE(cat.name,'-') as category,
                COALESCE(pl.qty_bundle,0) as qty, 0 as qty_ok, 0 as qty_reject,
                COALESCE(pl.qty_bundle,0) - COALESCE(pl.qty_returned_ok,0) - COALESCE(pl.qty_returned_reject,0) as qty_outstanding,
                COALESCE(pl.wage_per_pcs,0) as snapshot_rate
            ")
            ->get();

        // ---- Setor jahit (return lines) ----
        $ret = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->join('sewing_pickups as pp', 'pp.id', '=', 'pl.sewing_pickup_id')
            ->join('items as it', 'it.id', '=', 'pl.finished_item_id')
            ->join('employees as e', 'e.id', '=', 'r.operator_id')
            ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
            ->whereNull('r.voided_at')
            ->where('r.status', '!=', 'void')
            ->whereNull('pp.voided_at')
            ->where('pp.status', '!=', 'void')
            ->whereNull('pl.voided_at')
            ->where('pl.status', '!=', 'void')
            ->whereRaw('DATE(r.date) BETWEEN ? AND ?', [$from, $to]);
        if ($f['operator_id']) {
            $ret->where('r.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $ret->where('pl.finished_item_id', $f['item_id']);
        }
        if ($f['category_id']) {
            $ret->where('it.item_category_id', $f['category_id']);
        }
        $ret = $ret->selectRaw("
                'Setor' as type, r.code as code, DATE(r.date) as date, r.created_at as created_at,
                DATE(pp.date) as pickup_date,
                e.id as operator_emp_id, e.code as operator_code, e.name as operator_name,
                it.id as item_id, it.code as sku, it.name as product_name, COALESCE(cat.name,'-') as category,
                COALESCE(rl.qty_ok,0) as qty, COALESCE(rl.qty_ok,0) as qty_ok, COALESCE(rl.qty_reject,0) as qty_reject,
                0 as qty_outstanding, COALESCE(pl.wage_per_pcs,0) as snapshot_rate
            ")
            ->get();

        $rateMemo = [];

        return $pick->concat($ret)
            ->map(function ($r) use (&$rateMemo) {
                $qtyOk = (float) $r->qty_ok;
                $amount = 0.0;
                $key = $r->operator_emp_id.'|'.$r->item_id.'|'.$r->date;
                $snapshotRate = (float) ($r->snapshot_rate ?? 0);
                $rate = $snapshotRate > 0
                    ? $snapshotRate
                    : ($rateMemo[$key] ??= (float) $this->pieceRate->getRatePerPcs(
                        'sewing',
                        (int) $r->operator_emp_id,
                        (int) $r->item_id,
                        $r->date,
                    ));
                if ($r->type === 'Ambil' && (float) $r->qty > 0) {
                    $amount = $rate * (float) $r->qty;
                }

                return (object) [
                    'type' => $r->type,
                    'code' => $r->code,
                    'date' => $r->date,
                    'pickup_date' => $r->pickup_date,
                    'created_at' => $r->created_at,
                    'operator_code' => $r->operator_code,
                    'operator_name' => $r->operator_name,
                    'sku' => $r->sku,
                    'product_name' => $r->product_name,
                    'category' => $r->category,
                    'qty' => (float) $r->qty,
                    'qty_ok' => $qtyOk,
                    'qty_reject' => (float) $r->qty_reject,
                    'qty_outstanding' => (float) ($r->qty_outstanding ?? 0),
                    'rate' => $rate,
                    'amount' => $amount,
                ];
            })
            ->sortBy([['date', 'desc'], ['created_at', 'desc']])
            ->values();
    }

    /**
     * Daftar kejadian cutting per baris (per cutting_job_bundle, status job qc_done).
     * Upah borongan cutting = piece rate (module=cutting) × qty_qc_ok.
     */
    private function buildCuttingActivity(array $f): Collection
    {
        $from = $f['date_from'];
        $to = $f['date_to'];

        $q = DB::table('cutting_job_bundles as cb')
            ->join('cutting_jobs as cj', 'cj.id', '=', 'cb.cutting_job_id')
            ->join('items as it', 'it.id', '=', 'cb.finished_item_id')
            ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
            ->leftJoin('employees as e', 'e.id', '=', DB::raw('COALESCE(cb.operator_id, cj.operator_id)'))
            ->where('cj.status', 'qc_done')
            ->whereRaw('DATE(cj.date) BETWEEN ? AND ?', [$from, $to]);

        if ($f['operator_id']) {
            $q->whereRaw('COALESCE(cb.operator_id, cj.operator_id) = ?', [$f['operator_id']]);
        }
        if ($f['item_id']) {
            $q->where('cb.finished_item_id', $f['item_id']);
        }
        if ($f['category_id']) {
            $q->where('it.item_category_id', $f['category_id']);
        }

        $rows = $q->selectRaw("
                cj.code as code, DATE(cj.date) as date, cj.created_at as created_at,
                COALESCE(cb.operator_id, cj.operator_id) as operator_emp_id,
                COALESCE(e.code, '-') as operator_code, COALESCE(e.name, 'Tanpa Operator') as operator_name,
                it.id as item_id, it.code as sku, it.name as product_name, COALESCE(cat.name,'-') as category,
                COALESCE(cb.qty_pcs,0) as qty, COALESCE(cb.qty_qc_ok,0) as qty_ok, COALESCE(cb.qty_qc_reject,0) as qty_reject
            ")
            ->get();

        $rateMemo = [];

        return $rows
            ->map(function ($r) use (&$rateMemo) {
                $qtyOk = (float) $r->qty_ok;
                $rate = 0.0;
                $amount = 0.0;
                if ($r->operator_emp_id) {
                    $key = $r->operator_emp_id.'|'.$r->item_id.'|'.$r->date;
                    $rate = $rateMemo[$key] ??= (float) $this->pieceRate->getRatePerPcs(
                        'cutting',
                        (int) $r->operator_emp_id,
                        (int) $r->item_id,
                        $r->date,
                    );
                    if ($qtyOk > 0) {
                        $amount = $rate * $qtyOk;
                    }
                }

                return (object) [
                    'code' => $r->code,
                    'date' => $r->date,
                    'pickup_date' => null,
                    'created_at' => $r->created_at,
                    'operator_code' => $r->operator_code,
                    'operator_name' => $r->operator_name,
                    'sku' => $r->sku,
                    'product_name' => $r->product_name,
                    'category' => $r->category,
                    'qty' => (float) $r->qty,
                    'qty_ok' => $qtyOk,
                    'qty_reject' => (float) $r->qty_reject,
                    'qty_outstanding' => 0.0,
                    'rate' => $rate,
                    'amount' => $amount,
                ];
            })
            ->sortBy([['date', 'desc'], ['created_at', 'desc']])
            ->values();
    }
}
