<?php

namespace App\Services\Costing;

use App\Models\CuttingJobBundle;
use App\Models\ItemCostSnapshot;
use App\Models\PieceworkPayrollLine;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;

class FgHppAutoService
{
    public function __construct(
        protected InventoryService $inventory,
        protected HppService $hpp, // service pembungkus ItemCostSnapshot::create()
    ) {}

    /**
     * Hitung HPP per unit untuk 1 FG + simpan ke item_cost_snapshots.
     *
     * @param  int         $itemId       ID item finished good
     * @param  string      $dateFrom     'Y-m-d'
     * @param  string      $dateTo       'Y-m-d'
     * @param  int|null    $warehouseId  optional gudang (misal WH-RTS)
     * @param  string|null $notes        catatan snapshot
     */
    public function calculateAndSnapshotForFinishedItem(
        int $itemId,
        string $dateFrom,
        string $dateTo,
        ?int $warehouseId = null,
        ?string $notes = null,
    ): ItemCostSnapshot {
        $dateFrom = Carbon::parse($dateFrom)->toDateString();
        $dateTo = Carbon::parse($dateTo)->toDateString();

        // 1) HPP bahan baku (kain) per pcs → dari Cutting + LotCost + qty_used_fabric (multi-LOT)
        $rmUnitCost = $this->calculateRmCostPerUnitFromCutting(
            finishedItemId: $itemId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );

        // 2) HPP payroll Cutting per pcs → dari PieceworkPayrollLine (module: cutting)
        $cuttingUnitCost = $this->calculateCuttingPayrollCostPerUnit(
            finishedItemId: $itemId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );

        // 3) HPP payroll Sewing per pcs → dari PieceworkPayrollLine (module: sewing)
        $sewingUnitCost = $this->calculateSewingPayrollCostPerUnit(
            finishedItemId: $itemId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );

        // 4) Placeholder sementara (kalau nanti ada modul finishing/packaging/overhead)
        $finishingUnitCost = 0.0;
        $packagingUnitCost = 0.0;
        $overheadUnitCost = 0.0;

        // 5) Basis qty: total qty OK sewing periode ini (konsisten dgn payroll sewing)
        $qtyBasis = $this->getFgQtyBasisFromSewingPayroll(
            finishedItemId: $itemId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );

        // 6) Simpan snapshot via HppService
        return $this->hpp->createSnapshot(
            $itemId, // item_id
            $warehouseId, // warehouse_id
            $dateTo, // snapshot_date
            'auto_hpp_period', // reference_type
            null, // reference_id
            $qtyBasis, // qty_basis
            $rmUnitCost, // rm_unit_cost
            $cuttingUnitCost, // cutting_unit_cost
            $sewingUnitCost, // sewing_unit_cost
            $finishingUnitCost,
            $packagingUnitCost,
            $overheadUnitCost,
            $notes ?: "Auto HPP {$dateFrom} s/d {$dateTo}"
        );
    }

    /**
     * Hitung cost bahan baku per pcs (MULTI-LOT):
     * - ambil CuttingJobBundle.finished_item_id = FG ini
     * - filter CuttingJob.date di antara periode
     * - pakai qty_used_fabric + lot avg cost (per LOT di gudang RM)
     * - FG OK pakai qty_qc_ok (kalau ada), fallback ke qty_cutting_ok / qty_pcs
     * - rumus: Σ (unit_cost_LOT × qty_used_fabric_bundle) / Σ qty_FG_OK
     *
     * Ini otomatis multi-LOT karena setiap bundle punya lot_id masing-masing.
     */
    /**
     * Hitung cost bahan baku per pcs (MULTI-LOT):
     * - ambil CuttingJobBundle.finished_item_id = FG ini
     * - filter CuttingJob.date di antara periode
     * - pakai qty_used_fabric + lot avg cost (per LOT di gudang RM)
     * - FG OK pakai qty_qc_ok (kalau ada > 0), fallback ke qty_cutting_ok / qty_pcs
     * - rumus: Σ (unit_cost_LOT × qty_used_fabric_bundle) / Σ qty_FG_OK
     *
     * Ini otomatis multi-LOT karena setiap bundle punya lot_id masing-masing.
     */
    /**
     * Hitung cost bahan baku per pcs (MULTI-LOT):
     * - ambil CuttingJobBundle.finished_item_id = FG ini
     * - filter CuttingJob.date di antara periode
     * - pakai qty_used_fabric + lot avg cost (per LOT di gudang RM)
     * - FG OK pakai qty_qc_ok (kalau ada > 0), fallback ke qty_cutting_ok / qty_pcs
     * - rumus: Σ (unit_cost_LOT × qty_used_fabric_bundle) / Σ qty_FG_OK
     *
     * Ini otomatis multi-LOT karena setiap bundle punya lot_id masing-masing.
     */
    public function calculateRmCostPerUnitFromCutting(
        int $finishedItemId,
        string $dateFrom,
        string $dateTo,
    ): float {
        /** @var \Illuminate\Support\Collection<int, \App\Models\CuttingJobBundle> $bundles */
        $bundles = CuttingJobBundle::query()
            ->where('finished_item_id', $finishedItemId)
            ->whereHas('cuttingJob', function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('date', [$dateFrom, $dateTo]);
            })
            ->with(['cuttingJob', 'lot'])
            ->get();

