{{-- resources/views/sales/shipments/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Shipments • Keluar Barang')

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
        position:relative;
        z-index:1;
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
    .sub{ color:var(--shp-muted); font-size:.78rem; }
    body[data-theme="dark"] .sub{ color:#9ca3af; }

    .kpis{ display:flex; flex-wrap:wrap; gap:.32rem; margin-top:.35rem; }
    .kpi{
        display:inline-flex; align-items:baseline; gap:.45rem;
        border-radius:7px; padding:.2rem .48rem;
        border:1px solid rgba(148,163,184,.28);
        background: transparent;
        font-size:.72rem;
    }
    body[data-theme="dark"] .kpi{
        background: rgba(15, 23, 42, 0.96);
        border-color: rgba(51, 65, 85, 0.85);
    }
    .kpi .lbl{ text-transform:none; letter-spacing:0; font-size:.66rem; color:#94a3b8; }
    body[data-theme="dark"] .kpi .lbl{ color:#6b7280; }
    .kpi .val{ font-weight:650; color:var(--shp-accent); }

    .header-actions{ display:flex; gap:.45rem; align-items:center; flex-wrap:wrap; justify-content:flex-end; }
    .filter-bar{ background:var(--card,#fff); border:1px solid var(--shp-border); border-radius:10px; padding:.75rem .85rem; margin-bottom:.65rem; box-shadow:0 2px 8px rgba(15,23,42,.025); }
    body[data-theme="dark"] .filter-bar{ background:rgba(15,23,42,.98); border-color:rgba(51,65,85,.6); }
    .shipment-filter-form{ display:block; }
    .shipment-filter-main{ display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; }
    .shipment-filter-main .filter-input-wrap{ flex:1 1 220px; min-width:180px; }
    .shipment-filter-main .filter-input{ width:100%; min-width:0; border-radius:8px; font-size:.8rem; }
    .shipment-filter-main .filter-select{ min-width:130px; border-radius:8px; font-size:.8rem; }
    .shipment-date-control{ display:flex; align-items:center; flex:1 1 300px; min-width:250px; }
    .shipment-date-control .date-section{ width:100%; min-width:0; height:36px!important; border:1px solid rgba(37,99,235,.32)!important; border-radius:9px!important; background:#f8fafc; box-shadow:0 2px 8px rgba(37,99,235,.08); }
    .shipment-date-control .rts-date-picker.flatpickr-input{ min-width:120px!important; width:100%!important; height:36px!important; color:#334155; font-size:.82rem!important; font-weight:650; }
    body[data-theme="dark"] .shipment-date-control .date-section{ background:#1e293b; border-color:rgba(96,165,250,.55)!important; box-shadow:0 2px 10px rgba(30,64,175,.18); }
    body[data-theme="dark"] .shipment-date-control .rts-date-picker.flatpickr-input{ color:#e2e8f0; }
    @media (min-width:769px){
        .shipment-filter-main{ display:grid; grid-template-columns:minmax(220px,1fr) minmax(115px,.7fr) minmax(125px,.8fr) minmax(250px,1.2fr) auto; align-items:center; flex-wrap:nowrap; }
        .shipment-filter-main .filter-input-wrap,
        .shipment-filter-main .filter-input,
        .shipment-filter-main .filter-select{ width:100%; min-width:0; }
        .shipment-date-control{ width:100%; min-width:0; flex:initial; }
        .shipment-date-control .date-section{ width:100%; min-width:0!important; }
        .shipment-filter-main > .btn{ white-space:nowrap; }
    }
    .filter-summary{ font-size:.74rem; color:var(--shp-muted); }
    .filter-summary strong{ color:var(--shp-accent); }
    body[data-theme="dark"] .filter-summary strong{ color:#cbd5e1; }
    .shipment-filter-form .btn{ min-height:32px; }
    .btn-pill{ border-radius:7px; padding-inline:.78rem; box-shadow:none!important; font-weight:600; }
    .btn-ship-primary{ background:var(--shp-accent)!important; border-color:var(--shp-accent)!important; color:#fff!important; }
    .btn-ship-primary:hover{ background:var(--shp-accent-2)!important; border-color:var(--shp-accent-2)!important; color:#fff!important; }
    .btn-ship-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-ship-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }
    .btn-fresh{ border-color:#fecaca; color:#b91c1c; background:transparent; }
    .btn-fresh:hover{ background:#fef2f2; color:#991b1b; border-color:#fca5a5; }

    .table-list{ margin-bottom:0; }
    .table-responsive{ max-height:60vh; overflow:auto; }
    .table-list thead th{
        position:sticky;
        top:0;
        z-index:20;
        border-bottom-width:1px;
        font-size:.64rem;
        text-transform:uppercase;
        letter-spacing:.04em;
        color:#64748b;
        background: var(--card,#fff);
        padding:.75rem 1rem;
        white-space:nowrap;
    }
    .table-list thead tr{ position:sticky; top:0; z-index:20; }
    .table-sort{ display:inline-flex; align-items:center; gap:.25rem; color:#64748b; text-decoration:none; }
    .table-sort:hover{ color:#111827; text-decoration:none; }
    .table-sort.active{ color:#334155; }
    .table-sort-icon{ font-size:.62rem; color:#94a3b8; }
    .table-sort.active .table-sort-icon{ color:#334155; }
    body[data-theme="dark"] .table-list thead th{
        background: rgba(15, 23, 42, 0.98);
        color:#9ca3af;
        border-bottom-color: rgba(30, 64, 175, 0.6);
    }
    .table-list tbody td{
        vertical-align:middle;
        border-top-color: rgba(148, 163, 184, 0.16);
        padding:.55rem .85rem;
        font-size:.78rem;
    }
    body[data-theme="dark"] .table-list tbody td{ border-top-color: rgba(51, 65, 85, 0.85); }

    .code-link{ font-size:.68rem; font-weight:650; text-decoration:none; color:#94a3b8!important; letter-spacing:.01em; }
    .code-link:hover{ text-decoration:underline; }
    .muted{ font-size:.82rem; color:#6b7280; }
    body[data-theme="dark"] .muted{ color:#9ca3af; }
    .store-name{ font-weight:600; }

    .badge-status{
        border-radius:7px; padding:.16rem .48rem;
        font-size:.68rem; letter-spacing:0; text-transform:none;
        border:1px solid transparent;
        display:inline-flex; align-items:center; gap:.35rem;
        white-space:nowrap;
    }
    .badge-status::before{ content:''; width:7px; height:7px; border-radius:999px; display:inline-block; }

    .st-draft{ background: rgba(148, 163, 184, 0.10); color:#475569; border-color: rgba(148, 163, 184, 0.30); }
    .st-draft::before{ background: rgba(100, 116, 139, 0.95); }
    .st-submitted{ background: rgba(59, 130, 246, 0.10); color:#1d4ed8; border-color: rgba(59, 130, 246, 0.30); }
    .st-submitted::before{ background: rgba(59, 130, 246, 0.95); }
    .st-posted{ background: rgba(34, 197, 94, 0.10); color:#166534; border-color: rgba(34, 197, 94, 0.30); }
    .st-posted::before{ background: rgba(34, 197, 94, 0.95); }
    .st-cancelled{ background: rgba(239, 68, 68, 0.10); color:#991b1b; border-color: rgba(239, 68, 68, 0.30); }
    .st-cancelled::before{ background: rgba(239, 68, 68, 0.95); }

    body[data-theme="dark"] .st-submitted{ background: rgba(59, 130, 246, 0.20); color:#dbeafe; border-color: rgba(59, 130, 246, 0.55); }
    body[data-theme="dark"] .st-posted{ background: rgba(34, 197, 94, 0.20); color:#dcfce7; border-color: rgba(34, 197, 94, 0.55); }
    body[data-theme="dark"] .st-cancelled{ background: rgba(239, 68, 68, 0.18); color:#fecaca; border-color: rgba(239, 68, 68, 0.55); }

    .empty{ padding:2.2rem 1.25rem; text-align:center; color:#64748b; }
    body[data-theme="dark"] .empty{ color:#9ca3af; }
    .divider{ height:1px; background: rgba(148, 163, 184, 0.20); }
    body[data-theme="dark"] .divider{ background: rgba(51, 65, 85, 0.85); }
    .flash-clean{ border-radius:8px; padding:.62rem .75rem; font-size:.84rem; border:1px solid rgba(148,163,184,.25); }
    .stale-draft-alert{ display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; border-radius:8px; padding:.5rem .7rem; color:#92400e; background:#fffbeb; border:1px solid #fde68a; font-size:.74rem; }
    .stale-draft-alert a{ color:#78350f; font-weight:800; text-decoration:underline; }

    /* Minimal list hierarchy */
    .ship-topbar{
        position:sticky;
        top:0;
        z-index:300;
        padding:.45rem .75rem;
        margin-inline:-.75rem;
        margin-bottom:.65rem;
        border-radius:0;
        box-shadow:none;
    }
    .ship-heading{ min-width:0; }
    .ship-topbar .title{ font-size:1rem; font-weight:750; color:inherit; }
    .ship-topbar .sub{ margin-top:0; }
    .ship-topbar .kpis{ margin-top:.35rem; }
    .ship-topbar .kpi{ background:transparent; }
    .ship-topbar .kpi .val{ color:var(--shp-accent); font-weight:650; }
    .header-actions .btn-ship-primary{ font-weight:600; }
    .filter-bar{ align-items:flex-start; padding:.7rem; }
    .filter-heading{ padding-top:.35rem; color:#0f172a; }
    .filter-heading i{ margin-right:.25rem; color:#2563eb; }
    .filter-input-wrap{ position:relative; min-width:0; }
    .filter-input-wrap i{ position:absolute; left:.7rem; top:50%; transform:translateY(-50%); color:#94a3b8; pointer-events:none; }
    .filter-input-wrap .filter-input{ padding-left:2rem; }
    .filter-advanced-label{ display:none; }
    .list-toolbar{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.7rem .8rem; border-bottom:1px solid var(--shp-border); }
    .list-toolbar-title{ color:#0f172a; font-size:.86rem; font-weight:850; }
    .list-toolbar-sub{ margin-left:.35rem; color:#94a3b8; font-size:.72rem; }
    .list-count{ display:inline-flex; align-items:center; min-height:28px; padding:.15rem .55rem; border:1px solid rgba(37,99,235,.18); border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:.7rem; font-weight:800; white-space:nowrap; }
    .code-link{ display:inline-block; color:#334155!important; font-size:.8rem; font-weight:800; text-decoration:none; background:rgba(148,163,184,.12); padding:.2rem .5rem; border-radius:6px; border:1px solid rgba(148,163,184,.25); white-space:nowrap; }
    .code-link:hover{ color:#0f172a!important; background:rgba(148,163,184,.22); border-color:rgba(148,163,184,.4); }
    .shipment-inline{ display:flex; flex-direction:column; align-items:flex-start; gap:.14rem; min-width:0; white-space:normal; }
    .shipment-document-head{ display:flex; align-items:center; flex-wrap:wrap; gap:.3rem .4rem; }
    .shipment-document-meta{ display:flex; align-items:center; flex-wrap:wrap; gap:.25rem .4rem; }
    .shipment-channel-inline{ color:#94a3b8; font-size:.66rem; line-height:1.2; white-space:nowrap; max-width:190px; overflow:hidden; text-overflow:ellipsis; }
    .shipment-timestamps{ display:flex; align-items:center; flex-wrap:wrap; gap:.18rem .45rem; color:#64748b; font-size:.7rem; }
    .shipment-timestamps span + span::before{ content:'·'; margin-right:.45rem; color:#cbd5e1; }
    .shipment-store{ color:#475569; font-size:.72rem; }
    .shipment-store::before{ content:'·'; margin-right:.45rem; color:#cbd5e1; }
    .mode-badge{ display:inline-flex; align-items:center; gap:.3rem; border-radius:999px; padding:.2rem .5rem; font-size:.68rem; font-weight:800; white-space:nowrap; }
    .mode-badge::before{ content:''; width:6px; height:6px; border-radius:999px; background:currentColor; }
    .mode-item{ color:#475569; background:#f8fafc; border:1px solid #e2e8f0; }
    .mode-order{ color:#64748b; background:#f8fafc; border:1px solid #e2e8f0; }
    .package-summary{ display:flex; align-items:center; flex-wrap:wrap; gap:.3rem .45rem; white-space:nowrap; }
    .package-main{ color:#334155; font-size:.78rem; font-weight:750; }
    .package-sub{ color:#64748b; font-size:.7rem; }
    .package-sub::before{ content:'·'; margin-right:.3rem; color:#cbd5e1; }
    .row-draft{ background:rgba(248,250,252,.68); }
    .row-draft:hover{ background:rgba(241,245,249,.9)!important; }
    .ship-row-action{ white-space:nowrap; }
    .ship-row-action .btn{ width:auto!important; white-space:nowrap; }
    .ship-row-action .d-flex{ flex-wrap:nowrap!important; }
    .shipment-row-clickable{ cursor:pointer; }
    .shipment-row-clickable:hover{ background:rgba(241,245,249,.82)!important; }
    .action-icon{ display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; padding:0!important; border-radius:8px!important; }
    .action-icon i{ margin:0!important; font-size:.86rem; }
    .action-icon .action-label{ display:none; }
    .ship-row-action .action-icon{ transition:transform .12s ease, background .12s ease, border-color .12s ease; }
    .ship-row-action .action-icon:hover{ transform:translateY(-1px); }
    .ship-row-action .btn-ship-outline.action-icon{ color:#475569!important; background:transparent!important; border-color:rgba(148,163,184,.35)!important; }
    .ship-row-action .btn-ship-outline.action-icon:hover{ color:#111827!important; background:rgba(148,163,184,.08)!important; border-color:rgba(148,163,184,.5)!important; }
    .ship-row-action .btn-outline-danger.action-icon{ color:#b45353!important; background:#fffafa!important; border-color:#f1d0d0!important; }
    .ship-row-action .btn-outline-danger.action-icon:hover{ color:#991b1b!important; background:#fef2f2!important; border-color:#e8b4b4!important; }
    .ship-row-action .btn-ship-primary{ color:#fff!important; background:var(--shp-accent)!important; border-color:var(--shp-accent)!important; }
    .ship-row-action .btn-ship-primary:hover{ background:var(--shp-accent-2)!important; border-color:var(--shp-accent-2)!important; }
    .action-label{ margin-left:.22rem; }
    .empty-icon{ display:grid; place-items:center; width:42px; height:42px; margin:0 auto .65rem; border-radius:12px; background:#eff6ff; color:#2563eb; font-size:1.1rem; }
    .empty-title{ color:#0f172a; font-size:.9rem; font-weight:850; }
    .empty-sub{ margin-top:.25rem; font-size:.78rem; }
    .list-footer{ display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.65rem .8rem .8rem; border-top:1px solid var(--shp-border); }
    .list-footer .pagination{ margin:0; }
    body[data-theme="dark"] .ship-topbar .title,
    body[data-theme="dark"] .filter-heading,
    body[data-theme="dark"] .list-toolbar-title,
    body[data-theme="dark"] .code-link,
    body[data-theme="dark"] .package-main,
    body[data-theme="dark"] .empty-title{ color:#f1f5f9; }
    body[data-theme="dark"] .ship-topbar .kpi{ background:rgba(15,23,42,.96); }
    body[data-theme="dark"] .ship-topbar .kpi .val{ color:#e5e7eb; }
    body[data-theme="dark"] .shipment-store,
    body[data-theme="dark"] .shipment-timestamps,
    body[data-theme="dark"] .package-sub{ color:#94a3b8; }
    body[data-theme="dark"] .row-draft{ background:rgba(30,41,59,.55); }
    body[data-theme="dark"] .code-link:hover{ color:#fff!important; }

    @media (max-width: 768px) {
        .page-wrap{ padding:.5rem .5rem 4rem; }
        .ship-topbar{ margin-inline:-.5rem; padding:.5rem .65rem; }
        .ship-heading{ min-width:0; width:100%; }
        .ship-topbar .sub{ display:block; }
        .ship-topbar .kpis{ margin-top:.45rem; }
        .ship-topbar .kpi{ flex:1; justify-content:space-between; }
        .header-actions{ width:100%; justify-content:flex-start; }
        .header-actions .btn{ flex:1; }
        .filter-bar{ display:block; padding:.65rem; }
        .shipment-filter-main{ display:grid; grid-template-columns:1fr; width:100%; }
        .shipment-filter-main .filter-input-wrap,
        .shipment-filter-main .filter-input,
        .shipment-filter-main .filter-select{ width:100%; min-width:0; }
        .shipment-filter-main .filter-select{ min-height:40px; }
        .shipment-date-control{ width:100%; min-width:0; flex-basis:100%; }
        .shipment-filter-form .btn{ width:100%; min-height:40px; }
        .shipment-filter-form .btn{ min-height:40px; }
        .kpis{ display:none; }
        .ship-topbar .kpis{ display:none; }
        .list-toolbar{ align-items:flex-start; }
        .list-toolbar-sub{ display:block; margin:.15rem 0 0; }
        .list-footer{ display:block; }
        .list-footer .pagination{ margin-top:.6rem; }
        .action-label{ display:inline; }
        .shipment-inline{ display:block; white-space:normal; }
        .shipment-timestamps,
        .shipment-store{ display:block; margin-top:.15rem; }
        .shipment-timestamps span{ display:block; }
        .shipment-timestamps span + span{ margin-top:.12rem; }
        .shipment-timestamps span + span::before,
        .shipment-store::before{ display:none; }
        .package-summary{ display:block; white-space:normal; }
        .package-sub{ display:block; margin-top:.12rem; }
        .package-sub::before{ display:none; }
        .table-responsive{ max-height:62vh; overflow:auto; }
        .table-list thead{ display:none; }
        .table-list,
        .table-list tbody,
        .table-list tr,
        .table-list td{ display:block; width:100%; }
        .table-list tbody tr{
            padding:.66rem;
            border-top:1px solid rgba(148,163,184,.16);
        }
        .table-list tbody td{
            border:0;
            padding:0;
        }
        .shipment-time-cell{ margin-top:.42rem; padding-top:.42rem!important; border-top:1px solid #edf1f6!important; font-size:.72rem; }
        .shipment-time-stack{ flex-direction:row; flex-wrap:wrap; gap:.18rem .65rem; }
        .shipment-time-stack small{ min-width:auto; margin-right:.15rem; }
        .table-list tbody td.mobile-hide{ display:none; }
        .ship-row-main{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:.75rem;
        }
        .ship-row-meta{
            display:flex;
            align-items:center;
            gap:.45rem;
            flex-wrap:wrap;
            margin-top:.35rem;
            color:#64748b;
            font-size:.78rem;
        }
        .ship-row-action{
            margin-top:.55rem;
        }
        .ship-row-action .btn{
            width:100%;
            min-height:38px;
        }
    }

    /* ═══════════════════════════════
       PREVIEW MODAL (Custom)
    ═══════════════════════════════ */
    .ms-modal-backdrop {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15,23,42,.6);
        backdrop-filter: blur(4px);
        z-index: 9990;
        display: none;
        align-items: center; justify-content: center;
        padding: 1rem;
    }
    .ms-modal-backdrop.show { display: flex; }
    
    .ms-modal-content {
        background: var(--card, #fff);
        border-radius: 24px;
        width: 100%; max-width: 500px;
        box-shadow: 0 20px 40px rgba(0,0,0,.2);
        overflow: hidden;
        display: flex; flex-direction: column;
        max-height: 90vh;
    }
    .ms-modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--shp-border, rgba(148,163,184,.15));
        display: flex; align-items: center; justify-content: space-between;
    }
    .ms-modal-header h3 {
        margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--text, #0f172a);
    }
    .ms-modal-close {
        background: transparent; border: none; font-size: 1.5rem; color: var(--muted); cursor: pointer;
    }
    .ms-modal-body {
        padding: 1.5rem;
        overflow-y: auto;
        background: #e2e8f0;
        display: flex; justify-content: center;
    }
    body[data-theme="dark"] .ms-modal-body { background: #0f172a; }
    
    .ms-modal-footer {
        padding: 1.25rem 1.5rem;
        border-top: 1px solid var(--shp-border, rgba(148,163,184,.15));
        display: flex; justify-content: flex-end; gap: .75rem;
    }

    /* ═══════════════════════════════
       LABEL (100mm × 150mm)
    ═══════════════════════════════ */
    .label-wrap {
        width: 100mm;
        min-height: 150mm;
        background: #fff;
        color: #0f172a;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        position: relative;
        box-shadow: 0 8px 32px rgba(0,0,0,.15);
        overflow: hidden;
    }

    .label-header {
        background: #fff;
        color: #000;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 2px solid #000;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .label-header .logo-svg { width: 36px; height: 36px; flex-shrink: 0; object-fit: contain; }
    .label-header .brand-text { font-size: 18px; font-weight: 900; letter-spacing: -.5px; color: #000; }
    .label-header .brand-sub { font-size: 8px; font-weight: 800; color: #000; letter-spacing: 1px; text-transform: uppercase; margin-top: 1px; }

    .label-section { padding: 8px 14px; border-bottom: 1.5px dashed #000; }
    .label-section:last-child { border-bottom: none; }
    .label-section-title { font-size: 7px; font-weight: 900; text-transform: uppercase; letter-spacing: 1.2px; color: #000; margin-bottom: 4px; }
    .label-name { font-size: 13px; font-weight: 900; line-height: 1.2; margin-bottom: 2px; text-transform: uppercase; color: #000; }
    .label-phone { font-size: 11px; font-weight: 700; color: #000; display: flex; align-items: center; gap: 4px; }
    .label-phone .phone-icon { font-size: 10px; }
    .label-address { font-size: 11px; font-weight: 500; line-height: 1.35; color: #000; margin-top: 4px; word-break: break-word; }
    .label-items { font-size: 9px; font-weight: 600; color: #000; margin-top: 6px; line-height: 1.4; }
    .label-item-row { display: flex; justify-content: space-between; border-bottom: 1px solid #ccc; padding: 2px 0; color: #000; }
    .label-item-row:last-child { border-bottom: none; }

    .label-divider { display: flex; align-items: center; gap: 8px; padding: 3px 14px; background: #fff; }
    .label-divider::before, .label-divider::after { content: ''; flex: 1; height: 1px; background: #000; }
    .label-divider .arrow-icon { font-size: 14px; color: #000; }

    .label-promo { background: #fff; padding: 10px 14px; text-align: center; border-bottom: 1.5px dashed #000; }
    .label-promo-title { font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #000; margin-bottom: 6px; }
    .label-promo-content { display: flex; align-items: center; justify-content: center; gap: 12px; }
    .label-qr { flex-shrink: 0; }
    .label-qr canvas, .label-qr img { width: 60px !important; height: 60px !important; }
    .label-promo-info { text-align: left; }
    .label-promo-url { font-size: 12px; font-weight: 900; color: #000; margin-bottom: 4px; }
    .label-promo-socials { display: flex; flex-direction: column; gap: 3px; }
    .label-promo-social-item { display: flex; align-items: center; gap: 5px; font-size: 9px; font-weight: 600; color: #000; }
    .label-promo-social-item img { width: 13px; height: 13px; object-fit: contain; filter: grayscale(100%) contrast(200%); }

    .label-footer { 
        background: #fff; 
        color: #000; 
        text-align: center; 
        padding: 8px 14px; 
        font-size: 8px; 
        font-weight: 800; 
        letter-spacing: .3px; 
        border-top: 2px solid #000; 
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .label-footer strong { color: #000; font-weight: 900; }

    @media print {
        @page { size: 100mm 150mm; margin: 0; }
        body * { visibility: hidden; }
        #modalPreview, #modalPreview * { visibility: visible; }
        #modalPreview { position: absolute; left: 0; top: 0; width: 100mm; height: 150mm; margin: 0; padding: 0; display: block !important; background: none !important; }
        .ms-modal-content { box-shadow: none !important; border-radius: 0 !important; max-width: none !important; border: none !important; background: none !important; margin: 0 !important; padding: 0 !important; }
        .ms-modal-header, .ms-modal-footer, .no-print { display: none !important; }
        .ms-modal-body { padding: 0 !important; background: #fff !important; display: block !important; overflow: visible !important; }
        .label-wrap { width: 100mm !important; min-height: 150mm !important; box-shadow: none !important; margin: 0 !important; position: absolute; top: 0; left: 0; }
    }

    body[data-theme="dark"] .ms-modal-content { background: #1e293b; }
    body[data-theme="dark"] .ms-modal-header h3 { color: #f1f5f9; }
    body[data-theme="dark"] .ms-modal-header, body[data-theme="dark"] .ms-modal-footer { border-color: rgba(51,65,85,.6); }

    /* Shipment index: aligned with the compact scan/confirm surfaces */
    .page-wrap{
        max-width:960px;
        padding:.55rem .7rem 3.5rem;
    }
    .ship-topbar{
        position:relative;
        top:auto;
        margin:0 0 .65rem;
        padding:.7rem .8rem;
        border:1px solid #dbe3ee;
        border-radius:10px;
        box-shadow:0 2px 10px rgba(15,23,42,.035);
    }
    .ship-topbar .sub{ margin-top:.15rem; color:#94a3b8; }
    .kpis{ gap:.35rem; margin-top:.5rem; }
    .kpi{
        min-height:28px;
        padding:.18rem .5rem;
        border-color:#e2e8f0;
        background:#f8fafc;
    }
    .kpi .lbl{ color:#64748b; }
    .kpi .val{ color:#334155; }
    .header-actions{ gap:.35rem; }
    .header-actions .btn{ min-height:32px; }

    .filter-bar{
        margin-bottom:.65rem;
        padding:.6rem;
        border-color:#dbe3ee;
        border-radius:10px;
        box-shadow:0 1px 7px rgba(15,23,42,.025);
    }
    .shipment-filter-main{ gap:.4rem; }
    .shipment-filter-main .filter-input,
    .shipment-filter-main .filter-select{
        min-height:34px;
        border-color:#dbe3ee;
        background:#fff;
        color:#334155;
    }
    .shipment-filter-main .filter-input:focus,
    .shipment-filter-main .filter-select:focus{
        border-color:#94a3b8;
        box-shadow:0 0 0 3px rgba(148,163,184,.14);
    }
    .shipment-date-control .date-section{
        height:34px!important;
        border-color:#dbe3ee!important;
        background:#fff;
        box-shadow:none;
    }
    .shipment-date-control .rts-date-picker.flatpickr-input{
        height:34px!important;
        color:#334155;
    }
    .filter-summary{
        margin-bottom:.45rem!important;
        color:#94a3b8;
    }
    .filter-summary strong{ color:#64748b; font-weight:700; }

    .card-main{
        border-color:#dbe3ee;
        border-radius:10px;
        box-shadow:0 2px 10px rgba(15,23,42,.035);
    }
    .table-responsive{ max-height:64vh; }
    .table-list thead th{
        padding:.65rem .75rem;
        color:#64748b;
        background:#f8fafc;
        border-bottom-color:#e2e8f0;
        font-size:.62rem;
        font-weight:750;
    }
    .table-list tbody td{
        padding:.62rem .75rem;
        border-top-color:#edf1f6;
    }
    .table-list tbody tr:hover{ background:#fbfdff!important; }
    .shipment-time-cell{ color:#64748b; font-size:.7rem; white-space:nowrap; }
    .shipment-time-stack{ display:flex; flex-direction:column; gap:.18rem; line-height:1.25; }
    .shipment-time-stack small{ display:inline-block; min-width:38px; color:#94a3b8; font-size:.62rem; font-weight:700; }
    .code-link{
        padding:.18rem .45rem;
        border-color:#e2e8f0;
        background:#f8fafc;
        color:#334155!important;
    }
    .package-main{ font-weight:700; }
    .badge-status{ padding:.18rem .5rem; }
    .list-footer{ padding:.6rem .75rem .7rem; border-top-color:#edf1f6; }
    .action-icon{ width:31px; height:31px; }

    body[data-theme="dark"] .ship-topbar,
    body[data-theme="dark"] .filter-bar,
    body[data-theme="dark"] .card-main{
        border-color:rgba(71,85,105,.75);
        box-shadow:none;
    }
    body[data-theme="dark"] .kpi,
    body[data-theme="dark"] .shipment-filter-main .filter-input,
    body[data-theme="dark"] .shipment-filter-main .filter-select,
    body[data-theme="dark"] .shipment-date-control .date-section,
    body[data-theme="dark"] .table-list thead th,
    body[data-theme="dark"] .code-link{
        background:#111827;
        border-color:rgba(71,85,105,.75);
    }
    body[data-theme="dark"] .table-list tbody td,
    body[data-theme="dark"] .list-footer{ border-color:rgba(51,65,85,.75); }

    @media (max-width:768px){
        .page-wrap{ max-width:none; padding:.5rem .5rem 3.5rem; }
        .ship-topbar{ margin:0 -.5rem .6rem; padding:.65rem; border-radius:0 0 10px 10px; }
        .filter-bar{ padding:.55rem; }
        .shipment-filter-main .filter-input,
        .shipment-filter-main .filter-select,
        .shipment-date-control .date-section{ min-height:40px; }
        .shipment-date-control .date-section{ height:40px!important; }
        .shipment-date-control .rts-date-picker.flatpickr-input{ height:40px!important; }
        .card-main{ border-radius:9px; }
    }

    /* Soft color accents: clear grouping without visual noise */
    .ship-topbar{
        background:linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        border-top-color:#bfdbfe;
        border-left:3px solid #60a5fa;
    }
    .ship-topbar .title{ color:#172554; }
    .kpi{ background:#f8fafc; }
    .kpi .val{ color:#334155; }
    .kpi:first-child{ background:#eff6ff; border-color:#bfdbfe; }
    .kpi:first-child .val{ color:#2563eb; }
    .kpi-draft{ background:#fffbeb; border-color:#fde68a; }
    .kpi-draft .val{ color:#b45309; }
    .kpi-submitted{ background:#f5f3ff; border-color:#ddd6fe; }
    .kpi-submitted .val{ color:#6d28d9; }
    .kpi-posted{ background:#ecfdf5; border-color:#bbf7d0; }
    .kpi-posted .val{ color:#15803d; }
    .filter-bar{ background:linear-gradient(135deg, #ffffff 0%, #fbfdff 100%); }
    .filter-input-wrap > i{ color:#60a5fa; }
    .filter-input:focus{ border-color:#93c5fd!important; box-shadow:0 0 0 3px rgba(96,165,250,.13)!important; }
    .filter-select:focus{ border-color:#c4b5fd!important; box-shadow:0 0 0 3px rgba(139,92,246,.12)!important; }
    .shipment-date-control .date-section{ border-color:#bfdbfe!important; background:#f8fbff; }
    .table-list thead th{ background:#f7faff; color:#536987; }
    .table-list tbody tr:nth-child(even){ background:#fcfdff; }
    .table-list tbody tr:nth-child(even):hover,
    .table-list tbody tr:hover{ background:#f1f7ff!important; }
    .code-link{ background:#eef5ff; border-color:#cfe1ff; color:#1d4ed8!important; }
    .code-link:hover{ background:#e0edff; border-color:#b8d2ff; color:#1e40af!important; }
    .mode-item{ color:#0f766e; background:#ecfdf5; border-color:#99f6e4; }
    .mode-order{ color:#6d28d9; background:#f5f3ff; border-color:#ddd6fe; }
    .list-count{ background:#eff6ff; border-color:#bfdbfe; color:#2563eb; }
    .empty-icon{ background:#eff6ff; color:#2563eb; }
    .btn-ship-primary{ background:#2563eb!important; border-color:#2563eb!important; }
    .btn-ship-primary:hover{ background:#1d4ed8!important; border-color:#1d4ed8!important; }
    .btn-ship-outline{ color:#475569!important; border-color:#cbd5e1!important; }
    .btn-ship-outline:hover{ color:#1d4ed8!important; background:#eff6ff!important; border-color:#bfdbfe!important; }

    body[data-theme="dark"] .ship-topbar{
        background:linear-gradient(135deg, #111827 0%, #172554 100%);
        border-top-color:#1d4ed8;
        border-left-color:#60a5fa;
    }
    body[data-theme="dark"] .ship-topbar .title{ color:#dbeafe; }
    body[data-theme="dark"] .kpi:first-child{ background:rgba(30,64,175,.25); border-color:rgba(96,165,250,.4); }
    body[data-theme="dark"] .kpi-draft{ background:rgba(120,53,15,.25); border-color:rgba(245,158,11,.35); }
    body[data-theme="dark"] .kpi-submitted{ background:rgba(76,29,149,.25); border-color:rgba(167,139,250,.35); }
    body[data-theme="dark"] .kpi-posted{ background:rgba(20,83,45,.25); border-color:rgba(74,222,128,.35); }
    body[data-theme="dark"] .filter-bar{ background:linear-gradient(135deg, #111827 0%, #172033 100%); }
    body[data-theme="dark"] .shipment-date-control .date-section{ background:#172033; border-color:rgba(96,165,250,.45)!important; }
    body[data-theme="dark"] .table-list thead th{ background:#172033; }
    body[data-theme="dark"] .table-list tbody tr:nth-child(even){ background:rgba(30,41,59,.35); }
    body[data-theme="dark"] .table-list tbody tr:nth-child(even):hover,
    body[data-theme="dark"] .table-list tbody tr:hover{ background:rgba(30,64,175,.18)!important; }
    body[data-theme="dark"] .code-link{ background:rgba(30,64,175,.25); border-color:rgba(96,165,250,.4); color:#bfdbfe!important; }

    /* Neutral primary accent */
    .ship-topbar{
        background:linear-gradient(135deg, #ffffff 0%, #fafafa 100%);
        border-top-color:#d1d5db;
        border-left-color:#111827;
    }
    .ship-topbar .title{ color:#111827; }
    .filter-bar{ background:linear-gradient(135deg, #ffffff 0%, #fafafa 100%); }
    .filter-input-wrap > i{ color:#111827; }
    .filter-input:focus,
    .filter-select:focus{ border-color:#6b7280!important; box-shadow:0 0 0 3px rgba(17,24,39,.1)!important; }
    .shipment-date-control .date-section{ border-color:#d1d5db!important; background:#fff; }
    .table-list thead th{ background:#fafafa; color:#4b5563; }
    .table-list tbody tr:nth-child(even):hover,
    .table-list tbody tr:hover{ background:#f5f5f5!important; }
    .code-link{ background:#f3f4f6; border-color:#d1d5db; color:#111827!important; }
    .code-link:hover{ background:#e5e7eb; border-color:#9ca3af; color:#030712!important; }
    .list-count{ background:#f3f4f6; border-color:#d1d5db; color:#111827; }
    .empty-icon{ background:#f3f4f6; color:#111827; }
    .btn-ship-primary{ background:#111827!important; border-color:#111827!important; }
    .btn-ship-primary:hover{ background:#000!important; border-color:#000!important; }
    .btn-ship-outline{ color:#374151!important; border-color:#cbd5e1!important; }
    .btn-ship-outline:hover{ color:#111827!important; background:#f3f4f6!important; border-color:#9ca3af!important; }

    body[data-theme="dark"] .ship-topbar{
        background:linear-gradient(135deg, #111827 0%, #0f172a 100%);
        border-top-color:#374151;
        border-left-color:#f3f4f6;
    }
    body[data-theme="dark"] .ship-topbar .title{ color:#f9fafb; }
    body[data-theme="dark"] .filter-bar{ background:linear-gradient(135deg, #111827 0%, #0f172a 100%); }
    body[data-theme="dark"] .filter-input-wrap > i{ color:#f3f4f6; }
    body[data-theme="dark"] .shipment-date-control .date-section{ border-color:#4b5563!important; background:#172033; }
    body[data-theme="dark"] .table-list thead th{ background:#111827; }
    body[data-theme="dark"] .table-list tbody tr:nth-child(even):hover,
    body[data-theme="dark"] .table-list tbody tr:hover{ background:rgba(255,255,255,.06)!important; }
    body[data-theme="dark"] .code-link{ background:#1f2937; border-color:#4b5563; color:#f3f4f6!important; }
    body[data-theme="dark"] .list-count,
    body[data-theme="dark"] .empty-icon{ background:#1f2937; border-color:#4b5563; color:#f3f4f6; }
    body[data-theme="dark"] .shipment-time-cell{ color:#94a3b8; }
    body[data-theme="dark"] .shipment-time-stack small{ color:#64748b; }

    /* Final enterprise polish */
    .ship-topbar,
    .filter-bar,
    .card-main{
        background:#fff;
        border-radius:8px;
        box-shadow:0 1px 4px rgba(15,23,42,.035);
    }
    .ship-topbar{ border-left-width:1px; padding:.75rem .85rem; }
    .ship-topbar .title{ font-size:1.05rem; letter-spacing:-.01em; }
    .ship-topbar .sub{ font-size:.74rem; }
    .filter-bar{ padding:.55rem; }
    .filter-summary{ font-size:.7rem; letter-spacing:.01em; }
    .filter-summary strong{ font-weight:650; }
    .table-list thead th{
        background:#f3f4f6;
        color:#4b5563;
        border-top:1px solid #e5e7eb;
        border-bottom:1px solid #d1d5db;
        padding-top:.7rem;
        padding-bottom:.7rem;
        font-size:.61rem;
        font-weight:750;
        letter-spacing:.06em;
    }
    .table-list thead th:first-child{ border-top-left-radius:6px; }
    .table-list thead th:last-child{ border-top-right-radius:6px; }
    .table-list thead .table-sort{ color:#4b5563; }
    .table-list thead .table-sort:hover,
    .table-list thead .table-sort.active{ color:#111827; }
    .table-list tbody tr{ transition:background-color .12s ease; }
    .table-list tbody td{ padding-top:.65rem; padding-bottom:.65rem; }
    .shipment-time-stack span+span{
        padding-top:.18rem;
        border-top:1px solid #eef0f3;
    }
    .shipment-time-cell{ color:#4b5563; }
    .shipment-time-stack{ letter-spacing:.005em; }
    .shipment-channel-stack{ display:flex; flex-direction:column; gap:.18rem; line-height:1.25; }
    .shipment-channel-type{ color:#374151; font-size:.72rem; font-weight:700; }
    .shipment-channel-store{ color:#94a3b8; font-size:.68rem; }
    .shipment-created-cell{ color:#64748b; font-size:.72rem; white-space:nowrap; }
    .shipment-created-stack{ display:flex; flex-direction:column; gap:.1rem; line-height:1.2; }
    .shipment-created-head{ display:flex; align-items:center; gap:.35rem; }
    .shipment-created-date{ color:#111827; font-size:.78rem; font-weight:750; letter-spacing:-.01em; }
    .shipment-created-status{ display:flex; align-items:center; }
    .shipment-created-status .badge-status{ padding:.1rem .32rem; font-size:.54rem; opacity:.82; }
    .shipment-created-updated{ color:#9ca3af; font-size:.56rem; letter-spacing:0; }
    .package-number-cell{ color:#475569; font-size:.74rem; font-weight:650; font-variant-numeric:tabular-nums; }
    .shipment-dispatch-cell{ white-space:nowrap; }
    .dispatch-badge{
        display:inline-flex;
        align-items:center;
        min-height:24px;
        padding:.18rem .45rem;
        border:1px solid #e5e7eb;
        border-radius:5px;
        font-size:.64rem;
        font-weight:700;
        color:#64748b;
        background:#f8fafc;
    }
    .dispatch-daily{ color:#475569; background:#f3f4f6; border-color:#d1d5db; }
    .dispatch-single{ color:#64748b; background:#fff; border-color:#e5e7eb; }
    .badge-status{ border-radius:6px; font-weight:650; }
    .action-icon{ border-radius:6px!important; }

    body[data-theme="dark"] .ship-topbar,
    body[data-theme="dark"] .filter-bar,
    body[data-theme="dark"] .card-main{ background:#111827; box-shadow:none; }
    body[data-theme="dark"] .table-list thead th{
        background:#1f2937;
        color:#cbd5e1;
        border-top-color:#374151;
        border-bottom-color:#4b5563;
    }
    body[data-theme="dark"] .table-list thead .table-sort,
    body[data-theme="dark"] .table-list thead .table-sort:hover,
    body[data-theme="dark"] .table-list thead .table-sort.active{ color:#e5e7eb; }
    body[data-theme="dark"] .shipment-time-stack span+span{ border-top-color:#273449; }
    body[data-theme="dark"] .shipment-channel-type{ color:#e5e7eb; }
    body[data-theme="dark"] .shipment-channel-store{ color:#94a3b8; }
    body[data-theme="dark"] .shipment-channel-inline{ color:#94a3b8; }
    body[data-theme="dark"] .shipment-created-cell{ color:#94a3b8; }
    body[data-theme="dark"] .shipment-created-date{ color:#f3f4f6; }
    body[data-theme="dark"] .shipment-created-updated{ color:#64748b; }
    body[data-theme="dark"] .package-number-cell{ color:#cbd5e1; }
    body[data-theme="dark"] .dispatch-badge{ color:#cbd5e1; background:#1f2937; border-color:#4b5563; }
    body[data-theme="dark"] .dispatch-daily{ color:#f3f4f6; background:#374151; }

    /* Action column: explicit labels with compact enterprise controls */
    .ship-row-action{ min-width:160px; }
    .ship-row-action .d-flex{ gap:.3rem!important; align-items:center; }
    .ship-row-action form{ display:inline-flex; }
    .action-button{
        display:inline-flex!important;
        align-items:center;
        justify-content:center;
        gap:.3rem;
        min-width:68px;
        min-height:31px;
        padding:.2rem .55rem!important;
        border-radius:6px!important;
        font-size:.7rem;
        font-weight:650;
        line-height:1;
        white-space:nowrap;
        transition:background-color .12s ease, border-color .12s ease, color .12s ease, transform .12s ease;
    }
    .action-button i{ margin:0!important; font-size:.8rem; }
    .action-button:hover{ transform:translateY(-1px); }
    .action-button-text{
        display:inline;
        margin:0;
        font-size:.7rem;
        font-weight:650;
        line-height:1;
    }
    .ship-row-action .action-button.action-icon{ min-width:32px; width:32px!important; padding:0!important; }
    .ship-row-action .action-button.action-icon i{ font-size:.8rem!important; }
    .ship-row-action .action-button.action-icon .action-button-text{ display:none; }
    .ship-row-action .btn-ship-outline.action-button{ color:#64748b!important; }
    .ship-row-action .btn-ship-outline.action-button .action-button-text{ color:#64748b; }
    .ship-row-action .btn-ship-outline.action-button:hover,
    .ship-row-action .btn-ship-outline.action-button:hover .action-button-text{ color:#334155!important; }
    .ship-row-action .btn-ship-primary.action-button .action-button-text{ color:#fff; }
    .ship-row-action .btn-outline-danger.action-button{
        color:#b45353!important;
        background:#fffafa!important;
        border-color:#f1d0d0!important;
    }
    .ship-row-action .btn-outline-danger.action-button:hover{
        color:#991b1b!important;
        background:#fef2f2!important;
        border-color:#e8b4b4!important;
    }
    .ship-row-action .btn-outline-danger.action-button .action-button-text{ color:#b45353; }
    .ship-row-action .btn-outline-danger.action-button:hover .action-button-text{ color:#991b1b; }

    /* Pagination: compact, quiet, and aligned with the table */
    .list-footer{ align-items:center; }
    .list-footer > .muted{ color:#94a3b8; font-size:.68rem; white-space:nowrap; }
    .list-footer nav{ display:flex; align-items:center; }
    .list-footer .pagination{
        display:flex;
        align-items:center;
        flex-wrap:wrap;
        gap:.25rem;
    }
    .list-footer .pagination .page-item{ margin:0; }
    .list-footer .pagination .page-link{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width:30px;
        height:30px;
        padding:.2rem .45rem;
        border:1px solid #e5e7eb;
        border-radius:6px!important;
        background:#fff;
        color:#64748b;
        font-size:.7rem;
        font-weight:650;
        line-height:1;
        box-shadow:none;
        transition:background-color .12s ease, border-color .12s ease, color .12s ease;
    }
    .list-footer .pagination .page-link:hover{
        background:#f3f4f6;
        border-color:#d1d5db;
        color:#111827;
    }
    .list-footer .pagination .page-item.active .page-link{
        background:#111827;
        border-color:#111827;
        color:#fff;
    }
    .list-footer .pagination .page-item.disabled .page-link{
        background:#f8fafc;
        border-color:#eef0f3;
        color:#cbd5e1;
    }
    body[data-theme="dark"] .list-footer > .muted{ color:#64748b; }
    body[data-theme="dark"] .list-footer .pagination .page-link{
        background:#111827;
        border-color:#374151;
        color:#94a3b8;
    }
    body[data-theme="dark"] .list-footer .pagination .page-link:hover{
        background:#1f2937;
        border-color:#4b5563;
        color:#f3f4f6;
    }
    body[data-theme="dark"] .list-footer .pagination .page-item.active .page-link{
        background:#f3f4f6;
        border-color:#f3f4f6;
        color:#111827;
    }
    body[data-theme="dark"] .list-footer .pagination .page-item.disabled .page-link{
        background:#172033;
        border-color:#273449;
        color:#475569;
    }

    @media (max-width:768px){
        .ship-row-action{ min-width:0; }
        .ship-row-action .d-flex{ width:100%; display:grid!important; grid-template-columns:repeat(auto-fit, minmax(0, 1fr)); }
        .ship-row-action form{ width:100%; }
        .ship-row-action .action-button{ width:100%!important; min-height:37px; }
        .ship-row-action .action-button.action-icon .action-button-text{ display:inline; }
        .list-footer > .muted{ display:block; }
        .list-footer nav{ margin-top:.6rem; overflow-x:auto; padding-bottom:.1rem; }
        .list-footer .pagination{ flex-wrap:nowrap; }
    }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
@endpush

@section('content')
<div class="page-wrap">
    @if(isset($isDummy) && $isDummy)
    <div style="background-color: #fff3cd; color: #856404; padding: 10px 15px; border-radius: 6px; border: 1px solid #ffeeba; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <strong>🧪 MENGGUNAKAN DUMMY MODE</strong><br>
            <span style="font-size: 0.9em;">Halaman ini sedang berada dalam mode dummy pengujian UI.</span>
        </div>
        <a href="?" style="background: #856404; color: #fff; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 0.85em; font-weight: bold;">Keluar Dummy</a>
    </div>
    @endif
    @php
        use Illuminate\Support\Carbon;

        $fmtDate = function ($value, string $format = 'd M Y', string $fallback = '-') {
            if (empty($value)) return $fallback;
            try {
                if ($value instanceof \DateTimeInterface) return $value->format($format);
                return Carbon::parse($value)->format($format);
            } catch (\Throwable $e) {
                return $fallback;
            }
        };

        $statusFilter = $statusFilter ?? request('status', 'all');
        $canSeeNominal = $canSeeNominal ?? ((auth()->user()->role ?? null) !== 'admin');
        $userRole = auth()->user()->role ?? null;
        $isAdmin = $userRole === 'admin';
        $isOwner = $userRole === 'owner';
        $useCompactIndexColumns = $isAdmin || $isOwner;
        $canSeeShipmentTime = !$useCompactIndexColumns;
        $canFreshShipments = $canFreshShipments ?? false;

        $sortUrl = function (string $column) use ($sort, $direction) {
            $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
            return request()->fullUrlWithQuery([
                'sort' => $column,
                'direction' => $nextDirection,
                'page' => 1,
            ]);
        };
        $sortIcon = function (string $column) use ($sort, $direction) {
            if ($sort !== $column) return '↕';
            return $direction === 'asc' ? '↑' : '↓';
        };
    @endphp

    @if(session('message'))
        <div class="flash-clean alert alert-{{ session('status') === 'success' ? 'success' : 'danger' }} mb-2">
            {{ session('message') }}
        </div>
    @endif

    <div class="ship-topbar">
        <div class="ship-heading">
            <div class="title">Daftar Shipment</div>

            <div class="kpis">
                <span class="kpi"><span class="lbl">Semua</span><span class="val">{{ number_format($kpi['total'], 0, ',', '.') }}</span></span>
                <span class="kpi kpi-draft"><span class="lbl">Draft</span><span class="val">{{ number_format($kpi['draft'], 0, ',', '.') }}</span></span>
                <span class="kpi kpi-submitted"><span class="lbl">Diproses</span><span class="val">{{ number_format($kpi['submitted'], 0, ',', '.') }}</span></span>
                <span class="kpi kpi-posted"><span class="lbl">Selesai</span><span class="val">{{ number_format($kpi['posted'], 0, ',', '.') }}</span></span>
            </div>
        </div>

        <div class="header-actions">
            @if($canFreshShipments)
                <form method="POST" action="{{ route('sales.shipments.dev_fresh') }}"
                      onsubmit="return confirm('Fresh semua data shipment? Aksi ini hanya untuk database dev.');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger btn-pill btn-fresh">
                        Fresh Data
                    </button>
                </form>
            @endif

            <a href="{{ route('sales.shipments.create') }}" class="btn btn-sm btn-ship-primary btn-pill">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Shipment Baru
            </a>
            <a href="{{ route('sales.shipments.create', ['type' => 'affiliate']) }}" class="btn btn-sm btn-ship-outline btn-pill">
                <i class="bi bi-people" aria-hidden="true"></i> Kirim Paket Afiliasi
            </a>
        </div>
    </div>

    @if(isset($staleDrafts) && $staleDrafts->count() > 0)
        <div class="stale-draft-alert mb-2" role="status">
            <i class="bi bi-clock-history" aria-hidden="true"></i>
            <strong>Draft lama:</strong>
            <span>{{ $staleDrafts->count() }} shipment · {{ number_format($staleDrafts->sum('total_allocated'), 0, ',', '.') }} unit stok</span>
            <span>·</span>
            @foreach($staleDrafts->take(5) as $sd)
                <a href="{{ route('sales.shipments.edit', $sd) }}">{{ $sd->code }}</a>{{ !$loop->last ? ',' : '' }}
            @endforeach
            @if($staleDrafts->count() > 5) <span>dan lainnya</span> @endif
        </div>
    @endif

    <form id="shipment-filter-form" method="GET" class="shipment-filter-form">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">

        <div class="filter-bar">
            <div class="shipment-filter-main">
                <div class="filter-input-wrap">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" name="q" value="{{ $search }}" class="form-control form-control-sm filter-input" placeholder="Cari shipment, order, atau resi..." autocomplete="off" aria-label="Cari kode shipment, order, atau resi">
                </div>
                <select name="status" class="form-select form-select-sm filter-select" aria-label="Filter status">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="draft" {{ $statusFilter === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="submitted" {{ $statusFilter === 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="posted" {{ $statusFilter === 'posted' ? 'selected' : '' }}>Posted</option>
                    <option value="cancelled" {{ $statusFilter === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
                <select name="scan_mode" class="form-select form-select-sm filter-select" aria-label="Filter mode scan">
                    <option value="all" {{ $scanMode === 'all' ? 'selected' : '' }}>Semua Mode Scan</option>
                    <option value="order_first" {{ $scanMode === 'order_first' ? 'selected' : '' }}>Scan Order Dulu</option>
                    <option value="item_first" {{ $scanMode === 'item_first' ? 'selected' : '' }}>Scan Item Dulu</option>
                </select>
                <div class="shipment-date-control">
                    <x-date-range-picker
                        :date-from="$dateFrom"
                        :date-to="$dateTo"
                        :period="$period"
                        form-id="shipment-filter-form"
                        name-from="date_from"
                        name-to="date_to"
                        :show-presets="false"
                    />
                </div>
                @if($search || $statusFilter !== 'all' || $scanMode !== 'all' || $dateFrom || $dateTo || $period !== 'all')
                    <a href="{{ route('sales.shipments.index') }}" class="btn btn-sm btn-ship-outline btn-pill">Reset Filter</a>
                @endif
            </div>
        </div>
    </form>

    @if($shipments->count() > 0)
        <div class="filter-summary mb-2 px-1">
            Terakhir dibuat <strong>{{ $fmtDate($shipments->first()->created_at, 'd M Y H:i') }} WIB</strong>
        </div>
    @endif

    <div class="card card-main">
        <div class="card-body p-0">
            @if ($shipments->count() === 0)
                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></div>
                    <div class="empty-title">Belum ada shipment yang cocok</div>
                    <div class="empty-sub">Coba ubah filter atau klik <b>Shipment Baru</b> untuk mulai.</div>
                    @if($search || $statusFilter !== 'all' || $scanMode !== 'all' || $dateFrom || $dateTo || $period !== 'all')
                        <a href="{{ route('sales.shipments.index') }}" class="btn btn-sm btn-ship-outline btn-pill mt-3">Hapus Filter</a>
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-list">
                        <thead>
                            <tr>
                                @if($useCompactIndexColumns)
                                    <th style="width: 135px;">Dibuat</th>
                                @else
                                    <th style="width: 46px;">#</th>
                                @endif
                                <th style="min-width: 150px;"><a class="table-sort {{ $sort === 'code' ? 'active' : '' }}" href="{{ $sortUrl('code') }}">Dokumen <span class="table-sort-icon">{{ $sortIcon('code') }}</span></a></th>
                                @if($canSeeShipmentTime)
                                    <th style="width: 170px;">Waktu</th>
                                @endif
                                @if($useCompactIndexColumns)
                                    <th style="width: 110px;">Pengiriman</th>
                                @endif
                                <th class="text-end" style="width: 72px;">Order</th>
                                <th class="text-end" style="width: 72px;"><a class="table-sort {{ $sort === 'lines_count' ? 'active' : '' }}" href="{{ $sortUrl('lines_count') }}">Item <span class="table-sort-icon">{{ $sortIcon('lines_count') }}</span></a></th>
                                <th class="text-end" style="width: 64px;">Qty</th>
                                <th class="text-end" style="width: 160px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($shipments as $shipment)
                                @php
                                    $qty = (int) ($shipment->total_qty_calc ?? 0);
                                    $orderCount = (int) ($shipment->order_scans_count ?? 0);
                                    $itemCount = (int) ($shipment->lines_count ?? $shipment->lines->count());

                                    $isCancelled = !empty($shipment->cancelled_at);
                                    $uiStatus = $isCancelled ? 'cancelled' : ($shipment->status ?? 'submitted');

                                    $statusClass = match ($uiStatus) {
                                        'draft' => 'st-draft',
                                        'submitted' => 'st-submitted',
                                        'posted' => 'st-posted',
                                        'cancelled' => 'st-cancelled',
                                        default => 'st-submitted',
                                    };

                                    $statusLabel = match ($uiStatus) {
                                        'draft' => 'Draft',
                                        'submitted' => 'Diproses',
                                        'posted' => 'Posted',
                                        'cancelled' => 'Dibatalkan',
                                        default => ucfirst($uiStatus),
                                    };
                                    // Mode scan adalah pilihan awal shipment. Jumlah order yang
                                    // sudah tercatat tidak boleh mengubah label mode menjadi order_first.
                                    $isOrderFirst = ($shipment->scan_mode ?? 'item_first') === 'order_first';
                                    $typeLabel = match ($shipment->shipment_type) {
                                        'affiliate' => 'Afiliasi',
                                        'marketplace' => 'Marketplace',
                                        default => 'Manual',
                                    };
                                    $dispatchLabel = ($shipment->dispatch_mode ?? 'single') === 'daily'
                                        ? 'Daily Wave'
                                        : 'Single';
                                    $dispatchClass = $dispatchLabel === 'Daily Wave' ? 'dispatch-daily' : 'dispatch-single';
                                    $stockBlocked = !empty($shipment->stock_insufficient_calc);
                                    $draftActionRoute = ($shipment->shipment_type === 'affiliate' || $isOrderFirst)
                                        ? 'sales.shipments.scan_order'
                                        : 'sales.shipments.edit';
                                    $actionRoute = $uiStatus === 'draft'
                                        ? route($draftActionRoute, $shipment)
                                        : route('sales.shipments.show', $shipment);
                                    $actionLabel = $uiStatus === 'draft' ? 'Lanjut scan' : 'Lihat detail';
                                @endphp

                                <tr class="shipment-row-clickable {{ $uiStatus === 'draft' ? 'row-draft' : '' }}"
                                    data-row-href="{{ $actionRoute }}" tabindex="0" role="link"
                                    aria-label="{{ $actionLabel }} {{ $shipment->code }}">
                                    @if($useCompactIndexColumns)
                                        <td class="shipment-created-cell" aria-label="Tanggal dibuat dan kanal">
                                            <div class="shipment-created-stack">
                                                <span class="shipment-created-date">{{ $fmtDate($shipment->created_at, 'd M Y') }}</span>
                                                <span class="shipment-created-updated">
                                                    {{ $fmtDate($shipment->updated_at ?? $shipment->created_at, 'd M Y H:i') }} WIB
                                                </span>
                                            </div>
                                        </td>
                                    @else
                                        <td class="text-muted small mobile-hide">
                                            {{ ($shipments->currentPage() - 1) * $shipments->perPage() + $loop->iteration }}
                                        </td>
                                    @endif

                                    <td>
                                        <div class="ship-row-main">
                                            <div class="shipment-inline">
                                                <div class="shipment-document-head">
                                                    <a class="code-link" href="{{ $actionRoute }}">
                                                        {{ $shipment->code }}
                                                    </a>
                                                </div>

                                                <div class="shipment-document-meta">
                                                    <span class="badge-status {{ $statusClass }}">{{ $statusLabel }}</span>
                                                    <span class="shipment-channel-inline">{{ $typeLabel }}@if($shipment->store) · {{ $shipment->store->name ?: $shipment->store->code }}@endif</span>
                                                </div>

                                                <div class="ship-row-meta d-md-none">
                                                    <span>{{ number_format($orderCount, 0, ',', '.') }} order</span>
                                                    <span>{{ number_format($itemCount, 0, ',', '.') }} item</span>
                                                    <span>{{ number_format($qty, 0, ',', '.') }} qty</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    @if($canSeeShipmentTime)
                                        <td class="shipment-time-cell">
                                            <div class="shipment-time-stack" aria-label="Waktu shipment">
                                                <span>{{ $fmtDate($shipment->created_at, 'd M Y H:i') }} WIB</span>
                                                @if($shipment->posted_at)
                                                    <span>{{ $fmtDate($shipment->posted_at, 'd M Y H:i') }} WIB</span>
                                                @elseif($shipment->updated_at)
                                                    <span>{{ $fmtDate($shipment->updated_at, 'd M Y H:i') }} WIB</span>
                                                @endif
                                            </div>
                                        </td>
                                    @endif

                                    @if($useCompactIndexColumns)
                                        <td class="shipment-dispatch-cell">
                                            <span class="dispatch-badge {{ $dispatchClass }}">{{ $dispatchLabel }}</span>
                                        </td>
                                    @endif

                                    <td class="text-end mobile-hide package-number-cell" aria-label="Total order">
                                        {{ number_format($orderCount, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end mobile-hide package-number-cell" aria-label="Jumlah item">
                                        {{ number_format($itemCount, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end mobile-hide package-number-cell" aria-label="Total qty">
                                        {{ number_format($qty, 0, ',', '.') }}
                                    </td>

                                    <td class="text-end ship-row-action">
                                        @if($shipment->shipment_type === 'manual')
                                            @php 
                                                $recv = ['nama' => '-', 'phone' => '-', 'alamat' => '-'];
                                                if ($shipment->notes) {
                                                    $decoded = json_decode($shipment->notes, true);
                                                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                        $recv = array_merge($recv, $decoded);
                                                    }
                                                }
                                                $linesJson = $shipment->lines->map(fn($l) => [
                                                    'code' => $l->item->code ?? '', 
                                                    'name' => $l->item->name ?? '', 
                                                    'qty' => $l->qty_scanned
                                                ])->toJson();
                                            @endphp
                                            <div class="d-flex justify-content-end gap-1 flex-wrap">
                                                <button type="button" class="btn btn-sm btn-ship-outline btn-pill action-button"
                                                    data-code="{{ $shipment->code }}"
                                                    data-date="{{ $fmtDate($shipment->date, 'd M Y') }}"
                                                    data-nama="{{ $recv['nama'] }}"
                                                    data-phone="{{ $recv['phone'] }}"
                                                    data-alamat="{{ $recv['alamat'] }}"
                                                    data-items="{{ $linesJson }}"
                                                    onclick="openPreview(this)" title="Preview label" aria-label="Preview label">
                                                    <i class="bi bi-printer" aria-hidden="true"></i>
                                                    <span class="action-button-text">Preview</span>
                                                </button>
                                                
                                                @if($uiStatus === 'draft')
                                                    <form action="{{ route('sales.shipments.manual.post', $shipment) }}" method="POST" class="d-inline"
                                                          data-gf-confirm
                                                          data-gf-confirm-title="Kirim paket manual?"
                                                          data-gf-confirm-summary='@json(["orders" => (int) ($shipment->order_scans_count ?? 0), "items" => $shipment->lines->count(), "qty" => $qty])'
                                                          data-gf-confirm-text="Order discan: {{ (int) ($shipment->order_scans_count ?? 0) }} · Item: {{ $shipment->lines->count() }} · Total qty: {{ $qty }}. Stok WH-RTS akan dipotong."
                                                          data-gf-confirm-ok="Kirim"
                                                          data-gf-confirm-cancel="Batal">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-ship-primary btn-pill action-button" title="{{ $stockBlocked ? 'Stok WH-RTS tidak cukup' : 'Kirim/Post' }}" aria-label="{{ $stockBlocked ? 'Stok WH-RTS tidak cukup' : 'Kirim paket' }}" @disabled($stockBlocked)>
                                                            <i class="bi bi-send" aria-hidden="true"></i>
                                                            <span class="action-button-text">Kirim</span>
                                                        </button>
                                                    </form>
                                                    
                                                    <form action="{{ route('sales.shipments.manual.destroy', $shipment) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus paket manual ini?');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-pill action-button" style="border-color:#fecaca;" title="Hapus shipment" aria-label="Hapus shipment">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                            <span class="action-button-text">Hapus</span>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($isOwner && $uiStatus !== 'draft')
                                                    <a href="{{ route('sales.shipments.show', $shipment) }}" class="btn btn-sm btn-ship-outline btn-pill action-button action-icon" title="Detail shipment" aria-label="Detail shipment {{ $shipment->code }}">
                                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                                        <span class="action-button-text">Detail</span>
                                                    </a>
                                                @endif
                                                @if($isOwner && $uiStatus !== 'cancelled')
                                                    <a href="{{ route('sales.shipments.cancel_form', $shipment) }}" class="btn btn-sm btn-outline-danger btn-pill action-button action-icon" title="Batalkan shipment" aria-label="Batalkan shipment {{ $shipment->code }}">
                                                        <i class="bi bi-x-circle" aria-hidden="true"></i>
                                                        <span class="action-button-text">Batalkan</span>
                                                    </a>
                                                @endif
                                            </div>
                                        @else
                                            <div class="d-flex justify-content-end gap-1 flex-wrap">
                                                <a href="{{ $actionRoute }}" class="btn btn-sm {{ $uiStatus === 'draft' ? 'btn-ship-primary' : 'btn-ship-outline' }} btn-pill action-button" title="{{ $actionLabel }}" aria-label="{{ $actionLabel }} {{ $shipment->code }}">
                                                    <i class="bi {{ $uiStatus === 'draft' ? 'bi-upc-scan' : 'bi-eye' }}" aria-hidden="true"></i>
                                                    <span class="action-button-text">{{ $uiStatus === 'draft' ? 'Lanjut scan' : 'Lihat detail' }}</span>
                                                </a>
                                                @if($isOwner && $uiStatus === 'draft')
                                                    <a href="{{ route('sales.shipments.show', $shipment) }}" class="btn btn-sm btn-ship-outline btn-pill action-button action-icon" title="Detail shipment" aria-label="Detail shipment {{ $shipment->code }}">
                                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                                        <span class="action-button-text">Detail</span>
                                                    </a>
                                                @endif
                                                @if($isOwner && $uiStatus !== 'cancelled')
                                                    <a href="{{ route('sales.shipments.cancel_form', $shipment) }}" class="btn btn-sm btn-outline-danger btn-pill action-button action-icon" title="Batalkan shipment" aria-label="Batalkan shipment {{ $shipment->code }}">
                                                        <i class="bi bi-x-circle" aria-hidden="true"></i>
                                                        <span class="action-button-text">Batalkan</span>
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="divider"></div>

                <div class="list-footer">
                    <span class="muted">Menampilkan {{ $shipments->firstItem() }}–{{ $shipments->lastItem() }} dari {{ $shipments->total() }}</span>
                    {{ $shipments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- MODAL PREVIEW LABEL (Manual Shipment) -->
<div class="ms-modal-backdrop" id="modalPreview">
    <div class="ms-modal-content">
        <div class="ms-modal-header no-print">
            <h3><i class="bi bi-zoom-in"></i> Preview Label Cetak</h3>
            <button class="ms-modal-close" onclick="closePreview()">&times;</button>
        </div>
        
        <div class="ms-modal-body" id="previewArea">
            <div class="label-wrap" id="labelWrap">
                <!-- Label Header -->
                <div class="label-header">
                    <img src="{{ asset('images/logo-mark.svg') }}" alt="GF Logo" class="logo-svg" style="filter: brightness(0); width: 36px; height: 36px;">
                    <div>
                        <div class="brand-text">GREATFIT.ID</div>
                        <div class="brand-sub">Manual Shipping Label</div>
                    </div>
                </div>

                <!-- Resi Placeholder -->
                <div class="label-section" style="padding: 12px 14px; text-align: center;">
                    <div style="border: 2px dashed #000; min-height: 90px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #64748b; letter-spacing: 1.5px; font-size: 11px; text-transform: uppercase;">
                        [ TEMPEL / TULIS NO RESI DI SINI ]
                    </div>
                </div>

                <!-- Pengirim -->
                <div class="label-section">
                    <div class="label-section-title">✉ Pengirim</div>
                    <div class="label-name" id="lblSenderName">GREATFIT.ID</div>
                    <div class="label-phone">
                        <span class="phone-icon">📞</span>
                        <span id="lblSenderPhone">081224889319</span>
                    </div>
                </div>

                <!-- Arrow Divider -->
                <div class="label-divider">
                    <span class="arrow-icon">▼</span>
                </div>

                <!-- Penerima -->
                <div class="label-section">
                    <div class="label-section-title">📍 Penerima</div>
                    <div class="label-name" id="lblRecvName">—</div>
                    <div class="label-phone">
                        <span class="phone-icon">📞</span>
                        <span id="lblRecvPhone">—</span>
                    </div>
                    <div class="label-address" id="lblRecvAddress">—</div>
                </div>
                
                <!-- Items Summary -->
                <div class="label-section">
                    <div class="label-section-title">📦 Daftar Item</div>
                    <div class="label-items" id="lblItemsList">
                        <!-- Items will be injected here -->
                    </div>
                </div>

                <!-- Promo Section -->
                <div class="label-promo">
                    <div class="label-promo-title">✨ Kunjungi Kami ✨</div>
                    <div class="label-promo-content">
                        <div class="label-qr" id="labelQrCode"></div>
                        <div class="label-promo-info">
                            <div class="label-promo-url">www.greatfit.id</div>
                            <div class="label-promo-socials">
                                <div class="label-promo-social-item">
                                    <img src="{{ asset('img/social/IG.png') }}" alt="IG"> @greatfit.id
                                </div>
                                <div class="label-promo-social-item">
                                    <img src="{{ asset('img/social/WA.png') }}" alt="WA"> 081224889319
                                </div>
                                <div class="label-promo-social-item">
                                    <img src="{{ asset('img/social/TT.png') }}" alt="TT"> @greatfit.id
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="label-footer" style="display:flex; justify-content:space-between; align-items:center; padding: 8px 14px;">
                    <span id="lblShipmentCode" style="font-weight: 900; letter-spacing: 1px;">MNL-XXX</span>
                    <span>Terima kasih sudah berbelanja di <strong>Greatfit</strong> 🩵</span>
                    <span id="lblShipmentDate">01 JAN 2026</span>
                </div>
            </div>
        </div>
        
        <div class="ms-modal-footer no-print">
            <button class="ms-btn ms-btn-outline" onclick="closePreview()" style="padding:.5rem 1rem; border-radius:10px; border:1px solid #cbd5e1; background:transparent; cursor:pointer;">Tutup</button>
            <button class="ms-btn ms-btn-primary" onclick="printLabel()" style="padding:.5rem 1rem; border-radius:10px; border:none; background:#3b82f6; color:#fff; cursor:pointer;"><i class="bi bi-printer"></i> Cetak Label</button>
        </div>
    </div>
</div>

<script>
let qrGenerated = false;

const shipmentFilterForm = document.getElementById('shipment-filter-form');
shipmentFilterForm?.querySelectorAll('select').forEach(function (select) {
    select.addEventListener('change', function () {
        shipmentFilterForm.requestSubmit();
    });
});
shipmentFilterForm?.querySelector('input[name="q"]')?.addEventListener('keydown', function (event) {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    shipmentFilterForm.requestSubmit();
});

function openPreview(btn) {
    const code = btn.getAttribute('data-code');
    const date = btn.getAttribute('data-date');
    const nama = btn.getAttribute('data-nama');
    const phone = btn.getAttribute('data-phone');
    const alamat = btn.getAttribute('data-alamat');
    let items = [];
    try {
        items = JSON.parse(btn.getAttribute('data-items'));
    } catch(e) {}

    document.getElementById('lblShipmentCode').textContent = code;
    document.getElementById('lblShipmentDate').textContent = date;
    document.getElementById('lblRecvName').textContent = nama.toUpperCase();
    document.getElementById('lblRecvPhone').textContent = phone;
    document.getElementById('lblRecvAddress').textContent = alamat;
    
    // Render items
    const itemsContainer = document.getElementById('lblItemsList');
    itemsContainer.innerHTML = '';
    
    if(items && items.length > 0) {
        items.forEach((item, index) => {
            const row = document.createElement('div');
            row.className = 'label-item-row';
            row.style.alignItems = 'center';
            row.innerHTML = `
                <div style="flex:1; padding-right:10px; line-height:1.2;">
                    ${index + 1}. <strong style="font-size:10px;">${item.name || item.code}</strong><br>
                    <span style="color:#475569; font-size:8px; margin-left:12px;">${item.code}</span>
                </div>
                <strong style="font-size:11px;">x${item.qty}</strong>
            `;
            itemsContainer.appendChild(row);
        });
    } else {
        itemsContainer.innerHTML = '<i>Data item tidak tersedia</i>';
    }
    
    // Generate QR once
    if (!qrGenerated && typeof QRCode !== 'undefined') {
        const qrContainer = document.getElementById('labelQrCode');
        new QRCode(qrContainer, {
            text: 'https://www.greatfit.id',
            width: 60,
            height: 60,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
        qrGenerated = true;
    }
    
    document.getElementById('modalPreview').classList.add('show');
}

function closePreview() {
    document.getElementById('modalPreview').classList.remove('show');
}

function printLabel() {
    window.print();
}

document.querySelectorAll('tr[data-row-href]').forEach(function (row) {
    function goToRow(event) {
        if (event.target.closest('a, button, form, input, select, textarea, label')) return;
        window.location.href = row.dataset.rowHref;
    }

    row.addEventListener('click', goToRow);
    row.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        goToRow(event);
    });
});
</script>
@endsection
