@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
@endphp

<div class="card-main p-3 mb-3">
    <div class="kpi-grid">
        <div class="kpi accent-blue">
            <div class="label">Cutting OK</div>
            <div class="val">{{ $fmt($summary['cutting_ok']) }}</div>
            <div class="sub">Reject {{ $fmt($summary['cutting_reject']) }}</div>
        </div>
        <div class="kpi accent-blue">
            <div class="label">Pickup Sewing</div>
            <div class="val">{{ $fmt($summary['pickup_total']) }}</div>
            <div class="sub">qty diambil operator</div>
        </div>
        <div class="kpi accent-green">
            <div class="label">Setor Sewing OK</div>
            <div class="val">{{ $fmt($summary['sewing_ok']) }}</div>
            <div class="sub">Yield {{ $summary['sewing_yield'] }}% • R {{ $fmt($summary['sewing_reject']) }}</div>
        </div>
        <div class="kpi accent-green">
            <div class="label">Finishing OK</div>
            <div class="val">{{ $fmt($summary['finishing_ok']) }}</div>
            <div class="sub">In {{ $fmt($summary['finishing_in']) }} • R {{ $fmt($summary['finishing_reject']) }}</div>
        </div>
        <div class="kpi accent-red">
            <div class="label">Total Reject</div>
            <div class="val">{{ $fmt($summary['total_reject']) }}</div>
            <div class="sub">{{ $summary['reject_rate'] }}% dari output</div>
        </div>
        <div class="kpi accent-amber">
            <div class="label">Total Output OK</div>
            <div class="val">{{ $fmt($summary['total_ok']) }}</div>
            <div class="sub">cut + sew + fin</div>
        </div>
    </div>
</div>

<div class="card-main p-3">
    <h2 class="h6 mb-2">Tren Harian</h2>
    @php $maxTrend = max(1, $dailyTrend->max(fn($r) => max($r->cutting_ok, $r->sewing_ok))); @endphp
    @if ($dailyTrend->isEmpty())
        <div class="empty">Tidak ada data pada periode ini.</div>
    @else
        <div class="table-responsive">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th class="num">Cutting OK</th>
                        <th class="num">Sewing OK</th>
                        <th style="width:35%;">Sewing OK (visual)</th>
                        <th class="num">Reject</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dailyTrend as $row)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row->date)->format('d M (D)') }}</td>
                            <td class="num">{{ $fmt($row->cutting_ok) }}</td>
                            <td class="num">{{ $fmt($row->sewing_ok) }}</td>
                            <td>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: {{ round($row->sewing_ok / $maxTrend * 100) }}%"></div>
                                </div>
                            </td>
                            <td class="num">{{ $row->reject > 0 ? $fmt($row->reject) : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
