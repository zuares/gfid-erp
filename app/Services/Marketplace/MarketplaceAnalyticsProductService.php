<?php

namespace App\Services\Marketplace;

/** Product-tab entry point for Marketplace Analytics. */
final class MarketplaceAnalyticsProductService
{
    public function __construct(private MarketplaceAnalyticsSummaryService $summary)
    {
    }

    public function products(array $filters, int $limit = 100): array
    {
        return $this->summary->products($filters, $limit);
    }
}
