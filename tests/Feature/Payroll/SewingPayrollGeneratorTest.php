<?php

namespace Tests\Feature\Payroll;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Lot;
use App\Models\SewingPickup;
use App\Models\SewingPickupLine;
use App\Models\Warehouse;
use App\Services\Payroll\SewingPayrollGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SewingPayrollGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_sewing_payroll_uses_picked_qty_even_when_nothing_has_been_returned(): void
    {
        [$employee, $item, $warehouse] = $this->makeMasterData();
        $pickup = SewingPickup::create([
            'code' => 'SWP-PAYROLL-001',
            'date' => '2026-08-05',
            'warehouse_id' => $warehouse->id,
            'operator_id' => $employee->id,
            'status' => 'posted',
        ]);

        SewingPickupLine::create([
            'sewing_pickup_id' => $pickup->id,
            'cutting_job_bundle_id' => $this->makeBundle($item)->id,
            'finished_item_id' => $item->id,
            'qty_bundle' => 10,
            'qty_returned_ok' => 0,
            'qty_returned_reject' => 0,
            'status' => 'in_progress',
            'wage_per_pcs' => 1500,
        ]);

        $period = SewingPayrollGenerator::generate('2026-08-01', '2026-08-07');

        $this->assertSame('sewing', $period->module);
        $this->assertSame(10.0, (float) $period->lines->sum('total_qty_ok'));
        $this->assertSame(15000.0, (float) $period->lines->sum('amount'));
        $this->assertSame(1500.0, (float) $period->lines->first()->rate_per_pcs);
    }

    public function test_void_pickup_line_is_not_included_in_sewing_payroll(): void
    {
        [$employee, $item, $warehouse] = $this->makeMasterData();
        $pickup = SewingPickup::create([
            'code' => 'SWP-PAYROLL-002',
            'date' => '2026-08-05',
            'warehouse_id' => $warehouse->id,
            'operator_id' => $employee->id,
            'status' => 'posted',
        ]);

        SewingPickupLine::create([
            'sewing_pickup_id' => $pickup->id,
            'cutting_job_bundle_id' => $this->makeBundle($item)->id,
            'finished_item_id' => $item->id,
            'qty_bundle' => 10,
            'status' => 'void',
            'wage_per_pcs' => 1500,
            'voided_at' => now(),
        ]);

        $period = SewingPayrollGenerator::generate('2026-08-01', '2026-08-07');

        $this->assertSame(0, $period->lines->count());
        $this->assertSame(0.0, (float) $period->total_amount);
    }

    private function makeMasterData(): array
    {
        $category = ItemCategory::create([
            'code' => 'FG-PAYROLL-TEST',
            'name' => 'Produk Payroll Test',
        ]);
        $item = Item::create([
            'code' => 'ITEM-PAYROLL-TEST',
            'name' => 'Item Payroll Test',
            'type' => 'finished_good',
            'item_category_id' => $category->id,
        ]);
        $employee = Employee::create([
            'code' => 'EMP-SEW-PAYROLL-TEST-'.uniqid(),
            'name' => 'Penjahit Payroll Test',
            'role' => 'sewing',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        $warehouse = Warehouse::create([
            'code' => 'WIP-SEW-TEST-'.uniqid(),
            'name' => 'WIP Sewing Test',
            'type' => 'wip',
            'active' => true,
        ]);

        return [$employee, $item, $warehouse];
    }

    private function makeBundle(Item $item): CuttingJobBundle
    {
        // Hanya diperlukan sebagai foreign key pada pickup line.
        $warehouse = Warehouse::firstOrCreate([
            'code' => 'WIP-CUT-TEST-'.uniqid(),
        ], [
            'name' => 'WIP Cutting Test',
            'type' => 'wip',
            'active' => true,
        ]);
        $lot = Lot::create([
            'code' => 'LOT-PAYROLL-'.uniqid(),
            'item_id' => $item->id,
            'initial_qty' => 100,
            'initial_cost' => 100,
            'qty_onhand' => 100,
            'total_cost' => 100,
            'avg_cost' => 1,
        ]);
        $job = CuttingJob::create([
            'code' => 'CUT-PAYROLL-'.uniqid(),
            'date' => '2026-08-04',
            'warehouse_id' => $warehouse->id,
            'lot_id' => $lot->id,
            'fabric_item_id' => $item->id,
            'operator_id' => null,
            'total_bundles' => 1,
            'total_qty_pcs' => 10,
            'status' => 'qc_done',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return CuttingJobBundle::create([
            'cutting_job_id' => $job->id,
            'bundle_code' => 'BND-PAYROLL-'.uniqid(),
            'bundle_no' => 1,
            'lot_id' => $lot->id,
            'finished_item_id' => $item->id,
            'qty_pcs' => 10,
            'qty_used_fabric' => 0,
            'operator_id' => null,
            'status' => 'qc_ok',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
