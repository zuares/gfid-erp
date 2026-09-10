<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureModuleAccess;
use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketplaceOrdersPendingTest extends TestCase
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

        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $this->store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'PENDING-STORE',
            'name' => 'Toko Pending',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'test-token'],
            'token_expires_at' => now()->addDay(),
        ]);

        $legacyChannelId = DB::table('marketplace_channels')->insertGetId([
            'code' => 'shopee',
            'name' => 'Shopee',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('marketplace_stores')->insert([
            'id' => $this->store->id,
            'channel_id' => $legacyChannelId,
            'name' => $this->store->name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrder(string $orderSn, string $status, string $username): MarketplaceOrder
    {
        return MarketplaceOrder::create([
            'store_id' => $this->store->id,
            'external_order_id' => $orderSn,
            'channel_order_id' => $orderSn,
            'order_date' => now(),
            'ordered_at' => now(),
            'order_status' => $status,
            'buyer_username' => $username,
        ]);
    }

    public function test_pending_orders_are_filtered_before_pagination_and_counted_across_pages(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $ready = $this->createOrder("READY-{$i}", 'READY_TO_SHIP', 'buyer');
            if ($i === 0) {
                $ready->update(['raw_json' => ['package_list' => [['logistics_status' => 'LOGISTICS_NOT_START']]]]);
            }
            $order = $this->createOrder("PENDING-{$i}", 'PENDING', 'buyer');
            $order->update(['raw_json' => ['package_list' => [['logistics_status' => 'LOGISTICS_NOT_START']]]]);
        }

        foreach ([1, 2, 3] as $page) {
            $this->getJson("/api/marketplace/local-orders-paginated?tab=ready&sub_tab=pending&limit=1&page={$page}")
                ->assertOk()->assertJsonPath('total', 3)->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.platform_pending', true)
                ->assertJsonPath('data.0.logistics_status', 'LOGISTICS_NOT_START');
        }

        $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&sub_tab=process&limit=1')
            ->assertOk()->assertJsonPath('total', 3)->assertJsonPath('data.0.platform_pending', false);
        $this->getJson('/api/marketplace/local-order-counts')
            ->assertOk()->assertJsonPath('ready', 6)
            ->assertJsonPath('ready_pending', 3)->assertJsonPath('ready_process', 3);
        $this->getJson('/api/marketplace/local-order-counts?search=PENDING-')
            ->assertOk()->assertJsonPath('ready_pending', 3)->assertJsonPath('ready_process', 0);
    }

    public function test_pending_status_and_root_logistics_status_are_supported(): void
    {
        $this->createOrder('STATUS-PENDING', 'PENDING', 'buyer');
        $root = $this->createOrder('ROOT-PENDING', 'READY_TO_SHIP', 'buyer');
        $root->update(['raw_json' => ['order_status' => 'PENDING', 'logistics_status' => 'LOGISTICS_NOT_START']]);
        $raw = $this->createOrder('RAW-PENDING', 'MATCHED', 'buyer');
        $raw->update(['raw_json' => ['order_status' => 'PENDING']]);

        $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&sub_tab=pending')
            ->assertOk()->assertJsonPath('total', 3)
            ->assertJsonPath('data.0.platform_pending', true)
            ->assertJsonPath('data.1.platform_pending', true)
            ->assertJsonPath('data.2.platform_pending', true);
        $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&sub_tab=all')
            ->assertOk()->assertJsonPath('total', 3);
    }

    public function test_order_returns_to_process_when_logistics_becomes_ready(): void
    {
        $order = $this->createOrder('BECOMES-READY', 'PENDING', 'buyer');
        $order->update(['raw_json' => ['order_status' => 'PENDING', 'package_list' => [['logistics_status' => 'LOGISTICS_NOT_START']]]]);
        $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&sub_tab=pending')
            ->assertOk()->assertJsonPath('total', 1);

        // Status logistik yang siap mengalahkan status PENDING lama pada payload.
        $order->update(['raw_json' => ['order_status' => 'PENDING', 'package_list' => [['logistics_status' => 'LOGISTICS_READY']]]]);
        $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&sub_tab=pending')
            ->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&sub_tab=process')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.platform_pending', false);
        $this->getJson('/api/marketplace/local-order-counts')
            ->assertOk()->assertJsonPath('ready_pending', 0)->assertJsonPath('ready_process', 1);
    }

    public function test_stale_pending_payload_does_not_move_unpaid_cancelled_or_processed_orders(): void
    {
        foreach (['UNPAID', 'CANCELLED', 'PROCESSED', 'SHIPPED'] as $status) {
            $order = $this->createOrder($status, $status, 'buyer');
            $order->update(['raw_json' => ['package_list' => [['logistics_status' => 'LOGISTICS_NOT_START']]]]);
            $this->assertFalse($order->shipping_pending);
        }

        $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&sub_tab=pending')
            ->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&sub_tab=unpaid')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.platform_pending', false);
    }
}
