<?php

namespace Tests\Feature\Production;

use App\Models\Account;
use App\Models\FinishingJob;
use App\Models\FinishingJobLine;
use App\Models\FinishingMaterialCorrection;
use App\Models\FinishingRepair;
use App\Models\FinishingRepairLine;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\ItemBom;
use App\Models\ItemBomLine;
use App\Models\Journal;
use App\Models\PurchaseRequest;
use App\Models\StandaloneAccountingJournal;
use App\Models\User;
use App\Services\Accounting\JournalService;
use App\Services\Accounting\StandaloneProductionPostingService;
use App\Services\Inventory\InventoryService;
use App\Services\Production\FinishingBomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinishingJobLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_correction_creates_audited_purchase_request_for_related_item(): void
    {
        $fixture = $this->postedFixture();
        $material = Item::create([
            'code' => 'PACK-CORRECTION-TEST',
            'name' => 'Packing correction test material',
            'unit' => 'pcs',
            'type' => 'material',
            'item_role' => 'production_supply',
            'is_stocked' => true,
            'active' => true,
        ]);
        $bom = ItemBom::create([
            'item_id' => $fixture['item_id'],
            'name' => 'Packing correction test BOM',
            'active' => true,
        ]);
        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
            'qty' => 1,
            'uom' => 'pcs',
            'scrap_pct' => 0,
            'is_optional' => false,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($fixture['user'])->post(route('production.finishing_material_corrections.store'), [
            'finishing_item_id' => $fixture['item_id'],
            'material_item_id' => $material->id,
            'action' => 'purchase',
            'qty' => 3,
            'reason' => 'Stok RM tidak cukup untuk finishing hari ini.',
        ]);

        $correction = FinishingMaterialCorrection::query()->latest('id')->firstOrFail();
        $purchaseRequest = PurchaseRequest::query()->findOrFail($correction->reference_id);
        $response->assertRedirect(route('purchasing.purchase_requests.show', $purchaseRequest));

        $this->assertSame('purchase', $correction->action);
        $this->assertSame('open', $correction->status);
        $this->assertSame($fixture['user']->id, $correction->created_by);
        $this->assertSame('Stok RM tidak cukup untuk finishing hari ini.', $correction->reason);
        $this->assertSame('draft', $purchaseRequest->status);
        $this->assertDatabaseHas('purchase_request_lines', [
            'purchase_request_id' => $purchaseRequest->id,
            'item_id' => $material->id,
            'qty' => 3,
        ]);
    }

    public function test_material_correction_stock_creates_audited_draft_adjustment(): void
    {
        $fixture = $this->postedFixture();
        $material = Item::create([
            'code' => 'STOCK-CORRECTION-TEST',
            'name' => 'Stock correction test material',
            'unit' => 'pcs',
            'type' => 'material',
            'item_role' => 'production_supply',
            'is_stocked' => true,
            'active' => true,
        ]);
        $bom = ItemBom::create(['item_id' => $fixture['item_id'], 'name' => 'Stock correction BOM', 'active' => true]);
        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
            'qty' => 1,
            'uom' => 'pcs',
            'scrap_pct' => 0,
            'is_optional' => false,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($fixture['user'])->post(route('production.finishing_material_corrections.store'), [
            'finishing_item_id' => $fixture['item_id'],
            'material_item_id' => $material->id,
            'action' => 'stock',
            'qty' => 2.5,
            'reason' => 'Hasil hitung fisik berbeda dari saldo sistem.',
        ]);

        $correction = FinishingMaterialCorrection::query()->latest('id')->firstOrFail();
        $adjustment = InventoryAdjustment::query()->findOrFail($correction->reference_id);
        $response->assertRedirect(route('inventory.adjustments.show', $adjustment));

        $this->assertSame('stock', $correction->action);
        $this->assertSame('draft', $adjustment->status);
        $this->assertSame(FinishingMaterialCorrection::class, $adjustment->reference_type);
        $this->assertDatabaseHas('inventory_adjustment_lines', [
            'inventory_adjustment_id' => $adjustment->id,
            'item_id' => $material->id,
            'qty_change' => 2.5,
            'direction' => 'in',
        ]);
    }

    public function test_admin_cannot_create_stock_correction_and_must_contact_owner(): void
    {
        $fixture = $this->postedFixture();
        $admin = User::factory()->create([
            'employee_code' => 'USR-FIN-ADMIN',
            'role' => 'admin',
            'is_developer' => false,
        ]);
        $material = Item::create([
            'code' => 'STOCK-CORRECTION-ADMIN-TEST',
            'name' => 'Admin stock correction test material',
            'unit' => 'pcs',
            'type' => 'material',
            'item_role' => 'production_supply',
            'is_stocked' => true,
            'active' => true,
        ]);
        $bom = ItemBom::create(['item_id' => $fixture['item_id'], 'name' => 'Admin correction BOM', 'active' => true]);
        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
            'qty' => 1,
            'uom' => 'pcs',
            'scrap_pct' => 0,
            'is_optional' => false,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('production.finishing_material_corrections.store'), [
            'finishing_item_id' => $fixture['item_id'],
            'material_item_id' => $material->id,
            'action' => 'stock',
            'qty' => 1,
            'reason' => 'Koreksi stok dari admin.',
        ]);

        $response->assertRedirect()->assertSessionHasErrors('action');
        $this->assertDatabaseMissing('finishing_material_corrections', [
            'material_item_id' => $material->id,
            'action' => 'stock',
        ]);
        $this->assertSame(0, InventoryAdjustment::query()
            ->where('reference_type', FinishingMaterialCorrection::class)
            ->count());
    }

    public function test_material_correction_bom_is_completed_when_bom_is_saved(): void
    {
        $fixture = $this->postedFixture();
        DB::table('items')->where('id', $fixture['item_id'])->update([
            'type' => 'finished_good',
            'can_make' => true,
        ]);
        $material = Item::create([
            'code' => 'BOM-CORRECTION-TEST',
            'name' => 'BOM correction test material',
            'unit' => 'pcs',
            'type' => 'material',
            'item_role' => 'production_supply',
            'is_stocked' => true,
            'active' => true,
        ]);
        $bom = ItemBom::create(['item_id' => $fixture['item_id'], 'name' => 'BOM correction test', 'active' => true]);
        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
            'qty' => 1,
            'uom' => 'pcs',
            'scrap_pct' => 0,
            'is_optional' => false,
            'sort_order' => 1,
        ]);

        $this->actingAs($fixture['user'])->post(route('production.finishing_material_corrections.store'), [
            'finishing_item_id' => $fixture['item_id'],
            'material_item_id' => $material->id,
            'action' => 'bom',
            'reason' => 'Standar packing perlu dikoreksi sesuai pemakaian aktual.',
        ])->assertRedirect(route('master.item_boms.edit', ['bom' => $bom, 'correction_id' => FinishingMaterialCorrection::query()->latest('id')->value('id')]));

        $correction = FinishingMaterialCorrection::query()->latest('id')->firstOrFail();
        $this->actingAs($fixture['user'])->put(route('master.item_boms.update', $bom), [
            'name' => $bom->name,
            'active' => 1,
            'material_correction_id' => $correction->id,
            'lines' => [[
                'material_item_id' => $material->id,
                'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
                'qty' => 1.25,
                'uom' => 'pcs',
                'scrap_pct' => 0,
                'is_optional' => 0,
            ]],
        ])->assertRedirect(route('master.item_boms.edit', $bom));

        $this->assertDatabaseHas('finishing_material_corrections', [
            'id' => $correction->id,
            'status' => 'completed',
            'reference_type' => ItemBom::class,
            'reference_id' => $bom->id,
        ]);
        $this->assertDatabaseHas('item_bom_lines', [
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'qty' => 1.25,
        ]);
    }

    public function test_finishing_show_resolves_bundle_and_employee_operator(): void
    {
        $fixture = $this->postedFixture();

        $response = $this->actingAs($fixture['user'])
            ->get(route('production.finishing_jobs.show', $fixture['job']->id));

        $response->assertOk();
        $response->assertSee('Finishing lifecycle operator');
        $response->assertSee('summary-warn mono">1', false);
    }

    public function test_finishing_create_hides_legacy_wip_already_consumed_by_active_job(): void
    {
        config(['inventory.readiness_source' => 'cache']);
        $fixture = $this->postedFixture();

        // Simulate the legacy state found in production: the posted job has
        // consumed the WIP, but the old reservation marker was not updated.
        DB::table('sewing_return_lines')
            ->where('id', $fixture['return_line_id'])
            ->update(['finished_qty' => 0]);
        DB::table('cutting_job_bundles')
            ->where('id', $fixture['bundle_id'])
            ->update([
                'wip_warehouse_id' => $fixture['warehouses']['WIP-FIN'],
                'wip_qty' => 2,
            ]);

        $response = $this->actingAs($fixture['user'])
            ->get(route('production.finishing_jobs.create'));

        $response->assertOk();
        $response->assertDontSee('FIN-LIFECYCLE-TEST');
    }

    public function test_finishing_packing_bom_is_consumed_once_and_marks_line_applied(): void
    {
        $fixture = $this->postedFixture();
        $material = Item::create([
            'code' => 'PACK-FIN-LIFECYCLE-TEST',
            'name' => 'Packing supply lifecycle test',
            'unit' => 'pcs',
            'type' => 'material',
            'item_role' => 'production_supply',
            'is_stocked' => true,
            'active' => true,
        ]);
        $bom = ItemBom::create([
            'item_id' => $fixture['item_id'],
            'name' => 'Packing BOM lifecycle test',
            'active' => true,
        ]);
        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
            'qty' => 2,
            'uom' => 'pcs',
            'scrap_pct' => 0,
            'is_optional' => false,
            'sort_order' => 1,
        ]);
        app(InventoryService::class)->stockIn(
            warehouseId: $fixture['warehouses']['RM'],
            itemId: $material->id,
            qty: 10,
            date: '2026-10-27',
            sourceType: 'grn_finishing_lifecycle_test',
            sourceId: $fixture['job']->id,
            notes: 'RM packing lifecycle test',
            lotId: null,
            unitCost: 2500,
            affectLotCost: false,
        );

        $service = app(FinishingBomService::class);
        $service->applySupOnlyForPostedJob($fixture['job']->fresh(), new \DateTimeImmutable('2026-10-27'));
        $service->applySupOnlyForPostedJob($fixture['job']->fresh(), new \DateTimeImmutable('2026-10-27'));

        $this->assertSame(1, InventoryMutation::query()
            ->where('source_type', 'finishing_bom')
            ->where('source_id', $fixture['line']->id)
            ->where('item_id', $material->id)
            ->count());
        $this->assertSame(-2.0, (float) InventoryMutation::query()
            ->where('source_type', 'finishing_bom')
            ->where('source_id', $fixture['line']->id)
            ->where('item_id', $material->id)
            ->value('qty_change'));
        $this->assertNotNull($fixture['line']->fresh()->bom_applied_at);
        $this->assertFalse((bool) $fixture['line']->fresh()->bom_has_gaps);
    }

    public function test_finishing_packing_bom_blocks_when_rm_cost_is_missing_without_partial_mutation(): void
    {
        $fixture = $this->postedFixture();
        $material = Item::create([
            'code' => 'PACK-FIN-NO-COST-TEST',
            'name' => 'Packing supply without cost',
            'unit' => 'pcs',
            'type' => 'material',
            'item_role' => 'production_supply',
            'is_stocked' => true,
            'active' => true,
        ]);
        $bom = ItemBom::create([
            'item_id' => $fixture['item_id'],
            'name' => 'Packing BOM no cost test',
            'active' => true,
        ]);
        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
            'qty' => 1,
            'uom' => 'pcs',
            'scrap_pct' => 0,
            'is_optional' => false,
            'sort_order' => 1,
        ]);
        // Kuantitas ada, tetapi tidak ada incoming unit cost. Ini membedakan
        // error HPP dari error stok kurang.
        InventoryStock::create([
            'warehouse_id' => $fixture['warehouses']['RM'],
            'item_id' => $material->id,
            'qty' => 10,
        ]);

        try {
            app(FinishingBomService::class)->applySupOnlyForPostedJob(
                $fixture['job']->fresh(),
                new \DateTimeImmutable('2026-10-27'),
            );
            $this->fail('Posting BOM seharusnya ditolak saat HPP RM belum tersedia.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString(
                'HPP kelengkapan packing belum tersedia',
                implode(' ', $e->errors()['supplies'] ?? []),
            );
        }

        $this->assertSame(0, InventoryMutation::query()
            ->where('source_type', 'finishing_bom')
            ->where('source_id', $fixture['line']->id)
            ->where('item_id', $material->id)
            ->count());
        $this->assertNull($fixture['line']->fresh()->bom_applied_at);
        $this->assertFalse((bool) $fixture['line']->fresh()->bom_has_gaps);
    }

    public function test_finishing_journal_allocates_bom_only_to_ok_output_when_reject_exists(): void
    {
        config(['accounting.standalone_dual_posting_production_finishing_enabled' => true]);
        $fixture = $this->postedFixture();

        // Fixture sudah memiliki jejak legacy untuk job yang sama. Simulasikan
        // repost agar test memastikan journal service memakai cycle aktif baru.
        Journal::query()
            ->where('source_type', 'finishing_job')
            ->where('source_id', $fixture['job']->id)
            ->whereNull('voided_at')
            ->update(['voided_at' => now()]);

        $journal = app(JournalService::class)->postFinishingJob($fixture['job']);

        $this->assertNotNull($journal);
        $this->assertEqualsWithDelta(210.0, (float) $journal->lines->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(210.0, (float) $journal->lines->sum('credit'), 0.01);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => Account::where('code', '1203')->value('id'),
            'debit' => 110,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => Account::where('code', '1204')->value('id'),
            'debit' => 100,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => Account::where('code', '1202')->value('id'),
            'debit' => 0,
            'credit' => 210,
        ]);
    }

    public function test_void_reverses_inventory_reservation_legacy_and_standalone_atomically(): void
    {
        config(['accounting.standalone_dual_posting_production_finishing_enabled' => true]);
        $fixture = $this->postedFixture();

        $response = $this->actingAs($fixture['user'])
            ->post(route('production.finishing_jobs.void', $fixture['job']->id));

        $response->assertRedirect(route('production.finishing_jobs.show', $fixture['job']->id));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('finishing_jobs', [
            'id' => $fixture['job']->id,
            'status' => 'void',
        ]);
        $this->assertSame(10.0, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['warehouses']['WIP-FIN'])
            ->where('item_id', $fixture['item_id'])
            ->value('qty'));
        $this->assertSame(0.0, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['warehouses']['WH-PRD'])
            ->where('item_id', $fixture['item_id'])
            ->value('qty'));
        $this->assertSame(0.0, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['warehouses']['REJ-FIN'])
            ->where('item_id', $fixture['item_id'])
            ->value('qty'));
        $this->assertSame(0, (int) DB::table('sewing_return_lines')
            ->where('id', $fixture['return_line_id'])
            ->value('finished_qty'));
        $this->assertSame(2.0, (float) DB::table('cutting_job_bundles')
            ->where('id', $fixture['bundle_id'])
            ->value('wip_qty'));

        $this->assertSame(0, Journal::query()
            ->whereIn('source_type', ['finishing_job', 'finishing_bom'])
            ->where('source_id', $fixture['job']->id)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(0, StandaloneAccountingJournal::query()
            ->where('source_id', $fixture['job']->id)
            ->whereNull('voided_at')
            ->count());
        $this->assertGreaterThan(0, InventoryMutation::query()
            ->where('source_type', 'finishing_job_reverse')
            ->where('source_id', $fixture['job']->id)
            ->count());
        $this->assertGreaterThan(0, InventoryMutation::query()
            ->where('source_type', 'finishing_bom_reverse')
            ->where('source_id', $fixture['line']->id)
            ->count());

        // Klik VOID kedua tidak boleh menambah reversal.
        $reverseCount = InventoryMutation::query()
            ->where('source_type', 'finishing_job_reverse')
            ->where('source_id', $fixture['job']->id)
            ->count();
        $this->actingAs($fixture['user'])
            ->post(route('production.finishing_jobs.void', $fixture['job']->id))
            ->assertRedirect(route('production.finishing_jobs.show', $fixture['job']->id));
        $this->assertSame($reverseCount, InventoryMutation::query()
            ->where('source_type', 'finishing_job_reverse')
            ->where('source_id', $fixture['job']->id)
            ->count());
    }

    public function test_unpost_reverses_posting_but_keeps_sewing_reservation_and_is_idempotent(): void
    {
        config(['accounting.standalone_dual_posting_production_finishing_enabled' => true]);
        $fixture = $this->postedFixture();

        $this->actingAs($fixture['user'])
            ->post(route('production.finishing_jobs.unpost', $fixture['job']->id))
            ->assertRedirect(route('production.finishing_jobs.show', $fixture['job']->id));

        $this->assertDatabaseHas('finishing_jobs', [
            'id' => $fixture['job']->id,
            'status' => 'draft',
            'posted_at' => null,
        ]);
        $this->assertSame(2, (int) DB::table('sewing_return_lines')
            ->where('id', $fixture['return_line_id'])
            ->value('finished_qty'));
        $this->assertSame(0, Journal::query()
            ->where('source_id', $fixture['job']->id)
            ->whereNull('voided_at')
            ->count());

        $this->actingAs($fixture['user'])
            ->post(route('production.finishing_jobs.unpost', $fixture['job']->id))
            ->assertRedirect(route('production.finishing_jobs.show', $fixture['job']->id));

        $this->assertSame(2, (int) DB::table('sewing_return_lines')
            ->where('id', $fixture['return_line_id'])
            ->value('finished_qty'));
        $this->assertSame(3, InventoryMutation::query()
            ->where('source_type', 'finishing_job_reverse')
            ->where('source_id', $fixture['job']->id)
            ->count());
    }

    public function test_unpost_then_post_creates_only_current_cycle_cost_and_new_mirror_trace(): void
    {
        config(['accounting.standalone_dual_posting_production_finishing_enabled' => true]);
        $fixture = $this->postedFixture();
        $user = $fixture['user'];

        $this->actingAs($user)
            ->post(route('production.finishing_jobs.unpost', $fixture['job']->id))
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('production.finishing_jobs.post', $fixture['job']->id))
            ->assertRedirect(route('production.finishing_jobs.show', $fixture['job']->id));

        $this->assertDatabaseHas('finishing_jobs', [
            'id' => $fixture['job']->id,
            'status' => 'posted',
        ]);
        $this->assertSame(1, Journal::query()
            ->where('source_type', 'finishing_job')
            ->where('source_id', $fixture['job']->id)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(1, StandaloneAccountingJournal::query()
            ->where('source_type', 'finishing_job_repost_1')
            ->where('source_id', $fixture['job']->id)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(8.0, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['warehouses']['WIP-FIN'])
            ->where('item_id', $fixture['item_id'])
            ->value('qty'));
        $this->assertSame(1.0, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['warehouses']['WH-PRD'])
            ->where('item_id', $fixture['item_id'])
            ->value('qty'));
    }

    public function test_unpost_then_post_reapplies_packing_bom_in_new_cycle(): void
    {
        config(['accounting.standalone_dual_posting_production_finishing_enabled' => true]);
        $fixture = $this->postedFixture();
        $material = Item::create([
            'code' => 'PACK-FIN-REPOST-TEST',
            'name' => 'Packing supply repost test',
            'unit' => 'pcs',
            'type' => 'material',
            'item_role' => 'production_supply',
            'is_stocked' => true,
            'active' => true,
        ]);
        $bom = ItemBom::create([
            'item_id' => $fixture['item_id'],
            'name' => 'Packing BOM repost test',
            'active' => true,
        ]);
        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
            'qty' => 1,
            'uom' => 'pcs',
            'scrap_pct' => 0,
            'is_optional' => false,
            'sort_order' => 1,
        ]);
        app(InventoryService::class)->stockIn(
            warehouseId: $fixture['warehouses']['RM'],
            itemId: $material->id,
            qty: 10,
            date: '2026-10-27',
            sourceType: 'grn_finishing_repost_test',
            sourceId: $fixture['job']->id,
            notes: 'RM packing repost test',
            lotId: null,
            unitCost: 3000,
            affectLotCost: false,
        );

        $this->actingAs($fixture['user'])
            ->post(route('production.finishing_jobs.unpost', $fixture['job']->id))
            ->assertRedirect(route('production.finishing_jobs.show', $fixture['job']->id));

        $this->assertNull($fixture['line']->fresh()->bom_applied_at);

        $this->actingAs($fixture['user'])
            ->post(route('production.finishing_jobs.post', $fixture['job']->id))
            ->assertRedirect(route('production.finishing_jobs.show', $fixture['job']->id));

        $this->assertSame(1, InventoryMutation::query()
            ->where('source_type', 'finishing_bom')
            ->where('source_id', $fixture['line']->id)
            ->where('item_id', $material->id)
            ->where('qty_change', '<', 0)
            ->count());
        $this->assertNotNull($fixture['line']->fresh()->bom_applied_at);
        $this->assertSame(1, Journal::query()
            ->where('source_type', 'finishing_bom')
            ->where('source_id', $fixture['job']->id)
            ->whereNull('voided_at')
            ->count());
    }

    public function test_finishing_repair_unpost_returns_reject_to_rej_fin_and_is_idempotent(): void
    {
        $fixture = $this->postedFixture();
        $rejectItemId = (int) DB::table('items')->insertGetId([
            'code' => 'REJ-FIN-LIFECYCLE-TEST',
            'name' => 'Reject finishing lifecycle test item',
            'unit' => 'pcs',
            'type' => 'finished',
            'last_purchase_price' => 0,
            'hpp' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $repair = FinishingRepair::create([
            'code' => 'FRP-FIN-LIFECYCLE-TEST',
            'date' => '2026-10-27',
            'status' => 'posted',
            'created_by_user_id' => $fixture['user']->id,
        ]);
        FinishingRepairLine::create([
            'finishing_repair_id' => $repair->id,
            'finishing_job_line_id' => $fixture['line']->id,
            'item_id' => $fixture['item_id'],
            'reject_item_id' => $rejectItemId,
            'cutting_job_bundle_id' => $fixture['bundle_id'],
            'qty_ok' => 0,
            'qty_reject' => 1,
        ]);

        DB::table('inventory_stocks')->insert([
            ['warehouse_id' => $fixture['warehouses']['WH-RTS'], 'item_id' => $rejectItemId, 'qty' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['warehouses']['REJ-FIN'])
            ->where('item_id', $fixture['item_id'])
            ->update(['qty' => 0, 'updated_at' => now()]);
        $this->mutation($fixture['warehouses']['REJ-FIN'], $fixture['item_id'], -1, 'finishing_reject_convert', $fixture['line']->id, $fixture['bundle_id'], 100, "Tidak bisa diperbaiki {$repair->code} OUT SKU asli");
        $this->mutation($fixture['warehouses']['WH-RTS'], $rejectItemId, 1, 'finishing_reject_convert', $fixture['line']->id, $fixture['bundle_id'], 100, "Tidak bisa diperbaiki {$repair->code} IN WH-RTS REJ-FIN-LIFECYCLE-TEST");

        $this->actingAs($fixture['user'])
            ->post(route('production.finishing_repairs.unpost', $repair->id))
            ->assertRedirect(route('production.finishing_repairs.show', $repair->id));

        $this->assertDatabaseHas('finishing_repairs', [
            'id' => $repair->id,
            'status' => 'draft',
        ]);
        $this->assertSame(1.0, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['warehouses']['REJ-FIN'])
            ->where('item_id', $fixture['item_id'])
            ->value('qty'));
        $this->assertSame(0.0, (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $fixture['warehouses']['WH-RTS'])
            ->where('item_id', $rejectItemId)
            ->value('qty'));

        $reverseCount = InventoryMutation::query()
            ->where('source_type', 'finishing_reject_convert_reverse')
            ->where('source_id', $repair->id)
            ->count();
        $this->assertSame(2, $reverseCount);

        $this->actingAs($fixture['user'])
            ->post(route('production.finishing_repairs.unpost', $repair->id))
            ->assertRedirect(route('production.finishing_repairs.show', $repair->id));

        $this->assertSame($reverseCount, InventoryMutation::query()
            ->where('source_type', 'finishing_reject_convert_reverse')
            ->where('source_id', $repair->id)
            ->count());
    }

    /** @return array<string, mixed> */
    private function postedFixture(): array
    {
        $user = User::factory()->create([
            'employee_code' => 'USR-FIN-LIFECYCLE',
            'role' => 'owner',
            'is_developer' => false,
        ]);

        $warehouses = [];
        foreach (['WIP-FIN', 'WH-PRD', 'WH-RTS', 'REJ-FIN', 'REJ-SEW', 'RM'] as $code) {
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
            'code' => 'FIN-LIFECYCLE-TEST',
            'name' => 'Finishing lifecycle test item',
            'unit' => 'pcs',
            'type' => 'finished',
            'last_purchase_price' => 0,
            'hpp' => 100,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lotId = (int) DB::table('lots')->insertGetId([
            'code' => 'LOT-FIN-LIFECYCLE',
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
        $employeeId = (int) DB::table('employees')->insertGetId([
            'code' => 'EMP-FIN-LIFECYCLE',
            'name' => 'Finishing lifecycle operator',
            'role' => 'sewing',
            'payment_type' => 'variable',
            'weekly_fixed_salary' => 0,
            'default_piece_rate' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $cuttingJobId = (int) DB::table('cutting_jobs')->insertGetId([
            'code' => 'CUT-FIN-LIFECYCLE',
            'date' => '2026-10-27',
            'warehouse_id' => $warehouses['WIP-FIN'],
            'lot_id' => $lotId,
            'fabric_item_id' => $itemId,
            'operator_id' => $employeeId,
            'total_bundles' => 1,
            'total_qty_pcs' => 2,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $bundleId = (int) DB::table('cutting_job_bundles')->insertGetId([
            'cutting_job_id' => $cuttingJobId,
            'bundle_code' => 'BND-FIN-LIFECYCLE',
            'bundle_no' => 1,
            'lot_id' => $lotId,
            'finished_item_id' => $itemId,
            'qty_pcs' => 2,
            'qty_used_fabric' => 1,
            'operator_id' => $employeeId,
            'status' => 'qc_ok',
            'qty_qc_ok' => 2,
            'qty_qc_reject' => 0,
            'wip_warehouse_id' => $warehouses['WIP-FIN'],
            'wip_qty' => 0,
            'cut_wip_warehouse_id' => $warehouses['WIP-FIN'],
            'cut_wip_qty' => 2,
            'sewing_picked_qty' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $pickupId = (int) DB::table('sewing_pickups')->insertGetId([
            'code' => 'SWP-FIN-LIFECYCLE',
            'date' => '2026-10-27',
            'warehouse_id' => $warehouses['WIP-FIN'],
            'operator_id' => $employeeId,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $pickupLineId = (int) DB::table('sewing_pickup_lines')->insertGetId([
            'sewing_pickup_id' => $pickupId,
            'cutting_job_bundle_id' => $bundleId,
            'finished_item_id' => $itemId,
            'qty_bundle' => 2,
            'qty_returned_ok' => 2,
            'qty_returned_reject' => 0,
            'status' => 'done',
            'unit_cost' => 100,
            'wage_per_pcs' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $returnId = (int) DB::table('sewing_returns')->insertGetId([
            'code' => 'SRT-FIN-LIFECYCLE',
            'date' => '2026-10-27',
            'warehouse_id' => $warehouses['WIP-FIN'],
            'operator_id' => $employeeId,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $returnLineId = (int) DB::table('sewing_return_lines')->insertGetId([
            'sewing_return_id' => $returnId,
            'sewing_pickup_line_id' => $pickupLineId,
            'qty_ok' => 2,
            'qty_reject' => 0,
            'finished_qty' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = FinishingJob::create([
            'code' => 'FIN-LIFECYCLE-TEST',
            'date' => '2026-10-27',
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_at' => now(),
        ]);
        $line = FinishingJobLine::create([
            'finishing_job_id' => $job->id,
            'bundle_id' => $bundleId,
            'operator_id' => $employeeId,
            'sewing_operator_id' => $employeeId,
            'sewing_operator_name' => 'Finishing lifecycle operator',
            'item_id' => $itemId,
            'qty_in' => 2,
            'qty_ok' => 1,
            'qty_reject' => 1,
            'reject_cause' => 'finishing',
            'processed_at' => '2026-10-27',
        ]);

        DB::table('inventory_stocks')->insert([
            ['warehouse_id' => $warehouses['WIP-FIN'], 'item_id' => $itemId, 'qty' => 9, 'created_at' => now(), 'updated_at' => now()],
            ['warehouse_id' => $warehouses['WH-PRD'], 'item_id' => $itemId, 'qty' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['warehouse_id' => $warehouses['REJ-FIN'], 'item_id' => $itemId, 'qty' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['warehouse_id' => $warehouses['RM'], 'item_id' => $itemId, 'qty' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->mutation($warehouses['WIP-FIN'], $itemId, -2, FinishingJob::class, $job->id, $bundleId, 100);
        $this->mutation($warehouses['WH-PRD'], $itemId, 1, FinishingJob::class, $job->id, $bundleId, 100);
        $this->mutation($warehouses['REJ-FIN'], $itemId, 1, FinishingJob::class, $job->id, $bundleId, 100);
        $this->mutation($warehouses['RM'], $itemId, -1, 'finishing_bom', $line->id, $bundleId, 10);
        $this->mutation($warehouses['WIP-FIN'], $itemId, 1, 'finishing_bom', $line->id, $bundleId, 10);

        $this->legacyJournal('finishing_job', $job->id, [
            ['code' => '1203', 'debit' => 100, 'credit' => 0],
            ['code' => '1204', 'debit' => 100, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 200],
        ]);
        $this->legacyJournal('finishing_bom', $job->id, [
            ['code' => '1202', 'debit' => 10, 'credit' => 0],
            ['code' => '1201', 'debit' => 0, 'credit' => 10],
        ]);
        app(StandaloneProductionPostingService::class)->mirrorFinishingJob($job->id);

        return [
            'user' => $user,
            'job' => $job,
            'line' => $line,
            'bundle_id' => $bundleId,
            'return_line_id' => $returnLineId,
            'item_id' => $itemId,
            'warehouses' => $warehouses,
        ];
    }

    private function mutation(int $warehouseId, int $itemId, float $qty, string $sourceType, int $sourceId, int $bundleId, float $unitCost, ?string $notes = null): void
    {
        InventoryMutation::create([
            'date' => '2026-10-27',
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'qty_change' => $qty,
            'direction' => $qty >= 0 ? 'in' : 'out',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'notes' => $notes,
            'cutting_job_bundle_id' => $bundleId,
            'unit_cost' => $unitCost,
            'total_cost' => $qty * $unitCost,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @param array<int, array{code:string,debit:int|float,credit:int|float}> $lines */
    private function legacyJournal(string $sourceType, int $sourceId, array $lines): Journal
    {
        $journal = Journal::create([
            'date' => '2026-10-27',
            'description' => 'Test '.$sourceType.' #'.$sourceId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'posted_at' => now(),
        ]);
        $journal->lines()->createMany(array_map(function (array $line): array {
            return [
                'account_id' => Account::firstOrCreate(
                    ['code' => $line['code']],
                    ['name' => 'Test '.$line['code'], 'type' => 'asset', 'is_active' => true],
                )->id,
                'debit' => $line['debit'],
                'credit' => $line['credit'],
            ];
        }, $lines));

        return $journal;
    }
}
