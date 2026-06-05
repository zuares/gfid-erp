{{-- resources/views/inventory/intelligence/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Inventory Intelligence')

@php
    $tabs = [
        'summary' => 'Ringkasan',
        'health' => 'Kesehatan Stok',
        'forecast' => 'Forecast & Saran',
        'trend' => 'Tren Permintaan',
    ];
    $tabDesc = [
        'summary' => 'Ringkasan kesehatan stok seluruh SKU barang jadi.',
        'health' => 'Cover stok per SKU — mana yang menipis / kritis / stockout.',
        'forecast' => 'Laju jual, perkiraan 30 hari, dan saran jumlah produksi.',
        'trend' => 'Pergerakan penjualan harian per SKU dan arah naik/turun.',
    ];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .ii-tab-loading {
            display: flex; align-items: center; justify-content: center; gap: .6rem;
            color: var(--gf-muted); font-size: .85rem; padding: 2.4rem 1rem;
        }
        .ii-tab-spinner {
            width: 18px; height: 18px; border-radius: 50%;
            border: 2px solid rgba(148, 163, 184, .35); border-top-color: #2563eb;
            animation: iispin .7s linear infinite;
        }
        @keyframes iispin { to { transform: rotate(360deg); } }
        .ii-filter-busy { opacity: .55; pointer-events: none; }
        .ii-empty { text-align: center; color: var(--gf-muted); font-size: .85rem; padding: 1.6rem; }

        .ii-toolbar {
            display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; margin-bottom: .9rem;
        }
        .ii-toolbar .form-control, .ii-toolbar .form-select {
            min-height: 36px; border-radius: 999px; font-size: .8rem; font-weight: 600;
            border-color: rgba(15, 23, 42, .12); box-shadow: none;
        }
        .ii-toolbar .ii-search { flex: 1 1 220px; min-width: 180px; max-width: 330px; }
        .ii-toolbar .form-select { width: auto; padding-right: 1.9rem; }
        .ii-count { margin-left: auto; font-size: .78rem; font-weight: 800; color: #475569; white-space: nowrap; }
        .ii-actions { display: inline-flex; gap: .4rem; }
        .ii-actions .btn-sm { font-size: .75rem; font-weight: 700; padding: .35rem .8rem; }

        /* Status dot (kesehatan stok) */
        .ii-status { display: inline-flex; align-items: center; gap: .4rem; font-weight: 600; color: #475569; white-space: nowrap; }
        .ii-status-dot { width: 8px; height: 8px; border-radius: 50%; background: #cbd5e1; flex: none; }
        .ii-status-stockout .ii-status-dot { background: #dc2626; }
        .ii-status-kritis .ii-status-dot { background: #ef4444; }
        .ii-status-menipis .ii-status-dot { background: #f59e0b; }
        .ii-status-sehat .ii-status-dot { background: #16a34a; }
        .ii-status-no_demand .ii-status-dot { background: #94a3b8; }
        .ii-status-stockout, .ii-status-kritis { color: #b91c1c; }

        /* Tren Permintaan */
        .ii-trend-chart { margin-bottom: 1rem; }
        .ii-trend-chart-head { display: flex; justify-content: space-between; align-items: center; gap: .5rem; margin-bottom: .4rem; flex-wrap: wrap; }
        .ii-trend-title { font-weight: 800; font-size: .85rem; color: #0f172a; }
        .ii-trend-legend { font-size: .75rem; font-weight: 700; color: #475569; white-space: nowrap; }
        .ii-trend-canvas-wrap { position: relative; height: 200px; }
        [data-ii-trend-id] { cursor: pointer; }
        [data-ii-trend-id].is-active { background: rgba(37, 99, 235, .08); }
        .ii-dir { display: inline-flex; align-items: center; gap: .25rem; font-weight: 700; font-size: .78rem; white-space: nowrap; }
        .ii-dir-up { color: #16a34a; }
        .ii-dir-down { color: #dc2626; }
        .ii-dir-flat { color: #64748b; }
        .ii-dir-new { color: #2563eb; }

        /* Skor evaluasi (0–100): tinggi = paling butuh perhatian */
        .ii-score { font-weight: 800; }
        .ii-score-high { color: #dc2626; }
        .ii-score-mid { color: #f59e0b; }
        .ii-score-low { color: #16a34a; }

        @media (max-width: 576px) {
            .ii-toolbar .ii-search { flex: 1 1 100%; max-width: none; }
            .ii-count { margin-left: 0; }
            .gf-hide-mobile { display: none !important; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Inventory"
        title="Inventory Intelligence"
        :description="$tabDesc[$initialTab] ?? ''">

        <x-slot:actions>
            <div class="gf-dashboard-header-actions">
                <form id="iiFilterForm" method="GET" action="{{ route('inventory.intelligence') }}"
                    class="gf-dashboard-header-filter" data-ii-filter>
                    <select name="category_id" class="form-select gf-header-select" data-ii-filterctl aria-label="Kategori">
                        <option value="">Semua Kategori</option>
                        @foreach ($categoryOptions as $cat)
                            <option value="{{ $cat->id }}" @selected($filters['category_id'] == $cat->id)>
                                {{ $cat->name }}</option>
                        @endforeach
                    </select>

                    <select name="item_id" class="form-select gf-header-select" data-ii-filterctl aria-label="Varian (SKU)">
                        <option value="">Semua SKU</option>
                        @foreach ($itemOptions as $it)
                            <option value="{{ $it->id }}" @selected($filters['item_id'] == $it->id)>
                                {{ $it->code }} — {{ $it->name }}</option>
                        @endforeach
                    </select>

                    <a href="{{ route('inventory.intelligence') }}" class="btn btn-light border gf-header-icon-btn"
                        data-ii-reset title="Reset filter">Reset</a>
                </form>
            </div>
        </x-slot:actions>

        <div class="gf-marketplace-dashboard gf-marketplace-clean-ui" data-ii-root>
            <div class="gf-marketplace-sticky-head">
                <div class="gf-marketplace-tabs" role="tablist" id="iiTabs">
                    @foreach ($tabs as $key => $label)
                        <button type="button" class="gf-marketplace-tab {{ $key === $initialTab ? 'is-active' : '' }}"
                            data-tab-target="{{ $key }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            @foreach ($tabs as $key => $label)
                <section class="gf-marketplace-tab-panel" data-tab-panel="{{ $key }}"
                    data-loaded="{{ $key === $initialTab ? '1' : '0' }}" @if($key !== $initialTab) hidden @endif>
                    @if ($key === $initialTab)
                        @include($initialPartial)
                    @else
                        <div class="ii-tab-loading"><span class="ii-tab-spinner"></span> Memuat…</div>
                    @endif
                </section>
            @endforeach
        </div>
    </x-gf.page>
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

            const tabBtns = Array.from(document.querySelectorAll('#iiTabs .gf-marketplace-tab'));
            const panes = Array.from(document.querySelectorAll('[data-tab-panel]'));
            const form = document.getElementById('iiFilterForm');

            const idFmt = (n) => (n || 0).toLocaleString('id-ID');
            const paneByName = (name) => panes.find(p => p.dataset.tabPanel === name);
            const activeName = () =>
                (tabBtns.find(b => b.classList.contains('is-active'))?.dataset.tabTarget) || SERVER_INITIAL;

            const loadingHTML = '<div class="ii-tab-loading"><span class="ii-tab-spinner"></span> Memuat…</div>';
            const errorHTML = (name) =>
                '<div class="ii-empty">Gagal memuat data. ' +
                '<button type="button" class="btn btn-sm btn-light border rounded-pill" data-ii-retry="' + name + '">Coba lagi</button></div>';

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
                    applyTableFilter(pane);
                    if (name === 'trend') initTrend(pane);
                } catch (e) {
                    pane.innerHTML = errorHTML(name);
                }
            }

            async function applyFilters() {
                panes.forEach(p => {
                    if (p.dataset.tabPanel !== activeName()) {
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
            form.querySelectorAll('select[data-ii-filterctl]').forEach(sel =>
                sel.addEventListener('change', applyFilters));
            const resetLink = form.querySelector('[data-ii-reset]');
            if (resetLink) resetLink.addEventListener('click', (e) => {
                e.preventDefault();
                form.querySelectorAll('select[data-ii-filterctl]').forEach(s => s.value = '');
                applyFilters();
            });

            // Tab awal sudah dirender server-side → terapkan filter client-side-nya.
            applyTableFilter(paneByName(SERVER_INITIAL));
            if (SERVER_INITIAL === 'trend') initTrend(paneByName('trend'));
        });
    </script>
@endpush
