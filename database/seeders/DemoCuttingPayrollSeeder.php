<?php

namespace Database\Seeders;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoCuttingPayrollSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();

        // 1. EMPLOYEE (operator cutting)
        $mrf = Employee::updateOrCreate(
            ['code' => 'MRF'],
            [
                'name' => 'Operator Cutting MRF',
                'role' => 'cutting',
                'active' => 1,
                'default_piece_rate' => 400,
            ]
        );

        // 2. ITEM CATEGORY
        $kaosCategory = ItemCategory::firstOrCreate(
            ['code' => 'KAOS'],
            [
                'name' => 'Kaos Oblong',
            ]
        );

        // 3. ITEMS
        $itemK7BLK = Item::firstOrCreate(
            ['code' => 'K7BLK'],
            [
                'name' => 'Kaos Hitam Lengan Pendek',
                'item_category_id' => $kaosCategory->id,
                'type' => 'finished_good',
            ]
        );

        $itemK7WHT = Item::firstOrCreate(
            ['code' => 'K7WHT'],
            [
                'name' => 'Kaos Putih Lengan Pendek',
                'item_category_id' => $kaosCategory->id,
                'type' => 'finished_good',
            ]
        );

        // 4. WAREHOUSE untuk cutting job
        $cuttingWarehouse = Warehouse::firstOrCreate(
            ['code' => 'CUT-DEMO'],
            [
                'name' => 'Gudang Cutting Demo',
                'type' => 'cutting',
                'active' => 1,
            ]
        );

        // 5. CUTTING JOB (header)
        $cuttingJob = CuttingJob::firstOrCreate(
            ['code' => 'CUT-DEMO-001'],
            [
                'date' => $today->copy()->subDay()->toDateString(), // kemarin
                'status' => 'done',
                'operator_id' => $mrf->id,
                'warehouse_id' => $cuttingWarehouse->id,
                'lot_id' => 1, // sesuaikan kalau nanti pakai lot beneran
            ]
        );

        // 6. CUTTING JOB BUNDLES
        CuttingJobBundle::firstOrCreate(
            [
                'cutting_job_id' => $cuttingJob->id,
                'bundle_no' => 1,
            ],
            [
                'bundle_code' => 'BND-DEMO-001-01',
                'lot_id' => null,
                'finished_item_id' => $itemK7BLK->id,
                'item_category_id' => $kaosCategory->id,
                'qty_pcs' => 50,
                'qty_used_fabric' => 0,
                'operator_id' => $mrf->id,
                'status' => 'qc_done',
                'notes' => 'Demo bundle K7BLK',
                'qty_qc_ok' => 50,
                'qty_qc_reject' => 0,
                'wip_warehouse_id' => null,
                'wip_qty' => 0,
            ]
        );

        CuttingJobBundle::firstOrCreate(
            [
                'cutting_job_id' => $cuttingJob->id,
                'bundle_no' => 2,
            ],
            [
                'bundle_code' => 'BND-DEMO-001-02',
                'lot_id' => null,
                'finished_item_id' => $itemK7WHT->id,
                'item_category_id' => $kaosCategory->id,
                'qty_pcs' => 30,
                'qty_used_fabric' => 0,
                'operator_id' => $mrf->id,
                'status' => 'qc_done',
                'notes' => 'Demo bundle K7WHT',
                'qty_qc_ok' => 30,
                'qty_qc_reject' => 0,
                'wip_warehouse_id' => null,
                'wip_qty' => 0,
            ]
        );
    }
}
