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
    protected $signature = 'marketplace:sync-orders {--store= : ID toko spesifik yang ingin diproses} {--days=3 : Rentang hari ke belakang yang ditarik (1-365)} {--dry-run : Hanya simulasi, tidak panggil API atau ubah DB}';

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
            ->where('is_active', true) // toko nonaktif dilewati: jangan tarik/refresh token
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

        $days = max(1, min(365, (int) $this->option('days'))); // clamp 1-365 hari (dipecah per 14 hari di service)
        $timeTo = now()->timestamp;
        $timeFrom = now()->subDays($days)->timestamp;
        $isDryRun = $this->option('dry-run');

        if ($days > 3) {
            $this->line("Rentang: {$days} hari terakhir");
            $this->line('');
        }

        foreach ($stores as $index => $store) {
            $orderNum = $index + 1;
            $totalStore = $stores->count();
            $this->info("[{$orderNum}/{$totalStore}] {$store->name}");

            // Validasi koneksi — refresh token bila kedaluwarsa lewat helper terpusat
            // di service (dulu blok refresh yang sama terduplikasi di command ini).
            if (! $isDryRun && $store->connection_status === 'TOKEN_EXPIRED') {
                $syncService->ensureStoreConnected($store);
            }
            $status = $store->connection_status;

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

            $progressKey = "marketplace:sync_progress:{$store->id}";

            // ── Rentang panjang (>14 hari): per-jendela lewat service ─────────
            // Lock diambil-dilepas PER JENDELA di syncOrdersWindowed() — satu lock
            // TTL 240 dtk akan kedaluwarsa di tengah rentang panjang dan membuka
            // celah tabrakan dengan sync 5-menit.
            if ($days > 14) {
                try {
                    Cache::put($progressKey, [
                        'percent' => 2, 'label' => 'Memulai…', 'status' => 'running',
                        'store' => $store->name, 'ts' => now()->timestamp,
                    ], 1800);

                    $totals = $syncService->syncOrdersWindowed(
                        $store, $timeFrom, $timeTo, 50, 'update_time', false,
                        function (int $i, int $total, int $wFrom, int $wTo, ?array $result, ?\Throwable $error) use ($progressKey, $store) {
                            Cache::put($progressKey, [
                                'percent' => (int) round(($i / max(1, $total)) * 98),
                                'label'   => 'Periode ' . $i . '/' . $total . ' (' . date('d M', $wFrom) . ' – ' . date('d M Y', $wTo) . ')' . ($error ? ' — gagal, lanjut' : ''),
                                'status'  => 'running', 'store' => $store->name, 'ts' => now()->timestamp,
                            ], 1800);
                        }
                    );

                    Cache::put($progressKey, [
                        'percent' => 100,
                        'label'   => "Selesai · {$totals['new']} baru, {$totals['updated']} update",
                        'status'  => 'done', 'store' => $store->name,
                        'new' => $totals['new'], 'updated' => $totals['updated'], 'ts' => now()->timestamp,
                    ], 300);

                    $this->info("Hasil: {$totals['new']} order baru, {$totals['updated']} diperbarui"
                        . ($totals['failed'] ? ", {$totals['failed']} periode gagal" : '')
                        . ($totals['skipped_locked'] ? ", {$totals['skipped_locked']} periode dilewati (lock)" : ''));

                    Log::info('Marketplace long-range sync done', [
                        'store_id' => $store->id, 'store_name' => $store->name, 'days' => $days,
                    ] + $totals);

                    $successCount++;
                } catch (\Throwable $e) {
                    Log::error('Marketplace long-range sync failed', [
                        'store_id' => $store->id, 'store_name' => $store->name, 'error' => $e->getMessage(),
                    ]);
                    Cache::put($progressKey, [
                        'percent' => 100, 'label' => 'Gagal: ' . $e->getMessage(),
                        'status' => 'error', 'store' => $store->name, 'ts' => now()->timestamp,
                    ], 300);
                    $this->error("Hasil: Gagal ({$e->getMessage()})");
                    $failedCount++;
                }

                $this->line('');
                continue;
            }

            // ── Rentang pendek (<=14 hari): sekali jalan dengan satu lock ─────
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

                Cache::put($progressKey, [
                    'percent' => 2, 'label' => 'Memulai…', 'status' => 'running',
                    'store' => $store->name, 'ts' => now()->timestamp,
                ], 1800);

                $result = $syncService->syncOrders(
                    $store,
                    $timeFrom,
                    $timeTo,
                    50,
                    $isDryRun,
                    function (int $percent, string $label) use ($progressKey, $store) {
                        Cache::put($progressKey, [
                            'percent' => $percent, 'label' => $label, 'status' => 'running',
                            'store' => $store->name, 'ts' => now()->timestamp,
                        ], 1800);
                    }
                );

                $duration = round((microtime(true) - $start) * 1000);

                $new = $result['new'] ?? 0;
                $updated = $result['updated'] ?? 0;

                Cache::put($progressKey, [
                    'percent' => 100,
                    'label'   => "Selesai · {$new} baru, {$updated} update",
                    'status'  => 'done', 'store' => $store->name,
                    'new' => $new, 'updated' => $updated, 'ts' => now()->timestamp,
                ], 300);

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

                Cache::put($progressKey, [
                    'percent' => 100, 'label' => 'Gagal: ' . $e->getMessage(),
                    'status' => 'error', 'store' => $store->name, 'ts' => now()->timestamp,
                ], 300);

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
