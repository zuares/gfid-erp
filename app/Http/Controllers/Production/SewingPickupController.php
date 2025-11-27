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
                // Ambil qty yang sudah pernah dipick ke sewing
                $alreadyPicked = (float) ($bundle->sewing_picked_qty ?? 0);

                // Sisa qty yang masih boleh di-pick
                $remaining = max($maxQtyOk - $alreadyPicked, 0);

                if ($remaining <= 0) {
                    // bundle ini sudah habis dipick sebelumnya
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
                // 🔁 BLOK COSTING / INVENTORY
                // =======================

                $notes = "Sewing pickup {$pickup->code} - bundle {$bundle->bundle_code}";

                // 1️⃣ Ambil unit_cost per pcs dari WIP-CUT untuk LOT + item ini
                //    Ini memastikan biaya di WIP-SEW = biaya di WIP-CUT (tidak bikin HPP baru).
                $unitCostPerPiece = $this->inventory->getItemIncomingUnitCost(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $bundle->finished_item_id,
                );

                // Kalau mau extra safety:
                if ($unitCostPerPiece === null || $unitCostPerPiece <= 0) {
                    // fallback: kalau misal belum ada saldo WIP-CUT (kasus error data)
                    // - bisa pakai getItemIncomingUnitCost
                    // - atau set 0 tapi kamu harus aware HPP akan 0
                    $unitCostPerPiece = $this->inventory->getItemIncomingUnitCost(
                        warehouseId: $wipCutWarehouseId,
                        itemId: $bundle->finished_item_id,
                    );
                }

                // 1) Keluar dari WIP-CUT
                $this->inventory->stockOut(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $bundle->finished_item_id,
                    qty: $qty,
                    date: $date,
                    sourceType: SewingPickup::class,
                    sourceId: $pickup->id,
                    notes: $notes,
                    allowNegative: false,
                    lotId: $bundle->lot_id,
                    unitCostOverride: $unitCostPerPiece, // ⬅️ pakai cost WIP, bukan LotCost kain
                    affectLotCost: false, // ⬅️ jangan konsumsi LotCost lagi
                );

// 2) Masuk ke WIP-SEW
                $this->inventory->stockIn(
                    warehouseId: $sewingWarehouseId,
                    itemId: $bundle->finished_item_id,
                    qty: $qty,
                    date: $date,
                    sourceType: SewingPickup::class,
                    sourceId: $pickup->id,
                    notes: $notes,
                    lotId: $bundle->lot_id,
                    unitCost: $unitCostPerPiece,
                    affectLotCost: false,
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
            ->route('production.sewing_pickups.index')
            ->with('success', 'Sewing pickup berhasil dibuat. Stok sudah dipindahkan dari WIP-CUT ke gudang sewing dengan costing yang mengikuti saldo WIP-CUT.');
    }

}
