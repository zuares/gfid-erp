<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorefrontCustomer;
use App\Models\StorefrontOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StorefrontSegmentController extends Controller
{
    // ── Definisi segment ──────────────────────────────────────────────────────

    public static function segments(): array
    {
        return [
            'champions'   => [
                'label'   => 'Champions',
                'icon'    => 'bi-trophy',
                'color'   => '#f59e0b',
                'bg'      => '#fffbeb',
                'desc'    => 'Order ≥3× dan belanja dalam 60 hari terakhir',
                'action'  => 'Minta jadi brand ambassador / ajak referral',
                'message' => "Halo {name}! 🏆\n\nKamu adalah pelanggan setia kami dan kami benar-benar menghargai itu!\n\nSebagai bentuk apresiasi, kami ingin menawarkan kamu akses eksklusif ke koleksi terbaru sebelum diluncurkan ke publik. Tertarik? 😊\n\n",
            ],
            'loyal'       => [
                'label'   => 'Loyal',
                'icon'    => 'bi-heart',
                'color'   => '#ef4444',
                'bg'      => '#fef2f2',
                'desc'    => 'Order ≥2× dan aktif dalam 90 hari',
                'action'  => 'Tawarkan produk baru / bundle deal',
                'message' => "Halo {name}! ❤️\n\nMakasih ya sudah setia belanja di kami. Kami punya koleksi baru yang mungkin cocok buat kamu!\n\nMau lihat-lihat dulu? Klik link berikut: ",
            ],
            'new'         => [
                'label'   => 'New Customer',
                'icon'    => 'bi-stars',
                'color'   => '#6366f1',
                'bg'      => '#eef2ff',
                'desc'    => 'Baru pertama order dalam 30 hari terakhir',
                'action'  => 'Follow up kepuasan, minta review',
                'message' => "Halo {name}! 👋\n\nTerima kasih sudah percaya belanja di kami untuk pertama kalinya!\n\nGimana produknya? Apakah sesuai ekspektasi? Kalau ada yang kurang berkenan, kami sangat ingin tahu 🙏\n\n",
            ],
            'promising'   => [
                'label'   => 'Promising',
                'icon'    => 'bi-graph-up-arrow',
                'color'   => '#10b981',
                'bg'      => '#f0fdf4',
                'desc'    => 'Baru 1× order, antara 30–90 hari lalu',
                'action'  => 'Dorong ke pembelian kedua dengan penawaran',
                'message' => "Halo {name}! 😊\n\nSudah beberapa waktu sejak order pertamamu. Kami harap produknya memuaskan!\n\nKami ada koleksi baru yang mungkin kamu suka. Mau lihat? ",
            ],
            'at_risk'     => [
                'label'   => 'At Risk',
                'icon'    => 'bi-exclamation-triangle',
                'color'   => '#f97316',
                'bg'      => '#fff7ed',
                'desc'    => 'Pernah beli ≥2×, tapi terakhir order >90 hari',
                'action'  => 'Re-engagement — tawarkan promo atau tanya kabar',
                'message' => "Halo {name}! 👋\n\nUdah lama nih nggak ketemu — kami kangen pelanggan setia seperti kamu!\n\nAda koleksi baru yang datang dan kami langsung kepikiran kamu. Mau lihat dulu? ",
            ],
            'lost'        => [
                'label'   => 'Lost',
                'icon'    => 'bi-person-dash',
                'color'   => '#94a3b8',
                'bg'      => '#f8fafc',
                'desc'    => 'Tidak ada order lebih dari 180 hari',
                'action'  => 'Win-back campaign dengan diskon atau penawaran spesial',
                'message' => "Halo {name}! 🙏\n\nSudah lama banget kami tidak bertemu. Kami harap semuanya baik-baik saja!\n\nKami ingin mengajak kamu kembali dengan penawaran spesial eksklusif. Tertarik? ",
            ],
            'big_spender' => [
                'label'   => 'Big Spender',
                'icon'    => 'bi-gem',
                'color'   => '#8b5cf6',
                'bg'      => '#faf5ff',
                'desc'    => 'Total belanja ≥ Rp1jt (di luar kategori lain)',
                'action'  => 'Tawarkan produk premium / layanan prioritas',
                'message' => "Halo {name}! 💎\n\nSebagai pelanggan dengan belanja tertinggi di toko kami, kamu layak mendapatkan yang terbaik!\n\nKami punya penawaran eksklusif yang sudah kami siapkan khusus untuk kamu. Mau dengar? ",
            ],
        ];
    }

    // ── Helper: hitung segment dari data customer ─────────────────────────────

    public static function classify(int $orderCount, float $daysSinceLast, float $totalSpent): string
    {
        if ($orderCount >= 3 && $daysSinceLast <= 60)  return 'champions';
        if ($orderCount >= 2 && $daysSinceLast <= 90)  return 'loyal';
        if ($orderCount === 1 && $daysSinceLast <= 30)  return 'new';
        if ($daysSinceLast > 180)                       return 'lost';
        if ($orderCount >= 2 && $daysSinceLast > 90)    return 'at_risk';
        if ($totalSpent >= 1_000_000)                   return 'big_spender';
        return 'promising';
    }

    // ── Ambil semua customer + segment mereka ─────────────────────────────────

    private function allCustomers(): Collection
    {
        // Pre-load phones that have a registered StorefrontCustomer account
        // StorefrontCustomer.phone is stored as 628xxx; normalise to base (strip leading 62/0)
        $accountPhones = StorefrontCustomer::whereNotNull('phone')
            ->pluck('phone')
            ->mapWithKeys(function ($p) {
                $base = preg_replace('/^(\+?62|0)/', '', $p);
                return [$base => true];
            });

        return StorefrontOrder::whereNotNull('customer_phone')
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
            ->groupBy('customer_phone')
            ->get()
            ->map(function ($c) use ($accountPhones) {
                $daysSinceLast = now()->diffInDays($c->last_order_at);
                // Normalise phone → wa_phone (always 628xxx)
                $base           = preg_replace('/^(\+?62|0)/', '', $c->customer_phone ?? '');
                $c->wa_phone    = '62' . $base;
                $c->has_account = isset($accountPhones[$base]);
                $c->segment     = self::classify((int) $c->order_count, $daysSinceLast, (float) $c->total_spent);
                $c->days_since  = $daysSinceLast;
                return $c;
            });
    }

    // ── Index: overview semua segment ─────────────────────────────────────────

    public function index()
    {
        $customers    = $this->allCustomers();
        $segmentDefs  = self::segments();
        $totalCustomers = $customers->count();
        $totalRevenue   = $customers->sum('total_spent');

        $overview = collect($segmentDefs)->map(function ($def, $key) use ($customers, $totalCustomers) {
            $group = $customers->where('segment', $key);
            $cnt   = $group->count();
            return array_merge($def, [
                'key'         => $key,
                'count'       => $cnt,
                'pct'         => $totalCustomers > 0 ? round($cnt / $totalCustomers * 100) : 0,
                'revenue'     => $group->sum('total_spent'),
                'avg_clv'     => $cnt > 0 ? (int) $group->avg('total_spent') : 0,
                'has_phone'   => $group->filter(fn ($c) => $c->customer_phone)->count(),
                'has_account' => $group->filter(fn ($c) => $c->has_account)->count(),
            ]);
        });

        return view('admin.crm.segments.index', compact(
            'overview', 'totalCustomers', 'totalRevenue'
        ));
    }

    // ── Show: customer dalam satu segment ─────────────────────────────────────

    public function show(string $segment)
    {
        $segmentDefs = self::segments();
        abort_if(! array_key_exists($segment, $segmentDefs), 404);

        $def       = $segmentDefs[$segment];
        $customers = $this->allCustomers()
            ->where('segment', $segment)
            ->sortByDesc('total_spent')
            ->values();

        return view('admin.crm.segments.show', compact('segment', 'def', 'customers'));
    }
}
