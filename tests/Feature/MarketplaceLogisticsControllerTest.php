<?php

namespace Tests\Feature;

use App\Http\Controllers\MarketplaceLogisticsController;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceLogisticsService;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Tests\TestCase;

class MarketplaceLogisticsControllerTest extends TestCase
{
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
}
