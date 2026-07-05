<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\QcResult;
use App\Models\SewingPickup;
use App\Models\SewingPickupLine;
use App\Models\SewingPickupLineSupplyLine;
use App\Models\SewingPickupSupplyLine;
use App\Models\SewingReturn;
use App\Models\SewingReturnLine;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SewingReturnController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
        protected JournalService $journal,
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
                'destinationWarehouse', // tujuan pasca jahit (WH-PRD)
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
    public function print(SewingReturn $return): View
    {
        $return->load([
            'warehouse',
            'operator',
            'pickup',
            'lines.sewingPickupLine.bundle.finishedItem',
        ]);

        return view('production.sewing_returns.print', compact('return'));
    }

    public function barcode(SewingReturn $return): View
    {
        $return->load([
            'warehouse',
            'operator',
            'lines.sewingPickupLine.bundle.finishedItem',
        ]);

        return view('production.sewing_returns.barcode', compact('return'));
    }

    public function show(SewingReturn $return): View
    {
        $return->load([
            'warehouse',
            'destinationWarehouse',
            'operator',
            'pickup.operator',
            'lines.sewingPickupLine.sewingPickup',
            'lines.sewingPickupLine.supplyLines.materialItem:id,code,name,unit',
            'lines.sewingPickupLine.finishedItem:id,code,name',
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

        // Sewing Return normal masuk gate QC dulu: WIP-SEW tetap menjadi sumber,
        // destination disiapkan ke WIP-FIN agar dokumen jelas menunggu QC jahit.
        $canChooseDestination = false;

        // ambil gudang tujuan pasca jahit
        $destinationWarehouses = Warehouse::query()
            ->whereIn('code', Route::has('production.qc.sewing.edit') ? ['WIP-FIN'] : ['WH-PRD'])
            ->get(['id', 'code', 'name']);

        $defaultDestWarehouseId = (int) optional($destinationWarehouses->first())->id;

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
            ->when($isRejectReworkMode, fn($ids) => $ids
                ->merge($operatorIdsFromSewingReturns)
                ->merge($operatorIdsFromFinishing))
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
                'sewingPickup.supplyLines.material:id,code,name',
                'sewingPickup.lines:id,sewing_pickup_id,finished_item_id,qty_bundle,voided_at',
                'sewingPickup.lines.finishedItem:id,code',
                'supplyLines.materialItem:id,code,name,unit',
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

        // Pre-compute per-bundle greedy supply allocation (cached per pickup_id)
        // Greedy: alokasikan issued_pcs dari bundle terbesar (qty_bundle desc) → sama dengan urutan modal JS
        $pickupAllocCache = []; // pickup_id → [bundle_line_id → [sl_id => [issued, required]]]

        $lines = $lines->map(function ($l) use ($wipStockByItemId, &$pickupAllocCache) {
            $itemId = (int) ($l->finished_item_id ?? 0);
            $l->wip_stock  = (float) ($wipStockByItemId[$itemId] ?? 0);
            $remaining     = (float) ($l->remaining_qty ?? 0);

            $pu = $l->sewingPickup;
            $puId = $pu?->id;
            $lineSupplyLines = $l->supplyLines ?? collect();

            if ($lineSupplyLines->isNotEmpty()) {
                $this->applyLineSupplyStateToPickupLine($l, $lineSupplyLines, $remaining);
                return $l;
            }

            // Jika pickup tidak punya supply lines sama sekali → bebas setor
            $allSupplyLines = $pu?->supplyLines ?? collect();
            if (!$puId || $allSupplyLines->isEmpty()) {
                $l->supply_incomplete  = false;
                $l->supply_partial     = false;
                $l->supply_max_setor   = null;
                $l->supply_shortage_label = '';
                $l->supply_unmigrated = true;
                return $l;
            }

            // Build greedy allocation cache untuk pickup ini (hanya sekali per pickup)
            if (!array_key_exists($puId, $pickupAllocCache)) {
                // Sort bundle lines desc by qty_bundle — sama dengan urutan breakdown modal
                $allBundleLines = ($pu->lines ?? collect())
                    ->whereNull('voided_at')
                    ->sortByDesc('qty_bundle')
                    ->values();
                $totalQty = (float) $allBundleLines->sum('qty_bundle');

                $cache = []; // [bundle_line_id][sl_id] → [issued, required]
                foreach ($allSupplyLines as $sl) {
                    $reqTotal   = (float) ($sl->required_pcs ?? 0);
                    $remainIss  = (float) ($sl->issued_pcs ?? 0);

                    foreach ($allBundleLines as $bl) {
                        $share     = $totalQty > 0.0001
                            ? round((float) ($bl->qty_bundle ?? 0) / $totalQty * $reqTotal)
                            : 0.0;
                        $allocated = min($remainIss, $share);
                        $remainIss -= $allocated;
                        $cache[$bl->id][$sl->id] = ['issued' => $allocated, 'required' => $share];
                    }
                }
                $pickupAllocCache[$puId] = $cache;
            }

            $myAlloc    = $pickupAllocCache[$puId][$l->id] ?? null;
            $slById     = $allSupplyLines->keyBy('id');
            $bundleQty  = (float) ($l->qty_bundle ?? 0);

            // Jika $l->id tidak ada di cache (edge case), fallback → no block
            if ($myAlloc === null) {
                $l->supply_incomplete  = false;
                $l->supply_partial     = false;
                $l->supply_max_setor   = null;
                $l->supply_shortage_label = '';
                return $l;
            }

            // Supply lines yang kurang untuk bundle ini (berdasarkan alokasi greedy)
            $shortForLine = collect($myAlloc)->filter(
                fn($a) => (float) $a['issued'] + 0.000001 < (float) $a['required']
            );

            if ($shortForLine->isEmpty()) {
                // Bundle ini sudah fully covered → bebas setor
                $l->supply_incomplete  = false;
                $l->supply_partial     = false;
                $l->supply_max_setor   = null;
                $l->supply_shortage_label = '';
                return $l;
            }

            // Hitung max setor berdasarkan alokasi per bundle ini
            $supplyMaxSetor = $shortForLine->reduce(function ($carry, $a) use ($bundleQty) {
                if ((float) $a['required'] <= 0) return $carry;
                $canServe = (int) floor((float) $a['issued'] / (float) $a['required'] * $bundleQty + 0.00001);
                return $carry === null ? $canServe : min($carry, $canServe);
            }, null);
            $supplyMaxSetor = (int) min($supplyMaxSetor ?? 0, $remaining);

            $l->supply_incomplete = $supplyMaxSetor < 1;
            $l->supply_partial    = !$l->supply_incomplete;
            $l->supply_max_setor  = $l->supply_partial ? $supplyMaxSetor : 0;

            $l->supply_shortage_label = $shortForLine->map(function ($a, $slId) use ($slById) {
                $sl    = $slById->get((int) $slId);
                $short = (int) ceil((float) $a['required'] - (float) $a['issued']);
                if ($short > 0) {
                    return ($sl?->material?->code ?: 'Bahan') . ' kurang ' . number_format($short, 0, ',', '.') . ' pcs';
                }
                return null;
            })->filter()->implode('; ');

            return $l;
        })->filter(fn($l) => (float) $l->wip_stock > 0.000001)->values();

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

    private function applyLineSupplyStateToPickupLine(SewingPickupLine $line, $supplyLines, float $remaining): void
    {
        $epsilon = 0.000001;
        $shortLines = collect($supplyLines)->filter(
            fn($s) => (float) ($s->issued_qty ?? 0) + $epsilon < (float) ($s->required_qty ?? 0)
        );

        $line->supply_unmigrated = false;

        if ($shortLines->isEmpty()) {
            $line->supply_incomplete = false;
            $line->supply_partial = false;
            $line->supply_max_setor = null;
            $line->supply_shortage_label = '';
            return;
        }

        $bundleQty = max((float) ($line->qty_bundle ?? 0), 0.0);
        $supplyMaxSetor = $shortLines->reduce(function ($carry, $s) use ($bundleQty) {
            $requiredQty = (float) ($s->required_qty ?? 0);
            $issuedQty = (float) ($s->issued_qty ?? 0);
            if ($requiredQty <= 0.000001 || $bundleQty <= 0.000001) {
                return $carry;
            }

            $canServe = (int) floor(($issuedQty / $requiredQty) * $bundleQty + 0.00001);
            return $carry === null ? $canServe : min($carry, $canServe);
        }, null);

        $supplyMaxSetor = (int) min($supplyMaxSetor ?? 0, $remaining);

        $line->supply_incomplete = $supplyMaxSetor < 1;
        $line->supply_partial = !$line->supply_incomplete;
        $line->supply_max_setor = $line->supply_partial ? $supplyMaxSetor : 0;
        $line->supply_shortage_label = $shortLines->map(function ($s) {
            $shortQty = max((float) ($s->required_qty ?? 0) - (float) ($s->issued_qty ?? 0), 0);
            $uom = trim((string) ($s->uom ?: $s->materialItem?->unit ?: ''));
            return ($s->materialItem?->code ?: 'Bahan') . ' kurang ' . number_format($shortQty, 4, ',', '.') . ($uom ? ' ' . $uom : '');
        })->filter()->implode('; ');
    }

    private function assertPickupSuppliesCompleteForReturn($normalRows, $pickupLines): void
    {
        if ($normalRows->isEmpty()) {
            return;
        }

        // Developer mode: bypass supply validation HANYA saat real save (bukan dry run)
        // Saat dry run, validasi tetap jalan agar hasil dry run realistis
        if (auth()->check() && auth()->user()->isDeveloper() && !request()->boolean('dry_run')) {
            return;
        }

        $lineIds = $normalRows->pluck('sewing_pickup_line_id')->unique()->values()->all();
        $lineSupplyLines = SewingPickupLineSupplyLine::query()
            ->whereIn('sewing_pickup_line_id', $lineIds)
            ->with('materialItem:id,code,name')
            ->get()
            ->groupBy('sewing_pickup_line_id');

        $lineSupplyErrors = [];
        foreach ($normalRows as $row) {
            $lineId = (int) ($row['sewing_pickup_line_id'] ?? 0);
            $qtyTotal = (float) ($row['qty_ok'] ?? 0) + (float) ($row['qty_reject'] ?? 0);
            if ($lineId <= 0 || $qtyTotal <= 0.000001 || !$lineSupplyLines->has($lineId)) {
                continue;
            }

            $pickupLine = $pickupLines->get($lineId) ?? $pickupLines[$lineId] ?? null;
            if (!$pickupLine) {
                continue;
            }

            $supplies = $lineSupplyLines->get($lineId, collect());
            $minRatio = $supplies->reduce(function ($carry, $supply) {
                $requiredQty = (float) ($supply->required_qty ?? 0);
                if ($requiredQty <= 0.000001) {
                    return $carry;
                }

                $ratio = (float) ($supply->issued_qty ?? 0) / $requiredQty;
                return $carry === null ? $ratio : min($carry, $ratio);
            }, null);

            if ($minRatio === null || $minRatio >= 1) {
                continue;
            }

            $qtyBundle = (float) ($pickupLine->qty_bundle ?? 0);
            $returnedOk = (float) ($pickupLine->qty_returned_ok ?? 0);
            $returnedRj = (float) ($pickupLine->qty_returned_reject ?? 0);
            $directPick = (float) ($pickupLine->qty_direct_picked ?? 0);
            $progressAdj = (float) ($pickupLine->qty_progress_adjusted ?? 0);
            $alreadyProcessed = $returnedOk + $returnedRj + $directPick + $progressAdj;

            $coveredPcs = (int) floor(max($minRatio, 0) * $qtyBundle + 0.00001);
            $maxAllowed = max($coveredPcs - $alreadyProcessed, 0);
            $sku = $pickupLine?->finishedItem?->code ?: ('Line #' . $lineId);

            if ($maxAllowed < 1) {
                $lineSupplyErrors[] = "Bundle {$sku} belum bisa disetor karena kelengkapan jahit belum cukup.";
            } elseif ($qtyTotal > $maxAllowed + 0.001) {
                $lineSupplyErrors[] = "Bundle {$sku} maksimal setor {$maxAllowed} pcs sesuai kelengkapan jahit yang sudah dibawa.";
            }
        }

        if (!empty($lineSupplyErrors)) {
            throw ValidationException::withMessages([
                'results' => implode(' | ', array_unique($lineSupplyErrors)),
            ]);
        }

        $legacyRows = $normalRows
            ->filter(fn($row) => !$lineSupplyLines->has((int) ($row['sewing_pickup_line_id'] ?? 0)))
            ->values();

        if ($legacyRows->isEmpty()) {
            return;
        }

        $legacyLineIds = $legacyRows->pluck('sewing_pickup_line_id')->unique()->values()->all();
        $pickupIds = $pickupLines
            ->only($legacyLineIds)
            ->pluck('sewing_pickup_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($pickupIds)) {
            return;
        }

        $supplyLines = SewingPickupSupplyLine::query()
            ->whereIn('sewing_pickup_id', $pickupIds)
            ->with('material:id,code,name')
            ->get()
            ->groupBy('sewing_pickup_id');

        if ($supplyLines->isEmpty()) {
            return;
        }

        // Hitung max setor per pickup berdasarkan coverage supply (pakai issued_pcs / required_pcs)
        $maxSetorByPickupId = [];
        foreach ($supplyLines as $pickupId => $lines) {
            $shortLines = $lines->filter(
                fn($l) => (float) $l->issued_qty + 0.000001 < (float) $l->required_qty
            );
            if ($shortLines->isEmpty()) {
                $maxSetorByPickupId[(int) $pickupId] = null; // tidak dibatasi
                continue;
            }
            // Gunakan issued_pcs / required_pcs untuk menghindari floating point error
            // max setor dihitung per pickup line (akan disesuaikan dengan remaining di bawah)
            $minRatio = $shortLines->reduce(function ($carry, $l) {
                $reqPcs = (float) ($l->required_pcs ?? 0);
                $issPcs = (float) ($l->issued_pcs ?? 0);
                if ($reqPcs <= 0) return $carry;
                $ratio = $issPcs / $reqPcs;
                return $carry === null ? $ratio : min($carry, $ratio);
            }, null);
            $maxSetorByPickupId[(int) $pickupId] = $minRatio; // null = tak terbatas, 0 = hard block
        }

        // Validasi setiap row yang di-submit
        $errors = [];
        foreach ($legacyRows as $r) {
            $lineId   = (int) ($r['sewing_pickup_line_id'] ?? 0);
            $qtyOk    = (float) ($r['qty_ok'] ?? 0);
            $qtyRj    = (float) ($r['qty_reject'] ?? 0);
            $totalQty = $qtyOk + $qtyRj;
            if ($totalQty <= 0.000001) continue;

            $pickupLine = $pickupLines[$lineId] ?? null;
            $pickupId   = (int) ($pickupLine?->sewing_pickup_id ?? 0);
            if (!$pickupId || !array_key_exists($pickupId, $maxSetorByPickupId)) continue;

            $minRatio = $maxSetorByPickupId[$pickupId];
            if ($minRatio === null) continue; // supply lengkap, tidak dibatasi

            // Hitung remaining bundle (sebelum submission ini)
            $bundleQty   = (float) ($pickupLine?->qty_bundle ?? 0);
            $returnedOk  = (float) ($pickupLine?->qty_returned_ok ?? 0);
            $returnedRj  = (float) ($pickupLine?->qty_returned_reject ?? 0);
            $remaining   = max($bundleQty - $returnedOk - $returnedRj, 0);
            $maxAllowed  = (int) floor($minRatio * $remaining + 0.00001);

            $shortLabels = ($supplyLines[$pickupId] ?? collect())
                ->filter(fn($l) => (float) $l->issued_qty + 0.000001 < (float) $l->required_qty)
                ->map(fn($l) => $this->supplyShortLabel($l))
                ->implode('; ');

            if ($maxAllowed < 1) {
                $errors[] = 'Bahan jahit belum siap, tidak bisa setor sekarang. Hubungi bagian gudang.';
            } elseif ($totalQty > $maxAllowed + 0.001) {
                $errors[] = 'Jumlah setor terlalu banyak. Maksimal ' . $maxAllowed . ' pcs untuk pickup ini. Kurangi jumlah dan coba lagi.';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages([
                'results' => implode(' | ', array_unique($errors)),
            ]);
        }
    }

    private function supplyShortLabel(SewingPickupSupplyLine $line): string
    {
        $code      = $line->material?->code ?: ('Item #' . $line->material_item_id);
        $needPcs   = (float) ($line->required_pcs ?? 0);
        $issuedPcs = (float) ($line->issued_pcs ?? 0);
        if ($needPcs > 0) {
            return $code . ' kurang ' . number_format(max($needPcs - $issuedPcs, 0), 0, ',', '.') . ' pcs';
        }
        $uom   = (string) ($line->uom ?: '');
        $short = max((float) $line->required_qty - (float) $line->issued_qty, 0);
        return $code . ' kurang ' . number_format($short, 4, ',', '.') . ($uom ? ' ' . $uom : '');
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

        // ── DRY RUN MODE (developer only) ────────────────────────────────────
        // Semua validasi + DB writes jalan normal di dalam transaction,
        // tapi di akhir di-rollback → data tidak tersimpan.
        $isDryRun = $request->boolean('dry_run')
            && auth()->check()
            && auth()->user()->isDeveloper();

        $postedSewingReturn = null;

        try {
        $response = DB::transaction(function () use ($validated, $date, $operatorId, $isDryRun, &$postedSewingReturn): RedirectResponse {

            $wipSewWarehouse = Warehouse::query()
                ->whereIn('code', ['WIP-SEW', 'WH-SEWING'])
                ->first();

            if (!$wipSewWarehouse) {
                throw ValidationException::withMessages(['results' => 'Gudang WIP-SEW / WH-SEWING belum ada.']);
            }

            $wipFinWarehouse = Warehouse::query()->where('code', 'WIP-FIN')->first();
            $sewingQcFlowActive = Route::has('production.qc.sewing.edit') && $wipFinWarehouse;

            if (!$wipFinWarehouse && $sewingQcFlowActive) {
                throw ValidationException::withMessages(['destination_warehouse_id' => 'Gudang WIP-FIN belum ada.']);
            }

            if ($sewingQcFlowActive) {
                $destWarehouse = $wipFinWarehouse;
            } else {
                $destWarehouse = Warehouse::query()->where('code', 'WH-PRD')->first();

                if (!$destWarehouse) {
                    throw ValidationException::withMessages(['destination_warehouse_id' => 'Gudang WH-PRD belum ada.']);
                }

                $requestedDestId = (int) $validated['destination_warehouse_id'];
                if ($requestedDestId !== (int) $destWarehouse->id) {
                    throw ValidationException::withMessages(['destination_warehouse_id' => 'Sewing Return hanya boleh masuk ke WH-PRD.']);
                }
            }

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
                throw ValidationException::withMessages(['results' => 'Minimal isi 1 baris setoran.']);
            }

            // lock pickup lines
            $lineIds = $rawResults->pluck('sewing_pickup_line_id')->unique()->values()->all();

            $pickupLines = SewingPickupLine::query()
                ->whereIn('id', $lineIds)
                ->whereNull('voided_at')
                ->with('finishedItem:id,code,name')
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

            if ($sewingQcFlowActive && $normalRows->contains(fn($r) => (float) ($r['qty_reject'] ?? 0) > 0.000001)) {
                throw ValidationException::withMessages([
                    'results' => 'Reject jahit diisi lewat QC Jahit. Setoran Jahit normal hanya boleh mengisi qty setor.',
                ]);
            }

            $this->assertPickupSuppliesCompleteForReturn($normalRows, $pickupLines);

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
                        'results' => "Qty setor melebihi sisa pickup (line #{$pl->id}). Sisa: {$remainingPickup}.",
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
                'status' => (new SewingReturn())->isFillable('status')
                    ? ($sewingQcFlowActive && $normalRows->isNotEmpty() ? 'pending_qc' : 'posted')
                    : null,
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

                // Normal sewing return menunggu QC. Stok tetap di WIP-SEW sampai QC approve.
                if ($sewingQcFlowActive && $sourceRejectLineId <= 0 && $sourceFinishingLineId <= 0) {
                    continue;
                }

                // Reject jahit: OUT WIP-SEW lalu IN REJ-SEW.
                if ($qtyRj > 0.000001) {
                    if (!$rejectWarehouse) {
                        throw ValidationException::withMessages([
                            'results' => 'Gudang REJ-SEW belum ada. Reject jahit harus masuk gudang Reject Sewing.',
                        ]);
                    }

                    $rejectUnitCost = (float) $this->inventory->getItemIncomingUnitCost(
                        warehouseId: $wipSewWarehouse->id,
                        itemId: $itemId,
                    );

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
                        unitCostOverride: $rejectUnitCost > 0 ? $rejectUnitCost : null,
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
                        unitCost: $rejectUnitCost > 0 ? $rejectUnitCost : null,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );
                }

                if (($sourceRejectLineId > 0 || $sourceFinishingLineId > 0) && $qtyOk > 0.000001) {
                    // REJ-SEW sudah membawa nilai material + upah dari pickup.
                    // Saat rework berhasil, cukup pindahkan nilai yang sudah ada.
                    $unitCostRejSew = (float) $this->inventory->getItemIncomingUnitCost(
                        warehouseId: $rejectWarehouse->id,
                        itemId: $itemId
                    );
                    $unitCostFinRework = round($unitCostRejSew, 10);

                    $this->inventory->stockOut(
                        warehouseId: $rejectWarehouse->id,
                        itemId: $itemId,
                        qty: $qtyOk,
                        date: $date,
                        sourceType: 'sewing_reject_rework_ok',
                        sourceId: $sewingReturn->id,
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
                        sourceId: $sewingReturn->id,
                        notes: "Setor ulang reject {$sewingReturn->code} IN {$destWarehouse->code}",
                        lotId: null,
                        unitCost: $unitCostFinRework,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );

                    $okByBundle[$bundleId] = ($okByBundle[$bundleId] ?? 0) + $qtyOk;
                    continue;
                }

                // OK: OUT WIP-SEW → IN destination.
                // WIP-SEW sudah membawa material + upah sejak Ambil Jahit.
                if ($qtyOk > 0.000001) {
                    $unitCostWipSew = (float) $this->inventory->getItemIncomingUnitCost(
                        warehouseId: $wipSewWarehouse->id,
                        itemId: $itemId
                    );
                    $unitCostFin = round($unitCostWipSew, 10);

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
                        notes: "Sewing Return {$sewingReturn->code} (OK) IN {$destWarehouse->code}",
                        lotId: null,
                        unitCost: $unitCostFin,
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

                // Posisi hilir pasca-jahit mengikuti destination.
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
                $sewingReturn->status = ($sewingQcFlowActive && $normalRows->isNotEmpty()) ? 'pending_qc' : 'posted';
                $sewingReturn->save();
            }

            if ($sewingQcFlowActive && $normalRows->isNotEmpty()) {
                $this->createPendingSewingQcResults($sewingReturn, $rawResults, $pickupLines, $bundlesMap, $date, $operatorId);
            }

            // ── DRY RUN: kumpulkan info lalu throw → transaction di-rollback ──
            if ($isDryRun) {
                throw new \App\Exceptions\DryRunRollbackException($sewingReturn);
            }

            $postedSewingReturn = $sewingReturn;

            return redirect()
                ->route('production.sewing.returns.show', $sewingReturn)
                ->with('success', 'Sewing Return berhasil disimpan.');
        });

        if ($postedSewingReturn && ($postedSewingReturn->status ?? null) !== 'pending_qc') {
            foreach ([
                'postSewingReturnOk',
                'postSewingReturnReject',
                'postSewingReworkOk',
            ] as $method) {
                try {
                    $this->journal->{$method}($postedSewingReturn);
                } catch (\Throwable $journalError) {
                    Log::warning("Gagal membuat jurnal {$method}", [
                        'sewing_return_id' => $postedSewingReturn->id,
                        'message' => $journalError->getMessage(),
                    ]);
                }
            }
        }

        return $response;
        } catch (\App\Exceptions\DryRunRollbackException $e) {
            // Transaction sudah di-rollback otomatis. Tampilkan hasil dry run.
            $sr = $e->sewingReturn;
            return back()
                ->withInput()
                ->with('dev_dry_run', [
                    'ok'      => true,
                    'code'    => $sr->code ?? '(generated)',
                    'date'    => ($sr->date ? \Carbon\Carbon::parse($sr->date)->format('d/m/Y') : \Carbon\Carbon::parse($date)->format('d/m/Y')),
                    'lines'   => $sr->lines?->map(fn($l) => [
                        'item'       => $l->finishedItem?->code ?? $l->finished_item_id,
                        'qty_ok'     => $l->qty_ok,
                        'qty_reject' => $l->qty_reject,
                    ])->toArray() ?? [],
                    'message' => 'Dry run selesai — validasi LOLOS, transaksi di-rollback. Data tidak tersimpan.',
                ]);
        }
    }

    private function createPendingSewingQcResults(
        SewingReturn $sewingReturn,
        $rawResults,
        $pickupLines,
        $bundlesMap,
        string $date,
        ?int $operatorId
    ): void {
        foreach ($rawResults as $row) {
            if ((int) ($row['source_reject_return_line_id'] ?? 0) > 0 || (int) ($row['source_finishing_job_line_id'] ?? 0) > 0) {
                continue;
            }

            $pickupLine = $pickupLines->get((int) ($row['sewing_pickup_line_id'] ?? 0));
            if (!$pickupLine) {
                continue;
            }

            $bundleId = (int) ($pickupLine->cutting_job_bundle_id ?? 0);
            if ($bundleId <= 0) {
                continue;
            }

            $bundle = $bundlesMap->get($bundleId);

            QcResult::updateOrCreate(
                [
                    'stage' => QcResult::STAGE_SEWING,
                    'sewing_job_id' => (int) $sewingReturn->id,
                    'cutting_job_bundle_id' => $bundleId,
                ],
                [
                    'cutting_job_id' => $bundle?->cutting_job_id,
                    'finishing_job_id' => null,
                    'qc_date' => $date,
                    'qty_ok' => (float) ($row['qty_ok'] ?? 0),
                    'qty_reject' => (float) ($row['qty_reject'] ?? 0),
                    'reject_reason' => null,
                    'operator_id' => $operatorId,
                    'status' => 'pending',
                    'notes' => trim((string) ($row['notes'] ?? '')) ?: 'Menunggu QC jahit',
                ]
            );
        }
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

        $returnId = (int) $return->id;

        $response = DB::transaction(function () use ($return, $validated): RedirectResponse {

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

            // Tujuan Sewing Return adalah WH-PRD. Data lama tetap memakai destination_warehouse_id bila ada.
            $destWarehouse = null;
            if (!empty($return->destination_warehouse_id)) {
                $destWarehouse = Warehouse::query()->whereKey((int) $return->destination_warehouse_id)->first();
            }
            if (!$destWarehouse) {
                $destWarehouse = Warehouse::query()->where('code', 'WH-PRD')->first();
            }
            if (!$destWarehouse) {
                throw ValidationException::withMessages(['reason' => 'Gudang tujuan SR tidak ditemukan (WH-PRD).']);
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

            // lock stok di gudang tujuan (WH-PRD; data lama mengikuti destination_warehouse_id tersimpan)
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

        // Void jurnal di LUAR transaction supaya tidak rollback kalau journal gagal.
        // Tiap source dihandle terpisah supaya 1 gagal tidak batalkan yang lain.
        foreach ([
            JournalService::SRC_SEWING_RETURN_OK     => "VOID Setor Jahit OK",
            JournalService::SRC_SEWING_RETURN_REJECT => "VOID Setor Jahit Reject",
            JournalService::SRC_SEWING_REWORK_OK     => "VOID Setor Ulang Rework",
        ] as $srcType => $reason) {
            try {
                $this->journal->voidBySource($srcType, $returnId, $reason);
            } catch (\Throwable $e) {
                Log::warning("Gagal void jurnal [{$srcType}] sewing_return #{$returnId}", [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $response;
    }
}
