<?php

use App\Models\Item;
use App\Models\StockOpname;
use App\Models\StockOpnameLine;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows admin to create a stock opname but not finalize it', function () {
    $admin = User::factory()->create([
        'employee_code' => 'SO-ADMIN-AUTH',
        'role' => 'admin',
    ]);
    $warehouse = Warehouse::create([
        'code' => 'SO-AUTH-WH',
        'name' => 'Stock opname test warehouse',
        'type' => 'internal',
        'active' => true,
    ]);
    $item = Item::create([
        'code' => 'SO-AUTH-ITEM',
        'name' => 'Stock opname test item',
        'unit' => 'pcs',
        'type' => 'material',
        'hpp' => 100,
        'active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('inventory.stock_opnames.create'))
        ->assertOk();

    $this->actingAs($admin)
        ->post(route('inventory.stock_opnames.store'), [
            'warehouse_id' => $warehouse->id,
            'date' => now()->toDateString(),
            'type' => StockOpname::TYPE_PERIODIC,
        ])
        ->assertRedirect();

    $opname = StockOpname::create([
        'code' => 'SO-AUTH-REVIEWED',
        'date' => now()->toDateString(),
        'snapshot_at' => now(),
        'warehouse_id' => $warehouse->id,
        'type' => StockOpname::TYPE_PERIODIC,
        'status' => StockOpname::STATUS_REVIEWED,
        'created_by' => $admin->id,
    ]);
    StockOpnameLine::create([
        'stock_opname_id' => $opname->id,
        'item_id' => $item->id,
        'system_qty' => 10,
        'physical_qty' => 9,
        'difference_qty' => -1,
        'is_counted' => true,
        'unit_cost' => 100,
    ]);

    $this->actingAs($admin)
        ->get(route('inventory.stock_opnames.show', $opname))
        ->assertOk()
        ->assertDontSee(route('inventory.stock_opnames.finalize', $opname), false);

    $this->actingAs($admin)
        ->post(route('inventory.stock_opnames.finalize', $opname), [
            'reason' => 'Unauthorized admin finalize attempt',
        ])
        ->assertForbidden();

    expect($opname->fresh()->status)->toBe(StockOpname::STATUS_REVIEWED)
        ->and($opname->adjustment()->exists())->toBeFalse();
});
