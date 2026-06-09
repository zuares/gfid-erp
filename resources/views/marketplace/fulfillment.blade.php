@extends('layouts.app')
@section('title', 'Marketplace • Fulfillment')

@include('marketplace._shared')

@section('content')
<x-gf.page eyebrow="Marketplace" title="Fulfillment" description="Review dan konfirmasi order sebelum stok dipotong.">

    {{-- KPI --}}
    <div class="oc-kpi-grid">
        <div class="oc-kpi-card"><div class="oc-kpi-label">Perlu Review</div><div class="oc-kpi-value" id="kpiPending">—</div><div class="oc-kpi-note">draft / pending</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Unresolved SKU</div><div class="oc-kpi-value" id="kpiUnresolved">—</div><div class="oc-kpi-note">butuh mapping</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Stok Kurang</div><div class="oc-kpi-value" id="kpiShortage">—</div><div class="oc-kpi-note">ada kekurangan stok</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Siap Konfirmasi</div><div class="oc-kpi-value" id="kpiReady">—</div><div class="oc-kpi-note">semua resolved & stok cukup</div></div>
    </div>

    {{-- Warning: Data Perlu Diperbaiki banner --}}
    <div id="fulfillIncompleteDataBanner" style="display:none;margin-bottom:.75rem">
        <div style="background:rgba(239,68,68,.07);border:1.5px solid rgba(239,68,68,.25);border-radius:14px;padding:.75rem 1rem;display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap">
            <div style="display:flex;align-items:center;gap:.55rem">
                <span style="font-size:1rem">⚠️</span>
                <div>
                    <span class="fw-bold" style="font-size:.82rem;color:#991b1b">Ada <span id="bannerIncompleteCount">?</span> order dengan data belum lengkap.</span>
                    <span style="font-size:.78rem;color:#b91c1c"> Order tersebut tidak ditampilkan di sini sampai data diperbaiki.</span>
                </div>
            </div>
            <a href="{{ route('marketplace.issues') }}" class="btn btn-danger btn-sm fw-bold"
                style="border-radius:999px;font-size:.75rem;white-space:nowrap">→ Perbaiki Data</a>
        </div>
    </div>

    {{-- Warning banner --}}
    <div id="fulfillUnmappedBanner" style="display:none;margin-bottom:.75rem">
        <div style="background:rgba(245,158,11,.1);border:1.5px solid rgba(245,158,11,.35);border-radius:14px;padding:.75rem 1rem;display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap">
            <div style="display:flex;align-items:center;gap:.55rem">
                <span style="font-size:1rem">⚠️</span>
                <div>
                    <span class="fw-bold" style="font-size:.82rem;color:#92400e">Ada <span id="bannerUnmappedCount">?</span> item belum dipetakan.</span>
                    <span style="font-size:.78rem;color:#b45309"> Tambahkan mapping dulu agar item ter-resolve otomatis.</span>
                </div>
            </div>
            <a href="{{ route('marketplace.sku-mapping') }}" class="btn btn-warning btn-sm fw-bold"
                style="border-radius:999px;font-size:.75rem;white-space:nowrap">→ Buka SKU Mapping</a>
        </div>
    </div>

    <x-gf.panel title="Fulfillment Queue" subtitle="Order yang perlu dikonfirmasi sebelum stok dipotong.">
        <x-slot:actions>
            <button type="button" class="btn btn-warning btn-sm fw-bold" id="remapAllBtn"
                style="border-radius:999px;font-size:.75rem"
                onclick="remapAll()">⚡ Apply Mapping</button>
            <button type="button" class="btn btn-light border btn-sm"
                style="border-radius:999px;font-size:.75rem;font-weight:700"
                onclick="loadFulfillments()">↻ Refresh</button>
        </x-slot:actions>
        <div id="fulfillBody"><div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div></div>
    </x-gf.panel>
</x-gf.page>

{{-- Fulfillment Detail Modal --}}
<div class="modal fade" id="fulfillModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-black" id="fulfillModalTitle">Detail Fulfillment</h5>
                    <div class="text-muted" style="font-size:.8rem" id="fulfillModalSub"></div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="fulfillModalBody">
                <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
            </div>
            <div class="modal-footer border-0">
                <div id="fulfillModalAlert" class="alert d-none w-100 mb-0" style="border-radius:12px;font-size:.85rem"></div>
                <button class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Tutup</button>
                <button class="btn btn-success fw-bold" style="border-radius:999px" id="fulfillConfirmBtn">
                    ✓ Konfirmasi & Potong Stok
                </button>
            </div>
        </div>
    </div>
</div>

@include('marketplace._mapping-modal')
@endsection

