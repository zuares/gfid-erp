<?php

namespace App\Services\Marketplace\Ads;

use App\Models\Item;
use App\Models\MarketplaceAdCampaign;
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
    public const DEFAULT_NET_REVENUE_RATIO = 0.781;

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
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }
        $isAllStores = $storeId === 'all';
        $storeIds = $isAllStores ? $stores->pluck('id')->all() : [$storeId];

        // (Removed getKpiSummary)

        // Ringkasan memakai grain level toko sebagai sumber utama. Campaign
        // daily tetap dipakai untuk drill-down, tetapi menjumlahkan semua
        // campaign dapat membesar jika laporan agregat GMS ikut tersimpan.
        $summaryByDate = function (string $from, string $to) use ($storeIds): array {
            $shop = DB::table('marketplace_ads_dailies')
                ->whereIn('store_id', $storeIds)
                ->whereBetween('date', [$from, $to])
                ->selectRaw('date, SUM(impressions) impressions, SUM(clicks) clicks, SUM(spend) spend, SUM(orders) orders, SUM(gmv) gmv')
                ->groupBy('date')
                ->get()
                ->keyBy(fn ($row) => substr((string) $row->date, 0, 10));

            $campaign = DB::table('marketplace_ad_campaign_dailies')
                ->whereIn('store_id', $storeIds)
                ->whereNotLike('channel_campaign_id', 'GMS-%')
                ->whereBetween('date', [$from, $to])
                ->selectRaw('date, SUM(impressions) impressions, SUM(clicks) clicks, SUM(expense) spend, SUM(broad_order) orders, SUM(broad_gmv) gmv')
                ->groupBy('date')
                ->get()
                ->keyBy(fn ($row) => substr((string) $row->date, 0, 10));

            $gms = DB::table('marketplace_ad_campaign_dailies')
                ->whereIn('store_id', $storeIds)
                ->whereLike('channel_campaign_id', 'GMS-%')
                ->whereBetween('date', [$from, $to])
                ->selectRaw('date, SUM(impressions) impressions, SUM(clicks) clicks, SUM(expense) spend, SUM(broad_order) orders, SUM(broad_gmv) gmv')
                ->groupBy('date')
                ->get()
                ->keyBy(fn ($row) => substr((string) $row->date, 0, 10));

            $dates = $shop->keys()->merge($campaign->keys())->merge($gms->keys())->unique()->sort()->values();

            return $dates->mapWithKeys(function (string $date) use ($shop, $campaign, $gms) {
                // Jika total toko sudah tersedia, campaign non-GMS tidak
                // dijumlahkan lagi. GMS ditambahkan sebagai komponen terpisah.
                $base = $shop->get($date) ?? $campaign->get($date);
                $extra = $gms->get($date);
                $row = (object) [
                    'date' => $date,
                    'impressions' => (int) ($base->impressions ?? 0) + (int) ($extra->impressions ?? 0),
                    'clicks' => (int) ($base->clicks ?? 0) + (int) ($extra->clicks ?? 0),
                    'spend' => (float) ($base->spend ?? 0) + (float) ($extra->spend ?? 0),
                    'orders' => (int) ($base->orders ?? 0) + (int) ($extra->orders ?? 0),
                    'gmv' => (float) ($base->gmv ?? 0) + (float) ($extra->gmv ?? 0),
                ];

                return [$date => $row];
            })->all();
        };

        $dailyChartData = collect($summaryByDate($dateFrom, $dateTo))
            ->sortKeys()
            ->map(fn ($row) => [
                'date'        => $row->date,
                'impressions' => $row->impressions,
                'clicks'      => $row->clicks,
                'spend'       => $row->spend,
                'orders'      => $row->orders,
                'gmv'         => $row->gmv,
                'roas'        => (float) ($row->spend > 0 ? $row->gmv / $row->spend : 0),
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
        $sumSummary = function (array $rows): object {
            return collect($rows)->reduce(function ($carry, $row) {
            $carry->spend += $row->spend;
            $carry->gmv += $row->gmv;
            $carry->orders += $row->orders;
            $carry->clicks += $row->clicks;
            $carry->impressions += $row->impressions;
            return $carry;
            }, (object) ['spend' => 0.0, 'gmv' => 0.0, 'orders' => 0, 'clicks' => 0, 'impressions' => 0]);
        };
        $summaryCurrent = $sumSummary($summaryByDate($dateFrom, $dateTo));
        $summaryPrevious = $sumSummary($summaryByDate($prevDateFrom, $prevDateTo));

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
                'campaign_placement',
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
                $camp->sum_prev_broad_order_amount = (float) ($p->sboa ?? 0);

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
                $camp->impressions = $camp->sum_impressions ?? 0;
                $camp->orders = $camp->sum_orders ?? 0;
                // Pada report harian tertentu Shopee tidak mengisi
                // broad_order_amount (pcs). Jangan langsung mengubahnya
                // menjadi 0 lalu menghitung HPP berdasarkan rasio GMV/harga,
                // karena pada filter satu hari hasilnya bisa sangat melenceng.
                // Gunakan order sebagai fallback yang jelas berstatus estimasi.
                $camp->items_sold = $camp->sum_broad_order_amount > 0
                    ? $camp->sum_broad_order_amount
                    : $camp->sum_broad_orders;
                $camp->items_sold_source = $camp->sum_broad_order_amount > 0
                    ? 'api'
                    : ($camp->sum_broad_orders > 0 ? 'order_fallback' : 'unknown');
                
                $camp->prev_spend = $camp->sum_prev_expense ?? 0;
                $camp->prev_gmv = $camp->sum_prev_gmv ?? 0;
                $camp->prev_clicks = $camp->sum_prev_clicks ?? 0;
                $camp->prev_impressions = $camp->sum_prev_impressions ?? 0;
                $camp->prev_orders = $camp->sum_prev_orders ?? 0;
                $camp->prev_items_sold = $camp->sum_prev_broad_order_amount > 0
                    ? $camp->sum_prev_broad_order_amount
                    : $camp->sum_prev_broad_orders;

                $acos = $camp->gmv > 0 && $camp->spend > 0 ? round($camp->spend / $camp->gmv, 4) : null;
                $camp->acos_pct = $acos !== null ? round($acos * 100, 1) : null;

                $campaignKey = $camp->store_id . '|' . (string) $camp->channel_item_id;
                $internalUnitCogs = $this->resolveItemUnitCogs($camp->internalItem);
                $camp->unit_cogs = (float) ($unitCogsByKey[$campaignKey] ?? $internalUnitCogs);

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

                $hasCogsData = $camp->unit_cogs > 0
                    && (($camp->items_sold ?? 0) > 0 || $trueAvgPrice > 0);
                // Profit aktual tetap harus dihitung walaupun produk sudah
                // melewati titik break-even. Break-even hanya metrik batas
                // aman ACOS; ia bukan syarat agar profit bisa dihitung.
                if ($hasCogsData) {
                    $netRevenue = $camp->gmv * $netRevRatio;
                    $totalCogs = ($camp->unit_cogs > 0 && ($camp->items_sold ?? 0) > 0)
                        ? $camp->unit_cogs * $camp->items_sold
                        : $camp->gmv * ($camp->cogs_ratio ?? 0);
                    $spendAfterTax = $camp->spend * 1.11;
                    $camp->profit_after_ads = round($netRevenue - $totalCogs - $spendAfterTax, 2);
                    $camp->net_revenue = $netRevenue;
                    $camp->total_cogs = $totalCogs;
                } else {
                    $camp->profit_after_ads = null;
                    $camp->net_revenue = $camp->gmv * $netRevRatio;
                    $camp->total_cogs = null;
                }

                // Kalkulasi Previous Net & Profit
                $prevNetRevenue = $camp->prev_gmv * $netRevRatio;
                $prevTotalCogs = ($camp->unit_cogs > 0 && ($camp->prev_items_sold ?? 0) > 0)
                    ? $camp->unit_cogs * ($camp->prev_items_sold ?? 0)
                    : $camp->prev_gmv * ($camp->cogs_ratio ?? 0);
                $camp->prev_net_revenue = $prevNetRevenue;
                $camp->prev_total_cogs = $prevTotalCogs;
                $camp->prev_profit_after_ads = $hasCogsData
                    ? round($prevNetRevenue - $prevTotalCogs - ($camp->prev_spend * 1.11), 2)
                    : null;

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

        // -------------------------------------------------------------
        // KPI CALCULATION
        // Directly aggregate from $campaigns so that top KPI 
        // perfectly matches the sum of table data.
        // -------------------------------------------------------------
        $kpi = [
            'current' => (object) [
                'spend' => $summaryCurrent->spend,
                'gmv' => $summaryCurrent->gmv,
                'orders' => $summaryCurrent->orders,
                'clicks' => $summaryCurrent->clicks,
                'impressions' => $summaryCurrent->impressions,
                'net_revenue' => $campaigns->sum('net_revenue'),
                'total_cogs' => $campaigns->sum('total_cogs'),
            ],
            'previous' => (object) [
                'spend' => $summaryPrevious->spend,
                'gmv' => $summaryPrevious->gmv,
                'orders' => $summaryPrevious->orders,
                'clicks' => $summaryPrevious->clicks,
                'impressions' => $summaryPrevious->impressions,
                'net_revenue' => $campaigns->sum('prev_net_revenue'),
                'total_cogs' => $campaigns->sum('prev_total_cogs'),
            ],
            'changes' => []
        ];

        // Profit hanya dijumlahkan dari campaign yang punya HPP; campaign tanpa
        // HPP tidak boleh dianggap sebagai profit 0 atau rugi.
        $knownProfitCampaigns = $campaigns->filter(fn ($camp) => ($camp->spend > 0 || $camp->gmv > 0) && $camp->profit_after_ads !== null);
        $unknownProfitCampaigns = $campaigns->filter(fn ($camp) => ($camp->spend > 0 || $camp->gmv > 0) && $camp->profit_after_ads === null);
        $kpi['current']->net_profit = $knownProfitCampaigns->sum('profit_after_ads');
        $kpi['previous']->net_profit = $campaigns->filter(fn ($camp) => $camp->prev_profit_after_ads !== null)->sum('prev_profit_after_ads');
        $kpi['current']->profit_campaign_count = $knownProfitCampaigns->count();
        $kpi['current']->profit_unknown_campaign_count = $unknownProfitCampaigns->count();
        $kpi['current']->profit_estimated_campaign_count = $knownProfitCampaigns
            ->where('items_sold_source', 'order_fallback')
            ->count();
        $kpi['current']->profit_gmv = $knownProfitCampaigns->sum('gmv');

        // Hitung AOV Net
        $kpi['current']->aov_net = $kpi['current']->orders > 0 ? $kpi['current']->net_revenue / $kpi['current']->orders : 0;
        $kpi['previous']->aov_net = $kpi['previous']->orders > 0 ? $kpi['previous']->net_revenue / $kpi['previous']->orders : 0;

        foreach (['spend', 'gmv', 'orders', 'clicks', 'impressions', 'net_revenue', 'total_cogs', 'net_profit'] as $m) {
            $c = $kpi['current']->$m;
            $p = $kpi['previous']->$m;
            if ($p == 0) {
                $kpi['changes'][$m] = $c > 0 ? null : 0;
            } else {
                $kpi['changes'][$m] = round((($c - $p) / abs($p)) * 100, 2);
            }
        }

        // ROAS adalah metrik turunan, jadi harus dihitung eksplisit agar kartu
        // Ringkasan tidak selalu menampilkan "0% vs lalu".
        $currentRoas = $kpi['current']->spend > 0
            ? $kpi['current']->gmv / $kpi['current']->spend
            : 0;
        $previousRoas = $kpi['previous']->spend > 0
            ? $kpi['previous']->gmv / $kpi['previous']->spend
            : 0;
        $kpi['changes']['roas'] = $previousRoas == 0
            ? ($currentRoas > 0 ? null : 0)
            : round((($currentRoas - $previousRoas) / abs($previousRoas)) * 100, 2);

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
                'created_at',
                'updated_at',
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
        $historicalData = $analytics->getHistoricalComparison($storeIds, $dateFrom, $dateTo, 3, $compareMode);

        $itemPerformanceRaw = DB::table('marketplace_ad_campaign_dailies as cd')
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
                cd.store_id,
                c.channel_item_id,
                MAX(p.item_sku) as item_sku,
                MAX(p.item_name) as item_name,
                MAX(p.image_url) as image_url,
                MAX(p.stock_total) as stock_total,
                MAX(p.price_min) as price_min,
                SUM(cd.impressions) as impressions,
                SUM(cd.clicks) as clicks,
                SUM(cd.expense) as spend,
                SUM(cd.broad_gmv) as gmv,
                SUM(cd.broad_order) as orders,
                SUM(cd.broad_order_amount) as items_sold,
                SUM(cd.direct_gmv) as direct_gmv
            ')
            ->groupBy('cd.store_id', 'c.channel_item_id')
            ->orderByDesc('spend')
            ->get();

        $itemPerformance = $itemPerformanceRaw->map(function ($prod) use ($unitCogsByKey, $revenueRatioByKey, $avgPriceByKey, $manualFeeRatio, $catByChan) {
            $prod->roas = $prod->spend > 0 ? $prod->gmv / $prod->spend : 0;
            $prod->ctr = $prod->impressions > 0 ? ($prod->clicks / $prod->impressions) * 100 : 0;
            $prod->cvr = $prod->clicks > 0 ? ($prod->orders / $prod->clicks) * 100 : 0;
            $prod->cpc = $prod->clicks > 0 ? $prod->spend / $prod->clicks : 0;
            $prod->cpa = $prod->orders > 0 ? $prod->spend / $prod->orders : 0;
            
            $key = $prod->store_id . '|' . $prod->channel_item_id;
            
            $prod->unit_cogs = (float) ($unitCogsByKey[$key] ?? 0);
            
            $trueAvgPrice = (float) ($avgPriceByKey[$key] ?? 0);
            if (! $trueAvgPrice || $trueAvgPrice <= 0) {
                $trueAvgPrice = ($prod->orders > 0 && $prod->gmv > 0) ? ($prod->gmv / $prod->orders) : (float) $prod->price_min;
            }
            
            if ($manualFeeRatio !== null) {
                $netRevRatio = $manualFeeRatio;
            } else {
                $netRevRatio = $revenueRatioByKey[(string) $prod->channel_item_id][0] ?? self::DEFAULT_NET_REVENUE_RATIO;
            }
            
            $prod->net_revenue = $prod->gmv * $netRevRatio;
            
            $itemsSold = (float) ($prod->items_sold ?? $prod->orders);
            
            if ($prod->unit_cogs > 0 && $trueAvgPrice > 0) {
                $totalCogs = ($itemsSold > 0)
                    ? $prod->unit_cogs * $itemsSold
                    : $prod->gmv * ($prod->unit_cogs / $trueAvgPrice);
                
                $prod->gross_profit = $prod->net_revenue - $totalCogs;
                // Subtract Ad Spend (with 11% Tax)
                $prod->profit_after_ads = round($prod->gross_profit - ($prod->spend * 1.11), 2);
                $prod->poas = $prod->spend > 0 ? $prod->profit_after_ads / ($prod->spend * 1.11) : 0;
            } else {
                // HPP tidak tersedia: jangan menganggap seluruh pendapatan sebagai gross profit.
                $prod->gross_profit = null;
                $prod->profit_after_ads = null;
                $prod->poas = null;
            }
            
            $prod->item_category = $catByChan[(string) $prod->channel_item_id] ?? 'Uncategorized';
            
            // Product Classification Logic
            $prod->classification = 'Review';
            $prod->class_color = 'secondary';
            
            if ($prod->profit_after_ads > 0) {
                if ($prod->poas > 0.3 && $prod->spend > 10000) {
                    $prod->classification = 'Hero Product';
                    $prod->class_color = 'success';
                } elseif ($prod->poas > 0 && $prod->orders >= 3) {
                    $prod->classification = 'Profit Driver';
                    $prod->class_color = 'primary';
                } elseif ($prod->orders >= 5 && $prod->poas <= 0.1) {
                    $prod->classification = 'Volume Driver';
                    $prod->class_color = 'info';
                }
            } else {
                if ($prod->spend > 15000 && $prod->gmv > $prod->spend) {
                    $prod->classification = 'Loss Maker';
                    $prod->class_color = 'danger';
                } elseif ($prod->clicks > 100 && $prod->orders == 0) {
                    $prod->classification = 'Traffic Driver (No Conv)';
                    $prod->class_color = 'warning';
                } elseif ($prod->spend > 0 && $prod->orders == 0) {
                    $prod->classification = 'Low Performer';
                    $prod->class_color = 'secondary';
                }
            }
            
            if (($prod->stock_total ?? 0) < 5 && $prod->orders > 0 && !in_array($prod->classification, ['Low Performer', 'Traffic Driver (No Conv)'])) {
                $prod->classification = 'Stock Risk';
                $prod->class_color = 'danger';
            }
            
            return $prod;
        })->sortByDesc('spend')->values();

        // Pass an empty gmsItems so we don't break other parts if they exist
        $gmsItems = collect();

        $ltvData = $this->getCustomerLtvData($storeIds, $dateFrom, $dateTo, $kpi['current']->spend ?? 0);

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
            'autoCampaign',
            'ltvData'
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
                    $hpp = $this->resolveItemUnitCogs($mapping->item);
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

    private function getCustomerLtvData(array $storeIds, string $dateFrom, string $dateTo, float $totalAdSpend): array
    {
        // 1. Get unique buyers who purchased in the current period
        $ordersInPeriod = DB::table('marketplace_orders')
            ->whereIn('store_id', $storeIds)
            ->whereDate('order_date', '>=', $dateFrom)
            ->whereDate('order_date', '<=', $dateTo)
            ->whereNotIn('status', ['CANCELLED', 'UNPAID', 'cancelled', 'unpaid'])
            ->whereNotNull('buyer_username')
            ->select('buyer_username', 'total_amount')
            ->get();

        $uniqueBuyers = $ordersInPeriod->pluck('buyer_username')->unique()->values()->all();
        
        $newCustomersCount = 0;
        $repeatCustomersCount = 0;
        $cohorts = [];
        
        if (count($uniqueBuyers) > 0) {
            // 2. Find their globally first order date
            $firstOrders = DB::table('marketplace_orders')
                ->whereIn('store_id', $storeIds)
                ->whereIn('buyer_username', $uniqueBuyers)
                ->whereNotIn('status', ['CANCELLED', 'UNPAID', 'cancelled', 'unpaid'])
                ->groupBy('buyer_username')
                ->selectRaw('buyer_username, MIN(order_date) as first_order_date')
                ->pluck('first_order_date', 'buyer_username');
                
            foreach ($uniqueBuyers as $buyer) {
                $firstOrderDate = $firstOrders[$buyer] ?? null;
                if ($firstOrderDate && $firstOrderDate >= $dateFrom) {
                    $newCustomersCount++;
                } else {
                    $repeatCustomersCount++;
                }
            }
        }
        
        $totalCustomers = $newCustomersCount + $repeatCustomersCount;
        $rpr = $totalCustomers > 0 ? ($repeatCustomersCount / $totalCustomers) * 100 : 0;
        $blendedCac = $newCustomersCount > 0 ? $totalAdSpend / $newCustomersCount : 0;
        
        // Simple Average Order Frequency for the period
        $avgOrderFreq = $totalCustomers > 0 ? count($ordersInPeriod) / $totalCustomers : 0;

        return [
            'new_customers' => $newCustomersCount,
            'repeat_customers' => $repeatCustomersCount,
            'total_customers' => $totalCustomers,
            'repeat_purchase_rate' => $rpr,
            'blended_cac' => $blendedCac,
            'avg_order_freq' => $avgOrderFreq,
        ];
    }

    private function deriveBreakEvenAcos(?Item $item, float $gmv, int $units, ?float $netRevRatio = null): ?float
    {
        if (! $item || $units <= 0 || $gmv <= 0) {
            return null;
        }

        $hpp = $this->resolveItemUnitCogs($item);
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

    private function resolveItemUnitCogs(?Item $item): float
    {
        if (! $item) {
            return 0.0;
        }

        $hpp = (float) ($item->hpp ?? 0);
        return $hpp > 0 ? $hpp : (float) ($item->base_unit_cost ?? 0);
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
