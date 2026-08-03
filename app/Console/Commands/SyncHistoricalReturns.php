<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Jobs\SyncMarketplaceReturns;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncHistoricalReturns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopee:sync-historical-returns {year=2022 : Tahun target untuk mundur ke belakang (contoh: 2022)} {--store=all : ID spesifik dari Store}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menarik data retur masa lalu dari Shopee per 14 hari sampai tahun target tercapai.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetYear = (int)$this->argument('year');
        $storeId = $this->option('store');

        $query = Store::whereHas('channel', function($q) {
            $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
        })->where('is_active', true); // toko nonaktif dilewati

        if ($storeId !== 'all') {
            $query->where('id', $storeId);
        }

        $stores = $query->get();

        if ($stores->isEmpty()) {
            $this->error("Tidak ada toko Shopee yang ditemukan.");
            return;
        }

        $targetDate = Carbon::create($targetYear, 1, 1, 0, 0, 0)->timestamp;
        
        foreach ($stores as $store) {
            $this->info("Memulai sinkronisasi histori untuk toko: {$store->name} (ID: {$store->id}) menuju tahun {$targetYear}");
            
            // Mulai dari hari ini
            $currentEndDate = time();
            
            while ($currentEndDate > $targetDate) {
                // Mundur 14 hari
                $currentStartDate = $currentEndDate - (14 * 86400);
                
                $startFmt = date('Y-m-d', $currentStartDate);
                $endFmt = date('Y-m-d', $currentEndDate);
                
                $this->line("Menarik data rentang: {$startFmt} sampai {$endFmt}...");
                
                try {
                    // Gunakan dispatch_sync agar script menunggu job selesai sebelum lanjut mundur
                    dispatch_sync(new SyncMarketplaceReturns($store, $currentStartDate, $currentEndDate));
                } catch (\Exception $e) {
                    $this->error("Gagal sinkronisasi rentang {$startFmt} - {$endFmt}: " . $e->getMessage());
                    Log::error("SyncHistoricalReturns Error: " . $e->getMessage());
                }
                
                // Mundur ke periode sebelumnya (kurangi 1 detik dari currentStartDate)
                $currentEndDate = $currentStartDate - 1;
                
                // Beri jeda 1 detik agar tidak terkena rate limit Shopee
                sleep(1);
            }
            
            $this->info("✅ Selesai sinkronisasi histori toko {$store->name} sampai tahun {$targetYear}!");
        }
        
        $this->info("Semua toko selesai diproses!");
    }
}
