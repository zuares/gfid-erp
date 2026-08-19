<?php

namespace Tests\Feature;

use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceProduct;
use App\Models\Store;
use App\Services\Marketplace\Ads\AdsExperimentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdsExperimentTest extends TestCase
{
    use RefreshDatabase;

    public function test_target_roas_change_creates_scoped_experiment_and_aggregates_completed_windows(): void
    {
        $store = $this->createStore('EXP-ROAS');
        $campaign = MarketplaceAdCampaign::create([
            'store_id' => $store->id,
            'channel_campaign_id' => 'campaign-1',
            'channel_item_id' => 'item-1',
            'report_date_from' => now()->subDays(20)->toDateString(),
            'report_date_to' => now()->subDay()->toDateString(),
            'target_roas' => 2.0,
        ]);
        $changedAt = now()->subDays(10)->setTime(12, 0);

        foreach (range(1, 7) as $day) {
            $date = $changedAt->copy()->subDays(8 - $day)->toDateString();
            $this->insertCampaignDaily($store->id, $campaign->channel_campaign_id, $date, 10, 5, 10000, 1, 100000);
        }
        foreach (range(1, 7) as $day) {
            $date = $changedAt->copy()->addDays($day)->toDateString();
            $this->insertCampaignDaily($store->id, $campaign->channel_campaign_id, $date, 20, 10, 15000, 2, 180000);
        }

        $experiment = app(AdsExperimentService::class)->recordTargetRoasChange(
            store: $store,
            channelCampaignId: 'campaign-1',
            channelItemId: 'item-1',
            oldTargetRoas: 2.0,
            newTargetRoas: 3.0,
            changedAt: $changedAt,
        );

        $this->assertNotNull($experiment);
        $this->assertDatabaseHas('marketplace_ad_experiments', [
            'id' => $experiment->id,
            'store_id' => $store->id,
            'channel_campaign_id' => 'campaign-1',
            'channel_item_id' => 'item-1',
            'old_target_roas' => 2.0,
            'new_target_roas' => 3.0,
            'effective_date' => $changedAt->toDateString(),
        ]);

        $details = app(AdsExperimentService::class)->details($experiment->fresh());

        $this->assertSame('READY_TO_EVALUATE', $details['lifecycle_status']);
        $this->assertSame(35, $details['baseline']['metrics']['clicks']);
        $this->assertSame(70, $details['observation']['metrics']['clicks']);
        $this->assertEquals(7, $details['windows']['observation']['completed_days']);
        $this->assertFalse($details['data_quality']['actual_profit_ready']);
    }

    public function test_price_change_creates_item_scoped_experiment_without_campaign(): void
    {
        $store = $this->createStore('EXP-PRICE');
        $product = MarketplaceProduct::create([
            'store_id' => $store->id,
            'item_id' => 'item-price-1',
            'item_name' => 'Test product',
        ]);

        $experiments = app(AdsExperimentService::class)->recordPriceChange(
            product: $product,
            changes: [[
                'model_id' => '0',
                'old_price' => 100000,
                'new_price' => 110000,
            ]],
            changedAt: Carbon::parse('2026-08-10 10:00:00', config('app.timezone')),
        );

        $this->assertCount(1, $experiments);
        $this->assertSame('price', $experiments->first()->change_type);
        $this->assertSame('item-price-1', $experiments->first()->channel_item_id);
        $this->assertSame('missing_mapping', $experiments->first()->mapping_status);
        $this->assertSame('2026-08-10', $experiments->first()->effective_date->toDateString());
    }

    private function createStore(string $code): Store
    {
        $channel = \App\Models\Channel::firstOrCreate([
            'code' => 'shopee',
            'name' => 'Shopee',
        ]);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => $code,
            'name' => $code,
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    private function insertCampaignDaily(
        int $storeId,
        string $campaignId,
        string $date,
        int $impressions,
        int $clicks,
        int $expense,
        int $orders,
        int $gmv,
    ): void {
        DB::table('marketplace_ad_campaign_dailies')->insert([
            'store_id' => $storeId,
            'channel_campaign_id' => $campaignId,
            'date' => $date,
            'impressions' => $impressions * 10,
            'clicks' => $clicks,
            'expense' => $expense,
            'broad_order' => $orders,
            'broad_order_amount' => $orders,
            'broad_gmv' => $gmv,
            'direct_order' => 0,
            'direct_order_amount' => 0,
            'direct_gmv' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
