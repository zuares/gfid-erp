@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
@endphp

<div class="row g-2 mb-3">
    <div class="col-12 col-md-6">
        <div class="card-main p-3 h-100">
            <h2 class="h6 mb-2">Reject per Operator</h2>
            @if ($reject['by_operator']->isEmpty())
                <div class="empty">Tidak ada reject.</div>
            @else
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Operator</th>
                            <th class="num">Reject</th>
                            <th class="num">OK</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reject['by_operator']->take(12) as $r)
                            <tr>
                                <td>{{ $r->operator_name }}</td>
                                <td class="num text-danger fw-bold">{{ $fmt($r->total_reject) }}</td>
                                <td class="num">{{ $fmt($r->total_ok) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card-main p-3 h-100">
            <h2 class="h6 mb-2">Reject per Item</h2>
            @if ($reject['by_item']->isEmpty())
                <div class="empty">Tidak ada reject.</div>
            @else
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="num">Reject</th>
                            <th class="num">OK</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reject['by_item']->take(12) as $r)
                            <tr>
                                <td><span class="chip" title="{{ $r->item_name }}"><b>{{ $r->item_code }}</b></span></td>
                                <td class="num text-danger fw-bold">{{ $fmt($r->total_reject) }}</td>
                                <td class="num">{{ $fmt($r->total_ok) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>

<div class="card-main p-3">
    <h2 class="h6 mb-2">Detail Reject ({{ $fmt($reject['total_reject']) }} total)</h2>
    @if ($reject['detail']->isEmpty())
        <div class="empty">Tidak ada reject pada periode ini.</div>
    @else
        <div class="table-responsive">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tahap</th>
                        <th>Item</th>
                        <th>Operator</th>
                        <th class="num">Reject</th>
                        <th>Ref</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reject['detail'] as $d)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($d->date)->format('d M Y') }}</td>
                            <td><span class="chip">{{ ucfirst($d->stage) }}</span></td>
                            <td><span class="chip" title="{{ $d->item_name }}"><b>{{ $d->item_code }}</b></span></td>
                            <td>{{ $d->operator_name }}</td>
                            <td class="num text-danger fw-bold">{{ $fmt($d->qty_reject) }}</td>
                            <td>{{ $d->ref_code }}</td>
                            <td class="text-muted">{{ \Illuminate\Support\Str::limit($d->notes ?? '', 40) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
