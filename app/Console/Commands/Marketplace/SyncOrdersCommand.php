<?php

namespace App\Console\Commands\Marketplace;

use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:sync-orders {--store= : ID toko spesifik yang ingin diproses} {--dry-run : Hanya simulasi, tidak panggil API atau ubah DB}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marketplace order sync';

    /**
     * Execute the console command.
     */
    public function handle(MarketplaceSyncService $syncService): int
    {
        $this->info('Marketplace order sync');
        $this->line('');

        $query = Store::where('status', 'active')
            ->whereHas('channel', function ($q) {
                $q->where('code', 'shopee');
            });

        if ($this->option('store')) {
            $query->where('id', $this->option('store'));
        }

        $stores = $query->get();

        if ($stores->isEmpty()) {
            if ($this->option('store')) {
                $this->error('Toko tidak ditemukan, tidak aktif, atau bukan Shopee.');
                return self::FAILURE;
            }
            $this->info('Tidak ada toko Shopee aktif.');
            return self::SUCCESS;
        }

        $this->info('Ditemukan: ' . $stores->count() . ' toko Shopee aktif');
        $this->line('');

        $successCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        $timeTo = now()->timestamp;
        $timeFrom = now()->subDays(3)->timestamp; // default seperti controller
        $isDryRun = $this->option('dry-run');

        foreach ($stores as $index => $store) {
            $orderNum = $index + 1;
            $totalStore = $stores->count();
            $this->info("[{$orderNum}/{$totalStore}] {$store->name}");

            // Validasi koneksi
            $status = $store->connection_status;

            if ($status === 'TOKEN_EXPIRED') {
                if (! $isDryRun) {
                    try {
                        if ($store->channel->code === 'shopee') {
                            $shopee = app(\App\Services\Channels\Shopee\ShopeeChannel::class);
                            $shopee->refreshToken($store);
                            $store->refresh();
                            $status = $store->connection_status;
                        }
                    } catch (\Throwable $e) {
                        $status = 'AUTH_REQUIRED';
                        Log::warning('Token refresh failed during auto sync', [
                            'store_id'   => $store->id,
                            'store_name' => $store->name,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }
            }

            if ($status === 'NOT_CONNECTED') {
                $this->line('Status koneksi: Belum terhubung');
                $this->warn('Hasil: Dilewati');
                $this->line('');
                $skippedCount++;
                continue;
            }

            if ($status !== 'CONNECTED') {
                $this->line('Status koneksi: Perlu login ulang');
                $this->warn('Hasil: Dilewati');
                $this->line('');
                $skippedCount++;
                continue;
            }

            $this->line('Status koneksi: Terhubung');

            if ($isDryRun) {
                $this->info('Hasil: Dry-run OK (tidak ada API call)');
                $this->line('');
                $successCount++;
                continue;
            }

            // Lock per toko
            $lockKey = "sync_store_{$store->id}";
            $lock = Cache::lock($lockKey, 240);

            if (! $lock->get()) {
                $this->warn('Hasil: Dilewati (Toko sedang disinkronkan oleh proses lain)');
                $this->line('');
                $skippedCount++;
                continue;
            }

            try {
                $start = microtime(true);

                $result = $syncService->syncOrders(
                    $store,
                    $timeFrom,
                    $timeTo,
                    50,
                    $isDryRun
                );

                $duration = round((microtime(true) - $start) * 1000);
                
                $new = $result['new'] ?? 0;
                $updated = $result['updated'] ?? 0;

                $this->info("Hasil: {$new} order baru, {$updated} diperbarui, 0 gagal");

                Log::info('Marketplace auto sync success', [
                    'store_id'       => $store->id,
                    'store_name'     => $store->name,
                    'shop_id'        => $store->external_shop_id,
                    'duration_ms'    => $duration,
                    'orders_created' => $new,
                    'orders_updated' => $updated,
                    'orders_failed'  => 0,
                    'result'         => 'success'
                ]);

                $successCount++;

            } catch (\Throwable $e) {
                Log::error('Marketplace auto sync failed', [
                    'store_id'   => $store->id,
                    'store_name' => $store->name,
                    'shop_id'    => $store->external_shop_id,
                    'error'      => $e->getMessage(),
                    'result'     => 'failed'
                ]);

                $this->error("Hasil: Gagal ({$e->getMessage()})");
                $failedCount++;
            } finally {
                $lock->release();
            }

            $this->line('');
        }

        $this->info('Selesai');
        $this->line("Berhasil: {$successCount} toko");
        $this->line("Dilewati: {$skippedCount} toko");
        $this->line("Gagal: {$failedCount} toko");

        return $failedCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
