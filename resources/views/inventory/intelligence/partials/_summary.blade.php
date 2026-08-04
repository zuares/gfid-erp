@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $fmtRp = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $productionDays = (int) ($filters['production_days'] ?? 30);
    $procurementDays = (int) ($filters['procurement_days'] ?? 60);

    // SKU paling berisiko
    $critical = $rows
        ->whereIn('status', ['stockout', 'kritis', 'menipis'])
        ->sortBy(fn($r) => $r->cover_days ?? 0)
        ->values();

    $topSuggestionsOwn = $rows
        ->where('suggested_qty', '>', 0)
        ->where('production_source_key', 'own')
        ->sortByDesc('suggested_qty')
        ->values();

    $topSuggestionsExt = $rows
        ->where('suggested_qty', '>', 0)
        ->where('production_source_key', 'external')
        ->sortByDesc('suggested_qty')
        ->values();

    $statusLabel = [
        'stockout' => 'Stockout',
        'kritis' => 'Kritis',
        'menipis' => 'Menipis',
        'sehat' => 'Sehat',
        'no_demand' => 'Tanpa demand',
    ];
@endphp

@php
    $skuTotal = max((int) ($summary['sku_total'] ?? 0), 1);
    $pctBelow = round(($summary['below_target'] / $skuTotal) * 100);
    $pctStockout = round(($summary['stockout'] / $skuTotal) * 100);
    $pctSehat = round(($summary['sehat'] / $skuTotal) * 100);
    $pctNoDemand = round((($summary['sku_no_demand'] ?? 0) / $skuTotal) * 100);
    $ownSuggested = (float) $rows->where('suggested_qty', '>', 0)->where('production_source_key', 'own')->sum('suggested_qty');
    $extSuggested = (float) $rows->where('suggested_qty', '>', 0)->where('production_source_key', 'external')->sum('suggested_qty');
@endphp

