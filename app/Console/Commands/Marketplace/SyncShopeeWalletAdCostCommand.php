<?php

namespace App\Console\Commands\Marketplace;

use App\Models\Store;
use App\Services\Marketplace\Ads\ShopeeWalletAdCostSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncShopeeWalletAdCostCommand extends Command
{
    protected $signature = 'marketplace:sync-shopee-ad-wallet
        {--store= : ID toko Shopee spesifik}
        {--from= : Tanggal mulai (Y-m-d)}
        {--to= : Tanggal akhir (Y-m-d)}
        {--page-size=100 : Jumlah data per halaman (1-100)}';

    protected $description = 'Sinkronisasi biaya iklan aktual dari wallet Shopee (transaction type 450/451).';

    public function handle(ShopeeWalletAdCostSyncService $syncService): int
    {
        $pageSize = (int) $this->option('page-size');
        if ($pageSize < 1 || $pageSize > 100) {
            $this->error('Opsi --page-size harus di antara 1 dan 100.');
            return self::FAILURE;
        }

        try {
            $from = Carbon::createFromFormat('Y-m-d', (string) ($this->option('from') ?: now()->subDays(2)->toDateString()))->startOfDay();
            $to = Carbon::createFromFormat('Y-m-d', (string) ($this->option('to') ?: now()->toDateString()))->endOfDay();
        } catch (\Throwable) {
            $this->error('Format tanggal harus Y-m-d.');
            return self::FAILURE;
        }

        if ($to->lt($from)) {
            $this->error('Tanggal --to tidak boleh lebih kecil dari --from.');
            return self::FAILURE;
        }

        $storeId = $this->option('store');
        $stores = Store::query()
            ->where('status', 'active')
            ->where('is_active', true)
            ->whereHas('channel', fn ($q) => $q->whereIn('code', ['shopee', 'shp', 'SHOPEE']))
            ->when($storeId, fn ($q) => $q->whereKey((int) $storeId))
            ->orderBy('id')
            ->get();

        if ($stores->isEmpty()) {
            $this->info('Tidak ada toko Shopee aktif yang cocok.');
            return self::SUCCESS;
        }

        $failed = 0;
        foreach ($stores as $store) {
            $lock = Cache::lock("marketplace:ad_wallet_sync:{$store->id}", 1800);
            if (! $lock->get()) {
                $this->warn("{$store->name}: dilewati karena sync sedang berjalan.");
                continue;
            }

            try {
                $result = $syncService->sync($store, $from, $to, $pageSize);
                $this->line(sprintf(
                    '%s: created=%d updated=%d skipped=%d pages=%d requests=%d',
                    $store->name,
                    $result['created'],
                    $result['updated'],
                    $result['skipped'],
                    $result['pages'],
                    $result['requests'],
                ));
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
