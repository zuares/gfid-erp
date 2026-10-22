@extends('layouts.app')
@section('title', 'Marketplace • SKU Mapping')

@include('marketplace._shared')

@section('content')
<x-gf.page eyebrow="Marketplace" title="SKU Mapping" description="Petakan SKU marketplace ke item internal agar fulfillment bisa diproses.">
    <x-slot:actions>
        <button type="button" class="btn btn-dark btn-sm fw-bold"
            style="border-radius:999px;font-size:.78rem"
            onclick="mpMapping.open('', loadMappings)">+ Tambah Mapping</button>
        <a href="{{ route('marketplace.sku-mapping.registered') }}" class="btn btn-light border btn-sm fw-bold"
            style="border-radius:999px;font-size:.78rem">Mapping Terdaftar</a>
        <button type="button" class="btn btn-light border btn-sm"
            style="border-radius:999px;font-size:.78rem;font-weight:700"
            onclick="loadMappings()">↻ Refresh</button>
    </x-slot:actions>

    {{-- KPI --}}
    <div class="oc-kpi-grid">
        <div class="oc-kpi-card"><div class="oc-kpi-label">Belum Dipetakan</div><div class="oc-kpi-value" id="kpiUnmapped">—</div><div class="oc-kpi-note">perlu mapping</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Sudah Dipetakan</div><div class="oc-kpi-value" id="kpiMapped">—</div><div class="oc-kpi-note">mapping aktif</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Total SKU</div><div class="oc-kpi-value" id="kpiTotalSku">—</div><div class="oc-kpi-note">dari semua order</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Coverage</div><div class="oc-kpi-value" id="kpiCoverage">—</div><div class="oc-kpi-note">% SKU terpetakan</div></div>
    </div>

    {{-- Unmapped SKUs --}}
    <x-gf.panel title="SKU Belum Dipetakan" subtitle="SKU dari order marketplace yang belum ada item internalnya.">
        <x-slot:actions>
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <button type="button" id="unmappedAutoMapBtn" class="btn btn-warning btn-sm fw-bold"
                    style="border-radius:999px;font-size:.75rem"
                    title="Cocokkan SKU marketplace dengan kode item internal yang sama"
                    onclick="runUnmappedAutoMap()">⚡ Mapping Otomatis</button>
                <input type="text" id="unmappedSearch" placeholder="Cari SKU atau nama item…"
                    style="border-radius:999px;font-size:.75rem;padding:.3rem .85rem;border:1px solid rgba(15,23,42,.1);background:#fff;min-width:210px"
                    oninput="filterUnmapped(this.value)">
                <select id="unmappedStoreFilter" class="form-select form-select-sm"
                    style="border-radius:999px;font-size:.75rem;width:auto;min-width:170px"
                    onchange="applyUnmappedFilters()">
                    <option value="">Semua Toko</option>
                </select>
                <select id="unmappedChannelFilter" class="form-select form-select-sm"
                    style="border-radius:999px;font-size:.75rem;width:auto;min-width:135px"
                    onchange="applyUnmappedFilters()">
                    <option value="">Semua Channel</option>
                    <option value="shopee">Shopee</option>
                    <option value="tiktok">TikTok</option>
                    <option value="tokopedia">Tokopedia</option>
                    <option value="lazada">Lazada</option>
                </select>
                <input type="text" id="unmappedDateRange" class="form-control form-control-sm"
                    placeholder="Rentang tanggal" autocomplete="off"
                    aria-label="Rentang tanggal order" title="Rentang tanggal order"
                    style="border-radius:999px;font-size:.75rem;width:190px;cursor:pointer;background:#fff">
                <input type="hidden" id="unmappedDateFrom">
                <input type="hidden" id="unmappedDateTo">
                <select id="unmappedSort" class="form-select form-select-sm"
                    style="border-radius:999px;font-size:.75rem;width:auto;min-width:145px"
                    onchange="applyUnmappedFilters()">
                    <option value="orders_desc">Order terbanyak</option>
                    <option value="latest">Order terbaru</option>
                    <option value="sku_asc">SKU A-Z</option>
                </select>
                <button type="button" class="btn btn-light border btn-sm"
                    style="border-radius:999px;font-size:.75rem;font-weight:700"
                    onclick="resetUnmappedFilters()">Reset</button>
            </div>
        </x-slot:actions>
        <div id="unmappedBody"><div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div></div>
    </x-gf.panel>

