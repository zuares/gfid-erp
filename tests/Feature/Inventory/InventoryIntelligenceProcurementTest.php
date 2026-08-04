<?php

use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryIntelligenceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('uses a 60-day FOB forecast and counts process WIP once', function () {
    $item = Item::create([
        'code' => 'FOB-60-TEST',
        'name' => 'FOB Forecast Test',
        'unit' => 'pcs',
        'type' => 'finished_good',
        'production_source' => Item::PRODUCTION_BUY,
        'last_purchase_price' => 100000,
        'active' => true,
    ]);

    foreach (['WH-RTS', 'WH-PRD', 'WIP-CUT', 'WIP-SEW'] as $code) {
        $warehouse = Warehouse::create([
            'code' => $code,
            'name' => $code,
            'type' => str_starts_with($code, 'WIP-') ? 'wip' : 'fg',
            'active' => true,
        ]);

        InventoryStock::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'qty' => match ($code) {
                'WH-RTS' => 10,
                'WH-PRD' => 5,
                'WIP-CUT' => 7,
                'WIP-SEW' => 8,
            },
        ]);
    }

    $today = Carbon::today();
    for ($daysAgo = 0; $daysAgo < 30; $daysAgo++) {
        DB::table('daily_item_sales')->insert([
            'date' => $today->copy()->subDays($daysAgo)->toDateString(),
            'item_id' => $item->id,
            'qty_sold' => 2,
            'value_sold' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $row = app(InventoryIntelligenceService::class)->rows([])->firstWhere('item_id', $item->id);

    expect($row)->not->toBeNull()
        ->and($row->ads)->toBe(2.0)
        ->and($row->forecast_60)->toBe(120.0)
        ->and($row->ready_total)->toBe(15.0)
        ->and($row->wip_process)->toBe(15.0)
        ->and($row->available_stock)->toBe(30.0)
        ->and($row->unit_cost)->toBe(100000.0)
        ->and($row->available_value)->toBe(3000000.0)
        ->and($row->production_forecast)->toBe(60.0)
        ->and($row->procurement_forecast)->toBe(120.0)
        ->and($row->procurement_suggested_qty)->toBe(90.0)
        ->and($row->suggested_value)->toBe(9000000.0)
        ->and($row->suggested_qty)->toBe(90.0);

    $row30 = app(InventoryIntelligenceService::class)
        ->rows(['procurement_days' => 30])
        ->firstWhere('item_id', $item->id);

    expect($row30->procurement_days)->toBe(30)
        ->and($row30->procurement_forecast)->toBe(60.0)
        ->and($row30->procurement_suggested_qty)->toBe(30.0)
        ->and($row30->suggested_qty)->toBe(30.0);

    $rowProduction60 = app(InventoryIntelligenceService::class)
        ->rows(['production_days' => 60])
        ->firstWhere('item_id', $item->id);

    expect($rowProduction60->production_days)->toBe(60)
        ->and($rowProduction60->production_forecast)->toBe(120.0);
});
