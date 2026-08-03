<?php

use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('excludes RM warehouse from stock value on inventory stock items page', function () {
    $user = User::factory()->create([
        'name' => 'Owner Test',
        'employee_code' => 'OWNTEST-RM',
        'role' => 'owner',
    ]);

    $item = Item::create([
        'code' => 'TEST-RM-VALUE',
        'name' => 'Test Item',
        'unit' => 'pcs',
        'type' => 'material',
        'hpp' => 1000,
        'active' => true,
    ]);

    $rmWarehouse = Warehouse::create([
        'code' => 'RM',
        'name' => 'Gudang RM',
        'type' => 'raw_material',
        'active' => true,
    ]);

    $fgWarehouse = Warehouse::create([
        'code' => 'FG',
        'name' => 'Gudang FG',
        'type' => 'fg',
        'active' => true,
    ]);

    InventoryMutation::create([
        'date' => now()->toDateString(),
        'warehouse_id' => $rmWarehouse->id,
        'item_id' => $item->id,
        'qty_change' => 5,
        'direction' => 'in',
        'source_type' => 'adjustment',
        'source_id' => 1,
    ]);

    InventoryMutation::create([
        'date' => now()->toDateString(),
        'warehouse_id' => $fgWarehouse->id,
        'item_id' => $item->id,
        'qty_change' => 3,
        'direction' => 'in',
        'source_type' => 'adjustment',
        'source_id' => 2,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->get(route('inventory.stocks.items'));

    $response->assertOk();
    $response->assertJsonPath('rows.0.total_qty', 8);
    $response->assertJsonPath('rows.0.allocated_qty', 0);
    $response->assertJsonPath('rows.0.available_qty', 8);
    $response->assertJsonPath('rows.0.available_stock', 8);
    $response->assertJsonPath('rows.0.stock_value', 3000);
    $response->assertJsonPath('hpp_summary.total_qty', 8);
    $response->assertJsonPath('hpp_summary.total_value', 3000);
    $response->assertJsonPath('hpp_summary.value_qty', 3);
    $response->assertJsonPath('hpp_summary.avg_hpp_weighted', 1000);
});

it('returns available stock for a specific item and warehouse', function () {
    $user = User::factory()->create([
        'name' => 'Owner Test',
        'employee_code' => 'OWNTEST-AVAIL',
        'role' => 'owner',
    ]);

    $item = Item::create([
        'code' => 'TEST-AVAIL',
        'name' => 'Test Available Item',
        'unit' => 'pcs',
        'type' => 'material',
        'active' => true,
    ]);

    $warehouse = Warehouse::create([
        'code' => 'WH-AVAIL',
        'name' => 'Warehouse Available',
        'type' => 'internal',
        'active' => true,
    ]);

    InventoryStock::create([
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'qty' => 12,
        'allocated_qty' => 4,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->get(route('inventory.stocks.items.available', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
        ]));

    $response->assertOk();
    $response->assertJsonPath('ok', true);
    $response->assertJsonPath('item.id', $item->id);
    $response->assertJsonPath('warehouse.id', $warehouse->id);
    $response->assertJsonPath('stock.qty', 12);
    $response->assertJsonPath('stock.allocated_qty', 4);
    $response->assertJsonPath('stock.available_qty', 8);
});
