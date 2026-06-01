<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\SewingPickupLine;
use App\Models\Warehouse;
use App\Services\Production\ProductionFlowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Dashboard Produksi (konsolidasi semua laporan produksi).
 *
 * Menggabungkan tanpa redundansi:
 *  - Ringkasan harian (daily_production + sewing dashboard)
 *  - Alur WIP (production_flow_dashboard)
 *  - Outstanding & Aging sewing (outstanding + aging_wip_sew + partial_pickup + wip_sewing_age)
 *  - Performa operator (operators + productivity + lead_time + operator_behavior)
 *  - Analisa reject (reject_detail + reject_analysis)
 *  - Output per item (sewing_per_item + finishing_jobs)
 */
class ProductionDashboardController extends Controller
{
    /** Daftar tab valid + builder-nya. */
    private const TABS = ['ringkasan', 'wip', 'bottleneck', 'outstanding', 'operator', 'reject', 'item'];

    public function __construct(private ProductionFlowService $flow)
    {
    }

    /** Render strip overview (KPI + ringkasan SKU) untuk filter saat ini. */
    private function overviewData(array $f): array
    {
        return [
            'kpi' => $this->flow->dashboardKpis($f),
            'skuSummary' => $this->flow->skuSummary($f),
        ];
    }

    /**
     * Shell dashboard: render kerangka + HANYA tab awal (lazy).
     * Tab lain di-fetch via API saat dibuka (lihat data()).
     */
    public function index(Request $request): View
    {
        $filters = $this->resolveFilters($request);

        $initialTab = $request->input('tab');
        if (!in_array($initialTab, self::TABS, true)) {
            $initialTab = 'ringkasan';
        }

        // Default 7 hari (untuk tombol reset di front-end)
        $today = Carbon::today();
        $defaults = [
            'date_from' => $today->copy()->subDays(6)->toDateString(),
            'date_to' => $today->toDateString(),
        ];

        return view('production.dashboard.index', array_merge(
            [
                'filters' => $filters,
                'defaults' => $defaults,
                'initialTab' => $initialTab,
                'initialPartial' => $this->partialFor($initialTab),
                'periodLabel' => $this->periodLabel($filters),
                'operatorOptions' => Employee::where('role', 'sewing')->orderBy('code')->get(),
                'itemOptions' => Item::where('type', 'finished_good')->orderBy('code')->get(),
                'categoryOptions' => ItemCategory::where('active', 1)->orderBy('name')->get(),
            ],
            $this->overviewData($filters),
            $this->tabData($initialTab, $filters),
        ));
    }

