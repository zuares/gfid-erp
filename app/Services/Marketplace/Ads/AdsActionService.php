<?php

namespace App\Services\Marketplace\Ads;

use App\Models\MarketplaceAdCampaign;
use App\Models\Store;
use App\Services\Channels\Shopee\ShopeeChannel;
use Illuminate\Support\Facades\Log;

class AdsActionService
{
    public function realtimeStatus(Store $store, ShopeeAdsApiService $adsApi): array
    {
        $balance = $adsApi->getAdsTotalBalance($store);
        $toggleInfo = $adsApi->getAdsShopToggleInfo($store);
        $facilRate = $adsApi->getAdsFacilShopRate($store);

        return [
            'status' => 'success',
            'data' => [
                'balance' => $balance['response'] ?? [],
                'toggle_info' => $toggleInfo['response'] ?? [],
                'facil_rate' => $facilRate['response'] ?? [],
            ],
        ];
    }

    public function actionGmsItem(Store $store, int $itemId, string $action, ShopeeChannel $shopeeChannel): array
    {
        $res = $shopeeChannel->editGmsItemProductCampaign($store, $action, [$itemId]);

        if (! empty($res['error'])) {
            return [
                'status' => 'error',
                'message' => $res['message'] ?? $res['error'],
                'http_status' => 400,
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Berhasil ' . ($action === 'add' ? 'menambahkan' : 'mengeluarkan') . ' produk dari GMV Max.',
        ];
    }

    public function actionCpcCampaign(
        Store $store,
        int $campaignId,
        ?float $roasTarget,
        ?float $dailyBudget,
        ?string $statusAction,
        ShopeeChannel $shopeeChannel
    ): array {
        $results = [];

        if ($roasTarget !== null && $roasTarget !== '') {
            $params = ['roas_target' => (float) $roasTarget];
            $resRoas = $shopeeChannel->editManualProductAds($store, $campaignId, 'change_roas_target', $params);
            if (! empty($resRoas['error'])) {
                return [
                    'status' => 'error',
                    'message' => 'Gagal ubah ROAS: ' . ($resRoas['message'] ?? $resRoas['error']),
                    'http_status' => 400,
                ];
            }
            $results[] = 'Target ROAS (' . ($roasTarget == 0 ? 'Auto' : $roasTarget) . ')';
            usleep(300000);
        }

        if ($dailyBudget !== null && $dailyBudget !== '') {
            $params = ['budget' => (float) $dailyBudget];
            $resBudget = $shopeeChannel->editManualProductAds($store, $campaignId, 'change_budget', $params);
            if (! empty($resBudget['error'])) {
                return [
                    'status' => 'error',
                    'message' => 'Gagal ubah Budget: ' . ($resBudget['message'] ?? $resBudget['error']),
                    'http_status' => 400,
                ];
            }
            $results[] = 'Batas Modal Harian (' . ($dailyBudget == 0 ? 'Tidak Terbatas' : number_format($dailyBudget, 0, ',', '.')) . ')';
        }

        if ($statusAction === 'pause' || $statusAction === 'resume') {
            $resStatus = $shopeeChannel->editManualProductAds($store, $campaignId, $statusAction, []);
            if (! empty($resStatus['error'])) {
                return [
                    'status' => 'error',
                    'message' => 'Gagal ubah status: ' . ($resStatus['message'] ?? $resStatus['error']),
                    'http_status' => 400,
                ];
            }
            $results[] = 'Status (' . ucfirst($statusAction) . ')';
            usleep(300000);
        }

        if (empty($results)) {
            return [
                'status' => 'error',
                'message' => 'Tidak ada pengaturan yang diubah.',
                'http_status' => 400,
            ];
        }

        $localCampaign = MarketplaceAdCampaign::where('store_id', $store->id)
            ->where('channel_campaign_id', $campaignId)
            ->first();

        if ($localCampaign) {
            if ($roasTarget !== null && $roasTarget !== '') {
                $localCampaign->target_roas = (float) $roasTarget;
            }
            if ($dailyBudget !== null && $dailyBudget !== '') {
                $localCampaign->campaign_budget = (float) $dailyBudget;
            }
            if ($statusAction === 'pause') {
                $localCampaign->campaign_status = 'paused';
            } elseif ($statusAction === 'resume') {
                $localCampaign->campaign_status = 'ongoing';
            }
            $localCampaign->save();
        }

        return [
            'status' => 'success',
            'message' => 'Pengaturan Kampanye CPC berhasil disimpan: ' . implode(' dan ', $results),
        ];
    }

    public function actionGmsCampaign(
        Store $store,
        ?string $campaignId,
        ?float $roasTarget,
        ?float $dailyBudget,
        ShopeeChannel $shopeeChannel
    ): array {
        if (is_string($campaignId) && str_starts_with($campaignId, 'GMS-')) {
            $campaignId = null;
        }

        $results = [];

        if ($roasTarget !== null && $roasTarget !== '') {
            $params = ['roas_target' => (float) $roasTarget];
            if ($campaignId) {
                $params['campaign_id'] = (int) $campaignId;
            }

            $resRoas = $shopeeChannel->editGmsProductCampaign($store, 'change_roas_target', $params);
            if (! empty($resRoas['error'])) {
                return [
                    'status' => 'error',
                    'message' => 'Gagal ubah ROAS: ' . ($resRoas['message'] ?? $resRoas['error']),
                    'http_status' => 400,
                ];
            }
            $results[] = 'Target ROAS (' . ($roasTarget == 0 ? 'Auto' : $roasTarget) . ')';
        }

        if ($dailyBudget !== null && $dailyBudget !== '') {
            $params = ['daily_budget' => (float) $dailyBudget];
            if ($campaignId) {
                $params['campaign_id'] = (int) $campaignId;
            }

            $resBudget = $shopeeChannel->editGmsProductCampaign($store, 'change_budget', $params);
            if (! empty($resBudget['error'])) {
                return [
                    'status' => 'error',
                    'message' => 'Gagal ubah budget: ' . ($resBudget['message'] ?? $resBudget['error']),
                    'http_status' => 400,
                ];
            }
            $results[] = 'Batas Modal Harian';
        }

        if (empty($results)) {
            return [
                'status' => 'error',
                'message' => 'Tidak ada data yang diubah.',
                'http_status' => 400,
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Berhasil memperbarui ' . implode(' dan ', $results) . ' pada kampanye GMV Max.',
        ];
    }

    public function campaignHourly(
        Store $store,
        int|string $campaignId,
        string $date,
        ShopeeAdsApiService $adsApi
    ): array {
        $res = $adsApi->getCampaignHourlyPerformance($store, [(string) $campaignId], $date);

        if (! empty($res['error'])) {
            Log::warning('[AdsActionService] campaignHourly error: ' . ($res['message'] ?? $res['error']));
            return [
                'status' => 'error',
                'message' => $res['message'] ?? $res['error'],
                'http_status' => 400,
            ];
        }

        return [
            'status' => 'success',
            'data' => $res['response'] ?? [],
        ];
    }
}
