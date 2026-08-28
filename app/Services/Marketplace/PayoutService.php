<?php

namespace App\Services\Marketplace;

use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use RuntimeException;

/**
 * Live reader untuk payout Shopee Cross-Border.
 * Tidak menyimpan atau mengubah data lokal; response Shopee tetap dibawa ke UI.
 */
class PayoutService
{
    private const SHOPEE_CODES = ['shopee', 'shp'];

    public function __construct(
        protected MarketplaceApiGateway $gateway,
    ) {}

    /**
     * @return array{items:array<int,array<string,mixed>>,more:bool,next_cursor:string,page_size:int,raw_response:array}
     */
    public function fetchInfo(
        Store $store,
        Carbon $from,
        Carbon $to,
        string $cursor = '',
        int $pageSize = 100,
    ): array {
        $this->assertShopee($store);

        $pageSize = min(100, max(1, $pageSize));
        $response = $this->gateway->getPayoutInfo(
            $store,
            $from->timestamp,
            $to->timestamp,
            trim($cursor),
            $pageSize,
        );
        $this->assertSuccessfulResponse($response);

        $root = data_get($response, 'response');
        if (! is_array($root)) {
            $root = $response;
        }

        $rows = $root['payout_list'] ?? [];
        if (is_array($rows) && ! array_is_list($rows)) {
            $rows = [$rows];
        }
        if (! is_array($rows)) {
            $rows = [];
        }

        return [
            'items' => collect($rows)
                ->filter(static fn (mixed $row): bool => is_array($row))
                ->map(fn (array $row): array => $this->normalizeInfo($row))
                ->values()
                ->all(),
            'more' => (bool) ($root['more'] ?? false),
            'next_cursor' => (string) ($root['next_cursor'] ?? ''),
            'page_size' => $pageSize,
            'raw_response' => $this->withoutMeta($response),
        ];
    }

    /**
     * @return array{items:array<int,array<string,mixed>>,more:bool,page_no:int,page_size:int,raw_response:array}
     */
    public function fetchDetail(
        Store $store,
        Carbon $from,
        Carbon $to,
        int $pageNo = 1,
        int $pageSize = 100,
    ): array {
        $this->assertShopee($store);

        $pageNo = max(1, $pageNo);
        $pageSize = min(100, max(1, $pageSize));
        $response = $this->gateway->getPayoutDetail(
            $store,
            $from->timestamp,
            $to->timestamp,
            $pageNo,
            $pageSize,
        );
        $this->assertSuccessfulResponse($response);

        $root = data_get($response, 'response');
        if (! is_array($root)) {
            $root = $response;
        }

        $rows = $root['payout_list'] ?? [];
        if (! is_array($rows)) {
            $rows = [];
        }

        return [
            'items' => collect($rows)
                ->filter(static fn (mixed $row): bool => is_array($row))
                ->map(fn (array $row): array => $this->normalizeDetail($row))
                ->values()
                ->all(),
            'more' => (bool) ($root['more'] ?? false),
            'page_no' => $pageNo,
            'page_size' => $pageSize,
            'raw_response' => $this->withoutMeta($response),
        ];
    }

    /** @return array<string,mixed> */
    private function normalizeInfo(array $row): array
    {
        $info = $row['payout_info'] ?? $row;
        if (! is_array($info)) {
            $info = [];
        }

        $rawTime = $info['payout_time'] ?? null;

        return [
            'from_currency' => $info['from_currency'] ?? null,
            'payout_currency' => $info['payout_currency'] ?? null,
            'from_amount' => $this->numberOrNull($info['from_amount'] ?? null),
            'payout_amount' => $this->numberOrNull($info['payout_amount'] ?? null),
            'exchange_rate' => $info['exchange_rate'] ?? null,
            'payout_time' => is_numeric($rawTime) ? (int) $rawTime : null,
            'payout_at' => $this->timestamp($rawTime)?->toIso8601String(),
            'pay_service' => $info['pay_service'] ?? null,
            'payee_id' => $info['payee_id'] ?? null,
            'encrypted_payout_id' => $info['encrypted_payout_id'] ?? null,
            'raw' => $row,
        ];
    }

    /** @return array<string,mixed> */
    private function normalizeDetail(array $row): array
    {
        $info = $this->normalizeInfo((array) ($row['payout_info'] ?? []));

        $escrow = collect($row['escrow_list'] ?? [])
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'order_sn' => (string) ($item['order_sn'] ?? ''),
                'escrow_amount' => $this->numberOrNull($item['escrow_amount'] ?? null),
                'currency' => $item['currency'] ?? null,
                'raw' => $item,
            ])
            ->values()
            ->all();

        return [
            'payout_info' => Arr::except($info, ['raw']),
            'escrow_list' => $escrow,
            'offline_adjustment_list' => is_array($row['offline_adjustment_list'] ?? null)
                ? array_values($row['offline_adjustment_list'])
                : [],
            'raw_response' => $row,
        ];
    }

    private function assertShopee(Store $store): void
    {
        $code = strtolower((string) ($store->channel()->value('code') ?? ''));
        if (! in_array($code, self::SHOPEE_CODES, true)) {
            throw new RuntimeException('Modul payout hanya tersedia untuk toko Shopee.');
        }

        try {
            if (! $store->is_active || $store->connection_status !== 'CONNECTED') {
                throw new RuntimeException('Toko Shopee belum terhubung. Hubungkan toko terlebih dahulu sebelum mengambil payout.');
            }
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            throw new RuntimeException('Kredensial toko Shopee tidak dapat dibaca. Hubungkan ulang toko terlebih dahulu.');
        }
    }

    private function assertSuccessfulResponse(array $response): void
    {
        $error = $response['error'] ?? null;
        $code = $response['code'] ?? null;
        if (! empty($error) || ($code !== null && (string) $code !== '0')) {
            $identifier = (string) ($error ?? $code ?? 'unknown_error');
            $message = (string) ($response['message'] ?? $identifier);
            throw new RuntimeException("Shopee {$identifier}: {$message}");
        }
    }

    private function numberOrNull(mixed $value): mixed
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function timestamp(mixed $value): ?Carbon
    {
        if (! is_numeric($value) || (float) $value <= 0) {
            return null;
        }

        $seconds = (int) $value;
        if ($seconds > 10_000_000_000) {
            $seconds = (int) floor($seconds / 1000);
        }

        try {
            return Carbon::createFromTimestamp($seconds, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }

    /** @param array<string,mixed> $response */
    private function withoutMeta(array $response): array
    {
        return Arr::except($response, ['_meta']);
    }
}
