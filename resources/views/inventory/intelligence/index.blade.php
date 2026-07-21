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
        'procurement' => 'Laju jual, perkiraan 30 hari, dan saran pengadaan eksternal.',
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
        
        .ii-count { margin-left: auto; font-size: .78rem; font-weight: 700; color: #475569; white-space: nowrap; }

        /* Table */
        .table-list { margin-bottom: 0; font-size: .82rem; }
        .table-list thead th { border-bottom-width: 1px; font-size: .72rem; text-transform: none; letter-spacing: 0; color: #64748b; background: var(--card,#fff); padding: .6rem .75rem; white-space: nowrap; font-weight: 600; }
        body[data-theme="dark"] .table-list thead th { background: rgba(15, 23, 42, 0.98); color: #9ca3af; border-bottom-color: rgba(51, 65, 85, 0.6); }
        .table-list tbody td { vertical-align: middle; border-top-color: rgba(148, 163, 184, 0.16); padding: .6rem .75rem; }
        body[data-theme="dark"] .table-list tbody td { border-top-color: rgba(51, 65, 85, 0.85); }
        .table-list tbody tr:hover td { background: rgba(241, 245, 249, 0.4); }
        body[data-theme="dark"] .table-list tbody tr:hover td { background: rgba(30, 41, 59, 0.4); }

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
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: .75rem; margin-bottom: 1rem; }
        .kpi-card { background: var(--card, #fff); border: 1px solid var(--shp-border); border-radius: 8px; padding: 1rem; display: flex; flex-direction: column; justify-content: center; }
        body[data-theme="dark"] .kpi-card { background: var(--card, #0f172a); border-color: rgba(51, 65, 85, .85); }
        .kpi-label { font-size: .78rem; font-weight: 600; color: #64748b; margin-bottom: .25rem; }
        .kpi-val { font-size: 1.5rem; font-weight: 800; color: #0f172a; line-height: 1.2; }
        body[data-theme="dark"] .kpi-val { color: #f8fafc; }
        .kpi-note { font-size: .7rem; color: #94a3b8; margin-top: .35rem; }
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
            .ship-topbar{ flex-direction: column; align-items: stretch; margin-inline:-.5rem; padding:.65rem; }
            .ii-tabs-nav { width: 100%; justify-content: flex-start; overflow-x: auto; flex-wrap: nowrap; padding-bottom: .25rem; }
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
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div class="ii-tabs-nav m-0" role="tablist" id="iiTabs">
                @foreach ($tabs as $key => $label)
                    <button type="button" class="ii-tab-btn {{ $key === $initialTab ? 'is-active' : '' }}"
                        data-tab-target="{{ $key }}">{{ $label }}</button>
                @endforeach
            </div>
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

            <a href="{{ route('inventory.intelligence') }}" class="btn btn-ship-outline btn-pill bg-white" data-ii-reset title="Reset filter">Reset</a>
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
                    'ads-desc': (a, b) => (+b.dataset.ads) - (+a.dataset.ads),
                    'suggested-desc': (a, b) => (+b.dataset.suggested) - (+a.dataset.suggested),
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
            applyTableFilter(paneByName(SERVER_INITIAL));
            if (SERVER_INITIAL === 'trend') initTrend(paneByName('trend'));
        });
    </script>
@endpush
