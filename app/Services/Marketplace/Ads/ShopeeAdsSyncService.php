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
            
            $list = data_get($res, 'response.campaign_list', []);
            $rawIdList = data_get($res, 'response.campaign_id_list', []);
            $extractedIds = $list !== []
                ? array_map(function ($c) { return $c['campaign_id'] ?? null; }, $list)
                : array_map(function ($c) { return is_array($c) ? ($c['campaign_id'] ?? null) : $c; }, $rawIdList);
            $extractedIds = array_filter($extractedIds); // remove nulls
            $campaignIds = array_merge($campaignIds, $extractedIds);
            
            $pageInfo = data_get($res, 'response.page_info');
            $hasMore = $pageInfo['has_more'] ?? false;
            $pageNo++;
        } while ($hasMore);

        $run->total_received += count($campaignIds);

        // Hemat kuota: kampanye yang SUDAH tutup/berakhir dan settingnya pernah
        // tersimpan tidak perlu di-fetch ulang — settingnya tidak akan berubah.
        // (Toko dengan ratusan kampanye lama turun dari ~5 call jadi ~1 call.)
        $knownClosed = MarketplaceAdCampaign::where('store_id', $store->id)
            ->whereIn('campaign_status', ['closed', 'ended', 'deleted'])
            ->whereNotNull('setting_synced_at')
            ->pluck('channel_campaign_id')
            ->map(fn ($v) => (string) $v)
            ->all();

        $idsToFetch = array_values(array_diff(
            array_map('strval', $campaignIds),
            $knownClosed
        ));

        // 2. Ambil Settings per chunk (API max 100) — kembalikan ke int untuk API
        $chunks = array_chunk(array_map('intval', $idsToFetch), 100);
        foreach ($chunks as $chunk) {
            $retry = 0;
            $res = null;
            while ($retry < 3) {
                $res = $this->api->getCampaignSettingInfo($store, $chunk);
                $run->total_requests++;
                
                if (!empty($res['error']) && $res['error'] === 'error_rate_limit') {
                    Log::warning("[ShopeeAdsSync] Rate limit hit for setting info chunk. Retrying in 2s...");
                    sleep(2);
                    $retry++;
                    continue;
                }
                break;
            }
            
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
                        'campaign_name' => $common['ad_name'] ?? $common['campaign_name'] ?? null,
                        'campaign_type' => $common['campaign_type'] ?? null,
                        'status' => $common['status'] ?? null,
                        'ad_type' => $common['ad_type'] ?? null,
                        'bidding_method' => $common['bidding_method'] ?? null,
                        'campaign_status' => match($common['campaign_status'] ?? null) {
                            1 => 'normal',
                            2 => 'ended',
                            3 => 'paused',
                            4 => 'abnormal',
                            5 => 'deleted',
                            // API juga bisa return string langsung
                            'normal', 'ongoing', 'closed', 'paused', 'ended', 'abnormal', 'deleted' => $common['campaign_status'],
                            default => $common['campaign_status'] ?? null,
                        },
                        'campaign_placement' => $common['campaign_placement'] ?? null,
                        'campaign_budget' => $common['campaign_budget'] ?? $common['budget'] ?? null,
                        'target_roas' => $auto['roas_target'] ?? $auto['target_roas'] ?? null,
                        'started_at' => isset($common['campaign_duration']['start_time']) && $common['campaign_duration']['start_time'] > 0 ? Carbon::createFromTimestamp($common['campaign_duration']['start_time']) : (isset($common['start_time']) && $common['start_time'] > 0 ? Carbon::createFromTimestamp($common['start_time']) : null),
                        'ended_at' => isset($common['campaign_duration']['end_time']) && $common['campaign_duration']['end_time'] > 0 ? Carbon::createFromTimestamp($common['campaign_duration']['end_time']) : (isset($common['end_time']) && $common['end_time'] > 0 ? Carbon::createFromTimestamp($common['end_time']) : null),
                        'raw_setting_payload' => $setting,
                        'setting_synced_at' => now(),
                    ]
                );
                
                $run->total_updated++;

                // Item list: simpan item yang terhubung ke kampanye
                $itemIdList = $common['item_id_list'] ?? [];
                if (!empty($itemIdList)) {
                    // Simpan item pertama langsung di campaign (untuk join cepat)
                    $campaign->update(['channel_item_id' => $itemIdList[0]]);
                    
                    foreach ($itemIdList as $itemId) {
                        MarketplaceAdsCampaignItem::updateOrCreate(
                            ['campaign_id' => $campaign->id, 'channel_item_id' => $itemId],
                            ['status' => $common['campaign_status'] ?? null]
                        );
                    }
                }
                // Fallback: cek product_ads_info (format lama)
                if (empty($itemIdList) && isset($setting['product_ads_info']['item_list'])) {
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
                'orders'      => $d['broad_order'] ?? $d['direct_order'] ?? $d['orders'] ?? 0,
                'gmv'         => $d['broad_gmv'] ?? $d['gmv'] ?? (isset($d['expense'], $d['direct_roi']) ? $d['expense'] * $d['direct_roi'] : ($d['direct_order_amount'] ?? 0)),
                'roas'        => $d['broad_roi'] ?? $d['direct_roi'] ?? $d['roas'] ?? null,
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

    public function syncCampaignDailyPerformance(Store $store, string $dateFrom, string $dateTo, MarketplaceAdsSyncRun $run): bool
    {
        // PENTING: buang pseudo-ID non-numerik (mis. 'GMS-5' milik GMV Max).
        // Satu ID non-numerik membuat Shopee menolak SELURUH request
        // ("campaign_id_list is invalid") sehingga data CPC tidak pernah tersimpan.
        $campaigns = MarketplaceAdCampaign::where('store_id', $store->id)
            ->pluck('channel_campaign_id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();
        if (empty($campaigns)) return true;
        
        $chunks = array_chunk($campaigns, 100);
        $hasFailure = false;
        
        foreach ($chunks as $chunk) {
            usleep(250000); // Rate limiter: 0.25 detik
            try {
                $res = $this->api->getCampaignDailyPerformance($store, $chunk, Carbon::parse($dateFrom)->format('d-m-Y'), Carbon::parse($dateTo)->format('d-m-Y'));
                $run->total_requests++;
            } catch (\App\Exceptions\ShopeeAdsRateLimitException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $hasFailure = true;
                Log::warning("[ShopeeAdsSync] Exception sync campaign daily: " . $e->getMessage());
                continue;
            }
            
            if (!empty($res['error'])) {
                $hasFailure = true;
                Log::warning("[ShopeeAdsSync] Error sync campaign daily: " . ($res['message'] ?? $res['error']));
                continue;
            }
            
            $list = data_get($res, 'response.campaign_list', []);
            $run->total_received += count($list);
            
            foreach ($list as $camp) {
                $channelCampaignId = $camp['campaign_id'] ?? null;
                $adType = $camp['ad_type'] ?? null;
                // API returns metrics_list instead of daily_performance
                $dailyList = $camp['metrics_list'] ?? $camp['daily_performance'] ?? [];
                
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
                            'ad_type'      => $adType,
                            'impressions'  => $d['impression'] ?? 0,
                            'clicks'       => $d['clicks'] ?? $d['click'] ?? 0,
                            'expense'      => $d['expense'] ?? 0,
                            'broad_order'  => $d['broad_order'] ?? 0,
                            // broad_order_amount = JUMLAH ITEM TERJUAL (pcs), bukan rupiah
                            'broad_order_amount'  => $d['broad_order_amount'] ?? 0,
                            'direct_order_amount' => $d['direct_order_amount'] ?? 0,
                            'broad_gmv'    => $d['broad_gmv'] ?? 0,
                            'direct_order' => $d['direct_order'] ?? 0,
                            'direct_gmv'   => $d['direct_gmv'] ?? (isset($d['expense'], $d['direct_roi']) ? $d['expense'] * $d['direct_roi'] : 0),
                            'cpc'          => $d['cpc'] ?? null,
                            'raw_json'     => $d,
                        ]
                    );
                    $run->total_updated++;
                }
            }
        }

        return ! $hasFailure;
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
                    'performance_date' => Carbon::parse($date)->format('Y-m-d 00:00:00'),
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

    public function syncGmsDailyPerformance(Store $store, string $dateFrom, string $dateTo, MarketplaceAdsSyncRun $run): bool
    {
        // Hanya sync kampanye yang AKTIF (status ongoing/paused, bukan ended/deleted)
        // Ini mengurangi 405 kampanye menjadi hanya ~10-30 yang relevan
        $campaigns = MarketplaceAdCampaign::where('store_id', $store->id)
            ->whereIn('campaign_status', ['ongoing', 'normal', 'paused'])
            ->pluck('channel_campaign_id')
            ->toArray();
            
        // Fallback: jika semua campaign_status null/kosong, ambil yang punya data baru-baru ini
        if (empty($campaigns)) {
            $campaigns = MarketplaceAdCampaign::where('store_id', $store->id)
                ->whereNotIn('campaign_status', ['ended', 'deleted', 'abnormal'])
                ->pluck('channel_campaign_id')
                ->toArray();
        }
        
        if (empty($campaigns)) {
            Log::info("[ShopeeAdsSync] No active GMS campaigns for store {$store->id}, skipping.");
            return true;
        }
        
        Log::info("[ShopeeAdsSync] GMS sync for store {$store->id}: {$dateFrom} to {$dateTo}, " . count($campaigns) . " active campaigns.");
        
        $start = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);
        $days = $start->diffInDays($end) + 1;
        $hasFailure = false;
        
        for ($i = 0; $i < $days; $i++) {
            $currentCarbon = $start->copy()->addDays($i);
            // Endpoint GMS menerima format DD-MM-YYYY.
            $dCurrent = $currentCarbon->format('d-m-Y');
            $dbCurrent = $currentCarbon->format('Y-m-d');

            // Resume-aware: hari historis yang BARU SAJA ditarik (≤2 jam lalu)
            // dilewati — retry setelah rate limit tidak mengulang dari nol.
            // 7 hari terakhir selalu ditarik (jendela revisi atribusi Shopee).
            if ($currentCarbon->lt(now()->subDays(7)->startOfDay())) {
                $freshGms = MarketplaceAdCampaignDaily::where('store_id', $store->id)
                    ->where('channel_campaign_id', 'GMS-' . $store->id)
                    ->where('date', $dbCurrent)
                    ->where('updated_at', '>=', now()->subHours(2))
                    ->exists();
                if ($freshGms) {
                    continue;
                }
            }

            usleep(120000); // jeda kecil antar hari (pace utama sudah di ShopeeAdsApiService)
            
            // 1. Campaign Performance (1 API call per day for global GMS)
            try {
                // campaign_id bersifat opsional untuk mengambil performa GMS tingkat toko.
                $res = $this->api->getGmsCampaignPerformance($store, null, $dCurrent, $dCurrent);
                $run->total_requests++;
                
                if (!empty($res['error'])) {
                    $hasFailure = true;
                    Log::warning("[ShopeeAdsSync] GMS Campaign API error: " . ($res['message'] ?? $res['error']));
                } elseif (!empty($res['response']['report'])) {
                    $report = $res['response']['report'];
                    
                    // Pastikan ada parent campaign untuk GMS agar muncul di Daftar Kampanye
                    \App\Models\MarketplaceAdCampaign::updateOrCreate(
                        ['store_id' => $store->id, 'channel_campaign_id' => 'GMS-' . $store->id],
                        [
                            'campaign_name' => 'GMV Max (Semua Produk)',
                            'bidding_method' => 'auto',
                            'status' => 'ONGOING',
                            'synced_at' => now(),
                        ]
                    );
                    
                    // Simpan per hari dengan ID dummy untuk global GMS
                    MarketplaceAdCampaignDaily::updateOrCreate(
                        [
                            'store_id' => $store->id,
                            'channel_campaign_id' => 'GMS-' . $store->id,
                            'date' => $dbCurrent,
                        ],
                        [
                            'impressions'  => $report['impression'] ?? 0,
                            'clicks'       => $report['clicks'] ?? $report['click'] ?? 0,
                            'expense'      => $report['expense'] ?? 0,
                            'broad_order'  => $report['broad_order'] ?? 0,
                            'broad_order_amount'  => $report['broad_order_amount'] ?? 0,
                            'direct_order_amount' => $report['direct_order_amount'] ?? 0,
                            'broad_gmv'    => $report['broad_gmv'] ?? 0,
                            'direct_order' => $report['direct_order'] ?? 0,
                            'direct_gmv'   => $report['direct_gmv'] ?? (isset($report['expense'], $report['direct_roi']) ? $report['expense'] * $report['direct_roi'] : 0),
                            'cpc'          => $report['cpc'] ?? null,
                            'raw_json'     => $report,
                        ]
                    );
                }
            } catch (\App\Exceptions\ShopeeAdsRateLimitException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $hasFailure = true;
                Log::warning("[ShopeeAdsSync] GMS Campaign Sync failed: " . $e->getMessage());
            }

            // 2. Item Performance
            usleep(120000);
            try {
                $resItem = $this->api->getGmsItemPerformance($store, null, $dCurrent, $dCurrent);
                $run->total_requests++;
                
                if (!empty($resItem['error'])) {
                    $hasFailure = true;
                    Log::warning("[ShopeeAdsSync] GMS Item API error: " . ($resItem['message'] ?? $resItem['error']));
                } elseif (!empty($resItem['response']['result_list'])) {
                    $itemList = $resItem['response']['result_list'];
                    
                    foreach ($itemList as $item) {
                        $channelItemId = $item['item_id'] ?? null;
                        $report = $item['report'] ?? [];
                        
                        if (empty($channelItemId) || empty($report)) continue;
                        
                        \App\Models\MarketplaceAdsItemDaily::updateOrCreate(
                            [
                                'store_id' => $store->id,
                                'channel_campaign_id' => 'GMS-' . $store->id,
                                'channel_item_id' => $channelItemId,
                                'date' => $dbCurrent,
                            ],
                            [
                                'impressions'  => $report['impression'] ?? 0,
                                'clicks'       => $report['clicks'] ?? $report['click'] ?? 0,
                                'expense'      => $report['expense'] ?? 0,
                                'broad_order'  => $report['broad_order'] ?? 0,
                                'broad_gmv'    => $report['broad_gmv'] ?? $report['broad_order_amount'] ?? 0,
                                'direct_order' => $report['direct_order'] ?? 0,
                                'direct_gmv'   => $report['direct_gmv'] ?? (isset($report['expense'], $report['direct_roi']) ? $report['expense'] * $report['direct_roi'] : ($report['direct_order_amount'] ?? 0)),
                                'cpc'          => $report['cpc'] ?? null,
                                'raw_json'     => $report,
                            ]
                        );
                    }
                }
            } catch (\App\Exceptions\ShopeeAdsRateLimitException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $hasFailure = true;
                Log::warning("[ShopeeAdsSync] GMS Item Sync failed: " . $e->getMessage());
            }
        }

        return ! $hasFailure;
    }
}
