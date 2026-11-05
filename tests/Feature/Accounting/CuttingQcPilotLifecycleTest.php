<?php

namespace Tests\Feature\Accounting;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\CuttingJobLot;
use App\Models\Employee;
use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\ItemBom;
use App\Models\ItemBomLine;
use App\Models\ItemCategory;
use App\Models\Journal;
use App\Models\Lot;
use App\Models\PieceRate;
use App\Models\StandaloneAccountingJournal;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use App\Services\Accounting\StandaloneProductionPostingService;
use App\Services\Production\CuttingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CuttingQcPilotLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Test legacy QC flows independently from a developer's local pilot
        // flag, then enable it explicitly only in the dedicated auto-BOM test.
        config(['accounting.standalone_production_cutting_qc_auto_bom_enabled' => false]);
        $this->resetWipWarehouseCache();
    }

    protected function tearDown(): void
    {
        $this->resetWipWarehouseCache();
        parent::tearDown();
    }

    public function test_qc_cutting_mirrors_wip_is_idempotent_then_cancel_reverses_and_reposts(): void
    {
        config(['accounting.standalone_dual_posting_production_cutting_enabled' => true]);

        [$owner, $job, $bundle] = $this->makePostedCuttingJob();

        $payload = [
            'qc_date' => '2026-10-27',
            'results' => [[
                'cutting_job_bundle_id' => $bundle->id,
                'qty_ok' => 8,
                'qty_reject' => 2,
                'reject_reason' => 'QC pilot reject',
            ]],
        ];

        $this->actingAs($owner)
            ->put(route('production.qc.cutting.update', $job), $payload)
            ->assertRedirect(route('production.cutting_jobs.show', $job));

        $this->actingAs($owner)
            ->put(route('production.qc.cutting.update', $job), $payload)
            ->assertRedirect(route('production.cutting_jobs.show', $job));

        $this->assertSame(1, Journal::query()
            ->where('source_type', 'cutting_wip')
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(1, StandaloneAccountingJournal::query()
            ->where('source_type', 'cutting_wip')
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(1, DB::table('inventory_mutations')
            ->where('source_type', 'cutting_wip')
            ->where('source_id', $job->id)
            ->count());
        $this->assertSame(1, DB::table('inventory_mutations')
            ->where('source_type', 'cutting_reject')
            ->where('source_id', $job->id)
            ->count());

        $this->assertEqualsWithDelta(1100, (float) Journal::query()
            ->where('source_type', 'cutting_wip')
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->with('lines')
            ->firstOrFail()
            ->lines
            ->sum('debit'), 0.01);

        $this->actingAs($owner)
            ->post(route('production.qc.cutting.cancel', $job))
            ->assertRedirect();

        $this->assertDatabaseMissing('qc_results', [
            'cutting_job_id' => $job->id,
            'cutting_job_bundle_id' => $bundle->id,
        ]);
        $this->assertDatabaseHas('cutting_jobs', [
            'id' => $job->id,
            'status' => 'sent_to_qc',
        ]);
        $this->assertSame(0, Journal::query()
            ->where('source_type', 'cutting_wip')
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(0, StandaloneAccountingJournal::query()
            ->where('source_id', $job->id)
            ->whereIn('source_type', ['cutting_wip', 'cutting_wip_repost_1'])
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(2, StandaloneAccountingJournal::query()
            ->where('source_id', $job->id)
            ->where(function ($query): void {
                $query->where('source_type', 'cutting_wip')
                    ->orWhere('source_type', 'like', 'cutting_wip_reversal_%');
            })
            ->count());

        $this->actingAs($owner)
            ->put(route('production.qc.cutting.update', $job), $payload)
            ->assertRedirect(route('production.cutting_jobs.show', $job));

        $this->assertSame(1, StandaloneAccountingJournal::query()
            ->where('source_type', 'cutting_wip_repost_1')
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->count());
        $this->assertEqualsWithDelta(1100, (float) StandaloneAccountingJournal::query()
            ->where('source_id', $job->id)
            ->whereIn('source_type', ['cutting_wip', 'cutting_wip_repost_1'])
            ->whereNull('voided_at')
            ->with('lines')
            ->get()
            ->flatMap->lines
            ->sum('debit'), 0.01);
    }

    public function test_qc_journal_does_not_accrue_cutting_wage_twice(): void
    {
        config(['accounting.standalone_dual_posting_production_cutting_enabled' => true]);

        [$owner, $job, $bundle] = $this->makePostedCuttingJob();

        $this->actingAs($owner)
            ->put(route('production.qc.cutting.update', $job), [
                'qc_date' => '2026-10-27',
                'results' => [[
                    'cutting_job_bundle_id' => $bundle->id,
                    'qty_ok' => 8,
                    'qty_reject' => 2,
                ]],
            ])
            ->assertRedirect();

        $qcJournal = Journal::query()
            ->where('source_type', JournalService::SRC_CUTTING_WIP)
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->with('lines.account')
            ->firstOrFail();

        $this->assertEqualsWithDelta(1100, (float) $qcJournal->lines->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(1100, (float) $qcJournal->lines
            ->where('account.code', JournalService::CODE_INV_WIP)
            ->sum('credit'), 0.01);
        $this->assertEqualsWithDelta(0, (float) $qcJournal->lines
            ->where('account.code', JournalService::CODE_PAYROLL_PAYABLE)
            ->sum('credit'), 0.01);

        $standalonePayroll = StandaloneAccountingJournal::query()
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->with('lines.account')
            ->get()
            ->flatMap->lines
            ->where('account.code', '2-130')
            ->sum('credit');
        $this->assertEqualsWithDelta(1000, (float) $standalonePayroll, 0.01);
    }

    public function test_resaving_posted_qc_with_changed_qty_is_rejected(): void
    {
        config(['accounting.standalone_dual_posting_production_cutting_enabled' => true]);

        [$owner, $job, $bundle] = $this->makePostedCuttingJob();
        $payload = [
            'qc_date' => '2026-10-27',
            'results' => [[
                'cutting_job_bundle_id' => $bundle->id,
                'qty_ok' => 8,
                'qty_reject' => 2,
            ]],
        ];

        $this->actingAs($owner)
            ->put(route('production.qc.cutting.update', $job), $payload)
            ->assertRedirect();

        $this->actingAs($owner)
            ->put(route('production.qc.cutting.update', $job), array_replace_recursive($payload, [
                'results' => [[
                    'cutting_job_bundle_id' => $bundle->id,
                    'qty_ok' => 7,
                    'qty_reject' => 3,
                ]],
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('qc_results', [
            'cutting_job_id' => $job->id,
            'cutting_job_bundle_id' => $bundle->id,
            'qty_ok' => 8,
            'qty_reject' => 2,
        ]);
        $this->assertSame(1, InventoryMutation::query()
            ->where('source_type', 'cutting_wip')
            ->where('source_id', $job->id)
            ->count());
        $this->assertSame(1, Journal::query()
            ->where('source_type', JournalService::SRC_CUTTING_WIP)
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->count());
    }

    public function test_qc_edit_shows_hpp_components_by_category(): void
    {
        config(['accounting.standalone_production_cutting_qc_auto_bom_enabled' => true]);

        [$owner, $job, $bundle] = $this->makePostedCuttingJob();
        $supply = Item::create([
            'code' => 'SUP-QC-HPP-'.uniqid(),
            'name' => 'Kelengkapan QC HPP',
            'type' => 'material',
            'item_role' => 'production_supply',
            'base_unit_cost' => 25,
            'active' => true,
        ]);
        $other = Item::create([
            'code' => 'PKG-QC-HPP-'.uniqid(),
            'name' => 'Komponen Lain QC HPP',
            'type' => 'material',
            'item_role' => 'shipping_supply',
            'base_unit_cost' => 10,
            'active' => true,
        ]);
        $bom = ItemBom::create([
            'item_id' => $bundle->finished_item_id,
            'name' => 'QC HPP Component BOM',
            'active' => true,
        ]);
        foreach ([
            [$supply, ItemBomLine::STAGE_SEWING_SUPPLY],
            [$other, ItemBomLine::STAGE_PACKING_SUPPLY],
        ] as $index => [$material, $stage]) {
            ItemBomLine::create([
                'item_bom_id' => $bom->id,
                'material_item_id' => $material->id,
                'usage_stage' => $stage,
                'qty' => 1,
                'uom' => 'pcs',
                'scrap_pct' => 0,
                'is_optional' => false,
                'sort_order' => $index + 1,
            ]);
        }

        $this->actingAs($owner)
            ->get(route('production.qc.cutting.edit', $job))
            ->assertOk()
            ->assertSee('Bahan baku')
            ->assertSee('Bahan pendukung')
            ->assertSee('Lain-lain')
            ->assertSee('HPP / pcs')
            ->assertSee('BOM / pcs')
            ->assertSee('Stok RM tersedia')
            ->assertSee('Stok RM / pcs')
            ->assertSee('Qty OK')
            ->assertSee('Rp / pcs')
            ->assertSee('Total HPP')
            ->assertSee('Estimasi Kelengkapan Jahit')
            ->assertSee('Debit WIP')
            ->assertSee('Est. sisa stok')
            ->assertSee('Pemakaian Material Produksi')
            ->assertSee($supply->code);
    }

    public function test_qc_cutting_consumes_sewing_supply_bom_once_and_keeps_pickup_out_of_bom(): void
    {
        config([
            'accounting.standalone_dual_posting_production_cutting_enabled' => true,
            'accounting.standalone_production_cutting_qc_auto_bom_enabled' => true,
        ]);

        [$owner, $job, $bundle] = $this->makePostedCuttingJob();
        $rm = Warehouse::create([
            'code' => 'RM',
            'name' => 'QC Pilot Raw Material',
            'type' => 'internal',
            'active' => true,
        ]);
        $category = ItemCategory::query()->where('code', 'QC-PILOT')->first()
            ?: ItemCategory::create([
                'code' => 'QC-PILOT',
                'name' => 'QC Pilot Supplies',
            ]);
        $supply = Item::create([
            'code' => 'SUP-QC-PILOT-'.uniqid(),
            'name' => 'QC Pilot Sewing Supply',
            'type' => 'material',
            'item_category_id' => $category->id,
            'item_role' => 'production_supply',
            'unit' => 'pcs',
            'is_stocked' => true,
            'allow_negative' => true,
        ]);
        $bom = ItemBom::create([
            'item_id' => $bundle->finished_item_id,
            'name' => 'QC Pilot Sewing Supply BOM',
            'active' => true,
        ]);
        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $supply->id,
            'usage_stage' => ItemBomLine::STAGE_SEWING_SUPPLY,
            'qty' => 0.25,
            'uom' => 'pcs',
            'scrap_pct' => 0,
            'is_optional' => false,
            'sort_order' => 1,
        ]);
        InventoryStock::create([
            'warehouse_id' => $rm->id,
            'item_id' => $supply->id,
            'qty' => 2,
            'allocated_qty' => 0,
        ]);
        $supplyLot = Lot::create([
            'code' => 'LOT-QC-SUPPLY-'.uniqid(),
            'item_id' => $supply->id,
            'initial_qty' => 2,
            'initial_cost' => 100,
            'qty_onhand' => 2,
            'total_cost' => 200,
            'avg_cost' => 100,
            'status' => 'open',
        ]);
        InventoryMutation::create([
            'date' => '2026-10-27',
            'warehouse_id' => $rm->id,
            'item_id' => $supply->id,
            'qty_change' => 2,
            'direction' => 'in',
            'source_type' => 'qc_pilot_supply_fixture',
            'source_id' => $job->id,
            'lot_id' => $supplyLot->id,
            'unit_cost' => 100,
            'total_cost' => 200,
        ]);

        $payload = [
            'qc_date' => '2026-10-27',
            'results' => [[
                'cutting_job_bundle_id' => $bundle->id,
                'qty_ok' => 8,
                'qty_reject' => 2,
            ]],
        ];

        $response = $this->actingAs($owner)
            ->put(route('production.qc.cutting.update', $job), $payload);
        $response->assertRedirect();

        $this->assertSame(1, InventoryMutation::query()
            ->where('source_type', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY)
            ->where('source_id', $job->id)
            ->where('direction', 'out')
            ->count());
        $this->assertEqualsWithDelta(-2, (float) InventoryMutation::query()
            ->where('source_type', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY)
            ->where('source_id', $job->id)
            ->sum('qty_change'), 0.000001);
        $originalBomLotId = InventoryMutation::query()
            ->where('source_type', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY)
            ->where('source_id', $job->id)
            ->where('direction', 'out')
            ->value('lot_id');
        $this->assertNotNull($originalBomLotId);
        $this->assertSame(1, Journal::query()
            ->where('source_type', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY)
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->count());
        $this->assertEqualsWithDelta(200, (float) Journal::query()
            ->where('source_type', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY)
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->with('lines')
            ->firstOrFail()
            ->lines
            ->sum('debit'), 0.01);
        $this->assertSame(1, StandaloneAccountingJournal::query()
            ->where('source_type', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY)
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->count());

        $this->actingAs($owner)
            ->get(route('production.qc.cutting.edit', $job))
            ->assertOk()
            ->assertSee('Auto BOM kelengkapan jahit')
            ->assertSee($supplyLot->code)
            ->assertSee('Jejak lot aktual')
            ->assertSee('Stok RM sebelum QC')
            ->assertSee('Sekarang: 0,0000 pcs')
            ->assertSee('POSTED');

        $this->actingAs($owner)
            ->put(route('production.qc.cutting.update', $job), $payload)
            ->assertRedirect();

        $this->assertSame(1, InventoryMutation::query()
            ->where('source_type', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY)
            ->where('source_id', $job->id)
            ->where('direction', 'out')
            ->count());
        $this->assertSame(1, Journal::query()
            ->where('source_type', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY)
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->count());

        $this->actingAs($owner)
            ->post(route('production.qc.cutting.cancel', $job))
            ->assertRedirect();

        $this->assertEqualsWithDelta(2, (float) InventoryMutation::query()
            ->where('source_type', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY.'_void')
            ->where('source_id', $job->id)
            ->sum('qty_change'), 0.000001);
        $this->assertSame((int) $originalBomLotId, (int) InventoryMutation::query()
            ->where('source_type', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY.'_void')
            ->where('source_id', $job->id)
            ->value('lot_id'));
        $this->assertEqualsWithDelta(2, (float) Lot::query()->whereKey($originalBomLotId)->value('qty_onhand'), 0.000001);
        $this->assertSame(0, Journal::query()
            ->where('source_type', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY)
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(0, StandaloneAccountingJournal::query()
            ->where('source_type', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY)
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(1, StandaloneAccountingJournal::query()
            ->where('source_type', 'like', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY.'_reversal_%')
            ->where('source_id', $job->id)
            ->count());
    }

    public function test_qc_cutting_auto_bom_does_not_require_cutting_accounting_mirror(): void
    {
        config([
            'accounting.standalone_dual_posting_production_cutting_enabled' => false,
            'accounting.standalone_production_cutting_qc_auto_bom_enabled' => true,
        ]);

        [$owner, $job, $bundle] = $this->makePostedCuttingJob();
        $rm = Warehouse::create([
            'code' => 'RM',
            'name' => 'QC Auto BOM Without Mirror RM',
            'type' => 'internal',
            'active' => true,
        ]);
        $supply = Item::create([
            'code' => 'SUP-QC-NO-MIRROR-'.uniqid(),
            'name' => 'QC Supply Without Mirror',
            'type' => 'material',
            'item_role' => 'production_supply',
            'unit' => 'pcs',
            'is_stocked' => true,
            'active' => true,
        ]);
        $bom = ItemBom::create([
            'item_id' => $bundle->finished_item_id,
            'name' => 'QC BOM Without Mirror',
            'active' => true,
        ]);
        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $supply->id,
            'usage_stage' => ItemBomLine::STAGE_SEWING_SUPPLY,
            'qty' => 0.25,
            'uom' => 'pcs',
            'scrap_pct' => 0,
            'is_optional' => false,
            'sort_order' => 1,
        ]);
        InventoryStock::create([
            'warehouse_id' => $rm->id,
            'item_id' => $supply->id,
            'qty' => 3,
            'allocated_qty' => 0,
        ]);
        $lot = Lot::create([
            'code' => 'LOT-QC-NO-MIRROR-'.uniqid(),
            'item_id' => $supply->id,
            'initial_qty' => 3,
            'initial_cost' => 100,
            'qty_onhand' => 3,
            'total_cost' => 300,
            'avg_cost' => 100,
            'status' => 'open',
        ]);
        InventoryMutation::create([
            'date' => '2026-10-27',
            'warehouse_id' => $rm->id,
            'item_id' => $supply->id,
            'qty_change' => 3,
            'direction' => 'in',
            'source_type' => 'qc_no_mirror_supply_fixture',
            'source_id' => $job->id,
            'lot_id' => $lot->id,
            'unit_cost' => 100,
            'total_cost' => 300,
        ]);

        $this->actingAs($owner)
            ->put(route('production.qc.cutting.update', $job), [
                'qc_date' => '2026-10-27',
                'results' => [[
                    'cutting_job_bundle_id' => $bundle->id,
                    'qty_ok' => 8,
                    'qty_reject' => 2,
                ]],
            ])
            ->assertRedirect(route('production.cutting_jobs.show', $job));

        $this->assertDatabaseHas('inventory_mutations', [
            'source_type' => JournalService::SRC_CUTTING_QC_SEWING_SUPPLY,
            'source_id' => $job->id,
            'item_id' => $supply->id,
            'qty_change' => -2,
        ]);
        $this->assertDatabaseHas('journals', [
            'source_type' => JournalService::SRC_CUTTING_QC_SEWING_SUPPLY,
            'source_id' => $job->id,
            'voided_at' => null,
        ]);
        $this->assertSame(0, StandaloneAccountingJournal::query()
            ->where('source_id', $job->id)
            ->whereIn('source_type', [
                'cutting_wip',
                JournalService::SRC_CUTTING_QC_SEWING_SUPPLY,
            ])
            ->count());
    }

    public function test_auto_bom_lot_allocator_consumes_fifo_across_multiple_lots(): void
    {
        $warehouse = Warehouse::create([
            'code' => 'RM',
            'name' => 'FIFO Test RM',
            'type' => 'internal',
            'active' => true,
        ]);
        $category = ItemCategory::create([
            'code' => 'FIFO-'.uniqid(),
            'name' => 'FIFO Test',
        ]);
        $item = Item::create([
            'code' => 'SUP-FIFO-'.uniqid(),
            'name' => 'FIFO Supply',
            'type' => 'material',
            'item_category_id' => $category->id,
            'unit' => 'pcs',
            'is_stocked' => true,
        ]);
        $firstLot = Lot::create([
            'code' => 'LOT-FIFO-1-'.uniqid(),
            'item_id' => $item->id,
            'initial_qty' => 1,
            'initial_cost' => 100,
            'qty_onhand' => 1,
            'total_cost' => 100,
            'avg_cost' => 100,
            'status' => 'open',
        ]);
        $secondLot = Lot::create([
            'code' => 'LOT-FIFO-2-'.uniqid(),
            'item_id' => $item->id,
            'initial_qty' => 2,
            'initial_cost' => 120,
            'qty_onhand' => 2,
            'total_cost' => 240,
            'avg_cost' => 120,
            'status' => 'open',
        ]);
        foreach ([[$firstLot, '2026-10-01', 1], [$secondLot, '2026-10-02', 2]] as [$lot, $date, $qty]) {
            InventoryMutation::create([
                'date' => $date,
                'warehouse_id' => $warehouse->id,
                'item_id' => $item->id,
                'qty_change' => $qty,
                'direction' => 'in',
                'source_type' => 'fifo_fixture',
                'source_id' => $lot->id,
                'lot_id' => $lot->id,
                'unit_cost' => $lot->avg_cost,
                'total_cost' => $qty * $lot->avg_cost,
            ]);
        }

        $allocations = app(\App\Services\Inventory\LotAllocationService::class)
            ->allocateFifo($warehouse->id, $item->id, 2.5);

        $this->assertSame([$firstLot->id, $secondLot->id], array_column($allocations, 'lot_id'));
        $this->assertEqualsWithDelta(1, (float) $allocations[0]['qty'], 0.000001);
        $this->assertEqualsWithDelta(1.5, (float) $allocations[1]['qty'], 0.000001);
        $this->assertEqualsWithDelta(100, (float) $allocations[0]['unit_cost'], 0.000001);
        $this->assertEqualsWithDelta(120, (float) $allocations[1]['unit_cost'], 0.000001);
    }

    public function test_non_owner_qc_with_auto_bom_lot_shortage_waits_for_owner_correction(): void
    {
        config([
            'accounting.standalone_dual_posting_production_cutting_enabled' => true,
            'accounting.standalone_production_cutting_qc_auto_bom_enabled' => true,
        ]);

        [$owner, $job, $bundle] = $this->makePostedCuttingJob();
        $operator = User::factory()->create([
            'role' => 'operating',
            'employee_id' => $job->operator_id,
            'employee_code' => Employee::query()->findOrFail($job->operator_id)->code,
        ]);
        $rm = Warehouse::create([
            'code' => 'RM',
            'name' => 'QC Pending Review RM',
            'type' => 'internal',
            'active' => true,
        ]);
        $supply = Item::create([
            'code' => 'SUP-PENDING-'.uniqid(),
            'name' => 'Pending Review Supply',
            'type' => 'material',
            'unit' => 'pcs',
            'is_stocked' => true,
        ]);
        $bom = ItemBom::create([
            'item_id' => $bundle->finished_item_id,
            'name' => 'Pending Review BOM',
            'active' => true,
        ]);
        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $supply->id,
            'usage_stage' => ItemBomLine::STAGE_SEWING_SUPPLY,
            'qty' => 0.2,
            'uom' => 'pcs',
            'scrap_pct' => 0,
            'is_optional' => false,
            'sort_order' => 1,
        ]);
        InventoryStock::create([
            'warehouse_id' => $rm->id,
            'item_id' => $supply->id,
            'qty' => 1,
            'allocated_qty' => 0,
        ]);
        $supplyLot = Lot::create([
            'code' => 'LOT-PENDING-'.uniqid(),
            'item_id' => $supply->id,
            'initial_qty' => 1,
            'initial_cost' => 100,
            'qty_onhand' => 1,
            'total_cost' => 100,
            'avg_cost' => 100,
            'status' => 'open',
        ]);
        InventoryMutation::create([
            'date' => '2026-10-27',
            'warehouse_id' => $rm->id,
            'item_id' => $supply->id,
            'qty_change' => 1,
            'direction' => 'in',
            'source_type' => 'pending_review_supply_fixture',
            'source_id' => $job->id,
            'lot_id' => $supplyLot->id,
            'unit_cost' => 100,
            'total_cost' => 100,
        ]);

        $operatorPayload = [
            'qc_date' => '2026-10-27',
            'results' => [[
                'cutting_job_bundle_id' => $bundle->id,
                'qty_ok' => 10,
                'qty_reject' => 0,
            ]],
        ];

        $preview = app(\App\Services\Production\QcService::class)
            ->previewCuttingQcSewingSupplyLots($job, $operatorPayload['results']);
        $this->assertFalse($preview['ready']);
        $this->assertSame($supply->code.' — '.$supply->name, $preview['detail']['item']);
        $this->assertSame('2 pcs', $preview['detail']['required']);
        $this->assertSame('1 pcs', $preview['detail']['available']);
        $this->assertSame('1 pcs', $preview['detail']['shortage']);
        $this->assertSame($supply->id, $preview['detail']['item_id']);
        $this->assertEqualsWithDelta(1, $preview['detail']['shortage_qty'], 0.000001);

        $operatorResponse = $this->actingAs($operator)
            ->put(route('production.qc.cutting.update', $job), $operatorPayload);
        $operatorResponse
            ->assertRedirect(route('production.qc.cutting.edit', $job))
            ->assertSessionHas('warning', fn (string $warning): bool => str_contains($warning, 'Menunggu Koreksi Owner'));

        $this->assertDatabaseHas('cutting_jobs', [
            'id' => $job->id,
            'status' => 'qc_pending_owner_review',
        ]);
        $this->assertDatabaseHas('cutting_job_bundles', [
            'id' => $bundle->id,
            'status' => 'pending_owner_review',
            'qty_qc_ok' => 10,
            'qty_qc_reject' => 0,
        ]);
        $this->assertDatabaseHas('qc_results', [
            'cutting_job_id' => $job->id,
            'cutting_job_bundle_id' => $bundle->id,
            'status' => 'pending_owner_review',
        ]);
        $this->assertSame(0, InventoryMutation::query()
            ->where('source_type', 'cutting_wip')
            ->where('source_id', $job->id)
            ->count());
        $this->assertSame(0, InventoryMutation::query()
            ->where('source_type', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY)
            ->where('source_id', $job->id)
            ->count());

        $this->actingAs($owner)
            ->get(route('production.qc.cutting.edit', $job))
            ->assertOk()
            ->assertSee('Menunggu tindakan Owner')
            ->assertSee($supply->code.' — '.$supply->name)
            ->assertSee('Koreksi Persediaan')
            ->assertDontSee('Koreksi QC')
            ->assertSee('Buat PO')
            ->assertSee('Auto BOM kurang stok lot — koreksi persediaan')
            ->assertSee(route('production.qc.cutting.edit', $job), false);

        $this->actingAs($owner)
            ->get(route('purchasing.purchase_orders.create', [
                'item_id' => $supply->id,
                'qty' => 1,
                'auto_bom_shortage' => 1,
            ]))
            ->assertOk()
            ->assertSee($supply->code)
            ->assertSee('name="lines[0][qty]"', false)
            ->assertSee('value="1"', false)
            ->assertSee('name="auto_bom_shortage" value="1"', false);

        config(['purchasing.po_approval_enabled' => true]);
        $supplier = \App\Models\Supplier::create([
            'code' => 'SUP-QC-'.uniqid(),
            'name' => 'Supplier QC Auto BOM',
            'type' => 'supplier',
            'active' => true,
        ]);
        $paymentMethod = \App\Models\PaymentMethod::create([
            'code' => 'TEMPO-QC-'.uniqid(),
            'name' => 'Tempo QC Auto BOM',
            'mode' => 'credit',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('purchasing.purchase_orders.store'), [
                'date' => '2026-11-04',
                'supplier_id' => $supplier->id,
                'payment_method_id' => $paymentMethod->id,
                'order_type' => 'material',
                'tax_percent' => '0',
                'discount' => '0',
                'shipping_cost' => '0',
                'auto_bom_shortage' => 1,
                'lines' => [[
                    'item_id' => $supply->id,
                    'qty' => '1',
                    'purchase_unit' => 'pcs',
                    'stock_unit' => 'pcs',
                    'conversion_factor' => 1,
                    'unit_price' => '100',
                    'discount' => '0',
                ]],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $supplier->id,
            'status' => 'approved',
            'approved_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->put(route('production.qc.cutting.update', $job), $operatorPayload)
            ->assertRedirect()
            ->assertSessionHas('qc_auto_bom_shortage', fn (array $detail): bool =>
                $detail['item'] === $supply->code.' — '.$supply->name
                && $detail['required'] === '2 pcs'
                && $detail['available'] === '1 pcs'
                && $detail['shortage'] === '1 pcs'
            )
            ->assertSessionMissing('error');

        $ownerPayload = array_replace_recursive($operatorPayload, [
            'results' => [[
                'qty_ok' => 5,
                'qty_reject' => 5,
            ]],
        ]);

        $ownerResponse = $this->actingAs($owner)
            ->put(route('production.qc.cutting.update', $job), $ownerPayload);
        $ownerResponse
            ->assertRedirect(route('production.cutting_jobs.show', $job));

        $this->assertDatabaseHas('cutting_jobs', [
            'id' => $job->id,
            'status' => 'qc_done',
        ]);
        $this->assertDatabaseHas('qc_results', [
            'cutting_job_id' => $job->id,
            'cutting_job_bundle_id' => $bundle->id,
            'status' => 'qc_mixed',
            'qty_ok' => 5,
            'qty_reject' => 5,
        ]);
        $this->assertEqualsWithDelta(-1, (float) InventoryMutation::query()
            ->where('source_type', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY)
            ->where('source_id', $job->id)
            ->sum('qty_change'), 0.000001);
    }

    public function test_auto_bom_lot_allocator_blocks_shortage_and_untraceable_stock(): void
    {
        $warehouse = Warehouse::create([
            'code' => 'RM',
            'name' => 'Shortage Test RM',
            'type' => 'internal',
            'active' => true,
        ]);
        $item = Item::create([
            'code' => 'SUP-SHORT-'.uniqid(),
            'name' => 'Shortage Supply',
            'type' => 'material',
            'unit' => 'pcs',
            'is_stocked' => true,
        ]);
        $lot = Lot::create([
            'code' => 'LOT-SHORT-'.uniqid(),
            'item_id' => $item->id,
            'initial_qty' => 1,
            'initial_cost' => 100,
            'qty_onhand' => 1,
            'total_cost' => 100,
            'avg_cost' => 100,
            'status' => 'open',
        ]);
        InventoryMutation::create([
            'date' => '2026-10-01',
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'qty_change' => 1,
            'direction' => 'in',
            'source_type' => 'shortage_fixture',
            'source_id' => $lot->id,
            'lot_id' => $lot->id,
            'unit_cost' => 100,
            'total_cost' => 100,
        ]);

        $allocator = app(\App\Services\Inventory\LotAllocationService::class);
        try {
            $allocator->allocateFifo($warehouse->id, $item->id, 2);
            $this->fail('Allocator seharusnya menghentikan Auto BOM saat saldo lot kurang.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();

            foreach ([
                'Auto BOM belum dapat diposting karena stok kelengkapan kurang.',
                'Item: '.$item->code.' — '.$item->name,
                'Gudang: '.$warehouse->code.' — '.$warehouse->name,
                'Kebutuhan QC: 2 pcs',
                'Saldo lot yang dapat dipakai: 1 pcs',
                'Kekurangan: 1 pcs',
                'Auto BOM tidak diposting; stok dan jurnal tidak berubah.',
            ] as $expected) {
                $this->assertStringContainsString($expected, $message);
            }
        }
    }

    /** @return array{0: User, 1: CuttingJob, 2: \App\Models\CuttingJobBundle} */
    private function makePostedCuttingJob(): array
    {
        $ownerEmployee = Employee::create([
            'code' => 'EMP-QC-PILOT-OWNER-'.uniqid(),
            'name' => 'QC Pilot Owner',
            'role' => 'owner',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        $operator = Employee::create([
            'code' => 'EMP-QC-PILOT-OP-'.uniqid(),
            'name' => 'QC Pilot Operator',
            'role' => 'cutting',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => $ownerEmployee->code,
            'employee_id' => $ownerEmployee->id,
        ]);
        foreach ([
            ['1201', 'Raw', 'asset'],
            ['1202', 'WIP', 'asset'],
            ['1204', 'Reject', 'asset'],
            ['2102', 'Production payroll', 'liability'],
        ] as [$code, $name, $type]) {
            DB::table('accounts')->updateOrInsert(['code' => $code], [
                'name' => $name,
                'type' => $type,
                'is_cash' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $rm = Warehouse::create([
            'code' => 'RM-QC-PILOT-'.uniqid(),
            'name' => 'QC Pilot Raw Material',
            'type' => 'internal',
            'active' => true,
        ]);
        $wip = Warehouse::create([
            'code' => 'WIP-CUT',
            'name' => 'QC Pilot WIP Cutting',
            'type' => 'wip',
            'active' => true,
        ]);
        $reject = Warehouse::create([
            'code' => 'REJ-CUT',
            'name' => 'QC Pilot Reject Cutting',
            'type' => 'reject',
            'active' => true,
        ]);
        $category = ItemCategory::create([
            'code' => 'QC-PILOT-'.uniqid(),
            'name' => 'QC Pilot',
        ]);
        $fabric = Item::create([
            'code' => 'FAB-QC-PILOT-'.uniqid(),
            'name' => 'QC Pilot Fabric',
            'type' => 'raw_material',
            'item_category_id' => $category->id,
        ]);
        $finished = Item::create([
            'code' => 'FG-QC-PILOT-'.uniqid(),
            'name' => 'QC Pilot Finished Good',
            'type' => 'finished_good',
            'item_category_id' => $category->id,
        ]);
        $lot = Lot::create([
            'code' => 'LOT-QC-PILOT-'.uniqid(),
            'item_id' => $fabric->id,
            'initial_qty' => 10,
            'initial_cost' => 100,
            'qty_onhand' => 10,
            'total_cost' => 1000,
            'avg_cost' => 100,
            'status' => 'open',
        ]);
        InventoryStock::create([
            'warehouse_id' => $rm->id,
            'item_id' => $fabric->id,
            'qty' => 10,
            'allocated_qty' => 0,
        ]);
        InventoryMutation::create([
            'date' => '2026-10-27',
            'warehouse_id' => $rm->id,
            'item_id' => $fabric->id,
            'qty_change' => 10,
            'direction' => 'in',
            'source_type' => 'qc_pilot_fixture',
            'source_id' => $lot->id,
            'lot_id' => $lot->id,
            'unit_cost' => 100,
            'total_cost' => 1000,
        ]);
        PieceRate::create([
            'module' => 'cutting',
            'employee_id' => $operator->id,
            'item_id' => $finished->id,
            'rate_per_pcs' => 100,
            'effective_from' => '2026-01-01',
        ]);

        $job = app(CuttingService::class)->create([
            'code' => 'CUT-QC-PILOT-'.uniqid(),
            'date' => '2026-10-27',
            'warehouse_id' => $rm->id,
            'lot_id' => $lot->id,
            'fabric_item_id' => $fabric->id,
            'operator_id' => $operator->id,
            'bundles' => [[
                'bundle_no' => 1,
                'lot_id' => $lot->id,
                'finished_item_id' => $finished->id,
                'qty_pcs' => 10,
                'qty_used_fabric' => 1,
                'operator_id' => $operator->id,
            ]],
        ]);
        CuttingJobLot::create([
            'cutting_job_id' => $job->id,
            'lot_id' => $lot->id,
            'planned_fabric_qty' => 1,
            'used_fabric_qty' => 0,
        ]);
        app(CuttingService::class)->reconsumeFabricFromLots($job->fresh());

        $postedJob = $job->fresh(['bundles']);
        app(JournalService::class)->postCuttingJob($postedJob);
        app(JournalService::class)->postCuttingJobWage($postedJob);
        app(StandaloneProductionPostingService::class)->mirrorCuttingJob($job->id);

        $this->assertNotSame($rm->id, $wip->id);
        $this->assertNotSame($rm->id, $reject->id);

        return [$owner, $job->fresh(), $job->bundles()->firstOrFail()];
    }

    private function resetWipWarehouseCache(): void
    {
        $property = new \ReflectionProperty(CuttingJobBundle::class, 'wipCutWarehouseIdCache');
        $property->setValue(null);
    }
}
