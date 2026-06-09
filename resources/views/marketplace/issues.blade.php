@extends('layouts.app')
@section('title', 'Marketplace • Data Perlu Diperbaiki')

@include('marketplace._shared')

@push('head')
<style>
/* ── Tabs ───────────────────────────────────────────────────── */
.dpd-tabs { display:flex; gap:.35rem; flex-wrap:wrap; margin-bottom:.9rem; }
.dpd-tab {
    font-size:.75rem; font-weight:700; padding:.3rem .85rem; border-radius:999px;
    border:1px solid #e2e8f0; background:#f8fafc; color:#475569; cursor:pointer;
    transition:all .12s; display:inline-flex; align-items:center; gap:.35rem;
}
.dpd-tab.active,.dpd-tab:hover { background:#0f172a; color:#fff; border-color:#0f172a; }
.dpd-tab .cnt {
    font-size:.65rem; font-weight:900; padding:.05rem .42rem; border-radius:999px;
    background:rgba(255,255,255,.22);
}
.dpd-tab:not(.active) .cnt { background:#e2e8f0; color:#64748b; }

/* ── Issue badges ───────────────────────────────────────────── */
.ib { display:inline-flex;align-items:center;gap:.22rem;font-size:.7rem;font-weight:800;
      padding:.18rem .55rem;border-radius:999px;white-space:nowrap; }
.ib-sku-empty   { background:rgba(239,68,68,.1);   color:#b91c1c; }
.ib-not-found   { background:rgba(217,119,6,.1);   color:#b45309; }
.ib-missing-hpp { background:rgba(37,99,235,.1);   color:#1d4ed8; }
.ib-mapped      { background:rgba(22,163,74,.1);   color:#166534; }
.ib-complete    { background:rgba(22,163,74,.1);   color:#166534; }
.ib-incomplete  { background:rgba(239,68,68,.1);   color:#b91c1c; }
.ib-valid       { background:rgba(22,163,74,.1);   color:#166534; }
.ib-null        { background:rgba(148,163,184,.1); color:#64748b; }

/* ── KPI ────────────────────────────────────────────────────── */
.err-kpi  { border-left:3px solid #ef4444; }
.warn-kpi { border-left:3px solid #f59e0b; }
.ok-kpi   { border-left:3px solid #22c55e; }
.err-kpi  .oc-kpi-value { color:#b91c1c; }
.warn-kpi .oc-kpi-value { color:#b45309; }
.ok-kpi   .oc-kpi-value { color:#166534; }

/* ── Search bar ─────────────────────────────────────────────── */
.dpd-filters { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:.85rem; align-items:center; }
.dpd-filters input,.dpd-filters select { font-size:.8rem; border-radius:10px; padding:.35rem .75rem; border:1.5px solid #e2e8f0; }
.dpd-filters input:focus,.dpd-filters select:focus { border-color:#94a3b8; outline:none; }
.dpd-filters input.search-box { min-width:240px; }

/* ── Toast ──────────────────────────────────────────────────── */
#toastContainer { position:fixed; bottom:1.5rem; right:1.5rem; z-index:9999; display:flex; flex-direction:column; gap:.5rem; }
.toast-msg {
    background:#1e293b; color:#fff; padding:.6rem 1.1rem; border-radius:14px;
    font-size:.82rem; font-weight:600; box-shadow:0 4px 20px rgba(0,0,0,.25);
    animation: slideUp .2s ease; max-width:340px;
}
.toast-msg.ok  { background:#166534; }
.toast-msg.err { background:#b91c1c; }
@keyframes slideUp { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
</style>
@endpush

@section('content')
<x-gf.page eyebrow="Marketplace" title="Data Perlu Diperbaiki"
    description="Selesaikan masalah data order agar order bisa diproses dan profit dapat dihitung.">
    <x-slot:actions>
        <button id="remapBtn" class="btn btn-outline-secondary btn-sm fw-bold" style="border-radius:999px;font-size:.78rem"
            onclick="runRemap()">⟳ Re-map Semua</button>
    </x-slot:actions>

    {{-- KPI Cards --}}
    <div class="oc-kpi-grid" style="grid-template-columns:repeat(4,minmax(0,1fr))">
        <div class="oc-kpi-card err-kpi">
            <div class="oc-kpi-label">SKU Kosong</div>
            <div class="oc-kpi-value" id="kpiSkuEmpty">—</div>
            <div class="oc-kpi-note">SKU marketplace tidak ada</div>
        </div>
        <div class="oc-kpi-card warn-kpi">
            <div class="oc-kpi-label">Belum Mapping</div>
            <div class="oc-kpi-value" id="kpiNotFound">—</div>
            <div class="oc-kpi-note">SKU belum terhubung ke produk</div>
        </div>
        <div class="oc-kpi-card warn-kpi" style="border-left-color:#3b82f6">
            <div class="oc-kpi-label" style="color:#1d4ed8">HPP Kosong</div>
            <div class="oc-kpi-value" style="color:#1d4ed8" id="kpiMissingHpp">—</div>
            <div class="oc-kpi-note">HPP belum diisi</div>
        </div>
        <div class="oc-kpi-card warn-kpi">
            <div class="oc-kpi-label">Profit Belum Lengkap</div>
            <div class="oc-kpi-value" id="kpiProfitIncomplete">—</div>
            <div class="oc-kpi-note">profit belum bisa dihitung</div>
        </div>
    </div>

    <x-gf.panel>
        {{-- Tabs --}}
        <div class="dpd-tabs" id="tabsRow">
            <button class="dpd-tab active" data-tab="all" onclick="setTab('all',this)">Semua <span class="cnt" id="tcAll">—</span></button>
            <button class="dpd-tab" data-tab="sku_empty"         onclick="setTab('sku_empty',this)">SKU Kosong <span class="cnt" id="tcSku">—</span></button>
            <button class="dpd-tab" data-tab="mapping_not_found" onclick="setTab('mapping_not_found',this)">Belum Mapping <span class="cnt" id="tcMap">—</span></button>
            <button class="dpd-tab" data-tab="missing_hpp"       onclick="setTab('missing_hpp',this)">HPP Kosong <span class="cnt" id="tcHpp">—</span></button>
            <button class="dpd-tab" data-tab="profit_incomplete"  onclick="setTab('profit_incomplete',this)">Profit Incomplete <span class="cnt" id="tcProfit">—</span></button>
            <button class="dpd-tab" data-tab="selesai"            onclick="setTab('selesai',this)">✓ Selesai <span class="cnt" id="tcDone">—</span></button>
        </div>

        {{-- Filters --}}
        <div class="dpd-filters">
            <input type="text" class="search-box" id="fSearch" placeholder="🔍 Cari order / produk / SKU…" oninput="debouncedLoad()">
            <select id="fStore" onchange="loadItems(1)">
                <option value="">Semua Toko</option>
            </select>
            <input type="date" id="fDateFrom" onchange="loadItems(1)" style="max-width:145px">
            <input type="date" id="fDateTo"   onchange="loadItems(1)" style="max-width:145px">
            <button class="btn btn-light border btn-sm" style="border-radius:999px;font-size:.75rem;font-weight:700"
                onclick="clearFilters()">✕ Reset</button>
        </div>

        {{-- Table --}}
        <div id="tableBody"><div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div></div>

        {{-- Pagination --}}
        <div id="paginationRow" class="d-flex justify-content-between align-items-center mt-3 d-none" style="font-size:.78rem;color:#64748b">
            <span id="paginationInfo"></span>
            <div class="d-flex gap-1" id="paginationBtns"></div>
        </div>
    </x-gf.panel>
</x-gf.page>

<div id="toastContainer"></div>

{{-- ════════════════════════════════════════════════════════════
     Modal 1: Isi SKU
════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalIsiSku" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-black">⊘ Isi SKU Marketplace</h5>
                    <div class="text-muted" style="font-size:.8rem" id="isSkuSub">—</div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="isSkuItemId">
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">PRODUK MARKETPLACE</label>
                    <div id="isSkuItemName" style="font-size:.88rem;font-weight:600;color:#0f172a">—</div>
                    <div id="isSkuVariant" style="font-size:.78rem;color:#64748b">—</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">SKU MARKETPLACE <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="isSkuInput" placeholder="Masukkan SKU marketplace"
                        style="border-radius:12px;font-size:.88rem">
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="isSkuApplyAll" checked>
                    <label class="form-check-label" for="isSkuApplyAll" style="font-size:.8rem">
                        Terapkan ke semua order dengan produk &amp; variant yang sama
                    </label>
                </div>
                <div id="isSkuAlert" class="d-none alert" style="border-radius:12px;font-size:.82rem"></div>
            </div>
            <div class="modal-footer border-0 pt-0 gap-2">
                <button class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-dark fw-bold" style="border-radius:999px" id="isSkuBtn" onclick="submitIsiSku()">
                    Simpan SKU
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     Modal 2: Mapping Sekarang
════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalMapping" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-black">🔗 Hubungkan SKU ke Produk Internal</h5>
                    <div class="text-muted" style="font-size:.8rem" id="mapSub">—</div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="mapItemId">
                <input type="hidden" id="mapSelectedItemId">
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">SKU MARKETPLACE</label>
                    <code id="mapSku" style="font-size:.9rem;background:#f8fafc;padding:.25rem .6rem;border-radius:8px;display:inline-block">—</code>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">PRODUK MARKETPLACE</label>
                    <div id="mapItemName" style="font-size:.88rem;font-weight:600">—</div>
                    <div id="mapVariant" style="font-size:.78rem;color:#64748b">—</div>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">CARI PRODUK INTERNAL <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="mapSearch" placeholder="Ketik nama atau kode produk…"
                        style="border-radius:12px;font-size:.88rem" oninput="searchItems()">
                </div>
                <div id="mapResults" style="max-height:200px;overflow-y:auto;border:1.5px solid #e2e8f0;border-radius:12px;background:#fff">
                    <div style="padding:.75rem;font-size:.8rem;color:#94a3b8;text-align:center">Ketik untuk mencari produk internal…</div>
                </div>
                <div id="mapSelected" class="d-none mt-2" style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:12px;padding:.6rem .9rem">
                    <div style="font-size:.72rem;font-weight:800;color:#166534;margin-bottom:.2rem">DIPILIH</div>
                    <div id="mapSelectedName" style="font-size:.85rem;font-weight:700;color:#0f172a"></div>
                    <div id="mapSelectedCode" style="font-size:.72rem;color:#64748b"></div>
                    <div id="mapSelectedHpp" style="font-size:.72rem;color:#64748b"></div>
                </div>
                <div class="form-check mt-3">
                    <input type="checkbox" class="form-check-input" id="mapApplyAll" checked>
                    <label class="form-check-label" for="mapApplyAll" style="font-size:.8rem">
                        Hubungkan semua order dengan SKU marketplace yang sama
                    </label>
                </div>
                <div id="mapAlert" class="d-none alert mt-2" style="border-radius:12px;font-size:.82rem"></div>
            </div>
            <div class="modal-footer border-0 pt-0 gap-2">
                <button class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-dark fw-bold" style="border-radius:999px" id="mapBtn" onclick="submitMapping()">
                    🔗 Hubungkan SKU
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     Modal 3: Isi HPP
════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalHpp" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-black">💰 Isi HPP Item</h5>
                    <div class="text-muted" style="font-size:.8rem" id="hppSub">—</div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="hppItemId">
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">PRODUK INTERNAL</label>
                    <div id="hppInternalName" style="font-size:.88rem;font-weight:600;color:#0f172a">—</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">HPP SAAT INI</label>
                    <div id="hppCurrent" style="font-size:.9rem;font-weight:700;color:#64748b">—</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">HPP BARU (Rp) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="hppInput" placeholder="0"
                        min="1" step="1" style="border-radius:12px;font-size:.88rem">
                    <div style="font-size:.73rem;color:#94a3b8;margin-top:.25rem">Format angka, tanpa titik/koma. Contoh: 25000</div>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="hppUpdateAll" checked>
                    <label class="form-check-label" for="hppUpdateAll" style="font-size:.8rem">
                        Hitung ulang profit semua order yang terdampak HPP ini
                    </label>
                </div>
                <div id="hppAlert" class="d-none alert" style="border-radius:12px;font-size:.82rem"></div>
            </div>
            <div class="modal-footer border-0 pt-0 gap-2">
                <button class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-dark fw-bold" style="border-radius:999px" id="hppBtn" onclick="submitHpp()">
                    💾 Simpan HPP &amp; Hitung Ulang Profit
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const { api, fmt, fmtDate, esc, channelPill } = window.mpHelpers;
    const fmtRp = n => n > 0 ? 'Rp ' + Number(n).toLocaleString('id') : '—';

    let currentTab = 'all';
    let currentPage = 1;
    let searchTimer = null;

    // ── Urlparams pre-fill ────────────────────────────────────────────────
    const urlParams = new URLSearchParams(location.search);
    if (urlParams.get('store_id')) {
        document.addEventListener('DOMContentLoaded', () => {
            const sel = document.getElementById('fStore');
            // will be set after stores loaded
            window._preStoreId = urlParams.get('store_id');
        });
    }
    const urlTab = urlParams.get('tab') || urlParams.get('issue_type');
    if (urlTab) {
        const map = { sku_empty:'sku_empty', mapping_not_found:'mapping_not_found', missing_hpp:'missing_hpp' };
        if (map[urlTab]) currentTab = map[urlTab];
    }

    const $ = id => document.getElementById(id);

    // ── Init ──────────────────────────────────────────────────────────────
    async function init() {
        // Load stores for filter dropdown
        try {
            const stores = await api('/api/marketplace/stores');
            const sel = $('fStore');
            stores.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id; opt.textContent = s.name;
                sel.appendChild(opt);
            });
            if (window._preStoreId) sel.value = window._preStoreId;
        } catch {}

        // Set active tab from URL
        if (currentTab !== 'all') {
            document.querySelectorAll('.dpd-tab').forEach(t => t.classList.remove('active'));
            const activeBtn = document.querySelector(`[data-tab="${currentTab}"]`);
            if (activeBtn) activeBtn.classList.add('active');
        }

        await Promise.all([loadSummary(), loadItems(1)]);
    }

    // ── Summary / KPI ─────────────────────────────────────────────────────
    async function loadSummary() {
        try {
            const s = await api('/api/marketplace/issue-summary');
            $('kpiSkuEmpty').textContent        = (s.sku_empty         || 0).toLocaleString('id');
            $('kpiNotFound').textContent         = (s.mapping_not_found || 0).toLocaleString('id');
            $('kpiMissingHpp').textContent       = (s.missing_hpp       || 0).toLocaleString('id');
            $('kpiProfitIncomplete').textContent = (s.profit_incomplete  || 0).toLocaleString('id');

            $('tcAll').textContent    = (s.total_issues      || 0).toLocaleString('id');
            $('tcSku').textContent    = (s.sku_empty         || 0).toLocaleString('id');
            $('tcMap').textContent    = (s.mapping_not_found || 0).toLocaleString('id');
            $('tcHpp').textContent    = (s.missing_hpp       || 0).toLocaleString('id');
            $('tcProfit').textContent = (s.profit_incomplete  || 0).toLocaleString('id');
            $('tcDone').textContent   = (s.data_valid         || 0).toLocaleString('id');
        } catch {}
    }

    // ── Items table ───────────────────────────────────────────────────────
    async function loadItems(page = 1) {
        currentPage = page;
        $('tableBody').innerHTML = `<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>`;
        $('paginationRow').classList.add('d-none');

        const params = new URLSearchParams({
            tab:       currentTab,
            page:      page,
            q:         $('fSearch').value  || '',
            store_id:  $('fStore').value   || '',
            date_from: $('fDateFrom').value || '',
            date_to:   $('fDateTo').value  || '',
        });

        try {
            const res = await api('/api/marketplace/issue-items?' + params);
            renderTable(res.data || []);
            renderPagination(res.current_page, res.last_page, res.total, res.per_page);
        } catch (e) {
            $('tableBody').innerHTML = `<div class="oc-empty text-danger">Gagal memuat: ${esc(e.message)}</div>`;
        }
    }

    function renderTable(items) {
        if (!items.length) {
            $('tableBody').innerHTML = `<div class="oc-empty">
                <div style="font-size:2rem;margin-bottom:.5rem">✓</div>
                <div style="font-weight:700">Tidak ada data bermasalah</div>
                <div style="font-size:.8rem;color:#64748b;margin-top:.25rem">
                    ${currentTab === 'selesai' ? 'Belum ada item yang selesai.' : 'Semua data sudah valid untuk filter ini.'}
                </div>
            </div>`;
            return;
        }

        $('tableBody').innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead><tr>
                <th>Order</th><th>Toko</th><th>Produk Marketplace</th><th>Variant</th>
                <th>SKU Marketplace</th><th>Masalah</th><th class="text-end">Aksi Cepat</th>
            </tr></thead>
            <tbody>
            ${items.map(item => `<tr>
                <td style="white-space:nowrap">
                    <a href="/marketplace/orders" class="fw-bold text-decoration-none" style="font-size:.8rem">
                        ${esc(item.order_number || '—')}
                    </a><br>
                    <span style="font-size:.68rem;color:#94a3b8">
                        ${item.ordered_at ? fmtDate(item.ordered_at) : '—'}
                    </span>
                </td>
                <td style="font-size:.78rem;font-weight:600">${esc(item.store_name || '—')}</td>
                <td style="font-size:.8rem;max-width:180px">
                    <div class="fw-bold" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${esc(item.item_name||'')}">
                        ${esc(item.item_name || '—')}
                    </div>
                    ${item.internal_item_name
                        ? `<div style="font-size:.68rem;color:#3b82f6;margin-top:.1rem">→ ${esc(item.internal_item_name)}</div>`
                        : ''}
                </td>
                <td style="font-size:.78rem;color:#475569">${esc(item.variant_name || '—')}</td>
                <td>
                    ${item.marketplace_sku
                        ? `<code style="font-size:.75rem">${esc(item.marketplace_sku)}</code>`
                        : `<span style="font-size:.75rem;color:#b91c1c;font-weight:700">⊘ kosong</span>`}
                </td>
                <td>${renderIssueBadges(item)}</td>
                <td class="text-end">${renderActions(item)}</td>
            </tr>`).join('')}
            </tbody>
        </table></div>`;
    }

    function renderIssueBadges(item) {
        const labelMap = {
            marketplace_sku_empty: ['ib-sku-empty',   'SKU marketplace kosong'],
            mapping_not_found:     ['ib-not-found',   'SKU belum terhubung'],
            missing_hpp:           ['ib-missing-hpp', 'HPP belum diisi'],
            mapped:                ['ib-mapped',      'Terhubung'],
            complete:              ['ib-complete',     'Lengkap'],
            incomplete:            ['ib-incomplete',   'Data belum lengkap'],
            valid:                 ['ib-valid',        'Data valid'],
        };

        const badges = [];
        const issue = item.issue_reason;
        if (issue && labelMap[issue]) {
            const [cls, lbl] = labelMap[issue];
            badges.push(`<span class="ib ${cls}">${lbl}</span>`);
        } else if (item.data_status === 'valid') {
            badges.push(`<span class="ib ib-valid">✓ Data valid</span>`);
        } else if (item.data_status === 'incomplete') {
            badges.push(`<span class="ib ib-incomplete">Data belum lengkap</span>`);
        }
        return badges.join(' ') || '<span class="ib ib-null">—</span>';
    }

    function renderActions(item) {
        const btns = [];
        const issue = item.issue_reason;

        if (issue === 'marketplace_sku_empty') {
            btns.push(`<button class="btn btn-danger btn-sm fw-bold" style="border-radius:999px;font-size:.72rem"
                onclick="openIsiSku(${item.id},'${esc(item.item_name||'')}','${esc(item.variant_name||'')}')">
                ✏ Isi SKU
            </button>`);
        } else if (issue === 'mapping_not_found') {
            btns.push(`<button class="btn btn-warning btn-sm fw-bold" style="border-radius:999px;font-size:.72rem"
                onclick="openMapping(${item.id},'${esc(item.marketplace_sku||'')}','${esc(item.item_name||'')}','${esc(item.variant_name||'')}')">
                🔗 Mapping Sekarang
            </button>`);
        } else if (issue === 'missing_hpp') {
            btns.push(`<button class="btn btn-primary btn-sm fw-bold" style="border-radius:999px;font-size:.72rem"
                onclick="openIsiHpp(${item.id},'${esc(item.internal_item_name||'—')}',${item.hpp_current||0})">
                💰 Isi HPP
            </button>`);
        } else if (item.data_status !== 'valid' && item.mapping_status === 'mapped' && item.cost_status === 'complete') {
            btns.push(`<button class="btn btn-outline-secondary btn-sm fw-bold" style="border-radius:999px;font-size:.72rem"
                onclick="doRecalcProfit(${item.id},this)">
                🔄 Hitung Profit
            </button>`);
        }

        if (item.data_status === 'valid') {
            btns.push(`<span style="font-size:.72rem;color:#166534;font-weight:700">✓ Selesai</span>`);
        }

        return btns.join(' ') || '<span style="font-size:.72rem;color:#94a3b8">—</span>';
    }

    function renderPagination(current, last, total, perPage) {
        if (last <= 1 && total === 0) return;
        const row = $('paginationRow');
        row.classList.remove('d-none');
        $('paginationInfo').textContent = `Menampilkan ${Math.min((current-1)*perPage+1, total)}–${Math.min(current*perPage, total)} dari ${total} item`;

        const btns = [];
        if (current > 1) btns.push(`<button class="btn btn-light border btn-sm" style="border-radius:8px;font-size:.75rem" onclick="loadItems(${current-1})">‹ Sebelumnya</button>`);
        // Show up to 5 page buttons
        let start = Math.max(1, current-2), end = Math.min(last, start+4);
        start = Math.max(1, end-4);
        for (let p = start; p <= end; p++) {
            btns.push(`<button class="btn btn-${p===current?'dark':'light border'} btn-sm" style="border-radius:8px;font-size:.75rem;min-width:34px" onclick="loadItems(${p})">${p}</button>`);
        }
        if (current < last) btns.push(`<button class="btn btn-light border btn-sm" style="border-radius:8px;font-size:.75rem" onclick="loadItems(${current+1})">Berikutnya ›</button>`);
        $('paginationBtns').innerHTML = btns.join('');
    }

    // ── Tab + filter handlers ─────────────────────────────────────────────
    window.setTab = function (tab, btn) {
        currentTab = tab;
        document.querySelectorAll('.dpd-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        loadItems(1);
    };

    window.clearFilters = function () {
        $('fSearch').value = ''; $('fStore').value = '';
        $('fDateFrom').value = ''; $('fDateTo').value = '';
        loadItems(1);
    };

    window.debouncedLoad = function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadItems(1), 400);
    };

    // ── Modal: Isi SKU ────────────────────────────────────────────────────
    window.openIsiSku = function (itemId, itemName, variant) {
        $('isSkuItemId').value = itemId;
        $('isSkuSub').textContent = 'Item ID #' + itemId;
        $('isSkuItemName').textContent = itemName || '—';
        $('isSkuVariant').textContent  = variant  || '—';
        $('isSkuInput').value = '';
        $('isSkuAlert').className = 'd-none alert';
        $('isSkuBtn').disabled = false; $('isSkuBtn').textContent = 'Simpan SKU';
        new bootstrap.Modal($('modalIsiSku')).show();
    };

    window.submitIsiSku = async function () {
        const itemId = $('isSkuItemId').value;
        const sku    = $('isSkuInput').value.trim();
        if (!sku) { showModalAlert('isSkuAlert', 'SKU tidak boleh kosong.', 'danger'); return; }

        const btn = $('isSkuBtn');
        btn.disabled = true; btn.textContent = 'Menyimpan…';

        try {
            const res = await api('/api/marketplace/order-items/' + itemId + '/fill-sku', {
                method: 'PATCH',
                body: JSON.stringify({ sku, apply_to_similar: $('isSkuApplyAll').checked }),
            });
            bootstrap.Modal.getInstance($('modalIsiSku')).hide();
            toast(res.message, 'ok');
            loadItems(currentPage); loadSummary();
        } catch (e) {
            showModalAlert('isSkuAlert', e.message, 'danger');
            btn.disabled = false; btn.textContent = 'Simpan SKU';
        }
    };

    // ── Modal: Mapping Sekarang ───────────────────────────────────────────
    let mapSearchTimer = null;

    window.openMapping = function (itemId, sku, itemName, variant) {
        $('mapItemId').value = itemId;
        $('mapSelectedItemId').value = '';
        $('mapSub').textContent = 'SKU: ' + (sku || '—');
        $('mapSku').textContent  = sku      || '—';
        $('mapItemName').textContent = itemName || '—';
        $('mapVariant').textContent  = variant  || '—';
        $('mapSearch').value = itemName || '';
        $('mapResults').innerHTML = `<div style="padding:.75rem;font-size:.8rem;color:#94a3b8;text-align:center">Ketik untuk mencari produk internal…</div>`;
        $('mapSelected').classList.add('d-none');
        $('mapAlert').className = 'd-none alert';
        $('mapBtn').disabled = false; $('mapBtn').textContent = '🔗 Hubungkan SKU';
        new bootstrap.Modal($('modalMapping')).show();
        // Auto-search with item name
        if (itemName) setTimeout(() => searchItems(), 100);
    };

    window.searchItems = function () {
        clearTimeout(mapSearchTimer);
        mapSearchTimer = setTimeout(async () => {
            const q = $('mapSearch').value.trim();
            if (!q) {
                $('mapResults').innerHTML = `<div style="padding:.75rem;font-size:.8rem;color:#94a3b8;text-align:center">Ketik untuk mencari…</div>`;
                return;
            }
            $('mapResults').innerHTML = `<div style="padding:.75rem;font-size:.8rem;color:#94a3b8;text-align:center">Mencari…</div>`;
            try {
                const items = await api('/api/marketplace/items/search?q=' + encodeURIComponent(q));
                if (!items.length) {
                    $('mapResults').innerHTML = `<div style="padding:.75rem;font-size:.8rem;color:#94a3b8;text-align:center">Produk tidak ditemukan.</div>`;
                    return;
                }
                $('mapResults').innerHTML = items.map(i =>
                    `<div class="map-result-item" style="padding:.6rem .9rem;cursor:pointer;border-bottom:1px solid #f1f5f9;font-size:.82rem"
                        onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''"
                        onclick="selectInternalItem(${i.id},'${esc(i.name)}','${esc(i.code||'')}',${i.hpp||0})">
                        <div style="font-weight:700;color:#0f172a">${esc(i.name)}</div>
                        <div style="color:#64748b">${esc(i.code||'')} ${i.hpp > 0 ? '· HPP: ' + fmtRp(i.hpp) : '· <span style=color:#b91c1c>HPP belum diisi</span>'}</div>
                    </div>`
                ).join('');
            } catch (e) {
                $('mapResults').innerHTML = `<div style="padding:.75rem;font-size:.8rem;color:#b91c1c">Gagal mencari: ${e.message}</div>`;
            }
        }, 350);
    };

    window.selectInternalItem = function (id, name, code, hpp) {
        $('mapSelectedItemId').value = id;
        $('mapSelectedName').textContent = name;
        $('mapSelectedCode').textContent = code ? 'Kode: ' + code : '';
        $('mapSelectedHpp').innerHTML  = hpp > 0
            ? 'HPP: ' + fmtRp(hpp)
            : '<span style="color:#b91c1c">HPP belum diisi — perlu diisi setelah mapping</span>';
        $('mapSelected').classList.remove('d-none');
        $('mapResults').innerHTML = `<div style="padding:.75rem;font-size:.8rem;color:#94a3b8;text-align:center">Klik produk lain untuk mengganti pilihan.</div>`;
    };

    window.submitMapping = async function () {
        const itemId         = $('mapItemId').value;
        const internalItemId = $('mapSelectedItemId').value;
        if (!internalItemId) { showModalAlert('mapAlert', 'Pilih produk internal terlebih dahulu.', 'danger'); return; }

        const btn = $('mapBtn');
        btn.disabled = true; btn.textContent = 'Menyimpan…';

        try {
            const res = await api('/api/marketplace/order-items/' + itemId + '/map-sku', {
                method: 'PATCH',
                body: JSON.stringify({ internal_item_id: parseInt(internalItemId), apply_to_all: $('mapApplyAll').checked }),
            });
            bootstrap.Modal.getInstance($('modalMapping')).hide();
            toast(res.message, 'ok');
            loadItems(currentPage); loadSummary();
        } catch (e) {
            showModalAlert('mapAlert', e.message, 'danger');
            btn.disabled = false; btn.textContent = '🔗 Hubungkan SKU';
        }
    };

    // ── Modal: Isi HPP ────────────────────────────────────────────────────
    window.openIsiHpp = function (itemId, internalName, hppCurrent) {
        $('hppItemId').value = itemId;
        $('hppSub').textContent = 'Item ID #' + itemId;
        $('hppInternalName').textContent = internalName || '—';
        $('hppCurrent').textContent = hppCurrent > 0 ? fmtRp(hppCurrent) : 'Belum diisi';
        $('hppInput').value = hppCurrent > 0 ? Math.round(hppCurrent) : '';
        $('hppAlert').className = 'd-none alert';
        $('hppBtn').disabled = false; $('hppBtn').textContent = '💾 Simpan HPP & Hitung Ulang Profit';
        new bootstrap.Modal($('modalHpp')).show();
    };

    window.submitHpp = async function () {
        const itemId = $('hppItemId').value;
        const hpp    = parseFloat($('hppInput').value);
        if (!hpp || hpp <= 0) { showModalAlert('hppAlert', 'HPP harus lebih dari 0.', 'danger'); return; }

        const btn = $('hppBtn');
        btn.disabled = true; btn.textContent = 'Menyimpan…';

        try {
            const res = await api('/api/marketplace/order-items/' + itemId + '/fill-hpp', {
                method: 'PATCH',
                body: JSON.stringify({ hpp, update_affected: $('hppUpdateAll').checked }),
            });
            bootstrap.Modal.getInstance($('modalHpp')).hide();
            toast(res.message, 'ok');
            loadItems(currentPage); loadSummary();
        } catch (e) {
            showModalAlert('hppAlert', e.message, 'danger');
            btn.disabled = false; btn.textContent = '💾 Simpan HPP & Hitung Ulang Profit';
        }
    };

    // ── Recalc profit ─────────────────────────────────────────────────────
    window.doRecalcProfit = async function (itemId, btn) {
        btn.disabled = true; btn.textContent = '⏳…';
        try {
            const res = await api('/api/marketplace/order-items/' + itemId + '/recalc-profit', { method: 'POST' });
            toast(res.message, 'ok');
            loadItems(currentPage); loadSummary();
        } catch (e) {
            toast(e.message, 'err');
            btn.disabled = false; btn.textContent = '🔄 Hitung Profit';
        }
    };

    // ── Re-map all ────────────────────────────────────────────────────────
    window.runRemap = async function () {
        const btn = $('remapBtn');
        btn.disabled = true; btn.textContent = '⏳ Re-mapping…';
        try {
            const res = await api('/api/marketplace/remap-items', { method: 'POST' });
            toast(`Re-map selesai — ${res.updated} item diperbarui.`, 'ok');
            loadItems(1); loadSummary();
        } catch (e) {
            toast(e.message, 'err');
        } finally {
            btn.disabled = false; btn.textContent = '⟳ Re-map Semua';
        }
    };

    // ── Toast ─────────────────────────────────────────────────────────────
    function toast(msg, type = '') {
        const el = document.createElement('div');
        el.className = 'toast-msg ' + type;
        el.textContent = msg;
        $('toastContainer').appendChild(el);
        setTimeout(() => el.remove(), 3500);
    }

    function showModalAlert(id, msg, type) {
        const el = $(id);
        el.className = `alert alert-${type}`;
        el.textContent = msg;
    }

    window.loadItems = loadItems;
    init();
})();
</script>
@endpush
