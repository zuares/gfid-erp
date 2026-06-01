{{-- resources/views/production/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Dashboard')

@php
    $tabs = [
        'ringkasan' => 'Ringkasan',
        'wip' => 'Alur WIP',
        'bottleneck' => 'Bottleneck/Pipeline',
        'outstanding' => 'Outstanding & Aging',
        'operator' => 'Performa Operator',
        'reject' => 'Reject',
        'item' => 'Output per Item',
    ];
@endphp

@push('head')
    <style>
        :root {
            --r: 16px;
            --b: rgba(148, 163, 184, .22);
            --muted: #6b7280;
            --shadow: 0 12px 30px rgba(15, 23, 42, .10), 0 0 0 1px rgba(15, 23, 42, .03);
        }

        .page-wrap {
            max-width: 1180px;
            margin: 0 auto;
            padding: 14px 12px 96px;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, .12) 0, rgba(45, 212, 191, .10) 28%, rgba(255, 255, 255, 1) 75%);
            border-radius: 18px;
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, .22) 0, rgba(45, 212, 191, .16) 26%, #020617 68%);
            border-radius: 18px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .card-main {
            background: var(--card);
            border: 1px solid var(--b);
            border-radius: var(--r);
            box-shadow: var(--shadow);
        }

        .btn-pill {
            border-radius: 999px !important;
            font-weight: 800;
        }

        /* KPI */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: .7rem;
        }

        .kpi {
            border: 1px solid var(--b);
            border-radius: 14px;
            padding: .75rem .85rem;
            background: var(--card);
        }

        .kpi .label {
            font-size: .72rem;
            font-weight: 800;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .kpi .val {
            font-size: 1.5rem;
            font-weight: 900;
            line-height: 1.1;
            margin-top: .15rem;
        }

        .kpi .sub {
            font-size: .72rem;
            color: var(--muted);
            margin-top: .1rem;
        }

        .kpi.accent-green {
            border-left: 4px solid #22c55e;
        }

        .kpi.accent-blue {
            border-left: 4px solid #3b82f6;
        }

        .kpi.accent-red {
            border-left: 4px solid #ef4444;
        }

        .kpi.accent-amber {
            border-left: 4px solid #f59e0b;
        }

        /* TABS */
        .tabs {
            display: flex;
            gap: .35rem;
            overflow-x: auto;
            padding-bottom: .25rem;
        }

        .tab-btn {
            border: 1px solid var(--b);
            background: var(--card);
            color: var(--muted);
            border-radius: 999px;
            padding: .4rem .9rem;
            font-weight: 800;
            font-size: .82rem;
            white-space: nowrap;
            cursor: pointer;
        }

        .tab-btn.active {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        /* TABLE */
        .tbl {
            width: 100%;
            border-collapse: collapse;
        }

        .tbl thead th {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
            font-weight: 900;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            padding: .55rem .5rem;
            text-align: left;
        }

        .tbl tbody td {
            border-top: 1px solid rgba(148, 163, 184, .10);
            padding: .5rem .5rem;
            vertical-align: middle;
        }

        .tbl .num {
            text-align: right;
        }

        .chip {
            display: inline-flex;
            align-items: baseline;
            gap: .25rem;
            font-size: .72rem;
            padding: .15rem .45rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, .14);
            border: 1px solid rgba(148, 163, 184, .18);
        }

        .badge-grade {
            display: inline-block;
            font-size: .68rem;
            font-weight: 900;
            padding: .12rem .5rem;
            border-radius: 999px;
        }

        .g-excellent {
            background: rgba(34, 197, 94, .15);
            color: #166534;
        }

        .g-good {
            background: rgba(59, 130, 246, .15);
            color: #1e40af;
        }

        .g-cukup {
            background: rgba(245, 158, 11, .16);
            color: #92400e;
        }

        .g-risk {
            background: rgba(239, 68, 68, .15);
            color: #991b1b;
        }

        .prog {
            height: 6px;
            border-radius: 999px;
            overflow: hidden;
            background: rgba(148, 163, 184, .22);
            min-width: 90px;
        }

        .prog>span {
            display: block;
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #2563eb, #38bdf8);
        }

        .stage-card {
            border: 1px solid var(--b);
            border-radius: 14px;
            padding: .8rem;
            background: var(--card);
        }

        .stage-card h3 {
            font-size: .78rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--muted);
            margin: 0 0 .2rem;
        }

        .stage-card .big {
            font-size: 1.6rem;
            font-weight: 900;
        }

        .bar-row {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-top: .35rem;
            font-size: .74rem;
        }

        .bar-track {
            flex: 1;
            height: 8px;
            border-radius: 999px;
            background: rgba(148, 163, 184, .18);
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #16a34a, #22c55e);
        }

        .age-pill {
            font-size: .68rem;
            font-weight: 800;
            padding: .1rem .45rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, .15);
            color: var(--muted);
        }

        .age-old {
            background: rgba(239, 68, 68, .15);
            color: #991b1b;
        }

        .filter-input {
            padding: .45rem .6rem;
            border: 1px solid var(--b);
            border-radius: 10px;
            background: var(--card);
            color: inherit;
            font-size: .85rem;
        }

        .empty {
            text-align: center;
            color: var(--muted);
            font-size: .82rem;
            padding: 1.4rem;
        }

        /* LAZY LOADING */
        .tab-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            color: var(--muted);
            font-size: .85rem;
            padding: 2.4rem 1rem;
        }

        .tab-spinner {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid rgba(148, 163, 184, .35);
            border-top-color: #2563eb;
            animation: prodspin .7s linear infinite;
        }

        @keyframes prodspin {
            to {
                transform: rotate(360deg);
            }
        }

        .filter-busy {
            opacity: .55;
            pointer-events: none;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap mono">

        {{-- HEADER + FILTER --}}
        <div class="card-main p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
                <div>
                    <h1 class="h5 mb-1 fw-bold" style="font-family:inherit;">Dashboard Produksi</h1>
                    <p class="text-muted small mb-0">
                        Periode <span id="periodLabel">{{ $periodLabel }}</span>
                    </p>
                </div>
            </div>

            <form id="filterForm" method="GET" action="{{ route('production.dashboard') }}"
                class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="d-block small text-muted mb-1">Dari</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="filter-input">
                </div>
                <div>
                    <label class="d-block small text-muted mb-1">Sampai</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="filter-input">
                </div>
                <div>
                    <label class="d-block small text-muted mb-1">Operator</label>
                    <select name="operator_id" class="filter-input">
                        <option value="">Semua</option>
                        @foreach ($operatorOptions as $op)
                            <option value="{{ $op->id }}" @selected($filters['operator_id'] == $op->id)>
                                {{ $op->code }} — {{ $op->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="d-block small text-muted mb-1">Produk (Kategori)</label>
                    <select name="category_id" class="filter-input">
                        <option value="">Semua</option>
                        @foreach ($categoryOptions as $cat)
                            <option value="{{ $cat->id }}" @selected($filters['category_id'] == $cat->id)>
                                {{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="d-block small text-muted mb-1">Varian (SKU)</label>
                    <select name="item_id" class="filter-input">
                        <option value="">Semua</option>
                        @foreach ($itemOptions as $it)
                            <option value="{{ $it->id }}" @selected($filters['item_id'] == $it->id)>
                                {{ $it->code }} — {{ $it->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-pill px-3">Terapkan</button>
                    <button type="button" id="filterReset" class="btn btn-outline-secondary btn-pill px-3"
                        data-from="{{ $defaults['date_from'] }}" data-to="{{ $defaults['date_to'] }}">Reset</button>
                </div>
            </form>

            <div class="d-flex gap-1 mt-2 flex-wrap">
                @php
                    $presets = [
                        '7 Hari' => [now()->subDays(6)->toDateString(), now()->toDateString()],
                        '30 Hari' => [now()->subDays(29)->toDateString(), now()->toDateString()],
                        'Bulan Ini' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
                    ];
                @endphp
                @foreach ($presets as $label => $range)
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-pill" data-preset
                        data-from="{{ $range[0] }}" data-to="{{ $range[1] }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        {{-- OVERVIEW: KPI operasional + ringkasan SKU (selalu tampil, refresh saat filter) --}}
        <div id="prodOverview">
            @include('production.dashboard.partials._overview')
        </div>

        {{-- TABS --}}
        <div class="tabs mb-3" id="prodTabs">
            @foreach ($tabs as $key => $label)
                <button type="button" class="tab-btn {{ $key === $initialTab ? 'active' : '' }}"
                    data-tab="{{ $key }}">{{ $label }}</button>
            @endforeach
        </div>

        {{-- PANES (lazy) --}}
        @foreach ($tabs as $key => $label)
            <div class="tab-pane {{ $key === $initialTab ? 'active' : '' }}" data-pane="{{ $key }}"
                data-loaded="{{ $key === $initialTab ? '1' : '0' }}">
                @if ($key === $initialTab)
                    @include($initialPartial)
                @else
                    <div class="tab-loading"><span class="tab-spinner"></span> Memuat…</div>
                @endif
            </div>
        @endforeach

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const DATA_URL = @json(route('production.dashboard.data'));
            const SERVER_INITIAL = @json($initialTab);

            const tabsEl = document.getElementById('prodTabs');
            const tabBtns = Array.from(tabsEl.querySelectorAll('.tab-btn'));
            const panes = Array.from(document.querySelectorAll('.tab-pane'));
            const form = document.getElementById('filterForm');
            const periodLabel = document.getElementById('periodLabel');
            const overviewEl = document.getElementById('prodOverview');
            const KEY = 'prodDashTab';

            const paneByName = (name) => panes.find(p => p.dataset.pane === name);
            const activeName = () => (tabBtns.find(b => b.classList.contains('active'))?.dataset.tab) || 'ringkasan';

            const loadingHTML = '<div class="tab-loading"><span class="tab-spinner"></span> Memuat…</div>';
            const errorHTML = (name) =>
                '<div class="empty">Gagal memuat data. ' +
                '<button type="button" class="btn btn-sm btn-outline-secondary btn-pill" data-retry="' + name + '">Coba lagi</button></div>';

            function currentFilters() {
                const fd = new FormData(form);
                const obj = {};
                for (const [k, v] of fd.entries())
                    if (v !== '' && v != null) obj[k] = v;
                return obj;
            }

            function buildUrl(tab) {
                const params = new URLSearchParams(currentFilters());
                params.set('tab', tab);
                return DATA_URL + '?' + params.toString();
            }

            function activate(name) {
                tabBtns.forEach(b => b.classList.toggle('active', b.dataset.tab === name));
                panes.forEach(p => p.classList.toggle('active', p.dataset.pane === name));
            }

            async function loadTab(name, {
                force = false
            } = {}) {
                const pane = paneByName(name);
                if (!pane) return;
                if (pane.dataset.loaded === '1' && !force) return;

                pane.dataset.loaded = '0';
                pane.innerHTML = loadingHTML;
                try {
                    const res = await fetch(buildUrl(name), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const json = await res.json();
                    pane.innerHTML = json.html;
                    pane.dataset.loaded = '1';
                    if (json.meta?.period_label && periodLabel) periodLabel.textContent = json.meta.period_label;
                    if (json.overview_html && overviewEl) overviewEl.innerHTML = json.overview_html;
                } catch (e) {
                    pane.innerHTML = errorHTML(name);
                }
            }

            // sync URL biar shareable / refresh-aman
            function syncUrl() {
                const params = new URLSearchParams(currentFilters());
                params.set('tab', activeName());
                history.replaceState(null, '', location.pathname + '?' + params.toString());
            }

            // apply filter → invalidate semua tab, reload tab aktif
            async function applyFilters() {
                panes.forEach(p => {
                    if (p.dataset.pane !== activeName()) {
                        p.dataset.loaded = '0';
                        p.innerHTML = loadingHTML;
                    }
                });
                form.classList.add('filter-busy');
                syncUrl();
                await loadTab(activeName(), {
                    force: true
                });
                form.classList.remove('filter-busy');
            }

            // tab click
            tabBtns.forEach(b => b.addEventListener('click', () => {
                const name = b.dataset.tab;
                activate(name);
                try {
                    localStorage.setItem(KEY, name);
                } catch (e) {}
                syncUrl();
                loadTab(name);
            }));

            // retry (delegasi)
            document.addEventListener('click', (e) => {
                const r = e.target.closest('[data-retry]');
                if (r) loadTab(r.dataset.retry, {
                    force: true
                });
            });

            // submit filter
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                applyFilters();
            });

            // presets
            document.querySelectorAll('[data-preset]').forEach(btn => {
                btn.addEventListener('click', () => {
                    form.querySelector('[name=date_from]').value = btn.dataset.from;
                    form.querySelector('[name=date_to]').value = btn.dataset.to;
                    applyFilters();
                });
            });

            // reset
            const resetBtn = document.getElementById('filterReset');
            if (resetBtn) resetBtn.addEventListener('click', () => {
                form.querySelector('[name=date_from]').value = resetBtn.dataset.from;
                form.querySelector('[name=date_to]').value = resetBtn.dataset.to;
                form.querySelector('[name=operator_id]').value = '';
                form.querySelector('[name=item_id]').value = '';
                const catSel = form.querySelector('[name=category_id]');
                if (catSel) catSel.value = '';
                applyFilters();
            });

            // restore tab tersimpan (kalau beda dari yang dirender server)
            try {
                const saved = localStorage.getItem(KEY);
                if (saved && saved !== SERVER_INITIAL && paneByName(saved)) {
                    activate(saved);
                    syncUrl();
                    loadTab(saved);
                }
            } catch (e) {}
        });
    </script>
@endpush
