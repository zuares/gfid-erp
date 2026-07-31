<?php

namespace App\Services\Marketplace\Ads;

use App\Models\Item;
use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdsDaily;
use App\Models\MarketplaceAdsSetting;
use App\Models\MarketplaceAdsSyncRun;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplaceProduct;
use App\Models\SkuMapping;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdsDashboardService
{
    public function buildDashboardData(
        Collection $stores,
        int|string|null $storeId,
        ?string $dateFromInput,
        ?string $dateToInput,
        string $compareMode,
        AdsAnalyticsService $analytics
    ): array {
        $dateFrom = $this->safeDate($dateFromInput, now()->subDays(6));
        $dateTo = $this->safeDate($dateToInput, now());
        $isAllStores = $storeId === 'all';
        $storeIds = $isAllStores ? $stores->pluck('id')->all() : [$storeId];

        $kpi = $analytics->getKpiSummary($storeIds, $dateFrom, $dateTo);

        $dailyChartData = MarketplaceAdsDaily::whereIn('store_id', $storeIds)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date')
            ->get(['date', 'impressions', 'clicks', 'spend', 'orders', 'gmv', 'roas'])
            ->map(fn (MarketplaceAdsDaily $row) => [
                'date'        => substr((string) $row->date, 0, 10),
                'impressions' => (int) $row->impressions,
                'clicks'      => (int) $row->clicks,
                'spend'       => (float) $row->spend,
                'orders'      => (int) $row->orders,
                'gmv'         => (float) $row->gmv,
                'roas'        => (float) ($row->roas ?? 0),
            ])
            ->values();

        $dStart = Carbon::parse($dateFrom);
        $dEnd = Carbon::parse($dateTo);

        if ($compareMode === 'prev_month') {
            $prevDateFrom = $dStart->copy()->subMonth()->toDateString();
            $prevDateTo = $dEnd->copy()->subMonth()->toDateString();
        } elseif ($compareMode === 'prev_year') {
            $prevDateFrom = $dStart->copy()->subYear()->toDateString();
            $prevDateTo = $dEnd->copy()->subYear()->toDateString();
        } else {
            $diffDays = $dStart->diffInDays($dEnd);
            $prevDateFrom = $dStart->copy()->subDays($diffDays + 1)->toDateString();
            $prevDateTo = $dStart->copy()->subDay()->toDateString();
        }

        $adsSetting = $isAllStores ? null : MarketplaceAdsSetting::where('store_id', $storeId)->first();
        $manualFeeRatio = ($adsSetting && ($adsSetting->admin_fee_mode ?? 'auto') === 'manual' && $adsSetting->admin_fee_pct !== null)
            ? max(0.0, 1 - ((float) $adsSetting->admin_fee_pct / 100))
            : null;

        $aggFor = function (string $from, string $to) use ($storeIds) {
            return DB::table('marketplace_ad_campaign_dailies')
                ->whereIn('store_id', $storeIds)
                ->whereBetween('date', [$from, $to])
                ->groupBy('store_id', 'channel_campaign_id')
                ->selectRaw('store_id, channel_campaign_id,
                    SUM(expense) se, SUM(broad_gmv) sbg, SUM(direct_gmv) sdg,
                    SUM(clicks) sc, SUM(impressions) si,
                    SUM(broad_order) sbo, SUM(broad_order_amount) sboa,
                    SUM(direct_order) sdo, SUM(direct_order_amount) sdoa')
                ->get()
                ->keyBy(fn ($r) => $r->store_id . '|' . $r->channel_campaign_id);
        };
        $aggCur = $aggFor($dateFrom, $dateTo);
        $aggPrev = $aggFor($prevDateFrom, $prevDateTo);

        $campaigns = MarketplaceAdCampaign::query()
            ->select([
                'id',
                'store_id',
                'channel_campaign_id',
                'channel_item_id',
                'internal_item_id',
                'campaign_name',
                'campaign_status',
                'ad_type',
                'target_roas',
                'campaign_budget',
                'break_even_acos',
                'spend',
                'impressions',
                'clicks',
                'orders',
                'items_sold',
                'gmv',
                'direct_gmv',
                'roas',
            ])
            ->with([
                'internalItem:id,hpp,base_unit_cost,item_category_id',
                'internalItem.category:id,name',
                'store:id,name,channel_id',
                'store.channel:id,code',
            ])
            ->whereIn('store_id', $storeIds)
            ->get();

        [$avgPriceByKey, $unitCogsByKey, $revenueRatioByKey] = $this->preloadCampaignAnalytics($campaigns);

        $campaigns = $campaigns
            ->map(function ($camp) use ($manualFeeRatio, $aggCur, $aggPrev, $avgPriceByKey, $unitCogsByKey, $revenueRatioByKey) {
                $k = $camp->store_id . '|' . $camp->channel_campaign_id;
                $a = $aggCur->get($k);
                $p = $aggPrev->get($k);

                $camp->sum_expense = (float) ($a->se ?? 0);
                $camp->sum_broad_gmv = (float) ($a->sbg ?? 0);
                $camp->sum_direct_gmv = (float) ($a->sdg ?? 0);
                $camp->sum_clicks = (int) ($a->sc ?? 0);
                $camp->sum_impressions = (int) ($a->si ?? 0);
                $camp->sum_broad_orders = (int) ($a->sbo ?? 0);
                $camp->sum_broad_order_amount = (float) ($a->sboa ?? 0);
                $camp->sum_direct_orders = (int) ($a->sdo ?? 0);
                $camp->sum_direct_order_amount = (float) ($a->sdoa ?? 0);
                $camp->sum_prev_expense = (float) ($p->se ?? 0);
                $camp->sum_prev_broad_gmv = (float) ($p->sbg ?? 0);
                $camp->sum_prev_direct_gmv = (float) ($p->sdg ?? 0);
                $camp->sum_prev_clicks = (int) ($p->sc ?? 0);
                $camp->sum_prev_impressions = (int) ($p->si ?? 0);
                $camp->sum_prev_broad_orders = (int) ($p->sbo ?? 0);
                $camp->sum_prev_direct_orders = (int) ($p->sdo ?? 0);

                $camp->sum_gmv = $camp->sum_broad_gmv ?? 0;
                $camp->sum_orders = $camp->sum_broad_orders ?? 0;
                $camp->sum_prev_gmv = $camp->sum_prev_broad_gmv ?? 0;
                $camp->sum_prev_orders = $camp->sum_prev_broad_orders ?? 0;

                $roas = $camp->sum_expense > 0 ? $camp->sum_gmv / $camp->sum_expense : 0;
                $prevRoas = $camp->sum_prev_expense > 0 ? $camp->sum_prev_gmv / $camp->sum_prev_expense : 0;

                $camp->roas = $roas;
                $camp->prev_roas = $prevRoas;
                $camp->roas_growth = $prevRoas > 0 ? (($roas - $prevRoas) / $prevRoas) * 100 : 0;
                $camp->spend_growth = $camp->sum_prev_expense > 0 ? (($camp->sum_expense - $camp->sum_prev_expense) / $camp->sum_prev_expense) * 100 : 0;
                $camp->gmv_growth = $camp->sum_prev_gmv > 0 ? (($camp->sum_gmv - $camp->sum_prev_gmv) / $camp->sum_prev_gmv) * 100 : 0;

                $camp->spend = $camp->sum_expense ?? 0;
                $camp->gmv = $camp->sum_gmv ?? 0;
                $camp->clicks = $camp->sum_clicks ?? 0;
                $camp->orders = $camp->sum_orders ?? 0;
                $camp->items_sold = $camp->sum_broad_order_amount ?? 0;
                $camp->prev_spend = $camp->sum_prev_expense ?? 0;
                $camp->prev_gmv = $camp->sum_prev_gmv ?? 0;
                $camp->prev_clicks = $camp->sum_prev_clicks ?? 0;
                $camp->prev_orders = $camp->sum_prev_orders ?? 0;

                $acos = $camp->gmv > 0 && $camp->spend > 0 ? round($camp->spend / $camp->gmv, 4) : null;
                $camp->acos_pct = $acos !== null ? round($acos * 100, 1) : null;

                $campaignKey = $camp->store_id . '|' . (string) $camp->channel_item_id;
                $camp->unit_cogs = (float) ($unitCogsByKey[$campaignKey] ?? ($camp->internalItem?->hpp ?? $camp->internalItem?->base_unit_cost ?? 0));

                $trueAvgPrice = (float) ($avgPriceByKey[$campaignKey] ?? 0);
                if (! $trueAvgPrice || $trueAvgPrice <= 0) {
                    $trueAvgPrice = ($camp->orders > 0 && $camp->gmv > 0) ? ($camp->gmv / $camp->orders) : 0;
                }
                $camp->cogs_ratio = $trueAvgPrice > 0 ? ($camp->unit_cogs / $trueAvgPrice) : 0;

                if ($manualFeeRatio !== null) {
                    [$ratio, $ratioSource] = [$manualFeeRatio, 'manual'];
                } else {
                    [$ratio, $ratioSource] = $revenueRatioByKey[(string) $camp->channel_item_id]
                        ?? [self::DEFAULT_NET_REVENUE_RATIO, 'default'];
                }
                $camp->net_revenue_ratio = $ratio;
                $camp->net_revenue_ratio_source = $ratioSource;

                $netRevRatio = $camp->net_revenue_ratio ?? self::DEFAULT_NET_REVENUE_RATIO;
                $beAcos = null;
                if ($camp->unit_cogs > 0 && $trueAvgPrice > 0) {
                    $netPerUnit = $trueAvgPrice * $netRevRatio;
                    if ($camp->unit_cogs < $netPerUnit) {
                        $beAcos = round(($netPerUnit - $camp->unit_cogs) / $trueAvgPrice, 6);
                    }
                }
                if ($beAcos === null) {
                    $beAcos = $camp->break_even_acos !== null
                        ? (float) $camp->break_even_acos
                        : $this->deriveBreakEvenAcos($camp->internalItem, (float) $camp->gmv, (int) $camp->orders, $netRevRatio);
                }
                $camp->break_even_acos_pct = $beAcos !== null ? round($beAcos * 100, 1) : null;

                if ($beAcos !== null) {
                    $netRevenue = $camp->gmv * $netRevRatio;
                    $totalCogs = ($camp->unit_cogs > 0 && ($camp->items_sold ?? 0) > 0)
                        ? $camp->unit_cogs * $camp->items_sold
                        : $camp->gmv * ($camp->cogs_ratio ?? 0);
                    $spendAfterTax = $camp->spend * 1.11;
                    $camp->profit_after_ads = round($netRevenue - $totalCogs - $spendAfterTax, 2);
                } else {
                    $camp->profit_after_ads = null;
                }

                $camp->reco = $this->adsRecommendation((float) $camp->spend, $acos, $beAcos, (int) $camp->orders);

                return $camp;
            })
            ->filter(fn ($camp) => in_array($camp->campaign_status, ['ongoing', 'normal']) || $camp->spend > 0 || $camp->prev_spend > 0)
            ->sortByDesc('spend')
            ->values();

        $chanIds = $campaigns->pluck('channel_item_id')->filter()->unique()->map(fn ($v) => (string) $v)->values();
        $catByChan = collect();
        if ($chanIds->isNotEmpty()) {
            $catByChan = DB::table('marketplace_order_items as moi')
                ->join('items as i', 'i.id', '=', 'moi.internal_item_id')
                ->leftJoin('item_categories as cat', 'cat.id', '=', 'i.item_category_id')
                ->whereIn('moi.external_item_id', $chanIds)
                ->whereNotNull('moi.internal_item_id')
                ->groupBy('moi.external_item_id')
                ->selectRaw('moi.external_item_id, MAX(cat.name) as cat_name')
                ->pluck('cat_name', 'external_item_id');
        }
        $campaigns = $campaigns->map(function ($camp) use ($catByChan) {
            $camp->item_category = $camp->internalItem?->category?->name
                ?? ($catByChan[(string) $camp->channel_item_id] ?? null);
            return $camp;
        });

        $syncRuns = MarketplaceAdsSyncRun::whereIn('store_id', $storeIds)
            ->select([
                'id',
                'store_id',
                'sync_type',
                'date_from',
                'date_to',
                'status',
                'total_requests',
                'total_updated',
                'error_message',
                'started_at',
                'finished_at',
            ])
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $lastSuccessRun = MarketplaceAdsSyncRun::whereIn('store_id', $storeIds)
            ->select([
                'id',
                'store_id',
                'sync_type',
                'date_from',
                'date_to',
                'status',
                'total_requests',
                'total_updated',
                'error_message',
                'started_at',
                'finished_at',
            ])
            ->where('status', 'success')
            ->latest('id')
            ->first();

        $heatmapData = $analytics->getHourlyHeatmap($storeIds, $dateFrom, $dateTo);
        $historicalData = $analytics->getHistoricalComparison($storeIds, $dateFrom, $dateTo, 3);

        $itemPerformance = DB::table('marketplace_ad_campaign_dailies as cd')
            ->join('marketplace_ad_campaigns as c', function ($join) {
                $join->on('cd.channel_campaign_id', '=', 'c.channel_campaign_id')
                    ->on('cd.store_id', '=', 'c.store_id');
            })
            ->leftJoin('marketplace_products as p', function ($join) {
                $join->on('c.channel_item_id', '=', 'p.item_id')
                    ->on('c.store_id', '=', 'p.store_id');
            })
            ->whereIn('cd.store_id', $storeIds)
            ->whereNotNull('c.channel_item_id')
            ->whereBetween('cd.date', [$dateFrom, $dateTo])
            ->selectRaw('
                c.channel_item_id,
                c.channel_campaign_id,
                MAX(c.campaign_name) as campaign_name,
                MAX(c.campaign_status) as campaign_status,
                MAX(c.ad_type) as ad_type,
                MAX(p.item_sku) as item_sku,
                MAX(p.item_name) as item_name,
                SUM(cd.impressions) as impressions,
                SUM(cd.clicks) as clicks,
                SUM(cd.expense) as spend,
                SUM(cd.broad_gmv) as gmv,
                SUM(cd.broad_order) as orders,
                SUM(cd.direct_gmv) as direct_gmv_sum
            ')
            ->groupBy('c.channel_item_id', 'c.channel_campaign_id')
            ->orderByDesc('spend')
            ->limit(50)
            ->get();

        $gmsItems = DB::table('marketplace_ads_item_dailies as id')
            ->leftJoin('marketplace_products as p', function ($join) {
                $join->on('id.channel_item_id', '=', 'p.item_id')
                    ->on('id.store_id', '=', 'p.store_id');
            })
            ->leftJoin('marketplace_ad_campaigns as camp', function ($join) {
                $join->on('id.channel_campaign_id', '=', 'camp.channel_campaign_id')
                    ->on('id.store_id', '=', 'camp.store_id');
            })
            ->whereIn('id.store_id', $storeIds)
            ->where(function ($q) use ($dateFrom, $dateTo, $prevDateFrom, $prevDateTo) {
                $q->whereBetween('id.date', [$dateFrom, $dateTo])
                    ->orWhereBetween('id.date', [$prevDateFrom, $prevDateTo]);
            })
            ->selectRaw("
                id.channel_item_id,
                MAX(id.channel_campaign_id) as channel_campaign_id,
                MAX(p.item_sku) as item_sku,
                MAX(p.item_name) as item_name,
                MAX(camp.target_roas) as target_roas,
                MAX(camp.campaign_budget) as campaign_budget,
                MAX(camp.campaign_status) as campaign_status,
                SUM(CASE WHEN id.date >= '{$dateFrom}' AND id.date <= '{$dateTo}' THEN id.impressions ELSE 0 END) as impression,
                SUM(CASE WHEN id.date >= '{$dateFrom}' AND id.date <= '{$dateTo}' THEN id.clicks ELSE 0 END) as click,
                SUM(CASE WHEN id.date >= '{$dateFrom}' AND id.date <= '{$dateTo}' THEN id.expense ELSE 0 END) as expense,
                SUM(CASE WHEN id.date >= '{$dateFrom}' AND id.date <= '{$dateTo}' THEN id.broad_order ELSE 0 END) as broad_order,
                SUM(CASE WHEN id.date >= '{$dateFrom}' AND id.date <= '{$dateTo}' THEN id.broad_gmv ELSE 0 END) as broad_gmv,
                SUM(CASE WHEN id.date >= '{$dateFrom}' AND id.date <= '{$dateTo}' THEN id.direct_order ELSE 0 END) as direct_order,
                SUM(CASE WHEN id.date >= '{$dateFrom}' AND id.date <= '{$dateTo}' THEN id.direct_gmv ELSE 0 END) as direct_gmv,

                SUM(CASE WHEN id.date >= '{$prevDateFrom}' AND id.date <= '{$prevDateTo}' THEN id.impressions ELSE 0 END) as prev_impression,
                SUM(CASE WHEN id.date >= '{$prevDateFrom}' AND id.date <= '{$prevDateTo}' THEN id.clicks ELSE 0 END) as prev_click,
                SUM(CASE WHEN id.date >= '{$prevDateFrom}' AND id.date <= '{$prevDateTo}' THEN id.expense ELSE 0 END) as prev_expense,
                SUM(CASE WHEN id.date >= '{$prevDateFrom}' AND id.date <= '{$prevDateTo}' THEN id.broad_order ELSE 0 END) as prev_broad_order,
                SUM(CASE WHEN id.date >= '{$prevDateFrom}' AND id.date <= '{$prevDateTo}' THEN id.broad_gmv ELSE 0 END) as prev_broad_gmv,
                SUM(CASE WHEN id.date >= '{$prevDateFrom}' AND id.date <= '{$prevDateTo}' THEN id.direct_order ELSE 0 END) as prev_direct_order,
                SUM(CASE WHEN id.date >= '{$prevDateFrom}' AND id.date <= '{$prevDateTo}' THEN id.direct_gmv ELSE 0 END) as prev_direct_gmv
            ")
            ->groupBy('id.channel_item_id')
            ->orderByDesc('expense')
            ->get()
            ->map(fn ($item) => $item);

        $autoCampaign = $isAllStores ? null : MarketplaceAdCampaign::select([
                'id',
                'store_id',
                'channel_campaign_id',
                'channel_item_id',
                'campaign_name',
                'campaign_status',
                'campaign_budget',
                'target_roas',
                'ad_type',
            ])
            ->where('store_id', $storeId)
            ->where('ad_type', 'auto')
            ->first();

        return compact(
            'dateFrom',
            'dateTo',
            'compareMode',
            'kpi',
            'dailyChartData',
            'campaigns',
            'syncRuns',
            'heatmapData',
            'historicalData',
            'itemPerformance',
            'lastSuccessRun',
            'adsSetting',
            'gmsItems',
            'autoCampaign'
        ) + [
            'storeId' => $storeId,
            'stores' => $stores,
        ];
    }

    private function safeDate(?string $value, Carbon $fallback): string
    {
        if (! $value) {
            return $fallback->toDateString();
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return $fallback->toDateString();
        }
    }

    /**
     * @return array{0: array<string, float>, 1: array<string, float>, 2: array<string, array{0: float, 1: string}>}
     */
    private function preloadCampaignAnalytics(Collection $campaigns): array
    {
        $realCampaigns = $campaigns->filter(function ($camp) {
            $itemId = (string) ($camp->channel_item_id ?? '');
            return $itemId !== '' && ! str_starts_with($itemId, 'GMS-');
        });

        $storeIds = $realCampaigns->pluck('store_id')->filter()->unique()->values();
        $itemIds = $realCampaigns->pluck('channel_item_id')->filter()->map(fn ($v) => (string) $v)->unique()->values();

        if ($storeIds->isEmpty() || $itemIds->isEmpty()) {
            return [[], [], []];
        }

        $channelCodesByStore = $realCampaigns
            ->mapWithKeys(function ($camp) {
                $code = strtolower((string) data_get($camp, 'store.channel.code', ''));
                return [$camp->store_id => $code ?: null];
            })
            ->all();

        $products = MarketplaceProduct::query()
            ->whereIn('store_id', $storeIds)
            ->whereIn('item_id', $itemIds)
            ->with([
                'models' => function ($q) {
                    $q->select('id', 'marketplace_product_id', 'model_id', 'model_sku', 'price', 'raw_json');
                },
            ])
            ->get(['id', 'store_id', 'item_id']);

        $modelSkus = $products
            ->flatMap(fn ($product) => $product->models->pluck('model_sku'))
            ->filter()
            ->map(fn ($v) => (string) $v)
            ->unique()
            ->values();

        $mappingBySku = [];
        if ($modelSkus->isNotEmpty()) {
            $mappings = SkuMapping::with(['item:id,hpp,base_unit_cost'])
                ->whereIn('marketplace_sku', $modelSkus)
                ->get(['id', 'marketplace_sku', 'channel_code', 'item_id']);

            foreach ($mappings as $mapping) {
                $sku = (string) $mapping->marketplace_sku;
                $channel = strtolower((string) ($mapping->channel_code ?? '')) ?: '__global';
                $mappingBySku[$sku][$channel] = $mapping;
            }
        }

        $avgPriceByKey = [];
        $unitCogsByKey = [];

        foreach ($products as $product) {
            $key = $product->store_id . '|' . $product->item_id;
            $channelCode = strtolower((string) ($channelCodesByStore[$product->store_id] ?? '')) ?: '__global';

            $prices = [];
            $hpps = [];

            foreach ($product->models as $model) {
                $currentPrice = data_get($model->raw_json, 'price_info.0.current_price');
                if ($currentPrice === null && (float) $model->price > 0) {
                    $currentPrice = $model->price;
                }
                if ($currentPrice !== null && (float) $currentPrice > 0) {
                    $prices[] = (float) $currentPrice;
                }

                $sku = (string) ($model->model_sku ?? '');
                if ($sku === '') {
                    continue;
                }

                $mapping = $mappingBySku[$sku][$channelCode] ?? $mappingBySku[$sku]['__global'] ?? null;
                if ($mapping && $mapping->item) {
                    $hpp = (float) ($mapping->item->hpp ?? $mapping->item->base_unit_cost ?? 0);
                    if ($hpp > 0) {
                        $hpps[] = $hpp;
                    }
                }
            }

            if (! empty($prices)) {
                $avgPriceByKey[$key] = array_sum($prices) / count($prices);
            }
            if (! empty($hpps)) {
                $unitCogsByKey[$key] = array_sum($hpps) / count($hpps);
            }
        }

        $revenueRatioByKey = [];
        $orderItemsSub = MarketplaceOrderItem::query()
            ->select('external_item_id', 'order_id')
            ->distinct()
            ->whereIn('external_item_id', $itemIds)
            ->whereNotNull('order_id');

        $ratioRows = MarketplaceOrderSettlement::query()
            ->joinSub($orderItemsSub, 'oi', function ($join) {
                $join->on('oi.order_id', '=', 'marketplace_order_settlements.order_id');
            })
            ->where('final_income', '>', 0)
            ->groupBy('oi.external_item_id')
            ->selectRaw('oi.external_item_id, SUM(marketplace_order_settlements.final_income) as total_final_income, SUM(marketplace_order_settlements.buyer_payment_amount) as total_item_value')
            ->get();

        foreach ($ratioRows as $row) {
            $itemId = (string) ($row->external_item_id ?? '');
            $totalItemValue = (float) ($row->total_item_value ?? 0);
            $totalFinalIncome = (float) ($row->total_final_income ?? 0);
            if ($itemId === '' || $totalItemValue <= 0) {
                continue;
            }

            $revenueRatioByKey[$itemId] = [round(min(1, $totalFinalIncome / $totalItemValue), 4), 'item'];
        }

        return [$avgPriceByKey, $unitCogsByKey, $revenueRatioByKey];
    }

    private function deriveBreakEvenAcos(?Item $item, float $gmv, int $units, ?float $netRevRatio = null): ?float
    {
        if (! $item || $units <= 0 || $gmv <= 0) {
            return null;
        }

        $hpp = (float) ($item->hpp ?? $item->base_unit_cost ?? 0);
        if ($hpp <= 0) {
            return null;
        }

        $avgPrice = $gmv / $units;
        $netPerUnit = $avgPrice * ($netRevRatio ?? 1.0);
        if ($hpp >= $netPerUnit) {
            return null;
        }

        return round(($netPerUnit - $hpp) / $avgPrice, 6);
    }

    private function adsRecommendation(float $spend, ?float $acos, ?float $breakEvenAcos, int $orders): array
    {
        if ($spend === 0.0) {
            return ['label' => 'Tidak Aktif', 'color' => '#94a3b8', 'icon' => '⚪', 'class' => 'reco-nodata'];
        }
        if ($orders === 0) {
            return ['label' => 'Stop — 0 Konversi', 'color' => '#b91c1c', 'icon' => '🔴', 'class' => 'reco-stop'];
        }
        if ($breakEvenAcos === null) {
            return ['label' => 'Set Break-Even', 'color' => '#b45309', 'icon' => '⚠️', 'class' => 'reco-warn'];
        }
        if ($acos === null) {
            return ['label' => 'Data Tidak Lengkap', 'color' => '#94a3b8', 'icon' => '⚪', 'class' => 'reco-nodata'];
        }

        $ratio = $acos / $breakEvenAcos;

        if ($ratio <= 0.60) {
            return ['label' => 'Scale — Naikkan Budget', 'color' => '#16a34a', 'icon' => '🚀', 'class' => 'reco-scale'];
        }
        if ($ratio <= 0.85) {
            return ['label' => 'Pertahankan', 'color' => '#2563eb', 'icon' => '✅', 'class' => 'reco-ok'];
        }
        if ($ratio <= 1.00) {
            return ['label' => 'Perhatikan — Margin Tipis', 'color' => '#d97706', 'icon' => '⚡', 'class' => 'reco-warn'];
        }

        return ['label' => 'Stop / Kurangi Bid', 'color' => '#b91c1c', 'icon' => '🔴', 'class' => 'reco-stop'];
    }
}
