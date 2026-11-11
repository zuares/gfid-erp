<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplaceReturn;
use App\Models\MarketplaceTikTokFinancialTransaction;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceFinancialDataQualityService;
use App\Services\Marketplace\MarketplaceAnalyticsSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceAnalyticsSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_and_pending_invoice_orders_are_excluded_from_omzet_for_any_period(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'ANALYTICS-TEST',
            'name' => 'Analytics Test Store',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->order($store, 'SHIPPED', 100000);
        $this->order($store, 'READY_TO_SHIP', 500000);
        $this->order($store, 'PROCESSED', 600000);
        $this->order($store, 'UNPAID', 200000);
        $this->order($store, 'INVOICE_PENDING', 300000);
        $this->order($store, 'CANCELLED_BEFORE_SHIPPING', 400000);

        $summary = app(MarketplaceAnalyticsSummaryService::class)->summary([
            'store_id' => $store->id,
            'date_from' => '2026-09-17',
            'date_to' => '2026-09-17',
            'compare_mode' => 'prev_period',
        ]);

        $current = $summary['current'];

        $this->assertSame(6, $current['placed_order_count']);
        $this->assertSame(3, $current['order_total']);
        $this->assertSame(3, $current['cash_unsettled_order_count']);
        $this->assertSame(1200000.0, (float) $current['cash_unsettled_order_revenue']);
        $this->assertSame(948000.0, (float) $current['estimated_unsettled_payout']);
        $this->assertSame(252000.0, (float) $current['marketplace_fee_estimate']);
        $this->assertSame(1200000.0, (float) $current['gmv']);
        $this->assertSame((float) $current['estimated_profit'], (float) $current['estimated_net_profit']);
        $this->assertSame((float) $current['settled_profit'], (float) $current['realized_net_profit']);
        $this->assertSame(1, $current['cancelled_count']);
        $this->assertSame(400000.0, (float) $current['cancelled_amount']);
    }

    public function test_waiting_confirmation_order_stays_in_pending_cash_scope(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'ANALYTICS-CONFIRM',
            'name' => 'Analytics Confirm Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = $this->order($store, 'TO_CONFIRM_RECEIVE', 150000);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => 150000,
            'final_income' => 118500,
            'settlement_time' => '2026-09-17 12:00:00',
        ]);

        $summary = app(MarketplaceAnalyticsSummaryService::class)->summary([
            'store_id' => $store->id,
            'date_from' => '2026-09-17',
            'date_to' => '2026-09-17',
            'compare_mode' => 'prev_period',
        ]);

        $current = $summary['current'];

        $this->assertSame(0, $current['cash_order_count']);
        $this->assertSame(1, $current['cash_unsettled_order_count']);
        $this->assertSame(150000.0, (float) $current['cash_unsettled_order_revenue']);
    }

    public function test_cash_sales_kpis_keep_gross_sales_at_or_above_net_sales_with_platform_discount(): void
    {
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'ANALYTICS-GROSS-NET',
            'name' => 'Analytics Gross Net Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = $this->order($store, 'COMPLETED', 100000);
        $order->update(['financial_data_status' => MarketplaceFinancialDataQualityService::ORDER_READY]);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => 90000,
            'seller_voucher' => 5000,
            'final_income' => 85000,
            'settlement_time' => '2026-09-17 12:00:00',
            'data_status' => MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE,
            'raw_json' => [
                'income_details' => [
                    'order_selling_price' => 100000,
                    'voucher_from_seller' => 5000,
                ],
            ],
        ]);

        $payload = app(MarketplaceAnalyticsSummaryService::class)->cashOrders([
            'store_id' => $store->id,
            'date_from' => '2026-09-17',
            'date_to' => '2026-09-17',
            'settlement' => 'settled',
        ]);

        $summary = $payload['summary'];

        $this->assertSame(100000.0, (float) $summary['cash_gross_sales']);
        $this->assertSame(95000.0, (float) $summary['cash_order_revenue']);
        $this->assertLessThanOrEqual($summary['cash_gross_sales'], $summary['cash_order_revenue']);
    }

    public function test_products_include_eligible_orders_when_hpp_is_incomplete(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'ANALYTICS-PRODUCT',
            'name' => 'Analytics Product Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = $this->order($store, 'COMPLETED', 100000);

        MarketplaceOrderItem::create([
            'marketplace_order_id' => $order->id,
            'order_id' => $order->id,
            'item_name' => 'Produk tanpa HPP',
            'item_sku' => 'NO-HPP-001',
            'qty' => 2,
            'price' => 50000,
            'data_status' => 'valid',
        ]);
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => 100000,
            'final_income' => 90000,
            'settlement_time' => '2026-09-17 12:00:00',
        ]);

        $products = app(MarketplaceAnalyticsSummaryService::class)->products([
            'store_id' => $store->id,
            'date_from' => '2026-09-17',
            'date_to' => '2026-09-17',
        ]);

        $this->assertCount(1, $products);
        $this->assertSame('NO-HPP-001', $products[0]['sku']);
        $this->assertSame(2, $products[0]['qty']);
        $this->assertSame(100000.0, (float) $products[0]['gross_sales']);
        $this->assertSame(0.0, (float) $products[0]['hpp']);
    }

    public function test_store_ledger_includes_actual_admin_fee_for_financial_ready_orders(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'ANALYTICS-STORE-LEDGER',
            'name' => 'Analytics Store Ledger',
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = $this->order($store, 'COMPLETED', 100000);
        $order->update(['financial_data_status' => MarketplaceFinancialDataQualityService::ORDER_READY]);

        MarketplaceOrderItem::create([
            'marketplace_order_id' => $order->id,
            'order_id' => $order->id,
            'item_name' => 'Produk ledger',
            'item_sku' => 'LEDGER-001',
            'qty' => 1,
            'price' => 100000,
            'data_status' => 'valid',
            'hpp_snapshot' => 40000,
        ]);
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => 100000,
            'commission_fee' => 7500,
            'final_income' => 92500,
            'settlement_time' => '2026-09-17 12:00:00',
            'data_status' => MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE,
        ]);

        $summary = app(MarketplaceAnalyticsSummaryService::class)->summary([
            'store_id' => $store->id,
            'date_from' => '2026-09-17',
            'date_to' => '2026-09-17',
            'compare_mode' => 'prev_period',
        ]);

        $storeRow = $summary['stores'][0];

        $this->assertSame(1, $storeRow['order_total']);
        $this->assertSame(100000.0, (float) $storeRow['gross_sales']);
        $this->assertSame(7500.0, (float) $storeRow['admin_fee_actual']);
        $this->assertSame(7.5, (float) $summary['current']['marketplace_fee_rate_on_gmv']);
    }

    public function test_store_ledger_uses_platform_ad_mutation_when_ads_daily_is_empty(): void
    {
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'ANALYTICS-TIKTOK-ADS-FALLBACK',
            'name' => 'TikTok Ads Fallback',
            'status' => 'active',
            'is_active' => true,
        ]);

        MarketplaceTikTokFinancialTransaction::create([
            'store_id' => $store->id,
            'external_transaction_id' => 'TT-ADS-FALLBACK-1',
            'source' => 'statement_transactions',
            'transaction_type' => 'GMV_PAYMENT_FOR_TIKTOK_ADS',
            'amount' => 111000,
            'currency' => 'IDR',
            'money_flow' => 'MONEY_OUT',
            'transaction_created_at' => '2026-09-17 12:00:00',
            'source_payload' => [],
        ]);

        $summary = app(MarketplaceAnalyticsSummaryService::class)->summary([
            'platform' => 'tiktok',
            'store_id' => $store->id,
            'date_from' => '2026-09-17',
            'date_to' => '2026-09-17',
            'compare_mode' => 'prev_period',
        ]);

        $storeRow = $summary['stores'][0];

        $this->assertSame(111000.0, (float) $storeRow['ad_cost']);
        $this->assertSame('platform_transaction_fallback', $storeRow['ad_cost_source']);
        $this->assertSame(100000.0, (float) $storeRow['ad_cost_before_tax']);
        $this->assertSame(11000.0, (float) $storeRow['ad_cost_vat']);
    }

    public function test_tiktok_ad_cost_falls_back_to_settlement_ad_cost_when_ads_daily_is_empty(): void
    {
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'ANALYTICS-TIKTOK-SETTLEMENT-ADS',
            'name' => 'TikTok Settlement Ads',
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = $this->order($store, 'COMPLETED', 200000);
        $order->update(['financial_data_status' => MarketplaceFinancialDataQualityService::ORDER_READY]);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => 200000,
            'final_income' => 178000,
            'ad_cost' => 111000,
            'settlement_time' => '2026-09-17 12:00:00',
            'data_status' => MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE,
        ]);

        $summary = app(MarketplaceAnalyticsSummaryService::class)->summary([
            'platform' => 'tiktok',
            'store_id' => $store->id,
            'date_from' => '2026-09-17',
            'date_to' => '2026-09-17',
            'compare_mode' => 'prev_period',
        ]);

        $current = $summary['current'];
        $storeRow = $summary['stores'][0];
        $dailyRow = $summary['daily'][0];

        $this->assertSame(111000.0, (float) $current['ad_cost']);
        $this->assertSame(100000.0, (float) $current['ad_cost_before_tax']);
        $this->assertSame(11000.0, (float) $current['ad_cost_vat']);
        $this->assertSame('settlement_fallback', $current['ad_cost_source']);
        $this->assertSame(111000.0, (float) $storeRow['ad_cost']);
        $this->assertSame('settlement_fallback', $storeRow['ad_cost_source']);
        $this->assertSame(111000.0, (float) $dailyRow['ad_cost']);
    }

    public function test_tiktok_settlement_ad_cost_is_used_by_product_and_fee_tabs(): void
    {
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'ANALYTICS-TIKTOK-TABS',
            'name' => 'TikTok Other Tabs',
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = $this->order($store, 'COMPLETED', 200000);
        $order->update(['financial_data_status' => MarketplaceFinancialDataQualityService::ORDER_READY]);

        MarketplaceOrderItem::create([
            'marketplace_order_id' => $order->id,
            'order_id' => $order->id,
            'item_name' => 'Produk TikTok Ads',
            'item_sku' => 'TIKTOK-ADS-001',
            'qty' => 1,
            'price' => 200000,
            'hpp_snapshot' => 50000,
            'data_status' => 'valid',
        ]);
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => 200000,
            'final_income' => 178000,
            'ad_cost' => 111000,
            'settlement_time' => '2026-09-17 12:00:00',
            'data_status' => MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE,
        ]);

        $filters = [
            'platform' => 'tiktok',
            'store_id' => $store->id,
            'date_from' => '2026-09-17',
            'date_to' => '2026-09-17',
        ];
        $service = app(MarketplaceAnalyticsSummaryService::class);
        $products = $service->products($filters);
        $fees = $service->cashOrders($filters, 1, 50, 'settled');
        $adFee = collect($fees['fee_by_store'])->firstWhere('cost_type', 'ads');

        $this->assertSame(111000.0, (float) $products[0]['ad_cost']);
        $this->assertSame(111000.0, (float) $products[0]['ad_cost_settlement']);
        $this->assertSame(111000.0, (float) ($adFee['amount'] ?? 0));
        $this->assertSame('settlement_fallback', $adFee['source'] ?? null);
    }

    public function test_store_performance_separates_refund_from_actual_eligible_orders(): void
    {
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'ANALYTICS-TIKTOK-REFUND',
            'name' => 'TikTok Refund Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = $this->order($store, 'COMPLETED', 100000);

        MarketplaceReturn::create([
            'store_id' => $store->id,
            'return_sn' => 'RETURN-ANALYTICS-1',
            'order_sn' => $order->channel_order_id,
            'status' => 'ACCEPTED',
            'amount_before_discount' => 100000,
            'create_time' => \Carbon\Carbon::parse('2026-09-17 12:00:00')->timestamp,
            'update_time' => \Carbon\Carbon::parse('2026-09-17 12:00:00')->timestamp,
        ]);

        $summary = app(MarketplaceAnalyticsSummaryService::class)->summary([
            'platform' => 'tiktok',
            'store_id' => $store->id,
            'date_from' => '2026-09-17',
            'date_to' => '2026-09-17',
            'compare_mode' => 'prev_period',
        ]);

        $current = $summary['current'];
        $storeRow = $summary['stores'][0];

        $this->assertSame(1, $current['placed_order_count']);
        $this->assertSame(0, $current['order_total']);
        $this->assertSame(0, $current['completed_count']);
        $this->assertSame(1, $current['return_refund_order_count']);
        $this->assertSame(1, $storeRow['placed_order_count']);
        $this->assertSame(0, $storeRow['order_total']);
        $this->assertSame(0, $storeRow['completed_count']);
        $this->assertSame(1, $storeRow['return_refund_count']);
    }

    public function test_store_performance_exposes_gmv_that_is_not_settled_yet(): void
    {
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'ANALYTICS-TIKTOK-UNSETTLED',
            'name' => 'TikTok Unsettled Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = $this->order($store, 'TO_CONFIRM_RECEIVE', 150000);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => 150000,
            'final_income' => 0,
            'settlement_time' => null,
        ]);

        $summary = app(MarketplaceAnalyticsSummaryService::class)->summary([
            'platform' => 'tiktok',
            'store_id' => $store->id,
            'date_from' => '2026-09-17',
            'date_to' => '2026-09-17',
            'compare_mode' => 'prev_period',
        ]);

        $storeRow = $summary['stores'][0];

        $this->assertSame(1, $storeRow['unsettled_order_count']);
        $this->assertSame(150000.0, (float) $storeRow['unsettled_gmv']);
    }

    public function test_refund_amount_is_separate_and_not_counted_as_unsettled_gmv(): void
    {
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'ANALYTICS-TIKTOK-REFUND-PENDING',
            'name' => 'TikTok Pending Refund Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = $this->order($store, 'TO_CONFIRM_RECEIVE', 150000);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => 150000,
            'final_income' => 0,
            'settlement_time' => null,
        ]);
        MarketplaceReturn::create([
            'store_id' => $store->id,
            'return_sn' => 'RETURN-ANALYTICS-PENDING-1',
            'order_sn' => $order->channel_order_id,
            'status' => 'ACCEPTED',
            'amount_before_discount' => 150000,
            'create_time' => \Carbon\Carbon::parse('2026-09-17 12:00:00')->timestamp,
            'update_time' => \Carbon\Carbon::parse('2026-09-17 12:00:00')->timestamp,
        ]);

        $summary = app(MarketplaceAnalyticsSummaryService::class)->summary([
            'platform' => 'tiktok',
            'store_id' => $store->id,
            'date_from' => '2026-09-17',
            'date_to' => '2026-09-17',
            'compare_mode' => 'prev_period',
        ]);

        $storeRow = collect($summary['stores'])->firstWhere('store_id', $store->id);

        $this->assertSame(1, $storeRow['return_refund_count']);
        $this->assertSame(150000.0, (float) $storeRow['return_refund_amount']);
        $this->assertSame(0, $storeRow['unsettled_order_count']);
        $this->assertSame(0.0, (float) $storeRow['unsettled_gmv']);
    }

    public function test_order_eligible_completed_and_cancelled_counts_are_scoped_to_active_platform_stores(): void
    {
        $shopee = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $tiktok = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $activeShopee = Store::create([
            'channel_id' => $shopee->id,
            'code' => 'ACTIVE-SHOPEE',
            'name' => 'Active Shopee',
            'status' => 'active',
            'is_active' => true,
        ]);
        $activeTiktok = Store::create([
            'channel_id' => $tiktok->id,
            'code' => 'ACTIVE-TIKTOK',
            'name' => 'Active TikTok',
            'status' => 'active',
            'is_active' => true,
        ]);
        $inactiveShopee = Store::create([
            'channel_id' => $shopee->id,
            'code' => 'INACTIVE-SHOPEE',
            'name' => 'Inactive Shopee',
            'status' => 'inactive',
            'is_active' => false,
        ]);

        foreach ([$activeShopee, $activeTiktok, $inactiveShopee] as $store) {
            $this->order($store, 'READY_TO_SHIP', 100000);
            $this->order($store, 'COMPLETED', 200000);
            $this->order($store, 'CANCELLED', 300000);
        }

        $service = app(MarketplaceAnalyticsSummaryService::class);
        $baseFilters = [
            'date_from' => '2026-09-17',
            'date_to' => '2026-09-17',
            'compare_mode' => 'prev_period',
        ];

        $shopeeSummary = $service->summary($baseFilters + ['platform' => 'shopee']);
        $this->assertSame(2, $shopeeSummary['current']['order_total']);
        $this->assertSame(1, $shopeeSummary['current']['completed_count']);
        $this->assertSame(1, $shopeeSummary['current']['cancelled_count']);
        $this->assertSame(['Active Shopee'], collect($shopeeSummary['stores'])->pluck('store_name')->all());

        $tiktokSummary = $service->summary($baseFilters + ['platform' => 'tiktok']);
        $this->assertSame(2, $tiktokSummary['current']['order_total']);
        $this->assertSame(1, $tiktokSummary['current']['completed_count']);
        $this->assertSame(1, $tiktokSummary['current']['cancelled_count']);
        $this->assertSame(['Active TikTok'], collect($tiktokSummary['stores'])->pluck('store_name')->all());

        $allSummary = $service->summary($baseFilters);
        $this->assertSame(4, $allSummary['current']['order_total']);
        $this->assertSame(2, $allSummary['current']['completed_count']);
        $this->assertSame(2, $allSummary['current']['cancelled_count']);
        $this->assertNotContains('Inactive Shopee', collect($allSummary['stores'])->pluck('store_name')->all());
    }

    public function test_store_summary_separates_shipping_and_confirmation_orders(): void
    {
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'ANALYTICS-STATUS',
            'name' => 'Analytics Status Store',
            'status' => 'active',
            'is_active' => true,
        ]);

        foreach (['READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'READY_TO_HANDOVER', 'TO_CONFIRM_RECEIVE', 'COMPLETED', 'CANCELLED'] as $status) {
            $this->order($store, $status, 100000);
        }

        $summary = app(MarketplaceAnalyticsSummaryService::class)->summary([
            'platform' => 'tiktok',
            'store_id' => $store->id,
            'date_from' => '2026-09-17',
            'date_to' => '2026-09-17',
            'compare_mode' => 'prev_period',
        ]);

        $current = $summary['current'];
        $storeRow = collect($summary['stores'])->firstWhere('store_id', $store->id);

        $this->assertSame(4, $current['shipping_count']);
        $this->assertSame(2, $current['ready_to_ship_count']);
        $this->assertSame(2, $current['in_transit_count']);
        $this->assertSame(1, $current['confirmation_count']);
        $this->assertSame(2, $storeRow['ready_to_ship_count']);
        $this->assertSame(2, $storeRow['in_transit_count']);
        $this->assertSame(4, $storeRow['shipping_count']);
        $this->assertSame(1, $storeRow['confirmation_count']);
    }

    private function order(Store $store, string $status, float $amount): MarketplaceOrder
    {
        return MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'ANALYTICS-' . $status,
            'channel_order_id' => 'ANALYTICS-' . $status,
            'order_status' => $status,
            'status' => strtolower($status),
            'total_amount' => $amount,
            'total_paid_customer' => $amount,
            'subtotal_items' => $amount,
            'order_date' => '2026-09-17 10:00:00',
            'ordered_at' => '2026-09-17 10:00:00',
            'raw_json' => [],
        ]);
    }
}
