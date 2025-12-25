<?php

namespace App\Services\Production;

use App\Helpers\CodeGenerator;
use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\InventoryMutation;
use App\Models\Item;
use App\Models\Lot;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;

class CuttingService
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    /**
     * Buat Cutting Job baru.
     *
     * Versi MEDIUM (multi-LOT):
     * - Header job boleh punya lot_id (biasanya LOT pertama).
     * - LOT utama per bundle diambil dari $row['lot_id'] (bukan lagi $job->lot_id).
     * - SAAT SIMPAN: stok kain per LOT LANGSUNG berkurang (RM OUT),
     *   berdasarkan qty_used_fabric per bundle.
     */
    public function create(array $payload): CuttingJob
    {
        return DB::transaction(function () use ($payload) {
            $bundlesData = $payload['bundles'] ?? [];
            unset($payload['bundles']);

            // generate kode job kalau belum diisi
            if (empty($payload['code'] ?? null)) {
                $payload['code'] = CodeGenerator::generate('CUT');
            }

            /** @var CuttingJob $job */
            $job = CuttingJob::create([
                'code' => $payload['code'],
                'date' => $payload['date'],
                'warehouse_id' => $payload['warehouse_id'], // gudang proses cutting (biasanya RM)
                'lot_id' => $payload['lot_id'] ?? null, // boleh null, atau LOT pertama dari controller
                'fabric_item_id' => $payload['fabric_item_id'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'status' => 'cut', // bukan draft lagi
                'total_bundles' => 0,
                'total_qty_pcs' => 0,
                'operator_id' => $payload['operator_id'],
            ]);

            $operatorId = $payload['operator_id'] ?? null;

            // (opsional) Ambil gudang WIP-CUT kalau nanti mau dipakai
            $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');

            // 🔹 PREFETCH: mapping item_id => item_category_id (dipakai untuk isi item_category_id di bundles)
            $itemCategoryMap = Item::whereIn(
                'id',
                collect($bundlesData)
                    ->pluck('finished_item_id')
                    ->filter()
                    ->unique()
            )->pluck('item_category_id', 'id'); // [item_id => item_category_id]

            $running = 1;
            $totalBundles = 0;
            $totalQtyPcs = 0.0;

            foreach ($bundlesData as $row) {
                if (empty($row['finished_item_id'])) {
                    continue;
                }

                $qtyPcs = $this->num($row['qty_pcs'] ?? 0);
                if ($qtyPcs <= 0) {
                    continue;
                }

                $bundleNo = $row['bundle_no'] ?? $running;
                $qtyUsedFabric = $this->num($row['qty_used_fabric'] ?? 0);
                $finishedItemId = (int) $row['finished_item_id'];

                // ⬇️ per-bundle LOT
                $bundleLotId = !empty($row['lot_id']) ? (int) $row['lot_id'] : null;

                // auto-fill kategori dari master item
                $itemCategoryId = $itemCategoryMap[$finishedItemId] ?? null;

                CuttingJobBundle::create([
                    'cutting_job_id' => $job->id,
                    'bundle_code' => $this->generateBundleCode($job, $bundleNo),
                    'bundle_no' => $bundleNo,
                    'lot_id' => $bundleLotId, // ⬅️ per-bundle LOT
                    'finished_item_id' => $finishedItemId,
                    'item_category_id' => $itemCategoryId,
                    'qty_pcs' => $qtyPcs,
                    'qty_used_fabric' => $qtyUsedFabric,
                    'operator_id' => $operatorId,
                    'status' => 'cut',
                    'notes' => $row['notes'] ?? null,
                    // WIP-CUT masih 0, karena pattern WIP tetap via QC
                    'wip_warehouse_id' => null,
                    'wip_qty' => 0,
                ]);

                $running++;
                $totalBundles++;
                $totalQtyPcs += $qtyPcs;
            }

            // update summary di header
            $job->update([
                'total_bundles' => $totalBundles,
                'total_qty_pcs' => $totalQtyPcs,
            ]);

            // 🔥 PATTERN BARU:
            // Saat cutting disimpan → langsung kurangi stok kain per LOT (RM OUT),
            // berdasarkan qty_used_fabric di CuttingJobBundle.
            $this->consumeFabricFromLots($job);

            return $job->fresh(['bundles']);
        });
    }

    /**
     * Update Cutting Job + bundles.
     *
     * NOTE:
     * - Untuk sekarang, update TIDAK OTOMATIS adjust stok kain lagi.
     *   Jadi disarankan pemakaian kain fix saat create.
     *   (Nanti kalau mau, bisa tambah logika penyesuaian dengan melihat mutasi sebelumnya.)
     *
     * - Header: update tanggal, notes, fabric_item_id (warehouse & lot bisa tetap).
     * - Bundles: lot_id & item_category_id di-update sesuai input terbaru.
     */
    public function update(array $payload, CuttingJob $job): CuttingJob
    {
        return DB::transaction(function () use ($payload, $job) {
            $bundlesData = $payload['bundles'] ?? [];
            unset($payload['bundles']);

            // update header (kalau kamu mau allow ganti warehouse/lot, tinggal tambah di sini)
            $job->update([
                'date' => $payload['date'],
                'notes' => $payload['notes'] ?? null,
                'fabric_item_id' => $payload['fabric_item_id'] ?? null,
                // 'warehouse_id' => $payload['warehouse_id'] ?? $job->warehouse_id,
                // 'lot_id'       => $payload['lot_id'] ?? $job->lot_id,
            ]);

            $operatorId = $payload['operator_id'] ?? null;
            $existingIds = $job->bundles()->pluck('id')->all();
            $keepIds = [];

            // 🔹 PREFETCH kategori item untuk update juga
            $itemCategoryMap = Item::whereIn(
                'id',
                collect($bundlesData)
                    ->pluck('finished_item_id')
                    ->filter()
                    ->unique()
            )->pluck('item_category_id', 'id');

            $running = 1;
            $totalBundles = 0;
            $totalQtyPcs = 0.0;

            foreach ($bundlesData as $row) {
                if (empty($row['finished_item_id'])) {
                    continue;
                }

                $qtyPcs = $this->num($row['qty_pcs'] ?? 0);
                if ($qtyPcs <= 0) {
                    continue;
                }

                $bundleNo = $row['bundle_no'] ?? $running;
                $qtyUsedFabric = $this->num($row['qty_used_fabric'] ?? 0);
                $finishedItemId = (int) $row['finished_item_id'];
                $bundleLotId = !empty($row['lot_id']) ? (int) $row['lot_id'] : null;

                $itemCategoryId = $itemCategoryMap[$finishedItemId] ?? null;

                if (!empty($row['id']) && in_array($row['id'], $existingIds)) {
                    // UPDATE
                    $bundle = CuttingJobBundle::where('cutting_job_id', $job->id)
                        ->where('id', $row['id'])
                        ->first();

                    if ($bundle) {
                        $bundle->update([
                            'bundle_no' => $bundleNo,
                            'lot_id' => $bundleLotId,
                            'finished_item_id' => $finishedItemId,
                            'item_category_id' => $itemCategoryId,
                            'qty_pcs' => $qtyPcs,
                            'qty_used_fabric' => $qtyUsedFabric,
                            'operator_id' => $operatorId,
                            'notes' => $row['notes'] ?? null,
                        ]);
                        $keepIds[] = $bundle->id;
                    }
                } else {
                    // INSERT baru
                    $bundle = CuttingJobBundle::create([
                        'cutting_job_id' => $job->id,
                        'bundle_code' => $this->generateBundleCode($job, $bundleNo),
                        'bundle_no' => $bundleNo,
                        'lot_id' => $bundleLotId,
                        'finished_item_id' => $finishedItemId,
                        'item_category_id' => $itemCategoryId,
                        'qty_pcs' => $qtyPcs,
                        'qty_used_fabric' => $qtyUsedFabric,
                        'operator_id' => $operatorId,
                        'status' => 'cut',
                        'notes' => $row['notes'] ?? null,
                    ]);
                    $keepIds[] = $bundle->id;
                }

                $running++;
                $totalBundles++;
                $totalQtyPcs += $qtyPcs;
            }

            // hapus bundle yang tidak dikirim lagi
            if (!empty($existingIds)) {
                $deleteIds = array_diff($existingIds, $keepIds);
                if (!empty($deleteIds)) {
                    CuttingJobBundle::where('cutting_job_id', $job->id)
                        ->whereIn('id', $deleteIds)
                        ->delete();
                }
            }

            // update summary header
            $job->update([
                'total_bundles' => $totalBundles,
                'total_qty_pcs' => $totalQtyPcs,
            ]);

            return $job->fresh(['bundles']);
        });
    }

    /**
     * Generate kode bundle.
     * Contoh: BND-20251125-001-001 (Tgl-JobId-BundleNo)
     */
    protected function generateBundleCode(CuttingJob $job, int $bundleNo): string
    {
        $datePart = $job->date?->format('Ymd') ?? now()->format('Ymd');
        $jobSeq = str_pad((string) $job->id, 3, '0', STR_PAD_LEFT);
        $bundleSeq = str_pad((string) $bundleNo, 3, '0', STR_PAD_LEFT);

        return "BND-{$datePart}-{$jobSeq}-{$bundleSeq}";
    }

    /**
     * PATTERN BARU:
     * Konsumsi kain per LOT pada saat Cutting disimpan.
     *
     * - Group bundle per lot_id.
     * - Qty dipakai = sum(qty_used_fabric) per LOT.
     * - Mutasi: RM OUT, pakai moving average per LOT (LotCostService).
     */
    protected function consumeFabricFromLots(CuttingJob $job): void
    {
        // Pastikan relasi bundles + lots (cutting_job_lots) ke-load
        $job->loadMissing(['bundles', 'lots']); // kalau relasinya beda, ganti 'lots' sesuai nama relasimu

        $fabricItemId = $job->fabric_item_id;
        $warehouseId = $job->warehouse_id;

        if (!$fabricItemId || !$warehouseId) {
            // kalau header belum lengkap, skip saja
            return;
        }

        // ============================
        // 1. TOTAL PEMAKAIAN DARI BUNDLES
        // ============================
        $totalUsed = 0.0;

        foreach ($job->bundles as $bundle) {
            /** @var CuttingJobBundle $bundle */
            $qtyUsed = $this->num($bundle->qty_used_fabric ?? 0);
            if ($qtyUsed > 0) {
                $totalUsed += $qtyUsed;
            }
        }

        if ($totalUsed <= 0) {
            // tidak ada pemakaian kain yang valid
            return;
        }

        // ============================
        // 2. Coba pakai cutting_job_lots sebagai dasar distribusi
        // ============================
        $lotPlans = $job->lots; // relasi ke tabel cutting_job_lots (id, lot_id, planned_fabric_qty, used_fabric_qty, ...)

        if ($lotPlans && $lotPlans->count() > 0) {
            $totalPlanned = (float) $lotPlans->sum('planned_fabric_qty');

            // Kalau tidak ada planned, bagi rata ke semua LOT
            if ($totalPlanned <= 0) {
                $perLot = $totalUsed / max(1, $lotPlans->count());
                $perLot = $this->num($perLot);

                $remaining = $totalUsed;
                foreach ($lotPlans as $index => $plan) {
                    /** @var \App\Models\CuttingJobLot $plan */
                    $qtyOut = $perLot;

                    // LOT terakhir dapat sisa supaya total pas
                    if ($index === $lotPlans->count() - 1) {
                        $qtyOut = $this->num($remaining);
                    }

                    if ($qtyOut <= 0) {
                        $plan->used_fabric_qty = 0;
                        $plan->save();
                        continue;
                    }

                    $this->inventory->stockOut(
                        warehouseId: $warehouseId,
                        itemId: $fabricItemId,
                        qty: $qtyOut,
                        date: $job->date,
                        sourceType: 'cutting_job',
                        sourceId: $job->id,
                        notes: "Pemakaian kain untuk Cutting {$job->code} (LOT {$plan->lot_id})",
                        allowNegative: false,
                        lotId: $plan->lot_id,
                        unitCostOverride: null,
                        affectLotCost: true, // tetap pakai LotCost (moving average per LOT)
                    );

                    $plan->used_fabric_qty = $qtyOut;
                    $plan->save();

                    $remaining -= $qtyOut;
                    if ($remaining <= 0) {
                        break;
                    }
                }

                return;
            }

            // ============================
            // 3. Distribusi proporsional terhadap planned_fabric_qty
            // ============================
            $remaining = $totalUsed;

            foreach ($lotPlans as $index => $plan) {
                /** @var \App\Models\CuttingJobLot $plan */
                $planned = (float) $plan->planned_fabric_qty;

                if ($planned <= 0) {
                    $plan->used_fabric_qty = 0;
                    $plan->save();
                    continue;
                }

                // porsi ideal berdasarkan proporsi planned
                $portion = ($planned / $totalPlanned) * $totalUsed;
                $portion = $this->num($portion);

                // LOT terakhir ambil semua sisa supaya pas
                if ($index === $lotPlans->count() - 1) {
                    $portion = $this->num($remaining);
                }

                if ($portion <= 0) {
                    $plan->used_fabric_qty = 0;
                    $plan->save();
                    continue;
                }

                $this->inventory->stockOut(
                    warehouseId: $warehouseId,
                    itemId: $fabricItemId,
                    qty: $portion,
                    date: $job->date,
                    sourceType: 'cutting_job',
                    sourceId: $job->id,
                    notes: "Pemakaian kain untuk Cutting {$job->code} (LOT {$plan->lot_id})",
                    allowNegative: false,
                    lotId: $plan->lot_id,
                    unitCostOverride: null,
                    affectLotCost: true,
                );

                $plan->used_fabric_qty = $portion;
                $plan->save();

                $remaining -= $portion;
                if ($remaining <= 0) {
                    break;
                }
            }

            return;
        }

        // ============================
        // 4. Fallback ke LOGIC LAMA (kalau belum pakai cutting_job_lots)
        // ============================
        $byLot = [];

        foreach ($job->bundles as $bundle) {
            /** @var CuttingJobBundle $bundle */
            $lotId = $bundle->lot_id;
            if (!$lotId) {
                continue;
            }

            $qtyUsed = $this->num($bundle->qty_used_fabric ?? 0);
            if ($qtyUsed <= 0) {
                continue;
            }

            if (!isset($byLot[$lotId])) {
                $byLot[$lotId] = 0.0;
            }

            $byLot[$lotId] += $qtyUsed;
        }

        if (empty($byLot)) {
            return;
        }

        foreach ($byLot as $lotId => $qtyUsedTotal) {
            if ($qtyUsedTotal <= 0) {
                continue;
            }

            $this->inventory->stockOut(
                warehouseId: $warehouseId,
                itemId: $fabricItemId,
                qty: $qtyUsedTotal,
                date: $job->date,
                sourceType: 'cutting_job',
                sourceId: $job->id,
                notes: "Pemakaian kain untuk Cutting {$job->code} (LOT {$lotId})",
                allowNegative: false,
                lotId: $lotId,
                unitCostOverride: null,
                affectLotCost: true,
            );
        }
    }

    /**
     * Normalisasi angka.
     */
    protected function num(float | int | string | null $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);

        // format Indonesia 1.234,56
        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }

    public function createWipFromCuttingQc(CuttingJob $job, ?string $qcDate = null): void
    {
        $job->loadMissing(['bundles']);

        $date = $qcDate ?: ($job->date?->format('Y-m-d') ?? now()->format('Y-m-d'));

        // 🔹 Ambil warehouse WIP-CUT & REJ-CUT
        $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');
        $rejCutWarehouseId = Warehouse::where('code', 'REJ-CUT')->value('id');

        if (!$wipCutWarehouseId || !$rejCutWarehouseId) {
            throw new \RuntimeException('Warehouse WIP-CUT atau REJ-CUT belum dikonfigurasi (code = WIP-CUT / REJ-CUT).');
        }

        // ======================================================
        // 1) Hitung total qty OK + REJECT semua bundle (pembagi)
        // ======================================================
        $totalProcessedAll = 0.0;
        $totalOkAll = 0.0;

        foreach ($job->bundles as $bundle) {
            $qtyOk = $this->num($bundle->qty_qc_ok ?? 0);
            $qtyReject = $this->num($bundle->qty_qc_reject ?? 0);

            $totalProcessedAll += ($qtyOk + $qtyReject);
            $totalOkAll += $qtyOk;
        }

        if ($totalProcessedAll <= 0 || $totalOkAll <= 0) {
            // tidak ada yang diproses / tidak ada OK → tidak ada WIP
            return;
        }

        // ======================================================
        // 2) Ambil total biaya kain dari mutasi RM OUT untuk job ini
        // ======================================================
        $rmMutations = InventoryMutation::query()
            ->where('source_type', 'cutting_job')
            ->where('source_id', $job->id)
            ->where('direction', 'out')
            ->get();

        $totalRmCost = (float) $rmMutations->sum('total_cost'); // negatif
        $totalRmCost = abs($totalRmCost); // jadikan positif

        // Kalau belum ada RM OUT (job lama?), jangan paksa costing
        if ($totalRmCost <= 0) {
            $unitCostPerPcs = null;
        } else {
            // 🔥 HPP RM per pcs (dibagi OK+REJECT)
            $unitCostPerPcs = $totalRmCost / $totalProcessedAll;
        }

        // ======================================================
        // 3) Loop bundle → buat WIP-CUT (OK) + REJ-CUT (Reject)
        // ======================================================
        foreach ($job->bundles as $bundle) {
            /** @var CuttingJobBundle $bundle */
            $qtyOk = $this->num($bundle->qty_qc_ok ?? 0);
            $qtyReject = $this->num($bundle->qty_qc_reject ?? 0);

            // kalau tidak ada hasil sama sekali → skip
            if ($qtyOk <= 0 && $qtyReject <= 0) {
                continue;
            }

            // kalau sudah pernah dibuat WIP (wip_qty > 0), anggap sudah di-post → skip
            if ($this->num($bundle->wip_qty ?? 0) > 0) {
                continue;
            }

            // Gunakan wip_warehouse_id kalau ada, kalau tidak fallback ke WIP-CUT global
            $bundleWipWarehouseId = $bundle->wip_warehouse_id ?: $wipCutWarehouseId;

            // ========================
            // 3.a WIP-CUT (OK)
            // ========================
            if ($qtyOk > 0) {
                $this->inventory->stockIn(
                    warehouseId: $bundleWipWarehouseId,
                    itemId: $bundle->finished_item_id,
                    qty: $qtyOk,
                    date: $date,
                    sourceType: 'cutting_wip',
                    sourceId: $job->id,
                    notes: "WIP Cutting OK dari bundle {$bundle->bundle_code} (job {$job->code})",
                    lotId: null,
                    unitCost: $unitCostPerPcs, // boleh null; InventoryService akan handle
                    affectLotCost: false,
                );

                // update info WIP di bundle
                $bundle->wip_warehouse_id = $bundleWipWarehouseId;
                $bundle->wip_qty = $qtyOk;
            }

            // ========================
            // 3.b REJ-CUT (Reject)
            // ========================
            if ($qtyReject > 0) {
                $this->inventory->stockIn(
                    warehouseId: $rejCutWarehouseId,
                    itemId: $bundle->finished_item_id,
                    qty: $qtyReject,
                    date: $date,
                    sourceType: 'cutting_reject',
                    sourceId: $job->id,
                    notes: "Reject Cutting {$qtyReject} pcs dari bundle {$bundle->bundle_code} (job {$job->code})",
                    lotId: null,
                    unitCost: $unitCostPerPcs,
                    affectLotCost: false,
                );
            }

            $bundle->save();
        }

        // update status job (kalau belum)
        if ($job->status !== 'qc_done') {
            $job->update(['status' => 'qc_done']);
        }
    }

}
