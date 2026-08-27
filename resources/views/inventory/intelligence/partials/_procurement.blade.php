@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $rows = $rows
        ->filter(fn($r) => (bool) ($r->can_buy ?? false) || ($r->default_supply_source ?? null) === \App\Models\Item::SUPPLY_OUTSOURCE)
        ->map(function ($r) {
            $r = clone $r;
            $r->suggested_qty = (float) ($r->procurement_suggested_qty ?? 0);
            $r->suggested_value = round($r->suggested_qty * (float) ($r->unit_cost ?? 0), 0);
            $r->production_source_key = 'external';
            return $r;
        })
        ->values();
    $procurementDays = (int) ($filters['procurement_days'] ?? 60);
    $fmtRp = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $skuCount = $rows->count();
    $totalSuggested = (float) $rows->sum('suggested_qty');
    $totalForecast = (float) $rows->sum('procurement_forecast');
    $totalAvailable = (float) $rows->sum('available_stock');
    $totalSuggestedValue = (float) $rows->sum('suggested_value');
    $statusLabel = [
        'stockout' => 'Stockout',
        'kritis' => 'Kritis',
        'menipis' => 'Menipis',
        'sehat' => 'Sehat',
        'no_demand' => 'Tanpa demand',
    ];
@endphp

<div class="row g-2 mb-3">
    <div class="col-6 col-xl">
        <div class="card-main h-100 p-3">
            <div class="text-muted-ii small">SKU FOB</div>
            <div class="fw-bold fs-5">{{ $fmt($skuCount) }}</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="card-main h-100 p-3">
            <div class="text-muted-ii small">Forecast minimum {{ $procurementDays }} hari</div>
            <div class="fw-bold fs-5">{{ $fmt($totalForecast) }} pcs</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="card-main h-100 p-3">
            <div class="text-muted-ii small">Stok tersedia</div>
            <div class="fw-bold fs-5">{{ $fmt($totalAvailable) }} pcs</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="card-main h-100 p-3">
            <div class="text-muted-ii small">Saran pengadaan</div>
            <div class="fw-bold fs-5 text-success">{{ $fmt($totalSuggested) }} pcs</div>
        </div>
    </div>
    <div class="col-12 col-xl">
        <div class="card-main h-100 p-3">
            <div class="text-muted-ii small">Estimasi nilai pengadaan</div>
            <div class="fw-bold fs-5 text-success">{{ $fmtRp($totalSuggestedValue) }}</div>
        </div>
    </div>
</div>

