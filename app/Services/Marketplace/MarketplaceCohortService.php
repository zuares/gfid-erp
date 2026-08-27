<?php

namespace App\Services\Marketplace;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * SQL-backed cohort aggregation for the Marketplace Analytics page.
 *
 * The service intentionally returns aggregates only. Customer identifiers are
 * used inside COUNT(DISTINCT ...) and never leave the database response.
 */
class MarketplaceCohortService
{
    private const EXCLUDED_ORDER_STATUSES = [
        'UNPAID',
        'CANCELLED',
        'BATAL',
        'IN_CANCEL',
        'CANCELLED_BEFORE_SHIPPING',
        'TO_RETURN',
        'RETURNED',
        'REFUND',
    ];

    private const CUSTOMER_METRICS = [
        'retention_pct',
        'active_customers',
        'orders',
        'qty_sold',
        'revenue',
    ];

    private const PRODUCT_METRICS = [
        'qty_sold',
        'revenue',
        'gross_profit',
        'gross_margin_pct',
        'net_profit',
    ];

    public function cohort(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        return $filters['mode'] === 'product'
            ? $this->productCohort($filters)
            : $this->customerCohort($filters);
    }

    public function options(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);
        $optionFilters = array_merge($filters, [
            'category' => null,
            'product' => null,
            'sku' => null,
        ]);

        $marketplaceExpression = "COALESCE(NULLIF(TRIM(ch.name), ''), NULLIF(TRIM(ch.code), ''))";
        $marketplaces = $this->baseOrderQuery($optionFilters)
            ->selectRaw("{$marketplaceExpression} AS value")
            ->whereRaw("{$marketplaceExpression} IS NOT NULL")
            ->distinct()
            ->orderBy('value')
            ->limit(100)
            ->pluck('value')
            ->map(fn ($value) => (string) $value)
            ->filter()
            ->values()
            ->all();

        $itemQuery = $this->productOrderItemQuery($optionFilters, true);
        $categoryExpression = "COALESCE(NULLIF(ic.name, ''), 'Tanpa kategori')";
        $productExpression = $this->productNameExpression();
        $skuExpression = $this->skuExpression('moi');

