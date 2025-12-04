@extends('layouts.app')

@section('title', 'Shipments • Keluar Barang')

@push('head')
    <style>
        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 26%,
                    #f9fafb 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.10),
                0 0 0 1px rgba(148, 163, 184, 0.08);
        }

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .65rem;
            font-size: .75rem;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: rgba(15, 23, 42, 0.02);
        }

        .badge-status {
            border-radius: 999px;
            padding: .18rem .65rem;
            font-size: .72rem;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .badge-status-draft {
            background: rgba(245, 158, 11, 0.08);
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge-status-submitted {
            background: rgba(34, 197, 94, 0.10);
            color: #166534;
            border: 1px solid rgba(34, 197, 94, 0.35);
        }

        .table-list thead th {
            border-bottom-width: 1px;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7280;
            background: rgba(15, 23, 42, 0.02);
        }

        .table-list tbody td {
            vertical-align: middle;
            border-top-color: rgba(148, 163, 184, 0.2);
        }

        .shipment-no {
            font-weight: 600;
            font-size: .92rem;
        }

        .shipment-no a {
            text-decoration: none;
        }

        .shipment-no a:hover {
            text-decoration: underline;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-1">
                    Shipments Keluar Barang
                </h1>
                <p class="text-muted mb-0 small">
                    Rekap dokumen pengeluaran barang dari gudang (scan shipment).
                </p>
            </div>
            <a href="{{ route('shipments.create') }}" class="btn btn-primary">
                <i class="bi bi-upc-scan me-1"></i>
                Shipment Baru
            </a>
        </div>

        {{-- Flash --}}
        @if (session('status') === 'success')
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @elseif (session('status') === 'error')
            <div class="alert alert-danger">
                {{ session('message') }}
            </div>
        @endif

        {{-- Filter + summary --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('shipments.index') }}" class="mb-3">
                    <div class="row g-2 align-items-end">
                        {{-- Date from --}}
                        <div class="col-md-2">
                            <label class="form-label small text-uppercase text-muted mb-1">
                                Dari Tanggal
                            </label>
                            <input type="date" name="date_from" class="form-control form-control-sm"
                                value="{{ $filters['date_from'] ?? '' }}">
                        </div>

                        {{-- Date to --}}
                        <div class="col-md-2">
                            <label class="form-label small text-uppercase text-muted mb-1">
                                Sampai Tanggal
                            </label>
                            <input type="date" name="date_to" class="form-control form-control-sm"
                                value="{{ $filters['date_to'] ?? '' }}">
                        </div>

                        {{-- Warehouse --}}
                        <div class="col-md-2">
                            <label class="form-label small text-uppercase text-muted mb-1">
                                Gudang
                            </label>
                            <select name="warehouse_id" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" @selected(($filters['warehouse_id'] ?? null) == $wh->id)>
                                        {{ $wh->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Customer --}}
                        <div class="col-md-2">
                            <label class="form-label small text-uppercase text-muted mb-1">
                                Customer
                            </label>
                            <select name="customer_id" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(($filters['customer_id'] ?? null) == $customer->id)>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Store --}}
                        <div class="col-md-2">
                            <label class="form-label small text-uppercase text-muted mb-1">
                                Store
                            </label>
                            <select name="store_id" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}" @selected(($filters['store_id'] ?? null) == $store->id)>
                                        {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Search --}}
                        <div class="col-md-2">
                            <label class="form-label small text-uppercase text-muted mb-1">
                                Cari No Shipment
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="q" class="form-control" placeholder="SHP-..."
                                    value="{{ $filters['q'] ?? '' }}">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            Menampilkan <strong>{{ $shipments->total() }}</strong> shipment
                            (halaman {{ $shipments->currentPage() }} / {{ $shipments->lastPage() }}).
                        </div>
                        <div>
                            @if (array_filter($filters))
                                <a href="{{ route('shipments.index') }}" class="btn btn-link btn-sm text-decoration-none">
                                    Reset filter
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                @php
                    $pageTotalQty = $shipments->sum('total_items');
                @endphp

                {{-- Summary --}}
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <span class="badge-soft">
                        <span class="text-muted small">Shipment di halaman ini:</span>
                        <span class="fw-semibold ms-1">{{ $shipments->count() }}</span>
                    </span>
                    <span class="badge-soft">
                        <span class="text-muted small">Total Qty keluar (halaman ini):</span>
                        <span class="fw-semibold ms-1">{{ number_format($pageTotalQty, 0, ',', '.') }}</span>
                    </span>
                </div>

                {{-- Tabel --}}
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-list mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 120px;">Tanggal</th>
                                <th>No Shipment</th>
                                <th style="width: 120px;">Gudang</th>
                                <th>Customer / Store</th>
                                <th style="width: 120px;" class="text-end">Total Qty</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($shipments as $shipment)
                                <tr>
                                    <td class="text-muted small">
                                        {{ ($shipments->currentPage() - 1) * $shipments->perPage() + $loop->iteration }}
                                    </td>
                                    <td class="small">
                                        {{ id_date($shipment->date) }}
                                    </td>
                                    <td>
                                        <div class="shipment-no">
                                            <a href="{{ route('shipments.show', $shipment) }}">
                                                {{ $shipment->shipment_no }}
                                            </a>
                                        </div>
                                        <div class="small text-muted">
                                            Dibuat oleh {{ $shipment->creator?->name ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small">
                                            {{ $shipment->warehouse?->code ?? '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $shipment->warehouse?->name ?? '' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($shipment->customer)
                                            <div class="small fw-semibold">
                                                {{ $shipment->customer->name }}
                                            </div>
                                        @endif
                                        @if ($shipment->store)
                                            <div class="small text-muted">
                                                Channel: {{ $shipment->store->name }}
                                            </div>
                                        @endif
                                        @if (!$shipment->customer && !$shipment->store)
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold">
                                            {{ number_format($shipment->total_items, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($shipment->status === 'draft')
                                            <span class="badge-status badge-status-draft">
                                                Draft
                                            </span>
                                        @else
                                            <span class="badge-status badge-status-submitted">
                                                Submitted
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('shipments.show', $shipment) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Belum ada shipment. Buat baru dengan tombol
                                        <span class="fw-semibold">Shipment Baru</span> di atas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-3">
                    {{ $shipments->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
