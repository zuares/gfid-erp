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

<div class="filter-bar">
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
            <option value="cover-asc">Cover tertipis</option>
            <option value="sku-asc">SKU A–Z</option>
        </select>
        <span class="ii-count ms-auto" data-ii-count>{{ $fmt($skuCount) }} SKU</span>
    </div>
</div>

<div class="card-main">
    @if ($rows->isEmpty())
        <div class="ii-empty">Tidak ada data stok untuk filter ini.</div>
    @else
        <div class="table-responsive ii-table-scroll" style="max-height: 70vh;">
            <table class="table table-hover align-middle table-list" data-ii-table id="table-health" data-ii-default-sort="ads" data-ii-default-dir="desc">
                <thead style="background: rgba(241, 245, 249, 0.5);">
                    <tr>
                        <th data-ii-sort-key="sku" data-ii-sort-type="text" style="padding-left: 1.25rem;">SKU & Produk</th>
                        <th data-ii-sort-key="ready_total" data-ii-sort-type="number" class="text-end">Stok Siap</th>
                        <th data-ii-sort-key="wip" data-ii-sort-type="number" class="text-end gf-hide-mobile">WIP Proses</th>
                        <th data-ii-sort-key="ads" data-ii-sort-type="number" class="text-end gf-hide-mobile">Jual/hari</th>
                        <th data-ii-sort-key="cover" data-ii-sort-type="number" class="text-end" title="Cover = ready ÷ laju jual">Cover (hr)</th>
                        <th data-ii-sort-key="pipe" data-ii-sort-type="number" class="text-end gf-hide-mobile" title="Pipe = (ready + WIP) ÷ laju jual">Pipeline (hr)</th>
                        <th data-ii-sort-key="status" data-ii-sort-type="status" style="padding-right: 1.25rem;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        <tr data-ii-row
                            data-search="{{ strtolower(trim($r->sku . ' ' . $r->product . ' ' . $r->category)) }}"
                            data-status="{{ $r->status }}"
                            data-cover="{{ $r->cover_days ?? 99999 }}"
                            data-ads="{{ $r->ads }}"
                            data-sku="{{ $r->sku }}"
                            data-ready_total="{{ $r->ready_total }}"
                            data-wip="{{ $r->wip_process }}"
                            data-pipe="{{ $r->pipe_cover_days ?? 99999 }}">
                            
                            <td style="padding-left: 1.25rem;">
                                <span class="fw-semibold">{{ $r->sku }}</span>
                                <div class="text-muted-ii" style="font-size: .7rem;">{{ $r->product }}</div>
                                <div class="text-muted-ii mt-1" style="font-size: .65rem;">{{ $r->category }}</div>
                            </td>
                            
                            <td class="text-end">
                                <div class="fw-semibold">{{ $fmt($r->ready_total) }}</div>
                                <div class="text-muted-ii" style="font-size: .65rem; white-space: nowrap;">
                                    RTS: {{ $fmt($r->ready) }} | PRD: {{ $fmt($r->wh_prd) }}
                                </div>
                            </td>
                            <td class="text-end gf-hide-mobile text-muted">{{ $fmt($r->wip_process) }}</td>
                            <td class="text-end gf-hide-mobile text-muted">{{ $fmt($r->ads, 1) }}</td>
                            <td class="text-end fw-semibold">{{ $r->cover_days === null ? '–' : $fmt($r->cover_days, 1) }}</td>
                            <td class="text-end gf-hide-mobile text-muted">{{ $r->pipe_cover_days === null ? '–' : $fmt($r->pipe_cover_days, 1) }}</td>
                            <td style="padding-right: 1.25rem;">
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
