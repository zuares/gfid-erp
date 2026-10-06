<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketplaceSalesDashboardController extends Controller
{
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

    public function index(Request $request)
    {
        $today = now()->startOfDay();
        $defaultFrom = (clone $today)->subDays(30);

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
        $stores = Store::query()
            ->where('is_active', true)
            ->with('channel')
            ->orderBy('name')
            ->get();

        if ($storeId && ! $stores->contains('id', $storeId)) {
            $storeId = null;
        }

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
            ->whereRaw("{$dateExpression} IS NOT NULL")
            ->whereDate(DB::raw($dateExpression), '>=', $from->toDateString())
            ->whereDate(DB::raw($dateExpression), '<=', $to->toDateString())
            ->whereRaw("{$statusExpression} NOT IN (" . implode(',', array_fill(0, count(self::NON_REVENUE_STATUSES), '?')) . ')', self::NON_REVENUE_STATUSES)
            ->when($storeId, fn ($query) => $query->where('o.store_id', $storeId));

        $orderDetails = (clone $base)
            ->select([
                'o.id',
                'o.channel_order_id',
                'o.external_order_id',
                'o.buyer_username',
                'o.buyer_name',
                'o.payment_method',
                'o.payment_status',
                'o.shipping_fee_customer',
                'o.raw_json',
                'o.raw_payload_json',
                'st.name as store_name',
            ])
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

                unset($row->raw_json, $row->raw_payload_json, $row->item_qty);

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
                    'aov' => $orders > 0 ? $subtotal / $orders : 0,
                ];
            })
            ->sortByDesc('day')
            ->values();

        $products = DB::table('marketplace_order_items as oi')
            ->join('marketplace_orders as o', function ($join) {
                $join->on(DB::raw('COALESCE(oi.marketplace_order_id, oi.order_id)'), '=', 'o.id');
            })
            ->whereRaw("{$dateExpression} IS NOT NULL")
            ->whereDate(DB::raw($dateExpression), '>=', $from->toDateString())
            ->whereDate(DB::raw($dateExpression), '<=', $to->toDateString())
            ->whereRaw("{$statusExpression} NOT IN (" . implode(',', array_fill(0, count(self::NON_REVENUE_STATUSES), '?')) . ')', self::NON_REVENUE_STATUSES)
            ->when($storeId, fn ($query) => $query->where('o.store_id', $storeId))
            ->selectRaw("COALESCE(NULLIF(oi.item_name, ''), NULLIF(oi.item_name_snapshot, ''), NULLIF(oi.variant_name, ''), NULLIF(oi.variant_snapshot, ''), 'Produk tanpa nama') as name")
            ->selectRaw("COALESCE(NULLIF(oi.item_sku, ''), NULLIF(oi.marketplace_sku, ''), NULLIF(oi.model_sku, ''), NULLIF(oi.external_sku, ''), NULLIF(oi.item_code_snapshot, ''), '-') as sku")
            ->selectRaw('COALESCE(SUM(CASE WHEN oi.qty > 0 THEN oi.qty ELSE 0 END), 0) as qty')
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(oi.line_net_amount, 0) > 0 THEN oi.line_net_amount WHEN COALESCE(oi.price, 0) > 0 THEN oi.price * COALESCE(oi.qty, 0) WHEN COALESCE(oi.line_gross_amount, 0) > 0 THEN oi.line_gross_amount ELSE 0 END), 0) as sales')
            ->groupByRaw("COALESCE(NULLIF(oi.item_name, ''), NULLIF(oi.item_name_snapshot, ''), NULLIF(oi.variant_name, ''), NULLIF(oi.variant_snapshot, ''), 'Produk tanpa nama')")
            ->groupByRaw("COALESCE(NULLIF(oi.item_sku, ''), NULLIF(oi.marketplace_sku, ''), NULLIF(oi.model_sku, ''), NULLIF(oi.external_sku, ''), NULLIF(oi.item_code_snapshot, ''), '-')")
            ->orderByDesc('sales')
            ->limit(8)
            ->get();

        $paymentExpression = "COALESCE(NULLIF(o.payment_method, ''), NULLIF(o.payment_status, ''), 'Belum ditentukan')";
        $payments = (clone $base)
            ->selectRaw("{$paymentExpression} as method")
            ->selectRaw('COUNT(DISTINCT o.id) as orders')
            ->selectRaw("COALESCE(SUM({$subtotalExpression}), 0) as subtotal")
            ->groupByRaw($paymentExpression)
            ->orderByDesc('orders')
            ->get();

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
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$legacyPromotionAmountExpression} > 0 THEN o.id END) as promotion_orders")
            ->selectRaw('COALESCE(SUM(ipromo.product_discount), 0) as product_discount')
            ->selectRaw('COALESCE(SUM(o.voucher_discount), 0) as voucher_store')
            ->selectRaw('0 as voucher_platform')
            ->selectRaw('0 as bundle_discount')
            ->selectRaw('COALESCE(SUM(o.other_discount), 0) as other_discount')
            ->selectRaw('COALESCE(SUM(o.shipping_discount_platform), 0) as shipping_discount')
            ->selectRaw("COALESCE(SUM({$legacyPromotionAmountExpression}), 0) as total_promotion")
            ->groupByRaw("DATE({$dateExpression})")
            ->orderByDesc('day')
            ->get()
            ->keyBy('day');

        $settlementPromotionRows = DB::table('marketplace_order_settlements as ms')
            ->join('marketplace_orders as o', 'o.id', '=', 'ms.order_id')
            ->whereRaw("{$dateExpression} IS NOT NULL")
            ->whereDate(DB::raw($dateExpression), '>=', $from->toDateString())
            ->whereDate(DB::raw($dateExpression), '<=', $to->toDateString())
            ->whereRaw("{$statusExpression} NOT IN (" . implode(',', array_fill(0, count(self::NON_REVENUE_STATUSES), '?')) . ')', self::NON_REVENUE_STATUSES)
            ->when($storeId, fn ($query) => $query->where('o.store_id', $storeId))
            ->select([
                'ms.order_id',
                'ms.seller_voucher',
                'ms.shipping_fee_subsidy',
                'ms.raw_json',
                'o.raw_json as order_raw_json',
            ])
            ->selectRaw("DATE({$dateExpression}) as day")
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
            $settlementItems = (array) ($raw['items'] ?? []);
            $promotionItems = $settlementItems ?: (array) ($orderRaw['item_list'] ?? []);
            foreach ($promotionItems as $item) {
                $itemDiscount = (float) ($item['seller_discount'] ?? 0);
                $originalPrice = (float) ($item['original_price'] ?? $item['model_original_price'] ?? 0);
                $discountedPrice = (float) ($item['discounted_price'] ?? $item['model_discounted_price'] ?? 0);
                if ($itemDiscount <= 0 && $discountedPrice > 0) {
                    $itemDiscount = max($originalPrice - $discountedPrice, 0);
                }

                $promotionTypes = collect((array) ($item['promotion_list'] ?? []))
                    ->map(fn ($promotion) => strtolower((string) ($promotion['promotion_type'] ?? '')))
                    ->all();
                $isBundle = in_array('bundle_deal', $promotionTypes, true)
                    || strtolower((string) ($item['activity_type'] ?? '')) === 'bundle_deal'
                    || strtolower((string) ($item['promotion_type'] ?? '')) === 'bundle_deal';

                if ($isBundle) {
                    $bundleDiscount += $itemDiscount;
                } else {
                    $productDiscount += $itemDiscount;
                }
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
                    'voucher_store' => 0,
                    'voucher_platform' => 0,
                    'bundle_discount' => 0,
                    'other_discount' => 0,
                    'shipping_discount' => 0,
                    'total_promotion' => 0,
                ];
            }

            $row = $promotionDaily[$day];
            $promotionTotal = $productDiscount + $voucherStore + $voucherPlatform
                + $bundleDiscount + $shippingDiscount;
            if ($promotionTotal > 0) {
                $row->promotion_orders++;
            }
            $row->product_discount += $productDiscount;
            $row->voucher_store += $voucherStore;
            $row->voucher_platform += $voucherPlatform;
            $row->bundle_discount += $bundleDiscount;
            $row->shipping_discount += $shippingDiscount;
            $row->total_promotion += $promotionTotal;
        }

        $promotionDaily = $promotionDaily->sortByDesc('day')->values();
        $promotionOrders = (int) $promotionDaily->sum('promotion_orders');

        $shippingStatusExpression = "UPPER(COALESCE(NULLIF(o.order_status, ''), NULLIF(o.status, ''), 'BELUM DITENTUKAN'))";
        $shipping = (clone $base)
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

        return view('marketplace.dashboard.sales', [
            'summary' => $summary,
            'daily' => $daily,
            'products' => $products,
            'payments' => $payments,
            'promotionDaily' => $promotionDaily,
            'promotionOrders' => $promotionOrders,
            'shipping' => $shipping,
            'orderDetails' => $orderDetails,
            'stores' => $stores,
            'filters' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'store_id' => $storeId,
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
}
