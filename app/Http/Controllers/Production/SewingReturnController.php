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
                'lines.sewingPickupLine', // penting untuk ringkasan
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
        $operators = Employee::orderBy('code')->get();

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
        $totalPickup = $lines->sum(function ($line) {
            $pl = $line->sewingPickupLine;
            return (float) ($pl->qty_bundle ?? 0);
        });

        // Raw OK & Reject dari SR
        $totalOk = (float) $lines->sum('qty_ok');
        $totalReject = (float) $lines->sum('qty_reject');
        $totalProcessed = $totalOk + $totalReject;

        $okPercent = $totalProcessed > 0 ? round(($totalOk / $totalProcessed) * 100, 1) : 0.0;
        $rejectPercent = $totalProcessed > 0 ? round(($totalReject / $totalProcessed) * 100, 1) : 0.0;

        // Total Direct Pickup (unik per pickup line) - lebih aman daripada sum line-by-line (biar gak double)
        $totalDirectPick = $lines
            ->pluck('sewingPickupLine')
            ->filter()
            ->unique('id')
            ->sum(fn($pl) => (float) ($pl->qty_direct_picked ?? 0));

        // ✅ Total progress adjusted (unik per pickup line)
        $totalProgressAdjusted = $lines
            ->pluck('sewingPickupLine')
            ->filter()
            ->unique('id')
            ->sum(fn($pl) => (float) ($pl->qty_progress_adjusted ?? 0));

        // Remaining = qty_bundle - (returned_ok + returned_reject + direct_pick + progress_adjusted)
        $totalRemaining = $lines->sum(function ($line) {
            $pl = $line->sewingPickupLine;
            if (!$pl) {
                return 0;
            }

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
    public function create(Request $request): \Illuminate\View\View
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

        // ✅ ambil operator_id dari SewingPickup (bukan dari Employee)
        $operatorIds = SewingPickup::query()
            ->whereNull('voided_at')
            ->whereNotNull('operator_id')
            ->distinct()
            ->pluck('operator_id')
            ->map(fn($v) => (int) $v)
            ->filter()
            ->values()
            ->all();

        // ✅ baru lookup untuk label dropdown (kalau mau)
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

        // ✅ default: tampilkan semua (operatorId nullable)
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

        // remaining + itemIds
        $itemIds = [];
        $lines = $lines->map(function ($l) use (&$itemIds) {
            $qtyBundle = (float) ($l->qty_bundle ?? 0);
            $returnedOk = (float) ($l->qty_returned_ok ?? 0);
            $returnedRj = (float) ($l->qty_returned_reject ?? 0);
            $directPick = (float) ($l->qty_direct_picked ?? 0);

            $l->remaining_qty = max($qtyBundle - ($returnedOk + $returnedRj + $directPick), 0);

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
     * STORE (POSTED ON SUBMIT)
     * ============================================================
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],

            // operator manual (wajib)
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

            // Warehouses
            $wipSewWarehouse = Warehouse::query()
                ->whereIn('code', ['WIP-SEW', 'WH-SEWING'])
                ->first();

            if (!$wipSewWarehouse) {
                throw ValidationException::withMessages([
                    'results' => 'Gudang WIP-SEW / WH-SEWING belum ada.',
                ]);
            }

            $wipFinWarehouse = Warehouse::query()->where('code', 'WIP-FIN')->first();
            if (!$wipFinWarehouse) {
                throw ValidationException::withMessages([
                    'results' => 'Gudang tujuan WIP-FIN belum ada (sesuaikan code jika berbeda).',
                ]);
            }

            $rejectWarehouse = Warehouse::query()->where('code', 'REJECT')->first();

            // Ambil results yang terisi
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
                ->filter(fn($r) => ($r['total'] ?? 0) > 0.000001)
                ->values();

            if ($rawResults->isEmpty()) {
                throw ValidationException::withMessages([
                    'results' => 'Minimal isi 1 baris (OK / Reject).',
                ]);
            }

            // Lock pickup lines terkait
            $lineIds = $rawResults->pluck('sewing_pickup_line_id')->unique()->values()->all();

            $pickupLines = SewingPickupLine::query()
                ->whereIn('id', $lineIds)
                ->whereNull('voided_at')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($pickupLines->count() !== count($lineIds)) {
                throw ValidationException::withMessages([
                    'results' => 'Ada pickup line yang tidak valid / sudah void.',
                ]);
            }

            // Kumpulkan pickup ids yang tersentuh
            $touchedPickupIds = $pickupLines->map(fn($pl) => (int) $pl->sewing_pickup_id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            // Lock pickups yang tersentuh
            $pickupsMap = SewingPickup::query()
                ->whereIn('id', $touchedPickupIds)
                ->whereNull('voided_at')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Lock bundles yang tersentuh
            $bundleIds = $pickupLines->map(fn($pl) => (int) $pl->cutting_job_bundle_id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $bundlesMap = CuttingJobBundle::query()
                ->whereIn('id', $bundleIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Lock stok WIP-SEW untuk item terkait (item = finished_item_id)
            $itemIds = $pickupLines->map(fn($pl) => (int) $pl->finished_item_id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $stocks = InventoryStock::query()
                ->where('warehouse_id', $wipSewWarehouse->id)
                ->whereIn('item_id', $itemIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('item_id');

            $availableByItem = [];
            foreach ($itemIds as $itemId) {
                $availableByItem[$itemId] = (float) ($stocks[$itemId]->qty ?? 0);
            }

            // 1) Clamp global per item (sum OK+RJ <= stok WIP-SEW)
            $requestedByItem = [];
            foreach ($rawResults as $r) {
                $pl = $pickupLines->get($r['sewing_pickup_line_id']);
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

            // 2) Clamp per pickup line (total <= remaining pickup line) (include direct_picked)
            foreach ($rawResults as $r) {
                $pl = $pickupLines->get($r['sewing_pickup_line_id']);

                $qtyBundle = (float) ($pl->qty_bundle ?? 0);
                $returnedOk = (float) ($pl->qty_returned_ok ?? 0);
                $returnedRej = (float) ($pl->qty_returned_reject ?? 0);
                $directPick = (float) ($pl->qty_direct_picked ?? 0);

                $remainingPickup = max($qtyBundle - ($returnedOk + $returnedRej + $directPick), 0);

                if ((float) $r['total'] > $remainingPickup + 0.000001) {
                    throw ValidationException::withMessages([
                        'results' => "Qty OK+Reject melebihi sisa pickup (line #{$pl->id}). Sisa: {$remainingPickup}.",
                    ]);
                }
            }

            // Jika semua dari 1 pickup, boleh isi pickup_id, kalau multi set NULL
            $uniqPickupIds = collect($touchedPickupIds)->values()->all();
            $headerPickupId = (count($uniqPickupIds) === 1) ? (int) $uniqPickupIds[0] : null;

            // CREATE header Sewing Return
            $sewingReturn = SewingReturn::create([
                'code' => method_exists(SewingReturn::class, 'generateCode')
                ? SewingReturn::generateCode($date)
                : ('SR-' . Carbon::parse($date)->format('Ymd') . '-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT)),
                'date' => $date,
                'warehouse_id' => (int) $wipSewWarehouse->id,

                // ✅ multi pickup: NULL (atau isi kalau cuma 1 pickup)
                'pickup_id' => $headerPickupId,

                'operator_id' => $operatorId,
                'created_by_user_id' => auth()->id(),
                'notes' => null,
                'status' => (new SewingReturn())->isFillable('status') ? 'posted' : null,
            ]);

            // CREATE return lines + update pickup line counters
            foreach ($rawResults as $r) {
                $pl = $pickupLines->get($r['sewing_pickup_line_id']);

                SewingReturnLine::create([
                    'sewing_return_id' => $sewingReturn->id,
                    'sewing_pickup_line_id' => $pl->id,
                    'qty_ok' => (float) $r['qty_ok'],
                    'qty_reject' => (float) $r['qty_reject'],
                    'notes' => $r['notes'] !== '' ? $r['notes'] : null,

                    // ✅ kalau definisi finished_qty = OK yang selesai disetor
                    'finished_qty' => (int) round((float) $r['qty_ok']),
                ]);

                $pl->qty_returned_ok = (float) ($pl->qty_returned_ok ?? 0) + (float) $r['qty_ok'];
                $pl->qty_returned_reject = (float) ($pl->qty_returned_reject ?? 0) + (float) $r['qty_reject'];
                $pl->save();
            }

            // MUTASI INVENTORY + update bundle tracker WIP-FIN
            $okByBundle = [];

            foreach ($rawResults as $r) {
                $pl = $pickupLines->get($r['sewing_pickup_line_id']);

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
                if ($qtyOk > 0.000001) {
                    $this->inventory->move(
                        itemId: $itemId,
                        fromWarehouseId: $wipSewWarehouse->id,
                        toWarehouseId: $wipFinWarehouse->id,
                        qty: $qtyOk,
                        referenceType: 'sewing_return_ok',
                        referenceId: $sewingReturn->id,
                        notes: "Sewing Return {$sewingReturn->code} (OK) WIP-SEW → WIP-FIN",
                        date: $date,
                        allowNegative: false,
                        lotId: null,
                    );

                    $okByBundle[$bundleId] = ($okByBundle[$bundleId] ?? 0) + $qtyOk;
                }
            }

            // Update bundle wip tracker (set qty ke total OK yg disetor hari ini)
            foreach ($okByBundle as $bundleId => $sumOk) {
                $b = $bundlesMap->get($bundleId);
                if (!$b) {
                    continue;
                }

                $b->wip_warehouse_id = (int) $wipFinWarehouse->id;
                $b->wip_qty = (float) $sumOk;
                $b->save();
            }

            // UPDATE STATUS pickup per pickup yang tersentuh (include direct_picked)
            foreach ($touchedPickupIds as $pid) {
                $pickup = $pickupsMap->get($pid);
                if (!$pickup) {
                    continue;
                }

                $lines = SewingPickupLine::query()
                    ->where('sewing_pickup_id', $pid)
                    ->whereNull('voided_at')
                    ->get();

                $totalRemaining = (float) $lines->sum(function (SewingPickupLine $pl) {
                    $qtyBundle = (float) ($pl->qty_bundle ?? 0);
                    $returnedOk = (float) ($pl->qty_returned_ok ?? 0);
                    $returnedRej = (float) ($pl->qty_returned_reject ?? 0);
                    $directPick = (float) ($pl->qty_direct_picked ?? 0);

                    return max($qtyBundle - ($returnedOk + $returnedRej + $directPick), 0);
                });

                $totalProgress = (float) $lines->sum(function (SewingPickupLine $pl) {
                    return (float) ($pl->qty_returned_ok ?? 0)
                     + (float) ($pl->qty_returned_reject ?? 0)
                     + (float) ($pl->qty_direct_picked ?? 0);
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

}
