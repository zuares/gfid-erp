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
@endphp

<x-gf.panel title="Kesehatan Stok per SKU" subtitle="Cover = ready ÷ laju jual. Pipe = (ready + WIP) ÷ laju jual.">
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
            <option value="cover-asc">Cover tertipis</option>
            <option value="ads-desc">Jual/hari tertinggi</option>
            <option value="sku-asc">SKU A–Z</option>
        </select>

        <span class="ii-count" data-ii-count>{{ $fmt($skuCount) }} SKU</span>
    </div>

    @if ($rows->isEmpty())
        <div class="ii-empty">Tidak ada data stok untuk filter ini.</div>
    @else
        <div class="gf-table-scroll gf-table-scroll-sticky">
            <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table" data-ii-table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th class="gf-hide-mobile">Kategori</th>
                        <th class="gf-num">Ready</th>
                        <th class="gf-num gf-hide-mobile">WIP</th>
                        <th class="gf-num gf-hide-mobile">Jual/hari</th>
                        <th class="gf-num">Cover (hr)</th>
                        <th class="gf-num gf-hide-mobile">Pipe (hr)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        <tr data-ii-row
                            data-search="{{ strtolower(trim($r->sku . ' ' . $r->product . ' ' . $r->category)) }}"
                            data-status="{{ $r->status }}"
                            data-sku="{{ $r->sku }}"
                            data-cover="{{ $r->cover_days ?? 99999 }}"
                            data-ads="{{ $r->ads }}"
                            data-suggested="{{ $r->suggested_qty }}">
                            <td><span class="gf-chip" title="{{ $r->product }}"><b>{{ $r->sku }}</b></span></td>
                            <td class="text-muted gf-hide-mobile">{{ $r->category }}</td>
                            <td class="gf-num">{{ $fmt($r->ready) }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $fmt($r->wip) }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $fmt($r->ads, 1) }}</td>
                            <td class="gf-num">{{ $r->cover_days === null ? '–' : $fmt($r->cover_days, 1) }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $r->pipe_cover_days === null ? '–' : $fmt($r->pipe_cover_days, 1) }}</td>
                            <td>
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
