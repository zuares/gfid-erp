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
            ->whereRaw("{$dateExpression} IS NOT NULL")
            ->whereDate(DB::raw($dateExpression), '>=', $from->toDateString())
            ->whereDate(DB::raw($dateExpression), '<=', $to->toDateString())
            ->whereRaw("{$statusExpression} NOT IN (" . implode(',', array_fill(0, count(self::NON_REVENUE_STATUSES), '?')) . ')', self::NON_REVENUE_STATUSES)
            ->when($storeId, fn ($query) => $query->where('o.store_id', $storeId));

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

        $promotionTotals = (clone $base)
            ->selectRaw('COUNT(DISTINCT CASE WHEN COALESCE(o.voucher_discount, 0) + COALESCE(o.other_discount, 0) + COALESCE(o.shipping_discount_platform, 0) > 0 THEN o.id END) as discounted_orders')
            ->selectRaw('COALESCE(SUM(o.voucher_discount), 0) as voucher_discount')
            ->selectRaw('COALESCE(SUM(o.other_discount), 0) as other_discount')
            ->selectRaw('COALESCE(SUM(o.shipping_discount_platform), 0) as shipping_discount')
            ->first();

        $promotions = collect([
            ['label' => 'Voucher marketplace', 'amount' => (float) ($promotionTotals->voucher_discount ?? 0)],
            ['label' => 'Diskon lainnya', 'amount' => (float) ($promotionTotals->other_discount ?? 0)],
            ['label' => 'Subsidi ongkir platform', 'amount' => (float) ($promotionTotals->shipping_discount ?? 0)],
        ])->filter(fn ($row) => $row['amount'] > 0)->values();

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
            'promotions' => $promotions,
            'promotionOrders' => (int) ($promotionTotals->discounted_orders ?? 0),
            'shipping' => $shipping,
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
