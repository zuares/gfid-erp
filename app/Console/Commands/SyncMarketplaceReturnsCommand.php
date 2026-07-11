<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Jobs\SyncMarketplaceReturns;

class SyncMarketplaceReturnsCommand extends Command
{
    protected $signature = 'marketplace:sync-returns';
    protected $description = 'Sinkronisasi retur dari semua toko marketplace aktif';

    public function handle()
    {
        $this->info('Memulai sinkronisasi retur marketplace...');
        
        $stores = Store::where('is_active', true)->get();
        foreach ($stores as $store) {
            $this->info("Dispatching sync job untuk toko: {$store->name}...");
            SyncMarketplaceReturns::dispatch($store);
        }
        
        $this->info('Semua job sinkronisasi retur berhasil dimasukkan ke antrean.');
    }
}
