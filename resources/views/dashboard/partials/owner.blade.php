@php
    use Illuminate\Support\Facades\Route;
    $r = fn (string $name, array $p = []) => Route::has($name) ? route($name, $p) : null;
    $qty = fn ($n) => number_format((float) $n, 0, ',', '.');
@endphp

@php
    $marketplaceKpi = $d['marketplace_kpi'] ?? [];
    $marketplaceTodayKpi = $d['marketplace_today_kpi'] ?? [];
    $marketplaceYesterdayKpi = $d['marketplace_yesterday_kpi'] ?? [];
    $marketplaceSameDatePreviousKpi = $d['marketplace_same_date_previous_kpi'] ?? [];
    $marketplaceMonthKpi = $d['marketplace_month_kpi'] ?? $marketplaceKpi;
    $marketplaceMonthPreviousKpi = $d['marketplace_month_previous_kpi'] ?? [];
    $periodToday = \Carbon\Carbon::now();
    $previousMonthDate = $periodToday->copy()->subMonthNoOverflow();
    $previousPeriodLabel = $previousMonthDate->copy()->startOfMonth()->format('d') . '–' . $previousMonthDate->format('d M');
    $monthComparison = function (string $key, callable $format) use ($marketplaceMonthKpi, $marketplaceMonthPreviousKpi, $previousPeriodLabel): array {
        $comparison = $marketplaceMonthKpi['comparisons'][$key] ?? [];

        return [
            'label' => 'Bln lalu · ' . $previousPeriodLabel,
            'value' => $format($marketplaceMonthPreviousKpi[$key] ?? 0),
            'tone' => 'muted',
            'delta' => $comparison['value'] ?? '—',
            'delta_tone' => $comparison['tone'] ?? 'muted',
        ];
    };
    $money = fn ($value) => rupiah($value);
    $count = fn ($value) => number_format((int) $value, 0, ',', '.');
    $percent = fn ($value) => number_format((float) $value, 1, ',', '.') . '%';
    $multiple = fn ($value) => $value === null ? '—' : number_format((float) $value, 2, ',', '.') . 'x';
    $todayComparison = function (string $key, callable $format) use ($marketplaceTodayKpi, $marketplaceYesterdayKpi, $marketplaceSameDatePreviousKpi, $periodToday): array {
        $comparison = $marketplaceTodayKpi['comparisons'][$key] ?? [];
        $sameDate = $periodToday->copy()->subMonthNoOverflow();

        return [
            [
                'label' => 'Kemarin · ' . $periodToday->copy()->subDay()->format('d M'),
                'value' => $format($marketplaceYesterdayKpi[$key] ?? 0),
                'tone' => 'muted',
                'delta' => $comparison['yesterday']['value'] ?? '—',
                'delta_tone' => $comparison['yesterday']['tone'] ?? 'muted',
            ],
            [
                'label' => 'Tgl sama · ' . $sameDate->format('d M'),
                'value' => $format($marketplaceSameDatePreviousKpi[$key] ?? 0),
                'tone' => 'muted',
                'delta' => $comparison['same_date']['value'] ?? '—',
                'delta_tone' => $comparison['same_date']['tone'] ?? 'muted',
            ],
        ];
    };
    $marketplaceAnalyticsUrl = $r('marketplace.analytics', [
        'date_from' => now()->startOfMonth()->toDateString(),
        'date_to' => now()->toDateString(),
        'compare_mode' => 'prev_month',
    ]);
    $marketplaceExceptions = (int) ($marketplaceMonthKpi['exception_total'] ?? 0);
    $marketplaceRoas = $marketplaceMonthKpi['roas_net'] ?? null;
@endphp
<section class="dash-priority-stack" aria-labelledby="dash-priority-title">
    <h2 id="dash-priority-title" class="visually-hidden">Prioritas kerja hari ini</h2>
    <div class="dash-panels">
        @include('dashboard.partials._list_repeat_orders', ['title' => 'Repeat order pesanan hari ini', 'rows' => $d['repeat_orders'] ?? [], 'link' => $r('marketplace.orders')])
    </div>

    <div class="dash-panels">
        @include('dashboard.partials._list_order_items', ['title' => 'Permintaan belum dikirim per item internal', 'rows' => $d['list_todo_items'] ?? [], 'link' => $r('marketplace.orders')])
    </div>
</section>

