<?php

use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('returns ready stock for many item codes using a bearer token', function () {
    $user = User::factory()->create([
        'name' => 'Stock Reader',
        'employee_code' => 'STOCK-READER',
        'role' => 'owner',
    ]);

    $plainToken = Str::random(40);
    $tokenId = DB::table('personal_access_tokens')->insertGetId([
        'tokenable_type' => User::class,
        'tokenable_id' => $user->id,
        'name' => 'telegram-stock-read',
        'token' => hash('sha256', $plainToken),
        'abilities' => json_encode(['stock-read']),
        'last_used_at' => null,
        'expires_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $token = $tokenId . '|' . $plainToken;

    $itemA = Item::create([
        'code' => 'SKU-A',
        'name' => 'Item A',
        'unit' => 'pcs',
        'type' => 'material',
        'active' => true,
    ]);

    $itemB = Item::create([
        'code' => 'SKU-B',
        'name' => 'Item B',
        'unit' => 'pcs',
        'type' => 'material',
        'active' => true,
    ]);

    $warehouse = Warehouse::create([
        'code' => 'WH-READY',
        'name' => 'Ready Warehouse',
        'type' => 'internal',
        'active' => true,
    ]);

    InventoryStock::create([
        'warehouse_id' => $warehouse->id,
        'item_id' => $itemA->id,
        'qty' => 10,
        'allocated_qty' => 2,
    ]);

    InventoryStock::create([
        'warehouse_id' => $warehouse->id,
        'item_id' => $itemB->id,
        'qty' => 4,
        'allocated_qty' => 1,
    ]);

    $response = $this
        ->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson(route('stocks.ready'), [
            'item_codes' => ['SKU-A', 'SKU-B', 'SKU-Z'],
            'warehouse_code' => 'WH-READY',
        ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            ['item_code', 'stock_ready'],
        ],
    ]);
    $response->assertJsonPath('data.0.item_code', 'SKU-A');
    $response->assertJsonPath('data.0.stock_ready', 8);
    $response->assertJsonPath('data.1.item_code', 'SKU-B');
    $response->assertJsonPath('data.1.stock_ready', 3);
    $response->assertJsonPath('data.2.item_code', 'SKU-Z');
    $response->assertJsonPath('data.2.stock_ready', 0);
});

it('rejects requests without bearer token', function () {
    $response = $this->postJson(route('stocks.ready'), [
        'item_codes' => ['SKU-A'],
    ]);

    $response->assertStatus(401);
});

it('rejects bearer tokens without stock-read ability', function () {
    $user = User::factory()->create([
        'name' => 'No Stock Read',
        'employee_code' => 'NO-STOCK-READ',
        'role' => 'owner',
    ]);

    $plainToken = Str::random(40);
    $tokenId = DB::table('personal_access_tokens')->insertGetId([
        'tokenable_type' => User::class,
        'tokenable_id' => $user->id,
        'name' => 'telegram-stock-read',
        'token' => hash('sha256', $plainToken),
        'abilities' => json_encode(['profile']),
        'last_used_at' => null,
        'expires_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $token = $tokenId . '|' . $plainToken;

    $response = $this
        ->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson(route('stocks.ready'), [
            'item_codes' => ['SKU-A'],
        ]);

    $response->assertStatus(403);
});
