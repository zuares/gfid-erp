{{-- resources/views/inventory/stocks/items.blade.php --}}
@extends('layouts.app')

@section('title', 'Inventory • Stok Finished Good per Item')

@push('head')
    <style>
        :root {
            --card-r: 14px;
            --br: rgba(148, 163, 184, .25);
            --muted: #6b7280;

            --chip-bg: rgba(59, 130, 246, .10);
            --chip-br: rgba(59, 130, 246, .22);
            --chip-tx: rgba(29, 78, 216, 1);

            --chip2-bg: rgba(45, 212, 191, .12);
            --chip2-br: rgba(45, 212, 191, .24);
            --chip2-tx: rgba(15, 118, 110, 1);

            --chip3-bg: rgba(148, 163, 184, .14);
            --chip3-br: rgba(148, 163, 184, .26);
            --chip3-tx: rgba(71, 85, 105, 1);
        }

        body[data-theme="dark"] {
            --muted: #9ca3af;
            --chip-bg: rgba(147, 197, 253, .12);
            --chip-br: rgba(147, 197, 253, .22);
            --chip-tx: rgba(191, 219, 254, 1);
            --chip2-bg: rgba(45, 212, 191, .12);
            --chip2-br: rgba(45, 212, 191, .22);
            --chip2-tx: rgba(153, 246, 228, 1);
            --chip3-bg: rgba(148, 163, 184, .14);
            --chip3-br: rgba(148, 163, 184, .22);
            --chip3-tx: rgba(203, 213, 225, 1);
        }

        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: .9rem .85rem 4.2rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, .12) 0, rgba(45, 212, 191, .10) 26%, #f9fafb 60%);
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(15, 23, 42, .92) 0, #020617 65%);
        }

        .card-main {
            background: var(--card);
            border-radius: var(--card-r);
            border: 1px solid var(--br);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .08), 0 0 0 1px rgba(148, 163, 184, .10);
        }

        .meta {
            font-size: .72rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .28rem .65rem;
            border-radius: 999px;
            border: 1px solid var(--chip-br);
            background: var(--chip-bg);
            color: var(--chip-tx);
            font-size: .72rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .chip--admin {
            border-color: var(--chip2-br);
            background: var(--chip2-bg);
            color: var(--chip2-tx);
        }

        .chip--operating {
            border-color: var(--chip-br);
            background: var(--chip-bg);
            color: var(--chip-tx);
        }

        .chip--owner {
            border-color: var(--chip3-br);
            background: var(--chip3-bg);
            color: var(--chip3-tx);
        }

        .btn-chip {
            border-radius: 999px;
            font-size: .78rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: .35rem 1rem;
        }

        .item-code-link {
            padding: 0;
            border: none;
            background: none;
            color: #2563eb;
            font-weight: 800;
        }

        .item-code-link:hover {
            text-decoration: underline;
        }

        body[data-theme="dark"] .item-code-link {
            color: #93c5fd;
        }

        /* FILTER compact + sejajar */
        .filter-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .55rem;
            align-items: end;
        }

        .filter-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .55rem;
        }

        .filter-label {
            font-size: .74rem;
            color: var(--muted);
            margin: 0 0 .25rem 0;
        }

        .filter-field .form-select,
        .filter-field .form-control {
            border-radius: 12px;
        }

        .filter-field .form-select-sm,
        .filter-field .form-control-sm {
            padding-top: .45rem;
            padding-bottom: .45rem;
        }

        .filter-check {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .52rem .65rem;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 12px;
            background: rgba(148, 163, 184, .08);
            min-height: 38px;
        }

        body[data-theme="dark"] .filter-check {
            border-color: rgba(148, 163, 184, .18);
            background: rgba(148, 163, 184, .08);
        }

        .filter-check .form-check-input {
            margin-top: 0;
        }

        .filter-check .form-check-label {
            margin: 0;
            color: var(--muted);
            font-size: .82rem;
        }

        @media (min-width: 768px) {
            .filter-grid {
                grid-template-columns: 1.1fr 1.2fr 1.2fr .9fr;
                gap: .6rem;
            }

            .filter-actions {
                grid-template-columns: 1fr 1fr;
                justify-content: end;
            }
        }

        /* Mobile list */
        @media (max-width: 576px) {
            .page-wrap {
                padding: .8rem .7rem 4.4rem;
            }

            .table thead {
                display: none;
            }

            .row-card {
                border-top: 1px solid rgba(148, 163, 184, .22);
                padding: .55rem .65rem;
            }

            .row-card:first-child {
                border-top: none;
            }

            .row-title {
                display: flex;
                justify-content: space-between;
                gap: .6rem;
                align-items: flex-start;
            }

            .name {
                color: var(--muted);
                font-size: .82rem;
                line-height: 1.2;
                margin-top: .2rem;
            }

            .row-metrics {
                display: flex;
                gap: .5rem;
                margin-top: .45rem;
                flex-wrap: wrap;
            }

            .m-pill {
                display: inline-flex;
                align-items: center;
                gap: .35rem;
                padding: .18rem .55rem;
                border-radius: 999px;
                border: 1px solid rgba(148, 163, 184, .28);
                background: rgba(148, 163, 184, .10);
                font-size: .76rem;
                color: var(--muted);
            }

            body[data-theme="dark"] .m-pill {
                border-color: rgba(148, 163, 184, .22);
                background: rgba(148, 163, 184, .10);
            }
        }

        .skeleton {
            opacity: .7;
            filter: saturate(.9);
        }
    </style>
