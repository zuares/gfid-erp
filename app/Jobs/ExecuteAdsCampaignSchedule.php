<?php

namespace App\Jobs;

use App\Models\AdsCampaignSchedule;
use App\Models\MarketplaceAdCampaign;
use App\Services\Channels\Shopee\ShopeeChannel;
use App\Services\Marketplace\Ads\AdsActionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteAdsCampaignSchedule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $scheduleId)
    {
        $this->onQueue('ads');
    }

    public function handle(AdsActionService $actions, ShopeeChannel $shopeeChannel): void
    {
        $schedule = AdsCampaignSchedule::find($this->scheduleId);
        if (! $schedule || ! in_array($schedule->status, ['pending', 'queued'], true)) {
            return;
        }

        $schedule->update(['status' => 'running', 'error_message' => null]);

        try {
            $store = $schedule->store;
            if (! $store) {
                throw new \RuntimeException('Toko campaign tidak ditemukan.');
            }

            $campaignId = (string) $schedule->channel_campaign_id;
            $localCampaign = MarketplaceAdCampaign::query()
                ->where('store_id', $store->id)
                ->where('channel_campaign_id', $campaignId)
                ->first();
            $isGms = str_starts_with($campaignId, 'GMS-')
                || ($localCampaign?->ad_type ?? null) === 'auto';

            if (! $isGms && ! ctype_digit($campaignId)) {
                throw new \RuntimeException('Campaign ID regular tidak valid.');
            }

            $isBudgetChange = $schedule->action === 'budget';
            $dailyBudget = $isBudgetChange ? data_get($schedule->meta, 'daily_budget') : null;
            if ($isBudgetChange && $dailyBudget === null) {
                throw new \RuntimeException('Nilai modal harian pada jadwal tidak ditemukan.');
            }

            $result = $isGms
                ? $actions->actionGmsCampaign($store, $campaignId, null, $dailyBudget, $isBudgetChange ? null : $schedule->action, $shopeeChannel)
                : $actions->actionCpcCampaign($store, (int) $campaignId, null, $dailyBudget, $isBudgetChange ? null : $schedule->action, $shopeeChannel);

            if (($result['status'] ?? null) !== 'success') {
                throw new \RuntimeException($result['message'] ?? 'API Shopee menolak jadwal campaign.');
            }

            $schedule->update([
                'status' => 'completed',
                'executed_at' => now(),
                'meta' => array_merge($schedule->meta ?? [], [
                    'campaign_name' => $localCampaign?->campaign_name,
                    'api_message' => $result['message'] ?? null,
                ]),
            ]);
        } catch (\Throwable $e) {
            $schedule->update([
                'status' => 'failed',
                'executed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
            Log::error('[ExecuteAdsCampaignSchedule] failed', [
                'schedule_id' => $schedule->id,
                'store_id' => $schedule->store_id,
                'campaign_id' => $schedule->channel_campaign_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
