{{-- resources/views/master/items/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Master Item')

@php
    $fmt = fn($value) => number_format((float) $value, 0, ',', '.');
    $stats = $itemStats ?? [
        'total' => $items->total(),
        'active' => $items->count(),
        'can_buy' => 0,
        'can_make' => 0,
        'hybrid' => 0,
        'missing_hpp' => 0,
    ];
    $hasFilters = request()->hasAny(['q', 'type', 'category_kind', 'item_category_id', 'supply_mode']);
    $user = auth()->user();
    $isAdminRole = $user && strtolower((string) $user->role) === 'admin' && ! $user->isDeveloper();
    $canManageMasterData = ! $isAdminRole && ($user?->canAccessModule('master') ?? false);
    $supplyModes = [
        'buy' => 'Beli jadi saja',
        'make' => 'Produksi sendiri saja',
        'hybrid' => 'Hybrid: beli / produksi',
        'undefined' => 'Belum ditentukan',
    ];
@endphp

@push('head')
<style>
    .item-page { max-width: 1280px; margin: 0 auto; padding: .75rem .75rem 4rem; color: #0f172a; }
    .item-hero, .item-card { border: 1px solid rgba(148,163,184,.18); border-radius: 8px; background: #fff; box-shadow: none; }
    .item-hero { display:flex; justify-content:space-between; align-items:center; gap:.6rem; padding:.45rem .75rem; margin-inline:-.75rem; margin-bottom:.65rem; border-width:0 0 1px; }
    .item-hero-main { display:flex; align-items:center; gap:13px; min-width:0; }
    .item-icon { display:none; }
    .item-eyebrow { display:block; color:#64748b; font-size:.66rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; margin-bottom:1px; }
    .item-title { margin:0; font-size:1rem; line-height:1.2; letter-spacing:0; font-weight:750; }
    .item-subtitle { margin-top:1px; color:#64748b; font-size:.78rem; font-weight:500; }
    .item-actions, .item-row-actions { display:flex; flex-wrap:wrap; gap:7px; align-items:center; justify-content:flex-end; }
    .item-actions .btn, .item-row-actions .btn, .item-card .btn { border-radius:7px; font-weight:600; display:inline-flex; align-items:center; gap:6px; }
    .item-primary { color:#fff!important; background:linear-gradient(135deg,#0f172a,#334155)!important; border-color:transparent!important; }
    .item-primary .action-label,
    .item-primary:hover .action-label,
    .item-primary:focus .action-label,
    .item-primary:active .action-label { color:#fff!important; }
    .item-soft { color:#334155!important; background:#fff!important; border:1px solid #cbd5e1!important; }
    .item-soft:hover { background:#f8fafc!important; }
    .item-kpis { display:flex; flex-wrap:wrap; gap:.32rem; margin-top:.35rem; margin-bottom:0; }
    .item-kpi { display:inline-flex; align-items:baseline; gap:.45rem; min-width:0; padding:.2rem .48rem; border:1px solid rgba(148,163,184,.28); border-radius:7px; background:transparent; }
    .item-kpi-label { color:#94a3b8; font-size:.66rem; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .item-kpi-value { margin-top:0; color:#334155; font-size:.72rem; font-weight:650; letter-spacing:0; }
    .item-kpi-note { display:none; }
    .item-card { overflow:hidden; }
    .item-filter { display:grid; grid-template-columns:minmax(220px,1.5fr) repeat(4,minmax(130px,1fr)) auto; gap:9px; align-items:end; padding:14px; border-bottom:1px solid #eef2f7; }
    .item-label { display:block; margin-bottom:5px; color:#475569; font-size:.68rem; font-weight:850; text-transform:uppercase; letter-spacing:.04em; }
    .item-filter .form-control, .item-filter .form-select { min-height:38px; border-radius:12px; border-color:#e2e8f0; font-size:.8rem; font-weight:600; box-shadow:none; }
    .item-filter .form-control:focus, .item-filter .form-select:focus { border-color:#94a3b8; box-shadow:0 0 0 .2rem rgba(15,23,42,.07); }
    .item-filter-actions { display:flex; gap:7px; align-items:center; }
    .item-filter-actions .btn { min-height:38px; }
    .item-bulk { display:flex; align-items:center; gap:9px; flex-wrap:wrap; padding:10px 14px; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
    .item-bulk strong { font-size:.8rem; }
    .item-table-wrap { overflow-x:auto; overflow-y:auto; max-height:calc(100vh - 210px); }
    .item-table-wrap::-webkit-scrollbar { width:6px; height:6px; }
    .item-table-wrap::-webkit-scrollbar-track { background:transparent; }
    .item-table-wrap::-webkit-scrollbar-thumb { background:rgba(148,163,184,.3); border-radius:4px; }
    .item-table { min-width:980px; margin:0; font-size:.78rem; }
    .item-table thead th { position:sticky; top:0; z-index:10; padding:.75rem 1rem; background:#fff; color:#64748b; border-bottom:0; font-size:.64rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
    .item-table thead th::after { content:''; position:absolute; left:0; bottom:0; width:100%; border-bottom:1px solid rgba(148,163,184,.25); }
    .item-table tbody td { padding:.55rem .85rem; border-top:1px solid rgba(148,163,184,.12); border-bottom:0; vertical-align:middle; font-size:.78rem; }
    .item-table tbody tr:hover { background:#f8fafc; }
    .item-code { display:inline-flex; border-radius:6px; padding:.15rem .45rem; background:rgba(148,163,184,.12); border:1px solid rgba(148,163,184,.25); color:#334155; font-size:.68rem; font-weight:650; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; white-space:nowrap; }
    .item-name { color:#0f172a; font-weight:650; letter-spacing:0; }
    .item-muted { color:#64748b; font-size:.74rem; }
    .item-meta { color:#64748b; font-size:.7rem; margin-top:2px; }
    .item-bom-badge { display:inline-flex; align-items:center; gap:4px; margin-top:4px; padding:2px 6px; border-radius:999px; color:#047857; background:#ecfdf5; border:1px solid #a7f3d0; font-size:.64rem; font-weight:800; }
    .item-type { display:inline-flex; border-radius:7px; padding:.16rem .48rem; color:#475569; background:rgba(148,163,184,.1); border:1px solid rgba(148,163,184,.3); font-size:.68rem; font-weight:650; }
    .item-status { display:inline-flex; align-items:center; gap:.35rem; border-radius:7px; padding:.16rem .48rem; font-size:.68rem; font-weight:650; white-space:nowrap; }
    .item-status:before { content:''; width:7px; height:7px; border-radius:50%; background:currentColor; flex:0 0 7px; }
    .item-status-active { color:#166534; background:#f0fdf4; border:1px solid #bbf7d0; }
    .item-status-off { color:#991b1b; background:#fef2f2; border:1px solid #fecaca; }
    .item-status-buy { color:#1d4ed8; background:#eff6ff; border:1px solid #bfdbfe; }
    .item-status-make { color:#047857; background:#ecfdf5; border:1px solid #a7f3d0; }
    .item-status-hybrid { color:#7c3aed; background:#f5f3ff; border:1px solid #ddd6fe; }
    .item-status-undefined { color:#92400e; background:#fffbeb; border:1px solid #fde68a; }
    .item-footer { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; padding:13px 14px; color:#64748b; font-size:.75rem; font-weight:650; }
    .item-footer .pagination { margin:0; }
    .item-footer .page-link { border-radius:9px; color:#475569; border-color:#e2e8f0; font-size:.75rem; }
    .item-footer .page-item.active .page-link { color:#fff; background:#0f172a; border-color:#0f172a; }
    .item-empty { padding:44px 18px; text-align:center; color:#64748b; }
    .item-empty-icon { width:50px; height:50px; margin:0 auto 10px; display:flex; align-items:center; justify-content:center; border-radius:16px; color:#64748b; background:#f1f5f9; font-size:1.25rem; }
    .modal-content { border:0; border-radius:18px; box-shadow:0 24px 60px rgba(15,23,42,.2); }
    .modal-header, .modal-footer { border-color:#eef2f7; }
    .modal-title { font-weight:850; font-size:.95rem; }
    .modal .form-control, .modal .form-select { border-radius:11px; font-size:.82rem; }
    .item-mobile-list { display:none; }
    .item-mobile-card { padding:.66rem; border-bottom:1px solid rgba(148,163,184,.16); }
    .item-mobile-card:last-child { border-bottom:0; }
    .item-mobile-card-head, .item-mobile-card-footer { display:flex; align-items:center; justify-content:space-between; gap:9px; }
    .item-mobile-card-head { align-items:flex-start; }
    .item-mobile-card-title { min-width:0; }
    .item-mobile-card-title .item-name { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .item-mobile-card-meta { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.45rem; margin-top:.55rem; }
    .item-mobile-card-meta > div { min-width:0; }
    .item-mobile-card-label { color:#64748b; font-size:.64rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
    .item-mobile-card-value { margin-top:2px; color:#334155; font-size:.74rem; font-weight:650; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .item-mobile-card-footer { margin-top:.55rem; }
    .item-mobile-card-actions { display:flex; flex:1 1 auto; gap:6px; }
    .item-mobile-card-actions .btn { flex:1 1 0; justify-content:center; }
    body[data-theme="dark"] .item-mobile-card { border-color:#334155; }
    body[data-theme="dark"] .item-mobile-card-value { color:#e2e8f0; }
    body[data-theme="dark"] .item-page { color:#e5e7eb; }
    body[data-theme="dark"] .item-hero, body[data-theme="dark"] .item-card, body[data-theme="dark"] .item-kpi { background:#0f172a; border-color:#334155; }
    body[data-theme="dark"] .item-eyebrow, body[data-theme="dark"] .item-bulk, body[data-theme="dark"] .item-table thead th { background:#111827; border-color:#334155; }
    body[data-theme="dark"] .item-title, body[data-theme="dark"] .item-name, body[data-theme="dark"] .item-kpi-value { color:#f8fafc; }
    body[data-theme="dark"] .item-subtitle, body[data-theme="dark"] .item-muted, body[data-theme="dark"] .item-label, body[data-theme="dark"] .item-kpi-label, body[data-theme="dark"] .item-footer { color:#94a3b8; }
    body[data-theme="dark"] .item-table tbody td { border-top-color:#334155; }
    body[data-theme="dark"] .item-table tbody tr:hover { background:#111827; }
    body[data-theme="dark"] .item-code, body[data-theme="dark"] .item-type { background:rgba(148,163,184,.12); border-color:rgba(148,163,184,.35); color:#cbd5e1; }
    body[data-theme="dark"] .item-bom-badge { color:#a7f3d0; background:#064e3b; border-color:#047857; }
    body[data-theme="dark"] .item-filter .form-control, body[data-theme="dark"] .item-filter .form-select, body[data-theme="dark"] .modal-content { background:#0f172a; border-color:#475569; color:#f8fafc; }
    @media (max-width:1100px) { .item-filter { grid-template-columns:repeat(3,minmax(0,1fr)); } .item-filter-search { grid-column:1 / -1; } }
    @media (max-width:700px) {
        .item-page { padding: .5rem .5rem 4rem; }
        .item-hero { align-items:flex-start; flex-direction:column; margin-inline:-.5rem; padding:.5rem .65rem; }
        .item-title { font-size:1.05rem; }
        .item-subtitle { font-size:.74rem; }
        .item-actions, .item-actions .btn { width:100%; }
        .item-actions { display:grid; grid-template-columns:1fr 1fr; }
        .item-actions .btn { justify-content:center; }
        .item-kpis { width:100%; gap:.25rem; }
        .item-kpi { flex:1 1 auto; justify-content:space-between; }
        .item-kpi:nth-child(n+5) { display:none; }
        .item-filter { grid-template-columns:1fr; padding:11px; }
        .item-filter-search { grid-column:auto; }
        .item-filter-actions, .item-filter-actions .btn { width:100%; }
        .item-filter-actions { display:grid; grid-template-columns:1fr auto; }
        .item-filter-actions .btn { justify-content:center; }
        .item-table-wrap { display:none; }
        .item-mobile-list { display:block; }
        .item-footer { align-items:flex-start; padding:11px; }
        .item-footer .pagination { width:100%; overflow:auto; padding-bottom:2px; }
        .item-footer .pagination .page-link { white-space:nowrap; }
        .item-bulk { padding:9px 11px; }
        .item-bulk .item-muted { display:none; }
        .item-bulk .btn { flex:1 1 auto; }
        .item-bulk #bulkClear { flex:0 0 auto; }
    }

    /* Final desktop pass: keep the page readable inside the application shell. */
    .item-page { max-width: 1280px; width: 100%; margin-inline: auto; padding: 1rem 1.1rem 4rem; }
    .item-hero {
        align-items: center;
        flex-wrap: nowrap;
        gap: 1rem;
        margin: 0 0 1rem;
        padding: 1.1rem 1.2rem;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 10px 28px rgba(15,23,42,.05);
    }
    .item-hero-main { align-items: flex-start; gap: 12px; flex: 1 1 280px; min-width: 220px; }
    .item-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 40px;
        width: 40px;
        height: 40px;
        margin-top: 2px;
        border-radius: 13px;
        color: #111827;
        background: #f3f4f6;
        font-size: 1.1rem;
    }
    .item-eyebrow { color: #111827; font-size: .65rem; letter-spacing: .09em; }
    .item-title { font-size: 1.3rem; letter-spacing: -.03em; }
    .item-subtitle { max-width: 360px; margin-top: 3px; font-size: .78rem; }
    .item-actions { flex: 0 0 auto; align-self: center; padding-top: 0; }
    .item-actions .btn { min-height: 38px; padding-inline: .85rem; border-radius: 10px; }
    .item-primary { background: linear-gradient(135deg, #111827, #000000) !important; box-shadow: 0 6px 14px rgba(15,23,42,.18); }
    .item-soft { border-color: #dbe3ee !important; }
    .item-row-actions .item-soft,
    .item-mobile-card-actions .item-soft { color: #fff !important; background: #111827 !important; border-color: #111827 !important; }
    .item-row-actions .item-soft:hover,
    .item-row-actions .item-soft:focus,
    .item-mobile-card-actions .item-soft:hover,
    .item-mobile-card-actions .item-soft:focus { color: #fff !important; background: #000 !important; border-color: #000 !important; }
    .item-kpis { display: flex; flex: 0 0 auto; align-items: stretch; gap: .45rem; margin-top: 0; }
    .item-kpi { display: block; padding: .55rem .65rem; border-radius: 11px; background: rgba(255,255,255,.78); border-color: #e2e8f0; }
    .item-kpi-label { display: block; color: #64748b; font-size: .62rem; line-height: 1.2; }
    .item-kpi-value { display: block; margin-top: .2rem; color: #0f172a; font-size: .95rem; font-weight: 800; }
    .item-card { border-radius: 18px; border-color: #e5e7eb; box-shadow: 0 10px 28px rgba(15,23,42,.045); }
    .item-filter { grid-template-columns: minmax(220px, 1.45fr) repeat(4, minmax(118px, 1fr)) auto; gap: 10px; padding: 13px 15px; background: #fbfdff; }
    .item-filter-search {
        position: relative;
        grid-column: span 2;
        padding: 0;
    }
    .item-filter-search .item-label { color: #111827; margin-bottom: 6px; line-height: 1; }
    .item-filter-search::before { content: '\F52A'; position: absolute; z-index: 2; left: 13px; bottom: 12px; color: #111827; font-family: 'bootstrap-icons'; font-size: .9rem; }
    .item-label { height: 12px; margin-bottom: 6px; color: #64748b; font-size: .64rem; line-height: 1; letter-spacing: .08em; }
    .item-filter .form-control, .item-filter .form-select { height: 40px; min-height: 40px; border-radius: 10px; background-color: #fff; font-size: .78rem; line-height: 1.2; }
    .item-filter-search .form-control { padding-left: 36px; border: 1.5px solid #111827; border-radius: 10px; font-size: .84rem; font-weight: 650; box-shadow: 0 0 0 2px rgba(15,23,42,.04); }
    .item-filter-search .form-control:focus { border-color: #111827; box-shadow: 0 0 0 3px rgba(15,23,42,.09); }
    .item-filter .form-control::placeholder { color: #94a3b8; }
    .item-filter > div:not(.item-filter-search), .item-filter-actions { min-width: 0; align-self: end; }
    .item-filter-actions .btn { height: 40px; min-height: 40px; border-radius: 10px; }
    .item-filter-actions .btn[type="submit"] { min-width: 40px; }
    .item-filter.is-loading { opacity: .78; }
    .item-filter.is-loading .item-filter-actions .btn[type="submit"] { pointer-events: none; }
    .item-results { transition: opacity .16s ease; }
    .item-results.is-loading { opacity: .42; pointer-events: none; }
    .item-overview { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; padding: .65rem .9rem; border-bottom: 1px solid #eef2f7; background: #fff; }
    .item-overview-label { margin-right: .25rem; color: #64748b; font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; }
    .item-overview-stat { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .55rem; border-radius: 999px; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; font-size: .7rem; font-weight: 700; }
    .item-overview-stat strong { color: #0f172a; font-size: .78rem; }
    .item-bulk { padding: 10px 14px; background: #f8fbff; border-bottom-color: #e5e7eb; }
    .item-bulk .btn { border-radius: 9px; }
    .item-table-wrap { max-height: calc(100vh - 292px); }
    .item-table { min-width: 1020px; font-size: .76rem; }
    .item-page-admin .item-table { min-width: 0; }
    .item-page-admin .item-table th:last-child,
    .item-page-admin .item-table td:last-child { min-width: 88px; }
    .item-page-admin .item-mobile-card-meta { grid-template-columns: 1fr; }
    .item-table thead th { padding: .7rem .7rem; background: #fff; font-size: .61rem; vertical-align: middle; }
    .item-table tbody td { padding: .72rem .7rem; font-size: .75rem; }
    .item-table th:last-child, .item-table td:last-child { position: sticky; right: 0; z-index: 3; background: #fff; box-shadow: -8px 0 16px rgba(15,23,42,.06); }
    .item-table thead th:last-child { z-index: 11; }
    .item-table tbody tr:hover td:last-child { background: #f8fafc; }
    .item-row-actions { gap: 5px; flex-wrap: nowrap; }
    .item-row-actions .btn { min-width: 32px; min-height: 32px; padding: .3rem .48rem; border-radius: 9px; }
    .item-row-actions .dropdown-menu { min-width: 160px; }
    .item-footer { padding: 12px 14px; }
    @media (max-width:1399.98px) and (min-width:701px) { .item-row-actions .action-label { display: none !important; } }
    @media (max-width:1100px) and (min-width:901px) {
        .item-kpis { display: grid; grid-template-columns: repeat(2, minmax(100px, 1fr)); width: 100%; }
        .item-hero { flex-direction: column; align-items: stretch; }
        .item-hero-main { flex-basis: auto; }
        .item-actions { width: 100%; justify-content: flex-end; }
        .item-filter { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .item-filter-search { grid-column: 1 / -1; }
    }
    @media (max-width:900px) and (min-width:701px) {
        .item-kpis { display: grid; grid-template-columns: repeat(2, minmax(100px, 1fr)); width: 100%; }
        .item-hero { flex-direction: column; align-items: stretch; }
        .item-hero-main { flex-basis: auto; }
        .item-actions { width: 100%; justify-content: flex-end; }
        .item-filter { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .item-filter-search, .item-filter-actions { grid-column: 1 / -1; }
    }
    @media (max-width:700px) {
        .item-page { padding: .65rem .55rem 4rem; }
        .item-hero { padding: .9rem; border-radius: 15px; }
        .item-icon { flex-basis: 34px; width: 34px; height: 34px; border-radius: 10px; }
        .item-title { font-size: 1.12rem; }
        .item-kpis { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: .7rem; }
        .item-kpi-value { font-size: .88rem; }
        .item-filter { grid-template-columns: 1fr; }
        .item-filter-search, .item-filter-actions { grid-column: auto; }
        .item-overview { overflow-x: auto; flex-wrap: nowrap; scrollbar-width: none; }
        .item-overview::-webkit-scrollbar { display: none; }
        .item-overview-label { flex: 0 0 auto; }
        .item-overview-stat { flex: 0 0 auto; }
        .item-table-wrap { max-height: none; }
    }
    body[data-theme="dark"] .item-hero { background: linear-gradient(135deg, #0f172a 0%, #111827 100%); border-color: #334155; }
    body[data-theme="dark"] .item-icon { color: #f8fafc; background: #1f2937; }
    body[data-theme="dark"] .item-kpi, body[data-theme="dark"] .item-overview-stat { background: rgba(15,23,42,.72); border-color: #334155; }
    body[data-theme="dark"] .item-kpi-value, body[data-theme="dark"] .item-overview-stat strong { color: #f8fafc; }
    body[data-theme="dark"] .item-filter, body[data-theme="dark"] .item-overview { background: #0f172a; border-color: #334155; }
    body[data-theme="dark"] .item-filter-search .item-label, body[data-theme="dark"] .item-filter-search::before { color: #e2e8f0; }
    body[data-theme="dark"] .item-filter-search .form-control { background: #0f172a; border-color: #cbd5e1; color: #f8fafc; box-shadow: none; }
    body[data-theme="dark"] .item-filter .form-control, body[data-theme="dark"] .item-filter .form-select, body[data-theme="dark"] .item-table th:last-child, body[data-theme="dark"] .item-table td:last-child { background: #0f172a; }
    body[data-theme="dark"] .item-table tbody tr:hover td:last-child { background: #111827; }
</style>
@endpush

@section('content')
<div class="item-page {{ $isAdminRole ? 'item-page-admin' : '' }}">
    <div class="item-hero">
        <div class="item-hero-main">
            <div class="item-icon"><i class="bi bi-box-seam"></i></div>
            <div>
                <div class="item-eyebrow"><i class="bi bi-database-check"></i> Master Data</div>
                <h1 class="item-title">Master Item</h1>
            </div>
        </div>
        @unless($isAdminRole)
            <div class="item-kpis" aria-label="Ringkasan item">
                <div class="item-kpi"><div class="item-kpi-label">Total Item</div><div class="item-kpi-value">{{ $fmt($stats['total']) }}</div></div>
                <div class="item-kpi"><div class="item-kpi-label">Aktif</div><div class="item-kpi-value">{{ $fmt($stats['active']) }}</div></div>
                <div class="item-kpi"><div class="item-kpi-label">HPP Kosong</div><div class="item-kpi-value">{{ $fmt($stats['missing_hpp']) }}</div></div>
                <div class="item-kpi"><div class="item-kpi-label">Perlu Ditinjau</div><div class="item-kpi-value">{{ $fmt($supplySummary['review'] ?? 0) }}</div></div>
            </div>
            <div class="item-actions">
                @if($canManageMasterData)
                    <a href="{{ route('master.item_categories.index') }}" class="btn item-soft btn-sm"><i class="bi bi-tags"></i>Kategori</a>
                @endif
                <a href="{{ route('master.items.create') }}" class="btn item-primary btn-sm"><i class="bi bi-plus-lg"></i>Tambah Item</a>
            </div>
        @endunless
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2 px-3 mb-3" style="font-size:.8rem;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.8rem;">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.8rem;">{{ $errors->first() }}</div>
    @endif

    <div class="item-card">
        <form method="GET" action="{{ route('master.items.index') }}" class="item-filter">
            <div class="item-filter-search">
                <label class="item-label" for="item-search">Cari item</label>
                <input id="item-search" type="search" name="q" value="{{ request('q') }}" class="form-control" autocomplete="off" placeholder="Kode, nama, atau SKU">
            </div>
            <div>
                <label class="item-label" for="item-type">Tipe</label>
                <select id="item-type" name="type" class="form-select">
                    <option value="">Semua tipe</option>
                    @foreach($typeLabels as $key => $label)
                        <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="item-label" for="item-category-kind">Kelompok</label>
                <select id="item-category-kind" name="category_kind" class="form-select">
                    <option value="">Semua kelompok</option>
                    @foreach($categoryKinds as $key => $label)
                        <option value="{{ $key }}" @selected(request('category_kind') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="item-label" for="item-category">Kategori</label>
                <select id="item-category" name="item_category_id" class="form-select">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('item_category_id') === (string) $category->id)>{{ $category->code }} — {{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="item-label" for="item-supply-mode">Metode pasok</label>
                <select id="item-supply-mode" name="supply_mode" class="form-select">
                    <option value="">Semua metode</option>
                    @foreach($supplyModes as $key => $label)
                        <option value="{{ $key }}" @selected(request('supply_mode') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="item-filter-actions">
                <a href="{{ route('master.items.index') }}" class="btn item-soft btn-sm item-reset-filter {{ $hasFilters ? '' : 'd-none' }}" title="Hapus semua filter" aria-label="Reset filter"><i class="bi bi-x-lg"></i><span class="d-none d-xl-inline">Reset</span></a>
            </div>
        </form>
        <div id="itemResults" class="item-results" aria-live="polite">
            @if(! $isAdminRole && ($supplySummary['total'] ?? 0) > 0)
                <div class="item-overview" aria-label="Ringkasan metode pasok">
                    <span class="item-overview-label"><i class="bi bi-bar-chart-line me-1"></i>Ringkasan pasok</span>
                    <span class="item-overview-stat"><strong>{{ $fmt($supplySummary['hybrid'] ?? 0) }}</strong> Hybrid</span>
                    <span class="item-overview-stat"><strong>{{ $fmt($supplySummary['make_only'] ?? 0) }}</strong> Produksi</span>
                    <span class="item-overview-stat"><strong>{{ $fmt($supplySummary['buy_only'] ?? 0) }}</strong> Beli</span>
                    <span class="item-overview-stat"><strong>{{ $fmt($supplySummary['review'] ?? 0) }}</strong> Perlu ditinjau</span>
                </div>
            @endif
        @unless($isAdminRole)
            <div id="bulkToolbar" class="item-bulk d-none">
                <strong><span id="selectedCount">0</span> item dipilih</strong>
                <span class="item-muted">Aksi massal:</span>
                <button type="button" class="btn item-soft btn-sm" data-bs-toggle="modal" data-bs-target="#bulkCategoryModal"><i class="bi bi-tags"></i>Kategori</button>
                <button type="button" class="btn item-soft btn-sm" data-bs-toggle="modal" data-bs-target="#bulkTypeModal"><i class="bi bi-diagram-3"></i>Tipe</button>
                <button type="button" class="btn item-soft btn-sm" data-bs-toggle="modal" data-bs-target="#bulkHppModal"><i class="bi bi-cash-stack"></i>Set HPP</button>
                <button type="button" class="btn btn-link btn-sm text-muted ms-auto" id="bulkClear">Batal</button>
            </div>
        @endunless

        @if($items->count())
            <div class="item-table-wrap">
                <table class="table table-hover align-middle item-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:52px;">No.</th>
                            @unless($isAdminRole)
                                <th class="text-center" style="width:38px;"><input type="checkbox" class="form-check-input" id="checkAll" aria-label="Pilih semua"></th>
                            @endunless
                            <th style="min-width:220px;">Item</th>
                            @if($isAdminRole)
                                <th style="min-width:155px;">Klasifikasi</th>
                            @endif
                            @unless($isAdminRole)
                                <th style="min-width:155px;">Klasifikasi</th>
                                <th style="min-width:165px;">Metode Pasok</th>
                                <th class="text-end" style="min-width:110px;">HPP</th>
                            @endunless
                            <th class="text-end" style="min-width:125px;">Harga Terakhir</th>
                            @unless($isAdminRole)
                                <th style="width:88px;">Status</th>
                            @endunless
                            <th class="text-end" style="width:132px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            @php
                                $hpp = (float) ($item->active_unit_cost ?? $item->effective_unit_cost ?? $item->hpp ?? 0);
                                $lastPurchasePrice = (float) ($item->last_purchase_price ?? 0);
                                $modeClass = $item->isHybrid() ? 'item-status-hybrid' : ($item->canMake() ? 'item-status-make' : ($item->canBuy() ? 'item-status-buy' : 'item-status-undefined'));
                            @endphp
                            <tr>
                                <td class="text-center text-muted fw-semibold">{{ $items->firstItem() + $loop->index }}</td>
                                @unless($isAdminRole)
                                    <td class="text-center"><input type="checkbox" class="form-check-input row-check" value="{{ $item->id }}" aria-label="Pilih {{ $item->code }}"></td>
                                @endunless
                                <td>
                                    <div><a class="item-code text-decoration-none" href="{{ route('master.items.show', $item) }}">{{ $item->code }}</a></div>
                                    <div class="item-name mt-1">{{ $item->name }}</div>
                                </td>
                                @if($isAdminRole)
                                    <td>
                                        <span class="item-type">{{ $typeLabels[$item->type] ?? $item->type }}</span>
                                        @if($item->category?->code)
                                            <div class="item-meta">{{ $item->category->code }}</div>
                                        @endif
                                    </td>
                                @endif
                                @unless($isAdminRole)
                                    <td>
                                        <span class="item-type">{{ $typeLabels[$item->type] ?? $item->type }}</span>
                                        @if($item->category?->code)
                                            <div class="item-meta">{{ $item->category->code }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if(in_array($item->type, ['finished_good', 'wip'], true))
                                            <span class="item-status {{ $modeClass }}">{{ $item->supply_mode_label }}</span>
                                        @else
                                            <span class="item-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="item-name">{{ $hpp > 0 ? 'Rp '.$fmt($hpp) : '—' }}</div>
                                    </td>
                                @endunless
                                <td class="text-end">
                                    <div class="item-name">{{ $lastPurchasePrice > 0 ? 'Rp '.$fmt($lastPurchasePrice) : '—' }}</div>
                                </td>
                                @unless($isAdminRole)
                                    <td><span class="item-status {{ $item->active ? 'item-status-active' : 'item-status-off' }}">{{ $item->active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                @endunless
                                <td class="text-end">
                                    <div class="item-row-actions">
                                        @if($isAdminRole)
                                            <a href="{{ route('master.items.edit', $item) }}" class="btn item-primary btn-sm" title="Edit nama" aria-label="Edit nama {{ $item->code }}"><i class="bi bi-pencil"></i><span class="action-label">Edit Nama</span></a>
                                        @else
                                            <a href="{{ route('master.items.show', $item) }}" class="btn item-soft btn-sm" title="Lihat detail" aria-label="Lihat detail {{ $item->code }}"><i class="bi bi-eye"></i><span class="action-label d-none d-xl-inline">Lihat</span></a>
                                            <a href="{{ route('master.items.edit', $item) }}" class="btn item-primary btn-sm" title="Edit item" aria-label="Edit item {{ $item->code }}"><i class="bi bi-pencil"></i><span class="action-label d-none d-xl-inline">Edit</span></a>
                                            <div class="dropdown">
                                                <button type="button" class="btn item-soft btn-sm px-2" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi lainnya"><i class="bi bi-three-dots"></i></button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <li><a class="dropdown-item small" href="{{ route('master.items.hpp_temp.edit', $item) }}"><i class="bi bi-cash-coin me-2"></i>Set HPP</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('master.items.destroy', $item) }}" onsubmit="return confirm('Hapus item {{ addslashes($item->code) }}?');">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item small text-danger"><i class="bi bi-trash3 me-2"></i>Hapus item</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="item-mobile-list">
                @foreach($items as $item)
                    @php
                        $hpp = (float) ($item->active_unit_cost ?? $item->effective_unit_cost ?? $item->hpp ?? 0);
                        $lastPurchasePrice = (float) ($item->last_purchase_price ?? 0);
                        $modeClass = $item->isHybrid() ? 'item-status-hybrid' : ($item->canMake() ? 'item-status-make' : ($item->canBuy() ? 'item-status-buy' : 'item-status-undefined'));
                    @endphp
                    <article class="item-mobile-card">
                        <div class="item-mobile-card-head">
                            <div class="d-flex align-items-start gap-2 min-w-0">
                                @unless($isAdminRole)
                                    <input type="checkbox" class="form-check-input row-check mt-1" value="{{ $item->id }}" aria-label="Pilih {{ $item->code }}">
                                @endunless
                                <div class="item-mobile-card-title">
                                    <a class="item-code text-decoration-none" href="{{ route('master.items.show', $item) }}">{{ $item->code }}</a>
                                    <div class="item-name mt-1">{{ $item->name }}</div>
                                </div>
                            </div>
                            @unless($isAdminRole)
                                <span class="item-status {{ $item->active ? 'item-status-active' : 'item-status-off' }}">{{ $item->active ? 'Aktif' : 'Nonaktif' }}</span>
                            @endunless
                        </div>
                        <div class="item-mobile-card-meta">
                            @if($isAdminRole)
                                <div><div class="item-mobile-card-label">Klasifikasi</div><div class="item-mobile-card-value">{{ $typeLabels[$item->type] ?? $item->type }}@if($item->category?->code) · {{ $item->category->code }}@endif</div></div>
                            @endif
                            @unless($isAdminRole)
                                <div><div class="item-mobile-card-label">Tipe</div><div class="item-mobile-card-value">{{ $typeLabels[$item->type] ?? $item->type }}</div></div>
                                <div><div class="item-mobile-card-label">Kategori</div><div class="item-mobile-card-value">{{ $item->category?->code ?? '—' }}</div></div>
                                <div><div class="item-mobile-card-label">Pasok</div><div class="item-mobile-card-value">@if(in_array($item->type, ['finished_good', 'wip'], true))<span class="item-status {{ $modeClass }}">{{ $item->supply_mode_label }}</span>@else—@endif</div></div>
                                <div><div class="item-mobile-card-label">HPP</div><div class="item-mobile-card-value">{{ $hpp > 0 ? 'Rp '.$fmt($hpp) : '—' }}</div></div>
                            @endunless
                            <div><div class="item-mobile-card-label">Harga terakhir</div><div class="item-mobile-card-value">{{ $lastPurchasePrice > 0 ? 'Rp '.$fmt($lastPurchasePrice) : '—' }}</div></div>
                        </div>
                        <div class="item-mobile-card-footer">
                            <div class="item-mobile-card-actions">
                                @if($isAdminRole)
                                    <a href="{{ route('master.items.edit', $item) }}" class="btn item-primary btn-sm"><i class="bi bi-pencil"></i>Edit Nama</a>
                                @else
                                    <a href="{{ route('master.items.show', $item) }}" class="btn item-soft btn-sm"><i class="bi bi-eye"></i>Lihat</a>
                                    <a href="{{ route('master.items.edit', $item) }}" class="btn item-primary btn-sm"><i class="bi bi-pencil"></i>Edit</a>
                                @endif
                            </div>
                            @unless($isAdminRole)
                                <div class="dropdown">
                                    <button type="button" class="btn item-soft btn-sm px-2" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi lainnya"><i class="bi bi-three-dots"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li><a class="dropdown-item small" href="{{ route('master.items.hpp_temp.edit', $item) }}"><i class="bi bi-cash-coin me-2"></i>Set HPP</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('master.items.destroy', $item) }}" onsubmit="return confirm('Hapus item {{ addslashes($item->code) }}?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item small text-danger"><i class="bi bi-trash3 me-2"></i>Hapus item</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            @endunless
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="item-footer"><div>Menampilkan {{ $items->firstItem() }}–{{ $items->lastItem() }} dari {{ $fmt($items->total()) }} item</div>{{ $items->links() }}</div>
        @else
            <div class="item-empty">
                <div class="item-empty-icon"><i class="bi bi-search"></i></div>
                <div class="fw-bold">Tidak ada item yang cocok</div>
                <div class="small mt-1">Coba ubah kata pencarian atau reset filter.</div>
                @if($hasFilters)<a href="{{ route('master.items.index') }}" class="btn item-soft btn-sm mt-3 item-reset-filter">Reset filter</a>@endif
            </div>
        @endif
        </div>
    </div>
</div>

@unless($isAdminRole)
<div class="modal fade" id="bulkCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('master.items.bulk_update') }}" class="modal-content bulk-form">
            @csrf
            <input type="hidden" name="action" value="set_category">
            <div class="modal-header"><h5 class="modal-title">Ubah kategori <span class="text-muted">(<span class="sel-count">0</span> item)</span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><label class="item-label" for="bulk-category">Kategori baru</label><select id="bulk-category" name="item_category_id" class="form-select" required><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->code }} — {{ $category->name }}</option>@endforeach</select><div class="ids-container"></div></div>
            <div class="modal-footer"><button type="button" class="btn item-soft btn-sm" data-bs-dismiss="modal">Batal</button><button class="btn item-primary btn-sm">Terapkan</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="bulkTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('master.items.bulk_update') }}" class="modal-content bulk-form">
            @csrf
            <input type="hidden" name="action" value="set_type">
            <div class="modal-header"><h5 class="modal-title">Ubah tipe <span class="text-muted">(<span class="sel-count">0</span> item)</span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><label class="item-label" for="bulk-type">Tipe baru</label><select id="bulk-type" name="type" class="form-select" required>@foreach($typeLabels as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select><div class="alert alert-info py-2 px-3 mt-3 mb-0 small"><i class="bi bi-info-circle me-1"></i>Item yang menjadi Finished Good otomatis disiapkan sebagai bisa dibeli.</div><div class="ids-container"></div></div>
            <div class="modal-footer"><button type="button" class="btn item-soft btn-sm" data-bs-dismiss="modal">Batal</button><button class="btn item-primary btn-sm">Terapkan</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="bulkHppModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('master.items.bulk_update') }}" class="modal-content bulk-form">
            @csrf
            <input type="hidden" name="action" value="set_hpp">
            <div class="modal-header"><h5 class="modal-title">Set HPP sementara <span class="text-muted">(<span class="sel-count">0</span> item)</span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><label class="item-label" for="bulk-hpp">HPP per unit (Rp)</label><input id="bulk-hpp" type="number" min="0" step="0.01" name="unit_cost" class="form-control" required><label class="item-label mt-3" for="bulk-hpp-notes">Catatan (opsional)</label><input id="bulk-hpp-notes" type="text" name="notes" maxlength="255" class="form-control"><div class="ids-container"></div></div>
            <div class="modal-footer"><button type="button" class="btn item-soft btn-sm" data-bs-dismiss="modal">Batal</button><button class="btn btn-success btn-sm rounded-pill fw-bold">Terapkan HPP</button></div>
        </form>
    </div>
</div>
@endunless
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.querySelector('.item-filter');
    const results = () => document.getElementById('itemResults');
    let filterTimer = null;
    let activeRequest = null;

    function selectedIds() {
        return [...new Set(Array.from(document.querySelectorAll('#itemResults .row-check:checked')).map(input => input.value))];
    }

    function refreshBulkUi() {
        const rows = Array.from(document.querySelectorAll('#itemResults .row-check'));
        const ids = selectedIds();
        const toolbar = document.getElementById('bulkToolbar');
        const checkAll = document.getElementById('checkAll');
        const selectedCount = document.getElementById('selectedCount');
        if (toolbar) toolbar.classList.toggle('d-none', ids.length === 0);
        if (selectedCount) selectedCount.textContent = ids.length;
        document.querySelectorAll('#itemResults .sel-count').forEach(node => node.textContent = ids.length);
        if (checkAll) {
            const rowIds = [...new Set(rows.map(input => input.value))];
            checkAll.checked = rowIds.length > 0 && rowIds.every(id => ids.includes(id));
            checkAll.indeterminate = ids.length > 0 && !checkAll.checked;
        }
    }

    function bindBulkControls() {
        const checkAll = document.getElementById('checkAll');
        const clearButton = document.getElementById('bulkClear');
        document.querySelectorAll('#itemResults .row-check').forEach(input => input.addEventListener('change', () => {
            document.querySelectorAll(`#itemResults .row-check[value="${CSS.escape(input.value)}"]`).forEach(peer => peer.checked = input.checked);
            refreshBulkUi();
        }));
        checkAll?.addEventListener('change', () => {
            document.querySelectorAll('#itemResults .row-check').forEach(input => input.checked = checkAll.checked);
            refreshBulkUi();
        });
        clearButton?.addEventListener('click', () => {
            document.querySelectorAll('#itemResults .row-check').forEach(input => input.checked = false);
            refreshBulkUi();
        });
        document.querySelectorAll('#itemResults .bulk-form').forEach(form => form.addEventListener('submit', function (event) {
            const ids = selectedIds();
            if (!ids.length) {
                event.preventDefault();
                window.alert('Pilih minimal satu item terlebih dahulu.');
                return;
            }
            const container = form.querySelector('.ids-container');
            if (!container) return;
            container.replaceChildren(...ids.map(id => {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'item_ids[]';
                hidden.value = id;
                return hidden;
            }));
        }));
        refreshBulkUi();
    }

    function buildFilterUrl() {
        const url = new URL(filterForm.action, window.location.href);
        const data = new FormData(filterForm);
        for (const [key, value] of data.entries()) {
            if (String(value).trim() !== '') url.searchParams.set(key, value);
        }
        url.searchParams.delete('page');
        return url;
    }

    function syncFilterChrome(url) {
        document.querySelectorAll('.item-reset-filter').forEach(link => link.classList.toggle('d-none', !['q', 'type', 'category_kind', 'item_category_id', 'supply_mode'].some(key => url.searchParams.get(key))));
    }

    async function refreshItems(url) {
        activeRequest?.abort();
        const requestController = new AbortController();
        activeRequest = requestController;
        filterForm.classList.add('is-loading');
        results()?.classList.add('is-loading');
        const submitButton = document.getElementById('itemFilterSubmit');
        if (submitButton) submitButton.disabled = true;

        try {
            const response = await fetch(url.toString(), {
                signal: requestController.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            });
            if (!response.ok) throw new Error(`Filter request failed: ${response.status}`);
            const html = await response.text();
            const incoming = new DOMParser().parseFromString(html, 'text/html').querySelector('#itemResults');
            const current = results();
            if (!incoming || !current) throw new Error('Hasil filter tidak ditemukan.');
            current.replaceWith(incoming);
            window.history.replaceState({}, '', url.toString());
            syncFilterChrome(url);
            bindBulkControls();
        } catch (error) {
            if (error.name !== 'AbortError') console.error(error);
        } finally {
            if (activeRequest === requestController) {
                filterForm.classList.remove('is-loading');
                results()?.classList.remove('is-loading');
                if (submitButton) submitButton.disabled = false;
            }
        }
    }

    filterForm?.addEventListener('submit', event => {
        event.preventDefault();
        clearTimeout(filterTimer);
        refreshItems(buildFilterUrl());
    });
    document.getElementById('item-search')?.addEventListener('input', () => {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(() => refreshItems(buildFilterUrl()), 280);
    });
    filterForm?.querySelectorAll('select').forEach(select => select.addEventListener('change', () => refreshItems(buildFilterUrl())));
    document.addEventListener('click', event => {
        const link = event.target.closest('.item-reset-filter');
        if (!link) return;
        event.preventDefault();
        filterForm.reset();
        refreshItems(new URL(link.href, window.location.href));
    });
    document.addEventListener('click', event => {
        const pageLink = event.target.closest('#itemResults .pagination a');
        if (!pageLink) return;
        event.preventDefault();
        refreshItems(new URL(pageLink.href, window.location.href));
    });

    bindBulkControls();
    syncFilterChrome(new URL(window.location.href));
});
</script>
@endpush
