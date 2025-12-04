@extends('layouts.app')

@section('title', 'Detail Shipment • ' . $shipment->shipment_no)

@push('head')
    <style>
        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.12) 0,
                    rgba(45, 212, 191, 0.10) 26%,
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

        .badge-status {
            border-radius: 999px;
            padding: .2rem .75rem;
            font-size: .75rem;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .badge-status-draft {
            background: rgba(245, 158, 11, 0.08);
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.35);
        }

        .badge-status-submitted {
            background: rgba(34, 197, 94, 0.08);
            color: #166534;
            border: 1px solid rgba(34, 197, 94, 0.35);
        }

        .summary-pill {
            border-radius: 999px;
            padding: .25rem .8rem;
            font-size: .8rem;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: rgba(15, 23, 42, 0.02);
        }

        .table-lines thead th {
            border-bottom-width: 1px;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7280;
            background: rgba(15, 23, 42, 0.02);
        }

        .table-lines tbody td {
            vertical-align: middle;
            border-top-color: rgba(148, 163, 184, 0.2);
        }

        .item-code {
            font-weight: 600;
            font-size: .9rem;
        }

        .meta-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #6b7280;
        }
    </style>
@endpush

@section('content')
    @php
        $totalQty = $shipment->lines->sum('qty');
        $totalLines = $shipment->lines->count();
    @endphp

    <div class="page-wrap">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="meta-label mb-1">
                    DETAIL SHIPMENT
                </div>
                <h1 class="h4 mb-1">
                    {{ $shipment->shipment_no }}
                </h1>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="text-muted small">
                        Tanggal {{ id_date($shipment->date) }}
                    </span>

                    @if ($shipment->status === 'draft')
                        <span class="badge-status badge-status-draft">
                            Draft
                        </span>
                    @else
                        <span class="badge-status badge-status-submitted">
                            Submitted
                        </span>
                    @endif
                </div>
            </div>

            <div class="text-end">
                <a href="{{ route('shipments.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                    &larr; Kembali ke list
                </a>
                <a href="{{ route('shipments.from_invoice', $invoice) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-box-arrow-up-right me-1"></i>
                    Buat Shipment dari Invoice
                </a>

                <div class="small text-muted">
                    Dibuat oleh <strong>{{ $shipment->creator?->name ?? '-' }}</strong><br>
                    <span class="text-muted">
                        {{ id_datetime($shipment->created_at) }}
                    </span>
                </div>
            </div>
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

        {{-- Info utama --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="meta-label mb-1">
                            Gudang
                        </div>
                        <div class="fw-semibold">
                            {{ $shipment->warehouse?->code ?? '-' }}
                        </div>
                        <div class="text-muted small">
                            {{ $shipment->warehouse?->name ?? '' }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="meta-label mb-1">
                            Customer
                        </div>
                        @if ($shipment->customer)
                            <div class="fw-semibold">
                                {{ $shipment->customer->name }}
                            </div>
                            @if ($shipment->customer->phone ?? false)
                                <div class="text-muted small">
                                    {{ $shipment->customer->phone }}
                                </div>
                            @endif
                        @else
                            <div class="text-muted small">Tidak diisi</div>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <div class="meta-label mb-1">
                            Channel / Store
                        </div>
                        @if ($shipment->store)
                            <div class="fw-semibold">
                                {{ $shipment->store->name }}
                            </div>
                        @else
                            <div class="text-muted small">Tidak diisi</div>
                        @endif
                    </div>

                    <div class="col-12">
                        <div class="meta-label mb-1">
                            Catatan
                        </div>
                        @if ($shipment->notes)
                            <div class="small">
                                {!! nl2br(e($shipment->notes)) !!}
                            </div>
                        @else
                            <div class="text-muted small">
                                Tidak ada catatan.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Rekap + lines --}}
        <div class="card card-main">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="meta-label mb-1">
                            Daftar Barang Keluar
                        </div>
                        <div class="small text-muted">
                            Detail item yang keluar untuk shipment ini.
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <div class="summary-pill">
                            Jumlah baris:
                            <span class="fw-semibold ms-1">{{ $totalLines }}</span>
                        </div>
                        <div class="summary-pill">
                            Total qty:
                            <span class="fw-semibold ms-1">
                                {{ number_format($totalQty, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-lines mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 140px;">Kode</th>
                                <th>Nama Barang</th>
                                <th style="width: 120px;" class="text-end">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($shipment->lines as $line)
                                <tr>
                                    <td class="text-muted small">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td>
                                        <div class="item-code">
                                            {{ $line->item?->code ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            {{ $line->item?->name ?? '-' }}
                                        </div>
                                        @if ($line->remarks)
                                            <div class="small text-muted">
                                                Catatan: {{ $line->remarks }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold">
                                            {{ number_format($line->qty, 0, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Tidak ada baris item pada shipment ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Opsional: footer info --}}
                <div class="mt-3 d-flex justify-content-between align-items-center small text-muted">
                    <div>
                        Dibuat: {{ id_datetime($shipment->created_at) }}<br>
                        Terakhir diupdate: {{ id_datetime($shipment->updated_at) }}
                    </div>
                    <div>
                        Total qty keluar:
                        <span class="fw-semibold">
                            {{ number_format($totalQty, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
