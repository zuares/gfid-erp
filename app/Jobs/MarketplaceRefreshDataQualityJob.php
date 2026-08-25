<?php

namespace App\Jobs;

use App\Models\MarketplaceOrder;
use App\Services\Marketplace\MarketplaceFinancialDataQualityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MarketplaceRefreshDataQualityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(
        public string $storeId,
        public bool $dryRun = false,
    ) {
        $this->onQueue('marketplace-quality');
    }

    public function handle(MarketplaceFinancialDataQualityService $quality): void
    {
        MarketplaceOrder::query()
            ->with(['settlement', 'items'])
            ->when($this->storeId !== 'all', fn ($query) => $query->where('store_id', (int) $this->storeId))
            ->orderBy('id')
            ->chunkById(200, function ($orders) use ($quality): void {
                foreach ($orders as $order) {
                    if ($this->dryRun) {
                        $quality->assessOrder($order);
                    } else {
                        $quality->refreshOrder($order);
                    }
                }
            });
    }
}
