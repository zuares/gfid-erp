@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');

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

<div class="kpi-grid mb-4">
    <div class="kpi-card kpi-card-strong" style="border-left: 4px solid #ef4444;">
        <div class="d-flex align-items-center gap-2 mb-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
            <div class="kpi-label mb-0" style="color: #b91c1c;">Perlu Perhatian</div>
        </div>
        <div class="kpi-val">{{ $fmt($summary['below_target']) }}</div>
        <div class="kpi-note">stockout + kritis + menipis</div>
    </div>
    
    <div class="kpi-card" style="border-left: 4px solid #f97316; background: rgba(249, 115, 22, 0.02);">
        <div class="d-flex align-items-center gap-2 mb-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            <div class="kpi-label mb-0" style="color: #c2410c;">Stockout</div>
        </div>
        <div class="kpi-val" style="color: #c2410c;">{{ $fmt($summary['stockout']) }}</div>
        <div class="kpi-note">ready 0 tapi ada demand</div>
    </div>
    
    <div class="kpi-card gf-hide-mobile" style="border-left: 4px solid #10b981; background: rgba(16, 185, 129, 0.02);">
        <div class="d-flex align-items-center gap-2 mb-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <div class="kpi-label mb-0" style="color: #047857;">Sehat</div>
        </div>
        <div class="kpi-val" style="color: #047857;">{{ $fmt($summary['sehat']) }}</div>
        <div class="kpi-note">cover ≥ 21 hari</div>
    </div>
    
    <div class="kpi-card gf-hide-mobile">
        <div class="d-flex align-items-center gap-2 mb-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="m17 5-5-3-5 3"/><path d="m7 19 5 3 5-3"/></svg>
            <div class="kpi-label mb-0">Cover Tertipis</div>
        </div>
        <div class="kpi-val">{{ $summary['tightest_cover'] === null ? '–' : $fmt($summary['tightest_cover'], 1) }}</div>
        <div class="kpi-note">hari stok ready</div>
    </div>
    
    <div class="kpi-card" style="border-left: 4px solid #6366f1; background: rgba(99, 102, 241, 0.02);">
        <div class="d-flex align-items-center gap-2 mb-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            <div class="kpi-label mb-0" style="color: #4338ca;">Total Ready</div>
        </div>
        <div class="kpi-val" style="color: #4338ca;">{{ $fmt($summary['total_ready']) }}</div>
        <div class="kpi-note">pcs siap jual</div>
    </div>
    
    <div class="kpi-card" style="border-left: 4px solid #3b82f6; background: rgba(59, 130, 246, 0.02);">
        <div class="d-flex align-items-center gap-2 mb-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-7l-2-2"/><path d="M12 15l2-2"/><path d="M12 2v7l2 2"/><path d="M12 9l-2 2"/><path d="M2 12h7l2-2"/><path d="M9 12l2 2"/><path d="M22 12h-7l-2-2"/><path d="M15 12l-2 2"/></svg>
            <div class="kpi-label mb-0" style="color: #1d4ed8;">Saran Produksi & Pengadaan</div>
        </div>
        <div class="kpi-val" style="color: #1d4ed8;">{{ $fmt($summary['total_suggested']) }}</div>
        <div class="kpi-note">pcs (target cover 21 hr)</div>
    </div>
</div>

@php
    $totalSKU = $summary['sku_total'] > 0 ? $summary['sku_total'] : 1;
    $pctStockout = round(($summary['stockout'] / $totalSKU) * 100);
    $pctKritis = round(($summary['kritis'] / $totalSKU) * 100);
    $pctMenipis = round(($summary['menipis'] / $totalSKU) * 100);
    $pctSehat = round(($summary['sehat'] / $totalSKU) * 100);
    $pctNoDemand = 100 - ($pctStockout + $pctKritis + $pctMenipis + $pctSehat); // remaining
@endphp

