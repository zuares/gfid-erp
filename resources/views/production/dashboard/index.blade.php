{{-- resources/views/production/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Dashboard')

@php
    $tabs = [
        'ringkasan' => 'Ringkasan',
        'siap-jahit' => 'Siap Jahit',
        'sedang-jahit' => 'Sedang Jahit',
        'setor-qc' => 'Setor & QC',
        'reject' => 'Reject',
        'penjahit' => 'Penjahit',
        'prioritas' => 'Prioritas',
    ];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        /* Lazy-load helpers (khusus dashboard produksi) */
        .prod-tab-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            color: var(--gf-muted);
            font-size: .85rem;
            padding: 2.4rem 1rem;
        }

        .prod-tab-spinner {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid rgba(148, 163, 184, .35);
            border-top-color: #2563eb;
            animation: prodspin .7s linear infinite;
        }

        @keyframes prodspin {
            to { transform: rotate(360deg); }
        }

        .prod-filter-busy { opacity: .55; pointer-events: none; }

        /* Select filter pill (penjahit / kategori / sku) — selaras header marketplace */
        .gf-header-select {
            min-height: 38px;
            max-width: 168px;
            border-radius: 999px !important;
            font-size: .78rem;
            font-weight: 700;
            padding-left: .85rem;
            padding-right: 1.9rem;
            border-color: rgba(15, 23, 42, .10);
            box-shadow: none !important;
            text-overflow: ellipsis;
        }

        @media (max-width: 576px) {
            .gf-header-select { max-width: 100%; width: 100%; }
        }

        .prod-empty {
            text-align: center;
            color: var(--gf-muted);
            font-size: .85rem;
            padding: 1.6rem;
        }

        /* Toolbar filter realtime (tab Siap Jahit) */
        .sj-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            align-items: center;
            margin-bottom: .9rem;
        }

        .sj-toolbar .form-control,
        .sj-toolbar .form-select {
            min-height: 36px;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 600;
            border-color: rgba(15, 23, 42, .12);
            box-shadow: none;
        }

        .sj-toolbar .sj-search {
            flex: 1 1 220px;
            min-width: 180px;
            max-width: 330px;
        }

        .sj-toolbar .form-select {
            width: auto;
            padding-right: 1.9rem;
        }

        .sj-check {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .78rem;
            font-weight: 700;
            color: #475569;
            white-space: nowrap;
            cursor: pointer;
        }

        .sj-count {
            margin-left: auto;
            font-size: .78rem;
            font-weight: 800;
            color: #475569;
            white-space: nowrap;
        }

        @media (max-width: 576px) {
            .sj-toolbar .sj-search { flex: 1 1 100%; max-width: none; }
            .sj-count { margin-left: 0; }
            .gf-hide-mobile { display: none !important; }
            /* kolom tersisa harus pas di layar — hilangkan min-width & padatkan */
            .gf-table-scroll-sticky .gf-clean-table { min-width: 0 !important; font-size: .76rem; }
            .gf-table-scroll-sticky .gf-clean-table th,
            .gf-table-scroll-sticky .gf-clean-table td { padding-left: .4rem; padding-right: .4rem; }
            .gf-table-scroll.gf-table-scroll-sticky { overflow-x: hidden; }
        }

        /* Scroll vertikal + thead sticky (override .gf-table-scroll yg flat) */
        .gf-table-scroll.gf-table-scroll-sticky {
            max-height: calc(100vh - 360px);
            min-height: 220px;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
        }
        .gf-table-scroll-sticky .gf-sticky-table thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #f8fafc;
            box-shadow: inset 0 -1px 0 #e6eaf0;
        }

        /* Badges & chips dipakai partial produksi */
        .gf-badge {
            display: inline-block;
            font-size: .68rem;
            font-weight: 800;
            padding: .14rem .5rem;
            border-radius: 999px;
            white-space: nowrap;
        }
        .gf-badge-red { background: rgba(239, 68, 68, .14); color: #b91c1c; }
        .gf-badge-amber { background: rgba(245, 158, 11, .16); color: #b45309; }
        .gf-badge-blue { background: rgba(37, 99, 235, .14); color: #1d4ed8; }
        .gf-badge-green { background: rgba(34, 197, 94, .16); color: #166534; }
        .gf-badge-muted { background: rgba(148, 163, 184, .16); color: #64748b; }

        .gf-chip {
            display: inline-flex;
            align-items: baseline;
            gap: .25rem;
            font-size: .74rem;
            padding: .12rem .45rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, .12);
            border: 1px solid rgba(148, 163, 184, .2);
        }

        .gf-bar-track {
            height: 8px;
            border-radius: 999px;
            background: rgba(148, 163, 184, .2);
            overflow: hidden;
            min-width: 90px;
        }
        .gf-bar-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #16a34a, #22c55e);
        }

        .gf-funnel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: .7rem;
        }
        .gf-funnel-step {
            border: 1px solid var(--gf-border);
            border-left: 3px solid #94a3b8;
            border-radius: 12px;
            padding: .7rem .8rem;
            background: #fff;
        }
        .gf-funnel-step.accent-blue { border-left-color: #2563eb; }
        .gf-funnel-step.accent-green { border-left-color: #16a34a; }
        .gf-funnel-label {
            font-size: .7rem; color: var(--gf-muted); font-weight: 800;
            text-transform: uppercase; letter-spacing: .03em;
        }
        .gf-funnel-val { font-size: 1.3rem; font-weight: 900; margin: .1rem 0 .4rem; }

        .gf-num { text-align: right; font-variant-numeric: tabular-nums; }
        tr.gf-row-warn > td { background: rgba(245, 158, 11, .06); }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Produksi"
        title="Dashboard Produksi"
        description="Pantau alur produksi harian: stok siap jahit, WIP penjahit, setoran &amp; QC, performa, dan prioritas.">

        <x-slot:actions>
            <div class="gf-dashboard-header-actions">
                <form id="filterForm" method="GET" action="{{ route('production.dashboard') }}"
                    class="gf-dashboard-header-filter" data-dashboard-filter>
                    <input type="hidden" name="date_from" value="{{ $filters['date_from'] }}" data-date-from>
                    <input type="hidden" name="date_to" value="{{ $filters['date_to'] }}" data-date-to>

                    <select class="form-select gf-header-period-select" data-period aria-label="Periode">
                        <option value="custom">Custom</option>
                        <option value="7">7 Hari</option>
                        <option value="30">30 Hari</option>
                        <option value="month">Bulan Ini</option>
                    </select>

                    <input type="text" class="form-control gf-header-date-input" autocomplete="off"
                        data-date-range aria-label="Rentang tanggal"
                        value="{{ $filters['date_from'] }} — {{ $filters['date_to'] }}">

                    <select name="operator_id" class="form-select gf-header-select" data-filter aria-label="Penjahit">
                        <option value="">Semua Penjahit</option>
                        @foreach ($operatorOptions as $op)
                            <option value="{{ $op->id }}" @selected($filters['operator_id'] == $op->id)>
                                {{ $op->code }} — {{ $op->name }}</option>
                        @endforeach
                    </select>

                    <select name="category_id" class="form-select gf-header-select" data-filter aria-label="Kategori">
                        <option value="">Semua Kategori</option>
                        @foreach ($categoryOptions as $cat)
                            <option value="{{ $cat->id }}" @selected($filters['category_id'] == $cat->id)>
                                {{ $cat->name }}</option>
                        @endforeach
                    </select>

                    <select name="item_id" class="form-select gf-header-select" data-filter aria-label="Varian (SKU)">
                        <option value="">Semua SKU</option>
                        @foreach ($itemOptions as $it)
                            <option value="{{ $it->id }}" @selected($filters['item_id'] == $it->id)>
                                {{ $it->code }} — {{ $it->name }}</option>
                        @endforeach
                    </select>

                    <a href="{{ route('production.dashboard') }}" class="btn btn-light border gf-header-icon-btn"
                        data-filter-reset data-from="{{ $defaults['date_from'] }}" data-to="{{ $defaults['date_to'] }}"
                        title="Reset filter">Reset</a>
                </form>
            </div>
        </x-slot:actions>

        <div class="gf-marketplace-dashboard gf-marketplace-clean-ui" data-dashboard-root>
            {{-- TABS --}}
            <div class="gf-marketplace-sticky-head">
                <div class="gf-marketplace-tabs" role="tablist" id="prodTabs">
                    @foreach ($tabs as $key => $label)
                        <button type="button" class="gf-marketplace-tab {{ $key === $initialTab ? 'is-active' : '' }}"
                            data-tab-target="{{ $key }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            {{-- PANES (lazy) --}}
            @foreach ($tabs as $key => $label)
                <section class="gf-marketplace-tab-panel" data-tab-panel="{{ $key }}"
                    data-loaded="{{ $key === $initialTab ? '1' : '0' }}" @if($key !== $initialTab) hidden @endif>
                    @if ($key === $initialTab)
                        @include($initialPartial)
                    @else
                        <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
                    @endif
                </section>
            @endforeach
        </div>
    </x-gf.page>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const DATA_URL = @json(route('production.dashboard.data'));
            const SERVER_INITIAL = @json($initialTab);
            const KEY = 'prodDashTab';

            const tabBtns = Array.from(document.querySelectorAll('#prodTabs .gf-marketplace-tab'));
            const panes = Array.from(document.querySelectorAll('[data-tab-panel]'));
            const form = document.getElementById('filterForm');
            const periodLabel = document.getElementById('periodLabel');

            const paneByName = (name) => panes.find(p => p.dataset.tabPanel === name);
            const activeName = () =>
                (tabBtns.find(b => b.classList.contains('is-active'))?.dataset.tabTarget) || SERVER_INITIAL;

            const loadingHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
            const errorHTML = (name) =>
                '<div class="prod-empty">Gagal memuat data. ' +
                '<button type="button" class="btn btn-sm btn-light border rounded-pill" data-retry="' + name + '">Coba lagi</button></div>';

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
                tabBtns.forEach(b => b.classList.toggle('is-active', b.dataset.tabTarget === name));
                panes.forEach(p => p.hidden = (p.dataset.tabPanel !== name));
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
                    if (json.meta?.period_label && periodLabel) periodLabel.textContent = json.meta.period_label;
                } catch (e) {
                    pane.innerHTML = errorHTML(name);
                }
            }

            function syncUrl() {
                const params = new URLSearchParams(currentFilters());
                params.set('tab', activeName());
                history.replaceState(null, '', location.pathname + '?' + params.toString());
            }

            async function applyFilters() {
                panes.forEach(p => {
                    if (p.dataset.tabPanel !== activeName()) {
                        p.dataset.loaded = '0';
                        p.innerHTML = loadingHTML;
                    }
                });
                form.classList.add('prod-filter-busy');
                syncUrl();
                await loadTab(activeName(), { force: true });
                form.classList.remove('prod-filter-busy');
            }

            tabBtns.forEach(b => b.addEventListener('click', () => {
                const name = b.dataset.tabTarget;
                activate(name);
                try { localStorage.setItem(KEY, name); } catch (e) {}
                syncUrl();
                loadTab(name);
            }));

            document.addEventListener('click', (e) => {
                const r = e.target.closest('[data-retry]');
                if (r) loadTab(r.dataset.retry, { force: true });
            });

            // Submit (mis. Enter di input tanggal) → realtime apply tanpa reload
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                applyFilters();
            });

            // ---- Filter realtime: flatpickr range + periode + select ----
            const fromEl = form.querySelector('[data-date-from]');
            const toEl = form.querySelector('[data-date-to]');
            const rangeEl = form.querySelector('[data-date-range]');
            const periodSel = form.querySelector('[data-period]');

            let fp = null;
            const ymd = (d) => (fp && d instanceof Date) ? fp.formatDate(d, 'Y-m-d') : d;

            if (rangeEl && window.GFID && window.GFID.initDateRange) {
                fp = window.GFID.initDateRange(rangeEl, {
                    defaultDate: [fromEl.value, toEl.value],
                    onClose: (sel) => {
                        if (sel.length === 2) {
                            fromEl.value = ymd(sel[0]);
                            toEl.value = ymd(sel[1]);
                            if (periodSel) periodSel.value = 'custom';
                            applyFilters();
                        }
                    }
                });
            }

            function detectPeriod() {
                if (!periodSel) return;
                const today = new Date();
                const minus = (n) => { const x = new Date(); x.setDate(x.getDate() - n); return x; };
                const tStr = ymd(today);
                let val = 'custom';
                if (toEl.value === tStr && fromEl.value === ymd(minus(6))) val = '7';
                else if (toEl.value === tStr && fromEl.value === ymd(minus(29))) val = '30';
                else if (toEl.value === tStr && fromEl.value === ymd(new Date(today.getFullYear(), today.getMonth(), 1))) val = 'month';
                periodSel.value = val;
            }
            detectPeriod();

            if (periodSel) periodSel.addEventListener('change', () => {
                const v = periodSel.value;
                if (v === 'custom') return;
                const today = new Date();
                let from;
                if (v === '7') { from = new Date(); from.setDate(from.getDate() - 6); }
                else if (v === '30') { from = new Date(); from.setDate(from.getDate() - 29); }
                else { from = new Date(today.getFullYear(), today.getMonth(), 1); }
                fromEl.value = ymd(from);
                toEl.value = ymd(today);
                if (fp) fp.setDate([from, today], false);
                applyFilters();
            });

            form.querySelectorAll('select[data-filter]').forEach(sel =>
                sel.addEventListener('change', applyFilters));

            const resetLink = form.querySelector('[data-filter-reset]');
            if (resetLink) resetLink.addEventListener('click', (e) => {
                e.preventDefault();
                fromEl.value = resetLink.dataset.from;
                toEl.value = resetLink.dataset.to;
                if (fp) fp.setDate([resetLink.dataset.from, resetLink.dataset.to], false);
                form.querySelectorAll('select[data-filter]').forEach(s => s.value = '');
                if (periodSel) periodSel.value = 'custom';
                detectPeriod();
                applyFilters();
            });

            // ---- Filter realtime tab "Siap Jahit" (client-side, instan) ----
            const idFmt = (n) => (n || 0).toLocaleString('id-ID');

            function applySjFilters(root) {
                if (!root) return;
                const table = root.querySelector('[data-sj-table]');
                if (!table) return;
                const tbody = table.querySelector('tbody');
                const q = (root.querySelector('[data-sj-search]')?.value || '').trim().toLowerCase();
                const grade = root.querySelector('[data-sj-grade]')?.value || '';
                const sort = root.querySelector('[data-sj-sort]')?.value || 'remaining-desc';

                const rows = Array.from(tbody.querySelectorAll('[data-sj-row]'));
                let shown = 0, sumRemaining = 0;
                rows.forEach(r => {
                    let ok = true;
                    if (q && !(r.dataset.search || '').includes(q)) ok = false;
                    if (grade && r.dataset.grade !== grade) ok = false;
                    r.hidden = !ok;
                    if (ok) { shown++; sumRemaining += parseFloat(r.dataset.remaining) || 0; }
                });

                const cmp = {
                    'remaining-desc': (a, b) => (+b.dataset.remaining) - (+a.dataset.remaining),
                    'hpp-desc': (a, b) => (+b.dataset.hpp) - (+a.dataset.hpp),
                    'stok-asc': (a, b) => (+a.dataset.stok) - (+b.dataset.stok),
                    'bundles-desc': (a, b) => (+b.dataset.bundles) - (+a.dataset.bundles),
                    'sku-asc': (a, b) => (a.dataset.sku || '').localeCompare(b.dataset.sku || ''),
                }[sort];
                if (cmp) rows.sort(cmp).forEach(r => tbody.appendChild(r));

                const cnt = root.querySelector('[data-sj-count]');
                if (cnt) cnt.textContent = idFmt(shown) + ' SKU · ' + idFmt(sumRemaining) + ' pcs';
                const empty = root.querySelector('[data-sj-empty]');
                if (empty) empty.hidden = (shown !== 0) || rows.length === 0;
            }

            const SJ_SEL = '[data-sj-search],[data-sj-grade],[data-sj-sort]';
            document.addEventListener('input', (e) => {
                if (!e.target.matches('[data-sj-search]')) return;
                applySjFilters(e.target.closest('[data-tab-panel]'));
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches(SJ_SEL)) return;
                applySjFilters(e.target.closest('[data-tab-panel]'));
            });

            // ---- Filter realtime tab "Sedang Jahit" (client-side, instan) ----
            function applySdFilters(root) {
                if (!root) return;
                const table = root.querySelector('[data-sd-table]');
                if (!table) return;
                const tbody = table.querySelector('tbody');
                const q = (root.querySelector('[data-sd-search]')?.value || '').trim().toLowerCase();
                const op = root.querySelector('[data-sd-operator]')?.value || '';
                const sort = root.querySelector('[data-sd-sort]')?.value || 'out-desc';

                const rows = Array.from(tbody.querySelectorAll('[data-sd-row]'));
                let shown = 0, sumOut = 0;
                rows.forEach(r => {
                    let ok = true;
                    if (q && !(r.dataset.search || '').includes(q)) ok = false;
                    if (op && r.dataset.operator !== op) ok = false;
                    r.hidden = !ok;
                    if (ok) { shown++; sumOut += parseFloat(r.dataset.outstanding) || 0; }
                });

                const cmp = {
                    'out-desc': (a, b) => (+b.dataset.outstanding) - (+a.dataset.outstanding),
                    'hpp-desc': (a, b) => (+b.dataset.hpp) - (+a.dataset.hpp),
                    'age-desc': (a, b) => (+b.dataset.age) - (+a.dataset.age),
                    'picked-desc': (a, b) => (+b.dataset.picked) - (+a.dataset.picked),
                }[sort];
                if (cmp) rows.sort(cmp).forEach(r => tbody.appendChild(r));

                const cnt = root.querySelector('[data-sd-count]');
                if (cnt) cnt.textContent = idFmt(shown) + ' baris · ' + idFmt(sumOut) + ' pcs';
                const empty = root.querySelector('[data-sd-empty]');
                if (empty) empty.hidden = (shown !== 0) || rows.length === 0;
            }

            const SD_SEL = '[data-sd-search],[data-sd-operator],[data-sd-sort]';
            document.addEventListener('input', (e) => {
                if (!e.target.matches('[data-sd-search]')) return;
                applySdFilters(e.target.closest('[data-tab-panel]'));
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches(SD_SEL)) return;
                applySdFilters(e.target.closest('[data-tab-panel]'));
            });

            // ---- Filter realtime tab "Setor & QC" (client-side, instan) ----
            function applyQcFilters(root) {
                if (!root) return;
                const table = root.querySelector('[data-qc-table]');
                if (!table) return;
                const tbody = table.querySelector('tbody');
                const q = (root.querySelector('[data-qc-search]')?.value || '').trim().toLowerCase();
                const op = root.querySelector('[data-qc-operator]')?.value || '';
                const sort = root.querySelector('[data-qc-sort]')?.value || 'date-desc';

                const rows = Array.from(tbody.querySelectorAll('[data-qc-row]'));
                let shown = 0, sumOk = 0;
                rows.forEach(r => {
                    let ok = true;
                    if (q && !(r.dataset.search || '').includes(q)) ok = false;
                    if (op && r.dataset.operator !== op) ok = false;
                    r.hidden = !ok;
                    if (ok) { shown++; sumOk += parseFloat(r.dataset.ok) || 0; }
                });

                const cmp = {
                    'date-desc': (a, b) => (a.dataset.date < b.dataset.date ? 1 : -1),
                    'ok-desc': (a, b) => (+b.dataset.ok) - (+a.dataset.ok),
                    'hpp-desc': (a, b) => (+b.dataset.hpp) - (+a.dataset.hpp),
                    'yield-asc': (a, b) => (+a.dataset.yield) - (+b.dataset.yield),
                }[sort];
                if (cmp) rows.sort(cmp).forEach(r => tbody.appendChild(r));

                const cnt = root.querySelector('[data-qc-count]');
                if (cnt) cnt.textContent = idFmt(shown) + ' setoran · ' + idFmt(sumOk) + ' pcs OK';
                const empty = root.querySelector('[data-qc-empty]');
                if (empty) empty.hidden = (shown !== 0) || rows.length === 0;
            }

            const QC_SEL = '[data-qc-search],[data-qc-operator],[data-qc-sort]';
            document.addEventListener('input', (e) => {
                if (!e.target.matches('[data-qc-search]')) return;
                applyQcFilters(e.target.closest('[data-tab-panel]'));
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches(QC_SEL)) return;
                applyQcFilters(e.target.closest('[data-tab-panel]'));
            });

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
