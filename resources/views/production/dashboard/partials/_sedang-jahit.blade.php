@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $rp = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $operatorOptions = $lines
        ->map(fn($l) => ['code' => $l->operator_code, 'name' => $l->operator_name])
        ->unique('code')
        ->sortBy('code')
        ->values();
@endphp

<div class="gf-overview-kpi-grid">
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong">
        <div class="gf-overview-kpi-label">Total Outstanding</div>
        <div class="gf-overview-kpi-value">{{ $fmt($total_outstanding) }}</div>
        <div class="gf-overview-kpi-note">pcs masih dijahit</div>
    </div>
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong gf-hide-mobile">
        <div class="gf-overview-kpi-label">Total HPP</div>
        <div class="gf-overview-kpi-value">{{ $rp($total_hpp) }}</div>
        <div class="gf-overview-kpi-note">nilai WIP jahit</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Penjahit Aktif</div>
        <div class="gf-overview-kpi-value">{{ $fmt($operator_count) }}</div>
        <div class="gf-overview-kpi-note">pegang WIP jahit</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Baris WIP</div>
        <div class="gf-overview-kpi-value">{{ $fmt($lines->count()) }}</div>
        <div class="gf-overview-kpi-note">bundle dalam proses</div>
    </div>
</div>

<x-gf.panel title="Sedang Dijahit" subtitle="WIP per penjahit / bundle — sumber: form Setor Jahit (Sewing Return)">
    {{-- Filter realtime (client-side, instan) --}}
    <div class="sj-toolbar" data-sd-toolbar>
        <input type="search" class="form-control sj-search" data-sd-search
            placeholder="Cari SKU / produk / penjahit…" autocomplete="off">

        <select class="form-select" data-sd-operator aria-label="Penjahit">
            <option value="">Semua Penjahit</option>
            @foreach ($operatorOptions as $op)
                <option value="{{ $op['code'] }}">{{ $op['code'] }} — {{ $op['name'] }}</option>
            @endforeach
        </select>

        <select class="form-select" data-sd-sort aria-label="Urutkan">
            <option value="date-desc">Terbaru</option>
            <option value="out-desc">Sisa terbanyak</option>
            <option value="hpp-desc">Nilai HPP terbesar</option>
            <option value="age-desc">Umur terlama</option>
            <option value="picked-desc">Diambil terbanyak</option>
        </select>

        <span class="sj-count" data-sd-count>{{ $fmt($lines->count()) }} baris · {{ $fmt($total_outstanding) }} pcs</span>
    </div>

    @if ($lines->isEmpty())
        <div class="prod-empty">Tidak ada bundle yang sedang dijahit.</div>
    @else
        <div class="gf-table-scroll gf-table-scroll-sticky">
            <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table" data-sd-table>
                <thead>
                    <tr>
                        <th>Tgl Ambil</th>
                        <th>Penjahit</th>
                        <th>SKU</th>
                        <th class="gf-hide-mobile">Produk</th>
                        <th class="gf-num gf-hide-mobile">Diambil</th>
                        <th class="gf-num gf-hide-mobile">Disetor</th>
                        <th class="gf-num">Sisa</th>
                        <th class="gf-num gf-hide-mobile">Nilai HPP</th>
                        <th class="gf-num">Umur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lines as $l)
                        <tr data-sd-row
                            class="{{ $l->age_days !== null && $l->age_days >= 7 ? 'gf-row-warn' : '' }}"
                            data-search="{{ strtolower(trim($l->sku . ' ' . $l->product_name . ' ' . $l->category . ' ' . $l->operator_code . ' ' . $l->operator_name)) }}"
                            data-operator="{{ $l->operator_code }}"
                            data-outstanding="{{ $l->qty_outstanding }}"
                            data-hpp="{{ $l->hpp_total }}"
                            data-age="{{ $l->age_days ?? -1 }}"
                            data-date="{{ $l->pickup_date }}"
                            data-picked="{{ $l->qty_picked }}"
                            @if ($l->operator_id)
                                data-sd-href="{{ route('production.sewing.returns.create') }}?operator_id={{ (int) $l->operator_id }}"
                                style="cursor: pointer;"
                                title="Klik untuk setor jahit penjahit {{ $l->operator_name }}"
                            @endif>
                            <td><x-gf.datecell :date="$l->pickup_date" :time="$l->created_at" /></td>
                            <td>
                                <span class="gf-chip" title="{{ $l->operator_name }}"><b>{{ $l->operator_code }}</b></span>
                                <span class="text-muted small d-block gf-hide-mobile">{{ $l->operator_name }}</span>
                            </td>
                            <td><span class="gf-chip" title="{{ $l->product_name }}"><b>{{ $l->sku }}</b></span></td>
                            <td class="text-muted gf-hide-mobile">{{ $l->category }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $fmt($l->qty_picked) }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $fmt($l->qty_returned) }}</td>
                            <td class="gf-num"><b>{{ $fmt($l->qty_outstanding) }}</b></td>
                            <td class="gf-num gf-hide-mobile">{{ $rp($l->hpp_total) }}</td>
                            <td class="gf-num">
                                <span class="gf-badge {{ $l->age_days !== null && $l->age_days >= 7 ? 'gf-badge-amber' : 'gf-badge-muted' }}">
                                    {{ $l->age_days !== null ? $l->age_days . ' hr' : '-' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="prod-empty" data-sd-empty hidden>Tidak ada baris yang cocok dengan filter.</div>
    @endif
</x-gf.panel>
