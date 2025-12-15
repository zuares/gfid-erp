{{-- resources/views/inventory/stocks/items.blade.php --}}
@extends('layouts.app')

@section('title', 'Inventory • Stok Finished Good per Item')

@push('head')
    <style>
        :root {
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
            padding: .75rem .75rem 3.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.12) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 60%);
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(15, 23, 42, 0.9) 0,
                    #020617 65%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            box-shadow:
                0 8px 24px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(148, 163, 184, 0.10);
        }

        .meta-label {
            font-size: .7rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #6b7280;
        }

        body[data-theme="dark"] .meta-label {
            color: #9ca3af;
        }

        .header-sub {
            font-size: .82rem;
            color: #6b7280;
        }

        body[data-theme="dark"] .header-sub {
            color: #9ca3af;
        }

        .summary-text {
            font-size: .80rem;
            color: #6b7280;
        }

        body[data-theme="dark"] .summary-text {
            color: #9ca3af;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .btn-chip {
            border-radius: 999px;
            font-size: .78rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding-inline: 1rem;
            padding-block: .35rem;
        }

        .mode-chip {
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

        .mode-chip--admin {
            border-color: var(--chip2-br);
            background: var(--chip2-bg);
            color: var(--chip2-tx);
        }

        .mode-chip--operating {
            border-color: var(--chip-br);
            background: var(--chip-bg);
            color: var(--chip-tx);
        }

        .mode-chip--owner {
            border-color: var(--chip3-br);
            background: var(--chip3-bg);
            color: var(--chip3-tx);
        }

        .table-wrap {
            overflow-x: auto;
        }

        .table thead th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .table tbody tr:hover {
            background: rgba(239, 246, 255, 0.65);
        }

        body[data-theme="dark"] .table tbody tr:hover {
            background: rgba(15, 23, 42, 0.96);
        }

        /* Kode item bisa di-klik */
        .item-code-link {
            padding: 0;
            border: none;
            background: none;
            color: #2563eb;
            font-weight: 600;
        }

        .item-code-link:hover {
            text-decoration: underline;
        }

        body[data-theme="dark"] .item-code-link {
            color: #93c5fd;
        }

        .modal-header {
            border-bottom-color: rgba(148, 163, 184, 0.35);
        }

        .modal-footer {
            border-top-color: rgba(148, 163, 184, 0.35);
        }
    </style>
@endpush

@section('content')
    @php
        $role = auth()->user()->role ?? null;

        $modeText = match ($role) {
            'admin' => 'Mode: Admin (RTS & WIP-SEW)',
            'operating' => 'Mode: Operating (Produksi)',
            'owner' => 'Mode: Owner (Semua)',
            default => 'Mode: User',
        };

        $modeClass = match ($role) {
            'admin' => 'mode-chip mode-chip--admin',
            'operating' => 'mode-chip mode-chip--operating',
            'owner' => 'mode-chip mode-chip--owner',
            default => 'mode-chip',
        };

        $activeWarehouse = $warehouses->firstWhere('id', $filters['warehouse_id'] ?? null);
        $activeItem = $items->firstWhere('id', $filters['item_id'] ?? null);
        $activeSearch = trim($filters['search'] ?? '');
        $hasBalance = (bool) ($filters['has_balance_only'] ?? true);
    @endphp

    <div class="page-wrap py-3 py-md-4">
        {{-- Header + Tabs --}}
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
            <div>
                <div class="meta-label mb-1 d-flex flex-wrap align-items-center gap-2">
                    <span>Inventory • Stok per Item (Finished Good)</span>
                    <span class="{{ $modeClass }}">
                        <i class="bi bi-shield-check"></i>
                        {{ $modeText }}
                    </span>
                </div>

                <h5 class="mb-1">
                    📦 Rekap Stok Finished Good per Item
                </h5>

                <div class="header-sub">
                    Hanya item <strong>type = finished_good</strong>.
                    Total stok per item, terpisah antara <strong>Finished Good</strong> (WH-RTS) & <strong>WIP</strong>
                    (WIP-*).<br>
                    Klik <strong>kode item</strong> untuk melihat posisi barang di gudang mana saja.
                </div>
            </div>

            <ul class="nav nav-pills small">
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('inventory.stocks.items') }}">
                        📦 Item
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('inventory.stocks.lots') }}">
                        🎫 LOT
                    </a>
                </li>
            </ul>
        </div>

        {{-- Filter Card --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <div class="meta-label mb-2">Filter</div>

                <form method="GET" class="row g-2 align-items-end filter-row">
                    <div class="col-6 col-md-3">
                        <label for="warehouse_id" class="form-label small">Gudang (opsional)</label>
                        <select name="warehouse_id" id="warehouse_id" class="form-select form-select-sm">
                            <option value="">Semua Gudang</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected(($filters['warehouse_id'] ?? null) == $wh->id)>
                                    {{ $wh->code }} — {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <label for="item_id" class="form-label small">Item Finished Good</label>
                        <select name="item_id" id="item_id" class="form-select form-select-sm">
                            <option value="">Semua Item FG</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}" @selected(($filters['item_id'] ?? null) == $item->id)>
                                    {{ $item->code }} — {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <label for="search" class="form-label small">Cari kode / nama</label>
                        <input type="text" name="search" id="search" value="{{ $filters['search'] ?? '' }}"
                            class="form-control form-control-sm" placeholder="Kode / nama item...">
                    </div>

                    <div class="col-6 col-md-2">
                        <div class="form-check mt-4 pt-1">
                            <input class="form-check-input" type="checkbox" id="has_balance_only" name="has_balance_only"
                                value="1" @checked($filters['has_balance_only'] ?? true)>
                            <label class="form-check-label small" for="has_balance_only">
                                Hanya yang ada stok
                            </label>
                        </div>
                    </div>

                    <div class="col-6 col-md-1 d-flex justify-content-md-end gap-2 mt-2 mt-md-0">
                        <button type="submit" class="btn btn-primary btn-sm w-100 w-md-auto btn-chip">
                            Filter
                        </button>
                    </div>

                    <div class="col-6 col-md-1 d-flex justify-content-md-end mt-1 mt-md-0">
                        <a href="{{ route('inventory.stocks.items') }}"
                            class="btn btn-outline-secondary btn-sm w-100 w-md-auto btn-chip">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Summary --}}
        <div class="mb-2 summary-text d-flex flex-wrap align-items-center gap-2">
            <span>
                Menampilkan <strong>{{ $stocks->total() }}</strong> item.
            </span>

            @if ($activeWarehouse)
                <span class="mode-chip">
                    <i class="bi bi-building"></i>
                    Gudang: {{ $activeWarehouse->code }}
                </span>
            @endif

            @if ($activeItem)
                <span class="mode-chip">
                    <i class="bi bi-tag"></i>
                    Item: {{ $activeItem->code }}
                </span>
            @endif

            @if ($activeSearch)
                <span class="mode-chip">
                    <i class="bi bi-search"></i>
                    Cari: {{ $activeSearch }}
                </span>
            @endif

            <span class="mode-chip">
                <i class="bi bi-filter"></i>
                {{ $hasBalance ? 'Hanya ada stok' : 'Termasuk nol/minus' }}
            </span>
        </div>

        {{-- Table Card --}}
        <div class="card card-main">
            <div class="card-body p-0">
                <div class="table-wrap">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead>
                            <tr class="text-nowrap">
                                <th style="width: 1%">#</th>
                                <th>Kode Item</th>
                                <th>Nama Item</th>
                                <th class="text-end">Stok Total</th>
                                <th class="text-end">Stok (Finished Good)</th>
                                <th class="text-end">Stok WIP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stocks as $index => $row)
                                <tr>
                                    <td class="text-muted small">
                                        {{ $stocks->firstItem() + $index }}
                                    </td>
                                    <td class="mono">
                                        <button type="button" class="item-code-link" data-item-id="{{ $row->item_id }}"
                                            data-item-code="{{ $row->item_code }}" data-item-name="{{ $row->item_name }}"
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
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        Tidak ada data stok yang cocok dengan filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($stocks->hasPages())
                    <div class="p-2 border-top">
                        {{ $stocks->links() }}
                    </div>
                @endif
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
                        <h6 class="modal-title mb-0" id="itemLocationsModalLabel">
                            Posisi Stok Item
                        </h6>
                        <div class="small text-muted" id="itemLocationsSubtitle"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <div id="itemLocationsLoading" class="small text-muted mb-2" style="display: none;">
                        Mengambil data posisi stok...
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 1%">#</th>
                                    <th>Gudang</th>
                                    <th class="text-end">Qty</th>
                                    <th style="width: 1%"></th>
                                </tr>
                            </thead>
                            <tbody id="itemLocationsTbody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer small">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalEl = document.getElementById('itemLocationsModal');
            if (!modalEl) return;

            const tbody = document.getElementById('itemLocationsTbody');
            const subtitleEl = document.getElementById('itemLocationsSubtitle');
            const loadingEl = document.getElementById('itemLocationsLoading');
            const stockCardBaseUrl = modalEl.dataset.stockcardBaseUrl || '';

            const bsModal = new bootstrap.Modal(modalEl);

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

            function renderEmpty(msg) {
                if (!tbody) return;
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">${esc(msg)}</td>
                    </tr>`;
            }

            function setLoading(on) {
                if (!loadingEl) return;
                loadingEl.style.display = on ? 'block' : 'none';
            }

            async function loadLocations({
                itemId,
                itemCode,
                itemName,
                url
            }) {
                if (!itemId || !url) return;

                if (subtitleEl) subtitleEl.textContent = `${itemCode} — ${itemName}`;
                if (tbody) tbody.innerHTML = '';
                setLoading(true);
                bsModal.show();

                try {
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    const locations = data.locations || [];

                    setLoading(false);

                    if (!locations.length) {
                        renderEmpty('Tidak ada stok di gudang manapun.');
                        return;
                    }

                    let html = '';
                    locations.forEach((loc, idx) => {
                        const whId = loc.id;
                        const whCode = loc.code || '-';
                        const whName = loc.name || '-';
                        const qty = loc.qty || 0;

                        const stockCardUrl = stockCardBaseUrl ?
                            `${stockCardBaseUrl}?item_id=${encodeURIComponent(itemId)}&warehouse_id=${encodeURIComponent(whId)}` :
                            '#';

                        html += `
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
                            </tr>`;
                    });

                    if (tbody) tbody.innerHTML = html;

                } catch (e) {
                    setLoading(false);
                    renderEmpty('Gagal mengambil data posisi stok.');
                }
            }

            // Event delegation: lebih ringan daripada bind ke semua tombol
            document.addEventListener('click', function(e) {
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
