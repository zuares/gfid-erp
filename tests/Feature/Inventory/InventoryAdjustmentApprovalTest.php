<?php

use App\Models\InventoryAdjustment;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
