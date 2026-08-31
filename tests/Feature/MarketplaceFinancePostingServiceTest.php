<?php

namespace Tests\Feature;

use App\Domain\Marketplace\Finance\Enums\ComponentDirection;
use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use App\Domain\Marketplace\Finance\Enums\SettlementStatus;
use App\Models\Account;
use App\Models\Channel;
use App\Models\Journal;
use App\Models\MarketplaceFinanceSettlement;
use App\Models\MarketplaceFinancialComponent;
use App\Models\MarketplaceFinancialTransaction;
use App\Models\Store;
use App\Services\Marketplace\Finance\MarketplaceFinancePostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MarketplaceFinancePostingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_posting_uses_configured_receivable_and_sales_accounts_idempotently(): void
    {
        [$store, $transaction] = $this->transaction('POST-SALE-001', 100000);
        $this->accounts();
        $service = app(MarketplaceFinancePostingService::class);

        $service->postSale($transaction);
        $service->postSale($transaction->fresh());
        $journal = Journal::query()->where('source_type', 'marketplace_sale')->firstOrFail();

        $this->assertSame(1, Journal::where('source_type', 'marketplace_sale')->count());
        $this->assertSame($journal->id, $transaction->fresh()->sale_journal_id);
        $this->assertJournalLines($journal, '1302', 100000, 0);
        $this->assertJournalLines($journal, '4101', 0, 100000);
        $this->assertDatabaseMissing('journal_lines', ['account_id' => Account::where('code', '5101')->value('id')]);
    }

    public function test_escrow_posting_maps_components_and_marks_transaction_finalized_without_hpp(): void
    {
        [$store, $transaction] = $this->transaction('POST-ESCROW-001', 100000);
        $this->accounts();
        MarketplaceFinancialComponent::create([
            'financial_transaction_id' => $transaction->id,
            'component_code' => 'admin_fee',
            'component_name' => 'Admin Fee',
            'amount' => 5000,
            'direction' => ComponentDirection::DEBIT,
            'dedupe_key' => str_repeat('a', 64),
            'raw_payload' => ['commission_fee' => 5000],
        ]);

        $service = app(MarketplaceFinancePostingService::class);
        $service->postEscrow($transaction);
        $service->postEscrow($transaction->fresh());
        $journal = Journal::query()->where('source_type', 'marketplace_escrow')->firstOrFail();
        $fresh = $transaction->fresh();

        $this->assertSame(EscrowStatus::FINALIZED, $fresh->escrow_status);
        $this->assertSame(1, Journal::where('source_type', 'marketplace_escrow')->count());
        $this->assertJournalLines($journal, '6202', 5000, 0);
        $this->assertJournalLines($journal, '1302', 0, 5000);
        $this->assertDatabaseMissing('journal_lines', ['account_id' => Account::where('code', '5101')->value('id')]);
    }

    public function test_settlement_received_posts_bank_against_marketplace_receivable_idempotently(): void
    {
        $store = $this->store();
        $this->accounts();
        $bank = Account::where('code', '1101')->firstOrFail();
        $settlement = MarketplaceFinanceSettlement::create([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'external_settlement_id' => 'POST-SETTLEMENT-001',
            'settlement_date' => '2026-08-28',
            'amount' => 95000,
            'currency' => 'IDR',
            'bank_account_id' => $bank->id,
            'status' => SettlementStatus::RECEIVED,
            'received_at' => now(),
        ]);

        $service = app(MarketplaceFinancePostingService::class);
        $service->postSettlement($settlement);
        $service->postSettlement($settlement->fresh());
        $journal = Journal::query()->where('source_type', 'marketplace_settlement')->firstOrFail();

        $this->assertSame(1, Journal::where('source_type', 'marketplace_settlement')->count());
        $this->assertSame($journal->id, $settlement->fresh()->journal_id);
        $this->assertJournalLines($journal, '1101', 95000, 0);
        $this->assertJournalLines($journal, '1302', 0, 95000);
    }

    public function test_unreceived_settlement_is_blocked_and_creates_no_journal(): void
    {
        $store = $this->store();
        $this->accounts();
        $settlement = MarketplaceFinanceSettlement::create([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'external_settlement_id' => 'POST-SETTLEMENT-PENDING',
            'amount' => 50000,
            'status' => SettlementStatus::UNKNOWN,
        ]);

        $this->expectException(ValidationException::class);
        app(MarketplaceFinancePostingService::class)->postSettlement($settlement);
        $this->assertDatabaseCount('journals', 0);
    }

    public function test_reversal_uses_journal_service_and_allows_a_new_active_posting(): void
    {
        [$store, $transaction] = $this->transaction('POST-REVERSAL-001', 75000);
        $this->accounts();
        $service = app(MarketplaceFinancePostingService::class);

        $service->postSale($transaction);
        $original = Journal::query()->where('source_type', 'marketplace_sale')->firstOrFail();
        $reversal = $service->reverseSale($transaction, 'Koreksi test');

        $this->assertNotNull($reversal);
        $this->assertNotNull($original->fresh()->voided_at);
        $this->assertNotNull($reversal->fresh()->voided_at);
        $this->assertSame(2, Journal::where('source_type', 'marketplace_sale')->count());

        $service->postSale($transaction->fresh());
        $this->assertSame(3, Journal::where('source_type', 'marketplace_sale')->count());
        $this->assertNull($transaction->fresh()->saleJournal->voided_at);
    }

    private function assertJournalLines(Journal $journal, string $accountCode, float $debit, float $credit): void
    {
        $accountId = Account::where('code', $accountCode)->value('id');
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => $accountId,
            'debit' => $debit,
            'credit' => $credit,
        ]);
    }

    private function transaction(string $orderSn, float $amount): array
    {
        $store = $this->store();
        $transaction = MarketplaceFinancialTransaction::create([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'order_sn' => $orderSn,
            'currency' => 'IDR',
            'gross_amount' => $amount,
            'net_amount' => $amount,
            'escrow_status' => EscrowStatus::SYNCED,
            'income_status' => IncomeStatus::PENDING,
        ]);

        return [$store, $transaction];
    }

    private function store(): Store
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => 'POST-FIN-'.fake()->unique()->numerify('#####'),
            'name' => 'Posting Finance Store',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    private function accounts(): void
    {
        foreach ([
            ['1101', 'Bank Test', 'asset', true],
            ['1302', 'Saldo Marketplace / Clearing', 'asset', false],
            ['4101', 'Penjualan', 'revenue', false],
            ['5101', 'Harga Pokok Penjualan', 'expense', false],
            ['6202', 'Biaya Komisi', 'expense', false],
            ['6203', 'Biaya Layanan', 'expense', false],
            ['6204', 'Biaya Transaksi', 'expense', false],
            ['6205', 'Biaya Affiliate', 'expense', false],
            ['6207', 'Biaya Asuransi Pengiriman', 'expense', false],
            ['6201', 'Biaya Marketplace Lainnya', 'expense', false],
        ] as [$code, $name, $type, $isCash]) {
            Account::updateOrCreate(['code' => $code], [
                'name' => $name,
                'type' => $type,
                'is_cash' => $isCash,
                'is_active' => true,
            ]);
        }
    }
}
