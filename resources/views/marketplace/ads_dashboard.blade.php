@extends('layouts.app')

@section('title', 'Analisis Iklan Shopee')

@push('head')
@include('dashboard.partials._styles')
<style>
/* ─────────────────────────────────────────────────────────────────────────────
   CUSTOM TABS UNTUK DASBOR INI (Gaya Segmentation Minimalis)
───────────────────────────────────────────────────────────────────────────── */
.dash-tabs {
    display: inline-flex;
    background: var(--card, #fff);
    border: 1px solid var(--dsh-border);
    border-radius: 8px;
    padding: .2rem;
    gap: .2rem;
    margin-bottom: .2rem;
}
.dash-tab {
    background: transparent;
    border: none;
    padding: .4rem 1rem;
    font-size: .8rem;
    font-weight: 650;
    color: var(--dsh-muted);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.dash-tab:hover {
    color: var(--text, #0f172a);
}
.dash-tab.active {
    background: var(--dsh-accent);
    color: #fff;
}
body[data-theme="dark"] .dash-tab.active {
    background: var(--text);
    color: var(--bg);
}
.tab-pane { display: none; }
.tab-pane.active { display: block; }

/* Filter Container di dalam Dashboard */
.dash-filter {
    background: var(--card, #fff);
    border: 1px solid var(--dsh-border);
    border-radius: 8px;
    padding: .75rem 1rem;
    display: flex;
    gap: .75rem;
    align-items: flex-end;
    flex-wrap: wrap;
}
.filter-item { flex: 1; min-width: 160px; }
.filter-item label { font-size: .72rem; font-weight: 650; color: var(--dsh-muted); margin-bottom: .25rem; display: block; }
.filter-item input, .filter-item select {
    width: 100%; font-size: .8rem; padding: .4rem .6rem; border-radius: 6px;
    border: 1px solid var(--dsh-border); background: var(--bg, #f4f5fb); color: var(--text, #0f172a);
}
body[data-theme="dark"] .filter-item input, body[data-theme="dark"] .filter-item select {
    background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); color: #e5e7eb;
}

/* Tabel di dalam dpanel */
.dpanel-table { width: 100%; border-collapse: collapse; }
.dpanel-table thead th {
    background: rgba(148,163,184,.04);
    border-bottom: 1px solid var(--dsh-border);
    font-size: .72rem;
    font-weight: 700;
    color: var(--dsh-muted);
    padding: .5rem .85rem;
    text-align: left;
    white-space: nowrap;
}
.dpanel-table tbody td {
    padding: .6rem .85rem;
    font-size: .82rem;
    border-bottom: 1px solid var(--dsh-border);
    color: var(--text, #0f172a);
    vertical-align: middle;
}
.dpanel-table tbody tr:last-child td { border-bottom: none; }
.dpanel-table tbody tr:hover td { background: rgba(148,163,184,.04); }

/* Periode Bar (Pill) selaras dengan Shipments */
.period-bar { display: flex; gap: .75rem; align-items: center; }
.period-select { border-radius: 8px; flex: 1; max-width: 250px; font-size: .8rem; padding: .4rem .6rem; border: 1px solid var(--dsh-border); background: var(--bg); color: var(--text); }
.range-pill {
    display: inline-flex; align-items: center; justify-content: space-between; gap: .7rem;
    border: 1px solid var(--dsh-border); background: rgba(148,163,184,.06); padding: .4rem .85rem; border-radius: 8px;
    cursor: pointer; font-size: .85rem; color: var(--text, #0f172a); transition: background .2s ease;
}
body[data-theme="dark"] .range-pill { color: #e5e7eb; background: rgba(255,255,255,.05); }
.range-pill:hover { background: rgba(148,163,184,.12); }
.range-pill .range-text { font-weight: 650; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 230px; }
.range-pill .range-meta { display: inline-flex; align-items: center; gap: .5rem; flex: 0 0 auto; }
.range-pill .tz { color: var(--dsh-muted); font-size: .82rem; }

</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabBtns = document.querySelectorAll('.dash-tab');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));

            btn.classList.add('active');
            document.getElementById(btn.getAttribute('data-target')).classList.add('active');
            window.dispatchEvent(new Event('resize')); // re-render charts
        });
    });

    // Flatpickr & Preset Logic (Shipments Style)
    const preset = document.getElementById('presetRange');
    const toggleDate = document.getElementById('toggleDate');
    const rangeText = document.getElementById('rangeText');
    const fromEl = document.getElementById('fromHidden');
    const toEl = document.getElementById('toHidden');
    const fpInput = document.getElementById('rangePicker');
    const filterForm = document.getElementById('filterForm');

    function ymd(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
    
    function setRangeText() {
        if(!fromEl || !toEl || !fromEl.value || !toEl.value) { if(rangeText) rangeText.textContent = '-'; return; }
        const idMonths = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        const fmtDate = (str) => {
            if(!str) return '';
            const d = new Date(str);
            return d.getDate() + ' ' + idMonths[d.getMonth()] + ' ' + d.getFullYear();
        };
        const f = fmtDate(fromEl.value);
        const t = fmtDate(toEl.value);
        if(rangeText) rangeText.textContent = (f === t) ? f : (f + ' - ' + t);
    }
    
    function applyPreset(v) {
        if(v === 'custom') return;
        const now = new Date();
        let start = new Date();
        let end = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        
        const n = parseInt(v) || 7;
        start = new Date(now.getFullYear(), now.getMonth(), now.getDate() - (n - 1));
        
        fromEl.value = ymd(start);
        toEl.value = ymd(end);
        setRangeText();
        filterForm.submit();
    }
    
    setRangeText();
    
    let fp = null;
    if(typeof flatpickr !== 'undefined' && fpInput) {
        fp = flatpickr(fpInput, {
            mode: 'range',
            locale: 'id',
            showMonths: window.innerWidth > 768 ? 2 : 1,
            defaultDate: [fromEl.value, toEl.value],
            onChange: function(selectedDates, dateStr, instance) {
                if(selectedDates.length < 2) return;
                fromEl.value = ymd(selectedDates[0]);
                toEl.value = ymd(selectedDates[1]);
                setRangeText();
                if(preset) preset.value = 'custom';
                filterForm.submit();
            }
        });
    }

    if(toggleDate) {
        toggleDate.addEventListener('click', () => {
            if(preset) preset.value = 'custom';
            if(fp) fp.open();
        });
    }

    if(preset) {
        preset.addEventListener('change', () => { applyPreset(preset.value); });
        const now = new Date();
        const endStr = ymd(now);
        if(toEl.value === endStr) {
            const dStart = new Date(fromEl.value);
            const diffDays = Math.round((now - dStart) / (1000 * 60 * 60 * 24)) + 1;
            if([7,14,30].includes(diffDays)) preset.value = diffDays.toString();
            else preset.value = 'custom';
        } else {
            preset.value = 'custom';
        }
    }
});
</script>
@endpush

