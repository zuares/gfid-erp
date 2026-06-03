@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $gradeClass = fn($g) => match ($g) {
        'A' => 'gf-badge-green',
        'B' => 'gf-badge-blue',
        'C' => 'gf-badge-amber',
        'D' => 'gf-badge-red',
        default => 'gf-badge-muted',
    };
@endphp

<x-gf.panel title="Performa Penjahit" subtitle="Skor, efisiensi & lead time periode">
    @if ($operators->isEmpty())
        <div class="prod-empty">Tidak ada data penjahit pada periode ini.</div>
    @else
        @php $maxScore = max(1, $operators->max(fn($o) => (float) $o->score)); @endphp
        <div class="gf-table-scroll">
            <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table">
                <thead>
                    <tr>
                        <th>Penjahit</th>
                        <th class="gf-num">Ambilan</th>
                        <th class="gf-num">Diambil</th>
                        <th class="gf-num">OK</th>
                        <th class="gf-num">Reject</th>
                        <th class="gf-num">Sisa</th>
                        <th class="gf-num">Efisiensi</th>
                        <th class="gf-num">Lead (hr)</th>
                        <th class="gf-num">Skor</th>
                        <th style="width:18%;">Skor (visual)</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($operators as $o)
                        <tr>
                            <td>
                                <span class="gf-chip" title="{{ $o->operator_name }}"><b>{{ $o->operator_code }}</b></span>
                                <span class="text-muted small d-block">{{ $o->operator_name }}</span>
                            </td>
                            <td class="gf-num">{{ $fmt($o->total_pickups) }}</td>
                            <td class="gf-num">{{ $fmt($o->picked) }}</td>
                            <td class="gf-num">{{ $fmt($o->total_ok) }}</td>
                            <td class="gf-num">{{ $o->total_reject > 0 ? $fmt($o->total_reject) : '-' }}</td>
                            <td class="gf-num">{{ $fmt($o->outstanding) }}</td>
                            <td class="gf-num">{{ $o->efficiency === null ? '–' : $fmt($o->efficiency, 1) . '%' }}</td>
                            <td class="gf-num">{{ $o->avg_lead_days === null ? '–' : $fmt($o->avg_lead_days, 1) }}</td>
                            <td class="gf-num"><b>{{ $fmt($o->score, 1) }}</b></td>
                            <td>
                                <div class="gf-bar-track">
                                    <div class="gf-bar-fill" style="width: {{ round((float) $o->score / $maxScore * 100) }}%"></div>
                                </div>
                            </td>
                            <td><span class="gf-badge {{ $gradeClass($o->grade) }}">{{ $o->grade }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-gf.panel>
