<?php

namespace App\Console\Commands;

use App\Models\StorefrontEvent;
use App\Models\StorefrontOrder;
use App\Models\StorefrontVisitor;
use App\Services\WaNotificationService;
use Illuminate\Console\Command;

class SendWeeklyCrmSummary extends Command
{
    protected $signature   = 'crm:weekly-summary {--force : Kirim sekarang tanpa menunggu jadwal Senin}';
    protected $description = 'Kirim ringkasan CRM mingguan ke admin via WA (dijadwalkan setiap Senin jam 08:00)';

    public function handle(WaNotificationService $wa): int
    {
        $since = now()->subWeek()->startOfDay();
        $until = now();

        // ── Order & revenue ────────────────────────────────────────────────────
        $orderCount = StorefrontOrder::whereBetween('created_at', [$since, $until])->count();
        $revenue    = StorefrontOrder::whereBetween('created_at', [$since, $until])
            ->whereNotIn('status', ['cancelled'])->sum('total_amount');
        $pending    = StorefrontOrder::where('status', 'pending')->count();

        // ── Visitors & customers ───────────────────────────────────────────────
        $newVisitors  = StorefrontVisitor::where('first_seen_at', '>=', $since)->count();
        $newCustomers = StorefrontOrder::whereBetween('created_at', [$since, $until])
            ->whereNotNull('customer_phone')
            ->distinct('customer_phone')
            ->count('customer_phone');

        // ── Top products (add_to_cart) ─────────────────────────────────────────
        $topProducts = StorefrontEvent::where('event_type', 'add_to_cart')
            ->where('created_at', '>=', $since)
            ->get()
            ->groupBy(fn($e) => $e->payload['name'] ?? '?')
            ->map(fn($g) => $g->count())
            ->sortDesc()
            ->take(3);

        // ── Prospects baru ────────────────────────────────────────────────────
        $orderedTokens = StorefrontOrder::whereBetween('created_at', [$since, $until])
            ->pluck('visitor_token')->filter()->unique();
        $newProspects  = StorefrontEvent::where('event_type', 'add_to_cart')
            ->where('created_at', '>=', $since)
            ->whereNotIn('visitor_token', $orderedTokens)
            ->distinct('visitor_token')
            ->count('visitor_token');

        // ── Repeat buyers (all time) ───────────────────────────────────────────
        $repeatBuyers = StorefrontOrder::whereNotNull('customer_phone')
            ->whereNotIn('status', ['cancelled'])
            ->select('customer_phone')
            ->groupBy('customer_phone')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        // ── Format pesan ──────────────────────────────────────────────────────
        $dateRange = $since->format('d M') . ' – ' . $until->format('d M Y');
        $prodLines = $topProducts->isEmpty()
            ? '  (belum ada)'
            : $topProducts->map(fn($cnt, $name) => "  • {$name} ({$cnt}×)")->join("\n");

        $message = "📊 *Laporan Mingguan CRM*\n"
            . "_{$dateRange}_\n\n"
            . "🛒 Order masuk: *{$orderCount}*\n"
            . "💰 Revenue: *Rp" . number_format($revenue, 0, ',', '.') . "*\n"
            . ($pending > 0 ? "⚠️ Masih pending: *{$pending}* order\n" : '')
            . "👥 Visitor baru: *{$newVisitors}*\n"
            . "🆕 Customer baru: *{$newCustomers}*\n"
            . "🎯 Prospects baru: *{$newProspects}*\n"
            . "🔄 Repeat buyers total: *{$repeatBuyers}*\n\n"
            . "🔥 *Top Produk (Cart):*\n{$prodLines}\n\n"
            . "📱 " . route('admin.crm.dashboard');

        $wa->sendToAdmin($message);

        $this->info("✅ Weekly summary dikirim: {$orderCount} orders, Rp" . number_format($revenue));

        return self::SUCCESS;
    }
}
