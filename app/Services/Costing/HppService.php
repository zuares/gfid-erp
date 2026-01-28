<?php

namespace App\Services\Costing;

use App\Models\CuttingJob;
use App\Models\ItemCostSnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class HppService
{
    // =========================================================
    //  A) HITUNG HPP PROSES (CUTTING/SEWING/FINISHING/PACKAGING)
    // =========================================================

    public function calculateCuttingHpp(float | int | string $rmTotalCost, float | int | string $totalQtyOk): float
    {
        $rmTotalCost = $this->num($rmTotalCost);
        $totalQtyOk = $this->num($totalQtyOk);

        if ($totalQtyOk <= 0) {
            throw new \RuntimeException('Qty OK harus > 0 untuk hitung HPP Cutting.');
        }

        return round($rmTotalCost / $totalQtyOk, 4);
    }

    public function calculateSewingHpp(float | int | string $cuttingUnitCost, float | int | string $sewingLaborPerUnit): float
    {
        return round($this->num($cuttingUnitCost) + $this->num($sewingLaborPerUnit), 4);
    }

    public function calculateFinishingHpp(float | int | string $sewingUnitCost, float | int | string $finishingPerUnit): float
    {
        return round($this->num($sewingUnitCost) + $this->num($finishingPerUnit), 4);
    }

    public function calculatePackagingHpp(float | int | string $packagingPerUnit): float
    {
        return round($this->num($packagingPerUnit), 4);
    }

    public function calculateTotalHpp(array $components): float
    {
        $total = 0.0;
        foreach ($components as $value) {
            $total += $this->num($value);
        }
        return round($total, 4);
    }

    // =========================================================
    //  B) SNAPSHOT COSTING (UNTUK SALES/LAPORAN)
    // =========================================================

    public function createSnapshot(array $data): ItemCostSnapshot
    {
        $snapshotDate = $this->normalizeDate($data['snapshot_date'] ?? null);

        $rm = $this->num($data['rm_unit_cost'] ?? 0);
        $cutting = $this->num($data['cutting_unit_cost'] ?? 0);
        $sewing = $this->num($data['sewing_unit_cost'] ?? 0);
        $finishing = $this->num($data['finishing_unit_cost'] ?? 0);
        $packaging = $this->num($data['packaging_unit_cost'] ?? 0);
        $overhead = $this->num($data['overhead_unit_cost'] ?? 0);

        $totalUnitCost = $this->calculateTotalHpp([$rm, $cutting, $sewing, $finishing, $packaging, $overhead]);

        $snapshot = new ItemCostSnapshot();
        $snapshot->item_id = (int) $data['item_id'];
        $snapshot->warehouse_id = array_key_exists('warehouse_id', $data) ? (is_null($data['warehouse_id']) ? null : (int) $data['warehouse_id']) : null;
        $snapshot->snapshot_date = $snapshotDate;
        $snapshot->reference_type = $data['reference_type'] ?? null;
        $snapshot->reference_id = $data['reference_id'] ?? null;

        $snapshot->qty_basis = $this->num($data['qty_basis'] ?? 0);

        $snapshot->rm_unit_cost = $rm;
        $snapshot->cutting_unit_cost = $cutting;
        $snapshot->sewing_unit_cost = $sewing;
        $snapshot->finishing_unit_cost = $finishing;
        $snapshot->packaging_unit_cost = $packaging;
        $snapshot->overhead_unit_cost = $overhead;

        $snapshot->unit_cost = $totalUnitCost;
        $snapshot->notes = $data['notes'] ?? null;
        $snapshot->is_active = (bool) ($data['is_active'] ?? false);
        $snapshot->created_by = Auth::id();

        $snapshot->save();

        return $snapshot;
    }

    public function getActiveSnapshotForItem(int $itemId, ?int $warehouseId = null): ?ItemCostSnapshot
    {
        return ItemCostSnapshot::query()
            ->where('item_id', $itemId)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->where('is_active', true)
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();
    }

    public function getSnapshotForSale(int $itemId, ?int $warehouseId, string $saleDate): ?ItemCostSnapshot
    {
        return ItemCostSnapshot::query()
            ->where('item_id', $itemId)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->where('is_active', true)
            ->whereDate('snapshot_date', '<=', $saleDate)
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();
    }

    public function getUnitCostForSale(int $itemId, ?int $warehouseId, string $saleDate): float
    {
        return (float) ($this->getSnapshotForSale($itemId, $warehouseId, $saleDate)?->unit_cost ?? 0.0);
    }

    // Alias biar controller lama tidak rusak
    public function getActiveFinalHppForItem(int $itemId, ?int $warehouseId = null): ?ItemCostSnapshot
    {
        return $this->getActiveSnapshotForItem($itemId, $warehouseId);
    }

    // =========================================================
    //  C) JOB COSTING (CUTTING JOB) - dari Production service
    // =========================================================

    public function calculateForCuttingJob(CuttingJob $job, float $overheadPerPcs = 0.0): array
    {
        $job->loadMissing(['details.finishedItem', 'bundles', 'labours']);

        $totalQty = (float) ($job->total_output_pcs ?? 0);

        if ($totalQty <= 0) {
            return [
                'job_id' => $job->id,
                'job_code' => $job->code,
                'summary' => [
                    'total_qty' => 0,
                    'total_fabric_cost' => 0,
                    'total_labour_cost' => 0,
                    'total_overhead_cost' => 0,
                    'total_hpp' => 0,
                    'hpp_per_pcs' => 0,
                ],
                'items' => [],
            ];
        }

        $totalFabricCost = (float) ($job->fabric_usage_cost ?? 0);
        $totalLabourCost = (float) $job->labours->sum('total_cost');

        $totalOverheadCost = $overheadPerPcs > 0 ? ($overheadPerPcs * $totalQty) : 0.0;
        $totalHpp = $totalFabricCost + $totalLabourCost + $totalOverheadCost;

        $hppPerPcs = $totalHpp / $totalQty;

        $items = [];
        foreach ($job->details as $detail) {
            $itemQty = (float) ($detail->total_pcs ?? 0);
            if ($itemQty <= 0) {
                continue;
            }

            $share = $itemQty / $totalQty;

            $itemFabricCost = $totalFabricCost * $share;
            $itemLabourCost = $totalLabourCost * $share;
            $itemOverheadCost = $totalOverheadCost * $share;
            $itemTotalHpp = $itemFabricCost + $itemLabourCost + $itemOverheadCost;
            $itemHppPerPcs = $itemTotalHpp / $itemQty;

            $items[] = [
                'finished_item_id' => $detail->finished_item_id,
                'finished_item_code' => $detail->finishedItem?->code,
                'finished_item_name' => $detail->finishedItem?->name,
                'size_label' => $detail->size_label,
                'qty' => $itemQty,
                'fabric_cost' => round($itemFabricCost, 2),
                'labour_cost' => round($itemLabourCost, 2),
                'overhead_cost' => round($itemOverheadCost, 2),
                'hpp_total' => round($itemTotalHpp, 2),
                'hpp_per_pcs' => round($itemHppPerPcs, 2),
            ];
        }

        return [
            'job_id' => $job->id,
            'job_code' => $job->code,
            'summary' => [
                'total_qty' => $totalQty,
                'total_fabric_cost' => round($totalFabricCost, 2),
                'total_labour_cost' => round($totalLabourCost, 2),
                'total_overhead_cost' => round($totalOverheadCost, 2),
                'total_hpp' => round($totalHpp, 2),
                'hpp_per_pcs' => round($hppPerPcs, 2),
            ],
            'items' => $items,
        ];
    }

    // =========================================================
    //  D) UTIL
    // =========================================================

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
            // Format Indonesia: 1.234,56
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }

    protected function normalizeDate(string | \DateTimeInterface  | null $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date)->toDateString();
        }
        if (is_string($date) && trim($date) !== '') {
            return Carbon::parse($date)->toDateString();
        }
        return now()->toDateString();
    }
}
