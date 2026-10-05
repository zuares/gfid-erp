<?php

namespace Tests\Feature\Production;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\CuttingQcCancellation;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Lot;
use App\Models\QcResult;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Production\QcService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QcCuttingPartialCancelTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_cancel_reverses_only_the_unpicked_bundle_and_writes_audit(): void
    {
        [$owner, $job, $bundle, $item, $wipCut] = $this->fixture();
        $this->actingAs($owner);

        app(QcService::class)->cancelCuttingBundleQc(
            bundle: $bundle,
            reason: 'Turun size karena kebutuhan stok',
            actorId: $owner->id,
        );

        $this->assertDatabaseMissing('qc_results', [
            'cutting_job_bundle_id' => $bundle->id,
            'stage' => QcResult::STAGE_CUTTING,
        ]);
        $this->assertDatabaseHas('cutting_job_bundles', [
            'id' => $bundle->id,
            'status' => 'cut',
            'qty_qc_ok' => 0,
            'cut_wip_qty' => 0,
        ]);
        $this->assertDatabaseHas('inventory_stocks', [
            'warehouse_id' => $wipCut->id,
            'item_id' => $item->id,
            'qty' => 0,
        ]);
        $this->assertDatabaseHas('cutting_qc_cancellations', [
            'cutting_job_id' => $job->id,
            'cutting_job_bundle_id' => $bundle->id,
            'reason' => 'Turun size karena kebutuhan stok',
        ]);
        $this->assertDatabaseHas('production_logs', [
            'event' => 'qc_bundle_cancelled',
            'source_id' => CuttingQcCancellation::query()
                ->where('cutting_job_bundle_id', $bundle->id)
                ->value('id'),
        ]);
        $this->assertDatabaseHas('inventory_mutations', [
            'source_type' => 'cutting_qc_bundle_void',
            'source_id' => CuttingQcCancellation::query()
                ->where('cutting_job_bundle_id', $bundle->id)
                ->value('id'),
            'cutting_job_bundle_id' => $bundle->id,
            'qty_change' => -5,
        ]);
    }

    public function test_partial_cancel_is_rejected_when_bundle_has_active_sewing_pickup(): void
    {
        [$owner, $job, $bundle] = $this->fixture();
        $wipSew = Warehouse::create([
            'code' => 'WIP-SEW-PC',
            'name' => 'WIP Sewing Partial Cancel',
            'type' => 'internal',
            'active' => true,
        ]);
        $pickupId = DB::table('sewing_pickups')->insertGetId([
            'code' => 'SWP-PC-001',
            'date' => '2026-09-05',
            'warehouse_id' => $wipSew->id,
            'operator_id' => $bundle->operator_id,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sewing_pickup_lines')->insert([
            'sewing_pickup_id' => $pickupId,
            'cutting_job_bundle_id' => $bundle->id,
            'finished_item_id' => $bundle->finished_item_id,
            'qty_bundle' => 1,
            'status' => 'in_progress',
            'unit_cost' => 10,
            'wage_per_pcs' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('sudah ada qty yang diambil jahit');

        $this->actingAs($owner);
        app(QcService::class)->cancelCuttingBundleQc(
            bundle: $bundle,
            reason: 'Tidak boleh dibatalkan',
            actorId: $owner->id,
        );
    }

    private function fixture(): array
    {
        $ownerEmployee = Employee::create([
            'code' => 'OWN-PC-'.uniqid(),
            'name' => 'Owner Partial Cancel',
            'role' => 'owner',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        $operator = Employee::create([
            'code' => 'CUT-PC-'.uniqid(),
            'name' => 'Cutting Partial Cancel',
            'role' => 'cutting',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_id' => $ownerEmployee->id,
            'employee_code' => $ownerEmployee->code,
        ]);

        foreach ([
            ['1202', 'WIP'],
            ['1204', 'Reject'],
            ['2102', 'Payroll'],
        ] as [$code, $name]) {
            DB::table('accounts')->updateOrInsert(['code' => $code], [
                'name' => $name,
                'type' => $code === '2102' ? 'liability' : 'asset',
                'is_cash' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $wipCut = Warehouse::create([
            'code' => 'WIP-CUT-PC',
            'name' => 'WIP Cutting Partial Cancel',
            'type' => 'internal',
            'active' => true,
        ]);
        $category = ItemCategory::create([
            'code' => 'PC-'.uniqid(),
            'name' => 'Partial Cancel',
        ]);
        $fabric = Item::create([
            'code' => 'FAB-PC-'.uniqid(),
            'name' => 'Fabric Partial Cancel',
            'type' => 'material',
            'item_category_id' => $category->id,
        ]);
        $item = Item::create([
            'code' => 'FG-PC-'.uniqid(),
            'name' => 'FG Partial Cancel',
            'type' => 'finished_good',
            'item_category_id' => $category->id,
        ]);
        $lot = Lot::create([
            'code' => 'LOT-PC-'.uniqid(),
            'item_id' => $fabric->id,
            'initial_qty' => 10,
            'initial_cost' => 40,
            'qty_onhand' => 10,
            'total_cost' => 40,
            'avg_cost' => 4,
            'status' => 'open',
        ]);
        $job = CuttingJob::create([
            'code' => 'CUT-PC-'.uniqid(),
            'date' => '2026-09-05',
            'warehouse_id' => $wipCut->id,
            'lot_id' => $lot->id,
            'fabric_item_id' => $fabric->id,
            'operator_id' => $operator->id,
            'total_bundles' => 1,
            'total_qty_pcs' => 5,
            'status' => 'qc_done',
        ]);
        $bundle = CuttingJobBundle::create([
            'cutting_job_id' => $job->id,
            'bundle_code' => 'BND-PC-'.uniqid(),
            'bundle_no' => 1,
            'lot_id' => $lot->id,
            'finished_item_id' => $item->id,
            'item_category_id' => $category->id,
            'qty_pcs' => 5,
            'qty_used_fabric' => 1,
            'operator_id' => $operator->id,
            'status' => 'qc_ok',
            'qty_qc_ok' => 5,
            'qty_qc_reject' => 0,
            'wip_warehouse_id' => $wipCut->id,
            'wip_qty' => 5,
            'cut_wip_warehouse_id' => $wipCut->id,
            'cut_wip_qty' => 5,
            'sewing_picked_qty' => 0,
            'wip_posted_at' => now(),
        ]);
        $qc = QcResult::create([
            'stage' => QcResult::STAGE_CUTTING,
            'cutting_job_id' => $job->id,
            'cutting_job_bundle_id' => $bundle->id,
            'qc_date' => '2026-09-05',
            'qty_ok' => 5,
            'qty_reject' => 0,
            'status' => 'qc_ok',
        ]);

        DB::table('inventory_stocks')->insert([
            'warehouse_id' => $wipCut->id,
            'item_id' => $item->id,
            'qty' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_mutations')->insert([
            'date' => '2026-09-05',
            'warehouse_id' => $wipCut->id,
            'item_id' => $item->id,
            'qty_change' => 5,
            'direction' => 'in',
            'source_type' => 'cutting_wip',
            'source_id' => $job->id,
            'cutting_job_bundle_id' => $bundle->id,
            'unit_cost' => 10,
            'total_cost' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_mutations')->insert([
            'date' => '2026-09-05',
            'warehouse_id' => $wipCut->id,
            'item_id' => $fabric->id,
            'qty_change' => -1,
            'direction' => 'out',
            'source_type' => 'cutting_job',
            'source_id' => $job->id,
            'cutting_job_bundle_id' => $bundle->id,
            'lot_id' => $lot->id,
            'unit_cost' => 40,
            'total_cost' => -40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$owner, $job, $bundle, $item, $wipCut, $qc];
    }
}
