<?php

namespace App\Services\Channels\Contracts;

use App\Models\Store;

interface MarketplaceChannel
{
    public function getShopInfo(Store $store): array;

    public function getOrders(Store $store, int $timeFrom, int $timeTo, int $pageSize = 20): array;

    public function getOrderDetail(Store $store, array $orderSnList): array;

    public function refreshToken(Store $store): array;
}
