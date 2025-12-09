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

    public function show(SewingPickup $pickup)
    {
        $pickup->load([
            'warehouse',
            'operator',
            'lines.bundle.finishedItem',
            'lines.bundle.cuttingJob.lot.item',
        ]);

        return view('production.sewing_pickups.show', [
            'pickup' => $pickup,
        ]);
    }

    public function bundlesReady()
    {
        $bundles = CuttingJobBundle::with([
            'finishedItem',
            'cuttingJob.lot.item',
            'latestCuttingQc', // relation kecil yang tadi
        ])
            ->readyForSewing()
            ->orderBy('id')
            ->get();

        return view('production.sewing_pickups.bundles_ready', [
            'bundles' => $bundles,
        ]);
    }

    public function create()
    {
        $operators = Employee::where('role', 'sewing')
            ->orderBy('code')
            ->get();

        // Kalau masih mau pakai list semua gudang untuk keperluan lain:
        $warehouses = Warehouse::orderBy('code')->get();

        $wipCutId = Warehouse::where('code', 'WIP-CUT')->value('id');
        $wipSewWarehouse = Warehouse::where('code', 'WIP-SEW')->firstOrFail();

        $bundles = CuttingJobBundle::readyForSewing($wipCutId)
            ->with(['finishedItem', 'cuttingJob.lot.item', 'qcResults'])
            ->get();

        return view('production.sewing_pickups.create', [
            'operators' => $operators,
            'warehouses' => $warehouses,
            'wipSewWarehouse' => $wipSewWarehouse,
            'bundles' => $bundles,
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'], // gudang sewing (WIP-SEW)
            'operator_id' => ['required', 'exists:employees,id'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.bundle_id' => ['required', 'exists:cutting_job_bundles,id'],
            'lines.*.qty_bundle' => ['nullable', 'numeric', 'min:0'], // boleh 0, nanti di-skip
        ], [
            'lines.required' => 'Minimal satu baris bundle harus diisi.',
            'lines.*.bundle_id.required' => 'Bundle tidak valid.',
            'lines.*.qty_bundle.required' => 'Qty pickup wajib diisi.',
        ]);

        DB::transaction(function () use ($validated) {

            // 🔎 Cari gudang WIP-CUT (sumber stok WIP Cutting)
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
                'status' => 'draft', // nanti bisa ada tombol POSTED
                'notes' => $validated['notes'] ?? null,
            ]);

            $createdLines = 0;

            foreach ($validated['lines'] as $row) {

                $qty = (float) ($row['qty_bundle'] ?? 0);
                if ($qty <= 0) {
                    continue; // baris kosong, skip
                }

                // Ambil bundle + QC Cutting (stage = cutting)
                /** @var \App\Models\CuttingJobBundle|null $bundle */
                $bundle = CuttingJobBundle::with([
                    'qcResults' => function ($q) {
                        $q->where('stage', 'cutting');
                    },
                    'cuttingJob',
                ])->find($row['bundle_id']);

                if (!$bundle) {
                    continue;
                }

                // QC Cutting terakhir untuk bundle ini
                $lastQc = $bundle->qcResults
                    ->sortByDesc('qc_date')
                    ->first();

                // Batas maksimum qty hasil QC:
                // - kalau ada QC → pakai qty_ok
                // - kalau belum ada QC → fallback ke qty_pcs
                if ($lastQc && $lastQc->qty_ok !== null) {
                    $maxQtyOk = (float) $lastQc->qty_ok;
                } else {
                    $maxQtyOk = (float) $bundle->qty_pcs;
                }

                if ($maxQtyOk <= 0) {
                    // tidak ada qty OK yang bisa dijahit
                    continue;
                }

                // ========= LOGIKA “STOK BUNDLE TERSISA” =========

// 1️⃣ Total yg sudah pernah dipick dari bundle ini (semua SWP lama)
                $alreadyPicked = (float) SewingPickupLine::query()
                    ->where('cutting_job_bundle_id', $bundle->id)
                    ->sum('qty_bundle');

// 2️⃣ Sisa qty berdasarkan QC (atau qty_pcs kalau belum ada QC)
                $remainingByQc = max($maxQtyOk - $alreadyPicked, 0);

// 3️⃣ Cross-check dengan stok WIP-CUT (fisik)
                $wipCutOnHand = $this->inventory->getOnHandQty(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $bundle->finished_item_id,
                );

// Sisa yg boleh dipick = MIN( sisa QC , stok WIP-CUT )
                $remaining = min($remainingByQc, $wipCutOnHand);

                if ($remaining <= 0) {
                    // bundle ini sudah habis secara logis
                    continue;
                }

// Batasi qty request ke sisa yang masih ada
                if ($qty > $remaining) {
                    $qty = $remaining;
                }

                if ($qty <= 0) {
                    continue;
                }

                // 🔹 Simpan detail sewing pickup
                SewingPickupLine::create([
                    'sewing_pickup_id' => $pickup->id,
                    'cutting_job_bundle_id' => $bundle->id,
                    'finished_item_id' => $bundle->finished_item_id,
                    'qty_bundle' => $qty,
                    'status' => 'in_progress',
                ]);

                // 🔹 UPDATE “STOK” BUNDLE (akumulasi qty yang sudah dipick)
                $newPicked = $alreadyPicked + $qty;

                // Clamp supaya tidak lebih dari maxQtyOk (jaga-jaga)
                if ($newPicked > $maxQtyOk) {
                    $newPicked = $maxQtyOk;
                }

                $bundle->sewing_picked_qty = $newPicked;

                // Opsional: update status bundle kalau sudah habis
                if ($newPicked >= $maxQtyOk) {
                    // misal: bundle sudah full dikirim ke sewing
                    $bundle->status = 'in_sewing'; // sesuaikan dengan enum/status kamu
                }

                $bundle->save();

                // =======================
                // =======================
// 🔁 BLOK COSTING / INVENTORY
// =======================

                $notes = "Sewing pickup {$pickup->code} - bundle {$bundle->bundle_code}";

// 1️⃣ Ambil unit_cost per pcs dari WIP-CUT (berdasarkan mutasi incoming WIP-CUT)
                $unitCostPerPiece = $this->inventory->getItemIncomingUnitCost(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $bundle->finished_item_id,
                );

                if ($unitCostPerPiece === null || $unitCostPerPiece <= 0) {
                    $unitCostPerPiece = 0; // atau biarkan null kalau mau jelas bedanya "belum dikosting"
                }

// 1) Keluar dari WIP-CUT (WIP → WIP, TANPA LOT)
                $this->inventory->stockOut(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $bundle->finished_item_id,
                    qty: $qty,
                    date: $date,
                    sourceType: SewingPickup::class, // atau 'sewing_pickup' kalau kamu pakai string
                    sourceId: $pickup->id,
                    notes: $notes,
                    allowNegative: false,
                    lotId: null, // ✅ WIP tidak pakai LOT
                    unitCostOverride: $unitCostPerPiece, // optional, tergantung implementasi InventoryService
                    affectLotCost: false, // ✅ jangan sentuh LotCost kain
                );

// 2) Masuk ke WIP-SEW (still WIP, TANPA LOT)
                $this->inventory->stockIn(
                    warehouseId: $sewingWarehouseId,
                    itemId: $bundle->finished_item_id,
                    qty: $qty,
                    date: $date,
                    sourceType: SewingPickup::class,
                    sourceId: $pickup->id,
                    notes: $notes,
                    lotId: null, // ✅ tidak pakai LOT
                    unitCost: $unitCostPerPiece, // cost WIP ikut pindah
                    affectLotCost: false, // ✅ tidak mengubah LotCost
                );

                $createdLines++;
            }
            // Kalau tidak ada satupun line valid, batal & lempar error
            if ($createdLines === 0) {
                throw ValidationException::withMessages([
                    'lines' => 'Minimal satu bundle harus punya Qty Pickup > 0 dan qty OK dari QC yang masih tersisa.',
                ]);
            }
        });

        return redirect()
            ->route('production.sewing_returns.create')
            ->with('success', 'Sewing pickup berhasil dibuat. Stok sudah dipindahkan dari WIP-CUT ke gudang sewing dengan costing yang mengikuti saldo WIP-CUT.');
    }

    public function ajaxReadyBundles(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $color = trim((string) $request->get('color', ''));
        $size = trim((string) $request->get('size', ''));
        $minReady = $request->get('min_ready');
        $maxReady = $request->get('max_ready');

        $bundlesQuery = CuttingJobBundle::query()
            ->with([
                'finishedItem',
                'cuttingJob.lot.item',
                'qcResults',
            ]);

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
                // TODO: sesuaikan field warna (misal color_code / color_short / dsb)
                $q2->where('color_code', $color);
            });
        }

        // FILTER UKURAN
        if ($size !== '') {
            $bundlesQuery->whereHas('finishedItem', function ($q2) use ($size) {
                // TODO: sesuaikan field ukuran (misal size_code / size / dsb)
                $q2->where('size_code', $size);
            });
        }

        $bundles = $bundlesQuery
            ->orderBy('id')
            ->get();

        // Hitung qty ready & filter min/max ready di level Collection
        $minReadyF = is_null($minReady) || $minReady === '' ? null : (float) $minReady;
        $maxReadyF = is_null($maxReady) || $maxReady === '' ? null : (float) $maxReady;

        $displayBundles = $bundles->filter(function (CuttingJobBundle $b) use ($minReadyF, $maxReadyF) {
            $qtyOk = (float) ($b->qty_cutting_ok ?? 0);
            $qtyRemain = (float) ($b->qty_remaining_for_sewing ?? ($qtyOk ?: $b->qty_pcs));

            if ($qtyRemain <= 0) {
                return false;
            }

            if (!is_null($minReadyF) && $qtyRemain < $minReadyF) {
                return false;
            }

            if (!is_null($maxReadyF) && $qtyRemain > $maxReadyF) {
                return false;
            }

            // simpan untuk dipakai di view
            $b->computed_qty_remain = $qtyRemain;

            return true;
        })->values();

        $totalBundlesReady = $displayBundles->count();
        $totalQtyReady = $displayBundles->sum(function ($b) {
            return (float) ($b->computed_qty_remain ?? 0);
        });

        // render ulang baris tabel
        $html = view('production.sewing_pickups._bundle_picker_rows', [
            'displayBundles' => $displayBundles,
            'oldLines' => [], // AJAX tidak pakai old() form
            'preselectedBundleId' => null, // AJAX filter murni
        ])->render();

        return response()->json([
            'html' => $html,
            'total_bundles' => $totalBundlesReady,
            'total_ready' => $totalQtyReady,
            'total_ready_formatted' => number_format($totalQtyReady, 2, ',', '.'),
        ]);
    }

}
