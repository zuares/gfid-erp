<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceAdWalletTransaction;
use App\Models\MarketplaceAdsDaily;
use Carbon\Carbon;

class MarketplaceFinancialStatementService
{
    private const WALLET_AD_CHARGE_TYPES = ['450', 'paid_ads_charge', 'paid-ads-charge'];
    private const WALLET_AD_REFUND_TYPES = ['451', 'paid_ads_refund', 'paid-ads-refund'];

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

        // Wallet ads are period-level cash mutations. They are intentionally
        // reported separately from settlement.ad_cost until an allocation
        // policy to individual orders has been approved.
        $summary = array_merge($summary, $this->adCostReconciliation($report['filters']));

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
                'wallet_ad_cost' => $summary['wallet_ad_cost'],
                'wallet_ad_charge' => $summary['wallet_ad_charge'],
                'wallet_ad_refund' => $summary['wallet_ad_refund'],
                'ads_daily_spend' => $summary['ads_daily_spend'],
                'ad_cost_variance' => $summary['ad_cost_variance'],
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

    /**
     * Compare actual wallet deductions with Ads Daily performance spend.
     * Both are store-level aggregates and use the transaction/performance date,
     * not the order date or settlement release date.
     */
    private function adCostReconciliation(array $filters): array
    {
        $from = Carbon::parse($filters['date_from'])->startOfDay();
        $to = Carbon::parse($filters['date_to'])->endOfDay();

        $walletBase = MarketplaceAdWalletTransaction::query()
            ->whereBetween('transaction_created_at', [$from, $to])
            ->when($filters['store_id'], fn ($query, $storeId) => $query->where('store_id', $storeId));

        // Use the transaction type as the source of truth. Amount signs from
        // Shopee/proxies are not stable enough to distinguish charge/refund.
        $chargeRows = (clone $walletBase)
            ->whereIn('transaction_type', self::WALLET_AD_CHARGE_TYPES)
            ->selectRaw('COALESCE(SUM(ABS(amount)), 0) AS total')
            ->first();
        $refundRows = (clone $walletBase)
            ->whereIn('transaction_type', self::WALLET_AD_REFUND_TYPES)
            ->selectRaw('COALESCE(SUM(ABS(amount)), 0) AS total')
            ->first();
        $walletCount = (clone $walletBase)
            ->whereIn('transaction_type', array_merge(self::WALLET_AD_CHARGE_TYPES, self::WALLET_AD_REFUND_TYPES))
            ->count();

        $adsDailySpend = MarketplaceAdsDaily::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->when($filters['store_id'], fn ($query, $storeId) => $query->where('store_id', $storeId))
            ->sum('spend');

        $charge = round((float) ($chargeRows->total ?? 0), 2);
        $refund = round((float) ($refundRows->total ?? 0), 2);
        $walletNet = round($charge - $refund, 2);
        $adsDailySpend = round((float) $adsDailySpend, 2);

        return [
            'wallet_ad_charge' => $charge,
            'wallet_ad_refund' => $refund,
            'wallet_ad_cost' => $walletNet,
            'wallet_ad_transaction_count' => (int) $walletCount,
            'ads_daily_spend' => $adsDailySpend,
            'ad_cost_variance' => round($walletNet - $adsDailySpend, 2),
            'ad_cost_date_basis' => 'wallet_transaction_created_at',
        ];
    }
}
