{{-- resources/views/inventory/stocks/items.blade.php --}}
@extends('layouts.app')

@section('title', 'Inventory • Stok per Item')

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
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, .12) 0,
                    rgba(45, 212, 191, .10) 26%,
                    #f9fafb 60%);
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(15, 23, 42, .92) 0,
                    #020617 65%);
        }

        .card-main {
            background: var(--card);
            border-radius: var(--card-r);
            border: 1px solid var(--br);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .08),
                0 0 0 1px rgba(148, 163, 184, .10);
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

        .item-toggle-btn {
            padding: 0;
            border: none;
            background: none;
            color: #2563eb;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: .3rem;
        }

        .item-toggle-btn .toggle-icon {
            transition: transform .16s ease-out;
        }

        .item-row.is-open .toggle-icon {
            transform: rotate(90deg);
        }

        body[data-theme="dark"] .item-toggle-btn {
            color: #93c5fd;
        }

        /* FILTER */
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

        /* MOBILE LIST */
        @media (max-width: 576px) {
            .page-wrap {
                padding: .8rem .7rem 4.4rem;
            }

            .table thead {
                display: none;
            }

            .row-card {
                border-top: 1px solid rgba(148, 163, 184, .22);
                padding: .65rem .7rem;
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
                margin-top: .25rem;
            }

            .row-metrics {
                display: flex;
                gap: .45rem;
                margin-top: .45rem;
                flex-wrap: wrap;
            }

            .m-pill {
                display: inline-flex;
                align-items: center;
                gap: .35rem;
                padding: .2rem .6rem;
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

            .card-toggle-btn {
                padding: 0;
                border: none;
                background: none;
                display: flex;
                width: 100%;
                justify-content: space-between;
                align-items: flex-start;
                text-align: left;
            }

            .card-toggle-btn .toggle-icon {
                transition: transform .16s ease-out;
            }

            .item-card.is-open .toggle-icon {
                transform: rotate(90deg);
            }
        }

        /* DETAIL DROPDOWN */
        .detail-row {
            background: rgba(148, 163, 184, .06);
        }

        body[data-theme="dark"] .detail-row {
            background: rgba(15, 23, 42, .9);
        }

        .detail-inner {
            padding: .6rem .75rem .7rem;
            font-size: .78rem;
        }

        .detail-inner-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            margin-bottom: .35rem;
            color: var(--muted);
            font-size: .76rem;
        }

        .detail-locations-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-locations-table td {
            padding: .25rem .2rem;
            font-size: .78rem;
        }

        .detail-locations-table tr+tr td {
            border-top: 1px dashed rgba(148, 163, 184, .4);
        }

        .detail-empty {
            font-size: .78rem;
            color: var(--muted);
        }

        .row-detail {
            font-size: .78rem;
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

    <div class="page-wrap" data-stockcard-base-url="{{ route('inventory.stock_card.index') }}">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
            <div>
                <div class="meta mb-1 d-flex flex-wrap align-items-center gap-2">
                    <span>Inventory • Stok per Item</span>
                    <span class="{{ $modeClass }}"><i class="bi bi-shield-check"></i>{{ $modeText }}</span>
                </div>
                <h5 class="mb-0">📦 Stok Barang per Item (FG &amp; WIP)</h5>
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

                <form method="GET" action="{{ route('inventory.stocks.items') }}">
                    <div class="filter-grid">
                        <div class="filter-field">
                            <div class="filter-label">Gudang</div>
                            <select name="warehouse_id" class="form-select form-select-sm">
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
                            <select name="item_id" class="form-select form-select-sm">
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
                            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                                class="form-control form-control-sm" placeholder="Kode / nama...">
                        </div>

                        <div class="filter-field">
                            <div class="filter-label">Opsi</div>
                            <div class="filter-check">
                                <input class="form-check-input" type="checkbox" id="has_balance_only"
                                    name="has_balance_only" value="1" @checked($filters['has_balance_only'] ?? false)>
                                <label class="form-check-label" for="has_balance_only">
                                    Hanya ada stok
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="filter-actions mt-2">
                        <button type="submit" class="btn btn-primary btn-sm btn-chip w-100">
                            Filter
                        </button>
                        <a href="{{ route('inventory.stocks.items') }}"
                            class="btn btn-outline-secondary btn-sm btn-chip w-100">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Summary --}}
        <div class="mb-2 d-flex flex-wrap align-items-center gap-2" style="color:var(--muted);font-size:.82rem;">
            <span>Menampilkan <strong>{{ $stocks->total() }}</strong> item.</span>

            @if ($activeWarehouse)
                <span class="chip"><i class="bi bi-building"></i>{{ $activeWarehouse->code }}</span>
            @endif
            @if ($activeItem)
                <span class="chip"><i class="bi bi-tag"></i>{{ $activeItem->code }}</span>
            @endif
            @if ($activeSearch)
                <span class="chip"><i class="bi bi-search"></i>{{ $activeSearch }}</span>
            @endif
            <span class="chip">
                <i class="bi bi-filter"></i>{{ $hasBalance ? 'Ada stok' : 'All' }}
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
                                    <th>Kode Barang</th>
                                    <th>Nama Item</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Barang Jadi</th>
                                    <th class="text-end">Sedang diproses</th>
                                </tr>
                            </thead>
                            <tbody id="desktopTbody">
                                @forelse ($stocks as $index => $row)
                                    <tr class="item-row" data-item-id="{{ $row->item_id }}"
                                        data-item-code="{{ $row->item_code }}" data-item-name="{{ $row->item_name }}"
                                        data-locations-url="{{ route('inventory.stocks.item_locations', $row->item_id) }}">
                                        <td class="text-muted small">
                                            {{ $stocks->firstItem() + $index }}
                                        </td>
                                        <td class="mono">
                                            <button type="button" class="item-toggle-btn js-row-toggle">
                                                <i class="bi bi-caret-right-fill toggle-icon"></i>
                                                <span>{{ $row->item_code }}</span>
                                            </button>
                                        </td>
                                        <td>{{ $row->item_name }}</td>
                                        <td class="text-end mono">
                                            {{ number_format($row->total_qty, 2, ',', '.') }}
                                        </td>
                                        <td class="text-end mono">
                                            {{ number_format($row->fg_qty, 2, ',', '.') }}
                                        </td>
                                        <td class="text-end mono">
                                            {{ number_format($row->wip_qty, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            Tidak ada data.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile Cards --}}
                <div class="d-sm-none" id="mobileList">
                    @forelse ($stocks as $index => $row)
                        <div class="row-card item-card" data-item-id="{{ $row->item_id }}"
                            data-item-code="{{ $row->item_code }}" data-item-name="{{ $row->item_name }}"
                            data-locations-url="{{ route('inventory.stocks.item_locations', $row->item_id) }}">
                            <div class="row-title">
                                <button type="button" class="card-toggle-btn js-card-toggle">
                                    <div class="mono">
                                        <span class="text-muted small me-2">
                                            #{{ $stocks->firstItem() + $index }}
                                        </span>
                                        <span>{{ $row->item_code }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="mono fw-semibold">
                                            {{ number_format($row->total_qty, 2, ',', '.') }}
                                        </div>
                                        <i class="bi bi-caret-right-fill toggle-icon"></i>
                                    </div>
                                </button>
                            </div>
                            <div class="name">{{ $row->item_name }}</div>
                            <div class="row-metrics">
                                <span class="m-pill">
                                    <span class="mono">FG</span>
                                    <span class="mono">
                                        {{ number_format($row->fg_qty, 2, ',', '.') }}
                                    </span>
                                </span>
                                <span class="m-pill">
                                    <span class="mono">WIP</span>
                                    <span class="mono">
                                        {{ number_format($row->wip_qty, 2, ',', '.') }}
                                    </span>
                                </span>
                                <span class="m-pill">
                                    <span class="mono">Total</span>
                                    <span class="mono">
                                        {{ number_format($row->total_qty, 2, ',', '.') }}
                                    </span>
                                </span>
                            </div>
                            <div class="row-detail mt-2" style="display:none;"></div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            Tidak ada data.
                        </div>
                    @endforelse
                </div>

                <div class="p-2 border-top">
                    {!! $stocks->links() !!}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const pageWrap = document.querySelector('.page-wrap');
            const stockCardBaseUrl = pageWrap?.dataset.stockcardBaseUrl || '';

            const fmt = (n) => new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(n || 0));

            const esc = (s) => String(s ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const buildLocationsHtml = (locations, itemId) => {
                if (!locations.length) {
                    return `<div class="detail-empty">Tidak ada stok di gudang manapun.</div>`;
                }

                const rows = locations.map((loc, idx) => {
                    const whId = loc.id;
                    const whCode = esc(loc.code || '-');
                    const whName = esc(loc.name || '-');
                    const qty = fmt(loc.qty || 0);

                    const stockCardUrl = stockCardBaseUrl ?
                        `${stockCardBaseUrl}?item_id=${encodeURIComponent(itemId)}&warehouse_id=${encodeURIComponent(whId)}` :
                        '#';

                    return `
                        <tr>
                            <td class="text-muted small">${idx + 1}</td>
                            <td>
                                <div class="fw-semibold">${whCode}</div>
                                <div class="small text-muted">${whName}</div>
                            </td>
                            <td class="text-end mono">${qty}</td>
                            <td class="text-end">
                                <a href="${stockCardUrl}"
                                   class="btn btn-outline-secondary btn-sm py-0 px-2"
                                   title="Stock Card">
                                    <i class="bi bi-journal-text"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                }).join('');

                // Tanpa thead, hanya tbody
                return `
                    <table class="detail-locations-table">
                        <tbody>
                            ${rows}
                        </tbody>
                    </table>
                `;
            };

            const fetchLocations = async (url) => {
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });
                const data = await res.json();
                return data.locations || [];
            };

            // Desktop toggle
            const handleDesktopToggle = async (btn) => {
                const row = btn.closest('.item-row');
                if (!row) return;

                const alreadyOpen = row.classList.contains('is-open');
                const next = row.nextElementSibling;

                if (alreadyOpen && next && next.classList.contains('detail-row')) {
                    next.remove();
                    row.classList.remove('is-open');
                    return;
                }

                if (next && next.classList.contains('detail-row')) {
                    next.remove();
                }

                const itemId = row.dataset.itemId;
                const url = row.dataset.locationsUrl || '';
                if (!itemId || !url) return;

                row.classList.add('is-open');

                const detailTr = document.createElement('tr');
                detailTr.className = 'detail-row';
                detailTr.innerHTML = `
                    <td colspan="6">
                        <div class="detail-inner">

                            <div class="detail-body">Mengambil data...</div>
                        </div>
                    </td>
                `;
                row.insertAdjacentElement('afterend', detailTr);

                const detailBody = detailTr.querySelector('.detail-body');

                try {
                    const locations = await fetchLocations(url);
                    detailBody.innerHTML = buildLocationsHtml(locations, itemId);
                } catch (err) {
                    detailBody.innerHTML =
                        `<div class="detail-empty">Gagal mengambil data posisi stok.</div>`;
                }
            };

            // Mobile toggle
            const handleMobileToggle = async (btn) => {
                const card = btn.closest('.item-card');
                if (!card) return;

                const rowDetail = card.querySelector('.row-detail');
                if (!rowDetail) return;

                const isOpen = card.classList.contains('is-open');

                if (isOpen) {
                    card.classList.remove('is-open');
                    rowDetail.style.display = 'none';
                    rowDetail.innerHTML = '';
                    return;
                }

                const itemId = card.dataset.itemId;
                const url = card.dataset.locationsUrl || '';
                if (!itemId || !url) return;

                card.classList.add('is-open');
                rowDetail.style.display = 'block';
                rowDetail.innerHTML =
                    `<div class="detail-empty">Mengambil data posisi stok...</div>`;

                try {
                    const locations = await fetchLocations(url);

                    rowDetail.innerHTML = `
                        <div class="detail-inner">
                            <div class="detail-inner-header">
                                <div class="small text-muted">Posisi stok per gudang</div>
                            </div>
                            ${buildLocationsHtml(locations, itemId)}
                        </div>
                    `;
                } catch (err) {
                    rowDetail.innerHTML =
                        `<div class="detail-empty">Gagal mengambil data posisi stok.</div>`;
                }
            };

            document.addEventListener('click', (e) => {
                const desktopBtn = e.target.closest('.js-row-toggle');
                if (desktopBtn) {
                    e.preventDefault();
                    handleDesktopToggle(desktopBtn);
                    return;
                }

                const row = e.target.closest('tr.item-row');
                if (row && !e.target.closest('a') && !e.target.closest('button')) {
                    const toggleBtn = row.querySelector('.js-row-toggle');
                    if (toggleBtn) {
                        e.preventDefault();
                        handleDesktopToggle(toggleBtn);
                    }
                    return;
                }

                const mobileBtn = e.target.closest('.js-card-toggle');
                if (mobileBtn) {
                    e.preventDefault();
                    handleMobileToggle(mobileBtn);
                    return;
                }
            });
        });
    </script>
@endpush
