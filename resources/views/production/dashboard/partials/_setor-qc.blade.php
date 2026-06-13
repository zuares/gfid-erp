@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $rp = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $operatorOptions = $lines
        ->map(fn($l) => ['code' => $l->operator_code, 'name' => $l->operator_name])
        ->filter(fn($o) => $o['code'] !== '-')
        ->unique('code')
        ->sortBy('code')
        ->values();
@endphp

<div class="gf-overview-kpi-grid">
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong">
        <div class="gf-overview-kpi-label">Masuk WH-PRD</div>
        <div class="gf-overview-kpi-value">{{ $fmt($total_ok) }}</div>
        <div class="gf-overview-kpi-note">pcs lolos QC → WH-PRD</div>
    </div>
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong gf-hide-mobile">
        <div class="gf-overview-kpi-label">Total HPP</div>
        <div class="gf-overview-kpi-value">{{ $rp($total_hpp) }}</div>
        <div class="gf-overview-kpi-note">nilai stok masuk WH-PRD</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Total Reject</div>
        <div class="gf-overview-kpi-value">{{ $fmt($total_reject) }}</div>
        <div class="gf-overview-kpi-note">pcs gagal QC periode</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Total Bundle</div>
        <div class="gf-overview-kpi-value">{{ $fmt($total_bundle_count ?? $lines->count()) }}</div>
        <div class="gf-overview-kpi-note">
            {{ $yield === null ? 'belum ada setoran' : 'yield ' . $fmt($yield, 1) . '%' }}
        </div>
    </div>
</div>

<x-gf.panel title="Setor &amp; QC — Masuk WH-PRD" subtitle="Rincian setoran jahit lolos QC yang masuk gudang WH-PRD">
    {{-- Filter realtime (client-side, instan) --}}
    <div class="sj-toolbar" data-qc-toolbar>
        <input type="search" class="form-control sj-search" data-qc-search
            placeholder="Cari SKU / produk / penjahit…" autocomplete="off">

        <select class="form-select" data-qc-operator aria-label="Penjahit">
            <option value="">Semua Penjahit</option>
            @foreach ($operatorOptions as $op)
                <option value="{{ $op['code'] }}">{{ $op['code'] }} — {{ $op['name'] }}</option>
            @endforeach
        </select>

        <select class="form-select" data-qc-sort aria-label="Urutkan">
            <option value="date-desc">Terbaru</option>
            <option value="ok-desc">OK terbanyak</option>
            <option value="hpp-desc">Nilai HPP terbesar</option>
            <option value="yield-asc">Yield terendah</option>
        </select>

        <span class="sj-count" data-qc-count>{{ $fmt($lines->count()) }} setoran · {{ $fmt($total_ok) }} pcs OK</span>
    </div>

    @if ($lines->isEmpty())
        <div class="prod-empty">Tidak ada stok masuk WH-PRD pada periode ini.</div>
    @else
        <div class="gf-table-scroll gf-table-scroll-sticky">
            <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table" data-qc-table>
                <thead>
                    <tr>
                        <th>Tgl Setor</th>
                        <th>Penjahit</th>
                        <th>SKU</th>
                        <th class="gf-hide-mobile">Produk</th>
                        <th class="gf-num">OK</th>
                        <th class="gf-num gf-hide-mobile">Reject</th>
                        <th class="gf-num gf-hide-mobile">Nilai HPP</th>
                        <th class="gf-num">Yield</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lines as $l)
                        <tr data-qc-row
                            data-search="{{ strtolower(trim($l->sku . ' ' . $l->product_name . ' ' . $l->category . ' ' . $l->operator_code . ' ' . $l->operator_name)) }}"
                            data-operator="{{ $l->operator_code }}"
                            data-date="{{ $l->date }}"
                            data-ok="{{ $l->qty_ok }}"
                            data-hpp="{{ $l->hpp_total }}"
                            data-yield="{{ $l->yield ?? 999 }}">
                            <td><x-gf.datecell :date="$l->date" :time="$l->created_at" /></td>
                            <td>
                                <span class="gf-chip" title="{{ $l->operator_name }}"><b>{{ $l->operator_code }}</b></span>
                                <span class="text-muted small d-block gf-hide-mobile">{{ $l->operator_name }}</span>
                            </td>
                            <td><span class="gf-chip" title="{{ $l->product_name }}"><b>{{ $l->sku }}</b></span></td>
                            <td class="text-muted gf-hide-mobile">{{ $l->category }}</td>
                            <td class="gf-num"><b>{{ $fmt($l->qty_ok) }}</b></td>
                            <td class="gf-num gf-hide-mobile">{{ $l->qty_reject > 0 ? $fmt($l->qty_reject) : '-' }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $rp($l->hpp_total) }}</td>
                            <td class="gf-num">
                                @php $y = $l->yield; @endphp
                                <span class="gf-badge {{ $y === null ? 'gf-badge-muted' : ($y >= 95 ? 'gf-badge-green' : ($y >= 85 ? 'gf-badge-amber' : 'gf-badge-red')) }}">
                                    {{ $y === null ? '–' : $fmt($y, 1) . '%' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="prod-empty" data-qc-empty hidden>Tidak ada setoran yang cocok dengan filter.</div>
    @endif
</x-gf.panel>
