<?php

namespace Tests\Feature;

use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use App\Models\Account;
use App\Models\Channel;
use App\Models\Journal;
use App\Models\MarketplaceFinanceSettlement;
use App\Models\MarketplaceFinancialComponent;
use App\Models\MarketplaceFinancialTransaction;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderIncomeEstimate;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplacePayout;
use App\Models\MpIncome;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketplaceFinanceBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_is_dry_run_by_default_and_apply_is_idempotent_without_journals(): void
    {
        [$store, $order] = $this->legacyRows();

        $this->artisan('marketplace:finance-backfill')
            ->assertExitCode(0)
            ->expectsOutput('MODE DRY-RUN: tidak ada data yang ditulis.');
        $this->assertSame(0, MarketplaceFinancialTransaction::count());
        $this->assertSame(0, MarketplaceFinancialComponent::count());
        $this->assertSame(0, MarketplaceFinanceSettlement::count());

        $this->artisan('marketplace:finance-backfill', ['--apply' => true])
            ->assertExitCode(0)
            ->expectsOutput('MODE APPLY: hasil backfill ditulis ke tabel finance baru.');

        $this->assertSame(1, MarketplaceFinancialTransaction::count());
        $this->assertSame(4, MarketplaceFinancialComponent::count());
        $this->assertSame(1, MarketplaceFinanceSettlement::count());
        $this->assertSame(1, DB::table('marketplace_finance_settlement_allocations')->count());
        $transaction = MarketplaceFinancialTransaction::firstOrFail();
        $this->assertSame($order->id, $transaction->marketplace_order_id);
        $this->assertSame(EscrowStatus::FINALIZED, $transaction->escrow_status);
        $this->assertSame(IncomeStatus::RELEASED, $transaction->income_status);
        $this->assertNull($transaction->sale_journal_id);
        $this->assertNull(MarketplaceFinanceSettlement::firstOrFail()->journal_id);

        $this->artisan('marketplace:finance-backfill', ['--apply' => true])
            ->assertExitCode(0);

        $this->assertSame(1, MarketplaceFinancialTransaction::count());
        $this->assertSame(4, MarketplaceFinancialComponent::count());
        $this->assertSame(1, MarketplaceFinanceSettlement::count());
        $this->assertSame(1, DB::table('marketplace_finance_settlement_allocations')->count());
    }

    public function test_dry_run_predicts_residual_components_and_zero_net_does_not_repeat_update(): void
    {
        $store = $this->store();
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'channel_order_id' => 'BF-RESIDUAL-001',
            'buyer_payment_amount' => 100000,
            'commission_fee' => 5000,
            'final_income' => 90000,
            'settlement_time' => '2026-08-28 12:00:00',
        ]);
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'channel_order_id' => 'BF-ZERO-NET-001',
            'buyer_payment_amount' => 100000,
            'final_income' => 0,
        ]);
        $service = app(\App\Services\Marketplace\Finance\MarketplaceFinanceBackfillService::class);
        $options = ['store_id' => $store->id, 'sources' => ['order_settlements']];

        $preflight = $service->run($options + ['dry_run' => true]);
        $applied = $service->run($options + ['dry_run' => false]);
        $after = $service->run($options + ['dry_run' => true]);

        $this->assertSame($preflight['summary']['components_created'], $applied['summary']['components_created']);
        $this->assertSame(2, $applied['summary']['components_created']);
        $this->assertSame(0, $after['summary']['updated']);
        $this->assertSame(0, $after['summary']['created']);
    }

    public function test_dry_run_reports_unmatched_payout_without_creating_anything(): void
    {
        $store = $this->store();
        $bank = Account::create(['code' => 'BF-1101', 'name' => 'Bank Backfill', 'type' => 'asset', 'is_cash' => true, 'is_active' => true]);
        MarketplacePayout::create([
            'date' => '2026-08-28',
            'marketplace_name' => 'Shopee',
            'store_id' => $store->id,
            'source' => 'shopee',
            'amount' => 50000,
            'bank_account_id' => $bank->id,
            'external_transaction_id' => 'BF-UNMATCHED-001',
            'status' => 'posted',
            'source_payload' => ['allocations' => [['order_sn' => 'NOT-IN-ORDERS', 'amount' => 50000]]],
        ]);

        $result = app(\App\Services\Marketplace\Finance\MarketplaceFinanceBackfillService::class)->run([
            'dry_run' => true,
            'sources' => ['payouts'],
        ]);

        $this->assertSame(1, $result['summary']['unmatched']);
        $this->assertSame('order_unmatched:NOT-IN-ORDERS', $result['unmatched_rows'][0]['reason']);
        $this->assertSame(0, MarketplaceFinanceSettlement::count());
        $this->assertSame(0, MarketplaceFinancialTransaction::count());
    }

    public function test_payout_without_order_mapping_creates_unallocated_settlement_header(): void
    {
        $store = $this->store();
        $bank = Account::create(['code' => 'BF-WALLET-1101', 'name' => 'Wallet Payout Bank', 'type' => 'asset', 'is_cash' => true, 'is_active' => true]);
        MarketplacePayout::create([
            'date' => '2026-10-01',
            'transaction_created_at' => '2026-10-01 09:30:00',
            'marketplace_name' => 'Shopee',
            'store_id' => $store->id,
            'source' => 'shopee_wallet',
            'amount' => 250000,
            'bank_account_id' => $bank->id,
            'external_transaction_id' => 'BF-WALLET-PAYOUT-001',
            'status' => 'draft',
            'source_payload' => ['transaction_type' => 'withdrawal', 'amount' => 250000],
        ]);

        $dryRun = app(\App\Services\Marketplace\Finance\MarketplaceFinanceBackfillService::class)->run([
            'dry_run' => true,
            'sources' => ['payouts'],
        ]);

        $this->assertSame(1, $dryRun['summary']['settlements_created']);
        $this->assertSame(0, $dryRun['summary']['allocations_created']);
        $this->assertSame('settlement_without_order_mapping', $dryRun['unmatched_rows'][0]['reason']);
        $this->assertSame(0, MarketplaceFinanceSettlement::count());

        $this->artisan('marketplace:finance-backfill', [
            '--apply' => true,
            '--source' => ['payouts'],
        ])->assertExitCode(0);

        $settlement = MarketplaceFinanceSettlement::firstOrFail();
        $this->assertSame('250000.00', (string) $settlement->amount);
        $this->assertSame('2026-10-01', $settlement->settlement_date->toDateString());
        $this->assertSame('pending', $settlement->status->value);
        $this->assertNull($settlement->journal_id);
        $this->assertSame(0, DB::table('marketplace_finance_settlement_allocations')->count());
    }

    public function test_completed_wallet_withdrawal_backfills_as_withdrawn_not_bank_received(): void
    {
        $store = $this->store();
        $bank = Account::create(['code' => 'BF-WALLET-COMPLETED-1101', 'name' => 'Wallet Withdrawal Bank', 'type' => 'asset', 'is_cash' => true, 'is_active' => true]);
        MarketplacePayout::create([
            'date' => '2026-10-10',
            'marketplace_name' => 'Shopee',
            'store_id' => $store->id,
            'source' => 'shopee_wallet',
            'amount' => 2000000,
            'bank_account_id' => $bank->id,
            'external_transaction_id' => 'BF-WALLET-COMPLETED',
            'status' => 'draft',
            'source_payload' => [
                'transaction_type' => 'WITHDRAWAL_CREATED',
                'status' => 'COMPLETED',
                'amount' => -2000000,
            ],
        ]);

        app(\App\Services\Marketplace\Finance\MarketplaceFinanceBackfillService::class)->run([
            'dry_run' => false,
            'sources' => ['payouts'],
            'store_id' => $store->id,
        ]);

        $settlement = MarketplaceFinanceSettlement::firstOrFail();
        $this->assertSame('withdrawn', $settlement->status->value);
        $this->assertNull($settlement->received_at);
        $this->assertTrue($settlement->isWalletWithdrawal());
        $this->assertNull($settlement->journal_id);

        $settlement->forceFill([
            'status' => \App\Domain\Marketplace\Finance\Enums\SettlementStatus::RECEIVED,
            'received_at' => now(),
            'raw_payload' => ['source' => 'marketplace_payouts'],
        ])->save();
        app(\App\Services\Marketplace\Finance\MarketplaceFinanceBackfillService::class)->run([
            'dry_run' => false,
            'sources' => ['payouts'],
            'store_id' => $store->id,
        ]);

        $this->assertSame('withdrawn', $settlement->fresh()->status->value);
        $this->assertNull($settlement->fresh()->received_at);
        $this->assertTrue($settlement->fresh()->isWalletWithdrawal());
    }

    public function test_posted_legacy_wallet_keeps_its_journal_reference_but_not_bank_received_status(): void
    {
        $store = $this->store();
        $bank = Account::create(['code' => 'BF-POSTED-WALLET-1101', 'name' => 'Posted Wallet Bank', 'type' => 'asset', 'is_cash' => true, 'is_active' => true]);
        $payout = MarketplacePayout::create([
            'date' => '2026-10-10',
            'marketplace_name' => 'Shopee',
            'store_id' => $store->id,
            'source' => 'shopee_wallet',
            'amount' => 2000000,
            'bank_account_id' => $bank->id,
            'external_transaction_id' => 'BF-POSTED-WALLET',
            'status' => 'posted',
            'source_payload' => ['transaction_type' => 'WITHDRAWAL_CREATED', 'status' => 'COMPLETED'],
        ]);
        $journal = Journal::create([
            'date' => '2026-10-10',
            'source_type' => 'marketplace_payout',
            'source_id' => $payout->id,
            'posted_at' => now(),
        ]);
        $payout->forceFill(['journal_id' => $journal->id])->save();

        app(\App\Services\Marketplace\Finance\MarketplaceFinanceBackfillService::class)->run([
            'dry_run' => false,
            'sources' => ['payouts'],
            'store_id' => $store->id,
        ]);

        $settlement = MarketplaceFinanceSettlement::firstOrFail();
        $this->assertSame('withdrawn', $settlement->status->value);
        $this->assertNull($settlement->received_at);
        $this->assertSame($journal->id, $settlement->raw_payload['legacy_journal_id']);
        $this->assertNull($settlement->journal_id);
        $this->assertSame(1, Journal::count());
    }

    public function test_settlement_backfill_maps_existing_order_invoice_and_shipment_on_first_insert(): void
    {
        $store = $this->store();
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'BF-LINK-001',
            'channel_order_id' => 'BF-LINK-001',
            'order_date' => '2026-08-28',
            'ordered_at' => '2026-08-28 09:00:00',
            'order_status' => 'COMPLETED',
            'total_amount' => 100000,
            'currency' => 'IDR',
        ]);
        $warehouseId = $this->warehouse('BF-LINK-WH');
        $invoice = SalesInvoice::create([
            'code' => 'INV-BF-LINK-001',
            'date' => '2026-08-28',
            'warehouse_id' => $warehouseId,
            'status' => 'posted',
            'grand_total' => 100000,
        ]);
        $invoice->forceFill([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'channel_order_no' => 'BF-LINK-001',
        ])->save();
        $shipment = Shipment::create([
            'code' => 'SHP-BF-LINK-001',
            'store_id' => $store->id,
            'sales_invoice_id' => $invoice->id,
            'date' => '2026-08-28',
            'status' => 'posted',
        ]);
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => 'BF-LINK-001',
            'buyer_payment_amount' => 90000,
            'commission_fee' => 5000,
            'final_income' => 95000,
            'settlement_time' => '2026-08-28 12:00:00',
            'raw_json' => ['order_discounted_price' => 100000],
        ]);

        $this->artisan('marketplace:finance-backfill', [
            '--apply' => true,
            '--source' => ['order_settlements'],
        ])->assertExitCode(0);

        $transaction = MarketplaceFinancialTransaction::firstOrFail();
        $this->assertSame($order->id, $transaction->marketplace_order_id);
        $this->assertSame($invoice->id, $transaction->sales_invoice_id);
        $this->assertSame($shipment->id, $transaction->shipment_id);
        $this->assertSame('100000.00', (string) $transaction->gross_amount);
        $this->assertSame(1, MarketplaceFinancialComponent::count());
    }

    public function test_payout_with_root_order_sn_creates_settlement_allocation(): void
    {
        [$store, $order] = $this->legacyRows();
        $bank = Account::create([
            'code' => 'BF-ROOT-1101',
            'name' => 'Bank Root Payout',
            'type' => 'asset',
            'is_cash' => true,
            'is_active' => true,
        ]);
        MarketplacePayout::create([
            'date' => '2026-08-28',
            'marketplace_name' => 'Shopee',
            'store_id' => $store->id,
            'source' => 'shopee',
            'amount' => 94500,
            'bank_account_id' => $bank->id,
            'external_transaction_id' => 'BF-ROOT-PAYOUT-001',
            'transaction_created_at' => '2026-08-28 13:00:00',
            'status' => 'posted',
            'source_payload' => [
                'order_sn' => $order->channel_order_id,
                'amount' => 94500,
            ],
        ]);

        $this->artisan('marketplace:finance-backfill', [
            '--apply' => true,
            '--source' => ['order_settlements', 'income_estimates', 'mp_incomes', 'payouts'],
        ])->assertExitCode(0);

        $this->assertSame(2, MarketplaceFinanceSettlement::count());
        $this->assertSame(2, DB::table('marketplace_finance_settlement_allocations')->count());
    }

    /** @return array{0:Store,1:MarketplaceOrder} */
    private function legacyRows(): array
    {
        $store = $this->store();
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'BF-ORDER-001',
            'channel_order_id' => 'BF-ORDER-001',
            'order_date' => '2026-08-28',
            'ordered_at' => '2026-08-28 09:00:00',
            'order_status' => 'COMPLETED',
            'total_amount' => 100000,
            'currency' => 'IDR',
        ]);
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => 'BF-ORDER-001',
            'buyer_payment_amount' => 100000,
            'commission_fee' => 5000,
            'seller_voucher' => 1000,
            'drc_adjustable_refund' => -500,
            'final_income' => 94500,
            'settlement_time' => '2026-08-28 12:00:00',
            'raw_json' => ['legacy' => true],
        ]);
        MarketplaceOrderIncomeEstimate::create([
            'store_id' => $store->id,
            'marketplace_order_id' => $order->id,
            'channel_order_id' => 'BF-ORDER-001',
            'income_status' => 1,
            'estimated_escrow_amount' => 100000,
            'estimated_payout_at' => '2026-08-28 12:00:00',
            'currency' => 'IDR',
            'synced_at' => '2026-08-28 12:00:00',
            'raw_json' => ['estimate' => true],
        ]);
        MpIncome::create([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'platform_order_id' => 'BF-ORDER-001',
            'released_at' => '2026-08-28 12:00:00',
            'platform_fee_total' => 5000,
            'refund_total' => 0,
            'net_payout_actual' => 94500,
            'currency' => 'IDR',
            'raw_payload' => ['income' => true],
        ]);
        $bank = Account::create(['code' => 'BF-1101', 'name' => 'Bank Backfill', 'type' => 'asset', 'is_cash' => true, 'is_active' => true]);
        MarketplacePayout::create([
            'date' => '2026-08-28',
            'marketplace_name' => 'Shopee',
            'store_id' => $store->id,
            'source' => 'shopee',
            'amount' => 94500,
            'bank_account_id' => $bank->id,
            'external_transaction_id' => 'BF-PAYOUT-001',
            'transaction_created_at' => '2026-08-28 13:00:00',
            'status' => 'posted',
            'source_payload' => ['allocations' => [['order_sn' => 'BF-ORDER-001', 'amount' => 94500]]],
        ]);

        return [$store, $order];
    }

    private function store(): Store
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => 'BF-'.fake()->unique()->numerify('#####'),
            'name' => 'Backfill Store',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    private function warehouse(string $code): int
    {
        return DB::table('warehouses')->insertGetId([
            'code' => $code,
            'name' => $code,
            'type' => 'internal',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
