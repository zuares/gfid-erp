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
        '450' => ['sign' => -1, 'kind' => 'ad_cost'], // PAID_ADS_CHARGE
        '451' => ['sign' => 1, 'kind' => 'ad_refund'], // PAID_ADS_REFUND
        'SPM_DEDUCT' => ['sign' => -1, 'kind' => 'ad_wallet_topup'],
    ];

    private const TYPE_ALIASES = [
        '450' => ['450', 'paid_ads_charge', 'paid-ads-charge'],
        '451' => ['451', 'paid_ads_refund', 'paid-ads-refund'],
        'SPM_DEDUCT' => ['spm_deduct', 'spm-deduct'],
    ];

    public function __construct(private readonly ShopeeChannel $shopee)
    {
    }

    /**
     * Sync wallet movements related to ads. Shopee can return the general
     * wallet list even when transaction_type=450 is sent, so rows are
     * classified locally instead of trusting the request filter.
     *
     * @return array{created:int,updated:int,skipped:int,pages:int,requests:int,errors:int,ad_usage:int,ad_topup:int}
     */
    public function sync(Store $store, Carbon $from, Carbon $to, int $pageSize = 100): array
    {
        if ($pageSize < 1 || $pageSize > 100) {
            throw new RuntimeException('page size harus berada di antara 1 dan 100.');
        }
        if ($to->lt($from)) {
            throw new RuntimeException('periode sync wallet tidak valid: tanggal akhir sebelum tanggal mulai.');
        }

        $totals = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'pages' => 0,
            'requests' => 0,
            'errors' => 0,
            'ad_usage' => 0,
            'ad_topup' => 0,
        ];
        $periodTo = $to->copy()->endOfDay();
        $windowFrom = $from->copy()->startOfDay();

        while ($windowFrom->lte($periodTo)) {
            $windowTo = $windowFrom->copy()->addDays(14)->endOfDay();
            if ($windowTo->gt($periodTo)) {
                $windowTo = $periodTo->copy();
            }

            $result = $this->syncWindow($store, $windowFrom, $windowTo, $pageSize);
            foreach ($totals as $key => $value) {
                $totals[$key] += (int) ($result[$key] ?? 0);
            }

            $windowFrom = $windowTo->copy()->addSecond()->startOfDay();
        }

        return $totals;
    }

    private function syncWindow(Store $store, Carbon $from, Carbon $to, int $pageSize): array
    {
        $totals = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'pages' => 0, 'requests' => 0, 'errors' => 0, 'ad_usage' => 0, 'ad_topup' => 0];
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
                null,
                null,
                null,
            );

            if (! empty($result['error'])) {
                throw new RuntimeException(sprintf(
                    'Shopee wallet transaction gagal: %s',
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

                $canonicalType = $this->classifyTransactionType($row);
                if ($canonicalType === null) {
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

                $meta = self::TRANSACTION_TYPES[$canonicalType];
                $date = Carbon::createFromTimestamp($createdTime, config('app.timezone'));
                $payload = [
                    'transaction_type' => (string) (data_get($row, 'transaction_type') ?? $canonicalType),
                    'amount' => round($amount * $meta['sign'], 2),
                    'money_flow' => $meta['sign'] < 0 ? 'MONEY_OUT' : 'MONEY_IN',
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
                if ($meta['kind'] === 'ad_wallet_topup') {
                    $totals['ad_topup']++;
                } else {
                    $totals['ad_usage']++;
                }
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

    private function classifyTransactionType(array $row): ?string
    {
        $rawType = data_get($row, 'transaction_type') ?? data_get($row, 'type');
        if ($rawType === null || trim((string) $rawType) === '') {
            return null;
        }

        $normalized = strtolower(trim((string) $rawType));
        foreach (self::TYPE_ALIASES as $canonicalType => $aliases) {
            if (in_array($normalized, $aliases, true)) {
                return $canonicalType;
            }
        }

        return null;
    }
}
