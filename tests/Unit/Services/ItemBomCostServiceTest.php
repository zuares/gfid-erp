<?php

namespace Tests\Unit\Services;

use App\Models\Item;
use App\Models\ItemBom;
use App\Models\ItemBomLine;
use App\Services\Production\ItemBomCostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemBomCostServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_required_component_cost_with_scrap(): void
    {
        $bundle = Item::create([
            'code' => 'BUNDLE-COST-001',
            'name' => 'Bundle Cost Test',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'default_allocation' => 'hpp',
            'can_make' => true,
            'is_stocked' => true,
            'active' => true,
        ]);

        $material = Item::create([
            'code' => 'MAT-COST-001',
            'name' => 'Material Cost Test',
            'unit' => 'pcs',
            'type' => 'material',
            'hpp' => 20000,
            'base_unit_cost' => 20000,
            'default_allocation' => 'hpp',
            'is_stocked' => true,
            'active' => true,
        ]);

        $bom = ItemBom::create([
            'item_id' => $bundle->id,
            'name' => 'BOM Cost Test',
            'active' => true,
        ]);

        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
            'qty' => '5',
            'uom' => 'pcs',
            'scrap_pct' => '10',
            'is_optional' => false,
            'sort_order' => 10,
        ]);

        $estimate = app(ItemBomCostService::class)->estimate($bom);

        $this->assertSame(1, $estimate['line_count']);
        $this->assertEqualsWithDelta(5.5, $estimate['components'][0]['qty_with_scrap'], 0.000001);
        $this->assertEqualsWithDelta(20000, $estimate['components'][0]['unit_cost'], 0.000001);
        $this->assertEqualsWithDelta(110000, $estimate['total'], 0.000001);
        $this->assertEqualsWithDelta(11, $bom->lines()->first()->requiredQty(2), 0.000001);
    }

    public function test_optional_components_are_excluded_by_default(): void
    {
        $bundle = Item::create([
            'code' => 'BUNDLE-COST-002',
            'name' => 'Bundle Optional Cost Test',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'default_allocation' => 'hpp',
            'can_make' => true,
            'is_stocked' => true,
            'active' => true,
        ]);

        $material = Item::create([
            'code' => 'MAT-COST-002',
            'name' => 'Material Optional Cost Test',
            'unit' => 'pcs',
            'type' => 'material',
            'hpp' => 15000,
            'base_unit_cost' => 15000,
            'default_allocation' => 'hpp',
            'is_stocked' => true,
            'active' => true,
        ]);

        $bom = ItemBom::create([
            'item_id' => $bundle->id,
            'name' => 'BOM Optional Cost Test',
            'active' => true,
        ]);

        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_SEWING_SUPPLY,
            'qty' => '2',
            'uom' => 'pcs',
            'scrap_pct' => '0',
            'is_optional' => true,
            'sort_order' => 10,
        ]);

        $service = app(ItemBomCostService::class);

        $this->assertEqualsWithDelta(0, $service->total($bom), 0.000001);
        $this->assertEqualsWithDelta(30000, $service->total($bom, true), 0.000001);
    }
}
