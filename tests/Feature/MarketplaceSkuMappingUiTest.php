<?php

namespace Tests\Feature;

use Carbon\Carbon;
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
            ->assertSee('sku-mapping-dashboard', false)
            ->assertSee("const FMT = new Intl.NumberFormat('id-ID');", false)
            ->assertSee('unmappedDateRange', false)
            ->assertSee("mode: 'range'", false)
            ->assertSee('Nama Variant', false)
            ->assertSee('unmappedAutoMapBtn', false)
            ->assertSee('runUnmappedAutoMap', false)
            ->assertSee('autoMapConfirmModal', false)
            ->assertSee('confirmUnmappedAutoMap', false)
            ->assertSee('Tidak ada order item yang perlu dipetakan otomatis', false)
            ->assertSee('Mapping Terdaftar', false)
            ->assertSee('Edit SKU Mapping', false)
            ->assertSee('mapSaveButton', false);
    }

    public function test_registered_sku_mappings_have_a_dedicated_page(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-REGISTERED-SKU-PAGE-'.uniqid(),
        ]);

        $this->actingAs($owner)
            ->get(route('marketplace.sku-mapping.registered'))
            ->assertOk()
            ->assertSee('sku-mapping-dashboard', false)
            ->assertSee('Mapping Terdaftar', false)
            ->assertSee('mappingSearch', false)
            ->assertSee('mappingChannelFilter', false)
            ->assertSee('mappingStockFilter', false)
            ->assertSee('SKU Belum Dipetakan', false)
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

    public function test_unmapped_skus_can_filter_by_store_channel_search_and_order_date(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-UNMAPPED-FILTER-'.uniqid(),
        ]);
        $shopee = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $tiktok = Channel::create(['code' => 'tiktok', 'name' => 'TikTok']);
        $storeA = Store::create([
            'code' => 'STORE-UNMAPPED-A-'.uniqid(),
            'name' => 'Toko A',
            'channel_id' => $shopee->id,
            'is_active' => true,
        ]);
        $storeB = Store::create([
            'code' => 'STORE-UNMAPPED-B-'.uniqid(),
            'name' => 'Toko B',
            'channel_id' => $tiktok->id,
            'is_active' => true,
        ]);

        $makeOrderWithItem = function (Store $store, string $orderId, string $sku, string $name, Carbon $orderedAt): void {
            $order = MarketplaceOrder::create([
                'store_id' => $store->id,
                'external_order_id' => $orderId,
                'order_date' => $orderedAt,
                'ordered_at' => $orderedAt,
                'status' => 'new',
                'order_status' => 'READY_TO_SHIP',
            ]);

            MarketplaceOrderItem::create([
                'order_id' => $order->id,
                'marketplace_order_id' => $order->id,
                'line_no' => 1,
                'external_item_id' => 'ITEM-'.$sku,
                'item_name' => $name,
                'variant_name' => $sku === 'SKU-A' ? 'Merah / L' : null,
                'item_sku' => $sku,
                'qty' => 1,
                'price' => 100,
                'mapping_status' => 'mapping_not_found',
            ]);
        };

        $makeOrderWithItem($storeA, 'ORDER-UNMAPPED-A', 'SKU-A', 'Produk A', now()->subDay());
        $makeOrderWithItem($storeB, 'ORDER-UNMAPPED-B', 'SKU-B', 'Produk B', now()->subDays(10));

        $this->actingAs($owner)
            ->getJson('/api/sku-mappings/unmapped-skus?store_id='.$storeA->id.'&channel_code=SHOPEE&search=produk%20a&order_date_from='.now()->subDays(2)->toDateString())
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.sku', 'SKU-A')
            ->assertJsonPath('0.variant_name', 'Merah / L')
            ->assertJsonPath('0.channel_code', 'shopee')
            ->assertJsonPath('0.store_names', 'Toko A');

        $internalItem = Item::create([
            'code' => 'ITEM-UNMAPPED-A',
            'name' => 'Item Internal A',
            'unit' => 'pcs',
            'type' => 'finished',
            'active' => true,
        ]);

        $this->actingAs($owner)
            ->postJson('/api/sku-mappings', [
                'marketplace_sku' => 'SKU-A',
                'channel_code' => 'shopee',
                'item_id' => $internalItem->id,
            ])
            ->assertCreated();

        $this->actingAs($owner)
            ->getJson('/api/sku-mappings/unmapped-skus?store_id='.$storeA->id.'&channel_code=shopee')
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_unmapped_skus_treats_tiktok_and_ttk_as_the_same_channel(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-UNMAPPED-ALIAS-'.uniqid(),
        ]);
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok']);
        $store = Store::create([
            'code' => 'STORE-UNMAPPED-ALIAS-'.uniqid(),
            'name' => 'Toko TikTok Alias',
            'channel_id' => $channel->id,
            'is_active' => true,
        ]);
        $item = Item::create([
            'code' => 'TTC-BLK-L',
            'name' => 'Tracktop Hitam Strip 2 Tangan Ukuiran L',
            'unit' => 'pcs',
            'type' => 'finished',
            'active' => true,
        ]);
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'ORDER-UNMAPPED-ALIAS',
            'order_date' => now(),
            'ordered_at' => now(),
            'status' => 'new',
            'order_status' => 'READY_TO_SHIP',
        ]);
        MarketplaceOrderItem::create([
            'order_id' => $order->id,
            'marketplace_order_id' => $order->id,
            'line_no' => 1,
            'external_item_id' => 'ITEM-TTC-BLK-L',
            'item_name' => 'Celana Pendek Short Boxer',
            'variant_name' => 'Hitam, 6L',
            'marketplace_sku' => 'TTC-BLK-L',
            'model_sku' => 'TTC-BLK-L',
            'item_sku' => 'TTC-BLK-L',
            'qty' => 1,
            'price' => 100,
            'mapping_status' => 'mapped',
            'internal_item_id' => $item->id,
        ]);
        SkuMapping::create([
            'marketplace_sku' => 'TTC-BLK-L',
            'channel_code' => 'TTK',
            'item_id' => $item->id,
        ]);

        $this->actingAs($owner)
            ->getJson('/api/sku-mappings/unmapped-skus?store_id='.$store->id)
            ->assertOk()
            ->assertJsonCount(0);
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

    public function test_mapping_resolves_all_order_items_with_the_same_marketplace_sku_and_channel(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-SKU-BULK-MAP-'.uniqid(),
        ]);
        $channel = Channel::firstOrCreate(['code' => 'TTK'], ['name' => 'TikTok']);
        $store = Store::create([
            'code' => 'STORE-SKU-BULK-'.uniqid(),
            'name' => 'Dummy TikTok External',
            'channel_id' => $channel->id,
            'is_active' => true,
        ]);
        $internalItem = Item::create([
            'code' => 'ITEM-TTK-BULK',
            'name' => 'Item TikTok Bulk',
            'unit' => 'pcs',
            'type' => 'finished',
            'active' => true,
        ]);

        foreach (['MODEL-TTK-1', 'MODEL-TTK-2'] as $modelSku) {
            $order = MarketplaceOrder::create([
                'store_id' => $store->id,
                'external_order_id' => 'ORDER-TTK-BULK-'.uniqid(),
                'order_date' => now(),
                'ordered_at' => now(),
                'status' => 'new',
                'order_status' => 'READY_TO_SHIP',
            ]);

            MarketplaceOrderItem::create([
                'order_id' => $order->id,
                'marketplace_order_id' => $order->id,
                'line_no' => 1,
                'external_item_id' => 'ITEM-TTK-BULK-'.$modelSku,
                'item_name' => 'Celana Pendek Short Boxer Big Size Premium Jumbo Size 40 Casual',
                'variant_name' => 'Hitam, 6L',
                'marketplace_sku' => 'TTK',
                'model_sku' => $modelSku,
                'item_sku' => 'TTK',
                'qty' => 1,
                'price' => 100,
                'mapping_status' => 'mapping_not_found',
            ]);
        }

        $this->actingAs($owner)
            ->postJson('/api/sku-mappings', [
                'marketplace_sku' => 'TTK',
                'channel_code' => 'ttk',
                'item_id' => $internalItem->id,
            ])
            ->assertCreated();

        $this->assertDatabaseCount('sku_mappings', 1);
        $this->assertDatabaseMissing('marketplace_order_items', [
            'marketplace_sku' => 'TTK',
            'mapping_status' => 'mapping_not_found',
        ]);
        $this->assertDatabaseCount('marketplace_order_items', 2);
        $this->assertDatabaseHas('marketplace_order_items', [
            'marketplace_sku' => 'TTK',
            'mapping_status' => 'mapped',
            'internal_item_id' => $internalItem->id,
        ]);
    }

    public function test_auto_mapping_matches_case_insensitive_code_and_resolves_all_related_items(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-AUTO-MAP-'.uniqid(),
        ]);
        $channel = Channel::firstOrCreate(['code' => 'TTK'], ['name' => 'TikTok']);
        $store = Store::create([
            'code' => 'STORE-AUTO-MAP-'.uniqid(),
            'name' => 'Dummy TikTok Auto Map',
            'channel_id' => $channel->id,
            'is_active' => true,
        ]);
        $internalItem = Item::create([
            'code' => 'AUTO-TTK',
            'name' => 'Item Auto TikTok',
            'unit' => 'pcs',
            'type' => 'finished',
            'active' => true,
        ]);
        $exactItem = Item::create([
            'code' => 'TTC-BLK-L',
            'name' => 'Tracktop Hitam Ukuran L',
            'unit' => 'pcs',
            'type' => 'finished',
            'active' => true,
        ]);

        foreach (['MODEL-AUTO-1', 'MODEL-AUTO-2'] as $index => $modelSku) {
            $order = MarketplaceOrder::create([
                'store_id' => $store->id,
                'external_order_id' => 'ORDER-AUTO-MAP-'.uniqid(),
                'order_date' => now(),
                'ordered_at' => now(),
                'status' => 'new',
                'order_status' => 'READY_TO_SHIP',
            ]);

            MarketplaceOrderItem::create([
                'order_id' => $order->id,
                'marketplace_order_id' => $order->id,
                'line_no' => 1,
                'external_item_id' => 'ITEM-AUTO-MAP-'.$index,
                'item_name' => 'Produk Auto Map',
                'marketplace_sku' => 'listing-auto-ttk',
                'model_sku' => 'AUTO-TTK',
                'qty' => 1,
                'price' => 100,
                'mapping_status' => $index === 0 ? 'marketplace_sku_empty' : 'mapping_not_found',
            ]);
        }

        $exactOrder = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'ORDER-AUTO-EXACT-'.uniqid(),
            'order_date' => now(),
            'ordered_at' => now(),
            'status' => 'new',
            'order_status' => 'READY_TO_SHIP',
        ]);
        MarketplaceOrderItem::create([
            'order_id' => $exactOrder->id,
            'marketplace_order_id' => $exactOrder->id,
            'line_no' => 1,
            'external_item_id' => 'ITEM-AUTO-EXACT',
            'item_name' => 'Tracktop Hitam Ukuran L',
            'marketplace_sku' => 'ttc-blk-l',
            'model_sku' => 'MODEL-EXACT',
            'qty' => 1,
            'price' => 100,
            'mapping_status' => 'mapping_not_found',
        ]);

        $this->actingAs($owner)
            ->getJson('/api/marketplace/auto-map-by-code/preview')
            ->assertOk()
            ->assertJsonPath('total_matches', 2)
            ->assertJsonPath('total_order_items', 3)
            ->assertJsonPath('matches.1.item_code', 'TTC-BLK-L');

        $this->actingAs($owner)
            ->postJson('/api/marketplace/auto-map-by-code')
            ->assertOk()
            ->assertJsonPath('mapped', 3)
            ->assertJsonPath('errors', 0);

        $this->assertDatabaseCount('sku_mappings', 2);
        $this->assertDatabaseHas('sku_mappings', [
            'marketplace_sku' => 'listing-auto-ttk',
            'channel_code' => 'ttk',
            'item_id' => $internalItem->id,
        ]);
        $this->assertDatabaseHas('sku_mappings', [
            'marketplace_sku' => 'ttc-blk-l',
            'channel_code' => 'ttk',
            'item_id' => $exactItem->id,
        ]);
        $this->assertDatabaseMissing('marketplace_order_items', [
            'marketplace_sku' => 'listing-auto-ttk',
            'mapping_status' => 'mapping_not_found',
        ]);
        $this->assertDatabaseMissing('marketplace_order_items', [
            'marketplace_sku' => 'ttc-blk-l',
            'mapping_status' => 'mapping_not_found',
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
