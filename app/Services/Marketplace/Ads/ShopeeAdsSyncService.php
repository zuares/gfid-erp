<?php

namespace App\Services\Marketplace\Ads;

use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdsBalanceLog;
use App\Models\MarketplaceAdsCampaignItem;
use App\Models\MarketplaceAdsDaily;
use App\Models\MarketplaceAdCampaignDaily;
use App\Models\MarketplaceAdsHourlyPerformance;
use App\Models\MarketplaceAdsSyncRun;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShopeeAdsSyncService
{
    protected ShopeeAdsApiService $api;

    public function __construct(ShopeeAdsApiService $api)
    {
        $this->api = $api;
    }

    public function syncBalance(Store $store, MarketplaceAdsSyncRun $run): void
    {
        $res = $this->api->getAdsTotalBalance($store);
        $run->total_requests++;
        
        if (!empty($res['error'])) {
            throw new \Exception("Gagal mengambil saldo: " . ($res['message'] ?? $res['error']));
        }
        
        $run->total_received++;
        $bal = data_get($res, 'response.total_balance');
        if ($bal !== null) {
            MarketplaceAdsBalanceLog::create(['store_id' => $store->id, 'balance' => $bal]);
            $run->total_inserted++;
        }
    }

    public function syncCampaignsAndSettings(Store $store, MarketplaceAdsSyncRun $run): void
    {
        // 1. Ambil daftar Campaign ID
        $pageNo = 1;
        $campaignIds = [];
        
        do {
            $res = $this->api->getCampaignIdList($store, $pageNo, 100);
            $run->total_requests++;
            
            if (!empty($res['error'])) {
                throw new \Exception("Gagal mengambil campaign id list: " . ($res['message'] ?? $res['error']));
            }
            
            $list = data_get($res, 'response.campaign_id_list', []);
            $campaignIds = array_merge($campaignIds, $list);
            
            $pageInfo = data_get($res, 'response.page_info');
            $hasMore = $pageInfo['has_more'] ?? false;
            $pageNo++;
        } while ($hasMore);

        $run->total_received += count($campaignIds);

        // 2. Ambil Settings per chunk (API max 100)
        $chunks = array_chunk($campaignIds, 100);
        foreach ($chunks as $chunk) {
            $res = $this->api->getCampaignSettingInfo($store, $chunk);
            $run->total_requests++;
            
            if (!empty($res['error'])) {
                Log::warning("[ShopeeAdsSync] Error sync setting info chunk: " . ($res['message'] ?? $res['error']));
                continue;
            }
            
            $settingsList = data_get($res, 'response.campaign_list', []);
            
            foreach ($settingsList as $setting) {
                $channelCampaignId = $setting['campaign_id'] ?? null;
                if (!$channelCampaignId) continue;
                
                $common = $setting['common_info'] ?? [];
                $manual = $setting['manual_bidding_info'] ?? [];
                $auto = $setting['auto_bidding_info'] ?? [];
                
                $campaign = MarketplaceAdCampaign::updateOrCreate(
                    [
                        'store_id' => $store->id,
                        'channel_campaign_id' => $channelCampaignId,
                    ],
                    [
                        'campaign_name' => $common['campaign_name'] ?? null,
                        'campaign_type' => $common['campaign_type'] ?? null,
                        'status' => $common['status'] ?? null,
                        'ad_type' => $common['ad_type'] ?? null,
                        'bidding_method' => $common['bidding_method'] ?? null,
                        'campaign_status' => $common['campaign_status'] ?? null,
                        'campaign_placement' => $common['campaign_placement'] ?? null,
                        'campaign_budget' => $common['budget'] ?? null,
                        'target_roas' => $auto['target_roas'] ?? null,
                        'started_at' => isset($common['start_time']) && $common['start_time'] > 0 ? Carbon::createFromTimestamp($common['start_time']) : null,
                        'ended_at' => isset($common['end_time']) && $common['end_time'] > 0 ? Carbon::createFromTimestamp($common['end_time']) : null,
                        'raw_setting_payload' => $setting,
                        'setting_synced_at' => now(),
                    ]
                );
                
                $run->total_updated++;

                // Item list (jika ada)
                if (isset($setting['product_ads_info']['item_list'])) {
                    foreach ($setting['product_ads_info']['item_list'] as $item) {
                        MarketplaceAdsCampaignItem::updateOrCreate(
                            ['campaign_id' => $campaign->id, 'channel_item_id' => $item['item_id']],
                            ['status' => $item['status'] ?? null]
                        );
                    }
                }
            }
        }
    }

    public function syncShopDailyPerformance(Store $store, string $dateFrom, string $dateTo, MarketplaceAdsSyncRun $run): void
    {
        $res = $this->api->getAdsShopDailyPerformance($store, Carbon::parse($dateFrom)->format('d-m-Y'), Carbon::parse($dateTo)->format('d-m-Y'));
        $run->total_requests++;
        
        if (!empty($res['error'])) {
            throw new \Exception("Gagal mengambil shop daily: " . ($res['message'] ?? $res['error']));
        }
        
        $list = data_get($res, 'response.day_list') ?? data_get($res, 'response.daily_performance') ?? (is_array($res['response'] ?? null) && array_is_list($res['response']) ? $res['response'] : []);
        $run->total_received += count($list);
        
        foreach ($list as $d) {
            if (empty($d['date'])) continue;
            $dateObj = Carbon::createFromFormat('d-m-Y', $d['date']);
            $record = MarketplaceAdsDaily::where('store_id', $store->id)
                ->whereDate('date', $dateObj)
                ->first();
                
            $payload = [
                'impressions' => $d['impression'] ?? $d['impressions'] ?? 0,
                'clicks'      => $d['clicks'] ?? $d['click'] ?? 0,
                'ctr'         => $d['ctr'] ?? null,
                'spend'       => $d['expense'] ?? $d['spend'] ?? 0,
                'orders'      => $d['broad_order'] ?? $d['orders'] ?? 0,
                'gmv'         => $d['broad_gmv'] ?? $d['broad_order_amount'] ?? $d['gmv'] ?? 0,
                'roas'        => $d['broad_roi'] ?? $d['roas'] ?? null,
                'cpc'         => $d['cpc'] ?? null,
                'cvr'         => $d['conversion_rate'] ?? null,
                'raw_json'    => $d,
            ];
            
            if ($record) {
                $record->update($payload);
            } else {
                MarketplaceAdsDaily::create(array_merge([
                    'store_id' => $store->id,
                    'date' => $dateObj->format('Y-m-d')
                ], $payload));
            }
            
            $run->total_updated++;
        }
    }

    public function syncCampaignDailyPerformance(Store $store, string $dateFrom, string $dateTo, MarketplaceAdsSyncRun $run): void
    {
        $campaigns = MarketplaceAdCampaign::where('store_id', $store->id)->pluck('channel_campaign_id')->toArray();
        if (empty($campaigns)) return;
        
        $chunks = array_chunk($campaigns, 100);
        
        foreach ($chunks as $chunk) {
            usleep(250000); // Rate limiter: 0.25 detik
            $res = $this->api->getCampaignDailyPerformance($store, $chunk, Carbon::parse($dateFrom)->format('d-m-Y'), Carbon::parse($dateTo)->format('d-m-Y'));
            $run->total_requests++;
            
            if (!empty($res['error'])) {
                Log::warning("[ShopeeAdsSync] Error sync campaign daily: " . ($res['message'] ?? $res['error']));
                continue;
            }
            
            $list = data_get($res, 'response.campaign_list', []);
            $run->total_received += count($list);
            
            foreach ($list as $camp) {
                $channelCampaignId = $camp['campaign_id'] ?? null;
                $dailyList = $camp['daily_performance'] ?? [];
                
                foreach ($dailyList as $d) {
                    if (empty($d['date'])) continue;
                    $date = Carbon::createFromFormat('d-m-Y', $d['date'])->format('Y-m-d');
                    
                    MarketplaceAdCampaignDaily::updateOrCreate(
                        [
                            'store_id' => $store->id,
                            'channel_campaign_id' => $channelCampaignId,
                            'date' => $date,
                        ],
                        [
                            'impressions'  => $d['impression'] ?? 0,
                            'clicks'       => $d['clicks'] ?? $d['click'] ?? 0,
                            'expense'      => $d['expense'] ?? 0,
                            'broad_order'  => $d['broad_order'] ?? 0,
                            'broad_gmv'    => $d['broad_gmv'] ?? $d['broad_order_amount'] ?? 0,
                            'direct_order' => $d['direct_order'] ?? 0,
                            'direct_gmv'   => $d['direct_gmv'] ?? 0,
                            'cpc'          => $d['cpc'] ?? null,
                            'raw_json'     => $d,
                        ]
                    );
                    $run->total_updated++;
                }
            }
        }
    }

    public function syncShopHourlyPerformance(Store $store, string $date, MarketplaceAdsSyncRun $run): void
    {
        $res = $this->api->getAdsShopHourlyPerformance($store, Carbon::parse($date)->format('d-m-Y'));
        $run->total_requests++;
        
        if (!empty($res['error'])) {
            throw new \Exception("Gagal mengambil shop hourly: " . ($res['message'] ?? $res['error']));
        }
        
        $list = data_get($res, 'response', []);
        $run->total_received += count($list);
        
        foreach ($list as $d) {
            $hour = $d['hour'] ?? null;
            if ($hour === null) continue;
            
            MarketplaceAdsHourlyPerformance::updateOrCreate(
                [
                    'store_id' => $store->id,
                    'campaign_id' => null,
                    'channel_campaign_id' => '-',
                    'performance_date' => $date,
                    'performance_hour' => (int) $hour,
                ],
                [
                    'impression' => $d['impression'] ?? 0,
                    'clicks'     => $d['clicks'] ?? $d['click'] ?? 0,
                    'ctr'        => $d['ctr'] ?? null,
                    'expense'    => $d['expense'] ?? 0,
                    'broad_gmv'  => $d['broad_gmv'] ?? $d['broad_order_amount'] ?? 0,
                    'broad_order'=> $d['broad_order'] ?? 0,
                    'broad_roi'  => $d['broad_roi'] ?? null,
                    'conversion_rate' => $d['conversion_rate'] ?? null,
                    'cpc'        => $d['cpc'] ?? null,
                    'direct_gmv' => $d['direct_gmv'] ?? 0,
                    'direct_roi' => $d['direct_roi'] ?? null,
                    'raw_response'=> $d,
                ]
            );
            $run->total_updated++;
        }
    }
}
