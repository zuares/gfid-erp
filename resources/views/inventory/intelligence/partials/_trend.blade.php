@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $skuCount = $rows->count();
    $statusLabel = [
        'stockout' => 'Stockout',
        'kritis' => 'Kritis',
        'menipis' => 'Menipis',
        'sehat' => 'Sehat',
        'no_demand' => 'Tanpa demand',
    ];
    $dirMeta = [
        'up' => ['▲', 'ii-dir-up'],
        'down' => ['▼', 'ii-dir-down'],
        'flat' => ['→', 'ii-dir-flat'],
        'new' => ['★', 'ii-dir-new'],
    ];
    $labels = collect(range(29, 0))->map(fn($i) => \Carbon\Carbon::today()->subDays($i)->format('d/m'))->all();
    $payload = [
        'labels' => $labels,
        'items' => $rows->mapWithKeys(fn($r) => [
            $r->item_id => ['sku' => $r->sku, 'product' => $r->product, 'series' => $r->series],
        ])->all(),
    ];
@endphp

<div class="filter-bar mb-3">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <div class="filter-placeholder d-flex flex-wrap align-items-center gap-2"></div>
        <div class="vr mx-1 d-none d-md-block" style="opacity: .15;"></div>
        <input type="search" class="form-control form-control-sm ii-search" data-ii-search
            placeholder="Cari SKU / produk / kategori…" autocomplete="off" style="max-width:200px;">

        <select class="form-select form-select-sm" data-ii-status aria-label="Status" style="max-width:140px;">
            <option value="">Semua Status</option>
            <option value="stockout">Stockout</option>
            <option value="kritis">Kritis</option>
            <option value="menipis">Menipis</option>
            <option value="sehat">Sehat</option>
            <option value="no_demand">Tanpa demand</option>
        </select>

        <select class="form-select form-select-sm" data-ii-sort aria-label="Urutkan" style="max-width:180px;">
            <option value="ads-desc" selected>Jual/hari tertinggi</option>
            <option value="trend-desc">Paling naik</option>
            <option value="trend-asc">Paling turun</option>
            <option value="score-desc">Skor evaluasi tertinggi</option>
            <option value="sku-asc">SKU A–Z</option>
        </select>

        <span class="ii-count ms-auto" data-ii-count>{{ $fmt($skuCount) }} SKU</span>
    </div>
</div>

<div class="card-main">
    @if ($rows->isEmpty())
        <div class="ii-empty">Tidak ada data penjualan untuk filter ini.</div>
    @else
        <div class="ii-trend-chart">
            <div class="ii-trend-chart-head">
                <span class="ii-trend-title" data-ii-trend-title>—</span>
                <span class="ii-trend-legend">
                    Naik {{ $fmt($trendSummary['up']) }} · Turun {{ $fmt($trendSummary['down']) }} · Datar {{ $fmt($trendSummary['flat']) }}
                </span>
            </div>
            <div class="ii-trend-canvas-wrap"><canvas data-ii-trend-canvas></canvas></div>
        </div>
        <script type="application/json" data-ii-trend-data>@json($payload)</script>

        <div class="table-responsive ii-table-scroll" style="max-height: 50vh;">
            <table class="table table-hover align-middle table-list" data-ii-table id="table-trend" data-ii-default-sort="ads30" data-ii-default-dir="desc">
                <thead style="background: rgba(241, 245, 249, 0.5);">
                    <tr>
                        <th data-ii-sort-key="sku" data-ii-sort-type="text" style="padding-left: 1.25rem;">SKU & Produk</th>
                        <th data-ii-sort-key="delta" data-ii-sort-type="number">Arah (7hr)</th>
                        <th data-ii-sort-key="ads7" data-ii-sort-type="number" class="text-end gf-hide-mobile">ADS 7</th>
                        <th data-ii-sort-key="ads14" data-ii-sort-type="number" class="text-end gf-hide-mobile">ADS 14</th>
                        <th data-ii-sort-key="ads30" data-ii-sort-type="number" class="text-end">ADS 30</th>
                        <th data-ii-sort-key="score" data-ii-sort-type="number" class="text-end gf-hide-mobile">Skor</th>
                        <th data-ii-sort-key="status" data-ii-sort-type="status" class="gf-hide-mobile" style="padding-right: 1.25rem;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        @php $dm = $dirMeta[$r->direction] ?? $dirMeta['flat']; @endphp
                        <tr data-ii-row data-ii-trend-id="{{ $r->item_id }}"
                            data-search="{{ strtolower(trim($r->sku . ' ' . $r->product . ' ' . $r->category)) }}"
                            data-status="{{ $r->status }}"
                            data-sku="{{ $r->sku }}"
                            data-ads7="{{ $r->ads7 }}"
                            data-ads14="{{ $r->ads14 }}"
                            data-ads30="{{ $r->ads30 }}"
                            data-ads="{{ $r->ads30 }}"
                            data-score="{{ $r->eval_score }}"
                            data-delta="{{ $r->direction === 'new' ? 999999 : ($r->delta_pct ?? 0) }}"
                            data-direction="{{ $r->direction }}">
                            
                            <td style="padding-left: 1.25rem;">
                                <span class="fw-semibold">{{ $r->sku }}</span>
                                <div class="text-muted-ii" style="font-size: .7rem;">{{ $r->product }}</div>
                                <div class="text-muted-ii mt-1" style="font-size: .65rem;">{{ $r->category }}</div>
                            </td>
                            
                            <td>
                                <span class="ii-dir {{ $dm[1] }}">{{ $dm[0] }}
                                    @if ($r->direction === 'new')
                                        Baru
                                    @elseif ($r->delta_pct === null)
                                        –
                                    @else
                                        {{ $r->delta_pct > 0 ? '+' : '' }}{{ $fmt($r->delta_pct, 0) }}%
                                    @endif
                                </span>
                            </td>
                            <td class="text-end gf-hide-mobile text-muted">{{ $fmt($r->ads7, 1) }}</td>
                            <td class="text-end gf-hide-mobile text-muted">{{ $fmt($r->ads14, 1) }}</td>
                            <td class="text-end fw-semibold">{{ $fmt($r->ads30, 1) }}</td>
                            <td class="text-end gf-hide-mobile">
                                @php $sc = $r->eval_score; $scb = $sc >= 70 ? 'ii-score-high' : ($sc >= 40 ? 'ii-score-mid' : 'ii-score-low'); @endphp
                                <span class="ii-score {{ $scb }}">{{ $sc }}</span>
                            </td>
                            <td class="gf-hide-mobile" style="padding-right: 1.25rem;">
                                <span class="badge-status st-{{ $r->status }}">
                                    {{ $statusLabel[$r->status] ?? $r->status }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="ii-empty" data-ii-empty hidden>Tidak ada SKU yang cocok dengan filter.</div>
    @endif
</div>
