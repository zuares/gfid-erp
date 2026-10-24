<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureModuleAccess;
use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceReturnsTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            Authenticate::class,
            EnsureModuleAccess::class,
        ]);

        $channel = Channel::create([
            'code' => 'tiktok',
            'name' => 'TikTok Shop',
        ]);

        $this->store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'TTK-RETURNS',
            'name' => 'TikTok Returns Test',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'test-token'],
            'token_expires_at' => now()->addDay(),
        ]);
    }

    private function createOrder(string $orderSn, string $orderStatus, string $legacyStatus): MarketplaceOrder
    {
        return MarketplaceOrder::create([
            'store_id' => $this->store->id,
            'external_order_id' => $orderSn,
            'channel_order_id' => $orderSn,
            'order_date' => now(),
            'ordered_at' => now(),
            'order_status' => $orderStatus,
            'status' => $legacyStatus,
            'total_amount' => 99073,
            'meta' => [
                'has_return_request' => true,
                'buyer_dispute_reason' => 'Pengiriman paket gagal (SYSTEM)',
            ],
        ]);
    }

    public function test_cancelled_tiktok_order_is_only_listed_in_cancelled_results(): void
    {
        $order = $this->createOrder('TTK-CANCELLED-001', 'CANCELLED', 'cancelled');

        $this->getJson('/api/marketplace/returns/stored?type=return&search='.$order->channel_order_id)
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/marketplace/returns/stored?type=cancel&search='.$order->channel_order_id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kind', 'cancel')
            ->assertJsonPath('data.0.status', 'CANCELLED');
    }

    public function test_non_cancelled_tiktok_dispute_can_still_appear_as_return(): void
    {
        $order = $this->createOrder('TTK-DISPUTE-001', 'TO_CONFIRM_RECEIVE', 'shipped');

        $this->getJson('/api/marketplace/returns/stored?type=return&search='.$order->channel_order_id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kind', 'return')
            ->assertJsonPath('data.0.status', 'DISPUTE_REQUESTED');
    }
}
