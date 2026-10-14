<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceProductImage;
use App\Models\MarketplaceProductModel;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedMarketplaceProductsUiDummy extends Command
{
    private const SOURCE = 'marketplace_products_ui_v1';

    protected $signature = 'marketplace:seed-products-ui-dummy
                            {--store= : ID toko Shopee yang dipakai untuk data demo}
                            {--reset : Hapus dummy produk ini sebelum membuat ulang}';

    protected $description = '[DEV ONLY] Buat dummy produk, varian, dan gambar untuk preview UI marketplace.';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Command ini hanya boleh dijalankan di lokal/testing.');

            return self::FAILURE;
        }

        $store = $this->resolveStore();
        if (! $store) {
            $this->error('Toko Shopee lokal tidak ditemukan dan gagal dibuat.');

            return self::FAILURE;
        }

        $existingIds = MarketplaceProduct::query()
            ->whereJsonContains('raw_json->dummy_source', self::SOURCE)
            ->pluck('id');

        if ($existingIds->isNotEmpty() && ! $this->option('reset')) {
            $this->error('Dummy produk sudah ada. Jalankan ulang dengan --reset jika ingin menggantinya.');

            return self::FAILURE;
        }

        $products = $this->productRows();

        DB::transaction(function () use ($existingIds, $products, $store): void {
            if ($existingIds->isNotEmpty()) {
                MarketplaceProductImage::whereIn('marketplace_product_id', $existingIds)->delete();
                MarketplaceProductModel::whereIn('marketplace_product_id', $existingIds)->delete();
                MarketplaceProduct::whereKey($existingIds)->delete();
            }

            foreach ($products as $index => $row) {
                $this->createProduct($store, $row, $index + 1);
            }
        });

        $this->info(sprintf(
            '✅ %d dummy produk dibuat untuk toko "%s" (ID %s).',
            count($products),
            $store->name,
            $store->id,
        ));
        $this->line('Data ini hanya untuk UI lokal dan tidak membuat order, promosi, iklan, atau campaign.');
        $this->line('Buka: /marketplace/products');

        return self::SUCCESS;
    }

    private function resolveStore(): ?Store
    {
        if ($this->option('store')) {
            return Store::find($this->option('store'));
        }

        $store = Store::query()
            ->where('is_active', true)
            ->whereHas('channel', fn ($query) => $query->whereIn('code', ['SHP', 'SHOPEE']))
            ->first();

        if ($store) {
            return $store;
        }

        $channel = Channel::query()->firstOrCreate(
            ['code' => 'SHP'],
            ['name' => 'Shopee', 'status' => 'active', 'meta' => null]
        );

        return Store::query()->firstOrCreate(
            ['code' => 'SHP-UI-DUMMY'],
            [
                'name' => 'Shopee UI Dummy',
                'channel_id' => $channel->id,
                'is_active' => true,
                'status' => 'active',
                'external_shop_id' => null,
            ]
        );
    }

    private function createProduct(Store $store, array $row, int $sequence): void
    {
        $models = $row['models'];
        $prices = array_column($models, 'price');
        $stocks = array_column($models, 'stock');
        $imageBase = 'https://placehold.co/320x320/e2e8f0/334155?text='.rawurlencode($row['short']);

        $product = MarketplaceProduct::create([
            'store_id' => $store->id,
            'item_id' => $row['item_id'],
            'item_name' => $row['name'],
            'item_sku' => $row['sku'],
            'item_status' => $row['status'],
            'category_id' => $row['category_id'],
            'image_url' => $imageBase,
            'price_min' => min($prices),
            'price_max' => max($prices),
            'stock_total' => array_sum($stocks),
            'has_model' => count($models) > 1 || ($models[0]['model_id'] ?? '0') !== '0',
            'sales' => $row['sales'],
            'views' => $row['views'],
            'rating_star' => $row['rating'],
            'raw_json' => [
                'dummy_source' => self::SOURCE,
                'is_dummy' => true,
                'sequence' => $sequence,
                'description' => $row['description'],
            ],
            'synced_at' => now()->subMinutes($sequence * 7),
        ]);

        foreach ($models as $model) {
            MarketplaceProductModel::create([
                'marketplace_product_id' => $product->id,
                'model_id' => (string) $model['model_id'],
                'model_name' => $model['name'],
                'model_sku' => $model['sku'],
                'price' => $model['price'],
                'stock' => $model['stock'],
                'raw_json' => [
                    'dummy_source' => self::SOURCE,
                    'price_info' => [[
                        'original_price' => $model['price'],
                        'current_price' => $model['price'],
                    ]],
                ],
            ]);
        }

        foreach ([$imageBase, $this->imageUrl($row['short'], $row['image_tone'])] as $sort => $imageUrl) {
            MarketplaceProductImage::create([
                'marketplace_product_id' => $product->id,
                'shopee_image_id' => sprintf('dummy-ui-%02d-%d', $sequence, $sort + 1),
                'image_url' => $imageUrl,
                'sort_order' => $sort,
                'is_cover' => $sort === 0,
                'status' => 'active',
                'raw_json' => ['dummy_source' => self::SOURCE, 'is_dummy' => true],
            ]);
        }
    }

    private function imageUrl(string $label, string $tone): string
    {
        return 'https://placehold.co/320x320/'.$tone.'/ffffff?text='.rawurlencode($label);
    }

    private function productRows(): array
    {
        return [
            $this->row('DUMMY-UI-001', 'Kaos Basic Premium Cotton', 'DUMMY-KAOS', 'NORMAL', 'Kaos', 'e0f2fe', 89000, 32, 126, 2400, 4.8, [['model_id' => '0', 'name' => 'Default', 'sku' => 'DUMMY-KAOS', 'price' => 89000, 'stock' => 32]]),
            $this->row('DUMMY-UI-002', 'Hoodie Fleece Essential', 'DUMMY-HOODIE', 'NORMAL', 'Hoodie', 'ede9fe', 179000, 16, 84, 1800, 4.7, [
                ['model_id' => '201', 'name' => 'Black', 'sku' => 'DUMMY-HOODIE-BLK', 'price' => 179000, 'stock' => 12],
                ['model_id' => '202', 'name' => 'Beige', 'sku' => 'DUMMY-HOODIE-BGE', 'price' => 185000, 'stock' => 4],
                ['model_id' => '203', 'name' => 'Olive', 'sku' => 'DUMMY-HOODIE-OLV', 'price' => 179000, 'stock' => 0],
            ]),
            $this->row('DUMMY-UI-003', 'Celana Cargo Relaxed', 'DUMMY-CARGO', 'NORMAL', 'Cargo', 'dcfce7', 149000, 34, 61, 1200, 4.6, [
                ['model_id' => '301', 'name' => 'S', 'sku' => 'DUMMY-CARGO-S', 'price' => 149000, 'stock' => 5],
                ['model_id' => '302', 'name' => 'M', 'sku' => 'DUMMY-CARGO-M', 'price' => 149000, 'stock' => 18],
                ['model_id' => '303', 'name' => 'L', 'sku' => 'DUMMY-CARGO-L', 'price' => 155000, 'stock' => 9],
                ['model_id' => '304', 'name' => 'XL', 'sku' => 'DUMMY-CARGO-XL', 'price' => 155000, 'stock' => 2],
            ]),
            $this->row('DUMMY-UI-004', 'Totebag Canvas Daily', 'DUMMY-TOTE', 'UNLIST', 'Totebag', 'fef3c7', 59000, 18, 22, 500, 4.5, [['model_id' => '0', 'name' => 'Default', 'sku' => 'DUMMY-TOTE', 'price' => 59000, 'stock' => 18]]),
            $this->row('DUMMY-UI-005', 'Sneakers Urban Mono', 'DUMMY-SNEAKER', 'BANNED', 'Sneakers', 'fee2e2', 249000, 3, 9, 900, 4.2, [
                ['model_id' => '501', 'name' => '40', 'sku' => 'DUMMY-SNEAKER-40', 'price' => 249000, 'stock' => 0],
                ['model_id' => '502', 'name' => '41', 'sku' => 'DUMMY-SNEAKER-41', 'price' => 249000, 'stock' => 3],
                ['model_id' => '503', 'name' => '42', 'sku' => 'DUMMY-SNEAKER-42', 'price' => 259000, 'stock' => 0],
            ]),
            $this->row('DUMMY-UI-006', 'Topi Twill Signature', null, 'NORMAL', 'Topi', 'f1f5f9', 79000, 0, 12, 400, 4.3, [['model_id' => '0', 'name' => 'Default', 'sku' => null, 'price' => 79000, 'stock' => 0]]),
            $this->row('DUMMY-UI-007', 'Kemeja Linen Weekend', 'DUMMY-LINEN', 'NORMAL', 'Kemeja', 'ffedd5', 129000, 64, 310, 4200, 4.9, [['model_id' => '0', 'name' => 'Default', 'sku' => 'DUMMY-LINEN', 'price' => 129000, 'stock' => 64]]),
            $this->row('DUMMY-UI-008', 'Sling Bag Mini Utility', 'DUMMY-SLING', 'UNLIST', 'Sling Bag', 'dbeafe', 99000, 7, 45, 1100, 4.4, [
                ['model_id' => '801', 'name' => 'Black', 'sku' => 'DUMMY-SLING-BLK', 'price' => 99000, 'stock' => 7],
                ['model_id' => '802', 'name' => 'Stone', 'sku' => 'DUMMY-SLING-STN', 'price' => 109000, 'stock' => 0],
            ]),
        ];
    }

    private function row(string $itemId, string $name, ?string $sku, string $status, string $short, string $tone, int $price, int $stock, int $sales, int $views, float $rating, array $models): array
    {
        return [
            'item_id' => $itemId,
            'name' => $name,
            'sku' => $sku,
            'status' => $status,
            'category_id' => 'DUMMY-CAT',
            'short' => $short,
            'image_tone' => $tone,
            'sales' => $sales,
            'views' => $views,
            'rating' => $rating,
            'description' => $name.' — data demo lokal untuk menguji workflow listing marketplace.',
            'models' => $models,
        ];
    }
}