</x-gf.page>

@include('marketplace._mapping-modal')

<div class="modal fade" id="autoMapConfirmModal" tabindex="-1" aria-labelledby="autoMapConfirmModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0">
                <div>
                    <h5 class="modal-title fw-bold" id="autoMapConfirmModalTitle">Konfirmasi Mapping Otomatis</h5>
                    <div class="text-muted" style="font-size:.78rem">Periksa item master yang akan digunakan sebelum proses dijalankan.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div id="autoMapPreviewSummary" class="mb-3"></div>
                <div id="autoMapPreviewBody" class="gf-table-scroll" style="max-height:360px;overflow:auto"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="autoMapConfirmButton" class="btn btn-warning fw-bold" style="border-radius:999px" onclick="confirmUnmappedAutoMap()">
                    Jalankan Mapping
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const { api, esc, channelPill } = window.mpHelpers;
    const FMT = new Intl.NumberFormat('id-ID');
    let unmappedList = [];
    let mappingsPaginator = null;
    let currentPage = 1;
    let currentSearch = '';
    let currentChannel = '';
    let currentStockStatus = '';
    let visibleMappings = [];
    let unmappedSearchTimeout;
    let unmappedFilterOptionsLoaded = false;
    let pendingAutoMapStoreId = '';

    const $ = id => document.getElementById(id);

    async function loadData() {
        $('unmappedBody').innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        const unmappedParams = unmappedRequestParams();
        const params = mappingParams();

        const [um, mp] = await Promise.allSettled([
            api('/api/sku-mappings/unmapped-skus?' + unmappedParams.toString()),
            api('/api/sku-mappings?' + params.toString()),
        ]);

        if (um.status === 'rejected') {
            console.error('Failed to load unmapped SKUs:', um.reason);
            $('unmappedBody').innerHTML = '<div class="oc-empty text-danger">Gagal memuat SKU belum dipetakan.</div>';
        } else {
            unmappedList = um.value || [];
            renderUnmapped();
        }

        if (mp.status === 'rejected') {
            console.error('Failed to load mappings:', mp.reason);
        } else {
            mappingsPaginator = mp.value || null;
        }

        renderKpi();
    }

    async function loadUnmappedFilterOptions() {
        if (unmappedFilterOptionsLoaded) return;
        unmappedFilterOptionsLoaded = true;

        const [storesResult, channelsResult] = await Promise.allSettled([
            api('/api/marketplace/stores'),
            api('/api/marketplace/channels'),
        ]);

        if (storesResult.status === 'fulfilled') {
            const stores = Array.isArray(storesResult.value) ? storesResult.value : [];
            const select = $('unmappedStoreFilter');
            select.innerHTML = '<option value="">Semua Toko</option>' + stores.map(store => {
                const channel = store.channel?.name ? ` · ${store.channel.name}` : '';
                return `<option value="${esc(store.id)}">${esc(store.name)}${esc(channel)}</option>`;
            }).join('');
        }

        if (channelsResult.status === 'fulfilled') {
            const channels = Array.isArray(channelsResult.value) ? channelsResult.value : [];
            const select = $('unmappedChannelFilter');
            const current = select.value;
            select.innerHTML = '<option value="">Semua Channel</option>' + channels.map(channel =>
                `<option value="${esc(String(channel.code || '').toLowerCase())}">${esc(channel.name || channel.code)}</option>`
            ).join('');
            if (current) select.value = current;
        }
    }

    function initUnmappedDateRange() {
        const input = $('unmappedDateRange');
        if (!input || !window.flatpickr) return;

        window.flatpickr(input, {
            mode: 'range',
            dateFormat: 'd/m/Y',
            allowInput: false,
            locale: (window.flatpickr.l10ns && window.flatpickr.l10ns.id)
                ? window.flatpickr.l10ns.id
                : { firstDayOfWeek: 1 },
            onChange(selectedDates, dateString, instance) {
                const from = selectedDates[0]
                    ? window.flatpickr.formatDate(selectedDates[0], 'Y-m-d')
                    : '';
                const to = selectedDates[1]
                    ? window.flatpickr.formatDate(selectedDates[1], 'Y-m-d')
                    : '';

                $('unmappedDateFrom').value = from;
                $('unmappedDateTo').value = to;
                instance.input.value = selectedDates.length === 2
                    ? `${window.flatpickr.formatDate(selectedDates[0], 'd/m/Y')} — ${window.flatpickr.formatDate(selectedDates[1], 'd/m/Y')}`
                    : selectedDates.length === 1
                        ? window.flatpickr.formatDate(selectedDates[0], 'd/m/Y')
                        : '';

                reloadUnmappedOnly();
            },
        });
    }

    function unmappedRequestParams() {
        const params = new URLSearchParams();
        const search = $('unmappedSearch').value.trim();
        const storeId = $('unmappedStoreFilter').value;
        const channel = $('unmappedChannelFilter').value;
        const dateFrom = $('unmappedDateFrom').value;
        const dateTo = $('unmappedDateTo').value;
        const sort = $('unmappedSort').value;

        if (search) params.append('search', search);
        if (storeId) params.append('store_id', storeId);
        if (channel) params.append('channel_code', channel);
        if (dateFrom) params.append('order_date_from', dateFrom);
        if (dateTo) params.append('order_date_to', dateTo);
        if (sort) params.append('sort', sort);
        return params;
    }

    function unmappedHasFilters() {
        return Boolean($('unmappedSearch').value.trim()
            || $('unmappedStoreFilter').value
            || $('unmappedChannelFilter').value
            || $('unmappedDateFrom').value
            || $('unmappedDateTo').value);
    }

    async function reloadUnmappedOnly() {
        $('unmappedBody').innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        try {
            unmappedList = await api('/api/sku-mappings/unmapped-skus?' + unmappedRequestParams().toString());
            renderUnmapped();
            renderKpi();
        } catch (e) {
            console.error(e);
            $('unmappedBody').innerHTML = '<div class="oc-empty text-danger">Gagal memuat SKU belum dipetakan.</div>';
        }
    }
    
    async function reloadMappingsOnly() {
        const params = mappingParams();
        
        try {
            mappingsPaginator = await api('/api/sku-mappings?' + params.toString());
            renderKpi();
        } catch (e) {
            console.error(e);
        }
    }

    function mappingParams() {
        const params = new URLSearchParams({ page: currentPage, per_page: 50 });
        if (currentSearch) params.append('search', currentSearch);
        if (currentChannel) params.append('channel_code', currentChannel);
        if (currentStockStatus) params.append('stock_status', currentStockStatus);
        return params;
    }

    function renderKpi() {
        const unmapped = unmappedList.length;
        const mapped   = mappingsPaginator ? (mappingsPaginator.total || 0) : 0;
        const total    = unmapped + mapped;
        const pct      = total > 0 ? Math.round((mapped / total) * 100) : 0;
        $('kpiUnmapped').textContent  = unmapped;
        $('kpiMapped').textContent    = mapped;
        $('kpiTotalSku').textContent  = total;
        $('kpiCoverage').textContent  = pct + '%';
    }

    function renderUnmapped() {
        const body = $('unmappedBody');
        if (!unmappedList.length) {
            body.innerHTML = unmappedHasFilters()
                ? '<div class="oc-empty">Tidak ada SKU belum dipetakan yang cocok dengan filter.</div>'
                : '<div class="oc-empty">🎉 Semua SKU sudah dipetakan.</div>';
            return;
        }
        body.innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead><tr>
                <th>SKU Marketplace</th><th>Nama Item (di Marketplace)</th><th>Nama Variant</th>
                <th>Toko</th>
                <th class="text-end">Jumlah Order</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            ${unmappedList.map(u => `<tr>
                <td>
                    <code style="font-size:.8rem;font-weight:700">${esc(u.sku)}</code>
                    ${u.channel_code ? `<br><span class="oc-badge oc-badge-muted" style="font-size:.65rem">${channelPill({code: u.channel_code, name: u.channel_code})}</span>` : ''}
                </td>
                <td style="font-size:.82rem;color:#475569">${esc(u.item_name || '—')}</td>
                <td style="font-size:.78rem;color:#475569">${esc(u.variant_name || '—')}</td>
                <td style="font-size:.78rem;color:#475569">${esc(u.store_names || '—')}</td>
                <td class="text-end" style="font-size:.8rem;font-weight:700">${u.order_count}</td>
                <td class="text-end">
                    <button class="btn btn-dark btn-sm fw-bold" style="border-radius:999px;font-size:.72rem"
                        onclick="openUnmappedMapping('${encodeURIComponent(String(u.sku || ''))}','${encodeURIComponent(String(u.channel_code || ''))}')">+ Map</button>
                </td>
            </tr>`).join('')}
            </tbody>
        </table></div>
        <div class="gf-table-foot"><span class="gf-table-foot-hint">${unmappedList.length} SKU belum dipetakan</span></div>`;
    }

    function renderMappings() {
        const body = $('mappingsBody');
        const rows = mappingsPaginator && mappingsPaginator.data ? mappingsPaginator.data : [];
        visibleMappings = rows;

        if (!rows.length) {
            body.innerHTML = currentSearch || currentChannel || currentStockStatus
                ? '<div class="oc-empty">Tidak ada mapping yang cocok dengan filter.</div>'
                : '<div class="oc-empty">Belum ada mapping terdaftar.</div>';
            return;
        }

        let html = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead><tr>
                <th>Marketplace SKU</th><th>Channel</th><th>Item Internal</th>
                <th class="text-end">Stok Tersedia</th>
                <th>Catatan</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            ${rows.map(m => `<tr>
                <td><code style="font-size:.8rem;font-weight:700">${esc(m.marketplace_sku)}</code></td>
                <td>${m.channel_code ? channelPill({code: m.channel_code, name: m.channel_code}) : '<span class="oc-badge oc-badge-muted" style="font-size:.68rem">Semua</span>'}</td>
                <td>
                    ${m.item
                        ? `<span class="fw-bold" style="font-size:.82rem">${esc(m.item.code)}</span><br>
                           <span class="text-muted" style="font-size:.72rem">${esc(m.item.name)}</span>`
                        : '<span class="text-danger" style="font-size:.78rem">Item dihapus</span>'}
                </td>
                <td class="text-end" style="font-size:.82rem;">
                    ${m.item 
                        ? `<span class="fw-bold text-success">${FMT.format(m.item.stock_available || 0)}</span>
                           <br><span class="text-muted" style="font-size:.7rem;">Fisik: ${FMT.format(m.item.stock_physical || 0)}</span>`
                        : '—'}
                </td>
                <td style="font-size:.78rem;color:#64748b">${esc(m.notes || '—')}</td>
                <td class="text-end">
                    <button class="btn btn-light border btn-sm me-1" style="border-radius:999px;font-size:.72rem;color:#1d4ed8"
                        onclick="editMapping(${m.id})">Edit</button>
                    <button class="btn btn-light border btn-sm" style="border-radius:999px;font-size:.72rem;color:#b91c1c"
                        onclick="deleteMapping(${m.id})">Hapus</button>
                </td>
            </tr>`).join('')}
            </tbody>
        </table></div>`;

        // Pagination UI
        let linksHtml = '';
        if (mappingsPaginator && mappingsPaginator.last_page > 1) {
            linksHtml += '<div class="btn-group">';
            
            if (mappingsPaginator.current_page > 1) {
                linksHtml += `<button class="btn btn-sm btn-light border" onclick="goToPage(${mappingsPaginator.current_page - 1})">Prev</button>`;
            } else {
                linksHtml += `<button class="btn btn-sm btn-light border" disabled>Prev</button>`;
            }

            let start = Math.max(1, mappingsPaginator.current_page - 2);
            let end = Math.min(mappingsPaginator.last_page, mappingsPaginator.current_page + 2);
            
            for(let p = start; p <= end; p++) {
                if (p === mappingsPaginator.current_page) {
                    linksHtml += `<button class="btn btn-sm btn-primary active">${p}</button>`;
                } else {
                    linksHtml += `<button class="btn btn-sm btn-light border" onclick="goToPage(${p})">${p}</button>`;
                }
            }

            if (mappingsPaginator.current_page < mappingsPaginator.last_page) {
                linksHtml += `<button class="btn btn-sm btn-light border" onclick="goToPage(${mappingsPaginator.current_page + 1})">Next</button>`;
            } else {
                linksHtml += `<button class="btn btn-sm btn-light border" disabled>Next</button>`;
            }
            linksHtml += '</div>';
        }

        html += `
        <div style="padding:.5rem .75rem; border-top:1px solid var(--shp-border); display:flex; justify-content:space-between; align-items:center; font-size:.75rem; color:var(--shp-muted);">
            <div>Menampilkan baris ${mappingsPaginator.from || 0} - ${mappingsPaginator.to || 0} dari total ${mappingsPaginator.total || 0}</div>
            <div>${linksHtml}</div>
        </div>`;

        body.innerHTML = html;
    }

    window.goToPage = function(page) {
        currentPage = page;
        reloadMappingsOnly();
    };

    window.editMapping = function (id) {
        const mapping = visibleMappings.find(row => Number(row.id) === Number(id));
        if (mapping) mpMapping.open(mapping.marketplace_sku, loadData, mapping);
    };

    window.openUnmappedMapping = function (encodedSku, encodedChannel) {
        const sku = decodeURIComponent(encodedSku);
        const channel = decodeURIComponent(encodedChannel);
        mpMapping.open(sku, loadData, { marketplace_sku: sku, channel_code: channel });
    };

    window.deleteMapping = async function (id) {
        if (!confirm('Hapus mapping ini?')) return;
        try {
            await api('/api/sku-mappings/' + id, { method: 'DELETE' });
            loadData();
        } catch (e) { alert(e.message); }
    };

    let searchTimeout;
    window.filterMappings = function (q) {
        clearTimeout(searchTimeout);
        currentSearch = q;
        currentPage = 1;
        searchTimeout = setTimeout(() => {
            reloadMappingsOnly();
        }, 400);
    };

    window.filterUnmapped = function () {
        clearTimeout(unmappedSearchTimeout);
        unmappedSearchTimeout = setTimeout(reloadUnmappedOnly, 350);
    };

    window.applyUnmappedFilters = function () {
        reloadUnmappedOnly();
    };

    function renderAutoMapPreview(preview) {
        const matches = Array.isArray(preview?.matches) ? preview.matches : [];
        const confirmBtn = $('autoMapConfirmButton');
        const storeName = pendingAutoMapStoreId
            ? $('unmappedStoreFilter').selectedOptions[0]?.textContent || 'Toko terpilih'
            : 'Semua toko';

        $('autoMapPreviewSummary').innerHTML = `
            <div class="alert alert-warning mb-0" style="border-radius:12px;font-size:.8rem">
                <strong>${FMT.format(preview?.total_matches || 0)} SKU</strong> akan dipetakan untuk <strong>${esc(storeName)}</strong>,
                mencakup <strong>${FMT.format(preview?.total_order_items || 0)} order item</strong>.
            </div>`;

        if (!matches.length) {
            $('autoMapPreviewBody').innerHTML = '<div class="oc-empty">Tidak ada order item yang perlu dipetakan otomatis. SKU mungkin sudah terpetakan atau kode itemnya belum ada di master.</div>';
            confirmBtn.disabled = true;
            return;
        }

        confirmBtn.disabled = false;
        $('autoMapPreviewBody').innerHTML = `
            <table class="gf-clean-table w-100">
                <thead><tr>
                    <th>SKU Marketplace</th><th>Channel</th><th>Item Master</th>
                    <th>Cocok Berdasarkan</th><th class="text-end">Order Item</th>
                </tr></thead>
                <tbody>${matches.map(match => `
                    <tr>
                        <td>
                            <code style="font-size:.78rem;font-weight:700">${esc(match.marketplace_sku)}</code>
                            <br><span class="text-muted" style="font-size:.68rem">${esc((match.store_names || []).join(', ') || '—')}</span>
                        </td>
                        <td style="font-size:.78rem">${esc(match.channel_code || 'Global')}</td>
                        <td>
                            <strong style="font-size:.8rem">${esc(match.item_code)}</strong><br>
                            <span class="text-muted" style="font-size:.72rem">${esc(match.item_name)}</span>
                        </td>
                        <td style="font-size:.75rem">${match.matched_by === 'exact' ? 'Kode sama persis' : 'Prefix SKU varian'}<br>
                            <code style="font-size:.68rem">${esc(match.matched_code)}</code>
                        </td>
                        <td class="text-end" style="font-size:.8rem;font-weight:700">${FMT.format(match.order_count || 0)}</td>
                    </tr>`).join('')}</tbody>
            </table>`;
    }

    window.runUnmappedAutoMap = async function () {
        const btn = $('unmappedAutoMapBtn');
        pendingAutoMapStoreId = $('unmappedStoreFilter').value || '';
        btn.disabled = true;
        btn.textContent = '⏳ Menyiapkan…';

        try {
            const query = pendingAutoMapStoreId ? '?store_id=' + encodeURIComponent(pendingAutoMapStoreId) : '';
            const preview = await api('/api/marketplace/auto-map-by-code/preview' + query);
            renderAutoMapPreview(preview);
            bootstrap.Modal.getOrCreateInstance($('autoMapConfirmModal')).show();
        } catch (e) {
            if (window.GFID?.errorAlert) {
                window.GFID.errorAlert(e.message);
            } else {
                alert(e.message);
            }
        } finally {
            btn.disabled = false;
            btn.textContent = '⚡ Mapping Otomatis';
        }
    };

    window.confirmUnmappedAutoMap = async function () {
        const confirmBtn = $('autoMapConfirmButton');
        const btn = $('unmappedAutoMapBtn');
        confirmBtn.disabled = true;
        confirmBtn.textContent = '⏳ Memproses…';

        try {
            const query = pendingAutoMapStoreId ? '?store_id=' + encodeURIComponent(pendingAutoMapStoreId) : '';
            const res = await api('/api/marketplace/auto-map-by-code' + query, { method: 'POST' });
            const mapped = Number(res.mapped || 0);
            const message = mapped > 0
                ? `Mapping otomatis selesai — ${mapped} item berhasil dipetakan${res.skipped ? ', ' + res.skipped + ' tidak cocok' : ''}.`
                : 'Tidak ada SKU yang cocok dengan kode item internal.';

            if (window.GFID?.toast) {
                window.GFID.toast(message, { icon: mapped > 0 ? 'success' : 'info' });
            } else {
                alert(message);
            }

            bootstrap.Modal.getInstance($('autoMapConfirmModal'))?.hide();
            await loadData();
        } catch (e) {
            if (window.GFID?.errorAlert) {
                window.GFID.errorAlert(e.message);
            } else {
                alert(e.message);
            }
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Jalankan Mapping';
            btn.disabled = false;
            btn.textContent = '⚡ Mapping Otomatis';
        }
    };

    window.resetUnmappedFilters = function () {
        $('unmappedSearch').value = '';
        $('unmappedStoreFilter').value = '';
        $('unmappedChannelFilter').value = '';
        $('unmappedDateFrom').value = '';
        $('unmappedDateTo').value = '';
        $('unmappedDateRange').value = '';
        $('unmappedDateRange')._flatpickr?.clear();
        $('unmappedSort').value = 'orders_desc';
        reloadUnmappedOnly();
    };

    window.applyMappingFilters = function () {
        currentChannel = $('mappingChannelFilter').value;
        currentStockStatus = $('mappingStockFilter').value;
        currentPage = 1;
        reloadMappingsOnly();
    };
    
    // Setup initial window globals
    window.loadMappings = loadData; // Alias for backward compatibility if used in modals
    window.loadData = loadData;
    
    initUnmappedDateRange();
    loadUnmappedFilterOptions();
    loadData();
})();
</script>
@endpush
