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
    public function create(Request $request): View
    {
        $pickups = SewingPickup::query()
            ->with(['operator', 'lines'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $pickupId = $request->integer('pickup_id') ?: null;

        $lines = collect();
        $selectedPickup = null;

        // Warehouse WIP-SEW (fallback WH-SEWING)
        $wipSewWarehouse = Warehouse::query()
            ->where('code', 'WIP-SEW')
            ->orWhere('code', 'WH-SEWING')
            ->first();

        $wipStockByItemId = [];

        if ($pickupId && $wipSewWarehouse) {
            $selectedPickup = SewingPickup::query()
                ->with([
                    'operator',
                    'lines.bundle.finishedItem',
                    'lines.bundle.cuttingJob.lot',
                ])
                ->find($pickupId);

            if ($selectedPickup) {
                $itemIds = $selectedPickup->lines
                    ->map(fn(SewingPickupLine $l) => $l->bundle?->finishedItem?->id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (!empty($itemIds)) {
                    $wipStockByItemId = InventoryStock::query()
                        ->where('warehouse_id', $wipSewWarehouse->id)
                        ->whereIn('item_id', $itemIds)
                        ->pluck('qty', 'item_id')
                        ->map(fn($v) => (float) $v)
                        ->toArray();
                }

                $lines = $selectedPickup->lines
                    ->map(function (SewingPickupLine $line) use ($wipStockByItemId) {
                        $qtyBundle = (float) ($line->qty_bundle ?? 0);
                        $returnedOk = (float) ($line->qty_returned_ok ?? 0);
                        $returnedRej = (float) ($line->qty_returned_reject ?? 0);
                        $directPick = (float) ($line->qty_direct_picked ?? 0);
                        $progressAdj = (float) ($line->qty_progress_adjusted ?? 0);

                        $remainingPickup = max($qtyBundle - ($returnedOk + $returnedRej + $directPick + $progressAdj), 0);

                        $itemId = (int) ($line->bundle?->finishedItem?->id ?? 0);
                        $wipStock = (float) ($wipStockByItemId[$itemId] ?? 0);

                        $line->remaining_qty = $remainingPickup;
                        $line->wip_stock = $wipStock;

                        return $line;
                    })
                    ->filter(fn($line) =>
                        (float) ($line->remaining_qty ?? 0) > 0.000001 &&
                        (float) ($line->wip_stock ?? 0) > 0.000001
                    )
                    ->values();
            }
        }

        return view('production.sewing_returns.create', [
            'pickups' => $pickups,
            'selectedPickup' => $selectedPickup,
            'pickupId' => $pickupId,
            'lines' => $lines,
            'wipSewWarehouse' => $wipSewWarehouse,
            'wipStockByItemId' => $wipStockByItemId,
        ]);
    }

    /* ============================================================
     * STORE (POSTED ON SUBMIT)
     * ============================================================
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'pickup_id' => ['required', 'integer', 'exists:sewing_pickups,id'],
            'operator_id' => ['nullable', 'integer'],
            'results' => ['required', 'array', 'min:1'],

            'results.*.sewing_pickup_line_id' => ['required', 'integer', 'exists:sewing_pickup_lines,id'],
            'results.*.bundle_id' => ['nullable', 'integer'],

            'results.*.qty_ok' => ['nullable', 'numeric', 'min:0'],
            'results.*.qty_reject' => ['nullable', 'numeric', 'min:0'],
            'results.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $date = Carbon::parse($validated['date'])->toDateString();

        return DB::transaction(function () use ($validated, $date): RedirectResponse {

            // Warehouses
            $wipSewWarehouse = Warehouse::query()
                ->where('code', 'WIP-SEW')
                ->orWhere('code', 'WH-SEWING')
                ->first();

            if (!$wipSewWarehouse) {
                throw ValidationException::withMessages([
                    'pickup_id' => 'Gudang WIP-SEW / WH-SEWING belum ada.',
                ]);
            }

            $wipFinWarehouse = Warehouse::query()->where('code', 'WIP-FIN')->first();
            if (!$wipFinWarehouse) {
                throw ValidationException::withMessages([
                    'pickup_id' => 'Gudang tujuan WIP-FIN belum ada (sesuaikan code jika berbeda).',
                ]);
            }

            $rejectWarehouse = Warehouse::query()->where('code', 'REJECT')->first();

            /** @var SewingPickup $pickup */
            $pickup = SewingPickup::query()
                ->with(['operator'])
                ->lockForUpdate()
                ->findOrFail((int) $validated['pickup_id']);

            // Anti bypass operator
            if (!empty($validated['operator_id']) && (int) $validated['operator_id'] !== (int) $pickup->operator_id) {
                throw ValidationException::withMessages([
                    'operator_id' => 'Operator tidak valid (harus mengikuti Sewing Pickup yang dipilih).',
                ]);
            }

            // Ambil results yang terisi
            $rawResults = collect($validated['results'] ?? [])
                ->map(function ($r) {
                    $ok = (float) ($r['qty_ok'] ?? 0);
                    $rj = (float) ($r['qty_reject'] ?? 0);

                    return [
                        'sewing_pickup_line_id' => (int) ($r['sewing_pickup_line_id'] ?? 0),
                        'bundle_id' => isset($r['bundle_id']) ? (int) $r['bundle_id'] : null,
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
                ->with(['bundle.finishedItem'])
                ->whereIn('id', $lineIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($pickupLines as $pl) {
                if ((int) $pl->sewing_pickup_id !== (int) $pickup->id) {
                    throw ValidationException::withMessages([
                        'results' => 'Ada baris yang bukan milik Sewing Pickup yang dipilih (bypass terdeteksi).',
                    ]);
                }
            }

            // Lock bundles (update tracker WIP-FIN)
            $bundleIds = $pickupLines
                ->map(fn($pl) => $pl->bundle?->id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $bundlesMap = CuttingJobBundle::query()
                ->whereIn('id', $bundleIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Lock stok WIP-SEW untuk item terkait
            $itemIds = $pickupLines
                ->map(fn($pl) => $pl->bundle?->finishedItem?->id)
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

            // 1) Clamp global per item (sum input <= stok real WIP-SEW)
            $requestedByItem = [];
            foreach ($rawResults as $r) {
                $pl = $pickupLines->get($r['sewing_pickup_line_id']);
                $itemId = (int) ($pl?->bundle?->finishedItem?->id ?? 0);
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

            // 2) Clamp per line (total <= remaining pickup) ✅ include direct_pick + progress_adjusted
            foreach ($rawResults as $r) {
                $pl = $pickupLines->get($r['sewing_pickup_line_id']);

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

            // CREATE header Sewing Return
            $warehouseIdForReturn = (int) ($pickup->warehouse_id ?? 0);
            if ($warehouseIdForReturn <= 0) {
                $warehouseIdForReturn = (int) $wipSewWarehouse->id;
            }

            if ($warehouseIdForReturn <= 0) {
                throw new \RuntimeException('Warehouse WIP-SEW belum ada / pickup tidak punya warehouse_id.');
            }

            /** @var SewingReturn $sewingReturn */
            $sewingReturn = SewingReturn::create([
                'code' => method_exists(SewingReturn::class, 'generateCode')
                ? SewingReturn::generateCode($date)
                : ('SR-' . Carbon::parse($date)->format('Ymd') . '-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT)),
                'date' => $date,
                'warehouse_id' => $warehouseIdForReturn,
                'sewing_pickup_id' => $pickup->id,
                'operator_id' => $pickup->operator_id,
                'created_by_user_id' => auth()->id(),
                'notes' => null,
                'status' => (new SewingReturn())->isFillable('status') ? 'posted' : ($pickup->status ?? null),
            ]);

            // CREATE lines + update pickup counters
            foreach ($rawResults as $r) {
                $pl = $pickupLines->get($r['sewing_pickup_line_id']);
                $bundle = $pl?->bundle;
                $itemId = (int) ($bundle?->finishedItem?->id ?? 0);

                SewingReturnLine::create([
                    'sewing_return_id' => $sewingReturn->id,
                    'sewing_pickup_line_id' => $pl->id,
                    'bundle_id' => $bundle?->id,
                    'item_id' => $itemId ?: null,
                    'qty_ok' => (float) $r['qty_ok'],
                    'qty_reject' => (float) $r['qty_reject'],
                    'notes' => $r['notes'] !== '' ? $r['notes'] : null,
                ]);

                $pl->qty_returned_ok = (float) ($pl->qty_returned_ok ?? 0) + (float) $r['qty_ok'];
                $pl->qty_returned_reject = (float) ($pl->qty_returned_reject ?? 0) + (float) $r['qty_reject'];
                $pl->save();
            }

            // MUTASI INVENTORY + update bundle tracker WIP-FIN
            $okByBundle = [];

            foreach ($rawResults as $r) {
                $pl = $pickupLines->get($r['sewing_pickup_line_id']);
                $bundle = $pl?->bundle;

                $bundleId = (int) ($bundle?->id ?? 0);
                $itemId = (int) ($bundle?->finishedItem?->id ?? 0);
                if ($itemId <= 0) {
                    continue;
                }

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

                    if ($bundleId > 0) {
                        $okByBundle[$bundleId] = ($okByBundle[$bundleId] ?? 0) + $qtyOk;
                    }
                }
            }

            // UPDATE bundle WIP tracker untuk Finishing (SET)
            foreach ($okByBundle as $bundleId => $sumOk) {
                /** @var CuttingJobBundle|null $b */
                $b = $bundlesMap->get($bundleId);
                if (!$b) {
                    continue;
                }

                $bundleItemId = (int) ($b->finished_item_id ?? 0);
                if ($bundleItemId <= 0) {
                    $bundleItemId = (int) ($b->finishedItem?->id ?? 0);
                }

                if (!empty($b->finished_item_id) && $bundleItemId > 0 && (int) $b->finished_item_id !== $bundleItemId) {
                    throw ValidationException::withMessages([
                        'results' => "Mismatch finished_item_id pada bundle #{$bundleId}. (bundle={$b->finished_item_id}, rel={$bundleItemId})",
                    ]);
                }

                if (empty($b->finished_item_id) && $bundleItemId > 0) {
                    $b->finished_item_id = $bundleItemId;
                }

                $b->wip_warehouse_id = (int) $wipFinWarehouse->id;
                $b->wip_qty = (float) $sumOk; // represent qty tersedia untuk finishing
                $b->save();
            }

            // UPDATE STATUS PICKUP ✅ include direct_picked + progress_adjusted
            $pickup->refresh()->load('lines');

            $totalRemaining = (float) $pickup->lines->sum(function (SewingPickupLine $pl) {
                $qtyBundle = (float) ($pl->qty_bundle ?? 0);
                $returnedOk = (float) ($pl->qty_returned_ok ?? 0);
                $returnedRej = (float) ($pl->qty_returned_reject ?? 0);
                $directPick = (float) ($pl->qty_direct_picked ?? 0);
                $progressAdj = (float) ($pl->qty_progress_adjusted ?? 0);

                return max($qtyBundle - ($returnedOk + $returnedRej + $directPick + $progressAdj), 0);
            });

            $totalProgress = (float) $pickup->lines->sum(function (SewingPickupLine $pl) {
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

            if ($sewingReturn->isFillable('status') && empty($sewingReturn->status)) {
                $sewingReturn->status = 'posted';
                $sewingReturn->save();
            }

            return redirect()
                ->route('production.sewing.returns.show', $sewingReturn)
                ->with('success', 'Sewing Return berhasil disimpan.');
        });
    }
}
