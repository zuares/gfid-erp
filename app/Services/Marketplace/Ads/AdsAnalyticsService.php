<?php

namespace App\Services\Marketplace\Ads;

use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdsSetting;
use App\Models\MarketplaceAdsHourlyPerformance;
use App\Models\MarketplaceOrderSettlement;
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
            ->select('performance_hour', DB::raw('SUM(impression) as impressions'), DB::raw('SUM(expense) as expense'), DB::raw('SUM(broad_gmv) as gmv'), DB::raw('SUM(clicks) as clicks'), DB::raw('SUM(broad_order) as orders'))
            ->groupBy('performance_hour')
            ->orderBy('performance_hour')
            ->get()
            ->map(fn ($row) => [
                'performance_hour' => (int) $row->performance_hour,
                'impressions'      => (int) $row->impressions,
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
        $storeIds = array_values(array_filter((array) $storeId, fn ($id) => is_numeric($id)));
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
            
            // Analisa Profit harus memakai omzet bersih per periode, bukan
            // rasio admin periode aktif yang ditempelkan ke semua periode.
            // Ambil rasio per toko/tanggal agar mode manual dan settlement
            // historis tetap konsisten, termasuk saat memilih Semua Toko.
            $adminRatios = $this->getAdminFeeRatios($storeIds, $start, $end);
            $storeDaily = collect($storeIds)->mapWithKeys(function ($id) use ($start, $end) {
                return [(string) $id => collect($this->aggregateDailyRows((int) $id, $start, $end))->keyBy(fn ($row) => (string) $row->date)];
            });

            $daily = collect($this->aggregateDailyRows($storeIds, $start, $end))
                ->sortKeys()
                ->map(function ($row) use ($storeIds, $storeDaily, $adminRatios) {
                    $date = substr((string) $row->date, 0, 10);
                    $netRevenue = 0.0;
                    $adminFee = 0.0;
                    $hasStoreBreakdown = false;

                    foreach ($storeIds as $id) {
                        $storeRow = $storeDaily->get((string) $id)?->get($date);
                        if (! $storeRow) {
                            continue;
                        }

                        $storeGmv = (float) ($storeRow->gmv ?? 0);
                        if ($storeGmv <= 0) {
                            continue;
                        }

                        $ratio = (float) ($adminRatios[(string) $id . '|' . $date] ?? AdsDashboardService::DEFAULT_NET_REVENUE_RATIO);
                        $netRevenue += $storeGmv * $ratio;
                        $adminFee += $storeGmv * max(0.0, 1 - $ratio);
                        $hasStoreBreakdown = true;
                    }

                    if (! $hasStoreBreakdown) {
                        $gmv = (float) ($row->gmv ?? 0);
                        $netRevenue = $gmv * AdsDashboardService::DEFAULT_NET_REVENUE_RATIO;
                        $adminFee = $gmv - $netRevenue;
                    }

                    return [
                        'date'        => $date,
                        'spend'       => (float) $row->spend,
                        'gmv'         => (float) $row->gmv,
                        'net_revenue' => round($netRevenue, 2),
                        'admin_fee'   => round($adminFee, 2),
                        'impressions' => (int) $row->impressions,
                        'clicks'      => (int) $row->clicks,
                        'orders'      => (int) $row->orders,
                    ];
                })
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

    /**
     * Return net-revenue ratios keyed by store and calendar date.
     * Manual admin fee is authoritative; auto mode uses settlement data and
     * falls back to the dashboard default when no settlement is available.
     *
     * @return array<string, float>
     */
    private function getAdminFeeRatios(array $storeIds, string $dateFrom, string $dateTo): array
    {
        if ($storeIds === []) {
            return [];
        }

        $ratios = [];
        $manualRatios = MarketplaceAdsSetting::query()
            ->whereIn('store_id', $storeIds)
            ->where('admin_fee_mode', 'manual')
            ->whereNotNull('admin_fee_pct')
            ->get(['store_id', 'admin_fee_pct'])
            ->mapWithKeys(fn ($setting) => [
                (string) $setting->store_id => max(0.0, 1 - ((float) $setting->admin_fee_pct / 100)),
            ])
            ->all();

        foreach ($manualRatios as $storeKey => $ratio) {
            $ratios[$storeKey . '|*'] = (float) $ratio;
        }

        $dateExpression = 'DATE(COALESCE(mo.ordered_at, mo.order_date, mos.settlement_time, mos.created_at))';
        $settlementRows = MarketplaceOrderSettlement::query()
            ->from('marketplace_order_settlements as mos')
            ->leftJoin('marketplace_orders as mo', 'mo.id', '=', 'mos.order_id')
            ->whereIn('mos.store_id', $storeIds)
            ->whereBetween(DB::raw($dateExpression), [$dateFrom, $dateTo])
            ->selectRaw("mos.store_id, {$dateExpression} as settlement_date, SUM(mos.final_income) as final_income, SUM(mos.buyer_payment_amount) as buyer_payment")
            ->groupBy('mos.store_id', DB::raw($dateExpression))
            ->get();

        foreach ($settlementRows as $row) {
            $storeKey = (string) $row->store_id;
            if (array_key_exists($storeKey . '|*', $ratios)) {
                continue;
            }

            $buyerPayment = (float) ($row->buyer_payment ?? 0);
            $finalIncome = (float) ($row->final_income ?? 0);
            $ratio = $buyerPayment > 0
                ? min(1.0, max(0.0, $finalIncome / $buyerPayment))
                : AdsDashboardService::DEFAULT_NET_REVENUE_RATIO;
            $ratios[$storeKey . '|' . substr((string) $row->settlement_date, 0, 10)] = round($ratio, 4);
        }

        foreach ($storeIds as $storeId) {
            $storeKey = (string) $storeId;
            $fallback = (float) ($ratios[$storeKey . '|*'] ?? AdsDashboardService::DEFAULT_NET_REVENUE_RATIO);
            $start = Carbon::parse($dateFrom);
            $end = Carbon::parse($dateTo);
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $key = $storeKey . '|' . $date->toDateString();
                $ratios[$key] ??= $fallback;
            }
        }

        return $ratios;
    }
}
