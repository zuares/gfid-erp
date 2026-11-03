<?php

namespace Tests\Feature\Accounting;

use App\Models\PieceRate;
use App\Models\StandaloneAccountingJournal;
use App\Models\User;
use App\Services\Accounting\StandaloneProductionPostingService;
use App\Services\Production\SewingPickupRejectSupplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SewingPickupRejectAccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_reject_post_is_idempotent_and_void_creates_audit_reversal(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_pickup_enabled' => true]);
        $lineId = $this->lineFixture();
        $service = app(StandaloneProductionPostingService::class);

        $first = $service->postSewingPickupReject($lineId, 200, '2026-10-29', 'jahitan rusak');
        $second = $service->postSewingPickupReject($lineId, 200, '2026-10-29', 'retry');

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second?->id);
        $this->assertSame(1, StandaloneAccountingJournal::query()
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_REJECT)
            ->where('source_id', $lineId)
            ->whereNull('voided_at')
            ->count());

        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $first->id,
            'account_id' => DB::table('standalone_accounting_accounts')->where('code', '1-153')->value('id'),
            'debit' => 200,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $first->id,
            'account_id' => DB::table('standalone_accounting_accounts')->where('code', '1-151-01')->value('id'),
            'debit' => 0,
            'credit' => 200,
        ]);

        $service->voidSewingPickupReject($lineId);
        $service->voidSewingPickupReject($lineId);

        $this->assertSame(0, StandaloneAccountingJournal::query()
            ->where('source_id', $lineId)
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_REJECT)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(1, StandaloneAccountingJournal::query()
            ->where('source_id', $lineId)
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_REJECT.'_reversal_'.$first->id)
            ->whereNotNull('voided_at')
            ->count());
    }

    public function test_reject_post_blocks_when_source_mapping_is_missing(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_pickup_enabled' => true]);
        $lineId = $this->lineFixture();

        DB::table('standalone_production_account_mappings')
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_REJECT)
            ->where('line_side', 'credit')
            ->update(['is_active' => false]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('sewing_pickup_pre_sewing_reject');

        app(StandaloneProductionPostingService::class)
            ->postSewingPickupReject($lineId, 200, '2026-10-29', 'mapping test');
    }

    public function test_controller_posts_reject_only_to_rej_cut_without_wip_sew_mutation(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_pickup_enabled' => true]);
        $fixture = $this->controllerFixture();

        $response = $this->actingAs($fixture['user'])->post(route('production.sewing.pickups.store'), [
            'date' => '2026-10-29',
            'warehouse_id' => $fixture['wip_sew_id'],
            'lines' => [[
                'bundle_id' => $fixture['bundle_id'],
                'qty_bundle' => 0,
                'qty_reject' => 2,
                'reject_reason' => 'Reject susulan test',
            ]],
        ]);

        $response
            ->assertRedirect(route('production.sewing.pickups.create'))
            ->assertSessionDoesntHaveErrors();
        $pickupId = (int) DB::table('sewing_pickups')->latest('id')->value('id');
        $lineId = (int) DB::table('sewing_pickup_lines')->where('sewing_pickup_id', $pickupId)->value('id');

        $this->assertDatabaseHas('sewing_pickup_lines', [
            'id' => $lineId,
            'qty_bundle' => 0,
            'qty_rejected' => 2,
            'reject_reason' => 'Reject susulan test',
        ]);
        $this->assertDatabaseHas('sewing_pickups', [
            'id' => $pickupId,
            'operator_id' => null,
        ]);
        $this->assertSame(2.0, (float) DB::table('inventory_mutations')
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_REJECT)
            ->where('source_id', $pickupId)
            ->where('warehouse_id', $fixture['rej_cut_id'])
            ->sum('qty_change'));
        $this->assertSame(-2.0, (float) DB::table('inventory_mutations')
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_REJECT)
            ->where('source_id', $pickupId)
            ->where('warehouse_id', $fixture['wip_cut_id'])
            ->sum('qty_change'));
        $this->assertSame(0, DB::table('inventory_mutations')
            ->where('source_type', 'App\\Models\\SewingPickup')
            ->where('source_id', $pickupId)
            ->where('warehouse_id', $fixture['wip_sew_id'])
            ->count());

        $journal = StandaloneAccountingJournal::query()
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_REJECT)
            ->where('source_id', $lineId)
            ->first();
        $this->assertNotNull($journal);
        $this->assertSame(200.0, (float) $journal->lines->sum('debit'));
        $this->assertSame(200.0, (float) $journal->lines->sum('credit'));
    }

    public function test_reject_only_void_returns_stock_from_rej_cut_to_wip_cut(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_pickup_enabled' => true]);
        $fixture = $this->controllerFixture();

        $this->actingAs($fixture['user'])->post(route('production.sewing.pickups.store'), [
            'date' => '2026-10-29',
            'warehouse_id' => $fixture['wip_sew_id'],
            'lines' => [[
                'bundle_id' => $fixture['bundle_id'],
                'qty_bundle' => 0,
                'qty_reject' => 2,
                'reject_reason' => 'Reject sebelum jahit',
            ]],
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $pickupId = (int) DB::table('sewing_pickups')->latest('id')->value('id');

        $this->actingAs($fixture['user'])->post(route('production.sewing.pickups.void', $pickupId), [
            'reason' => 'Koreksi lokasi reject',
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertSame(0.0, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['rej_cut_id'])
            ->where('item_id', DB::table('items')->where('code', 'ITEM-SEW-REJECT-CONTROLLER')->value('id'))
            ->value('qty'));
        $this->assertSame(2.0, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['wip_cut_id'])
            ->where('item_id', DB::table('items')->where('code', 'ITEM-SEW-REJECT-CONTROLLER')->value('id'))
            ->value('qty'));
    }

    public function test_reject_returns_only_sewing_supply_to_rm_and_void_reverses_it(): void
    {
        config([
            'accounting.standalone_dual_posting_production_sewing_pickup_enabled' => true,
            'accounting.standalone_production_cutting_qc_auto_bom_enabled' => true,
        ]);

        $fixture = $this->controllerFixture();
        $supplyItemId = (int) DB::table('items')->insertGetId([
            'code' => 'RIB-SEW-REJECT-TEST',
            'name' => 'Rib Sewing Supply Reject Test',
            'unit' => 'kg',
            'type' => 'raw_material',
            'item_role' => 'production_supply',
            'last_purchase_price' => 50,
            'hpp' => 50,
            'active' => 1,
            'allow_negative' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $bomId = (int) DB::table('item_boms')->insertGetId([
            'item_id' => DB::table('cutting_job_bundles')->where('id', $fixture['bundle_id'])->value('finished_item_id'),
            'name' => 'BOM reject supply test',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('item_bom_lines')->insert([
            'item_bom_id' => $bomId,
            'material_item_id' => $supplyItemId,
            'usage_stage' => 'sewing_supply',
            'qty' => 0.5,
            'uom' => 'kg',
            'scrap_pct' => 0,
            'is_optional' => 0,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lotId = (int) DB::table('lots')->insertGetId([
            'code' => 'LOT-RIB-SEW-REJECT-TEST',
            'item_id' => $supplyItemId,
            'initial_qty' => 10,
            'initial_cost' => 50,
            'qty_onhand' => 9,
            'total_cost' => 450,
            'avg_cost' => 50,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_stocks')->insert([
            'warehouse_id' => DB::table('warehouses')->where('code', 'RM')->value('id'),
            'item_id' => $supplyItemId,
            'qty' => 9,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $jobId = (int) DB::table('cutting_job_bundles')->where('id', $fixture['bundle_id'])->value('cutting_job_id');
        DB::table('inventory_mutations')->insert([
            'date' => '2026-10-29',
            'warehouse_id' => DB::table('warehouses')->where('code', 'RM')->value('id'),
            'item_id' => $supplyItemId,
            'qty_change' => -1,
            'direction' => 'out',
            'source_type' => 'cutting_qc_sewing_supply',
            'source_id' => $jobId,
            'lot_id' => $lotId,
            'unit_cost' => 50,
            'total_cost' => -50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pickupId = (int) DB::table('sewing_pickups')->insertGetId([
            'code' => 'SWP-REJECT-SUPPLY-TEST',
            'date' => '2026-10-29',
            'warehouse_id' => $fixture['wip_sew_id'],
            'operator_id' => null,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lineId = (int) DB::table('sewing_pickup_lines')->insertGetId([
            'sewing_pickup_id' => $pickupId,
            'cutting_job_bundle_id' => $fixture['bundle_id'],
            'finished_item_id' => DB::table('cutting_job_bundles')->where('id', $fixture['bundle_id'])->value('finished_item_id'),
            'qty_bundle' => 0,
            'qty_rejected' => 2,
            'reject_unit_cost' => 100,
            'reject_reason' => 'Supply return test',
            'status' => 'in_progress',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $line = \App\Models\SewingPickupLine::with('bundle')->findOrFail($lineId);
        $supplyReturn = app(SewingPickupRejectSupplyService::class)->returnForReject(
            $line,
            '2026-10-29',
            'fixture return supply',
        );
        $this->assertEqualsWithDelta(50, $supplyReturn, 0.01);

        $posting = app(StandaloneProductionPostingService::class);
        $first = $posting->postSewingPickupRejectSupply($lineId, '2026-10-29', 'Supply return test');
        $second = $posting->postSewingPickupRejectSupply($lineId, '2026-10-29', 'retry');

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second?->id);
        $this->assertEqualsWithDelta(50, (float) $first->lines->sum('debit'), 0.01);
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $first->id,
            'account_id' => DB::table('standalone_accounting_accounts')->where('code', '1-150')->value('id'),
            'debit' => 50,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $first->id,
            'account_id' => DB::table('standalone_accounting_accounts')->where('code', '1-153')->value('id'),
            'debit' => 0,
            'credit' => 50,
        ]);

        $this->assertSame(0, DB::table('inventory_mutations')
            ->where('source_type', SewingPickupRejectSupplyService::SOURCE)
            ->where('source_id', $lineId)
            ->where('item_id', DB::table('items')->where('code', 'ITEM-SEW-REJECT-CONTROLLER')->value('id'))
            ->count());

        // Jika QC Cutting dibatalkan sebelum reject line di-VOID, reversal QC
        // tidak boleh memulihkan BOM yang sudah dikembalikan oleh reject.
        app(\App\Services\Inventory\InventoryService::class)->reverseBySource(
            originalSourceTypes: ['cutting_qc_sewing_supply'],
            originalSourceId: $jobId,
            voidSourceType: 'cutting_qc_sewing_supply_void',
            voidSourceId: $jobId,
            notesPrefix: 'QC cancel netting test',
            date: '2026-10-29',
        );
        $this->assertEqualsWithDelta(10, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', DB::table('warehouses')->where('code', 'RM')->value('id'))
            ->where('item_id', $supplyItemId)
            ->value('qty'), 0.0001);

        app(SewingPickupRejectSupplyService::class)->reverseForReject($line, '2026-10-29', 'void supply return');
        $posting->voidSewingPickupRejectSupply($lineId);
        $posting->voidSewingPickupRejectSupply($lineId);

        $this->assertSame(0.0, (float) DB::table('inventory_mutations')
            ->whereIn('source_type', [SewingPickupRejectSupplyService::SOURCE, SewingPickupRejectSupplyService::VOID_SOURCE])
            ->where('source_id', $lineId)
            ->sum('qty_change'));
        $this->assertSame(0, StandaloneAccountingJournal::query()
            ->where('source_id', $lineId)
            ->where('source_type', SewingPickupRejectSupplyService::SOURCE)
            ->whereNull('voided_at')
            ->count());
    }

    private function lineFixture(): int
    {
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'WIP-SEW-REJECT-TEST', 'name' => 'WIP Sewing Reject Test', 'type' => 'wip', 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $employeeId = DB::table('employees')->insertGetId([
            'code' => 'EMP-SEW-REJECT-TEST', 'name' => 'Sewing Reject Test', 'role' => 'sewing', 'payment_type' => 'variable',
            'weekly_fixed_salary' => 0, 'default_piece_rate' => 0, 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $itemId = DB::table('items')->insertGetId([
            'code' => 'ITEM-SEW-REJECT-TEST', 'name' => 'Sewing Reject Test Item', 'unit' => 'pcs', 'type' => 'finished',
            'last_purchase_price' => 0, 'hpp' => 0, 'active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $lotId = DB::table('lots')->insertGetId([
            'code' => 'LOT-SEW-REJECT-TEST', 'item_id' => $itemId, 'initial_qty' => 1, 'initial_cost' => 0,
            'qty_onhand' => 1, 'total_cost' => 0, 'avg_cost' => 0, 'status' => 'open',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $jobId = DB::table('cutting_jobs')->insertGetId([
            'code' => 'CUT-SEW-REJECT-TEST', 'date' => '2026-10-29', 'warehouse_id' => $warehouseId,
            'lot_id' => $lotId, 'fabric_item_id' => $itemId, 'operator_id' => $employeeId,
            'total_bundles' => 1, 'total_qty_pcs' => 10, 'status' => 'cut',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $bundleId = DB::table('cutting_job_bundles')->insertGetId([
            'cutting_job_id' => $jobId, 'bundle_code' => 'BND-SEW-REJECT-TEST', 'bundle_no' => 1, 'lot_id' => $lotId,
            'finished_item_id' => $itemId, 'qty_pcs' => 10, 'qty_used_fabric' => 1, 'operator_id' => $employeeId,
            'status' => 'cut', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $pickupId = DB::table('sewing_pickups')->insertGetId([
            'code' => 'SWP-SEW-REJECT-TEST', 'date' => '2026-10-29', 'warehouse_id' => $warehouseId,
            'operator_id' => $employeeId, 'status' => 'posted', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return (int) DB::table('sewing_pickup_lines')->insertGetId([
            'sewing_pickup_id' => $pickupId, 'cutting_job_bundle_id' => $bundleId, 'finished_item_id' => $itemId,
            'qty_bundle' => 0, 'qty_rejected' => 2, 'reject_unit_cost' => 100, 'reject_reason' => 'test',
            'qty_returned_ok' => 0, 'qty_returned_reject' => 0, 'wage_per_pcs' => 0,
            'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @return array<string, int|User> */
    private function controllerFixture(): array
    {
        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'USR-SEW-REJECT-CONTROLLER',
        ]);
        $warehouse = [];
        foreach (['WIP-CUT', 'WIP-SEW', 'REJ-CUT', 'REJ-SEW', 'RM'] as $code) {
            $warehouse[$code] = (int) DB::table('warehouses')->insertGetId([
                'code' => $code,
                'name' => $code,
                'type' => 'internal',
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $employeeId = (int) DB::table('employees')->insertGetId([
            'code' => 'EMP-SEW-REJECT-CONTROLLER',
            'name' => 'Reject Controller Operator',
            'role' => 'sewing',
            'payment_type' => 'variable',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $itemId = (int) DB::table('items')->insertGetId([
            'code' => 'ITEM-SEW-REJECT-CONTROLLER',
            'name' => 'Reject Controller Item',
            'unit' => 'pcs',
            'type' => 'finished',
            'last_purchase_price' => 0,
            'hpp' => 0,
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        PieceRate::create([
            'module' => 'sewing',
            'employee_id' => $employeeId,
            'item_id' => $itemId,
            'rate_per_pcs' => 5000,
            'effective_from' => '2026-01-01',
        ]);
        $lotId = (int) DB::table('lots')->insertGetId([
            'code' => 'LOT-SEW-REJECT-CONTROLLER',
            'item_id' => $itemId,
            'initial_qty' => 2,
            'initial_cost' => 200,
            'qty_onhand' => 2,
            'total_cost' => 200,
            'avg_cost' => 100,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $jobId = (int) DB::table('cutting_jobs')->insertGetId([
            'code' => 'CUT-SEW-REJECT-CONTROLLER',
            'date' => '2026-10-29',
            'warehouse_id' => $warehouse['WIP-CUT'],
            'lot_id' => $lotId,
            'fabric_item_id' => $itemId,
            'operator_id' => $employeeId,
            'total_bundles' => 1,
            'total_qty_pcs' => 2,
            'status' => 'cut',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $bundleId = (int) DB::table('cutting_job_bundles')->insertGetId([
            'cutting_job_id' => $jobId,
            'bundle_code' => 'BND-SEW-REJECT-CONTROLLER',
            'bundle_no' => 1,
            'lot_id' => $lotId,
            'finished_item_id' => $itemId,
            'qty_pcs' => 2,
            'qty_used_fabric' => 1,
            'operator_id' => $employeeId,
            'status' => 'qc_ok',
            'qty_qc_ok' => 2,
            'qty_qc_reject' => 0,
            'wip_warehouse_id' => $warehouse['WIP-CUT'],
            'wip_qty' => 2,
            'cut_wip_warehouse_id' => $warehouse['WIP-CUT'],
            'cut_wip_qty' => 2,
            'sewing_picked_qty' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('qc_results')->insert([
            'stage' => 'cutting',
            'cutting_job_bundle_id' => $bundleId,
            'qc_date' => '2026-10-29',
            'qty_ok' => 2,
            'qty_reject' => 0,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_stocks')->insert([
            'warehouse_id' => $warehouse['WIP-CUT'],
            'item_id' => $itemId,
            'qty' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_mutations')->insert([
            'date' => '2026-10-29',
            'warehouse_id' => $warehouse['WIP-CUT'],
            'item_id' => $itemId,
            'qty_change' => 2,
            'direction' => 'in',
            'source_type' => 'cutting_wip',
            'source_id' => $jobId,
            'cutting_job_bundle_id' => $bundleId,
            'unit_cost' => 100,
            'total_cost' => 200,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'user' => $user,
            'employee_id' => $employeeId,
            'bundle_id' => $bundleId,
            'wip_cut_id' => $warehouse['WIP-CUT'],
            'wip_sew_id' => $warehouse['WIP-SEW'],
            'rej_cut_id' => $warehouse['REJ-CUT'],
            'rej_sew_id' => $warehouse['REJ-SEW'],
        ];
    }
}
