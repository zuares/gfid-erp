<?php

namespace Tests\Feature;

use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use App\Models\Channel;
use App\Models\MarketplaceFinancialComponent;
use App\Models\MarketplaceFinancialTransaction;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceTikTokFinancialTransaction;
use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketplaceFinanceTikTokProjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_tiktok_order_statement_is_projected_to_generic_finance_idempotently(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-GENERIC-FINANCE',
            'name' => 'TikTok Generic Finance',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
            'credentials' => [
                'app_key' => 'app-key-test',
                'app_secret' => 'app-secret-test',
                'access_token' => 'access-token-test',
                'shop_cipher' => 'shop-cipher-test',
            ],
        ]);
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'channel_order_id' => 'TT-GENERIC-ORDER-1',
            'external_order_id' => 'TT-GENERIC-ORDER-1',
            'order_status' => 'COMPLETED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'total_paid_customer' => 100000,
            'total_amount' => 100000,
            'currency' => 'IDR',
        ]);

        Http::fake([
            'https://open-api.tiktokglobalshop.com/finance/202501/orders/*/statement_transactions*' => Http::response([
                'code' => 0,
                'data' => [
                    'order_id' => $order->channel_order_id,
                    'currency' => 'IDR',
                    'revenue_amount' => '100000',
                    'fee_and_tax_amount' => '-8766',
                    'settlement_amount' => '91234',
                    'sku_transactions' => [[
                        'sku_id' => 'SKU-1',
                        'fee_tax_breakdown' => [
                            'fee' => ['platform_commission_fee' => '-8000'],
                            'tax' => ['vat' => '-766'],
                        ],
                    ]],
                ],
            ]),
        ]);

        $sync = app(MarketplaceSyncService::class);
        $sync->syncTikTokOrderFinance($store, $order);
        $sync->syncTikTokOrderFinance($store, $order);

        $this->assertDatabaseHas('marketplace_financial_transactions', [
            'store_id' => $store->id,
            'channel' => 'tiktok',
            'order_sn' => $order->channel_order_id,
            'gross_amount' => '100000.00',
            'net_amount' => '91234.00',
            'escrow_status' => EscrowStatus::FINALIZED->value,
            'income_status' => IncomeStatus::RELEASED->value,
        ]);
        $this->assertSame(1, MarketplaceFinancialTransaction::count());
        $this->assertSame(2, MarketplaceFinancialComponent::count());
        $this->assertDatabaseHas('marketplace_financial_components', [
            'component_code' => 'admin_fee',
            'amount' => '8000.00',
        ]);
        $this->assertDatabaseHas('marketplace_financial_components', [
            'component_code' => 'other_fee',
            'amount' => '766.00',
        ]);
    }

    public function test_tiktok_withdrawal_backfill_becomes_unverified_generic_settlement(): void
    {
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-WITHDRAWAL-FINANCE',
            'name' => 'TikTok Withdrawal Finance',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        MarketplaceTikTokFinancialTransaction::create([
            'store_id' => $store->id,
            'external_transaction_id' => 'TT-WITHDRAW-1',
            'source' => 'withdrawals',
            'transaction_type' => 'WITHDRAW',
            'amount' => 50000,
            'currency' => 'IDR',
            'money_flow' => 'MONEY_OUT',
            'status' => 'COMPLETED',
            'transaction_created_at' => now()->subDay(),
            'source_payload' => ['type' => 'WITHDRAW'],
            'synced_at' => now(),
        ]);

        $this->artisan('marketplace:finance-backfill', [
            '--apply' => true,
            '--source' => ['tiktok_financial_transactions'],
        ])->assertExitCode(0);

        $this->assertDatabaseHas('marketplace_finance_settlements', [
            'store_id' => $store->id,
            'channel' => 'tiktok',
            'external_settlement_id' => 'tiktok_withdrawal_TT-WITHDRAW-1',
            'amount' => '50000.00',
            'status' => 'withdrawn',
        ]);
        $this->assertSame(0, MarketplaceFinancialTransaction::count());
    }
}
