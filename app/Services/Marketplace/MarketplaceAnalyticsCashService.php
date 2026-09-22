<?php

namespace App\Services\Marketplace;

/** Cash/order-detail entry point for the Marketplace Analytics tab. */
final class MarketplaceAnalyticsCashService
{
    public function __construct(private MarketplaceAnalyticsSummaryService $summary)
    {
    }

    public function orders(array $filters, int $page = 1, int $perPage = 50, string $settlement = 'settled'): array
    {
        return $this->summary->cashOrders($filters, $page, $perPage, $settlement);
    }
}
