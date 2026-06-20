<?php

namespace App\Services\Production;

use App\Helpers\CodeGenerator;
use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\InventoryMutation;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Services\Payroll\PieceRateService;
use Illuminate\Support\Facades\DB;

class CuttingService
{
    public function __construct(
        protected InventoryService $inventory,
        protected PieceRateService $pieceRate, // ✅ NEW
    ) {}

    /**
     * Buat Cutting Job baru.
     *
     * Versi MEDIUM (multi-LOT):
     * - Header job boleh punya lot_id (biasanya LOT pertama).
     * - LOT utama per bundle diambil dari $row['lot_id'].
     * - Saat SIMPAN: stok kain per LOT LANGSUNG berkurang (RM OUT),
     *   berdasarkan qty_used_fabric per bundle.
     */
    public function create(array $payload): CuttingJob
    {
        return DB::transaction(function () use ($payload) {
            $bundlesData = $payload['bundles'] ?? [];
            unset($payload['bundles']);

            if (empty($payload['code'] ?? null)) {
                $payload['code'] = CodeGenerator::generate('CUT');
            }

            /** @var CuttingJob $job */
            $job = CuttingJob::create([
                'code' => $payload['code'],
                'date' => $payload['date'],
                'warehouse_id' => $payload['warehouse_id'],
                'lot_id' => $payload['lot_id'] ?? null,
                'fabric_item_id' => $payload['fabric_item_id'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'status' => 'cut',
                'total_bundles' => 0,
                'total_qty_pcs' => 0,
                'operator_id' => $payload['operator_id'],
            ]);

            $operatorId = $payload['operator_id'] ?? null;

            // PREFETCH: item_category_id untuk bundles
            $itemCategoryMap = Item::whereIn(
                'id',
                collect($bundlesData)->pluck('finished_item_id')->filter()->unique()
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

                CuttingJobBundle::create([
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
                    'wip_warehouse_id' => null,
                    'wip_qty' => 0,
                    'cut_wip_warehouse_id' => null,
                    'cut_wip_qty' => 0,
                ]);

                $running++;
                $totalBundles++;
                $totalQtyPcs += $qtyPcs;
            }

            $job->update([
                'total_bundles' => $totalBundles,
                'total_qty_pcs' => $totalQtyPcs,
            ]);

            // Potong kain per LOT saat create
            $this->consumeFabricFromLots($job);

            return $job->fresh(['bundles']);
        });
    }

    /**
     * Update Cutting Job + bundles.
     * NOTE: update TIDAK adjust stok kain lagi.
     */
    public function update(array $payload, CuttingJob $job): CuttingJob
    {
        return DB::transaction(function () use ($payload, $job) {
            $bundlesData = $payload['bundles'] ?? [];
            unset($payload['bundles']);

            $job->update([
                'date' => $payload['date'],
                'notes' => $payload['notes'] ?? null,
                'fabric_item_id' => $payload['fabric_item_id'] ?? null,
            ]);

            $operatorId = $payload['operator_id'] ?? null;
            $existingIds = $job->bundles()->pluck('id')->all();
            $keepIds = [];

            $itemCategoryMap = Item::whereIn(
                'id',
                collect($bundlesData)->pluck('finished_item_id')->filter()->unique()
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

            if (!empty($existingIds)) {
                $deleteIds = array_diff($existingIds, $keepIds);
                if (!empty($deleteIds)) {
                    CuttingJobBundle::where('cutting_job_id', $job->id)
                        ->whereIn('id', $deleteIds)
                        ->delete();
                }
            }

            $job->update([
                'total_bundles' => $totalBundles,
                'total_qty_pcs' => $totalQtyPcs,
            ]);

            return $job->fresh(['bundles']);
        });
    }

    protected function generateBundleCode(CuttingJob $job, int $bundleNo): string
    {
        $datePart = $job->date?->format('Ymd') ?? now()->format('Ymd');
        $jobSeq = str_pad((string) $job->id, 3, '0', STR_PAD_LEFT);
        $bundleSeq = str_pad((string) $bundleNo, 3, '0', STR_PAD_LEFT);
        return "BND-{$datePart}-{$jobSeq}-{$bundleSeq}";
    }

    /**
     * Konsumsi kain per LOT saat cutting create.
     * (kode kamu tetap)
     */
    protected function consumeFabricFromLots(CuttingJob $job): void
    {
        $job->loadMissing(['bundles', 'lots']);

        $fabricItemId = $job->fabric_item_id;
        $warehouseId = $job->warehouse_id;

        if (!$fabricItemId || !$warehouseId) {
            return;
        }

        $totalUsed = 0.0;
        foreach ($job->bundles as $bundle) {
            $qtyUsed = $this->num($bundle->qty_used_fabric ?? 0);
            if ($qtyUsed > 0) {
                $totalUsed += $qtyUsed;
            }

        }
        if ($totalUsed <= 0) {
            return;
        }

        $lotPlans = $job->lots;

        if ($lotPlans && $lotPlans->count() > 0) {
            $totalPlanned = (float) $lotPlans->sum('planned_fabric_qty');

            if ($totalPlanned <= 0) {
                $perLot = $totalUsed / max(1, $lotPlans->count());
                $perLot = $this->num($perLot);

                $remaining = $totalUsed;
                foreach ($lotPlans as $index => $plan) {
                    $qtyOut = $perLot;
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
                        allowNegative: true,
                        lotId: $plan->lot_id,
                        unitCostOverride: null,
                        affectLotCost: true,
                        strictNonNegative: true,
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

            $remaining = $totalUsed;
            foreach ($lotPlans as $index => $plan) {
                $planned = (float) $plan->planned_fabric_qty;

                if ($planned <= 0) {
                    $plan->used_fabric_qty = 0;
                    $plan->save();
                    continue;
                }

                $portion = ($planned / $totalPlanned) * $totalUsed;
                $portion = $this->num($portion);

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
                    allowNegative: true,
                    lotId: $plan->lot_id,
                    unitCostOverride: null,
                    affectLotCost: true,
                    strictNonNegative: true,
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

        // fallback group by lot_id dari bundles
        $byLot = [];
        foreach ($job->bundles as $bundle) {
            $lotId = $bundle->lot_id;
            if (!$lotId) {
                continue;
            }

            $qtyUsed = $this->num($bundle->qty_used_fabric ?? 0);
            if ($qtyUsed <= 0) {
                continue;
            }

            $byLot[$lotId] = ($byLot[$lotId] ?? 0) + $qtyUsed;
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
                allowNegative: true,
                lotId: $lotId,
                unitCostOverride: null,
                affectLotCost: true,
                strictNonNegative: true,
            );
        }
    }

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

        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }

    /**
     * POST QC Cutting -> Create WIP-CUT + REJ-CUT
     * ✅ Updated: unit cost includes cutting piece rate otomatis
     */
    public function createWipFromCuttingQc(CuttingJob $job, ?string $qcDate = null): void
    {
        $job->loadMissing(['bundles']);

        $date = $qcDate ?: ($job->date?->format('Y-m-d') ?? now()->format('Y-m-d'));

        $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');
        $rejCutWarehouseId = Warehouse::where('code', 'REJ-CUT')->value('id');

        if (!$wipCutWarehouseId || !$rejCutWarehouseId) {
            throw new \RuntimeException('Warehouse WIP-CUT atau REJ-CUT belum dikonfigurasi (code = WIP-CUT / REJ-CUT).');
        }

        // 1) total processed (OK+Reject)
        $totalProcessedAll = 0.0;
        foreach ($job->bundles as $bundle) {
            $qtyOk = $this->num($bundle->qty_qc_ok ?? 0);
            $qtyReject = $this->num($bundle->qty_qc_reject ?? 0);
            $totalProcessedAll += ($qtyOk + $qtyReject);
        }

        if ($totalProcessedAll <= 0) {
            return;
        }

        // 2) total RM cost (kain) dari RM OUT
        $rmMutations = InventoryMutation::query()
            ->where('source_type', 'cutting_job')
            ->where('source_id', $job->id)
            ->where('direction', 'out')
            ->get();

        $totalRmCost = abs((float) $rmMutations->sum('total_cost'));

        // 2B) total cutting labor dari piece rate
        $totalCuttingLabor = 0.0;

        foreach ($job->bundles as $bundle) {
            $qtyOk = $this->num($bundle->qty_qc_ok ?? 0);
            $qtyReject = $this->num($bundle->qty_qc_reject ?? 0);
            $qtyProcessed = $qtyOk + $qtyReject;

            if ($qtyProcessed <= 0) {
                continue;
            }

            $empId = (int) ($bundle->operator_id ?: $job->operator_id ?: 0);
            if ($empId <= 0) {
                continue;
            }

            $itemId = (int) ($bundle->finished_item_id ?? 0);
            if ($itemId <= 0) {
                continue;
            }

            $rate = (float) $this->pieceRate->requireRatePerPcs(
                module: 'cutting',
                employeeId: $empId,
                itemId: $itemId,
                date: $date
            );

            if ($rate <= 0) {
                continue;
            }

            $totalCuttingLabor += ($rate * $qtyProcessed);
        }

        // 2C) unit cost = (RM + labor) / processed
        $totalCost = $totalRmCost + $totalCuttingLabor;
        $unitCostPerPcs = $totalCost > 0 ? ($totalCost / $totalProcessedAll) : null;

        // 3) stockIn WIP-CUT / REJ-CUT
        foreach ($job->bundles as $bundle) {
            /** @var CuttingJobBundle $bundle */
            $qtyOk = $this->num($bundle->qty_qc_ok ?? 0);
            $qtyReject = $this->num($bundle->qty_qc_reject ?? 0);

            if ($qtyOk <= 0 && $qtyReject <= 0) {
                continue;
            }

            if (!empty($bundle->wip_posted_at)) {
                $dirty = false;
                if ($this->num($bundle->wip_qty ?? 0) !== $qtyOk) {
                    $bundle->wip_qty = $qtyOk;
                    $dirty = true;
                }
                // Jaga konsistensi kolom cutting-WIP (kebal dari tahap hilir).
                if ($this->num($bundle->cut_wip_qty ?? 0) !== $qtyOk) {
                    $bundle->cut_wip_qty = $qtyOk;
                    $dirty = true;
                }
                if (empty($bundle->cut_wip_warehouse_id)) {
                    // cut WIP selalu di WIP-CUT (invarian dijaga model guard).
                    $bundle->cut_wip_warehouse_id = $wipCutWarehouseId;
                    $dirty = true;
                }
                if ($dirty) {
                    $bundle->save();
                }
                continue;
            }

            $bundleWipWarehouseId = $bundle->wip_warehouse_id ?: $wipCutWarehouseId;

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
                    unitCost: $unitCostPerPcs,
                    affectLotCost: false,
                    cuttingJobBundleId: $bundle->id,
                );

                $bundle->wip_warehouse_id = $bundleWipWarehouseId;
                $bundle->wip_qty = $qtyOk;

                // ✅ Kolom cutting-WIP yang otoritatif untuk "Ambil Jahit".
                // Di-set sekali di sini dan TIDAK boleh ditimpa tahap hilir.
                // cut WIP selalu di WIP-CUT (invarian dijaga model guard).
                $bundle->cut_wip_warehouse_id = $wipCutWarehouseId;
                $bundle->cut_wip_qty = $qtyOk;
            } else {
                $bundle->wip_qty = 0;
                $bundle->cut_wip_qty = 0;
            }

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
                    cuttingJobBundleId: $bundle->id,
                );
            }

            $bundle->wip_posted_at = now();
            $bundle->save();
        }

        if ($job->status !== 'qc_done') {
            $job->update(['status' => 'qc_done']);
        }
    }
}
