<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceOrdersFinancialVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_page_marks_financial_widgets_for_owner_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'ADMIN-VISIBILITY-'.uniqid()]);
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-VISIBILITY-'.uniqid()]);

        $this->actingAs($admin)
            ->get(route('marketplace.orders'))
            ->assertOk()
            ->assertSee('window.CAN_VIEW_MARKETPLACE_FINANCIALS = false', false);

        $this->actingAs($owner)
            ->get(route('marketplace.orders'))
            ->assertOk()
            ->assertSee('window.CAN_VIEW_MARKETPLACE_FINANCIALS = true', false);
    }

    public function test_non_owner_order_payload_does_not_expose_payout_or_profitability(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'VISIBILITY-'.uniqid(),
            'name' => 'Visibility Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'VISIBILITY-001',
            'channel_order_id' => 'VISIBILITY-001',
            'order_status' => 'COMPLETED',
            'status' => 'completed',
            'total_amount' => 100000,
            'subtotal_items' => 100000,
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'raw_json' => [
                'order_status' => 'COMPLETED',
                'income_details' => ['escrow_amount' => 85000],
            ],
            'meta' => ['source_type' => 'marketplace_import'],
        ]);
        MarketplaceOrderItem::create([
            'marketplace_order_id' => $order->id,
            'order_id' => $order->id,
            'model_sku' => 'VISIBILITY-SKU',
            'qty' => 1,
            'price' => 100000,
            'hpp_snapshot' => 20000,
            'mapping_status' => 'mapped',
        ]);
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => 100000,
            'final_income' => 85000,
            'settlement_time' => now()->subHours(2),
            'raw_json' => ['escrow_amount' => 85000, 'commission_fee' => 5000],
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'ADMIN-VISIBILITY-'.uniqid()]);
        $payload = $this->actingAs($admin)
            ->getJson('/api/marketplace/local-orders-paginated?tab=completed')
            ->assertOk()
            ->json('data.0');

        $this->assertSame('hidden', $payload['income_summary']['source']);
        $this->assertNull($payload['income_summary']['amount']);
        $this->assertNull($payload['profit_after_ads_summary']);
        $this->assertArrayNotHasKey('income_estimate', $payload);
        $this->assertArrayNotHasKey('raw_json', $payload['settlement'] ?? []);
        $this->assertArrayNotHasKey('final_income', $payload['settlement'] ?? []);
        $this->assertArrayNotHasKey('income_amount', $payload['items'][0] ?? []);
        $this->assertArrayNotHasKey('hpp_snapshot', $payload['items'][0] ?? []);
        $this->assertArrayNotHasKey('net_payout_estimated', $payload);

        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-VISIBILITY-'.uniqid()]);
        $ownerPayload = $this->actingAs($owner)
            ->getJson('/api/marketplace/local-orders-paginated?tab=completed')
            ->assertOk()
            ->json('data.0');

        $this->assertSame(85000.0, (float) $ownerPayload['income_summary']['amount']);
        $this->assertSame(85000.0, (float) $ownerPayload['settlement']['final_income']);
        $this->assertNotNull($ownerPayload['profit_after_ads_summary']);
    }
}
