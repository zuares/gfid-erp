<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
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
