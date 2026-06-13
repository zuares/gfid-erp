<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\SewingPickup;
use App\Models\SewingPickupLine;
use App\Models\SewingReturn;
use App\Models\SewingReturnLine;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Services\Payroll\PieceRateService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SewingReturnController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    /* ============================================================
     * INDEX
     * ============================================================
     */
    public function index(Request $request): View
    {
        $filters = [
            'status' => $request->get('status'),
            'operator_id' => $request->get('operator_id'),
            'from_date' => $request->get('from_date'),
            'to_date' => $request->get('to_date'),
            'q' => $request->get('q'),
        ];

        $query = SewingReturn::query()
            ->with([
                'operator',
                'warehouse', // asal (WIP-SEW)
                'destinationWarehouse', // tujuan pasca jahit (WIP-FIN)
                'pickup',
                'lines.sewingPickupLine',
                // ✅ untuk chip detail barang di index
                'lines.sewingPickupLine.finishedItem:id,code,name',
            ])
            ->when($filters['status'], fn($q, $status) => $q->where('status', $status))
            ->when($filters['operator_id'], fn($q, $opId) => $q->where('operator_id', $opId))
            ->when($filters['from_date'], fn($q, $from) => $q->whereDate('date', '>=', $from))
            ->when($filters['to_date'], fn($q, $to) => $q->whereDate('date', '<=', $to))
            ->when($filters['q'], function ($q, $search) {
                $search = trim((string) $search);
                $q->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhereHas('pickup', fn($qq) => $qq->where('code', 'like', "%{$search}%"))
                        ->orWhereHas('operator', function ($qq) use ($search) {
                            $qq->where('code', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('date')
            ->orderByDesc('id');

        $returns = $query->paginate(20)->withQueryString();
        $operators = Employee::orderBy('code')->get(['id', 'code', 'name']);

        return view('production.sewing_returns.index', [
            'returns' => $returns,
            'operators' => $operators,
            'filters' => $filters,
        ]);
    }

    /* ============================================================
     * SHOW
     * ============================================================
     */
    public function show(SewingReturn $return): View
    {
        $return->load([
            'warehouse',
            'destinationWarehouse',
            'operator',
            'pickup.operator',
            'lines.sewingPickupLine.sewingPickup',
            'lines.sewingPickupLine.bundle.finishedItem',
            'lines.sewingPickupLine.bundle.cuttingJob.lot.item',
        ]);

        $lines = $return->lines ?? collect();

        $totalPickup = (float) $lines->sum(function ($line) {
            $pl = $line->sewingPickupLine;
            return (float) ($pl->qty_bundle ?? 0);
        });

        $totalOk = (float) $lines->sum('qty_ok');
        $totalReject = (float) $lines->sum('qty_reject');
        $totalProcessed = $totalOk + $totalReject;

        $okPercent = $totalProcessed > 0 ? round(($totalOk / $totalProcessed) * 100, 1) : 0.0;
        $rejectPercent = $totalProcessed > 0 ? round(($totalReject / $totalProcessed) * 100, 1) : 0.0;

        $uniquePickupLines = $lines->pluck('sewingPickupLine')
            ->filter()
            ->keyBy(fn($pl) => (int) $pl->id);

        $totalDirectPick = (float) $uniquePickupLines->sum(fn($pl) => (float) ($pl->qty_direct_picked ?? 0));
        $totalProgressAdjusted = (float) $uniquePickupLines->sum(fn($pl) => (float) ($pl->qty_progress_adjusted ?? 0));

        $totalRemaining = (float) $uniquePickupLines->sum(function ($pl) {
            $qtyBundle = (float) ($pl->qty_bundle ?? 0);
            $returnedOk = (float) ($pl->qty_returned_ok ?? 0);
            $returnedRej = (float) ($pl->qty_returned_reject ?? 0);
            $directPick = (float) ($pl->qty_direct_picked ?? 0);
            $progressAdj = (float) ($pl->qty_progress_adjusted ?? 0);

            return max($qtyBundle - ($returnedOk + $returnedRej + $directPick + $progressAdj), 0);
        });

        return view('production.sewing_returns.show', [
            'return' => $return,
            'totalPickup' => $totalPickup,
            'totalOk' => $totalOk,
            'totalReject' => $totalReject,
            'totalProcessed' => $totalProcessed,
            'okPercent' => $okPercent,
            'rejectPercent' => $rejectPercent,
            'totalRemaining' => $totalRemaining,
            'totalDirectPick' => $totalDirectPick,
            'totalProgressAdjusted' => $totalProgressAdjusted,
        ]);
    }

    /* ============================================================
     * CREATE
     * ============================================================
     */
    public function create(Request $request): View
    {
        $operatorId = $request->integer('operator_id') ?: null;
        $isRejectReworkMode = $request->get('source') === 'reject-sewing' || $request->filled('reject_return_line_id') || $request->filled('source_finishing_job_line_id');
        $selectedRejectLineId = $request->integer('reject_return_line_id') ?: null;
        $selectedFinishingLineId = $request->integer('source_finishing_job_line_id') ?: null;
        if ($selectedRejectLineId && !$selectedFinishingLineId) {
            $hasSewingRejectLine = DB::table('sewing_return_lines')->whereKey($selectedRejectLineId)->exists();
            if (!$hasSewingRejectLine && DB::table('finishing_job_lines')->whereKey($selectedRejectLineId)->exists()) {
                $selectedFinishingLineId = $selectedRejectLineId;
                $selectedRejectLineId = null;
            }
        }

        $pickupDate = $request->input('pickup_date');
        $pickupDate = is_string($pickupDate) ? trim($pickupDate) : null;
        if ($pickupDate === '') {
            $pickupDate = null;
        }

        $role = strtolower((string) (auth()->user()->role ?? ''));

        $wipSewWarehouse = Warehouse::query()
            ->whereIn('code', ['WIP-SEW', 'WH-SEWING'])
            ->first();

        // Sewing Return selalu masuk WIP-FIN. WH-PRD hanya setelah finishing/QC final.
        $canChooseDestination = false;

        // ambil gudang tujuan pasca jahit
        $destinationWarehouses = Warehouse::query()
            ->whereIn('code', ['WIP-FIN'])
            ->get(['id', 'code', 'name']);

        $wipFinId = (int) optional($destinationWarehouses->firstWhere('code', 'WIP-FIN'))->id;
        $defaultDestWarehouseId = $wipFinId;

        $operatorIdsFromSewingReturns = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->where('rl.qty_reject', '>', 0)
            ->whereNull('r.voided_at')
            ->whereNotNull('r.operator_id')
            ->distinct()
            ->pluck('r.operator_id')
            ->map(fn($v) => (int) $v)
            ->filter()
            ->values()
            ->all();

        $operatorIdsFromFinishing = DB::table('finishing_job_lines as fl')
            ->join('finishing_jobs as f', 'f.id', '=', 'fl.finishing_job_id')
            ->where('fl.qty_reject', '>', 0)
            ->where('fl.reject_cause', 'sewing')
            ->where('f.status', 'posted')
            ->whereNotNull('fl.sewing_operator_id')
            ->distinct()
            ->pluck('fl.sewing_operator_id')
            ->map(fn($v) => (int) $v)
            ->filter()
            ->values()
            ->all();

        $operatorIdsFromPickups = SewingPickup::query()
            ->whereNull('voided_at')
            ->whereNotNull('operator_id')
            ->distinct()
            ->pluck('operator_id')
            ->map(fn($v) => (int) $v)
            ->filter()
            ->values()
            ->all();

        $operatorIds = collect($isRejectReworkMode ? [] : $operatorIdsFromPickups)
            ->merge($operatorIdsFromSewingReturns)
            ->merge($operatorIdsFromFinishing)
            ->unique()
            ->values()
            ->all();

        $operators = Employee::query()
            ->whereIn('id', $operatorIds)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $lines = collect();
        $wipStockByItemId = [];

        if ($isRejectReworkMode) {
            $rejectWarehouse = Warehouse::query()->where('code', 'REJ-SEW')->first();

            $lines = $this->buildRejectReworkLines($operatorId, $selectedRejectLineId, $rejectWarehouse?->id, $selectedFinishingLineId);

            return view('production.sewing_returns.create', compact(
                'operators',
                'operatorId',
                'pickupDate',
                'lines',
                'wipSewWarehouse',
                'wipStockByItemId',
                'destinationWarehouses',
                'defaultDestWarehouseId',
                'canChooseDestination',
                'isRejectReworkMode',
            ));
        }

        if (!$wipSewWarehouse) {
            return view('production.sewing_returns.create', compact(
                'operators',
                'operatorId',
                'pickupDate',
                'lines',
                'wipSewWarehouse',
                'wipStockByItemId',
                'destinationWarehouses',
                'defaultDestWarehouseId',
                'canChooseDestination',
                'isRejectReworkMode',
            ));
        }

        $q = SewingPickupLine::query()
            ->whereNull('voided_at')
            ->whereHas('sewingPickup', function ($qq) use ($operatorId, $pickupDate) {
                $qq->whereNull('voided_at');
                if ($operatorId) {
                    $qq->where('operator_id', $operatorId);
                }

                if ($pickupDate) {
                    $qq->whereDate('date', $pickupDate);
                }

            })
            ->with([
                'sewingPickup:id,code,date,operator_id',
                'sewingPickup.operator:id,code,name',
                'finishedItem:id,code,name',
                'bundle.cuttingJob.lot',
            ])
            ->orderByDesc('id');

        $lines = $q->get();

        // compute remaining (include progress_adjusted) + collect itemIds
        $itemIds = [];
        $lines = $lines->map(function ($l) use (&$itemIds) {
            $qtyBundle = (float) ($l->qty_bundle ?? 0);
            $returnedOk = (float) ($l->qty_returned_ok ?? 0);
            $returnedRj = (float) ($l->qty_returned_reject ?? 0);
            $directPick = (float) ($l->qty_direct_picked ?? 0);
            $progressAdj = (float) ($l->qty_progress_adjusted ?? 0);

            $l->remaining_qty = max($qtyBundle - ($returnedOk + $returnedRj + $directPick + $progressAdj), 0);

            $itemId = (int) ($l->finished_item_id ?? 0);
            if ($itemId > 0) {
                $itemIds[] = $itemId;
            }

            return $l;
        })->filter(fn($l) => (float) $l->remaining_qty > 0.000001)->values();

        $itemIds = collect($itemIds)->unique()->values()->all();

        if (!empty($itemIds)) {
            $wipStockByItemId = InventoryStock::query()
                ->where('warehouse_id', $wipSewWarehouse->id)
                ->whereIn('item_id', $itemIds)
                ->pluck('qty', 'item_id')
                ->map(fn($v) => (float) $v)
                ->toArray();
        }

        $lines = $lines->map(function ($l) use ($wipStockByItemId) {
            $itemId = (int) ($l->finished_item_id ?? 0);
            $l->wip_stock = (float) ($wipStockByItemId[$itemId] ?? 0);
            return $l;
        })->filter(fn($l) => (float) $l->wip_stock > 0.000001)->values();

        $rejectWarehouse = Warehouse::query()->where('code', 'REJ-SEW')->first();
        $rejectReworkLines = $this->buildRejectReworkLines($operatorId, null, $rejectWarehouse?->id, null);
        $lines = $lines->concat($rejectReworkLines)->values();

        return view('production.sewing_returns.create', compact(
            'operators',
            'operatorId',
            'pickupDate',
            'lines',
            'wipSewWarehouse',
            'wipStockByItemId',
            'destinationWarehouses',
            'defaultDestWarehouseId',
            'canChooseDestination',
            'isRejectReworkMode',
        ));
    }

    private function buildRejectReworkLines(?int $operatorId, ?int $selectedRejectLineId, ?int $rejectWarehouseId, ?int $selectedFinishingLineId = null): \Illuminate\Support\Collection
    {
        if (!$rejectWarehouseId) {
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
            ->leftJoin('sewing_pickups as sp', 'sp.id', '=', 'pl.sewing_pickup_id')
            ->leftJoin('inventory_stocks as st', function ($join) use ($rejectWarehouseId) {
                $join->on('st.item_id', '=', 'pl.finished_item_id')
                    ->where('st.warehouse_id', '=', $rejectWarehouseId);
            })
            ->leftJoinSub($reworkedSub, 'rw_sum', 'rw_sum.source_reject_return_line_id', '=', 'rl.id')
            ->where('rl.qty_reject', '>', 0)
            ->whereNull('r.voided_at')
            ->when($operatorId, fn($q) => $q->where('r.operator_id', $operatorId))
            ->when($selectedRejectLineId, fn($q) => $q->where('rl.id', $selectedRejectLineId))
            ->selectRaw("
                rl.id as source_reject_return_line_id,
                rl.notes as source_notes,
                rl.qty_reject,
                COALESCE(rw_sum.qty_reworked,0) as qty_reworked,
                COALESCE(st.qty,0) as stock_rej_sew,
                DATE(r.date) as reject_date,
                r.code as reject_code,
                r.operator_id,
                COALESCE(e.code,'') as operator_code,
                COALESCE(e.name,'') as operator_name,
                pl.id as sewing_pickup_line_id,
                pl.sewing_pickup_id,
                pl.cutting_job_bundle_id,
                pl.finished_item_id,
                it.code as item_code,
                it.name as item_name,
                COALESCE(cat.name,'-') as category_name,
                sp.code as pickup_code,
                sp.date as pickup_date
            ")
            ->orderByDesc('r.date')
            ->orderByDesc('rl.id')
            ->get();

        $normalRows = $rows->map(function ($r) {
            $remaining = min(
                max((float) $r->qty_reject - (float) $r->qty_reworked, 0.0),
                max((float) $r->stock_rej_sew, 0.0)
            );

            if ($remaining <= 0.000001) {
                return null;
            }

            $line = new \stdClass();
            $line->id = (int) $r->sewing_pickup_line_id;
            $line->sewing_pickup_id = (int) $r->sewing_pickup_id;
            $line->cutting_job_bundle_id = (int) $r->cutting_job_bundle_id;
            $line->finished_item_id = (int) $r->finished_item_id;
            $line->remaining_qty = $remaining;
            $line->wip_stock = $remaining;
            $line->source_reject_return_line_id = (int) $r->source_reject_return_line_id;
            $line->reject_code = $r->reject_code;
            $line->reject_date = $r->reject_date;
            $line->source_notes = $r->source_notes;

            $line->finishedItem = (object) [
                'id' => (int) $r->finished_item_id,
                'code' => $r->item_code,
                'name' => $r->item_name,
            ];

            $line->sewingPickup = (object) [
                'id' => (int) $r->sewing_pickup_id,
                'code' => $r->pickup_code,
                'date' => $r->pickup_date,
                'operator_id' => (int) $r->operator_id,
                'operator' => (object) [
                    'id' => (int) $r->operator_id,
                    'code' => $r->operator_code,
                    'name' => $r->operator_name,
                ],
            ];

            return $line;
        })->filter()->values();

        $finishingRows = DB::table('finishing_job_lines as fl')
            ->join('finishing_jobs as f', 'f.id', '=', 'fl.finishing_job_id')
            ->join('items as it', 'it.id', '=', 'fl.item_id')
            ->join('cutting_job_bundles as b', 'b.id', '=', 'fl.bundle_id')
            ->join('sewing_pickup_lines as pl', 'pl.cutting_job_bundle_id', '=', 'b.id')
            ->join('sewing_pickups as sp', 'sp.id', '=', 'pl.sewing_pickup_id')
            ->leftJoin('employees as e', 'e.id', '=', 'fl.sewing_operator_id')
            ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
            ->leftJoin('inventory_stocks as st', function ($join) use ($rejectWarehouseId) {
                $join->on('st.item_id', '=', 'fl.item_id')
                    ->where('st.warehouse_id', '=', $rejectWarehouseId);
            })
            ->leftJoinSub($finishingReworkedSub, 'frw_sum', 'frw_sum.source_finishing_job_line_id', '=', 'fl.id')
            ->where('fl.qty_reject', '>', 0)
            ->where('fl.reject_cause', 'sewing')
            ->where('f.status', 'posted')
            ->when($operatorId, fn($q) => $q->where('fl.sewing_operator_id', $operatorId))
            ->when($selectedFinishingLineId, fn($q) => $q->where('fl.id', $selectedFinishingLineId))
            ->selectRaw("
                fl.id as source_finishing_job_line_id,
                fl.reject_notes as source_notes,
                fl.qty_reject,
                COALESCE(frw_sum.qty_reworked,0) as qty_reworked,
                COALESCE(st.qty,0) as stock_rej_sew,
                DATE(f.date) as reject_date,
                f.code as reject_code,
                fl.sewing_operator_id as operator_id,
                COALESCE(e.code,'') as operator_code,
                COALESCE(e.name,'') as operator_name,
                MAX(pl.id) as sewing_pickup_line_id,
                MAX(pl.sewing_pickup_id) as sewing_pickup_id,
                fl.bundle_id as cutting_job_bundle_id,
                fl.item_id as finished_item_id,
                it.code as item_code,
                it.name as item_name,
                COALESCE(cat.name,'-') as category_name,
                MAX(sp.code) as pickup_code,
                MAX(sp.date) as pickup_date
            ")
            ->groupBy('fl.id', 'fl.reject_notes', 'fl.qty_reject', 'frw_sum.qty_reworked', 'st.qty', 'f.date', 'f.code', 'fl.sewing_operator_id', 'e.code', 'e.name', 'fl.bundle_id', 'fl.item_id', 'it.code', 'it.name', 'cat.name')
            ->orderByDesc('f.date')
            ->orderByDesc('fl.id')
            ->get()
            ->map(function ($r) {
                $remaining = min(
                    max((float) $r->qty_reject - (float) $r->qty_reworked, 0.0),
                    max((float) $r->stock_rej_sew, 0.0)
                );

                if ($remaining <= 0.000001) {
                    return null;
                }

                $line = new \stdClass();
                $line->id = (int) $r->sewing_pickup_line_id;
                $line->sewing_pickup_id = (int) $r->sewing_pickup_id;
                $line->cutting_job_bundle_id = (int) $r->cutting_job_bundle_id;
                $line->finished_item_id = (int) $r->finished_item_id;
                $line->remaining_qty = $remaining;
                $line->wip_stock = $remaining;
                $line->source_reject_return_line_id = null;
                $line->source_finishing_job_line_id = (int) $r->source_finishing_job_line_id;
                $line->reject_code = $r->reject_code;
                $line->reject_date = $r->reject_date;
                $line->source_notes = $r->source_notes;

                $line->finishedItem = (object) [
                    'id' => (int) $r->finished_item_id,
                    'code' => $r->item_code,
                    'name' => $r->item_name,
                ];

                $line->sewingPickup = (object) [
                    'id' => (int) $r->sewing_pickup_id,
                    'code' => $r->pickup_code,
                    'date' => $r->pickup_date,
                    'operator_id' => (int) $r->operator_id,
                    'operator' => (object) [
                        'id' => (int) $r->operator_id,
                        'code' => $r->operator_code,
                        'name' => $r->operator_name,
                    ],
                ];

                return $line;
            })
            ->filter()
            ->values();

        return $normalRows->concat($finishingRows)->values();
    }

    /* ============================================================
     * STORE
     * ============================================================
     */
    public function store(Request $request): RedirectResponse
    {
        $normalizedResults = collect($request->input('results', []))
            ->map(function ($row) {
                if (is_array($row)) {
                    foreach (['source_reject_return_line_id', 'source_finishing_job_line_id'] as $key) {
                        if ((int) ($row[$key] ?? 0) <= 0) {
                            $row[$key] = null;
                        }
                    }
                }

                return $row;
            })
            ->all();

        $request->merge(['results' => $normalizedResults]);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'operator_id' => ['required', 'integer', 'exists:employees,id'],

            // selalu ada nilai (owner select / non-owner hidden)
            'destination_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],

            'results' => ['required', 'array', 'min:1'],
            'results.*.sewing_pickup_line_id' => ['required', 'integer', 'exists:sewing_pickup_lines,id'],
            'results.*.qty_ok' => ['nullable', 'numeric', 'min:0'],
            'results.*.qty_reject' => ['nullable', 'numeric', 'min:0'],
            'results.*.notes' => ['nullable', 'string', 'max:500'],
            'results.*.source_reject_return_line_id' => ['nullable', 'integer', 'exists:sewing_return_lines,id'],
            'results.*.source_finishing_job_line_id' => ['nullable', 'integer', 'exists:finishing_job_lines,id'],
        ]);

        $date = Carbon::parse($validated['date'])->toDateString();
        $operatorId = (int) $validated['operator_id'];

        return DB::transaction(function () use ($validated, $date, $operatorId): RedirectResponse {

            $wipSewWarehouse = Warehouse::query()
                ->whereIn('code', ['WIP-SEW', 'WH-SEWING'])
                ->first();

            if (!$wipSewWarehouse) {
                throw ValidationException::withMessages(['results' => 'Gudang WIP-SEW / WH-SEWING belum ada.']);
            }

            // Hasil jahit OK masuk WIP-FIN dulu. WH-PRD hanya dari proses finishing/QC final.
            $wipFin = Warehouse::query()->where('code', 'WIP-FIN')->first();

            if (!$wipFin) {
                throw ValidationException::withMessages(['destination_warehouse_id' => 'Gudang WIP-FIN belum ada.']);
            }

            $requestedDestId = (int) $validated['destination_warehouse_id'];
            if ($requestedDestId !== (int) $wipFin->id) {
                throw ValidationException::withMessages(['destination_warehouse_id' => 'Sewing Return hanya boleh masuk ke WIP-FIN.']);
            }

            $destWarehouse = $wipFin;

            $rejectWarehouse = Warehouse::query()->where('code', 'REJ-SEW')->first();

            // normalize results
            $rawResults = collect($validated['results'] ?? [])
                ->map(function ($r) {
                    $ok = (float) ($r['qty_ok'] ?? 0);
                    $rj = (float) ($r['qty_reject'] ?? 0);

                    return [
                        'sewing_pickup_line_id' => (int) ($r['sewing_pickup_line_id'] ?? 0),
                        'qty_ok' => $ok,
                        'qty_reject' => $rj,
                        'notes' => trim((string) ($r['notes'] ?? '')),
                        'source_reject_return_line_id' => (int) ($r['source_reject_return_line_id'] ?? 0),
                        'source_finishing_job_line_id' => (int) ($r['source_finishing_job_line_id'] ?? 0),
                        'total' => $ok + $rj,
                    ];
                })
                ->filter(fn($r) => (float) ($r['total'] ?? 0) > 0.000001)
                ->values();

            if ($rawResults->isEmpty()) {
                throw ValidationException::withMessages(['results' => 'Minimal isi 1 baris (OK / Reject).']);
            }

            // lock pickup lines
            $lineIds = $rawResults->pluck('sewing_pickup_line_id')->unique()->values()->all();

            $pickupLines = SewingPickupLine::query()
                ->whereIn('id', $lineIds)
                ->whereNull('voided_at')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($pickupLines->count() !== count($lineIds)) {
                throw ValidationException::withMessages(['results' => 'Ada pickup line yang tidak valid / sudah void.']);
            }

            $rejectReworkRows = $rawResults
                ->filter(fn($r) => (int) ($r['source_reject_return_line_id'] ?? 0) > 0 || (int) ($r['source_finishing_job_line_id'] ?? 0) > 0)
                ->values();

            $normalRows = $rawResults
                ->filter(fn($r) => (int) ($r['source_reject_return_line_id'] ?? 0) <= 0 && (int) ($r['source_finishing_job_line_id'] ?? 0) <= 0)
                ->values();

            // touched pickups
            $touchedPickupIds = $pickupLines->pluck('sewing_pickup_id')
                ->filter()->unique()->values()->all();

            $pickupsMap = SewingPickup::query()
                ->whereIn('id', $touchedPickupIds)
                ->whereNull('voided_at')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // touched bundles
            $bundleIds = $pickupLines->pluck('cutting_job_bundle_id')
                ->filter()->unique()->values()->all();

            $bundlesMap = CuttingJobBundle::query()
                ->whereIn('id', $bundleIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // lock stock WIP-SEW per item
            $itemIds = $pickupLines->pluck('finished_item_id')
                ->filter()->unique()->values()->all();

            $stocksWipSew = InventoryStock::query()
                ->where('warehouse_id', $wipSewWarehouse->id)
                ->whereIn('item_id', $itemIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('item_id');

            $availableByItem = [];
            foreach ($itemIds as $itemId) {
                $availableByItem[$itemId] = (float) ($stocksWipSew[$itemId]->qty ?? 0);
            }

            $rejectReworkDetails = collect();

            if ($rejectReworkRows->isNotEmpty()) {
                if (!$rejectWarehouse) {
                    throw ValidationException::withMessages(['results' => 'Gudang REJ-SEW belum ada.']);
                }

                $sourceLineIds = $rejectReworkRows->pluck('source_reject_return_line_id')->filter()->unique()->values()->all();
                $sourceFinishingLineIds = $rejectReworkRows->pluck('source_finishing_job_line_id')->filter()->unique()->values()->all();

                $reworkedSub = DB::table('sewing_return_lines as rw')
                    ->join('sewing_returns as srw', 'srw.id', '=', 'rw.sewing_return_id')
                    ->whereNull('srw.voided_at')
                    ->where('rw.source_type', 'reject_sewing_rework')
                    ->whereNotNull('rw.source_reject_return_line_id')
                    ->when($sourceLineIds, fn($q) => $q->whereIn('rw.source_reject_return_line_id', $sourceLineIds), fn($q) => $q->whereRaw('1=0'))
                    ->groupBy('rw.source_reject_return_line_id')
                    ->selectRaw('rw.source_reject_return_line_id, SUM(COALESCE(rw.qty_ok,0)) as qty_reworked');

                $rejectReworkDetails = DB::table('sewing_return_lines as rl')
                    ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
                    ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
                    ->leftJoinSub($reworkedSub, 'rw_sum', 'rw_sum.source_reject_return_line_id', '=', 'rl.id')
                    ->when($sourceLineIds, fn($q) => $q->whereIn('rl.id', $sourceLineIds), fn($q) => $q->whereRaw('1=0'))
                    ->whereNull('r.voided_at')
                    ->selectRaw('rl.id, rl.qty_reject, COALESCE(rw_sum.qty_reworked,0) as qty_reworked, pl.finished_item_id as item_id, pl.cutting_job_bundle_id as bundle_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if (!empty($sourceFinishingLineIds)) {
                    $finishingReworkedSub = DB::table('sewing_return_lines as rw')
                        ->join('sewing_returns as srw', 'srw.id', '=', 'rw.sewing_return_id')
                        ->whereNull('srw.voided_at')
                        ->where('rw.source_type', 'finishing_sewing_rework')
                        ->whereNotNull('rw.source_finishing_job_line_id')
                        ->whereIn('rw.source_finishing_job_line_id', $sourceFinishingLineIds)
                        ->groupBy('rw.source_finishing_job_line_id')
                        ->selectRaw('rw.source_finishing_job_line_id, SUM(COALESCE(rw.qty_ok,0)) as qty_reworked');

                    $finishingDetails = DB::table('finishing_job_lines as fl')
                        ->leftJoinSub($finishingReworkedSub, 'frw_sum', 'frw_sum.source_finishing_job_line_id', '=', 'fl.id')
                        ->whereIn('fl.id', $sourceFinishingLineIds)
                        ->where('fl.reject_cause', 'sewing')
                        ->selectRaw('fl.id, fl.qty_reject, COALESCE(frw_sum.qty_reworked,0) as qty_reworked, fl.item_id as item_id, fl.bundle_id as bundle_id')
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    $rejectReworkDetails = $rejectReworkDetails->union($finishingDetails->mapWithKeys(fn($row, $key) => ['finishing:' . $key => $row]));
                }
            }

            // 1) clamp global per item: need <= stok WIP-SEW
            $requestedByItem = [];
            foreach ($normalRows as $r) {
                $pl = $pickupLines->get((int) $r['sewing_pickup_line_id']);
                $itemId = (int) ($pl->finished_item_id ?? 0);
                $requestedByItem[$itemId] = ($requestedByItem[$itemId] ?? 0) + (float) $r['total'];
            }

            foreach ($requestedByItem as $itemId => $need) {
                $avail = (float) ($availableByItem[$itemId] ?? 0);
                if ($need > $avail + 0.000001) {
                    throw ValidationException::withMessages([
                        'results' => "Stok WIP-SEW tidak cukup untuk item #{$itemId}. Butuh {$need}, stok {$avail}.",
                    ]);
                }
            }

            // 2) clamp per pickup line: total <= remaining (include direct_picked + progress_adjusted)
            foreach ($normalRows as $r) {
                $pl = $pickupLines->get((int) $r['sewing_pickup_line_id']);

                $qtyBundle = (float) ($pl->qty_bundle ?? 0);
                $returnedOk = (float) ($pl->qty_returned_ok ?? 0);
                $returnedRej = (float) ($pl->qty_returned_reject ?? 0);
                $directPick = (float) ($pl->qty_direct_picked ?? 0);
                $progressAdj = (float) ($pl->qty_progress_adjusted ?? 0);

                $remainingPickup = max($qtyBundle - ($returnedOk + $returnedRej + $directPick + $progressAdj), 0);

                if ((float) $r['total'] > $remainingPickup + 0.000001) {
                    throw ValidationException::withMessages([
                        'results' => "Qty OK+Reject melebihi sisa pickup (line #{$pl->id}). Sisa: {$remainingPickup}.",
                    ]);
                }
            }

            foreach ($rejectReworkRows as $r) {
                $sourceLineId = (int) $r['source_reject_return_line_id'];
                $sourceFinishingLineId = (int) $r['source_finishing_job_line_id'];
                $detailKey = $sourceFinishingLineId > 0 ? 'finishing:' . $sourceFinishingLineId : $sourceLineId;
                $detail = $rejectReworkDetails->get($detailKey);
                if (!$detail) {
                    throw ValidationException::withMessages(['results' => 'Baris reject tidak valid / sudah void.']);
                }

                if ((float) $r['qty_reject'] > 0.000001) {
                    throw ValidationException::withMessages(['results' => 'Setor ulang reject hanya boleh mengisi kolom Di setor.']);
                }

                $remainingReject = max((float) $detail->qty_reject - (float) $detail->qty_reworked, 0.0);
                if ((float) $r['qty_ok'] > $remainingReject + 0.000001) {
                    throw ValidationException::withMessages(['results' => "Qty setor ulang melebihi sisa reject. Sisa: {$remainingReject}."]);
                }
            }

            // header pickup id (kalau single pickup)
            $headerPickupId = (count($touchedPickupIds) === 1) ? (int) $touchedPickupIds[0] : null;
            $sourceWarehouseId = ($normalRows->isEmpty() && $rejectReworkRows->isNotEmpty() && $rejectWarehouse)
                ? (int) $rejectWarehouse->id
                : (int) $wipSewWarehouse->id;

            $sewingReturn = SewingReturn::create([
                'code' => method_exists(SewingReturn::class, 'generateCode')
                ? SewingReturn::generateCode($date)
                : ('SR-' . Carbon::parse($date)->format('Ymd') . '-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT)),
                'date' => $date,
                'warehouse_id' => $sourceWarehouseId,
                'destination_warehouse_id' => (int) $destWarehouse->id,
                'pickup_id' => $headerPickupId,
                'operator_id' => $operatorId,
                'created_by_user_id' => auth()->id(),
                'notes' => null,
                'status' => (new SewingReturn())->isFillable('status') ? 'posted' : null,
            ]);

            // create lines + update pickup line counters
            foreach ($rawResults as $r) {
                $pl = $pickupLines->get((int) $r['sewing_pickup_line_id']);

                SewingReturnLine::create([
                    'sewing_return_id' => $sewingReturn->id,
                    'sewing_pickup_line_id' => $pl->id,
                    'qty_ok' => (float) $r['qty_ok'],
                    'qty_reject' => (float) $r['qty_reject'],
                    'notes' => $r['notes'] !== '' ? $r['notes'] : null,
                    'finished_qty' => (int) round((float) $r['qty_ok']),
                    'source_type' => ((int) ($r['source_finishing_job_line_id'] ?? 0) > 0)
                        ? 'finishing_sewing_rework'
                        : (((int) ($r['source_reject_return_line_id'] ?? 0) > 0) ? 'reject_sewing_rework' : null),
                    'source_reject_return_line_id' => ((int) ($r['source_reject_return_line_id'] ?? 0) > 0) ? (int) $r['source_reject_return_line_id'] : null,
                    'source_finishing_job_line_id' => ((int) ($r['source_finishing_job_line_id'] ?? 0) > 0) ? (int) $r['source_finishing_job_line_id'] : null,
                ]);

                if ((int) ($r['source_reject_return_line_id'] ?? 0) <= 0 && (int) ($r['source_finishing_job_line_id'] ?? 0) <= 0) {
                    $pl->qty_returned_ok = (float) ($pl->qty_returned_ok ?? 0) + (float) $r['qty_ok'];
                    $pl->qty_returned_reject = (float) ($pl->qty_returned_reject ?? 0) + (float) $r['qty_reject'];
                    $pl->save();
                }
            }

            // inventory + bundle tracker
            $okByBundle = [];

            foreach ($rawResults as $r) {
                $pl = $pickupLines->get((int) $r['sewing_pickup_line_id']);

                $bundleId = (int) $pl->cutting_job_bundle_id;
                $itemId = (int) $pl->finished_item_id;

                $qtyOk = (float) $r['qty_ok'];
                $qtyRj = (float) $r['qty_reject'];

                $sourceRejectLineId = (int) ($r['source_reject_return_line_id'] ?? 0);
                $sourceFinishingLineId = (int) ($r['source_finishing_job_line_id'] ?? 0);

                // Reject jahit: OUT WIP-SEW lalu IN REJ-SEW.
                if ($qtyRj > 0.000001) {
                    if (!$rejectWarehouse) {
                        throw ValidationException::withMessages([
                            'results' => 'Gudang REJ-SEW belum ada. Reject jahit harus masuk gudang Reject Sewing.',
                        ]);
                    }

                    $this->inventory->stockOut(
                        warehouseId: $wipSewWarehouse->id,
                        itemId: $itemId,
                        qty: $qtyRj,
                        date: $date,
                        sourceType: 'sewing_return_reject',
                        sourceId: $sewingReturn->id,
                        notes: "Sewing Return {$sewingReturn->code} (RJ)",
                        allowNegative: false,
                        lotId: null,
                        unitCostOverride: null,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );

                    $this->inventory->stockIn(
                        warehouseId: $rejectWarehouse->id,
                        itemId: $itemId,
                        qty: $qtyRj,
                        date: $date,
                        sourceType: 'sewing_return_reject',
                        sourceId: $sewingReturn->id,
                        notes: "Sewing Return {$sewingReturn->code} (RJ) → REJ-SEW",
                        lotId: null,
                        unitCost: null,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );
                }

                if (($sourceRejectLineId > 0 || $sourceFinishingLineId > 0) && $qtyOk > 0.000001) {
                    $unitCostRejSew = (float) $this->inventory->getItemIncomingUnitCost(
                        warehouseId: $rejectWarehouse->id,
                        itemId: $itemId
                    );

                    $rate = (float) app(PieceRateService::class)->requireRatePerPcs(
                        module: 'sewing',
                        employeeId: $operatorId,
                        itemId: $itemId,
                        date: $date
                    );

                    $unitCostWithLabor = $unitCostRejSew + $rate;

                    $this->inventory->stockOut(
                        warehouseId: $rejectWarehouse->id,
                        itemId: $itemId,
                        qty: $qtyOk,
                        date: $date,
                        sourceType: 'sewing_reject_rework_ok',
                        sourceId: $sourceFinishingLineId > 0 ? $sourceFinishingLineId : $sourceRejectLineId,
                        notes: "Setor ulang reject {$sewingReturn->code} OUT REJ-SEW",
                        allowNegative: false,
                        lotId: null,
                        unitCostOverride: null,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );

                    $this->inventory->stockIn(
                        warehouseId: $destWarehouse->id,
                        itemId: $itemId,
                        qty: $qtyOk,
                        date: $date,
                        sourceType: 'sewing_reject_rework_ok',
                        sourceId: $sourceFinishingLineId > 0 ? $sourceFinishingLineId : $sourceRejectLineId,
                        notes: "Setor ulang reject {$sewingReturn->code} IN {$destWarehouse->code} + labor @{$rate}/pcs",
                        lotId: null,
                        unitCost: $unitCostWithLabor,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );

                    $okByBundle[$bundleId] = ($okByBundle[$bundleId] ?? 0) + $qtyOk;
                    continue;
                }

                // OK: OUT WIP-SEW, IN WIP-FIN + labor
                if ($qtyOk > 0.000001) {
                    $unitCostWipSew = (float) $this->inventory->getItemIncomingUnitCost(
                        warehouseId: $wipSewWarehouse->id,
                        itemId: $itemId
                    );

                    $rate = (float) app(PieceRateService::class)->requireRatePerPcs(
                        module: 'sewing',
                        employeeId: $operatorId,
                        itemId: $itemId,
                        date: $date
                    );

                    $unitCostWithLabor = $unitCostWipSew + $rate;

                    $this->inventory->stockOut(
                        warehouseId: $wipSewWarehouse->id,
                        itemId: $itemId,
                        qty: $qtyOk,
                        date: $date,
                        sourceType: 'sewing_return_ok',
                        sourceId: $sewingReturn->id,
                        notes: "Sewing Return {$sewingReturn->code} (OK) OUT {$wipSewWarehouse->code}",
                        allowNegative: false,
                        lotId: null,
                        unitCostOverride: null,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );

                    $this->inventory->stockIn(
                        warehouseId: $destWarehouse->id,
                        itemId: $itemId,
                        qty: $qtyOk,
                        date: $date,
                        sourceType: 'sewing_return_ok',
                        sourceId: $sewingReturn->id,
                        notes: "Sewing Return {$sewingReturn->code} (OK) IN {$destWarehouse->code} + labor @{$rate}/pcs",
                        lotId: null,
                        unitCost: $unitCostWithLabor,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );

                    $okByBundle[$bundleId] = ($okByBundle[$bundleId] ?? 0) + $qtyOk;
                }
            }

            // bundle: set "posisi terakhir"
            foreach ($okByBundle as $bundleId => $sumOk) {
                $b = $bundlesMap->get($bundleId);
                if (!$b) {
                    continue;
                }

                // Posisi hilir pasca-jahit → WIP-FIN. WH-PRD hanya setelah finishing/QC final.
                // ⚠️ JANGAN pernah menyentuh cut_wip_warehouse_id / cut_wip_qty di sini:
                // kolom itu milik tahap cutting (otoritatif untuk Ambil Jahit) dan dijaga
                // invarian di CuttingJobBundle::booted(). Menimpanya = bug "stok nyangkut".
                $b->wip_warehouse_id = (int) $destWarehouse->id;
                $b->wip_qty = (float) ($b->wip_qty ?? 0) + (float) $sumOk;
                $b->save();
            }

            // update pickup status (include progress_adjusted)
            foreach ($touchedPickupIds as $pid) {
                $pickup = $pickupsMap->get((int) $pid);
                if (!$pickup) {
                    continue;
                }

                $pls = SewingPickupLine::query()
                    ->where('sewing_pickup_id', $pid)
                    ->whereNull('voided_at')
                    ->get();

                $totalRemaining = (float) $pls->sum(function (SewingPickupLine $pl) {
                    $qtyBundle = (float) ($pl->qty_bundle ?? 0);
                    $returnedOk = (float) ($pl->qty_returned_ok ?? 0);
                    $returnedRej = (float) ($pl->qty_returned_reject ?? 0);
                    $directPick = (float) ($pl->qty_direct_picked ?? 0);
                    $progressAdj = (float) ($pl->qty_progress_adjusted ?? 0);

                    return max($qtyBundle - ($returnedOk + $returnedRej + $directPick + $progressAdj), 0);
                });

                $totalProgress = (float) $pls->sum(function (SewingPickupLine $pl) {
                    return (float) ($pl->qty_returned_ok ?? 0)
                     + (float) ($pl->qty_returned_reject ?? 0)
                     + (float) ($pl->qty_direct_picked ?? 0)
                     + (float) ($pl->qty_progress_adjusted ?? 0);
                });

                if ($pickup->isFillable('status')) {
                    if ($totalRemaining <= 0.000001) {
                        $pickup->status = 'completed';
                    } else {
                        $pickup->status = ($totalProgress > 0.000001) ? 'partial' : 'draft';
                    }

                    $pickup->save();
                }
            }

            if ($sewingReturn->isFillable('status') && empty($sewingReturn->status)) {
                $sewingReturn->status = 'posted';
                $sewingReturn->save();
            }

            return redirect()
                ->route('production.sewing.returns.show', $sewingReturn)
                ->with('success', 'Sewing Return berhasil disimpan.');
        });
    }

    /* ============================================================
     * VOID
     * ============================================================
     */
    public function void(Request $request, SewingReturn $return): RedirectResponse
    {
        if (strtolower(auth()->user()->role ?? '') !== 'owner') {
            abort(403, 'Hanya owner yang boleh melakukan VOID.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($return, $validated): RedirectResponse {

            $return = SewingReturn::query()
                ->whereKey($return->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!is_null($return->voided_at)) {
                return redirect()
                    ->route('production.sewing.returns.show', $return)
                    ->with('error', 'Sewing Return sudah di-VOID sebelumnya.');
            }

            $return->load(['lines.sewingPickupLine']);
            $lines = $return->lines ?? collect();

            $voidAt = now('Asia/Jakarta');
            $voidDate = $voidAt;

            if ($lines->isEmpty()) {
                $return->voided_at = now();
                if ($return->isFillable('voided_by_user_id')) {
                    $return->voided_by_user_id = auth()->id();
                }

                if ($return->isFillable('void_reason')) {
                    $return->void_reason = trim((string) ($validated['reason'] ?? '')) ?: null;
                }

                if ($return->isFillable('status')) {
                    $return->status = 'void';
                }

                $return->save();

                return redirect()
                    ->route('production.sewing.returns.show', $return)
                    ->with('success', 'Sewing Return berhasil di-VOID.');
            }

            $wipSewWarehouse = Warehouse::query()->whereIn('code', ['WIP-SEW', 'WH-SEWING'])->first();
            if (!$wipSewWarehouse) {
                throw ValidationException::withMessages(['reason' => 'Gudang WIP-SEW / WH-SEWING belum ada.']);
            }

            // Tujuan Sewing Return baru adalah WIP-FIN. Data lama tetap memakai destination_warehouse_id bila ada.
            $destWarehouse = null;
            if (!empty($return->destination_warehouse_id)) {
                $destWarehouse = Warehouse::query()->whereKey((int) $return->destination_warehouse_id)->first();
            }
            if (!$destWarehouse) {
                $destWarehouse = Warehouse::query()->where('code', 'WIP-FIN')->first();
            }
            if (!$destWarehouse) {
                throw ValidationException::withMessages(['reason' => 'Gudang tujuan SR tidak ditemukan (WIP-FIN).']);
            }

            $rejectWarehouse = Warehouse::query()->where('code', 'REJ-SEW')->first();

            $pickupLineIds = $lines->pluck('sewing_pickup_line_id')->filter()->unique()->values()->all();

            $pickupLines = SewingPickupLine::query()
                ->whereIn('id', $pickupLineIds)
                ->whereNull('voided_at')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($pickupLines->count() !== count($pickupLineIds)) {
                throw ValidationException::withMessages(['reason' => 'Ada pickup line yang tidak valid / sudah void.']);
            }

            $touchedPickupIds = $pickupLines->pluck('sewing_pickup_id')->filter()->unique()->values()->all();

            $pickupsMap = SewingPickup::query()
                ->whereIn('id', $touchedPickupIds)
                ->whereNull('voided_at')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $bundleIds = $pickupLines->pluck('cutting_job_bundle_id')->filter()->unique()->values()->all();
            $bundlesMap = CuttingJobBundle::query()
                ->whereIn('id', $bundleIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $itemIds = $pickupLines->pluck('finished_item_id')->filter()->unique()->values()->all();

            // lock stok di gudang tujuan (normal baru: WIP-FIN; data lama mengikuti tujuan tersimpan)
            $stocksDest = InventoryStock::query()
                ->where('warehouse_id', $destWarehouse->id)
                ->whereIn('item_id', $itemIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('item_id');

            $stocksReject = collect();
            if ($rejectWarehouse) {
                $stocksReject = InventoryStock::query()
                    ->where('warehouse_id', $rejectWarehouse->id)
                    ->whereIn('item_id', $itemIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('item_id');
            }

            $okByBundleVoid = [];

            foreach ($lines as $line) {
                $pl = $pickupLines->get((int) $line->sewing_pickup_line_id);
                if (!$pl) {
                    throw ValidationException::withMessages([
                        'reason' => "Pickup line {$line->sewing_pickup_line_id} tidak ditemukan / sudah void.",
                    ]);
                }

                $itemId = (int) $pl->finished_item_id;
                $bundleId = (int) $pl->cutting_job_bundle_id;

                $qtyOk = (int) round((float) ($line->qty_ok ?? 0));
                $qtyRj = (int) round((float) ($line->qty_reject ?? 0));
                $isRejectReworkLine = in_array((string) ($line->source_type ?? ''), ['reject_sewing_rework', 'finishing_sewing_rework'], true);

                // Setor ulang reject tidak menambah counter pickup awal, jadi saat void juga tidak menguranginya.
                if (!$isRejectReworkLine) {
                    $pl->qty_returned_ok = max((float) ($pl->qty_returned_ok ?? 0) - $qtyOk, 0);
                    $pl->qty_returned_reject = max((float) ($pl->qty_returned_reject ?? 0) - $qtyRj, 0);
                    $pl->save();
                }

                if ($qtyRj > 0 && !$rejectWarehouse) {
                    throw ValidationException::withMessages([
                        'reason' => "Tidak bisa VOID: qty_reject ada tapi gudang REJ-SEW belum ada (item #{$itemId}).",
                    ]);
                }

                if ($isRejectReworkLine && !$rejectWarehouse) {
                    throw ValidationException::withMessages([
                        'reason' => "Tidak bisa VOID: baris setor ulang reject perlu gudang REJ-SEW (item #{$itemId}).",
                    ]);
                }

                // reverse OK: DEST -> WIP-SEW, atau DEST -> REJ-SEW untuk setor ulang reject.
                if ($qtyOk > 0) {
                    $destStock = $stocksDest->get($itemId);
                    $destAvail = (float) ($destStock?->qty ?? 0);

                    if (($destAvail + 0.0000001) < $qtyOk) {
                        throw ValidationException::withMessages([
                            'reason' => "Tidak bisa VOID: stok {$destWarehouse->code} item #{$itemId} tidak cukup. Butuh {$qtyOk}, stok {$destAvail}.",
                        ]);
                    }

                    $sourceBackWarehouse = $isRejectReworkLine ? $rejectWarehouse : $wipSewWarehouse;
                    $voidSourceType = $isRejectReworkLine ? 'sewing_reject_rework_void' : 'sewing_return_void_ok';
                    $voidSourceId = $isRejectReworkLine
                        ? (int) ($line->source_finishing_job_line_id ?? $line->source_reject_return_line_id ?? $return->id)
                        : (int) $return->id;

                    $this->inventory->move(
                        $itemId,
                        $destWarehouse->id,
                        $sourceBackWarehouse->id,
                        $qtyOk,
                        $voidSourceType,
                        $voidSourceId,
                        "VOID {$return->code} (OK) {$destWarehouse->code} → {$sourceBackWarehouse->code}",
                        $voidDate,
                        false,
                        null,
                        $bundleId
                    );

                    // snapshot update
                    $stocksDest->put($itemId, (object) ['qty' => $destAvail - $qtyOk]);

                    $okByBundleVoid[$bundleId] = ($okByBundleVoid[$bundleId] ?? 0) + $qtyOk;
                }

                // reverse RJ: REJ-SEW -> WIP-SEW
                if ($qtyRj > 0) {
                    $rejStock = $stocksReject->get($itemId);
                    $rejAvail = (float) ($rejStock?->qty ?? 0);

                    if (($rejAvail + 0.0000001) < $qtyRj) {
                        throw ValidationException::withMessages([
                            'reason' => "Tidak bisa VOID: stok REJ-SEW item #{$itemId} tidak cukup. Butuh {$qtyRj}, stok {$rejAvail}.",
                        ]);
                    }

                    $this->inventory->move(
                        $itemId,
                        $rejectWarehouse->id,
                        $wipSewWarehouse->id,
                        $qtyRj,
                        'sewing_return_void_reject',
                        $return->id,
                        "VOID {$return->code} (RJ) REJ-SEW → {$wipSewWarehouse->code}",
                        $voidDate,
                        false,
                        null,
                        $bundleId
                    );

                    $stocksReject->put($itemId, (object) ['qty' => $rejAvail - $qtyRj]);
                }
            }

            // decrement bundle tracker
            foreach ($okByBundleVoid as $bundleId => $sumOk) {
                $b = $bundlesMap->get($bundleId);
                if (!$b) {
                    continue;
                }

                $b->wip_qty = max((float) ($b->wip_qty ?? 0) - (float) $sumOk, 0);
                $b->save();
            }

            // update pickup status
            foreach ($touchedPickupIds as $pid) {
                $pickup = $pickupsMap->get((int) $pid);
                if (!$pickup) {
                    continue;
                }

                $pls = SewingPickupLine::query()
                    ->where('sewing_pickup_id', $pid)
                    ->whereNull('voided_at')
                    ->get();

                $totalRemaining = (float) $pls->sum(function (SewingPickupLine $pl) {
                    $qtyBundle = (float) ($pl->qty_bundle ?? 0);
                    $returnedOk = (float) ($pl->qty_returned_ok ?? 0);
                    $returnedRej = (float) ($pl->qty_returned_reject ?? 0);
                    $directPick = (float) ($pl->qty_direct_picked ?? 0);
                    $progressAdj = (float) ($pl->qty_progress_adjusted ?? 0);

                    return max($qtyBundle - ($returnedOk + $returnedRej + $directPick + $progressAdj), 0);
                });

                $totalProgress = (float) $pls->sum(function (SewingPickupLine $pl) {
                    return (float) ($pl->qty_returned_ok ?? 0)
                     + (float) ($pl->qty_returned_reject ?? 0)
                     + (float) ($pl->qty_direct_picked ?? 0)
                     + (float) ($pl->qty_progress_adjusted ?? 0);
                });

                if ($pickup->isFillable('status')) {
                    if ($totalRemaining <= 0.000001) {
                        $pickup->status = 'completed';
                    } else {
                        $pickup->status = ($totalProgress > 0.000001) ? 'partial' : 'draft';
                    }
                    $pickup->save();
                }
            }

            // mark return voided
            $return->voided_at = now();
            if ($return->isFillable('voided_by_user_id')) {
                $return->voided_by_user_id = auth()->id();
            }

            if ($return->isFillable('void_reason')) {
                $return->void_reason = trim((string) ($validated['reason'] ?? '')) ?: null;
            }

            if ($return->isFillable('status')) {
                $return->status = 'void';
            }

            $return->save();

            return redirect()
                ->route('production.sewing.returns.show', $return)
                ->with('success', 'Sewing Return berhasil di-VOID dan stok/counter sudah dibalik.');
        });
    }
}
