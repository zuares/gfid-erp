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
            const targetPane = document.getElementById(btn.getAttribute('data-target'));
            if (targetPane) targetPane.classList.add('active');
            
            window.dispatchEvent(new Event('resize')); // re-render charts
        });
    });

    window.dispatchEvent(new Event('resize')); // re-render charts on load

    // Flatpickr Logic
    const rangePicker = document.getElementById('rangePicker');
    const fromEl = document.getElementById('fromHidden');
    const toEl = document.getElementById('toHidden');
    const filterForm = document.getElementById('filterForm');

    function ymd(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
    
    if(typeof flatpickr !== 'undefined' && rangePicker) {
        flatpickr(rangePicker, {
            mode: 'range',
            locale: 'id',
            showMonths: 2, // Tampilkan 2 bulan berjejer agar mudah pilih rentang panjang
            dateFormat: 'Y-m-d',
            defaultDate: [fromEl.value, toEl.value],
            onChange: function(selectedDates, dateStr, instance) {
                if(selectedDates.length === 2) {
                    fromEl.value = ymd(selectedDates[0]);
                    toEl.value = ymd(selectedDates[1]);
                    filterForm.submit();
                }
            }
        });
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
            @if(isset($syncRuns) && $syncRuns->isNotEmpty())
            <div style="font-size: 0.72rem; color: var(--dsh-muted); text-align: right; margin-bottom: 0.35rem; font-weight: 500;">
                Terakhir Sync: {{ $syncRuns->first()->updated_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }}
            </div>
            @endif
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
                <input type="text" id="rangePicker" class="range-pill" style="width: 260px; text-align: center; cursor: pointer; background: rgba(148,163,184,.06); border: 1px solid var(--dsh-border); padding: .4rem .85rem; border-radius: 8px; color: var(--text, #0f172a); font-weight: 650; font-size: .85rem;" placeholder="Pilih Rentang Tanggal..." readonly>
                <input type="hidden" name="date_from" id="fromHidden" value="{{ $dateFrom }}">
                <input type="hidden" name="date_to" id="toHidden" value="{{ $dateTo }}">
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
                <button class="dash-tab active" data-target="tab-dashboard">Dashboard</button>
                <button class="dash-tab" data-target="tab-historical">Komparasi Historis</button>
                <button class="dash-tab" data-target="tab-campaigns">Rincian Kampanye</button>
                <button class="dash-tab" data-target="tab-sync">Sinkronisasi</button>
            </div>
        </div>

        {{-- ==============================================
             TAB CONTENT
        ============================================== --}}
        
        <!-- DASHBOARD TAB -->
        <div class="tab-pane active" id="tab-dashboard">
            
            <div class="dash-sec"><i class="bi bi-grid-1x2"></i> Indikator Performa (KPI)</div>
            
            <div class="dash-grid mb-3">
                @php
                    $metrics = [
                        ['title' => 'Biaya (Spend)', 'key' => 'spend', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'red', 'icon' => 'bi-wallet2'],
                        ['title' => 'GMV (Pendapatan)', 'key' => 'gmv', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'green', 'icon' => 'bi-bag-check'],
                        ['title' => 'ROAS', 'key' => 'roas', 'prefix' => '', 'suffix' => 'x', 'cls' => 'blue', 'icon' => 'bi-lightning-charge'],
                        ['title' => 'Pesanan', 'key' => 'orders', 'prefix' => '', 'suffix' => '', 'cls' => 'slate', 'icon' => 'bi-box-seam'],
                        ['title' => 'AOV', 'key' => 'aov', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'slate', 'icon' => 'bi-cart-check'],
                        ['title' => 'Impression', 'key' => 'impressions', 'prefix' => '', 'suffix' => '', 'cls' => 'amber', 'icon' => 'bi-eye'],
                        ['title' => 'CTR', 'key' => 'ctr', 'prefix' => '', 'suffix' => '%', 'cls' => 'amber', 'icon' => 'bi-hand-index'],
                        ['title' => 'Klik', 'key' => 'clicks', 'prefix' => '', 'suffix' => '', 'cls' => 'violet', 'icon' => 'bi-cursor'],
                        ['title' => 'CVR', 'key' => 'cvr', 'prefix' => '', 'suffix' => '%', 'cls' => 'violet', 'icon' => 'bi-funnel'],
                        ['title' => 'CPC', 'key' => 'cpc', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'red', 'icon' => 'bi-coin'],
                    ];
                @endphp
                @foreach($metrics as $m)
                    @php
                        $currSpend = $kpi['current']->spend ?? 0;
                        $currGmv = $kpi['current']->gmv ?? 0;
                        $currOrders = $kpi['current']->orders ?? 0;
                        $currClicks = $kpi['current']->clicks ?? 0;
                        $currImpressions = $kpi['current']->impressions ?? 0;

                        $prevSpend = $kpi['previous']->spend ?? 0;
                        $prevGmv = $kpi['previous']->gmv ?? 0;
                        $prevOrders = $kpi['previous']->orders ?? 0;
                        $prevClicks = $kpi['previous']->clicks ?? 0;
                        $prevImpressions = $kpi['previous']->impressions ?? 0;

                        $val = $kpi['current']->{$m['key']} ?? 0;
                        $prevVal = $kpi['previous']->{$m['key']} ?? 0;

                        if($m['key'] === 'roas') {
                            $val = $currSpend > 0 ? round($currGmv / $currSpend, 2) : 0;
                            $prevVal = $prevSpend > 0 ? round($prevGmv / $prevSpend, 2) : 0;
                        } elseif ($m['key'] === 'aov') {
                            $val = $currOrders > 0 ? round($currGmv / $currOrders, 0) : 0;
                            $prevVal = $prevOrders > 0 ? round($prevGmv / $prevOrders, 0) : 0;
                        } elseif ($m['key'] === 'cpc') {
                            $val = $currClicks > 0 ? round($currSpend / $currClicks, 0) : 0;
                            $prevVal = $prevClicks > 0 ? round($prevSpend / $prevClicks, 0) : 0;
                        } elseif ($m['key'] === 'ctr') {
                            $val = $currImpressions > 0 ? round(($currClicks / $currImpressions) * 100, 2) : 0;
                            $prevVal = $prevImpressions > 0 ? round(($prevClicks / $prevImpressions) * 100, 2) : 0;
                        } elseif ($m['key'] === 'cvr') {
                            $val = $currClicks > 0 ? round(($currOrders / $currClicks) * 100, 2) : 0;
                            $prevVal = $prevClicks > 0 ? round(($prevOrders / $prevClicks) * 100, 2) : 0;
                        }

                        $change = $kpi['changes'][$m['key']] ?? 0;
                        if (in_array($m['key'], ['aov', 'cpc', 'ctr', 'cvr'])) {
                            if ($prevVal == 0) {
                                $change = $val > 0 ? 100 : 0;
                            } else {
                                $change = round((($val - $prevVal) / $prevVal) * 100, 2);
                            }
                        }

                        $isUp = $change >= 0;
                        
                        // For cost metrics, going down is good (green). For others, going up is good.
                        if (in_array($m['key'], ['spend', 'cpc'])) {
                            $colorClass = $isUp && $change > 0 ? 'color: #dc2626;' : 'color: #16a34a;';
                        } else {
                            $colorClass = $isUp ? 'color: #16a34a;' : 'color: #dc2626;';
                        }
                    @endphp
                    <div class="kpi {{ $m['cls'] }}">
                        <div class="kpi-label">
                            <div class="ico"><i class="bi {{ $m['icon'] }}"></i></div>
                            {{ $m['title'] }}
                        </div>
                        <div class="kpi-value {{ in_array($m['key'], ['spend', 'gmv', 'aov']) ? 'sm' : '' }}" style="font-family: ui-monospace, monospace;">
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

            <div class="dash-sec"><i class="bi bi-robot"></i> Asisten Analisis (Berdasarkan Rentang Tanggal)</div>
            <div class="dash-panels mb-4" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-border)" id="insightHealth"></div>
                <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-border)" id="insightTraffic"></div>
                <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-border)" id="insightTime"></div>
            </div>

            <div class="dash-sec"><i class="bi bi-graph-up"></i> Grafik Performa Harian & Per Jam</div>
            
            <div class="dash-panels" style="grid-template-columns: 1fr; gap: 1rem;">
                <!-- 1. TREN FINANSIAL HARIAN -->
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div style="font-weight: 650; font-size: 0.85rem; color: var(--dsh-muted);">Tren Finansial Harian</div>
                            <div class="mt-1" style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85;">
                                💡 <b>Finansial:</b> Selisih <b>GMV & Biaya</b> = Margin. <b>Garis ROAS (Emas)</b> = Profitabilitas. <b>AOV (Putus-putus)</b> = Rata-rata belanja (cek sensitivitas harga).
                            </div>
                        </div>
                        <div id="dailySummary" style="font-size: 0.8rem; font-weight: 700; color: var(--text); text-align: right;"></div>
                    </div>
                    <div style="height: 280px;">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>

                <!-- 2. TREN TRAFIK HARIAN -->
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div style="font-weight: 650; font-size: 0.85rem; color: var(--dsh-muted);">Tren Trafik Harian</div>
                            <div class="mt-1" style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85;">
                                💡 <b>Trafik (Funnel):</b> <b>Impresi (Kuning)</b> vs <b>Klik (Biru)</b> = Rasio bocor. Awasi <b>Garis CTR (Ungu)</b>; jika anjlok, segera evaluasi foto/judul produk!
                            </div>
                        </div>
                        <div id="trafficSummary" style="font-size: 0.8rem; font-weight: 700; color: var(--text); text-align: right;"></div>
                    </div>
                    <div style="height: 280px;">
                        <canvas id="trafficChart"></canvas>
                    </div>
                </div>

                <!-- 3. DISTRIBUSI PER JAM -->
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div style="font-weight: 650; font-size: 0.85rem; color: var(--dsh-muted);">Distribusi Per Jam (Dayparting)</div>
                            <div class="mt-1" style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85;">
                                💡 <b>Dayparting:</b> Cari jam di mana <b>Garis ROAS (Emas)</b> memuncak dan <b>Siluet Trafik</b> tebal. Itu adalah <b>Jam Emas</b> untuk menaikkan bid iklan!
                            </div>
                        </div>
                        <div id="hourlySummary" style="font-size: 0.8rem; font-weight: 700; color: var(--text); text-align: right;"></div>
                    </div>
                    <div style="height: 280px;">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        </div>

        <!-- TAB RINCIAN KAMPANYE -->
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
                                <th class="text-end">ROAS</th>
                                <th class="text-end">Klik</th>
                                <th class="text-end">Pesanan</th>
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
                                    <td class="text-end" style="font-family: ui-monospace, monospace; font-weight:700;">
                                        {{ $camp->spend > 0 ? number_format($camp->gmv / $camp->spend, 2) : 0 }}x
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; color: var(--dsh-muted);">{{ number_format($camp->clicks, 0, ',', '.') }}</td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; color: var(--dsh-muted);">{{ number_format($camp->orders, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4" style="color: var(--dsh-muted); font-size: .8rem;">
                                        Belum ada data kampanye.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB KOMPARASI HISTORIS -->
        <div class="tab-pane" id="tab-historical">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="dash-sec mb-0"><i class="bi bi-clock-history"></i> Komparasi Historis (Period-over-Period)</div>
                <div>
                    <select id="histMetricSelect" class="form-select form-select-sm" style="width: auto; background: var(--dsh-panel); color: var(--text); border-color: var(--dsh-border);">
                        <option value="roas">Metrik: ROAS</option>
                        <option value="gmv">Metrik: GMV (Pendapatan)</option>
                        <option value="spend">Metrik: Biaya (Spend)</option>
                    </select>
                </div>
            </div>
            
            <div class="dpanel p-3">
                <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 1rem;">
                    💡 <b>Info:</b> Membandingkan performa rentang saat ini dengan rentang sebelumnya yang berdurasi sama persis.
                </div>
                <div style="position: relative; height: 350px;">
                    <canvas id="historicalChart"></canvas>
                </div>
            </div>
        </div>

        <!-- SINKRONISASI TAB -->
        <div class="tab-pane" id="tab-sync">
            
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
    const rawDaily = @json($dailyChartData ?? []);
    const rawHourly = @json($heatmapData ?? []);
    const rawHistorical = @json($historicalData ?? []);
    
    // Pad Daily Data to show full range
    const dailyData = [];
    const fromEl = document.getElementById('fromHidden');
    const toEl = document.getElementById('toHidden');
    if (fromEl && toEl && fromEl.value && toEl.value) {
        // use ymd function defined earlier or create inline logic
        function ymdLocal(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
        const dStart = new Date(fromEl.value);
        const dEnd = new Date(toEl.value);
        for (let d = new Date(dStart); d <= dEnd; d.setDate(d.getDate() + 1)) {
            let ds = ymdLocal(d);
            let found = rawDaily.find(item => {
                if(!item.date) return false;
                let itemDate = new Date(item.date);
                return ymdLocal(itemDate) === ds;
            });
            dailyData.push(found ? found : { date: ds, spend: 0, gmv: 0, roas: 0 });
        }
    } else {
        dailyData.push(...rawDaily);
    }

    // Pad Hourly Data to show full 24 hours
    const hourlyData = [];
    for (let i = 0; i < 24; i++) {
        let found = rawHourly.find(d => parseInt(d.performance_hour) === i);
        hourlyData.push(found ? found : { performance_hour: i, clicks: 0, orders: 0, expense: 0, gmv: 0 });
    }
    
    // Theme & UX Colors
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#94a3b8' : '#64748b'; // softer text for axes
    const gridColor = isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.04)';
    const tooltipBg = isDark ? 'rgba(15,23,42,0.95)' : 'rgba(255,255,255,0.95)';
    const tooltipBorder = isDark ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.08)';
    const tooltipText = isDark ? '#f8fafc' : '#0f172a';
    
    Chart.defaults.color = textColor;
    Chart.defaults.font.family = 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace';

    // Helper: Format Rupiah Singkat (Jt/Rb)
    const formatShortIDR = (value) => {
        if(value >= 1000000) return (value / 1000000).toFixed(1).replace(/\.0$/, '') + ' Jt';
        if(value >= 1000) return (value / 1000).toFixed(1).replace(/\.0$/, '') + ' Rb';
        return value;
    };

    // Calculate summaries for charts
    let totalDailySpend = dailyData.reduce((sum, d) => sum + parseFloat(d.spend || 0), 0);
    let totalDailyGmv = dailyData.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);
    let totalDailyRoas = totalDailySpend > 0 ? (totalDailyGmv / totalDailySpend).toFixed(2) : "0.00";
    let dsEl = document.getElementById('dailySummary');
    if(dsEl) {
        dsEl.innerHTML = `<span style="color:#dc2626">Rp ${formatShortIDR(totalDailySpend)} Biaya</span> &bull; <span style="color:#16a34a">Rp ${formatShortIDR(totalDailyGmv)} GMV</span> &bull; <span style="color:#eab308">${totalDailyRoas}x ROAS</span>`;
    }

    let totalHourlySpend = hourlyData.reduce((sum, d) => sum + parseFloat(d.expense || 0), 0);
    let totalHourlyGmv = hourlyData.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);
    let totalHourlyRoas = totalHourlySpend > 0 ? (totalHourlyGmv / totalHourlySpend).toFixed(2) : "0.00";
    let hsEl = document.getElementById('hourlySummary');
    if(hsEl) {
        hsEl.innerHTML = `<span style="color:#dc2626">Rp ${formatShortIDR(totalHourlySpend)} Biaya</span> &bull; <span style="color:#10b981">Rp ${formatShortIDR(totalHourlyGmv)} GMV</span> &bull; <span style="color:#eab308">${totalHourlyRoas}x ROAS</span>`;
    }

    let totalDailyImpressions = dailyData.reduce((sum, d) => sum + parseInt(d.impressions || 0), 0);
    let totalDailyClicks = dailyData.reduce((sum, d) => sum + parseInt(d.clicks || 0), 0);
    let avgDailyCtr = totalDailyImpressions > 0 ? ((totalDailyClicks / totalDailyImpressions) * 100).toFixed(2) : "0.00";
    let tsEl = document.getElementById('trafficSummary');
    if(tsEl) {
        tsEl.innerHTML = `<span style="color:#f59e0b">${formatShortIDR(totalDailyImpressions)} Impresi</span> &bull; <span style="color:#3b82f6">${formatShortIDR(totalDailyClicks)} Klik</span> &bull; <span style="color:#8b5cf6">${avgDailyCtr}% CTR</span>`;
    }

    // --- AI INSIGHTS GENERATOR ---
    let totalDailyOrders = dailyData.reduce((sum, d) => sum + parseInt(d.orders || 0), 0);
    let avgDailyCvr = totalDailyClicks > 0 ? ((totalDailyOrders / totalDailyClicks) * 100).toFixed(2) : "0.00";

    // 1. Health Check
    let healthEl = document.getElementById('insightHealth');
    if (healthEl) {
        let healthHtml = '';
        if (totalDailyRoas >= 4.0) {
            healthHtml = `<div style="font-weight: 700; color: #16a34a; font-size: 0.85rem; margin-bottom: 0.3rem;">🟢 Iklan Sangat Sehat</div>
                          <div style="font-size: 0.72rem; color: var(--dsh-muted);">Efisiensi luar biasa dengan ROAS <b>${totalDailyRoas}x</b>. GMV menutupi biaya iklan dengan margin profit yang sangat aman. Pertahankan!</div>`;
            healthEl.style.borderLeftColor = '#16a34a';
        } else if (totalDailyRoas >= 2.0) {
            healthHtml = `<div style="font-weight: 700; color: #eab308; font-size: 0.85rem; margin-bottom: 0.3rem;">🟡 Status Waspada</div>
                          <div style="font-size: 0.72rem; color: var(--dsh-muted);">ROAS berada di level <b>${totalDailyRoas}x</b>. Masih profit, namun margin mulai menipis. Evaluasi kampanye yang memakan biaya besar tapi seret penjualan.</div>`;
            healthEl.style.borderLeftColor = '#eab308';
        } else {
            healthHtml = `<div style="font-weight: 700; color: #dc2626; font-size: 0.85rem; margin-bottom: 0.3rem;">🔴 Indikasi Boncos</div>
                          <div style="font-size: 0.72rem; color: var(--dsh-muted);">ROAS anjlok di angka <b>${totalDailyRoas}x</b>. Segera matikan/kurangi bid pada kampanye yang menyedot biaya tanpa hasil!</div>`;
            healthEl.style.borderLeftColor = '#dc2626';
        }
        healthEl.innerHTML = healthHtml;
    }

    // 2. Traffic Detective
    let trafficEl = document.getElementById('insightTraffic');
    if (trafficEl) {
        let trafficHtml = '';
        if (avgDailyCtr < 1.5 && totalDailyImpressions > 1000) {
            trafficHtml = `<div style="font-weight: 700; color: #dc2626; font-size: 0.85rem; margin-bottom: 0.3rem;">📉 Kebocoran Trafik (CTR)</div>
                           <div style="font-size: 0.72rem; color: var(--dsh-muted);">CTR sangat rendah (<b>${avgDailyCtr}%</b>). Pembeli melihat iklan tapi enggan klik. 💡 Saran: Segera ganti Foto Utama atau Judul Produk!</div>`;
            trafficEl.style.borderLeftColor = '#dc2626';
        } else if (avgDailyCvr < 2.0 && totalDailyClicks > 100) {
            trafficHtml = `<div style="font-weight: 700; color: #f59e0b; font-size: 0.85rem; margin-bottom: 0.3rem;">🛒 Konversi Rendah (CVR)</div>
                           <div style="font-size: 0.72rem; color: var(--dsh-muted);">CTR aman, tapi Konversi (CVR) hanya <b>${avgDailyCvr}%</b>. Orang klik tapi ragu beli. 💡 Saran: Cek harga kompetitor, voucher, atau rating produk.</div>`;
            trafficEl.style.borderLeftColor = '#f59e0b';
        } else {
            trafficHtml = `<div style="font-weight: 700; color: #3b82f6; font-size: 0.85rem; margin-bottom: 0.3rem;">🚀 Trafik Optimal</div>
                           <div style="font-size: 0.72rem; color: var(--dsh-muted);">Daya tarik iklan (CTR <b>${avgDailyCtr}%</b>) dan daya beli (CVR <b>${avgDailyCvr}%</b>) berada dalam kondisi prima.</div>`;
            trafficEl.style.borderLeftColor = '#3b82f6';
        }
        trafficEl.innerHTML = trafficHtml;
    }

    // 3. Golden Hour
    let timeEl = document.getElementById('insightTime');
    if (timeEl) {
        let bestHour = '-';
        let highestRoas = 0;
        hourlyData.forEach(d => {
            let sp = parseFloat(d.expense || 0);
            let gm = parseFloat(d.gmv || 0);
            if (sp > 1000) { // minimum spend threshold
                let r = gm / sp;
                if (r > highestRoas) {
                    highestRoas = r;
                    bestHour = d.performance_hour;
                }
            }
        });
        
        if (highestRoas > 0) {
            timeEl.innerHTML = `<div style="font-weight: 700; color: #8b5cf6; font-size: 0.85rem; margin-bottom: 0.3rem;">⏳ Waktu Emas (Dayparting)</div>
                                <div style="font-size: 0.72rem; color: var(--dsh-muted);">Puncak efisiensi tertinggi terjadi pada pukul <b>${bestHour}:00</b> (ROAS ${highestRoas.toFixed(2)}x). 💡 Saran: Naikkan bid iklan secara agresif di jam ini!</div>`;
            timeEl.style.borderLeftColor = '#8b5cf6';
        } else {
            timeEl.innerHTML = `<div style="font-weight: 700; color: var(--dsh-muted); font-size: 0.85rem; margin-bottom: 0.3rem;">⏳ Data Waktu Belum Cukup</div>
                                <div style="font-size: 0.72rem; color: var(--dsh-muted);">Belum ada jam dengan pengeluaran dan konversi yang cukup signifikan untuk menyimpulkan Waktu Emas.</div>`;
            timeEl.style.borderLeftColor = 'var(--dsh-border)';
        }
    }

    // Helper: Format Full Rupiah untuk Tooltip
    const formatFullIDR = (value) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
    };

    // --- DAILY LINE CHART ---
    const ctxDaily = document.getElementById("dailyChart");
    if(ctxDaily) {
        const ctxDaily2D = ctxDaily.getContext('2d');
        
        // Buat Gradient (atas lebih pekat, bawah memudar)
        let gradientSpend = ctxDaily2D.createLinearGradient(0, 0, 0, 300);
        gradientSpend.addColorStop(0, 'rgba(220, 38, 38, 0.25)'); // Red pekat
        gradientSpend.addColorStop(1, 'rgba(220, 38, 38, 0.0)');  // Red pudar

        let gradientGMV = ctxDaily2D.createLinearGradient(0, 0, 0, 300);
        gradientGMV.addColorStop(0, 'rgba(22, 163, 74, 0.25)'); // Green pekat
        gradientGMV.addColorStop(1, 'rgba(22, 163, 74, 0.0)');  // Green pudar

        new Chart(ctxDaily2D, {
            type: 'line',
            data: {
                labels: dailyData.map(d => {
                    // ubah format "2026-07-22" jadi "22 Jul"
                    const date = new Date(d.date);
                    return date.getDate() + ' ' + date.toLocaleString('id-ID', { month: 'short' });
                }),
                datasets: [
                    {
                        label: 'AOV',
                        data: dailyData.map(d => {
                            let gm = parseFloat(d.gmv || 0);
                            let or = parseInt(d.orders || 0);
                            return or > 0 ? parseFloat((gm/or).toFixed(0)) : 0;
                        }),
                        borderColor: '#94a3b8', // slate
                        backgroundColor: '#94a3b8',
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 0, // hide dots
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#94a3b8',
                        yAxisID: 'y2'
                    },
                    {
                        label: 'ROAS',
                        data: dailyData.map(d => {
                            let sp = parseFloat(d.spend || 0);
                            let gm = parseFloat(d.gmv || 0);
                            return sp > 0 ? parseFloat((gm/sp).toFixed(2)) : 0;
                        }),
                        borderColor: '#eab308', // Gold
                        backgroundColor: '#eab308',
                        fill: false,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#eab308',
                        yAxisID: 'y1'
                    },
                    {
                        label: 'GMV (Pendapatan)',
                        data: dailyData.map(d => parseFloat(d.gmv || 0)),
                        borderColor: '#16a34a',
                        backgroundColor: gradientGMV,
                        fill: true,
                        tension: 0.4, 
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#16a34a',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Biaya (Spend)',
                        data: dailyData.map(d => parseFloat(d.spend || 0)),
                        borderColor: '#dc2626',
                        backgroundColor: gradientSpend,
                        fill: true,
                        tension: 0.4, 
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#dc2626',
                        yAxisID: 'y'
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
                    legend: { 
                        position: 'top', 
                        labels: { usePointStyle: true, boxWidth: 6, font: { size: 11, family: 'Inter, sans-serif' } } 
                    },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipText,
                        bodyColor: tooltipText,
                        borderColor: tooltipBorder,
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true,
                        boxPadding: 4,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.dataset.yAxisID === 'y1') {
                                    return label + context.parsed.y + 'x';
                                } else {
                                    return label + formatFullIDR(context.parsed.y);
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: { 
                        grid: { display: false }, 
                        ticks: { font: { size: 10 } } 
                    },
                    y: { 
                        type: 'linear', 
                        position: 'left', 
                        beginAtZero: true, 
                        grid: { color: gridColor, drawBorder: false }, 
                        ticks: { 
                            font: { size: 10 }, 
                            padding: 8,
                            callback: function(value) { return formatShortIDR(value); }
                        }
                    },
                    y1: { 
                        type: 'linear', 
                        position: 'right', 
                        beginAtZero: true, 
                        grid: { drawOnChartArea: false, drawBorder: false }, 
                        ticks: { 
                            font: { size: 10 }, 
                            padding: 8,
                            callback: function(value) { return value + 'x'; }
                        }
                    },
                    y2: {
                        type: 'linear',
                        display: false,
                        position: 'left',
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // --- HOURLY BAR CHART ---
    const ctxHourly = document.getElementById("hourlyChart");
    if(ctxHourly) {
        new Chart(ctxHourly.getContext('2d'), {
            type: 'bar',
            data: {
                labels: hourlyData.map(d => d.performance_hour + ':00'),
                datasets: [
                    {
                        type: 'line',
                        label: 'Klik (Trafik)',
                        data: hourlyData.map(d => parseInt(d.clicks || 0)),
                        borderColor: 'rgba(148, 163, 184, 0)',
                        backgroundColor: 'rgba(148, 163, 184, 0.15)',
                        borderWidth: 0,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 0,
                        yAxisID: 'y2'
                    },
                    {
                        type: 'line',
                        label: 'ROAS',
                        data: hourlyData.map(d => {
                            let sp = parseFloat(d.expense || 0);
                            let gm = parseFloat(d.gmv || 0);
                            return sp > 0 ? parseFloat((gm/sp).toFixed(2)) : 0;
                        }),
                        borderColor: '#eab308',
                        backgroundColor: '#eab308',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        yAxisID: 'y1'
                    },
                    {
                        type: 'bar',
                        label: 'Biaya (Spend)',
                        data: hourlyData.map(d => parseFloat(d.expense || 0)),
                        backgroundColor: 'rgba(220, 38, 38, 0.85)',
                        borderRadius: 4,
                        yAxisID: 'y'
                    },
                    {
                        type: 'bar',
                        label: 'GMV (Pendapatan)',
                        data: hourlyData.map(d => parseFloat(d.gmv || 0)),
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                        borderRadius: 4,
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { 
                    legend: { 
                        display: true, 
                        position: 'top', 
                        labels: { usePointStyle: true, boxWidth: 6, font: { size: 10, family: 'Inter, sans-serif' } } 
                    },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipText,
                        bodyColor: tooltipText,
                        borderColor: tooltipBorder,
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.dataset.yAxisID === 'y1') {
                                    return label + context.parsed.y + 'x';
                                } else if (context.dataset.yAxisID === 'y2') {
                                    return label + context.parsed.y;
                                } else {
                                    return label + formatFullIDR(context.parsed.y);
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { 
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true, 
                        grid: { color: gridColor, drawBorder: false }, 
                        ticks: { 
                            font: { size: 10 }, 
                            padding: 8,
                            callback: function(value) { return formatShortIDR(value); }
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false, drawBorder: false },
                        ticks: { 
                            font: { size: 10 }, 
                            padding: 8,
                            callback: function(value) { return value + 'x'; }
                        }
                    },
                    y2: {
                        type: 'linear',
                        display: false,
                        position: 'left',
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // --- DAILY TRAFFIC CHART ---
    const ctxTraffic = document.getElementById("trafficChart");
    if(ctxTraffic) {
        const ctxTraffic2D = ctxTraffic.getContext('2d');
        
        let gradImp = ctxTraffic2D.createLinearGradient(0, 0, 0, 300);
        gradImp.addColorStop(0, 'rgba(245, 158, 11, 0.25)'); // Amber
        gradImp.addColorStop(1, 'rgba(245, 158, 11, 0.0)');
        
        let gradClick = ctxTraffic2D.createLinearGradient(0, 0, 0, 300);
        gradClick.addColorStop(0, 'rgba(59, 130, 246, 0.25)'); // Blue
        gradClick.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

        new Chart(ctxTraffic2D, {
            type: 'line',
            data: {
                labels: dailyData.map(d => {
                    const date = new Date(d.date);
                    return date.getDate() + ' ' + date.toLocaleString('id-ID', { month: 'short' });
                }),
                datasets: [
                    {
                        label: 'CTR',
                        data: dailyData.map(d => {
                            let imp = parseInt(d.impressions || 0);
                            let clk = parseInt(d.clicks || 0);
                            return imp > 0 ? parseFloat(((clk/imp)*100).toFixed(2)) : 0;
                        }),
                        borderColor: '#8b5cf6', // Violet
                        backgroundColor: '#8b5cf6',
                        fill: false,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        yAxisID: 'y2'
                    },
                    {
                        label: 'Klik',
                        data: dailyData.map(d => parseInt(d.clicks || 0)),
                        borderColor: '#3b82f6',
                        backgroundColor: gradClick,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Impression',
                        data: dailyData.map(d => parseInt(d.impressions || 0)),
                        borderColor: '#f59e0b',
                        backgroundColor: gradImp,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 6, font: { size: 11, family: 'Inter, sans-serif' } } },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipText,
                        bodyColor: tooltipText,
                        borderColor: tooltipBorder,
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true,
                        boxPadding: 4,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.dataset.yAxisID === 'y2') {
                                    return label + context.parsed.y + '%';
                                } else {
                                    return label + context.parsed.y.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { 
                        type: 'linear', position: 'left', beginAtZero: true, 
                        grid: { color: gridColor, drawBorder: false }, 
                        ticks: { font: { size: 10 }, padding: 8, callback: function(value) { return formatShortIDR(value); } } 
                    },
                    y1: { 
                        type: 'linear', position: 'right', beginAtZero: true, 
                        grid: { drawOnChartArea: false, drawBorder: false }, 
                        ticks: { font: { size: 10 }, padding: 8, callback: function(value) { return formatShortIDR(value); } }
                    },
                    y2: { type: 'linear', display: false, position: 'left', beginAtZero: true }
                }
            }
        });
    }
});
</script>
@endif
@endpush
