<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AmsPerformanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_ams_overview_reads_shop_and_campaign_metrics(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'AMS-OVERVIEW']);
        $store = $this->store();

        Http::fake([
            '*/api/v2/ams/get_performance_data_update_time*' => Http::response([
                'response' => ['last_report_date' => '2026-10-09'],
            ]),
            '*/api/v2/ams/get_shop_performance*' => Http::response([
                'response' => [
                    'sales' => '1500000',
                    'gross_item_sold' => 12,
                    'orders' => 8,
                    'clicks' => 320,
                    'est_commission' => '90000',
                    'roi' => '16.66',
                ],
            ]),
            '*/api/v2/ams/get_campaign_key_metrics_performance*' => Http::response([
                'response' => [
                    'open_campaign_key_metircs' => ['affiliates' => 4, 'items_sold' => 6, 'sales' => '700000'],
                    'targeted_campaign_key_metircs' => ['affiliates' => 2, 'items_sold' => 3, 'sales' => '800000'],
                ],
            ]),
        ]);

        $response = $this->actingAs($user)->get(route('marketplace.reports.ams-performance', [
            'load' => 1,
            'store_id' => $store->id,
            'report' => 'overview',
            'period_type' => 'Last30d',
            'date_from' => '2026-09-10',
            'date_to' => '2026-10-09',
            'order_type' => 'ConfirmedOrder',
            'channel' => 'AllChannel',
        ]));

        $response->assertOk()
            ->assertViewIs('marketplace.ams_performance_reports')
            ->assertSee('1.500.000')
            ->assertSee('2026-10-09')
            ->assertSee('Open Campaign');

        Http::assertSent(function ($request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_contains($request->url(), '/api/v2/ams/get_shop_performance')
                && ($query['access_token'] ?? null) === 'dummy_access_token'
                && ($query['period_type'] ?? null) === 'Last30d'
                && ($query['start_date'] ?? null) === '20260910'
                && ($query['end_date'] ?? null) === '20261009'
                && ($query['order_type'] ?? null) === 'ConfirmedOrder';
        });
    }

    public function test_conversion_report_maps_date_filters_to_shopee_timestamps(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'AMS-CONVERSION']);
        $store = $this->store('AMS-CONVERSION-STORE');

        Http::fake([
            '*/api/v2/ams/get_conversion_report*' => Http::response([
                'response' => [
                    'list' => [[
                        'order_sn' => 'ORDER-AMS-1',
                        'order_status' => 'Completed',
                        'affiliate_name' => 'Affiliate One',
                        'items' => [['item_name' => 'Produk AMS', 'seller_service_fee' => '12.50']],
                    ]],
                    'total_count' => 41,
                    'has_more' => true,
                ],
            ]),
            '*/api/v2/ams/get_performance_data_update_time*' => Http::response([
                'response' => ['last_report_date' => '2026-10-09'],
            ]),
        ]);

        $response = $this->actingAs($user)->get(route('marketplace.reports.ams-performance', [
            'load' => 1,
            'store_id' => $store->id,
            'report' => 'conversion',
            'period_type' => 'Last30d',
            'date_from' => '2026-10-01',
            'date_to' => '2026-10-09',
            'order_type' => 'ConfirmedOrder',
            'channel' => 'AllChannel',
            'page_no' => 1,
            'page_size' => 20,
        ]));

        $response->assertOk()
            ->assertSee('ORDER-AMS-1')
            ->assertSee('Affiliate One')
            ->assertSeeText('Halaman 1 dari 3')
            ->assertSee('page_no=2', false);

        Http::assertSent(function ($request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_contains($request->url(), '/api/v2/ams/get_conversion_report')
                && ($query['page_size'] ?? null) === '20'
                && ($query['place_order_time_start'] ?? null) === (string) strtotime('2026-10-01 00:00:00')
                && ($query['place_order_time_end'] ?? null) === (string) strtotime('2026-10-09 23:59:59');
        });
    }

    private function store(string $code = 'AMS-OVERVIEW-STORE'): Store
    {
        $channel = Channel::firstOrCreate(['code' => 'shopee'], ['name' => 'Shopee']);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => $code,
            'name' => 'AMS Test Store',
            'external_shop_id' => '12345',
            'status' => 'active',
            'is_active' => true,
            'credentials' => [
                'partner_id' => '2000000',
                'partner_key' => 'dummy_key',
                'shop_id' => '12345',
                'access_token' => 'dummy_access_token',
            ],
            'token_expires_at' => now()->addHours(2),
        ]);
    }
}