@endpush

@section('content')
    @php
        $role = auth()->user()->role ?? null;

        $modeText = match ($role) {
            'admin' => 'Mode: Admin',
            'operating' => 'Mode: Operating',
            'owner' => 'Mode: Owner',
            default => 'Mode: User',
        };

        $modeClass = match ($role) {
            'admin' => 'chip chip--admin',
            'operating' => 'chip chip--operating',
            'owner' => 'chip chip--owner',
            default => 'chip',
        };

        $activeWarehouse = $warehouses->firstWhere('id', $filters['warehouse_id'] ?? null);
        $activeItem = $items->firstWhere('id', $filters['item_id'] ?? null);
        $activeSearch = trim($filters['search'] ?? '');
        $hasBalance = (bool) ($filters['has_balance_only'] ?? false);

        $hideItemSelectOnMobile = in_array($role, ['admin', 'operating'], true);
    @endphp

    <div class="page-wrap">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
            <div>
                <div class="meta mb-1 d-flex flex-wrap align-items-center gap-2">
                    <span>Inventory • Stok per Item (FG)</span>
                    <span class="{{ $modeClass }}"><i class="bi bi-shield-check"></i>{{ $modeText }}</span>
                </div>
                <h5 class="mb-0">📦 Stok Finished Good per Item</h5>
            </div>

            <ul class="nav nav-pills small">
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('inventory.stocks.items') }}">📦 Item</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('inventory.stocks.lots') }}">🎫 LOT</a>
                </li>
            </ul>
        </div>

        {{-- Filter --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <div class="meta mb-2">Filter</div>

                <form id="filterForm" method="GET" action="{{ route('inventory.stocks.items') }}">
                    <div class="filter-grid">
                        <div class="filter-field">
                            <div class="filter-label">Gudang</div>
                            <select name="warehouse_id" class="form-select form-select-sm" id="warehouse_id">
                                <option value="">Semua Gudang</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" @selected(($filters['warehouse_id'] ?? null) == $wh->id)>
                                        {{ $wh->code }} — {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-field {{ $hideItemSelectOnMobile ? 'd-none d-md-block' : '' }}">
                            <div class="filter-label">Item FG</div>
                            <select name="item_id" class="form-select form-select-sm" id="item_id">
                                <option value="">Semua Item FG</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}" @selected(($filters['item_id'] ?? null) == $item->id)>
                                        {{ $item->code }} — {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-field">
                            <div class="filter-label">Cari</div>
                            <input type="text" name="search" id="search" value="{{ $filters['search'] ?? '' }}"
                                class="form-control form-control-sm" placeholder="Kode / nama...">
                        </div>

                        <div class="filter-field">
                            <div class="filter-label">Opsi</div>
                            <div class="filter-check">
                                <input class="form-check-input" type="checkbox" id="has_balance_only"
                                    name="has_balance_only" value="1" @checked($filters['has_balance_only'] ?? false)>
                                <label class="form-check-label" for="has_balance_only">Hanya ada stok</label>
                            </div>
                        </div>
                    </div>

                    <div class="filter-actions mt-2">
                        <button type="submit" class="btn btn-primary btn-sm btn-chip w-100" id="btnApply">Filter</button>
                        <a href="{{ route('inventory.stocks.items') }}"
                            class="btn btn-outline-secondary btn-sm btn-chip w-100" id="btnReset">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Summary --}}
        <div class="mb-2 d-flex flex-wrap align-items-center gap-2" style="color:var(--muted);font-size:.82rem;">
            <span id="summaryTotal">Menampilkan <strong>{{ $stocks->total() }}</strong> item.</span>

            @if ($activeWarehouse)
                <span class="chip"><i class="bi bi-building"></i>{{ $activeWarehouse->code }}</span>
            @endif
            @if ($activeItem)
                <span class="chip"><i class="bi bi-tag"></i>{{ $activeItem->code }}</span>
            @endif
            @if ($activeSearch)
                <span class="chip"><i class="bi bi-search"></i>{{ $activeSearch }}</span>
            @endif
            <span class="chip"><i class="bi bi-filter"></i>{{ $hasBalance ? 'Ada stok' : 'All' }}</span>

            <span id="ajaxState" class="chip" style="display:none;">
                <i class="bi bi-arrow-repeat"></i>Loading
            </span>
        </div>

        {{-- Data --}}
        <div class="card card-main">
            <div class="card-body p-0">
                {{-- Desktop Table --}}
                <div class="d-none d-sm-block">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th style="width:1%">#</th>
                                    <th>Kode</th>
                                    <th>Nama Item</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">FG</th>
                                    <th class="text-end">WIP</th>
                                </tr>
                            </thead>
                            <tbody id="desktopTbody">
                                @forelse($stocks as $index => $row)
                                    <tr>
                                        <td class="text-muted small">{{ $stocks->firstItem() + $index }}</td>
                                        <td class="mono">
                                            <button type="button" class="item-code-link"
                                                data-item-id="{{ $row->item_id }}" data-item-code="{{ $row->item_code }}"
                                                data-item-name="{{ $row->item_name }}"
                                                data-locations-url="{{ route('inventory.stocks.item_locations', $row->item_id) }}">
                                                {{ $row->item_code }}
                                            </button>
                                        </td>
                                        <td>{{ $row->item_name }}</td>
                                        <td class="text-end mono">{{ number_format($row->total_qty, 2, ',', '.') }}</td>
                                        <td class="text-end mono">{{ number_format($row->fg_qty, 2, ',', '.') }}</td>
                                        <td class="text-end mono">{{ number_format($row->wip_qty, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile Cards --}}
                <div class="d-sm-none" id="mobileList">
                    @forelse($stocks as $index => $row)
                        <div class="row-card">
                            <div class="row-title">
                                <div class="mono">
                                    <span class="text-muted small me-2">#{{ $stocks->firstItem() + $index }}</span>
                                    <button type="button" class="item-code-link" data-item-id="{{ $row->item_id }}"
                                        data-item-code="{{ $row->item_code }}" data-item-name="{{ $row->item_name }}"
                                        data-locations-url="{{ route('inventory.stocks.item_locations', $row->item_id) }}">
                                        {{ $row->item_code }}
                                    </button>
                                </div>
                                <div class="mono fw-semibold">{{ number_format($row->total_qty, 2, ',', '.') }}</div>
                            </div>
                            <div class="name">{{ $row->item_name }}</div>
                            <div class="row-metrics">
                                <span class="m-pill"><span class="mono">FG</span> <span
                                        class="mono">{{ number_format($row->fg_qty, 2, ',', '.') }}</span></span>
                                <span class="m-pill"><span class="mono">WIP</span> <span
                                        class="mono">{{ number_format($row->wip_qty, 2, ',', '.') }}</span></span>
                                <span class="m-pill"><span class="mono">Total</span> <span
                                        class="mono">{{ number_format($row->total_qty, 2, ',', '.') }}</span></span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">Tidak ada data.</div>
                    @endforelse
                </div>

                <div class="p-2 border-top" id="paginationWrap">
                    {!! $stocks->hasPages() ? $stocks->links() : '' !!}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Lokasi Item --}}
    <div class="modal fade" id="itemLocationsModal" tabindex="-1" aria-labelledby="itemLocationsModalLabel"
        aria-hidden="true" data-stockcard-base-url="{{ route('inventory.stock_card.index') }}">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h6 class="modal-title mb-0" id="itemLocationsModalLabel">Posisi Stok Item</h6>
                        <div class="small text-muted" id="itemLocationsSubtitle"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <div id="itemLocationsLoading" class="small text-muted mb-2" style="display:none;">Mengambil data...
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:1%">#</th>
                                    <th>Gudang</th>
                                    <th class="text-end">Qty</th>
                                    <th style="width:1%"></th>
                                </tr>
                            </thead>
                            <tbody id="itemLocationsTbody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer small">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('filterForm');
            const ajaxState = document.getElementById('ajaxState');
            const summaryTotal = document.getElementById('summaryTotal');

            const desktopTbody = document.getElementById('desktopTbody');
            const mobileList = document.getElementById('mobileList');
            const paginationWrap = document.getElementById('paginationWrap');

            const modalEl = document.getElementById('itemLocationsModal');
            const tbodyLoc = document.getElementById('itemLocationsTbody');
            const subtitleEl = document.getElementById('itemLocationsSubtitle');
            const loadingLoc = document.getElementById('itemLocationsLoading');
            const stockCardBaseUrl = modalEl?.dataset.stockcardBaseUrl || '';
            const bsModal = modalEl ? new bootstrap.Modal(modalEl) : null;

            const fmt = (n) => new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(n || 0));
            const esc = (s) => String(s ?? '')
                .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;').replaceAll("'", '&#039;');

            const setLoading = (on) => {
                if (ajaxState) ajaxState.style.display = on ? 'inline-flex' : 'none';
                if (desktopTbody) desktopTbody.classList.toggle('skeleton', on);
                if (mobileList) mobileList.classList.toggle('skeleton', on);
            };

            const buildQueryFromForm = () => {
                const fd = new FormData(form);
                const params = new URLSearchParams();
                for (const [k, v] of fd.entries()) {
                    if (v === '' || v === null) continue;
                    // checkbox: kalau tidak dicentang, FormData tidak mengirim -> aman
                    params.set(k, v);
                }
                return params.toString();
            };

            const pushUrl = (qs) => {
                const base = form.getAttribute('action') || window.location.pathname;
                const url = qs ? `${base}?${qs}` : base;
                window.history.pushState({}, '', url);
            };

            const renderRows = (rows, meta) => {
                // Desktop
                if (desktopTbody) {
                    if (!rows.length) {
                        desktopTbody.innerHTML =
                            `<tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data.</td></tr>`;
                    } else {
                        const startNo = ((meta.current_page - 1) * meta.per_page) + 1;
                        desktopTbody.innerHTML = rows.map((r, i) => `
                    <tr>
                        <td class="text-muted small">${startNo + i}</td>
                        <td class="mono">
                            <button type="button" class="item-code-link"
                                data-item-id="${r.item_id}"
                                data-item-code="${esc(r.item_code)}"
                                data-item-name="${esc(r.item_name)}"
                                data-locations-url="${esc(r.locations_url)}">${esc(r.item_code)}</button>
                        </td>
                        <td>${esc(r.item_name)}</td>
                        <td class="text-end mono">${fmt(r.total_qty)}</td>
                        <td class="text-end mono">${fmt(r.fg_qty)}</td>
                        <td class="text-end mono">${fmt(r.wip_qty)}</td>
                    </tr>
                `).join('');
                    }
                }

                // Mobile
                if (mobileList) {
                    if (!rows.length) {
                        mobileList.innerHTML = `<div class="text-center py-4 text-muted">Tidak ada data.</div>`;
                    } else {
                        const startNo = ((meta.current_page - 1) * meta.per_page) + 1;
                        mobileList.innerHTML = rows.map((r, i) => `
                    <div class="row-card">
                        <div class="row-title">
                            <div class="mono">
                                <span class="text-muted small me-2">#${startNo + i}</span>
                                <button type="button" class="item-code-link"
                                    data-item-id="${r.item_id}"
                                    data-item-code="${esc(r.item_code)}"
                                    data-item-name="${esc(r.item_name)}"
                                    data-locations-url="${esc(r.locations_url)}">${esc(r.item_code)}</button>
                            </div>
                            <div class="mono fw-semibold">${fmt(r.total_qty)}</div>
                        </div>
                        <div class="name">${esc(r.item_name)}</div>
                        <div class="row-metrics">
                            <span class="m-pill"><span class="mono">FG</span> <span class="mono">${fmt(r.fg_qty)}</span></span>
                            <span class="m-pill"><span class="mono">WIP</span> <span class="mono">${fmt(r.wip_qty)}</span></span>
                            <span class="m-pill"><span class="mono">Total</span> <span class="mono">${fmt(r.total_qty)}</span></span>
                        </div>
                    </div>
                `).join('');
                    }
                }
            };

            const loadPage = async (qs, {
                push = true
            } = {}) => {
                setLoading(true);

                const base = form.getAttribute('action') || window.location.pathname;
                const url = qs ? `${base}?${qs}` : base;

                try {
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });
                    const json = await res.json();

                    if (!json?.ok) throw new Error('Bad response');

                    if (push) pushUrl(qs);

                    renderRows(json.rows || [], json.meta || {
                        current_page: 1,
                        per_page: 50
                    });
                    if (summaryTotal) summaryTotal.innerHTML =
                        `Menampilkan <strong>${json.meta?.total ?? 0}</strong> item.`;
                    if (paginationWrap) paginationWrap.innerHTML = json.pagination_html || '';

                } catch (e) {
                    // fallback: kalau error, refresh normal
                    window.location.href = url;
                    return;
                } finally {
                    setLoading(false);
                }
            };

            // Submit filter => AJAX
            form?.addEventListener('submit', (e) => {
                e.preventDefault();
                const qs = buildQueryFromForm();
                // reset page ketika filter berubah
                const params = new URLSearchParams(qs);
                params.delete('page');
                loadPage(params.toString(), {
                    push: true
                });
            });

            // Pagination click => AJAX
            document.addEventListener('click', (e) => {
                const a = e.target.closest('#paginationWrap a');
                if (!a) return;
                const href = a.getAttribute('href');
                if (!href) return;
                e.preventDefault();

                const u = new URL(href, window.location.origin);
                const qs = u.searchParams.toString();
                loadPage(qs, {
                    push: true
                });
            });

            // Back/forward browser => AJAX reload current qs
            window.addEventListener('popstate', () => {
                const qs = window.location.search.replace(/^\?/, '');
                loadPage(qs, {
                    push: false
                });
            });

            // Modal lokasi item (tetap AJAX)
            const setLoadingLoc = (on) => {
                if (loadingLoc) loadingLoc.style.display = on ? 'block' : 'none';
            };
            const renderEmptyLoc = (msg) => {
                if (!tbodyLoc) return;
                tbodyLoc.innerHTML =
                    `<tr><td colspan="4" class="text-center text-muted py-3">${esc(msg)}</td></tr>`;
            };

            async function loadLocations({
                itemId,
                itemCode,
                itemName,
                url
            }) {
                if (!itemId || !url || !bsModal) return;

                if (subtitleEl) subtitleEl.textContent = `${itemCode} — ${itemName}`;
                if (tbodyLoc) tbodyLoc.innerHTML = '';
                setLoadingLoc(true);
                bsModal.show();

                try {
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await res.json();
                    const locations = data.locations || [];

                    setLoadingLoc(false);

                    if (!locations.length) return renderEmptyLoc('Tidak ada stok di gudang manapun.');

                    tbodyLoc.innerHTML = locations.map((loc, idx) => {
                        const whId = loc.id;
                        const whCode = loc.code || '-';
                        const whName = loc.name || '-';
                        const qty = loc.qty || 0;

                        const stockCardUrl = stockCardBaseUrl ?
                            `${stockCardBaseUrl}?item_id=${encodeURIComponent(itemId)}&warehouse_id=${encodeURIComponent(whId)}` :
                            '#';

                        return `
                    <tr>
                        <td class="text-muted small">${idx + 1}</td>
                        <td>
                            <div class="fw-semibold">${esc(whCode)}</div>
                            <div class="small text-muted">${esc(whName)}</div>
                        </td>
                        <td class="text-end mono">${fmt(qty)}</td>
                        <td class="text-end">
                            <a href="${stockCardUrl}" class="btn btn-outline-secondary btn-sm py-0 px-2" title="Stock Card">
                                <i class="bi bi-journal-text"></i>
                            </a>
                        </td>
                    </tr>
                `;
                    }).join('');
                } catch (e) {
                    setLoadingLoc(false);
                    renderEmptyLoc('Gagal mengambil data posisi stok.');
                }
            }

            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.item-code-link');
                if (!btn) return;
                loadLocations({
                    itemId: btn.dataset.itemId,
                    itemCode: btn.dataset.itemCode || '',
                    itemName: btn.dataset.itemName || '',
                    url: btn.dataset.locationsUrl
                });
            });
        });
    </script>
@endpush
