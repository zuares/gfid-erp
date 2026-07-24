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
            usleep(500000); // Centralized rate limiting pace: 0.5 detik

            $response = $callable();

            // Tangkap 429 Rate Limit
            if (isset($response['_meta']['http_status']) && $response['_meta']['http_status'] == 429) {
                $retryAfter = (int) ($response['_meta']['retry_after'] ?? 0);
                if ($retryAfter <= 0) {
                    $retryAfter = 300; // fallback aman 5 menit
                }
                $retryAfter += random_int(15, 60); // jitter

                Log::warning("[ShopeeAdsApiService] Rate limit (429) {$endpoint} untuk toko {$store->id}. Delay: {$retryAfter}s.");
                throw new \App\Exceptions\ShopeeAdsRateLimitException($retryAfter, "Shopee Ads API rate limit reached pada endpoint {$endpoint}");
            }

            // Log if there's an error from Shopee Ads API
            if (!empty($response['error'])) {
                Log::warning("[ShopeeAdsApiService] Error {$endpoint} untuk toko {$store->id}: " . ($response['message'] ?? $response['error']));
            }

            return $response;
        } catch (\App\Exceptions\ShopeeAdsRateLimitException $e) {
            throw $e;
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

    public function getGmsCampaignPerformance(Store $store, array $campaignIds, string $startDate, string $endDate): array
    {
        return $this->execute($store, 'get_gms_campaign_performance', fn() => $this->driver($store)->getGmsCampaignPerformance($store, $campaignIds, $startDate, $endDate));
    }

    public function getGmsItemPerformance(Store $store, array $campaignIds, string $startDate, string $endDate): array
    {
        return $this->execute($store, 'get_gms_item_performance', fn() => $this->driver($store)->getGmsItemPerformance($store, $campaignIds, $startDate, $endDate));
    }
}
