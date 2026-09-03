<?php

namespace Tests\Feature\Production;

use App\Services\Production\ProductionFlowAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductionFlowAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_passes_for_a_consistent_cutting_to_sewing_reject_flow(): void
    {
        $fixture = $this->flowFixture();

        $report = app(ProductionFlowAuditService::class)->audit($fixture['bundle_id']);

        $this->assertSame('PASS', $report['summary']['status']);
        $this->assertSame(0, $report['summary']['issues']);
        $this->assertSame([], $report['unassigned_movements']);
    }

    public function test_audit_detects_sewing_reject_ledger_qty_mismatch(): void
    {
        $fixture = $this->flowFixture();

        DB::table('inventory_mutations')
            ->where('cutting_job_bundle_id', $fixture['bundle_id'])
            ->where('source_type', 'sewing_qc_reject')
            ->update(['qty_change' => 1]);
        $report = app(ProductionFlowAuditService::class)->audit($fixture['bundle_id']);
        $codes = collect($report['issues'])->pluck('code')->all();

        $this->assertSame('REVIEW', $report['summary']['status']);
        $this->assertContains('SEWING_QC_REJECT_QTY_MISMATCH', $codes);
        $this->assertContains('HPP_FORMULA_MISMATCH', $codes);
    }

    private function flowFixture(): array
    {
        $warehouses = [];
        foreach (['WIP-CUT', 'REJ-CUT', 'WIP-SEW', 'WH-PRD', 'REJ-SEW'] as $code) {
            $warehouses[$code] = (int) DB::table('warehouses')->insertGetId([
                'code' => $code,
                'name' => $code,
                'type' => 'internal',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $itemId = (int) DB::table('items')->insertGetId([
            'code' => 'TTC-BLK-L',
            'name' => 'Test item',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_role' => 'finished_good',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'last_purchase_price' => 0,
            'hpp' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lotId = (int) DB::table('lots')->insertGetId([
            'code' => 'LOT-AUDIT-001',
            'item_id' => $itemId,
            'initial_qty' => 100,
            'initial_cost' => 10000,
            'qty_onhand' => 100,
            'total_cost' => 10000,
            'avg_cost' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $employeeId = (int) DB::table('employees')->insertGetId([
            'code' => 'EMP-AUDIT-001',
            'name' => 'Audit Operator',
            'role' => 'sewing',
            'payment_type' => 'variable',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $jobId = (int) DB::table('cutting_jobs')->insertGetId([
            'code' => 'CUT-AUDIT-001',
            'date' => '2026-09-01',
            'warehouse_id' => $warehouses['WIP-CUT'],
            'lot_id' => $lotId,
            'operator_id' => $employeeId,
            'total_bundles' => 1,
            'total_qty_pcs' => 10,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $bundleId = (int) DB::table('cutting_job_bundles')->insertGetId([
            'cutting_job_id' => $jobId,
            'bundle_code' => 'BND-AUDIT-001',
            'bundle_no' => 1,
            'lot_id' => $lotId,
            'finished_item_id' => $itemId,
            'qty_pcs' => 10,
            'qty_used_fabric' => 1,
            'operator_id' => $employeeId,
            'status' => 'qc_ok',
            'qty_qc_ok' => 10,
            'qty_qc_reject' => 0,
            'wip_warehouse_id' => $warehouses['WIP-CUT'],
            'wip_qty' => 10,
            'cut_wip_warehouse_id' => $warehouses['WIP-CUT'],
            'cut_wip_qty' => 10,
            'sewing_picked_qty' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $pickupId = (int) DB::table('sewing_pickups')->insertGetId([
            'code' => 'SWP-AUDIT-001',
            'date' => '2026-09-01',
            'warehouse_id' => $warehouses['WIP-SEW'],
            'operator_id' => $employeeId,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $pickupLineId = (int) DB::table('sewing_pickup_lines')->insertGetId([
            'sewing_pickup_id' => $pickupId,
            'cutting_job_bundle_id' => $bundleId,
            'finished_item_id' => $itemId,
            'qty_bundle' => 10,
            'qty_returned_ok' => 8,
            'qty_returned_reject' => 2,
            'status' => 'done',
            'unit_cost' => 105,
            'wage_per_pcs' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $returnId = (int) DB::table('sewing_returns')->insertGetId([
            'code' => 'SRT-AUDIT-001',
            'date' => '2026-09-02',
            'warehouse_id' => $warehouses['WIP-SEW'],
            'operator_id' => $employeeId,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sewing_return_lines')->insert([
            'sewing_return_id' => $returnId,
            'sewing_pickup_line_id' => $pickupLineId,
            'qty_ok' => 8,
            'qty_reject' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('qc_results')->insert([
            'stage' => 'sewing',
            'cutting_job_bundle_id' => $bundleId,
            'sewing_job_id' => $returnId,
            'qc_date' => '2026-09-02',
            'qty_ok' => 8,
            'qty_reject' => 2,
            'status' => 'mixed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $movements = [
            [$warehouses['WIP-CUT'], 10, 'in', 'cutting_wip', $jobId, 100],
            [$warehouses['WIP-CUT'], -10, 'out', 'App\\Models\\SewingPickup', $pickupId, 105],
            [$warehouses['WIP-SEW'], 10, 'in', 'App\\Models\\SewingPickup', $pickupId, 105],
            [$warehouses['WIP-SEW'], -10, 'out', 'sewing_qc_out', $returnId, 105],
            [$warehouses['WH-PRD'], 8, 'in', 'sewing_qc_in', $returnId, 105],
            [$warehouses['REJ-SEW'], 2, 'in', 'sewing_qc_reject', $returnId, 105],
        ];
        foreach ($movements as [$warehouseId, $qty, $direction, $sourceType, $sourceId, $unitCost]) {
            DB::table('inventory_mutations')->insert([
                'date' => '2026-09-02',
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'qty_change' => $qty,
                'direction' => $direction,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'cutting_job_bundle_id' => $bundleId,
                'unit_cost' => $unitCost,
                'total_cost' => $qty * $unitCost,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return ['bundle_id' => $bundleId];
    }
}
