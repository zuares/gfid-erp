<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Store;

class SyncAdCampaignsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900; // 15 menit
    public $tries = 1;

    protected $store;
    protected $dateFrom;
    protected $dateTo;

    public function __construct(Store $store, $dateFrom, $dateTo)
    {
        $this->store = $store;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function handle()
    {
        app(\App\Services\MarketplaceSyncService::class)->syncAdCampaigns($this->store, $this->dateFrom, $this->dateTo);
    }
}
