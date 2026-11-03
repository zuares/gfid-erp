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
use App\Models\StandaloneAccountingJournal;
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

    public function test_partial_cancel_is_mirrored_to_new_accounting_when_enabled(): void
    {
        config(['accounting.standalone_dual_posting_production_cutting_enabled' => true]);

        [$owner, $job, $bundle] = $this->fixture();
        $this->actingAs($owner);

        app(QcService::class)->cancelCuttingBundleQc(
            bundle: $bundle,
            reason: 'Koreksi bundle pilot',
            actorId: $owner->id,
        );

        $auditId = CuttingQcCancellation::query()
            ->where('cutting_job_bundle_id', $bundle->id)
            ->value('id');

        $this->assertSame(1, StandaloneAccountingJournal::query()
            ->where('source_type', 'cutting_qc_bundle_void')
            ->where('source_id', $auditId)
            ->whereNull('voided_at')
            ->count());
    }

    public function test_adjustment_uses_bundle_cost_and_posts_balanced_mirror(): void
    {
        config(['accounting.standalone_dual_posting_production_cutting_enabled' => true]);

        [$owner, $job, $bundle, $item, $wipCut] = $this->fixture();
        DB::table('cutting_job_bundles')->where('id', $bundle->id)->update(['wip_posted_at' => now()]);
        Warehouse::create([
            'code' => 'WIP-CUT',
            'name' => 'Default WIP Cutting',
            'type' => 'wip',
            'active' => true,
        ]);
        Warehouse::create([
            'code' => 'REJ-CUT',
            'name' => 'Reject Cutting Partial Cancel',
            'type' => 'reject',
            'active' => true,
        ]);
        $this->actingAs($owner);

        app(QcService::class)->adjustCuttingBundleQc(
            bundle: $bundle,
            qcDate: '2026-09-05',
            newOk: 4,
            newReject: 1,
            operatorId: $owner->employee_id,
            qcByUserId: $owner->id,
            notes: 'Reklasifikasi QC',
        );

        $this->assertDatabaseHas('inventory_mutations', [
            'source_type' => 'cutting_qc_adjust_out',
            'source_id' => $job->id,
            'cutting_job_bundle_id' => $bundle->id,
            'unit_cost' => 10,
            'total_cost' => -10,
        ]);
        $this->assertDatabaseHas('inventory_mutations', [
            'source_type' => 'cutting_qc_adjust_in',
            'source_id' => $job->id,
            'cutting_job_bundle_id' => $bundle->id,
            'unit_cost' => 10,
            'total_cost' => 10,
        ]);
        $this->assertSame(1, DB::table('journals')
            ->where('source_type', 'cutting_qc_adjust')
            ->where('source_id', $bundle->id)
            ->count());
        $this->assertSame(1, StandaloneAccountingJournal::query()
            ->where('source_type', 'cutting_qc_adjust')
            ->where('source_id', $bundle->id)
            ->whereNull('voided_at')
            ->count());
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

    public function test_partial_cancelled_bundle_can_change_finished_item_before_reqc(): void
    {
        [$owner, $job, $bundle, $oldItem] = $this->fixture();
        $this->actingAs($owner);

        $service = app(QcService::class);
        $service->cancelCuttingBundleQc(
            bundle: $bundle,
            reason: 'Turun size karena kebutuhan stok',
            actorId: $owner->id,
        );

        $newItem = Item::create([
            'code' => 'K3MST',
            'name' => 'Item K3MST',
            'type' => 'finished_good',
            'item_category_id' => $oldItem->item_category_id,
            'can_make' => true,
            'production_source' => 'in_house',
            'active' => true,
        ]);
        DB::table('item_boms')->insert([
            'item_id' => $newItem->id,
            'name' => 'BOM K3MST',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service->updateCuttingBundleItem(
            bundle: $bundle,
            itemCode: 'k3mst',
            actorId: $owner->id,
        );

        $this->assertDatabaseHas('cutting_job_bundles', [
            'id' => $bundle->id,
            'finished_item_id' => $newItem->id,
            'status' => 'cut',
        ]);
        $this->assertDatabaseHas('production_logs', [
            'event' => 'qc_bundle_item_changed',
            'source_id' => $bundle->id,
        ]);
        $this->assertDatabaseHas('cutting_qc_cancellations', [
            'cutting_job_id' => $job->id,
            'cutting_job_bundle_id' => $bundle->id,
        ]);
    }

    public function test_bundle_correction_returns_material_and_is_idempotent(): void
    {
        config(['accounting.standalone_dual_posting_production_cutting_enabled' => true]);

        [$owner, $job, $bundle, $oldItem] = $this->fixture();
        $this->actingAs($owner);

        $replacement = Item::create([
            'code' => 'FG-PC-REPLACEMENT',
            'name' => 'FG Partial Cancel Replacement',
            'type' => 'finished_good',
            'item_category_id' => $oldItem->item_category_id,
            'can_make' => true,
            'production_source' => 'in_house',
            'active' => true,
        ]);
        DB::table('item_boms')->insert([
            'item_id' => $replacement->id,
            'name' => 'BOM Replacement',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $correction = app(QcService::class)->correctCuttingBundle(
            bundle: $bundle,
            returnFabricQty: 1,
            replacementItemCode: 'fg-pc-replacement',
            reason: 'Salah kode item saat input cutting',
            actorId: $owner->id,
        );

        $this->assertSame('returned', $correction->status);
        $this->assertDatabaseHas('cutting_bundle_corrections', [
            'id' => $correction->id,
            'cutting_job_bundle_id' => $bundle->id,
            'original_item_id' => $oldItem->id,
            'replacement_item_id' => $replacement->id,
            'return_fabric_qty' => 1,
            'return_cost' => 40,
        ]);
        $this->assertDatabaseHas('cutting_job_bundles', [
            'id' => $bundle->id,
            'status' => 'corrected',
            'qty_used_fabric' => 0,
        ]);
        $this->assertDatabaseHas('inventory_mutations', [
            'source_type' => 'cutting_bundle_material_return',
            'source_id' => $correction->id,
            'lot_id' => $bundle->lot_id,
            'qty_change' => 1,
            'total_cost' => 40,
        ]);
        $this->assertEquals(0.0, (float) DB::table('inventory_mutations')
            ->where('cutting_job_bundle_id', $bundle->id)
            ->where(function ($query) use ($job): void {
                $query
                    ->where(function ($original) use ($job): void {
                        $original
                            ->whereIn('source_type', ['cutting_wip', 'cutting_reject', 'cutting_qc_adjust_in', 'cutting_qc_adjust_out'])
                            ->where('source_id', $job->id);
                    })
                    ->orWhere('source_type', 'cutting_qc_bundle_void');
            })
            ->sum('qty_change'));
        $this->assertDatabaseHas('journals', [
            'source_type' => 'cutting_bundle_material_return',
            'source_id' => $correction->id,
            'voided_at' => null,
        ]);
        $this->assertDatabaseHas('standalone_accounting_journals', [
            'source_type' => 'cutting_bundle_material_return',
            'source_id' => $correction->id,
            'voided_at' => null,
        ]);
        $this->assertDatabaseHas('journals', [
            'source_type' => 'cutting_bundle_correction_wage_void',
            'source_id' => $correction->id,
            'voided_at' => null,
        ]);
        $this->assertDatabaseHas('standalone_accounting_journals', [
            'source_type' => 'cutting_bundle_correction_wage_void',
            'source_id' => $correction->id,
            'voided_at' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('sudah pernah dikoreksi');
        app(QcService::class)->correctCuttingBundle(
            bundle: $bundle,
            returnFabricQty: 1,
            replacementItemCode: 'fg-pc-replacement',
            reason: 'Retry tidak boleh menggandakan return',
            actorId: $owner->id,
        );
    }

    public function test_bundle_correction_allows_same_item_code(): void
    {
        [$owner, $job, $bundle, $oldItem] = $this->fixture();
        $this->actingAs($owner);

        $oldItem->update(['can_make' => true, 'active' => true]);
        DB::table('item_boms')->insert([
            'item_id' => $oldItem->id,
            'name' => 'BOM Existing Item',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $correction = app(QcService::class)->correctCuttingBundle(
            bundle: $bundle,
            returnFabricQty: 1,
            replacementItemCode: $oldItem->code,
            reason: 'Ulang cutting dengan kode item yang sama',
            actorId: $owner->id,
        );

        $this->assertSame($oldItem->id, (int) $correction->replacement_item_id);
        $this->assertDatabaseHas('cutting_job_bundles', [
            'id' => $bundle->id,
            'status' => 'corrected',
            'qty_used_fabric' => 0,
        ]);
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
            ['1201', 'Raw Material'],
            ['1202', 'WIP'],
            ['1204', 'Reject'],
            ['2102', 'Payroll'],
            ['6120', 'Production Loss'],
        ] as [$code, $name]) {
            DB::table('accounts')->updateOrInsert(['code' => $code], [
                'name' => $name,
                'type' => $code === '2102' ? 'liability' : ($code === '6120' ? 'expense' : 'asset'),
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

        $wageJournalId = DB::table('journals')->insertGetId([
            'date' => '2026-09-05',
            'description' => 'Cutting test — Upah borongan',
            'source_type' => 'cutting_job_wage',
            'source_id' => $job->id,
            'posted_at' => now(),
            'voided_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_lines')->insert([
            [
                'journal_id' => $wageJournalId,
                'account_id' => DB::table('accounts')->where('code', '1202')->value('id'),
                'debit' => 50,
                'credit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_id' => $wageJournalId,
                'account_id' => DB::table('accounts')->where('code', '2102')->value('id'),
                'debit' => 0,
                'credit' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return [$owner, $job, $bundle, $item, $wipCut, $qc];
    }
}
