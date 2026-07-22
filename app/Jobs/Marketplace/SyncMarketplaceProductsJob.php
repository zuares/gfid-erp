<?php

namespace App\Jobs\Marketplace;

use App\Models\Store;
use App\Services\MarketplaceProductService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMarketplaceProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout job bisa sangat lama karena request API Shopee.
     */
    public $timeout = 7200;

    public function __construct(public Store $store)
    {
    }

    public function handle(MarketplaceProductService $service): void
    {
        Log::info("Starting Marketplace Product Sync for store: {$this->store->name} ({$this->store->id})");
        $res = $service->syncProducts($this->store);
        
        Log::info("Finished Marketplace Product Sync for store: {$this->store->name}", [
            'synced' => $res['synced'] ?? 0,
            'errors_count' => count($res['errors'] ?? []),
        ]);
        
        if (!empty($res['errors'])) {
            Log::warning("Errors during Marketplace Product Sync for store {$this->store->name}", $res['errors']);
        }
    }
}