<div class="dash-sec"><i class="bi bi-graph-up-arrow"></i> Penjualan (bulan berjalan)</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'Order hari ini', 'icon' => 'bi-receipt', 'color' => 'green',
        'value' => number_format((int) ($marketplaceTodayKpi['order_total'] ?? 0), 0, ',', '.'),
        'sub' => 'Order setelah batal/refund/return',
        'comparisons' => $todayComparison('order_total', $count),
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'AOV hari ini', 'icon' => 'bi-calculator', 'color' => 'violet',
        'value' => rupiah($marketplaceTodayKpi['net_aov'] ?? 0),
        'sub' => 'Net Sales ÷ order hari ini',
        'comparisons' => $todayComparison('net_aov', $money),
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Net Sales hari ini', 'icon' => 'bi-lightning-charge', 'color' => 'blue',
        'value' => rupiah($marketplaceTodayKpi['net_order_revenue'] ?? 0),
        'sub' => 'Setelah potongan penjual',
        'comparisons' => $todayComparison('net_order_revenue', $money),
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Net Sales setelah potongan penjual', 'icon' => 'bi-cart-check', 'color' => 'blue',
        'featured' => true,
        'value' => rupiah($marketplaceMonthKpi['net_order_revenue'] ?? 0),
        'sub' => 'Setelah batal, refund, return, dan voucher seller',
        'comparisons' => [
            $monthComparison('net_order_revenue', $money),
        ],
        'url' => $marketplaceAnalyticsUrl, 'cta' => 'Buka analytics',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'GMV bulan ini', 'icon' => 'bi-bag-check', 'color' => 'green',
        'value' => rupiah($marketplaceMonthKpi['gmv'] ?? 0),
        'sub' => 'Nilai order eligible sebelum biaya marketplace',
        'comparisons' => [$monthComparison('gmv', $money)],
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Order bulan ini', 'icon' => 'bi-receipt', 'color' => 'blue',
        'value' => number_format((int) ($marketplaceMonthKpi['order_total'] ?? 0), 0, ',', '.'),
        'sub' => 'Order setelah batal/refund/return',
        'comparisons' => [$monthComparison('order_total', $count)],
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Produk terjual bulan ini', 'icon' => 'bi-box-seam', 'color' => 'violet',
        'value' => number_format((int) ($marketplaceMonthKpi['product_qty'] ?? 0), 0, ',', '.'),
        'sub' => 'Qty dari order eligible',
        'comparisons' => [$monthComparison('product_qty', $count)],
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'AOV net bulan ini', 'icon' => 'bi-calculator', 'color' => 'blue',
        'value' => rupiah($marketplaceMonthKpi['net_aov'] ?? 0),
        'sub' => 'Net Sales ÷ order bulan ini',
        'comparisons' => [$monthComparison('net_aov', $money)],
    ])
</div>

