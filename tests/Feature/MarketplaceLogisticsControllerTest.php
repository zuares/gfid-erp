<?php

namespace Tests\Feature;

use App\Http\Controllers\MarketplaceLogisticsController;
use App\Jobs\ProcessTikTokShipmentJob;
use App\Models\Store;
use App\Services\Channels\ChannelManager;
use App\Services\Channels\Contracts\MarketplaceChannel;
use App\Services\Channels\Shopee\ShopeeChannel;
use App\Services\Marketplace\MarketplaceLogisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

class MarketplaceLogisticsControllerTest extends TestCase
{
    public function test_order_list_forwards_shopee_get_order_list_parameters(): void
    {
        $store = new Store();
        $store->id = 7;

        $driver = \Mockery::mock(MarketplaceChannel::class);
        $driver->shouldReceive('getOrders')
            ->once()
            ->withArgs(function (Store $receivedStore, int $timeFrom, int $timeTo, int $pageSize, string $cursor, string $status, string $timeRangeField): bool {
                return $receivedStore->id === 7
                    && $timeFrom === 1700000000
                    && $timeTo === 1700003600
                    && $pageSize === 100
                    && $cursor === 'CURSOR-2'
                    && $status === 'UNPAID'
                    && $timeRangeField === 'create_time';
            })
            ->andReturn(['response' => ['order_list' => []]]);

        $this->mock(ChannelManager::class, function (MockInterface $mock) use ($store, $driver): void {
            $mock->shouldReceive('driver')->once()->with($store)->andReturn($driver);
        });

        $request = Request::create('/order-list', 'GET', [
            'time_from' => 1700000000,
            'time_to' => 1700003600,
            'page_size' => 200,
            'cursor' => 'CURSOR-2',
            'order_status' => 'UNPAID',
            'time_range_field' => 'create_time',
        ]);
        $response = app(MarketplaceLogisticsController::class)->getOrderList($store, $request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->getData(true)['response']['order_list']);
    }

    public function test_pending_invoice_order_list_uses_dedicated_endpoint_without_status_filter(): void
    {
        $store = new Store();
        $store->id = 7;

        $driver = \Mockery::mock(ShopeeChannel::class);
        $driver->shouldReceive('getPendingBuyerInvoiceOrderList')
            ->once()
            ->withArgs(function (Store $receivedStore, int $timeFrom, int $timeTo, int $pageSize, string $cursor, string $timeRangeField): bool {
                return $receivedStore->id === 7
                    && $timeFrom === 1700000000
                    && $timeTo === 1700003600
                    && $pageSize === 100
                    && $cursor === 'CURSOR-2'
                    && $timeRangeField === 'create_time';
            })
            ->andReturn(['response' => ['order_list' => []]]);

        $this->mock(ChannelManager::class, function (MockInterface $mock) use ($store, $driver): void {
            $mock->shouldReceive('driver')->once()->with($store)->andReturn($driver);
        });

        $request = Request::create('/order-list', 'GET', [
            'time_from' => 1700000000,
            'time_to' => 1700003600,
            'page_size' => 200,
            'cursor' => 'CURSOR-2',
            'order_status' => 'INVOICE_PENDING',
            'time_range_field' => 'create_time',
        ]);
        $response = app(MarketplaceLogisticsController::class)->getPendingBuyerInvoiceOrderList($store, $request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->getData(true)['response']['order_list']);
    }

    public function test_arrange_shipment_forwards_root_dropoff_method(): void
    {
        $store = new Store();
        $store->id = 7;

        $this->mock(MarketplaceLogisticsService::class, function (MockInterface $mock) {
            $mock->shouldReceive('arrangeShipment')
                ->once()
                ->withArgs(function (Store $receivedStore, string $orderSn, array $params, bool $isAutoSync): bool {
                    return $receivedStore->id === 7
                        && $orderSn === 'ORDER-001'
                        && $params['dropoff'] instanceof \stdClass
                        && ! array_key_exists('pickup', $params)
                        && ! array_key_exists('non_integrated', $params)
                        && $isAutoSync === false;
                })
                ->andReturn(['success' => true]);
        });

        $request = Request::create('/ship', 'POST', ['dropoff' => []]);
        $response = app(MarketplaceLogisticsController::class)
            ->arrangeShipment($request, $store, 'ORDER-001');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
    }

    public function test_arrange_shipment_rejects_multiple_shipping_methods(): void
    {
        $store = new Store();
        $store->id = 7;

        $this->mock(MarketplaceLogisticsService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('arrangeShipment');
        });

        $request = Request::create('/ship', 'POST', [
            'pickup' => [],
            'dropoff' => [],
        ]);
        $response = app(MarketplaceLogisticsController::class)
            ->arrangeShipment($request, $store, 'ORDER-001');

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('tepat satu', $response->getData(true)['message']);
    }

    public function test_arrange_shipment_accepts_auto_sync_fallback_without_shipping_method(): void
    {
        $store = new Store();
        $store->id = 7;

        $this->mock(MarketplaceLogisticsService::class, function (MockInterface $mock) {
            $mock->shouldReceive('arrangeShipment')
                ->once()
                ->withArgs(function (Store $receivedStore, string $orderSn, array $params, bool $isAutoSync): bool {
                    return $receivedStore->id === 7
                        && $orderSn === 'ORDER-AUTO'
                        && $params === []
                        && $isAutoSync === true;
                })
                ->andReturn(['success' => true]);
        });

        $request = Request::create('/ship', 'POST', ['auto_sync_only' => 1]);
        $response = app(MarketplaceLogisticsController::class)
            ->arrangeShipment($request, $store, 'ORDER-AUTO');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
    }

    public function test_arrange_shipment_defaults_to_dropoff_for_shopee_when_method_is_missing(): void
    {
        $store = new Store();
        $store->id = 7;
        $store->setRelation('channel', (object) ['code' => 'shopee']);

        $this->mock(MarketplaceLogisticsService::class, function (MockInterface $mock) {
            $mock->shouldReceive('arrangeShipment')
                ->once()
                ->withArgs(function (Store $receivedStore, string $orderSn, array $params, bool $isAutoSync): bool {
                    return $receivedStore->id === 7
                        && $orderSn === 'ORDER-DROPOFF'
                        && $params['dropoff'] instanceof \stdClass
                        && $isAutoSync === false;
                })
                ->andReturn(['success' => true]);
        });

        $request = Request::create('/ship', 'POST', []);
        $response = app(MarketplaceLogisticsController::class)
            ->arrangeShipment($request, $store, 'ORDER-DROPOFF');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
    }

    public function test_tiktok_arrange_shipment_is_queued_to_avoid_browser_timeout(): void
    {
        $store = new Store();
        $store->id = 17;
        $store->setRelation('channel', (object) ['code' => 'tiktok']);
        Queue::fake();

        $request = Request::create('/ship', 'POST', [
            'package_id' => 'TT-PACKAGE-QUEUE-001',
            'shipping_service_id' => 'SERVICE-001',
        ]);
        $response = app(MarketplaceLogisticsController::class)
            ->arrangeShipment($request, $store, 'TT-ORDER-QUEUE-001');

        $this->assertSame(202, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['queued']);
        Queue::assertPushed(ProcessTikTokShipmentJob::class, function (ProcessTikTokShipmentJob $job): bool {
            return $job->storeId === 17
                && $job->orderSn === 'TT-ORDER-QUEUE-001'
                && $job->params['package_id'] === 'TT-PACKAGE-QUEUE-001';
        });
    }
}
