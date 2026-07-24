<?php

namespace App\Console\Commands\Marketplace;

use App\Jobs\ShopeeAdsSyncJob;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAdsCommand extends Command
{
    protected $signature = 'marketplace:sync-ads
                            {--store= : ID Toko tertentu (kosong = semua)}
                            {--from= : Tanggal mulai (YYYY-MM-DD)}
                            {--to= : Tanggal akhir (YYYY-MM-DD)}
                            {--hourly : Jalankan sinkronisasi hourly performance}
                            {--backfill : Push ke queue untuk backfill historical}';
                            
    protected $description = 'Sinkronisasi data Shopee Ads (Balance, Campaign, Daily, Hourly)';

    public function handle(): int
    {
        $storeId = $this->option('store');
        $from = $this->option('from');
        $to = $this->option('to');
        $isHourly = $this->option('hourly');
        $isBackfill = $this->option('backfill');

        if (!$from) {
            $from = now()->subDays(3)->format('Y-m-d');
        }
        if (!$to) {
            $to = now()->format('Y-m-d');
        }

        $query = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('status', 'active');

        if ($storeId) {
            $query->where('id', $storeId);
        }

        $stores = $query->get();

        if ($stores->isEmpty()) {
            $this->warn("Tidak ada toko Shopee aktif ditemukan.");
            return self::FAILURE;
        }

        foreach ($stores as $store) {
            if ($isBackfill) {
                // Pecah menjadi per 30 hari untuk dispatch job (batas shopee max 30 hari per request)
                $startDate = Carbon::parse($from);
                $endDate = Carbon::parse($to);
                $jobs = [];
                
                while ($startDate->lte($endDate)) {
                    $chunkEnd = (clone $startDate)->addDays(29);
                    if ($chunkEnd->gt($endDate)) {
                        $chunkEnd = clone $endDate;
                    }
                    
                    $jobs[] = new ShopeeAdsSyncJob($store, clone $startDate, clone $chunkEnd, $isHourly);
                    $this->info("Prepared backfill job for Store {$store->name} ({$startDate->toDateString()} to {$chunkEnd->toDateString()})");
                    
                    $startDate->addDays(30);
                }
                
                if (!empty($jobs)) {
                    \Illuminate\Support\Facades\Bus::chain($jobs)->dispatch();
                    $this->info("Dispatched backfill chain for Store {$store->name}");
                }
            } else {
                // Execute directly synchronously
                $this->info("Menjalankan sync untuk Store {$store->name}");
                
                $syncTypes = ['balance', 'campaigns'];
                if ($isHourly) {
                    $syncTypes[] = 'hourly';
                } else {
                    $syncTypes[] = 'daily';
                }
                
                foreach ($syncTypes as $type) {
                    try {
                        app(\App\Services\Marketplace\Ads\ShopeeAdsSyncService::class)->syncBalance($store, new \App\Models\MarketplaceAdsSyncRun()); // Mocked run for sync command without proper run tracking yet
                        // Real implementation will be within the job or directly called
                        ShopeeAdsSyncJob::dispatchSync($store, Carbon::parse($from), Carbon::parse($to), $isHourly);
                        break; // dispatchSync handles all
                    } catch (\Throwable $e) {
                        $this->error("Error sync {$type} Store {$store->name}: " . $e->getMessage());
                    }
                }
            }
        }

        $this->info("Selesai mengeksekusi marketplace:sync-ads.");
        return self::SUCCESS;
    }
}