<div class="dash-sec"><i class="bi bi-speedometer2"></i> KPI Marketplace (bulan berjalan)</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'Estimasi profit hari ini', 'icon' => 'bi-piggy-bank', 'color' => (float) ($marketplaceTodayKpi['estimated_profit'] ?? 0) >= 0 ? 'green' : 'red',
        'value' => rupiah($marketplaceTodayKpi['estimated_profit'] ?? 0),
        'sub' => 'Setelah fee, HPP, refund, dan iklan hari ini',
        'comparisons' => $todayComparison('estimated_profit', $money),
        'url' => $marketplaceAnalyticsUrl, 'cta' => 'Lihat detail',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Estimasi profit bulan ini', 'icon' => 'bi-piggy-bank', 'color' => (float) ($marketplaceMonthKpi['estimated_profit'] ?? 0) >= 0 ? 'green' : 'red',
        'value' => rupiah($marketplaceMonthKpi['estimated_profit'] ?? 0),
        'sub' => 'Setelah fee, HPP, refund, dan iklan',
        'comparisons' => [
            $monthComparison('estimated_profit', $money),
        ],
        'url' => $marketplaceAnalyticsUrl, 'cta' => 'Lihat profit',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Margin estimasi', 'icon' => 'bi-percent', 'color' => (float) ($marketplaceMonthKpi['estimated_profit_margin'] ?? 0) >= 10 ? 'green' : 'amber',
        'value' => number_format((float) ($marketplaceMonthKpi['estimated_profit_margin'] ?? 0), 1, ',', '.') . '%',
        'sub' => 'Profit estimasi ÷ Net Sales bulan ini',
        'comparisons' => [$monthComparison('estimated_profit_margin', $percent)],
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Payout cair', 'icon' => 'bi-wallet2', 'color' => 'green',
        'value' => rupiah($marketplaceMonthKpi['cash_payout'] ?? 0),
        'sub' => 'Penghasilan settlement yang sudah cair',
        'comparisons' => [$monthComparison('cash_payout', $money)],
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Biaya marketplace', 'icon' => 'bi-receipt-cutoff', 'color' => 'amber',
        'value' => rupiah($marketplaceMonthKpi['cash_marketplace_fees'] ?? 0),
        'sub' => 'Fee aktual dari settlement',
        'comparisons' => [$monthComparison('cash_marketplace_fees', $money)],
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Biaya iklan incl. PPN', 'icon' => 'bi-megaphone', 'color' => 'amber',
        'value' => rupiah($marketplaceMonthKpi['ad_cost'] ?? 0),
        'sub' => 'Spend iklan marketplace bulan ini',
        'comparisons' => [$monthComparison('ad_cost', $money)],
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'ROAS net', 'icon' => 'bi-graph-up-arrow', 'color' => $marketplaceRoas !== null && $marketplaceRoas >= 3 ? 'green' : 'amber',
        'value' => $marketplaceRoas === null ? '—' : number_format((float) $marketplaceRoas, 2, ',', '.') . 'x', 'small' => true,
        'sub' => 'Penjualan net ÷ biaya iklan incl. PPN',
        'comparisons' => [$monthComparison('roas_net', $multiple)],
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Affiliate / AMS', 'icon' => 'bi-person-check', 'color' => 'violet',
        'value' => rupiah($marketplaceMonthKpi['cash_affiliate_fees'] ?? $marketplaceMonthKpi['affiliate_fees_actual'] ?? 0),
        'sub' => 'Biaya affiliate & activity bulan ini',
        'comparisons' => [$monthComparison('cash_affiliate_fees', $money)],
    ])
</div>

<div class="dash-sec"><i class="bi bi-truck"></i> Operasional Order</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'Batal + refund', 'icon' => 'bi-exclamation-triangle', 'color' => $marketplaceExceptions > 0 ? 'amber' : 'green',
        'value' => number_format($marketplaceExceptions, 0, ',', '.'), 'small' => true,
        'sub' => number_format((int) ($marketplaceMonthKpi['cancelled_count'] ?? 0), 0, ',', '.') . ' dibatalkan · ' . number_format((int) ($marketplaceMonthKpi['return_refund_order_count'] ?? 0), 0, ',', '.') . ' refund/return bulan ini',
        'comparisons' => [$monthComparison('exception_total', $count)],
        'url' => $marketplaceAnalyticsUrl, 'cta' => 'Audit exception',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Perlu diproses', 'icon' => 'bi-hourglass-split', 'color' => $d['orders_todo'] > 0 ? 'amber' : '',
        'value' => number_format($d['orders_todo'], 0, ',', '.'),
        'sub' => 'Pesanan belum dikirim',
        'url' => $r('marketplace.orders'), 'cta' => 'Proses sekarang',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Sudah dikirim (7 hari)', 'icon' => 'bi-truck', 'color' => '',
        'value' => number_format($d['orders_shipped_7'], 0, ',', '.'),
        'sub' => 'Paket keluar sepekan',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Belum settlement', 'icon' => 'bi-hourglass-bottom', 'color' => (int) ($marketplaceMonthKpi['cash_unsettled_order_count'] ?? 0) > 0 ? 'amber' : 'green',
        'value' => number_format((int) ($marketplaceMonthKpi['cash_unsettled_order_count'] ?? 0), 0, ',', '.'), 'small' => true,
        'sub' => 'Order bulan ini belum cair atau menunggu konfirmasi',
        'comparisons' => [$monthComparison('cash_unsettled_order_count', $count)],
        'url' => $marketplaceAnalyticsUrl, 'cta' => 'Lihat detail',
    ])
</div>

