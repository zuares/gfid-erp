<?php

use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists purchased finished goods on the RTS stock request page', function () {
    $user = User::factory()->create([
        'role' => 'owner',
        'employee_code' => 'OWNER-RTS-TEST',
    ]);

    Warehouse::create([
        'code' => 'WH-PRD',
        'name' => 'Production Warehouse',
        'type' => 'internal',
        'active' => true,
    ]);

    Warehouse::create([
        'code' => 'WH-RTS',
        'name' => 'RTS Warehouse',
        'type' => 'internal',
        'active' => true,
    ]);

    Item::create([
        'code' => 'TTB-WHT-M',
        'name' => 'Tracktop Putih Garis 3 Tangan Ukuran M',
        'unit' => 'pcs',
        'type' => 'finished_good',
        'production_source' => 'buy',
        'can_buy' => true,
        'can_make' => false,
        'active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('rts.stock-requests.create'))
        ->assertOk()
        ->assertSee('TTB-WHT-M')
        ->assertSee('Tracktop Putih Garis 3 Tangan Ukuran M');
});

it('prefills the related item and shortage quantity when opened from a stock shortage', function () {
    $user = User::factory()->create([
        'role' => 'owner',
        'employee_code' => 'OWNER-RTS-PREFILL-TEST',
    ]);

    Warehouse::create([
        'code' => 'WH-PRD',
        'name' => 'Production Warehouse',
        'type' => 'internal',
        'active' => true,
    ]);

    Warehouse::create([
        'code' => 'WH-RTS',
        'name' => 'RTS Warehouse',
        'type' => 'internal',
        'active' => true,
    ]);

    $item = Item::create([
        'code' => 'TTB-RJK-XXL',
        'name' => 'Reject Defact Tracktop XXL Garis 3',
        'unit' => 'pcs',
        'type' => 'finished_good',
        'active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('rts.stock-requests.create', [
            'item_id' => $item->id,
            'qty' => 1,
        ]))
        ->assertOk()
        ->assertSee('item_id', false)
        ->assertSee('qty_request', false)
        ->assertSee((string) $item->id, false)
        ->assertSee('TTB-RJK-XXL');
});
