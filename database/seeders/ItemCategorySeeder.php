<?php

namespace Database\Seeders;

use App\Models\ItemCategory;
use Illuminate\Database\Seeder;

class ItemCategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['code' => 'MAT', 'name' => 'Material / Bahan Baku'],
            ['code' => 'ACC', 'name' => 'Accessories'],
            ['code' => 'FG', 'name' => 'Finished Goods'],
            ['code' => 'SJR', 'name' => 'Celana Jogger Pendek Basic'],
            ['code' => 'LJR', 'name' => 'Celana Jogger Panjang'],
            ['code' => 'HDY', 'name' => 'Hoodie / Sweater'],
            ['code' => 'TSH', 'name' => 'T-shirt / Kaos'],
        ];

        foreach ($data as $row) {
            ItemCategory::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
