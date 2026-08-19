<?php

namespace App\Services\Marketplace\Ads;

/**
 * Pure calculations used by Ads experiments.
 *
 * This class deliberately has no database or HTTP dependency. It accepts a
 * normalized metric snapshot and returns profit/BEP/simulation values so the
 * same contract can be used by the API and tests without mutating ad facts.
 */
class AdsExperimentCalculator
{
    public const ADS_VAT_FACTOR = 1.11;
    public const PROFIT_BASIS = 'net_hpp_ads';

    /**
     * Add profit and break-even metrics to an observed period.
     *
     * @return array<string, mixed>
     */
    public function periodMetrics(
        array $metrics,
        ?float $price,
        float $hpp,
        float $netRevenueRatio,
        bool $qtyEstimated = false,
        bool $priceEstimated = false,
        bool $mappingReady = true,
    ): array {
        $impressions = max(0, (int) ($metrics['impressions'] ?? 0));
        $clicks = max(0, (int) ($metrics['clicks'] ?? 0));
        $qty = max(0.0, (float) ($metrics['qty'] ?? 0));
        $revenue = max(0.0, (float) ($metrics['revenue'] ?? 0));
        $spend = max(0.0, (float) ($metrics['spend'] ?? 0));
        $netRevenueRatio = max(0.0, min(1.0, $netRevenueRatio));
        $hpp = max(0.0, $hpp);
        $price = $price !== null && $price > 0 ? $price : null;

        $adCost = $spend * self::ADS_VAT_FACTOR;
        $netRevenue = $revenue * $netRevenueRatio;
        $totalCogs = $hpp > 0 && $qty > 0 ? $hpp * $qty : null;
        $profit = $totalCogs !== null
            ? $netRevenue - $totalCogs - $adCost
            : null;
        $contributionPerUnit = $price !== null
            ? ($price * $netRevenueRatio) - $hpp
            : null;
        $breakEvenQty = $contributionPerUnit !== null && $contributionPerUnit > 0
            ? $adCost / $contributionPerUnit
            : null;
        $cvrBep = $breakEvenQty !== null && $clicks > 0
            ? $breakEvenQty / $clicks
            : null;
        $breakEvenRoas = $price !== null && $contributionPerUnit !== null && $contributionPerUnit > 0
            ? self::ADS_VAT_FACTOR / ($contributionPerUnit / $price)
            : null;

        $profitEstimated = $profit !== null && ($qtyEstimated || $priceEstimated);
        $actualProfitReady = $profit !== null
            && $mappingReady
            && ! $qtyEstimated
            && ! $priceEstimated
            && $hpp > 0
            && $price !== null;

        return [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'qty' => round($qty, 4),
            'revenue' => round($revenue, 2),
            'spend' => round($spend, 2),
            'net_revenue' => round($netRevenue, 2),
            'ad_cost_incl_vat' => round($adCost, 2),
            'total_cogs' => $totalCogs !== null ? round($totalCogs, 2) : null,
            'profit' => $profit !== null ? round($profit, 2) : null,
            'profit_estimated' => $profitEstimated,
            'actual_profit_ready' => $actualProfitReady,
            'price' => $price !== null ? round($price, 2) : null,
            'hpp' => $hpp > 0 ? round($hpp, 2) : null,
            'net_revenue_ratio' => round($netRevenueRatio, 6),
            'contribution_per_unit' => $contributionPerUnit !== null ? round($contributionPerUnit, 2) : null,
            'break_even_qty' => $breakEvenQty !== null ? round($breakEvenQty, 4) : null,
            'cvr_bep' => $cvrBep !== null ? round($cvrBep, 6) : null,
            'break_even_roas' => $breakEvenRoas !== null ? round($breakEvenRoas, 4) : null,
            'profit_basis' => self::PROFIT_BASIS,
            'ads_vat_factor' => self::ADS_VAT_FACTOR,
        ];
    }

    /**
     * Simulate a price and/or target ROAS change from one normalized period.
     *
     * @return array<string, mixed>
     */
    public function simulate(
        float $price,
        float $hpp,
        float $netRevenueRatio,
        float $spend,
        int $clicks,
        float $qty,
        ?float $targetRoas = null,
        bool $qtyEstimated = true,
    ): array {
        $price = max(0.0, $price);
        $hpp = max(0.0, $hpp);
        $spend = max(0.0, $spend);
        $qty = max(0.0, $qty);
        $clicks = max(0, $clicks);
        $ratio = max(0.0, min(1.0, $netRevenueRatio));
        $adCost = $spend * self::ADS_VAT_FACTOR;
        $contribution = $price > 0 ? ($price * $ratio) - $hpp : null;

        $base = $this->periodMetrics(
            [
                'impressions' => 0,
                'clicks' => $clicks,
                'qty' => $qty,
                'revenue' => $price * $qty,
                'spend' => $spend,
            ],
            $price > 0 ? $price : null,
            $hpp,
            $ratio,
            $qtyEstimated,
            false,
            $hpp > 0,
        );

        $targetGmv = $targetRoas !== null && $targetRoas > 0
            ? $spend * $targetRoas
            : null;
        $targetQty = $targetGmv !== null && $price > 0
            ? $targetGmv / $price
            : null;
        $targetProfit = $targetGmv !== null && $targetQty !== null && $hpp > 0
            ? ($targetGmv * $ratio) - ($targetQty * $hpp) - $adCost
            : null;

        return [
            'assumptions' => [
                'price' => round($price, 2),
                'hpp' => $hpp > 0 ? round($hpp, 2) : null,
                'net_revenue_ratio' => round($ratio, 6),
                'spend' => round($spend, 2),
                'clicks' => $clicks,
                'qty' => round($qty, 4),
                'qty_estimated' => $qtyEstimated,
                'target_roas' => $targetRoas !== null ? round($targetRoas, 4) : null,
            ],
            'current_quantity' => $base,
            'target_roas' => [
                'target_gmv' => $targetGmv !== null ? round($targetGmv, 2) : null,
                'target_qty' => $targetQty !== null ? round($targetQty, 4) : null,
                'target_cvr' => $targetQty !== null && $clicks > 0 ? round($targetQty / $clicks, 6) : null,
                'estimated_profit' => $targetProfit !== null ? round($targetProfit, 2) : null,
            ],
            'break_even' => [
                'contribution_per_unit' => $contribution !== null ? round($contribution, 2) : null,
                'qty' => $base['break_even_qty'],
                'cvr' => $base['cvr_bep'],
                'roas' => $base['break_even_roas'],
                'valid' => $contribution !== null && $contribution > 0,
            ],
            'profit_basis' => self::PROFIT_BASIS,
            'ads_vat_factor' => self::ADS_VAT_FACTOR,
        ];
    }
}