<div class="kpi-grid mb-4">
    <div class="kpi-card kpi-card-risk">
        <div class="kpi-head">
            <div class="kpi-label mb-0">Perlu Perhatian</div>
            <div class="kpi-inline-pct">{{ $pctBelow }}%</div>
        </div>
        <div class="kpi-val">{{ $fmt($summary['below_target']) }}</div>
        <div class="kpi-note">stockout + kritis + menipis</div>
        <div class="kpi-sub-list">
            <div class="kpi-sub-item">
                <span class="kpi-sub-label"><i class="bi bi-x-circle text-danger"></i><span>Stockout</span></span>
                <span class="kpi-sub-val text-danger">{{ $fmt($summary['stockout']) }}</span>
            </div>
            <div class="kpi-sub-item">
                <span class="kpi-sub-label"><i class="bi bi-exclamation-triangle text-warning"></i><span>Kritis</span></span>
                <span class="kpi-sub-val text-warning">{{ $fmt($summary['kritis']) }}</span>
            </div>
            <div class="kpi-sub-item">
                <span class="kpi-sub-label"><i class="bi bi-hourglass-split text-warning"></i><span>Menipis</span></span>
                <span class="kpi-sub-val text-warning">{{ $fmt($summary['menipis']) }}</span>
            </div>
        </div>
    </div>

    <div class="kpi-card kpi-card-stockout">
        <div class="kpi-head">
            <div class="kpi-label mb-0">Stockout</div>
            <div class="kpi-inline-pct">{{ $pctStockout }}%</div>
        </div>
        <div class="kpi-val text-danger">{{ $fmt($summary['stockout']) }}</div>
        <div class="kpi-note">ready 0 tapi ada demand</div>
        <div class="kpi-sub-list">
            <div class="kpi-sub-item">
                <span class="kpi-sub-label"><i class="bi bi-box-seam text-secondary"></i><span>Ready</span></span>
                <span class="kpi-sub-val">{{ $fmt(0) }}</span>
            </div>
            <div class="kpi-sub-item">
                <span class="kpi-sub-label"><i class="bi bi-bar-chart-line text-primary"></i><span>Demand</span></span>
                <span class="kpi-sub-val">{{ $fmt($summary['stockout']) }}</span>
            </div>
        </div>
    </div>

    <div class="kpi-card kpi-card-good gf-hide-mobile">
        <div class="kpi-head">
            <div class="kpi-label mb-0">Sehat</div>
            <div class="kpi-inline-pct">{{ $pctSehat }}%</div>
        </div>
        <div class="kpi-val text-success">{{ $fmt($summary['sehat']) }}</div>
        <div class="kpi-note">cover &ge; 21 hari</div>
        <div class="kpi-sub-list">
            <div class="kpi-sub-item">
                <span class="kpi-sub-label"><i class="bi bi-check-circle text-success"></i><span>SKU demand</span></span>
                <span class="kpi-sub-val">{{ $fmt($summary['sku_demand']) }}</span>
            </div>
            <div class="kpi-sub-item">
                <span class="kpi-sub-label"><i class="bi bi-graph-up text-success"></i><span>Avg cover</span></span>
                <span class="kpi-sub-val">{{ $summary['avg_cover'] === null ? '–' : $fmt($summary['avg_cover'], 1) }}</span>
            </div>
        </div>
    </div>

    <div class="kpi-card kpi-card-ready gf-hide-mobile">
        <div class="kpi-head">
            <div class="kpi-label mb-0">Total Ready</div>
            <div class="kpi-inline-pct">RTS + PRD</div>
        </div>
        <div class="kpi-val text-primary">{{ $fmt($summary['total_ready']) }}</div>
        <div class="kpi-note">pcs siap jual</div>
        <div class="kpi-sub-list">
            <div class="kpi-sub-item">
                <span class="kpi-sub-label"><i class="bi bi-box"></i><span>RTS</span></span>
                <span class="kpi-sub-val">{{ $fmt($summary['total_rts']) }}</span>
            </div>
            <div class="kpi-sub-item">
                <span class="kpi-sub-label"><i class="bi bi-building"></i><span>WH PRD</span></span>
                <span class="kpi-sub-val">{{ $fmt($summary['total_wh_prd']) }}</span>
            </div>
        </div>
    </div>

    <div class="kpi-card kpi-card-plan">
        <div class="kpi-head">
            <div class="kpi-label mb-0">Saran Produksi & Pengadaan</div>
            <div class="kpi-inline-pct">{{ $summary['total_suggested'] > 0 ? 'need' : 'ok' }}</div>
        </div>
        <div class="kpi-val text-primary">{{ $fmt($summary['total_suggested']) }}</div>
        <div class="kpi-note">pcs (produksi {{ $productionDays }} hr · FOB {{ $procurementDays }} hr)</div>
        <div class="kpi-sub-list">
            <div class="kpi-sub-item">
                <span class="kpi-sub-label"><i class="bi bi-hammer"></i><span>In-house</span></span>
                <span class="kpi-sub-val">{{ $fmt($ownSuggested) }}</span>
            </div>
            <div class="kpi-sub-item">
                <span class="kpi-sub-label"><i class="bi bi-truck"></i><span>FOB</span></span>
                <span class="kpi-sub-val">{{ $fmt($extSuggested) }}</span>
            </div>
            <div class="kpi-sub-item">
                <span class="kpi-sub-label"><i class="bi bi-cash-stack"></i><span>Nilai saran</span></span>
                <span class="kpi-sub-val">{{ $fmtRp($summary['total_suggested_value'] ?? 0) }}</span>
            </div>
        </div>
    </div>
</div>

