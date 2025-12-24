<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemCostSnapshot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'CRG' => [
                'name' => 'Jogger Pendek Cargo',
                'items' => [
                    'C5BLK', 'C5MST', 'C5NVY',
                    'C7BLK', 'C7MST', 'C7NVY',
                ],
            ],
            'LJR' => [
                'name' => 'Jogger Panjang Basic',
                'items' => [
                    'J3ABT', 'J3BLK', 'J3MST', 'J3NVY',
                    'J5ABT', 'J5BLK', 'J5MST', 'J5NVY',
                    'J7ABT', 'J7BLK', 'J7MST', 'J7NVY',
                ],
            ],
            'SJR' => [
                'name' => 'Jogger Pendek Basic',
                'items' => [
                    'K1ABT', 'K1BLK', 'K1MST', 'K1NVY', 'K1WHT',
                    'K2ABT', 'K2BLK', 'K2MST', 'K2NVY',
                    'K3ABT', 'K3BBL', 'K3BLK', 'K3MST', 'K3NVY', 'K3WHT',
                    'K5ABT', 'K5BBL', 'K5BLK', 'K5MST', 'K5NVY', 'K5WHT',
                    'K7ABT', 'K7BBL', 'K7BLK', 'K7MST', 'K7NVY', 'K7WHT',
                    'K1BBL', 'K2BBL', 'K2WHT',
                ],
            ],
            'LCG' => [
                'name' => 'Jogger Panjang Cargo',
                'items' => [
                    'L1BLK', 'L1MST', 'L1NVY',
                    'L2BLK', 'L2MST', 'L2NVY',
                    'L1ABT', 'L2ABT',
                ],
            ],
            'SHT' => [
                'name' => 'Shot Boxer Brief',
                'items' => [
                    'S2RDM', 'S2RDM-3', 'S2RDM-6',
                    'S3RDM', 'S3RDM-3', 'S3RDM-6',
                    'S4RDM', 'S4RDM-3', 'S4RDM-6',
                    'S5RDM', 'S5RDM-3', 'S5RDM-6',
                ],
            ],
            'TJR' => [
                'name' => 'Celana Jogger Pendek Bodyfit',
                'items' => [
                    'T1ABT', 'T1BLK', 'T1MST', 'T1NVY',
                    'T2ABT', 'T2BLK', 'T2MST', 'T2NVY',
                ],
            ],
            'MAT' => [
                'name' => 'Bahan Baku',
                'items' => [
                    'FLC280BLK', 'FLC280NVY', 'FLC280MST', 'FLC280ABT', 'FLC280WHT', 'FLC280BBL',
                    'RIB280BLK', 'RIB280NVY', 'RIB280MST', 'RIB280ABT', 'RIB280WHT', 'RIB280BBL',
                ],
            ],
            'BPU' => [
                'name' => 'Bahan Pendukung',
                'items' => [
                    'TLKADDS', 'KRT4CM', 'BNGJHT',
                ],
            ],
        ];

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

                /** @var \App\Models\Item $item */
                $item = Item::firstOrCreate(
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

                // Hitung HPP default berdasarkan pola kode item
                $hpp = $this->guessHppFromCode($code);

                // Hanya terapkan untuk finished good & kalau ada rule HPP
                if ($type === 'finished_good' && $hpp !== null) {
                    // Update kolom hpp di items **hanya jika** masih 0/null
                    if (empty($item->hpp) || $item->hpp == 0) {
                        $item->hpp = $hpp;
                        $item->save();
                    }

                    // Buat snapshot HPP hanya kalau belum ada snapshot aktif
                    $hasActiveSnapshot = ItemCostSnapshot::where('item_id', $item->id)
                        ->where('is_active', 1)
                        ->exists();

                    if (!$hasActiveSnapshot) {
                        ItemCostSnapshot::create([
                            'item_id' => $item->id,
                            'warehouse_id' => null, // global HPP (tidak spesifik gudang)
                            'snapshot_date' => Carbon::today()->toDateString(),
                            'reference_type' => 'seed',
                            'reference_id' => null,
                            'qty_basis' => 1,
                            'rm_unit_cost' => 0,
                            'cutting_unit_cost' => 0,
                            'sewing_unit_cost' => 0,
                            'finishing_unit_cost' => 0,
                            'packaging_unit_cost' => 0,
                            'overhead_unit_cost' => 0,
                            'unit_cost' => $hpp,
                            'notes' => 'Initial HPP seed from ItemSeeder',
                            'is_active' => 1,
                        ]);
                    }
                }
            }
        }
    }

    private function generateName(string $catCode, string $code): string
    {
        $colors = [
            'BLK' => 'Hitam',
            'MST' => 'Misty (Abu-Abu) M71',
            'NVY' => 'Navy',
            'ABT' => 'Abu Tua M81',
            'BBL' => 'Baby Blue',
            'WHT' => 'Putih',
            'RDM' => 'Random',
        ];

        $prefix = [
            'CRG' => 'Jogger Pendek Cargo',
            'LJR' => 'Jogger Panjang Basic',
            'SJR' => 'Jogger Pendek Basic',
            'LCG' => 'Jogger Panjang Cargo',
            'SHT' => 'Shot Boxer Brief',
            'TJR' => 'Jogger Pendek Bodyfit',
        ];

        $manual = [
            'TLKADDS' => 'Tali Karet Adidas',
            'KRT4CM' => 'Karet 4 CM',
            'BNGJHT' => 'Benang Jahit',
        ];

        if (isset($manual[$code])) {
            return $manual[$code];
        }

        if (str_starts_with($code, 'FLC')) {
            $gram = substr($code, 3, 3);
            $clr = substr($code, 6);
            return 'Fleece ' . $gram . ' ' . ($colors[$clr] ?? $clr);
        }

        if (str_starts_with($code, 'RIB')) {
            $gram = substr($code, 3, 3);
            $clr = substr($code, 6);
            return 'Rib ' . $gram . ' ' . ($colors[$clr] ?? $clr);
        }

        $catName = $prefix[$catCode] ?? $catCode;

        preg_match('/([A-Za-z0-9]+?)([A-Z]{3})$/', $code, $m);

        $model = $m[1] ?? $code;
        $clr = $m[2] ?? '';
        $colorName = $colors[$clr] ?? $clr;

        return "{$catName} {$model} {$colorName}";
    }

    /**
     * Hitung HPP default berdasarkan pola kode.
     *
     * Aturan:
     * - K1,K2,K3,K5,K7        → 30.000
     * - J3,J5,J7              → 45.000
     * - L1,L2                 → 35.000
     * - C3,C5,C7              → 35.000
     * - T1,T2                 → 30.000
     * - S2RDM,S3RDM,S4RDM     → 6.700 (per pcs)
     * - S5RDM                 → 8.400 (per pcs)
     *   - Jika suffix "-3"    → HPP × 3
     *   - Jika suffix "-6"    → HPP × 6
     */
    private function guessHppFromCode(string $code): ?int
    {
        $c = strtoupper($code);
        $base = null;

        $prefix2 = substr($c, 0, 2); // contoh: K1, J3, L1, C5, T1, T2

        // Jogger K1,K2,K3,K5,K7 → 30.000
        if (in_array($prefix2, ['K1', 'K2', 'K3', 'K5', 'K7'], true)) {
            $base = 30000;
        }

        // Jaket J3,J5,J7 → 45.000
        if (in_array($prefix2, ['J3', 'J5', 'J7'], true)) {
            $base = 45000;
        }

        // Legging L1,L2 → 35.000
        if (in_array($prefix2, ['L1', 'L2'], true)) {
            $base = 35000;
        }

        // Cargo C3,C5,C7 → 35.000
        if (in_array($prefix2, ['C3', 'C5', 'C7'], true)) {
            $base = 35000;
        }

        // T-Shirt T1,T2 → 30.000
        if (in_array($prefix2, ['T1', 'T2'], true)) {
            $base = 30000;
        }

        // Short RDM:
        // - S2RDM,S3RDM,S4RDM → 6.700
        // - S5RDM             → 8.400
        if (preg_match('/^S[2-4]RDM/i', $c)) {
            $base = 6700;
        } elseif (preg_match('/^S5RDM/i', $c)) {
            $base = 8400;
        }

        if ($base === null) {
            return null;
        }

        // Bundle handling: ...-6 / ...-3 → dikali 6 / 3
        if (str_ends_with($c, '-6')) {
            $base *= 6;
        } elseif (str_ends_with($c, '-3')) {
            $base *= 3;
        }

        return $base;
    }
}
