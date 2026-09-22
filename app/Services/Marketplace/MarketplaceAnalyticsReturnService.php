<?php

namespace App\Services\Marketplace;

/** Return/refund-detail entry point for the Marketplace Analytics tab. */
final class MarketplaceAnalyticsReturnService
{
    public function __construct(private MarketplaceAnalyticsSummaryService $summary)
    {
    }

    public function orders(array $filters, int $page = 1, int $perPage = 50, string $type = 'return_refund'): array
    {
        return $this->summary->returnOrders($filters, $page, $perPage, $type);
    }
}
