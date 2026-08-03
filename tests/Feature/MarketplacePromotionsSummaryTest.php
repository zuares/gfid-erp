<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketplacePromotionsSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function createStore(bool $isActive = true, string $name = 'Toko Summary'): Store
    {
        $channel = Channel::firstOrCreate(
            ['code' => 'shopee'],
            ['name' => 'Shopee']
        );

        return Store::create([
            'channel_id' => $channel->id,
            'code' => 'S' . random_int(1000, 9999),
            'name' => $name,
            'status' => 'active',
            'is_active' => $isActive,
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

    public function test_promotions_summary_page_renders()
    {
        $this->withoutMiddleware();

        $response = $this->get(route('marketplace.promotions.summary'));

        $response->assertOk();
        $response->assertSee('Summary Promosi');
    }

    public function test_promotions_summary_api_filters_by_status_and_date()
    {
        $this->withoutMiddleware();

        $store = $this->createStore();

        $ongoingStart = Carbon::parse('2026-07-12 10:00:00')->timestamp;
        $ongoingEnd = Carbon::parse('2026-07-18 23:59:59')->timestamp;
        $upcomingStart = Carbon::parse('2026-07-19 09:00:00')->timestamp;
        $upcomingEnd = Carbon::parse('2026-07-25 23:59:59')->timestamp;
        $endedStart = Carbon::parse('2026-06-01 10:00:00')->timestamp;
        $endedEnd = Carbon::parse('2026-06-10 23:59:59')->timestamp;
        $suspendedStart = Carbon::parse('2026-07-14 08:00:00')->timestamp;
        $suspendedEnd = Carbon::parse('2026-07-16 22:00:00')->timestamp;

        Http::fake([
            '*/api/v2/discount/get_discount_list*' => function (Request $request) use (
                $ongoingStart,
                $ongoingEnd,
                $upcomingStart,
                $upcomingEnd,
                $endedStart,
                $endedEnd,
                $suspendedStart,
                $suspendedEnd
            ) {
                $query = [];
                parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);
                $status = $query['discount_status'] ?? '';

                $payload = match ($status) {
                    'ongoing' => [
                        'response' => [
                            'discount_list' => [
                                [
                                    'discount_id' => 501,
                                    'discount_name' => 'Promo Ongoing',
                                    'discount_status' => 'ongoing',
                                    'start_time' => $ongoingStart,
                                    'end_time' => $ongoingEnd,
                                    'item_count' => 2,
                                ],
                            ],
                        ],
                    ],
                    'upcoming' => [
                        'response' => [
                            'discount_list' => [
                                [
                                    'discount_id' => 502,
                                    'discount_name' => 'Promo Upcoming',
                                    'discount_status' => 'upcoming',
                                    'start_time' => $upcomingStart,
                                    'end_time' => $upcomingEnd,
                                    'item_count' => 3,
                                ],
                            ],
                        ],
                    ],
                    'ended' => [
                        'response' => [
                            'discount_list' => [
                                [
                                    'discount_id' => 503,
                                    'discount_name' => 'Promo Ended',
                                    'discount_status' => 'ended',
                                    'start_time' => $endedStart,
                                    'end_time' => $endedEnd,
                                    'item_count' => 4,
                                ],
                            ],
                        ],
                    ],
                    'suspended' => [
                        'response' => [
                            'discount_list' => [
                                [
                                    'discount_id' => 504,
                                    'discount_name' => 'Promo Suspended',
                                    'discount_status' => 'suspended',
                                    'start_time' => $suspendedStart,
                                    'end_time' => $suspendedEnd,
                                    'item_count' => 5,
                                ],
                            ],
                        ],
                    ],
                    default => ['response' => ['discount_list' => []]],
                };

                return Http::response($payload, 200);
            },
        ]);

        $response = $this->getJson('/api/marketplace/promotions/summary?store_id=' . $store->id . '&status=all&date_from=2026-07-12&date_to=2026-07-18');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('totals.stores', 1)
            ->assertJsonPath('totals.promotions', 2)
            ->assertJsonPath('totals.ongoing', 1)
            ->assertJsonPath('totals.suspended', 1)
            ->assertJsonPath('totals.items', 7)
            ->assertJsonPath('rows.0.discount_id', 504)
            ->assertJsonPath('rows.1.discount_id', 501)
            ->assertJsonPath('store_summaries.0.promotions', 2);
    }

    public function test_promotions_summary_api_ignores_inactive_stores()
    {
        $this->withoutMiddleware();

        $activeStore = $this->createStore(true, 'Toko Aktif');
        $this->createStore(false, 'Toko Nonaktif');

        Http::fake([
            '*/api/v2/discount/get_discount_list*' => Http::response([
                'response' => [
                    'discount_list' => [
                        [
                            'discount_id' => 601,
                            'discount_name' => 'Promo Aktif',
                            'discount_status' => 'ongoing',
                            'start_time' => Carbon::parse('2026-07-12 10:00:00')->timestamp,
                            'end_time' => Carbon::parse('2026-07-18 23:59:59')->timestamp,
                            'item_count' => 2,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/marketplace/promotions/summary?store_id=all&status=all&date_from=2026-07-12&date_to=2026-07-18');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('totals.stores', 1)
            ->assertJsonPath('store_summaries.0.store.id', $activeStore->id)
            ->assertJsonMissingPath('store_summaries.1');
    }
}
