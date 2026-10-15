<?php

namespace Tests\Unit\Services\Channels\Shopee;

use App\Models\Channel;
use App\Models\Store;
use App\Services\Channels\Shopee\ShopeeChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopeeVoucherApiTest extends TestCase
{
    use RefreshDatabase;

    private function store(): Store
    {
        $channel = Channel::firstOrCreate(['code' => 'shopee'], ['name' => 'Shopee']);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => 'V' . random_int(1000, 9999),
            'name' => 'Toko Voucher',
            'status' => 'active',
            'is_active' => true,
            'external_shop_id' => '12345',
            'credentials' => [
                'partner_id' => '2000000', 'partner_key' => 'dummy_key', 'shop_id' => '12345',
                'access_token' => 'dummy_access_token', 'refresh_token' => 'dummy_refresh_token',
            ],
            'token_expires_at' => now()->addHour(),
        ]);
    }

    public function test_voucher_list_uses_official_query_contract(): void
    {
        $store = $this->store();
        Http::fake(['*/api/v2/voucher/get_voucher_list*' => Http::response(['response' => ['voucher_list' => [], 'more' => false]])]);

        app(ShopeeChannel::class)->getVoucherList($store, 'ongoing', 2, 50);

        Http::assertSent(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            return $request->method() === 'GET'
                && str_contains($request->url(), '/api/v2/voucher/get_voucher_list')
                && ($query['status'] ?? null) === 'ongoing'
                && (int) ($query['page_no'] ?? 0) === 2
                && (int) ($query['page_size'] ?? 0) === 50;
        });
    }

    public function test_voucher_create_and_update_forward_payloads(): void
    {
        $store = $this->store();
        Http::fake([
            '*/api/v2/voucher/add_voucher*' => Http::response(['response' => ['voucher_id' => 123]]),
            '*/api/v2/voucher/update_voucher*' => Http::response(['response' => ['voucher_id' => 123]]),
        ]);

        app(ShopeeChannel::class)->addVoucher($store, [
            'voucher_name' => 'Hemat', 'voucher_code' => 'HEMAT10', 'start_time' => 1785553200,
            'end_time' => 1788156000, 'voucher_type' => 1, 'reward_type' => 2,
            'usage_quantity' => 100, 'min_basket_price' => 100000, 'percentage' => 10,
            'max_price' => 20000, 'display_channel_list' => [1],
        ]);
        app(ShopeeChannel::class)->updateVoucher($store, 123, ['voucher_name' => 'Hemat Update', 'usage_quantity' => 200]);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/api/v2/voucher/add_voucher')) return false;
            $payload = $request->data();
            return ($payload['voucher_code'] ?? null) === 'HEMAT10'
                && (int) ($payload['voucher_type'] ?? 0) === 1
                && (int) ($payload['reward_type'] ?? 0) === 2
                && (int) ($payload['percentage'] ?? 0) === 10
                && (int) ($payload['max_price'] ?? 0) === 20000;
        });
        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/api/v2/voucher/update_voucher')) return false;
            $payload = $request->data();
            return (int) ($payload['voucher_id'] ?? 0) === 123
                && ($payload['voucher_name'] ?? null) === 'Hemat Update'
                && (int) ($payload['usage_quantity'] ?? 0) === 200;
        });
    }
}
