<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
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