<div class="filter-bar mb-3">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <div class="filter-placeholder d-flex flex-wrap align-items-center gap-2"></div>
        <span class="text-muted-ii" style="font-size:.72rem;">Forecast minimum {{ $procurementDays }} hari · menyesuaikan lead time supplier</span>
        <div class="vr mx-1 d-none d-md-block" style="opacity: .15;"></div>
        <input type="search" class="form-control form-control-sm ii-search" data-ii-search
            placeholder="Cari SKU / produk / kategori…" autocomplete="off" style="max-width:200px;">

        <select class="form-select form-select-sm" data-ii-procurement-days aria-label="Periode forecast pengadaan" style="max-width:150px;">
            <option value="30" @selected($procurementDays === 30)>Forecast 30 hari</option>
            <option value="60" @selected($procurementDays === 60)>Forecast 60 hari</option>
        </select>

        <select class="form-select form-select-sm" data-ii-action-filter aria-label="Filter pengadaan" style="max-width:190px;">
            <option value="">Semua item FOB</option>
            <option value="need">Perlu pengadaan (saran &gt; 0)</option>
            <option value="none">Belum perlu pengadaan</option>
        </select>

        <select class="form-select form-select-sm" data-ii-status aria-label="Status" style="max-width:140px;">
            <option value="">Semua Status</option>
            <option value="stockout">Stockout</option>
            <option value="kritis">Kritis</option>
            <option value="menipis">Menipis</option>
            <option value="sehat">Sehat</option>
            <option value="no_demand">Tanpa demand</option>
        </select>

        <select class="form-select form-select-sm" data-ii-sort aria-label="Urutkan" style="max-width:200px;">
            <option value="ads-desc" selected>Jual/hari tertinggi</option>
            <option value="suggested-desc">Saran Terbesar &darr;</option>
            <option value="suggested-asc">Saran Terkecil &uarr;</option>
            <option value="cover-asc">Cover Stok Tertipis &uarr;</option>
            <option value="cover-desc">Cover Stok Tertebal &darr;</option>
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
        <div class="table-responsive ii-table-scroll" style="max-height: 70vh;">
            <table class="table table-hover align-middle table-list" data-ii-table id="table-procurement" data-ii-default-sort="ads" data-ii-default-dir="desc">
                <thead style="background: rgba(241, 245, 249, 0.5);">
                    <tr>
                        <th data-ii-sort-key="sku" data-ii-sort-type="text" style="padding-left: 1.25rem;">SKU & Produk</th>
                        <th class="text-end">Lead Time</th>
                        <th data-ii-sort-key="stock" data-ii-sort-type="number" class="text-end">Stok Tersedia</th>
                        <th data-ii-sort-key="ads" data-ii-sort-type="number" class="text-end">Jual / Hari</th>
                        <th data-ii-sort-key="forecast" data-ii-sort-type="number" class="text-end" title="Forecast minimal {{ $procurementDays }} hari, diperpanjang mengikuti lead time">Forecast</th>
                        <th data-ii-sort-key="suggested" data-ii-sort-type="number" class="text-end" style="padding-right: 1.25rem; width: 140px;">Saran Pengadaan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows->sortByDesc('suggested_qty') as $r)
                        <tr data-ii-row
                            data-item-id="{{ $r->item_id }}"
                            data-search="{{ strtolower(trim($r->sku . ' ' . $r->product . ' ' . $r->category)) }}"
                            data-status="{{ $r->status }}"
                            data-sku="{{ $r->sku }}"
                            data-stock="{{ $r->available_stock }}"
                            data-cover="{{ $r->cover_days ?? 99999 }}"
                            data-ads="{{ $r->ads }}"
                            data-wads="{{ $r->wads }}"
                            data-forecast="{{ $r->procurement_forecast }}"
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

                            <td class="text-end" style="min-width: 150px;">
                                @if ($r->lead_time_mapping_id && ($canEditLeadTime ?? false))
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <input type="number" class="form-control form-control-sm text-end"
                                            data-ii-lead-time-input data-item-id="{{ $r->item_id }}"
                                            value="{{ $r->lead_time_days ?? '' }}" min="0" max="3650"
                                            placeholder="-" style="width: 78px;">
                                        <span class="text-muted-ii">hari</span>
                                    </div>
                                    <div class="text-muted-ii" style="font-size:.62rem;">
                                        {{ $r->lead_time_supplier_name ?? 'Supplier SKU' }}
                                    </div>
                                @elseif ($r->lead_time_days !== null)
                                    <div class="fw-semibold">{{ $r->lead_time_days }} hari</div>
                                    <div class="text-muted-ii" style="font-size:.62rem;">{{ $r->lead_time_source === 'category' ? 'Dari kategori' : ($r->lead_time_supplier_name ?? 'Supplier SKU') }}</div>
                                @else
                                    <span class="text-danger" style="font-size:.72rem;">Belum diisi</span>
                                @endif
                                @if (($canEditLeadTime ?? false) && !$r->lead_time_mapping_id)
                                    <a href="{{ route('purchasing.supplier_items.index', ['mode' => 'item', 'q' => $r->sku]) }}"
                                        class="d-block" style="font-size:.62rem;">Atur supplier</a>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="fw-semibold">{{ $fmt($r->available_stock) }}</div>
                                <div class="text-muted-ii" style="font-size: .65rem; white-space: nowrap;">
                                    Ready+PRD: {{ $fmt($r->ready_total) }} | WIP: {{ $fmt($r->wip_process) }}
                                </div>
                            </td>
                            <td class="text-end fw-semibold">{{ $fmt($r->ads, 1) }}</td>
                            <td class="text-end text-muted">
                                <div>{{ $fmt($r->procurement_forecast) }}</div>
                                <div style="font-size:.62rem;">{{ $r->procurement_effective_days }} hari</div>
                            </td>
                            <td class="text-end fw-bold" style="padding-right: 1.25rem; color: #059669;">
                                <div>{{ $fmt($r->suggested_qty) }} pcs</div>
                                <div class="text-muted-ii" style="font-size:.65rem;">{{ $fmtRp($r->suggested_value) }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-semibold">
                        <td colspan="5" class="text-end">Total saran pengadaan</td>
                        <td class="text-end" style="padding-right: 1.25rem; color: #059669;">
                            <b data-ii-filtered-total>{{ $fmt($totalSuggested) }}</b>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="ii-empty" data-ii-empty hidden>Tidak ada SKU yang cocok dengan filter.</div>
    @endif
</div>
