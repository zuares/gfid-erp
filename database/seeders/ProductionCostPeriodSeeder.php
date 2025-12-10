<?php

namespace Database\Seeders;

use App\Models\PieceworkPayrollPeriod;
use App\Models\ProductionCostPeriod;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ProductionCostPeriodSeeder extends Seeder
{
    public function run(): void
    {
        // Kalau sudah ada data, jangan seed lagi
        if (ProductionCostPeriod::count() > 0) {
            return;
        }

        $cuttingPeriod = PieceworkPayrollPeriod::query()
            ->where('module', 'cutting')
            ->orderByDesc('date_to')
            ->first();

        $sewingPeriod = PieceworkPayrollPeriod::query()
            ->where('module', 'sewing')
            ->orderByDesc('date_to')
            ->first();

        $dateFrom = Carbon::create(2025, 12, 1)->startOfDay();
        $dateTo = Carbon::create(2025, 12, 31)->endOfDay();

        ProductionCostPeriod::create([
            'code' => 'PCP-202512-001',
            'name' => 'HPP Desember 2025 (Auto seed)',

            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'snapshot_date' => $dateTo->toDateString(),

            'cutting_payroll_period_id' => $cuttingPeriod?->id,
            'sewing_payroll_period_id' => $sewingPeriod?->id,
            'finishing_payroll_period_id' => null,

            'status' => 'draft',
            'is_active' => false,
            'notes' => 'Contoh periode costing awal, nanti dipakai generate HPP dari payroll.',
            'created_by' => 1,
        ]);
    }
}
