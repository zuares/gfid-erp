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
        background: rgba(255, 255, 255, 0.9); padding: .7rem; border-radius: 12px; margin-bottom: 1.25rem;
        border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
        position: sticky; top: 68px; z-index: 290; backdrop-filter: blur(12px);
    }
    body[data-theme="dark"] .ord-tabs { background: rgba(15, 23, 42, 0.7); border-color: rgba(255,255,255,0.1); }
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
    .bo-slots{ display:flex; gap:1.25rem; flex-wrap:wrap; align-items:center; padding: 1.5rem 1rem; }
    .bo-slot{ width:64px; height:64px; border-radius:16px; border:2px dashed rgba(148,163,184,.5); display:flex; align-items:center; justify-content:center; font-size:1.5rem; color:#cbd5e1; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: #f8fafc; }
    .bo-slot.empty { cursor: pointer; transition: all 0.2s; }
    .bo-slot.empty:hover { background: #f1f5f9; border-color: rgba(148,163,184,.8); transform: translateY(-2px); }
    
    .chip-time { border: 1px solid #cbd5e1; background: #fff; padding: .4rem .85rem; border-radius: 8px; font-size: .85rem; font-weight: 700; color: #475569; cursor: pointer; transition: all .2s; user-select: none; }
    .chip-time:hover { background: #f8fafc; border-color: #94a3b8; }
    .chip-time.active { background: var(--prd-accent); border-color: var(--prd-accent); color: #fff; box-shadow: 0 4px 8px rgba(234,88,12,.25); transform: translateY(-1px); }
    body[data-theme="dark"] .chip-time { background: #1e293b; border-color: rgba(51,65,85,.6); color: #cbd5e1; }
    body[data-theme="dark"] .chip-time.active { color: #fff; }

    .bo-chip{ display:flex; align-items:center; gap:.8rem; border:1px solid rgba(15,23,42,.06); border-radius:16px; padding:.75rem; background:#fff; width:100%; max-width:320px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: transform 0.2s; }
    .bo-chip:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-color:rgba(15,23,42,.12); }
    body[data-theme="dark"] .bo-chip{ background:#1e293b; border-color:rgba(51,65,85,.6); }
    .bo-chip img{ width:48px; height:48px; border-radius:10px; object-fit:cover; background:#e2e8f0; }
    .bo-chip .nm{ font-size:.85rem; font-weight:700; line-height:1.3; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bo-chip .rm{ font-size:.75rem; color:#ea580c; font-weight:800; margin-top:.3rem; background: rgba(234,88,12,0.1); padding: 2px 8px; border-radius: 6px; display: inline-block; }

    .pick-prd-item { display: flex; align-items: center; gap: .75rem; padding: .75rem 1rem; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.2s; }
    .pick-prd-item:hover { background: #f8fafc; }
    body[data-theme="dark"] .pick-prd-item { border-color: rgba(255,255,255,0.05); }
    body[data-theme="dark"] .pick-prd-item:hover { background: rgba(255,255,255,0.02); }
    .pick-prd-item img { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; }
    .pick-prd-item .nm { font-size: .8rem; font-weight: 600; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--prd-text); }

    .btn-action { font-size: .72rem; padding: .25rem .6rem; border-radius: 6px; font-weight: 600; }
    .btn-outline-orange { color: #ea580c; border: 1px solid rgba(234,88,12,.4); background: rgba(234,88,12,.05); }
    .btn-outline-orange:hover { background: #ea580c; color: #fff; }

    .bo-tag{ font-size:.64rem; font-weight:800; padding:.1rem .45rem; border-radius:6px; text-transform:uppercase; letter-spacing:.03em; }
    .tag-schedule{ background:rgba(37,99,235,.12); color:#1d4ed8; }
    .tag-pool{ background:rgba(22,163,74,.12); color:#15803d; }
    .tag-manual{ background:rgba(100,116,139,.14); color:#475569; }
    
    .time-pill { background: rgba(37,99,235,.1); color: #1d4ed8; padding: .25rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 700; display: inline-flex; align-items:center; margin-right: .3rem; margin-bottom: .3rem; }
    .time-pill.off { opacity: .5; background: rgba(148,163,184,.2); color: #64748b; }

    .tab-pane { display: none; opacity: 0; }
    .tab-pane.active { display: block; animation: fadeInTab 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
    @keyframes fadeInTab { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

    /* ── Skeleton Loaders ── */
    .skel-tr td { position: relative; overflow: hidden; }
    .skel-tr td::after { content: ""; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent); animation: skeleton-sweep 1.2s infinite; }
    body[data-theme="dark"] .skel-tr td::after { background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent); }
    .skel-block { height: 14px; background: #e2e8f0; border-radius: 6px; margin-bottom: 6px; }
    body[data-theme="dark"] .skel-block { background: #334155; }
    .skel-av { width: 48px; height: 48px; background: #e2e8f0; border-radius: 10px; flex-shrink: 0; }
    body[data-theme="dark"] .skel-av { background: #334155; }
    @keyframes skeleton-sweep { 100% { left: 200%; } }

    /* ── Mobile Responsiveness ── */
    @media (max-width: 768px) {
        .prd-topbar { flex-direction: column; align-items: stretch; padding: .75rem; }
        .controls { width: 100%; flex-direction: column; align-items: stretch; margin-top: .5rem; gap: .75rem; }
        .controls .filter-select { max-width: 100%; text-align: center; padding: .6rem; font-size: .85rem; border: 2px solid var(--prd-accent); background: rgba(234,88,12,.05); color: var(--prd-accent); box-shadow: 0 4px 6px -1px rgba(234,88,12,.1); }
        .controls .btn-prd-outline { width: 100%; text-align: center; padding: .6rem; }
        
        /* Jadikan tombol tab full width (Grid 2 kolom agar proporsional dan tidak terlalu memakan tinggi layar) */
        .ord-tabs { 
            top: 160px; /* Adjust sticky point for mobile */ 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 8px; 
            padding: 8px; 
        }
        .ord-tab { 
            width: 100%; 
            justify-content: center; 
            font-size: .75rem; 
            padding: .6rem .25rem; 
        }

        .filter-search { max-width: 100%; }
        .bo-slots { justify-content: center; }
        .bo-chip { max-width: 100%; }
        
        /* Mobile Table to Card List */
        .table-list thead { display: none; }
        .table-list tr { display: flex; flex-direction: column; padding: 1rem; border-bottom: 1px solid var(--prd-border); gap: .5rem; position: relative; }
        .table-list td { padding: 0 !important; border: none !important; width: 100% !important; text-align: left !important; }
        .table-list td:last-child > div { flex-direction: row; width: 100%; gap: .4rem !important; }
        .table-list td:last-child > div > .btn-action { flex: 1; justify-content: center; padding: .6rem .25rem; font-size: .7rem; }
        .btn-action span { display: inline-block; } /* Tampilkan teks tombol */
        .badge.bg-warning { position: absolute; top: 1rem; right: 1rem; font-size: .65rem; }
    }
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
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card-main" style="height:100%; margin-bottom:0;">
                    <div class="card-header-styled">
                        <div><i class="bi bi-clock"></i> Jadwal Jam-Tetap</div>
                    </div>
                    <div style="padding:1.25rem;">
                        <div style="font-size:.78rem; color:#64748b; margin-bottom:1rem; line-height: 1.4;">Produk dinaikkan otomatis setiap hari pada jam yang telah ditentukan secara persis.</div>
                        <div id="scheduleList"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-main" style="height:100%; margin-bottom:0;">
                    <div class="card-header-styled">
                        <div><i class="bi bi-arrow-repeat"></i> Antrean Rotasi (Pool)</div>
                    </div>
                    <div style="padding:1.25rem;">
                        <div style="font-size:.78rem; color:#64748b; margin-bottom:1rem; line-height: 1.4;">Digilir untuk mengisi slot yang kosong setiap 4 jam. Produk yang paling lama belum dinaikkan akan mendapat prioritas giliran duluan.</div>
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
        <h6 class="modal-title fw-bold">📅 Jadwal Harian Otomatis</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-1">
        <input type="hidden" id="schedPid">
        <div style="font-size:.75rem; color:#64748b; margin-bottom:1rem; line-height:1.4;">
            Pilih jam berapa saja produk ini akan dinaikkan setiap harinya. (Saran: Jeda antar jam minimal 4 jam)
        </div>
        
        <div class="mb-3">
            <label class="form-label text-dark" style="font-size:.78rem; font-weight:700;">Pilihan Cepat (Disarankan)</label>
            <div id="schedChips" style="display:flex; flex-wrap:wrap; gap:.5rem;">
                <div class="chip-time" onclick="toggleTimeChip(this, '08:00')">08:00</div>
                <div class="chip-time" onclick="toggleTimeChip(this, '12:00')">12:00</div>
                <div class="chip-time" onclick="toggleTimeChip(this, '16:00')">16:00</div>
                <div class="chip-time" onclick="toggleTimeChip(this, '20:00')">20:00</div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label text-dark" style="font-size:.78rem; font-weight:700;">+ Waktu Spesifik (Opsional)</label>
            <input type="time" id="customSchedTime" class="form-control form-control-sm" onchange="checkSchedWarning()">
        </div>

        <div id="warnSched" class="alert alert-warning" style="display:none; font-size:.7rem; padding:.5rem .75rem; margin-bottom:1rem; border-radius:8px;">
            ⚠️ <b>Jeda Terlalu Dekat:</b> Ada jadwal dengan selisih kurang dari 4 jam. Fitur Boost berdurasi 4 jam, sehingga jadwal berdekatan mungkin ditolak oleh sistem.
        </div>

        <button class="btn btn-prd-primary w-100 btn-pill" onclick="saveScheduleModal()">Simpan Jadwal</button>
      </div>
    </div>
  </div>
</div>
<!-- Modal Pilih Produk untuk Boost -->
<div class="modal fade" id="modalPickBoost" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 10px 30px rgba(0,0,0,.15);">
      <div class="modal-header border-0 pb-1">
        <h6 class="modal-title fw-bold">Pilih Produk untuk Dinaikkan</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div style="padding: .5rem 1rem;">
            <input type="text" id="pickSearch" class="form-control form-control-sm" placeholder="Cari nama produk..." onkeyup="renderPickList()">
        </div>
        <div id="pickBoostList" style="max-height: 400px; overflow-y: auto; padding-bottom: .5rem;">
            <!-- Render list here -->
        </div>
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
        // Hanya toko Shopee yang BENAR-BENAR terhubung (punya token). Toko yang
        // belum di-authorize dibuang supaya tidak memicu "invalid token".
        const shopee = stores.filter(s => {
            const code = (s.channel?.code || '').toLowerCase();
            const isShopee = code.includes('shp') || code === 'shopee';
            return isShopee && s.connection_status !== 'NOT_CONNECTED' && s.connection_status !== 'INVALID_APP_KEY';
        });

        if (!shopee.length) {
            $('fStore').innerHTML = '<option value="">— tidak ada toko Shopee terhubung —</option>';
            statusData = { error: 'Belum ada toko Shopee yang terhubung. Authorize/Re-authorize toko dulu di menu Toko.' };
            switchTab(activeTab);
            return;
        }

        $('fStore').innerHTML = shopee.map(s => {
            const warn = s.connection_status === 'TOKEN_EXPIRED' ? ' ⚠ token kedaluwarsa' : '';
            return `<option value="${s.id}">${esc(s.name)}${warn}</option>`;
        }).join('');
        $('fStore').addEventListener('change', reloadAll);

        reloadAll(); // auto-pilih toko pertama yang terhubung
    }

    async function reloadAll() {
        if (!storeId()) {
            products = [];
            statusData = { error: 'Silakan pilih toko terlebih dahulu.' };
            switchTab(activeTab);
            return;
        }
        
        // Tampilkan Skeleton Loaders
        $('slotCount').textContent = '';
        $('slotBar').innerHTML = Array(5).fill('<div class="bo-slot" style="border:none;"><div class="skel-av skel-tr" style="width:100%;height:100%;border-radius:14px;background:#e2e8f0;border:none;"></div></div>').join('');
        $('boostedList').innerHTML = Array(2).fill('<div class="bo-chip skel-tr" style="border:none;box-shadow:none;"><div class="skel-av"></div><div style="flex:1"><div class="skel-block" style="width:80%"></div><div class="skel-block" style="width:40%"></div></div></div>').join('');
        $('allProductsBody').innerHTML = Array(5).fill(0).map(()=>`<tr class="skel-tr"><td><div style="display:flex;gap:.75rem;align-items:center"><div class="skel-av"></div><div style="flex:1"><div class="skel-block" style="width:70%"></div><div class="skel-block" style="width:40%;margin-bottom:0"></div></div></div></td><td><div class="skel-block" style="width:50%;margin-bottom:0"></div></td><td><div class="skel-block" style="width:80px;margin-left:auto;margin-bottom:0"></div></td></tr>`).join('');
        $('scheduleList').innerHTML = '<div class="skel-block" style="width:100%;height:40px;border-radius:8px;"></div>';
        $('poolList').innerHTML = '<div class="skel-block" style="width:100%;height:40px;border-radius:8px;"></div>';
        $('logList').innerHTML = Array(3).fill(0).map(()=>`<tr class="skel-tr"><td><div class="skel-block" style="width:60%"></div></td><td><div class="skel-block" style="width:40%"></div></td><td><div class="skel-block" style="width:80%"></div></td><td><div class="skel-block" style="width:50%"></div></td></tr>`).join('');

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
        for (let i = 0; i < max; i++) {
            if (i < used) {
                bar += `<div class="bo-slot filled">🚀</div>`;
            } else {
                bar += `<div class="bo-slot empty" onclick="openPickBoostModal()" title="Klik untuk memilih produk">+</div>`;
            }
        }
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

    let pickBoostModalInstance = null;

    function openPickBoostModal() {
        if (!products || !products.length) {
            toast('Silakan tunggu data produk dimuat atau pastikan toko memiliki produk.', 'warning');
            return;
        }
        $('pickSearch').value = '';
        renderPickList();
        if (!pickBoostModalInstance) pickBoostModalInstance = new bootstrap.Modal($('modalPickBoost'));
        pickBoostModalInstance.show();
    }

    function renderPickList() {
        const q = $('pickSearch').value.toLowerCase();
        const boostedMap = {};
        (statusData.items || []).forEach(it => boostedMap[it.id] = it);
        
        // Filter produk yang statusnya NORMAL dan belum diboost
        let list = products.filter(p => p.item_status === 'NORMAL' && !boostedMap[p.id]);
        if (q) list = list.filter(p => (p.item_name || '').toLowerCase().includes(q));

        if (!list.length) {
            $('pickBoostList').innerHTML = `<div class="text-center py-4 text-muted" style="font-size:.8rem;">Tidak ada produk tersedia.</div>`;
            return;
        }

        $('pickBoostList').innerHTML = list.map(p => `
            <div class="pick-prd-item" onclick="pickBoostAction(${p.id})">
                ${p.image_url ? `<img src="${esc(p.image_url)}">` : `<div style="width:40px;height:40px;background:#e2e8f0;border-radius:8px;"></div>`}
                <div class="nm" title="${esc(p.item_name)}">${esc(p.item_name)}</div>
                <button class="btn btn-sm btn-prd-outline" style="padding:.25rem .6rem;font-size:.7rem;border-radius:6px;">Pilih</button>
            </div>
        `).join('');
    }

    async function pickBoostAction(pid) {
        if (pickBoostModalInstance) pickBoostModalInstance.hide();
        await quickBoost(pid);
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
                    ${isBoosted ? `<span class="badge bg-warning text-dark"><i class="bi bi-rocket-takeoff"></i> <span class="rm-text">Sedang Naik (${rmLabel})</span></span>` : `<span class="text-muted d-none d-md-block" style="font-size:.75rem;">-</span>`}
                </td>
                <td style="text-align:right; white-space:nowrap;">
                    <div style="display:flex; gap:.3rem; justify-content:flex-end;">
                        <button class="btn btn-action btn-outline-orange" onclick="quickBoost(${p.id})" title="Naikkan Sekarang"><i class="bi bi-rocket"></i> <span>Naikkan</span></button>
                        <button class="btn btn-action btn-outline-secondary" onclick="openSchedModal(${p.id})" title="Atur Jadwal (Tiap Hari)"><i class="bi bi-calendar-check"></i> <span>Jadwal</span></button>
                        <button class="btn btn-action btn-outline-secondary" onclick="addPool(${p.id})" title="Masukkan ke Antrian Rotasi (Gilir)"><i class="bi bi-arrow-repeat"></i> <span>Rotasi</span></button>
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
        document.querySelectorAll('#schedChips .chip-time').forEach(el => el.classList.remove('active'));
        $('customSchedTime').value = '';
        $('warnSched').style.display = 'none';
        new bootstrap.Modal($('modalSched')).show();
    }
    
    function toggleTimeChip(el, timeStr) {
        el.classList.toggle('active');
        checkSchedWarning();
    }

    function checkSchedWarning() {
        const chips = [...document.querySelectorAll('#schedChips .chip-time.active')].map(el => el.innerText.trim());
        const custom = $('customSchedTime').value;
        const allTimes = [...chips];
        if (custom) allTimes.push(custom);
        
        let tooClose = false;
        if (allTimes.length > 1) {
            const minutes = allTimes.map(t => {
                const [h,m] = t.split(':').map(Number);
                return h * 60 + m;
            }).sort((a,b)=>a-b);
            
            for (let i = 1; i < minutes.length; i++) {
                if (minutes[i] - minutes[i-1] < 240) { tooClose = true; break; }
            }
            if (!tooClose && (minutes[0] + 1440 - minutes[minutes.length-1] < 240)) {
                tooClose = true;
            }
        }
        $('warnSched').style.display = tooClose ? 'block' : 'none';
    }

    async function saveScheduleModal() {
        const pid = parseInt($('schedPid').value);
        const chips = [...document.querySelectorAll('#schedChips .chip-time.active')].map(el => el.innerText.trim());
        const custom = $('customSchedTime').value;
        const times = [...chips];
        if (custom && !times.includes(custom)) times.push(custom);
        
        if (!times.length) {
            toast('Pilih minimal satu jam.', 'warning');
            return;
        }
        
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
        if (!storeId()) {
            c.innerHTML = '<div class="text-muted" style="font-size:.8rem;">Silakan pilih toko terlebih dahulu.</div>';
            return;
        }
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
        if (!storeId()) {
            c.innerHTML = '<div class="text-muted" style="font-size:.8rem;">Silakan pilih toko terlebih dahulu.</div>';
            return;
        }
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
        if (!storeId()) {
            c.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">Silakan pilih toko terlebih dahulu.</td></tr>`;
            return;
        }
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