{{-- ================= BARANG JADI & PRODUKSI ================= --}}
<div class="dash-sec"><i class="bi bi-box-seam"></i> Barang Jadi & Produksi</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'Barang Jadi Siap', 'icon' => 'bi-bag-check', 'color' => 'green',
        'value' => $qty($d['fg_ready']) . ' pcs', 'small' => true,
        'sub' => 'Stok siap jual di gudang',
        'url' => $r('inventory.stocks.items'), 'cta' => 'Lihat stok',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Sedang diproduksi', 'icon' => 'bi-gear-wide-connected', 'color' => 'violet',
        'value' => $qty($d['wip_total']) . ' pcs', 'small' => true,
        'sub' => 'Cutting + jahit + finishing + packing',
        'url' => $r('production.dashboard'), 'cta' => 'Pantau produksi',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Barang Jadi Habis', 'icon' => 'bi-exclamation-octagon', 'color' => $d['stock_out_fg'] > 0 ? 'red' : '',
        'value' => number_format($d['stock_out_fg'], 0, ',', '.'),
        'sub' => 'Model yang stoknya 0',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Bahan Baku Habis', 'icon' => 'bi-droplet-half', 'color' => $d['stock_out_rm'] > 0 ? 'amber' : '',
        'value' => number_format($d['stock_out_rm'], 0, ',', '.'),
        'sub' => 'Bahan yang perlu dibeli',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Saran Pengadaan', 'icon' => 'bi-cart-plus', 'color' => $d['procurement_suggestion_items'] > 0 ? 'amber' : '',
        'value' => $qty($d['procurement_suggestion_qty']) . ' pcs', 'small' => true,
        'sub' => $d['procurement_suggestion_items'] . ' SKU · forecast 60 hari · ' . rupiah($d['procurement_suggestion_value']),
        'url' => $r('inventory.intelligence', ['tab' => 'procurement']), 'cta' => 'Lihat saran',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Saran Produksi', 'icon' => 'bi-gear-wide-connected', 'color' => $d['production_suggestion_items'] > 0 ? 'violet' : '',
        'value' => $qty($d['production_suggestion_qty']) . ' pcs', 'small' => true,
        'sub' => $d['production_suggestion_items'] . ' SKU · forecast 30 hari · ' . rupiah($d['production_suggestion_value']),
        'url' => $r('inventory.intelligence', ['tab' => 'forecast']), 'cta' => 'Lihat saran',
    ])
</div>

{{-- ================= PEMBELIAN & MASALAH ================= --}}
<div class="dash-sec"><i class="bi bi-wallet2"></i> Pembelian & Perlu Perhatian</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'PO belum diterima', 'icon' => 'bi-box-arrow-in-down', 'color' => $d['po_unreceived'] > 0 ? 'amber' : '',
        'value' => number_format($d['po_unreceived'], 0, ',', '.'),
        'sub' => 'Barang beli belum datang',
        'url' => $r('purchasing.purchase_orders.index'), 'cta' => 'Cek PO',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'PO belum dibayar', 'icon' => 'bi-cash-stack', 'color' => $d['po_unpaid'] > 0 ? 'amber' : '',
        'value' => number_format($d['po_unpaid'], 0, ',', '.'),
        'sub' => 'Tagihan supplier',
        'url' => $r('purchasing.purchase_orders.index'), 'cta' => 'Cek tagihan',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Barang reject', 'icon' => 'bi-x-octagon', 'color' => $d['reject_total'] > 0 ? 'red' : '',
        'value' => $qty($d['reject_total']) . ' pcs', 'small' => true,
        'sub' => 'Total barang reject/cacat',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Atur akses user', 'icon' => 'bi-shield-lock', 'color' => 'violet',
        'value' => 'Kelola', 'small' => true,
        'sub' => 'Sidebar & izin per user',
        'url' => $r('owner.access-control.index'), 'cta' => 'Buka pengaturan',
    ])
</div>

{{-- ================= ANALITIK IKLAN ================= --}}
<div class="dash-sec"><i class="bi bi-megaphone"></i> Analitik Iklan Marketplace (7 Hari Terakhir)</div>
<div class="dash-panels dash-ads-grid mb-4">
    
    {{-- PIE CHART KAMPANYE --}}
    <div class="dpanel dash-chart-card">
        <div class="dash-chart-card-title">
            Proporsi Biaya per Kampanye
        </div>
        <div class="dash-chart-card-subtitle">
            Top 5 kampanye dengan pengeluaran terbesar.
        </div>
        <div class="dash-chart-wrap dash-chart-wrap-pie">
            @if(empty($d['ads_campaigns']) || count($d['ads_campaigns']) === 0)
                <div class="dash-chart-empty">Belum ada data kampanye aktif.</div>
            @else
                <canvas id="execPieChart" role="img" aria-label="Proporsi biaya per kampanye"></canvas>
            @endif
        </div>
    </div>

    {{-- HEATMAP JAM TAYANG --}}
    <div class="dpanel dash-chart-card">
        <div class="dash-chart-card-title">
            Heatmap Jam Tayang Efektif (Golden Hours)
        </div>
        <div class="dash-chart-card-subtitle">
            Menampilkan performa metrik (GMV / Biaya) per jam tayang dari data mesin. Semakin pekat warnanya, semakin tinggi performanya.
        </div>
        <div class="dash-chart-wrap dash-chart-wrap-heatmap">
            <canvas id="execHeatmapChart" role="img" aria-label="Heatmap jam tayang efektif"></canvas>
        </div>
    </div>

