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

    public function test_only_the_most_expensive_optional_component_is_included_by_default(): void
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

        $cheaperMaterial = Item::create([
            'code' => 'MAT-COST-002',
            'name' => 'Material Optional Cheaper',
            'unit' => 'pcs',
            'type' => 'material',
            'hpp' => 15000,
            'base_unit_cost' => 15000,
            'default_allocation' => 'hpp',
            'is_stocked' => true,
            'active' => true,
        ]);

        $expensiveMaterial = Item::create([
            'code' => 'MAT-COST-003',
            'name' => 'Material Optional Expensive',
            'unit' => 'pcs',
            'type' => 'material',
            'hpp' => 12000,
            'base_unit_cost' => 12000,
            'default_allocation' => 'hpp',
            'is_stocked' => true,
            'active' => true,
        ]);

        $bom = ItemBom::create([
            'item_id' => $bundle->id,
            'name' => 'BOM Optional Cost Test',
            'active' => true,
        ]);

        $cheaperLine = ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $cheaperMaterial->id,
            'usage_stage' => ItemBomLine::STAGE_SEWING_SUPPLY,
            'qty' => '2',
            'uom' => 'pcs',
            'scrap_pct' => '0',
            'is_optional' => true,
            'sort_order' => 10,
        ]);

        $expensiveLine = ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $expensiveMaterial->id,
            'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
            'qty' => '3',
            'uom' => 'pcs',
            'scrap_pct' => '0',
            'is_optional' => true,
            'sort_order' => 20,
        ]);

        $service = app(ItemBomCostService::class);

        $estimate = $service->estimate($bom);
        $components = collect($estimate['components'])->keyBy('line_id');

        $this->assertEqualsWithDelta(36000, $estimate['total'], 0.000001);
        $this->assertSame($expensiveLine->id, $estimate['selected_optional_line_id']);
        $this->assertSame(1, $estimate['included_optional_line_count']);
        $this->assertFalse($components[$cheaperLine->id]['included_in_estimate']);
        $this->assertTrue($components[$expensiveLine->id]['included_in_estimate']);
        $this->assertEqualsWithDelta(66000, $service->total($bom, true), 0.000001);
    }
}
