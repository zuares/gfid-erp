<?php

namespace App\Services\Marketplace;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Small, server-side summary for the Marketplace Analytics page.
 *
 * The page should not download every order and recalculate financials in the
 * browser. This service uses the same verified settlement/HPP basis as the
 * owner profit report, but returns only aggregate rows.
 */
class MarketplaceAnalyticsSummaryService
{
    private const ESTIMATED_MARKETPLACE_FEE_RATE = 0.21;
    private const ADS_VAT_RATE = 0.11;

    private const MARKETPLACE_FEE_FIELDS = [
        'commission_fee',
        'service_fee',
        'transaction_fee',
        'shipping_insurance_fee',
        'escrow_tax',
    ];

    private const AFFILIATE_FEE_FIELDS = [
        'affiliate_fee',
        'activity_fee',
    ];

    public function __construct(private MarketplaceProfitReportService $profitReport)
    {
    }

    public function summary(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);
        $previous = $this->previousRange($filters);

        $currentFinancial = $this->financialAggregate($filters);
        $previousFinancial = $this->financialAggregate(array_merge($filters, $previous));
        $currentCash = $this->cashAggregate($filters);
        $previousCash = $this->cashAggregate(array_merge($filters, $previous));
        $currentUnsettledCash = $this->cashAggregate($filters, 'unsettled');
        $previousUnsettledCash = $this->cashAggregate(array_merge($filters, $previous), 'unsettled');
        $currentHpp = $this->hppAggregate($filters);
        $currentReturnHpp = $this->returnRefundHppAggregate($filters);
        $currentReturns = $this->returnRefundAggregate($filters);
        $currentProductQty = $this->productQuantityAggregate($filters);
        $currentOperational = $this->operationalAggregate($filters);
        $previousOperational = $this->operationalAggregate(array_merge($filters, $previous));
        $currentAds = $this->adsAggregate($filters);
        $previousAds = $this->adsAggregate(array_merge($filters, $previous));
        $quality = $this->qualitySummary($filters);
        $current = $this->withRates($this->applyAdCost(array_merge($currentFinancial, $currentCash, [
            'cash_unsettled_order_count' => $currentUnsettledCash['cash_order_count'],
            'cash_unsettled_gross_sales' => $currentUnsettledCash['cash_gross_sales'],
            'cash_unsettled_order_revenue' => $currentUnsettledCash['cash_order_revenue'],
            'hpp_total' => $currentHpp['hpp_total'],
            'hpp_settled' => $currentHpp['hpp_settled'],
            'hpp_unsettled' => $currentHpp['hpp_unsettled'],
            'hpp_shipped' => $currentHpp['hpp_shipped'],
            'hpp_return_refund' => $currentReturnHpp['total'],
            'hpp_return_refund_settled' => $currentReturnHpp['settled'],
            'hpp_return_refund_unsettled' => $currentReturnHpp['unsettled'],
            'return_refund_count' => $currentReturns['return_refund_count'],
            'return_refund_order_count' => $currentReturns['return_refund_order_count'],
            'return_refund_settled_order_count' => $currentReturns['return_refund_settled_order_count'],
            'return_refund_unsettled_order_count' => $currentReturns['return_refund_unsettled_order_count'],
            'return_refund_amount' => $currentReturns['return_refund_amount'],
            'product_qty' => $currentProductQty['total'],
            'product_qty_settled' => $currentProductQty['settled'],
            'product_qty_unsettled' => $currentProductQty['unsettled'],
            'product_qty_return_refund' => $currentProductQty['return_refund'],
        ], $currentOperational), $currentAds));
        $previousSnapshot = $this->withRates($this->applyAdCost(array_merge($previousFinancial, $previousCash, [
            'cash_unsettled_order_count' => $previousUnsettledCash['cash_order_count'],
            'cash_unsettled_gross_sales' => $previousUnsettledCash['cash_gross_sales'],
            'cash_unsettled_order_revenue' => $previousUnsettledCash['cash_order_revenue'],
        ], $previousOperational), $previousAds));

