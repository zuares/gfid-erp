{{-- resources/views/inventory/intelligence/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Inventory Intelligence')

@php
    $tabs = [
        'summary' => 'Ringkasan',
        'health' => 'Kesehatan Stok',
        'forecast' => 'Forecast & Saran',
    ];
    $tabDesc = [
        'summary' => 'Ringkasan kesehatan stok seluruh SKU barang jadi.',
        'health' => 'Cover stok per SKU — mana yang menipis / kritis / stockout.',
        'forecast' => 'Laju jual, perkiraan 30 hari, dan saran jumlah produksi.',
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

        /* Status dot (kesehatan stok) */
        .ii-status { display: inline-flex; align-items: center; gap: .4rem; font-weight: 600; color: #475569; white-space: nowrap; }
        .ii-status-dot { width: 8px; height: 8px; border-radius: 50%; background: #cbd5e1; flex: none; }
        .ii-status-stockout .ii-status-dot { background: #dc2626; }
        .ii-status-kritis .ii-status-dot { background: #ef4444; }
        .ii-status-menipis .ii-status-dot { background: #f59e0b; }
        .ii-status-sehat .ii-status-dot { background: #16a34a; }
        .ii-status-no_demand .ii-status-dot { background: #94a3b8; }
        .ii-status-stockout, .ii-status-kritis { color: #b91c1c; }

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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const DATA_URL = @json(route('inventory.intelligence.data'));
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
        });
    </script>
@endpush
