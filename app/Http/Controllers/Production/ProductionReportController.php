<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJob;
use App\Models\Employee;
use App\Models\Item;
use App\Models\QcResult;
use App\Models\SewingPickupLine;
use App\Models\SewingReturn;
use App\Models\SewingReturnLine;
use App\Models\Warehouse;
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
     * Source dari SewingReturn + SewingReturnLine.
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
            $sewingRejects->whereDate('sewingReturns.date', '>=', $from);
        }

        if ($to) {
            $sewingRejects->whereDate('sewingReturns.date', '<=', $to);
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

    public function wipSewingAge(Request $request)
    {
        $operatorId = $request->get('operator_id');

        $q = SewingPickupLine::query()
            ->with([
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
                $pickup = $line->sewingPickup;
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

    public function sewingPerItem(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $operatorId = $request->get('operator_id');

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

}
