@php
    use Illuminate\Support\Facades\Route;
    $r = fn (string $name, array $p = []) => Route::has($name) ? route($name, $p) : null;
    $qty = fn ($n) => number_format((float) $n, 0, ',', '.');
@endphp

@php
    $marketplaceKpi = $d['marketplace_kpi'] ?? [];
    $marketplaceAnalyticsUrl = $r('marketplace.analytics', [
        'date_from' => now()->toDateString(),
        'date_to' => now()->toDateString(),
        'compare_mode' => 'prev_period',
    ]);
    $marketplaceExceptions = (int) ($marketplaceKpi['cancelled_count'] ?? 0) + (int) ($marketplaceKpi['return_refund_order_count'] ?? 0);
    $marketplaceRoas = $marketplaceKpi['roas_net'] ?? null;
@endphp
<div class="dash-sec"><i class="bi bi-graph-up-arrow"></i> Penjualan (hari ini)</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'Penjualan net marketplace', 'icon' => 'bi-cart-check', 'color' => 'blue',
        'value' => rupiah($marketplaceKpi['net_order_revenue'] ?? 0),
        'sub' => 'Setelah batal, refund, return, dan voucher seller',
        'comparisons' => [
            ['label' => 'vs minggu lalu', 'value' => $marketplaceKpi['net_revenue_vs_week']['value'] ?? '—', 'tone' => $marketplaceKpi['net_revenue_vs_week']['tone'] ?? 'muted'],
            ['label' => 'vs bulan lalu', 'value' => $marketplaceKpi['net_revenue_vs_month']['value'] ?? '—', 'tone' => $marketplaceKpi['net_revenue_vs_month']['tone'] ?? 'muted'],
        ],
        'url' => $marketplaceAnalyticsUrl, 'cta' => 'Buka analytics',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'GMV eligible', 'icon' => 'bi-bag-check', 'color' => 'green',
        'value' => rupiah($marketplaceKpi['gmv'] ?? 0),
        'sub' => 'Nilai order eligible sebelum biaya marketplace',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Order eligible', 'icon' => 'bi-receipt', 'color' => 'blue',
        'value' => number_format((int) ($marketplaceKpi['order_total'] ?? 0), 0, ',', '.'),
        'sub' => 'Order setelah batal/refund/return',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Produk terjual', 'icon' => 'bi-box-seam', 'color' => 'violet',
        'value' => number_format((int) ($marketplaceKpi['product_qty'] ?? 0), 0, ',', '.'),
        'sub' => 'Qty dari order eligible',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'AOV net', 'icon' => 'bi-calculator', 'color' => 'blue',
        'value' => rupiah($marketplaceKpi['net_aov'] ?? 0),
        'sub' => 'Penjualan net ÷ order eligible',
    ])
</div>

<div class="dash-sec"><i class="bi bi-speedometer2"></i> KPI Marketplace (hari ini)</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'Estimasi profit', 'icon' => 'bi-piggy-bank', 'color' => (float) ($marketplaceKpi['estimated_profit'] ?? 0) >= 0 ? 'green' : 'red',
        'value' => rupiah($marketplaceKpi['estimated_profit'] ?? 0),
        'sub' => 'Setelah fee, HPP, refund, dan iklan',
        'comparisons' => [
            ['label' => 'vs minggu lalu', 'value' => $marketplaceKpi['profit_vs_week']['value'] ?? '—', 'tone' => $marketplaceKpi['profit_vs_week']['tone'] ?? 'muted'],
            ['label' => 'vs bulan lalu', 'value' => $marketplaceKpi['profit_vs_month']['value'] ?? '—', 'tone' => $marketplaceKpi['profit_vs_month']['tone'] ?? 'muted'],
        ],
        'url' => $marketplaceAnalyticsUrl, 'cta' => 'Lihat profit',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Margin estimasi', 'icon' => 'bi-percent', 'color' => (float) ($marketplaceKpi['estimated_profit_margin'] ?? 0) >= 10 ? 'green' : 'amber',
        'value' => number_format((float) ($marketplaceKpi['estimated_profit_margin'] ?? 0), 1, ',', '.') . '%',
        'sub' => 'Profit estimasi ÷ penjualan net',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Payout cair', 'icon' => 'bi-wallet2', 'color' => 'green',
        'value' => rupiah($marketplaceKpi['cash_payout'] ?? 0),
        'sub' => 'Penghasilan settlement yang sudah cair',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Biaya marketplace', 'icon' => 'bi-receipt-cutoff', 'color' => 'amber',
        'value' => rupiah($marketplaceKpi['cash_marketplace_fees'] ?? 0),
        'sub' => 'Fee aktual dari settlement',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Biaya iklan incl. PPN', 'icon' => 'bi-megaphone', 'color' => 'amber',
        'value' => rupiah($marketplaceKpi['ad_cost'] ?? 0),
        'sub' => 'Spend iklan marketplace hari ini',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'ROAS net', 'icon' => 'bi-graph-up-arrow', 'color' => $marketplaceRoas !== null && $marketplaceRoas >= 3 ? 'green' : 'amber',
        'value' => $marketplaceRoas === null ? '—' : number_format((float) $marketplaceRoas, 2, ',', '.') . 'x', 'small' => true,
        'sub' => 'Penjualan net ÷ biaya iklan incl. PPN',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Affiliate / AMS', 'icon' => 'bi-person-check', 'color' => 'violet',
        'value' => rupiah($marketplaceKpi['cash_affiliate_fees'] ?? $marketplaceKpi['affiliate_fees_actual'] ?? 0),
        'sub' => 'Biaya affiliate & activity aktual',
    ])
