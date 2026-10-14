<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceShopPerformanceSnapshot;
use App\Models\Store;
use App\Models\User;
use App\Services\Marketplace\ShopeeShopPerformanceSyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopeeShopPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_endpoint_uses_principal_signature_and_persists_snapshot(): void
    {
        $channel = Channel::create([
            'code' => 'shopee',
            'name' => 'Shopee',
            'status' => 'active',
            'is_active' => true,
        ]);
        $store = Store::create([
            'code' => 'SHP-PERFORMANCE-TEST',
            'name' => 'Shopee Performance Test',
            'channel_id' => $channel->id,
            'external_shop_id' => '456',
            'status' => 'active',
            'is_active' => true,
            'token_expires_at' => now()->addHour(),
            'credentials' => [
                'partner_id' => '123',
                'partner_key' => 'test-partner-key',
                'shop_id' => '456',
                'principal_id' => '789',
                'access_token' => 'test-access-token',
                'base_url' => 'https://partner.test-stable.shopeemobile.com',
            ],
        ]);

        Http::fake([
            'https://partner.test-stable.shopeemobile.com/*' => Http::response([
                'request_id' => 'performance-test-request',
                'error' => '',
                'message' => '',
                'response' => [
                    'details' => [[
                        'shop_id' => 456,
                        'shop_name' => 'Shopee Performance Test',
                        'currency' => 'LOCAL',
                        'sales' => 123456.78,
                        'orders' => 12,
                        'units_sold' => 15,
                        'product_clicks' => 100,
                        'product_views' => 200,
                        'unique_visitors' => 150,
                        'item_conversion_rate' => 0.075,
                        'order_conversion_rate' => 0.12,
                    ]],
                ],
            ], 200),
        ]);

        $result = app(ShopeeShopPerformanceSyncService::class)->syncPeriod(
            $store,
            Carbon::parse('2026-10-14'),
            Carbon::parse('2026-10-14'),
            'day',
            'GMT+7',
            'LOCAL'
        );

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $path = '/api/v2/principal/get_shop_sales_performance_detail';
            $expected = hash_hmac(
                'sha256',
                '123' . $path . $query['timestamp'] . 'test-access-token' . '789',
                'test-partner-key'
            );

            return str_ends_with((string) parse_url($request->url(), PHP_URL_PATH), $path)
                && $query['principal_id'] === '789'
                && $query['sign'] === $expected
                && data_get($request->data(), 'granularity') === 'day';
        });

        $this->assertSame(1, $result['saved']);
        $snapshot = MarketplaceShopPerformanceSnapshot::query()->first();
        $this->assertNotNull($snapshot);
        $this->assertSame($store->id, $snapshot->store_id);
        $this->assertSame(456, $snapshot->shop_id);
        $this->assertSame('2026-10-14', $snapshot->period_start->toDateString());
        $this->assertSame(12, $snapshot->orders);
        $this->assertSame(15, $snapshot->units_sold);
        $this->assertSame('performance-test-request', $snapshot->request_id);
    }

    public function test_owner_can_open_shopee_performance_page(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'SHP-PERFORMANCE-UI-'.uniqid(),
        ]);

        $this->actingAs($owner)
            ->get(route('marketplace.shopee-performance'))
            ->assertOk()
            ->assertSee('Shopee Performance')
            ->assertSee('Sync periode')
            ->assertSee('Sales Shopee');
    }
}