@section('content')
<div class="dash py-3">

    {{-- ==============================================
         HERO SECTION (Header)
    ============================================== --}}
    <div class="dash-hero">
        <div>
            <h1>Analisis Iklan Shopee</h1>
            <div class="sub">Pemantauan biaya, GMV, dan kontrol ROAS harian.</div>
        </div>
        <div>
            <div class="role-chip">
                <i class="bi bi-clock"></i> Live Sync
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="dpanel dpanel-body" style="border-left: 4px solid #dc2626; color: #dc2626; font-size:.82rem; font-weight: 500;">
            <i class="bi bi-exclamation-circle me-1"></i> {{ session('error') }}
        </div>
    @endif
    @if(session('success'))
        <div class="dpanel dpanel-body" style="border-left: 4px solid #16a34a; color: #16a34a; font-size:.82rem; font-weight: 500;">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        </div>
    @endif

    {{-- ==============================================
         FILTER
    ============================================== --}}
    <form method="GET" action="{{ route('marketplace.ads.dashboard') }}" class="dash-filter" id="filterForm">
        <div class="filter-item">
            <label>Toko Shopee</label>
            <select name="store_id" onchange="this.form.submit()">
                @foreach($stores as $s)
                    <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-item" style="flex: 2;">
            <label>Periode Data</label>
            <div class="period-bar">
                <select class="period-select" id="presetRange">
                    <option value="7">7 Hari Sebelumnya</option>
                    <option value="14">14 Hari Sebelumnya</option>
                    <option value="30">30 Hari Sebelumnya</option>
                    <option value="custom">Pilih Tanggal...</option>
                </select>
                <button type="button" class="range-pill" id="toggleDate">
                    <span class="range-text" id="rangeText">-</span>
                    <span class="range-meta">
                        <span class="tz">(GMT+07)</span>
                        <span class="ico">📅</span>
                    </span>
                </button>
                <input type="hidden" name="date_from" id="fromHidden" value="{{ $dateFrom }}">
                <input type="hidden" name="date_to" id="toHidden" value="{{ $dateTo }}">
                <input type="text" id="rangePicker" class="visually-hidden" aria-hidden="true" tabindex="-1" style="display:none;">
            </div>
        </div>
    </form>

    @if(empty($storeId))
        <div class="dash-empty">
            <i class="bi bi-shop"></i>
            Pilih toko terlebih dahulu untuk melihat analisis.
        </div>
    @else
        {{-- ==============================================
             TABS
        ============================================== --}}
        <div>
            <div class="dash-tabs">
                <button class="dash-tab active" data-target="tab-overview">Ringkasan</button>
                <button class="dash-tab" data-target="tab-campaigns">Kampanye</button>
                <button class="dash-tab" data-target="tab-settings">Sync & Log</button>
            </div>
        </div>

        {{-- ==============================================
             TAB CONTENT
        ============================================== --}}
        
        <!-- TAB 1: OVERVIEW -->
        <div class="tab-pane active" id="tab-overview">
            
            <div class="dash-sec"><i class="bi bi-grid-1x2"></i> Indikator Performa (KPI)</div>
            
            <div class="dash-grid mb-3">
                @php
                    $metrics = [
                        ['title' => 'Biaya Iklan (Spend)', 'key' => 'spend', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'red', 'icon' => 'bi-wallet2'],
                        ['title' => 'GMV Iklan', 'key' => 'gmv', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'green', 'icon' => 'bi-bag-check'],
                        ['title' => 'ROAS', 'key' => 'roas', 'prefix' => '', 'suffix' => 'x', 'cls' => 'blue', 'icon' => 'bi-lightning-charge'],
                        ['title' => 'Pesanan', 'key' => 'orders', 'prefix' => '', 'suffix' => '', 'cls' => 'slate', 'icon' => 'bi-box-seam'],
                        ['title' => 'Impression', 'key' => 'impressions', 'prefix' => '', 'suffix' => '', 'cls' => 'amber', 'icon' => 'bi-eye'],
                        ['title' => 'Klik', 'key' => 'clicks', 'prefix' => '', 'suffix' => '', 'cls' => 'violet', 'icon' => 'bi-cursor'],
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
                        $colorClass = $isUp ? 'color: #16a34a;' : 'color: #dc2626;';
                    @endphp
                    <div class="kpi {{ $m['cls'] }}">
                        <div class="kpi-label">
                            <div class="ico"><i class="bi {{ $m['icon'] }}"></i></div>
                            {{ $m['title'] }}
                        </div>
                        <div class="kpi-value {{ $m['key'] == 'spend' || $m['key'] == 'gmv' ? 'sm' : '' }}" style="font-family: ui-monospace, monospace;">
                            {{ $m['prefix'] }}{{ is_float($val) ? number_format($val, 2, ',', '.') : number_format($val, 0, ',', '.') }}{{ $m['suffix'] }}
                        </div>
                        <div class="kpi-sub">
                            <span style="font-weight:700; {{ $colorClass }}">
                                <i class="bi bi-arrow-{{ $isUp ? 'up-right' : 'down-right' }}"></i> {{ abs($change) }}%
                            </span> 
                            vs rentang lalu
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="dash-sec"><i class="bi bi-graph-up"></i> Grafik Performa Harian & Per Jam</div>
            
            <div class="dash-panels" style="grid-template-columns: 2fr 1fr;">
                <div class="dpanel p-3">
                    <div style="height: 280px;">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
                <div class="dpanel p-3">
                    <div style="height: 280px;">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: CAMPAIGNS -->
        <div class="tab-pane" id="tab-campaigns">
            <div class="dash-sec"><i class="bi bi-megaphone"></i> Daftar Kampanye</div>
            
            <div class="dpanel">
                <div class="table-responsive">
                    <table class="dpanel-table">
                        <thead>
                            <tr>
                                <th>Kampanye</th>
                                <th>Tipe</th>
                                <th>Status</th>
                                <th class="text-end">Biaya (Spend)</th>
                                <th class="text-end">GMV</th>
                                <th class="text-center">ROAS</th>
                                <th class="text-end">ACOS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($campaigns as $camp)
                                <tr>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text);">{{ $camp->campaign_name }}</div>
                                        <div style="font-family: ui-monospace, monospace; font-size: .7rem; color: var(--dsh-muted);">ID: {{ $camp->channel_campaign_id }}</div>
                                    </td>
                                    <td><span style="font-size: .75rem; color: var(--dsh-muted);">{{ $camp->campaign_type }}</span></td>
                                    <td>
                                        <span class="pill {{ $camp->status == 'ONGOING' ? 'green' : 'slate' }}">
                                            {{ $camp->status }}
                                        </span>
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; font-weight:700; color: #dc2626;">Rp {{ number_format($camp->spend, 0, ',', '.') }}</td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; font-weight:700; color: #16a34a;">Rp {{ number_format($camp->gmv, 0, ',', '.') }}</td>
                                    <td class="text-center" style="font-family: ui-monospace, monospace; font-weight:700;">
                                        {{ $camp->spend > 0 ? number_format($camp->gmv / $camp->spend, 2) : 0 }}x
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; color: var(--dsh-muted);">{{ $camp->gmv > 0 ? number_format(($camp->spend / $camp->gmv) * 100, 2) : 0 }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4" style="color: var(--dsh-muted); font-size: .8rem;">
                                        Belum ada data kampanye.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 3: SETTINGS & LOGS -->
        <div class="tab-pane" id="tab-settings">
            
            <div class="dash-panels" style="grid-template-columns: 1fr 2.5fr;">
                <!-- Sync Form -->
                <div>
                    <div class="dash-sec"><i class="bi bi-arrow-repeat"></i> Manual Sync</div>
                    <div class="dpanel p-3">
                        <form action="{{ route('marketplace.ads.sync') }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <label style="font-size: .72rem; font-weight: 650; color: var(--dsh-muted); display: block; margin-bottom: .2rem;">Toko Target</label>
                                <select name="store_id" style="width: 100%; font-size: .8rem; padding: .4rem .6rem; border-radius: 6px; border: 1px solid var(--dsh-border); background: var(--bg); color: var(--text);" required>
                                    @foreach($stores as $s)
                                        <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label style="font-size: .72rem; font-weight: 650; color: var(--dsh-muted); display: block; margin-bottom: .2rem;">Mode Sinkronisasi</label>
                                <select name="sync_type" style="width: 100%; font-size: .8rem; padding: .4rem .6rem; border-radius: 6px; border: 1px solid var(--dsh-border); background: var(--bg); color: var(--text);">
                                    <option value="incremental">Incremental (Hari ini & Kemarin)</option>
                                    <option value="hourly">Hourly (Khusus Jam Ini)</option>
                                    <option value="backfill">Historical Backfill (6 Bulan)</option>
                                </select>
                            </div>
                            <button type="submit" class="act" style="border:none; background: var(--dsh-accent); color: #fff; width: 100%; justify-content: center;">
                                <i class="bi bi-cloud-download"></i> Jalankan Sync
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Sync Logs -->
                <div>
                    <div class="dash-sec"><i class="bi bi-clock-history"></i> Riwayat Sinkronisasi (Log)</div>
                    <div class="dpanel">
                        <div class="table-responsive">
                            <table class="dpanel-table">
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
                                                $statusClass = 'slate';
                                                if ($run->status == 'success') $statusClass = 'green';
                                                elseif ($run->status == 'error') $statusClass = 'red';
                                                elseif ($run->status == 'rate_limited') $statusClass = 'amber';
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div style="font-weight: 700; color: var(--text);">{{ $run->created_at->format('d/m/Y') }}</div>
                                                    <div style="font-family: ui-monospace, monospace; font-size: .7rem; color: var(--dsh-muted);">{{ $run->created_at->format('H:i:s') }}</div>
                                                </td>
                                                <td><span style="font-size: .75rem; color: var(--dsh-muted);">{{ $run->sync_type }}</span></td>
                                                <td><span class="pill {{ $statusClass }}">{{ strtoupper($run->status) }}</span></td>
                                                <td>
                                                    <div style="font-size: .75rem; color: var(--dsh-muted); max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $run->error_message }}">
                                                        {{ $run->error_message ?? '-' }}
                                                    </div>
                                                </td>
                                                <td class="text-end" style="font-family: ui-monospace, monospace; font-size: .75rem; font-weight: 650; color: var(--text);">
                                                    {{ $run->total_requests }} <span style="font-weight: normal; color: var(--dsh-muted);">/</span> <span style="color: #16a34a;">{{ $run->total_updated }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="5" class="text-center py-4" style="color: var(--dsh-muted); font-size: .8rem;">Belum ada riwayat sinkronisasi.</td>
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
    
    // Theme colors
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#e5e7eb' : '#0f172a';
    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    
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
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.1)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 2,
                        yAxisID: 'y'
                    },
                    {
                        label: 'GMV (Rp)',
                        data: dailyData.map(d => parseFloat(d.gmv)),
                        borderColor: '#16a34a',
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
                        grid: { color: gridColor }, ticks: { font: { size: 10 } }
                    },
                    y1: { 
                        type: 'linear', position: 'right', beginAtZero: true, 
                        grid: { drawOnChartArea: false }, ticks: { font: { size: 10 } }
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
                        grid: { color: gridColor }, ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }
});
</script>
@endif
@endpush
