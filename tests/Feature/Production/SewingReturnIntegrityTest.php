<?php

namespace Tests\Feature\Production;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SewingReturnIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_sewing_return_forces_wip_fin_when_legacy_destination_flag_is_disabled(): void
    {
        $fixture = $this->fixture(qty: 5, stock: 5);
        config(['accounting.sewing_return_to_wip_fin' => false]);

        $response = $this->actingAs($fixture['user'])
            ->post(route('production.sewing.returns.store'), $this->payload($fixture, [
                ['sewing_pickup_line_id' => $fixture['lineId'], 'qty_ok' => 2, 'qty_reject' => 0],
            ]));

        $response->assertRedirect()->assertSessionDoesntHaveErrors();

        $returnId = (int) DB::table('sewing_returns')->value('id');
        $this->assertSame(
            $fixture['warehouses']['WIP-FIN'],
            (int) DB::table('sewing_returns')->where('id', $returnId)->value('destination_warehouse_id')
        );
        $this->assertSame(2.0, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['warehouses']['WIP-FIN'])
            ->where('item_id', $fixture['itemId'])
            ->value('qty'));
        $this->assertSame(0.0, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['warehouses']['WH-RTS'])
            ->where('item_id', $fixture['itemId'])
            ->value('qty'));
    }

    public function test_duplicate_pickup_line_rows_are_rejected(): void
    {
        $fixture = $this->fixture(qty: 5, stock: 10);

        $response = $this->actingAs($fixture['user'])
            ->post(route('production.sewing.returns.store'), $this->payload($fixture, [
                ['sewing_pickup_line_id' => $fixture['lineId'], 'qty_ok' => 5, 'qty_reject' => 0],
                ['sewing_pickup_line_id' => $fixture['lineId'], 'qty_ok' => 5, 'qty_reject' => 0],
            ]));

        $response->assertRedirect()->assertSessionHasErrors('results');
        $this->assertDatabaseCount('sewing_returns', 0);
        $this->assertSame(0.0, (float) DB::table('sewing_pickup_lines')->where('id', $fixture['lineId'])->value('qty_returned_ok'));
    }

    public function test_operator_mismatch_with_pickup_is_rejected(): void
    {
        $fixture = $this->fixture(qty: 5, stock: 5);

        $response = $this->actingAs($fixture['user'])
            ->post(route('production.sewing.returns.store'), $this->payload($fixture, [
                ['sewing_pickup_line_id' => $fixture['lineId'], 'qty_ok' => 1, 'qty_reject' => 0],
            ], $fixture['otherOperatorId']));

        $response->assertRedirect()->assertSessionHasErrors('operator_id');
        $this->assertDatabaseCount('sewing_returns', 0);
        $this->assertSame(0.0, (float) DB::table('sewing_pickup_lines')->where('id', $fixture['lineId'])->value('qty_returned_ok'));
    }

    public function test_legacy_supply_uses_remaining_coverage_not_remaining_bundle_ratio(): void
    {
        $fixture = $this->fixture(qty: 10, stock: 6, returned: 4);
        $materialId = DB::table('items')->insertGetId([
            'code' => 'MAT-SR-INTEGRITY',
            'name' => 'Material Sewing Return Integrity',
            'unit' => 'pcs',
            'type' => 'material',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sewing_pickup_supply_lines')->insert([
            'sewing_pickup_id' => $fixture['pickupId'],
            'material_item_id' => $materialId,
            'required_qty' => 10,
            'issued_qty' => 5,
            'required_pcs' => 10,
            'issued_pcs' => 5,
            'uom' => 'pcs',
            'stock_available_snapshot' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tooMuch = $this->actingAs($fixture['user'])
            ->post(route('production.sewing.returns.store'), $this->payload($fixture, [
                ['sewing_pickup_line_id' => $fixture['lineId'], 'qty_ok' => 2, 'qty_reject' => 0],
            ]));

        $tooMuch->assertRedirect()->assertSessionHasErrors('results');
        $this->assertSame(4.0, (float) DB::table('sewing_pickup_lines')->where('id', $fixture['lineId'])->value('qty_returned_ok'));

        $allowed = $this->actingAs($fixture['user'])
            ->post(route('production.sewing.returns.store'), $this->payload($fixture, [
                ['sewing_pickup_line_id' => $fixture['lineId'], 'qty_ok' => 1, 'qty_reject' => 0],
            ]));

        $allowed->assertRedirect()->assertSessionDoesntHaveErrors();
        $this->assertSame(5.0, (float) DB::table('sewing_pickup_lines')->where('id', $fixture['lineId'])->value('qty_returned_ok'));
    }

    public function test_void_preserves_decimal_return_quantity(): void
    {
        $fixture = $this->fixture(qty: 5, stock: 1);

        $posted = $this->actingAs($fixture['user'])
            ->post(route('production.sewing.returns.store'), $this->payload($fixture, [
                ['sewing_pickup_line_id' => $fixture['lineId'], 'qty_ok' => 0.5, 'qty_reject' => 0],
            ]));

        $posted->assertRedirect()->assertSessionDoesntHaveErrors();
        $returnId = (int) DB::table('sewing_returns')->value('id');
        $this->assertSame(0.5, (float) DB::table('sewing_return_lines')->where('sewing_return_id', $returnId)->value('qty_ok'));
        $this->assertSame(0.5, (float) DB::table('sewing_pickup_lines')->where('id', $fixture['lineId'])->value('qty_returned_ok'));

        $voided = $this->actingAs($fixture['user'])
            ->post(route('production.sewing.returns.void', $returnId), ['reason' => 'Integrity test']);

        $voided->assertRedirect()->assertSessionDoesntHaveErrors();
        $this->assertSame(0.0, (float) DB::table('sewing_pickup_lines')->where('id', $fixture['lineId'])->value('qty_returned_ok'));
        $this->assertSame(0.0, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['warehouses']['WIP-FIN'])
            ->where('item_id', $fixture['itemId'])
            ->value('qty'));
        $this->assertSame(1.0, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['warehouses']['WIP-SEW'])
            ->where('item_id', $fixture['itemId'])
            ->value('qty'));
    }

    public function test_void_pending_qc_does_not_reverse_stock_that_never_moved(): void
    {
        $fixture = $this->fixture(qty: 5, stock: 1, requiresQc: true);

        $posted = $this->actingAs($fixture['user'])
            ->post(route('production.sewing.returns.store'), $this->payload($fixture, [
                ['sewing_pickup_line_id' => $fixture['lineId'], 'qty_ok' => 0.5, 'qty_reject' => 0],
            ]));

        $posted->assertRedirect()->assertSessionDoesntHaveErrors();
        $returnId = (int) DB::table('sewing_returns')->value('id');
        $this->assertSame('pending_qc', DB::table('sewing_returns')->where('id', $returnId)->value('status'));
        $this->assertSame(1.0, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['warehouses']['WIP-SEW'])
            ->where('item_id', $fixture['itemId'])
            ->value('qty'));
        $this->assertSame(0.0, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['warehouses']['WIP-FIN'])
            ->where('item_id', $fixture['itemId'])
            ->value('qty'));

        $voided = $this->actingAs($fixture['user'])
            ->post(route('production.sewing.returns.void', $returnId), ['reason' => 'Pending QC integrity test']);

        $voided->assertRedirect()->assertSessionDoesntHaveErrors();
        $this->assertSame('void', DB::table('sewing_returns')->where('id', $returnId)->value('status'));
        $this->assertSame('void', DB::table('qc_results')
            ->where('sewing_job_id', $returnId)
            ->where('stage', 'sewing')
            ->value('status'));
        $this->assertSame(1.0, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['warehouses']['WIP-SEW'])
            ->where('item_id', $fixture['itemId'])
            ->value('qty'));
    }

    private function payload(array $fixture, array $results, ?int $operatorId = null): array
    {
        return [
            'date' => '2026-11-05',
            'operator_id' => $operatorId ?: $fixture['operatorId'],
            'destination_warehouse_id' => $fixture['warehouses']['WIP-FIN'],
            'results' => $results,
        ];
    }

    private function fixture(float $qty, float $stock, float $returned = 0, bool $requiresQc = false): array
    {
        config([
            'accounting.sewing_return_to_wip_fin' => true,
            'accounting.sewing_return_requires_qc' => $requiresQc,
            'accounting.standalone_dual_posting_production_sewing_return_enabled' => false,
            'accounting.sewing_return_allow_developer_supply_bypass' => false,
        ]);

        $user = User::factory()->create([
            'employee_code' => 'USER-SR-INTEGRITY',
            'role' => 'owner',
        ]);
        $warehouses = [];
        foreach (['WIP-CUT', 'WIP-SEW', 'WIP-FIN', 'REJ-SEW', 'WH-RTS'] as $code) {
            $warehouses[$code] = DB::table('warehouses')->insertGetId([
                'code' => $code,
                'name' => $code,
                'type' => 'internal',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $itemId = DB::table('items')->insertGetId([
            'code' => 'ITEM-SR-INTEGRITY',
            'name' => 'Sewing Return Integrity Item',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lotId = DB::table('lots')->insertGetId([
            'code' => 'LOT-SR-INTEGRITY',
            'item_id' => $itemId,
            'initial_qty' => 10,
            'initial_cost' => 1000,
            'qty_onhand' => 10,
            'total_cost' => 1000,
            'avg_cost' => 100,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $operatorId = DB::table('employees')->insertGetId([
            'code' => 'EMP-SR-INTEGRITY-1',
            'name' => 'Operator One',
            'role' => 'sewing',
            'payment_type' => 'variable',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherOperatorId = DB::table('employees')->insertGetId([
            'code' => 'EMP-SR-INTEGRITY-2',
            'name' => 'Operator Two',
            'role' => 'sewing',
            'payment_type' => 'variable',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $jobId = DB::table('cutting_jobs')->insertGetId([
            'code' => 'CUT-SR-INTEGRITY',
            'date' => '2026-11-05',
            'warehouse_id' => $warehouses['WIP-CUT'],
            'lot_id' => $lotId,
            'fabric_item_id' => $itemId,
            'operator_id' => $operatorId,
            'total_bundles' => 1,
            'total_qty_pcs' => 10,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $bundleId = DB::table('cutting_job_bundles')->insertGetId([
            'cutting_job_id' => $jobId,
            'bundle_code' => 'BND-SR-INTEGRITY',
            'bundle_no' => 1,
            'lot_id' => $lotId,
            'finished_item_id' => $itemId,
            'qty_pcs' => 10,
            'qty_used_fabric' => 1,
            'operator_id' => $operatorId,
            'status' => 'qc_ok',
            'qty_qc_ok' => 10,
            'qty_qc_reject' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $pickupId = DB::table('sewing_pickups')->insertGetId([
            'code' => 'SWP-SR-INTEGRITY',
            'date' => '2026-11-05',
            'warehouse_id' => $warehouses['WIP-SEW'],
            'operator_id' => $operatorId,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lineId = DB::table('sewing_pickup_lines')->insertGetId([
            'sewing_pickup_id' => $pickupId,
            'cutting_job_bundle_id' => $bundleId,
            'finished_item_id' => $itemId,
            'qty_bundle' => $qty,
            'qty_returned_ok' => $returned,
            'qty_returned_reject' => 0,
            'status' => 'in_progress',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_stocks')->insert([
            'warehouse_id' => $warehouses['WIP-SEW'],
            'item_id' => $itemId,
            'qty' => $stock,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_mutations')->insert([
            'date' => '2026-11-05',
            'warehouse_id' => $warehouses['WIP-SEW'],
            'item_id' => $itemId,
            'qty_change' => $stock,
            'direction' => 'in',
            'source_type' => 'integrity_seed',
            'source_id' => 1,
            'unit_cost' => 100,
            'total_cost' => $stock * 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return compact('user', 'warehouses', 'itemId', 'operatorId', 'otherOperatorId', 'pickupId', 'lineId');
    }
}
