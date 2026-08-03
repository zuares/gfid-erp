<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Item;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceProductModel;
use App\Models\MarketplacePromotion;
use App\Models\Store;
use App\Models\SkuMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketplacePromotionsTest extends TestCase
{
    use RefreshDatabase;

    private function createStore(): Store
    {
        $channel = Channel::firstOrCreate(
            ['code' => 'shopee'],
            ['name' => 'Shopee']
        );

        return Store::create([
            'channel_id' => $channel->id,
            'code' => 'S' . random_int(1000, 9999),
            'name' => 'Toko Promosi',
            'status' => 'active',
            'is_active' => true,
            'external_shop_id' => '12345',
            'credentials' => [
                'partner_id' => '2000000',
                'partner_key' => 'dummy_key',
                'shop_id' => '12345',
                'access_token' => 'dummy_access_token',
                'refresh_token' => 'dummy_refresh_token',
                'base_url' => 'https://partner.shopeemobile.com',
            ],
            'token_expires_at' => now()->addHour(),
        ]);
    }

    private function requestPayload($request): array
    {
        $body = (string) $request->body();

        if ($body === '') {
            return [];
        }

        $json = json_decode($body, true);
        if (is_array($json)) {
            return $json;
        }

        parse_str($body, $parsed);

        return is_array($parsed) ? $parsed : [];
    }

    public function test_promotions_page_renders()
    {
        $this->withoutMiddleware();

        $response = $this->get(route('marketplace.promotions'));

        $response->assertOk();
        $response->assertSee('Promosi Diskon');
    }

    public function test_promotions_create_page_renders()
    {
        $this->withoutMiddleware();

        $store = $this->createStore();

        $response = $this->get(route('marketplace.promotions.create', [
            'store_id' => $store->id,
        ]));

        $response->assertOk();
        $response->assertSee('Buat Promosi');
        $response->assertSee('Simpan Promosi');
    }

    public function test_promotions_index_returns_discount_list()
    {
        $this->withoutMiddleware();

        $store = $this->createStore();

        Http::fake([
            '*/api/v2/discount/get_discount_list*' => Http::response([
                'response' => [
                    'discount_list' => [
                        [
                            'discount_id' => 101,
                            'discount_name' => 'Promo Mingguan',
                            'discount_status' => 'ongoing',
                            'start_time' => 1785553200,
                            'end_time' => 1792033200,
                            'item_count' => 3,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->withoutExceptionHandling();
        $response = $this->getJson('/api/marketplace/promotions?store_id=' . $store->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('promotions.0.discount_id', 101)
            ->assertJsonPath('promotions.0.discount_name', 'Promo Mingguan');
    }

    public function test_promotions_create_forwards_payloads_to_channel()
    {
        $this->withoutMiddleware();

        $store = $this->createStore();

        $payload = [
            'store_id' => $store->id,
            'discount_name' => 'Promo Baru',
            'start_time' => 1785553200,
            'end_time' => 1792033200,
            'item_list' => [
                [
                    'item_id' => 123456789,
                    'model_list' => [
                        [
                            'model_id' => 0,
                            'model_promotion_price' => 99000,
                        ],
                    ],
                ],
            ],
        ];

        Http::fake([
            '*/api/v2/discount/add_discount*' => Http::response([
                'response' => ['discount_id' => 202],
            ], 200),
            '*/api/v2/discount/add_discount_item*' => Http::response([
                'response' => ['discount_id' => 202],
            ], 200),
        ]);

        $response = $this->postJson('/api/marketplace/promotions', $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('discount_id', 202);
    }

    public function test_promotions_create_persists_local_record()
    {
        $this->withoutMiddleware();

        $store = $this->createStore();

        $payload = [
            'store_id' => $store->id,
            'discount_name' => 'Promo Duplikat',
            'start_time' => 1785553200,
            'end_time' => 1792033200,
            'duplicate_from_discount_id' => 101,
            'item_list' => [
                [
                    'item_id' => 123456789,
                    'model_list' => [
                        [
                            'model_id' => 0,
                            'model_promotion_price' => 99000,
                        ],
                    ],
                ],
            ],
        ];

        Http::fake([
            '*/api/v2/discount/add_discount*' => Http::response([
                'response' => ['discount_id' => 303],
            ], 200),
            '*/api/v2/discount/add_discount_item*' => Http::response([
                'response' => ['discount_id' => 303],
            ], 200),
        ]);

        $response = $this->postJson('/api/marketplace/promotions', $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('discount_id', 303);

        $record = MarketplacePromotion::query()
            ->where('store_id', $store->id)
            ->where('discount_id', 303)
            ->first();

        $this->assertNotNull($record);
        $this->assertSame('Promo Duplikat', $record->discount_name);
        $this->assertSame(101, $record->source_discount_id);
        $this->assertSame('synced', $record->sync_status);
        $this->assertSame(1, $record->item_count);
        $this->assertSame([
            [
                'item_id' => 123456789,
                'model_list' => [
                    [
                        'model_id' => 0,
                        'model_promotion_price' => 99000,
                        'model_promotion_percentage' => null,
                    ],
                ],
            ],
        ], $record->item_list_json);
    }

    public function test_promotions_update_ongoing_ignores_start_time_change()
    {
        $this->withoutMiddleware();

        $store = $this->createStore();

        MarketplacePromotion::create([
            'store_id' => $store->id,
            'channel_code' => 'shopee',
            'discount_id' => 808,
            'discount_name' => 'Promo Ongoing',
            'discount_status' => 'ongoing',
            'sync_status' => 'synced',
            'start_time' => 1785553200,
            'end_time' => 1792033200,
        ]);

        Http::fake([
            '*/api/v2/discount/update_discount*' => Http::response([
                'response' => ['discount_id' => 808],
            ], 200),
        ]);

        $request = \Illuminate\Http\Request::create(
            '/api/marketplace/promotions/' . $store->id . '/808/update',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'discount_name' => 'Promo Ongoing',
                'start_time' => 1800000000,
                'end_time' => 1792033200,
            ])
        );

        $controller = app(\App\Http\Controllers\MarketplaceController::class);
        $response = $controller->promotionUpdate($request, $store, 808);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);

        Http::assertSent(function ($sentRequest) {
            if (! str_contains($sentRequest->url(), '/api/v2/discount/update_discount')) {
                return false;
            }

            $payload = $this->requestPayload($sentRequest);

            return (int) ($payload['discount_id'] ?? 0) === 808
                && ($payload['discount_name'] ?? null) === 'Promo Ongoing'
                && (int) ($payload['end_time'] ?? 0) === 1792033200
                && ! array_key_exists('start_time', $payload);
        });
    }

    public function test_promotions_index_refreshes_invalid_access_token_then_retries()
    {
        $this->withoutMiddleware();

        $store = $this->createStore();

        $firstAttempt = true;

        Http::fake(function ($request) use (&$firstAttempt) {
            $url = $request->url();

            if (str_contains($url, '/api/v2/auth/access_token/get')) {
                return Http::response([
                    'response' => [
                        'access_token' => 'refreshed_access_token',
                        'refresh_token' => 'refreshed_refresh_token',
                        'expire_in' => 14400,
                    ],
                ], 200);
            }

            if (str_contains($url, '/api/v2/discount/get_discount_list')) {
                if ($firstAttempt) {
                    $firstAttempt = false;

                    return Http::response([
                        'error' => 'error_auth',
                        'message' => 'Invalid access_token, please have a check.',
                    ], 200);
                }

                return Http::response([
                    'response' => [
                        'discount_list' => [
                            [
                                'discount_id' => 303,
                                'discount_name' => 'Promo Setelah Refresh',
                                'discount_status' => 'ongoing',
                                'start_time' => 1785553200,
                                'end_time' => 1792033200,
                                'item_count' => 1,
                            ],
                        ],
                    ],
                ], 200);
            }

            return Http::response([], 200);
        });

        $response = $this->getJson('/api/marketplace/promotions?store_id=' . $store->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('promotions.0.discount_id', 303)
            ->assertJsonPath('promotions.0.discount_name', 'Promo Setelah Refresh');
    }

    public function test_promotions_detail_includes_original_price_fields()
    {
        $this->withoutMiddleware();

        $store = $this->createStore();

        $item = Item::create([
            'code' => 'INT-SKU-123',
            'name' => 'Internal SKU 123',
            'type' => 'finished_good',
            'active' => true,
        ]);

        SkuMapping::create([
            'marketplace_sku' => 'VAR-SKU-123',
            'channel_code' => 'shopee',
            'item_id' => $item->id,
            'notes' => 'test mapping',
        ]);

        $product = MarketplaceProduct::create([
            'store_id' => $store->id,
            'item_id' => 99887766,
            'item_name' => 'Produk Detail',
            'item_sku' => 'ITEM-SKU-123',
            'item_status' => 'normal',
            'has_model' => true,
            'price_min' => 125000,
            'price_max' => 150000,
            'stock_total' => 10,
        ]);

        MarketplaceProductModel::create([
            'marketplace_product_id' => $product->id,
            'model_id' => '1',
            'model_name' => 'Default',
            'model_sku' => 'VAR-SKU-123',
            'price' => 125000,
            'stock' => 10,
        ]);

        Http::fake([
            '*/api/v2/discount/get_discount*' => Http::response([
                'response' => [
                    'discount_info' => [
                        'discount_id' => 404,
                        'discount_name' => 'Promo Detail',
                        'discount_status' => 'ongoing',
                        'start_time' => 1785553200,
                        'end_time' => 1792033200,
                    ],
                    'discount_item_list' => [
                        [
                            'item_id' => 99887766,
                            'item_name' => 'Produk Detail',
                            'original_price' => 150000,
                            'model_list' => [
                                [
                                    'model_id' => 1,
                                    'model_name' => 'Default',
                                    'original_price' => 175000,
                                    'model_promotion_price' => 125000,
                                    'model_promotion_percentage' => 29,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/marketplace/promotions/' . $store->id . '/404');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('promotion.discount_id', 404)
            ->assertJsonPath('promotion.items.0.product_title_label', 'Produk Detail')
            ->assertJsonPath('promotion.items.0.item_original_price', 150000)
            ->assertJsonPath('promotion.items.0.model_list.0.model_original_price', 175000)
            ->assertJsonPath('promotion.items.0.model_list.0.variant_sku_label', 'VAR-SKU-123')
            ->assertJsonPath('promotion.items.0.model_list.0.sku_mapping_code', 'INT-SKU-123')
            ->assertJsonPath('promotion.items.0.model_list.0.promo_stock', 10)
            ->assertJsonPath('promotion.items.0.model_list.0.model_promotion_price', 125000);
    }

    public function test_promotions_detail_uses_cached_snapshot_on_second_open()
    {
        $this->withoutMiddleware();

        $store = $this->createStore();

        $callCount = 0;

        Http::fake(function ($request) use (&$callCount) {
            $url = $request->url();

            if (str_contains($url, '/api/v2/discount/get_discount')) {
                $callCount++;

                if ($callCount > 2) {
                    return Http::response([
                        'error' => 'unexpected_second_call',
                        'message' => 'API should not be called again when cache is warm.',
                    ], 500);
                }

                return Http::response([
                    'response' => [
                        'discount_info' => [
                            'discount_id' => 505,
                            'discount_name' => 'Promo Cache',
                            'discount_status' => 'ongoing',
                            'start_time' => 1785553200,
                            'end_time' => 1792033200,
                        ],
                        'discount_item_list' => [
                            [
                                'item_id' => 99887766,
                                'item_name' => 'Produk Cache',
                                'original_price' => 150000,
                                'model_list' => [
                                    [
                                        'model_id' => 1,
                                        'model_name' => 'Default',
                                        'original_price' => 175000,
                                        'model_promotion_price' => 125000,
                                        'model_promotion_percentage' => 29,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200);
            }

            return Http::response([], 200);
        });

        $first = $this->getJson('/api/marketplace/promotions/' . $store->id . '/505');
        $first->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cached', false);

        $second = $this->getJson('/api/marketplace/promotions/' . $store->id . '/505');
        $second->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cached', true)
            ->assertJsonPath('promotion.discount_id', 505);

        $refresh = $this->getJson('/api/marketplace/promotions/' . $store->id . '/505?refresh=1');
        $refresh->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cached', false)
            ->assertJsonPath('promotion.discount_id', 505);

        $this->assertSame(2, $callCount);
    }

    public function test_promotions_activate_now_updates_start_time_and_status()
    {
        $this->withoutMiddleware();

        $store = $this->createStore();

        MarketplacePromotion::create([
            'store_id' => $store->id,
            'channel_code' => 'shopee',
            'discount_id' => 606,
            'discount_name' => 'Promo Upcoming',
            'discount_status' => 'upcoming',
            'sync_status' => 'synced',
            'start_time' => 1785553200,
            'end_time' => 1792033200,
        ]);

        Http::fake([
            '*/api/v2/discount/update_discount*' => Http::response([
                'response' => [
                    'discount_id' => 606,
                    'modify_time' => 1785553200,
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/marketplace/promotions/' . $store->id . '/606/activate', [
            'current_status' => 'upcoming',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Promosi berhasil diaktifkan sekarang.');
    }

    public function test_promotions_deactivate_ongoing_ends_campaign()
    {
        $this->withoutMiddleware();

        $store = $this->createStore();

        MarketplacePromotion::create([
            'store_id' => $store->id,
            'channel_code' => 'shopee',
            'discount_id' => 707,
            'discount_name' => 'Promo Ongoing',
            'discount_status' => 'ongoing',
            'sync_status' => 'synced',
            'start_time' => 1785553200,
            'end_time' => 1792033200,
        ]);

        Http::fake([
            '*/api/v2/discount/end_discount*' => Http::response([
                'response' => [
                    'discount_id' => 707,
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/marketplace/promotions/' . $store->id . '/707/deactivate', [
            'current_status' => 'ongoing',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Promosi berhasil dinonaktifkan.');
    }
}
