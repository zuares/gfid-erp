<?php

namespace Tests\Unit\Services\Channels\Shopee;

use App\Models\Channel;
use App\Models\Store;
use App\Services\Channels\Shopee\ShopeeChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test aditif untuk penambahan `_meta` (http_status, retry_after) di
 * ShopeeChannel::doGet()/doPost() — Koreksi 3. Memastikan status HTTP tidak lagi
 * hilang saat Shopee mengembalikan body JSON valid untuk error (429/5xx), dan
 * bahwa perubahan ini tidak merusak field asli Shopee yang sudah ada.
 *
 * Juga mengunci (regression test) perilaku existing: getEscrowDetail() tetap
 * lewat ensureFreshToken() sebelum memanggil API (Koreksi 18, item test #12).
 */
class ShopeeChannelMetaTest extends TestCase
{
    use RefreshDatabase;

    private function createStore(array $overrides = []): Store
    {
        $channel = Channel::firstOrCreate(['code' => 'shopee'], ['name' => 'Shopee']);

        return Store::create(array_merge([
            'channel_id' => $channel->id,
            'code'       => 'S' . rand(1000, 9999),
            'name'       => 'Toko Uji',
            'status'     => 'active',
            'is_active'  => true,
            'external_shop_id' => '12345',
            'credentials' => [
                'partner_id'   => '2000000',
                'partner_key'  => 'dummy_key',
                'shop_id'      => '12345',
                'access_token' => 'dummy_access_token',
                'refresh_token' => 'dummy_refresh_token',
            ],
            'token_expires_at' => now()->addHours(2),
        ], $overrides));
    }

    public function test_meta_tetap_ada_untuk_response_200_json_valid()
    {
        $store = $this->createStore();

        Http::fake([
            '*/api/v2/payment/get_escrow_detail*' => Http::response([
                'response' => ['order_income' => ['final_income' => 1000]],
            ], 200),
        ]);

        $result = app(ShopeeChannel::class)->getEscrowDetail($store, 'ORDER-X');

        $this->assertSame(200, $result['_meta']['http_status']);
        $this->assertSame(1000, $result['response']['order_income']['final_income']);
    }

    public function test_http_status_tidak_hilang_saat_error_429_berbentuk_json_valid()
    {
        $store = $this->createStore();

        Http::fake([
            '*/api/v2/payment/get_escrow_detail*' => Http::response([
                'error'   => 'error_too_many_request',
                'message' => 'Rate limit exceeded',
            ], 429, ['Retry-After' => '5']),
        ]);

        $result = app(ShopeeChannel::class)->getEscrowDetail($store, 'ORDER-X');

        // Sebelum perubahan ini, status HTTP asli (429) HILANG karena json() valid
        // sehingga cabang fallback ('status' => ...) tidak pernah dieksekusi.
        $this->assertSame(429, $result['_meta']['http_status']);
        $this->assertSame('5', $result['_meta']['retry_after']);
        // Field asli Shopee tetap utuh, tidak diubah oleh penambahan _meta.
        $this->assertSame('error_too_many_request', $result['error']);
        $this->assertSame('Rate limit exceeded', $result['message']);
    }

    public function test_http_status_tetap_tercatat_untuk_response_5xx()
    {
        $store = $this->createStore();

        Http::fake([
            '*/api/v2/payment/get_escrow_detail*' => Http::response([
                'error' => 'internal_server_error',
                'message' => 'Something went wrong',
            ], 500),
        ]);

        $result = app(ShopeeChannel::class)->getEscrowDetail($store, 'ORDER-X');

        $this->assertSame(500, $result['_meta']['http_status']);
    }

    public function test_gms_campaign_performance_uses_valid_payload()
    {
        $store = $this->createStore();

        Http::fake([
            '*/api/v2/ads/get_gms_campaign_performance*' => Http::response([
                'response' => ['report' => []],
            ], 200),
        ]);

        app(ShopeeChannel::class)->getGmsCampaignPerformance(
            $store,
            null,
            '30-07-2026',
            '30-07-2026'
        );

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->method() === 'POST'
                && str_contains($request->url(), '/api/v2/ads/get_gms_campaign_performance')
                && ($body['start_date'] ?? null) === '30-07-2026'
                && ($body['end_date'] ?? null) === '30-07-2026'
                && ! array_key_exists('campaign_id_list', $body)
                && ! array_key_exists('campaign_id', $body);
        });
    }

    public function test_meta_tetap_ada_untuk_body_non_json()
    {
        $store = $this->createStore();

        Http::fake([
            '*/api/v2/payment/get_escrow_detail*' => Http::response('<html>Gateway Timeout</html>', 504),
        ]);

        $result = app(ShopeeChannel::class)->getEscrowDetail($store, 'ORDER-X');

        $this->assertSame(504, $result['_meta']['http_status']);
        $this->assertSame('invalid_response', $result['error']);
    }

    public function test_getEscrowDetail_tetap_lewat_ensure_fresh_token_saat_token_hampir_kedaluwarsa()
    {
        // Token sisa < 2 menit -> ensureFreshToken() harus proaktif refresh dulu
        // SEBELUM memanggil get_escrow_detail (perilaku existing, dikunci di sini).
        $store = $this->createStore(['token_expires_at' => now()->addSeconds(30)]);

        Http::fake([
            '*/api/v2/auth/access_token/get*' => Http::response([
                'access_token'  => 'token_baru',
                'refresh_token' => 'refresh_baru',
                'expire_in'     => 14400,
            ], 200),
            '*/api/v2/payment/get_escrow_detail*' => Http::response([
                'response' => ['order_income' => ['final_income' => 1000]],
            ], 200),
        ]);

        app(ShopeeChannel::class)->getEscrowDetail($store, 'ORDER-X');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v2/auth/access_token/get');
        });
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v2/payment/get_escrow_detail');
        });
    }

    public function test_getEscrowList_mengirim_filter_rentang_dan_pagination()
    {
        $store = $this->createStore();

        Http::fake([
            '*/api/v2/payment/get_escrow_list*' => Http::response([
                'response' => [
                    'escrow_list' => [[
                        'order_sn' => 'ORDER-RELEASED',
                        'escrow_release_time' => 1754395200,
                    ]],
                    'more' => false,
                ],
            ], 200),
        ]);

        $result = app(ShopeeChannel::class)->getEscrowList(
            $store,
            1754006400,
            1754438400,
            2,
            250,
        );

        $this->assertSame('ORDER-RELEASED', $result['response']['escrow_list'][0]['order_sn']);
        Http::assertSent(function ($request) {
            $url = $request->url();

            return str_contains($url, '/api/v2/payment/get_escrow_list')
                && str_contains($url, 'release_time_from=1754006400')
                && str_contains($url, 'release_time_to=1754438400')
                && str_contains($url, 'page_no=2')
                && str_contains($url, 'page_size=100');
        });
    }

    public function test_getIncomeDetail_memakai_get_dan_parameter_resmi()
    {
        $store = $this->createStore();

        Http::fake([
            '*/api/v2/payment/get_income_detail*' => Http::response([
                'income_detail_list' => [
                    'list' => [],
                    'next_page' => ['cursor' => '', 'page_size' => 30],
                ],
            ], 200),
        ]);

        app(ShopeeChannel::class)->getIncomeDetail(
            $store,
            '2026-08-25',
            '2026-08-26',
            2,
            30,
            'CURSOR-X',
        );

        Http::assertSent(function ($request) {
            $url = $request->url();

            return $request->method() === 'GET'
                && str_contains($url, '/api/v2/payment/get_income_detail')
                && str_contains($url, 'date_from=2026-08-25')
                && str_contains($url, 'date_to=2026-08-26')
                && str_contains($url, 'income_status=2')
                && str_contains($url, 'page_size=30')
                && str_contains($url, 'cursor=CURSOR-X');
        });
    }
}
