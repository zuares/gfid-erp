<?php

namespace Tests\Feature\Database;

use App\Domain\Marketplace\Finance\Enums\ComponentDirection;
use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use App\Domain\Marketplace\Finance\Enums\SettlementStatus;
use App\Models\Account;
use App\Models\Channel;
use App\Models\MarketplaceFinanceSettlement;
use App\Models\MarketplaceFinanceSettlementAllocation;
use App\Models\MarketplaceFinancialComponent;
use App\Models\MarketplaceFinancialTransaction;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceFinanceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_models_use_expected_tables_and_enum_casts(): void
    {
        $transaction = new MarketplaceFinancialTransaction([
            'escrow_status' => EscrowStatus::PENDING,
            'income_status' => IncomeStatus::TO_RELEASE,
        ]);
        $component = new MarketplaceFinancialComponent([
            'direction' => ComponentDirection::DEBIT,
        ]);
        $settlement = new MarketplaceFinanceSettlement([
            'status' => SettlementStatus::RECEIVED,
        ]);

        $this->assertSame('marketplace_financial_transactions', $transaction->getTable());
        $this->assertSame('marketplace_financial_components', $component->getTable());
        $this->assertSame('marketplace_finance_settlements', $settlement->getTable());
        $this->assertSame(EscrowStatus::PENDING, $transaction->escrow_status);
        $this->assertSame(IncomeStatus::TO_RELEASE, $transaction->income_status);
        $this->assertSame(ComponentDirection::DEBIT, $component->direction);
        $this->assertSame(SettlementStatus::RECEIVED, $settlement->status);
        $this->assertSame('marketplace_finance_settlement_allocations', (new MarketplaceFinanceSettlementAllocation)->getTable());
    }

    public function test_transaction_and_settlement_external_keys_are_scoped(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $storeA = Store::create(['channel_id' => $channel->id, 'code' => 'STORE-A', 'name' => 'Store A']);
        $storeB = Store::create(['channel_id' => $channel->id, 'code' => 'STORE-B', 'name' => 'Store B']);

        MarketplaceFinancialTransaction::create([
            'store_id' => $storeA->id,
            'channel' => 'shopee',
            'order_sn' => 'ORDER-1',
        ]);
        MarketplaceFinancialTransaction::create([
            'store_id' => $storeB->id,
            'channel' => 'shopee',
            'order_sn' => 'ORDER-1',
        ]);

        $this->expectException(QueryException::class);
        MarketplaceFinancialTransaction::create([
            'store_id' => $storeA->id,
            'channel' => 'shopee',
            'order_sn' => 'ORDER-1',
        ]);
    }

    public function test_finance_relationships_are_available_without_creating_journals(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create(['channel_id' => $channel->id, 'code' => 'STORE-A', 'name' => 'Store A']);
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'LEGACY-1',
            'channel_order_id' => 'ORDER-1',
            'order_date' => now(),
        ]);
        $account = Account::query()->where('code', '6202')->firstOrFail();
        $transaction = MarketplaceFinancialTransaction::create([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'marketplace_order_id' => $order->id,
            'order_sn' => 'ORDER-1',
        ]);
        $component = $transaction->components()->create([
            'component_code' => 'commission_fee',
            'component_name' => 'Commission',
            'amount' => 100,
            'direction' => ComponentDirection::DEBIT,
            'account_id' => $account->id,
            'dedupe_key' => str_repeat('a', 64),
        ]);

        $this->assertTrue($order->financialTransactions->contains($transaction));
        $this->assertTrue($transaction->components->contains($component));
        $this->assertCount(0, $transaction->saleJournal()->get());
        $this->assertCount(0, $transaction->escrowJournal()->get());
    }
}
