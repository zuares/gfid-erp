<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Journal;
use App\Models\StandaloneAccountingJournal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PilotCuttingMirrorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_pilot_command_is_read_only_and_reports_no_candidate(): void
    {
        $initialJournalCount = StandaloneAccountingJournal::query()->count();

        $this->artisan('production:pilot-cutting', ['--json' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('"status": "NO_CANDIDATE"');

        $this->assertSame($initialJournalCount, StandaloneAccountingJournal::query()->count());
    }

    public function test_apply_is_blocked_when_cutting_feature_flag_is_off(): void
    {
        $this->artisan('production:pilot-cutting', [
            'jobId' => 99101,
            '--apply' => true,
            '--confirm' => 'PILOT-CUTTING',
            '--json' => true,
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('"status": "BLOCKED"');

        $this->assertSame(0, StandaloneAccountingJournal::query()->where('source_id', 99101)->count());
    }

    public function test_apply_requires_explicit_confirmation_when_flag_is_on(): void
    {
        config(['accounting.standalone_dual_posting_production_cutting_enabled' => true]);

        $this->artisan('production:pilot-cutting', [
            'jobId' => 99102,
            '--apply' => true,
            '--json' => true,
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('"status": "BLOCKED"');

        $this->assertSame(0, StandaloneAccountingJournal::query()->where('source_id', 99102)->count());
    }

    public function test_apply_mirrors_one_ready_cutting_job_and_is_idempotent(): void
    {
        config(['accounting.standalone_dual_posting_production_cutting_enabled' => true]);

        $jobId = $this->createCuttingJob(99103);
        $this->legacyJournal($jobId);

        $this->artisan('production:pilot-cutting', [
            'jobId' => $jobId,
            '--json' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('"status": "READY"');

        $this->artisan('production:pilot-cutting', [
            'jobId' => $jobId,
            '--apply' => true,
            '--confirm' => 'PILOT-CUTTING',
            '--json' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('"status": "APPLIED"');

        $this->artisan('production:pilot-cutting', [
            'jobId' => $jobId,
            '--apply' => true,
            '--confirm' => 'PILOT-CUTTING',
            '--json' => true,
        ])->assertExitCode(0);

        $this->assertSame(1, StandaloneAccountingJournal::query()
            ->where('source_type', 'cutting_job')
            ->where('source_id', $jobId)
            ->whereNull('voided_at')
            ->count());
    }

    private function createCuttingJob(int $sourceId): int
    {
        $warehouseId = (int) DB::table('warehouses')->insertGetId([
            'code' => 'W-PILOT-'.$sourceId,
            'name' => 'Pilot warehouse '.$sourceId,
            'type' => 'internal',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $itemId = (int) DB::table('items')->insertGetId([
            'code' => 'ITEM-PILOT-'.$sourceId,
            'name' => 'Pilot item '.$sourceId,
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
        $lotId = (int) DB::table('lots')->insertGetId([
            'code' => 'LOT-PILOT-'.$sourceId,
            'item_id' => $itemId,
            'initial_qty' => 10,
            'initial_cost' => 1000,
            'qty_onhand' => 10,
            'total_cost' => 1000,
            'avg_cost' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $employeeId = (int) DB::table('employees')->insertGetId([
            'code' => 'EMP-PILOT-'.$sourceId,
            'name' => 'Pilot operator '.$sourceId,
            'role' => 'cutting',
            'payment_type' => 'variable',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('cutting_jobs')->insertGetId([
            'code' => 'CUT-PILOT-'.$sourceId,
            'date' => '2026-10-27',
            'warehouse_id' => $warehouseId,
            'lot_id' => $lotId,
            'fabric_item_id' => $itemId,
            'operator_id' => $employeeId,
            'total_bundles' => 1,
            'total_qty_pcs' => 10,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function legacyJournal(int $sourceId): void
    {
        $journal = Journal::create([
            'date' => '2026-10-27',
            'description' => 'Pilot cutting journal',
            'source_type' => 'cutting_job',
            'source_id' => $sourceId,
            'posted_at' => now(),
        ]);
        $raw = Account::firstOrCreate(['code' => '1201'], [
            'name' => 'Pilot raw material',
            'type' => 'asset',
            'is_active' => true,
        ]);
        $wip = Account::firstOrCreate(['code' => '1202'], [
            'name' => 'Pilot WIP',
            'type' => 'asset',
            'is_active' => true,
        ]);
        $journal->lines()->createMany([
            ['account_id' => $wip->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $raw->id, 'debit' => 0, 'credit' => 100],
        ]);
    }
}