<div class="card-main mb-4" style="padding: 1.25rem;">
    <h6 style="font-size: .85rem; font-weight: 700; color: #334155; margin-bottom: 1rem;">Distribusi Kesehatan Stok</h6>
    <div class="progress" style="height: 12px; border-radius: 6px; background-color: #f1f5f9; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);">
        <div class="progress-bar" role="progressbar" style="width: {{ $pctStockout }}%; background-color: #ef4444;" title="Stockout: {{ $summary['stockout'] }} SKU ({{ $pctStockout }}%)"></div>
        <div class="progress-bar" role="progressbar" style="width: {{ $pctKritis }}%; background-color: #f97316;" title="Kritis: {{ $summary['kritis'] }} SKU ({{ $pctKritis }}%)"></div>
        <div class="progress-bar" role="progressbar" style="width: {{ $pctMenipis }}%; background-color: #f59e0b;" title="Menipis: {{ $summary['menipis'] }} SKU ({{ $pctMenipis }}%)"></div>
        <div class="progress-bar" role="progressbar" style="width: {{ $pctSehat }}%; background-color: #10b981;" title="Sehat: {{ $summary['sehat'] }} SKU ({{ $pctSehat }}%)"></div>
        <div class="progress-bar" role="progressbar" style="width: {{ $pctNoDemand }}%; background-color: #94a3b8;" title="Tanpa Demand: {{ $summary['sku_total'] - $summary['sku_demand'] }} SKU ({{ $pctNoDemand }}%)"></div>
    </div>
    <div class="d-flex justify-content-between flex-wrap gap-3 mt-3" style="font-size: .75rem; font-weight: 600;">
        <div class="d-flex align-items-center gap-1"><span style="width: 8px; height: 8px; border-radius: 50%; background-color: #ef4444;"></span> <span style="color: #64748b;">Stockout ({{ $pctStockout }}%)</span></div>
        <div class="d-flex align-items-center gap-1"><span style="width: 8px; height: 8px; border-radius: 50%; background-color: #f97316;"></span> <span style="color: #64748b;">Kritis ({{ $pctKritis }}%)</span></div>
        <div class="d-flex align-items-center gap-1"><span style="width: 8px; height: 8px; border-radius: 50%; background-color: #f59e0b;"></span> <span style="color: #64748b;">Menipis ({{ $pctMenipis }}%)</span></div>
        <div class="d-flex align-items-center gap-1"><span style="width: 8px; height: 8px; border-radius: 50%; background-color: #10b981;"></span> <span style="color: #64748b;">Sehat ({{ $pctSehat }}%)</span></div>
        <div class="d-flex align-items-center gap-1"><span style="width: 8px; height: 8px; border-radius: 50%; background-color: #94a3b8;"></span> <span style="color: #64748b;">Tanpa Demand ({{ $pctNoDemand }}%)</span></div>
    </div>
</div>

<div class="filter-bar">
    <div class="d-flex flex-wrap align-items-center gap-2">
         <div class="filter-placeholder d-flex flex-wrap align-items-center gap-2 w-100"></div>
    </div>
</div>

