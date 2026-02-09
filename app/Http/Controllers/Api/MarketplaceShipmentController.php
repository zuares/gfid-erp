<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MpShipment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MarketplaceShipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tz = 'Asia/Jakarta';

        // ---------- Filters ----------
        $filters = [
            'q' => trim((string) $request->get('q', '')),
            'channel' => (string) $request->get('channel', ''), // shopee/tiktok
            'store_id' => (string) $request->get('store_id', ''),
            'status' => (string) $request->get('status', ''),
            'from' => (string) $request->get('from', ''),
            'to' => (string) $request->get('to', ''),
        ];

        // ---------- Date Range (DEFAULT last 7 days) ----------
        $today = Carbon::now($tz)->startOfDay();

        $from = $filters['from']
        ? Carbon::parse($filters['from'], $tz)->startOfDay()
        : (clone $today)->subDays(6);

        $to = $filters['to']
        ? Carbon::parse($filters['to'], $tz)->endOfDay()
        : (clone $today)->endOfDay();

        // normalize for response/UI
        $filters['from'] = $from->format('Y-m-d');
        $filters['to'] = $to->format('Y-m-d');

        $days = max(1, $from->diffInDays($to) + 1);

        // ---------- Previous Period ----------
        $prevTo = (clone $from)->subDay()->endOfDay();
        $prevFrom = (clone $prevTo)->subDays($days - 1)->startOfDay();

        /**
         * BASE QUERY:
         * - tampil berdasarkan shipped_at
         * - fallback: kalau shipped_at null, pakai order_created_at (biar record gak "hilang")
         *
         * ✅ Kalau kamu mau STRICT (yang shipped_at null tidak tampil):
         *    ganti blok where(...) ini menjadi: ->whereBetween('shipped_at', [$f, $t])
         */
        $makeBase = function (Carbon $f, Carbon $t) use ($filters) {
            return MpShipment::query()
                ->where(function ($q) use ($f, $t) {
                    $q->whereBetween('shipped_at', [$f, $t])
                        ->orWhere(function ($w) use ($f, $t) {
                            $w->whereNull('shipped_at')
                                ->whereBetween('order_created_at', [$f, $t]);
                        });
                })
                ->when($filters['channel'] !== '', fn($q) => $q->where('channel', $filters['channel']))
                ->when($filters['store_id'] !== '', fn($q) => $q->where('store_id', (int) $filters['store_id']))
                ->when($filters['status'] !== '', fn($q) => $q->where('status_norm', $filters['status']))
                ->when($filters['q'] !== '', function ($q) use ($filters) {
                    $qq = $filters['q'];
                    $q->where(function ($w) use ($qq) {
                        $w->where('platform_order_id', 'like', "%{$qq}%")
                            ->orWhere('platform_shipment_id', 'like', "%{$qq}%")
                            ->orWhere('tracking_no', 'like', "%{$qq}%");
                    });
                });
        };

        $base = $makeBase($from, $to);

        // ---------- KPI Cache (versioned, can bust on import/commit) ----------
        $ver = (int) Cache::get('mp_shipments:kpi:ver', 1);

        $cacheKey = 'mp_shipments:kpi:v' . $ver . ':' . md5(json_encode([
            'filters' => $filters,
            'from' => $from->toISOString(),
            'to' => $to->toISOString(),
            'prev_from' => $prevFrom->toISOString(),
            'prev_to' => $prevTo->toISOString(),
            'basis' => 'shipped_at_fallback_order_created_at',
        ]));

        $kpiPayload = Cache::remember($cacheKey, 30, function () use ($base, $makeBase, $prevFrom, $prevTo) {

            $agg = function ($qb) {
                $row = (clone $qb)->selectRaw('
                    COUNT(*) as rows,
                    COALESCE(SUM(total_qty), 0) as sum_qty,
                    COALESCE(SUM(grand_total), 0) as sum_grand_total,

                    SUM(CASE WHEN status_norm = "delivered" THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status_norm = "in_transit" THEN 1 ELSE 0 END) as in_transit,
                    SUM(CASE WHEN status_norm = "canceled" THEN 1 ELSE 0 END) as canceled,
                    SUM(CASE WHEN tracking_no IS NULL THEN 1 ELSE 0 END) as untracked
                ')->first();

                return [
                    'rows' => (int) ($row->rows ?? 0),
                    'sum_qty' => (int) ($row->sum_qty ?? 0),
                    'sum_grand_total' => (float) ($row->sum_grand_total ?? 0),

                    'delivered' => (int) ($row->delivered ?? 0),
                    'in_transit' => (int) ($row->in_transit ?? 0),
                    'canceled' => (int) ($row->canceled ?? 0),
                    'untracked' => (int) ($row->untracked ?? 0),
                ];
            };

            // ===== CURRENT =====
            $curAgg = $agg($base);

            $summary = [
                'rows' => $curAgg['rows'],
                'sum_qty' => $curAgg['sum_qty'],
                'sum_grand_total' => $curAgg['sum_grand_total'],
            ];

            $orders = [
                'sales' => $curAgg['sum_grand_total'],
                'orders' => $curAgg['rows'],
                'items' => $curAgg['sum_qty'],
                'delivered' => $curAgg['delivered'],
                'in_transit' => $curAgg['in_transit'],
            ];

            $orders['delivery_rate'] =
            ($orders['delivered'] + $orders['in_transit']) > 0
            ? ($orders['delivered'] / ($orders['delivered'] + $orders['in_transit'])) * 100
            : 0;

            $ship = [
                'in_transit' => $curAgg['in_transit'],
                'delivered' => $curAgg['delivered'],
                'canceled' => $curAgg['canceled'],
                'untracked' => $curAgg['untracked'],
                'avg_delivery_days' => (clone $base)
                    ->whereNotNull('shipped_at')
                    ->whereNotNull('delivered_at')
                    ->avg(DB::raw('julianday(delivered_at) - julianday(shipped_at)')),
            ];

            // ===== PREVIOUS =====
            $prev = $makeBase($prevFrom, $prevTo);
            $prevAgg = $agg($prev);

            $prevOrders = [
                'sales' => $prevAgg['sum_grand_total'],
                'orders' => $prevAgg['rows'],
                'items' => $prevAgg['sum_qty'],
                'delivered' => $prevAgg['delivered'],
                'in_transit' => $prevAgg['in_transit'],
            ];

            $prevOrders['delivery_rate'] =
            ($prevOrders['delivered'] + $prevOrders['in_transit']) > 0
            ? ($prevOrders['delivered'] / ($prevOrders['delivered'] + $prevOrders['in_transit'])) * 100
            : 0;

            $prevShip = [
                'in_transit' => $prevAgg['in_transit'],
                'delivered' => $prevAgg['delivered'],
                'untracked' => $prevAgg['untracked'],
                'avg_delivery_days' => (clone $prev)
                    ->whereNotNull('shipped_at')
                    ->whereNotNull('delivered_at')
                    ->avg(DB::raw('julianday(delivered_at) - julianday(shipped_at)')),
            ];

            // ===== Delta helpers =====
            $pct = function (?float $cur, ?float $prev) {
                $cur = (float) ($cur ?? 0);
                $prev = (float) ($prev ?? 0);
                if ($prev == 0.0) {
                    return $cur == 0.0 ? 0.0 : 100.0;
                }

                return (($cur - $prev) / abs($prev)) * 100.0;
            };

            $delta = [
                'orders_sales' => $pct($orders['sales'], $prevOrders['sales']),
                'orders_orders' => $pct($orders['orders'], $prevOrders['orders']),
                'orders_items' => $pct($orders['items'], $prevOrders['items']),
                'orders_delivery_rate' => (float) ($orders['delivery_rate'] - $prevOrders['delivery_rate']),

                'ship_in_transit' => $pct($ship['in_transit'], $prevShip['in_transit']),
                'ship_delivered' => $pct($ship['delivered'], $prevShip['delivered']),
                'ship_untracked' => $pct($ship['untracked'], $prevShip['untracked']),
                'ship_avg_days' => (float) ((float) ($ship['avg_delivery_days'] ?? 0) - (float) ($prevShip['avg_delivery_days'] ?? 0)),
            ];

            return [
                'summary' => $summary,
                'orders' => $orders,
                'ship' => [
                     ...$ship,
                    'avg_delivery_days' => $ship['avg_delivery_days'] !== null ? (float) $ship['avg_delivery_days'] : null,
                ],
                'prev' => [
                    'orders' => $prevOrders,
                    'ship' => [
                         ...$prevShip,
                        'avg_delivery_days' => $prevShip['avg_delivery_days'] !== null ? (float) $prevShip['avg_delivery_days'] : null,
                    ],
                ],
                'delta' => $delta,
            ];
        });

        // ---------- Pagination (NOT cached) ----------
        $shipments = (clone $base)
            ->with(['store:id,name'])
            // ✅ tampil berdasarkan shipped_at (fallback order_created_at)
            ->orderByRaw('COALESCE(shipped_at, order_created_at) DESC')
            ->paginate(20)
            ->appends($request->query());

        $rows = collect($shipments->items())->map(fn($s) => [
            'id' => $s->id,
            'platform_order_id' => (string) $s->platform_order_id,
            'platform_shipment_id' => $s->platform_shipment_id,
            'channel' => $s->channel,
            'store' => $s->store?->name,
            'tracking_no' => $s->tracking_no,
            'status_norm' => $s->status_norm,

            // ✅ tampilkan shipped_at juga
            'shipped_at' => optional($s->shipped_at)->toISOString(),
            'delivered_at' => optional($s->delivered_at)->toISOString(),
            'order_created_at' => optional($s->order_created_at)->toISOString(),

            'total_qty' => (int) $s->total_qty,
            'grand_total' => (float) $s->grand_total,
        ]);

        return response()->json([
            'filters' => $filters,
            'period' => [
                'from' => $from->toISOString(),
                'to' => $to->toISOString(),
                'days' => $days,
                'basis' => 'shipped_at (fallback order_created_at when null)',
            ],
            'prev_period' => [
                'from' => $prevFrom->toISOString(),
                'to' => $prevTo->toISOString(),
                'days' => $days,
            ],

            // cached KPI block
            'summary' => $kpiPayload['summary'],
            'orders' => $kpiPayload['orders'],
            'ship' => $kpiPayload['ship'],
            'prev' => $kpiPayload['prev'],
            'delta' => $kpiPayload['delta'],

            // uncached table block
            'shipments' => [
                'data' => $rows,
                'meta' => [
                    'current_page' => $shipments->currentPage(),
                    'last_page' => $shipments->lastPage(),
                    'per_page' => $shipments->perPage(),
                    'total' => $shipments->total(),
                    'next_page_url' => $shipments->nextPageUrl(),
                    'prev_page_url' => $shipments->previousPageUrl(),
                ],
            ],
        ]);
    }
}
