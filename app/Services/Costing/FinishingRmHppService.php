<?php

namespace App\Services\Costing;

use App\Models\FinishingJob;
use App\Models\ItemCostSnapshot;
use App\Services\Inventory\InventoryService;

class FinishingRmHppService
{
    public function __construct(
        protected InventoryService $inventory,
        protected HppService $hpp,
    ) {}

    /**
     * Buat RM-only snapshot untuk setiap FG dalam satu FinishingJob.
     *
     * @param  FinishingJob $job
     * @param  array<int,float> $qtyOkByItem [item_id => total_qty_ok_finishing]
     * @return ItemCostSnapshot[]
     */
    public function createRmOnlySnapshotsFromFinishing(
        FinishingJob $job,
        array $qtyOkByItem,
    ): array {
        if (empty($qtyOkByItem)) {
            return [];
        }

        // pastikan bundles + relasi Cutting & Lot sudah di-load
        $job->loadMissing([
            'bundles.lot',
            'bundles.cuttingJob',
        ]);

        $snapshots = [];

        foreach ($qtyOkByItem as $itemId => $qtyOk) {
            $qtyOk = (float) $qtyOk;
            if ($itemId <= 0 || $qtyOk <= 0) {
                continue;
            }

            $rmUnitCost = $this->calculateRmCostPerUnitForFinishingItem(
                job: $job,
                finishedItemId: $itemId,
                qtyOkTotal: $qtyOk,
            );

            if ($rmUnitCost <= 0) {
                continue;
            }

            // tanggal snapshot → pakai tanggal finishing job, kalau tidak ada fallback ke today
            $snapshotDate = method_exists($job, 'getAttribute')
            ? ($job->date ?? now()->toDateString())
            : now()->toDateString();

            $notes = "Auto HPP RM-only dari Finishing {$job->code}";

            $snapshots[] = $this->hpp->createSnapshot(
                itemId: $itemId,
                warehouseId: null, // atau isi kalau kamu punya gudang FG spesifik
                snapshotDate: $snapshotDate,
                referenceType: 'auto_hpp_rm_only_finishing',
                referenceId: $job->id,
                qtyBasis: $qtyOk,
                rmUnitCost: $rmUnitCost,
                cuttingUnitCost: 0.0,
                sewingUnitCost: 0.0,
                finishingUnitCost: 0.0,
                packagingUnitCost: 0.0,
                overheadUnitCost: 0.0,
                notes: $notes,
                setActive: false, // RM-only biasanya basis, bukan HPP final
            );
        }

        return $snapshots;
    }

    /**
     * Hitung RM/unit untuk 1 FG di dalam 1 FinishingJob (MULTI-LOT):
     * - pakai bundles yang terkait finishing job ini
     * - filter per finished_item_id
     * - cost = Σ (unit_cost_LOT × qty_used_fabric_bundle)
     * - basis qty = total qty OK finishing (dipass dari QC) / fallback qty bundle
     */
    protected function calculateRmCostPerUnitForFinishingItem(
        FinishingJob $job,
        int $finishedItemId,
        float $qtyOkTotal,
    ): float {
        $bundles = $job->bundles
            ->where('finished_item_id', $finishedItemId);

        if ($bundles->isEmpty()) {
            return 0.0;
        }

        $totalRmCost = 0.0;
        $fallbackFgQty = 0.0;

        foreach ($bundles as $bundle) {
            // pemakaian kain (meter / yard / pcs) per bundle
            $rmQty = (float) ($bundle->qty_used_fabric ?? 0);
            if ($rmQty <= 0) {
                continue;
            }

            $cuttingJob = $bundle->cuttingJob;
            $lot = $bundle->lot;

            if (!$cuttingJob || !$lot) {
                continue;
            }

            $rmWarehouseId = (int) $cuttingJob->warehouse_id;
            $rmItemId = (int) $lot->item_id;
            $lotId = (int) $bundle->lot_id;

            if (!$rmWarehouseId || !$rmItemId || !$lotId) {
                continue;
            }

            $unitCostLot = $this->inventory->getLotMovingAverageUnitCost(
                warehouseId: $rmWarehouseId,
                itemId: $rmItemId,
                lotId: $lotId,
            );

            if ($unitCostLot === null) {
                continue;
            }

            $totalRmCost += $unitCostLot * $rmQty;

            // fallback: kalau qtyOkTotal <= 0, pakai qty dari bundle
            $fgQtyBundle = (float) ($bundle->qty_sewing_ok ?? $bundle->qty_qc_ok ?? $bundle->qty_pcs ?? 0);
            if ($fgQtyBundle > 0) {
                $fallbackFgQty += $fgQtyBundle;
            }
        }

        $basisQty = $qtyOkTotal > 0 ? $qtyOkTotal : $fallbackFgQty;

        if ($basisQty <= 0 || $totalRmCost <= 0) {
            return 0.0;
        }

        return round($totalRmCost / $basisQty, 4);
    }
}
