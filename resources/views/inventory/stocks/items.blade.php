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
            gap: .4rem;
            padding: .24rem .6rem;
            border-radius: 999px;
            border: 1px solid var(--chip-br);
            background: var(--chip-bg);
            color: var(--chip-tx);
            font-size: .7rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .chip i {
            font-size: .8rem;
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

        /* ===== PAGE HEADER (desktop/tablet only) ===== */
        .page-header {
            display: flex;
            flex-direction: column;
            gap: .65rem;
            margin-bottom: 1.1rem;
        }

        .page-header-main {
            display: flex;
            flex-direction: column;
            gap: .3rem;
        }

        .page-header-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .4rem;
        }

        .page-header-title {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: .45rem;
        }

        .page-header-title h5 {
            margin: 0;
        }

        .page-header-sub {
            font-size: .8rem;
            color: var(--muted);
        }

        .page-header-actions {
            display: flex;
            justify-content: flex-start;
        }

        .page-tabs .nav-link {
            border-radius: 999px;
            padding: .25rem .75rem;
            font-size: .8rem;
        }

        .page-tabs .nav-link.active {
            box-shadow: 0 0 0 1px rgba(148, 163, 184, .30);
        }

        body[data-theme="light"] .page-tabs .nav-link {
            background: rgba(248, 250, 252, .9);
            border: 1px solid rgba(148, 163, 184, .35);
        }

        body[data-theme="light"] .page-tabs .nav-link.active {
            background: #0f172a;
            color: #f9fafb;
            border-color: transparent;
        }

        body[data-theme="dark"] .page-tabs .nav-link {
            background: rgba(15, 23, 42, .9);
            border: 1px solid rgba(30, 64, 175, .7);
            color: #e5e7eb;
        }

        body[data-theme="dark"] .page-tabs .nav-link.active {
            background: #e5e7eb;
            color: #020617;
        }

        @media (min-width: 768px) {
            .page-header {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }

            .page-header-main {
                max-width: 70%;
            }

            .page-header-actions {
                justify-content: flex-end;
            }
        }

        /* ===== STICKY / FIXED SEARCH CARD (SELALU NEMPEL) ===== */
        .search-card-sticky {
            position: sticky;
            top: .55rem;
            z-index: 25;
        }

        body[data-theme="dark"] .search-card-sticky {
            background: transparent;
        }

        @media (min-width: 768px) {
            .search-card-sticky {
                top: .75rem;
            }
        }

        /* MOBILE: search ( + summary ) fixed di bawah navbar */
        @media (max-width: 576px) {
            .page-wrap {
                /* ruang untuk topbar + search card fixed */
                padding: 4.6rem .7rem 4.4rem;
            }

            .search-card-sticky {
                position: fixed;
                top: calc(env(safe-area-inset-top, 0px) + 5rem);
                left: 0;
                right: 0;
                margin-inline: .7rem;
                z-index: 40;
            }

            .search-card-sticky .card-body {
                padding: .55rem .75rem .55rem;
            }
        }

        /* FILTER (SIMPLE SEARCH ONLY) */
        .filter-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: .55rem;
            align-items: end;
        }

        .filter-label {
            font-size: .74rem;
            color: var(--muted);
            margin: 0 0 .25rem 0;
        }

        .filter-field .form-control {
            /* tidak terlalu rounded */
            border-radius: 8px;
        }

        .filter-field .form-control-sm {
            padding-top: .55rem;
            padding-bottom: .55rem;
        }

        .search-wrapper {
            position: relative;
        }

        .search-wrapper .search-icon {
            position: absolute;
            inset-block: 0;
            left: .7rem;
            display: flex;
            align-items: center;
            pointer-events: none;
            color: var(--muted);
            font-size: .9rem;
        }

        .search-wrapper .form-control {
            padding-left: 2rem;
        }

        /* UPPERCASE visual untuk input search */
        .search-input-uppercase {
            text-transform: uppercase;
        }

        @media (min-width: 768px) {
            .filter-grid {
                grid-template-columns: 1.4fr;
            }
        }

        /* SUMMARY DALAM CARD PENCARIAN (ikut sticky/fixed) */
        .summary-bar {
            color: var(--muted);
            font-size: .82rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .35rem;
            margin-top: .45rem;
        }

        /* MOBILE LIST */
        @media (max-width: 576px) {
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

            .card-toggle-btn {
                padding: 0;
                border: none;
                background: none;
                display: flex;
                width: 100%;
                justify-content: space-between;
                align-items: center;
                text-align: left;
            }

            .card-toggle-main {
                display: flex;
                align-items: flex-start;
                gap: .35rem;
            }

            .card-index {
                font-size: .7rem;
                color: var(--muted);
                margin-top: .12rem;
            }

            .card-code {
                font-size: .95rem;
                font-weight: 700;
            }

            .card-total {
                font-size: .9rem;
                font-weight: 700;
            }

            .card-total .label {
                display: block;
                font-size: .7rem;
                color: var(--muted);
            }

            .card-total .value {
                line-height: 1.1;
            }

            .card-toggle-btn .toggle-icon {
                transition: transform .16s ease-out;
                margin-left: .35rem;
                font-size: .85rem;
                color: var(--muted);
            }

            .item-card.is-open .toggle-icon {
                transform: rotate(90deg);
            }

            .name {
                color: var(--muted);
                font-size: .82rem;
                line-height: 1.2;
                margin-top: .25rem;
            }

            .row-metrics {
                display: none;
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

        /* ===== LOADING OVERLAY & FADE-IN ===== */
        .data-card {
            position: relative;
            overflow: hidden;
        }

        .data-card .card-body {
            transition: opacity .15s ease-out;
        }

        .data-card.is-loading .card-body {
            opacity: .55;
        }

        .loading-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 1rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity .15s ease-out;
            background: linear-gradient(to bottom,
                    rgba(15, 23, 42, .02),
                    rgba(15, 23, 42, .06));
        }

        body[data-theme="dark"] .loading-overlay {
            background: radial-gradient(circle at top,
                    rgba(15, 23, 42, .85),
                    rgba(15, 23, 42, .90));
        }

        .loading-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }

        .loading-box {
            padding: .55rem .9rem;
            border-radius: 999px;
            background: rgba(15, 23, 42, .02);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(148, 163, 184, .3);
        }

        body[data-theme="dark"] .loading-box {
            background: rgba(15, 23, 42, .85);
        }

        .fade-in {
            animation: fadeInUp .18s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        $activeSearch = trim($filters['search'] ?? '');
    @endphp

    <div class="page-wrap" data-stockcard-base-url="{{ route('inventory.stock_card.index') }}"
        data-role="{{ $role }}" data-hide-rts="{{ $role === 'operating' ? '1' : '0' }}">

        {{-- HEADER (desktop & tablet only) --}}
        <div class="page-header d-none d-sm-flex">
            <div class="page-header-main">
                <div class="page-header-meta">
                    <span class="meta">Inventory • Stok per Item</span>
                    <span class="{{ $modeClass }}">
                        <i class="bi bi-shield-check"></i>
                        {{ $modeText }}
                    </span>
                </div>

                <div class="page-header-title">
                    <h5>📦 Stok Barang per Item</h5>
                    <span class="page-header-sub">Ringkasan stok FG &amp; WIP per kode barang.</span>
                </div>
            </div>

            <div class="page-header-actions">
                <ul class="nav nav-pills small page-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('inventory.stocks.items') }}">📦 Item</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('inventory.stocks.lots') }}">🎫 LOT</a>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Filter + Summary (sticky / fixed) --}}
        <div class="card card-main mb-3 search-card-sticky">
            <div class="card-body">
                <div class="meta mb-2">Pencarian</div>

                <form method="GET" action="{{ route('inventory.stocks.items') }}" id="stockFilterForm">
                    <div class="filter-grid">
                        <div class="filter-field">
                            <div class="filter-label">Cari Item</div>

                            <div class="search-wrapper">
                                <span class="search-icon">
                                    <i class="bi bi-search"></i>
                                </span>

                                <input type="text" name="search" id="searchInput" value="{{ $filters['search'] ?? '' }}"
                                    class="form-control form-control-sm search-input-uppercase"
                                    placeholder="Kode / nama item...">
                            </div>
                        </div>
                    </div>
                </form>

                {{-- Summary ikut nempel di card, jadi setelah input tetap fixed/sticky --}}
                <div class="summary-bar">
                    <span>Menampilkan <strong>{{ $stocks->total() }}</strong> item.</span>

                    @if ($activeSearch)
                        <span class="chip"><i class="bi bi-search"></i>{{ $activeSearch }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Data --}}
        <div class="card card-main data-card" id="dataCard">
            {{-- Loading overlay --}}
            <div class="loading-overlay" id="loadingOverlay">
                <div class="loading-box text-center">
                    <div class="spinner-border spinner-border-sm mb-1" role="status"></div>
                    <div class="small text-muted">Mengambil data...</div>
                </div>
            </div>

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
                            <button type="button" class="card-toggle-btn js-card-toggle">
                                <div class="card-toggle-main">
                                    <div class="card-index mono">
                                        #{{ $stocks->firstItem() + $index }}
                                    </div>
                                    <div class="mono card-code">
                                        {{ $row->item_code }}
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="mono card-total text-end">
                                        <span class="label">Total</span>
                                        <span class="value">
                                            {{ number_format($row->total_qty, 2, ',', '.') }}
                                        </span>
                                    </div>
                                    <i class="bi bi-caret-right-fill toggle-icon"></i>
                                </div>
                            </button>
                            <div class="name">{{ $row->item_name }}</div>
                            <div class="row-detail mt-2" style="display:none;"></div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            Tidak ada data.
                        </div>
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
            const userRole = pageWrap?.dataset.role || '';
            const hideRtsWarehouse = pageWrap?.dataset.hideRts === '1';

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
                // Filter WH-RTS untuk role operating (front-end guard)
                const list = hideRtsWarehouse ?
                    (locations || []).filter((loc) => {
                        const code = (loc.code || '').toString().toUpperCase();
                        return code !== 'WH-RTS';
                    }) :
                    (locations || []);

                if (!list.length) {
                    return `<div class="detail-empty">Tidak ada stok di gudang manapun.</div>`;
                }

                const rows = list.map((loc, idx) => {
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

            // ====== REALTIME SEARCH + LOADING & FADE-IN ======
            const form = document.getElementById('stockFilterForm');
            const searchInput = document.getElementById('searchInput');
            const desktopTbody = document.getElementById('desktopTbody');
            const mobileList = document.getElementById('mobileList');
            const paginationWrap = document.getElementById('paginationWrap');
            const dataCard = document.getElementById('dataCard');
            const loadingOverlay = document.getElementById('loadingOverlay');

            const debounce = (fn, delay = 350) => {
                let t;
                return (...args) => {
                    clearTimeout(t);
                    t = setTimeout(() => fn(...args), delay);
                };
            };

            const setLoading = (isLoading) => {
                if (!dataCard || !loadingOverlay) return;
                if (isLoading) {
                    dataCard.classList.add('is-loading');
                    loadingOverlay.classList.add('show');
                } else {
                    dataCard.classList.remove('is-loading');
                    loadingOverlay.classList.remove('show');
                }
            };

            const buildDesktopRow = (row, index, from) => {
                const no = (from || 0) + index;
                const total = fmt(row.total_qty);
                const fg = fmt(row.fg_qty);
                const wip = fmt(row.wip_qty);
                const code = esc(row.item_code);
                const name = esc(row.item_name);
                const locationsUrl = esc(row.locations_url);

                return `
                    <tr class="item-row"
                        data-item-id="${row.item_id}"
                        data-item-code="${code}"
                        data-item-name="${name}"
                        data-locations-url="${locationsUrl}">
                        <td class="text-muted small">${no}</td>
                        <td class="mono">
                            <button type="button" class="item-toggle-btn js-row-toggle">
                                <i class="bi bi-caret-right-fill toggle-icon"></i>
                                <span>${code}</span>
                            </button>
                        </td>
                        <td>${name}</td>
                        <td class="text-end mono">${total}</td>
                        <td class="text-end mono">${fg}</td>
                        <td class="text-end mono">${wip}</td>
                    </tr>
                `;
            };

            const buildMobileCard = (row, index, from) => {
                const no = (from || 0) + index;
                const total = fmt(row.total_qty);
                const code = esc(row.item_code);
                const name = esc(row.item_name);
                const locationsUrl = esc(row.locations_url);

                return `
                    <div class="row-card item-card"
                        data-item-id="${row.item_id}"
                        data-item-code="${code}"
                        data-item-name="${name}"
                        data-locations-url="${locationsUrl}">
                        <button type="button" class="card-toggle-btn js-card-toggle">
                            <div class="card-toggle-main">
                                <div class="card-index mono">#${no}</div>
                                <div class="mono card-code">${code}</div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="mono card-total text-end">
                                    <span class="label">Total</span>
                                    <span class="value">${total}</span>
                                </div>
                                <i class="bi bi-caret-right-fill toggle-icon"></i>
                            </div>
                        </button>
                        <div class="name">${name}</div>
                        <div class="row-detail mt-2" style="display:none;"></div>
                    </div>
                `;
            };

            const applyStocksData = (payload) => {
                if (!payload || !payload.ok) return;

                const rows = payload.rows || [];
                const meta = payload.meta || {};
                const from = meta.from || 0;

                // Desktop
                if (desktopTbody) {
                    if (!rows.length) {
                        desktopTbody.innerHTML = `
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Tidak ada data.
                                </td>
                            </tr>
                        `;
                    } else {
                        desktopTbody.innerHTML = rows
                            .map((row, idx) => buildDesktopRow(row, idx, from))
                            .join('');
                    }

                    desktopTbody.classList.remove('fade-in');
                    void desktopTbody.offsetWidth;
                    desktopTbody.classList.add('fade-in');
                }

                // Mobile
                if (mobileList) {
                    if (!rows.length) {
                        mobileList.innerHTML = `
                            <div class="text-center py-4 text-muted">
                                Tidak ada data.
                            </div>
                        `;
                    } else {
                        mobileList.innerHTML = rows
                            .map((row, idx) => buildMobileCard(row, idx, from))
                            .join('');
                    }

                    mobileList.classList.remove('fade-in');
                    void mobileList.offsetWidth;
                    mobileList.classList.add('fade-in');
                }

                // Pagination
                if (paginationWrap) {
                    paginationWrap.innerHTML = payload.pagination_html || '';
                }
            };

            const fetchStocks = async (extraParams = {}) => {
                if (!form) return;

                const formData = new FormData(form);
                const params = new URLSearchParams(formData);

                Object.entries(extraParams).forEach(([key, val]) => {
                    if (val === undefined || val === null) return;
                    params.set(key, val);
                });

                const url = `${form.action}?${params.toString()}`;

                setLoading(true);

                try {
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });

                    if (!res.ok) throw new Error('HTTP ' + res.status);

                    const data = await res.json();
                    applyStocksData(data);
                } catch (err) {
                    console.error(err);
                } finally {
                    setLoading(false);
                }
            };

            const fetchStocksDebounced = debounce(() => fetchStocks({
                page: 1
            }), 350);

            // ====== Search input behavior: select on focus & uppercase on type ======
            if (searchInput) {
                searchInput.addEventListener('focus', (e) => {
                    e.target.select();
                });

                searchInput.addEventListener('click', (e) => {
                    if (e.target.selectionStart === e.target.selectionEnd) {
                        e.target.select();
                    }
                });
            }

            // Submit form (enter di keyboard)
            if (form) {
                form.addEventListener('submit', (e) => {
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
            }

            // Realtime search + uppercase ketika mengetik
            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    const start = searchInput.selectionStart;
                    const end = searchInput.selectionEnd;

                    const upper = (searchInput.value || '').toUpperCase();
                    searchInput.value = upper;

                    if (start !== null && end !== null) {
                        searchInput.setSelectionRange(start, end);
                    }

                    fetchStocksDebounced();
                });
            }

            // Global click handler: toggle detail desktop & mobile
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
