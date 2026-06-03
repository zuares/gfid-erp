@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
@endphp

{{-- ============ A. KPI Utama (snapshot kondisi produksi) ============ --}}
<div class="gf-overview-kpi-grid">
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Stok Siap Jahit</div>
        <div class="gf-overview-kpi-value">{{ $fmt($kpiA['siap_jahit']) }}</div>
        <div class="gf-overview-kpi-note">WIP-CUT siap dibagi</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Sedang Dijahit</div>
        <div class="gf-overview-kpi-value">{{ $fmt($kpiA['sedang_jahit']) }}</div>
        <div class="gf-overview-kpi-note">{{ $fmt($kpiA['penjahit_aktif']) }} penjahit aktif</div>
    </div>
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong">
        <div class="gf-overview-kpi-label">Disetor Hari Ini</div>
        <div class="gf-overview-kpi-value">{{ $fmt($kpiA['setor_today_ok']) }}</div>
        <div class="gf-overview-kpi-note">Reject {{ $fmt($kpiA['setor_today_reject']) }}</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Belum Disetor / Telat</div>
        <div class="gf-overview-kpi-value">{{ $fmt($kpiA['overdue']) }}</div>
        <div class="gf-overview-kpi-note">
            @if ($kpiA['overdue'] > 0)
                <span class="gf-badge gf-badge-red">menua</span>
            @else
                WIP jahit aman
            @endif
        </div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Prioritas Produksi</div>
        <div class="gf-overview-kpi-value">{{ $fmt($kpiA['priority_count']) }}</div>
        <div class="gf-overview-kpi-note">
            @if ($kpiA['priority_count'] > 0)
                <span class="gf-badge gf-badge-amber">cover tipis</span>
            @else
                cover aman
            @endif
        </div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Siap Masuk WH-PRD</div>
        <div class="gf-overview-kpi-value">{{ $fmt($kpiA['wh_prd']) }}</div>
        <div class="gf-overview-kpi-note">menunggu QC / siap jual</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Penjahit Aktif</div>
        <div class="gf-overview-kpi-value">{{ $fmt($kpiA['penjahit_aktif']) }}</div>
        <div class="gf-overview-kpi-note">pegang WIP jahit</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Rata-rata Performa</div>
        <div class="gf-overview-kpi-value">{{ $kpiA['avg_score'] === null ? '–' : $fmt($kpiA['avg_score'], 1) }}</div>
        <div class="gf-overview-kpi-note">
            @php $avg = $kpiA['avg_score']; @endphp
            @if ($avg === null)
                skor penjahit periode
            @elseif ($avg >= 70)
                <span class="gf-badge gf-badge-green">baik</span>
            @elseif ($avg >= 50)
                <span class="gf-badge gf-badge-amber">cukup</span>
            @else
                <span class="gf-badge gf-badge-red">perlu perhatian</span>
            @endif
        </div>
    </div>
</div>

{{-- ============ Funnel throughput periode (Cutting → Setor → Finishing) ============ --}}
<x-gf.panel title="Alur Produksi" subtitle="Periode terpilih">
    @php
        $steps = [
            ['Cutting OK', $summary['cutting_ok'], 'accent-blue'],
            ['Diambil Penjahit', $summary['pickup_total'], 'accent-blue'],
            ['Setor Jahit OK', $summary['sewing_ok'], 'accent-green'],
            ['Finishing OK', $summary['finishing_ok'], 'accent-green'],
        ];
        $funnelMax = max(1, collect($steps)->max(fn($s) => (float) $s[1]));
    @endphp
    <div class="gf-funnel">
        @foreach ($steps as [$label, $qty, $accent])
            <div class="gf-funnel-step {{ $accent }}">
                <div class="gf-funnel-label">{{ $label }}</div>
                <div class="gf-funnel-val">{{ $fmt($qty) }}</div>
                <div class="gf-bar-track">
                    <div class="gf-bar-fill" style="width: {{ round((float) $qty / $funnelMax * 100) }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="d-flex flex-wrap gap-3 mt-3 gf-subtext">
        <span>Reject total: <b class="text-danger">{{ $fmt($summary['total_reject']) }}</b> ({{ $summary['reject_rate'] }}%)</span>
        <span>Yield jahit: <b>{{ $summary['sewing_yield'] }}%</b></span>
        <span>Total output OK: <b>{{ $fmt($summary['total_ok']) }}</b></span>
    </div>
</x-gf.panel>

{{-- ============ Tren harian ============ --}}
<x-gf.panel title="Tren Harian" subtitle="Cutting & sewing per hari">
    @if ($dailyTrend->isEmpty())
        <div class="prod-empty">Tidak ada data pada periode ini.</div>
    @else
        @php $maxTrend = max(1, $dailyTrend->max(fn($r) => max($r->cutting_ok, $r->sewing_ok))); @endphp
        <div class="gf-table-scroll">
            <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th class="gf-num">Cutting OK</th>
                        <th class="gf-num">Sewing OK</th>
                        <th style="width:35%;">Sewing OK (visual)</th>
                        <th class="gf-num">Reject</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dailyTrend as $row)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row->date)->format('d M (D)') }}</td>
                            <td class="gf-num">{{ $fmt($row->cutting_ok) }}</td>
                            <td class="gf-num">{{ $fmt($row->sewing_ok) }}</td>
                            <td>
                                <div class="gf-bar-track">
                                    <div class="gf-bar-fill" style="width: {{ round($row->sewing_ok / $maxTrend * 100) }}%"></div>
                                </div>
                            </td>
                            <td class="gf-num">{{ $row->reject > 0 ? $fmt($row->reject) : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-gf.panel>
