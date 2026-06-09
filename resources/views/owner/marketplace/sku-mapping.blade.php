@extends('layouts.app')
@section('title', 'Marketplace • SKU Mapping')

@include('owner.marketplace._shared')

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
            <input type="text" id="mappingSearch" placeholder="Cari SKU atau item…"
                style="border-radius:999px;font-size:.75rem;padding:.3rem .85rem;border:1px solid rgba(15,23,42,.1);background:#fff;min-width:200px"
                oninput="filterMappings(this.value)">
        </x-slot:actions>
        <div id="mappingsBody"><div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div></div>
    </x-gf.panel>
</x-gf.page>

@include('owner.marketplace._mapping-modal')
@endsection

@push('scripts')
<script>
(function () {
    const { api, esc, channelPill } = window.mpHelpers;
    let unmappedList = [], mappingsList = [];

    const $ = id => document.getElementById(id);

    async function loadMappings() {
        $('unmappedBody').innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        $('mappingsBody').innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';

        const [um, mp] = await Promise.allSettled([
            api('/api/sku-mappings/unmapped-skus'),
            api('/api/sku-mappings'),
        ]);
        unmappedList = um.value || [];
        mappingsList  = mp.value || [];

        renderKpi();
        renderUnmapped();
        renderMappings('');
    }

    function renderKpi() {
        const unmapped = unmappedList.length;
        const mapped   = mappingsList.length;
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
                <td><code style="font-size:.8rem;font-weight:700">${esc(u.sku)}</code></td>
                <td style="font-size:.82rem;color:#475569">${esc(u.item_name || '—')}</td>
                <td class="text-end" style="font-size:.8rem;font-weight:700">${u.order_count}</td>
                <td class="text-end">
                    <button class="btn btn-dark btn-sm fw-bold" style="border-radius:999px;font-size:.72rem"
                        onclick="mpMapping.open('${esc(u.sku)}', loadMappings)">+ Map</button>
                </td>
            </tr>`).join('')}
            </tbody>
        </table></div>
        <div class="gf-table-foot"><span class="gf-table-foot-hint">${unmappedList.length} SKU belum dipetakan</span></div>`;
    }

    function renderMappings(q) {
        const body = $('mappingsBody');
        const rows = q
            ? mappingsList.filter(m =>
                (m.marketplace_sku||'').toLowerCase().includes(q.toLowerCase()) ||
                (m.item?.code||'').toLowerCase().includes(q.toLowerCase()) ||
                (m.item?.name||'').toLowerCase().includes(q.toLowerCase()))
            : mappingsList;

        if (!rows.length) {
            body.innerHTML = q
                ? '<div class="oc-empty">Tidak ada mapping yang cocok.</div>'
                : '<div class="oc-empty">Belum ada mapping terdaftar.</div>';
            return;
        }

        body.innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead><tr>
                <th>Marketplace SKU</th><th>Channel</th><th>Item Internal</th>
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
                <td style="font-size:.78rem;color:#64748b">${esc(m.notes || '—')}</td>
                <td class="text-end">
                    <button class="btn btn-light border btn-sm" style="border-radius:999px;font-size:.72rem;color:#b91c1c"
                        onclick="deleteMapping(${m.id})">Hapus</button>
                </td>
            </tr>`).join('')}
            </tbody>
        </table></div>
        <div class="gf-table-foot"><span class="gf-table-foot-hint">${rows.length} mapping ditampilkan</span></div>`;
    }

    window.deleteMapping = async function (id) {
        if (!confirm('Hapus mapping ini?')) return;
        try {
            await api('/api/sku-mappings/' + id, { method: 'DELETE' });
            loadMappings();
        } catch (e) { alert(e.message); }
    };

    window.filterMappings = function (q) { renderMappings(q); };
    window.loadMappings   = loadMappings;
    loadMappings();
})();
</script>
@endpush
