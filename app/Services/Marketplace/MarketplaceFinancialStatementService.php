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
        $report = $this->profitReport->report($filters);
        $summary = $this->statementRow($report['summary']);

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
        ]);
    }
}
