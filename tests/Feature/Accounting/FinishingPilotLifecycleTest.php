<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Journal;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingJournal;
use App\Services\Accounting\StandaloneProductionPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinishingPilotLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_finishing_mirror_is_balanced_and_idempotent(): void
    {
        config(['accounting.standalone_dual_posting_production_finishing_enabled' => true]);

        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_FINISHING_JOB, 8101, [
            ['code' => '1203', 'debit' => 90, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 90],
        ]);
        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_FINISHING_BOM, 8101, [
            ['code' => '1202', 'debit' => 10, 'credit' => 0],
            ['code' => '1201', 'debit' => 0, 'credit' => 10],
        ]);

        $service = app(StandaloneProductionPostingService::class);
        $first = $service->mirrorFinishingJob(8101);
        $second = $service->mirrorFinishingJob(8101);

        $this->assertCount(2, $first);
        $this->assertCount(2, $second);
        $this->assertSame(2, StandaloneAccountingJournal::query()->where('source_id', 8101)->count());

        $finishingMirror = collect($first)->first(
            fn (StandaloneAccountingJournal $journal): bool => $journal->source_type === StandaloneProductionPostingService::SOURCE_FINISHING_JOB,
        );
        $this->assertNotNull($finishingMirror);
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $finishingMirror->id,
            'account_id' => StandaloneAccountingAccount::where('code', '1-152')->value('id'),
            'debit' => 90,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $finishingMirror->id,
            'account_id' => StandaloneAccountingAccount::where('code', '1-151-03')->value('id'),
            'debit' => 0,
            'credit' => 90,
        ]);

        foreach ($first as $journal) {
            $this->assertEqualsWithDelta(
                (float) $journal->lines->sum('debit'),
                (float) $journal->lines->sum('credit'),
                0.01,
            );
        }
    }

    public function test_finishing_void_creates_voided_reversals_and_repost_trace(): void
    {
        config(['accounting.standalone_dual_posting_production_finishing_enabled' => true]);

        $legacy = $this->legacyJournal(StandaloneProductionPostingService::SOURCE_FINISHING_JOB, 8102, [
            ['code' => '1203', 'debit' => 75, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 75],
        ]);
        $service = app(StandaloneProductionPostingService::class);
        $mirrors = $service->mirrorFinishingJob(8102);

        $service->voidFinishingJob(8102);
        $service->voidFinishingJob(8102);

        $this->assertSame(0, StandaloneAccountingJournal::query()
            ->where('source_id', 8102)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(2, StandaloneAccountingJournal::query()->where('source_id', 8102)->count());
        $this->assertNotNull(StandaloneAccountingJournal::query()
            ->where('source_type', StandaloneProductionPostingService::SOURCE_FINISHING_JOB.'_reversal_'.$mirrors[0]->id)
            ->whereNotNull('voided_at')
            ->first());

        $legacy->forceFill(['voided_at' => now()])->save();
        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_FINISHING_JOB, 8102, [
            ['code' => '1203', 'debit' => 60, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 60],
        ]);

        $repost = $service->mirrorFinishingJob(8102);
        $this->assertCount(1, $repost);
        $this->assertSame(
            StandaloneProductionPostingService::SOURCE_FINISHING_JOB.'_repost_1',
            $repost[0]->source_type
        );
    }

    public function test_missing_finishing_mapping_blocks_without_creating_mirror(): void
    {
        config(['accounting.standalone_dual_posting_production_finishing_enabled' => true]);

        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_FINISHING_JOB, 8103, [
            ['code' => '9999', 'debit' => 10, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 10],
        ]);

        try {
            app(StandaloneProductionPostingService::class)->mirrorFinishingJob(8103);
            $this->fail('Mirror seharusnya gagal ketika mapping akun tidak tersedia.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('9999', (string) $exception->getMessage());
        }

        $this->assertDatabaseMissing('standalone_accounting_journals', ['source_id' => 8103]);
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
}
