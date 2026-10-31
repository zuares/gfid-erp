<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Item;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceProductModel;
use App\Models\SkuMapping;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceEmptyVariantDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_lists_only_mapped_multi_variants_with_empty_stock(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-EMPTY-STOCK']);
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create(['channel_id' => $channel->id, 'code' => 'TTK-EMPTY-STOCK', 'name' => 'TikTok Stock Audit', 'status' => 'active', 'is_active' => true]);
        $product = MarketplaceProduct::create(['store_id' => $store->id, 'item_id' => 'product-variants', 'item_name' => 'Jaket Multi Variant', 'item_status' => 'NORMAL', 'has_model' => true]);

        $mappedItem = Item::create(['code' => 'JACKET-BLK-M', 'name' => 'Jaket Hitam M', 'unit' => 'pcs', 'type' => 'finished']);
        SkuMapping::create(['marketplace_sku' => 'TT-JACKET-BLK-M', 'channel_code' => 'tiktok', 'item_id' => $mappedItem->id]);
        MarketplaceProductModel::create(['marketplace_product_id' => $product->id, 'model_id' => 'empty-mapped', 'model_name' => 'Hitam / M', 'model_sku' => 'TT-JACKET-BLK-M', 'stock' => 0]);

        MarketplaceProductModel::create(['marketplace_product_id' => $product->id, 'model_id' => 'empty-unmapped', 'model_name' => 'Navy / M', 'model_sku' => 'TT-JACKET-NAVY-M', 'stock' => 0]);

        $stockedItem = Item::create(['code' => 'JACKET-BLK-L', 'name' => 'Jaket Hitam L', 'unit' => 'pcs', 'type' => 'finished']);
        SkuMapping::create(['marketplace_sku' => 'TT-JACKET-BLK-L', 'channel_code' => 'tiktok', 'item_id' => $stockedItem->id]);
        MarketplaceProductModel::create(['marketplace_product_id' => $product->id, 'model_id' => 'stocked-mapped', 'model_name' => 'Hitam / L', 'model_sku' => 'TT-JACKET-BLK-L', 'stock' => 8]);

        $this->actingAs($owner)
            ->get(route('marketplace.products.empty-variants'))
            ->assertOk()
            ->assertSee('Dashboard Variant Stok Kosong')
            ->assertSee('Hitam / M')
            ->assertSee('JACKET-BLK-M')
            ->assertDontSee('Navy / M')
            ->assertDontSee('Hitam / L');
    }

    public function test_dashboard_accepts_global_mapping_and_filters_zero_stock(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-GLOBAL-MAPPING']);
        $channel = Channel::create(['code' => 'SHOPEE', 'name' => 'Shopee']);
        $store = Store::create(['channel_id' => $channel->id, 'code' => 'SHP-EMPTY-STOCK', 'name' => 'Shopee Stock Audit', 'status' => 'active', 'is_active' => true]);
        $product = MarketplaceProduct::create(['store_id' => $store->id, 'item_id' => 'product-global', 'item_name' => 'Celana Variant', 'item_status' => 'NORMAL', 'has_model' => true]);
        $item = Item::create(['code' => 'PANTS-NAVY-XL', 'name' => 'Celana Navy XL', 'unit' => 'pcs', 'type' => 'finished']);
        SkuMapping::create(['marketplace_sku' => 'SHP-PANTS-NAVY-XL', 'channel_code' => null, 'item_id' => $item->id]);
        MarketplaceProductModel::create(['marketplace_product_id' => $product->id, 'model_id' => 'zero', 'model_name' => 'Navy / XL', 'model_sku' => 'SHP-PANTS-NAVY-XL', 'stock' => 0]);
        MarketplaceProductModel::create(['marketplace_product_id' => $product->id, 'model_id' => 'negative', 'model_name' => 'Navy / L', 'model_sku' => 'SHP-PANTS-NAVY-XL', 'stock' => -2]);

        $this->actingAs($owner)
            ->get(route('marketplace.products.empty-variants', ['stock_status' => 'zero', 'channel' => 'shopee']))
            ->assertOk()
            ->assertSee('Navy / XL')
            ->assertDontSee('Navy / L');
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

    public function test_guest_cannot_open_empty_variant_dashboard(): void
    {
        $this->get(route('marketplace.products.empty-variants'))->assertRedirect();
    }
}
