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
            padding: .25rem .8rem;
            font-size: .75rem;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: rgba(15, 23, 42, 0.02);
        }

        .badge-label {
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .68rem;
            color: #9ca3af;
        }

        .badge-value {
            font-weight: 600;
            font-size: .9rem;
        }

        .badge-status {
            border-radius: 999px;
            padding: .18rem .7rem;
            font-size: .72rem;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .badge-status-draft {
            background: rgba(245, 158, 11, 0.08);
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.35);
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
            border-top-color: rgba(148, 163, 184, 0.18);
        }

        .shipment-no {
            font-size: .95rem;
            font-weight: 600;
        }

        .shipment-no a {
            text-decoration: none;
        }

        .shipment-no a:hover {
            text-decoration: underline;
        }

        .btn-primary {
            border-radius: 999px;
            padding-inline: 1rem;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        @php
            $pageTotalQty = $shipments->sum('total_items');
        @endphp

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-1">
                    Shipments Keluar Barang
                </h1>
                <p class="text-muted small mb-0">
                    Rekap dokumen barang keluar dari gudang siap jual.
                </p>

                {{-- KPI HEADER --}}
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="badge-soft">
                        <span class="badge-label">Total Shipment</span>
                        <span class="badge-value ms-1">
                            {{ number_format($shipments->total(), 0, ',', '.') }}
                        </span>
                    </span>
                    <span class="badge-soft">
                        <span class="badge-label">Di Halaman Ini</span>
                        <span class="badge-value ms-1">
                            {{ $shipments->count() }}
                        </span>
                    </span>
                    <span class="badge-soft">
                        <span class="badge-label">Total Qty (Halaman Ini)</span>
                        <span class="badge-value ms-1">
                            {{ number_format($pageTotalQty, 0, ',', '.') }}
                        </span>
                    </span>
                </div>
            </div>

            <a href="{{ route('sales.shipments.create') }}" class="btn btn-primary">
                <i class="bi bi-upc-scan me-1"></i>
                Shipment Baru
            </a>
        </div>

        {{-- CARD LIST --}}
        <div class="card card-main">
            <div class="card-body p-0">

                @if ($shipments->count() === 0)
                    <div class="p-4 text-center text-muted">
                        Belum ada shipment. Buat dokumen baru dengan tombol
                        <span class="fw-semibold">Shipment Baru</span> di kanan atas.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-list mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th style="width: 120px;">Tanggal</th>
                                    <th>No Shipment</th>
                                    <th>Store / Channel</th>
                                    <th class="text-end" style="width: 130px;">Total Qty</th>
                                    <th style="width: 120px;">Status</th>
                                    <th style="width: 80px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($shipments as $shipment)
                                    <tr>
                                        <td class="text-muted small">
                                            {{ ($shipments->currentPage() - 1) * $shipments->perPage() + $loop->iteration }}
                                        </td>

                                        <td class="small">
                                            {{ $shipment->date?->format('d M Y') }}
                                        </td>

                                        <td>
                                            <div class="shipment-no">
                                                <a href="{{ route('sales.shipments.show', $shipment) }}">
                                                    {{ $shipment->code }}
                                                </a>
                                            </div>
                                        </td>

                                        <td>
                                            @if ($shipment->store)
                                                <div class="small fw-semibold">
                                                    {{ $shipment->store->name }}
                                                </div>
                                            @else
                                                <span class="small text-muted">-</span>
                                            @endif
                                        </td>

                                        <td class="text-end">
                                            <span class="fw-semibold">
                                                {{ number_format($shipment->total_items ?? 0, 0, ',', '.') }}
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
                                            <a href="{{ route('sales.shipments.show', $shipment) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- PAGINATION --}}
                    <div class="p-3">
                        {{ $shipments->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection
