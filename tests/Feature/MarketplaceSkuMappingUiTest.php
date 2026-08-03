<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\OrderFulfillment;
use App\Models\OrderFulfillmentLine;
use App\Models\SkuMapping;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceSkuMappingUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sku_mapping_page_defines_stock_number_formatter(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-SKU-MAPPING-'.uniqid(),
        ]);

        $this->actingAs($owner)
            ->get(route('marketplace.sku-mapping'))
            ->assertOk()
            ->assertSee("const FMT = new Intl.NumberFormat('id-ID');", false)
            ->assertSee('mappingChannelFilter', false)
            ->assertSee('Edit SKU Mapping', false)
            ->assertSee('mapSaveButton', false);
    }

    public function test_mapping_index_filters_by_channel_and_stock_status(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-SKU-FILTER-'.uniqid(),
        ]);
        $warehouse = Warehouse::create([
            'code' => 'WH-SKU-'.uniqid(),
            'name' => 'Warehouse SKU Mapping',
            'type' => 'internal',
            'active' => true,
        ]);
        $availableItem = Item::create([
            'code' => 'ITEM-SKU-AVAILABLE',
            'name' => 'Item SKU Available',
            'unit' => 'pcs',
            'type' => 'finished',
            'active' => true,
        ]);
        $emptyItem = Item::create([
            'code' => 'ITEM-SKU-EMPTY',
            'name' => 'Item SKU Empty',
            'unit' => 'pcs',
            'type' => 'finished',
            'active' => true,
        ]);
        InventoryStock::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $availableItem->id,
            'qty' => 10,
            'allocated_qty' => 2,
        ]);

        SkuMapping::create([
            'marketplace_sku' => 'SKU-AVAILABLE',
            'channel_code' => 'shopee',
            'item_id' => $availableItem->id,
        ]);
        SkuMapping::create([
            'marketplace_sku' => 'SKU-EMPTY',
            'channel_code' => 'shopee',
            'item_id' => $emptyItem->id,
        ]);
        SkuMapping::create([
            'marketplace_sku' => 'SKU-OTHER-CHANNEL',
            'channel_code' => 'tiktok',
            'item_id' => $availableItem->id,
        ]);

        $this->actingAs($owner)
            ->getJson('/api/sku-mappings?channel_code=shopee&stock_status=available')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.marketplace_sku', 'SKU-AVAILABLE');
    }

    public function test_mapping_can_be_updated_without_creating_a_second_row(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-SKU-UPDATE-'.uniqid(),
        ]);
        $oldItem = Item::create([
            'code' => 'ITEM-SKU-OLD',
            'name' => 'Item SKU Old',
            'unit' => 'pcs',
            'type' => 'finished',
            'active' => true,
        ]);
        $newItem = Item::create([
            'code' => 'ITEM-SKU-NEW',
            'name' => 'Item SKU New',
            'unit' => 'pcs',
            'type' => 'finished',
            'active' => true,
        ]);
        $mapping = SkuMapping::create([
            'marketplace_sku' => 'SKU-BEFORE-UPDATE',
            'channel_code' => 'shopee',
            'item_id' => $oldItem->id,
            'notes' => 'lama',
        ]);

        $this->actingAs($owner)
            ->putJson('/api/sku-mappings/'.$mapping->id, [
                'marketplace_sku' => 'SKU-AFTER-UPDATE',
                'channel_code' => 'tiktok',
                'item_id' => $newItem->id,
                'notes' => 'sudah diperbarui',
            ])
            ->assertOk()
            ->assertJsonPath('marketplace_sku', 'SKU-AFTER-UPDATE')
            ->assertJsonPath('channel_code', 'tiktok')
            ->assertJsonPath('item_id', $newItem->id);

        $this->assertDatabaseCount('sku_mappings', 1);
        $this->assertDatabaseHas('sku_mappings', [
            'id' => $mapping->id,
            'marketplace_sku' => 'SKU-AFTER-UPDATE',
            'channel_code' => 'tiktok',
            'item_id' => $newItem->id,
            'notes' => 'sudah diperbarui',
        ]);
    }

    public function test_mapping_update_propagates_to_order_item_and_pending_fulfillment(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-SKU-REALTIME-'.uniqid(),
        ]);
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'code' => 'STORE-SKU-'.uniqid(),
            'name' => 'Store SKU Realtime',
            'channel_id' => $channel->id,
            'is_active' => true,
        ]);
        $warehouse = Warehouse::create([
            'code' => 'WH-REALTIME-'.uniqid(),
            'name' => 'Warehouse Realtime',
            'type' => 'internal',
            'active' => true,
        ]);
        $oldItem = Item::create([
            'code' => 'ITEM-REALTIME-OLD',
            'name' => 'Item Realtime Old',
            'unit' => 'pcs',
            'type' => 'finished',
            'hpp' => 50,
            'active' => true,
        ]);
        $newItem = Item::create([
            'code' => 'ITEM-REALTIME-NEW',
            'name' => 'Item Realtime New',
            'unit' => 'pcs',
            'type' => 'finished',
            'hpp' => 100,
            'active' => true,
        ]);
        InventoryStock::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $newItem->id,
            'qty' => 20,
            'allocated_qty' => 0,
        ]);
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'ORDER-REALTIME-'.uniqid(),
            'order_date' => now(),
            'status' => 'new',
            'order_status' => 'READY_TO_SHIP',
        ]);
        $orderItem = MarketplaceOrderItem::create([
            'order_id' => $order->id,
            'marketplace_order_id' => $order->id,
            'line_no' => 1,
            'external_item_id' => 'ITEM-CHANNEL-1',
            'item_name' => 'Order Item Realtime',
            'item_sku' => 'SKU-REALTIME',
            'qty' => 2,
            'price' => 100,
            'mapping_status' => 'mapping_not_found',
            'data_status' => 'incomplete',
        ]);
        $fulfillment = OrderFulfillment::create([
            'marketplace_order_id' => $order->id,
            'warehouse_id' => $warehouse->id,
            'status' => OrderFulfillment::STATUS_PENDING_REVIEW,
        ]);
        $line = OrderFulfillmentLine::create([
            'fulfillment_id' => $fulfillment->id,
            'marketplace_order_item_id' => $orderItem->id,
            'marketplace_sku' => 'SKU-REALTIME',
            'marketplace_item_name' => 'Order Item Realtime',
            'qty_ordered' => 2,
            'qty_fulfilled' => 0,
            'stock_available' => 0,
        ]);
        $mapping = SkuMapping::create([
            'marketplace_sku' => 'SKU-REALTIME',
            'channel_code' => 'shopee',
            'item_id' => $oldItem->id,
        ]);

        $this->actingAs($owner)
            ->putJson('/api/sku-mappings/'.$mapping->id, [
                'marketplace_sku' => 'SKU-REALTIME',
                'channel_code' => 'shopee',
                'item_id' => $newItem->id,
                'notes' => 'realtime',
            ])
            ->assertOk();

        $this->assertDatabaseHas('marketplace_order_items', [
            'id' => $orderItem->id,
            'internal_item_id' => $newItem->id,
            'mapping_status' => 'mapped',
        ]);
        $this->assertDatabaseHas('order_fulfillment_lines', [
            'id' => $line->id,
            'item_id' => $newItem->id,
            'stock_available' => 20,
        ]);
    }
}
