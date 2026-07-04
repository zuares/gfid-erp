{{-- resources/views/inventory/stocks/items.blade.php --}}
@extends('layouts.app')

@section('title', 'Inventory • Stock by Item')

@push('head')
    @include('inventory.stocks.items.styles')
    <style>
        /* Sticky thead saat tabel di-scroll vertikal */
        .stock-table-scroll {
            max-height: 65vh;
            overflow-y: auto;
        }

        .stock-table-scroll table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background: var(--card);
            /* tutup celah border saat scroll */
            box-shadow: inset 0 -1px 0 var(--br);
        }

        body[data-theme="light"] .stock-table-scroll table thead th {
            background: #ffffff;
        }

        body[data-theme="dark"] .stock-table-scroll table thead th {
            background: #0b1220;
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

        $warehouseId = (int) ($filters['warehouse_id'] ?? 0);
        $activeWarehouse = null;
        if ($isOwner && $warehouseId > 0) {
            $activeWarehouse = collect($warehouses ?? [])->firstWhere('id', $warehouseId);
        }

        $rowsNow = method_exists($stocks, 'getCollection') ? $stocks->getCollection() : collect($stocks);

        $initTotalQty = (float) (($hppSummary['total_qty'] ?? null) ?? $rowsNow->sum(fn($r) => (float) ($r->total_qty ?? 0)));
        $initTotalVal = (float) (($hppSummary['total_value'] ?? null) ?? $rowsNow->sum(fn($r) => (float) ($r->stock_value ?? 0)));
        $initAvgHpp = $initTotalQty > 0 ? $initTotalVal / $initTotalQty : 0;
        $initAvgAds = (float) (($hppSummary['avg_ads'] ?? null) ?? ($rowsNow->count() ? (float) $rowsNow->avg(fn($r) => (float) ($r->ads ?? 0)) : 0));

        $initByCat = $hppByCategory ?? $rowsNow
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

        // grouping dropdown by type
        $whGroups = collect($warehouses ?? [])
            ->groupBy(fn($w) => (string) ($w->type ?? 'other'))
            ->all();

        $typeLabel = [
            'fg' => 'FG',
            'ready_to_sell' => 'RTS',
            'production' => 'PRODUCTION',
            'wip' => 'WIP',
            'raw_material' => 'RAW MATERIAL',
            'reject' => 'REJECT',
            'internal' => 'INTERNAL',
            'other' => 'OTHER',
        ];
    @endphp

    <div id="stocksItemsPage" class="page-wrap" data-stockcard-base-url="{{ route('inventory.stock_card.index') }}"
        data-hide-rts="{{ $role === 'operating' ? '1' : '0' }}" data-is-owner="{{ $isOwner ? '1' : '0' }}"
        data-selected-warehouse-id="{{ $warehouseId }}">

        @if (session('success'))
            <div class="alert alert-success py-2 px-3 mb-2" style="font-size:.82rem;">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2 px-3 mb-2" style="font-size:.82rem;">{{ session('error') }}</div>
        @endif

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

                        @if ($isOwner && $activeWarehouse)
                            <span class="pill"><i class="bi bi-house"></i>{{ $activeWarehouse->name }}</span>
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

        {{-- OWNER INSIGHTS --}}
        @if ($isOwner)
            <div class="cardx mb-2">
                <div class="cardx-b">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <div class="meta">Owner Insights</div>

                        <div class="d-flex align-items-center gap-2">
                            <form method="POST" action="{{ route('inventory.stocks.sync_hpp') }}"
                                onsubmit="return confirm('Sync HPP: tarik HPP master (snapshot aktif) ke kolom HPP item untuk valuasi stok?\n\nIni TIDAK menyentuh harga lot / jurnal — hanya HPP referensi.');"
                                class="m-0">
                                @csrf
                                <button class="btn btn-outline-success btn-sm" type="submit"
                                    title="Tarik HPP master ke valuasi stok (items.hpp)">
                                    <i class="bi bi-arrow-repeat"></i> Sync HPP
                                </button>
                            </form>

                            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse"
                                data-bs-target="#hppByCatCollapse" aria-expanded="false" aria-controls="hppByCatCollapse">
                                <i class="bi bi-diagram-3"></i> HPP by Kategori
                            </button>
                        </div>
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
                    <div class="controls {{ $isOwner ? 'is-owner' : '' }}">
                        <div>
                            <div class="meta mb-1">Search</div>
                            <div class="search">
                                <i class="bi bi-search"></i>
                                <input type="text" name="search" id="searchInput"
                                    value="{{ $filters['search'] ?? '' }}" class="form-control form-control-sm"
                                    placeholder="Item code / name" autocomplete="off" inputmode="search">
                            </div>
                        </div>

                        {{-- Owner Gudang (name only) --}}
                        @if ($isOwner)
                            <div>
                                <div class="meta mb-1">Gudang</div>
                                <select name="warehouse_id" id="warehouseSelect" class="form-select form-select-sm">
                                    <option value="">Semua Gudang</option>
                                    @foreach ($whGroups as $type => $list)
                                        <optgroup label="{{ $typeLabel[$type] ?? strtoupper($type) }}">
                                            @foreach ($list as $wh)
                                                <option value="{{ $wh->id }}" @selected($warehouseId === (int) $wh->id)
                                                    title="{{ $wh->code }}">
                                                    {{ $wh->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                        @endif

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
                    <div class="table-responsive stock-table-scroll">
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
                                    @php $mm = $moverMeta((float)($row->ads ?? 0)); @endphp
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

                {{-- Mobile list tetap --}}
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
    @include('inventory.stocks.items.scripts', ['currentPage' => (int) ($stocks->currentPage() ?? 1)])
@endpush
