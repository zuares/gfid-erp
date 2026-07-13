@extends('layouts.app')
@section('title', 'Marketplace • Naikkan Produk')

@include('marketplace._shared')

@push('head')
<style>
    :root{
        --prd-accent:#ea580c; /* using orange for boost */
        --prd-accent-2:#c2410c;
        --prd-border:rgba(148,163,184,.18);
        --prd-muted:#64748b;
    }
    .page-wrap{ max-width:1180px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }

    /* ── Topbar ── */
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

    .controls{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
    .btn-pill{ border-radius:7px; padding-inline:.78rem; box-shadow:none!important; font-weight:600; font-size:.8rem; }
    .btn-prd-primary{ background:var(--prd-accent)!important; border-color:var(--prd-accent)!important; color:#fff!important; }
    .btn-prd-primary:hover{ background:var(--prd-accent-2)!important; border-color:var(--prd-accent-2)!important; }
    .btn-prd-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-prd-outline:hover{ background:rgba(148,163,184,.08)!important; }

    /* ── Tabs ── */
    .ord-tabs {
        display: flex; gap: .5rem; flex-wrap: wrap;
        background: #ffffff; padding: .7rem; border-radius: 12px; margin-bottom: 1.25rem;
        border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
    }
    body[data-theme="dark"] .ord-tabs { background: rgba(15, 23, 42, 0.4); border-color: rgba(255,255,255,0.1); }
    .ord-tab {
        display: flex; align-items: center; gap: .45rem;
        background: #f8fafc; border: 1px solid #f1f5f9; padding: .45rem .85rem;
        font-size: .8rem; font-weight: 600; color: #475569;
        border-radius: 8px; cursor: pointer; transition: all .2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative; white-space: nowrap; flex-shrink: 0;
    }
    .ord-tab:hover:not(.active) { background: #f1f5f9; color: #1e293b; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    body[data-theme="dark"] .ord-tab:hover:not(.active) { background: rgba(255,255,255,0.05); color: #fff; }
    .ord-tab.active { 
        color: #fff; background: linear-gradient(135deg, #ea580c, #c2410c); 
        border-color: #9a3412; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25);
    }

    /* ── Filter bar ── */
    .filter-bar{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; margin-bottom:1rem; }
    .filter-select{ border-radius:7px; font-size:.8rem; min-width:160px; max-width:200px; padding:.4rem .6rem; border:1px solid var(--prd-border); background:var(--card,#fff); }
    .filter-search{ border-radius:7px; font-size:.8rem; max-width:280px; padding:.4rem .6rem; border:1px solid var(--prd-border); background:var(--card,#fff); flex: 1; }
    body[data-theme="dark"] .filter-select, body[data-theme="dark"] .filter-search { background:#0f172a; color:#fff; border-color: rgba(148,163,184,.25); }

    /* ── Card + table ── */
    .card-main{
        background:var(--card,#fff); border-radius:8px; border:1px solid var(--prd-border); overflow:hidden; margin-bottom:1.5rem;
    }
    body[data-theme="dark"] .card-main{ border-color:rgba(51,65,85,.85); }
    
    .card-header-styled {
        padding: .75rem 1rem; border-bottom: 1px solid var(--prd-border); background: rgba(248,250,252,.5);
        font-weight: 700; font-size: .9rem; display: flex; justify-content: space-between; align-items: center;
    }
    body[data-theme="dark"] .card-header-styled { background: rgba(15,23,42,.4); }

    .table-list{ width:100%; margin-bottom:0; border-collapse:collapse; }
    .table-list thead th{
        border-bottom:1px solid var(--prd-border); font-size:.68rem; color:#64748b;
        background:var(--card,#fff); padding:.6rem 1rem; white-space:nowrap; text-align:left;
    }
    body[data-theme="dark"] .table-list thead th{ background:rgba(15,23,42,.98); color:#9ca3af; }
    .table-list tbody td{
        vertical-align:middle; border-top:1px solid rgba(148,163,184,.16);
        padding:.6rem 1rem; font-size:.78rem;
    }
    body[data-theme="dark"] .table-list tbody td{ border-top-color:rgba(51,65,85,.85); }

    .prd-img{ width:48px; height:48px; border-radius:7px; object-fit:cover; background:#f1f5f9; flex-shrink:0; }
    .prd-name{ font-weight:700; color:inherit; max-width:320px; line-height:1.3; }
    .prd-sku{ font-size:.7rem; color:#94a3b8; margin-top:.2rem; }
    .muted{ font-size:.74rem; color:#6b7280; }

    /* ── Slots ── */
    .bo-slots{ display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; padding: 1rem; }
    .bo-slot{ width:48px; height:48px; border-radius:12px; border:2px dashed rgba(148,163,184,.4); display:flex; align-items:center; justify-content:center; font-size:1.1rem; color:#cbd5e1; }
    .bo-slot.filled{ border-style:solid; border-color:#ea580c; background:rgba(234,88,12,.08); color:#ea580c; }
    
    .bo-chip{ display:flex; align-items:center; gap:.6rem; border:1px solid rgba(15,23,42,.1); border-radius:12px; padding:.5rem .7rem; background:#f8fafc; width:100%; max-width:320px; }
    body[data-theme="dark"] .bo-chip{ background:#1e293b; border-color:rgba(51,65,85,.6); }
    .bo-chip img{ width:38px; height:38px; border-radius:8px; object-fit:cover; background:#e2e8f0; }
    .bo-chip .nm{ font-size:.8rem; font-weight:700; line-height:1.2; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bo-chip .rm{ font-size:.7rem; color:#ea580c; font-weight:700; margin-top:.15rem; }

    .btn-action { font-size: .72rem; padding: .25rem .6rem; border-radius: 6px; font-weight: 600; }
    .btn-outline-orange { color: #ea580c; border: 1px solid rgba(234,88,12,.4); background: rgba(234,88,12,.05); }
    .btn-outline-orange:hover { background: #ea580c; color: #fff; }

    .bo-tag{ font-size:.64rem; font-weight:800; padding:.1rem .45rem; border-radius:6px; text-transform:uppercase; letter-spacing:.03em; }
    .tag-schedule{ background:rgba(37,99,235,.12); color:#1d4ed8; }
    .tag-pool{ background:rgba(22,163,74,.12); color:#15803d; }
    .tag-manual{ background:rgba(100,116,139,.14); color:#475569; }
    
    .time-pill { background: rgba(37,99,235,.1); color: #1d4ed8; padding: .25rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 700; display: inline-flex; align-items:center; margin-right: .3rem; margin-bottom: .3rem; }
    .time-pill.off { opacity: .5; background: rgba(148,163,184,.2); color: #64748b; }

    .tab-pane { display: none; }
    .tab-pane.active { display: block; }
</style>
@endpush

@section('content')
<div class="page-wrap">
    {{-- Topbar --}}
    <div class="prd-topbar">
        <div>
            <h1 class="title">🚀 Naikkan Produk (Boost)</h1>
            <div class="sub">Jadwalkan produk naik otomatis atau gilir (rotasi) setiap 4 jam.</div>
        </div>
        <div class="controls">
            <select id="fStore" class="filter-select form-select-sm" style="font-weight:600;"></select>
            <a href="{{ route('marketplace.products') }}" class="btn btn-pill btn-prd-outline">← Kembali ke Produk</a>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="ord-tabs">
        <div class="ord-tab active" data-tab="tab-status" onclick="switchTab('tab-status')">
            <i class="bi bi-broadcast"></i> Status Saat Ini
        </div>
        <div class="ord-tab" data-tab="tab-all" onclick="switchTab('tab-all')">
            <i class="bi bi-box-seam"></i> Semua Produk
        </div>
        <div class="ord-tab" data-tab="tab-sched" onclick="switchTab('tab-sched')">
            <i class="bi bi-calendar-check"></i> Jadwal & Rotasi
        </div>
        <div class="ord-tab" data-tab="tab-logs" onclick="switchTab('tab-logs')">
            <i class="bi bi-clock-history"></i> Riwayat
        </div>
    </div>

    {{-- Tab: Status Slot --}}
    <div id="tab-status" class="tab-pane active">
        <div class="card-main">
            <div class="card-header-styled">
                <div>📊 Status Boost Slot <span id="slotCount" style="color:#ea580c; margin-left:.5rem;"></span></div>
                <button class="btn btn-sm btn-prd-outline" onclick="loadStatus()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            </div>
            <div class="bo-slots" id="slotBar"></div>
            <div style="padding: 0 1rem 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;" id="boostedList"></div>
        </div>
    </div>

    {{-- Tab: Semua Produk --}}
    <div id="tab-all" class="tab-pane">
        <div class="filter-bar">
            <input type="text" id="fSearch" class="filter-search" placeholder="🔍 Cari nama atau SKU produk..." oninput="renderAllProducts()">
        </div>
        <div class="card-main">
            <div class="table-wrap" style="overflow-x:auto;">
                <table class="table-list">
                    <thead>
                        <tr>
                            <th style="width:50%">Produk</th>
                            <th>Status Saat Ini</th>
                            <th style="text-align:right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="allProductsBody">
                        <tr><td colspan="3" class="text-center py-4 text-muted">Memuat produk...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab: Jadwal & Rotasi --}}
    <div id="tab-sched" class="tab-pane">
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card-main" style="height:100%;">
                    <div class="card-header-styled">
                        <div><i class="bi bi-clock"></i> Jadwal Jam-Tetap</div>
                    </div>
                    <div style="padding:1rem;">
                        <div style="font-size:.75rem; color:#64748b; margin-bottom:1rem;">Produk dinaikkan otomatis tiap hari pada jam yang ditentukan.</div>
                        <div id="scheduleList"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card-main" style="height:100%;">
                    <div class="card-header-styled">
                        <div><i class="bi bi-arrow-repeat"></i> Antrian Rotasi (Pool)</div>
                    </div>
                    <div style="padding:1rem;">
                        <div style="font-size:.75rem; color:#64748b; margin-bottom:1rem;">Digilir mengisi slot kosong tiap 4 jam. Yang terlama belum naik mendapat giliran duluan.</div>
                        <div id="poolList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab: Riwayat --}}
    <div id="tab-logs" class="tab-pane">
        <div class="card-main">
            <div class="table-wrap" style="overflow-x:auto;">
                <table class="table-list">
                    <thead>
                        <tr>
                            <th>Waktu Eksekusi</th>
                            <th>Sumber</th>
                            <th>Produk</th>
                            <th>Status / Pesan</th>
                        </tr>
                    </thead>
                    <tbody id="logList">
                        <tr><td colspan="4" class="text-center py-4 text-muted">Memuat...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Jadwal -->
<div class="modal fade" id="modalSched" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 10px 30px rgba(0,0,0,.15);">
      <div class="modal-header border-0 pb-1">
        <h6 class="modal-title fw-bold">Atur Jadwal</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="schedPid">
        <div class="mb-3">
            <label class="form-label text-muted" style="font-size:.75rem;">Pilih jam untuk menaikkan produk ini tiap harinya:</label>
            <div id="schedTimesModal" style="display:flex; flex-direction:column; gap:.5rem;">
                <input type="time" class="form-control" value="08:00">
            </div>
            <button class="btn btn-sm text-primary p-0 mt-2" onclick="addTimeFieldModal()" style="font-size:.75rem; font-weight:600;"><i class="bi bi-plus-circle"></i> Tambah Jam</button>
        </div>
        <button class="btn btn-prd-primary w-100 btn-pill" onclick="saveScheduleModal()">Simpan Jadwal</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    const { api, esc } = window.mpHelpers;
    const $ = id => document.getElementById(id);
    const API = '/api/marketplace';
    let stores = [];
    let products = [];
    let statusData = {};
    let activeTab = 'tab-status';

    const storeId = () => $('fStore').value;

    function remainingLabel(mins) {
        if (mins == null || mins <= 0) return '';
        const h = Math.floor(mins / 60), m = mins % 60;
        return (h ? h + 'j ' : '') + m + 'm lagi';
    }
    function fmtTime(d) {
        if (!d) return '—';
        return new Date(d).toLocaleString('id-ID', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' });
    }

    function switchTab(tabId) {
        document.querySelectorAll('.ord-tab').forEach(el => el.classList.remove('active'));
        document.querySelector(`.ord-tab[data-tab="${tabId}"]`).classList.add('active');
        document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
        $(tabId).classList.add('active');
        activeTab = tabId;

        if (tabId === 'tab-status') loadStatus();
        if (tabId === 'tab-all') renderAllProducts();
        if (tabId === 'tab-sched') { loadSchedules(); loadPool(); }
        if (tabId === 'tab-logs') loadLogs();
    }

    async function init() {
        stores = await api(`${API}/stores`).catch(() => []);
        const shopee = stores.filter(s => (s.channel?.code || '').toLowerCase().includes('shp') || (s.channel?.code || '').toLowerCase() === 'shopee');
        const list = shopee.length ? shopee : stores;
        
        $('fStore').innerHTML = list.map(s => `<option value="${s.id}">${esc(s.name)}</option>`).join('') || '<option value="">— tidak ada toko —</option>';
        $('fStore').addEventListener('change', reloadAll);
        
        reloadAll();
    }

    async function reloadAll() {
        await loadStatus(); // load status first so renderAllProducts has statusData
        await loadProducts();
        switchTab(activeTab); // refresh current tab
    }

    async function loadProducts() {
        const sid = storeId();
        if (!sid) { products = []; return; }
        try {
            products = await api(`${API}/products?store_id=${sid}`);
            // sort so NORMAL is first
            products.sort((a, b) => {
                if (a.item_status === 'NORMAL' && b.item_status !== 'NORMAL') return -1;
                if (a.item_status !== 'NORMAL' && b.item_status === 'NORMAL') return 1;
                return 0;
            });
        } catch { products = []; }
    }

    // ── Status Tab ──
    async function loadStatus() {
        try { 
            statusData = await api(`${API}/boost/status?store_id=${storeId()}`); 
        } catch (e) { 
            statusData = { error: e.message }; 
        }

        if (statusData.error) { 
            $('slotBar').innerHTML = `<span class="text-danger" style="font-size:.78rem">${esc(statusData.error)}</span>`; 
            $('slotCount').textContent=''; 
            $('boostedList').innerHTML = '';
            return; 
        }

        const used = statusData.used || (statusData.items || []).length;
        const max = statusData.max || 5;
        $('slotCount').textContent = `(${used}/${max})`;
        
        let bar = '';
        for (let i = 0; i < max; i++) bar += `<div class="bo-slot ${i < used ? 'filled' : ''}">${i < used ? '🚀' : ''}</div>`;
        $('slotBar').innerHTML = bar;

        $('boostedList').innerHTML = (statusData.items || []).length
            ? statusData.items.map(it => `
                <div class="bo-chip">
                    ${it.image_url ? `<img src="${esc(it.image_url)}">` : '<div style="width:38px;height:38px;background:#e2e8f0;border-radius:8px;"></div>'}
                    <div style="min-width:0; flex:1;">
                        <div class="nm" title="${esc(it.name)}">${esc(it.name)}</div>
                        <div class="rm">${remainingLabel(it.remaining_minutes) || 'aktif'}</div>
                    </div>
                </div>`).join('')
            : '<span class="text-muted" style="font-size:.8rem;">Belum ada produk yang dinaikkan.</span>';
    }

    // ── All Products Tab ──
    function renderAllProducts() {
        const q = $('fSearch').value.toLowerCase();
        let list = products;
        if (q) {
            list = list.filter(p => (p.item_name || '').toLowerCase().includes(q) || (p.item_sku || '').toLowerCase().includes(q));
        }

        const tbody = $('allProductsBody');
        if (!list.length) {
            tbody.innerHTML = `<tr><td colspan="3" class="text-center py-4 text-muted">Tidak ada produk ditemukan.</td></tr>`;
            return;
        }

        // map boosted status
        const boostedMap = {};
        (statusData.items || []).forEach(it => boostedMap[it.id] = it);

        tbody.innerHTML = list.map(p => {
            const isBoosted = !!boostedMap[p.id];
            const rmLabel = isBoosted ? remainingLabel(boostedMap[p.id].remaining_minutes) : '';
            
            return `
            <tr>
                <td>
                    <div style="display:flex; align-items:flex-start; gap:.75rem;">
                        ${p.image_url ? `<img src="${esc(p.image_url)}" class="prd-img">` : `<div class="prd-img"></div>`}
                        <div>
                            <div class="prd-name">${esc(p.item_name || 'Item ' + p.id)}</div>
                            <div class="prd-sku">${esc(p.item_sku || 'No SKU')} ${p.item_status !== 'NORMAL' ? `<span class="badge bg-secondary ms-1">Disembunyikan</span>` : ''}</div>
                        </div>
                    </div>
                </td>
                <td>
                    ${isBoosted ? `<span class="badge bg-warning text-dark"><i class="bi bi-rocket-takeoff"></i> Sedang Naik (${rmLabel})</span>` : `<span class="text-muted" style="font-size:.75rem;">-</span>`}
                </td>
                <td style="text-align:right; white-space:nowrap;">
                    <div style="display:flex; gap:.3rem; justify-content:flex-end;">
                        <button class="btn btn-action btn-outline-orange" onclick="quickBoost(${p.id})" title="Naikkan Sekarang"><i class="bi bi-rocket"></i> Naikkan</button>
                        <button class="btn btn-action btn-outline-secondary" onclick="openSchedModal(${p.id})" title="Atur Jadwal (Tiap Hari)"><i class="bi bi-calendar-check"></i> Jadwal</button>
                        <button class="btn btn-action btn-outline-secondary" onclick="addPool(${p.id})" title="Masukkan ke Antrian Rotasi (Gilir)"><i class="bi bi-arrow-repeat"></i> Rotasi</button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    }

    async function quickBoost(pid) {
        try {
            const res = await api(`${API}/boost/now`, { method:'POST', body: JSON.stringify({ store_id: storeId(), product_ids: [pid] }) });
            toast(res.message, res.success ? 'success' : 'warning');
            await loadStatus();
            if(activeTab === 'tab-all') renderAllProducts();
        } catch (e) { toast('Gagal: ' + e.message, 'error'); }
    }

    // Modal Schedule
    function openSchedModal(pid) {
        $('schedPid').value = pid;
        $('schedTimesModal').innerHTML = '<input type="time" class="form-control" value="08:00">';
        new bootstrap.Modal($('modalSched')).show();
    }
    function addTimeFieldModal() {
        const div = document.createElement('div');
        div.innerHTML = '<input type="time" class="form-control" value="12:00">';
        $('schedTimesModal').appendChild(div.firstElementChild);
    }
    async function saveScheduleModal() {
        const pid = parseInt($('schedPid').value);
        const times = [...document.querySelectorAll('#schedTimesModal input[type="time"]')].map(i => i.value).filter(Boolean);
        if (!times.length) return alert('Isi minimal satu jam.');
        try {
            const res = await api(`${API}/boost/schedules`, { method:'POST', body: JSON.stringify({ store_id: storeId(), marketplace_product_id: pid, times }) });
            toast(res.message);
            bootstrap.Modal.getInstance($('modalSched')).hide();
            if(activeTab === 'tab-sched') loadSchedules();
        } catch (e) { toast('Gagal: ' + e.message, 'error'); }
    }

    // ── Jadwal ──
    async function loadSchedules() {
        const c = $('scheduleList');
        c.innerHTML = '<div class="text-muted" style="font-size:.8rem;">Memuat…</div>';
        let d;
        try { d = await api(`${API}/boost/schedules?store_id=${storeId()}`); }
        catch (e) { c.innerHTML = `<div class="text-danger" style="font-size:.78rem">${esc(e.message)}</div>`; return; }

        const byProd = {};
        (d.schedules || []).forEach(s => { (byProd[s.product_id] ||= { name:s.product, sku:s.sku, image:s.image_url, rows:[] }).rows.push(s); });
        const keys = Object.keys(byProd);
        
        c.innerHTML = keys.length ? keys.map(k => {
            const g = byProd[k];
            const pills = g.rows.sort((a,b)=>a.time.localeCompare(b.time)).map(r =>
                `<span class="time-pill ${r.is_active ? '' : 'off'}">${r.time}
                    <a href="javascript:toggleSchedule(${r.id})" style="text-decoration:none; margin-left:.35rem;" title="Aktif/Jeda">${r.is_active ? '⏸' : '▶'}</a>
                    <a href="javascript:delSchedule(${r.id})" style="text-decoration:none; color:#dc2626; margin-left:.35rem;" title="Hapus">✕</a>
                </span>`).join('');
            
            return `
            <div style="display:flex; align-items:center; gap:.75rem; padding:.6rem 0; border-bottom:1px solid rgba(148,163,184,.14);">
                ${g.image ? `<img src="${esc(g.image)}" style="width:40px;height:40px;border-radius:6px;object-fit:cover;flex-shrink:0;">` : '<div style="width:40px;height:40px;background:#e2e8f0;border-radius:6px;flex-shrink:0;"></div>'}
                <div style="flex:1; min-width:0;">
                    <div style="font-size:.8rem; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${esc(g.name || '—')}</div>
                    <div style="font-size:.7rem; color:#64748b;">${esc(g.sku || '')}</div>
                </div>
                <div style="text-align:right; max-width:180px;">${pills}</div>
            </div>`;
        }).join('') : '<div class="text-muted" style="font-size:.8rem;">Belum ada jadwal tetap. Klik "Jadwal" di tab Semua Produk.</div>';
    }
    
    async function toggleSchedule(id) { await api(`${API}/boost/schedules/${id}/toggle`, { method:'POST' }); loadSchedules(); }
    async function delSchedule(id) { if (!confirm('Hapus slot jadwal ini?')) return; await api(`${API}/boost/schedules/${id}`, { method:'DELETE' }); loadSchedules(); }

    // ── Pool ──
    async function addPool(pid) {
        try {
            const res = await api(`${API}/boost/pool`, { method:'POST', body: JSON.stringify({ store_id: storeId(), product_ids: [pid] }) });
            toast(res.message);
            if(activeTab === 'tab-sched') loadPool();
        } catch (e) { toast('Gagal: ' + e.message, 'error'); }
    }

    async function loadPool() {
        const c = $('poolList');
        c.innerHTML = '<div class="text-muted" style="font-size:.8rem;">Memuat…</div>';
        let d;
        try { d = await api(`${API}/boost/pool?store_id=${storeId()}`); }
        catch (e) { c.innerHTML = `<div class="text-danger" style="font-size:.78rem">${esc(e.message)}</div>`; return; }

        c.innerHTML = (d.pool || []).length ? d.pool.map(p => `
            <div style="display:flex; align-items:center; gap:.75rem; padding:.6rem 0; border-bottom:1px solid rgba(148,163,184,.14); ${p.is_active?'':'opacity:.5;'}">
                ${p.image_url ? `<img src="${esc(p.image_url)}" style="width:40px;height:40px;border-radius:6px;object-fit:cover;flex-shrink:0;">` : '<div style="width:40px;height:40px;background:#e2e8f0;border-radius:6px;flex-shrink:0;"></div>'}
                <div style="flex:1; min-width:0;">
                    <div style="font-size:.8rem; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${esc(p.product || '—')}</div>
                    <div style="font-size:.7rem; color:#64748b;">Trkhr Naik: ${p.last_boosted_at ? fmtTime(p.last_boosted_at) : 'belum pernah'}</div>
                </div>
                <div style="display:flex; gap:.3rem;">
                    <button class="btn btn-action btn-outline-secondary" onclick="togglePool(${p.id})">${p.is_active ? '⏸ Jeda' : '▶ Aktif'}</button>
                    <button class="btn btn-action btn-outline-danger" onclick="delPool(${p.id})">✕</button>
                </div>
            </div>`).join('') : '<div class="text-muted" style="font-size:.8rem;">Antrian kosong. Klik "Rotasi" di tab Semua Produk.</div>';
    }

    async function togglePool(id) { await api(`${API}/boost/pool/${id}/toggle`, { method:'POST' }); loadPool(); }
    async function delPool(id) { if (!confirm('Keluarkan produk dari antrian?')) return; await api(`${API}/boost/pool/${id}`, { method:'DELETE' }); loadPool(); }

    // ── Logs ──
    async function loadLogs() {
        const c = $('logList');
        c.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">Memuat...</td></tr>`;
        let d;
        try { d = await api(`${API}/boost/logs?store_id=${storeId()}`); }
        catch (e) { c.innerHTML = `<tr><td colspan="4" class="text-danger text-center">${esc(e.message)}</td></tr>`; return; }

        c.innerHTML = (d.logs || []).length ? d.logs.map(l => `
            <tr>
                <td style="white-space:nowrap;">${fmtTime(l.boosted_at)}</td>
                <td><span class="bo-tag tag-${l.source}">${l.source}</span></td>
                <td>
                    <div style="font-weight:600;">${esc(l.product)}</div>
                    <div class="text-muted" style="font-size:.7rem;">${esc(l.sku || '')}</div>
                </td>
                <td>
                    ${l.success ? `<span class="badge bg-success">Sukses</span>` : `<span class="badge bg-danger">Gagal</span><div style="font-size:.7rem;color:#b91c1c;margin-top:.1rem;">${esc(l.message)}</div>`}
                </td>
            </tr>`).join('') : `<tr><td colspan="4" class="text-center py-4 text-muted">Belum ada riwayat.</td></tr>`;
    }

    function toast(title, icon = 'success') {
        if (window.Swal) Swal.fire({ toast:true, position:'top-end', icon, title, showConfirmButton:false, timer:2800 });
    }

    document.addEventListener('DOMContentLoaded', init);
</script>
@endpush
