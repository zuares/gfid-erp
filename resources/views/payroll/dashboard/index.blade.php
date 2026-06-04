{{-- resources/views/payroll/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Payroll • Dashboard')

@php
    $tabs = [
        'keseluruhan' => 'Keseluruhan',
        'penjahit' => 'Penjahit',
        'cutting' => 'Cutting',
    ];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        /* Lazy-load helpers (dashboard payroll) */
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

        /* Select filter pill (operator / kategori / sku) — selaras header marketplace */
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

        /* Toolbar filter realtime */
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
            .gf-table-scroll-sticky .gf-clean-table { min-width: 0 !important; font-size: .76rem; }
            .gf-table-scroll-sticky .gf-clean-table th,
            .gf-table-scroll-sticky .gf-clean-table td { padding-left: .4rem; padding-right: .4rem; }
            .gf-table-scroll.gf-table-scroll-sticky { overflow-x: hidden; }
        }

        /* Scroll vertikal + thead sticky */
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

        /* Badges & chips dipakai partial */
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

        .gf-num { text-align: right; font-variant-numeric: tabular-nums; }

        /* Sel tanggal 2-baris */
        .gf-datecell { display: flex; flex-direction: column; line-height: 1.18; }
        .gf-datecell-d { font-weight: 600; color: var(--gf-dark); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .gf-datecell-sub { font-size: .68rem; color: var(--gf-muted); font-variant-numeric: tabular-nums; white-space: nowrap; }

        /* Tombol Cetak Slip */
        .gf-slip-btn { display: inline-flex; align-items: center; gap: .35rem; padding: .4rem .85rem;
            border-radius: 999px; background: #0f172a; color: #fff; font-weight: 600; font-size: .8rem;
            text-decoration: none; border: 1px solid #0f172a; white-space: nowrap; }
        .gf-slip-btn::before { content: "🖨"; font-size: .85em; }
        .gf-slip-btn:hover { background: #1e293b; color: #fff; }
        /* Footer tabel (tempat tombol Cetak Slip) */
        .gf-table-foot { display: flex; align-items: center; justify-content: flex-end; gap: .75rem;
            margin-top: .85rem; padding-top: .75rem; border-top: 1px solid #eef0f4; }
        .gf-table-foot-hint { font-size: .8rem; color: #94a3b8; }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Payroll"
        title="Dashboard Payroll"
        description="Borongan jahit &amp; cutting · upah per operator">

        <x-slot:actions>
            <div class="gf-dashboard-header-actions">
                <form id="filterForm" method="GET" action="{{ route('payroll.dashboard') }}"
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

                    <select name="operator_id" class="form-select gf-header-select" data-filter aria-label="Operator">
                        <option value="">Semua Operator</option>
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

                    <a href="{{ route('payroll.dashboard') }}" class="btn btn-light border gf-header-icon-btn"
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
            const DATA_URL = @json(route('payroll.dashboard.data'));
            const SLIP_URL = @json(route('payroll.dashboard.slip'));
            const SERVER_INITIAL = @json($initialTab);
            const KEY = 'payrollDashTab';

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
                    if (typeof initTabFilters === 'function') initTabFilters(name, pane);
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

            const idFmt = (n) => (n || 0).toLocaleString('id-ID');
            const idRp = (n) => 'Rp ' + idFmt(Math.round(n || 0));

            // Tombol "Cetak Slip": tampil saat satu operator + modul terpilih.
            function gfUpdateSlip(linkEl, operatorCode, module) {
                if (!linkEl) return;
                const hint = linkEl.closest('.gf-table-foot')?.querySelector('.gf-table-foot-hint');
                if (operatorCode && operatorCode !== '-' && module) {
                    const p = new URLSearchParams(currentFilters());
                    p.delete('operator_id');
                    p.set('module', module);
                    p.set('operator', operatorCode);
                    linkEl.href = SLIP_URL + '?' + p.toString();
                    linkEl.hidden = false;
                    if (hint) hint.hidden = true;
                } else {
                    linkEl.hidden = true;
                    linkEl.removeAttribute('href');
                    if (hint) hint.hidden = false;
                }
            }

            // ---- Tab "Keseluruhan" (gabungan jahit + cutting) ----
            function applyKsFilters(root) {
                if (!root) return;
                const table = root.querySelector('[data-ks-table]');
                if (!table) return;
                const tbody = table.querySelector('tbody');
                const q = (root.querySelector('[data-ks-search]')?.value || '').trim().toLowerCase();
                const role = root.querySelector('[data-ks-role]')?.value || '';
                const operator = root.querySelector('[data-ks-operator]')?.value || '';
                const kind = root.querySelector('[data-ks-kind]')?.value || '';
                const sort = root.querySelector('[data-ks-sort]')?.value || 'date-desc';

                const rows = Array.from(tbody.querySelectorAll('[data-ks-row]'));
                let shown = 0, sumQty = 0, sumProj = 0, sumJahit = 0, sumCutting = 0; const ops = new Set();
                rows.forEach(r => {
                    let ok = true;
                    if (q && !(r.dataset.search || '').includes(q)) ok = false;
                    if (role && r.dataset.role !== role) ok = false;
                    if (operator && r.dataset.operator !== operator) ok = false;
                    if (kind && r.dataset.kind !== kind) ok = false;
                    r.hidden = !ok;
                    if (ok) {
                        shown++;
                        ops.add(r.dataset.operator);
                        sumQty += parseFloat(r.dataset.qty) || 0;
                        sumProj += parseFloat(r.dataset.proj) || 0;
                        const amt = parseFloat(r.dataset.amount) || 0;
                        if (r.dataset.module === 'sewing') sumJahit += amt;
                        else if (r.dataset.module === 'cutting') sumCutting += amt;
                    }
                });

                const cmp = {
                    'date-desc': (a, b) => (a.dataset.date < b.dataset.date ? 1 : -1),
                    'qty-desc': (a, b) => (+b.dataset.qty) - (+a.dataset.qty),
                    'amount-desc': (a, b) => (+b.dataset.proj) - (+a.dataset.proj),
                }[sort];
                if (cmp) rows.sort(cmp).forEach(r => tbody.appendChild(r));

                const cnt = root.querySelector('[data-ks-count]');
                if (cnt) cnt.textContent = idFmt(shown) + ' transaksi · ' + idFmt(ops.size) + ' operator · ' + idRp(sumProj);
                const footQty = root.querySelector('[data-ks-foot-qty]');
                if (footQty) footQty.textContent = idFmt(sumQty);
                const footAmt = root.querySelector('[data-ks-foot-amount]');
                if (footAmt) footAmt.textContent = idRp(sumProj);

                const setKpi = (sel, val) => { const el = root.querySelector(sel); if (el) el.textContent = val; };
                setKpi('[data-ks-kpi-total]', idRp(sumJahit + sumCutting));
                setKpi('[data-ks-kpi-jahit]', idRp(sumJahit));
                setKpi('[data-ks-kpi-cutting]', idRp(sumCutting));
                setKpi('[data-ks-kpi-operator]', idFmt(ops.size));
                setKpi('[data-ks-kpi-tx]', idFmt(shown));

                const module = role === 'Jahit' ? 'sewing' : (role === 'Cutting' ? 'cutting' : '');
                gfUpdateSlip(root.querySelector('[data-ks-slip]'), operator, module);

                const empty = root.querySelector('[data-ks-empty]');
                if (empty) empty.hidden = (shown !== 0) || rows.length === 0;
            }

            const KS_SEL = '[data-ks-search],[data-ks-role],[data-ks-operator],[data-ks-kind],[data-ks-sort]';
            document.addEventListener('input', (e) => {
                if (!e.target.matches('[data-ks-search]')) return;
                applyKsFilters(e.target.closest('[data-tab-panel]'));
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches(KS_SEL)) return;
                applyKsFilters(e.target.closest('[data-tab-panel]'));
            });

            // ---- Tab "Penjahit" ----
            function applyPjFilters(root) {
                if (!root) return;
                const table = root.querySelector('[data-pj-table]');
                if (!table) return;
                const tbody = table.querySelector('tbody');
                const q = (root.querySelector('[data-pj-search]')?.value || '').trim().toLowerCase();
                const operator = root.querySelector('[data-pj-operator]')?.value || '';
                const type = root.querySelector('[data-pj-type]')?.value || '';
                const sort = root.querySelector('[data-pj-sort]')?.value || 'date-desc';

                const rows = Array.from(tbody.querySelectorAll('[data-pj-row]'));
                let shown = 0, sumAmount = 0, sumProj = 0, sumQty = 0, sumOk = 0, sumReject = 0; const ops = new Set();
                rows.forEach(r => {
                    let ok = true;
                    if (q && !(r.dataset.search || '').includes(q)) ok = false;
                    if (operator && r.dataset.operator !== operator) ok = false;
                    if (type && r.dataset.type !== type) ok = false;
                    r.hidden = !ok;
                    if (ok) {
                        shown++;
                        ops.add(r.dataset.operator);
                        sumQty += parseFloat(r.dataset.qty) || 0;
                        sumAmount += parseFloat(r.dataset.amount) || 0;
                        sumProj += parseFloat(r.dataset.proj) || 0;
                        if (r.dataset.type === 'Setor') {
                            sumOk += parseFloat(r.dataset.qty) || 0;
                            sumReject += parseFloat(r.dataset.reject) || 0;
                        }
                    }
                });

                const cmp = {
                    'date-desc': (a, b) => (a.dataset.date < b.dataset.date ? 1 : -1),
                    'qty-desc': (a, b) => (+b.dataset.qty) - (+a.dataset.qty),
                    'amount-desc': (a, b) => (+b.dataset.amount) - (+a.dataset.amount),
                    'reject-desc': (a, b) => (+b.dataset.reject) - (+a.dataset.reject),
                }[sort];
                if (cmp) rows.sort(cmp).forEach(r => tbody.appendChild(r));

                const cnt = root.querySelector('[data-pj-count]');
                if (cnt) cnt.textContent = idFmt(shown) + ' transaksi · ' + idFmt(ops.size) + ' penjahit · ' + idRp(sumProj);
                const footQty = root.querySelector('[data-pj-foot-qty]');
                if (footQty) footQty.textContent = idFmt(sumQty);
                const footAmt = root.querySelector('[data-pj-foot-amount]');
                if (footAmt) footAmt.textContent = idRp(sumProj);

                const setKpi = (sel, val) => { const el = root.querySelector(sel); if (el) el.textContent = val; };
                setKpi('[data-pj-kpi-penjahit]', idFmt(ops.size));
                setKpi('[data-pj-kpi-tx]', idFmt(shown));
                setKpi('[data-pj-kpi-ok]', idFmt(sumOk));
                setKpi('[data-pj-kpi-upah]', idRp(sumAmount));
                setKpi('[data-pj-kpi-reject]', idFmt(sumReject));

                gfUpdateSlip(root.querySelector('[data-pj-slip]'), operator, 'sewing');

                const empty = root.querySelector('[data-pj-empty]');
                if (empty) empty.hidden = (shown !== 0) || rows.length === 0;
            }

            const PJ_SEL = '[data-pj-search],[data-pj-operator],[data-pj-type],[data-pj-sort]';
            document.addEventListener('input', (e) => {
                if (!e.target.matches('[data-pj-search]')) return;
                applyPjFilters(e.target.closest('[data-tab-panel]'));
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches(PJ_SEL)) return;
                applyPjFilters(e.target.closest('[data-tab-panel]'));
            });

            // ---- Tab "Cutting" ----
            function applyCgFilters(root) {
                if (!root) return;
                const table = root.querySelector('[data-cg-table]');
                if (!table) return;
                const tbody = table.querySelector('tbody');
                const q = (root.querySelector('[data-cg-search]')?.value || '').trim().toLowerCase();
                const operator = root.querySelector('[data-cg-operator]')?.value || '';
                const sort = root.querySelector('[data-cg-sort]')?.value || 'date-desc';

                const rows = Array.from(tbody.querySelectorAll('[data-cg-row]'));
                let shown = 0, sumQty = 0, sumOk = 0, sumReject = 0, sumAmount = 0; const ops = new Set();
                rows.forEach(r => {
                    let ok = true;
                    if (q && !(r.dataset.search || '').includes(q)) ok = false;
                    if (operator && r.dataset.operator !== operator) ok = false;
                    r.hidden = !ok;
                    if (ok) {
                        shown++;
                        ops.add(r.dataset.operator);
                        sumQty += parseFloat(r.dataset.qty) || 0;
                        sumOk += parseFloat(r.dataset.ok) || 0;
                        sumReject += parseFloat(r.dataset.reject) || 0;
                        sumAmount += parseFloat(r.dataset.amount) || 0;
                    }
                });

                const cmp = {
                    'date-desc': (a, b) => (a.dataset.date < b.dataset.date ? 1 : -1),
                    'qty-desc': (a, b) => (+b.dataset.qty) - (+a.dataset.qty),
                    'amount-desc': (a, b) => (+b.dataset.amount) - (+a.dataset.amount),
                    'reject-desc': (a, b) => (+b.dataset.reject) - (+a.dataset.reject),
                }[sort];
                if (cmp) rows.sort(cmp).forEach(r => tbody.appendChild(r));

                const cnt = root.querySelector('[data-cg-count]');
                if (cnt) cnt.textContent = idFmt(shown) + ' transaksi · ' + idFmt(ops.size) + ' pemotong · ' + idRp(sumAmount);
                const footQty = root.querySelector('[data-cg-foot-qty]');
                if (footQty) footQty.textContent = idFmt(sumQty);
                const footAmt = root.querySelector('[data-cg-foot-amount]');
                if (footAmt) footAmt.textContent = idRp(sumAmount);

                const setKpi = (sel, val) => { const el = root.querySelector(sel); if (el) el.textContent = val; };
                setKpi('[data-cg-kpi-operator]', idFmt(ops.size));
                setKpi('[data-cg-kpi-tx]', idFmt(shown));
                setKpi('[data-cg-kpi-ok]', idFmt(sumOk));
                setKpi('[data-cg-kpi-upah]', idRp(sumAmount));
                setKpi('[data-cg-kpi-reject]', idFmt(sumReject));

                gfUpdateSlip(root.querySelector('[data-cg-slip]'), operator, 'cutting');

                const empty = root.querySelector('[data-cg-empty]');
                if (empty) empty.hidden = (shown !== 0) || rows.length === 0;
            }

            const CG_SEL = '[data-cg-search],[data-cg-operator],[data-cg-sort]';
            document.addEventListener('input', (e) => {
                if (!e.target.matches('[data-cg-search]')) return;
                applyCgFilters(e.target.closest('[data-tab-panel]'));
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches(CG_SEL)) return;
                applyCgFilters(e.target.closest('[data-tab-panel]'));
            });

            // Terapkan filter default per-tab setelah HTML tab dimuat.
            function initTabFilters(name, pane) {
                if (name === 'keseluruhan') applyKsFilters(pane);
                else if (name === 'penjahit') applyPjFilters(pane);
                else if (name === 'cutting') applyCgFilters(pane);
            }

            initTabFilters(SERVER_INITIAL, paneByName(SERVER_INITIAL));

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
