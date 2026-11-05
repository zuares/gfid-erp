<?php

use App\Http\Controllers\Inventory\InventoryAdjustmentController;
use App\Models\InventoryAdjustment;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

it('lets admin submit a manual correction for owner finalization', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'employee_code' => 'ADMIN-ADJ-TEST',
    ]);
    User::factory()->create([
        'role' => 'owner',
        'employee_code' => 'OWNER-ADJ-TEST',
    ]);
    $warehouse = Warehouse::create([
        'code' => 'WH-RTS',
        'name' => 'Gudang RTS',
        'type' => 'finished_goods',
        'active' => true,
    ]);
    $item = Item::create([
        'code' => 'ITEM-ADJ-001',
        'name' => 'Item Adjustment Test',
        'unit' => 'pcs',
        'type' => 'finished_good',
        'active' => true,
    ]);

    $response = $this->actingAs($admin)->post(route('inventory.adjustments.manual.store'), [
        'warehouse_id' => $warehouse->id,
        'date' => now()->toDateString(),
        'notes' => 'Koreksi hasil pengecekan fisik',
        'lines' => [[
            'item_id' => $item->id,
            'qty_change' => 1,
            'notes' => 'Barang fisik ditemukan di area gudang',
        ]],
    ]);

    $adjustment = InventoryAdjustment::query()->latest('id')->firstOrFail();

    $response
        ->assertRedirect(route('inventory.adjustments.show', $adjustment))
        ->assertSessionHas('message', 'Koreksi persediaan berhasil dikirim dan menunggu finalisasi Owner.');

    expect($adjustment->status)->toBe(InventoryAdjustment::STATUS_PENDING);
    $this->assertDatabaseMissing('inventory_stocks', [
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
    ]);

    $this->actingAs($admin)
        ->post(route('inventory.adjustments.post', $adjustment))
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('inventory.adjustments.approve', $adjustment))
        ->assertForbidden();
});

it('shows pending manual corrections in the owner notification bell', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'employee_code' => 'ADMIN-ADJ-NOTIF-TEST',
    ]);
    $owner = User::factory()->create([
        'role' => 'owner',
        'employee_code' => 'OWNER-ADJ-NOTIF-TEST',
    ]);
    $warehouse = Warehouse::create([
        'code' => 'WH-RTS',
        'name' => 'Gudang RTS',
        'type' => 'finished_goods',
        'active' => true,
    ]);
    $item = Item::create([
        'code' => 'ITEM-ADJ-NOTIF-001',
        'name' => 'Item Notification Test',
        'unit' => 'pcs',
        'type' => 'finished_good',
        'active' => true,
    ]);

    $this->actingAs($admin)->post(route('inventory.adjustments.manual.store'), [
        'warehouse_id' => $warehouse->id,
        'date' => now()->toDateString(),
        'lines' => [[
            'item_id' => $item->id,
            'qty_change' => 2,
            'notes' => 'Koreksi item untuk notifikasi owner',
        ]],
    ])->assertRedirect();

    $adjustment = InventoryAdjustment::query()->latest('id')->firstOrFail();

    $this->actingAs($owner)
        ->get(route('inventory.adjustments.index'))
        ->assertOk()
        ->assertSee($adjustment->code)
        ->assertSee('Menunggu finalisasi Owner')
        ->assertSee(route('inventory.adjustments.show', $adjustment), false);
});

it('accepts multiple related items for a bulk inventory correction prefill', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'employee_code' => 'ADMIN-ADJ-BULK-TEST',
    ]);
    $warehouse = Warehouse::create([
        'code' => 'WH-RTS',
        'name' => 'Gudang RTS',
        'type' => 'finished_goods',
        'active' => true,
    ]);
    $first = Item::create([
        'code' => 'BULK-ADJ-001',
        'name' => 'Bulk Adjustment Item One',
        'unit' => 'pcs',
        'type' => 'finished_good',
        'active' => true,
    ]);
    $second = Item::create([
        'code' => 'BULK-ADJ-002',
        'name' => 'Bulk Adjustment Item Two',
        'unit' => 'pcs',
        'type' => 'finished_good',
        'active' => true,
    ]);

    $request = Request::create('/inventory/adjustments/manual/create', 'GET', [
        'warehouse_id' => $warehouse->id,
        'prefill' => json_encode([
            ['item_id' => $first->id, 'qty_change' => 2],
            ['item_id' => $second->id, 'qty_change' => 4],
        ]),
    ]);
    $request->setUserResolver(fn () => $admin);

    $view = app(InventoryAdjustmentController::class)->createManual($request);

    expect($view->getData()['prefillLines'])->toBe([
        ['item_id' => $first->id, 'item_code' => 'BULK-ADJ-001', 'qty_change' => 2.0],
        ['item_id' => $second->id, 'item_code' => 'BULK-ADJ-002', 'qty_change' => 4.0],
    ]);
});

it('searches multiple manual correction items when terms are separated by spaces', function () {
    $warehouse = Warehouse::create([
        'code' => 'WH-SEARCH',
        'name' => 'Gudang Search',
        'type' => 'finished_goods',
        'active' => true,
    ]);
    $first = Item::create([
        'code' => 'MULTI-ADJ-001',
        'name' => 'Multi Search Item One',
        'unit' => 'pcs',
        'type' => 'finished_good',
        'active' => true,
    ]);
    $second = Item::create([
        'code' => 'MULTI-ADJ-002',
        'name' => 'Multi Search Item Two',
        'unit' => 'pcs',
        'type' => 'finished_good',
        'active' => true,
    ]);

    $request = Request::create('/inventory/adjustments/items', 'GET', [
        'warehouse_id' => $warehouse->id,
        'q' => 'MULTI-ADJ-001 MULTI-ADJ-002',
    ]);

    $response = app(InventoryAdjustmentController::class)->itemsForWarehouse($request);
    $codes = collect($response->getData(true))->pluck('code')->all();

    expect($codes)->toContain($first->code)
        ->toContain($second->code);
});
