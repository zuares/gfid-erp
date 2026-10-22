<?php

namespace Tests\Unit\Services\Channels\TikTokShop;

use App\Models\Channel;
use App\Models\Store;
use App\Services\Channels\TikTokShop\TikTokShopChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class TikTokShopChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_signature_matches_tiktok_v202309_reference_vector(): void
    {
        $channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-SIGNATURE',
            'name' => 'TikTok Signature Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
            'credentials' => [
                'app_key' => '29a39d',
                'app_secret' => 'e59af819cc',
            ],
        ]);

        $method = new ReflectionMethod(TikTokShopChannel::class, 'sign');
        $method->setAccessible(true);

        $signature = $method->invoke(
            app(TikTokShopChannel::class),
            $store,
            '/authorization/202309/shops',
            [
                'app_key' => '29a39d',
                'timestamp' => '1623812664',
            ],
            ''
        );

        $this->assertSame(
            'b596b73e0cc6de07ac26f036364178ab16b0a907af13d43f0a0cd2345f582dc8',
            $signature
        );
    }

    public function test_post_request_keeps_tiktok_query_parameters_outside_json_body(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-POST-QUERY',
            'name' => 'TikTok POST Query Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
            'credentials' => [
                'app_key' => 'app-key-test',
                'app_secret' => 'app-secret-test',
                'access_token' => 'access-token-test',
                'shop_cipher' => 'shop-cipher-test',
            ],
        ]);

        Http::fake([
            'https://open-api.tiktokglobalshop.com/*' => Http::response([
                'code' => 0,
                'data' => [
                    'orders' => [],
                    'next_page_token' => null,
                ],
            ]),
        ]);

        $response = app(TikTokShopChannel::class)->getOrders(
            $store,
            1700000000,
            1700003600,
            20,
            '',
            '',
            'update_time'
        );
        $this->assertSame([], $response['response']['order_list']);

        Http::assertSent(function ($request): bool {
            $url = $request->url();

            return $request->method() === 'POST'
                && str_starts_with($url, 'https://open-api.tiktokglobalshop.com/order/202309/orders/search?')
                && str_contains($url, 'app_key=app-key-test')
                && str_contains($url, 'shop_cipher=shop-cipher-test')
                && str_contains($url, 'sign=')
                && str_contains($url, 'timestamp=')
                && str_contains($url, 'page_size=20')
                && str_contains($url, 'sort_field=update_time')
                && str_contains($url, 'sort_order=ASC')
                && $request->body() === '{"update_time_ge":1700000000,"update_time_lt":1700003600}';
        });
    }

    public function test_order_responses_are_normalized_to_the_shared_marketplace_schema(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-ORDER-NORMALIZE',
            'name' => 'TikTok Order Normalize Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
            'credentials' => [
                'app_key' => 'app-key-test',
                'app_secret' => 'app-secret-test',
                'access_token' => 'access-token-test',
                'shop_cipher' => 'shop-cipher-test',
            ],
        ]);

        $order = [
            'id' => 'tts-order-1',
            'status' => 'AWAITING_SHIPMENT',
            'create_time' => 1700000000,
            'shipping_provider' => 'J&T Express',
            'tracking_number' => 'JT123',
            'payment_method_name' => 'Cash on delivery',
            'payment' => [
                'total_amount' => '142023',
                'shipping_fee' => '54000',
                'currency' => 'IDR',
            ],
            'recipient_address' => [
                'first_name' => 'Buyer',
                'last_name' => 'Test',
                'postal_code' => '12345',
            ],
            'line_items' => [[
                'id' => 'line-1',
                'product_id' => 'product-1',
                'sku_id' => 'sku-1',
                'product_name' => 'Jaket Test',
                'sku_name' => 'Hitam, L',
                'seller_sku' => 'SKU-TEST',
                'quantity' => 2,
                'original_price' => '100000',
                'sale_price' => '85000',
                'sku_image' => 'https://example.test/item.jpg',
            ]],
        ];

        Http::fake([
            'https://open-api.tiktokglobalshop.com/order/202309/orders/search*' => Http::response([
                'code' => 0,
                'data' => ['orders' => [$order], 'next_page_token' => null],
            ]),
            'https://open-api.tiktokglobalshop.com/order/202309/orders*' => Http::response([
                'code' => 0,
                'data' => ['orders' => [$order]],
            ]),
        ]);

        $driver = app(TikTokShopChannel::class);
        $list = $driver->getOrders($store, 1700000000, 1700003600);
        $detail = $driver->getOrderDetail($store, ['tts-order-1']);

        foreach ([$list, $detail] as $response) {
            $normalized = $response['response']['order_list'][0];

            $this->assertSame('tts-order-1', $normalized['order_sn']);
            $this->assertSame('READY_TO_SHIP', $normalized['order_status']);
            $this->assertSame('AWAITING_SHIPMENT', $normalized['platform_order_status']);
            $this->assertSame('142023', $normalized['total_amount']);
            $this->assertSame('SKU-TEST', $normalized['item_list'][0]['model_sku']);
            $this->assertSame(2, $normalized['item_list'][0]['model_quantity_purchased']);
            $this->assertSame('Buyer Test', $normalized['recipient_address']['name']);
            $this->assertSame('12345', $normalized['recipient_address']['zip_code']);
            $this->assertSame('JT123', $normalized['package_list'][0]['tracking_no']);
        }
    }

    public function test_product_search_and_detail_use_the_v202309_product_endpoints(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-PRODUCT-API',
            'name' => 'TikTok Product API Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
            'credentials' => [
                'app_key' => 'app-key-test',
                'app_secret' => 'app-secret-test',
                'access_token' => 'access-token-test',
                'shop_cipher' => 'shop-cipher-test',
            ],
        ]);

        Http::fake([
            'https://open-api.tiktokglobalshop.com/product/202309/products/search*' => Http::response([
                'code' => 0,
                'data' => [
                    'products' => [['id' => 'product-1', 'title' => 'Jaket']],
                    'next_page_token' => 'next-token',
                ],
            ]),
            'https://open-api.tiktokglobalshop.com/product/202309/products/product-1*' => Http::response([
                'code' => 0,
                'data' => ['id' => 'product-1', 'title' => 'Jaket'],
            ]),
        ]);

        $search = app(TikTokShopChannel::class)->searchProducts($store, 'ALL', '', 100);
        $detail = app(TikTokShopChannel::class)->getProduct($store, 'product-1');

        $this->assertSame('next-token', $search['response']['next_page_token']);
        $this->assertSame('product-1', $detail['data']['id']);
        Http::assertSent(function ($request): bool {
            return $request->method() === 'POST'
                && str_contains($request->url(), '/product/202309/products/search?')
                && str_contains($request->url(), 'page_size=100')
                && str_contains($request->url(), 'shop_cipher=shop-cipher-test')
                && $request->body() === '{"status":"ALL","locale":"id-ID"}';
        });
    }

    public function test_order_finance_uses_the_v202501_statement_transactions_endpoint(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-FINANCE-API',
            'name' => 'TikTok Finance API Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
            'credentials' => [
                'app_key' => 'app-key-test',
                'app_secret' => 'app-secret-test',
                'access_token' => 'access-token-test',
                'shop_cipher' => 'shop-cipher-test',
            ],
        ]);

        Http::fake([
            'https://open-api.tiktokglobalshop.com/finance/202501/orders/ORDER-FIN-1/statement_transactions*' => Http::response([
                'code' => 0,
                'data' => [
                    'order_id' => 'ORDER-FIN-1',
                    'currency' => 'IDR',
                    'revenue_amount' => '120000',
                    'fee_and_tax_amount' => '-6000',
                    'shipping_cost_amount' => '-25000',
                    'settlement_amount' => '89000',
                    'sku_transactions' => [],
                ],
            ]),
        ]);

        $result = app(TikTokShopChannel::class)
            ->getOrderStatementTransactions($store, 'ORDER-FIN-1');

        $this->assertSame('89000', $result['data']['settlement_amount']);
        Http::assertSent(function ($request): bool {
            return $request->method() === 'GET'
                && str_contains($request->url(), '/finance/202501/orders/ORDER-FIN-1/statement_transactions?')
                && str_contains($request->url(), 'app_key=app-key-test')
                && str_contains($request->url(), 'shop_cipher=shop-cipher-test')
                && str_contains($request->url(), 'sign=');
        });
    }
}
