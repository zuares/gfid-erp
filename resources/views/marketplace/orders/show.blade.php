@extends('layouts.app')

@section('title', 'Marketplace Order • ' . $order->external_order_id)

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
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- Back --}}
        <div class="mb-3">
            <a href="{{ route('marketplace.orders.index') }}" class="btn btn-sm btn-outline-secondary">
                ← Kembali ke List
            </a>
            <a href="{{ route('shipments.from_marketplace_order', $order) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-box-arrow-up-right me-1"></i>
                Buat Shipment dari Order
            </a>
        </div>

        {{-- HEADER --}}
        <div class="card card-main mb-3">
            <div class="card-header fw-semibold">
                Marketplace Order #{{ $order->external_order_id }}
            </div>
            <div class="card-body">

                <div class="row g-3">

                    {{-- Store --}}
                    <div class="col-md-4">
                        <label class="small text-muted d-block mb-1">Store</label>
                        <div class="fw-semibold">
                            [{{ $order->store->channel->code }}] {{ $order->store->name }}
                        </div>
                    </div>

                    {{-- Tanggal --}}
                    <div class="col-md-4">
                        <label class="small text-muted d-block mb-1">Tanggal Order</label>
                        <div class="fw-semibold">
                            {{ id_datetime($order->order_date) }}
                        </div>
                    </div>

                    {{-- Invoice No --}}
                    <div class="col-md-4">
                        <label class="small text-muted d-block mb-1">No. Invoice Marketplace</label>
                        <div class="fw-semibold">
                            {{ $order->external_invoice_no ?: '-' }}
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="col-md-3">
                        <label class="small text-muted d-block mb-1">Status</label>
                        <span class="badge bg-primary-subtle text-primary">
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>

                </div>
            </div>
        </div>

        {{-- BUYER / CUSTOMER --}}
        <div class="card card-main mb-3">
            <div class="card-header fw-semibold">
                Buyer / Customer
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">

                        @if ($order->customer)
                            {{-- Customer dari master --}}
                            <div class="fw-semibold mb-1">
                                {{ $order->customer->name }}
                            </div>

                            <div class="small text-muted">
                                {{ $order->customer->phone }}<br>
                                {{ $order->customer->email }}<br>
                                {{ $order->customer->address }}
                            </div>

                            {{-- Tombol --}}
                            <div class="mt-2 d-flex flex-wrap gap-2">

                                <a href="{{ route('customers.edit', $order->customer) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    Lihat Profil Customer
                                </a>

                                <a href="{{ route('marketplace.orders.index', ['customer_id' => $order->customer_id]) }}"
                                    class="btn btn-sm btn-outline-secondary">
                                    Lihat Semua Order Customer Ini
                                </a>

                            </div>
                        @else
                            {{-- fallback --}}
                            <div class="fw-semibold mb-1">
                                {{ $order->buyer_name ?: '-' }}
                            </div>
                            <div class="small text-muted">
                                {{ $order->buyer_phone }}<br>
                                {{ $order->shipping_address }}
                            </div>
                        @endif

                    </div>

                    <div class="col-md-6">
                        <label class="small text-muted mb-1">Alamat Pengiriman</label>
                        <div class="fw-semibold">
                            {{ $order->shipping_address ?: '-' }}
                        </div>
                        @if ($order->remarks)
                            <div class="small text-muted mt-2">
                                Catatan: {{ $order->remarks }}
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>

        {{-- ITEMS --}}
        <div class="card card-main mb-3">
            <div class="card-header fw-semibold">
                Item Pemesanan
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="text-muted small">
                            <tr>
                                <th style="width: 10%">SKU</th>
                                <th>Nama Item</th>
                                <th class="text-center" style="width: 12%">Qty</th>
                                <th class="text-end" style="width: 15%">Harga</th>
                                <th class="text-end" style="width: 15%">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $line)
                                <tr>
                                    <td class="fw-semibold">{{ $line->external_sku ?: '-' }}</td>

                                    <td>
                                        <div class="fw-semibold">
                                            {{ $line->item_name_snapshot ?: '-' }}
                                        </div>
                                        @if ($line->item_code_snapshot)
                                            <div class="small text-muted">
                                                Kode: {{ $line->item_code_snapshot }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        {{ (int) $line->qty }}
                                    </td>

                                    <td class="text-end">
                                        {{ number_format($line->price_original, 0, ',', '.') }}
                                    </td>

                                    <td class="text-end fw-semibold">
                                        {{ number_format($line->line_gross_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        {{-- TOTAL --}}
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Subtotal Items</th>
                                <th class="text-end fw-semibold">
                                    {{ number_format($order->subtotal_items, 0, ',', '.') }}
                                </th>
                            </tr>
                        </tfoot>

                    </table>
                </div>
            </div>
        </div>

        {{-- RIWAYAT ORDER CUSTOMER INI --}}
        @if ($order->customer && isset($customerOrders) && $customerOrders->isNotEmpty())
            <div class="card card-main mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">
                        Riwayat Order Customer Ini
                    </span>
                    <span class="small text-muted">
                        Menampilkan {{ $customerOrders->count() }} order terakhir (di luar order ini)
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="text-muted small">
                                <tr>
                                    <th style="width: 14%">No. Order</th>
                                    <th>Store</th>
                                    <th style="width: 16%">Tanggal</th>
                                    <th style="width: 18%">Total</th>
                                    <th style="width: 14%">Status</th>
                                    <th style="width: 10%" class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customerOrders as $o)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">
                                                {{ $o->external_order_id }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ $o->external_invoice_no ?: '-' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">
                                                {{ $o->store->name }}
                                            </div>
                                            <div class="small text-muted">
                                                [{{ $o->store->channel->code }}]
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small">
                                                {{ id_datetime($o->order_date) }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-end fw-semibold">
                                                Rp {{ number_format($o->total_paid_customer, 0, ',', '.') }}
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $badgeClass = match ($o->status) {
                                                    'completed' => 'bg-success-subtle text-success',
                                                    'cancelled' => 'bg-danger-subtle text-danger',
                                                    'shipped' => 'bg-info-subtle text-info',
                                                    'packed' => 'bg-warning-subtle text-warning',
                                                    default => 'bg-secondary-subtle text-muted',
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">
                                                {{ ucfirst($o->status) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('marketplace.orders.show', $o) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection
