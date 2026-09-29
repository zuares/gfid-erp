<?php

namespace Tests\Unit\Services;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\CuttingJobLot;
use App\Models\Item;
use App\Models\Lot;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Services\Payroll\PieceRateService;
use App\Services\Production\CuttingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuttingServiceMultiMaterialTest extends TestCase
{
    use RefreshDatabase;

    public function test_cutting_stock_out_uses_the_material_of_each_lot(): void
    {
        $warehouse = Warehouse::create([
            'code' => 'RM',
            'name' => 'Raw Materials',
            'type' => 'internal',
            'active' => true,
        ]);

        $materialA = Item::create([
            'code' => 'CUT-MAT-A',
            'name' => 'Cutting Material A',
            'unit' => 'kg',
            'type' => 'material',
            'item_role' => 'raw_material',
            'active' => true,
        ]);
        $materialB = Item::create([
            'code' => 'CUT-MAT-B',
            'name' => 'Cutting Material B',
            'unit' => 'kg',
            'type' => 'material',
            'item_role' => 'raw_material',
            'active' => true,
        ]);
        $finished = Item::create([
            'code' => 'CUT-FG-MULTI',
            'name' => 'Cutting Finished Good',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
        ]);

        $lotA = Lot::create([
            'code' => 'CUT-LOT-A',
            'item_id' => $materialA->id,
            'initial_qty' => 10,
            'initial_cost' => 10,
            'qty_onhand' => 10,
            'total_cost' => 10,
            'avg_cost' => 1,
        ]);
        $lotB = Lot::create([
            'code' => 'CUT-LOT-B',
            'item_id' => $materialB->id,
            'initial_qty' => 10,
            'initial_cost' => 10,
            'qty_onhand' => 10,
            'total_cost' => 10,
            'avg_cost' => 1,
        ]);

        $job = CuttingJob::create([
            'code' => 'CUT-MULTI-MATERIAL',
            'date' => '2026-09-30',
            'warehouse_id' => $warehouse->id,
            'lot_id' => $lotA->id,
            'fabric_item_id' => $materialA->id,
            'total_bundles' => 2,
            'total_qty_pcs' => 2,
            'status' => 'cut',
        ]);

        CuttingJobBundle::create([
            'cutting_job_id' => $job->id,
            'bundle_code' => 'CUT-MULTI-BUNDLE-A',
            'bundle_no' => 1,
            'lot_id' => $lotA->id,
            'finished_item_id' => $finished->id,
            'qty_pcs' => 1,
            'qty_used_fabric' => 1,
            'status' => 'cut',
        ]);
        CuttingJobBundle::create([
            'cutting_job_id' => $job->id,
            'bundle_code' => 'CUT-MULTI-BUNDLE-B',
            'bundle_no' => 2,
            'lot_id' => $lotB->id,
            'finished_item_id' => $finished->id,
            'qty_pcs' => 1,
            'qty_used_fabric' => 2,
            'status' => 'cut',
        ]);
        CuttingJobLot::create([
            'cutting_job_id' => $job->id,
            'lot_id' => $lotA->id,
            'planned_fabric_qty' => 1,
        ]);
        CuttingJobLot::create([
            'cutting_job_id' => $job->id,
            'lot_id' => $lotB->id,
            'planned_fabric_qty' => 2,
        ]);

        $stockOuts = [];
        $inventory = \Mockery::mock(InventoryService::class);
        $inventory->shouldReceive('stockOut')
            ->twice()
            ->andReturnUsing(function (...$args) use (&$stockOuts) {
                $stockOuts[] = [
                    'item_id' => (int) $args[1],
                    'qty' => (float) $args[2],
                    'lot_id' => (int) $args[8],
                ];

                return null;
            });

        $service = new CuttingService($inventory, new PieceRateService());
        $service->reconsumeFabricFromLots($job);

        $this->assertEqualsCanonicalizing([
            ['item_id' => $materialA->id, 'qty' => 1.0, 'lot_id' => $lotA->id],
            ['item_id' => $materialB->id, 'qty' => 2.0, 'lot_id' => $lotB->id],
        ], $stockOuts);
    }
}
