<?php

namespace App\Console\Commands\Marketplace;

use App\Jobs\Marketplace\SyncMarketplaceProductsJob;
use App\Models\MarketplaceProduct;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SwapTikTokCatalogCommand extends Command
{
    protected $signature = 'marketplace:swap-tiktok-catalog
        {first_store_id : ID toko TikTok yang katalog lokalnya berada di toko kedua}
        {second_store_id : ID toko TikTok yang katalog lokalnya berada di toko pertama}
        {--confirm : Terapkan pertukaran snapshot katalog (default hanya dry-run)}
        {--queue-sync : Antrekan full-sync produk kedua toko setelah pertukaran berhasil}';

    protected $description = 'Tukar kepemilikan snapshot katalog dua toko TikTok yang tertukar, secara transaksional.';

    /**
     * Tables whose redundant store_id must follow the product ownership.
     * Related models/images/logs without store_id follow marketplace_product_id
     * automatically and therefore require no update.
     *
     * @var array<int,string>
     */
    private const PRODUCT_STORE_TABLES = [
        'marketplace_product_dailies',
        'marketplace_product_model_daily',
        'marketplace_boost_schedules',
        'marketplace_boost_pool',
        'marketplace_boost_logs',
    ];

    public function handle(): int
    {
        $firstStoreId = (int) $this->argument('first_store_id');
        $secondStoreId = (int) $this->argument('second_store_id');

        if ($firstStoreId <= 0 || $secondStoreId <= 0 || $firstStoreId === $secondStoreId) {
            $this->error('Masukkan dua ID toko TikTok yang berbeda.');

            return self::FAILURE;
        }

        $stores = Store::query()
            ->with('channel:id,code,name')
            ->whereIn('id', [$firstStoreId, $secondStoreId])
            ->get()
            ->keyBy('id');
        $firstStore = $stores->get($firstStoreId);
        $secondStore = $stores->get($secondStoreId);

        if (! $firstStore || ! $secondStore) {
            $this->error('Salah satu toko tidak ditemukan.');

            return self::FAILURE;
        }

        foreach ([$firstStore, $secondStore] as $store) {
            $channel = strtolower((string) $store->channel?->code);
            if (! in_array($channel, ['tiktok', 'ttk', 'tt'], true)) {
                $this->error("Store #{$store->id} bukan toko TikTok Shop.");

                return self::FAILURE;
            }

            if (! $store->is_active || strtolower((string) $store->status) !== 'active' || $store->connection_status !== 'CONNECTED') {
                $this->error("Store #{$store->id} tidak aktif atau koneksinya tidak valid.");

                return self::FAILURE;
            }
        }

        $firstProducts = MarketplaceProduct::query()
            ->where('store_id', $firstStore->id)
            ->orderBy('id')
            ->get(['id', 'item_id', 'item_name']);
        $secondProducts = MarketplaceProduct::query()
            ->where('store_id', $secondStore->id)
            ->orderBy('id')
            ->get(['id', 'item_id', 'item_name']);
        $overlappingItemIds = $firstProducts->pluck('item_id')
            ->intersect($secondProducts->pluck('item_id'))
            ->values();

        $this->table(
            ['Store', 'External shop ID', 'Snapshot produk', 'Tujuan setelah repair'],
            [
                [$this->storeLabel($firstStore), $firstStore->external_shop_id ?: '—', $firstProducts->count(), $this->storeLabel($secondStore)],
                [$this->storeLabel($secondStore), $secondStore->external_shop_id ?: '—', $secondProducts->count(), $this->storeLabel($firstStore)],
            ]
        );

        if ($firstProducts->isEmpty() || $secondProducts->isEmpty()) {
            $this->error('Kedua toko harus memiliki snapshot produk sebelum dapat ditukar.');

            return self::FAILURE;
        }

        if ($overlappingItemIds->isNotEmpty()) {
            $this->error('Pertukaran dibatalkan: ditemukan item_id yang ada di kedua toko. Tidak ada data diubah.');
            $this->line('Contoh item_id bentrok: '.$overlappingItemIds->take(10)->implode(', '));

            return self::FAILURE;
        }

        if (! $this->option('confirm')) {
            $this->warn('DRY-RUN: tidak ada data yang diubah. Jalankan ulang dengan --confirm setelah memverifikasi tabel di atas.');

            return self::SUCCESS;
        }

        $backupPath = $this->backupCatalogs($firstStore, $secondStore, $firstProducts, $secondProducts);
        $firstProductIds = $firstProducts->pluck('id')->all();
        $secondProductIds = $secondProducts->pluck('id')->all();

        DB::transaction(function () use ($firstStore, $secondStore, $firstProductIds, $secondProductIds): void {
            $this->moveDependentStoreIds($firstProductIds, $secondStore->id);
            $this->moveDependentStoreIds($secondProductIds, $firstStore->id);

            MarketplaceProduct::query()
                ->whereIn('id', $firstProductIds)
                ->update(['store_id' => $secondStore->id]);
            MarketplaceProduct::query()
                ->whereIn('id', $secondProductIds)
                ->update(['store_id' => $firstStore->id]);
        });

        $this->info("Snapshot katalog berhasil ditukar. Backup privat: {$backupPath}");

        if ($this->option('queue-sync')) {
            SyncMarketplaceProductsJob::dispatch($firstStore);
            SyncMarketplaceProductsJob::dispatch($secondStore);
            $this->info('Full-sync produk untuk kedua toko telah diantrikan.');
        } else {
            $this->warn('Full-sync belum dijalankan. Setelah hasil swap diverifikasi, jalankan ulang dengan --queue-sync atau sinkronkan kedua toko satu per satu.');
        }

        return self::SUCCESS;
    }

    /** @param array<int,int> $productIds */
    private function moveDependentStoreIds(array $productIds, int $destinationStoreId): void
    {
        foreach (self::PRODUCT_STORE_TABLES as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)
                    ->whereIn('marketplace_product_id', $productIds)
                    ->update(['store_id' => $destinationStoreId]);
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int,MarketplaceProduct>  $firstProducts
     * @param  \Illuminate\Support\Collection<int,MarketplaceProduct>  $secondProducts
     */
    private function backupCatalogs(Store $firstStore, Store $secondStore, $firstProducts, $secondProducts): string
    {
        $timestamp = now()->format('Ymd_His');
        $path = "marketplace-catalog-repairs/tiktok-catalog-swap-{$firstStore->id}-{$secondStore->id}-{$timestamp}.json";
        $payload = [
            'created_at' => now()->toISOString(),
            'operation' => 'swap_tiktok_catalog',
            'stores' => [
                $this->backupStore($firstStore, $firstProducts),
                $this->backupStore($secondStore, $secondProducts),
            ],
        ];

        Storage::disk('local')->put(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );

        return $path;
    }

    /** @param \Illuminate\Support\Collection<int,MarketplaceProduct> $products */
    private function backupStore(Store $store, $products): array
    {
        return [
            'store_id' => $store->id,
            'store_name' => $store->name,
            'external_shop_id' => $store->external_shop_id,
            'products' => MarketplaceProduct::query()
                ->whereIn('id', $products->pluck('id'))
                ->with('models')
                ->get()
                ->map(fn (MarketplaceProduct $product): array => $product->toArray())
                ->all(),
        ];
    }

    private function storeLabel(Store $store): string
    {
        return "#{$store->id} {$store->name}";
    }
}
