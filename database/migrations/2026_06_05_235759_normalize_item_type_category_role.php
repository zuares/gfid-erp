<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $roleIds = DB::table('item_roles')->pluck('id', 'code');
            $categoryIds = DB::table('item_categories')->pluck('id', 'code');

            $rmId = $roleIds['RM'] ?? null;
            $supId = $roleIds['SUP'] ?? null;
            $pkgId = $roleIds['PKG'] ?? null;
            $fgId = $roleIds['FG'] ?? null;
            $matCategoryId = $categoryIds['MAT'] ?? null;

            if ($matCategoryId) {
                DB::table('items')
                    ->where('type', 'material')
                    ->whereIn('item_category_id', function ($q) {
                        $q->select('id')
                            ->from('item_categories')
                            ->where('kind', 'product');
                    })
                    ->update([
                        'item_category_id' => $matCategoryId,
                        'updated_at' => now(),
                    ]);
            }

            if ($fgId) {
                DB::table('items')
                    ->where('type', 'finished_good')
                    ->update([
                        'item_role_id' => $fgId,
                        'item_role' => 'finished_good',
                        'is_stocked' => 1,
                        'hpp_behavior' => 'hpp',
                        'updated_at' => now(),
                    ]);
            }

            if ($rmId) {
                DB::table('items as i')
                    ->join('item_categories as c', 'c.id', '=', 'i.item_category_id')
                    ->where('i.type', 'material')
                    ->where('c.kind', 'material')
                    ->update([
                        'item_role_id' => $rmId,
                        'item_role' => 'raw_material',
                        'is_stocked' => 1,
                        'hpp_behavior' => 'hpp',
                        'updated_at' => now(),
                    ]);
            }

            if ($supId) {
                DB::table('items as i')
                    ->join('item_categories as c', 'c.id', '=', 'i.item_category_id')
                    ->where('i.type', 'material')
                    ->whereIn('c.kind', ['support', 'accessory'])
                    ->update([
                        'item_role_id' => $supId,
                        'item_role' => 'production_supply',
                        'is_stocked' => 1,
                        'hpp_behavior' => 'hpp',
                        'updated_at' => now(),
                    ]);
            }

            if ($pkgId) {
                DB::table('items as i')
                    ->join('item_categories as c', 'c.id', '=', 'i.item_category_id')
                    ->where('i.type', 'material')
                    ->where('c.kind', 'packaging')
                    ->update([
                        'item_role_id' => $pkgId,
                        'item_role' => 'shipping_supply',
                        'is_stocked' => 0,
                        'hpp_behavior' => 'non_hpp',
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    public function down(): void
    {
        // Data normalization is intentionally not reversed.
    }
};
