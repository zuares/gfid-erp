@extends('layouts.app')

@section('title', 'Marketplace • Pesanan Kilat')

@push('head')
<style>
    :root{
        --shp-accent:#334155;
        --shp-accent-2:#1f2937;
        --shp-border:rgba(148,163,184,.18);
        --shp-border-strong:rgba(148,163,184,.30);
        --shp-muted:#64748b;
    }
    
    .page-wrap{ max-width:1040px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }
    
    .card-main{
        background: var(--card, #fff);
        border-radius: 8px;
        border: 1px solid var(--shp-border);
        box-shadow: none;
        overflow:hidden;
    }
    body[data-theme="dark"] .card-main{
        border-color: rgba(51,65,85,.85);
        background: var(--card, #0f172a);
    }

    .ship-topbar{
        position:sticky;
        top:0;
        z-index:300;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:.6rem;
        flex-wrap:wrap;
        padding:.45rem .75rem;
        margin-inline:-.75rem;
        margin-bottom:.65rem;
        background:var(--card,#fff);
        border-bottom:1px solid var(--shp-border);
    }
    body[data-theme="dark"] .ship-topbar{ background:var(--card,#0f172a); }
    .title{ font-weight: 750; font-size:1.15rem; letter-spacing: 0; margin:0; display:flex; align-items:center; gap:.5rem; color:var(--shp-accent); }
    body[data-theme="dark"] .title{ color:#f8fafc; }
    
    .controls { display: flex; gap: .45rem; align-items: center; flex-wrap: wrap; }
    @media (min-width: 1024px) {
        .ship-topbar { flex-wrap: nowrap; overflow-x: auto; }
        .ship-topbar::-webkit-scrollbar { display: none; }
        .controls { flex-wrap: nowrap; }
    }
    
    .ord-search-bar {
        display: flex; align-items: center; gap: .4rem;
        background: transparent; border: 1px solid rgba(148,163,184,.35);
        border-radius: 7px; padding: .32rem .75rem;
        transition: border-color .15s; flex: 1; min-width: 180px; max-width: 320px;
    }
    .ord-search-bar:focus-within { border-color: var(--shp-accent); box-shadow: 0 0 0 2px rgba(148,163,184,.15); }
    body[data-theme="dark"] .ord-search-bar { border-color: rgba(148,163,184,.25); }
    body[data-theme="dark"] .ord-search-bar:focus-within { border-color: #94a3b8; box-shadow: 0 0 0 2px rgba(148,163,184,.1); }
    .ord-search-bar input {
        border: none; background: transparent; outline: none;
        font-size: .78rem; width: 100%; color: #0f172a;
    }
    body[data-theme="dark"] .ord-search-bar input { color: #f8fafc; }
    body[data-theme="dark"] .ord-search-bar input::placeholder { color: #64748b; }

    .btn-ship-primary{ background:var(--shp-accent)!important; border-color:var(--shp-accent)!important; color:#fff!important; padding:.32rem .75rem; border-radius:7px; font-weight:600; font-size:.78rem; display:inline-flex; align-items:center; gap:.35rem; cursor:pointer; transition: all .15s; }
    .btn-ship-primary:hover{ background:var(--shp-accent-2)!important; border-color:var(--shp-accent-2)!important; }
    .btn-ship-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; padding:.32rem .75rem; border-radius:7px; font-weight:600; font-size:.78rem; display:inline-flex; align-items:center; gap:.35rem; cursor:pointer; transition: all .15s; }
    .btn-ship-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }
    body[data-theme="dark"] .btn-ship-outline { color: #9ca3af!important; border-color: rgba(148,163,184,.25)!important; }
    body[data-theme="dark"] .btn-ship-outline:hover { background: rgba(148,163,184,.15)!important; color: #fff!important; }

    .btn-toolbar {
        display: inline-flex; align-items: center; gap: .35rem;
        background: transparent; border: 1px solid rgba(148,163,184,.35); border-radius: 7px;
        padding: .25rem .6rem; font-size: .72rem; font-weight: 600;
        color: #475569; cursor: pointer; transition: all .15s; white-space: nowrap; box-shadow: none;
    }
    .btn-toolbar:hover { background: rgba(148,163,184,.08); color: #111827; }
    body[data-theme="dark"] .btn-toolbar { color: #9ca3af; border-color: rgba(148,163,184,.25); }
    body[data-theme="dark"] .btn-toolbar:hover { background: rgba(148,163,184,.15); color: #fff; }
    
    .btn-toolbar.primary { background: var(--shp-accent); color: #fff; border-color: var(--shp-accent); }
    .btn-toolbar.primary:hover { background: var(--shp-accent-2); border-color: var(--shp-accent-2); color: #fff; }

    /* Tabs */
    .ord-tabs {
        display: flex; gap: .5rem; flex-wrap: wrap;
        background: #ffffff;
        padding: .7rem;
        border-radius: 12px;
        margin-bottom: 1rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
        position: sticky; top: 60px; z-index: 30;
    }
    body[data-theme="dark"] .ord-tabs {
        background: rgba(15, 23, 42, 0.4);
        border-color: rgba(255,255,255,0.1);
    }
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
        color: #fff; 
        background: linear-gradient(135deg, #2563eb, #1d4ed8); 
        border-color: #1e40af; 
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    }
    .ord-tab.active .ord-badge { background: rgba(255,255,255,0.25) !important; color: #fff !important; border-color: rgba(255,255,255,0.1) !important; box-shadow: none !important; }
    .ord-badge.urgent { background: #fef2f2; color: #dc2626; border: none; }
    .ord-tab.active .ord-badge.urgent { background: #dc2626; color: #fff; border: none; }
    
    @media (max-width: 640px) {
        .ord-tabs { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; top: 60px; }
        .ord-tabs::-webkit-scrollbar { display: none; }
    }

    /* Table */
    .ord-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .ord-table thead tr th {
        font-size: .68rem; font-weight: 600; letter-spacing: 0;
        color: #64748b; text-transform: none; padding: .52rem .62rem;
        border-bottom: 1px solid var(--shp-border); background: var(--card,#fff); white-space: nowrap;
        position: sticky; top: 0; z-index: 10; text-align: left;
    }
    body[data-theme="dark"] .ord-table thead tr th { background: rgba(15,23,42,0.98); color: #9ca3af; border-bottom-color: rgba(51, 65, 85, 0.85); }
    .ord-table tbody tr { transition: all .2s ease; }
    .ord-table tbody tr:hover td { background: #f8fafc; box-shadow: inset 0 2px 4px -2px rgba(0,0,0,0.03); }
    body[data-theme="dark"] .ord-table tbody tr:hover td { background: rgba(255,255,255,0.02); }
    .ord-table tbody tr td {
        padding: .7rem .62rem; border-top: 1px solid rgba(148, 163, 184, 0.16); border-bottom: 0;
        vertical-align: top; font-size: .78rem;
    }
    .ord-table tbody tr:first-child td { border-top: none; }
    body[data-theme="dark"] .ord-table tbody tr td { border-top-color: rgba(51, 65, 85, 0.85); color: #f8fafc; }

    .ord-badge {
        font-size: .63rem; font-weight: 800; padding: .1rem .35rem;
        border-radius: 999px; background: #e2e8f0; color: #475569;
        min-width: 17px; text-align: center; line-height: 1.4;
    }
    .ord-id { font-size: .75rem; font-weight: 800; color: #0f172a; font-family: 'SF Mono', 'Menlo', monospace; letter-spacing: -.01em; word-break: break-all; }
    body[data-theme="dark"] .ord-id { color: #f8fafc; }
    .ord-date { font-size: .68rem; color: #94a3b8; margin-top: .15rem; }
    .ord-store { display: inline-flex; align-items: center; gap: .25rem; font-size: .72rem; font-weight: 600; color: #475569; background: #f1f5f9; padding: .15rem .45rem; border-radius: 6px; }
    body[data-theme="dark"] .ord-store { color: #cbd5e1; background: rgba(255,255,255,0.1); }
    
    .ord-empty { text-align: center; padding: 4rem 1rem; color: #64748b; font-size: .95rem; font-weight: 500; }
    .ord-empty-icon { font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.7; }

    .fstatus { display:inline-block; font-size:.68rem; font-weight:700; padding:.1rem .45rem; border-radius:999px; vertical-align:middle; }
    .fstatus-none    { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
    .fstatus-draft   { background:#fefce8; color:#a16207; border:1px solid #fde68a; }
    .fstatus-pending { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
    .fstatus-done    { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }

    /* Inline Items */
    .ord-items-cell { display: flex; flex-direction: column; gap: 6px; padding: 6px; background: #f8fafc; border-radius: 8px; border: 1px solid rgba(148,163,184,.15); }
    body[data-theme="dark"] .ord-items-cell { background: rgba(0,0,0,0.15); border-color: rgba(255,255,255,0.05); }
    .ord-item-card { display: flex; align-items: flex-start; gap: 8px; font-size: .75rem; padding: 4px; }
    .ord-item-qty { font-weight: 800; color: var(--shp-accent); background: rgba(148,163,184,.1); padding: 1px 5px; border-radius: 4px; font-size: .7rem; }
    body[data-theme="dark"] .ord-item-qty { color: #94a3b8; background: rgba(255,255,255,0.1); }
    .ord-item-body { flex: 1; min-width: 0; }
    .ord-item-name { font-weight: 600; color: #1e293b; line-height: 1.3; margin-bottom: 2px; }
    body[data-theme="dark"] .ord-item-name { color: #f1f5f9; }
    .ord-item-variant { font-size: .68rem; color: #64748b; }

    /* Forms & Modal */
    .modal-content { border-radius: 12px; border: none; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
    body[data-theme="dark"] .modal-content { background: var(--card,#0f172a); border: 1px solid rgba(255,255,255,0.1); }
    .form-control-custom { border-radius: 7px; border: 1px solid rgba(148,163,184,.35); padding: 0.4rem 0.75rem; font-size: 0.78rem; transition: border-color .15s; background: transparent; }
    .form-control-custom:focus { border-color: var(--shp-accent); outline: none; }
    body[data-theme="dark"] .form-control-custom { border-color: rgba(148,163,184,.25); color: #f8fafc; }
</style>
@endpush

@section('content')
<div class="page-wrap">
    {{-- ── TOPBAR ── --}}
    <div class="ship-topbar">
        <h1 class="title">
            ⚡ Pesanan Kilat 
            <span class="ord-badge" style="background:#fefce8;color:#a16207;border:1px solid #fde68a;">Booking Shopee</span>
        </h1>
        <div class="controls">
            <div class="ord-search-bar">
                <span style="opacity:0.5; font-size:0.9em;">🔍</span>
                <input type="text" id="searchInput" placeholder="Cari SN / Pesanan / Resi..." autocomplete="off">
            </div>
            <button class="btn-ship-outline" id="btnRefresh" title="Segarkan Data" style="padding: 0.32rem 0.6rem;">🔃</button>
            <button class="btn-ship-primary" id="btnSync">🔄 <span class="mobile-hide">Sync Shopee</span></button>
        </div>
    </div>

    {{-- TABS --}}
    <div class="ord-tabs" id="ordTabs">
        <button class="ord-tab active" data-tab="all" onclick="switchTab('all', this)">
            Semua <span class="ord-badge" id="badge-all">—</span>
        </button>
        <button class="ord-tab" data-tab="ready" onclick="switchTab('ready', this)">
            Perlu Kirim <span class="ord-badge urgent" id="badge-ready">—</span>
        </button>
        <button class="ord-tab" data-tab="processed" onclick="switchTab('processed', this)">
            Sedang Proses <span class="ord-badge" id="badge-processed" style="background:#eff6ff;color:#2563eb;border-color:#bfdbfe">—</span>
        </button>
        <button class="ord-tab" data-tab="shipped" onclick="switchTab('shipped', this)">
            Dikirim / Selesai <span class="ord-badge" id="badge-shipped">—</span>
        </button>
        <button class="ord-tab" data-tab="cancelled" onclick="switchTab('cancelled', this)">
            Dibatalkan <span class="ord-badge" id="badge-cancelled" style="background:#fef2f2;color:#dc2626;border-color:#fecaca">—</span>
        </button>
    </div>

    <div class="card-main">
        <div class="table-responsive">
            <table class="ord-table">
                <thead>
                    <tr>
                        <th>Toko</th>
                        <th>Booking / Pesanan</th>
                        <th>Status</th>
                        <th>Kurir</th>
                        <th>Resi</th>
                        <th>Dibuat</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="kiltBody">
                    <tr>
                        <td colspan="7">
                            <div class="ord-empty">
                                <div class="spinner-border text-primary mb-2" style="width:1.5rem; height:1.5rem; border-width:2px;" role="status"></div>
                                <div>Memuat Data...</div>
                                <div style="font-size:0.8rem; margin-top:4px">Sedang mengambil informasi pesanan kilat.</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail Booking -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="padding: 1.25rem 1.4rem .9rem; border-bottom: 1.5px solid #f1f5f9;">
                <h5 class="modal-title fw-bold" style="font-size: 1.05rem; color: #0f172a;">📦 Detail Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 0; background: #f8fafc;">
                <div id="detailLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-2" style="width:1.5rem; height:1.5rem; border-width:2px;" role="status"></div>
                    <div style="font-size:0.85rem; font-weight:600; color:#475569;">Memuat Detail...</div>
                </div>
                <div id="detailError" style="display:none; padding: 2rem; text-align: center; color: #dc2626;"></div>
                
                <div id="detailContent" style="display:none;">
                    <!-- Info Bar -->
                    <div style="background: #fff; padding: 1rem 1.4rem; border-bottom: 1px solid #e2e8f0; display:flex; flex-wrap:wrap; gap: 1.5rem;">
                        <div>
                            <div style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase;">No. Pesanan / Booking</div>
                            <div id="detSn" class="ord-id" style="font-size: 1rem; margin-top: .15rem;"></div>
                        </div>
                        <div>
                            <div style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase;">Kurir</div>
                            <div id="detCourier" style="font-weight: 700; color: #0f172a; margin-top: .15rem; font-size: .85rem;"></div>
                        </div>
                        <div>
                            <div style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase;">No. Resi / Pelacakan</div>
                            <div id="detTracking" style="font-family: monospace; font-weight: 700; color: var(--shp-accent); margin-top: .15rem; font-size: .95rem;"></div>
                        </div>
                        <div>
                            <div style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase;">Status Kurir (Logistik)</div>
                            <div id="detCourierStatus" style="font-weight: 700; margin-top: .15rem; font-size: .85rem;"></div>
                        </div>
                    </div>
                    
                    <!-- Address & Dropshipper -->
                    <div style="background: #fff; padding: 1rem 1.4rem; border-bottom: 1px solid #e2e8f0; display:flex; flex-wrap:wrap; gap: 1.5rem;">
                        <div style="flex: 1; min-width: 250px;">
                            <div style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase;">Alamat Penerima</div>
                            <div id="detAddress" style="font-size: .85rem; color: #334155; margin-top: .4rem; line-height: 1.4;"></div>
                        </div>
                        <div id="detCancelBox" style="display:none; flex: 1; min-width: 250px;">
                            <div style="font-size: .65rem; font-weight: 800; color: #ef4444; letter-spacing: .07em; text-transform: uppercase;">Info Pembatalan</div>
                            <div style="font-size: .85rem; color: #dc2626; margin-top: .4rem; line-height: 1.4;">
                                Dibatalkan oleh: <span id="detCancelBy" style="font-weight:700"></span><br>
                                Alasan: <span id="detCancelReason" style="font-weight:700"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items -->
                    <div style="padding: 1.4rem;">
                        <h6 style="font-size: .85rem; font-weight: 700; color: #1e293b; margin-bottom: 1rem;">Daftar Produk</h6>
                        <div class="table-responsive" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <table class="ord-table" style="margin:0;">
                                <thead>
                                    <tr>
                                        <th>Nama Produk</th>
                                        <th style="text-align:center; width:80px">Qty</th>
                                        <th style="text-align:right">Harga</th>
                                    </tr>
                                </thead>
                                <tbody id="detItems"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: .9rem 1.4rem; border-top: 1.5px solid #f1f5f9; background: #fff;">
                <button type="button" class="btn-ship-primary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Atur Pengiriman Kilat -->
<div class="modal fade" id="shipModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="padding: 1.25rem 1.4rem .9rem; border-bottom: 1.5px solid #f1f5f9;">
                <h5 class="modal-title fw-bold" style="font-size: 1.05rem; color: #0f172a;">🚚 Atur Pengiriman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1.25rem 1.4rem;">
                <div style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase; margin-bottom: .25rem;">Booking SN</div>
                <div id="shipBookingSn" class="ord-id mb-4" style="font-size:1.1rem"></div>

                <div id="shipLoading" class="text-center py-4">
                    <div class="spinner-border text-primary mb-2" style="width:1.5rem; height:1.5rem; border-width:2px;" role="status"></div>
                    <div style="font-size:0.85rem; font-weight:600;">Mengambil Opsi...</div>
                </div>

                <div id="shipForm" style="display:none">
                    <div class="mb-3 text-center" id="shipMethodInfo"></div>

                    <div class="mb-3" id="pickupAddrWrap" style="display:none">
                        <label style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase; display:block; margin-bottom:.35rem;">Alamat Pickup</label>
                        <select id="pickupAddr" class="form-select form-control-custom w-100"></select>
                    </div>
                    <div class="mb-3" id="pickupTimeWrap" style="display:none">
                        <label style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase; display:block; margin-bottom:.35rem;">Jadwal Pickup</label>
                        <select id="pickupTime" class="form-select form-control-custom w-100"></select>
                    </div>
                    <div class="mb-3" id="dropoffWrap" style="display:none">
                        <label style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase; display:block; margin-bottom:.35rem;">Titik Dropoff</label>
                        <select id="dropoffBranch" class="form-select form-control-custom w-100"></select>
                    </div>
                    <div id="shipNoParam" style="display:none; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:.75rem 1rem; margin-bottom:1rem; align-items:flex-start; gap:.75rem;">
                        <div style="font-size:1.2rem">ℹ️</div>
                        <div>
                            <div style="font-size:.85rem; font-weight:800; color:#1e40af; margin-bottom:.15rem">Kurir Instan/Otomatis</div>
                            <div style="font-size:.78rem; color:#1d4ed8; line-height:1.4">Kurir ini tidak membutuhkan opsi pickup/dropoff. Klik Kirim untuk memproses.</div>
                        </div>
                    </div>
                </div>

                <div id="shipError" style="display:none; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:.75rem 1rem; align-items:flex-start; gap:.75rem;">
                    <div style="font-size:1.2rem">⚠️</div>
                    <div>
                        <div style="font-size:.85rem; font-weight:800; color:#991b1b; margin-bottom:.15rem">Terjadi Kesalahan</div>
                        <div style="font-size:.78rem; color:#b91c1c; line-height:1.4" id="shipErrorText"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: .9rem 1.4rem; border-top: 1.5px solid #f1f5f9;">
                <button type="button" class="btn-ship-outline" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn-ship-primary" id="shipSubmit" disabled>
                    ✔️ Kirim Sekarang
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const api = async (url, options = {}) => {
        options.headers = Object.assign({ 'Accept': 'application/json' }, options.headers || {});
        if (options.method && options.method !== 'GET') options.headers['X-CSRF-TOKEN'] = token;
        const res = await fetch(url, options);
        let data; try { data = await res.json(); } catch(e) {}
        if (!res.ok) throw new Error(data?.message || data?.error || res.statusText);
        return data;
    };

    const tbody = document.getElementById('kiltBody');
    const btnRefresh = document.getElementById('btnRefresh');
    const btnSync = document.getElementById('btnSync');
    const searchInput = document.getElementById('searchInput');
    let bookings = [];
    let loading = false;
    let currentTab = 'all';

    function fmtDate(ts){
        if(!ts) return '—';
        return new Date(ts*1000).toLocaleString('id-ID', {day:'numeric',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
    }

    function statusBadge(s){
        s = (s||'').toUpperCase();
        if(['SHIPPED','COMPLETED','PROCESSED'].includes(s)) return `<span class="fstatus fstatus-done">${s||'—'}</span>`;
        if(['CANCELLED','FAILED'].includes(s)) return `<span class="fstatus fstatus-none">${s}</span>`;
        if(!s) return `<span class="fstatus fstatus-none">—</span>`;
        if(s === 'PROCESSED_INSTANT') return `<span class="fstatus fstatus-draft">${s}</span>`;
        return `<span class="fstatus fstatus-pending">${s}</span>`;
    }

    function renderEmpty(filteredCount = 0) {
        if(filteredCount === 0 && bookings.length > 0) {
            return `<tr>
                <td colspan="7">
                    <div class="ord-empty">
                        <div class="ord-empty-icon">📂</div>
                        <div>Tidak ada pesanan di tab ini.</div>
                    </div>
                </td>
            </tr>`;
        }
        return `<tr>
            <td colspan="7">
                <div class="ord-empty">
                    <div class="ord-empty-icon">📭</div>
                    <div>Belum ada pesanan kilat</div>
                    <div style="font-size:0.8rem; margin-top:4px">Tekan "Sync Shopee" untuk menarik data.</div>
                </div>
            </td>
        </tr>`;
    }
    
    function renderLoading(msg, sub) {
        return `<tr>
            <td colspan="7">
                <div class="ord-empty">
                    <div class="spinner-border text-primary mb-2" style="width:1.5rem; height:1.5rem; border-width:2px;" role="status"></div>
                    <div style="font-weight:600; color:#334155;">${msg}</div>
                    <div style="font-size:0.8rem; margin-top:4px">${sub}</div>
                </div>
            </td>
        </tr>`;
    }

    window.switchTab = function(tabName, el) {
        document.querySelectorAll('.ord-tab').forEach(t => t.classList.remove('active'));
        if(el) el.classList.add('active');
        currentTab = tabName;
        render();
    };

    function filterBookings(arr) {
        if (currentTab === 'all') return arr;
        return arr.filter(b => {
            const s = (b.booking_status||'').toUpperCase();
            if (currentTab === 'ready') return b.needs_shipping === true;
            if (currentTab === 'processed') return s === 'PROCESSED' || s === 'PROCESSED_INSTANT';
            if (currentTab === 'shipped') return s === 'SHIPPED' || s === 'COMPLETED';
            if (currentTab === 'cancelled') return s === 'CANCELLED' || s === 'FAILED';
            return true;
        });
    }

    function updateBadges() {
        let cntReady = 0, cntProcessed = 0, cntShipped = 0, cntCancelled = 0;
        bookings.forEach(b => {
            const s = (b.booking_status||'').toUpperCase();
            if (b.needs_shipping === true) cntReady++;
            if (s === 'PROCESSED' || s === 'PROCESSED_INSTANT') cntProcessed++;
            if (s === 'SHIPPED' || s === 'COMPLETED') cntShipped++;
            if (s === 'CANCELLED' || s === 'FAILED') cntCancelled++;
        });
        document.getElementById('badge-all').textContent = bookings.length;
        document.getElementById('badge-ready').textContent = cntReady;
        document.getElementById('badge-processed').textContent = cntProcessed;
        document.getElementById('badge-shipped').textContent = cntShipped;
        document.getElementById('badge-cancelled').textContent = cntCancelled;
        
        const elTotal = document.getElementById('kpiTotal');
        const elNeedShip = document.getElementById('kpiNeedShip');
        if (elTotal) elTotal.textContent = bookings.length;
        if (elNeedShip) elNeedShip.textContent = cntReady;
    }

    function render(){
        updateBadges();
        
        const filtered = filterBookings(bookings);

        if(filtered.length === 0){
            tbody.innerHTML = renderEmpty(filtered.length);
            return;
        }
        tbody.innerHTML = '';
        filtered.forEach(b => {
            const store = b.store_name || ('Toko #' + b.store_id);
            let aksi = `<button class="btn-toolbar" onclick="showDetail('${b.store_id}','${b.booking_sn}')">ℹ️ Detail</button>`;
            if(b.needs_shipping){
                aksi += `<button class="btn-toolbar primary" onclick="arrangeShip('${b.store_id}','${b.booking_sn}')">🚚 Atur Kirim</button>`;
            }
            
            let metaStatusHtml = '';
            if (b.meta) {
                if (b.meta.courier_status) {
                    const cStat = b.meta.courier_status.replace(/_/g, ' ');
                    metaStatusHtml += `<div style="font-size:0.65rem; margin-top:4px; font-weight:700; color:#475569; padding:2px 6px; background:#f1f5f9; border-radius:4px; display:inline-block">🚚 ${cStat}</div>`;
                }
                if (b.meta.booking_shipping_document_status) {
                    const dStat = b.meta.booking_shipping_document_status;
                    const color = dStat === 'READY' ? '#15803d' : '#b91c1c';
                    const bg = dStat === 'READY' ? '#dcfce7' : '#fef2f2';
                    metaStatusHtml += `<div style="font-size:0.65rem; margin-top:4px; font-weight:700; color:${color}; padding:2px 6px; background:${bg}; border-radius:4px; display:inline-block; margin-left:4px">📄 Doc: ${dStat}</div>`;
                }
            }

            let itemsHtml = '';
            if (b.items && b.items.length > 0) {
                const lines = b.items.map(it => `
                    <div class="ord-item-card">
                        <div class="ord-item-qty">${it.model_quantity_purchased || it.quantity || 1}×</div>
                        <div class="ord-item-body">
                            <div class="ord-item-name">${(it.item_name || it.name || '—')}</div>
                            ${it.model_name ? `<div class="ord-item-variant">${it.model_name}</div>` : ''}
                            ${(it.item_sku || it.sku) ? `<div class="ord-item-variant" style="color:#94a3b8; font-family:monospace">SKU: ${it.item_sku || it.sku}</div>` : ''}
                        </div>
                    </div>
                `).join('');
                itemsHtml = `<div class="ord-items-cell" style="margin-top:8px">${lines}</div>`;
            }
            
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><span class="ord-store">🏪 ${store}</span></td>
                <td>
                    <div class="ord-id">${b.booking_sn}</div>
                    ${(b.order_sn && b.order_sn !== b.booking_sn) ? `<div class="ord-date">📦 ${b.order_sn}</div>` : ''}
                    ${itemsHtml}
                </td>
                <td>
                    ${statusBadge(b.booking_status)}
                    <div>${metaStatusHtml}</div>
                </td>
                <td style="font-size:0.75rem;font-weight:600;color:var(--shp-accent)">${b.shipping_carrier || '—'}</td>
                <td style="font-size:0.75rem;font-family:monospace">${b.tracking_number || '—'}</td>
                <td class="ord-date" style="margin-top:0">${fmtDate(b.create_time)}</td>
                <td style="text-align:right">
                    <div style="display:flex; flex-direction:column; gap:4px; align-items:flex-end">
                        ${aksi}
                    </div>
                </td>`;
            tbody.appendChild(tr);
        });
    }

    async function load(){
        if(loading) return;
        loading = true; btnRefresh.disabled = true; btnSync.disabled = true;
        
        if (bookings.length === 0) {
            tbody.innerHTML = renderLoading("Memuat Data...", "Sedang mengambil informasi pesanan kilat.");
        }
        
        try{
            const params = new URLSearchParams();
            if(searchInput.value.trim()) params.set('search', searchInput.value.trim());
            const res = await api('/api/marketplace/bookings/stored?' + params.toString());
            bookings = (res && res.data) ? res.data : [];
            render();
        }catch(e){
            tbody.innerHTML = `<tr>
                <td colspan="7">
                    <div class="ord-empty" style="color:#dc2626">
                        <div class="ord-empty-icon">⚠️</div>
                        <div style="font-weight:600">Gagal memuat data</div>
                        <div style="font-size:0.8rem; margin-top:4px">${e.message}</div>
                    </div>
                </td>
            </tr>`;
        }finally{
            loading = false; btnRefresh.disabled = false; btnSync.disabled = false;
        }
    }

    btnRefresh.addEventListener('click', load);
    btnSync.addEventListener('click', async () => {
        btnSync.disabled = true;
        tbody.innerHTML = renderLoading("Sinkronisasi Shopee...", "Menarik data booking terbaru, mohon bersabar.");
        try{
            await api('/api/marketplace/bookings/sync-all?full=1', { method: 'POST' });
            await load();
        }catch(e){ 
            alert('Gagal sinkron: ' + e.message); 
            btnSync.disabled = false; 
            if(bookings.length > 0) render(); else tbody.innerHTML = renderEmpty();
        }
    });

    let t = null;
    searchInput.addEventListener('keyup', () => { clearTimeout(t); t = setTimeout(load, 400); });

    const detailModalEl = document.getElementById('detailModal');
    
    window.showDetail = async (storeId, sn) => {
        document.getElementById('detailLoading').style.display = 'block';
        document.getElementById('detailContent').style.display = 'none';
        document.getElementById('detailError').style.display = 'none';
        
        bootstrap.Modal.getOrCreateInstance(detailModalEl).show();
        
        try {
            const d = await api(`/api/marketplace/stores/${storeId}/bookings/${sn}/detail`);
            const info = d.order_list ? d.order_list[0] : d; // Handle structure
            
            document.getElementById('detailLoading').style.display = 'none';
            document.getElementById('detailContent').style.display = 'block';
            
            // Ambil data lokal sebagai fallback jika API Shopee (getBookingDetail) tidak mereturn field tersebut
            const localBooking = bookings.find(b => b.booking_sn === sn || b.order_sn === sn);
            
            const carrier = info.shipping_carrier || (localBooking ? localBooking.shipping_carrier : null) || '—';
            const tracking = info.tracking_no || info.tracking_number || (localBooking ? localBooking.tracking_number : null);

            document.getElementById('detSn').textContent = info.order_sn || (localBooking ? localBooking.order_sn : sn) || sn;
            document.getElementById('detCourier').textContent = carrier;
            document.getElementById('detTracking').innerHTML = tracking 
                ? `${tracking} <a href="https://shopee.co.id/search?keyword=${tracking}" target="_blank" style="text-decoration:none; margin-left:4px;" title="Lacak di Shopee">🔎</a>`
                : '<em>Belum Ada Resi</em>';
            
            // Tampilkan meta logs apabila pesanan sudah diproses webhook
            const courierStatusEl = document.getElementById('detCourierStatus');
            if (localBooking && localBooking.meta && localBooking.meta.courier_status) {
                const cStat = localBooking.meta.courier_status.replace(/_/g, ' ');
                courierStatusEl.innerHTML = `<span style="color:#2563eb; background:#eff6ff; padding:2px 6px; border-radius:4px;">🚚 ${cStat}</span>`;
            } else {
                courierStatusEl.innerHTML = '<span style="color:#64748b">—</span>';
            }
            
            const tbody = document.getElementById('detItems');
            tbody.innerHTML = '';
            
            // Address & Cancellation info
            const addr = info.recipient_address;
            let addrHtml = '<em>Tidak ada data alamat</em>';
            if (addr) {
                addrHtml = `<strong>${addr.name || ''}</strong> ${addr.phone ? '('+addr.phone+')' : ''}<br>`;
                addrHtml += `${addr.full_address || ''}`;
            }
            if (info.dropshipper && info.dropshipper !== '-') {
                addrHtml += `<div style="margin-top:8px; padding-top:8px; border-top:1px dashed #cbd5e1; color:#0f172a">
                    <strong>Dropshipper:</strong> ${info.dropshipper} ${info.dropshipper_phone ? '('+info.dropshipper_phone+')' : ''}
                </div>`;
            }
            document.getElementById('detAddress').innerHTML = addrHtml;

            const cancelBox = document.getElementById('detCancelBox');
            if (info.booking_status === 'CANCELLED' || info.cancel_by) {
                cancelBox.style.display = 'block';
                document.getElementById('detCancelBy').textContent = info.cancel_by || 'Unknown';
                document.getElementById('detCancelReason').textContent = info.cancel_reason || '—';
            } else {
                cancelBox.style.display = 'none';
            }
            
            // Item list fallback to local DB items if Shopee API doesn't provide it
            const itemsToRender = (info.item_list && info.item_list.length > 0) ? info.item_list : (localBooking ? localBooking.items : []);
            
            if (itemsToRender && itemsToRender.length > 0) {
                itemsToRender.forEach(item => {
                    const price = item.model_discounted_price || item.model_original_price || item.price || 0;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>
                            <div style="font-weight:600; color:#0f172a; margin-bottom:2px;">${item.item_name || item.name || '—'}</div>
                            <div style="font-size:0.7rem; color:#64748b;">${item.model_name || item.variation_name || ''} <span style="margin-left:5px; font-family:monospace; color:#94a3b8">${item.item_sku || item.sku || ''}</span></div>
                        </td>
                        <td style="text-align:center; font-weight:700;">${item.model_quantity_purchased || 1}</td>
                        <td style="text-align:right; font-weight:600; color:#1e293b;">Rp ${parseInt(price).toLocaleString('id-ID')}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="3" class="text-center" style="padding:2rem; color:#64748b;">Informasi item tidak tersedia untuk pesanan ini.</td></tr>`;
            }
            
        } catch (e) {
            document.getElementById('detailLoading').style.display = 'none';
            const errEl = document.getElementById('detailError');
            errEl.style.display = 'block';
            errEl.innerHTML = `<strong>Gagal mengambil detail pesanan.</strong><br><span style="font-size:0.85rem">${e.message}</span>`;
        }
    };

    // ── Atur Kirim (pilih pickup / dropoff) ──────────────────────────────────
    let shipCtx = { storeId: null, sn: null, method: null };
    const shipModalEl = document.getElementById('shipModal');
    const el = id => document.getElementById(id);

    function resetShipModal(){
        el('shipLoading').style.display = 'block';
        el('shipForm').style.display = 'none';
        el('shipError').style.display = 'none';
        el('pickupAddrWrap').style.display = 'none';
        el('pickupTimeWrap').style.display = 'none';
        el('dropoffWrap').style.display = 'none';
        el('shipNoParam').style.display = 'none';
        el('shipSubmit').disabled = true;
        el('pickupAddr').innerHTML = '';
        el('pickupTime').innerHTML = '';
        el('dropoffBranch').innerHTML = '';
        el('shipMethodInfo').innerHTML = '';
    }

    window.arrangeShip = async (storeId, sn) => {
        shipCtx = { storeId, sn, method: null };
        el('shipBookingSn').textContent = sn;
        resetShipModal();
        bootstrap.Modal.getOrCreateInstance(shipModalEl).show();

        try {
            const p = await api(`/api/marketplace/stores/${storeId}/bookings/${sn}/shipping-parameter`);
            const info    = p.info_needed || {};
            const pickup  = p.pickup || {};
            const dropoff = p.dropoff || {};
            const addrList   = pickup.address_list || [];
            const branchList = dropoff.branch_list || [];

            el('shipLoading').style.display = 'none';
            el('shipForm').style.display = 'block';

            // Pilih metode: utamakan pickup bila ada alamat, jika tidak pakai dropoff.
            if (addrList.length && (info.pickup !== undefined || !branchList.length)) {
                shipCtx.method = 'pickup';
                el('shipMethodInfo').innerHTML = '<span class="fstatus fstatus-pending" style="font-size:0.75rem;">📦 Pickup (Dijemput Kurir)</span>';
                el('pickupAddrWrap').style.display = 'block';
                addrList.forEach((a, i) => {
                    const label = [a.address, a.city, a.state, a.zipcode].filter(Boolean).join(', ');
                    el('pickupAddr').insertAdjacentHTML('beforeend', `<option value="${a.address_id}" data-idx="${i}">${label || ('Alamat #'+a.address_id)}</option>`);
                });
                fillPickupTimes(addrList, 0);
                el('pickupAddr').onchange = e => fillPickupTimes(addrList, e.target.selectedOptions[0].dataset.idx);
                el('shipSubmit').disabled = false;
            } else if (branchList.length) {
                shipCtx.method = 'dropoff';
                el('shipMethodInfo').innerHTML = '<span class="fstatus fstatus-warning" style="font-size:0.75rem;">🏪 Dropoff (Antar ke Titik)</span>';
                el('dropoffWrap').style.display = 'block';
                branchList.forEach(b => {
                    const label = [b.address, b.city, b.state, b.zipcode].filter(Boolean).join(', ');
                    el('dropoffBranch').insertAdjacentHTML('beforeend', `<option value="${b.branch_id}">${label || ('Titik #'+b.branch_id)}</option>`);
                });
                el('shipSubmit').disabled = false;
            } else {
                shipCtx.method = 'none';
                el('shipNoParam').style.display = 'flex';
                el('shipSubmit').disabled = false;
            }
        } catch(e) {
            el('shipLoading').style.display = 'none';
            el('shipError').style.display = 'flex';
            el('shipErrorText').textContent = 'Gagal mengambil opsi pengiriman: ' + e.message;
        }
    };

    function fillPickupTimes(addrList, idx){
        const slots = (addrList[idx] && addrList[idx].time_slot_list) || [];
        const sel = el('pickupTime');
        sel.innerHTML = '';
        if (!slots.length) { el('pickupTimeWrap').style.display = 'none'; return; }
        el('pickupTimeWrap').style.display = 'block';
        slots.forEach(s => {
            const d = s.date ? new Date(s.date*1000).toLocaleDateString('id-ID', {weekday:'short',day:'numeric',month:'short'}) : '';
            sel.insertAdjacentHTML('beforeend', `<option value="${s.pickup_time_id}">${[d, s.time_text].filter(Boolean).join(' ')}</option>`);
        });
    }

    el('shipSubmit').addEventListener('click', async () => {
        const btn = el('shipSubmit');
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = 'Memproses...';

        const body = {};
        if (shipCtx.method === 'pickup') {
            body.pickup = { address_id: Number(el('pickupAddr').value) };
            if (el('pickupTimeWrap').style.display !== 'none' && el('pickupTime').value) {
                body.pickup.pickup_time_id = el('pickupTime').value;
            }
        } else if (shipCtx.method === 'dropoff') {
            body.dropoff = { branch_id: Number(el('dropoffBranch').value) };
        }

        try {
            const res = await api(`/api/marketplace/stores/${shipCtx.storeId}/bookings/${shipCtx.sn}/ship`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });
            bootstrap.Modal.getInstance(shipModalEl).hide();
            alert((res.message || 'Berhasil') + (res.tracking_number ? ('\nResi: ' + res.tracking_number) : ''));
            load();
        } catch(e) {
            el('shipError').style.display = 'flex';
            el('shipErrorText').textContent = 'Gagal atur kirim: ' + e.message;
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });

    load();
</script>
@endpush
