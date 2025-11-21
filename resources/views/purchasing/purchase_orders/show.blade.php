{{-- resources/views/purchasing/purchase_orders/show.blade.php --}}
@extends('layouts.app')

@section('title', 'PO ' . $order->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
        }

        th.sticky {
            position: sticky;
            top: 0;
            background: var(--card);
            z-index: 1;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .tag {
            border-radius: 999px;
            padding: .15rem .65rem;
            font-size: .7rem;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, .12);
        }

        /* ===========================
                       MOBILE: ITEM → TAMPILKAN KODE
                       =========================== */
        @media (max-width: 768px) {
            .po-item-name {
                display: none;
                /* sembunyikan nama di mobile */
            }

            .po-item-code {
                display: block;
                font-weight: 600;
                font-size: .9rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="mb-0">Purchase Order</h2>
                <div class="text-muted mono">Kode: {{ $order->code }}</div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('purchasing.purchase_orders.index') }}" class="btn btn-outline-secondary btn-sm">
                    &larr; Kembali
                </a>

                {{-- tombol edit PO dsb kalau ada --}}

                {{-- 🔥 NEW: tombol buat GRN dari PO ini --}}
                <a href="{{ route('purchasing.purchase_receipts.create_from_order', $order->id) }}"
                    class="btn btn-primary btn-sm">
                    Buat GRN dari PO ini
                </a>
            </div>

        </div>

        {{-- INFO CARD --}}
        <div class="card mb-4">
            <div class="card-body row g-3">

                <div class="col-md-3">
                    <div class="text-muted small">Tanggal</div>
                    <div class="fw-semibold mono">
                        {{ $order->date }}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Supplier</div>
                    <div class="fw-semibold">
                        {{ optional($order->supplier)->name ?? '—' }}
                        @if ($order->supplier)
                            <div class="text-muted small mono">{{ $order->supplier->code }}</div>
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Status</div>
                    <span class="tag mono">
                        {{ strtoupper($order->status) }}
                    </span>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Dibuat oleh</div>
                    <div class="fw-semibold">
                        {{ optional($order->createdBy)->name ?? '—' }}
                    </div>
                </div>

                <div class="col-12">
                    <div class="text-muted small">Catatan</div>
                    <div>{{ $order->notes ?: '—' }}</div>
                </div>

            </div>
        </div>

        {{-- DETAIL BARANG --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold">
                Detail Barang
            </div>

            <div class="table-responsive">
                <table class="table table-sm mb-0 mono">
                    <thead>
                        <tr>
                            <th class="sticky" style="width: 5%">No</th>
                            <th class="sticky">Item</th>
                            <th class="text-end sticky" style="width: 12%">Qty</th>
                            <th class="text-end sticky" style="width: 18%">Harga</th>
                            <th class="text-end sticky" style="width: 15%">Diskon</th>
                            <th class="text-end sticky" style="width: 18%">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->lines as $line)
                            <tr>
                                <td class="text-center align-middle">
                                    {{ $loop->iteration }}
                                </td>

                                <td>
                                    {{-- Desktop: nama + kode kecil
                                         Mobile : hanya kode (po-item-name disembunyikan, po-item-code ditonjolkan) --}}
                                    <div class="po-item-name">
                                        {{ optional($line->item)->name ?? '—' }}
                                    </div>
                                    @if ($line->item)
                                        <div class="text-muted small po-item-code">
                                            {{ $line->item->code }}
                                        </div>
                                    @endif
                                </td>

                                <td class="text-end">
                                    {{ decimal_id($line->qty, 2) }}
                                </td>

                                <td class="text-end">
                                    {{ angka($line->unit_price) }}
                                </td>

                                <td class="text-end">
                                    {{ angka($line->discount) }}
                                </td>

                                <td class="text-end fw-semibold">
                                    {{ angka($line->line_total) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    Tidak ada item
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Subtotal</th>
                            <th class="text-end">
                                {{ rupiah($order->subtotal) }}
                            </th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-end">Diskon</th>
                            <th class="text-end">
                                {{ rupiah($order->discount) }}
                            </th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-end">
                                PPN
                                @if ($order->tax_percent)
                                    ({{ angka($order->tax_percent) }}%)
                                @endif
                            </th>
                            <th class="text-end">
                                {{ rupiah($order->tax_amount) }}
                            </th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-end">Ongkir</th>
                            <th class="text-end">
                                {{ rupiah($order->shipping_cost) }}
                            </th>
                        </tr>
                        <tr class="table-light">
                            <th colspan="5" class="text-end">Grand Total</th>
                            <th class="text-end fs-5 fw-bold">
                                {{ rupiah($order->grand_total) }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- FOOT ACTION --}}
        <div class="d-flex justify-content-end">
            <a href="{{ route('purchasing.purchase_orders.index') }}" class="btn btn-outline-secondary">
                ⬅️ Kembali ke daftar
            </a>
        </div>
    </div>
@endsection
