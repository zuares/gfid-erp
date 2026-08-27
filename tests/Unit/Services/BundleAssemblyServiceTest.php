<?php

namespace Tests\Unit\Services;

use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\ItemBom;
use App\Models\ItemBomLine;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Services\Production\BundleAssemblyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BundleAssemblyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_previews_bundle_component_qty_and_cost(): void
    {
        $bundle = Item::create([
            'code' => 'BUNDLE-ASSEMBLY-001',
            'name' => 'Bundle Assembly Test',
            'unit' => 'pcs',
            'stock_unit' => 'pcs',
            'type' => 'finished_good',
            'default_allocation' => 'hpp',
            'can_make' => true,
            'is_stocked' => true,
            'active' => true,
        ]);

        $material = Item::create([
            'code' => 'MAT-ASSEMBLY-001',
            'name' => 'Material Assembly Test',
            'unit' => 'pcs',
            'stock_unit' => 'pcs',
            'type' => 'material',
            'hpp' => 20000,
            'base_unit_cost' => 20000,
            'default_allocation' => 'hpp',
            'is_stocked' => true,
            'active' => true,
        ]);

        $bom = ItemBom::create([
            'item_id' => $bundle->id,
            'name' => 'BOM Assembly Test',
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

        $preview = app(BundleAssemblyService::class)->preview($bundle, '10');

        $this->assertSame(10.0, $preview['assembly_qty']);
        $this->assertSame('pcs', $preview['stock_unit']);
        $this->assertEqualsWithDelta(55, $preview['components'][0]['qty_required'], 0.000001);
        $this->assertEqualsWithDelta(110000, $preview['unit_cost'], 0.000001);
        $this->assertEqualsWithDelta(1100000, $preview['total_cost'], 0.000001);
        $this->assertEqualsWithDelta(1100000, $preview['components'][0]['total_cost'], 0.000001);
    }

    public function test_it_rejects_assembly_without_an_active_bom(): void
    {
        $bundle = Item::create([
            'code' => 'BUNDLE-ASSEMBLY-002',
            'name' => 'Bundle Without BOM',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'default_allocation' => 'hpp',
            'can_make' => true,
            'is_stocked' => true,
            'active' => true,
        ]);

        $this->expectException(ValidationException::class);
        app(BundleAssemblyService::class)->preview($bundle, 1);
    }

    public function test_it_posts_and_voids_assembly_through_inventory_ledger(): void
    {
        $warehouse = Warehouse::create([
            'code' => 'ASM-WH-001',
            'name' => 'Assembly Warehouse',
            'type' => 'internal',
            'active' => true,
        ]);

        $bundle = Item::create([
            'code' => 'BUNDLE-ASSEMBLY-003',
            'name' => 'Bundle Posting Test',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'default_allocation' => 'hpp',
            'can_make' => true,
            'is_stocked' => true,
            'active' => true,
        ]);

        $material = Item::create([
            'code' => 'MAT-ASSEMBLY-003',
            'name' => 'Material Posting Test',
            'unit' => 'pcs',
            'type' => 'material',
            'default_allocation' => 'hpp',
            'is_stocked' => true,
            'active' => true,
        ]);

        $bom = ItemBom::create([
            'item_id' => $bundle->id,
            'name' => 'BOM Posting Test',
            'active' => true,
        ]);

        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_SEWING_SUPPLY,
            'qty' => '5',
            'uom' => 'pcs',
            'scrap_pct' => '0',
            'is_optional' => false,
            'sort_order' => 10,
        ]);

        app(InventoryService::class)->stockIn(
            warehouseId: $warehouse->id,
            itemId: $material->id,
            qty: 100,
            date: '2026-08-28',
            sourceType: 'test_receipt',
            unitCost: 20000,
            affectLotCost: false,
        );

        $service = app(BundleAssemblyService::class);
        $assembly = $service->createDraft($bundle, $warehouse->id, 10, '2026-08-28');

        $this->assertSame('draft', $assembly->status);
        $this->assertDatabaseHas('bundle_assembly_lines', [
            'bundle_assembly_id' => $assembly->id,
            'qty_required' => 50,
        ]);

        $posted = $service->post($assembly);

        $this->assertSame('posted', $posted->status);
        $this->assertEqualsWithDelta(50, (float) InventoryStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $material->id)
            ->value('qty'), 0.000001);
        $this->assertEqualsWithDelta(10, (float) InventoryStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $bundle->id)
            ->value('qty'), 0.000001);
        $this->assertEqualsWithDelta(1000000, (float) $posted->total_cost, 0.000001);
        $this->assertDatabaseHas('inventory_mutations', [
            'source_type' => 'bundle_assembly',
            'source_id' => $assembly->id,
            'item_id' => $bundle->id,
            'qty_change' => 10,
        ]);

        $voided = $service->void($posted);

        $this->assertSame('void', $voided->status);
        $this->assertEqualsWithDelta(100, (float) InventoryStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $material->id)
            ->value('qty'), 0.000001);
        $this->assertEqualsWithDelta(0, (float) InventoryStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $bundle->id)
            ->value('qty'), 0.000001);
        $this->assertDatabaseHas('inventory_mutations', [
            'source_type' => 'bundle_assembly_void',
            'source_id' => $assembly->id,
            'item_id' => $bundle->id,
            'qty_change' => -10,
        ]);
    }
}
