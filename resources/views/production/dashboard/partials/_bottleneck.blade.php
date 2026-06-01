@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');

    $days = $bottleneck['days'];
    $threshold = $bottleneck['threshold'];
    $queues = $bottleneck['queues'];
    $top = $bottleneck['bottleneck'];
    $funnel = $bottleneck['funnel'];
    $funnelMax = $bottleneck['funnel_max'];
    $rtsStock = $bottleneck['rts_stock'];
    $cutRate = $bottleneck['cut_rate'];
    $sewRate = $bottleneck['sew_rate'];
    $finRate = $bottleneck['fin_rate'];
@endphp

{{-- ALERT BOTTLENECK / DAYS-OF-COVER --}}
@if ($top)
    <div class="card-main p-3 mb-3" style="border-left:4px solid #ef4444;">
        <div class="d-flex align-items-start gap-2 flex-wrap justify-content-between">
            <div>
                <h2 class="h6 mb-1">Bottleneck Terdeteksi · {{ $top->stage }}</h2>
                <div class="small text-muted">
                    {{ $top->queue_label }} menumpuk <b>{{ $fmt($top->backlog) }}</b> pcs.
                    @if ($top->days !== null)
                        Dengan laju output {{ $fmt($top->rate, 1) }}/hari, perlu
                        <b class="text-danger">±{{ $fmt($top->days, 1) }} hari</b> untuk dikosongkan.
                    @else
                        <b class="text-danger">Tidak ada output</b> pada periode ini — antrean mandek.
                    @endif
                </div>
            </div>
            <span class="badge-grade g-risk">Perlu Tindakan</span>
        </div>
    </div>
@else
    <div class="card-main p-3 mb-3" style="border-left:4px solid #22c55e;">
        <h2 class="h6 mb-1">Pipeline Lancar</h2>
        <div class="small text-muted">Tidak ada backlog WIP signifikan pada periode ini.</div>
    </div>
@endif

{{-- FUNNEL THROUGHPUT --}}
<div class="card-main p-3 mb-3">
    <h2 class="h6 mb-2">Funnel Output ({{ $fmt($days) }} hari)</h2>
    @foreach ($funnel as $row)
        @php $pct = round($row->qty / $funnelMax * 100); @endphp
        <div class="bar-row">
            <div style="min-width:120px;font-weight:800;">{{ $row->label }}</div>
            <div class="bar-track">
                <div class="bar-fill" style="width: {{ $pct }}%"></div>
            </div>
            <div style="min-width:80px;text-align:right;font-weight:800;">{{ $fmt($row->qty) }}</div>
        </div>
    @endforeach
    <div class="small text-muted mt-2">
        Konversi antar-stage memakai kuantitas OK dalam periode. Laju harian:
        Cutting {{ $fmt($cutRate, 1) }} · Sewing {{ $fmt($sewRate, 1) }} · Finishing {{ $fmt($finRate, 1) }} pcs/hari.
    </div>
</div>

{{-- ANTREAN WIP + DAYS-OF-COVER --}}
<div class="card-main p-3 mb-3">
    <h2 class="h6 mb-2">Antrean WIP per Stage &amp; Hari Pengosongan</h2>
    <div class="table-responsive">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Stage</th>
                    <th>Antrean</th>
                    <th class="num">Backlog</th>
                    <th class="num">Laju/Hari</th>
                    <th class="num">Hari Kosong</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($queues as $q)
                    @php
                        $stalled = $q->backlog > 0 && $q->rate <= 0;
                        $hot = $stalled || ($q->days !== null && $q->days >= $threshold);
                    @endphp
                    <tr>
                        <td class="fw-bold">{{ $q->stage }}</td>
                        <td class="text-muted small">{{ $q->queue_label }}</td>
                        <td class="num">{{ $fmt($q->backlog) }}</td>
                        <td class="num">{{ $fmt($q->rate, 1) }}</td>
                        <td class="num">
                            <span class="age-pill {{ $hot ? 'age-old' : '' }}">
                                {{ $q->days !== null ? $fmt($q->days, 1) . ' hr' : ($q->backlog > 0 ? 'mandek' : '-') }}
                            </span>
                        </td>
                        <td>
                            @if ($stalled)
                                <span class="badge-grade g-risk">Mandek</span>
                            @elseif ($hot)
                                <span class="badge-grade g-cukup">Menumpuk</span>
                            @elseif ($q->backlog > 0)
                                <span class="badge-grade g-good">Normal</span>
                            @else
                                <span class="badge-grade g-excellent">Kosong</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="small text-muted mt-2">
        Ambang alert: antrean &ge; {{ $threshold }} hari output, atau ada backlog tanpa output (mandek).
        FG siap (WH-RTS): <b>{{ $fmt($rtsStock) }}</b> pcs.
    </div>
</div>

{{-- WIDGET BELUM AKTIF --}}
<div class="card-main p-3">
    <h2 class="h6 mb-2">Widget Belum Aktif</h2>
    <p class="small text-muted mb-2">
        Widget berikut diminta namun belum dapat dibangun karena data sumbernya belum tersedia.
        Disembunyikan agar dashboard tidak menampilkan angka palsu.
    </p>
    <div class="kpi-grid">
        @php
            $inactive = [
                ['Order Aktif', 'Tabel production_orders kosong — produksi digerakkan langsung oleh Cutting Job.'],
                ['Delivery Due Date', 'Tidak ada data tanggal jatuh tempo pengiriman / sales order.'],
                ['On Time Delivery', 'Tidak ada baseline due-date vs aktual untuk dihitung.'],
                ['Line Produksi', 'Tidak ada master line/mesin produksi.'],
                ['Utilisasi Produksi', 'Tidak ada kapasitas / jam kerja standar sebagai pembagi.'],
            ];
        @endphp
        @foreach ($inactive as [$name, $why])
            <div class="kpi" style="opacity:.7;border-left:4px solid rgba(148,163,184,.5);">
                <div class="label">{{ $name }}</div>
                <div class="sub" style="margin-top:.35rem;">{{ $why }}</div>
                <span class="badge-grade"
                    style="background:rgba(148,163,184,.18);color:var(--muted);margin-top:.4rem;">Belum aktif</span>
            </div>
        @endforeach
    </div>
</div>
