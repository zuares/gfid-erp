<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Services\Channels\TikTokShop\TikTokShopChannel;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\Marketplace\MarketplaceLogisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class TikTokShopLogisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiktok_shipping_parameter_queries_eligible_services(): void
    {
        $store = $this->storeWithOrder([
            'order_id' => 'TT-ORDER-001',
            'line_items' => [['id' => 'TT-LINE-001']],
        ]);

        Http::fake(fn (ClientRequest $request) => Http::response([
            'code' => 0,
            'message' => 'Success',
            'data' => [
                'shipping_services' => [[
                    'id' => 'SERVICE-001',
                    'name' => 'TikTok Shipping',
                    'is_default' => true,
                ]],
            ],
        ]));

        $result = app(TikTokShopChannel::class)->getShippingParameter($store, 'TT-ORDER-001');

        $this->assertSame('tiktok', data_get($result, 'response.platform'));
        $this->assertSame('SERVICE-001', data_get($result, 'response.shipping_services.0.id'));

        Http::assertSent(function (ClientRequest $request): bool {
            $path = parse_url($request->url(), PHP_URL_PATH);
            $body = json_decode($request->body(), true) ?: [];
            return $request->method() === 'POST'
                && $path === '/fulfillment/202309/orders/TT-ORDER-001/shipping_services/query'
                && data_get($body, 'order_line_item_ids.0') === 'TT-LINE-001'
                && data_get($body, 'weight.unit') === 'GRAM'
                && data_get($body, 'dimension.unit') === 'CM';
        });
    }

    public function test_tiktok_arrange_creates_package_then_ships_it(): void
    {
        $store = $this->storeWithOrder([
            'order_id' => 'TT-ORDER-002',
            'line_items' => [['id' => 'TT-LINE-002']],
        ]);

        Http::fake(function (ClientRequest $request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if ($path === '/fulfillment/202309/packages') {
                return Http::response([
                    'code' => 0,
                    'message' => 'Success',
                    'data' => ['package_id' => 'TT-PACKAGE-002'],
                ]);
            }

            if ($path === '/fulfillment/202309/packages/TT-PACKAGE-002/ship') {
                return Http::response(['code' => 0, 'message' => 'Success', 'data' => []]);
            }

            return Http::response(['code' => 404, 'message' => 'Unexpected URL'], 404);
        });

        $result = app(TikTokShopChannel::class)->shipOrder($store, 'TT-ORDER-002', [
            'shipping_service_id' => 'SERVICE-002',
            'handover_method' => 'DROP_OFF',
            'weight' => ['value' => '750', 'unit' => 'GRAM'],
            'dimension' => ['length' => '20', 'width' => '15', 'height' => '5', 'unit' => 'CM'],
        ]);

        $this->assertSame('TT-PACKAGE-002', $result['package_id']);
        $this->assertSame(0, $result['code']);
        Http::assertSentCount(2);
        Http::assertSent(function (ClientRequest $request): bool {
            $path = parse_url($request->url(), PHP_URL_PATH);
            $body = json_decode($request->body(), true) ?: [];

            return $path === '/fulfillment/202309/packages/TT-PACKAGE-002/ship'
                && ! array_key_exists('handover_method', $body);
        });
    }

    public function test_tiktok_seller_shipping_uses_existing_package_and_self_shipment(): void
    {
        $store = $this->storeWithOrder([
            'order_id' => 'TT-ORDER-SELLER-001',
            'shipping_type' => 'SELLER_SHIPPING',
            'packages' => [['package_id' => 'TT-PACKAGE-SELLER-001']],
        ]);

        Http::fake(function (ClientRequest $request) {
            if (parse_url($request->url(), PHP_URL_PATH) === '/fulfillment/202309/packages/TT-PACKAGE-SELLER-001/ship') {
                return Http::response([
                    'code' => 0,
                    'message' => 'Success',
                    'data' => [],
                ]);
            }

            return Http::response(['code' => 404, 'message' => 'Unexpected URL'], 404);
        });

        $result = app(TikTokShopChannel::class)->shipOrder($store, 'TT-ORDER-SELLER-001', [
            'self_shipment' => [
                'tracking_number' => 'JNE-SELLER-001',
                'shipping_provider_id' => 'JNE',
            ],
            'handover_method' => 'DROP_OFF',
        ]);

        $this->assertSame('TT-PACKAGE-SELLER-001', $result['package_id']);
        $this->assertSame(0, $result['code']);
        Http::assertSentCount(1);
        Http::assertSent(function (ClientRequest $request): bool {
            $body = json_decode($request->body(), true) ?: [];

            return data_get($body, 'self_shipment.tracking_number') === 'JNE-SELLER-001'
                && data_get($body, 'self_shipment.shipping_provider_id') === 'JNE'
                && ! array_key_exists('handover_method', $body);
        });
    }

    public function test_tiktok_seller_shipping_parameter_does_not_query_tiktok_services(): void
    {
        $store = $this->storeWithOrder([
            'order_id' => 'TT-ORDER-SELLER-002',
            'shipping_type' => 'SELLER_SHIPPING',
            'packages' => [['package_id' => 'TT-PACKAGE-SELLER-002']],
        ]);

        Http::fake();

        $result = app(TikTokShopChannel::class)->getShippingParameter($store, 'TT-ORDER-SELLER-002');

        $this->assertTrue(data_get($result, 'response.is_seller_shipping'));
        $this->assertSame([], data_get($result, 'response.shipping_services'));
        Http::assertNothingSent();
    }

    public function test_tiktok_shipping_document_returns_package_document_url(): void
    {
        $store = $this->storeWithOrder([
            'order_id' => 'TT-ORDER-003',
            'packages' => [['package_id' => 'TT-PACKAGE-003']],
        ]);

        Http::fake([
            '*fulfillment/202309/packages/TT-PACKAGE-003/shipping_documents*' => Http::response([
                'code' => 0,
                'message' => 'Success',
                'data' => [
                    'doc_url' => 'https://cdn.example.test/tiktok/TT-PACKAGE-003.pdf',
                    'tracking_number' => 'TT-AWB-003',
                ],
            ]),
        ]);

        $result = app(TikTokShopChannel::class)->getShippingDocument($store, ['TT-ORDER-003']);

        $this->assertSame('https://cdn.example.test/tiktok/TT-PACKAGE-003.pdf', data_get($result, 'response.documents.0.doc_url'));
        $this->assertSame('TT-AWB-003', data_get($result, 'response.documents.0.tracking_number'));

        Http::assertSent(function (ClientRequest $request): bool {
            $query = parse_url($request->url(), PHP_URL_QUERY) ?: '';
            parse_str($query, $params);

            return ($params['document_type'] ?? null) === 'SHIPPING_LABEL'
                && ($params['document_size'] ?? null) === 'A6'
                && ($params['document_format'] ?? null) === 'PDF';
        });
    }

    public function test_tiktok_arrange_returns_immediately_without_follow_up_tracking_requests(): void
    {
        $store = $this->storeWithOrder([
            'order_id' => 'TT-ORDER-004',
            'line_items' => [['id' => 'TT-LINE-004']],
        ]);

        $gateway = Mockery::mock(MarketplaceApiGateway::class);
        $gateway->shouldReceive('shipOrder')
            ->once()
            ->andReturn(['code' => 0, 'message' => 'Success', 'package_id' => 'TT-PACKAGE-004']);
        $gateway->shouldNotReceive('getTrackingNumber');
        $gateway->shouldNotReceive('getOrderDetail');

        $result = new MarketplaceLogisticsService($gateway)
            ->arrangeShipment($store, 'TT-ORDER-004', []);

        $order = $store->orders()->first()->fresh();
        $this->assertSame('TT-PACKAGE-004', $result['package_id']);
        $this->assertSame('PROCESSED', $order->order_status);
        $this->assertNotNull($order->shipping_arranged_at);
    }

    private function storeWithOrder(array $raw): Store
    {
        $channel = Channel::updateOrCreate(
            ['code' => 'TTK'],
            ['name' => 'TikTok Shop', 'status' => 'active', 'is_active' => true]
        );

        $store = Store::create([
            'code' => 'TTK-LOGISTICS-'.uniqid(),
            'name' => 'TikTok Logistics Test',
            'channel_id' => $channel->id,
            'external_shop_id' => 'SHOP-TEST',
            'region' => 'ID',
            'status' => 'active',
            'is_active' => true,
            'credentials' => [
                'app_key' => 'app-test',
                'app_secret' => 'secret-test',
                'access_token' => 'token-test',
                'shop_cipher' => 'cipher-test',
            ],
        ]);

        MarketplaceOrder::create([
            'store_id' => $store->id,
            'channel_order_id' => (string) ($raw['order_id'] ?? 'TT-ORDER-TEST'),
            'external_order_id' => (string) ($raw['order_id'] ?? 'TT-ORDER-TEST'),
            'order_date' => now(),
            'status' => 'new',
            'order_status' => 'READY_TO_SHIP',
            'raw_json' => $raw,
        ]);

        return $store->fresh(['channel']);
    }
}
