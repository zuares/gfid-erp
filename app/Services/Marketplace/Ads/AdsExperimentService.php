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
    public const MIN_DAYS_FOR_VERDICT = 7;
    public const MATERIAL_CHANGE_PERCENT = 5.0;

    public function __construct(
        protected ItemHppResolver $hppResolver,
        protected AdsExperimentCalculator $calculator,
        protected AdsDashboardService $dashboardService,
    ) {}

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

        $changedAtLocal = $this->asLocalDateTime($changedAt);
        $sameDay = $this->sameDayExperimentForScope(
            $store,
            $channelCampaignId,
            $channelItemId,
            $changedAtLocal,
        );

        // Multiple ROAS edits on one calendar day are one change event from
        // an experiment perspective. Keep the first old value and move the
        // final target forward so the next observation has one clean start.
        if ($sameDay) {
            $conflictReasons = array_values(array_unique(array_merge(
                $sameDay->conflict_reason ?? [],
                $confounders,
            )));
            $sameDay->fill([
                'new_target_roas' => $newTargetRoas,
                'changed_at' => $changedAtLocal,
                'calculation_snapshot' => $this->targetRoasSnapshot(
                    $sameDay->old_target_roas !== null ? (float) $sameDay->old_target_roas : $oldTargetRoas,
                    $newTargetRoas,
                    $sameDay->internal_item_id ? (int) $sameDay->internal_item_id : null,
                    $sameDay->source_granularity ?: ($channelCampaignId ? 'campaign' : ($channelItemId ? 'item' : 'store')),
                ),
                'confounded' => $sameDay->confounded || $conflictReasons !== [],
                'conflict_reason' => $conflictReasons ?: null,
            ]);
            $sameDay->save();

            return $sameDay->refresh();
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

    public function activeExperimentForScope(
        Store $store,
        ?string $channelCampaignId,
        ?string $channelItemId,
        CarbonInterface|string|null $at = null,
    ): ?MarketplaceAdExperiment {
        $atLocal = $this->asLocalDateTime($at);
        $date = $atLocal->toDateString();

        return $this->scopeExperimentQuery($store, $channelCampaignId, $channelItemId)
            ->whereNotIn('lifecycle_status', [
                MarketplaceAdExperiment::STATUS_COMPLETED,
                MarketplaceAdExperiment::STATUS_CONFOUNDED,
            ])
            ->whereDate('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->get()
            ->first(function (MarketplaceAdExperiment $experiment) use ($atLocal): bool {
                $effectiveDate = Carbon::parse($experiment->effective_date->toDateString(), $atLocal->getTimezone());
                return $effectiveDate->copy()->addDays((int) $experiment->observation_days)->toDateString()
                    >= $atLocal->toDateString();
            });
    }

    public function sameDayExperimentForScope(
        Store $store,
        ?string $channelCampaignId,
        ?string $channelItemId,
        CarbonInterface|string|null $at = null,
    ): ?MarketplaceAdExperiment {
        $date = $this->asLocalDateTime($at)->toDateString();

        return $this->scopeExperimentQuery($store, $channelCampaignId, $channelItemId)
            ->where('change_type', MarketplaceAdExperiment::CHANGE_TARGET_ROAS)
            ->whereDate('changed_at', $date)
            ->orderByDesc('changed_at')
            ->orderByDesc('id')
            ->first();
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

        $baseline = $this->periodMetrics(
            $experiment,
            $baselineFrom,
            $baselineTo,
            $experiment->old_price !== null ? (float) $experiment->old_price : ($experiment->new_price !== null ? (float) $experiment->new_price : null),
        );
        $observation = $observationFrom->lte($observationTo)
            ? $this->periodMetrics(
                $experiment,
                $observationFrom,
                $observationTo,
                $experiment->new_price !== null ? (float) $experiment->new_price : ($experiment->old_price !== null ? (float) $experiment->old_price : null),
            )
            : $this->emptyPeriod($observationFrom, $observationTo);

        $completedObservationDays = $observationFrom->lte($observationTo)
            ? $observationFrom->diffInDays($observationTo) + 1
            : 0;
        $conflicts = $this->detectConflicts($experiment, $effectiveDate, $observationWindowEnd);
        $dataSufficiency = $this->dataSufficiency(
            $baseline,
            $observation,
            $completedObservationDays,
            $experiment,
        );
        $impact = $this->impactAnalysis($baseline, $observation);
        $status = $this->lifecycleStatus(
            $experiment,
            $baseline,
            $observation,
            $completedObservationDays,
            $conflicts,
        );
        $verdict = $this->determineVerdict($impact, $dataSufficiency, $conflicts);

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
            'verdict' => $verdict,
            'baseline' => $baseline,
            'observation' => $observation,
            'metric_delta' => $this->metricDelta($baseline['metrics'], $observation['metrics']),
            'impact' => $impact,
            'data_sufficiency' => $dataSufficiency,
            'conflicts' => $conflicts,
            'data_quality' => [
                'experiment_flags' => $experiment->data_quality_flags ?? [],
                'baseline_flags' => $baseline['data_quality'],
                'observation_flags' => $observation['data_quality'],
                'conflict_flags' => collect($conflicts)->pluck('reason')->values()->all(),
                'mapping_status' => $experiment->mapping_status,
                'actual_profit_ready' => (bool) ($observation['metrics']['actual_profit_ready'] ?? false),
                'profit_estimated' => (bool) ($observation['metrics']['profit_estimated'] ?? true),
            ],
            'calculation_snapshot' => $experiment->calculation_snapshot ?? [],
        ];
    }

    /**
     * Simulate price/target-ROAS inputs from one observed experiment period.
     * This method only reads fact data and never writes to the experiment or
     * ad platform.
     *
     * @return array<string, mixed>
     */
    public function simulate(MarketplaceAdExperiment $experiment, array $input = []): array
    {
        $details = $this->details($experiment);
        $periodName = ($input['period'] ?? 'observation') === 'baseline' ? 'baseline' : 'observation';
        $period = $details[$periodName];
        $metrics = $period['metrics'];

        $price = array_key_exists('price', $input)
            ? $this->nullableFloat($input['price'])
            : ($experiment->new_price !== null
                ? (float) $experiment->new_price
                : ($experiment->old_price !== null ? (float) $experiment->old_price : null));
        if (($price === null || $price <= 0) && ($metrics['qty'] ?? 0) > 0 && ($metrics['revenue'] ?? 0) > 0) {
            $price = (float) $metrics['revenue'] / (float) $metrics['qty'];
        }

        $hpp = $experiment->internal_item_id
            ? $this->hppResolver->resolve((int) $experiment->internal_item_id)
            : 0.0;
        [$netRevenueRatio, $ratioSource] = $this->dashboardService->resolveConfiguredNetRevenueRatio(
            $experiment->channel_item_id,
            (int) $experiment->store_id,
        );

        $spend = array_key_exists('spend', $input) ? (float) $input['spend'] : (float) ($metrics['spend'] ?? 0);
        $clicks = array_key_exists('clicks', $input) ? (int) $input['clicks'] : (int) ($metrics['clicks'] ?? 0);
        $qty = array_key_exists('qty', $input) ? (float) $input['qty'] : (float) ($metrics['qty'] ?? 0);
        $targetRoas = array_key_exists('target_roas', $input)
            ? $this->nullableFloat($input['target_roas'])
            : ($experiment->new_target_roas !== null ? (float) $experiment->new_target_roas : null);

        $result = $this->calculator->simulate(
            (float) ($price ?? 0),
            $hpp,
            $netRevenueRatio,
            max(0.0, $spend),
            max(0, $clicks),
            max(0.0, $qty),
            $targetRoas,
            (bool) ($metrics['qty_estimated'] ?? true),
        );

        $result['assumptions']['price_source'] = array_key_exists('price', $input)
            ? 'input'
            : (($experiment->new_price !== null || $experiment->old_price !== null) ? 'experiment' : 'observed_gmv_per_qty');
        $result['assumptions']['net_revenue_ratio_source'] = $ratioSource;
        $result['assumptions']['mapping_status'] = $experiment->mapping_status;
        $result['assumptions']['actual_profit_ready'] = (bool) ($period['metrics']['actual_profit_ready'] ?? false);

        return [
            'experiment_id' => (int) $experiment->id,
            'period' => $periodName,
            'source_period' => [
                'from' => $period['from'],
                'to' => $period['to'],
                'days_with_data' => $period['days_with_data'],
            ],
            'result' => $result,
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
            'profit_basis' => AdsExperimentCalculator::PROFIT_BASIS,
            'mapping_status' => $internalItemId ? 'mapped' : 'missing_mapping',
            'confounded' => false,
            'data_quality_flags' => array_values(array_unique($flags)),
            'baseline_days' => self::DEFAULT_BASELINE_DAYS,
            'observation_days' => self::DEFAULT_OBSERVATION_DAYS,
            'calculation_version' => 'phase2-v1',
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
            'fee_mode' => 'configured_net_revenue_ratio',
            'fee_source' => 'store_setting_or_settlement',
            'ads_vat_factor' => 1.11,
            'price_basis' => 'marketplace_product_model',
            'profit_basis' => AdsExperimentCalculator::PROFIT_BASIS,
            'source_granularity' => $sourceGranularity,
            'mapping_status' => $internalItemId ? 'mapped' : 'missing_mapping',
            'calculation_version' => 'phase2-v1',
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
            'fee_mode' => 'configured_net_revenue_ratio',
            'fee_source' => 'store_setting_or_settlement',
            'ads_vat_factor' => 1.11,
            'price_basis' => 'observed_gmv_per_order',
            'profit_basis' => AdsExperimentCalculator::PROFIT_BASIS,
            'source_granularity' => $sourceGranularity,
            'mapping_status' => $internalItemId ? 'mapped' : 'missing_mapping',
            'calculation_version' => 'phase2-v1',
        ];
    }

    private function periodMetrics(
        MarketplaceAdExperiment $experiment,
        CarbonInterface $from,
        CarbonInterface $to,
        ?float $priceOverride = null,
    ): array {
        if ($from->gt($to)) {
            return $this->emptyPeriod($from, $to);
        }

        [$table, $query] = $this->factQuery($experiment, $from, $to);
        $isCampaignFact = $table === 'marketplace_ad_campaign_dailies';
        $isItemFact = $table === 'marketplace_ads_item_dailies';
        $aggregate = (clone $query)
            ->selectRaw('COALESCE(SUM(impressions), 0) as impressions')
            ->selectRaw('COALESCE(SUM(clicks), 0) as clicks')
            ->selectRaw('COALESCE(SUM(' . ($isCampaignFact ? 'expense' : 'expense') . '), 0) as spend')
            ->selectRaw('COALESCE(SUM(' . ($isCampaignFact ? 'broad_order' : 'broad_order') . '), 0) as orders')
            ->selectRaw('COALESCE(SUM(' . ($isCampaignFact ? 'broad_gmv' : 'broad_gmv') . '), 0) as revenue')
            ->when($isCampaignFact, fn ($q) => $q->selectRaw('COALESCE(SUM(broad_order_amount), 0) as qty_reported'))
            ->first();
        $daysWithData = (int) (clone $query)->distinct()->count('date');

        $qtyFromItemReports = 0.0;
        if ($isItemFact) {
            $itemRows = (clone $query)->get(['raw_json']);
            foreach ($itemRows as $itemRow) {
                $raw = $itemRow->raw_json;
                if (is_string($raw)) {
                    $raw = json_decode($raw, true) ?: [];
                }
                $reportedQty = $this->reportedQty($raw);
                if ($reportedQty !== null) {
                    $qtyFromItemReports += $reportedQty;
                }
            }
        }

        $orders = (int) ($aggregate->orders ?? 0);
        $qtyReported = $isItemFact
            ? $qtyFromItemReports
            : (float) ($aggregate->qty_reported ?? 0);
        $usesQtyFallback = $qtyReported <= 0;
        $qty = $usesQtyFallback ? (float) $orders : $qtyReported;
        $impressions = (int) ($aggregate->impressions ?? 0);
        $clicks = (int) ($aggregate->clicks ?? 0);
        $spend = (float) ($aggregate->spend ?? 0);
        $revenue = (float) ($aggregate->revenue ?? 0);

        $price = $priceOverride !== null && $priceOverride > 0 ? $priceOverride : null;
        $priceEstimated = false;
        if (($price === null || $price <= 0) && $qty > 0 && $revenue > 0) {
            $price = $revenue / $qty;
            $priceEstimated = true;
        }
        $hpp = $experiment->internal_item_id
            ? $this->hppResolver->resolve((int) $experiment->internal_item_id)
            : 0.0;
        [$netRevenueRatio] = $this->dashboardService->resolveConfiguredNetRevenueRatio(
            $experiment->channel_item_id,
            (int) $experiment->store_id,
        );

        $quality = ['partial_day_excluded'];
        if ($usesQtyFallback) {
            $quality[] = 'estimated_qty';
        }
        if ($priceEstimated) {
            $quality[] = 'estimated_price';
        }
        if ($hpp <= 0) {
            $quality[] = 'missing_hpp';
        }
        if ($price === null) {
            $quality[] = 'missing_price';
        }
        if ($clicks < self::MIN_CLICKS || $orders < self::MIN_ORDERS) {
            $quality[] = 'low_volume';
        }
        if ($daysWithData === 0) {
            $quality[] = 'missing_metric';
        }

        $baseMetrics = [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'orders' => $orders,
            'qty' => $qty,
            'revenue' => round($revenue, 2),
            'spend' => round($spend, 2),
            'ctr' => $impressions > 0 ? round($clicks / $impressions, 6) : null,
            'cvr' => $clicks > 0 ? round($orders / $clicks, 6) : null,
            'roas' => $spend > 0 ? round($revenue / $spend, 4) : null,
            'qty_source' => $usesQtyFallback ? 'order_fallback' : 'reported',
            'qty_estimated' => $usesQtyFallback,
        ];
        $calculatedMetrics = $this->calculator->periodMetrics(
            $baseMetrics,
            $price,
            $hpp,
            $netRevenueRatio,
            $usesQtyFallback,
            $priceEstimated,
            $experiment->mapping_status === 'mapped' && $hpp > 0,
        );
        if ($calculatedMetrics['profit_estimated']) {
            $quality[] = 'estimated_profit';
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'days_with_data' => $daysWithData,
            'source_table' => $table,
            'metrics' => array_merge($baseMetrics, $calculatedMetrics),
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
                'qty_source' => 'none',
                'qty_estimated' => true,
                'net_revenue' => 0.0,
                'ad_cost_incl_vat' => 0.0,
                'total_cogs' => null,
                'profit' => null,
                'profit_estimated' => true,
                'actual_profit_ready' => false,
                'price' => null,
                'hpp' => null,
                'net_revenue_ratio' => null,
                'contribution_per_unit' => null,
                'break_even_qty' => null,
                'cvr_bep' => null,
                'break_even_roas' => null,
                'profit_basis' => AdsExperimentCalculator::PROFIT_BASIS,
                'ads_vat_factor' => AdsExperimentCalculator::ADS_VAT_FACTOR,
            ],
            'data_quality' => ['partial_day_excluded', 'missing_metric', 'low_volume'],
        ];
    }

    private function metricDelta(array $before, array $after): array
    {
        $delta = [];
        foreach (['impressions', 'clicks', 'orders', 'qty', 'revenue', 'spend', 'ctr', 'cvr', 'roas', 'profit', 'break_even_qty', 'cvr_bep', 'break_even_roas'] as $metric) {
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
        array $conflicts = [],
    ): string {
        if ($experiment->confounded || $conflicts !== []) {
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

    /**
     * Build the causal-ish funnel used by the UI. The service intentionally
     * reports observed deltas and does not claim that every delta is caused
     * by the change; conflict and sufficiency checks qualify the verdict.
     *
     * @return array<string, mixed>
     */
    private function impactAnalysis(array $baseline, array $observation): array
    {
        $before = $baseline['metrics'] ?? [];
        $after = $observation['metrics'] ?? [];
        $steps = [
            'traffic' => ['label' => 'Traffic', 'metric' => 'impressions'],
            'ctr' => ['label' => 'CTR', 'metric' => 'ctr'],
            'cvr' => ['label' => 'CVR', 'metric' => 'cvr'],
            'qty' => ['label' => 'Qty', 'metric' => 'qty'],
            'roas' => ['label' => 'ROAS', 'metric' => 'roas'],
            'profit' => ['label' => 'Profit', 'metric' => 'profit'],
        ];

        $impact = [];
        foreach ($steps as $key => $step) {
            $metric = $step['metric'];
            $beforeValue = $before[$metric] ?? null;
            $afterValue = $after[$metric] ?? null;
            $delta = $beforeValue !== null && $afterValue !== null
                ? (float) $afterValue - (float) $beforeValue
                : null;
            $changePercent = $beforeValue !== null && (float) $beforeValue != 0.0 && $delta !== null
                ? round(($delta / (float) $beforeValue) * 100, 2)
                : null;

            $impact[$key] = [
                'label' => $step['label'],
                'metric' => $metric,
                'before' => $beforeValue,
                'after' => $afterValue,
                'delta' => $delta !== null ? round($delta, 6) : null,
                'change_percent' => $changePercent,
                'direction' => $this->direction($delta, $changePercent),
                'available' => $beforeValue !== null && $afterValue !== null,
            ];
        }

        $profitReady = (bool) ($before['actual_profit_ready'] ?? false)
            && (bool) ($after['actual_profit_ready'] ?? false);
        $impact['profit']['measurement'] = $profitReady
            ? 'actual'
            : (($before['profit'] !== null && $after['profit'] !== null) ? 'estimated' : 'unavailable');
        $impact['profit']['estimated'] = $impact['profit']['measurement'] !== 'actual';

        return [
            'sequence' => ['traffic', 'ctr', 'cvr', 'qty', 'roas', 'profit'],
            'steps' => $impact,
            'profit_measurement' => $impact['profit']['measurement'],
            'note' => 'Delta adalah perbandingan baseline dan observation; verdict dibatasi oleh sufficiency dan conflict check.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dataSufficiency(
        array $baseline,
        array $observation,
        int $completedObservationDays,
        MarketplaceAdExperiment $experiment,
    ): array {
        $baselineMetrics = $baseline['metrics'] ?? [];
        $observationMetrics = $observation['metrics'] ?? [];
        $checks = [
            'baseline_clicks' => [
                'passed' => (int) ($baselineMetrics['clicks'] ?? 0) >= self::MIN_CLICKS,
                'actual' => (int) ($baselineMetrics['clicks'] ?? 0),
                'required' => self::MIN_CLICKS,
            ],
            'observation_clicks' => [
                'passed' => (int) ($observationMetrics['clicks'] ?? 0) >= self::MIN_CLICKS,
                'actual' => (int) ($observationMetrics['clicks'] ?? 0),
                'required' => self::MIN_CLICKS,
            ],
            'baseline_orders' => [
                'passed' => (int) ($baselineMetrics['orders'] ?? 0) >= self::MIN_ORDERS,
                'actual' => (int) ($baselineMetrics['orders'] ?? 0),
                'required' => self::MIN_ORDERS,
            ],
            'observation_orders' => [
                'passed' => (int) ($observationMetrics['orders'] ?? 0) >= self::MIN_ORDERS,
                'actual' => (int) ($observationMetrics['orders'] ?? 0),
                'required' => self::MIN_ORDERS,
            ],
            'observation_window' => [
                'passed' => $completedObservationDays >= min((int) $experiment->observation_days, self::MIN_DAYS_FOR_VERDICT),
                'actual' => $completedObservationDays,
                'required' => min((int) $experiment->observation_days, self::MIN_DAYS_FOR_VERDICT),
            ],
        ];
        $metricReady = collect($checks)->every(fn (array $check): bool => $check['passed']);
        $profitReady = $metricReady
            && (bool) ($baselineMetrics['actual_profit_ready'] ?? false)
            && (bool) ($observationMetrics['actual_profit_ready'] ?? false);

        $reasons = collect($checks)
            ->filter(fn (array $check): bool => ! $check['passed'])
            ->keys()
            ->values()
            ->all();
        if ($experiment->mapping_status !== 'mapped') {
            $reasons[] = 'missing_mapping';
        }
        if (! $profitReady) {
            $reasons[] = 'profit_not_actual';
        }

        return [
            'metric_ready' => $metricReady,
            'profit_ready' => $profitReady,
            'ready' => $profitReady,
            'checks' => $checks,
            'reasons' => array_values(array_unique($reasons)),
            'thresholds' => [
                'min_clicks_per_period' => self::MIN_CLICKS,
                'min_orders_per_period' => self::MIN_ORDERS,
                'min_observation_days' => min((int) $experiment->observation_days, self::MIN_DAYS_FOR_VERDICT),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $impact
     * @param array<string, mixed> $sufficiency
     * @param array<int, array<string, mixed>> $conflicts
     */
    private function determineVerdict(array $impact, array $sufficiency, array $conflicts): string
    {
        if ($conflicts !== []) {
            return 'CONFOUNDED';
        }
        if (! ($sufficiency['metric_ready'] ?? false)) {
            return 'INSUFFICIENT_DATA';
        }
        if (! ($sufficiency['profit_ready'] ?? false)) {
            return 'INCONCLUSIVE';
        }

        $steps = $impact['steps'] ?? [];
        $profit = $steps['profit']['change_percent'] ?? null;
        $roas = $steps['roas']['change_percent'] ?? null;
        $cvr = $steps['cvr']['change_percent'] ?? null;
        if ($profit === null) {
            return 'INCONCLUSIVE';
        }
        if (abs((float) $profit) < self::MATERIAL_CHANGE_PERCENT) {
            return 'NO_CLEAR_IMPACT';
        }
        if ((float) $profit > 0) {
            return ($roas !== null && $roas < -self::MATERIAL_CHANGE_PERCENT)
                || ($cvr !== null && $cvr < -self::MATERIAL_CHANGE_PERCENT)
                ? 'MIXED'
                : 'POSITIVE';
        }

        return ($roas !== null && $roas > self::MATERIAL_CHANGE_PERCENT)
            || ($cvr !== null && $cvr > self::MATERIAL_CHANGE_PERCENT)
            ? 'MIXED'
            : 'NEGATIVE';
    }

    private function direction(?float $delta, ?float $changePercent): string
    {
        if ($delta === null) {
            return 'unavailable';
        }
        if ($changePercent !== null && abs($changePercent) < self::MATERIAL_CHANGE_PERCENT) {
            return 'flat';
        }

        return $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
    }

    /**
     * Detect another experiment change overlapping this observation window.
     * Same event rows are ignored because a single price update can create
     * multiple scoped rows for one campaign/item.
     *
     * @return array<int, array<string, mixed>>
     */
    private function detectConflicts(
        MarketplaceAdExperiment $experiment,
        CarbonInterface $effectiveDate,
        CarbonInterface $observationWindowEnd,
    ): array {
        $conflicts = collect($experiment->conflict_reason ?? [])
            ->map(fn ($reason): array => [
                'reason' => (string) $reason,
                'type' => 'recorded',
            ])
            ->values();
        if ($experiment->confounded && $conflicts->isEmpty()) {
            $conflicts->push([
                'reason' => 'recorded_confounder',
                'type' => 'recorded',
            ]);
        }

        $query = MarketplaceAdExperiment::query()
            ->where('store_id', $experiment->store_id)
            ->where('id', '!=', $experiment->id)
            ->where('change_event_id', '!=', $experiment->change_event_id)
            ->whereBetween('changed_at', [
                $effectiveDate->copy()->startOfDay(),
                $observationWindowEnd->copy()->endOfDay(),
            ]);

        if ($experiment->source_granularity === 'item' && $experiment->channel_item_id) {
            $query->where('channel_item_id', $experiment->channel_item_id);
        } elseif ($experiment->source_granularity === 'campaign' && $experiment->channel_campaign_id) {
            $query->where('channel_campaign_id', $experiment->channel_campaign_id);
        }

        foreach ($query->get(['id', 'change_type', 'changed_at', 'channel_campaign_id', 'channel_item_id']) as $row) {
            // Same-day target ROAS edits are treated as one grouped change.
            // This also keeps historical rows created before the guard from
            // making every row in that group look confounded on read.
            if (Carbon::parse($row->changed_at)->toDateString() === $effectiveDate->toDateString()) {
                continue;
            }
            $conflicts->push([
                'reason' => 'overlapping_experiment',
                'type' => 'experiment',
                'experiment_id' => (int) $row->id,
                'change_type' => $row->change_type,
                'changed_at' => Carbon::parse($row->changed_at)->toIso8601String(),
                'channel_campaign_id' => $row->channel_campaign_id,
                'channel_item_id' => $row->channel_item_id,
            ]);
        }

        return $conflicts->values()->all();
    }

    private function scopeExperimentQuery(
        Store $store,
        ?string $channelCampaignId,
        ?string $channelItemId,
    ) {
        return MarketplaceAdExperiment::query()
            ->where('store_id', $store->id)
            ->when(
                $channelCampaignId !== null,
                fn ($query) => $query->where('channel_campaign_id', $channelCampaignId),
                fn ($query) => $query->whereNull('channel_campaign_id'),
            )
            ->when(
                $channelItemId !== null,
                fn ($query) => $query->where('channel_item_id', $channelItemId),
                fn ($query) => $query->whereNull('channel_item_id'),
            );
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

    private function reportedQty(mixed $raw): ?float
    {
        if (! is_array($raw)) {
            return null;
        }

        foreach (['broad_order_amount', 'quantity', 'qty', 'item_quantity', 'sold_quantity'] as $key) {
            if (array_key_exists($key, $raw) && is_numeric($raw[$key])) {
                return max(0.0, (float) $raw[$key]);
            }
        }

        return null;
    }

    private function asLocalDateTime(CarbonInterface|string|null $value): Carbon
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        return $value instanceof CarbonInterface
            ? Carbon::instance($value)->timezone($timezone)
            : Carbon::parse($value ?: 'now', $timezone);
    }
}
