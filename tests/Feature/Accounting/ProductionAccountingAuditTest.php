<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Services\Accounting\ProductionAccountingAuditService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductionAccountingAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_detects_missing_legacy_journal_and_optional_mirror_gap(): void
    {
        $sourceId = $this->cuttingMutation(7001, 10, 100);

        $report = app(ProductionAccountingAuditService::class)->report(
            onlyStages: ['cutting_material'],
        );
        $row = $report['rows'][0];

        $this->assertSame('REVIEW', $report['summary']['status']);
        $this->assertSame(1, $row['document_count']);
        $this->assertSame(1, $row['legacy_missing_count']);
        $this->assertSame(1, $row['standalone_missing_count']);
        $this->assertSame($sourceId, $report['details'][0]['source_id']);

        $this->createLegacyJournal('cutting_job', $sourceId, 1000);
        $report = app(ProductionAccountingAuditService::class)->report(
            onlyStages: ['cutting_material'],
        );
        $row = $report['rows'][0];

        $this->assertSame('PASS', $report['summary']['status']);
        $this->assertSame(0, $row['legacy_missing_count']);
        $this->assertSame(0.0, (float) $row['stock_vs_legacy_variance']);
    }

    public function test_audit_can_make_mirror_gap_a_review_condition(): void
    {
        config(['accounting.standalone_production_audit_required' => true]);
        $sourceId = $this->cuttingMutation(7002, 5, 50);
        $this->createLegacyJournal('cutting_job', $sourceId, 250);

        $report = app(ProductionAccountingAuditService::class)->report(
            onlyStages: ['cutting_material'],
        );

        $this->assertSame('REVIEW', $report['summary']['status']);
        $this->assertSame(1, $report['rows'][0]['standalone_missing_count']);
    }

    public function test_cutting_qc_reconciles_output_with_sewing_supply_bom_journal(): void
    {
        config([
            'accounting.standalone_dual_posting_production_cutting_enabled' => false,
            'accounting.standalone_production_audit_required' => false,
        ]);

        $warehouseId = (int) DB::table('warehouses')->insertGetId([
            'code' => 'WIP-AUDIT-QC-BOM',
            'name' => 'WIP Audit QC BOM',
            'type' => 'wip',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $itemId = (int) DB::table('items')->insertGetId([
            'code' => 'ITEM-AUDIT-QC-BOM',
            'name' => 'Item Audit QC BOM',
            'unit' => 'pcs',
            'type' => 'finished',
            'item_role' => 'finished_goods',
            'last_purchase_price' => 0,
            'hpp' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $supplyItemId = (int) DB::table('items')->insertGetId([
            'code' => 'SUPPLY-AUDIT-QC-BOM',
            'name' => 'Supply Audit QC BOM',
            'unit' => 'pcs',
            'type' => 'raw_material',
            'item_role' => 'sewing_supply',
            'last_purchase_price' => 50,
            'hpp' => 50,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_mutations')->insert([
            [
                'date' => '2026-10-27',
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'qty_change' => 40,
                'direction' => 'in',
                'source_type' => 'cutting_wip',
                'source_id' => 7101,
                'unit_cost' => 23480,
                'total_cost' => 939200,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'date' => '2026-10-27',
                'warehouse_id' => $warehouseId,
                'item_id' => $supplyItemId,
                'qty_change' => -2,
                'direction' => 'out',
                'source_type' => 'cutting_qc_sewing_supply',
                'source_id' => 7101,
                'unit_cost' => 50000,
                'total_cost' => -100000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->createLegacyJournal('cutting_wip', 7101, 839200);
        $this->createLegacyJournal('cutting_qc_sewing_supply', 7101, 100000);

        $report = app(ProductionAccountingAuditService::class)->report(
            onlyStages: ['cutting_qc'],
        );
        $row = $report['rows'][0];

        $this->assertSame('PASS', $report['summary']['status']);
        $this->assertSame(939200.0, (float) $row['legacy_debit']);
        $this->assertSame(939200.0, (float) $row['movement_basis_value']);
        $this->assertSame(0.0, (float) $row['stock_vs_legacy_variance']);
    }

    public function test_pre_cutover_mirror_gap_is_reported_as_historical_without_blocking(): void
    {
        config([
            'accounting.standalone_production_audit_required' => true,
            'accounting.standalone_production_audit_cutover_at' => '2026-10-27 22:00:00',
        ]);
        $sourceId = $this->cuttingMutation(7005, 5, 50);
        $this->createLegacyJournal(
            'cutting_job',
            $sourceId,
            250,
            null,
            Carbon::parse('2026-10-27 21:00:00'),
        );

        $report = app(ProductionAccountingAuditService::class)->report(
            onlyStages: ['cutting_material'],
        );

        $this->assertSame('PASS', $report['summary']['status']);
        $this->assertSame(0, $report['rows'][0]['standalone_missing_count']);
        $this->assertSame(1, $report['rows'][0]['standalone_historical_gap_count']);
        $this->assertTrue($report['details'][0]['standalone_historical_gap']);
        $this->assertSame(1, $report['summary']['standalone_historical_gaps']);
    }

    public function test_voided_legacy_journal_is_not_counted_as_active(): void
    {
        $sourceId = $this->cuttingMutation(7003, 2, 20);
        $this->createLegacyJournal('cutting_job', $sourceId, 100, now());

        $report = app(ProductionAccountingAuditService::class)->report(
            onlyStages: ['cutting_material'],
        );
        $row = $report['rows'][0];

        $this->assertSame(0, $row['legacy_active_journal_count']);
        $this->assertSame(1, $row['legacy_voided_journal_count']);
        $this->assertSame(0, $row['legacy_missing_count']);
        $this->assertSame(0.0, (float) $row['legacy_debit']);
        $this->assertSame('PASS', $report['summary']['status']);
    }

    public function test_voided_shipment_movement_is_not_counted_in_active_variance(): void
    {
        $warehouseId = (int) DB::table('warehouses')->insertGetId([
            'code' => 'WH-AUDIT-VOID-7004',
            'name' => 'Warehouse Audit Void 7004',
            'type' => 'internal',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $itemId = (int) DB::table('items')->insertGetId([
            'code' => 'ITEM-AUDIT-VOID-7004',
            'name' => 'Item Audit Void 7004',
            'unit' => 'pcs',
            'type' => 'raw_material',
            'item_role' => 'raw_material',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'last_purchase_price' => 0,
            'hpp' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $shipmentId = DB::table('shipments')->insertGetId([
            'code' => 'SHP-AUDIT-VOID-7004',
            'shipment_type' => 'manual',
            'scan_mode' => 'manual',
            'dispatch_mode' => 'single',
            'date' => '2026-10-27',
            'status' => 'posted',
            'total_qty' => 1,
            'posted_at' => now(),
            'cancelled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_mutations')->insert([
            'date' => '2026-10-27',
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'qty_change' => -1,
            'direction' => 'out',
            'source_type' => 'shipment',
            'source_id' => $shipmentId,
            'unit_cost' => 50,
            'total_cost' => -50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createLegacyJournal('shipment_cogs', $shipmentId, 50, now());

        $report = app(ProductionAccountingAuditService::class)->report(
            onlyStages: ['shipment_cogs'],
        );
        $row = $report['rows'][0];

        $this->assertSame('PASS', $report['summary']['status']);
        $this->assertSame(0.0, (float) $row['stock_vs_legacy_variance']);
        $this->assertSame(0.0, (float) $report['details'][0]['movement_value']);
        $this->assertSame('VOID_ONLY', $report['details'][0]['legacy_status']);
    }

    public function test_audit_command_cutting_alias_expands_to_cutting_stages(): void
    {
        $this->artisan('production:audit-accounting', [
            '--stage' => 'cutting',
            '--json' => true,
        ])
            ->assertExitCode(0);
    }

    public function test_audit_command_rejects_unknown_stage_instead_of_returning_empty_pass(): void
    {
        $this->artisan('production:audit-accounting', [
            '--stage' => 'not-a-stage',
            '--json' => true,
        ])
            ->assertExitCode(1);
    }

    private function cuttingMutation(int $sourceId, float $qty, float $unitCost): int
    {
        $warehouseId = (int) DB::table('warehouses')->insertGetId([
            'code' => 'WIP-AUDIT-'.$sourceId,
            'name' => 'WIP Audit '.$sourceId,
            'type' => 'internal',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $itemId = (int) DB::table('items')->insertGetId([
            'code' => 'ITEM-AUDIT-'.$sourceId,
            'name' => 'Item Audit '.$sourceId,
            'unit' => 'pcs',
            'type' => 'raw_material',
            'item_role' => 'raw_material',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'last_purchase_price' => 0,
            'hpp' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_mutations')->insert([
            'date' => '2026-10-27',
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'qty_change' => -$qty,
            'direction' => 'out',
            'source_type' => 'cutting_job',
            'source_id' => $sourceId,
            'unit_cost' => $unitCost,
            'total_cost' => -($qty * $unitCost),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $sourceId;
    }

    private function createLegacyJournal(string $sourceType, int $sourceId, float $amount, $voidedAt = null, $createdAt = null): void
    {
        $createdAt ??= now();
        $wip = Account::firstOrCreate(['code' => '1202'], [
            'name' => 'WIP Audit',
            'type' => 'asset',
            'is_active' => true,
        ]);
        $raw = Account::firstOrCreate(['code' => '1201'], [
            'name' => 'Raw Audit',
            'type' => 'asset',
            'is_active' => true,
        ]);
        $journal = DB::table('journals')->insertGetId([
            'date' => '2026-10-27',
            'description' => 'Audit production fixture',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'posted_at' => $createdAt,
            'voided_at' => $voidedAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
        DB::table('journal_lines')->insert([
            ['journal_id' => $journal, 'account_id' => $wip->id, 'debit' => $amount, 'credit' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['journal_id' => $journal, 'account_id' => $raw->id, 'debit' => 0, 'credit' => $amount, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