        return [
            'marketplaces' => $marketplaces,
            'categories' => $this->distinctOptionValues($itemQuery, $categoryExpression),
            'products' => $this->distinctOptionValues($itemQuery, $productExpression),
            'skus' => $this->distinctOptionValues($itemQuery, $skuExpression),
        ];
    }

    private function customerCohort(array $filters): array
    {
        $buyerKey = $this->buyerKeyExpression('mo');
        $periodDate = 'COALESCE(mo.ordered_at, mo.order_date)';
        $firstTransactions = $this->customerFirstTransactions($filters);
        $orderQuantities = $this->orderQuantityTotals();
        $cohortMonth = $this->monthExpression('first_tx.first_transaction_at');
        $periodMonth = $this->monthExpression($periodDate);
        $revenue = $this->orderRevenueExpression('mo');

        $rows = $this->baseOrderQuery($filters)
            ->whereRaw("({$buyerKey}) IS NOT NULL")
            ->joinSub($firstTransactions, 'first_tx', function (JoinClause $join) use ($buyerKey) {
                $join->on(DB::raw($buyerKey), '=', 'first_tx.buyer_key');
            })
            ->leftJoinSub($orderQuantities, 'order_qty', 'order_qty.order_id', '=', 'mo.id')
            ->selectRaw("{$cohortMonth} AS cohort_month")
            ->selectRaw("{$periodMonth} AS period_month")
            ->selectRaw("COUNT(DISTINCT {$buyerKey}) AS active_customers")
            ->selectRaw('COUNT(DISTINCT mo.id) AS orders')
            ->selectRaw('COALESCE(SUM(order_qty.qty_sold), 0) AS qty_sold')
            ->selectRaw("COALESCE(SUM({$revenue}), 0) AS revenue")
            ->groupByRaw("{$cohortMonth}, {$periodMonth}")
            ->orderByRaw("{$cohortMonth}, {$periodMonth}")
            ->get();

        $cohortSizeMonth = $this->monthExpression('first_tx_sizes.first_transaction_at');
        $cohortSizes = DB::query()
            ->fromSub($firstTransactions, 'first_tx_sizes')
            ->selectRaw("{$cohortSizeMonth} AS cohort_month, COUNT(*) AS cohort_size")
            ->groupByRaw($cohortSizeMonth)
            ->pluck('cohort_size', 'cohort_month')
            ->map(fn ($value) => (int) $value)
            ->all();

        $periodRows = [];
        $maxPeriod = $this->periodSpan($filters);
        foreach ($rows as $row) {
            $cohortMonthValue = (string) $row->cohort_month;
            $periodMonthValue = (string) $row->period_month;
            $periodIndex = $this->monthDiff($cohortMonthValue, $periodMonthValue);
            if ($periodIndex < 0) {
                continue;
            }

            $maxPeriod = max($maxPeriod, $periodIndex);
            $base = (int) ($cohortSizes[$cohortMonthValue] ?? 0);
            $periodRows[$cohortMonthValue][$periodIndex] = [
                'period_month' => $periodMonthValue,
                'period_index' => $periodIndex,
                'active_customers' => (int) $row->active_customers,
                'orders' => (int) $row->orders,
                'qty_sold' => (int) $row->qty_sold,
                'revenue' => round((float) $row->revenue, 2),
                'retention_pct' => $base > 0
                    ? round(((int) $row->active_customers / $base) * 100, 2)
                    : null,
            ];
        }

        $cohortRows = [];
        foreach ($cohortSizes as $cohortMonthValue => $cohortSize) {
            $periods = $periodRows[$cohortMonthValue] ?? [];
            if (! $periods) {
                continue;
            }

            ksort($periods);
            $cohortRows[] = [
                'cohort_month' => $cohortMonthValue,
                'cohort_size' => $cohortSize,
                'periods' => $periods,
            ];
        }

        $metric = $filters['metric'];
        $summary = $this->customerSummary($cohortRows, $maxPeriod);

        return [
            'mode' => 'customer',
            'metric' => $metric,
            'metric_label' => $this->metricLabel($metric),
            'filters' => $filters,
            'max_period' => $maxPeriod,
            'rows' => $cohortRows,
            'summary' => $summary,
            'notes' => [
                'buyer_key' => 'buyer_username, fallback customer_id; anonymous orders are excluded from retention.',
                'cohort_month' => 'First eligible transaction month within the active filter scope.',
                'unavailable_periods' => 'Periods after the latest available order remain blank.',
            ],
        ];
    }

    private function productCohort(array $filters): array
    {
        $productKey = $this->productKeyExpression('moi');
        $productName = $this->productNameExpression();
        $sku = $this->skuExpression('moi');
        $category = "COALESCE(NULLIF(ic.name, ''), 'Tanpa kategori')";
        $periodDate = 'COALESCE(mo.ordered_at, mo.order_date)';
        $cohortMonth = $this->monthExpression('first_product.first_transaction_at');
        $periodMonth = $this->monthExpression($periodDate);
        $itemRevenue = $this->itemRevenueExpression('moi');
        $itemHpp = $this->itemHppExpression('moi');
        $fee = $this->feeExpression('mos');
        $ads = 'COALESCE(mos.ad_cost, 0)';

        $firstProducts = $this->productOrderItemQuery($filters, true)
            ->selectRaw("{$productKey} AS product_key")
            ->selectRaw('MIN(COALESCE(mo.ordered_at, mo.order_date)) AS first_transaction_at')
            ->groupByRaw($productKey);

        $orderItemTotals = DB::table('marketplace_order_items as total_moi')
            ->selectRaw('COALESCE(total_moi.marketplace_order_id, total_moi.order_id) AS order_id')
            ->selectRaw('SUM(' . $this->itemRevenueExpression('total_moi') . ') AS order_item_revenue')
            ->groupByRaw('COALESCE(total_moi.marketplace_order_id, total_moi.order_id)');

        $rows = $this->productOrderItemQuery($filters, true)
            ->joinSub($firstProducts, 'first_product', function (JoinClause $join) use ($productKey) {
                $join->on(DB::raw($productKey), '=', 'first_product.product_key');
            })
            ->leftJoinSub($orderItemTotals, 'order_item_totals', 'order_item_totals.order_id', '=', 'mo.id')
            ->leftJoin('marketplace_order_settlements as mos', 'mos.order_id', '=', 'mo.id')
            ->selectRaw("{$cohortMonth} AS cohort_month")
            ->selectRaw("{$periodMonth} AS period_month")
            ->selectRaw("{$productKey} AS product_key")
            ->selectRaw("{$productName} AS product_name")
            ->selectRaw("{$sku} AS sku")
            ->selectRaw("{$category} AS category")
            ->selectRaw('COUNT(DISTINCT mo.id) AS orders')
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(moi.qty, 0) > 0 THEN moi.qty ELSE 0 END), 0) AS qty_sold')
            ->selectRaw("COALESCE(SUM({$itemRevenue}), 0) AS revenue")
            ->selectRaw("COALESCE(SUM({$itemHpp}), 0) AS hpp")
            ->selectRaw("COALESCE(SUM(CASE WHEN mos.data_status = 'complete' AND COALESCE(order_item_totals.order_item_revenue, 0) > 0 THEN ({$fee}) * ({$itemRevenue}) / order_item_totals.order_item_revenue ELSE 0 END), 0) AS marketplace_fee")
            ->selectRaw("COALESCE(SUM(CASE WHEN mos.data_status = 'complete' AND COALESCE(order_item_totals.order_item_revenue, 0) > 0 THEN ({$ads}) * ({$itemRevenue}) / order_item_totals.order_item_revenue ELSE 0 END), 0) AS ads")
            ->selectRaw("COUNT(DISTINCT CASE WHEN mos.data_status = 'complete' THEN mo.id END) AS financial_order_count")
            ->groupByRaw("{$cohortMonth}, {$periodMonth}, {$productKey}, {$productName}, {$sku}, {$category}")
            ->orderByRaw("{$cohortMonth}, {$productKey}, {$periodMonth}")
            ->get();

        $grouped = [];
        $maxPeriod = $this->periodSpan($filters);
        foreach ($rows as $row) {
            $cohortMonthValue = (string) $row->cohort_month;
            $periodMonthValue = (string) $row->period_month;
            $periodIndex = $this->monthDiff($cohortMonthValue, $periodMonthValue);
            if ($periodIndex < 0) {
                continue;
            }

            $maxPeriod = max($maxPeriod, $periodIndex);
            $key = implode('|', [$cohortMonthValue, (string) $row->product_key]);
            $revenue = (float) $row->revenue;
            $hpp = (float) $row->hpp;
            $feeValue = (float) $row->marketplace_fee;
            $adsValue = (float) $row->ads;
            $grossProfit = $revenue - $feeValue - $hpp;
            $netProfit = $grossProfit - $adsValue;

            $grouped[$key] ??= [
                'cohort_month' => $cohortMonthValue,
                'product_key' => (string) $row->product_key,
                'product_name' => (string) ($row->product_name ?: $row->product_key),
                'sku' => (string) ($row->sku ?: '-'),
                'category' => (string) ($row->category ?: 'Tanpa kategori'),
                'periods' => [],
            ];
            $grouped[$key]['periods'][$periodIndex] = [
                'period_month' => $periodMonthValue,
                'period_index' => $periodIndex,
                'orders' => (int) $row->orders,
                'qty_sold' => (int) $row->qty_sold,
                'revenue' => round($revenue, 2),
                'hpp' => round($hpp, 2),
                'marketplace_fee' => round($feeValue, 2),
                'ads' => round($adsValue, 2),
                'gross_profit' => round($grossProfit, 2),
                'gross_margin_pct' => $revenue > 0 ? round(($grossProfit / $revenue) * 100, 2) : null,
                'net_profit' => round($netProfit, 2),
                'financial_coverage_pct' => (int) $row->orders > 0
                    ? round(((int) $row->financial_order_count / (int) $row->orders) * 100, 2)
                    : 0,
            ];
        }

        $productRows = array_values($grouped);
        foreach ($productRows as &$productRow) {
            ksort($productRow['periods']);
        }
        unset($productRow);

        return [
            'mode' => 'product',
            'metric' => $filters['metric'],
            'metric_label' => $this->metricLabel($filters['metric']),
            'filters' => $filters,
            'max_period' => $maxPeriod,
            'rows' => $productRows,
            'summary' => $this->productSummary($productRows),
            'notes' => [
                'product_key' => 'internal_item_id, model_sku, item_sku, external_sku, then item snapshot fallback.',
                'profit' => 'Marketplace fee and ads are allocated by item revenue only where settlement data_status is complete.',
                'coverage' => 'Financial coverage is exposed per cell so incomplete profit data is visible.',
            ],
        ];
    }

    private function customerFirstTransactions(array $filters): Builder
    {
        $buyerKey = $this->buyerKeyExpression('mo');

        return $this->baseOrderQuery($filters)
            ->whereRaw("({$buyerKey}) IS NOT NULL")
            ->selectRaw("{$buyerKey} AS buyer_key")
            ->selectRaw('MIN(COALESCE(mo.ordered_at, mo.order_date)) AS first_transaction_at')
            ->groupByRaw($buyerKey);
    }

    private function productOrderItemQuery(array $filters, bool $withDate): Builder
    {
        $query = $this->baseOrderQuery($filters, $withDate)
            ->join('marketplace_order_items as moi', function (JoinClause $join) {
                $join->on(function (JoinClause $nested) {
                    $nested->on('moi.marketplace_order_id', '=', 'mo.id')
                        ->orOn('moi.order_id', '=', 'mo.id');
                });
            })
            ->leftJoin('items as i', 'i.id', '=', 'moi.internal_item_id')
            ->leftJoin('item_categories as ic', 'ic.id', '=', 'i.item_category_id');

        $productKey = $this->productKeyExpression('moi');
        $sku = $this->skuExpression('moi');
        $category = "COALESCE(NULLIF(ic.name, ''), 'Tanpa kategori')";

        return $query
            ->whereRaw("({$productKey}) IS NOT NULL")
            ->when($filters['category'], fn (Builder $builder) => $builder->whereRaw("{$category} LIKE ?", ['%' . $filters['category'] . '%']))
            ->when($filters['product'], fn (Builder $builder) => $builder->whereRaw("({$this->productNameExpression()}) LIKE ?", ['%' . $filters['product'] . '%']))
            ->when($filters['sku'], fn (Builder $builder) => $builder->whereRaw("{$sku} LIKE ?", ['%' . $filters['sku'] . '%']));
    }

    private function baseOrderQuery(array $filters, bool $withDate = true): Builder
    {
        $query = DB::table('marketplace_orders as mo')
            ->join('stores as st', 'st.id', '=', 'mo.store_id')
            ->leftJoin('channels as ch', 'ch.id', '=', 'st.channel_id')
            ->where(function (Builder $builder) {
                $builder->whereNull('mo.order_status')
                    ->orWhereNotIn('mo.order_status', self::EXCLUDED_ORDER_STATUSES);
            })
            ->when($filters['store_id'], fn (Builder $builder, $storeId) => $builder->where('mo.store_id', $storeId))
            ->when($filters['marketplace'], function (Builder $builder, string $marketplace) {
                $builder->where(function (Builder $nested) use ($marketplace) {
                    $nested->where('ch.code', 'like', '%' . $marketplace . '%')
                        ->orWhere('ch.name', 'like', '%' . $marketplace . '%');
                });
            });

        $this->applyItemFiltersToOrders($query, $filters);

        if ($withDate) {
            $dateExpression = 'COALESCE(mo.ordered_at, mo.order_date)';
            if (! empty($filters['date_from'])) {
                $query->whereRaw("{$dateExpression} >= ?", [Carbon::parse($filters['date_from'])->startOfDay()]);
            }
            if (! empty($filters['date_to'])) {
                $query->whereRaw("{$dateExpression} < ?", [Carbon::parse($filters['date_to'])->addDay()->startOfDay()]);
            }
        }

        return $query->whereNotNull(DB::raw('COALESCE(mo.ordered_at, mo.order_date)'));
    }

    private function applyItemFiltersToOrders(Builder $query, array $filters): void
    {
        if (! $filters['category'] && ! $filters['product'] && ! $filters['sku']) {
            return;
        }

        $productKey = $this->productKeyExpression('filter_moi');
        $productName = $this->productNameExpression('filter_moi', 'filter_i');
        $sku = $this->skuExpression('filter_moi');
        $category = "COALESCE(NULLIF(filter_ic.name, ''), 'Tanpa kategori')";

        $query->whereExists(function (Builder $subQuery) use ($filters, $productKey, $productName, $sku, $category) {
            $subQuery
                ->selectRaw('1')
                ->from('marketplace_order_items as filter_moi')
                ->leftJoin('items as filter_i', 'filter_i.id', '=', 'filter_moi.internal_item_id')
                ->leftJoin('item_categories as filter_ic', 'filter_ic.id', '=', 'filter_i.item_category_id')
                ->where(function (Builder $nested) {
                    $nested->whereColumn('filter_moi.marketplace_order_id', 'mo.id')
                        ->orWhereColumn('filter_moi.order_id', 'mo.id');
                })
                ->when($filters['category'], fn (Builder $builder) => $builder->whereRaw("{$category} LIKE ?", ['%' . $filters['category'] . '%']))
                ->when($filters['product'], fn (Builder $builder) => $builder->whereRaw("({$productName}) LIKE ?", ['%' . $filters['product'] . '%']))
                ->when($filters['sku'], fn (Builder $builder) => $builder->whereRaw("{$sku} LIKE ?", ['%' . $filters['sku'] . '%']));
        });
    }

    private function orderQuantityTotals(): Builder
    {
        return DB::table('marketplace_order_items as moi')
            ->selectRaw('COALESCE(moi.marketplace_order_id, moi.order_id) AS order_id')
            ->selectRaw('SUM(CASE WHEN COALESCE(moi.qty, 0) > 0 THEN moi.qty ELSE 0 END) AS qty_sold')
            ->groupByRaw('COALESCE(moi.marketplace_order_id, moi.order_id)');
    }

    private function distinctOptionValues(Builder $query, string $expression, int $limit = 250): array
    {
        return (clone $query)
            ->selectRaw("{$expression} AS value")
            ->whereRaw("{$expression} IS NOT NULL")
            ->distinct()
            ->orderBy('value')
            ->limit($limit)
            ->pluck('value')
            ->map(fn ($value) => (string) $value)
            ->filter()
            ->values()
            ->all();
    }

    private function customerSummary(array $rows, int $maxPeriod): array
    {
        $m1 = [];
        $m3 = [];
        $latestActive = 0;
        foreach ($rows as $row) {
            foreach ([1 => &$m1, 3 => &$m3] as $period => &$bucket) {
                if (isset($row['periods'][$period]['retention_pct'])) {
                    $bucket[] = (float) $row['periods'][$period]['retention_pct'];
                }
            }
            unset($bucket);
            if (isset($row['periods'][$maxPeriod])) {
                $latestActive += (int) $row['periods'][$maxPeriod]['active_customers'];
            }
        }

        return [
            'cohort_count' => count($rows),
            'latest_active_customers' => $latestActive,
            'avg_m1_retention_pct' => $m1 ? round(array_sum($m1) / count($m1), 2) : null,
            'avg_m3_retention_pct' => $m3 ? round(array_sum($m3) / count($m3), 2) : null,
        ];
    }

    private function productSummary(array $rows): array
    {
        $revenue = 0.0;
        $grossProfit = 0.0;
        $coverage = [];
        foreach ($rows as $row) {
            foreach ($row['periods'] as $period) {
                $revenue += (float) $period['revenue'];
                $grossProfit += (float) $period['gross_profit'];
                $coverage[] = (float) $period['financial_coverage_pct'];
            }
        }

        return [
            'product_count' => count($rows),
            'revenue' => round($revenue, 2),
            'gross_profit' => round($grossProfit, 2),
            'gross_margin_pct' => $revenue > 0 ? round(($grossProfit / $revenue) * 100, 2) : null,
            'avg_financial_coverage_pct' => $coverage ? round(array_sum($coverage) / count($coverage), 2) : 0,
        ];
    }

    private function normalizeFilters(array $filters): array
    {
        $mode = ($filters['mode'] ?? 'customer') === 'product' ? 'product' : 'customer';
        $allowedMetrics = $mode === 'product' ? self::PRODUCT_METRICS : self::CUSTOMER_METRICS;
        $metric = in_array($filters['metric'] ?? null, $allowedMetrics, true)
            ? $filters['metric']
            : ($mode === 'product' ? 'revenue' : 'retention_pct');

        return [
            'mode' => $mode,
            'metric' => $metric,
            'store_id' => ! empty($filters['store_id']) ? (int) $filters['store_id'] : null,
            'marketplace' => trim((string) ($filters['marketplace'] ?? '')) ?: null,
            'category' => trim((string) ($filters['category'] ?? '')) ?: null,
            'product' => trim((string) ($filters['product'] ?? '')) ?: null,
            'sku' => trim((string) ($filters['sku'] ?? '')) ?: null,
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
        ];
    }

    private function metricLabel(string $metric): string
    {
        return [
            'retention_pct' => 'Retention %',
            'active_customers' => 'Active Customers',
            'orders' => 'Orders',
            'qty_sold' => 'Qty Sold',
            'revenue' => 'Revenue',
            'gross_profit' => 'Gross Profit',
            'gross_margin_pct' => 'Gross Margin %',
            'net_profit' => 'Net Profit',
        ][$metric] ?? $metric;
    }

    private function buyerKeyExpression(string $alias): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "COALESCE(NULLIF(TRIM({$alias}.buyer_username), ''), CASE WHEN {$alias}.customer_id IS NOT NULL THEN 'customer:' || {$alias}.customer_id END)";
        }

        return "COALESCE(NULLIF(TRIM({$alias}.buyer_username), ''), CASE WHEN {$alias}.customer_id IS NOT NULL THEN CONCAT('customer:', {$alias}.customer_id) END)";
    }

    private function productKeyExpression(string $alias): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "COALESCE(NULLIF(CAST({$alias}.internal_item_id AS TEXT), '0'), NULLIF(TRIM({$alias}.model_sku), ''), NULLIF(TRIM({$alias}.item_sku), ''), NULLIF(TRIM({$alias}.external_sku), ''), NULLIF(TRIM({$alias}.item_code_snapshot), ''))";
        }

        return "COALESCE(NULLIF(CAST({$alias}.internal_item_id AS CHAR), '0'), NULLIF(TRIM({$alias}.model_sku), ''), NULLIF(TRIM({$alias}.item_sku), ''), NULLIF(TRIM({$alias}.external_sku), ''), NULLIF(TRIM({$alias}.item_code_snapshot), ''))";
    }

    private function productNameExpression(string $itemAlias = 'moi', string $internalItemAlias = 'i'): string
    {
        return "COALESCE(NULLIF(TRIM({$internalItemAlias}.name), ''), NULLIF(TRIM({$itemAlias}.item_name), ''), NULLIF(TRIM({$itemAlias}.item_name_snapshot), ''), {$this->productKeyExpression($itemAlias)})";
    }

    private function skuExpression(string $alias): string
    {
        return "COALESCE(NULLIF(TRIM({$alias}.model_sku), ''), NULLIF(TRIM({$alias}.item_sku), ''), NULLIF(TRIM({$alias}.external_sku), ''), NULLIF(TRIM({$alias}.item_code_snapshot), ''))";
    }

    private function orderRevenueExpression(string $alias): string
    {
        return "COALESCE(NULLIF({$alias}.total_amount, 0), NULLIF({$alias}.total_paid_customer, 0), NULLIF({$alias}.subtotal_items, 0), 0)";
    }

    private function itemRevenueExpression(string $alias): string
    {
        $qty = "CASE WHEN COALESCE({$alias}.qty, 0) > 0 THEN {$alias}.qty ELSE 0 END";

        return "CASE WHEN COALESCE({$alias}.line_net_amount, 0) > 0 THEN {$alias}.line_net_amount WHEN COALESCE({$alias}.line_gross_amount, 0) > 0 THEN {$alias}.line_gross_amount WHEN COALESCE({$alias}.price_after_discount, 0) > 0 THEN {$alias}.price_after_discount * ({$qty}) ELSE COALESCE({$alias}.price, 0) * ({$qty}) END";
    }

    private function itemHppExpression(string $alias): string
    {
        $qty = "CASE WHEN COALESCE({$alias}.qty, 0) > 0 THEN {$alias}.qty ELSE 0 END";

        return "COALESCE(NULLIF({$alias}.hpp_total_snapshot, 0), NULLIF({$alias}.hpp_snapshot, 0) * ({$qty}), NULLIF({$alias}.hpp_unit_snapshot, 0) * ({$qty}), 0)";
    }

    private function feeExpression(string $alias): string
    {
        return "COALESCE({$alias}.commission_fee, 0) + COALESCE({$alias}.service_fee, 0) + COALESCE({$alias}.transaction_fee, 0) + COALESCE({$alias}.affiliate_fee, 0) + COALESCE({$alias}.activity_fee, 0) + COALESCE({$alias}.shipping_insurance_fee, 0) + COALESCE({$alias}.escrow_tax, 0)";
    }

    private function monthExpression(string $expression): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$expression})"
            : "DATE_FORMAT({$expression}, '%Y-%m')";
    }

    private function monthDiff(string $from, string $to): int
    {
        return Carbon::createFromFormat('Y-m', $from)->diffInMonths(Carbon::createFromFormat('Y-m', $to), false);
    }

    private function periodSpan(array $filters): int
    {
        if (empty($filters['date_from']) || empty($filters['date_to'])) {
            return 0;
        }

        return max(0, Carbon::parse($filters['date_from'])->startOfMonth()->diffInMonths(
            Carbon::parse($filters['date_to'])->startOfMonth(),
        ));
    }
}