</div>

{{-- ================= DAFTAR ================= --}}
<div class="dash-panels">
    @include('dashboard.partials._list_stock', ['title' => 'Stok kritis (perlu segera)', 'rows' => $d['list_stock'], 'link' => $r('inventory.stocks.items')])
</div>

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const rawHourly = @json($d['ads_hourly'] ?? []);
    const rawCampaigns = @json($d['ads_campaigns'] ?? []);
    
    // Theme configs
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.04)';
    const tooltipBg = isDark ? 'rgba(15,23,42,0.95)' : 'rgba(255,255,255,0.95)';
    const tooltipBorder = isDark ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.08)';
    const tooltipText = isDark ? '#f8fafc' : '#0f172a';
    
    Chart.defaults.color = textColor;
    Chart.defaults.font.family = 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace';

    // 1. HEATMAP CHART
    if (rawHourly.length > 0) {
        const hourlyData = [];
        for (let i = 0; i < 24; i++) {
            let found = rawHourly.find(d => parseInt(d.performance_hour) === i);
            hourlyData.push(found ? found : { performance_hour: i, clicks: 0, orders: 0, spend: 0, gmv: 0 });
        }
        
        const labels = hourlyData.map(d => String(d.performance_hour).padStart(2, '0') + ':00');
        const spendData = hourlyData.map(d => parseFloat(d.spend));
        const gmvData = hourlyData.map(d => parseFloat(d.gmv));
        
        const ctxHeatmap = document.getElementById('execHeatmapChart').getContext('2d');
        new Chart(ctxHeatmap, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'GMV (Pendapatan)',
                        data: gmvData,
                        backgroundColor: '#10b981', // Green
                        borderRadius: 4,
                        order: 1
                    },
                    {
                        label: 'Biaya (Spend)',
                        data: spendData,
                        type: 'line',
                        borderColor: '#ef4444', // Red
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        order: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } },
                    tooltip: {
                        backgroundColor: tooltipBg, titleColor: tooltipText, bodyColor: tooltipText,
                        borderColor: tooltipBorder, borderWidth: 1, padding: 10,
                        callbacks: {
                            label: function(ctx) {
                                let val = ctx.raw;
                                let str = val;
                                if(val >= 1000000) str = (val/1000000).toFixed(1) + ' Jt';
                                else if(val >= 1000) str = (val/1000).toFixed(1) + ' Rb';
                                return ctx.dataset.label + ': Rp ' + str;
                            }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: {
                        callback: function(v) {
                            if(v >= 1000000) return (v/1000000).toFixed(0) + 'Jt';
                            if(v >= 1000) return (v/1000).toFixed(0) + 'Rb';
                            return v;
                        }
                    }},
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 2. PIE CHART
    if (rawCampaigns.length > 0) {
        const pieLabels = rawCampaigns.map(c => c.campaign_name.length > 20 ? c.campaign_name.substring(0,20)+'...' : c.campaign_name);
        const pieData = rawCampaigns.map(c => parseFloat(c.total_spend));
        
        // Brand colors
        const colors = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#64748b'];
        
        const ctxPie = document.getElementById('execPieChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieData,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: isDark ? '#1e293b' : '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { boxWidth: 10, font: { size: 10 } }
                    },
                    tooltip: {
                        backgroundColor: tooltipBg, titleColor: tooltipText, bodyColor: tooltipText,
                        borderColor: tooltipBorder, borderWidth: 1, padding: 10,
                        callbacks: {
                            label: function(ctx) {
                                let val = ctx.raw;
                                let str = val;
                                if(val >= 1000000) str = (val/1000000).toFixed(1) + ' Jt';
                                else if(val >= 1000) str = (val/1000).toFixed(1) + ' Rb';
                                return ' Biaya: Rp ' + str;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }
});
</script>
@endpush
