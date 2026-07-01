<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorefrontEvent;
use App\Models\StorefrontVisitor;
use Illuminate\Support\Facades\DB;

class StorefrontVisitorController extends Controller
{
    public function index()
    {
        $days = (int) request('days', 30);
        $since = now()->subDays($days)->startOfDay();

        // ── Stat cards ────────────────────────────────────────────────────────

        // Total unique visitors dalam periode
        $uniqueVisitors = StorefrontVisitor::where('first_seen_at', '>=', $since)->count();

        // Returning visitors: last_seen_at > first_seen_at + 1 jam (beda sesi)
        $returningVisitors = StorefrontVisitor::where('first_seen_at', '>=', $since)
            ->whereRaw("CAST((julianday(last_seen_at) - julianday(first_seen_at)) * 24 AS REAL) > 1")
            ->count();

        $returnRate = $uniqueVisitors > 0
            ? round($returningVisitors / $uniqueVisitors * 100, 1)
            : 0;

        // Avg time on page dari page_view_duration events
        $durationEvents = StorefrontEvent::where('event_type', 'page_view_duration')
            ->where('created_at', '>=', $since)
            ->get();

        $avgSeconds = $durationEvents->count() > 0
            ? (int) $durationEvents->avg(fn($e) => $e->payload['seconds'] ?? 0)
            : 0;
        $avgDurationFormatted = $avgSeconds >= 60
            ? floor($avgSeconds / 60) . 'm ' . ($avgSeconds % 60) . 's'
            : $avgSeconds . 's';

        // Bounce rate: visitor yang hanya punya 1 page_view/product_view event
        // (tidak ada add_to_cart, checkout, dsb)
        $totalSessions = StorefrontEvent::where('created_at', '>=', $since)
            ->whereIn('event_type', ['page_view', 'product_view'])
            ->distinct('visitor_token')
            ->count('visitor_token');

        // Engaged = visitor yang punya event selain page_view (add_to_cart, checkout, dll)
        $engagedTokens = StorefrontEvent::where('created_at', '>=', $since)
            ->whereNotIn('event_type', ['page_view', 'product_view', 'page_view_duration'])
            ->distinct('visitor_token')
            ->pluck('visitor_token')
            ->toArray();

        // Bounce = hanya 1 page_view total, tidak ada interaksi lain
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

        // ── Daily unique visitors (chart) ─────────────────────────────────────
        $isSqlite = DB::getDriverName() === 'sqlite';
        $dateExpr = $isSqlite
            ? "date(first_seen_at)"
            : "DATE(first_seen_at)";

        $dailyVisitors = StorefrontVisitor::where('first_seen_at', '>=', $since)
            ->selectRaw("{$dateExpr} as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Isi gap hari yang tidak ada visitor
        $chartLabels = [];
        $chartData   = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
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
                return $slug ? "Produk: {$slug}" : str_replace('storefront.', '', $route);
            })
            ->map(fn($g) => [
                'label'    => $g->first()->payload['route'] ?? 'unknown',
                'slug'     => $g->first()->payload['slug'] ?? null,
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
                return $slug ? "Produk: {$slug}" : str_replace('storefront.', '', $route);
            })
            ->map(fn($g) => [
                'page'     => $g->first()->payload['route'] ?? '-',
                'avg_sec'  => (int) $g->avg(fn($e) => $e->payload['seconds'] ?? 0),
                'count'    => $g->count(),
            ])
            ->sortByDesc('avg_sec')
            ->take(10)
            ->values();

        // ── Recent sessions (top 20 visitors by event count) ─────────────────
        $recentSessions = StorefrontVisitor::where('first_seen_at', '>=', $since)
            ->orderByDesc('last_seen_at')
            ->take(20)
            ->get()
            ->map(function ($v) {
                $events = StorefrontEvent::where('visitor_token', $v->visitor_token)
                    ->orderBy('created_at')
                    ->get();
                $durationEvent = $events->where('event_type', 'page_view_duration')->last();
                $durationSec   = $durationEvent ? ($durationEvent->payload['seconds'] ?? 0) : 0;

                // Total duration dari semua page_view_duration events
                $totalSec = $events->where('event_type', 'page_view_duration')
                    ->sum(fn($e) => $e->payload['seconds'] ?? 0);

                return [
                    'visitor'    => $v,
                    'page_views' => $events->whereIn('event_type', ['page_view', 'product_view'])->count(),
                    'total_sec'  => $totalSec,
                    'events'     => $events->count(),
                    'has_cart'   => $events->where('event_type', 'add_to_cart')->count() > 0,
                    'has_order'  => $events->where('event_type', 'order_complete')->count() > 0,
                ];
            });

        return view('admin.crm.visitors', compact(
            'days',
            'uniqueVisitors',
            'returningVisitors',
            'returnRate',
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
}
