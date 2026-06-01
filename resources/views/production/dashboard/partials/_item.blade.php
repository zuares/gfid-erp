@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
@endphp

<div class="row g-2">
    <div class="col-12 col-lg-6">
        <div class="card-main p-3 h-100">
            <h2 class="h6 mb-2">Sewing per Item</h2>
            @if ($perItem['sewing']->isEmpty())
                <div class="empty">Tidak ada data.</div>
            @else
                <div class="table-responsive">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="num">OK</th>
                                <th class="num">Reject</th>
                                <th class="num">Yield</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($perItem['sewing'] as $r)
                                @php
                                    $base = $r->total_ok + $r->total_reject;
                                    $yld = $base > 0 ? round($r->total_ok / $base * 100) : 0;
                                @endphp
                                <tr>
                                    <td><span class="chip" title="{{ $r->item_name }}"><b>{{ $r->item_code }}</b></span></td>
                                    <td class="num">{{ $fmt($r->total_ok) }}</td>
                                    <td class="num">{{ $r->total_reject > 0 ? $fmt($r->total_reject) : '-' }}</td>
                                    <td class="num">
                                        <div class="d-flex align-items-center gap-2 justify-content-end">
                                            <div class="prog"><span style="width: {{ $yld }}%"></span></div>
                                            <span>{{ $yld }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card-main p-3 h-100">
            <h2 class="h6 mb-2">Finishing per Item</h2>
            @if ($perItem['finishing']->isEmpty())
                <div class="empty">Tidak ada data.</div>
            @else
                <div class="table-responsive">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="num">In</th>
                                <th class="num">OK</th>
                                <th class="num">Reject</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($perItem['finishing'] as $r)
                                <tr>
                                    <td><span class="chip" title="{{ $r->item_name }}"><b>{{ $r->item_code }}</b></span></td>
                                    <td class="num">{{ $fmt($r->total_in) }}</td>
                                    <td class="num">{{ $fmt($r->total_ok) }}</td>
                                    <td class="num">{{ $r->total_reject > 0 ? $fmt($r->total_reject) : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
