@extends('layouts.app')
@section('title', 'Visitor Analytics')

@push('head')
<style>
.tab-pill { border: 1.5px solid #e2e8f0; border-radius: 999px; padding: .25rem .7rem; font-size: .72rem; font-weight: 700; text-decoration: none; color: #64748b; background: #fff; white-space: nowrap; }
.tab-pill.active, .tab-pill:hover { background: #0f172a; color: #fff; border-color: #0f172a; }
.stat-card { background:#fff; border:1.5px solid #e8ecf0; border-radius:16px; padding:1.1rem 1.25rem; }
.stat-label { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; margin-bottom:.25rem; }
.stat-value { font-size:1.6rem; font-weight:900; color:#0f172a; line-height:1.1; }
.stat-sub { font-size:.72rem; color:#64748b; margin-top:.2rem; }
.crm-table th { font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; border-bottom:1.5px solid #e8ecf0; padding:.5rem .75rem; background:#f8fafc; white-space:nowrap; }
.crm-table td { font-size:.8rem; vertical-align:middle; padding:.55rem .75rem; border-bottom:1px solid #f1f5f9; }
.crm-table tr:last-child td { border-bottom:0; }
.section-card { background:#fff; border:1.5px solid #e8ecf0; border-radius:16px; overflow:hidden; }
.section-title { font-size:.78rem; font-weight:800; color:#0f172a; padding:.75rem 1rem; border-bottom:1.5px solid #f1f5f9; background:#f8fafc; }
.bar-wrap { background:#f1f5f9; border-radius:4px; height:6px; overflow:hidden; min-width:60px; }
.bar-fill { height:6px; border-radius:4px; background:#6366f1; }
.event-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:4px; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-black mb-0" style="font-size:1.05rem;">Visitor Analytics</h5>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:2px;">Perilaku pengunjung — durasi, klik, halaman yang dikunjungi</div>
        </div>
        <a href="{{ route('admin.crm.dashboard') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.75rem;">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
    </div>

    {{-- Period filter --}}
    <div class="d-flex gap-2 flex-wrap align-items-center mb-3">
        @foreach([7=>'7 Hari', 30=>'30 Hari', 60=>'60 Hari', 90=>'90 Hari'] as $d => $label)
        <a href="{{ route('admin.crm.visitors', ['days' => $d]) }}"
           class="tab-pill {{ $days == $d ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    {{-- Stat Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Unique Visitors</div>
                <div class="stat-value">{{ number_format($uniqueVisitors) }}</div>
                <div class="stat-sub">{{ $days }} hari terakhir</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Return Rate</div>
                <div class="stat-value">{{ $returnRate }}%</div>
                <div class="stat-sub">{{ number_format($returningVisitors) }} returning visitors</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Avg Time on Site</div>
                <div class="stat-value">{{ $avgDurationFormatted }}</div>
                <div class="stat-sub">
                    @if($avgSeconds > 0)
                        dari semua halaman yang ditracking
                    @else
                        <span style="color:#e2e8f0;">Belum ada data duration</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Bounce Rate</div>
                <div class="stat-value {{ $bounceRate > 70 ? 'text-danger' : ($bounceRate > 50 ? 'text-warning' : 'text-success') }}">
                    {{ $bounceRate }}%
                </div>
                <div class="stat-sub">{{ $bouncedCount }} dari {{ $totalSessions }} sesi bounce</div>
            </div>
        </div>
    </div>

    {{-- Daily Visitors Chart --}}
    <div class="section-card mb-3">
        <div class="section-title">
            <i class="bi bi-graph-up me-1"></i> Unique Visitors per Hari
        </div>
        <div style="padding:1rem;">
            <canvas id="visitorsChart" height="80"></canvas>
        </div>
    </div>

    {{-- Most Visited Pages + Click Events --}}
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="section-card h-100">
                <div class="section-title"><i class="bi bi-eye me-1"></i> Halaman Paling Sering Dikunjungi</div>
                @if($pageViews->isEmpty())
                <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.82rem;">Belum ada data page view</div>
                @else
                <div style="overflow-x:auto;">
                    <table class="table mb-0 crm-table">
                        <thead>
                            <tr>
                                <th>Halaman</th>
                                <th style="text-align:right;">Views</th>
                                <th style="text-align:right;">Visitors</th>
                                <th style="min-width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                        @php $maxPv = $pageViews->max('count'); @endphp
                        @foreach($pageViews as $pv)
                        <tr>
                            <td>
                                @if($pv['slug'])
                                <span style="font-size:.72rem;background:#e0e7ff;color:#3730a3;border-radius:4px;padding:.1rem .35rem;font-weight:700;">produk</span>
                                {{ $pv['slug'] }}
                                @else
                                {{ str_replace('storefront.', '', $pv['label']) }}
                                @endif
                            </td>
                            <td style="text-align:right;font-weight:800;">{{ $pv['count'] }}</td>
                            <td style="text-align:right;color:#64748b;">{{ $pv['visitors'] }}</td>
                            <td>
                                <div class="bar-wrap">
                                    <div class="bar-fill" style="width:{{ $maxPv > 0 ? round($pv['count']/$maxPv*100) : 0 }}%;background:#6366f1;"></div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <div class="col-md-6">
            <div class="section-card h-100">
                <div class="section-title"><i class="bi bi-cursor me-1"></i> Elemen Paling Banyak Diklik</div>
                @if($clickEvents->isEmpty())
                <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.82rem;">
                    Belum ada data klik.<br>
                    <span style="font-size:.72rem;">Data akan muncul setelah pengunjung storefront aktif klik.</span>
                </div>
                @else
                <div style="overflow-x:auto;">
                    <table class="table mb-0 crm-table">
                        <thead>
                            <tr>
                                <th>Elemen</th>
                                <th style="text-align:right;">Klik</th>
                                <th style="text-align:right;">Visitors</th>
                                <th style="min-width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                        @php
                            $maxClk = $clickEvents->max('count');
                            $clickColors = [
                                'wa_button'     => '#25d366',
                                'product_link'  => '#6366f1',
                                'product_card'  => '#8b5cf6',
                                'add_to_cart_cta' => '#f59e0b',
                                'checkout_link' => '#0ea5e9',
                                'submit_button' => '#10b981',
                                'cart_link'     => '#f43f5e',
                                'nav_link'      => '#94a3b8',
                            ];
                        @endphp
                        @foreach($clickEvents as $ce)
                        <tr>
                            <td>
                                <span class="event-dot" style="background:{{ $clickColors[$ce['label']] ?? '#94a3b8' }}"></span>
                                {{ str_replace('_', ' ', $ce['label']) }}
                            </td>
                            <td style="text-align:right;font-weight:800;">{{ $ce['count'] }}</td>
                            <td style="text-align:right;color:#64748b;">{{ $ce['visitors'] }}</td>
                            <td>
                                <div class="bar-wrap">
                                    <div class="bar-fill" style="width:{{ $maxClk > 0 ? round($ce['count']/$maxClk*100) : 0 }}%;background:{{ $clickColors[$ce['label']] ?? '#94a3b8' }};"></div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Page Duration + Recent Sessions --}}
    <div class="row g-3 mb-3">
        <div class="col-md-5">
            <div class="section-card h-100">
                <div class="section-title"><i class="bi bi-clock-history me-1"></i> Avg Durasi per Halaman</div>
                @if($pageDurations->isEmpty())
                <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.82rem;">
                    Belum ada data durasi.<br>
                    <span style="font-size:.72rem;">Muncul setelah pengunjung buka halaman storefront.</span>
                </div>
                @else
                <div style="overflow-x:auto;">
                    <table class="table mb-0 crm-table">
                        <thead>
                            <tr><th>Halaman</th><th style="text-align:right;">Avg Durasi</th><th style="text-align:right;">Sample</th></tr>
                        </thead>
                        <tbody>
                        @foreach($pageDurations as $pd)
                        <tr>
                            <td style="font-size:.75rem;">{{ str_replace('storefront.', '', $pd['page']) }}</td>
                            <td style="text-align:right;font-weight:800;">
                                @if($pd['avg_sec'] >= 60)
                                    {{ floor($pd['avg_sec']/60) }}m {{ $pd['avg_sec']%60 }}s
                                @else
                                    {{ $pd['avg_sec'] }}s
                                @endif
                            </td>
                            <td style="text-align:right;color:#64748b;font-size:.72rem;">{{ $pd['count'] }}×</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <div class="col-md-7">
            <div class="section-card h-100">
                <div class="section-title"><i class="bi bi-person-lines-fill me-1"></i> Sesi Visitor Terbaru</div>
                @if($recentSessions->isEmpty())
                <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.82rem;">Belum ada visitor</div>
                @else
                <div style="overflow-x:auto;">
                    <table class="table mb-0 crm-table">
                        <thead>
                            <tr>
                                <th>Visitor</th>
                                <th>Kota</th>
                                <th style="text-align:right;">Pages</th>
                                <th style="text-align:right;">Total Waktu</th>
                                <th>Aktivitas</th>
                                <th>Terakhir</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($recentSessions as $s)
                        @php $v = $s['visitor']; @endphp
                        <tr>
                            <td>
                                @if($v->customer_name)
                                    <div class="fw-bold" style="font-size:.78rem;">{{ $v->customer_name }}</div>
                                    <div style="font-size:.65rem;color:#94a3b8;font-family:monospace;">{{ substr($v->visitor_token, 0, 12) }}…</div>
                                @else
                                    <div style="font-size:.72rem;font-family:monospace;color:#94a3b8;">{{ substr($v->visitor_token, 0, 16) }}…</div>
                                    <span style="font-size:.65rem;background:#f1f5f9;color:#64748b;border-radius:4px;padding:.05rem .3rem;">Anon</span>
                                @endif
                            </td>
                            <td style="font-size:.75rem;color:#334155;text-transform:capitalize;">{{ $v->city ? strtolower($v->city) : '—' }}</td>
                            <td style="text-align:right;font-weight:700;">{{ $s['page_views'] }}</td>
                            <td style="text-align:right;font-size:.75rem;color:#64748b;">
                                @if($s['total_sec'] > 0)
                                    @if($s['total_sec'] >= 60) {{ floor($s['total_sec']/60) }}m {{ $s['total_sec']%60 }}s
                                    @else {{ $s['total_sec'] }}s @endif
                                @else —
                                @endif
                            </td>
                            <td style="font-size:.7rem;">
                                @if($s['has_order'])
                                    <span style="background:#dcfce7;color:#166534;border-radius:4px;padding:.1rem .35rem;font-weight:800;">Order</span>
                                @elseif($s['has_cart'])
                                    <span style="background:#fef3c7;color:#92400e;border-radius:4px;padding:.1rem .35rem;font-weight:800;">Cart</span>
                                @else
                                    <span style="color:#cbd5e1;">Browse</span>
                                @endif
                            </td>
                            <td style="font-size:.7rem;color:#94a3b8;white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($v->last_seen_at)->diffForHumans() }}
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

</div>
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const labels = @json($chartLabels);
const data   = @json($chartData);

new Chart(document.getElementById('visitorsChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Unique Visitors',
            data: data,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,.08)',
            borderWidth: 2,
            pointRadius: data.length <= 14 ? 4 : 2,
            pointBackgroundColor: '#6366f1',
            tension: 0.3,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.parsed.y} visitor`
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 10 }, maxTicksLimit: 15 }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    font: { size: 10 },
                    precision: 0,
                    stepSize: 1,
                },
                grid: { color: 'rgba(0,0,0,.04)' }
            }
        }
    }
});
</script>
@endpush
@endsection
