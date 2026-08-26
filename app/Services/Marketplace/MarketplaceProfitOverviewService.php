<?php

namespace App\Services\Marketplace;

use App\Models\ItemCostSnapshot;
use App\Models\MarketplaceAdsDaily;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\SkuMapping;
use App\Services\MarketplaceIssueService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MarketplaceProfitOverviewService
{
    private const EXCLUDED_ORDER_STATUSES = [
        'UNPAID',
        'CANCELLED',
        'BATAL',
        'IN_CANCEL',
        'RETURNED',
        'REFUND',
        'TO_RETURN',
        'RETURNING',
    ];

    /**
     * Laporan operasional profit yang memisahkan payout aktual dan estimasi.
     * Nilai tanpa sumber dana atau tanpa HPP tetap ditampilkan sebagai masalah
     * kualitas data, tetapi tidak ikut KPI profit.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function report(array $filters): array
    {
        $orders = $this->orders($filters)->get();
        [$mappingItemIds, $activeCosts] = $this->costLookups($orders);

        $rows = $orders
            ->map(fn (MarketplaceOrder $order) => $this->buildRow($order, $mappingItemIds, $activeCosts))
            ->filter()
            ->values();

        if (($filters['hpp_status'] ?? null) === 'mapped') {
            $rows = $rows->where('hpp_mapped', true)->values();
        } elseif (($filters['hpp_status'] ?? null) === 'empty') {
            $rows = $rows->where('hpp_mapped', false)->values();
        }

        $rows = $this->sortRows($rows, $filters['sort'] ?? null);
        $meta = $this->buildMeta($rows, $filters);

        return compact('rows', 'meta');
    }

    /** @param array<string, mixed> $filters */
    private function orders(array $filters): Builder
    {
        $query = MarketplaceOrder::query()
            ->with([
                'store:id,name,channel_id',
                'store.channel:id,code,name',
                'settlement',
                'incomeEstimate:id,marketplace_order_id,estimated_escrow_amount,estimated_payout_at,income_status,synced_at,status_description',
                'items:id,marketplace_order_id,item_name,model_sku,item_sku,marketplace_sku,external_sku,qty,price,mapping_status,internal_item_id,hpp_snapshot',
            ])
            ->where(function (Builder $builder) {
                $builder->whereNull('order_status')
                    ->orWhereNotIn('order_status', self::EXCLUDED_ORDER_STATUSES);
            });

        $query
            ->when($filters['store_id'] ?? null, fn (Builder $builder, $storeId) => $builder->where('store_id', $storeId))
            ->when($filters['status'] ?? null, fn (Builder $builder, $status) => $builder->where('order_status', $status))
            ->when($filters['order_date_from'] ?? null, fn (Builder $builder, $date) => $builder->whereDate('ordered_at', '>=', $date))
            ->when($filters['order_date_to'] ?? null, fn (Builder $builder, $date) => $builder->whereDate('ordered_at', '<=', $date));

        if (($filters['settlement_status'] ?? null) === 'cair') {
            $query->whereHas('settlement', fn (Builder $settlement) => $settlement->whereNotNull('settlement_time'));
        } elseif (($filters['settlement_status'] ?? null) === 'belum_cair') {
            $query->whereDoesntHave('settlement', fn (Builder $settlement) => $settlement->whereNotNull('settlement_time'));
        }

        if (! empty($filters['settlement_date_from']) || ! empty($filters['settlement_date_to'])) {
            $query->where(function (Builder $dateQuery) use ($filters) {
                $dateQuery
                    ->whereHas('settlement', function (Builder $settlement) use ($filters) {
                        $settlement->whereNotNull('settlement_time')
                            ->when($filters['settlement_date_from'] ?? null, fn (Builder $builder, $date) => $builder->whereDate('settlement_time', '>=', $date))
                            ->when($filters['settlement_date_to'] ?? null, fn (Builder $builder, $date) => $builder->whereDate('settlement_time', '<=', $date));
                    })
                    ->orWhere(function (Builder $estimateQuery) use ($filters) {
                        $estimateQuery
                            ->whereDoesntHave('settlement', fn (Builder $settlement) => $settlement->whereNotNull('settlement_time'))
                            ->whereHas('incomeEstimate', function (Builder $estimate) use ($filters) {
                                $estimate
                                    ->when($filters['settlement_date_from'] ?? null, fn (Builder $builder, $date) => $builder->whereDate('estimated_payout_at', '>=', $date))
                                    ->when($filters['settlement_date_to'] ?? null, fn (Builder $builder, $date) => $builder->whereDate('estimated_payout_at', '<=', $date));
                            });
                    });
            });
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('channel_order_id', 'like', "%{$search}%")
                    ->orWhereHas('items', function (Builder $items) use ($search) {
                        $items->where('item_name', 'like', "%{$search}%")
                            ->orWhere('model_sku', 'like', "%{$search}%")
                            ->orWhere('item_sku', 'like', "%{$search}%")
                            ->orWhere('marketplace_sku', 'like', "%{$search}%")
                            ->orWhere('external_sku', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /**
     * @param  Collection<int, MarketplaceOrder>  $orders
     * @return array{array<string, int>, Collection<int, float>}
     */
    private function costLookups(Collection $orders): array
    {
        $skus = $orders->flatMap(fn (MarketplaceOrder $order) => $order->items)
            ->map(fn (MarketplaceOrderItem $item) => $this->itemSku($item))
            ->filter()
            ->unique()
            ->values();

        $channelCodes = $orders
            ->map(fn (MarketplaceOrder $order) => strtolower((string) $order->store?->channel?->code))
            ->filter()
            ->unique()
            ->values();

        $mappings = SkuMapping::query()
            ->whereIn('marketplace_sku', $skus)
            ->where(function (Builder $builder) use ($channelCodes) {
                $builder->whereNull('channel_code')
                    ->orWhereIn('channel_code', $channelCodes);
            })
            ->get(['marketplace_sku', 'channel_code', 'item_id']);

        $mappingItemIds = [];
        foreach ($mappings as $mapping) {
            $channel = $mapping->channel_code ? strtolower((string) $mapping->channel_code) : '*';
            $mappingItemIds[$channel.'|'.$mapping->marketplace_sku] = (int) $mapping->item_id;
        }

        $itemIds = $mappings->pluck('item_id')
            ->merge($orders->flatMap(fn (MarketplaceOrder $order) => $order->items)->pluck('internal_item_id'))
            ->filter()
            ->unique()
            ->values();

        $activeCosts = ItemCostSnapshot::query()
            ->whereIn('item_id', $itemIds)
            ->active()
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->get(['id', 'item_id', 'unit_cost', 'snapshot_date'])
            ->groupBy('item_id')
            ->map(fn (Collection $snapshots) => (float) $snapshots->first()->unit_cost);

        return [$mappingItemIds, $activeCosts];
    }

    /**
     * @param  array<string, int>  $mappingItemIds
     * @param  Collection<int, float>  $activeCosts
     * @return array<string, mixed>|null
     */
    private function buildRow(MarketplaceOrder $order, array $mappingItemIds, Collection $activeCosts): ?array
    {
        $status = strtoupper((string) ($order->order_status ?: $order->status));
        if (in_array($status, self::EXCLUDED_ORDER_STATUSES, true)) {
            return null;
        }

        $settlement = $order->settlement;
        $estimate = $order->incomeEstimate;
        $channelCode = strtolower((string) $order->store?->channel?->code);
        $items = $order->items ?? collect();
        $hppTotal = 0.0;
        $hppMapped = $items->isNotEmpty();
        $itemDetails = [];

        foreach ($items as $item) {
            $sku = $this->itemSku($item);
            $qty = (int) $item->qty;
            $mappedItemId = $item->internal_item_id
                ?: ($mappingItemIds[$channelCode.'|'.$sku] ?? $mappingItemIds['*|'.$sku] ?? null);
            $snapshotHpp = (float) ($item->hpp_snapshot ?? 0);
            $hpp = $snapshotHpp > 0
                ? $snapshotHpp
                : (float) ($activeCosts->get((int) $mappedItemId) ?? 0);
            $mappingKnown = $snapshotHpp > 0
                || ! empty($item->internal_item_id)
                || ! empty($mappedItemId)
                || $item->mapping_status === MarketplaceIssueService::MAPPING_MAPPED;
            $itemReady = $qty > 0 && $mappingKnown && $hpp > 0;

            if ($itemReady) {
                $hppTotal += $hpp * $qty;
            } else {
                $hppMapped = false;
            }

            $itemDetails[] = [
                'sku' => $sku ?: 'No SKU',
                'qty' => $qty,
                'hpp_unit' => $hpp,
                'mapped' => $itemReady,
            ];
        }

        $orderRaw = $this->rawArray($order->raw_json);
        $settlementRaw = $this->rawArray($settlement?->raw_json);
        $raw = array_replace_recursive($orderRaw, $settlementRaw);
        if (! empty($orderRaw['items']) && empty($settlementRaw['items'])) {
            $raw['items'] = $orderRaw['items'];
        }

        $incomeDetails = is_array($raw['income_details'] ?? null) ? $raw['income_details'] : [];
        $baseAmount = $order->total_paid_customer !== null
            ? (float) $order->total_paid_customer
            : (float) ($order->total_amount ?? $order->subtotal_items ?? 0);
        $buyerPayment = $settlement?->buyer_payment_amount !== null
            ? (float) $settlement->buyer_payment_amount
            : (float) ($incomeDetails['buyer_total_amount'] ?? $baseAmount);
        $grossSales = (float) (
            $incomeDetails['cost_of_goods_sold']
            ?? $incomeDetails['order_selling_price']
            ?? $raw['cost_of_goods_sold']
            ?? $raw['order_selling_price']
            ?? $buyerPayment
        );

        $isActual = $settlement
            && $settlement->settlement_time !== null
            && $settlement->final_income !== null;
        $incomeAmount = null;
        $incomeSource = 'unavailable';
        $incomeSyncedAt = null;

        if ($isActual) {
            $incomeAmount = (float) $settlement->final_income;
            $incomeSource = 'actual_settlement';
            $incomeSyncedAt = $settlement->synced_at?->toISOString();
        } elseif ($estimate && $estimate->estimated_escrow_amount !== null) {
            // Nilai 0 adalah estimasi yang valid dan tidak boleh dianggap kosong.
            $incomeAmount = max(0.0, (float) $estimate->estimated_escrow_amount);
            $incomeSource = 'shopee_income_detail';
            $incomeSyncedAt = $estimate->synced_at?->toISOString();
        } else {
            $legacyEstimate = $this->legacyEstimate($settlementRaw);
            if ($legacyEstimate !== null) {
                $incomeAmount = $legacyEstimate;
                $incomeSource = 'shopee_income_detail_legacy';
                $incomeSyncedAt = $settlement?->synced_at?->toISOString();
            }
        }

        $incomeAvailable = $incomeAmount !== null;
        $profitEligible = $incomeAvailable && $hppMapped;
        $profitContribution = $profitEligible ? $incomeAmount - $hppTotal : null;
        $margin = $profitEligible && $grossSales > 0
            ? round($profitContribution / $grossSales * 100, 1)
            : null;

        $sellerDiscount = collect($orderRaw['items'] ?? [])->sum(function ($item) {
            $sellingPrice = (float) ($item['selling_price'] ?? 0);
            $discountedPrice = (float) ($item['discounted_price'] ?? 0);

            return max($sellingPrice - $discountedPrice, 0);
        });

        return [
            'id' => $settlement?->id,
            'channel_order_id' => $order->channel_order_id,
            'store' => $order->store ? [
                'id' => $order->store->id,
                'name' => $order->store->name,
                'channel' => $channelCode,
            ] : null,
            'order' => [
                'id' => $order->id,
                'order_status' => $order->order_status,
                'ordered_at' => $order->ordered_at?->toISOString(),
            ],
            'items' => $itemDetails,
            'buyer_payment_amount' => $buyerPayment,
            'gross_sales' => $grossSales,
            'final_income' => $incomeAmount,
            'income_available' => $incomeAvailable,
            'income_type' => $isActual ? 'actual' : 'estimate',
            'income_source' => $incomeSource,
            'income_source_label' => match ($incomeSource) {
                'actual_settlement' => 'Aktual',
                'shopee_income_detail' => 'Est. Shopee',
                'shopee_income_detail_legacy' => 'Est. Shopee (legacy)',
                default => 'Belum tersedia',
            },
            'income_synced_at' => $incomeSyncedAt,
            'estimated_payout_at' => ! $isActual ? $estimate?->estimated_payout_at?->toISOString() : null,
            'income_status' => ! $isActual ? $estimate?->income_status : null,
            'hpp_total' => $hppTotal,
            'hpp_mapped' => $hppMapped,
            'profit_eligible' => $profitEligible,
            'profit_contribution' => $profitContribution,
            // Kompatibilitas frontend lama; sekarang artinya profit kontribusi
            // sebelum biaya iklan periode, bukan biaya iklan manual per order.
            'profit_gross' => $profitContribution,
            'profit_net' => $profitContribution,
            'margin_pct' => $margin,
            'settlement_time' => $settlement?->settlement_time?->toISOString(),
            'payout_date' => $isActual
                ? $settlement->settlement_time?->toISOString()
                : $estimate?->estimated_payout_at?->toISOString(),
            'data_quality_status' => ! $hppMapped
                ? 'missing_hpp'
                : ($incomeAvailable ? 'ready' : 'missing_income'),
            'raw_json' => $raw,
            'commission_fee' => (float) ($settlement?->commission_fee ?? 0),
            'service_fee' => (float) ($settlement?->service_fee ?? 0),
            'transaction_fee' => (float) ($settlement?->transaction_fee ?? 0),
            'activity_fee' => (float) ($settlement?->activity_fee ?? 0),
            'seller_voucher' => (float) ($settlement?->seller_voucher ?? 0),
            'seller_coin_cash_back' => (float) ($settlement?->seller_coin_cash_back ?? 0),
            'shipping_fee_subsidy' => (float) ($settlement?->shipping_fee_subsidy ?? 0),
            'seller_discount' => (float) $sellerDiscount,
        ];
    }

    /** @param Collection<int, array<string, mixed>> $rows */
    private function sortRows(Collection $rows, ?string $sort): Collection
    {
        [$field, $direction] = match ($sort) {
            'margin_asc' => ['margin_pct', 'asc'],
            'margin_desc' => ['margin_pct', 'desc'],
            'profit_asc' => ['profit_contribution', 'asc'],
            'profit_desc' => ['profit_contribution', 'desc'],
            'date_asc' => ['payout_date', 'asc'],
            default => ['payout_date', 'desc'],
        };

        return $rows->sort(function (array $left, array $right) use ($field, $direction) {
            $leftValue = data_get($left, $field) ?? ($field === 'payout_date' ? data_get($left, 'order.ordered_at') : null);
            $rightValue = data_get($right, $field) ?? ($field === 'payout_date' ? data_get($right, 'order.ordered_at') : null);

            if ($leftValue === null && $rightValue === null) {
                return 0;
            }
            if ($leftValue === null) {
                return 1;
            }
            if ($rightValue === null) {
                return -1;
            }

            $comparison = $leftValue <=> $rightValue;

            return $direction === 'asc' ? $comparison : -$comparison;
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function buildMeta(Collection $rows, array $filters): array
    {
        $eligibleRows = $rows->where('profit_eligible', true)->values();
        $kpiOmzet = (float) $eligibleRows->sum('gross_sales');
        $kpiHpp = (float) $eligibleRows->sum('hpp_total');
        $kpiNet = (float) $eligibleRows->sum('final_income');
        $profitContribution = (float) $eligibleRows->sum('profit_contribution');
        $marginContribution = $kpiOmzet > 0 ? round($profitContribution / $kpiOmzet * 100, 1) : null;
        $readyCount = $eligibleRows->count();
        $avgContribution = $readyCount > 0 ? round($profitContribution / $readyCount) : 0;

        // Iklan harian adalah overhead periode, bukan atribut order. Karena itu
        // biaya ini hanya diterapkan jika filter masih merepresentasikan seluruh
        // order toko pada periode, bukan subset status/search/kualitas.
        $adsApplicable = empty($filters['status'])
            && empty($filters['settlement_status'])
            && empty($filters['hpp_status'])
            && empty($filters['search']);
        [$adsFrom, $adsTo] = $this->adsDateRange($rows, $filters);
        $adsSpend = null;
        $adsTotal = null;
        $profitAfterAds = null;
        $marginAfterAds = null;
        $avgAfterAds = null;
        $vatPercent = (float) config('marketplace.ads_vat_percent', 11);

        if ($adsApplicable && $adsFrom && $adsTo) {
            $adsSpend = (float) MarketplaceAdsDaily::query()
                ->when($filters['store_id'] ?? null, fn (Builder $builder, $storeId) => $builder->where('store_id', $storeId))
                ->whereDate('date', '>=', $adsFrom)
                ->whereDate('date', '<=', $adsTo)
                ->sum('spend');
            $adsTotal = round($adsSpend * (1 + ($vatPercent / 100)), 2);
            $profitAfterAds = $profitContribution - $adsTotal;
            $marginAfterAds = $kpiOmzet > 0 ? round($profitAfterAds / $kpiOmzet * 100, 1) : null;
            $avgAfterAds = $readyCount > 0 ? round($profitAfterAds / $readyCount) : 0;
        }

        $lastSync = \App\Models\MarketplaceSyncLog::query()
            ->whereIn('action', ['sync_finance', 'sync_settlements', 'sync_income_details'])
            ->when($filters['store_id'] ?? null, fn (Builder $builder, $storeId) => $builder->where('store_id', $storeId))
            ->latest()
            ->value('created_at');

        return [
            'kpi_omzet' => $kpiOmzet,
            'kpi_hpp' => $kpiHpp,
            'kpi_net' => $kpiNet,
            'kpi_profit' => $profitContribution,
            'kpi_profit_contribution' => $profitContribution,
            'kpi_margin' => $marginContribution,
            'avg_profit' => $avgContribution,
            'kpi_ads_spend' => $adsSpend,
            'kpi_ads_total' => $adsTotal,
            'kpi_ads_vat_percent' => $vatPercent,
            'kpi_ads_applicable' => $adsApplicable,
            'kpi_profit_final' => $profitAfterAds,
            'kpi_margin_final' => $marginAfterAds,
            'avg_profit_final' => $avgAfterAds,
            'kpi_count' => $rows->count(),
            'kpi_ready_count' => $readyCount,
            'kpi_excluded_count' => $rows->count() - $readyCount,
            'kpi_missing_hpp_count' => $rows->where('hpp_mapped', false)->count(),
            'kpi_missing_income_count' => $rows->where('income_available', false)->count(),
            'kpi_actual_count' => $eligibleRows->where('income_type', 'actual')->count(),
            'kpi_estimated_count' => $eligibleRows->where('income_type', 'estimate')->count(),
            'ads_date_from' => $adsFrom,
            'ads_date_to' => $adsTo,
            'last_sync' => $lastSync?->toISOString(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $filters
     * @return array{?string, ?string}
     */
    private function adsDateRange(Collection $rows, array $filters): array
    {
        $from = $filters['order_date_from'] ?? null;
        $to = $filters['order_date_to'] ?? null;

        if (! $from || ! $to) {
            $dates = $rows->pluck('order.ordered_at')
                ->filter()
                ->map(fn (string $date) => \Carbon\Carbon::parse($date)->timezone(config('app.timezone'))->toDateString());
            $from = $from ?: $dates->min();
            $to = $to ?: $dates->max();
        }

        return [$from, $to];
    }

    private function itemSku(MarketplaceOrderItem $item): string
    {
        return trim((string) ($item->model_sku ?: $item->item_sku ?: $item->marketplace_sku ?: $item->external_sku));
    }

    /** @return array<string, mixed> */
    private function rawArray(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        return is_array($raw) ? $raw : [];
    }

    /** @param array<string, mixed> $raw */
    private function legacyEstimate(array $raw): ?float
    {
        $nested = (array) data_get($raw, '_income_detail', []);
        $hasNested = array_key_exists('estimated_escrow_amount', $nested);
        $hasTopLevel = array_key_exists('estimated_escrow_amount', $raw);

        if (! $hasNested && ! $hasTopLevel) {
            return null;
        }

        $value = $hasNested
            ? data_get($raw, '_income_detail.estimated_escrow_amount')
            : data_get($raw, 'estimated_escrow_amount');

        return is_numeric($value) ? max(0.0, (float) $value) : null;
    }
}
