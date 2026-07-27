<?php

namespace App\Services\Marketplace\Ads;

use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdsDaily;
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
        return MarketplaceAdsDaily::whereIn('store_id', (array) $storeId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->select(
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(gmv) as gmv'),
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(orders) as orders')
            )->first();
    }

    protected function calculateChanges($current, $previous)
    {
        $changes = [];
        $metrics = ['spend', 'gmv', 'impressions', 'clicks', 'orders'];
        foreach ($metrics as $m) {
            $c = (float) ($current->$m ?? 0);
            $p = (float) ($previous->$m ?? 0);
            if ($p == 0) {
                $changes[$m] = $c > 0 ? 100 : 0;
            } else {
                $changes[$m] = round((($c - $p) / $p) * 100, 2);
            }
        }
        
        // Calculate derived metrics
        $c_roas = $current->spend > 0 ? round($current->gmv / $current->spend, 2) : 0;
        $p_roas = $previous->spend > 0 ? round($previous->gmv / $previous->spend, 2) : 0;
        $changes['roas'] = $p_roas > 0 ? round((($c_roas - $p_roas) / $p_roas) * 100, 2) : ($c_roas > 0 ? 100 : 0);

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
            ->get();
    }

    public function getHistoricalComparison(int|array $storeId, string $dateFrom, string $dateTo, int $periods = 3)
    {
        $days = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1;
        $results = [];
        
        for ($i = 0; $i < $periods; $i++) {
            $start = Carbon::parse($dateFrom)->subDays($days * $i)->toDateString();
            $end = Carbon::parse($dateTo)->subDays($days * $i)->toDateString();
            
            $daily = MarketplaceAdsDaily::whereIn('store_id', (array) $storeId)
                ->whereBetween('date', [$start, $end])
                ->orderBy('date')
                ->get();
                
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
