<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Journal;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Services\Accounting\StandaloneProductionPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShipmentPilotLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['accounting.standalone_dual_posting_production_shipment_enabled' => true]);
        $this->ensureProductionMapping('5101', '5-110');
        $this->ensureProductionMapping('1203', '1-152');
    }

    public function test_shipment_hpp_mirror_is_balanced_and_idempotent(): void
    {
        $this->legacyShipmentJournal(9101, 120, 0);

        $service = app(StandaloneProductionPostingService::class);
        $first = $service->mirrorShipment(9101);
        $second = $service->mirrorShipment(9101);

        $this->assertCount(1, $first);
        $this->assertCount(1, $second);
        $this->assertSame(1, StandaloneAccountingJournal::query()
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SHIPMENT_COGS)
            ->where('source_id', 9101)
            ->whereNull('voided_at')
            ->count());
        $this->assertEqualsWithDelta(120, (float) $first[0]->lines->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(120, (float) $first[0]->lines->sum('credit'), 0.01);
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $first[0]->id,
            'account_id' => $this->standaloneAccountId('5-110'),
            'debit' => 120,
            'credit' => 0,
        ]);
    }

    public function test_feature_flag_off_keeps_legacy_only_and_creates_no_mirror(): void
    {
        config(['accounting.standalone_dual_posting_production_shipment_enabled' => false]);
        $this->legacyShipmentJournal(9100, 25, 0);

        $mirrors = app(StandaloneProductionPostingService::class)->mirrorShipment(9100);

        $this->assertSame([], $mirrors);
        $this->assertDatabaseMissing('standalone_accounting_journals', [
            'source_id' => 9100,
        ]);
        $this->assertDatabaseHas('journals', [
            'source_type' => StandaloneProductionPostingService::SOURCE_SHIPMENT_COGS,
            'source_id' => 9100,
        ]);
    }

    public function test_shipment_void_unpost_is_idempotent_and_creates_audit_reversal(): void
    {
        $this->legacyShipmentJournal(9102, 75, 0);

        $service = app(StandaloneProductionPostingService::class);
        $mirrors = $service->mirrorShipment(9102);
        $service->voidShipment(9102);
        $service->voidShipment(9102);

        $this->assertNotNull($mirrors[0]->fresh()->voided_at);
        $this->assertSame(0, StandaloneAccountingJournal::query()
            ->where('source_id', 9102)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(2, StandaloneAccountingJournal::query()
            ->where('source_id', 9102)
            ->count());

        $reversal = StandaloneAccountingJournal::query()
            ->where('source_id', 9102)
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SHIPMENT_COGS.'_reversal_'.$mirrors[0]->id)
            ->with('lines')
            ->firstOrFail();
        $this->assertNotNull($reversal->voided_at);
        $this->assertEqualsWithDelta(75, (float) $reversal->lines->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(75, (float) $reversal->lines->sum('credit'), 0.01);
    }

    public function test_shipment_repost_uses_a_new_trace_key_after_void(): void
    {
        $legacy = $this->legacyShipmentJournal(9103, 50, 0);
        $service = app(StandaloneProductionPostingService::class);
        $service->mirrorShipment(9103);
        $service->voidShipment(9103);

        $legacy->forceFill(['voided_at' => now()])->save();
        $this->legacyShipmentJournal(9103, 60, 0);

        $repost = $service->mirrorShipment(9103);

        $this->assertCount(1, $repost);
        $this->assertSame(
            StandaloneProductionPostingService::SOURCE_SHIPMENT_COGS.'_repost_1',
            $repost[0]->source_type,
        );
        $this->assertNull($repost[0]->fresh()->voided_at);
    }

    public function test_missing_shipment_mapping_blocks_without_creating_mirror(): void
    {
        StandaloneAccountingAccountMapping::query()
            ->where('legacy_account_code', '5101')
            ->delete();
        $this->legacyShipmentJournal(9104, 10, 0);

        try {
            app(StandaloneProductionPostingService::class)->mirrorShipment(9104);
            $this->fail('Mirror seharusnya gagal ketika mapping HPP tidak tersedia.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('5101', (string) $exception->getMessage());
        }

        $this->assertDatabaseMissing('standalone_accounting_journals', [
            'source_id' => 9104,
        ]);
    }

    private function legacyShipmentJournal(int $shipmentId, float $amount, float $credit): Journal
    {
        $journal = Journal::create([
            'date' => '2026-10-27',
            'description' => 'Test shipment_cogs #'.$shipmentId,
            'source_type' => StandaloneProductionPostingService::SOURCE_SHIPMENT_COGS,
            'source_id' => $shipmentId,
            'posted_at' => now(),
        ]);

        $journal->lines()->createMany([
            [
                'account_id' => Account::firstOrCreate(
                    ['code' => '5101'],
                    ['name' => 'Test HPP', 'type' => 'expense', 'is_active' => true],
                )->id,
                'debit' => $amount,
                'credit' => $credit,
            ],
            [
                'account_id' => Account::firstOrCreate(
                    ['code' => '1203'],
                    ['name' => 'Test Finished Goods', 'type' => 'asset', 'is_active' => true],
                )->id,
                'debit' => $credit,
                'credit' => $amount,
            ],
        ]);

        return $journal;
    }

    private function ensureProductionMapping(string $legacyCode, string $standaloneCode): void
    {
        StandaloneAccountingAccount::query()->updateOrCreate(
            ['code' => $standaloneCode],
            ['name' => 'Test '.$standaloneCode, 'type' => str_starts_with($standaloneCode, '5-') ? 'expense' : 'asset', 'is_active' => true],
        );
        StandaloneAccountingAccountMapping::query()->updateOrCreate(
            ['legacy_account_code' => $legacyCode],
            ['standalone_account_code' => $standaloneCode, 'purpose' => 'Test mapping', 'is_active' => true],
        );
    }

    private function standaloneAccountId(string $code): int
    {
        return (int) StandaloneAccountingAccount::query()->where('code', $code)->value('id');
    }
}
