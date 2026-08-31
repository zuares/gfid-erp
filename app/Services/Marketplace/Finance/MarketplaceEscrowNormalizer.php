<?php

namespace App\Services\Marketplace\Finance;

use Carbon\Carbon;

class MarketplaceEscrowNormalizer
{
    /**
     * @return array{
     *     order_sn:string,
     *     gross_amount:string|null,
     *     net_amount:string|null,
     *     released_at:Carbon|null,
     *     components:array<int,array{code:string,name:string,amount:string,direction:string,provider_line_id:string,raw_payload:array}>,
     *     raw_payload:array,
     *     source_hash:string
     * }
     */
    public function normalize(array $detail, string $fallbackOrderSn): array
    {
        $rawPayload = is_array($detail['raw_response'] ?? null)
            ? $detail['raw_response']
            : $detail;
        $root = is_array($rawPayload['response'] ?? null) ? $rawPayload['response'] : $rawPayload;
        $income = is_array($detail['income'] ?? null) ? $detail['income'] : [];
        if ($income === [] && is_array($root['order_income'] ?? null)) {
            $income = $root['order_income'];
        }

        $orderSn = trim((string) ($detail['order_sn'] ?? $income['order_sn'] ?? $root['order_sn'] ?? $fallbackOrderSn));
        $grossAmount = $this->firstAmount($income, $root, [
            'buyer_total_amount',
            'buyer_payment_amount',
            'buyer_paid_amount',
            'order_selling_price',
            'gross_amount',
        ]);
        $netAmount = $this->firstAmount($income, $root, [
            'escrow_amount',
            'final_income',
            'payout_amount',
            'net_amount',
        ]);

        $components = $this->components($income, $root);
        $releasedAt = $this->releaseTime(
            $detail['escrow_release_at'] ?? null,
            $income['escrow_release_time'] ?? null,
            $income['release_time'] ?? null,
            $root['escrow_release_time'] ?? null,
            $root['release_time'] ?? null,
        );

        return [
            'order_sn' => $orderSn,
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
            'released_at' => $releasedAt,
            'components' => $components,
            'raw_payload' => $rawPayload,
            'source_hash' => $this->sourceHash($rawPayload),
        ];
    }

    /**
     * @return array<int,array{code:string,name:string,amount:string,direction:string,provider_line_id:string,raw_payload:array}>
     */
    private function components(array $income, array $root): array
    {
        $components = [];

        $definitions = [
            ['admin_fee', 'Admin Fee', ['commission_fee', 'admin_fee', 'seller_commission_fee']],
            ['service_fee', 'Service Fee', ['service_fee']],
            ['affiliate_fee', 'Affiliate Fee', ['affiliate_fee', 'affiliate_commission_fee', 'affiliate_commission', 'seller_affiliate_fee']],
            ['shipping_adjustment', 'Shipping Adjustment', ['shipping_adjustment', 'shipping_fee_adjustment', 'shipping_fee_subsidy', 'shipping_fee_rebate', 'shopee_shipping_rebate', 'reverse_shipping_fee']],
            ['voucher', 'Voucher', ['voucher_from_seller', 'seller_voucher', 'seller_voucher_rebate', 'voucher']],
            ['rebate', 'Rebate', ['rebate', 'seller_coin_cash_back', 'seller_absorbed_coin_discount']],
            ['refund', 'Refund', ['refund', 'refund_amount', 'drc_adjustable_refund', 'seller_return_refund_amount', 'seller_return_refund']],
        ];

        foreach ($definitions as [$code, $name, $keys]) {
            [$found, $value, $key] = $this->firstValue($income, $root, $keys);
            if ($found) {
                $components[] = $this->component($code, $name, $value, (string) $key, $income, $root);
            }
        }

        [$sellerTransactionFound, $sellerTransaction, $sellerTransactionKey] = $this->firstValue(
            $income,
            $root,
            ['seller_transaction_fee']
        );
        [$processingFound, $processing, $processingKey] = $this->firstValue(
            $income,
            $root,
            ['seller_order_processing_fee']
        );
        if ($sellerTransactionFound || $processingFound) {
            $amount = $this->number($sellerTransactionFound ? $sellerTransaction : 0)
                + $this->number($processingFound ? $processing : 0);
            $components[] = $this->component(
                'transaction_fee',
                'Transaction Fee',
                $amount,
                implode('+', array_filter([(string) $sellerTransactionKey, (string) $processingKey])),
                $income,
                $root,
            );
        } else {
            [$found, $value, $key] = $this->firstValue($income, $root, ['transaction_fee']);
            if ($found) {
                $components[] = $this->component('transaction_fee', 'Transaction Fee', $value, (string) $key, $income, $root);
            }
        }

        foreach (['adjustment_amount', 'other_adjustment', 'seller_other_adjustment', 'misc_adjustment'] as $key) {
            if (array_key_exists($key, $income) || array_key_exists($key, $root)) {
                $value = array_key_exists($key, $income) ? $income[$key] : $root[$key];
                $components[] = $this->component('other_adjustment', 'Other Adjustment', $value, $key, $income, $root);
            }
        }

        foreach (['adjustment_list', 'adjustments', 'fee_detail_list'] as $listKey) {
            $rows = $income[$listKey] ?? $root[$listKey] ?? null;
            if (! is_array($rows)) {
                continue;
            }

            foreach ($rows as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $valueKey = $this->firstExistingKey($row, ['amount', 'value', 'fee_amount', 'adjustment_amount']);
                if ($valueKey === null) {
                    continue;
                }
                $code = $this->slug((string) ($row['component_code'] ?? $row['type'] ?? $row['fee_type'] ?? 'other_adjustment'));
                $name = trim((string) ($row['component_name'] ?? $row['name'] ?? $code));
                $providerLineId = (string) ($row['line_id'] ?? $row['id'] ?? $listKey.':'.$index);
                $components[] = $this->component($code, $name, $row[$valueKey], $providerLineId, $row, $row);
            }
        }

        return $components;
    }

