@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
@endphp

{{-- KPI OPERASIONAL (snapshot stok per status) --}}
<div class="kpi-grid mb-3">
    <div class="kpi accent-blue">
        <div class="label">Siap Jahit</div>
        <div class="val">{{ $fmt($kpi['siap_jahit']) }}</div>
        <div class="sub">WIP-CUT · siap diambil penjahit</div>
    </div>
    <div class="kpi accent-amber">
        <div class="label">Sedang Jahit</div>
        <div class="val">{{ $fmt($kpi['sedang_jahit']) }}</div>
        <div class="sub">WIP-SEW · sedang dikerjakan</div>
    </div>
    <div class="kpi accent-green">
        <div class="label">WH-PRD</div>
        <div class="val">{{ $fmt($kpi['wh_prd']) }}</div>
        <div class="sub">Gudang produksi</div>
    </div>
    <div class="kpi accent-red">
        <div class="label">Overdue</div>
        <div class="val">{{ $fmt($kpi['overdue']) }}</div>
        <div class="sub">WIP jahit &gt; 14 hari</div>
    </div>
    <div class="kpi accent-red">
        <div class="label">High Priority</div>
        <div class="val">{{ $fmt($kpi['high_priority']) }}</div>
        <div class="sub">SKU cover ready &lt; 7 hari</div>
    </div>
</div>

{{-- RINGKASAN SKU PRODUKSI --}}
<div class="card-main p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <h2 class="h6 mb-0">Ringkasan SKU Produksi</h2>
        <span class="small text-muted">Snapshot stok saat ini · {{ $skuSummary->count() }} SKU</span>
    </div>
    @if ($skuSummary->isEmpty())
        <div class="empty">Tidak ada stok produksi pada filter ini.</div>
    @else
        <div class="table-responsive">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>SKU / Produk</th>
                        <th>Kategori</th>
                        <th class="num">Siap Jahit</th>
                        <th class="num">Sedang Jahit</th>
                        <th class="num">WH-PRD</th>
                        <th class="num">Ready</th>
                        <th class="num">Jual 7h</th>
                        <th class="num">Cover</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($skuSummary as $r)
                        <tr>
                            <td>
                                <span class="chip" title="{{ $r->product }}"><b>{{ $r->sku }}</b></span>
                                @if ($r->is_priority)
                                    <span class="badge-grade g-risk ms-1">Prioritas</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $r->category }}</td>
                            <td class="num">{{ $r->siap_jahit > 0 ? $fmt($r->siap_jahit) : '-' }}</td>
                            <td class="num">{{ $r->sedang_jahit > 0 ? $fmt($r->sedang_jahit) : '-' }}</td>
                            <td class="num">{{ $r->wh_prd > 0 ? $fmt($r->wh_prd) : '-' }}</td>
                            <td class="num">{{ $r->ready > 0 ? $fmt($r->ready) : '-' }}</td>
                            <td class="num">{{ $r->sales_7d > 0 ? $fmt($r->sales_7d) : '-' }}</td>
                            <td class="num">
                                @if ($r->cover_days === null)
                                    <span class="text-muted">-</span>
                                @else
                                    <span class="age-pill {{ $r->is_priority ? 'age-old' : '' }}">{{ $fmt($r->cover_days, 1) }} hr</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="small text-muted mt-2">
            Cover = stok Ready ÷ laju jual 7 hari. SKU dengan cover &lt; 7 hari ditandai <b>Prioritas</b>.
        </div>
    @endif
</div>
