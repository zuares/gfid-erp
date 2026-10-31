<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceProductModel;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceEmptyVariantDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_lists_only_multi_variant_products_with_incomplete_variant_data(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-EMPTY-VARIANTS']);
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'TTK-EMPTY-VARIANTS',
            'name' => 'TikTok Variant Audit',
            'status' => 'active',
            'is_active' => true,
        ]);

        $incomplete = MarketplaceProduct::create([
            'store_id' => $store->id,
            'item_id' => 'product-incomplete',
            'item_name' => 'Jaket Multi Variant',
            'item_status' => 'NORMAL',
            'has_model' => true,
        ]);
        MarketplaceProductModel::create([
            'marketplace_product_id' => $incomplete->id,
            'model_id' => 'sku-incomplete',
            'model_name' => '',
            'model_sku' => null,
            'stock' => 3,
        ]);

        $complete = MarketplaceProduct::create([
            'store_id' => $store->id,
            'item_id' => 'product-complete',
            'item_name' => 'Kaos Lengkap',
            'item_status' => 'NORMAL',
            'has_model' => true,
        ]);
        MarketplaceProductModel::create([
            'marketplace_product_id' => $complete->id,
            'model_id' => 'sku-complete',
            'model_name' => 'Hitam / L',
            'model_sku' => 'TSHIRT-BLK-L',
            'stock' => 5,
        ]);

        $singleSku = MarketplaceProduct::create([
            'store_id' => $store->id,
            'item_id' => 'product-single',
            'item_name' => 'Produk Tanpa Varian',
            'item_status' => 'NORMAL',
            'has_model' => false,
        ]);
        MarketplaceProductModel::create([
            'marketplace_product_id' => $singleSku->id,
            'model_id' => '0',
            'model_name' => null,
            'model_sku' => null,
            'stock' => 1,
        ]);

        $this->actingAs($owner)
            ->get(route('marketplace.products.empty-variants'))
            ->assertOk()
            ->assertSee('Dashboard Variant Kosong')
            ->assertSee('Jaket Multi Variant')
            ->assertDontSee('Kaos Lengkap')
            ->assertDontSee('Produk Tanpa Varian');
    }

    public function test_dashboard_can_filter_missing_variant_sku(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-EMPTY-SKU']);
        $channel = Channel::create(['code' => 'SHOPEE', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'SHP-EMPTY-SKU',
            'name' => 'Shopee Variant Audit',
            'status' => 'active',
            'is_active' => true,
        ]);
        $product = MarketplaceProduct::create([
            'store_id' => $store->id,
            'item_id' => 'product-missing-sku',
            'item_name' => 'Celana Variant SKU Kosong',
            'item_status' => 'NORMAL',
            'has_model' => true,
        ]);
        MarketplaceProductModel::create([
            'marketplace_product_id' => $product->id,
            'model_id' => 'model-1',
            'model_name' => 'Navy / XL',
            'model_sku' => '   ',
            'stock' => 2,
        ]);

        $this->actingAs($owner)
            ->get(route('marketplace.products.empty-variants', ['issue' => 'missing_sku', 'channel' => 'shopee']))
            ->assertOk()
            ->assertSee('Celana Variant SKU Kosong')
            ->assertSee('SKU kosong');
    }

    public function test_guest_cannot_open_empty_variant_dashboard(): void
    {
        $this->get(route('marketplace.products.empty-variants'))
            ->assertRedirect();
    }
}
