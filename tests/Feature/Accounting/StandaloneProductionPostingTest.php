<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Journal;
use App\Models\StandaloneAccountingJournal;
use App\Services\Accounting\StandaloneProductionPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StandaloneProductionPostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cutting_mirror_is_balanced_and_idempotent(): void
    {
        config(['accounting.standalone_dual_posting_production_cutting_enabled' => true]);

        $this->legacyJournal('cutting_job', 9001, [
            ['code' => '1202', 'debit' => 100, 'credit' => 0],
            ['code' => '1201', 'debit' => 0, 'credit' => 100],
        ]);
        $this->legacyJournal('cutting_job_wage', 9001, [
            ['code' => '1202', 'debit' => 20, 'credit' => 0],
            ['code' => '2102', 'debit' => 0, 'credit' => 20],
        ]);

        $service = app(StandaloneProductionPostingService::class);
        $first = $service->mirrorCuttingJob(9001);
        $second = $service->mirrorCuttingJob(9001);

        $this->assertCount(2, $first);
        $this->assertCount(2, $second);
        $this->assertSame(2, StandaloneAccountingJournal::query()
            ->whereIn('source_type', ['cutting_job', 'cutting_job_wage'])
            ->where('source_id', 9001)
            ->count());

        $this->assertEqualsWithDelta(100, (float) $first[0]->lines->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(100, (float) $first[0]->lines->sum('credit'), 0.01);
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $first[0]->id,
            'account_id' => $this->standaloneAccountId('1-151-01'),
            'debit' => 100,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $first[0]->id,
            'account_id' => $this->standaloneAccountId('1-150'),
            'debit' => 0,
            'credit' => 100,
        ]);
    }

    public function test_void_creates_voided_reversal_and_repost_uses_a_new_trace_key(): void
    {
        config(['accounting.standalone_dual_posting_production_cutting_enabled' => true]);

        $legacy = $this->legacyJournal('cutting_job', 9002, [
            ['code' => '1202', 'debit' => 50, 'credit' => 0],
            ['code' => '1201', 'debit' => 0, 'credit' => 50],
        ]);
        $service = app(StandaloneProductionPostingService::class);
        $mirrors = $service->mirrorCuttingJob(9002);
        $service->voidCuttingJob(9002);
        $service->voidCuttingJob(9002);

        $this->assertNotNull($mirrors[0]->fresh()->voided_at);
        $this->assertSame(0, StandaloneAccountingJournal::query()
            ->where('source_id', 9002)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(2, StandaloneAccountingJournal::query()
            ->where('source_id', 9002)
            ->count());

        $reversal = StandaloneAccountingJournal::query()
            ->where('source_id', 9002)
            ->where('source_type', 'cutting_job_reversal_'.$mirrors[0]->id)
            ->with('lines')
            ->firstOrFail();
        $this->assertNotNull($reversal->voided_at);
        $this->assertEqualsWithDelta(50, (float) $reversal->lines->sum('credit'), 0.01);
        $this->assertEqualsWithDelta(50, (float) $reversal->lines->sum('debit'), 0.01);

        $legacy->forceFill(['voided_at' => now()])->save();
        $this->legacyJournal('cutting_job', 9002, [
            ['code' => '1202', 'debit' => 60, 'credit' => 0],
            ['code' => '1201', 'debit' => 0, 'credit' => 60],
        ]);

        $repost = $service->mirrorCuttingJob(9002);
        $this->assertCount(1, $repost);
        $this->assertSame('cutting_job_repost_1', $repost[0]->source_type);
        $this->assertNotNull($repost[0]->fresh()->posted_at);
        $this->assertNull($repost[0]->fresh()->voided_at);
    }

    public function test_existing_mirror_is_voided_even_after_feature_flag_is_turned_off(): void
    {
        config(['accounting.standalone_dual_posting_production_cutting_enabled' => true]);

        $this->legacyJournal('cutting_job', 9004, [
            ['code' => '1202', 'debit' => 75, 'credit' => 0],
            ['code' => '1201', 'debit' => 0, 'credit' => 75],
        ]);

        $service = app(StandaloneProductionPostingService::class);
        $mirrors = $service->mirrorCuttingJob(9004);

        config(['accounting.standalone_dual_posting_production_cutting_enabled' => false]);
        $service->voidCuttingJob(9004);

        $this->assertNotNull($mirrors[0]->fresh()->voided_at);
        $this->assertSame(0, StandaloneAccountingJournal::query()
            ->where('source_id', 9004)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(2, StandaloneAccountingJournal::query()
            ->where('source_id', 9004)
            ->count());
    }

    public function test_missing_mapping_blocks_cutting_mirror_without_creating_new_journal(): void
    {
        config(['accounting.standalone_dual_posting_production_cutting_enabled' => true]);

        $this->legacyJournal('cutting_job', 9003, [
            ['code' => '9999', 'debit' => 10, 'credit' => 0],
            ['code' => '1201', 'debit' => 0, 'credit' => 10],
        ]);

        try {
            app(StandaloneProductionPostingService::class)->mirrorCuttingJob(9003);
            $this->fail('Mirror seharusnya gagal ketika mapping akun tidak tersedia.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('9999', (string) $exception->getMessage());
        }

        $this->assertDatabaseMissing('standalone_accounting_journals', [
            'source_id' => 9003,
        ]);
    }

    public function test_missing_stage_mapping_blocks_cutting_mirror(): void
    {
        config(['accounting.standalone_dual_posting_production_cutting_enabled' => true]);

        DB::table('standalone_production_account_mappings')
            ->where('source_type', StandaloneProductionPostingService::SOURCE_CUTTING_JOB)
            ->where('legacy_account_code', '1202')
            ->where('line_side', 'debit')
            ->delete();

        $this->legacyJournal('cutting_job', 9005, [
            ['code' => '1202', 'debit' => 10, 'credit' => 0],
            ['code' => '1201', 'debit' => 0, 'credit' => 10],
        ]);

        try {
            app(StandaloneProductionPostingService::class)->mirrorCuttingJob(9005);
            $this->fail('Mirror seharusnya gagal ketika mapping WIP tahap tidak tersedia.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Mapping WIP tahap', (string) $exception->getMessage());
        }

        $this->assertDatabaseMissing('standalone_accounting_journals', ['source_id' => 9005]);
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

        return $journal->load('lines.account');
    }

    private function standaloneAccountId(string $code): int
    {
        return (int) \App\Models\StandaloneAccountingAccount::query()
            ->where('code', $code)
            ->value('id');
    }
}
