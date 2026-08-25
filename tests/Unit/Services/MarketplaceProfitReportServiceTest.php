<?php

namespace Tests\Unit\Services;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceProfitReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceProfitReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_uses_actual_payout_and_does_not_double_deduct_marketplace_fees(): void
    {
        $store = $this->store();
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'PROFIT-001',
            'channel_order_id' => 'PROFIT-001',
            'order_date' => '2026-08-04 10:00:00',
            'ordered_at' => '2026-08-04 10:00:00',
            'order_status' => 'COMPLETED',
            'financial_data_status' => 'ready',
        ]);

        MarketplaceOrderItem::create([
            'order_id' => $order->id,
            'marketplace_order_id' => $order->id,
            'line_no' => 1,
            'item_name' => 'Produk Profit',
            'model_sku' => 'SKU-PROFIT',
            'qty' => 2,
            'price' => 150,
            'hpp_snapshot' => 50,
            'data_status' => 'valid',
        ]);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => 'PROFIT-001',
            'buyer_payment_amount' => 300,
            'commission_fee' => 30,
            'service_fee' => 10,
            'transaction_fee' => 5,
            'seller_voucher' => 20,
            'final_income' => 255,
            'ad_cost' => 15,
            'data_status' => 'complete',
            'raw_json' => [],
        ]);

        $report = app(MarketplaceProfitReportService::class)->report([
            'store_id' => $store->id,
            'date_basis' => 'ordered_at',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
        ]);

        $this->assertSame(1, $report['summary']['order_count']);
        $this->assertSame(300.0, $report['summary']['gross_sales']);
        $this->assertSame(255.0, $report['summary']['payout']);
        $this->assertSame(100.0, $report['summary']['hpp']);
        $this->assertSame(45.0, $report['summary']['marketplace_fees']);
        $this->assertSame(155.0, $report['summary']['gross_profit']);
        $this->assertSame(140.0, $report['summary']['operating_profit']);
    }

    public function test_incomplete_orders_are_visible_in_quality_counts_but_excluded_from_profit(): void
    {
        $store = $this->store();
        MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'PROFIT-INCOMPLETE-001',
            'channel_order_id' => 'PROFIT-INCOMPLETE-001',
            'order_date' => '2026-08-04 10:00:00',
            'ordered_at' => '2026-08-04 10:00:00',
            'order_status' => 'COMPLETED',
            'financial_data_status' => 'incomplete',
            'financial_issue_reason' => 'settlement_missing',
        ]);

        $report = app(MarketplaceProfitReportService::class)->report([
            'store_id' => $store->id,
            'date_basis' => 'ordered_at',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
        ]);

        $this->assertSame(1, $report['quality']['total']);
        $this->assertSame(1, $report['quality']['incomplete']);
        $this->assertSame(0, $report['summary']['order_count']);
        $this->assertSame(0.0, $report['summary']['operating_profit']);
        $this->assertSame('settlement_missing', $report['quality']['issues'][0]['reason']);
    }

    public function test_non_completed_orders_are_excluded_from_financial_quality_counts(): void
    {
        $store = $this->store();
        MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'PROFIT-PENDING-001',
            'channel_order_id' => 'PROFIT-PENDING-001',
            'order_date' => '2026-08-04 10:00:00',
            'ordered_at' => '2026-08-04 10:00:00',
            'order_status' => 'SHIPPED',
            'financial_data_status' => 'unknown',
        ]);

        $report = app(MarketplaceProfitReportService::class)->report([
            'store_id' => $store->id,
            'date_basis' => 'ordered_at',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
        ]);

        $this->assertSame(0, $report['quality']['total']);
        $this->assertSame(0, $report['quality']['unknown']);
        $this->assertSame(0, $report['summary']['order_count']);
    }

    private function store(): Store
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => 'PROFIT-' . uniqid(),
            'name' => 'Profit Test Store',
            'status' => 'active',
            'is_active' => true,
        ]);
    }
}
