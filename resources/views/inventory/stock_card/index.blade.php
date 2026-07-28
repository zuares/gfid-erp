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
            --summary-final: #0f766e;
        }

        .page-wrap { max-width:1180px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }

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

        .hero-panel {
            position: sticky;
            top: 0;
            z-index: 300;
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:1rem;
            flex-wrap:wrap;
            padding:.45rem .75rem;
            margin-inline:-.75rem;
            margin-bottom:.65rem;
            background: var(--card, #fff);
            border-bottom: 1px solid var(--shp-border);
            border-radius: 0;
            box-shadow: none;
        }
        body[data-theme="dark"] .hero-panel {
            background: var(--card, #0f172a);
            box-shadow: none;
        }
        .hero-copy { min-width: 0; flex: 1 1 520px; }
        .hero-kicker {
            font-size: .7rem;
            color: var(--shp-muted);
            margin-bottom: .15rem;
        }
        body[data-theme="dark"] .hero-kicker { color: #9ca3af; }
        .hero-title { font-weight: 750; font-size:1rem; letter-spacing: 0; margin:0; color:var(--shp-accent); }
        body[data-theme="dark"] .hero-title { color:#e2e8f0; }
        .hero-desc { color:var(--shp-muted); font-size:.78rem; line-height:1.4; margin-top:.3rem; }
        body[data-theme="dark"] .hero-desc { color:#cbd5e1; }
        .hero-meta {
            display:flex;
            flex-wrap:wrap;
            gap:.32rem;
            margin-top:.45rem;
        }
        .hero-pill {
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            border-radius:7px;
            padding:.2rem .48rem;
            border:1px solid rgba(148,163,184,.28);
            background: transparent;
            font-size:.72rem;
            color:var(--shp-accent);
            white-space:nowrap;
        }
        body[data-theme="dark"] .hero-pill {
            background: rgba(15, 23, 42, 0.96);
            color:#e2e8f0;
            border-color: rgba(51, 65, 85, 0.9);
        }
        .hero-pill strong {
            font-size: .66rem;
            letter-spacing: 0;
            color: var(--shp-muted);
            font-weight: 700;
        }
        body[data-theme="dark"] .hero-pill strong { color: #94a3b8; }

        .hero-actions {
            flex: 0 0 auto;
            padding-top: .15rem;
        }

        .filter-panel {
            border-radius: 8px;
            background: var(--card);
        }
        body[data-theme="dark"] .filter-panel {
            background: rgba(15, 23, 42, 0.96);
        }
        .search-upper {
            text-transform: uppercase;
        }

        .summary-grid {
            display:grid;
            grid-template-columns: repeat(auto-fit, minmax(165px, 1fr));
            gap:.7rem;
            margin-bottom: 1rem;
        }
        .summary-card {
            position: relative;
            overflow: hidden;
            min-height: 108px;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
            gap:.35rem;
            padding:.85rem .9rem .85rem 1rem;
            border-radius: 16px;
            border: 1px solid var(--shp-border);
            background: var(--card);
            box-shadow: 0 1px 0 rgba(15, 23, 42, 0.02);
        }
        body[data-theme="dark"] .summary-card {
            background: rgba(15, 23, 42, 0.96);
            border-color: rgba(51, 65, 85, 0.85);
        }
        .summary-card::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: var(--summary-accent, var(--bs-primary));
        }
        .summary-card--soft { --summary-accent: #64748b; }
        .summary-card--in { --summary-accent: var(--in); }
        .summary-card--out { --summary-accent: var(--out); }
        .summary-card--final {
            --summary-accent: var(--summary-final);
            background: linear-gradient(180deg, color-mix(in srgb, var(--summary-final) 8%, var(--card) 92%), var(--card));
        }
        body[data-theme="dark"] .summary-card--final {
            background: linear-gradient(180deg, rgba(8, 47, 73, 0.55), rgba(15, 23, 42, 0.96));
        }
        .summary-label {
            font-size: .68rem;
            letter-spacing: .11em;
            text-transform: uppercase;
            color: var(--shp-muted);
        }
        .summary-value {
            font-weight: 850;
            font-size: 1.35rem;
            line-height: 1.1;
            color: var(--shp-accent);
        }
        body[data-theme="dark"] .summary-value { color:#e2e8f0; }
        .summary-sub {
            font-size: .75rem;
            color: var(--shp-muted);
            line-height: 1.35;
        }
        body[data-theme="dark"] .summary-sub { color:#94a3b8; }

        .summary-inline-note {
            color: var(--shp-muted);
            font-size: .78rem;
        }
        body[data-theme="dark"] .summary-inline-note { color:#94a3b8; }

        .stock-footer-summary {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:1rem;
            flex-wrap:wrap;
            margin-top:.8rem;
            padding:.9rem 1rem;
            border-radius:16px;
            border:1px solid var(--shp-border);
            background: linear-gradient(180deg, color-mix(in srgb, var(--summary-final) 7%, var(--card) 93%), var(--card));
        }
        body[data-theme="dark"] .stock-footer-summary {
            background: linear-gradient(180deg, rgba(8, 47, 73, 0.4), rgba(15, 23, 42, 0.96));
            border-color: rgba(51, 65, 85, 0.85);
        }
        .stock-footer-summary .footer-meta {
            display:flex;
            flex-wrap:wrap;
            gap:.9rem 1.2rem;
        }
        .stock-footer-summary .footer-block {
            min-width: 120px;
        }
        .stock-footer-summary .footer-label {
            font-size:.68rem;
            letter-spacing:.11em;
            text-transform:uppercase;
            color:var(--shp-muted);
        }
        .stock-footer-summary .footer-value {
            font-size:1.05rem;
            font-weight:800;
            color:var(--shp-accent);
        }
        body[data-theme="dark"] .stock-footer-summary .footer-value { color:#e2e8f0; }

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
            .hero-panel { padding: .85rem .85rem .8rem; }
            .hero-title { font-size: .98rem; }
            .summary-grid { grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)); }
            .qty-cell { grid-template-columns: auto minmax(4.8rem, auto) auto; }
            .qty-num { min-width: 4.8rem; }
            .stock-footer-summary { padding: .85rem .9rem; }
        }
    </style>
@endpush

@section('content')
    @php
        $itemId = $filters['item_id'] ?? null;
        $qItem = $filters['q_item'] ?? '';
        $warehouseId = $filters['warehouse_id'] ?? null;
        $warehouseLabel = optional($warehouses->firstWhere('id', $warehouseId))->name ?? 'Semua gudang';
        $periodLabel = sprintf(
            '%s - %s',
            !empty($filters['from_date']) ? \Illuminate\Support\Carbon::parse($filters['from_date'])->format('d/m/Y') : 'Awal',
            !empty($filters['to_date']) ? \Illuminate\Support\Carbon::parse($filters['to_date'])->format('d/m/Y') : 'Hari ini'
        );
        $modeLabel = $itemId && $selectedItem ? 'Mode item' : 'Mode semua transaksi';
        $subtitle = $itemId && $selectedItem
            ? 'Riwayat mutasi stok dan saldo akhir untuk item terpilih.'
            : 'Riwayat mutasi stok dan saldo akhir per baris transaksi.';
    @endphp

    <div class="page-wrap">

        {{-- Header --}}
        <div class="hero-panel card-main mb-3">
            <div class="hero-copy">
                <div class="hero-kicker">Inventory / Stock Card</div>
                <h1 class="hero-title">Kartu Stok</h1>
                <div class="hero-desc" id="sc_subtitle">
                    {{ $subtitle }}
                </div>
                <div class="hero-meta">
                    <span class="hero-pill"><strong>{{ $modeLabel }}</strong></span>
                    <span class="hero-pill"><strong>Gudang</strong> {{ $warehouseLabel }}</span>
                    <span class="hero-pill"><strong>Periode</strong> {{ $periodLabel }}</span>
                </div>
                <div class="hero-desc mt-2" id="sc_item_label" @if (!($itemId && $selectedItem)) style="display:none" @endif>
                    Item terpilih: <span class="mono fw-semibold">{{ $selectedItem->code ?? '' }}</span> - {{ $selectedItem->name ?? '' }}
                </div>
            </div>

            <div class="hero-actions controls">
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
        
        {{-- KPI (AJAX replace) --}}
        <div id="sc_kpi">
            @include('inventory.stock_card._kpi')
        </div>

        {{-- Filter (compact) --}}
        <form method="GET" action="{{ route('inventory.stock_card.index') }}" id="stockCardFilter" class="mb-3">
            <div class="card-main filter-panel p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <div class="hero-kicker">Filter</div>
                    </div>
                    <button type="submit" class="btn btn-ship-outline btn-pill btn-sm d-md-none">
                        Terapkan
                    </button>
                </div>

                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="filter-label">Cari Item</label>
                        <div class="position-relative">
                            <input type="text" name="q_item" value="{{ $qItem }}" class="form-control filter-select search-upper"
                                id="q_item_input" placeholder="Kode / nama item" autocomplete="off">
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
                        <label class="filter-label">Dari</label>
                        <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control filter-select">
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="filter-label">Sampai</label>
                        <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="form-control filter-select">
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
                qInput.addEventListener('input', () => {
                    const caret = qInput.selectionStart ?? qInput.value.length;
                    const value = qInput.value.toUpperCase();
                    if (qInput.value !== value) {
                        qInput.value = value;
                        qInput.setSelectionRange(caret, caret);
                    }
                });
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
            form.querySelectorAll('select, input[type="date"]').forEach(el => {
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

            // initial focus (optional)
            if (qInput) qInput.focus({
                preventScroll: true
            });
        })();
    </script>
@endpush
