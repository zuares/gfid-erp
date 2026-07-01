<?php

namespace Database\Seeders;

use App\Models\StorefrontProductCategory;
use App\Models\StorefrontProduct;
use App\Models\StorefrontProductVariant;
use App\Models\StorefrontProductSize;
use Illuminate\Database\Seeder;

class StorefrontProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // ── Bersihkan data lama (safe to re-run) ──────────────────────────────
        StorefrontProductSize::query()->delete();
        StorefrontProductVariant::query()->delete();
        StorefrontProduct::query()->delete();
        StorefrontProductCategory::query()->delete();

        $this->command->info('');
        $this->command->info('── Seeding Kategori ─────────────────────────────────');

        // ── 4 Kategori ────────────────────────────────────────────────────────
        $kategoris = [
            ['slug' => 'jaket',  'name' => 'Jaket',  'sort_order' => 1],
            ['slug' => 'hoodie', 'name' => 'Hoodie', 'sort_order' => 2],
            ['slug' => 'celana', 'name' => 'Celana', 'sort_order' => 3],
            ['slug' => 'kaos',   'name' => 'Kaos',   'sort_order' => 4],
        ];

        $cats = [];
        foreach ($kategoris as $k) {
            $cat = StorefrontProductCategory::create([
                'slug'       => $k['slug'],
                'name'       => $k['name'],
                'sort_order' => $k['sort_order'],
                'is_active'  => true,
            ]);
            $cats[$k['slug']] = $cat->id;
            $this->command->info("  [OK] {$k['name']}");
        }

        $this->command->info('');
        $this->command->info('── Seeding Produk ───────────────────────────────────');

        // ── Produk ────────────────────────────────────────────────────────────
        // Format sizes: array string  → harga sama (null override)
        //               array assoc  → ['label'=>..., 'price_override'=>...]
        $products = [

            // ════════════════════════════════════════════════════
            // JAKET
            // ════════════════════════════════════════════════════

            // 1. Track Jacket — Pria — Regular
            [
                'product' => [
                    'slug'         => 'gf-track-jacket',
                    'name'         => 'GF Track Jacket',
                    'description'  => 'Jaket training ringan bahan polyester premium, full zipper. Cocok untuk gym dan aktivitas outdoor harian.',
                    'product_type' => 'regular',
                    'base_price'   => 149000,
                    'label'        => 'Best Seller',
                    'is_published' => true,
                    'sort_order'   => 1,
                    'category_id'  => $cats['jaket'],
                    'audience'     => 'pria',
                ],
                'variants' => [
                    ['color_name' => 'Navy',  'hex_color' => '#1c2b4a', 'is_default' => true,  'image_url' => 'https://images.unsplash.com/photo-1768983953826-231e8ef0b6dc?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Black', 'hex_color' => '#0a0a0a', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Olive', 'hex_color' => '#3a4a2b', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=600&h=600&fit=crop&auto=format&q=80'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
            ],

            // 2. Track Jacket — Pria — Jumbo
            [
                'product' => [
                    'slug'         => 'gf-track-jacket-jumbo',
                    'name'         => 'GF Track Jacket Jumbo',
                    'description'  => 'Jaket training ringan bahan polyester premium, full zipper. Tersedia ukuran big size 3XL–6XL.',
                    'product_type' => 'jumbo',
                    'base_price'   => 169000,
                    'label'        => 'Big Size',
                    'is_published' => true,
                    'sort_order'   => 2,
                    'category_id'  => $cats['jaket'],
                    'audience'     => 'pria',
                ],
                'variants' => [
                    ['color_name' => 'Navy',  'hex_color' => '#1c2b4a', 'is_default' => true,  'image_url' => 'https://images.unsplash.com/photo-1768983953826-231e8ef0b6dc?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Black', 'hex_color' => '#0a0a0a', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=600&h=600&fit=crop&auto=format&q=80'],
                ],
                'sizes' => ['3XL', '4XL', '5XL', '6XL'],
            ],

            // 3. Windbreaker — Wanita — Regular
            [
                'product' => [
                    'slug'         => 'gf-windbreaker-wanita',
                    'name'         => 'GF Windbreaker Wanita',
                    'description'  => 'Jaket windbreaker ringan dan water-resistant. Potongan feminine dengan hood adjustable, sempurna untuk lari pagi atau bersepeda.',
                    'product_type' => 'regular',
                    'base_price'   => 159000,
                    'label'        => 'New',
                    'is_published' => true,
                    'sort_order'   => 3,
                    'category_id'  => $cats['jaket'],
                    'audience'     => 'wanita',
                ],
                'variants' => [
                    ['color_name' => 'Lilac', 'hex_color' => '#c4b5fd', 'is_default' => true,  'image_url' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Blush', 'hex_color' => '#f9a8d4', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Mint',  'hex_color' => '#6ee7b7', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1485518882345-15568b007407?w=600&h=600&fit=crop&auto=format&q=80'],
                ],
                'sizes' => ['XS', 'S', 'M', 'L', 'XL'],
            ],

            // 4. Kids Jacket — Anak — Regular
            [
                'product' => [
                    'slug'         => 'gf-kids-jacket',
                    'name'         => 'GF Kids Jacket',
                    'description'  => 'Jaket anak bahan fleece lembut dan hangat. Desain colorful yang disukai anak-anak, mudah dipakai dengan full zipper.',
                    'product_type' => 'regular',
                    'base_price'   => 119000,
                    'label'        => 'Kids',
                    'is_published' => true,
                    'sort_order'   => 4,
                    'category_id'  => $cats['jaket'],
                    'audience'     => 'anak',
                ],
                'variants' => [
                    ['color_name' => 'Red',   'hex_color' => '#ef4444', 'is_default' => true,  'image_url' => 'https://images.unsplash.com/photo-1622290291468-a28f7a7dc6a8?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Blue',  'hex_color' => '#3b82f6', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Green', 'hex_color' => '#22c55e', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1596870230751-ebdfce98ec42?w=600&h=600&fit=crop&auto=format&q=80'],
                ],
                'sizes' => ['2-3Y', '4-5Y', '6-7Y', '8-9Y', '10-12Y'],
            ],

            // ════════════════════════════════════════════════════
            // HOODIE
            // ════════════════════════════════════════════════════

            // 5. Essential Hoodie — Unisex — Regular
            [
                'product' => [
                    'slug'         => 'gf-essential-hoodie',
                    'name'         => 'GF Essential Hoodie',
                    'description'  => 'Hoodie fleece lembut dan hangat. Fit oversized unisex, nyaman untuk santai maupun olahraga ringan.',
                    'product_type' => 'regular',
                    'base_price'   => 165000,
                    'label'        => 'Best Seller',
                    'is_published' => true,
                    'sort_order'   => 5,
                    'category_id'  => $cats['hoodie'],
                    'audience'     => 'unisex',
                ],
                'variants' => [
                    ['color_name' => 'Grey',  'hex_color' => '#878787', 'is_default' => true,  'image_url' => 'https://images.unsplash.com/photo-1564557287817-3785e38ec1f5?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Black', 'hex_color' => '#0a0a0a', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1556821840-3a63f15732ce?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Sand',  'hex_color' => '#c8b89a', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1578587018452-892bacefd3f2?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Navy',  'hex_color' => '#1c2b4a', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1614975058789-41316d0e2cc3?w=600&h=600&fit=crop&auto=format&q=80'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
            ],

            // 6. Essential Hoodie — Unisex — Jumbo
            [
                'product' => [
                    'slug'         => 'gf-essential-hoodie-jumbo',
                    'name'         => 'GF Essential Hoodie Jumbo',
                    'description'  => 'Hoodie fleece lembut dan hangat. Fit oversized unisex, tersedia ukuran big size 3XL–6XL.',
                    'product_type' => 'jumbo',
                    'base_price'   => 185000,
                    'label'        => 'Big Size',
                    'is_published' => true,
                    'sort_order'   => 6,
                    'category_id'  => $cats['hoodie'],
                    'audience'     => 'unisex',
                ],
                'variants' => [
                    ['color_name' => 'Grey',  'hex_color' => '#878787', 'is_default' => true,  'image_url' => 'https://images.unsplash.com/photo-1564557287817-3785e38ec1f5?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Black', 'hex_color' => '#0a0a0a', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1556821840-3a63f15732ce?w=600&h=600&fit=crop&auto=format&q=80'],
                ],
                'sizes' => ['3XL', '4XL', '5XL', '6XL'],
            ],

            // 7. Zip Hoodie — Pria — Regular
            [
                'product' => [
                    'slug'         => 'gf-zip-hoodie-pria',
                    'name'         => 'GF Zip Hoodie Pria',
                    'description'  => 'Hoodie full-zip bahan French terry tebal. Cocok untuk gym, lari sore, atau dipakai sehari-hari.',
                    'product_type' => 'regular',
                    'base_price'   => 175000,
                    'label'        => null,
                    'is_published' => true,
                    'sort_order'   => 7,
                    'category_id'  => $cats['hoodie'],
                    'audience'     => 'pria',
                ],
                'variants' => [
                    ['color_name' => 'Charcoal', 'hex_color' => '#3c3c3c', 'is_default' => true,  'image_url' => 'https://images.unsplash.com/photo-1605459523894-f9474ce957ec?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Olive',    'hex_color' => '#3a4a2b', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1523381294911-8d3cead13475?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Maroon',   'hex_color' => '#7f1d1d', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?w=600&h=600&fit=crop&auto=format&q=80'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
            ],

            // 8. Kids Hoodie — Anak — Regular
            [
                'product' => [
                    'slug'         => 'gf-kids-hoodie',
                    'name'         => 'GF Kids Hoodie',
                    'description'  => 'Hoodie anak bahan fleece lembut, hangat, dan tidak bikin gerah. Desain kasual dengan kantong depan dan hood yang bisa diatur.',
                    'product_type' => 'regular',
                    'base_price'   => 129000,
                    'label'        => 'Kids',
                    'is_published' => true,
                    'sort_order'   => 8,
                    'category_id'  => $cats['hoodie'],
                    'audience'     => 'anak',
                ],
                'variants' => [
                    ['color_name' => 'Sky Blue', 'hex_color' => '#7dd3fc', 'is_default' => true,  'image_url' => 'https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Pink',     'hex_color' => '#f9a8d4', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Yellow',   'hex_color' => '#fde047', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1596870230751-ebdfce98ec42?w=600&h=600&fit=crop&auto=format&q=80'],
                ],
                'sizes' => ['2-3Y', '4-5Y', '6-7Y', '8-9Y', '10-12Y'],
            ],

            // ════════════════════════════════════════════════════
            // CELANA
            // ════════════════════════════════════════════════════

            // 9. Jogger Pants — Pria — Regular
            [
                'product' => [
                    'slug'         => 'gf-jogger-pants',
                    'name'         => 'GF Jogger Pants',
                    'description'  => 'Celana jogger slim dengan tali pinggang adjustable. Bahan stretch 4-way mengikuti gerakan tubuh sepanjang hari.',
                    'product_type' => 'regular',
                    'base_price'   => 135000,
                    'label'        => 'Ready',
                    'is_published' => true,
                    'sort_order'   => 9,
                    'category_id'  => $cats['celana'],
                    'audience'     => 'pria',
                ],
                'variants' => [
                    ['color_name' => 'Black',    'hex_color' => '#0a0a0a', 'is_default' => true,  'image_url' => 'https://images.unsplash.com/photo-1569032915512-922c2e506c51?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Navy',     'hex_color' => '#1c2b4a', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1542327897-d73f4005b533?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Charcoal', 'hex_color' => '#3c3c3c', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1552902865-b72c031ac5ea?w=600&h=600&fit=crop&auto=format&q=80'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
            ],

            // 10. Jogger Pants — Pria — Jumbo
            [
                'product' => [
                    'slug'         => 'gf-jogger-pants-jumbo',
                    'name'         => 'GF Jogger Pants Jumbo',
                    'description'  => 'Celana jogger slim dengan tali pinggang adjustable. Bahan stretch 4-way, tersedia ukuran big size 3XL–6XL.',
                    'product_type' => 'jumbo',
                    'base_price'   => 155000,
                    'label'        => 'Big Size',
                    'is_published' => true,
                    'sort_order'   => 10,
                    'category_id'  => $cats['celana'],
                    'audience'     => 'pria',
                ],
                'variants' => [
                    ['color_name' => 'Black',    'hex_color' => '#0a0a0a', 'is_default' => true,  'image_url' => 'https://images.unsplash.com/photo-1569032915512-922c2e506c51?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Charcoal', 'hex_color' => '#3c3c3c', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1552902865-b72c031ac5ea?w=600&h=600&fit=crop&auto=format&q=80'],
                ],
                'sizes' => ['3XL', '4XL', '5XL', '6XL'],
            ],

            // 11. Legging Pants — Wanita — Regular
            [
                'product' => [
                    'slug'         => 'gf-legging-pants-wanita',
                    'name'         => 'GF Legging Pants Wanita',
                    'description'  => 'Legging olahraga high-waist bahan spandex premium. Anti-transparan, kering cepat, dan nyaman untuk yoga, pilates, atau gym.',
                    'product_type' => 'regular',
                    'base_price'   => 125000,
                    'label'        => 'New',
                    'is_published' => true,
                    'sort_order'   => 11,
                    'category_id'  => $cats['celana'],
                    'audience'     => 'wanita',
                ],
                'variants' => [
                    ['color_name' => 'Black',  'hex_color' => '#0a0a0a', 'is_default' => true,  'image_url' => 'https://images.unsplash.com/photo-1506629082955-511b1aa562c8?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Plum',   'hex_color' => '#6b21a8', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Coral',  'hex_color' => '#fb923c', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=600&h=600&fit=crop&auto=format&q=80'],
                ],
                'sizes' => ['XS', 'S', 'M', 'L', 'XL'],
            ],

            // 12. Cargo Pants — Olahraga — Regular
            [
                'product' => [
                    'slug'         => 'gf-cargo-pants-olahraga',
                    'name'         => 'GF Cargo Pants Sport',
                    'description'  => 'Celana cargo sport multi-pocket dengan bahan ripstop ringan. Ideal untuk hiking, trail running, atau aktivitas outdoor intens.',
                    'product_type' => 'regular',
                    'base_price'   => 145000,
                    'label'        => null,
                    'is_published' => false,
                    'sort_order'   => 12,
                    'category_id'  => $cats['celana'],
                    'audience'     => 'olahraga',
                ],
                'variants' => [
                    ['color_name' => 'Khaki',    'hex_color' => '#a3855a', 'is_default' => true,  'image_url' => 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Olive',    'hex_color' => '#3a4a2b', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Black',    'hex_color' => '#0a0a0a', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1569032915512-922c2e506c51?w=600&h=600&fit=crop&auto=format&q=80'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
            ],

            // ════════════════════════════════════════════════════
            // KAOS
            // ════════════════════════════════════════════════════

            // 13. Training Tee — Olahraga — Regular
            [
                'product' => [
                    'slug'         => 'gf-training-tee',
                    'name'         => 'GF Training Tee',
                    'description'  => 'Kaos training teknologi moisture-wicking. Tetap kering dan segar saat olahraga intens.',
                    'product_type' => 'regular',
                    'base_price'   => 89000,
                    'label'        => 'Promo',
                    'is_published' => true,
                    'sort_order'   => 13,
                    'category_id'  => $cats['kaos'],
                    'audience'     => 'olahraga',
                ],
                'variants' => [
                    ['color_name' => 'Black', 'hex_color' => '#0a0a0a', 'is_default' => true,  'image_url' => 'https://images.unsplash.com/photo-1571455786673-9d9d6c194f90?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'White', 'hex_color' => '#f0f0f0', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Olive', 'hex_color' => '#4a5c3a', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1586790170083-2f9ceadc732d?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Navy',  'hex_color' => '#1c2b4a', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=600&h=600&fit=crop&auto=format&q=80'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
            ],

            // 14. Training Tee — Olahraga — Jumbo
            [
                'product' => [
                    'slug'         => 'gf-training-tee-jumbo',
                    'name'         => 'GF Training Tee Jumbo',
                    'description'  => 'Kaos training teknologi moisture-wicking. Tetap kering dan segar saat olahraga intens. Tersedia ukuran big size 3XL–6XL.',
                    'product_type' => 'jumbo',
                    'base_price'   => 99000,
                    'label'        => null,
                    'is_published' => true,
                    'sort_order'   => 14,
                    'category_id'  => $cats['kaos'],
                    'audience'     => 'olahraga',
                ],
                'variants' => [
                    ['color_name' => 'Black', 'hex_color' => '#0a0a0a', 'is_default' => true,  'image_url' => 'https://images.unsplash.com/photo-1571455786673-9d9d6c194f90?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'White', 'hex_color' => '#f0f0f0', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=600&h=600&fit=crop&auto=format&q=80'],
                ],
                'sizes' => ['3XL', '4XL', '5XL', '6XL'],
            ],

            // 15. Crop Tee — Wanita — Regular
            [
                'product' => [
                    'slug'         => 'gf-crop-tee-wanita',
                    'name'         => 'GF Crop Tee Wanita',
                    'description'  => 'Kaos crop wanita bahan cotton-spandex stretch ringan. Desain stylish yang cocok untuk gym, yoga, atau daily casual.',
                    'product_type' => 'regular',
                    'base_price'   => 79000,
                    'label'        => 'New',
                    'is_published' => true,
                    'sort_order'   => 15,
                    'category_id'  => $cats['kaos'],
                    'audience'     => 'wanita',
                ],
                'variants' => [
                    ['color_name' => 'White',  'hex_color' => '#f0f0f0', 'is_default' => true,  'image_url' => 'https://images.unsplash.com/photo-1485518882345-15568b007407?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Blush',  'hex_color' => '#f9a8d4', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Sage',   'hex_color' => '#86efac', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Black',  'hex_color' => '#0a0a0a', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1571455786673-9d9d6c194f90?w=600&h=600&fit=crop&auto=format&q=80'],
                ],
                'sizes' => ['XS', 'S', 'M', 'L', 'XL'],
            ],

            // 16. Kids Tee — Anak — Regular
            [
                'product' => [
                    'slug'         => 'gf-kids-tee',
                    'name'         => 'GF Kids Tee',
                    'description'  => 'Kaos anak bahan cotton combed 30s yang lembut di kulit. Nyaman dipakai seharian, tidak mudah kusut, dan warnanya tahan lama.',
                    'product_type' => 'regular',
                    'base_price'   => 59000,
                    'label'        => 'Kids',
                    'is_published' => true,
                    'sort_order'   => 16,
                    'category_id'  => $cats['kaos'],
                    'audience'     => 'anak',
                ],
                'variants' => [
                    ['color_name' => 'Red',    'hex_color' => '#ef4444', 'is_default' => true,  'image_url' => 'https://images.unsplash.com/photo-1622290291468-a28f7a7dc6a8?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Blue',   'hex_color' => '#3b82f6', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Yellow', 'hex_color' => '#fde047', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1596870230751-ebdfce98ec42?w=600&h=600&fit=crop&auto=format&q=80'],
                    ['color_name' => 'Green',  'hex_color' => '#22c55e', 'is_default' => false, 'image_url' => 'https://images.unsplash.com/photo-1512101176959-07798f88e0c9?w=600&h=600&fit=crop&auto=format&q=80'],
                ],
                'sizes' => ['2-3Y', '4-5Y', '6-7Y', '8-9Y', '10-12Y'],
            ],

        ];

        // ── Simpan semua produk ───────────────────────────────────────────────
        $audienceLabel = [
            'pria'     => 'PRIA',
            'wanita'   => 'WANITA',
            'anak'     => 'ANAK',
            'olahraga' => 'OLAHRAGA',
            'unisex'   => 'UNISEX',
        ];

        foreach ($products as $data) {
            $product = StorefrontProduct::create($data['product']);

            foreach ($data['variants'] as $j => $variantData) {
                StorefrontProductVariant::create([
                    'product_id'     => $product->id,
                    'color_name'     => $variantData['color_name'],
                    'hex_color'      => $variantData['hex_color'],
                    'image_url'      => $variantData['image_url'] ?? null,
                    'price_override' => $variantData['price_override'] ?? null,
                    'is_default'     => $variantData['is_default'],
                    'sort_order'     => $j + 1,
                    'is_active'      => true,
                ]);
            }

            foreach ($data['sizes'] as $k => $sizeLabel) {
                StorefrontProductSize::create([
                    'product_id'     => $product->id,
                    'size_label'     => $sizeLabel,
                    'price_override' => null,
                    'sort_order'     => $k + 1,
                    'is_active'      => true,
                ]);
            }

            $aud = $audienceLabel[$data['product']['audience']] ?? '—';
            $pub = $data['product']['is_published'] ? 'published' : 'draft';
            $this->command->info(sprintf(
                '  [OK] [%s/%s/%s] %s — %d warna, %d ukuran (%s)',
                strtoupper($data['product']['product_type']),
                $this->categoryNameFromId($kategoris, $data['product']['category_id'], $cats),
                $aud,
                $data['product']['name'],
                count($data['variants']),
                count($data['sizes']),
                $pub
            ));
        }

        // ── Ringkasan ─────────────────────────────────────────────────────────
        $published = collect($products)->where('product.is_published', true)->count();
        $draft     = collect($products)->where('product.is_published', false)->count();

        $this->command->info('');
        $this->command->info('─────────────────────────────────────────────────────');
        $this->command->info(sprintf(
            'Selesai: %d kategori, %d produk (%d published, %d draft).',
            count($kategoris), count($products), $published, $draft
        ));

        // Breakdown per audience
        $byAudience = collect($products)->groupBy('product.audience')->map->count();
        foreach ($byAudience as $aud => $count) {
            $this->command->info(sprintf('  %s → %d produk', $audienceLabel[$aud] ?? $aud, $count));
        }
        $this->command->info('');
    }

    private function categoryNameFromId(array $kategoris, int $id, array $cats): string
    {
        foreach ($cats as $slug => $catId) {
            if ($catId === $id) {
                foreach ($kategoris as $k) {
                    if ($k['slug'] === $slug) return strtoupper($k['slug']);
                }
            }
        }
        return '?';
    }
}
