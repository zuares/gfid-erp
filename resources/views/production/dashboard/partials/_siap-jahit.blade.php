@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $rp = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $gradeClass = fn($g) => match ($g) {
        'Kritis' => 'gf-badge-red',
        'Tinggi' => 'gf-badge-amber',
        'Sedang' => 'gf-badge-blue',
        default => 'gf-badge-muted',
    };
    $skuCount = $skus->count();
    $urgentCount = $skus->filter(fn($s) => in_array($s->grade, ['Kritis', 'Tinggi'], true))->count();
    $totalBundles = $skus->sum('bundle_count');
@endphp

{{-- ============ KPI ringkas (SKU · Siap Jahit · HPP · Stok jadi) ============ --}}
<div class="gf-overview-kpi-grid">
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong">
        <div class="gf-overview-kpi-label">Total Siap Jahit</div>
        <div class="gf-overview-kpi-value">{{ $fmt($total_remaining) }}</div>
        <div class="gf-overview-kpi-note">pcs siap dibagi</div>
    </div>
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong gf-hide-mobile">
        <div class="gf-overview-kpi-label">Total HPP</div>
        <div class="gf-overview-kpi-value">{{ $rp($total_hpp) }}</div>
        <div class="gf-overview-kpi-note">nilai stok siap jahit</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Stok Barang Jadi</div>
        <div class="gf-overview-kpi-value">{{ $fmt($total_stok_jadi) }}</div>
        <div class="gf-overview-kpi-note">pcs siap jual (WH-PRD + WH-RTS)</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Jumlah SKU</div>
        <div class="gf-overview-kpi-value">{{ $fmt($skuCount) }}</div>
        <div class="gf-overview-kpi-note">{{ $fmt($totalBundles) }} bundle</div>
    </div>
</div>

{{-- ============ Stok siap jahit per SKU (sumber: form Sewing Pickup) ============ --}}
<x-gf.panel title="Stok Siap Jahit per SKU" subtitle="Bundle WIP-CUT siap dibagi ke penjahit — dikelompokkan per SKU">
    {{-- Filter realtime (client-side, instan) --}}
    <div class="sj-toolbar" data-sj-toolbar>
        <input type="search" class="form-control sj-search" data-sj-search
            placeholder="Cari SKU / produk / kategori…" autocomplete="off">

        <select class="form-select" data-sj-grade aria-label="Prioritas">
            <option value="">Semua Prioritas</option>
            <option value="Kritis">Kritis</option>
            <option value="Tinggi">Tinggi</option>
            <option value="Sedang">Sedang</option>
        </select>

        <select class="form-select" data-sj-sort aria-label="Urutkan">
            <option value="remaining-desc">Siap jahit terbanyak</option>
            <option value="hpp-desc">Nilai HPP terbesar</option>
            <option value="stok-asc">Stok jadi tersedikit</option>
            <option value="bundles-desc">Bundle terbanyak</option>
            <option value="sku-asc">SKU A→Z</option>
        </select>

        <span class="sj-count" data-sj-count>{{ $fmt($skuCount) }} SKU · {{ $fmt($total_remaining) }} pcs</span>
    </div>

    @if ($skus->isEmpty())
        <div class="prod-empty">Tidak ada stok siap jahit.</div>
    @else
        <div class="gf-table-scroll gf-table-scroll-sticky">
            <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table" data-sj-table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th class="gf-hide-mobile">Produk</th>
                        <th class="gf-num">Siap Jahit</th>
                        <th class="gf-num">Bundle</th>
                        <th class="gf-num gf-hide-mobile">Nilai HPP</th>
                        <th class="gf-num gf-hide-mobile">WH-PRD</th>
                        <th class="gf-num gf-hide-mobile">WH-RTS</th>
                        <th class="gf-num gf-hide-mobile">Stok Jadi</th>
                        <th class="gf-num gf-hide-mobile">ADS</th>
                        <th class="gf-num gf-hide-mobile">Cover</th>
                        <th>Prioritas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($skus as $s)
                        <tr data-sj-row
                            data-search="{{ strtolower(trim($s->sku . ' ' . $s->product_name . ' ' . $s->category)) }}"
                            data-grade="{{ $s->grade ?? '' }}"
                            data-remaining="{{ $s->qty_ready }}"
                            data-hpp="{{ $s->hpp_total }}"
                            data-bundles="{{ $s->bundle_count }}"
                            data-stok="{{ $s->stok_jadi }}"
                            data-sku="{{ $s->sku }}"
                            data-sj-href="{{ route('production.sewing.pickups.create') }}?sku={{ urlencode($s->sku) }}"
                            style="cursor: pointer;"
                            title="Klik untuk bagi bundle SKU {{ $s->sku }} ke penjahit">
                            <td><span class="gf-chip" title="{{ $s->product_name }}"><b>{{ $s->sku }}</b></span></td>
                            <td class="text-muted gf-hide-mobile">{{ $s->product_name }}</td>
                            <td class="gf-num"><b>{{ $fmt($s->qty_ready) }}</b></td>
                            <td class="gf-num">{{ $fmt($s->bundle_count) }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $rp($s->hpp_total) }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $fmt($s->stok_prd) }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $fmt($s->stok_rts) }}</td>
                            <td class="gf-num gf-hide-mobile"><b>{{ $fmt($s->stok_jadi) }}</b></td>
                            <td class="gf-num gf-hide-mobile">{{ $s->ads > 0 ? $fmt($s->ads, 1) : '–' }}</td>
                            @php $cover = $s->ads > 0 ? $s->stok_jadi / $s->ads : null; @endphp
                            <td class="gf-num gf-hide-mobile">{{ $cover === null ? '–' : $fmt($cover, 1) }}</td>
                            <td>
                                @if ($s->grade)
                                    <span class="gf-badge {{ $gradeClass($s->grade) }}">{{ $s->grade }}</span>
                                @else
                                    <span class="text-muted">–</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="prod-empty" data-sj-empty hidden>Tidak ada SKU yang cocok dengan filter.</div>
    @endif
</x-gf.panel>
