@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $rp = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $stageClass = fn($s) => $s === 'Cutting' ? 'gf-badge-blue' : 'gf-badge-amber';
@endphp

<div class="gf-overview-kpi-grid">
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong">
        <div class="gf-overview-kpi-label">Total Reject</div>
        <div class="gf-overview-kpi-value">{{ $fmt($total_reject) }}</div>
        <div class="gf-overview-kpi-note">pcs gagal QC periode</div>
    </div>
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong gf-hide-mobile">
        <div class="gf-overview-kpi-label">Nilai HPP</div>
        <div class="gf-overview-kpi-value">{{ $rp($total_hpp) }}</div>
        <div class="gf-overview-kpi-note">kerugian stok reject</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Reject Cutting</div>
        <div class="gf-overview-kpi-value">{{ $fmt($reject_cutting) }}</div>
        <div class="gf-overview-kpi-note">pcs gagal QC potong</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Reject Jahit</div>
        <div class="gf-overview-kpi-value">{{ $fmt($reject_sewing) }}</div>
        <div class="gf-overview-kpi-note">pcs gagal QC jahit</div>
    </div>
</div>

<x-gf.panel title="Reject Produksi" subtitle="Barang gagal QC dari tahap cutting & jahit — per kejadian">
    {{-- Filter realtime (client-side, instan) --}}
    <div class="sj-toolbar" data-rj-toolbar>
        <input type="search" class="form-control sj-search" data-rj-search
            placeholder="Cari SKU / produk / penjahit / alasan…" autocomplete="off">

        <select class="form-select" data-rj-stage aria-label="Tahap">
            <option value="">Semua Tahap</option>
            <option value="Cutting">Cutting</option>
            <option value="Jahit">Jahit</option>
        </select>

        <select class="form-select" data-rj-sort aria-label="Urutkan">
            <option value="date-desc">Terbaru</option>
            <option value="qty-desc">Reject terbanyak</option>
            <option value="hpp-desc">Nilai HPP terbesar</option>
        </select>

        <span class="sj-count" data-rj-count>{{ $fmt($lines->count()) }} kejadian · {{ $fmt($total_reject) }} pcs</span>
    </div>

    @if ($lines->isEmpty())
        <div class="prod-empty">Tidak ada reject pada periode ini.</div>
    @else
        <div class="gf-table-scroll gf-table-scroll-sticky">
            <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table" data-rj-table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tahap</th>
                        <th class="gf-hide-mobile">Operator</th>
                        <th>SKU</th>
                        <th class="gf-hide-mobile">Produk</th>
                        <th class="gf-num">Reject</th>
                        <th class="gf-num gf-hide-mobile">Nilai HPP</th>
                        <th class="gf-hide-mobile">Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lines as $l)
                        <tr data-rj-row
                            data-search="{{ strtolower(trim($l->sku . ' ' . $l->product_name . ' ' . $l->category . ' ' . $l->operator_code . ' ' . $l->operator_name . ' ' . $l->reason)) }}"
                            data-stage="{{ $l->stage }}"
                            data-date="{{ $l->date }}"
                            data-qty="{{ $l->qty }}"
                            data-hpp="{{ $l->hpp_total }}">
                            <td>{{ \Carbon\Carbon::parse($l->date)->format('d M') }}</td>
                            <td><span class="gf-badge {{ $stageClass($l->stage) }}">{{ $l->stage }}</span></td>
                            <td class="gf-hide-mobile">
                                @if ($l->operator_code !== '-')
                                    <span class="gf-chip" title="{{ $l->operator_name }}"><b>{{ $l->operator_code }}</b></span>
                                @else
                                    <span class="text-muted">–</span>
                                @endif
                            </td>
                            <td><span class="gf-chip" title="{{ $l->product_name }}"><b>{{ $l->sku }}</b></span></td>
                            <td class="text-muted gf-hide-mobile">{{ $l->product_name }}</td>
                            <td class="gf-num"><b>{{ $fmt($l->qty) }}</b></td>
                            <td class="gf-num gf-hide-mobile">{{ $rp($l->hpp_total) }}</td>
                            <td class="text-muted small gf-hide-mobile">{{ $l->reason }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="prod-empty" data-rj-empty hidden>Tidak ada reject yang cocok dengan filter.</div>
    @endif
</x-gf.panel>
