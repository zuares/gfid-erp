<?php

namespace Tests\Feature\Production;

use App\Models\Item;
use App\Models\ItemBom;
use App\Models\ItemBomLine;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BundleAssemblyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_and_view_bundle_assembly_draft(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'ASM-OWNER',
        ]);
        $warehouse = Warehouse::create([
            'code' => 'ASM-UI-WH',
            'name' => 'Gudang Assembly UI',
            'active' => true,
        ]);
        $bundle = Item::create([
            'code' => 'BUNDLE-UI-001',
            'name' => 'Bundle UI Test',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'default_allocation' => 'hpp',
            'can_make' => true,
            'is_stocked' => true,
            'active' => true,
        ]);
        $material = Item::create([
            'code' => 'MAT-UI-001',
            'name' => 'Material UI Test',
            'unit' => 'pcs',
            'type' => 'material',
            'base_unit_cost' => 12000,
            'default_allocation' => 'hpp',
            'is_stocked' => true,
            'active' => true,
        ]);
        $bom = ItemBom::create([
            'item_id' => $bundle->id,
            'name' => 'BOM UI Test',
            'active' => true,
        ]);
        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
            'qty' => 2,
            'uom' => 'pcs',
            'scrap_pct' => 0,
            'is_optional' => false,
            'sort_order' => 10,
        ]);

        $this->actingAs($owner)
            ->get(route('production.bundle_assemblies.create'))
            ->assertOk()
            ->assertSee('Assembly Bundle Baru');

        $response = $this->actingAs($owner)->post(route('production.bundle_assemblies.store'), [
            'item_id' => $bundle->id,
            'warehouse_id' => $warehouse->id,
            'qty' => 5,
            'date' => '2026-08-28',
            'notes' => 'Draft UI test',
        ]);

        $assembly = \App\Models\BundleAssembly::query()->firstOrFail();

        $response->assertRedirect(route('production.bundle_assemblies.show', $assembly));
        $this->assertDatabaseHas('bundle_assemblies', [
            'id' => $assembly->id,
            'item_id' => $bundle->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
        ]);
        $this->assertDatabaseCount('inventory_mutations', 0);

        $this->actingAs($owner)
            ->get(route('production.bundle_assemblies.show', $assembly))
            ->assertOk()
            ->assertSee($assembly->code)
            ->assertSee('Draft belum mengubah stok');
    }
}
