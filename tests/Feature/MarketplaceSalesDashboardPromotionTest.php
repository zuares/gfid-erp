<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketplace\MarketplaceSalesDashboardController;
use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MarketplaceSalesDashboardPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_tab_includes_settlement_vouchers_in_month_comparison(): void
    {
        Queue::fake();

        $channel = Channel::create(['code' => 'SHP', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'SHP-SALES-'.uniqid(),
            'name' => 'Shopee Sales Test',
            'status' => 'active',
            'is_active' => true,
        ]);

        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'channel_order_id' => 'SHP-SALES-PROMO-001',
            'external_order_id' => 'SHP-SALES-PROMO-001',
            'purchase_channel' => 'shopee',
            'order_status' => 'COMPLETED',
            'status' => 'completed',
            'subtotal_items' => 100000,
            'total_amount' => 100000,
            'total_paid_customer' => 99700,
            'ordered_at' => '2026-10-10 10:00:00',
            'order_date' => '2026-10-10 10:00:00',
        ]);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => 99700,
            'seller_voucher' => 100,
            'raw_json' => [
                'voucher_from_seller' => 100,
                'voucher_from_shopee' => 200,
            ],
        ]);

        $view = app(MarketplaceSalesDashboardController::class)->index(Request::create(
            '/marketplace/dashboard/sales',
            'GET',
            [
                'tab' => 'sales',
                'comparison_mode' => 'month',
                'platform' => 'SHOPEE',
                'date_from' => '2026-10-01',
                'date_to' => '2026-10-24',
            ],
        ));

        $data = $view->getData();
        $promotion = collect($data['promotionDaily'])->firstWhere('day', '2026-10-10');
        $daily = collect($data['daily'])->firstWhere('day', '2026-10-10');

        $this->assertNotNull($promotion);
        $this->assertSame(100.0, (float) $promotion->voucher_store);
        $this->assertSame(200.0, (float) $promotion->voucher_platform);
        $this->assertSame(300.0, (float) $promotion->total_promotion);
        $this->assertNotNull($daily);
        $this->assertSame(100.0, (float) $daily->voucher_store);
        $this->assertSame(200.0, (float) $daily->voucher_platform);

        $promotionsView = app(MarketplaceSalesDashboardController::class)->index(Request::create(
            '/marketplace/dashboard/sales',
            'GET',
            [
                'tab' => 'promotions',
                'comparison_mode' => 'month',
                'platform' => 'SHOPEE',
                'date_from' => '2026-10-01',
                'date_to' => '2026-10-24',
            ],
        ));
        $promotionsData = $promotionsView->getData();
        $promotionsRow = collect($promotionsData['promotionDaily'])->firstWhere('day', '2026-10-10');

        $this->assertNotNull($promotionsRow);
        $this->assertSame(100.0, (float) $promotionsRow->voucher_store);
        $this->assertSame(200.0, (float) $promotionsRow->voucher_platform);
    }
}
