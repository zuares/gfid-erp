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
    .filter-bar{ display:flex; align-items:center; gap:.75rem; margin:0 0 .65rem; padding:.6rem .7rem; border:1px solid var(--shp-border); border-radius:8px; background:var(--card,#fff); }
    .filter-heading{ min-width:105px; color:#334155; font-size:.76rem; font-weight:800; }
    .filter-heading small{ display:block; margin-top:.08rem; color:#94a3b8; font-size:.66rem; font-weight:600; }
    .shipment-filter-form{ display:grid; grid-template-columns:minmax(220px,1.5fr) repeat(2,minmax(115px, .8fr)) repeat(2,minmax(115px,.8fr)) auto auto; gap:.4rem; align-items:center; flex:1; }
    .shipment-filter-form .filter-input{ min-width:0; border-radius:7px; font-size:.78rem; }
    .shipment-filter-form .filter-date{ width:125px; border-radius:7px; font-size:.78rem; }
    .shipment-filter-form .btn{ min-height:32px; }
    .filter-label{ font-size:.8rem; color:#6b7280; }
    body[data-theme="dark"] .filter-label{ color:#9ca3af; }
    .filter-select{ border-radius:7px; padding-left:.75rem; padding-right:2rem; font-size:.82rem; }
    .btn-pill{ border-radius:7px; padding-inline:.78rem; box-shadow:none!important; font-weight:600; }
    .btn-ship-primary{ background:var(--shp-accent)!important; border-color:var(--shp-accent)!important; color:#fff!important; }
    .btn-ship-primary:hover{ background:var(--shp-accent-2)!important; border-color:var(--shp-accent-2)!important; color:#fff!important; }
    .btn-ship-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-ship-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }
    .btn-fresh{ border-color:#fecaca; color:#b91c1c; background:transparent; }
    .btn-fresh:hover{ background:#fef2f2; color:#991b1b; border-color:#fca5a5; }

    .table-list{ margin-bottom:0; }
    .table-responsive{ max-height:min(64vh, 640px); overflow:auto; }
    .table-list thead th{
        position:sticky;
        top:0;
        z-index:20;
        border-bottom-width:1px;
        font-size:.68rem;
        text-transform:none;
        letter-spacing:0;
        color:#64748b;
        background: var(--card,#fff);
        padding:.52rem .62rem;
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
        padding:.52rem .62rem;
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
    .code-link{ display:inline-block; color:#334155!important; font-size:.8rem; font-weight:700; }
    .code-link:hover{ color:#0f172a!important; }
    .shipment-inline{ display:flex; align-items:center; gap:.45rem; min-width:0; white-space:nowrap; }
    .shipment-timestamps{ display:flex; align-items:center; flex-wrap:wrap; gap:.18rem .45rem; margin-top:.18rem; color:#64748b; font-size:.7rem; }
    .shipment-timestamps span + span::before{ content:'·'; margin-right:.45rem; color:#cbd5e1; }
    .shipment-store{ color:#475569; font-size:.72rem; }
    .shipment-store::before{ content:'·'; margin-right:.45rem; color:#cbd5e1; }
    .mode-badge{ display:inline-flex; align-items:center; gap:.3rem; border-radius:999px; padding:.2rem .5rem; font-size:.68rem; font-weight:800; white-space:nowrap; }
    .mode-badge::before{ content:''; width:6px; height:6px; border-radius:999px; background:currentColor; }
    .mode-item{ color:#475569; background:#f8fafc; border:1px solid #e2e8f0; }
    .mode-order{ color:#64748b; background:#f8fafc; border:1px solid #e2e8f0; }
    .package-summary{ display:flex; align-items:baseline; gap:.3rem; white-space:nowrap; }
    .package-main{ color:#0f172a; font-size:.78rem; font-weight:850; }
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
        .filter-heading{ margin-bottom:.5rem; }
        .shipment-filter-form{ display:grid; grid-template-columns:1fr 1fr; width:100%; }
        .shipment-filter-form .filter-input-wrap{ grid-column:1 / -1; width:100%; }
        .shipment-filter-form .filter-input{ width:100%; }
        .shipment-filter-form .filter-date{ width:100%; }
        .shipment-filter-form .filter-label{ display:none; }
        .shipment-filter-form .btn{ width:100%; }
        .filter-select{ width:100%; min-height:40px; }
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

    @if(isset($staleDrafts) && $staleDrafts->count() > 0)
        <div class="alert alert-warning mb-2" style="border-radius: 8px; font-size: 0.88rem;">
            <i class="bi bi-clock-history"></i> 
            <strong>Peringatan Stale Draft:</strong> 
            Terdapat <b>{{ $staleDrafts->count() }} Shipment</b> (Draft/Submitted) berumur lebih dari 24 jam yang menahan total 
            <b>{{ number_format($staleDrafts->sum('total_allocated'), 0, ',', '.') }} unit stok</b>. 
            Mohon segera selesaikan atau hapus draf berikut: 
            @foreach($staleDrafts->take(5) as $sd)
                <a href="{{ route('sales.shipments.edit', $sd) }}" class="fw-bold text-dark text-decoration-underline">{{ $sd->code }}</a>{{ !$loop->last ? ',' : '' }}
            @endforeach
            @if($staleDrafts->count() > 5) ... @endif
        </div>
    @endif

    <div class="ship-topbar">
        <div class="ship-heading">
            <div class="title">Daftar Shipment</div>
            <div class="sub">Pilih shipment untuk melanjutkan scan atau melihat detail.</div>

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
        </div>
    </div>

    <div class="filter-bar">
        <div class="filter-heading"><i class="bi bi-search" aria-hidden="true"></i> Cari Shipment<small>Gunakan kode, order, atau resi</small></div>
        <form method="GET" class="shipment-filter-form">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
            <div class="filter-input-wrap">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $search }}" class="form-control form-control-sm filter-input" placeholder="Kode shipment / order / resi" autocomplete="off" aria-label="Cari kode shipment, order, atau resi">
            </div>
            <select name="status" class="form-select form-select-sm filter-select" aria-label="Filter status">
                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua status</option>
                <option value="draft" {{ $statusFilter === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="submitted" {{ $statusFilter === 'submitted' ? 'selected' : '' }}>Submitted</option>
                <option value="posted" {{ $statusFilter === 'posted' ? 'selected' : '' }}>Posted</option>
                <option value="cancelled" {{ $statusFilter === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <select name="scan_mode" class="form-select form-select-sm filter-select" aria-label="Filter mode scan">
                <option value="all" {{ $scanMode === 'all' ? 'selected' : '' }}>Semua mode</option>
                <option value="order_first" {{ $scanMode === 'order_first' ? 'selected' : '' }}>Scan Order</option>
                <option value="item_first" {{ $scanMode === 'item_first' ? 'selected' : '' }}>Scan Item</option>
            </select>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm filter-date" aria-label="Dari tanggal" title="Dari tanggal">
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm filter-date" aria-label="Sampai tanggal" title="Sampai tanggal">
            <button type="submit" class="btn btn-sm btn-ship-primary btn-pill">Terapkan</button>
            @if($search || $statusFilter !== 'all' || $scanMode !== 'all' || $dateFrom || $dateTo)
                <a href="{{ route('sales.shipments.index') }}" class="btn btn-sm btn-ship-outline btn-pill">Reset</a>
            @endif
        </form>
    </div>

    <div class="card card-main">
        <div class="card-body p-0">
            @if ($shipments->count() === 0)
                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></div>
                    <div class="empty-title">Belum ada shipment yang cocok</div>
                    <div class="empty-sub">Coba ubah filter atau klik <b>Shipment Baru</b> untuk mulai.</div>
                    @if($search || $statusFilter !== 'all' || $scanMode !== 'all' || $dateFrom || $dateTo)
                        <a href="{{ route('sales.shipments.index') }}" class="btn btn-sm btn-ship-outline btn-pill mt-3">Hapus Filter</a>
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-list">
                        <thead>
                            <tr>
                                <th style="width: 46px;">#</th>
                                <th style="min-width: 220px;"><a class="table-sort {{ $sort === 'code' ? 'active' : '' }}" href="{{ $sortUrl('code') }}">Shipment <span class="table-sort-icon">{{ $sortIcon('code') }}</span></a></th>
                                <th style="width: 125px;">Mode Scan</th>
                                <th style="width: 145px;"><a class="table-sort {{ $sort === 'lines_count' ? 'active' : '' }}" href="{{ $sortUrl('lines_count') }}">Isi Paket <span class="table-sort-icon">{{ $sortIcon('lines_count') }}</span></a></th>
                                <th style="width: 130px;"><a class="table-sort {{ $sort === 'status' ? 'active' : '' }}" href="{{ $sortUrl('status') }}">Status <span class="table-sort-icon">{{ $sortIcon('status') }}</span></a></th>
                                <th style="width: 125px;">Aksi</th>
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
                                    $modeLabel = $isOrderFirst ? 'Order dulu' : 'Item dulu';
                                    $stockBlocked = !empty($shipment->stock_insufficient_calc);
                                    $actionRoute = $uiStatus === 'draft'
                                        ? route($isOrderFirst ? 'sales.shipments.confirm_orders' : 'sales.shipments.edit', $shipment)
                                        : route('sales.shipments.show', $shipment);
                                    $actionLabel = $uiStatus === 'draft' ? 'Lanjut scan' : 'Lihat detail';
                                @endphp

                                <tr class="shipment-row-clickable {{ $uiStatus === 'draft' ? 'row-draft' : '' }}"
                                    data-row-href="{{ $actionRoute }}" tabindex="0" role="link"
                                    aria-label="{{ $actionLabel }} {{ $shipment->code }}">
                                    <td class="text-muted small mobile-hide">
                                        {{ ($shipments->currentPage() - 1) * $shipments->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="ship-row-main">
                                            <div class="shipment-inline">
                                                <a class="code-link" href="{{ $actionRoute }}">
                                                    {{ $shipment->code }}
                                                </a>

                                                <div class="shipment-timestamps" aria-label="Tanggal shipment">
                                                    <span>Dibuat {{ $fmtDate($shipment->created_at, 'd M Y H:i') }} WIB</span>
                                                    @if($shipment->posted_at)
                                                        <span>Posted {{ $fmtDate($shipment->posted_at, 'd M Y H:i') }} WIB</span>
                                                    @elseif($shipment->updated_at)
                                                        <span>Update {{ $fmtDate($shipment->updated_at, 'd M Y H:i') }} WIB</span>
                                                    @endif
                                                </div>
                                                @if($shipment->store)
                                                    <span class="shipment-store">{{ $shipment->store->name ?: $shipment->store->code }}</span>
                                                @endif

                                                <div class="ship-row-meta d-md-none">
                                                    <span>{{ $modeLabel }}</span>
                                                    <span>{{ number_format($orderCount, 0, ',', '.') }} order</span>
                                                    <span>{{ number_format($itemCount, 0, ',', '.') }} SKU</span>
                                                    <span>{{ number_format($qty, 0, ',', '.') }} qty</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="mobile-hide">
                                        <span class="mode-badge {{ $isOrderFirst ? 'mode-order' : 'mode-item' }}">{{ $modeLabel }}</span>
                                    </td>

                                    <td class="mobile-hide">
                                        <div class="package-summary">
                                            <span class="package-main">{{ number_format($itemCount, 0, ',', '.') }} SKU · {{ number_format($qty, 0, ',', '.') }} qty</span>
                                            <span class="package-sub">{{ number_format($orderCount, 0, ',', '.') }} order tercatat</span>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge-status {{ $statusClass }}">{{ $statusLabel }}</span>
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
                                                <button type="button" class="btn btn-sm btn-ship-outline btn-pill action-icon"
                                                    data-code="{{ $shipment->code }}"
                                                    data-date="{{ $fmtDate($shipment->date, 'd M Y') }}"
                                                    data-nama="{{ $recv['nama'] }}"
                                                    data-phone="{{ $recv['phone'] }}"
                                                    data-alamat="{{ $recv['alamat'] }}"
                                                    data-items="{{ $linesJson }}"
                                                    onclick="openPreview(this)" title="Preview label" aria-label="Preview label">
                                                    <i class="bi bi-printer" aria-hidden="true"></i>
                                                </button>
                                                
                                                @if($uiStatus === 'draft')
                                                    <form action="{{ route('sales.shipments.manual.post', $shipment) }}" method="POST" class="d-inline"
                                                          data-gf-confirm
                                                          data-gf-confirm-title="Kirim paket manual?"
                                                          data-gf-confirm-summary='@json(["orders" => (int) ($shipment->order_scans_count ?? 0), "items" => $shipment->lines->count(), "qty" => $qty])'
                                                          data-gf-confirm-text="Order discan: {{ (int) ($shipment->order_scans_count ?? 0) }} · Item/SKU: {{ $shipment->lines->count() }} · Total qty: {{ $qty }}. Stok WH-RTS akan dipotong."
                                                          data-gf-confirm-ok="Kirim"
                                                          data-gf-confirm-cancel="Batal">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-ship-primary btn-pill action-icon" title="{{ $stockBlocked ? 'Stok WH-RTS tidak cukup' : 'Kirim/Post' }}" aria-label="{{ $stockBlocked ? 'Stok WH-RTS tidak cukup' : 'Kirim paket' }}" @disabled($stockBlocked)>
                                                            <i class="bi bi-send" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <form action="{{ route('sales.shipments.manual.destroy', $shipment) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus paket manual ini?');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-pill action-icon" style="border-color:#fecaca;" title="Hapus shipment" aria-label="Hapus shipment">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @else
                                            <a href="{{ $actionRoute }}" class="btn btn-sm {{ $uiStatus === 'draft' ? 'btn-ship-primary' : 'btn-ship-outline' }} btn-pill action-icon" title="{{ $actionLabel }}" aria-label="{{ $actionLabel }} {{ $shipment->code }}">
                                                <i class="bi {{ $uiStatus === 'draft' ? 'bi-upc-scan' : 'bi-eye' }}" aria-hidden="true"></i>
                                            </a>
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
