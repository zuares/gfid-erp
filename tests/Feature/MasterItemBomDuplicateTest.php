<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemBom;
use App\Models\ItemBomLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterItemBomDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_bom_can_be_duplicated_from_edit_page_to_another_finished_good(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'BOM-DUPLICATE-OWNER',
        ]);

        $source = $this->makeFinishedGood('FG-DUP-SOURCE');
        $target = $this->makeFinishedGood('FG-DUP-TARGET');
        $material = Item::create([
            'code' => 'FAB-DUP-001',
            'name' => 'Kain Duplicate Test',
            'unit' => 'kg',
            'type' => 'material',
            'item_role' => 'raw_material',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'active' => true,
        ]);

        $sourceBom = ItemBom::create([
            'item_id' => $source->id,
            'name' => 'BOM Sumber Duplicate Test',
            'active' => true,
        ]);
        ItemBomLine::create([
            'item_bom_id' => $sourceBom->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
            'qty' => '0.375',
            'uom' => 'kg',
            'scrap_pct' => '2.5',
            'is_optional' => false,
            'sort_order' => 10,
        ]);

        $editResponse = $this->actingAs($owner)->get(route('master.item_boms.edit', $sourceBom));

        $editResponse->assertOk()
            ->assertSee('Duplikat BOM')
            ->assertSee(route('master.item_boms.duplicate_form', ['from_item_id' => $source->id]), false);

        $duplicateFormResponse = $this->actingAs($owner)->get(route('master.item_boms.duplicate_form', [
            'from_item_id' => $source->id,
        ]));

        $duplicateFormResponse->assertOk()
            ->assertSee($source->code . ' — ' . $source->name, false);

        $response = $this->actingAs($owner)->post(route('master.item_boms.duplicate'), [
            'from_item_id' => $source->id,
            'to_item_id' => $target->id,
            'mode' => 'replace',
            'copy_name' => 1,
            'activate' => 1,
        ]);

        $targetBom = $target->boms()->firstOrFail();

        $response->assertRedirect(route('master.item_boms.edit', $targetBom));
        $this->assertDatabaseHas('item_boms', [
            'id' => $targetBom->id,
            'name' => 'BOM Sumber Duplicate Test',
            'active' => 1,
        ]);
        $this->assertDatabaseHas('item_bom_lines', [
            'item_bom_id' => $targetBom->id,
            'material_item_id' => $material->id,
            'qty' => '0.375',
            'uom' => 'kg',
            'scrap_pct' => '2.50',
        ]);
    }

    private function makeFinishedGood(string $code): Item
    {
        return Item::create([
            'code' => $code,
            'name' => $code . ' Test',
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
    }
}
