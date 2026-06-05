<?php

namespace Database\Seeders;

use App\Models\ItemCategory;
use Illuminate\Database\Seeder;

class ItemCategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['code' => 'MAT', 'name' => 'Material / Bahan Baku', 'kind' => 'material'],
            ['code' => 'BPU', 'name' => 'Bahan Pendukung', 'kind' => 'support'],
            ['code' => 'ACC', 'name' => 'Accessories', 'kind' => 'accessory'],
            ['code' => 'PACK', 'name' => 'Packaging & Shipping', 'kind' => 'packaging'],
            ['code' => 'FG', 'name' => 'Finished Goods', 'kind' => 'product'],
            ['code' => 'TJR', 'name' => 'Celana Jogger Pendek Bodyfit', 'kind' => 'product'],
            ['code' => 'LBP', 'name' => 'Celana Panjang Baggy Pants', 'kind' => 'product'],
            ['code' => 'SJR', 'name' => 'Celana Jogger Pendek Basic', 'kind' => 'product'],
            ['code' => 'CRG', 'name' => 'Jogger Pendek Cargo', 'kind' => 'product'],
            ['code' => 'LJR', 'name' => 'Jogger Panjang Basic', 'kind' => 'product'],
            ['code' => 'LCG', 'name' => 'Jogger Panjang Cargo', 'kind' => 'product'],
            ['code' => 'SHT', 'name' => 'Shot Boxer Brief', 'kind' => 'product'],
            ['code' => 'HDY', 'name' => 'Hoodie / Sweater', 'kind' => 'product'],
            ['code' => 'TSH', 'name' => 'T-shirt / Kaos', 'kind' => 'product'],
        ];

        foreach ($data as $row) {
            ItemCategory::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
