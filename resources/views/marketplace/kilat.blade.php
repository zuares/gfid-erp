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

    /* Sub-tabs (selaras halaman Orders) */
    .ord-subtab { display:inline-flex; align-items:center; gap:.3rem; background:transparent; border:none; padding:.3rem .7rem; font-size:.75rem; font-weight:600; color:#64748b; border-radius:6px; cursor:pointer; white-space:nowrap; }
    .ord-subtab:hover { background:#eef2f7; color:#1e293b; }
    .ord-subtab.active { background:#fff; color:#0f172a; box-shadow:0 1px 2px rgba(0,0,0,.08); }
    body[data-theme="dark"] .ord-subtab.active { background:rgba(255,255,255,0.12); color:#fff; }
    .ord-badge.bg-secondary { background:#e2e8f0; color:#475569; border-color:transparent; }
    .awb-track { text-decoration:none; margin-left:4px; }
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

    {{-- Penjelasan singkat agar owner tidak bingung --}}
    <div id="kiltHelp" style="display:flex; align-items:center; gap:.5rem; background:#fffbeb; border:1px solid #fde68a; color:#92400e; border-radius:8px; padding:.5rem .8rem; font-size:.75rem; margin-bottom:.75rem; line-height:1.4;">
        <span style="font-size:1rem">💡</span>
        <span>Pesanan <strong>Kilat</strong> dikelola gudang Shopee. Alur: <strong>Perlu Proses Penjual</strong> → <strong>Dikirim ke DC</strong> → <strong>Dikirim ke Pembeli</strong>. Kolom di bawah menampilkan status tiap pesanan secara ringkas.</span>
    </div>

    {{-- TABS (label mengikuti alur Pesanan Kilat, ramah-owner) --}}
    <div class="ord-tabs" id="ordTabs">
        <button class="ord-tab active" data-tab="all" onclick="switchTab('all', this)" title="Semua pesanan kilat">
            📋 Semua <span class="ord-badge" id="badge-all">—</span>
        </button>
        <button class="ord-tab" data-tab="ready" onclick="switchTab('ready', this)" title="Penjual perlu memproses / menyerahkan barang (READY_TO_SHIP/PROCESSED)">
            📦 Perlu Proses Penjual <span class="ord-badge urgent" id="badge-ready">—</span>
        </button>
        <button class="ord-tab" data-tab="shipped" onclick="switchTab('shipped', this)" title="Barang sudah dikirim ke gudang/DC Shopee (SHIPPED/COMPLETED)">
            🚚 Dikirim ke DC <span class="ord-badge" id="badge-shipped" style="background:#eff6ff;color:#2563eb;border-color:#bfdbfe">—</span>
        </button>
        <button class="ord-tab" data-tab="waiting" onclick="switchTab('waiting', this)" title="Barang di gudang Shopee, dalam perjalanan ke pembeli (MATCHED/PENDING)">
            🏠 Dikirim ke Pembeli <span class="ord-badge" id="badge-waiting" style="background:#f0fdf4;color:#16a34a;border-color:#bbf7d0">—</span>
        </button>
        <button class="ord-tab" data-tab="cancelled" onclick="switchTab('cancelled', this)" title="Dibatalkan / gagal (CANCELLED/FAILED)">
            ✖️ Dibatalkan <span class="ord-badge" id="badge-cancelled" style="background:#fef2f2;color:#dc2626;border-color:#fecaca">—</span>
        </button>
    </div>

    {{-- Sub-tab untuk tab "Perlu Proses Penjual" (tampil hanya saat tab itu aktif) --}}
    <div id="subTabReadyContainer" style="display:none; gap:0.25rem; align-items:center; background:#f8fafc; padding:3px; border-radius:8px; border:1px solid var(--shp-border); margin-bottom:1rem; width:fit-content;">
        <button class="ord-subtab active" data-sub="all" onclick="switchSubTabReady('all', this)">Semua <span class="ord-badge bg-secondary" id="badge-sub-ready-all">—</span></button>
        <button class="ord-subtab" data-sub="to_arrange" onclick="switchSubTabReady('to_arrange', this)">Perlu Diatur <span class="ord-badge bg-secondary urgent" id="badge-sub-ready-arrange">—</span></button>
        <button class="ord-subtab" data-sub="packing" onclick="switchSubTabReady('packing', this)">📦 Sedang Dikemas <span class="ord-badge bg-secondary" id="badge-sub-ready-packing">—</span></button>
        <button class="ord-subtab" data-sub="ready_ship" onclick="switchSubTabReady('ready_ship', this)">Siap Kirim <span class="ord-badge bg-secondary" id="badge-sub-ready-ship">—</span></button>
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
                        <th>No. AWB / Resi</th>
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
                <button type="button" class="btn-ship-outline" id="detTrackBtn" style="display:none">🔎 Lacak Pengiriman</button>
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
                    <label style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase; display:block; margin-bottom:.4rem;">Metode Pengiriman</label>
                    <div class="mb-3" id="shipMethods"></div>

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
<!-- Modal Lacak Pengiriman -->
<div class="modal fade" id="trackModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="padding: 1.25rem 1.4rem .9rem; border-bottom: 1.5px solid #f1f5f9;">
                <h5 class="modal-title fw-bold" style="font-size:1.05rem; color:#0f172a;">🔎 Lacak Pengiriman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.25rem 1.4rem;">
                <div style="font-size:.65rem; font-weight:800; color:#94a3b8; letter-spacing:.07em; text-transform:uppercase; margin-bottom:.2rem;">No. Resi</div>
                <div id="trkNo" class="ord-id" style="font-size:1rem; margin-bottom:1rem;">—</div>
                <div id="trkLoading" class="text-center py-4">
                    <div class="spinner-border text-primary mb-2" style="width:1.5rem;height:1.5rem;border-width:2px;" role="status"></div>
                    <div style="font-size:.85rem; font-weight:600;">Memuat pelacakan...</div>
                </div>
                <div id="trkEmpty" style="display:none; text-align:center; color:#64748b; padding:1.5rem; font-size:.85rem;"></div>
                <div id="trkTimeline"></div>
            </div>
            <div class="modal-footer" style="padding:.9rem 1.4rem; border-top:1.5px solid #f1f5f9;">
                <button type="button" class="btn-ship-outline" data-bs-dismiss="modal">Tutup</button>
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
    let subReady = 'all'; // sub-tab di dalam "Perlu Proses Penjual": all | to_arrange | packing

    function fmtDate(ts){
        if(!ts) return '—';
        return new Date(ts*1000).toLocaleString('id-ID', {day:'numeric',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
    }

    // Label status ramah-owner (bukan kode mentah Shopee).
    const STATUS_LABEL = {
        MATCHED:       ['Dikirim ke Pembeli',   'fstatus-done'],
        PENDING:       ['Dikirim ke Pembeli',   'fstatus-done'],
        READY_TO_SHIP: ['Perlu Proses Penjual', 'fstatus-draft'],
        PROCESSED:     ['Sedang Diproses',      'fstatus-draft'],
        SHIPPED:       ['Dikirim ke DC',        'fstatus-pending'],
        COMPLETED:     ['Selesai',              'fstatus-done'],
        CANCELLED:     ['Dibatalkan',           'fstatus-none'],
        FAILED:        ['Gagal',                'fstatus-none'],
    };

    function statusBadge(s){
        s = (s||'').toUpperCase();
        if(!s) return `<span class="fstatus fstatus-none">—</span>`;
        const [label, cls] = STATUS_LABEL[s] || [s.replace(/_/g,' '), 'fstatus-pending'];
        return `<span class="fstatus ${cls}" title="${s}">${label}</span>`;
    }

    // Satu sumber kebenaran pemetaan status → tab, supaya jumlah antar-tab pasti pas.
    function bucketOf(b){
        const s = (b.booking_status||'').toUpperCase();
        if (s === 'CANCELLED' || s === 'FAILED') return 'cancelled';
        if (s === 'SHIPPED' || s === 'COMPLETED') return 'shipped';
        if (s === 'READY_TO_SHIP' || s === 'PROCESSED' || b.needs_shipping) return 'ready';
        return 'waiting'; // MATCHED, PENDING, atau status lain yang belum diproses
    }

    // Bisa dilacak jika barang sudah bergerak (dikirim ke DC / ke pembeli) atau punya resi.
    function isTrackable(b){
        const s = (b.booking_status||'').toUpperCase();
        return ['SHIPPED','COMPLETED','MATCHED'].includes(s) || !!(b.tracking_number);
    }

    // URL pelacakan resmi kurir berdasarkan pola resi. SPX (Shopee Express) → spx.co.id.
    function courierTrackUrl(resi){
        if(!resi) return null;
        const r = String(resi).toUpperCase();
        if(r.startsWith('SPX')) return `https://spx.co.id/#/track?tracking_number=${encodeURIComponent(resi)}`;
        return null; // kurir lain: belum ada deep-link, cukup tombol salin
    }

    // HTML resi + tombol Lacak (SPX) + Salin, dipakai di modal Lacak & Detail.
    function resiActionsHtml(resi){
        if(!resi) return '<em>Belum Ada Resi</em>';
        const url = courierTrackUrl(resi);
        const track = url ? `<a href="${url}" target="_blank" rel="noopener" class="btn-toolbar" style="margin-left:.5rem">🔎 Lacak di SPX</a>` : '';
        const copy  = `<button class="btn-toolbar" style="margin-left:.35rem" onclick="navigator.clipboard.writeText('${resi}').then(()=>{this.textContent='✅ Tersalin'})">📋 Salin</button>`;
        return `<span style="font-family:monospace; font-weight:700">${resi}</span>${track}${copy}`;
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
        // Sub-tab hanya untuk "Perlu Proses Penjual".
        const sc = document.getElementById('subTabReadyContainer');
        if (sc) sc.style.display = (tabName === 'ready') ? 'inline-flex' : 'none';
        render();
    };

    window.switchSubTabReady = function(sub, el) {
        subReady = sub;
        document.querySelectorAll('#subTabReadyContainer .ord-subtab').forEach(b => b.classList.remove('active'));
        if(el) el.classList.add('active');
        render();
    };

    // Sub-bucket di dalam "Perlu Proses Penjual".
    function readySub(b){
        if (b.fulfillment_status === 'confirmed') return 'ready_ship';               // Siap Kirim
        if (b.needs_shipping) return 'to_arrange';                                  // Perlu Diatur
        if ((b.booking_status||'').toUpperCase() === 'PROCESSED') return 'packing';  // Sedang Dikemas
        return 'to_arrange';
    }

    function filterBookings(arr) {
        if (currentTab === 'all') return arr;
        let out = arr.filter(b => bucketOf(b) === currentTab);
        if (currentTab === 'ready' && subReady !== 'all') {
            out = out.filter(b => readySub(b) === subReady);
        }
        return out;
    }

    function updateBadges() {
        const cnt = { waiting: 0, ready: 0, shipped: 0, cancelled: 0 };
        bookings.forEach(b => { cnt[bucketOf(b)]++; });
        document.getElementById('badge-all').textContent = bookings.length;
        document.getElementById('badge-waiting').textContent = cnt.waiting;
        document.getElementById('badge-ready').textContent = cnt.ready;
        document.getElementById('badge-shipped').textContent = cnt.shipped;
        document.getElementById('badge-cancelled').textContent = cnt.cancelled;

        // Sembunyikan badge "urgent" (merah) di tab Siap Kirim bila memang 0.
        const readyBadge = document.getElementById('badge-ready');
        if (readyBadge) readyBadge.classList.toggle('urgent', cnt.ready > 0);

        // Badge sub-tab "Perlu Proses Penjual".
        let sAll = 0, sArrange = 0, sPacking = 0, sReadyShip = 0;
        bookings.forEach(b => {
            if (bucketOf(b) !== 'ready') return;
            sAll++;
            const sub = readySub(b);
            if (sub === 'packing') sPacking++; 
            else if (sub === 'ready_ship') sReadyShip++;
            else sArrange++;
        });
        const setTxt = (id, v) => { const e = document.getElementById(id); if (e) e.textContent = v; };
        setTxt('badge-sub-ready-all', sAll);
        setTxt('badge-sub-ready-arrange', sArrange);
        setTxt('badge-sub-ready-packing', sPacking);
        setTxt('badge-sub-ready-ship', sReadyShip);
    }

    // Sel AWB ringkas untuk tabel: nomor + ikon lacak SPX bila ada.
    function awbCell(resi){
        if(!resi) return '<span style="color:#cbd5e1">—</span>';
        const url = courierTrackUrl(resi);
        const link = url ? ` <a href="${url}" target="_blank" rel="noopener" class="awb-track" title="Lacak di SPX">🔎</a>` : '';
        return `<span style="font-weight:700">${resi}</span>${link}`;
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
            } else if ((b.booking_status||'').toUpperCase() !== 'CANCELLED') {
                aksi += `<button class="btn-toolbar" onclick="printDocument('${b.store_id}','${b.booking_sn}')">🖨 Cetak Resi</button>`;
            }
            
            if(isTrackable(b)){
                aksi += `<button class="btn-toolbar" onclick="trackShipment('${b.store_id}','${b.booking_sn}')">🔎 Lacak</button>`;
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
                <td style="font-size:0.75rem;font-family:monospace">${awbCell(b.tracking_number)}</td>
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
            
            // Resi & kurir bisa berada di package_list (get_order_detail) — bukan hanya top-level.
            const pkg = (info.package_list && info.package_list[0]) ? info.package_list[0] : {};
            const carrier = info.shipping_carrier || pkg.shipping_carrier || (localBooking ? localBooking.shipping_carrier : null) || '—';
            let tracking = info.tracking_no || info.tracking_number || pkg.tracking_number
                || (localBooking ? localBooking.tracking_number : null);
            // OFG… = package_number (nomor paket internal Shopee), BUKAN resi kurir. Jangan tampilkan sebagai resi.
            const packageNo = pkg.package_number || (tracking && String(tracking).toUpperCase().startsWith('OFG') ? tracking : null);
            if (tracking && String(tracking).toUpperCase().startsWith('OFG')) tracking = null;

            document.getElementById('detSn').textContent = info.order_sn || (localBooking ? localBooking.order_sn : sn) || sn;
            document.getElementById('detCourier').textContent = carrier;
            document.getElementById('detTracking').innerHTML = tracking
                ? resiActionsHtml(tracking)
                : (packageNo
                    ? `<em style="color:#94a3b8">Belum ada resi kurir</em><div style="font-size:.7rem; color:#94a3b8; margin-top:2px; font-family:monospace">Paket: ${packageNo}</div>`
                    : '<em>Belum Ada Resi</em>');

            // Tombol Lacak di modal detail — tampil bila pesanan sudah bisa dilacak.
            const detTrackBtn = document.getElementById('detTrackBtn');
            const canTrack = (localBooking && isTrackable(localBooking)) || !!tracking;
            if (canTrack) {
                detTrackBtn.style.display = '';
                detTrackBtn.onclick = () => { bootstrap.Modal.getInstance(detailModalEl)?.hide(); trackShipment(storeId, sn); };
            } else {
                detTrackBtn.style.display = 'none';
            }
            
            // Tampilkan meta logs apabila pesanan sudah diproses webhook
            const courierStatusEl = document.getElementById('detCourierStatus');
            const courierStatus = (localBooking && localBooking.meta && localBooking.meta.courier_status)
                || pkg.logistics_status || info.order_status || (localBooking ? localBooking.booking_status : null);
            if (courierStatus) {
                const cStat = String(courierStatus).replace(/_/g, ' ');
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

    let shipAddrList = [];

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
        el('shipMethods').innerHTML = '';
        shipAddrList = [];
    }

    // Tampilkan/sembunyikan bagian sesuai metode yang dipilih user (radio).
    function applyShipMethod(method){
        shipCtx.method = method;
        el('pickupAddrWrap').style.display = (method === 'pickup') ? 'block' : 'none';
        el('pickupTimeWrap').style.display = (method === 'pickup' && el('pickupTime').options.length) ? 'block' : 'none';
        el('dropoffWrap').style.display  = (method === 'dropoff') ? 'block' : 'none';
        el('shipNoParam').style.display  = (method === 'none') ? 'flex' : 'none';
    }

    window.arrangeShip = async (storeId, sn) => {
        shipCtx = { storeId, sn, method: null };
        el('shipBookingSn').textContent = sn;
        resetShipModal();
        bootstrap.Modal.getOrCreateInstance(shipModalEl).show();

        try {
            const p  = await api(`/api/marketplace/stores/${storeId}/bookings/${sn}/shipping-parameter`);
            const rd = p.response || p;
            const info = rd.info_needed || {};

            // Tangani dua kemungkinan bentuk respons Shopee:
            // (a) data di bawah info_needed.pickup/dropoff, atau (b) di top-level pickup/dropoff.
            const pickupData  = (info.pickup && info.pickup.address_list) ? info.pickup : (rd.pickup || null);
            const dropoffData = (info.dropoff && info.dropoff.branch_list) ? info.dropoff : (rd.dropoff || null);
            const addrList   = (pickupData && pickupData.address_list) ? pickupData.address_list : [];
            const branchList = (dropoffData && dropoffData.branch_list) ? dropoffData.branch_list : [];
            const hasPickup  = ('pickup' in info) || addrList.length > 0;
            const hasDropoff = ('dropoff' in info) || branchList.length > 0;

            shipAddrList = addrList;
            el('shipLoading').style.display = 'none';
            el('shipForm').style.display = 'block';

            // Isi dropdown pickup (alamat + jadwal) & dropoff.
            let defaultAddrIdx = 0;
            addrList.forEach((a, i) => {
                const label = [a.address, a.city, a.state, a.zipcode].filter(Boolean).join(', ');
                let isDefault = (a.address_flag && a.address_flag.includes('default_address')) ? 'selected' : '';
                if (isDefault) defaultAddrIdx = i;
                el('pickupAddr').insertAdjacentHTML('beforeend', `<option value="${a.address_id}" data-idx="${i}" ${isDefault}>${label || ('Alamat #'+a.address_id)}</option>`);
            });
            if (addrList.length) {
                fillPickupTimes(addrList, defaultAddrIdx);
                el('pickupAddr').onchange = e => { fillPickupTimes(addrList, e.target.selectedOptions[0].dataset.idx); applyShipMethod('pickup'); };
            }
            branchList.forEach(b => {
                const label = [b.address, b.city, b.state, b.zipcode].filter(Boolean).join(', ');
                el('dropoffBranch').insertAdjacentHTML('beforeend', `<option value="${b.branch_id}">${label || ('Titik #'+b.branch_id)}</option>`);
            });

            // Bangun radio metode (Pickup diutamakan jika ada alamat pickup).
            let radios = '';
            const shouldDefaultToPickup = hasPickup && addrList.length > 0;
            
            if (hasDropoff) {
                radios += `<label style="display:flex; align-items:center; gap:.5rem; padding:.5rem .7rem; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:.4rem; cursor:pointer;">
                    <input type="radio" name="shipMethod" value="dropoff" ${!shouldDefaultToPickup ? 'checked' : ''}>
                    <span><strong>🏪 Drop-off</strong> — antar ke titik/cabang</span></label>`;
            }
            if (hasPickup) {
                radios += `<label style="display:flex; align-items:center; gap:.5rem; padding:.5rem .7rem; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:.4rem; cursor:pointer;">
                    <input type="radio" name="shipMethod" value="pickup" ${shouldDefaultToPickup ? 'checked' : ''}>
                    <span><strong>📦 Pickup</strong> — dijemput kurir</span></label>`;
            }
            el('shipMethods').innerHTML = radios;

            document.querySelectorAll('input[name="shipMethod"]').forEach(r => {
                r.addEventListener('change', e => applyShipMethod(e.target.value));
            });

            // Metode awal sesuai yang tercentang; kalau tak ada opsi → 'none'.
            const initial = document.querySelector('input[name="shipMethod"]:checked');
            applyShipMethod(initial ? initial.value : 'none');
            el('shipSubmit').disabled = false;
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
            let isRecommended = (s.flags && s.flags.includes('recommended')) ? 'selected' : '';
            sel.insertAdjacentHTML('beforeend', `<option value="${s.pickup_time_id}" ${isRecommended}>${[d, s.time_text].filter(Boolean).join(' ')}</option>`);
        });
    }

    el('shipSubmit').addEventListener('click', async () => {
        const btn = el('shipSubmit');
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = 'Memproses...';

        const method = document.querySelector('input[name="shipMethod"]:checked')?.value || shipCtx.method;
        const body = {};
        if (method === 'pickup') {
            body.pickup = {};
            if (el('pickupAddr').value) body.pickup.address_id = Number(el('pickupAddr').value) || el('pickupAddr').value;
            if (el('pickupTimeWrap').style.display !== 'none' && el('pickupTime').value) {
                body.pickup.pickup_time_id = el('pickupTime').value;
            }
        } else if (method === 'dropoff') {
            body.dropoff = {};
            if (el('dropoffBranch').value) body.dropoff.branch_id = Number(el('dropoffBranch').value) || el('dropoffBranch').value;
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

    // ── Lacak Pengiriman ──────────────────────────────────────────────────────
    const trackModalEl = document.getElementById('trackModal');
    window.trackShipment = async (storeId, sn) => {
        el('trkNo').textContent = '—';
        el('trkLoading').style.display = 'block';
        el('trkTimeline').innerHTML = '';
        el('trkEmpty').style.display = 'none';
        bootstrap.Modal.getOrCreateInstance(trackModalEl).show();
        try {
            const d = await api(`/api/marketplace/stores/${storeId}/bookings/${sn}/tracking`);
            el('trkLoading').style.display = 'none';
            el('trkNo').innerHTML = resiActionsHtml(d.tracking_number);
            const list = (d.tracking_info || []).slice().sort((a,b) => (b.update_time||0) - (a.update_time||0));
            if (!list.length) {
                el('trkEmpty').style.display = 'block';
                el('trkEmpty').textContent = d.message || 'Belum ada data pelacakan.';
                return;
            }
            el('trkTimeline').innerHTML = list.map((t, i) => {
                const dt = t.update_time ? new Date(t.update_time*1000).toLocaleString('id-ID') : '';
                const desc = t.description || String(t.logistics_status || '').replace(/_/g, ' ');
                const active = i === 0;
                return `<div style="display:flex; gap:.7rem;">
                    <div style="display:flex; flex-direction:column; align-items:center;">
                        <div style="width:11px;height:11px;border-radius:50%;background:${active?'#16a34a':'#cbd5e1'};margin-top:4px;flex:none"></div>
                        ${i < list.length-1 ? '<div style="width:2px;flex:1;background:#e2e8f0;margin-top:2px;min-height:14px"></div>' : ''}
                    </div>
                    <div style="padding-bottom:.6rem;">
                        <div style="font-weight:${active?700:600};color:${active?'#0f172a':'#475569'};font-size:.83rem;line-height:1.35">${desc}</div>
                        <div style="font-size:.7rem;color:#94a3b8;margin-top:1px">${dt}</div>
                    </div>
                </div>`;
            }).join('');
        } catch(e) {
            el('trkLoading').style.display = 'none';
            el('trkEmpty').style.display = 'block';
            el('trkEmpty').textContent = 'Gagal memuat pelacakan: ' + e.message;
        }
    };

    // ── Cetak Resi ───────────────────────────────────────────────────────────
    window.printDocument = async function (storeId, bookingSn) {
        const url = `/api/marketplace/stores/${storeId}/bookings/${bookingSn}/document`;
        
        const alertHtml = `<div id="printAlert" style="position:fixed;top:20px;right:20px;background:#3b82f6;color:white;padding:10px 20px;border-radius:8px;z-index:9999;box-shadow:0 4px 6px rgba(0,0,0,0.1)">⏳ Meminta dokumen resi kilat...</div>`;
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        window.open(url, '_blank');
        
        setTimeout(() => {
            const el = document.getElementById('printAlert');
            if (el) el.remove();
            load();
        }, 5000);
    };

    // ── Polling (tanpa Reverb) ───────────────────────────────────────────────
    let lastPollAt = Date.now();
    setInterval(() => {
        // Polling setiap 15 detik
        if (Date.now() - lastPollAt >= 15000) {
            lastPollAt = Date.now();
            // Hanya poll jika halaman sedang aktif/terlihat
            if (!document.hidden) {
                load();
            }
        }
    }, 5000);

    load();
</script>
@endpush
