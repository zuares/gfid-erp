@extends('layouts.app')

@section('title', 'Marketplace Orders')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
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
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Marketplace Orders</h5>
                <div class="text-muted small">
                    Daftar order masuk dari marketplace (Shopee/Tokped/TikTok).
                </div>
            </div>
            <div>
                <a href="{{ route('marketplace.orders.create') }}" class="btn btn-primary btn-sm">
                    + Input Manual
                </a>
            </div>
        </div>

        {{-- FILTERS --}}
        <div class="card card-main mb-3">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('marketplace.orders.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Store</label>
                        <select name="store_id" class="form-select form-select-sm">
                            <option value="">- Semua -</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" @selected(request('store_id') == $store->id)>
                                    [{{ $store->channel->code }}] {{ $store->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">- Semua -</option>
                            @foreach ($statuses as $key => $label)
                                <option value="{{ $key }}" @selected(request('status') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small mb-1">Dari Tanggal</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small mb-1">Sampai Tanggal</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                            class="form-control form-control-sm">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small mb-1">Cari</label>
                        <input type="text" name="q" value="{{ request('q') }}"
                            class="form-control form-control-sm" placeholder="No order / nama buyer">
                    </div>

                    <div class="col-12 col-md-1">
                        <button class="btn btn-outline-secondary btn-sm w-100">
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="card card-main">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr class="text-muted">
                                <th style="width: 14%">Order</th>
                                <th>Store</th>
                                <th style="width: 14%">Tanggal</th>
                                <th>Buyer</th>
                                <th style="width: 10%" class="text-center">Items</th>
                                <th style="width: 12%" class="text-end">Total</th>
                                <th style="width: 12%" class="text-center">Status</th>
                                <th style="width: 10%" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $order->external_order_id }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $order->external_invoice_no ?: 'No Invoice' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $order->store->name }}
                                        </div>
                                        <div class="small text-muted">
                                            [{{ $order->store->channel->code }}]
                                            {{ $order->store->short_code }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            {{ id_datetime($order->order_date) }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $order->buyer_name ?: '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $order->buyer_phone }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary">
                                            {{ $order->items_count }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        Rp {{ number_format($order->total_paid_customer, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $badgeClass = match ($order->status) {
                                                'completed' => 'bg-success-subtle text-success',
                                                'cancelled' => 'bg-danger-subtle text-danger',
                                                'shipped' => 'bg-info-subtle text-info',
                                                'packed' => 'bg-warning-subtle text-warning',
                                                default => 'bg-secondary-subtle text-muted',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('marketplace.orders.show', $order) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Belum ada order marketplace.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-3 py-2">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>

    </div>
@endsection
