{{-- resources/views/inventory/stocks/items.blade.php --}}
@extends('layouts.app')

@section('title', 'Inventory • Stok Finished Good per Item')

@push('head')
    <style>
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

        .table-wrap {
            overflow-x: auto;
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

        .summary-text {
            font-size: .80rem;
            color: #6b7280;
        }

        body[data-theme="dark"] .summary-text {
            color: #9ca3af;
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
    <div class="page-wrap py-3 py-md-4">
        {{-- Header + Tabs --}}
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
            <div>
                <div class="meta-label mb-1">
                    Inventory • Stok per Item (Finished Good)
                </div>
                <h5 class="mb-1">
                    📦 Rekap Stok Finished Good per Item
                </h5>
                <div class="header-sub">
                    Hanya item <strong>type = finished_good</strong>.
                    Total stok per item, terpisah antara <strong>Finished Good</strong> (WH-RTS) & <strong>WIP</strong>
                    (gudang WIP-*).<br>
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
                <div class="meta-label mb-2">
                    Filter
                </div>
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
        <div class="mb-2 summary-text">
            Menampilkan <strong>{{ $stocks->total() }}</strong> item finished_good dengan stok.
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
                                        {{-- KODE ITEM KLIKABLE --}}
                                        <button type="button" class="item-code-link" data-item-id="{{ $row->item_id }}"
                                            data-item-code="{{ $row->item_code }}" data-item-name="{{ $row->item_name }}"
                                            data-locations-url="{{ route('inventory.stocks.item_locations', $row->item_id) }}">
                                            {{ $row->item_code }}
                                        </button>
                                    </td>
                                    <td>
                                        {{ $row->item_name }}
                                    </td>
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
                        <div class="small text-muted" id="itemLocationsSubtitle">
                            {{-- diisi via JS --}}
                        </div>
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
                            <tbody id="itemLocationsTbody">
                                {{-- diisi via JS --}}
                            </tbody>
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

            function formatNumber(n) {
                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(n || 0);
            }

            function handleClickItemCode(btn) {
                const itemId = btn.dataset.itemId;
                const itemCode = btn.dataset.itemCode || '';
                const itemName = btn.dataset.itemName || '';
                const url = btn.dataset.locationsUrl;

                if (!itemId || !url) return;

                // Set subtitle
                if (subtitleEl) {
                    subtitleEl.textContent = itemCode + ' — ' + itemName;
                }

                // Clear previous
                if (tbody) {
                    tbody.innerHTML = '';
                }

                if (loadingEl) {
                    loadingEl.style.display = 'block';
                }

                bsModal.show();

                fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (loadingEl) {
                            loadingEl.style.display = 'none';
                        }
                        if (!tbody) return;

                        const locations = data.locations || [];

                        if (!locations.length) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        Tidak ada stok di gudang manapun.
                                    </td>
                                </tr>`;
                            return;
                        }

                        let rowsHtml = '';
                        locations.forEach((loc, idx) => {
                            const whId = loc.id;
                            const whCode = loc.code || '-';
                            const whName = loc.name || '-';
                            const qty = loc.qty || 0;

                            const stockCardUrl = stockCardBaseUrl ?
                                stockCardBaseUrl + '?item_id=' + encodeURIComponent(itemId) +
                                '&warehouse_id=' + encodeURIComponent(whId) :
                                '#';

                            rowsHtml += `
                                <tr>
                                    <td class="text-muted small">${idx + 1}</td>
                                    <td>
                                        <div class="fw-semibold">${whCode}</div>
                                        <div class="small text-muted">${whName}</div>
                                    </td>
                                    <td class="text-end mono">
                                        ${formatNumber(qty)}
                                    </td>
                                    <td class="text-end">
                                        <a href="${stockCardUrl}" class="btn btn-outline-secondary btn-sm py-0 px-2">
                                            <i class="bi bi-journal-text"></i>
                                        </a>
                                    </td>
                                </tr>`;
                        });

                        tbody.innerHTML = rowsHtml;
                    })
                    .catch(() => {
                        if (loadingEl) {
                            loadingEl.style.display = 'none';
                        }
                        if (tbody) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="4" class="text-center text-danger py-3">
                                        Gagal mengambil data posisi stok.
                                    </td>
                                </tr>`;
                        }
                    });
            }

            // Bind klik ke semua kode item
            document.querySelectorAll('.item-code-link').forEach(btn => {
                btn.addEventListener('click', function() {
                    handleClickItemCode(this);
                });
            });
        });
    </script>
@endpush
