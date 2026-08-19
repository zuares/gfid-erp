<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Item;
use App\Models\MarketplaceAdExperiment;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceAdsItemDaily;
use App\Models\SkuMapping;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdsExperimentPhase2Test extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_phase2_simulation_controls(): void
    {
        $response = $this->actingAs($this->user())
            ->get(route('marketplace.ads.dashboard'));

        $response->assertOk()
            ->assertSee('Simulasi Phase 2')
            ->assertSee('experimentSimulationPrice');
    }

    public function test_single_item_period_metrics_include_profit_and_break_even_values(): void
    {
        $store = $this->store('PHASE2-SINGLE');
        $item = Item::create([
            'code' => 'PHASE2-ITEM-1',
            'name' => 'Phase 2 Single Item',
            'type' => 'finished',
            'hpp' => 30,
            'active' => true,
        ]);
        $experiment = $this->experiment($store, $item, [
            'channel_campaign_id' => 'C-PHASE2-SINGLE',
            'channel_item_id' => 'ITEM-PHASE2-SINGLE',
            'old_price' => 100,
            'new_price' => 120,
        ]);

        $effective = Carbon::parse($experiment->effective_date);
        foreach (range(1, 7) as $days) {
            $this->campaignFact($store, 'C-PHASE2-SINGLE', $effective->copy()->subDays($days), [
                'clicks' => 50,
                'impressions' => 500,
                'expense' => 100,
                'broad_order' => 2,
                'broad_order_amount' => 2,
                'broad_gmv' => 200,
            ]);
            $this->campaignFact($store, 'C-PHASE2-SINGLE', $effective->copy()->addDays($days), [
                'clicks' => 100,
                'impressions' => 1000,
                'expense' => 200,
                'broad_order' => 4,
                'broad_order_amount' => 5,
                'broad_gmv' => 600,
            ]);
        }

        $details = app(\App\Services\Marketplace\Ads\AdsExperimentService::class)->details($experiment);
        $metrics = $details['observation']['metrics'];

        $this->assertSame(35.0, (float) $metrics['qty']);
        $this->assertSame('reported', $metrics['qty_source']);
        $this->assertSame(1400.0, (float) $metrics['spend']);
        $this->assertSame(4200.0, (float) $metrics['revenue']);
        $this->assertSame(676.2, (float) $metrics['profit']);
        $this->assertEqualsWithDelta(24.3879, (float) $metrics['break_even_qty'], 0.001);
        $this->assertEqualsWithDelta(0.03484, (float) $metrics['cvr_bep'], 0.0001);
        $this->assertEqualsWithDelta(2.0904, (float) $metrics['break_even_roas'], 0.001);
        $this->assertTrue($metrics['actual_profit_ready']);
        $this->assertFalse($metrics['profit_estimated']);
    }

    public function test_multi_item_observation_is_scoped_to_changed_item_and_reads_reported_qty(): void
    {
        $store = $this->store('PHASE2-MULTI');
        $item = Item::create([
            'code' => 'PHASE2-ITEM-A',
            'name' => 'Phase 2 Multi Item A',
            'type' => 'finished',
            'hpp' => 25,
            'active' => true,
        ]);
        $experiment = $this->experiment($store, $item, [
            'channel_campaign_id' => 'GMS-PHASE2-MULTI',
            'channel_item_id' => 'ITEM-A',
            'source_granularity' => 'item',
            'old_price' => null,
            'new_price' => null,
        ]);
        $effective = Carbon::parse($experiment->effective_date);

        foreach (range(1, 7) as $days) {
            $date = $effective->copy()->addDays($days);
            $this->itemFact($store, 'GMS-PHASE2-MULTI', 'ITEM-A', $date, 3, 150);
            // Item B belongs to the same multi-item campaign but must not be
            // included in the experiment scoped to ITEM-A.
            $this->itemFact($store, 'GMS-PHASE2-MULTI', 'ITEM-B', $date, 99, 9900);
        }

        $details = app(\App\Services\Marketplace\Ads\AdsExperimentService::class)->details($experiment);
        $metrics = $details['observation']['metrics'];

        $this->assertSame(21.0, (float) $metrics['qty']);
        $this->assertSame('reported', $metrics['qty_source']);
        $this->assertFalse(in_array('estimated_qty', $details['observation']['data_quality'], true));
        $this->assertSame('marketplace_ads_item_dailies', $details['observation']['source_table']);
    }

    public function test_simulation_endpoint_is_read_only_and_returns_price_roas_and_bep_projection(): void
    {
        $store = $this->store('PHASE2-SIMULATE');
        $item = Item::create([
            'code' => 'PHASE2-ITEM-SIM',
            'name' => 'Phase 2 Simulation Item',
            'type' => 'finished',
            'hpp' => 40,
            'active' => true,
        ]);
        $experiment = $this->experiment($store, $item, [
            'channel_campaign_id' => 'C-PHASE2-SIM',
            'channel_item_id' => 'ITEM-PHASE2-SIM',
            'old_price' => 100,
            'new_price' => 120,
        ]);
        $effective = Carbon::parse($experiment->effective_date);

        foreach (range(1, 7) as $days) {
            $this->campaignFact($store, 'C-PHASE2-SIM', $effective->copy()->addDays($days), [
                'clicks' => 100,
                'impressions' => 1000,
                'expense' => 200,
                'broad_order' => 4,
                'broad_order_amount' => 5,
                'broad_gmv' => 600,
            ]);
        }

        $response = $this->actingAs($this->user())
            ->postJson(route('marketplace.ads.experiments.simulate'), [
                'experiment_id' => $experiment->id,
                'period' => 'observation',
                'price' => 150,
                'target_roas' => 4,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.result.assumptions.price', 150)
            ->assertJsonPath('data.result.assumptions.target_roas', 4)
            ->assertJsonStructure([
                'data' => [
                    'experiment_id',
                    'period',
                    'source_period',
                    'result' => [
                        'current_quantity',
                        'target_roas' => ['target_gmv', 'target_qty', 'target_cvr', 'estimated_profit'],
                        'break_even' => ['qty', 'cvr', 'roas', 'valid'],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('marketplace_ad_experiments', [
            'id' => $experiment->id,
            'new_price' => 120,
        ]);
    }

    public function test_phase3_returns_funnel_impact_sufficiency_and_positive_verdict(): void
    {
        $store = $this->store('PHASE3-POSITIVE');
        $item = Item::create([
            'code' => 'PHASE3-ITEM-POS',
            'name' => 'Phase 3 Positive Item',
            'type' => 'finished',
            'hpp' => 20,
            'active' => true,
        ]);
        $experiment = $this->experiment($store, $item, [
            'channel_campaign_id' => 'C-PHASE3-POS',
            'channel_item_id' => 'ITEM-PHASE3-POS',
            'old_price' => 100,
            'new_price' => 120,
        ]);
        $effective = Carbon::parse($experiment->effective_date);

        foreach (range(1, 7) as $days) {
            $this->campaignFact($store, 'C-PHASE3-POS', $effective->copy()->subDays($days), [
                'clicks' => 100,
                'impressions' => 1000,
                'expense' => 100,
                'broad_order' => 4,
                'broad_order_amount' => 4,
                'broad_gmv' => 400,
            ]);
            $this->campaignFact($store, 'C-PHASE3-POS', $effective->copy()->addDays($days), [
                'clicks' => 100,
                'impressions' => 1000,
                'expense' => 100,
                'broad_order' => 6,
                'broad_order_amount' => 6,
                'broad_gmv' => 720,
            ]);
        }

        $details = app(\App\Services\Marketplace\Ads\AdsExperimentService::class)->details($experiment);

        $this->assertSame('POSITIVE', $details['verdict']);
        $this->assertTrue($details['data_sufficiency']['metric_ready']);
        $this->assertTrue($details['data_sufficiency']['profit_ready']);
        $this->assertSame('actual', $details['impact']['profit_measurement']);
        $this->assertSame('up', $details['impact']['steps']['profit']['direction']);
        $this->assertEquals(42, $details['impact']['steps']['qty']['after']);
        $this->assertSame([], $details['conflicts']);

        $this->actingAs($this->user())
            ->getJson(route('marketplace.ads.experiments.show', ['experiment' => $experiment]))
            ->assertOk()
            ->assertJsonPath('data.verdict', 'POSITIVE')
            ->assertJsonPath('data.impact.profit_measurement', 'actual')
            ->assertJsonPath('data.data_sufficiency.metric_ready', true);
    }

    public function test_phase3_marks_overlapping_experiment_as_confounded(): void
    {
        $store = $this->store('PHASE3-CONFLICT');
        $item = Item::create([
            'code' => 'PHASE3-ITEM-CONFLICT',
            'name' => 'Phase 3 Conflict Item',
            'type' => 'finished',
            'hpp' => 20,
            'active' => true,
        ]);
        $experiment = $this->experiment($store, $item, [
            'channel_campaign_id' => 'C-PHASE3-CONFLICT',
            'channel_item_id' => 'ITEM-PHASE3-CONFLICT',
            'old_price' => 100,
            'new_price' => 120,
        ]);
        $effective = Carbon::parse($experiment->effective_date);
        MarketplaceAdExperiment::create([
            'store_id' => $store->id,
            'change_event_id' => (string) Str::uuid(),
            'channel_campaign_id' => 'C-PHASE3-CONFLICT',
            'channel_item_id' => 'ITEM-PHASE3-CONFLICT',
            'internal_item_id' => $item->id,
            'change_type' => MarketplaceAdExperiment::CHANGE_TARGET_ROAS,
            'old_target_roas' => 4,
            'new_target_roas' => 5,
            'changed_at' => $effective->copy()->addDays(2),
            'effective_date' => $effective->copy()->addDays(2)->toDateString(),
            'lifecycle_status' => MarketplaceAdExperiment::STATUS_LEARNING,
            'profit_basis' => 'net_hpp_ads',
            'source_granularity' => 'campaign',
            'mapping_status' => 'mapped',
            'baseline_days' => 7,
            'observation_days' => 7,
            'calculation_version' => 'phase3-v1',
        ]);

        $details = app(\App\Services\Marketplace\Ads\AdsExperimentService::class)->details($experiment);

        $this->assertSame('CONFOUNDED', $details['verdict']);
        $this->assertSame(MarketplaceAdExperiment::STATUS_CONFOUNDED, $details['lifecycle_status']);
        $this->assertTrue(collect($details['conflicts'])->contains('reason', 'overlapping_experiment'));
    }

    public function test_same_day_target_roas_changes_are_grouped_into_one_experiment(): void
    {
        $store = $this->store('PHASE3-GROUP');
        $item = Item::create([
            'code' => 'PHASE3-ITEM-GROUP',
            'name' => 'Phase 3 Grouped Item',
            'type' => 'finished',
            'hpp' => 20,
            'active' => true,
        ]);
        $changedAt = now()->subDays(2)->startOfDay()->addHours(9);
        $service = app(\App\Services\Marketplace\Ads\AdsExperimentService::class);

        $first = $service->recordTargetRoasChange(
            $store,
            'C-PHASE3-GROUP',
            'ITEM-PHASE3-GROUP',
            4,
            5,
            [],
            null,
            $changedAt,
        );
        $second = $service->recordTargetRoasChange(
            $store,
            'C-PHASE3-GROUP',
            'ITEM-PHASE3-GROUP',
            5,
            6,
            [],
            null,
            $changedAt->copy()->addHours(4),
        );

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('marketplace_ad_experiments', 1);
        $this->assertDatabaseHas('marketplace_ad_experiments', [
            'id' => $first->id,
            'old_target_roas' => 4,
            'new_target_roas' => 6,
        ]);

        $this->actingAs($this->user())
            ->getJson(route('marketplace.ads.experiments.index', ['store_id' => $store->id]))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_active_experiment_scope_excludes_confounded_experiment(): void
    {
        $store = $this->store('PHASE3-LOCK');
        $item = Item::create([
            'code' => 'PHASE3-ITEM-LOCK',
            'name' => 'Phase 3 Lock Item',
            'type' => 'finished',
            'hpp' => 20,
            'active' => true,
        ]);
        $active = $this->experiment($store, $item, [
            'channel_campaign_id' => 'C-PHASE3-LOCK',
            'channel_item_id' => 'ITEM-PHASE3-LOCK',
            'changed_at' => now()->subDays(2),
            'effective_date' => now()->subDays(2)->toDateString(),
            'lifecycle_status' => MarketplaceAdExperiment::STATUS_LEARNING,
        ]);
        $confounded = $this->experiment($store, $item, [
            'channel_campaign_id' => 'C-PHASE3-CONFOUNDED',
            'channel_item_id' => 'ITEM-PHASE3-CONFOUNDED',
            'changed_at' => now()->subDays(2),
            'effective_date' => now()->subDays(2)->toDateString(),
            'lifecycle_status' => MarketplaceAdExperiment::STATUS_CONFOUNDED,
            'confounded' => true,
        ]);

        $service = app(\App\Services\Marketplace\Ads\AdsExperimentService::class);

        $this->assertSame(
            $active->id,
            $service->activeExperimentForScope($store, 'C-PHASE3-LOCK', 'ITEM-PHASE3-LOCK')?->id,
        );
        $this->assertNull(
            $service->activeExperimentForScope($store, 'C-PHASE3-CONFOUNDED', 'ITEM-PHASE3-CONFOUNDED'),
        );
    }

    public function test_detail_resolves_hpp_from_marketplace_sku_when_experiment_has_no_manual_mapping(): void
    {
        $store = $this->store('PHASE3-SKU-FALLBACK');
        $item = Item::create([
            'code' => 'PHASE3-ITEM-SKU',
            'name' => 'Phase 3 SKU Fallback Item',
            'type' => 'finished',
            'hpp' => 30,
            'active' => true,
        ]);
        MarketplaceProduct::create([
            'store_id' => $store->id,
            'item_id' => 'MARKETPLACE-ITEM-SKU',
            'item_name' => 'Marketplace SKU Product',
            'item_sku' => 'SKU-PHASE3-FALLBACK',
            'price_min' => 100,
            'price_max' => 100,
        ]);
        SkuMapping::create([
            'marketplace_sku' => 'SKU-PHASE3-FALLBACK',
            'channel_code' => 'shopee',
            'item_id' => $item->id,
        ]);
        $experiment = $this->experiment($store, $item, [
            'channel_campaign_id' => 'C-PHASE3-SKU',
            'channel_item_id' => 'MARKETPLACE-ITEM-SKU',
            'internal_item_id' => null,
            'mapping_status' => 'missing_mapping',
            'old_price' => null,
            'new_price' => null,
        ]);
        $effective = Carbon::parse($experiment->effective_date);

        foreach (range(1, 7) as $days) {
            $this->campaignFact($store, 'C-PHASE3-SKU', $effective->copy()->subDays($days), [
                'clicks' => 50,
                'impressions' => 500,
                'expense' => 100,
                'broad_order' => 3,
                'broad_order_amount' => 3,
                'broad_gmv' => 300,
            ]);
        }

        $details = app(\App\Services\Marketplace\Ads\AdsExperimentService::class)->details($experiment);

        $this->assertSame('mapped', $details['data_quality']['mapping_status']);
        $this->assertNotNull($details['baseline']['metrics']['profit']);
        $this->assertSame(30.0, (float) $details['baseline']['metrics']['hpp']);
    }

    private function store(string $code): Store
    {
        $channel = Channel::firstOrCreate(['code' => 'shopee'], ['name' => 'Shopee']);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => $code,
            'name' => $code,
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    private function user(): \App\Models\User
    {
        return \App\Models\User::factory()->create([
            'employee_code' => 'PHASE2-' . Str::upper(Str::random(8)),
            'role' => 'admin',
        ]);
    }

    private function experiment(Store $store, Item $item, array $attributes): MarketplaceAdExperiment
    {
        $changedAt = now()->subDays(10)->startOfDay();

        return MarketplaceAdExperiment::create(array_merge([
            'store_id' => $store->id,
            'change_event_id' => (string) Str::uuid(),
            'channel_campaign_id' => 'C-PHASE2',
            'channel_item_id' => 'ITEM-PHASE2',
            'internal_item_id' => $item->id,
            'change_type' => MarketplaceAdExperiment::CHANGE_PRICE,
            'changed_at' => $changedAt,
            'effective_date' => $changedAt->toDateString(),
            'lifecycle_status' => MarketplaceAdExperiment::STATUS_LEARNING,
            'profit_basis' => 'net_hpp_ads',
            'source_granularity' => 'campaign',
            'mapping_status' => 'mapped',
            'baseline_days' => 7,
            'observation_days' => 7,
            'calculation_version' => 'phase2-v1',
        ], $attributes));
    }

    private function campaignFact(Store $store, string $campaignId, Carbon $date, array $metrics): void
    {
        DB::table('marketplace_ad_campaign_dailies')->insert(array_merge([
            'store_id' => $store->id,
            'channel_campaign_id' => $campaignId,
            'date' => $date->toDateString(),
            'ad_type' => 'product',
            'created_at' => now(),
            'updated_at' => now(),
        ], $metrics));
    }

    private function itemFact(Store $store, string $campaignId, string $itemId, Carbon $date, int $qty, float $gmv): void
    {
        MarketplaceAdsItemDaily::create([
            'store_id' => $store->id,
            'channel_campaign_id' => $campaignId,
            'channel_item_id' => $itemId,
            'date' => $date->toDateString(),
            'impressions' => 1000,
            'clicks' => 100,
            'expense' => 200,
            'broad_order' => 2,
            'broad_gmv' => $gmv,
            'direct_order' => 2,
            'direct_gmv' => $gmv,
            'raw_json' => ['broad_order_amount' => $qty],
        ]);
    }
}
