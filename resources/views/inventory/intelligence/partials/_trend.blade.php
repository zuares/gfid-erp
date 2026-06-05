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

<x-gf.panel title="Tren Permintaan per SKU"
    subtitle="Penjualan harian 30 hari. Arah = qty 7 hari terakhir vs 7 hari sebelumnya. Klik SKU untuk lihat grafiknya.">
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

        <div class="ii-toolbar">
            <input type="search" class="form-control ii-search" data-ii-search
                placeholder="Cari SKU / produk / kategori…" autocomplete="off">

            <select class="form-select" data-ii-status aria-label="Status">
                <option value="">Semua Status</option>
                <option value="stockout">Stockout</option>
                <option value="kritis">Kritis</option>
                <option value="menipis">Menipis</option>
                <option value="sehat">Sehat</option>
                <option value="no_demand">Tanpa demand</option>
            </select>

            <select class="form-select" data-ii-sort aria-label="Urutkan">
                <option value="trend-desc">Paling naik</option>
                <option value="trend-asc">Paling turun</option>
                <option value="score-desc">Skor evaluasi tertinggi</option>
                <option value="ads-desc">Jual/hari tertinggi</option>
                <option value="sku-asc">SKU A–Z</option>
            </select>

            <span class="ii-count" data-ii-count>{{ $fmt($skuCount) }} SKU</span>
        </div>

        <div class="gf-table-scroll gf-table-scroll-sticky">
            <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table" data-ii-table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th class="gf-hide-mobile">Kategori</th>
                        <th>Arah (7hr)</th>
                        <th class="gf-num gf-hide-mobile">ADS 7</th>
                        <th class="gf-num gf-hide-mobile">ADS 14</th>
                        <th class="gf-num">ADS 30</th>
                        <th class="gf-num gf-hide-mobile">Skor</th>
                        <th class="gf-hide-mobile">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        @php $dm = $dirMeta[$r->direction] ?? $dirMeta['flat']; @endphp
                        <tr data-ii-row data-ii-trend-id="{{ $r->item_id }}"
                            data-search="{{ strtolower(trim($r->sku . ' ' . $r->product . ' ' . $r->category)) }}"
                            data-status="{{ $r->status }}"
                            data-sku="{{ $r->sku }}"
                            data-ads="{{ $r->ads }}"
                            data-score="{{ $r->eval_score }}"
                            data-delta="{{ $r->direction === 'new' ? 999999 : ($r->delta_pct ?? 0) }}">
                            <td><span class="gf-chip" title="{{ $r->product }}"><b>{{ $r->sku }}</b></span></td>
                            <td class="text-muted gf-hide-mobile">{{ $r->category }}</td>
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
                            <td class="gf-num gf-hide-mobile">{{ $fmt($r->ads7, 1) }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $fmt($r->ads14, 1) }}</td>
                            <td class="gf-num">{{ $fmt($r->ads30, 1) }}</td>
                            <td class="gf-num gf-hide-mobile">
                                @php $sc = $r->eval_score; $scb = $sc >= 70 ? 'ii-score-high' : ($sc >= 40 ? 'ii-score-mid' : 'ii-score-low'); @endphp
                                <span class="ii-score {{ $scb }}">{{ $sc }}</span>
                            </td>
                            <td class="gf-hide-mobile">
                                <span class="ii-status ii-status-{{ $r->status }}">
                                    <span class="ii-status-dot"></span>{{ $statusLabel[$r->status] ?? $r->status }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="ii-empty" data-ii-empty hidden>Tidak ada SKU yang cocok dengan filter.</div>
    @endif
</x-gf.panel>
