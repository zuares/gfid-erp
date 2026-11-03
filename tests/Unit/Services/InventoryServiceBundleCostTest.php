<?php

namespace Tests\Unit\Services;

use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryServiceBundleCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_bundle_wip_cost_does_not_mix_other_historical_wip_receipts(): void
    {
        $wipCutId = DB::table('warehouses')->insertGetId([
            'code' => 'WIP-CUT-TEST',
            'name' => 'WIP Cutting Test',
            'type' => 'wip',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $itemId = DB::table('items')->insertGetId([
            'code' => 'FG-COST-TEST',
            'name' => 'FG Cost Test',
            'unit' => 'pcs',
            'type' => 'finished',
            'last_purchase_price' => 0,
            'hpp' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lotId = DB::table('lots')->insertGetId([
            'code' => 'LOT-COST-TEST',
            'item_id' => $itemId,
            'initial_qty' => 1,
            'initial_cost' => 0,
            'qty_onhand' => 1,
            'total_cost' => 0,
            'avg_cost' => 0,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $jobId = DB::table('cutting_jobs')->insertGetId([
            'code' => 'CUT-COST-TEST',
            'date' => '2026-10-28',
            'warehouse_id' => $wipCutId,
            'lot_id' => $lotId,
            'fabric_item_id' => $itemId,
            'total_bundles' => 1,
            'total_qty_pcs' => 9,
            'status' => 'qc_done',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $bundleId = DB::table('cutting_job_bundles')->insertGetId([
            'cutting_job_id' => $jobId,
            'bundle_code' => 'BND-COST-TEST',
            'bundle_no' => 1,
            'lot_id' => $lotId,
            'finished_item_id' => $itemId,
            'qty_pcs' => 9,
            'qty_used_fabric' => 1,
            'status' => 'qc_ok',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Histori lama item yang sama tidak boleh ikut menentukan cost bundle.
        DB::table('inventory_mutations')->insert([
            'date' => '2026-10-27',
            'warehouse_id' => $wipCutId,
            'item_id' => $itemId,
            'qty_change' => 100,
            'direction' => 'in',
            'source_type' => 'cutting_wip',
            'source_id' => 1,
            'cutting_job_bundle_id' => null,
            'unit_cost' => 5650,
            'total_cost' => 565000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_mutations')->insert([
            'date' => '2026-10-28',
            'warehouse_id' => $wipCutId,
            'item_id' => $itemId,
            'qty_change' => 9,
            'direction' => 'in',
            'source_type' => 'cutting_wip',
            'source_id' => $jobId,
            'cutting_job_bundle_id' => $bundleId,
            'unit_cost' => 19281.5,
            'total_cost' => 173533.5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $inventory = app(InventoryService::class);

        $this->assertEqualsWithDelta(
            (565000 + 173533.5) / 109,
            $inventory->getItemIncomingUnitCost($wipCutId, $itemId),
            0.001,
        );
        $this->assertEqualsWithDelta(
            19281.5,
            $inventory->getBundleWipUnitCost($wipCutId, $itemId, $bundleId),
            0.001,
        );
    }

    public function test_bundle_wip_cost_returns_zero_when_bundle_output_is_missing(): void
    {
        $inventory = app(InventoryService::class);

        $this->assertSame(0.0, $inventory->getBundleWipUnitCost(999999, 999999, 999999));
    }
}
