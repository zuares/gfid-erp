<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorefrontCustomer;
use App\Models\StorefrontEvent;
use App\Models\StorefrontOrder;
use App\Models\StorefrontVisitor;
use Illuminate\Support\Facades\DB;

class StorefrontVisitorController extends Controller
{
    // ── Human-readable route labels ───────────────────────────────────────────
    private const ROUTE_LABELS = [
        'storefront.home'              => 'Beranda',
        'storefront.products'          => 'Produk',
        'storefront.product_detail'    => 'Detail Produk',
        'storefront.cart'              => 'Keranjang',
        'storefront.checkout'          => 'Checkout',
        'storefront.checkout.address'  => 'Form Alamat',
        'storefront.login'             => 'Halaman Login',
        'storefront.login.verify'      => 'Verifikasi OTP',
        'storefront.register'          => 'Registrasi',
        'storefront.user'              => 'Profil Saya',
        'storefront.user.orders'       => 'Pesanan Saya',
        'storefront.order.success'     => 'Order Berhasil',
    ];

    private function parseBrand(?string $ua): string
    {
        if (! $ua) return '—';

        // Apple
        if (str_contains($ua, 'iPhone'))    return 'iPhone';
        if (str_contains($ua, 'iPad'))      return 'iPad';
        if (str_contains($ua, 'Macintosh')) return 'Mac';

        // Samsung
        if (preg_match('/samsung|SM-[A-Z]{1,2}\d{3,}/i', $ua)) return 'Samsung';

        // Xiaomi family
        if (preg_match('/xiaomi|redmi|poco/i', $ua)) return 'Xiaomi';

        // OPPO family
        if (preg_match('/oppo|CPH\d{4}/i', $ua)) return 'OPPO';

        // Realme
        if (preg_match('/realme|RMX\d{4}/i', $ua)) return 'Realme';

        // Vivo
        if (preg_match('/\bvivo\b/i', $ua)) return 'Vivo';

        // Huawei / Honor
        if (preg_match('/huawei|honor/i', $ua)) return 'Huawei';

        // OnePlus
        if (preg_match('/oneplus/i', $ua)) return 'OnePlus';

        // Generic Android
        if (str_contains($ua, 'Android')) return 'Android';

        // Desktop OS
        if (str_contains($ua, 'Windows')) return 'Windows';
        if (str_contains($ua, 'Linux'))   return 'Linux';

        return '—';
    }

