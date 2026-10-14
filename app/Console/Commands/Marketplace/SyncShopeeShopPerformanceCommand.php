<?php

namespace App\Console\Commands\Marketplace;

use App\Models\Store;
use App\Services\Marketplace\ShopeeShopPerformanceSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncShopeeShopPerformanceCommand extends Command
{
    protected $signature = 'marketplace:sync-shopee-performance
        {--store= : ID toko Shopee spesifik}
        {--from= : Tanggal mulai (Y-m-d)}
        {--to= : Tanggal akhir (Y-m-d), default kemarin}
        {--granularity=day : day, customize, week, month, quarter, atau year}
        {--timezone=GMT+7 : GMT+7, GMT+8, atau GMT-3}
        {--currency=LOCAL : LOCAL atau USD}';

    protected $description = 'Sinkronisasi performa penjualan toko Shopee dari Brand Portal API.';

    public function handle(ShopeeShopPerformanceSyncService $syncService): int
    {
        try {
            $to = Carbon::createFromFormat('Y-m-d', (string) ($this->option('to') ?: now('Asia/Jakarta')->subDay()->toDateString()))->startOfDay();
            $from = Carbon::createFromFormat('Y-m-d', (string) ($this->option('from') ?: $to->copy()->subDays(6)->toDateString()))->startOfDay();
        } catch (\Throwable) {
            $this->error('Format tanggal harus Y-m-d.');
            return self::FAILURE;
        }

        if ($from->greaterThan($to)) {
            $this->error('Tanggal --from tidak boleh lebih besar dari --to.');
            return self::FAILURE;
        }

        if ($to->greaterThanOrEqualTo(now('Asia/Jakarta')->startOfDay())) {
            $this->error('Tanggal --to harus sebelum hari ini karena data hari berjalan belum final.');
            return self::FAILURE;
        }

        $granularity = strtolower((string) ($this->option('granularity') ?: 'day'));
        $timezone = (string) ($this->option('timezone') ?: 'GMT+7');
        $currency = strtoupper((string) ($this->option('currency') ?: 'LOCAL'));
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
            $lock = Cache::lock("marketplace:shopee_performance_sync:{$store->id}", 3600);
            if (! $lock->get()) {
                $this->warn("{$store->name}: dilewati karena sync sedang berjalan.");
                continue;
            }

            try {
                $result = $granularity === 'day'
                    ? $syncService->syncDailyRange($store, $from, $to, $timezone, $currency)
                    : $syncService->syncPeriod($store, $from, $to, $granularity, $timezone, $currency);

                $this->info(sprintf(
                    '%s: saved=%d periods=%d',
                    $store->name,
                    $result['saved'] ?? 0,
                    $result['periods'] ?? 1,
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
