<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\InventoryStock;
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
                'warehouse',
                'pickup',
                'lines.sewingPickupLine',
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
            'operator',
            'pickup.operator',
            'lines.sewingPickupLine.sewingPickup',
            'lines.sewingPickupLine.bundle.finishedItem',
            'lines.sewingPickupLine.bundle.cuttingJob.lot.item',
        ]);

        $lines = $return->lines ?? collect();

        // Total pickup (qty_bundle)
        $totalPickup = (float) $lines->sum(function ($line) {
            $pl = $line->sewingPickupLine;
            return (float) ($pl->qty_bundle ?? 0);
        });

        // Raw OK & Reject dari SR
        $totalOk = (float) $lines->sum('qty_ok');
        $totalReject = (float) $lines->sum('qty_reject');
        $totalProcessed = $totalOk + $totalReject;

        $okPercent = $totalProcessed > 0 ? round(($totalOk / $totalProcessed) * 100, 1) : 0.0;
        $rejectPercent = $totalProcessed > 0 ? round(($totalReject / $totalProcessed) * 100, 1) : 0.0;

        // unique pickup lines (safe)
        $uniquePickupLines = $lines->pluck('sewingPickupLine')
            ->filter()
            ->keyBy(fn($pl) => (int) $pl->id);

        $totalDirectPick = (float) $uniquePickupLines->sum(fn($pl) => (float) ($pl->qty_direct_picked ?? 0));
        $totalProgressAdjusted = (float) $uniquePickupLines->sum(fn($pl) => (float) ($pl->qty_progress_adjusted ?? 0));

        // Remaining = qty_bundle - (returned_ok + returned_reject + direct_pick + progress_adjusted)
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

        $pickupDate = $request->input('pickup_date');
        $pickupDate = is_string($pickupDate) ? trim($pickupDate) : null;
        if ($pickupDate === '') {
            $pickupDate = null;
        }

        $wipSewWarehouse = Warehouse::query()
            ->whereIn('code', ['WIP-SEW', 'WH-SEWING'])
            ->first();

        // operator list dari SewingPickup (yang pernah ada)
        $operatorIds = SewingPickup::query()
            ->whereNull('voided_at')
            ->whereNotNull('operator_id')
            ->distinct()
            ->pluck('operator_id')
            ->map(fn($v) => (int) $v)
            ->filter()
            ->values()
            ->all();

        $operators = Employee::query()
            ->whereIn('id', $operatorIds)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $lines = collect();
        $wipStockByItemId = [];

        if (!$wipSewWarehouse) {
            return view('production.sewing_returns.create', compact(
                'operators',
                'operatorId',
                'pickupDate',
                'lines',
                'wipSewWarehouse',
                'wipStockByItemId',
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

        return view('production.sewing_returns.create', compact(
            'operators',
            'operatorId',
            'pickupDate',
            'lines',
            'wipSewWarehouse',
            'wipStockByItemId',
        ));
    }

    /* ============================================================
     * STORE
     * ============================================================
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'operator_id' => ['required', 'integer', 'exists:employees,id'],

            'results' => ['required', 'array', 'min:1'],
            'results.*.sewing_pickup_line_id' => ['required', 'integer', 'exists:sewing_pickup_lines,id'],
            'results.*.qty_ok' => ['nullable', 'numeric', 'min:0'],
            'results.*.qty_reject' => ['nullable', 'numeric', 'min:0'],
            'results.*.notes' => ['nullable', 'string', 'max:500'],
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

            $wipFinWarehouse = Warehouse::query()->where('code', 'WIP-FIN')->first();
            if (!$wipFinWarehouse) {
                throw ValidationException::withMessages(['results' => 'Gudang tujuan WIP-FIN belum ada.']);
            }

            $rejectWarehouse = Warehouse::query()->where('code', 'REJECT')->first();

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

            // touched pickups
            $touchedPickupIds = $pickupLines->pluck('sewing_pickup_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $pickupsMap = SewingPickup::query()
                ->whereIn('id', $touchedPickupIds)
                ->whereNull('voided_at')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // touched bundles
            $bundleIds = $pickupLines->pluck('cutting_job_bundle_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $bundlesMap = CuttingJobBundle::query()
                ->whereIn('id', $bundleIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // lock stock WIP-SEW per item
            $itemIds = $pickupLines->pluck('finished_item_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

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

            // 1) clamp global per item: need <= stok WIP-SEW
            $requestedByItem = [];
            foreach ($rawResults as $r) {
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
            foreach ($rawResults as $r) {
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

            // header pickup id (kalau single pickup)
            $headerPickupId = (count($touchedPickupIds) === 1) ? (int) $touchedPickupIds[0] : null;

            $sewingReturn = SewingReturn::create([
                'code' => method_exists(SewingReturn::class, 'generateCode')
                ? SewingReturn::generateCode($date)
                : ('SR-' . Carbon::parse($date)->format('Ymd') . '-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT)),
                'date' => $date,
                'warehouse_id' => (int) $wipSewWarehouse->id,
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
                ]);

                $pl->qty_returned_ok = (float) ($pl->qty_returned_ok ?? 0) + (float) $r['qty_ok'];
                $pl->qty_returned_reject = (float) ($pl->qty_returned_reject ?? 0) + (float) $r['qty_reject'];
                $pl->save();
            }

            // inventory + bundle tracker
            $okByBundle = [];

            foreach ($rawResults as $r) {
                $pl = $pickupLines->get((int) $r['sewing_pickup_line_id']);

                $bundleId = (int) $pl->cutting_job_bundle_id;
                $itemId = (int) $pl->finished_item_id;

                $qtyOk = (float) $r['qty_ok'];
                $qtyRj = (float) $r['qty_reject'];

                // Reject: OUT WIP-SEW (+ optional IN REJECT)
                if ($qtyRj > 0.000001) {
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
                    );

                    if ($rejectWarehouse) {
                        $this->inventory->stockIn(
                            warehouseId: $rejectWarehouse->id,
                            itemId: $itemId,
                            qty: $qtyRj,
                            date: $date,
                            sourceType: 'sewing_return_reject',
                            sourceId: $sewingReturn->id,
                            notes: "Sewing Return {$sewingReturn->code} (RJ) → REJECT",
                            lotId: null,
                            unitCost: null,
                            affectLotCost: false,
                        );
                    }
                }

                // OK: move WIP-SEW -> WIP-FIN
                // OK: OUT WIP-SEW, IN WIP-FIN dengan unit cost + labor (piece rate)
                if ($qtyOk > 0.000001) {

                    // 1) Ambil unit cost dari WIP-SEW (sebelum dipindah)
                    $unitCostWipSew = (float) $this->inventory->getItemIncomingUnitCost(
                        warehouseId: $wipSewWarehouse->id,
                        itemId: $itemId
                    );

                    // 2) Ambil rate jahit dari PieceRate (module=sewing)
                    // operatorId di SewingReturn = operator jahit (sesuai form kamu)
                    $rate = (float) app(PieceRateService::class)->requireRatePerPcs(
                        module: 'sewing',
                        employeeId: $operatorId,
                        itemId: $itemId,
                        date: $date
                    );

                    // 3) Unit cost baru di WIP-FIN = RM+proses sebelumnya + jahit
                    $unitCostWithLabor = $unitCostWipSew + $rate;

                    // 4) OUT dari WIP-SEW (nilai mengikuti average WIP-SEW)
                    $this->inventory->stockOut(
                        warehouseId: $wipSewWarehouse->id,
                        itemId: $itemId,
                        qty: $qtyOk,
                        date: $date,
                        sourceType: 'sewing_return_ok',
                        sourceId: $sewingReturn->id,
                        notes: "Sewing Return {$sewingReturn->code} (OK) OUT WIP-SEW",
                        allowNegative: false,
                        lotId: null,
                        unitCostOverride: null,
                        affectLotCost: false,
                    );

                    // 5) IN ke WIP-FIN dengan unit cost sudah + labor
                    $this->inventory->stockIn(
                        warehouseId: $wipFinWarehouse->id,
                        itemId: $itemId,
                        qty: $qtyOk,
                        date: $date,
                        sourceType: 'sewing_return_ok',
                        sourceId: $sewingReturn->id,
                        notes: "Sewing Return {$sewingReturn->code} (OK) IN WIP-FIN + labor @{$rate}/pcs",
                        lotId: null,
                        unitCost: $unitCostWithLabor,
                        affectLotCost: false,
                    );

                    $okByBundle[$bundleId] = ($okByBundle[$bundleId] ?? 0) + $qtyOk;
                }

            }

            foreach ($okByBundle as $bundleId => $sumOk) {
                $b = $bundlesMap->get($bundleId);
                if (!$b) {
                    continue;
                }

                $b->wip_warehouse_id = (int) $wipFinWarehouse->id;
                $b->wip_qty = (float) ($b->wip_qty ?? 0) + (float) $sumOk; // ✅ increment, not overwrite
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
                ->with('success', 'Sewing Return berhasil disimpan (multi-pickup).');
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

            // ✅ pakai DateTimeInterface (InventoryService akan normalize ke Y-m-d)
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

            $wipFinWarehouse = Warehouse::query()->where('code', 'WIP-FIN')->first();
            if (!$wipFinWarehouse) {
                throw ValidationException::withMessages(['reason' => 'Gudang WIP-FIN belum ada.']);
            }

            $rejectWarehouse = Warehouse::query()->where('code', 'REJECT')->first();

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

            $stocksWipFin = InventoryStock::query()
                ->where('warehouse_id', $wipFinWarehouse->id)
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

                // ✅ normalize to INT
                $qtyOk = (int) round((float) ($line->qty_ok ?? 0));
                $qtyRj = (int) round((float) ($line->qty_reject ?? 0));

                // reverse pickup counters (keep float columns? tapi isi int aman)
                $pl->qty_returned_ok = max((float) ($pl->qty_returned_ok ?? 0) - $qtyOk, 0);
                $pl->qty_returned_reject = max((float) ($pl->qty_returned_reject ?? 0) - $qtyRj, 0);
                $pl->save();

                // ✅ guard: kalau ada reject tapi gudang REJECT gak ada → jangan create stock dari udara
                if ($qtyRj > 0 && !$rejectWarehouse) {
                    throw ValidationException::withMessages([
                        'reason' => "Tidak bisa VOID: qty_reject ada tapi gudang REJECT belum ada (item #{$itemId}).",
                    ]);
                }

                // reverse OK: WIP-FIN -> WIP-SEW
                if ($qtyOk > 0) {
                    $finStock = $stocksWipFin->get($itemId);
                    $finAvail = (float) ($finStock?->qty ?? 0);

                    if (($finAvail + 0.0000001) < $qtyOk) {
                        throw ValidationException::withMessages([
                            'reason' => "Tidak bisa VOID: stok WIP-FIN item #{$itemId} tidak cukup. Butuh {$qtyOk}, stok {$finAvail}.",
                        ]);
                    }

                    $this->inventory->move(
                        $itemId,
                        $wipFinWarehouse->id,
                        $wipSewWarehouse->id,
                        $qtyOk,
                        'sewing_return_void_ok',
                        $return->id,
                        "VOID {$return->code} (OK) WIP-FIN → WIP-SEW",
                        $voidDate,
                        false,
                        null
                    );

                    // update local snapshot
                    $stocksWipFin->put($itemId, (object) ['qty' => $finAvail - $qtyOk]);

                    $okByBundleVoid[$bundleId] = ($okByBundleVoid[$bundleId] ?? 0) + $qtyOk;
                }

                // reverse RJ: REJECT -> WIP-SEW
                if ($qtyRj > 0) {
                    $rejStock = $stocksReject->get($itemId);
                    $rejAvail = (float) ($rejStock?->qty ?? 0);

                    if (($rejAvail + 0.0000001) < $qtyRj) {
                        throw ValidationException::withMessages([
                            'reason' => "Tidak bisa VOID: stok REJECT item #{$itemId} tidak cukup. Butuh {$qtyRj}, stok {$rejAvail}.",
                        ]);
                    }

                    $this->inventory->move(
                        $itemId,
                        $rejectWarehouse->id,
                        $wipSewWarehouse->id,
                        $qtyRj,
                        'sewing_return_void_reject',
                        $return->id,
                        "VOID {$return->code} (RJ) REJECT → WIP-SEW",
                        $voidDate,
                        false,
                        null
                    );

                    $stocksReject->put($itemId, (object) ['qty' => $rejAvail - $qtyRj]);
                }
            }

            // decrement bundle wip_qty (mirror store increment)
            foreach ($okByBundleVoid as $bundleId => $sumOk) {
                $b = $bundlesMap->get($bundleId);
                if (!$b) {
                    continue;
                }

                $b->wip_qty = max((float) ($b->wip_qty ?? 0) - (float) $sumOk, 0);
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
