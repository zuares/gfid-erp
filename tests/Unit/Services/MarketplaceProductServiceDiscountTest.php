<?php

namespace Tests\Unit\Services;

use App\Models\Channel;
use App\Models\Store;
use App\Services\Channels\ChannelManager;
use App\Services\Channels\Contracts\MarketplaceChannel;
use App\Services\MarketplaceProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MarketplaceProductServiceDiscountTest extends TestCase
{
    use RefreshDatabase;

    private function createStore(): Store
    {
        $channel = Channel::firstOrCreate(['code' => 'shopee'], ['name' => 'Shopee']);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => 'S' . rand(1000, 9999),
            'name' => 'Toko Uji',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function test_update_discount_helper_delegates_to_driver()
    {
        $store = $this->createStore();

        $gateway = Mockery::mock(\App\Services\Marketplace\MarketplaceApiGateway::class);
        $gateway->shouldReceive('updateDiscount')
            ->once()
            ->with($store, 123, 'Promo Baru', 1785553200, 1792033200)
            ->andReturn(['success' => true]);

        
        $service = new MarketplaceProductService($gateway);

        $result = $service->updateDiscount($store, 123, 'Promo Baru', 1785553200, 1792033200);

        $this->assertSame(['success' => true], $result);
    }

    public function test_delete_discount_item_helper_delegates_to_driver()
    {
        $store = $this->createStore();

        $gateway = Mockery::mock(\App\Services\Marketplace\MarketplaceApiGateway::class);
        $gateway->shouldReceive('deleteDiscountItem')
            ->once()
            ->with($store, 123, 1776783, 1467683)
            ->andReturn(['success' => true]);

        
        $service = new MarketplaceProductService($gateway);

        $result = $service->deleteDiscountItem($store, 123, 1776783, 1467683);

        $this->assertSame(['success' => true], $result);
    }
}
