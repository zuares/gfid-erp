<?php

namespace Tests\Unit\Services;

use App\Models\MarketplaceOrder;
use App\Services\Marketplace\MarketplaceFinancialDataQualityService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MarketplaceFinancialDataQualityServiceTest extends TestCase
{
    public function test_non_completed_orders_are_not_applicable_until_final_settlement_exists(): void
    {
        $order = new MarketplaceOrder(['order_status' => 'SHIPPED']);
        $order->setRelation('settlement', null);
        $order->setRelation('items', new Collection());

        $assessment = (new MarketplaceFinancialDataQualityService())->assessOrder($order);

        $this->assertSame(MarketplaceFinancialDataQualityService::ORDER_NOT_APPLICABLE, $assessment['status']);
        $this->assertNull($assessment['reason']);
    }

    public function test_optional_missing_fee_fields_do_not_block_settlement(): void
    {
        $service = new MarketplaceFinancialDataQualityService();

        $assessment = $service->assessSettlement([
            'buyer_total_amount' => 100000,
            'commission_fee' => 5000,
            'service_fee' => 6000,
            'seller_transaction_fee' => 1000,
            'voucher_from_seller' => 0,
            'seller_coin_cash_back' => 0,
            'actual_shipping_fee' => 7000,
            'shopee_shipping_rebate' => 7000,
            'reverse_shipping_fee' => 0,
            'order_ams_commission_fee' => 0,
            'drc_adjustable_refund' => 0,
            'escrow_tax' => 0,
            'escrow_amount' => 81000,
        ]);

        $this->assertSame('complete', $assessment['status']);
        $this->assertSame([], $assessment['flags']['blocking_missing_fields']);
        $this->assertSame(
            ['affiliate_fee', 'shipping_insurance_fee'],
            $assessment['flags']['missing_financial_fields']
        );
    }

    public function test_cost_of_goods_sold_is_not_accepted_as_buyer_payment(): void
    {
        $service = new MarketplaceFinancialDataQualityService();

        $assessment = $service->assessSettlement([
            'cost_of_goods_sold' => 50000,
            'commission_fee' => 5000,
            'service_fee' => 6000,
            'seller_transaction_fee' => 1000,
            'escrow_amount' => 38000,
        ]);

        $this->assertSame('incomplete', $assessment['status']);
        $this->assertContains('buyer_payment_amount', $assessment['flags']['blocking_missing_fields']);
    }

    public function test_explicit_zero_is_present_not_missing(): void
    {
        $service = new MarketplaceFinancialDataQualityService();

        $assessment = $service->assessSettlement([
            'buyer_total_amount' => 100000,
            'commission_fee' => 0,
            'service_fee' => 0,
            'transaction_fee' => 0,
            'affiliate_fee' => 0,
            'voucher_from_seller' => 0,
            'seller_coin_cash_back' => 0,
            'actual_shipping_fee' => 0,
            'shopee_shipping_rebate' => 0,
            'reverse_shipping_fee' => 0,
            'shipping_insurance_fee' => 0,
            'activity_fee' => 0,
            'drc_adjustable_refund' => 0,
            'escrow_tax' => 0,
            'final_income' => 100000,
        ]);

        $this->assertSame('complete', $assessment['status']);
        $this->assertSame([], $assessment['flags']['missing_financial_fields']);
    }
}
