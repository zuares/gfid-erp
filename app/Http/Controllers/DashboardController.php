<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

/**
 * Dashboard beranda per-role.
 *
 * Menyajikan angka nyata + tombol aksi cepat dengan bahasa yang mudah
 * dimengerti di lapangan. Semua query dibungkus aman (safe*) supaya halaman
 * tetap tampil walau sebagian tabel kosong atau skema berbeda di production.
 */
class DashboardController extends Controller
{
    /** Kode gudang penting untuk membaca posisi barang di tiap tahap. */
    private const WH = [
        'RM'       => 'RM',
        'CUT'      => 'WIP-CUT',
        'SEW'      => 'WIP-SEW',
        'FIN'      => 'WIP-FIN',
        'PACK'     => 'WIP-PACK',
        'FG'       => 'FG',
        'RTS'      => 'WH-RTS',
    ];

    public function devRunAudit(): \Illuminate\Http\JsonResponse
    {
        \Illuminate\Support\Facades\Artisan::call('inventory:audit-allocated', ['--quiet' => false]);
        return response()->json(['message' => "Audit selesai dijalankan."]);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $role = $this->roleBucket($user);

        $data = match ($role) {
            'owner'     => $this->ownerData(),
            'admin'     => $this->adminData(),
            'operating' => $this->operatingData(),
            default     => [],
        };

        return view('dashboard.index', [
            'role'      => $role,
            'greeting'  => $this->greeting(),
            'userName'  => $user?->name ?? 'User',
            'today'     => Carbon::now()->translatedFormat('l, d F Y'),
            'd'         => $data,
        ]);
    }

    // =====================================================================
    //  DATA PER ROLE
    // =====================================================================

    private function ownerData(): array
    {
        $sales7  = $this->mpSales(6);
        $salesTd = $this->mpSales(0);

        // Fetch Ads Analytics for Executive Dashboard (Owner only)
        $adsHourly = [];
        $adsCampaigns = [];
        try {
            if (Schema::hasTable('marketplace_ads_hourlies') && Schema::hasTable('marketplace_ads_campaign_dailies')) {
                $from = Carbon::today()->subDays(6)->toDateString();
                $to = Carbon::today()->toDateString();
                
                // 1. Heatmap (7 Days)
                $adsHourly = DB::table('marketplace_ads_hourlies')
                    ->selectRaw('performance_hour, SUM(expense) as spend, SUM(broad_gmv) as gmv, SUM(clicks) as clicks, SUM(broad_order) as orders')
                    ->whereBetween('date', [$from, $to])
                    ->groupBy('performance_hour')
                    ->orderBy('performance_hour')
                    ->get();
                    
                // 2. Top 5 Campaigns Spend Share (7 Days)
                $adsCampaigns = DB::table('marketplace_ads_campaign_dailies')
                    ->join('marketplace_ad_campaigns', 'marketplace_ads_campaign_dailies.channel_campaign_id', '=', 'marketplace_ad_campaigns.campaign_id')
                    ->selectRaw('marketplace_ad_campaigns.campaign_name, SUM(marketplace_ads_campaign_dailies.expense) as total_spend')
                    ->whereBetween('marketplace_ads_campaign_dailies.date', [$from, $to])
                    ->groupBy('marketplace_ad_campaigns.campaign_name')
                    ->having('total_spend', '>', 0)
                    ->orderByDesc('total_spend')
                    ->limit(5)
                    ->get();
            }
        } catch (\Throwable $e) {}

        return [
            'sales_today_count'   => $salesTd['count'],
            'sales_today_amount'  => $salesTd['amount'],
            'sales_7_count'       => $sales7['count'],
            'sales_7_amount'      => $sales7['amount'],
            'orders_todo'         => $this->mpUnshippedCount(),
            'orders_ready'        => $this->mpStatusCount(['READY_TO_SHIP']),
            'orders_shipped_7'    => $this->mpStatusCount(['SHIPPED'], 6),
            'wip_total'           => $this->whQty([self::WH['CUT'], self::WH['SEW'], self::WH['FIN'], self::WH['PACK']]),
            'fg_ready'            => $this->whQty([self::WH['FG'], self::WH['RTS']]),
            'stock_out_fg'        => $this->stockOutCount('finished_good'),
            'stock_out_rm'        => $this->stockOutCount('material'),
            'po_unreceived'       => $this->poCount(['not_received', 'partially_received'], 'received_status'),
            'po_unpaid'           => $this->poCount(['unpaid', 'partial'], 'payment_status'),
            'reject_total'        => $this->whQty(['REJ-CUT', 'REJ-SEW', 'REJ-FIN', 'REJECT']),
            'list_todo'           => $this->mpUnshippedList(6),
            'list_stock'          => $this->stockCriticalList(6),
            'ads_hourly'          => $adsHourly,
            'ads_campaigns'       => $adsCampaigns,
        ];
    }

