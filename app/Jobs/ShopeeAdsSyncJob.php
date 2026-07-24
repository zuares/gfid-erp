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
        $this->onQueue('shopee-ads');
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

        try {
            // 1. Sync Balance
            $syncService->syncBalance($this->store, $run);
            
            // 2. Sync Campaigns and Settings
            $syncService->syncCampaignsAndSettings($this->store, $run);

            if ($this->isHourly) {
                // Hourly hanya menerima 1 hari, jadi loop per hari
                $start = clone $this->dateFrom;
                while ($start->lte($this->dateTo)) {
                    $syncService->syncShopHourlyPerformance($this->store, $start->toDateString(), $run);
                    $start->addDay();
                }
            } else {
                // 3. Sync Shop Daily
                $syncService->syncShopDailyPerformance($this->store, $this->dateFrom->toDateString(), $this->dateTo->toDateString(), $run);
                
                // 4. Sync Campaign Daily
                $syncService->syncCampaignDailyPerformance($this->store, $this->dateFrom->toDateString(), $this->dateTo->toDateString(), $run);
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

            // Release back to queue with delay
            $this->release($e->retryAfter);
            return;
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
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
