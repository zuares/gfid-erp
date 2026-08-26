<?php

namespace App\Services\Marketplace;

class MarketplaceFinancialStatementService
{
    public function __construct(private MarketplaceProfitReportService $profitReport)
    {
    }

    /**
     * Build a marketplace subledger statement from the verified profit report.
     * This intentionally does not create general-ledger journals.
     */
    public function statement(array $filters): array
    {
        $report = $this->profitReport->report($this->normalizeFilters($filters));
        $summary = $this->statementRow($report['summary']);
        $orderRows = $report['orders'];
        $summary['data_last_order_at'] = collect($orderRows)
            ->pluck('ordered_at')
            ->filter()
            ->max();
        $summary['payout_anomaly_count'] = collect($orderRows)
            ->filter(function (array $row) {
                $gross = (float) ($row['gross_sales'] ?? 0);
                $payout = (float) ($row['payout'] ?? 0);

                return $gross > 0 && $payout > $gross && ($payout - $gross) > max($gross * 0.10, 50000);
            })
            ->count();
        $summary['final'] = $this->statementRow($summary['final'] ?? []);
        $summary['provisional'] = $this->statementRow($summary['provisional'] ?? []);

        $stores = array_map(fn (array $row) => $this->statementRow($row), $report['stores']);
        $daily = array_map(function (array $row) {
            return array_merge(['date' => $row['date'] ?? null], $this->statementRow($row));
        }, $report['daily']);

        return [
            'filters' => $report['filters'],
            'quality' => $report['quality'],
            'summary' => $summary,
            'stores' => $stores,
            'daily' => $daily,
            'orders' => $report['orders'],
            'items' => $report['items'],
            'reconciliation' => [
                'gross_sales' => $summary['gross_sales'],
                'seller_discount' => $summary['seller_discount'],
                'net_sales_before_settlement' => $summary['net_sales_before_settlement'],
                'marketplace_fees' => $summary['marketplace_fees'],
                'refund' => $summary['refund'],
                'expected_payout_before_other_adjustments' => $summary['expected_payout_before_other_adjustments'],
                'other_settlement_adjustment' => $summary['other_settlement_adjustment'],
                'actual_payout' => $summary['payout'],
                'difference' => round(
                    $summary['payout'] - ($summary['expected_payout_before_other_adjustments'] + $summary['other_settlement_adjustment']),
                    2,
                ),
            ],
        ];
    }

    /**
     * Normalize filters without executing the potentially expensive report query.
     */
    public function normalizeFilters(array $filters): array
    {
        return [
            'store_id' => ! empty($filters['store_id']) ? (int) $filters['store_id'] : null,
            'report_scope' => ($filters['report_scope'] ?? 'final') === 'include_shipped'
                ? 'include_shipped'
                : 'final',
            'date_basis' => in_array($filters['date_basis'] ?? 'ordered_at', ['ordered_at', 'settlement_time'], true)
                ? $filters['date_basis']
                : 'ordered_at',
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
        ];
    }

    private function statementRow(array $row): array
    {
        $grossSales = (float) ($row['gross_sales'] ?? 0);
        $sellerDiscount = (float) ($row['seller_discount'] ?? 0);
        $fees = (float) ($row['marketplace_fees'] ?? 0);
        $refund = (float) ($row['refund'] ?? 0);
        $payout = (float) ($row['payout'] ?? 0);
        $hpp = (float) ($row['hpp'] ?? 0);
        $adCost = (float) ($row['ad_cost'] ?? 0);
        $netSales = $grossSales - $sellerDiscount;
        $expectedPayout = $netSales - $fees - $refund;
        $otherAdjustment = $payout - $expectedPayout;
        $grossProfit = $payout - $hpp;
        $operatingProfit = $grossProfit - $adCost;

        return array_merge($row, [
            'net_sales_before_settlement' => round($netSales, 2),
            'expected_payout_before_other_adjustments' => round($expectedPayout, 2),
            'other_settlement_adjustment' => round($otherAdjustment, 2),
            'gross_profit' => round($grossProfit, 2),
            'operating_profit' => round($operatingProfit, 2),
            'margin_pct' => $grossSales > 0 ? round(($operatingProfit / $grossSales) * 100, 2) : 0.0,
            'payout_rate_pct' => $grossSales > 0 ? round(($payout / $grossSales) * 100, 2) : 0.0,
            'fee_rate_pct' => $grossSales > 0 ? round(($fees / $grossSales) * 100, 2) : 0.0,
            'hpp_rate_pct' => $grossSales > 0 ? round(($hpp / $grossSales) * 100, 2) : 0.0,
            'refund_rate_pct' => $grossSales > 0 ? round(($refund / $grossSales) * 100, 2) : 0.0,
            'average_order_value' => ((float) ($row['order_count'] ?? 0)) > 0
                ? round($grossSales / (float) $row['order_count'], 2)
                : 0.0,
        ]);
    }
}
