<?php

namespace App\Services\Marketplace\Ads;

use App\Models\MarketplaceAdCampaign;
use App\Models\Store;
use App\Services\Channels\Shopee\ShopeeChannel;
use Carbon\Carbon;
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

    public function actionGmsItem(
        Store $store,
        int $itemId,
        string $action,
        ShopeeChannel $shopeeChannel,
        ?Carbon $periodFrom = null,
        ?Carbon $periodTo = null,
        bool $confirmExistingAds = false,
    ): array
    {
        if ($action === 'add') {
            $periodFrom ??= now()->startOfDay();
            $periodTo ??= now()->endOfDay();

            $activeRegularAds = MarketplaceAdCampaign::query()
                ->where('store_id', $store->id)
                ->where('channel_item_id', $itemId)
                ->where(function ($query) {
                    $query->whereNull('ad_type')
                        ->orWhereNotIn('ad_type', ['auto', 'gms', 'gmv_max']);
                })
                ->where(function ($query) {
                    $query->whereIn('campaign_status', ['ongoing', 'normal'])
                        ->orWhere(function ($fallback) {
                            $fallback->whereNull('campaign_status')
                                ->whereIn('status', ['ongoing', 'normal', 'ONGOING', 'NORMAL']);
                        });
                })
                ->where(function ($query) use ($periodTo) {
                    $query->whereNull('started_at')
                        ->orWhere('started_at', '<=', $periodTo);
                })
                ->where(function ($query) use ($periodFrom) {
                    $query->whereNull('ended_at')
                        ->orWhere('ended_at', '>=', $periodFrom);
                })
                ->get([
                    'id',
                    'channel_campaign_id',
                    'campaign_name',
                    'campaign_status',
                    'status',
                    'started_at',
                    'ended_at',
                ]);

            if ($activeRegularAds->isNotEmpty() && ! $confirmExistingAds) {
                return [
                    'status' => 'warning',
                    'code' => 'existing_regular_ads',
                    'requires_confirmation' => true,
                    'message' => 'Kamu memiliki Iklan yang sedang berlangsung untuk produk ini selama periode waktu yang sama. Mengaktifkan fitur GMV Max akan menjeda Iklan tersebut.',
                    'campaigns' => $activeRegularAds->map(fn ($campaign) => [
                        'id' => $campaign->id,
                        'channel_campaign_id' => $campaign->channel_campaign_id,
                        'name' => $campaign->campaign_name ?: 'Campaign ' . $campaign->channel_campaign_id,
                        'status' => $campaign->campaign_status ?: $campaign->status,
                        'started_at' => $campaign->started_at?->toIso8601String(),
                        'ended_at' => $campaign->ended_at?->toIso8601String(),
                    ])->values()->all(),
                    'http_status' => 409,
                ];
            }
        }

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
        $localCampaign = MarketplaceAdCampaign::where('store_id', $store->id)
            ->where('channel_campaign_id', (string) $campaignId)
            ->first();
        $oldTargetRoas = $localCampaign?->target_roas !== null
            ? (float) $localCampaign->target_roas
            : null;

        $experimentGuard = $this->activeExperimentGuard(
            $store,
            (string) $campaignId,
            $localCampaign?->channel_item_id !== null ? (string) $localCampaign->channel_item_id : null,
            $roasTarget !== null && $roasTarget !== ''
                || $dailyBudget !== null && $dailyBudget !== ''
                || $statusAction !== null,
        );
        if ($experimentGuard) {
            return $experimentGuard;
        }

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

        if ($statusAction === 'stop') {
            // Shopee tidak menyediakan endpoint stop terpisah. Hentikan
            // campaign dengan mengisi end_date melalui endpoint edit yang
            // sama; API menerima tanggal DD-MM-YYYY.
            $resStop = $shopeeChannel->editManualProductAds(
                $store,
                $campaignId,
                'change_end_date',
                ['end_date' => now()->format('d-m-Y')]
            );
            if (! empty($resStop['error'])) {
                return [
                    'status' => 'error',
                    'message' => 'Gagal menghentikan iklan: ' . ($resStop['message'] ?? $resStop['error']),
                    'http_status' => 400,
                ];
            }
            $results[] = 'Status (Dihentikan)';
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
            } elseif ($statusAction === 'stop') {
                $localCampaign->campaign_status = 'closed';
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
        ?string $statusAction,
        ShopeeChannel $shopeeChannel
    ): array {
        if (is_string($campaignId) && str_starts_with($campaignId, 'GMS-')) {
            $campaignId = null;
        }

        $results = [];
        $localCampaignQuery = MarketplaceAdCampaign::where('store_id', $store->id);
        if ($campaignId !== null && $campaignId !== '') {
            $localCampaignQuery->where('channel_campaign_id', (string) $campaignId);
        } else {
            $localCampaignQuery->whereLike('channel_campaign_id', 'GMS-%');
        }
        $localCampaign = $localCampaignQuery->first();
        $oldTargetRoas = $localCampaign?->target_roas !== null
            ? (float) $localCampaign->target_roas
            : null;

        $experimentGuard = $this->activeExperimentGuard(
            $store,
            $campaignId !== null ? (string) $campaignId : null,
            $localCampaign?->channel_item_id !== null ? (string) $localCampaign->channel_item_id : null,
            $roasTarget !== null && $roasTarget !== ''
                || $dailyBudget !== null && $dailyBudget !== ''
                || $statusAction !== null,
        );
        if ($experimentGuard) {
            return $experimentGuard;
        }

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

        if ($statusAction === 'pause' || $statusAction === 'resume') {
            $params = [];
            if ($campaignId) {
                $params['campaign_id'] = (int) $campaignId;
            }
            $resStatus = $shopeeChannel->editGmsProductCampaign($store, $statusAction, $params);
            if (! empty($resStatus['error'])) {
                return [
                    'status' => 'error',
                    'message' => 'Gagal ubah status: ' . ($resStatus['message'] ?? $resStatus['error']),
                    'http_status' => 400,
                ];
            }
            $results[] = 'Status (' . ucfirst($statusAction) . ')';
        }

        if ($statusAction === 'stop') {
            $params = ['end_date' => now()->format('d-m-Y')];
            if ($campaignId) {
                $params['campaign_id'] = (int) $campaignId;
            }
            $resStop = $shopeeChannel->editGmsProductCampaign($store, 'change_end_date', $params);
            if (! empty($resStop['error'])) {
                return [
                    'status' => 'error',
                    'message' => 'Gagal menghentikan iklan: ' . ($resStop['message'] ?? $resStop['error']),
                    'http_status' => 400,
                ];
            }
            $results[] = 'Status (Dihentikan)';
        }

        if (empty($results)) {
            return [
                'status' => 'error',
                'message' => 'Tidak ada data yang diubah.',
                'http_status' => 400,
            ];
        }

        // Simpan nilai terakhir secara lokal agar kolom Target ROAS dan Modal
        // di Ads Dashboard langsung konsisten setelah aksi Shopee berhasil.
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
            } elseif ($statusAction === 'stop') {
                $localCampaign->campaign_status = 'closed';
            }
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

    private function activeExperimentGuard(
        Store $store,
        ?string $channelCampaignId,
        ?string $channelItemId,
        bool $hasChanges,
    ): ?array {
        if (! $hasChanges) {
            return null;
        }

        $experiments = app(AdsExperimentService::class);
        if ($experiments->sameDayExperimentForScope($store, $channelCampaignId, $channelItemId)) {
            // Same-day edits are folded into the existing experiment record.
            return null;
        }

        $active = $experiments->activeExperimentForScope($store, $channelCampaignId, $channelItemId);
        if (! $active) {
            return null;
        }

        $effectiveDate = $active->effective_date?->toDateString() ?? '-';

        return [
            'status' => 'error',
            'message' => 'Experiment aktif untuk scope ini sejak ' . $effectiveDate . '. Tunggu observation selesai sebelum mengubah target ROAS, modal, atau status campaign.',
            'http_status' => 409,
            'experiment_id' => (int) $active->id,
        ];
    }
}
