@extends('layouts.app')
@section('title', 'Marketplace • Produk')

@push('head')
<style>
    :root{
        --prd-accent:#334155;
        --prd-accent-2:#1f2937;
        --prd-border:rgba(148,163,184,.18);
        --prd-muted:#64748b;
    }
    .page-wrap{ max-width:1180px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }

    /* ── Topbar (selaras shipment) ── */
    .prd-topbar{
        position:sticky; top:0; z-index:300;
        display:flex; justify-content:space-between; align-items:center; gap:.6rem; flex-wrap:wrap;
        padding:.45rem .75rem; margin-inline:-.75rem; margin-bottom:.65rem;
        background:var(--card,#fff); border-bottom:1px solid var(--prd-border);
    }
    body[data-theme="dark"] .prd-topbar{ background:var(--card,#0f172a); }
    .title{ font-weight:750; font-size:1rem; margin:0; }
    .sub{ color:var(--prd-muted); font-size:.78rem; }
    body[data-theme="dark"] .sub{ color:#9ca3af; }

    .kpis{ display:flex; flex-wrap:wrap; gap:.32rem; margin-top:.35rem; }
    .kpi{
        display:inline-flex; align-items:baseline; gap:.45rem; cursor:pointer;
        border-radius:7px; padding:.2rem .48rem; border:1px solid rgba(148,163,184,.28);
        background:transparent; font-size:.72rem; user-select:none;
    }
    .kpi:hover{ background:rgba(148,163,184,.08); }
    .kpi.active{ border-color:var(--prd-accent); background:rgba(51,65,85,.06); }
    body[data-theme="dark"] .kpi{ background:rgba(15,23,42,.96); border-color:rgba(51,65,85,.85); }
    .kpi .lbl{ font-size:.66rem; color:#94a3b8; }
    .kpi .val{ font-weight:650; color:var(--prd-accent); }
    body[data-theme="dark"] .kpi .val{ color:#cbd5e1; }

    .controls{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
    .btn-pill{ border-radius:7px; padding-inline:.78rem; box-shadow:none!important; font-weight:600; font-size:.8rem; }
    .btn-prd-primary{ background:var(--prd-accent)!important; border-color:var(--prd-accent)!important; color:#fff!important; }
    .btn-prd-primary:hover{ background:var(--prd-accent-2)!important; border-color:var(--prd-accent-2)!important; }
    .btn-prd-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-prd-outline:hover{ background:rgba(148,163,184,.08)!important; }

    /* ── Filter bar ── */
    .filter-bar{
        display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; margin-bottom:.65rem;
    }
    .filter-select{ border-radius:7px; padding-left:.6rem; padding-right:1.8rem; font-size:.8rem; max-width:170px; }
    .filter-search{ border-radius:7px; font-size:.8rem; max-width:250px; }
    .filter-count{ font-size:.72rem; color:var(--prd-muted); margin-left:auto; }
    .filter-reset{ font-size:.7rem; color:#b91c1c; cursor:pointer; text-decoration:underline; display:none; }

    /* ── Card + table (selaras shipment) ── */
    .card-main{
        background:var(--card,#fff); border-radius:8px; border:1px solid var(--prd-border); overflow:hidden;
    }
    body[data-theme="dark"] .card-main{ border-color:rgba(51,65,85,.85); }
    .table-list{ width:100%; margin-bottom:0; border-collapse:collapse; }
    .table-list thead th{
        border-bottom:1px solid var(--prd-border); font-size:.68rem; color:#64748b;
        background:var(--card,#fff); padding:.52rem .62rem; white-space:nowrap; text-align:left;
    }
    body[data-theme="dark"] .table-list thead th{ background:rgba(15,23,42,.98); color:#9ca3af; }
    .table-list tbody td{
        vertical-align:middle; border-top:1px solid rgba(148,163,184,.16);
        padding:.52rem .62rem; font-size:.78rem;
    }
    body[data-theme="dark"] .table-list tbody td{ border-top-color:rgba(51,65,85,.85); }

    .prd-img{ width:42px; height:42px; border-radius:7px; object-fit:cover; background:#f1f5f9; }
    .prd-name{ font-weight:700; color:inherit; max-width:300px; line-height:1.3; }
    .prd-sku{ font-size:.67rem; color:#94a3b8; }
    .muted{ font-size:.74rem; color:#6b7280; }

    /* Badge status ala shipment (dengan titik) */
    .badge-status{
        border-radius:7px; padding:.16rem .48rem; font-size:.68rem;
        border:1px solid transparent; display:inline-flex; align-items:center; gap:.35rem; white-space:nowrap;
    }
    .badge-status::before{ content:''; width:7px; height:7px; border-radius:999px; display:inline-block; }
    .st-normal{ background:rgba(34,197,94,.10); color:#166534; border-color:rgba(34,197,94,.30); }
    .st-normal::before{ background:rgba(34,197,94,.95); }
    .st-unlist{ background:rgba(148,163,184,.10); color:#475569; border-color:rgba(148,163,184,.30); }
    .st-unlist::before{ background:rgba(100,116,139,.95); }
    .st-banned{ background:rgba(239,68,68,.10); color:#991b1b; border-color:rgba(239,68,68,.30); }
    .st-banned::before{ background:rgba(239,68,68,.95); }
    .st-map-ok{ background:rgba(34,197,94,.10); color:#166534; border-color:rgba(34,197,94,.30); }
    .st-map-ok::before{ background:rgba(34,197,94,.95); }
    .st-map-no{ background:rgba(239,68,68,.10); color:#991b1b; border-color:rgba(239,68,68,.30); }
    .st-map-no::before{ background:rgba(239,68,68,.95); }
    .st-map-warn{ background:rgba(245,158,11,.10); color:#92400e; border-color:rgba(245,158,11,.30); }
    .st-map-warn::before{ background:rgba(245,158,11,.95); }

    .stock-low{ color:#b91c1c; font-weight:800; }
    .stock-zero{ color:#b91c1c; font-weight:800; background:rgba(239,68,68,.08); border-radius:6px; padding:1px 7px; }

    .model-row td{ background:rgba(148,163,184,.05); font-size:.73rem; }
    body[data-theme="dark"] .model-row td{ background:rgba(30,41,59,.5); }
    .inp-mini{ width:88px; font-size:.72rem; padding:2px 6px; border:1px solid rgba(148,163,184,.35); border-radius:6px; background:transparent; color:inherit; }
    .btn-mini{ font-size:.65rem; padding:2px 8px; border-radius:6px; }
    .prd-caret{ cursor:pointer; user-select:none; color:#64748b; font-size:.8rem; }
    .empty{ padding:2.2rem 1.25rem; text-align:center; color:#64748b; }

    @media (max-width:768px){
        .page-wrap{ padding:.5rem .5rem 4rem; }
        .prd-topbar{ margin-inline:-.5rem; padding:.5rem .65rem; }
        .sub{ display:none; }
        .controls{ width:100%; }
        .controls .btn-pill{ flex:1; }
        .filter-select, .filter-search{ max-width:none; flex:1 1 46%; }
        .table-wrap{ overflow-x:auto; }
        .prd-name{ max-width:200px; }
    }
</style>
@endpush

@section('content')
<div class="page-wrap">

    {{-- Topbar --}}
    <div class="prd-topbar">
        <div>
            <h1 class="title">🏷 Produk Marketplace</h1>
            <div class="sub">Kelola stok, harga, tampil/sembunyi & mapping SKU — tersinkron langsung ke Shopee <span id="rtLabel" class="muted"></span></div>
            <div class="kpis" id="kpiRow"></div>
        </div>
        <div class="controls">
            <a class="btn btn-pill btn-prd-outline" href="{{ route('marketplace.boost') }}" title="Atur jadwal & rotasi Naikkan Produk">🚀 Boost</a>
            <button class="btn btn-pill btn-prd-outline" id="btnAutoMap" onclick="autoMap()">⚡ Auto-map</button>
            <button class="btn btn-pill btn-prd-primary" id="btnSync" onclick="syncProducts()">⟳ Sync Shopee</button>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="filter-bar">
        <input type="text" class="form-control form-control-sm filter-search" placeholder="🔍 Cari nama / SKU / item id…" id="fSearch">
        <select class="form-select form-select-sm filter-select" id="fStore"><option value="">Semua Toko</option></select>
        <select class="form-select form-select-sm filter-select" id="fStatus">
            <option value="">Semua Status</option>
            <option value="NORMAL">● Tampil</option>
            <option value="UNLIST">● Disembunyikan</option>
            <option value="BANNED">● Banned</option>
        </select>
        <select class="form-select form-select-sm filter-select" id="fMapping">
            <option value="">Semua Mapping</option>
            <option value="unmapped">❌ Belum di-mapping</option>
            <option value="mapped">✓ Sudah di-mapping</option>
            <option value="nosku">⚠ SKU kosong</option>
        </select>
        <select class="form-select form-select-sm filter-select" id="fStock">
            <option value="">Semua Stok</option>
            <option value="zero">Habis (0)</option>
            <option value="low">Menipis (≤5)</option>
            <option value="ok">Aman (>5)</option>
        </select>
        <select class="form-select form-select-sm filter-select" id="fSort">
            <option value="synced">Terbaru sync</option>
            <option value="sales">Terlaris</option>
            <option value="stock_asc">Stok terendah</option>
            <option value="stock_desc">Stok tertinggi</option>
            <option value="price_asc">Harga terendah</option>
            <option value="price_desc">Harga tertinggi</option>
            <option value="name">Nama A–Z</option>
        </select>
        <span class="filter-reset" id="fReset" onclick="resetFilters()">✕ reset filter</span>
        <span class="filter-count" id="prdCount"></span>
    </div>

    {{-- Tabel --}}
    <div class="card-main table-wrap">
        <table class="table-list">
            <thead><tr>
                <th style="width:26px"></th><th style="width:50px"></th><th>Produk</th><th>Toko</th>
                <th>Status</th><th>Harga</th><th>Stok</th><th>Terjual</th><th>Mapping</th><th style="width:200px">Aksi</th>
            </tr></thead>
            <tbody id="prdBody"><tr><td colspan="10" class="empty">Memuat…</td></tr></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const API = '/api/marketplace/products';
    let products = [];   // semua data dari server
    let kpiFilter = '';  // filter cepat via klik KPI
    const $ = id => document.getElementById(id);
    const esc = s => (s ?? '').toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const rp = n => n == null ? '—' : 'Rp' + Number(n).toLocaleString('id-ID');

    async function api(url, opts = {}) {
        const res = await fetch(url, { headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }, ...opts });
        if (!res.ok) throw new Error((await res.json().catch(() => ({})))?.message || ('HTTP ' + res.status));
        return res.json();
    }

    // ── Data ────────────────────────────────────────────────────────────────
    window.loadProducts = async function () {
        try {
            products = await api(API);
            buildStoreOptions();
            render();
        } catch (e) {
            $('prdBody').innerHTML = `<tr><td colspan="10" class="empty text-danger">${esc(e.message)}</td></tr>`;
        }
    };

    window.syncProducts = async function () {
        $('btnSync').disabled = true; $('btnSync').textContent = '⏳ Sync…';
        try {
            const res = await api(`${API}/sync`, { method: 'POST', body: '{}' });
            toast(res.message, res.errors?.length ? 'warning' : 'success');
            if (res.errors?.length) console.warn('Sync errors:', res.errors);
            loadProducts();
        } catch (e) { alert('Sync gagal: ' + e.message); }
        finally { $('btnSync').disabled = false; $('btnSync').textContent = '⟳ Sync Shopee'; }
    };

    function buildStoreOptions() {
        const sel = $('fStore'), cur = sel.value;
        const stores = [...new Map(products.filter(p => p.store).map(p => [p.store.id, p.store.name])).entries()];
        sel.innerHTML = '<option value="">Semua Toko</option>' + stores.map(([id, name]) => `<option value="${id}">${esc(name)}</option>`).join('');
        sel.value = cur;
    }

    // ── Filter fungsional (instan, client-side) ─────────────────────────────
    function modelMapState(m) {
        if (!m.model_sku) return 'nosku';
        return m.mapping ? 'mapped' : 'unmapped';
    }
    function productMapState(p) {
        const models = p.models || [];
        if (!models.length) return 'nosku';
        const states = models.map(modelMapState);
        if (states.every(s => s === 'nosku')) return 'nosku';
        return states.some(s => s === 'unmapped') ? 'unmapped' : 'mapped';
    }

    function filtered() {
        const q = $('fSearch').value.trim().toLowerCase();
        const store = $('fStore').value;
        const status = $('fStatus').value;
        const mapping = $('fMapping').value || (kpiFilter === 'unmapped' ? 'unmapped' : '');
        const stock = $('fStock').value || (kpiFilter === 'zero' ? 'zero' : '');
        const statusKpi = ['NORMAL','UNLIST'].includes(kpiFilter) ? kpiFilter : '';

        let rows = products.filter(p => {
            if (store && String(p.store?.id) !== store) return false;
            if ((status || statusKpi) && p.item_status !== (status || statusKpi)) return false;
            if (mapping && productMapState(p) !== mapping) return false;
            if (stock === 'zero' && (p.stock_total ?? 0) !== 0) return false;
            if (stock === 'low'  && ((p.stock_total ?? 0) === 0 || p.stock_total > 5)) return false;
            if (stock === 'ok'   && (p.stock_total ?? 0) <= 5) return false;
            if (q) {
                const hay = [p.item_name, p.item_sku, p.item_id, ...(p.models || []).map(m => m.model_sku), ...(p.models || []).map(m => m.model_name)]
                    .filter(Boolean).join(' ').toLowerCase();
                if (!hay.includes(q)) return false;
            }
            return true;
        });

        const sort = $('fSort').value;
        const num = v => v == null ? -1 : Number(v);
        rows.sort((a, b) => {
            switch (sort) {
                case 'sales':      return num(b.sales) - num(a.sales);
                case 'stock_asc':  return num(a.stock_total) - num(b.stock_total);
                case 'stock_desc': return num(b.stock_total) - num(a.stock_total);
                case 'price_asc':  return num(a.price_min) - num(b.price_min);
                case 'price_desc': return num(b.price_min) - num(a.price_min);
                case 'name':       return (a.item_name || '').localeCompare(b.item_name || '');
                default:           return new Date(b.synced_at || 0) - new Date(a.synced_at || 0);
            }
        });
        return rows;
    }

    window.resetFilters = function () {
        ['fSearch','fStore','fStatus','fMapping','fStock'].forEach(id => $(id).value = '');
        $('fSort').value = 'synced';
        kpiFilter = '';
        render();
    };

    function anyFilterActive() {
        return kpiFilter || $('fSearch').value || $('fStore').value || $('fStatus').value || $('fMapping').value || $('fStock').value;
    }

    // ── KPI (klik untuk quick-filter) ───────────────────────────────────────
    function renderKpis() {
        const total    = products.length;
        const normal   = products.filter(p => p.item_status === 'NORMAL').length;
        const unlist   = products.filter(p => p.item_status === 'UNLIST').length;
        const habis    = products.filter(p => (p.stock_total ?? 0) === 0 && p.item_status === 'NORMAL').length;
        const unmapped = products.filter(p => productMapState(p) === 'unmapped').length;

        const kpi = (key, lbl, val) =>
            `<span class="kpi ${kpiFilter === key ? 'active' : ''}" onclick="kpiClick('${key}')"><span class="lbl">${lbl}</span><span class="val">${val}</span></span>`;

        $('kpiRow').innerHTML =
            kpi('', 'Total', total) +
            kpi('NORMAL', 'Tampil', normal) +
            kpi('UNLIST', 'Sembunyi', unlist) +
            kpi('zero', 'Stok Habis', habis) +
            kpi('unmapped', 'Belum Map', unmapped);
    }

    window.kpiClick = function (key) {
        kpiFilter = (kpiFilter === key) ? '' : key;
        // KPI mengambil alih dropdown terkait supaya tidak dobel
        if (['NORMAL','UNLIST'].includes(key)) $('fStatus').value = '';
        if (key === 'zero') $('fStock').value = '';
        if (key === 'unmapped') $('fMapping').value = '';
        render();
    };

    // ── Render tabel ────────────────────────────────────────────────────────
    function statusBadge(st) {
        const cls = { NORMAL: 'st-normal', UNLIST: 'st-unlist', BANNED: 'st-banned' }[st] || 'st-unlist';
        const lbl = { NORMAL: 'Tampil', UNLIST: 'Sembunyi', BANNED: 'Banned' }[st] || (st || '—');
        return `<span class="badge-status ${cls}">${lbl}</span>`;
    }

    function stockCell(n) {
        if ((n ?? 0) === 0) return '<span class="stock-zero">0</span>';
        if (n <= 5) return `<span class="stock-low">${n}</span>`;
        return `<b>${n}</b>`;
    }

    function mappingBadge(m) {
        if (!m.model_sku) return '<span class="badge-status st-map-warn">SKU kosong</span>';
        if (m.mapping) {
            return `<span class="badge-status st-map-ok" title="${esc(m.mapping.item_name || '')}">${esc(m.mapping.item_code || m.mapping.item_id)}</span>
                <button class="btn btn-prd-outline btn-mini" onclick="openMapModal('${esc(m.model_sku)}')" title="Ganti mapping">✎</button>`;
        }
        return `<button class="btn btn-outline-danger btn-mini" onclick="openMapModal('${esc(m.model_sku)}')">❌ Map</button>`;
    }

    function mappingSummary(models) {
        const withSku = models.filter(m => m.model_sku);
        if (!withSku.length) return '<span class="badge-status st-map-warn">SKU kosong</span>';
        const mapped = withSku.filter(m => m.mapping).length;
        const cls = mapped === withSku.length ? 'st-map-ok' : 'st-map-no';
        return `<span class="badge-status ${cls}">${mapped}/${withSku.length} map</span>`;
    }

    function inlineEditors(pid, m) {
        return `<div class="d-flex gap-1 flex-wrap align-items-center">
            <input class="inp-mini" type="number" min="0" value="${m.stock}" id="stk-${pid}-${m.model_id}" title="Stok">
            <button class="btn btn-prd-outline btn-mini" onclick="saveStock(${pid}, '${m.model_id}')">Stok</button>
            <input class="inp-mini" type="number" min="100" value="${m.price ?? ''}" id="prc-${pid}-${m.model_id}" title="Harga">
            <button class="btn btn-prd-outline btn-mini" onclick="savePrice(${pid}, '${m.model_id}')">Harga</button>
        </div>`;
    }

    function render() {
        renderKpis();
        const rows = filtered();
        $('prdCount').textContent = `${rows.length} dari ${products.length} produk`;
        $('fReset').style.display = anyFilterActive() ? '' : 'none';

        if (!rows.length) {
            $('prdBody').innerHTML = `<tr><td colspan="10" class="empty">${products.length ? 'Tidak ada produk yang cocok dengan filter. <a href="javascript:resetFilters()">Reset filter</a>' : 'Belum ada produk. Klik "⟳ Sync Shopee".'}</td></tr>`;
            return;
        }

        $('prdBody').innerHTML = rows.map(p => {
            const st = p.item_status || '—';
            const models = p.models || [];
            const multiModel = p.has_model && models.length > 0;
            const price = p.price_min != null
                ? (p.price_min === p.price_max ? rp(p.price_min) : `${rp(p.price_min)} – ${rp(p.price_max)}`)
                : '—';

            const mainRow = `<tr data-pid="${p.id}">
                <td>${multiModel ? `<span class="prd-caret" onclick="toggleModels(${p.id}, this)">▶</span>` : ''}</td>
                <td>${p.image_url ? `<img class="prd-img" src="${esc(p.image_url)}" loading="lazy">` : '<div class="prd-img"></div>'}</td>
                <td><div class="prd-name">${esc(p.item_name || '—')}</div>
                    <div class="prd-sku">SKU: ${esc(p.item_sku || '—')} · ${esc(p.item_id)}${multiModel ? ` · ${models.length} varian` : ''}</div></td>
                <td class="muted">${esc(p.store?.name || '')}</td>
                <td>${statusBadge(st)}</td>
                <td>${price}</td>
                <td>${stockCell(p.stock_total)}</td>
                <td class="muted">${p.sales ?? '—'}</td>
                <td>${multiModel ? mappingSummary(models) : (models.length ? mappingBadge(models[0]) : '—')}</td>
                <td>
                    <button class="btn btn-prd-outline btn-mini mb-1" onclick="showHistory(${p.id})" title="Riwayat harian: stok, harga, terjual">📈 Riwayat</button>
                    ${st === 'NORMAL'
                        ? `<button class="btn btn-prd-outline btn-mini mb-1" onclick="boostNow(${p.id}, this)" title="Naikkan produk ke urutan teratas (4 jam)">🚀 Naikkan</button>`
                        : ''}
                    ${!multiModel && models.length ? inlineEditors(p.id, models[0]) : ''}
                    ${st === 'NORMAL'
                        ? `<button class="btn btn-prd-outline btn-mini mt-1" onclick="setUnlist(${p.id}, true)">🙈 Sembunyikan</button>`
                        : (st === 'UNLIST' ? `<button class="btn btn-outline-success btn-mini mt-1" onclick="setUnlist(${p.id}, false)">👁 Tampilkan</button>` : '')}
                </td>
            </tr>`;

            const modelRows = multiModel ? models.map(m => `
                <tr class="model-row mr-${p.id}" style="display:none">
                    <td></td><td></td>
                    <td style="padding-left:22px">↳ ${esc(m.model_name || 'Varian')} <span class="prd-sku">SKU: ${esc(m.model_sku || '—')}</span></td>
                    <td></td><td></td>
                    <td>${rp(m.price)}</td>
                    <td>${stockCell(m.stock)}</td>
                    <td></td>
                    <td>${mappingBadge(m)}</td>
                    <td>${inlineEditors(p.id, m)}</td>
                </tr>`).join('') : '';

            return mainRow + modelRows;
        }).join('');
    }

    window.toggleModels = function (pid, caret) {
        const rows = document.querySelectorAll('.mr-' + pid);
        const show = rows.length && rows[0].style.display === 'none';
        rows.forEach(r => r.style.display = show ? '' : 'none');
        caret.textContent = show ? '▼' : '▶';
    };

    // ── Aksi stok / harga / unlist ──────────────────────────────────────────
    window.saveStock = async function (pid, modelId) {
        const val = parseInt($(`stk-${pid}-${modelId}`).value);
        if (isNaN(val) || val < 0) return alert('Stok tidak valid');
        try {
            await api(`${API}/${pid}/stock`, { method: 'POST', body: JSON.stringify({ stock_list: [{ model_id: modelId, stock: val }] }) });
            toast('Stok tersimpan ke Shopee ✔');
            loadProducts();
        } catch (e) { alert('Gagal: ' + e.message); }
    };

    window.savePrice = async function (pid, modelId) {
        const val = parseFloat($(`prc-${pid}-${modelId}`).value);
        if (isNaN(val) || val < 100) return alert('Harga tidak valid (min 100)');
        try {
            await api(`${API}/${pid}/price`, { method: 'POST', body: JSON.stringify({ price_list: [{ model_id: modelId, original_price: val }] }) });
            toast('Harga tersimpan ke Shopee ✔');
            loadProducts();
        } catch (e) { alert('Gagal: ' + e.message); }
    };

    window.setUnlist = async function (pid, unlist) {
        try {
            await api(`${API}/${pid}/unlist`, { method: 'POST', body: JSON.stringify({ unlist }) });
            toast(unlist ? 'Produk disembunyikan' : 'Produk ditampilkan');
            loadProducts();
        } catch (e) { alert('Gagal: ' + e.message); }
    };

    // Naikkan (boost) satu produk sekarang — maks 5 aktif / 4 jam (dibatasi Shopee)
    window.boostNow = async function (pid, btn) {
        const p = products.find(x => x.id === pid);
        if (!p) return;
        const orig = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '⏳'; }
        try {
            const res = await api('/api/marketplace/boost/now', {
                method: 'POST',
                body: JSON.stringify({ store_id: p.store_id, product_ids: [pid] }),
            });
            toast(res.message || 'Produk dinaikkan', res.success ? 'success' : 'warning');
        } catch (e) {
            toast('Gagal boost: ' + e.message, 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = orig; }
        }
    };

    function toast(title, icon = 'success') {
        if (window.Swal) Swal.fire({ toast:true, position:'top-end', icon, title, showConfirmButton:false, timer:2600 });
    }

    // ── Mapping SKU → item internal ─────────────────────────────────────────
    window.autoMap = async function () {
        $('btnAutoMap').disabled = true;
        try {
            const res = await api(`${API}/auto-map`, { method: 'POST', body: '{}' });
            toast(res.message, res.created > 0 ? 'success' : 'info');
            loadProducts();
        } catch (e) { alert('Gagal: ' + e.message); }
        finally { $('btnAutoMap').disabled = false; }
    };

    window.openMapModal = async function (sku) {
        const { value: itemId } = await Swal.fire({
            title: `Mapping SKU: ${sku}`,
            html: `
                <input id="mapSearch" class="swal2-input" placeholder="Cari kode / nama item internal…" style="font-size:.85rem">
                <div id="mapResults" style="max-height:240px;overflow-y:auto;text-align:left;font-size:.8rem"></div>`,
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            didOpen: () => {
                const inp = document.getElementById('mapSearch');
                const box = document.getElementById('mapResults');
                let t = null;
                window._mapSelected = null;

                async function search(q) {
                    const items = await fetch(`/api/sku-mappings/search-items?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } }).then(r => r.json());
                    box.innerHTML = items.length ? items.map(i => `
                        <div class="map-opt" data-id="${i.id}" style="padding:6px 10px;border-bottom:1px solid #f1f5f9;cursor:pointer;border-radius:6px">
                            <b>${i.code}</b> — ${i.name}
                        </div>`).join('') : '<div class="text-muted p-2">Tidak ditemukan.</div>';
                    box.querySelectorAll('.map-opt').forEach(el => el.onclick = () => {
                        box.querySelectorAll('.map-opt').forEach(x => x.style.background = '');
                        el.style.background = '#eef2ff';
                        window._mapSelected = parseInt(el.dataset.id);
                    });
                }
                inp.oninput = () => { clearTimeout(t); t = setTimeout(() => search(inp.value), 300); };
                search(sku);
                inp.focus();
            },
            preConfirm: () => {
                if (!window._mapSelected) { Swal.showValidationMessage('Pilih item internal dulu'); return false; }
                return window._mapSelected;
            }
        });

        if (!itemId) return;
        try {
            await api('/api/sku-mappings', {
                method: 'POST',
                body: JSON.stringify({
                    marketplace_sku: sku,
                    channel_code: null,
                    item_id: itemId,
                    notes: 'mapping dari tab Produk'
                })
            });
            toast(`SKU ${sku} berhasil di-mapping ✔`);
            loadProducts();
        } catch (e) { alert('Gagal simpan mapping: ' + e.message); }
    };

    // ── Mesin waktu: riwayat harian produk ──────────────────────────────────
    window.showHistory = async function (pid) {
        const p = products.find(x => x.id === pid);
        try {
            const d = await api(`${API}/${pid}/history?days=90`);
            const days = (d.days || []).slice().reverse(); // terbaru dulu
            const body = days.length ? `
                <div style="max-height:320px;overflow-y:auto">
                <table style="width:100%;font-size:.75rem;text-align:right;border-collapse:collapse">
                    <thead><tr style="color:#64748b;font-size:.65rem;text-transform:uppercase;position:sticky;top:0;background:#fff">
                        <th style="text-align:left;padding:4px">Tanggal</th><th>Stok</th><th>Harga</th><th>Terjual/hari</th><th>Total Jual</th><th>Status</th>
                    </tr></thead>
                    <tbody>${days.map(r => `<tr style="border-top:1px solid #f1f5f9">
                        <td style="text-align:left;padding:4px">${(r.date || '').substring(0, 10)}</td>
                        <td style="${r.stock_total === 0 ? 'color:#dc2626;font-weight:700' : ''}">${r.stock_total}</td>
                        <td>${r.price_min != null ? 'Rp' + Number(r.price_min).toLocaleString('id-ID') : '—'}</td>
                        <td>${r.sales_delta ?? '—'}</td>
                        <td>${r.sales ?? '—'}</td>
                        <td style="font-size:.65rem">${r.item_status || ''}</td>
                    </tr>`).join('')}</tbody>
                </table></div>`
                : '<div class="text-muted p-3">Belum ada snapshot. Data terkumpul otomatis tiap malam (23:45), atau jalankan <code>php artisan marketplace:snapshot-products</code>.</div>';

            Swal.fire({
                title: `📈 ${p?.item_name || 'Produk'}`,
                html: body,
                width: 620,
                showConfirmButton: false,
                showCloseButton: true,
            });
        } catch (e) { alert('Gagal muat riwayat: ' + e.message); }
    };

    // ── Event listeners filter (instan) ─────────────────────────────────────
    let searchDeb = null;
    $('fSearch').addEventListener('input', () => { clearTimeout(searchDeb); searchDeb = setTimeout(render, 250); });
    ['fStore','fStatus','fMapping','fStock','fSort'].forEach(id => $(id).addEventListener('change', render));

    // ── Realtime ────────────────────────────────────────────────────────────
    if (window.Echo) {
        try {
            window.Echo.channel('marketplace').listen('ProductUpdated', () => loadProducts());
            const conn = window.Echo.connector?.pusher?.connection;
            if (conn) {
                const upd = () => { $('rtLabel').textContent = conn.state === 'connected' ? '· ⚡ realtime' : ''; };
                conn.bind('connected', upd); conn.bind('disconnected', upd);
                upd();
            }
        } catch (e) {}
    }

    loadProducts();
})();
</script>
@endpush
