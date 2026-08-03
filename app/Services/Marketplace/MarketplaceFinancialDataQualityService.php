<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplaceOrderItem;

class MarketplaceFinancialDataQualityService
{
    public const SETTLEMENT_UNKNOWN = 'unknown';
    public const SETTLEMENT_INCOMPLETE = 'incomplete';
    public const SETTLEMENT_COMPLETE = 'complete';

    public const ORDER_UNKNOWN = 'unknown';
    public const ORDER_INCOMPLETE = 'incomplete';
    public const ORDER_READY = 'ready';
    public const ORDER_NOT_APPLICABLE = 'not_applicable';

    // Final income/settlement hanya dianggap sebagai fakta finansial setelah
    // order COMPLETED. Status fulfillment sebelumnya belum memiliki payout
    // final yang wajib masuk laporan keuangan.
    public const FINANCIAL_ELIGIBLE_ORDER_STATUSES = ['COMPLETED'];

    // Field belum tersedia pada sebagian response Shopee lama. Nilainya tetap
    // disimpan 0, tetapi tidak boleh membuat seluruh settlement dianggap invalid.
    private const NON_BLOCKING_MISSING_FIELDS = [
        'affiliate_fee',
        'shipping_insurance_fee',
    ];

    /**
     * Field aliases required before a settlement can be used as a financial fact.
     * Missing is intentionally different from a value of zero.
     */
    private const REQUIRED_FIELD_ALIASES = [
        'buyer_payment_amount' => [
            'buyer_total_amount',
            'buyer_paid_amount',
            'buyer_payment_amount',
            'order_selling_price',
        ],
        'commission_fee' => ['commission_fee'],
        'service_fee' => ['service_fee'],
        'transaction_fee' => [
            'seller_transaction_fee',
            'seller_order_processing_fee',
            'transaction_fee',
        ],
        'affiliate_fee' => [
            'affiliate_fee',
            'affiliate_commission_fee',
            'affiliate_commission',
            'seller_affiliate_fee',
        ],
        'seller_voucher' => [
            'voucher_from_seller',
            'seller_voucher_rebate',
            'seller_voucher',
        ],
        'seller_coin_cash_back' => [
            'seller_coin_cash_back',
            'seller_absorbed_coin_discount',
        ],
        'actual_shipping_fee' => [
            'actual_shipping_fee',
            'estimated_shipping_fee',
        ],
        'shipping_fee_subsidy' => [
            'shopee_shipping_rebate',
            'shipping_fee_rebate',
        ],
        'reverse_shipping_fee' => ['reverse_shipping_fee'],
        'shipping_insurance_fee' => [
            'shipping_insurance_fee',
            'shipping_insurance',
            'insurance_fee',
        ],
        'activity_fee' => [
            'order_ams_commission_fee',
            'ams_commission_fee',
            'activity_fee',
        ],
        'drc_adjustable_refund' => [
            'drc_adjustable_refund',
            'seller_return_refund_amount',
        ],
        'escrow_tax' => ['escrow_tax'],
        'final_income' => ['final_income', 'escrow_amount'],
    ];

    /**
     * Assess raw settlement data without mutating it.
     *
     * @return array{status:string, missing_fields:array<int,string>, flags:array<string,mixed>}
     */
    public function assessSettlement(?array $raw): array
    {
        $raw = is_array($raw) ? $raw : [];
        $payload = is_array($raw['income'] ?? null) ? $raw['income'] : $raw;
        $missing = [];

        foreach (self::REQUIRED_FIELD_ALIASES as $field => $aliases) {
            $present = false;
            foreach ($aliases as $alias) {
                if (array_key_exists($alias, $payload) && $payload[$alias] !== null && $payload[$alias] !== '') {
                    $present = true;
                    break;
                }
            }

            if (! $present) {
                $missing[] = $field;
            }
        }

        $blockingMissing = array_values(array_diff($missing, self::NON_BLOCKING_MISSING_FIELDS));

        return [
            'status' => empty($blockingMissing) ? self::SETTLEMENT_COMPLETE : self::SETTLEMENT_INCOMPLETE,
            'missing_fields' => $missing,
            'flags' => [
                'source' => 'settlement_raw_json',
                'missing_financial_fields' => $missing,
                'blocking_missing_fields' => $blockingMissing,
                'has_raw_payload' => ! empty($payload),
            ],
        ];
    }

    public function refreshSettlement(MarketplaceOrderSettlement $settlement): array
    {
        $assessment = $this->assessSettlement($settlement->raw_json);

        if (! $settlement->order_id || ! $settlement->store_id) {
            $assessment['status'] = self::SETTLEMENT_INCOMPLETE;
            $assessment['flags']['reason'] = 'missing_order_or_store_link';
        }

        $settlement->forceFill([
            'data_status' => $assessment['status'],
            'data_quality_flags' => $assessment['flags'],
            'data_checked_at' => now(),
        ])->saveQuietly();

        return $assessment;
    }

    public function refreshOrder(MarketplaceOrder $order): array
    {
        $order->loadMissing(['settlement', 'items']);

        if ($order->settlement) {
            $this->refreshSettlement($order->settlement);
        }

        $assessment = $this->assessOrder($order);
        return $this->saveOrderQuality($order, $assessment['status'], $assessment['reason']);
    }

    /**
     * Evaluate an order without writing anything. Useful for audit/dry-run.
     *
     * @return array{status:string, reason:?string}
     */
    public function assessOrder(MarketplaceOrder $order): array
    {
        $order->loadMissing(['settlement', 'items']);

        if (in_array(strtoupper((string) $order->order_status), ['CANCELLED', 'BATAL', 'IN_CANCEL'], true)) {
            return ['status' => self::ORDER_NOT_APPLICABLE, 'reason' => null];
        }

        if (! in_array(strtoupper((string) $order->order_status), self::FINANCIAL_ELIGIBLE_ORDER_STATUSES, true)) {
            return ['status' => self::ORDER_NOT_APPLICABLE, 'reason' => null];
        }

        if (! $order->settlement) {
            return ['status' => self::ORDER_INCOMPLETE, 'reason' => 'settlement_missing'];
        }

        $settlementAssessment = $this->assessSettlement($order->settlement->raw_json);
        if ($settlementAssessment['status'] !== self::SETTLEMENT_COMPLETE) {
            return ['status' => self::ORDER_INCOMPLETE, 'reason' => 'settlement_data_incomplete'];
        }

        $items = $order->items;
        if ($items->isEmpty()) {
            return ['status' => self::ORDER_INCOMPLETE, 'reason' => 'order_items_missing'];
        }

        $badItem = $items->first(function (MarketplaceOrderItem $item) {
            return ($item->data_status ?? null) !== 'valid'
                || (float) ($item->hpp_snapshot ?? 0) <= 0;
        });

        if ($badItem) {
            return ['status' => self::ORDER_INCOMPLETE, 'reason' => 'item_mapping_or_hpp_incomplete'];
        }

        return ['status' => self::ORDER_READY, 'reason' => null];
    }

    private function saveOrderQuality(MarketplaceOrder $order, string $status, ?string $reason): array
    {
        $order->forceFill([
            'financial_data_status' => $status,
            'financial_issue_reason' => $reason,
            'financial_checked_at' => now(),
        ])->saveQuietly();

        return [
            'status' => $status,
            'reason' => $reason,
        ];
    }
}
