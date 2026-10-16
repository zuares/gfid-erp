<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\MarketplacePayout;
use App\Models\User;
use App\Services\Accounting\MarketplacePayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplacePayoutBulkPostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_post_unposted_only_posts_draft_sesuai_filter(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'BULK-POST-1']);
        $bank = $this->createAccount('1101', 'Bank Test', true);
        $this->createAccount('1302', 'Saldo Marketplace / Clearing');
        $pendingAccount = $this->createAccount('2301', 'Payout Marketplace Belum Direkonsiliasi');

        $included = $this->createPayout($bank, '2026-08-10', 'Shopee', 'draft');
        $outsideDate = $this->createPayout($bank, '2026-07-10', 'Shopee', 'draft');
        $otherMarketplace = $this->createPayout($bank, '2026-08-12', 'TikTok', 'draft');

        $response = $this->actingAs($user)->post(route('accounting.marketplace-payouts.bulk-post'), [
            'scope'       => 'unposted',
            'marketplace' => 'Shopee',
            'from'        => '2026-08-01',
            'to'          => '2026-08-31',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'ok');
        $this->assertSame('posted', $included->fresh()->status);
        $this->assertNotNull($included->fresh()->journal_id);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $included->fresh()->journal_id,
            'account_id' => $pendingAccount->id,
            'credit'     => 100000,
        ]);
        $this->assertSame('draft', $outsideDate->fresh()->status);
        $this->assertSame('draft', $otherMarketplace->fresh()->status);
    }

    public function test_bulk_post_all_mengabaikan_yang_sudah_posted_dan_void(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'BULK-POST-2']);
        $bank = $this->createAccount('1102', 'Bank Test 2', true);
        $this->createAccount('1302', 'Saldo Marketplace / Clearing');
        $this->createAccount('2301', 'Payout Marketplace Belum Direkonsiliasi');

        $draft = $this->createPayout($bank, '2026-08-10', 'Shopee', 'draft');
        $posted = $this->createPayout($bank, '2026-08-11', 'Shopee', 'posted');
        $void = $this->createPayout($bank, '2026-08-12', 'Shopee', 'void');

        $response = $this->actingAs($user)->post(route('accounting.marketplace-payouts.bulk-post'), [
            'scope' => 'all',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'ok');
        $this->assertSame('posted', $draft->fresh()->status);
        $this->assertSame('posted', $posted->fresh()->status);
        $this->assertSame('void', $void->fresh()->status);
    }

    public function test_payout_matches_existing_clearing_before_using_pending_account(): void
    {
        $bank = $this->createAccount('1103', 'Bank Test 3', true);
        $clearing = $this->createAccount('1302', 'Saldo Marketplace / Clearing');
        $pending = $this->createAccount('2301', 'Payout Marketplace Belum Direkonsiliasi');

        $seedJournal = Journal::create([
            'date' => '2026-08-10',
            'description' => 'Settlement final test',
            'source_type' => 'marketplace_financial_statement',
            'source_id' => 999,
            'posted_at' => now(),
        ]);
        JournalLine::create([
            'journal_id' => $seedJournal->id,
            'account_id' => $clearing->id,
            'debit' => 150000,
            'credit' => 0,
        ]);

        $payout = $this->createPayout($bank, '2026-08-11', 'Shopee', 'draft');
        app(MarketplacePayoutService::class)->post($payout);

        $journalId = $payout->fresh()->journal_id;
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journalId,
            'account_id' => $clearing->id,
            'debit' => 0,
            'credit' => 100000,
        ]);
        $this->assertDatabaseMissing('journal_lines', [
            'journal_id' => $journalId,
            'account_id' => $pending->id,
            'credit' => 100000,
        ]);
    }

    private function createAccount(string $code, string $name, bool $isCash = false): Account
    {
        return Account::firstOrCreate(['code' => $code], [
            'code'      => $code,
            'name'      => $name,
            'type'      => $isCash ? 'asset' : 'asset',
            'is_cash'   => $isCash,
            'is_active' => true,
        ]);
    }

    private function createPayout(Account $bank, string $date, string $marketplace, string $status): MarketplacePayout
    {
        return MarketplacePayout::create([
            'date'             => $date,
            'marketplace_name' => $marketplace,
            'amount'           => 100000,
            'bank_account_id'  => $bank->id,
            'reference'        => $marketplace . '-' . $date . '-' . uniqid(),
            'description'      => 'Payout test',
            'status'           => $status,
        ]);
    }
}
