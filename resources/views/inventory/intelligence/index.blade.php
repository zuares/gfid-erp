@extends('layouts.app')

@section('title', 'Inventory Intelligence')

@php
    $tabs = [
        'summary' => 'Ringkasan',
        'health' => 'Kesehatan Stok',
        'forecast' => 'Saran Produksi (In-House)',
        'procurement' => 'Saran Pengadaan (FOB)',
        'trend' => 'Tren Permintaan',
    ];
    $tabDesc = [
        'summary' => 'Ringkasan kesehatan stok seluruh SKU barang jadi.',
        'health' => 'Cover stok per SKU — mana yang menipis / kritis / stockout.',
        'forecast' => 'Laju jual, perkiraan 30 hari, dan saran jumlah produksi.',
        'procurement' => 'Forecast 60 hari, stok ready + WIP proses, dan saran pengadaan eksternal (FOB).',
        'trend' => 'Pergerakan penjualan harian per SKU dan arah naik/turun.',
    ];
@endphp

@push('head')
    <style>
        :root{
            --shp-accent:#334155;
            --shp-accent-2:#1f2937;
            --shp-border:rgba(148,163,184,.18);
            --shp-border-strong:rgba(148,163,184,.30);
            --shp-muted:#64748b;
            --shp-text:#0f172a;
            --ii-accent:var(--shp-accent);
            --ii-accent-2:var(--shp-accent-2);
            --ii-border:var(--shp-border);
            --ii-border-strong:var(--shp-border-strong);
            --ii-muted:var(--shp-muted);
        }
        body[data-theme="dark"] { --shp-text:#f8fafc; }
        .page-wrap{ max-width:1040px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }

        .card-main{
            background: var(--card, #fff);
            border-radius: 8px;
            border: 1px solid var(--ii-border);
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
            align-items:center;
            gap:.6rem;
            flex-wrap:wrap;
            padding:.45rem .75rem;
            margin-inline:-.75rem;
            margin-bottom:.65rem;
            background:var(--card,#fff);
            border-bottom:1px solid var(--ii-border);
        }
        body[data-theme="dark"] .ship-topbar{ background:var(--card,#0f172a); }
        .title{ font-weight:750; font-size:1rem; letter-spacing:0; margin:0; }
        .sub{ color:var(--ii-muted); font-size:.78rem; }
        body[data-theme="dark"] .sub{ color:#9ca3af; }
        .controls{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
        .filter-label{ font-size:.8rem; color:#6b7280; font-weight: 600; }
        body[data-theme="dark"] .filter-label{ color:#9ca3af; }
        
        .gf-header-select {
            min-height: 36px; border-radius: 10px; font-size: .82rem; font-weight: 600;
            border: 1px solid rgba(148,163,184,.35); box-shadow: none; background-color: transparent;
            padding-left: .75rem; padding-right: 2rem;
        }

        .btn-pill{ border-radius:7px; padding-inline:.78rem; box-shadow:none!important; font-weight:600; min-height: 36px; display: inline-flex; align-items: center; }
        .btn-ship-primary{ background:var(--ii-accent)!important; border-color:var(--ii-accent)!important; color:#fff!important; }
        .btn-ship-primary:hover{ background:var(--ii-accent-2)!important; border-color:var(--ii-accent-2)!important; color:#fff!important; }
        .btn-ship-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
        .btn-ship-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }
        body[data-theme="dark"] .btn-ship-primary{ background:#e2e8f0!important; border-color:#e2e8f0!important; color:#0f172a!important; }
        body[data-theme="dark"] .btn-ship-outline{ background:rgba(255,255,255,.05)!important; border-color:rgba(255,255,255,.14)!important; color:#e2e8f0!important; }

        .ii-tabs-nav {
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            padding:.35rem;
            border-radius:16px;
            background:rgba(148,163,184,.08);
            border:1px solid rgba(148,163,184,.16);
            overflow-x:auto;
            max-width:100%;
            scrollbar-width:none;
            margin:0 0 1rem;
        }
        .ii-tabs-nav::-webkit-scrollbar{ display:none; }
        .ii-tab-btn {
            background: transparent;
            border: 0;
            color: #64748b;
            font-weight: 800;
            font-size: .8rem;
            padding: .62rem .92rem;
            border-radius: 12px;
            transition: color .15s ease;
            white-space: nowrap;
        }
        .ii-tab-btn:hover { color: #0f172a; background:rgba(255,255,255,.65); }
        .ii-tab-btn.is-active { background:var(--card,#fff); color:#0f172a; box-shadow:0 8px 18px rgba(15,23,42,.08); }
        body[data-theme="dark"] .ii-tabs-nav{ background:rgba(15,23,42,.72); border-color:rgba(51,65,85,.85); }
        body[data-theme="dark"] .ii-tab-btn { color:#94a3b8; }
        body[data-theme="dark"] .ii-tab-btn:hover{ color:#e2e8f0; background:rgba(255,255,255,.06); }
        body[data-theme="dark"] .ii-tab-btn.is-active { background:rgba(15,23,42,.98); color:#e2e8f0; }

        /* General partials UI */
        .ii-tab-loading { display: flex; align-items: center; justify-content: center; gap: .6rem; color: #64748b; font-size: .85rem; padding: 2.4rem 1rem; }
        .ii-tab-spinner { width: 18px; height: 18px; border-radius: 50%; border: 2px solid rgba(148, 163, 184, .35); border-top-color: #2563eb; animation: iispin .7s linear infinite; }
        @keyframes iispin { to { transform: rotate(360deg); } }
        .ii-filter-busy { opacity: .55; pointer-events: none; }
        .ii-empty { text-align: center; color: #64748b; font-size: .85rem; padding: 2.5rem 1rem; }

        /* Filter bar */
        .filter-bar{
            background:linear-gradient(180deg,#fff 0%,#f8fafc 100%);
            border:1px solid rgba(148,163,184,.15);
            border-radius:14px;
            padding:.85rem .95rem;
            margin-bottom:1rem;
            box-shadow:0 1px 2px rgba(15,23,42,.04);
        }
        body[data-theme="dark"] .filter-bar{ background:rgba(15,23,42,.92); border-color:rgba(51,65,85,.75); box-shadow:none; }
        .filter-bar .form-control, .filter-bar .form-select, .item-suggest-input{ border-radius:8px; font-size:.84rem; border-color: rgba(148,163,184,.3); min-height: 34px; }
        .filter-bar .form-control:focus, .filter-bar .form-select:focus, .item-suggest-input:focus { box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); border-color: #3b82f6; }
        
        .ii-count { margin-left: auto; font-size: .78rem; font-weight: 700; color: #475569; white-space: nowrap; }

        /* Table */
        .table-list { margin-bottom: 0; font-size: .82rem; }
        .table-list thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom-width: 1px;
            font-size: .68rem;
            text-transform: none;
            letter-spacing: 0;
            color: #64748b;
            background: var(--card-bg, #f8fafc);
            padding: .52rem .62rem;
            white-space: nowrap;
            font-weight: 700;
            box-shadow: 0 1px 0 rgba(148,163,184,.18);
        }
        body[data-theme="dark"] .table-list thead th {
            background: rgba(15, 23, 42, 0.98);
            color: #9ca3af;
            border-bottom-color: rgba(51, 65, 85, 0.6);
            box-shadow: 0 1px 0 rgba(51,65,85,.75);
        }
        .table-list tbody td { vertical-align: middle; border-top-color: rgba(148, 163, 184, 0.16); padding: .6rem .75rem; }
        body[data-theme="dark"] .table-list tbody td { border-top-color: rgba(51, 65, 85, 0.85); }
        .table-list tbody tr:hover td { background: rgba(241, 245, 249, 0.4); }
        body[data-theme="dark"] .table-list tbody tr:hover td { background: rgba(30, 41, 59, 0.4); }
        .ii-table-scroll{
            overflow: auto !important;
            scrollbar-gutter: stable both-edges;
        }
        .table-list thead th[data-ii-sort-key]{
            cursor: pointer;
            user-select: none;
            position: sticky;
            top: 0;
            z-index: 3;
            padding-right: 1.4rem;
            white-space: nowrap;
        }
        .table-list thead th[data-ii-sort-key]::after{
            content: '↕';
            position: absolute;
            right: .55rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: .62rem;
            color: #94a3b8;
            line-height: 1;
        }
        .table-list thead th[data-ii-sort-key][data-sort-dir="asc"]::after{ content: '▲'; color: #334155; }
        .table-list thead th[data-ii-sort-key][data-sort-dir="desc"]::after{ content: '▼'; color: #334155; }
        body[data-theme="dark"] .table-list thead th[data-ii-sort-key]::after{ color:#64748b; }
        body[data-theme="dark"] .table-list thead th[data-ii-sort-key][data-sort-dir="asc"]::after,
        body[data-theme="dark"] .table-list thead th[data-ii-sort-key][data-sort-dir="desc"]::after{ color:#cbd5e1; }

        .text-muted-ii { color: #64748b; font-size: .78rem; }
        .fw-semibold { font-weight: 600; }

        /* Badges */
        .badge-status{ border-radius:7px; padding:.16rem .48rem; font-size:.7rem; letter-spacing:0; text-transform:none; border:1px solid transparent; display:inline-flex; align-items:center; gap:.35rem; white-space:nowrap; font-weight: 500; }
        .badge-status::before{ content:''; width:7px; height:7px; border-radius:999px; display:inline-block; }
        .st-sehat { background: rgba(34, 197, 94, 0.10); color:#166534; border-color: rgba(34, 197, 94, 0.30); }
        .st-sehat::before { background: rgba(34, 197, 94, 0.95); }
        .st-menipis { background: rgba(245, 158, 11, 0.10); color:#b45309; border-color: rgba(245, 158, 11, 0.30); }
        .st-menipis::before { background: rgba(245, 158, 11, 0.95); }
        .st-kritis { background: rgba(239, 68, 68, 0.10); color:#991b1b; border-color: rgba(239, 68, 68, 0.30); }
        .st-kritis::before { background: rgba(239, 68, 68, 0.95); }
        .st-stockout { background: rgba(185, 28, 28, 0.10); color:#7f1d1d; border-color: rgba(185, 28, 28, 0.30); }
        .st-stockout::before { background: rgba(185, 28, 28, 0.95); }
        .st-no_demand { background: rgba(148, 163, 184, 0.10); color:#475569; border-color: rgba(148, 163, 184, 0.30); }
        .st-no_demand::before { background: rgba(100, 116, 139, 0.95); }

        body[data-theme="dark"] .st-sehat{ background: rgba(34, 197, 94, 0.20); color:#dcfce7; border-color: rgba(34, 197, 94, 0.55); }
        body[data-theme="dark"] .st-menipis{ background: rgba(245, 158, 11, 0.20); color:#fef3c7; border-color: rgba(245, 158, 11, 0.55); }
        body[data-theme="dark"] .st-kritis{ background: rgba(239, 68, 68, 0.18); color:#fecaca; border-color: rgba(239, 68, 68, 0.55); }
        body[data-theme="dark"] .st-stockout{ background: rgba(185, 28, 28, 0.25); color:#fee2e2; border-color: rgba(185, 28, 28, 0.65); }
        body[data-theme="dark"] .st-no_demand{ background: rgba(148, 163, 184, 0.18); color:#cbd5e1; border-color: rgba(148, 163, 184, 0.55); }

        /* KPI Cards */
        .kpi-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1rem; }
        .kpi-card {
            position:relative;
            background: linear-gradient(180deg,#fff 0%,#f8fafc 100%);
            border: 1px solid var(--ii-border);
            border-radius: 14px;
            padding: .78rem .85rem .72rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 1px 2px rgba(15,23,42,.04);
            overflow:hidden;
        }
        .kpi-card::before{
            content:'';
            position:absolute;
            inset:0 auto auto 0;
            width:100%;
            height:3px;
            background: linear-gradient(90deg,var(--kpi-start,#334155),var(--kpi-end,#94a3b8));
        }
        body[data-theme="dark"] .kpi-card { background: rgba(15,23,42,.92); border-color: rgba(51, 65, 85, .85); box-shadow:none; }
        .kpi-card-risk{ --kpi-start:#dc2626; --kpi-end:#f97316; }
        .kpi-card-stockout{ --kpi-start:#ea580c; --kpi-end:#fb923c; }
        .kpi-card-good{ --kpi-start:#16a34a; --kpi-end:#22c55e; }
        .kpi-card-ready{ --kpi-start:#2563eb; --kpi-end:#38bdf8; }
        .kpi-card-plan{ --kpi-start:#7c3aed; --kpi-end:#60a5fa; }
        .kpi-card-muted{ --kpi-start:#64748b; --kpi-end:#94a3b8; }
        .kpi-head{ display:flex; align-items:center; justify-content:space-between; gap:.35rem; }
        .kpi-head .kpi-label{ min-width:0; flex:1 1 auto; }
        .kpi-inline-pct{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            flex:0 0 auto;
            min-height:1.18rem;
            padding:.08rem .38rem;
            border-radius:999px;
            font-size:.55rem;
            font-weight:900;
            letter-spacing:-.01em;
            line-height:1;
            color:#b91c1c;
            background:#fee2e2;
            white-space:nowrap;
        }
        .kpi-label { font-size: .58rem; font-weight: 900; color: #64748b; margin-bottom: .16rem; text-transform: uppercase; letter-spacing:.07em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .kpi-val { font-size: 1.05rem; font-weight: 900; color: #0f172a; line-height: 1.08; letter-spacing:-.03em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        body[data-theme="dark"] .kpi-val { color: #f8fafc; }
        .kpi-note { font-size: .66rem; color: #94a3b8; margin-top: .22rem; }
        .kpi-sub-list {
            margin-top:.36rem;
            padding-top:.36rem;
            border-top:1px dashed rgba(148,163,184,.24);
        }
        .kpi-sub-item {
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:.45rem;
            margin-top:.24rem;
        }
        .kpi-sub-item:first-child { margin-top:0; }
        .kpi-sub-label {
            font-size:.56rem;
            color:#64748b;
            display:flex;
            align-items:flex-start;
            gap:.28rem;
            line-height:1.2;
            letter-spacing:-.01em;
            min-width:0;
        }
        .kpi-sub-val {
            font-size:.6rem;
            font-weight:900;
            line-height:1.15;
            color:var(--shp-text);
            text-align:right;
            white-space:nowrap;
        }
        .kpi-card-strong { border-color: rgba(239, 68, 68, .4); background: rgba(239, 68, 68, .02); }
        .kpi-card-strong .kpi-val { color: #b91c1c; }
        body[data-theme="dark"] .kpi-card-strong .kpi-val { color: #fca5a5; }

        /* Trend */
        .ii-trend-chart { margin-bottom: 1rem; padding: 1rem; }
        .ii-trend-chart-head { display: flex; justify-content: space-between; align-items: center; gap: .5rem; margin-bottom: .4rem; flex-wrap: wrap; }
        .ii-trend-title { font-weight: 800; font-size: .85rem; color: #0f172a; }
        body[data-theme="dark"] .ii-trend-title { color: #f8fafc; }
        .ii-trend-legend { font-size: .75rem; font-weight: 700; color: #475569; white-space: nowrap; }
        .ii-trend-canvas-wrap { position: relative; height: 200px; }
        [data-ii-trend-id] { cursor: pointer; }
        [data-ii-trend-id].is-active { background: rgba(37, 99, 235, .08); }
        .ii-dir { display: inline-flex; align-items: center; gap: .25rem; font-weight: 700; font-size: .78rem; white-space: nowrap; }
        .ii-dir-up { color: #16a34a; }
        .ii-dir-down { color: #dc2626; }
        .ii-dir-flat { color: #64748b; }
        .ii-dir-new { color: #2563eb; }
        .ii-score { font-weight: 800; }
        .ii-score-high { color: #dc2626; }
        .ii-score-mid { color: #f59e0b; }
        .ii-score-low { color: #16a34a; }

        @media (max-width: 768px) {
            .page-wrap{ padding:.5rem .5rem 4rem; }
            .ship-topbar{ flex-direction: column; align-items: stretch; }
            .ii-tabs-nav { width: 100%; justify-content: flex-start; overflow-x: auto; flex-wrap: nowrap; padding-bottom: .1rem; }
            .ii-tab-btn { white-space: nowrap; }
            .gf-hide-mobile { display: none !important; }
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 575.98px) {
            #iiFilterForm, .filter-bar > .d-flex { flex-direction: column; align-items: stretch; width: 100%; }
            #iiFilterForm > div { width: 100%; }
            .gf-header-select { width: 100%; }
            .filter-bar .ii-search { flex: 1 1 100%; max-width: none !important; }
            .ii-count { margin-left: 0; margin-top: .5rem; }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap" data-ii-root>
        <div class="ship-topbar">
            <div>
                <div class="title">Inventory Intelligence</div>
                <div class="sub gf-master-desc">{{ $tabDesc[$initialTab] ?? '' }}</div>
            </div>
            <div class="controls">
                <a href="{{ route('inventory.intelligence.export') }}?{{ http_build_query($filters) }}" class="btn btn-sm btn-ship-outline btn-pill">
                    <i class="bi bi-download"></i> Export
                </a>
                <a href="{{ route('inventory.intelligence.slip') }}?{{ http_build_query($filters) }}" class="btn btn-sm btn-ship-primary btn-pill">
                    <i class="bi bi-printer"></i> Slip
                </a>
            </div>
        </div>

        <div class="ii-tabs-nav" role="tablist" id="iiTabs">
            @foreach ($tabs as $key => $label)
                <button type="button" class="ii-tab-btn {{ $key === $initialTab ? 'is-active' : '' }}"
                    data-tab-target="{{ $key }}">{{ $label }}</button>
            @endforeach
        </div>

        <form id="iiFilterForm" method="GET" action="{{ route('inventory.intelligence') }}" class="d-flex flex-wrap gap-2 align-items-center m-0" style="display: none !important;" data-ii-filter>
            <div class="d-flex align-items-center gap-1">
                <span class="filter-label d-none d-lg-inline">Kategori</span>
                <select name="category_id" class="form-select form-select-sm gf-header-select bg-white" data-ii-filterctl aria-label="Kategori" style="min-width: 130px;">
                    <option value="">Semua Kategori</option>
                    @foreach ($categoryOptions as $cat)
                        <option value="{{ $cat->id }}" @selected($filters['category_id'] == $cat->id)>
                            {{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="d-flex align-items-center gap-1">
                <span class="filter-label d-none d-lg-inline">SKU</span>
                <div style="min-width: 220px; max-width: 300px; flex: 1;">
                    <x-item-suggest-input 
                        idName="item_id" 
                        type="finished_good"
                        placeholder="Cari SKU..."
                        :idValue="$filters['item_id'] ?? ''"
                        :displayValue="$selectedItemLabel ?? ''"
                    />
                </div>
            </div>

            <a href="{{ route('inventory.intelligence') }}" class="btn btn-ship-outline btn-pill" data-ii-reset title="Reset filter">Reset</a>
        </form>

        @foreach ($tabs as $key => $label)
            <section data-tab-panel="{{ $key }}" data-loaded="{{ $key === $initialTab ? '1' : '0' }}" @if($key !== $initialTab) hidden @endif>
                @if ($key === $initialTab)
                    @include($initialPartial)
                @else
                    <div class="card-main">
                        <div class="ii-tab-loading"><span class="ii-tab-spinner"></span> Memuat…</div>
                    </div>
                @endif
            </section>
        @endforeach
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const DATA_URL = @json(route('inventory.intelligence.data'));
            const SLIP_URL = @json(route('inventory.intelligence.slip'));
            const EXPORT_URL = @json(route('inventory.intelligence.export'));
            const SERVER_INITIAL = @json($initialTab);
            const TAB_DESC = @json($tabDesc);
            const descEl = document.querySelector('.gf-master-desc');
            const setDesc = (name) => { if (descEl && TAB_DESC[name]) descEl.textContent = TAB_DESC[name]; };

            const tabBtns = Array.from(document.querySelectorAll('#iiTabs .ii-tab-btn'));
            const panes = Array.from(document.querySelectorAll('[data-tab-panel]'));
            const form = document.getElementById('iiFilterForm');

            const idFmt = (n) => (n || 0).toLocaleString('id-ID');
            const paneByName = (name) => panes.find(p => p.dataset.tabPanel === name);
            const activeName = () =>
                (tabBtns.find(b => b.classList.contains('is-active'))?.dataset.tabTarget) || SERVER_INITIAL;

            const loadingHTML = '<div class="card-main"><div class="ii-tab-loading"><span class="ii-tab-spinner"></span> Memuat…</div></div>';
            const errorHTML = (name) =>
                '<div class="card-main"><div class="ii-empty">Gagal memuat data. ' +
                '<button type="button" class="btn btn-sm btn-light border rounded-pill" data-ii-retry="' + name + '">Coba lagi</button></div></div>';

            function currentFilters() {
                const fd = new FormData(form);
                const obj = {};
                for (const [k, v] of fd.entries()) if (v !== '' && v != null) obj[k] = v;
                return obj;
            }
            function buildUrl(tab) {
                const params = new URLSearchParams(currentFilters());
                params.set('tab', tab);
                return DATA_URL + '?' + params.toString();
            }
            function activate(name) {
                tabBtns.forEach(b => b.classList.toggle('is-active', b.dataset.tabTarget === name));
                panes.forEach(p => p.hidden = (p.dataset.tabPanel !== name));
                setDesc(name);
                
                // Move global filter to the active tab's placeholder
                const filterWrapper = document.getElementById('iiFilterForm');
                const activePane = paneByName(name);
                if (filterWrapper && activePane) {
                    const placeholder = activePane.querySelector('.filter-placeholder');
                    if (placeholder) {
                        filterWrapper.style.setProperty('display', 'flex', 'important');
                        placeholder.appendChild(filterWrapper);
                    }
                }
            }
            function syncUrl() {
                const params = new URLSearchParams(currentFilters());
                params.set('tab', activeName());
                history.replaceState(null, '', location.pathname + '?' + params.toString());
            }

            async function loadTab(name, { force = false } = {}) {
                const pane = paneByName(name);
                if (!pane) return;
                if (pane.dataset.loaded === '1' && !force) return;
                
                const filterWrapper = document.getElementById('iiFilterForm');
                if (filterWrapper && pane.contains(filterWrapper)) {
                    document.body.appendChild(filterWrapper);
                }
                
                pane.dataset.loaded = '0';
                pane.innerHTML = loadingHTML;
                try {
                    const res = await fetch(buildUrl(name), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const json = await res.json();
                    pane.innerHTML = json.html;
                    pane.dataset.loaded = '1';
                    
                    const placeholder = pane.querySelector('.filter-placeholder');
                    if (placeholder && filterWrapper) {
                        placeholder.appendChild(filterWrapper);
                    }
                    
                    if (name === 'summary') initSummary(pane);
                    initSortableTables(pane);
                    applyTableFilter(pane);
                    if (name === 'trend') initTrend(pane);
                } catch (e) {
                    pane.innerHTML = errorHTML(name);
                }
            }

            async function applyFilters() {
                const filterWrapper = document.getElementById('iiFilterForm');
                panes.forEach(p => {
                    if (p.dataset.tabPanel !== activeName()) {
                        if (filterWrapper && p.contains(filterWrapper)) document.body.appendChild(filterWrapper);
                        p.dataset.loaded = '0';
                        p.innerHTML = loadingHTML;
                    }
                });
                form.classList.add('ii-filter-busy');
                syncUrl();
                await loadTab(activeName(), { force: true });
                form.classList.remove('ii-filter-busy');
            }

            function setSummarySubtab(pane, tab) {
                if (!pane) return;
                pane.querySelectorAll('[data-summary-subtab-target]').forEach(btn => {
                    btn.classList.toggle('is-active', btn.dataset.summarySubtabTarget === tab);
                });
                pane.querySelectorAll('[data-summary-subpanel]').forEach(panel => {
                    panel.hidden = panel.dataset.summarySubpanel !== tab;
                });
            }

            function initSummary(pane) {
                if (!pane) return;
                const active = pane.querySelector('[data-summary-subtab-target].is-active')?.dataset.summarySubtabTarget || 'critical';
                setSummarySubtab(pane, active);
            }

            function switchPriorityTab(tab) {
                const pane = document.querySelector('[data-tab-panel="summary"]');
                if (!pane) return;

                pane.querySelectorAll('#tab-btn-own, #tab-btn-ext').forEach(btn => btn.classList.remove('is-active'));
                pane.querySelectorAll('#tab-content-own, #tab-content-ext').forEach(el => {
                    el.style.setProperty('display', 'none', 'important');
                });

                const btn = pane.querySelector('#tab-btn-' + tab);
                const panel = pane.querySelector('#tab-content-' + tab);
                if (btn) btn.classList.add('is-active');
                if (panel) panel.style.setProperty('display', 'flex', 'important');

                const linkAll = pane.querySelector('#link-prioritas-all');
                if (linkAll) {
                    if (tab === 'own') {
                        linkAll.setAttribute('onclick', "document.querySelector('[data-tab-target=\\'forecast\\']').click();");
                    } else {
                        linkAll.setAttribute('onclick', "document.querySelector('[data-tab-target=\\'procurement\\']').click();");
                    }
                }
            }
            window.switchPriorityTab = switchPriorityTab;

            function readSortValue(row, key, type) {
                const raw = row.dataset[key] ?? '';
                if (type === 'text') return String(raw).toLowerCase();
                if (type === 'status') {
                    const rank = { stockout: 0, kritis: 1, menipis: 2, sehat: 3, no_demand: 4 };
                    return rank[raw] ?? 99;
                }
                if (type === 'direction') {
                    const rank = { down: 0, flat: 1, up: 2, new: 3 };
                    return rank[raw] ?? 99;
                }
                const num = parseFloat(String(raw).replace(/,/g, ''));
                return Number.isFinite(num) ? num : 0;
            }

            function sortTable(table, key, dir = 'desc', type = 'number') {
                const tbody = table.querySelector('tbody');
                if (!tbody) return;
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const factor = dir === 'asc' ? 1 : -1;

                rows.sort((a, b) => {
                    const av = readSortValue(a, key, type);
                    const bv = readSortValue(b, key, type);
                    if (type === 'text') {
                        return String(av).localeCompare(String(bv), 'id', { sensitivity: 'base' }) * factor;
                    }
                    return (av - bv) * factor;
                });

                rows.forEach(row => tbody.appendChild(row));

                table.dataset.sortKey = key;
                table.dataset.sortDir = dir;
                table.querySelectorAll('th[data-ii-sort-key]').forEach(th => {
                    if (th.dataset.iiSortKey === key) {
                        th.dataset.sortDir = dir;
                    } else {
                        delete th.dataset.sortDir;
                    }
                });
            }

            function initSortableTables(root) {
                if (!root) return;
                root.querySelectorAll('table[data-ii-table]').forEach(table => {
                    if (table.dataset.iiSortableInit === '1') {
                        const key = table.dataset.sortKey;
                        const dir = table.dataset.sortDir;
                        if (key && dir) sortTable(table, key, dir, table.dataset.sortType || 'number');
                        return;
                    }

                    table.dataset.iiSortableInit = '1';
                    const defaultKey = table.dataset.iiDefaultSort || table.querySelector('th[data-ii-sort-key]')?.dataset.iiSortKey;
                    const defaultDir = table.dataset.iiDefaultDir || 'desc';
                    const defaultType = table.dataset.iiDefaultType || table.querySelector(`th[data-ii-sort-key="${defaultKey}"]`)?.dataset.iiSortType || 'number';

                    table.querySelectorAll('th[data-ii-sort-key]').forEach(th => {
                        th.addEventListener('click', () => {
                            const key = th.dataset.iiSortKey;
                            const type = th.dataset.iiSortType || 'number';
                            const currentKey = table.dataset.sortKey;
                            const currentDir = table.dataset.sortDir || defaultDir;
                            const nextDir = currentKey === key
                                ? (currentDir === 'asc' ? 'desc' : 'asc')
                                : ((type === 'text' || type === 'status') ? 'asc' : defaultDir);
                            sortTable(table, key, nextDir, type);
                        });
                    });

                    if (defaultKey) {
                        sortTable(table, defaultKey, defaultDir, defaultType);
                    }
                });
            }

            // ---- Client-side filter tabel (search / status / sort) ----
            function applyTableFilter(root) {
                if (!root) return;
                const table = root.querySelector('[data-ii-table]');
                if (!table) return;
                const tbody = table.querySelector('tbody');
                const q = (root.querySelector('[data-ii-search]')?.value || '').trim().toLowerCase();
                const status = root.querySelector('[data-ii-status]')?.value || '';
                const sort = root.querySelector('[data-ii-sort]')?.value || '';

                const rows = Array.from(tbody.querySelectorAll('[data-ii-row]'));
                let shown = 0;
                rows.forEach(r => {
                    let ok = true;
                    if (q && !(r.dataset.search || '').includes(q)) ok = false;
                    if (status && r.dataset.status !== status) ok = false;
                    r.hidden = !ok;
                    if (ok) shown++;
                });

                const cmp = {
                    'sku-asc': (a, b) => (a.dataset.sku || '').localeCompare(b.dataset.sku || ''),
                    'cover-asc': (a, b) => (+a.dataset.cover) - (+b.dataset.cover),
                    'cover-desc': (a, b) => (+b.dataset.cover) - (+a.dataset.cover),
                    'ads-desc': (a, b) => (+b.dataset.ads) - (+a.dataset.ads),
                    'suggested-desc': (a, b) => (+b.dataset.suggested) - (+a.dataset.suggested),
                    'suggested-asc': (a, b) => (+a.dataset.suggested) - (+b.dataset.suggested),
                    'trend-desc': (a, b) => (+b.dataset.delta) - (+a.dataset.delta),
                    'trend-asc': (a, b) => (+a.dataset.delta) - (+b.dataset.delta),
                    'score-desc': (a, b) => (+b.dataset.score) - (+a.dataset.score),
                    'wads-desc': (a, b) => (+b.dataset.wads) - (+a.dataset.wads),
                }[sort];
                if (cmp) rows.sort(cmp).forEach(r => tbody.appendChild(r));

                const cnt = root.querySelector('[data-ii-count]');
                if (cnt) cnt.textContent = idFmt(shown) + ' SKU';
                const empty = root.querySelector('[data-ii-empty]');
                if (empty) empty.hidden = (shown !== 0) || rows.length === 0;
            }

            const II_SEL = '[data-ii-search],[data-ii-status],[data-ii-sort]';
            document.addEventListener('input', (e) => {
                if (!e.target.matches('[data-ii-search]')) return;
                applyTableFilter(e.target.closest('[data-tab-panel]'));
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches(II_SEL)) return;
                applyTableFilter(e.target.closest('[data-tab-panel]'));
            });

            // ---- Tab switching ----
            tabBtns.forEach(b => b.addEventListener('click', () => {
                const name = b.dataset.tabTarget;
                activate(name);
                syncUrl();
                loadTab(name);
            }));
            document.addEventListener('click', (e) => {
                const summaryBtn = e.target.closest('[data-summary-subtab-target]');
                if (summaryBtn) {
                    const pane = summaryBtn.closest('[data-tab-panel="summary"]');
                    if (pane) setSummarySubtab(pane, summaryBtn.dataset.summarySubtabTarget);
                    return;
                }
                const r = e.target.closest('[data-ii-retry]');
                if (r) loadTab(r.dataset.iiRetry, { force: true });
            });

            // ---- Tren Permintaan: grafik garis detail per SKU (Chart.js) ----
            function initTrend(pane) {
                if (!pane || typeof Chart === 'undefined') return;
                const dataEl = pane.querySelector('[data-ii-trend-data]');
                const canvas = pane.querySelector('[data-ii-trend-canvas]');
                if (!dataEl || !canvas) return;

                let payload;
                try { payload = JSON.parse(dataEl.textContent); } catch (e) { return; }
                if (pane._trendChart) { pane._trendChart.destroy(); pane._trendChart = null; }

                const titleEl = pane.querySelector('[data-ii-trend-title]');
                pane._trendDraw = function (id) {
                    const it = payload.items[id];
                    if (!it) return;
                    if (titleEl) titleEl.textContent = it.sku + ' — ' + it.product;
                    const ds = {
                        labels: payload.labels,
                        datasets: [{
                            data: it.series, borderColor: '#2563eb',
                            backgroundColor: 'rgba(37,99,235,.12)', fill: true,
                            tension: .3, pointRadius: 0, borderWidth: 2,
                        }],
                    };
                    if (pane._trendChart) {
                        pane._trendChart.data = ds;
                        pane._trendChart.update();
                    } else {
                        pane._trendChart = new Chart(canvas, {
                            type: 'line', data: ds,
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false }, tooltip: { intersect: false, mode: 'index' } },
                                scales: {
                                    x: { ticks: { maxTicksLimit: 8, font: { size: 10 } }, grid: { display: false } },
                                    y: { beginAtZero: true, ticks: { font: { size: 10 } }, grid: { color: 'rgba(148,163,184,.15)' } },
                                },
                            },
                        });
                    }
                    pane.querySelectorAll('[data-ii-trend-id]').forEach(tr =>
                        tr.classList.toggle('is-active', tr.dataset.iiTrendId === String(id)));
                };

                const first = pane.querySelector('[data-ii-trend-id]');
                if (first) pane._trendDraw(first.dataset.iiTrendId);
            }
            document.addEventListener('click', (e) => {
                const tr = e.target.closest('[data-ii-trend-id]');
                if (!tr) return;
                const pane = tr.closest('[data-tab-panel]');
                if (pane && pane._trendDraw) pane._trendDraw(tr.dataset.iiTrendId);
            });

            // ---- Production Action: slip cetak + export CSV (ikut filter aktif) ----
            function actionUrl(base) {
                const params = new URLSearchParams(currentFilters());
                if (activeName() === 'procurement') params.set('source', 'external');
                if (activeName() === 'forecast') params.set('source', 'own');
                return base + (params.toString() ? '?' + params.toString() : '');
            }
            document.addEventListener('click', (e) => {
                if (e.target.closest('[data-ii-slip]')) {
                    window.open(actionUrl(SLIP_URL), '_blank', 'noopener');
                } else if (e.target.closest('[data-ii-export]')) {
                    window.location.assign(actionUrl(EXPORT_URL));
                }
            });

            // ---- Server-side filter (kategori / SKU) ----
            form.addEventListener('submit', (e) => { e.preventDefault(); applyFilters(); });
            form.querySelectorAll('select[data-ii-filterctl], input[name="item_id"]').forEach(el =>
                el.addEventListener('change', applyFilters));
            const resetLink = form.querySelector('[data-ii-reset]');
            if (resetLink) resetLink.addEventListener('click', (e) => {
                e.preventDefault();
                form.querySelectorAll('select[data-ii-filterctl], input[name="item_id"]').forEach(s => s.value = '');
                form.querySelectorAll('.item-suggest-input').forEach(s => s.value = '');
                applyFilters();
            });

            // Tab awal sudah dirender server-side → terapkan filter client-side-nya.
            activate(SERVER_INITIAL);
            initSummary(paneByName('summary'));
            initSortableTables(paneByName(SERVER_INITIAL));
            applyTableFilter(paneByName(SERVER_INITIAL));
            if (SERVER_INITIAL === 'trend') initTrend(paneByName('trend'));
        });
    </script>
@endpush
