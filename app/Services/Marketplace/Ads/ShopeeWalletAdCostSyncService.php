<?php

namespace App\Services\Marketplace\Ads;

use App\Models\MarketplaceAdWalletTransaction;
use App\Models\Store;
use App\Services\Channels\Shopee\ShopeeChannel;
use Carbon\Carbon;
use RuntimeException;

class ShopeeWalletAdCostSyncService
{
    private const TRANSACTION_TYPES = [
        '450' => -1, // PAID_ADS_CHARGE
        '451' => 1,  // PAID_ADS_REFUND
    ];

    private const TYPE_ALIASES = [
        '450' => ['450', 'paid_ads_charge', 'paid-ads-charge'],
        '451' => ['451', 'paid_ads_refund', 'paid-ads-refund'],
    ];

    public function __construct(private readonly ShopeeChannel $shopee)
    {
    }

    /**
     * Sync actual paid-ads wallet mutations. The endpoint accepts at most
     * fifteen calendar days, so longer periods are split into windows.
     *
     * @return array{created:int,updated:int,skipped:int,pages:int,requests:int,errors:int}
     */
    public function sync(Store $store, Carbon $from, Carbon $to, int $pageSize = 100): array
    {
        if ($pageSize < 1 || $pageSize > 100) {
            throw new RuntimeException('page size harus berada di antara 1 dan 100.');
        }

        $totals = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'pages' => 0,
            'requests' => 0,
            'errors' => 0,
        ];
        $periodTo = $to->copy()->endOfDay();
        $windowFrom = $from->copy()->startOfDay();

        while ($windowFrom->lte($periodTo)) {
            $windowTo = $windowFrom->copy()->addDays(14)->endOfDay();
            if ($windowTo->gt($periodTo)) {
                $windowTo = $periodTo->copy();
            }

            foreach (self::TRANSACTION_TYPES as $transactionType => $sign) {
                $result = $this->syncWindow(
                    $store,
                    $windowFrom,
                    $windowTo,
                    $pageSize,
                    $transactionType,
                    $sign,
                );

                foreach ($totals as $key => $value) {
                    $totals[$key] += (int) ($result[$key] ?? 0);
                }
            }

            $windowFrom = $windowTo->copy()->addSecond()->startOfDay();
        }

        return $totals;
    }

    private function syncWindow(
        Store $store,
        Carbon $from,
        Carbon $to,
        int $pageSize,
        string $transactionType,
        int $sign,
    ): array {
        $totals = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'pages' => 0, 'requests' => 0, 'errors' => 0];
        $pageNo = 0;

        do {
            $totals['requests']++;
            $result = $this->shopee->getWalletTransactionList(
                $store,
                $pageNo,
                $pageSize,
                $from->timestamp,
                $to->timestamp,
                null,
                $transactionType,
                null,
                null,
            );

            if (! empty($result['error'])) {
                throw new RuntimeException(sprintf(
                    'Shopee wallet transaction type %s gagal: %s',
                    $transactionType,
                    (string) ($result['message'] ?? $result['error']),
                ));
            }

            $rows = data_get($result, 'response.transaction_list');
            if (! is_array($rows)) {
                $rows = data_get($result, 'transaction_list', []);
            }
            $rows = $this->normalizeRows(is_array($rows) ? $rows : []);
            $totals['pages']++;

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    $totals['skipped']++;
                    continue;
                }

                // Some Shopee responses/proxies can return more than the
                // requested type. Never let a mixed response overwrite an
                // existing charge with a refund (or vice versa).
                if (! $this->matchesTransactionType($row, $transactionType)) {
                    $totals['skipped']++;
                    continue;
                }

                $transactionId = trim((string) (
                    data_get($row, 'transaction_id')
                    ?? data_get($row, 'id', '')
                ));
                $amountRaw = data_get($row, 'amount')
                    ?? data_get($row, 'transaction_amount')
                    ?? data_get($row, 'amount_value');
                $amount = $this->numericAmount($amountRaw);
                $createdTime = (int) data_get($row, 'create_time', 0);

                if ($transactionId === '' || $amount <= 0 || $createdTime <= 0) {
                    $totals['skipped']++;
                    continue;
                }

                $date = Carbon::createFromTimestamp($createdTime, config('app.timezone'));
                $payload = [
                    'transaction_type' => (string) (data_get($row, 'transaction_type') ?? $transactionType),
                    'amount' => round($amount * $sign, 2),
                    'money_flow' => $sign < 0 ? 'MONEY_OUT' : 'MONEY_IN',
                    'wallet_type' => data_get($row, 'wallet_type'),
                    'order_sn' => data_get($row, 'order_sn'),
                    'status' => data_get($row, 'status'),
                    'reason' => data_get($row, 'reason'),
                    'transaction_created_at' => $date,
                    'source_payload' => $row,
                ];

                $existing = MarketplaceAdWalletTransaction::query()
                    ->where('store_id', $store->id)
                    ->where('external_transaction_id', $transactionId)
                    ->first(['id']);

                MarketplaceAdWalletTransaction::updateOrCreate(
                    [
                        'store_id' => $store->id,
                        'external_transaction_id' => $transactionId,
                    ],
                    $payload,
                );

                $totals[$existing ? 'updated' : 'created']++;
            }

            $pageNo += count($rows);
            $more = (bool) data_get($result, 'response.more', data_get($result, 'more', false));
        } while ($more && count($rows) > 0);

        return $totals;
    }

    private function normalizeRows(array $rows): array
    {
        if (isset($rows['transaction_list']) && is_array($rows['transaction_list'])) {
            return $rows['transaction_list'];
        }

        if (! array_is_list($rows) && (
            array_key_exists('transaction_id', $rows)
            || array_key_exists('amount', $rows)
        )) {
            return [$rows];
        }

        return $rows;
    }

    private function numericAmount(mixed $value): float
    {
        if (is_numeric($value)) {
            return abs((float) $value);
        }

        return abs((float) str_replace(',', '', trim((string) $value)));
    }

    private function matchesTransactionType(array $row, string $requestedType): bool
    {
        $rawType = data_get($row, 'transaction_type') ?? data_get($row, 'type');
        if ($rawType === null || trim((string) $rawType) === '') {
            // If Shopee omits the type, trust the server-side filter.
            return true;
        }

        return in_array(
            strtolower(trim((string) $rawType)),
            self::TYPE_ALIASES[$requestedType] ?? [$requestedType],
            true,
        );
    }
}
