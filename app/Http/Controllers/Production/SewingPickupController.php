<?php

namespace App\Http\Controllers\Production;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\SewingPickup;
use App\Models\SewingPickupLine;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SewingPickupController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    public function index(Request $request)
    {
        $pickups = SewingPickup::query()
            ->with([
                'warehouse',
                'operator',
                'lines',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('production.sewing_pickups.index', [
            'pickups' => $pickups,
        ]);
    }

    public function show(SewingPickup $pickup): View
    {
        $pickup->load([
            'warehouse',
            'operator',
            'lines.bundle.finishedItem',
            'lines.bundle.cuttingJob.lot.item',
        ]);

        $epsilon = 0.000001;

        $totalBundles = $pickup->lines->count();
        $totalQtyPickup = (float) $pickup->lines->sum('qty_bundle');
        $totalReturnOk = (float) $pickup->lines->sum('qty_returned_ok');
        $totalReturnReject = (float) $pickup->lines->sum('qty_returned_reject');

        $totalDirectPick = (float) $pickup->lines->sum(fn($l) => (float) ($l->qty_direct_picked ?? 0));
        $totalProgressAdjusted = (float) $pickup->lines->sum(fn($l) => (float) ($l->qty_progress_adjusted ?? 0));

        $totalProgressAll = $totalReturnOk + $totalReturnReject + $totalDirectPick + $totalProgressAdjusted;

        $overallProgress = $totalQtyPickup > 0
        ? round(($totalProgressAll / $totalQtyPickup) * 100, 1)
        : 0.0;

        // Stats per line
        $notReturnedCount = $pickup->lines->filter(function ($l) use ($epsilon) {
            $progress = (float) ($l->qty_returned_ok ?? 0)
             + (float) ($l->qty_returned_reject ?? 0)
             + (float) ($l->qty_direct_picked ?? 0)
             + (float) ($l->qty_progress_adjusted ?? 0);
            return $progress <= $epsilon;
        })->count();

        $fullReturnedCount = $pickup->lines->filter(function ($l) use ($epsilon) {
            $picked = (float) ($l->qty_bundle ?? 0);
            $progress = (float) ($l->qty_returned_ok ?? 0)
             + (float) ($l->qty_returned_reject ?? 0)
             + (float) ($l->qty_direct_picked ?? 0)
             + (float) ($l->qty_progress_adjusted ?? 0);

            return $picked > 0 && ($picked - $progress) <= $epsilon;
        })->count();

        $partialReturnedCount = $totalBundles - $notReturnedCount - $fullReturnedCount;

        return view('production.sewing_pickups.show', [
            'pickup' => $pickup,
            'totalBundles' => $totalBundles,
            'totalQtyPickup' => $totalQtyPickup,
            'totalReturnOk' => $totalReturnOk,
            'totalReturnReject' => $totalReturnReject,
            'totalDirectPick' => $totalDirectPick,
            'totalProgressAdjusted' => $totalProgressAdjusted,
            'totalProgressAll' => $totalProgressAll,
            'overallProgress' => $overallProgress,
            'notReturnedCount' => $notReturnedCount,
            'partialReturnedCount' => $partialReturnedCount,
            'fullReturnedCount' => $fullReturnedCount,
        ]);
    }

    /**
     * Halaman list bundle siap dijahit (opsional).
     */
    public function bundlesReady()
    {
        $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');

        $bundles = CuttingJobBundle::query()
            ->with([
                'finishedItem',
                'cuttingJob.lot.item',
                'latestCuttingQc',
            ])
            ->where('wip_warehouse_id', $wipCutWarehouseId)
            ->where('wip_qty', '>', 0)
            ->whereColumn('sewing_picked_qty', '<', 'wip_qty')
            ->orderBy('id')
            ->get();

        return view('production.sewing_pickups.bundles_ready', [
            'bundles' => $bundles,
        ]);
    }

    /**
     * Form create Sewing Pickup.
     * Bundles yang muncul:
     * - wip_warehouse_id = WIP-CUT
     * - wip_qty > sewing_picked_qty
     * - qty_qc_ok (atau QC cutting) masih ada sisa.
     */
    public function create()
    {
        $operators = Employee::where('role', 'sewing')
            ->orderBy('code')
            ->get();

        $warehouses = Warehouse::orderBy('code')->get();

        $wipCutId = Warehouse::where('code', 'WIP-CUT')->value('id');
        $wipSewWarehouse = Warehouse::where('code', 'WIP-SEW')->firstOrFail();

        $bundles = CuttingJobBundle::with(['finishedItem', 'cuttingJob.lot.item', 'qcResults'])
            ->readyForSewing($wipCutId)
            ->get();

        return view('production.sewing_pickups.create', [
            'operators' => $operators,
            'warehouses' => $warehouses,
            'wipSewWarehouse' => $wipSewWarehouse,
            'bundles' => $bundles,
        ]);
    }

    /**
     * Simpan Sewing Pickup.
     * Pattern:
     * - OUT: WIP-CUT (WIP Cutting)
     * - IN : gudang sewing (biasanya WIP-SEW)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'], // gudang sewing (WIP-SEW)
            'operator_id' => ['required', 'exists:employees,id'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.bundle_id' => ['required', 'exists:cutting_job_bundles,id'],
            'lines.*.qty_bundle' => ['nullable', 'numeric', 'min:0'],
        ], [
            'lines.required' => 'Minimal satu baris bundle harus diisi.',
            'lines.*.bundle_id.required' => 'Bundle tidak valid.',
            'lines.*.qty_bundle.required' => 'Qty pickup wajib diisi.',
        ]);

        DB::transaction(function () use ($validated) {

            $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');
            if (!$wipCutWarehouseId) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'Gudang WIP-CUT belum dikonfigurasi. Pastikan ada warehouse dengan code "WIP-CUT".',
                ]);
            }

            $sewingWarehouseId = (int) $validated['warehouse_id']; // biasanya WIP-SEW
            $date = $validated['date'];
            $code = CodeGenerator::generate('SWP');

            /** @var SewingPickup $pickup */
            $pickup = SewingPickup::create([
                'code' => $code,
                'date' => $date,
                'warehouse_id' => $sewingWarehouseId,
                'operator_id' => $validated['operator_id'],
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
            ]);

            $createdLines = 0;
            $epsilon = 0.000001;

            foreach ($validated['lines'] as $row) {

                $qty = (float) ($row['qty_bundle'] ?? 0);
                if ($qty <= $epsilon) {
                    continue;
                }

                /** @var CuttingJobBundle|null $bundle */
                $bundle = CuttingJobBundle::with([
                    'qcResults' => function ($q) {
                        $q->where('stage', 'cutting');
                    },
                    'cuttingJob',
                ])->find($row['bundle_id']);

                if (!$bundle) {
                    continue;
                }

                // ✅ WAJIB: bundle ini harus berada di WIP-CUT
                if ((int) $bundle->wip_warehouse_id !== (int) $wipCutWarehouseId) {
                    throw ValidationException::withMessages([
                        'lines' => "Bundle {$bundle->bundle_code} bukan berada di gudang WIP-CUT.",
                    ]);
                }

                // WIP per bundle
                $wipQty = (float) ($bundle->wip_qty ?? 0);
                $alreadyPicked = (float) ($bundle->sewing_picked_qty ?? 0);
                $maxFromWip = max($wipQty - $alreadyPicked, 0.0);

                // QC Cutting terakhir
                $lastQc = $bundle->qcResults
                ? $bundle->qcResults->sortByDesc('qc_date')->first()
                : null;

                // Batas qty berdasarkan QC
                $maxQtyOk = $lastQc && $lastQc->qty_ok !== null
                ? (float) $lastQc->qty_ok
                : (float) $bundle->qty_pcs;

                if ($maxQtyOk <= $epsilon) {
                    continue;
                }

                $remainingByQc = max($maxQtyOk - $alreadyPicked, 0.0);
                $remaining = min($maxFromWip, $remainingByQc);

                if ($remaining <= $epsilon) {
                    continue;
                }

                if ($qty > $remaining) {
                    $qty = $remaining;
                }

                if ($qty <= $epsilon) {
                    continue;
                }

                // ✅ Ambil unit cost dulu (penting supaya bisa disimpan ke line)
                $unitCostPerPiece = (float) $this->inventory->getItemIncomingUnitCost(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $bundle->finished_item_id,
                );
                if ($unitCostPerPiece <= 0) {
                    $unitCostPerPiece = 0;
                }

                // 🔹 Simpan detail sewing pickup (simpan unit_cost)
                SewingPickupLine::create([
                    'sewing_pickup_id' => $pickup->id,
                    'cutting_job_bundle_id' => $bundle->id,
                    'finished_item_id' => $bundle->finished_item_id,
                    'qty_bundle' => $qty,
                    'unit_cost' => $unitCostPerPiece, // ✅ simpan cost
                    'status' => 'in_progress',
                ]);

                // 🔹 UPDATE qty pick di bundle
                $newPicked = $alreadyPicked + $qty;
                if ($newPicked > $maxQtyOk) {
                    $newPicked = $maxQtyOk;
                }

                $bundle->sewing_picked_qty = $newPicked;

                if ($newPicked >= $maxQtyOk) {
                    $bundle->status = 'in_sewing'; // sesuaikan jika enum berbeda
                }

                $bundle->save();

                // =======================
                // INVENTORY MOVEMENT
                // =======================
                $notes = "Sewing pickup {$pickup->code} - bundle {$bundle->bundle_code}";

                // 1️⃣ OUT dari WIP-CUT
                $this->inventory->stockOut(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $bundle->finished_item_id,
                    qty: $qty,
                    date: $date,
                    sourceType: SewingPickup::class,
                    sourceId: $pickup->id,
                    notes: $notes,
                    allowNegative: false,
                    lotId: null,
                    unitCostOverride: $unitCostPerPiece,
                    affectLotCost: false,
                );

                // 2️⃣ IN ke gudang sewing (WIP-SEW)
                $this->inventory->stockIn(
                    warehouseId: $sewingWarehouseId,
                    itemId: $bundle->finished_item_id,
                    qty: $qty,
                    date: $date,
                    sourceType: SewingPickup::class,
                    sourceId: $pickup->id,
                    notes: $notes,
                    lotId: null,
                    unitCost: $unitCostPerPiece,
                    affectLotCost: false,
                );

                $createdLines++;
            }

            if ($createdLines === 0) {
                throw ValidationException::withMessages([
                    'lines' => 'Minimal satu bundle harus punya Qty Pickup > 0 dan qty ready yang masih tersisa.',
                ]);
            }
        });

        return redirect()
            ->route('production.sewing.returns.create')
            ->with('success', 'Sewing pickup berhasil dibuat. Stok sudah dipindahkan dari WIP-CUT ke gudang sewing.');
    }

    /**
     * AJAX: filter bundles ready untuk picker.
     */
    public function ajaxReadyBundles(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $color = trim((string) $request->get('color', ''));
        $size = trim((string) $request->get('size', ''));
        $minReady = $request->get('min_ready');
        $maxReady = $request->get('max_ready');

        $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');

        $bundlesQuery = CuttingJobBundle::with([
            'finishedItem',
            'cuttingJob.lot.item',
            'qcResults',
        ])
            ->readyForSewing($wipCutWarehouseId);

        // SEARCH TEXT
        if ($q !== '') {
            $term = '%' . $q . '%';

            $bundlesQuery->where(function ($qq) use ($term) {
                $qq->where('bundle_code', 'like', $term)
                    ->orWhereHas('finishedItem', function ($q2) use ($term) {
                        $q2->where('code', 'like', $term)
                            ->orWhere('name', 'like', $term);
                    })
                    ->orWhereHas('cuttingJob.lot', function ($q2) use ($term) {
                        $q2->where('code', 'like', $term);
                    })
                    ->orWhereHas('cuttingJob.lot.item', function ($q2) use ($term) {
                        $q2->where('code', 'like', $term)
                            ->orWhere('name', 'like', $term);
                    });
            });
        }

        // FILTER WARNA
        if ($color !== '') {
            $bundlesQuery->whereHas('finishedItem', function ($q2) use ($color) {
                $q2->where('color_code', $color); // sesuaikan field warna
            });
        }

        // FILTER UKURAN
        if ($size !== '') {
            $bundlesQuery->whereHas('finishedItem', function ($q2) use ($size) {
                $q2->where('size_code', $size); // sesuaikan field size
            });
        }

        $bundles = $bundlesQuery
            ->orderBy('id')
            ->get();

        $minReadyF = is_null($minReady) || $minReady === '' ? null : (float) $minReady;
        $maxReadyF = is_null($maxReady) || $maxReady === '' ? null : (float) $maxReady;

        $displayBundles = $bundles->filter(function (CuttingJobBundle $b) use ($minReadyF, $maxReadyF) {
            $qtyRemain = (float) $b->qty_ready_for_sewing;

            if ($qtyRemain <= 0) {
                return false;
            }

            if (!is_null($minReadyF) && $qtyRemain < $minReadyF) {
                return false;
            }

            if (!is_null($maxReadyF) && $qtyRemain > $maxReadyF) {
                return false;
            }

            $b->computed_qty_remain = $qtyRemain;

            return true;
        })->values();

        $totalBundlesReady = $displayBundles->count();
        $totalQtyReady = $displayBundles->sum(function ($b) {
            return (float) ($b->computed_qty_remain ?? 0);
        });

        $html = view('production.sewing.pickups._bundle_picker_rows', [
            'displayBundles' => $displayBundles,
            'oldLines' => [],
            'preselectedBundleId' => null,
        ])->render();

        return response()->json([
            'html' => $html,
            'total_bundles' => $totalBundlesReady,
            'total_ready' => $totalQtyReady,
            'total_ready_formatted' => number_format($totalQtyReady, 2, ',', '.'),
        ]);
    }

    public function void(Request $request, SewingPickup $pickup)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:150'],
        ]);

        $pickupId = $pickup->id;

        DB::transaction(function () use ($pickupId, $validated) {

            $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');
            if (!$wipCutWarehouseId) {
                throw ValidationException::withMessages([
                    'pickup' => 'Gudang WIP-CUT belum dikonfigurasi.',
                ]);
            }

            // lock header + lines
            $pickup = SewingPickup::with('lines')
                ->lockForUpdate()
                ->findOrFail($pickupId);

            if ($pickup->status === 'void') {
                throw ValidationException::withMessages([
                    'pickup' => 'Pickup sudah di-VOID.',
                ]);
            }

            $epsilon = 0.000001;

            // Validasi: belum kepakai proses lain
            foreach ($pickup->lines as $line) {
                $used =
                (float) ($line->qty_returned_ok ?? 0) +
                (float) ($line->qty_returned_reject ?? 0) +
                (float) ($line->qty_direct_picked ?? 0) +
                (float) ($line->qty_progress_adjusted ?? 0);

                if ($used > $epsilon) {
                    throw ValidationException::withMessages([
                        'pickup' => "Pickup sudah dipakai proses lain (line #{$line->id}). Tidak bisa VOID.",
                    ]);
                }
            }

            $sewingWarehouseId = (int) $pickup->warehouse_id;
            $date = $pickup->date;

            foreach ($pickup->lines as $line) {
                $qty = (float) ($line->qty_bundle ?? 0);
                if ($qty <= $epsilon) {
                    continue;
                }

                // lock bundle
                $bundle = CuttingJobBundle::lockForUpdate()->findOrFail($line->cutting_job_bundle_id);

                // Cek konsistensi supaya tidak minus
                if ($qty > (float) $bundle->sewing_picked_qty + $epsilon) {
                    throw ValidationException::withMessages([
                        'pickup' => "Data tidak konsisten (bundle {$bundle->bundle_code}). sewing_picked_qty lebih kecil dari qty pickup.",
                    ]);
                }

                // Reverse picked qty
                $bundle->sewing_picked_qty = max(((float) $bundle->sewing_picked_qty) - $qty, 0);
                $bundle->save();

                // Reverse inventory movement: OUT dari WIP-SEW, IN ke WIP-CUT
                $notes = "VOID Sewing pickup {$pickup->code} - bundle {$bundle->bundle_code}";

                // gunakan unit_cost yang disimpan saat pickup dibuat
                $unitCost = (float) ($line->unit_cost ?? 0);
                if ($unitCost < 0) {
                    $unitCost = 0;
                }

                $this->inventory->stockOut(
                    warehouseId: $sewingWarehouseId,
                    itemId: $line->finished_item_id,
                    qty: $qty,
                    date: $date,
                    sourceType: SewingPickup::class,
                    sourceId: $pickup->id,
                    notes: $notes,
                    allowNegative: false,
                    lotId: null,
                    unitCostOverride: $unitCost,
                    affectLotCost: false,
                );

                $this->inventory->stockIn(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $line->finished_item_id,
                    qty: $qty,
                    date: $date,
                    sourceType: SewingPickup::class,
                    sourceId: $pickup->id,
                    notes: $notes,
                    lotId: null,
                    unitCost: $unitCost,
                    affectLotCost: false,
                );

                // Mark line void
                $line->status = 'void';
                $line->save();
            }

            // Mark header void (pastikan kolom ini ada di DB)
            $pickup->status = 'void';
            $pickup->void_reason = $validated['reason'];
            $pickup->voided_at = now();
            $pickup->voided_by = auth()->id();
            $pickup->save();
        });

        return redirect()
            ->route('production.sewing.pickups.show', $pickupId)
            ->with('success', 'Pickup berhasil di-VOID. Stok sudah dibalik ke WIP-CUT.');
    }

}
