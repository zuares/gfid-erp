@extends('layouts.app')
@section('title', 'Marketplace • Settlement')

@include('marketplace._shared')

@push('head')
    <style>
        .settlement-tabs{
            display:inline-flex;
            gap:.25rem;
            padding:.3rem;
            border-radius:14px;
            background:rgba(148,163,184,.08);
            margin-bottom:.9rem;
            overflow-x:auto;
            max-width:100%;
            scrollbar-width:none;
        }
        .settlement-tabs::-webkit-scrollbar{ display:none; }
        body[data-theme="dark"] .settlement-tabs{ background:rgba(30,41,59,.5); }
        .settlement-tab-btn{
            border:none;
            background:transparent;
            color:#64748b;
            border-radius:10px;
            padding:.5rem .9rem;
            font-size:.8rem;
            font-weight:800;
            cursor:pointer;
            white-space:nowrap;
            transition:all .15s ease;
        }
        .settlement-tab-btn:hover{ color:var(--shp-text); }
        .settlement-tab-btn.active{
            background:var(--card,#fff);
            color:var(--shp-text);
            box-shadow:0 1px 3px rgba(0,0,0,.05), 0 1px 2px rgba(0,0,0,.1);
        }
        body[data-theme="dark"] .settlement-tab-btn{ color:#94a3b8; }
        body[data-theme="dark"] .settlement-tab-btn.active{
            background:var(--card,#1e293b);
            color:var(--text,#e2e8f0);
        }
        .settlement-sync-shell{
            padding:1rem;
        }
        .settlement-sync-header{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:.85rem;
            flex-wrap:wrap;
        }
        .settlement-sync-eyebrow{
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            font-size:.68rem;
            font-weight:900;
            letter-spacing:.04em;
            text-transform:uppercase;
            color:#64748b;
        }
        .settlement-sync-title{
            font-size:1.02rem;
            font-weight:800;
            color:var(--shp-text);
            line-height:1.2;
            margin-top:.1rem;
        }
        .settlement-auto-sync-banner{
            display:flex;
            align-items:center;
            gap:.7rem;
            margin:.75rem 0 .9rem;
            padding:.7rem .85rem;
            border:1px solid rgba(148,163,184,.24);
            border-radius:12px;
            background:rgba(248,250,252,.9);
            color:var(--shp-text);
            min-height:52px;
        }
        .settlement-auto-sync-banner-icon{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:30px;
            height:30px;
            flex:0 0 30px;
            border-radius:10px;
            background:rgba(100,116,139,.13);
            color:#475569;
        }
        .settlement-auto-sync-banner-copy{ min-width:0; flex:1 1 auto; }
        .settlement-auto-sync-banner-title{ display:block; font-size:.76rem; font-weight:900; line-height:1.2; }
        .settlement-auto-sync-banner-message{ display:block; margin-top:.12rem; color:var(--shp-muted); font-size:.7rem; line-height:1.35; }
        .settlement-auto-sync-banner-meta{ display:block; margin-top:.12rem; color:var(--shp-muted); font-size:.64rem; }
        .settlement-auto-sync-banner-action{ flex:0 0 auto; white-space:nowrap; }
        .settlement-auto-sync-banner.running{ border-color:rgba(59,130,246,.28); background:rgba(239,246,255,.95); }
        .settlement-auto-sync-banner.running .settlement-auto-sync-banner-icon{ background:rgba(59,130,246,.14); color:#2563eb; }
        .settlement-auto-sync-banner.success{ border-color:rgba(34,197,94,.25); background:rgba(240,253,244,.92); }
        .settlement-auto-sync-banner.success .settlement-auto-sync-banner-icon{ background:rgba(34,197,94,.14); color:#16a34a; }
        .settlement-auto-sync-banner.warn{ border-color:rgba(245,158,11,.28); background:rgba(255,251,235,.95); }
        .settlement-auto-sync-banner.warn .settlement-auto-sync-banner-icon{ background:rgba(245,158,11,.16); color:#d97706; }
        .settlement-auto-sync-banner.error{ border-color:rgba(239,68,68,.24); background:rgba(254,242,242,.95); }
        .settlement-auto-sync-banner.error .settlement-auto-sync-banner-icon{ background:rgba(239,68,68,.14); color:#dc2626; }
        body[data-theme="dark"] .settlement-auto-sync-banner{ background:rgba(15,23,42,.72); }
        body[data-theme="dark"] .settlement-auto-sync-banner.running{ background:rgba(30,64,175,.2); }
        body[data-theme="dark"] .settlement-auto-sync-banner.success{ background:rgba(20,83,45,.2); }
        body[data-theme="dark"] .settlement-auto-sync-banner.warn{ background:rgba(120,53,15,.2); }
        body[data-theme="dark"] .settlement-auto-sync-banner.error{ background:rgba(127,29,29,.2); }
        @media (max-width: 640px){
            .settlement-auto-sync-banner{ align-items:flex-start; }
            .settlement-auto-sync-banner-action{ margin-left:auto; }
            .settlement-auto-sync-banner-action span{ display:none; }
        }
        .settlement-sync-actions-row{
            display:flex;
            gap:.5rem;
            align-items:center;
            flex-wrap:wrap;
            justify-content:flex-end;
        }
        .settlement-sync-feedback{
            display:flex;
            align-items:flex-start;
            gap:.65rem;
            border-radius:12px;
            border:1px solid rgba(148,163,184,.22);
            background:rgba(248,250,252,.9);
            padding:.75rem .85rem;
            margin-top:1rem;
            font-size:.82rem;
        }
        .settlement-sync-feedback strong{ display:block; font-size:.85rem; line-height:1.2; }
        .settlement-sync-feedback .settlement-feedback-meta{
            display:flex;
            flex-wrap:wrap;
            gap:.35rem;
            margin-top:.45rem;
        }
        .settlement-sync-feedback .settlement-feedback-meta span{
            display:inline-flex;
            align-items:center;
            gap:.25rem;
            padding:.16rem .5rem;
            border-radius:999px;
            font-size:.68rem;
            font-weight:800;
            background:rgba(148,163,184,.12);
            color:#475569;
        }
        .settlement-sync-progress-context{
            display:flex;
            flex-wrap:wrap;
            align-items:center;
            gap:.35rem .55rem;
            margin-top:.18rem;
            color:var(--shp-muted);
            font-size:.7rem;
            line-height:1.35;
        }
        .settlement-sync-progress-context strong{
            color:var(--shp-text);
            font-weight:850;
        }
        .settlement-sync-progress-context .separator{ color:#cbd5e1; }
        .settlement-sync-feedback.success{
            border-color:rgba(34,197,94,.25);
            background:rgba(240,253,244,.92);
        }
        .settlement-sync-feedback.warn{
            border-color:rgba(245,158,11,.25);
            background:rgba(255,251,235,.92);
        }
        .settlement-sync-feedback.error{
            border-color:rgba(239,68,68,.22);
            background:rgba(254,242,242,.95);
        }
        .settlement-sync-feedback.info{
            border-color:rgba(59,130,246,.22);
            background:rgba(239,246,255,.95);
        }
        .settlement-sync-status-grid{
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:.5rem;
            margin-top:.9rem;
        }
        .settlement-sync-chip{
            display:inline-flex;
            align-items:center;
            gap:.3rem;
            padding:.22rem .55rem;
            border-radius:999px;
            font-size:.68rem;
            font-weight:800;
            background:rgba(148,163,184,.12);
            color:#475569;
            min-width:0;
        }
        .settlement-sync-chip.success{ background:rgba(34,197,94,.14); color:#166534; }
        .settlement-sync-chip.warn{ background:rgba(245,158,11,.16); color:#b45309; }
        .settlement-sync-chip.error{ background:rgba(239,68,68,.14); color:#b91c1c; }
        .settlement-sync-chip.running{ background:rgba(59,130,246,.12); color:#1d4ed8; }
        .settlement-sync-action-strip{
            margin-top:1rem;
            padding:1rem;
            border:1px solid var(--shp-border);
            border-radius:12px;
            background:rgba(148,163,184,.03);
        }
        .settlement-sync-field{
            flex:1 1 200px;
            min-width:200px;
            position:relative;
        }
        .settlement-sync-field label{
            display:block;
            margin-bottom:.28rem;
            font-size:.68rem;
            font-weight:900;
            color:#64748b;
            text-transform:uppercase;
            letter-spacing:.04em;
        }
        .settlement-sync-details{
            margin-top:.9rem;
            border:1px solid var(--shp-border);
            border-radius:12px;
            overflow:hidden;
            background:var(--card);
        }
        .settlement-sync-details > summary{
            list-style:none;
            cursor:pointer;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:.5rem;
            padding:.85rem 1rem;
            font-size:.82rem;
            font-weight:800;
            color:var(--shp-text);
            border-bottom:1px solid transparent;
        }
        .settlement-sync-details > summary::-webkit-details-marker{ display:none; }
        .settlement-sync-details[open] > summary{ border-bottom-color:var(--shp-border); }
        .settlement-sync-details-body{
            padding:1rem;
            display:grid;
            gap:.9rem;
        }
        .settlement-sync-console-wrap{
            display:grid;
            gap:.35rem;
        }
        .settlement-sync-console{
            background:rgba(2,6,23,.92);
            border:1px solid var(--shp-border);
            border-radius:10px;
            padding:10px 12px;
            font-family:ui-monospace,monospace;
            font-size:.7rem;
            color:#a1a1aa;
            height:118px;
            overflow-y:auto;
            line-height:1.45;
            white-space:pre-wrap;
        }
        .settlement-sync-advanced{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:.5rem;
            flex-wrap:wrap;
            padding:.8rem 1rem;
            border:1px dashed var(--shp-border-strong);
            border-radius:12px;
            background:rgba(148,163,184,.03);
        }
        .settlement-store-health{
            margin-top:.85rem;
            padding:.85rem;
            border:1px solid var(--shp-border);
            border-radius:12px;
            background:rgba(148,163,184,.025);
        }
        .settlement-store-health-head{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:.75rem;
            flex-wrap:wrap;
            margin-bottom:.65rem;
        }
        .settlement-store-health-title{
            font-size:.75rem;
            font-weight:900;
            color:var(--shp-text);
        }
        .settlement-store-health-subtitle{
            margin-top:.16rem;
            font-size:.7rem;
            color:var(--shp-muted);
        }
        .settlement-store-health-summary{
            display:flex;
            gap:.35rem;
            flex-wrap:wrap;
        }
        .settlement-store-health-summary span{
            display:inline-flex;
            align-items:center;
            gap:.25rem;
            border-radius:999px;
            padding:.18rem .5rem;
            font-size:.67rem;
            font-weight:800;
            background:rgba(148,163,184,.12);
            color:#475569;
        }
        .settlement-store-health-summary .ready{ background:rgba(34,197,94,.14); color:#166534; }
        .settlement-store-health-summary .attention{ background:rgba(245,158,11,.16); color:#b45309; }
        .settlement-store-health-summary .inactive{ background:rgba(100,116,139,.14); color:#475569; }
        .settlement-store-health-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
            gap:.5rem;
        }
        .settlement-store-health-card{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:.55rem;
            padding:.65rem .7rem;
            border:1px solid var(--shp-border);
            border-radius:10px;
            background:var(--card,#fff);
        }
        .settlement-store-health-card strong{
            display:block;
            font-size:.74rem;
            color:var(--shp-text);
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
        }
        .settlement-store-health-card small{
            display:block;
            margin-top:.16rem;
            font-size:.67rem;
            color:var(--shp-muted);
        }
        .settlement-store-health-status{
            flex:none;
            display:inline-flex;
            align-items:center;
            gap:.25rem;
            border-radius:999px;
            padding:.18rem .42rem;
            font-size:.63rem;
            font-weight:900;
            white-space:nowrap;
        }
        .settlement-store-health-status.ready{ background:rgba(34,197,94,.14); color:#166534; }
        .settlement-store-health-status.refresh{ background:rgba(59,130,246,.14); color:#1d4ed8; }
        .settlement-store-health-status.attention{ background:rgba(245,158,11,.16); color:#b45309; }
        .settlement-store-health-status.inactive{ background:rgba(100,116,139,.14); color:#475569; }
        body[data-theme="dark"] .settlement-sync-feedback,
        body[data-theme="dark"] .settlement-sync-action-strip,
        body[data-theme="dark"] .settlement-sync-details{
            background:rgba(15,23,42,.92);
        }
        body[data-theme="dark"] .settlement-sync-advanced{
            background:rgba(15,23,42,.78);
        }
        body[data-theme="dark"] .settlement-store-health,
        body[data-theme="dark"] .settlement-store-health-card{
            background:rgba(15,23,42,.78);
        }
        .settlement-sync-log-ok{ color:#4ade80; }
        .settlement-sync-log-warn{ color:#f59e0b; }
        .settlement-sync-log-error{ color:#f87171; }
        .settlement-sync-log-muted{ color:#94a3b8; }

        .settlement-filter-shell{
            padding:.85rem;
            background:rgba(148,163,184,.035);
            border-bottom:1px solid var(--shp-border);
        }
        .settlement-filter-grid{
            display:grid;
            grid-template-columns:
                minmax(120px,1.05fr)
                minmax(165px,1.4fr)
                minmax(125px,1.05fr)
                minmax(120px,.95fr)
                minmax(145px,1.2fr)
                minmax(145px,1.2fr);
            gap:.65rem;
            align-items:end;
        }
        .settlement-filter-field{
            min-width:0;
            position:relative;
        }
        .settlement-filter-field.platform,
        .settlement-filter-field.store,
        .settlement-filter-field.status,
        .settlement-filter-field.fund,
        .settlement-filter-field.date,
        .settlement-filter-field.search{ grid-column:auto; }
        .settlement-filter-label{
            display:block;
            margin:0 0 .28rem .1rem;
            color:var(--shp-muted);
            font-size:.64rem;
            font-weight:850;
            letter-spacing:.035em;
            text-transform:uppercase;
        }
        .settlement-filter-control{
            min-height:34px;
            border-color:var(--shp-border);
            background:var(--card,#fff);
            color:var(--shp-text);
            padding-left:2rem;
        }
        .settlement-filter-control:focus{
            border-color:var(--shp-accent,#2563eb);
            box-shadow:0 0 0 .18rem rgba(37,99,235,.12);
        }
        .settlement-filter-control:disabled{
            cursor:not-allowed;
            opacity:.62;
            background:rgba(148,163,184,.08);
        }
        .settlement-filter-field input.flatpickr-input{
            cursor:pointer;
            padding-right:2.35rem;
        }
        .settlement-filter-field input.flatpickr-input:focus{
            background:var(--card,#fff);
        }
        .settlement-range-calendar .flatpickr-day.inRange{
            background:rgba(37,99,235,.12);
            border-color:transparent;
            box-shadow:none;
            color:var(--shp-text);
        }
        .settlement-range-calendar .flatpickr-day.startRange,
        .settlement-range-calendar .flatpickr-day.endRange{
            background:var(--shp-accent,#2563eb);
            border-color:var(--shp-accent,#2563eb);
            color:#fff;
        }
        .settlement-range-calendar .flatpickr-day.startRange{ border-radius:9px 3px 3px 9px; }
        .settlement-range-calendar .flatpickr-day.endRange{ border-radius:3px 9px 9px 3px; }
        .settlement-range-calendar .flatpickr-day.startRange.endRange{ border-radius:9px; }
        .settlement-range-calendar .flatpickr-months{ padding:.2rem .25rem; }
        .settlement-range-calendar .flatpickr-weekdays{ padding:0 .25rem; }
        .settlement-filter-icon{
            position:absolute;
            left:.68rem;
            bottom:.58rem;
            z-index:1;
            color:#94a3b8;
            font-size:.75rem;
            pointer-events:none;
        }
        body[data-theme="dark"] .settlement-filter-shell{ background:rgba(15,23,42,.58); }
        body[data-theme="dark"] .settlement-filter-control{ background:rgba(15,23,42,.88); }

        .settlement-page-header{
            align-items:center;
            padding:.9rem 1rem;
            margin-bottom:.8rem;
            border:1px solid var(--shp-border);
            border-radius:var(--gf-radius-card,16px);
            box-shadow:var(--gf-shadow-sm,0 1px 2px rgba(16,24,40,.04));
        }
        .settlement-heading{ min-width:0; }
        .settlement-eyebrow{
            display:flex;
            align-items:center;
            gap:.4rem;
            margin-bottom:.22rem;
            color:var(--shp-accent);
            font-size:.66rem;
            font-weight:900;
            letter-spacing:.08em;
            text-transform:uppercase;
        }
        .settlement-eyebrow-dot{ width:6px; height:6px; border-radius:50%; background:#22c55e; }
        .settlement-page-title{ margin:0; color:var(--shp-text); font-size:1.25rem; font-weight:850; letter-spacing:-.025em; }
        .settlement-page-sub{ margin-top:.22rem; color:var(--shp-muted); font-size:.76rem; }
        .settlement-header-actions{ display:flex; align-items:center; justify-content:flex-end; gap:.45rem; flex-wrap:wrap; }
        .settlement-header-secondary{ display:flex; align-items:center; gap:.35rem; }
        .settlement-icon-button{ width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; padding:0; }
        .settlement-page-header .btn{ min-height:34px; }
        body[data-theme="dark"] .settlement-page-header{ box-shadow:none; }

        .settlement-filter-head{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:.75rem;
            margin-bottom:.7rem;
        }
        .settlement-filter-title{ color:var(--shp-text); font-size:.78rem; font-weight:850; }
        .settlement-filter-caption{ color:var(--shp-muted); font-size:.7rem; margin-left:.35rem; }
        .settlement-filter-reset{ border:0; background:transparent; color:var(--shp-accent); font-size:.7rem; font-weight:800; padding:.2rem .3rem; }
        .settlement-filter-reset:hover{ text-decoration:underline; }

        .settlement-kpi-grid{
            display:grid;
            grid-template-columns:repeat(5,minmax(0,1fr));
            gap:.65rem;
            margin-bottom:1rem;
        }
        .settlement-kpi-grid .oc-kpi-card{
            min-width:0;
            margin:0!important;
            padding:.75rem .8rem!important;
            overflow:hidden;
        }
        .settlement-kpi-grid .oc-kpi-label,
        .settlement-kpi-grid .oc-kpi-value,
        .settlement-kpi-grid [id^="kpi"]{
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .settlement-ledger-card{ overflow:hidden; }
        .settlement-ledger-head{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:.75rem;
            padding:.8rem 1rem;
            border-bottom:1px solid var(--shp-border);
        }
        .settlement-ledger-head-actions{ display:flex; align-items:center; justify-content:flex-end; gap:.65rem; min-width:0; }
        .settlement-ledger-search{ position:relative; width:min(280px,32vw); min-width:220px; }
        .settlement-ledger-search .settlement-filter-control{ min-height:34px; padding-left:2rem; }
        .settlement-ledger-search .settlement-filter-icon{ bottom:.58rem; }
        .settlement-ledger-title{ color:var(--shp-text); font-size:.86rem; font-weight:850; }
        .settlement-ledger-meta{ color:var(--shp-muted); font-size:.7rem; }
        .settlement-ledger-status{ display:inline-flex; align-items:center; gap:.3rem; color:#15803d; font-size:.68rem; font-weight:850; }
        .settlement-ledger-status::before{ content:""; width:6px; height:6px; border-radius:50%; background:#22c55e; }
        .settlement-table-scroll{ max-height:65vh; overflow-x:auto; overflow-y:auto; }
        .settlement-ledger-table{ min-width:0; width:100%; table-layout:fixed; }
        .settlement-ledger-table thead th{ padding:.65rem .8rem; background:var(--card,#fff); }
        .settlement-ledger-table thead th:nth-child(1){ width:22%; }
        .settlement-ledger-table thead th:nth-child(2){ width:20%; }
        .settlement-ledger-table thead th:nth-child(3){ width:15%; }
        .settlement-ledger-table thead th:nth-child(4){ width:24%; }
        .settlement-ledger-table thead th:nth-child(5){ width:19%; }
        .settlement-ledger-table tbody tr{ transition:background .15s ease; }
        .settlement-ledger-table tbody tr:hover{ background:rgba(91,92,226,.035); }
        .settlement-ledger-table tbody td{ padding:.72rem .8rem; border-top-color:var(--shp-border); overflow-wrap:anywhere; }
        .settlement-order-id{ display:block; font-size:.78rem; font-weight:850; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .settlement-order-store{ margin-top:.35rem; color:var(--shp-muted); font-size:.7rem; font-weight:650; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .settlement-order-source{ margin-top:.28rem; color:#2563eb; font-size:.6rem; font-weight:850; letter-spacing:.035em; text-transform:uppercase; }
        .settlement-timeline{ display:grid; gap:.5rem; }
        .settlement-timeline-item{ display:grid; gap:.12rem; }
        .settlement-timeline-label{ color:var(--shp-muted); font-size:.59rem; font-weight:850; letter-spacing:.04em; text-transform:uppercase; }
        .settlement-timeline-value{ color:var(--shp-text); font-size:.72rem; font-weight:700; overflow-wrap:anywhere; }
        .settlement-timeline-value.muted{ color:var(--shp-muted); }
        .settlement-money-stack{ display:grid; gap:.35rem; text-align:right; }
        .settlement-money-item{ display:grid; gap:.08rem; }
        .settlement-money-label{ color:var(--shp-muted); font-size:.59rem; font-weight:850; letter-spacing:.04em; text-transform:uppercase; }
        .settlement-money-value{ color:var(--shp-text); font-size:.82rem; font-weight:800; }
        .settlement-money-value.net{ color:#16a34a; font-size:.9rem; }
        .settlement-money-value.pending{ color:#d97706; }
        .settlement-money-source{ color:var(--shp-muted); font-size:.6rem; font-weight:800; }
        .settlement-breakdown{ display:grid; grid-template-columns:1fr; gap:.55rem; }
        .settlement-breakdown-block{ min-width:0; }
        .settlement-breakdown-block + .settlement-breakdown-block{ padding-top:.5rem; border-top:1px dashed rgba(148,163,184,.22); }
        .settlement-breakdown-title{ margin-bottom:.25rem; color:var(--shp-muted); font-size:.59rem; font-weight:850; letter-spacing:.04em; text-transform:uppercase; }
        .settlement-breakdown .fee-list{ font-size:.68rem; }
        .settlement-ledger-table .fee-list{ min-width:0; overflow-wrap:anywhere; }
        .settlement-ledger-table .fee-list li{ align-items:flex-start; min-width:0; margin-bottom:.16rem; }
        .settlement-ledger-table .fee-list li > span:first-child{ min-width:0; flex:1 1 auto; overflow-wrap:anywhere; }
        .settlement-ledger-table .fee-list .fee-val,
        .settlement-ledger-table .fee-list .subsidy-val,
        .settlement-ledger-table .fee-list .buyer-val,
        .settlement-ledger-table .fee-list .drc-val{ flex:0 0 auto; white-space:nowrap; text-align:right; }
        .settlement-pending-note{ padding:.5rem .6rem; border:1px dashed rgba(217,119,6,.38); border-radius:9px; background:rgba(245,158,11,.07); color:#92400e; font-size:.68rem; line-height:1.35; }
        .settlement-pagination{ display:flex; justify-content:space-between; align-items:center; gap:.75rem; padding:.7rem .9rem; border-top:1px solid var(--shp-border); color:var(--shp-muted); font-size:.72rem; }

        @media (max-width: 980px) {
            .settlement-kpi-grid{ grid-template-columns:repeat(3,minmax(0,1fr)); }
            .settlement-page-header{ align-items:flex-start; }
            .settlement-header-actions{ justify-content:flex-start; }
        }

        @media (max-width: 1100px) {
            .settlement-filter-grid{ grid-template-columns:repeat(4,minmax(0,1fr)); }
            .settlement-filter-field.platform,
            .settlement-filter-field.store,
            .settlement-filter-field.status,
            .settlement-filter-field.fund,
            .settlement-filter-field.date{ grid-column:span 1; }
            .settlement-filter-field.search{ grid-column:span 2; }
        }

        @media (max-width: 768px) {
            .settlement-sync-status-grid{ grid-template-columns:1fr; }
            .settlement-sync-header{ align-items:stretch; }
            .settlement-sync-actions-row{ justify-content:flex-start; }
            .settlement-sync-field,
            .settlement-sync-field[style]{ max-width:none !important; min-width:0 !important; flex:1 1 100%; }
            .settlement-sync-action-strip{ padding:.85rem; }
            .settlement-filter-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); gap:.55rem; }
            .settlement-filter-field.platform,
            .settlement-filter-field.store,
            .settlement-filter-field.status,
            .settlement-filter-field.fund,
            .settlement-filter-field.date,
            .settlement-filter-field.search{ grid-column:span 1; }
            .settlement-filter-field.search{ grid-column:1 / -1; }
            .settlement-filter-shell{ padding:.7rem; }
            .settlement-page-header{ position:relative; top:auto; z-index:auto; margin-inline:-.7rem; border-radius:0; border-inline:0; }
            .settlement-page-title{ font-size:1.1rem; }
            .settlement-page-sub{ max-width:34rem; }
            .settlement-kpi-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); gap:.5rem; }
            .settlement-ledger-head{ align-items:flex-start; flex-direction:column; }
            .settlement-ledger-head-actions{ width:100%; justify-content:space-between; }
            .settlement-ledger-search{ width:min(100%,360px); min-width:0; }
            .settlement-table-scroll{ overflow:visible; max-height:none; }
            .settlement-ledger-table{ display:block; }
            .settlement-ledger-table thead{ display:none; }
            .settlement-ledger-table tbody,
            .settlement-ledger-table tr{ display:block; }
            .settlement-ledger-table tbody tr{ margin:.65rem; padding:.7rem; border:1px solid var(--shp-border); border-radius:12px; background:var(--card,#fff); }
            .settlement-ledger-table tbody td{ display:block; width:100%!important; padding:.35rem 0; border:0; }
            .settlement-ledger-table tbody td + td{ border-top:1px solid rgba(148,163,184,.16); padding-top:.6rem; margin-top:.2rem; }
            .settlement-money-stack{ grid-template-columns:repeat(2,minmax(0,1fr)); text-align:left; }
            .settlement-breakdown{ grid-template-columns:1fr; gap:.45rem; }
            .settlement-pagination{ align-items:flex-start; flex-direction:column; }
        }
    </style>
@endpush

@section('content')
<div class="page-wrap">
    <div class="ship-topbar settlement-page-header">
        <div class="settlement-heading">
            <div class="settlement-eyebrow"><span class="settlement-eyebrow-dot"></span> Marketplace Finance</div>
            <h1 class="settlement-page-title">Settlement</h1>
            <div class="settlement-page-sub">Pantau dana final, potongan marketplace, dan estimasi pencairan per order.</div>
        </div>
        <div class="settlement-header-actions">
            <a href="{{ route('marketplace.reports.financial-quality') }}" class="btn btn-sm btn-ship-primary btn-pill">
                <i class="bi bi-shield-check me-1"></i> Audit Data
            </a>
            <button class="btn btn-sm btn-ship-primary btn-pill" onclick="switchSettlementTab('sync')" title="Buka tab sync settlement">
                <i class="bi bi-arrow-repeat me-1"></i> Sync Settlement
            </button>
            <div class="settlement-header-secondary">
                <a href="{{ route('marketplace.income-detail') }}" class="btn btn-sm btn-ship-outline btn-pill" title="Buka rincian penghasilan" aria-label="Buka rincian penghasilan">
                    <i class="bi bi-journal-text"></i><span class="d-none d-lg-inline ms-1">Penghasilan</span>
                </a>
                <button class="btn btn-sm btn-ship-outline btn-pill settlement-icon-button" onclick="resetFilters()" title="Reset semua filter" aria-label="Reset semua filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
                <button class="btn btn-sm btn-ship-outline btn-pill settlement-icon-button" onclick="loadSettlements()" title="Refresh data settlement" aria-label="Refresh data settlement">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                @if(auth()->user()?->role === 'owner')
                    <button class="btn btn-sm btn-outline-danger btn-pill settlement-icon-button" onclick="purgeAllMarketplaceData()" title="Hapus semua data marketplace untuk toko ini" aria-label="Hapus semua data marketplace untuk toko ini">
                        <i class="bi bi-trash3"></i>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div id="settlementAutoSyncBanner" class="settlement-auto-sync-banner" role="status" aria-live="polite">
        <span id="settlementAutoSyncBannerIcon" class="settlement-auto-sync-banner-icon" aria-hidden="true">
            <i class="bi bi-broadcast-pin"></i>
        </span>
        <div class="settlement-auto-sync-banner-copy">
            <strong id="settlementAutoSyncBannerTitle" class="settlement-auto-sync-banner-title">Auto-sync aktif</strong>
            <span id="settlementAutoSyncBannerMessage" class="settlement-auto-sync-banner-message">Memeriksa status sync Shopee dan TikTok…</span>
            <small id="settlementAutoSyncBannerMeta" class="settlement-auto-sync-banner-meta"></small>
        </div>
        <button type="button" class="btn btn-sm btn-ship-outline btn-pill settlement-auto-sync-banner-action" onclick="switchSettlementTab('sync')" title="Lihat detail sync">
            <i class="bi bi-activity"></i><span class="ms-1">Lihat detail</span>
        </button>
    </div>

    <div class="settlement-tabs" role="tablist" aria-label="Tabs settlement">
        <button type="button" id="settlementTabDataBtn" class="settlement-tab-btn active" role="tab" aria-selected="true" aria-controls="settlementTabData" onclick="switchSettlementTab('data')">
            <i class="bi bi-receipt"></i> Settlement
        </button>
        <button type="button" id="settlementTabSyncBtn" class="settlement-tab-btn" role="tab" aria-selected="false" aria-controls="settlementTabSync" tabindex="-1" onclick="switchSettlementTab('sync')">
            <i class="bi bi-arrow-repeat"></i> Sync
            <span id="settlementSyncDot" style="display:none; width:8px; height:8px; border-radius:50%; background:#3b82f6; margin-left:2px;"></span>
        </button>
    </div>

    <div id="settlementTabData" role="tabpanel" aria-labelledby="settlementTabDataBtn" tabindex="0">
        <div class="card-main mb-3">
            <div class="settlement-filter-shell">
                <div class="settlement-filter-head">
                    <div><span class="settlement-filter-title">Filter settlement</span><span class="settlement-filter-caption">Platform dan toko saling mengikuti</span></div>
                    <button type="button" class="settlement-filter-reset" onclick="resetFilters()">Reset</button>
                </div>
                <div class="settlement-filter-grid" aria-label="Filter settlement">
                    <div class="settlement-filter-field platform">
                        <label class="settlement-filter-label" for="filterPlatform">Platform</label>
                        <select class="form-select form-select-sm filter-select settlement-filter-control w-100" id="filterPlatform" aria-label="Filter platform">
                            <option value="">Semua Platform</option>
                        </select>
                        <i class="bi bi-globe2 settlement-filter-icon" aria-hidden="true"></i>
                    </div>

                    <div class="settlement-filter-field store">
                        <label class="settlement-filter-label" for="filterStore">Toko</label>
                        <select class="form-select form-select-sm filter-select settlement-filter-control w-100" id="filterStore" onchange="loadSettlements()" aria-label="Filter toko">
                            <option value="">Semua Toko</option>
                        </select>
                        <i class="bi bi-shop settlement-filter-icon" aria-hidden="true"></i>
                    </div>

                    <div class="settlement-filter-field status">
                        <label class="settlement-filter-label" for="filterStatus">Status order</label>
                        <select class="form-select form-select-sm filter-select settlement-filter-control w-100" id="filterStatus" onchange="loadSettlements()" aria-label="Filter status order">
                            <option value="">Semua Status</option>
                            <option value="COMPLETED">Selesai</option>
                            <option value="SHIPPED">Dikirim</option>
                            <option value="CANCELLED">Batal</option>
                        </select>
                        <i class="bi bi-box-seam settlement-filter-icon" aria-hidden="true"></i>
                    </div>

                    <div class="settlement-filter-field fund">
                        <label class="settlement-filter-label" for="filterSettlementStatus">Dana</label>
                        <select class="form-select form-select-sm filter-select settlement-filter-control w-100" id="filterSettlementStatus" onchange="onSettlementStatusChange()" aria-label="Filter status dana">
                            <option value="">Semua Dana</option>
                            <option value="cair" selected>Sudah Cair</option>
                            <option value="belum_cair">Belum Cair</option>
                        </select>
                        <i class="bi bi-wallet2 settlement-filter-icon" aria-hidden="true"></i>
                    </div>

                    <div class="settlement-filter-field date">
                        <label class="settlement-filter-label" for="filterOrderDate">Tanggal order</label>
                        <input type="text" class="form-control form-control-sm filter-select settlement-filter-control w-100" id="filterOrderDate" placeholder="Pilih rentang" aria-label="Filter tanggal order">
                        <i class="bi bi-calendar3 settlement-filter-icon" aria-hidden="true"></i>
                    </div>

                    <div class="settlement-filter-field date">
                        <label class="settlement-filter-label" for="filterSettlementDate">Tanggal cair</label>
                        <input type="text" class="form-control form-control-sm filter-select settlement-filter-control w-100" id="filterSettlementDate" placeholder="Pilih rentang" title="Tanggal pencairan final" aria-label="Filter tanggal cair atau estimasi pencairan">
                        <i class="bi bi-calendar3 settlement-filter-icon" aria-hidden="true"></i>
                    </div>

                </div>
            </div>
        </div>

        <div class="settlement-kpi-grid">
            <div class="oc-kpi-card">
                <div class="oc-kpi-label" id="kpiCountLabel" title="Order Cair">Order Cair</div>
                <div class="oc-kpi-value" id="kpiCount">—</div>
            </div>
            <div class="oc-kpi-card">
                <div class="oc-kpi-label" title="Gross Sales">Gross Sales</div>
                <div class="oc-kpi-value" id="kpiBuyerTotal">—</div>
            </div>
            <div class="oc-kpi-card">
                <div class="oc-kpi-label" id="kpiFeeLabel" title="Total Fee Marketplace">Fee Marketplace</div>
                <div class="oc-kpi-value" id="kpiFeeTotal">—</div>
                <div id="kpiFeePercent" class="oc-kpi-note">—</div>
            </div>
            <div class="oc-kpi-card">
                <div class="oc-kpi-label" title="Total Biaya Iklan">Biaya Iklan</div>
                <div class="oc-kpi-value" id="kpiAdCost">—</div>
            </div>
            <div class="oc-kpi-card">
                <div class="oc-kpi-label" id="kpiNetPayoutLabel" title="Dana Cair Final">Dana Cair (Final)</div>
                <div class="oc-kpi-value" id="kpiNetPayout">—</div>
                <div id="kpiEstimateFreshness" class="oc-kpi-note"></div>
            </div>
        </div>

        <div class="card-main settlement-ledger-card">
            <div class="settlement-ledger-head">
                <div>
                    <div class="settlement-ledger-title">Ledger statements</div>
                    <div class="settlement-ledger-meta" id="settlementLedgerMeta">Memuat data ledger…</div>
                </div>
                <div class="settlement-ledger-head-actions">
                    <div class="settlement-ledger-search">
                        <label class="visually-hidden" for="filterSearch">Cari ledger statements</label>
                        <input type="search" class="form-control form-control-sm filter-select settlement-filter-control w-100" id="filterSearch" placeholder="Cari order, produk, SKU" onkeyup="if(event.key==='Enter')loadSettlements()" aria-label="Cari order produk atau SKU">
                        <i class="bi bi-search settlement-filter-icon" aria-hidden="true"></i>
                    </div>
                    <div class="settlement-ledger-status">Data tersimpan</div>
                </div>
            </div>
            <div id="settlementBody">
                <div style="padding:2rem;text-align:center;color:var(--shp-muted);font-size:.85rem;">
                    <span class="spinner-border spinner-border-sm"></span> Memuat data...
                </div>
            </div>
        </div>
    </div>

    <div id="settlementTabSync" role="tabpanel" aria-labelledby="settlementTabSyncBtn" tabindex="0" aria-hidden="true" style="display:none;">
        <div class="card-main settlement-sync-shell mb-3">
            <div class="settlement-sync-header">
                <div>
                    <div class="settlement-sync-eyebrow"><i class="bi bi-arrow-repeat"></i> Settlement Sync</div>
                </div>
                <div class="settlement-sync-actions-row">
                    <button class="btn btn-sm btn-ship-outline btn-pill" onclick="switchSettlementTab('data')">
                        <i class="bi bi-receipt"></i> Lihat Data
                    </button>
                    <button class="btn btn-sm btn-ship-outline btn-pill" onclick="refreshSettlementSyncLogs()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
            </div>

            <div id="settlementSyncFeedback" class="settlement-sync-feedback d-none"></div>

            <div id="settlementSyncProgressWrap" class="d-none" style="margin-top:1rem;">
                <div style="display:flex; justify-content:space-between; gap:.75rem; align-items:center; margin-bottom:.35rem;">
                    <div style="min-width:0;">
                        <div id="settlementSyncProgressLabel" style="font-size:.74rem; font-weight:800; color:var(--shp-text);">Menunggu proses…</div>
                        <div id="settlementSyncProgressContext" class="settlement-sync-progress-context" aria-live="polite"></div>
                    </div>
                    <div id="settlementSyncProgressPct" style="font-size:.72rem; font-weight:800; color:#1d4ed8;">0%</div>
                </div>
                <div class="progress" style="height:10px; border-radius:999px; background:rgba(148,163,184,.18); overflow:hidden;">
                    <div id="settlementSyncProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%; background:#3b82f6; transition:width .35s ease;"></div>
                </div>
                <div id="settlementSyncProgressMeta" style="display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.45rem; font-size:.68rem; color:var(--shp-muted);"></div>
            </div>

            <div class="settlement-sync-status-grid">
                <span id="settlementSyncStateChip" class="settlement-sync-chip">
                    <i class="bi bi-shield-check" style="color:#64748b;"></i>
                    <strong id="settlementSyncStateValue" style="color:#15803d;">Siap</strong>
                </span>
                <span class="settlement-sync-chip">
                    <i class="bi bi-check2-circle" style="color:#15803d;"></i>
                    Sync terakhir: <strong id="settlementSyncLastSuccess" style="color:var(--shp-text);">—</strong>
                </span>
                <span class="settlement-sync-chip">
                    <i class="bi bi-broadcast" style="color:#64748b;"></i>
                    Last check: <strong id="settlementSyncLastCheck" style="color:var(--shp-text);">—</strong>
                </span>
            </div>

            <div class="settlement-sync-action-strip">
                <div style="display:flex; flex-wrap:wrap; gap:.75rem; align-items:flex-end;">
                    <div class="settlement-sync-field" style="min-width:160px;">
                        <label for="syncPlatform">Platform</label>
                        <select class="form-select form-select-sm w-100" style="padding-left:26px; cursor:pointer;" id="syncPlatform">
                            <option value="">Semua Platform</option>
                        </select>
                        <i class="bi bi-globe2" style="position:absolute;left:8px;top:50%;transform:translateY(8px);color:#94a3b8;font-size:.75rem"></i>
                    </div>

                    <div class="settlement-sync-field">
                        <label for="syncStore">Toko</label>
                        <select class="form-select form-select-sm w-100" style="padding-left:26px; cursor:pointer;" id="syncStore">
                            <option value="">Pilih Toko</option>
                        </select>
                        <i class="bi bi-shop" style="position:absolute;left:8px;top:50%;transform:translateY(8px);color:#94a3b8;font-size:.75rem"></i>
                    </div>

                    <div class="settlement-sync-field" style="min-width:220px;">
                        <label for="syncBackfillMonths">Backfill cepat</label>
                        <select class="form-select form-select-sm w-100 mb-2" id="syncBackfillMonths">
                            <option value="1">1 bulan</option>
                            <option value="2">2 bulan</option>
                            <option value="3" selected>3 bulan</option>
                        </select>
                        <div class="btn-group btn-group-sm w-100" role="group" aria-label="Quick backfill">
                            <button type="button" class="btn btn-outline-secondary" id="runSettlementBackfill1Btn" onclick="runSettlementBackfill(1, 'runSettlementBackfill1Btn')">1 bln</button>
                            <button type="button" class="btn btn-outline-secondary" id="runSettlementBackfill2Btn" onclick="runSettlementBackfill(2, 'runSettlementBackfill2Btn')">2 bln</button>
                            <button type="button" class="btn btn-outline-secondary" id="runSettlementBackfill3Btn" onclick="runSettlementBackfill(3, 'runSettlementBackfill3Btn')">3 bln</button>
                        </div>
                    </div>

                    <div class="settlement-sync-actions-row" style="margin-left:auto;">
                        <button class="btn btn-sm btn-ship-primary btn-pill" id="runSettlementBtn" onclick="runSettlementSync()" title="Tarik nominal breakdown settlement order">
                            <i class="bi bi-arrow-down-circle"></i> Tarik Settlement
                        </button>
                        <button class="btn btn-sm btn-info btn-pill text-white" id="runSyncReleasedBtn" onclick="runSyncReleasedSettlements()" title="Tarik tanggal dana cair dari Shopee GetEscrowList">
                            <i class="bi bi-calendar-check"></i> Tarik Tanggal Cair
                        </button>
                        <button class="btn btn-sm btn-warning btn-pill" id="runIncomeEstimateBtn" onclick="runIncomeEstimateSync()" title="Perbarui estimasi payout order yang belum cair">
                            <i class="bi bi-clock-history"></i> Perbarui Estimasi
                        </button>
                        <button class="btn btn-sm btn-ship-outline btn-pill" id="runSettlementBackfillBtn" onclick="runSettlementBackfill()" title="Backfill data settlement beberapa bulan ke belakang">
                            <i class="bi bi-clock-history"></i> Jalankan Backfill
                        </button>
                    </div>
                </div>

            </div>

            <div id="settlementStoreHealth" class="settlement-store-health" aria-live="polite">
                <div class="settlement-store-health-head">
                    <div>
                        <div class="settlement-store-health-title"><i class="bi bi-shield-check me-1"></i>Kesiapan toko</div>
                        <div class="settlement-store-health-subtitle">Toko nonaktif dan credential bermasalah tidak akan diproses.</div>
                    </div>
                    <div id="settlementStoreHealthSummary" class="settlement-store-health-summary"></div>
                </div>
                <div id="settlementStoreHealthGrid" class="settlement-store-health-grid">
                    <div style="font-size:.72rem; color:var(--shp-muted);">Memuat status toko…</div>
                </div>
            </div>

            <details id="settlementSyncDetails" class="settlement-sync-details">
                <summary>
                    <span><i class="bi bi-activity me-1"></i> Riwayat</span>
                </summary>
                <div class="settlement-sync-details-body">
                    <div class="settlement-sync-advanced">
                        <div class="controls">
                            <button class="btn btn-sm btn-ship-outline btn-pill" id="runSettlementBgBtn" onclick="runSettlementSyncBackground()">
                                <i class="bi bi-cloud-download"></i> Sync Semua
                            </button>
                            <button type="button" onclick="clearSettlementSyncConsole()" class="btn btn-sm btn-ship-outline btn-pill">
                                <i class="bi bi-eraser"></i> Clear Log
                            </button>
                        </div>
                    </div>

                    <div class="settlement-sync-console-wrap">
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:.5rem;">
                            <span style="font-size:.72rem; font-weight:800; color:var(--shp-text);"><i class="bi bi-terminal"></i> Console</span>
                        </div>
                        <div id="settlementSyncConsole" class="settlement-sync-console">&gt; Kosong.</div>
                    </div>

                    <div class="card-main">
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:.75rem 1rem; border-bottom:1px solid var(--shp-border);">
                            <div style="font-weight:700; font-size:.85rem;"><i class="bi bi-clock-history" style="color:#64748b;"></i> Riwayat Sync Settlement</div>
                            <span style="font-size:.68rem; color:#94a3b8;">20 terakhir</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-list w-100" style="margin-bottom:0">
                                <thead>
                                    <tr>
                                        <th style="min-width:130px">Waktu</th>
                                        <th style="min-width:150px">Toko</th>
                                        <th style="min-width:90px">Status</th>
                                        <th style="min-width:180px">Ringkasan</th>
                                        <th style="min-width:220px">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody id="settlementSyncLogsBody">
                                    <tr>
                                        <td colspan="5" class="text-center py-4" style="color:var(--shp-muted); font-size:.8rem;">Memuat...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </details>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const { api, fmtDate, fmtRp, esc } = window.mpHelpers;
    const IS_OWNER = @json(auth()->user()?->role === 'owner');
    let settlements = [], stores = [], syncLogs = [];
    let fpOrderDate = null, fpSettlementDate = null;
    let currentPage = 1;
    let paginationData = null;
    let activeTab = sessionStorage.getItem('settlementActiveTab') || 'data';
    let syncPollTimer = null;
    let autoSyncPollTimer = null;
    let autoSyncRefreshInFlight = false;
    let autoSyncInitialized = false;
    let autoSyncLastActivityKey = '';
    let lastSettlementProgressKey = 'idle:idle';
    let settlementSyncViewVersion = 0;
    let settlementRequestVersion = 0;
    let settlementSyncRunId = null;
    let settlementSyncLastPercent = 0;
    let settlementSyncRefreshInFlight = false;
    const settlementFiltersStorageKey = 'marketplace:settlement_filters:v2';
    const $ = id => document.getElementById(id);

    function getSettlementFilterState() {
        const orderDates = fpOrderDate?.selectedDates || [];
        const settlementDates = fpSettlementDate?.selectedDates || [];

        return {
            store_id: $('filterStore')?.value || '',
            platform: $('filterPlatform')?.value || '',
            status: $('filterStatus')?.value || '',
            settlement_status: $('filterSettlementStatus')?.value || '',
            search: $('filterSearch')?.value || '',
            order_date_from: orderDates[0] ? fpOrderDate.formatDate(orderDates[0], 'Y-m-d') : '',
            order_date_to: orderDates[1] ? fpOrderDate.formatDate(orderDates[1], 'Y-m-d') : '',
            settlement_date_from: settlementDates[0] ? fpSettlementDate.formatDate(settlementDates[0], 'Y-m-d') : '',
            settlement_date_to: settlementDates[1] ? fpSettlementDate.formatDate(settlementDates[1], 'Y-m-d') : '',
        };
    }

    function saveSettlementFilterState() {
        try {
            localStorage.setItem(settlementFiltersStorageKey, JSON.stringify(getSettlementFilterState()));
        } catch (_) {}
    }

    function restoreSettlementFilterState() {
        let state = null;
        try {
            state = JSON.parse(localStorage.getItem(settlementFiltersStorageKey) || 'null');
        } catch (_) {
            state = null;
        }

        if (!state) return null;

        if ($('filterStore')) $('filterStore').value = state.store_id || '';
        if ($('filterPlatform')) $('filterPlatform').value = state.platform || '';
        if ($('filterStatus')) $('filterStatus').value = state.status || '';
        if ($('filterSettlementStatus')) {
            $('filterSettlementStatus').value = Object.prototype.hasOwnProperty.call(state, 'settlement_status')
                ? state.settlement_status
                : 'cair';
        }
        if ($('filterSearch')) $('filterSearch').value = state.search || '';

        if (fpOrderDate) {
            if (state.order_date_from && state.order_date_to) {
                fpOrderDate.setDate(
                    [state.order_date_from, state.order_date_to],
                    false,
                );
            } else {
                fpOrderDate.clear(false);
            }
        }

        if (fpSettlementDate) {
            if (state.settlement_date_from && state.settlement_date_to) {
                fpSettlementDate.setDate(
                    [state.settlement_date_from, state.settlement_date_to],
                    false,
                );
            } else {
                fpSettlementDate.clear(false);
            }
        }

        const syncStore = $('syncStore');
        if (syncStore && syncStore.dataset.manual !== '1') {
            syncStore.value = state.store_id || '';
        }

        return state;
    }

    function fmtDateTime(value) {
        if (!value) return '—';
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return String(value);
        return d.toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function fmtSettlementDate(value) {
        if (!value) return '—';
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return String(value);
        return d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    }

    function fmtRelativeTime(value) {
        if (!value) return 'belum pernah diperbarui';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return fmtDateTime(value);
        const seconds = Math.round((date.getTime() - Date.now()) / 1000);
        const formatter = new Intl.RelativeTimeFormat('id-ID', { numeric: 'auto' });
        if (Math.abs(seconds) < 60) return formatter.format(seconds, 'second');
        const minutes = Math.round(seconds / 60);
        if (Math.abs(minutes) < 60) return formatter.format(minutes, 'minute');
        const hours = Math.round(minutes / 60);
        if (Math.abs(hours) < 24) return formatter.format(hours, 'hour');
        return formatter.format(Math.round(hours / 24), 'day');
    }

    function updateSettlementModeLabels(meta = null) {
        const mode = $('filterSettlementStatus')?.value || '';
        const isPending = mode === 'belum_cair';
        const isFinal = mode === 'cair';
        const dateInput = $('filterSettlementDate');

        if ($('kpiCountLabel')) {
            $('kpiCountLabel').textContent = isPending ? 'Order Menunggu Cair' : (isFinal ? 'Order Cair' : 'Total Order');
        }
        if ($('kpiFeeLabel')) {
            $('kpiFeeLabel').textContent = isPending ? 'Estimasi Fee' : (isFinal ? 'Fee Marketplace' : 'Fee Final + Est.');
        }
        if ($('kpiNetPayoutLabel')) {
            $('kpiNetPayoutLabel').textContent = isPending ? 'Estimasi Dana Cair' : (isFinal ? 'Dana Cair (Final)' : 'Dana Final + Estimasi');
        }
        if ($('kpiNetPayout')) {
            $('kpiNetPayout').style.color = isPending ? '#d97706' : (isFinal ? '#16a34a' : '#2563eb');
        }
        if (dateInput) {
            const dateLabel = document.querySelector('label[for="filterSettlementDate"]');
            dateInput.placeholder = isPending ? 'Estimasi cair...' : (isFinal ? 'Cair final...' : 'Cair / estimasi...');
            dateInput.title = isPending
                ? 'Memfilter estimasi tanggal payout untuk order belum cair'
                : (isFinal
                    ? 'Memfilter tanggal pencairan final'
                    : 'Memfilter tanggal pencairan final atau estimasi payout');
            if (dateLabel) {
                dateLabel.textContent = isPending ? 'Estimasi cair' : (isFinal ? 'Tanggal cair' : 'Cair / estimasi');
            }
        }

        const freshness = $('kpiEstimateFreshness');
        if (freshness) {
            if (isPending && meta) {
                const shopeeCount = Number(meta.estimate_shopee_count || 0);
                const manualCount = Number(meta.estimate_manual_count || 0);
                const lastSync = meta.estimate_last_synced_at
                    ? ` • diperbarui ${fmtRelativeTime(meta.estimate_last_synced_at)}`
                    : '';
                freshness.textContent = `${shopeeCount} Shopee • ${manualCount} fallback 24%${lastSync}`;
                freshness.title = meta.estimate_last_synced_at ? `Sync terakhir ${fmtDateTime(meta.estimate_last_synced_at)}` : 'Belum ada estimasi Shopee';
            } else {
                freshness.textContent = '';
                freshness.removeAttribute('title');
            }
        }
    }

    window.onSettlementStatusChange = function () {
        currentPage = 1;
        updateSettlementModeLabels();
        loadSettlements();
    };

    function getSyncStoreId() {
        return $('syncStore') ? $('syncStore').value : '';
    }

    function isFinanceStore(store) {
        return ['shopee', 'shp', 'tiktok', 'ttk', 'tkt'].includes(String(store?.channel?.code || '').toLowerCase());
    }

    function isShopeeStore(store) {
        return ['shopee', 'shp'].includes(String(store?.channel?.code || '').toLowerCase());
    }

    function isTikTokStore(store) {
        return ['tiktok', 'ttk', 'tkt'].includes(String(store?.channel?.code || '').toLowerCase());
    }

    function isActiveStore(store) {
        return store?.status === 'active' && store?.is_active === true;
    }

    function isSettlementSyncReadyStore(store) {
        if (!isActiveStore(store) || !isFinanceStore(store)) return false;

        const connection = String(store?.connection_status || '').toUpperCase();
        if (!['CONNECTED', 'TOKEN_EXPIRED'].includes(connection)) return false;

        return !(isTikTokStore(store)
            && String(store?.meta?.tiktok_finance_scope_status || '').toLowerCase() === 'missing');
    }

    function updateSyncPlatformActions() {
        const selectedStore = stores.find(store => String(store.id) === String($('syncStore')?.value || ''));
        const isTikTok = selectedStore && isTikTokStore(selectedStore);
        const isShopee = selectedStore && isShopeeStore(selectedStore);

        [
            ['runSyncReleasedBtn', isShopee, 'Tarik tanggal dana cair dari Shopee Escrow List'],
            ['runIncomeEstimateBtn', isShopee, 'Perbarui estimasi payout Shopee yang belum cair'],
        ].forEach(([id, supported, title]) => {
            const button = $(id);
            if (!button) return;
            button.style.display = supported || !selectedStore ? '' : 'none';
            button.disabled = !selectedStore;
            button.title = supported ? title : (isTikTok ? 'Tidak berlaku untuk TikTok Finance' : title);
        });

        const settlementButton = $('runSettlementBtn');
        if (settlementButton) {
            settlementButton.disabled = !selectedStore;
            settlementButton.title = isTikTok
                ? 'Tarik statement final dari TikTok Finance API'
                : 'Tarik nominal breakdown settlement order';
        }

        ['runSettlementBackfill1Btn', 'runSettlementBackfill2Btn', 'runSettlementBackfill3Btn', 'runSettlementBackfillBtn', 'syncBackfillMonths']
            .forEach(id => {
                const control = $(id);
                if (control) control.disabled = !selectedStore;
            });
    }

    function storePlatformCode(store) {
        return String(store?.channel?.code || '').trim().toLowerCase();
    }

    function getSettlementSyncReadyStores() {
        return stores.filter(store => isSettlementSyncReadyStore(store));
    }

    function getSettlementSyncReadyPlatforms() {
        const platforms = new Map();
        getSettlementSyncReadyStores().forEach(store => {
            const code = storePlatformCode(store);
            const name = String(store.channel?.name || code || 'Platform').trim();
            if (code && !platforms.has(code)) platforms.set(code, name);
        });

        return platforms;
    }

    function populateSyncStoreSelect(preferredStoreId = '') {
        const platformSel = $('syncPlatform');
        const storeSel = $('syncStore');
        if (!storeSel) return;

        const selectedPlatform = String(platformSel?.value || '').trim().toLowerCase();
        const currentStoreId = String(preferredStoreId || storeSel.value || '');
        const candidates = getSettlementSyncReadyStores().filter(store =>
            !selectedPlatform || storePlatformCode(store) === selectedPlatform);

        storeSel.innerHTML = '<option value="">Pilih Toko siap sync</option>';
        candidates.forEach(store => {
            const option = document.createElement('option');
            option.value = store.id;
            option.textContent = `${store.name} (${store.channel?.name || '?'})`;
            storeSel.appendChild(option);
        });

        const hasCandidates = candidates.length > 0;
        storeSel.disabled = !hasCandidates;
        if (!hasCandidates) {
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = selectedPlatform ? 'Tidak ada toko siap sync' : 'Tidak ada toko siap sync';
            emptyOption.disabled = true;
            storeSel.appendChild(emptyOption);
            storeSel.value = '';
        }

        if (candidates.some(store => String(store.id) === currentStoreId)) {
            storeSel.value = currentStoreId;
        } else if (candidates.length === 1) {
            storeSel.value = String(candidates[0].id);
        }
    }

    function populateFilterStoreSelect(preferredStoreId = '') {
        const platformSel = $('filterPlatform');
        const storeSel = $('filterStore');
        if (!storeSel) return;

        const selectedPlatform = String(platformSel?.value || '').trim().toLowerCase();
        const currentStoreId = String(preferredStoreId || storeSel.value || '');
        const candidates = stores.filter(store => !selectedPlatform
            || storePlatformCode(store) === selectedPlatform);

        storeSel.innerHTML = '<option value="">Semua Toko</option>';
        candidates.forEach(store => {
            const option = document.createElement('option');
            option.value = store.id;
            option.textContent = `${store.name} (${store.channel?.name || '?'})`;
            storeSel.appendChild(option);
        });

        if (candidates.some(store => String(store.id) === currentStoreId)) {
            storeSel.value = currentStoreId;
        }
    }

    function getStoreSyncState(store) {
        if (!isActiveStore(store)) {
            return { key: 'inactive', label: 'Nonaktif', icon: 'bi-pause-circle', note: 'Aktifkan toko untuk sync.' };
        }
        if (!isFinanceStore(store)) {
            return { key: 'attention', label: 'Tidak didukung', icon: 'bi-question-circle', note: 'Finance settlement tersedia untuk Shopee dan TikTok.' };
        }

        const connection = String(store.connection_status || '').toUpperCase();
        if (isTikTokStore(store) && String(store.meta?.tiktok_finance_scope_status || '').toLowerCase() === 'missing') {
            return {
                key: 'attention',
                label: 'Scope Finance belum aktif',
                icon: 'bi-shield-exclamation',
                note: 'Aktifkan seller.finance.info, lalu hubungkan ulang toko TikTok.',
            };
        }
        if (connection === 'CONNECTED') {
            return { key: 'ready', label: 'Siap sync', icon: 'bi-check-circle', note: isTikTokStore(store) ? 'Finance API TikTok v202501 siap.' : 'Credential aktif.' };
        }
        if (connection === 'TOKEN_EXPIRED') {
            return { key: 'refresh', label: 'Refresh token', icon: 'bi-arrow-repeat', note: 'Akan dicoba refresh otomatis.' };
        }
        if (connection === 'NOT_CONNECTED') {
            return { key: 'attention', label: 'Belum terhubung', icon: 'bi-plug', note: 'Hubungkan toko terlebih dahulu.' };
        }
        if (connection === 'INVALID_APP_KEY') {
            return { key: 'attention', label: 'APP_KEY invalid', icon: 'bi-shield-exclamation', note: 'Periksa APP_KEY/config server.' };
        }
        return { key: 'attention', label: 'Perlu perhatian', icon: 'bi-exclamation-triangle', note: 'Koneksi toko belum siap.' };
    }

    function getStoreAction(store, state) {
        if (state.key !== 'attention' || !isFinanceStore(store)) return null;
        if (isTikTokStore(store)
            && String(store.meta?.tiktok_finance_scope_status || '').toLowerCase() === 'missing') {
            return { url: '/marketplace/tiktok/connect', label: 'Hubungkan ulang' };
        }
        if (String(store.connection_status || '').toUpperCase() === 'NOT_CONNECTED') {
            return { url: isTikTokStore(store) ? '/marketplace/tiktok/connect' : '/marketplace/shopee/connect', label: 'Hubungkan' };
        }
        return { url: '/marketplace/settings', label: 'Buka pengaturan' };
    }

    function renderStoreHealth() {
        const summary = $('settlementStoreHealthSummary');
        const grid = $('settlementStoreHealthGrid');
        if (!summary || !grid) return;

        if (!stores.length) {
            summary.innerHTML = '';
            grid.innerHTML = '<div style="font-size:.72rem; color:var(--shp-muted);">Tidak ada data toko.</div>';
            return;
        }

        const states = stores.map(store => ({ store, state: getStoreSyncState(store) }));
        const ready = states.filter(row => ['ready', 'refresh'].includes(row.state.key)).length;
        const attention = states.filter(row => row.state.key === 'attention').length;
        const inactive = states.filter(row => row.state.key === 'inactive').length;
        summary.innerHTML = `
            <span class="ready"><i class="bi bi-check-circle"></i>${ready} siap</span>
            <span class="attention"><i class="bi bi-exclamation-triangle"></i>${attention} perhatian</span>
            <span class="inactive"><i class="bi bi-pause-circle"></i>${inactive} nonaktif</span>
        `;

        grid.innerHTML = states.map(({ store, state }) => {
            const action = getStoreAction(store, state);
            const actionHtml = action
                ? `<a href="${esc(action.url)}" class="btn btn-sm btn-ship-outline btn-pill" style="font-size:.64rem; padding:.2rem .45rem; margin-top:.35rem;">${esc(action.label)}</a>`
                : '';
            return `
                <div class="settlement-store-health-card">
                    <div style="min-width:0;">
                        <strong title="${esc(store.name || '')}">${esc(store.name || 'Toko tanpa nama')}</strong>
                        <small>${esc(store.channel?.name || 'Channel tidak dikenal')} · ${esc(state.note)}</small>
                        ${actionHtml}
                    </div>
                    <span class="settlement-store-health-status ${esc(state.key)}"><i class="bi ${esc(state.icon)}"></i>${esc(state.label)}</span>
                </div>
            `;
        }).join('');
    }

    function presentSyncError(error, fallbackTitle = 'Sync gagal') {
        const code = String(error?.data?.code || '').toUpperCase();
        const fallback = error?.data?.message || error?.message || 'Proses settlement belum berhasil.';
        const map = {
            STORE_INACTIVE: { level: 'warn', title: 'Toko nonaktif', message: 'Aktifkan toko terlebih dahulu sebelum menjalankan settlement sync.' },
            STORE_NOT_CONNECTED: { level: 'warn', title: 'Toko belum terhubung', message: fallback },
            SHOPEE_AUTH_REQUIRED: { level: 'warn', title: 'Login ulang diperlukan', message: fallback },
            SETTLEMENT_CHANNEL_UNSUPPORTED: { level: 'warn', title: 'Channel belum didukung', message: fallback },
            SETTLEMENT_SYNC_BUSY: { level: 'warn', title: 'Sync sedang berjalan', message: fallback },
            SETTLEMENT_QUEUE_ERROR: { level: 'error', title: 'Queue tidak tersedia', message: 'Sync belum masuk antrian. Pastikan queue worker berjalan, lalu coba lagi.' },
            SETTLEMENT_SYNC_ERROR: { level: 'error', title: 'Sync settlement gagal', message: fallback },
            INCOME_DETAIL_SYNC_BUSY: { level: 'warn', title: 'Sync lain sedang berjalan', message: fallback },
            INCOME_DETAIL_SYNC_ERROR: { level: 'error', title: 'Estimasi gagal diperbarui', message: fallback },
        };
        const item = map[code] || { level: 'error', title: fallbackTitle, message: fallback };
        const action = error?.data?.action?.type === 'redirect'
            ? { url: error.data.action.url, label: error.data.action.label }
            : (code === 'SETTLEMENT_QUEUE_ERROR' ? { url: '#settlementSyncDetails', label: 'Cek status queue' } : null);
        return { ...item, code, action };
    }

    function setSyncState(status, text) {
        const chip = $('settlementSyncStateChip');
        const value = $('settlementSyncStateValue');
        if (!chip || !value) return;

        chip.classList.remove('success', 'running', 'warn', 'error');
        if (status === 'running') chip.classList.add('running');
        else if (status === 'success') chip.classList.add('success');
        else if (status === 'warn') chip.classList.add('warn');
        else if (status === 'error') chip.classList.add('error');

        value.textContent = text;
    }

    function updateSyncDot(visible) {
        const dot = $('settlementSyncDot');
        if (!dot) return;
        dot.style.display = visible ? 'inline-block' : 'none';
    }

    function setAutomaticSyncBanner(variant, title, message, meta = '') {
        const banner = $('settlementAutoSyncBanner');
        const icon = $('settlementAutoSyncBannerIcon');
        const titleNode = $('settlementAutoSyncBannerTitle');
        const messageNode = $('settlementAutoSyncBannerMessage');
        const metaNode = $('settlementAutoSyncBannerMeta');
        if (!banner || !icon || !titleNode || !messageNode || !metaNode) return;

        const safeVariant = ['running', 'success', 'warn', 'error'].includes(variant) ? variant : '';
        const iconClass = safeVariant === 'running'
            ? 'bi-arrow-repeat'
            : (safeVariant === 'success'
                ? 'bi-check-circle'
                : (safeVariant === 'warn' ? 'bi-exclamation-triangle' : (safeVariant === 'error' ? 'bi-x-circle' : 'bi-broadcast-pin')));

        banner.className = 'settlement-auto-sync-banner' + (safeVariant ? ` ${safeVariant}` : '');
        icon.innerHTML = `<i class="bi ${iconClass}"></i>`;
        titleNode.textContent = title || 'Auto-sync marketplace';
        messageNode.textContent = message || '';
        metaNode.textContent = meta || '';
    }

    function autoSyncStoreLabel(progress) {
        const store = stores.find(item => String(item.id) === String(progress?.store_id || ''));
        return {
            platform: progress?.platform || store?.channel?.name || 'Marketplace',
            store: progress?.store_name || store?.name || 'Toko marketplace',
        };
    }

    // Progress queued/running harus punya batas waktu. Jika queue worker mati
    // atau request terputus, cache lama tidak boleh terus terlihat sebagai
    // sync aktif di halaman.
    function normalizeSettlementProgress(progress) {
        if (!progress || typeof progress !== 'object') return progress;

        const status = String(progress.status || '').toLowerCase();
        if (!['queued', 'running'].includes(status) || !progress.updated_at) return progress;

        const updatedAt = Date.parse(progress.updated_at);
        if (Number.isNaN(updatedAt)) return progress;

        const staleAfterMs = status === 'queued'
            ? 15 * 60 * 1000
            : 60 * 60 * 1000;
        if (Date.now() - updatedAt <= staleAfterMs) return progress;

        return {
            ...progress,
            status: 'stale',
            phase: 'stale',
            stale: true,
            percent: null,
            label: status === 'queued'
                ? 'Sync masih antre tetapi belum diambil worker.'
                : 'Tidak ada pembaruan sync dalam waktu yang ditentukan.',
        };
    }

    function autoSyncLogSummary(log) {
        const payload = log?.payload || {};
        return [
            payload.synced !== undefined ? `Settlement ${payload.synced}` : null,
            payload.pending !== undefined ? `Pending ${payload.pending}` : null,
            payload.estimates_updated !== undefined ? `Estimasi ${payload.estimates_updated}` : null,
            payload.errors !== undefined ? `Error ${payload.errors}` : null,
        ].filter(Boolean).join(' · ');
    }

    function renderAutomaticSyncStatus(logs, progressRows) {
        const normalizedProgressRows = progressRows
            .map(normalizeSettlementProgress)
            .filter(Boolean);
        const activeProgress = normalizedProgressRows
            .filter(progress => ['queued', 'running'].includes(String(progress?.status || '').toLowerCase()))
            .sort((a, b) => Number(b?.percent || 0) - Number(a?.percent || 0));
        const latestLog = [...logs].sort((a, b) => new Date(b?.created_at || 0) - new Date(a?.created_at || 0))[0] || null;
        const latestProgress = [...normalizedProgressRows]
            .filter(progress => ['success', 'warn', 'error', 'stale'].includes(String(progress?.status || '').toLowerCase()))
            .sort((a, b) => new Date(b?.updated_at || 0) - new Date(a?.updated_at || 0))[0] || null;

        if (activeProgress.length) {
            const progress = activeProgress[0];
            const labels = autoSyncStoreLabel(progress);
            const status = String(progress.status || '').toLowerCase();
            const progressText = progress.percent !== undefined && progress.percent !== null
                ? `Progress ${Math.max(0, Math.min(100, Number(progress.percent)))}%`
                : 'Sedang diproses';
            const key = `progress:${progress.run_id || progress.store_id}:${status}:${progress.updated_at || progress.label || ''}`;
            setAutomaticSyncBanner(
                'running',
                status === 'queued' ? 'Sync otomatis sedang antre' : 'Sync otomatis sedang berjalan',
                `${labels.platform} · ${labels.store}: ${progress.label || 'data sedang disinkronkan.'}`,
                `${progressText} · halaman akan memperbarui data setelah proses selesai`,
            );
            return { key, active: true, progress };
        }

        const logStatus = String(latestLog?.status || '').toLowerCase();
        const logTime = latestLog?.created_at ? new Date(latestLog.created_at) : null;
        const logIsActive = logStatus === 'processing'
            && logTime
            && !Number.isNaN(logTime.getTime())
            && (Date.now() - logTime.getTime()) <= 60 * 60 * 1000;
        if (logIsActive) {
            const labels = autoSyncStoreLabel(latestLog);
            const key = `log:${latestLog.id || latestLog.store_id || 'all'}:processing:${latestLog.created_at || ''}`;
            setAutomaticSyncBanner(
                'running',
                'Sync otomatis sedang berjalan',
                `${labels.platform} · ${labels.store}: ${latestLog.message || 'sedang memproses data.'}`,
                'Progress detail sedang dikumpulkan dari worker.',
            );
            return { key, active: true };
        }
        const progressTime = latestProgress?.updated_at ? new Date(latestProgress.updated_at) : null;
        const latestTime = [logTime, progressTime]
            .filter(date => date && !Number.isNaN(date.getTime()))
            .sort((a, b) => b.getTime() - a.getTime())[0] || null;
        const ageMinutes = latestTime ? (Date.now() - latestTime.getTime()) / 60000 : Infinity;
        const terminalProgressIsRecent = latestProgress && progressTime && ageMinutes <= 15;
        const source = terminalProgressIsRecent ? latestProgress : latestLog;
        const sourceStatus = String(source?.status || '').toLowerCase();
        const labels = autoSyncStoreLabel(source || {});
        const sourceTime = source?.updated_at || source?.created_at || null;
        const summary = source === latestProgress
            ? [
                source.synced !== undefined ? `Settlement ${source.synced}` : null,
                source.pending !== undefined ? `Pending ${source.pending}` : null,
                source.errors !== undefined ? `Error ${source.errors}` : null,
            ].filter(Boolean).join(' · ')
            : autoSyncLogSummary(source);
        const key = source
            ? `terminal:${source.run_id || source.id || source.store_id || 'all'}:${sourceStatus}:${sourceTime || ''}`
            : 'idle';

        if (!source) {
            setAutomaticSyncBanner('', 'Auto-sync aktif', 'Menunggu jadwal sync Shopee dan TikTok berikutnya.', 'Status diperiksa otomatis setiap 10 detik.');
            return { key, active: false };
        }

        if (sourceStatus === 'stale') {
            setAutomaticSyncBanner('warn', 'Sync otomatis tidak berjalan', `${labels.platform} · ${labels.store}: progress lama sudah kedaluwarsa.`, 'Jalankan ulang sync dari tab Sync untuk memulai proses baru.');
        } else if (sourceStatus === 'error' || sourceStatus === 'failed') {
            setAutomaticSyncBanner('error', 'Sync otomatis perlu diperiksa', `${labels.platform} · ${labels.store}: proses terakhir gagal.`, summary || 'Buka tab Sync untuk melihat detail error.');
        } else if (sourceStatus === 'warn' || sourceStatus === 'partial_success') {
            setAutomaticSyncBanner('warn', 'Sync otomatis selesai dengan catatan', `${labels.platform} · ${labels.store}: sebagian data selesai diproses.`, summary || 'Buka tab Sync untuk melihat detail.');
        } else {
            setAutomaticSyncBanner('success', 'Sync otomatis selesai', `${labels.platform} · ${labels.store}: data settlement sudah diperbarui.`, summary || `Terakhir diperbarui ${fmtDateTime(sourceTime)}.`);
        }

        return { key, active: false, terminal: true, sourceTime };
    }

    async function refreshAutomaticSyncStatus() {
        if (autoSyncRefreshInFlight || document.visibilityState === 'hidden') return;
        autoSyncRefreshInFlight = true;

        try {
            const logRequests = [
                api('/api/marketplace/sync-logs?action=sync_settlements&all=1').catch(() => []),
                api('/api/marketplace/sync-logs?action=sync_finance').catch(() => []),
            ];
            const progressRequests = stores
                .filter(store => isFinanceStore(store))
                .map(store => api(`/api/marketplace/stores/${store.id}/sync-settlements-progress`).catch(() => null));
            const [settlementLogs, financeLogs, ...progressRows] = await Promise.all([...logRequests, ...progressRequests]);
            const logs = [...(settlementLogs || []), ...(financeLogs || [])]
                .filter(log => ['sync_settlements', 'sync_finance'].includes(log.action));
            const rendered = renderAutomaticSyncStatus(logs, progressRows.filter(Boolean));

            if (autoSyncInitialized && rendered.terminal && rendered.key !== autoSyncLastActivityKey) {
                currentPage = 1;
                loadSettlements();
            }
            autoSyncLastActivityKey = rendered.key;
            autoSyncInitialized = true;
        } catch (_) {
            setAutomaticSyncBanner('warn', 'Status auto-sync belum terbaca', 'Halaman belum bisa memeriksa proses Shopee/TikTok.', 'Coba refresh halaman atau buka tab Sync untuk detail.');
        } finally {
            autoSyncRefreshInFlight = false;
        }
    }

    function startAutomaticSyncPolling() {
        if (autoSyncPollTimer) return;
        refreshAutomaticSyncStatus();
        autoSyncPollTimer = setInterval(refreshAutomaticSyncStatus, 10000);
    }

    function renderSettlementProgress(progress) {
        progress = normalizeSettlementProgress(progress);
        const wrap = $('settlementSyncProgressWrap');
        const bar = $('settlementSyncProgressBar');
        const label = $('settlementSyncProgressLabel');
        const context = $('settlementSyncProgressContext');
        const pct = $('settlementSyncProgressPct');
        const meta = $('settlementSyncProgressMeta');
        if (!wrap || !bar || !label || !context || !pct || !meta) return null;

        const status = String(progress?.status || 'idle').toLowerCase();
        const isActive = ['queued', 'running'].includes(status);
        const isStale = status === 'stale';

        if (status === 'idle' || progress === null) {
            wrap.classList.add('d-none');
            bar.style.width = '0%';
            bar.style.background = '#3b82f6';
            bar.classList.add('progress-bar-striped', 'progress-bar-animated');
            label.textContent = 'Menunggu proses…';
            context.innerHTML = '';
            pct.textContent = '0%';
            meta.innerHTML = '';
            return { status: 'idle', terminal: false };
        }

        const rawPercent = progress.percent ?? (isActive ? settlementSyncLastPercent || 5 : 100);
        const percent = Math.max(0, Math.min(100, Number(rawPercent)));
        settlementSyncLastPercent = percent;
        const completion = isActive ? null : getSettlementCompletionPresentation(progress);
        const statusLabel = isActive
            ? (status === 'queued' ? 'Menunggu antrean sync' : 'Sedang sync order')
            : (completion?.title || progress.label || 'Sync selesai');
        const visualStatus = isActive ? status : completion.level;
        const color = visualStatus === 'success' ? '#16a34a'
            : (visualStatus === 'warn' ? '#d97706'
            : (visualStatus === 'error' ? '#dc2626'
            : (visualStatus === 'queued' ? '#2563eb' : '#3b82f6')));

        wrap.classList.remove('d-none');
        bar.style.width = isStale ? '0%' : percent + '%';
        bar.style.background = color;
        bar.classList.toggle('progress-bar-striped', isActive);
        bar.classList.toggle('progress-bar-animated', isActive);
        label.textContent = statusLabel;
        pct.textContent = isStale ? '—' : percent + '%';
        pct.style.color = color;

        const selectedStore = stores.find(store => String(store.id) === String(progress?.store_id || getSyncStoreId()));
        const platform = progress?.platform || selectedStore?.channel?.name || 'Platform marketplace';
        const storeName = progress?.store_name || selectedStore?.name || 'Toko terpilih';
        const dateFrom = progress?.date_from || progress?.from || '';
        const dateTo = progress?.date_to || progress?.to || '';
        const formatSyncDate = value => {
            if (!value) return '';
            const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);
            if (Number.isNaN(date.getTime())) return String(value);
            return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        };
        const dateRange = dateFrom && dateTo
            ? `Tanggal order: ${formatSyncDate(dateFrom)} – ${formatSyncDate(dateTo)}`
            : 'Tanggal order: rentang sync aktif';
        context.innerHTML = `<strong>${esc(platform)}</strong><span class="separator">·</span><strong>${esc(storeName)}</strong><span class="separator">·</span><span>${esc(dateRange)}</span>`;

        // Detail angka ditampilkan di satu feedback final saja. Menyembunyikan
        // meta terminal mencegah Found/Processed/Synced muncul dua kali.
        const metaBits = [];
        if (isActive && progress.batch !== undefined && progress.max_batches !== undefined && progress.max_batches !== null) {
            metaBits.push(`Batch ${progress.batch}/${progress.max_batches}`);
        }
        if (isActive && progress.found !== undefined) metaBits.push(`Order ditemukan ${progress.found}`);
        if (isActive && progress.processed !== undefined) metaBits.push(`Order diproses ${progress.processed}`);
        if (isActive && progress.synced !== undefined) metaBits.push(`Settlement diperbarui ${progress.synced}`);
        if (isActive && progress.pending !== undefined) metaBits.push(`Menunggu settlement ${progress.pending}`);
        if (isActive && progress.errors !== undefined) metaBits.push(`Error ${progress.errors}`);
        if (isActive && progress.skipped !== undefined) metaBits.push(`Dilewati ${progress.skipped}`);
        if (isActive && progress.current_order) metaBits.push(`Order ${progress.current_order}`);
        if (isActive && progress.retry_after !== undefined && progress.retry_after !== null) metaBits.push(`Tunggu ${progress.retry_after}s`);
        meta.innerHTML = metaBits.map(item => `<span style="display:inline-flex; align-items:center; gap:.25rem; padding:.12rem .45rem; border-radius:999px; background:rgba(148,163,184,.12); color:var(--shp-muted);">${esc(item)}</span>`).join('');

        return {
            status,
            terminal: ['success', 'warn', 'error', 'stale'].includes(status),
            percent,
            label: statusLabel,
        };
    }

    function getSettlementCompletionPresentation(progress) {
        const baseStatus = String(progress?.status || '').toLowerCase();
        const found = Number(progress?.found || 0);
        const processed = Number(progress?.processed || 0);
        const synced = Number(progress?.synced || 0);
        const pending = Number(progress?.pending || 0);
        const estimates = Number(progress?.estimates_updated || 0);
        const errors = Number(progress?.errors || 0) + Number(progress?.statement_ad_cost_errors || 0);

        if (baseStatus === 'stale') {
            const platform = progress?.platform || 'Platform marketplace';
            const storeName = progress?.store_name || 'Toko terpilih';

            return {
                level: 'warn',
                state: 'Tidak berjalan',
                title: 'Sync tidak berjalan',
                message: progress?.label || 'Progress sync sudah kedaluwarsa dan tidak lagi dianggap aktif. Jalankan ulang sync.',
                meta: [`${platform} · ${storeName}`, 'Progress lama tidak dihitung sebagai proses aktif.'],
            };
        }

        if (baseStatus === 'error') {
            return {
                level: 'error',
                state: 'Gagal',
                title: 'Sync gagal',
                message: progress?.label || 'Finance TikTok belum berhasil diproses.',
            };
        }

        const hasPending = pending > 0;
        const hasErrors = errors > 0;
        const hasExplicitWarn = baseStatus === 'warn';
        const level = hasErrors || hasPending || hasExplicitWarn ? 'warn' : 'success';
        const state = hasErrors ? 'Parsial' : (hasPending ? 'Pending' : (hasExplicitWarn ? 'Parsial' : 'Siap'));
        const platform = progress?.platform || 'Platform marketplace';
        const storeName = progress?.store_name || 'Toko terpilih';
        const title = hasErrors || hasExplicitWarn
            ? `Sync ${storeName} selesai dengan catatan`
            : `Sync ${storeName} selesai`;
        const message = hasPending
            ? `${platform}: ${pending} order masih menunggu settlement. Estimasi payout diperbarui untuk ${estimates} order.`
            : `${platform}: ${synced} settlement final diperbarui${estimates ? ` dan ${estimates} estimasi payout` : ''}.`;
        const formatSyncDate = value => {
            if (!value) return '';
            const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);
            if (Number.isNaN(date.getTime())) return String(value);
            return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        };
        const completionDateFrom = progress?.date_from || progress?.from || '';
        const completionDateTo = progress?.date_to || progress?.to || '';
        const range = completionDateFrom && completionDateTo
            ? `${formatSyncDate(completionDateFrom)} – ${formatSyncDate(completionDateTo)}`
            : null;

        return {
            level,
            state,
            title,
            message,
            meta: [
                `${platform} · ${storeName}`,
                range ? `Tanggal order ${range}` : null,
                `Order diperiksa ${found}`,
                `Order diproses ${processed}`,
                `Settlement final ${synced}`,
                `Menunggu settlement ${pending}`,
                `Estimasi payout ${estimates}`,
                `Error ${errors}`,
            ].filter(Boolean),
        };
    }

    function showSyncFeedback(level, title, message, metaLines = [], action = null) {
        const box = $('settlementSyncFeedback');
        if (!box) return;

        const variant = ['success', 'warn', 'error', 'info'].includes(level) ? level : 'info';
        box.className = 'settlement-sync-feedback ' + variant;

        const metaHtml = (metaLines || []).filter(Boolean).map(item => `<span>${esc(String(item))}</span>`).join('');
        const actionHtml = action?.url
            ? `<a href="${esc(action.url)}" class="btn btn-sm btn-ship-outline btn-pill" style="margin-top:.65rem;">${esc(action.label || 'Buka Aksi')}</a>`
            : '';

        box.innerHTML = `
            <i class="bi ${variant === 'success' ? 'bi-check-circle' : (variant === 'warn' ? 'bi-exclamation-triangle' : (variant === 'error' ? 'bi-x-circle' : 'bi-info-circle'))}" style="margin-top:.1rem; font-size:1rem; color:${variant === 'success' ? '#16a34a' : (variant === 'warn' ? '#d97706' : (variant === 'error' ? '#dc2626' : '#2563eb'))};"></i>
            <div style="min-width:0; flex:1 1 auto;">
                <strong>${esc(title || 'Info')}</strong>
                <div style="color:var(--shp-text); line-height:1.45; margin-top:.1rem;">${esc(message || '')}</div>
                ${metaHtml ? `<div class="settlement-feedback-meta">${metaHtml}</div>` : ''}
                ${actionHtml}
            </div>
        `;
        box.classList.remove('d-none');
    }

    function hideSyncFeedback() {
        const box = $('settlementSyncFeedback');
        if (!box) return;
        box.className = 'settlement-sync-feedback d-none';
        box.innerHTML = '';
    }

    function clearSettlementSyncConsole() {
        const box = $('settlementSyncConsole');
        if (box) box.innerHTML = '&gt; Kosong.';
    }

    window.clearSettlementSyncConsole = clearSettlementSyncConsole;

    function populateStoreSelects() {
        const filterSel = $('filterStore');
        const platformSel = $('filterPlatform');
        const syncPlatformSel = $('syncPlatform');
        const syncSel = $('syncStore');
        if (!filterSel || !syncSel) return;

        filterSel.innerHTML = '<option value="">Semua Toko</option>';
        if (platformSel) platformSel.innerHTML = '<option value="">Semua Platform</option>';
        if (syncPlatformSel) syncPlatformSel.innerHTML = '<option value="">Platform siap sync</option>';
        syncSel.innerHTML = '<option value="">Pilih Toko</option>';

        const platforms = new Map();
        stores.forEach(s => {
            const code = String(s.channel?.code || '').trim().toLowerCase();
            const name = String(s.channel?.name || code || 'Platform').trim();
            if (code && !platforms.has(code)) platforms.set(code, name);
        });
        if (platformSel) {
            platforms.forEach((name, code) => {
                const option = document.createElement('option');
                option.value = code;
                option.textContent = name;
                platformSel.appendChild(option);
            });
        }

        if (syncPlatformSel) {
            const readyStores = getSettlementSyncReadyStores();
            const readyPlatforms = getSettlementSyncReadyPlatforms();
            syncPlatformSel.innerHTML = '<option value="">Platform siap sync</option>';
            readyPlatforms.forEach((name, code) => {
                const option = document.createElement('option');
                option.value = code;
                option.textContent = name;
                syncPlatformSel.appendChild(option);
            });
            syncPlatformSel.disabled = readyPlatforms.size === 0;
            syncSel.disabled = readyStores.length === 0;
        }

        populateFilterStoreSelect();
        populateSyncStoreSelect();
        renderStoreHealth();
    }

    function startSyncPolling() {
        if (syncPollTimer) return;
        syncPollTimer = setInterval(() => {
            if (activeTab === 'sync') {
                refreshSettlementSyncLogs();
            }
        }, 5000);
    }

    function stopSyncPolling() {
        if (!syncPollTimer) return;
        clearInterval(syncPollTimer);
        syncPollTimer = null;
    }

    function renderSyncConsole() {
        const box = $('settlementSyncConsole');
        if (!box) return;

        if (!syncLogs.length) {
            box.innerHTML = '&gt; Kosong.';
            return;
        }

        box.innerHTML = syncLogs.slice(0, 5).map(log => {
            const status = String(log.status || '').toLowerCase();
            const cls = status === 'success'
                ? 'settlement-sync-log-ok'
                : (status === 'processing' ? 'settlement-sync-log-muted' : (status === 'partial_success' ? 'settlement-sync-log-warn' : 'settlement-sync-log-error'));

            return `
                <div style="margin-bottom:.35rem;">
                    <span class="settlement-sync-log-muted">[${esc(fmtDateTime(log.created_at))}]</span>
                    <span class="${cls}" style="font-weight:800;">${esc(String(log.status || '-').toUpperCase())}</span>
                    <span class="settlement-sync-log-muted">${log.store_name ? '[' + esc(log.store_name) + '] ' : ''}</span>
                    <span>${esc(log.message || '—')}</span>
                </div>
            `;
        }).join('');
    }

    function renderSyncLogs() {
        const body = $('settlementSyncLogsBody');
        if (!body) return;

        if (!syncLogs.length) {
            body.innerHTML = '<tr><td colspan="5" class="text-center py-4" style="color:var(--shp-muted); font-size:.8rem;">Riwayat kosong.</td></tr>';
            return;
        }

        body.innerHTML = syncLogs.slice(0, 20).map(log => {
            const status = String(log.status || '').toLowerCase();
            const badge = status === 'success'
                ? '<span class="oc-badge oc-badge-green">SUCCESS</span>'
                : (status === 'partial_success'
                    ? '<span class="oc-badge oc-badge-amber">PARTIAL</span>'
                    : (status === 'processing'
                        ? '<span class="oc-badge oc-badge-muted">PROCESS</span>'
                        : '<span class="oc-badge oc-badge-red">FAILED</span>'));

            const payload = log.payload || {};
            const summaryParts = [];
            if (payload.found !== undefined) summaryParts.push('Found: ' + payload.found);
            if (payload.synced !== undefined) summaryParts.push('Synced: ' + payload.synced);
            if (payload.new !== undefined) summaryParts.push('Baru: ' + payload.new);
            if (payload.updated !== undefined) summaryParts.push('Update: ' + payload.updated);
            if (payload.errors !== undefined) summaryParts.push('Error: ' + payload.errors);
            if (payload.skipped !== undefined) summaryParts.push('Skip: ' + payload.skipped);

            return `
                <tr>
                    <td style="font-size:.78rem; color:var(--shp-muted); white-space:nowrap;">${esc(fmtDateTime(log.created_at))}</td>
                    <td style="font-size:.78rem; font-weight:600;">${esc(log.store_name || '—')}</td>
                    <td>${badge}</td>
                    <td style="font-size:.76rem; color:var(--shp-text);">${esc(summaryParts.length ? summaryParts.join(' · ') : log.action || '—')}</td>
                    <td style="font-size:.76rem; color:var(--shp-muted); max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${esc(log.message || '—')}">${esc(log.message || '—')}</td>
                </tr>
            `;
        }).join('');
    }

    async function refreshSettlementSyncLogs() {
        if (settlementSyncRefreshInFlight) return syncLogs;
        settlementSyncRefreshInFlight = true;
        const refreshVersion = settlementSyncViewVersion;
        const expectedRunId = settlementSyncRunId;

        const lastCheck = $('settlementSyncLastCheck');
        if (lastCheck) lastCheck.textContent = fmtDateTime(new Date().toISOString());

        try {
            const storeId = getSyncStoreId();
            if (!storeId) {
                syncLogs = [];
                renderSettlementProgress(null);
                hideSyncFeedback();
                settlementSyncRunId = null;
                lastSettlementProgressKey = 'idle:idle';
                setSyncState('warn', 'Pilih toko');
                updateSyncDot(false);
                if ($('settlementSyncLastSuccess')) $('settlementSyncLastSuccess').textContent = '—';
                renderSyncConsole();
                renderSyncLogs();
                return syncLogs;
            }

            const logRequests = ['sync_settlements', 'sync_finance'].map(action => {
                const params = new URLSearchParams({ action, store_id: String(storeId) });
                return api('/api/marketplace/sync-logs?' + params.toString()).catch(() => []);
            });
            const [settlementLogs, financeLogs, rawProgress] = await Promise.all([
                ...logRequests,
                api('/api/marketplace/stores/' + storeId + '/sync-settlements-progress').catch(() => null),
            ]);
            const progress = normalizeSettlementProgress(rawProgress);

            // Polling lama dapat selesai setelah user memulai run baru. Jangan
            // izinkan response lama mengembalikan feedback/status run sebelumnya.
            if (refreshVersion !== settlementSyncViewVersion) {
                return syncLogs;
            }

            if (expectedRunId && progress?.run_id && String(progress.run_id) !== String(expectedRunId)) {
                return syncLogs;
            }

            syncLogs = [...(settlementLogs || []), ...(financeLogs || [])]
                .filter(log => ['sync_settlements', 'sync_finance'].includes(log.action))
                .sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));

            const latest = syncLogs[0] || null;
            const lastSuccess = syncLogs.find(log => log.status === 'success') || null;
            const progressState = renderSettlementProgress(progress);
            const progressStatus = String(progress?.status || 'idle').toLowerCase();
            const progressActive = ['queued', 'running'].includes(progressStatus);
            const progressRunId = String(progress?.run_id || settlementSyncRunId || 'legacy');
            const progressKey = `${progressRunId}:${progressStatus}`;

            const completion = progressState?.terminal ? getSettlementCompletionPresentation(progress) : null;
            const lastSyncAt = progressState?.terminal && progress?.updated_at
                ? progress.updated_at
                : (lastSuccess ? lastSuccess.created_at : null);

            if (progressActive) {
                setSyncState('running', progressStatus === 'queued' ? 'Antre' : 'Berjalan');
                updateSyncDot(true);
                if ($('settlementSyncLastSuccess')) $('settlementSyncLastSuccess').textContent = lastSyncAt ? fmtDateTime(lastSyncAt) : '—';
            } else if (progressState?.terminal) {
                setSyncState(completion.level, completion.state);
                updateSyncDot(false);
                if ($('settlementSyncLastSuccess')) $('settlementSyncLastSuccess').textContent = lastSyncAt ? fmtDateTime(lastSyncAt) : '—';
            } else if (!latest) {
                setSyncState('warn', 'Kosong');
                updateSyncDot(false);
                if ($('settlementSyncLastSuccess')) $('settlementSyncLastSuccess').textContent = lastSyncAt ? fmtDateTime(lastSyncAt) : '—';
            } else if (latest.status === 'success' || latest.status === 'partial_success') {
                setSyncState('success', latest.status === 'success' ? 'Siap' : 'Parsial');
                updateSyncDot(false);
                if ($('settlementSyncLastSuccess')) $('settlementSyncLastSuccess').textContent = lastSyncAt ? fmtDateTime(lastSyncAt) : '—';
            } else if (syncLogs.some(log => log.status === 'processing')) {
                setSyncState('running', 'Berjalan');
                updateSyncDot(true);
                if ($('settlementSyncLastSuccess')) $('settlementSyncLastSuccess').textContent = lastSyncAt ? fmtDateTime(lastSyncAt) : '—';
            } else {
                setSyncState('error', 'Gagal');
                updateSyncDot(false);
                if ($('settlementSyncLastSuccess')) $('settlementSyncLastSuccess').textContent = lastSyncAt ? fmtDateTime(lastSyncAt) : '—';
            }

            if (progressState && progressState.terminal && lastSettlementProgressKey !== progressKey) {
                loadSettlements();
                showSyncFeedback(completion.level, completion.title, completion.message, completion.meta || []);
            }

            lastSettlementProgressKey = progressKey;

            renderSyncConsole();
            renderSyncLogs();

            return syncLogs;
        } finally {
            settlementSyncRefreshInFlight = false;
        }
    }

    window.refreshSettlementSyncLogs = refreshSettlementSyncLogs;

    window.switchSettlementTab = function (tab, skipPersist = false) {
        activeTab = tab === 'sync' ? 'sync' : 'data';
        if (!skipPersist) {
            sessionStorage.setItem('settlementActiveTab', activeTab);
        }

        const isSync = activeTab === 'sync';
        $('settlementTabData').style.display = isSync ? 'none' : '';
        $('settlementTabSync').style.display = isSync ? '' : 'none';
        $('settlementTabData').setAttribute('aria-hidden', String(isSync));
        $('settlementTabSync').setAttribute('aria-hidden', String(!isSync));
        $('settlementTabDataBtn').setAttribute('aria-selected', String(!isSync));
        $('settlementTabSyncBtn').setAttribute('aria-selected', String(isSync));
        $('settlementTabDataBtn').tabIndex = isSync ? -1 : 0;
        $('settlementTabSyncBtn').tabIndex = isSync ? 0 : -1;
        $('settlementTabDataBtn').classList.toggle('active', !isSync);
        $('settlementTabSyncBtn').classList.toggle('active', isSync);

        if (isSync) {
            refreshSettlementSyncLogs();
            startSyncPolling();
        } else {
            stopSyncPolling();
        }
    };

    async function init() {
        try {
            stores = await api('/api/marketplace/stores');
        } catch (error) {
            stores = [];
            showSyncFeedback('error', 'Daftar toko gagal dimuat', 'UI belum bisa menentukan toko mana yang aman untuk diproses. Muat ulang halaman atau periksa koneksi server.', [error?.data?.code ? `Kode: ${error.data.code}` : null].filter(Boolean));
        }
        populateStoreSelects();

        if (window.GFID && window.GFID.initDateRange) {
            const updateDatePickerState = function(instance, selectedDates, type) {
                const visibleInput = instance.altInput || instance.input;
                const complete = selectedDates.length === 2;
                const label = type === 'order' ? 'rentang tanggal order' : 'rentang tanggal cair/estimasi';
                visibleInput.placeholder = complete
                    ? 'Rentang terpilih'
                    : (selectedDates.length === 1 ? 'Pilih tanggal akhir' : 'Pilih rentang tanggal');
                visibleInput.setAttribute('aria-label', complete ? `Filter ${label} terpilih` : `Pilih ${label}`);
                visibleInput.classList.toggle('is-range-complete', complete);
            };
            const onDateChange = function(selectedDates, dateStr, instance) {
                updateDatePickerState(instance, selectedDates, instance.input.id === 'filterOrderDate' ? 'order' : 'settlement');
                if (selectedDates.length === 2) {
                    instance.close();
                }
            };
            const onDateClose = function(selectedDates) {
                if (selectedDates.length === 2 || selectedDates.length === 0) {
                    currentPage = 1;
                    loadSettlements();
                }
            };
            const onDateOpen = function(selectedDates, dateStr, instance) {
                [fpOrderDate, fpSettlementDate].forEach(picker => {
                    if (picker && picker !== instance && picker.isOpen) {
                        picker.close();
                    }
                });
            };
            const datePickerOptions = {
                allowInput: false,
                clickOpens: true,
                closeOnSelect: false,
                showMonths: window.innerWidth >= 1024 ? 2 : 1,
                position: 'auto center',
                rangeSeparator: ' – ',
                onChange: onDateChange,
                onOpen: onDateOpen,
                onClose: onDateClose,
                onReady: function(selectedDates, dateStr, instance) {
                    instance.input.classList.add('gf-date-input');
                    instance.calendarContainer.classList.add('gf-date-calendar', 'settlement-range-calendar');
                    updateDatePickerState(instance, selectedDates, instance.input.id === 'filterOrderDate' ? 'order' : 'settlement');
                },
            };
            fpOrderDate = window.GFID.initDateRange('#filterOrderDate', datePickerOptions);
            fpSettlementDate = window.GFID.initDateRange('#filterSettlementDate', datePickerOptions);
        }

        document.querySelectorAll('.filter-select').forEach(el => {
            if (el.tagName === 'SELECT' || el.id === 'filterSearch') {
                el.addEventListener('change', () => {
                    if (el.id === 'filterPlatform') {
                        populateFilterStoreSelect();
                    }
                    if (el.id !== 'filterOrderDate' && el.id !== 'filterSettlementDate') {
                        currentPage = 1;
                        loadSettlements();
                    }
                });
            }
        });

        const filterStore = $('filterStore');
        const syncStore = $('syncStore');
        const syncPlatform = $('syncPlatform');
        if (syncStore) {
            syncStore.dataset.manual = '0';
            syncStore.addEventListener('change', () => {
                syncStore.dataset.manual = '1';
                const selectedStore = stores.find(store => String(store.id) === String(syncStore.value));
                if (syncPlatform && selectedStore) {
                    syncPlatform.value = storePlatformCode(selectedStore);
                }
                updateSyncPlatformActions();
                refreshSettlementSyncLogs();
            });
        }
        if (syncPlatform) {
            syncPlatform.addEventListener('change', () => {
                if (syncStore) syncStore.dataset.manual = '0';
                populateSyncStoreSelect();
                updateSyncPlatformActions();
                refreshSettlementSyncLogs();
            });
        }
        if (filterStore && syncStore) {
            filterStore.addEventListener('change', () => {
                if (syncStore.dataset.manual !== '1') {
                    syncStore.value = filterStore.value;
                    const selectedStore = stores.find(store => String(store.id) === String(syncStore.value));
                    if (syncPlatform && selectedStore) {
                        syncPlatform.value = storePlatformCode(selectedStore);
                    }
                    updateSyncPlatformActions();
                    refreshSettlementSyncLogs();
                }
            });
        }

        const restoredFilters = restoreSettlementFilterState();
        populateFilterStoreSelect(restoredFilters?.store_id || $('filterStore')?.value || '');
        const restoredStore = stores.find(store => String(store.id) === String(syncStore?.value || ''));
        if (syncPlatform && restoredStore) {
            syncPlatform.value = storePlatformCode(restoredStore);
        }
        populateSyncStoreSelect(syncStore?.value || '');
        updateSyncPlatformActions();
        updateSettlementModeLabels();

        const filterSearch = $('filterSearch');
        if (filterSearch) {
            filterSearch.addEventListener('input', () => saveSettlementFilterState());
        }

        switchSettlementTab(activeTab, true);
        loadSettlements();
        startAutomaticSyncPolling();
    }

    window.resetFilters = function() {
        document.querySelectorAll('.filter-select').forEach(el => el.value = '');
        if ($('syncPlatform')) $('syncPlatform').value = '';
        populateFilterStoreSelect();
        populateSyncStoreSelect();
        if ($('filterSettlementStatus')) $('filterSettlementStatus').value = 'cair';
        if (fpOrderDate) fpOrderDate.clear();
        if (fpSettlementDate) fpSettlementDate.clear();
        try {
            localStorage.removeItem(settlementFiltersStorageKey);
        } catch (_) {}
        const syncStore = $('syncStore');
        if (syncStore && syncStore.dataset.manual !== '1') {
            syncStore.value = '';
        }
        currentPage = 1;
        updateSettlementModeLabels();
        loadSettlements();
    };

    window.goToPage = function(page) {
        currentPage = page;
        loadSettlements();
    };

    window.purgeAllMarketplaceData = async function () {
        if (!IS_OWNER) {
            alert('Aksi ini hanya tersedia untuk owner.');
            return;
        }

        const storeId = getSyncStoreId() || $('filterStore').value;
        if (!storeId) {
            alert('Pilih toko dulu sebelum menghapus data marketplace.');
            return;
        }

        const storeName = $('syncStore')?.selectedOptions[0]?.textContent || $('filterStore')?.selectedOptions[0]?.textContent || 'toko ini';
        const typed = prompt('Untuk menghapus semua data marketplace toko ' + storeName + ', ketik: HAPUS SEMUA DATA MARKETPLACE');
        if (typed === null) return;
        if (typed.trim().toUpperCase() !== 'HAPUS SEMUA DATA MARKETPLACE') {
            alert('Konfirmasi tidak cocok. Penghapusan dibatalkan.');
            return;
        }

        if (!confirm('Yakin mau hapus semua data marketplace untuk ' + storeName + '? Aksi ini tidak bisa dibatalkan.')) {
            return;
        }

        const btn = document.querySelector('button[onclick="purgeAllMarketplaceData()"]');
        if (!btn || btn.disabled) return;

        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menghapus...';

        try {
            const res = await api('/api/marketplace/stores/' + storeId + '/purge-marketplace-data', {
                method: 'POST',
                body: JSON.stringify({ confirm: typed.trim() }),
            });
            currentPage = 1;
            await loadSettlements();
            await refreshSettlementSyncLogs();
            alert(res.message || 'Semua data marketplace berhasil dihapus.');
        } catch (e) {
            alert(e.data?.message || e.message || 'Gagal menghapus data marketplace.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
        }
    };

    window.purgeAllSettlements = window.purgeAllMarketplaceData;

    function applySettlementMeta(meta) {
        if (!meta) return;

        $('kpiCount').textContent = meta.kpi_count;
        $('kpiBuyerTotal').textContent = fmtRp(meta.kpi_gross);
        $('kpiFeeTotal').textContent = fmtRp(meta.kpi_fees);
        $('kpiAdCost').textContent = fmtRp(meta.kpi_ad_cost || 0);
        $('kpiFeePercent').textContent = Number.isFinite(Number(meta.kpi_fee_pct))
            ? `${Number(meta.kpi_fee_pct).toFixed(1)}% dari gross setelah voucher`
            : '—';
        $('kpiNetPayout').textContent = fmtRp(meta.kpi_net);
        updateSettlementModeLabels(meta);
    }

    async function loadSettlementSummary(listParams, requestVersion) {
        const params = new URLSearchParams(listParams);
        params.set('page', '1');
        params.set('include_meta', '1');
        params.set('summary_only', '1');
        params.delete('per_page');

        try {
            const res = await api('/api/marketplace/settlements?' + params.toString());
            if (requestVersion !== settlementRequestVersion) return;
            applySettlementMeta(res?.meta);
        } catch (error) {
            if (requestVersion !== settlementRequestVersion) return;
            $('kpiFeePercent').textContent = 'Ringkasan gagal dimuat';
            console.error('Gagal memuat ringkasan settlement', error);
        }
    }

    window.loadSettlements = async function () {
        const requestVersion = ++settlementRequestVersion;
        saveSettlementFilterState();
        updateSettlementModeLabels();
        $('settlementBody').innerHTML = '<div style="padding:2rem;text-align:center;color:var(--shp-muted);font-size:.85rem;"><span class="spinner-border spinner-border-sm"></span> Memuat data...</div>';

        const params = new URLSearchParams();
        if ($('filterStore').value) params.append('store_id', $('filterStore').value);
        if ($('filterPlatform').value) params.append('platform', $('filterPlatform').value);
        if ($('filterStatus').value) params.append('status', $('filterStatus').value);
        if ($('filterSettlementStatus').value) params.append('settlement_status', $('filterSettlementStatus').value);
        if ($('filterSearch').value) params.append('search', $('filterSearch').value);
        params.append('page', currentPage);
        params.append('per_page', 50);
        params.append('include_meta', 0);

        if (fpOrderDate && fpOrderDate.selectedDates && fpOrderDate.selectedDates.length === 2) {
            const orderFrom = fpOrderDate.formatDate(fpOrderDate.selectedDates[0], 'Y-m-d');
            const orderTo = fpOrderDate.formatDate(fpOrderDate.selectedDates[1], 'Y-m-d');
            params.append('order_date_from', orderFrom);
            params.append('order_date_to', orderTo);
        }

        if (fpSettlementDate && fpSettlementDate.selectedDates && fpSettlementDate.selectedDates.length === 2) {
            const settlementFrom = fpSettlementDate.formatDate(fpSettlementDate.selectedDates[0], 'Y-m-d');
            const settlementTo = fpSettlementDate.formatDate(fpSettlementDate.selectedDates[1], 'Y-m-d');
            params.append('settlement_date_from', settlementFrom);
            params.append('settlement_date_to', settlementTo);
        }

        try {
            const res = await api('/api/marketplace/settlements?' + params.toString());
            if (requestVersion !== settlementRequestVersion) return;
            if (!res || !res.paginator) {
                throw new Error("Invalid response format from server.");
            }

            settlements = res.paginator.data || [];
            paginationData = res.paginator;
            renderTable();

            if (params.get('page') === '1') {
                $('kpiFeePercent').textContent = 'Menghitung ringkasan…';
                void loadSettlementSummary(params, requestVersion);
            }
        } catch (e) {
            if (requestVersion !== settlementRequestVersion) return;
            $('settlementBody').innerHTML = '<div style="padding:2rem;text-align:center;color:#b91c1c;">Gagal memuat data: ' + e.message + '</div>';
        }
    };

    function renderTable() {
        const body = $('settlementBody');
        if ($('settlementLedgerMeta')) {
            $('settlementLedgerMeta').textContent = `${paginationData?.total || 0} order dalam filter aktif`;
        }
        if (!settlements.length) {
            body.innerHTML = '<div style="padding:3rem 1rem;text-align:center;color:var(--shp-muted);font-size:.9rem;">Tidak ada data settlement yang sesuai dengan filter.</div>';
            return;
        }

        const selectedFundMode = $('filterSettlementStatus')?.value || '';
        const pendingView = selectedFundMode === 'belum_cair';
        const dateHeading = pendingView ? 'Est. Tgl Cair' : (selectedFundMode === 'cair' ? 'Tgl Cair Final' : 'Cair / Estimasi');
        const feeHeading = pendingView ? 'Status Rincian Fee' : 'Biaya Marketplace';

        let html = `
        <div class="settlement-table-scroll">
        <table class="table table-list settlement-ledger-table w-100" style="margin-bottom:0">
            <thead>
                <tr>
                    <th>Order & Toko</th>
                    <th>Timeline</th>
                    <th class="text-end">Gross / Net</th>
                    <th>${feeHeading}</th>
                    <th>Potongan & Penyesuaian</th>
                </tr>
            </thead>
            <tbody>
            ${settlements.map(s => {
                const isPending = !s.settlement_time && !s.is_finance_final;
                const isTikTokFinance = ['tiktok', 'ttk', 'tkt', 'tt'].includes(String(s.store?.channel?.code || '').toLowerCase())
                    || String(s.finance_source || '').toLowerCase().includes('tiktok_finance');
                const settlementDateOnly = isTikTokFinance && s.settlement_time_precision === 'date';
                const settlementTimeLabel = s.settlement_time
                    ? (settlementDateOnly ? fmtSettlementDate(s.settlement_time) : fmtDateTime(s.settlement_time))
                    : 'Tanggal final belum tersedia';
                const tiktokShippingCost = Number(s.actual_shipping_fee_amount ?? s.actual_shipping_fee ?? 0);
                const tiktokBuyerShipping = Number(s.shipping_fee_customer ?? s.order?.shipping_fee_customer ?? 0);
                const tiktokPlatformShipping = Number(s.shipping_fee_discount_amount ?? s.shipping_fee_subsidy ?? 0);
                const tiktokShippingDifference = tiktokShippingCost - tiktokBuyerShipping - tiktokPlatformShipping;
                const tiktokSellerDiscount = Number(s.tiktok_seller_discount_amount || 0);
                const tiktokPlatformDiscount = Number(s.tiktok_platform_discount_amount || 0);
                const buyerShipping = isTikTokFinance ? tiktokBuyerShipping : Number(s.order?.shipping_fee_customer ?? 0);
                const estimateSource = String(s.income_estimation_source || '');
                const estimateSourceLabel = estimateSource === 'payout'
                    ? 'PAYOUT AKTUAL'
                    : (estimateSource === 'estimated_escrow'
                        ? 'EST. ESCROW'
                        : (estimateSource === 'manual_24' ? 'EST. MANUAL 24%' : 'ESTIMASI BELUM TERSEDIA'));
                const pendingReason = String(s.finance_pending_reason || '').trim().toUpperCase();
                const pendingReasonLabel = pendingReason === 'WAITING_FOR_PACKAGE_DELIVERY'
                    ? 'Menunggu paket diterima TikTok'
                    : (pendingReason === 'ORDER_DELIVERED_AND_AWAITING_SETTLEMENT'
                        ? 'Order sudah diterima, menunggu settlement TikTok'
                        : 'Menunggu statement Finance TikTok');
                const pendingDetailPlaceholder = isTikTokFinance ? `
                    <div class="settlement-pending-note">
                        <strong>${s.finance_pending_cancelled ? 'Status TikTok belum final' : 'Belum final'}</strong><br>${pendingReasonLabel}.
                    </div>` : `
                    <div class="settlement-pending-note">
                        <strong>Belum final</strong><br>Rincian tersedia setelah settlement.
                    </div>`;
                const finalSourceLabel = settlementDateOnly
                    ? 'TIKTOK • JAM TIDAK DISEDIAKAN'
                    : (isTikTokFinance ? 'TIKTOK FINANCE API' : 'CAIR • FINAL');
                return `<tr>
                    <td class="settlement-order-cell">
                        <a href="/marketplace/orders/${s.order?.id || ''}" class="code-link settlement-order-id">${esc(s.channel_order_id)}</a>
                        <div style="margin-top:4px">${s.order?.order_status ? `<span class="oc-badge oc-badge-muted" style="font-size:.63rem">${esc(s.order.order_status)}</span>` : ''}</div>
                        <div class="settlement-order-store">${esc(s.store?.name || '—')}</div>
                        ${isTikTokFinance ? '<div class="settlement-order-source">TikTok Finance</div>' : ''}
                    </td>
                    <td class="settlement-timeline">
                        <div class="settlement-timeline-item">
                            <span class="settlement-timeline-label">Order dibuat</span>
                            <strong class="settlement-timeline-value">${s.order?.ordered_at ? fmtDateTime(s.order.ordered_at) : '—'}</strong>
                        </div>
                        <div class="settlement-timeline-item">
                            <span class="settlement-timeline-label">${dateHeading}</span>
                            ${isPending
                                ? `<strong class="settlement-timeline-value muted">${s.estimated_payout_at ? 'Est. ' + fmtDateTime(s.estimated_payout_at) : 'Belum tersedia'}</strong>
                                   <span class="oc-badge oc-badge-amber" style="width:max-content;font-size:.6rem">${isTikTokFinance ? 'Menunggu statement' : estimateSourceLabel}</span>`
                                : `<strong class="settlement-timeline-value">${settlementTimeLabel}</strong>
                                   <span class="oc-badge oc-badge-green" style="width:max-content;font-size:.6rem">${finalSourceLabel}</span>`}
                        </div>
                    </td>

                    <td class="text-end">
                        <div class="settlement-money-stack">
                            <div class="settlement-money-item">
                                <span class="settlement-money-label">Gross sales</span>
                                <strong class="settlement-money-value">${fmtRp(s.gross_amount || 0)}</strong>
                            </div>
                            ${isTikTokFinance && s.net_sales_amount !== null && s.net_sales_amount !== undefined ? `
                            <div class="settlement-money-item">
                                <span class="settlement-money-label">Net sales</span>
                                <strong class="settlement-money-value">${fmtRp(s.net_sales_amount)}</strong>
                                <span class="settlement-money-source">TikTok Finance</span>
                            </div>` : ''}
                            <div class="settlement-money-item">
                                <span class="settlement-money-label">${isPending ? 'Estimasi net' : 'Net settlement'}</span>
                                <strong class="settlement-money-value ${isPending ? 'pending' : 'net'}">${fmtRp(s.final_income)}</strong>
                                <span class="settlement-money-source">${isPending ? (isTikTokFinance ? 'Menunggu statement' : estimateSourceLabel) : finalSourceLabel}</span>
                            </div>
                        </div>
                    </td>

                    <td>
                        ${isPending ? pendingDetailPlaceholder : `
                        <ul class="fee-list">
                            ${(() => {
                                let html = '';
                                const renderFeeLine = (label, rawValue, force = false, percent = null) => {
                                    const numeric = Number(rawValue || 0);
                                    if (!force && !numeric) return '';
                                    const sign = numeric < 0 ? '−' : '';
                                    const pct = percent !== null && percent !== undefined
                                        ? ` <span style="font-size:.68rem; color:var(--shp-muted);">(${Number(percent).toFixed(1)}%)</span>`
                                        : '';
                                    return `<li><span>${label}:</span> <span class="fee-val">${sign}${fmtRp(Math.abs(numeric))}${pct}</span></li>`;
                                };
                                const sectionLabel = (label) => `<li style="margin-top:.45rem; padding-top:.35rem; border-top:1px dashed rgba(148,163,184,.22); font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#64748b;">${label}</li>`;
                                const sumFeeRows = (rows) => (Array.isArray(rows) ? rows : [])
                                    .reduce((total, item) => total + Math.abs(Number(item?.amount || 0)), 0);
                                const renderFeeSectionLabel = (label, amount, base) => {
                                    const numericAmount = Math.abs(Number(amount || 0));
                                    const numericBase = Math.abs(Number(base || 0));
                                    const percent = numericBase > 0 ? (numericAmount / numericBase) * 100 : null;
                                    const suffix = percent === null
                                        ? ''
                                        : ` <span style="font-size:.68rem; color:var(--shp-muted);">(${percent.toFixed(1)}%)</span>`;
                                    return sectionLabel(`${label}${suffix}`);
                                };
                                const renderShopeeFeeRows = (rows) => (Array.isArray(rows) ? rows : [])
                                    .map((item) => renderFeeLine(item?.label || 'Program Layanan', item?.amount))
                                    .join('');
                                const renderShopeeFeeDifference = (difference) => {
                                    const amount = Number(difference);
                                    if (!Number.isFinite(amount) || Math.abs(amount) < 0.01) return '';
                                    const label = amount > 0 ? 'Detail layanan belum terpetakan' : 'Detail layanan melebihi total';
                                    return renderFeeLine(label, Math.abs(amount));
                                };
                                const formatFeeKey = (key) => String(key || '')
                                    .split('.')
                                    .map((part) => part.replace(/[_-]+/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()))
                                    .join(' › ');
                                const tiktokFeeLabels = {
                                    platform_commission_amount: 'Komisi Platform',
                                    platform_commission_fee: 'Komisi Platform',
                                    referral_fee_amount: 'Referral Fee',
                                    refund_administration_fee_amount: 'Biaya Administrasi Refund',
                                    transaction_fee_amount: 'Biaya Transaksi',
                                    transaction_fee: 'Biaya Transaksi',
                                    credit_card_handling_fee_amount: 'Biaya Handling Kartu',
                                    affiliate_commission_amount: 'Komisi Affiliate',
                                    affiliate_commission_fee: 'Komisi Affiliate',
                                    affiliate_commission_amount_before_pit: 'Komisi Affiliate sebelum Pajak',
                                    affiliate_partner_commission_amount: 'Komisi Partner Affiliate',
                                    shipping_fee_guarantee_service_fee: 'Biaya Jaminan Ongkir',
                                    bonus_cashback_service_fee_amount: 'Biaya Layanan Bonus Cashback',
                                    campaign_period_fee_cfp_amount: 'Biaya Campaign Period',
                                    campaign_period_fee_cfp_tax_amount: 'Pajak Campaign Period',
                                    smart_promotion_fee_amount: 'Biaya Smart Promotion',
                                    flash_sales_service_fee_amount: 'Biaya Layanan Flash Sale',
                                    voucher_xtra_service_fee_amount: 'Biaya Layanan Voucher Xtra',
                                    tsp_commission_amount: 'Komisi TikTok Shop Partner',
                                    tap_shop_ads_commission: 'Komisi Shop Ads',
                                    non_standard_dimension_fee: 'Biaya Dimensi Non-Standar',
                                    new_customer_growth_package_fee: 'Biaya Growth Package — New Customer',
                                    store_gmv_growth_package_fee: 'Biaya Growth Package — Store GMV',
                                    target_product_gmv_growth_package_fee: 'Biaya Growth Package — Target Product GMV',
                                    auto_post_shoppable_video_commission_fee: 'Komisi Auto-Post Shoppable Video',
                                    new_customer_growth_package_fee_tax: 'Pajak Growth Package — New Customer',
                                    store_gmv_growth_package_fee_sales_tax: 'Pajak Growth Package — Store GMV',
                                    target_product_gmv_growth_package_tax: 'Pajak Growth Package — Target Product GMV',
                                    auto_post_shoppable_video_sales_tax: 'Pajak Auto-Post Shoppable Video',
                                };
                                const tiktokFeeLabel = (key, type) => {
                                    const mapped = tiktokFeeLabels[String(key || '')];
                                    if (mapped) return mapped;
                                    const label = formatFeeKey(key);
                                    return `${type === 'tax' ? 'Pajak' : 'Fee'} — ${label}`;
                                };
                                const tiktokFeeGroup = (key, type) => {
                                    const normalized = String(key || '').toLowerCase();
                                    if (type === 'tax') return 'program';
                                    if (/(affiliate_commission|affiliate_partner|tsp_commission)/.test(normalized)) {
                                        return 'affiliate';
                                    }
                                    if (/(platform_commission|referral_fee|refund_administration|transaction_fee|credit_card_handling|infrastructure|dynamic_commission)/.test(normalized)) {
                                        return 'administration';
                                    }
                                    if (/(service_fee|bonus_cashback|flash_sales|voucher_xtra|shipping_fee_guarantee)/.test(normalized)) {
                                        return 'service';
                                    }
                                    return 'program';
                                };
                                const renderTikTokGroup = (label, rows, base) => (rows.length > 0
                                    ? renderFeeSectionLabel(label, sumFeeRows(rows), base) + rows.map((item) => renderFeeLine(item.label, item.amount)).join('')
                                    : '');
                                const extractTikTokBreakdown = (raw) => {
                                    const normalized = raw.tiktok_fee_breakdown || {};
                                    const result = {
                                        fees: normalized.fees && typeof normalized.fees === 'object' ? { ...normalized.fees } : {},
                                        taxes: normalized.taxes && typeof normalized.taxes === 'object' ? { ...normalized.taxes } : {},
                                    };
                                    const addValues = (rows, target, prefix = '') => {
                                        if (!rows || typeof rows !== 'object') return;
                                        Object.entries(rows).forEach(([key, value]) => {
                                            const label = prefix ? `${prefix}.${key}` : key;
                                            if (value && typeof value === 'object' && !Array.isArray(value)) {
                                                addValues(value, target, label);
                                                return;
                                            }
                                            if (value !== null && value !== '' && Number.isFinite(Number(value))) {
                                                target[label] = Number(target[label] || 0) + Math.abs(Number(value));
                                            }
                                        });
                                    };
                                    if (Object.keys(result.fees).length === 0 && Object.keys(result.taxes).length === 0) {
                                        (Array.isArray(raw.sku_transactions) ? raw.sku_transactions : []).forEach((transaction) => {
                                            const breakdown = transaction && typeof transaction.fee_tax_breakdown === 'object'
                                                ? transaction.fee_tax_breakdown
                                                : {};
                                            addValues(breakdown.fee, result.fees);
                                            addValues(breakdown.tax, result.taxes);
                                        });
                                    }
                                    return result;
                                };
                                const feeTotal = Number(s.fee_total || 0);
                                const feePercent = s.fee_percent !== undefined && s.fee_percent !== null ? Number(s.fee_percent) : null;
                                const feeBase = Number(s.gross_after_voucher || 0);
                                if (s.raw_json) {
                                    const r = s.raw_json;
                                    const tiktokBreakdown = extractTikTokBreakdown(r);
                                    const tiktokFees = tiktokBreakdown.fees;
                                    const affiliateAmount = Number(tiktokFees.affiliate_commission_amount);
                                    const affiliateBeforePit = Number(tiktokFees.affiliate_commission_amount_before_pit);
                                    if (Number.isFinite(affiliateAmount) && Number.isFinite(affiliateBeforePit)
                                        && Math.abs(affiliateAmount - affiliateBeforePit) < 0.01) {
                                        delete tiktokFees.affiliate_commission_amount_before_pit;
                                    }
                                    const tiktokTaxes = tiktokBreakdown.taxes;
                                    const isTikTokFinance = String(r.source || '').toLowerCase().includes('tiktok')
                                        || Object.keys(tiktokFees).length > 0
                                        || Object.keys(tiktokTaxes).length > 0
                                        || Array.isArray(r.sku_transactions);
                                    const hasTikTokBreakdown = Object.keys(tiktokFees).length > 0 || Object.keys(tiktokTaxes).length > 0;
                                    if (isTikTokFinance) {
                                        html += renderFeeLine('Total Fee Marketplace', feeTotal, true, feePercent);
                                        const tiktokFeeGroups = {
                                            administration: [],
                                            service: [],
                                            affiliate: [],
                                            program: [],
                                        };
                                        if (!hasTikTokBreakdown) {
                                            tiktokFeeGroups.program.push({
                                                label: 'Fee & Pajak TikTok (agregat)',
                                                amount: Math.abs(Number(r.fee_and_tax_amount || 0)),
                                            });
                                        }
                                        Object.entries(tiktokFees).forEach(([key, value]) => {
                                            tiktokFeeGroups[tiktokFeeGroup(key, 'fee')].push({
                                                label: tiktokFeeLabel(key, 'fee'),
                                                amount: value,
                                            });
                                        });
                                        Object.entries(tiktokTaxes).forEach(([key, value]) => {
                                            tiktokFeeGroups.program.push({
                                                label: tiktokFeeLabel(key, 'tax'),
                                                amount: value,
                                            });
                                        });
                                        const shippingCost = Number(s.shipping_cost_amount || 0);
                                        if (Number.isFinite(shippingCost) && Math.abs(shippingCost) > 0.001) {
                                            tiktokFeeGroups.service.push({ label: 'Biaya logistik', amount: shippingCost });
                                        }
                                        html += renderTikTokGroup('Biaya Platform', tiktokFeeGroups.administration, feeTotal);
                                        html += renderTikTokGroup('Biaya Layanan', tiktokFeeGroups.service, feeTotal);
                                        html += renderTikTokGroup('Komisi Affiliate', tiktokFeeGroups.affiliate, feeTotal);
                                        html += renderTikTokGroup('Biaya Program & Lainnya', tiktokFeeGroups.program, feeTotal);
                                    } else {
                                        html += renderFeeLine('Total Biaya Marketplace', feeTotal, true, feePercent);
                                        const shopeeCommissionFee = r.commission_fee ?? s.commission_fee;
                                        const shopeeProcessingFee = r.seller_order_processing_fee ?? s.seller_order_processing_fee;
                                        const shopeeServiceFee = r.service_fee ?? s.service_fee;
                                        const shopeeTransactionFee = r.seller_transaction_fee ?? r.transaction_fee ?? s.transaction_fee;
                                        const shopeeAmsFee = r.order_ams_commission_fee ?? r.ams_commission_fee ?? s.order_ams_commission_fee;
                                        const shopeeOtherFeeTotal = sumFeeRows(s.shopee_other_fee_breakdown)
                                            + Math.abs(Number(shopeeTransactionFee || 0))
                                            + Math.abs(Number(shopeeAmsFee || 0));

                                        html += renderFeeSectionLabel('Biaya Platform', Math.abs(Number(shopeeCommissionFee || 0)) + Math.abs(Number(shopeeProcessingFee || 0)), feeTotal);
                                        html += renderFeeLine('Biaya Administrasi', shopeeCommissionFee);
                                        html += renderFeeLine('Biaya Proses Pesanan', shopeeProcessingFee);

                                        html += renderFeeSectionLabel('Biaya Layanan', shopeeServiceFee, feeTotal);
                                        html += renderFeeLine('Total Biaya Layanan', shopeeServiceFee);
                                        html += renderShopeeFeeRows(s.shopee_service_fee_breakdown);
                                        html += renderShopeeFeeDifference(s.shopee_service_fee_breakdown_difference);

                                        html += renderFeeSectionLabel('Biaya Program & Lainnya', shopeeOtherFeeTotal, feeTotal);
                                        html += renderShopeeFeeRows(s.shopee_other_fee_breakdown);
                                        html += renderFeeLine('Biaya Transaksi', shopeeTransactionFee);
                                        html += renderFeeLine('Biaya Komisi AMS', shopeeAmsFee);
                                    }
                                } else {
                                    html += renderFeeLine('Total Biaya Marketplace', feeTotal, true, feePercent);
                                    const shopeeOtherFeeTotal = sumFeeRows(s.shopee_other_fee_breakdown)
                                        + Math.abs(Number(s.transaction_fee || 0))
                                        + Math.abs(Number(s.order_ams_commission_fee ?? s.ams_commission_fee ?? s.activity_fee ?? 0));

                                    html += renderFeeSectionLabel('Biaya Platform', Math.abs(Number(s.commission_fee || 0)) + Math.abs(Number(s.seller_order_processing_fee || 0)), feeTotal);
                                    html += renderFeeLine('Biaya Administrasi', s.commission_fee);
                                    html += renderFeeLine('Biaya Proses Pesanan', s.seller_order_processing_fee);

                                    html += renderFeeSectionLabel('Biaya Layanan', s.service_fee, feeTotal);
                                    html += renderFeeLine('Total Biaya Layanan', s.service_fee);
                                    html += renderShopeeFeeRows(s.shopee_service_fee_breakdown);
                                    html += renderShopeeFeeDifference(s.shopee_service_fee_breakdown_difference);

                                    html += renderFeeSectionLabel('Biaya Program & Lainnya', shopeeOtherFeeTotal, feeTotal);
                                    html += renderShopeeFeeRows(s.shopee_other_fee_breakdown);
                                    html += renderFeeLine('Biaya Transaksi', s.transaction_fee);
                                    html += renderFeeLine('Biaya Komisi AMS', s.order_ams_commission_fee ?? s.ams_commission_fee ?? s.activity_fee);
                                }
                                if (isTikTokFinance) {
                                    html += renderFeeSectionLabel('Biaya Iklan', s.ad_cost, feeTotal);
                                    html += renderFeeLine('Biaya Iklan', s.ad_cost);
                                }
                                return html || '<li><span style="color:var(--shp-muted)">Tidak ada potongan</span></li>';
                            })()}
                        </ul>
                        `}
                    </td>

                    <td>
                        ${isPending ? pendingDetailPlaceholder : `
                        <div class="settlement-breakdown">
                            <div class="settlement-breakdown-block">
                                <div class="settlement-breakdown-title">Promosi</div>
                                <ul class="fee-list">
                                    ${isTikTokFinance ? `
                                    <li><span>Diskon produk dari penjual</span> <span class="fee-val">${tiktokSellerDiscount ? '−'+fmtRp(tiktokSellerDiscount) : '-'}</span></li>
                                    <li><span>Diskon produk dari platform</span> <span class="fee-val">${tiktokPlatformDiscount ? '−'+fmtRp(tiktokPlatformDiscount) : '-'}</span></li>` : ''}
                                    <li><span>Voucher</span> <span class="fee-val">${s.seller_voucher ? '−'+fmtRp(s.seller_voucher) : '-'}</span></li>
                                    <li><span>Koin CB</span> <span class="fee-val">${s.seller_coin_cash_back ? '−'+fmtRp(s.seller_coin_cash_back) : '-'}</span></li>
                                </ul>
                            </div>
                            <div class="settlement-breakdown-block">
                                <div class="settlement-breakdown-title">Logistik</div>
                                <ul class="fee-list">
                                    <li><span>Biaya ongkir</span> <span class="fee-val">${isTikTokFinance ? (tiktokShippingCost ? '−'+fmtRp(tiktokShippingCost) : '-') : (s.actual_shipping_fee ? '−'+fmtRp(s.actual_shipping_fee) : '-')}</span></li>
                                    <li><span>Pembeli</span> <span class="buyer-val">${buyerShipping ? '+'+fmtRp(buyerShipping) : '-'}</span></li>
                                    <li><span>Platform</span> <span class="subsidy-val">${isTikTokFinance ? (tiktokPlatformShipping ? '+'+fmtRp(tiktokPlatformShipping) : '-') : (s.shipping_fee_subsidy ? '+'+fmtRp(s.shipping_fee_subsidy) : '-')}</span></li>
                                    ${isTikTokFinance ? `<li><span>Selisih Ongkir</span> <span class="fee-val">${tiktokShippingDifference ? (tiktokShippingDifference > 0 ? '−' : '+')+fmtRp(Math.abs(tiktokShippingDifference)) : '-'}</span></li>` : ''}
                                </ul>
                            </div>
                            <div class="settlement-breakdown-block">
                                <div class="settlement-breakdown-title">Penyesuaian</div>
                                <ul class="fee-list">
                                    <li><span>Ongkir balik</span> <span class="fee-val">${s.reverse_shipping_fee ? '−'+fmtRp(s.reverse_shipping_fee) : '-'}</span></li>
                                    <li><span>DRC refund</span> <span class="drc-val">${s.drc_adjustable_refund ? (s.drc_adjustable_refund > 0 ? '+' : '') + fmtRp(s.drc_adjustable_refund) : '-'}</span></li>
                                </ul>
                            </div>
                        </div>
                        `}
                    </td>
                </tr>`;
            }).join('')}
            </tbody>
        </table>
        </div>`;

        let linksHtml = '';
        if (paginationData && paginationData.last_page > 1) {
            linksHtml += '<div class="btn-group">';
            if (paginationData.current_page > 1) {
                linksHtml += `<button class="btn btn-sm btn-light border" onclick="goToPage(${paginationData.current_page - 1})">Prev</button>`;
            } else {
                linksHtml += `<button class="btn btn-sm btn-light border" disabled>Prev</button>`;
            }

            let start = Math.max(1, paginationData.current_page - 2);
            let end = Math.min(paginationData.last_page, paginationData.current_page + 2);

            for (let p = start; p <= end; p++) {
                if (p === paginationData.current_page) {
                    linksHtml += `<button class="btn btn-sm btn-primary active">${p}</button>`;
                } else {
                    linksHtml += `<button class="btn btn-sm btn-light border" onclick="goToPage(${p})">${p}</button>`;
                }
            }

            if (paginationData.current_page < paginationData.last_page) {
                linksHtml += `<button class="btn btn-sm btn-light border" onclick="goToPage(${paginationData.current_page + 1})">Next</button>`;
            } else {
                linksHtml += `<button class="btn btn-sm btn-light border" disabled>Next</button>`;
            }
            linksHtml += '</div>';
        }

        html += `
        <div class="settlement-pagination">
            <div>Menampilkan baris ${paginationData.from || 0} - ${paginationData.to || 0} dari total ${paginationData.total || 0}</div>
            <div>${linksHtml}</div>
        </div>`;

        body.innerHTML = html;
    }

    async function queueSettlementSyncRequest({ btnId, payload, busyText, idleText, confirmText = null, confirmTitle = 'Konfirmasi', ackTitle = 'Dikirim ke antrian' }) {
        const storeId = getSyncStoreId();
        const selectedStore = stores.find(store => String(store.id) === String(storeId));
        if (!storeId || !selectedStore) {
            alert('Pilih toko dulu di tab Sync sebelum memulai settlement sync.');
            return;
        }
        if (!isSettlementSyncReadyStore(selectedStore)) {
            alert('Toko belum siap sync. Periksa status credential dan scope Finance pada Kesiapan toko.');
            return;
        }

        const storeName = $('syncStore')?.selectedOptions[0]?.textContent || 'toko ini';
        if (confirmText && !confirm(confirmText.split('{store}').join(storeName))) {
            return;
        }

        const btn = $(btnId);
        if (!btn || btn.disabled) return;

        const oldHtml = btn.innerHTML;
        settlementSyncViewVersion++;
        settlementSyncRunId = null;
        lastSettlementProgressKey = 'new:queued';
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + busyText;

        const probeQueueState = async () => {
            for (let i = 0; i < 3; i++) {
                try {
                    const progress = await api('/api/marketplace/stores/' + storeId + '/sync-settlements-progress');
                    if (progress && ['queued', 'running'].includes(String(progress.status || '').toLowerCase())) {
                        return progress;
                    }
                } catch (_) {}
                await new Promise(resolve => setTimeout(resolve, 350 * (i + 1)));
            }
            return null;
        };

        hideSyncFeedback();
        setSyncState('running', 'Antre');
        updateSyncDot(true);
        renderSettlementProgress({
            status: 'queued',
            percent: 2,
            label: busyText,
            store_id: storeId,
            store_name: storeName,
            updated_at: new Date().toISOString(),
        });

        try {
            const d = await api('/api/marketplace/stores/' + storeId + '/sync-settlements-background', {
                method: 'POST',
                body: JSON.stringify(payload || {}),
            });

            settlementSyncRunId = d.run_id ? String(d.run_id) : null;
            lastSettlementProgressKey = settlementSyncRunId
                ? `${settlementSyncRunId}:queued`
                : 'new:queued';

            const meta = [
                d.mode ? `Mode: ${d.mode}` : null,
                d.progress_key ? `Progress key: ${d.progress_key}` : null,
            ].filter(Boolean);

            showSyncFeedback('info', ackTitle, d.message || 'Sinkronisasi dikirim ke latar belakang.', meta);
            await refreshSettlementSyncLogs();
        } catch (e) {
            settlementSyncRunId = e?.data?.progress?.run_id ? String(e.data.progress.run_id) : null;
            if (settlementSyncRunId) {
                lastSettlementProgressKey = `${settlementSyncRunId}:${String(e.data.progress.status || 'queued').toLowerCase()}`;
            }
            const canRecover = String(e?.message || '').toLowerCase().includes('response tidak valid')
                || [502, 503, 504, 522].includes(Number(e?.status || 0));
            if (canRecover) {
                const progress = await probeQueueState();
                if (progress) {
                    const meta = [
                        progress.mode ? `Mode: ${progress.mode}` : null,
                        progress.percent !== undefined && progress.percent !== null ? `Progress: ${progress.percent}%` : null,
                        progress.label ? progress.label : null,
                    ].filter(Boolean);
                    showSyncFeedback('warn', ackTitle, 'Request awal sempat gagal dibaca, tapi job sudah masuk antrian dan terus berjalan.', meta);
                    setSyncState(progress.status === 'running' ? 'running' : 'success', progress.status === 'running' ? 'Berjalan' : 'Antre');
                    updateSyncDot(true);
                    renderSettlementProgress(progress);
                    await refreshSettlementSyncLogs();
                    return;
                }
            }

            const presentation = presentSyncError(e, 'Gagal mengirim');
            showSyncFeedback(presentation.level, presentation.title, presentation.message, [presentation.code ? `Kode: ${presentation.code}` : null].filter(Boolean), presentation.action);
            setSyncState('error', 'Gagal');
            updateSyncDot(false);
            renderSettlementProgress({ status: 'error', percent: 100, label: presentation.message });
            refreshSettlementSyncLogs();
        } finally {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
        }
    }

    window.runSettlementSync = async function () {
        const storeId = getSyncStoreId();
        if (!storeId) {
            alert('Pilih toko Shopee atau TikTok dulu di tab Sync sebelum memulai sinkronisasi finance.');
            return;
        }

        // Finance TikTok memanggil endpoint statement per order. Untuk toko
        // dengan ratusan order, proses ini tidak aman dijalankan di request
        // browser karena dapat melewati timeout HTTP. Gunakan jalur queue yang
        // sama dengan backfill agar progress tetap bisa dipantau dan hasil
        // batch pertama tidak hilang ketika batch berikutnya masih berjalan.
        const selectedStore = stores.find(store => String(store.id) === String(storeId));
        if (selectedStore && isTikTokStore(selectedStore)) {
            await queueSettlementSyncRequest({
                btnId: 'runSettlementBtn',
                payload: {},
                busyText: 'Mengantre Finance TikTok…',
                idleText: 'Tarik Settlement',
                ackTitle: 'Finance TikTok masuk antrian',
            });
            return;
        }

        const btn = $('runSettlementBtn');
        if (!btn || btn.disabled) return;
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menarik…';
        hideSyncFeedback();
        setSyncState('running', 'Berjalan');
        updateSyncDot(true);

        try {
            const d = await api('/api/marketplace/stores/' + storeId + '/sync-settlements', {
                method: 'POST',
                body: JSON.stringify({}),
            });
            const hasErrors = Number(d.errors || 0) > 0;
            const hasWarnings = hasErrors || Number(d.skipped || 0) > 0;
            showSyncFeedback(hasWarnings ? 'warn' : 'success', hasWarnings ? 'Sync selesai dengan catatan' : 'Sync selesai', d.message || 'Settlement berhasil diproses.', [
                d.found !== undefined ? `Found: ${d.found}` : null,
                d.processed !== undefined ? `Processed: ${d.processed}` : null,
                d.synced !== undefined ? `Synced: ${d.synced}` : null,
                d.skipped !== undefined ? `Skipped: ${d.skipped}` : null,
                d.errors !== undefined ? `Errors: ${d.errors}` : null,
            ].filter(Boolean));
            setSyncState(hasWarnings ? 'warn' : 'success', hasWarnings ? 'Parsial' : 'Siap');
            updateSyncDot(false);
            currentPage = 1;
            await loadSettlements();
            await refreshSettlementSyncLogs();
        } catch (e) {
            const presentation = presentSyncError(e, 'Gagal menarik settlement');
            showSyncFeedback(presentation.level, presentation.title, presentation.message, [presentation.code ? `Kode: ${presentation.code}` : null].filter(Boolean), presentation.action);
            setSyncState(presentation.level === 'warn' ? 'warn' : 'error', presentation.level === 'warn' ? 'Perlu tindakan' : 'Gagal');
            updateSyncDot(false);
            refreshSettlementSyncLogs();
        } finally {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
        }
    };

    window.runIncomeEstimateSync = async function () {
        const storeId = getSyncStoreId();
        if (!storeId) {
            alert('Pilih satu toko Shopee di tab Sync sebelum memperbarui estimasi income.');
            return;
        }

        const selectedStore = stores.find(store => String(store.id) === String(storeId));
        if (selectedStore && isTikTokStore(selectedStore)) {
            alert('Estimasi income khusus Shopee. Untuk TikTok, gunakan tombol Tarik Settlement melalui Finance API.');
            return;
        }

        const btn = $('runIncomeEstimateBtn');
        if (!btn || btn.disabled) return;
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memperbarui…';
        hideSyncFeedback();
        setSyncState('running', 'Estimasi');
        updateSyncDot(true);

        try {
            const result = await api('/api/marketplace/stores/' + storeId + '/sync-income-details', {
                method: 'POST',
                body: JSON.stringify({ page_size: 100, days: 30 }),
            });
            showSyncFeedback('success', 'Estimasi dana cair diperbarui', result.message || 'Income detail Shopee berhasil diperbarui.', [
                result.found !== undefined ? `Ditemukan: ${result.found}` : null,
                result.updated !== undefined ? `Diperbarui: ${result.updated}` : null,
                result.created !== undefined ? `Baru: ${result.created}` : null,
                result.unmatched !== undefined ? `Order tidak cocok: ${result.unmatched}` : null,
                result.pages !== undefined ? `Halaman: ${result.pages}` : null,
                result.date_from && result.date_to ? `Periode: ${result.date_from} s/d ${result.date_to}` : null,
            ].filter(Boolean));
            setSyncState('success', 'Siap');
            updateSyncDot(false);
            currentPage = 1;
            await loadSettlements();
        } catch (error) {
            const presentation = presentSyncError(error, 'Gagal memperbarui estimasi');
            showSyncFeedback(presentation.level, presentation.title, presentation.message, [presentation.code ? `Kode: ${presentation.code}` : null].filter(Boolean), presentation.action);
            setSyncState(presentation.level === 'warn' ? 'warn' : 'error', presentation.level === 'warn' ? 'Tertunda' : 'Gagal');
            updateSyncDot(false);
        } finally {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
        }
    };

    window.runSyncReleasedSettlements = async function () {
        const storeId = getSyncStoreId();
        if (!storeId) {
            alert('Pilih toko dulu sebelum menarik tanggal cair.');
            return;
        }

        const selectedStore = stores.find(store => String(store.id) === String(storeId));
        if (selectedStore && !isShopeeStore(selectedStore)) {
            alert('Penarikan tanggal cair via Escrow List hanya berlaku untuk Shopee. TikTok memakai settlement status otomatis.');
            return;
        }

        const btn = $('runSyncReleasedBtn');
        if (!btn || btn.disabled) return;
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menarik…';
        hideSyncFeedback();
        setSyncState('running', 'Tgl Cair');
        updateSyncDot(true);

        try {
            const result = await api('/api/marketplace/stores/' + storeId + '/sync-released-settlements', {
                method: 'POST',
                body: JSON.stringify({ days: 45 }),
            });
            showSyncFeedback('success', 'Tanggal pencairan berhasil diperbarui', result.message || 'Tanggal dana cair berhasil disinkronkan.', [
                result.found !== undefined ? `Ditemukan: ${result.found}` : null,
                result.updated !== undefined ? `Diperbarui cair: ${result.updated}` : null,
                result.unmatched !== undefined ? `Tidak cocok: ${result.unmatched}` : null,
            ].filter(Boolean));
            setSyncState('success', 'Siap');
            updateSyncDot(false);
            currentPage = 1;
            await loadSettlements();
        } catch (error) {
            const presentation = presentSyncError(error, 'Gagal menarik tanggal cair');
            showSyncFeedback(presentation.level, presentation.title, presentation.message, [presentation.code ? `Kode: ${presentation.code}` : null].filter(Boolean), presentation.action);
            setSyncState(presentation.level === 'warn' ? 'warn' : 'error', presentation.level === 'warn' ? 'Tertunda' : 'Gagal');
            updateSyncDot(false);
        } finally {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
        }
    };

    window.runSettlementBackfill = async function (months, btnId = 'runSettlementBackfillBtn') {
        if (!getSyncStoreId()) {
            alert('Pilih toko siap sync dulu sebelum menjalankan backfill.');
            return;
        }
        const selectedMonths = Number(months || $('syncBackfillMonths')?.value || 3);
        if (![1,2,3].includes(selectedMonths)) {
            alert('Backfill hanya tersedia untuk 1, 2, atau 3 bulan.');
            return;
        }

        const selector = $('syncBackfillMonths');
        if (selector) {
            selector.value = String(selectedMonths);
        }

        await queueSettlementSyncRequest({
            btnId,
            payload: { backfill_months: selectedMonths },
            busyText: `Mengantre backfill ${selectedMonths} bulan…`,
            idleText: 'Jalankan Backfill',
            confirmText: 'Backfill settlement untuk {store} akan dijalankan. Lanjutkan?',
            ackTitle: `Backfill ${selectedMonths} bulan dikirim ke antrian`,
        });
    };

    window.runSettlementSyncBackground = async function () {
        const selectedPlatform = String($('syncPlatform')?.value || '').trim().toLowerCase();
        const targetStores = stores.filter(s => isSettlementSyncReadyStore(s)
            && (!selectedPlatform || storePlatformCode(s) === selectedPlatform));
        const skippedStores = stores.filter(s => {
            return s.status === 'active'
                && s.is_active === true
                && isFinanceStore(s)
                && (!selectedPlatform || storePlatformCode(s) === selectedPlatform)
                && !targetStores.includes(s);
        });

        if (!targetStores.length) {
            alert('Tidak ada toko Shopee/TikTok aktif dengan credential yang siap digunakan.');
            return;
        }

        const btn = $('runSettlementBgBtn');
        if (!btn || btn.disabled) return;
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengantre semua toko…';
        hideSyncFeedback();
        setSyncState('running', 'Antre');
        updateSyncDot(true);

        try {
            const results = await Promise.allSettled(targetStores.map(store => api('/api/marketplace/stores/' + store.id + '/sync-settlements-background', {
                method: 'POST',
                body: JSON.stringify({ all: 1 }),
            })));
            const queued = results.filter(result => result.status === 'fulfilled').length;
            const failed = results.length - queued;
            const skipped = skippedStores.length;
            const failedNames = results
                .map((result, index) => result.status === 'rejected' ? targetStores[index].name : null)
                .filter(Boolean);
            const skippedNames = skippedStores.map(store => store.name).filter(Boolean);
            const meta = [
                `Toko aktif: ${targetStores.length + skipped}`,
                `Masuk antrean: ${queued}`,
                failed ? `Gagal dikirim: ${failed} (${failedNames.slice(0, 3).join(', ')})` : null,
                skipped ? `Dilewati credential/status: ${skipped} (${skippedNames.slice(0, 3).join(', ')})` : null,
            ].filter(Boolean);

            showSyncFeedback(failed || skipped ? 'warn' : 'info',
                failed ? 'Sebagian toko gagal diantrekan' : 'Sync semua toko dikirim',
                failed || skipped
                    ? 'Toko valid tetap diproses; toko invalid/nonaktif dilewati.'
                    : 'Semua toko Shopee/TikTok aktif berhasil dikirim ke background queue.',
                meta);
            setSyncState(failed ? 'warn' : 'running', failed ? 'Parsial' : 'Antre');
            updateSyncDot(true);
            await refreshSettlementSyncLogs();
        } catch (e) {
            const presentation = presentSyncError(e, 'Gagal mengirim sync semua toko');
            showSyncFeedback(presentation.level, presentation.title, presentation.message, [presentation.code ? `Kode: ${presentation.code}` : null].filter(Boolean), presentation.action);
            setSyncState(presentation.level === 'warn' ? 'warn' : 'error', presentation.level === 'warn' ? 'Perlu tindakan' : 'Gagal');
            updateSyncDot(false);
        } finally {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
        }
    };

    init();
})();
</script>
@endpush
