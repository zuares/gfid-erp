<?php

namespace App\Services\Marketplace;

/**
 * Overview/KPI entry point for Marketplace Analytics.
 *
 * The legacy summary service remains the shared aggregation engine for now;
 * this boundary lets the controller depend on the tab's domain instead of a
 * service that owns every analytics tab.
 */
final class MarketplaceAnalyticsOverviewService
{
    public function __construct(private MarketplaceAnalyticsSummaryService $summary)
    {
    }

    public function summary(array $filters): array
    {
        return $this->summary->summary($filters);
    }

    public function kpis(array $filters): array
    {
        return $this->summary->kpis($filters);
    }
}
