<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class TikTokShopOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_authorization_uses_service_id_and_new_seller_endpoint(): void
    {
        config()->set('tiktok_shop.service_id', 'service-test-123');

        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'TTK-OAUTH-'.uniqid(),
        ]);

        $response = $this->actingAs($user)->get(route('marketplace.tiktok.connect'));

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringStartsWith('https://services.tiktokshop.com/open/authorize?', $location);
        $this->assertStringContainsString('service_id=service-test-123', $location);
        $this->assertStringNotContainsString('app_key=', $location);
    }

    public function test_callback_exchanges_code_gets_authorized_shop_and_persists_it(): void
    {
        config()->set('tiktok_shop.app_key', 'app-key-test');
        config()->set('tiktok_shop.app_secret', 'app-secret-test');
        config()->set('tiktok_shop.service_id', 'service-test-123');
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        Queue::fake();
        Http::fake([
            'https://auth.tiktok-shops.com/api/v2/token/get*' => Http::response([
                'code' => 0,
                'data' => [
                    'access_token' => 'access-token-test',
                    'refresh_token' => 'refresh-token-test',
                    'access_token_expire_in' => 7200,
                    'refresh_token_expire_in' => 86400,
                    'user_type' => 0,
                    'granted_scopes' => ['seller.authorization.info'],
                ],
            ]),
            'https://open-api.tiktokglobalshop.com/seller/202309/shops*' => Http::response([
                'code' => 0,
                'data' => [
                    'shops' => [[
                        'shop_id' => 'shop-id-test',
                        'cipher' => 'shop-cipher-test',
                        'shop_name' => 'Test Shop',
                        'region' => 'ID',
                    ]],
                ],
            ]),
        ]);

        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'TTK-OAUTH-CB-'.uniqid(),
        ]);

        $start = $this->actingAs($user)->get(route('marketplace.tiktok.connect'));
        $location = $start->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $response = $this->actingAs($user)->get(route('marketplace.tiktok.callback', [
            'code' => 'authorization-code-test',
            'state' => $query['state'],
        ]));

        $response->assertRedirectContains('/marketplace/toko?connecting=1&connection_id=');

        $store = Store::query()->where('external_shop_id', 'shop-id-test')->firstOrFail();
        $this->assertSame('shop-cipher-test', $store->credential('shop_cipher'));
        $this->assertSame('access-token-test', $store->credential('access_token'));
        $this->assertSame('refresh-token-test', $store->credential('refresh_token'));
        $this->assertSame(['seller.authorization.info'], $store->meta['granted_scopes']);

        Http::assertSent(function ($request): bool {
            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://auth.tiktok-shops.com/api/v2/token/get?')
                && $request['grant_type'] === 'authorized_code';
        });

        Http::assertSent(function ($request): bool {
            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://open-api.tiktokglobalshop.com/seller/202309/shops?')
                && ! str_contains($request->url(), 'version=202309');
        });
    }

    public function test_callback_can_use_one_time_server_state_when_browser_session_cookie_changes(): void
    {
        config()->set('tiktok_shop.app_key', 'app-key-test');
        config()->set('tiktok_shop.app_secret', 'app-secret-test');
        config()->set('tiktok_shop.service_id', 'service-test-123');
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        Queue::fake();
        Http::fake([
            'https://auth.tiktok-shops.com/api/v2/token/get*' => Http::response([
                'code' => 0,
                'data' => [
                    'access_token' => 'access-token-test',
                    'refresh_token' => 'refresh-token-test',
                    'access_token_expire_in' => 7200,
                    'refresh_token_expire_in' => 86400,
                    'user_type' => 0,
                    'granted_scopes' => ['seller.authorization.info'],
                ],
            ]),
            'https://open-api.tiktokglobalshop.com/seller/202309/shops*' => Http::response([
                'code' => 0,
                'data' => ['shops' => [[
                    'shop_id' => 'shop-id-cache-state-test',
                    'cipher' => 'shop-cipher-cache-state-test',
                    'shop_name' => 'Cache State Test Shop',
                    'region' => 'ID',
                ]]],
            ]),
        ]);

        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'TTK-OAUTH-CACHE-'.uniqid(),
        ]);

        $start = $this->actingAs($user)->get(route('marketplace.tiktok.connect'));
        parse_str((string) parse_url($start->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertNotNull(Cache::get('tiktok_oauth_state:' . $query['state']));

        // Simulate the browser returning with a different session cookie.
        $this->withSession(['tiktok_oauth_state' => Str::random(64)]);

        $response = $this->actingAs($user)->get(route('marketplace.tiktok.callback', [
            'code' => 'authorization-code-cache-test',
            'state' => $query['state'],
        ]));
        $response->assertRedirectContains('/marketplace/toko?connecting=1&connection_id=');
        $this->assertDatabaseHas('stores', [
            'external_shop_id' => 'shop-id-cache-state-test',
        ]);
    }

    public function test_callback_accepts_seller_sandbox_response_without_state_when_session_is_intact(): void
    {
        config()->set('tiktok_shop.app_key', 'app-key-test');
        config()->set('tiktok_shop.app_secret', 'app-secret-test');
        config()->set('tiktok_shop.service_id', 'service-test-123');
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        Queue::fake();
        Http::fake([
            'https://auth.tiktok-shops.com/api/v2/token/get*' => Http::response([
                'code' => 0,
                'data' => [
                    'access_token' => 'access-token-no-state-test',
                    'refresh_token' => 'refresh-token-no-state-test',
                    'access_token_expire_in' => 7200,
                    'refresh_token_expire_in' => 86400,
                    'user_type' => 0,
                ],
            ]),
            'https://open-api.tiktokglobalshop.com/seller/202309/shops*' => Http::response([
                'code' => 0,
                'data' => ['shops' => [[
                    'shop_id' => 'shop-id-no-state-test',
                    'cipher' => 'shop-cipher-no-state-test',
                    'shop_name' => 'No State Test Shop',
                    'region' => 'ID',
                ]]],
            ]),
        ]);

        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'TTK-OAUTH-NO-STATE-'.uniqid(),
        ]);

        $start = $this->actingAs($user)->get(route('marketplace.tiktok.connect'));
        parse_str((string) parse_url($start->headers->get('Location'), PHP_URL_QUERY), $query);

        $response = $this->actingAs($user)->get(route('marketplace.tiktok.callback', [
            'code' => 'authorization-code-no-state-test',
        ]));

        $response->assertRedirectContains('/marketplace/toko?connecting=1&connection_id=');
        $this->assertDatabaseHas('stores', [
            'external_shop_id' => 'shop-id-no-state-test',
        ]);
    }

    public function test_active_shop_is_completed_with_authorized_shop_cipher(): void
    {
        config()->set('tiktok_shop.app_key', 'app-key-test');
        config()->set('tiktok_shop.app_secret', 'app-secret-test');
        config()->set('tiktok_shop.service_id', 'service-test-123');
        config()->set('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com');

        Queue::fake();
        Http::fake([
            'https://auth.tiktok-shops.com/api/v2/token/get*' => Http::response([
                'code' => 0,
                'data' => [
                    'access_token' => 'access-token-test',
                    'refresh_token' => 'refresh-token-test',
                    'user_type' => 0,
                ],
            ]),
            'https://open-api.tiktokglobalshop.com/seller/202309/shops*' => Http::response([
                'code' => 105005,
                'message' => 'Access denied. This app has not been granted any access scope required by this endpoint.',
            ], 401),
            'https://open-api.tiktokglobalshop.com/authorization/202309/shops*' => Http::response([
                'code' => 0,
                'data' => ['shops' => [[
                    'shop_id' => 'shop-id-active-test',
                    'cipher' => 'shop-cipher-authorized-test',
                    'shop_name' => 'Authorized Test Shop',
                    'region' => 'ID',
                ]]],
            ]),
        ]);

        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'TTK-OAUTH-MERGE-'.uniqid(),
        ]);

        $start = $this->actingAs($user)->get(route('marketplace.tiktok.connect'));
        parse_str((string) parse_url($start->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->actingAs($user)->get(route('marketplace.tiktok.callback', [
            'code' => 'authorization-code-merge-test',
            'state' => $query['state'],
        ]))->assertRedirectContains('/marketplace/toko?connecting=1&connection_id=');

        $store = Store::query()->where('external_shop_id', 'shop-id-active-test')->firstOrFail();
        $this->assertSame('shop-cipher-authorized-test', $store->credential('shop_cipher'));
        $this->assertSame('Authorized Test Shop', $store->name);
    }

    public function test_callback_does_not_create_store_when_authorized_shop_lookup_is_empty(): void
    {
        config()->set('tiktok_shop.app_key', 'app-key-test');
        config()->set('tiktok_shop.app_secret', 'app-secret-test');
        config()->set('tiktok_shop.service_id', 'service-test-123');

        Queue::fake();
        Http::fake([
            'https://auth.tiktok-shops.com/api/v2/token/get*' => Http::response([
                'code' => 0,
                'data' => [
                    'access_token' => 'access-token-test',
                    'refresh_token' => 'refresh-token-test',
                    'user_type' => 0,
                ],
            ]),
            'https://open-api.tiktokglobalshop.com/seller/202309/shops*' => Http::response([
                'code' => 0,
                'data' => ['shops' => []],
            ]),
        ]);

        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'TTK-OAUTH-EMPTY-'.uniqid(),
        ]);

        $start = $this->actingAs($user)->get(route('marketplace.tiktok.connect'));
        parse_str((string) parse_url($start->headers->get('Location'), PHP_URL_QUERY), $query);

        $response = $this->actingAs($user)->get(route('marketplace.tiktok.callback', [
            'code' => 'authorization-code-test',
            'state' => $query['state'],
        ]));

        $response->assertRedirectContains('/marketplace/toko?connecting=1&connection_id=');
        $this->assertDatabaseMissing('stores', ['external_shop_id' => 'shop-id-test']);
    }

    public function test_missing_tiktok_scope_is_exposed_as_connection_status(): void
    {
        config()->set('tiktok_shop.app_key', 'app-key-test');
        config()->set('tiktok_shop.app_secret', 'app-secret-test');
        config()->set('tiktok_shop.service_id', 'service-test-123');

        Queue::fake();
        Http::fake([
            'https://auth.tiktok-shops.com/api/v2/token/get*' => Http::response([
                'code' => 0,
                'data' => [
                    'access_token' => 'access-token-scope-test',
                    'refresh_token' => 'refresh-token-scope-test',
                    'user_type' => 0,
                ],
            ]),
            'https://open-api.tiktokglobalshop.com/seller/202309/shops*' => Http::response([
                'code' => 105005,
                'message' => 'Access denied. This app has not been granted any access scope required by this endpoint.',
                'request_id' => 'request-id-scope-test',
            ], 401),
        ]);

        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'TTK-OAUTH-SCOPE-'.uniqid(),
        ]);

        $start = $this->actingAs($user)->get(route('marketplace.tiktok.connect'));
        parse_str((string) parse_url($start->headers->get('Location'), PHP_URL_QUERY), $query);

        $response = $this->actingAs($user)->get(route('marketplace.tiktok.callback', [
            'code' => 'authorization-code-scope-test',
            'state' => $query['state'],
        ]));

        $location = $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $redirectQuery);
        $connectionId = $redirectQuery['connection_id'] ?? null;

        $this->assertNotEmpty($connectionId);
        $status = $this->actingAs($user)->getJson('/api/marketplace/tiktok/connection-status/' . $connectionId);
        $status->assertOk()
            ->assertJsonPath('status', 'failed')
            ->assertJsonPath('code', 105005);
        $status->assertJsonFragment(['message' => 'Scope TikTok Shop belum aktif. Aktifkan seller.shop.info, seller.authorization.info, dan seller.finance.info di Partner Center, lalu revoke dan hubungkan ulang toko.']);
    }
}
