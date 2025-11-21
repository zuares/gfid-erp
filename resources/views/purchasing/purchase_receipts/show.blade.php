@extends('layouts.app')

@section('title', 'GRN ' . $receipt->code)

@section('content')
    <div class="container py-3">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">
                    Goods Receipt: {{ $receipt->code }}
                </h1>
                <div class="small text-muted">
                    Tanggal: {{ $receipt->date?->format('Y-m-d') }}
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('purchasing.purchase_receipts.index') }}" class="btn btn-outline-secondary btn-sm">
                    &larr; Kembali
                </a>

                @if ($receipt->status === 'draft')
                    <a href="{{ route('purchasing.purchase_receipts.edit', $receipt->id) }}" class="btn btn-primary btn-sm">
                        Edit
                    </a>
                    <form action="{{ route('purchasing.purchase_receipts.post', $receipt->id) }}" method="POST"
                        onsubmit="return confirm('Post GRN ini? Stok akan bertambah.');">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm">
                            Post GRN
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- ALERTS --}}
        @if (session('success'))
            <div class="alert alert-success py-2">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2">
                {{ session('error') }}
            </div>
        @endif

        {{-- INFO HEADER --}}
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="mb-2">
                            @if ($receipt->status === 'posted')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    Posted
                                </span>
                            @elseif ($receipt->status === 'draft')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                    Draft
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">
                                    {{ ucfirst($receipt->status) }}
                                </span>
                            @endif
                        </div>

                        <dl class="row mb-0 small">
                            <dt class="col-sm-4">Kode</dt>
                            <dd class="col-sm-8">{{ $receipt->code }}</dd>

                            <dt class="col-sm-4">Tanggal</dt>
                            <dd class="col-sm-8">{{ $receipt->date?->format('Y-m-d') }}</dd>

                            <dt class="col-sm-4">Supplier</dt>
                            <dd class="col-sm-8">
                                @if ($receipt->supplier)
                                    {{ $receipt->supplier->name }} ({{ $receipt->supplier->code }})
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4">Gudang</dt>
                            <dd class="col-sm-8">
                                @if ($receipt->warehouse)
                                    {{ $receipt->warehouse->name }} ({{ $receipt->warehouse->code }})
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4">Catatan</dt>
                            <dd class="col-sm-8">
                                {{ $receipt->notes ?: '-' }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- RINGKASAN NILAI --}}
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Ringkasan Nilai</h6>
                        <dl class="row mb-0 small">
                            <dt class="col-sm-5">Subtotal</dt>
                            <dd class="col-sm-7 text-end">
                                {{ rupiah($receipt->subtotal ?? 0) }}
                            </dd>

                            <dt class="col-sm-5">Diskon</dt>
                            <dd class="col-sm-7 text-end">
                                {{ rupiah($receipt->discount ?? 0) }}
                            </dd>

                            <dt class="col-sm-5">
                                PPN ({{ $receipt->tax_percent }}%)
                            </dt>
                            <dd class="col-sm-7 text-end">
                                {{ rupiah($receipt->tax_amount ?? 0) }}
                            </dd>

                            <dt class="col-sm-5">Ongkir</dt>
                            <dd class="col-sm-7 text-end">
                                {{ rupiah($receipt->shipping_cost ?? 0) }}
                            </dd>

                            <dt class="col-sm-5 fw-semibold">Grand Total</dt>
                            <dd class="col-sm-7 text-end fw-semibold">
                                {{ rupiah($receipt->grand_total ?? 0) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- DETAIL BARANG --}}
        <div class="card">
            <div class="card-header">
                <span class="fw-semibold">Detail Barang Diterima</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 420px;">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light sticky-top">
                            <tr>
                                {{-- NO URUT --}}
                                <th style="width: 5%;" class="text-center">No</th>
                                <th style="width: 26%">Item</th>
                                <th style="width: 10%" class="text-end">Qty Diterima</th>
                                <th style="width: 10%" class="text-end">Qty Reject</th>
                                <th style="width: 12%" class="text-end">Harga/Unit</th>
                                <th style="width: 12%" class="text-end">Total</th>
                                <th style="width: 10%">Unit</th>
                                <th style="width: 15%">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($receipt->lines as $line)
                                <tr>
                                    {{-- NO URUT --}}
                                    <td class="text-center">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td>
                                        @if ($line->item)
                                            <div class="fw-semibold">{{ $line->item->name }}</div>
                                            <div class="small text-muted">{{ $line->item->code }}</div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ decimal_id($line->qty_received, 3) }}
                                    </td>
                                    <td class="text-end">
                                        {{ decimal_id($line->qty_reject, 3) }}
                                    </td>
                                    <td class="text-end">
                                        {{ rupiah($line->unit_price) }}
                                    </td>
                                    <td class="text-end">
                                        {{ rupiah($line->line_total) }}
                                    </td>
                                    <td>
                                        {{ $line->unit ?: $line->item->unit ?? '-' }}
                                    </td>
                                    <td>
                                        {{ $line->notes ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-3">
                                        Tidak ada detail barang.
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
