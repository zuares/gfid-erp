<?php

namespace App\Console\Commands\Marketplace;

use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Sinkronisasi bertahap nilai estimated_escrow_amount untuk order pending.
 * Tidak mengubah final_income/settlement_time yang sudah tersimpan.
 */
class SyncIncomeDetailsCommand extends Command
{
    protected $signature = 'marketplace:sync-income-details
        {--store= : ID toko Shopee spesifik}
        {--status=2 : Income status Shopee (2 = Pending)}
        {--page-size=100 : Jumlah record per halaman (1-100)}';

    protected $description = 'Sinkronisasi estimated_escrow_amount dari Shopee get_income_detail untuk order pending.';

    public function handle(MarketplaceSyncService $syncService): int
    {
        $pageSize = (int) $this->option('page-size');
        if ($pageSize < 1 || $pageSize > 100) {
            $this->error('Opsi --page-size harus di antara 1 dan 100.');
            return self::FAILURE;
        }

        $incomeStatus = (int) $this->option('status');
        if ($incomeStatus < 0 || $incomeStatus > 2) {
            $this->error('Opsi --status harus 0, 1, atau 2.');
            return self::FAILURE;
        }

        $storeId = $this->option('store');
        $stores = Store::where('status', 'active')
            ->where('is_active', true)
            ->whereHas('channel', fn ($q) => $q->whereIn('code', ['shopee', 'shp']))
            ->when($storeId, fn ($q) => $q->whereKey((int) $storeId))
            ->get();

        if ($stores->isEmpty()) {
            $this->info('Tidak ada toko Shopee aktif yang cocok.');
            return self::SUCCESS;
        }

        $failed = 0;
        foreach ($stores as $store) {
            $lock = Cache::lock("sync_settlements_store_{$store->id}", (int) config('marketplace.settlement_lock_ttl', 3600));
            if (! $lock->get()) {
                $this->warn("{$store->name}: dilewati karena sedang ada sync settlement lain.");
                continue;
            }

            try {
                $result = $syncService->syncIncomeDetails($store, $incomeStatus, $pageSize);
                $this->line(sprintf(
                    '%s: found=%d updated=%d created=%d unmatched=%d pages=%d',
                    $store->name,
                    $result['found'],
                    $result['updated'],
                    $result['created'],
                    $result['unmatched'],
                    $result['pages'],
                ));
                if (($result['errors'] ?? 0) > 0) {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("{$store->name}: {$e->getMessage()}");
            } finally {
                $lock->release();
            }
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
