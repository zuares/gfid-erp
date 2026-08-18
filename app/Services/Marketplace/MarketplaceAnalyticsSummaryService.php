<?php

namespace App\Services\Marketplace;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Small, server-side summary for the Marketplace Analytics page.
 *
 * The page should not download every order and recalculate financials in the
 * browser. This service uses the same verified settlement/HPP basis as the
 * owner profit report, but returns only aggregate rows.
 */
class MarketplaceAnalyticsSummaryService
{
    private const ESTIMATED_MARKETPLACE_FEE_RATE = 0.21;

    private const FEE_FIELDS = [
        'commission_fee',
        'service_fee',
        'transaction_fee',
        'affiliate_fee',
        'activity_fee',
        'shipping_insurance_fee',
        'escrow_tax',
    ];

    public function __construct(private MarketplaceProfitReportService $profitReport)
    {
    }

    public function summary(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);
        $previous = $this->previousRange($filters);

        $currentFinancial = $this->financialAggregate($filters);
        $previousFinancial = $this->financialAggregate(array_merge($filters, $previous));
        $currentCash = $this->cashAggregate($filters);
        $previousCash = $this->cashAggregate(array_merge($filters, $previous));
        $currentOperational = $this->operationalAggregate($filters);
        $previousOperational = $this->operationalAggregate(array_merge($filters, $previous));
        $currentAds = $this->adsAggregate($filters);
        $previousAds = $this->adsAggregate(array_merge($filters, $previous));
        $quality = $this->qualitySummary($filters);
        $current = $this->withRates($this->applyAdCost(array_merge($currentFinancial, $currentCash, $currentOperational), $currentAds));
        $previousSnapshot = $this->withRates($this->applyAdCost(array_merge($previousFinancial, $previousCash, $previousOperational), $previousAds));

