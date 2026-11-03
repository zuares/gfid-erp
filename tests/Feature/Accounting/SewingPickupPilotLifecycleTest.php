<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Journal;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingJournal;
use App\Services\Accounting\ProductionAccountingAuditService;
use App\Services\Accounting\StandaloneProductionPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SewingPickupPilotLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sewing_pickup_mirror_covers_transfer_wage_and_supply_idempotently(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_pickup_enabled' => true]);
        [$pickupId, $lineId] = $this->sewingPickupFixture();

        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_PICKUP, $pickupId, [
            ['code' => '1202', 'debit' => 100, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 100],
        ]);
        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_WAGE, $lineId, [
            ['code' => '1202', 'debit' => 30, 'credit' => 0],
            ['code' => '2102', 'debit' => 0, 'credit' => 30],
        ]);
        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_SUPPLY, $pickupId, [
            ['code' => '1202', 'debit' => 20, 'credit' => 0],
            ['code' => '1201', 'debit' => 0, 'credit' => 20],
        ]);

        $service = app(StandaloneProductionPostingService::class);
        $first = $service->mirrorSewingPickup($pickupId);
        $second = $service->mirrorSewingPickup($pickupId);

        $this->assertCount(3, $first);
        $this->assertCount(3, $second);
        $this->assertSame(3, StandaloneAccountingJournal::query()->whereIn('source_id', [$pickupId, $lineId])->count());
        foreach ($first as $journal) {
            $this->assertEqualsWithDelta((float) $journal->lines->sum('debit'), (float) $journal->lines->sum('credit'), 0.01);
            $this->assertNotNull($journal->posted_at);
        }

        $pickupMirror = collect($first)->first(
            fn (StandaloneAccountingJournal $journal): bool => $journal->source_type === StandaloneProductionPostingService::SOURCE_SEWING_PICKUP,
        );
        $this->assertNotNull($pickupMirror);
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $pickupMirror->id,
            'account_id' => StandaloneAccountingAccount::where('code', '1-151-02')->value('id'),
            'debit' => 100,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $pickupMirror->id,
            'account_id' => StandaloneAccountingAccount::where('code', '1-151-01')->value('id'),
            'debit' => 0,
            'credit' => 100,
        ]);

        config(['accounting.standalone_production_audit_required' => true]);
        $audit = app(ProductionAccountingAuditService::class)->report(
            onlyStages: ['sewing_pickup'],
        );
        $row = $audit['rows'][0];
        $this->assertSame('PASS', $audit['summary']['status']);
        $this->assertSame(3, $row['legacy_journal_count']);
        $this->assertSame(3, $row['standalone_journal_count']);
        $this->assertSame(0, $row['standalone_missing_count']);
    }

    public function test_sewing_pickup_void_creates_audit_reversals_and_repost_is_new_trace(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_pickup_enabled' => true]);
        [$pickupId, $lineId] = $this->sewingPickupFixture();
        $legacy = [
            $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_PICKUP, $pickupId, [
                ['code' => '1202', 'debit' => 100, 'credit' => 0], ['code' => '1202', 'debit' => 0, 'credit' => 100],
            ]),
            $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_WAGE, $lineId, [
                ['code' => '1202', 'debit' => 30, 'credit' => 0], ['code' => '2102', 'debit' => 0, 'credit' => 30],
            ]),
            $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_SUPPLY, $pickupId, [
                ['code' => '1202', 'debit' => 20, 'credit' => 0], ['code' => '1201', 'debit' => 0, 'credit' => 20],
            ]),
        ];

        $service = app(StandaloneProductionPostingService::class);
        $mirrors = $service->mirrorSewingPickup($pickupId);
        $service->voidSewingPickup($pickupId);
        $service->voidSewingPickup($pickupId);

        $this->assertSame(0, StandaloneAccountingJournal::query()->where('source_id', $pickupId)->whereNull('voided_at')->count());
        $this->assertSame(6, StandaloneAccountingJournal::query()->whereIn('source_id', [$pickupId, $lineId])->count());
        $this->assertNotNull($mirrors[0]->fresh()->voided_at);
        $this->assertSame(1, StandaloneAccountingJournal::query()
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_PICKUP.'_reversal_'.$mirrors[0]->id)
            ->where('voided_at', '!=', null)
            ->count());

        foreach ($legacy as $journal) {
            $journal->forceFill(['voided_at' => now()])->save();
        }
        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_PICKUP, $pickupId, [
            ['code' => '1202', 'debit' => 60, 'credit' => 0], ['code' => '1202', 'debit' => 0, 'credit' => 60],
        ]);
        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_WAGE, $lineId, [
            ['code' => '1202', 'debit' => 15, 'credit' => 0], ['code' => '2102', 'debit' => 0, 'credit' => 15],
        ]);
        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_SUPPLY, $pickupId, [
            ['code' => '1202', 'debit' => 10, 'credit' => 0], ['code' => '1201', 'debit' => 0, 'credit' => 10],
        ]);

        $repost = $service->mirrorSewingPickup($pickupId);
        $this->assertCount(3, $repost);
        $this->assertSame('App\\Models\\SewingPickup_repost_1', $repost[0]->source_type);
    }

    public function test_sewing_pickup_missing_mapping_blocks_without_creating_mirror(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_pickup_enabled' => true]);
        [$pickupId] = $this->sewingPickupFixture();
        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_PICKUP, $pickupId, [
            ['code' => '9999', 'debit' => 10, 'credit' => 0], ['code' => '1202', 'debit' => 0, 'credit' => 10],
        ]);

        try {
            app(StandaloneProductionPostingService::class)->mirrorSewingPickup($pickupId);
            $this->fail('Mirror seharusnya gagal ketika mapping akun tidak tersedia.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('9999', (string) $exception->getMessage());
        }

        $this->assertDatabaseMissing('standalone_accounting_journals', ['source_id' => $pickupId]);
    }

    public function test_fully_voided_pickup_material_is_audit_only_in_both_ledgers(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_pickup_enabled' => true]);
        [$pickupId, $lineId] = $this->sewingPickupFixture();

        $material = $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_PICKUP, $pickupId, [
            ['code' => '1202', 'debit' => 100, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 100],
        ]);
        $this->legacyJournal('sewing_pickup_line_void', $lineId, [
            ['code' => '1202', 'debit' => 100, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 100],
        ]);

        $service = app(StandaloneProductionPostingService::class);
        $service->mirrorSewingPickup($pickupId);
        $service->mirrorSewingPickupLine($lineId);
        $service->voidFullyVoidedSewingPickupMaterial($pickupId, [$lineId]);

        $journalService = app(\App\Services\Accounting\JournalService::class);
        $journalService->voidBySourceFamily(StandaloneProductionPostingService::SOURCE_SEWING_PICKUP, $pickupId, 'test');
        $journalService->voidBySource('sewing_pickup_line_void', $lineId, 'test');

        $this->assertSame(0, StandaloneAccountingJournal::query()
            ->whereIn('source_id', [$pickupId, $lineId])
            ->whereNull('voided_at')
            ->count());

        $this->assertNotNull(StandaloneAccountingJournal::query()
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_PICKUP)
            ->where('source_id', $pickupId)
            ->firstOrFail()
            ->voided_at);

        $this->assertSame(0, Journal::query()
            ->whereIn('source_id', [$pickupId, $lineId])
            ->whereNull('voided_at')
            ->count());

        $this->assertNotNull($material->fresh()->voided_at);
        $this->assertSame(0, Journal::query()
            ->whereIn('source_id', [$pickupId, $lineId])
            ->whereNull('voided_at')
            ->count());
    }

    public function test_audit_includes_partial_line_void_reversal_and_excludes_voided_wage(): void
    {
        [$pickupId, $voidLineId] = $this->sewingPickupFixture();
        $pickup = DB::table('sewing_pickups')->where('id', $pickupId)->first();
        $line = DB::table('sewing_pickup_lines')->where('id', $voidLineId)->first();

        DB::table('sewing_pickup_lines')->where('id', $voidLineId)->update([
            'qty_bundle' => 1,
            'wage_per_pcs' => 30,
            'status' => 'void',
            'voided_at' => now(),
            'updated_at' => now(),
        ]);
        $activeLineId = DB::table('sewing_pickup_lines')->insertGetId([
            'sewing_pickup_id' => $pickupId,
            'cutting_job_bundle_id' => $line->cutting_job_bundle_id,
            'finished_item_id' => $line->finished_item_id,
            'qty_bundle' => 1,
            'qty_returned_ok' => 0,
            'qty_returned_reject' => 0,
            'wage_per_pcs' => 30,
            'status' => 'in_progress',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_mutations')->insert([
            'date' => '2026-10-27',
            'warehouse_id' => $pickup->warehouse_id,
            'item_id' => $line->finished_item_id,
            'qty_change' => 2,
            'direction' => 'in',
            'source_type' => StandaloneProductionPostingService::SOURCE_SEWING_PICKUP,
            'source_id' => $pickupId,
            'unit_cost' => 180,
            'total_cost' => 360,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_PICKUP, $pickupId, [
            ['code' => '1202', 'debit' => 200, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 200],
        ]);
        $this->legacyJournal('sewing_pickup_line_void', $voidLineId, [
            ['code' => '1202', 'debit' => 100, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 100],
        ]);
        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_WAGE, $activeLineId, [
            ['code' => '1202', 'debit' => 30, 'credit' => 0],
            ['code' => '2102', 'debit' => 0, 'credit' => 30],
        ]);
        $voidedWage = $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_WAGE, $voidLineId, [
            ['code' => '1202', 'debit' => 30, 'credit' => 0],
            ['code' => '2102', 'debit' => 0, 'credit' => 30],
        ]);
        $voidedWage->forceFill(['voided_at' => now()])->save();

        $report = app(ProductionAccountingAuditService::class)->report(
            onlyStages: ['sewing_pickup'],
        );

        $row = collect($report['details'])->firstWhere('source_id', $pickupId);
        $this->assertNotNull($row);
        $this->assertSame(0.0, (float) $row['legacy_variance']);
        $this->assertSame(330.0, (float) $row['movement_basis_value']);
    }

    /** @return array{0:int,1:int} */
    private function sewingPickupFixture(): array
    {
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'WIP-SEW-TEST', 'name' => 'WIP Sewing Test', 'type' => 'wip', 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $employeeId = DB::table('employees')->insertGetId([
            'code' => 'EMP-SEW-TEST', 'name' => 'Sewing Test', 'role' => 'sewing', 'payment_type' => 'variable',
            'weekly_fixed_salary' => 0, 'default_piece_rate' => 0, 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $itemId = DB::table('items')->insertGetId([
            'code' => 'ITEM-SEW-TEST', 'name' => 'Sewing Test Item', 'unit' => 'pcs', 'type' => 'finished',
            'last_purchase_price' => 0, 'hpp' => 0, 'active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $lotId = DB::table('lots')->insertGetId([
            'code' => 'LOT-SEW-TEST', 'item_id' => $itemId, 'initial_qty' => 1, 'initial_cost' => 0,
            'qty_onhand' => 1, 'total_cost' => 0, 'avg_cost' => 0, 'status' => 'open',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $jobId = DB::table('cutting_jobs')->insertGetId([
            'code' => 'CUT-SEW-TEST', 'date' => '2026-10-27', 'warehouse_id' => $warehouseId,
            'lot_id' => $lotId, 'fabric_item_id' => $itemId, 'operator_id' => $employeeId,
            'total_bundles' => 1, 'total_qty_pcs' => 10, 'status' => 'cut',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $bundleId = DB::table('cutting_job_bundles')->insertGetId([
            'cutting_job_id' => $jobId, 'bundle_code' => 'BND-SEW-TEST', 'bundle_no' => 1, 'lot_id' => $lotId,
            'finished_item_id' => $itemId, 'qty_pcs' => 10, 'qty_used_fabric' => 1, 'operator_id' => $employeeId,
            'status' => 'cut', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $pickupId = DB::table('sewing_pickups')->insertGetId([
            'code' => 'SWP-SEW-TEST', 'date' => '2026-10-27', 'warehouse_id' => $warehouseId,
            'operator_id' => $employeeId, 'status' => 'posted', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $lineId = DB::table('sewing_pickup_lines')->insertGetId([
            'sewing_pickup_id' => $pickupId, 'cutting_job_bundle_id' => $bundleId, 'finished_item_id' => $itemId,
            'qty_bundle' => 10, 'qty_returned_ok' => 0, 'qty_returned_reject' => 0,
            'wage_per_pcs' => 3, 'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return [(int) $pickupId, (int) $lineId];
    }

    /** @param array<int, array{code:string,debit:int|float,credit:int|float}> $lines */
    private function legacyJournal(string $sourceType, int $sourceId, array $lines): Journal
    {
        $journal = Journal::create([
            'date' => '2026-10-27', 'description' => 'Test '.$sourceType.' #'.$sourceId,
            'source_type' => $sourceType, 'source_id' => $sourceId, 'posted_at' => now(),
        ]);
        $journal->lines()->createMany(array_map(function (array $line): array {
            return [
                'account_id' => Account::firstOrCreate(
                    ['code' => $line['code']],
                    ['name' => 'Test '.$line['code'], 'type' => 'asset', 'is_active' => true],
                )->id,
                'debit' => $line['debit'], 'credit' => $line['credit'],
            ];
        }, $lines));

        return $journal->load('lines.account');
    }
}
