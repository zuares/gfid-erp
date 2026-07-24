@extends('layouts.app')

@section('title', 'Analisis Iklan Shopee')

@push('head')
<style>
/* ─────────────────────────────────────────────────────────────────────────────
   LAYOUT
───────────────────────────────────────────────────────────────────────────── */
.page-wrap {
    max-width: 1080px;
    margin-inline: auto;
    padding-bottom: 3rem;
}

/* ─────────────────────────────────────────────────────────────────────────────
   KPI STRIP  (6 kolom → 3 di mobile)
───────────────────────────────────────────────────────────────────────────── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: .6rem;
    margin-bottom: .85rem;
}
.kpi-cell {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: .72rem .82rem;
    min-width: 0;
    position: relative;
    overflow: hidden;
}
.kpi-label {
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--muted);
    font-weight: 800;
    margin-bottom: .18rem;
}
.kpi-value {
    font-size: .95rem;
    font-weight: 850;
    line-height: 1.2;
    color: var(--text);
}
.kpi-sub {
    font-size: .72rem;
    margin-top: .15rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.kpi-sub.text-success { color: var(--success) !important; }
.kpi-sub.text-danger { color: var(--danger) !important; }

/* ─────────────────────────────────────────────────────────────────────────────
   FILTER CARD
───────────────────────────────────────────────────────────────────────────── */
.card-filter {
    background: var(--card);
    border-radius: 14px;
    border: 1px solid var(--line);
    padding: .8rem .9rem;
    margin-bottom: .85rem;
}
.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    align-items: end;
}

/* ─────────────────────────────────────────────────────────────────────────────
   TABS
───────────────────────────────────────────────────────────────────────────── */
.gf-tabs {
    display: flex;
    gap: 1rem;
    border-bottom: 1px solid var(--line);
    margin-bottom: 1rem;
}
.gf-tab {
    background: transparent;
    border: none;
    padding: .5rem 0;
    font-size: .85rem;
    font-weight: 600;
    color: var(--muted);
    border-bottom: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
}
.gf-tab:hover {
    color: var(--text);
}
.gf-tab.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}
.tab-pane {
    display: none;
}
.tab-pane.active {
    display: block;
}

/* ─────────────────────────────────────────────────────────────────────────────
   TABLE CARD
───────────────────────────────────────────────────────────────────────────── */
.card-table {
    background: var(--card);
    border-radius: 14px;
    border: 1px solid var(--line);
    overflow: hidden;
    margin-bottom: 1rem;
}
.table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
}
.table thead th {
    background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
    border-bottom: 1px solid var(--line);
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--muted);
    white-space: nowrap;
    padding: .52rem .65rem;
    text-align: left;
}
.table tbody td {
    vertical-align: middle;
    font-size: .83rem;
    padding: .58rem .65rem;
    border-bottom: 1px solid var(--line);
    color: var(--text);
}
.table tbody tr:last-child td { border-bottom: none; }
.table tbody tr:hover { background: rgba(59,130,246,.04); }

/* ─────────────────────────────────────────────────────────────────────────────
   BADGES
───────────────────────────────────────────────────────────────────────────── */
.badge-status {
    border-radius: 999px;
    font-size: .7rem;
    padding: .1rem .6rem;
    border: 1px solid transparent;
    white-space: nowrap;
    display: inline-block;
    font-weight: 600;
}
.badge-ongoing { background:rgba(22,163,74,.12);   color:#15803d; border-color:rgba(22,163,74,.6); }
.badge-ended { background:rgba(148,163,184,.12); color:#64748b; border-color:rgba(148,163,184,.5); }
.badge-warning { background:rgba(234,179,8,.10);   color:#a16207; border-color:rgba(234,179,8,.55); }
.badge-danger { background:rgba(220,38,38,.08);   color:#b91c1c; border-color:rgba(220,38,38,.6); }

body[data-theme="dark"] .badge-ongoing { color: var(--success); border-color: var(--success); }
body[data-theme="dark"] .badge-ended { color: var(--muted); border-color: var(--muted); }
body[data-theme="dark"] .badge-warning { color: #facc15; border-color: #facc15; }
body[data-theme="dark"] .badge-danger { color: var(--danger); border-color: var(--danger); }

/* Typography */
.mono {
    font-variant-numeric: tabular-nums;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
}

@media (max-width: 767.98px) {
    .page-wrap   { padding-inline: .75rem; }
    .card-filter { padding: .75rem .8rem; }
    .kpi-grid    { grid-template-columns: repeat(3, minmax(0,1fr)); gap:.5rem; }
    .filter-row  { flex-direction: column; align-items: stretch; }
    .filter-row > div { width: 100%; }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Custom Tab Logic
    const tabBtns = document.querySelectorAll('.gf-tab');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active classes
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));

            // Add active class to clicked tab and corresponding pane
            btn.classList.add('active');
            const targetId = btn.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');

            // Trigger window resize to fix chart canvas scaling
            window.dispatchEvent(new Event('resize'));
        });
    });
});
</script>
@endpush

