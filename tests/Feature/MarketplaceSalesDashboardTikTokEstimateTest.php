<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketplace\MarketplaceSalesDashboardController;
use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderIncomeEstimate;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MarketplaceSalesDashboardTikTokEstimateTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiktok_dashboard_prefers_unsettled_estimate_until_final_statement_is_complete(): void
    {
        Queue::fake();

        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'TIKTOK-SALES-'.uniqid(),
            'name' => 'TikTok Sales Test',
            'status' => 'active',
            'is_active' => true,
        ]);

        $pendingOrder = $this->createOrder($store, 'TIKTOK-PENDING-001', '2026-09-10 10:00:00');
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $pendingOrder->id,
            'channel_order_id' => $pendingOrder->channel_order_id,
            'buyer_payment_amount' => 100000,
            'final_income' => 65000,
            'data_status' => 'incomplete',
        ]);
        MarketplaceOrderIncomeEstimate::create([
            'store_id' => $store->id,
            'marketplace_order_id' => $pendingOrder->id,
            'channel_order_id' => $pendingOrder->channel_order_id,
            'income_status' => 2,
            'estimated_escrow_amount' => 82000,
            'synced_at' => '2026-09-11 10:00:00',
        ]);

        $finalOrder = $this->createOrder($store, 'TIKTOK-FINAL-001', '2026-09-11 10:00:00');
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $finalOrder->id,
            'channel_order_id' => $finalOrder->channel_order_id,
            'buyer_payment_amount' => 100000,
            'final_income' => 76000,
            'data_status' => 'complete',
        ]);
        MarketplaceOrderIncomeEstimate::create([
            'store_id' => $store->id,
            'marketplace_order_id' => $finalOrder->id,
            'channel_order_id' => $finalOrder->channel_order_id,
            'income_status' => 2,
            'estimated_escrow_amount' => 84000,
            'synced_at' => '2026-09-12 10:00:00',
        ]);

        $view = app(MarketplaceSalesDashboardController::class)->index(Request::create(
            '/marketplace/dashboard/sales',
            'GET',
            [
                '_sales_export' => 1,
                'platform' => 'TIKTOK',
                'purchase_channel' => 'tiktok',
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-30',
            ],
        ));

        $data = $view->getData();
        $daily = collect($data['daily'])->keyBy('day');

        $this->assertSame(82000.0, (float) $daily['2026-09-10']->estimated_payout);
        $this->assertSame(76000.0, (float) $daily['2026-09-11']->estimated_payout);
        $this->assertSame(1, $data['incomeSummary']['settled_orders']);
        $this->assertSame(1, $data['incomeSummary']['pending_orders']);
        $this->assertSame(76000.0, (float) $data['incomeSummary']['final_income']);
    }

    private function createOrder(Store $store, string $orderId, string $orderedAt): MarketplaceOrder
    {
        return MarketplaceOrder::create([
            'store_id' => $store->id,
            'channel_order_id' => $orderId,
            'external_order_id' => $orderId,
            'purchase_channel' => 'tiktok',
            'order_status' => 'COMPLETED',
            'status' => 'completed',
            'subtotal_items' => 100000,
            'total_amount' => 100000,
            'total_paid_customer' => 100000,
            'ordered_at' => $orderedAt,
            'order_date' => $orderedAt,
        ]);
    }
}
