@extends('layouts.app')

@section('title', 'Inventory • Kartu Stok')

@push('head')
    <style>
        :root {
            --radius: 12px;
            --line: color-mix(in srgb, var(--bs-border-color) 78%, var(--bs-body-bg) 22%);
            --muted: var(--bs-secondary-color);
            --in: var(--bs-teal);
            --out: var(--bs-orange);
        }

        .wrap {
            max-width: 1120px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .soft {
            border-color: color-mix(in srgb, var(--line) 70%, transparent 30%);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }

        .muted {
            color: var(--muted);
        }

        .btn-ghost {
            border: 1px solid var(--line);
            background: transparent;
        }

        /* Compact filter */
        .filter .form-control,
        .filter .form-select {
            border-radius: 10px;
            background: transparent;
            border: 1px solid var(--line);
            padding-top: .42rem;
            padding-bottom: .42rem;
        }

        /* Compact KPI strip */
        .kpi-strip {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem .6rem;
            align-items: center;
        }

        .kpi-pill {
            display: inline-flex;
            gap: .4rem;
            align-items: baseline;
            padding: .22rem .6rem;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: color-mix(in srgb, var(--card) 92%, var(--bs-primary) 8%);
            font-size: .78rem;
            color: var(--muted);
            line-height: 1.1;
        }

        .kpi-pill strong {
            color: var(--bs-body-color);
            font-weight: 600;
        }

        .kpi-in {
            color: var(--in) !important;
        }

        .kpi-out {
            color: var(--out) !important;
        }

        /* Quick chips (tight) */
        .chips .btn {
            border-color: var(--line);
            padding: .25rem .55rem;
            font-size: .78rem;
        }

        .chips .btn.active {
            background: color-mix(in srgb, var(--bs-primary) 8%, transparent);
            border-color: var(--bs-primary);
        }

        /* Table compact */
        .table {
            margin: 0;
        }

        .table thead th {
            font-weight: 600;
            color: var(--muted);
            background: var(--card);
            position: sticky;
            top: 0;
            z-index: 1;
            border-bottom: 1px solid var(--line);
            text-transform: uppercase;
            font-size: .72rem;
            letter-spacing: .03em;
            white-space: nowrap;
            padding: .45rem .55rem;
        }

        .table td {
            border: 0;
            vertical-align: middle;
            padding: .45rem .55rem;
        }

        .table tbody tr+tr td {
            border-top: 1px dashed color-mix(in srgb, var(--line) 80%, transparent 20%);
        }

        .table tbody tr:hover {
            background: color-mix(in srgb, var(--bs-primary) 4%, var(--bs-body-bg) 96%);
        }

        .sub-badge {
            background: color-mix(in srgb, var(--bs-primary) 10%, var(--bs-body-bg) 90%);
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: .14rem .5rem;
            font-size: .78rem;
            white-space: nowrap;
        }

        .qty-cell {
            display: grid;
            grid-template-columns: auto minmax(5.2rem, auto) auto;
            align-items: baseline;
            justify-items: end;
            column-gap: .35rem;
            white-space: nowrap;
            font-variant-numeric: tabular-nums
        }

        .qty-num {
            min-width: 5.2rem;
            text-align: right;
        }

        .qty-unit {
            color: var(--muted);
        }

        .qty-in-num {
            color: var(--in);
            font-weight: 600;
        }

        .qty-out-num {
            color: var(--out);
            font-weight: 600;
        }

        .qty-zero {
            color: var(--muted);
        }

        /* Search clear */
        .search-clear {
            position: absolute;
            right: .5rem;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: var(--muted);
            padding: .1rem .25rem;
            border-radius: 8px;
        }

        .search-clear:hover {
            background: color-mix(in srgb, var(--bs-primary) 8%, transparent);
            color: var(--bs-body-color);
        }

        .loading {
            opacity: .65;
            pointer-events: none;
        }

        @media(max-width:768px) {
            .hide-sm {
                display: none;
            }

            .qty-cell {
                grid-template-columns: auto minmax(4.8rem, auto) auto;
            }

            .qty-num {
                min-width: 4.8rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $itemId = $filters['item_id'] ?? null;
        $qItem = $filters['q_item'] ?? '';
        $direction = $filters['direction'] ?? '';
        $sourceType = $filters['source_type'] ?? '';
    @endphp

    <div class="wrap py-3">

        {{-- Header --}}
        <div class="d-flex align-items-start justify-content-between mb-2 gap-2">
            <div>
                <div class="fw-semibold">Inventory • Kartu Stok</div>
                <div class="small muted" id="sc_subtitle">
                    {{ $itemId && $selectedItem ? 'Kartu stok item (running saldo).' : 'Semua mutasi (filter via keyword).' }}
                </div>
                <div class="small muted mt-1" id="sc_item_label"
                    @if (!($itemId && $selectedItem)) style="display:none" @endif>
                    Item: <span class="mono fw-semibold">{{ $selectedItem->code ?? '' }}</span> —
                    {{ $selectedItem->name ?? '' }}
                </div>
            </div>

            <div class="d-flex gap-2">
                <a class="btn btn-ghost btn-sm" href="{{ route('inventory.stock_card.index') }}">Reset</a>
                @if ($itemId)
                    <a class="btn btn-success btn-sm"
                        href="{{ route('inventory.stock_card.export', request()->query()) }}">Export</a>
                @endif
            </div>
        </div>

        {{-- KPI (AJAX replace) --}}
        <div id="sc_kpi">
            @include('inventory.stock_card._kpi')
        </div>

        {{-- Quick chips (AJAX via intercept link) --}}
        <div class="d-flex flex-wrap gap-2 mb-2 chips" id="sc_quick_chips">
            <a class="btn btn-sm btn-outline-secondary {{ $direction === '' ? 'active' : '' }}"
                href="{{ request()->fullUrlWithQuery(['direction' => null]) }}">Semua</a>
            <a class="btn btn-sm btn-outline-secondary {{ $direction === 'in' ? 'active' : '' }}"
                href="{{ request()->fullUrlWithQuery(['direction' => 'in']) }}">IN</a>
            <a class="btn btn-sm btn-outline-secondary {{ $direction === 'out' ? 'active' : '' }}"
                href="{{ request()->fullUrlWithQuery(['direction' => 'out']) }}">OUT</a>
            @foreach ($availableSourceTypes as $key => $label)
                @continue($key === '')
                <a class="btn btn-sm btn-outline-secondary {{ $sourceType === $key ? 'active' : '' }}"
                    href="{{ request()->fullUrlWithQuery(['source_type' => $key]) }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Filter (compact) --}}
        <form method="GET" action="{{ route('inventory.stock_card.index') }}" class="card soft p-2 mb-2 filter"
            id="stockCardFilter">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="small muted mb-1 d-block">Cari Item</label>
                    <div class="position-relative">
                        <input type="text" name="q_item" value="{{ $qItem }}" class="form-control"
                            id="q_item_input" placeholder="Kode / nama… (Enter)" autocomplete="off">
                        <button type="button" class="search-clear" id="q_item_clear"
                            style="{{ $qItem ? '' : 'display:none' }}">✕</button>
                    </div>
                </div>

                <input type="hidden" name="item_id" value="{{ $itemId }}">

                <div class="col-6 col-md-2">
                    <label class="small muted mb-1 d-block">Gudang</label>
                    <select name="warehouse_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" @selected(($filters['warehouse_id'] ?? null) == $w->id)>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="small muted mb-1 d-block">LOT</label>
                    <select name="lot_id" class="form-select" @disabled(!$itemId)>
                        <option value="">All</option>
                        @foreach ($lots as $l)
                            <option value="{{ $l->id }}" @selected(($filters['lot_id'] ?? null) == $l->id)>{{ $l->code }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="small muted mb-1 d-block">Dari</label>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control">
                </div>

                <div class="col-6 col-md-2">
                    <label class="small muted mb-1 d-block">Sampai</label>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="form-control">
                </div>

                <div class="col-6 col-md-2">
                    <label class="small muted mb-1 d-block">Arah</label>
                    <select name="direction" class="form-select">
                        <option value="">All</option>
                        <option value="in" @selected(($filters['direction'] ?? null) === 'in')>IN</option>
                        <option value="out" @selected(($filters['direction'] ?? null) === 'out')>OUT</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="small muted mb-1 d-block">Sumber</label>
                    <select name="source_type" class="form-select">
                        @foreach ($availableSourceTypes as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['source_type'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="small muted mb-1 d-block">Sort</label>
                    <select name="sort" class="form-select">
                        <option value="desc" @selected(($filters['sort'] ?? 'desc') === 'desc')>New</option>
                        <option value="asc" @selected(($filters['sort'] ?? 'desc') === 'asc')>Old</option>
                    </select>
                </div>

                <div class="col-12 col-md-4 d-flex align-items-center">
                    <div class="form-check mt-2 mt-md-0">
                        <input class="form-check-input" type="checkbox" id="has_cost" name="has_cost" value="1"
                            @checked(!empty($filters['has_cost']))>
                        <label class="form-check-label small muted" for="has_cost">Has value</label>
                    </div>
                </div>
            </div>
        </form>

        {{-- Table (AJAX replace) --}}
        <div id="sc_table_wrap">
            @include('inventory.stock_card._table')
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('stockCardFilter');
            const qInput = document.getElementById('q_item_input');
            const qClear = document.getElementById('q_item_clear');

            const kpiWrap = document.getElementById('sc_kpi');
            const tableWrap = document.getElementById('sc_table_wrap');

            if (!form || !kpiWrap || !tableWrap) return;

            // focus/select all
            if (qInput) {
                qInput.addEventListener('focus', () => qInput.select());
                qInput.addEventListener('click', () => qInput.select());
            }

            const buildUrl = () => {
                const url = new URL(window.location.href);
                const fd = new FormData(form);
                url.search = '';
                for (const [k, v] of fd.entries()) {
                    if (v !== '') url.searchParams.set(k, v);
                }
                url.searchParams.set('ajax', '1');
                return url;
            };

            let timer = null;
            let abortCtrl = null;
            const debounce = (fn, wait = 550) => (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, args), wait);
            };

            const setLoading = (on) => {
                const card = document.getElementById('sc_table_card');
                if (card) card.classList.toggle('loading', on);
            };

            async function fetchAndSwap() {
                const url = buildUrl();

                // update visible URL (without ajax)
                const visible = new URL(url);
                visible.searchParams.delete('ajax');
                window.history.replaceState({}, '', visible);

                if (abortCtrl) abortCtrl.abort();
                abortCtrl = new AbortController();

                setLoading(true);

                try {
                    const res = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json'
                        },
                        signal: abortCtrl.signal
                    });
                    if (!res.ok) throw new Error('Request failed');
                    const json = await res.json();

                    if (json.kpi !== undefined) kpiWrap.innerHTML = json.kpi;
                    if (json.table !== undefined) tableWrap.innerHTML = json.table;

                    if (qInput) qInput.focus({
                        preventScroll: true
                    });

                } catch (e) {
                    if (e.name !== 'AbortError') console.error(e);
                } finally {
                    setLoading(false);
                }
            }

            const debouncedFetch = debounce(fetchAndSwap, 600);

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                fetchAndSwap();
            });

            // instant controls
            form.querySelectorAll('select, input[type="date"], input[type="checkbox"]').forEach(el => {
                el.addEventListener('change', () => fetchAndSwap());
            });

            // keyword
            if (qInput) {
                qInput.addEventListener('input', () => {
                    if (qClear) qClear.style.display = qInput.value.trim() ? '' : 'none';
                    debouncedFetch();
                });
                qInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        fetchAndSwap();
                    }
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        qInput.value = '';
                        if (qClear) qClear.style.display = 'none';
                        fetchAndSwap();
                    }
                });
            }

            if (qClear && qInput) {
                qClear.addEventListener('click', () => {
                    qInput.value = '';
                    qClear.style.display = 'none';
                    fetchAndSwap();
                    qInput.focus({
                        preventScroll: true
                    });
                    qInput.select();
                });
            }

            // pagination click -> ajax (delegate)
            document.addEventListener('click', (e) => {
                const a = e.target.closest('#sc_pagination a');
                if (!a) return;
                e.preventDefault();

                const url = new URL(a.href);
                let pageInput = form.querySelector('input[name="page"]');
                if (!pageInput) {
                    pageInput = document.createElement('input');
                    pageInput.type = 'hidden';
                    pageInput.name = 'page';
                    form.appendChild(pageInput);
                }
                pageInput.value = url.searchParams.get('page') || '';

                fetchAndSwap();
            });

            // quick chips links -> ajax
            document.addEventListener('click', (e) => {
                const a = e.target.closest('#sc_quick_chips a');
                if (!a) return;
                e.preventDefault();

                const url = new URL(a.href);
                // sync params into form (simple: set direct by name)
                ['direction', 'source_type'].forEach((key) => {
                    const v = url.searchParams.get(key) || '';
                    const el = form.querySelector(`[name="${key}"]`);
                    if (el) el.value = v;
                });

                // reset page on chip click
                const pageInput = form.querySelector('input[name="page"]');
                if (pageInput) pageInput.value = '';

                fetchAndSwap();
            });

            // initial focus (optional)
            if (qInput) qInput.focus({
                preventScroll: true
            });
        })();
    </script>
@endpush
