@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');

    // ---- KPI agregat ----
    $skuCount = $priority->count();
    $urgentCount = $priority->whereIn('grade', ['Kritis', 'Tinggi'])->count();
    $totalWip = (float) $priority->sum('wip');
    // cover tertipis (abaikan null) — makin kecil makin urgent
    $tightCover = $priority->filter(fn($p) => $p->cover_days !== null)->min('cover_days');
@endphp

<div class="gf-overview-kpi-grid">
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong">
        <div class="gf-overview-kpi-label">Perlu Didahulukan</div>
        <div class="gf-overview-kpi-value" data-pr-kpi-urgent>{{ $fmt($urgentCount) }}</div>
        <div class="gf-overview-kpi-note">SKU kritis &amp; tinggi</div>
    </div>
    <div class="gf-overview-kpi-card gf-hide-mobile">
        <div class="gf-overview-kpi-label">SKU Dipantau</div>
        <div class="gf-overview-kpi-value" data-pr-kpi-sku>{{ $fmt($skuCount) }}</div>
        <div class="gf-overview-kpi-note">masuk daftar prioritas</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Cover Tertipis</div>
        <div class="gf-overview-kpi-value">{{ $tightCover === null ? '–' : $fmt($tightCover, 1) }}</div>
        <div class="gf-overview-kpi-note">hari stok ready</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Total WIP</div>
        <div class="gf-overview-kpi-value">{{ $fmt($totalWip) }}</div>
        <div class="gf-overview-kpi-note">pcs dalam proses</div>
    </div>
</div>

<x-gf.panel title="Prioritas Produksi" subtitle="SKU dengan cover stok tipis — dahulukan produksi">
    {{-- Filter realtime (client-side, instan) --}}
    <div class="sj-toolbar" data-pr-toolbar>
        <input type="search" class="form-control sj-search" data-pr-search
            placeholder="Cari SKU / produk / alasan…" autocomplete="off">

        <select class="form-select" data-pr-grade aria-label="Tingkat prioritas">
            <option value="">Semua Tingkat</option>
            <option value="Kritis">Kritis</option>
            <option value="Tinggi">Tinggi</option>
            <option value="Sedang">Sedang</option>
            <option value="Rendah">Rendah</option>
        </select>

        <select class="form-select" data-pr-sort aria-label="Urutkan">
            <option value="score-desc">Skor tertinggi</option>
            <option value="cover-asc">Cover tertipis</option>
            <option value="ads-desc">Jual/hari tertinggi</option>
        </select>

        <span class="sj-count" data-pr-count>{{ $fmt($skuCount) }} SKU · {{ $fmt($urgentCount) }} perlu didahulukan</span>
    </div>

    @if ($priority->isEmpty())
        <div class="prod-empty">Tidak ada data prioritas.</div>
    @else
        <div class="gf-table-scroll gf-table-scroll-sticky">
            <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table" data-pr-table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th class="gf-hide-mobile">Produk</th>
                        <th class="gf-num gf-hide-mobile">Ready</th>
                        <th class="gf-num gf-hide-mobile">WIP</th>
                        <th class="gf-num gf-hide-mobile">Jual/hari</th>
                        <th class="gf-num">Cover (hr)</th>
                        <th class="gf-num">Skor</th>
                        <th>Prioritas</th>
                        <th class="gf-hide-mobile">Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($priority as $p)
                        <tr data-pr-row
                            data-search="{{ strtolower(trim($p->sku . ' ' . $p->product . ' ' . $p->category . ' ' . $p->reason)) }}"
                            data-grade="{{ $p->grade }}"
                            data-score="{{ $p->score }}"
                            data-cover="{{ $p->cover_days ?? 99999 }}"
                            data-ads="{{ $p->ads }}">
                            <td><span class="gf-chip" title="{{ $p->product }}"><b>{{ $p->sku }}</b></span></td>
                            <td class="text-muted gf-hide-mobile">{{ $p->category }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $fmt($p->ready) }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $fmt($p->wip) }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $fmt($p->ads, 1) }}</td>
                            <td class="gf-num">{{ $p->cover_days === null ? '–' : $fmt($p->cover_days, 1) }}</td>
                            <td class="gf-num">
                                <span class="gf-pri-score">
                                    <span class="gf-pri-bar"><i style="width: {{ max(0, min(100, (float) $p->score)) }}%"></i></span>
                                    <b>{{ $fmt($p->score, 1) }}</b>
                                </span>
                            </td>
                            <td>
                                <span class="gf-pri-status gf-pri-{{ strtolower($p->grade) }}">
                                    <span class="gf-pri-dot"></span>{{ $p->grade }}
                                </span>
                            </td>
                            <td class="text-muted small gf-hide-mobile">{{ $p->reason }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="prod-empty" data-pr-empty hidden>Tidak ada SKU yang cocok dengan filter.</div>
    @endif
</x-gf.panel>
