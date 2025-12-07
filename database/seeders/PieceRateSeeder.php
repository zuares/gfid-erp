<?php

namespace Database\Seeders;

use App\Models\PieceRate;
use Illuminate\Database\Seeder;

class PieceRateSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'module' => 'sewing',
                'employee_id' => 6,
                'item_category_id' => 4,
                'item_id' => null,
                'rate_per_pcs' => 5000,
                'effective_from' => '2025-11-30',
                'effective_to' => '2026-01-31',
                'notes' => null,
            ],
            [
                'module' => 'cutting',
                'employee_id' => 4,
                'item_category_id' => 4,
                'item_id' => null,
                'rate_per_pcs' => 800,
                'effective_from' => '2025-11-30',
                'effective_to' => '2025-12-31',
                'notes' => null,
            ],
            [
                'module' => 'cutting',
                'employee_id' => 4,
                'item_category_id' => 5,
                'item_id' => null,
                'rate_per_pcs' => 1000,
                'effective_from' => '2025-11-30',
                'effective_to' => '2025-12-31',
                'notes' => null,
            ],
            [
                'module' => 'sewing',
                'employee_id' => 5,
                'item_category_id' => 5,
                'item_id' => null,
                'rate_per_pcs' => 6500,
                'effective_from' => '2025-11-30',
                'effective_to' => '2025-12-31',
                'notes' => null,
            ],
            [
                'module' => 'sewing',
                'employee_id' => 6,
                'item_category_id' => 5,
                'item_id' => null,
                'rate_per_pcs' => 6000,
                'effective_from' => '2025-11-30',
                'effective_to' => '2025-12-31',
                'notes' => null,
            ],
            [
                'module' => 'sewing',
                'employee_id' => 5,
                'item_category_id' => 4,
                'item_id' => null,
                'rate_per_pcs' => 5500,
                'effective_from' => '2025-11-30',
                'effective_to' => '2025-12-31',
                'notes' => null,
            ],
        ];

        PieceRate::insert($data);
    }
}
