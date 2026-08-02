<?php

namespace Tests\Feature\Console;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\OrderFulfillment;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\MarketplaceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

class MarketplaceRepairStuckOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    private Channel $shopee;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shopee = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $this->store = Store::create([
            'channel_id' => $this->shopee->id,
            'code' => 'REPAIR-STORE',
            'name' => 'Toko Repair',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'dummy'],
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

    private function createOrder(string $orderSn, string $status = 'PROCESSED'): MarketplaceOrder
    {
        return MarketplaceOrder::create([
            'store_id' => $this->store->id,
            'external_order_id' => $orderSn,
            'channel_order_id' => $orderSn,
            'order_date' => now(),
            'ordered_at' => now(),
            'order_status' => $status,
        ]);
    }

    public function test_preview_tidak_mengubah_data(): void
    {
        $order = $this->createOrder('REPAIR-PREVIEW');

        $this->artisan('marketplace:repair-stuck-orders')
            ->expectsOutputToContain('[MISSING_FULFILLMENT]')
            ->expectsOutputToContain('Preview selesai')
            ->assertSuccessful();

        $this->assertDatabaseMissing('order_fulfillments', [
            'marketplace_order_id' => $order->id,
        ]);
    }

    public function test_apply_membuat_fulfillment_yang_hilang_dan_memulihkan_handover(): void
    {
        $missing = $this->createOrder('REPAIR-MISSING');
        $handover = $this->createOrder('REPAIR-HANDOVER', 'PROCESSED');

        $fulfillment = OrderFulfillment::create([
            'marketplace_order_id' => $handover->id,
            'status' => OrderFulfillment::STATUS_CONFIRMED,
        ]);

        $this->artisan('marketplace:repair-stuck-orders', ['--apply' => true])
            ->expectsOutputToContain('Repair selesai')
            ->assertSuccessful();

        $this->assertDatabaseHas('order_fulfillments', [
            'marketplace_order_id' => $missing->id,
            'status' => OrderFulfillment::STATUS_PENDING_REVIEW,
        ]);
        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $handover->id,
            'order_status' => 'READY_TO_HANDOVER',
        ]);
        $this->assertDatabaseHas('order_fulfillments', [
            'id' => $fulfillment->id,
            'status' => OrderFulfillment::STATUS_CONFIRMED,
        ]);
    }

    public function test_repair_terdaftar_di_scheduler(): void
    {
        Artisan::call('schedule:list');

        $this->assertStringContainsString('repair-stuck-orders', Artisan::output());
    }

    public function test_move_to_ready_memindahkan_order_processed_dengan_fulfillment_aktif(): void
    {
        $order = $this->createOrder('REPAIR-MOVE-READY', 'PROCESSED');

        OrderFulfillment::create([
            'marketplace_order_id' => $order->id,
            'status' => OrderFulfillment::STATUS_PENDING_REVIEW,
        ]);

        $this->artisan('marketplace:repair-stuck-orders', [
            '--apply' => true,
            '--move-to-ready' => true,
        ])->expectsOutputToContain('MOVE_TO_READY')
            ->assertSuccessful();

        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $order->id,
            'order_status' => 'READY_TO_HANDOVER',
            'status' => 'packed',
        ]);
    }

    public function test_verifikasi_api_memindahkan_processed_yang_sudah_shipped(): void
    {
        $order = $this->createOrder('VERIFY-SHIPPED', 'PROCESSED');

        MarketplaceOrderItem::create([
            'marketplace_order_id' => $order->id,
            'order_id' => $order->id,
            'item_name' => 'Belum Mapping',
            'model_sku' => 'SKU-BELUM-MAPPING',
            'mapping_status' => 'mapping_not_found',
            'data_status' => 'incomplete',
            'qty' => 1,
        ]);

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getOrderDetail')
                ->once()
                ->withArgs(function (Store $store, array $orderSns) use ($order) {
                    return $store->id === $order->store_id && $orderSns === [$order->channel_order_id];
                })
                ->andReturn([
                    'response' => [
                        'order_list' => [[
                            'order_sn' => $order->channel_order_id,
                            'order_status' => 'SHIPPED',
                        ]],
                    ],
                ]);
        });

        $this->artisan('marketplace:verify-processed-orders', ['--apply' => true])
            ->expectsOutputToContain('[MOVE]')
            ->assertSuccessful();

        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $order->id,
            'order_status' => 'SHIPPED',
            'status' => 'shipped',
        ]);
    }

    public function test_verifikasi_api_tidak_memindahkan_jika_api_masih_processed(): void
    {
        $order = $this->createOrder('VERIFY-STILL-PROCESSED', 'PROCESSED');

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getOrderDetail')->once()->andReturn([
                'response' => [
                    'order_list' => [[
                        'order_sn' => $order->channel_order_id,
                        'order_status' => 'PROCESSED',
                    ]],
                ],
            ]);
        });

        $this->artisan('marketplace:verify-processed-orders', ['--apply' => true])
            ->expectsOutputToContain('[UNCHANGED]')
            ->assertSuccessful();

        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $order->id,
            'order_status' => 'PROCESSED',
        ]);
    }

    public function test_sync_juga_memverifikasi_order_tanpa_booking_sn(): void
    {
        $order = $this->createOrder('SYNC-NO-BOOKING', 'PROCESSED');
        $order->update([
            'booking_sn' => null,
            'processed_api_checked_at' => now()->subMinutes(20),
        ]);

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getOrders')
                ->times(7)
                ->andReturn(['response' => ['order_list' => []]]);
            $mock->shouldReceive('getOrderDetail')
                ->once()
                ->withArgs(function (Store $store, array $orderSns) use ($order) {
                    return $store->id === $order->store_id && $orderSns === [$order->channel_order_id];
                })
                ->andReturn([
                    'response' => [
                        'order_list' => [[
                            'order_sn' => $order->channel_order_id,
                            'order_status' => 'SHIPPED',
                        ]],
                    ],
                ]);
        });

        $result = app(MarketplaceSyncService::class)->syncOrders(
            $this->store,
            now()->subMinutes(5)->timestamp,
            now()->timestamp,
        );

        $this->assertSame(1, $result['processed_verification']['moved']);
        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $order->id,
            'booking_sn' => null,
            'order_status' => 'SHIPPED',
            'status' => 'shipped',
        ]);
    }
}
