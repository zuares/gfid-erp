<?php

use App\Models\Item;
use App\Models\StockOpname;
use App\Models\StockOpnameLine;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('defaults unfilled periodic lines to zero and marks them as automatic', function () {
    $user = User::factory()->create([
        'employee_code' => 'SO-DEFAULT-ZERO',
        'role' => 'owner',
    ]);
    $warehouse = Warehouse::create([
        'code' => 'SO-DEFAULT-WH',
        'name' => 'Default zero warehouse',
        'type' => 'internal',
        'active' => true,
    ]);
    $unfilledItem = Item::create([
        'code' => 'SO-DEFAULT-EMPTY',
        'name' => 'Unfilled item',
        'unit' => 'pcs',
        'type' => 'material',
        'hpp' => 100,
        'active' => true,
    ]);
    $explicitZeroItem = Item::create([
        'code' => 'SO-EXPLICIT-ZERO',
        'name' => 'Explicit zero item',
        'unit' => 'pcs',
        'type' => 'material',
        'hpp' => 100,
        'active' => true,
    ]);
    $opname = StockOpname::create([
        'code' => 'SO-DEFAULT-ZERO-TEST',
        'date' => now()->toDateString(),
        'snapshot_at' => now(),
        'warehouse_id' => $warehouse->id,
        'type' => StockOpname::TYPE_PERIODIC,
        'status' => StockOpname::STATUS_COUNTING,
        'created_by' => $user->id,
    ]);
    $unfilledLine = StockOpnameLine::create([
        'stock_opname_id' => $opname->id,
        'item_id' => $unfilledItem->id,
        'system_qty' => 10,
        'physical_qty' => null,
        'difference_qty' => 0,
        'is_counted' => false,
        'is_auto_zeroed' => false,
        'unit_cost' => 100,
    ]);
    $explicitZeroLine = StockOpnameLine::create([
        'stock_opname_id' => $opname->id,
        'item_id' => $explicitZeroItem->id,
        'system_qty' => 4,
        'physical_qty' => 0,
        'difference_qty' => -4,
        'is_counted' => true,
        'is_auto_zeroed' => false,
        'unit_cost' => 100,
    ]);

    $this->actingAs($user)
        ->put(route('inventory.stock_opnames.update', $opname), [
            'lines' => [
                $unfilledLine->id => ['physical_qty' => ''],
                $explicitZeroLine->id => ['physical_qty' => 0],
            ],
        ])
        ->assertRedirect();

    expect($unfilledLine->fresh()->physical_qty)->toBeNull()
        ->and($unfilledLine->fresh()->is_counted)->toBeFalse()
        ->and($unfilledLine->fresh()->is_auto_zeroed)->toBeFalse();

    $this->actingAs($user)
        ->put(route('inventory.stock_opnames.update', $opname), [
            'lines' => [
                $unfilledLine->id => ['physical_qty' => ''],
                $explicitZeroLine->id => ['physical_qty' => 0],
            ],
            'mark_reviewed' => 1,
        ])
        ->assertRedirect(route('inventory.stock_opnames.show', $opname));

    expect($unfilledLine->fresh()->physical_qty)->toBe('0.000')
        ->and((float) $unfilledLine->fresh()->difference_qty)->toBe(-10.0)
        ->and($unfilledLine->fresh()->is_counted)->toBeTrue()
        ->and($unfilledLine->fresh()->is_auto_zeroed)->toBeTrue()
        ->and($explicitZeroLine->fresh()->is_auto_zeroed)->toBeFalse();

    $this->get(route('inventory.stock_opnames.show', $opname))
        ->assertOk()
        ->assertSee('Default 0');
});
