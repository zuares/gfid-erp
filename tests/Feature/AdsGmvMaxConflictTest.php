<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceAdCampaign;
use App\Models\Store;
use App\Services\Channels\Shopee\ShopeeChannel;
use App\Services\Marketplace\Ads\AdsActionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdsGmvMaxConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_gmv_max_add_returns_confirmation_when_regular_ad_is_active_for_same_item(): void
    {
        $store = $this->createStore();

        MarketplaceAdCampaign::create([
            'store_id' => $store->id,
            'channel_campaign_id' => '880001',
            'channel_item_id' => 123456,
            'campaign_name' => 'Regular Produk 123456',
            'ad_type' => 'manual',
            'campaign_status' => 'ongoing',
            'started_at' => Carbon::parse('2026-08-01 00:00:00'),
            'ended_at' => Carbon::parse('2026-08-31 23:59:59'),
        ]);

        $shopee = Mockery::mock(ShopeeChannel::class);
        $shopee->shouldNotReceive('editGmsItemProductCampaign');

        $result = app(AdsActionService::class)->actionGmsItem(
            $store,
            123456,
            'add',
            $shopee,
            Carbon::parse('2026-08-10')->startOfDay(),
            Carbon::parse('2026-08-20')->endOfDay(),
        );

        $this->assertSame('warning', $result['status']);
        $this->assertTrue($result['requires_confirmation']);
        $this->assertSame('existing_regular_ads', $result['code']);
        $this->assertCount(1, $result['campaigns']);
        $this->assertStringContainsString('GMV Max akan menjeda', $result['message']);
    }

    private function createStore(): Store
    {
        $channel = Channel::firstOrCreate(['code' => 'shopee'], ['name' => 'Shopee']);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => 'GMS-CONFLICT',
            'name' => 'Store GMS Conflict',
            'status' => 'active',
            'is_active' => true,
        ]);
    }
}
