<?php

namespace App\Console\Commands\Marketplace;

use App\Models\MarketplaceProduct;
use App\Models\MarketplaceProductDaily;
use App\Models\Store;
use App\Services\MarketplaceProductService;
use Illuminate\Console\Command;

class SnapshotProductsCommand extends Command
{
    protected $signature = 'marketplace:snapshot-products {--sync : Sync dari Shopee dulu sebelum snapshot}';
    protected $description = 'Simpan snapshot harian metrik produk (stok, harga, terjual) untuk analisa tren';

    public function handle(MarketplaceProductService $service): int
    {
        // 1. Optional: refresh data dari Shopee dulu
        if ($this->option('sync')) {
            $stores = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
                ->where('status', 'active')->get();
            foreach ($stores as $store) {
                $res = $service->syncProducts($store);
                $this->info("[{$store->name}] sync {$res['synced']} produk" . ($res['errors'] ? ' (ada error, cek log)' : ''));
            }
        }

        // 2. Snapshot dari DB lokal (murah — tidak panggil API)
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $count = 0;

        MarketplaceProduct::query()->chunkById(500, function ($products) use ($today, $yesterday, &$count) {
            // Ambil snapshot kemarin sekali per chunk untuk hitung sales_delta
            $yesterdayMap = MarketplaceProductDaily::whereIn('marketplace_product_id', $products->pluck('id'))
                ->where('date', $yesterday)
                ->pluck('sales', 'marketplace_product_id');

            foreach ($products as $p) {
                $prevSales = $yesterdayMap[$p->id] ?? null;
                MarketplaceProductDaily::updateOrCreate(
                    ['marketplace_product_id' => $p->id, 'date' => $today],
                    [
                        'store_id'    => $p->store_id,
                        'item_status' => $p->item_status,
                        'price_min'   => $p->price_min,
                        'price_max'   => $p->price_max,
                        'stock_total' => $p->stock_total,
                        'sales'       => $p->sales,
                        'sales_delta' => ($p->sales !== null && $prevSales !== null) ? max(0, $p->sales - $prevSales) : null,
                        'views'       => $p->views,
                        'rating_star' => $p->rating_star,
                    ]
                );
                $count++;
            }
        });

        $this->info("Snapshot {$count} produk untuk {$today} tersimpan.");
        return self::SUCCESS;
    }
}
