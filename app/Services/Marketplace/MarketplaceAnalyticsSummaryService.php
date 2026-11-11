<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceOrderSettlement;
use App\Services\Marketplace\TikTokShop\TikTokWalletAdCostService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    /**
     * Minimum net margin to preserve after marketplace costs and ads.
     * Returned as a percentage in the drill-down payload.
     */
    private const TARGET_NET_MARGIN_RATE = 0.15;

    private const SHOPEE_AD_CHARGE_TYPES = [
        '450',
        'paid_ads_charge',
        'paid-ads-charge',
    ];

    private const SHOPEE_AD_REFUND_TYPES = [
        '451',
        'paid_ads_refund',
        'paid-ads-refund',
    ];

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

    /**
     * Order statuses which must never contribute to omzet/GMV. Pending
     * payment is not revenue yet, even when the marketplace supplies an
     * order amount that can be used for operational display elsewhere.
     */
    private const NON_REVENUE_ORDER_STATUSES = [
        'UNPAID',
        'INVOICE_PENDING',
        'CANCELLED',
        'CANCELED',
        'CANCELLED_BEFORE_SHIPPING',
        'BATAL',
        'IN_CANCEL',
        'TO_RETURN',
        'RETURNING',
        'RETURNED',
        'REFUND',
        'REFUNDED',
    ];

    public function __construct(
        private MarketplaceProfitReportService $profitReport,
        private TikTokWalletAdCostService $tiktokWalletAdCost,
    ) {}

    public function summary(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);
        $previous = $this->previousRange($filters);
        $previousFilters = array_merge($filters, $previous);

        $currentFinancial = $this->financialAggregate($filters);
        $previousFinancial = $this->financialAggregate($previousFilters);
        $currentCash = $this->cashAggregate($filters);
        $previousCash = $this->cashAggregate($previousFilters);
        $currentUnsettledCash = $this->cashAggregate($filters, 'unsettled');
        $previousUnsettledCash = $this->cashAggregate($previousFilters, 'unsettled');
        $currentUnsettledEstimate = $this->estimatedUnsettledPayout($filters, $currentCash);
        $previousUnsettledEstimate = $this->estimatedUnsettledPayout($previousFilters, $previousCash);
        $currentHpp = $this->hppAggregate($filters);
        $previousHpp = $this->hppAggregate($previousFilters);
        $currentReturnHpp = $this->returnRefundHppAggregate($filters);
        $previousReturnHpp = $this->returnRefundHppAggregate($previousFilters);
        $currentReturns = $this->returnRefundAggregate($filters);
        $previousReturns = $this->returnRefundAggregate($previousFilters);
        $currentProductQty = $this->productQuantityAggregate($filters);
        $previousProductQty = $this->productQuantityAggregate($previousFilters);
        $currentOperational = $this->operationalAggregate($filters);
        $previousOperational = $this->operationalAggregate($previousFilters);
        $currentAds = $this->adsAggregate($filters);
        $previousAds = $this->adsAggregate($previousFilters);
        $quality = $this->qualitySummary($filters);
        $current = $this->withRates($this->applyAdCost(array_merge($currentFinancial, $currentCash, [
            'cash_unsettled_order_count' => $currentUnsettledCash['cash_order_count'],
            'cash_unsettled_gross_sales' => $currentUnsettledCash['cash_gross_sales'],
            'cash_unsettled_order_revenue' => $currentUnsettledCash['cash_order_revenue'],
            'estimated_unsettled_payout' => $currentUnsettledEstimate['payout'],
            'estimated_unsettled_order_count' => $currentUnsettledEstimate['order_count'],
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
            'estimated_unsettled_payout' => $previousUnsettledEstimate['payout'],
            'estimated_unsettled_order_count' => $previousUnsettledEstimate['order_count'],
            'hpp_total' => $previousHpp['hpp_total'],
            'hpp_settled' => $previousHpp['hpp_settled'],
            'hpp_unsettled' => $previousHpp['hpp_unsettled'],
            'hpp_shipped' => $previousHpp['hpp_shipped'],
            'hpp_return_refund' => $previousReturnHpp['total'],
            'hpp_return_refund_settled' => $previousReturnHpp['settled'],
            'hpp_return_refund_unsettled' => $previousReturnHpp['unsettled'],
            'return_refund_count' => $previousReturns['return_refund_count'],
            'return_refund_order_count' => $previousReturns['return_refund_order_count'],
            'return_refund_settled_order_count' => $previousReturns['return_refund_settled_order_count'],
            'return_refund_unsettled_order_count' => $previousReturns['return_refund_unsettled_order_count'],
            'return_refund_amount' => $previousReturns['return_refund_amount'],
            'product_qty' => $previousProductQty['total'],
            'product_qty_settled' => $previousProductQty['settled'],
            'product_qty_unsettled' => $previousProductQty['unsettled'],
            'product_qty_return_refund' => $previousProductQty['return_refund'],
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

    /**
     * Lightweight payload for the first paint of the analytics page.
     * Deliberately excludes daily, store, product, and cohort detail rows.
     */
    public function kpis(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);
        $previous = $this->previousRange($filters);
        $previousFilters = array_merge($filters, $previous);
        $current = $this->kpiSnapshot($filters);
        $previousSnapshot = $this->kpiSnapshot($previousFilters);

        return [
            'filters' => $filters,
            'current' => $current,
            'previous' => $previousSnapshot,
            'changes' => $this->changes($current, $previousSnapshot),
            'quality' => $this->qualitySummary($filters),
        ];
    }

    /**
     * Lightweight store ledger payload used by the portfolio view. Keeping
     * this separate from the full summary prevents the store ranking from
     * waiting on daily charts, returns, and other detail aggregates.
     */
    public function stores(array $filters): array
    {
        return $this->storeSummary($this->normalizeFilters($filters));
    }

    private function kpiSnapshot(array $filters): array
    {
        $financial = $this->financialAggregate($filters);
        $cash = $this->cashAggregate($filters);
        $unsettledCash = $this->cashAggregate($filters, 'unsettled');
        $unsettledEstimate = $this->estimatedUnsettledPayout($filters, $cash);
        $hpp = $this->hppAggregate($filters);
        $returns = $this->returnRefundAggregate($filters);
        $productQty = $this->productQuantityAggregate($filters);
        $operational = $this->operationalAggregate($filters);
        $ads = $this->adsAggregate($filters);

        return $this->withRates($this->applyAdCost(array_merge($financial, $cash, [
            'cash_unsettled_order_count' => $unsettledCash['cash_order_count'],
            'cash_unsettled_gross_sales' => $unsettledCash['cash_gross_sales'],
            'cash_unsettled_order_revenue' => $unsettledCash['cash_order_revenue'],
            'estimated_unsettled_payout' => $unsettledEstimate['payout'],
            'estimated_unsettled_order_count' => $unsettledEstimate['order_count'],
            'hpp_total' => $hpp['hpp_total'],
            'hpp_settled' => $hpp['hpp_settled'],
            'hpp_unsettled' => $hpp['hpp_unsettled'],
            'hpp_shipped' => $hpp['hpp_shipped'],
            'return_refund_count' => $returns['return_refund_count'],
            'return_refund_order_count' => $returns['return_refund_order_count'],
            'return_refund_amount' => $returns['return_refund_amount'],
            'product_qty' => $productQty['total'],
            'product_qty_settled' => $productQty['settled'],
            'product_qty_unsettled' => $productQty['unsettled'],
            'product_qty_return_refund' => $productQty['return_refund'],
        ], $operational), $ads));
    }

    public function products(array $filters, int $limit = 100): array
    {
        $filters = $this->normalizeFilters($filters);
        $limit = max(1, min(200, $limit));
        $items = $this->profitReport->report([
            'store_id' => $filters['store_id'],
            'store_ids' => $filters['store_ids'],
            'date_basis' => 'ordered_at',
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
        ])['items'];

        // The profit report intentionally contains only orders which are
        // ready for final profit recognition. Analytics must still show the
        // product portfolio when an order is valid but its HPP is incomplete;
        // the UI can then label that row as "HPP belum lengkap" instead of
        // making the whole product tab look empty.
        $items = collect(array_merge($items, $this->operationalProductItems($filters)))
            ->groupBy('item_key')
            ->map(function ($rows): array {
                $merged = $rows->first();
                foreach (['qty', 'gross_sales', 'payout', 'hpp', 'ad_cost', 'ad_cost_settlement', 'gross_profit', 'operating_profit'] as $field) {
                    $merged[$field] = (float) $rows->sum(fn (array $row) => (float) ($row[$field] ?? 0));
                }

                return $merged;
            })
            ->values()
            ->all();
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
     * Return product rows from eligible orders which are not yet ready for
     * final profit reporting. These rows intentionally carry zero HPP and
     * payout when the source does not have those facts yet.
     */
    private function operationalProductItems(array $filters): array
    {
        $gross = 'COALESCE(NULLIF(ms.buyer_payment_amount, 0), NULLIF(mo.total_amount, 0), NULLIF(mo.total_paid_customer, 0), NULLIF(mo.subtotal_items, 0), 0)';

        $query = DB::table('marketplace_order_items as oi')
            ->join('marketplace_orders as mo', 'mo.id', '=', 'oi.marketplace_order_id')
            ->leftJoin('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
            ->whereNotNull('mo.ordered_at')
            ->whereRaw($this->isRevenueStatus())
            ->where(function ($query) {
                $query->whereNull('mo.financial_data_status')
                    ->orWhere('mo.financial_data_status', '!=', MarketplaceFinancialDataQualityService::ORDER_READY);
            })
            ->where(function ($query) {
                $query->where('oi.data_status', 'valid')
                    ->orWhereNull('oi.data_status');
            })
            ->select([
                'oi.id',
                'oi.marketplace_order_id as order_id',
                'oi.internal_item_id',
                'oi.item_name',
                'oi.item_name_snapshot',
                'oi.model_sku',
                'oi.item_sku',
                'oi.external_sku',
                'oi.qty',
                'oi.price',
                'oi.hpp_snapshot',
                'ms.final_income',
            ])
            ->selectRaw("{$gross} AS order_gross_sales");
        $rows = $this->applyDateAndStoreFilters($query, $filters, 'mo')->get();

        $productRows = [];
        foreach ($rows->groupBy('order_id') as $orderRows) {
            $lineSalesTotal = (float) $orderRows->sum(function ($row): float {
                return max((float) ($row->price ?? 0), 0) * max((int) ($row->qty ?? 0), 0);
            });
            $rowCount = max($orderRows->count(), 1);
            $orderGrossSales = (float) ($orderRows->first()->order_gross_sales ?? 0);
            $orderPayout = (float) ($orderRows->first()->final_income ?? 0);

            foreach ($orderRows as $row) {
                $qty = max((int) ($row->qty ?? 0), 0);
                $lineSales = max((float) ($row->price ?? 0), 0) * $qty;
                $share = $lineSalesTotal > 0 ? $lineSales / $lineSalesTotal : 1 / $rowCount;
                $key = (string) ($row->internal_item_id ?: $row->model_sku ?: $row->item_sku ?: $row->external_sku ?: $row->id);
                $hpp = (float) ($row->hpp_snapshot ?? 0) * $qty;

                $productRows[$key] ??= [
                    'item_key' => $key,
                    'item_name' => (string) ($row->item_name ?: $row->item_name_snapshot ?: '-'),
                    'sku' => (string) ($row->model_sku ?: $row->item_sku ?: $row->external_sku ?: '-'),
                    'qty' => 0,
                    'gross_sales' => 0.0,
                    'payout' => 0.0,
                    'hpp' => 0.0,
                    'ad_cost' => 0.0,
                    'ad_cost_settlement' => 0.0,
                    'gross_profit' => 0.0,
                    'operating_profit' => 0.0,
                    'margin_pct' => 0.0,
                ];

                $grossSales = $orderGrossSales * $share;
                $payout = $orderPayout * $share;
                $productRows[$key]['qty'] += $qty;
                $productRows[$key]['gross_sales'] += $grossSales;
                $productRows[$key]['payout'] += $payout;
                $productRows[$key]['hpp'] += $hpp;
                $productRows[$key]['gross_profit'] += $payout - $hpp;
                $productRows[$key]['operating_profit'] += $payout - $hpp;
            }
        }

        return array_values($productRows);
    }

    /**
     * Return order detail for the lazy cash-detail drawer. The all-status
     * view includes cancelled rows for audit, while its aggregate still
     * excludes cancelled orders from revenue.
     */
    public function cashOrders(array $filters, int $page = 1, int $perPage = 50, string $settlement = 'settled'): array
    {
        $filters = $this->normalizeFilters($filters);
        $page = max(1, $page);
        $perPage = max(10, min(100, $perPage));
        $settlement = in_array($settlement, ['all', 'unsettled', 'shipped', 'warehouse', 'confirm', 'cancelled', 'return_refund'], true) ? $settlement : 'settled';
        $base = match ($settlement) {
            'all' => $this->allCashBase($filters),
            'unsettled' => $this->unsettledOrdersBase($filters),
            'shipped' => $this->cashStatusBase($filters, 'shipped'),
            'warehouse' => $this->cashStatusBase($filters, 'warehouse'),
            'confirm' => $this->cashStatusBase($filters, 'confirm'),
            'cancelled' => $this->cashStatusBase($filters, 'cancelled'),
            'return_refund' => $this->returnRefundBase($filters),
            default => $this->cashBase($filters),
        };

        $paginator = $base
            ->join('stores as st', 'st.id', '=', 'mo.store_id')
            ->join('channels as ch', 'ch.id', '=', 'st.channel_id')
            ->select([
                'mo.id',
                'mo.channel_order_id',
                'mo.external_order_id',
                'mo.order_status',
                'mo.status',
                'mo.payment_method',
                'mo.ordered_at',
                'mo.total_amount',
                'mo.total_paid_customer',
                'mo.subtotal_items',
                'mo.raw_json as order_raw_json',
                'mo.store_id',
                'st.name as store_name',
                'ch.code as platform',
                'ms.data_status',
                'ms.settlement_time',
                'ms.buyer_payment_amount',
                'ms.seller_voucher',
                'ms.raw_json as settlement_raw_json',
                'ms.final_income',
                'ms.drc_adjustable_refund',
                'ms.return_refund_status',
                'ms.refund_source',
                'ms.reversal_amount',
                'ms.reversal_source',
                'ms.commission_fee',
                'ms.service_fee',
                'ms.transaction_fee',
                'ms.affiliate_fee',
                'ms.activity_fee',
                'ms.actual_shipping_fee',
                'ms.shipping_cost_amount',
                'ms.shipping_fee_subsidy',
                'ms.shipping_insurance_fee',
                'ms.escrow_tax',
                DB::raw($this->returnRefundExistsSql().' AS has_return_refund'),
                DB::raw($this->warehouseBookingExistsSql().' AS has_warehouse_booking'),
            ])
            ->orderByDesc('ms.settlement_time')
            ->orderByDesc('mo.ordered_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $orderIds = collect($paginator->items())->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $internalCategoriesByOrder = $this->internalCategoriesByOrderIds($orderIds);
        $internalItemQtyByOrder = $this->internalItemQtyByOrderIds($orderIds);
        $internalItemCogsByOrder = $this->internalItemCogsByOrderIds($orderIds);

        $marketplaceFee = fn ($row): float => collect(self::MARKETPLACE_FEE_FIELDS)
            ->sum(fn (string $field) => (float) ($row->{$field} ?? 0));
        $affiliateFee = fn ($row): float => collect(self::AFFILIATE_FEE_FIELDS)
            ->sum(fn (string $field) => (float) ($row->{$field} ?? 0));

        $data = collect($paginator->items())->map(function ($row) use ($marketplaceFee, $affiliateFee, $internalCategoriesByOrder, $internalItemQtyByOrder, $internalItemCogsByOrder): array {
            $grossSales = $this->cashOrderGrossSales($row);
            $marketplaceFees = $marketplaceFee($row);
            $affiliateFees = $affiliateFee($row);
            $cashPayout = round((float) ($row->final_income ?? 0), 2);
            $cogs = round((float) ($internalItemCogsByOrder[(int) $row->id] ?? 0), 2);
            $grossProfit = round($cashPayout - $cogs, 2);
            $targetNetMargin = self::TARGET_NET_MARGIN_RATE * 100;
            $targetNetMarginAmount = $cashPayout > 0 ? $cashPayout * self::TARGET_NET_MARGIN_RATE : 0.0;
            $breakEvenAdBudget = round(max(0, $grossProfit), 2);
            $targetAdBudget = round(max(0, $grossProfit - $targetNetMarginAmount), 2);
            $statusGroup = $this->cashOrderStatusGroup($row);
            $settlement = new MarketplaceOrderSettlement();
            $settlement->setRawAttributes([
                'commission_fee' => $row->commission_fee,
                'service_fee' => $row->service_fee,
                'transaction_fee' => $row->transaction_fee,
                'affiliate_fee' => $row->affiliate_fee,
                'activity_fee' => $row->activity_fee,
                'actual_shipping_fee' => $row->actual_shipping_fee ?? null,
                'shipping_cost_amount' => $row->shipping_cost_amount ?? null,
                'shipping_fee_subsidy' => $row->shipping_fee_subsidy ?? null,
                'shipping_insurance_fee' => $row->shipping_insurance_fee,
                'escrow_tax' => $row->escrow_tax,
                'raw_json' => $row->settlement_raw_json,
            ], true);
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
                'internal_categories' => $internalCategoriesByOrder[(int) $row->id] ?? ['Belum termapping'],
                'internal_category' => implode(', ', $internalCategoriesByOrder[(int) $row->id] ?? ['Belum termapping']),
                'internal_item_qty' => $internalItemQtyByOrder[(int) $row->id] ?? 0,
                'status' => (string) ($row->order_status ?: $row->status ?: '-'),
                'status_group' => $statusGroup['key'],
                'status_group_label' => $statusGroup['label'],
                'platform' => (string) ($row->platform ?? ''),
                'return_refund_status' => (string) ($row->return_refund_status ?: MarketplaceOrderSettlement::RETURN_STATUS_NONE),
                'refund_source' => (string) ($row->refund_source ?? ''),
                'reversal_amount' => round((float) ($row->reversal_amount ?? 0), 2),
                'reversal_source' => (string) ($row->reversal_source ?? ''),
                'payment_method' => $this->cashPaymentMethod($row),
                'settlement_status' => (string) ($row->data_status ?: 'not_settled'),
                'ordered_at' => $row->ordered_at,
                'settlement_time' => $row->settlement_time,
                'gross_sales' => round($grossSales, 2),
                'net_sales' => round($grossSales, 2),
                'buyer_payment_amount' => round($buyerPayment, 2),
                'cash_payout' => $cashPayout,
                'cogs' => $cogs,
                'gross_profit' => $grossProfit,
                'margin_before_ads' => $cashPayout > 0 ? round(($grossProfit / $cashPayout) * 100, 2) : null,
                'target_net_margin' => $targetNetMargin,
                'bep_ad_budget' => $breakEvenAdBudget,
                'bep_acos' => $cashPayout > 0 ? round(($breakEvenAdBudget / $cashPayout) * 100, 2) : null,
                'bep_roas' => $breakEvenAdBudget > 0 ? round($cashPayout / $breakEvenAdBudget, 2) : null,
                'target_ad_budget' => $targetAdBudget,
                'target_roas' => $targetAdBudget > 0 ? round($cashPayout / $targetAdBudget, 2) : null,
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
                'fee_breakdown' => $settlement->analyticsFeeBreakdown(),
            ];
        })->values()->all();

        $feeAnalysis = $perPage >= 50
            ? $this->canonicalFeeAnalysis($filters, $settlement)
            : ['totals' => [], 'types' => [], 'stores' => []];

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'summary' => $this->cashAggregate($filters, $settlement, true),
            'canonical_fee_totals' => $feeAnalysis['totals'],
            'fee_by_type' => $feeAnalysis['types'],
            'fee_by_store' => $feeAnalysis['stores'],
        ];
    }

    /**
     * Aggregate the canonical mapping for the active revenue table. This is
     * intentionally limited to the full-size revenue request; the eight small
     * tab-summary requests do not need to repeat the raw JSON scan.
     */
    private function canonicalFeeAnalysis(array $filters, string $settlement): array
    {
        $base = match ($settlement) {
            'all' => $this->allCashBase($filters),
            'unsettled' => $this->unsettledOrdersBase($filters),
            'shipped' => $this->cashStatusBase($filters, 'shipped'),
            'warehouse' => $this->cashStatusBase($filters, 'warehouse'),
            'confirm' => $this->cashStatusBase($filters, 'confirm'),
            'cancelled' => $this->cashStatusBase($filters, 'cancelled'),
            'return_refund' => $this->returnRefundBase($filters),
            default => $this->cashBase($filters),
        };
        if (in_array($settlement, ['all', 'cancelled'], true)) {
            $base->whereRaw($this->isRevenueStatus());
        }

        // Fee totals are sourced from settlement rows. Keep the chunk key
        // non-null because the cash bases intentionally use a left join for
        // orders that have not settled yet.
        $base->whereNotNull('ms.id');

        $totals = [];
        $burdenTotals = [];
        $stores = [];
        $base
            ->join('stores as fee_store', 'fee_store.id', '=', 'mo.store_id')
            ->select([
                'mo.store_id',
                'fee_store.name as fee_store_name',
                'ms.commission_fee',
                'ms.service_fee',
                'ms.transaction_fee',
                'ms.affiliate_fee',
                'ms.activity_fee',
                'ms.actual_shipping_fee',
                'ms.shipping_cost_amount',
                'ms.shipping_fee_subsidy',
                'ms.shipping_insurance_fee',
                'ms.escrow_tax',
                'ms.raw_json as settlement_raw_json',
            ])
            ->orderBy('ms.id')
            ->lazy(250)
            ->each(function ($row) use (&$totals, &$burdenTotals, &$stores): void {
                    $settlementModel = new MarketplaceOrderSettlement();
                    $settlementModel->setRawAttributes([
                        'commission_fee' => $row->commission_fee,
                        'service_fee' => $row->service_fee,
                        'transaction_fee' => $row->transaction_fee,
                        'affiliate_fee' => $row->affiliate_fee,
                        'activity_fee' => $row->activity_fee,
                        'actual_shipping_fee' => $row->actual_shipping_fee ?? null,
                        'shipping_cost_amount' => $row->shipping_cost_amount ?? null,
                        'shipping_fee_subsidy' => $row->shipping_fee_subsidy ?? null,
                        'shipping_insurance_fee' => $row->shipping_insurance_fee,
                        'escrow_tax' => $row->escrow_tax,
                        'raw_json' => $row->settlement_raw_json,
                    ], true);

                    foreach ($settlementModel->analyticsFeeBreakdown() as $item) {
                        $code = (string) ($item['code'] ?? 'other_fee');
                        $burden = in_array((string) ($item['burden'] ?? ''), ['seller', 'platform', 'logistics'], true)
                            ? (string) $item['burden']
                            : ($code === 'shipping_subsidy' ? 'platform' : 'seller');
                        // Selisih ongkir adalah derived seller burden. Jangan
                        // masukkan ke canonical totals karena shipping_cost
                        // dan shipping_subsidy sudah menjadi sumber totalnya.
                        if ($code !== 'shipping_difference') {
                            $totals[$code] ??= [
                                'code' => $code,
                                'label' => (string) ($item['label'] ?? $code),
                                'category' => (string) ($item['category'] ?? 'fee'),
                                'amount' => 0.0,
                                'count' => 0,
                            ];
                            $totals[$code]['amount'] += (float) ($item['amount'] ?? 0);
                            $totals[$code]['count']++;
                        }

                        $burdenKey = $burden.'|'.$code;
                        $burdenTotals[$burdenKey] ??= [
                            'code' => $code,
                            'label' => (string) ($item['label'] ?? $code),
                            'category' => (string) ($item['category'] ?? 'fee'),
                            'burden' => $burden,
                            'amount' => 0.0,
                            'count' => 0,
                            'details' => [],
                        ];
                        $burdenTotals[$burdenKey]['amount'] += (float) ($item['amount'] ?? 0);
                        $burdenTotals[$burdenKey]['count']++;
                        if ($code === 'other_fee') {
                            $detailSource = (string) ($item['source'] ?? 'settlement_fee_breakdown');
                            $detailKey = $detailSource.'|'.(string) ($item['label'] ?? $code);
                            $burdenTotals[$burdenKey]['details'][$detailKey] ??= [
                                'code' => $code,
                                'label' => (string) ($item['label'] ?? $code),
                                'source' => $detailSource,
                                'amount' => 0.0,
                                'count' => 0,
                            ];
                            $burdenTotals[$burdenKey]['details'][$detailKey]['amount'] += (float) ($item['amount'] ?? 0);
                            $burdenTotals[$burdenKey]['details'][$detailKey]['count']++;
                        }

                        $feeGroup = $code === 'affiliate_fee' || (string) ($item['category'] ?? '') === 'affiliate'
                            ? 'affiliate'
                            : $burden;

                        // Ongkir memiliki section tersendiri. Jangan campurkan
                        // biaya kirim ke performa fee per toko atau Seller fee.
                        if ($feeGroup === 'logistics') {
                            continue;
                        }

                        $storeKey = (string) ($row->store_id ?? 'unknown').'|'.$feeGroup;
                        $stores[$storeKey] ??= [
                            'store_id' => $row->store_id ?? 'unknown',
                            'store_name' => (string) ($row->fee_store_name ?: 'Toko tidak diketahui'),
                            'burden' => $burden,
                            'fee_group' => $feeGroup,
                            'cost_type' => 'fee',
                            'amount' => 0.0,
                            'count' => 0,
                        ];
                        $stores[$storeKey]['amount'] += (float) ($item['amount'] ?? 0);
                        $stores[$storeKey]['count']++;
                    }
            });

        foreach ($this->adsStoreSummary($filters) as $adRow) {
            $adCost = (float) ($adRow->ad_cost ?? 0);
            if ($adCost <= 0) {
                continue;
            }

            $storeKey = (string) ($adRow->store_id ?? 'unknown').'|ads';
            $stores[$storeKey] = [
                'store_id' => $adRow->store_id ?? 'unknown',
                'store_name' => (string) ($adRow->store_name ?: 'Toko tidak diketahui'),
                'burden' => 'seller',
                'fee_group' => 'ads',
                'cost_type' => 'ads',
                'source' => (string) ($adRow->ad_cost_source ?? 'none'),
                'amount' => round($adCost, 2),
                'count' => 0,
            ];
        }

        $normalize = function (array $item): array {
            $details = collect((array) ($item['details'] ?? []))
                ->map(fn (array $detail): array => array_merge($detail, [
                    'amount' => round((float) ($detail['amount'] ?? 0), 2),
                    'count' => (int) ($detail['count'] ?? 0),
                ]))
                ->sortByDesc('amount')
                ->values()
                ->all();

            return array_merge($item, [
                'amount' => round((float) $item['amount'], 2),
                'count' => (int) $item['count'],
                'details' => $details,
            ]);
        };

        return [
            'totals' => collect($totals)
                ->map($normalize)
                ->filter(fn (array $item): bool => $item['amount'] > 0)
                ->sortByDesc('amount')
                ->values()
                ->all(),
            'types' => collect($burdenTotals)
                ->map($normalize)
                ->filter(fn (array $item): bool => $item['amount'] > 0)
                ->sortByDesc('amount')
                ->values()
                ->all(),
            'stores' => collect($stores)
                ->map($normalize)
                ->filter(fn (array $item): bool => $item['amount'] > 0)
                ->sortByDesc('amount')
                ->values()
                ->all(),
        ];
    }

    /**
     * Resolve the internal item categories for only the paginated orders shown
     * in the drill-down. Both the canonical and legacy item foreign keys are
     * supported because historical marketplace rows can use either one.
     *
     * @param  array<int, int>  $orderIds
     * @return array<int, array<int, string>>
     */
    private function internalCategoriesByOrderIds(array $orderIds): array
    {
        $orderIds = collect($orderIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $categories = $orderIds->mapWithKeys(fn (int $id): array => [$id => []])->all();
        if ($orderIds->isEmpty()) {
            return $categories;
        }

        $items = DB::table('marketplace_order_items as oi')
            ->leftJoin('items as i', function ($join): void {
                $join->on('i.id', '=', 'oi.internal_item_id')
                    ->orOn('i.id', '=', 'oi.item_id');
            })
            ->leftJoin('item_categories as ic', 'ic.id', '=', 'i.item_category_id')
            ->where(function ($query) use ($orderIds): void {
                $query->whereIn('oi.marketplace_order_id', $orderIds->all())
                    ->orWhereIn('oi.order_id', $orderIds->all());
            })
            ->select([
                'oi.marketplace_order_id',
                'oi.order_id',
                'ic.code as category_code',
            ])
            ->get();

        foreach ($items as $item) {
            $category = trim((string) ($item->category_code ?? ''));
            if ($category === '') {
                continue;
            }

            foreach (array_unique([(int) $item->marketplace_order_id, (int) $item->order_id]) as $orderId) {
                if ($orderId <= 0 || ! array_key_exists($orderId, $categories)) {
                    continue;
                }

                if (! in_array($category, $categories[$orderId], true)) {
                    $categories[$orderId][] = $category;
                }
            }
        }

        foreach ($categories as $orderId => $orderCategories) {
            if ($orderCategories === []) {
                $categories[$orderId] = ['Belum termapping'];
            }
        }

        return $categories;
    }

    /**
     * Sum ordered quantities for the paginated drill-down rows. The query
     * reads both canonical and legacy foreign keys, but each item row is
     * counted only once.
     *
     * @param  array<int, int>  $orderIds
     * @return array<int, int>
     */
    private function internalItemQtyByOrderIds(array $orderIds): array
    {
        $orderIds = collect($orderIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $quantities = $orderIds->mapWithKeys(fn (int $id): array => [$id => 0])->all();
        if ($orderIds->isEmpty()) {
            return $quantities;
        }

        $items = DB::table('marketplace_order_items as oi')
            ->where(function ($query) use ($orderIds): void {
                $query->whereIn('oi.marketplace_order_id', $orderIds->all())
                    ->orWhereIn('oi.order_id', $orderIds->all());
            })
            ->select([
                'oi.marketplace_order_id',
                'oi.order_id',
                'oi.qty',
            ])
            ->get();

        foreach ($items as $item) {
            $qty = max(0, (int) ($item->qty ?? 0));
            foreach (array_unique([(int) $item->marketplace_order_id, (int) $item->order_id]) as $orderId) {
                if ($orderId <= 0 || ! array_key_exists($orderId, $quantities)) {
                    continue;
                }

                $quantities[$orderId] += $qty;
            }
        }

        return $quantities;
    }

    /**
     * Sum the internal item cost for only the paginated drill-down rows.
     * HPP snapshots are stored per unit; when a line total snapshot exists,
     * prefer that total to avoid multiplying it twice.
     *
     * @param  array<int, int>  $orderIds
     * @return array<int, float>
     */
    private function internalItemCogsByOrderIds(array $orderIds): array
    {
        $orderIds = collect($orderIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $cogs = $orderIds->mapWithKeys(fn (int $id): array => [$id => 0.0])->all();
        if ($orderIds->isEmpty()) {
            return $cogs;
        }

        $items = DB::table('marketplace_order_items as oi')
            ->where(function ($query) use ($orderIds): void {
                $query->whereIn('oi.marketplace_order_id', $orderIds->all())
                    ->orWhereIn('oi.order_id', $orderIds->all());
            })
            ->select([
                'oi.marketplace_order_id',
                'oi.order_id',
                'oi.qty',
                'oi.hpp_snapshot',
                'oi.hpp_total_snapshot',
            ])
            ->get();

        foreach ($items as $item) {
            $qty = max(0, (int) ($item->qty ?? 0));
            $hppTotalSnapshot = (float) ($item->hpp_total_snapshot ?? 0);
            $hppSnapshot = (float) ($item->hpp_snapshot ?? 0);
            $lineCogs = $hppTotalSnapshot > 0 ? $hppTotalSnapshot : ($hppSnapshot * $qty);

            if ($lineCogs <= 0) {
                continue;
            }

            foreach (array_unique([(int) $item->marketplace_order_id, (int) $item->order_id]) as $orderId) {
                if ($orderId <= 0 || ! array_key_exists($orderId, $cogs)) {
                    continue;
                }

                $cogs[$orderId] += $lineCogs;
            }
        }

        return collect($cogs)
            ->map(fn (float $amount): float => round($amount, 2))
            ->all();
    }

    private function cashOrderGrossSales($row): float
    {
        $grossSales = $this->cashOrderGrossSalesBeforeSellerDiscount($row);

        return round(max(0, $grossSales - $this->cashOrderSellerDiscount($row)), 2);
    }

    private function cashOrderGrossSalesBeforeSellerDiscount($row): float
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

        return round($grossSales, 2);
    }

    private function cashOrderSellerDiscount($row): float
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

        $sellerVoucher = max((float) ($row->seller_voucher ?? 0), 0);
        if ($sellerVoucher <= 0) {
            foreach ($sources as $source) {
                $sellerVoucher = (float) ($source['voucher_from_seller'] ?? $source['seller_voucher'] ?? 0);
                if ($sellerVoucher > 0) {
                    break;
                }
            }
        }

        return round(max(0, $sellerVoucher), 2);
    }

    private function cashPaymentMethod($row): ?string
    {
        $paymentMethod = trim((string) ($row->payment_method ?? ''));
        if ($paymentMethod !== '') {
            return $paymentMethod;
        }

        $rawOrder = $row->order_raw_json ?? null;
        if (! is_array($rawOrder)) {
            $rawOrder = json_decode((string) $rawOrder, true);
        }
        if (! is_array($rawOrder)) {
            return null;
        }

        foreach (['payment_method', 'pay_method', 'buyer_payment_method', 'payment_info.payment_method'] as $key) {
            $value = data_get($rawOrder, $key);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function cashOrderStatusGroup($row): array
    {
        $status = strtoupper(trim((string) ($row->order_status ?: $row->status ?: '')));

        if (in_array((string) ($row->return_refund_status ?? ''), MarketplaceOrderSettlement::RETURN_REFUND_STATUSES, true)
            || (int) ($row->has_return_refund ?? 0) === 1
            || in_array($status, ['TO_RETURN', 'RETURNING', 'RETURNED', 'REFUND', 'REFUNDED'], true)) {
            return ['key' => 'return_refund', 'label' => 'Return / Refund'];
        }

        if (in_array($status, ['CANCELLED', 'CANCELED', 'BATAL', 'IN_CANCEL'], true)) {
            return ['key' => 'cancelled', 'label' => 'Dibatalkan'];
        }

        if ($status === 'COMPLETED') {
            return ['key' => 'completed', 'label' => 'Completed · '.(! empty($row->settlement_time) ? 'Sudah cair' : 'Belum cair')];
        }

        if ($status === 'TO_CONFIRM_RECEIVE') {
            return ['key' => 'confirm', 'label' => 'Menunggu konfirmasi'];
        }

        if ((int) ($row->has_warehouse_booking ?? 0) === 1) {
            return ['key' => 'warehouse', 'label' => 'Disimpan di Gudang Shopee'];
        }

        if (in_array($status, ['READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'READY_TO_HANDOVER', 'TO_RETURN'], true)) {
            return ['key' => 'shipped', 'label' => 'Masih dikirim'];
        }

        return ['key' => 'other', 'label' => 'Status lainnya'];
    }

    private function cashStatusBase(array $filters, string $group)
    {
        $status = "UPPER(COALESCE(NULLIF(mo.order_status, ''), mo.status, ''))";
        $base = $this->allCashBase($filters);

        return match ($group) {
            'shipped' => $base
                ->whereRaw("{$status} IN ('READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'READY_TO_HANDOVER')")
                ->whereRaw('NOT '.$this->returnRefundExistsSql()),
            'warehouse' => $base
                ->whereRaw($this->warehouseBookingExistsSql())
                ->whereRaw("{$status} NOT IN ('CANCELLED', 'CANCELED', 'CANCELLED_BEFORE_SHIPPING', 'BATAL', 'IN_CANCEL', 'TO_RETURN', 'RETURNING', 'RETURNED', 'REFUND', 'REFUNDED')")
                ->whereRaw('NOT '.$this->returnRefundExistsSql()),
            'confirm' => $base
                ->whereRaw("{$status} = 'TO_CONFIRM_RECEIVE'")
                ->whereRaw('NOT '.$this->returnRefundExistsSql()),
            'cancelled' => $base
                ->whereRaw("{$status} IN ('CANCELLED', 'CANCELED', 'CANCELLED_BEFORE_SHIPPING', 'BATAL', 'IN_CANCEL')")
                ->whereRaw('NOT '.$this->returnRefundExistsSql()),
            'return_refund' => $base->where(function ($query) use ($status) {
                $query->whereRaw($this->returnRefundExistsSql())
                    ->orWhereRaw("{$status} IN ('TO_RETURN', 'RETURNING', 'RETURNED', 'REFUND', 'REFUNDED')");
            }),
            default => $base,
        };
    }

    private function returnRefundBase(array $filters)
    {
        $status = "UPPER(COALESCE(NULLIF(mo.order_status, ''), mo.status, ''))";
        $fromTimestamp = Carbon::parse($filters['date_from'])->startOfDay()->timestamp;
        $toTimestamp = Carbon::parse($filters['date_to'])->endOfDay()->timestamp;

        return DB::table('marketplace_orders as mo')
            ->leftJoin('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
            ->whereNotNull('mo.ordered_at')
            ->whereIn('mo.store_id', $filters['store_ids'])
            ->where(function ($query) use ($status, $filters, $fromTimestamp, $toTimestamp): void {
                $query->whereExists(function ($subQuery) use ($fromTimestamp, $toTimestamp): void {
                    $subQuery->selectRaw('1')
                        ->from('marketplace_returns as mr_event')
                        ->whereColumn('mr_event.store_id', 'mo.store_id')
                        ->where(function ($orderQuery): void {
                            $orderQuery
                                ->whereColumn('mr_event.order_sn', 'mo.channel_order_id')
                                ->orWhereColumn('mr_event.order_sn', 'mo.external_order_id');
                        })
                        ->whereBetween('mr_event.create_time', [$fromTimestamp, $toTimestamp]);
                })->orWhere(function ($financeQuery) use ($filters): void {
                    $financeQuery
                        ->where(function ($query): void {
                            $query->whereRaw('ABS(COALESCE(ms.drc_adjustable_refund, 0)) > 0')
                                ->orWhereIn('ms.return_refund_status', MarketplaceOrderSettlement::RETURN_REFUND_STATUSES);
                        })
                        ->whereExists(function ($channelQuery): void {
                            $channelQuery->selectRaw('1')
                                ->from('stores as refund_store')
                                ->join('channels as refund_channel', 'refund_channel.id', '=', 'refund_store.channel_id')
                                ->whereColumn('refund_store.id', 'mo.store_id')
                                ->whereRaw('LOWER(refund_channel.code) = ?', ['tiktok']);
                        })
                        ->whereBetween(DB::raw('COALESCE(ms.settlement_time, mo.ordered_at)'), [
                            $filters['date_from'].' 00:00:00',
                            $filters['date_to'].' 23:59:59',
                        ]);
                })->orWhere(function ($statusQuery) use ($status, $filters): void {
                    $statusQuery
                        ->whereRaw("{$status} IN ('TO_RETURN', 'RETURNING', 'RETURNED', 'REFUND', 'REFUNDED')")
                        ->whereBetween('mo.ordered_at', [
                            $filters['date_from'].' 00:00:00',
                            $filters['date_to'].' 23:59:59',
                        ]);
                });
            });
    }

    private function cashOrderRevenueAggregates(array $filters, string $settlement, bool $includePreShipment = false): array
    {
        $base = match ($settlement) {
            'all' => $this->allCashBase($filters),
            'unsettled' => $includePreShipment ? $this->unsettledOrdersBase($filters) : $this->unsettledBase($filters),
            'shipped' => $this->cashStatusBase($filters, 'shipped'),
            'warehouse' => $this->cashStatusBase($filters, 'warehouse'),
            'confirm' => $this->cashStatusBase($filters, 'confirm'),
            'cancelled' => $this->cashStatusBase($filters, 'cancelled'),
            'return_refund' => $this->returnRefundBase($filters),
            default => $this->cashBase($filters),
        };
        $netBase = clone $base;
        if (in_array($settlement, ['all', 'cancelled'], true)) {
            $netBase->whereRaw($this->isRevenueStatus());
        }
        $selectFields = [
                'mo.total_amount',
                'mo.total_paid_customer',
                'mo.subtotal_items',
                'mo.raw_json as order_raw_json',
                'ms.buyer_payment_amount',
                'ms.seller_voucher',
                'ms.raw_json as settlement_raw_json',
            ];
        $grossSales = $base
            ->select($selectFields)
            ->orderBy('mo.id')
            ->lazy(250)
            ->sum(fn ($row) => $this->cashOrderGrossSalesBeforeSellerDiscount($row));
        $netSales = $netBase
            ->select($selectFields)
            ->orderBy('mo.id')
            ->lazy(250)
            ->sum(fn ($row) => $this->cashOrderGrossSales($row));

        return [
            'gross_sales' => round((float) $grossSales, 2),
            'net_sales' => round((float) $netSales, 2),
        ];
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
                $filters['date_from'].' 00:00:00',
                $filters['date_to'].' 23:59:59',
            ])
            ->whereIn('mo.store_id', $filters['store_ids'])
            ->selectRaw("SUM({$hppExpression}) AS hpp_total")
            ->selectRaw("SUM(CASE WHEN ms.data_status = ? THEN {$hppExpression} ELSE 0 END) AS hpp_settled", [MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE])
            ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(NULLIF(mo.order_status, ''), mo.status, '')) IN ('READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'READY_TO_HANDOVER', 'TO_CONFIRM_RECEIVE', 'COMPLETED') THEN {$hppExpression} ELSE 0 END) AS hpp_shipped")
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
                $filters['date_from'].' 00:00:00',
                $filters['date_to'].' 23:59:59',
            ])
            ->whereIn('mo.store_id', $filters['store_ids'])
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
            $isShipped = in_array($status, ['READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'READY_TO_HANDOVER', 'TO_CONFIRM_RECEIVE', 'COMPLETED'], true);
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
            ->whereIn('mr.store_id', $filters['store_ids'])
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
                    $ordersByKey[$order->store_id.'|'.$key] = $order;
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
            $order = $ordersByKey[$return->store_id.'|'.(string) $return->order_sn] ?? null;
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
                ->whereIn('mr.store_id', $filters['store_ids'])
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

            // Finance statements can contain a refund adjustment even when
            // the separate return feed has no row for the TikTok shop. Once
            // the canonical settlement column is backfilled, expose that
            // financial case in the same drawer as marketplace return rows.
            $existingOrderKeys = $rows
                ->map(fn (array $row): string => $row['store_id'].'|'.(string) ($row['order_sn'] ?? ''))
                ->filter(fn (string $key): bool => ! str_ends_with($key, '|'))
                ->flip();
            $financeRows = DB::table('marketplace_orders as mo')
                ->join('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
                ->join('stores as st', 'st.id', '=', 'mo.store_id')
                ->join('channels as ch', 'ch.id', '=', 'st.channel_id')
                ->whereIn('mo.store_id', $filters['store_ids'])
                ->whereRaw('LOWER(ch.code) = ?', ['tiktok'])
                        ->where(function ($query): void {
                            $query->whereRaw('ABS(COALESCE(ms.drc_adjustable_refund, 0)) > 0')
                                ->orWhereIn('ms.return_refund_status', MarketplaceOrderSettlement::RETURN_REFUND_STATUSES);
                        })
                ->whereBetween(DB::raw('COALESCE(ms.settlement_time, mo.ordered_at)'), [
                    $filters['date_from'].' 00:00:00',
                    $filters['date_to'].' 23:59:59',
                ])
                ->select([
                    'mo.id as order_id',
                    'mo.store_id',
                    'st.name as store_name',
                    'mo.channel_order_id',
                    'mo.external_order_id',
                    'mo.order_status',
                    'mo.status',
                    'mo.ordered_at',
                    'ms.id as settlement_id',
                    'ms.settlement_time',
                    'ms.drc_adjustable_refund',
                    'ms.return_refund_status',
                    'ms.reversal_amount',
                ])
                ->orderByDesc('ms.settlement_time')
                ->orderByDesc('mo.ordered_at')
                ->limit(1000)
                ->get()
                ->filter(function ($row) use ($existingOrderKeys): bool {
                    $key = $row->store_id.'|'.(string) ($row->channel_order_id ?: $row->external_order_id ?: '');

                    return $key !== $row->store_id.'|' && ! $existingOrderKeys->has($key);
                })
                ->map(fn ($row): array => [
                    'id' => -1 * (int) $row->settlement_id,
                    'reference' => 'TT-REFUND-'.($row->channel_order_id ?: $row->external_order_id ?: $row->order_id),
                    'order_sn' => (string) ($row->channel_order_id ?: $row->external_order_id ?: '-'),
                    'store_id' => (int) $row->store_id,
                    'store_name' => (string) ($row->store_name ?: 'Tanpa toko'),
                    'status' => (string) ($row->order_status ?: $row->status ?: '—'),
                    'kind' => (string) $row->return_refund_status === MarketplaceOrderSettlement::RETURN_STATUS_REVERSAL ? 'reversal' : 'refund',
                    'reason' => (string) $row->return_refund_status === MarketplaceOrderSettlement::RETURN_STATUS_REVERSAL
                        ? 'TikTok Finance settlement reversal'
                        : 'TikTok Finance refund adjustment',
                    'amount' => round(max(abs((float) $row->drc_adjustable_refund), (float) ($row->reversal_amount ?? 0)), 2),
                    'tracking_number' => '',
                    'event_time' => $row->settlement_time ?: $row->ordered_at,
                    'updated_at' => $row->settlement_time ?: $row->ordered_at,
                    'source' => 'tiktok_finance',
                ]);
            $rows = $rows->concat($financeRows)->values();
        } else {
            $returnRows = DB::table('marketplace_returns as mr')
                ->join('stores as st', 'st.id', '=', 'mr.store_id')
                ->whereBetween('mr.create_time', [$fromTimestamp, $toTimestamp])
                ->whereIn('mr.store_id', $filters['store_ids'])
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
                ->whereBetween('mo.ordered_at', [$filters['date_from'].' 00:00:00', $filters['date_to'].' 23:59:59'])
                ->whereIn('mo.store_id', $filters['store_ids'])
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
                    'reference' => 'RTS-'.(string) ($row->channel_order_id ?: $row->external_order_id ?: $row->id),
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

            $rows = $returnRows->concat($orderRows)->unique(fn (array $row) => $row['source'].':'.$row['reference'])->values();
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
        $from = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['date_to'] ?? now()->toDateString();

        try {
            $from = Carbon::parse($from)->toDateString();
            $to = Carbon::parse($to)->toDateString();
        } catch (\Throwable) {
            $from = now()->startOfMonth()->toDateString();
            $to = now()->toDateString();
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $storeId = ! empty($filters['store_id']) ? (int) $filters['store_id'] : null;
        $platform = strtolower(trim((string) ($filters['platform'] ?? '')));
        $platform = in_array($platform, ['shopee', 'tiktok'], true) ? $platform : null;
        $storeScope = DB::table('stores as analytics_scope_store')
            ->when($platform, function ($query) use ($platform) {
                $query->join('channels as analytics_scope_channel', 'analytics_scope_channel.id', '=', 'analytics_scope_store.channel_id')
                    ->whereRaw('LOWER(analytics_scope_channel.code) = ?', [$platform]);
            })
            ->when($storeId,
                fn ($query) => $query->where('analytics_scope_store.id', $storeId),
                fn ($query) => $query->where('analytics_scope_store.is_active', true)
            )
            ->pluck('analytics_scope_store.id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        return [
            'store_id' => $storeId,
            'platform' => $platform,
            'store_ids' => $storeScope,
            'date_from' => $from,
            'date_to' => $to,
            'compare_mode' => in_array($filters['compare_mode'] ?? 'prev_month', ['prev_period', 'prev_month', 'prev_quarter', 'prev_year'], true)
                ? ($filters['compare_mode'] ?? 'prev_month')
                : 'prev_month',
        ];
    }

    private function previousRange(array $filters): array
    {
        $from = Carbon::parse($filters['date_from']);
        $to = Carbon::parse($filters['date_to']);

        if ($filters['compare_mode'] === 'prev_month') {
            // Keep the active date numbers and shift both endpoints one month
            // back; Carbon clamps dates such as 31 to the last valid day.
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
            ->join('marketplace_orders as fmo', 'fmo.id', '=', 'oi.marketplace_order_id')
            ->select('oi.marketplace_order_id')
            ->selectRaw('SUM(oi.hpp_snapshot * CASE WHEN oi.qty > 0 THEN oi.qty ELSE 0 END) AS hpp')
            ->selectRaw('SUM(CASE WHEN oi.qty > 0 THEN oi.qty ELSE 0 END) AS qty')
            ->where('oi.data_status', 'valid')
            ->where('oi.hpp_snapshot', '>', 0)
            ->where('fmo.financial_data_status', MarketplaceFinancialDataQualityService::ORDER_READY)
            ->whereRaw('COALESCE(fmo.ordered_at, fmo.order_date) IS NOT NULL')
            ->whereBetween(DB::raw('COALESCE(fmo.ordered_at, fmo.order_date)'), [
                $filters['date_from'].' 00:00:00',
                $filters['date_to'].' 23:59:59',
            ])
            ->whereIn('fmo.store_id', $filters['store_ids'])
            ->groupBy('oi.marketplace_order_id');

        $query = DB::table('marketplace_orders as mo')
            ->join('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
            ->joinSub($itemCosts, 'ic', fn ($join) => $join->on('ic.marketplace_order_id', '=', 'mo.id'))
            ->join('stores as st', 'st.id', '=', 'mo.store_id')
            ->where('mo.financial_data_status', MarketplaceFinancialDataQualityService::ORDER_READY)
            ->where('ms.data_status', MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE)
            ->whereRaw($this->isRevenueStatus())
            ->whereRaw('COALESCE(mo.ordered_at, mo.order_date) IS NOT NULL');

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
            ->selectRaw('DATE(COALESCE(mo.ordered_at, mo.order_date)) AS date')
            ->selectRaw($this->aggregateSelect(false))
            ->groupByRaw('DATE(COALESCE(mo.ordered_at, mo.order_date))')
            ->orderByRaw('DATE(COALESCE(mo.ordered_at, mo.order_date))')
            ->get()
            ->map(fn ($row) => array_merge(['date' => $row->date], $this->normalizeAggregate($row)))
            ->values()
            ->all();
    }

    private function cashAggregate(array $filters, string $settlement = 'settled', bool $includePreShipment = false): array
    {
        $fees = collect(self::MARKETPLACE_FEE_FIELDS)
            ->map(fn (string $field) => "COALESCE(ms.{$field}, 0)")
            ->implode(' + ');
        $affiliateFees = collect(self::AFFILIATE_FEE_FIELDS)
            ->map(fn (string $field) => "COALESCE(ms.{$field}, 0)")
            ->implode(' + ');

        $base = match ($settlement) {
            'all' => $this->allCashBase($filters),
            'unsettled' => $includePreShipment ? $this->unsettledOrdersBase($filters) : $this->unsettledBase($filters),
            'shipped' => $this->cashStatusBase($filters, 'shipped'),
            'warehouse' => $this->cashStatusBase($filters, 'warehouse'),
            'confirm' => $this->cashStatusBase($filters, 'confirm'),
            'cancelled' => $this->cashStatusBase($filters, 'cancelled'),
            'return_refund' => $this->returnRefundBase($filters),
            default => $this->cashBase($filters),
        };
        if ($settlement === 'all') {
            $base->whereRaw($this->isRevenueStatus());
        }

        $row = $base
            ->selectRaw('COUNT(DISTINCT mo.id) AS cash_order_count')
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
        $revenueTotals = $this->cashOrderRevenueAggregates($filters, $settlement, $includePreShipment);

        return [
            'cash_order_count' => (int) ($row->cash_order_count ?? 0),
            'cash_gross_sales' => $revenueTotals['gross_sales'],
            'cash_order_revenue' => $revenueTotals['net_sales'],
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
            ->whereIn('store_id', $filters['store_ids'])
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
                ->whereIn('mo.store_id', $filters['store_ids'])
                ->select([
                    'mo.id',
                    'mo.store_id',
                    'mo.channel_order_id',
                    'mo.external_order_id',
                    'ms.data_status as settlement_status',
                    'ms.drc_adjustable_refund as settlement_refund_amount',
                ])
                ->get();

        $settlementByKey = [];
        foreach ($orders as $order) {
            foreach ([(string) $order->channel_order_id, (string) $order->external_order_id] as $key) {
                if ($key !== '') {
                    $settlementByKey[$order->store_id.'|'.$key] = [
                        'status' => $order->settlement_status,
                        'refund_amount' => max(0, (float) ($order->settlement_refund_amount ?? 0)),
                    ];
                }
            }
        }

        $seenReturnOrders = [];
        $settledReturnOrders = 0;
        $unsettledReturnOrders = 0;
        $returnRefundAmount = 0.0;
        $returnRefundCount = $returns->count();
        foreach ($returns->groupBy(function ($return): string {
            $orderKey = (string) ($return->order_sn ?? '');

            return $return->store_id.'|'.($orderKey !== '' ? $orderKey : 'return:'.$return->id);
        }) as $key => $returnRows) {
            $seenReturnOrders[$key] = true;
            $settlement = $settlementByKey[$key] ?? null;
            if (($settlement['status'] ?? null) === MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE) {
                $settledReturnOrders++;
            } else {
                $unsettledReturnOrders++;
            }

            // Prefer the actual settlement refund. amount_before_discount is
            // only a fallback because it is the pre-discount item value.
            $returnRefundAmount += ($settlement['refund_amount'] ?? 0) > 0
                ? (float) $settlement['refund_amount']
                : (float) $returnRows->sum(fn ($row) => (float) ($row->amount_before_discount ?? 0));
        }

        // TikTok Finance carries refund adjustments in the order statement,
        // while the marketplace_returns feed may be unavailable or empty for
        // a shop. Use the canonical settlement amount as a fallback so the
        // analytics period still reports the financial refund.
        $financeReturns = DB::table('marketplace_orders as mo')
            ->join('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
            ->join('stores as st', 'st.id', '=', 'mo.store_id')
            ->join('channels as ch', 'ch.id', '=', 'st.channel_id')
            ->whereIn('mo.store_id', $filters['store_ids'])
            ->whereRaw('LOWER(ch.code) = ?', ['tiktok'])
            ->where(function ($query): void {
                $query->whereRaw('ABS(COALESCE(ms.drc_adjustable_refund, 0)) > 0')
                    ->orWhereIn('ms.return_refund_status', MarketplaceOrderSettlement::RETURN_REFUND_STATUSES);
            })
            ->whereBetween(DB::raw('COALESCE(ms.settlement_time, mo.ordered_at)'), [
                $filters['date_from'].' 00:00:00',
                $filters['date_to'].' 23:59:59',
            ])
            ->select([
                'mo.store_id',
                'mo.channel_order_id',
                'mo.external_order_id',
                'ms.data_status as settlement_status',
                'ms.drc_adjustable_refund as settlement_refund_amount',
            ])
            ->get();

        foreach ($financeReturns as $financeReturn) {
            $orderKey = trim((string) ($financeReturn->channel_order_id ?: $financeReturn->external_order_id ?: ''));
            if ($orderKey === '') {
                continue;
            }

            $key = $financeReturn->store_id.'|'.$orderKey;
            if (isset($seenReturnOrders[$key])) {
                continue;
            }

            $seenReturnOrders[$key] = true;
            if ($financeReturn->settlement_status === MarketplaceFinancialDataQualityService::SETTLEMENT_COMPLETE) {
                $settledReturnOrders++;
            } else {
                $unsettledReturnOrders++;
            }
            $returnRefundCount++;
            $returnRefundAmount += abs((float) $financeReturn->settlement_refund_amount);
        }

        return [
            'return_refund_count' => $returnRefundCount,
            'return_refund_order_count' => count($seenReturnOrders),
            'return_refund_settled_order_count' => $settledReturnOrders,
            'return_refund_unsettled_order_count' => $unsettledReturnOrders,
            'return_refund_amount' => round($returnRefundAmount, 2),
        ];
    }

    private function cashBase(array $filters)
    {
        $status = "UPPER(COALESCE(NULLIF(mo.order_status, ''), mo.status, ''))";

        return $this->applyDateAndStoreFilters(
            DB::table('marketplace_orders as mo')
                ->join('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
                ->whereNotNull('ms.settlement_time')
                ->whereRaw($this->isRevenueStatus())
                ->whereRaw("{$status} <> 'TO_CONFIRM_RECEIVE'"),
            $filters,
            'mo'
        );
    }

    private function allCashBase(array $filters)
    {
        return $this->applyDateAndStoreFilters(
            DB::table('marketplace_orders as mo')
                ->leftJoin('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
                ->whereNotNull('mo.ordered_at'),
            $filters,
            'mo'
        );
    }

    private function unsettledBase(array $filters)
    {
        return $this->unsettledOrdersBase($filters);
    }

    private function unsettledOrdersBase(array $filters)
    {
        $status = "UPPER(COALESCE(NULLIF(mo.order_status, ''), mo.status, ''))";

        return $this->applyDateAndStoreFilters(
            DB::table('marketplace_orders as mo')
                ->leftJoin('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
                ->whereRaw($this->isRevenueStatus())
                ->where(function ($query) use ($status) {
                    $query->whereNull('ms.settlement_time')
                        ->orWhereRaw("{$status} = 'TO_CONFIRM_RECEIVE'");
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
        $returnRefunds = $this->returnRefundStoreSummary($filters);
        $unsettled = $this->unsettledStoreSummary($filters);
        $ids = $financial->keys()
            ->merge($operational->keys())
            ->merge($ads->keys())
            ->merge($returnRefunds->keys())
            ->merge($unsettled->keys())
            ->unique();

        return $ids->map(function (string $id) use ($financial, $operational, $ads, $returnRefunds, $unsettled) {
            $finance = $financial->get($id);
            $ops = $operational->get($id);
            $ad = $ads->get($id);
            $pending = $unsettled->get($id, ['order_count' => 0, 'gmv' => 0.0]);
            $returnRefund = $returnRefunds->get($id, ['count' => 0, 'amount' => 0.0]);
            $aggregate = $this->normalizeAggregate($finance);

            return $this->withRates($this->applyAdCost(array_merge([
                'store_id' => (int) $id,
                'store_name' => $finance?->store_name ?? $ops?->store_name ?? $ad?->store_name ?? 'Tanpa toko',
                'placed_order_count' => (int) ($ops->placed_order_count ?? 0),
                'order_total' => (int) ($ops->order_total ?? 0),
                'ready_to_ship_count' => (int) ($ops->ready_to_ship_count ?? 0),
                'in_transit_count' => (int) ($ops->in_transit_count ?? 0),
                'shipping_count' => (int) ($ops->shipping_count ?? 0),
                'confirmation_count' => (int) ($ops->confirmation_count ?? 0),
                'shipped_count' => (int) ($ops->shipped_count ?? 0),
                'completed_count' => (int) ($ops->completed_count ?? 0),
                'cancelled_count' => (int) ($ops->cancelled_count ?? 0),
                'cancelled_amount' => round((float) ($ops->cancelled_amount ?? 0), 2),
                'return_refund_count' => (int) ($returnRefund['count'] ?? 0),
                'return_refund_amount' => round((float) ($returnRefund['amount'] ?? 0), 2),
                'gmv' => round((float) ($ops->gmv ?? 0), 2),
                'unsettled_order_count' => (int) ($pending['order_count'] ?? 0),
                'unsettled_gmv' => round((float) ($pending['gmv'] ?? 0), 2),
            ], $aggregate), [
                'ad_cost_before_tax' => (float) ($ad->ad_cost_before_tax ?? 0),
                'ad_cost_vat' => (float) ($ad->ad_cost_vat ?? 0),
                'ad_cost' => (float) ($ad->ad_cost ?? 0),
                'ad_cost_source' => (string) ($ad->ad_cost_source ?? 'none'),
            ]));
        })->sortByDesc('gross_sales')->values()->all();
    }

    private function operationalAggregate(array $filters): array
    {
        $row = $this->operationalBase($filters)
            ->selectRaw($this->operationalSelect())
            ->first();

        return [
            'placed_order_count' => (int) ($row->placed_order_count ?? 0),
            'order_total' => (int) ($row->order_total ?? 0),
            'ready_to_ship_count' => (int) ($row->ready_to_ship_count ?? 0),
            'in_transit_count' => (int) ($row->in_transit_count ?? 0),
            'shipping_count' => (int) ($row->shipping_count ?? 0),
            'confirmation_count' => (int) ($row->confirmation_count ?? 0),
            'shipped_count' => (int) ($row->shipped_count ?? 0),
            'completed_count' => (int) ($row->completed_count ?? 0),
            'cancelled_count' => (int) ($row->cancelled_count ?? 0),
            'cancelled_amount' => round((float) ($row->cancelled_amount ?? 0), 2),
            'gmv' => round((float) ($row->gmv ?? 0), 2),
        ];
    }

    private function productQuantityAggregate(array $filters): array
    {
        $base = DB::table('marketplace_orders as mo')
            ->whereNotNull('mo.ordered_at')
            ->whereRaw($this->isRevenueStatus())
            ->whereBetween('mo.ordered_at', [
                $filters['date_from'].' 00:00:00',
                $filters['date_to'].' 23:59:59',
            ])
            ->whereIn('mo.store_id', $filters['store_ids']);

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
            ->whereIn('mr.store_id', $filters['store_ids'])
            ->sum(DB::raw('CASE WHEN ri.return_item_quantity > 0 THEN ri.return_item_quantity ELSE 0 END'));

        return [
            // The base query already excludes return/refund orders. Keep
            // returned units as a separate audit metric instead of adding
            // them back into "Produk Terjual".
            'total' => $total,
            'settled' => $settled,
            'unsettled' => $unsettled,
            'return_refund' => $returnQty,
        ];
    }

    private function operationalDaily(array $filters): array
    {
        $itemGross = 'CASE'
            .' WHEN COALESCE(oi.line_net_amount, 0) > 0 THEN oi.line_net_amount'
            .' WHEN COALESCE(oi.price_after_discount, 0) > 0 THEN oi.price_after_discount * COALESCE(oi.qty, 0)'
            .' WHEN COALESCE(oi.line_gross_amount, 0) > 0 THEN oi.line_gross_amount'
            .' WHEN COALESCE(oi.price, 0) > 0 THEN oi.price * COALESCE(oi.qty, 0)'
            .' ELSE 0 END';
        $itemTotals = DB::table('marketplace_order_items as oi')
            ->selectRaw('COALESCE(oi.marketplace_order_id, oi.order_id) AS order_key')
            ->selectRaw('SUM(CASE WHEN oi.qty > 0 THEN oi.qty ELSE 0 END) AS qty')
            ->selectRaw("SUM({$itemGross}) AS gross_sales")
            ->selectRaw('SUM(CASE WHEN oi.hpp_total_snapshot > 0 THEN oi.hpp_total_snapshot WHEN oi.hpp_snapshot > 0 THEN oi.hpp_snapshot * CASE WHEN oi.qty > 0 THEN oi.qty ELSE 0 END ELSE 0 END) AS hpp')
            ->groupByRaw('COALESCE(oi.marketplace_order_id, oi.order_id)');
        $gross = $this->orderValueExpression();
        $salesGross = 'CASE WHEN COALESCE(od_items.gross_sales, 0) > 0 THEN od_items.gross_sales ELSE CASE WHEN COALESCE(mo.subtotal_items, 0) > 0 THEN mo.subtotal_items ELSE COALESCE(mo.total_amount, 0) END END';
        $buyerPaid = 'COALESCE(NULLIF(od_ms.buyer_payment_amount, 0), NULLIF(mo.total_paid_customer, 0), NULLIF(mo.total_amount, 0), NULLIF(mo.subtotal_items, 0), 0)';
        $estimatedPayout = "COALESCE(NULLIF(mo.net_payout_estimated, 0), NULLIF(od_ms.final_income, 0), od_ie.estimated_escrow_amount, ({$gross} * ".(1 - self::ESTIMATED_MARKETPLACE_FEE_RATE).'))';

        $rows = $this->operationalBase($filters)
            ->leftJoinSub($itemTotals, 'od_items', fn ($join) => $join->on('od_items.order_key', '=', 'mo.id'))
            ->leftJoin('marketplace_order_settlements as od_ms', 'od_ms.order_id', '=', 'mo.id')
            ->leftJoin('marketplace_order_income_estimates as od_ie', 'od_ie.marketplace_order_id', '=', 'mo.id')
            ->selectRaw('DATE(COALESCE(mo.ordered_at, mo.order_date)) AS date')
            ->selectRaw('SUM(CASE WHEN '.$this->isRevenueStatus().' THEN 1 ELSE 0 END) AS orders')
            ->selectRaw('SUM(CASE WHEN '.$this->isRevenueStatus().' THEN '.$this->orderValueExpression().' ELSE 0 END) AS gmv')
            ->selectRaw("SUM(CASE WHEN {$this->isRevenueStatus()} THEN {$salesGross} ELSE 0 END) AS gross_sales")
            ->selectRaw("SUM(CASE WHEN {$this->isRevenueStatus()} THEN {$buyerPaid} ELSE 0 END) AS paid")
            ->selectRaw('SUM(CASE WHEN '.$this->isRevenueStatus().' THEN COALESCE(od_items.qty, 0) ELSE 0 END) AS qty')
            ->selectRaw('SUM(CASE WHEN '.$this->isRevenueStatus().' THEN '.$estimatedPayout.' ELSE 0 END) AS payout')
            ->selectRaw('SUM(CASE WHEN '.$this->isRevenueStatus().' THEN COALESCE(od_items.hpp, 0) ELSE 0 END) AS hpp')
            ->groupByRaw('DATE(COALESCE(mo.ordered_at, mo.order_date))')
            ->orderByRaw('DATE(COALESCE(mo.ordered_at, mo.order_date))')
            ->get();
        $sellerDiscounts = $this->dailySellerDiscounts($filters);

        return $rows
            ->map(fn ($row) => [
                'date' => $row->date,
                'orders' => (int) ($row->orders ?? 0),
                'gmv' => round((float) ($row->gmv ?? 0), 2),
                'gross_sales' => round((float) ($row->gross_sales ?? 0), 2),
                'net_sales' => round(max(
                    (float) ($row->gross_sales ?? 0) - (float) ($sellerDiscounts[(string) $row->date] ?? 0),
                    0,
                ), 2),
                'paid' => round((float) ($row->paid ?? 0), 2),
                'qty' => (int) ($row->qty ?? 0),
                'payout' => round((float) ($row->payout ?? 0), 2),
                'hpp' => round((float) ($row->hpp ?? 0), 2),
            ])
            ->values()
            ->all();
    }

    /**
     * Keep daily Net Sales aligned with the sales dashboard. Seller-funded
     * vouchers, bundle deals, and combo hemat discounts are the only
     * promotion amounts that reduce seller revenue; platform vouchers do not.
     */
    private function dailySellerDiscounts(array $filters): array
    {
        $rows = $this->applyDateAndStoreFilters(
            DB::table('marketplace_orders as mo')
                ->leftJoin('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
                ->whereRaw($this->isRevenueStatus())
                ->select([
                    'mo.voucher_discount',
                    'mo.raw_json as order_raw_json',
                    'mo.raw_payload_json',
                    'ms.id as settlement_id',
                    'ms.seller_voucher',
                    'ms.raw_json as settlement_raw_json',
                ])
                ->selectRaw('DATE(COALESCE(mo.ordered_at, mo.order_date)) AS date'),
            $filters,
            'mo',
        )->get();

        $totals = [];
        foreach ($rows as $row) {
            $settlement = $this->decodeAnalyticsPayload($row->settlement_raw_json ?? null);
            $order = $this->decodeAnalyticsPayload($row->order_raw_json ?? $row->raw_payload_json ?? null);
            $sellerVoucher = 0.0;
            $bundleDiscount = 0.0;
            $comboHemat = 0.0;

            if ($row->settlement_id !== null) {
                $sellerVoucher = array_key_exists('voucher_from_seller', $settlement)
                    ? max((float) $settlement['voucher_from_seller'], 0)
                    : max((float) ($row->seller_voucher ?? 0), 0);
                $items = (array) ($settlement['items'] ?? ($order['item_list'] ?? []));
                foreach ($items as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $split = $this->analyticsPromotionDiscountSplit($item);
                    $bundleDiscount += $split['bundle_discount'];
                    $comboHemat += $split['combo_hemat'];
                }
            } else {
                $sellerVoucher = max((float) ($row->voucher_discount ?? 0), 0);
            }

            $date = (string) $row->date;
            $totals[$date] = ($totals[$date] ?? 0) + $sellerVoucher + $bundleDiscount + $comboHemat;
        }

        return collect($totals)->map(fn ($value) => round((float) $value, 2))->all();
    }

    private function analyticsPromotionDiscountSplit(array $item): array
    {
        $reportedDiscount = max((float) ($item['seller_discount'] ?? 0), 0);
        $originalPrice = max((float) ($item['original_price'] ?? $item['model_original_price'] ?? 0), 0);
        $discountedPrice = max((float) ($item['discounted_price'] ?? $item['model_discounted_price'] ?? 0), 0);
        $quantity = max((int) ($item['quantity_purchased'] ?? $item['model_quantity_purchased'] ?? $item['quantity'] ?? 1), 1);
        $priceDifference = max($originalPrice - $discountedPrice, 0);

        if ($reportedDiscount <= 0 && $priceDifference > 0) {
            $reportedDiscount = $priceDifference * $quantity;
        }
        if ($reportedDiscount > 0 && $priceDifference > 0) {
            $reportedDiscount = min($reportedDiscount, $priceDifference * $quantity);
        }
        if ($reportedDiscount <= 0) {
            return ['bundle_discount' => 0.0, 'combo_hemat' => 0.0];
        }

        $promotionTypes = collect((array) ($item['promotion_list'] ?? []))
            ->map(fn ($promotion) => strtolower((string) ($promotion['promotion_type'] ?? '')));
        $activityType = strtolower((string) ($item['activity_type'] ?? ''));
        $promotionType = strtolower((string) ($item['promotion_type'] ?? ''));
        $isBundle = in_array($activityType, ['bundle_deal', 'bundle_deal_discount'], true)
            || $promotionType === 'bundle_deal'
            || (collect([$item['bundle_discount'] ?? null, $item['bundle_deal_discount'] ?? null, $item['bundle_discount_amount'] ?? null])
                ->contains(fn ($amount) => is_numeric($amount) && (float) $amount > 0)
                && $promotionTypes->contains('bundle_deal'));
        $comboTypes = ['add_on_deal', 'add_on_deal_main', 'add_on_deal_sub'];
        $isCombo = ! $isBundle && (in_array($activityType, $comboTypes, true)
            || in_array($promotionType, $comboTypes, true)
            || $promotionTypes->intersect($comboTypes)->isNotEmpty());

        if (! $isBundle && ! $isCombo) {
            return ['bundle_discount' => 0.0, 'combo_hemat' => 0.0];
        }

        $sellingPrice = max((float) ($item['selling_price'] ?? 0), 0);
        $productGap = max($originalPrice - $sellingPrice, 0);
        $promotionGap = max($sellingPrice - $discountedPrice, 0);
        $promotionDiscount = $reportedDiscount;
        if ($productGap > 0 && $promotionGap > 0) {
            $promotionDiscount = min(max($reportedDiscount - min($reportedDiscount, $productGap), 0), $promotionGap);
        }

        return [
            'bundle_discount' => $isBundle ? $promotionDiscount : 0.0,
            'combo_hemat' => $isCombo ? $promotionDiscount : 0.0,
        ];
    }

    private function decodeAnalyticsPayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        $decoded = json_decode((string) $payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function mergeDaily(array $financial, array $operational, array $ads = []): array
    {
        $rows = collect($financial)->keyBy('date');
        foreach ($operational as $row) {
            $date = (string) $row['date'];
            $rows[$date] = array_merge($rows[$date] ?? [
                'date' => $date,
                'orders' => 0,
                'order_count' => 0,
                'qty' => 0,
                'gross_sales' => 0,
                'net_sales' => 0,
                'paid' => 0,
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
                'orders' => (int) ($row['orders'] ?? 0),
                'gmv' => (float) ($row['gmv'] ?? 0),
                'gross_sales' => (float) ($row['gross_sales'] ?? 0),
                'net_sales' => (float) ($row['net_sales'] ?? 0),
                'paid' => (float) ($row['paid'] ?? 0),
                'qty' => (int) ($row['qty'] ?? 0),
                'payout' => (float) ($row['payout'] ?? 0),
                'hpp' => (float) ($row['hpp'] ?? 0),
            ]);
        }

        if ($ads !== []) {
            foreach ($ads as $row) {
                $date = (string) $row['date'];
                $rows[$date] = array_merge($rows[$date] ?? [
                    'date' => $date,
                    'orders' => 0,
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

        }

        $rows = $rows->map(function (array $row) use ($ads) {
            $adCostByDate = collect($ads)->firstWhere('date', $row['date']);
            if ($ads !== []) {
                $row['ad_cost_settlement'] = round((float) ($row['ad_cost_settlement'] ?? $row['ad_cost'] ?? 0), 2);
                $row['ad_cost_before_tax'] = round((float) ($adCostByDate['ad_cost_before_tax'] ?? 0), 2);
                $row['ad_cost_vat'] = round((float) ($adCostByDate['ad_cost_vat'] ?? 0), 2);
                $row['ad_cost'] = round((float) ($adCostByDate['ad_cost'] ?? 0), 2);
            }
            $row['gross_profit'] = round((float) ($row['payout'] ?? 0) - (float) ($row['hpp'] ?? 0), 2);
            $row['operating_profit'] = round($row['gross_profit'] - (float) ($row['ad_cost'] ?? 0), 2);
            $row['margin_pct'] = (float) ($row['gross_sales'] ?? 0) > 0
                ? round(($row['operating_profit'] / $row['gross_sales']) * 100, 2)
                : 0.0;

            return $row;
        });

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

    private function unsettledStoreSummary(array $filters)
    {
        $rows = $this->unsettledOrdersBase($filters)
            ->join('stores as st', 'st.id', '=', 'mo.store_id')
            ->select([
                'mo.store_id',
                'mo.total_amount',
                'mo.total_paid_customer',
                'mo.subtotal_items',
                'mo.raw_json as order_raw_json',
                'ms.buyer_payment_amount',
                'ms.seller_voucher',
                'ms.raw_json as settlement_raw_json',
            ])
            ->get();

        return $rows
            ->groupBy(fn ($row): string => (string) $row->store_id)
            ->map(function ($storeRows): array {
                return [
                    'order_count' => $storeRows->count(),
                    'gmv' => round((float) $storeRows->sum(fn ($row): float => $this->cashOrderGrossSales($row)), 2),
                ];
            });
    }

    private function returnRefundStoreSummary(array $filters)
    {
        $fromTimestamp = Carbon::parse($filters['date_from'])->startOfDay()->timestamp;
        $toTimestamp = Carbon::parse($filters['date_to'])->endOfDay()->timestamp;
        $seen = [];
        $summary = [];

        $returnRows = DB::table('marketplace_returns')
            ->whereBetween('create_time', [$fromTimestamp, $toTimestamp])
            ->whereIn('store_id', $filters['store_ids'])
            ->get(['id', 'store_id', 'order_sn', 'amount_before_discount']);

        foreach ($returnRows as $row) {
            $orderKey = trim((string) ($row->order_sn ?? ''));
            $key = $row->store_id.'|'.($orderKey !== '' ? $orderKey : 'return:'.$row->id);
            $seen[$key] = [
                'store_id' => (int) $row->store_id,
                'amount' => 0.0,
            ];
        }

        $financeRows = DB::table('marketplace_orders as mo')
            ->join('marketplace_order_settlements as ms', 'ms.order_id', '=', 'mo.id')
            ->join('stores as st', 'st.id', '=', 'mo.store_id')
            ->join('channels as ch', 'ch.id', '=', 'st.channel_id')
            ->whereIn('mo.store_id', $filters['store_ids'])
            ->whereRaw('LOWER(ch.code) = ?', ['tiktok'])
            ->where(function ($query): void {
                $query->whereRaw('ABS(COALESCE(ms.drc_adjustable_refund, 0)) > 0')
                    ->orWhereIn('ms.return_refund_status', MarketplaceOrderSettlement::RETURN_REFUND_STATUSES);
            })
            ->whereBetween(DB::raw('COALESCE(ms.settlement_time, mo.ordered_at)'), [
                $filters['date_from'].' 00:00:00',
                $filters['date_to'].' 23:59:59',
            ])
            ->get([
                'mo.store_id',
                'mo.channel_order_id',
                'mo.external_order_id',
                'ms.drc_adjustable_refund as settlement_refund_amount',
            ]);

        foreach ($financeRows as $row) {
            $orderKey = trim((string) ($row->channel_order_id ?: $row->external_order_id ?: ''));
            if ($orderKey !== '') {
                $key = $row->store_id.'|'.$orderKey;
                if (! isset($seen[$key])) {
                    $seen[$key] = [
                        'store_id' => (int) $row->store_id,
                        'amount' => 0.0,
                    ];
                }
            }
        }

        foreach ($returnRows->groupBy(function ($row): string {
            $orderKey = trim((string) ($row->order_sn ?? ''));

            return $row->store_id.'|'.($orderKey !== '' ? $orderKey : 'return:'.$row->id);
        }) as $key => $rows) {
            $settlement = $financeRows->first(function ($row) use ($key): bool {
                foreach ([(string) $row->channel_order_id, (string) $row->external_order_id] as $orderKey) {
                    if (trim($orderKey) !== '' && $row->store_id.'|'.trim($orderKey) === $key) {
                        return true;
                    }
                }

                return false;
            });
            $settlementAmount = abs((float) ($settlement->settlement_refund_amount ?? 0));
            $seen[$key]['amount'] = $settlementAmount > 0
                ? $settlementAmount
                : (float) $rows->sum(fn ($row): float => (float) ($row->amount_before_discount ?? 0));
        }

        foreach ($financeRows as $row) {
            $orderKey = trim((string) ($row->channel_order_id ?: $row->external_order_id ?: ''));
            if ($orderKey === '') {
                continue;
            }

            $key = $row->store_id.'|'.$orderKey;
            $seen[$key]['amount'] = max(
                (float) ($seen[$key]['amount'] ?? 0),
                abs((float) ($row->settlement_refund_amount ?? 0))
            );
        }

        foreach ($seen as $row) {
            $storeId = (string) $row['store_id'];
            $summary[$storeId] ??= ['count' => 0, 'amount' => 0.0];
            $summary[$storeId]['count']++;
            $summary[$storeId]['amount'] += (float) $row['amount'];
        }

        return collect($summary)->map(fn (array $row): array => [
            'count' => (int) $row['count'],
            'amount' => round((float) $row['amount'], 2),
        ]);
    }

    private function adsAggregate(array $filters): array
    {
        $facts = $this->adsFacts($filters);
        $beforeTax = (float) collect($facts)
            ->sum(fn (array $row): float => (float) ($row['spend'] ?? 0));
        $sources = collect($facts)->pluck('source')->filter()->unique()->values();
        $hasSettlementFallback = $sources->contains('settlement_fallback');
        $hasPerformanceSource = $sources->contains(fn (string $source): bool => $source !== 'settlement_fallback');
        $source = $beforeTax > 0
            ? ($hasSettlementFallback
                ? ($hasPerformanceSource ? 'ads_api_or_import_with_settlement_fallback' : 'settlement_fallback')
                : 'ads_api_or_import')
            : 'none';

        return [
            'ad_cost_before_tax' => round($beforeTax, 2),
            'ad_cost_vat' => round($beforeTax * self::ADS_VAT_RATE, 2),
            'ad_cost' => round($beforeTax * (1 + self::ADS_VAT_RATE), 2),
            'ad_cost_source' => $source,
            'ad_cost_is_estimate' => false,
        ];
    }

    private function adsDaily(array $filters): array
    {
        return collect($this->adsFacts($filters))
            ->groupBy('date')
            ->sortKeys()
            ->map(function ($rows, string $date): array {
                $beforeTax = (float) $rows->sum(fn (array $row): float => (float) ($row['spend'] ?? 0));

                return [
                    'date' => $date,
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
        $adsFacts = collect($this->adsFacts($filters));
        $spendByStore = $adsFacts
            ->groupBy('store_id')
            ->map(fn ($rows): float => (float) $rows->sum(fn (array $row): float => (float) ($row['spend'] ?? 0)));
        $sourceByStore = $adsFacts
            ->groupBy('store_id')
            ->map(function ($rows): string {
                $sources = $rows->pluck('source')->filter()->unique();
                $hasSettlementFallback = $sources->contains('settlement_fallback');
                $hasPerformanceSource = $sources->contains(fn (string $source): bool => $source !== 'settlement_fallback');

                return $hasSettlementFallback
                    ? ($hasPerformanceSource ? 'ads_api_or_import_with_settlement_fallback' : 'settlement_fallback')
                    : 'ads_api_or_import';
            });

        $platformTransactionSpend = $this->platformTransactionAdSpendByStore($filters);

        $storeIds = $spendByStore->keys()
            ->merge($platformTransactionSpend->keys())
            ->unique()
            ->values();

        if ($storeIds->isEmpty()) {
            return collect();
        }

        return DB::table('stores as st')
            ->whereIn('st.id', $storeIds->all())
            ->get(['st.id as store_id', 'st.name as store_name'])
            ->map(function ($row) use ($spendByStore, $sourceByStore, $platformTransactionSpend) {
                $storeId = (int) $row->store_id;
                $adsDailyBeforeTax = (float) ($spendByStore->get($storeId) ?? 0);
                $platformTransactionCost = (float) ($platformTransactionSpend->get($storeId) ?? 0);

                if ($adsDailyBeforeTax > 0) {
                    $row->ad_cost_before_tax = round($adsDailyBeforeTax, 2);
                    $row->ad_cost_vat = round($adsDailyBeforeTax * self::ADS_VAT_RATE, 2);
                    $row->ad_cost = round($adsDailyBeforeTax * (1 + self::ADS_VAT_RATE), 2);
                    $row->ad_cost_source = (string) ($sourceByStore->get($storeId) ?? 'ads_api_or_import');

                    return $row;
                }

                // Ads Daily can be empty even though the platform has already
                // recorded an actual advertising deduction. Use the platform
                // mutation only for that store, never on top of Ads Daily.
                $row->ad_cost = round(max($platformTransactionCost, 0), 2);
                $row->ad_cost_before_tax = round($row->ad_cost / (1 + self::ADS_VAT_RATE), 2);
                $row->ad_cost_vat = round($row->ad_cost - $row->ad_cost_before_tax, 2);
                $row->ad_cost_source = $row->ad_cost > 0
                    ? 'platform_transaction_fallback'
                    : 'none';

                return $row;
            });
    }

    /**
     * Resolve actual platform advertising mutations per store for the
     * store-ledger fallback. This is intentionally separate from adsFacts():
     * Ads Daily remains the primary delivery-cost source and mutations are
     * only consulted when that store has no positive Ads Daily spend.
     */
    private function platformTransactionAdSpendByStore(array $filters)
    {
        $storePlatforms = DB::table('stores as st')
            ->leftJoin('channels as ch', 'ch.id', '=', 'st.channel_id')
            ->whereIn('st.id', $filters['store_ids'])
            ->pluck('ch.code', 'st.id')
            ->map(fn ($code): string => strtolower(trim((string) $code)));

        $spendByStore = collect();
        $tiktokStoreIds = $storePlatforms
            ->filter(fn (string $platform): bool => $platform === 'tiktok')
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        if ($tiktokStoreIds !== []) {
            $tiktokDaily = $this->tiktokWalletAdCost->storeDaily(
                $tiktokStoreIds,
                null,
                $filters['date_from'],
                $filters['date_to'],
            );
            $tiktokDaily
                ->groupBy(fn (float $value, string $key): string => explode('|', $key, 2)[1])
                ->each(function ($rows, string $storeId) use ($spendByStore): void {
                    $spendByStore->put((int) $storeId, round((float) $rows->sum(), 2));
                });
        }

        $shopeeStoreIds = $storePlatforms
            ->filter(fn (string $platform): bool => $platform === 'shopee')
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
        foreach ($this->shopeePlatformAdSpendByStore($shopeeStoreIds, $filters['date_from'], $filters['date_to']) as $storeId => $spend) {
            $spendByStore->put((int) $storeId, round((float) $spend, 2));
        }

        return $spendByStore;
    }

    private function shopeePlatformAdSpendByStore(array $storeIds, string $dateFrom, string $dateTo): array
    {
        if ($storeIds === []) {
            return [];
        }

        $preferred = $this->aggregateShopeeAdMutations(
            'marketplace_shopee_wallet_transactions',
            $storeIds,
            $dateFrom,
            $dateTo,
        );
        $legacy = $this->aggregateShopeeAdMutations(
            'marketplace_ad_wallet_transactions',
            $storeIds,
            $dateFrom,
            $dateTo,
        );

        $result = [];
        foreach ($storeIds as $storeId) {
            $storeId = (int) $storeId;
            $spend = array_key_exists($storeId, $preferred)
                ? $preferred[$storeId]
                : ($legacy[$storeId] ?? 0.0);
            if ((float) $spend > 0) {
                $result[$storeId] = round((float) $spend, 2);
            }
        }

        return $result;
    }

    private function aggregateShopeeAdMutations(string $table, array $storeIds, string $dateFrom, string $dateTo): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $rows = DB::table($table)
            ->whereIn('store_id', $storeIds)
            ->whereBetween('transaction_created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
            ->where(function ($query): void {
                $query
                    ->whereIn('transaction_type', array_merge(self::SHOPEE_AD_CHARGE_TYPES, self::SHOPEE_AD_REFUND_TYPES))
                    ->orWhereRaw("LOWER(transaction_type) LIKE '%paid%ads%charge%'")
                    ->orWhereRaw("LOWER(transaction_type) LIKE '%paid%ads%refund%'");
            })
            ->get(['store_id', 'transaction_type', 'amount']);

        return $rows
            ->groupBy('store_id')
            ->map(function ($storeRows): float {
                $total = $storeRows->sum(function ($row): float {
                    $type = strtolower(str_replace('-', '_', trim((string) $row->transaction_type)));
                    $isRefund = $type === '451' || str_contains($type, 'refund');
                    $amount = abs((float) ($row->amount ?? 0));

                    return $isRefund ? -$amount : $amount;
                });

                return round(max((float) $total, 0), 2);
            })
            ->all();
    }

    /**
     * Return one authoritative ad-spend fact per store/day.
     *
     * Ads API facts take precedence. Imported income reports (including
     * TikTok advertising deductions) fill the gap when a store/day has no
     * positive API spend, matching AdsDashboardService's source policy.
     * For TikTok, settlement.ad_cost is the next fallback because Finance
     * already normalizes the actual GMV Max advertising deduction there.
     * TikTok Finance cash-out remains a final store-ledger fallback only.
     *
     * @return array<int, array{store_id: int, date: string, spend: float}>
     */
    private function adsFacts(array $filters): array
    {
        $apiRows = DB::table('marketplace_ads_dailies')
            ->whereBetween('date', [$filters['date_from'], $filters['date_to']])
            ->whereIn('store_id', $filters['store_ids'])
            ->selectRaw('store_id, date, COALESCE(SUM(spend), 0) AS spend')
            ->groupBy('store_id', 'date')
            ->get();

        $importedRows = collect();
        if (Schema::hasTable('marketplace_imported_ad_spends')) {
            $importedRows = DB::table('marketplace_imported_ad_spends')
                ->whereBetween('date', [$filters['date_from'], $filters['date_to']])
                ->whereIn('store_id', $filters['store_ids'])
                ->selectRaw('store_id, date, COALESCE(SUM(spend), 0) AS spend')
                ->groupBy('store_id', 'date')
                ->get();
        }

        $settlementRows = collect();
        if (Schema::hasTable('marketplace_order_settlements')
            && Schema::hasColumn('marketplace_order_settlements', 'ad_cost')) {
            $settlementRows = DB::table('marketplace_order_settlements as ms')
                ->join('marketplace_orders as mo', 'mo.id', '=', 'ms.order_id')
                ->join('stores as st', 'st.id', '=', 'ms.store_id')
                ->join('channels as ch', 'ch.id', '=', 'st.channel_id')
                ->whereIn('ms.store_id', $filters['store_ids'])
                ->whereRaw('LOWER(ch.code) = ?', ['tiktok'])
                ->whereRaw('COALESCE(mo.ordered_at, mo.order_date) IS NOT NULL')
                ->whereBetween(DB::raw('COALESCE(mo.ordered_at, mo.order_date)'), [
                    $filters['date_from'].' 00:00:00',
                    $filters['date_to'].' 23:59:59',
                ])
                ->where('ms.ad_cost', '>', 0)
                ->selectRaw('ms.store_id, DATE(COALESCE(mo.ordered_at, mo.order_date)) AS date, COALESCE(SUM(ms.ad_cost), 0) AS spend')
                ->groupBy('ms.store_id', DB::raw('DATE(COALESCE(mo.ordered_at, mo.order_date))'))
                ->get();
        }

        $apiByKey = $apiRows->keyBy(fn ($row): string => ((int) $row->store_id).'|'.substr((string) $row->date, 0, 10));
        $importedByKey = $importedRows->keyBy(fn ($row): string => ((int) $row->store_id).'|'.substr((string) $row->date, 0, 10));
        $settlementByKey = $settlementRows->keyBy(fn ($row): string => ((int) $row->store_id).'|'.substr((string) $row->date, 0, 10));
        $facts = [];

        foreach ($apiByKey->keys()->merge($importedByKey->keys())->merge($settlementByKey->keys())->unique() as $key) {
            $api = $apiByKey->get($key);
            $imported = $importedByKey->get($key);
            $settlement = $settlementByKey->get($key);
            $apiSpend = (float) ($api->spend ?? 0);
            $importedSpend = (float) ($imported->spend ?? 0);
            $settlementSpend = (float) ($settlement->spend ?? 0);
            $source = 'ads_api_or_import';
            $spend = $apiSpend > 0
                ? $apiSpend
                : ($importedSpend > 0
                    ? $importedSpend
                    // settlement.ad_cost already includes the TikTok ad tax;
                    // normalize it to the before-tax basis used by Ads Daily.
                    : ($settlementSpend > 0 ? $settlementSpend / (1 + self::ADS_VAT_RATE) : 0));

            if ($apiSpend <= 0 && $importedSpend <= 0 && $settlementSpend > 0) {
                $source = 'settlement_fallback';
            }

            if ($spend <= 0) {
                continue;
            }

            [$storeId, $date] = explode('|', $key, 2);
            $facts[] = [
                'store_id' => (int) $storeId,
                'date' => $date,
                'spend' => $spend,
                'source' => $source,
            ];
        }

        return $facts;
    }

    private function operationalBase(array $filters)
    {
        return $this->applyDateAndStoreFilters(DB::table('marketplace_orders as mo'), $filters, 'mo');
    }

    private function qualitySummary(array $filters): array
    {
        $eligibleRows = $this->applyDateAndStoreFilters(DB::table('marketplace_orders as mo'), $filters, 'mo')
            ->whereIn('mo.order_status', self::financialEligibleStatuses())
            ->selectRaw("COALESCE(mo.financial_data_status, 'unknown') AS status, COUNT(*) AS total")
            ->groupBy('mo.financial_data_status')
            ->get()
            ->keyBy('status');

        $waiting = $this->applyDateAndStoreFilters(DB::table('marketplace_orders as mo'), $filters, 'mo')
            ->where(function ($query) {
                $query
                    ->whereNull('mo.order_status')
                    ->orWhereNotIn('mo.order_status', self::financialEligibleStatuses());
            })
            ->count();

        return [
            'total' => (int) $eligibleRows->sum('total'),
            'ready' => (int) ($eligibleRows->get('ready')?->total ?? 0),
            'incomplete' => (int) ($eligibleRows->get('incomplete')?->total ?? 0),
            'unknown' => (int) ($eligibleRows->get('unknown')?->total ?? 0),
            'not_applicable' => (int) ($eligibleRows->get('not_applicable')?->total ?? 0),
            'waiting' => (int) $waiting,
        ];
    }

    private static function financialEligibleStatuses(): array
    {
        return MarketplaceFinancialDataQualityService::FINANCIAL_ELIGIBLE_ORDER_STATUSES;
    }

    private function applyDateAndStoreFilters($query, array $filters, string $alias)
    {
        $dateExpression = "COALESCE({$alias}.ordered_at, {$alias}.order_date)";

        return $query
            ->whereBetween(DB::raw($dateExpression), [
                $filters['date_from'].' 00:00:00',
                $filters['date_to'].' 23:59:59',
            ])
            ->whereIn("{$alias}.store_id", $filters['store_ids']);
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
            'SUM(COALESCE(ms.commission_fee, 0)) AS admin_fee_actual',
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
        $cancelled = "{$status} IN ('CANCELLED', 'CANCELED', 'CANCELLED_BEFORE_SHIPPING', 'BATAL', 'IN_CANCEL')"
            .' AND NOT '.$this->returnRefundExistsSql();

        return implode(', ', [
            'COUNT(DISTINCT mo.id) AS placed_order_count',
            'SUM(CASE WHEN '.$this->isRevenueStatus().' THEN 1 ELSE 0 END) AS order_total',
            "SUM(CASE WHEN {$status} IN ('READY_TO_SHIP', 'PROCESSED') THEN 1 ELSE 0 END) AS ready_to_ship_count",
            "SUM(CASE WHEN {$status} IN ('SHIPPED', 'READY_TO_HANDOVER') THEN 1 ELSE 0 END) AS in_transit_count",
            "SUM(CASE WHEN {$status} IN ('READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'READY_TO_HANDOVER') THEN 1 ELSE 0 END) AS shipping_count",
            "SUM(CASE WHEN {$status} = 'TO_CONFIRM_RECEIVE' THEN 1 ELSE 0 END) AS confirmation_count",
            "SUM(CASE WHEN {$status} IN ('SHIPPED', 'READY_TO_HANDOVER', 'TO_CONFIRM_RECEIVE', 'COMPLETED') THEN 1 ELSE 0 END) AS shipped_count",
            // Keep completed orders on the same eligible/revenue basis as order_total.
            "SUM(CASE WHEN {$status} = 'COMPLETED' AND ({$this->isRevenueStatus()}) THEN 1 ELSE 0 END) AS completed_count",
            "SUM(CASE WHEN {$cancelled} THEN 1 ELSE 0 END) AS cancelled_count",
            "SUM(CASE WHEN {$cancelled} THEN {$this->orderValueExpression()} ELSE 0 END) AS cancelled_amount",
            'SUM(CASE WHEN '.$this->isRevenueStatus().' THEN '.$this->orderValueExpression().' ELSE 0 END) AS gmv',
        ]);
    }

    private function orderValueExpression(): string
    {
        return 'COALESCE(NULLIF(mo.total_amount, 0), NULLIF(mo.total_paid_customer, 0), mo.subtotal_items, 0)';
    }

    private function isRevenueStatus(): string
    {
        $status = "UPPER(COALESCE(NULLIF(mo.order_status, ''), mo.status, ''))";
        $excluded = "'".implode("', '", self::NON_REVENUE_ORDER_STATUSES)."'";

        return "{$status} NOT IN ({$excluded})"
            .' AND NOT '.$this->returnRefundExistsSql();
    }

    private function returnRefundExistsSql(): string
    {
        $statuses = "'".implode("', '", MarketplaceOrderSettlement::RETURN_REFUND_STATUSES)."'";

        return '(EXISTS (SELECT 1 FROM marketplace_returns AS mr_status'
            .' WHERE mr_status.store_id = mo.store_id'
            .' AND (mr_status.order_sn = mo.channel_order_id OR mr_status.order_sn = mo.external_order_id))'
            .' OR EXISTS (SELECT 1 FROM marketplace_order_settlements AS ms_refund'
            .' WHERE ms_refund.order_id = mo.id'
            .' AND (ABS(COALESCE(ms_refund.drc_adjustable_refund, 0)) > 0'
            .' OR ms_refund.return_refund_status IN ('.$statuses.'))))';
    }

    /**
     * Shopee Kilat bookings with SHIPPED status are physically held by
     * Shopee, so they need their own analytics bucket instead of being mixed
     * with regular shipped orders.
     */
    private function warehouseBookingExistsSql(): string
    {
        return 'EXISTS (SELECT 1 FROM marketplace_bookings AS mb_status'
            .' WHERE mb_status.store_id = mo.store_id'
            ." AND UPPER(COALESCE(mb_status.booking_status, '')) = 'SHIPPED'"
            .' AND (mb_status.booking_sn = mo.booking_sn'
            .' OR mb_status.order_sn = mo.channel_order_id'
            .' OR mb_status.order_sn = mo.external_order_id))';
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
            'admin_fee_actual' => round((float) ($row->admin_fee_actual ?? 0), 2),
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
        $orders = (int) ($aggregate['placed_order_count'] ?? $aggregate['order_total'] ?? 0);
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
        $aggregate['ad_cost_source'] = (string) ($ads['ad_cost_source'] ?? 'none');
        $aggregate['ad_cost_is_estimate'] = (bool) ($ads['ad_cost_is_estimate'] ?? false);
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
        $hasCashScope = array_key_exists('cash_order_revenue', $aggregate)
            || array_key_exists('cash_unsettled_order_revenue', $aggregate);
        $grossOrderRevenue = (float) ($aggregate['cash_order_revenue'] ?? 0)
            + (float) ($aggregate['cash_unsettled_order_revenue'] ?? 0);
        $grossOrderRevenue = $hasCashScope
            ? max(0, $grossOrderRevenue)
            : (float) ($aggregate['gmv'] ?? 0);
        $cashPayout = (float) ($aggregate['cash_payout'] ?? $aggregate['payout'] ?? 0);
        // Fee actual is known from settlement, but its rate is compared with
        // marketplace order revenue—not the final payout after deductions.
        $marketplaceRevenue = (float) ($aggregate['cash_order_revenue'] ?? 0);
        $actualMarketplaceFee = (float) ($aggregate['cash_marketplace_fees'] ?? 0);
        $gmv = max(0, (float) ($aggregate['gmv'] ?? $grossOrderRevenue));
        // Projected payout follows the same operational scope as Dana belum
        // cair, including READY_TO_SHIP and PROCESSED orders.
        $hpp = array_key_exists('hpp_shipped', $aggregate)
            ? (float) $aggregate['hpp_shipped']
            : (float) ($aggregate['hpp_total'] ?? $aggregate['hpp'] ?? 0);
        $adCost = (float) ($aggregate['ad_cost'] ?? 0);
        $feeRate = $marketplaceRevenue > 0 && $actualMarketplaceFee > 0
            ? $actualMarketplaceFee / $marketplaceRevenue
            : self::ESTIMATED_MARKETPLACE_FEE_RATE;
        $unsettledGross = (float) ($aggregate['cash_unsettled_order_revenue'] ?? 0);
        $estimatedUnsettledPayout = array_key_exists('estimated_unsettled_payout', $aggregate)
            ? (float) $aggregate['estimated_unsettled_payout']
            : max(0, $unsettledGross * (1 - $feeRate));
        $estimatedPayout = $cashPayout + $estimatedUnsettledPayout;
        $estimatedFee = max(0, $actualMarketplaceFee + ($unsettledGross - $estimatedUnsettledPayout));
        // cash/order revenue already comes from isRevenueStatus(), which
        // excludes cancelled and return/refund orders. Keep those exceptions
        // as separate audit metrics; do not subtract them a second time here.
        $netOrderRevenue = max(0, $grossOrderRevenue);
        $estimatedGrossProfit = $estimatedPayout - $hpp;
        $estimatedProfit = $estimatedGrossProfit - $adCost;
        $settledRevenue = max(0, (float) ($aggregate['cash_order_revenue'] ?? 0));
        $unsettledRevenue = max(0, (float) ($aggregate['cash_unsettled_order_revenue'] ?? 0));
        $revenueBasis = $settledRevenue + $unsettledRevenue;
        $settledHpp = max(0, (float) ($aggregate['hpp_settled'] ?? 0));
        $settledAdCost = $revenueBasis > 0 ? $adCost * ($settledRevenue / $revenueBasis) : 0;
        $settledProfit = $cashPayout - $settledHpp - $settledAdCost;
        $estimatedUnsettledProfit = $estimatedProfit - $settledProfit;

        return array_merge($aggregate, [
            'gross_order_revenue' => round($grossOrderRevenue, 2),
            'net_order_revenue' => round($netOrderRevenue, 2),
            'marketplace_fee_estimate_rate' => round($feeRate * 100, 2),
            'marketplace_fee_rate_on_gmv' => $gmv > 0
                ? round(($actualMarketplaceFee / $gmv) * 100, 2)
                : 0.0,
            'marketplace_fee_estimate' => round($estimatedFee, 2),
            'marketplace_fee_estimate_on_payout' => round($estimatedFee, 2),
            'marketplace_fee_estimate_on_cash' => round($estimatedFee, 2),
            'estimated_payout' => round($estimatedPayout, 2),
            'estimated_gross_profit' => round($estimatedGrossProfit, 2),
            'estimated_profit' => round($estimatedProfit, 2),
            'estimated_net_profit' => round($estimatedProfit, 2),
            'estimated_profit_margin' => $netOrderRevenue > 0 ? round(($estimatedProfit / $netOrderRevenue) * 100, 2) : 0.0,
            'settled_profit' => round($settledProfit, 2),
            'realized_net_profit' => round($settledProfit, 2),
            'estimated_unsettled_profit' => round($estimatedUnsettledProfit, 2),
            'settled_profit_margin' => $settledRevenue > 0 ? round(($settledProfit / $settledRevenue) * 100, 2) : 0.0,
            'estimated_unsettled_profit_margin' => $unsettledRevenue > 0 ? round(($estimatedUnsettledProfit / $unsettledRevenue) * 100, 2) : 0.0,
            'marketplace_fees_actual' => round((float) ($aggregate['cash_marketplace_fees'] ?? $aggregate['marketplace_fees'] ?? 0), 2),
            'affiliate_fees_actual' => round((float) ($aggregate['cash_affiliate_fees'] ?? $aggregate['affiliate_fees'] ?? 0), 2),
        ]);
    }

    /**
     * Resolve pending payout from Shopee income detail when available. Rows
     * without an estimate use the same period fee rate as the visible
     * fallback, so the forecast remains deterministic while data is syncing.
     */
    private function estimatedUnsettledPayout(array $filters, array $settledCash): array
    {
        $settledRevenue = (float) ($settledCash['cash_order_revenue'] ?? 0);
        $actualFees = (float) ($settledCash['cash_marketplace_fees'] ?? 0);
        $feeRate = $settledRevenue > 0 && $actualFees > 0
            ? $actualFees / $settledRevenue
            : self::ESTIMATED_MARKETPLACE_FEE_RATE;

        $rows = $this->unsettledBase($filters)
            ->leftJoin('marketplace_order_income_estimates as ie', 'ie.marketplace_order_id', '=', 'mo.id')
            ->select([
                'mo.total_amount',
                'mo.total_paid_customer',
                'mo.subtotal_items',
                'mo.raw_json as order_raw_json',
                'ms.buyer_payment_amount',
                'ms.seller_voucher',
                'ms.raw_json as settlement_raw_json',
                'ie.estimated_escrow_amount',
            ])
            ->get();

        $payout = 0.0;
        foreach ($rows as $row) {
            $gross = $this->cashOrderGrossSales($row);
            $payout += $row->estimated_escrow_amount !== null
                ? max(0, (float) $row->estimated_escrow_amount)
                : max(0, $gross * (1 - $feeRate));
        }

        return [
            'payout' => round($payout, 2),
            'order_count' => $rows->count(),
        ];
    }
}
