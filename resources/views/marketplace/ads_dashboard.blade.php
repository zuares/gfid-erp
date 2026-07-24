@extends('layouts.app')

@section('content')
<style>
    /* Premium UI/UX Customizations */
    .ads-dashboard {
        font-family: 'Inter', 'Roboto', sans-serif;
        background: #f8f9fc;
    }
    
    .premium-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .premium-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    }
    
    .gradient-header {
        background: linear-gradient(135deg, #f68048 0%, #ee4d2d 100%);
        color: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(238, 77, 45, 0.2);
    }

    .kpi-title {
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #858796;
    }

    .kpi-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: #2c3e50;
    }
    
    .kpi-icon {
        font-size: 2rem;
        opacity: 0.1;
        position: absolute;
        right: 20px;
        top: 20px;
        color: #ee4d2d;
    }

    /* Custom Modern Tabs */
    .nav-pills-custom .nav-link {
        color: #6c757d;
        background: #fff;
        border-radius: 50rem;
        padding: 0.6rem 1.5rem;
        font-weight: 600;
        border: 1px solid #e3e6f0;
        margin-right: 0.5rem;
        transition: all 0.3s ease;
    }
    
    .nav-pills-custom .nav-link:hover {
        background: #f1f3f9;
    }
    
    .nav-pills-custom .nav-link.active {
        color: #fff;
        background: #ee4d2d;
        border-color: #ee4d2d;
        box-shadow: 0 4px 10px rgba(238, 77, 45, 0.3);
    }

    /* Table Improvements */
    .table-modern {
        border-collapse: separate;
        border-spacing: 0 10px;
    }
    
    .table-modern thead th {
        border: none;
        background: transparent;
        color: #a0a5b1;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 1px;
    }
    
    .table-modern tbody tr {
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        border-radius: 8px;
        transition: transform 0.2s ease;
    }
    
    .table-modern tbody tr:hover {
        transform: scale(1.01);
    }
    
    .table-modern tbody td {
        border: none;
        padding: 1rem;
        vertical-align: middle;
    }
    
    .table-modern tbody td:first-child {
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
    }
    
    .table-modern tbody td:last-child {
        border-top-right-radius: 8px;
        border-bottom-right-radius: 8px;
    }

    .status-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 50rem;
        font-weight: 600;
        font-size: 0.75rem;
    }
    
    .status-ongoing { background: rgba(28, 200, 138, 0.1); color: #1cc88a; }
    .status-ended { background: rgba(133, 135, 150, 0.1); color: #858796; }
    .status-rate-limited { background: rgba(246, 194, 62, 0.1); color: #f6c23e; }
    .status-error { background: rgba(231, 74, 59, 0.1); color: #e74a3b; }
    .status-success { background: rgba(28, 200, 138, 0.1); color: #1cc88a; }
</style>

<div class="container-fluid py-4 ads-dashboard">
    
    <!-- Title & Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="gradient-header d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="h3 mb-1 text-white font-weight-bold">Analisis Iklan Shopee</h1>
                    <p class="mb-0 text-white-50">Pantau performa, ROAS, dan kendalikan data iklan toko Anda.</p>
                </div>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4"><i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success shadow-sm border-0 rounded-3 mb-4"><i class="fas fa-check-circle me-2"></i> {{ session('success') }}</div>
    @endif

    <div class="premium-card p-4 mb-4">
        <form method="GET" action="{{ route('marketplace.ads.dashboard') }}" class="row align-items-end g-3">
            <div class="col-md-4">
                <label class="form-label text-muted fw-bold small">Toko Shopee</label>
                <select name="store_id" class="form-select border-0 bg-light" style="border-radius: 8px;" onchange="this.form.submit()">
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted fw-bold small">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control border-0 bg-light" style="border-radius: 8px;" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted fw-bold small">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control border-0 bg-light" style="border-radius: 8px;" value="{{ $dateTo }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn text-white w-100" style="background: #ee4d2d; border-radius: 8px;">
                    <i class="fas fa-filter me-1"></i> Terapkan
                </button>
            </div>
        </form>
    </div>

    @if(empty($storeId))
        <div class="alert alert-info border-0 shadow-sm rounded-3">Pilih toko terlebih dahulu untuk melihat analisis.</div>
    @else
        <!-- Custom Tabs -->
        <ul class="nav nav-pills nav-pills-custom mb-4" id="adsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="pill" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">
                    <i class="fas fa-chart-pie me-1"></i> Ringkasan
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="campaigns-tab" data-bs-toggle="pill" data-bs-target="#campaigns" type="button" role="tab" aria-controls="campaigns" aria-selected="false">
                    <i class="fas fa-bullhorn me-1"></i> Kampanye
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="settings-tab" data-bs-toggle="pill" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="false">
                    <i class="fas fa-cog me-1"></i> Pengaturan & Riwayat
                </button>
            </li>
        </ul>

        <div class="tab-content" id="adsTabContent">
            
            <!-- TAB: OVERVIEW -->
            <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                <!-- KPI Cards -->
                <div class="row mb-2">
                    @php
                        $metrics = [
                            ['title' => 'Biaya Iklan (Spend)', 'key' => 'spend', 'prefix' => 'Rp ', 'suffix' => '', 'icon' => 'fa-wallet'],
                            ['title' => 'GMV Iklan', 'key' => 'gmv', 'prefix' => 'Rp ', 'suffix' => '', 'icon' => 'fa-shopping-bag'],
                            ['title' => 'ROAS', 'key' => 'roas', 'prefix' => '', 'suffix' => 'x', 'icon' => 'fa-rocket'],
                            ['title' => 'Pesanan', 'key' => 'orders', 'prefix' => '', 'suffix' => '', 'icon' => 'fa-box-open'],
                            ['title' => 'Impression', 'key' => 'impressions', 'prefix' => '', 'suffix' => '', 'icon' => 'fa-eye'],
                            ['title' => 'Klik', 'key' => 'clicks', 'prefix' => '', 'suffix' => '', 'icon' => 'fa-mouse-pointer'],
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
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="premium-card h-100 p-4 position-relative overflow-hidden">
                                <i class="fas {{ $m['icon'] }} kpi-icon"></i>
                                <div class="kpi-title mb-1">{{ $m['title'] }}</div>
                                <div class="kpi-value mb-2">
                                    {{ $m['prefix'] }}{{ is_float($val) ? number_format($val, 2, ',', '.') : number_format($val, 0, ',', '.') }}{{ $m['suffix'] }}
                                </div>
                                <div class="text-xs {{ $color }} fw-bold">
                                    <i class="fas fa-arrow-{{ $isUp ? 'up' : 'down' }}"></i> {{ abs($change) }}% vs lalu
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Charts -->
                <div class="row">
                    <div class="col-xl-8 col-lg-7 mb-4">
                        <div class="premium-card h-100 p-4">
                            <h6 class="font-weight-bold text-dark mb-4">Tren Performa Harian</h6>
                            <div class="chart-area" style="height: 320px;">
                                <canvas id="dailyChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-5 mb-4">
                        <div class="premium-card h-100 p-4">
                            <h6 class="font-weight-bold text-dark mb-4">Distribusi Per Jam</h6>
                            <div class="chart-pie" style="height: 320px;">
                                <canvas id="hourlyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: CAMPAIGNS -->
            <div class="tab-pane fade" id="campaigns" role="tabpanel" aria-labelledby="campaigns-tab">
                <div class="premium-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="font-weight-bold text-dark m-0">Daftar Kampanye Aktif & Historis</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th>Kampanye</th>
                                    <th>Tipe</th>
                                    <th>Status</th>
                                    <th>Biaya (Spend)</th>
                                    <th>GMV</th>
                                    <th>ROAS</th>
                                    <th>Target ROAS</th>
                                    <th>ACOS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($campaigns as $camp)
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-dark">{{ $camp->campaign_name }}</span>
                                            <div class="text-muted small">ID: {{ $camp->channel_campaign_id }}</div>
                                        </td>
                                        <td>{{ $camp->campaign_type }}</td>
                                        <td>
                                            <span class="status-badge {{ $camp->status == 'ONGOING' ? 'status-ongoing' : 'status-ended' }}">
                                                {{ $camp->status }}
                                            </span>
                                        </td>
                                        <td class="fw-bold">Rp {{ number_format($camp->spend, 0, ',', '.') }}</td>
                                        <td class="fw-bold text-success">Rp {{ number_format($camp->gmv, 0, ',', '.') }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-bolt text-warning me-1"></i>
                                                {{ $camp->spend > 0 ? number_format($camp->gmv / $camp->spend, 2) : 0 }}x
                                            </div>
                                        </td>
                                        <td>{{ $camp->target_roas ?? '-' }}</td>
                                        <td>{{ $camp->gmv > 0 ? number_format(($camp->spend / $camp->gmv) * 100, 2) : 0 }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <div class="mb-3"><i class="fas fa-folder-open fa-3x text-light"></i></div>
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
            <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
                <div class="row">
                    <!-- Sync Trigger Card -->
                    <div class="col-lg-4 mb-4">
                        <div class="premium-card p-4 h-100">
                            <h6 class="font-weight-bold text-dark mb-4"><i class="fas fa-sync-alt me-2 text-primary"></i> Pusat Sinkronisasi</h6>
                            <p class="text-muted small mb-4">Tarik data iklan terbaru dari Shopee secara manual atau jalankan backfill historis.</p>
                            
                            <form action="{{ route('marketplace.ads.sync') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">Toko Target</label>
                                    <select name="store_id" class="form-select bg-light border-0" style="border-radius: 8px;" required>
                                        @foreach($stores as $s)
                                            <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-muted">Mode Sinkronisasi</label>
                                    <select name="sync_type" class="form-select bg-light border-0" style="border-radius: 8px;">
                                        <option value="incremental">Incremental (Hari ini & Kemarin)</option>
                                        <option value="hourly">Hourly (Khusus Jam Ini)</option>
                                        <option value="backfill">Historical Backfill (6 Bulan Terakhir)</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 shadow-sm" style="border-radius: 8px; font-weight: 600;">
                                    Jalankan Sync Sekarang
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Sync Logs Card -->
                    <div class="col-lg-8 mb-4">
                        <div class="premium-card p-4 h-100">
                            <h6 class="font-weight-bold text-dark mb-4"><i class="fas fa-history me-2 text-primary"></i> Riwayat Sinkronisasi Terakhir</h6>
                            <div class="table-responsive">
                                <table class="table table-modern w-100">
                                    <thead>
                                        <tr>
                                            <th>Waktu Mulai</th>
                                            <th>Tipe</th>
                                            <th>Status</th>
                                            <th>Detail / Error</th>
                                            <th>Statistik</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(isset($syncRuns) && $syncRuns->count() > 0)
                                            @foreach($syncRuns as $run)
                                                @php
                                                    $statusClass = 'status-warning';
                                                    if ($run->status == 'success') $statusClass = 'status-success';
                                                    elseif ($run->status == 'error') $statusClass = 'status-error';
                                                    elseif ($run->status == 'rate_limited') $statusClass = 'status-rate-limited';
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold">{{ $run->created_at->format('d/m/Y') }}</div>
                                                        <div class="small text-muted">{{ $run->created_at->format('H:i:s') }}</div>
                                                    </td>
                                                    <td><span class="badge bg-light text-dark border">{{ $run->sync_type }}</span></td>
                                                    <td><span class="status-badge {{ $statusClass }}">{{ strtoupper($run->status) }}</span></td>
                                                    <td>
                                                        <div class="small text-muted" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $run->error_message }}">
                                                            {{ $run->error_message ?? '-' }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="small">
                                                            <i class="fas fa-cloud-download-alt text-primary me-1"></i> {{ $run->total_requests }} req <br>
                                                            <i class="fas fa-database text-success me-1"></i> {{ $run->total_updated }} rows
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">Belum ada riwayat sinkronisasi untuk toko ini.</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
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
    
    let dailyChartInstance = null;
    let hourlyChartInstance = null;

    function renderCharts() {
        // Daily Line Chart
        if(document.getElementById("dailyChart") && !dailyChartInstance) {
            const ctxDaily = document.getElementById("dailyChart").getContext('2d');
            
            // Create gradient for Spend
            let gradientSpend = ctxDaily.createLinearGradient(0, 0, 0, 300);
            gradientSpend.addColorStop(0, 'rgba(238, 77, 45, 0.4)');
            gradientSpend.addColorStop(1, 'rgba(238, 77, 45, 0.0)');

            dailyChartInstance = new Chart(ctxDaily, {
                type: 'line',
                data: {
                    labels: dailyData.map(d => d.date),
                    datasets: [
                        {
                            label: 'Spend (Rp)',
                            data: dailyData.map(d => parseFloat(d.spend)),
                            borderColor: '#ee4d2d',
                            backgroundColor: gradientSpend,
                            fill: true,
                            tension: 0.4, // Smooth curves
                            borderWidth: 2,
                            pointBackgroundColor: '#ee4d2d',
                            yAxisID: 'y'
                        },
                        {
                            label: 'GMV (Rp)',
                            data: dailyData.map(d => parseFloat(d.gmv)),
                            borderColor: '#1cc88a',
                            backgroundColor: 'transparent',
                            tension: 0.4,
                            borderWidth: 2,
                            pointBackgroundColor: '#1cc88a',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { type: 'linear', position: 'left', beginAtZero: true, border: { dash: [4, 4] } },
                        y1: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } }
                    }
                }
            });
        }

        // Hourly Bar Chart
        if(document.getElementById("hourlyChart") && !hourlyChartInstance) {
            const ctxHourly = document.getElementById("hourlyChart").getContext('2d');
            hourlyChartInstance = new Chart(ctxHourly, {
                type: 'bar',
                data: {
                    labels: hourlyData.map(d => d.performance_hour + ':00'),
                    datasets: [
                        {
                            label: 'Clicks',
                            data: hourlyData.map(d => parseInt(d.clicks)),
                            backgroundColor: '#4e73df',
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, border: { dash: [4, 4] } }
                    }
                }
            });
        }
    }

    // Render initially
    renderCharts();

    // Re-render charts when switching tabs because canvas size might collapse when hidden
    const tabEls = document.querySelectorAll('button[data-bs-toggle="pill"]');
    tabEls.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (event) {
            if (event.target.id === 'overview-tab') {
                if (dailyChartInstance) dailyChartInstance.resize();
                if (hourlyChartInstance) hourlyChartInstance.resize();
            }
        });
    });
});
</script>
@endif
@endpush
