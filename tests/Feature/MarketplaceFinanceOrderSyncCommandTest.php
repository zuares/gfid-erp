<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Channel;
use App\Models\MarketplaceFinanceSyncRun;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplacePayout;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MarketplaceFinanceOrderSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_is_order_only_and_writes_no_finance_records(): void
    {
        $store = $this->storeWithOrder();
        $this->payout($store);

        $this->artisan('marketplace:finance-order-sync', [
            '--store' => 'Greatfit.id',
        ])->assertSuccessful();

        $this->assertDatabaseCount('marketplace_financial_transactions', 0);
        $this->assertDatabaseCount('marketplace_finance_settlements', 0);
        $this->assertDatabaseCount('marketplace_finance_settlement_allocations', 0);
        $this->assertDatabaseCount('journals', 0);
        $this->assertDatabaseHas('marketplace_finance_sync_runs', [
            'trigger' => 'order_manual',
            'status' => 'success',
        ]);
        $this->assertStringContainsString('"scope":"order_only"', MarketplaceFinanceSyncRun::firstOrFail()->output);
    }

    public function test_apply_is_idempotent_and_never_imports_payout_or_posts_journals(): void
    {
        $store = $this->storeWithOrder();
        $this->payout($store);

        $arguments = [
            '--store' => 'Greatfit.id',
            '--apply' => true,
            '--trigger' => 'schedule',
        ];
        $this->artisan('marketplace:finance-order-sync', $arguments)->assertSuccessful();
        $this->artisan('marketplace:finance-order-sync', $arguments)->assertSuccessful();

        $this->assertDatabaseCount('marketplace_financial_transactions', 1);
        $this->assertDatabaseCount('marketplace_finance_settlements', 0);
        $this->assertDatabaseCount('marketplace_finance_settlement_allocations', 0);
        $this->assertDatabaseCount('journals', 0);
        $this->assertSame(2, MarketplaceFinanceSyncRun::where('trigger', 'order_schedule')->where('status', 'success')->count());
    }

    public function test_command_rejects_ambiguous_store_name(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        foreach (['ONE', 'TWO'] as $code) {
            Store::create([
                'channel_id' => $channel->id,
                'code' => $code,
                'name' => 'Greatfit.id',
                'status' => 'active',
                'is_active' => true,
            ]);
        }

        $this->artisan('marketplace:finance-order-sync', ['--store' => 'Greatfit.id'])
            ->assertExitCode(2);

        $this->assertDatabaseCount('marketplace_finance_sync_runs', 0);
    }

    public function test_order_only_sync_is_registered_in_scheduler(): void
    {
        Artisan::call('schedule:list');
        $output = Artisan::output();

        $this->assertStringContainsString('marketplace:finance-order-sync', $output);
        $this->assertStringContainsString("--store='Greatfit.id'", $output);
    }

    private function storeWithOrder(): Store
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'GREATFIT-ORDER-SYNC',
            'name' => 'Greatfit.id',
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'ORDER-SYNC-001',
            'channel_order_id' => 'ORDER-SYNC-001',
            'order_date' => '2026-10-20',
            'ordered_at' => '2026-10-20 10:00:00',
            'order_status' => 'COMPLETED',
            'total_amount' => 100000,
            'currency' => 'IDR',
        ]);
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => 100000,
            'commission_fee' => 5000,
            'final_income' => 95000,
            'settlement_time' => '2026-10-21 10:00:00',
            'raw_json' => ['order_selling_price' => 100000],
        ]);

        return $store;
    }

    private function payout(Store $store): void
    {
        $bank = Account::create([
            'code' => 'ORDER-SYNC-BANK',
            'name' => 'Order Sync Bank',
            'type' => 'asset',
            'is_cash' => true,
            'is_active' => true,
        ]);
        MarketplacePayout::create([
            'date' => '2026-10-21',
            'marketplace_name' => 'Shopee',
            'store_id' => $store->id,
            'bank_account_id' => $bank->id,
            'source' => 'shopee_wallet',
            'amount' => 95000,
            'external_transaction_id' => 'IGNORED-PAYOUT-001',
            'status' => 'completed',
            'source_payload' => ['status' => 'COMPLETED'],
        ]);
    }
}
