<?php

namespace App\Services\Marketplace;

use App\Domain\Marketplace\Finance\Enums\SettlementStatus;
use App\Models\MarketplaceFinanceSettlement;
use App\Models\Store;
use App\Services\Marketplace\Finance\MarketplaceSettlementSyncService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use RuntimeException;

/**
 * Reads and synchronizes Shopee wallet withdrawals.
 * Wallet withdrawal data is persisted as a Finance settlement, but it is not
 * treated as bank-received until an independent bank reconciliation confirms it.
 */
class PayoutService
{
    private const SHOPEE_CODES = ['shopee', 'shp'];
    private const KNOWN_NON_WITHDRAWAL_TYPES = [
        '101', '102', '203', 'paid_ads_charge', 'paid-ads-charge',
        'escrow_verified_add', 'escrow_verified_minus',
    ];

    public function __construct(
        protected MarketplaceApiGateway $gateway,
        protected MarketplaceSettlementSyncService $settlementSync,
    ) {}

    /**
     * Read payout withdrawals that have already been synchronized locally.
     * The wallet transaction is not treated as bank-received until a separate
     * bank reconciliation changes the Finance settlement status.
     *
     * @return array{items:array<int,array<string,mixed>>,more:bool,next_cursor:string,page_size:int,total:int,last_synced_at:?string}
     */
    public function storedInfo(
        Store $store,
        Carbon $from,
        Carbon $to,
        string $cursor = '',
        int $pageSize = 100,
    ): array {
        $this->assertShopeeChannel($store);

        $pageSize = min(100, max(1, $pageSize));
        $offset = $this->walletOffset($cursor);
        $settlements = MarketplaceFinanceSettlement::query()
            ->where('store_id', $store->id)
            ->whereIn('channel', self::SHOPEE_CODES)
            ->whereDate('settlement_date', '>=', $from->toDateString())
            ->whereDate('settlement_date', '<=', $to->toDateString())
            ->orderByDesc('settlement_date')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (MarketplaceFinanceSettlement $settlement): bool => $settlement->isWalletWithdrawal())
            ->values();

        $items = $settlements->slice($offset, $pageSize)
            ->map(fn (MarketplaceFinanceSettlement $settlement): array => $this->normalizeStoredSettlement($settlement))
            ->values()
            ->all();

