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
            // Hourly sync cukup kemarin + hari ini (dipanggil tiap jam, jangan berat).
            // Sync harian 7 hari ke belakang: Shopee merevisi angka atribusi
            // (order/GMV) sampai 7 hari — window 3 hari membuat hari ke-4 s/d 7
            // basi dibanding Seller Center.
            $from = $isHourly
                ? now()->subDay()->format('Y-m-d')
                : now()->subDays(7)->format('Y-m-d');
        }
        if (!$to) {
            $to = now()->format('Y-m-d');
        }

        // Batas riwayat Shopee Ads ±6 bulan.
        $minFrom = now()->subDays(180)->format('Y-m-d');
        if ($from < $minFrom) {
            $this->warn("Tanggal mulai {$from} dipangkas ke {$minFrom} (batas riwayat Shopee ±6 bulan).");
            $from = $minFrom;
        }

        $query = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('status', 'active')
            ->where('is_active', true); // toko nonaktif dilewati

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
                // Dedupe: jangan antre chain backfill ganda untuk toko yang sama
                // (klik dobel = 2x ratusan call API yang saling berebut kuota).
                if (! \Illuminate\Support\Facades\Cache::add('shopee-ads-backfill-queued:' . $store->id, 1, 1800)) {
                    $this->warn("Backfill untuk Store {$store->name} sudah ada di antrean — dilewati (coba lagi ±30 menit).");
                    continue;
                }

                // Pecah menjadi per 30 hari untuk dispatch job (batas shopee max 30 hari per request)
                $startDate = Carbon::parse($from);
                $endDate = Carbon::parse($to);
                $jobs = [];
                
                while ($startDate->lte($endDate)) {
                    $chunkEnd = (clone $startDate)->addDays(29);
                    if ($chunkEnd->gt($endDate)) {
                        $chunkEnd = clone $endDate;
                    }
                    
                    // Chunk pertama sync lengkap (balance + campaigns); chunk
                    // berikutnya skipMeta agar tidak mengulang call yang sama.
                    $jobs[] = new ShopeeAdsSyncJob($store, clone $startDate, clone $chunkEnd, $isHourly, count($jobs) > 0);
                    $this->info("Prepared backfill job for Store {$store->name} ({$startDate->toDateString()} to {$chunkEnd->toDateString()})");
                    
                    $startDate->addDays(30);
                }
                
                if (!empty($jobs)) {
                    \Illuminate\Support\Facades\Bus::chain($jobs)->dispatch();

                    // Untuk progress bar di tab Sync: total tahap chain ini.
                    \Illuminate\Support\Facades\Cache::put(
                        'shopee-ads-backfill-progress:' . $store->id,
                        ['total' => count($jobs), 'started' => time()],
                        7200
                    );

                    $this->info("Dispatched backfill chain for Store {$store->name}");
                }
            } else {
                // Execute synchronously (no queue worker needed)
                $this->info("Menjalankan sync langsung untuk Store {$store->name}...");
                
                try {
                    ShopeeAdsSyncJob::dispatchSync($store, Carbon::parse($from), Carbon::parse($to), $isHourly);
                    $this->info("Sync selesai untuk Store {$store->name}.");
                } catch (\Throwable $e) {
                    $this->error("Error sync Store {$store->name}: " . $e->getMessage());
                }
            }
        }

        $this->info("Selesai mengeksekusi marketplace:sync-ads.");
        return self::SUCCESS;
    }
}
