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

        $this->assertSame(6, count($rows));
        $this->assertSame(2707.0, $this->amountFor($rows, 'platform_commission'));
        $this->assertSame(4677.0, $this->amountFor($rows, 'dynamic_commission'));
        $this->assertSame(2339.0, $this->amountFor($rows, 'program_service_fee'));
        $this->assertSame(1250.0, $this->amountFor($rows, 'order_processing_fee'));
        $this->assertSame(9945.0, $this->amountFor($rows, 'affiliate_fee'));
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
        $this->assertSame(6500.0, $this->amountFor($rows, 'shipping_subsidy'));
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
}