    private function adminData(): array
    {
        $salesTd = $this->mpSales(0);
        $sales7  = $this->mpSales(6);

        return [
            'orders_today'      => $this->mpOrdersInPeriod(0),
            'sales_today'       => $salesTd['amount'],
            'sales_7'           => $sales7['amount'],
            'orders_todo'       => $this->mpUnshippedCount(),
            'orders_ready'      => $this->mpStatusCount(['READY_TO_SHIP']),
            'orders_processed'  => $this->mpStatusCount(['PROCESSED']),
            'orders_shipped_7'  => $this->mpStatusCount(['SHIPPED'], 6),
            'orders_issue'      => $this->mpNeedFixCount(),
            'po_unreceived'     => $this->poCount(['not_received', 'partially_received'], 'received_status'),
            'stock_out_fg'      => $this->stockOutCount('finished_good'),
            'fg_ready'          => $this->whQty([self::WH['FG'], self::WH['RTS']]),
            'list_todo'         => $this->mpUnshippedList(10),
            'list_stock'        => $this->stockCriticalList(6, 'finished_good'),
        ];
    }

    private function operatingData(): array
    {
        return [
            'wip_cut'        => $this->whQty([self::WH['CUT']]),
            'wip_sew'        => $this->whQty([self::WH['SEW']]),
            'wip_fin'        => $this->whQty([self::WH['FIN']]),
            'wip_pack'       => $this->whQty([self::WH['PACK']]),
            'fg_ready'       => $this->whQty([self::WH['FG'], self::WH['RTS']]),
            'rm_low'         => $this->stockOutCount('material'),
            'reject_total'   => $this->whQty(['REJ-CUT', 'REJ-SEW', 'REJ-FIN', 'REJECT']),
            'stock_req_open' => $this->stockRequestOpen(),
            'list_sew'       => $this->whItemsList(self::WH['SEW'], 8),
            'list_rm'        => $this->stockCriticalList(8, 'material'),
        ];
    }

    // =====================================================================
    //  HELPER QUERY (defensif)
    // =====================================================================

    /** Jalankan closure query, kembalikan fallback bila gagal / tabel tak ada. */
    private function safe(callable $fn, $fallback = 0)
    {
        try {
            return $fn();
        } catch (Throwable) {
            return $fallback;
        }
    }

    /** Penjualan marketplace n hari ke belakang (0 = hari ini). */
    private function mpSales(int $daysBack): array
    {
        return $this->safe(function () use ($daysBack) {
            if (!Schema::hasTable('marketplace_orders')) {
                return ['count' => 0, 'amount' => 0];
            }
            $from = Carbon::today()->subDays($daysBack)->startOfDay();
            $to   = Carbon::today()->endOfDay();
            $col  = $this->mpDateCol();

            $q = DB::table('marketplace_orders')
                ->whereBetween($col, [$from, $to])
                ->where(fn ($w) => $w->where('order_status', '!=', 'CANCELLED')->orWhereNull('order_status'));

            $amountExpr = 'COALESCE(total_amount, total_paid_customer, 0)';

            return [
                'count'  => (int) (clone $q)->count(),
                'amount' => (float) (clone $q)->sum(DB::raw($amountExpr)),
            ];
        }, ['count' => 0, 'amount' => 0]);
    }

