<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemBom;
use App\Models\ItemBomLine;
use Illuminate\Database\Seeder;

class ItemBomSeeder extends Seeder
{
    public function run(): void
    {
        $fg = Item::where('code', 'C5BLK')->first();
        if (!$fg) {
            return;
        }

        $bom = ItemBom::updateOrCreate(
            ['item_id' => $fg->id],
            ['name' => 'BOM ' . $fg->code, 'active' => true]
        );

        $materials = [
            ['code' => 'FLC280BLK', 'qty' => 1.20, 'uom' => 'pcs', 'scrap_pct' => 2.00, 'sort' => 10],
            ['code' => 'RIB280BLK', 'qty' => 0.25, 'uom' => 'pcs', 'scrap_pct' => 2.00, 'sort' => 20],
            ['code' => 'TLKADDS', 'qty' => 1.00, 'uom' => 'pcs', 'scrap_pct' => 0.00, 'sort' => 30],
            ['code' => 'KRT4CM', 'qty' => 1.00, 'uom' => 'pcs', 'scrap_pct' => 0.00, 'sort' => 40],
            ['code' => 'BNGJHT', 'qty' => 0.10, 'uom' => 'pcs', 'scrap_pct' => 0.00, 'sort' => 50],
        ];

        // bersihin dulu lines biar seeder idempotent
        ItemBomLine::where('item_bom_id', $bom->id)->delete();

        foreach ($materials as $m) {
            $mat = Item::where('code', $m['code'])->first();
            if (!$mat) {
                continue;
            }

            ItemBomLine::create([
                'item_bom_id' => $bom->id,
                'material_item_id' => $mat->id,
                'qty' => $m['qty'],
                'uom' => $m['uom'],
                'scrap_pct' => $m['scrap_pct'],
                'is_optional' => false,
                'sort_order' => $m['sort'],
            ]);
        }
    }
}
