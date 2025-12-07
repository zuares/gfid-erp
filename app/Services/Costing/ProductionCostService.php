<?php

namespace App\Services\Costing;

use App\Models\Item;
use App\Models\ItemCostSnapshot;
use App\Models\PieceworkPayrollLine;
use App\Models\ProductionCostPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductionCostService
{
    public function __construct(
        protected HppService $hpp,
    ) {}

    /**
     * Generate costing 1 periode → buat snapshot HPP FINAL baru.
     *
     * Langkah:
     * 1. Cari item FG yang relevan (punya produksi / payroll di periode).
     * 2. Hitung RM/unit (dari RM-only snapshot Finishing via HppService).
     * 3. Hitung biaya cutting / sewing / finishing dari payroll period yang ter-link.
     * 4. Buat snapshot HPP final (reference_type = production_cost_period).
     * 5. Set snapshot ini sebagai aktif (via HppService).
     */
    public function generateFromPayroll(ProductionCostPeriod $period): array
    {
        $results = [];

        DB::transaction(function () use ($period, &$results) {
            $dateFrom = Carbon::parse($period->date_from)->toDateString();
            $dateTo = Carbon::parse($period->date_to)->toDateString();
            $snapshotDate = Carbon::parse($period->snapshot_date)->toDateString();

            // 0) Cari item FG relevan (ada finishing OK, atau muncul di payroll periode ini)
            $itemIds = $this->getRelevantFinishedItemIdsForPeriod($period, $dateFrom, $dateTo);

            if (empty($itemIds)) {
                $period->update([
                    'status' => 'posted',
                    'is_active' => true,
                ]);
                return;
            }

            $finishedGoods = Item::query()
                ->where('type', 'finished_good')
                ->whereIn('id', $itemIds)
                ->get();

            $defaultPackagingUnitCost = 0.0;
            $defaultOverheadUnitCost = 0.0;

            foreach ($finishedGoods as $item) {
                $itemId = $item->id;

                $rmUnitCost = $this->calculateRmCostPerUnit($itemId, $dateFrom, $dateTo);
                $cuttingUnitCost = $this->calculatePayrollCostPerUnit($period->cutting_payroll_period_id, $itemId);
                $sewingUnitCost = $this->calculatePayrollCostPerUnit($period->sewing_payroll_period_id, $itemId);
                $finishingUnitCost = $this->calculatePayrollCostPerUnit($period->finishing_payroll_period_id, $itemId);

                $packagingUnitCost = $defaultPackagingUnitCost;
                $overheadUnitCost = $defaultOverheadUnitCost;

                $qtyBasis = $this->getProductionQty($itemId, $dateFrom, $dateTo);

                if ($this->isNoCostAndNoQty(
                    $rmUnitCost,
                    $cuttingUnitCost,
                    $sewingUnitCost,
                    $finishingUnitCost,
                    $packagingUnitCost,
                    $overheadUnitCost,
                    $qtyBasis,
                )) {
                    continue;
                }

                $totalHpp =
                    $rmUnitCost
                     + $cuttingUnitCost
                     + $sewingUnitCost
                     + $finishingUnitCost
                     + $packagingUnitCost
                     + $overheadUnitCost;

                // 7) Upsert SNAPSHOT HPP FINAL untuk periode ini
                $snapshot = ItemCostSnapshot::updateOrCreate(
                    [
                        'item_id' => $itemId,
                        'warehouse_id' => null,
                        'reference_type' => 'production_cost_period',
                        'reference_id' => $period->id,
                    ],
                    [
                        'snapshot_date' => $snapshotDate,
                        'qty_basis' => $qtyBasis,
                        'rm_unit_cost' => $rmUnitCost,
                        'cutting_unit_cost' => $cuttingUnitCost,
                        'sewing_unit_cost' => $sewingUnitCost,
                        'finishing_unit_cost' => $finishingUnitCost,
                        'packaging_unit_cost' => $packagingUnitCost,
                        'overhead_unit_cost' => $overheadUnitCost,
                        'unit_cost' => $totalHpp,
                        'notes' => "Auto HPP via ProductionCostPeriod {$period->code}",
                        // kalau mau isi created_by saat pertama kali dibuat:
                        'created_by' => auth()->id(),
                    ],
                );

                // Pastikan snapshot ini yang aktif untuk tipe 'production_cost_period'
                $this->hpp->setActiveSnapshot($snapshot, exclusiveWithinType: true);

                $results[] = [
                    'item_code' => $item->code,
                    'item_name' => $item->name,
                    'qty_basis' => $qtyBasis,
                    'rm' => $rmUnitCost,
                    'cutting' => $cuttingUnitCost,
                    'sewing' => $sewingUnitCost,
                    'finishing' => $finishingUnitCost,
                    'packaging' => $packagingUnitCost,
                    'overhead' => $overheadUnitCost,
                    'total_hpp' => $totalHpp,
                    'snapshot_id' => $snapshot->id,
                ];
            }

            $period->update([
                'status' => 'posted',
                'is_active' => true,
            ]);
        });

        return $results;
    }

    /**
     * Helper: cek apakah benar-benar tidak ada cost + tidak ada qty.
     */
    protected function isNoCostAndNoQty(
        float $rm,
        float $cut,
        float $sew,
        float $fin,
        float $pack,
        float $oh,
        float $qtyBasis,
    ): bool {
        $noCost = (
            $rm == 0.0
            && $cut == 0.0
            && $sew == 0.0
            && $fin == 0.0
            && $pack == 0.0
            && $oh == 0.0
        );

        return $noCost && $qtyBasis <= 0.0;
    }

    /**
     * Cari item FG relevan:
     * - Punya produksi di finishing_jobs (qty_ok > 0) dalam range
     * - ATAU muncul di payroll cutting/sewing/finishing yg di-link ke periode
     */
    protected function getRelevantFinishedItemIdsForPeriod(
        ProductionCostPeriod $period,
        string $dateFrom,
        string $dateTo,
    ): array {
        // Dari produksi finishing
        $fromFinishing = DB::table('finishing_job_lines')
            ->join('finishing_jobs', 'finishing_jobs.id', '=', 'finishing_job_lines.finishing_job_id')
            ->where('finishing_jobs.status', 'posted')
            ->whereBetween('finishing_jobs.date', [$dateFrom, $dateTo])
            ->where('finishing_job_lines.qty_ok', '>', 0)
            ->pluck('finishing_job_lines.item_id')
            ->map(fn($id) => (int) $id);

        // Dari payroll (cutting / sewing / finishing) yang sudah dipilih di periode
        $payrollPeriodIds = array_filter([
            $period->cutting_payroll_period_id,
            $period->sewing_payroll_period_id,
            $period->finishing_payroll_period_id,
        ]);

        $fromPayroll = collect();
        if (!empty($payrollPeriodIds)) {
            $fromPayroll = PieceworkPayrollLine::query()
                ->whereIn('payroll_period_id', $payrollPeriodIds)
                ->whereNotNull('item_id')
                ->pluck('item_id')
                ->map(fn($id) => (int) $id);
        }

        return $fromFinishing
            ->merge($fromPayroll)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Ambil RM cost/unit dari basis RM-only Finishing (via HppService).
     *
     * Saat ini: pakai snapshot <= $dateTo.
     * Kalau nanti mau lebih ketat range [dateFrom, dateTo], logic ada di HppService.
     */
    protected function calculateRmCostPerUnit(int $itemId, string $dateFrom, string $dateTo): float
    {
        return $this->hpp->getRmUnitCostForItem($itemId, $dateTo);
    }

    /**
     * Hitung biaya per unit berdasarkan payroll (cutting, sewing, finishing).
     *
     * Logic:
     * - Ambil semua PieceworkPayrollLine untuk payroll_period_id + item_id
     * - total_amount / total_qty_ok → unit cost
     */
    protected function calculatePayrollCostPerUnit(?int $payrollPeriodId, int $itemId): float
    {
        if (!$payrollPeriodId) {
            return 0.0;
        }

        $lines = PieceworkPayrollLine::query()
            ->where('payroll_period_id', $payrollPeriodId)
            ->where('item_id', $itemId)
            ->get();

        if ($lines->isEmpty()) {
            return 0.0;
        }

        $totalQty = (float) $lines->sum('total_qty_ok');
        $totalAmount = (float) $lines->sum('amount');

        if ($totalQty <= 0.0) {
            return 0.0;
        }

        return round($totalAmount / $totalQty, 4);
    }

    /**
     * Ambil qty produksi FG OK dalam periode → dari FinishingJob (finishing_jobs + finishing_job_lines).
     */
    protected function getProductionQty(int $itemId, string $dateFrom, string $dateTo): float
    {
        return (float) DB::table('finishing_job_lines')
            ->join('finishing_jobs', 'finishing_jobs.id', '=', 'finishing_job_lines.finishing_job_id')
            ->where('finishing_jobs.status', 'posted')
            ->where('finishing_job_lines.item_id', $itemId)
            ->whereBetween('finishing_jobs.date', [$dateFrom, $dateTo])
            ->sum('finishing_job_lines.qty_ok');
    }
}
