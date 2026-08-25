<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceProfitReportService
{
    private const FEE_FIELDS = [
        'commission_fee',
        'service_fee',
        'transaction_fee',
        'affiliate_fee',
        'activity_fee',
        'shipping_insurance_fee',
        'escrow_tax',
    ];

    /**
     * Profit report menggunakan payout aktual, bukan estimasi omzet x rasio.
     * Order baru masuk ke angka profit jika quality gate Tahap 1 sudah ready.
     *
     * @return array<string, mixed>
     */
    public function report(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        $qualityCounts = $this->filteredOrders($filters)
            ->whereIn('order_status', MarketplaceFinancialDataQualityService::FINANCIAL_ELIGIBLE_ORDER_STATUSES)
            ->selectRaw('COALESCE(financial_data_status, ?) AS status, COUNT(*) AS total', ['unknown'])
            ->groupBy('financial_data_status')
            ->pluck('total', 'status')
            ->map(fn ($value) => (int) $value);

        $issueBreakdown = $this->filteredOrders($filters)
            ->whereIn('order_status', MarketplaceFinancialDataQualityService::FINANCIAL_ELIGIBLE_ORDER_STATUSES)
            ->where(function (Builder $query) {
                $query->where('financial_data_status', MarketplaceFinancialDataQualityService::ORDER_INCOMPLETE)
                    ->orWhereNull('financial_data_status');
            })
            ->selectRaw('COALESCE(financial_issue_reason, ?) AS reason, COUNT(*) AS total', ['unknown_reason'])
            ->groupBy('financial_issue_reason')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'reason' => (string) $row->reason,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $ordersQuery = $this->filteredOrders($filters)
            ->whereIn('order_status', MarketplaceFinancialDataQualityService::FINANCIAL_ELIGIBLE_ORDER_STATUSES)
            ->where('financial_data_status', MarketplaceFinancialDataQualityService::ORDER_READY)
            ->whereHas('settlement', function (Builder $query) {
                $query->where('data_status', MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE);
            })
            ->with([
                'store:id,name,channel_id',
                'store.channel:id,code,name',
                'settlement:id,store_id,order_id,channel_order_id,buyer_payment_amount,commission_fee,service_fee,transaction_fee,affiliate_fee,activity_fee,shipping_insurance_fee,escrow_tax,seller_voucher,seller_coin_cash_back,drc_adjustable_refund,final_income,settlement_time,ad_cost,data_status',
                'items:id,marketplace_order_id,order_id,item_name,item_name_snapshot,model_sku,item_sku,external_sku,qty,price,hpp_snapshot,data_status',
            ]);

        $orderRows = [];
        $storeRows = [];
        $dailyRows = [];
        $itemRows = [];

        $ordersQuery->chunkById(200, function ($orders) use (&$orderRows, &$storeRows, &$dailyRows, &$itemRows, $filters) {
            foreach ($orders as $order) {
                $settlement = $order->settlement;
                if (! $settlement) {
                    continue;
                }

                $items = $order->items->filter(function (MarketplaceOrderItem $item) {
                    return ($item->data_status ?? null) === 'valid'
                        && (float) ($item->hpp_snapshot ?? 0) > 0;
                })->values();

                if ($items->isEmpty()) {
                    continue;
                }

                $row = $this->buildOrderRow($order, $settlement, $items);
                $orderRows[] = $row;

                $storeKey = (string) $order->store_id;
                $storeRows[$storeKey] = $this->mergeAggregate(
                    $storeRows[$storeKey] ?? $this->emptyAggregate([
                        'store_id' => $order->store_id,
                        'store_name' => $order->store?->name ?? '-',
                        'channel' => $order->store?->channel?->code ?? '-',
                    ]),
                    $row,
                );

                $date = $this->dateKey($order, $settlement, $filters['date_basis']);
                $dailyKey = $date ?: 'undated';
                $dailyRows[$dailyKey] = $this->mergeAggregate(
                    $dailyRows[$dailyKey] ?? $this->emptyAggregate(['date' => $date]),
                    $row,
                );

                $this->mergeItemRows($itemRows, $order, $settlement, $items, $row);
            }
        });

        $summary = $this->emptyAggregate();
        foreach ($orderRows as $row) {
            $summary = $this->mergeAggregate($summary, $row);
        }

        $summary['order_count'] = count($orderRows);
        $summary['margin_pct'] = $summary['gross_sales'] > 0
            ? round(($summary['operating_profit'] / $summary['gross_sales']) * 100, 2)
            : 0.0;

        usort($orderRows, fn (array $left, array $right) => strcmp((string) ($right['ordered_at'] ?? ''), (string) ($left['ordered_at'] ?? '')));
        $this->finalizeAggregates($storeRows);
        $this->finalizeAggregates($dailyRows);
        $this->finalizeAggregates($itemRows);

        ksort($dailyRows);

        return [
            'filters' => $filters,
            'summary' => $summary,
            'quality' => [
                'total' => (int) $qualityCounts->sum(),
                'ready' => (int) ($qualityCounts['ready'] ?? 0),
                'incomplete' => (int) ($qualityCounts['incomplete'] ?? 0),
                'not_applicable' => (int) ($qualityCounts['not_applicable'] ?? 0),
                'unknown' => (int) ($qualityCounts['unknown'] ?? 0),
                'issues' => $issueBreakdown,
            ],
            'orders' => $orderRows,
            'stores' => array_values($storeRows),
            'daily' => array_values($dailyRows),
            'items' => array_values($itemRows),
        ];
    }

    private function normalizeFilters(array $filters): array
    {
        return [
            'store_id' => ! empty($filters['store_id']) ? (int) $filters['store_id'] : null,
            'date_basis' => in_array($filters['date_basis'] ?? 'ordered_at', ['ordered_at', 'settlement_time'], true)
                ? $filters['date_basis']
                : 'ordered_at',
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
        ];
    }

    private function filteredOrders(array $filters): Builder
    {
        $query = MarketplaceOrder::query()
            ->when($filters['store_id'], fn (Builder $builder, $storeId) => $builder->where('store_id', $storeId));

        if ($filters['date_basis'] === 'settlement_time') {
            $query->whereHas('settlement', function (Builder $settlement) use ($filters) {
                $settlement
                    ->when($filters['date_from'], fn (Builder $builder, $date) => $builder->whereDate('settlement_time', '>=', $date))
                    ->when($filters['date_to'], fn (Builder $builder, $date) => $builder->whereDate('settlement_time', '<=', $date));
            });
        } else {
            $query
                ->when($filters['date_from'], fn (Builder $builder, $date) => $builder->whereDate('ordered_at', '>=', $date))
                ->when($filters['date_to'], fn (Builder $builder, $date) => $builder->whereDate('ordered_at', '<=', $date));
        }

        return $query;
    }

    private function buildOrderRow(MarketplaceOrder $order, MarketplaceOrderSettlement $settlement, $items): array
    {
        $grossSales = $settlement->buyer_payment_amount !== null
            ? (float) $settlement->buyer_payment_amount
            : (float) ($order->total_amount ?? $order->total_paid_customer ?? $order->subtotal_items ?? 0);
        $payout = (float) ($settlement->final_income ?? 0);
        $hpp = (float) $items->sum(fn (MarketplaceOrderItem $item) => (float) $item->hpp_snapshot * max((int) $item->qty, 0));
        $fees = (float) collect(self::FEE_FIELDS)->sum(fn (string $field) => (float) ($settlement->{$field} ?? 0));
        $sellerDiscount = (float) ($settlement->seller_voucher ?? 0) + (float) ($settlement->seller_coin_cash_back ?? 0);
        $refund = (float) ($settlement->drc_adjustable_refund ?? 0);
        $adCost = (float) ($settlement->ad_cost ?? 0);
        $grossProfit = $payout - $hpp;
        $operatingProfit = $grossProfit - $adCost;

        return [
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id ?: $order->external_order_id,
            'store_name' => $order->store?->name ?? '-',
            'channel' => $order->store?->channel?->code ?? '-',
            'order_status' => $order->order_status ?: $order->status,
            'ordered_at' => optional($order->ordered_at)->toDateString(),
            'settlement_time' => optional($settlement->settlement_time)->toDateString(),
            'gross_sales' => $grossSales,
            'seller_discount' => $sellerDiscount,
            'marketplace_fees' => $fees,
            'refund' => $refund,
            'payout' => $payout,
            'hpp' => $hpp,
            'ad_cost' => $adCost,
            'gross_profit' => $grossProfit,
            'operating_profit' => $operatingProfit,
            'margin_pct' => $grossSales > 0 ? round(($operatingProfit / $grossSales) * 100, 2) : 0.0,
            'qty' => (int) $items->sum(fn (MarketplaceOrderItem $item) => max((int) $item->qty, 0)),
        ];
    }

    private function emptyAggregate(array $extra = []): array
    {
        return array_merge([
            'order_count' => 0,
            'qty' => 0,
            'gross_sales' => 0.0,
            'seller_discount' => 0.0,
            'marketplace_fees' => 0.0,
            'refund' => 0.0,
            'payout' => 0.0,
            'hpp' => 0.0,
            'ad_cost' => 0.0,
            'gross_profit' => 0.0,
            'operating_profit' => 0.0,
            'margin_pct' => 0.0,
        ], $extra);
    }

    private function mergeAggregate(array $aggregate, array $row): array
    {
        $aggregate['order_count'] += 1;
        foreach (['qty', 'gross_sales', 'seller_discount', 'marketplace_fees', 'refund', 'payout', 'hpp', 'ad_cost', 'gross_profit', 'operating_profit'] as $field) {
            $aggregate[$field] += (float) ($row[$field] ?? 0);
        }

        return $aggregate;
    }

    private function finalizeAggregates(array &$rows): void
    {
        foreach ($rows as &$row) {
            $row['margin_pct'] = $row['gross_sales'] > 0
                ? round(($row['operating_profit'] / $row['gross_sales']) * 100, 2)
                : 0.0;
        }
    }

    private function dateKey(MarketplaceOrder $order, MarketplaceOrderSettlement $settlement, string $basis): ?string
    {
        return $basis === 'settlement_time'
            ? optional($settlement->settlement_time)->toDateString()
            : optional($order->ordered_at)->toDateString();
    }

    private function mergeItemRows(array &$itemRows, MarketplaceOrder $order, MarketplaceOrderSettlement $settlement, $items, array $orderRow): void
    {
        $totalLineSales = (float) $items->sum(fn (MarketplaceOrderItem $item) => max((float) ($item->price ?? 0), 0) * max((int) $item->qty, 0));
        $itemCount = max($items->count(), 1);

        foreach ($items as $item) {
            $lineSales = max((float) ($item->price ?? 0), 0) * max((int) $item->qty, 0);
            $share = $totalLineSales > 0 ? $lineSales / $totalLineSales : 1 / $itemCount;
            $key = (string) ($item->internal_item_id ?: $item->model_sku ?: $item->item_sku ?: $item->external_sku ?: $item->id);
            $itemRows[$key] ??= $this->emptyAggregate([
                'item_key' => $key,
                'item_name' => $item->item_name ?: $item->item_name_snapshot ?: '-',
                'sku' => $item->model_sku ?: $item->item_sku ?: $item->external_sku ?: '-',
            ]);

            $itemRows[$key]['qty'] += max((int) $item->qty, 0);
            $itemRows[$key]['gross_sales'] += $orderRow['gross_sales'] * $share;
            $itemRows[$key]['seller_discount'] += $orderRow['seller_discount'] * $share;
            $itemRows[$key]['marketplace_fees'] += $orderRow['marketplace_fees'] * $share;
            $itemRows[$key]['refund'] += $orderRow['refund'] * $share;
            $itemRows[$key]['payout'] += $orderRow['payout'] * $share;
            $itemRows[$key]['hpp'] += (float) $item->hpp_snapshot * max((int) $item->qty, 0);
            $itemRows[$key]['ad_cost'] += $orderRow['ad_cost'] * $share;
            $itemRows[$key]['gross_profit'] += ($orderRow['payout'] * $share) - ((float) $item->hpp_snapshot * max((int) $item->qty, 0));
            $itemRows[$key]['operating_profit'] += (($orderRow['payout'] - $orderRow['ad_cost']) * $share) - ((float) $item->hpp_snapshot * max((int) $item->qty, 0));
            $itemRows[$key]['order_count'] += 1;
        }
    }
}
