{{-- resources/views/sales/shipments/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Scan • ' . $shipment->code)

@push('head')
<style>
/* ══════════════════════════════════════════════════
   THEME VARIABLES
══════════════════════════════════════════════════ */
:root {
    --shp-accent:     #2563eb;
    --shp-accent-2:   #1d4ed8;
    --shp-accent-bg:  rgba(37,99,235,.08);
    --shp-accent-ring:rgba(37,99,235,.22);
    --shp-ok:         #15803d;
    --shp-ok-bg:      rgba(21,128,61,.08);
    --shp-err:        #b91c1c;
    --shp-err-bg:     rgba(185,28,28,.08);
    --shp-warn-bg:    rgba(245,158,11,.08);
    --shp-warn:       #92400e;
}
.page-theme-shopee {
    --shp-accent:     #f97316;
    --shp-accent-2:   #ea580c;
    --shp-accent-bg:  rgba(249,115,22,.08);
    --shp-accent-ring:rgba(249,115,22,.22);
}
.page-theme-tiktok {
    --shp-accent:     #0f766e;
    --shp-accent-2:   #0e7490;
    --shp-accent-bg:  rgba(15,118,110,.08);
    --shp-accent-ring:rgba(45,212,191,.22);
}

/* ══════════════════════════════════════════════════
   PAGE WRAP
══════════════════════════════════════════════════ */
.shp-wrap {
    max-width: 1100px;
    margin-inline: auto;
    padding: 0 .75rem 6rem;
}
body[data-theme="light"] .shp-wrap               { background: #f3f4f6; }
body[data-theme="light"] .page-theme-shopee       { background: #fff7ed; }
body[data-theme="light"] .page-theme-tiktok       { background: #ecfeff; }
body[data-theme="dark"]  .shp-wrap               {
    background: radial-gradient(circle at top left, rgba(15,23,42,.9) 0, #020617 65%);
}
body[data-theme="dark"]  .page-theme-shopee       {
    background: radial-gradient(circle at top left, rgba(148,27,19,.9) 0, #020617 65%);
}
body[data-theme="dark"]  .page-theme-tiktok       {
    background: radial-gradient(circle at top left, rgba(8,47,73,.9) 0, #020617 65%);
}

/* ══════════════════════════════════════════════════
   STICKY TOP BAR
══════════════════════════════════════════════════ */
.shp-topbar {
    position: sticky;
    top: 0;
    z-index: 300;
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .5rem .85rem;
    background: rgba(248,250,252,.97);
    border-bottom: 1px solid rgba(148,163,184,.22);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    flex-wrap: wrap;
}
body[data-theme="dark"] .shp-topbar {
    background: rgba(2,6,23,.96);
    border-bottom-color: rgba(30,64,175,.45);
}
.shp-topbar-code {
    font-weight: 900;
    font-size: 1.05rem;
    letter-spacing: .04em;
    white-space: nowrap;
}
body[data-theme="dark"] .shp-topbar-code { color: #e5e7eb; }

/* badges */
.shp-badge {
    border-radius: 999px;
    padding: .15rem .65rem;
    font-size: .7rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    white-space: nowrap;
}
.shp-badge-draft {
    background: rgba(251,191,36,.1);
    color: #92400e;
    border: 1px solid rgba(245,158,11,.28);
}
body[data-theme="dark"] .shp-badge-draft {
    background: rgba(251,191,36,.2);
    color: #fef9c3;
    border-color: rgba(245,158,11,.6);
}
.shp-badge-store {
    border: 1px solid rgba(148,163,184,.55);
    font-size: .73rem;
}
.page-theme-shopee .shp-badge-store { border-color: #f97316; color: #9a3412; background: rgba(254,215,170,.2); }
.page-theme-tiktok .shp-badge-store { border-color: #06b6d4; color: #0f766e; background: rgba(207,250,254,.2); }

.shp-topbar-spacer { flex: 1; min-width: .5rem; }

/* summary pills */
.shp-pill {
    border-radius: 999px;
    padding: .2rem .75rem;
    font-size: .77rem;
    border: 1px solid rgba(148,163,184,.32);
    background: rgba(248,250,252,.96);
    white-space: nowrap;
}
body[data-theme="dark"] .shp-pill {
    background: rgba(15,23,42,.98);
    border-color: rgba(30,64,175,.65);
    color: #e5e7eb;
}
.shp-pill b { font-size: .87rem; }
.shp-pill-accent {
    border-color: var(--shp-accent) !important;
    background: var(--shp-accent-bg) !important;
    color: var(--shp-accent) !important;
    font-weight: 700;
}
body[data-theme="dark"] .shp-pill-accent { color: #93c5fd !important; }

/* ══════════════════════════════════════════════════
   SUBMIT BUTTON (topbar)
══════════════════════════════════════════════════ */
.btn-shp-submit {
    border-radius: 999px;
    font-size: .8rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    padding: .42rem 1.35rem;
    border: 1px solid var(--shp-accent);
    background: var(--shp-accent);
    color: #fff;
    box-shadow: 0 4px 14px rgba(0,0,0,.2);
    transition: background .12s, box-shadow .12s;
    white-space: nowrap;
}
.btn-shp-submit:hover {
    background: var(--shp-accent-2);
    border-color: var(--shp-accent-2);
    color: #fff;
}
.btn-shp-outline {
    border-radius: 999px;
    font-size: .77rem;
    letter-spacing: .05em;
    text-transform: uppercase;
    padding: .32rem 1rem;
    border: 1px solid rgba(148,163,184,.5);
    background: transparent;
    color: #6b7280;
    white-space: nowrap;
    transition: background .12s, color .12s;
}
.btn-shp-outline:hover { background: rgba(226,232,240,.7); color: #374151; }
body[data-theme="dark"] .btn-shp-outline { color: #d1d5db; border-color: rgba(71,85,105,.8); }
body[data-theme="dark"] .btn-shp-outline:hover { background: rgba(30,41,59,.7); color: #f1f5f9; }

.btn-shp-ghost {
    border-radius: 999px;
    font-size: .74rem;
    letter-spacing: .04em;
    text-transform: uppercase;
    padding: .28rem .85rem;
    background: transparent;
    border: 1px solid transparent;
    color: #9ca3af;
    transition: background .12s, color .12s;
}
.btn-shp-ghost:hover { background: rgba(226,232,240,.7); color: #374151; }

/* ══════════════════════════════════════════════════
   KPI GRID
══════════════════════════════════════════════════ */
.shp-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: .65rem;
    margin-top: .85rem;
}
@media (max-width: 640px) {
    .shp-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
.shp-kpi-card {
    background: var(--card, #fff);
    border-radius: 14px;
    border: 1px solid rgba(148,163,184,.16);
    padding: .85rem 1.1rem;
    box-shadow: 0 2px 10px rgba(15,23,42,.04);
}
body[data-theme="dark"] .shp-kpi-card {
    border-color: rgba(30,64,175,.5);
    box-shadow: 0 8px 24px rgba(15,23,42,.7);
}
.shp-kpi-label {
    font-size: .64rem;
    text-transform: uppercase;
    letter-spacing: .09em;
    color: #9ca3af;
    margin-bottom: .3rem;
}
.shp-kpi-value {
    font-size: 1.6rem;
    font-weight: 900;
    line-height: 1;
    color: var(--shp-accent);
}
body[data-theme="dark"] .shp-kpi-value { color: #93c5fd; }
.page-theme-shopee .shp-kpi-value { color: #f97316; }
.page-theme-tiktok .shp-kpi-value { color: #0d9488; }

/* ══════════════════════════════════════════════════
   SHIPMENT INFO STRIP
══════════════════════════════════════════════════ */
.shp-info-strip {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem .9rem;
    margin-top: .85rem;
    padding: .65rem 1rem;
    border-radius: 12px;
    background: var(--shp-accent-bg);
    border: 1px solid rgba(148,163,184,.18);
    font-size: .82rem;
    align-items: center;
}
body[data-theme="dark"] .shp-info-strip {
    background: rgba(15,23,42,.75);
    border-color: rgba(30,64,175,.5);
}
.shp-info-item { color: #6b7280; }
.shp-info-item b { color: #1e293b; }
body[data-theme="dark"] .shp-info-item b { color: #e2e8f0; }

/* ══════════════════════════════════════════════════
   HERO SCAN CARD
══════════════════════════════════════════════════ */
.shp-scan-card {
    background: var(--card, #fff);
    border-radius: 20px;
    border: 2px solid rgba(148,163,184,.18);
    box-shadow: 0 4px 22px rgba(15,23,42,.06);
    padding: 1.35rem 1.5rem 1.2rem;
    margin-top: .75rem;
    transition: border-color .15s, box-shadow .15s;
}
.shp-scan-card:focus-within {
    border-color: var(--shp-accent);
    box-shadow: 0 0 0 4px var(--shp-accent-ring), 0 8px 28px rgba(15,23,42,.1);
}
body[data-theme="dark"] .shp-scan-card {
    border-color: rgba(30,64,175,.5);
    box-shadow: 0 12px 36px rgba(15,23,42,.85);
}
body[data-theme="dark"] .shp-scan-card:focus-within {
    border-color: var(--shp-accent);
    box-shadow: 0 0 0 4px var(--shp-accent-ring), 0 12px 36px rgba(15,23,42,.85);
}

.shp-scan-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .65rem;
    flex-wrap: wrap;
    gap: .35rem;
}
.shp-scan-label {
    font-size: .64rem;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: #9ca3af;
    font-weight: 700;
}
body[data-theme="dark"] .shp-scan-label { color: #6b7280; }
.shp-scan-counter {
    font-size: .7rem;
    color: var(--shp-accent);
    font-weight: 700;
    letter-spacing: .04em;
}
body[data-theme="dark"] .shp-scan-counter { color: #93c5fd; }

.shp-scan-input {
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: .15em;
    padding: .75rem 1.1rem;
    text-transform: uppercase;
    border-radius: 14px;
    border: 2.5px solid rgba(148,163,184,.35);
    width: 100%;
    line-height: 1.2;
    transition: border-color .12s, box-shadow .12s;
    background: transparent;
}
body[data-theme="dark"] .shp-scan-input {
    background: rgba(15,23,42,.6);
    border-color: rgba(51,65,85,.9);
    color: #f1f5f9;
}
.shp-scan-input::placeholder {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 400;
    font-size: 1.15rem;
    color: #cbd5e1;
}
body[data-theme="dark"] .shp-scan-input::placeholder { color: #334155; }
.shp-scan-input:focus {
    border-color: var(--shp-accent);
    box-shadow: 0 0 0 3px var(--shp-accent-ring);
    outline: none;
}

/* ── last scan ticker ── */
.shp-last-scan {
    display: none;
    align-items: center;
    gap: .85rem;
    margin-top: .85rem;
    padding: .85rem 1.1rem;
    border-radius: 14px;
    background: rgba(240,253,244,.96);
    border: 1.5px solid rgba(74,222,128,.38);
    animation: shpSlideIn .2s ease;
}
body[data-theme="dark"] .shp-last-scan {
    background: rgba(5,46,22,.75);
    border-color: rgba(74,222,128,.38);
}
@keyframes shpSlideIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.shp-ls-icon { font-size: 1.6rem; line-height: 1; flex-shrink: 0; }
.shp-ls-code {
    font-weight: 900;
    font-size: 1.2rem;
    letter-spacing: .06em;
    font-family: monospace;
    color: #15803d;
}
body[data-theme="dark"] .shp-ls-code { color: #4ade80; }
.shp-ls-name { font-size: .86rem; color: #374151; margin-top: .1rem; }
body[data-theme="dark"] .shp-ls-name { color: #bbf7d0; }
.shp-ls-qty-wrap { margin-left: auto; text-align: right; flex-shrink: 0; }
.shp-ls-qty {
    font-weight: 900;
    font-size: 2rem;
    color: #15803d;
    line-height: 1;
}
body[data-theme="dark"] .shp-ls-qty { color: #4ade80; }
.shp-ls-qty-label {
    font-size: .62rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #6b7280;
}

/* ── scan error ticker ── */
.shp-scan-error {
    display: none;
    align-items: center;
    gap: .65rem;
    margin-top: .85rem;
    padding: .8rem 1.1rem;
    border-radius: 14px;
    background: rgba(254,242,242,.96);
    border: 1.5px solid rgba(252,165,165,.45);
    color: #991b1b;
    font-size: .9rem;
    font-weight: 700;
    animation: shpSlideIn .18s ease;
}
body[data-theme="dark"] .shp-scan-error {
    background: rgba(69,10,10,.75);
    border-color: rgba(239,68,68,.45);
    color: #fca5a5;
}
.shp-scan-error-icon { font-size: 1.25rem; flex-shrink: 0; }

/* ══════════════════════════════════════════════════
   STOCK ERROR PANEL
══════════════════════════════════════════════════ */
.shp-error-panel {
    border-radius: 16px;
    border: 1.5px solid rgba(245,158,11,.38);
    background: rgba(255,251,235,.96);
    color: #78350f;
    padding: 1.1rem 1.35rem;
    margin-top: .85rem;
}
body[data-theme="dark"] .shp-error-panel {
    background: rgba(69,26,3,.7);
    border-color: rgba(245,158,11,.5);
    color: #fef3c7;
}
.shp-error-title { font-weight: 900; font-size: 1rem; }
.shp-error-copy { font-size: .85rem; line-height: 1.5; }
.shp-error-list { margin: .45rem 0 0; padding-left: 1.15rem; }
.shp-error-list li { margin-bottom: .25rem; font-size: .83rem; }
.shp-stock-table-wrap {
    overflow: hidden;
    border-radius: 12px;
    border: 1px solid rgba(245,158,11,.22);
    margin-top: .9rem;
}
.shp-stock-table-wrap table { margin-bottom: 0; }
.shp-stock-table-wrap th {
    font-size: .67rem; text-transform: uppercase; letter-spacing: .06em;
    color: #92400e; background: rgba(254,243,199,.8);
}
body[data-theme="dark"] .shp-stock-table-wrap th {
    background: rgba(120,53,15,.65); color: #fde68a;
}

/* ══════════════════════════════════════════════════
   ITEMS TABLE CARD
══════════════════════════════════════════════════ */
.shp-table-card {
    background: var(--card, #fff);
    border-radius: 20px;
    border: 1px solid rgba(148,163,184,.16);
    box-shadow: 0 4px 18px rgba(15,23,42,.05);
    margin-top: .85rem;
    overflow: hidden;
}
body[data-theme="dark"] .shp-table-card {
    border-color: rgba(30,64,175,.55);
    box-shadow: 0 12px 36px rgba(15,23,42,.8);
}
.shp-table-head {
    display: flex;
    align-items: center;
    gap: .65rem;
    flex-wrap: wrap;
    padding: .85rem 1.25rem .7rem;
    border-bottom: 1px solid rgba(148,163,184,.14);
}
body[data-theme="dark"] .shp-table-head { border-bottom-color: rgba(51,65,85,.8); }
.shp-table-title {
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: #9ca3af;
    font-weight: 700;
}
.shp-table-actions {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .55rem 1.25rem .6rem;
    border-bottom: 1px solid rgba(148,163,184,.09);
    flex-wrap: wrap;
}
body[data-theme="dark"] .shp-table-actions { border-bottom-color: rgba(51,65,85,.45); }

/* scrollable area */
.lines-wrapper {
    max-height: 44vh;
    overflow-y: auto;
    overscroll-behavior: contain;
    scroll-behavior: smooth;
    scrollbar-width: thin;
    scrollbar-color: rgba(148,163,184,.65) transparent;
}
.lines-wrapper::-webkit-scrollbar { width: 5px; }
.lines-wrapper::-webkit-scrollbar-track { background: transparent; }
.lines-wrapper::-webkit-scrollbar-thumb { background: rgba(148,163,184,.65); border-radius: 999px; }
@media (max-width: 768px) { .lines-wrapper { max-height: 52vh; } }

/* table */
.shp-table { margin-bottom: 0; }
.shp-table thead th {
    position: sticky; top: 0; z-index: 6;
    border-bottom-width: 1px;
    font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; color: #9ca3af;
    background: rgba(248,250,252,.98);
    padding: .55rem .85rem;
    white-space: nowrap;
}
body[data-theme="dark"] .shp-table thead th {
    background: rgba(15,23,42,.98);
    border-bottom-color: rgba(30,64,175,.7);
    color: #6b7280;
}
.shp-table tbody td {
    vertical-align: middle;
    border-top-color: rgba(148,163,184,.12);
    padding: .6rem .85rem;
}
body[data-theme="dark"] .shp-table tbody td { border-top-color: rgba(51,65,85,.65); }
.shp-table tbody tr:nth-child(even) { background: rgba(249,250,251,.7); }
body[data-theme="dark"] .shp-table tbody tr:nth-child(even) { background: rgba(15,23,42,.8); }

/* item info */
.item-code {
    font-weight: 800;
    font-size: 1.05rem;
    font-family: monospace;
    letter-spacing: .04em;
}
body[data-theme="dark"] .item-code { color: #e2e8f0; }
.item-name { font-size: .88rem; color: #4b5563; }
body[data-theme="dark"] .item-name { color: #94a3b8; }

/* qty pill — BIG for warehouse use */
.qty-display {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 80px;
    padding: .32rem .9rem;
    border-radius: 999px;
    border: 1.5px solid rgba(148,163,184,.5);
    font-weight: 800;
    font-size: 1.05rem;
    cursor: pointer;
    background: rgba(248,250,252,.96);
    transition: background .12s, box-shadow .1s, transform .1s;
    user-select: none;
}
.qty-display:hover {
    background: var(--shp-accent-bg);
    border-color: var(--shp-accent);
    box-shadow: 0 0 0 2.5px var(--shp-accent-ring);
    transform: translateY(-1px);
}
body[data-theme="dark"] .qty-display {
    background: rgba(15,23,42,.95);
    border-color: rgba(71,85,105,.9);
    color: #e2e8f0;
}
body[data-theme="dark"] .qty-display:hover {
    background: rgba(30,64,175,.45);
    border-color: #38bdf8;
    box-shadow: 0 0 0 2.5px rgba(56,189,248,.35);
}

.qty-edit-form { display: inline-flex; align-items: center; gap: .3rem; }
.qty-edit-input { width: 88px; text-align: right; padding-right: .4rem; font-size: .95rem; }
.qty-edit-save-btn { border-radius: 999px; padding: .2rem .55rem; font-size: .78rem; line-height: 1; }

/* last scanned row */
@keyframes rowFlash {
    0%   { background: rgba(253,224,71,.9) !important; }
    100% { background: rgba(254,249,195,.35); }
}
.row-flash td { animation: rowFlash .75s ease-out forwards; }
.last-scanned-row td { background: rgba(254,243,199,.85) !important; }
.last-scanned-row td:first-child { border-left: 3.5px solid var(--shp-accent); }
body[data-theme="dark"] .last-scanned-row td { background: rgba(30,64,175,.6) !important; }
body[data-theme="dark"] .last-scanned-row td:first-child { border-left-color: #38bdf8; }

/* delete btn */
.btn-del {
    width: 34px; height: 34px;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 8px;
    border: 1px solid rgba(239,68,68,.38);
    background: transparent;
    color: #ef4444;
    font-size: .9rem;
    transition: background .1s, border-color .1s;
    padding: 0; cursor: pointer;
}
.btn-del:hover { background: rgba(254,226,226,.9); border-color: #ef4444; }
body[data-theme="dark"] .btn-del { color: #fca5a5; border-color: rgba(239,68,68,.45); }
body[data-theme="dark"] .btn-del:hover { background: rgba(127,29,29,.55); }

/* ══════════════════════════════════════════════════
   FLASH
══════════════════════════════════════════════════ */
.shp-flash {
    border-radius: 12px;
    padding: .65rem 1rem;
    font-size: .85rem;
    margin-top: .75rem;
}

/* ══════════════════════════════════════════════════
   TOAST
══════════════════════════════════════════════════ */
.shp-toast {
    position: fixed;
    top: 4.5rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1090;
    min-width: 200px;
    max-width: 360px;
    border-radius: 999px;
    padding: .55rem 1.25rem;
    font-size: .88rem;
    font-weight: 700;
    display: none;
    align-items: center;
    gap: .5rem;
    box-shadow: 0 12px 36px rgba(15,23,42,.38);
    pointer-events: none;
}
.shp-toast-ok  { background: #15803d; color: #f0fdf4; }
.shp-toast-err { background: #b91c1c; color: #fee2e2; }
</style>
@endpush

@section('content')
@php
    $totalQty          = $shipment->lines->sum('qty_scanned');
    $totalLines        = $shipment->lines->count();
    $lastScannedLineId = session('last_scanned_line_id');
    $stockInsufficient = collect(session('stock_insufficient', []));
    $hasStockError     = $stockInsufficient->isNotEmpty();

    $storeName = $shipment->store->name ?? '';
    $storeCode = $shipment->store->code ?? '';
    $storeKey  = strtoupper($storeCode . ' ' . $storeName);

    $scanTheme = 'default';
    if (str_contains($storeKey, 'SHP') || str_contains($storeKey, 'SHOPEE'))  $scanTheme = 'shopee';
    elseif (str_contains($storeKey, 'TTK') || str_contains($storeKey, 'TIKTOK')) $scanTheme = 'tiktok';
@endphp

{{-- ═══════════════════════ STICKY TOP BAR ═══════════════════════ --}}
<div class="shp-topbar page-theme-{{ $scanTheme }}">
    <span class="shp-topbar-code">{{ $shipment->code }}</span>
    <span class="shp-badge shp-badge-draft">Draft</span>

    @if ($shipment->store)
        <span class="shp-badge shp-badge-store">
            {{ strtoupper($storeCode) ?: $storeName }}
            @if ($storeCode && $storeName) &nbsp;·&nbsp;{{ $storeName }} @endif
        </span>
    @endif

    <span class="shp-topbar-spacer"></span>

    <span class="shp-pill">
        Baris <b id="summaryTotalLines">{{ $totalLines }}</b>
    </span>
    <span class="shp-pill shp-pill-accent">
        Qty <b id="summaryTotalQty">{{ number_format($totalQty, 0, ',', '.') }}</b>
    </span>

    {{-- Rekonsiliasi Pesanan (Opsi C) --}}
    <a href="{{ route('sales.shipments.rekon', $shipment) }}"
       class="btn"
       style="border-radius:999px;font-size:.78rem;font-weight:700;letter-spacing:.04em;
              padding:.38rem 1.1rem;border:1.5px solid #6366f1;color:#4338ca;background:rgba(99,102,241,.08);
              white-space:nowrap;text-decoration:none;transition:background .12s"
       onmouseover="this.style.background='rgba(99,102,241,.16)'"
       onmouseout="this.style.background='rgba(99,102,241,.08)'">
        ⇄ Rekonsiliasi Pesanan
    </a>

    <form id="submitForm"
          action="{{ route('sales.shipments.submit', $shipment) }}"
          method="POST"
          onsubmit="return confirmSubmit(event)">
        @csrf
        <button type="submit" class="btn btn-shp-submit page-theme-{{ $scanTheme }}">
            Simpan &amp; Kurangi Stok ↗
        </button>
    </form>
</div>

<div class="shp-wrap page-theme-{{ $scanTheme }}">

    {{-- ═════════════════ FLASH ═════════════════ --}}
    @if (session('status') === 'error')
        <div class="shp-flash alert alert-danger js-auto-hide-alert" role="alert">
            {{ session('message') }}
        </div>
    @elseif (session('status') === 'success')
        <div class="shp-flash alert alert-success js-auto-hide-alert" role="alert">
            {{ session('message') }}
        </div>
    @endif

    {{-- ═════════════════ KPI GRID ═════════════════ --}}
    <div class="shp-kpi-grid">
        <div class="shp-kpi-card">
            <div class="shp-kpi-label">Shipment hari ini</div>
            <div class="shp-kpi-value">{{ $kpi['created'] }}</div>
        </div>
        <div class="shp-kpi-card">
            <div class="shp-kpi-label">Item keluar hari ini</div>
            <div class="shp-kpi-value">{{ number_format($kpi['qty'], 0, ',', '.') }}</div>
        </div>
        <div class="shp-kpi-card">
            <div class="shp-kpi-label">Masih draft</div>
            <div class="shp-kpi-value">{{ $kpi['draft'] }}</div>
        </div>
        <div class="shp-kpi-card">
            <div class="shp-kpi-label">Sudah selesai</div>
            <div class="shp-kpi-value">{{ $kpi['posted'] }}</div>
        </div>
    </div>

    {{-- ═════════════════ INFO STRIP ═════════════════ --}}
    <div class="shp-info-strip">
        <span class="shp-info-item"><b>{{ $shipment->code }}</b></span>
        <span class="shp-info-item">Tgl: <b>{{ id_date($shipment->date) }}</b></span>
        @if ($shipment->store)
            <span class="shp-info-item">Toko: <b>{{ $storeName }}</b></span>
        @endif
        @if ($shipment->invoice)
            <span class="shp-info-item">Invoice: <b>{{ $shipment->invoice->code ?? '-' }}</b></span>
        @endif
        @if ($shipment->notes)
            <span class="shp-info-item">Catatan: <b>{{ $shipment->notes }}</b></span>
        @endif
        <span class="ms-auto"></span>
        <a href="{{ route('sales.shipments.show', $shipment) }}"
           class="btn btn-shp-ghost">← Detail</a>
    </div>

    {{-- ═════════════════ HERO SCAN CARD ═════════════════ --}}
    <div class="shp-scan-card">
        <div class="shp-scan-header">
            <span class="shp-scan-label">⚡ Scan Barang</span>
            <span class="shp-scan-counter" id="sessionCounter">0 scan sesi ini</span>
        </div>

        <form id="scanForm" method="POST"
              action="{{ parse_url(route('sales.shipments.scan_item', $shipment), PHP_URL_PATH) }}">
            @csrf
            <input type="text" name="scan_code"
                   class="form-control shp-scan-input" id="scanInput"
                   placeholder="Arahkan scanner ke sini…"
                   autocomplete="off" spellcheck="false" required>
        </form>

        {{-- last scanned ticker --}}
        <div class="shp-last-scan" id="lastScanBox">
            <span class="shp-ls-icon">✅</span>
            <div>
                <div class="shp-ls-code" id="lastScanCode">—</div>
                <div class="shp-ls-name" id="lastScanName"></div>
            </div>
            <div class="shp-ls-qty-wrap">
                <div class="shp-ls-qty-label">Qty total</div>
                <div class="shp-ls-qty" id="lastScanQty">—</div>
            </div>
        </div>

        {{-- scan error ticker --}}
        <div class="shp-scan-error" id="scanErrorBox">
            <span class="shp-scan-error-icon">⚠️</span>
            <span id="scanErrorMsg">Scan gagal.</span>
        </div>
    </div>

    {{-- ═════════════════ STOCK ERROR ═════════════════ --}}
    @if ($hasStockError)
        <div class="shp-error-panel">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div class="shp-error-title">⚠️ Stok WH-RTS tidak mencukupi</div>
                <span class="shp-pill">Kurang: <b>{{ $stockInsufficient->count() }} barang</b></span>
            </div>
            <ul class="shp-error-list shp-error-copy mt-2">
                <li>Retur belum masuk WH-RTS, atau GRN belum diposting</li>
                <li>Qty yang discan melebihi stok yang tersedia</li>
                <li>Kurangi qty item yang bermasalah, lalu coba submit lagi</li>
            </ul>
            <div class="shp-stock-table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kode</th><th>Nama</th>
                            <th class="text-end">Stok RTS</th>
                            <th class="text-end">Butuh</th>
                            <th class="text-end">Kurang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stockInsufficient as $row)
                            <tr>
                                <td class="fw-bold font-monospace">{{ $row['code'] ?? '-' }}</td>
                                <td>{{ $row['name'] ?? '-' }}</td>
                                <td class="text-end">{{ number_format((int)($row['stock'] ?? 0), 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((int)($row['needed'] ?? 0), 0, ',', '.') }}</td>
                                <td class="text-end fw-bold text-danger">{{ number_format((int)($row['short'] ?? 0), 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ═════════════════ ITEMS TABLE CARD ═════════════════ --}}
    <div class="shp-table-card">

        {{-- head --}}
        <div class="shp-table-head">
            <span class="shp-table-title">Daftar Barang Keluar</span>
            <div class="ms-auto d-flex gap-2 align-items-center flex-wrap">
                <div class="input-group input-group-sm" style="width:210px">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="itemFilterInput" class="form-control"
                           placeholder="Filter kode / nama">
                </div>
                @if ($lastScannedLineId)
                    <button type="button" class="btn btn-sm btn-shp-outline" id="btnJumpLast">
                        ⤵ Terakhir
                    </button>
                @endif
            </div>
        </div>

        {{-- action strip --}}
        <div class="shp-table-actions">
            @if ($importPreview && is_array($importPreview) && count($importPreview))
                <button type="button" class="btn btn-sm btn-shp-outline"
                        data-bs-toggle="modal" data-bs-target="#importPreviewModal">
                    <i class="bi bi-eye me-1"></i>Preview Import
                </button>
            @endif

            <form id="importPreviewForm" method="POST"
                  action="{{ route('sales.shipments.import_preview', $shipment) }}"
                  enctype="multipart/form-data" class="d-inline">
                @csrf
                <input id="importFileInput" type="file" name="file" class="d-none"
                       accept=".csv,.txt,.xlsx,.xls" required>
                <button type="button" class="btn btn-sm btn-shp-outline"
                        onclick="document.getElementById('importFileInput').click()">
                    <i class="bi bi-upload me-1"></i>Import File
                </button>
                @error('file')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </form>

            @if ($shipment->lines->isNotEmpty())
                <a href="{{ route('sales.shipments.export_lines', $shipment) }}"
                   class="btn btn-sm btn-shp-outline">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
            @endif

            <span class="ms-auto"></span>

            @if ($shipment->lines->isNotEmpty())
                <form method="POST"
                      action="{{ route('sales.shipments.clear_lines', $shipment) }}"
                      onsubmit="return confirm('Bersihkan semua baris di shipment ini?')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash3 me-1"></i>Bersihkan Semua
                    </button>
                </form>
            @endif
        </div>

        {{-- table --}}
        <div class="lines-wrapper" id="linesWrapper">
            <table class="table align-middle shp-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th style="width:140px">Kode</th>
                        <th class="d-none d-md-table-cell">Nama Barang</th>
                        <th style="width:140px" class="text-end">Qty</th>
                        <th style="width:48px"></th>
                    </tr>
                </thead>
                <tbody id="linesTbody">
                    @forelse ($shipment->lines as $line)
                        <tr class="{{ $lastScannedLineId == $line->id ? 'last-scanned-row' : '' }}"
                            data-line-id="{{ $line->id }}">
                            <td class="text-muted small order-cell">{{ $loop->iteration }}</td>
                            <td><div class="item-code">{{ $line->item?->code ?? '-' }}</div></td>
                            <td class="d-none d-md-table-cell">
                                <div class="item-name">{{ $line->item?->name ?? '-' }}</div>
                                @if ($line->remarks)
                                    <div class="small text-muted">{{ $line->remarks }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <span class="qty-display" data-line-id="{{ $line->id }}"
                                      id="qty-display-{{ $line->id }}">
                                    {{ number_format($line->qty_scanned, 0, ',', '.') }}
                                </span>
                                <form action="{{ route('sales.shipments.update_line_qty', $line) }}"
                                      method="POST"
                                      class="d-inline qty-edit-form d-none"
                                      data-line-id="{{ $line->id }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" name="qty"
                                           class="form-control form-control-sm qty-edit-input"
                                           min="0" value="{{ $line->qty_scanned }}">
                                    <button type="submit"
                                            class="btn btn-primary btn-sm qty-edit-save-btn">✔</button>
                                </form>
                            </td>
                            <td class="text-end">
                                <form action="{{ route('sales.shipments.destroy_line', $line) }}"
                                      method="POST"
                                      class="d-inline js-delete-line-form"
                                      data-line-id="{{ $line->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-del" title="Hapus">🗑</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr class="no-lines-row">
                            <td colspan="5" class="text-center text-muted py-5">
                                Belum ada item yang discan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- footer --}}
        <div class="px-3 py-2 d-none d-md-flex justify-content-between align-items-center small text-muted"
             style="border-top:1px solid rgba(148,163,184,.1)">
            <span>Dibuat: {{ id_datetime($shipment->created_at) }}</span>
            <span>Diupdate: {{ id_datetime($shipment->updated_at) }}</span>
            <span>Total qty: <strong id="footerTotalQty">{{ number_format($totalQty, 0, ',', '.') }}</strong></span>
        </div>
    </div>
</div>

{{-- ═══════════════════════ IMPORT PREVIEW MODAL ═══════════════════════ --}}
@if ($importPreview && is_array($importPreview) && count($importPreview))
    <div class="modal fade" id="importPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview Import Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if ($importPreviewSummary)
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="shp-pill">OK: <b>{{ $importPreviewSummary['ok_count'] ?? 0 }}</b></span>
                            <span class="shp-pill">Dilewati: <b>{{ $importPreviewSummary['skip_count'] ?? 0 }}</b></span>
                            <span class="shp-pill">Total Qty OK: <b>{{ number_format($importPreviewSummary['total_qty_ok'] ?? 0, 0, ',', '.') }}</b></span>
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr class="small text-uppercase text-muted">
                                    <th style="width:40px">#</th>
                                    <th style="width:140px">Kode</th>
                                    <th style="width:110px">Qty File</th>
                                    <th style="width:110px">Qty Import</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($importPreview as $row)
                                    @php $isOk = ($row['status'] ?? null) === 'ok'; @endphp
                                    <tr class="small {{ $isOk ? '' : 'table-warning' }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $row['item_code'] ?? ($row['raw_product'] ?? '') }}</td>
                                        <td>{{ $row['raw_qty'] ?? '' }}</td>
                                        <td>{{ $isOk ? number_format($row['parsed_qty'] ?? 0, 0, ',', '.') : '-' }}</td>
                                        <td>
                                            @if ($isOk)
                                                <span class="text-success">OK</span>
                                            @else
                                                <span class="text-danger">{{ $row['error'] ?? 'Tidak bisa diimport.' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary"
                            data-bs-dismiss="modal">Tutup</button>
                    <form method="POST"
                          action="{{ route('sales.shipments.import_lines', $shipment) }}">
                        @csrf
                        @foreach ($importPreview as $i => $row)
                            @if (($row['status'] ?? null) === 'ok')
                                <input type="hidden" name="rows[{{ $i }}][product_code]"
                                       value="{{ $row['item_code'] }}">
                                <input type="hidden" name="rows[{{ $i }}][qty]"
                                       value="{{ (int)($row['parsed_qty'] ?? 0) }}">
                            @endif
                        @endforeach
                        <button type="submit" class="btn btn-sm btn-shp-submit page-theme-{{ $scanTheme }}">
                            Import ke Shipment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- TOAST --}}
<div id="scanToast" class="shp-toast"></div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    /* ── DOM refs ── */
    const scanInput         = document.getElementById('scanInput');
    const scanForm          = document.getElementById('scanForm');
    const linesWrapper      = document.getElementById('linesWrapper');
    const linesTbody        = document.getElementById('linesTbody');
    const itemFilterInput   = document.getElementById('itemFilterInput');
    const btnJumpLast       = document.getElementById('btnJumpLast');
    const toastEl           = document.getElementById('scanToast');
    const summaryLines      = document.getElementById('summaryTotalLines');
    const summaryQty        = document.getElementById('summaryTotalQty');
    const footerQty         = document.getElementById('footerTotalQty');
    const lastScanBox       = document.getElementById('lastScanBox');
    const lastScanCode      = document.getElementById('lastScanCode');
    const lastScanName      = document.getElementById('lastScanName');
    const lastScanQtyEl     = document.getElementById('lastScanQty');
    const scanErrorBox      = document.getElementById('scanErrorBox');
    const scanErrorMsg      = document.getElementById('scanErrorMsg');
    const sessionCounterEl  = document.getElementById('sessionCounter');
    const importPreviewForm = document.getElementById('importPreviewForm');
    const importFileInput   = document.getElementById('importFileInput');

    let lastScannedLineId = @json($lastScannedLineId);
    let sessionScanCount  = 0;

    const FMT = new Intl.NumberFormat('id-ID');

    /* ── delete url template ── */
    const deleteUrlTemplate = @json(parse_url(route('sales.shipments.destroy_line', ['line' => '__LINE_ID__']), PHP_URL_PATH));

    /* ── auto-submit import on file select ── */
    if (importPreviewForm && importFileInput) {
        importFileInput.addEventListener('change', function () {
            if (this.files && this.files.length > 0) importPreviewForm.submit();
        });
    }

    /* ── auto-hide flash ── */
    document.querySelectorAll('.js-auto-hide-alert').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .4s ease';
            el.style.opacity = '0';
            setTimeout(() => el.parentNode?.removeChild(el), 450);
        }, 3000);
    });

    /* ── audio beeps ── */
    function beep(freq, dur = 0.14, vol = 0.2) {
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            const ctx  = new Ctx();
            const osc  = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            osc.connect(gain);
            gain.connect(ctx.destination);
            gain.gain.setValueAtTime(vol, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + dur);
            osc.start(); osc.stop(ctx.currentTime + dur);
        } catch (e) {}
    }
    const beepOk  = () => beep(1046);
    const beepErr = () => beep(220, 0.18, 0.25);

    /* ── toast ── */
    let toastTimer = null;
    function showToast(type, msg) {
        if (!toastEl) return;
        clearTimeout(toastTimer);
        toastEl.className = 'shp-toast ' + (type === 'ok' ? 'shp-toast-ok' : 'shp-toast-err');
        toastEl.textContent = msg;
        toastEl.style.display = 'flex';
        toastEl.style.opacity = '1';
        toastTimer = setTimeout(() => {
            toastEl.style.transition = 'opacity .3s ease';
            toastEl.style.opacity = '0';
            setTimeout(() => { toastEl.style.display = 'none'; toastEl.style.transition = ''; }, 340);
        }, 1500);
    }

    /* ── last-scan ticker ── */
    function showLastScan(line) {
        if (!lastScanBox) return;
        if (scanErrorBox) scanErrorBox.style.display = 'none';
        lastScanCode.textContent = line.item_code || '—';
        lastScanName.textContent = line.item_name || '';
        lastScanQtyEl.textContent = FMT.format(line.qty_scanned || 0);
        lastScanBox.style.display = 'flex';
        /* re-trigger animation */
        lastScanBox.style.animation = 'none';
        void lastScanBox.offsetWidth;
        lastScanBox.style.animation = '';
    }
    function showScanError(msg) {
        if (!scanErrorBox) return;
        if (lastScanBox) lastScanBox.style.display = 'none';
        scanErrorMsg.textContent = msg;
        scanErrorBox.style.display = 'flex';
        setTimeout(() => { scanErrorBox.style.display = 'none'; }, 3500);
    }

    /* ── session counter ── */
    function bumpScanCount() {
        sessionScanCount++;
        if (sessionCounterEl) sessionCounterEl.textContent = sessionScanCount + ' scan sesi ini';
    }

    /* ── focus helpers ── */
    function focusScan() {
        if (scanInput) { scanInput.focus(); }
    }

    /* ── auto-refocus: any keydown on doc redirects to scan input ── */
    document.addEventListener('keydown', function (e) {
        /* skip if inside an input/select/textarea/contenteditable */
        const tag = (document.activeElement?.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'select' || tag === 'textarea' ||
            document.activeElement?.isContentEditable) return;
        /* skip modifier combos */
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        if (e.key.length !== 1) return;
        focusScan();
    });

    /* Ctrl+Enter to submit */
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            const submitForm = document.getElementById('submitForm');
            if (submitForm) submitForm.dispatchEvent(new Event('submit', { cancelable: true }));
        }
    });

    /* uppercase on input */
    if (scanInput) {
        scanInput.addEventListener('input', function () { this.value = this.value.toUpperCase(); });
    }

    /* ── totals UI update ── */
    function updateTotals(totals) {
        if (!totals) return;
        if (typeof totals.total_lines !== 'undefined' && summaryLines) {
            summaryLines.textContent = totals.total_lines;
        }
        if (typeof totals.total_qty !== 'undefined') {
            const f = FMT.format(totals.total_qty);
            if (summaryQty) summaryQty.textContent = f;
            if (footerQty)  footerQty.textContent  = f;
        }
    }

    /* ── scroll + highlight row ── */
    function scrollToRow(lineId, flash = true) {
        if (!linesWrapper) return;
        const row = linesWrapper.querySelector('tr[data-line-id="' + lineId + '"]');
        if (!row) return;
        linesWrapper.querySelectorAll('.last-scanned-row').forEach(r => r.classList.remove('last-scanned-row'));
        row.classList.add('last-scanned-row');
        if (flash) {
            row.classList.remove('row-flash');
            void row.offsetWidth;
            row.classList.add('row-flash');
        }
        const wRect  = linesWrapper.getBoundingClientRect();
        const rRect  = row.getBoundingClientRect();
        const offset = rRect.top - wRect.top;
        linesWrapper.scrollTo({ top: linesWrapper.scrollTop + offset - linesWrapper.clientHeight * 0.35, behavior: 'smooth' });
    }

    /* ── renumber rows ── */
    function renumberRows() {
        if (!linesTbody) return;
        let n = 1;
        linesTbody.querySelectorAll('tr[data-line-id]').forEach(r => {
            const c = r.querySelector('.order-cell');
            if (c) c.textContent = n++;
        });
    }

    /* ── bind qty click / edit ── */
    function bindQtyClick(row) {
        const lineId = row.getAttribute('data-line-id');
        const qtyEl  = row.querySelector('.qty-display');
        const form   = row.querySelector('.qty-edit-form[data-line-id="' + lineId + '"]');
        const input  = form ? form.querySelector('.qty-edit-input') : null;
        if (!qtyEl || !form || !input || qtyEl.dataset.boundClick === '1') return;

        qtyEl.addEventListener('click', () => {
            qtyEl.classList.add('d-none');
            form.classList.remove('d-none');
            input.focus(); input.select();
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const newQty = input.value.trim();
            if (newQty === '' || Number(newQty) < 0) { beepErr(); showToast('err', 'Qty tidak valid.'); return; }

            fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
            }).then(async res => {
                let data = null;
                try { data = await res.json(); } catch { form.submit(); return; }
                if (!res.ok || !data || data.status !== 'ok') {
                    beepErr(); showToast('err', data?.message || 'Gagal update qty.'); return;
                }
                beepOk();
                if (data.deleted) {
                    row.remove(); renumberRows();
                } else {
                    const val = typeof data.qty !== 'undefined' ? data.qty : Number(input.value);
                    qtyEl.textContent = FMT.format(val);
                    form.classList.add('d-none'); qtyEl.classList.remove('d-none');
                }
                updateTotals(data.totals);
                showToast('ok', data.message || 'Qty diperbarui.');
                focusScan();
            }).catch(() => form.submit());
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter')  { e.preventDefault(); form.dispatchEvent(new Event('submit', { cancelable: true })); }
            if (e.key === 'Escape') { e.preventDefault(); form.classList.add('d-none'); qtyEl.classList.remove('d-none'); focusScan(); }
        });

        qtyEl.dataset.boundClick = '1';
    }

    /* ── bind delete ── */
    function bindDelete(form) {
        if (!form || form.dataset.boundDelete === '1') return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!confirm('Hapus baris ini?')) return;
            const lineId = form.dataset.lineId;
            fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
            }).then(async res => {
                let data = null;
                try { data = await res.json(); } catch { form.submit(); return; }
                if (!res.ok || !data || data.status !== 'ok') {
                    beepErr(); showToast('err', data?.message || 'Gagal hapus.'); return;
                }
                beepOk();
                const row = linesTbody.querySelector('tr[data-line-id="' + lineId + '"]');
                if (row) row.remove();
                renumberRows();
                updateTotals(data.totals);
                showToast('ok', data.message || 'Baris dihapus.');
                focusScan();
            }).catch(() => form.submit());
        });
        form.dataset.boundDelete = '1';
    }

    /* ── initial bind ── */
    if (linesTbody) {
        linesTbody.querySelectorAll('tr[data-line-id]').forEach(r => bindQtyClick(r));
        linesTbody.querySelectorAll('.js-delete-line-form').forEach(f => bindDelete(f));
    }

    /* ── filter ── */
    if (itemFilterInput && linesWrapper) {
        itemFilterInput.addEventListener('input', function () {
            const term = this.value.toLowerCase().trim();
            linesWrapper.querySelectorAll('tbody tr[data-line-id]').forEach(row => {
                const code = (row.querySelector('.item-code')?.textContent || '').toLowerCase();
                const name = (row.querySelector('.item-name')?.textContent || '').toLowerCase();
                row.style.display = (!term || code.includes(term) || name.includes(term)) ? '' : 'none';
            });
        });
    }

    /* ── jump to last ── */
    if (btnJumpLast && linesWrapper) {
        btnJumpLast.addEventListener('click', () => {
            if (lastScannedLineId) scrollToRow(lastScannedLineId, true);
        });
    }

    /* ── submit confirm ── */
    window.confirmSubmit = function (e) {
        const lines = parseInt(summaryLines?.textContent || '0', 10);
        const qty   = summaryQty?.textContent || '0';
        if (!confirm(
            'Submit shipment ini?\n\n' +
            '• ' + lines + ' jenis barang\n' +
            '• ' + qty + ' total qty\n\n' +
            'Stok WH-RTS akan dikurangi. Tidak bisa di-scan lagi.'
        )) { e.preventDefault(); return false; }
        return true;
    };

    /* ── scan AJAX ── */
    if (scanForm && scanInput && linesTbody) {
        scanForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const code = scanInput.value.trim();
            if (!code) { beepErr(); showScanError('Kode kosong.'); focusScan(); return; }

            fetch(scanForm.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(scanForm),
            }).then(async res => {
                let data = null;
                try { data = await res.json(); } catch { scanForm.submit(); return; }

                scanInput.value = '';
                focusScan();

                if (!res.ok || !data || data.status !== 'ok') {
                    beepErr();
                    showScanError(data?.message || 'Scan gagal.');
                    showToast('err', data?.message || 'Scan gagal.');
                    return;
                }

                beepOk();
                bumpScanCount();
                const line   = data.line;
                const totals = data.totals || {};

                if (line && line.id) {
                    let row = linesTbody.querySelector('tr[data-line-id="' + line.id + '"]');

                    if (!row) {
                        const empty = linesTbody.querySelector('.no-lines-row');
                        if (empty) empty.remove();

                        const updUrl = line.update_qty_url
                            ? line.update_qty_url
                            : @json(parse_url(route('sales.shipments.update_line_qty', ['line' => '__LINE_ID__']), PHP_URL_PATH)).replace('__LINE_ID__', line.id);
                        const delUrl = deleteUrlTemplate.replace('__LINE_ID__', line.id);
                        const csrf   = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

                        row = document.createElement('tr');
                        row.setAttribute('data-line-id', line.id);
                        row.innerHTML =
                            '<td class="text-muted small order-cell"></td>' +
                            '<td><div class="item-code"></div></td>' +
                            '<td class="d-none d-md-table-cell">' +
                                '<div class="item-name"></div>' +
                                '<div class="small text-muted remarks-wrap d-none"></div>' +
                            '</td>' +
                            '<td class="text-end">' +
                                '<span class="qty-display" data-line-id="' + line.id + '" id="qty-display-' + line.id + '"></span>' +
                                '<form action="' + updUrl + '" method="POST" class="d-inline qty-edit-form d-none" data-line-id="' + line.id + '">' +
                                    '<input type="hidden" name="_token" value="' + csrf + '">' +
                                    '<input type="hidden" name="_method" value="PATCH">' +
                                    '<input type="number" name="qty" class="form-control form-control-sm qty-edit-input" min="0" value="' + (line.qty_scanned || 0) + '">' +
                                    '<button type="submit" class="btn btn-primary btn-sm qty-edit-save-btn">✔</button>' +
                                '</form>' +
                            '</td>' +
                            '<td class="text-end">' +
                                '<form action="' + delUrl + '" method="POST" class="d-inline js-delete-line-form" data-line-id="' + line.id + '">' +
                                    '<input type="hidden" name="_token" value="' + csrf + '">' +
                                    '<input type="hidden" name="_method" value="DELETE">' +
                                    '<button type="submit" class="btn-del" title="Hapus">🗑</button>' +
                                '</form>' +
                            '</td>';
                        linesTbody.appendChild(row);
                    }

                    row.querySelector('.item-code').textContent = line.item_code || '-';
                    const nameEl = row.querySelector('.item-name');
                    if (nameEl) nameEl.textContent = line.item_name || '-';
                    const remarksWrap = row.querySelector('.remarks-wrap');
                    if (remarksWrap) {
                        if (line.remarks) { remarksWrap.textContent = 'Catatan: ' + line.remarks; remarksWrap.classList.remove('d-none'); }
                        else remarksWrap.classList.add('d-none');
                    }
                    const qtyEl = row.querySelector('#qty-display-' + line.id);
                    if (qtyEl) qtyEl.textContent = FMT.format(line.qty_scanned || 0);

                    bindQtyClick(row);
                    bindDelete(row.querySelector('.js-delete-line-form'));

                    renumberRows();
                    updateTotals(totals);

                    lastScannedLineId = line.id;
                    scrollToRow(line.id, true);
                    showLastScan(line);
                    showToast('ok', data.message || ('+1 ' + (line.item_code || 'item')));
                } else {
                    showToast('ok', data.message || 'Berhasil scan.');
                }
            }).catch(() => scanForm.submit());
        });
    }

    /* ── on load ── */
    window.addEventListener('load', function () {
        focusScan();
        if (lastScannedLineId) scrollToRow(lastScannedLineId, false);

        const shouldShowPreview = {{ request()->boolean('show_preview', false) ? 'true' : 'false' }};
        const previewModalEl = document.getElementById('importPreviewModal');
        if (previewModalEl && shouldShowPreview && window.bootstrap?.Modal) {
            new bootstrap.Modal(previewModalEl).show();
        }
    });
})();
</script>
@endpush

@if (session('stock_insufficient'))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.Swal) return;
    const items = @json(session('stock_insufficient'));
    const rows = items.map(r => `
        <tr>
            <td style="padding:6px 8px;font-weight:700;font-family:monospace;font-size:.8rem;white-space:nowrap">${r.code}</td>
            <td style="padding:6px 8px;font-size:.8rem;text-align:left">${r.name}</td>
            <td style="padding:6px 8px;text-align:right;font-size:.8rem;color:#dc2626">${r.stock.toLocaleString('id')}</td>
            <td style="padding:6px 8px;text-align:right;font-size:.8rem">${r.needed.toLocaleString('id')}</td>
            <td style="padding:6px 8px;text-align:right;font-size:.8rem;font-weight:700;color:#dc2626">-${r.short.toLocaleString('id')}</td>
        </tr>`).join('');
    Swal.fire({
        icon: 'error',
        title: 'Barang Belum Siap Dikirim',
        html: `
            <p style="margin-bottom:8px;font-size:.85rem;font-weight:700">Shipment ditolak — stok WH-RTS tidak mencukupi.</p>
            <ul style="text-align:left;font-size:.8rem;color:#475569;margin:0 0 12px;padding-left:18px;line-height:1.9">
                <li>Ada <strong>return barang</strong> yang belum masuk WH-RTS</li>
                <li>Barang masih proses <strong>produksi</strong></li>
                <li><strong>PO pembelian</strong> belum dibuat / belum di-approve</li>
                <li>GRN belum diposting</li>
            </ul>
            <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#fef2f2;border-bottom:2px solid #fecaca">
                        <th style="padding:6px 8px;text-align:left;font-size:.72rem;color:#64748b;text-transform:uppercase">Kode</th>
                        <th style="padding:6px 8px;text-align:left;font-size:.72rem;color:#64748b;text-transform:uppercase">Item</th>
                        <th style="padding:6px 8px;text-align:right;font-size:.72rem;color:#64748b;text-transform:uppercase">Stok</th>
                        <th style="padding:6px 8px;text-align:right;font-size:.72rem;color:#64748b;text-transform:uppercase">Butuh</th>
                        <th style="padding:6px 8px;text-align:right;font-size:.72rem;color:#dc2626;text-transform:uppercase">Kurang</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
            </div>`,
        confirmButtonText: 'Mengerti',
        confirmButtonColor: '#dc2626',
        width: 600,
    });
});
</script>
@endpush
@endif
