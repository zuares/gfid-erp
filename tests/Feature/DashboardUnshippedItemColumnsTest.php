<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardUnshippedItemColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_sewing_and_not_sewn_columns_per_internal_item(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee', 'status' => 'active']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'DASH-SEWING-STORE',
            'name' => 'Dashboard Sewing Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $item = Item::create([
            'code' => 'DASH-SEWING-ITEM',
            'name' => 'Dashboard Sewing Item',
            'unit' => 'pcs',
            'type' => 'finished',
        ]);
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'DASH-SEWING-ORDER',
            'order_date' => now(),
            'status' => 'new',
            'order_status' => 'READY_TO_SHIP',
            'buyer_name' => 'Dashboard Buyer',
        ]);
        MarketplaceOrderItem::create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'item_code_snapshot' => $item->code,
            'item_name_snapshot' => $item->name,
            'external_sku' => $item->code,
            'qty' => 12,
        ]);

        $warehouseSew = Warehouse::create(['code' => 'WIP-SEW', 'name' => 'Sedang Jahit']);
        $warehouseCut = Warehouse::create(['code' => 'WIP-CUT', 'name' => 'Siap Jahit']);
        InventoryStock::create(['warehouse_id' => $warehouseSew->id, 'item_id' => $item->id, 'qty' => 3]);
        InventoryStock::create(['warehouse_id' => $warehouseCut->id, 'item_id' => $item->id, 'qty' => 5]);

        Cache::store('file')->forget('dashboard.admin.v14.' . today()->toDateString());

        $this->actingAs(User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'DASH-SEWING-ADMIN',
        ]))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sedang jahit')
            ->assertSee('Belum jahit')
            ->assertSee('DASH-SEWING-ITEM')
            ->assertSee('3 pcs')
            ->assertSee('5 pcs');
    }
}
