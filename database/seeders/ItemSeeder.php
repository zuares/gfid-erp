<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemCategory;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'CRG' => [
                'name' => 'Celana Cargo',
                'items' => [
                    'C5BLK', 'C5MST', 'C5NVY',
                    'C7BLK', 'C7MST', 'C7NVY',
                ],
            ],
            'LJR' => [
                'name' => 'Jaket Layer',
                'items' => [
                    'J3ABT', 'J3BLK', 'J3MST', 'J3NVY',
                    'J5ABT', 'J5BLK', 'J5MST', 'J5NVY',
                    'J7ABT', 'J7BLK', 'J7MST', 'J7NVY',
                ],
            ],
            'SJR' => [
                'name' => 'Celana Jogger',
                'items' => [
                    'K1ABT', 'K1BLK', 'K1MST', 'K1NVY', 'K1WHT',
                    'K2ABT', 'K2BLK', 'K2MST', 'K2NVY',
                    'K3ABT', 'K3BBL', 'K3BLK', 'K3MST', 'K3NVY', 'K3WHT',
                    'K5ABT', 'K5BBL', 'K5BLK', 'K5MST', 'K5NVY', 'K5WHT',
                    'K7ABT', 'K7BBL', 'K7BLK', 'K7MST', 'K7NVY', 'K7WHT',
                    // tambahan
                    'K1BBL', 'K2BBL', 'K2WHT',
                ],
            ],
            'LCG' => [
                'name' => 'Legging',
                'items' => [
                    'L1BLK', 'L1MST', 'L1NVY',
                    'L2BLK', 'L2MST', 'L2NVY',
                    'L1ABT', 'L2ABT',
                ],
            ],
            'SHT' => [
                'name' => 'Short Pants',
                'items' => [
                    'S2RDM', 'S2RDM-3', 'S2RDM-6',
                    'S3RDM', 'S3RDM-3', 'S3RDM-6',
                    'S4RDM', 'S4RDM-3', 'S4RDM-6',
                    'S5RDM', 'S5RDM-3', 'S5RDM-6',
                ],
            ],
            'TJR' => [
                'name' => 'T-Shirt',
                'items' => [
                    'T1ABT', 'T1BLK', 'T1MST', 'T1NVY',
                    'T2ABT', 'T2BLK', 'T2MST', 'T2NVY',
                ],
            ],
            'MAT' => [ // Bahan baku
                'name' => 'Bahan Baku',
                'items' => [
                    'FLC280BLK', 'FLC280NVY', 'FLC280MST', 'FLC280ABT', 'FLC280WHT', 'FLC280BBL',
                    'RIB280BLK', 'RIB280NVY', 'RIB280MST', 'RIB280ABT', 'RIB280WHT', 'RIB280BBL',
                ],
            ],
            'BPU' => [ // Bahan pendukung
                'name' => 'Bahan Pendukung',
                'items' => [
                    'TLKADDS', 'KRT4CM', 'BNGJHT',
                ],
            ],
        ];

        // kategori yang dianggap finished_good
        $finishedGoodCategories = ['CRG', 'LJR', 'SJR', 'LCG', 'SHT', 'TJR'];

        foreach ($data as $catCode => $config) {
            $category = ItemCategory::firstOrCreate(
                ['code' => $catCode],
                [
                    'name' => $config['name'],
                    'active' => 1,
                ]
            );

            foreach ($config['items'] as $code) {
                $type = in_array($catCode, $finishedGoodCategories, true)
                ? 'finished_good'
                : 'material';

                Item::firstOrCreate(
                    ['code' => $code],
                    [
                        'name' => $this->generateName($catCode, $code),
                        'unit' => 'pcs',
                        'type' => $type,
                        'item_category_id' => $category->id,
                        'last_purchase_price' => 0,
                        'hpp' => 0,
                        'active' => 1,
                    ]
                );
            }
        }
    }

    private function generateName(string $catCode, string $code): string
    {
        // warna mapping
        $colors = [
            'BLK' => 'Black',
            'MST' => 'Mustard',
            'NVY' => 'Navy',
            'ABT' => 'Abu Tua',
            'BBL' => 'Baby Blue',
            'WHT' => 'White',
            'RDM' => 'Red Maroon',
        ];

        // kategori nama depan
        $prefix = [
            'CRG' => 'Cargo',
            'LJR' => 'Jaket',
            'SJR' => 'Jogger',
            'LCG' => 'Legging',
            'SHT' => 'Short',
            'TJR' => 'T-Shirt',
        ];

        // Jika kategori BPU → manual nama
        $manual = [
            'TLKADDS' => 'Tali Karet Adidas',
            'KRT4CM' => 'Karet 4 CM',
            'BNGJHT' => 'Benang Jahit',
        ];

        if (isset($manual[$code])) {
            return $manual[$code];
        }

        // Jika MAT (bahan baku) → Fleece / Rib
        if (str_starts_with($code, 'FLC')) {
            // FLC280BLK → Fleece 280 Black
            $gram = substr($code, 3, 3);
            $clr = substr($code, 6);
            return 'Fleece ' . $gram . ' ' . ($colors[$clr] ?? $clr);
        }

        if (str_starts_with($code, 'RIB')) {
            $gram = substr($code, 3, 3);
            $clr = substr($code, 6);
            return 'Rib ' . $gram . ' ' . ($colors[$clr] ?? $clr);
        }

        // Kategori produk (general)
        $catName = $prefix[$catCode] ?? $catCode;

        // Pisahkan model & warna (ex: C5BLK → C5 + BLK)
        preg_match('/([A-Za-z0-9]+?)([A-Z]{3})$/', $code, $m);

        $model = $m[1] ?? $code;
        $clr = $m[2] ?? '';
        $colorName = $colors[$clr] ?? $clr;

        return "{$catName} {$model} {$colorName}";
    }
}
