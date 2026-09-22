<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\MarketplaceBooking;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
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

        $page->assertOk()->assertViewIs('marketplace.analytics')
            ->assertSee('Pesanan')
            ->assertSee('Masih dikirim')
            ->assertSee('Dana belum cair')
            ->assertSee('Sudah cair')
            ->assertSee('Disimpan di Gudang Shopee')
            ->assertSee('Menunggu konfirmasi')
            ->assertSee('Dibatalkan')
            ->assertSee('Return / Refund');

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

    public function test_product_cohort_can_group_products_by_master_item_category(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'ANALYTICS-PRODUCT-GROUP']);
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'PRODUCT-GROUP-TEST',
            'name' => 'Product Group Test Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $category = ItemCategory::create(['code' => 'JACKET', 'name' => 'Jaket', 'active' => true]);
        $firstItem = Item::create(['code' => 'JACKET-1', 'name' => 'Jaket Navy', 'unit' => 'pcs', 'type' => 'finished', 'item_category_id' => $category->id, 'active' => true]);
        $secondItem = Item::create(['code' => 'JACKET-2', 'name' => 'Jaket Black', 'unit' => 'pcs', 'type' => 'finished', 'item_category_id' => $category->id, 'active' => true]);

        $firstOrder = $this->order($store, 'buyer-a', '2026-06-10', 'PRODUCT-GROUP-A');
        $secondOrder = $this->order($store, 'buyer-b', '2026-06-15', 'PRODUCT-GROUP-B');
        MarketplaceOrderItem::create(['order_id' => $firstOrder->id, 'internal_item_id' => $firstItem->id, 'item_name_snapshot' => $firstItem->name, 'item_code_snapshot' => $firstItem->code, 'qty' => 1, 'line_net_amount' => 100000]);
        MarketplaceOrderItem::create(['order_id' => $secondOrder->id, 'internal_item_id' => $secondItem->id, 'item_name_snapshot' => $secondItem->name, 'item_code_snapshot' => $secondItem->code, 'qty' => 1, 'line_net_amount' => 100000]);

        $response = $this->actingAs($user)->getJson('/api/marketplace/analytics-cohort?' . http_build_query([
            'mode' => 'product',
            'group_by' => 'category',
            'metric' => 'revenue',
            'store_id' => $store->id,
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
        ]));

        $response->assertOk()->assertJsonPath('group_by', 'category');
        $rows = collect($response->json('rows'));
        $this->assertCount(1, $rows);
        $this->assertSame('Jaket', $rows->first()['category']);
        $this->assertSame(2, $rows->first()['product_count']);
        $this->assertSame(200000.0, (float) $rows->first()['periods'][0]['revenue']);
    }

    public function test_cash_orders_exposes_payment_method_and_waiting_confirmation_tab(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'ANALYTICS-CASH']);
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'CASH-TEST',
            'name' => 'Cash Test Store',
            'status' => 'active',
            'is_active' => true,
        ]);

        MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'CASH-CONFIRM-1',
            'channel_order_id' => 'CASH-CONFIRM-1',
            'order_status' => 'TO_CONFIRM_RECEIVE',
            'status' => 'to_confirm_receive',
            'payment_method' => 'COD',
            'total_amount' => 150000,
            'total_paid_customer' => 150000,
            'subtotal_items' => 150000,
            'order_date' => '2026-09-20 10:00:00',
            'ordered_at' => '2026-09-20 10:00:00',
            'raw_json' => [],
        ]);

        $response = $this->actingAs($user)->getJson('/api/marketplace/analytics-cash-orders?' . http_build_query([
            'store_id' => $store->id,
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
            'settlement' => 'confirm',
        ]));

        $response->assertOk()
            ->assertJsonPath('data.0.payment_method', 'COD')
            ->assertJsonPath('data.0.status_group', 'confirm')
            ->assertJsonPath('data.0.status_group_label', 'Menunggu konfirmasi');
    }

    public function test_cash_orders_shipped_tab_includes_ready_to_ship_and_processed_orders(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'ANALYTICS-SHIPPED']);
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'SHIPPED-TEST',
            'name' => 'Shipped Test Store',
            'status' => 'active',
            'is_active' => true,
        ]);

        foreach (['READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'READY_TO_HANDOVER'] as $index => $status) {
            MarketplaceOrder::create([
                'store_id' => $store->id,
                'external_order_id' => "SHIPPED-{$index}",
                'channel_order_id' => "SHIPPED-{$index}",
                'order_status' => $status,
                'status' => strtolower($status),
                'total_amount' => 100000,
                'total_paid_customer' => 100000,
                'subtotal_items' => 100000,
                'order_date' => '2026-09-20 10:00:00',
                'ordered_at' => '2026-09-20 10:00:00',
                'raw_json' => [],
            ]);
        }

        $response = $this->actingAs($user)->getJson('/api/marketplace/analytics-cash-orders?' . http_build_query([
            'store_id' => $store->id,
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
            'settlement' => 'shipped',
        ]));

        $response->assertOk()->assertJsonPath('meta.total', 4);
        $this->assertSame(['shipped', 'shipped', 'shipped', 'shipped'], collect($response->json('data'))->pluck('status_group')->all());
        $this->assertSame(['Masih dikirim', 'Masih dikirim', 'Masih dikirim', 'Masih dikirim'], collect($response->json('data'))->pluck('status_group_label')->all());

        $this->actingAs($user)->getJson('/api/marketplace/analytics-cash-orders?' . http_build_query([
            'store_id' => $store->id,
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
            'settlement' => 'unsettled',
        ]))->assertOk()->assertJsonPath('meta.total', 4);
    }

    public function test_cash_orders_exposes_shopee_warehouse_tab_for_kilat_booking(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'ANALYTICS-WAREHOUSE']);
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'WAREHOUSE-TEST',
            'name' => 'Warehouse Test Store',
            'status' => 'active',
            'is_active' => true,
        ]);

        MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => '260915AASADZEU6ZP7ZP7',
            'channel_order_id' => '260915AASADZEU6ZP7ZP7',
            'booking_sn' => '260915AASADZEU6ZP7ZP7',
            'order_status' => 'SHIPPED',
            'status' => 'shipped',
            'payment_method' => 'COD',
            'total_amount' => 175000,
            'total_paid_customer' => 175000,
            'subtotal_items' => 175000,
            'order_date' => '2026-09-20 10:00:00',
            'ordered_at' => '2026-09-20 10:00:00',
            'raw_json' => [],
        ]);

        MarketplaceBooking::create([
            'store_id' => $store->id,
            'booking_sn' => '260915AASADZEU6ZP7ZP7',
            'order_sn' => '260915AASADZEU6ZP7ZP7',
            'booking_status' => 'SHIPPED',
            'tracking_number' => 'SPXID067342336109',
        ]);

        $response = $this->actingAs($user)->getJson('/api/marketplace/analytics-cash-orders?' . http_build_query([
            'store_id' => $store->id,
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
            'settlement' => 'warehouse',
        ]));

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.channel_order_id', '260915AASADZEU6ZP7ZP7')
            ->assertJsonPath('data.0.status_group', 'warehouse')
            ->assertJsonPath('data.0.status_group_label', 'Disimpan di Gudang Shopee');
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
