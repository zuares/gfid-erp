@extends('layouts.app')

@section('title', 'Sales Invoices')

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

        .table thead th {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-semibold">
                Sales Invoices
            </h5>
            <a href="{{ route('sales.invoices.create') }}" class="btn btn-sm btn-primary">
                + Invoice Baru
            </a>
        </div>

        {{-- FILTER --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('sales.invoices.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Cari</label>
                        <input type="text" name="q" class="form-control form-control-sm"
                            placeholder="No. invoice / customer" value="{{ request('q') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Status</label>
                        @php
                            $statusOptions = [
                                '' => 'Semua',
                                'draft' => 'Draft',
                                'posted' => 'Posted',
                                'cancelled' => 'Cancelled',
                            ];
                        @endphp
                        <select name="status" class="form-select form-select-sm">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                            value="{{ request('date_from') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                            value="{{ request('date_to') }}">
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('sales.invoices.index') }}" class="btn btn-sm btn-outline-secondary">
                            Reset
                        </a>
                        <button type="submit" class="btn btn-sm btn-primary">
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- LIST --}}
        <div class="card card-main">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 12%;">Tanggal</th>
                                <th style="width: 16%;">No. Invoice</th>
                                <th>Customer</th>
                                <th style="width: 16%;">Warehouse</th>
                                <th style="width: 12%;">Status</th>
                                <th style="width: 16%;" class="text-end">Grand Total</th>
                                <th style="width: 10%;" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoices as $inv)
                                <tr>
                                    <td>
                                        <div class="small">
                                            {{ id_datetime($inv->date) }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $inv->code }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $inv->customer?->name ?? '-' }}
                                        </div>
                                        @if ($inv->customer?->phone)
                                            <div class="small text-muted">
                                                {{ $inv->customer->phone }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $inv->warehouse?->code ?? '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $inv->warehouse?->name }}
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = match ($inv->status) {
                                                'posted' => 'bg-success-subtle text-success',
                                                'draft' => 'bg-secondary-subtle text-muted',
                                                'cancelled' => 'bg-danger-subtle text-danger',
                                                default => 'bg-secondary-subtle text-muted',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ ucfirst($inv->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        Rp {{ number_format($inv->grand_total, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('sales.invoices.show', $inv) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center small text-muted py-4">
                                        Belum ada Sales Invoice.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($invoices instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="card-footer">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
