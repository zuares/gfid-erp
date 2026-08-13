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
        position: sticky; top: 0; z-index: 10;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    body[data-theme="dark"] .table-list thead th{ background:rgba(15,23,42,.98); color:#9ca3af; }
    .table-list tbody td{
        vertical-align:middle; border-top:1px solid rgba(148,163,184,.16);
        padding:.52rem .62rem; font-size:.78rem;
    }
    body[data-theme="dark"] .table-list tbody td{ border-top-color:rgba(51,65,85,.85); }

    .prd-img{ width:42px; height:42px; border-radius:7px; object-fit:cover; background:#f1f5f9; }
    .prd-name{ 
        font-weight:700; color:inherit; max-width:280px; line-height:1.3;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; text-overflow:ellipsis;
    }
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

    .model-row td{ background:rgba(148,163,184,.04); font-size:.73rem; padding: .35rem .62rem; border-top: 1px solid transparent; }
    body[data-theme="dark"] .model-row td{ background:rgba(30,41,59,.4); }
    .inp-mini{ width:88px; font-size:.72rem; padding:2px 6px; border:1px solid rgba(148,163,184,.35); border-radius:6px; background:transparent; color:inherit; }
    .btn-mini{ font-size:.65rem; padding:2px 8px; border-radius:6px; }
    .prd-caret{ cursor:pointer; user-select:none; color:#64748b; font-size:.8rem; }
    .empty{ padding:2.2rem 1.25rem; text-align:center; color:#64748b; }

    /* Autosuggest item untuk SKU varian di tab Bermasalah */
    .variant-sku-suggest{ position:relative; width:180px; }
    .variant-sku-suggest .input-group{ width:100%; }
    .variant-sku-results{
        display:none; position:absolute; left:0; right:0; top:calc(100% + 3px); z-index:10050;
        max-height:210px; overflow-y:auto; background:var(--card,#fff);
        border:1px solid rgba(148,163,184,.35); border-radius:7px;
        box-shadow:0 8px 18px rgba(15,23,42,.16);
    }
    body[data-theme="dark"] .variant-sku-results{ background:#1e293b; border-color:rgba(51,65,85,.9); }
    .variant-sku-option{
        display:block; width:100%; padding:.42rem .55rem; border:0; border-bottom:1px solid rgba(148,163,184,.14);
        background:transparent; color:inherit; text-align:left; cursor:pointer; font-size:.7rem;
    }
    .variant-sku-option:last-child{ border-bottom:0; }
    .variant-sku-option:hover{ background:rgba(59,130,246,.12); }
    .variant-sku-option-code{ display:block; font-weight:750; }
    .variant-sku-option-name{ display:block; color:#64748b; margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    body[data-theme="dark"] .variant-sku-option-name{ color:#cbd5e1; }

    /* ── Tabs (Produk / Boost) ── */
    .store-tab { 
        background: transparent; border: none; padding: .4rem .8rem; font-size: .8rem; font-weight: 600; 
        color: var(--prd-muted); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; white-space: nowrap; transition: color 0.15s;
    }
    .store-tab:hover { color: var(--prd-accent); }
    .store-tab.active { color: var(--prd-accent); border-bottom-color: var(--prd-accent); }
    body[data-theme="dark"] .store-tab:hover, body[data-theme="dark"] .store-tab.active { color: #fff; border-bottom-color: #fff; }

    .pt-tabs{ display:flex; gap:.35rem; margin-bottom:.85rem; }
    .pt-tab{
        font-size:.82rem; font-weight:650; padding:.4rem .9rem; border-radius:8px;
        border:1px solid var(--prd-border); background:transparent; color:var(--prd-muted); cursor:pointer;
    }
    .pt-tab:hover{ background:rgba(148,163,184,.08); }
    .pt-tab.active{ background:var(--prd-accent); border-color:var(--prd-accent); color:#fff; }
    .pt-pane{ display:none; }
    .pt-pane.active{ display:block; animation:ptfade .2s ease; }
    @keyframes ptfade{ from{ opacity:0; transform:translateY(3px);} to{ opacity:1; transform:none;} }

    /* ── Stok tab ── */
    .st-dirty-row td{ background:rgba(37,99,235,.07)!important; }
    body[data-theme="dark"] .st-dirty-row td{ background:rgba(37,99,235,.16)!important; }
    .st-stock-input{ text-align:right; }
    .st-cat-head{ cursor:pointer; }
    .st-cat-head td{ background:rgba(51,65,85,.07); border-top:2px solid rgba(148,163,184,.3); padding:.5rem .62rem; }
    body[data-theme="dark"] .st-cat-head td{ background:rgba(51,65,85,.4); border-top-color:rgba(51,65,85,.9); }
    .st-cat-head:hover td{ background:rgba(51,65,85,.12); }
    .st-cat-inner{ display:flex; align-items:center; gap:.55rem; }
    .st-caret{ color:#64748b; width:.9rem; font-size:.72rem; }
    .st-cat-name{ font-weight:800; font-size:.82rem; }
    .st-cat-meta{ font-size:.67rem; color:#94a3b8; }
    .st-cat-sum{ margin-left:auto; font-size:.72rem; color:#475569; display:inline-flex; align-items:center; gap:.5rem; white-space:nowrap; }
    body[data-theme="dark"] .st-cat-sum{ color:#cbd5e1; }
    .st-badge-empty{ background:rgba(239,68,68,.12); color:#b91c1c; border-radius:6px; padding:1px 7px; font-weight:700; font-size:.65rem; }
    .st-item-head td{ background:rgba(148,163,184,.05); padding:.32rem .62rem .32rem 1rem; }
    body[data-theme="dark"] .st-item-head td{ background:rgba(30,41,59,.55); }
    .st-item-name{ font-weight:700; font-size:.76rem; }
    .st-grp-row td:nth-child(2){ padding-left:1.15rem; }
    .st-stok-cell{ display:flex; align-items:center; gap:.4rem; justify-content:flex-end; }
    .st-stok-cell .st-stock-input{ width:76px; text-align:right; font-weight:700; font-size:.8rem; padding:2px 7px; border-radius:7px; }
    .st-gd-cell{ text-align:right; font-weight:700; font-size:.8rem; white-space:nowrap; }
    .st-inp-zero{ border-color:rgba(239,68,68,.55)!important; color:#b91c1c!important; background:rgba(239,68,68,.06)!important; }
    .st-inp-low{ border-color:rgba(245,158,11,.6)!important; color:#b45309!important; background:rgba(245,158,11,.07)!important; }
    .st-save-btn{ opacity:.4; transition:opacity .15s; }
    tr:hover .st-save-btn, .st-dirty-row .st-save-btn{ opacity:1; }

    /* ── Boost pane (minimalis) ── */
    .bo-bar{ display:flex; align-items:center; gap:.5rem; margin-bottom:1rem; }
    .bo-slotcount{ font-size:.78rem; font-weight:700; color:#ea580c; }
    .bo-sec{ border:1px solid var(--prd-border); border-radius:10px; padding:.85rem 1rem; margin-bottom:.9rem; background:var(--card,#fff); }
    body[data-theme="dark"] .bo-sec{ background:rgba(15,23,42,.5); }
    .bo-sec-head{ font-size:.85rem; font-weight:750; margin-bottom:.6rem; }
    .bo-hint{ font-weight:500; font-size:.7rem; color:#94a3b8; margin-left:.3rem; }
    .bo-slotbar{ display:flex; gap:.4rem; margin-bottom:.6rem; }
    .bo-slot{ width:40px; height:40px; border-radius:10px; border:2px dashed rgba(148,163,184,.4); display:flex; align-items:center; justify-content:center; font-size:1rem; color:#cbd5e1; }
    .bo-slot.filled{ border-style:solid; border-color:#ea580c; background:rgba(234,88,12,.08); }
    .bo-chips{ display:flex; gap:.5rem; flex-wrap:wrap; }
    .bo-chip{ display:flex; align-items:center; gap:.5rem; border:1px solid var(--prd-border); border-radius:10px; padding:.35rem .55rem; background:rgba(248,250,252,.6); max-width:260px; }
    body[data-theme="dark"] .bo-chip{ background:rgba(30,41,59,.6); }
    .bo-chip img{ width:32px; height:32px; border-radius:7px; object-fit:cover; background:#e2e8f0; }
    .bo-chip .nm{ font-size:.74rem; font-weight:650; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bo-chip .rm{ font-size:.66rem; color:#ea580c; font-weight:700; }
    .bo-list{ display:flex; flex-direction:column; }
    .bo-row{ display:flex; align-items:center; gap:.55rem; padding:.45rem 0; border-top:1px solid rgba(148,163,184,.14); font-size:.78rem; }
    .bo-row:first-child{ border-top:0; }
    .bo-row img{ width:32px; height:32px; border-radius:7px; object-fit:cover; background:#e2e8f0; flex:none; }
    .bo-row .grow{ flex:1; min-width:0; }
    .bo-row .nm{ font-weight:650; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bo-row .meta{ font-size:.68rem; color:#94a3b8; }
    .bo-empty{ font-size:.76rem; color:#94a3b8; padding:.5rem 0; }
    .bo-timepill{ display:inline-flex; align-items:center; gap:.3rem; font-size:.7rem; font-weight:700; padding:.14rem .5rem; border-radius:999px; background:rgba(37,99,235,.1); color:#1d4ed8; margin:0 .25rem .25rem 0; }
    .bo-timepill.off{ background:rgba(148,163,184,.18); color:#64748b; }
    .bo-timepill a{ text-decoration:none; }
    .bo-add{ display:flex; gap:.4rem; flex-wrap:wrap; align-items:center; margin-top:.7rem; padding-top:.7rem; border-top:1px dashed rgba(148,163,184,.3); }
    .bo-add select.prod-picker{ min-width:220px; max-width:280px; }
    .bo-times{ display:flex; gap:.3rem; flex-wrap:wrap; }
    .bo-t{ font-size:.76rem; padding:.25rem .4rem; border-radius:7px; border:1px solid var(--prd-border); background:var(--card,#fff); }
    body[data-theme="dark"] .bo-t{ background:#0f172a; color:#fff; }
    .bo-tag{ font-size:.62rem; font-weight:800; padding:.1rem .4rem; border-radius:5px; text-transform:uppercase; }
    .tag-schedule{ background:rgba(37,99,235,.12); color:#1d4ed8; }
    .tag-pool{ background:rgba(22,163,74,.12); color:#15803d; }
    .tag-manual{ background:rgba(100,116,139,.14); color:#475569; }
    .bo-off{ opacity:.5; }

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
            <button class="btn btn-pill btn-prd-outline" id="btnAutoMap" onclick="autoMap()">⚡ Auto-map</button>
            <button class="btn btn-pill btn-prd-primary" id="btnSync" onclick="syncProducts()">⟳ Sync Shopee</button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="pt-tabs">
        <button class="pt-tab active" data-tab="produk" onclick="switchTab('produk')">🏷 Daftar Produk</button>
        <button class="pt-tab" data-tab="stok" onclick="switchTab('stok')">📦 Stok</button>
        <button class="pt-tab" data-tab="boost" onclick="switchTab('boost')">🚀 Naikkan Produk</button>
    </div>

    {{-- ══ TAB: Daftar Produk ══ --}}
    <div id="tabProduk" class="pt-pane active">
    <div id="storeTabs" class="d-flex gap-2 mb-2" style="overflow-x:auto; border-bottom:1px solid var(--prd-border); padding-bottom:0px; scrollbar-width:none;"></div>
    <div style="background:rgba(148,163,184,.03); padding:.55rem .75rem; border-radius:8px; border:1px solid var(--prd-border); margin-bottom:.65rem;">
        <div class="filter-bar" style="flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; margin-bottom: 0;">
            <div style="position:relative; flex-shrink:0;">
                <input type="text" class="form-control form-control-sm filter-search w-100" style="padding-left:26px;" placeholder="Cari nama / SKU / item id…" id="fSearch">
                <i class="bi bi-search" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
            </div>
            <input type="hidden" id="fStore" value="">
            <div style="position:relative; flex-shrink:0; min-width:125px;">
                <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="fStatus">
                    <option value="">Semua Status</option>
                    <option value="NORMAL" selected>● Tampil</option>
                    <option value="UNLIST">● Arsip</option>
                    <option value="BANNED">● Banned</option>
                </select>
                <i class="bi bi-circle-half" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
            </div>
            <div style="position:relative; flex-shrink:0; min-width:145px;">
                <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="fMapping">
                    <option value="">Semua Mapping</option>
                    <option value="unmapped">❌ Belum di-mapping</option>
                    <option value="mapped">✓ Sudah di-mapping</option>
                    <option value="nosku">⚠ SKU kosong</option>
                </select>
                <i class="bi bi-link-45deg" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.85rem"></i>
            </div>
            <div style="position:relative; flex-shrink:0; min-width:125px;">
                <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="fSort">
                    <option value="sales" selected>Terlaris (Total)</option>
                    <option value="synced">Terbaru sync</option>
                    <option value="stock_asc">Stok terendah</option>
                    <option value="stock_desc">Stok tertinggi</option>
                    <option value="price_asc">Harga terendah</option>
                    <option value="price_desc">Harga tertinggi</option>
                    <option value="name">Nama A–Z</option>
                </select>
                <i class="bi bi-sort-down" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.8rem"></i>
            </div>
            <span class="filter-reset ms-2" id="fReset" onclick="resetFilters()" style="flex-shrink:0;">✕ Reset filter</span>
            <span class="filter-count ms-auto" id="prdCount" style="flex-shrink:0; white-space:nowrap;"></span>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="card-main table-wrap" style="max-height: calc(100vh - 210px); overflow-y: auto;">
        <table class="table-list table-hover">
            <thead><tr>
                <th style="width:26px"></th><th style="width:50px"></th><th>Produk</th>
                <th>Status</th><th>Harga Setelah Diskon</th><th>Stok</th><th>Terjual</th><th>Statistik</th><th>Mapping</th><th style="width:230px">Aksi</th>
            </tr></thead>
            <tbody id="prdBody"><tr><td colspan="10" class="empty">Memuat…</td></tr></tbody>
        </table>
    </div>
    </div>{{-- /tabProduk --}}

    {{-- ══ TAB: Stok ══ --}}
    <div id="tabStok" class="pt-pane">
        <div id="stStoreTabs" class="d-flex gap-2 mb-2" style="overflow-x:auto; border-bottom:1px solid var(--prd-border); padding-bottom:0px; scrollbar-width:none;"></div>
        <div class="kpis" id="stKpiRow" style="margin-bottom:.65rem;"></div>
        <div style="background:rgba(148,163,184,.03); padding:.55rem .75rem; border-radius:8px; border:1px solid var(--prd-border); margin-bottom:.65rem;">
            <div class="filter-bar" style="flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; margin-bottom: 0;">
                <div style="position:relative; flex-shrink:0;">
                    <input type="text" class="form-control form-control-sm filter-search w-100" style="padding-left:26px;" placeholder="Cari nama / SKU / varian…" id="stSearch">
                    <i class="bi bi-search" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                <div style="position:relative; flex-shrink:0; min-width:135px;">
                    <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="stStock">
                        <option value="">Semua Stok</option>
                        <option value="empty">● Habis (0)</option>
                        <option value="low">● Menipis (≤5)</option>
                        <option value="in">● Tersedia</option>
                    </select>
                    <i class="bi bi-box-seam" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                <div style="position:relative; flex-shrink:0; min-width:125px;">
                    <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="stSort">
                        <option value="stock_asc" selected>Stok terendah</option>
                        <option value="stock_desc">Stok tertinggi</option>
                        <option value="sales">Terlaris</option>
                        <option value="name">Nama A–Z</option>
                    </select>
                    <i class="bi bi-sort-down" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.8rem"></i>
                </div>
                <label class="ms-auto" style="display:flex;align-items:center;gap:.35rem;font-size:.72rem;flex-shrink:0;white-space:nowrap;cursor:pointer;color:var(--prd-muted)" title="Kelompokkan per kategori & produk hasil mapping">
                    <input type="checkbox" id="stGroup" checked style="cursor:pointer"> 📂 Grup kategori
                </label>
                <button class="btn btn-sm btn-prd-primary" id="stSaveAll" onclick="stSaveAll()" style="flex-shrink:0; white-space:nowrap;" disabled>💾 Simpan Semua (<span id="stDirtyCount">0</span>)</button>
            </div>
        </div>

        {{-- Tabel stok --}}
        <div class="card-main table-wrap" style="max-height: calc(100vh - 240px); overflow-y: auto;">
            <table class="table-list table-hover">
                <thead><tr>
                    <th style="width:46px"></th>
                    <th>Produk</th>
                    <th style="width:78px; text-align:right;" title="Stok gudang internal (tersedia = fisik − dialokasi)">Gudang</th>
                    <th style="width:150px; text-align:right;">Marketplace</th>
                </tr></thead>
                <tbody id="stBody"><tr><td colspan="4" class="empty">Memuat…</td></tr></tbody>
            </table>
        </div>
    </div>{{-- /tabStok --}}

    {{-- ══ TAB: Naikkan Produk (Boost) ══ --}}
    <div id="tabBoost" class="pt-pane">
        <div class="bo-bar">
            <select class="form-select form-select-sm filter-select" id="boStore"></select>
            <span class="bo-slotcount" id="boSlots">—</span>
            <button class="btn btn-sm btn-prd-outline ms-auto" onclick="boReload()">⟳ Refresh</button>
        </div>

        {{-- Sedang di-boost --}}
        <div class="bo-sec">
            <div class="bo-sec-head">Sedang di-boost <span class="bo-hint">maks 5 · tiap boost 4 jam</span></div>
            <div class="bo-slotbar" id="boSlotBar"></div>
            <div class="bo-chips" id="boBoosted"></div>
        </div>

        {{-- Jadwal jam-tetap --}}
        <div class="bo-sec">
            <div class="bo-sec-head">⏰ Jadwal jam-tetap <span class="bo-hint">naik otomatis tiap hari di jam ini</span></div>
            <div id="boSchedList" class="bo-list"></div>
            <div class="bo-add">
                <select class="prod-picker form-select form-select-sm" id="boSchedPick"></select>
                <span class="bo-times" id="boSchedTimes"><input type="time" class="bo-t" value="08:00"></span>
                <button class="btn btn-sm btn-prd-outline" onclick="boAddTime()">+ jam</button>
                <button class="btn btn-sm btn-prd-primary" onclick="boSaveSched()">Simpan</button>
            </div>
        </div>

        {{-- Rotasi otomatis --}}
        <div class="bo-sec">
            <div class="bo-sec-head">🔁 Rotasi otomatis <span class="bo-hint">isi slot kosong bergiliran tiap 4 jam</span></div>
            <div id="boPoolList" class="bo-list"></div>
            <div class="bo-add">
                <select class="prod-picker form-select form-select-sm" id="boPoolPick"></select>
                <button class="btn btn-sm btn-prd-primary" onclick="boAddPool()">+ Tambah ke antrian</button>
            </div>
        </div>

        {{-- Riwayat --}}
        <div class="bo-sec">
            <div class="bo-sec-head">🧾 Riwayat <span class="bo-hint">50 boost terakhir</span></div>
            <div id="boLogs" class="bo-list bo-logs"></div>
        </div>
    </div>{{-- /tabBoost --}}
</div>

<div class="modal fade" id="modalMapping" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-black">Hubungkan SKU ke Item Internal</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="mapSelectedInternalId">

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">MARKETPLACE SKU</label>
                    <input type="text" class="form-control" id="mapSkuInput"
                        placeholder="Marketplace SKU" style="border-radius:12px" autocomplete="off" readonly>
                </div>

                <div id="mapRecommendations" style="display:none;margin-bottom:1rem">
                    <div class="fw-bold mb-1"
                        style="font-size:.72rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em">
                        REKOMENDASI ITEM INTERNAL
                    </div>
                    <div id="mapRecoList" class="d-flex flex-wrap gap-2"></div>
                </div>

                <div class="mb-3 position-relative">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">CARI ITEM INTERNAL</label>
                    <input type="text" class="form-control mb-1" id="mapItemSearch"
                        placeholder="Ketik kode atau nama item…" style="border-radius:12px" autocomplete="off">
                    <div id="mapItemResults" class="border shadow-sm"
                        style="border-radius:12px;overflow:hidden;display:none;max-height:200px;overflow-y:auto;position:absolute;z-index:99;background:#fff;width:100%"></div>
                    <div id="mapItemSelected" class="mt-2 fw-bold" style="font-size:.85rem;color:#166534"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">CATATAN (Opsional)</label>
                    <input type="text" class="form-control" id="mapNotes" style="border-radius:12px">
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-dark fw-bold" style="border-radius:999px" id="mapSaveBtn"
                        onclick="submitMapping()">Simpan Mapping</button>
                </div>
            </div>
        </div>
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
            if ($('tabStok') && $('tabStok').classList.contains('active')) stRender();
        } catch (e) {
            $('prdBody').innerHTML = `<tr><td colspan="10" class="empty text-danger">${esc(e.message)}</td></tr>`;
        }
    };

    window.syncProducts = async function () {
        $('btnSync').disabled = true; $('btnSync').textContent = '⏳ Memulai…';
        try {
            const res = await api(`${API}/sync`, { method: 'POST', body: '{}' });
            toast(res.message, 'success');
            // Do not call loadProducts immediately since it's backgrounded. 
            // Just let user know it's running.
        } catch (e) { alert('Sync gagal: ' + e.message); }
        finally { $('btnSync').disabled = false; $('btnSync').textContent = '⟳ Sync Shopee'; }
    };

    function buildStoreOptions() {
        const sel = $('fStore'), cur = sel.value;
        const stores = [...new Map(products.filter(p => p.store).map(p => [p.store.id, p.store.name])).entries()];
        
        let html = `<button class="store-tab ${cur === '' ? 'active' : ''}" onclick="setStoreTab('')">Semua Produk</button>`;
        html += `<button class="store-tab ${cur === 'bermasalah' ? 'active' : ''}" style="color:var(--bs-danger);" onclick="setStoreTab('bermasalah')">⚠ Bermasalah</button>`;
        stores.forEach(([id, name]) => {
            html += `<button class="store-tab ${cur === String(id) ? 'active' : ''}" onclick="setStoreTab('${id}')">${esc(name)}</button>`;
        });
        if ($('storeTabs')) $('storeTabs').innerHTML = html;
    }
    
    window.setStoreTab = function(id) {
        $('fStore').value = id;
        buildStoreOptions();
        render();
    };

    // ── Filter fungsional (instan, client-side) ─────────────────────────────
    function modelMapState(m) {
        if (!m.model_sku) return 'nosku';
        return m.mapping ? 'mapped' : 'unmapped';
    }
    function productMapState(p) {
        const models = p.models || [];
        if (!models.length) {
            return p.item_sku ? (p.mapping ? 'mapped' : 'unmapped') : 'nosku';
        }
        const states = models.map(modelMapState);
        if (states.every(s => s === 'nosku')) return 'nosku';
        return states.some(s => s === 'unmapped') ? 'unmapped' : 'mapped';
    }

    function filtered() {
        const q = $('fSearch').value.trim().toLowerCase();
        const store = $('fStore').value;
        const status = $('fStatus').value;
        const mapping = $('fMapping')?.value || (kpiFilter === 'unmapped' ? 'unmapped' : '');
        const stock = kpiFilter === 'zero' ? 'zero' : '';
        const statusKpi = ['NORMAL','UNLIST'].includes(kpiFilter) ? kpiFilter : '';

        let rows = products.filter(p => {
            if (store === 'bermasalah') {
                const isBermasalah = p.item_status === 'BANNED' || (p.stock_total ?? 0) === 0 || productMapState(p) === 'unmapped' || productMapState(p) === 'nosku';
                if (!isBermasalah) return false;
            } else if (store && String(p.store?.id) !== store) {
                return false;
            }
            if ((status || statusKpi) && p.item_status !== (status || statusKpi)) return false;
            if (mapping && productMapState(p) !== mapping) return false;
            if (stock === 'zero' && (p.stock_total ?? 0) !== 0) return false;
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
        ['fSearch','fStore','fMapping'].forEach(id => {
            if ($(id)) $(id).value = '';
        });
        if ($('fStatus')) $('fStatus').value = 'NORMAL';
        if ($('fSort')) $('fSort').value = 'sales';
        kpiFilter = '';
        buildStoreOptions();
        render();
    };

    function anyFilterActive() {
        return kpiFilter || $('fSearch').value || $('fStore').value || ($('fStatus') && $('fStatus').value !== 'NORMAL') || $('fMapping').value;
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
        if (['NORMAL','UNLIST'].includes(key) && $('fStatus')) $('fStatus').value = '';
        if (key === 'unmapped' && $('fMapping')) $('fMapping').value = '';
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

    function mappingBadge(m, skuOverride = null, mappingOverride = undefined) {
        const sku = skuOverride ?? m.model_sku ?? '';
        const mapping = mappingOverride === undefined ? m.mapping : mappingOverride;
        if (!sku) return '<span class="badge-status st-map-warn">SKU kosong</span>';
        if (mapping) {
            return `<span class="badge-status st-map-ok" title="${esc(mapping.item_name || '')}">${esc(mapping.item_code || mapping.item_id)}</span>
                <button class="btn btn-prd-outline btn-mini" onclick="openMapModal('${esc(sku)}')" title="Ganti mapping">✎</button>`;
        }
        return `<button class="btn btn-outline-danger btn-mini" onclick="openMapModal('${esc(sku)}')">❌ Map</button>`;
    }

    function mappingSummary(models) {
        const withSku = models.filter(m => m.model_sku);
        if (!withSku.length) return '<span class="badge-status st-map-warn">SKU kosong</span>';
        const mapped = withSku.filter(m => m.mapping).length;
        const cls = mapped === withSku.length ? 'st-map-ok' : 'st-map-no';
        return `<span class="badge-status ${cls}">${mapped}/${withSku.length} map</span>`;
    }

    function inlineEditors(pid, m) {
        let origPrc = m.price ?? '';
        let discPrc = origPrc;
        
        try {
            const raw = typeof m.raw_json === 'string' ? JSON.parse(m.raw_json) : (m.raw_json || {});
            const pInfo = raw.price_info?.[0];
            if (pInfo) {
                origPrc = pInfo.original_price ?? origPrc;
                discPrc = pInfo.current_price ?? origPrc;
            }
        } catch(e) {}

        return `<div class="d-flex gap-1 flex-nowrap align-items-center">
            <input type="hidden" id="prc-${pid}-${m.model_id}" value="${origPrc}">
            <div class="input-group input-group-sm" style="width:115px;" title="Harga Setelah Diskon">
                <span class="input-group-text text-success" style="padding:0 6px; font-size:.7rem">Rp</span>
                <input class="form-control px-2" style="font-size:.75rem; padding: 2px 6px;" type="number" min="100" value="${discPrc}" id="disc-${pid}-${m.model_id}">
            </div>
            <button class="btn btn-outline-primary btn-mini" onclick="savePrice(${pid}, '${m.model_id}')" style="padding:1px 6px;" title="Simpan Harga">💾</button>
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
            const stats = `<div style="font-size:.7rem; line-height:1.4; white-space:nowrap;"><i class="bi bi-eye"></i> ${p.views || 0} &nbsp; <i class="bi bi-star"></i> ${p.rating_star || 0}</div>`;

            const isBermasalahTab = $('fStore').value === 'bermasalah';

            let aksiContent = '';
            if (isBermasalahTab) {
                let issues = [];
                if (p.item_status === 'BANNED') {
                    issues.push(`<a class="btn btn-sm btn-outline-danger w-100 mb-1 py-1" href="https://seller.shopee.co.id/portal/product/${p.item_id}" target="_blank" style="font-size:.7rem">Cek Pelanggaran di Shopee ↗</a>`);
                }
                if ((p.stock_total ?? 0) === 0) {
                    if (multiModel) {
                        issues.push(`<button class="btn btn-sm btn-outline-warning w-100 mb-1 py-1" onclick="toggleModels(${p.id}, document.querySelector('tr[data-pid=\\'${p.id}\\'] .prd-caret'))" style="font-size:.7rem">Buka Varian & Isi Stok</button>`);
                    } else {
                        issues.push(`<div class="input-group input-group-sm mb-1"><input type="number" class="form-control px-2" placeholder="Stok Baru" id="stk-${p.id}-0" style="font-size:.75rem"><button class="btn btn-warning" onclick="saveStock(${p.id}, '0')" style="font-size:.7rem; padding: 2px 6px;">Simpan</button></div>`);
                    }
                }
                const mapState = productMapState(p);
                if (mapState === 'nosku') {
                    if (multiModel) {
                        issues.push(`<button class="btn btn-sm btn-outline-danger w-100 py-1 mb-1" onclick="toggleModels(${p.id}, document.querySelector('tr[data-pid=\\'${p.id}\\'] .prd-caret'))" style="font-size:.7rem">⚠️ SKU Varian Kosong (Buka)</button>`);
                    } else {
                        issues.push(`<div class="mb-1 text-danger fw-bold" style="font-size:.7rem">⚠️ SKU Kosong (Isi di sebelah kiri)</div>`);
                    }
                } else if (mapState === 'unmapped') {
                    if (multiModel) {
                        issues.push(`<button class="btn btn-sm btn-outline-primary w-100 py-1" onclick="toggleModels(${p.id}, document.querySelector('tr[data-pid=\\'${p.id}\\'] .prd-caret'))" style="font-size:.7rem">Perbaiki Mapping Varian</button>`);
                    } else {
                        const sku = models.length ? models[0].model_sku : p.item_sku;
                        issues.push(`<button class="btn btn-sm btn-outline-primary w-100 py-1" onclick="openMapModal('${esc(sku || '')}')" style="font-size:.7rem">Mapping ke GFID</button>`);
                    }
                }
                
                aksiContent = issues.join('');
                if (!aksiContent) aksiContent = '<span class="text-muted" style="font-size:.75rem">Tidak ada masalah khusus</span>';
            } else {
                aksiContent = `
                    <div class="dropdown mb-1">
                        <button class="btn btn-prd-outline btn-mini dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 2px 8px; font-weight: 700; border-radius: 6px;">
                            ⋮ Aksi
                        </button>
                        <ul class="dropdown-menu shadow-sm" style="font-size: .78rem; min-width: 140px; padding: .3rem 0; border-radius: 8px;">
                            <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="showHistory(${p.id})">📈 Riwayat</a></li>
                            ${st === 'NORMAL' ? `<li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="boostNow(${p.id}, this)">🚀 Naikkan (Boost)</a></li>` : ''}
                            ${st === 'NORMAL' ? `<li><hr class="dropdown-divider" style="margin: .2rem 0"></li><li><a class="dropdown-item py-2 text-danger" href="javascript:void(0)" onclick="setUnlist(${p.id}, true)">🙈 Arsipkan Produk</a></li>` : (st === 'UNLIST' ? `<li><hr class="dropdown-divider" style="margin: .2rem 0"></li><li><a class="dropdown-item py-2 text-success" href="javascript:void(0)" onclick="setUnlist(${p.id}, false)">👁 Aktifkan Produk</a></li>` : '')}
                        </ul>
                    </div>
                    ${!multiModel && models.length ? `<div class="mt-1">${inlineEditors(p.id, models[0])}</div>` : ''}
                `;
            }

            const isModelOpened = openedModels.has(p.id);
            const mainRow = `<tr data-pid="${p.id}">
                <td>${multiModel ? `<span class="prd-caret" onclick="toggleModels(${p.id}, this)"><i class="bi bi-chevron-${isModelOpened ? 'down' : 'right'}"></i></span>` : ''}</td>
                <td>${p.image_url ? `<img class="prd-img" src="${esc(p.image_url)}" loading="lazy">` : '<div class="prd-img"></div>'}</td>
                <td><div class="prd-name" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden" title="${esc(p.item_name || '')}">${esc(p.item_name || '—')}</div>
                    <div class="prd-sku d-flex align-items-center gap-1 flex-wrap mt-1">
                        <span class="text-muted">SKU:</span>
                        <div class="input-group input-group-sm" style="max-width:180px;">
                            <input type="text" class="form-control px-2 py-0" style="font-size:.72rem;height:24px" value="${esc(p.item_sku || '')}" id="isku-${p.id}" placeholder="Induk SKU">
                            <button class="btn btn-outline-secondary btn-mini fw-bold" onclick="saveIndukSkuInline(${p.id})" style="padding:1px 8px;height:24px" title="Simpan SKU">💾</button>
                        </div>
                        <span class="text-muted">· ${esc(p.item_id)}${multiModel ? ` · ${models.length} varian` : ''}</span>
                    </div>
                </td>
                <td>${statusBadge(st)}</td>
                <td>${price}</td>
                <td>${stockCell(p.stock_total)}</td>
                <td class="muted">${p.sales ?? '—'}</td>
                <td>${stats}</td>
                <td data-product-mapping="${p.id}">${multiModel ? mappingSummary(models) : (models.length ? mappingBadge(models[0]) : mappingBadge(p, p.item_sku, p.mapping))}</td>
                <td>
                    ${aksiContent}
                </td>
            </tr>`;

            const modelRows = multiModel ? models.map(m => `
                <tr class="model-row mr-${p.id}" data-model-id="${esc(m.model_id)}" style="${isModelOpened ? '' : 'display:none'}">
                    <td></td><td></td>
                    <td style="padding-left:22px">↳ ${esc(m.model_name || 'Varian')} 
                        <div class="variant-sku-suggest mt-1">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control px-2 js-variant-sku-input" style="font-size:.72rem" value="${esc(m.model_sku || '')}" id="vsku-${p.id}-${m.model_id}" data-product-id="${p.id}" data-model-id="${esc(m.model_id)}" placeholder="Kode Variasi" autocomplete="off">
                                <button class="btn btn-outline-secondary btn-mini fw-bold" onclick="saveSkuInline(${p.id}, '${m.model_id}')" style="padding:1px 8px;" title="Simpan SKU">💾</button>
                            </div>
                            <div class="variant-sku-results" role="listbox"></div>
                        </div>
                    </td>
                    <td></td>
                    <td>${rp(m.price)}</td>
                    <td>${stockCell(m.stock)}</td>
                    <td></td>
                    <td></td>
                    <td class="variant-mapping-cell">${mappingBadge(m)}</td>
                    <td><div class="d-flex align-items-center gap-1">${inlineEditors(p.id, m)}<button class="btn btn-outline-secondary btn-mini" onclick="showHistory(${p.id})" style="padding:1px 5px" title="Riwayat">📈</button></div></td>
                </tr>`).join('') : '';

            return mainRow + modelRows;
        }).join('');
    }

    let openedModels = new Set();
    window.toggleModels = function (pid, caret) {
        const rows = document.querySelectorAll('.mr-' + pid);
        const show = rows.length && rows[0].style.display === 'none';
        rows.forEach(r => r.style.display = show ? '' : 'none');
        if (caret) caret.innerHTML = show ? '<i class="bi bi-chevron-down"></i>' : '<i class="bi bi-chevron-right"></i>';
        if (show) openedModels.add(pid);
        else openedModels.delete(pid);
    };

    // ── Autosuggest item untuk input SKU varian ─────────────────────────────
    // Input dirender ulang setiap kali tabel di-render, jadi gunakan event delegation.
    function closeVariantSuggestions(except = null) {
        document.querySelectorAll('.variant-sku-results').forEach(box => {
            if (!except || box !== except) box.style.display = 'none';
        });
    }

    function renderVariantSuggestions(input, items) {
        const box = input.closest('.variant-sku-suggest')?.querySelector('.variant-sku-results');
        if (!box) return;

        box.innerHTML = '';
        if (!items.length) {
            box.innerHTML = '<div class="p-2 text-muted" style="font-size:.7rem">Item tidak ditemukan</div>';
            box.style.display = 'block';
            return;
        }

        items.slice(0, 8).forEach(item => {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'variant-sku-option';
            option.dataset.code = item.code || '';
            option.dataset.itemId = item.id || '';
            option.dataset.itemName = item.name || '';
            option.innerHTML = `<span class="variant-sku-option-code"></span><span class="variant-sku-option-name"></span>`;
            option.querySelector('.variant-sku-option-code').textContent = (item.code || '').toUpperCase();
            option.querySelector('.variant-sku-option-name').textContent = item.name || '';
            box.appendChild(option);
        });

        closeVariantSuggestions(box);
        box.style.display = 'block';
    }

    async function loadVariantSuggestions(input) {
        const box = input.closest('.variant-sku-suggest')?.querySelector('.variant-sku-results');
        if (!box) return;

        const q = input.value.trim();
        if (q.length < 2) {
            box.style.display = 'none';
            return;
        }

        const requestId = String(Number(input.dataset.suggestRequest || 0) + 1);
        input.dataset.suggestRequest = requestId;
        box.innerHTML = '<div class="p-2 text-muted" style="font-size:.7rem">Mencari item…</div>';
        closeVariantSuggestions(box);
        box.style.display = 'block';

        try {
            const result = await api('/api/v1/items/suggest?q=' + encodeURIComponent(q) + '&limit=8');
            if (input.dataset.suggestRequest !== requestId) return;
            renderVariantSuggestions(input, Array.isArray(result?.data) ? result.data : []);
        } catch (e) {
            if (input.dataset.suggestRequest !== requestId) return;
            box.innerHTML = '<div class="p-2 text-danger" style="font-size:.7rem">Autosuggest gagal dimuat</div>';
            box.style.display = 'block';
        }
    }

    let variantSuggestTimer = null;
    document.addEventListener('focusin', e => {
        const input = e.target.closest('.js-variant-sku-input');
        if (!input) return;

        // Memudahkan mengganti SKU varian yang sudah ada.
        input.select();
        clearTimeout(variantSuggestTimer);
        variantSuggestTimer = setTimeout(() => loadVariantSuggestions(input), 80);
    });

    document.addEventListener('input', e => {
        const input = e.target.closest('.js-variant-sku-input');
        if (!input) return;

        clearTimeout(variantSuggestTimer);
        variantSuggestTimer = setTimeout(() => loadVariantSuggestions(input), 220);
    });

    document.addEventListener('mousedown', e => {
        const option = e.target.closest('.variant-sku-option');
        if (option) {
            e.preventDefault();
            const input = option.closest('.variant-sku-suggest')?.querySelector('.js-variant-sku-input');
            if (!input) return;
            clearTimeout(variantSuggestTimer);
            input.value = option.dataset.code || '';
            input.focus();
            input.select();
            clearTimeout(variantSuggestTimer);
            closeVariantSuggestions();
            if (option.dataset.itemId) {
                autoMapVariantSelection(input, {
                    id: Number(option.dataset.itemId),
                    code: option.dataset.code || '',
                    name: option.dataset.itemName || '',
                });
            }
            return;
        }

        if (!e.target.closest('.variant-sku-suggest')) closeVariantSuggestions();
    });

    document.addEventListener('keydown', e => {
        const input = e.target.closest('.js-variant-sku-input');
        if (input && e.key === 'Escape') closeVariantSuggestions();
    });

    // ── Aksi stok / harga / unlist ──────────────────────────────────────────
    window.saveStock = async function (pid, modelId) {
        const val = parseInt($(`stk-${pid}-${modelId}`).value);
        if (isNaN(val) || val < 0) return alert('Stok tidak valid');
        try {
            await api(`${API}/${pid}/stock`, { method: 'POST', body: JSON.stringify({ stock_list: [{ model_id: modelId, stock: val }] }) });
            toast('Stok tersimpan ke Shopee ✔');
            
            // Update local state instead of reloading table
            const p = products.find(x => x.id === pid);
            if (p) {
                const m = p.models.find(x => String(x.model_id) === String(modelId));
                if (m) {
                    const diff = val - (m.stock || 0);
                    m.stock = val;
                    p.stock_total = (p.stock_total || 0) + diff;
                }
            }
        } catch (e) { alert('Gagal: ' + e.message); }
    };

    window.savePrice = async function (pid, modelId) {
        const origPrice = parseFloat($(`prc-${pid}-${modelId}`).value);
        const discPrice = parseFloat($(`disc-${pid}-${modelId}`).value);
        
        if (isNaN(origPrice) || origPrice < 100) return alert('Harga asli tidak valid (min 100)');
        if (isNaN(discPrice) || discPrice < 100) return alert('Harga jual tidak valid (min 100)');
        if (discPrice > origPrice) return alert('Harga jual tidak boleh lebih tinggi dari Harga Asli');

        try {
            await api(`${API}/${pid}/price`, { 
                method: 'POST', 
                body: JSON.stringify({ 
                    price_list: [{ model_id: modelId, original_price: origPrice, discount_price: discPrice }] 
                }) 
            });
            toast('Harga berhasil diupdate ke Shopee ✔');
            
            // Update local state instead of reloading table
            const p = products.find(x => x.id === pid);
            if (p) {
                const m = p.models.find(x => String(x.model_id) === String(modelId));
                if (m) {
                    m.price = origPrice;
                    // Try parsing promotion if exists
                    const promo = m.promotion || p.promotion || null;
                    if (promo && typeof promo === 'object') {
                        if (promo.promotion_price_info && promo.promotion_price_info[0]) {
                            promo.promotion_price_info[0].promotion_price = discPrice;
                        }
                    }
                }
            }
        } catch (e) { alert('Gagal: ' + e.message); }
    };

    window.setUnlist = async function (pid, unlist) {
        try {
            const res = await api(`${API}/${pid}/unlist`, { method: 'POST', body: JSON.stringify({ unlist }) });
            const p = products.find(x => x.id === pid);
            if (p) p.item_status = res.item_status || (unlist ? 'UNLIST' : 'NORMAL');
            toast(unlist ? 'Produk disembunyikan' : 'Produk ditampilkan');
            render();
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
            if (boLoaded && $('tabBoost').classList.contains('active')) boReload();
        } catch (e) {
            toast('Gagal boost: ' + e.message, 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = orig; }
        }
    };

    function toast(title, icon = 'success') {
        if (window.Swal) Swal.fire({ toast:true, position:'top-end', icon, title, showConfirmButton:false, timer:2600 });
    }

    window.saveSkuInline = async function (pid, modelId) {
        const inp = document.getElementById(`vsku-${pid}-${modelId}`);
        const newSku = inp.value;
        const prod = products.find(p => p.id === pid);
        if (!prod) return;
        const m = prod.models.find(x => String(x.model_id) === String(modelId));
        if (!m || m.model_sku === newSku) return;

        inp.disabled = true;
        try {
            const res = await fetch(`/api/marketplace/products/${pid}/model-sku`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ model_id: modelId, new_sku: newSku })
            });
            const dat = await res.json();
            if (!res.ok) throw new Error(dat.message || 'Gagal update SKU');
            
            m.model_sku = newSku;
            m.mapping = null; // SKU berubah → mapping lama tak berlaku lagi
            toast('SKU varian berhasil disimpan ✔');
            render(); // tampilkan tombol Map langsung tanpa reload/pindah tab
        } catch (e) {
            alert('Gagal simpan SKU: ' + e.message);
            inp.value = m.model_sku || '';
            inp.disabled = false;
        }
    };

    function updateVariantMappingUi(pid, modelId, model) {
        const row = [...document.querySelectorAll(`tr.mr-${pid}`)]
            .find(el => String(el.dataset.modelId) === String(modelId));
        const mappingCell = row?.querySelector('.variant-mapping-cell');
        if (mappingCell) mappingCell.innerHTML = mappingBadge(model);

        const product = products.find(p => String(p.id) === String(pid));
        const productCell = document.querySelector(`tr[data-pid="${pid}"] [data-product-mapping="${pid}"]`);
        if (product && productCell) {
            productCell.innerHTML = product.has_model
                ? mappingSummary(product.models || [])
                : (product.models?.length ? mappingBadge(product.models[0]) : '—');
        }
    }

    async function autoMapVariantSelection(input, item) {
        const pid = input.dataset.productId;
        const modelId = input.dataset.modelId;
        const product = products.find(p => String(p.id) === String(pid));
        const model = product?.models?.find(m => String(m.model_id) === String(modelId));
        const sku = (item.code || '').trim();
        const saveButton = input.closest('.variant-sku-suggest')?.querySelector('button[title="Simpan SKU"]');

        if (!product || !model || !sku || !item.id) return;

        const previousSku = model.model_sku || '';
        const previousMapping = model.mapping || null;
        const originalButtonText = saveButton?.innerHTML || '💾';
        let skuSaved = false;

        input.disabled = true;
        if (saveButton) {
            saveButton.disabled = true;
            saveButton.innerHTML = '⏳';
        }

        try {
            // Pilihan autosuggest juga langsung menyimpan SKU varian ke marketplace.
            if (previousSku !== sku) {
                const skuResponse = await fetch(`/api/marketplace/products/${pid}/model-sku`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ model_id: modelId, new_sku: sku })
                });
                const skuData = await skuResponse.json().catch(() => ({}));
                if (!skuResponse.ok) throw new Error(skuData.message || 'Gagal update SKU');

                model.model_sku = sku;
                model.mapping = null;
                skuSaved = true;
            }

            // Buat/update mapping tanpa memuat ulang seluruh tabel.
            const mapping = await api('/api/sku-mappings', {
                method: 'POST',
                body: JSON.stringify({
                    marketplace_sku: sku,
                    channel_code: null,
                    item_id: Number(item.id),
                    notes: 'auto-map dari autosuggest SKU varian'
                })
            });

            model.mapping = {
                id: mapping.id,
                item_id: mapping.item_id ?? mapping.item?.id ?? Number(item.id),
                item_code: mapping.item?.code ?? item.code,
                item_name: mapping.item?.name ?? item.name,
            };
            updateVariantMappingUi(pid, modelId, model);
            input.value = sku;
            toast(`Varian ${sku} tersimpan & auto-map ✔`);
        } catch (e) {
            if (!skuSaved) {
                input.value = previousSku;
                model.mapping = previousMapping;
            }
            updateVariantMappingUi(pid, modelId, model);
            toast(skuSaved ? `SKU tersimpan, mapping gagal: ${e.message}` : `Gagal auto-map: ${e.message}`, 'error');
        } finally {
            input.disabled = false;
            if (saveButton) {
                saveButton.disabled = false;
                saveButton.innerHTML = originalButtonText;
            }
        }
    }

    window.saveIndukSkuInline = async function (pid) {
        const inp = document.getElementById(`isku-${pid}`);
        const newSku = inp.value;
        const prod = products.find(p => p.id === pid);
        if (!prod || prod.item_sku === newSku) return;

        inp.disabled = true;
        try {
            const res = await fetch(`/api/marketplace/products/${pid}/sku`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ new_sku: newSku })
            });
            const dat = await res.json();
            if (!res.ok) throw new Error(dat.message || 'Gagal update SKU');
            
            prod.item_sku = newSku;
            prod.mapping = null;
            toast('Induk SKU berhasil disimpan ✔');
            
            if (!prod.has_model) {
                render();
            }
        } catch (e) {
            alert('Gagal simpan SKU: ' + e.message);
            inp.value = prod.item_sku || '';
        } finally {
            inp.disabled = false;
        }
    };

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

    let mapSearchTimer = null;
    let mapSelectedItem = null; // { id, code, name } item internal yang dipilih

    window.selectMapItem = function (id, code, name) {
        mapSelectedItem = { id, code: code || '', name: name || '' };
        $('mapSelectedInternalId').value = id;
        $('mapItemSearch').value = code || name;
        $('mapItemSelected').textContent = '✓ Terpilih: ' + (code ? code + ' — ' : '') + name;
        $('mapItemResults').style.display = 'none';
        
        document.querySelectorAll('#mapRecoList .btn-outline-primary').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.id == id) btn.classList.add('active');
        });
    };

    window.submitMapping = async function () {
        const sku = $('mapSkuInput').value;
        const itemId = $('mapSelectedInternalId').value;
        const notes = $('mapNotes').value;

        if (!itemId) return alert('Silakan cari dan pilih item internal terlebih dahulu.');

        const btn = $('mapSaveBtn');
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';

        try {
            const dat = await api('/api/sku-mappings', {
                method: 'POST',
                body: JSON.stringify({
                    marketplace_sku: sku,
                    channel_code: null,
                    item_id: parseInt(itemId),
                    notes: notes || 'mapping dari tab Produk'
                })
            });

            // Update state lokal → tabel langsung ter-update tanpa reload
            const map = {
                id:        dat.id,
                item_id:   dat.item_id ?? (dat.item && dat.item.id) ?? parseInt(itemId),
                item_code: (dat.item && dat.item.code) ?? mapSelectedItem?.code ?? '',
                item_name: (dat.item && dat.item.name) ?? mapSelectedItem?.name ?? '',
            };
            products.forEach(p => (p.models || []).forEach(m => {
                if (m.model_sku === sku) m.mapping = map;
            }));
            products.forEach(p => {
                if (p.item_sku === sku) p.mapping = map;
            });

            toast(`SKU ${sku} berhasil di-mapping ✔`);
            render();
            bootstrap.Modal.getInstance($('modalMapping')).hide();
        } catch (e) {
            alert('Gagal simpan mapping: ' + e.message); 
        } finally {
            btn.disabled = false;
            btn.textContent = 'Simpan Mapping';
        }
    };

    window.openMapModal = async function (sku) {
        $('mapSelectedInternalId').value = '';
        $('mapSkuInput').value = sku || '';
        $('mapItemSearch').value = '';
        $('mapItemSelected').textContent = '';
        $('mapItemResults').style.display = 'none';
        $('mapRecommendations').style.display = 'none';
        $('mapRecoList').innerHTML = '';
        $('mapNotes').value = '';

        new bootstrap.Modal($('modalMapping')).show();

        if (!$('mapItemSearch').dataset.inited) {
            $('mapItemSearch').dataset.inited = '1';
            $('mapItemSearch').addEventListener('input', function () {
                clearTimeout(mapSearchTimer);
                const q = this.value.trim();
                if (q.length < 2) { $('mapItemResults').style.display = 'none'; return; }
                mapSearchTimer = setTimeout(async () => {
                    const items = await api('/api/sku-mappings/search-items?q=' + encodeURIComponent(q)).catch(() => []);
                    const box = $('mapItemResults');
                    if (!items.length) { box.style.display = 'none'; return; }
                    box.style.display = 'block';
                    box.innerHTML = items.map(i =>
                        `<div class="p-2 border-bottom text-dark" style="cursor:pointer;font-size:.82rem;background:#fff"
                            onmousedown="selectMapItem(${i.id},'${esc(i.code||'')}','${esc(i.name)}')" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#fff'">
                            <strong>${esc(i.code||'')}</strong>${i.code ? ' — ' : ''}${esc(i.name)}
                        </div>`
                    ).join('');
                }, 250);
            });
        }

        if (sku && sku.length >= 2) {
            const queries = new Set([sku, sku.split('-')[0], sku.replace(/[-_]\d+$/, '')]);
            let results = [];
            for (const q of queries) {
                if (!q || q.length < 2) continue;
                const items = await api('/api/sku-mappings/search-items?q=' + encodeURIComponent(q)).catch(() => []);
                items.forEach(i => { if (!results.find(r => r.id === i.id)) results.push(i); });
                if (results.length >= 5) break;
            }
            if (results.length) {
                $('mapRecommendations').style.display = 'block';
                $('mapRecoList').innerHTML = results.slice(0, 5).map(i =>
                    `<button type="button" class="btn btn-sm btn-outline-primary text-start" data-id="${i.id}" style="font-size:.7rem;border-radius:10px"
                        onclick="selectMapItem(${i.id},'${esc(i.code||'')}','${esc(i.name)}')">
                        <b>${esc(i.code||i.name)}</b><br><span style="font-size:.65rem">${esc(i.name)}</span>
                    </button>`
                ).join('');
            }
        }
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
    $('fSearch').addEventListener('input', render);
    ['fStatus','fMapping','fSort'].forEach(id => {
        if ($(id)) $(id).addEventListener('change', render);
    });

    // ══════════════════════════════════════════════════════════════════════
    // TAB: Stok — kelola & ubah stok cepat (reuse `products`, `api`, `esc`)
    // ══════════════════════════════════════════════════════════════════════
    let stStore = '';               // filter toko aktif di tab Stok
    const stDirty = new Map();      // key `${pid}:${modelId}` -> nilai stok baru

    // Ratakan produk menjadi baris per varian (atau satu baris jika tanpa varian).
    function stFlatRows() {
        const rows = [];
        // Hanya produk aktif (tampil) — bukan yang diarsipkan/banned di marketplace.
        products.filter(p => p.item_status === 'NORMAL').forEach(p => {
            const models = p.models || [];
            if (models.length) {
                models.forEach(m => rows.push({
                    pid: p.id, product: p, modelId: String(m.model_id),
                    name: p.item_name, variant: m.model_name, sku: m.model_sku || p.item_sku,
                    stock: m.stock ?? 0, image: p.image_url, store: p.store,
                    sales: p.sales, status: p.item_status, mapping: m.mapping || null,
                }));
            } else {
                rows.push({
                    pid: p.id, product: p, modelId: '0',
                    name: p.item_name, variant: null, sku: p.item_sku,
                    stock: p.stock_total ?? 0, image: p.image_url, store: p.store,
                    sales: p.sales, status: p.item_status, mapping: p.mapping || null,
                });
            }
        });
        return rows;
    }

    function stBuildStores() {
        const stores = [...new Map(products.filter(p => p.store).map(p => [p.store.id, p.store.name])).entries()];
        let html = `<button class="store-tab ${stStore === '' ? 'active' : ''}" onclick="stSetStore('')">Semua Toko</button>`;
        stores.forEach(([id, name]) => {
            html += `<button class="store-tab ${stStore === String(id) ? 'active' : ''}" onclick="stSetStore('${id}')">${esc(name)}</button>`;
        });
        if ($('stStoreTabs')) $('stStoreTabs').innerHTML = html;
    }

    window.stSetStore = function (id) { stStore = String(id); stRender(); };

    function stFilteredRows() {
        const q = $('stSearch').value.trim().toLowerCase();
        const band = $('stStock').value;
        return stFlatRows().filter(r => {
            if (stStore && String(r.store?.id) !== stStore) return false;
            const s = r.stock ?? 0;
            if (band === 'empty' && s !== 0) return false;
            if (band === 'low'   && !(s > 0 && s <= 5)) return false;
            if (band === 'in'    && s <= 0) return false;
            if (q) {
                const hay = [r.name, r.sku, r.variant, r.product.item_id,
                    r.mapping?.item_name, r.mapping?.item_code, r.mapping?.category_name]
                    .filter(Boolean).join(' ').toLowerCase();
                if (!hay.includes(q)) return false;
            }
            return true;
        });
    }

    function stSortRows(rows) {
        const sort = $('stSort').value;
        const num = v => v == null ? -1 : Number(v);
        rows.sort((a, b) => {
            switch (sort) {
                case 'stock_desc': return num(b.stock) - num(a.stock);
                case 'sales':      return num(b.sales) - num(a.sales);
                case 'name':       return (a.name || '').localeCompare(b.name || '');
                default:           return num(a.stock) - num(b.stock); // stock_asc
            }
        });
        return rows;
    }

    function stStockClass(n) {
        if ((n ?? 0) === 0) return 'stock-zero';
        if (n <= 5) return 'stock-low';
        return '';
    }

    function stRenderKpis() {
        const rows = stFlatRows().filter(r => !stStore || String(r.store?.id) === stStore);
        const totalSku = rows.length;
        const empty = rows.filter(r => (r.stock ?? 0) === 0).length;
        const low   = rows.filter(r => (r.stock ?? 0) > 0 && (r.stock ?? 0) <= 5).length;
        const units = rows.reduce((s, r) => s + (Number(r.stock) || 0), 0);
        const kpi = (lbl, val) => `<span class="kpi" style="cursor:default"><span class="lbl">${lbl}</span><span class="val">${val}</span></span>`;
        if ($('stKpiRow')) $('stKpiRow').innerHTML =
            kpi('Total SKU', totalSku) +
            kpi('Stok Habis', empty) +
            kpi('Menipis', low) +
            kpi('Total Unit', units.toLocaleString('id-ID'));
    }

    function stUpdateDirtyCount() {
        if ($('stDirtyCount')) $('stDirtyCount').textContent = stDirty.size;
        if ($('stSaveAll')) $('stSaveAll').disabled = stDirty.size === 0;
    }

    // ── Grouping helpers (kategori & nama produk hasil mapping) ──────────
    const stExpanded = new Set(); // kategori yang sedang dibuka (default: semua tertutup)

    function stCatKey(r) {
        if (!r.mapping) return '❓ Belum di-mapping';
        return r.mapping.category_name || '📦 Tanpa Kategori';
    }
    function stItemKey(r) {
        if (!r.mapping) return r.name || '—';
        return (r.mapping.item_code ? r.mapping.item_code + ' · ' : '') + (r.mapping.item_name || ('Item #' + r.mapping.item_id));
    }

    function stInputClass(n) {
        if ((n ?? 0) === 0) return 'st-inp-zero';
        if (n <= 5) return 'st-inp-low';
        return '';
    }

    function stRowHtml(r, compact) {
        const key = `${r.pid}:${r.modelId}`;
        const dirty = stDirty.has(key);
        const inputVal = dirty ? stDirty.get(key) : (r.stock ?? 0);
        const line1 = compact ? (r.variant ? '↳ ' + esc(r.variant) : esc(r.name || '—')) : esc(r.name || '—');
        const meta = [];
        if (!compact && r.variant) meta.push('↳ ' + esc(r.variant));
        meta.push('SKU: ' + esc(r.sku || '—'));
        if (r.store?.name) meta.push(esc(r.store.name));
        const gd = r.mapping;
        const gdAvail = (gd && gd.internal_available != null) ? gd.internal_available : null;
        const gdCls = gdAvail == null ? 'muted' : (gdAvail <= 0 ? 'stock-zero' : (gdAvail <= 5 ? 'stock-low' : ''));
        const gdTip = gdAvail == null ? 'Belum di-mapping ke item internal' : `Fisik: ${(gd.internal_physical ?? 0).toLocaleString('id-ID')} · Dialokasi: ${(gd.internal_allocated ?? 0).toLocaleString('id-ID')}`;
        const gdTxt = gdAvail == null ? '—' : gdAvail.toLocaleString('id-ID');
        return `<tr data-st-key="${key}" class="${dirty ? 'st-dirty-row' : ''} ${compact ? 'st-grp-row' : ''}">
            <td>${r.image ? `<img class="prd-img" src="${esc(r.image)}" loading="lazy">` : '<div class="prd-img"></div>'}</td>
            <td>
                <div class="prd-name" style="-webkit-line-clamp:1" title="${esc(r.name || '')}">${line1}</div>
                <div class="prd-sku">${meta.join(' · ')}</div>
            </td>
            <td class="st-gd-cell"><span class="${gdCls}" title="${gdTip}">${gdTxt}</span></td>
            <td>
                <div class="st-stok-cell">
                    <input type="number" min="0" class="form-control form-control-sm st-stock-input ${stInputClass(r.stock)}" value="${inputVal}" data-pid="${r.pid}" data-model-id="${r.modelId}" data-orig="${r.stock ?? 0}" title="Stok sekarang: ${r.stock ?? 0}">
                    <button class="btn btn-outline-primary btn-mini st-save-btn" onclick="stSaveOne('${key}', this)" title="Simpan ke Shopee">💾</button>
                </div>
            </td>
        </tr>`;
    }

    window.stRender = function () {
        stBuildStores();
        stRenderKpis();
        const grouped = $('stGroup') ? $('stGroup').checked : true;
        const rows = stFilteredRows();
        if (!rows.length) {
            $('stBody').innerHTML = `<tr><td colspan="4" class="empty">${products.length ? 'Tidak ada produk yang cocok dengan filter.' : 'Belum ada produk. Klik "⟳ Sync Shopee".'}</td></tr>`;
            stUpdateDirtyCount();
            return;
        }

        if (!grouped) {
            $('stBody').innerHTML = stSortRows(rows).map(r => stRowHtml(r, false)).join('');
            stUpdateDirtyCount();
            return;
        }

        // Grouping 2 tingkat: Kategori (mapping) → Nama produk (mapping) → varian
        const cats = new Map(); // catName -> Map(itemKey -> rows[])
        rows.forEach(r => {
            const ck = stCatKey(r), ik = stItemKey(r);
            if (!cats.has(ck)) cats.set(ck, new Map());
            const items = cats.get(ck);
            if (!items.has(ik)) items.set(ik, []);
            items.get(ik).push(r);
        });

        const catNames = [...cats.keys()].sort((a, b) => {
            const au = a.startsWith('❓'), bu = b.startsWith('❓'); // bucket belum-mapping paling bawah
            if (au !== bu) return au ? 1 : -1;
            return a.localeCompare(b);
        });

        let html = '';
        catNames.forEach(ck => {
            const items = cats.get(ck);
            let catUnits = 0, catEmpty = 0, catSku = 0;
            items.forEach(rs => rs.forEach(r => { catUnits += Number(r.stock) || 0; if ((r.stock ?? 0) === 0) catEmpty++; catSku++; }));
            const expanded = stExpanded.has(ck);
            html += `<tr class="st-cat-head" data-cat="${esc(ck)}">
                <td colspan="4"><div class="st-cat-inner">
                    <span class="st-caret">${expanded ? '▾' : '▸'}</span>
                    <span class="st-cat-name">${esc(ck)}</span>
                    <span class="st-cat-meta">${items.size} produk · ${catSku} SKU</span>
                    <span class="st-cat-sum">${catEmpty ? `<span class="st-badge-empty">${catEmpty} habis</span>` : ''}<b>${catUnits.toLocaleString('id-ID')}</b> unit</span>
                </div></td></tr>`;
            if (!expanded) return;

            [...items.keys()].sort((a, b) => a.localeCompare(b)).forEach(ik => {
                const rs = stSortRows(items.get(ik));
                const itUnits = rs.reduce((s, r) => s + (Number(r.stock) || 0), 0);
                const itEmpty = rs.filter(r => (r.stock ?? 0) === 0).length;
                const itInternal = rs[0]?.mapping?.internal_available;
                const itIntTxt = (itInternal == null) ? '—' : itInternal.toLocaleString('id-ID');
                const itIntCls = (itInternal == null) ? 'muted' : (itInternal <= 0 ? 'stock-zero' : (itInternal <= 5 ? 'stock-low' : ''));
                html += `<tr class="st-item-head">
                    <td></td>
                    <td><span class="st-item-name">${esc(ik)}</span> <span class="muted">· ${rs.length} varian</span></td>
                    <td class="st-gd-cell"><span class="${itIntCls}" title="Stok gudang internal">${itIntTxt}</span></td>
                    <td style="text-align:right"><span class="${itEmpty ? 'stock-low' : 'muted'}" title="${itEmpty} varian habis">${itUnits.toLocaleString('id-ID')} unit</span></td>
                </tr>`;
                html += rs.map(r => stRowHtml(r, true)).join('');
            });
        });
        $('stBody').innerHTML = html;
        stUpdateDirtyCount();
    };

    // Lacak perubahan input stok (event delegation — baris dirender ulang tiap saat).
    document.addEventListener('input', e => {
        const inp = e.target.closest('.st-stock-input');
        if (!inp) return;
        const key = `${inp.dataset.pid}:${inp.dataset.modelId}`;
        const orig = Number(inp.dataset.orig);
        const val = inp.value === '' ? NaN : parseInt(inp.value);
        const row = inp.closest('tr');
        if (isNaN(val) || val === orig) {
            stDirty.delete(key);
            row && row.classList.remove('st-dirty-row');
        } else {
            stDirty.set(key, val);
            row && row.classList.add('st-dirty-row');
        }
        stUpdateDirtyCount();
    });

    // Simpan stok ke Shopee + sinkron state lokal supaya tab lain ikut ter-update.
    async function stPersist(pid, stockList) {
        await api(`${API}/${pid}/stock`, { method: 'POST', body: JSON.stringify({ stock_list: stockList }) });
        const p = products.find(x => x.id === pid);
        if (!p) return;
        if (p.models && p.models.length) {
            stockList.forEach(({ model_id, stock }) => {
                const m = p.models.find(x => String(x.model_id) === String(model_id));
                if (m) m.stock = stock;
            });
            p.stock_total = p.models.reduce((s, m) => s + (Number(m.stock) || 0), 0);
        } else if (stockList.length) {
            p.stock_total = stockList[0].stock;
        }
    }

    window.stSaveOne = async function (key, btn) {
        const [pid, modelId] = key.split(':');
        const inp = document.querySelector(`.st-stock-input[data-pid="${pid}"][data-model-id="${modelId}"]`);
        if (!inp) return;
        const val = parseInt(inp.value);
        if (isNaN(val) || val < 0) return alert('Stok tidak valid');
        const orig = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '⏳'; }
        try {
            await stPersist(Number(pid), [{ model_id: modelId, stock: val }]);
            stDirty.delete(key);
            toast('Stok tersimpan ke Shopee ✔');
            stRender();
        } catch (e) {
            alert('Gagal simpan stok: ' + e.message);
            if (btn) { btn.disabled = false; btn.innerHTML = orig; }
        }
    };

    window.stSaveAll = async function () {
        if (!stDirty.size) return;
        const btn = $('stSaveAll');
        const groups = {};
        stDirty.forEach((stock, key) => {
            const [pid, modelId] = key.split(':');
            (groups[pid] ||= []).push({ model_id: modelId, stock });
        });
        const origHtml = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Menyimpan…'; }
        let ok = 0, fail = 0;
        for (const [pid, list] of Object.entries(groups)) {
            try {
                await stPersist(Number(pid), list);
                list.forEach(l => stDirty.delete(`${pid}:${l.model_id}`));
                ok += list.length;
            } catch (e) { fail += list.length; }
        }
        if (btn) btn.innerHTML = origHtml;
        toast(fail ? `${ok} stok tersimpan, ${fail} gagal` : `${ok} stok tersimpan ke Shopee ✔`, fail ? 'warning' : 'success');
        stRender();
    };

    // Event listener filter tab Stok
    ['stSearch','stStock','stSort'].forEach(id => {
        if ($(id)) $(id).addEventListener(id === 'stSearch' ? 'input' : 'change', stRender);
    });
    if ($('stGroup')) $('stGroup').addEventListener('change', stRender);
    // Lipat/buka kategori (event delegation; tbody dirender ulang tiap saat)
    if ($('stBody')) $('stBody').addEventListener('click', e => {
        const head = e.target.closest('.st-cat-head');
        if (!head) return;
        const ck = head.dataset.cat;
        if (stExpanded.has(ck)) stExpanded.delete(ck); else stExpanded.add(ck);
        stRender();
    });

    // ══════════════════════════════════════════════════════════════════════
    // TAB: Naikkan Produk (Boost) — memakai ulang `products`, `api`, `esc`
    // ══════════════════════════════════════════════════════════════════════
    const BAPI = '/api/marketplace/boost';
    let boLoaded = false;

    window.switchTab = function (tab) {
        document.querySelectorAll('.pt-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
        $('tabProduk').classList.toggle('active', tab === 'produk');
        $('tabStok').classList.toggle('active', tab === 'stok');
        $('tabBoost').classList.toggle('active', tab === 'boost');
        if (tab === 'stok') stRender();
        if (tab === 'boost') {
            boBuildStores();
            boLoaded = true;
            boReload();
        }
    };

    const boStoreId = () => $('boStore').value;
    const fmtT = d => d ? new Date(d).toLocaleString('id-ID', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' }) : '—';
    function remLabel(mins) {
        if (mins == null || mins <= 0) return 'aktif';
        const h = Math.floor(mins / 60), m = mins % 60;
        return (h ? h + 'j ' : '') + m + 'm lagi';
    }

    function boBuildStores() {
        const sel = $('boStore'), cur = sel.value;
        const stores = [...new Map(products.filter(p => p.store).map(p => [p.store.id, p.store.name])).entries()];
        sel.innerHTML = stores.map(([id, name]) => `<option value="${id}">${esc(name)}</option>`).join('') || '<option value="">— tidak ada toko —</option>';
        if (cur) sel.value = cur;
        if (!sel.dataset.bound) { sel.addEventListener('change', boReload); sel.dataset.bound = '1'; }
    }

    function boFillPickers() {
        const sid = String(boStoreId());
        const opts = '<option value="">— pilih produk —</option>' +
            products.filter(p => String(p.store?.id) === sid && p.item_status === 'NORMAL')
                .map(p => `<option value="${p.id}">${esc((p.item_name || 'item ' + p.item_id).slice(0, 60))}${p.item_sku ? ' · ' + esc(p.item_sku) : ''}</option>`).join('');
        document.querySelectorAll('#tabBoost .prod-picker').forEach(el => el.innerHTML = opts);
    }

    window.boReload = function () {
        boFillPickers();
        boStatus(); boScheds(); boPool(); boLogs();
    };

    async function boStatus() {
        $('boSlots').textContent = '…';
        $('boSlotBar').innerHTML = ''; $('boBoosted').innerHTML = '';
        let d;
        try { d = await api(`${BAPI}/status?store_id=${boStoreId()}`); }
        catch (e) { $('boSlots').textContent = ''; $('boBoosted').innerHTML = `<span class="bo-empty text-danger">${esc(e.message)}</span>`; return; }
        if (d.error) { $('boSlots').textContent = ''; $('boBoosted').innerHTML = `<span class="bo-empty text-danger">${esc(d.error)}</span>`; return; }

        const used = d.used || (d.items || []).length, max = d.max || 5;
        $('boSlots').textContent = `${used}/${max} slot terpakai`;
        let bar = '';
        for (let i = 0; i < max; i++) bar += `<div class="bo-slot ${i < used ? 'filled' : ''}">${i < used ? '🚀' : ''}</div>`;
        $('boSlotBar').innerHTML = bar;
        $('boBoosted').innerHTML = (d.items || []).length
            ? d.items.map(it => `<div class="bo-chip">${it.image_url ? `<img src="${esc(it.image_url)}">` : ''}<div><div class="nm" title="${esc(it.name)}">${esc(it.name)}</div><div class="rm">${remLabel(it.remaining_minutes)}</div></div></div>`).join('')
            : '<span class="bo-empty">Belum ada produk yang di-boost.</span>';
    }

    async function boScheds() {
        const c = $('boSchedList'); c.innerHTML = '<div class="bo-empty">Memuat…</div>';
        let d;
        try { d = await api(`${BAPI}/schedules?store_id=${boStoreId()}`); }
        catch (e) { c.innerHTML = `<div class="bo-empty text-danger">${esc(e.message)}</div>`; return; }
        const byProd = {};
        (d.schedules || []).forEach(s => { (byProd[s.product_id] ||= { name:s.product, sku:s.sku, img:s.image_url, rows:[] }).rows.push(s); });
        const keys = Object.keys(byProd);
        c.innerHTML = keys.length ? keys.map(k => {
            const g = byProd[k];
            const pills = g.rows.sort((a,b)=>a.time.localeCompare(b.time)).map(r =>
                `<span class="bo-timepill ${r.is_active ? '' : 'off'}">${r.time}
                    <a href="javascript:boToggleSched(${r.id})" title="aktif/nonaktif">${r.is_active ? '⏸' : '▶'}</a>
                    <a href="javascript:boDelSched(${r.id})" title="hapus" style="color:#dc2626">✕</a></span>`).join('');
            return `<div class="bo-row">${g.img ? `<img src="${esc(g.img)}">` : ''}<div class="grow"><div class="nm">${esc(g.name || '—')}</div><div class="meta">${esc(g.sku || '')}</div></div><div style="text-align:right">${pills}</div></div>`;
        }).join('') : '<div class="bo-empty">Belum ada jadwal.</div>';
    }

    window.boAddTime = function () {
        const el = document.createElement('input');
        el.type = 'time'; el.className = 'bo-t'; el.value = '20:00';
        $('boSchedTimes').appendChild(el);
    };

    window.boSaveSched = async function () {
        const pid = parseInt($('boSchedPick').value);
        if (!pid) return alert('Pilih produk dulu.');
        const times = [...document.querySelectorAll('#boSchedTimes .bo-t')].map(i => i.value).filter(Boolean);
        if (!times.length) return alert('Isi minimal satu jam.');
        try {
            const res = await api(`${BAPI}/schedules`, { method:'POST', body: JSON.stringify({ store_id: boStoreId(), marketplace_product_id: pid, times }) });
            toast(res.message);
            $('boSchedPick').value = '';
            $('boSchedTimes').innerHTML = '<input type="time" class="bo-t" value="08:00">';
            boScheds();
        } catch (e) { toast('Gagal: ' + e.message, 'error'); }
    };

    window.boToggleSched = async function (id) { await api(`${BAPI}/schedules/${id}/toggle`, { method:'POST' }); boScheds(); };
    window.boDelSched = async function (id) { if (!confirm('Hapus slot jadwal ini?')) return; await api(`${BAPI}/schedules/${id}`, { method:'DELETE' }); boScheds(); };

    async function boPool() {
        const c = $('boPoolList'); c.innerHTML = '<div class="bo-empty">Memuat…</div>';
        let d;
        try { d = await api(`${BAPI}/pool?store_id=${boStoreId()}`); }
        catch (e) { c.innerHTML = `<div class="bo-empty text-danger">${esc(e.message)}</div>`; return; }
        c.innerHTML = (d.pool || []).length ? d.pool.map(p => `
            <div class="bo-row ${p.is_active ? '' : 'bo-off'}">${p.image_url ? `<img src="${esc(p.image_url)}">` : ''}
                <div class="grow"><div class="nm">${esc(p.product || '—')}</div>
                    <div class="meta">terakhir naik: ${p.last_boosted_at ? fmtT(p.last_boosted_at) : 'belum pernah'}</div></div>
                <button class="btn btn-prd-outline btn-mini" onclick="boTogglePool(${p.id})">${p.is_active ? '⏸' : '▶'}</button>
                <button class="btn btn-outline-danger btn-mini" onclick="boDelPool(${p.id})">✕</button></div>`).join('')
            : '<div class="bo-empty">Antrian kosong.</div>';
    }

    window.boAddPool = async function () {
        const pid = parseInt($('boPoolPick').value);
        if (!pid) return alert('Pilih produk dulu.');
        try {
            const res = await api(`${BAPI}/pool`, { method:'POST', body: JSON.stringify({ store_id: boStoreId(), product_ids: [pid] }) });
            toast(res.message); $('boPoolPick').value = ''; boPool();
        } catch (e) { toast('Gagal: ' + e.message, 'error'); }
    };

    window.boTogglePool = async function (id) { await api(`${BAPI}/pool/${id}/toggle`, { method:'POST' }); boPool(); };
    window.boDelPool = async function (id) { if (!confirm('Keluarkan dari antrian?')) return; await api(`${BAPI}/pool/${id}`, { method:'DELETE' }); boPool(); };

    async function boLogs() {
        const c = $('boLogs'); c.innerHTML = '<div class="bo-empty">Memuat…</div>';
        let d;
        try { d = await api(`${BAPI}/logs?store_id=${boStoreId()}`); }
        catch (e) { c.innerHTML = `<div class="bo-empty text-danger">${esc(e.message)}</div>`; return; }
        const rows = (d.logs || []).slice(0, 50);
        c.innerHTML = rows.length ? rows.map(l => `
            <div class="bo-row"><span class="bo-tag tag-${l.source}">${l.source}</span>
                <div class="grow"><div class="nm">${esc(l.product)}</div>
                    <div class="meta">${l.success ? '' : '⚠ ' + esc(l.message || 'gagal') + ' · '}${fmtT(l.boosted_at)}</div></div>
                <span>${l.success ? '✅' : '❌'}</span></div>`).join('')
            : '<div class="bo-empty">Belum ada riwayat.</div>';
    }

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
