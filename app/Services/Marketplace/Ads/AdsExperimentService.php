<?php

namespace App\Services\Marketplace\Ads;

use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdExperiment;
use App\Models\MarketplaceAdItemMap;
use App\Models\MarketplaceProduct;
use App\Models\Store;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdsExperimentService
{
    public const DEFAULT_BASELINE_DAYS = 7;
    public const DEFAULT_OBSERVATION_DAYS = 7;
    public const MAX_OBSERVATION_DAYS = 14;
    public const MIN_CLICKS = 30;
    public const MIN_ORDERS = 3;

    public function __construct(protected ItemHppResolver $hppResolver) {}

    /**
     * Record one price change for every campaign scope that targets the item.
     * A product may be used by more than one campaign, therefore one physical
     * change event can produce multiple scoped experiment rows.
     */
    public function recordPriceChange(
        MarketplaceProduct $product,
        array $changes,
        ?int $createdBy = null,
        CarbonInterface|string|null $changedAt = null,
    ): Collection {
        $normalizedChanges = collect($changes)
            ->map(function (array $change): array {
                return [
                    'model_id' => (string) ($change['model_id'] ?? '0'),
                    'old_price' => $this->nullableFloat($change['old_price'] ?? null),
                    'new_price' => $this->nullableFloat($change['new_price'] ?? null),
                ];
            })
            ->filter(fn (array $change): bool =>
                $change['new_price'] !== null
                && ($change['old_price'] === null || abs($change['old_price'] - $change['new_price']) > 0.0001)
            )
            ->values();

        if ($normalizedChanges->isEmpty()) {
            return collect();
        }

        $campaignItemCampaignIds = DB::table('marketplace_ads_campaign_items')
            ->where('channel_item_id', (string) $product->item_id)
            ->pluck('campaign_id');
        $campaigns = MarketplaceAdCampaign::query()
            ->where('store_id', $product->store_id)
            ->where(function ($query) use ($product, $campaignItemCampaignIds) {
                $query->where('channel_item_id', (string) $product->item_id);
                if ($campaignItemCampaignIds->isNotEmpty()) {
                    $query->orWhereIn('id', $campaignItemCampaignIds->all());
                }
            })
            ->orderBy('id')
            ->get();

        $fallbackInternalItemId = $this->mappedInternalItemId(
            (int) $product->store_id,
            (string) $product->item_id,
            null,
        );
        $eventId = (string) Str::uuid();
        $created = collect();

        if ($campaigns->isEmpty()) {
            $created->push($this->createExperiment([
                'store_id' => $product->store_id,
                'change_event_id' => $eventId,
                'channel_item_id' => (string) $product->item_id,
                'internal_item_id' => $fallbackInternalItemId,
                'change_type' => MarketplaceAdExperiment::CHANGE_PRICE,
                'old_price' => $this->singleValue($normalizedChanges, 'old_price'),
                'new_price' => $this->singleValue($normalizedChanges, 'new_price'),
                'changed_at' => $changedAt,
                'source_granularity' => 'item',
                'mapping_status' => $fallbackInternalItemId ? 'mapped' : 'missing_mapping',
                'calculation_snapshot' => $this->priceSnapshot(
                    $normalizedChanges->all(),
                    $fallbackInternalItemId,
                    'item',
                ),
                'created_by' => $createdBy,
            ]));

            return $created;
        }

        foreach ($campaigns as $campaign) {
            $internalItemId = $campaign->internal_item_id
                ?: $this->mappedInternalItemId(
                    (int) $product->store_id,
                    (string) $product->item_id,
                    (string) $campaign->channel_campaign_id,
                );

            $created->push($this->createExperiment([
                'store_id' => $product->store_id,
                'change_event_id' => $eventId,
                'channel_campaign_id' => (string) $campaign->channel_campaign_id,
                'channel_item_id' => (string) $product->item_id,
                'internal_item_id' => $internalItemId,
                'change_type' => MarketplaceAdExperiment::CHANGE_PRICE,
                'old_price' => $this->singleValue($normalizedChanges, 'old_price'),
                'new_price' => $this->singleValue($normalizedChanges, 'new_price'),
                'changed_at' => $changedAt,
                'source_granularity' => 'item',
                'mapping_status' => $internalItemId ? 'mapped' : 'missing_mapping',
                'calculation_snapshot' => $this->priceSnapshot(
                    $normalizedChanges->all(),
                    $internalItemId,
                    'item',
                ),
                'created_by' => $createdBy,
            ]));
        }

        return $created;
    }

    public function recordTargetRoasChange(
        Store $store,
        ?string $channelCampaignId,
        ?string $channelItemId,
        ?float $oldTargetRoas,
        ?float $newTargetRoas,
        array $confounders = [],
        ?int $createdBy = null,
        CarbonInterface|string|null $changedAt = null,
    ): ?MarketplaceAdExperiment {
        if ($newTargetRoas === null || ($oldTargetRoas !== null && abs($oldTargetRoas - $newTargetRoas) <= 0.0001)) {
            return null;
        }

        $internalItemId = $this->mappedInternalItemId(
            (int) $store->id,
            $channelItemId,
            $channelCampaignId,
        );
        $sourceGranularity = $channelCampaignId ? 'campaign' : ($channelItemId ? 'item' : 'store');

        return $this->createExperiment([
            'store_id' => $store->id,
            'change_event_id' => (string) Str::uuid(),
            'channel_campaign_id' => $channelCampaignId,
            'channel_item_id' => $channelItemId,
            'internal_item_id' => $internalItemId,
            'change_type' => MarketplaceAdExperiment::CHANGE_TARGET_ROAS,
            'old_target_roas' => $oldTargetRoas,
            'new_target_roas' => $newTargetRoas,
            'changed_at' => $changedAt,
            'source_granularity' => $sourceGranularity,
            'mapping_status' => $internalItemId ? 'mapped' : 'missing_mapping',
            'confounded' => $confounders !== [],
            'conflict_reason' => $confounders !== [] ? array_values($confounders) : null,
            'calculation_snapshot' => $this->targetRoasSnapshot(
                $oldTargetRoas,
                $newTargetRoas,
                $internalItemId,
                $sourceGranularity,
            ),
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Return the stored experiment plus live before/after fact-table metrics.
     * Daily facts are never copied into the experiment table.
     */
    public function details(MarketplaceAdExperiment $experiment): array
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $effectiveDate = Carbon::parse($experiment->effective_date->toDateString(), $timezone)->startOfDay();
        $baselineFrom = $effectiveDate->copy()->subDays((int) $experiment->baseline_days);
        $baselineTo = $effectiveDate->copy()->subDay();
        $observationFrom = $effectiveDate->copy()->addDay();
        $observationWindowEnd = $effectiveDate->copy()->addDays(
            min((int) $experiment->observation_days, self::MAX_OBSERVATION_DAYS)
        );
        $lastCompletedDate = Carbon::today($timezone)->subDay();
        $observationTo = $observationWindowEnd->lt($lastCompletedDate)
            ? $observationWindowEnd
            : $lastCompletedDate;

        $baseline = $this->periodMetrics($experiment, $baselineFrom, $baselineTo);
        $observation = $observationFrom->lte($observationTo)
            ? $this->periodMetrics($experiment, $observationFrom, $observationTo)
            : $this->emptyPeriod($observationFrom, $observationTo);

        $completedObservationDays = $observationFrom->lte($observationTo)
            ? $observationFrom->diffInDays($observationTo) + 1
            : 0;
        $status = $this->lifecycleStatus($experiment, $baseline, $observation, $completedObservationDays);

        return [
            'experiment' => $experiment,
            'scope' => [
                'store_id' => (int) $experiment->store_id,
                'channel_campaign_id' => $experiment->channel_campaign_id,
                'channel_item_id' => $experiment->channel_item_id,
                'internal_item_id' => $experiment->internal_item_id,
            ],
            'change' => [
                'type' => $experiment->change_type,
                'old_price' => $experiment->old_price !== null ? (float) $experiment->old_price : null,
                'new_price' => $experiment->new_price !== null ? (float) $experiment->new_price : null,
                'old_target_roas' => $experiment->old_target_roas !== null ? (float) $experiment->old_target_roas : null,
                'new_target_roas' => $experiment->new_target_roas !== null ? (float) $experiment->new_target_roas : null,
                'changed_at' => $experiment->changed_at?->timezone($timezone)->toIso8601String(),
                'effective_date' => $experiment->effective_date->toDateString(),
            ],
            'windows' => [
                'baseline' => [
                    'from' => $baselineFrom->toDateString(),
                    'to' => $baselineTo->toDateString(),
                    'completed_days' => (int) $experiment->baseline_days,
                ],
                'observation' => [
                    'from' => $observationFrom->toDateString(),
                    'to' => $observationTo->toDateString(),
                    'completed_days' => $completedObservationDays,
                    'window_days' => (int) $experiment->observation_days,
                ],
            ],
            'lifecycle_status' => $status,
            'profit_basis' => $experiment->profit_basis,
            'verdict' => $experiment->verdict,
            'baseline' => $baseline,
            'observation' => $observation,
            'metric_delta' => $this->metricDelta($baseline['metrics'], $observation['metrics']),
            'data_quality' => [
                'experiment_flags' => $experiment->data_quality_flags ?? [],
                'baseline_flags' => $baseline['data_quality'],
                'observation_flags' => $observation['data_quality'],
                'mapping_status' => $experiment->mapping_status,
                'actual_profit_ready' => false,
            ],
            'calculation_snapshot' => $experiment->calculation_snapshot ?? [],
        ];
    }

    private function createExperiment(array $attributes): MarketplaceAdExperiment
    {
        $changedAt = $this->asLocalDateTime($attributes['changed_at'] ?? null);
        $internalItemId = $attributes['internal_item_id'] ?? null;
        $flags = ['partial_day_excluded', 'estimated_qty'];

        if (! $internalItemId) {
            $flags[] = 'missing_mapping';
        }
        if (! empty($attributes['confounded'])) {
            $flags[] = 'confounded';
        }

        $attributes['changed_at'] = $changedAt;
        $attributes['effective_date'] = $changedAt->toDateString();

        return MarketplaceAdExperiment::create(array_merge([
            'change_event_id' => (string) Str::uuid(),
            'changed_at' => $changedAt,
            'effective_date' => $changedAt->toDateString(),
            'lifecycle_status' => MarketplaceAdExperiment::STATUS_LEARNING,
            'profit_basis' => 'incomplete',
            'mapping_status' => $internalItemId ? 'mapped' : 'missing_mapping',
            'confounded' => false,
            'data_quality_flags' => array_values(array_unique($flags)),
            'baseline_days' => self::DEFAULT_BASELINE_DAYS,
            'observation_days' => self::DEFAULT_OBSERVATION_DAYS,
            'calculation_version' => 'phase1-v1',
        ], $attributes));
    }

    private function priceSnapshot(array $changes, ?int $internalItemId, string $sourceGranularity): array
    {
        $hpp = $this->hppResolver->summary($internalItemId);

        return [
            'old_price' => $this->singleValue(collect($changes), 'old_price'),
            'new_price' => $this->singleValue(collect($changes), 'new_price'),
            'price_changes' => $changes,
            'hpp' => $hpp['hpp'] > 0 ? $hpp['hpp'] : null,
            'hpp_source' => $hpp['hpp_source'],
            'fee_mode' => 'pending',
            'fee_source' => 'pending',
            'ads_vat_factor' => 1.11,
            'price_basis' => 'marketplace_product_model',
            'profit_basis' => 'incomplete',
            'source_granularity' => $sourceGranularity,
            'mapping_status' => $internalItemId ? 'mapped' : 'missing_mapping',
            'calculation_version' => 'phase1-v1',
        ];
    }

    private function targetRoasSnapshot(
        ?float $oldTargetRoas,
        float $newTargetRoas,
        ?int $internalItemId,
        string $sourceGranularity,
    ): array {
        $hpp = $this->hppResolver->summary($internalItemId);

        return [
            'old_target_roas' => $oldTargetRoas,
            'new_target_roas' => $newTargetRoas,
            'hpp' => $hpp['hpp'] > 0 ? $hpp['hpp'] : null,
            'hpp_source' => $hpp['hpp_source'],
            'fee_mode' => 'pending',
            'fee_source' => 'pending',
            'ads_vat_factor' => 1.11,
            'price_basis' => 'observed_gmv_per_order',
            'profit_basis' => 'incomplete',
            'source_granularity' => $sourceGranularity,
            'mapping_status' => $internalItemId ? 'mapped' : 'missing_mapping',
            'calculation_version' => 'phase1-v1',
        ];
    }

    private function periodMetrics(
        MarketplaceAdExperiment $experiment,
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        if ($from->gt($to)) {
            return $this->emptyPeriod($from, $to);
        }

        [$table, $query] = $this->factQuery($experiment, $from, $to);
        $isCampaignFact = $table === 'marketplace_ad_campaign_dailies';
        $aggregate = (clone $query)
            ->selectRaw('COALESCE(SUM(impressions), 0) as impressions')
            ->selectRaw('COALESCE(SUM(clicks), 0) as clicks')
            ->selectRaw('COALESCE(SUM(' . ($isCampaignFact ? 'expense' : 'expense') . '), 0) as spend')
            ->selectRaw('COALESCE(SUM(' . ($isCampaignFact ? 'broad_order' : 'broad_order') . '), 0) as orders')
            ->selectRaw('COALESCE(SUM(' . ($isCampaignFact ? 'broad_gmv' : 'broad_gmv') . '), 0) as revenue')
            ->when($isCampaignFact, fn ($q) => $q->selectRaw('COALESCE(SUM(broad_order_amount), 0) as qty_reported'))
            ->first();
        $daysWithData = (int) (clone $query)->distinct()->count('date');

        $orders = (int) ($aggregate->orders ?? 0);
        $qtyReported = (float) ($aggregate->qty_reported ?? 0);
        $usesQtyFallback = ! $isCampaignFact || $qtyReported <= 0;
        $qty = $usesQtyFallback ? (float) $orders : $qtyReported;
        $impressions = (int) ($aggregate->impressions ?? 0);
        $clicks = (int) ($aggregate->clicks ?? 0);
        $spend = (float) ($aggregate->spend ?? 0);
        $revenue = (float) ($aggregate->revenue ?? 0);

        $quality = ['partial_day_excluded'];
        if ($usesQtyFallback) {
            $quality[] = 'estimated_qty';
        }
        if ($clicks < self::MIN_CLICKS || $orders < self::MIN_ORDERS) {
            $quality[] = 'low_volume';
        }
        if ($daysWithData === 0) {
            $quality[] = 'missing_metric';
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'days_with_data' => $daysWithData,
            'source_table' => $table,
            'metrics' => [
                'impressions' => $impressions,
                'clicks' => $clicks,
                'orders' => $orders,
                'qty' => $qty,
                'revenue' => round($revenue, 2),
                'spend' => round($spend, 2),
                'ctr' => $impressions > 0 ? round($clicks / $impressions, 6) : null,
                'cvr' => $clicks > 0 ? round($orders / $clicks, 6) : null,
                'roas' => $spend > 0 ? round($revenue / $spend, 4) : null,
            ],
            'data_quality' => array_values(array_unique($quality)),
        ];
    }

    private function factQuery(MarketplaceAdExperiment $experiment, CarbonInterface $from, CarbonInterface $to): array
    {
        if ($experiment->source_granularity === 'item' && $experiment->channel_item_id) {
            $query = DB::table('marketplace_ads_item_dailies')
                ->where('store_id', $experiment->store_id)
                ->where('channel_item_id', $experiment->channel_item_id)
                ->when(
                    $experiment->channel_campaign_id,
                    fn ($q) => $q->where('channel_campaign_id', $experiment->channel_campaign_id)
                )
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);

            return ['marketplace_ads_item_dailies', $query];
        }

        if ($experiment->channel_campaign_id) {
            $query = DB::table('marketplace_ad_campaign_dailies')
                ->where('store_id', $experiment->store_id)
                ->where('channel_campaign_id', $experiment->channel_campaign_id)
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);

            return ['marketplace_ad_campaign_dailies', $query];
        }

        $query = DB::table('marketplace_ads_dailies')
            ->where('store_id', $experiment->store_id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);

        return ['marketplace_ads_dailies', $query];
    }

    private function emptyPeriod(CarbonInterface $from, CarbonInterface $to): array
    {
        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'days_with_data' => 0,
            'source_table' => null,
            'metrics' => [
                'impressions' => 0,
                'clicks' => 0,
                'orders' => 0,
                'qty' => 0,
                'revenue' => 0.0,
                'spend' => 0.0,
                'ctr' => null,
                'cvr' => null,
                'roas' => null,
            ],
            'data_quality' => ['partial_day_excluded', 'missing_metric', 'low_volume'],
        ];
    }

    private function metricDelta(array $before, array $after): array
    {
        $delta = [];
        foreach (['impressions', 'clicks', 'orders', 'qty', 'revenue', 'spend', 'ctr', 'cvr', 'roas'] as $metric) {
            $beforeValue = $before[$metric] ?? null;
            $afterValue = $after[$metric] ?? null;
            $absolute = $beforeValue !== null && $afterValue !== null
                ? $afterValue - $beforeValue
                : null;

            $delta[$metric] = [
                'before_value' => $beforeValue,
                'after_value' => $afterValue,
                'absolute_delta' => $absolute,
                'percentage_delta' => $beforeValue !== null && $beforeValue != 0 && $absolute !== null
                    ? round(($absolute / $beforeValue) * 100, 2)
                    : null,
                'is_available' => $beforeValue !== null && $afterValue !== null,
            ];
        }

        return $delta;
    }

    private function lifecycleStatus(
        MarketplaceAdExperiment $experiment,
        array $baseline,
        array $observation,
        int $completedObservationDays,
    ): string {
        if ($experiment->confounded) {
            return MarketplaceAdExperiment::STATUS_CONFOUNDED;
        }
        if ($completedObservationDays <= 2) {
            return MarketplaceAdExperiment::STATUS_LEARNING;
        }
        if ($completedObservationDays < self::DEFAULT_OBSERVATION_DAYS) {
            return MarketplaceAdExperiment::STATUS_EARLY_SIGNAL;
        }

        $baselineMetrics = $baseline['metrics'];
        $observationMetrics = $observation['metrics'];
        $sufficient = $baselineMetrics['clicks'] >= self::MIN_CLICKS
            && $observationMetrics['clicks'] >= self::MIN_CLICKS
            && $baselineMetrics['orders'] >= self::MIN_ORDERS
            && $observationMetrics['orders'] >= self::MIN_ORDERS;

        return $sufficient
            ? MarketplaceAdExperiment::STATUS_READY_TO_EVALUATE
            : MarketplaceAdExperiment::STATUS_INSUFFICIENT_DATA;
    }

    private function mappedInternalItemId(int $storeId, ?string $channelItemId, ?string $channelCampaignId): ?int
    {
        $campaign = $channelCampaignId
            ? MarketplaceAdCampaign::query()
                ->where('store_id', $storeId)
                ->where('channel_campaign_id', $channelCampaignId)
                ->first()
            : null;

        if ($campaign?->internal_item_id) {
            return (int) $campaign->internal_item_id;
        }

        $mappingQuery = MarketplaceAdItemMap::query()
            ->where(function ($query) use ($storeId) {
                $query->where('store_id', $storeId)->orWhereNull('store_id');
            });

        if ($channelItemId || $channelCampaignId) {
            $mappingQuery->where(function ($query) use ($channelItemId, $channelCampaignId) {
                if ($channelItemId) {
                    $query->where('channel_item_id', (int) $channelItemId);
                }
                if ($channelCampaignId) {
                    $method = $channelItemId ? 'orWhere' : 'where';
                    $query->{$method}('channel_campaign_id', $channelCampaignId);
                }
            });
        } else {
            return null;
        }

        $mapping = $mappingQuery
            ->orderByRaw('CASE WHEN store_id = ? THEN 0 ELSE 1 END', [$storeId])
            ->first();

        return $mapping?->internal_item_id ? (int) $mapping->internal_item_id : null;
    }

    private function singleValue(Collection $changes, string $key): ?float
    {
        if ($changes->count() !== 1) {
            return null;
        }

        $value = $changes->first()[$key] ?? null;
        return $value !== null ? (float) $value : null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }

    private function asLocalDateTime(CarbonInterface|string|null $value): Carbon
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        return $value instanceof CarbonInterface
            ? Carbon::instance($value)->timezone($timezone)
            : Carbon::parse($value ?: 'now', $timezone);
    }
}