        if ($bundles->isEmpty()) {
            return 0.0;
        }

        $totalRmCost = 0.0;
        $totalFgOk = 0.0;

        foreach ($bundles as $bundle) {
            // qty kain yg dipakai untuk bundle ini
            $rmQty = (float) ($bundle->qty_used_fabric ?? 0);
            if ($rmQty <= 0) {
                continue;
            }

            // 🔁 FG OK: prioritas qty_qc_ok > qty_cutting_ok > qty_pcs
            $qtyQcOk = (float) ($bundle->qty_qc_ok ?? 0);
            $qtyCuttingOk = (float) ($bundle->qty_cutting_ok ?? 0);
            $qtyPcs = (float) ($bundle->qty_pcs ?? 0);

            if ($qtyQcOk > 0) {
                $fgOk = $qtyQcOk;
            } elseif ($qtyCuttingOk > 0) {
                $fgOk = $qtyCuttingOk;
            } else {
                $fgOk = $qtyPcs; // fallback terakhir
            }

            if ($fgOk <= 0) {
                continue;
            }

            $job = $bundle->cuttingJob;
            $lot = $bundle->lot;

            if (!$job || !$lot) {
                continue;
            }

            $rmWarehouseId = (int) $job->warehouse_id;
            $rmItemId = (int) $lot->item_id;
            $lotId = (int) $bundle->lot_id;

            if (!$rmWarehouseId || !$rmItemId || !$lotId) {
                continue;
            }

            // unit cost per LOT → sudah moving average per LOT (multi-LOT)
            $lotCost = $this->inventory->getLotMovingAverageUnitCost(
                warehouseId: $rmWarehouseId,
                itemId: $rmItemId,
                lotId: $lotId,
            );

            if ($lotCost === null) {
                continue;
            }

            // akumulasi cost dan FG hasil
            $totalRmCost += $lotCost * $rmQty;
            $totalFgOk += $fgOk;
        }

        if ($totalFgOk <= 0) {
            return 0.0;
        }

        // Rupiah per pcs FG
        return round($totalRmCost / $totalFgOk, 4);
    }

    /**
     * Hitung cost payroll Cutting per pcs:
     * - PieceworkPayrollPeriod.module = 'cutting'
     * - status = final/posted
     * - periode overlapped dengan range yg diminta
     */
    private function calculateCuttingPayrollCostPerUnit(
        int $finishedItemId,
        string $dateFrom,
        string $dateTo,
    ): float {
        $lines = PieceworkPayrollLine::query()
            ->where('item_id', $finishedItemId)
            ->whereHas('payrollPeriod', function ($q) use ($dateFrom, $dateTo) {
                $q->where('module', 'cutting')
                    ->whereIn('status', ['final', 'posted'])
                    ->whereDate('period_start', '<=', $dateTo)
                    ->whereDate('period_end', '>=', $dateFrom);
            })
            ->get();

        if ($lines->isEmpty()) {
            return 0.0;
        }

        $totalAmount = (float) $lines->sum('amount');
        $totalQty = (float) $lines->sum('total_qty_ok');

        if ($totalQty <= 0.000001) {
            return 0.0;
        }

        return $totalAmount / $totalQty; // Rp/pcs
    }

    /**
     * Hitung cost payroll Sewing per pcs:
     * - PieceworkPayrollPeriod.module = 'sewing'
     * - status = final/posted
     * - periode overlapped dgn range
     */
    private function calculateSewingPayrollCostPerUnit(
        int $finishedItemId,
        string $dateFrom,
        string $dateTo,
    ): float {
        $lines = PieceworkPayrollLine::query()
            ->where('item_id', $finishedItemId)
            ->whereHas('payrollPeriod', function ($q) use ($dateFrom, $dateTo) {
                $q->where('module', 'sewing')
                    ->whereIn('status', ['final', 'posted'])
                    ->whereDate('period_start', '<=', $dateTo)
                    ->whereDate('period_end', '>=', $dateFrom);
            })
            ->get();

        if ($lines->isEmpty()) {
            return 0.0;
        }

        $totalAmount = (float) $lines->sum('amount');
        $totalQty = (float) $lines->sum('total_qty_ok');

        if ($totalQty <= 0.000001) {
            return 0.0;
        }

        return $totalAmount / $totalQty;
    }

    /**
     * Basis qty HPP = total qty OK Sewing periode ini.
     * Logika sama dgn calculateSewingPayrollCostPerUnit supaya konsisten.
     */
    protected function getFgQtyBasisFromSewingPayroll(
        int $finishedItemId,
        string $dateFrom,
        string $dateTo,
    ): float {
        $lines = PieceworkPayrollLine::query()
            ->where('item_id', $finishedItemId)
            ->whereHas('payrollPeriod', function ($q) use ($dateFrom, $dateTo) {
                $q->where('module', 'sewing')
                    ->whereIn('status', ['final', 'posted'])
                    ->whereDate('period_start', '<=', $dateTo)
                    ->whereDate('period_end', '>=', $dateFrom);
            })
            ->get();

        return (float) $lines->sum('total_qty_ok');
    }

    private function calculateFinishingCostPerUnit(
        int $finishedItemId,
        string $dateFrom,
        string $dateTo,
    ): float {
        return 0.0;
    }
}
