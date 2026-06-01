@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
@endphp

<div class="card-main p-3">
    <h2 class="h6 mb-2">Performa Operator Sewing</h2>
    @if ($operators->isEmpty())
        <div class="empty">Tidak ada aktivitas operator pada periode ini.</div>
    @else
        <div class="table-responsive">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Operator</th>
                        <th class="num">Pickup</th>
                        <th class="num">OK</th>
                        <th class="num">Reject</th>
                        <th class="num">Outstanding</th>
                        <th class="num">Efisiensi</th>
                        <th class="num">Lead Time</th>
                        <th class="num">Skor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($operators as $o)
                        @php
                            $gradeClass = match ($o->grade) {
                                'Excellent' => 'g-excellent',
                                'Good' => 'g-good',
                                'Cukup' => 'g-cukup',
                                default => 'g-risk',
                            };
                        @endphp
                        <tr>
                            <td>{{ $o->operator_code }} — {{ $o->operator_name }}</td>
                            <td class="num">{{ $fmt($o->picked) }}</td>
                            <td class="num">{{ $fmt($o->total_ok) }}</td>
                            <td class="num">{{ $o->total_reject > 0 ? $fmt($o->total_reject) : '-' }}</td>
                            <td class="num">{{ $fmt($o->outstanding) }}</td>
                            <td class="num">{{ $o->efficiency !== null ? $o->efficiency . '%' : '-' }}</td>
                            <td class="num">{{ $o->avg_lead_days !== null ? $o->avg_lead_days . ' hr' : '-' }}</td>
                            <td class="num">
                                <span class="badge-grade {{ $gradeClass }}">{{ $o->score }} · {{ $o->grade }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="small text-muted mt-2">
            Skor 0–100 gabungan: penyelesaian (40), reject (25), outstanding (20), lead time (15).
        </div>
    @endif
</div>
