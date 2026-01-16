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

            --code-tx: #0b1220;
            --code-bg: rgba(15, 23, 42, .06);
            --code-br: rgba(15, 23, 42, .14);
            --code-icon: rgba(15, 23, 42, .55);

            --total-tx: #0b1220;
            --total-bg: rgba(34, 197, 94, .10);
            --total-br: rgba(34, 197, 94, .22);
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

            --code-tx: #eaf2ff;
            --code-bg: rgba(147, 197, 253, .14);
            --code-br: rgba(147, 197, 253, .26);
            --code-icon: rgba(191, 219, 254, .85);

            --total-tx: #eafff1;
            --total-bg: rgba(34, 197, 94, .16);
            --total-br: rgba(34, 197, 94, .30);
        }

        .page-wrap {
            max-width: 1280px;
            margin-inline: auto;
            padding: .85rem .85rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, .10) 0,
                    rgba(45, 212, 191, .08) 22%,
                    #f8fafc 62%);
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(15, 23, 42, .92) 0, #020617 65%);
        }

        .cardx {
            background: var(--card);
            border: 1px solid var(--br);
            border-radius: var(--r);
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06),
                0 0 0 1px rgba(148, 163, 184, .08);
        }

        .cardx-b {
            padding: .75rem .85rem;
        }

        .meta {
            font-size: .70rem;
            letter-spacing: .10em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .topbar {
            display: flex;
            flex-direction: column;
            gap: .55rem;
            margin-bottom: .55rem;
        }

        .title {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: .5rem;
        }

        .title h4 {
            margin: 0;
            font-weight: 850;
            letter-spacing: -.01em;
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
            padding: .30rem .70rem;
            font-size: .80rem;
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
            gap: .35rem;
            padding: .20rem .55rem;
            border-radius: 999px;
            border: 1px solid var(--chip-br);
            background: var(--chip-bg);
            color: var(--chip-tx);
            font-size: .70rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .pill--ok {
            border-color: var(--ok-br);
            background: var(--ok-bg);
            color: var(--ok-tx);
        }

        .sticky {
            position: sticky;
            top: .70rem;
            z-index: 25;
        }

        @media (max-width:576px) {
            .page-wrap {
                padding: .8rem .7rem 4.2rem;
            }

            .sticky {
                position: static !important;
                top: auto !important;
                z-index: auto !important;
            }
        }

        .controls {
            display: grid;
            grid-template-columns: 1fr;
            gap: .45rem;
            align-items: end;
        }

        @media (min-width:768px) {
            .controls {
                grid-template-columns: 1.8fr 1fr .7fr;
                gap: .55rem;
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
            gap: .35rem;
            margin-top: .55rem;
            align-items: center;
            color: var(--muted);
            font-size: .80rem;
        }

        .metric {
            display: inline-flex;
            align-items: baseline;
            gap: .35rem;
            padding: .14rem .5rem;
            border-radius: 999px;
            border: 1px solid var(--br);
            background: var(--soft2);
        }

        body[data-theme="dark"] .metric {
            background: rgba(15, 23, 42, .55);
        }

        .metric .k {
            font-size: .66rem;
            letter-spacing: .10em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .metric .v {
            font-weight: 900;
            color: inherit;
        }

        .table thead th {
            font-size: .70rem;
            text-transform: uppercase;
            letter-spacing: .10em;
            color: var(--muted);
            border-bottom: 1px solid var(--br);
            padding: .55rem .65rem;
            white-space: nowrap;
        }

        .table tbody td {
            padding: .52rem .65rem;
            border-top: 1px solid var(--soft);
            font-size: .90rem;
        }

        .code-btn {
            padding: .16rem .5rem;
            border-radius: 10px;
            border: 1px solid var(--code-br);
            background: var(--code-bg);
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-weight: 900;
            letter-spacing: .02em;
            color: var(--code-tx);
            text-decoration: none;
        }

        .code-btn .caret {
            color: var(--code-icon);
        }

        .caret {
            transition: transform .16s ease-out;
        }

        tr.is-open .caret {
            transform: rotate(90deg);
        }

        .badge-mover {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .14rem .5rem;
            border-radius: 999px;
            font-size: .68rem;
            letter-spacing: .10em;
            text-transform: uppercase;
            border: 1px solid var(--br);
            background: var(--soft2);
            color: var(--muted);
            white-space: nowrap;
        }

        .badge-fast {
            border-color: rgba(34, 197, 94, .28);
            background: rgba(34, 197, 94, .10);
            color: rgba(22, 163, 74, 1);
        }

        .badge-med {
            border-color: rgba(59, 130, 246, .28);
            background: rgba(59, 130, 246, .10);
            color: rgba(29, 78, 216, 1);
        }

        .badge-slow {
            border-color: rgba(245, 158, 11, .28);
            background: rgba(245, 158, 11, .10);
            color: rgba(180, 83, 9, 1);
        }

        .badge-dead {
            border-color: rgba(148, 163, 184, .32);
            background: rgba(148, 163, 184, .10);
            color: rgba(71, 85, 105, 1);
        }

        .detail-row {
            background: var(--soft2);
        }

        body[data-theme="dark"] .detail-row {
            background: rgba(15, 23, 42, .85);
        }

        .detail-inner {
            padding: .65rem .75rem;
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
            padding: .45rem .8rem;
            border-radius: 999px;
            border: 1px solid var(--br);
            background: rgba(255, 255, 255, .55);
        }

        body[data-theme="dark"] .overlay-box {
            background: rgba(2, 6, 23, .6);
        }

        /* HPP by category */
        .mini-table {
            width: 100%;
            border-collapse: collapse;
        }

        .mini-table th,
        .mini-table td {
            padding: .48rem .6rem;
            border-top: 1px solid var(--soft);
            vertical-align: top;
        }

        .mini-table thead th {
            border-top: none;
            border-bottom: 1px solid var(--br);
            font-size: .68rem;
            letter-spacing: .10em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .mini-table .cat {
            font-weight: 850;
        }

        /* Mobile cards */
        @media (max-width:576px) {
            .table thead {
                display: none;
            }

            .mcard {
                border-top: 1px solid var(--br);
                padding: .7rem .75rem;
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
                gap: .7rem;
            }

            .m-left {
                display: flex;
                gap: .5rem;
                align-items: flex-start;
            }

            .m-no {
                font-size: .7rem;
                color: var(--muted);
                margin-top: .15rem;
            }

            .m-code {
                color: var(--code-tx);
                font-weight: 900;
                font-size: 1.00rem;
                background: var(--code-bg);
                border: 1px solid var(--code-br);
                border-radius: 10px;
                padding: .14rem .5rem;
                display: inline-flex;
                align-items: center;
            }

            .m-right {
                text-align: right;
            }

            .m-metric {
                display: grid;
                gap: .2rem;
            }

            .m-metric .k {
                font-size: .66rem;
                color: var(--muted);
                text-transform: uppercase;
                letter-spacing: .10em;
            }

            .m-metric .v {
                font-weight: 950;
                font-size: 1.00rem;
                color: var(--total-tx);
                background: var(--total-bg);
                border: 1px solid var(--total-br);
                border-radius: 10px;
                padding: .14rem .5rem;
                display: inline-flex;
                justify-content: flex-end;
                align-items: center;
                min-width: 5.3rem;
            }

            .m-detail {
                display: none;
                margin-top: .55rem;
            }

            /* ✅ Mobile detail KPI grid */
            .m-detail-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: .5rem;
                margin-bottom: .55rem;
            }

            .m-kpi {
                border: 1px solid var(--br);
                background: var(--soft2);
                border-radius: 12px;
                padding: .45rem .55rem;
            }

            .m-kpi .k {
                font-size: .64rem;
                color: var(--muted);
                letter-spacing: .10em;
                text-transform: uppercase;
            }

            .m-kpi .v {
                margin-top: .10rem;
                font-weight: 900;
            }

            .m-kpi .v.mono {
                font-variant-numeric: tabular-nums;
            }

            /* ✅ Anti zoom iOS when focus */
            #searchInput {
                font-size: 16px;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $roleRaw = (string) (auth()->user()->role ?? '');
        $role = strtolower(trim($roleRaw));
        $isOwner = $role === 'owner';

        $modeText = match ($role) {
            'admin' => 'Admin',
            'operating' => 'Operating',
            'owner' => 'Owner',
            default => 'User',
        };

        $activeSearch = trim($filters['search'] ?? '');
        $sortVal = $filters['sort'] ?? 'code';
        $dirVal = $filters['dir'] ?? 'asc';

        $rowsNow = method_exists($stocks, 'getCollection') ? $stocks->getCollection() : collect($stocks);

        $initTotalQty = (float) $rowsNow->sum(fn($r) => (float) ($r->total_qty ?? 0));
        $initTotalVal = (float) $rowsNow->sum(fn($r) => (float) ($r->stock_value ?? 0));
        $initAvgHpp = $initTotalQty > 0 ? $initTotalVal / $initTotalQty : 0;
        $initAvgAds = $rowsNow->count() ? (float) $rowsNow->avg(fn($r) => (float) ($r->ads ?? 0)) : 0;

        $initByCat = $rowsNow
            ->groupBy(fn($r) => $r->category_name ?? 'Uncategorized')
            ->map(function ($grp, $catName) {
                $qty = (float) $grp->sum('total_qty');
                $val = (float) $grp->sum('stock_value');
                return [
                    'category' => (string) $catName,
                    'total_qty' => $qty,
                    'total_value' => $val,
                    'avg_hpp_weighted' => $qty > 0 ? $val / $qty : 0.0,
                ];
            })
            ->values()
            ->all();

        $moverMeta = function (float $ads): array {
            if ($ads >= 2) {
                return ['cls' => 'badge-fast', 'label' => 'FAST', 'icon' => 'bi bi-lightning-charge'];
            }
            if ($ads >= 0.5) {
                return ['cls' => 'badge-med', 'label' => 'MED', 'icon' => 'bi bi-graph-up'];
            }
            if ($ads >= 0.1) {
                return ['cls' => 'badge-slow', 'label' => 'SLOW', 'icon' => 'bi bi-hourglass-split'];
            }
            return ['cls' => 'badge-dead', 'label' => 'DEAD', 'icon' => 'bi bi-moon'];
        };
    @endphp

    <div id="stocksItemsPage" class="page-wrap" data-stockcard-base-url="{{ route('inventory.stock_card.index') }}"
        data-hide-rts="{{ $role === 'operating' ? '1' : '0' }}" data-is-owner="{{ $isOwner ? '1' : '0' }}">

        {{-- TOP BAR --}}
        <div class="topbar">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                <div>
                    <div class="title">
                        <h4>Inventory</h4>
                        <span class="pill"><i class="bi bi-shield-check"></i>{{ $modeText }}</span>

                        @if ($isOwner)
                            <span class="pill pill--ok"><i class="bi bi-cash-coin"></i>HPP</span>
                            <span class="pill pill--ok"><i class="bi bi-archive"></i>Value</span>
                            <span class="pill pill--ok"><i class="bi bi-graph-up"></i>ADS</span>
                            <span class="pill pill--ok"><i class="bi bi-hourglass-split"></i>Cover</span>
                        @endif

                        @if ($activeSearch)
                            <span class="pill"><i class="bi bi-search"></i>{{ $activeSearch }}</span>
                        @endif
                    </div>
                </div>

                <div class="tabs">
                    <a class="active" href="{{ route('inventory.stocks.items') }}">Items</a>
                    <a href="{{ route('inventory.stocks.lots') }}">Lots</a>
                </div>
            </div>
        </div>

        {{-- OWNER INSIGHTS (compact + collapse) --}}
        @if ($isOwner)
            <div class="cardx mb-2">
                <div class="cardx-b">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <div class="meta">Owner Insights (current page)</div>

                        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse"
                            data-bs-target="#hppByCatCollapse" aria-expanded="false" aria-controls="hppByCatCollapse">
                            <i class="bi bi-diagram-3"></i> HPP by Kategori
                        </button>
                    </div>

                    <div class="summary">
                        <span class="metric"><span class="k">Items</span><span class="v mono"
                                id="sumTotalItems">{{ $stocks->total() }}</span></span>
                        <span class="metric"><span class="k">Qty</span><span class="v mono"
                                id="sumQty">{{ number_format($initTotalQty, 2, ',', '.') }}</span></span>
                        <span class="metric"><span class="k">Value</span><span class="v mono"
                                id="sumValue">{{ number_format($initTotalVal, 0, ',', '.') }}</span></span>
                        <span class="metric"><span class="k">Avg HPP</span><span class="v mono"
                                id="sumAvgHpp">{{ number_format($initAvgHpp, 0, ',', '.') }}</span></span>
                        <span class="metric"><span class="k">Avg ADS</span><span class="v mono"
                                id="sumAvgAds">{{ number_format($initAvgAds, 2, ',', '.') }}</span></span>
                    </div>

                    <div class="collapse mt-2" id="hppByCatCollapse">
                        <div class="border rounded-3 p-2"
                            style="border-color: var(--br) !important; background: var(--soft2);">
                            <div class="meta mb-2">HPP by Kategori</div>

                            <div id="hppByCategoryWrap">
                                <div class="table-responsive">
                                    <table class="mini-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Kategori</th>
                                                <th class="text-end">Qty</th>
                                                <th class="text-end">Value</th>
                                                <th class="text-end">Avg HPP</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($initByCat as $c)
                                                <tr>
                                                    <td class="cat">{{ $c['category'] }}</td>
                                                    <td class="text-end mono">
                                                        {{ number_format((float) $c['total_qty'], 2, ',', '.') }}</td>
                                                    <td class="text-end mono">
                                                        {{ number_format((float) $c['total_value'], 0, ',', '.') }}</td>
                                                    <td class="text-end mono">
                                                        {{ number_format((float) $c['avg_hpp_weighted'], 0, ',', '.') }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-3">No data.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="small text-muted mt-2">* Weighted Avg HPP = total_value / total_qty</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- CONTROLS --}}
        <div class="cardx sticky mb-2" id="controlsCard">
            <div class="cardx-b">
                <form method="GET" action="{{ route('inventory.stocks.items') }}" id="stockFilterForm">
                    <div class="controls">
                        <div>
                            <div class="meta mb-1">Search</div>
                            <div class="search">
                                <i class="bi bi-search"></i>
                                <input type="text" name="search" id="searchInput"
                                    value="{{ $filters['search'] ?? '' }}" class="form-control form-control-sm"
                                    placeholder="Item code / name" autocomplete="off" inputmode="search">
                            </div>
                        </div>

                        <div>
                            <div class="meta mb-1">Sort</div>
                            <select name="sort" id="sortSelect" class="form-select form-select-sm">
                                <option value="code" @selected($sortVal === 'code')>Alphabet</option>
                                <option value="total" @selected($sortVal === 'total')>Total</option>
                                <option value="fg" @selected($sortVal === 'fg')>FG</option>
                                <option value="wip" @selected($sortVal === 'wip')>WIP</option>
                                @if ($isOwner)
                                    <option value="value" @selected($sortVal === 'value')>Value</option>
                                    <option value="ads" @selected($sortVal === 'ads')>ADS</option>
                                    <option value="cover" @selected($sortVal === 'cover')>Coverage</option>
                                @endif
                            </select>
                        </div>

                        <div>
                            <div class="meta mb-1">Dir</div>
                            <select name="dir" id="dirSelect" class="form-select form-select-sm">
                                <option value="desc" @selected($dirVal === 'desc')>Desc</option>
                                <option value="asc" @selected($dirVal === 'asc')>Asc</option>
                            </select>
                        </div>
                    </div>
                </form>
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
                                    @if ($isOwner)
                                        <th class="text-end">HPP</th>
                                        <th class="text-end">Value</th>
                                        <th class="text-end">ADS</th>
                                        <th class="text-end">Cover</th>
                                    @endif
                                </tr>
                            </thead>

                            <tbody id="desktopTbody">
                                @forelse ($stocks as $index => $row)
                                    @php
                                        $ads = (float) ($row->ads ?? 0);
                                        $mm = $moverMeta($ads);
                                    @endphp

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

                                        <td class="text-end mono">
                                            {{ number_format((float) ($row->total_qty ?? 0), 2, ',', '.') }}</td>
                                        <td class="text-end mono">
                                            {{ number_format((float) ($row->fg_qty ?? 0), 2, ',', '.') }}</td>
                                        <td class="text-end mono">
                                            {{ number_format((float) ($row->wip_qty ?? 0), 2, ',', '.') }}</td>

                                        @if ($isOwner)
                                            <td class="text-end mono">
                                                {{ number_format((float) ($row->hpp_per_unit ?? 0), 0, ',', '.') }}</td>
                                            <td class="text-end mono">
                                                {{ number_format((float) ($row->stock_value ?? 0), 0, ',', '.') }}</td>

                                            <td class="text-end">
                                                <div class="d-flex justify-content-end align-items-center gap-2">
                                                    <span
                                                        class="mono">{{ number_format((float) ($row->ads ?? 0), 2, ',', '.') }}</span>
                                                    <span class="badge-mover {{ $mm['cls'] }}"><i
                                                            class="{{ $mm['icon'] }}"></i>{{ $mm['label'] }}</span>
                                                </div>
                                            </td>

                                            <td class="text-end mono">
                                                {{ number_format((float) ($row->coverage_days ?? 0), 0, ',', '.') }}</td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isOwner ? 10 : 6 }}" class="text-center py-4 text-muted">No
                                            data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile list (server render with data-row) --}}
                <div class="d-sm-none" id="mobileList">
                    @forelse ($stocks as $index => $row)
                        @php
                            $rowPayload = [
                                'total_qty' => (float) ($row->total_qty ?? 0),
                                'fg_qty' => (float) ($row->fg_qty ?? 0),
                                'wip_qty' => (float) ($row->wip_qty ?? 0),
                                'hpp_per_unit' => $isOwner ? (float) ($row->hpp_per_unit ?? 0) : null,
                                'stock_value' => $isOwner ? (float) ($row->stock_value ?? 0) : null,
                                'ads' => $isOwner ? (float) ($row->ads ?? 0) : null,
                                'coverage_days' => $isOwner ? (float) ($row->coverage_days ?? 0) : null,
                            ];
                        @endphp

                        <div class="mcard item-card" data-item-id="{{ $row->item_id }}"
                            data-locations-url="{{ route('inventory.stocks.item_locations', $row->item_id) }}"
                            data-row='@json($rowPayload)'>
                            <button type="button" class="mcard-btn js-card-toggle">
                                <div class="m-left">
                                    <div class="m-no mono">#{{ $stocks->firstItem() + $index }}</div>
                                    <div>
                                        <div class="m-code mono">{{ $row->item_code }}</div>
                                        <div class="small text-muted mt-1">{{ $row->item_name }}</div>
                                    </div>
                                </div>
                                <div class="m-right">
                                    <div class="m-metric">
                                        <div>
                                            <div class="k">Total</div>
                                            <div class="v mono">
                                                {{ number_format((float) ($row->total_qty ?? 0), 2, ',', '.') }}</div>
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
        (() => {
            if (window.__stocksItemsBooted) return;
            window.__stocksItemsBooted = true;

            const $ = (sel, root = document) => root.querySelector(sel);
            const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

            const esc = (s) => String(s ?? '')
                .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;').replaceAll("'", "&#039;");

            const escAttr = (s) => String(s ?? '')
                .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;').replaceAll("'", "&#039;");

            const num0 = (v) => {
                const n = Number(v);
                return Number.isFinite(n) ? n : 0;
            };

            const fmtQty = (n) => new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(n || 0));
            const fmtMoney = (n) => new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(Number(n || 0));

            const moverMeta = (ads) => {
                const a = Number(ads || 0);
                if (a >= 2) return {
                    cls: 'badge-fast',
                    label: 'FAST',
                    icon: 'bi bi-lightning-charge'
                };
                if (a >= .5) return {
                    cls: 'badge-med',
                    label: 'MED',
                    icon: 'bi bi-graph-up'
                };
                if (a >= .1) return {
                    cls: 'badge-slow',
                    label: 'SLOW',
                    icon: 'bi bi-hourglass-split'
                };
                return {
                    cls: 'badge-dead',
                    label: 'DEAD',
                    icon: 'bi bi-moon'
                };
            };

            document.addEventListener('DOMContentLoaded', () => {
                const pageWrap = document.getElementById('stocksItemsPage');
                if (!pageWrap) return;

                const isOwner = (pageWrap.dataset.isOwner === '1');
                const hideRtsWarehouse = (pageWrap.dataset.hideRts === '1');
                const stockCardBaseUrl = pageWrap.dataset.stockcardBaseUrl || '';

                const form = $('#stockFilterForm');
                const searchInput = $('#searchInput');
                const sortSelect = $('#sortSelect');
                const dirSelect = $('#dirSelect');

                const desktopTbody = $('#desktopTbody');
                const mobileList = $('#mobileList');
                const paginationWrap = $('#paginationWrap');

                const dataCard = $('#dataCard');
                const loadingOverlay = $('#loadingOverlay');

                const sumTotalItems = $('#sumTotalItems');
                const sumQty = $('#sumQty');
                const sumValue = $('#sumValue');
                const sumAvgHpp = $('#sumAvgHpp');
                const sumAvgAds = $('#sumAvgAds');
                const hppByCategoryWrap = $('#hppByCategoryWrap');

                // ✅ UX: focus search + select all if has value
                const focusSearch = () => {
                    if (!searchInput) return;

                    const ae = document.activeElement;
                    const tag = (ae?.tagName || '').toLowerCase();
                    if (ae && ['input', 'textarea', 'select', 'button'].includes(tag)) return;

                    setTimeout(() => {
                        try {
                            searchInput.focus({
                                preventScroll: true
                            });
                            const v = String(searchInput.value || '');
                            if (v.trim() !== '') {
                                searchInput.setSelectionRange(0, v.length); // ✅ select all
                            }
                        } catch (_) {}
                    }, 60);
                };

                focusSearch();
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') focusSearch();
                });

                const setLoading = (on) => {
                    if (!dataCard || !loadingOverlay) return;
                    dataCard.classList.toggle('is-loading', !!on);
                    loadingOverlay.classList.toggle('show', !!on);
                };

                const fetchJson = async (url) => {
                    const res = await fetch(url, {
                        method: 'GET',
                        credentials: 'include', // kalau 1 domain bisa 'same-origin'
                        cache: 'no-store',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                };

                const buildDesktopRow = (row, index, from) => {
                    const no = (from || 0) + index;

                    if (!isOwner) {
                        return `
          <tr class="item-row" data-item-id="${row.item_id}" data-locations-url="${esc(row.locations_url)}">
            <td class="text-muted small">${no}</td>
            <td class="mono">
              <button type="button" class="code-btn js-row-toggle">
                <i class="bi bi-caret-right-fill caret"></i><span>${esc(row.item_code)}</span>
              </button>
            </td>
            <td>${esc(row.item_name)}</td>
            <td class="text-end mono">${fmtQty(num0(row.total_qty))}</td>
            <td class="text-end mono">${fmtQty(num0(row.fg_qty))}</td>
            <td class="text-end mono">${fmtQty(num0(row.wip_qty))}</td>
          </tr>
        `;
                    }

                    const adsVal = num0(row.ads);
                    const mm = moverMeta(adsVal);

                    return `
        <tr class="item-row" data-item-id="${row.item_id}" data-locations-url="${esc(row.locations_url)}">
          <td class="text-muted small">${no}</td>
          <td class="mono">
            <button type="button" class="code-btn js-row-toggle">
              <i class="bi bi-caret-right-fill caret"></i><span>${esc(row.item_code)}</span>
            </button>
          </td>
          <td>${esc(row.item_name)}</td>
          <td class="text-end mono">${fmtQty(num0(row.total_qty))}</td>
          <td class="text-end mono">${fmtQty(num0(row.fg_qty))}</td>
          <td class="text-end mono">${fmtQty(num0(row.wip_qty))}</td>
          <td class="text-end mono">${fmtMoney(num0(row.hpp_per_unit))}</td>
          <td class="text-end mono">${fmtMoney(num0(row.stock_value))}</td>
          <td class="text-end">
            <div class="d-flex justify-content-end align-items-center gap-2">
              <span class="mono">${fmtQty(adsVal)}</span>
              <span class="badge-mover ${mm.cls}"><i class="${mm.icon}"></i>${mm.label}</span>
            </div>
          </td>
          <td class="text-end mono">${fmtMoney(num0(row.coverage_days))}</td>
        </tr>
      `;
                };

                const buildMobileCard = (row, index, from) => {
                    const no = (from || 0) + index;
                    const rowJson = escAttr(JSON.stringify({
                        total_qty: row.total_qty,
                        fg_qty: row.fg_qty,
                        wip_qty: row.wip_qty,
                        hpp_per_unit: row.hpp_per_unit,
                        stock_value: row.stock_value,
                        ads: row.ads,
                        coverage_days: row.coverage_days
                    }));

                    return `
        <div class="mcard item-card"
             data-item-id="${row.item_id}"
             data-locations-url="${esc(row.locations_url)}"
             data-row="${rowJson}">
          <button type="button" class="mcard-btn js-card-toggle">
            <div class="m-left">
              <div class="m-no mono">#${no}</div>
              <div>
                <div class="m-code mono">${esc(row.item_code)}</div>
                <div class="small text-muted mt-1">${esc(row.item_name)}</div>
              </div>
            </div>
            <div class="m-right">
              <div class="m-metric">
                <div>
                  <div class="k">Total</div>
                  <div class="v mono">${fmtQty(num0(row.total_qty))}</div>
                </div>
              </div>
              <i class="bi bi-caret-right-fill caret"></i>
            </div>
          </button>
          <div class="m-detail row-detail"></div>
        </div>
      `;
                };

                const buildLocationsHtml = (locations, itemId) => {
                    const list = hideRtsWarehouse ?
                        (locations || []).filter((loc) => (loc.code || '').toString().toUpperCase() !==
                            'WH-RTS') :
                        (locations || []);
                    if (!list.length) return `<div class="text-muted">No stock.</div>`;

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
              <div class="small text-muted">${whName}</div>
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

                const buildHppByCategoryHtml = (byCat) => {
                    const arr = Array.isArray(byCat) ? byCat : [];
                    if (!arr.length) return `<div class="text-muted text-center py-3">No data.</div>`;

                    const rows = arr.map((c) => `
        <tr>
          <td class="cat">${esc(c.category || 'Uncategorized')}</td>
          <td class="text-end mono">${fmtQty(num0(c.total_qty))}</td>
          <td class="text-end mono">${fmtMoney(num0(c.total_value))}</td>
          <td class="text-end mono">${fmtMoney(num0(c.avg_hpp_weighted))}</td>
        </tr>
      `).join('');

                    return `
        <div class="table-responsive">
          <table class="mini-table mb-0">
            <thead>
              <tr>
                <th>Kategori</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Value</th>
                <th class="text-end">Avg HPP</th>
              </tr>
            </thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      `;
                };

                const buildMobileSummaryHtml = (card) => {
                    let r = null;
                    try {
                        r = card.dataset.row ? JSON.parse(card.dataset.row) : null;
                    } catch {
                        r = null;
                    }

                    const fg = fmtQty(num0(r?.fg_qty));
                    const wip = fmtQty(num0(r?.wip_qty));

                    let html = `
        <div class="m-detail-grid">
          <div class="m-kpi">
            <div class="k">FG</div>
            <div class="v mono">${fg}</div>
          </div>
          <div class="m-kpi">
            <div class="k">WIP</div>
            <div class="v mono">${wip}</div>
          </div>
        </div>
      `;

                    if (isOwner) {
                        const hpp = fmtMoney(num0(r?.hpp_per_unit));
                        const val = fmtMoney(num0(r?.stock_value));
                        const ads = fmtQty(num0(r?.ads));
                        const cover = fmtMoney(num0(r?.coverage_days));
                        const mm = moverMeta(num0(r?.ads));

                        html += `
          <div class="m-detail-grid">
            <div class="m-kpi">
              <div class="k">HPP</div>
              <div class="v mono">${hpp}</div>
            </div>
            <div class="m-kpi">
              <div class="k">Value</div>
              <div class="v mono">${val}</div>
            </div>
            <div class="m-kpi">
              <div class="k">ADS</div>
              <div class="v mono">
                ${ads}
                <span class="badge-mover ${mm.cls}" style="margin-left:.35rem;"><i class="${mm.icon}"></i>${mm.label}</span>
              </div>
            </div>
            <div class="m-kpi">
              <div class="k">Cover</div>
              <div class="v mono">${cover}</div>
            </div>
          </div>
        `;
                    }

                    return html;
                };

                const applySummary = (payload) => {
                    if (!isOwner) return;

                    if (sumTotalItems && payload?.meta?.total !== undefined) {
                        sumTotalItems.textContent = String(payload.meta.total || 0);
                    }

                    const s = payload?.hpp_summary || null;
                    const rows = payload?.rows || [];

                    if (s) {
                        if (sumQty) sumQty.textContent = fmtQty(num0(s.total_qty));
                        if (sumValue) sumValue.textContent = fmtMoney(num0(s.total_value));
                        if (sumAvgHpp) sumAvgHpp.textContent = fmtMoney(num0(s.avg_hpp_weighted));
                    }

                    const adsSum = rows.reduce((acc, r) => acc + num0(r.ads), 0);
                    const adsAvg = rows.length ? (adsSum / rows.length) : 0;
                    if (sumAvgAds) sumAvgAds.textContent = fmtQty(adsAvg);

                    if (hppByCategoryWrap) {
                        hppByCategoryWrap.innerHTML = buildHppByCategoryHtml(payload?.hpp_by_category ||
                    []);
                    }
                };

                const applyStocksData = (payload) => {
                    if (!payload || !payload.ok) return;

                    // ✅ safety: owner but payload missing owner fields => don't overwrite
                    if (isOwner) {
                        const first = (payload.rows || [])[0];
                        if (first && first.hpp_per_unit === null && first.stock_value === null && first
                            .ads === null) {
                            console.warn('[stocks-items] owner fields missing in payload; skip overwrite');
                            return;
                        }
                    }

                    const rows = payload.rows || [];
                    const from = payload?.meta?.from || 0;

                    $$('tr.detail-row').forEach(n => n.remove());
                    $$('tr.item-row.is-open').forEach(n => n.classList.remove('is-open'));
                    $$('.item-card.is-open').forEach(n => n.classList.remove('is-open'));

                    if (desktopTbody) {
                        desktopTbody.innerHTML = rows.length ?
                            rows.map((r, idx) => buildDesktopRow(r, idx, from)).join('') :
                            `<tr><td colspan="${isOwner ? 10 : 6}" class="text-center py-4 text-muted">No data.</td></tr>`;
                    }

                    if (mobileList) {
                        mobileList.innerHTML = rows.length ?
                            rows.map((r, idx) => buildMobileCard(r, idx, from)).join('') :
                            `<div class="text-center py-4 text-muted">No data.</div>`;
                    }

                    if (paginationWrap) paginationWrap.innerHTML = payload.pagination_html || '';
                    applySummary(payload);
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
                        const data = await fetchJson(url);
                        applyStocksData(data);
                    } catch (e) {
                        console.error('Fetch JSON failed:', e);
                    } finally {
                        setLoading(false);
                    }
                };

                const debounce = (fn, delay = 320) => {
                    let t;
                    return (...args) => {
                        clearTimeout(t);
                        t = setTimeout(() => fn(...args), delay);
                    };
                };
                const fetchDebounced = debounce(() => fetchStocks({
                    page: 1
                }), 320);

                searchInput?.addEventListener('input', fetchDebounced);
                sortSelect?.addEventListener('change', () => fetchStocks({
                    page: 1
                }));
                dirSelect?.addEventListener('change', () => fetchStocks({
                    page: 1
                }));

                form?.addEventListener('submit', (e) => {
                    e.preventDefault();
                    fetchStocks({
                        page: 1
                    });
                });

                paginationWrap?.addEventListener('click', (e) => {
                    const a = e.target.closest('a[href]');
                    if (!a) return;
                    e.preventDefault();
                    const url = new URL(a.href);
                    fetchStocks({
                        page: url.searchParams.get('page') || 1
                    });
                });

                // desktop detail row
                const openDesktopDetail = (row, html, colspan) => {
                    const next = row.nextElementSibling;
                    if (next && next.classList.contains('detail-row')) next.remove();

                    const tr = document.createElement('tr');
                    tr.className = 'detail-row';
                    tr.innerHTML = `<td colspan="${colspan}"><div class="detail-inner">${html}</div></td>`;
                    row.insertAdjacentElement('afterend', tr);
                };

                const handleDesktopToggle = async (btn) => {
                    const row = btn.closest('tr.item-row');
                    if (!row) return;

                    const colspan = isOwner ? 10 : 6;
                    const isOpen = row.classList.contains('is-open');

                    if (isOpen) {
                        row.classList.remove('is-open');
                        const next = row.nextElementSibling;
                        if (next && next.classList.contains('detail-row')) next.remove();
                        return;
                    }

                    $$('tr.item-row.is-open').forEach((r) => {
                        r.classList.remove('is-open');
                        const n = r.nextElementSibling;
                        if (n && n.classList.contains('detail-row')) n.remove();
                    });

                    const itemId = row.dataset.itemId;
                    const url = row.dataset.locationsUrl || '';
                    if (!itemId || !url) return;

                    row.classList.add('is-open');
                    openDesktopDetail(row, `<div class="text-muted">Loading…</div>`, colspan);

                    try {
                        const data = await fetchJson(url);
                        openDesktopDetail(row, buildLocationsHtml(data.locations || [], itemId),
                            colspan);
                    } catch {
                        openDesktopDetail(row, `<div class="text-muted">Failed.</div>`, colspan);
                    }
                };

                const handleMobileToggle = async (btn) => {
                    const card = btn.closest('.item-card');
                    if (!card) return;

                    const detail = $('.row-detail', card);
                    if (!detail) return;

                    const isOpen = card.classList.contains('is-open');
                    if (isOpen) {
                        card.classList.remove('is-open');
                        detail.style.display = 'none';
                        detail.innerHTML = '';
                        return;
                    }

                    $$('.item-card.is-open').forEach((c) => {
                        c.classList.remove('is-open');
                        const d = $('.row-detail', c);
                        if (d) {
                            d.style.display = 'none';
                            d.innerHTML = '';
                        }
                    });

                    const itemId = card.dataset.itemId;
                    const url = card.dataset.locationsUrl || '';
                    if (!itemId || !url) return;

                    card.classList.add('is-open');
                    detail.style.display = 'block';
                    detail.innerHTML =
                        `<div class="detail-inner"><div class="text-muted">Loading…</div></div>`;

                    try {
                        const data = await fetchJson(url);
                        const summaryHtml = buildMobileSummaryHtml(card);
                        const locHtml = buildLocationsHtml(data.locations || [], itemId);

                        detail.innerHTML = `
          <div class="detail-inner">
            ${summaryHtml}
            ${locHtml}
          </div>
        `;
                    } catch {
                        detail.innerHTML =
                            `<div class="detail-inner"><div class="text-muted">Failed.</div></div>`;
                    }
                };

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

                // initial hydrate
                fetchStocks({
                    page: {{ (int) ($stocks->currentPage() ?? 1) }}
                });
            });
        })();
    </script>
@endpush
