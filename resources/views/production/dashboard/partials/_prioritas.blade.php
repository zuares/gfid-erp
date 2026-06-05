@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');

    // ---- KPI agregat ----
    $skuCount = $priority->count();
    $urgentCount = $priority->whereIn('grade', ['Kritis', 'Tinggi'])->count();
    $ownCount = $skuCount;
    $totalWip = (float) $priority->sum('wip');
    // cover tertipis (abaikan null) — makin kecil makin urgent
    $tightCover = $priority->filter(fn($p) => $p->stok_cukup_days !== null)->min('stok_cukup_days');
@endphp

<div class="gf-overview-kpi-grid gf-priority-kpis">
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong">
        <div class="gf-overview-kpi-label">Perlu Didahulukan</div>
        <div class="gf-overview-kpi-value" data-pr-kpi-urgent>{{ $fmt($urgentCount) }}</div>
        <div class="gf-overview-kpi-note">SKU kritis &amp; tinggi</div>
    </div>
    <div class="gf-overview-kpi-card gf-hide-mobile">
        <div class="gf-overview-kpi-label">Produksi Sendiri</div>
        <div class="gf-overview-kpi-value" data-pr-kpi-own>{{ $fmt($ownCount) }}</div>
        <div class="gf-overview-kpi-note">punya BOM aktif</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Stok Cukup</div>
        <div class="gf-overview-kpi-value">{{ $tightCover === null ? '–' : $fmt($tightCover, 1) }}</div>
        <div class="gf-overview-kpi-note">hari tersingkat</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Total WIP</div>
        <div class="gf-overview-kpi-value">{{ $fmt($totalWip) }}</div>
        <div class="gf-overview-kpi-note">pcs produksi sendiri</div>
    </div>
</div>

<x-gf.panel title="Prioritas Produksi" subtitle="Hanya SKU produksi sendiri yang perlu dipantau">
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

        <select class="form-select" data-pr-pickup aria-label="Bisa diambil jahit">
            <option value="">Semua</option>
            <option value="ready">Ada Stok Jahit</option>
            <option value="empty">Belum Ada Stok Jahit</option>
        </select>

        <select class="form-select" data-pr-sort aria-label="Urutkan">
            <option value="actionable-desc">Dahulukan + ada stok jahit</option>
            <option value="sewing-desc">Stok Jahit terbesar</option>
            <option value="score-desc">Dahulukan tertinggi</option>
            <option value="cover-asc">Stok cukup tersingkat</option>
            <option value="ads-desc">Jual/hari tertinggi</option>
        </select>

        <span class="sj-count" data-pr-count>{{ $fmt($skuCount) }} SKU produksi sendiri · {{ $fmt($urgentCount) }} perlu didahulukan</span>
    </div>

    @if ($priority->isEmpty())
        <div class="prod-empty">Tidak ada data prioritas.</div>
    @else
        <div class="gf-table-scroll gf-table-scroll-sticky">
            <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table" data-pr-table>
                <thead>
                    <tr>
                        <th>Item Produksi</th>
                        <th class="gf-num">Barang Jadi</th>
                        <th class="gf-num">Stok Jahit</th>
                        <th class="gf-num">Sedang Jahit</th>
                        <th class="gf-num gf-hide-mobile">Terjual/Hari</th>
                        <th class="gf-num">Stok Cukup</th>
                        <th>Dahulukan?</th>
                        <th class="gf-hide-mobile">Rekomendasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($priority as $p)
                        @php
                            $canPickup = (float) $p->siap_jahit > 0;
                            $pickupUrl = $canPickup ? route('production.sewing.pickups.create', ['sku' => $p->sku]) : null;
                        @endphp
                        <tr data-pr-row class="{{ $canPickup ? 'gf-pri-has-sewing-stock' : 'gf-pri-no-sewing-stock' }}"
                            @if ($pickupUrl) data-href="{{ $pickupUrl }}" @endif
                            data-search="{{ strtolower(trim($p->sku . ' ' . $p->product . ' ' . $p->category . ' ' . $p->reason)) }}"
                            data-grade="{{ $p->grade }}"
                            data-score="{{ $p->score }}"
                            data-cover="{{ $p->stok_cukup_days ?? 99999 }}"
                            data-ads="{{ $p->ads }}"
                            data-sewing-stock="{{ (float) $p->siap_jahit }}"
                            data-pickup="{{ $canPickup ? 'ready' : 'empty' }}">
                            <td data-label="Item">
                                <div class="gf-pri-item">
                                    @if ($pickupUrl)
                                    <a class="gf-pri-link" href="{{ $pickupUrl }}" title="Ambil jahit {{ $p->sku }}">
                                        <span class="gf-chip" title="{{ $p->product }}"><b>{{ $p->sku }}</b></span>
                                    </a>
                                    <a class="gf-pri-link gf-pri-link-copy" href="{{ $pickupUrl }}" title="Ambil jahit {{ $p->sku }}">
                                        <div class="gf-pri-product">{{ $p->product }}</div>
                                        <div class="gf-pri-meta">{{ $p->category }}</div>
                                    </a>
                                    @else
                                    <span class="gf-pri-link gf-pri-disabled" title="Belum ada stok jahit">
                                        <span class="gf-chip" title="{{ $p->product }}"><b>{{ $p->sku }}</b></span>
                                    </span>
                                    <span class="gf-pri-link-copy gf-pri-disabled" title="Belum ada stok jahit">
                                        <div class="gf-pri-product">{{ $p->product }}</div>
                                        <div class="gf-pri-meta">{{ $p->category }}</div>
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td class="gf-num gf-pri-fg" data-label="Barang Jadi">
                                <div class="gf-pri-stack">
                                    <b>{{ $fmt($p->barang_jadi) }}</b>
                                </div>
                            </td>
                            <td class="gf-num gf-pri-sewing-stock" data-label="Stok Jahit">
                                <div class="gf-pri-stack">
                                    <b>{{ $fmt($p->siap_jahit) }}</b>
                                </div>
                            </td>
                            <td class="gf-num gf-pri-sewing-progress" data-label="Sedang Jahit">
                                <div class="gf-pri-stack">
                                    <b>{{ $fmt($p->sedang_jahit) }}</b>
                                </div>
                            </td>
                            <td class="gf-num gf-hide-mobile" data-label="Terjual/Hari">
                                <div class="gf-pri-stack">
                                    <b>{{ $fmt($p->ads, 1) }}</b>
                                </div>
                            </td>
                            <td class="gf-num" data-label="Stok Cukup">
                                <div class="gf-pri-stack">
                                    <b>{{ $p->stok_cukup_days === null ? '–' : $fmt($p->stok_cukup_days, 1) }}</b>
                                </div>
                            </td>
                            <td data-label="Dahulukan?">
                                <div class="gf-pri-decision">
                                    <span class="gf-pri-status gf-pri-{{ strtolower($p->grade) }}">
                                        <span class="gf-pri-dot"></span>{{ $p->dahulukan_label }}
                                    </span>
                                    <span class="gf-pri-score">
                                        <span class="gf-pri-bar"><i style="width: {{ max(0, min(100, (float) $p->score)) }}%"></i></span>
                                        <b>{{ $p->grade }}</b>
                                    </span>
                                </div>
                            </td>
                            <td class="gf-pri-reason gf-hide-mobile" data-label="Rekomendasi">{{ $p->reason }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="prod-empty" data-pr-empty hidden>Tidak ada SKU yang cocok dengan filter.</div>
    @endif
</x-gf.panel>
