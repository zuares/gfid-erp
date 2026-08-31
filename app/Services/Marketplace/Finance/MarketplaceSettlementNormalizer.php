<?php

namespace App\Services\Marketplace\Finance;

use App\Domain\Marketplace\Finance\Enums\SettlementStatus;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class MarketplaceSettlementNormalizer
{
    /** @return array{external_settlement_id:string,settlement_date:Carbon|null,amount:string|null,currency:string,status:SettlementStatus,raw_payload:array} */
    public function payout(array $row): array
    {
        $info = is_array($row['payout_info'] ?? null) ? $row['payout_info'] : $row;
        $identifier = $this->firstString($info, [
            'encrypted_payout_id', 'payout_id', 'payout_sn', 'payout_reference',
            'withdrawal_id', 'withdraw_id', 'transaction_id',
        ]);

        return [
            'external_settlement_id' => $identifier,
            'settlement_date' => $this->timestamp($info['payout_time'] ?? $info['settlement_time'] ?? $info['create_time'] ?? null),
            'amount' => $this->amount($info['payout_amount'] ?? $info['amount'] ?? $info['net_amount'] ?? null),
            'currency' => $this->currency($info['payout_currency'] ?? $info['currency'] ?? null),
            'status' => $this->status($info['status'] ?? $info['payout_status'] ?? $row['status'] ?? null),
            'raw_payload' => Arr::except($row, ['_meta']),
        ];
    }

    /** @return array<int,array{order_sn:string,amount:string|null,raw_payload:array}> */
    public function allocations(array $row): array
    {
        $items = $row['escrow_list'] ?? $row['escrow_details'] ?? $row['order_list'] ?? [];
        if (is_array($items) && ! array_is_list($items) && (
            array_key_exists('order_sn', $items) || array_key_exists('order_id', $items)
        )) {
            $items = [$items];
        }
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->map(function (array $item): array {
                return [
                    'order_sn' => trim((string) ($item['order_sn'] ?? $item['order_id'] ?? $item['ordersn'] ?? '')),
                    'amount' => $this->amount($item['escrow_amount'] ?? $item['payout_amount'] ?? $item['allocated_amount'] ?? $item['amount'] ?? $item['final_income'] ?? null),
                    'raw_payload' => $item,
                ];
            })
            ->filter(static fn (array $item): bool => $item['order_sn'] !== '')
            ->values()
            ->all();
    }

    /** @return array{external_settlement_id:string,settlement_date:Carbon|null,amount:string|null,currency:string,status:SettlementStatus,raw_payload:array} */
    public function wallet(array $row): array
    {
        $identifier = $this->firstString($row, [
            'transaction_id', 'withdraw_id', 'withdrawal_id', 'root_withdrawal_id',
        ]);
        $status = $this->status($row['status'] ?? $row['transaction_status'] ?? null);
        $type = strtolower(str_replace('-', '_', trim((string) ($row['transaction_type'] ?? $row['type'] ?? ''))));
        if ($status === SettlementStatus::UNKNOWN && in_array($type, ['withdrawal_completed', '202'], true)) {
            $status = SettlementStatus::RECEIVED;
        }

        return [
            'external_settlement_id' => $identifier,
            'settlement_date' => $this->timestamp($row['create_time'] ?? $row['transaction_time'] ?? null),
            'amount' => $this->amount($row['amount'] ?? $row['transaction_amount'] ?? $row['withdrawal_amount'] ?? $row['net_amount'] ?? null),
            'currency' => $this->currency($row['currency'] ?? $row['transaction_currency'] ?? null),
            'status' => $status,
            'raw_payload' => Arr::except($row, ['_meta']),
        ];
    }

    private function firstString(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function amount(mixed $value): ?string
    {
        if ($value === null || $value === '' || ! is_numeric(str_replace(',', '', (string) $value))) {
            return null;
        }

        return number_format(abs((float) str_replace(',', '', (string) $value)), 2, '.', '');
    }

    private function currency(mixed $value): string
    {
        $currency = strtoupper(trim((string) $value));

        return $currency !== '' ? $currency : 'IDR';
    }

    private function status(mixed $value): SettlementStatus
    {
        $value = strtolower(str_replace(['-', ' '], '_', trim((string) $value)));

        return match ($value) {
            'received', 'completed', 'success', 'successful', 'paid', 'settled', 'withdrawal_completed' => SettlementStatus::RECEIVED,
            'pending', 'created', 'processing', 'initiated', 'withdrawal_created' => SettlementStatus::PENDING,
            'void', 'cancelled', 'canceled', 'failed', 'reversed' => SettlementStatus::VOID,
            default => SettlementStatus::UNKNOWN,
        };
    }

    private function timestamp(mixed $value): ?Carbon
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $timestamp = (int) $value;
        if ($timestamp > 10_000_000_000) {
            $timestamp = (int) floor($timestamp / 1000);
        }

        try {
            return Carbon::createFromTimestamp($timestamp, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }
}
