const fs = require('fs');
const path = '/Users/ariefmuhamad/Herd/gfid-dev/resources/views/marketplace/ads.blade.php';
let content = fs.readFileSync(path, 'utf8');

// The goal is to completely rewrite the blade structure while keeping JS logic intact but adapted.
const newContent = `@extends('layouts.app')
@section('title', 'Marketplace • Analisa Iklan')

@include('marketplace._shared')

@push('head')
<style>
    /* ── Shipments UI Match ─────────────────────────────────────────── */
    :root{
        --shp-accent:#334155;
        --shp-accent-2:#1f2937;
        --shp-border:rgba(148,163,184,.18);
        --shp-border-strong:rgba(148,163,184,.30);
        --shp-muted:#64748b;
    }
    .page-wrap{ max-width:1040px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }

    .card-main{
        background: var(--card); border-radius: 8px; border: 1px solid var(--shp-border);
        box-shadow: none; overflow:hidden; margin-bottom: 1rem;
    }
    body[data-theme="dark"] .card-main{ border-color: rgba(51,65,85,.85); box-shadow: none; background:var(--card,#0f172a); }

    .ship-topbar{
        position:sticky; top:0; z-index:300; display:flex; justify-content:space-between; align-items:center; gap:.6rem; flex-wrap:wrap;
        padding:.45rem .75rem; margin-inline:-.75rem; margin-bottom:.65rem; background:var(--card,#fff); border-bottom:1px solid var(--shp-border);
    }
    body[data-theme="dark"] .ship-topbar{ background:var(--card,#0f172a); }
    .title{ font-weight: 750; font-size:1rem; letter-spacing: 0; margin:0; }
    .sub{ color:var(--shp-muted); font-size:.78rem; }
    body[data-theme="dark"] .sub{ color:#9ca3af; }

    .kpis{ display:flex; flex-wrap:wrap; gap:.32rem; margin-top:.35rem; }
    .kpi{
        display:inline-flex; align-items:baseline; gap:.45rem; border-radius:7px; padding:.2rem .48rem;
        border:1px solid rgba(148,163,184,.28); background: transparent; font-size:.72rem;
    }
    body[data-theme="dark"] .kpi{ background: rgba(15, 23, 42, 0.96); border-color: rgba(51, 65, 85, 0.85); }
    .kpi .lbl{ text-transform:none; letter-spacing:0; font-size:.66rem; color:#94a3b8; }
    body[data-theme="dark"] .kpi .lbl{ color:#6b7280; }
    .kpi .val{ font-weight:650; color:var(--shp-accent); }
    body[data-theme="dark"] .kpi .val{ color:#e2e8f0; }

    .controls{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
    .filter-label{ font-size:.8rem; color:#6b7280; }
    body[data-theme="dark"] .filter-label{ color:#9ca3af; }
    .filter-select, .filter-input { 
        border-radius:7px; font-size:.82rem; border: 1px solid var(--shp-border); 
        padding:.3rem .6rem; background:var(--card,#fff); color:inherit; outline:none;
    }
    body[data-theme="dark"] .filter-select, body[data-theme="dark"] .filter-input { background: rgba(15, 23, 42, 0.98); }
    .filter-select:focus, .filter-input:focus { border-color:var(--shp-accent); }
    
    .btn-pill{ border-radius:7px; padding-inline:.78rem; box-shadow:none!important; font-weight:600; }
    .btn-ship-primary{ background:var(--shp-accent)!important; border-color:var(--shp-accent)!important; color:#fff!important; }
    .btn-ship-primary:hover{ background:var(--shp-accent-2)!important; border-color:var(--shp-accent-2)!important; color:#fff!important; }
    .btn-ship-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-ship-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }
    body[data-theme="dark"] .btn-ship-outline{ color:#9ca3af!important; }
    body[data-theme="dark"] .btn-ship-outline:hover{ color:#e2e8f0!important; background:rgba(148,163,184,.15)!important; }

    .table-list{ margin-bottom:0; width:100%; border-collapse: collapse; }
    .table-list thead th{
        border-bottom:1px solid var(--shp-border); font-size:.68rem; text-transform:none; letter-spacing:0;
        color:#64748b; background: var(--card,#fff); padding:.52rem .62rem; white-space:nowrap;
    }
    body[data-theme="dark"] .table-list thead th{ background: rgba(15, 23, 42, 0.98); color:#9ca3af; }
    .table-list tbody td{ vertical-align:middle; border-top: 1px solid rgba(148, 163, 184, 0.16); padding:.52rem .62rem; }
    body[data-theme="dark"] .table-list tbody td{ border-top-color: rgba(51, 65, 85, 0.85); }
    .table-list tbody tr:hover td { background:rgba(148,163,184,.05); }

    .table-list th.sortable { cursor:pointer; user-select:none; }
    .table-list th.sortable:hover { color:#0f172a; }
    body[data-theme="dark"] .table-list th.sortable:hover { color:#e2e8f0; }
    .table-list th .sort-icon { margin-left:.25rem; opacity:.35; font-size:.7rem; }
    .table-list th.sort-asc .sort-icon::after  { content:'↑'; opacity:1; }
    .table-list th.sort-desc .sort-icon::after { content:'↓'; opacity:1; }
    .table-list th:not(.sort-asc):not(.sort-desc) .sort-icon::after { content:'⇅'; }

    .reco-badge {
        display: inline-flex; align-items: center; gap: .3rem;
        font-size: .72rem; font-weight: 800; padding: .2rem .65rem;
        border-radius: 999px; white-space: nowrap;
    }
    .reco-scale  { background: rgba(22,163,74,.12); color: #15803d; }
    .reco-ok     { background: rgba(37,99,235,.1);  color: #1d4ed8; }
    .reco-warn   { background: rgba(217,119,6,.12); color: #b45309; }
    .reco-stop   { background: rgba(185,28,28,.1);  color: #b91c1c; }
    .reco-nodata { background: rgba(148,163,184,.12); color: #64748b; }

    .acos-bar-wrap { position:relative; height:5px; border-radius:999px; background:#f1f5f9; overflow:visible; margin-top:3px; min-width:60px; }
    body[data-theme="dark"] .acos-bar-wrap { background:#334155; }
    .acos-bar-fill { position:absolute; left:0; top:0; height:100%; border-radius:999px; transition:width .3s; }
    .acos-bar-be   { position:absolute; top:-2px; width:2px; height:9px; background:#0f172a; border-radius:1px; }
    body[data-theme="dark"] .acos-bar-be { background:#e2e8f0; }

    .period-tabs { display:flex; gap:.2rem; flex-wrap:wrap; }
    .period-tab {
        font-size:.75rem; font-weight:700; padding:.28rem .7rem; border-radius:999px;
        border:1px solid transparent; background:transparent; color:var(--shp-muted); cursor:pointer; transition:all .15s;
    }
    .period-tab.active, .period-tab:hover { background:rgba(148,163,184,.1); color:var(--shp-accent); border-color:var(--shp-border); }
    body[data-theme="dark"] .period-tab.active, body[data-theme="dark"] .period-tab:hover { color:#e2e8f0; }

    .toggle-switch { display:flex; align-items:center; gap:.4rem; font-size:.78rem; color:#475569; font-weight:600; cursor:pointer; }
    body[data-theme="dark"] .toggle-switch { color:#9ca3af; }
    .toggle-switch input { width:32px; height:18px; accent-color:#0f172a; cursor:pointer; }

    .type-pill {
        display:inline-block; font-size:.65rem; font-weight:700; padding:.1rem .45rem;
        border-radius:4px; background:#f1f5f9; color:#64748b; text-transform:uppercase; letter-spacing:.03em;
    }
    body[data-theme="dark"] .type-pill { background:#1e293b; color:#94a3b8; }

    .roas-chip { display:inline-block; font-weight:900; font-size:.88rem; padding:.05rem .4rem; border-radius:6px; }
    .roas-good { background:#dcfce7; color:#15803d; }
    .roas-ok   { background:#e0f2fe; color:#0369a1; }
    .roas-bad  { background:#fee2e2; color:#b91c1c; }
    .roas-none { color:#94a3b8; }
    body[data-theme="dark"] .roas-good { background:rgba(22,163,74,.2); color:#4ade80; }
    body[data-theme="dark"] .roas-ok { background:rgba(37,99,235,.2); color:#60a5fa; }
    body[data-theme="dark"] .roas-bad { background:rgba(220,38,38,.2); color:#f87171; }

    .row-inactive td { opacity:.4; }
    
    .ads-pager { display:flex; align-items:center; gap:.4rem; justify-content:flex-end; padding:.6rem; flex-wrap:wrap; }
    .ads-pager-btn {
        font-size:.75rem; font-weight:700; padding:.25rem .65rem; border-radius:8px;
        border:1px solid var(--shp-border); background:var(--card,#fff); cursor:pointer; color:#475569; transition:all .12s;
    }
    body[data-theme="dark"] .ads-pager-btn { background:rgba(15,23,42,0.98); color:#9ca3af; }
    .ads-pager-btn:hover:not(:disabled) { background:var(--shp-accent); color:#fff; border-color:var(--shp-accent); }
    .ads-pager-btn:disabled { opacity:.4; cursor:default; }
    .ads-pager-btn.active { background:var(--shp-accent); color:#fff; border-color:var(--shp-accent); }
    .ads-pager-info { font-size:.75rem; color:#64748b; }
</style>
@endpush

@section('content')
<div class="page-wrap">
    <div class="ship-topbar">
        <div>
            <div class="title">Analisa Iklan</div>
            <div class="sub">Performa campaign Ads</div>

            <div class="kpis">
                <span class="kpi" title="Sisa kredit iklan"><span class="lbl">Saldo</span><span class="val" id="kpiBalance">—</span></span>
                <span class="kpi"><span class="lbl">Spend</span><span class="val" id="kpiSpend">—</span></span>
                <span class="kpi"><span class="lbl">Sales</span><span class="val" id="kpiGmv">—</span></span>
                <span class="kpi"><span class="lbl">ROAS</span><span class="val" id="kpiRoas">—</span></span>
                <span class="kpi"><span class="lbl">ACOS</span><span class="val" id="kpiAcos">—</span></span>
                <span class="kpi"><span class="lbl">Orders</span><span class="val" id="kpiOrders">—</span></span>
                <span class="kpi" title="Gross profit - spend"><span class="lbl">Profit</span><span class="val" id="kpiProfit">—</span></span>
            </div>
        </div>

        <div class="controls">
            <select class="filter-select" id="adsStoreId"></select>
            <input type="date" class="filter-input" id="dateFrom">
            <span class="filter-label">-</span>
            <input type="date" class="filter-input" id="dateTo">
            
            <button class="btn btn-sm btn-ship-primary btn-pill" id="syncBtn" onclick="runSync()">Sync</button>
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="loadAds()">Refresh</button>
        </div>
    </div>
    
    <div id="adsSyncAlert" class="alert d-none mb-3" style="border-radius:12px;font-size:.85rem"></div>

    <div class="card card-main">
        <div class="card-body p-2 d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-bottom:1px solid var(--shp-border)">
            <div class="d-flex align-items-center gap-2">
                <span class="filter-label d-none d-sm-inline" style="font-size:.7rem;font-weight:700;text-transform:uppercase">Harian</span>
                <div class="period-tabs" id="periodTabs">
                    <button class="period-tab" data-days="7">7 Hari</button>
                    <button class="period-tab active" data-days="30">30 Hari</button>
                    <button class="period-tab" data-days="90">90 Hari</button>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-ship-outline btn-pill" id="btnSyncShopPerf" onclick="syncShopPerf()">Sync DB</button>
                <button class="btn btn-sm btn-ship-outline btn-pill" id="btnShopPerf" onclick="loadShopPerf()">Muat DB</button>
                <button class="btn btn-sm btn-ship-outline btn-pill" id="btnBackfill" onclick="backfillAds()">Tarik 6 Bln</button>
                <button class="btn btn-sm btn-ship-outline btn-pill" onclick="showBalanceHistory()">Riwayat Saldo</button>
            </div>
        </div>
        
        <div id="perStoreWrap" class="p-2" style="display:none; border-bottom:1px solid var(--shp-border)">
            <div style="font-size:.7rem;color:#64748b;font-weight:700;text-transform:uppercase;margin-bottom:4px">Perbandingan per Toko</div>
            <div id="perStoreChips" style="display:flex;gap:.4rem;flex-wrap:wrap"></div>
        </div>
        
        <div class="table-responsive">
            <table class="table-list" id="shopPerfTable">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th class="text-end">Impresi</th>
                        <th class="text-end">Klik</th>
                        <th class="text-end">CTR</th>
                        <th class="text-end">Spend</th>
                        <th class="text-end">Order</th>
                        <th class="text-end">GMV</th>
                        <th class="text-end">ROAS</th>
                    </tr>
                </thead>
                <tbody id="shopPerfBody">
                    <tr><td colspan="8" class="text-center text-muted py-3" style="font-size:.8rem">Belum dimuat.</td></tr>
                </tbody>
            </table>
            <div id="shopPerfInfo" class="px-3 pb-2 text-muted" style="font-size:.7rem;text-align:right"></div>
        </div>
    </div>

    <div class="card card-main">
        <div class="card-body p-2 d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-bottom:1px solid var(--shp-border)">
            <div class="period-tabs">
                <button class="period-tab active" data-view="campaign" onclick="setView('campaign')">Campaign</button>
                <button class="period-tab" data-view="item" onclick="setView('item')">Item</button>
                <button class="period-tab" data-view="group" onclick="setView('group')">Grup</button>
            </div>
            
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <span id="unmappedBadge" class="reco-badge reco-warn" style="display:none;cursor:pointer" onclick="toggleUnmappedFilter()"></span>
                <input type="search" id="searchCampaign" class="filter-input" placeholder="Cari..." oninput="applyFilters()" style="width:130px">
                <select id="filterStatus" class="filter-select" onchange="applyFilters()">
                    <option value="">Status (Semua)</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="suspended">Suspended</option>
                    <option value="ended">Ended</option>
                </select>
                <select id="filterReco" class="filter-select" onchange="applyFilters()">
                    <option value="">Rekomendasi (Semua)</option>
                    <option value="🚀">Scale</option>
                    <option value="✅">Pertahankan</option>
                    <option value="⚡">Perhatikan</option>
                    <option value="🔴">Stop</option>
                </select>
                <label class="toggle-switch" id="unmappedFilterWrap" style="display:none; margin-left:.5rem">
                    <input type="checkbox" id="onlyUnmapped" onchange="applyFilters()">
                    Hanya Unmapped
                </label>
                <label class="toggle-switch" style="margin-left:.5rem">
                    <input type="checkbox" id="hideInactive" checked onchange="applyFilters()">
                    Sembunyikan 0
                </label>
                <button class="btn btn-sm btn-ship-outline btn-pill" style="margin-left:.5rem" onclick="manageGroups()">Kelola Grup</button>
            </div>
        </div>
        
        <div class="table-responsive" style="overflow-y:hidden">
            <div id="adsBody">
                <div class="text-center text-muted py-4" style="font-size:.8rem">Memuat...</div>
            </div>
            <div id="adsPager" class="ads-pager" style="display:none"></div>
        </div>
    </div>
</div>
@endsection
`;

// Extract everything from `<script>` until `</script>` from original file
const scriptMatch = content.match(/<script>([\s\S]*?)<\/script>/);
if (scriptMatch) {
    let scriptContent = scriptMatch[1];
    
    // Replace JS classes
    scriptContent = scriptContent.replace(/gf-clean-table/g, 'table-list');
    scriptContent = scriptContent.replace(/gf-table-scroll/g, 'table-responsive');
    scriptContent = scriptContent.replace(/gf-table-foot/g, 'p-2');
    scriptContent = scriptContent.replace(/gf-table-foot-hint/g, 'text-muted small');
    
    fs.writeFileSync(path, newContent + "\n@push('scripts')\n<script>\n" + scriptContent + "\n</script>\n@endpush\n");
    console.log("File replaced successfully");
} else {
    console.error("Could not find script block");
}
