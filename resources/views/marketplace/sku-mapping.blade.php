@extends('layouts.app')
@section('title', 'Marketplace • SKU Mapping')

@include('marketplace._shared')

@section('content')
<x-gf.page eyebrow="Marketplace" title="SKU Mapping" description="Petakan SKU marketplace ke item internal agar fulfillment bisa diproses.">
    <x-slot:actions>
        <button type="button" class="btn btn-dark btn-sm fw-bold"
            style="border-radius:999px;font-size:.78rem"
            onclick="mpMapping.open('', loadMappings)">+ Tambah Mapping</button>
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
        <div id="unmappedBody"><div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div></div>
    </x-gf.panel>

    {{-- Existing Mappings --}}
    <x-gf.panel title="Mapping Terdaftar" subtitle="Seluruh mapping SKU marketplace → item internal.">
        <x-slot:actions>
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <input type="text" id="mappingSearch" placeholder="Cari SKU atau item…"
                    style="border-radius:999px;font-size:.75rem;padding:.3rem .85rem;border:1px solid rgba(15,23,42,.1);background:#fff;min-width:200px"
                    oninput="filterMappings(this.value)">
                <select id="mappingChannelFilter" class="form-select form-select-sm"
                    style="border-radius:999px;font-size:.75rem;width:auto;min-width:130px"
                    onchange="applyMappingFilters()">
                    <option value="">Semua Channel</option>
                    <option value="global">Global</option>
                    <option value="shopee">Shopee</option>
                    <option value="tiktok">TikTok</option>
                    <option value="tokopedia">Tokopedia</option>
                    <option value="lazada">Lazada</option>
                </select>
                <select id="mappingStockFilter" class="form-select form-select-sm"
                    style="border-radius:999px;font-size:.75rem;width:auto;min-width:125px"
                    onchange="applyMappingFilters()">
                    <option value="">Semua Stok</option>
                    <option value="available">Ada Stok</option>
                    <option value="empty">Stok Habis</option>
                </select>
            </div>
        </x-slot:actions>
        <div id="mappingsBody"><div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div></div>
    </x-gf.panel>
</x-gf.page>

@include('marketplace._mapping-modal')
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

    const $ = id => document.getElementById(id);

    async function loadData() {
        $('unmappedBody').innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        $('mappingsBody').innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';

        const params = mappingParams();

        const [um, mp] = await Promise.allSettled([
            api('/api/sku-mappings/unmapped-skus'),
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
            $('mappingsBody').innerHTML = '<div class="oc-empty text-danger">Gagal memuat daftar mapping.</div>';
        } else {
            mappingsPaginator = mp.value || null;
            renderMappings();
        }

        renderKpi();
    }
    
    async function reloadMappingsOnly() {
        $('mappingsBody').innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        
        const params = mappingParams();
        
        try {
            mappingsPaginator = await api('/api/sku-mappings?' + params.toString());
            renderMappings();
            renderKpi();
        } catch (e) {
            console.error(e);
            $('mappingsBody').innerHTML = '<div class="oc-empty text-danger">Gagal memuat daftar mapping.</div>';
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
            body.innerHTML = '<div class="oc-empty">🎉 Semua SKU sudah dipetakan.</div>';
            return;
        }
        body.innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead><tr>
                <th>Marketplace SKU</th><th>Nama Item (di Marketplace)</th>
                <th class="text-end">Jumlah Order</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            ${unmappedList.map(u => `<tr>
                <td>
                    <code style="font-size:.8rem;font-weight:700">${esc(u.sku)}</code>
                    ${u.channel_code ? `<br><span class="oc-badge oc-badge-muted" style="font-size:.65rem">${channelPill({code: u.channel_code, name: u.channel_code})}</span>` : ''}
                </td>
                <td style="font-size:.82rem;color:#475569">${esc(u.item_name || '—')}</td>
                <td class="text-end" style="font-size:.8rem;font-weight:700">${u.order_count}</td>
                <td class="text-end">
                    <button class="btn btn-dark btn-sm fw-bold" style="border-radius:999px;font-size:.72rem"
                        onclick="mpMapping.open('${esc(u.sku)}', window.loadData)">+ Map</button>
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
        if (mapping) mpMapping.open(mapping.marketplace_sku, window.loadData, mapping);
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

    window.applyMappingFilters = function () {
        currentChannel = $('mappingChannelFilter').value;
        currentStockStatus = $('mappingStockFilter').value;
        currentPage = 1;
        reloadMappingsOnly();
    };
    
    // Setup initial window globals
    window.loadMappings = loadData; // Alias for backward compatibility if used in modals
    window.loadData = loadData;
    
    loadData();
})();
</script>
@endpush
