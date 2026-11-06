<?php

namespace Tests\Unit\Models;

use App\Models\MarketplaceOrderSettlement;
use Tests\TestCase;

class MarketplaceOrderSettlementAnalyticsBreakdownTest extends TestCase
{
    public function test_tiktok_breakdown_uses_fee_children_without_adding_aggregate_fee(): void
    {
        $settlement = new MarketplaceOrderSettlement();
        $settlement->setRawAttributes([
            'commission_fee' => 21908,
            'raw_json' => [
                'source' => 'tiktok_finance_202501_order_statement_transactions',
                'shipping_cost_amount' => '-990',
                'tiktok_fee_breakdown' => [
                    'fees' => [
                        'platform_commission_fee' => '-2707',
                        'dynamic_commission_fee' => '-4677',
                        'service_fee' => '-2339',
                        'transaction_fee' => '-1250',
                        'affiliate_commission_amount' => '-9945',
                    ],
                    'taxes' => [],
                ],
            ],
        ], true);

        $rows = $settlement->analyticsFeeBreakdown();

        $this->assertSame(7, count($rows));
        $this->assertSame(2707.0, $this->amountFor($rows, 'platform_commission'));
        $this->assertSame(4677.0, $this->amountFor($rows, 'dynamic_commission'));
        $this->assertSame(2339.0, $this->amountFor($rows, 'program_service_fee'));
        $this->assertSame(1250.0, $this->amountFor($rows, 'order_processing_fee'));
        $this->assertSame(9945.0, $this->amountFor($rows, 'affiliate_fee'));
        $this->assertSame('affiliate', $this->categoryFor($rows, 'affiliate_fee'));
        $this->assertSame('seller', collect($rows)->firstWhere('code', 'shipping_cost')['burden']);
        $this->assertSame(990.0, $this->amountFor($rows, 'shipping_difference'));
        $this->assertSame('seller', collect($rows)->firstWhere('code', 'shipping_difference')['burden']);
        $this->assertSame('Selisih Ongkir', collect($rows)->firstWhere('code', 'shipping_difference')['label']);
        $this->assertNotContains(21908.0, array_column($rows, 'amount'));
    }

    public function test_shopee_breakdown_maps_admin_processing_service_and_shipping_separately(): void
    {
        $settlement = new MarketplaceOrderSettlement();
        $settlement->setRawAttributes([
            'commission_fee' => 6639,
            'service_fee' => 9253,
            'transaction_fee' => 1250,
            'actual_shipping_fee' => 6500,
            'shipping_fee_subsidy' => 6500,
            'raw_json' => [],
        ], true);

        $rows = $settlement->analyticsFeeBreakdown();

        $this->assertSame(6639.0, $this->amountFor($rows, 'platform_commission'));
        $this->assertSame(9253.0, $this->amountFor($rows, 'program_service_fee'));
        $this->assertSame(1250.0, $this->amountFor($rows, 'order_processing_fee'));
        $this->assertSame(6500.0, $this->amountFor($rows, 'shipping_cost'));
        $this->assertSame('logistics', collect($rows)->firstWhere('code', 'shipping_cost')['burden']);
        $this->assertSame(6500.0, $this->amountFor($rows, 'shipping_subsidy'));
    }

    public function test_shopee_breakdown_surfaces_raw_premium_in_fee_composition(): void
    {
        $settlement = new MarketplaceOrderSettlement();
        $settlement->setRawAttributes([
            'shipping_insurance_fee' => 0,
            'raw_json' => [
                'shipping_seller_protection_fee_amount' => 350,
                'delivery_seller_protection_fee_premium_amount' => 125,
            ],
        ], true);

        $rows = $settlement->analyticsFeeBreakdown();

        $this->assertSame(475.0, array_sum(array_map(
            fn (array $row): float => ($row['code'] ?? null) === 'insurance_fee' ? (float) $row['amount'] : 0.0,
            $rows,
        )));

        $this->assertSame('Premi Shopee · potongan settlement', collect($rows)->firstWhere('code', 'insurance_fee')['label']);
        $this->assertSame('seller', collect($rows)->firstWhere('code', 'insurance_fee')['burden']);
    }

    public function test_shopee_shipping_difference_is_a_seller_burden(): void
    {
        $settlement = new MarketplaceOrderSettlement();
        $settlement->setRawAttributes([
            'actual_shipping_fee' => 10000,
            'shipping_fee_subsidy' => 7000,
            'raw_json' => ['buyer_paid_shipping_fee' => 1000],
        ], true);

        $rows = $settlement->analyticsFeeBreakdown();
        $difference = collect($rows)->firstWhere('code', 'shipping_difference');

        $this->assertSame(2000.0, (float) $difference['amount']);
        $this->assertSame('seller', $difference['burden']);
        $this->assertSame('Selisih Ongkir', $difference['label']);
    }

    public function test_tiktok_negative_statement_with_return_request_is_classified_as_reversal_not_refund(): void
    {
        $classification = MarketplaceOrderSettlement::classifyTikTokReturnRefund(
            [
                'order_status' => 'COMPLETED',
                'has_return_request' => true,
                'dispute_reason' => 'Produk tidak sesuai deskripsi (BUYER)',
            ],
            [
                'revenue_amount' => '0',
                'settlement_amount' => '-70007',
            ],
            0,
            -70007,
        );

        $this->assertSame(MarketplaceOrderSettlement::RETURN_STATUS_REVERSAL, $classification['status']);
        $this->assertNull($classification['refund_source']);
        $this->assertSame(70007.0, $classification['reversal_amount']);
        $this->assertSame('tiktok_finance_statement', $classification['reversal_source']);
    }

    public function test_tiktok_refund_principal_wins_over_negative_settlement_classification(): void
    {
        $classification = MarketplaceOrderSettlement::classifyTikTokReturnRefund(
            [
                'order_status' => 'COMPLETED',
                'has_return_request' => true,
            ],
            [
                'revenue_amount' => '-100000',
                'settlement_amount' => '-70007',
            ],
            100000,
            -70007,
        );

        $this->assertSame(MarketplaceOrderSettlement::RETURN_STATUS_REFUNDED, $classification['status']);
        $this->assertSame('tiktok_finance_revenue_breakdown', $classification['refund_source']);
        $this->assertSame(0.0, $classification['reversal_amount']);
    }

    private function amountFor(array $rows, string $code): float
    {
        foreach ($rows as $row) {
            if (($row['code'] ?? null) === $code) {
                return (float) $row['amount'];
            }
        }

        return 0.0;
    }

    private function categoryFor(array $rows, string $code): ?string
    {
        foreach ($rows as $row) {
            if (($row['code'] ?? null) === $code) {
                return $row['category'] ?? null;
            }
        }

        return null;
    }
}
