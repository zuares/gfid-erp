<?php

namespace App\Services\Marketplace\Ads;

use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdsHourlyPerformance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdsAnalyticsService
{
    public function getKpiSummary(int|array $storeId, string $dateFrom, string $dateTo)
    {
        $current = $this->aggregateDaily($storeId, $dateFrom, $dateTo);
        
        // Calculate previous period
        $days = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1;
        $prevDateTo = Carbon::parse($dateFrom)->subDay()->toDateString();
        $prevDateFrom = Carbon::parse($prevDateTo)->subDays($days - 1)->toDateString();
        
        $previous = $this->aggregateDaily($storeId, $prevDateFrom, $prevDateTo);

        return [
            'current' => $current,
            'previous' => $previous,
            'changes' => $this->calculateChanges($current, $previous)
        ];
    }

    protected function aggregateDaily(int|array $storeId, string $dateFrom, string $dateTo)
    {
        return collect($this->aggregateDailyRows($storeId, $dateFrom, $dateTo))
            ->reduce(function ($carry, $row) {
                $carry->spend += $row->spend;
                $carry->gmv += $row->gmv;
                $carry->impressions += $row->impressions;
                $carry->clicks += $row->clicks;
                $carry->orders += $row->orders;
                return $carry;
            }, (object) ['spend' => 0.0, 'gmv' => 0.0, 'impressions' => 0, 'clicks' => 0, 'orders' => 0]);
    }

    /**
     * Ringkasan level toko. Campaign regular + GMS hanya menjadi fallback
     * ketika data level toko belum tersinkron untuk tanggal tersebut.
     */
    protected function aggregateDailyRows(int|array $storeId, string $dateFrom, string $dateTo): array
    {
        $stores = (array) $storeId;
        $group = function (string $table, string $spendColumn, string $gmvColumn, string $ordersColumn, ?string $campaignFilter = null) use ($stores, $dateFrom, $dateTo) {
            $query = DB::table($table)
                ->whereIn('store_id', $stores)
                ->whereBetween('date', [$dateFrom, $dateTo]);

            if ($campaignFilter === 'gms') {
                $query->where('channel_campaign_id', 'like', 'GMS-%');
            } elseif ($campaignFilter === 'campaign') {
                $query->where('channel_campaign_id', 'not like', 'GMS-%');
            }

            return $query
                ->selectRaw("date, SUM({$spendColumn}) spend, SUM({$gmvColumn}) gmv, SUM(impressions) impressions, SUM(clicks) clicks, SUM({$ordersColumn}) orders")
                ->groupBy('date')
                ->get()
                ->keyBy(fn ($row) => substr((string) $row->date, 0, 10));
        };

        $shop = $group('marketplace_ads_dailies', 'spend', 'gmv', 'orders');
        $campaign = $group('marketplace_ad_campaign_dailies', 'expense', 'broad_gmv', 'broad_order', 'campaign');
        $gms = $group('marketplace_ad_campaign_dailies', 'expense', 'broad_gmv', 'broad_order', 'gms');
        $dates = $shop->keys()->merge($campaign->keys())->merge($gms->keys())->unique();

        return $dates->mapWithKeys(function (string $date) use ($shop, $campaign, $gms) {
            $shopRow = $shop->get($date);
            if ($shopRow) {
                return [$date => (object) [
                    'date' => $date,
                    'spend' => (float) ($shopRow->spend ?? 0),
                    'gmv' => (float) ($shopRow->gmv ?? 0),
                    'impressions' => (int) ($shopRow->impressions ?? 0),
                    'clicks' => (int) ($shopRow->clicks ?? 0),
                    'orders' => (int) ($shopRow->orders ?? 0),
                ]];
            }

            $regular = $campaign->get($date);
            $gmsRow = $gms->get($date);
            return [$date => (object) [
                'date' => $date,
                'spend' => (float) ($regular->spend ?? 0) + (float) ($gmsRow->spend ?? 0),
                'gmv' => (float) ($regular->gmv ?? 0) + (float) ($gmsRow->gmv ?? 0),
                'impressions' => (int) ($regular->impressions ?? 0) + (int) ($gmsRow->impressions ?? 0),
                'clicks' => (int) ($regular->clicks ?? 0) + (int) ($gmsRow->clicks ?? 0),
                'orders' => (int) ($regular->orders ?? 0) + (int) ($gmsRow->orders ?? 0),
            ]];
        })->all();
    }

    protected function calculateChanges($current, $previous)
    {
        $changes = [];
        $metrics = ['spend', 'gmv', 'impressions', 'clicks', 'orders'];
        foreach ($metrics as $m) {
            $c = (float) ($current->$m ?? 0);
            $p = (float) ($previous->$m ?? 0);
            if ($p == 0) {
                $changes[$m] = $c > 0 ? null : 0;
            } else {
                $changes[$m] = round((($c - $p) / $p) * 100, 2);
            }
        }
        
        // Calculate derived metrics
        $c_roas = $current->spend > 0 ? round($current->gmv / $current->spend, 2) : 0;
        $p_roas = $previous->spend > 0 ? round($previous->gmv / $previous->spend, 2) : 0;
        $changes['roas'] = $p_roas > 0 ? round((($c_roas - $p_roas) / $p_roas) * 100, 2) : ($c_roas > 0 ? null : 0);

        return $changes;
    }

    public function getHourlyHeatmap(int|array $storeId, string $dateFrom, string $dateTo)
    {
        return MarketplaceAdsHourlyPerformance::whereIn('store_id', (array) $storeId)
            ->whereNull('campaign_id')
            ->whereBetween('performance_date', [$dateFrom, $dateTo])
            ->select('performance_hour', DB::raw('SUM(expense) as expense'), DB::raw('SUM(broad_gmv) as gmv'), DB::raw('SUM(clicks) as clicks'), DB::raw('SUM(broad_order) as orders'))
            ->groupBy('performance_hour')
            ->orderBy('performance_hour')
            ->get()
            ->map(fn ($row) => [
                'performance_hour' => (int) $row->performance_hour,
                'expense'          => (float) $row->expense,
                'gmv'              => (float) $row->gmv,
                'clicks'           => (int) $row->clicks,
                'orders'           => (int) $row->orders,
            ])
            ->values();
    }

    public function getHistoricalComparison(
        int|array $storeId,
        string $dateFrom,
        string $dateTo,
        int $periods = 3,
        string $compareMode = 'prev_period'
    )
    {
        $days = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1;
        $results = [];
        
        for ($i = 0; $i < $periods; $i++) {
            $startBase = Carbon::parse($dateFrom);
            $endBase = Carbon::parse($dateTo);
            if ($compareMode === 'prev_month') {
                $start = $startBase->subMonthsNoOverflow($i)->toDateString();
                $end = $endBase->subMonthsNoOverflow($i)->toDateString();
            } elseif ($compareMode === 'prev_year') {
                $start = $startBase->subYears($i)->toDateString();
                $end = $endBase->subYears($i)->toDateString();
            } else {
                $start = $startBase->subDays($days * $i)->toDateString();
                $end = $endBase->subDays($days * $i)->toDateString();
            }
            
            $daily = collect($this->aggregateDailyRows($storeId, $start, $end))
                ->sortKeys()
                ->map(fn ($row) => [
                    'date'        => substr((string) $row->date, 0, 10),
                    'spend'       => (float) $row->spend,
                    'gmv'         => (float) $row->gmv,
                    'impressions' => (int) $row->impressions,
                    'clicks'      => (int) $row->clicks,
                    'orders'      => (int) $row->orders,
                ])
                ->values();
                
            $results[] = [
                'period_index' => $i,
                'start' => $start,
                'end' => $end,
                'data' => $daily
            ];
        }
        
        return $results;
    }
}
