<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderIncomeEstimate;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketplaceTikTokFinanceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsettled_transactions_are_saved_as_order_estimates_until_final_statement_exists(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-FINANCE-SYNC',
            'name' => 'TikTok Finance Sync Test',
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
            'channel_order_id' => 'TT-ORDER-ESTIMATE-1',
            'external_order_id' => 'TT-ORDER-ESTIMATE-1',
            'order_status' => 'SHIPPED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'subtotal_items' => 100000,
            'total_paid_customer' => 100000,
            'total_amount' => 100000,
            'currency' => 'IDR',
        ]);

        Http::fake([
            'https://open-api.tiktokglobalshop.com/finance/202507/orders/unsettled*' => Http::response([
                'code' => 0,
                'data' => [
                    'next_page_token' => '',
                    'transactions' => [[
                        'type' => 'ORDER',
                        'id' => 'TT-TRANSACTION-1',
                        'status' => 'UNSETTLED',
                        'currency' => 'IDR',
                        'estimated_settlement' => now()->addDays(2)->timestamp,
                        'unsettled_reason' => 'waiting for delivery',
                        'order_create_time' => now()->subDay()->timestamp,
                        'order_id' => 'TT-ORDER-ESTIMATE-1',
                        'est_settlement_amount' => '81234',
                    ]],
                ],
            ]),
            'https://open-api.tiktokglobalshop.com/finance/202501/orders/*/statement_transactions*' => Http::response([
                'code' => 0,
                'data' => [
                    'order_id' => 'TT-ORDER-ESTIMATE-1',
                    'currency' => 'IDR',
                    'revenue_amount' => '100000',
                    'fee_and_tax_amount' => '-18766',
                    'shipping_cost_amount' => '0',
                    'settlement_amount' => '81234',
                    'sku_transactions' => [],
                ],
            ]),
        ]);

        $result = app(MarketplaceSyncService::class)->syncTikTokFinance(
            $store,
            now()->subDays(2)->timestamp,
            now()->timestamp,
            false,
            null,
            200,
        );

        $this->assertSame(1, $result['unsettled_found']);
        $this->assertSame(1, $result['estimates_updated']);
        $this->assertSame(1, $result['pending']);
        $this->assertDatabaseHas('marketplace_order_income_estimates', [
            'marketplace_order_id' => $order->id,
            'store_id' => $store->id,
            'channel_order_id' => 'TT-ORDER-ESTIMATE-1',
            'estimated_escrow_amount' => '81234.00',
            'status_description' => 'waiting for delivery',
        ]);

        $estimate = MarketplaceOrderIncomeEstimate::where('marketplace_order_id', $order->id)->firstOrFail();
        $this->assertSame('tiktok_finance_202507_orders_unsettled', $estimate->raw_json['source']);
    }

    public function test_final_statement_preserves_tiktok_fee_and_tax_breakdown(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-FEE-BREAKDOWN',
            'name' => 'TikTok Fee Breakdown Test',
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
            'channel_order_id' => 'TT-ORDER-FEE-BREAKDOWN-1',
            'external_order_id' => 'TT-ORDER-FEE-BREAKDOWN-1',
            'order_status' => 'COMPLETED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'subtotal_items' => 100000,
            'total_paid_customer' => 100000,
            'total_amount' => 100000,
            'currency' => 'IDR',
        ]);

        Http::fake([
            'https://open-api.tiktokglobalshop.com/finance/202501/orders/*/statement_transactions*' => Http::response([
                'code' => 0,
                'data' => [
                    'order_id' => 'TT-ORDER-FEE-BREAKDOWN-1',
                    'currency' => 'IDR',
                    'revenue_amount' => '100000',
                    'fee_and_tax_amount' => '-18766',
                    'shipping_cost_amount' => '0',
                    'settlement_amount' => '81234',
                    'sku_transactions' => [[
                        'sku_id' => 'SKU-1',
                        'fee_tax_breakdown' => [
                            'fee' => [
                                'platform_commission_fee' => '-5000',
                                'transaction_fee' => '-1000',
                                'service_fee' => '-2000',
                            ],
                            'tax' => [
                                'vat' => '-766',
                            ],
                        ],
                    ]],
                ],
            ]),
        ]);

        $result = app(MarketplaceSyncService::class)->syncTikTokOrderFinance($store, $order);

        $this->assertSame('synced', $result['status']);
        $settlement = MarketplaceOrderSettlement::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(8000.0, (float) $settlement->commission_fee);
        $this->assertSame(766.0, (float) $settlement->escrow_tax);
        $this->assertSame(8766.0, $settlement->totalDeductions());
        $this->assertSame(5000.0, (float) data_get($settlement->raw_json, 'tiktok_fee_breakdown.fees.platform_commission_fee'));
        $this->assertSame(1000.0, (float) data_get($settlement->raw_json, 'tiktok_fee_breakdown.fees.transaction_fee'));
        $this->assertSame(766.0, (float) data_get($settlement->raw_json, 'tiktok_fee_breakdown.taxes.vat'));
    }

    public function test_legacy_tiktok_payload_does_not_double_count_aggregate_fee(): void
    {
        $settlement = new MarketplaceOrderSettlement([
            'commission_fee' => 33187,
            'escrow_tax' => 0,
            'raw_json' => [
                'source' => 'tiktok_finance_202501_order_statement_transactions',
                'fee_and_tax_amount' => '-33187',
                'fee_tax_amount' => '739',
                'sku_transactions' => [[
                    'fee_tax_breakdown' => [
                        'fee' => ['platform_commission_amount' => '-33187'],
                        'tax' => ['sales_tax' => '-739'],
                    ],
                ]],
            ],
        ]);

        $this->assertSame(33926.0, $settlement->totalDeductions());
    }
}
