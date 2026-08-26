<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderIncomeEstimate;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MarketplaceProfitOverviewTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();

        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $this->store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'PROFIT-'.uniqid(),
            'name' => 'Profit Test Store',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function test_profit_endpoint_separates_actual_estimate_and_incomplete_rows(): void
    {
        $actual = $this->order('ACTUAL-ZERO', 'COMPLETED', 100000);
        $this->item($actual, 'SKU-ACTUAL', 1, 10000);
        MarketplaceOrderSettlement::create([
            'store_id' => $this->store->id,
            'order_id' => $actual->id,
            'channel_order_id' => $actual->channel_order_id,
            'buyer_payment_amount' => 100000,
            'final_income' => 0,
            'settlement_time' => '2026-08-27 10:00:00',
            'synced_at' => '2026-08-27 11:00:00',
            'raw_json' => [],
        ]);

        $estimated = $this->order('ESTIMATE-001', 'SHIPPED', 80000);
        $this->item($estimated, 'SKU-ESTIMATE', 1, 20000);
        MarketplaceOrderIncomeEstimate::create([
            'store_id' => $this->store->id,
            'marketplace_order_id' => $estimated->id,
            'channel_order_id' => $estimated->channel_order_id,
            'income_status' => 2,
            'estimated_escrow_amount' => 54321,
            'estimated_payout_at' => '2026-08-28 12:00:00',
            'synced_at' => '2026-08-26 12:00:00',
        ]);

        $incomplete = $this->order('INCOMPLETE-001', 'PROCESSED', 60000);
        $this->item($incomplete, 'SKU-NO-HPP', 1, 0);

        $response = $this->getJson('/api/marketplace/order-profits?order_date_from=2026-08-01&order_date_to=2026-08-31');
        $response->assertOk();
        $payload = $response->json();

        $this->assertSame(3, $payload['meta']['kpi_count']);
        $this->assertSame(2, $payload['meta']['kpi_ready_count']);
        $this->assertSame(1, $payload['meta']['kpi_missing_hpp_count']);
        $this->assertSame(1, $payload['meta']['kpi_missing_income_count']);
        $this->assertSame(1, $payload['meta']['kpi_actual_count']);
        $this->assertSame(1, $payload['meta']['kpi_estimated_count']);
        $this->assertSame(54321.0, (float) $payload['meta']['kpi_net']);

        $rows = collect($payload['paginator']['data'])->keyBy('channel_order_id');
        $this->assertSame('actual', $rows['ACTUAL-ZERO']['income_type']);
        $this->assertSame(0.0, (float) $rows['ACTUAL-ZERO']['final_income']);
        $this->assertSame('shopee_income_detail', $rows['ESTIMATE-001']['income_source']);
        $this->assertSame(54321.0, (float) $rows['ESTIMATE-001']['final_income']);
        $this->assertFalse($rows['INCOMPLETE-001']['profit_eligible']);
        $this->assertNull($rows['INCOMPLETE-001']['profit_net']);
    }

    public function test_pending_filter_uses_estimated_payout_date_and_hpp_filter_is_honest(): void
    {
        $pending = $this->order('PENDING-001', 'SHIPPED', 100000);
        $this->item($pending, 'SKU-PENDING', 1, 10000);
        MarketplaceOrderIncomeEstimate::create([
            'store_id' => $this->store->id,
            'marketplace_order_id' => $pending->id,
            'channel_order_id' => $pending->channel_order_id,
            'income_status' => 2,
            'estimated_escrow_amount' => 76000,
            'estimated_payout_at' => '2026-08-28 12:00:00',
            'synced_at' => now(),
        ]);

        $noEstimate = $this->order('PENDING-002', 'SHIPPED', 100000);
        $this->item($noEstimate, 'SKU-NO-ESTIMATE', 1, 0);

        $dateFiltered = $this->getJson('/api/marketplace/order-profits?settlement_status=belum_cair&settlement_date_from=2026-08-28&settlement_date_to=2026-08-28');
        $dateFiltered->assertOk()->assertJsonPath('paginator.total', 1)->assertJsonPath('paginator.data.0.channel_order_id', 'PENDING-001');

        $unsettled = $this->getJson('/api/marketplace/order-profits?settlement_status=belum_cair');
        $unsettled->assertOk()->assertJsonPath('paginator.total', 2);

        $hppEmpty = $this->getJson('/api/marketplace/order-profits?hpp_status=empty');
        $hppEmpty->assertOk()->assertJsonPath('paginator.total', 1)->assertJsonPath('paginator.data.0.channel_order_id', 'PENDING-002');
    }

    private function order(string $sn, string $status, float $amount): MarketplaceOrder
    {
        return MarketplaceOrder::create([
            'store_id' => $this->store->id,
            'external_order_id' => $sn,
            'channel_order_id' => $sn,
            'order_status' => $status,
            'total_amount' => $amount,
            'order_date' => Carbon::parse('2026-08-26 10:00:00'),
            'ordered_at' => Carbon::parse('2026-08-26 10:00:00'),
            'raw_json' => [],
        ]);
    }

    private function item(MarketplaceOrder $order, string $sku, int $qty, float $hpp): void
    {
        MarketplaceOrderItem::create([
            'marketplace_order_id' => $order->id,
            'order_id' => $order->id,
            'model_sku' => $sku,
            'qty' => $qty,
            'price' => 100000,
            'hpp_snapshot' => $hpp,
            'mapping_status' => $hpp > 0 ? 'mapped' : 'mapping_not_found',
        ]);
    }
}