    private function mpOrdersInPeriod(int $daysBack): int
    {
        return (int) $this->mpSales($daysBack)['count'];
    }

    /** Kolom tanggal yang tersedia di marketplace_orders. */
    private function mpDateCol(): string
    {
        if (Schema::hasColumn('marketplace_orders', 'order_date')) {
            return 'order_date';
        }
        if (Schema::hasColumn('marketplace_orders', 'ordered_at')) {
            return 'ordered_at';
        }
        return 'created_at';
    }

    /** Pesanan belum dikirim (perlu diproses + siap kirim). */
    private function mpUnshippedCount(): int
    {
        return $this->mpStatusCount(['PROCESSED', 'READY_TO_SHIP', 'TO_CONFIRM_RECEIVE']);
    }

    private function mpStatusCount(array $statuses, ?int $daysBack = null): int
    {
        return (int) $this->safe(function () use ($statuses, $daysBack) {
            if (!Schema::hasTable('marketplace_orders') || !Schema::hasColumn('marketplace_orders', 'order_status')) {
                return 0;
            }
            $q = DB::table('marketplace_orders')->whereIn('order_status', $statuses);
            if ($daysBack !== null) {
                $q->whereBetween($this->mpDateCol(), [
                    Carbon::today()->subDays($daysBack)->startOfDay(),
                    Carbon::today()->endOfDay(),
                ]);
            }
            return $q->count();
        });
    }

    /** Pesanan yang perlu diperbaiki datanya (belum termapping / tanpa AWB dll). */
    private function mpNeedFixCount(): int
    {
        return (int) $this->safe(function () {
            if (!Schema::hasTable('marketplace_orders')) {
                return 0;
            }
            $q = DB::table('marketplace_orders')
                ->whereIn('order_status', ['READY_TO_SHIP', 'PROCESSED']);
            if (Schema::hasColumn('marketplace_orders', 'shipping_awb_no')) {
                $q->where(fn ($w) => $w->whereNull('shipping_awb_no')->orWhere('shipping_awb_no', ''));
            }
            return $q->count();
        });
    }

    private function mpUnshippedList(int $limit): array
    {
        return (array) $this->safe(function () use ($limit) {
            if (!Schema::hasTable('marketplace_orders')) {
                return [];
            }
            return DB::table('marketplace_orders')
                ->whereIn('order_status', ['PROCESSED', 'READY_TO_SHIP', 'TO_CONFIRM_RECEIVE'])
                ->orderByDesc($this->mpDateCol())
                ->limit($limit)
                ->get([
                    'external_invoice_no', 'external_order_id', 'buyer_name',
                    'shipping_city', 'shipping_courier_code', 'order_status',
                    DB::raw('COALESCE(total_amount, total_paid_customer, 0) as amount'),
                ])
                ->map(fn ($r) => (array) $r)
                ->all();
        }, []);
    }

    /** Total qty di gudang tertentu (berdasarkan kode gudang). */
    private function whQty(array $codes): float
    {
        return (float) $this->safe(function () use ($codes) {
            if (!Schema::hasTable('inventory_stocks') || !Schema::hasTable('warehouses')) {
                return 0;
            }
            return DB::table('inventory_stocks as s')
                ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
                ->whereIn('w.code', $codes)
                ->sum('s.qty');
        });
    }

    /** Daftar item terbanyak di sebuah gudang. */
    private function whItemsList(string $code, int $limit): array
    {
        return (array) $this->safe(function () use ($code, $limit) {
            if (!Schema::hasTable('inventory_stocks') || !Schema::hasTable('items')) {
                return [];
            }
            return DB::table('inventory_stocks as s')
                ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
                ->join('items as i', 'i.id', '=', 's.item_id')
                ->where('w.code', $code)
                ->where('s.qty', '>', 0)
                ->orderByDesc('s.qty')
                ->limit($limit)
                ->get(['i.code as item_code', 'i.name as item_name', 's.qty'])
                ->map(fn ($r) => (array) $r)
                ->all();
        }, []);
    }

