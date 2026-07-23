<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MarketplaceSyncFinanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:sync-finance 
                            {--months=1 : Jumlah bulan ke belakang (1-3)} 
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
    public function handle(MarketplaceSyncService $syncService): int
    {
        $months = (int) $this->option('months');
        if ($months < 1 || $months > 12) {
            $this->error("Minimal 1 bulan, maksimal 12 bulan.");
            return self::FAILURE;
        }

        $storeId = $this->option('store_id');
        $channel = strtolower($this->option('channel'));
        $dryRun = $this->option('dry-run');

        $query = Store::where('status', 'active');
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

        $targetDate = now()->subMonths($months)->startOfDay()->timestamp;

        $totalPulled = 0;
        $totalSyncedToFinance = 0;

        foreach ($stores as $store) {
            $this->info("--------------------------------------------------");
            $this->info("Memproses Toko: {$store->name} (Channel: {$store->channel->name})");
            $this->info("--------------------------------------------------");

            if ($store->connection_status === 'TOKEN_EXPIRED') {
                if (!$dryRun) {
                    try {
                        $manager = app(\App\Services\Channels\ChannelManager::class);
                        $driver = $manager->driver($store);
                        if (method_exists($driver, 'refreshToken')) {
                            $driver->refreshToken($store);
                            $store->refresh();
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Token refresh failed in sync-finance', [
                            'store_id' => $store->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            if ($store->connection_status !== 'CONNECTED') {
                $this->warn("Toko {$store->name} perlu login ulang / tidak terhubung. Dilewati.");
                continue;
            }

            // 1. PULL DATA ORDER DARI API 
            $this->info("Mulai menarik data ORDER dari API untuk {$months} bulan terakhir...");
            $currentEndDate = time();
            
            while ($currentEndDate > $targetDate) {
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
                    } catch (\Exception $e) {
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

            if ($dryRun) {
                $this->info("   [DRY-RUN] Simulasi sinkronisasi settlement dilewati.");
            } else {
                $afterId = 0;
                $batchCount = 1;
                $storeSettlementSynced = 0;

                while (true) {
                    $this->line(" Menarik batch settlement ke-{$batchCount}...");

                    try {
                        // Hanya tarik settlement untuk order dalam rentang N bulan
                        $res = $syncService->syncSettlements(
                            store: $store,
                            timeFrom: $targetDate,
                            timeTo: time(),
                            orderSn: null,
                            resync: true, // Paksa resync untuk mengecek settlement terbaru
                            limit: 200,
                            afterId: $afterId
                        );

                        $synced = $res['synced'] ?? 0;
                        $skipped = $res['skipped'] ?? 0;
                        $errors = $res['errors'] ?? 0;
                        $storeSettlementSynced += $synced;

                        $this->info("   ✓ Batch {$batchCount}: {$synced} settlement tersinkron (Skipped: {$skipped}, Errors: {$errors}).");

                        $afterId = $res['last_processed_id'] ?? null;

                        // Stop if no more records processed or afterId is null
                        if (!$afterId || ($res['processed'] ?? 0) < 200) {
                            break;
                        }

                        $batchCount++;
                        sleep(1); // Mencegah rate limit

                    } catch (\Exception $e) {
                        $this->error("   Gagal pull settlement batch {$batchCount}: " . $e->getMessage());
                        break;
                    }
                }
                
                $totalSyncedToFinance += $storeSettlementSynced;
            }

            // 3. PULL DATA RETUR / REFUND
            $this->info("Mulai menarik data RETUR / REFUND dari API untuk {$months} bulan terakhir...");
            $currentEndDateRetur = time();
            
            while ($currentEndDateRetur > $targetDate) {
                $currentStartDateRetur = max($targetDate, $currentEndDateRetur - (14 * 86400));
                
                $startFmt = date('Y-m-d', $currentStartDateRetur);
                $endFmt = date('Y-m-d', $currentEndDateRetur);
                
                $this->line(" Menarik data retur rentang: {$startFmt} sampai {$endFmt}...");
                
                if (!$dryRun) {
                    try {
                        dispatch_sync(new \App\Jobs\SyncMarketplaceReturns($store, $currentStartDateRetur, $currentEndDateRetur));
                        $this->info("   ✓ API Success: Data retur berhasil diproses.");
                    } catch (\Exception $e) {
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

        return self::SUCCESS;
    }
}
