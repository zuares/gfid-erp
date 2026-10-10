<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Store;
use App\Models\User;
use App\Services\Marketplace\PrincipalSalesPerformanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Services\Channels\Shopee\ShopeeChannel;
use Tests\TestCase;

class PrincipalSalesPerformanceModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_principal_sales_module(): void
    {
        $this->get(route('marketplace.reports.principal-sales-performance'))
            ->assertRedirect(route('login'));
    }

    public function test_module_forwards_principal_scope_payload_and_renders_response(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'shopee_test',
            'name' => 'Shopee Test',
            'status' => 'active',
            'is_active' => true,
            'credentials' => [
                'access_token' => 'principal-token',
                'partner_id' => '123',
                'partner_key' => 'secret',
            ],
        ]);

        $service = \Mockery::mock(PrincipalSalesPerformanceService::class);
        $service->shouldReceive('fetch')
            ->once()
            ->with(\Mockery::on(fn (Store $actual): bool => $actual->is($store)), '123456', \Mockery::on(function (array $payload): bool {
                return $payload === [
                    'start_date' => '2026-10-01',
                    'end_date' => '2026-10-05',
                    'timezone' => 'GMT+7',
                    'granularity' => 'customize',
                    'region_list' => [
                        ['region' => 'ID', 'currency' => 'USD'],
                        ['region' => 'MY', 'currency' => 'USD'],
                    ],
                ];
            }))
            ->andReturn([
                'response' => [
                    'summary' => [
                        'sales' => 1250,
                        'orders' => 10,
                        'currency' => 'USD',
                    ],
                    'details' => [
                        ['region' => 'ID', 'sales' => 1250, 'orders' => 10],
                    ],
                ],
            ]);
        $this->app->instance(PrincipalSalesPerformanceService::class, $service);

        $response = $this->actingAs(User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'EMP-PRINCIPAL-001',
        ]))
            ->get(route('marketplace.reports.principal-sales-performance', [
                'load' => 1,
                'store_id' => $store->id,
                'principal_id' => '123456',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-05',
                'timezone' => 'GMT+7',
                'granularity' => 'customize',
                'currency' => 'USD',
                'regions' => 'ID MY',
            ]));

        $response->assertOk()
            ->assertSee('Principal Sales Performance')
            ->assertSee('ID')
            ->assertSee('1.250,00');
    }

    public function test_shopee_request_uses_principal_id_instead_of_shop_id(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'shopee_api_test',
            'name' => 'Shopee API Test',
            'status' => 'active',
            'is_active' => true,
            'token_expires_at' => now()->addHour(),
            'credentials' => [
                'access_token' => 'principal-token',
                'partner_id' => '123',
                'partner_key' => 'secret',
                'shop_id' => 'shop-should-not-be-sent',
                'base_url' => 'https://partner.shopeemobile.com',
            ],
        ]);

        Http::fake(['https://partner.shopeemobile.com/*' => Http::response([
            'response' => ['summary' => [], 'details' => []],
        ])]);

        app(ShopeeChannel::class)->getPrincipalSalesPerformanceDetail($store, '123456', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-01',
            'timezone' => 'GMT+7',
            'granularity' => 'day',
        ]);

        Http::assertSent(function ($request): bool {
            $query = parse_url($request->url(), PHP_URL_QUERY) ?? '';
            parse_str($query, $params);

            return $request->method() === 'POST'
                && ($params['principal_id'] ?? null) === '123456'
                && ! array_key_exists('shop_id', $params)
                && $request->data()['granularity'] === 'day';
        });
    }
}
