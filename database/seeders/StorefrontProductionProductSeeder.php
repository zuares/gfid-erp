<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\StorefrontProduct;
use App\Models\StorefrontProductCategory;
use App\Models\StorefrontProductSize;
use App\Models\StorefrontProductVariant;
use App\Models\StorefrontVariantItemMapping;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StorefrontProductionProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $categories = $this->seedCategories();

            foreach ($this->products($categories) as $payload) {
                $this->seedProduct($payload);
            }
        });
    }

    private function seedCategories(): array
    {
        $categories = [
            'celana' => ['name' => 'Celana', 'sort_order' => 3],
            'jaket' => ['name' => 'Jaket', 'sort_order' => 1],
            'hoodie' => ['name' => 'Hoodie', 'sort_order' => 2],
            'kaos' => ['name' => 'Kaos', 'sort_order' => 4],
        ];

        $ids = [];
        foreach ($categories as $slug => $data) {
            $category = StorefrontProductCategory::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'sort_order' => $data['sort_order'],
                    'is_active' => true,
                ]
            );

            $ids[$slug] = $category->id;
        }

        return $ids;
    }

    private function seedProduct(array $payload): void
    {
        $product = StorefrontProduct::updateOrCreate(
            ['slug' => $payload['slug']],
            [
                'name' => $payload['name'],
                'description' => $payload['description'],
                'product_type' => $payload['product_type'],
                'base_price' => $payload['base_price'],
                'label' => $payload['label'] ?? null,
                'image_url' => $payload['image_url'] ?? null,
                'is_published' => $payload['is_published'] ?? true,
                'sort_order' => $payload['sort_order'] ?? 0,
                'category_id' => $payload['category_id'],
                'audience' => $payload['audience'] ?? 'unisex',
            ]
        );

        $variants = [];
        foreach ($payload['colors'] as $index => $color) {
            $variants[$color['key']] = StorefrontProductVariant::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'color_name' => $color['name'],
                    'size_label' => null,
                ],
                [
                    'hex_color' => $color['hex_color'] ?? null,
                    'image_url' => $color['image_url'] ?? ($payload['image_url'] ?? null),
                    'price_override' => $color['price_override'] ?? null,
                    'stock_override' => null,
                    'is_default' => $index === 0,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }

        $sizes = [];
        foreach ($payload['sizes'] as $index => $size) {
            $label = is_array($size) ? $size['label'] : $size;
            $sizes[$label] = StorefrontProductSize::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'size_label' => $label,
                ],
                [
                    'price_override' => is_array($size) ? ($size['price_override'] ?? null) : null,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }

        foreach ($payload['mappings'] as $row) {
            $variant = $variants[$row['color']] ?? null;
            $size = $sizes[$row['size']] ?? null;

            if (! $variant || ! $size) {
                $this->command?->warn("Skip mapping {$payload['slug']} {$row['color']} / {$row['size']}: warna/ukuran tidak ditemukan.");
                continue;
            }

            $item = Item::where('code', $row['item_code'])->first();
            if (! $item) {
                $this->command?->warn("Skip mapping {$payload['slug']} {$row['color']} / {$row['size']}: item {$row['item_code']} tidak ditemukan.");
                continue;
            }

            StorefrontVariantItemMapping::updateOrCreate(
                [
                    'variant_id' => $variant->id,
                    'size_id' => $size->id,
                ],
                [
                    'product_id' => $product->id,
                    'item_id' => $item->id,
                    'price_override' => $row['price_override'] ?? null,
                    'stock_override' => null,
                ]
            );
        }

        $this->command?->info("Seeded storefront product: {$product->name}");
    }

    private function products(array $categories): array
    {
        $joggerImage = 'https://images.unsplash.com/photo-1569032915512-922c2e506c51?w=900&h=1100&fit=crop&auto=format&q=85';

        return [
            [
                'slug' => 'gf-jogger-pendek-basic',
                'name' => 'GF Jogger Pendek Basic',
                'description' => 'Jogger pendek basic dengan potongan santai, ringan, dan nyaman dipakai untuk aktivitas harian.',
                'product_type' => 'jumbo',
                'base_price' => 135000,
                'label' => 'Ready',
                'image_url' => $joggerImage,
                'is_published' => true,
                'sort_order' => 10,
                'category_id' => $categories['celana'],
                'audience' => 'pria',
                'colors' => [
                    ['key' => 'hitam', 'name' => 'Hitam', 'hex_color' => '#0a0a0a', 'image_url' => $joggerImage],
                    ['key' => 'navy', 'name' => 'Navy', 'hex_color' => '#1c2b4a', 'image_url' => $joggerImage],
                    ['key' => 'misty', 'name' => 'Misty', 'hex_color' => '#878787', 'image_url' => $joggerImage],
                    ['key' => 'abu-tua', 'name' => 'Abu Tua', 'hex_color' => '#4b5563', 'image_url' => $joggerImage],
                    ['key' => 'putih', 'name' => 'Putih', 'hex_color' => '#f8fafc', 'image_url' => $joggerImage],
                    ['key' => 'baby-blue', 'name' => 'Baby Blue', 'hex_color' => '#93c5fd', 'image_url' => $joggerImage],
                ],
                'sizes' => [
                    ['label' => 'L', 'price_override' => 125000],
                    ['label' => 'XL', 'price_override' => 130000],
                    ['label' => '3XL', 'price_override' => 135000],
                    ['label' => '5XL', 'price_override' => 145000],
                    ['label' => '7XL', 'price_override' => 155000],
                ],
                'mappings' => [
                    ['color' => 'hitam', 'size' => 'L', 'item_code' => 'K1BLK'],
                    ['color' => 'hitam', 'size' => 'XL', 'item_code' => 'K2BLK'],
                    ['color' => 'hitam', 'size' => '3XL', 'item_code' => 'K3BLK'],
                    ['color' => 'hitam', 'size' => '5XL', 'item_code' => 'K5BLK'],
                    ['color' => 'hitam', 'size' => '7XL', 'item_code' => 'K7BLK'],
                    ['color' => 'navy', 'size' => 'L', 'item_code' => 'K1NVY'],
                    ['color' => 'navy', 'size' => 'XL', 'item_code' => 'K2NVY'],
                    ['color' => 'navy', 'size' => '3XL', 'item_code' => 'K3NVY'],
                    ['color' => 'navy', 'size' => '5XL', 'item_code' => 'K5NVY'],
                    ['color' => 'navy', 'size' => '7XL', 'item_code' => 'K7NVY'],
                    ['color' => 'misty', 'size' => 'L', 'item_code' => 'K1MST'],
                    ['color' => 'misty', 'size' => 'XL', 'item_code' => 'K2MST'],
                    ['color' => 'misty', 'size' => '3XL', 'item_code' => 'K3MST'],
                    ['color' => 'misty', 'size' => '5XL', 'item_code' => 'K5MST'],
                    ['color' => 'misty', 'size' => '7XL', 'item_code' => 'K7MST'],
                    ['color' => 'abu-tua', 'size' => 'L', 'item_code' => 'K1ABT'],
                    ['color' => 'abu-tua', 'size' => 'XL', 'item_code' => 'K2ABT'],
                    ['color' => 'abu-tua', 'size' => '3XL', 'item_code' => 'K3ABT'],
                    ['color' => 'abu-tua', 'size' => '5XL', 'item_code' => 'K5ABT'],
                    ['color' => 'abu-tua', 'size' => '7XL', 'item_code' => 'K7ABT'],
                    ['color' => 'putih', 'size' => 'L', 'item_code' => 'K1WHT'],
                    ['color' => 'putih', 'size' => 'XL', 'item_code' => 'K2WHT'],
                    ['color' => 'putih', 'size' => '3XL', 'item_code' => 'K3WHT'],
                    ['color' => 'putih', 'size' => '5XL', 'item_code' => 'K5WHT'],
                    ['color' => 'putih', 'size' => '7XL', 'item_code' => 'K7WHT'],
                    ['color' => 'baby-blue', 'size' => 'L', 'item_code' => 'K1BBL'],
                    ['color' => 'baby-blue', 'size' => 'XL', 'item_code' => 'K2BBL'],
                    ['color' => 'baby-blue', 'size' => '3XL', 'item_code' => 'K3BBL'],
                    ['color' => 'baby-blue', 'size' => '5XL', 'item_code' => 'K5BBL'],
                    ['color' => 'baby-blue', 'size' => '7XL', 'item_code' => 'K7BBL'],
                ],
            ],
        ];
    }
}