    private function parseDevice(?string $ua): string
    {
        if (! $ua) return 'desktop';
        $ua = strtolower($ua);
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) return 'tablet';
        if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|windows phone|iemobile/', $ua)) return 'mobile';
        return 'desktop';
    }

    private function routeLabel(string $route, ?string $slug = null): string
    {
        if ($slug) return "Produk: {$slug}";
        return self::ROUTE_LABELS[$route] ?? str_replace(['storefront.', '_'], ['', ' '], $route);
    }

    public function index()
    {
        $days  = (int) request('days', 30);
        $since = now()->subDays($days)->startOfDay();

        // ── Stat cards ────────────────────────────────────────────────────────

        $uniqueVisitors = StorefrontVisitor::where('first_seen_at', '>=', $since)->count();

        $returningVisitors = StorefrontVisitor::where('first_seen_at', '>=', $since)
            ->whereRaw("CAST((julianday(last_seen_at) - julianday(first_seen_at)) * 24 AS REAL) > 1")
            ->count();

        $returnRate = $uniqueVisitors > 0
            ? round($returningVisitors / $uniqueVisitors * 100, 1)
            : 0;

        // Visitors dalam periode yang punya storefront_customers account
        $visitorTokensInPeriod = StorefrontVisitor::where('first_seen_at', '>=', $since)
            ->pluck('visitor_token');

        // Cek via customer_phone yang tersimpan di visitor, atau via orders
        $visitorsWithPhone = StorefrontVisitor::where('first_seen_at', '>=', $since)
            ->whereNotNull('customer_phone')
            ->pluck('customer_phone')
            ->map(fn($p) => str_starts_with($p, '0') ? '62' . substr($p, 1) : $p)
            ->unique();

        $registeredVisitors = StorefrontCustomer::whereIn('phone', $visitorsWithPhone)->count();

        // Avg duration
        $durationEvents = StorefrontEvent::where('event_type', 'page_view_duration')
            ->where('created_at', '>=', $since)
            ->get();

        $avgSeconds = $durationEvents->count() > 0
            ? (int) $durationEvents->avg(fn($e) => $e->payload['seconds'] ?? 0)
            : 0;

        $avgDurationFormatted = $avgSeconds >= 60
            ? floor($avgSeconds / 60) . 'm ' . ($avgSeconds % 60) . 's'
            : $avgSeconds . 's';

        // Bounce rate
        $totalSessions = StorefrontEvent::where('created_at', '>=', $since)
            ->whereIn('event_type', ['page_view', 'product_view'])
            ->distinct('visitor_token')
            ->count('visitor_token');

        $engagedTokens = StorefrontEvent::where('created_at', '>=', $since)
            ->whereNotIn('event_type', ['page_view', 'product_view', 'page_view_duration'])
            ->distinct('visitor_token')
            ->pluck('visitor_token')
            ->toArray();

        $bouncedCount = StorefrontEvent::where('created_at', '>=', $since)
            ->whereIn('event_type', ['page_view', 'product_view'])
            ->whereNotIn('visitor_token', $engagedTokens)
            ->select('visitor_token')
            ->groupBy('visitor_token')
            ->havingRaw('COUNT(*) = 1')
            ->get()
            ->count();

        $bounceRate = $totalSessions > 0
            ? round($bouncedCount / $totalSessions * 100, 1)
            : 0;

        // ── Daily visitors chart ──────────────────────────────────────────────
        $isSqlite = DB::getDriverName() === 'sqlite';
        $dateExpr = $isSqlite ? "date(first_seen_at)" : "DATE(first_seen_at)";

        $dailyVisitors = StorefrontVisitor::where('first_seen_at', '>=', $since)
            ->selectRaw("{$dateExpr} as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chartLabels = [];
        $chartData   = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d             = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->format('d/m');
            $chartData[]   = $dailyVisitors->get($d)?->count ?? 0;
        }

        // ── Most visited pages ────────────────────────────────────────────────
        $pageViews = StorefrontEvent::where('created_at', '>=', $since)
            ->whereIn('event_type', ['page_view', 'product_view'])
            ->get()
            ->groupBy(function ($e) {
                $route = $e->payload['route'] ?? 'unknown';
                $slug  = $e->payload['slug'] ?? null;
                return $slug ? "product:{$slug}" : $route;
            })
            ->map(fn($g) => [
                'label'    => $this->routeLabel(
                    $g->first()->payload['route'] ?? 'unknown',
                    $g->first()->payload['slug'] ?? null
                ),
                'slug'     => $g->first()->payload['slug'] ?? null,
                'route'    => $g->first()->payload['route'] ?? null,
                'count'    => $g->count(),
                'visitors' => $g->pluck('visitor_token')->unique()->count(),
            ])
            ->sortByDesc('count')
            ->take(15)
            ->values();

        // ── Most clicked elements ─────────────────────────────────────────────
        $clickEvents = StorefrontEvent::where('event_type', 'click')
            ->where('created_at', '>=', $since)
            ->get()
            ->groupBy(fn($e) => $e->payload['label'] ?? 'unknown')
            ->map(fn($g) => [
                'label'    => $g->first()->payload['label'] ?? 'unknown',
                'count'    => $g->count(),
                'visitors' => $g->pluck('visitor_token')->unique()->count(),
            ])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        // ── Page duration breakdown ───────────────────────────────────────────
        $pageDurations = $durationEvents
            ->groupBy(function ($e) {
                $route = $e->payload['route'] ?? 'unknown';
                $slug  = $e->payload['slug'] ?? null;
                return $slug ? "product:{$slug}" : $route;
            })
            ->map(fn($g) => [
                'page'    => $this->routeLabel(
                    $g->first()->payload['route'] ?? '-',
                    $g->first()->payload['slug'] ?? null
                ),
                'avg_sec' => (int) $g->avg(fn($e) => $e->payload['seconds'] ?? 0),
                'count'   => $g->count(),
            ])
            ->sortByDesc('avg_sec')
            ->take(10)
            ->values();

        // ── Recent sessions — batch load to avoid N+1 ────────────────────────
        $recentVisitors = StorefrontVisitor::where('first_seen_at', '>=', $since)
            ->orderByDesc('last_seen_at')
            ->take(25)
            ->get();

        $recentTokens = $recentVisitors->pluck('visitor_token')->all();

        // Batch load all events for these 25 visitors in one query
        $allEvents = StorefrontEvent::whereIn('visitor_token', $recentTokens)
            ->orderBy('created_at')
            ->get()
            ->groupBy('visitor_token');

        // Build set of phones that have accounts (for fast lookup)
        $accountedPhones = StorefrontCustomer::pluck('phone')
            ->map(fn($p) => str_starts_with($p, '0') ? '62' . substr($p, 1) : $p)
            ->flip(); // keyed by phone for O(1) lookup

        // Build set of visitor_tokens that have orders
        $orderedTokens = StorefrontOrder::whereIn('visitor_token', $recentTokens)
            ->pluck('visitor_token')
            ->flip();

        $recentSessions = $recentVisitors->map(function ($v) use ($allEvents, $accountedPhones, $orderedTokens) {
            $events = $allEvents->get($v->visitor_token, collect());

            $totalSec = $events->where('event_type', 'page_view_duration')
                ->sum(fn($e) => $e->payload['seconds'] ?? 0);

            // Phone from visitor record (set when address form is filled)
            $normPhone = $v->customer_phone
                ? (str_starts_with($v->customer_phone, '0')
                    ? '62' . substr($v->customer_phone, 1)
                    : $v->customer_phone)
                : null;

            $hasAccount = $normPhone && isset($accountedPhones[$normPhone]);
            $hasOrder   = isset($orderedTokens[$v->visitor_token])
                || $events->where('event_type', 'order_complete')->isNotEmpty();

            return [
                'visitor'    => $v,
                'page_views' => $events->whereIn('event_type', ['page_view', 'product_view'])->count(),
                'total_sec'  => (int) $totalSec,
                'events'     => $events->count(),
                'has_cart'   => $events->where('event_type', 'add_to_cart')->isNotEmpty(),
                'has_order'  => $hasOrder,
                'has_account' => $hasAccount,
                'has_login'  => $events->whereIn('event_type', ['page_view'])
                    ->filter(fn($e) => in_array(
                        $e->payload['route'] ?? '',
                        ['storefront.login', 'storefront.register', 'storefront.login.verify']
                    ))->isNotEmpty(),
                'is_active'  => \Carbon\Carbon::parse($v->last_seen_at)->diffInMinutes(now()) < 5,
                'device'     => $this->parseDevice($v->user_agent),
                'brand'      => $this->parseBrand($v->user_agent),
            ];
        });

        return view('admin.crm.visitors', compact(
            'days',
            'uniqueVisitors',
            'returningVisitors',
            'returnRate',
            'registeredVisitors',
            'avgSeconds',
            'avgDurationFormatted',
            'bounceRate',
            'bouncedCount',
            'totalSessions',
            'chartLabels',
            'chartData',
            'pageViews',
            'clickEvents',
            'pageDurations',
            'recentSessions'
        ));
    }

    // ── Live endpoint — JSON untuk AJAX polling ───────────────────────────────

    public function live()
    {
        $days  = (int) request('days', 30);
        $since = now()->subDays($days)->startOfDay();

        // ── Stats ─────────────────────────────────────────────────────────────
        $uniqueVisitors = StorefrontVisitor::where('first_seen_at', '>=', $since)->count();

        $visitorsWithPhone = StorefrontVisitor::where('first_seen_at', '>=', $since)
            ->whereNotNull('customer_phone')
            ->pluck('customer_phone')
            ->map(fn($p) => str_starts_with($p, '0') ? '62' . substr($p, 1) : $p)
            ->unique();

        $registeredVisitors = StorefrontCustomer::whereIn('phone', $visitorsWithPhone)->count();

        $totalSessions = StorefrontEvent::where('created_at', '>=', $since)
            ->whereIn('event_type', ['page_view', 'product_view'])
            ->distinct('visitor_token')
            ->count('visitor_token');

        $engagedTokens = StorefrontEvent::where('created_at', '>=', $since)
            ->whereNotIn('event_type', ['page_view', 'product_view', 'page_view_duration'])
            ->distinct('visitor_token')
            ->pluck('visitor_token')
            ->toArray();

        $bouncedCount = StorefrontEvent::where('created_at', '>=', $since)
            ->whereIn('event_type', ['page_view', 'product_view'])
            ->whereNotIn('visitor_token', $engagedTokens)
            ->select('visitor_token')
            ->groupBy('visitor_token')
            ->havingRaw('COUNT(*) = 1')
            ->get()->count();

        $bounceRate = $totalSessions > 0 ? round($bouncedCount / $totalSessions * 100, 1) : 0;

        $returningVisitors = StorefrontVisitor::where('first_seen_at', '>=', $since)
            ->whereRaw("CAST((julianday(last_seen_at) - julianday(first_seen_at)) * 24 AS REAL) > 1")
            ->count();
        $returnRate = $uniqueVisitors > 0 ? round($returningVisitors / $uniqueVisitors * 100, 1) : 0;

        // Visitors active in last 5 minutes
        $activeNow = StorefrontVisitor::where('last_seen_at', '>=', now()->subMinutes(5))->count();

        // ── Recent sessions (25 latest) ───────────────────────────────────────
        $recentVisitors = StorefrontVisitor::orderByDesc('last_seen_at')->take(25)->get();
        $recentTokens   = $recentVisitors->pluck('visitor_token')->all();

        $allEvents = StorefrontEvent::whereIn('visitor_token', $recentTokens)
            ->orderBy('created_at')
            ->get()
            ->groupBy('visitor_token');

        $accountedPhones = StorefrontCustomer::pluck('phone')
            ->map(fn($p) => str_starts_with($p, '0') ? '62' . substr($p, 1) : $p)
            ->flip();

        $orderedTokens = StorefrontOrder::whereIn('visitor_token', $recentTokens)
            ->pluck('visitor_token')->flip();

        $sessions = $recentVisitors->map(function ($v) use ($allEvents, $accountedPhones, $orderedTokens) {
            $events   = $allEvents->get($v->visitor_token, collect());
            $totalSec = (int) $events->where('event_type', 'page_view_duration')
                ->sum(fn($e) => $e->payload['seconds'] ?? 0);

            $normPhone  = $v->customer_phone
                ? (str_starts_with($v->customer_phone, '0') ? '62' . substr($v->customer_phone, 1) : $v->customer_phone)
                : null;
            $hasAccount = $normPhone && isset($accountedPhones[$normPhone]);
            $hasOrder   = isset($orderedTokens[$v->visitor_token])
                || $events->where('event_type', 'order_complete')->isNotEmpty();

            $isActive = \Carbon\Carbon::parse($v->last_seen_at)->diffInMinutes(now()) < 5;

            return [
                'token'       => substr($v->visitor_token, 0, 14) . '…',
                'name'        => $v->customer_name,
                'phone'       => $v->customer_phone,
                'city'        => $v->city ? strtolower($v->city) : null,
                'province'    => $v->province,
                'page_views'  => $events->whereIn('event_type', ['page_view', 'product_view'])->count(),
                'total_sec'   => $totalSec,
                'has_order'   => $hasOrder,
                'has_account' => $hasAccount,
                'has_cart'    => $events->where('event_type', 'add_to_cart')->isNotEmpty(),
                'has_login'   => $events->whereIn('event_type', ['page_view'])
                    ->filter(fn($e) => in_array($e->payload['route'] ?? '', ['storefront.login', 'storefront.register', 'storefront.login.verify']))
                    ->isNotEmpty(),
                'last_seen'   => \Carbon\Carbon::parse($v->last_seen_at)->diffForHumans(),
                'is_active'   => $isActive,
                'device'      => $this->parseDevice($v->user_agent),
                'brand'       => $this->parseBrand($v->user_agent),
            ];
        });

        return response()->json([
            'stats' => [
                'unique_visitors'     => $uniqueVisitors,
                'registered_visitors' => $registeredVisitors,
                'return_rate'         => $returnRate,
                'bounce_rate'         => $bounceRate,
                'active_now'          => $activeNow,
            ],
            'sessions'    => $sessions->values(),
            'fetched_at'  => now()->format('H:i:s'),
        ]);
    }
}
