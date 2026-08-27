<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemBomLine;
use App\Models\ItemCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterItemBomTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'BOM-OWNER',
        ]);
    }

    public function test_accessory_material_can_be_selected_and_saved_in_bom(): void
    {
        $category = ItemCategory::create([
            'code' => 'ACC-TEST',
            'name' => 'Accessories Test',
            'kind' => 'accessory',
            'active' => true,
        ]);

        $finishedGood = Item::create([
            'code' => 'FG-BOM-001',
            'name' => 'Produk BOM Test',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_role' => 'finished_good',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'can_buy' => true,
            'can_make' => true,
            'default_supply_source' => 'make',
            'active' => true,
        ]);

        $accessory = Item::create([
            'code' => 'ZIP-BOM-001',
            'name' => 'Resleting Accessories Test',
            'unit' => 'pcs',
            'type' => 'material',
            'item_category_id' => $category->id,
            'item_role' => 'production_supply',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'active' => true,
        ]);

        $searchResponse = $this->actingAs($this->owner)->get(route('master.item_boms.ajax_items', [
            'type' => 'material',
            'q' => 'ZIP-BOM-001',
        ]));

        $searchResponse->assertOk()->assertJsonFragment([
            'id' => $accessory->id,
            'text' => 'ZIP-BOM-001 — Resleting Accessories Test',
        ]);

        $response = $this->actingAs($this->owner)->post(route('master.item_boms.store'), [
            'item_id' => $finishedGood->id,
            'name' => 'BOM Produk Test',
            'active' => 1,
            'lines' => [[
                'material_item_id' => $accessory->id,
                'usage_stage' => ItemBomLine::STAGE_SEWING_SUPPLY,
                'qty' => '1',
                'uom' => 'pcs',
                'scrap_pct' => '0',
                'is_optional' => 0,
                'sort_order' => 10,
            ]],
        ]);

        $bom = $finishedGood->boms()->firstOrFail();

        $response->assertRedirect(route('master.item_boms.edit', $bom));
        $this->assertDatabaseHas('item_bom_lines', [
            'item_bom_id' => $bom->id,
            'material_item_id' => $accessory->id,
            'usage_stage' => ItemBomLine::STAGE_SEWING_SUPPLY,
        ]);
    }

    public function test_bom_rejects_the_parent_item_as_a_component(): void
    {
        $finishedGood = Item::create([
            'code' => 'FG-BOM-SELF-001',
            'name' => 'Produk BOM Self Reference',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_role' => 'finished_good',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'can_buy' => true,
            'can_make' => true,
            'default_supply_source' => 'make',
            'active' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->from(route('master.item_boms.create'))
            ->post(route('master.item_boms.store'), [
                'item_id' => $finishedGood->id,
                'name' => 'BOM Self Reference',
                'active' => 1,
                'lines' => [[
                    'material_item_id' => $finishedGood->id,
                    'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
                    'qty' => '1',
                    'uom' => 'pcs',
                    'scrap_pct' => '0',
                    'is_optional' => 0,
                    'sort_order' => 10,
                ]],
            ]);

        $response->assertRedirect(route('master.item_boms.create'));
        $response->assertSessionHasErrors('lines');
        $this->assertDatabaseCount('item_boms', 0);
    }
}
