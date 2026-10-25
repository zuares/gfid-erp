<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderIncomeEstimate;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketplaceTikTokFinanceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsettled_transactions_are_saved_as_order_estimates_until_final_statement_exists(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::create(['code' => 'tt', 'name' => 'TikTok Shop']);
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

    public function test_order_detail_uses_tiktok_estimate_when_settlement_is_not_final(): void
    {
        $channel = Channel::create(['code' => 'tt', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-DETAIL-ESTIMATE',
            'name' => 'TikTok Detail Estimate Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'channel_order_id' => 'TT-ORDER-DETAIL-ESTIMATE-1',
            'external_order_id' => 'TT-ORDER-DETAIL-ESTIMATE-1',
            'order_status' => 'SHIPPED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'shipped_at' => Carbon::create(2026, 4, 16, 12, 0, 0, config('app.timezone')),
            'subtotal_items' => 100000,
            'total_paid_customer' => 100000,
            'total_amount' => 100000,
            'currency' => 'IDR',
            'raw_json' => [],
        ]);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'final_income' => 0,
            'data_status' => 'incomplete',
            'raw_json' => ['source' => 'tiktok_finance_202501_order_statement_transactions'],
        ]);
        MarketplaceOrderIncomeEstimate::create([
            'store_id' => $store->id,
            'marketplace_order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'estimated_escrow_amount' => 81234,
            'status_description' => 'waiting for delivery',
            'currency' => 'IDR',
            'raw_json' => [
                'transaction' => [
                    'order_create_time' => Carbon::create(2026, 4, 14, 10, 30, 0, config('app.timezone'))->timestamp,
                    'est_fee_tax_amount' => '-30',
                    'est_adjustment_amount' => '170',
                    'fee_tax_breakdown' => [
                        'fee' => ['transaction_fee_amount' => '-2'],
                        'tax' => ['sales_tax_amount' => '-10'],
                    ],
                    'shipping_cost_breakdown' => [
                        'actual_shipping_fee_amount' => '-50',
                        'customer_paid_shipping_fee_amount' => '70',
                        'shipping_fee_discount_amount' => '50',
                    ],
                ],
            ],
            'synced_at' => now(),
        ]);
        MarketplaceOrderItem::create([
            'marketplace_order_id' => $order->id,
            'order_id' => $order->id,
            'item_name' => 'TikTok test item',
            'qty' => 1,
            'line_gross_amount' => 100000,
            'hpp_unit_snapshot' => 30000,
            'hpp_total_snapshot' => 30000,
        ]);

        $response = app(\App\Http\Controllers\Marketplace\MarketplaceOrderController::class)->show($order);
        $html = $response->render();

        $this->assertStringContainsString('Estimasi Penghasilan Akhir', $html);
        $this->assertStringContainsString('Berdasarkan estimasi TikTok Finance API', $html);
        $this->assertStringContainsString('Rp81.234', $html);
        $this->assertStringContainsString('14 April 2026 10:30', $html);
        $this->assertStringContainsString('16 April 2026 12:00', $html);
        $this->assertStringContainsString('TikTok Fee - Transaction Fee', $html);
        $this->assertStringContainsString('data-fee-panel="seller"', $html);
        $this->assertStringContainsString('Adjustment TikTok', $html);
        $this->assertStringContainsString('data-fee-tab="voucher">Voucher</button>', $html);
        $this->assertStringContainsString('Total Voucher', $html);
        $this->assertStringContainsString('Laba Kotor Estimasi', $html);
        $this->assertStringContainsString('Rp51.234', $html);
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
                    'shipping_cost_amount' => '-2220',
                    'settlement_amount' => '81234',
                    'sku_transactions' => [[
                        'sku_id' => 'SKU-1',
                        'shipping_cost_breakdown' => [
                            'actual_shipping_fee_amount' => '-50000',
                            'shipping_fee_customer' => '70000',
                            'shipping_fee_discount_amount' => '50000',
                        ],
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
        $this->assertSame(50000.0, (float) $settlement->actual_shipping_fee);
        $this->assertSame(-2220.0, (float) $settlement->shipping_cost_amount);
        $this->assertSame(50000.0, (float) $settlement->shipping_fee_subsidy);
        $this->assertSame(50000.0, (float) data_get($settlement->raw_json, 'tiktok_shipping_breakdown.actual_shipping_fee_amount'));
        $this->assertSame(50000.0, (float) data_get($settlement->raw_json, 'tiktok_shipping_breakdown.shipping_fee_discount_amount'));
        $this->assertSame(70000.0, (float) data_get($settlement->raw_json, 'tiktok_shipping_breakdown.shipping_fee_customer'));
        $this->assertSame(70000.0, (float) $order->fresh()->shipping_fee_customer);
        $this->assertSame(50000.0, (float) $order->fresh()->shipping_discount_platform);
        $this->assertNull($settlement->settlement_time);
        $this->assertSame(5000.0, (float) data_get($settlement->raw_json, 'tiktok_fee_breakdown.fees.platform_commission_fee'));
        $this->assertSame(1000.0, (float) data_get($settlement->raw_json, 'tiktok_fee_breakdown.fees.transaction_fee'));
        $this->assertSame(766.0, (float) data_get($settlement->raw_json, 'tiktok_fee_breakdown.taxes.vat'));

        MarketplaceOrderItem::create([
            'marketplace_order_id' => $order->id,
            'order_id' => $order->id,
            'item_name' => 'TikTok final test item',
            'qty' => 1,
            'line_gross_amount' => 100000,
            'hpp_unit_snapshot' => 30000,
            'hpp_total_snapshot' => 30000,
        ]);
        $html = app(\App\Http\Controllers\Marketplace\MarketplaceOrderController::class)->show($order)->render();

        $this->assertStringContainsString('Penghasilan Akhir', $html);
        $this->assertStringNotContainsString('Estimasi Penghasilan Akhir', $html);
        $this->assertStringNotContainsString('Laba Kotor Estimasi', $html);
        $this->assertStringContainsString('Rp51.234', $html);
        $this->assertStringContainsString('payout final TikTok', $html);
    }

    public function test_per_order_statement_id_hydrates_missing_settlement_time_from_statement_detail(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-STATEMENT-ID-FALLBACK',
            'name' => 'TikTok Statement ID Fallback',
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
            'channel_order_id' => 'TT-ORDER-STATEMENT-ID-FALLBACK-1',
            'external_order_id' => 'TT-ORDER-STATEMENT-ID-FALLBACK-1',
            'order_status' => 'SHIPPED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'total_paid_customer' => 100000,
            'total_amount' => 100000,
            'currency' => 'IDR',
        ]);
        $statementTime = Carbon::create(2026, 10, 25, 7, 0, 0, config('app.timezone'))->timestamp;

        Http::fake([
            'https://open-api.tiktokglobalshop.com/finance/202501/orders/*/statement_transactions*' => Http::response([
                'code' => 0,
                'data' => [
                    'order_id' => $order->channel_order_id,
                    'revenue_amount' => '100000',
                    'fee_and_tax_amount' => '-10000',
                    'settlement_amount' => '90000',
                    'sku_transactions' => [[
                        'statement_id' => 'STATEMENT-ID-FALLBACK-1',
                    ]],
                ],
            ]),
            'https://open-api.tiktokglobalshop.com/finance/202501/statements/STATEMENT-ID-FALLBACK-1/statement_transactions*' => Http::response([
                'code' => 0,
                'data' => [
                    'id' => 'STATEMENT-ID-FALLBACK-1',
                    'status' => 'SETTLED',
                    'create_time' => $statementTime,
                    'transactions' => [[
                        'type' => 'ORDER',
                        'order_id' => $order->channel_order_id,
                        'settlement_amount' => '90000',
                    ]],
                ],
            ]),
        ]);

        $result = app(MarketplaceSyncService::class)->syncTikTokOrderFinance($store, $order);

        $this->assertSame('synced', $result['status']);
        $settlement = MarketplaceOrderSettlement::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(
            Carbon::createFromTimestamp($statementTime, config('app.timezone'))->toDateTimeString(),
            $settlement->settlement_time?->toDateTimeString(),
        );
        $this->assertSame('STATEMENT-ID-FALLBACK-1', data_get($settlement->raw_json, 'tiktok_statement.statement_id'));
        $this->assertSame('SETTLED', data_get($settlement->raw_json, 'tiktok_statement.payment_status'));
        $this->assertSame($statementTime, data_get($settlement->raw_json, 'tiktok_statement.statement_time'));
    }

    public function test_equal_affiliate_before_pit_field_is_not_counted_twice(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-AFFILIATE-DEDUPE',
            'name' => 'TikTok Affiliate Dedupe Test',
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
            'channel_order_id' => 'TT-ORDER-AFFILIATE-DEDUPE-1',
            'external_order_id' => 'TT-ORDER-AFFILIATE-DEDUPE-1',
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
                    'order_id' => 'TT-ORDER-AFFILIATE-DEDUPE-1',
                    'currency' => 'IDR',
                    'revenue_amount' => '100000',
                    'fee_and_tax_amount' => '-22416',
                    'settlement_amount' => '77584',
                    'sku_transactions' => [[
                        'fee_tax_breakdown' => [
                            'fee' => [
                                'platform_commission_amount' => '-5000',
                                'affiliate_commission_amount' => '-17416',
                                'affiliate_commission_amount_before_pit' => '-17416',
                            ],
                            'tax' => [],
                        ],
                    ]],
                ],
            ]),
        ]);

        $result = app(MarketplaceSyncService::class)->syncTikTokOrderFinance($store, $order);

        $this->assertSame('synced', $result['status']);
        $settlement = MarketplaceOrderSettlement::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(22416.0, (float) $settlement->commission_fee);
        $this->assertSame(17416.0, (float) data_get($settlement->raw_json, 'tiktok_fee_breakdown.fees.affiliate_commission_amount'));
        $this->assertNull(data_get($settlement->raw_json, 'tiktok_fee_breakdown.fees.affiliate_commission_amount_before_pit'));
    }

    public function test_historical_affiliate_fee_backfill_is_dry_run_by_default_and_idempotent(): void
    {
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-AFFILIATE-BACKFILL',
            'name' => 'TikTok Affiliate Backfill Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'channel_order_id' => 'TT-ORDER-AFFILIATE-BACKFILL-1',
            'external_order_id' => 'TT-ORDER-AFFILIATE-BACKFILL-1',
            'order_status' => 'COMPLETED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'total_paid_customer' => 100000,
            'total_amount' => 100000,
            'currency' => 'IDR',
        ]);
        $settlement = MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'commission_fee' => 34832,
            'escrow_tax' => 0,
            'final_income' => 65168,
            'data_status' => 'complete',
            'raw_json' => [
                'source' => 'tiktok_finance_202501_order_statement_transactions',
                'tiktok_fee_breakdown' => [
                    'fees' => [
                        'affiliate_commission_amount' => 17416,
                        'affiliate_commission_amount_before_pit' => 17416,
                    ],
                    'fee_total' => 34832,
                    'non_ad_fee_total' => 34832,
                ],
            ],
        ]);

        $service = app(MarketplaceSyncService::class);
        $dryRun = $service->backfillTikTokAffiliateFeeDuplicates($store);
        $this->assertSame(1, $dryRun['candidates']);
        $this->assertSame(1, $dryRun['updated']);
        $this->assertSame(34832.0, (float) MarketplaceOrderSettlement::findOrFail($settlement->id)->commission_fee);

        $applied = $service->backfillTikTokAffiliateFeeDuplicates($store, true);
        $this->assertSame(1, $applied['updated']);
        $settlement->refresh();
        $this->assertSame(17416.0, (float) $settlement->commission_fee);
        $this->assertNull(data_get($settlement->raw_json, 'tiktok_fee_breakdown.fees.affiliate_commission_amount_before_pit'));
        $this->assertSame(17416.0, (float) data_get($settlement->raw_json, 'tiktok_fee_breakdown.fee_total'));

        $secondApply = $service->backfillTikTokAffiliateFeeDuplicates($store, true);
        $this->assertSame(0, $secondApply['candidates']);
    }

    public function test_batched_sync_uses_statement_transactions_for_final_order_settlement(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');
        $paymentTime = now()->subHour()->timestamp;

        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-STATEMENT-BATCH',
            'name' => 'TikTok Statement Batch Test',
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
            'channel_order_id' => 'TT-ORDER-STATEMENT-BATCH-1',
            'external_order_id' => 'TT-ORDER-STATEMENT-BATCH-1',
            'order_status' => 'COMPLETED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'subtotal_items' => 100000,
            'total_paid_customer' => 100000,
            'total_amount' => 100000,
            'currency' => 'IDR',
        ]);
        // A per-order response can already be data-complete while the
        // statement payment timestamp is still missing. The statement sync
        // must still count this as a newly finalized payout.
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'final_income' => 94000,
            'settlement_time' => null,
            'synced_at' => now()->subHours(2),
            'data_status' => 'complete',
            'raw_json' => [
                'source' => 'tiktok_finance_202501_order_statement_transactions',
                'order_id' => $order->channel_order_id,
                'sku_transactions' => [['statement_id' => 'STATEMENT-BATCH-1']],
            ],
        ]);

        Http::fake([
            'https://open-api.tiktokglobalshop.com/finance/202507/orders/unsettled*' => Http::response([
                'code' => 0,
                'data' => ['transactions' => [], 'next_page_token' => ''],
            ]),
            'https://open-api.tiktokglobalshop.com/finance/202501/orders/*/statement_transactions*' => Http::response([
                'code' => 0,
                'data' => [
                    'order_id' => 'TT-ORDER-STATEMENT-BATCH-1',
                    'sku_transactions' => [],
                ],
            ]),
            'https://open-api.tiktokglobalshop.com/finance/202309/statements*' => Http::response([
                'code' => 0,
                'data' => [
                    'statements' => [[
                        'id' => 'STATEMENT-BATCH-1',
                        'payment_id' => 'PAYMENT-BATCH-1',
                        'payment_status' => 'SETTLED',
                        'statement_time' => now()->subDays(5)->timestamp,
                        'payment_time' => $paymentTime,
                    ]],
                    'next_page_token' => '',
                ],
            ]),
            'https://open-api.tiktokglobalshop.com/finance/202501/statements/STATEMENT-BATCH-1/statement_transactions*' => Http::response([
                'code' => 0,
                'data' => [
                    'next_page_token' => '',
                    'transactions' => [
                        [
                            'id' => 'TX-ORDER-BATCH-1',
                            'type' => 'ORDER',
                            'order_id' => 'TT-ORDER-STATEMENT-BATCH-1',
                            'currency' => 'IDR',
                            'revenue_amount' => '100000',
                            'fee_tax_amount' => '6000',
                            'shipping_cost_amount' => '0',
                            'settlement_amount' => '94000',
                            'fee_tax_breakdown' => [
                                'fee' => ['platform_commission_amount' => '-5000'],
                                'tax' => ['vat' => '-1000'],
                            ],
                        ],
                        [
                            'id' => 'TX-ADJUSTMENT-1',
                            'type' => 'PLATFORM_PENALTY',
                            'adjustment_amount' => '-500',
                        ],
                    ],
                ],
            ]),
        ]);

        $result = app(MarketplaceSyncService::class)->syncTikTokFinanceBatched(
            $store,
            now()->subDays(2)->timestamp,
            now()->timestamp,
            false,
            null,
            200,
            now()->subDays(7)->timestamp,
        );

        $this->assertSame(1, $result['statement_settlements_synced']);
        $this->assertSame(1, $result['synced']);
        $this->assertSame(0, $result['statement_settlement_errors']);
        $this->assertDatabaseHas('marketplace_order_settlements', [
            'order_id' => $order->id,
            'channel_order_id' => 'TT-ORDER-STATEMENT-BATCH-1',
            'final_income' => '94000.00',
            'data_status' => 'complete',
        ]);
        $settlement = MarketplaceOrderSettlement::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('STATEMENT-BATCH-1', data_get($settlement->raw_json, 'tiktok_statement.statement_id'));
        $this->assertSame('TX-ORDER-BATCH-1', data_get($settlement->raw_json, 'tiktok_statement.statement_transaction_id'));
        $this->assertSame('PAYMENT-BATCH-1', data_get($settlement->raw_json, 'tiktok_statement.payment_id'));
        $this->assertSame('SETTLED', data_get($settlement->raw_json, 'tiktok_statement.payment_status'));
        $this->assertSame(
            Carbon::createFromTimestamp($paymentTime, config('app.timezone'))->toDateTimeString(),
            $settlement->settlement_time?->toDateTimeString(),
        );
        $this->assertSame($paymentTime, data_get($settlement->raw_json, 'tiktok_statement.payment_time'));
    }

    public function test_backfill_replaces_legacy_sync_timestamp_with_statement_payment_time(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-BACKFILL-DATE',
            'name' => 'TikTok Backfill Date Test',
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
            'channel_order_id' => 'TT-ORDER-BACKFILL-DATE-1',
            'external_order_id' => 'TT-ORDER-BACKFILL-DATE-1',
            'order_status' => 'COMPLETED',
            'ordered_at' => now()->subDays(10),
            'order_date' => now()->subDays(10),
            'subtotal_items' => 100000,
            'total_paid_customer' => 100000,
            'total_amount' => 100000,
            'currency' => 'IDR',
        ]);
        $legacySyncTime = Carbon::create(2026, 10, 24, 5, 5, 21, config('app.timezone'));
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'final_income' => 65364,
            'settlement_time' => $legacySyncTime,
            'synced_at' => $legacySyncTime,
            'data_status' => 'complete',
            'raw_json' => [
                'source' => 'tiktok_finance_202501_order_statement_transactions',
                'order_id' => $order->channel_order_id,
                'sku_transactions' => [['statement_id' => 'STATEMENT-BACKFILL-1']],
            ],
        ]);
        $paymentTime = Carbon::create(2026, 9, 13, 8, 0, 10, config('app.timezone'))->timestamp;

        Http::fake([
            'https://open-api.tiktokglobalshop.com/finance/202309/statements*' => Http::response([
                'code' => 0,
                'data' => [
                    'statements' => [[
                        'id' => 'STATEMENT-BACKFILL-1',
                        'statement_time' => Carbon::create(2026, 9, 13, 7, 0, 0, config('app.timezone'))->timestamp,
                        'payment_time' => $paymentTime,
                        'payment_status' => 'SETTLED',
                    ]],
                    'next_page_token' => '',
                ],
            ]),
        ]);

        $stats = app(MarketplaceSyncService::class)->backfillTikTokSettlementTimes(
            $store,
            Carbon::create(2026, 9, 1, 0, 0, 0, config('app.timezone')),
            Carbon::create(2026, 10, 1, 23, 59, 59, config('app.timezone')),
            true,
        );

        $this->assertSame(1, $stats['candidates']);
        $this->assertSame(1, $stats['matched']);
        $this->assertSame(1, $stats['updated']);

        $settlement = MarketplaceOrderSettlement::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('2026-09-13 08:00:10', $settlement->settlement_time?->toDateTimeString());
        $this->assertSame($paymentTime, data_get($settlement->raw_json, 'tiktok_statement.payment_time'));
        $this->assertTrue((bool) data_get($settlement->raw_json, 'tiktok_settlement_backfill'));
        $this->assertTrue((bool) data_get($settlement->data_quality_flags, 'settlement_time_backfilled'));
    }

    public function test_backfill_fills_missing_statement_time_for_complete_per_order_row(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-BACKFILL-MISSING-DATE',
            'name' => 'TikTok Missing Date Test',
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
            'channel_order_id' => 'TT-ORDER-BACKFILL-MISSING-DATE-1',
            'external_order_id' => 'TT-ORDER-BACKFILL-MISSING-DATE-1',
            'order_status' => 'TO_CONFIRM_RECEIVE',
            'ordered_at' => now()->subDays(2),
            'order_date' => now()->subDays(2),
            'subtotal_items' => 100000,
            'total_paid_customer' => 100000,
            'total_amount' => 100000,
            'currency' => 'IDR',
        ]);
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'final_income' => 76534,
            'settlement_time' => null,
            'synced_at' => now()->subHour(),
            'data_status' => 'complete',
            'raw_json' => [
                'source' => 'tiktok_finance_202501_order_statement_transactions',
                'order_id' => $order->channel_order_id,
                'sku_transactions' => [['statement_id' => 'STATEMENT-MISSING-DATE-1']],
            ],
        ]);
        $paymentTime = Carbon::create(2026, 10, 25, 8, 36, 47, config('app.timezone'))->timestamp;

        Http::fake([
            'https://open-api.tiktokglobalshop.com/finance/202309/statements*' => Http::response([
                'code' => 0,
                'data' => [
                    'statements' => [],
                    'next_page_token' => '',
                ],
            ]),
            'https://open-api.tiktokglobalshop.com/finance/202501/statements/STATEMENT-MISSING-DATE-1/statement_transactions*' => Http::response([
                'code' => 0,
                'data' => [
                    'id' => 'STATEMENT-MISSING-DATE-1',
                    'status' => 'SETTLED',
                    'create_time' => $paymentTime,
                    'transactions' => [[
                        'type' => 'ORDER',
                        'order_id' => $order->channel_order_id,
                        'settlement_amount' => '76534',
                    ]],
                ],
            ]),
        ]);

        $stats = app(MarketplaceSyncService::class)->backfillTikTokSettlementTimes(
            $store,
            Carbon::create(2026, 10, 25, 0, 0, 0, config('app.timezone')),
            Carbon::create(2026, 10, 26, 23, 59, 59, config('app.timezone')),
            true,
        );

        $this->assertSame(1, $stats['candidates']);
        $this->assertSame(1, $stats['matched']);
        $this->assertSame(1, $stats['updated']);

        $settlement = MarketplaceOrderSettlement::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('2026-10-25 08:36:47', $settlement->settlement_time?->toDateTimeString());
        $this->assertSame('SETTLED', data_get($settlement->raw_json, 'tiktok_statement.payment_status'));
        $this->assertSame($paymentTime, data_get($settlement->raw_json, 'tiktok_statement.statement_time'));
    }

    public function test_statement_transactions_supply_gmv_max_cost_from_settlement(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-GMV-MAX-STATEMENT',
            'name' => 'TikTok GMV Max Statement Test',
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
            'channel_order_id' => 'TT-ORDER-GMV-MAX-1',
            'external_order_id' => 'TT-ORDER-GMV-MAX-1',
            'order_status' => 'COMPLETED',
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
                'data' => ['transactions' => [], 'next_page_token' => ''],
            ]),
            'https://open-api.tiktokglobalshop.com/finance/202501/orders/*/statement_transactions*' => Http::response([
                'code' => 0,
                'data' => [
                    'order_id' => 'TT-ORDER-GMV-MAX-1',
                    'currency' => 'IDR',
                    'revenue_amount' => '100000',
                    'fee_and_tax_amount' => '-5000',
                    'shipping_cost_amount' => '0',
                    'settlement_amount' => '95000',
                    'sku_transactions' => [[
                        'sku_id' => 'SKU-1',
                        'fee_tax_breakdown' => [
                            'fee' => ['platform_commission_amount' => '-5000'],
                            'tax' => [],
                        ],
                    ]],
                ],
            ]),
            'https://open-api.tiktokglobalshop.com/finance/202309/statements*' => Http::response([
                'code' => 0,
                'data' => [
                    'statements' => [[
                        'id' => 'STATEMENT-GMV-MAX-1',
                    ]],
                    'next_page_token' => '',
                ],
            ]),
            'https://open-api.tiktokglobalshop.com/finance/202501/statements/STATEMENT-GMV-MAX-1/statement_transactions*' => Http::response([
                'code' => 0,
                'data' => [
                    'transactions' => [[
                        'id' => 'TRANSACTION-GMV-MAX-1',
                        'type' => 'ORDER',
                        'associated_order_id' => 'TT-ORDER-GMV-MAX-1',
                        'currency' => 'IDR',
                        'fee_tax_breakdown' => [
                            'fee' => [
                                'gmv_max_ad_fee_amount' => '-3000',
                                'gmv_max_coupon_fee' => '-500',
                                'platform_commission_amount' => '-5000',
                            ],
                            'tax' => [
                                'cps_shop_ads_commission_tax_amount' => '-55',
                            ],
                        ],
                    ]],
                    'next_page_token' => '',
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

        $this->assertSame(1, $result['statement_ad_costs_updated']);
        $this->assertSame(3555.0, (float) $result['statement_ad_costs_total']);

        $settlement = MarketplaceOrderSettlement::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(3555.0, (float) $settlement->ad_cost);
        $this->assertSame('STATEMENT-GMV-MAX-1', data_get($settlement->raw_json, 'tiktok_statement.statement_id'));
        $this->assertSame(3000.0, (float) data_get($settlement->raw_json, 'tiktok_statement_gmv_max.ad_fee'));
        $this->assertSame(500.0, (float) data_get($settlement->raw_json, 'tiktok_statement_gmv_max.coupon_fee'));
        $this->assertSame(55.0, (float) data_get($settlement->raw_json, 'tiktok_statement_gmv_max.ad_tax'));
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
