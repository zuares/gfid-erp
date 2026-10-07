<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Marketplace\Ads\AdsDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketplaceSalesDashboardController extends Controller
{
    private const PROMOTION_DUMMY_SOURCE = 'promotion_dashboard_v1';

    private const PLATFORM_ALIASES = [
        'SHOPEE' => ['SHP'],
        'TIKTOK' => ['TTK'],
    ];

    private const NON_REVENUE_STATUSES = [
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

    private const SHIPPING_FAILED_STATUSES = [
        'FAILED_DELIVERY',
        'DELIVERY_FAILED',
        'LOGISTICS_DELIVERY_FAILED',
        'RETURN_TO_SELLER',
        'RETURNED_TO_SELLER',
        'UNDELIVERED',
    ];

    private const SHIPPING_RETURN_STATUSES = [
        'TO_RETURN',
        'RETURNING',
        'RETURNED',
        'REFUND',
        'REFUNDED',
    ];

    private const SHIPPING_EXCLUDED_STATUSES = [
        'UNPAID',
        'CANCELLED',
        'CANCELED',
        'CANCELLED_BEFORE_SHIPPING',
        'BATAL',
        'IN_CANCEL',
    ];

    public function index(Request $request)
    {
        $isPromotionDummy = $request->boolean('dummy') && app()->environment(['local', 'testing']);
        $today = now()->startOfDay();
        $defaultFrom = (clone $today)->startOfMonth();

        $from = $this->dateOrDefault($request->query('date_from'), $defaultFrom);
        $to = $this->dateOrDefault($request->query('date_to'), $today);

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        // Keep the dashboard responsive even when someone pastes a very wide range.
        if ($from->diffInDays($to) > 366) {
            $from = (clone $to)->subDays(366);
        }

        $storeId = $request->integer('store_id') ?: null;
        $platformCode = $this->normalizePlatformCode($request->query('platform'));
        $platformCodes = $this->platformCodes($platformCode);
        $comparisonMode = in_array($request->query('comparison_mode'), ['period', 'month'], true)
            ? $request->query('comparison_mode')
            : 'period';
        $stores = Store::query()
            ->where('is_active', true)
            ->with('channel')
            ->orderBy('name')
            ->get();

        $platforms = $stores
            ->map(function ($store) {
                $code = $this->normalizePlatformCode($store->channel->code ?? null);
                if (! $code) {
                    return null;
                }

                return [
                    'code' => $code,
                    'label' => $store->channel->name ?: ucfirst(strtolower($code)),
                ];
            })
            ->filter()
            ->unique('code')
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        if ($platformCode && ! $platforms->contains('code', $platformCode)) {
            $platformCode = null;
        }

        $selectedStore = $storeId ? $stores->firstWhere('id', $storeId) : null;
        if ($storeId && (! $selectedStore || ($platformCode && $this->normalizePlatformCode($selectedStore->channel->code ?? null) !== $platformCode))) {
            $storeId = null;
        }

        $adStoreIds = $stores
            ->filter(function ($store) use ($platformCode) {
                $channelCode = $this->normalizePlatformCode($store->channel->code ?? null);

                return $channelCode === 'SHOPEE'
                    && (! $platformCode || $channelCode === $platformCode);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        $adDailyMetrics = app(AdsDashboardService::class)->getDailyMetrics(
            $adStoreIds,
            $storeId,
            $from->toDateString(),
            $to->toDateString(),
        );
        $adSpendDaily = $adDailyMetrics->map(fn (array $metrics) => (float) $metrics['spend']);
        $adOrdersDaily = $adDailyMetrics->map(fn (array $metrics) => (int) $metrics['orders']);
        $adSpendTotal = (float) $adSpendDaily->sum();
        $adImpressionsTotal = (int) $adDailyMetrics->sum('impressions');
        $adClicksTotal = (int) $adDailyMetrics->sum('clicks');
        $adOrdersTotal = (int) $adDailyMetrics->sum('orders');
        $adSalesTotal = (float) $adDailyMetrics->sum('gmv');
        $adCtr = $adImpressionsTotal > 0 ? ($adClicksTotal / $adImpressionsTotal) * 100 : 0;
        $adCvr = $adClicksTotal > 0 ? ($adOrdersTotal / $adClicksTotal) * 100 : 0;

        $itemTotals = DB::table('marketplace_order_items as oi')
            ->selectRaw('COALESCE(oi.marketplace_order_id, oi.order_id) as order_key')
            ->selectRaw('COALESCE(SUM(oi.qty), 0) as total_qty')
            ->groupByRaw('COALESCE(oi.marketplace_order_id, oi.order_id)');

        $dateExpression = 'COALESCE(o.ordered_at, o.order_date)';
        $statusExpression = "UPPER(COALESCE(NULLIF(o.order_status, ''), NULLIF(o.status, ''), ''))";
        $subtotalExpression = 'CASE WHEN COALESCE(o.subtotal_items, 0) > 0 THEN o.subtotal_items ELSE COALESCE(o.total_amount, 0) END';

        $base = DB::table('marketplace_orders as o')
            ->leftJoinSub($itemTotals, 'itot', 'itot.order_key', '=', 'o.id')
            ->leftJoin('stores as st', 'st.id', '=', 'o.store_id')
            ->leftJoin('channels as ch', 'ch.id', '=', 'st.channel_id')
            ->whereRaw("{$dateExpression} IS NOT NULL")
            ->whereDate(DB::raw($dateExpression), '>=', $from->toDateString())
            ->whereDate(DB::raw($dateExpression), '<=', $to->toDateString())
            ->whereRaw("{$statusExpression} NOT IN (" . implode(',', array_fill(0, count(self::NON_REVENUE_STATUSES), '?')) . ')', self::NON_REVENUE_STATUSES)
            ->when($isPromotionDummy, fn ($query) => $query->whereJsonContains('o.meta->dummy_source', self::PROMOTION_DUMMY_SOURCE))
            ->when($storeId, fn ($query) => $query->where('o.store_id', $storeId))
            ->when($platformCode, fn ($query) => $query->whereIn(DB::raw('UPPER(ch.code)'), $platformCodes));

        $orderDetails = (clone $base)
            ->select([
                'o.id',
                'o.channel_order_id',
                'o.external_order_id',
                'o.buyer_username',
                'o.buyer_name',
                'o.shipping_city',
                'o.shipping_province',
                'o.payment_method',
                'o.payment_status',
                'o.shipping_fee_customer',
                'o.total_paid_customer',
                'o.total_amount',
                'o.raw_json',
                'o.raw_payload_json',
                'ms.raw_json as settlement_raw_json',
                'ms.buyer_payment_amount',
                'ms.seller_voucher',
                'st.name as store_name',
            ])
            ->leftJoin('marketplace_order_settlements as ms', 'ms.order_id', '=', 'o.id')
            ->selectRaw("{$dateExpression} as order_at")
            ->selectRaw("DATE({$dateExpression}) as day")
            ->selectRaw("{$statusExpression} as status")
            ->selectRaw("{$subtotalExpression} as subtotal")
            ->selectRaw('COALESCE(itot.total_qty, 0) as item_qty')
            ->orderByDesc('order_at')
            ->orderByDesc('o.id')
            ->limit(500)
            ->get()
            ->map(function ($row) {
                $quantity = (int) $row->item_qty;
                if ($quantity <= 0) {
                    $quantity = $this->quantityFromPayload($row->raw_json ?? $row->raw_payload_json);
                }

                $row->day = (string) $row->day;
                $row->qty = max(0, $quantity);
                $row->subtotal = (float) $row->subtotal;
                // Gunakan nomor pesanan marketplace sebagai identitas utama di
                // tab Detail Pesanan; ID internal hanya menjadi fallback terakhir.
                $row->order_number = $this->orderNumberForDisplay(
                    $row->channel_order_id,
                    $row->external_order_id,
                    (int) $row->id,
                );
                $row->order_ref = $row->order_number;
                $row->buyer = $row->buyer_username ?: ($row->buyer_name ?: 'Pelanggan marketplace');
                $row->payment = $row->payment_method ?: ($row->payment_status ?: 'Belum ditentukan');
                $row->shipping_fee = (float) ($row->shipping_fee_customer ?? 0);
                $row->total_payment = (float) collect([
                    $row->buyer_payment_amount,
                    $row->total_paid_customer,
                    $row->total_amount,
                    $row->subtotal,
                ])->map(fn ($value) => (float) $value)->first(fn (float $value) => $value > 0) ?? 0;

                $settlementRaw = is_array($row->settlement_raw_json)
                    ? $row->settlement_raw_json
                    : (json_decode((string) $row->settlement_raw_json, true) ?: []);
                $orderRaw = is_array($row->raw_json)
                    ? $row->raw_json
                    : (json_decode((string) $row->raw_json, true) ?: []);
                $voucherStore = array_key_exists('voucher_from_seller', $settlementRaw)
                    ? (float) $settlementRaw['voucher_from_seller']
                    : (float) ($row->seller_voucher ?? 0);
                $voucherPlatform = array_key_exists('voucher_from_shopee', $settlementRaw)
                    ? (float) $settlementRaw['voucher_from_shopee']
                    : 0.0;
                $promotionItems = (array) ($settlementRaw['items'] ?? ($orderRaw['item_list'] ?? []));
                $bundleDiscount = 0.0;
                $comboHemat = 0.0;
                foreach ($promotionItems as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $split = $this->promotionDiscountSplit($item);
                    $bundleDiscount += $split['bundle_discount'];
                    $comboHemat += $split['combo_hemat'];
                }
                $row->voucher_store = $voucherStore;
                $row->voucher_platform = $voucherPlatform;
                $row->bundle_discount = $bundleDiscount;
                $row->combo_hemat = $comboHemat;
                $row->promotion_total = $voucherStore + $voucherPlatform + $bundleDiscount + $comboHemat;

                unset($row->raw_json, $row->raw_payload_json, $row->settlement_raw_json, $row->seller_voucher, $row->item_qty, $row->total_paid_customer, $row->buyer_payment_amount, $row->total_amount);

                return $row;
            });

        // Item rows are preferred. For older/imported orders, fall back to the
        // marketplace payload so the quantity KPI does not silently become 0.
        $orderRows = (clone $base)
            ->selectRaw("o.id, DATE({$dateExpression}) as day, {$subtotalExpression} as subtotal")
            ->selectRaw('COALESCE(itot.total_qty, 0) as item_qty')
            ->addSelect('o.raw_json', 'o.raw_payload_json')
            ->get()
            ->map(function ($row) {
                $quantity = (int) $row->item_qty;
                if ($quantity <= 0) {
                    $quantity = $this->quantityFromPayload($row->raw_json ?? $row->raw_payload_json);
                }

                return [
                    'day' => (string) $row->day,
                    'subtotal' => (float) $row->subtotal,
                    'qty' => max(0, $quantity),
                ];
            });

        $summary = [
            'orders' => $orderRows->count(),
            'qty' => (int) $orderRows->sum('qty'),
            'subtotal' => (float) $orderRows->sum('subtotal'),
        ];
        $buyerExpression = "COALESCE(NULLIF(o.buyer_username, ''), NULLIF(o.buyer_name, ''), o.id)";
        $summary['buyers'] = (int) (clone $base)
            ->selectRaw("COUNT(DISTINCT {$buyerExpression}) as buyers")
            ->value('buyers');
        $summary['aov'] = $summary['orders'] > 0 ? $summary['subtotal'] / $summary['orders'] : 0;

        $daily = $orderRows
            ->groupBy('day')
            ->map(function ($rows, $day) {
                $orders = $rows->count();
                $subtotal = (float) $rows->sum('subtotal');

                return (object) [
                    'day' => $day,
                    'orders' => $orders,
                    'qty' => (int) $rows->sum('qty'),
                    'subtotal' => $subtotal,
                    'avg_units_per_order' => $orders > 0 ? (float) $rows->sum('qty') / $orders : 0,
                    'aov' => $orders > 0 ? $subtotal / $orders : 0,
                ];
            })
            ->sortByDesc('day')
            ->values();

        $productLineValueExpression = 'CASE WHEN COALESCE(oi_total.line_net_amount, 0) > 0 THEN oi_total.line_net_amount WHEN COALESCE(oi_total.price, 0) > 0 THEN oi_total.price * COALESCE(oi_total.qty, 0) WHEN COALESCE(oi_total.line_gross_amount, 0) > 0 THEN oi_total.line_gross_amount ELSE 0 END';
        $productOrderTotals = DB::table('marketplace_order_items as oi_total')
            ->selectRaw('COALESCE(oi_total.marketplace_order_id, oi_total.order_id) as order_key')
            ->selectRaw("COALESCE(SUM({$productLineValueExpression}), 0) as order_item_value")
            ->groupByRaw('COALESCE(oi_total.marketplace_order_id, oi_total.order_id)');
        $productBuyerPaymentExpression = 'COALESCE(NULLIF(s.buyer_payment_amount, 0), NULLIF(o.total_paid_customer, 0), NULLIF(o.total_amount, 0), NULLIF(o.subtotal_items, 0), 0)';
        $productLineValueForRow = 'CASE WHEN COALESCE(oi.line_net_amount, 0) > 0 THEN oi.line_net_amount WHEN COALESCE(oi.price, 0) > 0 THEN oi.price * COALESCE(oi.qty, 0) WHEN COALESCE(oi.line_gross_amount, 0) > 0 THEN oi.line_gross_amount ELSE 0 END';
        $productLineNetValueExpression = 'CASE WHEN COALESCE(oi.line_net_amount, 0) > 0 THEN oi.line_net_amount WHEN COALESCE(oi.price_after_discount, 0) > 0 THEN oi.price_after_discount * COALESCE(oi.qty, 0) WHEN COALESCE(oi.line_gross_amount, 0) > 0 THEN oi.line_gross_amount ELSE 0 END';
        $marketplaceProductNameExpression = "COALESCE(NULLIF(oi.item_name, ''), NULLIF(oi.item_name_snapshot, ''), NULLIF(oi.variant_name, ''), NULLIF(oi.variant_snapshot, ''), 'Produk tanpa nama')";
        $marketplaceProductSkuExpression = "COALESCE(NULLIF(oi.item_sku, ''), NULLIF(oi.marketplace_sku, ''), NULLIF(oi.model_sku, ''), NULLIF(oi.external_sku, ''), NULLIF(oi.item_code_snapshot, ''), '-')";
        $periodPurchaseOrderConversionExpression = 'COALESCE(NULLIF(period_pol.conversion_factor, 0), NULLIF(internal_item.purchase_conversion_factor, 0), 1)';
        $periodReceiptConversionExpression = 'COALESCE(NULLIF(period_prl.conversion_factor, 0), NULLIF(internal_item.purchase_conversion_factor, 0), 1)';
        $periodLastPurchaseOrderPriceExpression = "(SELECT CASE
                WHEN COALESCE(period_pol.line_total, 0) > 0 AND COALESCE(period_pol.qty, 0) > 0
                    THEN 1.0 * period_pol.line_total / NULLIF(period_pol.qty * ({$periodPurchaseOrderConversionExpression}), 0)
                ELSE 1.0 * period_pol.unit_price / NULLIF(({$periodPurchaseOrderConversionExpression}), 0)
            END
            FROM purchase_order_lines as period_pol
            JOIN purchase_orders as period_po ON period_po.id = period_pol.purchase_order_id
            WHERE period_pol.item_id = internal_item.id
                AND period_po.date <= ?
                AND period_po.status NOT IN ('draft', 'cancelled', 'canceled', 'rejected')
                AND COALESCE(period_pol.allocation, 'hpp') = 'hpp'
                AND period_pol.unit_price > 0
            ORDER BY period_po.date DESC, period_po.id DESC, period_pol.id DESC
            LIMIT 1)";
        $periodLastReceiptPriceExpression = "(SELECT CASE
                WHEN COALESCE(period_prl.line_total, 0) > 0
                    AND COALESCE(NULLIF(period_prl.stock_qty_received, 0), period_prl.qty_received * ({$periodReceiptConversionExpression}), 0) > 0
                    THEN 1.0 * period_prl.line_total / NULLIF(COALESCE(NULLIF(period_prl.stock_qty_received, 0), period_prl.qty_received * ({$periodReceiptConversionExpression})), 0)
                ELSE 1.0 * period_prl.unit_price / NULLIF(({$periodReceiptConversionExpression}), 0)
            END
            FROM purchase_receipt_lines as period_prl
            JOIN purchase_receipts as period_pr ON period_pr.id = period_prl.purchase_receipt_id
            WHERE period_prl.item_id = internal_item.id
                AND period_pr.status = 'posted'
                AND period_pr.date <= ?
                AND COALESCE(period_prl.allocation, 'hpp') = 'hpp'
                AND period_prl.unit_price > 0
            ORDER BY period_pr.date DESC, period_pr.id DESC, period_prl.id DESC
            LIMIT 1)";

        $products = DB::table('marketplace_order_items as oi')
            ->join('marketplace_orders as o', function ($join) {
                $join->on(DB::raw('COALESCE(oi.marketplace_order_id, oi.order_id)'), '=', 'o.id');
            })
            ->leftJoin('marketplace_order_settlements as s', 's.order_id', '=', 'o.id')
            ->leftJoin('stores as st', 'st.id', '=', 'o.store_id')
            ->leftJoin('channels as ch', 'ch.id', '=', 'st.channel_id')
            ->leftJoin('items as internal_item', 'internal_item.id', '=', 'oi.internal_item_id')
            ->leftJoin('item_categories as internal_category', 'internal_category.id', '=', 'internal_item.item_category_id')
            ->leftJoinSub($productOrderTotals, 'product_order_totals', 'product_order_totals.order_key', '=', 'o.id')
            ->whereRaw("{$dateExpression} IS NOT NULL")
            ->whereDate(DB::raw($dateExpression), '>=', $from->toDateString())
            ->whereDate(DB::raw($dateExpression), '<=', $to->toDateString())
            ->whereRaw("{$statusExpression} NOT IN (" . implode(',', array_fill(0, count(self::NON_REVENUE_STATUSES), '?')) . ')', self::NON_REVENUE_STATUSES)
            ->when($storeId, fn ($query) => $query->where('o.store_id', $storeId))
            ->when($platformCode, fn ($query) => $query->whereIn(DB::raw('UPPER(ch.code)'), $platformCodes))
            ->selectRaw("COALESCE(MAX(NULLIF(internal_item.name, '')), MAX({$marketplaceProductNameExpression}), 'Produk tanpa nama') as name")
            ->selectRaw("MAX({$marketplaceProductNameExpression}) as marketplace_name")
            ->selectRaw("MAX(NULLIF(oi.image_url, '')) as image_url")
            ->selectRaw("COALESCE(MAX(NULLIF(internal_item.code, '')), MAX({$marketplaceProductSkuExpression}), '-') as sku")
            ->selectRaw("COALESCE(NULLIF({$periodLastPurchaseOrderPriceExpression}, 0), NULLIF({$periodLastReceiptPriceExpression}, 0), MAX(NULLIF(internal_item.base_unit_cost, 0)), MAX(NULLIF(internal_item.hpp, 0)), 0) as hpp", [$to->toDateString(), $to->toDateString()])
            ->selectRaw("MAX(NULLIF(internal_category.code, '')) as category_code")
            ->selectRaw("MAX(NULLIF(internal_category.name, '')) as category_name")
            ->selectRaw('MAX(NULLIF(oi.internal_item_id, 0)) as internal_item_id')
            ->selectRaw("MAX(NULLIF(oi.external_item_id, '')) as external_item_id")
            ->selectRaw('COALESCE(SUM(CASE WHEN oi.qty > 0 THEN oi.qty ELSE 0 END), 0) as qty')
            ->selectRaw('COUNT(DISTINCT o.id) as orders')
            ->selectRaw('GROUP_CONCAT(DISTINCT o.id) as order_keys')
            ->selectRaw("COUNT(DISTINCT COALESCE(NULLIF(o.buyer_username, ''), NULLIF(o.buyer_name, ''), o.id)) as buyers")
            ->selectRaw("COALESCE(SUM({$productLineNetValueExpression}), 0) as sales")
            ->selectRaw("COALESCE(SUM({$productLineNetValueExpression}), 0) as net_sales")
            ->selectRaw("COALESCE(SUM(CASE WHEN COALESCE(product_order_totals.order_item_value, 0) > 0 THEN ({$productBuyerPaymentExpression} * {$productLineValueForRow} / product_order_totals.order_item_value) ELSE 0 END), 0) as buyer_payment")
            ->groupByRaw('NULLIF(oi.internal_item_id, 0)')
            ->groupByRaw("CASE WHEN NULLIF(oi.internal_item_id, 0) IS NOT NULL THEN NULLIF(oi.external_item_id, '') END")
            ->groupByRaw("CASE WHEN NULLIF(oi.internal_item_id, 0) IS NULL THEN {$marketplaceProductNameExpression} END")
            ->groupByRaw("CASE WHEN NULLIF(oi.internal_item_id, 0) IS NULL THEN {$marketplaceProductSkuExpression} END")
            ->orderByDesc('sales')
            ->get();

        // Penjualan item sudah memakai harga setelah diskon item. Promosi
        // order-level tetap dialokasikan ke produk berdasarkan proporsi nilai
        // item agar Penjualan Netto tidak melewatkan voucher seller, paket,
        // dan combo hemat tanpa mengurangi diskon item dua kali.
        $productExtraPromotionByKey = collect();
        $internalProductIds = $products
            ->pluck('internal_item_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $unmappedProducts = $products->filter(fn ($product) => ! (int) ($product->internal_item_id ?? 0))->values();
        if ($internalProductIds->isNotEmpty() || $unmappedProducts->isNotEmpty()) {
            $promotionProductNameExpression = "COALESCE(NULLIF(promo_oi.item_name, ''), NULLIF(promo_oi.item_name_snapshot, ''), NULLIF(promo_oi.variant_name, ''), NULLIF(promo_oi.variant_snapshot, ''), 'Produk tanpa nama')";
            $promotionProductSkuExpression = "COALESCE(NULLIF(promo_oi.item_sku, ''), NULLIF(promo_oi.marketplace_sku, ''), NULLIF(promo_oi.model_sku, ''), NULLIF(promo_oi.external_sku, ''), NULLIF(promo_oi.item_code_snapshot, ''), '-')";
            $promotionRows = DB::table('marketplace_order_items as promo_oi')
                ->join('marketplace_orders as promo_o', function ($join) {
                    $join->on(DB::raw('COALESCE(promo_oi.marketplace_order_id, promo_oi.order_id)'), '=', 'promo_o.id');
                })
                ->leftJoin('marketplace_order_settlements as promo_s', 'promo_s.order_id', '=', 'promo_o.id')
                ->leftJoin('stores as promo_st', 'promo_st.id', '=', 'promo_o.store_id')
                ->leftJoin('channels as promo_ch', 'promo_ch.id', '=', 'promo_st.channel_id')
                ->leftJoinSub($productOrderTotals, 'promo_order_totals', 'promo_order_totals.order_key', '=', 'promo_o.id')
                ->whereRaw('COALESCE(promo_o.ordered_at, promo_o.order_date) IS NOT NULL')
                ->whereDate(DB::raw("COALESCE(promo_o.ordered_at, promo_o.order_date)"), '>=', $from->toDateString())
                ->whereDate(DB::raw("COALESCE(promo_o.ordered_at, promo_o.order_date)"), '<=', $to->toDateString())
                ->whereRaw("UPPER(COALESCE(NULLIF(promo_o.order_status, ''), NULLIF(promo_o.status, ''), '')) NOT IN (" . implode(',', array_fill(0, count(self::NON_REVENUE_STATUSES), '?')) . ')', self::NON_REVENUE_STATUSES)
                ->when($storeId, fn ($query) => $query->where('promo_o.store_id', $storeId))
                ->when($platformCode, fn ($query) => $query->whereIn(DB::raw('UPPER(promo_ch.code)'), $platformCodes))
                ->where(function ($query) use ($internalProductIds, $unmappedProducts, $promotionProductNameExpression, $promotionProductSkuExpression) {
                    if ($internalProductIds->isNotEmpty()) {
                        $query->whereIn('promo_oi.internal_item_id', $internalProductIds->all());
                    }
                    foreach ($unmappedProducts as $unmappedProduct) {
                        $query->orWhere(function ($productQuery) use ($promotionProductNameExpression, $promotionProductSkuExpression, $unmappedProduct) {
                            $productQuery
                                ->whereRaw('NULLIF(promo_oi.internal_item_id, 0) IS NULL')
                                ->whereRaw("{$promotionProductNameExpression} = ?", [(string) ($unmappedProduct->marketplace_name ?: $unmappedProduct->name)])
                                ->whereRaw("{$promotionProductSkuExpression} = ?", [(string) $unmappedProduct->sku]);
                        });
                    }
                })
                ->select([
                    'promo_o.id as order_id',
                    'promo_o.voucher_discount',
                    'promo_o.raw_json as order_raw_json',
                    'promo_o.raw_payload_json',
                    'promo_s.id as settlement_id',
                    'promo_s.seller_voucher',
                    'promo_s.raw_json as settlement_raw_json',
                    'promo_oi.id as item_id',
                    'promo_oi.internal_item_id',
                    'promo_oi.external_item_id',
                    'promo_oi.qty',
                    'promo_oi.price',
                    'promo_oi.price_after_discount',
                    'promo_oi.line_net_amount',
                    'promo_oi.line_gross_amount',
                    'promo_order_totals.order_item_value',
                ])
                ->selectRaw("{$promotionProductNameExpression} as marketplace_name")
                ->selectRaw("{$promotionProductSkuExpression} as marketplace_sku")
                ->get();

            $promotionRows
                ->groupBy('order_id')
                ->each(function ($orderItems) use (&$productExtraPromotionByKey) {
                    $first = $orderItems->first();
                    if (! $first) {
                        return;
                    }

                    $settlementRaw = $this->decodePayload($first->settlement_raw_json);
                    $orderRaw = $this->decodePayload($first->order_raw_json ?: $first->raw_payload_json);
                    $sellerVoucher = $first->settlement_id !== null
                        ? (array_key_exists('voucher_from_seller', $settlementRaw)
                            ? (float) $settlementRaw['voucher_from_seller']
                            : (float) ($first->seller_voucher ?? 0))
                        : (float) ($first->voucher_discount ?? 0);
                    $bundleDiscount = 0.0;
                    $comboHemat = 0.0;
                    $promotionItems = (array) ($settlementRaw['items'] ?? ($orderRaw['item_list'] ?? []));
                    foreach ($promotionItems as $promotionItem) {
                        if (! is_array($promotionItem)) {
                            continue;
                        }
                        $split = $this->promotionDiscountSplit($promotionItem);
                        $bundleDiscount += (float) $split['bundle_discount'];
                        $comboHemat += (float) $split['combo_hemat'];
                    }
                    $extraPromotion = max($sellerVoucher + $bundleDiscount + $comboHemat, 0);
                    if ($extraPromotion <= 0) {
                        return;
                    }

                    $lineValue = static function ($item): float {
                        $qty = max(0, (float) ($item->qty ?? 0));
                        foreach ([
                            (float) ($item->line_net_amount ?? 0),
                            (float) ($item->price_after_discount ?? 0) * $qty,
                            (float) ($item->line_gross_amount ?? 0),
                            (float) ($item->price ?? 0) * $qty,
                        ] as $value) {
                            if ($value > 0) {
                                return $value;
                            }
                        }

                        return 0.0;
                    };
                    $orderLineValue = (float) ($first->order_item_value ?? 0);
                    if ($orderLineValue <= 0) {
                        $orderLineValue = (float) $orderItems->sum(fn ($item) => $lineValue($item));
                    }
                    if ($orderLineValue <= 0) {
                        return;
                    }

                    foreach ($orderItems as $item) {
                        $internalItemId = (int) ($item->internal_item_id ?? 0);
                        $productKey = $internalItemId > 0
                            ? 'internal:'.$internalItemId.'|external:'.trim((string) ($item->external_item_id ?? ''))
                            : 'external:'.mb_strtolower(trim((string) $item->marketplace_name)).'|'.trim((string) $item->marketplace_sku);
                        $productExtraPromotionByKey[$productKey] = (float) ($productExtraPromotionByKey->get($productKey, 0))
                            + ($extraPromotion * $lineValue($item) / $orderLineValue);
                    }
                });
        }

        $products = $products->map(function ($product) use ($productExtraPromotionByKey) {
            $internalItemId = (int) ($product->internal_item_id ?? 0);
            $productKey = $internalItemId > 0
                ? 'internal:'.$internalItemId.'|external:'.trim((string) ($product->external_item_id ?? ''))
                : 'external:'.mb_strtolower(trim((string) ($product->marketplace_name ?: $product->name))).'|'.trim((string) $product->sku);
            $extraPromotion = (float) $productExtraPromotionByKey->get($productKey, 0);
            $product->net_sales = max((float) $product->sales - $extraPromotion, 0);

            return $product;
        });

        $productAdMetrics = app(AdsDashboardService::class)->getProductAdMetrics(
            $adStoreIds,
            $storeId,
            $from->toDateString(),
            $to->toDateString(),
        );
        $adMetricsByInternalItem = $productAdMetrics['internal'];
        $adMetricsByChannelItem = $productAdMetrics['external'];
        $productExternalIdsByInternal = collect();
        $internalProductIds = $products
            ->pluck('internal_item_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        if ($internalProductIds->isNotEmpty()) {
            $productExternalIdsByInternal = DB::table('marketplace_order_items as mapped_oi')
                ->join('marketplace_orders as mapped_o', function ($join) {
                    $join->on(DB::raw('COALESCE(mapped_oi.marketplace_order_id, mapped_oi.order_id)'), '=', 'mapped_o.id');
                })
                ->whereIn('mapped_oi.internal_item_id', $internalProductIds->all())
                ->whereNotNull('mapped_oi.external_item_id')
                ->where('mapped_oi.external_item_id', '<>', '')
                ->when($storeId, fn ($query) => $query->where('mapped_o.store_id', $storeId))
                ->select('mapped_oi.internal_item_id', 'mapped_oi.external_item_id')
                ->distinct()
                ->get()
                ->groupBy('internal_item_id')
                ->map(fn ($rows) => $rows->pluck('external_item_id')->map(fn ($id) => (string) $id)->unique()->values());
        }

        // Satu item marketplace dapat berisi banyak varian internal. Metrik
        // iklan pada level item marketplace harus dialokasikan ke varian,
        // bukan ditempel penuh ke setiap baris produk.
        $productExternalIds = $products->mapWithKeys(function ($product, $productIndex) use ($productExternalIdsByInternal) {
            $internalItemId = (int) ($product->internal_item_id ?? 0);
            $externalItemId = trim((string) ($product->external_item_id ?? ''));
            $externalIds = $externalItemId !== ''
                ? collect([$externalItemId])
                : ($internalItemId > 0
                    ? collect($productExternalIdsByInternal->get($internalItemId, collect()))
                    : collect());

            return [$productIndex => $externalIds->map(fn ($itemId) => (string) $itemId)->filter()->unique()->values()];
        });
        $productsByExternalId = collect();
        foreach ($productExternalIds as $productIndex => $externalIds) {
            foreach ($externalIds as $externalId) {
                $productsByExternalId[$externalId] = collect($productsByExternalId->get($externalId, []))
                    ->push($productIndex)
                    ->unique()
                    ->values();
            }
        }

        $products = $products->map(function ($product, $productIndex) use ($adMetricsByInternalItem, $adMetricsByChannelItem, $productExternalIds, $productsByExternalId, $products) {
            $internalItemId = (int) ($product->internal_item_id ?? 0);
            $externalIds = collect($productExternalIds->get($productIndex, collect()));
            $productAdMetrics = ['spend' => 0.0, 'sales' => 0.0, 'conversions' => 0];
            $hasExternalMetrics = false;

            foreach ($externalIds as $externalId) {
                if (! $adMetricsByChannelItem->has($externalId)) {
                    continue;
                }

                $sameExternalProducts = collect($productsByExternalId->get($externalId, []));
                $sameExternalSales = (float) $sameExternalProducts
                    ->map(fn ($sameProductIndex) => (float) ($products->get($sameProductIndex)->sales ?? 0))
                    ->sum();
                $productSales = (float) ($product->sales ?? 0);
                $allocationShare = $sameExternalSales > 0
                    ? $productSales / $sameExternalSales
                    : ($sameExternalProducts->count() > 0 ? 1 / $sameExternalProducts->count() : 0);
                $metrics = $adMetricsByChannelItem->get($externalId);
                $productAdMetrics['spend'] += (float) ($metrics['spend'] ?? 0) * $allocationShare;
                $productAdMetrics['sales'] += (float) ($metrics['sales'] ?? 0) * $allocationShare;
                $productAdMetrics['conversions'] += (int) round((int) ($metrics['conversions'] ?? 0) * $allocationShare);
                $hasExternalMetrics = true;
            }

            if (! $hasExternalMetrics && $internalItemId > 0 && $adMetricsByInternalItem->has($internalItemId)) {
                $sameInternalProducts = $products
                    ->filter(fn ($sameProduct) => (int) ($sameProduct->internal_item_id ?? 0) === $internalItemId)
                    ->values();
                $sameInternalSales = (float) $sameInternalProducts->sum(fn ($sameProduct) => (float) ($sameProduct->sales ?? 0));
                $allocationShare = $sameInternalSales > 0
                    ? (float) ($product->sales ?? 0) / $sameInternalSales
                    : ($sameInternalProducts->count() > 0 ? 1 / $sameInternalProducts->count() : 0);
                $internalMetrics = $adMetricsByInternalItem->get($internalItemId);
                $productAdMetrics = [
                    'spend' => (float) ($internalMetrics['spend'] ?? 0) * $allocationShare,
                    'sales' => (float) ($internalMetrics['sales'] ?? 0) * $allocationShare,
                    'conversions' => (int) round((int) ($internalMetrics['conversions'] ?? 0) * $allocationShare),
                ];
            }

            $product->ad_spend_matched = $hasExternalMetrics || ($internalItemId > 0 && $adMetricsByInternalItem->has($internalItemId));
            $product->ad_spend = (float) ($productAdMetrics['spend'] ?? 0);
            $product->ad_sales = (float) ($productAdMetrics['sales'] ?? 0);
            $product->ad_conversions = (int) ($productAdMetrics['conversions'] ?? 0);
            $product->hpp_total = (float) ($product->hpp ?? 0) * max(0, (int) ($product->qty ?? 0));
            $product->hpp_available = (float) ($product->hpp ?? 0) > 0;
            $product->gross_profit = $product->hpp_available
                ? (float) ($product->net_sales ?? 0) - $product->hpp_total
                : null;
            $product->contribution_profit = $product->gross_profit !== null
                ? $product->gross_profit - $product->ad_spend
                : null;
            $product->gross_margin = $product->gross_profit !== null && (float) ($product->net_sales ?? 0) > 0
                ? ($product->gross_profit / (float) $product->net_sales) * 100
                : null;
            $product->contribution_margin = $product->contribution_profit !== null && (float) ($product->net_sales ?? 0) > 0
                ? ($product->contribution_profit / (float) $product->net_sales) * 100
                : null;

            return $product;
        });

        $paymentStatusExpression = "UPPER(COALESCE(NULLIF(o.payment_status, ''), 'BELUM DITENTUKAN'))";
        $paymentCategorySourceExpression = "LOWER(COALESCE(NULLIF(o.payment_method, ''), NULLIF(o.payment_status, ''), ''))";
        $paymentCategoryExpression = "CASE
            WHEN {$paymentCategorySourceExpression} LIKE '%paylater%'
                OR {$paymentCategorySourceExpression} LIKE '%pay later%'
                OR {$paymentCategorySourceExpression} LIKE '%cicilan%'
                OR {$paymentCategorySourceExpression} LIKE '%installment%' THEN 'pay_later'
            WHEN {$paymentCategorySourceExpression} LIKE '%cod%'
                OR {$paymentCategorySourceExpression} LIKE '%cash on delivery%'
                OR {$paymentCategorySourceExpression} LIKE '%bayar di tempat%' THEN 'cod'
            ELSE 'non_cod'
        END";
        $buyerPaymentExpression = 'COALESCE(NULLIF(payment_ms.buyer_payment_amount, 0), NULLIF(o.total_paid_customer, 0), NULLIF(o.total_amount, 0), NULLIF(o.subtotal_items, 0), 0)';
        $paymentBase = (clone $base)
            ->leftJoin('marketplace_order_settlements as payment_ms', 'payment_ms.order_id', '=', 'o.id');
        $paymentSnapshotRows = (clone $paymentBase)
            ->select([
                'o.id',
                'o.payment_method',
                'o.payment_status',
                'o.subtotal_items',
                'o.shipping_fee_customer',
                'o.total_paid_customer',
                'o.total_amount',
                'o.raw_json',
                'o.raw_payload_json',
                'o.voucher_discount',
                'payment_ms.buyer_payment_amount',
                'payment_ms.seller_voucher',
                'payment_ms.raw_json as settlement_raw_json',
                'payment_ms.shipping_insurance_fee',
            ])
            ->selectRaw("DATE({$dateExpression}) as day")
            ->get()
            ->map(fn ($row) => $this->paymentSnapshotForDashboard($row));
        $payments = (clone $paymentBase)
            ->selectRaw("{$paymentCategoryExpression} as category")
            ->selectRaw('COUNT(DISTINCT o.id) as orders')
            ->selectRaw("COALESCE(SUM({$buyerPaymentExpression}), 0) as buyer_paid")
            ->selectRaw("COALESCE(AVG({$buyerPaymentExpression}), 0) as avg_ticket")
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$paymentStatusExpression} IN ('PAID', 'COMPLETED', 'SELESAI', 'LUNAS') THEN o.id END) as paid_orders")
            ->groupByRaw($paymentCategoryExpression)
            ->orderByDesc('orders')
            ->get()
            ->map(function ($row) {
                $row->category = (string) $row->category;
                $row->orders = (int) $row->orders;
                $row->buyer_paid = (float) $row->buyer_paid;
                $row->avg_ticket = (float) $row->avg_ticket;
                $row->paid_orders = (int) $row->paid_orders;
                $row->order_share = 0;

                return $row;
            });

        $paidPaymentStatuses = "'PAID', 'COMPLETED', 'SELESAI', 'LUNAS'";
        $paymentDaily = (clone $paymentBase)
            ->selectRaw("DATE({$dateExpression}) as day")
            ->selectRaw('COUNT(DISTINCT o.id) as orders')
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$paymentStatusExpression} IN ({$paidPaymentStatuses}) THEN o.id END) as paid_orders")
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$paymentStatusExpression} NOT IN ({$paidPaymentStatuses}) THEN o.id END) as pending_orders")
            ->selectRaw("COALESCE(SUM({$buyerPaymentExpression}), 0) as buyer_paid")
            ->selectRaw("COUNT(DISTINCT CASE WHEN ({$paymentCategoryExpression}) = 'cod' THEN o.id END) as cod_orders")
            ->selectRaw("COUNT(DISTINCT CASE WHEN ({$paymentCategoryExpression}) = 'non_cod' THEN o.id END) as non_cod_orders")
            ->selectRaw("COUNT(DISTINCT CASE WHEN ({$paymentCategoryExpression}) = 'pay_later' THEN o.id END) as pay_later_orders")
            ->selectRaw('COUNT(DISTINCT CASE WHEN COALESCE(o.shipping_fee_customer, 0) > 0 THEN o.id END) as buyer_shipping_orders')
            ->selectRaw("COALESCE(SUM(CASE WHEN ({$paymentCategoryExpression}) = 'cod' THEN {$buyerPaymentExpression} ELSE 0 END), 0) as cod_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN ({$paymentCategoryExpression}) = 'non_cod' THEN {$buyerPaymentExpression} ELSE 0 END), 0) as non_cod_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN ({$paymentCategoryExpression}) = 'pay_later' THEN {$buyerPaymentExpression} ELSE 0 END), 0) as pay_later_amount")
            ->selectRaw('COALESCE(SUM(COALESCE(o.shipping_fee_customer, 0)), 0) as buyer_shipping')
            ->selectRaw("COALESCE(SUM(CASE WHEN {$paymentStatusExpression} IN ({$paidPaymentStatuses}) THEN {$buyerPaymentExpression} ELSE 0 END), 0) as paid_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN {$paymentStatusExpression} NOT IN ({$paidPaymentStatuses}) THEN {$buyerPaymentExpression} ELSE 0 END), 0) as pending_amount")
            ->groupByRaw("DATE({$dateExpression})")
            ->orderByDesc('day')
            ->get()
            ->map(function ($row) {
                $row->orders = (int) $row->orders;
                $row->paid_orders = (int) $row->paid_orders;
                $row->pending_orders = (int) $row->pending_orders;
                $row->buyer_paid = (float) $row->buyer_paid;
                $row->cod_orders = (int) $row->cod_orders;
                $row->non_cod_orders = (int) $row->non_cod_orders;
                $row->pay_later_orders = (int) $row->pay_later_orders;
                $row->buyer_shipping_orders = (int) $row->buyer_shipping_orders;
                $row->cod_amount = (float) $row->cod_amount;
                $row->non_cod_amount = (float) $row->non_cod_amount;
                $row->pay_later_amount = (float) $row->pay_later_amount;
                $row->buyer_shipping = (float) $row->buyer_shipping;
                $row->paid_amount = (float) $row->paid_amount;
                $row->pending_amount = (float) $row->pending_amount;
                $row->aov = $row->orders > 0 ? $row->buyer_paid / $row->orders : 0;
                $row->cod_order_share = $row->orders > 0 ? ($row->cod_orders / $row->orders) * 100 : 0;
                $row->cod_amount_share = $row->buyer_paid > 0 ? ($row->cod_amount / $row->buyer_paid) * 100 : 0;

                return $row;
            });

        $paymentFeeByDay = (clone $paymentBase)
            ->selectRaw("DATE({$dateExpression}) as day")
            ->addSelect('payment_ms.shipping_insurance_fee', 'payment_ms.raw_json', 'o.raw_json as order_raw_json')
            ->get()
            ->groupBy('day')
            ->map(function ($rows) {
                $buyerServiceFee = 0.0;
                $productProtection = 0.0;
                $buyerServiceFeeOrders = 0;
                $productProtectionOrders = 0;
                $rawValue = function ($raw, array $keys): float {
                    $payload = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?: []);
                    foreach ($keys as $key) {
                        $value = data_get($payload, $key);
                        if ($value !== null && $value !== '' && is_numeric($value)) {
                            return abs((float) $value);
                        }
                    }

                    return 0.0;
                };

                foreach ($rows as $row) {
                    $rowBuyerServiceFee = $rawValue($row->raw_json, [
                        'buyer_service_fee',
                        'Buyer Service Fee',
                        'promotion_breakdown.buyer_service_fee',
                        'promotion_breakdown.Buyer Service Fee',
                    ]);
                    if ($rowBuyerServiceFee <= 0) {
                        $rowBuyerServiceFee = $rawValue($row->order_raw_json, [
                            'buyer_service_fee',
                            'Buyer Service Fee',
                            'promotion_breakdown.buyer_service_fee',
                            'promotion_breakdown.Buyer Service Fee',
                        ]);
                    }
                    $rowProductProtection = $rawValue($row->raw_json, [
                        'final_product_protection',
                        'buyer_paid_extended_warranty',
                        'product_protection_fee',
                        'product_protection',
                        'insurance_premium',
                        'premi',
                    ]);
                    $buyerServiceFee += $rowBuyerServiceFee;
                    $productProtection += $rowProductProtection;
                    $buyerServiceFeeOrders += $rowBuyerServiceFee > 0 ? 1 : 0;
                    $productProtectionOrders += $rowProductProtection > 0 ? 1 : 0;
                }

                return (object) [
                    'buyer_service_fee' => $buyerServiceFee,
                    'product_protection' => $productProtection,
                    'buyer_service_fee_orders' => $buyerServiceFeeOrders,
                    'product_protection_orders' => $productProtectionOrders,
                ];
            });

        // Gunakan snapshot yang sama dengan detail pembayaran untuk index:
        // buyer paid, ongkir, subtotal, voucher, dan biaya layanan tidak lagi
        // bergantung pada fallback SQL yang berbeda antar halaman.
        $payments = $paymentSnapshotRows
            ->groupBy('category')
            ->map(function ($rows, $category) {
                $orders = $rows->count();

                return (object) [
                    'category' => (string) $category,
                    'orders' => $orders,
                    'buyer_paid' => (float) $rows->sum('buyer_paid'),
                    'avg_ticket' => $orders > 0 ? (float) $rows->sum('buyer_paid') / $orders : 0,
                    'paid_orders' => $rows->where('is_paid', true)->count(),
                    'order_share' => 0,
                ];
            })
            ->sortByDesc('orders')
            ->values();

        $paymentDaily = $paymentSnapshotRows
            ->groupBy('day')
            ->map(function ($rows, $day) {
                $orders = $rows->count();
                $buyerPaid = (float) $rows->sum('buyer_paid');
                $codRows = $rows->where('category', 'cod');
                $nonCodRows = $rows->where('category', 'non_cod');
                $payLaterRows = $rows->where('category', 'pay_later');

                return (object) [
                    'day' => (string) $day,
                    'orders' => $orders,
                    'paid_orders' => $rows->where('is_paid', true)->count(),
                    'pending_orders' => $rows->where('is_paid', false)->count(),
                    'buyer_paid' => $buyerPaid,
                    'cod_orders' => $codRows->count(),
                    'non_cod_orders' => $nonCodRows->count(),
                    'pay_later_orders' => $payLaterRows->count(),
                    'buyer_shipping_orders' => $rows->where('buyer_shipping', '>', 0)->count(),
                    'cod_amount' => (float) $codRows->sum('buyer_paid'),
                    'non_cod_amount' => (float) $nonCodRows->sum('buyer_paid'),
                    'pay_later_amount' => (float) $payLaterRows->sum('buyer_paid'),
                    'buyer_shipping' => (float) $rows->sum('buyer_shipping'),
                    'paid_amount' => (float) $rows->where('is_paid', true)->sum('buyer_paid'),
                    'pending_amount' => (float) $rows->where('is_paid', false)->sum('buyer_paid'),
                    'aov' => $orders > 0 ? $buyerPaid / $orders : 0,
                    'cod_order_share' => $orders > 0 ? ($codRows->count() / $orders) * 100 : 0,
                    'cod_amount_share' => $buyerPaid > 0 ? ((float) $codRows->sum('buyer_paid') / $buyerPaid) * 100 : 0,
                ];
            })
            ->sortByDesc('day')
            ->values();

        $paymentFeeByDay = $paymentSnapshotRows
            ->groupBy('day')
            ->map(fn ($rows) => (object) [
                'buyer_service_fee' => (float) $rows->sum('buyer_service_fee'),
                'product_protection' => (float) $rows->sum('product_protection'),
                'buyer_service_fee_orders' => $rows->where('buyer_service_fee', '>', 0)->count(),
                'product_protection_orders' => $rows->where('product_protection', '>', 0)->count(),
            ]);

        $paymentDaily = $paymentDaily
            ->map(function ($row) use ($paymentFeeByDay) {
                $fees = $paymentFeeByDay->get((string) $row->day);
                $row->buyer_service_fee = (float) data_get($fees, 'buyer_service_fee', 0);
                $row->product_protection = (float) data_get($fees, 'product_protection', 0);
                $row->buyer_service_fee_orders = (int) data_get($fees, 'buyer_service_fee_orders', 0);
                $row->product_protection_orders = (int) data_get($fees, 'product_protection_orders', 0);

                return $row;
            })
            ->values();

        $paymentSummary = [
            'orders' => (int) $paymentDaily->sum('orders'),
            'paid_orders' => (int) $paymentDaily->sum('paid_orders'),
            'pending_orders' => (int) $paymentDaily->sum('pending_orders'),
            'buyer_paid' => (float) $paymentDaily->sum('buyer_paid'),
            'cod_amount' => (float) $paymentDaily->sum('cod_amount'),
            'paid_amount' => (float) $paymentDaily->sum('paid_amount'),
            'pending_amount' => (float) $paymentDaily->sum('pending_amount'),
        ];
        $paymentOrderAmounts = (clone $paymentBase)
            ->selectRaw("{$buyerPaymentExpression} as buyer_paid")
            ->pluck('buyer_paid')
            ->map(fn ($amount): float => (float) $amount)
            ->filter(fn (float $amount): bool => $amount > 0)
            ->values();
        $paymentSummary['median_ticket'] = (float) ($paymentOrderAmounts->median() ?? 0);
        $paymentSummary['max_ticket'] = (float) ($paymentOrderAmounts->max() ?? 0);
        $paymentSummary['aov'] = $paymentSummary['orders'] > 0
            ? $paymentSummary['buyer_paid'] / $paymentSummary['orders']
            : 0;
        $highValueThreshold = $paymentSummary['aov'] > 0 ? $paymentSummary['aov'] * 1.5 : 0;
        $paymentSummary['high_value_orders'] = $highValueThreshold > 0
            ? $paymentOrderAmounts->filter(fn (float $amount): bool => $amount >= $highValueThreshold)->count()
            : 0;
        $paymentSummary['cod_order_share'] = $paymentSummary['orders'] > 0
            ? ((float) $paymentDaily->sum('cod_orders') / $paymentSummary['orders']) * 100
            : 0;
        $paymentSummary['cod_amount_share'] = $paymentSummary['buyer_paid'] > 0
            ? ((float) $paymentDaily->sum('cod_amount') / $paymentSummary['buyer_paid']) * 100
            : 0;

        $incomePaymentExpression = 'COALESCE(NULLIF(income_ms.buyer_payment_amount, 0), NULLIF(o.total_paid_customer, 0), NULLIF(o.total_amount, 0), NULLIF(o.subtotal_items, 0), 0)';
        $incomeBase = (clone $base)
            ->leftJoin('marketplace_order_settlements as income_ms', 'income_ms.order_id', '=', 'o.id');
        $incomeDaily = (clone $incomeBase)
            ->selectRaw("DATE({$dateExpression}) as day")
            ->selectRaw('COUNT(DISTINCT o.id) as orders')
            ->selectRaw("COUNT(DISTINCT CASE WHEN income_ms.settlement_time IS NOT NULL THEN o.id END) as settled_orders")
            ->selectRaw("COUNT(DISTINCT CASE WHEN income_ms.settlement_time IS NULL THEN o.id END) as pending_orders")
            ->selectRaw("COALESCE(SUM({$incomePaymentExpression}), 0) as buyer_paid")
            ->selectRaw("COALESCE(SUM(CASE WHEN income_ms.settlement_time IS NOT NULL THEN ({$incomePaymentExpression}) ELSE 0 END), 0) as settled_buyer_paid")
            ->selectRaw("COALESCE(SUM(CASE WHEN income_ms.settlement_time IS NULL THEN ({$incomePaymentExpression}) ELSE 0 END), 0) as pending_buyer_paid")
            ->selectRaw('COALESCE(SUM(CASE WHEN income_ms.settlement_time IS NOT NULL THEN COALESCE(income_ms.final_income, 0) ELSE 0 END), 0) as final_income')
            ->groupByRaw("DATE({$dateExpression})")
            ->orderByDesc('day')
            ->get()
            ->map(function ($row) {
                $row->orders = (int) $row->orders;
                $row->settled_orders = (int) $row->settled_orders;
                $row->pending_orders = (int) $row->pending_orders;
                $row->buyer_paid = (float) $row->buyer_paid;
                $row->settled_buyer_paid = (float) $row->settled_buyer_paid;
                $row->pending_buyer_paid = (float) $row->pending_buyer_paid;
                $row->final_income = (float) $row->final_income;

                return $row;
            });
        $incomeSummary = [
            'orders' => (int) $incomeDaily->sum('orders'),
            'settled_orders' => (int) $incomeDaily->sum('settled_orders'),
            'pending_orders' => (int) $incomeDaily->sum('pending_orders'),
            'buyer_paid' => (float) $incomeDaily->sum('buyer_paid'),
            'settled_buyer_paid' => (float) $incomeDaily->sum('settled_buyer_paid'),
            'pending_buyer_paid' => (float) $incomeDaily->sum('pending_buyer_paid'),
            'final_income' => (float) $incomeDaily->sum('final_income'),
        ];

        // Imported order files keep the actual product promotion on the item
        // row. The legacy order-level discount columns are still used by API
        // orders, so combine both sources without double-counting them.
        $linePromotionExpression = <<<'SQL'
