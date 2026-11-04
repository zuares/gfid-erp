<?php

namespace App\Services\Marketplace\TikTokShop;

use App\Models\MarketplaceTikTokFinancialTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Read TikTok wallet settlement cash-ins for dashboard visibility.
 *
 * These movements are kept separate from order-level final_income because a
 * wallet settlement can represent the cash movement for orders already
 * present in the marketplace settlement table.
 */
class TikTokWalletPayoutService
{
    /**
     * @return Collection<string, float>
     */
    public function daily(array $storeIds, int|string|null $storeId, string $dateFrom, string $dateTo): Collection
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

        return MarketplaceTikTokFinancialTransaction::query()
            ->whereIn('store_id', $storeIds)
            ->where('source', 'withdrawals')
            ->where('transaction_type', 'SETTLE')
            ->whereIn('status', ['SETTLED', 'COMPLETED', 'SUCCESS', 'SUCCESSFUL', 'PAID'])
            ->where('money_flow', 'MONEY_IN')
            ->whereBetween('transaction_created_at', [
                $dateFrom.' 00:00:00',
                $dateTo.' 23:59:59',
            ])
            ->get(['store_id', 'external_transaction_id', 'amount', 'transaction_created_at'])
            ->groupBy(fn (MarketplaceTikTokFinancialTransaction $row): string => $row->transaction_created_at?->toDateString() ?? '')
            ->map(fn (Collection $rows): float => round((float) $rows->sum(fn ($row): float => abs((float) $row->amount)), 2))
            ->filter(fn (float $amount, string $day): bool => $day !== '' && $amount > 0)
            ->sortKeys();
    }
}