        return [
            'items' => $items,
            'more' => $offset + $pageSize < $settlements->count(),
            'next_cursor' => $offset + $pageSize < $settlements->count() ? (string) ($offset + $pageSize) : '',
            'page_size' => $pageSize,
            'total' => $settlements->count(),
            'last_synced_at' => $settlements->max('updated_at')?->toIso8601String(),
        ];
    }

    /**
     * Persist wallet withdrawals as Finance settlements only. This does not
     * confirm bank receipt and does not create a journal.
     */
    public function syncWallet(Store $store, Carbon $from, Carbon $to, int $pageSize = 100): array
    {
        $this->assertConnectedShopee($store);

        return $this->settlementSync->syncWalletPayouts($store, $from, $to, $pageSize);
    }

    /**
     * The wallet endpoint uses an offset in page_no. The UI keeps calling
     * this field a cursor so the existing pagination contract stays intact.
     *
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
        $offset = $this->walletOffset($cursor);
        $response = $this->gateway->getWalletTransactionList(
            $store,
            $offset,
            $pageSize,
            $from->timestamp,
            $to->timestamp,
            'MONEY_OUT',
            '201',
            null,
            'wallet_withdrawals',
        );
        $this->assertSuccessfulResponse($response);

        $root = $this->responseRoot($response);
        $rows = $this->withdrawalRows($root);
        $more = (bool) ($root['more'] ?? false);
        $nextOffset = $offset + $pageSize;

        return [
            'items' => collect($rows)
                ->filter(static fn (mixed $row): bool => is_array($row))
                ->map(fn (array $row): array => $this->normalizeWalletInfo($row))
                ->values()
                ->all(),
            'more' => $more,
            'next_cursor' => $more ? (string) $nextOffset : '',
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
        $offset = ($pageNo - 1) * $pageSize;
        $response = $this->gateway->getWalletTransactionList(
            $store,
            $offset,
            $pageSize,
            $from->timestamp,
            $to->timestamp,
            'MONEY_OUT',
            '201',
            null,
            'wallet_withdrawals',
        );
        $this->assertSuccessfulResponse($response);

        $root = $this->responseRoot($response);
        $rows = $this->withdrawalRows($root);

        return [
            'items' => collect($rows)
                ->filter(static fn (mixed $row): bool => is_array($row))
                ->map(fn (array $row): array => $this->normalizeWalletDetail($row))
                ->values()
                ->all(),
            'more' => (bool) ($root['more'] ?? false),
            'page_no' => $pageNo,
            'page_size' => $pageSize,
            'raw_response' => $this->withoutMeta($response),
        ];
    }

    /** @return array<string,mixed> */
    private function responseRoot(array $response): array
    {
        $root = data_get($response, 'response');

        return is_array($root) ? $root : $response;
    }

    /** @return array<int,array<string,mixed>> */
    private function withdrawalRows(array $root): array
    {
        $rows = $root['transaction_list'] ?? [];
        if (! is_array($rows)) {
            return [];
        }
        if (! array_is_list($rows) && (
            array_key_exists('transaction_id', $rows)
            || array_key_exists('withdraw_id', $rows)
            || array_key_exists('amount', $rows)
        )) {
            $rows = [$rows];
        }

        return collect($rows)
            ->filter(fn (mixed $row): bool => is_array($row) && $this->isWithdrawal($row))
            ->values()
            ->all();
    }

    private function isWithdrawal(array $row): bool
    {
        $type = strtolower(str_replace('-', '_', trim((string) ($row['transaction_type'] ?? $row['type'] ?? ''))));
        if ($type === '') {
            return true;
        }
        if (in_array($type, array_map(
            static fn (string $value): string => str_replace('-', '_', $value),
            self::KNOWN_NON_WITHDRAWAL_TYPES
        ), true)) {
            return false;
        }

        // The request is already filtered by Shopee to withdrawal type 201.
        // Keep unknown labels for forward compatibility with new withdrawal
        // labels, while explicitly excluding known wallet outflows.
        return true;
    }

    private function walletOffset(string $cursor): int
    {
        return max(0, is_numeric($cursor) ? (int) $cursor : 0);
    }

    /** @return array<string,mixed> */
    private function normalizeWalletInfo(array $row): array
    {
        $amount = $this->numberOrNull(
            $row['amount']
                ?? $row['transaction_amount']
                ?? $row['withdrawal_amount']
                ?? $row['net_amount']
        );
        $amount = $amount === null ? null : abs($amount);
        $rawTime = $row['create_time'] ?? $row['transaction_time'] ?? null;
        $transactionId = $row['transaction_id'] ?? $row['withdraw_id'] ?? $row['withdrawal_id'] ?? null;

        return [
            'from_currency' => $row['currency'] ?? 'IDR',
            'payout_currency' => $row['currency'] ?? 'IDR',
            'from_amount' => $amount,
            'payout_amount' => $amount,
            'exchange_rate' => null,
            'payout_time' => is_numeric($rawTime) ? (int) $rawTime : null,
            'payout_at' => $this->timestamp($rawTime)?->toIso8601String(),
            'pay_service' => 'Shopee Wallet',
            'payee_id' => $transactionId,
            'encrypted_payout_id' => $transactionId,
            'transaction_id' => $transactionId,
            'transaction_type' => $row['transaction_type'] ?? $row['type'] ?? null,
            'status' => $row['status'] ?? null,
            'reason' => $row['reason'] ?? null,
            'raw' => $row,
        ];
    }

    /** @return array<string,mixed> */
    private function normalizeWalletDetail(array $row): array
    {
        return [
            'payout_info' => Arr::except($this->normalizeWalletInfo($row), ['raw']),
            'escrow_list' => [],
            'offline_adjustment_list' => [],
            'raw_response' => $row,
        ];
    }

    private function assertShopee(Store $store): void
    {
        $this->assertShopeeChannel($store);

        try {
            if (! $store->is_active || $store->connection_status !== 'CONNECTED') {
                throw new RuntimeException('Toko Shopee belum terhubung. Hubungkan toko terlebih dahulu sebelum mengambil payout.');
            }
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            throw new RuntimeException('Kredensial toko Shopee tidak dapat dibaca. Hubungkan ulang toko terlebih dahulu.');
        }
    }

    private function assertShopeeChannel(Store $store): void
    {
        $code = strtolower((string) ($store->channel()->value('code') ?? ''));
        if (! in_array($code, self::SHOPEE_CODES, true)) {
            throw new RuntimeException('Modul payout hanya tersedia untuk toko Shopee.');
        }
    }

    private function assertConnectedShopee(Store $store): void
    {
        $this->assertShopee($store);
    }

    /** @return array<string,mixed> */
    private function normalizeStoredSettlement(MarketplaceFinanceSettlement $settlement): array
    {
        $raw = is_array($settlement->raw_payload) ? $settlement->raw_payload : [];
        $rawTime = $raw['create_time'] ?? $raw['transaction_time'] ?? null;
        $amount = $this->numberOrNull(
            $raw['amount']
                ?? $raw['transaction_amount']
                ?? $raw['withdrawal_amount']
                ?? $raw['net_amount']
                ?? $settlement->amount
        );
        $amount = $amount === null ? null : abs($amount);
        $transactionId = (string) ($settlement->external_settlement_id ?: ($raw['transaction_id'] ?? $raw['withdraw_id'] ?? ''));
        $transactionType = $raw['transaction_type'] ?? $raw['type'] ?? null;

        return [
            'finance_settlement_id' => $settlement->id,
            'from_currency' => $raw['currency'] ?? $settlement->currency ?? 'IDR',
            'payout_currency' => $raw['currency'] ?? $settlement->currency ?? 'IDR',
            'from_amount' => $amount,
            'payout_amount' => $amount,
            'exchange_rate' => null,
            'payout_time' => is_numeric($rawTime) ? (int) $rawTime : null,
            'payout_at' => $this->timestamp($rawTime)?->toIso8601String(),
            'pay_service' => 'Shopee Wallet',
            'payee_id' => $transactionId,
            'encrypted_payout_id' => $transactionId,
            'transaction_id' => $transactionId,
            'transaction_type' => $transactionType,
            'status' => $settlement->status?->value ?? SettlementStatus::UNKNOWN->value,
            'shopee_status' => $raw['status'] ?? $raw['transaction_status'] ?? null,
            'reconciliation_status' => $settlement->status === SettlementStatus::RECEIVED ? 'received' : 'bank_unverified',
            'stored_at' => $settlement->updated_at?->toIso8601String(),
            'reason' => $raw['reason'] ?? null,
            'raw' => $raw,
        ];
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
