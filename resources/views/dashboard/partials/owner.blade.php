@php
    use Illuminate\Support\Facades\Route;
    $r = fn (string $name, array $p = []) => Route::has($name) ? route($name, $p) : null;
    $qty = fn ($n) => number_format((float) $n, 0, ',', '.');
@endphp

{{-- ================= PENJUALAN ================= --}}
<div class="dash-sec"><i class="bi bi-graph-up-arrow"></i> Penjualan</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'Penjualan hari ini', 'icon' => 'bi-cart-check', 'color' => 'green',
        'value' => rupiah($d['sales_today_amount']),
        'sub' => $d['sales_today_count'] . ' pesanan masuk hari ini',
        'url' => $r('marketplace.orders'), 'cta' => 'Lihat pesanan',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Penjualan 7 hari', 'icon' => 'bi-calendar-week', 'color' => 'blue',
        'value' => rupiah($d['sales_7_amount']),
        'sub' => $d['sales_7_count'] . ' pesanan dalam sepekan',
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
</div>

{{-- ================= BARANG & PRODUKSI ================= --}}
<div class="dash-sec"><i class="bi bi-box-seam"></i> Barang & Produksi</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'Barang jadi siap', 'icon' => 'bi-bag-check', 'color' => 'green',
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
        'label' => 'Barang jadi habis', 'icon' => 'bi-exclamation-octagon', 'color' => $d['stock_out_fg'] > 0 ? 'red' : '',
        'value' => number_format($d['stock_out_fg'], 0, ',', '.'),
        'sub' => 'Model yang stoknya 0',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Bahan baku habis', 'icon' => 'bi-droplet-half', 'color' => $d['stock_out_rm'] > 0 ? 'amber' : '',
        'value' => number_format($d['stock_out_rm'], 0, ',', '.'),
        'sub' => 'Bahan yang perlu dibeli',
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
<div class="dash-panels mb-4" style="grid-template-columns: 2fr 1fr; gap: 1rem;">
    
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
