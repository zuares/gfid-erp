<?php

namespace App\Jobs;

use App\Models\MarketplaceAdsSyncRun;
use App\Models\Store;
use App\Services\Marketplace\Ads\ShopeeAdsSyncService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ShopeeAdsSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 minutes
    public $tries = 5;
    public $backoff = [30, 60, 120, 300, 600];
    
    public function __construct(Store $store, Carbon $dateFrom, Carbon $dateTo, bool $isHourly = false)
    {
        $this->store = $store;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->isHourly = $isHourly;
    }

    public function middleware()
    {
        return [
            (new WithoutOverlapping("shopee-ads-store:{$this->store->id}"))
                ->expireAfter(2100)
                ->releaseAfter(60)
        ];
    }

    protected Store $store;
    protected Carbon $dateFrom;
    protected Carbon $dateTo;
    protected bool $isHourly;
    public ?int $syncRunId = null;



    public function handle(ShopeeAdsSyncService $syncService)
    {
        if ($this->syncRunId) {
            $run = MarketplaceAdsSyncRun::find($this->syncRunId);
        }

        if (!isset($run) || !$run) {
            $run = MarketplaceAdsSyncRun::create([
                'store_id' => $this->store->id,
                'sync_type' => $this->isHourly ? 'hourly_all' : 'daily_all',
                'date_from' => $this->dateFrom->toDateString(),
                'date_to' => $this->dateTo->toDateString(),
                'status' => 'processing',
                'started_at' => now(),
            ]);
            $this->syncRunId = $run->id;
        } else {
            $run->update([
                'status' => 'processing',
                'started_at' => now(),
                'finished_at' => null,
            ]);
        }

        // Helper: simpan counter setelah setiap step agar tidak hilang kalau crash
        $saveProgress = function () use ($run) {
            $run->save();
        };

        try {
            // 1. Sync Balance
            $syncService->syncBalance($this->store, $run);
            $saveProgress();
            
            // 2. Sync Campaigns and Settings
            $syncService->syncCampaignsAndSettings($this->store, $run);
            $saveProgress();

            if ($this->isHourly) {
                // Hourly hanya menerima 1 hari, jadi loop per hari
                $start = clone $this->dateFrom;
                while ($start->lte($this->dateTo)) {
                    $syncService->syncShopHourlyPerformance($this->store, $start->toDateString(), $run);
                    $saveProgress();
                    $start->addDay();
                }
            } else {
                // 3. Sync Shop Daily
                $syncService->syncShopDailyPerformance($this->store, $this->dateFrom->toDateString(), $this->dateTo->toDateString(), $run);
                $saveProgress();
                
                // 4. Sync Campaign Daily (CPC)
                $syncService->syncCampaignDailyPerformance($this->store, $this->dateFrom->toDateString(), $this->dateTo->toDateString(), $run);
                $saveProgress();

                // 5. Sync GMS Campaign & Item Daily
                $syncService->syncGmsDailyPerformance($this->store, $this->dateFrom->toDateString(), $this->dateTo->toDateString(), $run);
                $saveProgress();
            }

            $run->update([
                'status' => 'success',
                'finished_at' => now(),
            ]);

        } catch (\App\Exceptions\ShopeeAdsRateLimitException $e) {
            $run->update([
                'status' => 'rate_limited',
                'error_message' => $e->getMessage(),
                'finished_at' => null, // Not final
            ]);

            // release() hanya bekerja dalam konteks queue worker.
            // Saat dispatchSync() (tanpa queue), gunakan sleep + retry manual.
            try {
                $this->release($e->retryAfter);
            } catch (\Throwable) {
                // Fallback untuk dispatchSync: log dan throw agar caller tahu
                \Illuminate\Support\Facades\Log::warning(
                    "[ShopeeAdsSync] Rate limited (sync mode). Retry after {$e->retryAfter}s. " . $e->getMessage()
                );
                throw $e;
            }
            return;
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'error',
                'error_message' => substr($e->getMessage(), 0, 1000),
                'finished_at' => now(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        if ($this->syncRunId) {
            $run = MarketplaceAdsSyncRun::find($this->syncRunId);
            if ($run && $run->status !== 'success') {
                $run->update([
                    'status' => 'error',
                    'error_message' => substr($exception->getMessage(), 0, 1000),
                    'finished_at' => now(),
                ]);
            }
        }
    }
}