</div>

<div class="dash-sec"><i class="bi bi-truck"></i> Operasional Order</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'Batal + refund', 'icon' => 'bi-exclamation-triangle', 'color' => $marketplaceExceptions > 0 ? 'amber' : 'green',
        'value' => number_format($marketplaceExceptions, 0, ',', '.'), 'small' => true,
        'sub' => number_format((int) ($marketplaceKpi['cancelled_count'] ?? 0), 0, ',', '.') . ' dibatalkan · ' . number_format((int) ($marketplaceKpi['return_refund_order_count'] ?? 0), 0, ',', '.') . ' refund/return',
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
        'label' => 'Belum settlement', 'icon' => 'bi-hourglass-bottom', 'color' => (int) ($marketplaceKpi['cash_unsettled_order_count'] ?? 0) > 0 ? 'amber' : 'green',
        'value' => number_format((int) ($marketplaceKpi['cash_unsettled_order_count'] ?? 0), 0, ',', '.'), 'small' => true,
        'sub' => 'Order belum cair atau menunggu konfirmasi',
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
<div class="dash-sec mt-4"><i class="bi bi-megaphone"></i> Analitik Iklan Marketplace (7 Hari Terakhir)</div>
<div class="dash-panels mb-4" style="grid-template-columns: 1fr 2fr; gap: 1rem;">
    
    {{-- PIE CHART KAMPANYE --}}
    <div class="dpanel p-3">
        <div style="font-size: 0.85rem; font-weight: 650; color: var(--text); margin-bottom: 0.5rem;">
            Proporsi Biaya per Kampanye
        </div>
        <div style="font-size: 0.72rem; color: var(--dsh-muted); margin-bottom: 1rem;">
            Top 5 kampanye dengan pengeluaran terbesar.
        </div>
        <div style="position: relative; height: 250px; display: flex; justify-content: center; align-items: center;">
            @if(empty($d['ads_campaigns']) || count($d['ads_campaigns']) === 0)
                <div style="color: var(--dsh-muted); font-size: 0.8rem; text-align: center;">Belum ada data kampanye aktif.</div>
            @else
                <canvas id="execPieChart"></canvas>
            @endif
        </div>
    </div>

    {{-- HEATMAP JAM TAYANG --}}
    <div class="dpanel p-3">
        <div style="font-size: 0.85rem; font-weight: 650; color: var(--text); margin-bottom: 0.5rem;">
            Heatmap Jam Tayang Efektif (Golden Hours)
        </div>
        <div style="font-size: 0.72rem; color: var(--dsh-muted); margin-bottom: 1rem;">
            Menampilkan performa metrik (GMV / Biaya) per jam tayang dari data mesin. Semakin pekat warnanya, semakin tinggi performanya.
        </div>
        <div style="position: relative; height: 250px;">
            <canvas id="execHeatmapChart"></canvas>
        </div>
    </div>

</div>

{{-- ================= DAFTAR ================= --}}
<div class="dash-panels">
    @include('dashboard.partials._list_orders', ['title' => 'Pesanan belum dikirim', 'rows' => $d['list_todo'], 'link' => $r('marketplace.orders')])
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
