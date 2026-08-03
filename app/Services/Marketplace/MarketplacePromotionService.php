<?php

namespace App\Services\Marketplace;

use App\Models\Store;

class MarketplacePromotionService
{
    public function __construct(
        protected MarketplaceApiGateway $gateway
    ) {}

    public function getDiscountList(Store $store, string $status = 'ongoing', int $pageNo = 1, int $pageSize = 100, ?int $updateTimeFrom = null, ?int $updateTimeTo = null): array
    {
        return $this->gateway->getDiscountList($store, $status, $pageNo, $pageSize, $updateTimeFrom, $updateTimeTo);
    }

    public function getDiscount(Store $store, int $discountId, int $pageNo = 1, int $pageSize = 50): array
    {
        return $this->gateway->getDiscount($store, $discountId, $pageNo, $pageSize);
    }

    public function createDiscount(Store $store, string $discountName, int $startTime, int $endTime): array
    {
        return $this->gateway->addDiscount($store, $discountName, $startTime, $endTime);
    }

    public function addDiscountItem(Store $store, int $discountId, array $itemList): array
    {
        return $this->gateway->addDiscountItem($store, $discountId, $itemList);
    }

    public function updateDiscount(Store $store, int $discountId, ?string $discountName = null, ?int $startTime = null, ?int $endTime = null): array
    {
        return $this->gateway->updateDiscount($store, $discountId, $discountName, $startTime, $endTime);
    }

    public function updateDiscountItem(Store $store, int $discountId, array $itemList): array
    {
        return $this->gateway->updateDiscountItem($store, $discountId, $itemList);
    }

    public function endDiscount(Store $store, int $discountId): array
    {
        return $this->gateway->endDiscount($store, $discountId);
    }

    public function deleteDiscount(Store $store, int $discountId): array
    {
        return $this->gateway->deleteDiscount($store, $discountId);
    }

    public function deleteDiscountItem(Store $store, int $discountId, int $itemId, int $modelId = 0): array
    {
        return $this->gateway->deleteDiscountItem($store, $discountId, $itemId, $modelId);
    }
}
