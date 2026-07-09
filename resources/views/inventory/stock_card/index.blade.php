@extends('layouts.app')

@section('title', 'Inventory • Kartu Stok')

@push('head')
    <style>
        :root {
            --shp-accent:#334155;
            --shp-accent-2:#1f2937;
            --shp-border:rgba(148,163,184,.18);
            --shp-border-strong:rgba(148,163,184,.30);
            --shp-muted:#64748b;
            --in: var(--bs-teal);
            --out: var(--bs-orange);
        }

        .page-wrap { max-width:1120px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }

        .card-main {
            background: var(--card);
            border-radius: 8px;
            border: 1px solid var(--shp-border);
            box-shadow: none;
            overflow:hidden;
        }
        body[data-theme="dark"] .card-main {
            border-color: rgba(51,65,85,.85);
            box-shadow: none;
        }

        .ship-topbar {
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
            border-bottom:1px solid var(--shp-border);
        }
        body[data-theme="dark"] .ship-topbar { background:var(--card,#0f172a); }

        .title { font-weight: 750; font-size:1rem; letter-spacing: 0; margin:0; }
        .sub { color:var(--shp-muted); font-size:.78rem; }
        body[data-theme="dark"] .sub { color:#9ca3af; }

        .kpis { display:flex; flex-wrap:wrap; gap:.32rem; margin-top:.35rem; }
        .kpi {
            display:inline-flex; align-items:baseline; gap:.45rem;
            border-radius:7px; padding:.2rem .48rem;
            border:1px solid rgba(148,163,184,.28);
            background: transparent;
            font-size:.72rem;
        }
        body[data-theme="dark"] .kpi {
            background: rgba(15, 23, 42, 0.96);
            border-color: rgba(51, 65, 85, 0.85);
        }
        .kpi .lbl { text-transform:none; letter-spacing:0; font-size:.66rem; color:#94a3b8; }
        body[data-theme="dark"] .kpi .lbl { color:#6b7280; }
        .kpi .val { font-weight:650; color:var(--shp-accent); }
        body[data-theme="dark"] .kpi .val { color:#e2e8f0; }

        .controls { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
        .filter-label { font-size:.8rem; color:#6b7280; margin-bottom:.2rem; display:block; }
        body[data-theme="dark"] .filter-label { color:#9ca3af; }
        .filter-select { border-radius:7px; padding-left:.75rem; padding-right:2rem; font-size:.82rem; }
        .btn-pill { border-radius:7px; padding-inline:.78rem; box-shadow:none!important; font-weight:600; }
        
        .table-responsive { max-height: calc(100vh - 220px); overflow-y: auto; }
        .table-list { margin-bottom:0; }
        .table-list thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom-width:1px;
            font-size:.68rem;
            text-transform:none;
            letter-spacing:0;
            color:#64748b;
            background: var(--card,#fff);
            padding:.52rem .62rem;
            white-space:nowrap;
        }
        body[data-theme="dark"] .table-list thead th {
            background: rgba(15, 23, 42, 0.98);
            color:#9ca3af;
            border-bottom-color: rgba(30, 64, 175, 0.6);
        }
        .table-list tbody td {
            vertical-align:middle;
            border-top-color: rgba(148, 163, 184, 0.16);
            padding:.52rem .62rem;
        }
        body[data-theme="dark"] .table-list tbody td { border-top-color: rgba(51, 65, 85, 0.85); }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }

        .muted { color: var(--shp-muted); }

        .sub-badge {
            background: color-mix(in srgb, var(--bs-primary) 10%, var(--bs-body-bg) 90%);
            border: 1px solid var(--shp-border);
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
        .qty-num { min-width: 5.2rem; text-align: right; }
        .qty-unit { color: var(--shp-muted); }
        .qty-in-num { color: var(--in); font-weight: 600; }
        .qty-out-num { color: var(--out); font-weight: 600; }
        .qty-zero { color: var(--shp-muted); }

        .search-clear {
            position: absolute;
            right: .5rem;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: var(--shp-muted);
            padding: .1rem .25rem;
            border-radius: 8px;
        }
        .search-clear:hover { background: rgba(148,163,184,.18); color: inherit; }
        .loading { opacity: .65; pointer-events: none; }

        @media(max-width:768px) {
            .hide-sm { display: none; }
            .qty-cell { grid-template-columns: auto minmax(4.8rem, auto) auto; }
            .qty-num { min-width: 4.8rem; }
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

    <div class="page-wrap">

        {{-- Header --}}
        <div class="ship-topbar">
            <div>
                <h1 class="title">Kartu Stok</h1>
                <div class="sub" id="sc_subtitle">
                    {{ $itemId && $selectedItem ? 'Riwayat saldo per item.' : 'Semua transaksi stok (filter via kata kunci).' }}
                </div>
                <div class="sub mt-1" id="sc_item_label" @if (!($itemId && $selectedItem)) style="display:none" @endif>
                    Item terpilih: <span class="mono fw-semibold">{{ $selectedItem->code ?? '' }}</span> — {{ $selectedItem->name ?? '' }}
                </div>
            </div>

            <div class="controls">
                <a class="btn btn-ship-outline btn-pill btn-sm" href="{{ route('inventory.stock_card.index') }}">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
                @if ($itemId)
                    <a class="btn btn-ship-primary btn-pill btn-sm" href="{{ route('inventory.stock_card.export', request()->query()) }}">
                        <i class="bi bi-file-earmark-excel"></i> Export
                    </a>
                @endif
            </div>
        </div>
        
        <div class="alert alert-info d-flex align-items-center py-2 px-3 mb-3" style="font-size: .8rem; border-radius: 8px;">
            <i class="bi bi-info-circle-fill me-2 fs-6"></i>
            <div>
                <strong>Info:</strong> Status <span class="badge bg-warning text-dark px-1">Sedang Packing</span> di Fulfillment / Shipment tidak mengurangi stok fisik dan <strong>tidak tercatat</strong> sebagai mutasi di Kartu Stok sampai order benar-benar diposting (Shipment).
            </div>
        </div>

        {{-- KPI (AJAX replace) --}}
        <div id="sc_kpi">
            @include('inventory.stock_card._kpi')
        </div>

        {{-- Quick chips (AJAX via intercept link) --}}
        {{-- Filter (compact) --}}
        <form method="GET" action="{{ route('inventory.stock_card.index') }}" id="stockCardFilter" class="mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="filter-label">Cari Item</label>
                    <div class="position-relative">
                        <input type="text" name="q_item" value="{{ $qItem }}" class="form-control filter-select"
                            id="q_item_input" placeholder="Kode / nama… (Enter)" autocomplete="off">
                        <button type="button" class="search-clear" id="q_item_clear"
                            style="{{ $qItem ? '' : 'display:none' }}">✕</button>
                    </div>
                </div>

                <input type="hidden" name="item_id" value="{{ $itemId }}">

                <div class="col-6 col-md-2">
                    <label class="filter-label">Gudang</label>
                    <select name="warehouse_id" class="form-select filter-select">
                        <option value="">Semua</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" @selected(($filters['warehouse_id'] ?? null) == $w->id)>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="filter-label">LOT</label>
                    <select name="lot_id" class="form-select filter-select" @disabled(!$itemId)>
                        <option value="">Semua</option>
                        @foreach ($lots as $l)
                            <option value="{{ $l->id }}" @selected(($filters['lot_id'] ?? null) == $l->id)>{{ $l->code }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="filter-label">Dari</label>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control filter-select">
                </div>

                <div class="col-6 col-md-2">
                    <label class="filter-label">Sampai</label>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="form-control filter-select">
                </div>
            </div>

            <div class="row g-2 mt-1 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="filter-label">Arah</label>
                    <select name="direction" class="form-select filter-select">
                        <option value="">Semua</option>
                        <option value="in" @selected(($filters['direction'] ?? null) === 'in')>IN (Masuk)</option>
                        <option value="out" @selected(($filters['direction'] ?? null) === 'out')>OUT (Keluar)</option>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label class="filter-label">Sumber</label>
                    <select name="source_type" class="form-select filter-select">
                        @foreach ($availableSourceTypes as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['source_type'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="filter-label">Urutkan</label>
                    <select name="sort" class="form-select filter-select">
                        <option value="desc" @selected(($filters['sort'] ?? 'desc') === 'desc')>Terbaru</option>
                        <option value="asc" @selected(($filters['sort'] ?? 'desc') === 'asc')>Terlama</option>
                    </select>
                </div>

                @if ($canViewCost ?? false)
                <div class="col-6 col-md-2 d-flex align-items-center mb-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="has_cost" name="has_cost" value="1"
                            @checked(!empty($filters['has_cost']))>
                        <label class="form-check-label filter-label mb-0" for="has_cost" style="cursor:pointer">Ada Nilai</label>
                    </div>
                </div>
                @endif
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
