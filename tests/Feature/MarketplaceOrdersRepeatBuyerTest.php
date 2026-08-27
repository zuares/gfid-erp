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

class MarketplaceOrdersRepeatBuyerTest extends TestCase
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
            'code' => 'REPEAT-STORE',
            'name' => 'Toko Repeat',
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

    public function test_order_ditandai_repeat_bila_pembeli_punya_histori_pembelian(): void
    {
        $this->createOrder('REPEAT-PREVIOUS', 'COMPLETED', 'buyer-repeat');
        $current = $this->createOrder('REPEAT-CURRENT', 'READY_TO_SHIP', 'buyer-repeat');

        $response = $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&limit=50');
        $row = collect($response->json('data'))->firstWhere('id', $current->id);

        $response->assertOk();
        $this->assertSame(1, $row['buyer_previous_order_count']);
        $this->assertTrue($row['is_repeat_buyer']);
    }

    public function test_order_baru_tidak_ditandai_repeat(): void
    {
        $current = $this->createOrder('NEW-CURRENT', 'READY_TO_SHIP', 'buyer-new');

        $response = $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&limit=50');
        $row = collect($response->json('data'))->firstWhere('id', $current->id);

        $response->assertOk();
        $this->assertSame(0, $row['buyer_previous_order_count']);
        $this->assertFalse($row['is_repeat_buyer']);
    }
}
