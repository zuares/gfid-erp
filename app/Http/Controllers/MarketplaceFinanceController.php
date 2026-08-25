<?php

namespace App\Http\Controllers;

use App\Http\Requests\SyncOrdersRequest;
use App\Models\Channel;
use App\Models\ItemCostSnapshot;
use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdGroup;
use App\Models\MarketplaceAdItemMap;
use App\Models\MarketplaceOrder;
use App\Models\Item;
use App\Models\MarketplaceProduct;
use App\Models\MarketplacePromotion;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplaceSyncLog;
use App\Models\OrderFulfillment;
use App\Models\SkuMapping;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\Marketplace\Ads\ItemHppResolver;
use App\Services\Channels\ChannelManager;
use App\Support\GmvMaxAnalytics;
use App\Services\MarketplaceIssueService;
use App\Services\MarketplaceSyncService;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MarketplaceFinanceController extends Controller
{
    public function settlement(): \Illuminate\View\View
    {
        return view('marketplace.settlement');
    }

    public function profit(): \Illuminate\View\View
    {
        return view('marketplace.profit');
    }

    public function roasCalculator(): \Illuminate\View\View
    {
        $products = \App\Models\Item::where('active', true)
            ->whereIn('type', ['finished_good'])
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'hpp']);

        return view('marketplace.roas-calculator', compact('products'));
    }

    public function fetchAdsDataForRoas(Request $request): JsonResponse
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $itemId = $request->item_id;
        $dateFrom = $request->date_from ?: now()->subDays(30)->toDateString();
        $dateTo = $request->date_to ?: now()->toDateString();

        // 1. Get from MarketplaceAdItemMap
        $channelItemIds = \App\Models\MarketplaceAdItemMap::where('internal_item_id', $itemId)
            ->pluck('channel_item_id')
            ->filter()
            ->toArray();

        // 2. Get from SkuMapping -> MarketplaceProduct
        $skus = \App\Models\SkuMapping::where('item_id', $itemId)->pluck('marketplace_sku');
        if ($skus->isNotEmpty()) {
            $mpItemIds = \App\Models\MarketplaceProduct::whereIn('item_sku', $skus)
                ->pluck('item_id')
                ->filter()
                ->toArray();
            $channelItemIds = array_merge($channelItemIds, $mpItemIds);
        }

        $channelItemIds = array_unique($channelItemIds);

        if (empty($channelItemIds)) {
            return response()->json([
                'expense' => 0,
                'gmv' => 0,
                'orders' => 0,
                'status' => 'not_mapped'
            ]);
        }

        $data = \App\Models\MarketplaceAdsItemDaily::whereIn('channel_item_id', $channelItemIds)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->selectRaw('SUM(expense) as expense, SUM(broad_gmv) as gmv, SUM(broad_order) as orders')
            ->first();

        return response()->json([
            'expense' => (float) $data->expense,
            'gmv' => (float) $data->gmv,
            'orders' => (int) $data->orders,
            'status' => 'success'
        ]);
    }

    public function incomeDetail(): \Illuminate\View\View
    {
        return view('marketplace.income-detail');
    }

    public function incomeProducts(Request $request): JsonResponse
    {
        $settlementQuery = MarketplaceOrderSettlement::with([
            'store:id,name,channel_id',
            'store.channel:id,code,name',
            'order:id,channel_order_id,order_status,ordered_at,subtotal_items,total_paid_customer',
            'order.items:id,marketplace_order_id,hpp_snapshot,qty,item_name,variant_name,model_sku,item_sku,image_url,mapping_status,internal_item_id,price',
        ]);

        if ($request->filled('store_id')) {
            $settlementQuery->where('store_id', $request->store_id);
        }
        if ($request->filled('order_date_from')) {
            $settlementQuery->whereHas('order', fn ($q) => $q->whereDate('ordered_at', '>=', $request->order_date_from));
        }
        if ($request->filled('order_date_to')) {
            $settlementQuery->whereHas('order', fn ($q) => $q->whereDate('ordered_at', '<=', $request->order_date_to));
        }
        if ($request->filled('settlement_date_from')) {
            $from = $request->settlement_date_from;
            $settlementQuery->where(function ($q) use ($from) {
                $q->whereDate('settlement_time', '>=', $from)
                  ->orWhere(function ($q2) use ($from) {
                      $q2->whereNull('settlement_time')
                         ->whereHas('order', fn ($oq) => $oq->whereDate('ordered_at', '>=', $from));
                });
            });
        }
        if ($request->filled('settlement_date_to')) {
            $to = $request->settlement_date_to;
            $settlementQuery->where(function ($q) use ($to) {
                $q->whereDate('settlement_time', '<=', $to)
                  ->orWhere(function ($q2) use ($to) {
                      $q2->whereNull('settlement_time')
                         ->whereHas('order', fn ($oq) => $oq->whereDate('ordered_at', '<=', $to));
                  });
            });
        }

        $settlements = $settlementQuery->get();

        // Tab Produk harus tetap mencakup order pending yang belum sempat
        // mempunyai row settlement. Row sintetis ini hanya untuk agregasi UI;
        // tidak disimpan ke database dan tidak dianggap sebagai dana cair.
        $pendingOrdersQuery = MarketplaceOrder::with([
            'store:id,name,channel_id',
            'store.channel:id,code,name',
            'items:id,marketplace_order_id,hpp_snapshot,qty,item_name,variant_name,model_sku,item_sku,image_url,mapping_status,internal_item_id,price',
        ])
            ->whereDoesntHave('settlement')
            ->whereNotIn('order_status', ['UNPAID', 'CANCELLED', 'BATAL', 'RETURNED', 'REFUND']);

        if ($request->filled('store_id')) {
            $pendingOrdersQuery->where('store_id', $request->store_id);
        }
        if ($request->filled('order_date_from')) {
            $pendingOrdersQuery->whereDate('ordered_at', '>=', $request->order_date_from);
        }
        if ($request->filled('order_date_to')) {
            $pendingOrdersQuery->whereDate('ordered_at', '<=', $request->order_date_to);
        }
        if ($request->filled('settlement_date_from')) {
            $pendingOrdersQuery->whereDate('ordered_at', '>=', $request->settlement_date_from);
        }
        if ($request->filled('settlement_date_to')) {
            $pendingOrdersQuery->whereDate('ordered_at', '<=', $request->settlement_date_to);
        }

        $pendingSettlements = $pendingOrdersQuery->get()->map(function (MarketplaceOrder $order) {
            $settlement = new MarketplaceOrderSettlement([
                'store_id' => $order->store_id,
                'order_id' => $order->id,
                'channel_order_id' => $order->channel_order_id,
                'buyer_payment_amount' => $order->total_paid_customer
                    ?? $order->subtotal_items
                    ?? $order->total_amount
                    ?? 0,
                'final_income' => 0,
                'settlement_time' => null,
                'raw_json' => [],
            ]);
            $settlement->setRelation('store', $order->store);
            $settlement->setRelation('order', $order);

            return $settlement;
        });

        $settlements = $settlements->concat($pendingSettlements);
        $rows = [];
        $totalOrderCount = 0;
        $totalMapped = 0;
        $totalUnmapped = 0;
        $totalSettledOrderCount = 0;
        $totalUnsettledOrderCount = 0;

        foreach ($settlements as $settlement) {
            $order = $settlement->order;
            if (! $order) continue;
            $totalOrderCount++;
            $isSettled = ! empty($settlement->settlement_time);
            if ($isSettled) {
                $totalSettledOrderCount++;
            } else {
                $totalUnsettledOrderCount++;
            }

            $sellerDiscountOrder = (float) data_get($settlement->raw_json, 'seller_discount', 0);
            $grossOrder = (float) ($order->subtotal_items ?? $settlement->buyer_payment_amount ?? 0) - $sellerDiscountOrder;
            $finalIncome = (float) ($settlement->final_income ?? 0);
            $estimatedEscrowAmount = $this->settlementEstimatedEscrowAmount($settlement);
            $cogsOrder = (float) $order->items->sum(fn ($item) => (float) ($item->hpp_snapshot ?? 0) * (int) ($item->qty ?? 0));
            $status = strtoupper($order->order_status ?? '');
            $isCancelledOrReturned = in_array($status, ['CANCELLED', 'BATAL', 'RETURNED', 'REFUND']);
            $isReturning = in_array($status, ['TO_RETURN', 'RETURNING']);
            $items = $order->items ?? collect();
            if ($items->isEmpty()) continue;

            foreach ($items as $item) {
                $qty = max((int) ($item->qty ?? 0), 1);
                $lineGrossBeforeSellerDiscount = (float) (($item->price ?? 0) * $qty);
                if ($lineGrossBeforeSellerDiscount <= 0 && $grossOrder > 0) {
                    $lineGrossBeforeSellerDiscount = $grossOrder / max($items->count(), 1);
                }
                $share = $grossOrder > 0 ? ($lineGrossBeforeSellerDiscount / max((float) ($order->subtotal_items ?? $grossOrder), 1)) : (1 / max($items->count(), 1));
                $voucherTokoOrder = (float) $this->settlementVoucherTokoAmount($settlement);
                $grossAfterVoucherTokoOrder = max($grossOrder - $voucherTokoOrder, 0);
                $lineGrossAfterSellerDiscount = $grossOrder * $share;
                $lineSalesAfterVoucherToko = $grossAfterVoucherTokoOrder * $share;
                $lineCogs = (float) ($item->hpp_snapshot ?? 0) * $qty;
                $estimatedNetIncomeOrder = $finalIncome;
                if ($isCancelledOrReturned || $isReturning) {
                    $estimatedNetIncomeOrder = 0;
                } elseif (! $isSettled && $estimatedEscrowAmount !== null) {
                    // Untuk pending, estimasi resmi Shopee mengalahkan fallback
                    // 24%, termasuk ketika nilainya memang 0.
                    $estimatedNetIncomeOrder = $estimatedEscrowAmount;
                } elseif ($estimatedNetIncomeOrder <= 0) {
                    $estimatedNetIncomeOrder = max($grossAfterVoucherTokoOrder * 0.76, 0);
                }
                $lineIncome = $estimatedNetIncomeOrder * $share;
                $lineProfit = $lineIncome - $lineCogs;
                $sku = $item->model_sku ?: $item->item_sku ?: $item->marketplace_sku ?: '-';
                $key = $sku . '|' . ($item->variant_name ?: '-') . '|' . ($item->item_name ?: '-');

                if (! isset($rows[$key])) {
                $rows[$key] = [
                    'sku' => $sku,
                    'name' => $item->item_name ?: $item->variant_name ?: '-',
                    'variant_name' => $item->variant_name ?: '-',
                    'image_url' => $item->image_url,
                        'order_count' => 0,
                        'qty_total' => 0,
                        'gross_before_seller_discount_total' => 0,
                        'gross_after_seller_discount_total' => 0,
                        'sales_after_voucher_toko_total' => 0,
                    'buyer_paid_total' => 0,
                    'cogs_total' => 0,
                    'cogs_qty_total' => 0,
                    'income_total' => 0,
                    'income_cair_total' => 0,
                    'income_belum_cair_total' => 0,
                    'profit_total' => 0,
                    'profit_cair_total' => 0,
                    'profit_belum_cair_total' => 0,
                    'qty_cair_total' => 0,
                    'qty_belum_cair_total' => 0,
                    'mapped_count' => 0,
                    'unmapped_count' => 0,
                ];
            }

                $rows[$key]['order_count'] += 1;
                $rows[$key]['qty_total'] += $qty;
                $rows[$key]['gross_before_seller_discount_total'] += $lineGrossBeforeSellerDiscount;
                $rows[$key]['gross_after_seller_discount_total'] += $lineGrossAfterSellerDiscount;
                $rows[$key]['gross_total'] = $rows[$key]['gross_after_seller_discount_total'];
                $rows[$key]['sales_after_voucher_toko_total'] += $lineSalesAfterVoucherToko;
                $buyerPaidOrder = (float) ($settlement->order?->total_paid_customer ?? $settlement->buyer_payment_amount ?? 0);
                $rows[$key]['buyer_paid_total'] += $buyerPaidOrder * $share;
                $rows[$key]['cogs_total'] += $lineCogs;
                $rows[$key]['income_total'] += $lineIncome;
                $rows[$key]['profit_total'] += $lineProfit;
                $rows[$key]['cogs_qty_total'] += $lineCogs > 0 ? $qty : 0;
                if ($isSettled) {
                    $rows[$key]['qty_cair_total'] += $qty;
                    $rows[$key]['income_cair_total'] += $lineIncome;
                    $rows[$key]['profit_cair_total'] += $lineProfit;
                } else {
                    $rows[$key]['qty_belum_cair_total'] += $qty;
                    $rows[$key]['income_belum_cair_total'] += $lineIncome;
                    $rows[$key]['profit_belum_cair_total'] += $lineProfit;
                }
                $rows[$key]['settlement_time'] = $settlement->settlement_time?->toISOString();
                $rows[$key]['avg_selling_price'] = $rows[$key]['qty_total'] > 0
                    ? ($rows[$key]['gross_before_seller_discount_total'] / $rows[$key]['qty_total'])
                    : 0;
                $rows[$key]['avg_selling_price_after_seller_discount'] = $rows[$key]['qty_total'] > 0
                    ? ($rows[$key]['gross_after_seller_discount_total'] / $rows[$key]['qty_total'])
                    : 0;
                $rows[$key]['avg_selling_price_after_voucher_toko'] = $rows[$key]['qty_total'] > 0
                    ? ($rows[$key]['sales_after_voucher_toko_total'] / $rows[$key]['qty_total'])
                    : 0;
                $rows[$key]['buyer_paid_satuan'] = $rows[$key]['qty_total'] > 0
                    ? ($rows[$key]['buyer_paid_total'] / $rows[$key]['qty_total'])
                    : 0;
                $rows[$key]['avg_buyer_paid_satuan'] = $rows[$key]['buyer_paid_satuan'];
                if (!empty($item->internal_item_id) || ($item->mapping_status ?? null) === 'mapped') {
                    $rows[$key]['mapped_count'] += 1;
                    $totalMapped++;
                } else {
                    $rows[$key]['unmapped_count'] += 1;
                    $totalUnmapped++;
                }
            }
        }

        $rows = collect(array_values($rows))
            ->sortByDesc('qty_total')
            ->values();

        $topByProfit = $rows->first();
        $topByQty = $rows->sortByDesc('qty_total')->first();
        $topByMargin = $rows->sortByDesc(fn ($r) => (($r['sales_after_voucher_toko_total'] ?? 0) > 0 ? (($r['profit_total'] ?? 0) / $r['sales_after_voucher_toko_total']) * 100 : -999))->first();
        $unmappedOnly = $rows->filter(fn ($r) => (int) ($r['unmapped_count'] ?? 0) > 0)->count();
        $mappedOnly = $rows->filter(fn ($r) => (int) ($r['unmapped_count'] ?? 0) === 0)->count();
        $totalGrossBeforeSellerDiscount = (float) $rows->sum('gross_before_seller_discount_total');
        $totalGrossAfterSellerDiscount = (float) $rows->sum('gross_after_seller_discount_total');
        $totalSalesAfterVoucherToko = (float) $rows->sum('sales_after_voucher_toko_total');
        $totalBuyerPaid = (float) $rows->sum('buyer_paid_total');
        $totalCogs = (float) $rows->sum('cogs_total');
        $totalCogsQty = (int) $rows->sum('cogs_qty_total');
        $totalProfit = (float) $rows->sum('profit_total');
        $totalIncome = (float) $rows->sum('income_total');
        $totalIncomeCair = (float) $rows->sum('income_cair_total');
        $totalIncomeBelumCair = (float) $rows->sum('income_belum_cair_total');
        $totalProfitCair = (float) $rows->sum('profit_cair_total');
        $totalProfitBelumCair = (float) $rows->sum('profit_belum_cair_total');
        $totalQtyCair = (int) $rows->sum('qty_cair_total');
        $totalQtyBelumCair = (int) $rows->sum('qty_belum_cair_total');
        $totalQty = (int) $rows->sum('qty_total');
        $coverageBase = $totalMapped + $totalUnmapped;
        $topProfitList = $rows->take(3)->map(fn ($r) => [
            'name' => $r['name'] ?? '-',
            'value' => (float) ($r['profit_total'] ?? 0),
            'qty' => (int) ($r['qty_total'] ?? 0),
        ])->values();
        $topQtyList = $rows->sortByDesc('qty_total')->take(3)->map(fn ($r) => [
            'name' => $r['name'] ?? '-',
            'value' => (int) ($r['qty_total'] ?? 0),
            'profit' => (float) ($r['profit_total'] ?? 0),
        ])->values();
        $topMarginList = $rows
            ->sortByDesc(fn ($r) => (($r['sales_after_voucher_toko_total'] ?? 0) > 0 ? (($r['profit_total'] ?? 0) / $r['sales_after_voucher_toko_total']) * 100 : -999))
            ->take(3)
            ->map(fn ($r) => [
                'name' => $r['name'] ?? '-',
                'margin' => (($r['sales_after_voucher_toko_total'] ?? 0) > 0 ? (($r['profit_total'] ?? 0) / $r['sales_after_voucher_toko_total']) * 100 : 0),
                'profit' => (float) ($r['profit_total'] ?? 0),
            ])->values();

        return response()->json([
            'rows' => $rows,
            'meta' => [
                'total_products' => $rows->count(),
                'total_qty' => $totalQty,
                'total_profit' => $totalProfit,
                'total_income' => $totalIncome,
                'total_gross_before_seller_discount' => $totalGrossBeforeSellerDiscount,
                'total_gross_after_seller_discount' => $totalGrossAfterSellerDiscount,
                'total_gross' => $totalGrossAfterSellerDiscount,
                'total_sales_after_voucher_toko' => $totalSalesAfterVoucherToko,
                'total_buyer_paid' => $totalBuyerPaid,
                'total_cogs' => $totalCogs,
                'total_cogs_qty' => $totalCogsQty,
                'total_income_cair' => $totalIncomeCair,
                'total_income_belum_cair' => $totalIncomeBelumCair,
                'total_profit_cair' => $totalProfitCair,
                'total_profit_belum_cair' => $totalProfitBelumCair,
                'total_settled_order_count' => $totalSettledOrderCount,
                'total_unsettled_order_count' => $totalUnsettledOrderCount,
                'total_qty_cair' => $totalQtyCair,
                'total_qty_belum_cair' => $totalQtyBelumCair,
                'total_order_count' => $totalOrderCount,
                'rows_mapped' => $totalMapped,
                'rows_unmapped' => $totalUnmapped,
                'unmapped_products' => $unmappedOnly,
                'mapped_products' => $mappedOnly,
                'avg_profit_margin' => $totalGrossAfterSellerDiscount > 0 ? (($totalProfit / $totalGrossAfterSellerDiscount) * 100) : 0,
                'avg_profit_per_order' => $totalOrderCount > 0 ? ($totalProfit / $totalOrderCount) : 0,
                'avg_sales_after_voucher_toko_satuan' => $totalQty > 0 ? ($totalSalesAfterVoucherToko / $totalQty) : 0,
                'avg_buyer_paid_satuan' => $totalQty > 0 ? ($totalBuyerPaid / $totalQty) : 0,
                'avg_cogs_satuan' => $totalQty > 0 ? ($totalCogs / $totalQty) : 0,
                'avg_income_cair_satuan' => $totalQtyCair > 0 ? ($totalIncomeCair / $totalQtyCair) : 0,
                'avg_income_belum_cair_satuan' => $totalQtyBelumCair > 0 ? ($totalIncomeBelumCair / $totalQtyBelumCair) : 0,
                'sku_map_rate' => $rows->count() > 0 ? (($mappedOnly / $rows->count()) * 100) : 0,
                'sku_coverage_rate' => $coverageBase > 0 ? (($totalMapped / $coverageBase) * 100) : 0,
                'top_profit_name' => $topByProfit['name'] ?? null,
                'top_profit_value' => $topByProfit['profit_total'] ?? 0,
                'top_qty_name' => $topByQty['name'] ?? null,
                'top_qty_value' => $topByQty['qty_total'] ?? 0,
                'top_margin_name' => $topByMargin['name'] ?? null,
                'top_margin_value' => $topByMargin['sales_after_voucher_toko_total'] > 0 ? (($topByMargin['profit_total'] / $topByMargin['sales_after_voucher_toko_total']) * 100) : 0,
                'top_price_name' => $rows->sortByDesc(fn ($r) => ($r['avg_selling_price_after_voucher_toko'] ?? 0))->first()['name'] ?? null,
                'top_price_value' => $rows->sortByDesc(fn ($r) => ($r['avg_selling_price_after_voucher_toko'] ?? 0))->first()['avg_selling_price_after_voucher_toko'] ?? 0,
                'top_profit_list' => $topProfitList,
                'top_qty_list' => $topQtyList,
                'top_margin_list' => $topMarginList,
            ],
        ]);
    }

    /**
     * Return the pending payout estimate when the field was explicitly supplied.
     * Null means the income-detail field was not available, while 0 is a valid
     * estimate and must not trigger the legacy 24% fallback.
     */
    private function settlementEstimatedEscrowAmount(MarketplaceOrderSettlement $settlement): ?float
    {
        $raw = is_array($settlement->raw_json) ? $settlement->raw_json : [];
        $hasNestedValue = array_key_exists('estimated_escrow_amount', (array) data_get($raw, '_income_detail', []));
        $hasTopLevelValue = array_key_exists('estimated_escrow_amount', $raw);

        if (! $hasNestedValue && ! $hasTopLevelValue) {
            return null;
        }

        $value = $hasNestedValue
            ? data_get($raw, '_income_detail.estimated_escrow_amount')
            : data_get($raw, 'estimated_escrow_amount');

        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return max(0.0, (float) $value);
    }

    private function settlementVoucherTokoAmount(MarketplaceOrderSettlement $settlement): float
    {
        $raw = is_array($settlement->raw_json) ? $settlement->raw_json : [];

        foreach (['voucher_from_seller', 'seller_voucher_rebate', 'seller_voucher'] as $key) {
            $value = data_get($raw, $key);
            if ($value !== null && $value !== '') {
                return abs((float) $value);
            }
        }

        return abs((float) $settlement->seller_voucher);
    }
}
