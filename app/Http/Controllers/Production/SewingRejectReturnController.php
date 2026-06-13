<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SewingRejectReturnController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'operator_id' => $request->get('operator_id'),
            'q' => trim((string) $request->get('q')),
        ];

        $rows = $this->rows($filters);

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $rows->forPage($page, $perPage)->values();
        $rejects = new LengthAwarePaginator(
            $items,
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $operators = Employee::query()
            ->whereIn('id', $rows->pluck('operator_id')->filter()->unique()->values()->all())
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('production.sewing_reject_returns.index', [
            'rejects' => $rejects,
            'operators' => $operators,
            'filters' => $filters,
            'totalRemaining' => (float) $rows->sum('remaining_qty'),
            'totalRows' => (int) $rows->count(),
        ]);
    }

    private function rows(array $filters): \Illuminate\Support\Collection
    {
        $rejSew = Warehouse::query()->where('code', 'REJ-SEW')->first();
        if (!$rejSew) {
            return collect();
        }

        $reworkedSub = DB::table('sewing_return_lines as rw')
            ->join('sewing_returns as srw', 'srw.id', '=', 'rw.sewing_return_id')
            ->whereNull('srw.voided_at')
            ->where('rw.source_type', 'reject_sewing_rework')
            ->whereNotNull('rw.source_reject_return_line_id')
            ->groupBy('rw.source_reject_return_line_id')
            ->selectRaw('rw.source_reject_return_line_id, SUM(COALESCE(rw.qty_ok,0)) as qty_reworked');

        $finishingReworkedSub = DB::table('sewing_return_lines as rw')
            ->join('sewing_returns as srw', 'srw.id', '=', 'rw.sewing_return_id')
            ->whereNull('srw.voided_at')
            ->where('rw.source_type', 'finishing_sewing_rework')
            ->whereNotNull('rw.source_finishing_job_line_id')
            ->groupBy('rw.source_finishing_job_line_id')
            ->selectRaw('rw.source_finishing_job_line_id, SUM(COALESCE(rw.qty_ok,0)) as qty_reworked');

        $rows = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->join('items as it', 'it.id', '=', 'pl.finished_item_id')
            ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
            ->leftJoin('employees as e', 'e.id', '=', 'r.operator_id')
            ->leftJoin('inventory_stocks as st', function ($join) use ($rejSew) {
                $join->on('st.item_id', '=', 'pl.finished_item_id')
                    ->where('st.warehouse_id', '=', $rejSew->id);
            })
            ->leftJoinSub($reworkedSub, 'rw_sum', 'rw_sum.source_reject_return_line_id', '=', 'rl.id')
            ->where('rl.qty_reject', '>', 0)
            ->whereNull('r.voided_at')
            ->when($filters['operator_id'], fn($q, $opId) => $q->where('r.operator_id', $opId))
            ->selectRaw("
                rl.id as line_id,
                'sewing_return' as source_kind,
                rl.qty_reject as qty_reject,
                COALESCE(rw_sum.qty_reworked,0) as qty_reworked,
                COALESCE(st.qty,0) as stock_rej_sew,
                DATE(r.date) as reject_date,
                r.code as reject_code,
                r.operator_id as operator_id,
                COALESCE(e.code,'-') as operator_code,
                COALESCE(e.name,'-') as operator_name,
                pl.id as sewing_pickup_line_id,
                pl.cutting_job_bundle_id as bundle_id,
                it.id as item_id,
                it.code as sku,
                it.name as product_name,
                COALESCE(cat.name,'-') as category,
                COALESCE(NULLIF(rl.notes,''),'-') as notes
            ")
            ->orderByDesc('r.date')
            ->orderByDesc('rl.id')
            ->get()
            ->map(function ($r) {
                $reject = (float) $r->qty_reject;
                $reworked = (float) $r->qty_reworked;
                $stock = max((float) $r->stock_rej_sew, 0.0);
                $r->remaining_qty = min(max($reject - $reworked, 0.0), $stock);
                return $r;
            })
            ->filter(fn($r) => (float) $r->remaining_qty > 0.000001)
            ->values();

        $finishingRows = DB::table('finishing_job_lines as fl')
            ->join('finishing_jobs as f', 'f.id', '=', 'fl.finishing_job_id')
            ->join('items as it', 'it.id', '=', 'fl.item_id')
            ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
            ->leftJoin('employees as e', 'e.id', '=', 'fl.sewing_operator_id')
            ->leftJoin('inventory_stocks as st', function ($join) use ($rejSew) {
                $join->on('st.item_id', '=', 'fl.item_id')
                    ->where('st.warehouse_id', '=', $rejSew->id);
            })
            ->leftJoinSub($finishingReworkedSub, 'frw_sum', 'frw_sum.source_finishing_job_line_id', '=', 'fl.id')
            ->where('fl.qty_reject', '>', 0)
            ->where('fl.reject_cause', 'sewing')
            ->where('f.status', 'posted')
            ->when($filters['operator_id'], fn($q, $opId) => $q->where('fl.sewing_operator_id', $opId))
            ->selectRaw("
                fl.id as line_id,
                'finishing' as source_kind,
                fl.qty_reject as qty_reject,
                COALESCE(frw_sum.qty_reworked,0) as qty_reworked,
                COALESCE(st.qty,0) as stock_rej_sew,
                DATE(f.date) as reject_date,
                f.code as reject_code,
                fl.sewing_operator_id as operator_id,
                COALESCE(e.code,'-') as operator_code,
                COALESCE(e.name,'-') as operator_name,
                NULL as sewing_pickup_line_id,
                fl.bundle_id as bundle_id,
                it.id as item_id,
                it.code as sku,
                it.name as product_name,
                COALESCE(cat.name,'-') as category,
                COALESCE(NULLIF(fl.reject_notes,''), 'Dari finishing') as notes
            ")
            ->orderByDesc('f.date')
            ->orderByDesc('fl.id')
            ->get()
            ->map(function ($r) {
                $reject = (float) $r->qty_reject;
                $reworked = (float) $r->qty_reworked;
                $stock = max((float) $r->stock_rej_sew, 0.0);
                $r->remaining_qty = min(max($reject - $reworked, 0.0), $stock);
                return $r;
            })
            ->filter(fn($r) => (float) $r->remaining_qty > 0.000001)
            ->values();

        $rows = $rows->concat($finishingRows)->values();

        if ($filters['q']) {
            $needle = mb_strtolower($filters['q']);
            $rows = $rows->filter(function ($r) use ($needle) {
                $hay = mb_strtolower(trim($r->sku . ' ' . $r->product_name . ' ' . $r->category . ' ' . $r->operator_code . ' ' . $r->operator_name . ' ' . $r->notes));
                return str_contains($hay, $needle);
            })->values();
        }

        return $rows;
    }
}