    /** Jumlah item yang stoknya habis (<=0) untuk tipe tertentu. */
    private function stockOutCount(string $type): int
    {
        return (int) $this->safe(function () use ($type) {
            if (!Schema::hasTable('items') || !Schema::hasTable('inventory_stocks')) {
                return 0;
            }
            // Total qty per item di semua gudang non-reject, lalu hitung yang <= 0.
            $sub = DB::table('inventory_stocks as s')
                ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
                ->where('w.code', 'not like', 'REJ%')
                ->where('w.code', '!=', 'REJECT')
                ->groupBy('s.item_id')
                ->select('s.item_id', DB::raw('SUM(s.qty) as total_qty'));

            return DB::table('items as i')
                ->leftJoinSub($sub, 't', 't.item_id', '=', 'i.id')
                ->where('i.type', $type)
                ->where('i.active', 1)
                ->where(fn ($w) => $w->whereNull('t.total_qty')->orWhere('t.total_qty', '<=', 0))
                ->count();
        });
    }

    /** Daftar item stok kritis (habis / menipis). */
    private function stockCriticalList(int $limit, ?string $type = null): array
    {
        return (array) $this->safe(function () use ($limit, $type) {
            if (!Schema::hasTable('items') || !Schema::hasTable('inventory_stocks')) {
                return [];
            }
            $sub = DB::table('inventory_stocks as s')
                ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
                ->where('w.code', 'not like', 'REJ%')
                ->where('w.code', '!=', 'REJECT')
                ->groupBy('s.item_id')
                ->select('s.item_id', DB::raw('SUM(s.qty) as total_qty'));

            $q = DB::table('items as i')
                ->leftJoinSub($sub, 't', 't.item_id', '=', 'i.id')
                ->where('i.active', 1)
                ->where(fn ($w) => $w->whereNull('t.total_qty')->orWhere('t.total_qty', '<=', 0));

            if ($type) {
                $q->where('i.type', $type);
            }

            return $q->orderBy('i.name')
                ->limit($limit)
                ->get([
                    'i.code as item_code', 'i.name as item_name', 'i.type',
                    DB::raw('COALESCE(t.total_qty, 0) as qty'),
                ])
                ->map(fn ($r) => (array) $r)
                ->all();
        }, []);
    }

    private function poCount(array $statuses, string $col): int
    {
        return (int) $this->safe(function () use ($statuses, $col) {
            if (!Schema::hasTable('purchase_orders') || !Schema::hasColumn('purchase_orders', $col)) {
                return 0;
            }
            return DB::table('purchase_orders')
                ->whereIn($col, $statuses)
                ->when(Schema::hasColumn('purchase_orders', 'status'),
                    fn ($q) => $q->where('status', '!=', 'cancelled'))
                ->count();
        });
    }

    private function stockRequestOpen(): int
    {
        return (int) $this->safe(function () {
            if (!Schema::hasTable('stock_requests') || !Schema::hasColumn('stock_requests', 'status')) {
                return 0;
            }
            return DB::table('stock_requests')
                ->whereNotIn('status', ['completed', 'cancelled', 'void'])
                ->count();
        });
    }

    // =====================================================================
    //  UTIL
    // =====================================================================

    private function roleBucket($user): string
    {
        if (!$user) {
            return 'generic';
        }
        if (method_exists($user, 'isOwner') && $user->isOwner()) {
            return 'owner';
        }
        $role = strtolower((string) ($user->role ?? ''));
        return in_array($role, ['owner', 'admin', 'operating'], true) ? $role : 'generic';
    }

    private function greeting(): string
    {
        $h = (int) Carbon::now()->format('H');
        return match (true) {
            $h < 11 => 'Selamat pagi',
            $h < 15 => 'Selamat siang',
            $h < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
    }
}
