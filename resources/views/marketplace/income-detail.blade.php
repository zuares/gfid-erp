@extends('layouts.app')
@section('title', 'Marketplace - Rincian Penghasilan')

@include('marketplace._shared')

@push('head')
    <style>
        .income-shell{
            position:relative;
        }
        .income-hero{
            position:sticky;
            top:.5rem;
            z-index:320;
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:1rem;
            flex-wrap:wrap;
            margin-bottom:.85rem;
            padding:1rem 1.05rem;
            border-radius:20px;
            border:1px solid rgba(148,163,184,.14);
            background:
                radial-gradient(circle at top right, rgba(96,165,250,.28), transparent 30%),
                radial-gradient(circle at bottom left, rgba(16,185,129,.16), transparent 28%),
                linear-gradient(135deg, #0f172a 0%, #111827 45%, #1d4ed8 135%);
            box-shadow:0 18px 50px rgba(15,23,42,.16);
            overflow:hidden;
        }
        .income-hero::before,
        .income-hero::after{
            content:'';
            position:absolute;
            border-radius:999px;
            pointer-events:none;
            opacity:.35;
            filter:blur(2px);
        }
        .income-hero::before{
            width:180px;
            height:180px;
            right:-60px;
            top:-80px;
            background:rgba(59,130,246,.28);
        }
        .income-hero::after{
            width:220px;
            height:220px;
            left:-90px;
            bottom:-130px;
            background:rgba(16,185,129,.18);
        }
        .income-hero > *{
            position:relative;
            z-index:1;
        }
        .income-hero .title{
            color:#fff;
            font-size:1.2rem;
            letter-spacing:-0.04em;
            margin:0;
        }
        .income-hero .sub{
            color:rgba(226,232,240,.8);
            max-width:48rem;
        }
        .income-eyebrow{
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            margin-bottom:.35rem;
            font-size:.66rem;
            font-weight:900;
            letter-spacing:.08em;
            text-transform:uppercase;
            color:#0f172a;
        }
        .income-hero-badges{
            display:flex;
            flex-wrap:wrap;
            gap:.4rem;
            margin-top:.75rem;
        }
        .income-chip{
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            padding:.34rem .6rem;
            border-radius:999px;
            background:rgba(255,255,255,.08);
            border:1px solid rgba(255,255,255,.12);
            color:rgba(255,255,255,.92);
            font-size:.72rem;
            font-weight:800;
            white-space:nowrap;
        }
        .income-hero .controls{
            justify-content:flex-end;
            gap:.45rem;
        }
        .income-hero .btn-pill{
            border-radius:999px;
            padding-inline:.82rem;
            font-size:.78rem;
            font-weight:800;
        }
        .income-hero .btn-ship-outline{
            background:rgba(255,255,255,.06)!important;
            border-color:rgba(255,255,255,.18)!important;
            color:#fff!important;
            box-shadow:none!important;
        }
        .income-hero .btn-ship-outline:hover{
            background:rgba(255,255,255,.14)!important;
            border-color:rgba(255,255,255,.26)!important;
            color:#fff!important;
        }
        body[data-theme="dark"] .income-hero{
            background:
                radial-gradient(circle at top right, rgba(59,130,246,.22), transparent 30%),
                radial-gradient(circle at bottom left, rgba(16,185,129,.12), transparent 28%),
                linear-gradient(135deg, rgba(15,23,42,.98) 0%, rgba(30,41,59,.94) 46%, rgba(30,64,175,.88) 135%);
        }
        .income-tabs-wrap{
            margin:0 0 1rem;
            position:relative;
        }
        .income-tabs-wrap::before{
            content:'';
            position:absolute;
            left:.9rem;
            right:.9rem;
            top:50%;
            height:1px;
            background:linear-gradient(90deg, transparent, rgba(148,163,184,.18), transparent);
            transform:translateY(-50%);
            pointer-events:none;
        }
        .income-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
            gap:.75rem;
            margin-bottom:.95rem;
        }
        .income-kpi{
            position:relative;
            display:flex;
            flex-direction:column;
            min-height:172px;
            border:1px solid rgba(148,163,184,.16);
            border-radius:18px;
            padding:1rem 1rem .95rem;
            background:linear-gradient(180deg,#fff 0%,#f8fafc 100%);
            box-shadow:0 12px 28px rgba(15,23,42,0.05);
            overflow:hidden;
        }
        body[data-theme="dark"] .income-kpi{
            background:rgba(15,23,42,.92);
            border-color:rgba(51,65,85,.85);
            box-shadow:none;
        }
        .income-kpi::before{
            content:'';
            position:absolute;
            inset:0 auto auto 0;
            width:100%;
            height:3px;
            background:linear-gradient(90deg,var(--kpi-accent-start,#334155),var(--kpi-accent-end,#94a3b8));
        }
        .income-kpi.kpi-order{ --kpi-accent-start:#0f172a; --kpi-accent-end:#334155; }
        .income-kpi.kpi-gross{ --kpi-accent-start:#2563eb; --kpi-accent-end:#38bdf8; }
        .income-kpi.kpi-promo{ --kpi-accent-start:#dc2626; --kpi-accent-end:#f97316; }
        .income-kpi.kpi-fee{ --kpi-accent-start:#b45309; --kpi-accent-end:#f59e0b; }
        .income-kpi.kpi-profit{ --kpi-accent-start:#16a34a; --kpi-accent-end:#22c55e; }
        .income-kpi-label{
            font-size:.62rem;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.08em;
            color:#334155;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
            line-height:1.05;
        }
        .income-kpi-value{
            font-size:1.28rem;
            font-weight:950;
            line-height:1;
            letter-spacing:-0.03em;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
            color:var(--shp-text);
            margin-top:.34rem;
            margin-bottom:0;
        }
        .kpi-head{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:.75rem;
        }
        .kpi-head .income-kpi-label{
            min-width:0;
            flex:1 1 auto;
            padding-top:.06rem;
        }
        .kpi-inline-pct{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            flex:0 0 auto;
            min-height:1.18rem;
            padding:.08rem .42rem;
            border-radius:999px;
            font-size:.52rem;
            font-weight:900;
            letter-spacing:-.01em;
            line-height:1;
            color:#0f172a;
            background:rgba(15,23,42,.08);
            white-space:nowrap;
        }
        .kpi-rate-badge{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:1.35rem;
            padding:.12rem .45rem;
            border-radius:999px;
            font-size:.55rem;
            font-weight:900;
            letter-spacing:-.01em;
            color:#b91c1c;
            background:#fee2e2;
            white-space:nowrap;
        }
        .kpi-sub-list {
            margin-top:auto;
            padding-top:.6rem;
            border-top:1px dashed rgba(148,163,184,0.22);
        }
        .kpi-sub-item {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap:.7rem;
            margin-top:.38rem;
        }
        .kpi-sub-item:first-child {
            margin-top: 0;
        }
        .kpi-sub-label {
            font-size: .66rem;
            color: #334155;
            display: flex;
            align-items: center;
            gap: .3rem;
            line-height:1.1;
            letter-spacing: -0.01em;
            min-width:0;
        }
        .kpi-sub-label .kpi-inline-pct{
            margin-left:0;
            margin-right:.12rem;
            transform:translateY(0);
        }
        .kpi-sub-label .kpi-inline-pct:last-child{
            margin-left:.12rem;
            margin-right:0;
        }
        .kpi-sub-val {
            font-size: .72rem;
            font-weight: 900;
            line-height:1.05;
            color: var(--shp-text);
            text-align:right;
            white-space:nowrap;
            letter-spacing:-0.01em;
        }
        .income-filters{
            display:grid;
            grid-template-columns:repeat(12,minmax(0,1fr));
            gap:.75rem;
            align-items:end;
        }
        .income-field{
            grid-column:span 2;
            min-width:0;
        }
        .income-field.wide{ grid-column:span 4; }
        .income-filters-panel{
            overflow:visible;
        }
        .income-filter-head{
            align-items:flex-start;
            padding:1rem 1rem .8rem;
            margin-bottom:.75rem;
            background:linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,250,252,.88));
        }
        body[data-theme="dark"] .income-filter-head{
            background:linear-gradient(180deg, rgba(15,23,42,.98), rgba(15,23,42,.9));
        }
        .income-filter-head .income-eyebrow{
            color:#0f172a;
            background:rgba(15,23,42,.06);
            padding:.22rem .48rem;
            border-radius:999px;
            margin-bottom:.45rem;
        }
        body[data-theme="dark"] .income-filter-head .income-eyebrow{
            color:#e2e8f0;
            background:rgba(255,255,255,.06);
        }
        .income-filter-head .income-panel-title{
            font-size:.98rem;
            letter-spacing:-0.03em;
            margin-top:0;
        }
        .income-field label{
            display:block;
            margin-bottom:.35rem;
            font-size:.67rem;
            font-weight:900;
            text-transform:none;
            letter-spacing:-0.01em;
            color:#334155;
        }
        .income-field input,
        .income-field select{
            width:100%;
            height:42px;
            border-radius:14px;
            border:1px solid rgba(148,163,184,.22);
            background:var(--card,#fff);
            color:var(--shp-text);
            font-size:.84rem;
            padding:.42rem .75rem;
            outline:none;
            transition:border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }
        .income-field input:focus,
        .income-field select:focus{
            border-color:#1d4ed8;
            box-shadow:0 0 0 3px rgba(37,99,235,.10);
        }
        .income-date-wrap{
            position:relative;
        }
        .income-date-wrap .income-date-icon{
            position:absolute;
            left:8px;
            top:50%;
            transform:translateY(-50%);
            z-index:2;
            font-size:.72rem;
            color:#94a3b8;
            pointer-events:none;
        }
        .income-date-input{
            height:38px;
            padding:.3rem .75rem .3rem 1.8rem !important;
            border-radius:12px !important;
            font-size:.78rem !important;
            letter-spacing:-0.01em;
            cursor:pointer;
        }
        .income-date-input::placeholder{
            color:#94a3b8;
        }
        body[data-theme="dark"] .income-field input,
        body[data-theme="dark"] .income-field select{
            background:rgba(15,23,42,.72);
            border-color:rgba(255,255,255,.12);
            color:#e2e8f0;
        }
        body[data-theme="dark"] .income-date-input{
            background:rgba(15,23,42,.82) !important;
            border-color:rgba(148,163,184,.22);
            color:#e2e8f0;
        }
        body[data-theme="dark"] .income-date-input::placeholder{
            color:#64748b;
        }
        .income-surface{
            border-radius:20px;
            border:1px solid rgba(148,163,184,.16);
            background:var(--card,#fff);
            box-shadow:0 12px 28px rgba(15,23,42,.05);
            overflow:hidden;
        }
        body[data-theme="dark"] .income-surface{
            background:var(--card,#0f172a);
            border-color:rgba(51,65,85,.85);
            box-shadow:none;
        }
        .income-panel-head,
        .income-table-head{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:1rem;
            flex-wrap:wrap;
            padding:1rem 1rem .95rem;
            border-bottom:1px solid rgba(148,163,184,.22);
            margin-bottom:1rem;
            background:linear-gradient(180deg, rgba(248,250,252,.92), rgba(255,255,255,.98));
        }
        body[data-theme="dark"] .income-panel-head,
        body[data-theme="dark"] .income-table-head{
            background:linear-gradient(180deg, rgba(15,23,42,.96), rgba(15,23,42,.88));
            border-bottom-color:rgba(51,65,85,.9);
        }
        .income-panel-title{
            margin:.08rem 0 0;
            font-size:1.02rem;
            font-weight:900;
            letter-spacing:-0.04em;
            color:var(--shp-text);
        }
        .income-panel-note,
        .income-table-meta{
            font-size:.76rem;
            color:var(--shp-muted);
            padding:.38rem .68rem;
            border-radius:999px;
            background:rgba(148,163,184,.12);
            border:1px solid rgba(148,163,184,.22);
            box-shadow:inset 0 1px 0 rgba(255,255,255,.42);
        }
        body[data-theme="dark"] .income-panel-note,
        body[data-theme="dark"] .income-table-meta{
            background:rgba(30,41,59,.82);
            border-color:rgba(51,65,85,.92);
            color:#cbd5e1;
            box-shadow:none;
        }
        .income-tabs{
            display:inline-flex;
            align-items:center;
            gap:.4rem;
            padding:.45rem;
            border-radius:999px;
            background:linear-gradient(180deg, rgba(248,250,252,.96), rgba(241,245,249,.92));
            border:1px solid rgba(148,163,184,.18);
            box-shadow:0 12px 28px rgba(15,23,42,.06);
            overflow-x:auto;
            max-width:100%;
            scrollbar-width:none;
            position:relative;
            z-index:1;
        }
        .income-tabs::-webkit-scrollbar{ display:none; }
        body[data-theme="dark"] .income-tabs{
            background:linear-gradient(180deg, rgba(15,23,42,.96), rgba(30,41,59,.92));
            border-color:rgba(51,65,85,.85);
            box-shadow:none;
        }
        .income-tab{
            border:none;
            background:transparent;
            color:#64748b;
            border-radius:999px;
            padding:.72rem 1rem;
            font-size:.8rem;
            font-weight:900;
            letter-spacing:-0.01em;
            cursor:pointer;
            white-space:nowrap;
            transition:all .18s ease;
        }
        .income-tab:hover{
            color:#0f172a;
            background:rgba(255,255,255,.8);
            transform:translateY(-1px);
        }
        .income-tab.active{
            background:#0f172a;
            color:#fff;
            box-shadow:0 10px 20px rgba(15,23,42,.18);
        }
        body[data-theme="dark"] .income-tab{
            color:#94a3b8;
        }
        body[data-theme="dark"] .income-tab:hover{
            color:#e2e8f0;
            background:rgba(255,255,255,.06);
        }
        body[data-theme="dark"] .income-tab.active{
            background:#1d4ed8;
            color:#fff;
        }
        .income-subtabs{
            display:flex;
            align-items:center;
            gap:.45rem;
            flex-wrap:wrap;
            width:fit-content;
            max-width:100%;
            padding:.35rem;
            border-radius:999px;
            background:rgba(148,163,184,.08);
            border:1px solid rgba(148,163,184,.16);
        }
        body[data-theme="dark"] .income-subtabs{
            background:rgba(15,23,42,.72);
            border-color:rgba(51,65,85,.85);
        }
        .income-subtab{
            border:none;
            background:transparent;
            color:#64748b;
            font-size:.72rem;
            font-weight:800;
            padding:.36rem .72rem;
            border-radius:999px;
            white-space:nowrap;
            transition:all .15s ease;
        }
        .income-subtab:hover{
            color:#0f172a;
            background:rgba(255,255,255,.65);
        }
        .income-subtab.active{
            color:var(--shp-text);
            background:var(--card,#fff);
            box-shadow:0 4px 10px rgba(15,23,42,.08);
        }
        body[data-theme="dark"] .income-subtab{
            color:#94a3b8;
        }
        body[data-theme="dark"] .income-subtab:hover{
            color:#e2e8f0;
            background:rgba(255,255,255,.06);
        }
        body[data-theme="dark"] .income-subtab.active{
            background:rgba(15,23,42,.98);
            color:#e2e8f0;
        }
        .income-fee-list{
            list-style:none;
            padding:0;
            margin:0;
            font-size:.7rem;
            color:#64748b;
        }
        .income-fee-list li{
            display:flex;
            justify-content:space-between;
            gap:.45rem;
            margin-bottom:2px;
        }
        .income-fee-val{ font-weight:700; color:#b91c1c; }
        .income-sect{
            margin-top:.4rem;
            padding-top:.3rem;
            border-top:1px dashed rgba(148,163,184,.24);
            font-size:.62rem;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.04em;
            color:#64748b;
        }
        .income-breakdown-btn{
            border:1px solid rgba(37,99,235,.18);
            background:rgba(37,99,235,.06);
            color:#1d4ed8;
            font-size:.74rem;
            font-weight:800;
            border-radius:999px;
            padding:.38rem .72rem;
            line-height:1;
            transition:background .15s ease, transform .15s ease, border-color .15s ease;
        }
        .income-breakdown-btn:hover{
            background:rgba(37,99,235,.12);
            border-color:rgba(37,99,235,.28);
            transform:translateY(-1px);
        }
        .income-breakdown-summary{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:.6rem;
            margin-bottom:1rem;
        }
        .income-breakdown-pill{
            padding:.65rem .75rem;
            border:1px solid rgba(148,163,184,.18);
            border-radius:12px;
            background:rgba(248,250,252,.8);
        }
        body[data-theme="dark"] .income-breakdown-pill{
            background:rgba(15,23,42,.88);
        }
        .income-breakdown-pill .label{
            display:block;
            font-size:.64rem;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.04em;
            color:#64748b;
        }
        .income-breakdown-pill .value{
            display:block;
            margin-top:.14rem;
            font-size:.95rem;
            font-weight:900;
            color:var(--shp-text);
        }
        .income-scroll{
            max-height:72vh;
            overflow:auto;
        }
        @media (max-width: 1100px){
            .income-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); }
            .income-field{ grid-column:span 4; }
            .income-field.wide{ grid-column:span 12; }
            .income-kpi{
                min-height:unset;
            }
            .income-kpi-value{
                font-size:1.08rem;
            }
            .kpi-sub-label{
                font-size:.62rem;
            }
            .kpi-sub-val{
                font-size:.66rem;
            }
        }
        @media (min-width: 641px) and (max-width: 900px){
            .income-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); }
            .income-kpi{
                padding:.95rem .9rem .85rem;
            }
            .income-kpi-value{
                font-size:1.04rem;
            }
            .kpi-sub-item{
                gap:.45rem;
            }
        }
        @media (max-width: 640px){
            .income-hero{
                top:.35rem;
                padding:.9rem .9rem .85rem;
            }
            .income-hero .controls{ width:100%; justify-content:flex-start; }
            .income-grid{ grid-template-columns:1fr; }
            .income-field,
            .income-field.wide{ grid-column:span 12; }
            .income-panel-head,
            .income-table-head{ padding:.9rem .9rem .75rem; }
            .income-filter-head{
                padding:.9rem .9rem .7rem;
            }
            .income-filter-head .income-panel-title{
                font-size:.95rem;
            }
            .income-field label{
                font-size:.64rem;
            }
            .income-field input,
            .income-field select{
                height:40px;
                font-size:.82rem;
                border-radius:12px;
            }
            .income-kpi{
                min-height:unset;
                padding:.88rem .86rem .82rem;
                border-radius:16px;
            }
            .income-kpi-value{
                font-size:1rem;
                margin-top:.28rem;
            }
            .income-kpi-label{
                font-size:.58rem;
                letter-spacing:.06em;
            }
            .kpi-head{
                gap:.35rem;
            }
            .kpi-inline-pct{
                min-height:1rem;
                padding:.06rem .3rem;
                font-size:.48rem;
            }
            .kpi-sub-list{
                padding-top:.45rem;
            }
            .kpi-sub-item{
                gap:.4rem;
                margin-top:.25rem;
            }
            .kpi-sub-label{
                font-size:.58rem;
            }
            .kpi-sub-val{
                font-size:.6rem;
            }
        }
        /* New Classes for Refactored Table */
        .txt-danger { color: #b91c1c; }
        .txt-success { color: #16a34a; }
        .txt-strong { font-weight: 900; color: var(--shp-text); }
        .txt-normal { color: var(--shp-text); }
        .txt-muted-sm { font-size: 0.6rem; color: #94a3b8; display: block; margin-top: 1px; line-height: 1.2; font-weight: 500; }
        .txt-val { font-weight: 650; font-size: 0.7rem; letter-spacing: -0.01em; }
        body[data-theme="dark"] .txt-danger { color: #ef4444; }
        body[data-theme="dark"] .txt-success { color: #22c55e; }
        .income-table { border-collapse: separate; border-spacing: 0; }
        .income-table th {
            white-space: nowrap;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            color: #475569;
            font-size: 0.68rem;
            padding: 0.72rem 0.78rem;
            text-transform: uppercase;
            font-weight: 900;
            letter-spacing: 0.05em;
            border-bottom: 1px solid rgba(148,163,184,0.2);
        }
        .income-table thead th { position: sticky; top: 0; z-index: 10; box-shadow: 0 1px 0 rgba(148,163,184,0.18); }
        .income-table tbody tr { transition: background-color 0.15s, transform 0.15s ease; }
        .income-table tbody tr:nth-child(odd) { background-color: rgba(248,250,252,0.58); }
        .income-table tbody tr:hover { background-color: rgba(59,130,246,0.06); transform: translateY(-1px); }
        body[data-theme="dark"] .income-table tbody tr:nth-child(odd) { background-color: rgba(15,23,42,.58); }
        body[data-theme="dark"] .income-table tbody tr:hover { background-color: rgba(30,41,59,0.52); }
        .income-table td { vertical-align: top; padding: 0.82rem 0.8rem; border-bottom: 1px solid rgba(148,163,184,0.12); line-height: 1.35; }
        .income-table-shell{
            border-radius:20px;
            overflow:hidden;
        }
        .income-order-table{
            table-layout:fixed;
            width:100%;
        }
        .income-order-table th,
        .income-order-table td{
            white-space:normal;
        }
        .income-order-table th:nth-child(1),
        .income-order-table td:nth-child(1){ width:34%; }
        .income-order-table th:nth-child(2),
        .income-order-table td:nth-child(2){ width:20%; }
        .income-order-table th:nth-child(3),
        .income-order-table td:nth-child(3){ width:18%; }
        .income-order-table th:nth-child(4),
        .income-order-table td:nth-child(4){ width:18%; }
        .income-order-table th:nth-child(5),
        .income-order-table td:nth-child(5){ width:10%; }
        .income-product-table{
            table-layout:fixed;
            width:100%;
        }
        .income-product-table th,
        .income-product-table td{
            white-space:normal;
        }
        .income-product-table th:nth-child(1),
        .income-product-table td:nth-child(1){ width:44px; }
        .income-product-table th:nth-child(2),
        .income-product-table td:nth-child(2){ width:25%; }
        .income-product-table th:nth-child(3),
        .income-product-table td:nth-child(3){ width:68px; }
        .income-product-table th:nth-child(4),
        .income-product-table td:nth-child(4){ width:56px; }
        .income-product-table th:nth-child(5),
        .income-product-table td:nth-child(5){ width:11%; }
        .income-product-table th:nth-child(6),
        .income-product-table td:nth-child(6){ width:11%; }
        .income-product-table th:nth-child(7),
        .income-product-table td:nth-child(7){ width:11%; }
        .income-product-table th:nth-child(8),
        .income-product-table td:nth-child(8){ width:11%; }
        .income-product-table th:nth-child(9),
        .income-product-table td:nth-child(9){ width:11%; }
        .income-product-metric{
            display:flex;
            flex-direction:column;
            gap:.08rem;
            min-width:0;
            line-height:1.1;
            text-align:right;
        }
        body[data-theme="dark"] .income-product-metric{
        }
        .income-product-metric .label{
            display:none;
        }
        .income-product-metric .value{
            font-size:.6rem;
            font-weight:850;
            color:var(--shp-text);
            white-space:nowrap;
            overflow:visible;
            text-overflow:clip;
            letter-spacing:-0.01em;
            font-variant-numeric: tabular-nums;
        }
        .income-product-metric .unit{
            font-size:.6rem;
            font-weight:800;
            color:#94a3b8;
            white-space:nowrap;
            overflow:visible;
            text-overflow:clip;
            letter-spacing:-0.01em;
            line-height:1;
        }
        .income-product-metric .value.primary{
            color:#2563eb;
        }
        .income-product-metric .value.success{
            color:#16a34a;
        }
        .income-product-metric .value.danger{
            color:#dc2626;
        }
        .income-table-shell .income-table-head{
            margin-bottom:0;
            background:linear-gradient(180deg, rgba(248,250,252,.82), rgba(255,255,255,.98));
        }
        body[data-theme="dark"] .income-table-shell .income-table-head{
            background:linear-gradient(180deg, rgba(15,23,42,.92), rgba(15,23,42,.98));
        }
        .income-table td [style*="font-size:0.45rem"],
        .income-table td [style*="font-size:0.48rem"],
        .income-table td [style*="font-size:0.5rem"],
        .income-table td [style*="font-size:0.54rem"],
        .income-table td [style*="font-size:0.55rem"],
        .income-table td [style*="font-size:0.6rem"],
        .income-table td [style*="font-size:0.62rem"],
        .income-table td [style*="font-size:0.65rem"],
        .income-table td [style*="font-size:0.7rem"]{
            font-size:.78rem !important;
            line-height:1.25 !important;
        }
        .income-table td .txt-val{ font-size:.78rem !important; }
        .income-table td .text-muted{ color:#64748b !important; }
        .income-table td .btn{
            border-radius:999px !important;
            font-size:.72rem !important;
            font-weight:800 !important;
            padding:.42rem .75rem !important;
        }
        .income-product-title{
            display:block;
            min-width:0;
            max-width:220px;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
            font-size:.68rem;
            font-weight:850;
            letter-spacing:-0.01em;
            line-height:1.15;
            color:var(--shp-text);
        }
        .income-product-group-title{
            display:block;
            min-width:0;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
            font-size:.72rem;
            font-weight:900;
            letter-spacing:-0.01em;
            line-height:1.15;
            color:var(--shp-text);
        }
        .income-product-group-summary{
            display:grid;
            grid-template-columns:minmax(0,1.7fr) 76px 76px minmax(0,1.25fr) auto;
            gap:.5rem;
            align-items:center;
            width:100%;
        }
        .income-product-group-cell{
            min-width:0;
        }
        .income-product-group-stat{
            display:flex;
            flex-direction:column;
            gap:.05rem;
            min-width:0;
            padding:.2rem .35rem;
            border-radius:10px;
            background:rgba(255,255,255,.58);
            border:1px solid rgba(148,163,184,.12);
        }
        .income-product-group-stat .label{
            font-size:.44rem;
            font-weight:900;
            letter-spacing:.06em;
            text-transform:uppercase;
            color:#64748b;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .income-product-group-stat .value{
            font-size:.63rem;
            font-weight:900;
            color:var(--shp-text);
            line-height:1.1;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .income-product-group-stat .value.muted{
            color:#64748b;
            font-weight:800;
        }
        .income-product-financial-stack{
            display:flex;
            flex-direction:column;
            gap:.14rem;
            min-width:0;
            padding:.22rem .38rem;
            border-radius:12px;
            background:rgba(255,255,255,.58);
            border:1px solid rgba(148,163,184,.12);
        }
        .income-product-financial-line{
            display:flex;
            justify-content:space-between;
            gap:.45rem;
            align-items:baseline;
            min-width:0;
            line-height:1.1;
        }
        .income-product-financial-line .label{
            font-size:.44rem;
            font-weight:900;
            letter-spacing:.06em;
            text-transform:uppercase;
            color:#64748b;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .income-product-financial-line .value{
            font-size:.63rem;
            font-weight:900;
            color:var(--shp-text);
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
            text-align:right;
        }
        .income-product-financial-line .value.muted{
            color:#64748b;
            font-weight:800;
        }
        .income-product-financial-line .value.success{
            color:#16a34a;
        }
        .income-product-financial-line .value.danger{
            color:#dc2626;
        }
        .income-product-group-badges{
            display:flex;
            flex-wrap:wrap;
            justify-content:flex-end;
            gap:.22rem;
        }
        .income-row-stack{
            display:flex;
            flex-direction:column;
            gap:.38rem;
            min-width:0;
        }
        .income-row-top{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:.5rem;
            line-height:1.15;
            min-width:0;
        }
        .income-row-main{
            display:flex;
            align-items:center;
            gap:.33rem;
            min-width:0;
        }
        .income-row-title{
            font-size:.7rem;
            font-weight:900;
            letter-spacing:-0.01em;
            color:#0f172a;
            text-decoration:none;
            min-width:0;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
        }
        .income-row-sub{
            display:flex;
            align-items:center;
            gap:.35rem;
            min-width:0;
            font-size:.53rem;
            line-height:1.1;
            color:#64748b;
        }
        .income-row-badges{
            display:flex;
            flex-wrap:wrap;
            gap:.25rem;
        }
        .income-chip{
            display:inline-flex;
            align-items:center;
            gap:.25rem;
            padding:.2rem .52rem;
            border-radius:999px;
            border:1px solid transparent;
            font-size:.48rem;
            font-weight:900;
            letter-spacing:.05em;
            text-transform:uppercase;
            white-space:nowrap;
            line-height:1;
        }
        .income-chip i{
            font-size:.58rem;
        }
        .income-chip.neutral{
            background:rgba(148,163,184,.12);
            border-color:rgba(148,163,184,.18);
            color:#475569;
        }
        .income-chip.primary{
            background:rgba(59,130,246,.08);
            border-color:rgba(59,130,246,.16);
            color:#2563eb;
        }
        .income-chip.success{
            background:rgba(16,185,129,.08);
            border-color:rgba(16,185,129,.18);
            color:#059669;
        }
        .income-chip.warning{
            background:rgba(245,158,11,.1);
            border-color:rgba(245,158,11,.18);
            color:#b45309;
        }
        .income-chip.danger{
            background:rgba(239,68,68,.08);
            border-color:rgba(239,68,68,.16);
            color:#dc2626;
        }
        .income-timeline-stack{
            display:flex;
            flex-direction:column;
            gap:.28rem;
            min-width:0;
        }
        .income-timeline-row{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:.55rem;
            line-height:1.15;
            min-width:0;
            padding:.28rem .42rem;
            border-radius:10px;
            background:rgba(248,250,252,.8);
            border:1px solid rgba(148,163,184,.14);
        }
        body[data-theme="dark"] .income-timeline-row{
            background:rgba(15,23,42,.72);
            border-color:rgba(71,85,105,.28);
        }
        .income-timeline-label{
            display:flex;
            align-items:center;
            gap:.28rem;
            min-width:0;
            font-size:.48rem;
            line-height:1.1;
            color:#64748b;
            text-transform:uppercase;
            letter-spacing:.05em;
            font-weight:900;
        }
        .income-timeline-value{
            font-size:.58rem;
            font-weight:900;
            color:var(--shp-text);
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
            text-align:right;
        }
        .income-row-meta-label{
            display:flex;
            align-items:center;
            gap:.28rem;
            min-width:0;
        }
        .income-row-meta-value{
            font-weight:800;
            text-align:right;
            white-space:nowrap;
        }
        .income-item-list{
            margin-top:.35rem;
            padding-top:.35rem;
            border-top:1px solid rgba(148,163,184,.14);
            display:flex;
            flex-direction:column;
            gap:.28rem;
        }
        .income-item-row{
            display:grid;
            grid-template-columns:16px minmax(0,1fr) auto auto;
            align-items:center;
            gap:.35rem;
            min-width:0;
        }
        .income-item-name{
            font-size:.56rem;
            font-weight:650;
            color:#334155;
            min-width:0;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
        }
        .income-item-qty{
            font-size:.56rem;
            font-weight:800;
            color:#0f172a;
            white-space:nowrap;
        }
        .income-item-sku{
            display:flex;
            align-items:center;
            gap:.25rem;
            justify-self:end;
            min-width:0;
            max-width:92px;
        }
        .income-item-sku span{
            font-size:.5rem;
            color:#64748b;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
        }
        .income-money-stack{
            display:flex;
            flex-direction:column;
            gap:.28rem;
            padding:.18rem .24rem;
            border-radius:12px;
            background:rgba(255,255,255,.54);
            border:1px solid rgba(148,163,184,.12);
            min-width:0;
        }
        body[data-theme="dark"] .income-money-stack{
            background:rgba(15,23,42,.58);
            border-color:rgba(71,85,105,.28);
        }
        .income-money-line{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:.5rem;
            line-height:1.15;
        }
        .income-money-label{
            font-size:.52rem;
            color:#64748b;
            text-transform:uppercase;
            letter-spacing:.05em;
            font-weight:900;
        }
        .income-money-value{
            font-size:.66rem;
            font-weight:900;
            white-space:nowrap;
            text-align:right;
        }
        .income-action-stack{
            display:flex;
            flex-direction:column;
            gap:.4rem;
            align-items:stretch;
        }
        .income-action-stack .btn{
            width:100%;
            padding:.38rem .62rem !important;
        }
        @media (max-width: 1280px){
            .income-order-table th:nth-child(2),
            .income-order-table td:nth-child(2){ width:32%; }
            .income-order-table th:nth-child(3),
            .income-order-table td:nth-child(3){ width:16%; }
            .income-order-table th:nth-child(4),
            .income-order-table td:nth-child(4){ width:16%; }
            .income-order-table th:nth-child(5),
            .income-order-table td:nth-child(5){ width:16%; }
            .income-order-table th:nth-child(6),
            .income-order-table td:nth-child(6){ width:10%; }
        }
        @media (max-width: 1100px){
            .income-order-table th:nth-child(2),
            .income-order-table td:nth-child(2){ width:34%; }
            .income-order-table th:nth-child(3),
            .income-order-table td:nth-child(3){ width:15%; }
            .income-order-table th:nth-child(4),
            .income-order-table td:nth-child(4){ width:15%; }
            .income-order-table th:nth-child(5),
            .income-order-table td:nth-child(5){ width:15%; }
            .income-order-table th:nth-child(6),
            .income-order-table td:nth-child(6){ width:9%; }
        }
        .skeleton-box { display: inline-block; height: 1em; position: relative; overflow: hidden; background-color: rgba(148,163,184, 0.2); border-radius: 4px; width: 100%; }
        .skeleton-box::after { position: absolute; top: 0; right: 0; bottom: 0; left: 0; transform: translateX(-100%); background-image: linear-gradient(90deg, rgba(255, 255, 255, 0) 0, rgba(255, 255, 255, 0.2) 20%, rgba(255, 255, 255, 0.5) 60%, rgba(255, 255, 255, 0)); animation: shimmer 1.5s infinite; content: ''; }
        @keyframes shimmer { 100% { transform: translateX(100%); } }
        .hover-sort:hover { background: rgba(148,163,184,0.1) !important; color: #1e293b !important; }
    </style>
@endpush

@section('content')
<div class="page-wrap income-shell">
    <div class="ship-topbar">
        <div>
            <h1 class="title">Rincian Penghasilan</h1>
            <div class="sub">Gross setelah voucher, beban seller/pembeli/platform, dan dana cair per order.</div>
        </div>
        <div class="controls">
            <a href="{{ route('marketplace.settlement') }}" class="btn btn-sm btn-ship-outline btn-pill">
                <i class="bi bi-arrow-left"></i> Ke Settlement
            </a>
            <a href="{{ route('marketplace.profit') }}" class="btn btn-sm btn-ship-outline btn-pill">
                <i class="bi bi-graph-up"></i> Ke Profit
            </a>
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="resetFilters()">Reset Filter</button>
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="loadIncomeDetail()">Refresh</button>
            <button class="btn btn-sm btn-warning btn-pill" id="refreshIncomeEstimateBtn" onclick="refreshIncomeEstimate()" title="Ambil estimasi payout terbaru dari Shopee untuk toko yang dipilih">
                <i class="bi bi-clock-history"></i> Perbarui Estimasi
            </button>
            <span id="incomeEstimateSyncStatus" style="font-size:.68rem;color:var(--shp-muted);"></span>
        </div>
    </div>

    <div class="income-tabs-wrap">
        <div class="income-tabs" role="tablist" aria-label="Tabs penghasilan">
            <button type="button" class="income-tab active" onclick="switchIncomeTab('cair', this)">Dana Cair</button>
            <button type="button" class="income-tab" onclick="switchIncomeTab('belum_cair', this)">Belum Cair</button>
            <button type="button" class="income-tab" onclick="switchIncomeTab('semua', this)">Semua Order</button>
            <button type="button" class="income-tab" onclick="switchIncomeTab('batal_return', this)">Batal / Return</button>
            <button type="button" class="income-tab" onclick="switchIncomeTab('produk', this)">Produk</button>
        </div>
    </div>
    <div id="belumCairSubTabs" class="income-subtabs" style="display:none; margin-bottom:1rem;">
        <button type="button" class="income-subtab active" onclick="switchSubTab('', this)">Semua</button>
        <button type="button" class="income-subtab" onclick="switchSubTab('packed', this)">Sedang Dikemas</button>
        <button type="button" class="income-subtab" onclick="switchSubTab('shipped', this)">Sedang Dikirim</button>
        <button type="button" class="income-subtab" onclick="switchSubTab('to_confirm', this)">Menunggu Konfirmasi</button>
        <button type="button" class="income-subtab" onclick="switchSubTab('returning', this)">Sedang Dikembalikan</button>
    </div>

    <div class="card-main income-surface income-panel income-filters-panel" style="margin-bottom:1rem;">
        <div class="income-panel-head income-filter-head">
            <div>
                <div class="income-eyebrow"><i class="bi bi-funnel"></i> Filter</div>
            </div>
        </div>
        <div class="income-filters" style="padding:0 1rem 1rem;">
            <div class="income-field">
                <label>Toko</label>
                <select id="filterStore" onchange="goFirstPage()">
                    <option value="">Semua</option>
                </select>
            </div>
            <div class="income-field">
                <label>Status Mapping</label>
                <select id="filterCogsZero" onchange="goFirstPage()">
                    <option value="">Semua Order</option>
                    <option value="1">Belum Mapped (COGS 0)</option>
                </select>
            </div>
            <div class="income-field wide">
                <label>Cari</label>
                <input id="filterSearch" type="search" placeholder="Order SN / produk / SKU..." oninput="onSearchInput()" onclick="this.select()">
            </div>
            <div class="income-field">
                <label>Tgl Order</label>
                <div class="income-date-wrap">
                    <i class="bi bi-calendar3 income-date-icon"></i>
                    <input id="filterOrder" class="income-date-input" type="text" placeholder="Bulan ini">
                </div>
            </div>
            <div class="income-field">
                <label id="filterSettlementLabel">Tgl Cair</label>
                <div class="income-date-wrap">
                    <i class="bi bi-calendar-check income-date-icon" style="color:#10b981;"></i>
                    <input id="filterSettlement" class="income-date-input" type="text" placeholder="Bulan ini">
                </div>
            </div>
        </div>
    </div>

    <div id="incomeSummaryPanel" class="card-main income-surface income-panel" style="margin-bottom:1rem;">
        <div class="income-panel-head">
            <div>
                <div class="income-eyebrow" id="summarySectionLabel"><i class="bi bi-bar-chart-line"></i> Ringkasan</div>
                <div id="kpiSummaryNote" class="income-panel-note">-</div>
            </div>
        </div>
        <div id="tabKpiGrid" class="income-grid" style="padding:0 1rem 1rem; display:none;"></div>
        <div id="legacyKpiGrid" class="income-grid" style="padding:0 1rem 1rem;">
            <div class="income-kpi kpi-order">
                <div class="income-kpi-label">Order</div>
                <div class="income-kpi-value" id="kpiCount">-</div>
                <div class="kpi-sub-list">
                    <div class="kpi-sub-item">
                        <span class="kpi-sub-label"><i class="bi bi-check-circle text-success"></i><span>Selesai</span></span>
                        <span id="kpiCountSelesai" class="kpi-sub-val">-</span>
                    </div>
                    <div class="kpi-sub-item">
                        <span class="kpi-sub-label"><i class="bi bi-x-circle text-danger"></i><span>Batal</span></span>
                        <span id="kpiCountBatal" class="kpi-sub-val text-danger">-</span>
                    </div>
                    <div class="kpi-sub-item">
                        <span class="kpi-sub-label"><i class="bi bi-tools text-warning"></i><span>Adj.</span></span>
                        <span id="kpiCountPenyesuaian" class="kpi-sub-val text-warning">-</span>
                    </div>
                </div>
            </div>
            <div class="income-kpi kpi-gross">
                <div class="kpi-head">
                    <div class="income-kpi-label" id="kpiGrossLabel">Gross</div>
                    <div id="kpiGrossPercent" class="kpi-inline-pct">-</div>
                </div>
                <div class="income-kpi-value" id="kpiGross">-</div>
                <div class="kpi-sub-list">
                    <div class="kpi-sub-item">
                        <span class="kpi-sub-label" id="kpiGrossBuyerPaidLabel"><i class="bi bi-person"></i><span>Paid</span></span>
                        <span id="kpiGrossBuyerPaid" class="kpi-sub-val">-</span>
                    </div>
                    <div class="kpi-sub-item">
                        <span class="kpi-sub-label"><i class="bi bi-cart"></i><span>AOV</span></span>
                        <span id="kpiAovPembeli" class="kpi-sub-val">-</span>
                    </div>
                </div>
            </div>
            <div class="income-kpi kpi-promo">
                <div class="kpi-head">
                    <div class="income-kpi-label" id="kpiVoucherTotalLabel">Promo</div>
                    <div id="kpiVoucherTotalPercent" class="kpi-inline-pct">-</div>
                </div>
                <div class="income-kpi-value text-danger" id="kpiVoucherTotal">-</div>
                <div class="kpi-sub-list">
                    <div class="kpi-sub-item">
                        <span class="kpi-sub-label" id="kpiVoucherTokoLabel"><i class="bi bi-shop"></i><span>Toko</span></span>
                        <span id="kpiVoucherToko" class="kpi-sub-val text-danger">-</span>
                    </div>
                    <div class="kpi-sub-item">
                        <span class="kpi-sub-label" id="kpiVoucherPlatformLabel"><i class="bi bi-globe"></i><span>Platform</span></span>
                        <span id="kpiVoucherPlatform" class="kpi-sub-val text-danger">-</span>
                    </div>
                </div>
            </div>
            <div class="income-kpi kpi-fee">
                <div class="kpi-head">
                    <div class="income-kpi-label" id="kpiFeeLabel">Fee</div>
                    <div id="kpiFeePercent" class="kpi-inline-pct">-</div>
                </div>
                <div class="income-kpi-value text-danger" id="kpiFeeTotal">-</div>
                <div class="kpi-sub-list">
                    <div class="kpi-sub-item">
                        <span class="kpi-sub-label" id="kpiAffiliateLabel"><i class="bi bi-people"></i><span>Aff.</span></span>
                        <span id="kpiAffiliate" class="kpi-sub-val text-danger">-</span>
                    </div>
                    <div class="kpi-sub-item">
                        <span class="kpi-sub-label" id="kpiMarketplaceLabel"><i class="bi bi-shop"></i><span>Mkt.</span></span>
                        <span id="kpiMarketplace" class="kpi-sub-val text-danger">-</span>
                    </div>
                    <div class="kpi-sub-item">
                        <span class="kpi-sub-label" id="kpiAdjustmentLabel"><i class="bi bi-tools"></i><span>Adj.</span></span>
                        <span id="kpiAdjustment" class="kpi-sub-val text-danger">-</span>
                    </div>
                </div>
            </div>
            <div class="income-kpi kpi-profit">
                <div class="kpi-head">
                    <div class="income-kpi-label" id="kpiGrossProfitLabel">Profit</div>
                    <div id="kpiGrossProfitPercent" class="kpi-inline-pct">-</div>
                </div>
                <div class="income-kpi-value text-success" id="kpiGrossProfit">-</div>
                <div class="kpi-sub-list">
                    <div class="kpi-sub-item">
                        <span class="kpi-sub-label" id="kpiNetLabel"><i class="bi bi-wallet2 text-primary"></i><span>Net</span></span>
                        <span id="kpiNetPayout" class="kpi-sub-val text-success">-</span>
                    </div>
                    <div class="kpi-sub-item">
                        <span class="kpi-sub-label" id="kpiCogsLabel"><i class="bi bi-box-seam text-secondary"></i><span>COGS</span></span>
                        <span id="kpiCogs" class="kpi-sub-val text-danger">-</span>
                    </div>
                    <div class="kpi-sub-item">
                        <span class="kpi-sub-label"><i class="bi bi-percent text-success"></i><span>Margin</span></span>
                        <span id="kpiMargin" class="kpi-sub-val text-success">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="productTopSummary" class="mb-3" hidden></div>

    <div class="card-main income-surface income-panel income-table-shell">
        <div class="income-table-head">
            <div>
                <div class="income-eyebrow"><i class="bi bi-table"></i> Daftar Order</div>
            </div>
            <div id="incomeTableMeta" class="income-table-meta">Memuat data…</div>
        </div>
        <div id="incomeBody" class="income-scroll">
            <div style="padding:1.5rem;">
                <div class="skeleton-box mb-2" style="height:40px; width:100%;"></div>
                <div class="skeleton-box mb-2" style="height:60px; width:100%;"></div>
                <div class="skeleton-box mb-2" style="height:60px; width:100%;"></div>
                <div class="skeleton-box mb-2" style="height:60px; width:100%;"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="incomeBreakdownModal" tabindex="-1" aria-labelledby="incomeBreakdownModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="incomeBreakdownModalLabel">Rincian Potongan</h5>
                        <div class="text-muted small" id="incomeBreakdownModalSub">-</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="incomeBreakdownModalBody">
                    <div class="text-center py-4 text-muted">Pilih baris order untuk melihat rincian potongan.</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const { api, fmt, fmtRp, esc } = window.mpHelpers;
    const productApiUrl = @json(route('marketplace.income-detail.products'));
    const storageKey = 'marketplace:income_detail_filters:v2';
    const $ = (id) => document.getElementById(id);

    let stores = [];
    let rows = [];
    let paginationData = null;
    let currentPage = 1;
    let searchTimer = null;
    let sortBy = 'settlement_time';
    let sortDir = 'desc';
    let currentTab = 'cair';
    let currentSubTab = '';
    let lastMeta = null;
    let orderRangePicker = null;
    let settlementRangePicker = null;

    function relativeTime(value) {
        if (!value) return 'belum pernah diperbarui';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return fmt(value);
        const seconds = Math.round((date.getTime() - Date.now()) / 1000);
        const formatter = new Intl.RelativeTimeFormat('id-ID', { numeric: 'auto' });
        if (Math.abs(seconds) < 60) return formatter.format(seconds, 'second');
        const minutes = Math.round(seconds / 60);
        if (Math.abs(minutes) < 60) return formatter.format(minutes, 'minute');
        const hours = Math.round(minutes / 60);
        if (Math.abs(hours) < 24) return formatter.format(hours, 'hour');
        return formatter.format(Math.round(hours / 24), 'day');
    }

    function updateIncomeModeLabels(meta = null) {
        const isPending = currentTab === 'belum_cair';
        const label = $('filterSettlementLabel');
        const input = $('filterSettlement');
        if (label) label.textContent = isPending ? 'Estimasi Tgl Cair' : (currentTab === 'semua' ? 'Tgl Cair / Order Pending' : 'Tgl Cair');
        if (input) {
            input.placeholder = isPending ? 'Estimasi bulan ini' : 'Bulan ini';
            input.title = isPending
                ? 'Memfilter estimated_payout_at dari Shopee'
                : (currentTab === 'semua' ? 'Tanggal settlement untuk order cair dan tanggal order untuk pending' : 'Memfilter tanggal settlement final');
        }

        const status = $('incomeEstimateSyncStatus');
        if (status && isPending && meta) {
            const shopeeCount = Number(meta.estimate_shopee_count || 0);
            const manualCount = Number(meta.estimate_manual_count || 0);
            status.textContent = `${shopeeCount} estimasi Shopee • ${manualCount} fallback 24%${meta.estimate_last_synced_at ? ` • update ${relativeTime(meta.estimate_last_synced_at)}` : ''}`;
            status.title = meta.estimate_last_synced_at ? `Sinkronisasi terakhir ${fmt(meta.estimate_last_synced_at)}` : 'Belum ada sinkronisasi estimasi Shopee';
        } else if (status && !status.dataset.syncing) {
            status.textContent = '';
            status.removeAttribute('title');
        }
    }

    function parseStoredRange(value) {
        if (!value || typeof value !== 'string') return null;
        const parts = value.split(' to ').map(part => part.trim()).filter(Boolean);
        if (!parts.length) return null;
        if (parts.length === 1) return [parts[0], parts[0]];
        return [parts[0], parts[1]];
    }

    function monthRange() {
        const now = new Date();
        return [
            new Date(now.getFullYear(), now.getMonth(), 1),
            now
        ];
    }

    function todayRange() {
        const now = new Date();
        return [now, now];
    }

    function defaultOrderRange(restoredValue) {
        return parseStoredRange(restoredValue) || monthRange();
    }

    function defaultSettlementRange(restoredValue) {
        return parseStoredRange(restoredValue) || monthRange();
    }

    function syncDefaultFilterInputs() {
        // Open page should always start from the default range.
        if (orderRangePicker) orderRangePicker.setDate(monthRange(), false);
        if (settlementRangePicker) settlementRangePicker.setDate(monthRange(), false);
    }

    window.toggleSort = function(col) {
        if (sortBy === col) {
            sortDir = sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy = col;
            sortDir = 'desc';
        }
        goFirstPage();
    };

    function readState() {
        try {
            return JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
        } catch (e) {
            return {};
        }
    }

    function saveState() {
        localStorage.setItem(storageKey, JSON.stringify({
            store: $('filterStore')?.value || '',
            search: $('filterSearch')?.value || '',
            order_date: $('filterOrder')?.value || '',
            settlement_date: $('filterSettlement')?.value || '',
            tab: currentTab,
            sub_tab: currentSubTab,
            page: currentPage,
            sortBy: sortBy,
            sortDir: sortDir
        }));
    }

    function restoreState() {
        const state = readState();
        if (state.store && $('filterStore')) $('filterStore').value = state.store;
        if (state.search && $('filterSearch')) $('filterSearch').value = state.search;
        if (state.order_date && $('filterOrder')) $('filterOrder').value = state.order_date;
        if (state.settlement_date && $('filterSettlement')) $('filterSettlement').value = state.settlement_date;
        if (state.tab) {
            currentTab = state.tab;
            document.querySelectorAll('.income-tab').forEach(t => t.classList.remove('active'));
            const tabEl = document.querySelector(`.income-tab[onclick*="'${currentTab}'"]`);
            if (tabEl) tabEl.classList.add('active');

            if (currentTab === 'belum_cair') {
                $('belumCairSubTabs').style.display = 'flex';
            } else {
                $('belumCairSubTabs').style.display = 'none';
            }
        }
        updateIncomeModeLabels();
        if (state.sub_tab !== undefined) {
            currentSubTab = state.sub_tab;
            document.querySelectorAll('#belumCairSubTabs button').forEach(b => b.classList.remove('active'));
            const subTabEl = document.querySelector(`#belumCairSubTabs button[onclick*="'${currentSubTab}'"]`);
            if (subTabEl) subTabEl.classList.add('active');
        }
        currentPage = state.page || 1;
        sortBy = state.sortBy || 'settlement_time';
        sortDir = state.sortDir || 'desc';

        return state;
    }

    function bindFilterPersistence() {
        const persistedIds = ['filterStore', 'filterCogsZero', 'filterSearch', 'filterOrder', 'filterSettlement'];
        persistedIds.forEach((id) => {
            const el = $(id);
            if (!el || el.dataset.persistBound === '1') return;
            el.dataset.persistBound = '1';
            const eventName = id === 'filterSearch' ? 'input' : 'change';
            el.addEventListener(eventName, saveState);
            el.addEventListener('blur', saveState);
        });

        if (!window.__incomeDetailBeforeUnloadBound) {
            window.__incomeDetailBeforeUnloadBound = true;
            window.addEventListener('beforeunload', saveState);
        }
    }

    function buildParams() {
        const params = new URLSearchParams();
        if ($('filterStore')?.value) params.append('store_id', $('filterStore').value);
        if ($('filterCogsZero')?.value === '1') params.append('cogs_zero', '1');
        if ($('filterSearch')?.value) params.append('search', $('filterSearch').value);
        params.append('tab', currentTab);
        params.append('sub_tab', currentSubTab);

        let fOrder = $('filterOrder');
        if (fOrder && fOrder._flatpickr && fOrder._flatpickr.selectedDates.length > 0) {
            let dates = fOrder._flatpickr.selectedDates;
            params.append('order_date_from', window.flatpickr.formatDate(dates[0], 'Y-m-d'));
            params.append('order_date_to', window.flatpickr.formatDate(dates[1] || dates[0], 'Y-m-d'));
        }

        let fStl = $('filterSettlement');
        if (fStl && fStl._flatpickr && fStl._flatpickr.selectedDates.length > 0) {
            let dates = fStl._flatpickr.selectedDates;
            params.append('settlement_date_from', window.flatpickr.formatDate(dates[0], 'Y-m-d'));
            params.append('settlement_date_to', window.flatpickr.formatDate(dates[1] || dates[0], 'Y-m-d'));
        }
        params.append('page', currentPage);
        params.append('per_page', 50);
        params.append('sort_by', sortBy);
        params.append('sort_dir', sortDir);
        return params;
    }

    function goFirstPage() {
        currentPage = 1;
        loadIncomeDetail();
    }

    window.goFirstPage = goFirstPage;

    window.onSearchInput = function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(goFirstPage, 500);
    };

    window.switchIncomeTab = function (tabId, el) {
        document.querySelectorAll('.income-tab').forEach(t => t.classList.remove('active'));
        if (el) el.classList.add('active');
        const previousTab = currentTab;
        currentTab = tabId;
        if ($('incomeSummaryPanel')) {
            $('incomeSummaryPanel').hidden = false;
        }
        if ($('productTopSummary')) {
            $('productTopSummary').hidden = true;
            $('productTopSummary').innerHTML = '';
        }
        if (currentTab === 'belum_cair') {
            $('belumCairSubTabs').style.display = 'flex';
            // Jangan sembunyikan order fallback yang belum memiliki tanggal
            // estimasi. User tetap dapat memilih rentang payout setelah masuk tab.
            if (previousTab !== 'belum_cair' && settlementRangePicker) {
                settlementRangePicker.clear(false);
            }
        } else {
            $('belumCairSubTabs').style.display = 'none';
            currentSubTab = '';
            document.querySelectorAll('#belumCairSubTabs button').forEach(b => b.classList.remove('active'));
            document.querySelector('#belumCairSubTabs button').classList.add('active');
        }
        updateIncomeModeLabels();
        goFirstPage();
    };

    window.switchSubTab = function (subTabId, el) {
        document.querySelectorAll('#belumCairSubTabs button').forEach(b => b.classList.remove('active'));
        if (el) el.classList.add('active');
        currentSubTab = subTabId;
        goFirstPage();
    };

    window.resetFilters = function () {
        localStorage.removeItem(storageKey);
        currentPage = 1;
        currentTab = 'cair';
        currentSubTab = '';
        $('belumCairSubTabs').style.display = 'none';
        document.querySelectorAll('#belumCairSubTabs button').forEach(b => b.classList.remove('active'));
        document.querySelector('#belumCairSubTabs button').classList.add('active');

        document.querySelectorAll('.income-tab').forEach(t => t.classList.remove('active'));
        document.querySelector(`.income-tab[onclick*="'cair'"]`)?.classList.add('active');
        if ($('filterStore')) $('filterStore').value = '';
        if ($('filterCogsZero')) $('filterCogsZero').value = '';
        if ($('filterSearch')) $('filterSearch').value = '';
        if (orderRangePicker) orderRangePicker.setDate(monthRange(), false);
        if (settlementRangePicker) settlementRangePicker.setDate(monthRange(), false);
        updateIncomeModeLabels();
        loadIncomeDetail();
    };

    window.refreshIncomeEstimate = async function () {
        const storeId = $('filterStore')?.value;
        if (!storeId) {
            alert('Pilih satu toko Shopee terlebih dahulu agar estimasi dapat diperbarui.');
            return;
        }

        const btn = $('refreshIncomeEstimateBtn');
        const status = $('incomeEstimateSyncStatus');
        if (!btn || btn.disabled) return;
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memperbarui…';
        if (status) {
            status.dataset.syncing = '1';
            status.style.color = '#b45309';
            status.textContent = 'Mengambil estimasi terbaru dari Shopee…';
        }

        try {
            const result = await api('/api/marketplace/stores/' + storeId + '/sync-income-details', {
                method: 'POST',
                body: JSON.stringify({ page_size: 100 }),
            });
            if (status) {
                status.style.color = '#15803d';
                status.textContent = `${Number(result.updated || 0)} diperbarui • ${Number(result.created || 0)} baru`;
            }
            currentPage = 1;
            await loadIncomeDetail();
        } catch (error) {
            if (status) {
                status.style.color = '#b91c1c';
                status.textContent = error?.data?.message || error?.message || 'Estimasi gagal diperbarui.';
            }
        } finally {
            if (status) delete status.dataset.syncing;
            btn.disabled = false;
            btn.innerHTML = oldHtml;
        }
    };

    function renderKpi(meta) {
        if (!meta) return;
        const toNum = (value) => Number(value || 0);
        const setText = (id, value) => { if ($(id)) $(id).textContent = value; };
        const setHtml = (id, value) => { if ($(id)) $(id).innerHTML = value; };
        const renderInsightGrid = (items) => {
            const grid = $('tabKpiGrid');
            if (!grid) return;
            grid.style.display = items && items.length ? 'grid' : 'none';
            if (!items || !items.length) {
                grid.innerHTML = '';
                return;
            }
            grid.innerHTML = items.slice(0, 12).map(item => `
                <div class="income-kpi" style="min-height:unset; padding:.9rem .95rem .85rem;">
                    <div class="kpi-head" style="margin-bottom:.45rem;">
                        <div class="income-kpi-label">${esc(item.label || '-')}</div>
                        <div class="kpi-inline-pct">${item.pct ?? '-'}</div>
                    </div>
                    <div style="font-size:1.15rem; font-weight:950; line-height:1.05; color:${item.color || 'var(--shp-text)'};">${item.value ?? '-'}</div>
                    <div style="margin-top:.35rem; font-size:.64rem; color:#64748b; font-weight:700;">${item.sub ?? '&nbsp;'}</div>
                </div>
            `).join('');
        };
        const pct = (value, base = toNum(meta.kpi_gross)) => {
            const baseVal = Number(base || 0);
            if (!Number.isFinite(baseVal) || baseVal === 0) return '0.0%';
            return `${((Math.abs(toNum(value)) / baseVal) * 100).toFixed(1)}%`;
        };
        const amount = (value, sign = '') => `${sign}${fmtRp(Math.abs(toNum(value)))}`;
        const labelPct = (value, base = toNum(meta.kpi_gross)) => `<span class="kpi-inline-pct">${pct(value, base)}</span>`;
        const setLabelWithPct = (id, iconHtml, text, value, base = toNum(meta.kpi_gross)) => {
            const el = $(id);
            if (!el) return;
            el.innerHTML = `${labelPct(value, base)}${iconHtml || ''}<span>${text}</span>`;
        };
        const gross = toNum(meta.kpi_gross);
        const voucher = toNum(meta.kpi_voucher);
        const fees = toNum(meta.kpi_fees);
        const net = toNum(meta.kpi_net);
        const cogs = toNum(meta.kpi_cogs);
        const grossProfit = toNum(meta.kpi_gross_profit);
        const margin = gross > 0 ? (grossProfit / gross) * 100 : 0;
        const feePct = gross > 0 ? (fees / gross) * 100 : 0;
        const hideLegacy = () => {
            if ($('legacyKpiGrid')) $('legacyKpiGrid').style.display = 'none';
            if ($('tabKpiGrid')) $('tabKpiGrid').style.display = 'grid';
        };
        const showLegacy = () => {
            if ($('legacyKpiGrid')) $('legacyKpiGrid').style.display = 'grid';
            if ($('tabKpiGrid')) $('tabKpiGrid').style.display = 'none';
        };

        const resetCards = () => {
            ['kpiCount', 'kpiCountSelesai', 'kpiCountBatal', 'kpiCountPenyesuaian', 'kpiGross', 'kpiGrossNetToko', 'kpiGrossBuyerPaid', 'kpiVoucherTotal', 'kpiVoucherToko', 'kpiVoucherPlatform', 'kpiFeeTotal', 'kpiAovPembeli', 'kpiAffiliate', 'kpiMarketplace', 'kpiAdjustment', 'kpiNetPayout', 'kpiCogs', 'kpiGrossProfit', 'kpiMargin'].forEach(id => setText(id, '-'));
            ['kpiGrossLabel','kpiGrossPercent','kpiGrossBuyerPaidLabel','kpiVoucherTotalLabel','kpiVoucherTotalPercent','kpiVoucherTokoLabel','kpiVoucherPlatformLabel','kpiFeeLabel','kpiFeePercent','kpiAffiliateLabel','kpiMarketplaceLabel','kpiAdjustmentLabel','kpiGrossProfitLabel','kpiGrossProfitPercent','kpiNetLabel','kpiCogsLabel'].forEach(id => setHtml(id, '-'));
        };

        const renderMode = {
            cair: () => {
                hideLegacy();
                renderInsightGrid([
                    { label: 'Order Cair', value: Number(meta.kpi_count || 0).toLocaleString('id-ID'), pct: labelPct(meta.kpi_count, meta.kpi_count || 1), sub: 'Order yang sudah settled' },
                    { label: 'Gross', value: fmtRp(meta.kpi_gross || 0), pct: labelPct(gross, gross || 1), sub: 'Nilai penjualan utama' },
                    { label: 'Net', value: fmtRp(meta.kpi_net || 0), pct: labelPct(net, gross || 1), sub: 'Dana yang masuk' },
                    { label: 'Profit', value: fmtRp(grossProfit), pct: `${margin.toFixed(1)}%`, sub: 'Sisa setelah biaya', color: grossProfit < 0 ? '#dc2626' : '#16a34a' },
                    { label: 'AOV', value: fmtRp(meta.kpi_aov), pct: '-', sub: 'Rata-rata per order' },
                    { label: 'Voucher', value: fmtRp(voucher, '-'), pct: labelPct(voucher, gross || 1), sub: 'Total diskon' },
                    { label: 'Fee', value: fmtRp(fees, '-'), pct: labelPct(fees, gross || 1), sub: 'Beban seller' },
                    { label: 'COGS', value: fmtRp(cogs, '-'), pct: labelPct(cogs, gross || 1), sub: 'Modal barang' },
                ]);
                setHtml('kpiSummaryNote', `Fokus dana cair: <b>${Number(meta.kpi_count || 0).toLocaleString('id-ID')}</b> order, gross <b>${fmtRp(meta.kpi_gross || 0)}</b>, net <b>${fmtRp(meta.kpi_net || 0)}</b>.`);
                setText('kpiCount', meta.kpi_count != null ? Number(meta.kpi_count).toLocaleString('id-ID') : '-');
                setText('kpiCountSelesai', meta.kpi_count_selesai != null ? Number(meta.kpi_count_selesai).toLocaleString('id-ID') : '-');
                setText('kpiCountBatal', meta.kpi_count_batal != null ? Number(meta.kpi_count_batal).toLocaleString('id-ID') : '-');
                setText('kpiCountPenyesuaian', meta.kpi_count_penyesuaian != null ? Number(meta.kpi_count_penyesuaian).toLocaleString('id-ID') : '-');
                setHtml('kpiGrossLabel', 'Gross');
                setHtml('kpiGrossPercent', labelPct(gross, gross));
                setHtml('kpiGross', amount(gross));
                setLabelWithPct('kpiGrossBuyerPaidLabel', '<i class="bi bi-person"></i>', 'Paid', meta.kpi_buyer_paid, gross);
                setHtml('kpiGrossBuyerPaid', amount(meta.kpi_buyer_paid));
                setText('kpiAovPembeli', fmtRp(toNum(meta.kpi_aov)));
                setHtml('kpiVoucherTotalLabel', 'Promo');
                setHtml('kpiVoucherTotalPercent', labelPct(voucher, gross));
                setHtml('kpiVoucherTotal', amount(voucher, '-'));
                setLabelWithPct('kpiVoucherTokoLabel', '<i class="bi bi-shop"></i>', 'Toko', meta.kpi_voucher_toko, gross);
                setHtml('kpiVoucherToko', amount(meta.kpi_voucher_toko, '-'));
                setLabelWithPct('kpiVoucherPlatformLabel', '<i class="bi bi-globe"></i>', 'Platform', meta.kpi_voucher_platform, gross);
                setHtml('kpiVoucherPlatform', amount(meta.kpi_voucher_platform, '-'));
                setHtml('kpiFeeLabel', 'Fee');
                setHtml('kpiFeePercent', labelPct(fees, gross));
                setHtml('kpiFeeTotal', amount(fees, '-'));
                setLabelWithPct('kpiAffiliateLabel', '<i class="bi bi-people"></i>', 'Aff.', meta.kpi_affiliate, gross);
                setHtml('kpiAffiliate', amount(meta.kpi_affiliate, '-'));
                setLabelWithPct('kpiMarketplaceLabel', '<i class="bi bi-shop"></i>', 'Mkt.', meta.kpi_marketplace, gross);
                setHtml('kpiMarketplace', amount(meta.kpi_marketplace, '-'));
                setLabelWithPct('kpiAdjustmentLabel', '<i class="bi bi-tools"></i>', 'Adj.', meta.kpi_adjustment_total, gross);
                setHtml('kpiAdjustment', amount(meta.kpi_adjustment_total, '-'));
                setHtml('kpiGrossProfitLabel', 'Profit');
                setHtml('kpiGrossProfitPercent', labelPct(grossProfit, gross));
                setHtml('kpiNetLabel', `${labelPct(net, gross)}<i class="bi bi-wallet2 text-primary"></i><span>Net</span>`);
                setHtml('kpiNetPayout', amount(net));
                setHtml('kpiCogsLabel', `${labelPct(cogs, gross)}<i class="bi bi-box-seam text-secondary"></i><span>COGS</span>`);
                setHtml('kpiCogs', amount(cogs, '-'));
                setHtml('kpiGrossProfit', `<span class="${grossProfit < 0 ? 'text-danger' : 'text-success'}">${amount(grossProfit)}</span>`);
                setHtml('kpiMargin', `<span style="font-size:.72rem; font-weight:900; color:${margin < 0 ? '#dc2626' : '#16a34a'}">${margin.toFixed(1)}%</span>`);
            },
            belum_cair: () => {
                hideLegacy();
                const subTab = currentSubTab || '';
                const total = Number(meta.kpi_count || 0);
                const shipped = Number(meta.kpi_count_shipped || 0);
                const toConfirm = Number(meta.kpi_count_to_confirm || 0);
                const returning = Number(meta.kpi_count_returning || 0);
                const unsettled = Number(meta.kpi_count_unsettled || 0);
                const cogsValue = Number(meta.kpi_cogs || 0);
                const netValue = Number(meta.kpi_net || 0);
                const grossValue = Number(meta.kpi_gross || 0);
                const totalBase = total || 1;

                const subTabMeta = {
                    '': {
                        note: `Ringkasan belum cair: <b>${total.toLocaleString('id-ID')}</b> total order, <b>${unsettled.toLocaleString('id-ID')}</b> belum cair, <b>${shipped.toLocaleString('id-ID')}</b> sudah dikirim, <b>${returning.toLocaleString('id-ID')}</b> return.`,
                        leftLabel: 'Total Order',
                        leftValue: total,
                        leftPct: labelPct(total, totalBase),
                        secondLabel: 'Belum Cair',
                        secondValue: unsettled,
                        secondPct: labelPct(unsettled, totalBase),
                        thirdLabel: 'Dikirim',
                        thirdValue: shipped,
                        thirdPct: labelPct(shipped, totalBase),
                        fourthLabel: 'Return',
                        fourthValue: returning,
                        fourthPct: labelPct(returning, totalBase),
                        summaryLabel: 'Konfirmasi',
                        summaryValue: toConfirm,
                        summaryPct: labelPct(toConfirm, totalBase),
                        grossLabel: 'Gross',
                        grossPct: labelPct(grossValue, grossValue || 1),
                        netLabel: 'Net Est.',
                        netValue: netValue,
                        netPct: labelPct(netValue, grossValue || 1),
                    },
                    shipped: {
                        note: `Sub-tab shipped: <b>${shipped.toLocaleString('id-ID')}</b> order siap cair, <b>${cogsValue.toLocaleString('id-ID')}</b> COGS, <b>${unsettled.toLocaleString('id-ID')}</b> pending.`,
                        leftLabel: 'Shipped',
                        leftValue: shipped,
                        leftPct: labelPct(shipped, totalBase),
                        secondLabel: 'Pending',
                        secondValue: unsettled,
                        secondPct: labelPct(unsettled, totalBase),
                        thirdLabel: 'COGS',
                        thirdValue: cogsValue,
                        thirdPct: labelPct(cogsValue, grossValue || 1),
                        fourthLabel: 'Return',
                        fourthValue: returning,
                        fourthPct: labelPct(returning, totalBase),
                        summaryLabel: 'Order',
                        summaryValue: total,
                        summaryPct: labelPct(total, totalBase),
                        grossLabel: 'Gross',
                        grossPct: labelPct(grossValue, grossValue || 1),
                        netLabel: 'Net Est.',
                        netValue: netValue,
                        netPct: labelPct(netValue, grossValue || 1),
                    },
                    to_confirm: {
                        note: `Sub-tab to confirm: <b>${toConfirm.toLocaleString('id-ID')}</b> order menunggu konfirmasi, <b>${cogsValue.toLocaleString('id-ID')}</b> COGS, <b>${unsettled.toLocaleString('id-ID')}</b> pending.`,
                        leftLabel: 'To Confirm',
                        leftValue: toConfirm,
                        leftPct: labelPct(toConfirm, totalBase),
                        secondLabel: 'Pending',
                        secondValue: unsettled,
                        secondPct: labelPct(unsettled, totalBase),
                        thirdLabel: 'Shipped',
                        thirdValue: shipped,
                        thirdPct: labelPct(shipped, totalBase),
                        fourthLabel: 'Return',
                        fourthValue: returning,
                        fourthPct: labelPct(returning, totalBase),
                        summaryLabel: 'Order',
                        summaryValue: total,
                        summaryPct: labelPct(total, totalBase),
                        grossLabel: 'Gross',
                        grossPct: labelPct(grossValue, grossValue || 1),
                        netLabel: 'Net Est.',
                        netValue: netValue,
                        netPct: labelPct(netValue, grossValue || 1),
                    },
                    returning: {
                        note: `Sub-tab return: <b>${returning.toLocaleString('id-ID')}</b> order return, <b>${cogsValue.toLocaleString('id-ID')}</b> COGS, <b>${unsettled.toLocaleString('id-ID')}</b> pending.`,
                        leftLabel: 'Return',
                        leftValue: returning,
                        leftPct: labelPct(returning, totalBase),
                        secondLabel: 'Pending',
                        secondValue: unsettled,
                        secondPct: labelPct(unsettled, totalBase),
                        thirdLabel: 'Shipped',
                        thirdValue: shipped,
                        thirdPct: labelPct(shipped, totalBase),
                        fourthLabel: 'To Confirm',
                        fourthValue: toConfirm,
                        fourthPct: labelPct(toConfirm, totalBase),
                        summaryLabel: 'Order',
                        summaryValue: total,
                        summaryPct: labelPct(total, totalBase),
                        grossLabel: 'Gross',
                        grossPct: labelPct(grossValue, grossValue || 1),
                        netLabel: 'Net Est.',
                        netValue: netValue,
                        netPct: labelPct(netValue, grossValue || 1),
                    },
                };

                const view = subTabMeta[subTab] || subTabMeta[''];
                renderInsightGrid([
                    { label: view.leftLabel, value: view.leftValue.toLocaleString('id-ID'), pct: view.leftPct, sub: 'Status utama pada sub-tab ini' },
                    { label: view.secondLabel, value: view.secondValue.toLocaleString('id-ID'), pct: view.secondPct, sub: 'Status pendamping' },
                    { label: view.thirdLabel, value: view.thirdValue.toLocaleString('id-ID'), pct: view.thirdPct, sub: 'Status penting lain' },
                    { label: view.fourthLabel, value: view.fourthValue.toLocaleString('id-ID'), pct: view.fourthPct, sub: 'Status tambahan' },
                    { label: 'Gross', value: fmtRp(grossValue), pct: view.grossPct, sub: 'Basis perhitungan' },
                    { label: 'Net Est.', value: fmtRp(netValue), pct: labelPct(netValue, grossValue || 1), sub: 'Estimasi masuk' },
                    { label: 'COGS', value: fmtRp(cogsValue, '-'), pct: labelPct(cogsValue, grossValue || 1), sub: 'Modal barang' },
                    { label: 'Pending', value: unsettled.toLocaleString('id-ID'), pct: labelPct(unsettled, totalBase), sub: 'Belum cair' },
                ]);
                setHtml('kpiSummaryNote', view.note);
                setText('kpiCount', view.leftValue.toLocaleString('id-ID'));
                setText('kpiCountSelesai', view.secondValue.toLocaleString('id-ID'));
                setText('kpiCountBatal', view.thirdValue.toLocaleString('id-ID'));
                setText('kpiCountPenyesuaian', view.fourthValue.toLocaleString('id-ID'));
                setHtml('kpiGrossLabel', view.leftLabel);
                setHtml('kpiGrossPercent', view.leftPct);
                setHtml('kpiGross', view.leftValue.toLocaleString('id-ID'));
                setLabelWithPct('kpiGrossBuyerPaidLabel', '<i class="bi bi-truck"></i>', view.secondLabel, view.secondValue, totalBase);
                setHtml('kpiGrossBuyerPaid', view.secondValue.toLocaleString('id-ID'));
                setText('kpiAovPembeli', fmtRp(toNum(meta.kpi_buyer_paid) / Math.max(totalBase, 1)));
                setHtml('kpiVoucherTotalLabel', view.summaryLabel);
                setHtml('kpiVoucherTotalPercent', view.summaryPct);
                setHtml('kpiVoucherTotal', view.summaryValue.toLocaleString('id-ID'));
                setLabelWithPct('kpiVoucherTokoLabel', '<i class="bi bi-check-circle"></i>', view.thirdLabel, view.thirdValue, totalBase);
                setHtml('kpiVoucherToko', view.thirdValue.toLocaleString('id-ID'));
                setLabelWithPct('kpiVoucherPlatformLabel', '<i class="bi bi-arrow-counterclockwise"></i>', view.fourthLabel, view.fourthValue, totalBase);
                setHtml('kpiVoucherPlatform', view.fourthValue.toLocaleString('id-ID'));
                setHtml('kpiFeeLabel', 'Gross');
                setHtml('kpiFeePercent', view.grossPct);
                setHtml('kpiFeeTotal', amount(grossValue));
                setLabelWithPct('kpiAffiliateLabel', '<i class="bi bi-wallet2"></i>', 'Net', netValue, grossValue || 1);
                setHtml('kpiAffiliate', amount(netValue));
                setLabelWithPct('kpiMarketplaceLabel', '<i class="bi bi-cash-coin"></i>', 'Net Est.', netValue, grossValue || 1);
                setHtml('kpiMarketplace', amount(netValue));
                setLabelWithPct('kpiAdjustmentLabel', '<i class="bi bi-percent"></i>', 'Margin', meta.kpi_gross_profit || 0, grossValue || 1);
                setHtml('kpiAdjustment', `<span style="font-size:.72rem; font-weight:900; color:${toNum(meta.kpi_gross_profit) < 0 ? '#dc2626' : '#16a34a'}">${grossValue > 0 ? ((toNum(meta.kpi_gross_profit) / grossValue) * 100).toFixed(1) : '0.0'}%</span>`);
                setHtml('kpiGrossProfitLabel', 'Est. Profit');
                setHtml('kpiGrossProfitPercent', labelPct(meta.kpi_gross_profit, grossValue || 1));
                setHtml('kpiNetLabel', `${view.netPct}<i class="bi bi-wallet2 text-primary"></i><span>${view.netLabel}</span>`);
                setHtml('kpiNetPayout', amount(netValue));
                setHtml('kpiCogsLabel', `${labelPct(cogsValue, grossValue || 1)}<i class="bi bi-box-seam text-secondary"></i><span>COGS</span>`);
                setHtml('kpiCogs', amount(cogsValue, '-'));
                setHtml('kpiGrossProfit', `<span class="${toNum(meta.kpi_gross_profit) < 0 ? 'text-danger' : 'text-success'}">${amount(meta.kpi_gross_profit)}</span>`);
                setHtml('kpiMargin', `<span style="font-size:.72rem; font-weight:900; color:${toNum(meta.kpi_gross_profit) < 0 ? '#dc2626' : '#16a34a'}">${grossValue > 0 ? ((toNum(meta.kpi_gross_profit) / grossValue) * 100).toFixed(1) : '0.0'}%</span>`);
            },
            batal_return: () => {
                hideLegacy();
                renderInsightGrid([
                    { label: 'Batal / Return', value: Number(meta.kpi_count_batal || 0).toLocaleString('id-ID'), pct: labelPct(meta.kpi_count_batal, meta.kpi_count || 1), sub: 'Order terdampak' },
                    { label: 'Adjustment', value: fmtRp(meta.kpi_adjustment_total || 0), pct: labelPct(meta.kpi_adjustment_total, gross || 1), sub: 'Nilai koreksi' },
                    { label: 'Refund', value: fmtRp(meta.kpi_adjustment_total || 0), pct: '-', sub: 'Estimasi refund' },
                    { label: 'COGS', value: fmtRp(cogs, '-'), pct: labelPct(cogs, gross || 1), sub: 'Modal yang tersisa' },
                    { label: 'Gross', value: fmtRp(gross), pct: labelPct(gross, gross || 1), sub: 'Basis awal' },
                    { label: 'Net', value: fmtRp(net), pct: labelPct(net, gross || 1), sub: 'Setelah dampak' },
                    { label: 'Margin', value: `${margin.toFixed(1)}%`, pct: '-', sub: 'Persentase profit' },
                    { label: 'Order Total', value: Number(meta.kpi_count || 0).toLocaleString('id-ID'), pct: labelPct(meta.kpi_count, meta.kpi_count || 1), sub: 'Total order di filter' },
                ]);
                setHtml('kpiSummaryNote', `Fokus risiko: <b>${Number(meta.kpi_count_batal || 0).toLocaleString('id-ID')}</b> order batal/return dengan dampak <b>${fmtRp(meta.kpi_adjustment_total || 0)}</b>.`);
                setText('kpiCount', meta.kpi_count_batal != null ? Number(meta.kpi_count_batal).toLocaleString('id-ID') : '-');
                setText('kpiCountSelesai', meta.kpi_count_penyesuaian != null ? Number(meta.kpi_count_penyesuaian).toLocaleString('id-ID') : '-');
                setText('kpiCountBatal', meta.kpi_count != null ? Number(meta.kpi_count).toLocaleString('id-ID') : '-');
                setText('kpiCountPenyesuaian', meta.kpi_adjustment_total != null ? fmtRp(toNum(meta.kpi_adjustment_total)) : '-');
                setHtml('kpiGrossLabel', 'Return / Batal');
                setHtml('kpiGrossPercent', labelPct(meta.kpi_count_batal, meta.kpi_count || 1));
                setHtml('kpiGross', Number(meta.kpi_count_batal || 0).toLocaleString('id-ID'));
                setLabelWithPct('kpiGrossBuyerPaidLabel', '<i class="bi bi-x-circle"></i>', 'Batal', meta.kpi_count_batal, meta.kpi_count || 1);
                setHtml('kpiGrossBuyerPaid', Number(meta.kpi_count_batal || 0).toLocaleString('id-ID'));
                setText('kpiAovPembeli', fmtRp(toNum(meta.kpi_adjustment_total)));
                setHtml('kpiVoucherTotalLabel', 'Adjustment');
                setHtml('kpiVoucherTotalPercent', labelPct(meta.kpi_adjustment_total, gross || 1));
                setHtml('kpiVoucherTotal', amount(meta.kpi_adjustment_total, '-'));
                setLabelWithPct('kpiVoucherTokoLabel', '<i class="bi bi-arrow-repeat"></i>', 'Refund', meta.kpi_adjustment_total, gross || 1);
                setHtml('kpiVoucherToko', amount(meta.kpi_adjustment_total, '-'));
                setLabelWithPct('kpiVoucherPlatformLabel', '<i class="bi bi-tools"></i>', 'Adj.', meta.kpi_count_penyesuaian, meta.kpi_count || 1);
                setHtml('kpiVoucherPlatform', Number(meta.kpi_count_penyesuaian || 0).toLocaleString('id-ID'));
                setHtml('kpiFeeLabel', 'Dampak');
                setHtml('kpiFeePercent', labelPct(meta.kpi_count_batal, meta.kpi_count || 1));
                setHtml('kpiFeeTotal', Number(meta.kpi_count_batal || 0).toLocaleString('id-ID'));
                setLabelWithPct('kpiAffiliateLabel', '<i class="bi bi-bag-x"></i>', 'Order', meta.kpi_count, meta.kpi_count || 1);
                setHtml('kpiAffiliate', Number(meta.kpi_count || 0).toLocaleString('id-ID'));
                setLabelWithPct('kpiMarketplaceLabel', '<i class="bi bi-receipt-cutoff"></i>', 'Fee', meta.kpi_fees, gross || 1);
                setHtml('kpiMarketplace', amount(meta.kpi_fees, '-'));
                setLabelWithPct('kpiAdjustmentLabel', '<i class="bi bi-slash-circle"></i>', 'Cancel', meta.kpi_count_batal, meta.kpi_count || 1);
                setHtml('kpiAdjustment', Number(meta.kpi_count_batal || 0).toLocaleString('id-ID'));
                setHtml('kpiGrossProfitLabel', 'Loss');
                setHtml('kpiGrossProfitPercent', labelPct(meta.kpi_count_batal, meta.kpi_count || 1));
                setHtml('kpiNetLabel', `${labelPct(meta.kpi_count_batal, meta.kpi_count || 1)}<i class="bi bi-wallet2 text-primary"></i><span>Lost</span>`);
                setHtml('kpiNetPayout', Number(meta.kpi_count_batal || 0).toLocaleString('id-ID'));
                setHtml('kpiCogsLabel', `${labelPct(meta.kpi_count_penyesuaian, meta.kpi_count || 1)}<i class="bi bi-box-seam text-secondary"></i><span>Adj</span>`);
                setHtml('kpiCogs', Number(meta.kpi_count_penyesuaian || 0).toLocaleString('id-ID'));
                setHtml('kpiGrossProfit', `<span class="text-danger">${Number(meta.kpi_count_batal || 0).toLocaleString('id-ID')}</span>`);
                setHtml('kpiMargin', `<span style="font-size:.72rem; font-weight:900; color:#dc2626">${(meta.kpi_count || 0) ? (((meta.kpi_count_batal || 0) / meta.kpi_count) * 100).toFixed(1) : '0.0'}%</span>`);
            },
            semua: () => {
                hideLegacy();
                const totalOrder = Number(meta.kpi_count || 0);
                const completed = Number(meta.kpi_count_selesai || 0);
                const pending = Number(meta.kpi_count_unsettled || 0);
                const cancelled = Number(meta.kpi_count_batal || 0);
                const adjustments = Number(meta.kpi_count_penyesuaian || 0);
                const completionRate = totalOrder > 0 ? (completed / totalOrder) * 100 : 0;
                const pendingRate = totalOrder > 0 ? (pending / totalOrder) * 100 : 0;
                const cancelRate = totalOrder > 0 ? (cancelled / totalOrder) * 100 : 0;
                const netRate = gross > 0 ? (net / gross) * 100 : 0;
                renderInsightGrid([
                    { label: 'Total Order', value: totalOrder.toLocaleString('id-ID'), pct: `${completionRate.toFixed(1)}% selesai`, sub: 'Semua order pada filter ini' },
                    { label: 'Selesai', value: completed.toLocaleString('id-ID'), pct: labelPct(completed, totalOrder || 1), sub: 'COMPLETED saja' },
                    { label: 'Belum Cair', value: pending.toLocaleString('id-ID'), pct: labelPct(pending, totalOrder || 1), sub: 'Masih pipeline' },
                    { label: 'Batal / Return', value: cancelled.toLocaleString('id-ID'), pct: labelPct(cancelled, totalOrder || 1), sub: 'Order bermasalah' },
                    { label: 'Gross', value: fmtRp(gross), pct: labelPct(gross, gross || 1), sub: 'Nilai penjualan' },
                    { label: 'Net', value: fmtRp(net), pct: `${netRate.toFixed(1)}%`, sub: 'Dana bersih' },
                    { label: 'Profit', value: fmtRp(grossProfit), pct: `${margin.toFixed(1)}%`, sub: 'Hasil akhir', color: grossProfit < 0 ? '#dc2626' : '#16a34a' },
                    { label: 'COGS', value: fmtRp(cogs, '-'), pct: labelPct(cogs, gross || 1), sub: 'Modal barang' },
                ]);

                setHtml('kpiSummaryNote', `Semua order: <b>${totalOrder.toLocaleString('id-ID')}</b> total, <b>${completed.toLocaleString('id-ID')}</b> selesai, <b>${pending.toLocaleString('id-ID')}</b> pending, <b>${cancelled.toLocaleString('id-ID')}</b> batal / return.`);
                setText('kpiCount', totalOrder.toLocaleString('id-ID'));
                setText('kpiCountSelesai', completed.toLocaleString('id-ID'));
                setText('kpiCountBatal', pending.toLocaleString('id-ID'));
                setText('kpiCountPenyesuaian', cancelled.toLocaleString('id-ID'));
                setHtml('kpiGrossLabel', 'Total Order');
                setHtml('kpiGrossPercent', `${completionRate.toFixed(1)}% selesai`);
                setHtml('kpiGross', totalOrder.toLocaleString('id-ID'));
                setLabelWithPct('kpiGrossBuyerPaidLabel', '<i class="bi bi-check2-all"></i>', 'Selesai', completed, totalOrder || 1);
                setHtml('kpiGrossBuyerPaid', completed.toLocaleString('id-ID'));
                setText('kpiAovPembeli', fmtRp(toNum(meta.kpi_aov || meta.kpi_buyer_paid) / Math.max(totalOrder || 1, 1)));
                setHtml('kpiVoucherTotalLabel', 'Pending');
                setHtml('kpiVoucherTotalPercent', `${pendingRate.toFixed(1)}%`);
                setHtml('kpiVoucherTotal', pending.toLocaleString('id-ID'));
                setLabelWithPct('kpiVoucherTokoLabel', '<i class="bi bi-x-octagon"></i>', 'Batal', cancelled, totalOrder || 1);
                setHtml('kpiVoucherToko', cancelled.toLocaleString('id-ID'));
                setLabelWithPct('kpiVoucherPlatformLabel', '<i class="bi bi-arrow-repeat"></i>', 'Adj.', adjustments, totalOrder || 1);
                setHtml('kpiVoucherPlatform', adjustments.toLocaleString('id-ID'));
                setHtml('kpiFeeLabel', 'Gross');
                setHtml('kpiFeePercent', gross > 0 ? `${((gross / Math.max(totalOrder, 1)) / Math.max(toNum(meta.kpi_aov || 1), 1) * 100).toFixed(1)}%` : '0.0%');
                setHtml('kpiFeeTotal', amount(gross));
                setLabelWithPct('kpiAffiliateLabel', '<i class="bi bi-wallet2"></i>', 'Net', net, gross || 1);
                setHtml('kpiAffiliate', amount(net));
                setLabelWithPct('kpiMarketplaceLabel', '<i class="bi bi-cash-coin"></i>', 'Cair', netRate, 100);
                setHtml('kpiMarketplace', `${netRate.toFixed(1)}%`);
                setLabelWithPct('kpiAdjustmentLabel', '<i class="bi bi-percent"></i>', 'Margin', grossProfit, gross || 1);
                setHtml('kpiAdjustment', `<span style="font-size:.72rem; font-weight:900; color:${margin < 0 ? '#dc2626' : '#16a34a'}">${margin.toFixed(1)}%</span>`);
                setHtml('kpiGrossProfitLabel', 'Profit');
                setHtml('kpiGrossProfitPercent', labelPct(grossProfit, gross || 1));
                setHtml('kpiNetLabel', `${labelPct(net, gross || 1)}<i class="bi bi-wallet2 text-primary"></i><span>Net</span>`);
                setHtml('kpiNetPayout', amount(net));
                setHtml('kpiCogsLabel', `${labelPct(cogs, gross || 1)}<i class="bi bi-box-seam text-secondary"></i><span>COGS</span>`);
                setHtml('kpiCogs', amount(cogs, '-'));
                setHtml('kpiGrossProfit', `<span class="${grossProfit < 0 ? 'text-danger' : 'text-success'}">${amount(grossProfit)}</span>`);
                setHtml('kpiMargin', `<span style="font-size:.72rem; font-weight:900; color:${margin < 0 ? '#dc2626' : '#16a34a'}">${margin.toFixed(1)}%</span>`);
            },
            produk: () => {
                hideLegacy();
                setHtml('summarySectionLabel', '<i class="bi bi-boxes"></i> Analisa Produk');
                const totalProducts = Number(meta.total_products || 0);
                const totalQty = Number(meta.total_qty || 0);
                const totalOrders = Number(meta.total_order_count || 0);
                const totalNetToko = Number(meta.total_sales_after_voucher_toko || 0);
                const totalNetCair = Number(meta.total_income_cair || 0);
                const totalNetBelumCair = Number(meta.total_income_belum_cair || 0);
                const totalBuyerPaid = Number(meta.total_buyer_paid || 0);
                const totalCogs = Number(meta.total_cogs || 0);
                const totalCogsQty = Number(meta.total_cogs_qty || 0);
                const totalProfit = Number(meta.total_profit || 0);
                const totalProfitCair = Number(meta.total_profit_cair || 0);
                const totalProfitBelumCair = Number(meta.total_profit_belum_cair || 0);
                const avgProfitPerOrder = Number(meta.avg_profit_per_order || (totalOrders > 0 ? totalProfit / totalOrders : 0));
                const settledOrderCount = Number(meta.total_settled_order_count || 0);
                const unsettledOrderCount = Number(meta.total_unsettled_order_count || 0);
                const totalQtyCair = Number(meta.total_qty_cair || 0);
                const totalQtyBelumCair = Number(meta.total_qty_belum_cair || 0);
                const avgNetToko = Number(meta.avg_sales_after_voucher_toko_satuan || (totalQty > 0 ? totalNetToko / totalQty : 0));
                const avgNetCair = Number(totalQty > 0 ? totalNetCair / totalQty : 0);
                const avgNetBelumCair = Number(totalQty > 0 ? totalNetBelumCair / totalQty : 0);
                const avgBuyerPaid = Number(meta.avg_buyer_paid_satuan || (totalQty > 0 ? totalBuyerPaid / totalQty : 0));
                const avgCogs = Number(meta.avg_cogs_satuan || (totalQty > 0 ? totalCogs / totalQty : 0));
                const avgProfit = Number(totalQty > 0 ? totalProfit / totalQty : 0);
                const profitMargin = totalNetToko > 0 ? (totalProfit / totalNetToko) * 100 : 0;
                const mappedRate = Number(meta.sku_map_rate || 0);

                renderInsightGrid([
                    { label: 'Produk', value: totalProducts.toLocaleString('id-ID'), pct: `${mappedRate.toFixed(1)}% map`, sub: 'SKU yang masuk analisa' },
                    { label: 'Qty', value: totalQty.toLocaleString('id-ID'), pct: totalProducts > 0 ? (totalQty / totalProducts).toFixed(1) : '0.0', sub: 'Total item terjual' },
                    { label: 'Order', value: totalOrders.toLocaleString('id-ID'), pct: totalOrders > 0 ? (totalQty / totalOrders).toFixed(1) : '0.0', sub: 'Order penyumbang SKU' },
                    { label: 'Net', value: fmtRp(totalNetToko), pct: `avg ${fmtRp(avgNetToko)}`, sub: 'Setelah voucher toko', color: totalNetToko < 0 ? '#dc2626' : '#2563eb' },
                    { label: 'Net Cair', value: fmtRp(totalNetCair), pct: `${settledOrderCount.toLocaleString('id-ID')} order`, sub: `qty ${totalQtyCair.toLocaleString('id-ID')} • estimasi bila kosong • avg ${fmtRp(avgNetCair)}`, color: totalNetCair < 0 ? '#dc2626' : '#16a34a' },
                    { label: 'Net Belum Cair', value: fmtRp(totalNetBelumCair), pct: `${unsettledOrderCount.toLocaleString('id-ID')} order`, sub: `qty ${totalQtyBelumCair.toLocaleString('id-ID')} • avg ${fmtRp(avgNetBelumCair)}`, color: totalNetBelumCair < 0 ? '#dc2626' : '#f59e0b' },
                    { label: 'Pembayaran Pembeli', value: fmtRp(totalBuyerPaid), pct: `avg ${fmtRp(avgBuyerPaid)}`, sub: 'Bayar pembeli', color: '#0f766e' },
                    { label: 'COGS', value: fmtRp(totalCogs, '-'), pct: `${totalCogsQty.toLocaleString('id-ID')} qty`, sub: `avg ${fmtRp(avgCogs)} • modal barang`, color: '#b45309' },
                    { label: 'Profit', value: fmtRp(totalProfit), pct: `${profitMargin.toFixed(1)}%`, sub: `${totalOrders.toLocaleString('id-ID')} order • avg ${fmtRp(avgProfit)}`, color: totalProfit < 0 ? '#dc2626' : '#16a34a' },
                    { label: 'Profit / Order', value: fmtRp(avgProfitPerOrder), pct: `${totalOrders.toLocaleString('id-ID')} order`, sub: 'Rata-rata profit per order', color: avgProfitPerOrder < 0 ? '#dc2626' : '#16a34a' },
                    { label: 'Profit Cair', value: fmtRp(totalProfitCair), pct: `${settledOrderCount.toLocaleString('id-ID')} order`, sub: 'Profit order cair', color: totalProfitCair < 0 ? '#dc2626' : '#16a34a' },
                    { label: 'Profit Belum Cair', value: fmtRp(totalProfitBelumCair), pct: `${unsettledOrderCount.toLocaleString('id-ID')} order`, sub: 'Profit order pending', color: totalProfitBelumCair < 0 ? '#dc2626' : '#f59e0b' },
                ]);
                setHtml('kpiSummaryNote', `Analisa produk: <b>${totalProducts.toLocaleString('id-ID')}</b> produk, <b>${totalQty.toLocaleString('id-ID')}</b> qty, <b>${settledOrderCount.toLocaleString('id-ID')}</b> order cair, <b>${unsettledOrderCount.toLocaleString('id-ID')}</b> order belum cair, net cair <b>${fmtRp(totalNetCair)}</b>, net belum cair <b>${fmtRp(totalNetBelumCair)}</b>, profit / order <b>${fmtRp(avgProfitPerOrder)}</b>, qty COGS <b>${totalCogsQty.toLocaleString('id-ID')}</b>.`);
            }
        };

        resetCards();
        (renderMode[currentTab] || renderMode.cair)();
    }

    function categoryTone(category) {
        if (category === 'buyer') return { color: '#0f766e', sign: '' };
        if (category === 'platform') return { color: '#15803d', sign: '' };
        if (category === 'voucher') return { color: '#b91c1c', sign: '-' };
        if (category === 'adjustment') return { color: '#b91c1c', sign: '-' };
        return { color: '#b91c1c', sign: '-' };
    }

    function categoryTitle(category) {
        if (category === 'buyer') return 'Beban Pembeli';
        if (category === 'platform') return 'Beban Platform';
        if (category === 'voucher') return 'Voucher';
        if (category === 'adjustment') return 'Penyesuaian';
        return 'Beban Seller';
    }

    function renderFeeLine(label, value, force = false, percent = null, category = 'seller') {
        const n = Number(value || 0);
        if (!force && !n) return '';
        const tone = categoryTone(category);
        const sign = tone.sign || (n < 0 ? '-' : '');
        const pct = percent !== null && percent !== undefined
            ? ` <span style="font-size:.66rem;color:var(--shp-muted);">(${Number(percent).toFixed(1)}%)</span>`
            : '';
        return `<li><span>${esc(label)}:</span><span class="income-fee-val" style="color:${tone.color};">${sign}${fmtRp(Math.abs(n))}${pct}</span></li>`;
    }

    function fmtOptionalRp(value) {
        const n = Number(value || 0);
        return n > 0 ? fmtRp(Math.abs(n)) : '—';
    }

    function fmtPct(value) {
        const n = Number(value);
        return Number.isFinite(n) ? `${n.toFixed(1)}%` : '-';
    }

    function getOrderStatusMeta(status) {
        const st = String(status || '').toUpperCase();
        if (st === 'COMPLETED') return { label: 'Selesai', cls: 'success', icon: 'bi-check-circle-fill' };
        if (['READY_TO_SHIP', 'PROCESSED', 'MATCHED', 'PACKED', 'DIKEMAS'].includes(st)) {
            return { label: 'Dikemas', cls: 'warning', icon: 'bi-box-seam' };
        }
        if (st === 'SHIPPED' || st === 'DIKIRIM') return { label: 'Dikirim', cls: 'primary', icon: 'bi-truck' };
        if (st === 'CANCELLED' || st === 'BATAL' || st === 'RETURNED' || st === 'REFUND') return { label: 'Batal / Return', cls: 'danger', icon: 'bi-x-circle-fill' };
        if (st === 'TO_RETURN' || st === 'RETURNING') return { label: 'Return', cls: 'warning', icon: 'bi-arrow-return-left' };
        if (st) return { label: st.replace(/_/g, ' '), cls: 'neutral', icon: 'bi-info-circle' };
        return { label: 'Unknown', cls: 'neutral', icon: 'bi-info-circle' };
    }

    window.toggleProductGroup = function (groupId) {
        const safeId = (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') ? CSS.escape(groupId) : groupId;
        const rows = document.querySelectorAll(`[data-product-group="${safeId}"]`);
        const icon = document.querySelector(`[data-product-toggle-icon="${safeId}"]`);
        const isOpen = rows.length > 0 && rows[0].style.display !== 'none';
        rows.forEach((row) => {
            row.style.display = isOpen ? 'none' : '';
        });
        if (icon) {
            icon.className = isOpen ? 'bi bi-chevron-down ms-1' : 'bi bi-chevron-up ms-1';
        }
    };

    function slugifyGroupKey(value) {
        return String(value || '-')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'group';
    }

    function updateIncomeTableMeta() {
        const el = $('incomeTableMeta');
        if (!el) return;

        const tabLabels = {
            cair: 'Dana Cair',
            belum_cair: 'Belum Cair',
            batal_return: 'Batal / Return',
            semua: 'Semua Order',
            produk: 'Produk',
        };
        const subLabels = {
            '': 'Semua',
            packed: 'Sedang Dikemas',
            shipped: 'Sedang Dikirim',
            to_confirm: 'Menunggu Konfirmasi',
            returning: 'Sedang Dikembalikan',
            return: 'Return',
        };

        const tabLabel = tabLabels[currentTab] || 'Semua Order';
        const subLabel = currentTab === 'belum_cair' && currentSubTab ? ` • ${subLabels[currentSubTab] || currentSubTab}` : '';
        if (!paginationData) {
            el.textContent = `${tabLabel}${subLabel} • Memuat data…`;
            return;
        }

        const total = Number(paginationData.total || 0).toLocaleString('id-ID');
        const from = paginationData.from || 0;
        const to = paginationData.to || 0;
        const page = paginationData.current_page || 1;
        const lastPage = paginationData.last_page || 1;
        el.textContent = `${tabLabel}${subLabel} • ${total} order • Menampilkan ${from} - ${to} • Hal ${page}/${lastPage}`;
    }

    function renderBreakdownSummary(s) {
        const gross = Number(s.gross_amount ?? s.buyer_payment_amount ?? 0);
        const buyerPaid = Number(s.buyer_paid_amount ?? s.buyer_payment_amount ?? 0);
        const voucherPlatform = Number(s.voucher_platform_total ?? 0);
        const voucherToko = Number(s.voucher_toko_total ?? s.seller_voucher ?? 0);
        const voucherTotal = Number(s.voucher_total ?? (voucherPlatform + voucherToko));
        const feeTotal = Number(s.seller_burden_total ?? s.fee_breakdown_total ?? s.fee_total ?? 0);
        const net = Number(s.final_income || 0);
        const grossAfterVoucher = Number(s.gross_after_voucher_toko || Math.max(gross - voucherToko, 0));

        return `
            <div class="income-breakdown-summary">
                <div class="income-breakdown-pill"><span class="label">Pembayaran Pembeli</span><span class="value">${fmtRp(buyerPaid)}</span></div>
                <div class="income-breakdown-pill"><span class="label">Gross Sales</span><span class="value">${fmtRp(gross)}</span></div>
                <div class="income-breakdown-pill"><span class="label">Voucher Platform</span><span class="value" style="color:#b91c1c">-${fmtRp(voucherPlatform)}</span></div>
                <div class="income-breakdown-pill"><span class="label">Voucher Toko</span><span class="value" style="color:#b91c1c">-${fmtRp(voucherToko)}</span></div>
                <div class="income-breakdown-pill"><span class="label">Gross Setelah Voucher Toko</span><span class="value">${fmtRp(grossAfterVoucher)}</span></div>
                <div class="income-breakdown-pill"><span class="label">Beban Seller</span><span class="value" style="color:#b91c1c">-${fmtRp(feeTotal)}</span></div>
                <div class="income-breakdown-pill"><span class="label">Total Voucher</span><span class="value" style="color:#b91c1c">-${fmtRp(voucherTotal)}</span></div>
                <div class="income-breakdown-pill"><span class="label">Dana Cair</span><span class="value" style="color:#16a34a">${fmtRp(net)}</span></div>
            </div>
        `;
    }

    function fmtShortDate(d) {
        if (!d) return '—';
        const dt = new Date(d);
        if (Number.isNaN(dt.getTime())) return '—';
        return dt.toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function renderBreakdownModalContent(s) {
        const feePercent = s.fee_percent !== undefined && s.fee_percent !== null ? Number(s.fee_percent) : null;
        const voucherPlatform = Number(s.voucher_platform_total ?? 0);
        const voucherToko = Number(s.voucher_toko_total ?? s.seller_voucher ?? 0);
        const grossAfterVoucher = Number(s.gross_after_voucher_toko || Math.max(Number(s.gross_amount ?? s.buyer_payment_amount ?? 0) - voucherToko, 0));

        return `
            ${renderBreakdownSummary(s)}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="small text-muted">Detail per kategori</div>
                <div class="small text-muted">${Number.isFinite(feePercent) ? feePercent.toFixed(1) + '% dari gross setelah voucher total' : '-'}</div>
            </div>
            <div class="mb-3 small text-muted">Gross setelah voucher toko: <strong>${fmtRp(grossAfterVoucher)}</strong></div>
            <ul class="income-fee-list">${feeBreakdownList(s)}</ul>
        `;
    }

    window.openBreakdownModal = function (index) {
        const s = rows[index];
        if (!s) return;

        const title = $('incomeBreakdownModalLabel');
        const sub = $('incomeBreakdownModalSub');
        const body = $('incomeBreakdownModalBody');

        title.textContent = `${s.is_booking_only ? 'Booking' : 'Rincian Potongan'} • ${s.booking_sn || s.channel_order_id || '-'}`;
        sub.textContent = `${s.store?.name || '-'} • ${s.order?.order_status || s.order_status || '-'}`;
        body.innerHTML = s.is_booking_only
            ? '<div style="padding:1.5rem;text-align:center;color:#a16207;"><i class="bi bi-lightning-charge-fill" style="font-size:1.4rem;"></i><div style="font-weight:800;margin-top:.5rem;">Belum ada data penghasilan</div><div style="font-size:.75rem;color:#64748b;margin-top:.25rem;">Booking belum terhubung ke No. Pesanan dan belum memiliki settlement Shopee.</div></div>'
            : renderBreakdownModalContent(s);

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('incomeBreakdownModal'));
        modal.show();
    };

    function feeBreakdownList(s) {
        const breakdown = Array.isArray(s.fee_breakdown) ? s.fee_breakdown : [];
        const feeTotal = Number(s.seller_burden_total ?? s.fee_breakdown_total ?? s.fee_total ?? 0);
        const buyerTotal = Number(s.buyer_burden_total ?? 0);
        const platformTotal = Number(s.platform_burden_total ?? 0);
        const voucherPlatformTotal = Number(s.voucher_platform_total ?? 0);
        const voucherTokoTotal = Number(s.voucher_toko_total ?? s.seller_voucher ?? 0);
        const voucherTotal = Number(s.voucher_total ?? (voucherPlatformTotal + voucherTokoTotal));
        const adjustmentTotal = Number(s.adjustment_total ?? 0);
        const grandTotal = Number(s.total_burden_total ?? (feeTotal + buyerTotal + platformTotal + voucherTotal + adjustmentTotal));
        const feePercent = s.fee_percent !== undefined && s.fee_percent !== null ? Number(s.fee_percent) : null;
        const grouped = breakdown.reduce((acc, item) => {
            const cat = item.category || 'seller';
            if (!acc[cat]) acc[cat] = [];
            acc[cat].push(item);
            return acc;
        }, {});

        const renderSection = (category, total, items) => {
            if (!items.length && !total) return '';
            const tone = categoryTone(category);
            const sign = tone.sign || '';
            let html = `<li class="income-sect" style="color:${tone.color};">${esc(categoryTitle(category))}<span style="float:right;color:${tone.color};">${sign}${fmtRp(Math.abs(total))}</span></li>`;
            if (items.length) {
                items.forEach(item => {
                    html += renderFeeLine(item.label || 'Biaya', item.amount ?? 0, true, null, item.category || category);
                });
            } else {
                html += '<li><span style="color:var(--shp-muted)">Tidak ada rincian</span></li>';
            }
            return html;
        };

        if (breakdown.length) {
            let html = '';
            html += renderFeeLine('Total Beban Seller', feeTotal, true, feePercent, 'seller');
            html += renderFeeLine('Total Voucher', voucherTotal, true, null, 'voucher');
            html += renderFeeLine('Total Beban Pembeli', buyerTotal, true, null, 'buyer');
            html += renderFeeLine('Total Beban Platform', platformTotal, true, null, 'platform');
            html += renderFeeLine('Total Penyesuaian', adjustmentTotal, true, null, 'adjustment');
            html += renderSection('seller', feeTotal, grouped.seller || []);
            html += renderSection('voucher', voucherTotal, grouped.voucher || []);
            html += renderSection('buyer', buyerTotal, grouped.buyer || []);
            html += renderSection('platform', platformTotal, grouped.platform || []);
            html += renderSection('adjustment', adjustmentTotal, grouped.adjustment || []);
            return html || '<li><span style="color:var(--shp-muted)">Tidak ada potongan</span></li>';
        }

        let html = '';
        html += renderFeeLine('Total Beban Seller', feeTotal, true, feePercent, 'seller');
        html += renderFeeLine('Total Voucher', voucherTotal, true, null, 'voucher');
        html += renderFeeLine('Total Beban Pembeli', buyerTotal, true, null, 'buyer');
        html += renderFeeLine('Total Beban Platform', platformTotal, true, null, 'platform');
        html += renderFeeLine('Total Penyesuaian', adjustmentTotal, true, null, 'adjustment');
        html += '<li class="income-sect">Komisi & Admin</li>';
        html += renderFeeLine('Biaya Administrasi', s.commission_fee);
        html += renderFeeLine('Biaya Layanan', s.service_fee);
        html += renderFeeLine('Biaya Proses Pesanan', s.seller_order_processing_fee || s.transaction_fee);
        html += renderFeeLine('Biaya Transaksi', s.seller_transaction_fee);

        html += '<li class="income-sect">Promo & Affiliate</li>';
        html += renderFeeLine('Premi', s.premi, true);
        html += renderFeeLine('Biaya Komisi AMS', s.activity_fee);
        html += renderFeeLine('Biaya Affiliate', s.biaya_affiliate ?? s.affiliate_commission_fee ?? s.seller_affiliate_fee, true);
        const affiliateCommissionValue = Number(s.affiliate_display ?? s.affiliate ?? s.affiliate_fee ?? s.affiliate_commission_fee ?? s.seller_affiliate_fee ?? s.activity_fee ?? 0);
        html += affiliateCommissionValue > 0
            ? renderFeeLine('Komisi Affiliate', affiliateCommissionValue, true)
            : '<li><span>Komisi Affiliate:</span><span class="income-fee-val" style="color:#64748b;">—</span></li>';

        html += '<li class="income-sect">Asuransi & Pajak</li>';
        html += renderFeeLine('Biaya Asuransi Pengiriman', s.shipping_insurance_fee, true);
        html += renderFeeLine('Pajak (Escrow)', s.escrow_tax);

        html += '<li class="income-sect">Biaya Iklan</li>';
        html += renderFeeLine('Biaya Iklan', s.ad_cost);
        html += '<li class="income-sect">Voucher</li>';
        html += renderFeeLine('Voucher Platform', s.voucher_platform_total);
        html += renderFeeLine('Voucher Toko', s.voucher_toko_total ?? s.seller_voucher, true, null, 'voucher');

        html += '<li class="income-sect">Penyesuaian</li>';
        html += renderFeeLine('Refund / Adjustment', s.adjustment_total ?? s.drc_adjustable_refund, true, null, 'adjustment');
        return html || '<li><span style="color:var(--shp-muted)">Tidak ada potongan</span></li>';
    }

    function renderTable() {
        if (currentTab === 'produk') {
            return renderProductTable();
        }
        const body = $('incomeBody');
        updateIncomeTableMeta();
        if (!rows || rows.length === 0) {
            if ($('incomeTableMeta')) $('incomeTableMeta').textContent = 'Tidak ada data untuk filter ini';
            body.innerHTML = `
                <div style="padding:4rem 2rem;text-align:center;color:var(--shp-muted);">
                    <div style="width:72px;height:72px;margin:0 auto 1rem;border-radius:22px;background:rgba(148,163,184,.14);display:flex;align-items:center;justify-content:center;color:#64748b;">
                        <i class="bi bi-inbox fs-2"></i>
                    </div>
                    <h6 style="font-weight:900;color:var(--shp-text);margin-bottom:.35rem;">Tidak ada data untuk filter ini</h6>
                    <div style="font-size:.8rem;max-width:28rem;margin:0 auto 1rem;">Coba longgarkan filter tab, rentang tanggal, atau status mapping untuk melihat data lain.</div>
                    <div style="margin-top:12px;">
                        <button class="btn btn-sm btn-light border" style="border-radius:999px;font-size:.78rem;font-weight:800;" onclick="resetFilters()">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset filter
                        </button>
                    </div>
                </div>`;
            return;
        }

        const html = `
        <div class="income-table-shell" style="max-height:68vh; overflow-y:auto; overflow-x:hidden;">
        <table class="table income-table income-order-table w-100 mb-0">
            <thead>
                <tr>
                    <th style="min-width:360px; cursor:pointer;" onclick="toggleSort('ordered_at')" class="hover-sort">
                        Order, Toko &amp; Tanggal <i class="bi bi-arrow-down-up ms-1" id="sortIcon_ordered_at" style="opacity:0.4;"></i>
                    </th>
                    <th style="min-width:250px; cursor:pointer;" onclick="toggleSort('buyer_payment_amount')" class="hover-sort">
                        Pemasukan <i class="bi bi-arrow-down-up ms-1" id="sortIcon_buyer_payment_amount" style="opacity:0.4;"></i>
                    </th>
                    <th style="min-width:220px;">Potongan &amp; Promo</th>
                    <th style="min-width:220px; cursor:pointer;" onclick="toggleSort('final_income')" class="hover-sort">
                        Hasil Akhir <i class="bi bi-arrow-down-up ms-1" id="sortIcon_final_income" style="opacity:0.4;"></i>
                    </th>
                    <th class="text-center" style="min-width:110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            ${rows.map((s, idx) => {
                const gross = Number(s.gross_amount ?? s.buyer_payment_amount ?? 0);
                const buyerPaid = Number(s.buyer_paid_amount ?? s.buyer_payment_amount ?? 0);
                const voucherPlatform = Number(s.voucher_platform_total ?? 0);
                const voucherToko = Number(s.voucher_toko_total ?? s.seller_voucher ?? 0);
                const grossAfterVoucherToko = Number(s.gross_after_voucher_toko || Math.max(gross - voucherToko, 0));
                const affiliateCommission = Number(s.affiliate_display ?? s.affiliate ?? s.affiliate_fee ?? s.affiliate_commission_fee ?? s.seller_affiliate_fee ?? s.activity_fee ?? s.biaya_affiliate ?? 0);
                const affiliateCommissionPercent = Number.isFinite(Number(s.affiliate_percent))
                    ? Number(s.affiliate_percent)
                    : (grossAfterVoucherToko > 0 ? (affiliateCommission / grossAfterVoucherToko) * 100 : 0);
                const affiliateCommissionText = affiliateCommission > 0 ? `-${fmtOptionalRp(affiliateCommission)}` : '—';
                let marketplaceFeeAfterAffiliate = Number(s.marketplace_fee_after_affiliate ?? Math.max((Number(s.seller_burden_total ?? s.fee_breakdown_total ?? s.fee_total ?? 0)) - affiliateCommission, 0));
                let marketplaceFeePercent = Number.isFinite(Number(s.marketplace_fee_percent))
                    ? Number(s.marketplace_fee_percent)
                    : (grossAfterVoucherToko > 0 ? (marketplaceFeeAfterAffiliate / grossAfterVoucherToko) * 100 : 0);
                const voucherTotal = Number(s.voucher_total ?? (voucherPlatform + voucherToko));
                let sellerBurdenTotal = Number(s.seller_burden_total ?? s.fee_breakdown_total ?? s.fee_total ?? 0);
                const adjustmentTotal = Number(s.adjustment_total ?? 0);
                let feePercent = s.fee_percent !== undefined && s.fee_percent !== null ? Number(s.fee_percent) : null;
                let net = Number(s.final_income || 0);
                const hasEstimatedEscrowAmount = s.estimated_escrow_amount !== null
                    && s.estimated_escrow_amount !== undefined
                    && s.estimated_escrow_amount !== '';
                const estimatedEscrowAmount = hasEstimatedEscrowAmount ? Number(s.estimated_escrow_amount) : 0;
                const isBookingOnly = s.is_booking_only === true;
                const bookingSn = s.booking_sn || s.order?.booking_sn || '';
                const displayOrderId = isBookingOnly ? (bookingSn || s.channel_order_id || '-') : (s.channel_order_id || '-');
                const orderLink = !isBookingOnly && s.order?.id
                    ? `<a href="/marketplace/orders/${s.order.id}" class="income-row-title" title="${esc(displayOrderId)}">${esc(displayOrderId)}</a>`
                    : `<span class="income-row-title" title="${isBookingOnly ? 'No. Booking — menunggu No. Pesanan' : displayOrderId}">${esc(displayOrderId)}</span>`;
                const bookingMeta = bookingSn && !isBookingOnly
                    ? `<div class="income-row-sub" title="No. Booking"><i class="bi bi-lightning-charge-fill" style="font-size:0.55rem;color:#a16207;"></i><span class="text-truncate" style="color:#a16207;font-weight:800;">No. Booking: ${esc(bookingSn)}</span></div>`
                    : '';
                const statusMeta = isBookingOnly
                    ? { label: 'Booking • Belum Match', cls: 'warning', icon: 'bi-lightning-charge-fill' }
                    : getOrderStatusMeta(s.order?.order_status || s.order_status);
                const orderDateText = s.order?.ordered_at ? fmtShortDate(s.order.ordered_at) : '—';
                const settlementDateText = s.settlement_time
                    ? fmtShortDate(s.settlement_time)
                    : (s.estimated_payout_at ? `Est. ${fmtShortDate(s.estimated_payout_at)}` : 'Belum cair');
                const itemCount = Array.isArray(s.order?.items) ? s.order.items.length : 0;
                const grossPercent = gross > 0 ? ((grossAfterVoucherToko / gross) * 100).toFixed(1) : '0.0';
                const buyerPercent = gross > 0 ? ((buyerPaid / gross) * 100).toFixed(1) : '0.0';
                let netCairValue = Number(s.final_income || 0);
                let finalPercent = gross > 0 ? ((netCairValue / gross) * 100).toFixed(1) : '0.0';
                const cogsValue = Number(s.cogs || 0);
                const cogsPercent = gross > 0 ? ((cogsValue / gross) * 100).toFixed(1) : '0.0';
                const profitValue = isBookingOnly ? 0 : Number(s.gross_profit || 0);
                const profitPercent = gross > 0 ? ((Math.abs(profitValue) / gross) * 100).toFixed(1) : '0.0';

                const isCompleted = s.order?.order_status === 'COMPLETED';
                const isCancelledOrReturned = s.order?.order_status === 'CANCELLED' || s.order?.order_status === 'BATAL' || s.order?.order_status === 'RETURNED' || s.order?.order_status === 'REFUND';
                const isReturning = s.order?.order_status === 'TO_RETURN' || s.order?.order_status === 'RETURNING';

                const shouldEstimatePendingIncome = !isBookingOnly && !s.settlement_time;
                const incomeEstimationSource = isBookingOnly ? null : (s.income_estimation_source
                    || (s.is_estimated_income ? (hasEstimatedEscrowAmount ? 'estimated_escrow' : 'manual_24') : null));
                const incomeSourceLabel = incomeEstimationSource === 'estimated_escrow'
                    ? 'EST. SHOPEE'
                    : (incomeEstimationSource === 'manual_24'
                        ? 'EST. MANUAL 24%'
                        : (isBookingOnly ? 'BELUM TERSEDIA' : (s.settlement_time ? 'CAIR • FINAL' : 'ESTIMASI BELUM TERSEDIA')));
                const incomeSourceTitle = incomeEstimationSource === 'estimated_escrow'
                    ? 'Estimasi dari estimated_escrow_amount Shopee'
                    : (incomeEstimationSource === 'manual_24'
                        ? 'Estimasi fallback manual 24%'
                        : (isBookingOnly ? 'Booking belum terhubung ke No. Pesanan dan belum memiliki data settlement' : (s.settlement_time ? 'Nilai pencairan final' : 'Income detail Shopee belum tersedia')));
                const incomeIsPending = !s.settlement_time;
                const estimateFreshness = s.income_estimate_synced_at
                    ? `Diperbarui ${relativeTime(s.income_estimate_synced_at)}`
                    : (isBookingOnly ? 'Menunggu No. Pesanan / data settlement Shopee' : (incomeEstimationSource === 'manual_24' ? 'Fallback lokal' : (incomeIsPending ? 'Menunggu data Shopee' : '')));

                if (isBookingOnly) {
                    net = 0;
                    s.final_income = 0;
                    s.cogs = 0;
                    s.gross_profit = 0;
                } else if (isCancelledOrReturned) {
                    net = 0;
                    s.final_income = 0;
                    s.cogs = 0;
                    s.gross_profit = 0;
                } else if (isReturning) {
                    net = 0;
                    s.final_income = 0;
                    s.gross_profit = 0 - (Number(s.cogs) || 0);
                } else if (!s.settlement_time && shouldEstimatePendingIncome) {
                    if (hasEstimatedEscrowAmount && Number.isFinite(estimatedEscrowAmount)) {
                        // Nilai pending dari Shopee menjadi sumber utama.
                        net = estimatedEscrowAmount;
                        const estimatedSellerBurden = Math.max(grossAfterVoucherToko - net, 0);
                        marketplaceFeeAfterAffiliate = Math.max(estimatedSellerBurden - affiliateCommission, 0);
                        sellerBurdenTotal = estimatedSellerBurden;
                        marketplaceFeePercent = grossAfterVoucherToko > 0 ? (marketplaceFeeAfterAffiliate / grossAfterVoucherToko) * 100 : 0;
                        feePercent = grossAfterVoucherToko > 0 ? (sellerBurdenTotal / grossAfterVoucherToko) * 100 : 0;
                    } else {
                        // Fallback sementara bila income detail belum tersedia.
                        marketplaceFeePercent = 24.0;
                        marketplaceFeeAfterAffiliate = Math.round(grossAfterVoucherToko * 0.24);
                        sellerBurdenTotal = marketplaceFeeAfterAffiliate + affiliateCommission;
                        feePercent = grossAfterVoucherToko > 0 ? (sellerBurdenTotal / grossAfterVoucherToko) * 100 : 0;
                        net = Math.max(grossAfterVoucherToko - sellerBurdenTotal, 0);
                    }
                    s.final_income = net;
                    netCairValue = net;
                    finalPercent = gross > 0 ? ((netCairValue / gross) * 100).toFixed(1) : '0.0';
                    s.gross_profit = net - (Number(s.cogs) || 0);
                } else if (!isCompleted && currentTab === 'semua') {
                    net = Number(s.final_income || 0);
                    s.final_income = net;
                    s.gross_profit = net - (Number(s.cogs) || 0);
                }

                return `<tr style="border-bottom:1px solid #e2e8f0; background-color: ${idx % 2 === 0 ? 'rgba(248, 250, 252, 0.7)' : '#ffffff'};">
                    <td>
                        <div class="income-row-stack">
                            <div class="income-row-top">
                                <div class="income-row-main">
                                    <i class="bi bi-box-seam text-muted" style="font-size:0.62rem;"></i>
                                    ${orderLink}
                                </div>
                                <span class="income-chip ${statusMeta.cls}">
                                    <i class="bi ${statusMeta.icon}"></i>${esc(statusMeta.label)}
                                </span>
                            </div>
                            <div class="income-row-sub">
                                <i class="bi bi-shop" style="font-size:0.55rem;"></i>
                                <span class="fw-bold text-truncate" style="letter-spacing:-0.01em;">${esc(s.store?.name || '-').toUpperCase()}</span>
                            </div>
                            ${bookingMeta}
                            ${isBookingOnly ? '<div style="font-size:.62rem;color:#a16207;font-weight:800;margin-top:.2rem;"><i class="bi bi-info-circle me-1"></i>Menunggu No. Pesanan dan data settlement Shopee</div>' : ''}
                            <div class="income-row-badges">
                                <span class="income-chip neutral"><i class="bi bi-box"></i>${itemCount.toLocaleString('id-ID')} item</span>
                                <span class="income-chip neutral" title="Metode pembayaran"><i class="bi bi-credit-card"></i>${esc(s.payment_method || 'Tidak tercatat')}</span>
                            </div>
                            <div class="income-timeline-stack">
                                <div class="income-timeline-row">
                                    <span class="income-timeline-label"><i class="bi bi-cart-check"></i><span>Order</span></span>
                                    <span class="income-timeline-value">${orderDateText}</span>
                                </div>
                                <div class="income-timeline-row">
                                    <span class="income-timeline-label"><i class="bi bi-cash-coin"></i><span>${s.settlement_time ? 'Cair' : 'Est. Cair'}</span></span>
                                    <span class="income-timeline-value ${s.settlement_time ? 'text-success' : 'text-warning'}">${settlementDateText}</span>
                                </div>
                            </div>
                            ${s.order?.items && s.order.items.length > 0 ? `
                            <div class="income-item-list">
                                ${s.order.items.map(item => `
                                    <div class="income-item-row">
                                        <div style="width:16px; height:16px; border-radius:4px; background:#f1f5f9; overflow:hidden; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                            ${item.image_url ? `<img src="${item.image_url}" style="width:100%; height:100%; object-fit:cover;">` : '<i class="bi bi-image text-muted" style="font-size:0.4rem;"></i>'}
                                        </div>
                                        <div class="income-item-name" title="${esc(item.variant_name && item.variant_name !== 'null' ? item.variant_name : 'No Variant')}">${esc(item.variant_name && item.variant_name !== 'null' ? item.variant_name : 'No Variant')}</div>
                                        <div class="income-item-qty">x${item.qty || 1}</div>
                                        <div class="income-item-sku">
                                            ${item.internal_item_id ? `<i class="bi bi-link-45deg text-primary" style="font-size:0.55rem; cursor:pointer;" onclick="mpMapping.open('${(item.model_sku || item.item_sku || '').replace(/'/g, '\\\'').replace(/"/g, '&quot;')}', function(){ loadIncomeDetail(); })" title="Mapped"></i>` : `<i class="bi bi-exclamation-circle text-danger" style="font-size:0.5rem; cursor:pointer;" onclick="mpMapping.open('${(item.model_sku || item.item_sku || '').replace(/'/g, '\\\'').replace(/"/g, '&quot;')}', function(){ loadIncomeDetail(); })" title="Unmapped"></i>`}
                                            <span title="${esc(item.model_sku || item.item_sku || '-')}">${esc(item.model_sku || item.item_sku || '-')}</span>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                            ` : ''}
                        </div>
                    </td>
                    <td>
                        ${isBookingOnly ? `
                            <div style="padding:.5rem 0;color:#a16207;font-size:.68rem;font-weight:800;">Belum tersedia</div>
                            <div style="font-size:.58rem;color:#94a3b8;">Menunggu No. Pesanan</div>
                        ` : `<div class="income-money-stack">
                            <div class="income-money-line">
                                <span class="income-money-label">Gross</span>
                                <span class="income-money-value text-muted">${fmtRp(gross)}</span>
                            </div>
                            <div class="income-money-line">
                                <span class="income-money-label">Net</span>
                                <span class="income-money-value" style="color:#2563eb; font-weight:800;">${fmtRp(grossAfterVoucherToko)} <span style="font-size:.52rem; color:#94a3b8; font-weight:800;">(${gross > 0 ? ((grossAfterVoucherToko/gross)*100).toFixed(1) : '0.0'}%)</span></span>
                            </div>
                            <div class="income-money-line">
                                <span class="income-money-label">Pembayaran Pembeli</span>
                                <span class="income-money-value text-muted">${fmtRp(buyerPaid)} <span style="font-size:.52rem; color:#94a3b8; font-weight:800;">(${gross > 0 ? ((buyerPaid/gross)*100).toFixed(1) : '0.0'}%)</span></span>
                            </div>
                        </div>`}
                    </td>
                    <td>
                        ${isBookingOnly ? `
                            <div style="padding:.5rem 0;color:#a16207;font-size:.68rem;font-weight:800;">Belum tersedia</div>
                            <div style="font-size:.58rem;color:#94a3b8;">Belum ada data potongan</div>
                        ` : `<div class="income-money-stack">
                            <div class="income-money-line">
                                <span class="income-money-label">Voucher Platform</span>
                                <span class="income-money-value txt-danger">-${fmtRp(voucherPlatform)}</span>
                            </div>
                            <div class="income-money-line">
                                <span class="income-money-label">Voucher Toko</span>
                                <span class="income-money-value txt-danger">-${fmtRp(voucherToko)}</span>
                            </div>
                            <div class="income-money-line">
                                <span class="income-money-label">Affiliate <span style="font-size:.48rem;">(${fmtPct(affiliateCommissionPercent)})</span></span>
                                <span class="income-money-value txt-danger">${affiliateCommissionText}</span>
                            </div>
                            <div class="income-money-line">
                                <span class="income-money-label">${incomeIsPending ? 'Mktplace Est.' : 'Mktplace'} <span style="font-size:.48rem;">(${fmtPct(marketplaceFeePercent)})</span></span>
                                <span class="income-money-value txt-danger">-${fmtRp(marketplaceFeeAfterAffiliate)}</span>
                            </div>
                            <div class="income-money-line">
                                <span class="income-money-label">${incomeIsPending ? 'Biaya Penjual Est.' : 'Biaya Penjual'} <span style="font-size:.48rem;">(${Number.isFinite(feePercent) ? feePercent.toFixed(1) + '%' : '-'})</span></span>
                                <span class="income-money-value txt-danger">-${fmtRp(sellerBurdenTotal)}</span>
                            </div>
                            <div class="income-money-line">
                                <span class="income-money-label">Penyesuaian</span>
                                <span class="income-money-value txt-danger">-${fmtRp(adjustmentTotal)}</span>
                            </div>
                        </div>`}
                    </td>
                    <td>
                        ${isBookingOnly ? `
                            <div style="padding:.5rem 0;color:#a16207;font-size:.68rem;font-weight:800;">Belum tersedia</div>
                            <div style="font-size:.58rem;color:#94a3b8;">Booking belum menjadi order</div>
                        ` : `<div class="income-money-stack">
                            <div class="income-money-line">
                                <span title="${incomeSourceTitle}" class="${incomeIsPending ? 'text-warning' : 'text-muted'} income-money-label">${incomeSourceLabel}</span>
                                <span class="${incomeIsPending ? 'text-warning' : 'text-success'} income-money-value">${fmtRp(netCairValue)} <span style="font-size:.52rem; color:${incomeIsPending ? '#d97706' : '#10b981'}; font-weight:800;">(${finalPercent}%)</span></span>
                            </div>
                            ${estimateFreshness ? `<div style="font-size:.52rem;color:#94a3b8;text-align:right;margin-top:-.1rem;">${estimateFreshness}</div>` : ''}
                            <div class="income-money-line">
                                <span class="income-money-label">COGS</span>
                                <span class="income-money-value text-danger">${fmtRp(cogsValue)} <span style="font-size:.52rem; color:#ef4444; font-weight:800;">(${cogsPercent}%)</span></span>
                            </div>
                            <div class="income-money-line" style="padding-top:.25rem; margin-top:.1rem; border-top:1px dashed rgba(148,163,184,.24);">
                                <span class="income-money-label" style="font-size:.58rem;">${incomeIsPending ? 'EST. PROFIT' : 'PROFIT'}</span>
                                <span class="${profitValue < 0 ? 'text-danger' : 'text-success'} income-money-value">${fmtRp(profitValue)} <span style="font-size:.52rem; color:${profitValue < 0 ? '#ef4444' : '#10b981'}; font-weight:800;">(${profitPercent}%)</span></span>
                            </div>
                        </div>`}
                    </td>
                    <td class="text-center align-middle" style="padding-right:1rem;">
                        <div class="income-action-stack w-100">
                            <button type="button" class="btn btn-sm btn-light border" style="font-size:0.6rem; font-weight:800; display:flex; justify-content:center; align-items:center; border-radius:10px;" onclick="openBreakdownModal(${idx})">
                                Rincian
                            </button>
                            <button type="button" class="btn btn-sm" style="font-size:0.6rem; font-weight:800; background:#eef2ff; color:#4f46e5; border:1px solid #c7d2fe; display:flex; justify-content:center; align-items:center; border-radius:10px;" onclick="trackOrder(${s.store?.id || s.order?.store_id || 0}, ${JSON.stringify(isBookingOnly ? (bookingSn || s.channel_order_id || '') : (s.channel_order_id || ''))}, event)">
                                <i class="bi bi-geo-alt-fill me-1"></i> Lacak
                            </button>
                        </div>
                    </td>
                </tr>`;
            }).join('')}
            </tbody>
        </table></div>`;

        let pager = '';
        if (paginationData && paginationData.last_page > 1) {
            pager += '<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap" style="padding: .85rem 1rem; border-top:1px solid rgba(148,163,184,.16)">';
            pager += `<div style="font-size:.72rem;color:var(--shp-muted)">Menampilkan ${paginationData.from || 0} - ${paginationData.to || 0} dari ${paginationData.total || 0}</div>`;
            pager += '<div class="btn-group">';
            pager += `<button class="btn btn-sm btn-light border" ${paginationData.current_page <= 1 ? 'disabled' : ''} onclick="goToPage(${Math.max(1, paginationData.current_page - 1)})">Prev</button>`;
            const start = Math.max(1, paginationData.current_page - 2);
            const end = Math.min(paginationData.last_page, paginationData.current_page + 2);
            for (let p = start; p <= end; p++) {
                pager += p === paginationData.current_page
                    ? `<button class="btn btn-sm btn-primary active">${p}</button>`
                    : `<button class="btn btn-sm btn-light border" onclick="goToPage(${p})">${p}</button>`;
            }
            pager += `<button class="btn btn-sm btn-light border" ${paginationData.current_page >= paginationData.last_page ? 'disabled' : ''} onclick="goToPage(${Math.min(paginationData.last_page, paginationData.current_page + 1)})">Next</button>`;
            pager += '</div></div>';
        }

        body.innerHTML = html + pager;
        updateIncomeTableMeta();
    }

    function renderProductTable() {
        const body = $('incomeBody');
        updateIncomeTableMeta();
        body.innerHTML = `
            <div style="padding:1.25rem;">
                <div class="skeleton-box mb-2" style="height:42px; width:100%;"></div>
                <div class="skeleton-box mb-2" style="height:72px; width:100%;"></div>
                <div class="skeleton-box mb-2" style="height:72px; width:100%;"></div>
            </div>`;

        const params = buildParams();
        fetch(productApiUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const rows = data.rows || [];
                const meta = data.meta || {};
                const topProfitList = Array.isArray(meta.top_profit_list) ? meta.top_profit_list : [];
                const topQtyList = Array.isArray(meta.top_qty_list) ? meta.top_qty_list : [];
                const topMarginList = Array.isArray(meta.top_margin_list) ? meta.top_margin_list : [];
                const topPriceName = meta.top_price_name || '-';
                const topPriceValue = Number(meta.top_price_value || 0);
                const avgGrossAfterDiscount = Number(meta.total_gross_after_seller_discount || 0);
                const avgGrossBeforeDiscount = Number(meta.total_gross_before_seller_discount || 0);
                const avgNetMargin = Number(meta.avg_profit_margin || 0);
                const totalSalesNeto = Number(meta.total_sales_after_voucher_toko || 0);
                const mappedProducts = Number(meta.mapped_products || 0);
                const unmappedProducts = Number(meta.unmapped_products || 0);
                const mappedRows = Number(meta.rows_mapped || 0);
                const unmappedRows = Number(meta.rows_unmapped || 0);
                const skuMapRate = (Number(meta.total_products || 0) > 0)
                    ? ((mappedProducts / Number(meta.total_products || 1)) * 100)
                    : 0;
                const skuCoverageRate = (mappedRows + unmappedRows) > 0
                    ? ((mappedRows / (mappedRows + unmappedRows)) * 100)
                    : 0;
                if ($('incomeTableMeta')) {
                    $('incomeTableMeta').textContent = `${Number(meta.total_products || 0).toLocaleString('id-ID')} produk • ${Number(meta.total_qty || 0).toLocaleString('id-ID')} pcs • ${fmtRp(totalSalesNeto)} penjualan neto • ${skuMapRate.toFixed(1)}% SKU map`;
                }
                if (currentTab === 'produk') {
                    if ($('incomeSummaryPanel')) $('incomeSummaryPanel').hidden = false;
                    if ($('productTopSummary')) $('productTopSummary').hidden = true;
                    renderKpi(meta);
                }
                if (!rows.length) {
                    if ($('productTopSummary')) {
                        $('productTopSummary').innerHTML = '';
                        $('productTopSummary').hidden = true;
                    }
                    body.innerHTML = `
                        <div style="padding:4rem 2rem;text-align:center;color:var(--shp-muted);">
                            <div style="width:72px;height:72px;margin:0 auto 1rem;border-radius:22px;background:rgba(148,163,184,.14);display:flex;align-items:center;justify-content:center;color:#64748b;">
                                <i class="bi bi-box-seam fs-2"></i>
                            </div>
                            <h6 style="font-weight:900;color:var(--shp-text);margin-bottom:.35rem;">Tidak ada data produk untuk filter ini</h6>
                            <div style="font-size:.8rem;max-width:28rem;margin:0 auto 1rem;">Coba longgarkan rentang tanggal atau pilih toko lain untuk melihat ringkasan produk.</div>
                        </div>`;
                    return;
                }

                if ($('productTopSummary')) {
                    $('productTopSummary').innerHTML = '';
                    $('productTopSummary').hidden = true;
                }

        const flatRows = rows.map((r, rowIndex) => {
                const totalQty = Number(r.qty_total || 0);
                    const netTokoTotal = Number(r.sales_after_voucher_toko_total || 0);
                    const netTotal = Number(r.income_total || 0);
                    const buyerPaidTotal = Number(r.buyer_paid_total || 0);
                    const cogsTotal = Number(r.cogs_total || 0);
                    const profitTotal = Number(r.profit_total || 0);
                    const netTokoUnit = totalQty > 0 ? netTokoTotal / totalQty : 0;
                    const netUnit = totalQty > 0 ? netTotal / totalQty : 0;
                    const buyerPaidUnit = totalQty > 0 ? buyerPaidTotal / totalQty : 0;
                    const cogsUnit = totalQty > 0 ? cogsTotal / totalQty : 0;
                    const profitUnit = totalQty > 0 ? profitTotal / totalQty : 0;

                    return `
                        <tr>
                            <td class="text-center txt-strong">${rowIndex + 1}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2" style="padding-left:.25rem; min-width:0;">
                                    <div style="width:34px;height:34px;border-radius:10px;background:#f1f5f9;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                                        ${r.image_url ? `<img src="${esc(r.image_url)}" style="width:100%;height:100%;object-fit:cover;">` : '<i class="bi bi-box text-muted"></i>'}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="income-product-title" title="${esc(r.name || '-')}">${esc(r.name || '-')}</div>
                                        <div style="font-size:.58rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px;line-height:1.1;" title="${esc(r.sku || '-')}${r.variant_name && r.variant_name !== '-' ? ' • ' + esc(r.variant_name) : ''}">${esc(r.sku || '-')}${r.variant_name && r.variant_name !== '-' ? ' • ' + esc(r.variant_name) : ''}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="txt-strong text-end">${Number(r.order_count || 0).toLocaleString('id-ID')}</td>
                            <td class="txt-strong text-end">${totalQty.toLocaleString('id-ID')}</td>
                            <td>
                                <div class="income-product-metric text-end">
                                    <div class="value primary">${fmtRp(netTokoTotal)}</div>
                                    <div class="unit">(${fmtRp(netTokoUnit)})</div>
                                </div>
                            </td>
                            <td>
                                <div class="income-product-metric text-end">
                                    <div class="value success">${fmtRp(netTotal)}</div>
                                    <div class="unit">(${fmtRp(netUnit)})</div>
                                </div>
                            </td>
                            <td>
                                <div class="income-product-metric text-end">
                                    <div class="value">${fmtRp(buyerPaidTotal)}</div>
                                    <div class="unit">(${fmtRp(buyerPaidUnit)})</div>
                                </div>
                            </td>
                            <td>
                                <div class="income-product-metric text-end">
                                    <div class="value danger">${fmtRp(cogsTotal)}</div>
                                    <div class="unit">(${fmtRp(cogsUnit)})</div>
                                </div>
                            </td>
                            <td>
                                <div class="income-product-metric text-end">
                                    <div class="value ${profitTotal < 0 ? 'danger' : 'success'}">${fmtRp(profitTotal)}</div>
                                    <div class="unit">(${fmtRp(profitUnit)})</div>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');

                const html = `
                    <div class="income-table-shell" style="max-height:68vh; overflow-y:auto; overflow-x:hidden;">
                    <table class="table income-table income-product-table w-100 mb-0">
                        <thead>
                            <tr>
                                <th class="product-sticky-head text-center" style="min-width:52px;">No</th>
                                <th class="product-sticky-head" style="min-width:220px;">Produk</th>
                                <th class="product-sticky-head text-end" style="min-width:88px;">Order</th>
                                <th class="product-sticky-head text-end" style="min-width:72px;">Qty</th>
                                <th class="product-sticky-head text-end" style="min-width:120px;">Net</th>
                                <th class="product-sticky-head text-end" style="min-width:120px;">Net Cair</th>
                                <th class="product-sticky-head text-end" style="min-width:120px;">Pembayaran Pembeli</th>
                                <th class="product-sticky-head text-end" style="min-width:110px;">COGS</th>
                                <th class="product-sticky-head text-end" style="min-width:120px;">Profit</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${flatRows}
                        </tbody>
                    </table>
                    </div>`;
                body.innerHTML = html;
            })
            .catch(err => {
                if ($('incomeTableMeta')) $('incomeTableMeta').textContent = 'Gagal memuat data produk';
                body.innerHTML = '<div style="padding:2rem;text-align:center;color:#b91c1c;">Gagal memuat data produk: ' + esc(err.message) + '</div>';
            });
    }

    window.goToPage = function (page) {
        currentPage = page;
        loadIncomeDetail();
    };

    window.loadIncomeDetail = async function () {
        saveState();
        updateIncomeModeLabels();
        if ($('incomeTableMeta')) $('incomeTableMeta').textContent = 'Memuat data…';
        $('incomeBody').innerHTML = `
            <div style="padding:1.5rem;">
                <div class="skeleton-box mb-2" style="height:40px; width:100%;"></div>
                <div class="skeleton-box mb-2" style="height:60px; width:100%;"></div>
                <div class="skeleton-box mb-2" style="height:60px; width:100%;"></div>
                <div class="skeleton-box mb-2" style="height:60px; width:100%;"></div>
            </div>`;

        if (currentPage === 1 || !lastMeta) {
            ['kpiCount', 'kpiCountSelesai', 'kpiCountBatal', 'kpiCountPenyesuaian', 'kpiGross', 'kpiGrossNetToko', 'kpiGrossBuyerPaid', 'kpiVoucher', 'kpiVoucherToko', 'kpiVoucherPlatform', 'kpiFeeTotal', 'kpiAovPembeli', 'kpiAffiliate', 'kpiMarketplace', 'kpiNetPayout', 'kpiCogs', 'kpiGrossProfit'].forEach(id => {
                if ($(id)) $(id).innerHTML = '<span class="spinner-border spinner-border-sm" style="width: 1rem; height: 1rem; border-width: 0.15em;" role="status" aria-hidden="true"></span>';
            });
        }

        const params = buildParams();
        try {
            const endpoint = currentTab === 'produk'
                ? productApiUrl
                : '/api/marketplace/settlements';
            const res = await api(endpoint + '?' + params.toString());

            if (currentTab === 'produk') {
                if (!res || !Array.isArray(res.rows)) {
                    throw new Error('Response produk tidak valid.');
                }
                rows = res.rows || [];
                paginationData = null;
                lastMeta = res.meta || null;
                if ($('incomeSummaryPanel')) $('incomeSummaryPanel').hidden = false;
                renderKpi(res.meta || {});
            } else {
                if (!res || !res.paginator) {
                    throw new Error('Response tidak valid.');
                }
                rows = res.paginator.data || [];
                paginationData = res.paginator;
                if (res.meta) {
                    lastMeta = res.meta;
                    renderKpi(res.meta);
                    updateIncomeModeLabels(res.meta);
                }
            }
            renderTable();
            setTimeout(() => {
                const icons = document.querySelectorAll('th i.bi-arrow-up, th i.bi-arrow-down, th i.bi-arrow-down-up');
                icons.forEach(el => { el.className = 'bi bi-arrow-down-up ms-1'; el.style.opacity = '0.4'; });
                const activeIcon = document.getElementById('sortIcon_' + sortBy);
                if (activeIcon) {
                    activeIcon.className = sortDir === 'asc' ? 'bi bi-arrow-up ms-1 text-primary' : 'bi bi-arrow-down ms-1 text-primary';
                    activeIcon.style.opacity = '1';
                }
            }, 50);
        } catch (e) {
            if ($('incomeTableMeta')) $('incomeTableMeta').textContent = 'Gagal memuat data';
            $('incomeBody').innerHTML = '<div style="padding:2rem;text-align:center;color:#b91c1c;">Gagal memuat data: ' + esc(e.message) + '</div>';
        }
    };

    async function init() {
        try {
            stores = await api('/api/marketplace/stores').catch(() => []);
            const sel = $('filterStore');
            if (sel) {
                stores.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.name + ' (' + (s.channel?.name || '?') + ')';
                    sel.appendChild(opt);
                });
            }

            const restoredState = restoreState();
            if (window.GFID && window.GFID.initDateRange) {
                const orderDefaultDate = defaultOrderRange(restoredState?.order_date);
                const settlementDefaultDate = defaultSettlementRange(restoredState?.settlement_date);
                orderRangePicker = window.GFID.initDateRange('#filterOrder', {
                    defaultDate: orderDefaultDate,
                    altInputClass: 'income-date-input gf-date-input',
                    onChange: function(sel, str) { saveState(); if (sel.length === 2 || sel.length === 0) goFirstPage(); }
                });
                settlementRangePicker = window.GFID.initDateRange('#filterSettlement', {
                    defaultDate: settlementDefaultDate,
                    altInputClass: 'income-date-input gf-date-input',
                    onChange: function(sel, str) { saveState(); if (sel.length === 2 || sel.length === 0) goFirstPage(); }
                });
                const restoredOrder = parseStoredRange(restoredState?.order_date);
                const restoredSettlement = parseStoredRange(restoredState?.settlement_date);
                if (orderRangePicker && restoredOrder) orderRangePicker.setDate(restoredOrder, false);
                if (settlementRangePicker && restoredSettlement) settlementRangePicker.setDate(restoredSettlement, false);
                syncDefaultFilterInputs();
            }
            bindFilterPersistence();
            await loadIncomeDetail();
            setTimeout(() => {
                const search = $('filterSearch');
                if (search) {
                    search.focus({ preventScroll: true });
                    if (typeof search.select === 'function') search.select();
                }
            }, 50);
        } catch (err) {
            if ($('incomeBody')) {
                $('incomeBody').innerHTML = '<div style="padding:2rem;color:red;">INIT ERROR: ' + err.message + '<br>' + err.stack + '</div>';
            }
        }
    }

    window.trackOrder = async function(storeId, orderSn, e) {
        if(e) e.preventDefault();

        let modalEl = document.getElementById('trackingModal');
        if (!modalEl) {
            document.body.insertAdjacentHTML('beforeend', `
                <div class="modal fade" id="trackingModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content border-0 shadow" style="border-radius:12px;">
                            <div class="modal-header border-bottom-0 pb-0">
                                <h5 class="modal-title fw-bold" style="font-size:1.1rem; color:#0f172a;">Status Pengiriman</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="trackingModalBody"></div>
                        </div>
                    </div>
                </div>
            `);
            modalEl = document.getElementById('trackingModal');
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        const body = document.getElementById('trackingModalBody');

        body.innerHTML = '<div style="text-align:center; padding:30px; color:#64748b"><div class="spinner-border spinner-border-sm text-primary mb-2"></div><br>Mengambil data tracking...</div>';
        modal.show();

        try {
            const res = await fetch(`/api/marketplace/stores/${storeId}/orders/${orderSn}/tracking`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (!res.ok || data.error) {
                body.innerHTML = `<div style="color:#ef4444; text-align:center; padding:20px">❌ Gagal mengambil tracking: ${data.message || data.error || 'Terjadi kesalahan'}</div>`;
                return;
            }

            const trk = data.response?.tracking_info || data.tracking_info || [];
            if (!trk.length) {
                body.innerHTML = `<div style="color:#d97706; text-align:center; padding:20px; font-weight:500;">ℹ️ Belum ada riwayat perjalanan paket.</div>`;
                return;
            }

            let html = '<div style="position:relative; padding-left:1.5rem; margin-top:10px;">';
            html += '<div style="position:absolute; left:7px; top:10px; bottom:10px; width:2px; background:#e2e8f0;"></div>';

            trk.forEach((t, i) => {
                const isFirst = i === 0;
                const dateObj = new Date(t.update_time * 1000);
                const dText = dateObj.toLocaleString('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
                const dotColor = isFirst ? '#334155' : '#cbd5e1';
                const textColor = isFirst ? '#0f172a' : '#64748b';

                html += `
                    <div style="position:relative; margin-bottom:1.2rem;">
                        <div style="position:absolute; left:-1.5rem; top:4px; width:10px; height:10px; border-radius:50%; background:${dotColor}; border:2px solid #fff; box-shadow:0 0 0 1px ${dotColor}"></div>
                        <div style="font-size:0.85rem; font-weight:${isFirst ? '600' : '500'}; color:${textColor};">${t.description || t.logistics_status}</div>
                        <div style="font-size:0.75rem; color:#94a3b8; margin-top:2px;">${dText}</div>
                    </div>
                `;
            });
            html += '</div>';
            body.innerHTML = html;
        } catch (err) {
            body.innerHTML = `<div style="color:#ef4444; text-align:center; padding:20px">❌ Gagal menghubungi server: ${err.message}</div>`;
        }
    };

    init();
})();
</script>
@include('marketplace._mapping-modal')
@endpush
