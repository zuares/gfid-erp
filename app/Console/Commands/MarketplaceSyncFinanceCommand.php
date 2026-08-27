<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MarketplaceSyncFinanceCommand extends Command
{
    private const MAX_RUNTIME_SECONDS = 45 * 60;
    private const SETTLEMENT_BATCH_LIMIT = 200;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:sync-finance
                            {--months=1 : Jumlah bulan ke belakang (1-12)}
                            {--days= : Jumlah HARI ke belakang (1-183) — mengalahkan --months jika diisi}
                            {--from= : Tanggal mulai eksplisit (Y-m-d) — mengalahkan --days/--months}
                            {--to= : Tanggal akhir eksplisit (Y-m-d) — mengalahkan --days/--months}
                            {--mode=full : full = tarik ulang semua; missing = cek DB dulu, ambil yang belum ada saja}
                            {--store_id=all : ID Store spesifik atau all}
                            {--channel=all : Filter channel (shopee/tiktok/all)}
                            {--dry-run : Hanya tes, tidak menyimpan ke DB}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menarik data order marketplace untuk 1-3 bulan terakhir dan menyinkronkannya ke keuangan (Settlement)';

    /**
     * Execute the console command.
     */
    public function handle(
        MarketplaceSyncService $syncService,
    ): int
    {
        $months = (int) $this->option('months');
        if ($months < 1 || $months > 12) {
            $this->error("Minimal 1 bulan, maksimal 12 bulan.");
            return self::FAILURE;
        }

        $days = $this->option('days') !== null ? (int) $this->option('days') : null;
        if ($days !== null && ($days < 1 || $days > 183)) {
            $this->error("Opsi --days minimal 1, maksimal 183 (6 bulan).");
            return self::FAILURE;
        }

        $fromOption = $this->option('from');
        $toOption = $this->option('to');
        $explicitFrom = null;
        $explicitTo = null;

        if (($fromOption === null) !== ($toOption === null)) {
            $this->error('Opsi --from dan --to harus diisi berpasangan. Format: Y-m-d.');
            return self::FAILURE;
        }

        if ($fromOption !== null && $toOption !== null) {
            try {
                $fromDate = Carbon::createFromFormat('!Y-m-d', (string) $fromOption);
                $toDate = Carbon::createFromFormat('!Y-m-d', (string) $toOption);
            } catch (\Throwable) {
                $this->error('Format tanggal harus Y-m-d, contoh: 2026-08-02.');
                return self::FAILURE;
            }

            if (! $fromDate || ! $toDate
                || $fromDate->format('Y-m-d') !== (string) $fromOption
                || $toDate->format('Y-m-d') !== (string) $toOption) {
                $this->error('Format tanggal harus Y-m-d, contoh: 2026-08-02.');
                return self::FAILURE;
            }

            if ($fromDate->greaterThan($toDate)) {
                $this->error('Tanggal --from tidak boleh lebih besar dari --to.');
                return self::FAILURE;
            }

            $explicitFrom = $fromDate->copy()->startOfDay()->timestamp;
            $explicitTo = $toDate->isToday()
                ? now()->timestamp
                : $toDate->copy()->endOfDay()->timestamp;
        }

        $mode = strtolower((string) $this->option('mode'));
        if (!in_array($mode, ['full', 'missing'], true)) {
            $this->error("Opsi --mode harus 'full' atau 'missing'.");
            return self::FAILURE;
        }
        $missingOnly = $mode === 'missing';

        $storeId = $this->option('store_id');
        $channel = strtolower($this->option('channel'));
        $dryRun = $this->option('dry-run');

        $query = Store::where('status', 'active')
            ->where('is_active', true); // toko nonaktif dilewati
        if ($storeId !== 'all') {
            $query->where('id', $storeId);
        }
        if ($channel !== 'all') {
            $query->whereHas('channel', function ($q) use ($channel) {
                $q->where('code', 'like', "%{$channel}%");
            });
        }

        $stores = $query->get();

        if ($stores->isEmpty()) {
            $this->error("Tidak ada toko yang cocok/aktif.");
            return self::FAILURE;
        }

        $targetDate = $explicitFrom ?? ($days !== null
            ? now()->subDays($days - 1)->startOfDay()->timestamp   // --days=1 berarti hari ini
            : now()->subMonths($months)->startOfDay()->timestamp);
        $syncEndTimestamp = $explicitTo ?? now()->timestamp;

        $rangeLabel = $explicitFrom !== null
            ? date('Y-m-d', $explicitFrom) . ' sampai ' . date('Y-m-d', $syncEndTimestamp)
            : ($days !== null ? "{$days} hari" : "{$months} bulan");
        $this->info("Mode: " . ($missingOnly ? "MISSING (cek DB dulu, ambil yang belum ada saja)" : "FULL (tarik ulang semua)") . " · Rentang: {$rangeLabel}");

        $totalPulled = 0;
        $totalSyncedToFinance = 0;
        $failureCount = 0;
        $startedAt = microtime(true);

        foreach ($stores as $store) {
            if ((microtime(true) - $startedAt) >= self::MAX_RUNTIME_SECONDS) {
                $this->error('Batas runtime finance sync tercapai; proses dihentikan agar lock tidak kedaluwarsa.');
                $failureCount++;
                break;
            }

            $this->info("--------------------------------------------------");
            $this->info("Memproses Toko: {$store->name} (Channel: {$store->channel->name})");
            $this->info("--------------------------------------------------");

            if ($store->connection_status === 'TOKEN_EXPIRED') {
                if (!$dryRun) {
                    try {
                        $manager = app(\App\Services\Channels\ChannelManager::class);
                        $driver = $manager->driver($store);
                        if (method_exists($driver, 'refreshToken')) {
                            $refreshResult = $driver->refreshToken($store);
                            if (is_array($refreshResult) && ! empty($refreshResult['error'])) {
                                $failureCount++;
                                Log::warning('Token refresh returned an error in sync-finance', [
                                    'store_id' => $store->id,
                                    'error' => $refreshResult['error'],
                                ]);
                            }
                            $store->refresh();
                        }
                    } catch (\Throwable $e) {
                        $failureCount++;
                        Log::warning('Token refresh failed in sync-finance', [
                            'store_id' => $store->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            if ($store->connection_status !== 'CONNECTED') {
                $this->warn("Toko {$store->name} perlu login ulang / tidak terhubung. Dilewati.");
                $failureCount++;
                continue;
            }

            // 1. PULL DATA ORDER DARI API
            $this->info("Mulai menarik data ORDER dari API untuk {$rangeLabel} terakhir...");
            $currentEndDate = $syncEndTimestamp;

            while ($currentEndDate > $targetDate) {
                if ((microtime(true) - $startedAt) >= self::MAX_RUNTIME_SECONDS) {
                    $this->error('Batas runtime finance sync tercapai saat menarik order; proses dihentikan.');
                    $failureCount++;
                    break;
                }

                $currentStartDate = max($targetDate, $currentEndDate - (14 * 86400));

                $startFmt = date('Y-m-d', $currentStartDate);
                $endFmt = date('Y-m-d', $currentEndDate);

                $this->line(" Menarik data order rentang: {$startFmt} sampai {$endFmt}...");
                
                if (!$dryRun) {
                    try {
                        $result = $syncService->syncOrders(
                            $store,
                            $currentStartDate,
                            $currentEndDate,
                            50,
                            false
                        );
                        
                        $new = $result['new'] ?? 0;
                        $updated = $result['updated'] ?? 0;
                        $totalPulled += ($new + $updated);
                        
                        $this->info("   ✓ API Success: {$new} order baru, {$updated} diperbarui.");
                    } catch (\Throwable $e) {
                        $failureCount++;
                        $this->error("   Gagal pull order rentang {$startFmt} - {$endFmt}: " . $e->getMessage());
                    }
                } else {
                    $this->info("   [DRY-RUN] Melewati proses API pull order.");
                }
                
                $currentEndDate = $currentStartDate - 1;
                sleep(1);
            }

            // 2. PULL DATA SETTLEMENT (Keuangan)
            $this->info("Mulai sinkronisasi data ke Keuangan (SETTLEMENT / INCOME)...");

            $channelCode = strtolower((string) ($store->channel?->code ?? ''));
            $settlementSupported = in_array($channelCode, ['shopee', 'shp'], true);

            if (! $settlementSupported) {
                $this->info("   Settlement dilewati: channel {$channelCode} belum mendukung escrow detail.");
            } elseif ($dryRun) {
                $this->info("   [DRY-RUN] Simulasi sinkronisasi settlement dilewati.");
            } else {
                // Jalur ini berbagi service settlement dengan scheduler
                // marketplace:sync-settlements. Gunakan lock yang sama agar
                // finance tidak menarik order settlement yang sama bersamaan.
                $settlementLock = Cache::lock(
                    "sync_settlements_store_{$store->id}",
                    (int) config('marketplace.settlement_lock_ttl', 3600)
                );
                if (! $settlementLock->get()) {
                    $this->warn("   Settlement dilewati: toko {$store->name} sedang diproses auto-sync settlement lain.");
                    $failureCount++;
                } else {
                    $afterId = 0;
                    $batchCount = 1;
                    $storeSettlementSynced = 0;

                    try {
                        while (true) {
                            if ((microtime(true) - $startedAt) >= self::MAX_RUNTIME_SECONDS) {
                                $this->error('Batas runtime finance sync tercapai saat settlement; proses dihentikan.');
                                $failureCount++;
                                break;
                            }

                            $this->line(" Menarik batch settlement ke-{$batchCount}...");

                            try {
                                // Hanya tarik settlement untuk order dalam rentang N bulan
                                $res = $syncService->syncSettlements(
                                    store: $store,
                                    timeFrom: $targetDate,
                                    timeTo: $syncEndTimestamp,
                                    orderSn: null,
                                    // Mode MISSING: hanya order yang settlement-nya belum ada/belum final
                                    // (logika bawaan syncSettlements). Mode FULL: paksa resync semua.
                                    resync: !$missingOnly,
                                    limit: self::SETTLEMENT_BATCH_LIMIT,
                                    afterId: $afterId
                                );

                                $synced = $res['synced'] ?? 0;
                                $skipped = $res['skipped'] ?? 0;
                                $errors = $res['errors'] ?? 0;
                                $storeSettlementSynced += $synced;

                                $this->info("   ✓ Batch {$batchCount}: {$synced} settlement tersinkron (Skipped: {$skipped}, Errors: {$errors}).");

                                $nextAfterId = (int) ($res['last_processed_id'] ?? 0);

                                // Stop if no more records processed or afterId is null
                                if (!$nextAfterId || ($res['processed'] ?? 0) < self::SETTLEMENT_BATCH_LIMIT) {
                                    break;
                                }

                                if ($nextAfterId <= $afterId) {
                                    $this->error('Settlement berhenti karena cursor tidak maju; mencegah loop tanpa akhir.');
                                    $failureCount++;
                                    break;
                                }

                                $afterId = $nextAfterId;

                                $batchCount++;
                                sleep(1); // Mencegah rate limit

                            } catch (\Throwable $e) {
                                $failureCount++;
                                $this->error("   Gagal pull settlement batch {$batchCount}: " . $e->getMessage());
                                break;
                            }
                        }
                    } finally {
                        $settlementLock->release();
                    }

                    $totalSyncedToFinance += $storeSettlementSynced;
                }
            }

            // 3. PULL DATA RETUR / REFUND
            $this->info("Mulai menarik data RETUR / REFUND dari API untuk {$rangeLabel} terakhir...");
            $currentEndDateRetur = $syncEndTimestamp;

            while ($currentEndDateRetur > $targetDate) {
                if ((microtime(true) - $startedAt) >= self::MAX_RUNTIME_SECONDS) {
                    $this->error('Batas runtime finance sync tercapai saat menarik retur; proses dihentikan.');
                    $failureCount++;
                    break;
                }

                $currentStartDateRetur = max($targetDate, $currentEndDateRetur - (14 * 86400));
                
                $startFmt = date('Y-m-d', $currentStartDateRetur);
                $endFmt = date('Y-m-d', $currentEndDateRetur);
                
                $this->line(" Menarik data retur rentang: {$startFmt} sampai {$endFmt}...");
                
                if (!$dryRun) {
                    try {
                        dispatch_sync(new \App\Jobs\SyncMarketplaceReturns($store, $currentStartDateRetur, $currentEndDateRetur));
                        $this->info("   ✓ API Success: Data retur berhasil diproses.");
                    } catch (\Throwable $e) {
                        $failureCount++;
                        $this->error("   Gagal pull retur rentang {$startFmt} - {$endFmt}: " . $e->getMessage());
                    }
                } else {
                    $this->info("   [DRY-RUN] Melewati proses API pull retur.");
                }
                
                $currentEndDateRetur = $currentStartDateRetur - 1;
                sleep(1);
            }

            $this->info("--------------------------------------------------");
        }

        $this->info("Semua proses selesai.");
        $this->info("Total Order ditarik dari API: {$totalPulled}");
        $this->info("Total Settlement ditarik/diperbarui: {$totalSyncedToFinance}");
        if ($failureCount > 0) {
            $this->error("Total kegagalan/skip yang perlu ditindaklanjuti: {$failureCount}");
        }

        return $failureCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
