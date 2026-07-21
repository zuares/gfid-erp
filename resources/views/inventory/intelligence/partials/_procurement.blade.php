@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $rows = $rows->where('production_source_key', 'external')->values();
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

<div class="filter-bar mb-3">
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

        <select class="form-select form-select-sm" data-ii-sort aria-label="Urutkan" style="max-width:200px;">
            <option value="saran_desc">Saran Terbesar &darr;</option>
            <option value="saran_asc">Saran Terkecil &uarr;</option>
            <option value="cover_asc">Cover Stok Tertipis &uarr;</option>
            <option value="cover_desc">Cover Stok Tertebal &darr;</option>
            <option value="ads_desc">Penjualan (ADS) &darr;</option>
        </select>

        <span class="ii-actions d-flex gap-2 ms-auto">
            <button type="button" class="btn btn-ship-primary btn-sm btn-pill" data-ii-slip>Cetak Slip</button>
            <button type="button" class="btn btn-ship-outline btn-sm btn-pill" data-ii-export>Export CSV</button>
        </span>
    </div>
</div>

<div class="card-main">

    @if ($rows->isEmpty())
        <div class="ii-empty">Tidak ada data untuk filter ini.</div>
    @else
        <div class="table-responsive" style="max-height: 70vh;">
            <table class="table table-hover align-middle table-list" data-ii-table id="table-procurement">
                <thead style="background: rgba(241, 245, 249, 0.5); position: sticky; top: 0; z-index: 10;">
                    <tr>
                        <th style="padding-left: 1.25rem;">SKU & Produk</th>
                        <th class="text-end">Jual / Hari</th>
                        <th class="text-end gf-hide-mobile" title="Skor evaluasi prioritas">Skor</th>
                        <th class="text-end gf-hide-mobile">Total Ready</th>
                        <th class="text-end" title="Ketahanan stok tersedia (hari)">Cover (hr)</th>
                        <th class="text-end" title="Prediksi penjualan 30 hari ke depan">Forecast 30hr</th>
                        <th class="text-end" style="padding-right: 1.25rem; width: 140px;">Saran Pengadaan</th>
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
                            
                            <td style="padding-left: 1.25rem;">
                                <span class="fw-semibold">{{ $r->sku }}</span>
                                <div class="text-muted-ii" style="font-size: .7rem;">{{ $r->product }}</div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge-status st-{{ $r->status }}" style="padding: 2px 6px; font-size: 0.6rem;">{{ $statusLabel[$r->status] ?? $r->status }}</span>
                                    <span class="text-muted-ii" style="font-size: .65rem;">{{ $r->category }}</span>
                                </div>
                            </td>
                            
                            <td class="text-end fw-semibold">{{ $fmt($r->ads, 1) }}</td>
                            <td class="text-end gf-hide-mobile">
                                @php $sc = $r->eval_score; $scb = $sc >= 70 ? 'ii-score-high' : ($sc >= 40 ? 'ii-score-mid' : 'ii-score-low'); @endphp
                                <span class="ii-score {{ $scb }}">{{ $sc }}</span>
                            </td>
                            <td class="text-end gf-hide-mobile">
                                <div class="fw-semibold">{{ $fmt($r->ready_total) }}</div>
                                <div class="text-muted-ii" style="font-size: .65rem; white-space: nowrap;">
                                    RTS: {{ $fmt($r->ready) }} | PRD: {{ $fmt($r->wh_prd) }}
                                </div>
                            </td>
                            <td class="text-end fw-semibold">{{ $r->cover_days === null ? '–' : $fmt($r->cover_days, 1) }}</td>
                            <td class="text-end text-muted">{{ $fmt($r->forecast_30) }}</td>
                            <td class="text-end fw-bold" style="padding-right: 1.25rem; color: #059669;">{{ $fmt($r->suggested_qty) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-semibold">
                        <td colspan="5" class="text-end gf-hide-mobile">Total saran pengadaan</td>
                        <td colspan="1" class="text-end d-md-none">Total saran</td>
                        <td class="text-end" style="padding-right: 1.25rem; color: #059669;"><b>{{ $fmt($totalSuggested) }}</b></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="ii-empty" data-ii-empty hidden>Tidak ada SKU yang cocok dengan filter.</div>
    @endif
</div>
