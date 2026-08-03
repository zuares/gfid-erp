<?php

namespace Tests\Unit\Services\Channels\Shopee;

use App\Models\Channel;
use App\Models\Store;
use App\Services\Channels\Shopee\ShopeeChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopeeDiscountApiTest extends TestCase
{
    use RefreshDatabase;

    private function createStore(array $overrides = []): Store
    {
        $channel = Channel::firstOrCreate(['code' => 'shopee'], ['name' => 'Shopee']);

        return Store::create(array_merge([
            'channel_id' => $channel->id,
            'code' => 'S' . rand(1000, 9999),
            'name' => 'Toko Uji',
            'status' => 'active',
            'is_active' => true,
            'external_shop_id' => '12345',
            'credentials' => [
                'partner_id' => '2000000',
                'partner_key' => 'dummy_key',
                'shop_id' => '12345',
                'access_token' => 'dummy_access_token',
                'refresh_token' => 'dummy_refresh_token',
            ],
            'token_expires_at' => now()->addHours(2),
        ], $overrides));
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

    public function test_get_discount_list_uses_docs_query_params()
    {
        $store = $this->createStore();

        Http::fake([
            '*/api/v2/discount/get_discount_list*' => Http::response([
                'response' => [
                    'discount_list' => [],
                    'more' => false,
                ],
            ], 200),
        ]);

        app(ShopeeChannel::class)->getDiscountList(
            $store,
            'ongoing',
            2,
            50,
            1785553200,
            1792033200
        );

        Http::assertSent(function ($request) {
            $query = [];
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            return $request->method() === 'GET'
                && str_contains($request->url(), '/api/v2/discount/get_discount_list')
                && ($query['discount_status'] ?? null) === 'ongoing'
                && (int) ($query['page_no'] ?? 0) === 2
                && (int) ($query['page_size'] ?? 0) === 50
                && (int) ($query['update_time_from'] ?? 0) === 1785553200
                && (int) ($query['update_time_to'] ?? 0) === 1792033200;
        });
    }

    public function test_update_discount_and_delete_discount_item_payloads_match_docs()
    {
        $store = $this->createStore();

        Http::fake([
            '*/api/v2/discount/update_discount*' => Http::response([
                'response' => ['discount_id' => 665123666665499],
            ], 200),
            '*/api/v2/discount/delete_discount_item*' => Http::response([
                'response' => ['discount_id' => 665123666665499],
            ], 200),
        ]);

        app(ShopeeChannel::class)->updateDiscount(
            $store,
            665123666665499,
            'Promo Baru',
            1785553200,
            1792033200
        );

        app(ShopeeChannel::class)->deleteDiscountItem(
            $store,
            665123666665499,
            1776783,
            1467683
        );

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/api/v2/discount/update_discount')) {
                return false;
            }

            $payload = $this->requestPayload($request);

            return (int) ($payload['discount_id'] ?? 0) === 665123666665499
                && ($payload['discount_name'] ?? null) === 'Promo Baru'
                && (int) ($payload['start_time'] ?? 0) === 1785553200
                && (int) ($payload['end_time'] ?? 0) === 1792033200;
        });

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/api/v2/discount/delete_discount_item')) {
                return false;
            }

            $payload = $this->requestPayload($request);

            return (int) ($payload['discount_id'] ?? 0) === 665123666665499
                && (int) ($payload['item_id'] ?? 0) === 1776783
                && (int) ($payload['model_id'] ?? 0) === 1467683;
        });
    }
}
