@extends('layouts.app')

@section('title', 'Warehouse Intelligence • Transfer & Produksi')

@php
    $role = strtolower((string)(auth()->user()?->role ?? 'owner'));
    $allTabs = [
        'rts' => 'Kebutuhan WH-RTS',
        'prd' => 'Prioritas WH-PRD',
    ];
    $allTabDesc = [
        'rts' => 'Daftar item yang stoknya menipis di area display/packing (WH-RTS).',
        'prd' => 'Daftar prioritas packing/transfer & jahit dari sudut pandang Gudang Produksi.',
    ];

    $tabs = [];
    $tabDesc = [];

    if ($role === 'admin') {
        $tabs['rts'] = $allTabs['rts'];
        $tabDesc['rts'] = $allTabDesc['rts'];
    } elseif ($role === 'operating') {
        $tabs['prd'] = $allTabs['prd'];
        $tabDesc['prd'] = $allTabDesc['prd'];
    } else {
        $tabs = $allTabs;
        $tabDesc = $allTabDesc;
    }
@endphp

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
            background: var(--card, #fff);
            border-radius: 8px;
            border: 1px solid var(--shp-border);
            box-shadow: none;
            overflow:hidden;
            margin-bottom: 1rem;
        }
        body[data-theme="dark"] .card-main{
            background: var(--card, #0f172a);
            border-color: rgba(51,65,85,.85);
            box-shadow: none;
        }

        .ship-topbar{
            position:sticky;
            top:0;
            z-index:300;
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:.6rem;
            flex-wrap:wrap;
            padding:.65rem .75rem;
            margin-inline:-.75rem;
            margin-bottom:.85rem;
            background:var(--card,#fff);
            border-bottom:1px solid var(--shp-border);
        }
        body[data-theme="dark"] .ship-topbar{ background:var(--card,#0f172a); }
        .title{ font-weight: 750; font-size:1.15rem; letter-spacing: 0; margin:0; }
        .sub{ color:var(--shp-muted); font-size:.82rem; margin-top: .15rem; }
        body[data-theme="dark"] .sub{ color:#9ca3af; }

        .controls{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; margin-top: .4rem; }
        .filter-label{ font-size:.8rem; color:#6b7280; font-weight: 600; }
        body[data-theme="dark"] .filter-label{ color:#9ca3af; }
        
        .gf-header-select {
            min-height: 36px; border-radius: 7px; font-size: .82rem; font-weight: 600;
            border: 1px solid rgba(148,163,184,.35); box-shadow: none; background-color: transparent;
            padding-left: .75rem; padding-right: 2rem;
        }

        .btn-pill{ border-radius:7px; padding-inline:.78rem; box-shadow:none!important; font-weight:600; min-height: 36px; display: inline-flex; align-items: center;}
        .btn-ship-primary{ background:var(--shp-accent)!important; border-color:var(--shp-accent)!important; color:#fff!important; }
        .btn-ship-primary:hover{ background:var(--shp-accent-2)!important; border-color:var(--shp-accent-2)!important; color:#fff!important; }
        .btn-ship-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
        .btn-ship-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }

        .ii-tabs-nav {
            display: flex; gap: .25rem; flex-wrap: wrap; margin-bottom: 1rem;
        }
        .ii-tab-btn {
            background: transparent; border: 1px solid transparent; color: #64748b; font-weight: 600; font-size: .85rem; padding: .4rem .8rem; border-radius: 7px; transition: all .15s;
        }
        .ii-tab-btn:hover { background: rgba(148,163,184,.1); color: #0f172a; }
        .ii-tab-btn.is-active { background: #f1f5f9; color: #0f172a; border-color: rgba(148,163,184,.25); }
        body[data-theme="dark"] .ii-tab-btn.is-active { background: rgba(51, 65, 85, .5); color: #f8fafc; border-color: rgba(51, 65, 85, .8); }
        body[data-theme="dark"] .ii-tab-btn:hover:not(.is-active) { color: #f8fafc; }

        /* General partials UI */
        .ii-tab-loading { display: flex; align-items: center; justify-content: center; gap: .6rem; color: #64748b; font-size: .85rem; padding: 2.4rem 1rem; }
        .ii-tab-spinner { width: 18px; height: 18px; border-radius: 50%; border: 2px solid rgba(148, 163, 184, .35); border-top-color: #2563eb; animation: iispin .7s linear infinite; }
        @keyframes iispin { to { transform: rotate(360deg); } }
        .ii-filter-busy { opacity: .55; pointer-events: none; }
        .ii-empty { text-align: center; color: #64748b; font-size: .85rem; padding: 2.5rem 1rem; }

        /* Filter bar */
        .filter-bar{
            background:var(--card, #fff); border:1px solid rgba(148,163,184,.15);
            border-radius:10px; padding:.75rem .85rem; margin-bottom:1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        body[data-theme="dark"] .filter-bar{ background:rgba(15,23,42,.98); border-color:rgba(51,65,85,.6); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .filter-bar .form-control, .filter-bar .form-select, .item-suggest-input{ border-radius:8px; font-size:.84rem; border-color: rgba(148,163,184,.3); min-height: 34px; }
        .filter-bar .form-control:focus, .filter-bar .form-select:focus, .item-suggest-input:focus { box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); border-color: #3b82f6; }
        @media (max-width: 767.98px){
            .filter-bar{
                display:none !important;
            }
        }
        
        .ii-count { margin-left: auto; font-size: .78rem; font-weight: 700; color: #475569; white-space: nowrap; }

        /* Table */
        .table-list { margin-bottom: 0; font-size: .82rem; }
        .table-list thead th { border-bottom-width: 1px; font-size: .72rem; text-transform: none; letter-spacing: 0; color: #64748b; background: var(--card,#fff); padding: .6rem .75rem; white-space: nowrap; font-weight: 600; }
        body[data-theme="dark"] .table-list thead th { background: rgba(15, 23, 42, 0.98); color: #9ca3af; border-bottom-color: rgba(51, 65, 85, 0.6); }
        .table-list tbody td { vertical-align: middle; border-top-color: rgba(148, 163, 184, 0.16); padding: .6rem .75rem; }
        body[data-theme="dark"] .table-list tbody td { border-top-color: rgba(51, 65, 85, 0.85); }
        .table-list tbody tr:hover td { background: rgba(241, 245, 249, 0.4); }
        body[data-theme="dark"] .table-list tbody tr:hover td { background: rgba(30, 41, 59, 0.4); }

        .rts-stock-label{
            color:#64748b;
            font-size:.7rem;
            font-weight:700;
            white-space:nowrap;
        }
        .rts-stock-grid{
            display:grid;
            grid-template-columns:repeat(4, minmax(0, 1fr));
            gap:.35rem;
            min-width:0;
        }
        .rts-stock-metric{
            min-width:0;
            padding:.45rem .5rem;
            border-radius:10px;
            border:1px solid rgba(148,163,184,.16);
            background:rgba(248,250,252,.92);
        }
        body[data-theme="dark"] .rts-stock-metric{
            background:rgba(15,23,42,.86);
            border-color:rgba(51,65,85,.72);
        }
        .rts-stock-value{
            font-size:.84rem;
            font-weight:800;
            color:#0f172a;
            line-height:1.2;
            margin-top:.1rem;
        }
        body[data-theme="dark"] .rts-stock-value{
            color:#f8fafc;
        }
        .rts-stock-note{
            color:#64748b;
            font-size:.66rem;
            line-height:1.25;
            margin-top:.12rem;
        }
        .rts-action-stack{
            display:flex;
            flex-direction:column;
            gap:.3rem;
            align-items:flex-start;
        }
        .rts-action-line{
            display:flex;
            flex-wrap:wrap;
            gap:.35rem;
            align-items:center;
            min-width:0;
        }
        .rts-action-chip{
            display:inline-flex;
            align-items:center;
            gap:.25rem;
            padding:.22rem .48rem;
            border-radius:999px;
            border:1px solid transparent;
            font-weight:800;
            white-space:nowrap;
        }
        .rts-draft-note{
            display:inline-flex;
            align-items:center;
            gap:.25rem;
            font-size:.68rem;
            font-weight:800;
            color:#7c3aed;
            text-decoration:none;
            white-space:nowrap;
        }
        .rts-draft-note:hover{ text-decoration:underline; }
        .rts-action-buttons{
            display:flex;
            flex-wrap:wrap;
            gap:.35rem;
        }
        .rts-action-btn{
            font-weight:700;
            font-size:.7rem;
            border-radius:7px;
            padding:.24rem .55rem;
            display:inline-flex;
            align-items:center;
            gap:4px;
            box-shadow:none!important;
        }
        .btn-edit-limit{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:18px;
            height:18px;
            margin-top:1px;
            color:#94a3b8!important;
        }
        .btn-edit-limit:hover{
            color:#475569!important;
        }
        @media (max-width: 991.98px){
            .rts-stock-grid{
                grid-template-columns:repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 575.98px){
            .rts-stock-grid{
                grid-template-columns:1fr;
            }
        }

        .text-muted-ii { color: #64748b; font-size: .78rem; }
        .fw-semibold { font-weight: 600; }

        .badge-status{ border-radius:7px; padding:.16rem .48rem; font-size:.7rem; letter-spacing:0; text-transform:none; border:1px solid transparent; display:inline-flex; align-items:center; gap:.35rem; white-space:nowrap; font-weight: 500; }
        .badge-status::before{ content:''; width:7px; height:7px; border-radius:999px; display:inline-block; }

        .sort-icon { display: inline-block; width: 0; height: 0; margin-left: 4px; vertical-align: middle; border-right: 4px solid transparent; border-left: 4px solid transparent; }
        .sortable.asc .sort-icon { border-bottom: 4px solid #64748b; border-top: 0; }
        .sortable.desc .sort-icon { border-top: 4px solid #64748b; border-bottom: 0; }

        .ii-ai-summary{
            background: linear-gradient(180deg, rgba(248,250,252,.98) 0%, rgba(255,255,255,.98) 100%);
            border: 1px solid rgba(148,163,184,.18);
            border-radius: 14px;
            padding: .95rem 1rem;
            margin-bottom: 1rem;
        }
        body[data-theme="dark"] .ii-ai-summary{
            background: linear-gradient(180deg, rgba(15,23,42,.96) 0%, rgba(15,23,42,.92) 100%);
            border-color: rgba(51,65,85,.75);
        }
        .ai-signal-chip{
            flex: 1 1 160px;
            min-width: 160px;
            background: #fff;
            border: 1px solid rgba(148,163,184,.18);
            border-radius: 12px;
            padding: .7rem .8rem;
        }
        body[data-theme="dark"] .ai-signal-chip{
            background: rgba(15,23,42,.9);
            border-color: rgba(51,65,85,.75);
        }
        .ai-signal-value{
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }
        body[data-theme="dark"] .ai-signal-value{ color: #f8fafc; }
        .ai-signal-label{
            font-size: .72rem;
            font-weight: 700;
            color: #475569;
            margin-top: .15rem;
        }
        body[data-theme="dark"] .ai-signal-label{ color: #94a3b8; }
        .ai-signal-evidence{
            font-size: .68rem;
            color: #64748b;
            margin-top: .25rem;
            line-height: 1.35;
        }
        body[data-theme="dark"] .ai-signal-evidence{ color: #94a3b8; }

        .ai-accordion-shell{
            border-left: 4px solid #334155;
        }
        .ai-accordion-shell > summary{
            list-style: none;
            cursor: pointer;
        }
        .ai-accordion-shell > summary::-webkit-details-marker{ display:none; }
        .ai-accordion-head{
            display:flex;
            justify-content:space-between;
            gap:.85rem;
            align-items:flex-start;
            padding: .95rem 1rem;
        }
        .ai-accordion-title{
            display:flex;
            flex-direction:column;
            gap:.3rem;
            min-width:0;
        }
        .ai-accordion-kicker{
            display:flex;
            align-items:center;
            gap:.5rem;
            flex-wrap:wrap;
        }
        .ai-accordion-kicker .text-muted-ii{
            font-size:.72rem;
        }
        .ai-accordion-main{
            font-size:.96rem;
            font-weight:800;
            color:#0f172a;
            line-height:1.35;
        }
        body[data-theme="dark"] .ai-accordion-main{ color:#f8fafc; }
        .ai-accordion-sub{
            font-size:.78rem;
            color:#64748b;
            line-height:1.45;
        }
        body[data-theme="dark"] .ai-accordion-sub{ color:#94a3b8; }
        .ai-accordion-metrics{
            display:flex;
            align-items:flex-start;
            gap:.5rem;
            flex-wrap:wrap;
            justify-content:flex-end;
        }
        .ai-mini-metric{
            min-width: 108px;
            padding: .55rem .7rem;
            border-radius: 10px;
            border: 1px solid rgba(148,163,184,.18);
            background: rgba(248,250,252,.92);
        }
        body[data-theme="dark"] .ai-mini-metric{
            background: rgba(15,23,42,.88);
            border-color: rgba(51,65,85,.75);
        }
        .ai-mini-metric .label{
            font-size:.68rem;
            color:#64748b;
            font-weight:700;
        }
        body[data-theme="dark"] .ai-mini-metric .label{ color:#94a3b8; }
        .ai-mini-metric .value{
            font-size:.95rem;
            font-weight:800;
            color:#0f172a;
            line-height:1.1;
            margin-top:.12rem;
        }
        body[data-theme="dark"] .ai-mini-metric .value{ color:#f8fafc; }
        .ai-accordion-body{
            border-top:1px solid rgba(148,163,184,.18);
            padding: 1rem;
        }
        body[data-theme="dark"] .ai-accordion-body{
            border-top-color: rgba(51,65,85,.75);
        }
        .ai-overview-box{
            border: 1px solid rgba(148,163,184,.16);
            border-radius: 12px;
            background: rgba(248,250,252,.9);
            padding: .85rem .95rem;
        }
        body[data-theme="dark"] .ai-overview-box{
            background: rgba(15,23,42,.82);
            border-color: rgba(51,65,85,.75);
        }
        .ai-overview-copy{
            font-size: .84rem;
            line-height: 1.55;
            color: #334155;
        }
        body[data-theme="dark"] .ai-overview-copy{ color:#cbd5e1; }
        @media (max-width: 576px){
            .ai-accordion-head{
                flex-direction: column;
                padding: .9rem .9rem .82rem;
            }
            .ai-accordion-kicker{
                align-items:flex-start;
                gap:.3rem;
            }
            .ai-accordion-kicker .text-muted-ii{
                width:100%;
                font-size:.7rem;
                line-height:1.35;
                display:block;
            }
            .ai-accordion-main{
                font-size:.9rem;
                line-height:1.35;
            }
            .ai-accordion-sub{
                font-size:.75rem;
                line-height:1.42;
                margin-top:.1rem;
            }
            .ai-accordion-metrics{
                width:100%;
                justify-content:flex-start;
                gap:.35rem;
                margin-top:.15rem;
            }
            .ai-mini-metric{
                min-width: calc(33.333% - .25rem);
                flex: 1 1 calc(33.333% - .25rem);
                padding: .48rem .55rem;
            }
            .ai-mini-metric .value{
                font-size:.88rem;
            }
        }
        .ai-pill-row{
            display:flex;
            flex-wrap:wrap;
            gap:.5rem;
        }
        .ai-pill{
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            padding:.38rem .65rem;
            border-radius:999px;
            border:1px solid rgba(148,163,184,.18);
            background:#fff;
            font-size:.74rem;
            font-weight:700;
            color:#334155;
        }
        body[data-theme="dark"] .ai-pill{
            background: rgba(15,23,42,.88);
            border-color: rgba(51,65,85,.75);
            color:#e2e8f0;
        }
        .ai-section-label{
            font-size:.74rem;
            font-weight:800;
            color:#334155;
            margin-bottom:.5rem;
        }
        body[data-theme="dark"] .ai-section-label{ color:#e2e8f0; }
        .ai-action-details{
            border:1px solid rgba(148,163,184,.18);
            border-radius:12px;
            background:#fff;
            overflow:hidden;
        }
        body[data-theme="dark"] .ai-action-details{
            background: rgba(15,23,42,.88);
            border-color: rgba(51,65,85,.75);
        }
        .ai-action-details > summary{
            list-style:none;
            cursor:pointer;
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:.75rem;
            padding:.75rem .85rem;
        }
        .ai-action-details > summary::-webkit-details-marker{ display:none; }
        .ai-action-head{
            min-width:0;
            flex:1 1 auto;
        }
        .ai-action-title{
            font-size:.9rem;
            font-weight:800;
            color:#0f172a;
            line-height:1.3;
        }
        body[data-theme="dark"] .ai-action-title{ color:#f8fafc; }
        .ai-action-product{
            font-size:.78rem;
            color:#475569;
            margin-top:.1rem;
        }
        body[data-theme="dark"] .ai-action-product{ color:#94a3b8; }
        .ai-action-meta{
            flex:0 0 auto;
            text-align:right;
            display:flex;
            flex-direction:column;
            align-items:flex-end;
            gap:.25rem;
        }
        .ai-action-chip-row{
            display:flex;
            flex-wrap:wrap;
            justify-content:flex-end;
            gap:.3rem;
        }
        .ai-action-chip{
            display:inline-flex;
            align-items:center;
            padding:.24rem .48rem;
            border-radius:999px;
            border:1px solid transparent;
            font-size:.7rem;
            font-weight:800;
            line-height:1.2;
            white-space:nowrap;
        }
        .ai-action-code{
            font-size:.7rem;
            color:#64748b;
        }
        .ai-action-body{
            border-top:1px solid rgba(148,163,184,.12);
            padding:.8rem .85rem .9rem;
            font-size:.82rem;
            line-height:1.5;
            color:#334155;
        }
        body[data-theme="dark"] .ai-action-body{
            color:#cbd5e1;
            border-top-color: rgba(51,65,85,.65);
        }
        .ai-compact-list{
            display:flex;
            flex-direction:column;
            gap:.55rem;
        }
        .ai-mini-row{
            display:flex;
            justify-content:space-between;
            gap:.75rem;
            align-items:flex-start;
            border:1px solid rgba(148,163,184,.16);
            background:#fff;
            border-radius:12px;
            padding:.72rem .8rem;
        }
        body[data-theme="dark"] .ai-mini-row{
            background: rgba(15,23,42,.88);
            border-color: rgba(51,65,85,.75);
        }
        .ai-mini-row .name{
            font-size:.86rem;
            font-weight:800;
            color:#0f172a;
            line-height:1.3;
        }
        body[data-theme="dark"] .ai-mini-row .name{ color:#f8fafc; }
        .ai-mini-row .desc{
            font-size:.72rem;
            color:#64748b;
            margin-top:.18rem;
            line-height:1.4;
        }
        body[data-theme="dark"] .ai-mini-row .desc{ color:#94a3b8; }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="page-wrap">
    
    <div class="ship-topbar">
        <div>
            <h1 class="title">Warehouse Intelligence</h1>
            <div class="sub" id="top-desc">{{ $tabDesc[$tab] }}</div>
        </div>
        <div class="controls">
            <button type="button" class="btn btn-ship-outline btn-pill" onclick="window.print()">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:.3rem"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Print
            </button>
            <a href="{{ route('inventory.intelligence') }}" class="btn btn-ship-primary btn-pill">
                Kembali ke Intelligence
            </a>
        </div>
    </div>

    <div class="ii-tabs-nav" id="intel-tabs">
        @foreach($tabs as $key => $label)
            <button type="button" class="ii-tab-btn {{ $tab === $key ? 'is-active' : '' }}" data-tab-target="{{ $key }}" data-desc="{{ $tabDesc[$key] }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div id="ai-insights-container">
        @include('inventory.warehouse_intelligence.partials._insights', ['insights' => $aiInsights])
    </div>

    @php
        $selectedFilterItem = collect($items ?? [])->firstWhere('id', (int) ($filters['item_id'] ?? 0));
        $filterItemDisplay = $selectedFilterItem ? trim(($selectedFilterItem->code ? $selectedFilterItem->code . ' - ' : '') . $selectedFilterItem->name) : '';
    @endphp

    <!-- General Filters -->
    <div class="filter-bar">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="filter-label d-block mb-1" style="font-size: .75rem;">Kategori Item</label>
                <select class="form-select js-intel-filter" id="filter-cat" data-filter-key="category_id">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 mobile-hide-item-filter">
                <label class="filter-label d-block mb-1" style="font-size: .75rem;">Item spesifik</label>
                <x-item-suggest-input
                    idName="filter-item-id"
                    placeholder="Cari SKU / nama item..."
                    type="finished_good"
                    :displayValue="$filterItemDisplay"
                    :idValue="(string) ($filters['item_id'] ?? '')"
                />
            </div>
            <div class="col-12 col-md-3">
                <label class="filter-label d-block mb-1" style="font-size: .75rem;">Filter Operasional</label>
                <select class="form-select js-intel-filter" id="filter-operational" data-filter-key="operational_filter">
                    <option value="">Semua data</option>
                    <option value="critical" @selected((string) ($filters['operational_filter'] ?? '') === 'critical')>Yang kritis</option>
                    <option value="transfer_ready" @selected((string) ($filters['operational_filter'] ?? '') === 'transfer_ready')>Siap dipindah</option>
                    <option value="need_buy" @selected((string) ($filters['operational_filter'] ?? '') === 'need_buy')>Perlu dibeli</option>
                    <option value="need_production" @selected((string) ($filters['operational_filter'] ?? '') === 'need_production')>Perlu produksi</option>
                    <option value="with_wip" @selected((string) ($filters['operational_filter'] ?? '') === 'with_wip')>Ada stok proses</option>
                    <option value="sewing" @selected((string) ($filters['operational_filter'] ?? '') === 'sewing')>Butuh jahit</option>
                    <option value="cutting" @selected((string) ($filters['operational_filter'] ?? '') === 'cutting')>Butuh potong</option>
                    <option value="ready_to_move" @selected((string) ($filters['operational_filter'] ?? '') === 'ready_to_move')>Siap bergerak</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="filter-label d-block mb-1" style="font-size: .75rem;">Draft</label>
                <select class="form-select js-intel-filter" id="filter-draft" data-filter-key="draft_filter">
                    <option value="">Semua</option>
                    <option value="has_draft" @selected((string) ($filters['draft_filter'] ?? '') === 'has_draft')>Sudah ada draft</option>
                    <option value="no_draft" @selected((string) ($filters['draft_filter'] ?? '') === 'no_draft')>Belum ada draft</option>
                </select>
            </div>
            <div class="col-12 col-md-1 d-flex justify-content-md-end">
                <button type="button" class="btn btn-ship-outline btn-pill w-100" onclick="window.refreshIntel()" style="height: 34px;">
                    <i class="bi bi-arrow-repeat"></i>
                </button>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
            <div class="text-muted-ii" style="font-size:.72rem;">Filter akan jalan otomatis begitu kamu pilih opsinya.</div>
            <div class="text-muted-ii" id="intel-filter-status" style="font-size:.72rem;">Siap memuat data terbaru.</div>
        </div>
    </div>

    <!-- The actual view will render here -->
    <div id="intel-container">
        <div class="ii-tab-loading">
            <div class="ii-tab-spinner"></div>
            Memuat analitik gudang...
        </div>
    </div>
</div>

<!-- Modal Edit Limit -->
<div class="modal fade" id="modalEditLimit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border: none; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
            <div class="modal-header" style="background: rgba(248, 250, 252, 0.9); border-bottom: 1px solid rgba(148, 163, 184, 0.15); padding: 1rem 1.25rem;">
                <h5 class="modal-title" style="font-size: 1rem; font-weight: 700; color: #0f172a;">Edit Batas Display</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: .8rem;"></button>
            </div>
            <div class="modal-body" style="padding: 1.25rem;">
                <div class="mb-3">
                    <label class="form-label text-muted" style="font-size: .75rem; font-weight: 600; margin-bottom: .25rem;">SKU ITEM</label>
                    <div id="modal-sku-text" style="font-weight: 600; font-size: .95rem; color: #1e293b;">-</div>
                </div>
                <input type="hidden" id="modal-item-id">
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label" style="font-size: .8rem; font-weight: 600;">Min Display</label>
                        <input type="number" class="form-control" id="modal-min-input" min="0">
                        <small class="text-muted d-block mt-1" style="font-size: .7rem;" id="modal-def-min-text">Def: 5</small>
                    </div>
                    <div class="col-6">
                        <label class="form-label" style="font-size: .8rem; font-weight: 600;">Max Display</label>
                        <input type="number" class="form-control" id="modal-max-input" min="0">
                        <small class="text-muted d-block mt-1" style="font-size: .7rem;" id="modal-def-max-text">Def: 14</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: .75rem 1.25rem; border-top: 1px solid rgba(148, 163, 184, 0.1);">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" style="font-weight: 600; border-radius: 6px;">Batal</button>
                <button type="button" class="btn btn-sm btn-ship-primary" id="btn-save-modal-limit" style="font-weight: 600; border-radius: 6px; min-width: 80px;">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('intel-container');
    const tabs = document.querySelectorAll('.ii-tab-btn');
    const topDesc = document.getElementById('top-desc');
    const insightsContainer = document.getElementById('ai-insights-container');
    const filterStatus = document.getElementById('intel-filter-status');
    const cache = {};

    let currentTab = '{{ $tab }}';
    let filterTimer = null;

    function setFilterStatus(text) {
        if (filterStatus) {
            filterStatus.textContent = text;
        }
    }

    function collectFilters() {
        return {
            item_id: (document.querySelector('input[name="filter-item-id"]')?.value || '').trim(),
            category_id: (document.getElementById('filter-cat')?.value || '').trim(),
            operational_filter: (document.getElementById('filter-operational')?.value || '').trim(),
            draft_filter: (document.getElementById('filter-draft')?.value || '').trim(),
        };
    }

    function buildQueryParams(tabKey, filters) {
        const params = new URLSearchParams();
        params.set('tab', tabKey);

        Object.entries(filters).forEach(([key, value]) => {
            if (value !== '') {
                params.set(key, value);
            }
        });

        return params.toString();
    }

    function syncUrl(tabKey, filters) {
        const url = new URL(window.location.href);
        url.search = buildQueryParams(tabKey, filters);
        window.history.replaceState({}, '', url);
    }

    function fetchTab(tabKey, force = false) {
        const filters = collectFilters();
        const cacheKey = `${tabKey}::${buildQueryParams(tabKey, filters)}`;

        syncUrl(tabKey, filters);

        if (!force && cache[cacheKey]) {
            container.innerHTML = cache[cacheKey];
            initTabScripts();
            setFilterStatus('Data sudah sinkron.');
            return;
        }

        container.innerHTML = '<div class="ii-tab-loading"><div class="ii-tab-spinner"></div>Memuat data...</div>';
        setFilterStatus('Lagi memuat data terbaru...');

        fetch(`{{ route('inventory.warehouse_intelligence.data') }}?${buildQueryParams(tabKey, filters)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.text())
            .then(html => {
                cache[cacheKey] = html;
                container.innerHTML = html;

                const scripts = container.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });

                initTabScripts();
                setFilterStatus('Data terbaru sudah tampil.');
            })
            .catch(() => {
                container.innerHTML = '<div class="ii-empty text-danger">Gagal memuat data. Silakan coba lagi.</div>';
                setFilterStatus('Gagal memuat data.');
            });
    }

    function fetchInsights(tabKey) {
        const filters = collectFilters();

        fetch(`{{ route('inventory.warehouse_intelligence.insights') }}?${buildQueryParams(tabKey, filters)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.json())
            .then(payload => {
                if (payload && payload.html && insightsContainer) {
                    insightsContainer.innerHTML = payload.html;
                }
            })
            .catch(() => {
                // Insights bersifat pelengkap, jadi tidak mengganggu data utama.
            });
    }

    function refreshAll(force = true) {
        fetchTab(currentTab, force);
        fetchInsights(currentTab);
    }

    window.refreshIntel = function() {
        refreshAll(true);
    };

    tabs.forEach(btn => {
        btn.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('is-active'));
            this.classList.add('is-active');
            currentTab = this.getAttribute('data-tab-target');
            topDesc.textContent = this.getAttribute('data-desc');
            refreshAll(false);
        });
    });

    const triggerLiveRefresh = () => {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(() => refreshAll(true), 160);
    };

    document.querySelectorAll('.js-intel-filter').forEach(el => {
        el.addEventListener('change', triggerLiveRefresh);
    });

    const itemHiddenInput = document.querySelector('input[name="filter-item-id"]');
    if (itemHiddenInput) {
        itemHiddenInput.addEventListener('change', triggerLiveRefresh);
    }

    function initTabScripts() {
        const editBtns = document.querySelectorAll('.btn-edit-limit');
        editBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const sku = this.getAttribute('data-sku');
                const min = this.getAttribute('data-min');
                const max = this.getAttribute('data-max');
                const defMin = this.getAttribute('data-def-min');
                const defMax = this.getAttribute('data-def-max');

                document.getElementById('modal-item-id').value = id;
                document.getElementById('modal-sku-text').innerText = sku;

                const minInput = document.getElementById('modal-min-input');
                const maxInput = document.getElementById('modal-max-input');

                minInput.value = min;
                maxInput.value = max;
                minInput.placeholder = defMin;
                maxInput.placeholder = defMax;

                document.getElementById('modal-def-min-text').innerText = `Auto (Def): ${defMin}`;
                document.getElementById('modal-def-max-text').innerText = `Auto (Def): ${defMax}`;

                const modal = new bootstrap.Modal(document.getElementById('modalEditLimit'));
                modal.show();
            });
        });

        const sortableHeaders = document.querySelectorAll('.sortable-table th.sortable');
        sortableHeaders.forEach(th => {
            th.addEventListener('click', function() {
                const table = th.closest('table');
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const index = Array.from(th.parentNode.children).indexOf(th);
                const type = th.getAttribute('data-sort');
                let isAsc = th.classList.contains('asc');

                table.querySelectorAll('th.sortable').forEach(h => { h.classList.remove('asc', 'desc'); });

                isAsc = !isAsc;
                th.classList.toggle('asc', isAsc);
                th.classList.toggle('desc', !isAsc);

                rows.sort((a, b) => {
                    let aVal = a.children[index].innerText.trim();
                    let bVal = b.children[index].innerText.trim();

                    if (type === 'int' || type === 'float') {
                        aVal = parseFloat(aVal.replace(/[^0-9.-]+/g, '')) || 0;
                        bVal = parseFloat(bVal.replace(/[^0-9.-]+/g, '')) || 0;
                        return isAsc ? aVal - bVal : bVal - aVal;
                    }

                    return isAsc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
                });

                rows.forEach(row => tbody.appendChild(row));
            });
        });
    }

    document.getElementById('btn-save-modal-limit').addEventListener('click', function() {
        const id = document.getElementById('modal-item-id').value;
        const minVal = document.getElementById('modal-min-input').value;
        const maxVal = document.getElementById('modal-max-input').value;

        const originalText = this.innerText;
        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        this.disabled = true;

        fetch('{{ route("inventory.warehouse_intelligence.limits") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                item_id: id,
                rts_min_display: minVal !== '' ? parseInt(minVal) : null,
                rts_max_display: maxVal !== '' ? parseInt(maxVal) : null
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const modalEl = document.getElementById('modalEditLimit');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                refreshAll(true);
            } else {
                alert('Gagal menyimpan data.');
            }
        })
        .catch(() => {
            alert('Terjadi kesalahan koneksi.');
        })
        .finally(() => {
            this.innerText = originalText;
            this.disabled = false;
        });
    });

    fetchTab(currentTab);
    fetchInsights(currentTab);
});
</script>
@endpush

@push('head')
<style>
    .sd-btn, .sd-pill{ display:inline-flex; align-items:center; justify-content:center; gap:.35rem; border-radius:7px; border:1px solid rgba(148,163,184,.3); background:transparent; color:#111827; text-decoration:none; font-size:.76rem; padding:.28rem .6rem; min-height:34px; font-weight:800; cursor:pointer; }
    .sd-btn:hover{ background:rgba(148,163,184,.09); color:#111827; text-decoration:none; }
    .sd-primary{ background:#334155!important; border-color:#334155!important; color:#fff!important; }
    .nav-pills .nav-link { color: #64748b; font-weight: 700; }
    .nav-pills .nav-link:hover { color: #334155; }
    .nav-pills .nav-link.active { background-color: var(--shp-accent) !important; color: #fff !important; }
</style>
@endpush
