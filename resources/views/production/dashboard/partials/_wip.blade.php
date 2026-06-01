@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
@endphp

<div class="row g-2 mb-3">
    @php
        $stages = [
            ['WIP Cutting', $wipFlow['totals']['cut'], $wipFlow['top_cut']],
            ['WIP Sewing', $wipFlow['totals']['sew'], $wipFlow['top_sew']],
            ['WIP Finishing', $wipFlow['totals']['fin'], $wipFlow['top_fin']],
        ];
    @endphp
    @foreach ($stages as [$name, $total, $tops])
        <div class="col-12 col-md-4">
            <div class="stage-card h-100">
                <h3>{{ $name }}</h3>
                <div class="big">{{ $fmt($total) }}</div>
                <div class="small text-muted mb-2">total qty di gudang</div>
                @php $maxTop = max(1, $tops->max('qty') ?? 1); @endphp
                @forelse ($tops as $t)
                    <div class="bar-row">
                        <span class="chip"><b>{{ $t->item_code }}</b></span>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: {{ round($t->qty / $maxTop * 100) }}%"></div>
                        </div>
                        <span>{{ $fmt($t->qty) }}</span>
                    </div>
                @empty
                    <div class="small text-muted">Kosong</div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>

<div class="card-main p-3">
    <h2 class="h6 mb-2">WIP Sewing Terlama (Aging)</h2>
    @if ($wipFlow['aging']->isEmpty())
        <div class="empty">Tidak ada WIP sewing outstanding.</div>
    @else
        <div class="table-responsive">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Item / Bundle</th>
                        <th>Operator</th>
                        <th class="num">Qty WIP</th>
                        <th class="num">Umur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($wipFlow['aging'] as $a)
                        <tr>
                            <td><span class="chip"><b>{{ $a->ref }}</b></span></td>
                            <td>{{ $a->operator }}</td>
                            <td class="num">{{ $fmt($a->qty) }}</td>
                            <td class="num">
                                <span class="age-pill {{ $a->age_days >= 8 ? 'age-old' : '' }}">
                                    {{ $a->age_days !== null ? $a->age_days . ' hari' : '-' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
