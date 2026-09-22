<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceAnalyticsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_page_and_kpi_endpoint_are_available_from_the_split_controller(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'ANALYTICS-ENDPOINT']);

        $page = $this->actingAs($user)->get('/marketplace/analytics?date_from=2099-01-01&date_to=2099-01-31');

        $page->assertOk()->assertViewIs('marketplace.analytics');

        $response = $this->actingAs($user)->getJson('/api/marketplace/analytics-kpis?date_from=2099-01-01&date_to=2099-01-31');

        $response->assertOk()->assertJsonStructure(['current']);

        foreach ([
            '/api/marketplace/analytics-summary',
            '/api/marketplace/analytics-products',
            '/api/marketplace/analytics-cohort-options',
            '/api/marketplace/analytics-cash-orders',
            '/api/marketplace/analytics-return-orders',
        ] as $endpoint) {
            $this->actingAs($user)
                ->getJson($endpoint . '?date_from=2099-01-01&date_to=2099-01-31')
                ->assertOk();
        }
    }

    public function test_cohort_endpoint_keeps_customer_retention_across_months(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'ANALYTICS-COHORT']);
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'COHORT-TEST',
            'name' => 'Cohort Test Store',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->order($store, 'buyer-a', '2026-06-10', 'COHORT-JUN-A');
        $this->order($store, 'buyer-b', '2026-06-15', 'COHORT-JUN-B');
        $this->order($store, 'buyer-a', '2026-07-10', 'COHORT-JUL-A');

        $response = $this->actingAs($user)->getJson('/api/marketplace/analytics-cohort?' . http_build_query([
            'mode' => 'customer',
            'metric' => 'retention_pct',
            'store_id' => $store->id,
            'date_from' => '2026-06-01',
            'date_to' => '2026-07-31',
        ]));

        $response->assertOk();

        $juneCohort = collect($response->json('rows'))
            ->firstWhere('cohort_month', '2026-06');

        $this->assertNotNull($juneCohort);
        $this->assertSame(2, $juneCohort['cohort_size']);
        $this->assertSame(100.0, (float) $juneCohort['periods'][0]['retention_pct']);
        $this->assertSame(50.0, (float) $juneCohort['periods'][1]['retention_pct']);
        $this->assertSame(1, $juneCohort['periods'][1]['active_customers']);
    }

    private function order(Store $store, string $buyer, string $date, string $externalId): MarketplaceOrder
    {
        return MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => $externalId,
            'channel_order_id' => $externalId,
            'order_status' => 'COMPLETED',
            'status' => 'completed',
            'buyer_username' => $buyer,
            'total_amount' => 100000,
            'total_paid_customer' => 100000,
            'subtotal_items' => 100000,
            'order_date' => $date . ' 10:00:00',
            'ordered_at' => $date . ' 10:00:00',
            'raw_json' => [],
        ]);
    }
}