    /**
     * @return array{code:string,name:string,amount:string,direction:string,provider_line_id:string,raw_payload:array}
     */
    private function component(string $code, string $name, mixed $value, string $providerLineId, array $income, array $root): array
    {
        $amount = $this->decimal($value);

        return [
            'code' => $code,
            'name' => $name,
            'amount' => $amount,
            'direction' => (float) $amount < 0 ? 'credit' : 'debit',
            'provider_line_id' => $providerLineId,
            'raw_payload' => [
                'provider_line_id' => $providerLineId,
                'value' => $value,
                'income' => $income,
                'root' => $root,
            ],
        ];
    }

    private function firstAmount(array $income, array $root, array $keys): ?string
    {
        [$found, $value] = $this->firstValue($income, $root, $keys);

        return $found ? $this->decimal($value) : null;
    }

    /** @return array{bool,mixed,string|null} */
    private function firstValue(array $income, array $root, array $keys): array
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $income)) {
                return [true, $income[$key], $key];
            }
            if (array_key_exists($key, $root)) {
                return [true, $root[$key], $key];
            }
        }

        return [false, null, null];
    }

    private function firstExistingKey(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return $key;
            }
        }

        return null;
    }

    private function decimal(mixed $value): string
    {
        if (is_string($value)) {
            $value = str_replace(',', '', trim($value));
        }
        if (! is_numeric($value)) {
            throw new \UnexpectedValueException('Nilai escrow component bukan angka valid.');
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function number(mixed $value): float
    {
        return (float) $this->decimal($value);
    }

    private function releaseTime(mixed ...$values): ?Carbon
    {
        foreach ($values as $value) {
            if ($value instanceof Carbon) {
                return $value;
            }
            if ($value === null || $value === '') {
                continue;
            }
            if (is_numeric($value)) {
                $seconds = (int) $value;
                if ($seconds > 10_000_000_000) {
                    $seconds = (int) floor($seconds / 1000);
                }
                if ($seconds > 0) {
                    return Carbon::createFromTimestamp($seconds, config('app.timezone'));
                }

                continue;
            }
            try {
                return Carbon::parse((string) $value, config('app.timezone'));
            } catch (\Throwable) {
                // Try the next known timestamp field.
            }
        }

        return null;
    }

    private function sourceHash(array $payload): string
    {
        return hash('sha256', json_encode($this->canonical($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
    }

    private function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $item) => $this->canonical($item), $value);
        }

        ksort($value);

        return array_map(fn (mixed $item) => $this->canonical($item), $value);
    }

    private function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? 'other_adjustment';

        return trim($value, '_') ?: 'other_adjustment';
    }
}
