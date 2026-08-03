<?php

namespace App\Services\Marketplace;

use App\Models\Store;

class MarketplaceReturnService
{
    public function __construct(
        protected MarketplaceApiGateway $gateway
    ) {}

    public function getLiveReturns(Store $store, int $timeFrom, int $timeTo, int $pageNo = 0, int $pageSize = 40): array
    {
        return $this->gateway->getReturnList($store, $pageNo, $pageSize, $timeFrom, $timeTo);
    }

    public function getReturnDetail(Store $store, string $returnSn): array
    {
        return $this->gateway->getReturnDetail($store, $returnSn);
    }

    public function getTracking(Store $store, string $returnSn): array
    {
        return $this->gateway->getReverseTrackingInfo($store, $returnSn);
    }

    public function confirmAndRestock(Store $store, string $returnSn): array
    {
        return $this->gateway->confirmReturn($store, $returnSn);
    }
}