@push('scripts')
<script>
(function () {
    const { api, fmtDate, esc, channelPill, statusBadge } = window.mpHelpers;
    let fulfillments = [], currentFulfillId = null;
    const $ = id => document.getElementById(id);

    // Check for incomplete data orders (show/hide banner)
    async function checkIncompleteBanner() {
        try {
            const s = await api('/api/marketplace/issue-summary');
            const cnt = s.data_incomplete || s.profit_incomplete || 0;
            if (cnt > 0) {
                document.getElementById('bannerIncompleteCount').textContent = cnt;
                document.getElementById('fulfillIncompleteDataBanner').style.display = 'block';
            }
        } catch {}
    }

    async function loadFulfillments() {
        $('fulfillBody').innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        fulfillments = await api('/api/fulfillments').catch(() => []);
        renderKpi();
        renderFulfillments();
    }

    function renderKpi() {
        const pending    = fulfillments.length;
        const unresolved = fulfillments.filter(f => !f.all_resolved).length;
        const shortage   = fulfillments.filter(f => f.has_shortage).length;
        const ready      = fulfillments.filter(f => f.all_resolved && !f.has_shortage).length;
        $('kpiPending').textContent    = pending;
        $('kpiUnresolved').textContent = unresolved;
        $('kpiShortage').textContent   = shortage;
        $('kpiReady').textContent      = ready;

        const unmappedLines = fulfillments.reduce((n, f) => n + (f.lines_count - f.lines_resolved), 0);
        const banner = $('fulfillUnmappedBanner');
        if (unmappedLines > 0) { $('bannerUnmappedCount').textContent = unmappedLines; banner.style.display = 'block'; }
        else { banner.style.display = 'none'; }
    }

    function renderFulfillments() {
        const body = $('fulfillBody');
        if (!fulfillments.length) { body.innerHTML = '<div class="oc-empty">Tidak ada order yang perlu dikonfirmasi.</div>'; return; }
        body.innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead><tr><th>Order</th><th>Toko</th><th>Status</th><th>Item</th><th>Stok</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            ${fulfillments.map(f => {
                const stockCls = !f.all_resolved ? 'oc-badge-red' : f.has_shortage ? 'oc-badge-amber' : 'oc-badge-green';
                const stockLbl = !f.all_resolved ? 'Belum Mapped' : f.has_shortage ? 'Stok Kurang' : 'Siap';
                return `<tr>
                    <td>
                        <div class="fw-bold" style="font-size:.8rem">${esc(f.order?.channel_order_id||'—')}</div>
                        <div class="text-muted" style="font-size:.7rem">${f.order?.ordered_at ? fmtDate(f.order.ordered_at) : '—'}</div>
                    </td>
                    <td>
                        <div style="font-size:.82rem">${esc(f.order?.store?.name||'—')}</div>
                        <div class="text-muted" style="font-size:.72rem">${esc(f.order?.store?.channel||'—')}</div>
                    </td>
                    <td>${statusBadge(f.status)}</td>
                    <td style="font-size:.78rem">${f.lines_resolved}/${f.lines_count} resolved</td>
                    <td><span class="oc-badge ${stockCls}">${stockLbl}</span></td>
                    <td class="text-end">
                        <button class="btn btn-dark btn-sm" style="border-radius:999px;font-size:.73rem;font-weight:700"
                            onclick="openFulfillment(${f.id})">Review →</button>
                    </td>
                </tr>`;
            }).join('')}
            </tbody>
        </table></div>
        <div class="gf-table-foot"><span class="gf-table-foot-hint">${fulfillments.length} order pending</span></div>`;
    }

    window.openFulfillment = async function (id) {
        currentFulfillId = id;
        $('fulfillModalTitle').textContent = 'Fulfillment #' + id;
        $('fulfillModalSub').textContent   = 'Review item, edit jika perlu, lalu konfirmasi.';
        $('fulfillModalBody').innerHTML    = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        $('fulfillModalAlert').className   = 'alert d-none w-100 mb-0';
        $('fulfillConfirmBtn').disabled    = false;
        $('fulfillConfirmBtn').textContent = '✓ Konfirmasi & Potong Stok';
        new bootstrap.Modal($('fulfillModal')).show();
        const f = await api('/api/fulfillments/' + id).catch(() => null);
        if (!f) { $('fulfillModalBody').innerHTML = '<div class="oc-empty text-danger">Gagal memuat data.</div>'; return; }
        renderDetail(f);
    };

    function renderDetail(f) {
        $('fulfillModalTitle').textContent = 'Fulfillment — ' + (f.order?.channel_order_id || '#' + f.id);
        $('fulfillModalSub').textContent   = `${f.order?.store?.name} · ${f.order?.store?.channel} · ${f.warehouse?.name || 'Belum ada gudang'}`;
        $('fulfillModalBody').innerHTML = `
        <div class="table-responsive">
        <table class="table align-middle" style="font-size:.85rem">
            <thead class="table-light"><tr>
                <th>SKU Marketplace</th><th>Nama Item</th><th>Item Internal</th><th>Lot</th>
                <th class="text-center">Dipesan</th><th class="text-center">Dipenuhi</th><th>Stok</th><th></th>
            </tr></thead>
            <tbody id="fulfillLinesBody">
            ${(f.lines||[]).map(l => renderLine(l)).join('')}
            </tbody>
        </table></div>`;
    }

    function renderLine(l) {
        const statusMap = { ok: 'oc-badge-green', low: 'oc-badge-amber', empty: 'oc-badge-red', unresolved: 'oc-badge-red' };
        const statusLbl = { ok: 'Cukup', low: 'Kurang', empty: 'Habis', unresolved: 'Belum Mapped' };
        return `<tr id="fline-${l.id}">
            <td><code style="font-size:.78rem">${esc(l.marketplace_sku||'—')}</code></td>
            <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(l.marketplace_item_name||'—')}</td>
            <td>
                ${l.item ? `<span class="fw-bold">${esc(l.item.code)}</span><br><span class="text-muted" style="font-size:.72rem">${esc(l.item.name)}</span>` : `<span class="text-danger" style="font-size:.78rem">Belum dipetakan</span>`}
                ${l.substituted ? '<span class="oc-badge oc-badge-amber ms-1">Diganti</span>' : ''}
            </td>
            <td style="font-size:.78rem;color:var(--gf-muted)">${l.lot ? esc(l.lot.code) : '—'}</td>
            <td class="text-center fw-bold">${l.qty_ordered}</td>
            <td class="text-center">
                <input type="number" class="form-control form-control-sm text-center"
                    style="width:70px;border-radius:8px;display:inline-block"
                    value="${l.qty_fulfilled}" min="0" max="${l.qty_ordered}"
                    onchange="updateQty(${l.id}, this.value)">
            </td>
            <td>
                <span class="oc-badge ${statusMap[l.stock_status]||'oc-badge-muted'}">${statusLbl[l.stock_status]||l.stock_status}</span>
                <div class="text-muted" style="font-size:.7rem">${l.stock_available} tersedia</div>
            </td>
            <td>
                <button class="btn btn-light border btn-sm" style="border-radius:999px;font-size:.72rem"
                    onclick="editLine(${l.id},'${esc(l.marketplace_sku||'')}')">Edit</button>
            </td>
        </tr>`;
    }

    window.updateQty = async function (lineId, qty) {
        await api(`/api/fulfillments/${currentFulfillId}/lines/${lineId}`, {
            method: 'PATCH', body: JSON.stringify({ qty_fulfilled: parseInt(qty) }),
        }).catch(e => alert(e.message));
    };

    window.editLine = function (lineId, sku) {
        mpMapping.openForLine(lineId, currentFulfillId, sku);
    };

    window.remapAll = async function () {
        const btn = $('remapAllBtn');
        btn.disabled = true; btn.textContent = '⚡ Memproses…';
        try {
            const d = await api('/api/fulfillments/remap-all', { method: 'POST' });
            await loadFulfillments();
            btn.textContent = `✓ ${d.resolved} resolved`;
            setTimeout(() => { btn.disabled = false; btn.textContent = '⚡ Apply Mapping'; }, 2500);
        } catch (e) { btn.disabled = false; btn.textContent = '⚡ Apply Mapping'; alert(e.message); }
    };

    $('fulfillConfirmBtn').addEventListener('click', async () => {
        if (!currentFulfillId) return;
        const btn = $('fulfillConfirmBtn'), alertEl = $('fulfillModalAlert');
        btn.disabled = true; btn.textContent = 'Mengkonfirmasi…';
        try {
            const d = await api(`/api/fulfillments/${currentFulfillId}/confirm`, { method: 'POST' });
            alertEl.className = 'alert alert-success w-100 mb-0'; alertEl.textContent = d.message;
            btn.textContent = '✓ Selesai';
            loadFulfillments();
        } catch (e) {
            alertEl.className = 'alert alert-danger w-100 mb-0'; alertEl.textContent = e.message;
            btn.disabled = false; btn.textContent = '✓ Konfirmasi & Potong Stok';
        }
    });

    window.loadFulfillments = loadFulfillments;
    loadFulfillments();
    checkIncompleteBanner();
})();
</script>
@endpush
