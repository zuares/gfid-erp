@extends('layouts.app')
@section('title', 'Marketplace • Order Lokal')

@include('marketplace._shared')

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
        background: var(--card);
        border-radius: 8px;
        border: 1px solid var(--shp-border);
        box-shadow: none;
        overflow:hidden;
    }
    body[data-theme="dark"] .card-main{
        border-color: rgba(51,65,85,.85);
        box-shadow: none;
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
    .title{ font-weight: 750; font-size:1rem; letter-spacing: 0; margin:0; }
    
    .controls { display: flex; gap: .45rem; align-items: center; flex-wrap: wrap; }
    @media (min-width: 1024px) {
        .ship-topbar { flex-wrap: nowrap; overflow-x: auto; }
        .ship-topbar::-webkit-scrollbar { display: none; }
        .controls { flex-wrap: nowrap; }
    }
    
/* ── Force header one line ── */
.gf-master-desc { display: none !important; }

/* ── Header icon buttons ── */
.hdr-btn {
    display: inline-flex; align-items: center; gap: .35rem;
    border: 1px solid rgba(148,163,184,.35); border-radius: 7px;
    padding: .32rem .75rem; font-size: .78rem; font-weight: 600;
    background: transparent; color: #475569; cursor: pointer;
    transition: all .15s; white-space: nowrap; position: relative; box-shadow: none;
    flex-shrink: 0;
}
.hdr-btn:hover { background: rgba(148,163,184,.08); color: #111827; }
body[data-theme="dark"] .hdr-btn { color: #9ca3af; border-color: rgba(148,163,184,.25); }
body[data-theme="dark"] .hdr-btn:hover { background: rgba(148,163,184,.15); color: #fff; }
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

/* ── Search Bar in Topbar ── */
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
.ord-search-clear {
    background: none; border: none; font-size: .7rem; color: #94a3b8;
    cursor: pointer; padding: 0; display: none;
}
.ord-search-clear:hover { color: #ef4444; }
.ord-search-clear.visible { display: block; }

/* ── Tabs ── */
.ord-tabs {
    display: flex; gap: .4rem; flex-wrap: nowrap;
    overflow-x: auto; -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    margin-bottom: 1rem; border-bottom: 1px solid var(--shp-border);
    padding-bottom: .6rem;
}
.ord-tabs::-webkit-scrollbar { display: none; }
.ord-tab {
    display: flex; align-items: center; gap: .35rem;
    background: transparent; border: 1px solid transparent; padding: .38rem .75rem;
    font-size: .78rem; font-weight: 600; color: var(--shp-muted);
    border-radius: 7px; cursor: pointer; transition: all .15s;
    position: relative; white-space: nowrap; flex-shrink: 0;
}
.ord-tab:hover { background: rgba(148,163,184,.08); color: var(--shp-accent); }
.ord-tab.active { 
    color: #fff; background: var(--shp-accent); border-color: var(--shp-accent); 
}

/* ── Mobile cards ── */
.ord-cards { display: flex; flex-direction: column; gap: .5rem; }
.ord-card {
    background: var(--card); border: 1px solid var(--shp-border);
    border-radius: 8px; overflow: hidden;
    transition: border-color .15s;
}
body[data-theme="dark"] .ord-card { border-color: rgba(51,65,85,.85); }
.ord-card:hover { border-color: var(--shp-border-strong); }
.ord-card.row-urgent  { border-left: 3px solid #f59e0b; }
.ord-card.row-packing { border-left: 3px solid #2563eb; }
.ord-card-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: .5rem; padding: .7rem .9rem .55rem;
}
.ord-card-meta { min-width: 0; }
.ord-card-meta .ord-id { font-size: .78rem; font-weight:600; }
.ord-card-sub {
    display: flex; align-items: center; gap: .4rem; margin-top: .18rem; flex-wrap: wrap;
}
.ord-card-sub-text { font-size: .68rem; color: var(--shp-muted); font-weight: 500; }
.ord-card-actions { flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-end; gap: .3rem; }
.ord-card-section { border-top: 1px solid var(--shp-border); }
/* Accordion trigger */
.ord-acc-toggle {
    display: flex; align-items: center; justify-content: space-between;
    padding: .42rem .9rem; cursor: pointer; user-select: none;
    gap: .5rem;
}
.ord-acc-toggle:hover { background: rgba(148,163,184,.05); }
.ord-acc-label {
    font-size: .68rem; font-weight: 600; color: var(--shp-muted); letter-spacing: 0;
    text-transform: none; display: flex; align-items: center; gap: .35rem;
}
.ord-acc-count {
    font-size: .6rem; font-weight: 600; background: rgba(148,163,184,.15); color: var(--shp-muted);
    border-radius: 999px; padding: .05rem .38rem; line-height: 1.5;
}
.ord-acc-chevron {
    font-size: .6rem; color: var(--shp-border-strong); transition: transform .18s; flex-shrink: 0;
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
    font-size: .68rem; font-weight: 600; letter-spacing: 0;
    color: var(--shp-muted); text-transform: none; padding: .52rem .62rem;
    border-bottom: 1px solid var(--shp-border); background: var(--card,#fff); white-space: nowrap;
}
body[data-theme="dark"] .ord-table thead tr th { background: rgba(15,23,42,0.98); color: #9ca3af; }
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
.picking-print-strip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .6rem;
    margin: -.35rem 0 .85rem;
    padding: .55rem .7rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 9px;
    background: #fff;
}
.picking-print-strip-info {
    font-size: .74rem;
    color: #64748b;
    font-weight: 600;
}
.picking-print-strip-info strong { color: #0f172a; }
@media (max-width: 640px) {
    .picking-print-strip {
        align-items: stretch;
        flex-direction: column;
    }
    .picking-print-strip .btn-toolbar {
        justify-content: center;
        width: 100%;
        min-height: 38px;
    }
}
.btn-toolbar {
    display: inline-flex; align-items: center; gap: .35rem;
    background: transparent; border: 1px solid rgba(148,163,184,.35); border-radius: 7px;
    padding: .35rem .8rem; font-size: .78rem; font-weight: 600;
    color: #475569; cursor: pointer; transition: all .15s; white-space: nowrap; box-shadow: none;
}
.btn-toolbar:hover { background: rgba(148,163,184,.08); color: #111827; }
body[data-theme="dark"] .btn-toolbar { color: #9ca3af; border-color: rgba(148,163,184,.25); }
body[data-theme="dark"] .btn-toolbar:hover { background: rgba(148,163,184,.15); color: #fff; }

.btn-toolbar.primary { background: var(--shp-accent); color: #fff; border-color: var(--shp-accent); }
.btn-toolbar.primary:hover { background: var(--shp-accent-2); border-color: var(--shp-accent-2); }
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
<div class="page-wrap">
    {{-- ── TOPBAR ── --}}
    <div class="ship-topbar">
        <div>
            <h1 class="title">Order Lokal</h1>
            <div class="sub">Pantau pesanan Marketplace</div>
        </div>
        <div class="controls" style="flex:1; justify-content:flex-end">
            {{-- Hidden date inputs --}}
            <input type="hidden" id="mpDateFrom" value="{{ $filters['date_from'] }}">
            <input type="hidden" id="mpDateTo"   value="{{ $filters['date_to'] }}">

            {{-- Search bar --}}
            <div class="ord-search-bar">
                <span class="search-icon">🔍</span>
                <input type="text" id="filterSearch" placeholder="Cari order/resi/SKU…" oninput="onSearchInput(this)" autocomplete="off">
                <button class="ord-search-clear" id="searchClearBtn" onclick="clearSearch()">✕</button>
            </div>

            {{-- Store filter --}}
            <div style="position:relative">
                <button class="hdr-btn" id="btnStore" onclick="toggleDropdown('ddStore', event)">
                    🏪 <span id="btnStoreLabel" class="hdr-btn-label">Semua Toko</span>
                </button>
                <div class="hdr-dropdown" id="ddStore">
                    <div style="padding:.25rem .4rem .1rem;font-size:.65rem;font-weight:700;color:#94a3b8;letter-spacing:.04em">PILIH TOKO</div>
                    <div id="storeDropdownItems"></div>
                </div>
            </div>

            {{-- Date filter --}}
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

            {{-- Sync --}}
            <button class="hdr-btn" style="background:var(--shp-accent);color:#fff;border-color:var(--shp-accent)" onclick="openQuickSync()">🔄 Sync</button>
            <button class="hdr-btn" onclick="loadOrders()" title="Refresh">🔃</button>

            @if(auth()->user()?->role === 'owner')
            <button class="hdr-btn" id="btnDevPanel" style="background:#faf5ff;color:#7c3aed;border-color:#ddd6fe" onclick="toggleDevPanel()">🛠 Dev</button>
            @endif
        </div>
    </div>

    @if(auth()->user()?->role === 'owner')
    {{-- ══ DEV TOOLS PANEL ══════════════════════════════════════════════════ --}}
    <div id="devPanel" style="display:none;background:#faf5ff;border:1px solid #ddd6fe;border-radius:8px;padding:1rem 1.25rem;margin-bottom:1rem">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem">
            <div style="font-size:.8rem;font-weight:800;color:#7c3aed;letter-spacing:.05em">🛠 DEV TOOLS — OWNER ONLY</div>
            <div id="devStats" style="font-size:.73rem;color:#6b7280">—</div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center">
            <button id="btnSeedOrders" onclick="devSeedOrders()" style="display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .85rem;border-radius:999px;border:1.5px solid #bbf7d0;background:#f0fdf4;color:#16a34a;font-size:.75rem;font-weight:700;cursor:pointer">📥 Seed Orders</button>
            <button id="btnResetFulfillments" onclick="devResetFulfillments()" style="display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .85rem;border-radius:999px;border:1.5px solid #fde68a;background:#fefce8;color:#a16207;font-size:.75rem;font-weight:700;cursor:pointer" title="Hapus semua fulfillments, orders tetap ada">🔄 Reset Fulfillments</button>
            <button id="btnFreshOrders" onclick="devFreshOrders()" style="display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .85rem;border-radius:999px;border:1.5px solid #fecaca;background:#fef2f2;color:#dc2626;font-size:.75rem;font-weight:700;cursor:pointer" title="Hapus SEMUA orders + fulfillments">🗑 Fresh All</button>
            <button id="btnRemapItems" onclick="devRemapItems()" style="display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .85rem;border-radius:999px;border:1.5px solid #c4b5fd;background:#f5f3ff;color:#6d28d9;font-size:.75rem;font-weight:700;cursor:pointer" title="Re-resolve semua mapping_status + cost_status item berdasarkan SKU Mapping">🔁 Remap Items</button>
            <div style="width:1px;height:20px;background:#e2e8f0;margin:0 .15rem"></div>
            <a href="/sales/shipments" style="display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .85rem;border-radius:999px;border:1.5px solid #bfdbfe;background:#eff6ff;color:#2563eb;font-size:.75rem;font-weight:700;text-decoration:none">📋 Buka Shipment →</a>
        </div>
    </div>
    @endif

    {{-- Alert SOP Baru --}}
    @if(false)
    <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:.75rem 1rem; margin-bottom:1rem; display:flex; gap:.75rem; align-items:flex-start">
        <div style="font-size:1.2rem">📦</div>
        <div>
            <div style="font-size:.85rem; font-weight:800; color:#1e40af; margin-bottom:.15rem">SOP BARU PENGIRIMAN</div>
            <div style="font-size:.78rem; color:#1d4ed8; line-height:1.4">
                Proses packing dan potong stok kini dilakukan lewat menu <strong><a href="/sales/shipments" style="color:#1d4ed8; text-decoration:underline">Shipment</a></strong>. Gunakan modul Shipment untuk membuat Draft (Batch) pengiriman.
            </div>
        </div>
    </div>
    @endif

    {{-- TABS (Replacement for redundant KPI cards) --}}
    <div class="ord-tabs" id="ordTabs" style="margin-bottom:1rem; border-bottom:1px solid var(--shp-border);">
        <button class="ord-tab active" data-tab="all" onclick="switchTab('all',this)">
            Semua <span class="ord-badge" id="badge-all">—</span>
        </button>
        <button class="ord-tab" data-tab="unpaid" onclick="switchTab('unpaid',this)">
            Belum Bayar <span class="ord-badge" id="badge-unpaid">—</span>
        </button>
        <button class="ord-tab" data-tab="ready" onclick="switchTab('ready',this)">
            Perlu Dikirim <span class="ord-badge urgent" id="badge-ready">—</span>
        </button>
        <button class="ord-tab" data-tab="processed" onclick="switchTab('processed',this)">
            Sedang Dikemas <span class="ord-badge" id="badge-processed" style="background:#eff6ff;color:#2563eb;border-color:#bfdbfe">—</span>
        </button>
        <button class="ord-tab" data-tab="ready_to_handover" onclick="switchTab('ready_to_handover',this)">
            Siap Kirim <span class="ord-badge" id="badge-ready_to_handover" style="background:#e0e7ff;color:#4f46e5;border-color:#c7d2fe">—</span>
        </button>
        <button class="ord-tab" data-tab="shipped" onclick="switchTab('shipped',this)">
            Sedang Dikirim <span class="ord-badge" id="badge-shipped">—</span>
        </button>
        <button class="ord-tab" data-tab="completed" onclick="switchTab('completed',this)">
            Selesai <span class="ord-badge" id="badge-completed">—</span>
        </button>
        <button class="ord-tab" data-tab="cancelled" onclick="switchTab('cancelled',this)">
            Batal/Retur <span class="ord-badge" id="badge-cancelled">—</span>
        </button>
        <button class="ord-tab" data-tab="issues" onclick="switchTab('issues',this)">
            ⚠️ Bermasalah <span class="ord-badge" id="badge-issues" style="background:#fef2f2;color:#dc2626;border-color:#fecaca">—</span>
        </button>
    </div>

    {{-- Toolbar: tab Perlu Diproses (Now more subtle) --}}
    <div class="process-toolbar" id="processToolbar" style="margin-bottom:1rem; border:1px solid var(--shp-border); background:var(--card); border-radius:8px;">
        <div class="process-toolbar-info" id="toolbarInfo" style="font-size:.8rem; padding:.5rem 1rem">
            <strong id="toolbarCount">0</strong> order perlu diproses
        </div>
        <div class="process-toolbar-actions" id="toolbarActionsProcess">
            <button class="btn-toolbar" id="btnBelumProses" onclick="toggleBelumProses()">🔴 Filter Belum Proses</button>
            <button class="btn-toolbar primary" id="btnBulkArrangeShipment" onclick="openBulkArrangeShipment()" style="background:#2563eb;border-color:#2563eb;">🚚 Atur Pengiriman Semua</button>
            <button class="btn-toolbar primary" id="btnBulkPrint" onclick="printPickingList()" style="background:#0f172a;border-color:#0f172a;">🖨️ Cetak Picking List</button>
            <button class="btn-toolbar primary" id="btnBulkPrintDocuments" onclick="printAllDocuments()" style="background:#0891b2;border-color:#0891b2;display:none;">🖨️ Cetak Semua Resi</button>
            <button class="btn-toolbar primary" id="btnBulkPrintGreetings" onclick="printAllGreetings()" style="background:#8b5cf6;border-color:#8b5cf6;display:none;">💌 Cetak Kartu Ucapan</button>
            <button class="btn-toolbar primary" id="btnBulkFulfill" onclick="window.location='/sales/shipments'">📦 Buka Shipment</button>
        </div>
        <div class="process-toolbar-actions" id="toolbarActionsUnresolved" style="display:none">
            <a href="/marketplace/issues" class="btn-toolbar primary">🔗 Perbaiki di Issues →</a>
        </div>
    </div>

    <div class="card-main">
        <div id="ordersBody">
            <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
        </div>
    </div> <!-- end .card-main -->
</div> <!-- end .page-wrap -->
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
{{-- Arrange Shipment Modal --}}
<div class="modal fade" id="arrangeShipmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:450px">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-black" style="font-size:1rem">🚚 Atur Pengiriman</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <div id="asLoading" style="text-align:center;padding:1.5rem;color:#64748b;font-size:0.85rem;">
                    ⏳ Sedang memeriksa opsi pengiriman dari Marketplace...
                </div>
                <div id="asContent" style="display:none;">
                    <div class="alert alert-info py-2" style="font-size:0.75rem" id="asAlert">
                        Silakan pilih metode pengiriman untuk order ini.
                    </div>
                    
                    <div id="asOptions" class="d-flex flex-column gap-2 mb-3">
                        <!-- Options will be injected here -->
                    </div>
                    
                    <input type="hidden" id="asStoreId">
                    <input type="hidden" id="asOrderSn">

                    <div class="form-check mb-3" style="margin-top: 10px;">
                        <input class="form-check-input" type="checkbox" id="asPrintDocument" checked>
                        <label class="form-check-label" for="asPrintDocument" style="font-size:0.8rem; font-weight:600;">
                            Langsung cetak resi setelah sukses
                        </label>
                    </div>
                    
                    <button class="btn btn-primary w-100 fw-bold rounded-pill" id="asSubmitBtn" onclick="submitArrangeShipment()">Konfirmasi Pengiriman</button>
                </div>
            </div>
        </div>
    </div>
</div>
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
                <div class="mb-3">
                    <div class="form-check form-switch" title="Jalankan sync tanpa menyimpan ke database">
                        <input class="form-check-input" type="checkbox" role="switch" id="qsSyncDryRun" style="cursor:pointer">
                        <label class="form-check-label fw-bold" for="qsSyncDryRun" style="font-size:.75rem;color:#64748b;cursor:pointer">Mode Dry Run</label>
                    </div>
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

{{-- Bulk Arrange Shipment Modal --}}
<div class="modal fade" id="bulkArrangeShipmentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:460px">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-black" style="font-size:1rem">🚚 Atur Semua Pengiriman</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <div id="basConfirmView">
                    <p style="font-size:.8rem;color:#64748b;margin-bottom:1rem" id="basSummaryText">
                        Menghitung order yang siap diproses...
                    </p>
                    
                    <div class="mb-3">
                        <label style="font-size:0.75rem;font-weight:700;color:#334155;margin-bottom:0.5rem;display:block">Metode Pengiriman Default</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="basMethod" id="basDropoff" value="dropoff" checked>
                            <label class="form-check-label" for="basDropoff"><strong>Drop-off</strong> (Antar ke Cabang)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="basMethod" id="basPickup" value="pickup">
                            <label class="form-check-label" for="basPickup"><strong>Pickup</strong> (Kurir Jemput)</label>
                        </div>
                    </div>
                    
                    <button class="btn btn-primary w-100 fw-bold rounded-pill" id="basStartBtn" onclick="startBulkArrangeShipment()">Mulai Proses</button>
                </div>
                
                <div id="basProgressView" style="display:none">
                    <p style="font-size:.8rem;color:#334155;font-weight:600;margin-bottom:.5rem" id="basProgressText">Memproses 0 dari 0 order...</p>
                    <div class="progress" style="height:10px;border-radius:5px;background:#e2e8f0;margin-bottom:1rem">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="basProgressBar" style="width:0%"></div>
                    </div>
                    <div id="basLog" style="height:120px;overflow-y:auto;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:.5rem;font-size:.7rem;font-family:monospace;color:#475569">
                        <!-- log lines -->
                    </div>
                </div>

                <div id="basDoneView" style="display:none;text-align:center;padding:1rem 0">
                    <div style="font-size:3rem;margin-bottom:.5rem">✅</div>
                    <h5 class="fw-black text-success">Selesai!</h5>
                    <p style="font-size:.8rem;color:#64748b" id="basResultText">Berhasil mengatur pengiriman.</p>
                    <button class="btn btn-light w-100 fw-bold rounded-pill mt-3" data-bs-dismiss="modal" onclick="loadOrders()">Tutup & Refresh</button>
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

    const { api, fmt, fmtDate, fmtRp, esc, channelPill, statusBadge } = window.mpHelpers;
    let orders           = [];
    let activeTab        = sessionStorage.getItem('ord_active_tab') || 'ready';
    let activeStore      = '';
    let fulfilledOrderIds    = new Set();   // order ID yang sudah punya fulfillment confirmed
    let printedOrderIds      = new Set();   // order ID yang sudah dicetak picking list
    let printedDocOrderSns   = new Set();   // channel_order_id yang sudah dicetak resi
    let currentFulfillment   = null;        // fulfillment aktif di modal
    let fulfillmentStatusMap = new Map();   // order_id → {id, status} — pre-loaded dari API
    let filterBelumProses    = false;       // toggle filter hanya order belum fulfilled

    const $ = id => document.getElementById(id);
    const getFrom   = () => $('mpDateFrom').value;
    const getTo     = () => $('mpDateTo').value;
    const getSearch = () => ($('filterSearch').value || '').toLowerCase().trim();

    // Status order yang dianggap "aktif" (perlu proses / sedang packing)
    const ACTIVE_ORDER_STATUSES = ['READY_TO_SHIP', 'PROCESSED'];

    const TAB_STATUSES = {
        all:        null,
        unpaid:     ['UNPAID'],
        ready:      ['READY_TO_SHIP'],
        processed:  ['PROCESSED'],
        ready_to_handover: ['READY_TO_HANDOVER'],
        shipped:    ['SHIPPED', 'TO_CONFIRM_RECEIVE'],
        completed:  ['COMPLETED'],
        cancelled:  ['CANCELLED', 'IN_CANCEL'],
        issues:     null, // via TAB_FILTERS
    };

    // Semua filter berbasis fungsi
    const TAB_FILTERS = {
        issues:     o => o.has_data_issues === true,
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
        updateToolbar();
        updatePickingPrintStrip();
        
        if (tab === 'processed') {
            autoFetchMissingAwbs();
        }
    };

    async function autoFetchMissingAwbs() {
        const rows = orders.filter(o => (o.order_status === 'PROCESSED' || o.order_status === 'SHIPPED') && !o.shipping_awb_no);
        if (rows.length === 0) return;
        
        let updatedCount = 0;
        for (const o of rows) {
            try {
                const res = await fetch(`/api/marketplace/stores/${o.store_id}/orders/${o.channel_order_id}/sync-awb`);
                const data = await res.json();
                if (data.success && data.awb) {
                    o.shipping_awb_no = data.awb;
                    updatedCount++;
                }
            } catch(e) {}
        }
        
        if (updatedCount > 0 && activeTab === 'processed') {
            renderTable();
        }
    }

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

        if (window.autoArrangeAfterSync) {
            window.autoArrangeAfterSync = false;
            setTimeout(() => {
                pendingShipOrders = orders.filter(o => o.order_status === 'READY_TO_SHIP');
                if (pendingShipOrders.length > 0) {
                    const modal = new bootstrap.Modal($('bulkArrangeShipmentModal'));
                    modal.show();
                    $('basConfirmView').style.display  = 'none';
                    $('basProgressView').style.display = 'block';
                    $('basDoneView').style.display     = 'none';
                    startBulkArrangeShipment();
                }
            }, 500);
        }
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

    window.render = function () { renderKpi(); renderBadges(); renderTable(); updateToolbar(); updatePickingPrintStrip(); };

    // ── Process Toolbar ───────────────────────────────────────────────────
    function getProcessRows() {
        return filterByTab(applyFilters(orders.filter(inRange)), 'ready');
    }

    function getPackingRows() {
        return filterByTab(applyFilters(orders.filter(inRange)), 'processed');
    }

    function getPrintablePickingRows() {
        const filtered = applyFilters(orders.filter(inRange));
        let rows = [];
        if (activeTab === 'ready') {
            rows = filterByTab(filtered, 'ready');
        } else if (activeTab === 'processed') {
            rows = filterByTab(filtered, 'processed');
        } else {
            rows = [...filterByTab(filtered, 'ready'), ...filterByTab(filtered, 'processed')];
        }
        const unique = new Map();
        rows.forEach(o => unique.set(o.id, o));
        return Array.from(unique.values());
    }

    function updatePickingPrintStrip() {
        const rows = getPrintablePickingRows();
        const readyCount = filterByTab(rows, 'ready').length;
        const processedCount = filterByTab(rows, 'processed').length;
        const info = $('pickingPrintInfo');
        const btn = $('btnPrintPickingTop');

        if (info) {
            info.innerHTML = `<strong>${rows.length}</strong> order siap dicetak <span style="color:#94a3b8">(${readyCount} perlu dikirim · ${processedCount} sedang dikemas)</span>`;
        }
        if (btn) {
            btn.disabled = rows.length === 0;
            btn.style.opacity = rows.length === 0 ? '.45' : '1';
            btn.style.pointerEvents = rows.length === 0 ? 'none' : '';
        }
    }

    function updateToolbar() {
        const toolbar = $('processToolbar');
        if (!['ready', 'processed', 'issues'].includes(activeTab)) { toolbar.classList.remove('visible'); return; }

        const isIssues = activeTab === 'issues';
        $('toolbarActionsProcess').style.display  = isIssues ? 'none' : '';
        $('toolbarActionsUnresolved').style.display = isIssues ? '' : 'none';
        const btnBelumProses = $('btnBelumProses');
        const btnBulkFulfill = $('btnBulkFulfill');
        const btnBulkArrangeShipment = $('btnBulkArrangeShipment');
        const btnBulkPrint = $('btnBulkPrint');
        const btnBulkPrintDocuments = $('btnBulkPrintDocuments');
        const btnBulkPrintGreetings = $('btnBulkPrintGreetings');

        if (isIssues) {
            const rows = filterByTab(applyFilters(orders.filter(inRange)), 'issues');
            toolbar.classList.toggle('visible', rows.length > 0);
            $('toolbarInfo').innerHTML = `<strong>${rows.length}</strong> order perlu diperbaiki`;
            if (btnBelumProses) btnBelumProses.style.display = '';
            if (btnBulkFulfill) btnBulkFulfill.style.display = '';
            if (btnBulkArrangeShipment) btnBulkArrangeShipment.style.display = 'none';
            if (btnBulkPrint) btnBulkPrint.style.display = 'none';
            if (btnBulkPrintDocuments) btnBulkPrintDocuments.style.display = 'none';
            if (btnBulkPrintGreetings) btnBulkPrintGreetings.style.display = 'none';
        } else if (activeTab === 'processed') {
            const rows = getPackingRows();
            toolbar.classList.toggle('visible', rows.length > 0);
            $('toolbarInfo').innerHTML = `<strong>${rows.length}</strong> order sedang dikemas`;
            if (btnBelumProses) btnBelumProses.style.display = 'none';
            if (btnBulkFulfill) btnBulkFulfill.style.display = '';
            if (btnBulkArrangeShipment) btnBulkArrangeShipment.style.display = 'none';
            if (btnBulkPrint) btnBulkPrint.style.display = '';
            if (btnBulkPrintDocuments) btnBulkPrintDocuments.style.display = '';
            if (btnBulkPrintGreetings) btnBulkPrintGreetings.style.display = '';
        } else {
            const rows = getProcessRows();
            toolbar.classList.toggle('visible', rows.length > 0);
            $('toolbarInfo').innerHTML = `<strong id="toolbarCount">${rows.length}</strong> order perlu diproses hari ini`;
            if (btnBelumProses) btnBelumProses.style.display = 'none';
            if (btnBulkFulfill) btnBulkFulfill.style.display = 'none';
            if (btnBulkArrangeShipment) btnBulkArrangeShipment.style.display = '';
            if (btnBulkPrint) btnBulkPrint.style.display = '';
            if (btnBulkPrintDocuments) btnBulkPrintDocuments.style.display = 'none';
            if (btnBulkPrintGreetings) btnBulkPrintGreetings.style.display = 'none';
        }
    }

    // ── Print Picking List ────────────────────────────────────────────────
    window.printPickingList = function () {
        // Cetak gabungan Perlu Proses + Sedang Proses sesuai filter toko/tanggal/search aktif.
        const rows = getPrintablePickingRows();
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
                const mSku    = i.model_sku || i.item_sku || '';
                const category = code
                    ? (i.internal_item?.category?.name || i.internal_item?.item_category?.name || i.internal_item?.category_name || 'Tanpa Kategori')
                    : 'Belum Mapping';
                const categoryOrder = code ? (category === 'Tanpa Kategori' ? 2 : 1) : 3;
                const key     = category + '||' + (code || mSku);
                if (!itemMap[key]) itemMap[key] = { code, mSku, category, categoryOrder, qty: 0, mapped: !!code };
                itemMap[key].qty += (i.qty || 1);
            });
        });

        // Urutkan per kategori: kategori normal, Tanpa Kategori, Belum Mapping.
        const sortedItems = Object.values(itemMap).sort((a, b) => {
            if (a.categoryOrder !== b.categoryOrder) return a.categoryOrder - b.categoryOrder;
            if (a.category !== b.category) return a.category.localeCompare(b.category, 'id');
            return (a.code || a.mSku || '').localeCompare((b.code || b.mSku || ''), 'id');
        });

        let currentCategory = null;
        const itemRows = sortedItems.map(it => {
            const groupRow = currentCategory !== it.category
                ? `<tr class="category-row"><td colspan="4">${esc(it.category)}</td></tr>`
                : '';
            currentCategory = it.category;

            const label = it.mapped
                ? `<strong class="sku-code">${it.code}</strong>`
                : `<span class="unmapped-text">${it.mSku || '—'} <em>(belum mapping)</em></span>`;
            return `${groupRow}<tr>
                <td class="chk"><input type="checkbox"></td>
                <td>${label}</td>
                <td class="qty">${it.qty}</td>
                <td class="picked-qty"></td>
            </tr>`;
        }).join('');

        const totalQty    = sortedItems.reduce((s, i) => s + i.qty, 0);

        const html = `<!DOCTYPE html><html><head>
            <meta charset="UTF-8">
            <title>Picking List — ${today}</title>
            <style>
                *, *::before, *::after {
                    box-sizing: border-box;
                    color: #000 !important;
                    border-color: #000 !important;
                    box-shadow: none !important;
                    text-shadow: none !important;
                    filter: none !important;
                    opacity: 1 !important;
                }
                @page { size: 100mm 150mm; margin: 3.5mm; }
                html, body {
                    margin: 0;
                    padding: 0;
                    background: #fff !important;
                    color: #000 !important;
                    font-family: Arial, Helvetica, sans-serif;
                    font-size: 6.5pt;
                    line-height: 1.05;
                    -webkit-print-color-adjust: economy;
                    print-color-adjust: economy;
                    color-scheme: light only;
                }
                /* Toolbar (hanya tampil di layar, tidak dicetak) */
                #toolbar {
                    position: fixed; top: 0; left: 0; right: 0; z-index: 99;
                    background: #0f172a !important; color: #fff !important; padding: .75rem 1rem;
                    display: flex; align-items: center; justify-content: space-between;
                    gap: 1rem;
                }
                #toolbar * { color: #fff !important; }
                #toolbar button {
                    background: #000 !important; color: #fff !important; border: 1px solid #fff; border-radius: 8px;
                    padding: .75rem 1.5rem; font-weight: 900; font-size: 1rem; cursor: pointer;
                    min-width: 132px;
                }
                #toolbar button:hover { background: #111 !important; }
                #content { padding-top: 58px; }
                @media print { #toolbar { display: none; } #content { padding-top: 0; } }
                /* Header */
                .page-header {
                    display: flex; justify-content: space-between; align-items: flex-end;
                    border-bottom: .3mm solid #000; padding-bottom: .8mm; margin-bottom: 1.1mm;
                }
                .header-left { display: flex; align-items: center; gap: 1.5mm; min-width: 0; }
                .print-logo {
                    width: 7mm;
                    height: 7mm;
                    object-fit: contain;
                    flex: 0 0 auto;
                    display: block;
                    filter: grayscale(1) contrast(1.4) !important;
                }
                .page-title { font-size: 6.5pt; font-weight: 900; letter-spacing: 0; }
                .page-date { font-size: 6pt; color: #000 !important; font-weight: 800; margin-top: .2mm; }
                .page-meta  { font-size: 6.5pt; color: #000 !important; text-align: right; font-weight: 900; }
                /* Ringkasan */
                .section-title {
                    font-size: 6.5pt; font-weight: 900; text-transform: uppercase;
                    letter-spacing: .02em; color: #000 !important; margin: 1mm 0 .7mm;
                    border-bottom: .25mm solid #000; padding-bottom: .5mm;
                }
                table { width: 100%; border-collapse: collapse; }
                thead { display: table-row-group; }
                table td, table th { padding: .62mm .8mm; border: .24mm solid #000; vertical-align: middle; }
                table th { font-size: 6.5pt; color: #000 !important; text-transform: uppercase; font-weight: 900; }
                .category-row td {
                    padding: .45mm .8mm;
                    font-size: 6pt;
                    font-weight: 900;
                    text-transform: uppercase;
                    letter-spacing: .03em;
                    color: #fff !important;
                    background: #000 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .chk  { width: 5.5mm; text-align: center; }
                .chk input { width: 2.8mm; height: 2.8mm; accent-color: #000; }
                .qty  { width: 9mm; text-align: center; font-weight: 900 !important; font-size: 6.5pt; }
                .picked-qty { width: 14mm; text-align: center; font-weight: 900 !important; font-size: 6.5pt; }
                .sku-code,
                .unmapped-text {
                    font-family: Arial, Helvetica, sans-serif;
                    font-size: 6.5pt;
                    font-weight: 900 !important;
                    color: #000 !important;
                    line-height: 1;
                }
                .variant-text {
                    display: inline;
                    margin-top: 0;
                    font-size: 6pt;
                    font-weight: 900;
                    color: #000 !important;
                }
                .unmapped-text em { color: #000 !important; font-style: normal; font-weight: 900; }
                .empty-text { color: #000 !important; font-style: normal; font-weight: 900; font-size: 6.5pt; }
                .footer {
                    display: flex; justify-content: space-between; font-weight: 900;
                    font-size: 6.5pt; border-top: .3mm solid #000; padding-top: .7mm; margin-top: 1mm;
                    color: #000 !important;
                }
                @media screen {
                    body {
                        width: 100mm;
                        min-height: 150mm;
                        margin: 0 auto;
                        padding: 0;
                        overflow-x: hidden;
                        background: #fff !important;
                    }
                    #content {
                        width: 100mm;
                        min-height: 150mm;
                        margin: 0;
                        padding-left: 3.5mm;
                        padding-right: 3.5mm;
                        padding-bottom: 3.5mm;
                    }
                }
                @media print {
                    *, *::before, *::after {
                        color: #000 !important;
                        border-color: #000 !important;
                        box-shadow: none !important;
                        text-shadow: none !important;
                        filter: none !important;
                        opacity: 1 !important;
                    }
                    html, body, #content { width: 93mm; background: #fff !important; }
                    thead { display: table-row-group !important; }
                    .qty,
                    .sku-code {
                        font-weight: 900 !important;
                    }
                    .category-row td {
                        color: #fff !important;
                        background: #000 !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
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
                    <div class="header-left">
                        <img class="print-logo" src="/images/logo-mark.svg" alt="GF">
                        <div>
                            <div class="page-title">PICKING LIST</div>
                            <div class="page-date">${today} · ${timeNow}</div>
                        </div>
                    </div>
                    <div class="page-meta">
                        <div><strong>${rows.length}</strong> order</div>
                        <div><strong>${totalQty}</strong> total item</div>
                    </div>
                </div>

                <div class="section-title">Daftar Barang Diambil</div>
                <table>
                    <thead><tr>
                        <th class="chk"></th>
                        <th style="text-align:left">Kode Item</th>
                        <th class="qty">Qty</th>
                        <th class="picked-qty">Diambil</th>
                    </tr></thead>
                    <tbody>${itemRows}</tbody>
                </table>

                <div class="footer">
                    <span>TOTAL ${rows.length} PESANAN</span>
                    <span>${totalQty} ITEM</span>
                </div>
            </div>
        </body></html>`;

        const win = window.open('', '_blank', 'width=430,height=680');
        if (!win) { alert('Popup diblokir. Izinkan popup untuk halaman ini.'); return; }
        win.document.write(html);
        win.document.close();
        win.focus();
        // Tidak auto-print — user klik tombol Print di toolbar
    };

    // ── Bulk Fulfillment ──────────────────────────────────────────────────
    // ── Bulk Arrange Shipment ─────────────────────────────────────────────
    let pendingShipOrders = [];

    window.openBulkArrangeShipment = function () {
        // Hanya proses order yang tampil di layar saat ini (terfilter by Toko/Search)
        pendingShipOrders = getProcessRows();
        
        if (!pendingShipOrders.length) {
            alert('Tidak ada order yang berstatus Belum Diproses (READY_TO_SHIP) di tampilan saat ini.');
            return;
        }

        $('basConfirmView').style.display  = 'block';
        $('basProgressView').style.display = 'none';
        $('basDoneView').style.display     = 'none';
        $('basStartBtn').disabled = false;
        $('basStartBtn').textContent = '📦 Proses Sekarang';

        $('basSummaryText').innerHTML = `<strong>${pendingShipOrders.length} order</strong> siap diatur pengirimannya ke pihak Marketplace.`;

        new bootstrap.Modal($('bulkArrangeShipmentModal')).show();
    };

    window.startBulkArrangeShipment = async function () {
        if (!pendingShipOrders.length) return;

        $('basConfirmView').style.display  = 'none';
        $('basProgressView').style.display = 'block';
        
        const methodEl = document.querySelector('input[name="basMethod"]:checked');
        const method = methodEl ? methodEl.value : 'dropoff';
        
        const logEl = $('basLog');
        logEl.innerHTML = '';
        
        const logMsg = (msg, color = '#475569') => {
            logEl.innerHTML += `<div style="color:${color};margin-bottom:2px">${msg}</div>`;
            logEl.scrollTop = logEl.scrollHeight;
        };

        let successCount = 0;
        let failCount = 0;
        
        for (let i = 0; i < pendingShipOrders.length; i++) {
            const o = pendingShipOrders[i];
            const pct = Math.round((i / pendingShipOrders.length) * 100);
            
            $('basProgressText').textContent = `Memproses ${i + 1} dari ${pendingShipOrders.length} order...`;
            $('basProgressBar').style.width = pct + '%';
            
            logMsg(`[${o.channel_order_id}] Memulai...`);
            
            try {
                let params = {};
                if (method === 'dropoff') params = { dropoff: {} };
                else if (method === 'pickup') params = { pickup: {} };
                
                await api(`/api/marketplace/stores/${o.store_id}/orders/${o.channel_order_id}/ship`, {
                    method: 'POST',
                    body: JSON.stringify(params)
                });
                
                logMsg(`[${o.channel_order_id}] Berhasil!`, '#16a34a');
                successCount++;
            } catch (e) {
                logMsg(`[${o.channel_order_id}] Gagal: ${e.message}`, '#dc2626');
                failCount++;
            }
        }
        
        $('basProgressBar').style.width = '100%';
        $('basProgressView').style.display = 'none';
        $('basDoneView').style.display = 'block';
        
        let resHtml = `Selesai memproses ${pendingShipOrders.length} order.<br>`;
        if (successCount > 0) resHtml += `<span style="color:#16a34a;font-weight:bold">✅ ${successCount} berhasil.</span><br>`;
        if (failCount > 0) resHtml += `<span style="color:#dc2626;font-weight:bold">❌ ${failCount} gagal.</span>`;
        $('basResultText').innerHTML = resHtml;
    };

    // ── KPI ───────────────────────────────────────────────────────────────
    function renderKpi() {
        const rows = applyFilters(orders.filter(inRange));
        // KPIs removed in favor of clean tabs
    }

    // ── Badges ────────────────────────────────────────────────────────────
    function renderBadges() {
        const rows = applyFilters(orders.filter(inRange));
        ['all', 'unpaid', 'ready', 'processed', 'ready_to_handover', 'shipped', 'completed', 'cancelled', 'issues'].forEach(tab => {
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
            const dispName = variantName || i.internal_item?.name || internalCode;
            bodyHtml = `<div class="ord-item-name">${esc(dispName)}</div>`
                + (mSku && mSku !== internalCode ? `<div class="ord-item-sku">${esc(mSku)}</div>` : '');
        } else if (mSku) {
            const dispName = variantName || i.item_name || mSku;
            bodyHtml = `<div class="ord-item-name" style="color:#64748b">${esc(dispName)}</div>`
                + `<span class="ord-item-nomap">Belum mapping</span>`;
        } else {
            const dispName = variantName || i.item_name || 'Item tidak diketahui';
            bodyHtml = `<div class="ord-item-name">${esc(dispName)}</div>`
                + `<span class="ord-item-nomap">Belum mapping</span>`;
        }

        const qtyClass = urgent ? 'ord-item-qty urgent' : 'ord-item-qty';
        return `<div class="ord-item-card">
            <div class="${qtyClass}">${i.qty || 1}×</div>
            <div class="ord-item-body">${bodyHtml}</div>
        </div>`;
    }

    // ── Table ─────────────────────────────────────────────────────────────
    function filterByTab(rows, tab) {
        if (tab === 'issues') return rows.filter(TAB_FILTERS.issues);

        const isPacked = o => fulfilledOrderIds.has(o.id);

        if (tab === 'shipped') {
            return rows.filter(o => ['SHIPPED', 'TO_CONFIRM_RECEIVE'].includes(o.order_status));
        }

        if (tab === 'ready_to_handover') {
            return rows.filter(o => 
                o.order_status === 'READY_TO_HANDOVER' 
                || (['READY_TO_SHIP', 'PROCESSED'].includes(o.order_status) && isPacked(o))
            );
        }

        if (['ready', 'processed'].includes(tab)) {
            const statuses = TAB_STATUSES[tab];
            return rows.filter(o => statuses.includes(o.order_status) && !isPacked(o));
        }

        const statuses = TAB_STATUSES[tab];
        if (statuses) return rows.filter(o => statuses.includes(o.order_status));
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
        const isPacking = activeTab === 'processed';

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
                        title="Buat atau lanjutkan Draft Shipment Marketplace, lalu scan resi/order ini."
                        onclick="window.location='/sales/shipments'">Lanjut ke Shipment →</button>`;
                } else {
                    fulfillBtn = `<button class="btn-fulfillment"
                        title="Buat atau lanjutkan Draft Shipment Marketplace, lalu scan resi/order ini."
                        onclick="window.location='/sales/shipments'">📦 Ke Shipment</button>`;
                }
            }

            // Sub-info
            const storeName   = o.store?.name || '';
            const channelName = o.store?.channel?.name || '';
            const storeText   = [storeName, channelName].filter(Boolean).join(' · ');
            const dateText    = o.ordered_at ? fmtDate(o.ordered_at) : '';
            const carrier     = (o.shipping_carrier || '').toLowerCase();
            const isKilat     = carrier.includes('instant') || carrier.includes('same day') || carrier.includes('sameday');
            const kilatBadge  = isKilat ? `<span style="font-size:.65rem;background:#fef08a;color:#854d0e;border-radius:4px;padding:1px 5px;font-weight:800;border:1px solid #fde047;margin-right:4px;">⚡ KILAT</span>` : '';

            // ── Section: Item Produk (accordion)
            const itemCards = items.map(i => renderItemCard(i, urgent)).join('');
            const itemsSection = makeAccordion('Item Produk', '', items.length, itemCards);

            // ── Section: Item Resolve (hanya packing, accordion)
            let resolveSection = '';
            if (isPacking) {
                const resolveLines = (o.fulfillment_resolve_lines || []).filter(l => l.code);
                if (resolveLines.length) {
                    const resolveCards = resolveLines.map(l => `<div class="ord-item-card" style="border-color:#bfdbfe;background:#eff6ff">
                        <div class="ord-item-qty" style="background:#dbeafe;color:#1d4ed8">${l.qty_ordered}×</div>
                        <div class="ord-item-body">
                            <div class="ord-item-name" style="color:#1e3a5f">${esc(l.code)}</div>
                            ${l.name ? `<div class="ord-item-variant">${esc(l.name)}</div>` : ''}
                        </div>
                    </div>`).join('');
                    resolveSection = makeAccordion('✅ Item Resolve', 'color:#1d4ed8', resolveLines.length, resolveCards);
                }
            }

            // ── Section: Item Scan (accordion)
            let scanSection = '';
            if (activeTab === 'processed' || activeTab === 'shipped') {
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
                            ${kilatBadge}
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

    function renderProcessCardList(rows, tabName = 'ready') {
        const isProcessed = tabName === 'processed' || tabName === 'ready_to_handover';
        const pkRows = rows.map(o => {
            const items       = o.items || [];
            const isFulfilled = fulfilledOrderIds.has(o.id);
            const isPrinted   = printedOrderIds.has(o.id);
            const isInPacking = fulfillmentStatusMap.has(o.id) && !isFulfilled;
            const fStatus     = fulfillmentStatusMap.get(o.id)?.status || '';
            const store       = [o.store?.name, o.store?.channel?.name].filter(Boolean).join(' · ');
            const carrier     = (o.shipping_carrier || '').toLowerCase();
            const isKilat     = carrier.includes('instant') || carrier.includes('same day') || carrier.includes('sameday');
            const kilatBadge  = isKilat ? `<span style="font-size:.65rem;background:#fef08a;color:#854d0e;border-radius:4px;padding:1px 5px;font-weight:800;border:1px solid #fde047;margin-right:4px;">⚡ KILAT</span>` : '';

            // Badge fulfillment
            const fBadgeHtml = isFulfilled
                ? `<span class="pk-badge-ok">✓ Selesai</span>`
                : isInPacking
                    ? (fStatus === 'pending_review'
                        ? `<span class="pk-badge-short">⏳ Siap Konfirmasi</span>`
                        : `<span style="display:inline-flex;align-items:center;gap:.2rem;font-size:.65rem;font-weight:700;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:999px;padding:.08rem .45rem">🔄 Dalam Proses</span>`)
                    : `<span style="display:inline-flex;align-items:center;gap:.2rem;font-size:.65rem;font-weight:700;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:999px;padding:.08rem .45rem">● Belum Proses</span>`;

            // Item chips (compact) for 'ready' tab
            let itemChips = '';
            let moreChip = '';
            if (!isProcessed) {
                itemChips = items.slice(0, 5).map(i => {
                    const code = i.internal_item?.code || i.model_sku || i.item_sku || '?';
                    const disp = i.variant_name || i.internal_item?.name || i.item_name || code;
                    const qty  = i.qty || 1;
                    const mapped = !!i.internal_item?.code;
                    return `<span style="display:inline-flex;align-items:center;gap:.2rem;font-size:.65rem;font-weight:700;
                        background:${mapped ? '#f1f5f9' : '#fef3c7'};color:${mapped ? '#334155' : '#92400e'};
                        border-radius:6px;padding:.1rem .38rem;font-family:'SF Mono','Menlo',monospace;white-space:nowrap">
                        ${qty}× ${esc(disp)}
                    </span>`;
                }).join('');
                moreChip = items.length > 5
                    ? `<span style="font-size:.63rem;color:#94a3b8;font-weight:600">+${items.length - 5} lagi</span>`
                    : '';
            }

            // Table format for 'processed' and 'ready_to_handover' tabs (menyerupai Rekon)
            let itemsSection = '';
            
            if (isProcessed) {
                let linesHtml = `<div style="overflow-x:auto; margin-top:.75rem; border:1px solid #e2e8f0; border-radius:8px;">
                    <table style="width:100%; border-collapse:collapse; min-width:600px;">
                    <thead style="background:#f8fafc; font-size:.65rem; color:#64748b; text-align:left; border-bottom:1px solid #e2e8f0;"><tr>
                        <th style="padding:.5rem .8rem">BARANG</th>
                        <th style="padding:.5rem .8rem; text-align:right">DIPESAN</th>
                        <th style="padding:.5rem .8rem; text-align:right">TERSEDIA</th>
                        <th style="padding:.5rem .8rem; text-align:right">KURANG</th>
                        <th style="padding:.5rem .8rem; text-align:center">STATUS</th>
                        <th style="padding:.5rem .8rem">BARANG PENGGANTI</th>
                    </tr></thead><tbody style="font-size:.75rem">`;

                const validScan = (o.fulfillment_scan_log || []).filter(s => s.code && s.qty > 0);
                const resolveLines = (o.fulfillment_resolve_lines || []).filter(l => l.code);
                
                const scans = {};
                validScan.forEach(s => scans[s.code] = (scans[s.code] || 0) + s.qty);
                
                items.forEach(i => {
                    const code = i.internal_item?.code || i.model_sku || i.item_sku || '?';
                    const name = i.variant_name || i.internal_item?.name || i.item_name || '';
                    const dipesan = i.qty || 1;
                    const tersedia = scans[code] || 0;
                    const kurang = Math.max(0, dipesan - tersedia);
                    
                    let statusHtml = '';
                    if (kurang === 0) statusHtml = '<span style="color:#16a34a;font-weight:800">OK</span>';
                    else if (tersedia > 0) statusHtml = '<span style="color:#d97706;font-weight:800">Sebagian</span>';
                    else statusHtml = '<span style="background:#f1f5f9;color:#64748b;padding:2px 6px;border-radius:4px;font-size:.65rem;font-weight:600;white-space:nowrap;">Belum Tertaut</span>';
                    
                    // Cari item di resolveLines yang merupakan substitusi dari item ini
                    let subHtml = '<span style="color:#cbd5e1">—</span>';
                    if (kurang > 0 && resolveLines.length > 0) {
                        const mSku = i.model_sku || i.item_sku;
                        let subsList = resolveLines.filter(r => 
                            (r.substituted || r.split_parent_id || r.code !== code) && 
                            r.marketplace_sku === mSku
                        );
                        
                        // Fallback untuk order single item
                        if (subsList.length === 0 && items.length === 1) {
                            subsList = resolveLines.filter(r => r.substituted || r.split_parent_id || r.code !== code);
                        }
                        
                        if (subsList.length > 0) {
                            subHtml = subsList.map(r => `<div style="font-weight:700;color:#1d4ed8">${r.code} <span style="font-weight:400;color:#64748b">×${r.qty_ordered}</span></div>`).join('');
                        }
                    }

                    linesHtml += `<tr style="border-bottom:1px solid #f1f5f9">
                        <td style="padding:.5rem .8rem"><div style="font-weight:800;color:#334155;font-size:.8rem">${esc(code)}</div><div style="font-size:.68rem;color:#64748b">${esc(name)}</div></td>
                        <td style="padding:.5rem .8rem; text-align:right; font-weight:700; font-size:.8rem">${dipesan}</td>
                        <td style="padding:.5rem .8rem; text-align:right; font-size:.8rem">${tersedia}</td>
                        <td style="padding:.5rem .8rem; text-align:right; color:${kurang>0?'#dc2626':'#334155'}; font-weight:800; font-size:.8rem">${kurang>0?'-'+kurang:kurang}</td>
                        <td style="padding:.5rem .8rem; text-align:center">${statusHtml}</td>
                        <td style="padding:.5rem .8rem">${subHtml}</td>
                    </tr>`;
                });
                
                linesHtml += `</tbody></table></div>`;
                itemsSection = linesHtml;
            }

            // Tombol aksi
            let actionBtn = '';
            let logisticsBtn = '';

            // Logistics Buttons
            if (o.order_status === 'READY_TO_SHIP') {
                logisticsBtn = `<button class="btn-review" style="background:#fef9c3;color:#854d0e;border-color:#fef08a"
                    onclick="openArrangeShipment(${o.store_id}, '${o.channel_order_id}')">🚚 Atur Kirim</button>`;
            } else if (o.order_status === 'PROCESSED' || o.order_status === 'SHIPPED') {
                logisticsBtn = `<button class="btn-review" style="background:#f1f5f9;color:#475569;border-color:#e2e8f0"
                    onclick="printDocument(${o.store_id}, '${o.channel_order_id}')">🖨 Cetak Resi</button>`;
            }

            if (isFulfilled) {
                actionBtn = `<div class="btn-review" style="background:#f0fdf4;color:#16a34a;border-color:#bbf7d0;cursor:default">✓ Selesai</div>`;
            } else if (isInPacking) {
                actionBtn = `<button class="btn-review" style="background:#eff6ff;color:#2563eb;border-color:#bfdbfe"
                    title="Buat atau lanjutkan Draft Shipment Marketplace, lalu scan resi/order ini."
                    onclick="window.location='/sales/shipments'">Lanjut ke Shipment →</button>`;
            } else {
                actionBtn = `<button class="btn-review"
                    title="Buat atau lanjutkan Draft Shipment Marketplace, lalu scan resi/order ini."
                    onclick="window.location='/sales/shipments'">📦 Ke Shipment</button>`;
            }

            const dataIssue = o.has_data_issues
                ? `<span style="font-size:.63rem;font-weight:700;color:#d97706;background:#fef3c7;border:1px solid #fde68a;border-radius:999px;padding:.05rem .38rem">⚠ Belum mapping</span>`
                : '';

            let logBadge = '';
            if (o.logistics_status) {
                let statusText = o.logistics_status.replace('LOGISTICS_', '').replace(/_/g, ' ');
                // Terjemahan status umum Shopee agar lebih mudah dipahami
                if (o.logistics_status === 'LOGISTICS_REQUEST_CREATED') statusText = 'Permintaan Dibuat';
                else if (o.logistics_status === 'LOGISTICS_READY_TO_SHIP') statusText = 'Siap Dikirim';
                else if (o.logistics_status === 'LOGISTICS_NOT_START') statusText = 'Belum Dimulai';
                else if (o.logistics_status === 'LOGISTICS_SHIPPED') statusText = 'Sudah Dikirim';
                
                if (tabName === 'processed' && o.logistics_status === 'LOGISTICS_REQUEST_CREATED') {
                    // Sembunyikan badge 'Permintaan Dibuat' jika di tab Sedang Dikemas
                } else {
                    logBadge = `<span style="font-size:0.65rem; color:#4f46e5; background:#e0e7ff; border:1px solid #c7d2fe; border-radius:4px; padding:1px 6px; font-weight:700;" title="Status Logistik dari Marketplace">📡 ${statusText}</span>`;
                }
            }

            let orderDateHtml = '';
            if (o.ordered_at || o.created_at) {
                const dateVal = o.ordered_at || o.created_at;
                let isLate = false;
                
                const d = new Date(dateVal);
                const todayNoon = new Date();
                todayNoon.setHours(12, 0, 0, 0);
                if (d < todayNoon) {
                    isLate = true;
                }
                
                if (isLate) {
                    orderDateHtml = `<span class="pk-meta-text" style="color:#dc2626; font-weight:700; background:#fef2f2; padding:1px 5px; border-radius:4px; border:1px solid #fecaca;" title="Pesanan masuk sebelum jam 12 hari ini / kemarin">🗓 ${fmt(dateVal)}</span><span class="pk-meta-text" style="color:#e2e8f0">·</span>`;
                } else {
                    orderDateHtml = `<span class="pk-meta-text" style="color:#64748b">🗓 ${fmt(dateVal)}</span><span class="pk-meta-text" style="color:#e2e8f0">·</span>`;
                }
            }

            return `<div class="pk-row ${isPrinted && !isFulfilled ? 'row-printed' : ''}" style="flex-direction:column; align-items:stretch">
                <div style="display:flex; justify-content:space-between; align-items:center; width:100%">
                    <div class="pk-row-left">
                        <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                            <span class="pk-order-id">${esc(o.channel_order_id || '—')}</span>
                            ${kilatBadge}
                            ${o.shipping_awb_no ? `<span style="font-size:0.55rem; color:#059669; font-weight:700; padding:1px 6px; background:#d1fae5; border:1px solid #34d399; border-radius:4px;">${printedDocOrderSns.has(o.channel_order_id) ? '🖨️ ' : ''}${esc(o.shipping_awb_no)}</span>` : ''}
                            ${logBadge}
                            ${isPrinted && !isFulfilled ? `<span style="font-size:0.7rem; background:#e0f2fe; color:#0369a1; border-radius:4px; padding:1px 6px; font-weight:700; border:1px solid #7dd3fc;">🖨 Sudah Cetak</span>` : ''}
                            ${fBadgeHtml}
                            ${dataIssue}
                        </div>
                        <div class="pk-row-meta" style="margin-top:.28rem; display:flex; align-items:center; flex-wrap:wrap; gap:.35rem">
                            ${store ? `<span class="pk-meta-text">${esc(store)}</span><span class="pk-meta-text" style="color:#e2e8f0">·</span>` : ''}
                            ${orderDateHtml}
                            <div style="display:flex;flex-wrap:wrap;gap:.25rem;align-items:center">
                                ${itemChips}${moreChip}
                            </div>
                        </div>
                    </div>
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        ${logisticsBtn}
                        ${actionBtn}
                    </div>
                </div>
                ${isProcessed ? `<div style="margin-top:0.8rem">${itemsSection}</div>` : ''}
            </div>`;
        }).join('');

        const count = rows.length;
        const titleText = isProcessed ? '📋 Sedang Dikemas' : '⚡ Perlu Diproses';
        const subText = isProcessed ? 'Order yang sudah diatur pengirimannya' : 'Order baru menunggu untuk diproses';
        const badgeColor = isProcessed ? '#3b82f6' : '#f59e0b';

        return `<div class="pk-section">
            <div class="pk-section-header">
                <div>
                    <div class="pk-section-title">${titleText}</div>
                    <div class="pk-section-sub">${subText}</div>
                </div>
                <span class="pk-count-badge" style="background:${badgeColor}">${count} order</span>
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

            const isPrinted = printedOrderIds.has(o.id);
            const printBtn = `<button class="btn-review" style="background:${isPrinted ? '#e0f2fe' : '#f1f5f9'};color:${isPrinted ? '#0369a1' : '#475569'};border-color:${isPrinted ? '#7dd3fc' : '#e2e8f0'}" onclick="printDocument(${o.store_id}, '${o.channel_order_id}')">🖨 ${isPrinted ? 'Sudah Cetak' : 'Cetak'}</button>`;

            return `<div class="pk-row ${isPrinted ? 'row-printed' : ''}">
                <div class="pk-row-left">
                    <div class="pk-order-id">
                        ${esc(o.channel_order_id || '—')}
                        ${o.shipping_awb_no ? `<span style="font-size:0.55rem; color:#059669; margin-left:8px; font-weight:600; padding:2px 6px; background:#d1fae5; border-radius:4px;">${printedDocOrderSns.has(o.channel_order_id) ? '🖨️ ' : ''}${esc(o.shipping_awb_no)}</span>` : ''}
                    </div>
                    <div class="pk-row-meta">
                        ${store ? `<span class="pk-meta-text">${esc(store)}</span>` : ''}
                        ${fStatusLabel ? `<span class="pk-meta-text" style="color:#7c3aed;font-weight:700">${fStatusLabel}</span>` : ''}
                        ${statusBadgeEl}
                        ${packInfo}
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; gap:0.25rem;">
                    <button class="btn-review" onclick="openReviewModal(${o.id})">🔍 Review</button>
                    ${printBtn}
                </div>
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

        if (isMobile()) {
            body.innerHTML = renderMobileCards(rows);
            return;
        }

        const tableRows = rows.map(o => {
            const items  = o.items || [];
            const urgent = ACTIVE_ORDER_STATUSES.includes(o.order_status);

            let dateHtml = '—';
            if (o.ordered_at || o.created_at) {
                const dateVal = o.ordered_at || o.created_at;
                const d = new Date(dateVal);
                const todayNoon = new Date();
                todayNoon.setHours(12, 0, 0, 0);
                
                if (['ready', 'processed', 'ready_to_handover'].includes(activeTab) && d < todayNoon) {
                    dateHtml = `<span style="color:#ef4444; font-weight:600; font-size:0.65rem; background:#fef2f2; padding:1px 4px; border-radius:4px; border:1px solid #fee2e2;" title="Pesanan masuk sebelum jam 12 hari ini / kemarin">${fmt(dateVal)}</span>`;
                } else {
                    dateHtml = `<span style="color:#94a3b8; font-size:0.65rem; font-weight:500;">${fmt(dateVal)}</span>`;
                }
            }

            let logBadge = '';
            if (o.logistics_status) {
                let statusText = o.logistics_status.replace('LOGISTICS_', '').replace(/_/g, ' ');
                if (o.logistics_status === 'LOGISTICS_REQUEST_CREATED') statusText = 'Permintaan Dibuat';
                else if (o.logistics_status === 'LOGISTICS_READY_TO_SHIP') statusText = 'Siap Dikirim';
                else if (o.logistics_status === 'LOGISTICS_NOT_START') statusText = 'Belum Dimulai';
                else if (o.logistics_status === 'LOGISTICS_SHIPPED') statusText = 'Sudah Dikirim';
                
                if (!(activeTab === 'processed' && o.logistics_status === 'LOGISTICS_REQUEST_CREATED')) {
                    logBadge = `<span style="font-size:0.65rem; color:#4f46e5; background:#e0e7ff; border:1px solid #c7d2fe; border-radius:4px; padding:1px 6px; font-weight:700;">📡 ${statusText}</span>`;
                }
            }

            let itemsHtml = `<div class="ord-items-cell">`
                + items.map(i => renderItemCard(i, urgent)).join('')
                + `</div>`;
                
            if (activeTab === 'shipped' && o.fulfillment_scan_log?.length) {
                const validScan = o.fulfillment_scan_log.filter(s => s.code && s.qty > 0);
                if (validScan.length) {
                    const scanCards = validScan.map(s => {
                        return `<div class="ord-item-card" style="border-color:#e9d5ff;background:#f3e8ff;margin-top:4px">
                            <div class="ord-item-qty" style="background:#ede9fe;color:#6d28d9">${s.qty}×</div>
                            <div class="ord-item-body">
                                <div class="ord-item-name" style="color:#4c1d95">${esc(s.code)}</div>
                                ${s.name ? `<div class="ord-item-variant">${esc(s.name)}</div>` : ''}
                            </div>
                        </div>`;
                    }).join('');
                    
                    itemsHtml += `<div style="margin-top:8px;margin-bottom:4px;font-size:0.65rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.04em">📦 Item Scan:</div>
                                  <div class="ord-items-cell">${scanCards}</div>`;
                }
            }

            let scanTd = '';
            if (activeTab === 'processed') {
                const validScan = (o.fulfillment_scan_log || []).filter(s => s.code && s.qty > 0);
                if (validScan.length) {
                    const scanCards = validScan.map(s => `<div class="ord-item-card" style="border-color:#e9d5ff;background:#f3e8ff">
                        <div class="ord-item-qty" style="background:#ede9fe;color:#6d28d9">${s.qty}×</div>
                        <div class="ord-item-body">
                            <div class="ord-item-name" style="color:#4c1d95">${esc(s.code)}</div>
                            ${s.name ? `<div class="ord-item-variant">${esc(s.name)}</div>` : ''}
                        </div>
                    </div>`).join('');
                    scanTd = `<td><div class="ord-items-cell">${scanCards}</div></td>`;
                } else {
                    scanTd = `<td><div style="color:#cbd5e1;font-size:.72rem;font-style:italic">—</div></td>`;
                }
            }

            // Item Resolve column (Sedang Proses tab)
            let resolveTd = '';
            if (activeTab === 'processed') {
                const resolveLines = (o.fulfillment_resolve_lines || []).filter(l => l.code);
                if (resolveLines.length) {
                    const resolveCards = resolveLines.map(l => `<div class="ord-item-card" style="border-color:#bfdbfe;background:#eff6ff">
                        <div class="ord-item-qty" style="background:#dbeafe;color:#1d4ed8">${l.qty_ordered}×</div>
                        <div class="ord-item-body">
                            <div class="ord-item-name" style="color:#1e3a5f">${esc(l.code)}</div>
                            ${l.name ? `<div class="ord-item-variant">${esc(l.name)}</div>` : ''}
                        </div>
                    </div>`).join('');
                    resolveTd = `<td><div class="ord-items-cell">${resolveCards}</div></td>`;
                } else {
                    resolveTd = `<td><div style="color:#94a3b8;font-size:.7rem;font-style:italic;padding:4px 0">Tidak ada item pengganti</div></td>`;
                }
            }

            const isFulfilled = fulfilledOrderIds.has(o.id);
            const isPrinted   = printedOrderIds.has(o.id);
            const isInPacking = fulfillmentStatusMap.has(o.id) && !fulfilledOrderIds.has(o.id);
            const rowClass    = isFulfilled ? 'row-fulfilled' : (isInPacking ? 'row-packing' : (urgent ? 'row-urgent' : ''));
            const carrier     = (o.shipping_carrier || '').toLowerCase();
            const isKilat     = carrier.includes('instant') || carrier.includes('same day') || carrier.includes('sameday');
            const kilatBadge  = isKilat ? `<span style="font-size:.65rem;background:#fef08a;color:#854d0e;border-radius:4px;padding:1px 5px;font-weight:800;border:1px solid #fde047;">⚡ KILAT</span>` : '';

            let logisticsBtn = '';

            // Logistics Buttons
            if (o.order_status === 'READY_TO_SHIP') {
                logisticsBtn = `<button class="btn btn-sm btn-outline-primary" style="font-size:0.7rem;padding:0.15rem 0.5rem;width:100%" onclick="event.stopPropagation(); openArrangeShipment(${o.store_id}, '${o.channel_order_id}')">🚚 Atur Pengiriman</button>`;
            } else if (o.order_status === 'PROCESSED' || o.order_status === 'SHIPPED') {
                logisticsBtn = `<button class="btn btn-sm btn-outline-secondary" style="font-size:0.7rem;padding:0.15rem 0.5rem;width:100%" onclick="event.stopPropagation(); printDocument(${o.store_id}, '${o.channel_order_id}')">🖨 Cetak Resi</button>`;
            }

            const printedBadge = (isPrinted && !isFulfilled && !['processed', 'shipped'].includes(activeTab))
                ? `<span style="font-size:.65rem;background:#e0f2fe;color:#0369a1;border-radius:4px;padding:1px 5px;font-weight:600">🖨 Sudah Cetak</span>`
                : '';

            const fBadge = urgent ? fulfillmentBadge(o) : '';

            // Badge masalah di tab Sudah Proses
            let issueBadge = '';
            if (activeTab === 'processed' && o.fulfillment_lines?.length) {
                const iss = fulfilledOrderIssues(o.fulfillment_lines);
                if (iss.hasAny) {
                    const parts = [];
                    if (iss.hasShort) parts.push('Kurang');
                    if (iss.hasSub)   parts.push('Diganti');
                    if (iss.hasSplit) parts.push('Split');
                    issueBadge = `<span style="font-size:.63rem;font-weight:700;
                            background:#fef3c7;color:#92400e;border:1px solid #fde68a;
                            border-radius:4px;padding:1px 6px">⚠ ${parts.join(' · ')}</span>`;
                }
            }
            
            let pengirimanHtml = '';
            if (o.shipping_awb_no) {
                pengirimanHtml += `<div style="margin-bottom:6px"><span style="font-size:0.55rem; color:#059669; font-weight:700; padding:1px 6px; background:#d1fae5; border:1px solid #34d399; border-radius:4px; display:inline-block; word-break:break-all;">${printedDocOrderSns.has(o.channel_order_id) ? '🖨️ ' : ''}${esc(o.shipping_awb_no)}</span></div>`;
            } else if (o.shipping_carrier) {
                pengirimanHtml += `<div style="margin-bottom:6px"><span style="font-size:0.7rem; color:#64748b; font-weight:700; padding:1px 6px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:4px; display:inline-block;">${esc(o.shipping_carrier)}</span></div>`;
            }
            if (logisticsBtn) {
                pengirimanHtml += `<div>${logisticsBtn}</div>`;
                if (['processed', 'shipped'].includes(activeTab) && isPrinted) {
                    pengirimanHtml += `<div style="margin-top:4px; font-size:0.68rem; color:#94a3b8; font-style:italic; text-align:center;">✓ Sudah dicetak</div>`;
                }
            }

            const rowClick = activeTab === 'issues'
                ? `onclick="window.location='/marketplace/issues'" style="cursor:pointer"`
                : '';

            return `<tr class="${rowClass}${isPrinted && !isFulfilled ? ' row-printed' : ''}" id="ord-row-${o.id}" ${rowClick}>
                <td>
                    <div class="ord-id">${esc(o.channel_order_id || '—')}</div>
                    <div class="ord-date" style="margin-top:4px">${dateHtml}</div>
                    
                    <div style="display:flex; flex-wrap:wrap; gap:4px; margin-top:8px;">
                        ${kilatBadge}
                        ${printedBadge}
                        ${logBadge}
                        ${fBadge}
                        ${issueBadge}
                    </div>
                </td>
                <td>${itemsHtml}</td>
                ${resolveTd}
                ${scanTd}
                <td>${statusBadge(o.order_status)}</td>
                <td>${pengirimanHtml}</td>
                <td>
                    <div style="font-weight:700;font-size:.78rem">${esc(o.store?.name || '—')}</div>
                    <div style="margin-top:.2rem">${channelPill(o.store?.channel)}</div>
                </td>
            </tr>`;
        }).join('');

        const hasResolveCol = activeTab === 'processed';
        const hasScanCol    = activeTab === 'processed';
        // col widths: order(16%) | items | resolve? | scan? | status | pengiriman | store
        const colItems  = hasResolveCol ? '16%' : (hasScanCol ? '24%' : '34%');
        const colStatus = '12%';
        const colStore  = '12%';
        body.innerHTML = `
        <div class="gf-table-scroll">
        <table class="ord-table">
            <colgroup>
                <col style="width:16%">
                <col style="width:${colItems}">
                ${hasResolveCol ? '<col style="width:15%">' : ''}
                ${hasScanCol    ? '<col style="width:15%">' : ''}
                <col style="width:${colStatus}">
                <col style="width:14%">
                <col style="width:${colStore}">
            </colgroup>
            <thead><tr>
                <th>Nomor Order</th>
                <th>Item Produk</th>
                ${hasResolveCol ? '<th>✅ Item Pengganti</th>' : ''}
                ${hasScanCol    ? '<th>📦 Item Scan</th>'    : ''}
                <th>Status</th>
                <th>Pengiriman</th>
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
                        body: JSON.stringify({ 
                            time_from: from, 
                            time_to: now, 
                            page_size: 50,
                            dry_run: $('qsSyncDryRun').checked ? 1 : 0
                        }),
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
    // ── Logistics ────────────────────────────────────────────────────────
    window.openArrangeShipment = async function (storeId, orderSn) {
        $('asLoading').style.display = 'block';
        $('asContent').style.display = 'none';
        $('asStoreId').value = storeId;
        $('asOrderSn').value = orderSn;
        $('asOptions').innerHTML = '';
        $('asSubmitBtn').disabled = true;
        
        const modal = new bootstrap.Modal($('arrangeShipmentModal'));
        modal.show();

        try {
            const res = await api(`/api/marketplace/stores/${storeId}/orders/${orderSn}/shipping-parameter`);
            
            $('asLoading').style.display = 'none';
            $('asContent').style.display = 'block';
            
            // Shopee format is usually ['response']['info_needed']['dropoff'] etc
            // We will simplify and assume it returns options we can show, 
            // but if there's no complex info needed, we just provide a default proceed.
            const responseData = res.response || res;
            const infoNeeded = responseData.info_needed || {};
            
            let html = '';
            
            if (infoNeeded.dropoff) {
                html += `<div class="form-check mb-2">
                    <input class="form-check-input as-method-radio" type="radio" name="asMethod" id="asDropoff" value="dropoff" checked>
                    <label class="form-check-label" for="asDropoff"><strong>Drop-off</strong> (Antar ke Cabang)</label>
                </div>`;
            }
            if (infoNeeded.pickup) {
                html += `<div class="form-check mb-2">
                    <input class="form-check-input as-method-radio" type="radio" name="asMethod" id="asPickup" value="pickup" ${!infoNeeded.dropoff ? 'checked' : ''}>
                    <label class="form-check-label" for="asPickup"><strong>Pickup</strong> (Kurir Jemput)</label>
                </div>`;
                
                if (infoNeeded.pickup.address_list && infoNeeded.pickup.address_list.length > 0) {
                    html += `<div class="pickup-options ps-4 mt-2" id="pickupOptionsWrapper" style="${!infoNeeded.dropoff ? 'display:block;' : 'display:none;'}">
                        <div class="mb-2">
                            <label class="form-label" style="font-size:0.8rem">Alamat Pickup</label>
                            <select class="form-select form-select-sm" id="asPickupAddress">`;
                    infoNeeded.pickup.address_list.forEach((addr, idx) => {
                        let timeslots = JSON.stringify(addr.time_slot_list || []);
                        html += `<option value="${addr.address_id}" data-timeslots='${timeslots.replace(/'/g, "&#39;")}'>${addr.address || addr.address_id}</option>`;
                    });
                    html += `       </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label" style="font-size:0.8rem">Waktu Pickup</label>
                            <select class="form-select form-select-sm" id="asPickupTime"></select>
                        </div>
                    </div>`;
                }
            }
            
            if (html === '') {
                html = `<div class="alert alert-warning py-1" style="font-size:0.75rem">Informasi metode pengiriman tidak spesifik, lanjutkan untuk mencoba arrange shipment otomatis.</div>
                <input type="hidden" name="asMethod" value="auto">`;
            }
            
            $('asOptions').innerHTML = html;
            $('asSubmitBtn').disabled = false;
            
            // Bind events for dynamically added options
            const methodRadios = document.querySelectorAll('.as-method-radio, input[name="asMethod"]');
            const pickupWrapper = document.getElementById('pickupOptionsWrapper');
            const addressSelect = document.getElementById('asPickupAddress');
            const timeSelect = document.getElementById('asPickupTime');
            
            methodRadios.forEach(r => {
                r.addEventListener('change', (e) => {
                    if (pickupWrapper) {
                        pickupWrapper.style.display = (e.target.value === 'pickup') ? 'block' : 'none';
                    }
                });
            });
            
            if (addressSelect && timeSelect) {
                const updateTimes = () => {
                    const selected = addressSelect.options[addressSelect.selectedIndex];
                    if (!selected) return;
                    let timeslots = [];
                    try { timeslots = JSON.parse(selected.getAttribute('data-timeslots') || '[]'); } catch(e){}
                    timeSelect.innerHTML = '';
                    if (timeslots.length > 0) {
                        timeslots.forEach(ts => {
                            let text = ts.time_text || ts.pickup_time_id || 'Pilih Waktu';
                            let dateStr = ts.date ? new Date(ts.date * 1000).toLocaleDateString('id-ID') + ' ' : '';
                            timeSelect.innerHTML += `<option value="${ts.pickup_time_id}">${dateStr}${text}</option>`;
                        });
                    } else {
                        timeSelect.innerHTML = '<option value="">(Tidak ada waktu tersedia)</option>';
                    }
                };
                addressSelect.addEventListener('change', updateTimes);
                updateTimes();
            }

        } catch (e) {
            $('asLoading').style.display = 'none';
            $('asContent').style.display = 'block';
            $('asOptions').innerHTML = `<div class="alert alert-danger py-1" style="font-size:0.75rem">Gagal: ${e.message}</div>`;
        }
    };

    window.submitArrangeShipment = async function () {
        const storeId = $('asStoreId').value;
        const orderSn = $('asOrderSn').value;
        const methodEl = document.querySelector('input[name="asMethod"]:checked') || document.querySelector('input[name="asMethod"]');
        const method = methodEl ? methodEl.value : '';
        
        const btn = $('asSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '⏳ Memproses...';
        
        let params = {};
        if (method === 'dropoff') {
            params = { dropoff: {} };
        } else if (method === 'pickup') {
            params = { pickup: {} };
            const addressSelect = document.getElementById('asPickupAddress');
            const timeSelect = document.getElementById('asPickupTime');
            if (addressSelect && timeSelect) {
                // Ensure address_id is sent as an integer or string based on original value (typically integer)
                if (addressSelect.value) params.pickup.address_id = Number(addressSelect.value) || addressSelect.value;
                if (timeSelect.value) params.pickup.pickup_time_id = timeSelect.value;
            }
        }

        try {
            await api(`/api/marketplace/stores/${storeId}/orders/${orderSn}/ship`, {
                method: 'POST',
                body: JSON.stringify(params)
            });
            
            bootstrap.Modal.getInstance($('arrangeShipmentModal')).hide();
            showQsAlert('success', 'Atur Pengiriman berhasil untuk ' + orderSn);
            loadOrders();

            if ($('asPrintDocument') && $('asPrintDocument').checked) {
                printDocument(storeId, orderSn);
            }
        } catch (e) {
            btn.disabled = false;
            btn.innerHTML = 'Coba Lagi';
            alert('Gagal Atur Pengiriman: ' + e.message);
        }
    };

    window.printDocument = async function (storeId, orderSn) {
        const url = `/api/marketplace/stores/${storeId}/orders/${orderSn}/document`;
        
        const alertHtml = `<div id="printAlert" style="position:fixed;top:20px;right:20px;background:#3b82f6;color:white;padding:10px 20px;border-radius:8px;z-index:9999;box-shadow:0 4px 6px rgba(0,0,0,0.1)">⏳ Meminta dokumen resi dari Marketplace...</div>`;
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        // Buka langsung agar tidak diblokir popup blocker
        window.open(url, '_blank');
        
        printedDocOrderSns.add(orderSn);
        
        setTimeout(() => {
            const el = document.getElementById('printAlert');
            if (el) el.remove();
            renderTable();
        }, 2000);
    };
    window.printAllDocuments = function() {
        const rows = getPackingRows();
        if (!rows.length) { alert('Tidak ada order yang sedang dikemas.'); return; }
        
        const unprintedRows = rows.filter(o => !printedDocOrderSns.has(o.channel_order_id));
        const total = rows.length;
        const unprinted = unprintedRows.length;

        const modalHtml = `
            <div id="printOptsModal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:99999;">
                <div style="background:white;padding:24px;border-radius:12px;width:320px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);">
                    <h3 style="margin-top:0;margin-bottom:12px;font-size:1.1rem;color:#1e293b;">🖨️ Cetak Resi Massal</h3>
                    <p style="font-size:0.85rem;color:#64748b;margin-bottom:20px;">Terdapat <strong>${total}</strong> total order.<br><strong>${unprinted}</strong> di antaranya belum dicetak resi.</p>
                    
                    <div style="margin-bottom:16px;">
                        <label style="display:flex;align-items:center;gap:8px;font-size:0.9rem;cursor:pointer;">
                            <input type="checkbox" id="chkPrintGreeting" checked style="width:16px;height:16px;">
                            Sertakan Kartu Ucapan
                        </label>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <button onclick="executePrintBulk(true)" class="btn-toolbar primary" style="width:100%;justify-content:center;background:#059669;border-color:#059669;color:white;cursor:${unprinted === 0 ? 'not-allowed' : 'pointer'};opacity:${unprinted === 0 ? '0.5' : '1'};" ${unprinted === 0 ? 'disabled' : ''}>
                            Cetak yang Belum (${unprinted})
                        </button>
                        <button onclick="executePrintBulk(false)" class="btn-toolbar" style="width:100%;justify-content:center;background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;cursor:pointer;">
                            Cetak Semua (${total})
                        </button>
                        <button onclick="document.getElementById('printOptsModal').remove()" class="btn-toolbar" style="width:100%;justify-content:center;background:transparent;border:none;color:#94a3b8;box-shadow:none;cursor:pointer;">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    };

    window.executePrintBulk = function(onlyUnprinted) {
        let printGreeting = true;
        const chk = document.getElementById('chkPrintGreeting');
        if (chk) printGreeting = chk.checked;
        
        const modal = document.getElementById('printOptsModal');
        if (modal) modal.remove();

        let rows = getPackingRows();
        if (onlyUnprinted) {
            rows = rows.filter(o => !printedDocOrderSns.has(o.channel_order_id));
        }

        if (!rows.length) { alert('Tidak ada order untuk dicetak.'); return; }
        
        // Kelompokkan orderSn berdasarkan store_id + shipping_carrier (logistics channel)
        // Shopee menolak bulk print jika channel/ekspedisi berbeda-beda dalam 1 request
        const groupedData = {};
        rows.forEach(o => {
            const carrier = o.shipping_carrier || 'default';
            const key = `${o.store_id}_${carrier}`;
            if (!groupedData[key]) groupedData[key] = { storeId: o.store_id, carrier: carrier, sns: [] };
            groupedData[key].sns.push(o.channel_order_id);
        });
        
        const alertHtml = `<div id="printBulkAlert" style="position:fixed;top:20px;right:20px;background:#f59e0b;color:white;padding:10px 20px;border-radius:8px;z-index:9999;box-shadow:0 4px 6px rgba(0,0,0,0.1)">⏳ Meminta dokumen resi massal dari Marketplace...</div>`;
        document.body.insertAdjacentHTML('beforeend', alertHtml);

        Object.values(groupedData).forEach(group => {
            const storeId = group.storeId;
            const allSns = group.sns;
            
            // Shopee limit 50 order per bulk request
            for (let i = 0; i < allSns.length; i += 50) {
                const chunk = allSns.slice(i, i + 50);
                const orderSns = chunk.join(',');
                const cardParam = printGreeting ? '1' : '0';
                const url = `/api/marketplace/stores/${storeId}/documents/bulk?orders=${orderSns}&card=${cardParam}`;
                window.open(url, '_blank');
            }
        });

        rows.forEach(o => printedDocOrderSns.add(o.channel_order_id));

        setTimeout(() => {
            const el = document.getElementById('printBulkAlert');
            if (el) el.remove();
            renderTable();
        }, 2000);
    };

    window.printAllGreetings = function() {
        const rows = getPackingRows();
        // Cukup ambil storeId dari baris pertama saja untuk memenuhi route Laravel
        const storeId = rows.length > 0 ? rows[0].store_id : (activeStore || 1);
        
        const alertHtml = `<div id="printBulkAlertGreetings" style="position:fixed;top:20px;right:20px;background:#8b5cf6;color:white;padding:10px 20px;border-radius:8px;z-index:9999;box-shadow:0 4px 6px rgba(0,0,0,0.1)">⏳ Meminta dokumen kartu ucapan...</div>`;
        document.body.insertAdjacentHTML('beforeend', alertHtml);

        // Hanya buka 1 tab berisi 1 halaman sesuai permintaan user
        const url = `/api/marketplace/stores/${storeId}/documents/bulk-greetings?orders=1`;
        window.open(url, '_blank');

        setTimeout(() => {
            const el = document.getElementById('printBulkAlertGreetings');
            if (el) el.remove();
        }, 1500);
    };

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
