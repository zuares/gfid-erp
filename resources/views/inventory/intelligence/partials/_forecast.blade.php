@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $skuCount = $rows->count();
    $totalSuggested = (float) $rows->sum('suggested_qty');
    $statusLabel = [
        'stockout' => 'Stockout',
        'kritis' => 'Kritis',
        'menipis' => 'Menipis',
        'sehat' => 'Sehat',
        'no_demand' => 'Tanpa demand',
    ];
@endphp

<x-gf.panel title="Forecast & Saran Produksi"
    subtitle="Forecast 30 hari = laju jual × 30. Saran = (21 × laju jual) − ready − WIP, minimal 0.">
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
            <option value="suggested-desc">Saran produksi terbanyak</option>
            <option value="score-desc">Skor evaluasi tertinggi</option>
            <option value="wads-desc">wADS tertinggi</option>
            <option value="ads-desc">Jual/hari tertinggi</option>
            <option value="cover-asc">Cover tertipis</option>
            <option value="sku-asc">SKU A–Z</option>
        </select>

        <span class="ii-count" data-ii-count>{{ $fmt($skuCount) }} SKU</span>

        <span class="ii-actions">
            <button type="button" class="btn btn-dark btn-sm rounded-pill" data-ii-slip>Cetak Slip</button>
            <button type="button" class="btn btn-light border btn-sm rounded-pill" data-ii-export>Export CSV</button>
        </span>
    </div>

    @if ($rows->isEmpty())
        <div class="ii-empty">Tidak ada data untuk filter ini.</div>
    @else
        <div class="gf-table-scroll gf-table-scroll-sticky">
            <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table" data-ii-table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th class="gf-hide-mobile">Kategori</th>
                        <th class="gf-num">Jual/hari</th>
                        <th class="gf-num gf-hide-mobile">wADS</th>
                        <th class="gf-num gf-hide-mobile">Skor</th>
                        <th class="gf-num gf-hide-mobile">Ready</th>
                        <th class="gf-num">Cover (hr)</th>
                        <th class="gf-num">Forecast 30hr</th>
                        <th class="gf-num">Saran Produksi</th>
                        <th class="gf-hide-mobile">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows->sortByDesc('suggested_qty') as $r)
                        <tr data-ii-row
                            data-search="{{ strtolower(trim($r->sku . ' ' . $r->product . ' ' . $r->category)) }}"
                            data-status="{{ $r->status }}"
                            data-sku="{{ $r->sku }}"
                            data-cover="{{ $r->cover_days ?? 99999 }}"
                            data-ads="{{ $r->ads }}"
                            data-wads="{{ $r->wads }}"
                            data-score="{{ $r->eval_score }}"
                            data-suggested="{{ $r->suggested_qty }}">
                            <td><span class="gf-chip" title="{{ $r->product }}"><b>{{ $r->sku }}</b></span></td>
                            <td class="text-muted gf-hide-mobile">{{ $r->category }}</td>
                            <td class="gf-num">{{ $fmt($r->ads, 1) }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $fmt($r->wads, 1) }}</td>
                            <td class="gf-num gf-hide-mobile">
                                @php $sc = $r->eval_score; $scb = $sc >= 70 ? 'ii-score-high' : ($sc >= 40 ? 'ii-score-mid' : 'ii-score-low'); @endphp
                                <span class="ii-score {{ $scb }}">{{ $sc }}</span>
                            </td>
                            <td class="gf-num gf-hide-mobile">{{ $fmt($r->ready) }}</td>
                            <td class="gf-num">{{ $r->cover_days === null ? '–' : $fmt($r->cover_days, 1) }}</td>
                            <td class="gf-num">{{ $fmt($r->forecast_30) }}</td>
                            <td class="gf-num"><b>{{ $fmt($r->suggested_qty) }}</b></td>
                            <td class="gf-hide-mobile">
                                <span class="ii-status ii-status-{{ $r->status }}">
                                    <span class="ii-status-dot"></span>{{ $statusLabel[$r->status] ?? $r->status }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-semibold">
                        <td colspan="8" class="text-end gf-hide-mobile">Total saran produksi</td>
                        <td colspan="1" class="text-end d-md-none">Total saran</td>
                        <td class="gf-num"><b>{{ $fmt($totalSuggested) }}</b></td>
                        <td class="gf-hide-mobile"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="ii-empty" data-ii-empty hidden>Tidak ada SKU yang cocok dengan filter.</div>
    @endif
</x-gf.panel>