CASE
    WHEN COALESCE(oi.line_discount, 0) > 0 THEN oi.line_discount
    WHEN COALESCE(oi.price_original, 0) > COALESCE(oi.price_after_discount, 0)
        THEN (COALESCE(oi.price_original, 0) - COALESCE(oi.price_after_discount, 0)) * COALESCE(oi.qty, 0)
    ELSE 0
END
SQL;
        $promotionItemTotals = DB::table('marketplace_order_items as oi')
            ->selectRaw('COALESCE(oi.marketplace_order_id, oi.order_id) as order_key')
            ->selectRaw("COALESCE(SUM({$linePromotionExpression}), 0) as product_discount")
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('marketplace_order_settlements as promo_settlement')
                    ->whereRaw('promo_settlement.order_id = COALESCE(oi.marketplace_order_id, oi.order_id)');
            })
            ->groupByRaw('COALESCE(oi.marketplace_order_id, oi.order_id)');

        $legacyPromotionAmountExpression = 'COALESCE(ipromo.product_discount, 0)'
            . ' + COALESCE(o.voucher_discount, 0)'
            . ' + COALESCE(o.other_discount, 0)'
            . ' + COALESCE(o.shipping_discount_platform, 0)';
        $visiblePromotionAmountExpression = 'COALESCE(ipromo.product_discount, 0)'
            . ' + COALESCE(o.voucher_discount, 0)';

        // Legacy/imported orders do not have a settlement breakdown. Keep them
        // separate from settlement-backed orders so voucher and bundle values
        // are not added twice.
        $promotionDaily = (clone $base)
            ->leftJoinSub($promotionItemTotals, 'ipromo', 'ipromo.order_key', '=', 'o.id')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('marketplace_order_settlements as promo_settlement')
                    ->whereColumn('promo_settlement.order_id', 'o.id');
            })
            ->selectRaw("DATE({$dateExpression}) as day")
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$visiblePromotionAmountExpression} > 0 THEN o.id END) as promotion_orders")
            ->selectRaw('COALESCE(SUM(ipromo.product_discount), 0) as product_discount')
            ->selectRaw('COUNT(DISTINCT CASE WHEN COALESCE(ipromo.product_discount, 0) > 0 THEN o.id END) as product_discount_orders')
            ->selectRaw('COALESCE(SUM(o.voucher_discount), 0) as voucher_store')
            ->selectRaw('COUNT(DISTINCT CASE WHEN COALESCE(o.voucher_discount, 0) > 0 THEN o.id END) as voucher_store_orders')
            ->selectRaw("COALESCE(SUM(CASE WHEN COALESCE(o.voucher_discount, 0) > 0 THEN ({$subtotalExpression}) ELSE 0 END), 0) as voucher_store_sales")
            ->selectRaw('0 as voucher_platform')
            ->selectRaw('0 as voucher_platform_orders')
            ->selectRaw('0 as voucher_platform_sales')
            ->selectRaw('0 as bundle_discount')
            ->selectRaw('0 as bundle_discount_orders')
            ->selectRaw('0 as bundle_discount_sales')
            ->selectRaw('0 as combo_hemat')
            ->selectRaw('0 as combo_hemat_orders')
            ->selectRaw('0 as combo_hemat_sales')
            ->selectRaw('COALESCE(SUM(o.other_discount), 0) as other_discount')
            ->selectRaw('COALESCE(SUM(o.shipping_discount_platform), 0) as shipping_discount')
            ->selectRaw("COALESCE(SUM({$visiblePromotionAmountExpression}), 0) as total_promotion")
            ->groupByRaw("DATE({$dateExpression})")
            ->orderByDesc('day')
            ->get()
            ->keyBy('day');

        $settlementPromotionRows = DB::table('marketplace_order_settlements as ms')
            ->join('marketplace_orders as o', 'o.id', '=', 'ms.order_id')
            ->leftJoin('stores as st', 'st.id', '=', 'o.store_id')
            ->leftJoin('channels as ch', 'ch.id', '=', 'st.channel_id')
            ->whereRaw("{$dateExpression} IS NOT NULL")
            ->whereDate(DB::raw($dateExpression), '>=', $from->toDateString())
            ->whereDate(DB::raw($dateExpression), '<=', $to->toDateString())
            ->whereRaw("{$statusExpression} NOT IN (" . implode(',', array_fill(0, count(self::NON_REVENUE_STATUSES), '?')) . ')', self::NON_REVENUE_STATUSES)
            ->when($storeId, fn ($query) => $query->where('o.store_id', $storeId))
            ->when($platformCode, fn ($query) => $query->whereIn(DB::raw('UPPER(ch.code)'), $platformCodes))
            ->select([
                'ms.order_id',
                'ms.seller_voucher',
                'ms.shipping_fee_subsidy',
                'ms.raw_json',
                'o.raw_json as order_raw_json',
            ])
            ->selectRaw("DATE({$dateExpression}) as day")
            ->selectRaw("{$subtotalExpression} as order_subtotal")
            ->when($isPromotionDummy, fn ($query) => $query->whereJsonContains('o.meta->dummy_source', self::PROMOTION_DUMMY_SOURCE))
            ->get();

        foreach ($settlementPromotionRows as $settlementRow) {
            $raw = is_array($settlementRow->raw_json)
                ? $settlementRow->raw_json
                : (json_decode((string) $settlementRow->raw_json, true) ?: []);
            $orderRaw = json_decode((string) $settlementRow->order_raw_json, true) ?: [];

            $voucherStore = array_key_exists('voucher_from_seller', $raw)
                ? (float) $raw['voucher_from_seller']
                : (float) ($settlementRow->seller_voucher ?? 0);
            $voucherPlatform = array_key_exists('voucher_from_shopee', $raw)
                ? (float) $raw['voucher_from_shopee']
                : 0.0;
            $shippingDiscount = array_key_exists('shopee_shipping_rebate', $raw)
                ? (float) $raw['shopee_shipping_rebate']
                : (float) ($settlementRow->shipping_fee_subsidy ?? 0);

            $productDiscount = 0.0;
            $bundleDiscount = 0.0;
            $comboHemat = 0.0;
            $settlementItems = (array) ($raw['items'] ?? []);
            $promotionItems = $settlementItems ?: (array) ($orderRaw['item_list'] ?? []);
            foreach ($promotionItems as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $split = $this->promotionDiscountSplit($item);
                $productDiscount += $split['product_discount'];
                $bundleDiscount += $split['bundle_discount'];
                $comboHemat += $split['combo_hemat'];
            }

            // Some settlement payloads do not include item details. Preserve
            // the seller discount as an unclassified product discount rather
            // than silently dropping it from the dashboard.
            if ($productDiscount <= 0 && $bundleDiscount <= 0 && ! empty($raw['seller_discount'])) {
                $productDiscount = (float) $raw['seller_discount'];
            }

            $day = (string) $settlementRow->day;
            if (! isset($promotionDaily[$day])) {
                $promotionDaily[$day] = (object) [
                    'day' => $day,
                    'promotion_orders' => 0,
                    'product_discount' => 0,
                    'product_discount_orders' => 0,
                    'voucher_store' => 0,
                    'voucher_store_orders' => 0,
                    'voucher_store_sales' => 0,
                    'voucher_platform' => 0,
                    'voucher_platform_orders' => 0,
                    'voucher_platform_sales' => 0,
                    'bundle_discount' => 0,
                    'bundle_discount_orders' => 0,
                    'bundle_discount_sales' => 0,
                    'combo_hemat' => 0,
                    'combo_hemat_orders' => 0,
                    'combo_hemat_sales' => 0,
                    'other_discount' => 0,
                    'shipping_discount' => 0,
                    'total_promotion' => 0,
                ];
            }

            $row = $promotionDaily[$day];
            $promotionTotal = $productDiscount + $voucherStore + $voucherPlatform + $bundleDiscount + $comboHemat;
            if ($promotionTotal > 0) {
                $row->promotion_orders++;
            }
            $row->product_discount += $productDiscount;
            if ($productDiscount > 0) {
                $row->product_discount_orders++;
            }
            $row->voucher_store += $voucherStore;
            if ($voucherStore > 0) {
                $row->voucher_store_orders++;
                $row->voucher_store_sales += (float) $settlementRow->order_subtotal;
            }
            $row->voucher_platform += $voucherPlatform;
            if ($voucherPlatform > 0) {
                $row->voucher_platform_orders++;
                $row->voucher_platform_sales += (float) $settlementRow->order_subtotal;
            }
            $row->bundle_discount += $bundleDiscount;
            if ($bundleDiscount > 0) {
                $row->bundle_discount_orders++;
                $row->bundle_discount_sales += (float) $settlementRow->order_subtotal;
            }
            $row->combo_hemat += $comboHemat;
            if ($comboHemat > 0) {
                $row->combo_hemat_orders++;
                $row->combo_hemat_sales += (float) $settlementRow->order_subtotal;
            }
            $row->shipping_discount += $shippingDiscount;
            $row->total_promotion += $promotionTotal;
        }

        $orderBeforeDiscountByDay = $orderRows
            ->groupBy('day')
            ->map(fn ($rows) => (float) $rows->sum('subtotal'));
        $orderCountByDay = $orderRows
            ->groupBy('day')
            ->map(fn ($rows) => $rows->count());

        $promotionDaily = $promotionDaily
            ->map(function ($row) use ($orderBeforeDiscountByDay, $orderCountByDay) {
                $row->order_before_discount = (float) ($orderBeforeDiscountByDay[(string) $row->day] ?? 0);
                $row->order_count = (int) ($orderCountByDay[(string) $row->day] ?? 0);

                return $row;
            })
            ->sortByDesc('day')
            ->values();
        $promotionByDay = $promotionDaily->keyBy(fn ($row) => (string) $row->day);
        $paymentDaily = $paymentDaily
            ->map(function ($row) use ($promotionByDay) {
                $promotion = $promotionByDay->get((string) $row->day);
                $sellerGrossSales = (float) data_get($promotion, 'order_before_discount', 0);
                $sellerDiscounts = (float) data_get($promotion, 'product_discount', 0)
                    + (float) data_get($promotion, 'voucher_store', 0)
                    + (float) data_get($promotion, 'combo_hemat', 0)
                    + (float) data_get($promotion, 'bundle_discount', 0);
                $row->voucher_store = (float) data_get($promotion, 'voucher_store', 0);
                $row->voucher_platform = (float) data_get($promotion, 'voucher_platform', 0);
                $row->voucher_store_orders = (int) data_get($promotion, 'voucher_store_orders', 0);
                $row->voucher_platform_orders = (int) data_get($promotion, 'voucher_platform_orders', 0);
                $row->promotion_orders = (int) data_get($promotion, 'promotion_orders', 0);
                $row->total_promotion = (float) data_get($promotion, 'total_promotion', 0);
                $row->seller_net_sales = max($sellerGrossSales - $sellerDiscounts, 0);
                $row->seller_net_sales_orders = (int) data_get($promotion, 'order_count', $row->orders);

                return $row;
            })
            ->values();
        $promotionOrders = (int) $promotionDaily->sum('promotion_orders');
        $summary['promotion_total'] = (float) $promotionDaily->sum('total_promotion');
        $summary['net_total'] = max($summary['subtotal'] - $summary['promotion_total'], 0);
        $paymentSummary['buyer_shipping'] = (float) $paymentDaily->sum('buyer_shipping');
        $paymentSummary['total_promotion'] = (float) $paymentDaily->sum('total_promotion');

        // AOV dashboard memakai nilai neto setelah promosi agar selaras dengan
        // nilai yang benar-benar direalisasikan per order.
        $daily = $daily->map(function ($row) use ($promotionByDay) {
            $promotionTotal = (float) data_get($promotionByDay->get((string) $row->day), 'total_promotion', 0);
            $row->net_total = max((float) $row->subtotal - $promotionTotal, 0);
            $row->aov = $row->orders > 0 ? $row->net_total / $row->orders : 0;

            return $row;
        })->values();
        $summary['aov'] = $summary['orders'] > 0 ? $summary['net_total'] / $summary['orders'] : 0;

        $shippingFailedStatusesSql = "'" . implode("', '", self::SHIPPING_FAILED_STATUSES) . "'";
        $shippingStatusExpression = "CASE WHEN COALESCE(o.delivery_failed, 0) = 1 OR UPPER(COALESCE(NULLIF(o.tracking_status, ''), '')) IN ({$shippingFailedStatusesSql}) THEN 'FAILED_DELIVERY' ELSE UPPER(COALESCE(NULLIF(o.order_status, ''), NULLIF(o.status, ''), 'BELUM DITENTUKAN')) END";
        $shippingExcludedPlaceholders = implode(',', array_fill(0, count(self::SHIPPING_EXCLUDED_STATUSES), '?'));
        $shippingBase = DB::table('marketplace_orders as o')
            ->leftJoin('stores as st', 'st.id', '=', 'o.store_id')
            ->leftJoin('channels as ch', 'ch.id', '=', 'st.channel_id')
            ->whereRaw("{$dateExpression} IS NOT NULL")
            ->whereDate(DB::raw($dateExpression), '>=', $from->toDateString())
            ->whereDate(DB::raw($dateExpression), '<=', $to->toDateString())
            ->whereRaw("{$shippingStatusExpression} NOT IN ({$shippingExcludedPlaceholders})", self::SHIPPING_EXCLUDED_STATUSES)
            ->when($isPromotionDummy, fn ($query) => $query->whereJsonContains('o.meta->dummy_source', self::PROMOTION_DUMMY_SOURCE))
            ->when($storeId, fn ($query) => $query->where('o.store_id', $storeId))
            ->when($platformCode, fn ($query) => $query->whereIn(DB::raw('UPPER(ch.code)'), $platformCodes));
        $shippingReturnStatusesSql = "'" . implode("', '", self::SHIPPING_RETURN_STATUSES) . "'";

        $shipping = (clone $shippingBase)
            ->selectRaw("{$shippingStatusExpression} as status")
            ->selectRaw('COUNT(DISTINCT o.id) as orders')
            ->groupByRaw($shippingStatusExpression)
            ->orderByDesc('orders')
            ->limit(8)
            ->get()
            ->map(function ($row) {
                $row->label = ucwords(strtolower(str_replace('_', ' ', (string) $row->status)));
                return $row;
            });

        $shippingDaily = (clone $shippingBase)
            ->selectRaw("DATE({$dateExpression}) as day")
            ->selectRaw('COUNT(DISTINCT o.id) as orders')
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$shippingStatusExpression} IN ('PENDING', 'INVOICE_PENDING', 'READY_TO_SHIP', 'MATCHED') THEN o.id END) as ready_orders")
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$shippingStatusExpression} IN ('PROCESSED', 'READY_TO_HANDOVER', 'SHIPPED', 'TO_CONFIRM_RECEIVE') THEN o.id END) as transit_orders")
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$shippingStatusExpression} IN ('COMPLETED', 'SELESAI') THEN o.id END) as completed_orders")
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$shippingStatusExpression} = 'FAILED_DELIVERY' THEN o.id END) as failed_orders")
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$shippingStatusExpression} IN ({$shippingReturnStatusesSql}) THEN o.id END) as return_orders")
            ->groupByRaw("DATE({$dateExpression})")
            ->orderByDesc('day')
            ->get()
            ->map(function ($row) {
                $row->orders = (int) $row->orders;
                $row->ready_orders = (int) $row->ready_orders;
                $row->transit_orders = (int) $row->transit_orders;
                $row->completed_orders = (int) $row->completed_orders;
                $row->failed_orders = (int) $row->failed_orders;
                $row->return_orders = (int) $row->return_orders;
                $row->other_orders = max(
                    0,
                    $row->orders - $row->ready_orders - $row->transit_orders - $row->completed_orders - $row->failed_orders - $row->return_orders,
                );

                return $row;
            });

        $shippingKpi = [
            'total' => (int) $shippingDaily->sum('orders'),
            'ready' => (int) $shippingDaily->sum('ready_orders'),
            'transit' => (int) $shippingDaily->sum('transit_orders'),
            'completed' => (int) $shippingDaily->sum('completed_orders'),
            'failed' => (int) $shippingDaily->sum('failed_orders'),
            'return' => (int) $shippingDaily->sum('return_orders'),
        ];
        $shippingKpi['exception'] = $shippingKpi['failed'] + $shippingKpi['return'];
        $shippingKpi['exception_rate'] = $shippingKpi['total'] > 0
            ? ($shippingKpi['exception'] / $shippingKpi['total']) * 100
            : 0;

        $comparisonMonth = null;
        $comparisonMonthPrevious = null;
        $comparisonMonthPreviousTwo = null;
        $comparisonPeriod = null;
        $comparisonPeriodPrevious = null;
        $comparisonPeriodPreviousTwo = null;
        if (! $request->boolean('_sales_comparison')) {
            $loadComparison = function ($comparisonFrom, $comparisonTo) use ($request) {
                $comparisonRequest = Request::create($request->url(), 'GET', array_merge($request->query(), [
                    'date_from' => $comparisonFrom->toDateString(),
                    'date_to' => $comparisonTo->toDateString(),
                    '_sales_comparison' => 1,
                ]));
                $comparisonView = $this->index($comparisonRequest);

                return [
                    'from' => $comparisonFrom->toDateString(),
                    'to' => $comparisonTo->toDateString(),
                    'data' => $comparisonView->getData(),
                ];
            };

            $comparisonMonth = $loadComparison((clone $from)->subMonthNoOverflow(), (clone $to)->subMonthNoOverflow());
            $comparisonMonthPreviousFrom = (clone $from)->subMonthsNoOverflow(2);
            $comparisonMonthPreviousTo = (clone $to)->subMonthsNoOverflow(2);
            $comparisonMonthPrevious = $loadComparison($comparisonMonthPreviousFrom, $comparisonMonthPreviousTo);

            $comparisonMonthPreviousTwoFrom = (clone $from)->subMonthsNoOverflow(3);
            $comparisonMonthPreviousTwoTo = (clone $to)->subMonthsNoOverflow(3);
            $comparisonMonthPreviousTwo = $loadComparison($comparisonMonthPreviousTwoFrom, $comparisonMonthPreviousTwoTo);
            $periodDays = $from->diffInDays($to) + 1;
            $comparisonPeriodTo = (clone $from)->subDay();
            $comparisonPeriodFrom = (clone $comparisonPeriodTo)->subDays($periodDays - 1);
            $comparisonPeriod = $loadComparison($comparisonPeriodFrom, $comparisonPeriodTo);

            $comparisonPeriodPreviousTo = (clone $comparisonPeriodFrom)->subDay();
            $comparisonPeriodPreviousFrom = (clone $comparisonPeriodPreviousTo)->subDays($periodDays - 1);
            $comparisonPeriodPrevious = $loadComparison($comparisonPeriodPreviousFrom, $comparisonPeriodPreviousTo);

            $comparisonPeriodPreviousTwoTo = (clone $comparisonPeriodPreviousFrom)->subDay();
            $comparisonPeriodPreviousTwoFrom = (clone $comparisonPeriodPreviousTwoTo)->subDays($periodDays - 1);
            $comparisonPeriodPreviousTwo = $loadComparison($comparisonPeriodPreviousTwoFrom, $comparisonPeriodPreviousTwoTo);
        }

        return view('marketplace.dashboard.sales', [
            'summary' => $summary,
            'daily' => $daily,
            'products' => $products,
            'payments' => $payments,
            'paymentDaily' => $paymentDaily,
            'paymentSummary' => $paymentSummary,
            'incomeDaily' => $incomeDaily,
            'incomeSummary' => $incomeSummary,
            'promotionDaily' => $promotionDaily,
            'promotionOrders' => $promotionOrders,
            'adSpendDaily' => $adSpendDaily,
            'adOrdersDaily' => $adOrdersDaily,
            'adSpendTotal' => $adSpendTotal,
            'adImpressionsTotal' => $adImpressionsTotal,
            'adClicksTotal' => $adClicksTotal,
            'adOrdersTotal' => $adOrdersTotal,
            'adSalesTotal' => $adSalesTotal,
            'adCtr' => $adCtr,
            'adCvr' => $adCvr,
            'shipping' => $shipping,
            'shippingDaily' => $shippingDaily,
            'shippingKpi' => $shippingKpi,
            'orderDetails' => $orderDetails,
            'comparisonMonth' => $comparisonMonth,
            'comparisonMonthPrevious' => $comparisonMonthPrevious,
            'comparisonMonthPreviousTwo' => $comparisonMonthPreviousTwo,
            'comparisonPeriod' => $comparisonPeriod,
            'comparisonPeriodPrevious' => $comparisonPeriodPrevious,
            'comparisonPeriodPreviousTwo' => $comparisonPeriodPreviousTwo,
            'stores' => $stores,
            'platforms' => $platforms,
            'filters' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'store_id' => $storeId,
                'platform' => $platformCode,
                'platform_codes' => $platformCodes,
                'comparison_mode' => $comparisonMode,
                'dummy' => $isPromotionDummy,
            ],
        ]);
    }

    public function productOrders(Request $request)
    {
        $name = trim((string) $request->query('name', ''));
        $sku = trim((string) $request->query('sku', ''));
        $internalItemId = $request->integer('internal_item_id') ?: null;

        if ($name === '' && $internalItemId === null) {
            return response()->json(['message' => 'Nama produk wajib diisi.'], 422);
        }

        $today = now()->startOfDay();
        $from = $this->dateOrDefault($request->query('date_from'), (clone $today)->subDays(30));
        $to = $this->dateOrDefault($request->query('date_to'), $today);

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($from->diffInDays($to) > 366) {
            $from = (clone $to)->subDays(366);
        }

        $storeId = $request->integer('store_id') ?: null;
        $platformCode = $this->normalizePlatformCode($request->query('platform'));
        $platformCodes = $this->platformCodes($platformCode);
        $isPromotionDummy = $request->boolean('dummy') && app()->environment(['local', 'testing']);
        $dateExpression = 'COALESCE(o.ordered_at, o.order_date)';
        $statusExpression = "UPPER(COALESCE(NULLIF(o.order_status, ''), NULLIF(o.status, ''), ''))";
        $nameExpression = "COALESCE(NULLIF(oi.item_name, ''), NULLIF(oi.item_name_snapshot, ''), NULLIF(oi.variant_name, ''), NULLIF(oi.variant_snapshot, ''), 'Produk tanpa nama')";
        $skuExpression = "COALESCE(NULLIF(oi.item_sku, ''), NULLIF(oi.marketplace_sku, ''), NULLIF(oi.model_sku, ''), NULLIF(oi.external_sku, ''), NULLIF(oi.item_code_snapshot, ''), '-')";

        $rows = DB::table('marketplace_order_items as oi')
            ->join('marketplace_orders as o', function ($join) {
                $join->on(DB::raw('COALESCE(oi.marketplace_order_id, oi.order_id)'), '=', 'o.id');
            })
            ->leftJoin('marketplace_order_settlements as s', 's.order_id', '=', 'o.id')
            ->leftJoin('stores as st', 'st.id', '=', 'o.store_id')
            ->leftJoin('channels as ch', 'ch.id', '=', 'st.channel_id')
            ->whereRaw("{$dateExpression} IS NOT NULL")
            ->whereDate(DB::raw($dateExpression), '>=', $from->toDateString())
            ->whereDate(DB::raw($dateExpression), '<=', $to->toDateString())
            ->whereRaw("{$statusExpression} NOT IN (" . implode(',', array_fill(0, count(self::NON_REVENUE_STATUSES), '?')) . ')', self::NON_REVENUE_STATUSES)
            ->when($isPromotionDummy, fn ($query) => $query->whereJsonContains('o.meta->dummy_source', self::PROMOTION_DUMMY_SOURCE))
            ->when($storeId, fn ($query) => $query->where('o.store_id', $storeId))
            ->when($platformCode, fn ($query) => $query->whereIn(DB::raw('UPPER(ch.code)'), $platformCodes))
            ->when($internalItemId, fn ($query) => $query->where('oi.internal_item_id', $internalItemId))
            ->when(! $internalItemId, fn ($query) => $query->whereRaw("{$nameExpression} = ?", [$name]))
            ->when(! $internalItemId && $sku !== '' && $sku !== '-', fn ($query) => $query->whereRaw("{$skuExpression} = ?", [$sku]))
            ->select([
                'o.id',
                'o.channel_order_id',
                'o.external_order_id',
                'o.buyer_username',
                'o.buyer_name',
                'o.payment_status',
                'o.order_status',
                'o.status',
                'o.total_paid_customer',
                'o.total_amount',
                'o.subtotal_items',
                's.buyer_payment_amount',
                'oi.qty',
                'oi.price',
                'oi.price_after_discount',
                'oi.line_net_amount',
                'oi.line_gross_amount',
            ])
            ->selectRaw("{$dateExpression} as order_at")
            ->selectRaw("{$nameExpression} as product_name")
            ->selectRaw("{$skuExpression} as product_sku")
            ->orderByDesc('order_at')
            ->orderByDesc('o.id')
            ->limit(500)
            ->get();

        $orders = $rows
            ->groupBy('id')
            ->map(function ($items) {
                $first = $items->first();
                $sales = (float) $items->sum(function ($item) {
                    $lineNet = (float) ($item->line_net_amount ?? 0);
                    $price = (float) ($item->price ?? 0);
                    $qty = max(0, (int) ($item->qty ?? 0));
                    $lineGross = (float) ($item->line_gross_amount ?? 0);

                    if ($lineNet > 0) {
                        return $lineNet;
                    }

                    if ($price > 0) {
                        return $price * $qty;
                    }

                    return $lineGross > 0 ? $lineGross : ((float) ($item->price_after_discount ?? 0) * $qty);
                });
                $buyerPayment = collect([
                    $first->buyer_payment_amount,
                    $first->total_paid_customer,
                    $first->total_amount,
                    $first->subtotal_items,
                ])->map(fn ($value) => (float) $value)->first(fn (float $value) => $value > 0) ?? 0;

                return [
                    'id' => (int) $first->id,
                    'order_number' => $this->orderNumberForDisplay($first->channel_order_id, $first->external_order_id, (int) $first->id),
                    'order_at' => $first->order_at,
                    'buyer' => $first->buyer_username ?: ($first->buyer_name ?: 'Pelanggan marketplace'),
                    'qty' => (int) $items->sum(fn ($item) => max(0, (int) ($item->qty ?? 0))),
                    'sales' => $sales,
                    'buyer_payment' => $buyerPayment,
                    'payment_status' => $first->payment_status ?: 'Belum ditentukan',
                    'order_status' => $first->order_status ?: ($first->status ?: 'Belum ditentukan'),
                ];
            })
            ->values();

        return response()->json([
            'product' => ['name' => $name, 'sku' => $sku],
            'orders' => $orders,
        ]);
    }

    public function paymentDetail(Request $request, string $date)
    {
        try {
            $selectedDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            if ($selectedDate->format('Y-m-d') !== $date) {
                abort(404);
            }
        } catch (\Throwable) {
            abort(404);
        }

        $isDashboardDummy = $request->boolean('dummy') && app()->environment(['local', 'testing']);
        $storeId = $request->integer('store_id') ?: null;
        $platformCode = $this->normalizePlatformCode($request->query('platform'));
        $platformCodes = $this->platformCodes($platformCode);
        $stores = Store::query()
            ->where('is_active', true)
            ->with('channel')
            ->orderBy('name')
            ->get();

        if ($storeId && ! $stores->contains('id', $storeId)) {
            $storeId = null;
        }

        $dateExpression = 'COALESCE(o.ordered_at, o.order_date)';
        $orderStatusExpression = "UPPER(COALESCE(NULLIF(o.order_status, ''), NULLIF(o.status, ''), ''))";
        $paymentStatusExpression = "UPPER(COALESCE(NULLIF(o.payment_status, ''), 'BELUM DITENTUKAN'))";
        $productSubtotalExpression = 'CASE WHEN COALESCE(o.subtotal_items, 0) > 0 THEN o.subtotal_items ELSE COALESCE(o.total_amount, 0) END';
        $buyerPaymentExpression = 'COALESCE(NULLIF(payment_ms.buyer_payment_amount, 0), NULLIF(o.total_paid_customer, 0), NULLIF(o.total_amount, 0), NULLIF(o.subtotal_items, 0), 0)';
        $nonRevenuePlaceholders = implode(',', array_fill(0, count(self::NON_REVENUE_STATUSES), '?'));

        $rows = DB::table('marketplace_orders as o')
            ->leftJoin('marketplace_order_settlements as payment_ms', 'payment_ms.order_id', '=', 'o.id')
            ->leftJoin('stores as st', 'st.id', '=', 'o.store_id')
            ->leftJoin('channels as ch', 'ch.id', '=', 'st.channel_id')
            ->whereRaw("{$dateExpression} IS NOT NULL")
            ->whereDate(DB::raw($dateExpression), $selectedDate->toDateString())
            ->whereRaw("{$orderStatusExpression} NOT IN ({$nonRevenuePlaceholders})", self::NON_REVENUE_STATUSES)
            ->when($isDashboardDummy, fn ($query) => $query->whereJsonContains('o.meta->dummy_source', self::PROMOTION_DUMMY_SOURCE))
            ->when($storeId, fn ($query) => $query->where('o.store_id', $storeId))
            ->when($platformCode, fn ($query) => $query->whereIn(DB::raw('UPPER(ch.code)'), $platformCodes))
            ->select([
                'o.id',
                'o.channel_order_id',
                'o.external_order_id',
                'o.buyer_username',
                'o.buyer_name',
                'o.payment_method',
                'o.payment_status',
                'o.shipping_fee_customer',
                'o.subtotal_items',
                'o.total_paid_customer',
                'o.total_amount',
                'o.raw_json as order_raw_json',
                'o.raw_payload_json',
                'o.voucher_discount',
                'payment_ms.id as settlement_id',
                'payment_ms.buyer_payment_amount',
                'payment_ms.raw_json as settlement_raw_json',
                'payment_ms.seller_voucher',
                'st.name as store_name',
                'ch.code as channel_code',
            ])
            ->selectRaw("{$dateExpression} as order_at")
            ->selectRaw('COALESCE(o.paid_at, o.payment_date) as paid_at')
            ->selectRaw("{$paymentStatusExpression} as payment_state")
            ->selectRaw("{$orderStatusExpression} as order_state")
            ->selectRaw("{$productSubtotalExpression} as product_subtotal")
            ->selectRaw("{$buyerPaymentExpression} as buyer_paid_amount")
            ->orderByDesc('order_at')
            ->orderByDesc('o.id')
            ->limit(500)
            ->get()
            ->map(function ($row) {
                $row->order_number = $this->orderNumberForDisplay(
                    $row->channel_order_id,
                    $row->external_order_id,
                    (int) $row->id,
                );
                $row->customer = $row->buyer_username ?: ($row->buyer_name ?: 'Pelanggan marketplace');
                $row->store = $row->store_name ?: 'Toko marketplace';
                $row->channel = $row->channel_code ? ucfirst((string) $row->channel_code) : 'Marketplace';
                $row->payment = $row->payment_method ?: 'Belum ditentukan';
                $settlementRaw = $this->decodePayload($row->settlement_raw_json);
                $orderRaw = $this->decodePayload($row->order_raw_json ?? $row->raw_payload_json);
                $liveData = $orderRaw['order_list'][0]
                    ?? $orderRaw['response']['order_list'][0]
                    ?? (isset($orderRaw['order_sn']) ? $orderRaw : []);
                $incomeRaw = array_replace((array) ($liveData['income_details'] ?? []), $settlementRaw);
                $promotionAmounts = $this->paymentPromotionAmounts(
                    $settlementRaw,
                    $liveData,
                    $incomeRaw,
                    (float) ($row->seller_voucher ?? 0),
                    (float) ($row->voucher_discount ?? 0),
                );
                $row->product_subtotal = $this->paymentSubtotal(
                    $incomeRaw,
                    $row->subtotal_items,
                    $row->product_subtotal,
                    $settlementRaw,
                    $liveData,
                );
                $row->buyer_paid_amount = $this->paymentBuyerPaid(
                    $incomeRaw,
                    $row->total_paid_customer,
                    $row->total_amount,
                    $row->buyer_payment_amount,
                    $liveData,
                );
                $row->shipping_fee = $this->paymentBuyerShipping(
                    $incomeRaw,
                    $row->shipping_fee_customer,
                    $liveData,
                );
                $row->voucher_store = (float) ($promotionAmounts['voucher_store'] ?? 0);
                $row->voucher_platform = (float) ($promotionAmounts['voucher_platform'] ?? 0);
                $buyerCoins = $this->firstPayloadAmount(
                    [$incomeRaw, $orderRaw, $orderRaw['promotion_breakdown'] ?? []],
                    ['coin', 'coins', 'coin_discount', 'cashback_coin'],
                );
                $buyerTransactionFee = $this->firstPayloadAmount(
                    [$incomeRaw, $orderRaw, $orderRaw['promotion_breakdown'] ?? []],
                    ['buyer_transaction_fee', 'buyer_service_fee'],
                );
                $buyerServiceBalance = $row->buyer_paid_amount
                    - $row->product_subtotal
                    - $row->shipping_fee
                    + $row->voucher_platform
                    + $row->voucher_store
                    + $buyerCoins;
                $row->buyer_service_fee = $row->buyer_paid_amount > 0
                    ? ($buyerServiceBalance >= 0 ? $buyerServiceBalance : $buyerTransactionFee)
                    : ($buyerTransactionFee > 0 ? $buyerTransactionFee : 2000);
                $row->total_paid = $row->buyer_paid_amount;
                $row->is_paid = in_array((string) $row->payment_state, ['PAID', 'COMPLETED', 'SELESAI', 'LUNAS'], true);

                return $row;
            });

        $summary = [
            'orders' => $rows->count(),
            'paid_orders' => $rows->where('is_paid', true)->count(),
            'pending_orders' => $rows->where('is_paid', false)->count(),
            'subtotal' => (float) $rows->sum('buyer_paid_amount'),
            'shipping_fee' => (float) $rows->sum('shipping_fee'),
            'total_paid' => (float) $rows->sum('total_paid'),
            'paid_amount' => (float) $rows->where('is_paid', true)->sum('buyer_paid_amount'),
            'pending_amount' => (float) $rows->where('is_paid', false)->sum('buyer_paid_amount'),
            'methods' => $rows->pluck('payment')->filter()->unique()->count(),
        ];
        $detailPaymentAmounts = $rows
            ->pluck('buyer_paid_amount')
            ->map(fn ($amount): float => (float) $amount)
            ->filter(fn (float $amount): bool => $amount > 0)
            ->values();
        $summary['median_ticket'] = (float) ($detailPaymentAmounts->median() ?? 0);
        $summary['max_ticket'] = (float) ($detailPaymentAmounts->max() ?? 0);
        $summary['aov'] = $summary['orders'] > 0 ? $summary['subtotal'] / $summary['orders'] : 0;
        $detailHighValueThreshold = $summary['aov'] > 0 ? $summary['aov'] * 1.5 : 0;
        $summary['high_value_orders'] = $detailHighValueThreshold > 0
            ? $detailPaymentAmounts->filter(fn (float $amount): bool => $amount >= $detailHighValueThreshold)->count()
            : 0;

        return view('marketplace.dashboard.payment-detail', [
            'rows' => $rows,
            'summary' => $summary,
            'selectedDate' => $selectedDate,
            'stores' => $stores,
            'filters' => [
                'store_id' => $storeId,
                'platform' => $platformCode,
                'dummy' => $isDashboardDummy,
            ],
        ]);
    }

    public function shippingDetail(Request $request, string $date)
    {
        try {
            $selectedDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            if ($selectedDate->format('Y-m-d') !== $date) {
                abort(404);
            }
        } catch (\Throwable) {
            abort(404);
        }

        $isDashboardDummy = $request->boolean('dummy') && app()->environment(['local', 'testing']);
        $storeId = $request->integer('store_id') ?: null;
        $platformCode = $this->normalizePlatformCode($request->query('platform'));
        $platformCodes = $this->platformCodes($platformCode);
        $stores = Store::query()
            ->where('is_active', true)
            ->with('channel')
            ->orderBy('name')
            ->get();

        if ($storeId && ! $stores->contains('id', $storeId)) {
            $storeId = null;
        }

        $dateExpression = 'COALESCE(o.ordered_at, o.order_date)';
        $shippingFailedStatusesSql = "'" . implode("', '", self::SHIPPING_FAILED_STATUSES) . "'";
        $statusExpression = "CASE WHEN COALESCE(o.delivery_failed, 0) = 1 OR UPPER(COALESCE(NULLIF(o.tracking_status, ''), '')) IN ({$shippingFailedStatusesSql}) THEN 'FAILED_DELIVERY' ELSE UPPER(COALESCE(NULLIF(o.order_status, ''), NULLIF(o.status, ''), 'BELUM DITENTUKAN')) END";
        $shippingExcludedPlaceholders = implode(',', array_fill(0, count(self::SHIPPING_EXCLUDED_STATUSES), '?'));

        $rows = DB::table('marketplace_orders as o')
            ->leftJoin('stores as st', 'st.id', '=', 'o.store_id')
            ->leftJoin('channels as ch', 'ch.id', '=', 'st.channel_id')
            ->whereRaw("{$dateExpression} IS NOT NULL")
            ->whereDate(DB::raw($dateExpression), $selectedDate->toDateString())
            ->whereRaw("{$statusExpression} NOT IN ({$shippingExcludedPlaceholders})", self::SHIPPING_EXCLUDED_STATUSES)
            ->when($isDashboardDummy, fn ($query) => $query->whereJsonContains('o.meta->dummy_source', self::PROMOTION_DUMMY_SOURCE))
            ->when($storeId, fn ($query) => $query->where('o.store_id', $storeId))
            ->when($platformCode, fn ($query) => $query->whereIn(DB::raw('UPPER(ch.code)'), $platformCodes))
            ->select([
                'o.id',
                'o.channel_order_id',
                'o.external_order_id',
                'o.buyer_username',
                'o.buyer_name',
                'o.shipping_carrier',
                'o.shipping_awb_no',
                'o.shipping_arranged_at',
                'o.shipped_at',
                'o.delivered_at',
                'st.name as store_name',
                'ch.code as channel_code',
            ])
            ->selectRaw("{$dateExpression} as order_at")
            ->selectRaw("{$statusExpression} as shipping_status")
            ->orderByDesc('order_at')
            ->orderByDesc('o.id')
            ->limit(500)
            ->get()
            ->map(function ($row) {
                $row->order_number = $this->orderNumberForDisplay(
                    $row->channel_order_id,
                    $row->external_order_id,
                    (int) $row->id,
                );
                $row->customer = $row->buyer_username ?: ($row->buyer_name ?: 'Pelanggan marketplace');
                $row->store = $row->store_name ?: 'Toko marketplace';
                $row->channel = $row->channel_code ? ucfirst((string) $row->channel_code) : 'Marketplace';
                $row->shipping_status = (string) $row->shipping_status;
                $row->status_label = [
                    'PENDING' => 'Menunggu',
                    'INVOICE_PENDING' => 'Menunggu Invoice',
                    'READY_TO_SHIP' => 'Siap Dikirim',
                    'MATCHED' => 'Siap Diproses',
                    'PROCESSED' => 'Diproses',
                    'READY_TO_HANDOVER' => 'Siap Diserahkan',
                    'SHIPPED' => 'Dikirim',
                    'TO_CONFIRM_RECEIVE' => 'Menunggu Konfirmasi',
                    'COMPLETED' => 'Selesai',
                    'SELESAI' => 'Selesai',
                    'FAILED_DELIVERY' => 'Gagal Kirim',
                    'TO_RETURN' => 'Akan Return',
                    'RETURNING' => 'Sedang Return',
                    'RETURNED' => 'Sudah Return',
                    'REFUND' => 'Refund',
                    'REFUNDED' => 'Refund Selesai',
                ][$row->shipping_status] ?? ucwords(strtolower(str_replace('_', ' ', $row->shipping_status)));
                $row->status_group = match ($row->shipping_status) {
                    'PENDING', 'INVOICE_PENDING', 'READY_TO_SHIP', 'MATCHED' => 'ready',
                    'PROCESSED', 'READY_TO_HANDOVER', 'SHIPPED', 'TO_CONFIRM_RECEIVE' => 'transit',
                    'COMPLETED', 'SELESAI' => 'completed',
                    'FAILED_DELIVERY' => 'failed',
                    'TO_RETURN', 'RETURNING', 'RETURNED', 'REFUND', 'REFUNDED' => 'return',
                    default => 'other',
                };

                return $row;
            });

        $summary = [
            'orders' => $rows->count(),
            'ready_orders' => $rows->where('status_group', 'ready')->count(),
            'transit_orders' => $rows->where('status_group', 'transit')->count(),
            'completed_orders' => $rows->where('status_group', 'completed')->count(),
            'failed_orders' => $rows->where('status_group', 'failed')->count(),
            'return_orders' => $rows->where('status_group', 'return')->count(),
        ];
        $summary['other_orders'] = max(
            0,
            $summary['orders'] - $summary['ready_orders'] - $summary['transit_orders'] - $summary['completed_orders'] - $summary['failed_orders'] - $summary['return_orders'],
        );
        $summary['exception_orders'] = $summary['failed_orders'] + $summary['return_orders'];
        $summary['exception_rate'] = $summary['orders'] > 0
            ? ($summary['exception_orders'] / $summary['orders']) * 100
            : 0;

        return view('marketplace.dashboard.shipping-detail', [
            'rows' => $rows,
            'summary' => $summary,
            'selectedDate' => $selectedDate,
            'stores' => $stores,
            'filters' => [
                'store_id' => $storeId,
                'platform' => $platformCode,
                'dummy' => $isDashboardDummy,
            ],
        ]);
    }

    public function promotionDetail(Request $request, string $date)
    {
        try {
            $selectedDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            if ($selectedDate->format('Y-m-d') !== $date) {
                abort(404);
            }
        } catch (\Throwable) {
            abort(404);
        }

        $isPromotionDummy = $request->boolean('dummy') && app()->environment(['local', 'testing']);
        $storeId = $request->integer('store_id') ?: null;
        $platformCode = $this->normalizePlatformCode($request->query('platform'));
        $platformCodes = $this->platformCodes($platformCode);
        $stores = Store::query()
            ->where('is_active', true)
            ->with('channel')
            ->orderBy('name')
            ->get();

        if ($storeId && ! $stores->contains('id', $storeId)) {
            $storeId = null;
        }

        $dateExpression = 'COALESCE(o.ordered_at, o.order_date)';
        $statusExpression = "UPPER(COALESCE(NULLIF(o.order_status, ''), NULLIF(o.status, ''), ''))";
        $subtotalExpression = 'CASE WHEN COALESCE(o.subtotal_items, 0) > 0 THEN o.subtotal_items ELSE COALESCE(o.total_amount, 0) END';
        $nonRevenuePlaceholders = implode(',', array_fill(0, count(self::NON_REVENUE_STATUSES), '?'));

        $linePromotionExpression = <<<'SQL'
CASE
    WHEN COALESCE(oi.line_discount, 0) > 0 THEN oi.line_discount
    WHEN COALESCE(oi.price_original, 0) > COALESCE(oi.price_after_discount, 0)
        THEN (COALESCE(oi.price_original, 0) - COALESCE(oi.price_after_discount, 0)) * COALESCE(oi.qty, 0)
    ELSE 0
END
SQL;
        $promotionItemTotals = DB::table('marketplace_order_items as oi')
            ->selectRaw('COALESCE(oi.marketplace_order_id, oi.order_id) as order_key')
            ->selectRaw("COALESCE(SUM({$linePromotionExpression}), 0) as product_discount")
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('marketplace_order_settlements as promo_settlement')
                    ->whereRaw('promo_settlement.order_id = COALESCE(oi.marketplace_order_id, oi.order_id)');
            })
            ->groupByRaw('COALESCE(oi.marketplace_order_id, oi.order_id)');

        $rows = DB::table('marketplace_orders as o')
            ->leftJoin('marketplace_order_settlements as ms', 'ms.order_id', '=', 'o.id')
            ->leftJoinSub($promotionItemTotals, 'ipromo', 'ipromo.order_key', '=', 'o.id')
            ->leftJoin('stores as st', 'st.id', '=', 'o.store_id')
            ->leftJoin('channels as ch', 'ch.id', '=', 'st.channel_id')
            ->whereRaw("{$dateExpression} IS NOT NULL")
            ->whereDate(DB::raw($dateExpression), $selectedDate->toDateString())
            ->whereRaw("{$statusExpression} NOT IN ({$nonRevenuePlaceholders})", self::NON_REVENUE_STATUSES)
            ->when($isPromotionDummy, fn ($query) => $query->whereJsonContains('o.meta->dummy_source', self::PROMOTION_DUMMY_SOURCE))
            ->when($storeId, fn ($query) => $query->where('o.store_id', $storeId))
            ->when($platformCode, fn ($query) => $query->whereIn(DB::raw('UPPER(ch.code)'), $platformCodes))
            ->select([
                'o.id',
                'o.channel_order_id',
                'o.external_order_id',
                'o.buyer_username',
                'o.buyer_name',
                'o.payment_method',
                'o.payment_status',
                'o.raw_json as order_raw_json',
                'o.raw_payload_json',
                'o.voucher_discount',
                'ms.id as settlement_id',
                'ms.raw_json as settlement_raw_json',
                'ms.seller_voucher',
                'ipromo.product_discount as legacy_product_discount',
                'st.name as store_name',
            ])
            ->selectRaw("{$dateExpression} as order_at")
            ->selectRaw("{$statusExpression} as status")
            ->selectRaw("{$subtotalExpression} as subtotal")
            ->orderByDesc('order_at')
            ->orderByDesc('o.id')
            ->limit(500)
            ->get()
            ->map(function ($row) use ($selectedDate) {
                $isSettlementBacked = $row->settlement_id !== null;
                $amounts = $isSettlementBacked
                    ? $this->promotionAmountsFromSettlement(
                        $row->settlement_raw_json,
                        $row->order_raw_json ?? $row->raw_payload_json,
                        (float) ($row->seller_voucher ?? 0),
                    )
                    : [
                        'product_discount' => (float) ($row->legacy_product_discount ?? 0),
                        'voucher_store' => (float) ($row->voucher_discount ?? 0),
                        'voucher_platform' => 0.0,
                        'bundle_discount' => 0.0,
                        'combo_hemat' => 0.0,
                    ];

                $amounts['total_promotion'] = array_sum($amounts);

                return (object) [
                    'order_id' => (int) $row->id,
                    'order_number' => $this->orderNumberForDisplay(
                        $row->channel_order_id,
                        $row->external_order_id,
                        (int) $row->id,
                    ),
                    'day' => $selectedDate->toDateString(),
                    'order_at' => $row->order_at,
                    'customer' => $row->buyer_username ?: ($row->buyer_name ?: 'Pelanggan marketplace'),
                    'store' => $row->store_name ?: 'Toko marketplace',
                    'payment' => $row->payment_method ?: ($row->payment_status ?: 'Belum ditentukan'),
                    'subtotal' => (float) $row->subtotal,
                    'product_discount' => $amounts['product_discount'],
                    'voucher_store' => $amounts['voucher_store'],
                    'voucher_platform' => $amounts['voucher_platform'],
                    'bundle_discount' => $amounts['bundle_discount'],
                    'combo_hemat' => $amounts['combo_hemat'],
                    'total_promotion' => $amounts['total_promotion'],
                ];
            })
            ->filter(fn ($row) => $row->total_promotion > 0)
            ->values();

        $summary = [
            'subtotal' => (float) $rows->sum('subtotal'),
            'product_discount' => (float) $rows->sum('product_discount'),
            'voucher_store' => (float) $rows->sum('voucher_store'),
            'voucher_platform' => (float) $rows->sum('voucher_platform'),
            'bundle_discount' => (float) $rows->sum('bundle_discount'),
            'combo_hemat' => (float) $rows->sum('combo_hemat'),
            'promotion_total' => (float) $rows->sum('total_promotion'),
        ];
        $summary['net_total'] = max($summary['subtotal'] - $summary['promotion_total'], 0);

        return view('marketplace.dashboard.promotion-detail', [
            'rows' => $rows,
            'summary' => $summary,
            'selectedDate' => $selectedDate,
            'stores' => $stores,
            'filters' => [
                'store_id' => $storeId,
                'platform' => $platformCode,
                'dummy' => $isPromotionDummy,
            ],
        ]);
    }

    private function dateOrDefault(?string $value, Carbon $default): Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('Y-m-d', $value)->startOfDay() : $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    private function normalizePlatformCode(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }

        foreach (self::PLATFORM_ALIASES as $canonical => $aliases) {
            if ($code === $canonical || in_array($code, $aliases, true)) {
                return $canonical;
            }
        }

        return $code;
    }

    /**
     * @return array<int, string>
     */
    private function platformCodes(?string $platformCode): array
    {
        if (! $platformCode) {
            return [];
        }

        return array_values(array_unique(array_merge(
            [$platformCode],
            self::PLATFORM_ALIASES[$platformCode] ?? [],
        )));
    }

    private function orderNumberForDisplay(?string $channelOrderId, ?string $externalOrderId, int $internalId): string
    {
        foreach ([$channelOrderId, $externalOrderId] as $candidate) {
            $candidate = trim((string) $candidate);

            // Nomor order marketplace tidak berupa kalimat/catatan bebas.
            // Record import lama yang salah mapping bisa berisi pesan pembeli;
            // jangan tampilkan pesan tersebut sebagai No. Pesanan.
            if ($candidate !== '' && ! preg_match('/\s/u', $candidate)) {
                return $candidate;
            }
        }

        return '#'.$internalId;
    }

    private function quantityFromPayload(mixed $payload): int
    {
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        if (! is_array($payload)) {
            return 0;
        }

        return (int) collect($payload['item_list'] ?? [])->sum(function ($item): int {
            if (! is_array($item)) {
                return 0;
            }

            return max(0, (int) ($item['model_quantity_purchased']
                ?? $item['quantity_purchased']
                ?? $item['active_qty']
                ?? 0));
        });
    }

    private function promotionAmountsFromSettlement(mixed $settlementPayload, mixed $orderPayload, float $sellerVoucher = 0): array
    {
        $settlementRaw = $this->decodePayload($settlementPayload);
        $orderRaw = $this->decodePayload($orderPayload);
        $voucherStore = array_key_exists('voucher_from_seller', $settlementRaw)
            ? (float) $settlementRaw['voucher_from_seller']
            : $sellerVoucher;
        $voucherPlatform = array_key_exists('voucher_from_shopee', $settlementRaw)
            ? (float) $settlementRaw['voucher_from_shopee']
            : 0.0;
        $productDiscount = 0.0;
        $bundleDiscount = 0.0;
        $comboHemat = 0.0;
        $promotionItems = (array) ($settlementRaw['items'] ?? ($orderRaw['item_list'] ?? []));

        foreach ($promotionItems as $item) {
            if (! is_array($item)) {
                continue;
            }

            $split = $this->promotionDiscountSplit($item);
            $productDiscount += $split['product_discount'];
            $bundleDiscount += $split['bundle_discount'];
            $comboHemat += $split['combo_hemat'];
        }

        if ($productDiscount <= 0 && $bundleDiscount <= 0 && $comboHemat <= 0 && ! empty($settlementRaw['seller_discount'])) {
            $productDiscount = (float) $settlementRaw['seller_discount'];
        }

        return [
            'product_discount' => $productDiscount,
            'voucher_store' => $voucherStore,
            'voucher_platform' => $voucherPlatform,
            'bundle_discount' => $bundleDiscount,
            'combo_hemat' => $comboHemat,
        ];
    }

    private function isBundlePromotionItem(array $item): bool
    {
        if (in_array(strtolower((string) ($item['activity_type'] ?? '')), ['bundle_deal', 'bundle_deal_discount'], true)) {
            return true;
        }

        if (strtolower((string) ($item['promotion_type'] ?? '')) === 'bundle_deal') {
            return true;
        }

        $hasExplicitBundleAmount = collect([
            $item['bundle_discount'] ?? null,
            $item['bundle_deal_discount'] ?? null,
            $item['bundle_discount_amount'] ?? null,
        ])->contains(fn ($amount) => is_numeric($amount) && (float) $amount > 0);

        return $hasExplicitBundleAmount && collect((array) ($item['promotion_list'] ?? []))
            ->contains(fn ($promotion) => strtolower((string) ($promotion['promotion_type'] ?? '')) === 'bundle_deal');
    }

    private function isComboHematPromotionItem(array $item): bool
    {
        $promotionTypes = ['add_on_deal', 'add_on_deal_main', 'add_on_deal_sub'];
        if (in_array(strtolower((string) ($item['activity_type'] ?? '')), $promotionTypes, true)) {
            return true;
        }

        if (in_array(strtolower((string) ($item['promotion_type'] ?? '')), $promotionTypes, true)) {
            return true;
        }

        return collect((array) ($item['promotion_list'] ?? []))
            ->contains(fn ($promotion) => in_array(strtolower((string) ($promotion['promotion_type'] ?? '')), $promotionTypes, true));
    }

    private function normalizedPromotionItemDiscount(array $item): float
    {
        $reportedDiscount = max((float) ($item['seller_discount'] ?? 0), 0);
        $originalPrice = max((float) ($item['original_price'] ?? $item['model_original_price'] ?? 0), 0);
        $discountedPrice = max((float) ($item['discounted_price'] ?? $item['model_discounted_price'] ?? 0), 0);
        $quantity = max((int) ($item['quantity_purchased'] ?? $item['model_quantity_purchased'] ?? $item['quantity'] ?? 1), 1);
        $priceDifference = max($originalPrice - $discountedPrice, 0);

        if ($reportedDiscount <= 0 && $priceDifference > 0) {
            $reportedDiscount = $priceDifference * $quantity;
        }

        // Settlement payloads can repeat an item-level seller discount. When
        // both prices are available, never report more than the price gap for
        // the purchased quantity.
        if ($reportedDiscount > 0 && $priceDifference > 0) {
            $reportedDiscount = min($reportedDiscount, $priceDifference * $quantity);
        }

        return $reportedDiscount;
    }

    /**
     * For a bundle item, Shopee defines selling_price as the price after the
     * item/product promotion but before the bundle promotion. Therefore the
     * first gap is product discount and the second gap is bundle discount.
     */
    private function promotionDiscountSplit(array $item): array
    {
        $totalDiscount = $this->normalizedPromotionItemDiscount($item);
        if ($totalDiscount <= 0) {
            return ['product_discount' => 0.0, 'bundle_discount' => 0.0, 'combo_hemat' => 0.0];
        }

        $isBundle = $this->isBundlePromotionItem($item);
        $isComboHemat = ! $isBundle && $this->isComboHematPromotionItem($item);
        if (! $isBundle && ! $isComboHemat) {
            return ['product_discount' => $totalDiscount, 'bundle_discount' => 0.0, 'combo_hemat' => 0.0];
        }

        $originalPrice = max((float) ($item['original_price'] ?? $item['model_original_price'] ?? 0), 0);
        $sellingPrice = max((float) ($item['selling_price'] ?? 0), 0);
        $discountedPrice = max((float) ($item['discounted_price'] ?? $item['model_discounted_price'] ?? 0), 0);

        $productGap = max($originalPrice - $sellingPrice, 0);
        $bundleGap = max($sellingPrice - $discountedPrice, 0);

        if ($productGap > 0 && $bundleGap > 0) {
            $productDiscount = min($totalDiscount, $productGap);
            $bundleDiscount = min(max($totalDiscount - $productDiscount, 0), $bundleGap);
            $remaining = max($totalDiscount - $productDiscount - $bundleDiscount, 0);

            // Preserve the complete reported seller discount if the source
            // rounds one of the price fields differently.
            $productDiscount += $remaining;

            return [
                'product_discount' => $productDiscount,
                'bundle_discount' => $isBundle ? $bundleDiscount : 0.0,
                'combo_hemat' => $isComboHemat ? $bundleDiscount : 0.0,
            ];
        }

        return [
            'product_discount' => 0.0,
            'bundle_discount' => $isBundle ? $totalDiscount : 0.0,
            'combo_hemat' => $isComboHemat ? $totalDiscount : 0.0,
        ];
    }

    private function decodePayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        return is_array($payload) ? $payload : [];
    }

    private function paymentSnapshotForDashboard(object $row): object
    {
        $orderRaw = $this->decodePayload($row->raw_json ?? $row->raw_payload_json);
        $liveData = $orderRaw['order_list'][0]
            ?? $orderRaw['response']['order_list'][0]
            ?? (isset($orderRaw['order_sn']) ? $orderRaw : []);
        $settlementRaw = $this->decodePayload($row->settlement_raw_json);
        $income = array_replace((array) ($liveData['income_details'] ?? []), $settlementRaw);
        $promotion = $this->paymentPromotionAmounts(
            $settlementRaw,
            $liveData,
            $income,
            (float) ($row->seller_voucher ?? 0),
            (float) ($row->voucher_discount ?? 0),
        );
        $subtotal = $this->paymentSubtotal(
            $income,
            $row->subtotal_items,
            0,
            $settlementRaw,
            $liveData,
        );
        $buyerPaid = $this->paymentBuyerPaid(
            $income,
            $row->total_paid_customer,
            $row->total_amount,
            $row->buyer_payment_amount,
            $liveData,
        );
        $shipping = $this->paymentBuyerShipping($income, $row->shipping_fee_customer, $liveData);
        $voucherStore = (float) ($promotion['voucher_store'] ?? 0);
        $voucherPlatform = (float) ($promotion['voucher_platform'] ?? 0);
        $buyerCoins = $this->firstPayloadAmount(
            [$income, $orderRaw, $orderRaw['promotion_breakdown'] ?? []],
            ['coin', 'coins', 'coin_discount', 'cashback_coin'],
        );
        $explicitServiceFee = $this->firstPayloadAmount(
            [$income, $orderRaw, $orderRaw['promotion_breakdown'] ?? []],
            ['buyer_transaction_fee', 'buyer_service_fee'],
        );
        $buyerServiceBalance = $buyerPaid - $subtotal - $shipping + $voucherPlatform + $voucherStore + $buyerCoins;
        $buyerServiceFee = $buyerPaid > 0
            ? ($buyerServiceBalance >= 0 ? $buyerServiceBalance : $explicitServiceFee)
            : ($explicitServiceFee > 0 ? $explicitServiceFee : 2000.0);
        $productProtection = $this->firstPayloadAmount(
            [$settlementRaw, $income, $orderRaw],
            [
                'final_product_protection',
                'buyer_paid_extended_warranty',
                'product_protection_fee',
                'product_protection',
                'insurance_premium',
                'premi',
            ],
        );

        return (object) [
            'day' => (string) $row->day,
            'category' => $this->paymentCategoryForDashboard($row->payment_method, $row->payment_status),
            'is_paid' => in_array(strtoupper((string) $row->payment_status), ['PAID', 'COMPLETED', 'SELESAI', 'LUNAS'], true),
            'buyer_paid' => max($buyerPaid, 0),
            'buyer_shipping' => max($shipping, 0),
            'buyer_service_fee' => max($buyerServiceFee, 0),
            'product_protection' => max($productProtection, 0),
        ];
    }

    private function paymentCategoryForDashboard(mixed $paymentMethod, mixed $paymentStatus): string
    {
        $source = strtolower((string) ($paymentMethod ?: $paymentStatus ?: ''));
        if (str_contains($source, 'paylater')
            || str_contains($source, 'pay later')
            || str_contains($source, 'cicilan')
            || str_contains($source, 'installment')) {
            return 'pay_later';
        }
        if (str_contains($source, 'cod')
            || str_contains($source, 'cash on delivery')
            || str_contains($source, 'bayar di tempat')) {
            return 'cod';
        }

        return 'non_cod';
    }

    private function paymentBuyerPaid(
        array $income,
        mixed $totalPaidCustomer,
        mixed $totalAmount,
        mixed $settlementBuyerPayment,
        array $liveData,
    ): float {
        $incomeBuyerPaid = $income['buyer_total_amount'] ?? $income['buyer_paid_amount'] ?? null;
        if ($incomeBuyerPaid !== null && $incomeBuyerPaid !== '' && is_numeric($incomeBuyerPaid)) {
            return (float) $incomeBuyerPaid;
        }

        if ((float) $totalPaidCustomer > 0) {
            return (float) $totalPaidCustomer;
        }

        if ((float) $settlementBuyerPayment > 0) {
            return (float) $settlementBuyerPayment;
        }

        return (float) ($liveData['total_amount'] ?? $totalAmount ?? 0);
    }

    private function paymentBuyerShipping(array $income, mixed $storedShipping, array $liveData): float
    {
        foreach ([$income, $liveData] as $source) {
            if (array_key_exists('buyer_paid_shipping_fee', $source)
                && $source['buyer_paid_shipping_fee'] !== ''
                && is_numeric($source['buyer_paid_shipping_fee'])) {
                return max((float) $source['buyer_paid_shipping_fee'], 0);
            }
        }

        // actual_shipping_fee dari order API adalah fallback operasional saat
        // escrow belum tersedia. Nilai 0 tetap valid dan tidak boleh diganti
        // dengan estimated_shipping_fee.
        if (array_key_exists('actual_shipping_fee', $liveData)
            && $liveData['actual_shipping_fee'] !== ''
            && is_numeric($liveData['actual_shipping_fee'])) {
            return max((float) $liveData['actual_shipping_fee'], 0);
        }

        $stored = is_numeric($storedShipping) ? (float) $storedShipping : 0.0;
        if ($stored > 0) {
            return $stored;
        }

        $shippingRebate = (float) ($income['shopee_shipping_rebate'] ?? 0);
        $estimatedShipping = (float) ($liveData['estimated_shipping_fee']
            ?? $income['estimated_shipping_fee']
            ?? $storedShipping
            ?? 0);

        if ($estimatedShipping > 0) {
            return max($estimatedShipping - $shippingRebate, 0);
        }

        return max($stored, 0);
    }

    private function paymentSubtotal(
        array $income,
        mixed $storedSubtotal,
        mixed $fallbackSubtotal,
        array $settlementRaw,
        array $liveData,
    ): float {
        $subtotal = (float) ($income['order_discounted_price'] ?? $storedSubtotal ?? 0);
        if ($subtotal > 0) {
            return $subtotal;
        }

        $items = (array) ($settlementRaw['items'] ?? []);
        if ($items === []) {
            $items = (array) ($liveData['item_list'] ?? []);
        }

        $itemSubtotal = $this->paymentItemsSubtotal($items);

        return $itemSubtotal > 0 ? $itemSubtotal : (float) $fallbackSubtotal;
    }

    private function paymentItemsSubtotal(array $items): float
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $qty = (float) ($item['quantity_purchased'] ?? $item['model_quantity_purchased'] ?? $item['quantity'] ?? 1);
            $qty = $qty > 0 ? $qty : 1;
            $lineTotal = (float) ($item['discounted_price'] ?? 0);
            $isSettlementItem = array_key_exists('discounted_price', $item) || array_key_exists('selling_price', $item);

            if ($isSettlementItem && $lineTotal <= 0) {
                $lineTotal = (float) ($item['selling_price'] ?? 0);
            }
            if ($isSettlementItem && $lineTotal > 0) {
                $subtotal += $lineTotal;
                continue;
            }

            $price = (float) ($item['model_discounted_price'] ?? 0);
            if ($price <= 0) {
                $price = (float) ($item['model_original_price'] ?? $item['selling_price'] ?? 0);
            }
            $subtotal += $price * $qty;
        }

        return $subtotal;
    }

    private function paymentPromotionAmounts(
        array $settlementRaw,
        array $liveData,
        array $income,
        float $sellerVoucher,
        float $legacyVoucher,
    ): array {
        $voucherStore = array_key_exists('voucher_from_seller', $settlementRaw)
            ? (float) $settlementRaw['voucher_from_seller']
            : ($sellerVoucher ?: (float) ($income['voucher_from_seller'] ?? $income['seller_voucher_rebate'] ?? $legacyVoucher));
        $voucherPlatform = array_key_exists('voucher_from_shopee', $settlementRaw)
            ? (float) $settlementRaw['voucher_from_shopee']
            : (float) ($income['voucher_from_shopee'] ?? $income['voucher_from_platform'] ?? $income['platform_voucher'] ?? 0);
        $productDiscount = 0.0;
        $bundleDiscount = 0.0;
        $comboHemat = 0.0;
        $items = (array) ($settlementRaw['items'] ?? []);
        if ($items === []) {
            $items = (array) ($liveData['item_list'] ?? []);
        }

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $split = $this->promotionDiscountSplit($item);
            $productDiscount += $split['product_discount'];
            $bundleDiscount += $split['bundle_discount'];
            $comboHemat += $split['combo_hemat'];
        }

        if ($productDiscount <= 0 && $bundleDiscount <= 0 && $comboHemat <= 0 && ! empty($settlementRaw['seller_discount'])) {
            $productDiscount = (float) $settlementRaw['seller_discount'];
        }

        return [
            'product_discount' => $productDiscount,
            'voucher_store' => $voucherStore,
            'voucher_platform' => $voucherPlatform,
            'bundle_discount' => $bundleDiscount,
            'combo_hemat' => $comboHemat,
        ];
    }

    private function firstPayloadAmount(array $payloads, array $keys): float
    {
        foreach ($payloads as $payload) {
            $decoded = $this->decodePayload($payload);
            foreach ($keys as $key) {
                $value = data_get($decoded, $key);
                if ($value !== null && $value !== '' && is_numeric($value)) {
                    return abs((float) $value);
                }
            }
        }

        return 0.0;
    }
}
