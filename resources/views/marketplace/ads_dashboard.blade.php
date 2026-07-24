@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Analisis Iklan Shopee</h1>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#syncModal">
                <i class="fas fa-sync fa-sm text-white-50"></i> Sync Center
            </button>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Filter Header -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('marketplace.ads.dashboard') }}" class="row align-items-end g-3">
                <div class="col-md-4">
                    <label class="form-label">Toko Shopee</label>
                    <select name="store_id" class="form-select" onchange="this.form.submit()">
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    @if(empty($storeId))
        <div class="alert alert-info">Pilih toko terlebih dahulu.</div>
    @else
        <!-- KPI Cards -->
        <div class="row mb-4">
            @php
                $metrics = [
                    ['title' => 'Biaya Iklan (Spend)', 'key' => 'spend', 'prefix' => 'Rp ', 'suffix' => ''],
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
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        {{ $m['title'] }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $m['prefix'] }}{{ is_float($val) ? number_format($val, 2, ',', '.') : number_format($val, 0, ',', '.') }}{{ $m['suffix'] }}
                                    </div>
                                    <div class="text-xs mt-1 {{ $color }}">
                                        <i class="fas fa-arrow-{{ $isUp ? 'up' : 'down' }}"></i> {{ abs($change) }}% vs lalu
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Charts -->
        <div class="row">
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Tren Performa Harian</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area" style="height: 300px;">
                            <canvas id="dailyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Distribusi Per Jam (Hourly)</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-pie pt-4" style="height: 300px;">
                            <canvas id="hourlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Campaign Table -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Campaign</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th>Campaign Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Spend</th>
                                <th>GMV</th>
                                <th>ROAS</th>
                                <th>Target ROAS</th>
                                <th>ACOS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($campaigns as $camp)
                                <tr>
                                    <td>{{ $camp->campaign_name }} <br><small class="text-muted">{{ $camp->channel_campaign_id }}</small></td>
                                    <td>{{ $camp->campaign_type }}</td>
                                    <td><span class="badge bg-{{ $camp->status == 'ONGOING' ? 'success' : 'secondary' }}">{{ $camp->status }}</span></td>
                                    <td>Rp {{ number_format($camp->spend, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($camp->gmv, 0, ',', '.') }}</td>
                                    <td>{{ $camp->spend > 0 ? number_format($camp->gmv / $camp->spend, 2) : 0 }}x</td>
                                    <td>{{ $camp->target_roas ?? '-' }}</td>
                                    <td>{{ $camp->gmv > 0 ? number_format(($camp->spend / $camp->gmv) * 100, 2) : 0 }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Belum ada campaign tersimpan. Silakan lakukan Sync.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Sync Modal -->
<div class="modal fade" id="syncModal" tabindex="-1" aria-labelledby="syncModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="syncModalLabel">Shopee Ads Sync Center</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="{{ route('marketplace.ads.sync') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label>Pilih Toko</label>
                <select name="store_id" class="form-select" required>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label>Jenis Sync</label>
                <select name="sync_type" class="form-select">
                    <option value="incremental">Incremental (Hari ini & Kemarin)</option>
                    <option value="hourly">Hourly (Khusus Jam Ini)</option>
                    <option value="backfill">Historical Backfill (6 Bulan Terakhir)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Jalankan Sync di Background</button>
        </form>

        <h6 class="mt-4 fw-bold">Riwayat Sync Terakhir</h6>
        <div class="table-responsive">
            <table class="table table-sm mt-2">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Requests</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($syncRuns))
                        @foreach($syncRuns as $run)
                            <tr>
                                <td>{{ $run->created_at->format('d/m/y H:i') }}</td>
                                <td>{{ $run->sync_type }}</td>
                                <td><span class="badge bg-{{ $run->status == 'success' ? 'success' : ($run->status == 'error' ? 'danger' : 'warning') }}">{{ $run->status }}</span></td>
                                <td>{{ $run->total_requests }}</td>
                                <td>{{ $run->total_updated }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
@if(!empty($dailyChartData))
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const dailyData = @json($dailyChartData);
    const hourlyData = @json($heatmapData ?? []);

    // Daily Line Chart
    if(document.getElementById("dailyChart")) {
        new Chart(document.getElementById("dailyChart"), {
            type: 'line',
            data: {
                labels: dailyData.map(d => d.date),
                datasets: [
                    {
                        label: 'Spend (Rp)',
                        data: dailyData.map(d => parseFloat(d.spend)),
                        borderColor: '#e74a3b',
                        backgroundColor: 'rgba(231, 74, 59, 0.1)',
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'GMV (Rp)',
                        data: dailyData.map(d => parseFloat(d.gmv)),
                        borderColor: '#1cc88a',
                        backgroundColor: 'transparent',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { type: 'linear', position: 'left', beginAtZero: true },
                    y1: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } }
                }
            }
        });
    }

    // Hourly Bar Chart
    if(document.getElementById("hourlyChart")) {
        new Chart(document.getElementById("hourlyChart"), {
            type: 'bar',
            data: {
                labels: hourlyData.map(d => d.performance_hour + ':00'),
                datasets: [
                    {
                        label: 'Clicks',
                        data: hourlyData.map(d => parseInt(d.clicks)),
                        backgroundColor: '#4e73df'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});
</script>
@endif
@endpush
