<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemBomSeeder extends Seeder
{
    public function run(): void
    {
        // BOM: BOM BP1ABM
        $fgId = DB::table('items')->where('code', 'BP1ABM')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM BP1ABM', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM BP1ABM', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC240ABM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM BP1ABT
        $fgId = DB::table('items')->where('code', 'BP1ABT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM BP1ABT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM BP1ABT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM BP1MST
        $fgId = DB::table('items')->where('code', 'BP1MST')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM BP1MST', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM BP1MST', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM BP2ABM
        $fgId = DB::table('items')->where('code', 'BP2ABM')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM BP2ABM', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM BP2ABM', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC240ABM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM BP2ABT
        $fgId = DB::table('items')->where('code', 'BP2ABT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM BP2ABT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM BP2ABT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM BP2BLK
        $fgId = DB::table('items')->where('code', 'BP2BLK')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM BP2BLK', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM BP2BLK', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM BP2MST
        $fgId = DB::table('items')->where('code', 'BP2MST')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM BP2MST', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM BP2MST', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM C5BLK
        $fgId = DB::table('items')->where('code', 'C5BLK')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM C5BLK', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM C5BLK', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM C5MST
        $fgId = DB::table('items')->where('code', 'C5MST')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM C5MST', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM C5MST', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM C5NVY
        $fgId = DB::table('items')->where('code', 'C5NVY')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM C5NVY', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM C5NVY', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM C7BLK
        $fgId = DB::table('items')->where('code', 'C7BLK')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM C7BLK', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM C7BLK', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM C7MST
        $fgId = DB::table('items')->where('code', 'C7MST')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM C7MST', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM C7MST', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM C7NVY
        $fgId = DB::table('items')->where('code', 'C7NVY')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM C7NVY', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM C7NVY', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM J3ABT
        $fgId = DB::table('items')->where('code', 'J3ABT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM J3ABT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM J3ABT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM J3BLK
        $fgId = DB::table('items')->where('code', 'J3BLK')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM J3BLK', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM J3BLK', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM J3MST
        $fgId = DB::table('items')->where('code', 'J3MST')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM J3MST', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM J3MST', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM J3NVY
        $fgId = DB::table('items')->where('code', 'J3NVY')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM J3NVY', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM J3NVY', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM J5ABT
        $fgId = DB::table('items')->where('code', 'J5ABT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM J5ABT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM J5ABT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM J5BLK
        $fgId = DB::table('items')->where('code', 'J5BLK')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM J5BLK', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM J5BLK', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM J5MST
        $fgId = DB::table('items')->where('code', 'J5MST')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM J5MST', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM J5MST', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM J5NVY
        $fgId = DB::table('items')->where('code', 'J5NVY')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM J5NVY', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM J5NVY', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM J7ABT
        $fgId = DB::table('items')->where('code', 'J7ABT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM J7ABT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM J7ABT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM J7BLK
        $fgId = DB::table('items')->where('code', 'J7BLK')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM J7BLK', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM J7BLK', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM J7MST
        $fgId = DB::table('items')->where('code', 'J7MST')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM J7MST', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM J7MST', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM J7NVY
        $fgId = DB::table('items')->where('code', 'J7NVY')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM J7NVY', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM J7NVY', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K1ABT
        $fgId = DB::table('items')->where('code', 'K1ABT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K1ABT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K1ABT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K1BBL
        $fgId = DB::table('items')->where('code', 'K1BBL')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K1BBL', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K1BBL', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BBL')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BBL')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K1BLK
        $fgId = DB::table('items')->where('code', 'K1BLK')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K1BLK', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K1BLK', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K1MST
        $fgId = DB::table('items')->where('code', 'K1MST')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K1MST', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K1MST', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K1NVY
        $fgId = DB::table('items')->where('code', 'K1NVY')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K1NVY', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K1NVY', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K1WHT
        $fgId = DB::table('items')->where('code', 'K1WHT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K1WHT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K1WHT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280WHT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280WHT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K2ABT
        $fgId = DB::table('items')->where('code', 'K2ABT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K2ABT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K2ABT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K2BBL
        $fgId = DB::table('items')->where('code', 'K2BBL')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K2BBL', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K2BBL', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BBL')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BBL')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K2BLK
        $fgId = DB::table('items')->where('code', 'K2BLK')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K2BLK', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K2BLK', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K2MST
        $fgId = DB::table('items')->where('code', 'K2MST')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K2MST', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K2MST', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K2NVY
        $fgId = DB::table('items')->where('code', 'K2NVY')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K2NVY', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K2NVY', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K2WHT
        $fgId = DB::table('items')->where('code', 'K2WHT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K2WHT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K2WHT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280WHT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280WHT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K3ABT
        $fgId = DB::table('items')->where('code', 'K3ABT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K3ABT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K3ABT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K3BBL
        $fgId = DB::table('items')->where('code', 'K3BBL')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K3BBL', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K3BBL', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BBL')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BBL')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K3BLK
        $fgId = DB::table('items')->where('code', 'K3BLK')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K3BLK', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K3BLK', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K3MST
        $fgId = DB::table('items')->where('code', 'K3MST')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K3MST', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K3MST', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K3NVY
        $fgId = DB::table('items')->where('code', 'K3NVY')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K3NVY', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K3NVY', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K3WHT
        $fgId = DB::table('items')->where('code', 'K3WHT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K3WHT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K3WHT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280WHT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280WHT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K5ABT
        $fgId = DB::table('items')->where('code', 'K5ABT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K5ABT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K5ABT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K5BBL
        $fgId = DB::table('items')->where('code', 'K5BBL')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K5BBL', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K5BBL', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BBL')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BBL')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K5BLK
        $fgId = DB::table('items')->where('code', 'K5BLK')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K5BLK', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K5BLK', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K5MST
        $fgId = DB::table('items')->where('code', 'K5MST')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K5MST', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K5MST', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K5NVY
        $fgId = DB::table('items')->where('code', 'K5NVY')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K5NVY', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K5NVY', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K5WHT
        $fgId = DB::table('items')->where('code', 'K5WHT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K5WHT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K5WHT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280WHT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280WHT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K7ABT
        $fgId = DB::table('items')->where('code', 'K7ABT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K7ABT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K7ABT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K7BBL
        $fgId = DB::table('items')->where('code', 'K7BBL')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K7BBL', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K7BBL', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BBL')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BBL')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K7BLK
        $fgId = DB::table('items')->where('code', 'K7BLK')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K7BLK', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K7BLK', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K7MST
        $fgId = DB::table('items')->where('code', 'K7MST')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K7MST', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K7MST', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K7NVY
        $fgId = DB::table('items')->where('code', 'K7NVY')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K7NVY', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K7NVY', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM K7WHT
        $fgId = DB::table('items')->where('code', 'K7WHT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM K7WHT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM K7WHT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280WHT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280WHT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM L1ABT
        $fgId = DB::table('items')->where('code', 'L1ABT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM L1ABT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM L1ABT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM L1BLK
        $fgId = DB::table('items')->where('code', 'L1BLK')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM L1BLK', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM L1BLK', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM L1MST
        $fgId = DB::table('items')->where('code', 'L1MST')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM L1MST', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM L1MST', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM L1NVY
        $fgId = DB::table('items')->where('code', 'L1NVY')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM L1NVY', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM L1NVY', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM L2ABT
        $fgId = DB::table('items')->where('code', 'L2ABT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM L2ABT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM L2ABT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM L2BLK
        $fgId = DB::table('items')->where('code', 'L2BLK')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM L2BLK', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM L2BLK', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM L2MST
        $fgId = DB::table('items')->where('code', 'L2MST')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM L2MST', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM L2MST', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM L2NVY
        $fgId = DB::table('items')->where('code', 'L2NVY')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM L2NVY', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM L2NVY', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM T1ABT
        $fgId = DB::table('items')->where('code', 'T1ABT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM T1ABT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM T1ABT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM T1BLK
        $fgId = DB::table('items')->where('code', 'T1BLK')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM T1BLK', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM T1BLK', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM T1MST
        $fgId = DB::table('items')->where('code', 'T1MST')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM T1MST', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM T1MST', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM T1NVY
        $fgId = DB::table('items')->where('code', 'T1NVY')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM T1NVY', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM T1NVY', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM T2ABT
        $fgId = DB::table('items')->where('code', 'T2ABT')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM T2ABT', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM T2ABT', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280ABT')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM T2BLK
        $fgId = DB::table('items')->where('code', 'T2BLK')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM T2BLK', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM T2BLK', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM T2MST
        $fgId = DB::table('items')->where('code', 'T2MST')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM T2MST', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM T2MST', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280MST')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM T2NVY
        $fgId = DB::table('items')->where('code', 'T2NVY')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM T2NVY', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM T2NVY', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.31, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280NVY')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.06, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.019, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.009, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }
        // BOM: BOM LPS-ABT-M
        $fgId = DB::table('items')->where('code', 'LPS-ABT-M')->value('id');
        if ($fgId) {
            $bomId = DB::table('item_boms')->where('item_id', $fgId)->value('id');
            if (!$bomId) {
                $bomId = DB::table('item_boms')->insertGetId(['item_id' => $fgId, 'name' => 'BOM LPS-ABT-M', 'active' => 1]);
            } else {
                DB::table('item_boms')->where('id', $bomId)->update(['name' => 'BOM LPS-ABT-M', 'active' => 1]);
            }
            $matId = DB::table('items')->where('code', 'FLC280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.45, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 10, 'usage_stage' => 'main_material']
            );
            $matId = DB::table('items')->where('code', 'RIB280BLK')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.07, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 20, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'KRT4CM')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.022, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 30, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'LBLSIZE')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 1, 'uom' => 'pcs', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 35, 'usage_stage' => 'sewing_supply']
            );
            $matId = DB::table('items')->where('code', 'TLKADDS')->value('id');
            if ($matId) DB::table('item_bom_lines')->updateOrInsert(
                ['item_bom_id' => $bomId, 'material_item_id' => $matId],
                ['item_bom_id' => $bomId, 'material_item_id' => $matId, 'qty' => 0.01, 'uom' => 'kg', 'scrap_pct' => 0, 'is_optional' => 0, 'sort_order' => 40, 'usage_stage' => 'packing_supply']
            );
        }

    }
}
