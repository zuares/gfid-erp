<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionCostPeriodSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('production_cost_periods')->updateOrInsert(
            ['code' => 'PCP-202512-001'],
            [
                'code' => 'PCP-202512-001',
                'name' => 'HPP Desember 2025 (Auto seed)',
                'date_from' => '2025-12-01',
                'date_to' => '2025-12-31',
                'snapshot_date' => '2025-12-31',
                'status' => 'draft',
                'is_active' => 0,
                'notes' => 'Contoh periode costing awal, nanti dipakai generate HPP dari payroll.',
            ]
        );

    }
}
