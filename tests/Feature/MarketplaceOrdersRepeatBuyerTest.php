<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureModuleAccess;
use App\Models\Channel;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Marketplace\MarketplaceTrackingStatusService;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketplaceOrdersRepeatBuyerTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            Authenticate::class,
            EnsureModuleAccess::class,
        ]);

        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $this->store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'REPEAT-STORE',
            'name' => 'Toko Repeat',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'test-token'],
            'token_expires_at' => now()->addDay(),
        ]);

        $legacyChannelId = DB::table('marketplace_channels')->insertGetId([
            'code' => 'shopee',
            'name' => 'Shopee',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('marketplace_stores')->insert([
            'id' => $this->store->id,
            'channel_id' => $legacyChannelId,
            'name' => $this->store->name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrder(string $orderSn, string $status, string $username): MarketplaceOrder
    {
        return MarketplaceOrder::create([
            'store_id' => $this->store->id,
            'external_order_id' => $orderSn,
            'channel_order_id' => $orderSn,
            'order_date' => now(),
            'ordered_at' => now(),
            'order_status' => $status,
            'buyer_username' => $username,
        ]);
    }

    public function test_order_ditandai_repeat_bila_pembeli_punya_histori_pembelian(): void
    {
        $this->createOrder('REPEAT-PREVIOUS', 'COMPLETED', 'buyer-repeat');
        $current = $this->createOrder('REPEAT-CURRENT', 'READY_TO_SHIP', 'buyer-repeat');

        $response = $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&limit=50');
        $row = collect($response->json('data'))->firstWhere('id', $current->id);

        $response->assertOk();
        $this->assertSame(1, $row['buyer_previous_order_count']);
        $this->assertTrue($row['is_repeat_buyer']);

        $history = $this->getJson("/api/marketplace/local-orders/{$current->id}/buyer-completed-orders");

        $history->assertOk()
            ->assertJsonPath('buyer_label', 'buyer-repeat')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_sn', 'REPEAT-PREVIOUS')
            ->assertJsonPath('data.0.status', 'COMPLETED');
    }

    public function test_order_baru_tidak_ditandai_repeat(): void
    {
        $current = $this->createOrder('NEW-CURRENT', 'READY_TO_SHIP', 'buyer-new');

        $response = $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&limit=50');
        $row = collect($response->json('data'))->firstWhere('id', $current->id);

        $response->assertOk();
        $this->assertSame(0, $row['buyer_previous_order_count']);
        $this->assertFalse($row['is_repeat_buyer']);
    }

    public function test_order_yang_belum_completed_tidak_menjadikan_pembeli_repeat(): void
    {
        $this->createOrder('REPEAT-IN-PROGRESS', 'SHIPPED', 'buyer-belum-selesai');
        $current = $this->createOrder('REPEAT-CURRENT', 'READY_TO_SHIP', 'buyer-belum-selesai');

        $response = $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&limit=50');
        $row = collect($response->json('data'))->firstWhere('id', $current->id);

        $response->assertOk();
        $this->assertSame(0, $row['buyer_previous_order_count']);
        $this->assertFalse($row['is_repeat_buyer']);
    }

    public function test_order_eligible_tanpa_escrow_lama_ditandai_tertunda(): void
    {
        $order = $this->createOrder('ESCROW-OVERDUE', 'READY_TO_SHIP', 'buyer-escrow');
        $order->update(['ordered_at' => now()->subMinutes(31)]);

        $response = $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&limit=50');
        $row = collect($response->json('data'))->firstWhere('id', $order->id);

        $response->assertOk();
        $this->assertSame('overdue', $row['escrow_sync']['state']);
        $this->assertGreaterThanOrEqual(31, $row['escrow_sync']['age_minutes']);
    }

    public function test_varian_berakhiran_angka_memakai_stok_item_induk(): void
    {
        $warehouse = Warehouse::create([
            'code' => 'WH-ROOT-STOCK',
            'name' => 'Warehouse Root Stock',
            'type' => 'internal',
            'active' => true,
        ]);
        $rootItem = Item::create([
            'code' => 'S2RDM',
            'name' => 'S2RDM',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
        ]);
        $variantItem = Item::create([
            'code' => 'S2RDM-2',
            'name' => 'S2RDM Varian 2',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
        ]);
        InventoryStock::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $rootItem->id,
            'qty' => 10,
            'allocated_qty' => 3,
        ]);
        $order = $this->createOrder('ROOT-STOCK-ORDER', 'READY_TO_SHIP', 'buyer-root-stock');
        MarketplaceOrderItem::create([
            'marketplace_order_id' => $order->id,
            'order_id' => $order->id,
            'internal_item_id' => $variantItem->id,
            'item_sku' => 'SKU-S2RDM-2',
            'qty' => 1,
        ]);

        $response = $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&limit=50');
        $row = collect($response->json('data'))->firstWhere('id', $order->id);

        $response->assertOk();
        $this->assertEquals(7.0, $row['items'][0]['internal_item']['stock_available']);
        $this->assertSame('S2RDM', $row['items'][0]['internal_item']['stock_reference_code']);
    }

    public function test_order_instan_masuk_tab_statusnya_tanpa_tab_khusus(): void
    {
        $readyOrder = $this->createOrder('INSTANT-READY', 'READY_TO_SHIP', 'buyer-instant');
        $readyOrder->update(['shipping_carrier' => 'Instant']);
        $processedOrder = $this->createOrder('INSTANT-PROCESSED', 'PROCESSED', 'buyer-instant');
        $processedOrder->update(['shipping_carrier' => 'Same Day']);

        $ready = $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&limit=50');
        $processed = $this->getJson('/api/marketplace/local-orders-paginated?tab=processed&limit=50');

        $ready->assertOk()->assertJsonFragment(['id' => $readyOrder->id]);
        $processed->assertOk()->assertJsonFragment(['id' => $processedOrder->id]);
        $this->get('/marketplace/orders')
            ->assertOk()
            ->assertDontSee('data-tab="processed_instant"', false)
            ->assertSee('Sedang Dikemas');
    }

    public function test_order_batal_ada_di_subtab_perlu_dikirim_bukan_retur_refund(): void
    {
        $cancelled = $this->createOrder('CANCELLED-ORDER', 'CANCELLED', 'buyer-cancelled');

        $ready = $this->getJson('/api/marketplace/local-orders-paginated?tab=ready&sub_tab=cancel&limit=50');
        $rrc = $this->getJson('/api/marketplace/local-orders-paginated?tab=rrc&limit=50');

        $ready->assertOk()->assertJsonFragment(['id' => $cancelled->id]);
        $rrc->assertOk()->assertJsonMissing(['id' => $cancelled->id]);
        $this->get('/marketplace/orders')
            ->assertOk()
            ->assertSee('data-sub="cancel"', false)
            ->assertDontSee('switchSubTabRrc(\'cancel\'', false);
    }

    public function test_tracking_gagal_masuk_subtab_pengiriman_gagal(): void
    {
        $failedOrder = $this->createOrder('FAILED-DELIVERY-ORDER', 'SHIPPED', 'buyer-failed-delivery');
        app(MarketplaceTrackingStatusService::class)->record($failedOrder, [[
            'logistics_status' => 'FAILED_DELIVERY',
            'description' => 'Pengiriman gagal karena penerima tidak dapat dihubungi.',
        ]]);

        $shipped = $this->getJson('/api/marketplace/local-orders-paginated?tab=shipped&sub_tab=failed&limit=50');
        $counts = $this->getJson('/api/marketplace/local-order-counts');

        $shipped->assertOk()
            ->assertJsonFragment(['id' => $failedOrder->id])
            ->assertJsonPath('data.0.delivery_failed', true)
            ->assertJsonPath('data.0.tracking_status', 'FAILED_DELIVERY');
        $counts->assertOk()->assertJsonPath('failed_delivery', 1);
        $this->get('/marketplace/orders')
            ->assertOk()
            ->assertSee('Pengiriman Gagal');
    }
}