@section('content')
<div class="page-wrap py-3">

    {{-- ── HEADER ──────────────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color: var(--text);">Analisis Iklan Shopee</h1>
            <p class="mb-0" style="font-size: .8rem; color: var(--muted);">Pantau performa dan ROAS iklan toko Anda.</p>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-3 p-2" style="font-size: .85rem;"><i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success shadow-sm border-0 rounded-3 mb-3 p-2" style="font-size: .85rem;"><i class="fas fa-check-circle me-1"></i> {{ session('success') }}</div>
    @endif

    {{-- ── FILTER ──────────────────────────────────────────────────────────── --}}
    <div class="card-filter">
        <form method="GET" action="{{ route('marketplace.ads.dashboard') }}" class="filter-row">
            <div style="flex: 1; min-width: 200px;">
                <label class="kpi-label">Toko Shopee</label>
                <select name="store_id" class="form-select form-select-sm" style="background-color: var(--bg); color: var(--text); border-color: var(--line);" onchange="this.form.submit()">
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label class="kpi-label">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" style="background-color: var(--bg); color: var(--text); border-color: var(--line);" value="{{ $dateFrom }}">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label class="kpi-label">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control form-control-sm" style="background-color: var(--bg); color: var(--text); border-color: var(--line);" value="{{ $dateTo }}">
            </div>
            <div>
                <button type="submit" class="btn btn-primary btn-sm h-100 px-3">
                    <i class="fas fa-filter"></i> Terapkan
                </button>
            </div>
        </form>
    </div>

    @if(empty($storeId))
        <div class="card-filter" style="text-align: center; color: var(--muted); padding: 2rem;">
            Pilih toko terlebih dahulu untuk melihat analisis.
        </div>
    @else
        {{-- ── TABS NAV ──────────────────────────────────────────────────────────── --}}
        <div class="gf-tabs">
            <button class="gf-tab active" data-target="tab-overview"><i class="fas fa-chart-pie me-1"></i> Ringkasan</button>
            <button class="gf-tab" data-target="tab-campaigns"><i class="fas fa-bullhorn me-1"></i> Kampanye</button>
            <button class="gf-tab" data-target="tab-settings"><i class="fas fa-cog me-1"></i> Pengaturan & Riwayat</button>
        </div>

        {{-- ── TABS CONTENT ──────────────────────────────────────────────────────────── --}}
        
        <!-- TAB: OVERVIEW -->
        <div class="tab-pane active" id="tab-overview">
            <!-- KPI STRIP -->
            <div class="kpi-grid">
                @php
                    $metrics = [
                        ['title' => 'Biaya (Spend)', 'key' => 'spend', 'prefix' => 'Rp ', 'suffix' => ''],
                        ['title' => 'GMV Iklan', 'key' => 'gmv', 'prefix' => 'Rp ', 'suffix' => ''],
                        ['title' => 'ROAS', 'key' => 'roas', 'prefix' => '', 'suffix' => 'x'],
                        ['title' => 'Pesanan', 'key' => 'orders', 'prefix' => '', 'suffix' => ''],
                        ['title' => 'Impression', 'key' => 'impressions', 'prefix' => '', 'suffix' => ''],
                        ['title' => 'Klik', 'key' => 'clicks', 'prefix' => '', 'suffix' => ''],
                    ];
                @endphp
                @foreach($metrics as $m)
                    @php
                        $val = $kpi['current']->{$m['key']} ?? 0;
                        if($m['key'] === 'roas' && isset($kpi['current']->spend) && $kpi['current']->spend > 0) {
                            $val = round($kpi['current']->gmv / $kpi['current']->spend, 2);
                        }
                        $change = $kpi['changes'][$m['key']] ?? 0;
                        $isUp = $change >= 0;
                        $color = $isUp ? 'text-success' : 'text-danger';
                    @endphp
                    <div class="kpi-cell">
                        <div class="kpi-label">{{ $m['title'] }}</div>
                        <div class="kpi-value mono">{{ $m['prefix'] }}{{ is_float($val) ? number_format($val, 2, ',', '.') : number_format($val, 0, ',', '.') }}{{ $m['suffix'] }}</div>
                        <div class="kpi-sub {{ $color }} fw-bold">
                            <i class="fas fa-arrow-{{ $isUp ? 'up' : 'down' }}"></i> {{ abs($change) }}% vs lalu
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- CHARTS -->
            <div class="row g-3 mb-3">
                <div class="col-lg-8">
                    <div class="card-table p-3 h-100">
                        <div class="kpi-label mb-3">Tren Performa Harian</div>
                        <div style="height: 280px;">
                            <canvas id="dailyChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card-table p-3 h-100">
                        <div class="kpi-label mb-3">Distribusi Klik Per Jam</div>
                        <div style="height: 280px;">
                            <canvas id="hourlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: CAMPAIGNS -->
        <div class="tab-pane" id="tab-campaigns">
            <div class="card-table">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kampanye</th>
                                <th>Tipe</th>
                                <th>Status</th>
                                <th class="text-end">Biaya</th>
                                <th class="text-end">GMV</th>
                                <th class="text-center">ROAS</th>
                                <th class="text-end">ACOS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($campaigns as $camp)
                                <tr>
                                    <td>
                                        <div class="fw-bold" style="color: var(--text);">{{ $camp->campaign_name }}</div>
                                        <div class="mono" style="font-size: .7rem; color: var(--muted);">ID: {{ $camp->channel_campaign_id }}</div>
                                    </td>
                                    <td><span style="font-size: .75rem; color: var(--muted);">{{ $camp->campaign_type }}</span></td>
                                    <td>
                                        <span class="badge-status {{ $camp->status == 'ONGOING' ? 'badge-ongoing' : 'badge-ended' }}">
                                            {{ $camp->status }}
                                        </span>
                                    </td>
                                    <td class="text-end mono fw-bold">Rp {{ number_format($camp->spend, 0, ',', '.') }}</td>
                                    <td class="text-end mono fw-bold" style="color: var(--success);">Rp {{ number_format($camp->gmv, 0, ',', '.') }}</td>
                                    <td class="text-center mono fw-bold">
                                        {{ $camp->spend > 0 ? number_format($camp->gmv / $camp->spend, 2) : 0 }}x
                                    </td>
                                    <td class="text-end mono">{{ $camp->gmv > 0 ? number_format(($camp->spend / $camp->gmv) * 100, 2) : 0 }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4" style="color: var(--muted);">
                                        Belum ada data kampanye tersimpan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: SETTINGS & LOGS -->
        <div class="tab-pane" id="tab-settings">
            <div class="row g-3 mb-3">
                <!-- Sync Form -->
                <div class="col-lg-4">
                    <div class="card-filter h-100 m-0">
                        <div class="kpi-label mb-3"><i class="fas fa-sync-alt me-1"></i> Pusat Sinkronisasi</div>
                        <p style="font-size: .8rem; color: var(--muted);">Tarik data iklan terbaru secara manual dari API Shopee.</p>
                        
                        <form action="{{ route('marketplace.ads.sync') }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <label class="kpi-label" style="font-size: .65rem;">Toko Target</label>
                                <select name="store_id" class="form-select form-select-sm" style="background-color: var(--bg); color: var(--text); border-color: var(--line);" required>
                                    @foreach($stores as $s)
                                        <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="kpi-label" style="font-size: .65rem;">Mode Sinkronisasi</label>
                                <select name="sync_type" class="form-select form-select-sm" style="background-color: var(--bg); color: var(--text); border-color: var(--line);">
                                    <option value="incremental">Incremental (Hari ini & Kemarin)</option>
                                    <option value="hourly">Hourly (Khusus Jam Ini)</option>
                                    <option value="backfill">Historical Backfill (6 Bulan)</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                                Jalankan Sync
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Sync Logs -->
                <div class="col-lg-8">
                    <div class="card-table h-100 m-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Waktu Mulai</th>
                                        <th>Tipe</th>
                                        <th>Status</th>
                                        <th>Detail / Error</th>
                                        <th class="text-end">Req/Rows</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(isset($syncRuns) && $syncRuns->count() > 0)
                                        @foreach($syncRuns as $run)
                                            @php
                                                $statusClass = 'badge-warning';
                                                if ($run->status == 'success') $statusClass = 'badge-ongoing';
                                                elseif ($run->status == 'error') $statusClass = 'badge-danger';
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="fw-bold" style="color: var(--text);">{{ $run->created_at->format('d/m/Y') }}</div>
                                                    <div class="mono" style="font-size: .7rem; color: var(--muted);">{{ $run->created_at->format('H:i:s') }}</div>
                                                </td>
                                                <td><span style="font-size: .75rem; color: var(--muted);">{{ $run->sync_type }}</span></td>
                                                <td><span class="badge-status {{ $statusClass }}">{{ strtoupper($run->status) }}</span></td>
                                                <td>
                                                    <div style="font-size: .75rem; color: var(--muted); max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $run->error_message }}">
                                                        {{ $run->error_message ?? '-' }}
                                                    </div>
                                                </td>
                                                <td class="text-end mono" style="font-size: .75rem;">
                                                    {{ $run->total_requests }} / <span class="text-success">{{ $run->total_updated }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="5" class="text-center py-4" style="color: var(--muted);">Belum ada riwayat sinkronisasi.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
@if(!empty($dailyChartData))
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const dailyData = @json($dailyChartData);
    const hourlyData = @json($heatmapData ?? []);
    
    // Theme colors matching CSS vars
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#e5e7eb' : '#111827';
    const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
    
    // Default Chart Config
    Chart.defaults.color = textColor;
    Chart.defaults.font.family = 'ui-monospace, SFMono-Regular, Menlo, Consolas';

    // Daily Line Chart
    if(document.getElementById("dailyChart")) {
        const ctxDaily = document.getElementById("dailyChart").getContext('2d');
        
        new Chart(ctxDaily, {
            type: 'line',
            data: {
                labels: dailyData.map(d => d.date),
                datasets: [
                    {
                        label: 'Spend (Rp)',
                        data: dailyData.map(d => parseFloat(d.spend)),
                        borderColor: '#2563eb', // accent
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 2,
                        yAxisID: 'y'
                    },
                    {
                        label: 'GMV (Rp)',
                        data: dailyData.map(d => parseFloat(d.gmv)),
                        borderColor: '#16a34a', // success
                        backgroundColor: 'transparent',
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 2,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 6 } } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { 
                        type: 'linear', position: 'left', beginAtZero: true, 
                        grid: { color: gridColor },
                        ticks: { font: { size: 10 } }
                    },
                    y1: { 
                        type: 'linear', position: 'right', beginAtZero: true, 
                        grid: { drawOnChartArea: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    // Hourly Bar Chart
    if(document.getElementById("hourlyChart")) {
        const ctxHourly = document.getElementById("hourlyChart").getContext('2d');
        new Chart(ctxHourly, {
            type: 'bar',
            data: {
                labels: hourlyData.map(d => d.performance_hour + ':00'),
                datasets: [
                    {
                        label: 'Clicks',
                        data: hourlyData.map(d => parseInt(d.clicks)),
                        backgroundColor: '#60a5fa',
                        borderRadius: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { 
                        beginAtZero: true, 
                        grid: { color: gridColor },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }
});
</script>
@endif
@endpush
