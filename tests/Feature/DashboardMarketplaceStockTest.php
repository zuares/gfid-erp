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
use App\Services\Marketplace\MarketplaceVariantStockSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardMarketplaceStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_variant_stock_summary_matches_inventory_rules(): void
    {
        [$store, $availableItem, $emptyItem] = $this->createVariantStockFixture();

        $summary = app(MarketplaceVariantStockSummaryService::class)->summary();

        $this->assertSame(2, $summary['mapped_total']);
        $this->assertSame(1, $summary['marketplace_zero_internal_available']);
        $this->assertSame(1, $summary['internal_empty_marketplace_stock']);
        $this->assertSame($store->id, MarketplaceProduct::where('store_id', $store->id)->value('store_id'));
        $this->assertNotSame($availableItem->id, $emptyItem->id);
    }

    public function test_owner_and_admin_see_marketplace_stock_mini_dashboard(): void
    {
        $this->createVariantStockFixture();
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'DASH-STOCK-OWNER']);
        $admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'DASH-STOCK-ADMIN']);
        $operating = User::factory()->create(['role' => 'operating', 'employee_code' => 'DASH-STOCK-OPERATING']);

        foreach ([$owner, $admin] as $user) {
            Cache::store('file')->forget('dashboard.owner.v15.'.today()->toDateString());
            Cache::store('file')->forget('dashboard.admin.v14.'.today()->toDateString());

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee('Stok marketplace')
                ->assertSee('Stok MP 0 · Internal Ada')
                ->assertSee('Stok Kosong tapi di Marketplace Ada Stok')
                ->assertSee('marketplace/products/empty-variants?stock_scope=marketplace_empty&amp;stock_status=zero', false)
                ->assertSee('marketplace/products/empty-variants?stock_scope=internal_empty_marketplace_stock', false);
        }

        $this->actingAs($operating)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Stok MP 0 · Internal Ada')
            ->assertDontSee('Stok Kosong tapi di Marketplace Ada Stok');
    }

    /** @return array{Store, Item, Item} */
    private function createVariantStockFixture(): array
    {
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop', 'status' => 'active']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'DASH-STOCK-TIKTOK',
            'name' => 'TikTok Dashboard Stock',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'dashboard-stock-token'],
        ]);
        $warehouse = Warehouse::create(['code' => 'DASH-STOCK-WH', 'name' => 'Dashboard Stock Warehouse']);

        $availableItem = Item::create([
            'code' => 'DASH-STOCK-AVAILABLE',
            'name' => 'Dashboard Stock Available',
            'unit' => 'pcs',
            'type' => 'finished',
        ]);
        SkuMapping::create([
            'marketplace_sku' => 'DASH-STOCK-ZERO',
            'channel_code' => 'tiktok',
            'item_id' => $availableItem->id,
        ]);
        InventoryStock::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $availableItem->id,
            'qty' => 10,
            'allocated_qty' => 2,
        ]);

        $emptyItem = Item::create([
            'code' => 'DASH-STOCK-EMPTY',
            'name' => 'Dashboard Stock Empty',
            'unit' => 'pcs',
            'type' => 'finished',
        ]);
        SkuMapping::create([
            'marketplace_sku' => 'DASH-STOCK-MP-AVAILABLE',
            'channel_code' => 'tiktok',
            'item_id' => $emptyItem->id,
        ]);

        $product = MarketplaceProduct::create([
            'store_id' => $store->id,
            'item_id' => 'DASH-STOCK-PRODUCT',
            'item_name' => 'Dashboard Stock Product',
            'item_status' => 'NORMAL',
            'has_model' => true,
        ]);
        MarketplaceProductModel::create([
            'marketplace_product_id' => $product->id,
            'model_id' => 'DASH-STOCK-ZERO-MODEL',
            'model_sku' => 'DASH-STOCK-ZERO',
            'stock' => 0,
        ]);
        MarketplaceProductModel::create([
            'marketplace_product_id' => $product->id,
            'model_id' => 'DASH-STOCK-MP-AVAILABLE-MODEL',
            'model_sku' => 'DASH-STOCK-MP-AVAILABLE',
            'stock' => 7,
        ]);

        return [$store, $availableItem, $emptyItem];
    }
}
