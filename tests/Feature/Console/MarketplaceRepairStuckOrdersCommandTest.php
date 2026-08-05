<?php

namespace Tests\Feature\Console;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceBooking;
use App\Models\OrderFulfillment;
use App\Models\Store;
use App\Jobs\SyncMarketplaceBookings;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\MarketplaceSyncService;
use App\Services\Channels\ChannelManager;
use App\Http\Controllers\MarketplaceController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
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

    public function test_sync_orders_otomatis_menjadwalkan_sync_booking(): void
    {
        $this->store->update(['connection_status' => 'CONNECTED']);
        Bus::fake();

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('syncOrders')->once()->andReturn([
                'new' => 0,
                'updated' => 0,
                'sku_empty' => 0,
                'mapping_not_found' => 0,
                'missing_hpp' => 0,
            ]);
        });

        $this->artisan('marketplace:sync-orders', [
            '--store' => $this->store->id,
            '--days' => 3,
        ])->assertSuccessful();

        Bus::assertDispatched(SyncMarketplaceBookings::class, function (SyncMarketplaceBookings $job): bool {
            return $job->store->id === $this->store->id;
        });
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

    public function test_booking_ready_to_ship_tidak_masuk_ke_processed_saat_backfill(): void
    {
        MarketplaceBooking::create([
            'store_id' => $this->store->id,
            'booking_sn' => 'BOOKING-READY-1',
            'booking_status' => 'READY_TO_SHIP',
            'items' => [],
        ]);

        $this->invokeBookingJobMethod('backfillMissingOrders');

        $this->assertDatabaseHas('marketplace_orders', [
            'channel_order_id' => 'BOOKING-READY-1',
            'booking_sn' => 'BOOKING-READY-1',
            'order_status' => 'READY_TO_SHIP',
        ]);
    }

    public function test_booking_ready_to_ship_mengembalikan_order_lama_dari_processed(): void
    {
        $order = $this->createOrder('BOOKING-ORDER-1', 'PROCESSED');
        $order->update(['booking_sn' => 'BOOKING-READY-2']);

        MarketplaceBooking::create([
            'store_id' => $this->store->id,
            'booking_sn' => 'BOOKING-READY-2',
            'order_sn' => $order->channel_order_id,
            'booking_status' => 'PROCESSED',
            'items' => [],
        ]);

        $this->invokeBookingJobMethod('normalizeUnarrangedOrders');

        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $order->id,
            'order_status' => 'READY_TO_SHIP',
            'status' => 'packed',
        ]);
    }

    public function test_sync_booking_tetap_menormalisasi_jika_request_api_gagal(): void
    {
        $order = $this->createOrder('BOOKING-API-FAIL', 'PROCESSED');
        $order->update(['booking_sn' => 'BOOKING-API-FAIL']);

        MarketplaceBooking::create([
            'store_id' => $this->store->id,
            'booking_sn' => 'BOOKING-API-FAIL',
            'booking_status' => 'READY_TO_SHIP',
            'items' => [],
        ]);

        $this->mock(ChannelManager::class, function (MockInterface $mock): void {
            $mock->shouldReceive('driver')->once()->andThrow(new \RuntimeException('API unavailable'));
        });

        $job = new SyncMarketplaceBookings($this->store);
        $job->handle($this->app->make(ChannelManager::class));

        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $order->id,
            'order_status' => 'READY_TO_SHIP',
            'status' => 'packed',
        ]);
    }

    public function test_local_orders_tidak_menduplikasi_booking_yang_sudah_menjadi_order(): void
    {
        $order = $this->createOrder('BOOKING-DUPLICATE', 'READY_TO_SHIP');
        $order->update([
            'booking_sn' => 'BOOKING-DUPLICATE',
            'external_order_id' => 'BOOKING-DUPLICATE',
        ]);

        MarketplaceBooking::create([
            'store_id' => $this->store->id,
            'booking_sn' => 'BOOKING-DUPLICATE',
            'booking_status' => 'READY_TO_SHIP',
            'items' => [],
        ]);

        $response = app(MarketplaceController::class)->localOrders();
        $rows = collect($response->getData(true))
            ->where('booking_sn', 'BOOKING-DUPLICATE')
            ->values();

        $this->assertCount(1, $rows);
        $this->assertFalse((bool) ($rows->first()['is_booking'] ?? false));
    }

    public function test_local_orders_memakai_status_live_api_saat_diminta_halaman_orders(): void
    {
        $order = $this->createOrder('LIVE-STATUS-ORDER', 'MATCHED');

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($order): void {
            $mock->shouldReceive('getOrderDetail')
                ->once()
                ->withArgs(function (Store $store, array $orderSns) use ($order): bool {
                    return $store->id === $order->store_id
                        && $orderSns === [$order->channel_order_id];
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

        $this->app->instance('request', Request::create(
            '/api/marketplace/local-orders?live_status=1&live_status_scope=matched',
            'GET',
            ['live_status' => '1', 'live_status_scope' => 'matched'],
        ));

        $rows = collect(app(MarketplaceController::class)->localOrders()->getData(true));
        $row = $rows->firstWhere('id', $order->id);

        $this->assertSame('SHIPPED', $row['order_status']);
        $this->assertSame('SHIPPED', $row['api_order_status']);
        $this->assertSame('api', $row['status_source']);
    }

    public function test_local_orders_memakai_status_live_api_untuk_ready_to_ship(): void
    {
        $order = $this->createOrder('LIVE-READY-TO-SHIP-ORDER', 'READY_TO_SHIP');

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($order): void {
            $mock->shouldReceive('getOrderDetail')
                ->once()
                ->withArgs(function (Store $store, array $orderSns) use ($order): bool {
                    return $store->id === $order->store_id
                        && $orderSns === [$order->channel_order_id];
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

        $this->app->instance('request', Request::create(
            '/api/marketplace/local-orders?live_status=1&live_status_scope=active',
            'GET',
            ['live_status' => '1', 'live_status_scope' => 'active'],
        ));

        $row = collect(app(MarketplaceController::class)->localOrders()->getData(true))
            ->firstWhere('id', $order->id);

        $this->assertSame('SHIPPED', $row['order_status']);
        $this->assertSame('SHIPPED', $row['api_order_status']);
        $this->assertSame('api', $row['status_source']);
    }

    public function test_local_orders_memverifikasi_status_cancel_invoice_dan_status_aktif_ke_api(): void
    {
        $statuses = [
            'UNPAID',
            'READY_TO_SHIP',
            'PROCESSED',
            'SHIPPED',
            'COMPLETED',
            'IN_CANCEL',
            'CANCELLED',
            'INVOICE_PENDING',
        ];
        $orders = collect($statuses)->mapWithKeys(function (string $status): array {
            $id = 'LIVE-' . $status;

            return [$id => $this->createOrder($id, $status)];
        });

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($orders, $statuses): void {
            $mock->shouldReceive('getOrderDetail')
                ->once()
                ->withArgs(function (Store $store, array $orderSns) use ($orders): bool {
                    return $store->id === $this->store->id
                        && empty(array_diff($orders->keys()->all(), $orderSns))
                        && empty(array_diff($orderSns, $orders->keys()->all()));
                })
                ->andReturn([
                    'response' => [
                        'order_list' => collect($statuses)->map(fn (string $status): array => [
                            'order_sn' => 'LIVE-' . $status,
                            'order_status' => $status,
                        ])->all(),
                    ],
                ]);
        });

        $this->app->instance('request', Request::create(
            '/api/marketplace/local-orders?live_status=1&live_status_scope=active',
            'GET',
            ['live_status' => '1', 'live_status_scope' => 'active'],
        ));

        $rows = collect(app(MarketplaceController::class)->localOrders()->getData(true))
            ->whereIn('id', $orders->pluck('id')->all())
            ->keyBy('channel_order_id');

        foreach ($statuses as $status) {
            $row = $rows->get('LIVE-' . $status);

            $this->assertSame($status, $row['api_order_status']);
            $this->assertSame('api', $row['status_source']);
        }
    }

    public function test_local_orders_mengabaikan_status_api_yang_tidak_dikenal(): void
    {
        $order = $this->createOrder('LIVE-UNKNOWN-STATUS', 'MATCHED');

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getOrderDetail')->once()->andReturn([
                'response' => [
                    'order_list' => [[
                        'order_sn' => 'LIVE-UNKNOWN-STATUS',
                        'order_status' => 'STATUS_BARU_YANG_BELUM_DIKENAL',
                    ]],
                ],
            ]);
        });

        $this->app->instance('request', Request::create(
            '/api/marketplace/local-orders?live_status=1&live_status_scope=matched',
            'GET',
            ['live_status' => '1', 'live_status_scope' => 'matched'],
        ));

        $row = collect(app(MarketplaceController::class)->localOrders()->getData(true))
            ->firstWhere('id', $order->id);

        $this->assertSame('MATCHED', $row['order_status']);
        $this->assertNull($row['api_order_status']);
        $this->assertSame('database', $row['status_source']);
    }

    public function test_local_orders_memindahkan_logistics_not_start_ke_subtab_tertunda(): void
    {
        $order = $this->createOrder('LIVE-LOGISTICS-PENDING', 'READY_TO_SHIP');

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getOrderDetail')->once()->andReturn([
                'response' => [
                    'order_list' => [[
                        'order_sn' => 'LIVE-LOGISTICS-PENDING',
                        'order_status' => 'READY_TO_SHIP',
                        'package_list' => [[
                            'logistics_status' => 'LOGISTICS_NOT_START',
                        ]],
                    ]],
                ],
            ]);
        });

        $this->app->instance('request', Request::create(
            '/api/marketplace/local-orders?live_status=1&live_status_scope=active',
            'GET',
            ['live_status' => '1', 'live_status_scope' => 'active'],
        ));

        $row = collect(app(MarketplaceController::class)->localOrders()->getData(true))
            ->firstWhere('id', $order->id);

        $this->assertSame('READY_TO_SHIP', $row['api_order_status']);
        $this->assertSame('LOGISTICS_NOT_START', $row['api_logistics_status']);
        $this->assertTrue($row['api_platform_pending']);
    }

    public function test_local_orders_mengeluarkan_logistics_ready_dari_tertunda(): void
    {
        $order = $this->createOrder('LIVE-LOGISTICS-READY', 'READY_TO_SHIP');

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getOrderDetail')->once()->andReturn([
                'response' => [
                    'order_list' => [[
                        'order_sn' => 'LIVE-LOGISTICS-READY',
                        'order_status' => 'READY_TO_SHIP',
                        'package_list' => [[
                            'logistics_status' => 'LOGISTICS_READY',
                        ]],
                    ]],
                ],
            ]);
        });

        $this->app->instance('request', Request::create(
            '/api/marketplace/local-orders?live_status=1&live_status_scope=active',
            'GET',
            ['live_status' => '1', 'live_status_scope' => 'active'],
        ));

        $row = collect(app(MarketplaceController::class)->localOrders()->getData(true))
            ->firstWhere('id', $order->id);

        $this->assertSame('READY_TO_SHIP', $row['api_order_status']);
        $this->assertSame('LOGISTICS_READY', $row['api_logistics_status']);
        $this->assertFalse($row['api_platform_pending']);
    }

    public function test_live_status_scope_matched_tidak_memanggil_api_untuk_processed(): void
    {
        $order = $this->createOrder('LIVE-SCOPE-PROCESSED', 'PROCESSED');
        $this->mock(MarketplaceApiGateway::class);

        $this->app->instance('request', Request::create(
            '/api/marketplace/local-orders?live_status=1&live_status_scope=matched',
            'GET',
            ['live_status' => '1', 'live_status_scope' => 'matched'],
        ));

        $row = collect(app(MarketplaceController::class)->localOrders()->getData(true))
            ->firstWhere('id', $order->id);

        $this->assertSame('PROCESSED', $row['order_status']);
        $this->assertSame('database', $row['status_source']);
    }

    public function test_sync_tidak_mempertahankan_processed_untuk_order_booking_ready_to_ship(): void
    {
        $order = $this->createOrder('BOOKING-ORDER-3', 'PROCESSED');
        $order->update(['booking_sn' => 'BOOKING-READY-3']);

        $method = new \ReflectionMethod(\App\Services\MarketplaceSyncService::class, 'upsertOrders');
        $method->setAccessible(true);
        $method->invoke(app(\App\Services\MarketplaceSyncService::class), $this->store, [[
            'order_sn' => $order->channel_order_id,
            'booking_sn' => $order->booking_sn,
            'order_status' => 'READY_TO_SHIP',
            'total_amount' => 100000,
            'item_list' => [],
        ]]);

        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $order->id,
            'order_status' => 'READY_TO_SHIP',
        ]);
    }

    private function invokeBookingJobMethod(string $method): void
    {
        $job = new SyncMarketplaceBookings($this->store);
        $reflection = new \ReflectionMethod($job, $method);
        $reflection->setAccessible(true);
        $reflection->invoke($job);
    }
}
