<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\MarketplaceBooking;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
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
            ->assertSee('Filter toko')
            ->assertDontSee('Executive command center untuk omzet, pencairan, biaya, dan profit.')
            ->assertSee('Pesanan')
            ->assertSee('Masih dikirim')
            ->assertSee('Dana belum cair')
            ->assertSee('Sudah cair')
            ->assertSee('Disimpan di Gudang Shopee')
            ->assertSee('Menunggu konfirmasi')
            ->assertSee('Dibatalkan')
            ->assertSee('Return / Refund')
            ->assertSee('Fee Marketplace')
            ->assertSee('data-an-tab="fees"', false)
            ->assertSee('feeOrdersBody', false)
            ->assertSee('Adm fee tercatat')
            ->assertSee('<th>Biaya iklan</th>', false);

        $response = $this->actingAs($user)->getJson('/api/marketplace/analytics-kpis?date_from=2099-01-01&date_to=2099-01-31');

        $response->assertOk()->assertJsonStructure(['current']);

        foreach ([
            '/api/marketplace/analytics-summary',
            '/api/marketplace/analytics-stores',
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

    public function test_store_filter_returns_active_stores_and_scopes_them_by_platform(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'ANALYTICS-STORES']);
        $shopee = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $tiktok = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        Store::create([
            'channel_id' => $shopee->id,
            'code' => 'ACTIVE-SHOPEE',
            'name' => 'Active Shopee',
            'status' => 'active',
            'is_active' => true,
        ]);
        Store::create([
            'channel_id' => $tiktok->id,
            'code' => 'ACTIVE-TIKTOK',
            'name' => 'Active TikTok',
            'status' => 'active',
            'is_active' => true,
        ]);
        Store::create([
            'channel_id' => $shopee->id,
            'code' => 'INACTIVE-SHOPEE',
            'name' => 'Inactive Shopee',
            'status' => 'inactive',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->getJson('/api/marketplace/stores?active_only=1')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Active Shopee'])
            ->assertJsonFragment(['name' => 'Active TikTok'])
            ->assertJsonMissing(['name' => 'Inactive Shopee']);

        $this->actingAs($user)
            ->getJson('/api/marketplace/stores?active_only=1&platform=shopee')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Active Shopee'])
            ->assertJsonMissing(['name' => 'Active TikTok'])
            ->assertJsonMissing(['name' => 'Inactive Shopee']);

        $this->actingAs($user)
            ->getJson('/api/marketplace/stores?active_only=1&platform=tiktok')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Active TikTok'])
            ->assertJsonMissing(['name' => 'Active Shopee'])
            ->assertJsonMissing(['name' => 'Inactive Shopee']);
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

    public function test_cash_orders_cancelled_tab_keeps_operational_count_without_counting_revenue(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'ANALYTICS-CANCELLED']);
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'CANCELLED-TEST',
            'name' => 'Cancelled Test Store',
            'status' => 'active',
            'is_active' => true,
        ]);

        MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'CANCELLED-1',
            'channel_order_id' => 'CANCELLED-1',
            'order_status' => 'CANCELLED',
            'status' => 'cancelled',
            'total_amount' => 125000,
            'total_paid_customer' => 125000,
            'subtotal_items' => 125000,
            'order_date' => '2026-09-20 10:00:00',
            'ordered_at' => '2026-09-20 10:00:00',
            'raw_json' => [],
        ]);

        $response = $this->actingAs($user)->getJson('/api/marketplace/analytics-cash-orders?' . http_build_query([
            'store_id' => $store->id,
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
            'settlement' => 'cancelled',
        ]));

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('summary.cash_order_count', 1)
            ->assertJsonPath('summary.cash_gross_sales', 125000)
            ->assertJsonPath('summary.cash_order_revenue', 0);
    }

    public function test_fee_analysis_follows_platform_and_store_filters(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'ANALYTICS-FEE-FILTER']);
        $shopee = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $tiktok = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $shopeeStore = Store::create([
            'channel_id' => $shopee->id,
            'code' => 'FEE-FILTER-SHOPEE',
            'name' => 'Fee Filter Shopee',
            'status' => 'active',
            'is_active' => true,
        ]);
        $tiktokStore = Store::create([
            'channel_id' => $tiktok->id,
            'code' => 'FEE-FILTER-TIKTOK',
            'name' => 'Fee Filter TikTok',
            'status' => 'active',
            'is_active' => true,
        ]);
        $shopeeOrder = $this->order($shopeeStore, 'fee-seller-a', '2026-09-10', 'FEE-FILTER-SHOPEE-1');
        $tiktokOrder = $this->order($tiktokStore, 'fee-seller-b', '2026-09-11', 'FEE-FILTER-TIKTOK-1');
        foreach ([[$shopeeStore, $shopeeOrder, 10000], [$tiktokStore, $tiktokOrder, 20000]] as [$store, $order, $commission]) {
            MarketplaceOrderSettlement::create([
                'store_id' => $store->id,
                'order_id' => $order->id,
                'channel_order_id' => $order->channel_order_id,
                'commission_fee' => $commission,
                'settlement_time' => '2026-09-12 10:00:00',
                'data_status' => 'complete',
                'final_income' => 100000 - $commission,
                'raw_json' => [],
            ]);
        }

        $query = [
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
            'settlement' => 'settled',
        ];
        $all = $this->actingAs($user)->getJson('/api/marketplace/analytics-cash-orders?'.http_build_query($query));
        $all->assertOk()->assertJsonPath('meta.total', 2);
        $this->assertCount(2, $all->json('fee_by_store'));

        $filtered = $this->actingAs($user)->getJson('/api/marketplace/analytics-cash-orders?'.http_build_query(array_merge($query, [
            'platform' => 'shopee',
            'store_id' => $shopeeStore->id,
        ])));
        $filtered->assertOk()->assertJsonPath('meta.total', 1);
        $this->assertSame(['Fee Filter Shopee'], collect($filtered->json('fee_by_store'))->pluck('store_name')->unique()->values()->all());
        $this->assertSame(10000.0, (float) collect($filtered->json('fee_by_type'))->firstWhere('code', 'platform_commission')['amount']);
    }

    public function test_analytics_rejects_store_and_platform_mismatch(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'ANALYTICS-SCOPE']);
        $shopee = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $shopee->id,
            'code' => 'SCOPE-SHOPEE',
            'name' => 'Scope Shopee',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->getJson('/api/marketplace/analytics-kpis?' . http_build_query([
                'store_id' => $store->id,
                'platform' => 'tiktok',
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-30',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['store_id']);
    }

    public function test_tiktok_finance_refund_is_visible_without_marketplace_return_feed(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'ANALYTICS-TIKTOK-REFUND']);
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'TIKTOK-REFUND-TEST',
            'name' => 'TikTok Refund Test',
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'TT-REFUND-ANALYTICS-1',
            'channel_order_id' => 'TT-REFUND-ANALYTICS-1',
            'order_status' => 'COMPLETED',
            'status' => 'completed',
            'total_amount' => 100000,
            'total_paid_customer' => 100000,
            'subtotal_items' => 100000,
            'order_date' => '2026-10-05 10:00:00',
            'ordered_at' => '2026-10-05 10:00:00',
            'raw_json' => [],
        ]);
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => 100000,
            'drc_adjustable_refund' => 70000,
            'final_income' => 30000,
            'settlement_time' => '2026-10-07 10:00:00',
            'data_status' => 'complete',
            'raw_json' => [
                'source' => 'tiktok_finance_202501_order_statement_transactions',
                'sku_transactions' => [[
                    'revenue_breakdown' => [
                        'refund_subtotal_before_discount_amount' => '-70000',
                    ],
                ]],
            ],
        ]);

        $query = http_build_query([
            'store_id' => $store->id,
            'platform' => 'tiktok',
            'date_from' => '2026-10-01',
            'date_to' => '2026-10-31',
        ]);

        $cash = $this->actingAs($user)->getJson('/api/marketplace/analytics-cash-orders?'.$query.'&settlement=return_refund');
        $cash->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('summary.cash_refund', 70000);

        $returns = $this->actingAs($user)->getJson('/api/marketplace/analytics-return-orders?'.$query);
        $returns->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.source', 'tiktok_finance')
            ->assertJsonPath('data.0.amount', 70000);

        $this->actingAs($user)->getJson('/api/marketplace/analytics-kpis?'.$query)
            ->assertOk()
            ->assertJsonPath('current.return_refund_amount', 70000);
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
