@extends('layouts.app')

@section('title', 'GRN ' . $receipt->code)

@section('content')
    <div class="container py-3">

        {{-- HEADER ATAS --}}
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h1 class="h4 mb-0">
                        Goods Receipt
                    </h1>

                    {{-- STATUS BADGE --}}
                    @if ($receipt->status === 'posted')
                        <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">
                            Posted
                        </span>
                    @elseif ($receipt->status === 'draft')
                        <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle">
                            Draft
                        </span>
                    @else
                        <span class="badge rounded-pill bg-secondary-subtle text-secondary border border-secondary-subtle">
                            {{ ucfirst($receipt->status) }}
                        </span>
                    @endif
                </div>

                <div class="small text-muted">
                    Kode: <span class="fw-semibold">{{ $receipt->code }}</span>
                    @if ($receipt->date)
                        <span class="mx-2">•</span>
                        Tanggal: {{ $receipt->date->format('Y-m-d') }}
                    @endif
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
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
            <div class="alert alert-success py-2 mb-3">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2 mb-3">
                {{ session('error') }}
            </div>
        @endif

        {{-- INFO HEADER + RINGKASAN NILAI --}}
        <div class="row g-3 mb-3">
            {{-- INFORMASI DOKUMEN --}}
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            Informasi Dokumen
                        </h6>

                        <dl class="row mb-0 small">
                            <dt class="col-sm-4">Kode</dt>
                            <dd class="col-sm-8 mono">
                                {{ $receipt->code }}
                            </dd>

                            <dt class="col-sm-4">Tanggal</dt>
                            <dd class="col-sm-8">
                                {{ $receipt->date?->format('Y-m-d') ?? '-' }}
                            </dd>

                            <dt class="col-sm-4">Supplier</dt>
                            <dd class="col-sm-8">
                                @if ($receipt->supplier)
                                    <div class="fw-semibold">{{ $receipt->supplier->name }}</div>
                                    <div class="text-muted small">
                                        {{ $receipt->supplier->code }}
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4">Gudang</dt>
                            <dd class="col-sm-8">
                                @if ($receipt->warehouse)
                                    <div class="fw-semibold">{{ $receipt->warehouse->name }}</div>
                                    <div class="text-muted small">
                                        {{ $receipt->warehouse->code }}
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </dd>

                            @if ($receipt->purchase_order_id && $receipt->purchaseOrder)
                                <dt class="col-sm-4">Dari PO</dt>
                                <dd class="col-sm-8">
                                    <a href="{{ route('purchasing.purchase_orders.show', $receipt->purchase_order_id) }}"
                                        class="text-decoration-none">
                                        {{ $receipt->purchaseOrder->code }}
                                    </a>
                                </dd>
                            @endif

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

                        <dl class="row mb-0 small mono">
                            <dt class="col-sm-5">Subtotal</dt>
                            <dd class="col-sm-7 text-end">
                                {{ rupiah($receipt->subtotal ?? 0) }}
                            </dd>

                            <dt class="col-sm-5">Diskon</dt>
                            <dd class="col-sm-7 text-end">
                                {{ rupiah($receipt->discount ?? 0) }}
                            </dd>

                            <dt class="col-sm-5">
                                PPN ({{ decimal_id($receipt->tax_percent ?? 0, 2) }}%)
                            </dt>
                            <dd class="col-sm-7 text-end">
                                {{ rupiah($receipt->tax_amount ?? 0) }}
                            </dd>

                            <dt class="col-sm-5">Ongkir</dt>
                            <dd class="col-sm-7 text-end">
                                {{ rupiah($receipt->shipping_cost ?? 0) }}
                            </dd>

                            <hr class="my-2">

                            <dt class="col-sm-5 fw-semibold">Grand Total</dt>
                            <dd class="col-sm-7 text-end fw-semibold fs-6">
                                {{ rupiah($receipt->grand_total ?? 0) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- DETAIL BARANG --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Detail Barang Diterima</span>
                <span class="small text-muted">
                    Total baris: {{ $receipt->lines->count() }}
                </span>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 420px;">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 4%;" class="text-center">No</th>
                                <th style="width: 24%">Item</th>
                                <th style="width: 14%">LOT</th>
                                <th style="width: 9%" class="text-end">Qty In</th>
                                <th style="width: 9%" class="text-end">Qty Reject</th>
                                <th style="width: 12%" class="text-end">Harga/Unit</th>
                                <th style="width: 12%" class="text-end">Total</th>
                                <th style="width: 8%">Unit</th>
                                <th style="width: 8%">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @forelse ($receipt->lines as $line)
                                <tr>
                                    {{-- NO --}}
                                    <td class="text-center align-middle">
                                        {{ $loop->iteration }}
                                    </td>

                                    {{-- ITEM --}}
                                    <td>
                                        @if ($line->item)
                                            <div class="fw-semibold">{{ $line->item->name }}</div>
                                            <div class="text-muted small mono">
                                                {{ $line->item->code }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    {{-- LOT --}}
                                    <td>
                                        @if ($line->lot)
                                            <div class="badge bg-light border text-body mono">
                                                {{ $line->lot->code }}
                                            </div>
                                            <div class="text-muted small mt-1">
                                                Saldo LOT:
                                                {{ decimal_id($line->lot->qty_onhand, 2) }}
                                                @if (!is_null($line->lot->avg_cost))
                                                    • Avg:
                                                    {{ rupiah($line->lot->avg_cost) }}
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>

                                    {{-- QTY IN --}}
                                    <td class="text-end mono">
                                        {{ decimal_id($line->qty_received, 2) }}
                                    </td>

                                    {{-- QTY REJECT --}}
                                    <td class="text-end mono">
                                        {{ decimal_id($line->qty_reject, 2) }}
                                    </td>

                                    {{-- HARGA / UNIT --}}
                                    <td class="text-end mono">
                                        {{ rupiah($line->unit_price) }}
                                    </td>

                                    {{-- TOTAL LINE --}}
                                    <td class="text-end mono">
                                        {{ rupiah($line->line_total) }}
                                    </td>

                                    {{-- UNIT --}}
                                    <td class="mono">
                                        {{ $line->unit ?: $line->item->unit ?? '-' }}
                                    </td>

                                    {{-- NOTES --}}
                                    <td>
                                        {{ $line->notes ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-3">
                                        Tidak ada detail barang.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        @if ($receipt->lines->count())
                            <tfoot class="table-light">
                                <tr class="fw-semibold">
                                    <td colspan="3" class="text-end">
                                        Total
                                    </td>
                                    <td class="text-end mono">
                                        {{ decimal_id($receipt->lines->sum('qty_received'), 2) }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ decimal_id($receipt->lines->sum('qty_reject'), 2) }}
                                    </td>
                                    <td class="text-end">
                                        {{-- Kosong: cuma spacer --}}
                                    </td>
                                    <td class="text-end mono">
                                        {{ rupiah($receipt->lines->sum('line_total')) }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
