<?php

namespace App\Services\Costing;

use App\Models\ItemCostSnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class HppService
{
    /**
     * Hitung HPP Cutting dari total nilai RM / lot yang dipakai.
     */
    public function calculateCuttingHpp(
        float | int | string $rmTotalCost,
        float | int | string $totalQtyOk
    ): float {
        $rmTotalCost = $this->num($rmTotalCost);
        $totalQtyOk = $this->num($totalQtyOk);

        if ($totalQtyOk <= 0) {
            throw new \RuntimeException('Qty OK harus > 0 untuk hitung HPP Cutting.');
        }

        return round($rmTotalCost / $totalQtyOk, 4);
    }

    /**
     * HPP Sewing = HPP Cutting + biaya sewing (piece rate per unit).
     */
    public function calculateSewingHpp(
        float | int | string $cuttingUnitCost,
        float | int | string $sewingLaborPerUnit
    ): float {
        $cutting = $this->num($cuttingUnitCost);
        $sewingLabor = $this->num($sewingLaborPerUnit);

        return round($cutting + $sewingLabor, 4);
    }

    /**
     * HPP Finishing = HPP Sewing + biaya finishing per unit.
     */
    public function calculateFinishingHpp(
        float | int | string $sewingUnitCost,
        float | int | string $finishingPerUnit
    ): float {
        $sewing = $this->num($sewingUnitCost);
        $finishing = $this->num($finishingPerUnit);

        return round($sewing + $finishing, 4);
    }

    /**
     * HPP Packaging = biaya packaging per unit (langsung).
     */
    public function calculatePackagingHpp(float | int | string $packagingPerUnit): float
    {
        return round($this->num($packagingPerUnit), 4);
    }

    /**
     * Hitung total HPP final (FG) dari semua komponen per unit.
     */
    public function calculateTotalHpp(array $components): float
    {
        // components: ['rm' => .., 'cutting' => .., 'sewing' => .., 'finishing' => .., 'packaging' => .., 'overhead' => ..]
        $total = 0.0;

        foreach ($components as $value) {
            $total += $this->num($value);
        }

        return round($total, 4);
    }

    /**
     * Simpan snapshot HPP satu item ke tabel item_cost_snapshots.
     *
     * @param array $data
     *   item_id (int)        → wajib
     *   warehouse_id (int?)  → opsional, misal WH-PRD / WH-RTS
     *   snapshot_date (string|\DateTimeInterface|null) → default: today
     *   reference_type (string|null) → opsional, misal 'cutting_job', 'payroll_period'
     *   reference_id (int|null)
     *   qty_basis (float|null) → total qty yang jadi basis perhitungan (misal total FG yang dihasilkan)
     *   rm_unit_cost, cutting_unit_cost, sewing_unit_cost,
     *   finishing_unit_cost, packaging_unit_cost, overhead_unit_cost
     *   notes (string|null)
     */
    public function createSnapshot(
        int $itemId,
        ?int $warehouseId,
        string $snapshotDate,
        ?string $referenceType,
        ?int $referenceId,
        ?float $qtyBasis,
        ?float $rmUnitCost,
        ?float $cuttingUnitCost,
        ?float $sewingUnitCost,
        ?float $finishingUnitCost,
        ?float $packagingUnitCost,
        ?float $overheadUnitCost,
        ?string $notes = null,
    ): ItemCostSnapshot {
        $totalUnitCost =
            ($rmUnitCost ?? 0) +
            ($cuttingUnitCost ?? 0) +
            ($sewingUnitCost ?? 0) +
            ($finishingUnitCost ?? 0) +
            ($packagingUnitCost ?? 0) +
            ($overheadUnitCost ?? 0);

        $snapshot = new ItemCostSnapshot();
        $snapshot->item_id = $itemId;
        $snapshot->warehouse_id = $warehouseId;
        $snapshot->snapshot_date = $snapshotDate;
        $snapshot->reference_type = $referenceType;
        $snapshot->reference_id = $referenceId;
        $snapshot->qty_basis = $qtyBasis;
        $snapshot->rm_unit_cost = $rmUnitCost;
        $snapshot->cutting_unit_cost = $cuttingUnitCost;
        $snapshot->sewing_unit_cost = $sewingUnitCost;
        $snapshot->finishing_unit_cost = $finishingUnitCost;
        $snapshot->packaging_unit_cost = $packagingUnitCost;
        $snapshot->overhead_unit_cost = $overheadUnitCost;
        $snapshot->total_unit_cost = $totalUnitCost;
        $snapshot->notes = $notes;
        $snapshot->created_by = Auth::id();
        $snapshot->save();

        return $snapshot;
    }

    /**
     * Helper: normalisasi angka (kayak di InventoryService::num()).
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

        if (strpos($value, ',') !== false) {
            // Format Indonesia: 1.234,56
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
            return (float) $value;
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