<div class="card-main mb-4" style="padding: 1.1rem 1.15rem 1rem;">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h6 style="font-size: .78rem; font-weight: 900; color: #334155; margin: 0; text-transform: uppercase; letter-spacing: .05em;">Distribusi Kesehatan Stok</h6>
        <span class="text-muted-ii" style="font-size: .72rem;">{{ $fmt($summary['sku_total']) }} SKU total</span>
    </div>
    <div class="progress" style="height: 12px; border-radius: 999px; background-color: #e2e8f0; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
        <div class="progress-bar" role="progressbar" style="width: {{ $pctBelow }}%; background-color: #dc2626;" title="Perlu perhatian: {{ $summary['below_target'] }} SKU ({{ $pctBelow }}%)"></div>
        <div class="progress-bar" role="progressbar" style="width: {{ $pctStockout }}%; background-color: #f97316;" title="Stockout: {{ $summary['stockout'] }} SKU ({{ $pctStockout }}%)"></div>
        <div class="progress-bar" role="progressbar" style="width: {{ $pctSehat }}%; background-color: #10b981;" title="Sehat: {{ $summary['sehat'] }} SKU ({{ $pctSehat }}%)"></div>
        <div class="progress-bar" role="progressbar" style="width: {{ $pctNoDemand }}%; background-color: #94a3b8;" title="Tanpa demand: {{ $summary['sku_no_demand'] ?? 0 }} SKU ({{ $pctNoDemand }}%)"></div>
    </div>
    <div class="d-flex justify-content-between flex-wrap gap-3 mt-3" style="font-size: .73rem; font-weight: 700;">
        <div class="d-flex align-items-center gap-1"><span style="width: 8px; height: 8px; border-radius: 50%; background-color: #dc2626;"></span> <span style="color: #64748b;">Perlu perhatian ({{ $pctBelow }}%)</span></div>
        <div class="d-flex align-items-center gap-1"><span style="width: 8px; height: 8px; border-radius: 50%; background-color: #f97316;"></span> <span style="color: #64748b;">Stockout ({{ $pctStockout }}%)</span></div>
        <div class="d-flex align-items-center gap-1"><span style="width: 8px; height: 8px; border-radius: 50%; background-color: #10b981;"></span> <span style="color: #64748b;">Sehat ({{ $pctSehat }}%)</span></div>
        <div class="d-flex align-items-center gap-1"><span style="width: 8px; height: 8px; border-radius: 50%; background-color: #94a3b8;"></span> <span style="color: #64748b;">Tanpa demand ({{ $pctNoDemand }}%)</span></div>
    </div>
</div>

<div class="filter-bar">
    <div class="d-flex flex-wrap align-items-center gap-2">
         <div class="filter-placeholder d-flex flex-wrap align-items-center gap-2 w-100"></div>
    </div>
</div>

