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
    // 10 percobaan: backfill rentang panjang bisa kena 429 berkali-kali;
    // tiap release memakai Retry-After dari Shopee + backoff di bawah.
    public $tries = 10;
    public $backoff = [30, 60, 120, 300, 600];
    
    public function __construct(Store $store, Carbon $dateFrom, Carbon $dateTo, bool $isHourly = false, bool $skipMeta = false)
    {
        $this->store = $store;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->isHourly = $isHourly;
        $this->skipMeta = $skipMeta;
        // Queue prioritas: jangan antre di belakang ratusan job download resi.
        $this->onQueue('ads');
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
    // true = lewati sync balance + campaigns/settings (hemat kuota API —
    // dipakai chunk backfill ke-2 dst yang datanya sama dengan chunk pertama).
    protected bool $skipMeta = false;
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

        // Helper: simpan counter database dan perbarui cache progress UI
        $updateUIProgress = function ($percent, $label) use ($run) {
            $payload = \Illuminate\Support\Facades\Cache::get('marketplace:ads_sync_progress:' . $this->store->id) ?? [];
            
            $totalJobs = $payload['total_jobs'] ?? 1;
            $completedJobs = $payload['completed_jobs'] ?? 0;
            
            // Kalkulasi persentase absolut (seluruh rangkaian job backfill)
            $jobWeight = 100 / max(1, $totalJobs);
            $absolutePercent = ($completedJobs * $jobWeight) + ($percent * $jobWeight / 100);
            
            $newPayload = array_merge($payload, [
                'status'     => 'processing',
                'percent'    => min(100, round($absolutePercent)),
                'label'      => "{$label} (Toko: {$this->store->name})" . ($totalJobs > 1 ? " [Tahap " . ($completedJobs + 1) . "/{$totalJobs}]" : ""),
                'store_id'   => $this->store->id,
                'store_name' => $this->store->name,
                'mode'       => $this->isHourly ? 'hourly' : 'daily',
                'updated_at' => now()->toISOString(),
            ]);
            
            \Illuminate\Support\Facades\Cache::put('marketplace:ads_sync_progress:' . $this->store->id, $newPayload, 1800);
            \Illuminate\Support\Facades\Cache::put('marketplace:ads_sync_progress:all', $newPayload, 1800);
        };
        
        $updateUIProgress(5, "Memulai koneksi");

        try {
            // Hormati cooldown rate-limit toko: jangan buang percobaan selagi
            // Shopee masih menolak. Exception yang sama ditangani blok
            // release/retry di bawah (job menunda diri sesuai sisa cooldown).
            $cooldownUntil = (int) \Illuminate\Support\Facades\Cache::get('shopee-ads-cooldown:' . $this->store->id, 0);
            if ($cooldownUntil > time()) {
                throw new \App\Exceptions\ShopeeAdsRateLimitException(
                    $cooldownUntil - time(),
                    'Menunggu cooldown rate limit Shopee (' . ($cooldownUntil - time()) . ' detik tersisa)'
                );
            }

            // Balance + campaigns/settings TIDAK di-sync pada mode hourly
            // (jalan tiap jam — datanya sudah ditangani sync daily tiap 4 jam;
            // menghemat ±10 call API per jam) dan pada chunk backfill lanjutan.
            if (! $this->isHourly && ! $this->skipMeta) {
                // 1. Sync Balance
                $syncService->syncBalance($this->store, $run);
                $updateUIProgress(15, "Menyinkronkan Saldo");

                // 2. Sync Campaigns and Settings
                $syncService->syncCampaignsAndSettings($this->store, $run);
                $updateUIProgress(30, "Menyinkronkan Kampanye");
            }

            // SAT-SET: chunk historis murni yang datanya sudah LENGKAP tidak
            // ditarik ulang — data lama Shopee tidak berubah. Klik "6 Bulan"
            // saat 3 bulan sudah ada = hanya 3 chunk lama yang benar-benar
            // menembak API; sisanya selesai seketika.
            if (! $this->isHourly && $this->isChunkComplete()) {
                $run->update([
                    'status'        => 'success',
                    'error_message' => 'Dilewati — data rentang ini sudah lengkap (0 call API).',
                    'finished_at'   => now(),
                ]);
                return;
            }

            if ($this->isHourly) {
                // Hourly hanya menerima 1 hari, jadi loop per hari
                $start = clone $this->dateFrom;
                $totalDays = $start->diffInDays($this->dateTo) + 1;
                $currentDay = 1;
                while ($start->lte($this->dateTo)) {
                    $syncService->syncShopHourlyPerformance($this->store, $start->toDateString(), $run);
                    $pct = min(99, 10 + round(($currentDay / $totalDays) * 85));
                    $updateUIProgress($pct, "Menyinkronkan Performa Per Jam (" . $start->format('d/m') . ")");
                    $start->addDay();
                    $currentDay++;
                }
            } else {
                // 3. Sync Shop Daily
                $syncService->syncShopDailyPerformance($this->store, $this->dateFrom->toDateString(), $this->dateTo->toDateString(), $run);
                $updateUIProgress(50, "Menyinkronkan Performa Harian");
                
                // 4. Sync Campaign Daily (CPC)
                $syncService->syncCampaignDailyPerformance($this->store, $this->dateFrom->toDateString(), $this->dateTo->toDateString(), $run);
                $updateUIProgress(80, "Menyinkronkan Performa Iklan CPC");

                // 5. Sync GMS Campaign & Item Daily
                $syncService->syncGmsDailyPerformance($this->store, $this->dateFrom->toDateString(), $this->dateTo->toDateString(), $run);
                $updateUIProgress(95, "Menyinkronkan Performa Iklan Otomatis (GMS)");
            }

            $updateUIProgress(100, "Menyimpan data");
            
            $run->update([
                'status' => 'success',
                'finished_at' => now(),
            ]);
            
            $payload = \Illuminate\Support\Facades\Cache::get('marketplace:ads_sync_progress:' . $this->store->id) ?? [];
            $payload['completed_jobs'] = ($payload['completed_jobs'] ?? 0) + 1;
            
            $payload['stats'] = [
                'inserted' => ($payload['stats']['inserted'] ?? 0) + ($run->total_inserted ?? 0),
                'updated'  => ($payload['stats']['updated'] ?? 0) + ($run->total_updated ?? 0),
                'failed'   => ($payload['stats']['failed'] ?? 0) + ($run->total_failed ?? 0),
            ];
            
            if ($payload['completed_jobs'] >= ($payload['total_jobs'] ?? 1)) {
                $payload['percent'] = 100;
                $payload['status'] = 'success';
                $payload['label'] = 'Semua tahap selesai!';
            } else {
                $payload['status'] = 'processing';
                $payload['label'] = 'Tahap selesai, melanjutkan tahap berikutnya...';
            }
            \Illuminate\Support\Facades\Cache::put('marketplace:ads_sync_progress:' . $this->store->id, $payload, 1800);
            \Illuminate\Support\Facades\Cache::put('marketplace:ads_sync_progress:all', $payload, 1800);

        } catch (\App\Exceptions\ShopeeAdsRateLimitException $e) {
            $run->update([
                'status' => 'rate_limited',
                'error_message' => $e->getMessage(),
                'finished_at' => null, // Not final
            ]);
            
            $payload = \Illuminate\Support\Facades\Cache::get('marketplace:ads_sync_progress:' . $this->store->id) ?? [];
            $payload['status'] = 'processing'; // Keep it processing so it doesn't fail
            $payload['label'] = 'Rate Limit: Menunggu ' . $e->retryAfter . ' detik...';
            \Illuminate\Support\Facades\Cache::put('marketplace:ads_sync_progress:' . $this->store->id, $payload, 1800);
            \Illuminate\Support\Facades\Cache::put('marketplace:ads_sync_progress:all', $payload, 1800);

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
            if (isset($updateUIProgress)) {
                $payload = \Illuminate\Support\Facades\Cache::get('marketplace:ads_sync_progress:' . $this->store->id) ?? [];
                $payload['status'] = 'error';
                $payload['label'] = 'Gagal: ' . substr($e->getMessage(), 0, 50);
                \Illuminate\Support\Facades\Cache::put('marketplace:ads_sync_progress:' . $this->store->id, $payload, 1800);
                \Illuminate\Support\Facades\Cache::put('marketplace:ads_sync_progress:all', $payload, 1800);
            }
            $run->update([
                'status' => 'error',
                'error_message' => substr($e->getMessage(), 0, 1000),
                'finished_at' => now(),
            ]);
            throw $e;
        }
    }

    /**
     * Chunk dianggap lengkap bila: bukan rentang 3 hari terakhir (yang selalu
     * di-refresh), semua tanggal punya baris shop-daily, dan semua hari
     * ber-spend punya baris campaign-daily (kriteria sama dengan audit gap).
     */
    protected function isChunkComplete(): bool
    {
        // Rentang yang menyentuh 7 hari terakhir selalu di-refresh — Shopee
        // merevisi angka atribusi sampai 7 hari ke belakang.
        if ($this->dateTo->gte(now()->subDays(7)->startOfDay())) {
            return false;
        }

        $from = $this->dateFrom->toDateString();
        $to = $this->dateTo->toDateString();
        $daysTotal = abs($this->dateTo->diffInDays($this->dateFrom)) + 1;

        $shopDays = \App\Models\MarketplaceAdsDaily::where('store_id', $this->store->id)
            ->whereBetween('date', [$from, $to])
            ->count();
        if ($shopDays < $daysTotal) {
            return false;
        }

        $spendDays = \App\Models\MarketplaceAdsDaily::where('store_id', $this->store->id)
            ->whereBetween('date', [$from, $to])
            ->where('spend', '>', 0)
            ->pluck('date')
            ->map(fn ($d) => substr((string) $d, 0, 10));

        if ($spendDays->isEmpty()) {
            return true;
        }

        $campDays = \App\Models\MarketplaceAdCampaignDaily::where('store_id', $this->store->id)
            ->whereBetween('date', [$from, $to])
            ->distinct()
            ->pluck('date')
            ->map(fn ($d) => substr((string) $d, 0, 10))
            ->flip();

        foreach ($spendDays as $d) {
            if (! isset($campDays[$d])) {
                return false;
            }
        }

        return true;
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
