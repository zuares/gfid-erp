<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorefrontEvent;
use App\Models\StorefrontOrder;
use App\Models\StorefrontVisitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StorefrontCrmController extends Controller
{
    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard(Request $request)
    {
        $days  = (int) $request->query('days', 30);
        $days  = in_array($days, [1, 7, 30, 90]) ? $days : 30;
        $since = now()->subDays($days)->startOfDay();

        // ── Funnel (unique visitors per tahap) ───────────────────────────────
        $funnel = [
            'visitors'   => StorefrontVisitor::where('first_seen_at', '>=', $since)->count(),
            'product_view' => StorefrontEvent::where('event_type', 'product_view')
                ->where('created_at', '>=', $since)
                ->distinct('visitor_token')->count('visitor_token'),
            'add_to_cart' => StorefrontEvent::where('event_type', 'add_to_cart')
                ->where('created_at', '>=', $since)
                ->distinct('visitor_token')->count('visitor_token'),
            'checkout'   => StorefrontEvent::where('event_type', 'checkout_start')
                ->where('created_at', '>=', $since)
                ->distinct('visitor_token')->count('visitor_token'),
            'orders'     => StorefrontOrder::where('created_at', '>=', $since)->count(),
            'wa_click'   => StorefrontEvent::where('event_type', 'wa_click')
                ->where('created_at', '>=', $since)
                ->distinct('visitor_token')->count('visitor_token'),
        ];

        // ── Revenue ───────────────────────────────────────────────────────────
        $revenue = [
            'total'   => StorefrontOrder::where('created_at', '>=', $since)
                ->whereNotIn('status', ['cancelled'])->sum('total_amount'),
            'avg'     => StorefrontOrder::where('created_at', '>=', $since)
                ->whereNotIn('status', ['cancelled'])->avg('total_amount') ?? 0,
            'pending' => StorefrontOrder::where('created_at', '>=', $since)
                ->where('status', 'pending')->count(),
        ];

        // ── Top produk (paling sering add_to_cart) ────────────────────────────
        $topProducts = StorefrontEvent::where('event_type', 'add_to_cart')
            ->where('created_at', '>=', $since)
            ->get()
            ->groupBy(fn($e) => $e->payload['name'] ?? 'Unknown')
            ->map(fn($g) => ['name' => $g->first()->payload['name'] ?? '-', 'count' => $g->count()])
            ->sortByDesc('count')
            ->take(5)
            ->values();

        // ── Sebaran kota (dari orders, lebih akurat karena selalu terisi) ───────
        $topCities = StorefrontOrder::where('created_at', '>=', $since)
            ->whereNotNull('city')
            ->whereNotIn('status', ['cancelled'])
            ->select('city', DB::raw('count(*) as total'))
            ->groupBy('city')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        // ── Order harian (grafik) ─────────────────────────────────────────────
        $dailyOrders = StorefrontOrder::where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as orders'), DB::raw('sum(total_amount) as revenue'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ── Returning vs New visitor ──────────────────────────────────────────
        $activeTokens = StorefrontEvent::where('created_at', '>=', $since)
            ->distinct('visitor_token')
            ->pluck('visitor_token');

        $returningCount = StorefrontVisitor::whereIn('visitor_token', $activeTokens)
            ->where('first_seen_at', '<', $since)
            ->count();

        // ── Mobile vs Desktop ─────────────────────────────────────────────────
        $mobileCount = StorefrontVisitor::where('first_seen_at', '>=', $since)
            ->where(function ($q) {
                $q->where('user_agent', 'like', '%Mobile%')
                  ->orWhere('user_agent', 'like', '%Android%')
                  ->orWhere('user_agent', 'like', '%iPhone%')
                  ->orWhere('user_agent', 'like', '%iPad%');
            })->count();
        $desktopCount = $funnel['visitors'] - $mobileCount;

        // ── Avg waktu add-to-cart → order ────────────────────────────────────
        $orderedVisitors = StorefrontOrder::where('created_at', '>=', $since)
            ->whereNotNull('visitor_token')
            ->select('visitor_token', 'created_at')
            ->get()
            ->keyBy('visitor_token');

        $firstCartTimes = StorefrontEvent::where('event_type', 'add_to_cart')
            ->whereIn('visitor_token', $orderedVisitors->keys())
            ->select('visitor_token', DB::raw('min(created_at) as first_cart_at'))
            ->groupBy('visitor_token')
            ->pluck('first_cart_at', 'visitor_token');

        $timeDiffs = collect();
        foreach ($orderedVisitors->keys() as $token) {
            $cartAt  = $firstCartTimes->get($token);
            $orderAt = $orderedVisitors->get($token)?->created_at;
            if ($cartAt && $orderAt) {
                $diff = \Carbon\Carbon::parse($cartAt)->diffInMinutes($orderAt);
                if ($diff >= 0 && $diff < 10080) { // max 7 hari
                    $timeDiffs->push($diff);
                }
            }
        }
        $avgCartToOrderMinutes   = $timeDiffs->count() > 0 ? (int) round($timeDiffs->avg()) : null;
        $avgCartToOrderSampleSize = $timeDiffs->count();

        // ── Konversi per produk ───────────────────────────────────────────────
        // Views per slug
        $viewsBySlug = StorefrontEvent::where('event_type', 'product_view')
            ->where('created_at', '>=', $since)
            ->get()
            ->groupBy(fn($e) => $e->payload['slug'] ?? '__unknown')
            ->map(fn($g) => $g->count());

        // Carts per slug
        $cartsBySlug = StorefrontEvent::where('event_type', 'add_to_cart')
            ->where('created_at', '>=', $since)
            ->get()
            ->groupBy(fn($e) => $e->payload['slug'] ?? '__unknown')
            ->map(fn($g) => [
                'count' => $g->count(),
                'name'  => $g->first()->payload['name'] ?? null,
            ]);

        // Orders per slug (dari JSON items)
        $ordersBySlug = collect();
        StorefrontOrder::where('created_at', '>=', $since)->get()->each(function ($order) use (&$ordersBySlug) {
            foreach ($order->items ?? [] as $item) {
                $slug = $item['slug'] ?? '__unknown';
                $ordersBySlug[$slug] = ($ordersBySlug[$slug] ?? 0) + 1;
            }
        });

        // Gabungkan
        $allSlugs = $viewsBySlug->keys()
            ->merge($cartsBySlug->keys())
            ->merge($ordersBySlug->keys())
            ->unique();

        $productConversion = $allSlugs->map(function ($slug) use ($viewsBySlug, $cartsBySlug, $ordersBySlug) {
            $views  = $viewsBySlug->get($slug, 0);
            $carts  = $cartsBySlug->get($slug)['count'] ?? 0;
            $orders = $ordersBySlug->get($slug, 0);
            $name   = $cartsBySlug->get($slug)['name'] ?? $slug;
            return compact('slug', 'name', 'views', 'carts', 'orders');
        })->sortByDesc('views')->values()->take(12);

        // ── UTM Sources ───────────────────────────────────────────────────────
        $utmSources = StorefrontVisitor::where('first_seen_at', '>=', $since)
            ->whereNotNull('utm_source')
            ->select('utm_source', DB::raw('count(*) as total'))
            ->groupBy('utm_source')
            ->orderByDesc('total')
            ->get();

        $utmCampaigns = StorefrontVisitor::where('first_seen_at', '>=', $since)
            ->whereNotNull('utm_campaign')
            ->select('utm_campaign', 'utm_source', DB::raw('count(*) as total'))
            ->groupBy('utm_campaign', 'utm_source')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $organicCount = StorefrontVisitor::where('first_seen_at', '>=', $since)
            ->whereNull('utm_source')
            ->count();

        // ── Peak hours ────────────────────────────────────────────────────────
        // SQLite: strftime('%H', ...) | MySQL: HOUR(...)
        $isSqlite = DB::getDriverName() === 'sqlite';
        $hourExpr = $isSqlite
            ? "CAST(strftime('%H', created_at) AS INTEGER)"
            : 'HOUR(created_at)';

        $peakHours = StorefrontEvent::where('created_at', '>=', $since)
            ->select(DB::raw("{$hourExpr} as hour"), DB::raw('count(*) as total'))
            ->groupBy(DB::raw($hourExpr))
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        $maxHourCount = $peakHours->max('total') ?: 1;

        return view('admin.crm.dashboard', compact(
            'funnel', 'revenue', 'topProducts', 'topCities', 'dailyOrders', 'days',
            'returningCount', 'utmSources', 'utmCampaigns', 'organicCount',
            'peakHours', 'maxHourCount',
            'mobileCount', 'desktopCount',
            'avgCartToOrderMinutes', 'avgCartToOrderSampleSize',
            'productConversion'
        ));
    }

    // ─── Orders ───────────────────────────────────────────────────────────────

    public function orders(Request $request)
    {
        $status = $request->query('status', '');
        $search = trim($request->query('search', ''));

        $query = StorefrontOrder::latest();

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(25)->withQueryString();

        $statusCounts = StorefrontOrder::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $agingCount = StorefrontOrder::where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(24))
            ->count();

        return view('admin.crm.orders', compact('orders', 'status', 'search', 'statusCounts', 'agingCount'));
    }

    public function updateStatus(Request $request, StorefrontOrder $order)
    {
        $request->validate([
            'status' => ['required', 'in:pending,confirmed,processing,shipped,done,cancelled'],
        ]);

        $order->update(['status' => $request->status]);

        return back()->with('success', "Order {$order->order_number} diupdate ke {$request->status}.");
    }

    // ─── Prospects ────────────────────────────────────────────────────────────

    public function prospects(Request $request)
    {
        $days   = (int) $request->query('days', 30);
        $days   = in_array($days, [1, 7, 30, 90]) ? $days : 30;
        $since  = now()->subDays($days)->startOfDay();
        $onlyId = $request->boolean('only_identified'); // hanya yang ada HP-nya

        // Token yang sudah order dalam periode ini
        $orderedTokens = StorefrontOrder::where('created_at', '>=', $since)
            ->pluck('visitor_token')
            ->filter()
            ->unique()
            ->values();

        // Visitor yang add_to_cart tapi TIDAK order
        $cartEvents = StorefrontEvent::where('event_type', 'add_to_cart')
            ->where('created_at', '>=', $since)
            ->whereNotIn('visitor_token', $orderedTokens)
            ->select('visitor_token', DB::raw('max(created_at) as last_cart_at'), DB::raw('count(*) as cart_count'))
            ->groupBy('visitor_token')
            ->orderByDesc('last_cart_at')
            ->get();

        $tokens = $cartEvents->pluck('visitor_token');

        $visitors = StorefrontVisitor::whereIn('visitor_token', $tokens)
            ->get()
            ->keyBy('visitor_token');

        // Semua cart items per prospect — group by visitor, lalu per slug ambil event terbaru
        $allCartItems = StorefrontEvent::where('event_type', 'add_to_cart')
            ->where('created_at', '>=', $since)
            ->whereIn('visitor_token', $tokens)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('visitor_token')
            ->map(fn($events) => $events
                // Per slug ambil event terakhir (reflects qty terkini)
                ->groupBy(fn($e) => $e->payload['slug'] ?? ($e->payload['name'] ?? 'unknown'))
                ->map(fn($g) => $g->first()->payload)
                ->values()
            );

        // Gabungkan
        $prospects = $cartEvents->map(function ($e) use ($visitors, $allCartItems) {
            $v     = $visitors->get($e->visitor_token);
            $items = $allCartItems->get($e->visitor_token, collect());
            return (object) [
                'visitor_token'  => $e->visitor_token,
                'name'           => $v?->customer_name,
                'phone'          => $v?->customer_phone,
                'city'           => $v?->city,
                'last_product'   => $items->first()['name'] ?? '-',
                'cart_items'     => $items,   // semua produk di keranjang
                'cart_count'     => $e->cart_count,
                'last_cart_at'   => $e->last_cart_at,
            ];
        });

        if ($onlyId) {
            $prospects = $prospects->filter(fn($p) => $p->phone);
        }

        return view('admin.crm.prospects', compact('prospects', 'days', 'onlyId'));
    }

    public function exportProspects(Request $request)
    {
        $days  = (int) $request->query('days', 30);
        $since = now()->subDays($days)->startOfDay();

        $orderedTokens = StorefrontOrder::where('created_at', '>=', $since)
            ->pluck('visitor_token')->filter()->unique()->values();

        $cartEvents = StorefrontEvent::where('event_type', 'add_to_cart')
            ->where('created_at', '>=', $since)
            ->whereNotIn('visitor_token', $orderedTokens)
            ->select('visitor_token', DB::raw('max(created_at) as last_cart_at'))
            ->groupBy('visitor_token')
            ->get();

        $tokens   = $cartEvents->pluck('visitor_token');
        $visitors = StorefrontVisitor::whereIn('visitor_token', $tokens)
            ->whereNotNull('customer_phone')
            ->get()
            ->keyBy('visitor_token');

        $lastProducts = StorefrontEvent::where('event_type', 'add_to_cart')
            ->where('created_at', '>=', $since)
            ->whereIn('visitor_token', $tokens)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('visitor_token')
            ->map(fn($g) => $g->first()->payload['name'] ?? '-');

        $rows   = [];
        $rows[] = ['Nama', 'Nomor HP', 'Kota', 'Produk Terakhir', 'Waktu Terakhir'];

        foreach ($cartEvents as $e) {
            $v = $visitors->get($e->visitor_token);
            if (! $v) continue;
            $rows[] = [
                $v->customer_name ?? '-',
                $v->customer_phone,
                $v->city ?? '-',
                $lastProducts->get($e->visitor_token, '-'),
                $e->last_cart_at,
            ];
        }

        $filename = 'prospects-' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel
            foreach ($rows as $row) {
                fputcsv($f, $row);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }
}
