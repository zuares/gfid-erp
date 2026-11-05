<?php

use App\Models\InventoryMutation;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the creator name on stock card rows', function () {
    $user = User::factory()->create([
        'name' => 'Budi Operator',
        'employee_code' => 'OWNTEST01',
        'role' => 'owner',
    ]);

    $item = Item::create([
        'code' => 'J3BLK',
        'name' => 'Jacket 3 Black',
        'unit' => 'pcs',
        'type' => 'material',
        'active' => true,
    ]);

    $warehouse = Warehouse::create([
        'code' => 'WH-RM',
        'name' => 'Gudang RM',
        'type' => 'internal',
        'active' => true,
    ]);

    InventoryMutation::create([
        'date' => now()->toDateString(),
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'qty_change' => 10,
        'direction' => 'in',
        'source_type' => 'adjustment',
        'source_id' => 99,
        'notes' => 'Stock opname',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('inventory.stock_card.index', [
        'item_id' => $item->id,
    ]));

    $response->assertOk();
    $response->assertSee('Budi Operator');
    $response->assertSee('Oleh');
    $response->assertSee('Koreksi stok / opname');
    $response->assertSee('Alasan: Stock opname');
    $response->assertDontSee('pcs');
    $response->assertSee('sc_date_range');
    $response->assertSee('name="direction"', false);
    $response->assertSee('name="source_type"', false);
    $response->assertDontSee('data-range-preset', false);
    $response->assertSee('sc-filter-details', false);
    $response->assertSee('sc-mobile-list', false);
    $response->assertSee('Riwayat mutasi');
    $response->assertSee('showMonths: 1', false);
    $response->assertDontSee('showMonths: 2', false);
});

it('shows zero balance clearly on the stock card', function () {
    $user = User::factory()->create([
        'name' => 'Budi Operator',
        'employee_code' => 'OWNTEST02',
        'role' => 'owner',
    ]);

    $item = Item::create([
        'code' => 'J3BLKZ',
        'name' => 'Jacket 3 Zero',
        'unit' => 'pcs',
        'type' => 'material',
        'active' => true,
    ]);

    $warehouse = Warehouse::create([
        'code' => 'WH-RZ',
        'name' => 'Gudang Zero',
        'type' => 'internal',
        'active' => true,
    ]);

    InventoryMutation::create([
        'date' => now()->toDateString(),
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'qty_change' => 10,
        'direction' => 'in',
        'source_type' => 'adjustment',
        'source_id' => 101,
        'notes' => 'Tambah stok',
        'created_by' => $user->id,
    ]);

    InventoryMutation::create([
        'date' => now()->toDateString(),
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'qty_change' => -10,
        'direction' => 'out',
        'source_type' => 'adjustment',
        'source_id' => 102,
        'notes' => 'Kurangi stok',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('inventory.stock_card.index', [
        'item_id' => $item->id,
    ]));

    $response->assertOk();
    $response->assertSee('summary-value--zero');
    $response->assertSee('qty-balance-zero');
    $response->assertSee('Tanda <span class="mono">-</span> dipakai untuk mutasi yang kosong.', false);
});

it('shows the mutation time and weekday in the stock card date cell', function () {
    $user = User::factory()->create([
        'name' => 'Budi Operator',
        'employee_code' => 'OWNTEST03',
        'role' => 'owner',
    ]);

    $item = Item::create([
        'code' => 'J3DATE',
        'name' => 'Jacket Date Cell',
        'unit' => 'pcs',
        'type' => 'material',
        'active' => true,
    ]);

    $warehouse = Warehouse::create([
        'code' => 'WH-DATE',
        'name' => 'Gudang Date Cell',
        'type' => 'internal',
        'active' => true,
    ]);

    $mutation = InventoryMutation::create([
        'date' => '2026-11-05',
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'qty_change' => 10,
        'direction' => 'in',
        'source_type' => 'adjustment',
        'source_id' => 103,
        'notes' => 'Tambah stok',
        'created_by' => $user->id,
    ]);
    $mutation->forceFill([
        'created_at' => Carbon::parse('2026-11-05 00:00:00', 'Asia/Jakarta'),
    ])->save();

    $response = $this->actingAs($user)->get(route('inventory.stock_card.index', [
        'item_id' => $item->id,
        'from_date' => '2026-11-05',
        'to_date' => '2026-11-05',
    ]));

    $response->assertOk();
    $response->assertSee('05 Nov');
    $response->assertSee('Kamis');
    $response->assertSee('00:00');
    $response->assertDontSee('00:00 AM');
    $response->assertDontSee('00:00 PM');
});
