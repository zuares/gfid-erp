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

    public function test_invalid_tiktok_token_header_refreshes_token_then_retries_request(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');
        config()->set('tiktok_shop.auth_url', 'https://auth.tiktok-shops.com');

        $channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-TOKEN-REFRESH',
            'name' => 'TikTok Token Refresh Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
            'credentials' => [
                'app_key' => 'app-key-test',
                'app_secret' => 'app-secret-test',
                'access_token' => 'expired-access-token',
                'refresh_token' => 'refresh-token-test',
                'shop_cipher' => 'shop-cipher-test',
            ],
        ]);

        $categoryRequests = 0;
        Http::fake(function ($request) use (&$categoryRequests) {
            if (str_contains($request->url(), '/api/v2/token/refresh')) {
                return Http::response([
                    'code' => 0,
                    'data' => [
                        'access_token' => 'refreshed-access-token',
                        'refresh_token' => 'rotated-refresh-token',
                        'access_token_expire_in' => 7200,
                        'refresh_token_expire_in' => 86400,
                    ],
                ]);
            }

            if (str_contains($request->url(), '/product/202309/categories')) {
                $categoryRequests++;
                if ($categoryRequests === 1) {
                    return Http::response([
                        'code' => 36009004,
                        'message' => "Invalid credentials. The 'x-tts-access-token' header is invalid.",
                    ]);
                }

                $this->assertSame('refreshed-access-token', $request->header('x-tts-access-token')[0] ?? null);

                return Http::response([
                    'code' => 0,
                    'message' => 'Success',
                    'data' => ['categories' => []],
                ]);
            }

            return Http::response(['code' => -1, 'message' => 'Unexpected request'], 500);
        });

        $response = app(TikTokShopChannel::class)->getProductCategories($store);

        $this->assertSame(0, $response['code']);
        $this->assertSame(2, $categoryRequests);
        $this->assertSame('refreshed-access-token', $store->fresh()->credential('access_token'));
        $this->assertSame('rotated-refresh-token', $store->fresh()->credential('refresh_token'));
        Http::assertSentCount(3);
    }

    public function test_get_unsettled_transactions_uses_the_v202507_finance_endpoint(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-UNSETTLED-FINANCE',
            'name' => 'TikTok Unsettled Finance Test',
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
            'https://open-api.tiktokglobalshop.com/finance/202507/orders/unsettled*' => Http::response([
                'code' => 0,
                'data' => [
                    'transactions' => [],
                    'next_page_token' => '',
                ],
            ]),
        ]);

        $response = app(TikTokShopChannel::class)->getUnsettledTransactions(
            $store,
            1754006400,
            1754438400,
            '',
            100,
        );

        $this->assertSame([], data_get($response, 'data.transactions'));
        Http::assertSent(function ($request): bool {
            $url = $request->url();

            return $request->method() === 'GET'
                && str_starts_with($url, 'https://open-api.tiktokglobalshop.com/finance/202507/orders/unsettled?')
                && str_contains($url, 'search_time_ge=1754006400')
                && str_contains($url, 'search_time_lt=1754438400')
                && str_contains($url, 'page_size=100')
                && str_contains($url, 'sort_field=order_create_time')
                && str_contains($url, 'sort_order=DESC')
                && str_contains($url, 'shop_cipher=shop-cipher-test')
                && str_contains($url, 'sign=');
        });
    }

    public function test_statement_finance_methods_use_the_statement_and_v202501_detail_endpoints(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-STATEMENT-FINANCE',
            'name' => 'TikTok Statement Finance Test',
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
            'https://open-api.tiktokglobalshop.com/finance/202309/statements*' => Http::response([
                'code' => 0,
                'data' => ['statements' => [], 'next_page_token' => ''],
            ]),
            'https://open-api.tiktokglobalshop.com/finance/202501/statements/STATEMENT-1/statement_transactions*' => Http::response([
                'code' => 0,
                'data' => ['transactions' => [], 'next_page_token' => ''],
            ]),
        ]);

        $driver = app(TikTokShopChannel::class);
        $statements = $driver->getStatements($store, 1754006400, 1754438400, '', 100);
        $transactions = $driver->getTransactionsByStatement($store, 'STATEMENT-1', '', 100);

        $this->assertSame([], data_get($statements, 'data.statements'));
        $this->assertSame([], data_get($transactions, 'data.transactions'));
        Http::assertSent(function ($request): bool {
            $url = $request->url();

            return $request->method() === 'GET'
                && str_contains($url, 'app_key=app-key-test')
                && str_contains($url, 'shop_cipher=shop-cipher-test')
                && str_contains($url, 'sign=');
        });
        Http::assertSentCount(2);
    }

    public function test_finance_get_retries_tiktok_rate_limit_response(): void
    {
        config()->set('tiktok_shop.finance_min_gap_ms', 0);
        config()->set('tiktok_shop.rate_limit_backoff_seconds', 0);
        config()->set('tiktok_shop.rate_limit_max_sleep_seconds', 0);
        config()->set('tiktok_shop.rate_limit_max_attempts', 2);

        $channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-RATE-LIMIT-RETRY',
            'name' => 'TikTok Rate Limit Retry',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
            'external_shop_id' => 'shop-123',
            'credentials' => [
                'app_key' => 'app-key-test',
                'app_secret' => 'app-secret-test',
                'access_token' => 'access-token-test',
                'shop_cipher' => 'shop-cipher-test',
            ],
        ]);

        Http::fake([
            'https://open-api.tiktokglobalshop.com/finance/202309/withdrawals*' => Http::sequence()
                ->push([
                    'message' => 'Too many requests. Please retry later.',
                ], 429, ['Retry-After' => '0'])
                ->push([
                    'code' => 0,
                    'data' => [
                        'withdrawals' => [],
                        'next_page_token' => '',
                    ],
                ], 200),
        ]);

        $response = app(TikTokShopChannel::class)->getSellerFinancialTransactions(
            $store,
            1754006400,
            1754438400,
            ['SETTLE'],
        );

        $this->assertSame(0, $response['code']);
        $this->assertSame([], data_get($response, 'data.withdrawals'));
        Http::assertSentCount(2);
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

    public function test_completed_order_and_dispute_line_item_are_normalized(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-DISPUTE-TEST',
            'name' => 'TikTok Dispute Test Store',
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
            'id' => 'tts-order-completed',
            'status' => 'COMPLETED',
            'create_time' => 1700000000,
            'update_time' => 1700050000,
            'delivery_time' => 1700040000,
            'rts_time' => 1700010000,
            'payment' => [
                'total_amount' => '120000',
            ],
            'line_items' => [[
                'id' => 'line-dispute',
                'product_id' => 'prod-1',
                'product_name' => 'Produk Dispute',
                'cancel_reason' => 'Did not receive parcel',
                'cancel_user' => 'BUYER',
                'original_price' => '120000',
                'sale_price' => '120000',
            ]],
        ];

        Http::fake([
            'https://open-api.tiktokglobalshop.com/order/202309/orders*' => Http::response([
                'code' => 0,
                'data' => ['orders' => [$order]],
            ]),
        ]);

        $driver = app(TikTokShopChannel::class);
        $res = $driver->getOrderDetail($store, ['tts-order-completed']);
        $normalized = $res['response']['order_list'][0];

        $this->assertSame('COMPLETED', $normalized['order_status']);
        $this->assertTrue($normalized['has_return_request']);
        $this->assertSame('Did not receive parcel (BUYER)', $normalized['dispute_reason']);
        $this->assertSame('Did not receive parcel', $normalized['item_list'][0]['cancel_reason']);
        $this->assertSame('BUYER', $normalized['item_list'][0]['cancel_user']);
    }

    public function test_get_return_list_handles_missing_scope_gracefully(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        $channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-RETURN-SCOPE',
            'name' => 'TikTok Return Scope Test',
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
            'https://open-api.tiktokglobalshop.com/return_refund/202309/returns/search*' => Http::response([
                'code' => 105005,
                'message' => 'Access denied. You have no permission to call this API.',
            ]),
        ]);

        $driver = app(TikTokShopChannel::class);
        $res = $driver->getReturnList($store, 1, 20);

        $this->assertSame('scope_missing', $res['error']);
        $this->assertStringContainsString('seller.return_refund.basic', $res['message']);
        $this->assertSame([], $res['response']['return']);
    }

    public function test_product_creation_endpoints_use_v202309_contracts(): void
    {
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');
        $channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-CREATE-ENDPOINTS',
            'name' => 'TikTok Create Endpoints',
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
        Http::fake(fn ($request) => Http::response(match (true) {
            str_contains($request->url(), '/images/upload') => ['code' => 0, 'data' => ['uri' => 'tos://image', 'url' => 'https://img.test/image.jpg']],
            str_contains($request->url(), '/listing_check') => ['code' => 0, 'data' => ['check_result' => 'PASS']],
            str_contains($request->url(), '/categories/') && str_contains($request->url(), '/attributes') => ['code' => 0, 'data' => ['attributes' => []]],
            str_contains($request->url(), '/categories') => ['code' => 0, 'data' => ['categories' => []]],
            str_contains($request->url(), '/warehouses') => ['code' => 0, 'data' => ['warehouses' => []]],
            default => ['code' => 0, 'data' => ['product_id' => 'product-new']],
        }));
        $driver = app(TikTokShopChannel::class);
        $payload = ['title' => 'Produk', 'save_mode' => 'AS_DRAFT', 'idempotency_key' => 'key'];

        $driver->getProductCategories($store);
        $driver->getProductAttributes($store, '12345');
        $driver->getProductWarehouses($store);
        $upload = $driver->uploadProductImage($store, 'image-bytes', 'image.jpg', 'image/jpeg');
        $driver->checkProductListing($store, $payload);
        $driver->addProduct($store, $payload);

        $this->assertSame('tos://image', data_get($upload, 'data.uri'));
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/product/202309/categories?')
            && str_contains($request->url(), 'category_version=v2')
            && str_contains($request->url(), 'listing_platform=TIKTOK_SHOP'));
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/product/202309/products/listing_check?')
            && str_contains($request->url(), 'is_diagnosis_required=true')
            && $request->body() === '{"title":"Produk"}');
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/product/202309/images/upload?')
            && str_contains($request->body(), 'name="data"')
            && str_contains($request->body(), 'name="use_case"')
            && str_contains($request->body(), 'MAIN_IMAGE'));
    }
}
