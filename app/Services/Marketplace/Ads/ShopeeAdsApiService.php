<?php

namespace App\Services\Marketplace\Ads;

use App\Models\Store;
use App\Services\Channels\ChannelManager;
use App\Services\Channels\Shopee\ShopeeChannel;
use Illuminate\Support\Facades\Log;

class ShopeeAdsApiService
{
    protected ChannelManager $channelManager;

    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    protected function driver(Store $store): ShopeeChannel
    {
        $driver = $this->channelManager->driver($store);
        if (!$driver instanceof ShopeeChannel) {
            throw new \Exception("Toko {$store->name} tidak menggunakan driver Shopee.");
        }
        return $driver;
    }

    protected function execute(Store $store, string $endpoint, callable $callable)
    {
        try {
            $response = $callable();
            
            // Log if there's an error from Shopee Ads API
            if (!empty($response['error'])) {
                Log::warning("[ShopeeAdsApiService] Error {$endpoint} untuk toko {$store->id}: " . ($response['message'] ?? $response['error']));
            }
            
            return $response;
        } catch (\Throwable $e) {
            Log::error("[ShopeeAdsApiService] Exception {$endpoint} untuk toko {$store->id}: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAdsTotalBalance(Store $store): array
    {
        return $this->execute($store, 'get_total_balance', fn() => $this->driver($store)->getAdsTotalBalance($store));
    }

    public function getCampaignIdList(Store $store, int $pageNo = 1, int $pageSize = 100): array
    {
        return $this->execute($store, 'get_campaign_id_list', fn() => $this->driver($store)->getCampaignIdList($store, $pageNo, $pageSize));
    }

    public function getCampaignSettingInfo(Store $store, array $campaignIds): array
    {
        return $this->execute($store, 'get_campaign_setting_info', fn() => $this->driver($store)->getCampaignSettingInfo($store, $campaignIds, '1,2,3,4'));
    }

    public function getAdsShopDailyPerformance(Store $store, string $startDate, string $endDate): array
    {
        return $this->execute($store, 'get_ads_shop_daily_performance', fn() => $this->driver($store)->getAdsShopDailyPerformance($store, $startDate, $endDate));
    }

    public function getAdsShopHourlyPerformance(Store $store, string $performanceDate): array
    {
        return $this->execute($store, 'get_ads_shop_hourly_performance', fn() => $this->driver($store)->getAdsShopHourlyPerformance($store, $performanceDate));
    }

    public function getCampaignDailyPerformance(Store $store, array $campaignIds, string $startDate, string $endDate): array
    {
        return $this->execute($store, 'get_campaign_daily_performance', fn() => $this->driver($store)->getCampaignDailyPerformance($store, $campaignIds, $startDate, $endDate));
    }

    public function getCampaignHourlyPerformance(Store $store, array $campaignIds, string $performanceDate): array
    {
        return $this->execute($store, 'get_campaign_hourly_performance', fn() => $this->driver($store)->getCampaignHourlyPerformance($store, $campaignIds, $performanceDate));
    }
}
