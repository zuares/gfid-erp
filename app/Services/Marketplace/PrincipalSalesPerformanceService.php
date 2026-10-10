<?php

namespace App\Services\Marketplace;

use App\Models\Store;

class PrincipalSalesPerformanceService
{
    public function __construct(
        protected MarketplaceApiGateway $gateway
    ) {}

    public function fetch(Store $store, string $principalId, array $payload): array
    {
        return $this->gateway->getPrincipalSalesPerformanceDetail(
            $store,
            $principalId,
            $payload
        );
    }
}
