<?php

namespace Tests\Unit\Services;

use App\Models\Item;
use App\Models\ItemBom;
use App\Models\ItemBomLine;
use App\Models\ItemCostSnapshot;
use App\Models\Employee;
use App\Models\PieceRate;
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
            'stock_unit' => 'pcs',
            'purchase_unit' => 'pack',
            'purchase_conversion_factor' => 5,
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
        $this->assertSame('pcs', $estimate['components'][0]['stock_unit']);
        $this->assertSame('pack', $estimate['components'][0]['purchase_unit']);
        $this->assertEqualsWithDelta(100000, $estimate['components'][0]['purchase_unit_cost'], 0.000001);
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

    public function test_bom_uses_master_purchase_price_converted_to_stock_unit(): void
    {
        $bundle = Item::create([
            'code' => 'BUNDLE-COST-MASTER-001',
            'name' => 'Bundle Master Unit Test',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'default_allocation' => 'hpp',
            'can_make' => true,
            'is_stocked' => true,
            'active' => true,
        ]);

        $opp = Item::create([
            'code' => 'OPP-MASTER-001',
            'name' => 'Plastik OPP Master Price',
            'unit' => 'pcs',
            'stock_unit' => 'pcs',
            'purchase_unit' => 'pack',
            'purchase_conversion_factor' => 500,
            'last_purchase_price' => 80000,
            'hpp' => 0,
            'base_unit_cost' => 0,
            'type' => 'material',
        ]);

        $bom = ItemBom::create([
            'item_id' => $bundle->id,
            'name' => 'BOM Master Unit Test',
            'active' => true,
        ]);

        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $opp->id,
            'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
            'qty' => '1',
            'uom' => 'pcs',
            'scrap_pct' => '0',
            'is_optional' => false,
            'sort_order' => 10,
        ]);

        $estimate = app(ItemBomCostService::class)->estimate($bom);

        $this->assertEqualsWithDelta(160, $estimate['components'][0]['unit_cost'], 0.000001);
        $this->assertEqualsWithDelta(80000, $estimate['components'][0]['purchase_unit_cost'], 0.000001);
        $this->assertEqualsWithDelta(160, $estimate['total'], 0.000001);
    }

    public function test_active_production_wage_snapshot_is_added_to_bom_cost(): void
    {
        $bundle = Item::create([
            'code' => 'BUNDLE-COST-WAGE-001',
            'name' => 'Bundle Wage Cost Test',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'default_allocation' => 'hpp',
            'can_make' => true,
            'is_stocked' => true,
            'active' => true,
        ]);

        $material = Item::create([
            'code' => 'MAT-COST-WAGE-001',
            'name' => 'Material Wage Cost Test',
            'unit' => 'pcs',
            'type' => 'material',
            'hpp' => 10000,
            'base_unit_cost' => 10000,
            'default_allocation' => 'hpp',
            'is_stocked' => true,
            'active' => true,
        ]);

        $bom = ItemBom::create([
            'item_id' => $bundle->id,
            'name' => 'BOM Wage Cost Test',
            'active' => true,
        ]);

        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
            'qty' => '2',
            'uom' => 'pcs',
            'scrap_pct' => '0',
            'is_optional' => false,
            'sort_order' => 10,
        ]);

        $snapshot = ItemCostSnapshot::create([
            'item_id' => $bundle->id,
            'warehouse_id' => null,
            'snapshot_date' => '2026-11-01',
            'reference_type' => 'production_cost_period',
            'reference_id' => 1,
            'qty_basis' => 100,
            'rm_unit_cost' => 20000,
            'cutting_unit_cost' => 1000,
            'sewing_unit_cost' => 2500,
            'finishing_unit_cost' => 500,
            'packaging_unit_cost' => 0,
            'overhead_unit_cost' => 0,
            'unit_cost' => 24000,
            'is_active' => true,
        ]);

        $estimate = app(ItemBomCostService::class)->estimate($bom);

        $this->assertEqualsWithDelta(20000, $estimate['material_total'], 0.000001);
        $this->assertEqualsWithDelta(4000, $estimate['production_wage_total'], 0.000001);
        $this->assertEqualsWithDelta(24000, $estimate['total'], 0.000001);
        $this->assertSame($snapshot->id, $estimate['production_wage']['snapshot_id']);
    }

    public function test_active_piece_rates_are_used_when_payroll_snapshot_is_not_available(): void
    {
        $bundle = Item::create([
            'code' => 'BUNDLE-COST-RATE-001',
            'name' => 'Bundle Piece Rate Cost Test',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
        ]);
        $material = Item::create([
            'code' => 'MAT-COST-RATE-001',
            'name' => 'Material Piece Rate Cost Test',
            'unit' => 'pcs',
            'type' => 'material',
            'hpp' => 20000,
            'base_unit_cost' => 20000,
            'active' => true,
        ]);
        $bom = ItemBom::create([
            'item_id' => $bundle->id,
            'name' => 'BOM Piece Rate Cost Test',
            'active' => true,
        ]);
        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
            'qty' => '1',
            'uom' => 'pcs',
            'scrap_pct' => '0',
            'is_optional' => false,
            'sort_order' => 10,
        ]);

        $cuttingOperator = Employee::create([
            'code' => 'RATE-CUT-001',
            'name' => 'Operator Cutting Rate Test',
            'role' => 'cutting',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        $sewingOperator = Employee::create([
            'code' => 'RATE-SEW-001',
            'name' => 'Operator Sewing Rate Test',
            'role' => 'sewing',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        PieceRate::create([
            'module' => 'cutting',
            'employee_id' => $cuttingOperator->id,
            'item_id' => $bundle->id,
            'rate_per_pcs' => 1000,
            'effective_from' => now()->subDay()->toDateString(),
        ]);
        PieceRate::create([
            'module' => 'sewing',
            'employee_id' => $sewingOperator->id,
            'item_id' => $bundle->id,
            'rate_per_pcs' => 4000,
            'effective_from' => now()->subDay()->toDateString(),
        ]);

        $estimate = app(ItemBomCostService::class)->estimate($bom);

        $this->assertSame('piece_rate_estimate', $estimate['production_wage']['source']);
        $this->assertTrue($estimate['production_wage']['is_estimate']);
        $this->assertEqualsWithDelta(1000, $estimate['production_wage']['cutting'], 0.000001);
        $this->assertEqualsWithDelta(4000, $estimate['production_wage']['sewing'], 0.000001);
        $this->assertEqualsWithDelta(5000, $estimate['production_wage_total'], 0.000001);
        $this->assertEqualsWithDelta(25000, $estimate['total'], 0.000001);
    }
}
