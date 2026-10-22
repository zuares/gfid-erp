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
    #addItemModal { z-index:10050; }
    .add-item-modal-form { overflow:hidden; }
    .add-item-modal-header { align-items:flex-start; gap:1rem; padding:1.2rem 1.35rem 1rem; background:linear-gradient(135deg,#f8fafc,#eef2ff); }
    .add-item-modal-kicker { display:flex; align-items:center; gap:.35rem; color:#4f46e5; font-size:.64rem; font-weight:900; letter-spacing:.09em; text-transform:uppercase; }
    .add-item-modal-header .modal-title { margin-top:.25rem; color:#111827; font-size:1.18rem; letter-spacing:-.025em; }
    .add-item-modal-subtitle { max-width:560px; margin:.35rem 0 0; color:#64748b; font-size:.76rem; line-height:1.45; }
    .add-item-modal-body { padding:1.1rem 1.35rem 1.25rem; background:#fff; }
    .add-item-alert { margin-bottom:1rem; border-radius:12px; font-size:.78rem; }
    .add-item-section { padding:1rem; border:1px solid #e7ebf2; border-radius:14px; background:#fff; }
    .add-item-section + .add-item-section { margin-top:.8rem; }
    .add-item-section-head { display:flex; align-items:flex-start; gap:.6rem; margin-bottom:.9rem; }
    .add-item-section-icon { display:grid; flex:0 0 30px; width:30px; height:30px; place-items:center; border-radius:9px; color:#4f46e5; background:#eef2ff; font-size:.84rem; }
    .add-item-section-head h3 { margin:0; color:#1e293b; font-size:.84rem; font-weight:850; }
    .add-item-section-head p { margin:.18rem 0 0; color:#64748b; font-size:.7rem; line-height:1.4; }
    .add-item-modal-body .form-label { margin-bottom:.35rem; color:#334155; font-size:.73rem; font-weight:800; }
    .add-item-modal-body .form-control, .add-item-modal-body .form-select { min-height:40px; border-color:#dbe3ee; background:#fff; font-size:.82rem; }
    .add-item-modal-body .form-control:focus, .add-item-modal-body .form-select:focus { border-color:#6366f1; box-shadow:0 0 0 .2rem rgba(99,102,241,.12); }
    .add-item-modal-body .form-text { margin-top:.3rem; color:#94a3b8; font-size:.67rem; line-height:1.35; }
    .add-item-modal-body .is-invalid { border-color:#dc3545; }
    .add-item-modal-body .invalid-feedback { font-size:.68rem; }
    .add-item-footer-note { display:flex; align-items:flex-start; gap:.45rem; margin-top:.9rem; padding:.7rem .8rem; border:1px solid #dbeafe; border-radius:11px; color:#1d4ed8; background:#eff6ff; font-size:.7rem; line-height:1.4; }
    .add-item-footer-note i { margin-top:.05rem; }
    .add-item-modal-footer { display:flex; align-items:center; gap:.75rem; padding:.85rem 1.35rem 1rem; background:#fbfcfe; }
    .add-item-modal-footer .btn { min-height:38px; border-radius:10px; }
    .add-item-full-link { display:inline-flex; align-items:center; gap:.35rem; color:#64748b; font-size:.72rem; font-weight:750; text-decoration:none; }
    .add-item-full-link:hover { color:#4f46e5; }
    body[data-theme="dark"] .add-item-modal-header { background:linear-gradient(135deg,#111827,#1e1b4b); }
    body[data-theme="dark"] .add-item-modal-header .modal-title, body[data-theme="dark"] .add-item-section-head h3 { color:#f8fafc; }
    body[data-theme="dark"] .add-item-modal-subtitle, body[data-theme="dark"] .add-item-section-head p { color:#94a3b8; }
    body[data-theme="dark"] .add-item-modal-body, body[data-theme="dark"] .add-item-section, body[data-theme="dark"] .add-item-modal-footer { background:#0f172a; border-color:#334155; }
    body[data-theme="dark"] .add-item-modal-body .form-label { color:#cbd5e1; }
    body[data-theme="dark"] .add-item-modal-body .form-control, body[data-theme="dark"] .add-item-modal-body .form-select { background:#111827; border-color:#475569; color:#f8fafc; }
    body[data-theme="dark"] .add-item-footer-note { border-color:#1e40af; color:#bfdbfe; background:#172554; }
    @media (max-width:700px) {
        .add-item-modal-header, .add-item-modal-body, .add-item-modal-footer { padding-inline: .9rem; }
        .add-item-modal-header { padding-top:1rem; }
        .add-item-section { padding:.8rem; }
        .add-item-modal-footer { align-items:stretch; flex-direction:column; }
        .add-item-full-link { min-height:36px; }
        .add-item-modal-footer > .d-flex { width:100%; margin-left:0!important; }
        .add-item-modal-footer > .d-flex .btn { flex:1 1 0; }
    }
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

    /* System design alignment: GFID tokens, panels, badges and CRUD modal. */
    .item-page {
        --gf-brand: #5b5ce2;
        --gf-brand-strong: #4142bb;
        --gf-accent: #13b8c4;
        --gf-ink: #101828;
        --gf-muted: #667085;
        --gf-line: #e4e7ec;
        --gf-soft: #f2f4f7;
        --gf-shadow-sm: 0 1px 2px rgba(16,24,40,.04), 0 4px 12px rgba(16,24,40,.04);
        --gf-shadow-lg: 0 24px 56px rgba(48,49,125,.14);
        max-width: 1280px;
        padding: 1.25rem 1.2rem 5rem;
        color: var(--gf-ink);
    }
    .item-hero { display: grid; grid-template-columns: minmax(250px,1fr) auto auto; align-items: center; gap: 1.25rem; margin: 0 0 1rem; padding: 1.35rem 1.5rem; border: 1px solid #e0e2fb; border-radius: 24px; background: linear-gradient(120deg,#f4f3ff 0%,#fff 52%,#edfcff 100%); box-shadow: var(--gf-shadow-lg); }
    .item-hero-main { display: flex; align-items: flex-start; gap: .85rem; min-width: 0; }
    .item-icon { display: grid; flex: 0 0 42px; width: 42px; height: 42px; place-items: center; margin-top: .08rem; border-radius: 13px; color: #fff; background: linear-gradient(145deg,var(--gf-brand),var(--gf-accent)); box-shadow: 0 8px 16px rgba(91,92,226,.24); font-size: 1rem; }
    .item-eyebrow { display: inline-flex; align-items: center; gap: .35rem; margin-bottom: .42rem; color: var(--gf-brand-strong); font-size: .64rem; font-weight: 850; letter-spacing: .12em; text-transform: uppercase; }
    .item-title { color: var(--gf-ink); font-size: clamp(1.35rem,2.1vw,1.75rem); font-weight: 820; letter-spacing: -.055em; line-height: 1.05; }
    .item-subtitle { max-width: 540px; margin: .45rem 0 0; color: var(--gf-muted); font-size: .78rem; line-height: 1.5; }
    .item-kpis { display: grid; grid-template-columns: repeat(4,minmax(88px,1fr)); gap: .45rem; margin: 0; }
    .item-kpi { position: relative; overflow: hidden; display: block; min-width: 0; padding: .68rem .72rem; border: 1px solid rgba(91,92,226,.14); border-radius: 13px; background: rgba(255,255,255,.72); box-shadow: 0 1px 3px rgba(16,24,40,.03); }
    .item-kpi::before { content: ''; position: absolute; width: 52px; height: 52px; right: -20px; top: -20px; border-radius: 50%; background: #eeefff; }
    .item-kpi-label, .item-kpi-value { position: relative; display: block; }
    .item-kpi-label { color: var(--gf-muted); font-size: .61rem; font-weight: 750; line-height: 1.25; }
    .item-kpi-value { margin-top: .35rem; color: var(--gf-ink); font-size: 1.15rem; font-weight: 850; letter-spacing: -.05em; font-variant-numeric: tabular-nums; }
    .item-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: .45rem; }
    .item-actions .btn, .item-row-actions .btn, .item-card .btn { min-height: 36px; border-radius: 10px; font-size: .72rem; font-weight: 750; }
    .item-primary { color: #fff !important; border-color: var(--gf-brand) !important; background: var(--gf-brand) !important; box-shadow: 0 7px 14px rgba(91,92,226,.2); }
    .item-primary:hover, .item-primary:focus-visible { color: #fff !important; border-color: var(--gf-brand-strong) !important; background: var(--gf-brand-strong) !important; }
    .item-soft { color: #475467 !important; border: 1px solid #d0d5dd !important; background: rgba(255,255,255,.82) !important; box-shadow: none !important; }
    .item-soft:hover, .item-soft:focus-visible { color: var(--gf-brand-strong) !important; border-color: #b9baf4 !important; background: #fff !important; }
    .item-card { overflow: hidden; border: 1px solid var(--gf-line); border-radius: 16px; background: #fff; box-shadow: var(--gf-shadow-sm); }
    .item-filter { display: grid; grid-template-columns: minmax(230px,1.5fr) repeat(4,minmax(120px,1fr)) auto; gap: .65rem; align-items: end; padding: 1rem; border-bottom: 1px solid #eef0f3; background: #fff; }
    .item-filter-search { position: relative; grid-column: span 2; }
    .item-filter-search::before { content: '\F52A'; position: absolute; z-index: 1; left: .75rem; bottom: .72rem; color: #98a2b3; font-family: 'bootstrap-icons'; font-size: .78rem; pointer-events: none; }
    .item-label { display: block; height: auto; margin-bottom: .32rem; color: #475467; font-size: .65rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .item-filter .form-control, .item-filter .form-select { min-height: 38px; height: 38px; padding-inline: .7rem; border: 1px solid #d0d5dd; border-radius: 9px; color: #344054; background: #fff; font-size: .74rem; font-weight: 600; box-shadow: none; }
    .item-filter-search .form-control { padding-left: 2.1rem; border-color: #b9baf4; font-size: .78rem; }
    .item-filter .form-control:focus, .item-filter .form-select:focus { border-color: var(--gf-brand); box-shadow: 0 0 0 3px rgba(91,92,226,.12); }
    .item-filter-actions { display: flex; align-items: center; gap: .4rem; }
    .item-filter-actions .btn { min-height: 38px; }
    .item-results { transition: opacity .18s ease; }
    .item-results.is-loading { opacity: .45; pointer-events: none; }
    .item-overview { display: flex; align-items: center; flex-wrap: wrap; gap: .42rem; padding: .72rem 1rem; border-bottom: 1px solid #eef0f3; background: #fcfcfd; }
    .item-overview-label { margin-right: .2rem; color: #98a2b3; font-size: .61rem; font-weight: 850; letter-spacing: .08em; text-transform: uppercase; }
    .item-overview-stat { display: inline-flex; align-items: center; gap: .35rem; min-height: 25px; padding: 0 .52rem; border: 1px solid #e4e7ec; border-radius: 999px; color: #667085; background: #fff; font-size: .64rem; font-weight: 700; }
    .item-overview-stat strong { color: var(--gf-ink); font-size: .7rem; font-weight: 850; }
    .item-bulk { display: flex; align-items: center; flex-wrap: wrap; gap: .45rem; padding: .68rem 1rem; border-bottom: 1px solid #eef0f3; background: #fafaff; }
    .item-bulk strong { color: var(--gf-ink); font-size: .72rem; }
    .item-bulk .item-muted { color: #98a2b3; font-size: .67rem; }
    .item-bulk .btn { min-height: 30px; padding-inline: .62rem; font-size: .67rem; }
    .item-table-wrap { max-height: calc(100vh - 330px); overflow: auto; }
    .item-table { min-width: 980px; margin: 0; font-size: .72rem; }
    .item-table thead th { position: sticky; top: 0; z-index: 10; padding: .72rem .75rem; border-bottom: 1px solid #eef0f3; color: #98a2b3; background: #fcfcfd; font-size: .59rem; font-weight: 850; letter-spacing: .06em; text-transform: uppercase; white-space: nowrap; }
    .item-table tbody td { padding: .72rem .75rem; border-top: 1px solid #f0f1f3; color: #475467; vertical-align: middle; font-size: .73rem; }
    .item-table tbody tr:hover { background: #fafaff; }
    .item-table th:last-child, .item-table td:last-child { position: sticky; right: 0; z-index: 11; background: #fff; box-shadow: -8px 0 16px rgba(16,24,40,.05); }
    .item-table thead th:last-child { background: #fcfcfd; }
    .item-table tbody tr:hover td:last-child { background: #fafaff; }
    .item-code { display: inline-flex; align-items: center; min-height: 22px; padding: 0 .45rem; border: 1px solid #dfe1ff; border-radius: 6px; color: var(--gf-brand-strong); background: #f4f4ff; font-family: ui-monospace,SFMono-Regular,Menlo,monospace; font-size: .64rem; font-weight: 800; }
    .item-name { color: var(--gf-ink); font-size: .74rem; font-weight: 750; }
    .item-muted, .item-meta { color: #98a2b3; font-size: .66rem; }
    .item-meta { margin-top: .22rem; }
    .item-type { display: inline-flex; align-items: center; min-height: 22px; padding: 0 .48rem; border-radius: 999px; color: #475467; background: #f2f4f7; font-size: .62rem; font-weight: 750; }
    .item-status { display: inline-flex; align-items: center; gap: .3rem; min-height: 22px; padding: 0 .52rem; border-radius: 999px; font-size: .61rem; font-weight: 800; white-space: nowrap; }
    .item-status::before { width: 6px; height: 6px; border-radius: 50%; background: currentColor; content: ''; }
    .item-status-active, .item-status-make { color: #067647; background: #ecfdf3; }
    .item-status-off { color: #b42318; background: #fef3f2; }
    .item-status-buy { color: #175cd3; background: #eff8ff; }
    .item-status-hybrid { color: var(--gf-brand-strong); background: #eeefff; }
    .item-status-undefined { color: #a15c07; background: #fffaeb; }
    .item-row-actions { display: flex; align-items: center; justify-content: flex-end; gap: .3rem; flex-wrap: nowrap; }
    .item-row-actions .btn { min-width: 32px; min-height: 32px; padding: .3rem .48rem; }
    .item-row-actions .item-soft, .item-mobile-card-actions .item-soft { color: #475467 !important; background: #fff !important; border-color: #d0d5dd !important; }
    .item-row-actions .item-soft:hover, .item-row-actions .item-soft:focus-visible, .item-mobile-card-actions .item-soft:hover, .item-mobile-card-actions .item-soft:focus-visible { color: var(--gf-brand-strong) !important; background: #fff !important; border-color: #b9baf4 !important; }
    .item-footer { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .8rem; padding: .85rem 1rem; border-top: 1px solid #eef0f3; color: #98a2b3; font-size: .66rem; font-weight: 650; }
    .item-footer .pagination { margin: 0; }
    .item-footer .page-link { border-color: #e4e7ec; border-radius: 8px; color: #667085; font-size: .65rem; }
    .item-footer .page-item.active .page-link { border-color: var(--gf-brand); color: #fff; background: var(--gf-brand); }
    .item-empty { display: grid; min-height: 240px; place-items: center; padding: 2rem; text-align: center; color: #667085; }
    .item-empty-icon { display: grid; width: 40px; height: 40px; place-items: center; margin: 0 auto .55rem; border-radius: 13px; color: var(--gf-brand); background: #eeefff; font-size: 1rem; }
    .item-mobile-list { display: none; padding: .55rem; background: #fcfcfd; }
    .item-mobile-card { padding: .82rem; border: 1px solid var(--gf-line); border-radius: 13px; background: #fff; box-shadow: var(--gf-shadow-sm); }
    .item-mobile-card + .item-mobile-card { margin-top: .55rem; }
    .item-mobile-card-head, .item-mobile-card-footer { display: flex; align-items: center; justify-content: space-between; gap: .6rem; }
    .item-mobile-card-head { align-items: flex-start; }
    .item-mobile-card-title { min-width: 0; }
    .item-mobile-card-title .item-name { overflow: hidden; margin-top: .3rem; text-overflow: ellipsis; white-space: nowrap; }
    .item-mobile-card-meta { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: .65rem .8rem; margin-top: .8rem; padding-top: .72rem; border-top: 1px solid #f0f1f3; }
    .item-mobile-card-label { color: #98a2b3; font-size: .58rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .item-mobile-card-value { overflow: hidden; margin-top: .18rem; color: #475467; font-size: .67rem; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
    .item-mobile-card-footer { margin-top: .8rem; }
    .item-mobile-card-actions { display: flex; flex: 1 1 auto; gap: .35rem; }
    .item-mobile-card-actions .btn { flex: 1 1 0; justify-content: center; }

    #addItemModal .modal-dialog { max-width: 720px; }
    #addItemModal .modal-content { overflow: hidden; border: 1px solid #e0e2fb; border-radius: 18px; box-shadow: var(--gf-shadow-lg); }
    #addItemModal .add-item-modal-header { display: grid; grid-template-columns: 38px minmax(0,1fr) 30px; align-items: start; gap: .7rem; padding: 1rem 1.2rem; border-bottom: 1px solid #f0f1f3; background: #fff; }
    #addItemModal .add-item-modal-header-icon { display: grid; width: 38px; height: 38px; place-items: center; border-radius: 12px; color: var(--gf-brand-strong); background: #eeefff; font-size: .9rem; }
    #addItemModal .add-item-modal-kicker { margin-top: .05rem; color: var(--gf-brand-strong); font-size: .6rem; font-weight: 850; letter-spacing: .1em; text-transform: uppercase; }
    #addItemModal .add-item-modal-header .modal-title { margin: .18rem 0 0; color: var(--gf-ink); font-size: 1.05rem; font-weight: 820; letter-spacing: -.04em; }
    #addItemModal .add-item-modal-subtitle { max-width: 540px; margin: .25rem 0 0; color: var(--gf-muted); font-size: .7rem; line-height: 1.45; }
    #addItemModal .btn-close { grid-column: 3; width: 30px; height: 30px; padding: .5rem; border-radius: 8px; }
    #addItemModal .add-item-modal-body { padding: 1rem 1.2rem 1.1rem; background: #fff; }
    #addItemModal .add-item-section { padding: .9rem; border: 1px solid var(--gf-line); border-radius: 13px; background: #fff; }
    #addItemModal .add-item-section + .add-item-section { margin-top: .7rem; }
    #addItemModal .add-item-section-head { gap: .55rem; margin-bottom: .75rem; }
    #addItemModal .add-item-section-icon { width: 28px; height: 28px; flex-basis: 28px; border-radius: 9px; color: var(--gf-brand-strong); background: #eeefff; font-size: .76rem; }
    #addItemModal .add-item-section-head h3 { color: var(--gf-ink); font-size: .75rem; font-weight: 800; }
    #addItemModal .add-item-section-head p { margin-top: .12rem; color: #98a2b3; font-size: .64rem; }
    #addItemModal .form-label { margin-bottom: .3rem; color: #475467; font-size: .64rem; font-weight: 750; }
    #addItemModal .form-control, #addItemModal .form-select { min-height: 38px; border-color: #d0d5dd; border-radius: 9px; color: #344054; background: #fff; font-size: .72rem; box-shadow: none; }
    #addItemModal .form-control:focus, #addItemModal .form-select:focus { border-color: var(--gf-brand); box-shadow: 0 0 0 3px rgba(91,92,226,.12); }
    #addItemModal .form-text { margin-top: .25rem; color: #98a2b3; font-size: .6rem; }
    #addItemModal .invalid-feedback { font-size: .6rem; }
    #addItemModal .add-item-footer-note { margin-top: .75rem; padding: .62rem .7rem; border: 1px solid #b2ddff; border-radius: 9px; color: #175cd3; background: #eff8ff; font-size: .63rem; }
    #addItemModal .add-item-modal-footer { display: flex; align-items: center; gap: .7rem; padding: .78rem 1.2rem; border-top: 1px solid #f0f1f3; background: #fcfcfd; }
    #addItemModal .add-item-modal-footer .btn { min-height: 34px; font-size: .68rem; }
    #addItemModal .add-item-full-link { color: #667085; font-size: .63rem; font-weight: 700; }
    #addItemModal .add-item-full-link:hover { color: var(--gf-brand-strong); }

    body[data-theme="dark"] .item-page { --gf-ink: #f8fafc; --gf-muted: #aab4c5; }
    body[data-theme="dark"] .item-hero { border-color: #263149; background: linear-gradient(120deg,#171638 0%,#111827 52%,#0f2931 100%); }
    body[data-theme="dark"] .item-title, body[data-theme="dark"] .item-name, body[data-theme="dark"] .item-kpi-value, body[data-theme="dark"] .item-overview-stat strong { color: #f8fafc; }
    body[data-theme="dark"] .item-kpi, body[data-theme="dark"] .item-card, body[data-theme="dark"] .item-mobile-card, body[data-theme="dark"] .item-filter, body[data-theme="dark"] .item-table th:last-child, body[data-theme="dark"] .item-table td:last-child { border-color: #334155; background: #111827; }
    body[data-theme="dark"] .item-filter, body[data-theme="dark"] .item-table thead th, body[data-theme="dark"] .item-mobile-list, body[data-theme="dark"] .item-overview, body[data-theme="dark"] .item-bulk, body[data-theme="dark"] .item-footer { border-color: #263149; background: #0f172a; }
    body[data-theme="dark"] .item-filter .form-control, body[data-theme="dark"] .item-filter .form-select, body[data-theme="dark"] #addItemModal .form-control, body[data-theme="dark"] #addItemModal .form-select { border-color: #475569; color: #f8fafc; background: #0f172a; }
    body[data-theme="dark"] .item-table tbody td, body[data-theme="dark"] .item-mobile-card-value { color: #cbd5e1; border-color: #263149; }
    body[data-theme="dark"] .item-table tbody tr:hover, body[data-theme="dark"] .item-table tbody tr:hover td:last-child { background: #172033; }
    body[data-theme="dark"] #addItemModal .modal-content, body[data-theme="dark"] #addItemModal .add-item-modal-header, body[data-theme="dark"] #addItemModal .add-item-modal-body, body[data-theme="dark"] #addItemModal .add-item-section, body[data-theme="dark"] #addItemModal .add-item-modal-footer { border-color: #334155; background: #111827; }
    body[data-theme="dark"] #addItemModal .add-item-modal-header .modal-title, body[data-theme="dark"] #addItemModal .add-item-section-head h3 { color: #f8fafc; }

    @media (max-width: 1100px) { .item-hero { grid-template-columns: 1fr; } .item-kpis { grid-template-columns: repeat(4,minmax(0,1fr)); } .item-actions { grid-column: auto; justify-content: flex-start; } .item-filter { grid-template-columns: repeat(3,minmax(0,1fr)); } .item-filter-search { grid-column: 1 / -1; } }
    @media (min-width: 761px) and (max-width: 1400px) {
        .item-page:not(.item-page-admin) .item-table { width: 100%; min-width: 0; table-layout: fixed; }
        .item-page:not(.item-page-admin) .item-table th,
        .item-page:not(.item-page-admin) .item-table td { min-width: 0 !important; }
        .item-page:not(.item-page-admin) .item-table th:nth-child(1), .item-page:not(.item-page-admin) .item-table td:nth-child(1) { width: 4%; }
        .item-page:not(.item-page-admin) .item-table th:nth-child(2), .item-page:not(.item-page-admin) .item-table td:nth-child(2) { width: 4%; }
        .item-page:not(.item-page-admin) .item-table th:nth-child(3), .item-page:not(.item-page-admin) .item-table td:nth-child(3) { width: 18%; }
        .item-page:not(.item-page-admin) .item-table th:nth-child(4), .item-page:not(.item-page-admin) .item-table td:nth-child(4) { width: 14%; }
        .item-page:not(.item-page-admin) .item-table th:nth-child(5), .item-page:not(.item-page-admin) .item-table td:nth-child(5) { width: 20%; }
        .item-page:not(.item-page-admin) .item-table th:nth-child(6), .item-page:not(.item-page-admin) .item-table td:nth-child(6) { width: 9%; }
        .item-page:not(.item-page-admin) .item-table th:nth-child(7), .item-page:not(.item-page-admin) .item-table td:nth-child(7) { width: 11%; }
        .item-page:not(.item-page-admin) .item-table th:nth-child(8), .item-page:not(.item-page-admin) .item-table td:nth-child(8) { width: 8%; }
        .item-page:not(.item-page-admin) .item-table th:nth-child(9), .item-page:not(.item-page-admin) .item-table td:nth-child(9) { width: 12%; }
        .item-page:not(.item-page-admin) .item-table .item-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .item-page:not(.item-page-admin) .item-table thead th:nth-child(7), .item-page:not(.item-page-admin) .item-table thead th:nth-child(8) { white-space: normal; line-height: 1.15; }
        .item-page:not(.item-page-admin) .item-table td:nth-child(5) .item-status { white-space: normal; line-height: 1.2; }
    }
    @media (max-width: 760px) {
        .item-page { padding: .75rem .65rem 5rem; }
        .item-hero { gap: .9rem; padding: 1rem; border-radius: 18px; }
        .item-icon { width: 36px; height: 36px; flex-basis: 36px; border-radius: 11px; }
        .item-title { font-size: 1.3rem; }
        .item-subtitle { font-size: .72rem; }
        .item-kpis { grid-template-columns: repeat(2,minmax(0,1fr)); }
        .item-actions { display: grid; grid-template-columns: 1fr 1fr; width: 100%; }
        .item-actions .btn { justify-content: center; width: 100%; }
        .item-filter { grid-template-columns: 1fr; padding: .8rem; }
        .item-filter-search { grid-column: auto; }
        .item-filter-actions { display: grid; grid-template-columns: 1fr auto; }
        .item-filter-actions .btn { justify-content: center; width: 100%; }
        .item-overview { flex-wrap: nowrap; overflow-x: auto; scrollbar-width: none; }
        .item-overview::-webkit-scrollbar { display: none; }
        .item-overview-label, .item-overview-stat { flex: 0 0 auto; }
        .item-table-wrap { display: none; }
        .item-mobile-list { display: block; }
        .item-footer { align-items: flex-start; padding: .8rem; }
        .item-footer .pagination { width: 100%; overflow-x: auto; padding-bottom: 2px; }
        .item-bulk { align-items: flex-start; padding: .7rem .8rem; }
        .item-bulk .item-muted { display: none; }
        .item-bulk .btn { flex: 1 1 auto; }
        .item-bulk #bulkClear { flex: 0 0 auto; }
        #addItemModal .modal-dialog { margin: .5rem; }
        #addItemModal .add-item-modal-header, #addItemModal .add-item-modal-body, #addItemModal .add-item-modal-footer { padding-inline: .9rem; }
        #addItemModal .add-item-modal-footer { align-items: stretch; flex-direction: column; }
        #addItemModal .add-item-modal-footer > .d-flex { width: 100%; margin-left: 0 !important; }
        #addItemModal .add-item-modal-footer > .d-flex .btn { flex: 1 1 0; }
    }
    @media (prefers-reduced-motion: reduce) { .item-results, .item-primary, .item-soft { transition: none !important; } }
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
                <p class="item-subtitle">Satu ruang untuk mengatur identitas, klasifikasi, harga, dan alur pasok barang.</p>
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
                <button type="button" class="btn item-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addItemModal">
                    <i class="bi bi-plus-lg"></i><span>Tambah Item</span>
                </button>
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
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalTitle" aria-describedby="addItemModalDescription" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <form method="POST" action="{{ route('master.items.store') }}" class="modal-content add-item-modal-form">
            @csrf
            <input type="hidden" name="return_to_index" value="1">
            <input type="hidden" name="can_buy" value="0" data-add-item-can-buy>
            <input type="hidden" name="can_make" value="0" data-add-item-can-make>
            <input type="hidden" name="default_supply_source" value="" data-add-item-default-source>

            <div class="modal-header add-item-modal-header">
                <div class="add-item-modal-header-icon" aria-hidden="true"><i class="bi bi-plus-lg"></i></div>
                <div>
                    <div class="add-item-modal-kicker">New record</div>
                    <h2 class="modal-title" id="addItemModalTitle">Tambah barang</h2>
                    <p class="add-item-modal-subtitle" id="addItemModalDescription">Isi informasi inti dulu. Detail supplier, barcode, dan HPP lanjutan bisa dilengkapi setelahnya.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body add-item-modal-body">
                @if($errors->any() && old('return_to_index'))
                    <div class="alert alert-danger add-item-alert" role="alert">
                        <div class="fw-bold"><i class="bi bi-exclamation-circle me-1"></i>Periksa kembali data barang</div>
                        <ul class="mb-0 mt-1 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="add-item-section">
                    <div class="add-item-section-head">
                        <span class="add-item-section-icon"><i class="bi bi-tag"></i></span>
                        <div><h3>Identitas barang</h3><p>Kode dan nama menjadi identitas utama di transaksi.</p></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-5">
                            <label class="form-label" for="add-item-code">Kode barang <span class="text-danger">*</span></label>
                            <input id="add-item-code" type="text" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" maxlength="50" placeholder="Contoh: BNG-HITAM" autocomplete="off" required>
                            <div class="form-text">Otomatis diubah ke huruf kapital.</div>
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-7">
                            <label class="form-label" for="add-item-name">Nama barang <span class="text-danger">*</span></label>
                            <input id="add-item-name" type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" maxlength="190" placeholder="Contoh: Benang jahit hitam" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label" for="add-item-sku">SKU <span class="text-muted fw-normal">(opsional)</span></label>
                            <input id="add-item-sku" type="text" name="sku" value="{{ old('sku') }}" class="form-control @error('sku') is-invalid @enderror" maxlength="100" placeholder="Kosongkan untuk mengikuti kode">
                            @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-7">
                            <label class="form-label" for="add-item-type">Tipe barang <span class="text-danger">*</span></label>
                            <select id="add-item-type" name="type" class="form-select @error('type') is-invalid @enderror" required data-add-item-type>
                                @foreach($typeLabels as $key => $label)
                                    <option value="{{ $key }}" @selected(old('type', 'material') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="add-item-category">Kategori <span class="text-muted fw-normal">(opsional)</span></label>
                            <select id="add-item-category" name="item_category_id" class="form-select @error('item_category_id') is-invalid @enderror" data-add-item-category>
                                <option value="">Pilih kategori bila tersedia</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" data-category-kind="{{ $category->kind }}" @selected((string) old('item_category_id') === (string) $category->id)>{{ $category->code }} — {{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text" data-add-item-category-help>Kategori akan membantu menentukan klasifikasi dan perlakuan akuntansi.</div>
                            @error('item_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="add-item-section">
                    <div class="add-item-section-head">
                        <span class="add-item-section-icon"><i class="bi bi-rulers"></i></span>
                        <div><h3>Satuan & pasokan</h3><p>Gunakan satuan stok untuk inventory dan satuan beli untuk purchasing.</p></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="add-item-stock-unit">Satuan stok <span class="text-danger">*</span></label>
                            <input id="add-item-stock-unit" type="text" name="stock_unit" value="{{ old('stock_unit', old('unit', 'pcs')) }}" class="form-control @error('stock_unit') is-invalid @enderror" maxlength="20" placeholder="pcs" required>
                            @error('stock_unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="add-item-purchase-unit">Satuan beli <span class="text-danger">*</span></label>
                            <input id="add-item-purchase-unit" type="text" name="purchase_unit" value="{{ old('purchase_unit', old('stock_unit', old('unit', 'pcs'))) }}" class="form-control @error('purchase_unit') is-invalid @enderror" maxlength="20" placeholder="pcs" required>
                            @error('purchase_unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="add-item-conversion">Konversi ke stok <span class="text-danger">*</span></label>
                            <input id="add-item-conversion" type="number" name="purchase_conversion_factor" value="{{ old('purchase_conversion_factor', '1') }}" class="form-control @error('purchase_conversion_factor') is-invalid @enderror" min="0.000001" step="0.000001" required>
                            @error('purchase_conversion_factor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12" data-add-item-supply-wrap>
                            <label class="form-label" for="add-item-supply-mode">Metode pasok default</label>
                            <select id="add-item-supply-mode" class="form-select" data-add-item-supply-mode>
                                <option value="buy" @selected(old('default_supply_source', 'buy') === 'buy')>Beli jadi</option>
                                <option value="make" @selected(old('default_supply_source') === 'make')>Produksi sendiri</option>
                                <option value="hybrid" @selected(old('default_supply_source') === 'hybrid')>Hybrid: beli / produksi</option>
                                <option value="outsource" @selected(old('default_supply_source') === 'outsource')>Makloon / outsource</option>
                            </select>
                            <div class="form-text" data-add-item-supply-help>Opsi ini berlaku untuk barang jadi dan WIP.</div>
                        </div>
                    </div>
                </div>

                <div class="add-item-section">
                    <div class="add-item-section-head">
                        <span class="add-item-section-icon"><i class="bi bi-cash-stack"></i></span>
                        <div><h3>Harga awal</h3><p>Boleh dikosongkan jika harga akan diisi dari penerimaan pembelian.</p></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="add-item-last-price">Harga beli terakhir (Rp)</label>
                            <input id="add-item-last-price" type="number" name="last_purchase_price" value="{{ old('last_purchase_price') }}" class="form-control @error('last_purchase_price') is-invalid @enderror" min="0" step="0.01" placeholder="0">
                            @error('last_purchase_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="add-item-unit-cost">HPP awal (Rp / satuan stok)</label>
                            <input id="add-item-unit-cost" type="number" name="unit_cost" value="{{ old('unit_cost') }}" class="form-control @error('unit_cost') is-invalid @enderror" min="0" step="0.01" placeholder="0">
                            @error('unit_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="add-item-footer-note"><i class="bi bi-shield-check"></i><span>Data akan divalidasi di server dan disimpan dalam satu transaksi.</span></div>
            </div>

            <div class="modal-footer add-item-modal-footer">
                <a href="{{ route('master.items.create') }}" class="add-item-full-link"><i class="bi bi-sliders2"></i><span>Buka form lengkap</span></a>
                <div class="d-flex gap-2 ms-auto">
                    <button type="button" class="btn item-soft btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn item-primary btn-sm" data-add-item-submit><i class="bi bi-check2-circle"></i><span>Simpan barang</span></button>
                </div>
            </div>
        </form>
    </div>
</div>

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

    const addItemModal = document.getElementById('addItemModal');
    const addItemForm = addItemModal?.querySelector('form');
    const addItemType = addItemModal?.querySelector('[data-add-item-type]');
    const addItemCategory = addItemModal?.querySelector('[data-add-item-category]');
    const addItemSupplyWrap = addItemModal?.querySelector('[data-add-item-supply-wrap]');
    const addItemSupplyMode = addItemModal?.querySelector('[data-add-item-supply-mode]');
    const addItemCanBuy = addItemModal?.querySelector('[data-add-item-can-buy]');
    const addItemCanMake = addItemModal?.querySelector('[data-add-item-can-make]');
    const addItemDefaultSource = addItemModal?.querySelector('[data-add-item-default-source]');

    function syncAddItemCategoryOptions() {
        if (!addItemType || !addItemCategory) return;
        const type = addItemType.value;
        const allowedKinds = type === 'finished_good' || type === 'wip'
            ? ['product']
            : ['material', 'support', 'accessory', 'packaging', 'operational', 'other'];
        const selectedOption = addItemCategory.value;
        Array.from(addItemCategory.options).forEach(option => {
            if (!option.dataset.categoryKind) {
                option.hidden = false;
                return;
            }
            const allowed = allowedKinds.includes(option.dataset.categoryKind);
            option.hidden = !allowed;
            option.disabled = !allowed;
        });
        const selected = addItemCategory.options[addItemCategory.selectedIndex];
        if (selected?.disabled) addItemCategory.value = '';
        const help = addItemModal?.querySelector('[data-add-item-category-help]');
        if (help) help.textContent = type === 'finished_good' || type === 'wip'
            ? 'Untuk barang jadi/WIP, pilih kategori kelompok produk jadi.'
            : 'Kategori akan membantu menentukan klasifikasi dan perlakuan akuntansi.';
        if (selectedOption && addItemCategory.value !== selectedOption) addItemCategory.dispatchEvent(new Event('change'));
    }

    function syncAddItemSupply() {
        if (!addItemType || !addItemSupplyMode || !addItemCanBuy || !addItemCanMake || !addItemDefaultSource) return;
        const isSupplyItem = ['finished_good', 'wip'].includes(addItemType.value);
        addItemSupplyWrap?.toggleAttribute('hidden', !isSupplyItem);
        if (!isSupplyItem) {
            addItemCanBuy.value = '0';
            addItemCanMake.value = '0';
            addItemDefaultSource.value = '';
            return;
        }
        const mode = addItemSupplyMode.value;
        addItemCanBuy.value = ['buy', 'hybrid'].includes(mode) ? '1' : '0';
        addItemCanMake.value = ['make', 'hybrid'].includes(mode) ? '1' : '0';
        addItemDefaultSource.value = mode === 'hybrid' ? 'buy' : mode;
    }

    addItemType?.addEventListener('change', () => {
        syncAddItemCategoryOptions();
        syncAddItemSupply();
    });
    addItemSupplyMode?.addEventListener('change', syncAddItemSupply);
    addItemForm?.addEventListener('submit', () => {
        syncAddItemSupply();
        const submit = addItemForm.querySelector('[data-add-item-submit]');
        if (submit) {
            submit.disabled = true;
            submit.querySelector('span').textContent = 'Menyimpan…';
            submit.querySelector('i').className = 'spinner-border spinner-border-sm';
        }
    });
    syncAddItemCategoryOptions();
    syncAddItemSupply();

    @if($errors->any() && old('return_to_index'))
        if (addItemModal && window.bootstrap) bootstrap.Modal.getOrCreateInstance(addItemModal).show();
    @endif

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
