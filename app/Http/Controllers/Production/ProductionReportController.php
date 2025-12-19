<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\FinishingJobLine;
use App\Models\Item;
use App\Models\QcResult;
use App\Models\SewingPickupLine;
use App\Models\SewingReturn;
use App\Models\SewingReturnLine;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionReportController extends Controller
{
    /**
     * 2️⃣ Laporan Performa Cutting → Sewing (Lead Time & Loss)
     */
    public function cuttingToSewingLoss(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $warehouseId = $request->get('warehouse_id');
        $fabricItemId = $request->get('fabric_item_id'); // item kain LOT (FLC280BLK, dll)

        $q = CuttingJob::query()
            ->with([
                'warehouse',
                'lot.item',
            ])
            ->select('cutting_jobs.*')
            ->addSelect(DB::raw('
                -- Qty OK hasil QC Cutting
                (
                    select coalesce(sum(qc.qty_ok), 0)
                    from qc_results qc
                    join cutting_job_bundles b on b.id = qc.cutting_job_bundle_id
                    where qc.stage = \'cutting\'
                      and b.cutting_job_id = cutting_jobs.id
                ) as qty_cut_ok,

                -- Total qty yang pernah dipickup ke Sewing
                (
                    select coalesce(sum(pl.qty_bundle), 0)
                    from cutting_job_bundles b2
                    join sewing_pickup_lines pl on pl.cutting_job_bundle_id = b2.id
                    where b2.cutting_job_id = cutting_jobs.id
                ) as qty_picked,

                -- Total OK di Sewing Return
                (
                    select coalesce(sum(rl.qty_ok), 0)
                    from cutting_job_bundles b3
                    join sewing_pickup_lines pl2 on pl2.cutting_job_bundle_id = b3.id
                    join sewing_return_lines rl on rl.sewing_pickup_line_id = pl2.id
                    where b3.cutting_job_id = cutting_jobs.id
                ) as qty_sewing_ok,

                -- Total Reject di Sewing Return
                (
                    select coalesce(sum(rl2.qty_reject), 0)
                    from cutting_job_bundles b4
                    join sewing_pickup_lines pl3 on pl3.cutting_job_bundle_id = b4.id
                    join sewing_return_lines rl2 on rl2.sewing_pickup_line_id = pl3.id
                    where b4.cutting_job_id = cutting_jobs.id
                ) as qty_sewing_reject
            '));

        // Filter tanggal Cutting Job
        if ($dateFrom) {
            $q->whereDate('cutting_jobs.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $q->whereDate('cutting_jobs.date', '<=', $dateTo);
        }

        // Filter gudang
        if ($warehouseId) {
            $q->where('cutting_jobs.warehouse_id', $warehouseId);
        }

        // Filter kain LOT (item di LOT)
        if ($fabricItemId) {
            $q->whereHas('lot', function ($qq) use ($fabricItemId) {
                $qq->where('item_id', $fabricItemId);
            });
        }

        // Opsional: hanya job yang sudah di-QC atau sudah pernah dipickup
        $q->where(function ($qq) {
            $qq->whereRaw('
                (select coalesce(sum(qc.qty_ok), 0)
                 from qc_results qc
                 join cutting_job_bundles b on b.id = qc.cutting_job_bundle_id
                 where qc.stage = \'cutting\'
                   and b.cutting_job_id = cutting_jobs.id
                ) > 0
            ')
                ->orWhereRaw('
                (select coalesce(sum(pl.qty_bundle), 0)
                 from cutting_job_bundles b2
                 join sewing_pickup_lines pl on pl.cutting_job_bundle_id = b2.id
                 where b2.cutting_job_id = cutting_jobs.id
                ) > 0
            ');
        });

        $jobs = $q
            ->orderByDesc('cutting_jobs.date')
            ->orderByDesc('cutting_jobs.id')
            ->paginate(20)
            ->withQueryString();

        // Data untuk filter dropdown
        $warehouses = Warehouse::orderBy('code')->get();

        $fabricItems = Item::query()
            ->where('type', 'raw_material') // atau sesuaikan kalau LOT pakai kategori tertentu
            ->orderBy('code')
            ->get();

        return view('production.reports.cutting_to_sewing_loss', [
            'jobs' => $jobs,
            'warehouses' => $warehouses,
            'fabricItems' => $fabricItems,
            'filters' => $request->only(['date_from', 'date_to', 'warehouse_id', 'fabric_item_id']),
        ]);
    }

    /**
     * 4️⃣ Laporan Rekap Harian Produksi (Daily Production Summary)
     * Source dari QC Cutting + SewingReturn + SewingReturnLine.
     */
    public function dailyProduction(Request $request)
    {
        $preset = $request->get('preset');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // =======================
        // 1. PRESET HANDLING
        // =======================
        if ($preset) {
            if ($preset === 'today') {
                $dateFrom = $dateTo = now()->toDateString();
            } elseif ($preset === 'yesterday') {
                $dateFrom = $dateTo = now()->subDay()->toDateString();
            } elseif ($preset === '7days') {
                $dateTo = now()->toDateString();
                $dateFrom = now()->subDays(7)->toDateString();
            } elseif ($preset === 'thismonth') {
                $dateFrom = now()->startOfMonth()->toDateString();
                $dateTo = now()->endOfMonth()->toDateString();
            }
        }

        // =======================
        // 2. MANUAL DATE RANGE
        // =======================
        if (!$dateFrom && !$dateTo) {
            // default: 7 hari terakhir
            $dateTo = now()->toDateString();
            $dateFrom = now()->subDays(7)->toDateString();
        } elseif ($dateFrom && !$dateTo) {
            $dateTo = $dateFrom;
        } elseif (!$dateFrom && $dateTo) {
            $dateFrom = $dateTo;
        }

        // Pastikan from <= to
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        // ==========================
        // 3. Cutting QC OK per hari
        // ==========================
        $cutting = DB::table('qc_results as qc')
            ->join('cutting_job_bundles as b', 'b.id', '=', 'qc.cutting_job_bundle_id')
            ->join('cutting_jobs as j', 'j.id', '=', 'qc.cutting_job_id')
            ->selectRaw('qc.qc_date as date, sum(qc.qty_ok) as total_ok, group_concat(distinct j.id) as job_ids')
            ->where('qc.stage', 'cutting')
            ->whereBetween('qc.qc_date', [$dateFrom, $dateTo])
            ->groupBy('qc.qc_date')
            ->get();

        // ==========================
        // 4. Sewing OK per hari
        // ==========================
        $sewing = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->selectRaw('r.date as date, sum(rl.qty_ok) as total_ok, group_concat(distinct r.id) as return_ids')
            ->whereBetween('r.date', [$dateFrom, $dateTo])
            ->groupBy('r.date')
            ->get();

        // ==========================
        // 5. Reject (Cutting + Sewing)
        // ==========================
        $rejectCutting = DB::table('qc_results')
            ->selectRaw('qc_date as date, sum(qty_reject) as total_reject')
            ->where('stage', 'cutting')
            ->whereBetween('qc_date', [$dateFrom, $dateTo])
            ->groupBy('qc_date')
            ->get();

        $rejectSewing = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->selectRaw('r.date as date, sum(rl.qty_reject) as total_reject')
            ->whereBetween('r.date', [$dateFrom, $dateTo])
            ->groupBy('r.date')
            ->get();

        // ==========================
        // 6. Gabungkan per tanggal
        // ==========================
        $dates = [];

        // inisialisasi key tanggal
        foreach ([$cutting, $sewing, $rejectCutting, $rejectSewing] as $group) {
            foreach ($group as $row) {
                if (!isset($dates[$row->date])) {
                    $dates[$row->date] = [
                        'date' => $row->date,
                        'cutting_ok' => 0,
                        'sewing_ok' => 0,
                        'reject_total' => 0,
                        'cutting_jobs' => [],
                        'sewing_returns' => [],
                    ];
                }
            }
        }

        foreach ($cutting as $row) {
            $dates[$row->date]['cutting_ok'] = (float) $row->total_ok;
            $dates[$row->date]['cutting_jobs'] = $row->job_ids
            ? explode(',', $row->job_ids)
            : [];
        }

        foreach ($sewing as $row) {
            $dates[$row->date]['sewing_ok'] = (float) $row->total_ok;
            $dates[$row->date]['sewing_returns'] = $row->return_ids
            ? explode(',', $row->return_ids)
            : [];
        }

        foreach ($rejectCutting as $row) {
            $dates[$row->date]['reject_total'] += (float) $row->total_reject;
        }

        foreach ($rejectSewing as $row) {
            $dates[$row->date]['reject_total'] += (float) $row->total_reject;
        }

        $records = collect($dates)->sortKeys()->values();

        return view('production.reports.daily_production', [
            'records' => $records,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'preset' => $preset,
        ]);
    }

    /**
     * 5️⃣ Laporan Reject Detail (Root Cause Hunting)
     * Fokus ke qty_reject > 0, bisa filter operator / item / tanggal.
     */
    public function rejectDetail(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');
        $operatorId = $request->get('operator_id');

        // =============================
        // 1) Reject dari QC Cutting
        // =============================
        $cuttingRejects = QcResult::query()
            ->where('stage', 'cutting')
            ->where('qty_reject', '>', 0)
            ->with([
                'cuttingJobBundle.cuttingJob.lot.item',
                'cuttingJobBundle.finishedItem',
                'cuttingJobBundle.operator',
            ]);

        if ($from) {
            $cuttingRejects->whereDate('qc_date', '>=', $from);
        }

        if ($to) {
            $cuttingRejects->whereDate('qc_date', '<=', $to);
        }

        if ($operatorId) {
            $cuttingRejects->whereHas('cuttingJobBundle', function ($q) use ($operatorId) {
                $q->where('operator_id', $operatorId);
            });
        }

        $cuttingRejects = $cuttingRejects->get()->map(function ($r) {
            return (object) [
                'date' => $r->qc_date,
                'stage' => 'cutting',
                'item_code' => $r->cuttingJobBundle->finishedItem->code ?? '-',
                'lot_code' => $r->cuttingJobBundle->cuttingJob->lot->code ?? '-',
                'lot_item_name' => $r->cuttingJobBundle->cuttingJob->lot->item->code ?? '-',
                'qty_ok' => $r->qty_ok,
                'qty_reject' => $r->qty_reject,
                'notes' => $r->notes,
                'operator_name' => $r->cuttingJobBundle->operator->name ?? '-',

                // 🔗 DRILLDOWN
                'link_code' => $r->cuttingJobBundle->cuttingJob->code,
                'link_url' => route('production.cutting_jobs.show', $r->cuttingJobBundle->cuttingJob->id),
            ];
        });

        // =============================
        // 2) Reject dari Sewing Return
        // =============================
        $sewingRejects = SewingReturnLine::query()
            ->where('qty_reject', '>', 0)
            ->with([
                'sewingReturn',
                'pickupLine.bundle.finishedItem',
                'pickupLine.bundle.cuttingJob.lot.item',
                'pickupLine.sewingPickup.operator',
            ]);

        if ($from) {
            // FIX: pakai alias tabel "sewing_returns as r"
            $sewingRejects->whereDate('sewing_returns.date', '>=', $from);
        }

        if ($to) {
            $sewingRejects->whereDate('sewing_returns.date', '<=', $to);
        }

        if ($operatorId) {
            $sewingRejects->whereHas('pickupLine.sewingPickup', function ($q) use ($operatorId) {
                $q->where('operator_id', $operatorId);
            });
        }

        $sewingRejects = $sewingRejects->get()->map(function ($r) {
            $pickup = $r->pickupLine->sewingPickup;

            return (object) [
                'date' => $r->sewingReturn->date,
                'stage' => 'sewing',
                'item_code' => $r->pickupLine->bundle->finishedItem->code ?? '-',
                'lot_code' => $r->pickupLine->bundle->cuttingJob->lot->code ?? '-',
                'lot_item_name' => $r->pickupLine->bundle->cuttingJob->lot->item->code ?? '-',
                'qty_ok' => $r->qty_ok,
                'qty_reject' => $r->qty_reject,
                'notes' => $r->notes,
                'operator_name' => $pickup->operator->name ?? '-',

                // 🔗 DRILLDOWN
                'link_code' => $r->sewingReturn->code,
                'link_url' => route('production.sewing_returns.show', $r->sewingReturn->id),
            ];
        });

        // =============================
        // MERGE Cutting + Sewing
        // =============================
        $rows = $cuttingRejects
            ->merge($sewingRejects)
            ->sortByDesc('date')
            ->values();

        $operators = Employee::whereIn('role', ['cutting', 'sewing'])->get();

        return view('production.reports.reject_detail', [
            'rows' => $rows,
            'operators' => $operators,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'operator_id' => $operatorId,
            ],
        ]);
    }

    /**
     * 6️⃣ Laporan WIP Sewing Age (umur WIP di operator jahit)
     */
    public function wipSewingAge(Request $request)
    {
        $operatorId = $request->get('operator_id');

        $q = SewingPickupLine::query()
            ->with([
                // relasi di model SewingPickupLine harus bernama "pickup"
                'pickup.operator',
                'pickup.warehouse',
                'bundle.finishedItem',
                'bundle.cuttingJob.lot.item',
            ])
            ->where('status', 'in_progress');

        if ($operatorId) {
            $q->whereHas('pickup', function ($qq) use ($operatorId) {
                $qq->where('operator_id', $operatorId);
            });
        }

        $lines = $q
            ->orderByDesc('id')
            ->get()
            ->map(function ($line) {
                // FIX: gunakan relasi "pickup" (bukan sewingPickup)
                $pickup = $line->pickup;
                $pickupDate = $pickup?->date
                ? \Illuminate\Support\Carbon::parse($pickup->date)
                : null;

                $today = now();
                $age = $pickupDate ? $pickupDate->diffInDays($today) : null;

                $returnedOk = (float) ($line->qty_returned_ok ?? 0);
                $returnedReject = (float) ($line->qty_returned_reject ?? 0);
                $used = $returnedOk + $returnedReject;
                $remaining = (float) $line->qty_bundle - $used;

                if ($remaining < 0) {
                    $remaining = 0;
                }

                $line->age_days = $age;
                $line->remaining_qty = $remaining;
                $line->used_qty = $used;

                return $line;
            });

        $operators = Employee::query()
            ->where('role', 'sewing')
            ->orderBy('code')
            ->get();

        return view('production.reports.wip_sewing_age', [
            'lines' => $lines,
            'operators' => $operators,
            'filters' => $request->only(['operator_id']),
        ]);
    }

    /**
     * 7️⃣ Laporan Sewing per Item Jadi
     */
    public function sewingPerItem(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $operatorId = $request->get('operator_id');
        $itemId = $request->get('item_id');

        $q = SewingReturn::query()
            ->join('sewing_return_lines as rl', 'rl.sewing_return_id', '=', 'sewing_returns.id')
            ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->join('items as it', 'it.id', '=', 'pl.finished_item_id')
            ->leftJoin('employees as op', 'op.id', '=', 'sewing_returns.operator_id')
            ->selectRaw('
                pl.finished_item_id,
                it.code as item_code,
                it.name as item_name,
                SUM(rl.qty_ok) as total_ok,
                SUM(rl.qty_reject) as total_reject,
                COUNT(DISTINCT sewing_returns.operator_id) as total_operators,
                COUNT(DISTINCT sewing_returns.id) as total_returns
            ')
            ->groupBy('pl.finished_item_id', 'it.code', 'it.name');

        if ($dateFrom) {
            $q->whereDate('sewing_returns.date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $q->whereDate('sewing_returns.date', '<=', $dateTo);
        }

        if ($operatorId) {
            $q->where('sewing_returns.operator_id', $operatorId);
        }

        if ($itemId) {
            $q->where('pl.finished_item_id', $itemId);
        }

        $rows = $q->orderBy('it.code')->get();

        // buat filter operator & item
        $operators = Employee::query()
            ->where('role', 'sewing')
            ->orderBy('code')
            ->get();

        $items = Item::query()
            ->where('type', 'finished_good')
            ->orderBy('code')
            ->get();

        return view('production.reports.sewing_per_item', [
            'rows' => $rows,
            'operators' => $operators,
            'items' => $items,
            'filters' => $request->only(['date_from', 'date_to', 'operator_id', 'item_id']),
        ]);
    }

    public function finishingJobs(Request $request)
    {
        // Filter tanggal default: 7 hari terakhir
        $defaultFrom = Carbon::now()->subDays(7)->toDateString();
        $defaultTo = Carbon::now()->toDateString();

        $dateFrom = $request->input('date_from', $defaultFrom);
        $dateTo = $request->input('date_to', $defaultTo);
        $itemId = $request->input('item_id');
        $operatorId = $request->input('operator_id');

        $query = FinishingJobLine::query()
            ->join('finishing_jobs', 'finishing_job_lines.finishing_job_id', '=', 'finishing_jobs.id')
            ->join('items', 'finishing_job_lines.item_id', '=', 'items.id')
            ->leftJoin('employees', 'finishing_job_lines.operator_id', '=', 'employees.id')
            ->whereBetween('finishing_jobs.date', [$dateFrom, $dateTo])
        // hanya job yang sudah diposting supaya stok sudah jalan
            ->where('finishing_jobs.status', 'posted');

        if ($itemId) {
            $query->where('finishing_job_lines.item_id', $itemId);
        }

        if ($operatorId) {
            $query->where('finishing_job_lines.operator_id', $operatorId);
        }

        $rows = $query
            ->selectRaw('
                items.id     as item_id,
                items.code   as item_code,
                items.name   as item_name,
                SUM(finishing_job_lines.qty_in)      as total_in,
                SUM(finishing_job_lines.qty_ok)      as total_ok,
                SUM(finishing_job_lines.qty_reject)  as total_reject
            ')
            ->groupBy('items.id', 'items.code', 'items.name')
            ->orderBy('items.code')
            ->get();

        // hitung summary
        $summary = [
            'total_in' => $rows->sum('total_in'),
            'total_ok' => $rows->sum('total_ok'),
            'total_reject' => $rows->sum('total_reject'),
        ];

        // Data untuk filter dropdown
        $items = Item::orderBy('code')->get();
        $operators = Employee::orderBy('name')->get();
        // dd($items);
        return view('production.reports.finishing_jobs', [
            'rows' => $rows,
            'summary' => $summary,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'itemId' => $itemId,
            'operatorId' => $operatorId,
            'items' => $items,
            'operators' => $operators,
        ]);
    }

    public function productionFlowDashboard(Request $request)
    {
        $today = now();

        // =========================
        // 1. WIP STOCK PER GUDANG
        // =========================

        $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');
        $wipSewWarehouseId = Warehouse::where('code', 'WIP-SEW')->value('id');
        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');

        $wipCutTotal = $wipCutWarehouseId
        ? (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $wipCutWarehouseId)
            ->sum('qty')
        : 0;

        $wipSewTotal = $wipSewWarehouseId
        ? (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $wipSewWarehouseId)
            ->sum('qty')
        : 0;

        $wipFinTotal = $wipFinWarehouseId
        ? (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $wipFinWarehouseId)
            ->sum('qty')
        : 0;

        // =========================
        // 2. TOP WIP PER ITEM (server-side filter qty > 0)
        // =========================

        $wipCutItems = collect();
        if ($wipCutWarehouseId) {
            $wipCutItems = DB::table('inventory_stocks as s')
                ->join('items as i', 'i.id', '=', 's.item_id')
                ->where('s.warehouse_id', $wipCutWarehouseId)
                ->groupBy('s.item_id', 'i.code', 'i.name')
                ->selectRaw('
                s.item_id,
                i.code  as item_code,
                i.name  as item_name,
                SUM(s.qty) as qty_wip
            ')
                ->havingRaw('SUM(s.qty) > 0.0001')
                ->orderByDesc('qty_wip')
                ->limit(10)
                ->get();
        }

        $wipSewItems = collect();
        if ($wipSewWarehouseId) {
            $wipSewItems = DB::table('inventory_stocks as s')
                ->join('items as i', 'i.id', '=', 's.item_id')
                ->where('s.warehouse_id', $wipSewWarehouseId)
                ->groupBy('s.item_id', 'i.code', 'i.name')
                ->selectRaw('
                s.item_id,
                i.code  as item_code,
                i.name  as item_name,
                SUM(s.qty) as qty_wip
            ')
                ->havingRaw('SUM(s.qty) > 0.0001')
                ->orderByDesc('qty_wip')
                ->limit(10)
                ->get();
        }

        $wipFinItems = collect();
        if ($wipFinWarehouseId) {
            $wipFinItems = DB::table('inventory_stocks as s')
                ->join('items as i', 'i.id', '=', 's.item_id')
                ->where('s.warehouse_id', $wipFinWarehouseId)
                ->groupBy('s.item_id', 'i.code', 'i.name')
                ->selectRaw('
                s.item_id,
                i.code  as item_code,
                i.name  as item_name,
                SUM(s.qty) as qty_wip
            ')
                ->havingRaw('SUM(s.qty) > 0.0001')
                ->orderByDesc('qty_wip')
                ->limit(10)
                ->get();
        }

        // =======================================
        // 3. DETAIL + AGING WIP-CUT per BUNDLE
        // =======================================

        $wipCutBundles = DB::table('cutting_job_bundles as b')
            ->join('cutting_jobs as j', 'j.id', '=', 'b.cutting_job_id')
            ->leftJoin('qc_results as qc', function ($join) {
                $join->on('qc.cutting_job_bundle_id', '=', 'b.id')
                    ->where('qc.stage', 'cutting');
            })
            ->leftJoin('sewing_pickup_lines as pl', 'pl.cutting_job_bundle_id', '=', 'b.id')
            ->selectRaw('
            b.id,
            b.bundle_no,
            j.code              as cutting_code,
            j.date              as cutting_date,
            COALESCE(SUM(qc.qty_ok), 0)                     as qty_cut_ok,
            COALESCE(SUM(pl.qty_bundle), 0)                 as qty_picked,
            COALESCE(SUM(qc.qty_ok), 0) - COALESCE(SUM(pl.qty_bundle), 0) as wip_cut_qty
        ')
            ->groupBy('b.id', 'b.bundle_no', 'j.code', 'j.date')
            ->having('wip_cut_qty', '>', 0)
            ->get()
            ->map(function ($row) use ($today) {
                $cutDate = $row->cutting_date
                ? \Illuminate\Support\Carbon::parse($row->cutting_date)
                : null;

                $row->age_days = $cutDate ? $cutDate->diffInDays($today) : null;

                return $row;
            })
            ->sortByDesc('age_days')
            ->take(30)
            ->values();

        // =======================================
        // 4. DETAIL + AGING WIP-SEW per PICKUP LINE
        // =======================================

        $wipSewLines = SewingPickupLine::query()
            ->with([
                'sewingPickup.operator',
                'sewingPickup.warehouse',
                'bundle.finishedItem',
                'bundle.cuttingJob.lot.item',
            ])
            ->where('status', 'in_progress')
            ->get()
            ->map(function ($line) use ($today) {
                $pickup = $line->sewingPickup;
                $pickupDate = $pickup?->date
                ? \Illuminate\Support\Carbon::parse($pickup->date)
                : null;

                $returnedOk = (float) ($line->qty_returned_ok ?? 0);
                $returnedReject = (float) ($line->qty_returned_reject ?? 0);
                $used = $returnedOk + $returnedReject;
                $remaining = (float) $line->qty_bundle - $used;

                if ($remaining < 0) {
                    $remaining = 0;
                }

                $line->age_days = $pickupDate ? $pickupDate->diffInDays($today) : null;
                $line->remaining_qty = $remaining;
                $line->used_qty = $used;

                return $line;
            });

        $wipSewTotalFromLines = $wipSewLines->sum('remaining_qty');

        $wipSewAging = $wipSewLines
            ->filter(fn($l) => $l->remaining_qty > 0)
            ->sortByDesc('age_days')
            ->take(30)
            ->values();

        // =======================================
        // 5. DETAIL + AGING WIP-FIN per BUNDLE
        // =======================================

        $wipFinBundles = DB::table('cutting_job_bundles as b')
            ->join('cutting_jobs as j', 'j.id', '=', 'b.cutting_job_id')
            ->leftJoin('sewing_pickup_lines as pl', 'pl.cutting_job_bundle_id', '=', 'b.id')
            ->leftJoin('sewing_return_lines as rl', 'rl.sewing_pickup_line_id', '=', 'pl.id')
            ->leftJoin('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->selectRaw('
            b.id,
            b.bundle_no,
            j.code                              as cutting_code,
            j.date                              as cutting_date,
            b.finished_item_id                  as item_id,
            b.wip_qty,
            MAX(r.date)                         as last_sewing_return_date
        ')
            ->groupBy('b.id', 'b.bundle_no', 'j.code', 'j.date', 'b.finished_item_id', 'b.wip_qty')
            ->having('b.wip_qty', '>', 0)
            ->get()
            ->map(function ($row) use ($today) {
                $baseDate = $row->last_sewing_return_date
                ? \Illuminate\Support\Carbon::parse($row->last_sewing_return_date)
                : ($row->cutting_date
                    ? \Illuminate\Support\Carbon::parse($row->cutting_date)
                    : null);

                $row->age_days = $baseDate ? $baseDate->diffInDays($today) : null;

                return $row;
            })
            ->sortByDesc('age_days')
            ->take(30)
            ->values();

        $wipFinTotalFromBundles = $wipFinBundles->sum('wip_qty');

        // =======================================
        // 6. SUMMARY + AGING COMBINED
        // =======================================

        $summary = [
            'wip_cut_total' => $wipCutTotal,
            'wip_sew_total' => $wipSewTotal,
            'wip_fin_total' => $wipFinTotal,
            'bundle_count_wip_cut' => $wipCutBundles->count(),
            'bundle_count_wip_sew' => $wipSewAging->count(),
            'bundle_count_wip_fin' => $wipFinBundles->count(),
        ];

        $agingBundles = collect()
        // CUT
            ->merge(
                $wipCutBundles->map(function ($row) {
                    return (object) [
                        'stage' => 'cut',
                        'bundle_id' => $row->id,
                        'bundle_code' => $row->bundle_no,
                        'cutting_code' => $row->cutting_code,
                        'item_code' => null,
                        'item_name' => null,
                        'lot_code' => null,
                        'lot_item_code' => null,
                        'qty_wip' => $row->wip_cut_qty,
                        'age_days' => $row->age_days,
                    ];
                })
            )
            // SEW
            ->merge(
                $wipSewAging->map(function ($line) {
                    $bundle = $line->bundle;
                    $cutJob = $bundle?->cuttingJob;
                    $lot = $cutJob?->lot;

                    return (object) [
                        'stage' => 'sew',
                        'bundle_id' => $bundle?->id,
                        'bundle_code' => $bundle?->bundle_no,
                        'cutting_code' => $cutJob?->code,
                        'item_code' => $bundle?->finishedItem?->code,
                        'item_name' => $bundle?->finishedItem?->name,
                        'lot_code' => $lot?->code,
                        'lot_item_code' => $lot?->item?->code,
                        'qty_wip' => $line->remaining_qty,
                        'age_days' => $line->age_days,
                    ];
                })
            )
            // FIN
            ->merge(
                $wipFinBundles->map(function ($row) {
                    return (object) [
                        'stage' => 'fin',
                        'bundle_id' => $row->id,
                        'bundle_code' => $row->bundle_no,
                        'cutting_code' => $row->cutting_code,
                        'item_code' => null, // bisa di-join kalau mau
                        'item_name' => null,
                        'lot_code' => null,
                        'lot_item_code' => null,
                        'qty_wip' => $row->wip_qty,
                        'age_days' => $row->age_days,
                    ];
                })
            )
            ->sortByDesc('age_days')
            ->values();

        return view('production.reports.production_flow_dashboard', [
            'summary' => $summary,
            'wipCutItems' => $wipCutItems,
            'wipSewItems' => $wipSewItems,
            'wipFinItems' => $wipFinItems,
            'agingBundles' => $agingBundles,
            'cards' => [
                'wip_cut_stocks' => $wipCutTotal,
                'wip_sew_stocks' => $wipSewTotal,
                'wip_fin_stocks' => $wipFinTotal,
                'wip_sew_from_lines' => $wipSewTotalFromLines,
                'wip_fin_from_bundles' => $wipFinTotalFromBundles,
            ],
        ]);
    }

    /**
     * 8️⃣ Laporan Chain Produksi per Finished Item
     * LOT kain → WIP-CUT → WIP-SEW → WIP-FIN → WH-PRD
     */

}
