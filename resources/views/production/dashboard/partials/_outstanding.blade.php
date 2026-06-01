@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
@endphp

<div class="card-main p-3 mb-3">
    <h2 class="h6 mb-2">Outstanding WIP Sewing per Umur</h2>
    <div class="kpi-grid">
        <div class="kpi accent-green">
            <div class="label">0–3 hari</div>
            <div class="val">{{ $fmt($outstanding['buckets']['b0_3']) }}</div>
        </div>
        <div class="kpi accent-blue">
            <div class="label">4–7 hari</div>
            <div class="val">{{ $fmt($outstanding['buckets']['b4_7']) }}</div>
        </div>
        <div class="kpi accent-amber">
            <div class="label">8–14 hari</div>
            <div class="val">{{ $fmt($outstanding['buckets']['b8_14']) }}</div>
        </div>
        <div class="kpi accent-red">
            <div class="label">15+ hari</div>
            <div class="val">{{ $fmt($outstanding['buckets']['b15p']) }}</div>
        </div>
        <div class="kpi">
            <div class="label">Total Outstanding</div>
            <div class="val">{{ $fmt($outstanding['total']) }}</div>
        </div>
    </div>
</div>

<div class="card-main p-3">
    <h2 class="h6 mb-2">Detail Outstanding per Operator & Item</h2>
    @if ($outstanding['detail']->isEmpty())
        <div class="empty">Tidak ada outstanding.</div>
    @else
        <div class="table-responsive">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Operator</th>
                        <th>Item</th>
                        <th class="num">Outstanding</th>
                        <th class="num">Umur Maks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($outstanding['detail'] as $d)
                        <tr>
                            <td>{{ $d->operator_code }} — {{ $d->operator_name }}</td>
                            <td><span class="chip" title="{{ $d->item_name }}"><b>{{ $d->item_code }}</b></span></td>
                            <td class="num">{{ $fmt($d->outstanding) }}</td>
                            <td class="num">
                                <span class="age-pill {{ $d->max_aging >= 8 ? 'age-old' : '' }}">
                                    {{ $d->max_aging !== null ? $d->max_aging . ' hari' : '-' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
