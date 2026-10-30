<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemBom;
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

    public function test_bom_can_save_multiple_main_materials(): void
    {
        $finishedGood = Item::create([
            'code' => 'FG-BOM-MULTI-001',
            'name' => 'Produk BOM Multi Material Test',
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

        $fabricA = Item::create([
            'code' => 'FAB-MULTI-A',
            'name' => 'Bahan Baku A',
            'unit' => 'kg',
            'type' => 'material',
            'item_role' => 'raw_material',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'active' => true,
        ]);

        $fabricB = Item::create([
            'code' => 'FAB-MULTI-B',
            'name' => 'Bahan Baku B',
            'unit' => 'kg',
            'type' => 'material',
            'item_role' => 'raw_material',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'active' => true,
        ]);

        $response = $this->actingAs($this->owner)->post(route('master.item_boms.store'), [
            'item_id' => $finishedGood->id,
            'name' => 'BOM Multi Material Test',
            'active' => 1,
            'lines' => [
                [
                    'material_item_id' => $fabricA->id,
                    'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
                    'qty' => '0.7',
                    'uom' => 'kg',
                    'scrap_pct' => '0',
                    'is_optional' => 0,
                    'sort_order' => 10,
                ],
                [
                    'material_item_id' => $fabricB->id,
                    'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
                    'qty' => '0.3',
                    'uom' => 'kg',
                    'scrap_pct' => '0',
                    'is_optional' => 0,
                    'sort_order' => 20,
                ],
            ],
        ]);

        $bom = $finishedGood->boms()->firstOrFail();

        $response->assertRedirect(route('master.item_boms.edit', $bom));
        $this->assertDatabaseCount('item_bom_lines', 2);
        $this->assertDatabaseHas('item_bom_lines', [
            'item_bom_id' => $bom->id,
            'material_item_id' => $fabricA->id,
            'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
        ]);
        $this->assertDatabaseHas('item_bom_lines', [
            'item_bom_id' => $bom->id,
            'material_item_id' => $fabricB->id,
            'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
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

    public function test_bom_search_matches_item_sku_and_bom_name(): void
    {
        $finishedGood = Item::create([
            'code' => 'FG-BOM-SEARCH-001',
            'sku' => 'SHOP-SKU-SEARCH-001',
            'name' => 'Produk Pencarian BOM',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_role' => 'finished_good',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'can_make' => true,
            'default_supply_source' => 'make',
            'active' => true,
        ]);

        $bom = ItemBom::create([
            'item_id' => $finishedGood->id,
            'name' => 'Resep khusus pencarian',
            'active' => true,
        ]);

        $this->actingAs($this->owner)
            ->get(route('master.item_boms.index', ['q' => 'SHOP-SKU-SEARCH-001']))
            ->assertOk()
            ->assertSee($finishedGood->code)
            ->assertSee($finishedGood->name);

        $this->actingAs($this->owner)
            ->get(route('master.item_boms.index', ['q' => 'khusus pencarian']))
            ->assertOk()
            ->assertSee($finishedGood->code);

        $this->actingAs($this->owner)
            ->get(route('master.item_boms.ajax_items', [
                'type' => 'finished_good',
                'q' => 'SHOP-SKU-SEARCH-001',
            ]))
            ->assertOk()
            ->assertJsonFragment(['id' => $finishedGood->id]);
    }

    public function test_existing_bom_can_be_edited_with_a_small_quantity(): void
    {
        $finishedGood = Item::create([
            'code' => 'FG-BOM-EDIT-001',
            'name' => 'Produk Edit BOM',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_role' => 'finished_good',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'can_make' => true,
            'default_supply_source' => 'make',
            'active' => true,
        ]);
        $material = Item::create([
            'code' => 'FAB-BOM-EDIT-001',
            'name' => 'Bahan Edit BOM',
            'unit' => 'kg',
            'type' => 'material',
            'item_role' => 'raw_material',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'active' => true,
        ]);

        $bom = ItemBom::create([
            'item_id' => $finishedGood->id,
            'name' => 'BOM sebelum edit',
            'active' => true,
        ]);
        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
            'qty' => '0.0005',
            'uom' => 'kg',
            'scrap_pct' => '0',
            'is_optional' => false,
            'sort_order' => 10,
        ]);

        $response = $this->actingAs($this->owner)
            ->put(route('master.item_boms.update', $bom), [
                'name' => 'BOM setelah edit',
                'active' => 1,
                'lines' => [[
                    'material_item_id' => $material->id,
                    'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
                    'qty' => '0.0005',
                    'uom' => 'kg',
                    'scrap_pct' => '15',
                    'is_optional' => 0,
                    'sort_order' => 10,
                ]],
            ]);

        $response->assertRedirect(route('master.item_boms.edit', $bom));
        $this->assertSame('BOM setelah edit', $bom->fresh()->name);
        $this->assertDatabaseHas('item_bom_lines', [
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'qty' => '0.0005',
            'scrap_pct' => '15.000',
        ]);
    }

    public function test_new_bom_line_uses_zero_percent_waste_by_default(): void
    {
        $this->actingAs($this->owner)
            ->get(route('master.item_boms.create'))
            ->assertOk()
            ->assertSee('Waste (%)')
            ->assertSee('Waste default 0%')
            ->assertSee('name="lines[0][scrap_pct]"', false)
            ->assertSee('value="0"', false)
            ->assertSee('data-max-decimals="3"', false)
            ->assertSee('placeholder="0,000"', false)
            ->assertSee('toFixed(3)', false);
    }

    public function test_bom_form_does_not_reorder_rows_when_group_changes(): void
    {
        $this->actingAs($this->owner)
            ->get(route('master.item_boms.create'))
            ->assertOk()
            ->assertDontSee('function reorderLines', false)
            ->assertDontSee('stageRank(', false);
    }
}
