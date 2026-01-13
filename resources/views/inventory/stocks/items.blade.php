{{-- resources/views/inventory/stocks/items.blade.php --}}
@extends('layouts.app')

@section('title', 'Inventory • Stock by Item')

@push('head')
    <style>
        :root {
            --r: 14px;
            --br: rgba(148, 163, 184, .22);
            --muted: #6b7280;
            --soft: rgba(148, 163, 184, .10);
            --soft2: rgba(148, 163, 184, .06);

            --primary: #0f172a;
            --primary-2: #111827;

            --chip-bg: rgba(59, 130, 246, .10);
            --chip-br: rgba(59, 130, 246, .22);
            --chip-tx: rgba(29, 78, 216, 1);

            --ok-bg: rgba(34, 197, 94, .10);
            --ok-br: rgba(34, 197, 94, .22);
            --ok-tx: rgba(22, 163, 74, 1);
        }

        body[data-theme="dark"] {
            --muted: #9ca3af;
            --br: rgba(148, 163, 184, .18);
            --soft: rgba(148, 163, 184, .10);
            --soft2: rgba(148, 163, 184, .06);

            --primary: #e5e7eb;
            --primary-2: #f3f4f6;

            --chip-bg: rgba(147, 197, 253, .12);
            --chip-br: rgba(147, 197, 253, .22);
            --chip-tx: rgba(191, 219, 254, 1);

            --ok-bg: rgba(34, 197, 94, .14);
            --ok-br: rgba(34, 197, 94, .24);
            --ok-tx: rgba(134, 239, 172, 1);
        }

        .page-wrap {
            max-width: 1200px;
            margin-inline: auto;
            padding: 1rem .9rem 4.2rem;
        }

        body[data-theme="light"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, .10) 0,
                    rgba(45, 212, 191, .08) 22%,
                    #f8fafc 62%);
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(15, 23, 42, .92) 0, #020617 65%);
        }

        /* ===== Cards ===== */
        .cardx {
            background: var(--card);
            border: 1px solid var(--br);
            border-radius: var(--r);
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06),
                0 0 0 1px rgba(148, 163, 184, .08);
        }

        .cardx-h {
            padding: .85rem 1rem;
            border-bottom: 1px solid var(--br);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
        }

        .cardx-b {
            padding: 1rem;
        }

        .meta {
            font-size: .72rem;
            letter-spacing: .10em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        /* ===== Top bar (enterprise) ===== */
        .topbar {
            display: flex;
            flex-direction: column;
            gap: .8rem;
            margin-bottom: .9rem;
        }

        .topbar-row {
            display: flex;
            flex-direction: column;
            gap: .55rem;
        }

        .title {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: .55rem;
        }

        .title h4 {
            margin: 0;
            font-weight: 800;
            letter-spacing: -.01em;
        }

        .subtitle {
            color: var(--muted);
            font-size: .86rem;
        }

        .tabs {
            display: inline-flex;
            border: 1px solid var(--br);
            border-radius: 999px;
            overflow: hidden;
            background: rgba(248, 250, 252, .6);
        }

        body[data-theme="dark"] .tabs {
            background: rgba(15, 23, 42, .55);
        }

        .tabs a {
            padding: .34rem .8rem;
            font-size: .82rem;
            text-decoration: none;
            color: inherit;
            border-right: 1px solid var(--br);
        }

        .tabs a:last-child {
            border-right: none;
        }

        .tabs a.active {
            background: var(--primary);
            color: #fff;
        }

        body[data-theme="dark"] .tabs a.active {
            background: var(--primary-2);
            color: #020617;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .25rem .6rem;
            border-radius: 999px;
            border: 1px solid var(--chip-br);
            background: var(--chip-bg);
            color: var(--chip-tx);
            font-size: .72rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .pill--ok {
            border-color: var(--ok-br);
            background: var(--ok-bg);
            color: var(--ok-tx);
        }

        /* ===== Sticky controls ===== */
        .sticky {
            position: sticky;
            top: .65rem;
            z-index: 25;
        }

        @media (min-width: 768px) {
            .sticky {
                top: .85rem;
            }
        }

        @media (max-width: 576px) {
            .page-wrap {
                padding: 4.8rem .7rem 4.4rem;
            }

            .sticky {
                position: fixed;
                top: calc(env(safe-area-inset-top, 0px) + 5rem);
                left: 0;
                right: 0;
                margin-inline: .7rem;
                z-index: 40;
            }

            .sticky .cardx-b {
                padding: .75rem .85rem;
            }
        }

        .controls {
            display: grid;
            grid-template-columns: 1fr;
            gap: .55rem;
            align-items: end;
        }

        @media (min-width: 768px) {
            .controls {
                grid-template-columns: 1.5fr .8fr .5fr;
                gap: .65rem;
            }
        }

        .search {
            position: relative;
        }

        .search i {
            position: absolute;
            left: .75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            pointer-events: none;
        }

        .search input {
            padding-left: 2.2rem;
        }

        .form-control-sm,
        .form-select-sm {
            border-radius: 10px;
        }

        .summary {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .65rem;
            align-items: center;
            color: var(--muted);
            font-size: .82rem;
        }

        .metric {
            display: inline-flex;
            align-items: baseline;
            gap: .35rem;
            padding: .18rem .55rem;
            border-radius: 999px;
            border: 1px solid var(--br);
            background: var(--soft2);
        }

        body[data-theme="dark"] .metric {
            background: rgba(15, 23, 42, .55);
        }

        .metric .k {
            font-size: .68rem;
            letter-spacing: .10em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .metric .v {
            font-weight: 800;
            color: inherit;
        }

        /* ===== Table ===== */
        .table thead th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .10em;
            color: var(--muted);
            border-bottom: 1px solid var(--br);
            padding: .65rem .75rem;
            white-space: nowrap;
        }

        .table tbody td {
            padding: .62rem .75rem;
            border-top: 1px solid var(--soft);
            font-size: .92rem;
        }

        .table-hover>tbody>tr:hover>* {
            background: rgba(59, 130, 246, .04);
        }

        body[data-theme="dark"] .table-hover>tbody>tr:hover>* {
            background: rgba(59, 130, 246, .08);
        }

        .code-btn {
            padding: 0;
            border: none;
            background: none;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-weight: 800;
            color: #2563eb;
        }

        body[data-theme="dark"] .code-btn {
            color: #93c5fd;
        }

        .caret {
            transition: transform .16s ease-out;
        }

        tr.is-open .caret {
            transform: rotate(90deg);
        }

        .detail-row {
            background: var(--soft2);
        }

        body[data-theme="dark"] .detail-row {
            background: rgba(15, 23, 42, .85);
        }

        .detail-inner {
            padding: .75rem .9rem;
            font-size: .82rem;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-table td {
            padding: .25rem .2rem;
            font-size: .82rem;
        }

        .detail-table tr+tr td {
            border-top: 1px dashed rgba(148, 163, 184, .35);
        }

        .muted {
            color: var(--muted);
        }

        /* ===== Category card ===== */
        .cat-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
        }

        .cat-body {
            margin-top: .6rem;
        }

        .cat-table th,
        .cat-table td {
            padding: .55rem .55rem;
            font-size: .85rem;
        }

        .cat-table tr+tr td {
            border-top: 1px dashed rgba(148, 163, 184, .35);
        }

        /* ===== Loading overlay ===== */
        .data-card {
            position: relative;
            overflow: hidden;
        }

        .data-card .cardx-b {
            transition: opacity .15s ease-out;
        }

        .data-card.is-loading .cardx-b {
            opacity: .55;
        }

        .overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity .15s ease-out;
            background: rgba(255, 255, 255, .55);
            backdrop-filter: blur(4px);
        }

        body[data-theme="dark"] .overlay {
            background: rgba(2, 6, 23, .55);
        }

        .overlay.show {
            opacity: 1;
            pointer-events: auto;
        }

        .overlay-box {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .55rem .9rem;
            border-radius: 999px;
            border: 1px solid var(--br);
            background: rgba(255, 255, 255, .55);
        }

        body[data-theme="dark"] .overlay-box {
            background: rgba(2, 6, 23, .6);
        }

        @media (max-width: 576px) {
            .table thead {
                display: none;
            }

            .mcard {
                border-top: 1px solid var(--br);
                padding: .75rem .85rem;
            }

            .mcard:first-child {
                border-top: none;
            }

            .mcard-btn {
                padding: 0;
                border: none;
                background: none;
                width: 100%;
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                text-align: left;
                gap: .65rem;
            }

            .m-left {
                display: flex;
                gap: .45rem;
                align-items: flex-start;
            }

            .m-no {
                font-size: .7rem;
                color: var(--muted);
                margin-top: .2rem;
            }

            .m-code {
                font-weight: 900;
                font-size: 1rem;
            }

            .m-name {
                color: var(--muted);
                font-size: .86rem;
                margin-top: .2rem;
                line-height: 1.15;
            }

            .m-right {
                text-align: right;
            }

            .m-metric {
                display: grid;
                gap: .35rem;
            }

            .m-metric .k {
                font-size: .68rem;
                color: var(--muted);
                text-transform: uppercase;
                letter-spacing: .10em;
            }

            .m-metric .v {
                font-weight: 900;
                font-size: .95rem;
            }

            .mcard .caret {
                color: var(--muted);
            }

            .m-detail {
                display: none;
                margin-top: .65rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $role = auth()->user()->role ?? null;

        $modeText = match ($role) {
            'admin' => 'Admin',
            'operating' => 'Operating',
            'owner' => 'Owner',
            default => 'User',
        };

        $activeSearch = trim($filters['search'] ?? '');
        $sortVal = $filters['sort'] ?? 'code';
        $dirVal = $filters['dir'] ?? 'asc';
    @endphp

    <div class="page-wrap" data-stockcard-base-url="{{ route('inventory.stock_card.index') }}"
        data-role="{{ $role }}" data-hide-rts="{{ $role === 'operating' ? '1' : '0' }}">

        {{-- TOP BAR --}}
        <div class="topbar">
            <div class="topbar-row">
                <div class="title">
                    <h4>Inventory</h4>
                    <span class="pill"><i class="bi bi-shield-check"></i>{{ $modeText }}</span>
                    <span class="pill pill--ok"><i class="bi bi-calculator"></i>HPP</span>
                </div>
                <div class="subtitle">Stock by item with FG / WIP, HPP and valuation.</div>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="tabs">
                    <a class="active" href="{{ route('inventory.stocks.items') }}">Items</a>
                    <a href="{{ route('inventory.stocks.lots') }}">Lots</a>
                </div>

                @if ($activeSearch)
                    <span class="pill"><i class="bi bi-search"></i>{{ $activeSearch }}</span>
                @endif
            </div>
        </div>

        {{-- CONTROLS (sticky) --}}
        <div class="cardx sticky mb-3">
            <div class="cardx-b">
                <form method="GET" action="{{ route('inventory.stocks.items') }}" id="stockFilterForm">
                    <div class="controls">
                        <div>
                            <div class="meta mb-2">Search</div>
                            <div class="search">
                                <i class="bi bi-search"></i>
                                <input type="text" name="search" id="searchInput" value="{{ $filters['search'] ?? '' }}"
                                    class="form-control form-control-sm" placeholder="Item code / name">
                            </div>
                        </div>

                        <div>
                            <div class="meta mb-2">Sort</div>
                            <select name="sort" id="sortSelect" class="form-select form-select-sm">
                                <option value="value" @selected($sortVal === 'value')>Value</option>
                                <option value="code" @selected($sortVal === 'code')>Alphabet</option>
                                <option value="total" @selected($sortVal === 'total')>Total</option>
                                <option value="fg" @selected($sortVal === 'fg')>FG</option>
                                <option value="wip" @selected($sortVal === 'wip')>WIP</option>
                            </select>
                        </div>

                        <div>
                            <div class="meta mb-2">Dir</div>
                            <select name="dir" id="dirSelect" class="form-select form-select-sm">
                                <option value="desc" @selected($dirVal === 'desc')>Desc</option>
                                <option value="asc" @selected($dirVal === 'asc')>Asc</option>
                            </select>
                        </div>
                    </div>
                </form>

                <div class="summary">
                    <span><strong id="sumTotalItems">{{ $stocks->total() }}</strong> items</span>
                    <span class="metric"><span class="k">Qty</span><span class="v mono" id="sumQty">—</span></span>
                    <span class="metric"><span class="k">Value</span><span class="v mono"
                            id="sumValue">—</span></span>
                    <span class="metric"><span class="k">Avg HPP</span><span class="v mono"
                            id="sumAvgHpp">—</span></span>
                </div>
            </div>
        </div>

        {{-- HPP by Category --}}
        <div class="cardx mb-3" id="hppCategoryCard">
            <div class="cardx-h">
                <div>
                    <div class="meta">HPP by Category</div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="toggleHppCatBtn">
                    <i class="bi bi-chevron-down"></i>
                </button>
            </div>
            <div class="cardx-b" id="hppCategoryBody">
                <div class="muted">—</div>
            </div>
        </div>

        {{-- DATA --}}
        <div class="cardx data-card" id="dataCard">
            <div class="overlay" id="loadingOverlay">
                <div class="overlay-box">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                    <span class="small muted">Loading…</span>
                </div>
            </div>

            <div class="cardx-b p-0">
                {{-- Desktop table --}}
                <div class="d-none d-sm-block">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th style="width:1%">#</th>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">FG</th>
                                    <th class="text-end">WIP</th>
                                    <th class="text-end">HPP</th>
                                    <th class="text-end">Value</th>
                                </tr>
                            </thead>
                            <tbody id="desktopTbody">
                                @forelse ($stocks as $index => $row)
                                    <tr class="item-row" data-item-id="{{ $row->item_id }}"
                                        data-locations-url="{{ route('inventory.stocks.item_locations', $row->item_id) }}">
                                        <td class="text-muted small">{{ $stocks->firstItem() + $index }}</td>
                                        <td class="mono">
                                            <button type="button" class="code-btn js-row-toggle">
                                                <i class="bi bi-caret-right-fill caret"></i>
                                                <span>{{ $row->item_code }}</span>
                                            </button>
                                        </td>
                                        <td>{{ $row->item_name }}</td>
                                        <td class="text-end mono">{{ number_format($row->total_qty, 2, ',', '.') }}</td>
                                        <td class="text-end mono">{{ number_format($row->fg_qty, 2, ',', '.') }}</td>
                                        <td class="text-end mono">{{ number_format($row->wip_qty, 2, ',', '.') }}</td>
                                        <td class="text-end mono">
                                            {{ number_format($row->hpp_per_unit ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end mono">{{ number_format($row->stock_value ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile cards --}}
                <div class="d-sm-none" id="mobileList">
                    @forelse ($stocks as $index => $row)
                        <div class="mcard item-card" data-item-id="{{ $row->item_id }}"
                            data-locations-url="{{ route('inventory.stocks.item_locations', $row->item_id) }}">
                            <button type="button" class="mcard-btn js-card-toggle">
                                <div class="m-left">
                                    <div class="m-no mono">#{{ $stocks->firstItem() + $index }}</div>
                                    <div>
                                        <div class="m-code mono">{{ $row->item_code }}</div>
                                        <div class="m-name">{{ $row->item_name }}</div>
                                    </div>
                                </div>
                                <div class="m-right">
                                    <div class="m-metric">
                                        <div>
                                            <div class="k">Total</div>
                                            <div class="v mono">{{ number_format($row->total_qty, 2, ',', '.') }}</div>
                                        </div>
                                        <div>
                                            <div class="k">HPP</div>
                                            <div class="v mono">{{ number_format($row->hpp_per_unit ?? 0, 0, ',', '.') }}
                                            </div>
                                        </div>
                                    </div>
                                    <i class="bi bi-caret-right-fill caret"></i>
                                </div>
                            </button>
                            <div class="m-detail row-detail"></div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">No data.</div>
                    @endforelse
                </div>

                <div class="p-2 border-top" id="paginationWrap">
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
            const hideRtsWarehouse = pageWrap?.dataset.hideRts === '1';

            const fmtQty = (n) => new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(n || 0));
            const fmtMoney = (n) => new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(Number(n || 0));

            const esc = (s) => String(s ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const buildLocationsHtml = (locations, itemId) => {
                const list = hideRtsWarehouse ?
                    (locations || []).filter((loc) => (loc.code || '').toString().toUpperCase() !== 'WH-RTS') :
                    (locations || []);

                if (!list.length) return `<div class="muted">No stock.</div>`;

                const rows = list.map((loc, idx) => {
                    const whId = loc.id;
                    const whCode = esc(loc.code || '-');
                    const whName = esc(loc.name || '-');
                    const qty = fmtQty(loc.qty || 0);

                    const stockCardUrl = stockCardBaseUrl ?
                        `${stockCardBaseUrl}?item_id=${encodeURIComponent(itemId)}&warehouse_id=${encodeURIComponent(whId)}` :
                        '#';

                    return `
                        <tr>
                            <td class="text-muted small">${idx + 1}</td>
                            <td>
                                <div class="fw-semibold">${whCode}</div>
                                <div class="small muted">${whName}</div>
                            </td>
                            <td class="text-end mono">${qty}</td>
                            <td class="text-end">
                                <a href="${stockCardUrl}" class="btn btn-outline-secondary btn-sm py-0 px-2" title="Stock Card">
                                    <i class="bi bi-journal-text"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                }).join('');

                return `<table class="detail-table"><tbody>${rows}</tbody></table>`;
            };

            const fetchLocations = async (url) => {
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                return data.locations || [];
            };

            const handleDesktopToggle = async (btn) => {
                const row = btn.closest('tr.item-row');
                if (!row) return;

                const alreadyOpen = row.classList.contains('is-open');
                const next = row.nextElementSibling;

                if (alreadyOpen && next && next.classList.contains('detail-row')) {
                    next.remove();
                    row.classList.remove('is-open');
                    return;
                }
                if (next && next.classList.contains('detail-row')) next.remove();

                const itemId = row.dataset.itemId;
                const url = row.dataset.locationsUrl || '';
                if (!itemId || !url) return;

                row.classList.add('is-open');

                const detailTr = document.createElement('tr');
                detailTr.className = 'detail-row';
                detailTr.innerHTML = `
                    <td colspan="8">
                        <div class="detail-inner">
                            <div class="detail-body muted">Loading…</div>
                        </div>
                    </td>
                `;
                row.insertAdjacentElement('afterend', detailTr);

                const detailBody = detailTr.querySelector('.detail-body');
                try {
                    const locations = await fetchLocations(url);
                    detailBody.innerHTML = buildLocationsHtml(locations, itemId);
                } catch (err) {
                    detailBody.innerHTML = `<div class="muted">Failed.</div>`;
                }
            };

            const handleMobileToggle = async (btn) => {
                const card = btn.closest('.item-card');
                if (!card) return;

                const detail = card.querySelector('.row-detail');
                if (!detail) return;

                const isOpen = card.classList.contains('is-open');
                if (isOpen) {
                    card.classList.remove('is-open');
                    detail.style.display = 'none';
                    detail.innerHTML = '';
                    return;
                }

                const itemId = card.dataset.itemId;
                const url = card.dataset.locationsUrl || '';
                if (!itemId || !url) return;

                card.classList.add('is-open');
                detail.style.display = 'block';
                detail.innerHTML = `<div class="detail-inner"><div class="muted">Loading…</div></div>`;

                try {
                    const locations = await fetchLocations(url);
                    detail.innerHTML =
                        `<div class="detail-inner">${buildLocationsHtml(locations, itemId)}</div>`;
                } catch (err) {
                    detail.innerHTML = `<div class="detail-inner"><div class="muted">Failed.</div></div>`;
                }
            };

            // ===== AJAX (search + sort) =====
            const form = document.getElementById('stockFilterForm');
            const searchInput = document.getElementById('searchInput');
            const sortSelect = document.getElementById('sortSelect');
            const dirSelect = document.getElementById('dirSelect');

            const desktopTbody = document.getElementById('desktopTbody');
            const mobileList = document.getElementById('mobileList');
            const paginationWrap = document.getElementById('paginationWrap');
            const dataCard = document.getElementById('dataCard');
            const loadingOverlay = document.getElementById('loadingOverlay');

            const sumTotalItems = document.getElementById('sumTotalItems');
            const sumQty = document.getElementById('sumQty');
            const sumValue = document.getElementById('sumValue');
            const sumAvgHpp = document.getElementById('sumAvgHpp');

            const hppCategoryBody = document.getElementById('hppCategoryBody');
            const toggleHppCatBtn = document.getElementById('toggleHppCatBtn');
            let catCollapsed = false;

            toggleHppCatBtn?.addEventListener('click', () => {
                catCollapsed = !catCollapsed;
                if (!hppCategoryBody) return;
                hppCategoryBody.style.display = catCollapsed ? 'none' : 'block';
                toggleHppCatBtn.querySelector('i')?.classList.toggle('bi-chevron-down', !catCollapsed);
                toggleHppCatBtn.querySelector('i')?.classList.toggle('bi-chevron-up', catCollapsed);
            });

            const debounce = (fn, delay = 350) => {
                let t;
                return (...args) => {
                    clearTimeout(t);
                    t = setTimeout(() => fn(...args), delay);
                };
            };

            const setLoading = (on) => {
                if (!dataCard || !loadingOverlay) return;
                if (on) {
                    dataCard.classList.add('is-loading');
                    loadingOverlay.classList.add('show');
                } else {
                    dataCard.classList.remove('is-loading');
                    loadingOverlay.classList.remove('show');
                }
            };

            const buildDesktopRow = (row, index, from) => {
                const no = (from || 0) + index;
                const total = fmtQty(row.total_qty);
                const fg = fmtQty(row.fg_qty);
                const wip = fmtQty(row.wip_qty);
                const hpp = fmtMoney(row.hpp_per_unit || 0);
                const val = fmtMoney(row.stock_value || 0);

                const code = esc(row.item_code);
                const name = esc(row.item_name);
                const locationsUrl = esc(row.locations_url);

                return `
                    <tr class="item-row" data-item-id="${row.item_id}" data-locations-url="${locationsUrl}">
                        <td class="text-muted small">${no}</td>
                        <td class="mono">
                            <button type="button" class="code-btn js-row-toggle">
                                <i class="bi bi-caret-right-fill caret"></i>
                                <span>${code}</span>
                            </button>
                        </td>
                        <td>${name}</td>
                        <td class="text-end mono">${total}</td>
                        <td class="text-end mono">${fg}</td>
                        <td class="text-end mono">${wip}</td>
                        <td class="text-end mono">${hpp}</td>
                        <td class="text-end mono">${val}</td>
                    </tr>
                `;
            };

            const buildMobileCard = (row, index, from) => {
                const no = (from || 0) + index;
                const total = fmtQty(row.total_qty);
                const hpp = fmtMoney(row.hpp_per_unit || 0);

                const code = esc(row.item_code);
                const name = esc(row.item_name);
                const locationsUrl = esc(row.locations_url);

                return `
                    <div class="mcard item-card" data-item-id="${row.item_id}" data-locations-url="${locationsUrl}">
                        <button type="button" class="mcard-btn js-card-toggle">
                            <div class="m-left">
                                <div class="m-no mono">#${no}</div>
                                <div>
                                    <div class="m-code mono">${code}</div>
                                    <div class="m-name">${name}</div>
                                </div>
                            </div>
                            <div class="m-right">
                                <div class="m-metric">
                                    <div>
                                        <div class="k">Total</div>
                                        <div class="v mono">${total}</div>
                                    </div>
                                    <div>
                                        <div class="k">HPP</div>
                                        <div class="v mono">${hpp}</div>
                                    </div>
                                </div>
                                <i class="bi bi-caret-right-fill caret"></i>
                            </div>
                        </button>
                        <div class="m-detail row-detail"></div>
                    </div>
                `;
            };

            const renderCategory = (list) => {
                if (!hppCategoryBody) return;
                const rows = list || [];
                if (!rows.length) {
                    hppCategoryBody.innerHTML = `<div class="muted">—</div>`;
                    return;
                }

                const body = rows.map((r, idx) => {
                    const cat = esc(r.category || 'Uncategorized');
                    const qty = fmtQty(r.total_qty || 0);
                    const val = fmtMoney(r.total_value || 0);
                    const avg = fmtMoney(r.avg_hpp_weighted || 0);

                    return `
                        <tr>
                            <td class="text-muted small">${idx + 1}</td>
                            <td>${cat}</td>
                            <td class="text-end mono">${qty}</td>
                            <td class="text-end mono">${val}</td>
                            <td class="text-end mono">${avg}</td>
                        </tr>
                    `;
                }).join('');

                hppCategoryBody.innerHTML = `
                    <div class="table-responsive">
                        <table class="table mb-0 cat-table">
                            <thead>
                                <tr>
                                    <th style="width:1%">#</th>
                                    <th>Category</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Value</th>
                                    <th class="text-end">Avg</th>
                                </tr>
                            </thead>
                            <tbody>${body}</tbody>
                        </table>
                    </div>
                `;
            };

            const applySummary = (payload) => {
                if (sumTotalItems && payload?.meta?.total !== undefined) {
                    sumTotalItems.textContent = String(payload.meta.total || 0);
                }
                const s = payload?.hpp_summary || null;
                if (!s) return;
                sumQty.textContent = fmtQty(s.total_qty || 0);
                sumValue.textContent = fmtMoney(s.total_value || 0);
                sumAvgHpp.textContent = fmtMoney(s.avg_hpp_weighted || 0);
            };

            const applyStocksData = (payload) => {
                if (!payload || !payload.ok) return;

                const rows = payload.rows || [];
                const from = payload?.meta?.from || 0;

                if (desktopTbody) {
                    desktopTbody.innerHTML = rows.length ?
                        rows.map((row, idx) => buildDesktopRow(row, idx, from)).join('') :
                        `<tr><td colspan="8" class="text-center py-4 text-muted">No data.</td></tr>`;
                }

                if (mobileList) {
                    mobileList.innerHTML = rows.length ?
                        rows.map((row, idx) => buildMobileCard(row, idx, from)).join('') :
                        `<div class="text-center py-4 text-muted">No data.</div>`;
                }

                if (paginationWrap) paginationWrap.innerHTML = payload.pagination_html || '';

                applySummary(payload);
                renderCategory(payload.hpp_by_category || []);
            };

            const fetchStocks = async (extraParams = {}) => {
                if (!form) return;
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);

                Object.entries(extraParams).forEach(([k, v]) => {
                    if (v === undefined || v === null) return;
                    params.set(k, v);
                });

                const url = `${form.action}?${params.toString()}`;

                setLoading(true);
                try {
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    applyStocksData(data);
                } catch (e) {
                    console.error(e);
                } finally {
                    setLoading(false);
                }
            };

            const fetchDebounced = debounce(() => fetchStocks({
                page: 1
            }), 320);

            // Search typing (no uppercase forcing, enterprise-like)
            searchInput?.addEventListener('input', () => fetchDebounced());

            // Sort / dir
            sortSelect?.addEventListener('change', () => fetchStocks({
                page: 1
            }));
            dirSelect?.addEventListener('change', () => fetchStocks({
                page: 1
            }));

            // Submit
            form?.addEventListener('submit', (e) => {
                e.preventDefault();
                fetchStocks({
                    page: 1
                });
            });

            // Pagination via AJAX
            paginationWrap?.addEventListener('click', (e) => {
                const a = e.target.closest('a[href]');
                if (!a) return;
                const url = new URL(a.href);
                const page = url.searchParams.get('page') || 1;
                e.preventDefault();
                fetchStocks({
                    page
                });
            });

            // Click toggles
            document.addEventListener('click', (e) => {
                const desktopBtn = e.target.closest('.js-row-toggle');
                if (desktopBtn) {
                    e.preventDefault();
                    handleDesktopToggle(desktopBtn);
                    return;
                }

                const mobileBtn = e.target.closest('.js-card-toggle');
                if (mobileBtn) {
                    e.preventDefault();
                    handleMobileToggle(mobileBtn);
                    return;
                }
            });

            // Initial fill (summary + category)
            fetchStocks({
                page: {{ (int) ($stocks->currentPage() ?? 1) }}
            });
        });
    </script>
@endpush
