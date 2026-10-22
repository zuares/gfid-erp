@extends('layouts.app')
@section('title', 'Marketplace • Mapping Terdaftar')

@include('marketplace._shared')

@section('content')
<x-gf.page eyebrow="Marketplace" title="Mapping Terdaftar" description="Kelola mapping SKU marketplace yang sudah terhubung ke item internal.">
    <x-slot:actions>
        <a href="{{ route('marketplace.sku-mapping') }}" class="btn btn-light border btn-sm fw-bold"
            style="border-radius:999px;font-size:.78rem">← SKU Belum Dipetakan</a>
        <button type="button" class="btn btn-dark btn-sm fw-bold"
            style="border-radius:999px;font-size:.78rem"
            onclick="mpMapping.open('', loadData)">+ Tambah Mapping</button>
        <button type="button" class="btn btn-light border btn-sm"
            style="border-radius:999px;font-size:.78rem;font-weight:700"
            onclick="loadData()">↻ Refresh</button>
    </x-slot:actions>

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
    let mappingsPaginator = null;
    let currentPage = 1;
    let currentSearch = '';
    let currentChannel = '';
    let currentStockStatus = '';
    let visibleMappings = [];
    let searchTimeout;
    const $ = id => document.getElementById(id);

    function mappingParams() {
        const params = new URLSearchParams({ page: currentPage, per_page: 50 });
        if (currentSearch) params.append('search', currentSearch);
        if (currentChannel) params.append('channel_code', currentChannel);
        if (currentStockStatus) params.append('stock_status', currentStockStatus);
        return params;
    }

    function renderMappings() {
        const body = $('mappingsBody');
        const rows = mappingsPaginator?.data || [];
        visibleMappings = rows;

        if (!rows.length) {
            body.innerHTML = currentSearch || currentChannel || currentStockStatus
                ? '<div class="oc-empty">Tidak ada mapping yang cocok dengan filter.</div>'
                : '<div class="oc-empty">Belum ada mapping terdaftar.</div>';
            return;
        }

        let html = `<div class="gf-table-scroll"><table class="gf-clean-table w-100">
            <thead><tr><th>Marketplace SKU</th><th>Channel</th><th>Item Internal</th>
                <th class="text-end">Stok Tersedia</th><th>Catatan</th><th class="text-end">Aksi</th></tr></thead><tbody>
            ${rows.map(m => `<tr>
                <td><code style="font-size:.8rem;font-weight:700">${esc(m.marketplace_sku)}</code></td>
                <td>${m.channel_code ? channelPill({code: m.channel_code, name: m.channel_code}) : '<span class="oc-badge oc-badge-muted" style="font-size:.68rem">Semua</span>'}</td>
                <td>${m.item ? `<span class="fw-bold" style="font-size:.82rem">${esc(m.item.code)}</span><br><span class="text-muted" style="font-size:.72rem">${esc(m.item.name)}</span>` : '<span class="text-danger" style="font-size:.78rem">Item dihapus</span>'}</td>
                <td class="text-end" style="font-size:.82rem">${m.item ? `<span class="fw-bold text-success">${FMT.format(m.item.stock_available || 0)}</span><br><span class="text-muted" style="font-size:.7rem">Fisik: ${FMT.format(m.item.stock_physical || 0)}</span>` : '—'}</td>
                <td style="font-size:.78rem;color:#64748b">${esc(m.notes || '—')}</td>
                <td class="text-end"><button class="btn btn-light border btn-sm me-1" style="border-radius:999px;font-size:.72rem;color:#1d4ed8" onclick="editMapping(${m.id})">Edit</button><button class="btn btn-light border btn-sm" style="border-radius:999px;font-size:.72rem;color:#b91c1c" onclick="deleteMapping(${m.id})">Hapus</button></td>
            </tr>`).join('')}</tbody></table></div>`;

        let links = '';
        if (mappingsPaginator.last_page > 1) {
            links += '<div class="btn-group">';
            links += `<button class="btn btn-sm btn-light border" ${mappingsPaginator.current_page <= 1 ? 'disabled' : `onclick="goToPage(${mappingsPaginator.current_page - 1})"`}>Prev</button>`;
            const start = Math.max(1, mappingsPaginator.current_page - 2);
            const end = Math.min(mappingsPaginator.last_page, mappingsPaginator.current_page + 2);
            for (let page = start; page <= end; page++) {
                links += page === mappingsPaginator.current_page
                    ? `<button class="btn btn-sm btn-primary active">${page}</button>`
                    : `<button class="btn btn-sm btn-light border" onclick="goToPage(${page})">${page}</button>`;
            }
            links += `<button class="btn btn-sm btn-light border" ${mappingsPaginator.current_page >= mappingsPaginator.last_page ? 'disabled' : `onclick="goToPage(${mappingsPaginator.current_page + 1})"`}>Next</button></div>`;
        }
        html += `<div style="padding:.5rem .75rem;border-top:1px solid var(--shp-border);display:flex;justify-content:space-between;align-items:center;font-size:.75rem;color:var(--shp-muted)"><div>Menampilkan baris ${mappingsPaginator.from || 0} - ${mappingsPaginator.to || 0} dari total ${mappingsPaginator.total || 0}</div><div>${links}</div></div>`;
        body.innerHTML = html;
    }

    async function loadData() {
        $('mappingsBody').innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        try {
            mappingsPaginator = await api('/api/sku-mappings?' + mappingParams().toString());
            renderMappings();
        } catch (error) {
            console.error(error);
            $('mappingsBody').innerHTML = '<div class="oc-empty text-danger">Gagal memuat daftar mapping.</div>';
        }
    }

    window.goToPage = page => { currentPage = page; loadData(); };
    window.editMapping = id => {
        const mapping = visibleMappings.find(row => Number(row.id) === Number(id));
        if (mapping) mpMapping.open(mapping.marketplace_sku, loadData, mapping);
    };
    window.deleteMapping = async id => {
        if (!confirm('Hapus mapping ini?')) return;
        try { await api('/api/sku-mappings/' + id, { method: 'DELETE' }); loadData(); }
        catch (error) { alert(error.message); }
    };
    window.filterMappings = query => {
        clearTimeout(searchTimeout);
        currentSearch = query;
        currentPage = 1;
        searchTimeout = setTimeout(loadData, 400);
    };
    window.applyMappingFilters = () => {
        currentChannel = $('mappingChannelFilter').value;
        currentStockStatus = $('mappingStockFilter').value;
        currentPage = 1;
        loadData();
    };
    window.loadData = loadData;
    loadData();
})();
</script>
@endpush
