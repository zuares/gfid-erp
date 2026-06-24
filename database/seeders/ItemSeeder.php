<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $catId = DB::table('item_categories')->where('code', 'CRG')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'C5BLK'],
            [
                'code' => 'C5BLK',
                'name' => 'Jogger Pendek Cargo C5 Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 35000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'CRG')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'C5MST'],
            [
                'code' => 'C5MST',
                'name' => 'Jogger Pendek Cargo C5 Misty (Abu-Abu) M71',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 35000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'CRG')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'C5NVY'],
            [
                'code' => 'C5NVY',
                'name' => 'Jogger Pendek Cargo C5 Navy',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 35000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'CRG')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'C7BLK'],
            [
                'code' => 'C7BLK',
                'name' => 'Jogger Pendek Cargo C7 Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 35000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'CRG')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'C7MST'],
            [
                'code' => 'C7MST',
                'name' => 'Jogger Pendek Cargo C7 Misty (Abu-Abu) M71',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 35000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'CRG')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'C7NVY'],
            [
                'code' => 'C7NVY',
                'name' => 'Jogger Pendek Cargo C7 Navy',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 35000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'J3ABT'],
            [
                'code' => 'J3ABT',
                'name' => 'Jogger Panjang Basic J3 Abu Tua M81',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 51500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'J3BLK'],
            [
                'code' => 'J3BLK',
                'name' => 'Jogger Panjang Basic J3 Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 51500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'J3MST'],
            [
                'code' => 'J3MST',
                'name' => 'Jogger Panjang Basic J3 Misty (Abu-Abu) M71',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 51500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'J3NVY'],
            [
                'code' => 'J3NVY',
                'name' => 'Jogger Panjang Basic J3 Navy',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 51500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'J5ABT'],
            [
                'code' => 'J5ABT',
                'name' => 'Jogger Panjang Basic J5 Abu Tua M81',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 51500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'J5BLK'],
            [
                'code' => 'J5BLK',
                'name' => 'Jogger Panjang Basic J5 Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 51500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'J5MST'],
            [
                'code' => 'J5MST',
                'name' => 'Jogger Panjang Basic J5 Misty (Abu-Abu) M71',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 51500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'J5NVY'],
            [
                'code' => 'J5NVY',
                'name' => 'Jogger Panjang Basic J5 Navy',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 51500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'J7ABT'],
            [
                'code' => 'J7ABT',
                'name' => 'Jogger Panjang Basic J7 Abu Tua M81',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 51500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'J7BLK'],
            [
                'code' => 'J7BLK',
                'name' => 'Jogger Panjang Basic J7 Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 51500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'J7MST'],
            [
                'code' => 'J7MST',
                'name' => 'Jogger Panjang Basic J7 Misty (Abu-Abu) M71',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 51500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'J7NVY'],
            [
                'code' => 'J7NVY',
                'name' => 'Jogger Panjang Basic J7 Navy',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 51500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K1ABT'],
            [
                'code' => 'K1ABT',
                'name' => 'Jogger Pendek Basic K1 Abu Tua M81',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K1BLK'],
            [
                'code' => 'K1BLK',
                'name' => 'Jogger Pendek Basic K1 Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K1MST'],
            [
                'code' => 'K1MST',
                'name' => 'Jogger Pendek Basic K1 Misty (Abu-Abu) M71',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K1NVY'],
            [
                'code' => 'K1NVY',
                'name' => 'Jogger Pendek Basic K1 Navy',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K1WHT'],
            [
                'code' => 'K1WHT',
                'name' => 'Jogger Pendek Basic K1 Putih',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K2ABT'],
            [
                'code' => 'K2ABT',
                'name' => 'Jogger Pendek Basic K2 Abu Tua M81',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K2BLK'],
            [
                'code' => 'K2BLK',
                'name' => 'Jogger Pendek Basic K2 Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K2MST'],
            [
                'code' => 'K2MST',
                'name' => 'Jogger Pendek Basic K2 Misty (Abu-Abu) M71',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K2NVY'],
            [
                'code' => 'K2NVY',
                'name' => 'Jogger Pendek Basic K2 Navy',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K3ABT'],
            [
                'code' => 'K3ABT',
                'name' => 'Jogger Pendek Basic K3 Abu Tua M81',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K3BBL'],
            [
                'code' => 'K3BBL',
                'name' => 'Jogger Pendek Basic K3 Baby Blue',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K3BLK'],
            [
                'code' => 'K3BLK',
                'name' => 'Jogger Pendek Basic K3 Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K3MST'],
            [
                'code' => 'K3MST',
                'name' => 'Jogger Pendek Basic K3 Misty (Abu-Abu) M71',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K3NVY'],
            [
                'code' => 'K3NVY',
                'name' => 'Jogger Pendek Basic K3 Navy',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K3WHT'],
            [
                'code' => 'K3WHT',
                'name' => 'Jogger Pendek Basic K3 Putih',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K5ABT'],
            [
                'code' => 'K5ABT',
                'name' => 'Jogger Pendek Basic K5 Abu Tua M81',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K5BBL'],
            [
                'code' => 'K5BBL',
                'name' => 'Jogger Pendek Basic K5 Baby Blue',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K5BLK'],
            [
                'code' => 'K5BLK',
                'name' => 'Jogger Pendek Basic K5 Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K5MST'],
            [
                'code' => 'K5MST',
                'name' => 'Jogger Pendek Basic K5 Misty (Abu-Abu) M71',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K5NVY'],
            [
                'code' => 'K5NVY',
                'name' => 'Jogger Pendek Basic K5 Navy',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K5WHT'],
            [
                'code' => 'K5WHT',
                'name' => 'Jogger Pendek Basic K5 Putih',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K7ABT'],
            [
                'code' => 'K7ABT',
                'name' => 'Jogger Pendek Basic K7 Abu Tua M81',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K7BBL'],
            [
                'code' => 'K7BBL',
                'name' => 'Jogger Pendek Basic K7 Baby Blue',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K7BLK'],
            [
                'code' => 'K7BLK',
                'name' => 'Jogger Pendek Basic K7 Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K7MST'],
            [
                'code' => 'K7MST',
                'name' => 'Jogger Pendek Basic K7 Misty (Abu-Abu) M71',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K7NVY'],
            [
                'code' => 'K7NVY',
                'name' => 'Jogger Pendek Basic K7 Navy',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K7WHT'],
            [
                'code' => 'K7WHT',
                'name' => 'Jogger Pendek Basic K7 Putih',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K1BBL'],
            [
                'code' => 'K1BBL',
                'name' => 'Jogger Pendek Basic K1 Baby Blue',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K2BBL'],
            [
                'code' => 'K2BBL',
                'name' => 'Jogger Pendek Basic K2 Baby Blue',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'K2WHT'],
            [
                'code' => 'K2WHT',
                'name' => 'Jogger Pendek Basic K2 Putih',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 33000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LCG')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'L1BLK'],
            [
                'code' => 'L1BLK',
                'name' => 'Jogger Panjang Cargo L1 Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 45500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LCG')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'L1MST'],
            [
                'code' => 'L1MST',
                'name' => 'Jogger Panjang Cargo L1 Misty (Abu-Abu) M71',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 45500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LCG')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'L1NVY'],
            [
                'code' => 'L1NVY',
                'name' => 'Jogger Panjang Cargo L1 Navy',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 45500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LCG')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'L2BLK'],
            [
                'code' => 'L2BLK',
                'name' => 'Jogger Panjang Cargo L2 Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 35000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LCG')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'L2MST'],
            [
                'code' => 'L2MST',
                'name' => 'Jogger Panjang Cargo L2 Misty (Abu-Abu) M71',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 35000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LCG')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'L2NVY'],
            [
                'code' => 'L2NVY',
                'name' => 'Jogger Panjang Cargo L2 Navy',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 35000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LCG')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'L1ABT'],
            [
                'code' => 'L1ABT',
                'name' => 'Jogger Panjang Cargo L1 Abu Tua M81',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 45500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LCG')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'L2ABT'],
            [
                'code' => 'L2ABT',
                'name' => 'Jogger Panjang Cargo L2 Abu Tua M81',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 35000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SHT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'S2RDM'],
            [
                'code' => 'S2RDM',
                'name' => 'Shot Boxer Brief S2 Random',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 6417,
                'hpp' => 6700,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SHT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'S2RDM-3'],
            [
                'code' => 'S2RDM-3',
                'name' => 'Shot Boxer Brief S2RDM-3 ',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 20100,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SHT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'S2RDM-6'],
            [
                'code' => 'S2RDM-6',
                'name' => 'Shot Boxer Brief S2RDM-6 ',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 40200,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SHT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'S3RDM'],
            [
                'code' => 'S3RDM',
                'name' => 'Shot Boxer Brief S3 Random',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 6667,
                'hpp' => 6700,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SHT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'S3RDM-3'],
            [
                'code' => 'S3RDM-3',
                'name' => 'Shot Boxer Brief S3RDM-3 ',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 20100,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SHT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'S3RDM-6'],
            [
                'code' => 'S3RDM-6',
                'name' => 'Shot Boxer Brief S3RDM-6 ',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 40200,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SHT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'S4RDM'],
            [
                'code' => 'S4RDM',
                'name' => 'Shot Boxer Brief S4 Random',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 6667,
                'hpp' => 6700,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SHT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'S4RDM-3'],
            [
                'code' => 'S4RDM-3',
                'name' => 'Shot Boxer Brief S4RDM-3 ',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 20100,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SHT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'S4RDM-6'],
            [
                'code' => 'S4RDM-6',
                'name' => 'Shot Boxer Brief S4RDM-6 ',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 40200,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SHT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'S5RDM'],
            [
                'code' => 'S5RDM',
                'name' => 'Shot Boxer Brief S5 Random',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 8333,
                'hpp' => 8400,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SHT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'S5RDM-3'],
            [
                'code' => 'S5RDM-3',
                'name' => 'Shot Boxer Brief S5RDM-3 ',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 25200,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'SHT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'S5RDM-6'],
            [
                'code' => 'S5RDM-6',
                'name' => 'Shot Boxer Brief S5RDM-6 ',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 50400,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'T1ABT'],
            [
                'code' => 'T1ABT',
                'name' => 'Jogger Pendek Bodyfit T1 Abu Tua M81',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 28000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'T1BLK'],
            [
                'code' => 'T1BLK',
                'name' => 'Jogger Pendek Bodyfit T1 Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 28000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'T1MST'],
            [
                'code' => 'T1MST',
                'name' => 'Jogger Pendek Bodyfit T1 Misty (Abu-Abu) M71',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 28000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'T1NVY'],
            [
                'code' => 'T1NVY',
                'name' => 'Jogger Pendek Bodyfit T1 Navy',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 28000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'T2ABT'],
            [
                'code' => 'T2ABT',
                'name' => 'Jogger Pendek Bodyfit T2 Abu Tua M81',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 28000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'T2BLK'],
            [
                'code' => 'T2BLK',
                'name' => 'Jogger Pendek Bodyfit T2 Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 28000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'T2MST'],
            [
                'code' => 'T2MST',
                'name' => 'Jogger Pendek Bodyfit T2 Misty (Abu-Abu) M71',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 28000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TJR')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'T2NVY'],
            [
                'code' => 'T2NVY',
                'name' => 'Jogger Pendek Bodyfit T2 Navy',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 28000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'RM')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'FLC280BLK'],
            [
                'code' => 'FLC280BLK',
                'name' => 'Fleece 280 Hitam',
                'unit' => 'pcs',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'raw_material',
                'item_role_id' => $roleId,
                'last_purchase_price' => 59000,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'RM')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'FLC280NVY'],
            [
                'code' => 'FLC280NVY',
                'name' => 'Fleece 280 Navy',
                'unit' => 'pcs',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'raw_material',
                'item_role_id' => $roleId,
                'last_purchase_price' => 59000,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'RM')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'FLC280MST'],
            [
                'code' => 'FLC280MST',
                'name' => 'Fleece 280 Misty (Abu-Abu) M71',
                'unit' => 'pcs',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'raw_material',
                'item_role_id' => $roleId,
                'last_purchase_price' => 52350,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'RM')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'FLC280ABT'],
            [
                'code' => 'FLC280ABT',
                'name' => 'Fleece 280 Abu Tua M81',
                'unit' => 'pcs',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'raw_material',
                'item_role_id' => $roleId,
                'last_purchase_price' => 54100,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'RM')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'FLC280WHT'],
            [
                'code' => 'FLC280WHT',
                'name' => 'Fleece 280 Putih',
                'unit' => 'pcs',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'raw_material',
                'item_role_id' => $roleId,
                'last_purchase_price' => 59800,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'RM')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'FLC280BBL'],
            [
                'code' => 'FLC280BBL',
                'name' => 'Fleece 280 Baby Blue',
                'unit' => 'pcs',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'raw_material',
                'item_role_id' => $roleId,
                'last_purchase_price' => 51750,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'SUP')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'RIB280BLK'],
            [
                'code' => 'RIB280BLK',
                'name' => 'Rib 280 Hitam',
                'unit' => 'kg',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'production_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 52100,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'SUP')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'RIB280NVY'],
            [
                'code' => 'RIB280NVY',
                'name' => 'Rib 280 Navy',
                'unit' => 'kg',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'production_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 54100,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'SUP')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'RIB280MST'],
            [
                'code' => 'RIB280MST',
                'name' => 'Rib 280 Misty (Abu-Abu) M71',
                'unit' => 'kg',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'production_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 56760,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'SUP')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'RIB280ABT'],
            [
                'code' => 'RIB280ABT',
                'name' => 'Rib 280 Abu Tua M81',
                'unit' => 'kg',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'production_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 57600,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'SUP')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'RIB280WHT'],
            [
                'code' => 'RIB280WHT',
                'name' => 'Rib 280 Putih',
                'unit' => 'kg',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'production_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 59150,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'SUP')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'RIB280BBL'],
            [
                'code' => 'RIB280BBL',
                'name' => 'Rib 280 Baby Blue',
                'unit' => 'kg',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'production_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 51800,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'BPU')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'SUP')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'TLKADDS'],
            [
                'code' => 'TLKADDS',
                'name' => 'Tali Karet Adidas',
                'unit' => 'kg',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'production_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'BPU')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'SUP')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'KRT4CM'],
            [
                'code' => 'KRT4CM',
                'name' => 'Karet 4 CM',
                'unit' => 'kg',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'production_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 36000,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'BPU')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'SUP')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'BNGJHT'],
            [
                'code' => 'BNGJHT',
                'name' => 'Benang Jahit',
                'unit' => 'pcs',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'production_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'PACK')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'PKG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'THR57X30'],
            [
                'code' => 'THR57X30',
                'name' => 'Kertas Thermal 57mm x 30mm',
                'unit' => 'roll',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'shipping_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 0,
                'default_allocation' => 'expense',
                'production_source' => null,
                'allow_negative' => 0,
                'is_stocked' => 0,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'PACK')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'PKG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'THR57X40'],
            [
                'code' => 'THR57X40',
                'name' => 'Kertas Thermal 57mm x 40mm',
                'unit' => 'roll',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'shipping_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 0,
                'default_allocation' => 'expense',
                'production_source' => null,
                'allow_negative' => 0,
                'is_stocked' => 0,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'PACK')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'PKG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'THR80X50'],
            [
                'code' => 'THR80X50',
                'name' => 'Kertas Thermal 80mm x 50mm',
                'unit' => 'roll',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'shipping_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 0,
                'default_allocation' => 'expense',
                'production_source' => null,
                'allow_negative' => 0,
                'is_stocked' => 0,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'PACK')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'SUP')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'OPP10X15'],
            [
                'code' => 'OPP10X15',
                'name' => 'Plastik OPP 10 x 15',
                'unit' => 'pack',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'production_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'PACK')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'SUP')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'OPP12X20'],
            [
                'code' => 'OPP12X20',
                'name' => 'Plastik OPP 12 x 20',
                'unit' => 'pack',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'production_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'PACK')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'SUP')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'OPP15X25'],
            [
                'code' => 'OPP15X25',
                'name' => 'Plastik OPP 15 x 25',
                'unit' => 'pack',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'production_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'PACK')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'SUP')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'OPP20X30'],
            [
                'code' => 'OPP20X30',
                'name' => 'Plastik OPP 20 x 30',
                'unit' => 'pack',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'production_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'PACK')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'PKG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'PLY20X30'],
            [
                'code' => 'PLY20X30',
                'name' => 'Polymailer 20 x 30',
                'unit' => 'pack',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'shipping_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 0,
                'default_allocation' => 'expense',
                'production_source' => null,
                'allow_negative' => 0,
                'is_stocked' => 0,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'PACK')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'PKG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'PLY25X35'],
            [
                'code' => 'PLY25X35',
                'name' => 'Polymailer 25 x 35',
                'unit' => 'pack',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'shipping_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 0,
                'default_allocation' => 'expense',
                'production_source' => null,
                'allow_negative' => 0,
                'is_stocked' => 0,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'PACK')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'PKG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'PLY30X40'],
            [
                'code' => 'PLY30X40',
                'name' => 'Polymailer 30 x 40',
                'unit' => 'pack',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'shipping_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 0,
                'default_allocation' => 'expense',
                'production_source' => null,
                'allow_negative' => 0,
                'is_stocked' => 0,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'RM')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'FLC240ABM'],
            [
                'code' => 'FLC240ABM',
                'name' => 'Fleece 240 Abu Muda M68',
                'unit' => 'kg',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'raw_material',
                'item_role_id' => $roleId,
                'last_purchase_price' => 56000,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LBP')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'BP2ABT'],
            [
                'code' => 'BP2ABT',
                'name' => 'Celana Panjang Baggy Ukuran XL Abu Tua',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 41500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LBP')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'BP1ABT'],
            [
                'code' => 'BP1ABT',
                'name' => 'Celana Panjang Baggy Ukuran L Abu Tua',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 41500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LBP')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'BP1MST'],
            [
                'code' => 'BP1MST',
                'name' => 'Celana Panjang Baggy Ukuran L Abu Misty M71',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 41500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LBP')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'BP2ABM'],
            [
                'code' => 'BP2ABM',
                'name' => 'Celana Panjang Baggy Ukuran XL Abu Muda M68',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 41500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LBP')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'BP2MST'],
            [
                'code' => 'BP2MST',
                'name' => 'Celana Panjang Baggy Ukuran XL Abu Misty M71',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 41500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LBP')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'BP1ABM'],
            [
                'code' => 'BP1ABM',
                'name' => 'Celana Panjang Baggy Ukuran L Abu Muda M68',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 41500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'RM')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'BP1BLK'],
            [
                'code' => 'BP1BLK',
                'name' => 'Celana Panjang Baggy Ukuran L Hitam',
                'unit' => 'pcs',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'raw_material',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 41500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LBP')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'BP2BLK'],
            [
                'code' => 'BP2BLK',
                'name' => 'Celana Panjang Baggy Ukuran XL Hitam',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 41500,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'RM')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'FLC240MST'],
            [
                'code' => 'FLC240MST',
                'name' => 'Fleece 240 Abu Misty M71',
                'unit' => 'pcs',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'raw_material',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'RM')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'FLC240ABT'],
            [
                'code' => 'FLC240ABT',
                'name' => 'Fleece 240 Abu Tua M68',
                'unit' => 'pcs',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'raw_material',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'MAT')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'RM')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'FLC240BLK'],
            [
                'code' => 'FLC240BLK',
                'name' => 'Fleece 240 Hitam',
                'unit' => 'pcs',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'raw_material',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TTB')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'TTB-BLK-L'],
            [
                'code' => 'TTB-BLK-L',
                'name' => 'Tracktop Hitam Garis 3 Tangan Ukuiran L',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 45000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TTB')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'TTB-BLK-M'],
            [
                'code' => 'TTB-BLK-M',
                'name' => 'Tracktop Hitam Garis 3 Tangan Ukuiran M',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 43000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TTB')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'TTB-BLK-XL'],
            [
                'code' => 'TTB-BLK-XL',
                'name' => 'Tracktop Hitam Garis 3 Tangan Ukuiran Xl',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 47000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TTB')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'TTB-BLK-XXL'],
            [
                'code' => 'TTB-BLK-XXL',
                'name' => 'Tracktop Hitam Garis 3 Tangan Ukuiran XXL',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 49000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TTB')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'TTB-WHT-M'],
            [
                'code' => 'TTB-WHT-M',
                'name' => 'Tracktop Putih Garis 3 Tangan Ukuiran M',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 43000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TTB')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'TTB-WHT-L'],
            [
                'code' => 'TTB-WHT-L',
                'name' => 'Tracktop Putih Garis 3 Tangan Ukuiran L',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 45000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TTB')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'TTB-WHT-XL'],
            [
                'code' => 'TTB-WHT-XL',
                'name' => 'Tracktop Putih Garis 3 Tangan Ukuiran XL',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 47000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TTB')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'TTB-WHT-XXL'],
            [
                'code' => 'TTB-WHT-XXL',
                'name' => 'Tracktop Putih Garis 3 Tangan Ukuiran XXL',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 49000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TTB')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'TTC-BLK-M'],
            [
                'code' => 'TTC-BLK-M',
                'name' => 'Tracktop Hitam Strip 2 Tangan Ukuran M',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TTB')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'TTC-BLK-L'],
            [
                'code' => 'TTC-BLK-L',
                'name' => 'Tracktop Hitam Strip 2 Tangan Ukuiran L',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TTB')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'TTC-BLK-XL'],
            [
                'code' => 'TTC-BLK-XL',
                'name' => 'Tracktop Hitam Strip 2 Tangan Ukuran XL',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'TTB')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'TTC-BLK-XXL'],
            [
                'code' => 'TTC-BLK-XXL',
                'name' => 'Tracktop Hitam Strip 2 Tangan Ukuiran XXL',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'LBP')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'LPS-ABT-M'],
            [
                'code' => 'LPS-ABT-M',
                'name' => 'Celana Panjang Loose Pants Abu Tua Ukuran M',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'BRD')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'B7BLK'],
            [
                'code' => 'B7BLK',
                'name' => 'Boardshort Parasit Hitam 7L',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 28000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'BRD')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'B5BLK'],
            [
                'code' => 'B5BLK',
                'name' => 'Boardshort Parasit Hitam 5L',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 28000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'in_house',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'BRD')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'FG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'B3BLK'],
            [
                'code' => 'B3BLK',
                'name' => 'Boardshort Parasit Hitam 3L',
                'unit' => 'pcs',
                'type' => 'finished_good',
                'item_category_id' => $catId,
                'item_role' => 'finished_good',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 28000,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => 'buy',
                'allow_negative' => 0,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'BPU')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'SUP')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'LBLSIZE'],
            [
                'code' => 'LBLSIZE',
                'name' => 'Label Size',
                'unit' => 'pcs',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'production_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 1,
                'default_allocation' => 'hpp',
                'production_source' => null,
                'allow_negative' => 1,
                'is_stocked' => 1,
            ]
        );
        $catId = DB::table('item_categories')->where('code', 'PACK')->value('id');
        $roleId = DB::table('item_roles')->where('code', 'PKG')->value('id');
        DB::table('items')->updateOrInsert(
            ['code' => 'THR100X150'],
            [
                'code' => 'THR100X150',
                'name' => 'Thermal 100mm x 150mm',
                'unit' => 'roll',
                'type' => 'material',
                'item_category_id' => $catId,
                'item_role' => 'shipping_supply',
                'item_role_id' => $roleId,
                'last_purchase_price' => 0,
                'hpp' => 0,
                'active' => 1,
                'affects_hpp' => 0,
                'default_allocation' => 'expense',
                'production_source' => null,
                'allow_negative' => 0,
                'is_stocked' => 0,
            ]
        );

    }
}
