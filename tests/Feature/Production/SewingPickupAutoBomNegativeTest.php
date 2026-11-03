<?php

namespace Tests\Feature\Production;

use App\Http\Controllers\Production\SewingPickupController;
use App\Models\SewingPickup;
use App\Models\SewingPickupSupplyLine;
use App\Models\User;
use App\Services\Accounting\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class SewingPickupAutoBomNegativeTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_bom_issues_full_requirement_and_records_stock_shortage(): void
    {
        config([
            'accounting.standalone_dual_posting_production_sewing_pickup_enabled' => true,
            'accounting.standalone_production_sewing_pickup_auto_bom_negative_enabled' => true,
        ]);

        $rmId = DB::table('warehouses')->insertGetId([
            'code' => 'RM', 'name' => 'Raw Material', 'type' => 'internal', 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $finishedId = $this->item('FG-AUTO-BOM', 'Finished Auto BOM', 'finished');
        $supplyId = $this->item('SUP-AUTO-BOM', 'Supply Auto BOM', 'raw');
        $bomId = DB::table('item_boms')->insertGetId([
            'item_id' => $finishedId, 'name' => 'BOM Auto Sewing', 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('item_bom_lines')->insert([
            'item_bom_id' => $bomId,
            'material_item_id' => $supplyId,
            'usage_stage' => 'sewing_supply',
            'qty' => 0.5,
            'uom' => 'pcs',
            'scrap_pct' => 0,
            'is_optional' => 0,
            'sort_order' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $pickup = SewingPickup::create([
            'code' => 'SWP-AUTO-BOM-TEST',
            'date' => '2026-10-29',
            'warehouse_id' => $rmId,
            'status' => 'draft',
            'idempotency_key' => 'auto-bom-test-1',
        ]);

        $controller = app(SewingPickupController::class);
        $method = new ReflectionMethod($controller, 'storeSewingPickupSupplies');
        $method->setAccessible(true);
        $method->invoke($controller, $pickup, [$finishedId => 4], null);

        $line = SewingPickupSupplyLine::query()->where('sewing_pickup_id', $pickup->id)->firstOrFail();
        $this->assertEqualsWithDelta(2.0, (float) $line->required_qty, 0.000001);
        $this->assertEqualsWithDelta(2.0, (float) $line->issued_qty, 0.000001);
        $this->assertEqualsWithDelta(2.0, (float) $line->stock_shortage_qty, 0.000001);
        $this->assertSame('open', $line->stock_shortage_status);
        $this->assertSame(0, DB::table('inventory_mutations')->where('source_id', $pickup->id)->count());
    }

    public function test_auto_bom_blocks_when_material_cost_is_missing(): void
    {
        config([
            'accounting.standalone_dual_posting_production_sewing_pickup_enabled' => true,
            'accounting.standalone_production_sewing_pickup_auto_bom_negative_enabled' => true,
        ]);

        $rmId = DB::table('warehouses')->insertGetId([
            'code' => 'RM', 'name' => 'Raw Material', 'type' => 'internal', 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $supplyId = $this->item('SUP-AUTO-COST', 'Supply Cost Missing', 'raw');
        $pickup = SewingPickup::create([
            'code' => 'SWP-AUTO-COST-TEST',
            'date' => '2026-10-29',
            'warehouse_id' => $rmId,
            'status' => 'draft',
        ]);
        SewingPickupSupplyLine::create([
            'sewing_pickup_id' => $pickup->id,
            'material_item_id' => $supplyId,
            'required_qty' => 1,
            'issued_qty' => 1,
            'required_pcs' => 1,
            'issued_pcs' => 1,
            'uom' => 'pcs',
            'stock_available_snapshot' => 0,
            'stock_shortage_qty' => 1,
            'stock_shortage_status' => 'open',
        ]);

        $controller = app(SewingPickupController::class);
        $method = new ReflectionMethod($controller, 'issueSewingPickupSupplies');
        $method->setAccessible(true);

        try {
            $method->invoke($controller, $pickup->fresh());
            $this->fail('Auto BOM harus berhenti bila cost material belum tersedia.');
        } catch (\ReflectionException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $previous = $exception->getPrevious();
            $message = $exception instanceof ValidationException
                ? $exception->getMessage()
                : ($previous?->getMessage() ?: $exception->getMessage());
            $this->assertStringContainsString('HPP kelengkapan', $message);
        }

        $this->assertSame(0, DB::table('inventory_mutations')
            ->where('source_type', JournalService::SRC_SEWING_PICKUP_SUPPLY)
            ->where('source_id', $pickup->id)
            ->count());
    }

    public function test_repeated_submit_with_same_idempotency_key_returns_existing_pickup(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-IDEMP']);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'WIP-SEW-IDEMP', 'name' => 'WIP Sewing Idempotency', 'type' => 'internal', 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $itemId = $this->item('FG-IDEMP', 'Finished Idempotency', 'finished');
        $lotId = DB::table('lots')->insertGetId([
            'code' => 'LOT-IDEMP', 'item_id' => $itemId, 'initial_qty' => 1, 'initial_cost' => 0,
            'qty_onhand' => 1, 'total_cost' => 0, 'avg_cost' => 0, 'status' => 'open',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $jobId = DB::table('cutting_jobs')->insertGetId([
            'code' => 'CUT-IDEMP', 'date' => '2026-10-29', 'warehouse_id' => $warehouseId,
            'lot_id' => $lotId, 'fabric_item_id' => $itemId, 'total_bundles' => 1, 'total_qty_pcs' => 1,
            'status' => 'cut', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $bundleId = DB::table('cutting_job_bundles')->insertGetId([
            'cutting_job_id' => $jobId, 'bundle_code' => 'BND-IDEMP', 'bundle_no' => 1, 'lot_id' => $lotId,
            'finished_item_id' => $itemId, 'qty_pcs' => 1, 'qty_used_fabric' => 0,
            'status' => 'cut', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $key = 'idempotency-sewing-pickup-test';
        $pickup = SewingPickup::create([
            'code' => 'SWP-IDEMP', 'date' => '2026-10-29', 'warehouse_id' => $warehouseId,
            'status' => 'draft', 'idempotency_key' => $key,
        ]);

        $response = $this->actingAs($owner)->post(route('production.sewing.pickups.store'), [
            'date' => '2026-10-29',
            'warehouse_id' => $warehouseId,
            'idempotency_key' => $key,
            'lines' => [['bundle_id' => $bundleId, 'qty_bundle' => 0]],
        ]);

        $response->assertRedirect(route('production.sewing.pickups.show', $pickup));
        $this->assertSame(1, SewingPickup::query()->where('idempotency_key', $key)->count());
    }

    private function item(string $code, string $name, string $type): int
    {
        return (int) DB::table('items')->insertGetId([
            'code' => $code, 'name' => $name, 'unit' => 'pcs', 'type' => $type,
            'last_purchase_price' => 0, 'hpp' => 0, 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
