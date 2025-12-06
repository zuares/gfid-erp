@extends('layouts.app')

@section('title', 'Detail Invoice • ' . $invoice->code)

@section('content')
    @php
        $fmt = fn($n, $dec = 0) => number_format($n ?? 0, $dec, ',', '.');
        $shipmentCount = $invoice->shipments?->count() ?? 0;
        $totalMargin = $invoice->lines->sum('margin_total');

        $status = $invoice->status ?? 'draft';
    @endphp

    <div class="page-wrap">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="meta-label mb-1">
                    DETAIL INVOICE
                </div>
                <h1 class="h4 mb-1">
                    {{ $invoice->code }}
                </h1>

                <div class="d-flex flex-wrap gap-2 align-items-center small text-muted">
                    <span>Tanggal {{ id_date($invoice->date) }}</span>

                    {{-- STATUS BADGE --}}
                    @if ($status === 'posted')
                        <span class="badge-status badge-status-posted">Posted</span>
                    @elseif ($status === 'unpriced')
                        <span class="badge-status badge-status-unpriced">Unpriced</span>
                    @else
                        <span class="badge-status badge-status-draft">Draft</span>
                    @endif

                    {{-- Badge shipment --}}
                    @if ($shipmentCount > 0)
                        <span class="badge-status badge-status-shipments">
                            {{ $shipmentCount }} Shipment
                        </span>
                    @else
                        <span class="badge-status badge-status-no-shipments">
                            Belum ada Shipment
                        </span>
                    @endif
                </div>
            </div>

            <div class="text-end small text-muted">
                <div class="mb-2 d-flex flex-wrap gap-2 justify-content-end">
                    {{-- Flow: buat Shipment dari invoice (kalau sudah posted) --}}
                    @if ($status === 'posted')
                        <a href="{{ route('sales.shipments.create', $invoice) }}" class="btn btn-theme-outline btn-sm">
                            Buat Shipment
                        </a>
                    @endif

                    {{-- Post invoice (kalau belum posted) --}}
                    @if ($status !== 'posted')
                        <form action="{{ route('sales.invoices.post', $invoice) }}" method="POST"
                            onsubmit="return confirm('Post invoice ini? Stok akan keluar saat shipment diposting dari WH-RTS.');">
                            @csrf
                            <button type="submit" class="btn btn-theme-main btn-sm">
                                Post Invoice
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('sales.invoices.index') }}" class="btn btn-theme-outline btn-sm">
                        &larr; Daftar Invoice
                    </a>
                </div>

                <div>
                    Dibuat: {{ id_datetime($invoice->created_at) }}<br>
                    Update: {{ id_datetime($invoice->updated_at) }}
                </div>
            </div>
        </div>

        {{-- FLASH --}}
        @if (session('success'))
            <div class="alert alert-success py-2 px-3 small">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2 px-3 small">{{ session('error') }}</div>
        @endif
        @if (session('info'))
            <div class="alert alert-info py-2 px-3 small">{{ session('info') }}</div>
        @endif

        {{-- INFO UTAMA --}}
        <div class="card card-main mb-3">
            <div class="card-body small">
                <div class="meta-label mb-2">
                    Info Utama
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted mb-1">Customer</div>
                        <div class="fw-semibold">{{ $invoice->customer?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted mb-1">Gudang</div>
                        <div class="fw-semibold">
                            {{ $invoice->warehouse?->code ?? '-' }}
                            <span class="text-muted">
                                — {{ $invoice->warehouse?->name ?? '' }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted mb-1">Store / Channel</div>
                        <div class="fw-semibold">
                            @if ($invoice->store)
                                {{ $invoice->store->code }} &mdash; {{ $invoice->store->name }}
                            @else
                                <span class="text-muted">Tidak diisi</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-12 mt-2">
                        <div class="text-muted mb-1">Catatan</div>
                        @if ($invoice->remarks)
                            <div>{!! nl2br(e($invoice->remarks)) !!}</div>
                        @else
                            <div class="text-muted">Tidak ada catatan.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ITEMS + MARGIN --}}
        <div class="card card-main mb-3">
            <div class="card-body p-0">
                <div class="px-3 pt-3 pb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="meta-label mb-1">Items & Perhitungan Margin</div>
                        <div class="small text-muted">
                            HPP/unit diambil dari snapshot HPP final (ProductionCostPeriod aktif).
                        </div>
                    </div>
                    <div class="summary-pill">
                        Total margin:
                        <span class="fw-semibold ms-1">{{ $fmt($totalMargin) }}</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle text-nowrap table-lines">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Disc</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-end">HPP/unit</th>
                                <th class="text-end">Margin/unit</th>
                                <th class="text-end">Margin total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoice->lines as $line)
                                <tr>
                                    <td>
                                        <div class="fw-semibold small">
                                            {{ $line->item?->code ?? '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $line->item?->name ?? '' }}
                                        </div>
                                    </td>
                                    <td class="text-end">{{ $fmt($line->qty) }}</td>
                                    <td class="text-end">{{ $fmt($line->unit_price) }}</td>
                                    <td class="text-end">{{ $fmt($line->line_discount) }}</td>
                                    <td class="text-end">{{ $fmt($line->line_total) }}</td>
                                    <td class="text-end">{{ $fmt($line->hpp_unit_snapshot) }}</td>
                                    <td class="text-end">{{ $fmt($line->margin_unit) }}</td>
                                    <td class="text-end fw-semibold">{{ $fmt($line->margin_total) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-3">
                                        Tidak ada item.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- SUMMARY FOOTER --}}
                <div class="card-footer bg-transparent border-0 pt-0">
                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="text-muted">Subtotal</span>
                                <span>{{ $fmt($invoice->subtotal) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="text-muted">Diskon</span>
                                <span>{{ $fmt($invoice->discount_total) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="text-muted">PPN ({{ $invoice->tax_percent }}%)</span>
                                <span>{{ $fmt($invoice->tax_amount) }}</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between mb-1">
                                <strong>Grand Total</strong>
                                <strong>{{ $fmt($invoice->grand_total) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mt-2 small text-muted">
                                <span>Total Margin</span>
                                <span class="fw-semibold">{{ $fmt($totalMargin) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SHIPMENTS TERKAIT --}}
        <div class="card card-main">
            <div class="card-body p-0">
                <div class="px-3 pt-3 pb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="meta-label mb-1">Shipment Terkait Invoice Ini</div>
                        <div class="small text-muted">
                            Invoice → bisa punya beberapa shipment (split pengiriman atau kirim ulang).
                        </div>
                    </div>

                    <div class="summary-pill">
                        Total shipment:
                        <span class="fw-semibold ms-1">{{ $shipmentCount }}</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle text-nowrap table-lines">
                        <thead>
                            <tr>
                                <th>Kode Shipment</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Metode Kirim</th>
                                <th>No. Resi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoice->shipments as $shp)
                                <tr>
                                    <td>
                                        <a href="{{ route('sales.shipments.show', $shp) }}" class="fw-semibold small">
                                            {{ $shp->code ?? ($shp->shipment_no ?? 'SHP#' . $shp->id) }}
                                        </a>
                                    </td>
                                    <td class="small">{{ id_date($shp->date) }}</td>
                                    <td>
                                        <span class="badge-status badge-status-sm badge-status-shipments">
                                            {{ ucfirst($shp->status) }}
                                        </span>
                                    </td>
                                    <td class="small">{{ $shp->shipping_method ?? '-' }}</td>
                                    <td class="small">{{ $shp->tracking_no ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        Belum ada shipment yang terkait invoice ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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

        .summary-pill {
            border-radius: 999px;
            padding: .2rem .75rem;
            font-size: .8rem;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(248, 250, 252, 0.96);
        }

        body[data-theme="dark"] .summary-pill {
            background: rgba(15, 23, 42, 0.98);
            border-color: rgba(30, 64, 175, 0.7);
            color: #e5e7eb;
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

        .badge-status-shipments {
            background: rgba(59, 130, 246, 0.12);
            color: #1d4ed8;
            border: 1px solid rgba(59, 130, 246, 0.4);
        }

        .badge-status-no-shipments {
            background: rgba(148, 163, 184, 0.10);
            color: #4b5563;
            border: 1px solid rgba(148, 163, 184, 0.4);
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

        body[data-theme="dark"] .badge-status-shipments {
            background: rgba(37, 99, 235, 0.30);
            color: #bfdbfe;
            border-color: rgba(37, 99, 235, 0.9);
        }

        body[data-theme="dark"] .badge-status-no-shipments {
            background: rgba(15, 23, 42, 0.85);
            color: #9ca3af;
            border-color: rgba(148, 163, 184, 0.7);
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
