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
        $isDemoStore = app()->environment('local') && (bool) data_get($store->meta, 'ads_demo');
        $localCampaign = MarketplaceAdCampaign::where('store_id', $store->id)
            ->where('channel_campaign_id', (string) $campaignId)
            ->first();
        $oldTargetRoas = $localCampaign?->target_roas !== null
            ? (float) $localCampaign->target_roas
            : null;

        if ($roasTarget !== null && $roasTarget !== '') {
            if (! $isDemoStore) {
                $params = ['roas_target' => (float) $roasTarget];
                $resRoas = $shopeeChannel->editManualProductAds($store, $campaignId, 'change_roas_target', $params);
                if (! empty($resRoas['error'])) {
                    return [
                        'status' => 'error',
                        'message' => 'Gagal ubah ROAS: ' . ($resRoas['message'] ?? $resRoas['error']),
                        'http_status' => 400,
                    ];
                }
            }
            $results[] = 'Target ROAS (' . ($roasTarget == 0 ? 'Auto' : $roasTarget) . ')';
            if (! $isDemoStore) {
                usleep(300000);
            }
        }

        if ($dailyBudget !== null && $dailyBudget !== '') {
            if (! $isDemoStore) {
                $params = ['budget' => (float) $dailyBudget];
                $resBudget = $shopeeChannel->editManualProductAds($store, $campaignId, 'change_budget', $params);
                if (! empty($resBudget['error'])) {
                    return [
                        'status' => 'error',
                        'message' => 'Gagal ubah Budget: ' . ($resBudget['message'] ?? $resBudget['error']),
                        'http_status' => 400,
                    ];
                }
            }
            $results[] = 'Batas Modal Harian (' . ($dailyBudget == 0 ? 'Tidak Terbatas' : number_format($dailyBudget, 0, ',', '.')) . ')';
        }

        if ($statusAction === 'pause' || $statusAction === 'resume') {
            if (! $isDemoStore) {
                $resStatus = $shopeeChannel->editManualProductAds($store, $campaignId, $statusAction, []);
                if (! empty($resStatus['error'])) {
                    return [
                        'status' => 'error',
                        'message' => 'Gagal ubah status: ' . ($resStatus['message'] ?? $resStatus['error']),
                        'http_status' => 400,
                    ];
                }
            }
            $results[] = 'Status (' . ucfirst($statusAction) . ')';
            if (! $isDemoStore) {
                usleep(300000);
            }
        }

        if (empty($results)) {
            return [
                'status' => 'error',
                'message' => 'Tidak ada pengaturan yang diubah.',
                'http_status' => 400,
            ];
        }

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

        if ($roasTarget !== null && $roasTarget !== '') {
            try {
                app(AdsExperimentService::class)->recordTargetRoasChange(
                    store: $store,
                    channelCampaignId: (string) $campaignId,
                    channelItemId: $localCampaign?->channel_item_id !== null
                        ? (string) $localCampaign->channel_item_id
                        : null,
                    oldTargetRoas: $oldTargetRoas,
                    newTargetRoas: (float) $roasTarget,
                    confounders: array_values(array_filter([
                        $dailyBudget !== null && $dailyBudget !== '' ? 'daily_budget_changed' : null,
                        $statusAction !== null ? 'campaign_status_changed' : null,
                    ])),
                    createdBy: auth()->id(),
                );
            } catch (\Throwable $e) {
                Log::warning('[AdsActionService] failed to record target ROAS experiment', [
                    'store_id' => $store->id,
                    'campaign_id' => $campaignId,
                    'error' => $e->getMessage(),
                ]);
            }
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
        // Keep the pseudo-ID for local persistence/experiment scope, but omit
        // it from the Shopee request because GMS-<store_id> is not an API ID.
        $apiCampaignId = is_string($campaignId) && str_starts_with($campaignId, 'GMS-')
            ? null
            : $campaignId;
        $isDemoStore = app()->environment('local') && (bool) data_get($store->meta, 'ads_demo');

        $results = [];
        $localCampaign = $campaignId !== null
            ? MarketplaceAdCampaign::where('store_id', $store->id)
                ->where('channel_campaign_id', (string) $campaignId)
                ->first()
            : null;
        $oldTargetRoas = $localCampaign?->target_roas !== null
            ? (float) $localCampaign->target_roas
            : null;

        if ($roasTarget !== null && $roasTarget !== '') {
            $params = ['roas_target' => (float) $roasTarget];
            if ($apiCampaignId) {
                $params['campaign_id'] = (int) $apiCampaignId;
            }

            if (! $isDemoStore) {
                $resRoas = $shopeeChannel->editGmsProductCampaign($store, 'change_roas_target', $params);
                if (! empty($resRoas['error'])) {
                    return [
                        'status' => 'error',
                        'message' => 'Gagal ubah ROAS: ' . ($resRoas['message'] ?? $resRoas['error']),
                        'http_status' => 400,
                    ];
                }
            }
            $results[] = 'Target ROAS (' . ($roasTarget == 0 ? 'Auto' : $roasTarget) . ')';
        }

        if ($dailyBudget !== null && $dailyBudget !== '') {
            $params = ['daily_budget' => (float) $dailyBudget];
            if ($apiCampaignId) {
                $params['campaign_id'] = (int) $apiCampaignId;
            }

            if (! $isDemoStore) {
                $resBudget = $shopeeChannel->editGmsProductCampaign($store, 'change_budget', $params);
                if (! empty($resBudget['error'])) {
                    return [
                        'status' => 'error',
                        'message' => 'Gagal ubah budget: ' . ($resBudget['message'] ?? $resBudget['error']),
                        'http_status' => 400,
                    ];
                }
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

        if ($localCampaign && $roasTarget !== null && $roasTarget !== '') {
            $localCampaign->target_roas = (float) $roasTarget;
            $localCampaign->save();
        }

        if ($roasTarget !== null && $roasTarget !== '') {
            try {
                app(AdsExperimentService::class)->recordTargetRoasChange(
                    store: $store,
                    channelCampaignId: $campaignId !== null ? (string) $campaignId : null,
                    channelItemId: $localCampaign?->channel_item_id !== null
                        ? (string) $localCampaign->channel_item_id
                        : null,
                    oldTargetRoas: $oldTargetRoas,
                    newTargetRoas: (float) $roasTarget,
                    confounders: $dailyBudget !== null && $dailyBudget !== ''
                        ? ['daily_budget_changed']
                        : [],
                    createdBy: auth()->id(),
                );
            } catch (\Throwable $e) {
                Log::warning('[AdsActionService] failed to record GMS target ROAS experiment', [
                    'store_id' => $store->id,
                    'campaign_id' => $campaignId,
                    'error' => $e->getMessage(),
                ]);
            }
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
