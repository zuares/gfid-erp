<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemCategorySeeder extends Seeder
{
    public function run(): void
    {
            DB::table('item_categories')->updateOrInsert(['code' => 'MAT'], [
                'code' => 'MAT',
                'name' => 'Bahan Baku',
                'active' => 1,
                'kind' => 'material',
            ]);
            DB::table('item_categories')->updateOrInsert(['code' => 'ACC'], [
                'code' => 'ACC',
                'name' => 'Accessories',
                'active' => 1,
                'kind' => 'accessory',
            ]);
            DB::table('item_categories')->updateOrInsert(['code' => 'FG'], [
                'code' => 'FG',
                'name' => 'Finished Goods',
                'active' => 1,
                'kind' => 'product',
            ]);
            DB::table('item_categories')->updateOrInsert(['code' => 'SJR'], [
                'code' => 'SJR',
                'name' => 'Jogger Pendek Basic',
                'active' => 1,
                'kind' => 'product',
            ]);
            DB::table('item_categories')->updateOrInsert(['code' => 'LJR'], [
                'code' => 'LJR',
                'name' => 'Jogger Panjang Basic',
                'active' => 1,
                'kind' => 'product',
            ]);
            DB::table('item_categories')->updateOrInsert(['code' => 'HDY'], [
                'code' => 'HDY',
                'name' => 'Hoodie / Sweater',
                'active' => 1,
                'kind' => 'product',
            ]);
            DB::table('item_categories')->updateOrInsert(['code' => 'TSH'], [
                'code' => 'TSH',
                'name' => 'T-shirt / Kaos',
                'active' => 1,
                'kind' => 'product',
            ]);
            DB::table('item_categories')->updateOrInsert(['code' => 'CRG'], [
                'code' => 'CRG',
                'name' => 'Jogger Pendek Cargo',
                'active' => 1,
                'kind' => 'product',
            ]);
            DB::table('item_categories')->updateOrInsert(['code' => 'LCG'], [
                'code' => 'LCG',
                'name' => 'Jogger Panjang Cargo',
                'active' => 1,
                'kind' => 'product',
            ]);
            DB::table('item_categories')->updateOrInsert(['code' => 'SHT'], [
                'code' => 'SHT',
                'name' => 'Shot Boxer Brief',
                'active' => 1,
                'kind' => 'product',
            ]);
            DB::table('item_categories')->updateOrInsert(['code' => 'TJR'], [
                'code' => 'TJR',
                'name' => 'Celana Jogger Pendek Bodyfit',
                'active' => 1,
                'kind' => 'product',
            ]);
            DB::table('item_categories')->updateOrInsert(['code' => 'BPU'], [
                'code' => 'BPU',
                'name' => 'Bahan Pendukung',
                'active' => 1,
                'kind' => 'support',
            ]);
            DB::table('item_categories')->updateOrInsert(['code' => 'PACK'], [
                'code' => 'PACK',
                'name' => 'Packaging & Shipping',
                'active' => 1,
                'kind' => 'packaging',
            ]);
            DB::table('item_categories')->updateOrInsert(['code' => 'LBP'], [
                'code' => 'LBP',
                'name' => 'Celana Panjang Baggy Pants',
                'active' => 1,
                'kind' => 'product',
            ]);
            DB::table('item_categories')->updateOrInsert(['code' => 'TTB'], [
                'code' => 'TTB',
                'name' => 'Tracktop',
                'active' => 1,
                'kind' => 'product',
            ]);
            DB::table('item_categories')->updateOrInsert(['code' => 'BRD'], [
                'code' => 'BRD',
                'name' => 'Celana Boardshort Parasit',
                'active' => 1,
                'kind' => 'product',
            ]);
    }
}
