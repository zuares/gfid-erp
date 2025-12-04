@extends('layouts.app')

@section('title', 'Sales Invoice • ' . $invoice->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1200px;
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
            border: 1px solid rgba(148, 163, 184, 0.30);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.03);
        }

        .table-items thead th {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- Back --}}
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <a href="{{ route('sales.invoices.index') }}" class="btn btn-sm btn-outline-secondary">
                ← Kembali ke List
            </a>

            <span class="small text-muted">
                Dibuat oleh: {{ $invoice->creator?->name ?? '-' }}
            </span>
        </div>

        {{-- HEADER --}}
        <div class="card card-main mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="fw-semibold">
                    Sales Invoice #{{ $invoice->code }}
                </div>
                <div>
                    @php
                        $badgeClass = match ($invoice->status) {
                            'posted' => 'bg-success-subtle text-success',
                            'draft' => 'bg-secondary-subtle text-muted',
                            'cancelled' => 'bg-danger-subtle text-danger',
                            default => 'bg-secondary-subtle text-muted',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="small text-muted d-block mb-1">Tanggal Invoice</label>
                        <div class="fw-semibold">
                            {{ id_datetime($invoice->date) }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="small text-muted d-block mb-1">Customer</label>
                        @if ($invoice->customer)
                            <div class="fw-semibold">
                                {{ $invoice->customer->name }}
                            </div>
                            <div class="small text-muted">
                                {{ $invoice->customer->phone }}<br>
                                {{ $invoice->customer->email }}
                            </div>
                        @else
                            <div class="text-muted">-</div>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <label class="small text-muted d-block mb-1">Warehouse</label>
                        <div class="fw-semibold">
                            {{ $invoice->warehouse?->code ?? '-' }}
                        </div>
                        <div class="small text-muted">
                            {{ $invoice->warehouse?->name }}
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="small text-muted d-block mb-1">Catatan</label>
                        <div>
                            {{ $invoice->remarks ?: '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ITEMS + MARGIN --}}
        @php
            $totalRevenue = 0;
            $totalHpp = 0;
            foreach ($invoice->lines as $line) {
                $totalRevenue += $line->line_total;
                $totalHpp += $line->hpp_total_snapshot;
            }
            $totalMargin = $totalRevenue - $totalHpp;
        @endphp

        <div class="card card-main mb-3">
            <div class="card-header fw-semibold">
                Items &amp; Margin
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 table-items">
                        <thead>
                            <tr>
                                <th style="width: 26%;">Item</th>
                                <th style="width: 8%;" class="text-center">Qty</th>
                                <th style="width: 12%;" class="text-end">Harga Jual</th>
                                <th style="width: 10%;" class="text-end">Diskon</th>
                                <th style="width: 12%;" class="text-end">Subtotal</th>
                                <th style="width: 10%;" class="text-end">HPP/Unit</th>
                                <th style="width: 10%;" class="text-end">Margin/Unit</th>
                                <th style="width: 12%;" class="text-end">Margin Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->lines as $line)
                                @php
                                    $marginUnit = $line->unit_price - $line->hpp_unit_snapshot;
                                    $marginTotal = $line->line_total - $line->hpp_total_snapshot;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $line->item_code_snapshot ?? $line->item?->code }} —
                                            {{ $line->item_name_snapshot ?? $line->item?->name }}
                                        </div>
                                        <div class="small text-muted">
                                            ID: {{ $line->item_id }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        {{ (float) $line->qty }}
                                    </td>
                                    <td class="text-end">
                                        Rp {{ number_format($line->unit_price, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        @if ($line->line_discount > 0)
                                            Rp {{ number_format($line->line_discount, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">
                                        Rp {{ number_format($line->line_total, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        Rp {{ number_format($line->hpp_unit_snapshot, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end {{ $marginUnit >= 0 ? 'text-success' : 'text-danger' }}">
                                        Rp {{ number_format($marginUnit, 0, ',', '.') }}
                                    </td>
                                    <td
                                        class="text-end fw-semibold {{ $marginTotal >= 0 ? 'text-success' : 'text-danger' }}">
                                        Rp {{ number_format($marginTotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <th colspan="4" class="text-end">TOTAL</th>
                                <th class="text-end fw-semibold">
                                    Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                                </th>
                                <th class="text-end fw-semibold">
                                    Rp {{ number_format($totalHpp, 0, ',', '.') }}
                                </th>
                                <th class="text-end">
                                    {{-- kosong / label --}}
                                </th>
                                <th class="text-end fw-bold {{ $totalMargin >= 0 ? 'text-success' : 'text-danger' }}">
                                    Rp {{ number_format($totalMargin, 0, ',', '.') }}
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- SUMMARY FOOTER --}}
            <div class="card-footer d-flex flex-wrap justify-content-end gap-4">
                <div class="text-end">
                    <div class="small text-muted">Subtotal Penjualan</div>
                    <div class="fw-bold fs-6">
                        Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}
                    </div>
                </div>
                <div class="text-end">
                    <div class="small text-muted">HPP Perkiraan</div>
                    <div class="fw-bold fs-6">
                        Rp {{ number_format($totalHpp, 0, ',', '.') }}
                    </div>
                </div>
                <div class="text-end">
                    <div class="small text-muted">Margin Kotor</div>
                    <div class="fw-bold fs-5 {{ $totalMargin >= 0 ? 'text-success' : 'text-danger' }}">
                        Rp {{ number_format($totalMargin, 0, ',', '.') }}
                    </div>
                </div>
                <div class="text-end">
                    <div class="small text-muted">Grand Total Invoice</div>
                    <div class="fw-bold fs-5">
                        Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
