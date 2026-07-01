<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorefrontEvent;
use App\Models\StorefrontOrder;
use App\Models\StorefrontVisitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StorefrontCustomerController extends Controller
{
    // ─── Index ───────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search = trim($request->query('search', ''));
        $sort   = $request->query('sort', 'total_spent');
        $repeat = $request->boolean('repeat_only');

        $query = StorefrontOrder::whereNotNull('customer_phone')
            ->whereNotIn('status', ['cancelled'])
            ->select(
                'customer_phone',
                DB::raw('MAX(customer_name) as customer_name'),
                DB::raw('MAX(city) as city'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as total_spent'),
                DB::raw('MAX(created_at) as last_order_at'),
                DB::raw('MIN(created_at) as first_order_at')
            )
            ->groupBy('customer_phone');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_phone', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        if ($repeat) {
            $query->havingRaw('COUNT(*) > 1');
        }

        match ($sort) {
            'order_count' => $query->orderByDesc('order_count'),
            'last_order'  => $query->orderByDesc('last_order_at'),
            default       => $query->orderByDesc(DB::raw('SUM(total_amount)')),
        };

        $customers = $query->paginate(25)->withQueryString();

        // Summary stats (across all time, not just current page)
        $allStats = StorefrontOrder::whereNotNull('customer_phone')
            ->whereNotIn('status', ['cancelled'])
            ->select('customer_phone', DB::raw('COUNT(*) as cnt'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('customer_phone')
            ->get();

        $totalCustomers = $allStats->count();
        $repeatBuyers   = $allStats->where('cnt', '>', 1)->count();
        $avgClv         = $allStats->avg('total') ?? 0;
        $totalRevenue   = $allStats->sum('total');

        return view('admin.crm.customers.index', compact(
            'customers', 'search', 'sort', 'repeat',
            'totalCustomers', 'repeatBuyers', 'avgClv', 'totalRevenue'
        ));
    }

    // ─── Show (360 view) ─────────────────────────────────────────────────────

    public function show(string $phone)
    {
        $orders = StorefrontOrder::where('customer_phone', $phone)
            ->orderByDesc('created_at')
            ->get();

        abort_if($orders->isEmpty(), 404);

        $validOrders = $orders->whereNotIn('status', ['cancelled']);

        $customer = (object) [
            'name'        => $orders->first()->customer_name,
            'phone'       => $phone,
            'city'        => $orders->first()->city,
            'province'    => $orders->first()->province ?? null,
            'total_spent' => $validOrders->sum('total_amount'),
            'order_count' => $orders->count(),
            'valid_count' => $validOrders->count(),
            'avg_order'   => $validOrders->count() > 0
                ? $validOrders->sum('total_amount') / $validOrders->count()
                : 0,
            'first_order' => $orders->min('created_at'),
            'last_order'  => $orders->max('created_at'),
            'is_repeat'   => $orders->count() > 1,
            'is_vip'      => $validOrders->sum('total_amount') >= 1_000_000,
        ];

        // Aggregasi produk yang pernah dibeli (plain array, bukan Collection, untuk avoid indirect modification error)
        $productMap = [];
        $orders->each(function ($order) use (&$productMap) {
            foreach ($order->items ?? [] as $item) {
                $slug = $item['slug'] ?? ($item['name'] ?? 'unknown');
                if (! isset($productMap[$slug])) {
                    $productMap[$slug] = ['name' => $item['name'] ?? $slug, 'qty' => 0, 'orders' => 0];
                }
                $productMap[$slug]['qty']    += (int) ($item['qty'] ?? 1);
                $productMap[$slug]['orders'] += 1;
            }
        });
        $products = collect($productMap)->sortByDesc('qty')->values();

        // ── Visitor & behaviour tracking ──────────────────────────────────────
        // Ambil SEMUA visitor record yang terhubung ke nomor ini (bisa beda device/browser)
        $visitors = StorefrontVisitor::where('customer_phone', $phone)
            ->orWhereIn('visitor_token', $orders->pluck('visitor_token')->filter()->unique())
            ->get()
            ->unique('visitor_token');

        $visitor = $visitors->sortByDesc('first_seen_at')->first(); // visitor utama untuk header

        $allTokens = $visitors->pluck('visitor_token')->filter()->unique()->values()->all();

        // Semua events dari semua visitor tokens, urut waktu terbaru
        $events = collect();
        if ($allTokens) {
            $events = StorefrontEvent::whereIn('visitor_token', $allTokens)
                ->orderByDesc('created_at')
                ->get();
        }

        // ── Halaman yang pernah dikunjungi ────────────────────────────────────
        $pageVisits = $events
            ->whereIn('event_type', ['page_view', 'product_view'])
            ->groupBy(function ($e) {
                $route = $e->payload['route'] ?? 'unknown';
                $slug  = $e->payload['slug'] ?? null;
                return $slug ? "product:{$slug}" : $route;
            })
            ->map(fn ($g) => [
                'label'   => ($g->first()->payload['slug'] ?? null)
                    ? $g->first()->payload['slug']
                    : str_replace('storefront.', '', $g->first()->payload['route'] ?? '-'),
                'type'    => $g->first()->event_type,
                'count'   => $g->count(),
                'last_at' => $g->max('created_at'),
            ])
            ->sortByDesc('count')
            ->values();

        // ── Durasi per halaman (dari page_view_duration events) ───────────────
        $pageDurations = $events
            ->where('event_type', 'page_view_duration')
            ->groupBy(function ($e) {
                $route = $e->payload['route'] ?? 'unknown';
                $slug  = $e->payload['slug'] ?? null;
                return $slug ? "product:{$slug}" : $route;
            })
            ->map(fn ($g) => [
                'label'   => ($g->first()->payload['slug'] ?? null)
                    ? $g->first()->payload['slug']
                    : str_replace('storefront.', '', $g->first()->payload['route'] ?? '-'),
                'avg_sec' => (int) $g->avg(fn ($e) => $e->payload['seconds'] ?? 0),
                'total_sec' => (int) $g->sum(fn ($e) => $e->payload['seconds'] ?? 0),
                'count'   => $g->count(),
            ])
            ->sortByDesc('total_sec')
            ->values();

        $totalTimeOnSite = (int) $events
            ->where('event_type', 'page_view_duration')
            ->sum(fn ($e) => $e->payload['seconds'] ?? 0);

        // ── Klik yang pernah dilakukan ────────────────────────────────────────
        $clickSummary = $events
            ->where('event_type', 'click')
            ->groupBy(fn ($e) => $e->payload['label'] ?? 'unknown')
            ->map(fn ($g) => ['label' => $g->first()->payload['label'] ?? '-', 'count' => $g->count()])
            ->sortByDesc('count')
            ->values();

        // ── Timeline events (50 terakhir, exclude page_view_duration karena noise) ──
        $timeline = $events
            ->whereNotIn('event_type', ['page_view_duration'])
            ->take(50);

        return view('admin.crm.customers.show', compact(
            'customer', 'orders', 'products',
            'visitor', 'visitors', 'events', 'timeline',
            'pageVisits', 'pageDurations', 'totalTimeOnSite', 'clickSummary'
        ));
    }
}
