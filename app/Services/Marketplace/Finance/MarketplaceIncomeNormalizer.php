<?php

namespace App\Services\Marketplace\Finance;

use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use Carbon\Carbon;

class MarketplaceIncomeNormalizer
{
    /**
     * @return array{order_sn:string,status:IncomeStatus,released_at:Carbon|null,raw_payload:array,source_hash:string}
     */
    public function normalize(array $row): array
    {
        $orderSn = trim((string) ($row['order_sn'] ?? $row['order_id'] ?? $row['ordersn'] ?? ''));
        $statusValue = array_key_exists('status', $row) ? $row['status'] : ($row['income_status'] ?? null);

        return [
            'order_sn' => $orderSn,
            'status' => $this->status($statusValue),
            'released_at' => $this->releaseTime(
                $row['released_at']
                    ?? $row['release_time']
                    ?? $row['released_time']
                    ?? $row['income_release_time']
                    ?? null
            ),
            'raw_payload' => $row,
            'source_hash' => hash('sha256', $this->canonical($row)),
        ];
    }

    private function status(mixed $value): IncomeStatus
    {
        if (is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', trim($value)))) {
            return match ((int) $value) {
                2 => IncomeStatus::PENDING,
                0 => IncomeStatus::TO_RELEASE,
                1 => IncomeStatus::RELEASED,
                default => IncomeStatus::UNKNOWN,
            };
        }

        $value = strtolower(trim((string) $value));
        $value = str_replace(['-', ' '], '_', $value);

        return match ($value) {
            'pending' => IncomeStatus::PENDING,
            'to_release', 'to_be_released', 'torelease' => IncomeStatus::TO_RELEASE,
            'released', 'release', 'paid' => IncomeStatus::RELEASED,
            default => IncomeStatus::UNKNOWN,
        };
    }

    private function releaseTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                $timestamp = (int) $value;
                if ($timestamp > 9999999999) {
                    $timestamp = (int) floor($timestamp / 1000);
                }

                return Carbon::createFromTimestamp($timestamp, config('app.timezone'));
            }

            return Carbon::parse((string) $value, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }

    private function canonical(array $payload): string
    {
        $normalized = $this->sortKeys($payload);

        return json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function sortKeys(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = array_is_list($value)
                    ? array_map(fn (mixed $item) => is_array($item) ? $this->sortKeys($item) : $item, $value)
                    : $this->sortKeys($value);
            }
        }

        if (! array_is_list($payload)) {
            ksort($payload);
        }

        return $payload;
    }
}
