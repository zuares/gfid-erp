<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Store;
use App\Services\Marketplace\Ads\AdsDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketplaceAdsProductMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_metrics_include_impressions_and_clicks_from_regular_and_gmv_max_ads(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'ADS-PRODUCT-METRICS',
            'name' => 'Ads Product Metrics',
            'status' => 'active',
            'is_active' => true,
        ]);

        DB::table('marketplace_ad_campaigns')->insert([
            'store_id' => $store->id,
            'channel_campaign_id' => 'REGULAR-1',
            'channel_item_id' => '123456789',
            'campaign_name' => 'Regular Product Ads',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('marketplace_ad_campaign_dailies')->insert([
            [
                'store_id' => $store->id,
                'channel_campaign_id' => 'REGULAR-1',
                'date' => '2026-10-10',
                'impressions' => 100,
                'clicks' => 10,
                'expense' => 1000,
                'broad_order' => 2,
                'broad_gmv' => 5000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'store_id' => $store->id,
                'channel_campaign_id' => 'GMS-1',
                'date' => '2026-10-10',
                'impressions' => 200,
                'clicks' => 20,
                'expense' => 1000,
                'broad_order' => 2,
                'broad_gmv' => 5000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('marketplace_ads_item_dailies')->insert([
            'store_id' => $store->id,
            'channel_campaign_id' => 'GMS-1',
            'channel_item_id' => '123456789',
            'date' => '2026-10-10',
            'impressions' => 200,
            'clicks' => 20,
            'expense' => 1000,
            'broad_order' => 2,
            'broad_gmv' => 5000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $metrics = app(AdsDashboardService::class)->getProductAdMetrics(
            [$store->id],
            $store->id,
            '2026-10-01',
            '2026-10-17',
        );
        $product = $metrics['external']->get('123456789');

        $this->assertNotNull($product);
        $this->assertSame(300, $product['impressions']);
        $this->assertSame(30, $product['clicks']);
        $this->assertSame(2000.0, $product['spend']);
        $this->assertSame(10000.0, $product['sales']);
        $this->assertSame(4, $product['conversions']);
    }
}
