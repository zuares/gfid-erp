@extends('layouts.app')

@section('title', 'Sales Invoices')

@section('content')
    <div class="page-wrap">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="meta-label mb-1">
                    SALES INVOICES
                </div>
                <h1 class="h4 mb-0">Daftar Invoice Penjualan</h1>
                <div class="small text-muted">
                    Pantau invoice, status posting, dan total penjualan per hari.
                </div>
            </div>

            <div class="btn-group">
                {{-- Flow biasa: buat invoice manual --}}
                <a href="{{ route('sales.invoices.create') }}" class="btn btn-theme-main btn-sm">
                    + Invoice Baru
                </a>

                {{-- Flow chain: mulai dari pilih Shipment dulu --}}
                <a href="{{ route('sales.shipments.create') }}" class="btn btn-theme-outline btn-sm">
                    Dari Shipment &raquo;
                </a>
            </div>
        </div>

        {{-- ALERTS --}}
        @if (session('success'))
            <div class="alert alert-success py-2 px-3 small">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2 px-3 small">{{ session('error') }}</div>
        @endif
        @if (session('info'))
            <div class="alert alert-info py-2 px-3 small">{{ session('info') }}</div>
        @endif

        {{-- LIST TABLE --}}
        <div class="card card-main">
            <div class="card-body p-0">
                <div class="px-3 pt-3 pb-2 d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Menampilkan {{ $invoices->firstItem() ?? 0 }}–{{ $invoices->lastItem() ?? 0 }}
                        dari {{ $invoices->total() }} invoice.
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle table-lines">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Tanggal</th>
                                <th>Customer</th>
                                <th>Gudang</th>
                                <th>Status</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoices as $inv)
                                @php
                                    $status = $inv->status ?? 'draft';
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('sales.invoices.show', $inv) }}"
                                            class="fw-semibold text-decoration-none">
                                            {{ $inv->code }}
                                        </a>
                                    </td>
                                    <td class="small">{{ id_date($inv->date) }}</td>
                                    <td class="small">{{ $inv->customer?->name ?? '-' }}</td>
                                    <td class="small">
                                        {{ $inv->warehouse?->code ?? '-' }}
                                        <span class="text-muted">
                                            {{ $inv->warehouse?->name ? '— ' . $inv->warehouse->name : '' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($status === 'posted')
                                            <span class="badge-status badge-status-posted">Posted</span>
                                        @elseif ($status === 'unpriced')
                                            <span class="badge-status badge-status-unpriced">Unpriced</span>
                                        @else
                                            <span class="badge-status badge-status-draft">Draft</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">
                                        {{ number_format($inv->grand_total ?? 0, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada invoice.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($invoices->hasPages())
                    <div class="card-footer bg-transparent border-0">
                        {{ $invoices->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('head')
    <style>
        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: .75rem .75rem 3.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 26%,
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
                0 8px 24px rgba(15, 23, 42, 0.12),
                0 0 0 1px rgba(148, 163, 184, 0.12);
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

        .btn-theme-main,
        .btn-theme-outline {
            border-radius: 999px;
            font-size: .78rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding-inline: 1rem;
            padding-block: .35rem;
            border-width: 1px;
        }

        .btn-theme-main {
            background: #2563eb;
            border-color: #2563eb;
            color: #eff6ff;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.30);
        }

        .btn-theme-main:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }

        .btn-theme-outline {
            background: transparent;
            border-color: rgba(148, 163, 184, 0.7);
            color: #4b5563;
        }

        .btn-theme-outline:hover {
            background: rgba(226, 232, 240, 0.8);
        }

        body[data-theme="dark"] .btn-theme-outline {
            color: #e5e7eb;
            border-color: rgba(148, 163, 184, 0.6);
        }

        body[data-theme="dark"] .btn-theme-outline:hover {
            background: rgba(15, 23, 42, 0.9);
        }

        .badge-status {
            border-radius: 999px;
            padding: .18rem .7rem;
            font-size: .72rem;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .badge-status-draft {
            background: rgba(251, 191, 36, 0.10);
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.25);
        }

        .badge-status-unpriced {
            background: rgba(249, 115, 22, 0.12);
            color: #9a3412;
            border: 1px solid rgba(248, 150, 108, 0.6);
        }

        .badge-status-posted {
            background: rgba(34, 197, 94, 0.10);
            color: #166534;
            border: 1px solid rgba(34, 197, 94, 0.25);
        }

        body[data-theme="dark"] .badge-status-draft {
            background: rgba(251, 191, 36, 0.25);
            color: #fef9c3;
            border-color: rgba(245, 158, 11, 0.7);
        }

        body[data-theme="dark"] .badge-status-unpriced {
            background: rgba(248, 150, 108, 0.25);
            color: #ffedd5;
            border-color: rgba(248, 150, 108, 0.8);
        }

        body[data-theme="dark"] .badge-status-posted {
            background: rgba(34, 197, 94, 0.25);
            color: #bbf7d0;
            border-color: rgba(34, 197, 94, 0.8);
        }

        .table-lines thead th {
            border-bottom-width: 1px;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7280;
            background: rgba(248, 250, 252, 0.96);
        }

        body[data-theme="dark"] .table-lines thead th {
            background: rgba(15, 23, 42, 0.98);
            border-bottom-color: rgba(30, 64, 175, 0.75);
            color: #9ca3af;
        }

        .table-lines tbody td {
            vertical-align: middle;
            border-top-color: rgba(148, 163, 184, 0.18);
        }

        body[data-theme="dark"] .table-lines tbody td {
            border-top-color: rgba(51, 65, 85, 0.85);
        }
    </style>
@endpush
