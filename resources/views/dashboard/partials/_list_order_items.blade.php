@php
    /** Var: title, rows (array), link (url|null) */
    $qty = fn ($value) => number_format((float) $value, 0, ',', '.');
@endphp
<div class="dpanel">
    <div class="dpanel-head">
        <div class="t"><i class="bi bi-box-seam"></i> {{ $title }}</div>
        @if(!empty($link))<a href="{{ $link }}">Lihat semua <i class="bi bi-arrow-right-short"></i></a>@endif
    </div>
    <div class="dpanel-body">
        @if(!empty($rows))
            <div class="ditem-table-wrap">
                <table class="ditem-table">
                    <thead>
                        <tr>
                            <th class="numeric">No</th>
                            <th>Kode item internal</th>
                            <th class="numeric">Permintaan</th>
                            <th class="numeric">Stok FG/RTS</th>
                            <th class="numeric">Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @php
                                $demand = (float) ($row['demand_qty'] ?? 0);
                                $stock = $row['stock_qty'] ?? null;
                                $short = $stock !== null && $stock < $demand;
                                $gap = $stock === null ? null : $stock - $demand;
                            @endphp
                            <tr>
                                <td class="numeric ditem-number">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="ditem-code">{{ $row['code'] ?: 'Belum mapping' }}</div>
                                </td>
                                <td class="numeric ditem-demand">{{ $qty($demand) }} pcs</td>
                                <td class="numeric ditem-stock {{ $short ? 'short' : '' }}">
                                    {{ $stock === null ? '—' : $qty($stock) . ' pcs' }}
                                </td>
                                <td class="numeric ditem-gap">
                                    @php($gapState = $gap === null ? 'unknown' : ($gap < 0 ? 'short' : 'safe'))
                                    <span class="ditem-gap-indicator {{ $gapState }}"
                                          title="{{ $gapState === 'safe' ? 'Stok mencukupi' : ($gapState === 'short' ? 'Stok kurang' : 'Stok belum diketahui') }}">
                                        <i class="bi {{ $gapState === 'safe' ? 'bi-check-circle-fill' : ($gapState === 'short' ? 'bi-exclamation-circle-fill' : 'bi-question-circle') }} ditem-gap-icon" aria-hidden="true"></i>
                                        <span>{{ $gap === null ? '—' : (($gap > 0 ? '+' : '') . $qty($gap)) }}</span>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="dash-empty"><i class="bi bi-check2-circle"></i> Tidak ada permintaan yang menunggu. Aman!</div>
        @endif
    </div>
</div>
