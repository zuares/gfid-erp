<?php

namespace Tests\Unit\Services;

use App\Models\Channel;
use App\Models\MarketplaceAdWalletTransaction;
use App\Models\MarketplaceAdsDaily;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceFinancialStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceFinancialStatementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_statement_reconciles_actual_payout_and_builds_operating_profit(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'STATEMENT-' . uniqid(),
            'name' => 'Statement Test Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'STATEMENT-001',
            'channel_order_id' => 'STATEMENT-001',
            'order_date' => '2026-08-04 10:00:00',
            'ordered_at' => '2026-08-04 10:00:00',
            'order_status' => 'COMPLETED',
            'financial_data_status' => 'ready',
        ]);
        MarketplaceOrderItem::create([
            'order_id' => $order->id,
            'marketplace_order_id' => $order->id,
            'line_no' => 1,
            'item_name' => 'Statement Product',
            'model_sku' => 'STATEMENT-SKU',
            'qty' => 2,
            'price' => 150,
            'hpp_snapshot' => 50,
            'data_status' => 'valid',
        ]);
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => 'STATEMENT-001',
            'buyer_payment_amount' => 300,
            'seller_voucher' => 20,
            'commission_fee' => 30,
            'service_fee' => 10,
            'transaction_fee' => 5,
            'drc_adjustable_refund' => 5,
            'final_income' => 235,
            'ad_cost' => 10,
            'data_status' => 'complete',
            'raw_json' => [],
        ]);
        MarketplaceAdWalletTransaction::create([
            'store_id' => $store->id,
            'external_transaction_id' => 'AD-CHARGE-1',
            'transaction_type' => 'paid_ads_charge',
            // Simulate a legacy/proxy row with an inverted sign; the report
            // must classify by transaction_type, not amount sign.
            'amount' => 150,
            'money_flow' => 'MONEY_OUT',
            'status' => 'COMPLETED',
            'transaction_created_at' => '2026-08-10 10:00:00',
            'source_payload' => [],
        ]);
        MarketplaceAdWalletTransaction::create([
            'store_id' => $store->id,
            'external_transaction_id' => 'AD-REFUND-1',
            'transaction_type' => 'paid_ads_refund',
            'amount' => -20,
            'money_flow' => 'MONEY_IN',
            'status' => 'COMPLETED',
            'transaction_created_at' => '2026-08-11 10:00:00',
            'source_payload' => [],
        ]);
        MarketplaceAdsDaily::create([
            'store_id' => $store->id,
            'date' => '2026-08-10',
            'spend' => 100,
        ]);

        $statement = app(MarketplaceFinancialStatementService::class)->statement([
            'store_id' => $store->id,
            'date_basis' => 'ordered_at',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
        ]);

        $this->assertSame(280.0, $statement['summary']['net_sales_before_settlement']);
        $this->assertSame(230.0, $statement['summary']['expected_payout_before_other_adjustments']);
        $this->assertSame(5.0, $statement['summary']['other_settlement_adjustment']);
        $this->assertSame(135.0, $statement['summary']['gross_profit']);
        $this->assertSame(135.0, $statement['summary']['operating_profit']);
        $this->assertSame(0.0, $statement['reconciliation']['difference']);
        $this->assertSame(150.0, $statement['summary']['wallet_ad_charge']);
        $this->assertSame(20.0, $statement['summary']['wallet_ad_refund']);
        $this->assertSame(130.0, $statement['summary']['wallet_ad_cost']);
        $this->assertSame(100.0, $statement['summary']['ads_daily_spend']);
        $this->assertSame(30.0, $statement['summary']['ad_cost_variance']);
    }
}
