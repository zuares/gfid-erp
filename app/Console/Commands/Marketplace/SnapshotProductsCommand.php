<?php

namespace App\Console\Commands\Marketplace;

use App\Models\MarketplaceProduct;
use App\Models\MarketplaceProductDaily;
use App\Models\MarketplaceProductModel;
use App\Models\MarketplaceProductModelDaily;
use App\Models\Store;
use App\Services\MarketplaceProductService;
use Illuminate\Console\Command;

class SnapshotProductsCommand extends Command
{
    protected $signature = 'marketplace:snapshot-products {--sync : Sync dari Shopee dulu sebelum snapshot}';
    protected $description = 'Simpan snapshot harian katalog produk dan variant untuk analisa historis';

    public function handle(MarketplaceProductService $service): int
    {
        // 1. Optional: refresh data dari Shopee dulu
        if ($this->option('sync')) {
            $stores = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
                ->where('status', 'active')
                ->where('is_active', true) // toko nonaktif dilewati
                ->get();
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
            $productIds = $products->pluck('id');
            // Ambil snapshot kemarin sekali per chunk untuk hitung sales_delta
            $yesterdayMap = MarketplaceProductDaily::whereIn('marketplace_product_id', $productIds)
                ->where('date', $yesterday)
                ->pluck('sales', 'marketplace_product_id');
            $modelsByProduct = MarketplaceProductModel::whereIn('marketplace_product_id', $productIds)
                ->get()
                ->groupBy('marketplace_product_id');
            $yesterdayModelsByProduct = MarketplaceProductModelDaily::whereIn('marketplace_product_id', $productIds)
                ->where('date', $yesterday)
                ->get()
                ->groupBy('marketplace_product_id');

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

                $currentModels = collect($modelsByProduct->get($p->id, []));
                if ($currentModels->isEmpty()) {
                    $currentModels = collect([(object) [
                        'model_id' => '0',
                        'model_name' => null,
                        'model_sku' => $p->item_sku,
                        'price' => $p->price_min,
                        'stock' => $p->stock_total,
                    ]]);
                }
                $currentModelIds = $currentModels
                    ->map(fn ($model) => (string) $model->model_id)
                    ->unique()
                    ->values();

                foreach ($currentModels as $model) {
                    MarketplaceProductModelDaily::updateOrCreate(
                        [
                            'marketplace_product_id' => $p->id,
                            'model_id' => (string) $model->model_id,
                            'date' => $today,
                        ],
                        [
                            'store_id' => $p->store_id,
                            'model_name' => $model->model_name,
                            'model_sku' => $model->model_sku,
                            'price' => $model->price,
                            'stock' => (int) ($model->stock ?? 0),
                            'is_available' => true,
                        ]
                    );
                }

                // Model yang hilang dari response marketplace ditandai tidak
                // tersedia agar tidak terus terbaca aktif secara historis.
                foreach (collect($yesterdayModelsByProduct->get($p->id, []))->whereNotIn('model_id', $currentModelIds) as $missingModel) {
                    MarketplaceProductModelDaily::updateOrCreate(
                        [
                            'marketplace_product_id' => $p->id,
                            'model_id' => (string) $missingModel->model_id,
                            'date' => $today,
                        ],
                        [
                            'store_id' => $p->store_id,
                            'model_name' => $missingModel->model_name,
                            'model_sku' => $missingModel->model_sku,
                            'price' => $missingModel->price,
                            'stock' => (int) ($missingModel->stock ?? 0),
                            'is_available' => false,
                        ]
                    );
                }
                $count++;
            }
        });

        $this->info("Snapshot {$count} produk untuk {$today} tersimpan.");
        return self::SUCCESS;
    }
}
