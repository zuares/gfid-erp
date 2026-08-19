<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ShopeeStoreAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'EMP' . random_int(100, 999),
        ]);
    }

    public function test_callback_updates_existing_store_and_triggers_background_sync()
    {
        $this->withoutMiddleware();

        config([
            'shopee.partner_id' => '2000000',
            'shopee.partner_key' => 'test_partner_key',
            'shopee.base_url' => 'https://partner.shopeemobile.com',
        ]);

        $user = $this->createUser();
        $channel = Channel::firstOrCreate(
            ['code' => 'shopee'],
            ['name' => 'Shopee', 'status' => 'active']
        );

        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'TOKO-001',
            'name' => 'Toko Lama',
            'status' => 'active',
            'is_active' => true,
        ]);

        Http::fake([
            '*' => Http::response([
                'access_token' => 'access-token-123',
                'refresh_token' => 'refresh-token-456',
                'expire_in' => 3600,
            ], 200),
        ]);

        $shopee = \Mockery::mock(\App\Services\Channels\Shopee\ShopeeChannel::class);
        $shopee->shouldReceive('getShopInfo')
            ->once()
            ->andReturn([
                'response' => [
                    'shop_name' => 'Shopee Toko Baru',
                ],
            ]);
        app()->instance(\App\Services\Channels\Shopee\ShopeeChannel::class, $shopee);

        Queue::fake();
        Bus::fake();

        $queuedAdsSync = \Mockery::mock();
        $queuedAdsSync->shouldReceive('onQueue')
            ->once()
            ->with('ads')
            ->andReturnSelf();

        Artisan::shouldReceive('queue')
            ->times(3)
            ->andReturn(null, null, $queuedAdsSync);

        $response = $this->actingAs($user)
            ->withSession(['shopee_connect_store_id' => $store->id])
            ->get(route('marketplace.shopee.callback', [
                'code' => 'dummy-code',
                'shop_id' => '987654321',
            ]));

        $response->assertRedirect('/marketplace/toko?connected=1');
        $response->assertSessionHas('success', 'Shopee berhasil terhubung.');

        $store->refresh();

        $this->assertSame('987654321', $store->external_shop_id);
        $this->assertSame('Shopee Toko Baru', $store->name);
        $this->assertSame('CONNECTED', $store->connection_status);
    }
}
