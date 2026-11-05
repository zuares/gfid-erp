<?php

use App\Http\Controllers\Inventory\RtsStockRequestController;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

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

it('accepts multiple related items for a bulk stock request prefill', function () {
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

    $first = Item::create([
        'code' => 'BULK-RTS-001',
        'name' => 'Bulk RTS Item One',
        'unit' => 'pcs',
        'type' => 'finished_good',
        'active' => true,
    ]);
    $second = Item::create([
        'code' => 'BULK-RTS-002',
        'name' => 'Bulk RTS Item Two',
        'unit' => 'pcs',
        'type' => 'finished_good',
        'active' => true,
    ]);

    $view = app(RtsStockRequestController::class)->create(Request::create('/rts/stock-requests/create', 'GET', [
        'prefill' => json_encode([
            ['item_id' => $first->id, 'qty_request' => 2],
            ['item_id' => $second->id, 'qty_request' => 3],
        ]),
    ]));

    expect($view->getData()['prefillLines'])->toBe([
        ['item_id' => $first->id, 'qty_request' => 2.0],
        ['item_id' => $second->id, 'qty_request' => 3.0],
    ]);
});