<div class="card-main mb-4" style="padding: 1rem 1.1rem 1.1rem;">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 style="margin: 0; font-size: .98rem; font-weight: 800; color: #334155;">Prioritas Operasional</h5>
            <div class="text-muted-ii" style="margin-top:.15rem;">Pilih fokus antara stok kritis dan prioritas pengadaan</div>
        </div>
        <div class="ii-tabs-nav m-0" role="tablist" data-summary-subtabs style="margin-bottom:0;">
            <button type="button" class="ii-tab-btn is-active" data-summary-subtab-target="critical">Stok Kritis</button>
            <button type="button" class="ii-tab-btn" data-summary-subtab-target="procurement">Prioritas Pengadaan</button>
        </div>
    </div>

    <div data-summary-subpanel="critical">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 style="margin: 0; font-size: .98rem; font-weight: 800; color: #dc2626;">Stok Kritis</h5>
                <div class="text-muted-ii" style="margin-top: .15rem;">SKU yang paling mendekati stockout</div>
            </div>
            <a href="javascript:void(0);" onclick="document.querySelector('[data-tab-target=\'health\']').click();" style="font-size: .75rem; text-decoration: none; font-weight: 700;">Lihat semua &rarr;</a>
        </div>

        @if ($critical->isEmpty())
            <div class="ii-empty">Tidak ada SKU berisiko. Semua stok sehat.</div>
        @else
        <div class="table-responsive ii-table-scroll" style="max-height: 46vh;">
                <table class="table table-hover align-middle table-list m-0" id="table-critical" data-ii-table data-ii-default-sort="ads" data-ii-default-dir="desc">
                    <thead style="background: rgba(220, 38, 38, 0.05);">
                        <tr>
                            <th data-ii-sort-key="sku" data-ii-sort-type="text" style="padding-left: 1.25rem;">SKU & Produk</th>
                            <th data-ii-sort-key="ads" data-ii-sort-type="number" class="text-end">Jual / Hari</th>
                            <th data-ii-sort-key="ready_total" data-ii-sort-type="number" class="text-end">Total Ready</th>
                            <th data-ii-sort-key="cover" data-ii-sort-type="number" class="text-end" style="padding-right: 1.25rem;">Cover (hr)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($critical as $r)
                            <tr data-ii-row
                                data-search="{{ strtolower(trim($r->sku . ' ' . $r->product . ' ' . $r->category)) }}"
                                data-status="{{ $r->status }}"
                                data-sku="{{ $r->sku }}"
                                data-ads="{{ $r->ads }}"
                                data-ready_total="{{ $r->ready_total }}"
                                data-cover="{{ $r->cover_days ?? 99999 }}">
                                <td style="padding-left: 1.25rem;">
                                    <span class="fw-semibold">{{ $r->sku }}</span>
                                    <div class="text-muted-ii" style="font-size: .7rem;">{{ $r->product }}</div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="badge-status st-{{ $r->status }}" style="padding: 2px 6px; font-size: 0.6rem;">{{ $statusLabel[$r->status] ?? $r->status }}</span>
                                        <span class="text-muted-ii" style="font-size: .65rem;">{{ $r->category }}</span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="fw-semibold">{{ $fmt($r->ads, 1) }}</div>
                                    <div class="text-muted-ii" style="font-size: .65rem;">wADS {{ $fmt($r->wads, 1) }}</div>
                                </td>
                                <td class="text-end">
                                    <div class="fw-semibold">{{ $fmt($r->ready_total) }}</div>
                                    <div class="text-muted-ii" style="font-size: .65rem; white-space: nowrap;">
                                        RTS: {{ $fmt($r->ready) }} | PRD: {{ $fmt($r->wh_prd) }}
                                    </div>
                                </td>
                                <td class="text-end fw-semibold" style="padding-right: 1.25rem;">
                                    @if($r->cover_days !== null && $r->cover_days < 7)
                                        <span class="text-danger">{{ $fmt($r->cover_days, 1) }}</span>
                                    @else
                                        {{ $r->cover_days === null ? '–' : $fmt($r->cover_days, 1) }}
                                    @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
        @endif
    </div>

    <div data-summary-subpanel="procurement" hidden>
        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
            <div>
                <h5 style="margin: 0; font-size: .98rem; font-weight: 800; color: #1d4ed8;">Prioritas Pengadaan</h5>
                <div class="text-muted-ii" style="margin-top:.15rem;">Urutan SKU berdasarkan kebutuhan produksi / beli</div>
            </div>
            <a href="javascript:void(0);" id="link-prioritas-all" onclick="document.querySelector('[data-tab-target=\'forecast\']').click();" style="font-size: .75rem; text-decoration: none; font-weight: 700;">Lihat semua &rarr;</a>
        </div>
        <div class="ii-tabs-nav m-0" role="tablist" style="margin-top: .25rem; margin-bottom: 1rem;">
            <button type="button" class="ii-tab-btn is-active" id="tab-btn-own" onclick="switchPriorityTab('own')">Produksi In-House</button>
            <button type="button" class="ii-tab-btn" id="tab-btn-ext" onclick="switchPriorityTab('ext')">Beli Barang Jadi (FOB)</button>
        </div>

        <div id="tab-content-own" class="d-flex flex-column">
            @if ($topSuggestionsOwn->isEmpty())
                <div class="ii-empty">Tidak ada saran produksi in-house saat ini.</div>
            @else
                <div class="table-responsive ii-table-scroll" style="max-height: 46vh;">
                    <table class="table table-hover align-middle table-list m-0" id="table-own" data-ii-table data-ii-default-sort="ads" data-ii-default-dir="desc">
                        <thead style="background: rgba(37, 99, 235, 0.05);">
                            <tr>
                                <th data-ii-sort-key="sku" data-ii-sort-type="text" style="padding-left: 1.25rem;">SKU & Produk</th>
                                <th data-ii-sort-key="stock" data-ii-sort-type="number" class="text-end">Stok Tersedia</th>
                                <th data-ii-sort-key="ads" data-ii-sort-type="number" class="text-end">Jual / Hari</th>
                                <th data-ii-sort-key="forecast" data-ii-sort-type="number" class="text-end" title="Prediksi penjualan {{ $productionDays }} hari ke depan">Forecast {{ $productionDays }}hr</th>
                                <th data-ii-sort-key="suggested" data-ii-sort-type="number" class="text-end" style="padding-right: 1.25rem;">Saran Produksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topSuggestionsOwn as $r)
                                <tr data-ii-row
                                    data-search="{{ strtolower(trim($r->sku . ' ' . $r->product . ' ' . $r->category)) }}"
                                    data-status="{{ $r->status }}"
                                    data-sku="{{ $r->sku }}"
                                    data-stock="{{ $r->available_stock }}"
                                    data-ads="{{ $r->ads }}"
                                    data-forecast="{{ $r->production_forecast }}"
                                    data-score="{{ $r->eval_score }}"
                                    data-suggested="{{ $r->suggested_qty }}">
                                    <td style="padding-left: 1.25rem;">
                                        <span class="fw-semibold">{{ $r->sku }}</span>
                                        <div class="text-muted-ii" style="font-size: .7rem;">{{ $r->product }}</div>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="text-muted-ii" style="font-size: .65rem;">Jual/hr: {{ $fmt($r->ads, 1) }}</span>
                                            <span class="text-muted-ii" style="font-size: .65rem;">• {{ $r->category }}</span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-semibold">{{ $fmt($r->available_stock) }}</div>
                                        <div class="text-muted-ii" style="font-size: .65rem; white-space: nowrap;">
                                            Ready+PRD: {{ $fmt($r->ready_total) }} | WIP: {{ $fmt($r->wip_process) }}
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold">{{ $fmt($r->ads, 1) }}</td>
                                    <td class="text-end text-muted">{{ $fmt($r->production_forecast) }}</td>
                                    <td class="text-end fw-bold" style="padding-right: 1.25rem;">
                                        <span style="background: #eff6ff; color: #2563eb; padding: 3px 8px; border-radius: 4px;">{{ $fmt($r->suggested_qty) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div id="tab-content-ext" class="d-flex flex-column" style="display: none !important;">
            @if ($topSuggestionsExt->isEmpty())
                <div class="ii-empty">Tidak ada saran pengadaan FOB saat ini.</div>
            @else
                <div class="table-responsive ii-table-scroll" style="max-height: 46vh;">
                    <table class="table table-hover align-middle table-list m-0" id="table-ext" data-ii-table data-ii-default-sort="ads" data-ii-default-dir="desc">
                        <thead style="background: rgba(16, 185, 129, 0.05);">
                            <tr>
                                <th data-ii-sort-key="sku" data-ii-sort-type="text" style="padding-left: 1.25rem;">SKU & Produk</th>
                                <th data-ii-sort-key="stock" data-ii-sort-type="number" class="text-end">Stok Tersedia</th>
                                <th data-ii-sort-key="ads" data-ii-sort-type="number" class="text-end">Jual / Hari</th>
                                <th data-ii-sort-key="score" data-ii-sort-type="number" class="text-end gf-hide-mobile">Skor</th>
                                <th data-ii-sort-key="forecast" data-ii-sort-type="number" class="text-end" title="Prediksi penjualan {{ $procurementDays }} hari ke depan">Forecast {{ $procurementDays }}hr</th>
                                <th data-ii-sort-key="suggested" data-ii-sort-type="number" class="text-end" style="padding-right: 1.25rem;">Saran Pengadaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topSuggestionsExt as $r)
                                <tr data-ii-row
                                    data-search="{{ strtolower(trim($r->sku . ' ' . $r->product . ' ' . $r->category)) }}"
                                    data-status="{{ $r->status }}"
                                    data-sku="{{ $r->sku }}"
                                    data-stock="{{ $r->available_stock }}"
                                    data-ads="{{ $r->ads }}"
                                    data-forecast="{{ $r->procurement_forecast }}"
                                    data-score="{{ $r->eval_score }}"
                                    data-suggested="{{ $r->suggested_qty }}">
                                    <td style="padding-left: 1.25rem;">
                                        <span class="fw-semibold">{{ $r->sku }}</span>
                                        <div class="text-muted-ii" style="font-size: .7rem;">{{ $r->product }}</div>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="text-muted-ii" style="font-size: .65rem;">Jual/hr: {{ $fmt($r->ads, 1) }}</span>
                                            <span class="text-muted-ii" style="font-size: .65rem;">• {{ $r->category }}</span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-semibold">{{ $fmt($r->available_stock) }}</div>
                                        <div class="text-muted-ii" style="font-size: .65rem; white-space: nowrap;">
                                            Ready+PRD: {{ $fmt($r->ready_total) }} | WIP: {{ $fmt($r->wip_process) }}
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold">{{ $fmt($r->ads, 1) }}</td>
                                    <td class="text-end fw-semibold gf-hide-mobile">{{ $fmt($r->eval_score) }}</td>
                                    <td class="text-end text-muted">{{ $fmt($r->procurement_forecast) }}</td>
                                    <td class="text-end fw-bold" style="padding-right: 1.25rem;">
                                        <span style="background: #ecfdf5; color: #059669; padding: 3px 8px; border-radius: 4px;">{{ $fmt($r->suggested_qty) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
