@extends('layouts.app')
@section('title', 'Marketplace • Order Lokal')

@include('marketplace._shared')

@push('head')
<style>
/* ── Force header one line ── */
.gf-master-desc { display: none !important; }

/* ── Header icon buttons ── */
.hdr-btn {
    display: inline-flex; align-items: center; gap: .3rem;
    border: 1.5px solid #e2e8f0; border-radius: 999px;
    padding: .28rem .65rem; font-size: .75rem; font-weight: 700;
    background: #fff; color: #0f172a; cursor: pointer;
    transition: all .15s; white-space: nowrap; position: relative;
    flex-shrink: 0;
}
.hdr-btn:hover { border-color: #94a3b8; background: #f8fafc; }
.hdr-btn.active { background: #0f172a; color: #fff; border-color: #0f172a; }
.hdr-btn .hdr-btn-label { font-size: .68rem; color: #94a3b8; font-weight: 600; }
.hdr-btn.active .hdr-btn-label { color: rgba(255,255,255,.55); }

/* ── Dropdown popover ── */
.hdr-dropdown {
    position: absolute; top: calc(100% + 8px); right: 0; z-index: 1050;
    background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px;
    box-shadow: 0 8px 30px rgba(0,0,0,.1); min-width: 180px; padding: .5rem;
    display: none;
}
.hdr-dropdown.open { display: block; }
.hdr-dropdown-item {
    display: flex; align-items: center; gap: .5rem;
    padding: .45rem .6rem; border-radius: 9px; cursor: pointer;
    font-size: .8rem; font-weight: 600; color: #0f172a; transition: background .1s;
}
.hdr-dropdown-item:hover { background: #f8fafc; }
.hdr-dropdown-item.selected { background: #f1f5f9; font-weight: 800; }
.hdr-dropdown-divider { height: 1px; background: #f1f5f9; margin: .3rem 0; }

/* ── Search bar ── */
.ord-search-bar {
    display: flex; align-items: center; gap: .5rem;
    background: #f8fafc; border: 1.5px solid #e2e8f0;
    border-radius: 12px; padding: .45rem .75rem;
    margin-bottom: 1rem; transition: border-color .15s;
}
.ord-search-bar:focus-within { border-color: #94a3b8; background: #fff; }
.ord-search-bar .search-icon { font-size: .9rem; flex-shrink: 0; opacity: .5; }
.ord-search-bar input {
    border: none; background: transparent; outline: none;
    font-size: .83rem; font-weight: 600; color: #0f172a; width: 100%;
}
.ord-search-bar input::placeholder { color: #94a3b8; font-weight: 500; }
.ord-search-clear {
    background: none; border: none; cursor: pointer; padding: 0;
    font-size: .75rem; color: #94a3b8; flex-shrink: 0; display: none;
}
.ord-search-clear.visible { display: block; }

/* ── Tabs ── */
.ord-tabs {
    display: flex; gap: .25rem; flex-wrap: nowrap;
    overflow-x: auto; -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    margin-bottom: 1rem; border-bottom: 1.5px solid #f1f5f9;
    padding-bottom: .5rem;
}
.ord-tabs::-webkit-scrollbar { display: none; }
.ord-tab {
    display: flex; align-items: center; gap: .35rem;
    background: none; border: none; padding: .38rem .7rem;
    font-size: .78rem; font-weight: 700; color: #94a3b8;
    border-radius: 8px; cursor: pointer; transition: all .15s;
    position: relative; white-space: nowrap; flex-shrink: 0;
}
.ord-tab:hover { background: #f8fafc; color: #475569; }
.ord-tab.active { color: #0f172a; background: #f1f5f9; }
.ord-tab.active::after {
    content: ''; position: absolute; bottom: -9px; left: 50%;
    transform: translateX(-50%); width: 70%; height: 2.5px;
    background: #0f172a; border-radius: 999px;
}

/* ── Mobile cards ── */
.ord-cards { display: flex; flex-direction: column; gap: .5rem; }
.ord-card {
    background: #fff; border: 1.5px solid #f1f5f9;
    border-radius: 14px; overflow: hidden;
    transition: border-color .15s;
}
.ord-card:hover { border-color: #e2e8f0; }
.ord-card.row-urgent  { border-left: 3px solid #f59e0b; }
.ord-card.row-packing { border-left: 3px solid #2563eb; }
.ord-card-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: .5rem; padding: .7rem .9rem .55rem;
}
.ord-card-meta { min-width: 0; }
.ord-card-meta .ord-id { font-size: .78rem; }
.ord-card-sub {
    display: flex; align-items: center; gap: .4rem; margin-top: .18rem; flex-wrap: wrap;
}
.ord-card-sub-text { font-size: .68rem; color: #94a3b8; font-weight: 500; }
.ord-card-actions { flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-end; gap: .3rem; }
.ord-card-section { border-top: 1px solid #f4f6f9; }
/* Accordion trigger */
.ord-acc-toggle {
    display: flex; align-items: center; justify-content: space-between;
    padding: .42rem .9rem; cursor: pointer; user-select: none;
    gap: .5rem;
}
.ord-acc-toggle:hover { background: #f8fafc; }
.ord-acc-label {
    font-size: .6rem; font-weight: 800; color: #94a3b8; letter-spacing: .06em;
    text-transform: uppercase; display: flex; align-items: center; gap: .35rem;
}
.ord-acc-count {
    font-size: .6rem; font-weight: 700; background: #f1f5f9; color: #64748b;
    border-radius: 999px; padding: .05rem .38rem; line-height: 1.5;
}
.ord-acc-chevron {
    font-size: .6rem; color: #cbd5e1; transition: transform .18s; flex-shrink: 0;
}
.ord-acc-body {
    display: none; padding: .3rem .9rem .55rem;
}
.ord-acc-body.open { display: block; }
.ord-acc-body .ord-items-cell { gap: .3rem; }
.ord-acc-body .ord-item-card { padding: .35rem .5rem; border-radius: 8px; }
.ord-acc-body .ord-item-name { font-size: .76rem; }
.ord-acc-body .ord-item-variant { font-size: .68rem; }
.ord-acc-toggle.open .ord-acc-chevron { transform: rotate(180deg); }

/* ── Media queries ── */
@media (max-width: 640px) {
    .oc-kpi-grid { grid-template-columns: 1fr 1fr !important; gap: .5rem !important; }
    .oc-kpi-card { padding: .65rem .75rem !important; }
    .oc-kpi-value { font-size: 1.5rem !important; }
    .oc-kpi-label { font-size: .65rem !important; }
    .oc-kpi-note  { font-size: .6rem !important; }
    .process-toolbar { flex-direction: column; align-items: flex-start; }
    .process-toolbar-actions { width: 100%; overflow-x: auto; }
}
.ord-badge {
    font-size: .63rem; font-weight: 800; padding: .1rem .35rem;
    border-radius: 999px; background: #e2e8f0; color: #475569;
    min-width: 17px; text-align: center; line-height: 1.4;
}
.ord-tab.active .ord-badge { background: #0f172a; color: #fff; }
.ord-badge.urgent { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.ord-tab.active .ord-badge.urgent { background: #dc2626; color: #fff; border-color: transparent; }

/* ── Empty state ── */
.ord-empty { text-align: center; padding: 3rem 1rem; color: #94a3b8; font-size: .85rem; }
.ord-empty-icon { font-size: 2rem; margin-bottom: .5rem; }

/* ── Table overrides ── */
.ord-table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
.ord-table thead tr th {
    font-size: .63rem; font-weight: 800; letter-spacing: .05em;
    color: #94a3b8; text-transform: uppercase; padding: .5rem .75rem;
    border-bottom: 1.5px solid #f1f5f9; background: #fafafa; white-space: nowrap;
}
.ord-table colgroup .col-order   { width: 13%; }
.ord-table colgroup .col-items   { width: 44%; }
.ord-table colgroup .col-items-sm { width: 28%; }
.ord-table colgroup .col-scan    { width: 20%; }
.ord-table colgroup .col-status  { width: 13%; }
.ord-table colgroup .col-status-sm { width: 10%; }
.ord-table colgroup .col-store   { width: 17%; }
.ord-table colgroup .col-store-sm { width: 16%; }
.ord-table colgroup .col-total   { width: 13%; }
.ord-table tbody tr { transition: background .1s; }
.ord-table tbody tr:hover td { background: #f8fafc; }
.ord-table tbody tr td {
    padding: .7rem .75rem; border-bottom: 1px solid #f4f6f9;
    vertical-align: top; font-size: .8rem;
}

/* ── Order ID cell ── */
.ord-id {
    font-size: .75rem; font-weight: 800; color: #0f172a;
    font-family: 'SF Mono', 'Menlo', monospace; letter-spacing: -.01em;
    word-break: break-all;
}
.ord-date { font-size: .68rem; color: #94a3b8; margin-top: .15rem; }

/* ── Item cards (focal point) ── */
.ord-items-cell { display: flex; flex-direction: column; gap: .4rem; }
.ord-item-card {
    display: flex; align-items: flex-start; gap: .55rem;
    background: #f8fafc; border: 1px solid #f1f5f9;
    border-radius: 9px; padding: .45rem .55rem;
    transition: border-color .1s;
}
.ord-table tbody tr:hover .ord-item-card { border-color: #e2e8f0; background: #fff; }
.ord-item-qty {
    font-size: .7rem; font-weight: 800; background: #e2e8f0; color: #475569;
    border-radius: 5px; padding: .1rem .35rem; flex-shrink: 0; margin-top: .1rem;
    min-width: 24px; text-align: center; line-height: 1.4;
}
.ord-item-qty.urgent { background: #fef3c7; color: #92400e; }
.ord-item-body { flex: 1; min-width: 0; }
.ord-item-name {
    font-size: .8rem; font-weight: 800; color: #0f172a;
    font-family: 'SF Mono', 'Menlo', 'Consolas', monospace;
    letter-spacing: -.01em; line-height: 1.3;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ord-item-variant {
    font-size: .72rem; color: #475569; margin-top: .12rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ord-item-sku {
    font-size: .63rem; color: #94a3b8; font-family: 'SF Mono', 'Menlo', monospace;
    margin-top: .06rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ord-item-nomap {
    font-size: .72rem; color: #d97706; background: #fef3c7;
    border-radius: 5px; padding: .1rem .4rem; display: inline-block; font-weight: 700;
}
.ord-more {
    font-size: .68rem; color: #64748b; padding: .15rem .5rem;
    background: #f1f5f9; border-radius: 5px; display: inline-block;
    border: 1px solid #e2e8f0; font-weight: 600;
}
/* ── Keterangan badges (tab Sudah Proses) ── */
.ord-ket {
    display: inline-block; font-size: .62rem; font-weight: 700;
    border-radius: 4px; padding: .1rem .38rem; margin-top: .18rem;
    line-height: 1.4;
}
.ord-ket-sub  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.ord-ket-spl  { background: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe; }
.ord-ket-ok   { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }

/* ── Row highlight for urgent ── */
.ord-table tbody tr.row-urgent td:first-child   { border-left: 3px solid #f59e0b; }
.ord-table tbody tr.row-packing td:first-child  { border-left: 3px solid #2563eb; }
.ord-table tbody tr.row-fulfilled td:first-child { border-left: 3px solid #22c55e; }
.ord-table tbody tr.row-printed td:first-child  { border-left: 3px solid #0ea5e9; }
.ord-table tbody tr.row-printed { opacity: 0.82; }

/* ── KPI strip ── */
.oc-kpi-note { font-size: .65rem; color: #94a3b8; margin-top: .1rem; }

/* ── Tombol fulfillment ── */
.btn-fulfillment {
    display: inline-flex; align-items: center; gap: .25rem;
    font-size: .68rem; font-weight: 700; padding: .22rem .55rem;
    border-radius: 999px; border: 1.5px solid #e2e8f0;
    background: #fff; color: #0f172a; cursor: pointer;
    transition: all .15s; white-space: nowrap;
}
.btn-fulfillment:hover { background: #0f172a; color: #fff; border-color: #0f172a; }
.btn-fulfillment.done { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; cursor: default; }


/* ── Process toolbar ── */
.process-toolbar {
    display: none; align-items: center; justify-content: space-between;
    padding: .6rem .75rem; background: #f8fafc; border-radius: 10px;
    border: 1.5px solid #f1f5f9; margin-bottom: 1rem; gap: .5rem;
}
.process-toolbar.visible { display: flex; }
.process-toolbar-info { font-size: .75rem; color: #64748b; font-weight: 600; }
.process-toolbar-info strong { color: #0f172a; }
.process-toolbar-actions { display: flex; gap: .4rem; flex-shrink: 0; }
.btn-toolbar {
    display: inline-flex; align-items: center; gap: .3rem;
    font-size: .73rem; font-weight: 700; padding: .28rem .65rem;
    border-radius: 999px; border: 1.5px solid #e2e8f0;
    background: #fff; color: #0f172a; cursor: pointer; transition: all .15s;
    white-space: nowrap;
}
.btn-toolbar:hover { border-color: #94a3b8; background: #f8fafc; }
.btn-toolbar.primary { background: #0f172a; color: #fff; border-color: #0f172a; }
.btn-toolbar.primary:hover { background: #1e293b; }
.btn-toolbar.active { background: #dc2626; color: #fff; border-color: #dc2626; }
.btn-toolbar.active:hover { background: #b91c1c; }
/* ── Fulfillment status badge di row ── */
.fstatus { display:inline-block; font-size:.68rem; font-weight:700; padding:.1rem .45rem; border-radius:999px; vertical-align:middle; }
.fstatus-none    { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.fstatus-draft   { background:#fefce8; color:#a16207; border:1px solid #fde68a; }
.fstatus-pending { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
.fstatus-done    { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }

/* ── Sedang Proses: card-list style ── */
.pk-section {
    background: #fff; border: 1.5px solid #f1f5f9;
    border-radius: 16px; overflow: hidden;
}
.pk-section-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: .85rem 1.1rem; border-bottom: 1.5px solid #f1f5f9;
    background: #fafafa;
}
.pk-section-title {
    display: flex; align-items: center; gap: .5rem;
    font-size: .82rem; font-weight: 800; color: #0f172a;
}
.pk-section-sub { font-size: .72rem; color: #94a3b8; font-weight: 500; margin-top: .1rem; }
.pk-count-badge {
    font-size: .7rem; font-weight: 800; background: #f59e0b;
    color: #fff; border-radius: 999px; padding: .15rem .6rem;
}
.pk-row {
    display: flex; align-items: center; gap: .75rem;
    padding: .75rem 1.1rem; border-bottom: 1px solid #f8fafc;
    transition: background .1s;
}
.pk-row:last-child { border-bottom: none; }
.pk-row:hover { background: #fafafa; }
.pk-row-left { flex: 1; min-width: 0; }
.pk-order-id {
    font-size: .82rem; font-weight: 800; color: #0f172a;
    font-family: 'SF Mono', 'Menlo', monospace; letter-spacing: -.01em;
}
.pk-row-meta {
    display: flex; align-items: center; gap: .5rem;
    margin-top: .22rem; flex-wrap: wrap;
}
.pk-meta-text { font-size: .72rem; color: #64748b; font-weight: 500; }
.pk-badge-short {
    display: inline-flex; align-items: center; gap: .2rem;
    font-size: .65rem; font-weight: 700;
    background: #fef3c7; color: #92400e;
    border: 1px solid #fde68a; border-radius: 999px; padding: .08rem .45rem;
}
.pk-badge-ok {
    display: inline-flex; align-items: center; gap: .2rem;
    font-size: .65rem; font-weight: 700;
    background: #f0fdf4; color: #15803d;
    border: 1px solid #bbf7d0; border-radius: 999px; padding: .08rem .45rem;
}
.pk-pack-info {
    font-size: .72rem; font-weight: 700; color: #2563eb;
}
.pk-pack-info.short { color: #d97706; }
.btn-review {
    display: inline-flex; align-items: center; gap: .25rem;
    font-size: .72rem; font-weight: 700; padding: .28rem .7rem;
    border-radius: 999px; border: 1.5px solid #e2e8f0;
    background: #fff; color: #0f172a; cursor: pointer;
    transition: all .15s; white-space: nowrap; flex-shrink: 0;
}
.btn-review:hover { background: #0f172a; color: #fff; border-color: #0f172a; }

/* ── Review Modal ── */
.ord-review-modal-bg {
    display: none; position: fixed; inset: 0; z-index: 2000;
    background: rgba(15,23,42,.45); backdrop-filter: blur(2px);
    align-items: center; justify-content: center; padding: 1rem;
}
.ord-review-modal-bg.open { display: flex; }
.ord-review-modal {
    background: #fff; border-radius: 20px; width: 100%; max-width: 560px;
    max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;
    box-shadow: 0 20px 60px rgba(15,23,42,.18);
}
.orm-header {
    padding: 1.25rem 1.4rem .9rem; border-bottom: 1.5px solid #f1f5f9;
    display: flex; justify-content: space-between; align-items: flex-start; gap: .5rem;
}
.orm-title { font-size: 1.05rem; font-weight: 800; color: #0f172a; font-family: 'SF Mono','Menlo',monospace; }
.orm-sub   { font-size: .75rem; color: #94a3b8; margin-top: .15rem; }
.orm-close {
    background: #f1f5f9; border: none; border-radius: 999px;
    width: 28px; height: 28px; cursor: pointer; font-size: .8rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; transition: background .15s; color: #475569;
}
.orm-close:hover { background: #e2e8f0; }
.orm-body { overflow-y: auto; padding: 1rem 1.4rem; flex: 1; }
.orm-section-label {
    font-size: .65rem; font-weight: 800; color: #94a3b8;
    letter-spacing: .07em; text-transform: uppercase;
    display: flex; align-items: center; gap: .5rem; margin-bottom: .6rem;
}
.orm-section-label .orm-cnt {
    font-size: .62rem; background: #f1f5f9; color: #64748b;
    border-radius: 999px; padding: .05rem .38rem; font-weight: 700;
}
/* Data pesanan table */
.orm-table { width: 100%; border-collapse: collapse; margin-bottom: 1.1rem; }
.orm-table th {
    font-size: .6rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;
    letter-spacing: .05em; padding: .35rem .5rem; border-bottom: 1.5px solid #f1f5f9;
    text-align: left;
}
.orm-table th:not(:first-child) { text-align: center; }
.orm-table td { padding: .5rem .5rem; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.orm-table tr:last-child td { border-bottom: none; }
.orm-item-code { font-size: .78rem; font-weight: 800; color: #0f172a; font-family: 'SF Mono','Menlo',monospace; }
.orm-item-name { font-size: .68rem; color: #64748b; margin-top: .08rem; }
.orm-qty { text-align: center; font-size: .82rem; font-weight: 800; color: #0f172a; }
.orm-qty.short { color: #d97706; }
.orm-qty.ok    { color: #16a34a; }
.orm-status-ok    { font-size: .65rem; font-weight: 700; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; border-radius: 999px; padding: .08rem .4rem; white-space: nowrap; }
.orm-status-short { font-size: .65rem; font-weight: 700; background: #fef3c7; color: #92400e; border: 1px solid #fde68a; border-radius: 999px; padding: .08rem .4rem; white-space: nowrap; }
/* Scan log items */
.orm-scan-list { display: flex; flex-direction: column; gap: .3rem; }
.orm-scan-item {
    display: flex; align-items: center; gap: .6rem;
    background: #f8fafc; border: 1px solid #f1f5f9;
    border-radius: 10px; padding: .4rem .65rem;
}
.orm-scan-code { font-size: .75rem; font-weight: 800; color: #4338ca; font-family: 'SF Mono','Menlo',monospace; flex-shrink: 0; }
.orm-scan-name { font-size: .73rem; color: #64748b; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.orm-scan-qty  { font-size: .8rem; font-weight: 800; color: #0f172a; flex-shrink: 0; }
.orm-footer {
    padding: .9rem 1.4rem; border-top: 1.5px solid #f1f5f9;
    display: flex; justify-content: flex-end;
}

/* ── Bulk Progress Modal ── */
.bulk-prog-item {
    display: flex; align-items: center; gap: .5rem;
    padding: .3rem 0; font-size: .78rem; border-bottom: 1px solid #f8fafc;
}
.bulk-prog-item:last-child { border-bottom: none; }
.bulk-prog-icon { flex-shrink: 0; width: 16px; text-align: center; }
</style>
@endpush

@section('content')
<x-gf.page eyebrow="Marketplace" title="Order Lokal"
    description="Pantau dan proses order masuk dari semua toko.">

    <x-slot:actions>
        {{-- Hidden date inputs --}}
        <input type="hidden" id="mpDateFrom" value="{{ $filters['date_from'] }}">
        <input type="hidden" id="mpDateTo"   value="{{ $filters['date_to'] }}">

        {{-- 🔄 Sync --}}
        <button class="hdr-btn" style="background:#0f172a;color:#fff;border-color:#0f172a"
            onclick="openQuickSync()">🔄 Sync</button>

        {{-- 🔃 Refresh --}}
        <button class="hdr-btn" onclick="loadOrders()" title="Refresh">🔃</button>

        @if(app()->environment('local', 'development', 'testing', 'staging'))
        <button class="hdr-btn" id="btnDevPanel"
            style="background:#faf5ff;color:#7c3aed;border-color:#ddd6fe"
            onclick="toggleDevPanel()">
            🛠 Dev
        </button>
        @endif

        {{-- 🏪 Store filter --}}
        <div style="position:relative">
            <button class="hdr-btn" id="btnStore" onclick="toggleDropdown('ddStore', event)">
                🏪 <span id="btnStoreLabel" class="hdr-btn-label">Semua Toko</span>
            </button>
            <div class="hdr-dropdown" id="ddStore">
                <div style="padding:.25rem .4rem .1rem;font-size:.65rem;font-weight:700;color:#94a3b8;letter-spacing:.04em">PILIH TOKO</div>
                <div id="storeDropdownItems"></div>
            </div>
        </div>

        {{-- Divider --}}
        <div style="width:1px;height:20px;background:#e2e8f0;margin:0 .1rem"></div>

        {{-- 📅 Date — ujung kanan --}}
        <div style="position:relative">
            <button class="hdr-btn" id="btnDate" onclick="toggleDropdown('ddDate', event)">
                📅 <span id="btnDateLabel" class="hdr-btn-label">30 hari terakhir</span>
            </button>
            <div class="hdr-dropdown" id="ddDate" style="right:0;left:auto;min-width:210px">
                <div style="padding:.25rem .4rem .1rem;font-size:.65rem;font-weight:700;color:#94a3b8;letter-spacing:.04em">PERIODE CEPAT</div>
                <div class="hdr-dropdown-item" onclick="setDatePreset(1)">📆 Hari ini</div>
                <div class="hdr-dropdown-item" onclick="setDatePreset(7)">📆 7 hari terakhir</div>
                <div class="hdr-dropdown-item selected" onclick="setDatePreset(30)">📆 30 hari terakhir</div>
                <div class="hdr-dropdown-item" onclick="setDatePreset(90)">📆 90 hari terakhir</div>
                <div class="hdr-dropdown-divider"></div>
                <div style="padding:.2rem .4rem .1rem;font-size:.65rem;font-weight:700;color:#94a3b8">PILIH MANUAL</div>
                <div style="padding:.3rem .4rem .4rem">
                    <input type="text" id="mpDateRange" autocomplete="off"
                        style="width:100%;font-size:.78rem;border:1.5px solid #e2e8f0;border-radius:8px;padding:.28rem .6rem"
                        placeholder="Pilih rentang…" value="{{ $filters['date_from'] }} — {{ $filters['date_to'] }}">
                </div>
            </div>
        </div>
    </x-slot:actions>

    @if(app()->environment('local', 'development', 'testing', 'staging'))
    {{-- ══ DEV TOOLS PANEL ══════════════════════════════════════════════════ --}}
    <div id="devPanel" style="display:none;background:#faf5ff;border:1.5px solid #ddd6fe;border-radius:14px;padding:1rem 1.25rem;margin-bottom:1.25rem">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem">
            <div style="font-size:.8rem;font-weight:800;color:#7c3aed;letter-spacing:.05em">🛠 DEV TOOLS — LOCAL ONLY</div>
            <div id="devStats" style="font-size:.73rem;color:#6b7280">—</div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center">
            {{-- Seed --}}
            <button id="btnSeedOrders" onclick="devSeedOrders()"
                style="display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .85rem;border-radius:999px;border:1.5px solid #bbf7d0;background:#f0fdf4;color:#16a34a;font-size:.75rem;font-weight:700;cursor:pointer">
                📥 Seed Orders
            </button>
            {{-- Reset fulfillments --}}
            <button id="btnResetFulfillments" onclick="devResetFulfillments()"
                style="display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .85rem;border-radius:999px;border:1.5px solid #fde68a;background:#fefce8;color:#a16207;font-size:.75rem;font-weight:700;cursor:pointer"
                title="Hapus semua fulfillments, orders tetap ada">
                🔄 Reset Fulfillments
            </button>
            {{-- Fresh (nuke all) --}}
            <button id="btnFreshOrders" onclick="devFreshOrders()"
                style="display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .85rem;border-radius:999px;border:1.5px solid #fecaca;background:#fef2f2;color:#dc2626;font-size:.75rem;font-weight:700;cursor:pointer"
                title="Hapus SEMUA orders + fulfillments">
                🗑 Fresh All
            </button>
            {{-- Remap items --}}
            <button id="btnRemapItems" onclick="devRemapItems()"
                style="display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .85rem;border-radius:999px;border:1.5px solid #c4b5fd;background:#f5f3ff;color:#6d28d9;font-size:.75rem;font-weight:700;cursor:pointer"
                title="Re-resolve semua mapping_status + cost_status item berdasarkan SKU Mapping">
                🔁 Remap Items
            </button>
            <div style="width:1px;height:20px;background:#e2e8f0;margin:0 .15rem"></div>
            {{-- Go to fulfillment --}}
            <a href="/marketplace/fulfillment"
                style="display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .85rem;border-radius:999px;border:1.5px solid #bfdbfe;background:#eff6ff;color:#2563eb;font-size:.75rem;font-weight:700;text-decoration:none">
                📋 Buka Fulfillment →
            </a>
        </div>
        <div style="margin-top:.65rem;font-size:.7rem;color:#9ca3af">
            Seed = tambah order baru READY_TO_SHIP &nbsp;|&nbsp;
            Remap = fix mapping_status item lama &nbsp;|&nbsp;
            Reset Fulfillments = order balik ke "Perlu Proses" &nbsp;|&nbsp;
            Fresh All = hapus semua data
        </div>
    </div>
    @endif

    {{-- KPI strip --}}
    <div class="oc-kpi-grid" style="margin-bottom:1.25rem">
        <div class="oc-kpi-card" style="cursor:pointer" onclick="switchTabByName('process')">
            <div class="oc-kpi-label">⚡ Perlu Diproses</div>
            <div class="oc-kpi-value" id="kpiProcess" style="color:#d97706">—</div>
            <div class="oc-kpi-note">klik untuk lihat</div>
        </div>
        <div class="oc-kpi-card" style="cursor:pointer" onclick="switchTabByName('shipping')">
            <div class="oc-kpi-label">🚚 Dalam Pengiriman</div>
            <div class="oc-kpi-value" id="kpiShipping" style="color:#2563eb">—</div>
            <div class="oc-kpi-note">klik untuk lihat</div>
        </div>
        <div class="oc-kpi-card" style="cursor:pointer" onclick="switchTabByName('done')">
            <div class="oc-kpi-label">✅ Selesai</div>
            <div class="oc-kpi-value" id="kpiCompleted" style="color:#16a34a">—</div>
            <div class="oc-kpi-note">klik untuk lihat</div>
        </div>
        <div class="oc-kpi-card" style="cursor:pointer" onclick="switchTabByName('cancel')">
            <div class="oc-kpi-label">✕ Dibatalkan</div>
            <div class="oc-kpi-value" id="kpiCancelled" style="color:#dc2626">—</div>
            <div class="oc-kpi-note">klik untuk lihat</div>
        </div>
    </div>

    <x-gf.panel>

        {{-- Search bar — prominent, di atas tabs --}}
        <div class="ord-search-bar">
            <span class="search-icon">🔍</span>
            <input type="text" id="filterSearch"
                placeholder="Cari nomor order, nama produk, atau SKU…"
                oninput="onSearchInput(this)" autocomplete="off">
            <button class="ord-search-clear" id="searchClearBtn" onclick="clearSearch()">✕</button>
        </div>

        {{-- Tabs --}}
        <div class="ord-tabs" id="ordTabs">
            <button class="ord-tab" data-tab="all" onclick="switchTab('all',this)">
                Semua <span class="ord-badge" id="badge-all">—</span>
            </button>
            <button class="ord-tab" data-tab="process" onclick="switchTab('process',this)">
                Perlu Proses <span class="ord-badge urgent" id="badge-process">—</span>
            </button>
            <button class="ord-tab" data-tab="packing" onclick="switchTab('packing',this)">
                Sedang Proses <span class="ord-badge" id="badge-packing" style="background:#eff6ff;color:#2563eb;border-color:#bfdbfe">—</span>
            </button>
            <button class="ord-tab" data-tab="shipping" onclick="switchTab('shipping',this)">
                Pengiriman <span class="ord-badge" id="badge-shipping">—</span>
            </button>
            <button class="ord-tab" data-tab="done" onclick="switchTab('done',this)">
                Selesai <span class="ord-badge" id="badge-done">—</span>
            </button>
            <button class="ord-tab" data-tab="cancel" onclick="switchTab('cancel',this)">
                Batal <span class="ord-badge" id="badge-cancel">—</span>
            </button>
        </div>

        {{-- Toolbar: tab Perlu Diproses --}}
        <div class="process-toolbar" id="processToolbar">
            <div class="process-toolbar-info" id="toolbarInfo">
                <strong id="toolbarCount">0</strong> order perlu diproses hari ini
            </div>
            <div class="process-toolbar-actions" id="toolbarActionsProcess">
                <button class="btn-toolbar" id="btnBelumProses" onclick="toggleBelumProses()"
                    style="transition:background .15s">🔴 Belum Proses</button>
                <button class="btn-toolbar" onclick="printPickingList()">🖨 Cetak Picking List</button>
                <button class="btn-toolbar primary" id="btnBulkFulfill" onclick="openBulkFulfill()">📦 Proses Semua</button>
            </div>
            <div class="process-toolbar-actions" id="toolbarActionsUnresolved" style="display:none">
                <a href="/marketplace/issues" class="btn-toolbar primary">🔗 Perbaiki di Issues →</a>
            </div>
        </div>

        <div id="ordersBody">
            <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
        </div>

    </x-gf.panel>
</x-gf.page>
@endsection

{{-- Review Modal (Sedang Proses) --}}
<div class="ord-review-modal-bg" id="ordReviewBg" onclick="closeReviewModal(event)">
    <div class="ord-review-modal" onclick="event.stopPropagation()">
        <div class="orm-header">
            <div>
                <div class="orm-title" id="ormTitle">—</div>
                <div class="orm-sub"  id="ormSub">—</div>
            </div>
            <button class="orm-close" onclick="closeReviewModal()">✕</button>
        </div>
        <div class="orm-body" id="ormBody">
            <div style="text-align:center;padding:2rem;color:#94a3b8">Memuat…</div>
        </div>
        <div class="orm-footer">
            <button class="btn-review" onclick="closeReviewModal()" style="font-size:.78rem;padding:.35rem 1rem">Tutup</button>
        </div>
    </div>
</div>

{{-- Quick Sync Modal --}}
<div class="modal fade" id="quickSyncModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-black" style="font-size:1rem">🔄 Sync Terbaru</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <p style="font-size:.8rem;color:#64748b;margin-bottom:1rem">
                    Sync order dari semua toko yang terhubung.
                </p>
                <div class="mb-3">
                    <label style="font-size:.68rem;font-weight:700;color:#94a3b8;display:block;margin-bottom:.3rem">RENTANG WAKTU</label>
                    <select id="qsSyncRange" class="form-select form-select-sm" style="border-radius:10px;font-size:.8rem">
                        <option value="1">1 hari terakhir</option>
                        <option value="3" selected>3 hari terakhir</option>
                        <option value="7">7 hari terakhir</option>
                        <option value="14">14 hari terakhir</option>
                    </select>
                </div>
                <div id="qsAlert" class="alert d-none mb-3" style="font-size:.8rem;border-radius:12px"></div>
                <div id="qsProgress" style="display:none">
                    <div class="d-flex align-items-center gap-2 mb-2" style="font-size:.8rem;color:#475569">
                        <span class="prod-tab-spinner"></span>
                        <span id="qsProgressText">Syncing…</span>
                    </div>
                    <div class="progress" style="height:4px;border-radius:999px">
                        <div id="qsProgressBar" class="progress-bar bg-dark" style="width:0%;transition:width .3s"></div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button class="btn btn-light border fw-bold" style="border-radius:999px;font-size:.78rem" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-dark fw-bold" style="border-radius:999px;font-size:.78rem" id="qsRunBtn" onclick="runQuickSync()">↓ Sync Sekarang</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Bulk Fulfillment Progress Modal --}}
<div class="modal fade" id="bulkFulfillModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:460px">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-black" style="font-size:1rem">📦 Proses Semua Order</h5>
            </div>
            <div class="modal-body pt-2">
                <div id="bulkConfirmView">
                    <p style="font-size:.82rem;color:#475569;margin-bottom:1rem" id="bulkSummaryText"></p>
                    <div id="bulkUnmappedWarn" class="alert alert-warning d-none" style="font-size:.78rem;border-radius:10px;padding:.5rem .75rem"></div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button class="btn btn-light border fw-bold" style="border-radius:999px;font-size:.78rem" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-dark fw-bold" style="border-radius:999px;font-size:.78rem" id="bulkStartBtn" onclick="runBulkFulfill()">📦 Proses Sekarang</button>
                    </div>
                </div>
                <div id="bulkProgressView" style="display:none">
                    <div class="mb-2">
                        <div class="progress" style="height:6px;border-radius:999px;margin-bottom:.5rem">
                            <div id="bulkProgressBar" class="progress-bar bg-dark" style="width:0%;transition:width .3s"></div>
                        </div>
                        <div style="font-size:.75rem;color:#64748b" id="bulkProgressText">Memproses…</div>
                    </div>
                    <div id="bulkProgressList" style="max-height:280px;overflow-y:auto"></div>
                </div>
                <div id="bulkDoneView" style="display:none">
                    <div id="bulkDoneSummary"></div>
                    <div class="d-flex justify-content-end mt-3">
                        <button class="btn btn-dark fw-bold" style="border-radius:999px;font-size:.78rem" data-bs-dismiss="modal" onclick="render()">✓ Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script>
(function () {
    // Force header actions ke satu baris (display:block → flex)
    document.addEventListener('DOMContentLoaded', () => {
        const actions = document.querySelector('.gf-master-actions');
        if (actions) {
            actions.style.display        = 'flex';
            actions.style.flexDirection  = 'row';
            actions.style.flexWrap       = 'nowrap';
            actions.style.alignItems     = 'center';
            actions.style.gap            = '.4rem';
            actions.style.justifyContent = 'flex-end';
        }
    });

    const { api, fmtDate, fmtRp, esc, channelPill, statusBadge } = window.mpHelpers;
    let orders           = [];
    let activeTab        = sessionStorage.getItem('ord_active_tab') || 'process';
    let activeStore      = '';
    let fulfilledOrderIds    = new Set();   // order ID yang sudah punya fulfillment confirmed
    let printedOrderIds      = new Set();   // order ID yang sudah dicetak picking list
    let currentFulfillment   = null;        // fulfillment aktif di modal
    let fulfillmentStatusMap = new Map();   // order_id → {id, status} — pre-loaded dari API
    let filterBelumProses    = false;       // toggle filter hanya order belum fulfilled
    // currentFulfillment tidak lagi dipakai — proses pindah ke /marketplace/fulfillment

    const $ = id => document.getElementById(id);
    const getFrom   = () => $('mpDateFrom').value;
    const getTo     = () => $('mpDateTo').value;
    const getSearch = () => ($('filterSearch').value || '').toLowerCase().trim();

    // Status order yang dianggap "aktif" (perlu proses / sedang packing)
    const ACTIVE_ORDER_STATUSES = ['READY_TO_SHIP', 'PROCESSED'];

    const TAB_STATUSES = {
        all:        null,
        process:    null, // via TAB_FILTERS — ACTIVE + belum ada fulfillment
        packing:    null, // via TAB_FILTERS — ACTIVE + sudah ada fulfillment aktif (belum confirmed)
        unresolved: null, // via TAB_FILTERS
        fulfilled:  null, // via TAB_FILTERS
        shipping:   ['SHIPPED', 'TO_CONFIRM_RECEIVE'],
        done:       ['COMPLETED'],
        cancel:     ['CANCELLED'],
    };

    // Semua filter berbasis fungsi
    const TAB_FILTERS = {
        process:    o => ACTIVE_ORDER_STATUSES.includes(o.order_status) && !fulfillmentStatusMap.has(o.id),
        packing:    o => ACTIVE_ORDER_STATUSES.includes(o.order_status) && fulfillmentStatusMap.has(o.id) && !fulfilledOrderIds.has(o.id),
        unresolved: o => o.has_data_issues === true,
        fulfilled:  o => o.fulfillment_status === 'confirmed' || fulfilledOrderIds.has(o.id),
    };

    const TAB_EMPTY = {
        all:        { icon: '📋', text: 'Belum ada order di periode ini.' },
        process:    { icon: '✅', text: 'Semua order sudah di-scan. Cek tab Sedang Packing!' },
        packing:    { icon: '📦', text: 'Belum ada order yang sedang dipacking di periode ini.' },
        unresolved: { icon: '✅', text: 'Tidak ada order yang perlu diperbaiki. Semua item sudah ter-mapping.' },
        fulfilled:  { icon: '🎉', text: 'Belum ada order yang sudah diproses di periode ini.' },
        shipping:   { icon: '🚚', text: 'Tidak ada order dalam pengiriman.' },
        done:       { icon: '🎉', text: 'Belum ada order selesai di periode ini.' },
        cancel:     { icon: '🙂', text: 'Tidak ada order yang dibatalkan.' },
    };

    // ── Dropdown toggle ───────────────────────────────────────────────────
    window.toggleDropdown = function (id, e) {
        e.stopPropagation();
        const dd = $(id);
        const isOpen = dd.classList.contains('open');
        document.querySelectorAll('.hdr-dropdown.open').forEach(d => d.classList.remove('open'));
        if (!isOpen) dd.classList.add('open');
    };
    document.addEventListener('click', () => {
        document.querySelectorAll('.hdr-dropdown.open').forEach(d => d.classList.remove('open'));
    });

    // ── Date presets ──────────────────────────────────────────────────────
    const PRESET_LABELS = { 1: 'Hari ini', 7: '7 hari', 30: '30 hari', 90: '90 hari' };

    window.setDatePreset = function (days) {
        const to   = new Date();
        const from = new Date(Date.now() - (days - 1) * 86400000);
        const fmt  = d => d.toISOString().slice(0,10);
        $('mpDateFrom').value  = fmt(from);
        $('mpDateTo').value    = fmt(to);
        $('mpDateRange').value = fmt(from) + ' — ' + fmt(to);
        $('btnDateLabel').textContent = PRESET_LABELS[days] || (days + ' hari');
        document.querySelectorAll('#ddDate .hdr-dropdown-item').forEach(el => el.classList.remove('selected'));
        event.target.closest('.hdr-dropdown-item').classList.add('selected');
        $('ddDate').classList.remove('open');
        history.replaceState(null, '', location.pathname + '?date_from=' + fmt(from) + '&date_to=' + fmt(to));
        render();
    };

    // ── Flatpickr (manual date) ───────────────────────────────────────────
    if (window.flatpickr) {
        flatpickr($('mpDateRange'), {
            mode: 'range', dateFormat: 'Y-m-d',
            defaultDate: [getFrom(), getTo()],
            onChange(dates) {
                if (dates.length === 2) {
                    const fmt  = d => d.toISOString().slice(0,10);
                    const fmtS = d => d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                    $('mpDateFrom').value  = fmt(dates[0]);
                    $('mpDateTo').value    = fmt(dates[1]);
                    $('mpDateRange').value = fmt(dates[0]) + ' — ' + fmt(dates[1]);
                    $('btnDateLabel').textContent = fmtS(dates[0]) + '–' + fmtS(dates[1]);
                    history.replaceState(null, '', location.pathname + '?date_from=' + getFrom() + '&date_to=' + getTo());
                    $('ddDate').classList.remove('open');
                    render();
                }
            }
        });
    }

    // ── Search ────────────────────────────────────────────────────────────
    window.onSearchInput = function (input) {
        const clearBtn = $('searchClearBtn');
        clearBtn.classList.toggle('visible', input.value.length > 0);
        render();
    };

    window.clearSearch = function () {
        $('filterSearch').value = '';
        $('searchClearBtn').classList.remove('visible');
        $('filterSearch').focus();
        render();
    };

    // ── Store dropdown ────────────────────────────────────────────────────
    function populateStoreDropdown() {
        const names = [...new Set(orders.map(o => o.store?.name).filter(Boolean))].sort();
        const el = $('storeDropdownItems');
        el.innerHTML = `<div class="hdr-dropdown-item ${!activeStore?'selected':''}" onclick="selectStore('')">🏪 Semua Toko</div>` +
            names.map(n => `<div class="hdr-dropdown-item ${activeStore===n?'selected':''}" onclick="selectStore('${esc(n)}')">${esc(n)}</div>`).join('');
    }

    window.selectStore = function (name) {
        activeStore = name;
        $('btnStoreLabel').textContent = name || 'Semua Toko';
        $('btnStore').classList.toggle('active', !!name);
        $('ddStore').classList.remove('open');
        populateStoreDropdown();
        render();
    };

    // ── Tab switch ────────────────────────────────────────────────────────
    window.switchTab = function (tab, btn) {
        activeTab = tab;
        sessionStorage.setItem('ord_active_tab', tab);
        document.querySelectorAll('.ord-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        renderTable();
    };

    window.switchTabByName = function (tab) {
        const btn = document.querySelector(`.ord-tab[data-tab="${tab}"]`);
        if (btn) switchTab(tab, btn);
    };

    // Apply saved tab on init
    function restoreSavedTab() {
        const saved = sessionStorage.getItem('ord_active_tab') || 'process';
        const btn   = document.querySelector(`.ord-tab[data-tab="${saved}"]`);
        if (btn) {
            document.querySelectorAll('.ord-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }
    }

    window.toggleBelumProses = function () {
        filterBelumProses = !filterBelumProses;
        render();
    };

    function fulfillmentBadge(o) {
        if (fulfilledOrderIds.has(o.id)) return '<span class="fstatus fstatus-done">✓ Selesai</span>';
        const f = fulfillmentStatusMap.get(o.id);
        if (!f) return '<span class="fstatus fstatus-none">Belum Proses</span>';
        if (f.status === 'confirmed')      return '<span class="fstatus fstatus-done">✓ Selesai</span>';
        if (o.has_data_issues)             return '<span class="fstatus fstatus-draft">⚠ Perlu Perbaiki</span>';
        if (f.status === 'pending_review') return '<span class="fstatus fstatus-pending">Siap Konfirmasi</span>';
        if (f.status === 'draft')          return '<span class="fstatus fstatus-draft">Draft</span>';
        return '';
    }

    // ── Load ──────────────────────────────────────────────────────────────
    async function loadOrders() {
        $('ordersBody').innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        orders = await api('/api/marketplace/local-orders').catch(() => []);
        // Pre-populate fulfillment status dari data API
        fulfillmentStatusMap.clear();
        orders.forEach(o => {
            if (o.fulfillment_status) {
                fulfillmentStatusMap.set(o.id, { id: o.fulfillment_id, status: o.fulfillment_status });
                if (o.fulfillment_status === 'confirmed') fulfilledOrderIds.add(o.id);
            }
        });
        populateStoreDropdown();
        restoreSavedTab();
        render();
    }

    function inRange(o) {
        if (!o.ordered_at) return true;
        const d = new Date(o.ordered_at);
        return d >= new Date(getFrom() + 'T00:00:00') && d <= new Date(getTo() + 'T23:59:59');
    }

    function applyFilters(rows) {
        const search = getSearch();
        return rows
            .filter(o => !activeStore || o.store?.name === activeStore)
            .filter(o => !filterBelumProses || !fulfilledOrderIds.has(o.id))
            .filter(o => !search
                || (o.channel_order_id||'').toLowerCase().includes(search)
                || (o.items||[]).some(i =>
                    (i.internal_item?.code||'').toLowerCase().includes(search)
                    || (i.variant_name||'').toLowerCase().includes(search)
                    || (i.model_sku||i.item_sku||'').toLowerCase().includes(search)));
    }

    window.render = function () { renderKpi(); renderBadges(); renderTable(); updateToolbar(); };

    // ── Process Toolbar ───────────────────────────────────────────────────
    function getProcessRows() {
        return applyFilters(orders.filter(inRange))
            .filter(TAB_FILTERS.process);
    }

    function updateToolbar() {
        const toolbar = $('processToolbar');
        if (!['process', 'packing', 'unresolved'].includes(activeTab)) { toolbar.classList.remove('visible'); return; }

        const isUnresolved = activeTab === 'unresolved';
        $('toolbarActionsProcess').style.display  = isUnresolved ? 'none' : '';
        $('toolbarActionsUnresolved').style.display = isUnresolved ? '' : 'none';

        if (isUnresolved) {
            const rows = filterByTab(applyFilters(orders.filter(inRange)), 'unresolved');
            toolbar.classList.toggle('visible', rows.length > 0);
            $('toolbarInfo').innerHTML = `<strong>${rows.length}</strong> order perlu diperbaiki`;
        } else {
            const rows = getProcessRows();
            toolbar.classList.toggle('visible', rows.length > 0);
            $('toolbarInfo').innerHTML = `<strong id="toolbarCount">${rows.length}</strong> order perlu diproses hari ini`;
        }
    }

    // ── Print Picking List ────────────────────────────────────────────────
    window.printPickingList = function () {
        // Gunakan rows yang sudah terfilter (termasuk filterBelumProses jika aktif)
        const rows = getProcessRows();
        if (!rows.length) { alert('Tidak ada order untuk dicetak.'); return; }

        // Tandai semua order ini sebagai sudah dicetak
        rows.forEach(o => printedOrderIds.add(o.id));
        renderTable();

        const today    = new Date().toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
        const timeNow  = new Date().toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });

        // ── Agregasi item (ringkasan picking) ─────────────────────────
        const itemMap = {};
        rows.forEach(o => {
            (o.items || []).forEach(i => {
                const code    = i.internal_item?.code || '';
                const variant = i.variant_name || '';
                const mSku    = i.model_sku || i.item_sku || '';
                const key     = (code || mSku) + '||' + variant;
                if (!itemMap[key]) itemMap[key] = { code, variant, mSku, qty: 0, mapped: !!code };
                itemMap[key].qty += (i.qty || 1);
            });
        });

        // Urutkan: mapped dulu, lalu unmapped; dalam group urut qty desc
        const sortedItems = Object.values(itemMap).sort((a, b) => {
            if (a.mapped !== b.mapped) return b.mapped - a.mapped;
            return b.qty - a.qty;
        });

        const itemRows = sortedItems.map(it => {
            const label = it.mapped
                ? `<strong style="font-family:monospace">${it.code}</strong>${it.variant ? `<span style="color:#555"> — ${it.variant}</span>` : ''}`
                : `<span style="color:#aaa;font-family:monospace">${it.mSku || '—'} <em>(belum mapping)</em></span>`;
            return `<tr>
                <td class="chk"><input type="checkbox"></td>
                <td>${label}</td>
                <td class="qty">${it.qty}</td>
            </tr>`;
        }).join('');

        // ── Detail per order (halaman kedua / lanjutan) ───────────────
        const orderCards = rows.map((o, idx) => {
            const itemLines = (o.items || []).map(i => {
                const code    = i.internal_item?.code || '';
                const variant = i.variant_name || '';
                const mSku    = i.model_sku || i.item_sku || '';
                const label   = code
                    ? `<strong style="font-family:monospace">${code}</strong>${variant ? ` <span style="color:#555">— ${variant}</span>` : ''}`
                    : `<span style="color:#999;font-family:monospace">${mSku} <em>(belum mapping)</em></span>`;
                return `<div class="item-line">
                    <span>${label}</span>
                    <strong>${i.qty || 1}×</strong>
                </div>`;
            }).join('');

            return `<div class="order-card">
                <div class="order-header">
                    <span class="order-no">${idx + 1}. ${o.channel_order_id || '—'}</span>
                    <span class="order-meta">${o.store?.name || '—'} · ${fmtRp(o.total_amount)}</span>
                </div>
                <div class="order-body">${itemLines || '<em style="color:#999">Tidak ada item</em>'}</div>
            </div>`;
        }).join('');

        const totalQty    = sortedItems.reduce((s, i) => s + i.qty, 0);
        const totalAmount = rows.reduce((s, o) => s + (parseFloat(o.total_amount) || 0), 0);

        const html = `<!DOCTYPE html><html><head>
            <meta charset="UTF-8">
            <title>Picking List — ${today}</title>
            <style>
                *, *::before, *::after { box-sizing: border-box; }
                @page { size: A4; margin: 12mm 12mm 10mm; }
                body { font-family: -apple-system, Arial, sans-serif; margin: 0; color: #000; font-size: 11px; line-height: 1.4; }
                /* Toolbar (hanya tampil di layar, tidak dicetak) */
                #toolbar {
                    position: fixed; top: 0; left: 0; right: 0; z-index: 99;
                    background: #0f172a; color: #fff; padding: .6rem 1rem;
                    display: flex; align-items: center; justify-content: space-between;
                }
                #toolbar button {
                    background: #6366f1; color: #fff; border: none; border-radius: 8px;
                    padding: .4rem 1rem; font-weight: 700; font-size: .85rem; cursor: pointer;
                }
                #toolbar button:hover { background: #4f46e5; }
                #content { padding-top: 44px; }
                @media print { #toolbar { display: none; } #content { padding-top: 0; } }
                /* Header */
                .page-header {
                    display: flex; justify-content: space-between; align-items: flex-end;
                    border-bottom: 2.5px solid #000; padding-bottom: 5px; margin-bottom: 10px;
                }
                .page-title { font-size: 18px; font-weight: 900; letter-spacing: -.03em; }
                .page-meta  { font-size: 9px; color: #555; text-align: right; }
                /* Ringkasan */
                .section-title {
                    font-size: 9px; font-weight: 800; text-transform: uppercase;
                    letter-spacing: .08em; color: #333; margin: 10px 0 4px;
                    border-bottom: 1.5px solid #000; padding-bottom: 3px;
                }
                table { width: 100%; border-collapse: collapse; }
                table td, table th { padding: 3px 4px; border-bottom: 1px solid #eee; vertical-align: middle; }
                table th { font-size: 9px; color: #999; text-transform: uppercase; font-weight: 700; border-bottom: 1px solid #ddd; }
                .chk  { width: 20px; }
                .qty  { width: 36px; text-align: center; font-weight: 900; font-size: 12px; }
                .total-row td { border-top: 2px solid #000; font-weight: 900; padding: 4px; }
                /* Detail order */
                .order-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-top: 4px; }
                .order-card { border: 1px solid #ddd; border-radius: 4px; overflow: hidden; page-break-inside: avoid; }
                .order-header {
                    display: flex; justify-content: space-between; align-items: center;
                    background: #f4f4f4; padding: 4px 6px; border-bottom: 1px solid #ddd;
                }
                .order-no   { font-family: monospace; font-weight: 900; font-size: 11px; }
                .order-meta { font-size: 9px; color: #555; text-align: right; }
                .order-body { padding: 4px 6px; }
                .item-line  {
                    display: flex; justify-content: space-between; align-items: center;
                    padding: 2px 0; border-bottom: 1px dashed #eee; font-size: 11px;
                }
                .item-line:last-child { border-bottom: none; }
                .footer {
                    display: flex; justify-content: space-between; font-weight: 900;
                    font-size: 11px; border-top: 2px solid #000; padding-top: 4px; margin-top: 6px;
                }
            </style>
        </head>
        <body>
            <div id="toolbar">
                <span style="font-size:.85rem;font-weight:600">📋 Picking List — ${rows.length} order · ${totalQty} item</span>
                <button onclick="window.print()">🖨 Print</button>
            </div>
            <div id="content">
                <div class="page-header">
                    <div>
                        <div class="page-title">PICKING LIST</div>
                        <div style="font-size:9px;color:#666;margin-top:1px">${today} · ${timeNow}</div>
                    </div>
                    <div class="page-meta">
                        <div><strong>${rows.length}</strong> order</div>
                        <div><strong>${totalQty}</strong> total item</div>
                    </div>
                </div>

                <div class="section-title">Item yang Harus Diambil</div>
                <table>
                    <thead><tr>
                        <th class="chk"></th>
                        <th style="text-align:left">Kode Item — Varian</th>
                        <th class="qty">Qty</th>
                    </tr></thead>
                    <tbody>${itemRows}</tbody>
                    <tfoot><tr class="total-row">
                        <td></td><td>TOTAL</td><td class="qty">${totalQty}</td>
                    </tr></tfoot>
                </table>

                <div class="section-title" style="margin-top:14px">Detail Pesanan</div>
                <div class="order-grid">${orderCards}</div>

                <div class="footer">
                    <span>TOTAL ${rows.length} PESANAN</span>
                    <span>${fmtRp(totalAmount)}</span>
                </div>
            </div>
        </body></html>`;

        const win = window.open('', '_blank', 'width=900,height=1000');
        if (!win) { alert('Popup diblokir. Izinkan popup untuk halaman ini.'); return; }
        win.document.write(html);
        win.document.close();
        win.focus();
        // Tidak auto-print — user klik tombol Print di toolbar
    };

    // ── Bulk Fulfillment ──────────────────────────────────────────────────
    window.openBulkFulfill = function () {
        const rows    = getProcessRows();
        const pending = rows.filter(o => !fulfilledOrderIds.has(o.id));
        if (!pending.length) return;

        // Cek unmapped items
        const unmapped = pending.filter(o =>
            (o.items||[]).some(i => !i.internal_item?.code)
        );

        $('bulkConfirmView').style.display  = 'block';
        $('bulkProgressView').style.display = 'none';
        $('bulkDoneView').style.display     = 'none';
        $('bulkStartBtn').disabled = false;
        $('bulkStartBtn').textContent = '📦 Proses Sekarang';

        const newOrders     = pending.filter(o => !printedOrderIds.has(o.id));
        const printedOrders = pending.filter(o => printedOrderIds.has(o.id));

        let summaryParts = [`<strong>${pending.length}</strong> order akan diproses`];
        if (printedOrders.length && newOrders.length) {
            summaryParts.push(`<span style="color:#0369a1">🖨 ${printedOrders.length} sudah dicetak</span> + <span style="color:#dc2626;font-weight:700">🆕 ${newOrders.length} order baru (belum dicetak)</span>`);
        } else if (newOrders.length && !printedOrders.length) {
            summaryParts.push(`<span style="color:#dc2626;font-weight:700">⚠ Belum dicetak picking list!</span>`);
        }
        $('bulkSummaryText').innerHTML = summaryParts.join(' — ') + '. Item yang sudah mapping akan dikonfirmasi dan stok dipotong.';

        const warnEl = $('bulkUnmappedWarn');
        if (unmapped.length) {
            warnEl.className = 'alert alert-warning mb-3';
            warnEl.innerHTML = `⚠ <strong>${unmapped.length} order</strong> punya item belum mapping — item tersebut akan dilewati, item lainnya tetap diproses.`;
        } else {
            warnEl.className = 'alert d-none';
        }

        new bootstrap.Modal($('bulkFulfillModal')).show();
    };

    window.runBulkFulfill = async function () {
        const rows    = getProcessRows().filter(o => !fulfilledOrderIds.has(o.id));
        const btn     = $('bulkStartBtn');
        btn.disabled  = true;

        $('bulkConfirmView').style.display  = 'none';
        $('bulkProgressView').style.display = 'block';
        $('bulkProgressList').innerHTML     = '';

        let done = 0, skipped = 0, stockWarnings = [];

        for (let i = 0; i < rows.length; i++) {
            const o    = rows[i];
            const pct  = Math.round(((i + 1) / rows.length) * 100);
            $('bulkProgressBar').style.width = pct + '%';
            $('bulkProgressText').textContent = `Memproses ${i+1} / ${rows.length}…`;

            const li = document.createElement('div');
            li.className = 'bulk-prog-item';
            li.innerHTML = `<span class="bulk-prog-icon">⏳</span>
                <span style="flex:1;font-family:monospace;font-size:.73rem">${esc(o.channel_order_id)}</span>
                <span style="font-size:.7rem;color:#94a3b8">memproses…</span>`;
            $('bulkProgressList').appendChild(li);
            $('bulkProgressList').scrollTop = $('bulkProgressList').scrollHeight;

            try {
                const draft = await api('/api/fulfillments/create-draft', {
                    method: 'POST',
                    body: JSON.stringify({ marketplace_order_id: o.id }),
                });
                await api(`/api/fulfillments/${draft.id}/confirm`, { method: 'POST' });

                fulfilledOrderIds.add(o.id);
                fulfillmentStatusMap.set(o.id, { id: draft.id, status: 'confirmed' });
                done++;

                // Cek ada shortage?
                const hasShortage = (draft.lines || []).some(l => l.stock_status === 'low' || l.stock_status === 'empty');
                if (hasShortage) stockWarnings.push(o.channel_order_id);

                li.innerHTML = `<span class="bulk-prog-icon">✓</span>
                    <span style="flex:1;font-family:monospace;font-size:.73rem">${esc(o.channel_order_id)}</span>
                    <span style="font-size:.7rem;color:#16a34a">selesai</span>`;
            } catch (e) {
                skipped++;
                li.innerHTML = `<span class="bulk-prog-icon">✕</span>
                    <span style="flex:1;font-family:monospace;font-size:.73rem">${esc(o.channel_order_id)}</span>
                    <span style="font-size:.7rem;color:#dc2626">${e.message.slice(0,40)}</span>`;
            }
        }

        // Done view
        $('bulkProgressView').style.display = 'none';
        $('bulkDoneView').style.display     = 'block';
        let summaryHtml = `<div style="font-size:.85rem;margin-bottom:.5rem">
            <div>✅ <strong>${done}</strong> fulfillment berhasil dikonfirmasi</div>
            ${skipped ? `<div style="color:#dc2626">✕ <strong>${skipped}</strong> gagal</div>` : ''}
            ${stockWarnings.length ? `<div style="color:#d97706;margin-top:.3rem">⚠ <strong>${stockWarnings.length}</strong> order stok minus: ${stockWarnings.slice(0,3).join(', ')}${stockWarnings.length>3?'…':''}</div>` : ''}
        </div>`;
        $('bulkDoneSummary').innerHTML = summaryHtml;
    };

    // ── KPI ───────────────────────────────────────────────────────────────
    function renderKpi() {
        const rows = applyFilters(orders.filter(inRange));
        $('kpiProcess').textContent   = rows.filter(o => ACTIVE_ORDER_STATUSES.includes(o.order_status) && !fulfillmentStatusMap.has(o.id)).length;
        $('kpiShipping').textContent  = rows.filter(o => TAB_STATUSES.shipping.includes(o.order_status)).length;
        $('kpiCompleted').textContent = rows.filter(o => o.order_status === 'COMPLETED').length;
        $('kpiCancelled').textContent = rows.filter(o => o.order_status === 'CANCELLED').length;
    }

    // ── Badges ────────────────────────────────────────────────────────────
    function renderBadges() {
        const rows = orders.filter(inRange);
        [...Object.keys(TAB_STATUSES), 'packing'].forEach(tab => {
            const el = $('badge-' + tab);
            if (!el) return;
            const count = tab === 'all' ? rows.length : filterByTab(rows, tab).length;
            el.textContent = count;
        });
        const btnFilter = $('btnBelumProses');
        if (btnFilter) btnFilter.classList.toggle('active', filterBelumProses);
    }

    // ── Render scan log sebagai konten <td> kolom "Item Scan" ──────────────
    function renderScanLogTd(scanLog) {
        if (!scanLog || !scanLog.length) return '<div class="ord-items-cell" style="color:#cbd5e1;font-size:.72rem;font-style:italic">—</div>';

        // Filter: hanya tampilkan item yang punya code valid dan qty > 0
        const valid = scanLog.filter(s => s.code && s.qty > 0);
        if (!valid.length) return '<div class="ord-items-cell" style="color:#cbd5e1;font-size:.72rem;font-style:italic">—</div>';

        const cards = valid.map(s => {
            const name = s.name || '';
            return `<div class="ord-item-card" style="border-color:#ddd6fe;background:#faf5ff">
                <div class="ord-item-qty" style="background:#ede9fe;color:#6d28d9">${s.qty}×</div>
                <div class="ord-item-body">
                    <div class="ord-item-name" style="color:#4c1d95">${esc(s.code)}</div>
                    ${name ? `<div class="ord-item-variant">${esc(name)}</div>` : ''}
                </div>
            </div>`;
        }).join('');

        return `<div class="ord-items-cell">${cards}</div>`;
    }

    // ── Render fulfilled line card (tab Sudah Proses) ────────────────────
    function renderFulfilledLineCard(l) {
        // Skip split parent placeholder — children di-render sendiri
        if (l.is_split_parent) return '';

        const code     = l.item?.code || l.marketplace_sku || '—';
        const name     = l.item?.name || l.marketplace_item_name || '';
        const qtyFul   = l.qty_fulfilled ?? l.qty_ordered ?? 1;
        const qtyOrd   = l.qty_ordered   ?? qtyFul;
        const isShort  = qtyFul < qtyOrd;

        let ket = '';
        if (l.substituted && !l.split_parent_id) {
            const asal = l.marketplace_sku ? ` dari ${l.marketplace_sku}` : '';
            ket = `<div><span class="ord-ket ord-ket-sub">🔄 Diganti${esc(asal)}</span></div>`;
        } else if (l.split_parent_id) {
            ket = `<div><span class="ord-ket ord-ket-spl">✂ Split</span></div>`;
        }
        if (isShort) {
            ket += `<div><span class="ord-ket" style="background:#fef3c7;color:#92400e;border-color:#fde68a">⚠ Kurang ${qtyFul}/${qtyOrd}</span></div>`;
        }
        if (l.notes) {
            ket += `<div style="font-size:.63rem;color:#64748b;margin-top:.1rem">${esc(l.notes)}</div>`;
        }

        // Card border merah jika kurang, kuning jika diganti/split, normal jika ok
        const cardStyle = isShort
            ? 'border-color:#fecaca;background:#fff8f8'
            : (l.substituted || l.split_parent_id) ? 'border-color:#fde68a;background:#fffbeb' : '';

        const bodyHtml = `<div class="ord-item-name">${esc(code)}</div>`
            + (name ? `<div class="ord-item-variant">${esc(name)}</div>` : '')
            + ket;

        return `<div class="ord-item-card" style="${cardStyle}">
            <div class="ord-item-qty">${qtyFul}×</div>
            <div class="ord-item-body">${bodyHtml}</div>
        </div>`;
    }

    // Apakah order punya masalah di fulfilled lines (kurang atau item tidak sesuai)?
    function fulfilledOrderIssues(lines) {
        const active = lines.filter(l => !l.is_split_parent);
        const hasShort = active.some(l => (l.qty_fulfilled ?? l.qty_ordered) < (l.qty_ordered ?? 1));
        const hasSub   = active.some(l => l.substituted && !l.split_parent_id);
        const hasSplit = active.some(l => l.split_parent_id);
        return { hasShort, hasSub, hasSplit, hasAny: hasShort || hasSub || hasSplit };
    }

    // ── Render satu item card ─────────────────────────────────────────────
    function renderItemCard(i, urgent) {
        const internalCode = i.internal_item?.code || null;
        const variantName  = i.variant_name || null;
        const mSku         = i.model_sku || i.item_sku || null;

        let bodyHtml = '';
        if (internalCode) {
            // Tampilkan kode internal sebagai focal, variant + marketplace SKU di bawah
            bodyHtml = `<div class="ord-item-name">${esc(internalCode)}</div>`
                + (variantName ? `<div class="ord-item-variant">${esc(variantName)}</div>` : '')
                + (mSku ? `<div class="ord-item-sku">${esc(mSku)}</div>` : '');
        } else if (mSku) {
            bodyHtml = `<div class="ord-item-name" style="color:#64748b">${esc(mSku)}</div>`
                + (variantName ? `<div class="ord-item-variant">${esc(variantName)}</div>` : '')
                + `<span class="ord-item-nomap">Belum mapping</span>`;
        } else {
            bodyHtml = `<span class="ord-item-nomap">Belum mapping</span>`;
        }

        const qtyClass = urgent ? 'ord-item-qty urgent' : 'ord-item-qty';
        return `<div class="ord-item-card">
            <div class="${qtyClass}">${i.qty || 1}×</div>
            <div class="ord-item-body">${bodyHtml}</div>
        </div>`;
    }

    // ── Table ─────────────────────────────────────────────────────────────
    function filterByTab(rows, tab) {
        const statuses = TAB_STATUSES[tab];
        const fn       = TAB_FILTERS[tab];
        if (statuses) return rows.filter(o => statuses.includes(o.order_status));
        if (fn)       return rows.filter(fn);
        return rows; // tab 'all'
    }

    const isMobile = () => window.innerWidth <= 640;

    let _accId = 0;
    function makeAccordion(label, labelStyle, count, bodyHtml) {
        const id = 'acc-' + (++_accId);
        return `<div class="ord-card-section">
            <div class="ord-acc-toggle" onclick="toggleAcc('${id}',this)">
                <span class="ord-acc-label" style="${labelStyle}">${label}
                    <span class="ord-acc-count">${count}</span>
                </span>
                <span class="ord-acc-chevron">▼</span>
            </div>
            <div class="ord-acc-body" id="${id}">
                <div class="ord-items-cell">${bodyHtml}</div>
            </div>
        </div>`;
    }

    window.toggleAcc = function(id, toggle) {
        const body = document.getElementById(id);
        if (!body) return;
        const open = body.classList.toggle('open');
        toggle.classList.toggle('open', open);
    };

    function renderMobileCards(rows) {
        const isPacking = activeTab === 'packing' || activeTab === 'fulfilled';

        const cards = rows.map(o => {
            const items  = o.items || [];
            const urgent = ACTIVE_ORDER_STATUSES.includes(o.order_status);
            const isFulfilled = fulfilledOrderIds.has(o.id);
            const isPrinted   = printedOrderIds.has(o.id);
            const isInPacking = fulfillmentStatusMap.has(o.id) && !fulfilledOrderIds.has(o.id);
            const rowClass = isFulfilled ? 'row-fulfilled' : (isInPacking ? 'row-packing' : (urgent ? 'row-urgent' : ''));

            // Tombol aksi
            let fulfillBtn = '';
            if (urgent) {
                if (isFulfilled) {
                    fulfillBtn = `<div class="btn-fulfillment done">✓ Selesai</div>`;
                } else if (isInPacking) {
                    const fStatus = fulfillmentStatusMap.get(o.id)?.status || '';
                    const statusLabel = fStatus === 'picking' ? '🔄 Picking'
                        : fStatus === 'packed' ? '📦 Packed'
                        : fStatus === 'pending_review' ? '⏳ Review' : '📋 Draft';
                    fulfillBtn = `<button class="btn-fulfillment" style="background:#eff6ff;color:#2563eb;border-color:#bfdbfe"
                        onclick="window.location='/marketplace/fulfillment'">${statusLabel} →</button>`;
                } else {
                    fulfillBtn = `<button class="btn-fulfillment"
                        onclick="window.location='/marketplace/fulfillment?scan=${encodeURIComponent(o.channel_order_id)}'">📦 Proses</button>`;
                }
            }

            // Sub-info
            const storeName   = o.store?.name || '';
            const channelName = o.store?.channel?.name || '';
            const storeText   = [storeName, channelName].filter(Boolean).join(' · ');
            const dateText    = o.ordered_at ? fmtDate(o.ordered_at) : '';

            // ── Section: Item Produk (accordion)
            const itemCards = items.map(i => renderItemCard(i, urgent)).join('');
            const itemsSection = makeAccordion('Item Produk', '', items.length, itemCards);

            // ── Section: Item Resolve (hanya packing, accordion)
            let resolveSection = '';
            if (isPacking) {
                const resolveLines = (o.fulfillment_resolve_lines || []).filter(l => l.code);
                if (resolveLines.length) {
                    const resolveCards = resolveLines.map(l => `<div class="ord-item-card" style="border-color:#bfdbfe;background:#eff6ff">
                        <div class="ord-item-qty" style="background:#dbeafe;color:#1d4ed8">${l.qty}×</div>
                        <div class="ord-item-body">
                            <div class="ord-item-name" style="color:#1e3a5f">${esc(l.code)}</div>
                            ${l.name ? `<div class="ord-item-variant">${esc(l.name)}</div>` : ''}
                        </div>
                    </div>`).join('');
                    resolveSection = makeAccordion('✅ Item Resolve', 'color:#1d4ed8', resolveLines.length, resolveCards);
                }
            }

            // ── Section: Item Scan (hanya packing, accordion)
            let scanSection = '';
            if (isPacking) {
                const validScan = (o.fulfillment_scan_log || []).filter(s => s.code && s.qty > 0);
                if (validScan.length) {
                    const scanCards = validScan.map(s => `<div class="ord-item-card" style="border-color:#ddd6fe;background:#faf5ff">
                        <div class="ord-item-qty" style="background:#ede9fe;color:#6d28d9">${s.qty}×</div>
                        <div class="ord-item-body">
                            <div class="ord-item-name" style="color:#4c1d95">${esc(s.code)}</div>
                            ${s.name ? `<div class="ord-item-variant">${esc(s.name)}</div>` : ''}
                        </div>
                    </div>`).join('');
                    scanSection = makeAccordion('📦 Item Scan', 'color:#7c3aed', validScan.length, scanCards);
                }
            }

            return `<div class="ord-card ${rowClass}${isPrinted && !isFulfilled ? ' row-printed' : ''}">
                <div class="ord-card-header">
                    <div class="ord-card-meta">
                        <div class="ord-id">${esc(o.channel_order_id || '—')}</div>
                        <div class="ord-card-sub">
                            ${dateText ? `<span class="ord-card-sub-text">${dateText}</span>` : ''}
                            ${storeText ? `<span class="ord-card-sub-text" style="color:#cbd5e1">·</span><span class="ord-card-sub-text">${esc(storeText)}</span>` : ''}
                        </div>
                    </div>
                    <div class="ord-card-actions">
                        ${statusBadge(o.order_status)}
                        ${urgent ? fulfillmentBadge(o) : ''}
                        ${fulfillBtn}
                        ${isPrinted && !isFulfilled ? `<span style="font-size:.6rem;background:#e0f2fe;color:#0369a1;border-radius:4px;padding:1px 5px;font-weight:700">🖨 Cetak</span>` : ''}
                    </div>
                </div>
                ${itemsSection}
                ${resolveSection}
                ${scanSection}
            </div>`;
        }).join('');

        return `<div class="ord-cards">${cards}</div>
        <div class="gf-table-foot" style="padding:.6rem .9rem">
            <span class="gf-table-foot-hint">${rows.length} order ditampilkan</span>
        </div>`;
    }

    function renderProcessCardList(rows) {
        const pkRows = rows.map(o => {
            const items       = o.items || [];
            const isFulfilled = fulfilledOrderIds.has(o.id);
            const isInPacking = fulfillmentStatusMap.has(o.id) && !isFulfilled;
            const fStatus     = fulfillmentStatusMap.get(o.id)?.status || '';
            const store       = [o.store?.name, o.store?.channel?.name].filter(Boolean).join(' · ');

            // Badge fulfillment
            const fBadgeHtml = isFulfilled
                ? `<span class="pk-badge-ok">✓ Selesai</span>`
                : isInPacking
                    ? (fStatus === 'pending_review'
                        ? `<span class="pk-badge-short">⏳ Siap Konfirmasi</span>`
                        : `<span style="display:inline-flex;align-items:center;gap:.2rem;font-size:.65rem;font-weight:700;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:999px;padding:.08rem .45rem">🔄 Dalam Proses</span>`)
                    : `<span style="display:inline-flex;align-items:center;gap:.2rem;font-size:.65rem;font-weight:700;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:999px;padding:.08rem .45rem">● Belum Proses</span>`;

            // Item chips (compact)
            const itemChips = items.slice(0, 5).map(i => {
                const code = i.internal_item?.code || i.model_sku || i.item_sku || '?';
                const qty  = i.qty || 1;
                const mapped = !!i.internal_item?.code;
                return `<span style="display:inline-flex;align-items:center;gap:.2rem;font-size:.65rem;font-weight:700;
                    background:${mapped ? '#f1f5f9' : '#fef3c7'};color:${mapped ? '#334155' : '#92400e'};
                    border-radius:6px;padding:.1rem .38rem;font-family:'SF Mono','Menlo',monospace;white-space:nowrap">
                    ${qty}× ${esc(code)}
                </span>`;
            }).join('');
            const moreChip = items.length > 5
                ? `<span style="font-size:.63rem;color:#94a3b8;font-weight:600">+${items.length - 5} lagi</span>`
                : '';

            // Tombol aksi
            let actionBtn = '';
            if (isFulfilled) {
                actionBtn = `<div class="btn-review" style="background:#f0fdf4;color:#16a34a;border-color:#bbf7d0;cursor:default">✓ Selesai</div>`;
            } else if (isInPacking) {
                actionBtn = `<button class="btn-review" style="background:#eff6ff;color:#2563eb;border-color:#bfdbfe"
                    onclick="window.location='/marketplace/fulfillment'">Lanjut →</button>`;
            } else {
                actionBtn = `<button class="btn-review"
                    onclick="window.location='/marketplace/fulfillment?scan=${encodeURIComponent(o.channel_order_id)}'">📦 Proses</button>`;
            }

            const dataIssue = o.has_data_issues
                ? `<span style="font-size:.63rem;font-weight:700;color:#d97706;background:#fef3c7;border:1px solid #fde68a;border-radius:999px;padding:.05rem .38rem">⚠ Belum mapping</span>`
                : '';

            return `<div class="pk-row">
                <div class="pk-row-left">
                    <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                        <span class="pk-order-id">${esc(o.channel_order_id || '—')}</span>
                        ${fBadgeHtml}
                        ${dataIssue}
                    </div>
                    <div class="pk-row-meta" style="margin-top:.28rem">
                        ${store ? `<span class="pk-meta-text">${esc(store)}</span><span class="pk-meta-text" style="color:#e2e8f0">·</span>` : ''}
                        <div style="display:flex;flex-wrap:wrap;gap:.25rem;align-items:center">
                            ${itemChips}${moreChip}
                        </div>
                    </div>
                </div>
                ${actionBtn}
            </div>`;
        }).join('');

        const count = rows.length;
        return `<div class="pk-section">
            <div class="pk-section-header">
                <div>
                    <div class="pk-section-title">⚡ Perlu Diproses</div>
                    <div class="pk-section-sub">Order baru menunggu untuk diproses</div>
                </div>
                <span class="pk-count-badge" style="background:#f59e0b">${count} order</span>
            </div>
            ${pkRows}
        </div>
        <div class="gf-table-foot">
            <span class="gf-table-foot-hint">${count} order ditampilkan</span>
        </div>`;
    }

    function renderPackingCardList(rows) {
        const pkRows = rows.map(o => {
            const summary = o.fulfillment_packing_summary;
            const fStatus = fulfillmentStatusMap.get(o.id)?.status || '';
            const store   = [o.store?.name, o.store?.channel?.name].filter(Boolean).join(' · ');

            // Packing info text
            const totalOrd = summary?.total_ordered   ?? 0;
            const totalFul = summary?.total_fulfilled ?? 0;
            const hasShort = summary?.has_shortage    ?? false;
            const packInfo = totalOrd > 0
                ? `<span class="pk-pack-info ${hasShort ? 'short' : ''}">${totalFul}/${totalOrd} dipacking</span>`
                : '';

            // Status badge
            const statusBadgeEl = hasShort
                ? `<span class="pk-badge-short">⚠ kurang</span>`
                : (totalFul > 0 ? `<span class="pk-badge-ok">✓ lengkap</span>` : '');

            // fStatus label
            const fStatusLabel = fStatus === 'packed' ? '📦 Packed'
                : fStatus === 'picking' ? '🔄 Picking'
                : fStatus === 'pending_review' ? '⏳ Review' : '';

            return `<div class="pk-row">
                <div class="pk-row-left">
                    <div class="pk-order-id">${esc(o.channel_order_id || '—')}</div>
                    <div class="pk-row-meta">
                        ${store ? `<span class="pk-meta-text">${esc(store)}</span>` : ''}
                        ${fStatusLabel ? `<span class="pk-meta-text" style="color:#7c3aed;font-weight:700">${fStatusLabel}</span>` : ''}
                        ${statusBadgeEl}
                        ${packInfo}
                    </div>
                </div>
                <button class="btn-review" onclick="openReviewModal(${o.id})">🔍 Review</button>
            </div>`;
        }).join('');

        const count = rows.length;
        return `<div class="pk-section">
            <div class="pk-section-header">
                <div>
                    <div class="pk-section-title">📋 Sedang Proses</div>
                    <div class="pk-section-sub">Order sudah diproses — menunggu konfirmasi potong stok</div>
                </div>
                <span class="pk-count-badge">${count} order</span>
            </div>
            ${pkRows}
        </div>
        <div class="gf-table-foot">
            <span class="gf-table-foot-hint">${count} order ditampilkan</span>
        </div>`;
    }

    function renderTable() {
        const body = $('ordersBody');
        let rows = applyFilters(orders.filter(inRange));
        rows = filterByTab(rows, activeTab);

        if (!rows.length) {
            const { icon, text } = TAB_EMPTY[activeTab] || TAB_EMPTY.all;
            body.innerHTML = `<div class="ord-empty"><div class="ord-empty-icon">${icon}</div>${text}</div>`;
            return;
        }

        // Tab Perlu Proses: card-list layout
        if (activeTab === 'process') {
            body.innerHTML = renderProcessCardList(rows);
            return;
        }

        // Tab Sedang Proses: card-list layout (semua ukuran layar)
        if (activeTab === 'packing') {
            body.innerHTML = renderPackingCardList(rows);
            return;
        }

        if (isMobile()) {
            body.innerHTML = renderMobileCards(rows);
            return;
        }

        const tableRows = rows.map(o => {
            const items  = o.items || [];
            const urgent = ACTIVE_ORDER_STATUSES.includes(o.order_status);

            const showPackingCols = activeTab === 'packing' || activeTab === 'fulfilled';
            let itemsHtml;
            if (activeTab === 'fulfilled' && o.fulfillment_lines?.length) {
                const cards = o.fulfillment_lines.map(l => renderFulfilledLineCard(l)).filter(Boolean);
                itemsHtml = `<div class="ord-items-cell">${cards.join('')}</div>`;
            } else {
                itemsHtml = `<div class="ord-items-cell">`
                    + items.map(i => renderItemCard(i, urgent)).join('')
                    + `</div>`;
            }
            const scanTd = showPackingCols ? `<td>${renderScanLogTd(o.fulfillment_scan_log)}</td>` : '';

            // Item Resolve column (Sedang Proses tab)
            let resolveTd = '';
            if (activeTab === 'packing') {
                const resolveLines = (o.fulfillment_resolve_lines || []).filter(l => l.code);
                if (resolveLines.length) {
                    const resolveCards = resolveLines.map(l => `<div class="ord-item-card" style="border-color:#bfdbfe;background:#eff6ff">
                        <div class="ord-item-qty" style="background:#dbeafe;color:#1d4ed8">${l.qty}×</div>
                        <div class="ord-item-body">
                            <div class="ord-item-name" style="color:#1e3a5f">${esc(l.code)}</div>
                            ${l.name ? `<div class="ord-item-variant">${esc(l.name)}</div>` : ''}
                        </div>
                    </div>`).join('');
                    resolveTd = `<td><div class="ord-items-cell">${resolveCards}</div></td>`;
                } else {
                    resolveTd = `<td><div style="color:#cbd5e1;font-size:.72rem;font-style:italic">—</div></td>`;
                }
            }

            const isFulfilled = fulfilledOrderIds.has(o.id);
            const isPrinted   = printedOrderIds.has(o.id);
            const isInPacking = fulfillmentStatusMap.has(o.id) && !fulfilledOrderIds.has(o.id);
            const rowClass    = isFulfilled ? 'row-fulfilled' : (isInPacking ? 'row-packing' : (urgent ? 'row-urgent' : ''));
            let fulfillBtn = '';
            if (urgent) {
                if (isFulfilled) {
                    fulfillBtn = `<div class="btn-fulfillment done" style="margin-top:.35rem">✓ Selesai</div>`;
                } else if (isInPacking) {
                    // Order sedang dipacking — tombol lanjutkan
                    const fStatus = fulfillmentStatusMap.get(o.id)?.status || '';
                    const statusLabel = fStatus === 'picking' ? '🔄 Picking…'
                        : fStatus === 'packed'   ? '📦 Packed'
                        : fStatus === 'pending_review' ? '⏳ Review'
                        : '📋 Draft';
                    fulfillBtn = `<button class="btn-fulfillment" style="margin-top:.35rem;background:#eff6ff;color:#2563eb;border-color:#bfdbfe"
                        onclick="window.location='/marketplace/fulfillment'">${statusLabel} →</button>`;
                } else {
                    fulfillBtn = `<button class="btn-fulfillment" style="margin-top:.35rem" onclick="window.location='/marketplace/fulfillment?scan=${encodeURIComponent(o.channel_order_id)}'">📦 Proses</button>`;
                }
            }

            const printedBadge = isPrinted && !isFulfilled
                ? `<span style="display:inline-block;margin-top:.3rem;font-size:.65rem;background:#e0f2fe;color:#0369a1;border-radius:4px;padding:1px 5px;font-weight:600">🖨 Sudah Cetak</span>`
                : '';

            const fBadge = urgent ? `<div style="margin-top:.3rem">${fulfillmentBadge(o)}</div>` : '';

            // Badge masalah di tab Sudah Proses
            let issueBadge = '';
            if (activeTab === 'fulfilled' && o.fulfillment_lines?.length) {
                const iss = fulfilledOrderIssues(o.fulfillment_lines);
                if (iss.hasAny) {
                    const parts = [];
                    if (iss.hasShort) parts.push('Kurang');
                    if (iss.hasSub)   parts.push('Diganti');
                    if (iss.hasSplit) parts.push('Split');
                    issueBadge = `<div style="margin-top:.3rem">
                        <span style="display:inline-block;font-size:.63rem;font-weight:700;
                            background:#fef3c7;color:#92400e;border:1px solid #fde68a;
                            border-radius:4px;padding:1px 6px">⚠ ${parts.join(' · ')}</span>
                    </div>`;
                }
            }

            const rowClick = activeTab === 'unresolved'
                ? `onclick="window.location='/marketplace/issues'" style="cursor:pointer"`
                : '';

            return `<tr class="${rowClass}${isPrinted && !isFulfilled ? ' row-printed' : ''}" id="ord-row-${o.id}" ${rowClick}>
                <td>
                    <div class="ord-id">${esc(o.channel_order_id || '—')}</div>
                    <div class="ord-date">${o.ordered_at ? fmtDate(o.ordered_at) : '—'}</div>
                    ${printedBadge}
                    ${fBadge}
                    ${issueBadge}
                    ${fulfillBtn}
                </td>
                <td>${itemsHtml}</td>
                ${resolveTd}
                ${scanTd}
                <td>${statusBadge(o.order_status)}</td>
                <td>
                    <div style="font-weight:700;font-size:.78rem">${esc(o.store?.name || '—')}</div>
                    <div style="margin-top:.2rem">${channelPill(o.store?.channel)}</div>
                </td>
            </tr>`;
        }).join('');

        const hasResolveCol = activeTab === 'packing';
        const hasScanCol    = activeTab === 'packing' || activeTab === 'fulfilled';
        // col widths: order(12%) | items | resolve? | scan? | status | store
        const colItems  = hasResolveCol ? '22%' : (hasScanCol ? '28%' : '44%');
        const colStatus = hasResolveCol ? '9%'  : (hasScanCol ? '10%' : '13%');
        const colStore  = hasResolveCol ? '14%' : (hasScanCol ? '16%' : '17%');
        body.innerHTML = `
        <div class="gf-table-scroll">
        <table class="ord-table">
            <colgroup>
                <col style="width:12%">
                <col style="width:${colItems}">
                ${hasResolveCol ? '<col style="width:20%">' : ''}
                ${hasScanCol    ? '<col style="width:20%">' : ''}
                <col style="width:${colStatus}">
                <col style="width:${colStore}">
            </colgroup>
            <thead><tr>
                <th>Nomor Order</th>
                <th>Item Produk</th>
                ${hasResolveCol ? '<th>✅ Item Resolve</th>' : ''}
                ${hasScanCol    ? '<th>📦 Item Scan</th>'    : ''}
                <th>Status</th>
                <th>Toko</th>
            </tr></thead>
            <tbody>${tableRows}</tbody>
        </table></div>
        <div class="gf-table-foot">
            <span class="gf-table-foot-hint">${rows.length} order ditampilkan</span>
        </div>`;
    }

    // ── Quick Sync ────────────────────────────────────────────────────────
    window.openQuickSync = function () {
        $('qsAlert').className = 'alert d-none';
        $('qsProgress').style.display = 'none';
        $('qsProgressBar').style.width = '0%';
        $('qsRunBtn').disabled = false;
        $('qsRunBtn').textContent = '↓ Sync Sekarang';
        new bootstrap.Modal($('quickSyncModal')).show();
    };

    window.runQuickSync = async function () {
        const days = parseInt($('qsSyncRange').value) || 3;
        const now  = Math.floor(Date.now() / 1000);
        const from = now - days * 86400;
        const btn  = $('qsRunBtn');

        btn.disabled = true; btn.textContent = '⏳ Syncing…';
        $('qsAlert').className = 'alert d-none';
        $('qsProgress').style.display = 'block';
        $('qsProgressBar').style.width = '10%';
        $('qsProgressText').textContent = 'Mengambil daftar toko…';

        try {
            const stores = await api('/api/marketplace/stores');
            const active = stores.filter(s => !s.token_expires_at || new Date(s.token_expires_at) > new Date());
            if (!active.length) {
                showQsAlert('warning', 'Tidak ada toko aktif yang terhubung.');
                btn.disabled = false; btn.textContent = '↓ Sync Sekarang'; return;
            }
            let totalNew = 0, totalIssues = 0;
            for (let i = 0; i < active.length; i++) {
                const s = active[i];
                $('qsProgressBar').style.width = (10 + Math.round(((i+1)/active.length)*85)) + '%';
                $('qsProgressText').textContent = `Sync ${s.name} (${i+1}/${active.length})…`;
                try {
                    const d = await api('/api/marketplace/stores/' + s.id + '/sync-orders', {
                        method: 'POST',
                        body: JSON.stringify({ time_from: from, time_to: now, page_size: 50 }),
                    });
                    totalNew    += d.new || d.synced || 0;
                    totalIssues += (d.sku_empty||0) + (d.mapping_not_found||0) + (d.missing_hpp||0);
                } catch (e) { /* lanjut */ }
            }
            $('qsProgressBar').style.width = '100%';
            $('qsProgress').style.display = 'none';
            let msg = `Sync selesai. ${totalNew} order baru.`;
            if (totalIssues > 0) msg += ` ⚠ ${totalIssues} item perlu diperbaiki.`;
            showQsAlert('success', msg);
            btn.textContent = '✓ Selesai';
            loadOrders();
        } catch (e) {
            $('qsProgress').style.display = 'none';
            showQsAlert('danger', 'Gagal: ' + e.message);
            btn.disabled = false; btn.textContent = '↓ Sync Sekarang';
        }
    };

    function showQsAlert(type, msg) {
        const el = $('qsAlert');
        el.className = `alert alert-${type} mb-3`;
        el.textContent = msg;
    }

    window.loadOrders = loadOrders;

    // ── [DEV ONLY] Fresh Orders ───────────────────────────────────────────────
    async function devFreshOrders() {
        const confirmed = window.confirm(
            '⚠️ [DEV MODE]\n\nIni akan menghapus SEMUA marketplace orders, fulfillments, dan inventory mutations terkait.\n\nLanjutkan?'
        );
        if (!confirmed) return;

        const btn = document.getElementById('btnFreshOrders');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Menghapus...'; }

        try {
            const res = await fetch('/api/dev/fresh-orders', { method: 'POST' });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal');
            alert(data.message);
            loadOrders();
        } catch (e) {
            alert('Error: ' + e.message);
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = '🗑 Fresh'; }
        }
    }
    window.devFreshOrders = devFreshOrders;

    // ── [DEV ONLY] Seed Dummy Orders ─────────────────────────────────────────
    async function devSeedOrders() {
        const input = window.prompt(
            '📥 [DEV MODE] Buat dummy READY_TO_SHIP orders\n\nMasukkan jumlah order (1–50):',
            '5'
        );
        if (input === null) return; // user cancel

        const count = parseInt(input, 10);
        if (isNaN(count) || count < 1 || count > 50) {
            alert('Jumlah tidak valid. Masukkan angka 1–50.');
            return;
        }

        const btn = document.getElementById('btnSeedOrders');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Membuat...'; }

        try {
            const res = await fetch('/api/dev/seed-orders', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ count, status: 'READY_TO_SHIP' }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal');
            alert(data.message);
            loadOrders();
        } catch (e) {
            alert('Error: ' + e.message);
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = '📥 Dummy'; }
        }
    }
    window.devSeedOrders = devSeedOrders;

    // ── [DEV ONLY] Reset Fulfillments ────────────────────────────────────────
    async function devResetFulfillments() {
        const ok = window.confirm('🔄 [DEV MODE]\n\nHapus semua fulfillments?\nOrder akan kembali ke tab "Perlu Proses".\n(Data order tidak dihapus)');
        if (!ok) return;
        const btn = document.getElementById('btnResetFulfillments');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Mereset...'; }
        try {
            const res  = await fetch('/api/dev/reset-fulfillments', { method: 'POST' });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal');
            alert(data.message);
            loadOrders();
            devLoadStats();
        } catch (e) { alert('Error: ' + e.message); }
        finally { if (btn) { btn.disabled = false; btn.textContent = '🔄 Reset Fulfillments'; } }
    }
    window.devResetFulfillments = devResetFulfillments;

    // ── [DEV ONLY] Remap Items ────────────────────────────────────────────────
    async function devRemapItems() {
        const btn = document.getElementById('btnRemapItems');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Remapping...'; }
        try {
            const res  = await fetch('/api/marketplace/remap-items', { method: 'POST' });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal');
            alert(`✅ Remap selesai.\nUpdated: ${data.updated ?? '?'} item\nErrors: ${data.errors ?? 0}`);
            devLoadStats();
        } catch (e) { alert('Error: ' + e.message); }
        finally { if (btn) { btn.disabled = false; btn.textContent = '🔁 Remap Items'; } }
    }
    window.devRemapItems = devRemapItems;

    // ── [DEV ONLY] Panel toggle + stats ──────────────────────────────────────
    async function devLoadStats() {
        try {
            const s   = await (await fetch('/api/dev/stats')).json();
            const el  = document.getElementById('devStats');
            if (el) el.textContent =
                `📦 ${s.orders} orders  |  ⚡ ${s.perluProses} perlu proses  |  🔄 ${s.sedangPacking} packing  |  ✅ ${s.fulfilled} selesai`;
        } catch {}
    }
    function toggleDevPanel() {
        const panel = document.getElementById('devPanel');
        const btn   = document.getElementById('btnDevPanel');
        if (!panel) return;
        const open = panel.style.display === 'none';
        panel.style.display = open ? 'block' : 'none';
        if (btn) btn.style.background = open ? '#ede9fe' : '#faf5ff';
        if (open) devLoadStats();
    }
    window.toggleDevPanel  = toggleDevPanel;
    window.devLoadStats    = devLoadStats;
    // ─────────────────────────────────────────────────────────────────────────

    loadOrders();

    // Re-render on resize (mobile ↔ desktop switch)
    // ── Review Modal (Sedang Proses) ────────────────────────────────────────
    window.openReviewModal = function(orderId) {
        const o = orders.find(x => x.id === orderId);
        if (!o) return;

        $('ormTitle').textContent = o.channel_order_id || '—';
        const store = [o.store?.name, o.store?.channel?.name].filter(Boolean).join(' · ');
        $('ormSub').textContent = store;
        $('ordReviewBg').classList.add('open');
        document.body.style.overflow = 'hidden';

        const lines    = o.fulfillment_resolve_lines || [];
        const scanLog  = (o.fulfillment_scan_log || []).filter(s => s.code && s.qty > 0);
        const summary  = o.fulfillment_packing_summary;

        // DATA PESANAN table
        let pesananHtml = '';
        if (lines.length) {
            const rows = lines.filter(l => l.code).map(l => {
                const isShort = (l.qty_fulfilled ?? 0) < (l.qty_ordered ?? 1);
                const statusHtml = isShort
                    ? `<span class="orm-status-short">⚠ Kurang</span>`
                    : `<span class="orm-status-ok">✓ OK</span>`;
                return `<tr>
                    <td>
                        <div class="orm-item-code">${esc(l.code)}</div>
                        ${l.name ? `<div class="orm-item-name">${esc(l.name)}</div>` : ''}
                    </td>
                    <td class="orm-qty">${l.qty_ordered ?? 1}</td>
                    <td class="orm-qty ${isShort ? 'short' : 'ok'}">${l.qty_fulfilled ?? 0}</td>
                    <td style="text-align:center">${statusHtml}</td>
                </tr>`;
            }).join('');
            pesananHtml = `
                <div class="orm-section-label">📋 Data Pesanan
                    <span class="orm-cnt">${lines.filter(l=>l.code).length} item</span>
                </div>
                <table class="orm-table">
                    <thead><tr>
                        <th>Item</th>
                        <th style="text-align:center">Dipesan</th>
                        <th style="text-align:center">Di-Pack</th>
                        <th style="text-align:center">Status</th>
                    </tr></thead>
                    <tbody>${rows}</tbody>
                </table>`;
        }

        // ITEM TERSCAN list
        let scanHtml = '';
        if (scanLog.length) {
            const scanItems = scanLog.map(s => `<div class="orm-scan-item">
                <span class="orm-scan-code">${esc(s.code)}</span>
                <span class="orm-scan-name">${esc(s.name || '')}</span>
                <span class="orm-scan-qty">×${s.qty}</span>
            </div>`).join('');
            scanHtml = `
                <div class="orm-section-label" style="color:#7c3aed">📦 Item Terscan
                    <span class="orm-cnt">${scanLog.length} item</span>
                </div>
                <div class="orm-scan-list">${scanItems}</div>`;
        } else {
            scanHtml = `
                <div class="orm-section-label" style="color:#7c3aed">📦 Item Terscan</div>
                <div style="font-size:.78rem;color:#94a3b8;font-style:italic;padding:.3rem 0">Belum ada item terscan.</div>`;
        }

        $('ormBody').innerHTML = pesananHtml + scanHtml;
    };

    window.closeReviewModal = function(e) {
        if (e && e.target !== $('ordReviewBg')) return;
        $('ordReviewBg').classList.remove('open');
        document.body.style.overflow = '';
    };

    // ── Re-render on resize ───────────────────────────────────────────────
    let _resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(_resizeTimer);
        _resizeTimer = setTimeout(() => renderTable(), 150);
    });
})();
</script>
@endpush
