<?php

namespace App\Services\Marketplace\TikTokShop;

use App\Models\MarketplaceTikTokFinancialTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Resolve advertising cost from TikTok wallet/finance transactions.
 *
 * TikTok exposes several cash movements in the same table. Only rows that
 * are explicitly advertising-related are included here; withdrawals,
 * settlements, transfers, and generic wallet outflows are excluded.
 */
class TikTokWalletAdCostService
{
    private const EXPLICIT_AD_TYPES = [
        'AD_BILLING',
        'AD_PAYMENT',
        'AD_CHARGE',
        'AD_REFUND',
        'PAID_ADS_CHARGE',
        'PAID_ADS_REFUND',
        'TOP_UP_ADS',
    ];

    /**
     * @return Collection<string, float>
     */
    public function daily(array $storeIds, int|string|null $storeId, string $dateFrom, string $dateTo): Collection
    {
        return $this->rows($storeIds, $storeId, $dateFrom, $dateTo)
            ->groupBy(fn (array $row): string => $row['day'])
            ->map(fn (Collection $rows): float => round((float) $rows->sum('cost'), 2))
            ->sortKeys();
    }

    /**
     * @return Collection<string, float> keyed as YYYY-MM-DD|store_id
     */
    public function storeDaily(array $storeIds, int|string|null $storeId, string $dateFrom, string $dateTo): Collection
    {
        return $this->rows($storeIds, $storeId, $dateFrom, $dateTo)
            ->groupBy(fn (array $row): string => $row['day'].'|'.$row['store_id'])
            ->map(fn (Collection $rows): float => round((float) $rows->sum('cost'), 2))
            ->sortKeys();
    }

    /**
     * @return Collection<int, array{day:string,store_id:int,cost:float}>
     */
    private function rows(array $storeIds, int|string|null $storeId, string $dateFrom, string $dateTo): Collection
    {
        if (! Schema::hasTable('marketplace_tiktok_financial_transactions')) {
            return collect();
        }

        $storeIds = collect($storeIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($storeId !== null && $storeId !== 'all') {
            $selectedStoreId = (int) $storeId;
            $storeIds = in_array($selectedStoreId, $storeIds, true) ? [$selectedStoreId] : [];
        }

        if ($storeIds === []) {
            return collect();
        }

        $rows = MarketplaceTikTokFinancialTransaction::query()
            ->whereIn('store_id', $storeIds)
            ->whereBetween('transaction_created_at', [
                $dateFrom.' 00:00:00',
                $dateTo.' 23:59:59',
            ])
            ->get([
                'id',
                'store_id',
                'external_transaction_id',
                'transaction_type',
                'amount',
                'money_flow',
                'source',
                'adjustment_id',
                'transaction_created_at',
                'source_payload',
            ]);

        $seen = [];

        return $rows
            ->filter(function (MarketplaceTikTokFinancialTransaction $row) use (&$seen): bool {
                if (! $this->isAdvertisingRow($row)) {
                    return false;
                }

                // A charge can be present in both account and statement
                // feeds. Keep the first normalized representation only.
                $keys = [
                    $row->store_id.'|external|'.$row->external_transaction_id,
                ];
                if (filled($row->adjustment_id)) {
                    $keys[] = $row->store_id.'|adjustment|'.$row->adjustment_id;
                }

                foreach ($keys as $key) {
                    if (isset($seen[$key])) {
                        return false;
                    }
                }
                foreach ($keys as $key) {
                    $seen[$key] = true;
                }

                return true;
            })
            ->map(function (MarketplaceTikTokFinancialTransaction $row): array {
                $payload = is_array($row->source_payload) ? $row->source_payload : [];
                $type = strtoupper(trim((string) $row->transaction_type));
                $isRefund = str_contains($type, 'REFUND')
                    || str_contains($type, 'REVERS')
                    || strtoupper((string) $row->money_flow) === 'MONEY_IN';
                $cost = abs((float) $row->amount) * ($isRefund ? -1 : 1);

                return [
                    'day' => $row->transaction_created_at?->toDateString() ?? '',
                    'store_id' => (int) $row->store_id,
                    'cost' => round($cost, 2),
                    'source' => (string) $row->source,
                    'type' => $type,
                    'payload' => $payload,
                ];
            })
            ->filter(fn (array $row): bool => $row['day'] !== '')
            ->values();
    }

    private function isAdvertisingRow(MarketplaceTikTokFinancialTransaction $row): bool
    {
        $type = strtoupper(trim((string) $row->transaction_type));
        $payload = strtoupper(json_encode(
            is_array($row->source_payload) ? $row->source_payload : [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        ) ?: '');

        $isGmvAd = str_contains($type, 'GMV_PAYMENT_FOR_') && str_contains($type, 'AD');
        $isExplicitAdType = in_array($type, self::EXPLICIT_AD_TYPES, true);
        $payloadMentionsAds = str_contains($payload, 'ADVERTIS')
            || str_contains($payload, 'TIKTOK_AD')
            || str_contains($payload, 'TIKTOK ADS')
            || str_contains($payload, 'PAID_AD')
            || str_contains($payload, 'IKLAN')
            || (str_contains($payload, 'BILLING') && str_contains($payload, 'AD'));

        if ($isGmvAd || $isExplicitAdType) {
            return true;
        }

        // Generic PAYMENT is accepted only when the provider payload makes
        // the advertising context explicit. This avoids treating unrelated
        // wallet payments as advertising expense.
        return $type === 'PAYMENT' && $payloadMentionsAds;
    }
}
