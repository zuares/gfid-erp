<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceProductModel;
use App\Models\SkuMapping;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\MarketplaceProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class MarketplaceEmptyVariantDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_lists_only_mapped_multi_variants_with_empty_stock(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-EMPTY-STOCK']);
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create(['channel_id' => $channel->id, 'code' => 'TTK-EMPTY-STOCK', 'name' => 'TikTok Stock Audit', 'status' => 'active', 'is_active' => true, 'credentials' => ['access_token' => 'tiktok-empty-stock-token']]);
        $product = MarketplaceProduct::create(['store_id' => $store->id, 'item_id' => 'product-variants', 'item_name' => 'Jaket Multi Variant', 'item_status' => 'NORMAL', 'has_model' => true]);

        $mappedItem = Item::create(['code' => 'JACKET-BLK-M', 'name' => 'Jaket Hitam M', 'unit' => 'pcs', 'type' => 'finished']);
        DB::table('items')->where('id', $mappedItem->id)->update(['avg_daily_sales' => 2, 'avg_daily_sales_window' => 30]);
        SkuMapping::create(['marketplace_sku' => 'TT-JACKET-BLK-M', 'channel_code' => 'tiktok', 'item_id' => $mappedItem->id]);
        $emptyModel = MarketplaceProductModel::create(['marketplace_product_id' => $product->id, 'model_id' => 'empty-mapped', 'model_name' => 'Hitam / M', 'model_sku' => 'TT-JACKET-BLK-M', 'stock' => 0]);
        $warehouse = Warehouse::create(['code' => 'WH-VARIANT-DASH', 'name' => 'Warehouse Variant Dashboard']);
        InventoryStock::create(['warehouse_id' => $warehouse->id, 'item_id' => $mappedItem->id, 'qty' => 15, 'allocated_qty' => 3]);

        MarketplaceProductModel::create(['marketplace_product_id' => $product->id, 'model_id' => 'empty-unmapped', 'model_name' => 'Navy / M', 'model_sku' => 'TT-JACKET-NAVY-M', 'stock' => 0]);

        $neverSoldItem = Item::create(['code' => 'JACKET-ZERO-S', 'name' => 'Jaket Belum Terjual', 'unit' => 'pcs', 'type' => 'finished']);
        SkuMapping::create(['marketplace_sku' => 'TT-JACKET-ZERO-S', 'channel_code' => 'tiktok', 'item_id' => $neverSoldItem->id]);
        MarketplaceProductModel::create(['marketplace_product_id' => $product->id, 'model_id' => 'empty-never-sold', 'model_name' => 'Belum Terjual / S', 'model_sku' => 'TT-JACKET-ZERO-S', 'stock' => 0]);

        $stockedItem = Item::create(['code' => 'JACKET-BLK-L', 'name' => 'Jaket Hitam L', 'unit' => 'pcs', 'type' => 'finished']);
        SkuMapping::create(['marketplace_sku' => 'TT-JACKET-BLK-L', 'channel_code' => 'tiktok', 'item_id' => $stockedItem->id]);
        MarketplaceProductModel::create(['marketplace_product_id' => $product->id, 'model_id' => 'stocked-mapped', 'model_name' => 'Hitam / L', 'model_sku' => 'TT-JACKET-BLK-L', 'stock' => 8]);

        $this->actingAs($owner)
            ->get(route('marketplace.products.empty-variants'))
            ->assertOk()
            ->assertSee('Stok Variant Kosong')
            ->assertSee('Variant perlu diperbarui')
            ->assertSee('Pilih Toko:')
            ->assertSee('Stok MP')
            ->assertSee('Update Stok')
            ->assertSee('Stok Kosong tapi di Marketplace Ada Stok')
            ->assertSee('data-mp-stock-val', false)
            ->assertDontSee('Stok saat ini:', false)
            ->assertSee('Hitam / M')
            ->assertSee('JACKET-BLK-M')
            ->assertSee('data-stock-form', false)
            ->assertSee('data-product-id="'.$product->id.'"', false)
            ->assertSee('data-model-id="empty-mapped"', false)
            ->assertSee('role="tablist"', false)
            ->assertSee('data-realtime-filter', false)
            ->assertSee('TikTok Stock Audit')
            ->assertDontSee('id="svStore"', false)
            ->assertSee('sv-product-photo', false)
            ->assertSee('class="sv-item-id"', false)
            ->assertSee('class="sv-record"', false)
            ->assertSee('class="sv-list-head"', false)
            ->assertSee('Filter diterapkan otomatis saat nilai berubah.')
            ->assertSee('title="Jaket Multi Variant"', false)
            ->assertSee('ID product-variants')
            ->assertSee('Stok internal')
            ->assertSee('id="stock-'.$emptyModel->id.'"', false)
            ->assertSee('value="12"', false)
            ->assertSee('id="svAverage" name="min_avg_sales" type="number" min="0" max="1000000" step="0.1" value="0"', false)
            ->assertDontSee('Pakai stok tersedia')
            ->assertDontSee('Belum Terjual / S')
            ->assertDontSee('Navy / M')
            ->assertDontSee('Hitam / L');
    }

    public function test_dashboard_accepts_global_mapping_and_filters_zero_stock(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-GLOBAL-MAPPING']);
        $channel = Channel::create(['code' => 'SHOPEE', 'name' => 'Shopee']);
        $store = Store::create(['channel_id' => $channel->id, 'code' => 'SHP-EMPTY-STOCK', 'name' => 'Shopee Stock Audit', 'status' => 'active', 'is_active' => true, 'credentials' => ['access_token' => 'shopee-empty-stock-token']]);
        $product = MarketplaceProduct::create(['store_id' => $store->id, 'item_id' => 'product-global', 'item_name' => 'Celana Variant', 'item_status' => 'NORMAL', 'has_model' => true]);
        $item = Item::create(['code' => 'PANTS-NAVY-XL', 'name' => 'Celana Navy XL', 'unit' => 'pcs', 'type' => 'finished']);
        DB::table('items')->where('id', $item->id)->update(['avg_daily_sales' => 1, 'avg_daily_sales_window' => 30]);
        SkuMapping::create(['marketplace_sku' => 'SHP-PANTS-NAVY-XL', 'channel_code' => null, 'item_id' => $item->id]);
        MarketplaceProductModel::create(['marketplace_product_id' => $product->id, 'model_id' => 'zero', 'model_name' => 'Navy / XL', 'model_sku' => 'SHP-PANTS-NAVY-XL', 'stock' => 0]);
        MarketplaceProductModel::create(['marketplace_product_id' => $product->id, 'model_id' => 'negative', 'model_name' => 'Navy / L', 'model_sku' => 'SHP-PANTS-NAVY-XL', 'stock' => -2]);

        $this->actingAs($owner)
            ->get(route('marketplace.products.empty-variants', ['stock_status' => 'zero', 'channel' => 'shopee']))
            ->assertOk()
            ->assertSee('Navy / XL')
            ->assertDontSee('Navy / L');
    }

    public function test_dashboard_filters_variants_by_inventory_average_daily_sales(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-AVG-SALES']);
        $channel = Channel::create(['code' => 'SHOPEE', 'name' => 'Shopee']);
        $store = Store::create(['channel_id' => $channel->id, 'code' => 'SHP-AVG-SALES', 'name' => 'Shopee Average Sales', 'status' => 'active', 'is_active' => true, 'credentials' => ['access_token' => 'shopee-average-sales-token']]);

        $fastProduct = MarketplaceProduct::create(['store_id' => $store->id, 'item_id' => 'fast-product', 'item_name' => 'Produk Cepat', 'item_status' => 'NORMAL', 'has_model' => true]);
        $fastItem = Item::create(['code' => 'FAST-M', 'name' => 'Fast M', 'unit' => 'pcs', 'type' => 'finished']);
        DB::table('items')->where('id', $fastItem->id)->update(['avg_daily_sales' => 3, 'avg_daily_sales_window' => 21]);
        SkuMapping::create(['marketplace_sku' => 'FAST-M', 'channel_code' => 'shopee', 'item_id' => $fastItem->id]);
        MarketplaceProductModel::create(['marketplace_product_id' => $fastProduct->id, 'model_id' => 'fast-model', 'model_name' => 'Fast / M', 'model_sku' => 'FAST-M', 'stock' => 0]);

        $slowProduct = MarketplaceProduct::create(['store_id' => $store->id, 'item_id' => 'slow-product', 'item_name' => 'Produk Lambat', 'item_status' => 'NORMAL', 'has_model' => true]);
        $slowItem = Item::create(['code' => 'SLOW-M', 'name' => 'Slow M', 'unit' => 'pcs', 'type' => 'finished']);
        DB::table('items')->where('id', $slowItem->id)->update(['avg_daily_sales' => 1, 'avg_daily_sales_window' => 30]);
        SkuMapping::create(['marketplace_sku' => 'SLOW-M', 'channel_code' => 'shopee', 'item_id' => $slowItem->id]);
        MarketplaceProductModel::create(['marketplace_product_id' => $slowProduct->id, 'model_id' => 'slow-model', 'model_name' => 'Slow / M', 'model_sku' => 'SLOW-M', 'stock' => 0]);

        $this->actingAs($owner)
            ->get(route('marketplace.products.empty-variants'))
            ->assertOk()
            ->assertSee('Produk Cepat')
            ->assertSee('3,0')
            ->assertSee('Inventory · 21 hari');

        $this->actingAs($owner)
            ->get(route('marketplace.products.empty-variants', ['min_avg_sales' => 2]))
            ->assertOk()
            ->assertSee('Produk Cepat')
            ->assertSee('3,0')
            ->assertDontSee('Produk Lambat')
            ->assertSee('value="2"', false);
    }

    public function test_single_sku_product_is_not_treated_as_empty_variant(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-SINGLE-SKU']);
        $channel = Channel::create(['code' => 'SHOPEE', 'name' => 'Shopee']);
        $store = Store::create(['channel_id' => $channel->id, 'code' => 'SHP-SINGLE', 'name' => 'Shopee Single', 'status' => 'active', 'is_active' => true]);
        $product = MarketplaceProduct::create(['store_id' => $store->id, 'item_id' => 'single-product', 'item_name' => 'Produk Single SKU', 'item_status' => 'NORMAL', 'has_model' => false]);
        $item = Item::create(['code' => 'SINGLE-INTERNAL', 'name' => 'Single Internal', 'unit' => 'pcs', 'type' => 'finished']);
        SkuMapping::create(['marketplace_sku' => 'SINGLE-SKU', 'channel_code' => 'shopee', 'item_id' => $item->id]);
        MarketplaceProductModel::create(['marketplace_product_id' => $product->id, 'model_id' => '0', 'model_name' => null, 'model_sku' => 'SINGLE-SKU', 'stock' => 0]);

        $this->actingAs($owner)->get(route('marketplace.products.empty-variants'))->assertOk()->assertDontSee('Produk Single SKU');
    }

    public function test_dashboard_only_shows_connected_active_stores_and_can_audit_internal_empty_stock(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-STOCK-MISMATCH']);
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $connectedStore = Store::create(['channel_id' => $channel->id, 'code' => 'SHP-CONNECTED', 'name' => 'Toko API Terhubung', 'status' => 'active', 'is_active' => true, 'credentials' => ['access_token' => 'connected-token']]);
        $disconnectedStore = Store::create(['channel_id' => $channel->id, 'code' => 'SHP-DISCONNECTED', 'name' => 'Toko Tidak Terhubung', 'status' => 'active', 'is_active' => true]);
        $inactiveStore = Store::create(['channel_id' => $channel->id, 'code' => 'SHP-INACTIVE', 'name' => 'Toko Nonaktif', 'status' => 'active', 'is_active' => false, 'credentials' => ['access_token' => 'inactive-token']]);
        $inactiveStatusStore = Store::create(['channel_id' => $channel->id, 'code' => 'SHP-INACTIVE-STATUS', 'name' => 'Toko Status Inactive', 'status' => 'inactive', 'is_active' => true, 'credentials' => ['access_token' => 'status-token']]);

        $auditedItem = Item::create(['code' => 'AUDIT-EMPTY', 'name' => 'Audit Internal Kosong', 'unit' => 'pcs', 'type' => 'finished']);
        DB::table('items')->where('id', $auditedItem->id)->update(['avg_daily_sales' => 2, 'avg_daily_sales_window' => 30]);
        SkuMapping::create(['marketplace_sku' => 'AUDIT-EMPTY', 'channel_code' => 'shopee', 'item_id' => $auditedItem->id]);
        $connectedProduct = MarketplaceProduct::create(['store_id' => $connectedStore->id, 'item_id' => 'audit-connected', 'item_name' => 'Variant Audit Terhubung', 'item_status' => 'NORMAL', 'has_model' => true]);
        MarketplaceProductModel::create(['marketplace_product_id' => $connectedProduct->id, 'model_id' => 'audit-model', 'model_name' => 'Audit / M', 'model_sku' => 'AUDIT-EMPTY', 'stock' => 7]);

        $hiddenItem = Item::create(['code' => 'AUDIT-HIDDEN', 'name' => 'Audit Hidden', 'unit' => 'pcs', 'type' => 'finished']);
        DB::table('items')->where('id', $hiddenItem->id)->update(['avg_daily_sales' => 2, 'avg_daily_sales_window' => 30]);
        SkuMapping::create(['marketplace_sku' => 'AUDIT-HIDDEN', 'channel_code' => 'shopee', 'item_id' => $hiddenItem->id]);
        $disconnectedProduct = MarketplaceProduct::create(['store_id' => $disconnectedStore->id, 'item_id' => 'audit-disconnected', 'item_name' => 'Variant Tidak Terhubung', 'item_status' => 'NORMAL', 'has_model' => true]);
        MarketplaceProductModel::create(['marketplace_product_id' => $disconnectedProduct->id, 'model_id' => 'hidden-model', 'model_name' => 'Hidden / M', 'model_sku' => 'AUDIT-HIDDEN', 'stock' => 9]);
        $inactiveProduct = MarketplaceProduct::create(['store_id' => $inactiveStore->id, 'item_id' => 'audit-inactive', 'item_name' => 'Variant Nonaktif', 'item_status' => 'NORMAL', 'has_model' => true]);
        MarketplaceProductModel::create(['marketplace_product_id' => $inactiveProduct->id, 'model_id' => 'inactive-model', 'model_name' => 'Inactive / M', 'model_sku' => 'AUDIT-HIDDEN', 'stock' => 9]);

        $this->actingAs($owner)
            ->get(route('marketplace.products.empty-variants', ['stock_scope' => 'internal_empty_marketplace_stock']))
            ->assertOk()
            ->assertSee('Toko API Terhubung')
            ->assertDontSee('Toko Tidak Terhubung')
            ->assertDontSee('Toko Nonaktif')
            ->assertDontSee('Toko Status Inactive')
            ->assertSee('Variant Audit Terhubung')
            ->assertSee('Audit mismatch: stok internal kosong, tetapi marketplace masih menjual variant ini.')
            ->assertSee('data-stock-scope="internal_empty_marketplace_stock"', false)
            ->assertDontSee('Variant Tidak Terhubung')
            ->assertDontSee('Variant Nonaktif');
    }

    public function test_guest_cannot_open_empty_variant_dashboard(): void
    {
        $this->get(route('marketplace.products.empty-variants'))->assertRedirect();
    }

    public function test_mapped_empty_variant_stock_can_be_updated_through_marketplace_endpoint(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-UPDATE-EMPTY-STOCK']);
        $channel = Channel::create(['code' => 'SHOPEE', 'name' => 'Shopee']);
        $store = Store::create(['channel_id' => $channel->id, 'code' => 'SHP-UPDATE-STOCK', 'name' => 'Shopee Update Stock', 'status' => 'active', 'is_active' => true]);
        $product = MarketplaceProduct::create(['store_id' => $store->id, 'item_id' => 'product-update-stock', 'item_name' => 'Produk Update Stok', 'item_status' => 'NORMAL', 'has_model' => true]);
        MarketplaceProductModel::create(['marketplace_product_id' => $product->id, 'model_id' => 'model-update', 'model_name' => 'Hitam / M', 'model_sku' => 'UPDATE-BLK-M', 'stock' => 0]);

        $service = Mockery::mock(MarketplaceProductService::class);
        $service->shouldReceive('updateStock')->once()->with(
            Mockery::on(fn (MarketplaceProduct $candidate): bool => $candidate->is($product)),
            [['model_id' => 'model-update', 'stock' => 12]],
        )->andReturn(['success' => true]);
        $this->app->instance(MarketplaceProductService::class, $service);

        $this->actingAs($owner)
            ->postJson('/api/marketplace/products/'.$product->id.'/stock', [
                'stock_list' => [['model_id' => 'model-update', 'stock' => 12]],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_sidebar_displays_empty_variants_link_for_owner_and_admin(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-SIDEBAR-EMPTY-VARIANTS']);
        $admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'ADMIN-SIDEBAR-EMPTY-VARIANTS']);

        $this->actingAs($owner)
            ->get(route('marketplace.products.empty-variants'))
            ->assertOk()
            ->assertSee(route('marketplace.products.empty-variants'), false)
            ->assertSee('Variant Stok Kosong');

        $this->actingAs($admin)
            ->get(route('marketplace.products.empty-variants'))
            ->assertOk()
            ->assertSee(route('marketplace.products.empty-variants'), false)
            ->assertSee('Variant Stok Kosong');
    }
}
