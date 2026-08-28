<?php

namespace Tests\Feature\Payroll;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Lot;
use App\Models\PieceRate;
use App\Models\Warehouse;
use App\Services\Payroll\CuttingPayrollGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuttingPayrollGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_cutting_payroll_includes_bundle_that_has_not_been_qc(): void
    {
        [$employee, $item, $warehouse] = $this->makeMasterData();
        $job = $this->makeJob($employee, $item, $warehouse, 'cut');

        CuttingJobBundle::create([
            'cutting_job_id' => $job->id,
            'bundle_code' => 'BND-PAYROLL-NO-QC',
            'bundle_no' => 1,
            'lot_id' => $this->makeLot($item)->id,
            'finished_item_id' => $item->id,
            'item_category_id' => $item->item_category_id,
            'qty_pcs' => 10,
            'qty_qc_ok' => 0,
            'qty_qc_reject' => 0,
            'operator_id' => $employee->id,
            'status' => 'cut',
        ]);

        $period = CuttingPayrollGenerator::generate('2026-08-01', '2026-08-07');

        $this->assertSame(10.0, (float) $period->lines->sum('total_qty_ok'));
        $this->assertSame(15000.0, (float) $period->lines->sum('amount'));
    }

    public function test_cutting_payroll_uses_qc_ok_after_bundle_has_been_qc(): void
    {
        [$employee, $item, $warehouse] = $this->makeMasterData();
        $job = $this->makeJob($employee, $item, $warehouse, 'qc_done');

        CuttingJobBundle::create([
            'cutting_job_id' => $job->id,
            'bundle_code' => 'BND-PAYROLL-QC',
            'bundle_no' => 1,
            'lot_id' => $this->makeLot($item)->id,
            'finished_item_id' => $item->id,
            'item_category_id' => $item->item_category_id,
            'qty_pcs' => 10,
            'qty_qc_ok' => 7,
            'qty_qc_reject' => 3,
            'operator_id' => $employee->id,
            'status' => 'qc_mixed',
        ]);

        $period = CuttingPayrollGenerator::generate('2026-08-01', '2026-08-07');

        $this->assertSame(7.0, (float) $period->lines->sum('total_qty_ok'));
        $this->assertSame(10500.0, (float) $period->lines->sum('amount'));
    }

    private function makeMasterData(): array
    {
        $category = ItemCategory::create([
            'code' => 'CAT-CUT-PAYROLL-TEST-' . uniqid(),
            'name' => 'Kategori Cutting Payroll Test',
        ]);
        $item = Item::create([
            'code' => 'ITEM-CUT-PAYROLL-TEST-' . uniqid(),
            'name' => 'Item Cutting Payroll Test',
            'type' => 'finished_good',
            'item_category_id' => $category->id,
        ]);
        $employee = Employee::create([
            'code' => 'EMP-CUT-PAYROLL-TEST-' . uniqid(),
            'name' => 'Operator Cutting Payroll Test',
            'role' => 'cutting',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        $warehouse = Warehouse::create([
            'code' => 'WIP-CUT-PAYROLL-TEST-' . uniqid(),
            'name' => 'WIP Cutting Payroll Test',
            'type' => 'wip',
            'active' => true,
        ]);

        PieceRate::create([
            'module' => 'cutting',
            'employee_id' => $employee->id,
            'item_id' => $item->id,
            'rate_per_pcs' => 1500,
            'effective_from' => '2026-08-01',
        ]);

        return [$employee, $item, $warehouse];
    }

    private function makeJob(Employee $employee, Item $item, Warehouse $warehouse, string $status): CuttingJob
    {
        return CuttingJob::create([
            'code' => 'CUT-PAYROLL-TEST-' . uniqid(),
            'date' => '2026-08-04',
            'warehouse_id' => $warehouse->id,
            'lot_id' => $this->makeLot($item)->id,
            'fabric_item_id' => $item->id,
            'operator_id' => $employee->id,
            'total_bundles' => 1,
            'total_qty_pcs' => 10,
            'status' => $status,
        ]);
    }

    private function makeLot(Item $item): Lot
    {
        return Lot::create([
            'code' => 'LOT-CUT-PAYROLL-TEST-' . uniqid(),
            'item_id' => $item->id,
            'initial_qty' => 100,
            'initial_cost' => 100,
            'qty_onhand' => 100,
            'total_cost' => 100,
            'avg_cost' => 1,
        ]);
    }
}