<div class="row g-3">
    <!-- Panel 1: SKU Kritis -->
    <div class="col-lg-6 d-flex flex-column">
        <div class="card-main flex-grow-1 d-flex flex-column">
            <div class="d-flex flex-column" style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--shp-border);">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 style="margin: 0; font-size: 1rem; font-weight: 750; color: #dc2626;">Stok Kritis</h5>
                    <a href="javascript:void(0);" onclick="document.querySelector('[data-tab-target=\'health\']').click();" style="font-size: .75rem; text-decoration: none; font-weight: 600;">Lihat Semua &rarr;</a>
                </div>
                <div class="text-muted-ii" style="margin-top: .15rem;">Cover stok tertipis — berisiko stockout</div>
            </div>

            @if ($critical->isEmpty())
                <div class="ii-empty">Tidak ada SKU berisiko. Semua stok sehat.</div>
            @else
                <div class="table-responsive flex-grow-1" style="min-height: 300px;">
                    <table class="table table-hover align-middle table-list m-0" id="table-critical">
                        <thead style="background: rgba(220, 38, 38, 0.05); position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th style="padding-left: 1.25rem;">SKU & Produk</th>
                                <th class="text-end">Total Ready</th>
                                <th class="text-end" style="padding-right: 1.25rem;">Cover (hr)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($critical as $r)
                                <tr>
                                    <td style="padding-left: 1.25rem;">
                                        <span class="fw-semibold">{{ $r->sku }}</span>
                                        <div class="text-muted-ii" style="font-size: .7rem;">{{ $r->product }}</div>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="badge-status st-{{ $r->status }}" style="padding: 2px 6px; font-size: 0.6rem;">{{ $statusLabel[$r->status] ?? $r->status }}</span>
                                            <span class="text-muted-ii" style="font-size: .65rem;">{{ $r->category }}</span>
                                        </div>
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
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1" style="padding: 0.75rem 1.25rem; border-top: 1px solid var(--shp-border);">
                    <div class="text-muted-ii" style="font-size: 0.8rem;" id="info-critical"></div>
                    <div class="d-flex gap-1" id="nav-critical"></div>
                </div>
            @endif
        </div>
    </div>

    <!-- Panel 2: Prioritas Produksi & Eksternal (Tabbed) -->
    <div class="col-lg-6 d-flex flex-column">
        <div class="card-main flex-grow-1 d-flex flex-column">
            
            <div class="d-flex flex-column" style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--shp-border); background: rgba(59,130,246,0.03);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 style="margin: 0; font-size: 1rem; font-weight: 750; color: #1d4ed8;">Prioritas Pengadaan</h5>
                    <a href="javascript:void(0);" id="link-prioritas-all" onclick="document.querySelector('[data-tab-target=\'forecast\']').click();" style="font-size: .75rem; text-decoration: none; font-weight: 600;">Lihat Semua &rarr;</a>
                </div>
                <div class="ii-tabs-nav m-0" role="tablist" style="margin-top: .25rem; margin-bottom: 0;">
                    <button type="button" class="ii-tab-btn is-active" id="tab-btn-own" onclick="switchPriorityTab('own')">Produksi In-House</button>
                    <button type="button" class="ii-tab-btn" id="tab-btn-ext" onclick="switchPriorityTab('ext')">Beli Barang Jadi (FOB)</button>
                </div>
            </div>

            <div id="tab-content-own" class="d-flex flex-column flex-grow-1">
                @if ($topSuggestionsOwn->isEmpty())
                    <div class="ii-empty">Tidak ada saran produksi in-house saat ini.</div>
                @else
                    <div class="table-responsive flex-grow-1" style="min-height: 300px;">
                        <table class="table table-hover align-middle table-list m-0" id="table-own">
                            <thead style="background: rgba(37, 99, 235, 0.05); position: sticky; top: 0; z-index: 10;">
                                <tr>
                                    <th style="padding-left: 1.25rem;">SKU & Produk</th>
                                    <th class="text-end" title="Prediksi penjualan 30 hari ke depan">Forecast 30hr</th>
                                    <th class="text-end" style="padding-right: 1.25rem;">Saran Produksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topSuggestionsOwn as $r)
                                    <tr>
                                        <td style="padding-left: 1.25rem;">
                                            <span class="fw-semibold">{{ $r->sku }}</span>
                                            <div class="text-muted-ii" style="font-size: .7rem;">{{ $r->product }}</div>
                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <span class="text-muted-ii" style="font-size: .65rem;">Jual/hr: {{ $fmt($r->ads, 1) }}</span>
                                                <span class="text-muted-ii" style="font-size: .65rem;">• {{ $r->category }}</span>
                                            </div>
                                        </td>
                                        <td class="text-end text-muted">{{ $fmt($r->forecast_30) }}</td>
                                        <td class="text-end fw-bold" style="padding-right: 1.25rem;">
                                            <span style="background: #eff6ff; color: #2563eb; padding: 3px 8px; border-radius: 4px;">{{ $fmt($r->suggested_qty) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-1" style="padding: 0.75rem 1.25rem; border-top: 1px solid var(--shp-border);">
                        <div class="text-muted-ii" style="font-size: 0.8rem;" id="info-own"></div>
                        <div class="d-flex gap-1" id="nav-own"></div>
                    </div>
                @endif
            </div>

            <div id="tab-content-ext" class="d-flex flex-column flex-grow-1" style="display: none !important;">
                @if ($topSuggestionsExt->isEmpty())
                    <div class="ii-empty">Tidak ada saran pengadaan FOB saat ini.</div>
                @else
                    <div class="table-responsive flex-grow-1" style="min-height: 300px;">
                        <table class="table table-hover align-middle table-list m-0" id="table-ext">
                            <thead style="background: rgba(16, 185, 129, 0.05); position: sticky; top: 0; z-index: 10;">
                                <tr>
                                    <th style="padding-left: 1.25rem;">SKU & Produk</th>
                                    <th class="text-end" title="Prediksi penjualan 30 hari ke depan">Forecast 30hr</th>
                                    <th class="text-end" style="padding-right: 1.25rem;">Saran Pengadaan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topSuggestionsExt as $r)
                                    <tr>
                                        <td style="padding-left: 1.25rem;">
                                            <span class="fw-semibold">{{ $r->sku }}</span>
                                            <div class="text-muted-ii" style="font-size: .7rem;">{{ $r->product }}</div>
                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <span class="text-muted-ii" style="font-size: .65rem;">Jual/hr: {{ $fmt($r->ads, 1) }}</span>
                                                <span class="text-muted-ii" style="font-size: .65rem;">• {{ $r->category }}</span>
                                            </div>
                                        </td>
                                        <td class="text-end text-muted">{{ $fmt($r->forecast_30) }}</td>
                                        <td class="text-end fw-bold" style="padding-right: 1.25rem;">
                                            <span style="background: #ecfdf5; color: #059669; padding: 3px 8px; border-radius: 4px;">{{ $fmt($r->suggested_qty) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-1" style="padding: 0.75rem 1.25rem; border-top: 1px solid var(--shp-border);">
                        <div class="text-muted-ii" style="font-size: 0.8rem;" id="info-ext"></div>
                        <div class="d-flex gap-1" id="nav-ext"></div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

<script>
function switchPriorityTab(tab) {
    document.getElementById('tab-btn-own').classList.remove('is-active');
    document.getElementById('tab-btn-ext').classList.remove('is-active');
    
    document.getElementById('tab-content-own').style.setProperty('display', 'none', 'important');
    document.getElementById('tab-content-ext').style.setProperty('display', 'none', 'important');
    
    document.getElementById('tab-btn-' + tab).classList.add('is-active');
    document.getElementById('tab-content-' + tab).style.setProperty('display', 'flex', 'important');
    
    const linkAll = document.getElementById('link-prioritas-all');
    if (linkAll) {
        if (tab === 'own') {
            linkAll.setAttribute('onclick', "document.querySelector('[data-tab-target=\\'forecast\\']').click();");
        } else {
            linkAll.setAttribute('onclick', "document.querySelector('[data-tab-target=\\'procurement\\']').click();");
        }
    }
}

(function() {
    function paginateTable(tableId, infoId, navId, rowsPerPage) {
        const table = document.getElementById(tableId);
        if (!table) return;
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const totalRows = rows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage);
        let currentPage = 1;

        const infoEl = document.getElementById(infoId);
        const navEl = document.getElementById(navId);

        function render() {
            rows.forEach((row, index) => {
                row.style.display = (index >= (currentPage - 1) * rowsPerPage && index < currentPage * rowsPerPage) ? '' : 'none';
            });

            const start = totalRows === 0 ? 0 : ((currentPage - 1) * rowsPerPage) + 1;
            const end = Math.min(currentPage * rowsPerPage, totalRows);
            infoEl.innerText = `${start}-${end} dari ${totalRows}`;

            navEl.innerHTML = '';
            const prevBtn = document.createElement('button');
            prevBtn.className = 'btn btn-sm btn-light border py-0 px-2';
            prevBtn.innerHTML = '&laquo;';
            prevBtn.disabled = currentPage === 1;
            prevBtn.onclick = () => { currentPage--; render(); };
            navEl.appendChild(prevBtn);

            const nextBtn = document.createElement('button');
            nextBtn.className = 'btn btn-sm btn-light border py-0 px-2';
            nextBtn.innerHTML = '&raquo;';
            nextBtn.disabled = currentPage === totalPages || totalPages === 0;
            nextBtn.onclick = () => { currentPage++; render(); };
            navEl.appendChild(nextBtn);
        }
        render();
    }

    paginateTable('table-critical', 'info-critical', 'nav-critical', 10);
    paginateTable('table-own', 'info-own', 'nav-own', 10);
    paginateTable('table-ext', 'info-ext', 'nav-ext', 10);
})();
</script>iv>
</div>