    /**
     * API: kembalikan HTML satu tab + meta (untuk AJAX lazy-load & filter).
     */
    public function data(Request $request): \Illuminate\Http\JsonResponse
    {
        $tab = $request->input('tab');
        if (!in_array($tab, self::TABS, true)) {
            return response()->json(['message' => 'Tab tidak dikenal.'], 422);
        }

        $filters = $this->resolveFilters($request);
        $html = view($this->partialFor($tab), $this->tabData($tab, $filters))->render();
        $overviewHtml = view('production.dashboard.partials._overview', $this->overviewData($filters))->render();

        return response()->json([
            'tab' => $tab,
            'html' => $html,
            'overview_html' => $overviewHtml,
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

    /** Parse & normalisasi filter (default: 7 hari terakhir). */
    private function resolveFilters(Request $request): array
    {
        $today = Carbon::today();
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if (!$dateFrom && !$dateTo) {
            $dateTo = $today->toDateString();
            $dateFrom = $today->copy()->subDays(6)->toDateString();
        } elseif ($dateFrom && !$dateTo) {
            $dateTo = $dateFrom;
        } elseif (!$dateFrom && $dateTo) {
            $dateFrom = $dateTo;
        }
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'operator_id' => $request->input('operator_id') ?: null,
            'item_id' => $request->input('item_id') ?: null,
            'category_id' => $request->input('category_id') ?: null,
        ];
    }

    /** Label periode untuk header. */
    private function periodLabel(array $f): string
    {
        return Carbon::parse($f['date_from'])->format('d M Y')
            . ' – ' . Carbon::parse($f['date_to'])->format('d M Y');
    }

    /** Nama blade partial untuk sebuah tab. */
    private function partialFor(string $tab): string
    {
        return 'production.dashboard.partials._' . $tab;
    }

    /** Hitung HANYA data yang diperlukan tab terkait. */
    private function tabData(string $tab, array $f): array
    {
        return match ($tab) {
            'wip' => ['wipFlow' => $this->buildWipFlow()],
            'bottleneck' => ['bottleneck' => $this->buildBottleneck($f)],
            'outstanding' => ['outstanding' => $this->buildOutstanding($f)],
            'operator' => ['operators' => $this->buildOperatorPerformance($f)],
            'reject' => ['reject' => $this->buildReject($f)],
            'item' => ['perItem' => $this->buildPerItem($f)],
            default => [
                'summary' => $this->buildSummary($f),
                'dailyTrend' => $this->buildDailyTrend($f),
            ],
        };
    }

    /**
     * Helper: kecualikan dokumen void (auto-detect kolom, SQLite-safe).
     */
    private function applyNotVoid($q, string $table, ?string $alias = null): void
    {
        $prefix = $alias ?: $table;

        // Canonical void marker in this app = timestamp column (voided_at).
        foreach (['voided_at', 'canceled_at', 'cancelled_at'] as $col) {
            if (Schema::hasColumn($table, $col)) {
                $q->whereNull("$prefix.$col");
                return;
            }
        }
        foreach (['is_void', 'is_voided', 'void', 'voided', 'is_canceled', 'is_cancelled'] as $col) {
            if (Schema::hasColumn($table, $col)) {
                $q->where("$prefix.$col", 0);
                return;
            }
        }
        foreach (['status', 'state'] as $col) {
            if (Schema::hasColumn($table, $col)) {
                $q->whereNotIn("$prefix.$col", ['void', 'VOID', 'canceled', 'CANCELED', 'cancelled', 'CANCELLED']);
                return;
            }
        }
    }

    // ==========================================================
    // 1) RINGKASAN (KPI)
    // ==========================================================
    private function buildSummary(array $f): array
    {
        $from = $f['date_from'];
        $to = $f['date_to'];

        // Cutting OK / Reject (QC cutting)
        $cutQ = DB::table('qc_results as qc')
            ->where('qc.stage', 'cutting')
            ->whereBetween('qc.qc_date', [$from, $to]);
        if ($f['item_id']) {
            $cutQ->join('cutting_job_bundles as b', 'b.id', '=', 'qc.cutting_job_bundle_id')
                ->where('b.finished_item_id', $f['item_id']);
        }
        $cutting = $cutQ->selectRaw('COALESCE(SUM(qc.qty_ok),0) ok, COALESCE(SUM(qc.qty_reject),0) rj')->first();

        // Pickup total (qty_bundle)
        $pickQ = DB::table('sewing_pickup_lines as pl')
            ->join('sewing_pickups as p', 'p.id', '=', 'pl.sewing_pickup_id')
            ->whereBetween('p.date', [$from, $to]);
        $this->applyNotVoid($pickQ, 'sewing_pickups', 'p');
        if ($f['operator_id']) {
            $pickQ->where('p.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $pickQ->where('pl.finished_item_id', $f['item_id']);
        }
        $pickupTotal = (float) $pickQ->sum('pl.qty_bundle');

        // Sewing OK / Reject (return lines)
        $sewQ = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->whereBetween('r.date', [$from, $to]);
        $this->applyNotVoid($sewQ, 'sewing_returns', 'r');
        if ($f['operator_id']) {
            $sewQ->where('r.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $sewQ->join('sewing_pickup_lines as pl2', 'pl2.id', '=', 'rl.sewing_pickup_line_id')
                ->where('pl2.finished_item_id', $f['item_id']);
        }
        $sewing = $sewQ->selectRaw('COALESCE(SUM(rl.qty_ok),0) ok, COALESCE(SUM(rl.qty_reject),0) rj')->first();

        // Finishing OK / Reject (posted)
        $finQ = DB::table('finishing_job_lines as fl')
            ->join('finishing_jobs as fj', 'fj.id', '=', 'fl.finishing_job_id')
            ->where('fj.status', 'posted')
            ->whereBetween('fj.date', [$from, $to]);
        if ($f['operator_id']) {
            $finQ->where('fl.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $finQ->where('fl.item_id', $f['item_id']);
        }
        $fin = $finQ->selectRaw('COALESCE(SUM(fl.qty_in),0) in_qty, COALESCE(SUM(fl.qty_ok),0) ok, COALESCE(SUM(fl.qty_reject),0) rj')->first();

        $cutOk = (float) $cutting->ok;
        $cutRj = (float) $cutting->rj;
        $sewOk = (float) $sewing->ok;
        $sewRj = (float) $sewing->rj;
        $finOk = (float) $fin->ok;
        $finRj = (float) $fin->rj;
        $totalRj = $cutRj + $sewRj + $finRj;
        $totalOk = $cutOk + $sewOk + $finOk;

        return [
            'cutting_ok' => $cutOk,
            'cutting_reject' => $cutRj,
            'pickup_total' => $pickupTotal,
            'sewing_ok' => $sewOk,
            'sewing_reject' => $sewRj,
            'finishing_in' => (float) $fin->in_qty,
            'finishing_ok' => $finOk,
            'finishing_reject' => $finRj,
            'total_ok' => $totalOk,
            'total_reject' => $totalRj,
            'reject_rate' => ($totalOk + $totalRj) > 0 ? round($totalRj / ($totalOk + $totalRj) * 100, 1) : 0,
            'sewing_yield' => ($sewOk + $sewRj) > 0 ? round($sewOk / ($sewOk + $sewRj) * 100, 1) : 0,
        ];
    }

    // ==========================================================
    // 1b) TREND HARIAN
    // ==========================================================
    private function buildDailyTrend(array $f): \Illuminate\Support\Collection
    {
        $from = $f['date_from'];
        $to = $f['date_to'];

        $cutQ = DB::table('qc_results as qc')
            ->where('qc.stage', 'cutting')
            ->whereBetween('qc.qc_date', [$from, $to]);
        if ($f['item_id']) {
            $cutQ->join('cutting_job_bundles as b', 'b.id', '=', 'qc.cutting_job_bundle_id')
                ->where('b.finished_item_id', $f['item_id']);
        }
        $cutting = $cutQ->selectRaw('qc.qc_date as date, SUM(qc.qty_ok) ok, SUM(qc.qty_reject) rj')
            ->groupBy('qc.qc_date')->get()->keyBy('date');

        $sewQ = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->whereBetween('r.date', [$from, $to]);
        $this->applyNotVoid($sewQ, 'sewing_returns', 'r');
        if ($f['operator_id']) {
            $sewQ->where('r.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $sewQ->join('sewing_pickup_lines as pl2', 'pl2.id', '=', 'rl.sewing_pickup_line_id')
                ->where('pl2.finished_item_id', $f['item_id']);
        }
        $sewing = $sewQ->selectRaw('r.date as date, SUM(rl.qty_ok) ok, SUM(rl.qty_reject) rj')
            ->groupBy('r.date')->get()->keyBy('date');

        // gabung semua tanggal di rentang
        $rows = collect();
        $cursor = Carbon::parse($from);
        $end = Carbon::parse($to);
        while ($cursor->lte($end)) {
            $d = $cursor->toDateString();
            $c = $cutting->get($d);
            $s = $sewing->get($d);
            $rows->push((object) [
                'date' => $d,
                'cutting_ok' => (float) ($c->ok ?? 0),
                'sewing_ok' => (float) ($s->ok ?? 0),
                'reject' => (float) ($c->rj ?? 0) + (float) ($s->rj ?? 0),
            ]);
            $cursor->addDay();
        }

        return $rows;
    }

    // ==========================================================
    // 2) ALUR WIP (stok per stage + top item + aging)
    // ==========================================================
    private function buildWipFlow(): array
    {
        $today = now();
        $ids = Warehouse::whereIn('code', ['WIP-CUT', 'WIP-SEW', 'WIP-FIN'])
            ->pluck('id', 'code');

        $stockTotal = function ($whId) {
            return $whId ? (float) DB::table('inventory_stocks')->where('warehouse_id', $whId)->sum('qty') : 0;
        };
        $topItems = function ($whId) {
            if (!$whId) {
                return collect();
            }
            return DB::table('inventory_stocks as s')
                ->join('items as i', 'i.id', '=', 's.item_id')
                ->where('s.warehouse_id', $whId)
                ->groupBy('s.item_id', 'i.code', 'i.name')
                ->selectRaw('i.code as item_code, i.name as item_name, SUM(s.qty) as qty')
                ->havingRaw('SUM(s.qty) > 0.0001')
                ->orderByDesc('qty')
                ->limit(8)
                ->get();
        };

        $cutId = $ids['WIP-CUT'] ?? null;
        $sewId = $ids['WIP-SEW'] ?? null;
        $finId = $ids['WIP-FIN'] ?? null;

        // Aging WIP-SEW per pickup line outstanding
        $sewLines = SewingPickupLine::query()
            ->with(['sewingPickup.operator', 'bundle.finishedItem'])
            ->where('status', 'in_progress')
            ->get()
            ->map(function ($line) use ($today) {
                $pickup = $line->sewingPickup;
                $pickupDate = $pickup?->date ? Carbon::parse($pickup->date) : null;
                $used = (float) ($line->qty_returned_ok ?? 0) + (float) ($line->qty_returned_reject ?? 0);
                $remaining = max((float) $line->qty_bundle - $used, 0);
                $line->age_days = $pickupDate ? $pickupDate->diffInDays($today) : null;
                $line->remaining_qty = $remaining;
                return $line;
            })
            ->filter(fn($l) => $l->remaining_qty > 0)
            ->sortByDesc('age_days')
            ->take(20)
            ->map(fn($l) => (object) [
                'stage' => 'WIP-SEW',
                'ref' => $l->bundle?->finishedItem?->code ?? ('Bundle ' . ($l->bundle?->bundle_no ?? '-')),
                'operator' => $l->sewingPickup?->operator?->name ?? '-',
                'qty' => $l->remaining_qty,
                'age_days' => $l->age_days,
            ])
            ->values();

        return [
            'totals' => [
                'cut' => $stockTotal($cutId),
                'sew' => $stockTotal($sewId),
                'fin' => $stockTotal($finId),
            ],
            'top_cut' => $topItems($cutId),
            'top_sew' => $topItems($sewId),
            'top_fin' => $topItems($finId),
            'aging' => $sewLines,
        ];
    }

    // ==========================================================
    // 2b) BOTTLENECK / PIPELINE (funnel + days-of-cover)
    // ==========================================================
    /**
     * Analisa bottleneck pipeline:
     *  - Funnel throughput OK per stage (Cutting → Sewing → Finishing).
     *  - Backlog WIP per stage + "hari untuk mengosongkan" (days-of-cover proxy)
     *    = stok WIP / laju output stage konsumen saat ini.
     *  - Stage dengan days terbesar = bottleneck; alert bila >= ambang.
     *
     * Catatan grounded: tidak ada data due-date/shipment, jadi days-of-cover
     * dihitung terhadap LAJU OUTPUT produksi (clearance rate), bukan permintaan.
     */
    private function buildBottleneck(array $f): array
    {
        $from = $f['date_from'];
        $to = $f['date_to'];
        $days = max(
            Carbon::parse($from)->startOfDay()->diffInDays(Carbon::parse($to)->startOfDay()) + 1,
            1
        );

        // ---- Throughput (OK qty) per stage dalam periode ----
        $cutQ = DB::table('qc_results as qc')
            ->where('qc.stage', 'cutting')
            ->whereBetween('qc.qc_date', [$from, $to]);
        if ($f['item_id']) {
            $cutQ->join('cutting_job_bundles as b', 'b.id', '=', 'qc.cutting_job_bundle_id')
                ->where('b.finished_item_id', $f['item_id']);
        }
        $cutOk = (float) $cutQ->sum('qc.qty_ok');

        $sewQ = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->whereBetween('r.date', [$from, $to]);
        $this->applyNotVoid($sewQ, 'sewing_returns', 'r');
        if ($f['operator_id']) {
            $sewQ->where('r.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $sewQ->join('sewing_pickup_lines as pl2', 'pl2.id', '=', 'rl.sewing_pickup_line_id')
                ->where('pl2.finished_item_id', $f['item_id']);
        }
        $sewOk = (float) $sewQ->sum('rl.qty_ok');

        $finQ = DB::table('finishing_job_lines as fl')
            ->join('finishing_jobs as fj', 'fj.id', '=', 'fl.finishing_job_id')
            ->where('fj.status', 'posted')
            ->whereBetween('fj.date', [$from, $to]);
        if ($f['operator_id']) {
            $finQ->where('fl.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $finQ->where('fl.item_id', $f['item_id']);
        }
        $finOk = (float) $finQ->sum('fl.qty_ok');

        // ---- Snapshot backlog WIP (stok saat ini) ----
        $ids = Warehouse::whereIn('code', ['WIP-CUT', 'WIP-SEW', 'WIP-FIN', 'WH-RTS'])
            ->pluck('id', 'code');
        $stock = fn($code) => isset($ids[$code])
            ? (float) DB::table('inventory_stocks')->where('warehouse_id', $ids[$code])->sum('qty')
            : 0.0;
        $wipCut = $stock('WIP-CUT');
        $wipSew = $stock('WIP-SEW');
        $wipFin = $stock('WIP-FIN');
        $rts = $stock('WH-RTS');

        // ---- Laju harian (clearance rate) ----
        $cutRate = $cutOk / $days;
        $sewRate = $sewOk / $days;
        $finRate = $finOk / $days;

        $mkQueue = function (string $stage, string $label, float $backlog, float $rate) {
            return (object) [
                'stage' => $stage,
                'queue_label' => $label,
                'backlog' => $backlog,
                'rate' => round($rate, 1),
                'days' => $rate > 0 ? round($backlog / $rate, 1) : null,
            ];
        };

        $queues = collect([
            $mkQueue('Sewing', 'WIP-CUT menunggu dijahit', $wipCut, $sewRate),
            $mkQueue('Sewing (proses)', 'WIP-SEW sedang dijahit', $wipSew, $sewRate),
            $mkQueue('Finishing', 'WIP-FIN menunggu finishing', $wipFin, $finRate),
        ]);

        // Bottleneck = backlog>0 dengan days terbesar (atau yang stalled: rate 0 tapi backlog ada).
        $bottleneck = $queues
            ->filter(fn($q) => $q->backlog > 0)
            ->sortByDesc(fn($q) => $q->days ?? PHP_INT_MAX)
            ->first();

        $threshold = 7;
        $alerts = $queues->filter(function ($q) use ($threshold) {
            // stalled (ada backlog tapi tidak ada output) ATAU days >= ambang
            return ($q->backlog > 0 && ($q->rate <= 0)) || ($q->days !== null && $q->days >= $threshold);
        })->values();

        // ---- Funnel throughput ----
        $funnel = collect([
            (object) ['label' => 'Cutting OK', 'qty' => $cutOk],
            (object) ['label' => 'Sewing OK', 'qty' => $sewOk],
            (object) ['label' => 'Finishing OK', 'qty' => $finOk],
        ]);
        $funnelMax = max($cutOk, $sewOk, $finOk, 1.0);

        return [
            'days' => $days,
            'threshold' => $threshold,
            'queues' => $queues,
            'bottleneck' => $bottleneck,
            'alerts' => $alerts,
            'funnel' => $funnel,
            'funnel_max' => $funnelMax,
            'rts_stock' => $rts,
            'cut_rate' => round($cutRate, 1),
            'sew_rate' => round($sewRate, 1),
            'fin_rate' => round($finRate, 1),
        ];
    }

    // ==========================================================
    // 3) OUTSTANDING & AGING SEWING
    // ==========================================================
    private function buildOutstanding(array $f): array
    {
        $today = Carbon::today();

        $q = SewingPickupLine::query()
            ->with(['sewingPickup.operator', 'finishedItem'])
            ->whereHas('sewingPickup', function ($qq) use ($f) {
                if ($f['operator_id']) {
                    $qq->where('operator_id', $f['operator_id']);
                }
            });
        if ($f['item_id']) {
            $q->where('finished_item_id', $f['item_id']);
        }

        $lines = $q->get()->map(function ($line) use ($today) {
            $picked = (float) ($line->qty_bundle ?? 0);
            $used = (float) ($line->qty_returned_ok ?? 0) + (float) ($line->qty_returned_reject ?? 0);
            $outstanding = max($picked - $used, 0);
            $pickup = $line->sewingPickup;
            $pickupDate = $pickup?->date ? Carbon::parse($pickup->date)->startOfDay() : null;
            $line->outstanding = $outstanding;
            $line->aging_days = $pickupDate ? $pickupDate->diffInDays($today) : null;
            return $line;
        })->filter(fn($l) => $l->outstanding > 0);

        $bucket = fn($lo, $hi) => $lines->filter(function ($l) use ($lo, $hi) {
            $a = $l->aging_days;
            return $a !== null && $a >= $lo && ($hi === null || $a <= $hi);
        })->sum('outstanding');

        $detail = $lines->groupBy(fn($l) => ($l->sewingPickup?->operator_id ?? 0) . '-' . ($l->finished_item_id ?? 0))
            ->map(function ($g) {
                $first = $g->first();
                return (object) [
                    'operator_code' => $first->sewingPickup?->operator?->code ?? '-',
                    'operator_name' => $first->sewingPickup?->operator?->name ?? '-',
                    'item_code' => $first->finishedItem?->code ?? '-',
                    'item_name' => $first->finishedItem?->name ?? '',
                    'outstanding' => $g->sum('outstanding'),
                    'max_aging' => $g->max('aging_days'),
                ];
            })
            ->sortByDesc('outstanding')
            ->values();

        return [
            'total' => $lines->sum('outstanding'),
            'buckets' => [
                'b0_3' => $bucket(0, 3),
                'b4_7' => $bucket(4, 7),
                'b8_14' => $bucket(8, 14),
                'b15p' => $bucket(15, null),
            ],
            'detail' => $detail,
        ];
    }

    // ==========================================================
    // 4) PERFORMA OPERATOR (pickup + return + lead + score)
    // ==========================================================
    private function buildOperatorPerformance(array $f): \Illuminate\Support\Collection
    {
        $from = $f['date_from'];
        $to = $f['date_to'];

        // pickup-side: picked + outstanding (pickup date in range)
        $pickQ = DB::table('sewing_pickup_lines as pl')
            ->join('sewing_pickups as p', 'p.id', '=', 'pl.sewing_pickup_id')
            ->join('employees as e', 'e.id', '=', 'p.operator_id')
            ->whereBetween('p.date', [$from, $to]);
        $this->applyNotVoid($pickQ, 'sewing_pickups', 'p');
        if ($f['operator_id']) {
            $pickQ->where('p.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $pickQ->where('pl.finished_item_id', $f['item_id']);
        }
        $pick = $pickQ->selectRaw('
                p.operator_id, e.code as operator_code, e.name as operator_name,
                COUNT(DISTINCT p.id) as total_pickups,
                COALESCE(SUM(pl.qty_bundle),0) as picked,
                COALESCE(SUM(pl.qty_bundle - COALESCE(pl.qty_returned_ok,0) - COALESCE(pl.qty_returned_reject,0)),0) as outstanding
            ')
            ->groupBy('p.operator_id', 'e.code', 'e.name')
            ->get()->keyBy('operator_id');

        // return-side: ok + reject (return date in range)
        $retQ = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->whereBetween('r.date', [$from, $to]);
        $this->applyNotVoid($retQ, 'sewing_returns', 'r');
        if ($f['operator_id']) {
            $retQ->where('r.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $retQ->join('sewing_pickup_lines as pl2', 'pl2.id', '=', 'rl.sewing_pickup_line_id')
                ->where('pl2.finished_item_id', $f['item_id']);
        }
        $ret = $retQ->selectRaw('
                r.operator_id,
                COALESCE(SUM(rl.qty_ok),0) as total_ok,
                COALESCE(SUM(rl.qty_reject),0) as total_reject
            ')
            ->groupBy('r.operator_id')->get()->keyBy('operator_id');

        // lead time: avg(return.date - pickup.date) per operator (pickup date in range)
        $leadRows = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->join('sewing_pickups as p', 'p.id', '=', 'pl.sewing_pickup_id')
            ->whereBetween('p.date', [$from, $to])
            ->when($f['operator_id'], fn($q) => $q->where('r.operator_id', $f['operator_id']))
            ->when($f['item_id'], fn($q) => $q->where('pl.finished_item_id', $f['item_id']))
            ->selectRaw('r.operator_id, r.date as return_date, p.date as pickup_date')
            ->get()
            ->groupBy('operator_id')
            ->map(function ($g) {
                $vals = $g->map(function ($row) {
                    if (!$row->pickup_date || !$row->return_date) {
                        return null;
                    }
                    return Carbon::parse($row->pickup_date)->startOfDay()
                        ->diffInDays(Carbon::parse($row->return_date)->startOfDay());
                })->filter(fn($v) => $v !== null);
                return $vals->count() ? round($vals->avg(), 1) : null;
            });

        // gabung per operator
        $opIds = collect($pick->keys())->merge($ret->keys())->unique()->filter();

        return $opIds->map(function ($opId) use ($pick, $ret, $leadRows) {
            $p = $pick->get($opId);
            $r = $ret->get($opId);
            $picked = (float) ($p->picked ?? 0);
            $ok = (float) ($r->total_ok ?? 0);
            $reject = (float) ($r->total_reject ?? 0);
            $outstanding = (float) ($p->outstanding ?? 0);
            $base = $ok + $reject;
            $efficiency = $base > 0 ? round($ok / $base * 100, 1) : null;
            $avgLead = $leadRows->get($opId);

            // Behavior score (0-100)
            $okRate = $picked > 0 ? $ok / $picked : 0;
            $rejectRate = $base > 0 ? $reject / $base : 0;
            $outRatio = $picked > 0 ? max($outstanding, 0) / $picked : 0;
            $scoreCompletion = min($okRate * 100, 100) * 0.40;
            $scoreReject = (1 - min($rejectRate, 0.20) / 0.20) * 25;
            $scoreOutstanding = (1 - min($outRatio, 0.50) / 0.50) * 20;
            $scoreLead = $avgLead === null ? 7.5 : (1 - min($avgLead, 7) / 7) * 15;
            $score = max(0, min(100, round($scoreCompletion + $scoreReject + $scoreOutstanding + $scoreLead, 1)));
            $grade = $score >= 85 ? 'Excellent' : ($score >= 70 ? 'Good' : ($score >= 50 ? 'Cukup' : 'Perlu Perhatian'));

            return (object) [
                'operator_code' => $p->operator_code ?? '-',
                'operator_name' => $p->operator_name ?? '-',
                'total_pickups' => (int) ($p->total_pickups ?? 0),
                'picked' => $picked,
                'total_ok' => $ok,
                'total_reject' => $reject,
                'outstanding' => max($outstanding, 0),
                'efficiency' => $efficiency,
                'avg_lead_days' => $avgLead,
                'score' => $score,
                'grade' => $grade,
            ];
        })->sortByDesc('score')->values();
    }

    // ==========================================================
    // 5) ANALISA REJECT (cutting + sewing)
    // ==========================================================
    private function buildReject(array $f): array
    {
        $from = $f['date_from'];
        $to = $f['date_to'];

        // Cutting rejects
        $cutQ = DB::table('qc_results as qc')
            ->join('cutting_job_bundles as b', 'b.id', '=', 'qc.cutting_job_bundle_id')
            ->join('cutting_jobs as j', 'j.id', '=', 'b.cutting_job_id')
            ->leftJoin('items as it', 'it.id', '=', 'b.finished_item_id')
            ->leftJoin('employees as op', 'op.id', '=', 'b.operator_id')
            ->where('qc.stage', 'cutting')
            ->where('qc.qty_reject', '>', 0)
            ->whereBetween('qc.qc_date', [$from, $to]);
        if ($f['item_id']) {
            $cutQ->where('b.finished_item_id', $f['item_id']);
        }
        $cutRows = $cutQ->selectRaw('
                qc.qc_date as date, \'cutting\' as stage,
                COALESCE(it.code,\'-\') as item_code, COALESCE(it.name,\'\') as item_name,
                COALESCE(op.name,\'-\') as operator_name,
                qc.qty_ok, qc.qty_reject, qc.notes, j.code as ref_code
            ')->get();

        // Sewing rejects
        $sewQ = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->join('employees as op', 'op.id', '=', 'r.operator_id')
            ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->join('items as it', 'it.id', '=', 'pl.finished_item_id')
            ->where('rl.qty_reject', '>', 0)
            ->whereBetween('r.date', [$from, $to]);
        $this->applyNotVoid($sewQ, 'sewing_returns', 'r');
        if ($f['operator_id']) {
            $sewQ->where('r.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $sewQ->where('pl.finished_item_id', $f['item_id']);
        }
        $sewRows = $sewQ->selectRaw('
                r.date as date, \'sewing\' as stage,
                it.code as item_code, it.name as item_name,
                op.name as operator_name,
                rl.qty_ok, rl.qty_reject, rl.notes, r.code as ref_code
            ')->get();

        // operator filter for cutting rows is skipped (cutting punya operator sendiri).
        $rows = $cutRows->concat($sewRows)->sortByDesc('date')->values();

        $byOperator = $rows->groupBy('operator_name')->map(fn($g) => (object) [
            'operator_name' => $g->first()->operator_name,
            'total_reject' => $g->sum('qty_reject'),
            'total_ok' => $g->sum('qty_ok'),
        ])->sortByDesc('total_reject')->values();

        $byItem = $rows->groupBy('item_code')->map(fn($g) => (object) [
            'item_code' => $g->first()->item_code,
            'item_name' => $g->first()->item_name,
            'total_reject' => $g->sum('qty_reject'),
            'total_ok' => $g->sum('qty_ok'),
        ])->sortByDesc('total_reject')->values();

        return [
            'total_reject' => $rows->sum('qty_reject'),
            'by_operator' => $byOperator,
            'by_item' => $byItem,
            'detail' => $rows->take(100),
        ];
    }

    // ==========================================================
    // 6) OUTPUT PER ITEM (sewing + finishing)
    // ==========================================================
    private function buildPerItem(array $f): array
    {
        $from = $f['date_from'];
        $to = $f['date_to'];

        // Sewing per item
        $sewQ = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->join('items as it', 'it.id', '=', 'pl.finished_item_id')
            ->whereBetween('r.date', [$from, $to]);
        $this->applyNotVoid($sewQ, 'sewing_returns', 'r');
        if ($f['operator_id']) {
            $sewQ->where('r.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $sewQ->where('pl.finished_item_id', $f['item_id']);
        }
        $sewing = $sewQ->selectRaw('
                it.code as item_code, it.name as item_name,
                SUM(rl.qty_ok) as total_ok, SUM(rl.qty_reject) as total_reject
            ')
            ->groupBy('it.code', 'it.name')
            ->orderBy('it.code')
            ->get();

        // Finishing per item (posted)
        $finQ = DB::table('finishing_job_lines as fl')
            ->join('finishing_jobs as fj', 'fj.id', '=', 'fl.finishing_job_id')
            ->join('items as it', 'it.id', '=', 'fl.item_id')
            ->where('fj.status', 'posted')
            ->whereBetween('fj.date', [$from, $to]);
        if ($f['operator_id']) {
            $finQ->where('fl.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $finQ->where('fl.item_id', $f['item_id']);
        }
        $finishing = $finQ->selectRaw('
                it.code as item_code, it.name as item_name,
                SUM(fl.qty_in) as total_in, SUM(fl.qty_ok) as total_ok, SUM(fl.qty_reject) as total_reject
            ')
            ->groupBy('it.code', 'it.name')
            ->orderBy('it.code')
            ->get();

        return [
            'sewing' => $sewing,
            'finishing' => $finishing,
        ];
    }
}