        return [
            'filters' => $filters,
            'current' => $current,
            'previous' => $previousSnapshot,
            'changes' => $this->changes($current, $previousSnapshot),
            'daily' => $this->mergeDaily($this->financialDaily($filters), $this->operationalDaily($filters), $this->adsDaily($filters)),
            'previous_daily' => $this->mergeDaily($this->financialDaily(array_merge($filters, $previous)), $this->operationalDaily(array_merge($filters, $previous)), $this->adsDaily(array_merge($filters, $previous))),
            'stores' => $this->storeSummary($filters),
            'quality' => $quality,
        ];
    }

    public function products(array $filters, int $limit = 100): array
    {
        $filters = $this->normalizeFilters($filters);
        $limit = max(1, min(200, $limit));
        $items = $this->profitReport->report([
            'store_id' => $filters['store_id'],
            'date_basis' => 'ordered_at',
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
        ])['items'];
        $adsCost = (float) ($this->adsAggregate($filters)['ad_cost'] ?? 0);
        $totalGrossSales = (float) collect($items)->sum(fn (array $row) => (float) ($row['gross_sales'] ?? 0));

        return collect($items)
            ->sortByDesc('gross_sales')
            ->take($limit)
            ->map(function (array $row) use ($adsCost, $totalGrossSales): array {
                $grossSales = (float) ($row['gross_sales'] ?? 0);
                $payout = (float) ($row['payout'] ?? 0);
                $hpp = (float) ($row['hpp'] ?? 0);
                $allocatedAdCost = $totalGrossSales > 0 ? $adsCost * ($grossSales / $totalGrossSales) : 0.0;
                $grossProfit = $payout - $hpp;
                $operatingProfit = $grossProfit - $allocatedAdCost;

                return [
                    'product_key' => (string) ($row['item_key'] ?? ''),
                    'product_name' => (string) ($row['item_name'] ?? '-'),
                    'sku' => (string) ($row['sku'] ?? '-'),
                    'qty' => (int) ($row['qty'] ?? 0),
                    'gross_sales' => round($grossSales, 2),
                    'payout' => round($payout, 2),
                    'hpp' => round($hpp, 2),
                    'ad_cost' => round($allocatedAdCost, 2),
                    'ad_cost_settlement' => round((float) ($row['ad_cost'] ?? 0), 2),
                    'gross_profit' => round($grossProfit, 2),
                    'operating_profit' => round($operatingProfit, 2),
                    'margin_pct' => $grossSales > 0 ? round(($operatingProfit / $grossSales) * 100, 2) : 0.0,
                ];
            })->values()->all();
    }

    private function normalizeFilters(array $filters): array
    {
        $from = $filters['date_from'] ?? now()->subDays(29)->toDateString();
        $to = $filters['date_to'] ?? now()->toDateString();

        try {
            $from = Carbon::parse($from)->toDateString();
            $to = Carbon::parse($to)->toDateString();
        } catch (\Throwable) {
            $from = now()->subDays(29)->toDateString();
            $to = now()->toDateString();
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'store_id' => ! empty($filters['store_id']) ? (int) $filters['store_id'] : null,
            'date_from' => $from,
            'date_to' => $to,
            'compare_mode' => in_array($filters['compare_mode'] ?? 'prev_period', ['prev_period', 'prev_month', 'prev_year'], true)
                ? ($filters['compare_mode'] ?? 'prev_period')
                : 'prev_period',
        ];
    }

    private function previousRange(array $filters): array
    {
        $from = Carbon::parse($filters['date_from']);
        $to = Carbon::parse($filters['date_to']);

        if ($filters['compare_mode'] === 'prev_month') {
            return [
                'date_from' => $from->copy()->subMonthsNoOverflow()->toDateString(),
                'date_to' => $to->copy()->subMonthsNoOverflow()->toDateString(),
            ];
        }

        if ($filters['compare_mode'] === 'prev_year') {
            return [
                'date_from' => $from->copy()->subYear()->toDateString(),
                'date_to' => $to->copy()->subYear()->toDateString(),
            ];
        }

        $days = $from->diffInDays($to) + 1;
        $previousTo = $from->copy()->subDay();

        return [
            'date_from' => $previousTo->copy()->subDays($days - 1)->toDateString(),
            'date_to' => $previousTo->toDateString(),
        ];
    }

    private function financialBase(array $filters)
    {
        $itemCosts = DB::table('marketplace_order_items as oi')
            ->select('oi.marketplace_order_id')
            ->selectRaw('SUM(oi.hpp_snapshot * CASE WHEN oi.qty > 0 THEN oi.qty ELSE 0 END) AS hpp')
            ->selectRaw('SUM(CASE WHEN oi.qty > 0 THEN oi.qty ELSE 0 END) AS qty')
            ->where('oi.data_status', 'valid')
            ->where('oi.hpp_snapshot', '>', 0)
            ->whereExists(function ($query) use ($filters) {
                $query->selectRaw('1')
                    ->from('marketplace_orders as fmo')
                    ->whereColumn('fmo.id', 'oi.marketplace_order_id')
                    ->where('fmo.financial_data_status', MarketplaceFinancialDataQualityService::ORDER_READY)
                    ->whereNotNull('fmo.ordered_at')
                    ->whereBetween('fmo.ordered_at', [
                        $filters['date_from'] . ' 00:00:00',
                        $filters['date_to'] . ' 23:59:59',
                    ])
                    ->when($filters['store_id'], fn ($q, $storeId) => $q->where('fmo.store_id', $storeId));
            })
            ->groupBy('oi.marketplace_order_id');

        $query = DB::table('marketplace_orders as mo')
            ->join('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
            ->joinSub($itemCosts, 'ic', fn ($join) => $join->on('ic.marketplace_order_id', '=', 'mo.id'))
            ->join('stores as st', 'st.id', '=', 'mo.store_id')
            ->where('mo.financial_data_status', MarketplaceFinancialDataQualityService::ORDER_READY)
            ->where('ms.data_status', MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE)
            ->whereNotNull('mo.ordered_at');

        return $this->applyDateAndStoreFilters($query, $filters, 'mo');
    }

    private function financialAggregate(array $filters): array
    {
        $row = $this->financialBase($filters)
            ->selectRaw($this->aggregateSelect())
            ->first();

        return $this->normalizeAggregate($row);
    }

    private function financialDaily(array $filters): array
    {
        return $this->financialBase($filters)
            ->selectRaw('DATE(mo.ordered_at) AS date')
            ->selectRaw($this->aggregateSelect(false))
            ->groupByRaw('DATE(mo.ordered_at)')
            ->orderByRaw('DATE(mo.ordered_at)')
            ->get()
            ->map(fn ($row) => array_merge(['date' => $row->date], $this->normalizeAggregate($row)))
            ->values()
            ->all();
    }

    private function cashAggregate(array $filters): array
    {
        $fees = collect(self::FEE_FIELDS)
            ->map(fn (string $field) => "COALESCE(ms.{$field}, 0)")
            ->implode(' + ');

        $row = $this->cashBase($filters)
            ->selectRaw('COUNT(DISTINCT mo.id) AS cash_order_count')
            ->selectRaw('SUM(COALESCE(ms.buyer_payment_amount, mo.total_amount, mo.total_paid_customer, mo.subtotal_items, 0)) AS cash_gross_sales')
            ->selectRaw('SUM(COALESCE(ms.final_income, 0)) AS cash_payout')
            ->selectRaw("SUM({$fees}) AS cash_marketplace_fees")
            ->selectRaw('SUM(COALESCE(ms.drc_adjustable_refund, 0)) AS cash_refund')
            ->first();

        return [
            'cash_order_count' => (int) ($row->cash_order_count ?? 0),
            'cash_gross_sales' => round((float) ($row->cash_gross_sales ?? 0), 2),
            'cash_payout' => round((float) ($row->cash_payout ?? 0), 2),
            'cash_marketplace_fees' => round((float) ($row->cash_marketplace_fees ?? 0), 2),
            'cash_refund' => round((float) ($row->cash_refund ?? 0), 2),
        ];
    }

    private function cashBase(array $filters)
    {
        return $this->applyDateAndStoreFilters(
            DB::table('marketplace_orders as mo')
                ->join('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
                ->where('ms.data_status', MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE)
                ->whereRaw($this->isRevenueStatus()),
            $filters,
            'mo'
        );
    }

    private function storeSummary(array $filters): array
    {
        $financial = $this->financialBase($filters)
            ->selectRaw('mo.store_id, st.name as store_name')
            ->selectRaw($this->aggregateSelect(false))
            ->groupBy('mo.store_id', 'st.name')
            ->get()
            ->keyBy(fn ($row) => (string) $row->store_id);

        $operational = $this->operationalStoreSummary($filters)->keyBy(fn ($row) => (string) $row->store_id);
        $ads = $this->adsStoreSummary($filters)->keyBy(fn ($row) => (string) $row->store_id);
        $ids = $financial->keys()->merge($operational->keys())->merge($ads->keys())->unique();

        return $ids->map(function (string $id) use ($financial, $operational, $ads) {
            $finance = $financial->get($id);
            $ops = $operational->get($id);
            $ad = $ads->get($id);
            $aggregate = $this->normalizeAggregate($finance);

            return $this->withRates($this->applyAdCost(array_merge([
                'store_id' => (int) $id,
                'store_name' => $finance?->store_name ?? $ops?->store_name ?? $ad?->store_name ?? 'Tanpa toko',
                'order_total' => (int) ($ops->order_total ?? 0),
                'completed_count' => (int) ($ops->completed_count ?? 0),
                'cancelled_count' => (int) ($ops->cancelled_count ?? 0),
                'gmv' => round((float) ($ops->gmv ?? 0), 2),
            ], $aggregate), ['ad_cost' => (float) ($ad->ad_cost ?? 0)]));
        })->sortByDesc('gross_sales')->values()->all();
    }

    private function operationalAggregate(array $filters): array
    {
        $row = $this->operationalBase($filters)
            ->selectRaw($this->operationalSelect())
            ->first();

        return [
            'order_total' => (int) ($row->order_total ?? 0),
            'completed_count' => (int) ($row->completed_count ?? 0),
            'cancelled_count' => (int) ($row->cancelled_count ?? 0),
            'gmv' => round((float) ($row->gmv ?? 0), 2),
        ];
    }

    private function operationalDaily(array $filters): array
    {
        return $this->operationalBase($filters)
            ->selectRaw('DATE(mo.ordered_at) AS date')
            ->selectRaw('SUM(CASE WHEN ' . $this->isRevenueStatus() . ' THEN ' . $this->orderValueExpression() . ' ELSE 0 END) AS gmv')
            ->groupByRaw('DATE(mo.ordered_at)')
            ->orderByRaw('DATE(mo.ordered_at)')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'gmv' => round((float) ($row->gmv ?? 0), 2)])
            ->values()
            ->all();
    }

    private function mergeDaily(array $financial, array $operational, array $ads = []): array
    {
        $rows = collect($financial)->keyBy('date');
        foreach ($operational as $row) {
            $date = (string) $row['date'];
            $rows[$date] = array_merge($rows[$date] ?? [
                'date' => $date,
                'order_count' => 0,
                'qty' => 0,
                'gross_sales' => 0,
                'marketplace_fees' => 0,
                'refund' => 0,
                'payout' => 0,
                'hpp' => 0,
                'ad_cost' => 0,
                'ad_cost_settlement' => 0,
                'gross_profit' => 0,
                'operating_profit' => 0,
                'margin_pct' => 0,
                'aov' => 0,
            ], ['gmv' => (float) ($row['gmv'] ?? 0)]);
        }

        if ($ads !== []) {
            foreach ($ads as $row) {
                $date = (string) $row['date'];
                $rows[$date] = array_merge($rows[$date] ?? [
                    'date' => $date,
                    'order_count' => 0,
                    'qty' => 0,
                    'gross_sales' => 0,
                    'marketplace_fees' => 0,
                    'refund' => 0,
                    'payout' => 0,
                    'hpp' => 0,
                    'ad_cost' => 0,
                    'ad_cost_settlement' => 0,
                    'gross_profit' => 0,
                    'operating_profit' => 0,
                    'margin_pct' => 0,
                    'aov' => 0,
                ], ['ad_cost' => (float) ($row['ad_cost'] ?? 0)]);
            }

            $rows = $rows->map(function (array $row) use ($ads) {
                $adCostByDate = collect($ads)->firstWhere('date', $row['date']);
                $row['ad_cost_settlement'] = round((float) ($row['ad_cost_settlement'] ?? $row['ad_cost'] ?? 0), 2);
                $row['ad_cost'] = round((float) ($adCostByDate['ad_cost'] ?? 0), 2);
                $row['gross_profit'] = round((float) ($row['payout'] ?? 0) - (float) ($row['hpp'] ?? 0), 2);
                $row['operating_profit'] = round($row['gross_profit'] - $row['ad_cost'], 2);
                $row['margin_pct'] = (float) ($row['gross_sales'] ?? 0) > 0
                    ? round(($row['operating_profit'] / $row['gross_sales']) * 100, 2)
                    : 0.0;

                return $row;
            });
        }

        return $rows->sortKeys()->values()->all();
    }

    private function operationalStoreSummary(array $filters)
    {
        return $this->operationalBase($filters)
            ->join('stores as st', 'st.id', '=', 'mo.store_id')
            ->selectRaw('mo.store_id, st.name as store_name')
            ->selectRaw($this->operationalSelect(false))
            ->groupBy('mo.store_id', 'st.name')
            ->get();
    }

    private function adsAggregate(array $filters): array
    {
        $row = $this->adsBase($filters)
            ->selectRaw('COALESCE(SUM(spend), 0) AS ad_cost')
            ->first();

        return ['ad_cost' => round((float) ($row->ad_cost ?? 0), 2)];
    }

    private function adsDaily(array $filters): array
    {
        return $this->adsBase($filters)
            ->selectRaw('date, COALESCE(SUM(spend), 0) AS ad_cost')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->date,
                'ad_cost' => round((float) ($row->ad_cost ?? 0), 2),
            ])
            ->values()
            ->all();
    }

    private function adsStoreSummary(array $filters)
    {
        return $this->adsBase($filters)
            ->join('stores as st', 'st.id', '=', 'marketplace_ads_dailies.store_id')
            ->selectRaw('marketplace_ads_dailies.store_id, st.name as store_name, COALESCE(SUM(marketplace_ads_dailies.spend), 0) AS ad_cost')
            ->groupBy('marketplace_ads_dailies.store_id', 'st.name')
            ->get();
    }

    private function adsBase(array $filters)
    {
        return DB::table('marketplace_ads_dailies')
            ->whereBetween('date', [$filters['date_from'], $filters['date_to']])
            ->when($filters['store_id'], fn ($q, $storeId) => $q->where('store_id', $storeId));
    }

    private function operationalBase(array $filters)
    {
        return $this->applyDateAndStoreFilters(DB::table('marketplace_orders as mo'), $filters, 'mo');
    }

    private function qualitySummary(array $filters): array
    {
        $rows = $this->applyDateAndStoreFilters(DB::table('marketplace_orders as mo'), $filters, 'mo')
            ->selectRaw("COALESCE(mo.financial_data_status, 'unknown') AS status, COUNT(*) AS total")
            ->groupBy('mo.financial_data_status')
            ->get()
            ->keyBy('status');

        return [
            'total' => (int) $rows->sum('total'),
            'ready' => (int) ($rows->get('ready')?->total ?? 0),
            'incomplete' => (int) ($rows->get('incomplete')?->total ?? 0),
            'unknown' => (int) ($rows->get('unknown')?->total ?? 0),
            'not_applicable' => (int) ($rows->get('not_applicable')?->total ?? 0),
        ];
    }

    private function applyDateAndStoreFilters($query, array $filters, string $alias)
    {
        return $query
            ->whereBetween("{$alias}.ordered_at", [
                $filters['date_from'] . ' 00:00:00',
                $filters['date_to'] . ' 23:59:59',
            ])
            ->when($filters['store_id'], fn ($q, $storeId) => $q->where("{$alias}.store_id", $storeId));
    }

    private function aggregateSelect(bool $includeStore = true): string
    {
        $gross = 'COALESCE(ms.buyer_payment_amount, mo.total_amount, mo.total_paid_customer, mo.subtotal_items, 0)';
        $fees = collect(self::FEE_FIELDS)
            ->map(fn (string $field) => "COALESCE(ms.{$field}, 0)")
            ->implode(' + ');

        return implode(', ', [
            'COUNT(DISTINCT mo.id) AS order_count',
            'SUM(COALESCE(ic.qty, 0)) AS qty',
            "SUM({$gross}) AS gross_sales",
            "SUM({$fees}) AS marketplace_fees",
            'SUM(COALESCE(ms.drc_adjustable_refund, 0)) AS refund',
            'SUM(COALESCE(ms.final_income, 0)) AS payout',
            'SUM(COALESCE(ic.hpp, 0)) AS hpp',
            'SUM(COALESCE(ms.ad_cost, 0)) AS ad_cost',
        ]);
    }

    private function operationalSelect(bool $includeStore = true): string
    {
        $status = "UPPER(COALESCE(NULLIF(mo.order_status, ''), mo.status, ''))";

        return implode(', ', [
            'SUM(CASE WHEN ' . $this->isRevenueStatus() . ' THEN 1 ELSE 0 END) AS order_total',
            "SUM(CASE WHEN {$status} = 'COMPLETED' THEN 1 ELSE 0 END) AS completed_count",
            "SUM(CASE WHEN {$status} IN ('CANCELLED', 'CANCELED', 'BATAL') THEN 1 ELSE 0 END) AS cancelled_count",
            'SUM(CASE WHEN ' . $this->isRevenueStatus() . ' THEN ' . $this->orderValueExpression() . ' ELSE 0 END) AS gmv',
        ]);
    }

    private function orderValueExpression(): string
    {
        return 'COALESCE(NULLIF(mo.total_amount, 0), NULLIF(mo.total_paid_customer, 0), mo.subtotal_items, 0)';
    }

    private function isRevenueStatus(): string
    {
        $status = "UPPER(COALESCE(NULLIF(mo.order_status, ''), mo.status, ''))";
        return "{$status} NOT IN ('CANCELLED', 'CANCELED', 'BATAL')";
    }

    private function normalizeAggregate($row): array
    {
        $gross = (float) ($row->gross_sales ?? 0);
        $payout = (float) ($row->payout ?? 0);
        $hpp = (float) ($row->hpp ?? 0);
        $adCost = (float) ($row->ad_cost ?? 0);
        $operating = $payout - $hpp - $adCost;

        return [
            'order_count' => (int) ($row->order_count ?? 0),
            'qty' => (int) ($row->qty ?? 0),
            'gross_sales' => round($gross, 2),
            'marketplace_fees' => round((float) ($row->marketplace_fees ?? 0), 2),
            'refund' => round((float) ($row->refund ?? 0), 2),
            'payout' => round($payout, 2),
            'hpp' => round($hpp, 2),
            'ad_cost' => round($adCost, 2),
            'ad_cost_settlement' => round($adCost, 2),
            'gross_profit' => round($payout - $hpp, 2),
            'operating_profit' => round($operating, 2),
            'margin_pct' => $gross > 0 ? round(($operating / $gross) * 100, 2) : 0.0,
            'aov' => ($row->order_count ?? 0) > 0 ? round($gross / (int) $row->order_count, 2) : 0.0,
        ];
    }

    private function changes(array $current, array $previous): array
    {
        return collect(['gmv', 'gross_sales', 'payout', 'estimated_profit', 'operating_profit', 'order_count', 'ad_cost', 'aov', 'completion_rate', 'cancel_rate', 'profit_margin'])
            ->mapWithKeys(function (string $key) use ($current, $previous) {
                $now = (float) ($current[$key] ?? 0);
                $before = (float) ($previous[$key] ?? 0);
                return [$key => $before == 0 ? ($now > 0 ? null : 0) : round((($now - $before) / $before) * 100, 2)];
            })->all();
    }

    private function withRates(array $aggregate): array
    {
        $orders = (int) ($aggregate['order_total'] ?? 0);
        $gross = (float) ($aggregate['gross_sales'] ?? 0);

        return array_merge($this->withEstimatedFee($aggregate), [
            'completion_rate' => $orders > 0 ? round(((int) ($aggregate['completed_count'] ?? 0) / $orders) * 100, 2) : 0.0,
            'cancel_rate' => $orders > 0 ? round(((int) ($aggregate['cancelled_count'] ?? 0) / $orders) * 100, 2) : 0.0,
            'profit_margin' => $gross > 0 ? round(((float) ($aggregate['operating_profit'] ?? 0) / $gross) * 100, 2) : 0.0,
        ]);
    }

    private function applyAdCost(array $aggregate, array $ads): array
    {
        $aggregate['ad_cost_settlement'] = round((float) ($aggregate['ad_cost_settlement'] ?? $aggregate['ad_cost'] ?? 0), 2);
        $aggregate['ad_cost'] = round((float) ($ads['ad_cost'] ?? 0), 2);
        $payout = (float) ($aggregate['payout'] ?? 0);
        $hpp = (float) ($aggregate['hpp'] ?? 0);
        $aggregate['gross_profit'] = round($payout - $hpp, 2);
        $aggregate['operating_profit'] = round($aggregate['gross_profit'] - $aggregate['ad_cost'], 2);

        $gross = (float) ($aggregate['gross_sales'] ?? 0);
        $aggregate['margin_pct'] = $gross > 0
            ? round(($aggregate['operating_profit'] / $gross) * 100, 2)
            : 0.0;

        return $aggregate;
    }

    private function withEstimatedFee(array $aggregate): array
    {
        $gmv = (float) ($aggregate['gmv'] ?? 0);
        $cashPayout = (float) ($aggregate['cash_payout'] ?? $aggregate['payout'] ?? 0);
        $hpp = (float) ($aggregate['hpp'] ?? 0);
        $adCost = (float) ($aggregate['ad_cost'] ?? 0);
        $estimatedFee = $gmv * self::ESTIMATED_MARKETPLACE_FEE_RATE;
        $estimatedProfit = $gmv - $estimatedFee - $hpp - $adCost;

        return array_merge($aggregate, [
            'marketplace_fee_estimate_rate' => self::ESTIMATED_MARKETPLACE_FEE_RATE * 100,
            'marketplace_fee_estimate' => round($estimatedFee, 2),
            'marketplace_fee_estimate_on_payout' => round($cashPayout * self::ESTIMATED_MARKETPLACE_FEE_RATE, 2),
            'marketplace_fee_estimate_on_cash' => round($cashPayout * self::ESTIMATED_MARKETPLACE_FEE_RATE, 2),
            'estimated_profit' => round($estimatedProfit, 2),
            'estimated_profit_margin' => $gmv > 0 ? round(($estimatedProfit / $gmv) * 100, 2) : 0.0,
            'marketplace_fees_actual' => round((float) ($aggregate['cash_marketplace_fees'] ?? $aggregate['marketplace_fees'] ?? 0), 2),
        ]);
    }
}
