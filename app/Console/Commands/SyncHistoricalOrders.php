<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncHistoricalOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopee:sync-historical-orders {year=2022 : Tahun target untuk mundur ke belakang (contoh: 2022)} {--store=all : ID spesifik dari Store}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menarik histori order masa lalu dari Shopee per 14 hari sampai tahun target tercapai.';

    /**
     * Execute the console command.
     */
    public function handle(MarketplaceSyncService $syncService)
    {
        $targetYear = (int)$this->argument('year');
        $storeId = $this->option('store');

        $query = Store::whereHas('channel', function($q) {
            $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
        })->where('status', 'active');

        if ($storeId !== 'all') {
            $query->where('id', $storeId);
        }

        $stores = $query->get();

        if ($stores->isEmpty()) {
            $this->error("Tidak ada toko Shopee yang ditemukan atau aktif.");
            return;
        }

        $targetDate = Carbon::create($targetYear, 1, 1, 0, 0, 0)->timestamp;
        
        foreach ($stores as $store) {
            $this->info("Memulai sinkronisasi histori Order untuk toko: {$store->name} (ID: {$store->id}) menuju tahun {$targetYear}");
            
            if ($store->connection_status !== 'CONNECTED') {
                $this->warn("Toko {$store->name} perlu login ulang / tidak terhubung. Dilewati.");
                continue;
            }
            
            // Mulai dari hari ini
            $currentEndDate = time();
            
            while ($currentEndDate > $targetDate) {
                // Mundur 14 hari
                $currentStartDate = $currentEndDate - (14 * 86400);
                
                $startFmt = date('Y-m-d', $currentStartDate);
                $endFmt = date('Y-m-d', $currentEndDate);
                
                $this->line("Menarik data rentang: {$startFmt} sampai {$endFmt}...");
                
                try {
                    // Gunakan service sync langsung (sama seperti job)
                    $result = $syncService->syncOrders(
                        $store,
                        $currentStartDate,
                        $currentEndDate,
                        50,
                        false
                    );
                    
                    $new = $result['new'] ?? 0;
                    $updated = $result['updated'] ?? 0;
                    
                    $this->info("✓ Masuk: {$new} baru, {$updated} diperbarui.");
                } catch (\Exception $e) {
                    $this->error("Gagal sinkronisasi rentang {$startFmt} - {$endFmt}: " . $e->getMessage());
                    Log::error("SyncHistoricalOrders Error: " . $e->getMessage());
                }
                
                // Mundur ke periode sebelumnya (kurangi 1 detik dari currentStartDate)
                $currentEndDate = $currentStartDate - 1;
                
                // Beri jeda 1 detik agar tidak terkena rate limit Shopee
                sleep(1);
            }
            
            $this->info("✅ Selesai sinkronisasi histori order toko {$store->name} sampai tahun {$targetYear}!");
        }
        
        $this->info("Semua toko selesai diproses!");
    }
}
