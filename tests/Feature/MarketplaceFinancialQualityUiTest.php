<?php

namespace Tests\Feature;

use App\Jobs\MarketplaceRefreshDataQualityJob;
use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceFinancialDataQualityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MarketplaceFinancialQualityUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_financial_quality_ui(): void
    {
        $owner = $this->owner();

        $response = $this->actingAs($owner)
            ->get(route('marketplace.reports.financial-quality'));

        $response->assertOk()
            ->assertSee('Pemeriksaan Data Keuangan Marketplace')
            ->assertSee('Jalankan audit')
            ->assertSee('Kesiapan posting akuntansi')
            ->assertSee('Siap posting')
            ->assertDontSee('Cek tanpa menyimpan')
            ->assertSee(route('marketplace.reports.financial-quality'), false);
    }

    public function test_non_owner_cannot_open_financial_quality_ui(): void
    {
        $admin = \App\Models\User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-FIN-QUALITY',
        ]);

        $this->actingAs($admin)
            ->get(route('marketplace.reports.financial-quality'))
            ->assertForbidden();
    }

    public function test_owner_ui_lists_orders_by_quality_status_and_store(): void
    {
        $store = $this->store();
        MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'UI-LIST-001',
            'channel_order_id' => 'UI-LIST-001',
            'order_date' => now(),
            'order_status' => 'COMPLETED',
            'financial_data_status' => 'incomplete',
            'financial_issue_reason' => 'settlement_missing',
            'financial_checked_at' => now(),
        ]);

        $this->actingAs($this->owner())
            ->get(route('marketplace.reports.financial-quality', [
                'store_id' => $store->id,
                'status' => 'incomplete',
            ]))
            ->assertOk()
            ->assertSee('UI-LIST-001')
            ->assertSee('Payout belum tersedia');
    }

    public function test_default_queue_lists_settlement_issues_for_completed_order(): void
    {
        $store = $this->store();
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'UI-QUEUE-SETTLEMENT-001',
            'channel_order_id' => 'UI-QUEUE-SETTLEMENT-001',
            'order_date' => now(),
            'order_status' => 'COMPLETED',
            'financial_data_status' => 'not_applicable',
        ]);

        \App\Models\MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'data_status' => 'incomplete',
            'raw_json' => [],
        ]);

        $this->actingAs($this->owner())
            ->get(route('marketplace.reports.financial-quality'))
            ->assertOk()
            ->assertSee('UI-QUEUE-SETTLEMENT-001');
    }

    public function test_default_queue_excludes_non_completed_orders_from_active_issues(): void
    {
        $store = $this->store();
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'UI-PENDING-001',
            'channel_order_id' => 'UI-PENDING-001',
            'order_date' => now(),
            'order_status' => 'SHIPPED',
            'financial_data_status' => 'unknown',
        ]);

        \App\Models\MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'data_status' => 'incomplete',
            'raw_json' => [],
        ]);

        $this->actingAs($this->owner())
            ->get(route('marketplace.reports.financial-quality'))
            ->assertOk()
            ->assertDontSee('UI-PENDING-001');
    }

    public function test_default_queue_excludes_cancelled_orders_from_active_issues(): void
    {
        $store = $this->store();
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'UI-CANCELLED-001',
            'channel_order_id' => 'UI-CANCELLED-001',
            'order_date' => now(),
            'order_status' => 'CANCELLED',
            'financial_data_status' => 'incomplete',
            'financial_issue_reason' => 'settlement_data_incomplete',
        ]);

        \App\Models\MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'data_status' => 'incomplete',
            'raw_json' => [],
        ]);

        $this->actingAs($this->owner())
            ->get(route('marketplace.reports.financial-quality'))
            ->assertOk()
            ->assertDontSee('UI-CANCELLED-001');
    }

    public function test_operational_filters_search_status_settlement_and_date(): void
    {
        $store = $this->store();
        MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'FILTER-OPS-001',
            'channel_order_id' => 'FILTER-OPS-001',
            'order_date' => '2026-08-04 10:00:00',
            'ordered_at' => '2026-08-04 10:00:00',
            'order_status' => 'COMPLETED',
            'financial_data_status' => 'incomplete',
            'financial_issue_reason' => 'settlement_missing',
        ]);
        MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'FILTER-OPS-002',
            'channel_order_id' => 'FILTER-OPS-002',
            'order_date' => '2026-07-01 10:00:00',
            'ordered_at' => '2026-07-01 10:00:00',
            'order_status' => 'SHIPPED',
            'financial_data_status' => 'incomplete',
            'financial_issue_reason' => 'settlement_missing',
        ]);

        $this->actingAs($this->owner())
            ->get(route('marketplace.reports.financial-quality', [
                'store_id' => $store->id,
                'status' => 'incomplete',
                'q' => 'FILTER-OPS-001',
                'order_status' => 'COMPLETED',
                'settlement_status' => 'missing',
                'date_from' => '2026-08-01',
                'date_to' => '2026-08-31',
            ]))
            ->assertOk()
            ->assertSee('FILTER-OPS-001')
            ->assertDontSee('FILTER-OPS-002');
    }

    public function test_owner_can_run_dry_run_from_ui_without_writing_status(): void
    {
        $store = $this->store();
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'UI-DRY-RUN-001',
            'channel_order_id' => 'UI-DRY-RUN-001',
            'order_date' => now(),
            'order_status' => 'COMPLETED',
        ]);

        $this->actingAs($this->owner())
            ->post(route('marketplace.reports.financial-quality.refresh'), [
                'store_id' => (string) $store->id,
                'dry_run' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('quality_result');

        $this->assertSame('unknown', $order->fresh()->financial_data_status);
    }

    public function test_refresh_is_dispatched_to_marketplace_quality_queue(): void
    {
        Queue::fake();
        $store = $this->store();

        $this->actingAs($this->owner())
            ->post(route('marketplace.reports.financial-quality.refresh'), [
                'store_id' => (string) $store->id,
                'dry_run' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('quality_result.queued', true);

        Queue::assertPushedOn('marketplace-quality', MarketplaceRefreshDataQualityJob::class, function ($job) use ($store) {
            return $job->storeId === (string) $store->id && $job->dryRun === true;
        });
    }

    public function test_owner_can_save_quality_status_from_ui(): void
    {
        $store = $this->store();
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'UI-SAVE-001',
            'channel_order_id' => 'UI-SAVE-001',
            'order_date' => now(),
            'order_status' => 'COMPLETED',
        ]);

        $this->actingAs($this->owner())
            ->post(route('marketplace.reports.financial-quality.refresh'), [
                'store_id' => (string) $store->id,
                'dry_run' => '0',
            ])
            ->assertRedirect();

        $this->assertSame('incomplete', $order->fresh()->financial_data_status);
        $this->assertSame('settlement_missing', $order->fresh()->financial_issue_reason);
    }

    public function test_refresh_flashes_success_notification(): void
    {
        $store = $this->store();

        $this->actingAs($this->owner())
            ->post(route('marketplace.reports.financial-quality.refresh'), [
                'store_id' => (string) $store->id,
                'dry_run' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas('quality_result');
    }

    public function test_status_endpoint_returns_audit_status(): void
    {
        $store = $this->store();

        \Illuminate\Support\Facades\Cache::put(
            MarketplaceRefreshDataQualityJob::cacheKey((string) $store->id),
            [
                'status' => 'processing',
                'store_id' => (string) $store->id,
                'message' => 'Sedang memeriksa data: 10/20 order selesai...',
                'processed_orders' => 10,
                'total_orders' => 20,
            ]
        );

        $this->actingAs($this->owner())
            ->get(route('marketplace.reports.financial-quality.status', ['store_id' => $store->id]))
            ->assertOk()
            ->assertJson([
                'status' => 'processing',
                'store_id' => (string) $store->id,
                'processed_orders' => 10,
            ]);
    }

    public function test_process_now_runs_and_returns_success(): void
    {
        $store = $this->store();
        MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'UI-PROC-NOW-001',
            'channel_order_id' => 'UI-PROC-NOW-001',
            'order_date' => now(),
            'order_status' => 'COMPLETED',
        ]);

        $this->actingAs($this->owner())
            ->post(route('marketplace.reports.financial-quality.process-now'), [
                'store_id' => (string) $store->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_quality_refresh_hydrates_raw_order_items_when_normalized_rows_are_missing(): void
    {
        $store = $this->store();
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'UI-RAW-ITEM-001',
            'channel_order_id' => 'UI-RAW-ITEM-001',
            'order_date' => now(),
            'ordered_at' => now(),
            'order_status' => 'COMPLETED',
            'raw_json' => [
                'item_list' => [[
                    'item_id' => 'RAW-ITEM-1',
                    'model_id' => 'RAW-MODEL-1',
                    'item_name' => 'Raw marketplace item',
                    'item_sku' => 'RAW-SKU-1',
                    'model_sku' => 'RAW-SKU-1',
                    'model_name' => 'Black, M',
                    'model_quantity_purchased' => 1,
                    'model_original_price' => 75000,
                    'model_discounted_price' => 70000,
                ]],
            ],
        ]);

        app(MarketplaceFinancialDataQualityService::class)->refreshOrder($order->fresh());

        $this->assertDatabaseHas('marketplace_order_items', [
            'marketplace_order_id' => $order->id,
            'order_id' => $order->id,
            'item_name' => 'Raw marketplace item',
            'model_sku' => 'RAW-SKU-1',
            'variant_name' => 'Black, M',
        ]);
    }

    public function test_quality_report_counts_raw_items_before_the_audit_hydrates_them(): void
    {
        $store = $this->store();
        MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'UI-RAW-ITEM-VIEW-001',
            'channel_order_id' => 'UI-RAW-ITEM-VIEW-001',
            'order_date' => now(),
            'ordered_at' => now(),
            'order_status' => 'COMPLETED',
            'financial_data_status' => 'incomplete',
            'financial_issue_reason' => 'order_items_missing',
            'raw_json' => [
                'item_list' => [[
                    'item_name' => 'Raw item visible in snapshot',
                ]],
            ],
        ]);

        $this->actingAs($this->owner())
            ->get(route('marketplace.reports.financial-quality', [
                'store_id' => $store->id,
                'status' => 'incomplete',
            ]))
            ->assertOk()
            ->assertSee('UI-RAW-ITEM-VIEW-001')
            ->assertSee('0/1 siap')
            ->assertDontSee('Belum ada item');
    }

    public function test_quality_report_counts_legacy_order_items_linked_by_order_id(): void
    {
        $store = $this->store();
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'UI-LEGACY-ITEM-001',
            'channel_order_id' => 'UI-LEGACY-ITEM-001',
            'order_date' => now(),
            'ordered_at' => now(),
            'order_status' => 'COMPLETED',
            'financial_data_status' => 'incomplete',
            'financial_issue_reason' => 'item_mapping_or_hpp_incomplete',
        ]);
        MarketplaceOrderItem::create([
            'order_id' => $order->id,
            'marketplace_order_id' => null,
            'line_no' => 1,
            'item_name' => 'Legacy marketplace item',
            'qty' => 1,
            'data_status' => 'incomplete',
        ]);

        $this->actingAs($this->owner())
            ->get(route('marketplace.reports.financial-quality', [
                'store_id' => $store->id,
                'status' => 'incomplete',
            ]))
            ->assertOk()
            ->assertSee('UI-LEGACY-ITEM-001')
            ->assertSee('0/1 siap')
            ->assertDontSee('Belum ada item');
    }

    private function owner()
    {
        return \App\Models\User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-FIN-QUALITY-' . uniqid(),
        ]);
    }

    private function store(): Store
    {
        $channel = Channel::create([
            'code' => 'shopee',
            'name' => 'Shopee',
        ]);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => 'FIN-Q-' . uniqid(),
            'name' => 'Store Financial Quality',
            'status' => 'active',
            'is_active' => true,
        ]);
    }
}