        return [
            'filters' => $filters,
            'current' => $current,
            'previous' => $previousSnapshot,
            'changes' => $this->changes($current, $previousSnapshot),
            'daily' => $this->mergeDaily($this->financialDaily($filters), $this->operationalDaily($filters), $this->adsDaily($filters)),
            'previous_daily' => $this->mergeDaily($this->financialDaily(array_merge($filters, $previous)), $this->operationalDaily(array_merge($filters, $previous)), $this->adsDaily(array_merge($filters, $previous))),
            'stores' => $this->storeSummary($filters),
            'quality' => $quality,
        ];
    }

    public function products(array $filters, int $limit = 100): array
    {
        $filters = $this->normalizeFilters($filters);
        $limit = max(1, min(200, $limit));
        $items = $this->profitReport->report([
            'store_id' => $filters['store_id'],
            'date_basis' => 'ordered_at',
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
        ])['items'];
        $adsCost = (float) ($this->adsAggregate($filters)['ad_cost'] ?? 0);
        $totalGrossSales = (float) collect($items)->sum(fn (array $row) => (float) ($row['gross_sales'] ?? 0));

        return collect($items)
            ->sortByDesc('gross_sales')
            ->take($limit)
            ->map(function (array $row) use ($adsCost, $totalGrossSales): array {
                $grossSales = (float) ($row['gross_sales'] ?? 0);
                $payout = (float) ($row['payout'] ?? 0);
                $hpp = (float) ($row['hpp'] ?? 0);
                $allocatedAdCost = $totalGrossSales > 0 ? $adsCost * ($grossSales / $totalGrossSales) : 0.0;
                $grossProfit = $payout - $hpp;
                $operatingProfit = $grossProfit - $allocatedAdCost;

                return [
                    'product_key' => (string) ($row['item_key'] ?? ''),
                    'product_name' => (string) ($row['item_name'] ?? '-'),
                    'sku' => (string) ($row['sku'] ?? '-'),
                    'qty' => (int) ($row['qty'] ?? 0),
                    'gross_sales' => round($grossSales, 2),
                    'payout' => round($payout, 2),
                    'hpp' => round($hpp, 2),
                    'ad_cost' => round($allocatedAdCost, 2),
                    'ad_cost_settlement' => round((float) ($row['ad_cost'] ?? 0), 2),
                    'gross_profit' => round($grossProfit, 2),
                    'operating_profit' => round($operatingProfit, 2),
                    'margin_pct' => $grossSales > 0 ? round(($operatingProfit / $grossSales) * 100, 2) : 0.0,
                ];
            })->values()->all();
    }

    /**
     * Return only settlement-complete orders for the lazy cash-detail drawer.
     * Keeping this separate from the summary prevents the dashboard from
     * loading every order until the user explicitly asks for the detail.
     */
    public function cashOrders(array $filters, int $page = 1, int $perPage = 50, string $settlement = 'settled'): array
    {
        $filters = $this->normalizeFilters($filters);
        $page = max(1, $page);
        $perPage = max(10, min(100, $perPage));
        $settlement = $settlement === 'unsettled' ? 'unsettled' : 'settled';
        $base = $settlement === 'settled' ? $this->cashBase($filters) : $this->unsettledBase($filters);

        $paginator = $base
            ->join('stores as st', 'st.id', '=', 'mo.store_id')
            ->select([
                'mo.id',
                'mo.channel_order_id',
                'mo.external_order_id',
                'mo.order_status',
                'mo.status',
                'mo.ordered_at',
                'mo.total_amount',
                'mo.total_paid_customer',
                'mo.subtotal_items',
                'mo.raw_json as order_raw_json',
                'mo.store_id',
                'st.name as store_name',
                'ms.data_status',
                'ms.settlement_time',
                'ms.buyer_payment_amount',
                'ms.seller_voucher',
                'ms.raw_json as settlement_raw_json',
                'ms.final_income',
                'ms.drc_adjustable_refund',
                'ms.commission_fee',
                'ms.service_fee',
                'ms.transaction_fee',
                'ms.affiliate_fee',
                'ms.activity_fee',
                'ms.shipping_insurance_fee',
                'ms.escrow_tax',
            ])
            ->orderByDesc('ms.settlement_time')
            ->orderByDesc('mo.ordered_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $marketplaceFee = fn ($row): float => collect(self::MARKETPLACE_FEE_FIELDS)
            ->sum(fn (string $field) => (float) ($row->{$field} ?? 0));
        $affiliateFee = fn ($row): float => collect(self::AFFILIATE_FEE_FIELDS)
            ->sum(fn (string $field) => (float) ($row->{$field} ?? 0));

        $data = collect($paginator->items())->map(function ($row) use ($marketplaceFee, $affiliateFee): array {
            $grossSales = $this->cashOrderGrossSales($row);
            $marketplaceFees = $marketplaceFee($row);
            $affiliateFees = $affiliateFee($row);
            $buyerPayment = collect([
                $row->buyer_payment_amount ?? null,
                $row->total_amount ?? null,
                $row->total_paid_customer ?? null,
                $row->subtotal_items ?? null,
            ])->map(fn ($value) => (float) $value)->first(fn (float $value) => $value > 0) ?? $grossSales;

            return [
                'order_id' => (int) $row->id,
                'channel_order_id' => (string) ($row->channel_order_id ?: $row->external_order_id ?: "#{$row->id}"),
                'store_id' => (int) $row->store_id,
                'store_name' => (string) ($row->store_name ?: 'Tanpa toko'),
                'status' => (string) ($row->order_status ?: $row->status ?: '-'),
                'settlement_status' => (string) ($row->data_status ?: 'not_settled'),
                'ordered_at' => $row->ordered_at,
                'settlement_time' => $row->settlement_time,
                'gross_sales' => round($grossSales, 2),
                'buyer_payment_amount' => round($buyerPayment, 2),
                'cash_payout' => round((float) ($row->final_income ?? 0), 2),
                'marketplace_fee' => round($marketplaceFees, 2),
                'affiliate_fee' => round($affiliateFees, 2),
                'commission_fee' => round((float) ($row->commission_fee ?? 0), 2),
                'service_fee' => round((float) ($row->service_fee ?? 0), 2),
                'transaction_fee' => round((float) ($row->transaction_fee ?? 0), 2),
                'shipping_insurance_fee' => round((float) ($row->shipping_insurance_fee ?? 0), 2),
                'escrow_tax' => round((float) ($row->escrow_tax ?? 0), 2),
                'affiliate_fee_raw' => round((float) ($row->affiliate_fee ?? 0), 2),
                'activity_fee' => round((float) ($row->activity_fee ?? 0), 2),
                'refund' => round((float) ($row->drc_adjustable_refund ?? 0), 2),
                'total_fees' => round($marketplaceFees + $affiliateFees, 2),
            ];
        })->values()->all();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'summary' => $this->cashAggregate($filters, $settlement),
        ];
    }

    private function cashOrderGrossSales($row): float
    {
        $decode = function ($value): array {
            if (is_array($value)) {
                return $value;
            }

            $decoded = json_decode((string) $value, true);
            return is_array($decoded) ? $decoded : [];
        };
        $settlement = $decode($row->settlement_raw_json ?? null);
        $order = $decode($row->order_raw_json ?? null);
        $sources = array_filter([
            $settlement['income_details'] ?? null,
            $settlement,
            $order,
        ], 'is_array');

        $grossSales = null;
        foreach ($sources as $source) {
            foreach (['order_selling_price', 'cost_of_goods_sold', 'order_discounted_price'] as $key) {
                $value = (float) ($source[$key] ?? 0);
                if ($value > 0) {
                    $grossSales = $value;
                    break 2;
                }
            }
        }

        if ($grossSales === null) {
            foreach ([
                $settlement['items'] ?? [],
                $order['item_list'] ?? [],
            ] as $items) {
                $total = collect(is_array($items) ? $items : [])->sum(function ($item): float {
                    if (! is_array($item)) {
                        return 0.0;
                    }

                    $price = (float) ($item['selling_price'] ?? $item['discounted_price'] ?? $item['model_discounted_price'] ?? 0);
                    $qty = (float) ($item['quantity_purchased'] ?? $item['model_quantity_purchased'] ?? $item['active_qty'] ?? 1);
                    return $price * max(1, $qty);
                });
                if ($total > 0) {
                    $grossSales = $total;
                    break;
                }
            }
        }

        if ($grossSales === null) {
            return round((float) ($row->buyer_payment_amount ?? $row->total_amount ?? $row->total_paid_customer ?? $row->subtotal_items ?? 0), 2);
        }

        $sellerVoucher = (float) ($row->seller_voucher ?? 0);
        if ($sellerVoucher <= 0) {
            foreach ($sources as $source) {
                $sellerVoucher = (float) ($source['voucher_from_seller'] ?? $source['seller_voucher'] ?? 0);
                if ($sellerVoucher > 0) {
                    break;
                }
            }
        }

        return round(max(0, $grossSales - $sellerVoucher), 2);
    }

    private function cashOrderRevenueAggregate(array $filters, string $settlement): float
    {
        $base = $settlement === 'unsettled' ? $this->unsettledBase($filters) : $this->cashBase($filters);
        $rows = $base
            ->select([
                'mo.total_amount',
                'mo.total_paid_customer',
                'mo.subtotal_items',
                'mo.raw_json as order_raw_json',
                'ms.buyer_payment_amount',
                'ms.seller_voucher',
                'ms.raw_json as settlement_raw_json',
            ])
            ->get();

        return round($rows->sum(fn ($row) => $this->cashOrderGrossSales($row)), 2);
    }

    private function hppAggregate(array $filters): array
    {
        $hppExpression = 'COALESCE(oi.hpp_snapshot, 0) * CASE WHEN oi.qty > 0 THEN oi.qty ELSE 0 END';
        $row = DB::table('marketplace_order_items as oi')
            ->join('marketplace_orders as mo', 'mo.id', '=', 'oi.marketplace_order_id')
            ->leftJoin('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
            ->where('oi.data_status', 'valid')
            ->where('oi.hpp_snapshot', '>', 0)
            ->whereNotNull('mo.ordered_at')
            ->whereRaw($this->isRevenueStatus())
            ->whereBetween('mo.ordered_at', [
                $filters['date_from'] . ' 00:00:00',
                $filters['date_to'] . ' 23:59:59',
            ])
            ->when($filters['store_id'], fn ($query, $storeId) => $query->where('mo.store_id', $storeId))
            ->selectRaw("SUM({$hppExpression}) AS hpp_total")
            ->selectRaw("SUM(CASE WHEN ms.data_status = ? THEN {$hppExpression} ELSE 0 END) AS hpp_settled", [MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE])
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(NULLIF(mo.order_status, ''), mo.status, '')) IN ('SHIPPED', 'READY_TO_HANDOVER', 'TO_CONFIRM_RECEIVE', 'COMPLETED') THEN {$hppExpression} ELSE 0 END) AS hpp_shipped")
            ->first();

        $rawFallback = $this->rawOrderHppAggregate($filters);
        $total = round((float) ($row->hpp_total ?? 0) + $rawFallback['total'], 2);
        $settled = round((float) ($row->hpp_settled ?? 0) + $rawFallback['settled'], 2);
        $shipped = round((float) ($row->hpp_shipped ?? 0) + $rawFallback['shipped'], 2);

        return [
            'hpp_total' => $total,
            'hpp_settled' => $settled,
            'hpp_unsettled' => round(max(0, $total - $settled), 2),
            'hpp_shipped' => $shipped,
        ];
    }

    private function rawOrderHppAggregate(array $filters): array
    {
        $orders = DB::table('marketplace_orders as mo')
            ->leftJoin('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
            ->whereNotNull('mo.ordered_at')
            ->whereNotNull('mo.raw_json')
            ->whereRaw($this->isRevenueStatus())
            ->whereBetween('mo.ordered_at', [
                $filters['date_from'] . ' 00:00:00',
                $filters['date_to'] . ' 23:59:59',
            ])
            ->when($filters['store_id'], fn ($query, $storeId) => $query->where('mo.store_id', $storeId))
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('marketplace_order_items as oi')
                    ->whereColumn('oi.marketplace_order_id', 'mo.id')
                    ->where('oi.data_status', 'valid')
                    ->where('oi.hpp_snapshot', '>', 0);
            })
            ->select([
                'mo.raw_json as order_raw_json',
                'mo.order_status',
                'mo.status',
                'ms.data_status as settlement_status',
            ])
            ->get();

        if ($orders->isEmpty()) {
            return ['total' => 0.0, 'settled' => 0.0, 'shipped' => 0.0];
        }

        $decode = static function ($value): array {
            if (is_array($value)) {
                return $value;
            }

            $decoded = json_decode((string) $value, true);
            return is_array($decoded) ? $decoded : [];
        };

        $skuKeys = $orders->flatMap(function ($order) use ($decode) {
            return collect($decode($order->order_raw_json)['item_list'] ?? [])
                ->map(fn ($item) => is_array($item) ? ($item['model_sku'] ?? $item['item_sku'] ?? null) : null)
                ->filter();
        })->unique()->values();

        if ($skuKeys->isEmpty()) {
            return ['total' => 0.0, 'settled' => 0.0, 'shipped' => 0.0];
        }

        $costs = DB::table('items as i')
            ->leftJoin('item_cost_snapshots as ics', function ($join) {
                $join->on('ics.item_id', '=', 'i.id')->where('ics.is_active', true);
            })
            ->whereIn('i.code', $skuKeys->all())
            ->select([
                'i.code',
                'ics.unit_cost as active_unit_cost',
                'i.base_unit_cost',
                'i.hpp',
            ])
            ->get()
            ->keyBy('code');

        $total = 0.0;
        $settled = 0.0;
        $shipped = 0.0;
        foreach ($orders as $order) {
            $isSettled = $order->settlement_status === MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE;
            $status = strtoupper((string) ($order->order_status ?: $order->status ?: ''));
            $isShipped = in_array($status, ['SHIPPED', 'READY_TO_HANDOVER', 'TO_CONFIRM_RECEIVE', 'COMPLETED'], true);
            foreach ($decode($order->order_raw_json)['item_list'] ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $sku = (string) ($item['model_sku'] ?? $item['item_sku'] ?? '');
                $cost = $costs->get($sku);
                if (! $cost) {
                    continue;
                }

                $unitHpp = (float) ($cost->active_unit_cost ?: $cost->base_unit_cost ?: $cost->hpp ?: 0);
                $qty = max(1, (int) ($item['model_quantity_purchased'] ?? $item['quantity_purchased'] ?? $item['active_qty'] ?? 1));
                $amount = $unitHpp * $qty;
                $total += $amount;
                if ($isSettled) {
                    $settled += $amount;
                }
                if ($isShipped) {
                    $shipped += $amount;
                }
            }
        }

        return [
            'total' => round($total, 2),
            'settled' => round($settled, 2),
            'shipped' => round($shipped, 2),
        ];
    }

    private function returnRefundHppAggregate(array $filters): array
    {
        $returns = DB::table('marketplace_returns as mr')
            ->leftJoin('marketplace_return_items as ri', 'ri.marketplace_return_id', '=', 'mr.id')
            ->whereBetween('mr.create_time', [
                Carbon::parse($filters['date_from'])->startOfDay()->timestamp,
                Carbon::parse($filters['date_to'])->endOfDay()->timestamp,
            ])
            ->when($filters['store_id'], fn ($query, $storeId) => $query->where('mr.store_id', $storeId))
            ->select([
                'mr.id as return_id',
                'mr.store_id',
                'mr.order_sn',
                'ri.item_sku',
                'ri.variation_sku',
                'ri.return_item_quantity',
            ])
            ->get();

        $returnSkuKeys = $returns->flatMap(fn ($row) => [(string) ($row->variation_sku ?? ''), (string) ($row->item_sku ?? '')])
            ->filter()
            ->unique()
            ->values();
        $masterCosts = $returnSkuKeys->isEmpty()
            ? collect()
            : DB::table('items as i')
                ->leftJoin('item_cost_snapshots as ics', function ($join) {
                    $join->on('ics.item_id', '=', 'i.id')->where('ics.is_active', true);
                })
                ->whereIn('i.code', $returnSkuKeys->all())
                ->select(['i.code', 'ics.unit_cost as active_unit_cost', 'i.base_unit_cost', 'i.hpp'])
                ->get()
                ->keyBy('code');

        $orderKeys = $returns->pluck('order_sn')->filter()->unique()->values();

        $orders = $orderKeys->isEmpty()
            ? collect()
            : DB::table('marketplace_orders')
                ->where(function ($query) use ($orderKeys) {
                    $query->whereIn('channel_order_id', $orderKeys->all())
                        ->orWhereIn('external_order_id', $orderKeys->all());
                })
                ->select(['id', 'store_id', 'channel_order_id', 'external_order_id'])
                ->get();

        $ordersByKey = [];
        foreach ($orders as $order) {
            foreach ([(string) $order->channel_order_id, (string) $order->external_order_id] as $key) {
                if ($key !== '') {
                    $ordersByKey[$order->store_id . '|' . $key] = $order;
                }
            }
        }

        $orderIds = collect($orders)->pluck('id')->values();

        $settlementStatuses = $orderIds->isEmpty()
            ? collect()
            : DB::table('marketplace_order_settlements')
                ->whereIn('order_id', $orderIds->all())
                ->pluck('data_status', 'order_id');

        $items = $orderIds->isEmpty()
            ? collect()
            : DB::table('marketplace_order_items')
                ->whereIn('marketplace_order_id', $orderIds->all())
                ->where('data_status', 'valid')
                ->where('hpp_snapshot', '>', 0)
                ->select(['marketplace_order_id', 'qty', 'hpp_snapshot', 'raw_json'])
                ->get()
                ->groupBy('marketplace_order_id');

        $decode = static function ($value): array {
            if (is_array($value)) {
                return $value;
            }

            $decoded = json_decode((string) $value, true);
            return is_array($decoded) ? $decoded : [];
        };

        $fallbackAmount = function ($returnItem) use ($masterCosts): float {
            $sku = (string) ($returnItem->variation_sku ?: $returnItem->item_sku ?: '');
            $cost = $masterCosts->get($sku);
            if (! $cost) {
                return 0.0;
            }

            $unitHpp = (float) ($cost->active_unit_cost ?: $cost->base_unit_cost ?: $cost->hpp ?: 0);
            $quantity = max(1, (int) ($returnItem->return_item_quantity ?? 1));
            return $unitHpp * $quantity;
        };

        $total = 0.0;
        $settled = 0.0;
        foreach ($returns->groupBy('return_id') as $returnItems) {
            $return = $returnItems->first();
            $order = $ordersByKey[$return->store_id . '|' . (string) $return->order_sn] ?? null;
            if (! $order) {
                foreach ($returnItems as $returnItem) {
                    $total += $fallbackAmount($returnItem);
                }
                continue;
            }

            $orderItems = $items->get($order->id, collect());
            foreach ($returnItems as $returnItem) {
                $itemSku = (string) ($returnItem->item_sku ?? '');
                $variationSku = (string) ($returnItem->variation_sku ?? '');
                $matches = $orderItems->filter(function ($item) use ($decode, $itemSku, $variationSku): bool {
                    $raw = $decode($item->raw_json);
                    $rawItemSku = (string) ($raw['item_sku'] ?? '');
                    $rawVariationSku = (string) ($raw['model_sku'] ?? '');
                    return ($variationSku !== '' && $rawVariationSku === $variationSku)
                        || ($itemSku !== '' && $rawItemSku === $itemSku);
                });

                if ($matches->isEmpty() && $orderItems->count() === 1) {
                    $matches = $orderItems;
                }

                $quantity = max(1, (int) ($returnItem->return_item_quantity ?? 1));
                $amount = 0.0;
                foreach ($matches as $item) {
                    $available = max(1, (int) ($item->qty ?? 1));
                    $amount = (float) $item->hpp_snapshot * min($quantity, $available);
                    $quantity = 0;
                    break;
                }
                if ($amount <= 0) {
                    $amount = $fallbackAmount($returnItem);
                }
                $total += $amount;
                if ($settlementStatuses->get($order->id) === MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE) {
                    $settled += $amount;
                }
            }
        }

        return [
            'total' => round($total, 2),
            'settled' => round($settled, 2),
            'unsettled' => round(max(0, $total - $settled), 2),
        ];
    }

    /**
     * Lightweight operational exceptions for the Return / refund drawer.
     * Return records are read from the stored marketplace return feed; failed
     * delivery is only inferred from explicit RTS/failed-delivery markers so
     * ordinary cancellations are not presented as delivery failures.
     */
    public function returnOrders(array $filters, int $page = 1, int $perPage = 50, string $type = 'return_refund'): array
    {
        $filters = $this->normalizeFilters($filters);
        $page = max(1, $page);
        $perPage = max(10, min(100, $perPage));
        $type = $type === 'failed_delivery' ? 'failed_delivery' : 'return_refund';
        $fromTimestamp = Carbon::parse($filters['date_from'])->startOfDay()->timestamp;
        $toTimestamp = Carbon::parse($filters['date_to'])->endOfDay()->timestamp;

        $rows = collect();

        if ($type === 'return_refund') {
            $rows = DB::table('marketplace_returns as mr')
                ->join('stores as st', 'st.id', '=', 'mr.store_id')
                ->whereBetween('mr.create_time', [$fromTimestamp, $toTimestamp])
                ->when($filters['store_id'], fn ($query, $storeId) => $query->where('mr.store_id', $storeId))
                ->select([
                    'mr.id',
                    'mr.store_id',
                    'st.name as store_name',
                    'mr.return_sn',
                    'mr.order_sn',
                    'mr.status',
                    'mr.reason',
                    'mr.reason_text_code',
                    'mr.return_solution',
                    'mr.amount_before_discount',
                    'mr.tracking_number',
                    'mr.create_time',
                    'mr.update_time',
                ])
                ->orderByDesc('mr.create_time')
                ->limit(1000)
                ->get()
                ->map(fn ($row): array => [
                    'id' => (int) $row->id,
                    'reference' => (string) ($row->return_sn ?: "RETURN-{$row->id}"),
                    'order_sn' => (string) ($row->order_sn ?: '-'),
                    'store_id' => (int) $row->store_id,
                    'store_name' => (string) ($row->store_name ?: 'Tanpa toko'),
                    'status' => (string) ($row->status ?: '—'),
                    'kind' => (int) ($row->return_solution ?? 0) === 1 ? 'refund' : 'return',
                    'reason' => (string) ($row->reason_text_code ?: $row->reason ?: 'Return / refund'),
                    'amount' => round((float) ($row->amount_before_discount ?? 0), 2),
                    'tracking_number' => (string) ($row->tracking_number ?: ''),
                    'event_time' => $row->create_time,
                    'updated_at' => $row->update_time,
                    'source' => 'marketplace_returns',
                ])->values();
        } else {
            $returnRows = DB::table('marketplace_returns as mr')
                ->join('stores as st', 'st.id', '=', 'mr.store_id')
                ->whereBetween('mr.create_time', [$fromTimestamp, $toTimestamp])
                ->when($filters['store_id'], fn ($query, $storeId) => $query->where('mr.store_id', $storeId))
                ->where(function ($query) {
                    $query->whereIn('mr.reason_text_code', ['RTS', 'FAILED_DELIVERY', 'DELIVERY_FAILED', 'RETURN_TO_SELLER'])
                        ->orWhereIn('mr.return_solution', ['RTS', 'RETURN_TO_SELLER'])
                        ->orWhereRaw("UPPER(COALESCE(mr.reason, '')) LIKE '%RTS%'")
                        ->orWhereRaw("UPPER(COALESCE(mr.reason, '')) LIKE '%DELIVERY%'");
                })
                ->select([
                    'mr.id',
                    'mr.store_id',
                    'st.name as store_name',
                    'mr.return_sn',
                    'mr.order_sn',
                    'mr.status',
                    'mr.reason',
                    'mr.reason_text_code',
                    'mr.amount_before_discount',
                    'mr.tracking_number',
                    'mr.create_time',
                    'mr.update_time',
                ])
                ->limit(1000)
                ->get()
                ->map(fn ($row): array => [
                    'id' => (int) $row->id,
                    'reference' => (string) ($row->return_sn ?: "RTS-{$row->id}"),
                    'order_sn' => (string) ($row->order_sn ?: '-'),
                    'store_id' => (int) $row->store_id,
                    'store_name' => (string) ($row->store_name ?: 'Tanpa toko'),
                    'status' => (string) ($row->status ?: '—'),
                    'kind' => 'failed_delivery',
                    'reason' => (string) ($row->reason_text_code ?: $row->reason ?: 'Pengiriman gagal'),
                    'amount' => round((float) ($row->amount_before_discount ?? 0), 2),
                    'tracking_number' => (string) ($row->tracking_number ?: ''),
                    'event_time' => $row->create_time,
                    'updated_at' => $row->update_time,
                    'source' => 'marketplace_returns',
                ]);

            $status = "UPPER(COALESCE(NULLIF(mo.order_status, ''), mo.status, ''))";
            $orderRows = DB::table('marketplace_orders as mo')
                ->join('stores as st', 'st.id', '=', 'mo.store_id')
                ->whereBetween('mo.ordered_at', [$filters['date_from'] . ' 00:00:00', $filters['date_to'] . ' 23:59:59'])
                ->when($filters['store_id'], fn ($query, $storeId) => $query->where('mo.store_id', $storeId))
                ->where(function ($query) use ($status) {
                    $query->whereRaw("{$status} IN ('FAILED_DELIVERY', 'DELIVERY_FAILED', 'RETURN_TO_SELLER', 'RETURNED_TO_SELLER', 'UNDELIVERED')")
                        ->orWhere(function ($nested) use ($status) {
                            $nested->whereRaw("{$status} IN ('CANCELLED', 'CANCELED')")
                                ->whereNotNull('mo.shipping_awb_no')
                                ->where('mo.shipping_awb_no', '<>', '');
                        });
                })
                ->select([
                    'mo.id',
                    'mo.store_id',
                    'st.name as store_name',
                    'mo.channel_order_id',
                    'mo.external_order_id',
                    'mo.order_status',
                    'mo.status',
                    'mo.total_amount',
                    'mo.total_paid_customer',
                    'mo.subtotal_items',
                    'mo.shipping_awb_no',
                    'mo.ordered_at',
                ])
                ->limit(1000)
                ->get()
                ->map(fn ($row): array => [
                    'id' => (int) $row->id,
                    'reference' => 'RTS-' . (string) ($row->channel_order_id ?: $row->external_order_id ?: $row->id),
                    'order_sn' => (string) ($row->channel_order_id ?: $row->external_order_id ?: '-'),
                    'store_id' => (int) $row->store_id,
                    'store_name' => (string) ($row->store_name ?: 'Tanpa toko'),
                    'status' => (string) ($row->order_status ?: $row->status ?: 'FAILED_DELIVERY'),
                    'kind' => 'failed_delivery',
                    'reason' => 'Pengiriman gagal / return to seller',
                    'amount' => round((float) ($row->total_amount ?: $row->total_paid_customer ?: $row->subtotal_items ?: 0), 2),
                    'tracking_number' => (string) ($row->shipping_awb_no ?: ''),
                    'event_time' => $row->ordered_at,
                    'updated_at' => null,
                    'source' => 'marketplace_orders',
                ]);

            $rows = $returnRows->concat($orderRows)->unique(fn (array $row) => $row['source'] . ':' . $row['reference'])->values();
        }

        $rows = $rows->sortByDesc('event_time')->values();
        $total = $rows->count();
        $pageRows = $rows->forPage($page, $perPage)->values()->all();

        return [
            'data' => $pageRows,
            'meta' => [
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ],
            'summary' => [
                'case_count' => $total,
                'amount' => round((float) $rows->sum('amount'), 2),
            ],
        ];
    }

    private function normalizeFilters(array $filters): array
    {
        $from = $filters['date_from'] ?? now()->subDays(29)->toDateString();
        $to = $filters['date_to'] ?? now()->toDateString();

        try {
            $from = Carbon::parse($from)->toDateString();
            $to = Carbon::parse($to)->toDateString();
        } catch (\Throwable) {
            $from = now()->subDays(29)->toDateString();
            $to = now()->toDateString();
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'store_id' => ! empty($filters['store_id']) ? (int) $filters['store_id'] : null,
            'date_from' => $from,
            'date_to' => $to,
            'compare_mode' => in_array($filters['compare_mode'] ?? 'prev_period', ['prev_period', 'prev_month', 'prev_quarter', 'prev_year'], true)
                ? ($filters['compare_mode'] ?? 'prev_period')
                : 'prev_period',
        ];
    }

    private function previousRange(array $filters): array
    {
        $from = Carbon::parse($filters['date_from']);
        $to = Carbon::parse($filters['date_to']);

        if ($filters['compare_mode'] === 'prev_month') {
            return [
                'date_from' => $from->copy()->subMonthsNoOverflow()->toDateString(),
                'date_to' => $to->copy()->subMonthsNoOverflow()->toDateString(),
            ];
        }

        if ($filters['compare_mode'] === 'prev_quarter') {
            return [
                'date_from' => $from->copy()->subMonthsNoOverflow(3)->toDateString(),
                'date_to' => $to->copy()->subMonthsNoOverflow(3)->toDateString(),
            ];
        }

        if ($filters['compare_mode'] === 'prev_year') {
            return [
                'date_from' => $from->copy()->subYear()->toDateString(),
                'date_to' => $to->copy()->subYear()->toDateString(),
            ];
        }

        $days = $from->diffInDays($to) + 1;
        $previousTo = $from->copy()->subDay();

        return [
            'date_from' => $previousTo->copy()->subDays($days - 1)->toDateString(),
            'date_to' => $previousTo->toDateString(),
        ];
    }

    private function financialBase(array $filters)
    {
        $itemCosts = DB::table('marketplace_order_items as oi')
            ->select('oi.marketplace_order_id')
            ->selectRaw('SUM(oi.hpp_snapshot * CASE WHEN oi.qty > 0 THEN oi.qty ELSE 0 END) AS hpp')
            ->selectRaw('SUM(CASE WHEN oi.qty > 0 THEN oi.qty ELSE 0 END) AS qty')
            ->where('oi.data_status', 'valid')
            ->where('oi.hpp_snapshot', '>', 0)
            ->whereExists(function ($query) use ($filters) {
                $query->selectRaw('1')
                    ->from('marketplace_orders as fmo')
                    ->whereColumn('fmo.id', 'oi.marketplace_order_id')
                    ->where('fmo.financial_data_status', MarketplaceFinancialDataQualityService::ORDER_READY)
                    ->whereNotNull('fmo.ordered_at')
                    ->whereBetween('fmo.ordered_at', [
                        $filters['date_from'] . ' 00:00:00',
                        $filters['date_to'] . ' 23:59:59',
                    ])
                    ->when($filters['store_id'], fn ($q, $storeId) => $q->where('fmo.store_id', $storeId));
            })
            ->groupBy('oi.marketplace_order_id');

        $query = DB::table('marketplace_orders as mo')
            ->join('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
            ->joinSub($itemCosts, 'ic', fn ($join) => $join->on('ic.marketplace_order_id', '=', 'mo.id'))
            ->join('stores as st', 'st.id', '=', 'mo.store_id')
            ->where('mo.financial_data_status', MarketplaceFinancialDataQualityService::ORDER_READY)
            ->where('ms.data_status', MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE)
            ->whereNotNull('mo.ordered_at');

        return $this->applyDateAndStoreFilters($query, $filters, 'mo');
    }

    private function financialAggregate(array $filters): array
    {
        $row = $this->financialBase($filters)
            ->selectRaw($this->aggregateSelect())
            ->first();

        return $this->normalizeAggregate($row);
    }

    private function financialDaily(array $filters): array
    {
        return $this->financialBase($filters)
            ->selectRaw('DATE(mo.ordered_at) AS date')
            ->selectRaw($this->aggregateSelect(false))
            ->groupByRaw('DATE(mo.ordered_at)')
            ->orderByRaw('DATE(mo.ordered_at)')
            ->get()
            ->map(fn ($row) => array_merge(['date' => $row->date], $this->normalizeAggregate($row)))
            ->values()
            ->all();
    }

    private function cashAggregate(array $filters, string $settlement = 'settled'): array
    {
        $fees = collect(self::MARKETPLACE_FEE_FIELDS)
            ->map(fn (string $field) => "COALESCE(ms.{$field}, 0)")
            ->implode(' + ');
        $affiliateFees = collect(self::AFFILIATE_FEE_FIELDS)
            ->map(fn (string $field) => "COALESCE(ms.{$field}, 0)")
            ->implode(' + ');

        $base = $settlement === 'unsettled' ? $this->unsettledBase($filters) : $this->cashBase($filters);
        $row = $base
            ->selectRaw('COUNT(DISTINCT mo.id) AS cash_order_count')
            ->selectRaw('SUM(COALESCE(NULLIF(ms.buyer_payment_amount, 0), NULLIF(mo.total_amount, 0), NULLIF(mo.total_paid_customer, 0), NULLIF(mo.subtotal_items, 0), 0)) AS cash_gross_sales')
            ->selectRaw('SUM(COALESCE(ms.final_income, 0)) AS cash_payout')
            ->selectRaw("SUM({$fees}) AS cash_marketplace_fees")
            ->selectRaw("SUM({$affiliateFees}) AS cash_affiliate_fees")
            ->selectRaw('SUM(COALESCE(ms.commission_fee, 0)) AS cash_commission_fee')
            ->selectRaw('SUM(COALESCE(ms.service_fee, 0)) AS cash_service_fee')
            ->selectRaw('SUM(COALESCE(ms.transaction_fee, 0)) AS cash_transaction_fee')
            ->selectRaw('SUM(COALESCE(ms.shipping_insurance_fee, 0)) AS cash_shipping_insurance_fee')
            ->selectRaw('SUM(COALESCE(ms.escrow_tax, 0)) AS cash_escrow_tax')
            ->selectRaw('SUM(COALESCE(ms.drc_adjustable_refund, 0)) AS cash_refund')
            ->first();
        $orderRevenue = $this->cashOrderRevenueAggregate($filters, $settlement);

        return [
            'cash_order_count' => (int) ($row->cash_order_count ?? 0),
            'cash_gross_sales' => round((float) ($row->cash_gross_sales ?? 0), 2),
            'cash_order_revenue' => $orderRevenue,
            'cash_payout' => round((float) ($row->cash_payout ?? 0), 2),
            'cash_marketplace_fees' => round((float) ($row->cash_marketplace_fees ?? 0), 2),
            'cash_affiliate_fees' => round((float) ($row->cash_affiliate_fees ?? 0), 2),
            'cash_commission_fee' => round((float) ($row->cash_commission_fee ?? 0), 2),
            'cash_service_fee' => round((float) ($row->cash_service_fee ?? 0), 2),
            'cash_transaction_fee' => round((float) ($row->cash_transaction_fee ?? 0), 2),
            'cash_shipping_insurance_fee' => round((float) ($row->cash_shipping_insurance_fee ?? 0), 2),
            'cash_escrow_tax' => round((float) ($row->cash_escrow_tax ?? 0), 2),
            'cash_refund' => round((float) ($row->cash_refund ?? 0), 2),
        ];
    }

    private function returnRefundAggregate(array $filters): array
    {
        $fromTimestamp = Carbon::parse($filters['date_from'])->startOfDay()->timestamp;
        $toTimestamp = Carbon::parse($filters['date_to'])->endOfDay()->timestamp;
        $returns = DB::table('marketplace_returns')
            ->whereBetween('create_time', [$fromTimestamp, $toTimestamp])
            ->when($filters['store_id'], fn ($query, $storeId) => $query->where('store_id', $storeId))
            ->select(['id', 'store_id', 'order_sn', 'amount_before_discount'])
            ->get();

        $orderKeys = $returns->pluck('order_sn')->filter()->unique()->values();
        $orders = $orderKeys->isEmpty()
            ? collect()
            : DB::table('marketplace_orders as mo')
                ->leftJoin('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
                ->where(function ($query) use ($orderKeys) {
                    $query->whereIn('mo.channel_order_id', $orderKeys->all())
                        ->orWhereIn('mo.external_order_id', $orderKeys->all());
                })
                ->select(['mo.id', 'mo.store_id', 'mo.channel_order_id', 'mo.external_order_id', 'ms.data_status as settlement_status'])
                ->get();

        $settlementByKey = [];
        foreach ($orders as $order) {
            foreach ([(string) $order->channel_order_id, (string) $order->external_order_id] as $key) {
                if ($key !== '') {
                    $settlementByKey[$order->store_id . '|' . $key] = $order->settlement_status;
                }
            }
        }

        $seenReturnOrders = [];
        $settledReturnOrders = 0;
        $unsettledReturnOrders = 0;
        foreach ($returns as $return) {
            $orderKey = (string) ($return->order_sn ?? '');
            $key = $return->store_id . '|' . ($orderKey !== '' ? $orderKey : 'return:' . $return->id);
            if (isset($seenReturnOrders[$key])) {
                continue;
            }

            $seenReturnOrders[$key] = true;
            if (($settlementByKey[$key] ?? null) === MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE) {
                $settledReturnOrders++;
            } else {
                $unsettledReturnOrders++;
            }
        }

        return [
            'return_refund_count' => $returns->count(),
            'return_refund_order_count' => count($seenReturnOrders),
            'return_refund_settled_order_count' => $settledReturnOrders,
            'return_refund_unsettled_order_count' => $unsettledReturnOrders,
            'return_refund_amount' => round((float) $returns->sum(fn ($return) => (float) ($return->amount_before_discount ?? 0)), 2),
        ];
    }

    private function cashBase(array $filters)
    {
        return $this->applyDateAndStoreFilters(
            DB::table('marketplace_orders as mo')
                ->join('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
                ->where('ms.data_status', MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE)
                ->whereRaw($this->isRevenueStatus()),
            $filters,
            'mo'
        );
    }

    private function unsettledBase(array $filters)
    {
        return $this->applyDateAndStoreFilters(
            DB::table('marketplace_orders as mo')
                ->leftJoin('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
                ->whereRaw($this->isRevenueStatus())
                ->where(function ($query) {
                    $query->whereNull('ms.id')
                        ->orWhereNull('ms.data_status')
                        ->orWhere('ms.data_status', '<>', MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE);
                }),
            $filters,
            'mo'
        );
    }

    private function storeSummary(array $filters): array
    {
        $financial = $this->financialBase($filters)
            ->selectRaw('mo.store_id, st.name as store_name')
            ->selectRaw($this->aggregateSelect(false))
            ->groupBy('mo.store_id', 'st.name')
            ->get()
            ->keyBy(fn ($row) => (string) $row->store_id);

        $operational = $this->operationalStoreSummary($filters)->keyBy(fn ($row) => (string) $row->store_id);
        $ads = $this->adsStoreSummary($filters)->keyBy(fn ($row) => (string) $row->store_id);
        $ids = $financial->keys()->merge($operational->keys())->merge($ads->keys())->unique();

        return $ids->map(function (string $id) use ($financial, $operational, $ads) {
            $finance = $financial->get($id);
            $ops = $operational->get($id);
            $ad = $ads->get($id);
            $aggregate = $this->normalizeAggregate($finance);

            return $this->withRates($this->applyAdCost(array_merge([
                'store_id' => (int) $id,
                'store_name' => $finance?->store_name ?? $ops?->store_name ?? $ad?->store_name ?? 'Tanpa toko',
                'order_total' => (int) ($ops->order_total ?? 0),
                'shipped_count' => (int) ($ops->shipped_count ?? 0),
                'completed_count' => (int) ($ops->completed_count ?? 0),
                'cancelled_count' => (int) ($ops->cancelled_count ?? 0),
                'gmv' => round((float) ($ops->gmv ?? 0), 2),
            ], $aggregate), [
                'ad_cost_before_tax' => (float) ($ad->ad_cost_before_tax ?? 0),
                'ad_cost_vat' => (float) ($ad->ad_cost_vat ?? 0),
                'ad_cost' => (float) ($ad->ad_cost ?? 0),
            ]));
        })->sortByDesc('gross_sales')->values()->all();
    }

    private function operationalAggregate(array $filters): array
    {
        $row = $this->operationalBase($filters)
            ->selectRaw($this->operationalSelect())
            ->first();

        return [
            'order_total' => (int) ($row->order_total ?? 0),
            'shipped_count' => (int) ($row->shipped_count ?? 0),
            'completed_count' => (int) ($row->completed_count ?? 0),
            'cancelled_count' => (int) ($row->cancelled_count ?? 0),
            'gmv' => round((float) ($row->gmv ?? 0), 2),
        ];
    }

    private function productQuantityAggregate(array $filters): array
    {
        $base = DB::table('marketplace_orders as mo')
            ->whereNotNull('mo.ordered_at')
            ->whereRaw($this->isRevenueStatus())
            ->whereBetween('mo.ordered_at', [
                $filters['date_from'] . ' 00:00:00',
                $filters['date_to'] . ' 23:59:59',
            ])
            ->when($filters['store_id'], fn ($query, $storeId) => $query->where('mo.store_id', $storeId));

        $local = (clone $base)
            ->join('marketplace_order_items as oi', 'oi.marketplace_order_id', '=', 'mo.id')
            ->leftJoin('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
            ->where('oi.data_status', 'valid')
            ->selectRaw('COALESCE(SUM(CASE WHEN oi.qty > 0 THEN oi.qty ELSE 0 END), 0) AS total')
            ->selectRaw('COALESCE(SUM(CASE WHEN ms.data_status = ? THEN CASE WHEN oi.qty > 0 THEN oi.qty ELSE 0 END ELSE 0 END), 0) AS settled', [MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE])
            ->first();

        $rawOrders = (clone $base)
            ->whereNotNull('mo.raw_json')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('marketplace_order_items as oi')
                    ->whereColumn('oi.marketplace_order_id', 'mo.id')
                    ->where('oi.data_status', 'valid');
            })
            ->select(['mo.raw_json as order_raw_json', 'ms.data_status as settlement_status'])
            ->leftJoin('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
            ->get();

        $decode = static function ($value): array {
            if (is_array($value)) {
                return $value;
            }

            $decoded = json_decode((string) $value, true);
            return is_array($decoded) ? $decoded : [];
        };

        $rawTotal = 0;
        $rawSettled = 0;
        foreach ($rawOrders as $rawOrder) {
            $quantity = collect($decode($rawOrder->order_raw_json)['item_list'] ?? [])->sum(function ($item): int {
                if (! is_array($item)) {
                    return 0;
                }

                return max(0, (int) ($item['model_quantity_purchased'] ?? $item['quantity_purchased'] ?? $item['active_qty'] ?? 0));
            });
            $rawTotal += $quantity;
            if ($rawOrder->settlement_status === MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE) {
                $rawSettled += $quantity;
            }
        }

        $total = (int) ($local->total ?? 0) + $rawTotal;
        $settled = (int) ($local->settled ?? 0) + $rawSettled;
        $unsettled = max(0, $total - $settled);
        $returnQty = (int) DB::table('marketplace_returns as mr')
            ->leftJoin('marketplace_return_items as ri', 'ri.marketplace_return_id', '=', 'mr.id')
            ->whereBetween('mr.create_time', [
                Carbon::parse($filters['date_from'])->startOfDay()->timestamp,
                Carbon::parse($filters['date_to'])->endOfDay()->timestamp,
            ])
            ->when($filters['store_id'], fn ($query, $storeId) => $query->where('mr.store_id', $storeId))
            ->sum(DB::raw('CASE WHEN ri.return_item_quantity > 0 THEN ri.return_item_quantity ELSE 0 END'));

        $returnQty = min($returnQty, $total);
        $unsettledAfterReturn = max(0, $unsettled - $returnQty);
        $settledAfterReturn = max(0, $settled - max(0, $returnQty - $unsettled));

        return [
            'total' => $settledAfterReturn + $unsettledAfterReturn + $returnQty,
            'settled' => $settledAfterReturn,
            'unsettled' => $unsettledAfterReturn,
            'return_refund' => $returnQty,
        ];
    }

    private function operationalDaily(array $filters): array
    {
        return $this->operationalBase($filters)
            ->selectRaw('DATE(mo.ordered_at) AS date')
            ->selectRaw('SUM(CASE WHEN ' . $this->isRevenueStatus() . ' THEN ' . $this->orderValueExpression() . ' ELSE 0 END) AS gmv')
            ->groupByRaw('DATE(mo.ordered_at)')
            ->orderByRaw('DATE(mo.ordered_at)')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'gmv' => round((float) ($row->gmv ?? 0), 2)])
            ->values()
            ->all();
    }

    private function mergeDaily(array $financial, array $operational, array $ads = []): array
    {
        $rows = collect($financial)->keyBy('date');
        foreach ($operational as $row) {
            $date = (string) $row['date'];
            $rows[$date] = array_merge($rows[$date] ?? [
                'date' => $date,
                'order_count' => 0,
                'qty' => 0,
                'gross_sales' => 0,
                'marketplace_fees' => 0,
                'refund' => 0,
                'payout' => 0,
                'hpp' => 0,
                'ad_cost_before_tax' => 0,
                'ad_cost_vat' => 0,
                'ad_cost' => 0,
                'ad_cost_settlement' => 0,
                'gross_profit' => 0,
                'operating_profit' => 0,
                'margin_pct' => 0,
                'aov' => 0,
            ], ['gmv' => (float) ($row['gmv'] ?? 0)]);
        }

        if ($ads !== []) {
            foreach ($ads as $row) {
                $date = (string) $row['date'];
                $rows[$date] = array_merge($rows[$date] ?? [
                    'date' => $date,
                    'order_count' => 0,
                    'qty' => 0,
                    'gross_sales' => 0,
                    'marketplace_fees' => 0,
                    'refund' => 0,
                    'payout' => 0,
                    'hpp' => 0,
                    'ad_cost_before_tax' => 0,
                    'ad_cost_vat' => 0,
                    'ad_cost' => 0,
                    'ad_cost_settlement' => 0,
                    'gross_profit' => 0,
                    'operating_profit' => 0,
                    'margin_pct' => 0,
                    'aov' => 0,
                ], [
                    'ad_cost_before_tax' => (float) ($row['ad_cost_before_tax'] ?? 0),
                    'ad_cost_vat' => (float) ($row['ad_cost_vat'] ?? 0),
                    'ad_cost' => (float) ($row['ad_cost'] ?? 0),
                ]);
            }

            $rows = $rows->map(function (array $row) use ($ads) {
                $adCostByDate = collect($ads)->firstWhere('date', $row['date']);
                $row['ad_cost_settlement'] = round((float) ($row['ad_cost_settlement'] ?? $row['ad_cost'] ?? 0), 2);
                $row['ad_cost_before_tax'] = round((float) ($adCostByDate['ad_cost_before_tax'] ?? 0), 2);
                $row['ad_cost_vat'] = round((float) ($adCostByDate['ad_cost_vat'] ?? 0), 2);
                $row['ad_cost'] = round((float) ($adCostByDate['ad_cost'] ?? 0), 2);
                $row['gross_profit'] = round((float) ($row['payout'] ?? 0) - (float) ($row['hpp'] ?? 0), 2);
                $row['operating_profit'] = round($row['gross_profit'] - $row['ad_cost'], 2);
                $row['margin_pct'] = (float) ($row['gross_sales'] ?? 0) > 0
                    ? round(($row['operating_profit'] / $row['gross_sales']) * 100, 2)
                    : 0.0;

                return $row;
            });
        }

        return $rows->sortKeys()->values()->all();
    }

    private function operationalStoreSummary(array $filters)
    {
        return $this->operationalBase($filters)
            ->join('stores as st', 'st.id', '=', 'mo.store_id')
            ->selectRaw('mo.store_id, st.name as store_name')
            ->selectRaw($this->operationalSelect(false))
            ->groupBy('mo.store_id', 'st.name')
            ->get();
    }

    private function adsAggregate(array $filters): array
    {
        $row = $this->adsBase($filters)
            ->selectRaw('COALESCE(SUM(spend), 0) AS ad_cost_before_tax')
            ->first();
        $beforeTax = (float) ($row->ad_cost_before_tax ?? 0);

        return [
            'ad_cost_before_tax' => round($beforeTax, 2),
            'ad_cost_vat' => round($beforeTax * self::ADS_VAT_RATE, 2),
            'ad_cost' => round($beforeTax * (1 + self::ADS_VAT_RATE), 2),
        ];
    }

    private function adsDaily(array $filters): array
    {
        return $this->adsBase($filters)
            ->selectRaw('date, COALESCE(SUM(spend), 0) AS ad_cost_before_tax')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                $beforeTax = (float) ($row->ad_cost_before_tax ?? 0);

                return [
                    'date' => (string) $row->date,
                    'ad_cost_before_tax' => round($beforeTax, 2),
                    'ad_cost_vat' => round($beforeTax * self::ADS_VAT_RATE, 2),
                    'ad_cost' => round($beforeTax * (1 + self::ADS_VAT_RATE), 2),
                ];
            })
            ->values()
            ->all();
    }

    private function adsStoreSummary(array $filters)
    {
        return $this->adsBase($filters)
            ->join('stores as st', 'st.id', '=', 'marketplace_ads_dailies.store_id')
            ->selectRaw('marketplace_ads_dailies.store_id, st.name as store_name, COALESCE(SUM(marketplace_ads_dailies.spend), 0) AS ad_cost_before_tax')
            ->groupBy('marketplace_ads_dailies.store_id', 'st.name')
            ->get()
            ->map(function ($row) {
                $beforeTax = (float) ($row->ad_cost_before_tax ?? 0);
                $row->ad_cost_before_tax = round($beforeTax, 2);
                $row->ad_cost_vat = round($beforeTax * self::ADS_VAT_RATE, 2);
                $row->ad_cost = round($beforeTax * (1 + self::ADS_VAT_RATE), 2);

                return $row;
            });
    }

    private function adsBase(array $filters)
    {
        return DB::table('marketplace_ads_dailies')
            ->whereBetween('date', [$filters['date_from'], $filters['date_to']])
            ->when($filters['store_id'], fn ($q, $storeId) => $q->where('store_id', $storeId));
    }

    private function operationalBase(array $filters)
    {
        return $this->applyDateAndStoreFilters(DB::table('marketplace_orders as mo'), $filters, 'mo');
    }

    private function qualitySummary(array $filters): array
    {
        $rows = $this->applyDateAndStoreFilters(DB::table('marketplace_orders as mo'), $filters, 'mo')
            ->selectRaw("COALESCE(mo.financial_data_status, 'unknown') AS status, COUNT(*) AS total")
            ->groupBy('mo.financial_data_status')
            ->get()
            ->keyBy('status');

        return [
            'total' => (int) $rows->sum('total'),
            'ready' => (int) ($rows->get('ready')?->total ?? 0),
            'incomplete' => (int) ($rows->get('incomplete')?->total ?? 0),
            'unknown' => (int) ($rows->get('unknown')?->total ?? 0),
            'not_applicable' => (int) ($rows->get('not_applicable')?->total ?? 0),
        ];
    }

    private function applyDateAndStoreFilters($query, array $filters, string $alias)
    {
        return $query
            ->whereBetween("{$alias}.ordered_at", [
                $filters['date_from'] . ' 00:00:00',
                $filters['date_to'] . ' 23:59:59',
            ])
            ->when($filters['store_id'], fn ($q, $storeId) => $q->where("{$alias}.store_id", $storeId));
    }

    private function aggregateSelect(bool $includeStore = true): string
    {
        $gross = 'COALESCE(NULLIF(ms.buyer_payment_amount, 0), NULLIF(mo.total_amount, 0), NULLIF(mo.total_paid_customer, 0), NULLIF(mo.subtotal_items, 0), 0)';
        $fees = collect(self::MARKETPLACE_FEE_FIELDS)
            ->map(fn (string $field) => "COALESCE(ms.{$field}, 0)")
            ->implode(' + ');
        $affiliateFees = collect(self::AFFILIATE_FEE_FIELDS)
            ->map(fn (string $field) => "COALESCE(ms.{$field}, 0)")
            ->implode(' + ');

        return implode(', ', [
            'COUNT(DISTINCT mo.id) AS order_count',
            'SUM(COALESCE(ic.qty, 0)) AS qty',
            "SUM({$gross}) AS gross_sales",
            "SUM({$fees}) AS marketplace_fees",
            "SUM({$affiliateFees}) AS affiliate_fees",
            'SUM(COALESCE(ms.drc_adjustable_refund, 0)) AS refund',
            'SUM(COALESCE(ms.final_income, 0)) AS payout',
            'SUM(COALESCE(ic.hpp, 0)) AS hpp',
            'SUM(COALESCE(ms.ad_cost, 0)) AS ad_cost',
        ]);
    }

    private function operationalSelect(bool $includeStore = true): string
    {
        $status = "UPPER(COALESCE(NULLIF(mo.order_status, ''), mo.status, ''))";

        return implode(', ', [
            'SUM(CASE WHEN ' . $this->isRevenueStatus() . ' THEN 1 ELSE 0 END) AS order_total',
            "SUM(CASE WHEN {$status} IN ('SHIPPED', 'READY_TO_HANDOVER', 'TO_CONFIRM_RECEIVE', 'COMPLETED') THEN 1 ELSE 0 END) AS shipped_count",
            "SUM(CASE WHEN {$status} = 'COMPLETED' THEN 1 ELSE 0 END) AS completed_count",
            "SUM(CASE WHEN {$status} IN ('CANCELLED', 'CANCELED', 'BATAL') THEN 1 ELSE 0 END) AS cancelled_count",
            'SUM(CASE WHEN ' . $this->isRevenueStatus() . ' THEN ' . $this->orderValueExpression() . ' ELSE 0 END) AS gmv',
        ]);
    }

    private function orderValueExpression(): string
    {
        return 'COALESCE(NULLIF(mo.total_amount, 0), NULLIF(mo.total_paid_customer, 0), mo.subtotal_items, 0)';
    }

    private function isRevenueStatus(): string
    {
        $status = "UPPER(COALESCE(NULLIF(mo.order_status, ''), mo.status, ''))";
        return "{$status} NOT IN ('CANCELLED', 'CANCELED', 'BATAL')";
    }

    private function normalizeAggregate($row): array
    {
        $gross = (float) ($row->gross_sales ?? 0);
        $payout = (float) ($row->payout ?? 0);
        $hpp = (float) ($row->hpp ?? 0);
        $adCost = (float) ($row->ad_cost ?? 0);
        $operating = $payout - $hpp - $adCost;

        return [
            'order_count' => (int) ($row->order_count ?? 0),
            'qty' => (int) ($row->qty ?? 0),
            'gross_sales' => round($gross, 2),
            'marketplace_fees' => round((float) ($row->marketplace_fees ?? 0), 2),
            'affiliate_fees' => round((float) ($row->affiliate_fees ?? 0), 2),
            'refund' => round((float) ($row->refund ?? 0), 2),
            'payout' => round($payout, 2),
            'hpp' => round($hpp, 2),
            'ad_cost' => round($adCost, 2),
            'ad_cost_settlement' => round($adCost, 2),
            'gross_profit' => round($payout - $hpp, 2),
            'operating_profit' => round($operating, 2),
            'margin_pct' => $gross > 0 ? round(($operating / $gross) * 100, 2) : 0.0,
            'aov' => ($row->order_count ?? 0) > 0 ? round($gross / (int) $row->order_count, 2) : 0.0,
        ];
    }

    private function changes(array $current, array $previous): array
    {
        return collect(['gmv', 'gross_sales', 'payout', 'estimated_profit', 'operating_profit', 'order_count', 'ad_cost', 'aov', 'completion_rate', 'cancel_rate', 'profit_margin'])
            ->mapWithKeys(function (string $key) use ($current, $previous) {
                $now = (float) ($current[$key] ?? 0);
                $before = (float) ($previous[$key] ?? 0);
                return [$key => $before == 0 ? ($now > 0 ? null : 0) : round((($now - $before) / $before) * 100, 2)];
            })->all();
    }

    private function withRates(array $aggregate): array
    {
        $orders = (int) ($aggregate['order_total'] ?? 0);
        $gross = (float) ($aggregate['gross_sales'] ?? 0);

        return array_merge($this->withEstimatedFee($aggregate), [
            'completion_rate' => $orders > 0 ? round(((int) ($aggregate['completed_count'] ?? 0) / $orders) * 100, 2) : 0.0,
            'cancel_rate' => $orders > 0 ? round(((int) ($aggregate['cancelled_count'] ?? 0) / $orders) * 100, 2) : 0.0,
            'profit_margin' => $gross > 0 ? round(((float) ($aggregate['operating_profit'] ?? 0) / $gross) * 100, 2) : 0.0,
        ]);
    }

    private function applyAdCost(array $aggregate, array $ads): array
    {
        $aggregate['ad_cost_settlement'] = round((float) ($aggregate['ad_cost_settlement'] ?? $aggregate['ad_cost'] ?? 0), 2);
        $aggregate['ad_cost_before_tax'] = round((float) ($ads['ad_cost_before_tax'] ?? $aggregate['ad_cost_before_tax'] ?? 0), 2);
        $aggregate['ad_cost_vat'] = round((float) ($ads['ad_cost_vat'] ?? $aggregate['ad_cost_vat'] ?? 0), 2);
        $aggregate['ad_cost_vat_rate'] = self::ADS_VAT_RATE * 100;
        $aggregate['ad_cost'] = round((float) ($ads['ad_cost'] ?? 0), 2);
        $payout = (float) ($aggregate['payout'] ?? 0);
        $hpp = (float) ($aggregate['hpp'] ?? 0);
        $aggregate['gross_profit'] = round($payout - $hpp, 2);
        $aggregate['operating_profit'] = round($aggregate['gross_profit'] - $aggregate['ad_cost'], 2);

        $gross = (float) ($aggregate['gross_sales'] ?? 0);
        $aggregate['margin_pct'] = $gross > 0
            ? round(($aggregate['operating_profit'] / $gross) * 100, 2)
            : 0.0;

        return $aggregate;
    }

    private function withEstimatedFee(array $aggregate): array
    {
        $gmv = (float) ($aggregate['gmv'] ?? 0);
        $cashPayout = (float) ($aggregate['cash_payout'] ?? $aggregate['payout'] ?? 0);
        // Fee actual is known from settlement, but its rate is compared with
        // marketplace order revenue—not the final payout after deductions.
        $marketplaceRevenue = (float) ($aggregate['cash_order_revenue'] ?? 0);
        $actualMarketplaceFee = (float) ($aggregate['cash_marketplace_fees'] ?? 0);
        $hpp = (float) ($aggregate['hpp'] ?? 0);
        $adCost = (float) ($aggregate['ad_cost'] ?? 0);
        $returnRefund = (float) ($aggregate['return_refund_amount'] ?? 0);
        $feeRate = $marketplaceRevenue > 0 && $actualMarketplaceFee > 0
            ? $actualMarketplaceFee / $marketplaceRevenue
            : self::ESTIMATED_MARKETPLACE_FEE_RATE;
        $estimatedFee = $gmv * $feeRate;
        $estimatedProfit = $gmv - $estimatedFee - $returnRefund - $hpp - $adCost;

        return array_merge($aggregate, [
            'marketplace_fee_estimate_rate' => round($feeRate * 100, 2),
            'marketplace_fee_estimate' => round($estimatedFee, 2),
            'marketplace_fee_estimate_on_payout' => round($cashPayout * $feeRate, 2),
            'marketplace_fee_estimate_on_cash' => round($cashPayout * $feeRate, 2),
            'estimated_profit' => round($estimatedProfit, 2),
            'estimated_profit_margin' => $gmv > 0 ? round(($estimatedProfit / $gmv) * 100, 2) : 0.0,
            'marketplace_fees_actual' => round((float) ($aggregate['cash_marketplace_fees'] ?? $aggregate['marketplace_fees'] ?? 0), 2),
            'affiliate_fees_actual' => round((float) ($aggregate['cash_affiliate_fees'] ?? $aggregate['affiliate_fees'] ?? 0), 2),
        ]);
    }
}
