{{-- resources/views/purchasing/purchase_receipts/show.blade.php --}}
@extends('layouts.app')

@section('title', 'GRN ' . $receipt->code)

@push('head')
    {{-- ========== PAGE WRAP + GLOBAL (mirip index) ========== --}}
    <style>
        .grn-show-page {
            min-height: 100vh;
        }

        .grn-show-page .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: 1rem 1rem 4rem;
        }

        body[data-theme="light"] .grn-show-page .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.12) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 60%);
        }

        .grn-show-page .card-main {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.10),
                0 0 0 1px rgba(148, 163, 184, 0.08);
        }

        .grn-show-page .card-soft {
            background: color-mix(in srgb, var(--card) 94%, var(--bg) 6%);
            border-radius: 16px;
            border: 1px solid var(--line);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        @media (max-width: 767.98px) {

            html,
            body {
                max-width: 100%;
                overflow-x: hidden;
            }

            .grn-show-page {
                overflow-x: hidden;
            }

            .grn-show-page .page-wrap {
                padding-inline: .85rem;
            }
        }
    </style>

    {{-- ========== HEADER STYLES (follow index) ========== --}}
    <style>
        .grn-show-page .show-header-title {
            font-size: 1.35rem;
            font-weight: 600;
        }

        .grn-show-page .show-header-subtitle {
            font-size: .8rem;
            color: var(--muted);
        }

        .grn-show-page .show-header-pill {
            font-size: .75rem;
            border-radius: 999px;
            padding: .16rem .7rem;
            border: 1px solid rgba(148, 163, 184, .55);
            background: color-mix(in srgb, var(--card) 80%, var(--bg) 20%);
        }

        .grn-show-page .show-header-status {
            font-size: .78rem;
            border-radius: 999px;
            padding: .14rem .7rem;
        }

        @media (max-width: 767.98px) {
            .grn-show-page .show-header {
                flex-direction: column;
                align-items: flex-start;
                gap: .4rem;
            }

            .grn-show-page .show-header-title {
                font-size: 1.15rem;
            }

            .grn-show-page .show-header-actions {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: .35rem;
            }

            .grn-show-page .show-header-actions .btn {
                flex: 1 1 auto;
            }
        }
    </style>

    {{-- ========== SECTION / CARD STYLES ========== --}}
    <style>
        .grn-show-page .section-title {
            font-size: .86rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .grn-show-page dl.small dt {
            color: var(--muted);
        }

        .grn-show-page .card-soft .card-body {
            padding: 1rem 1.2rem 1.15rem;
        }

        .grn-show-page .card-main .card-header {
            padding: .6rem 1.2rem;
            background: transparent;
            border-bottom-color: var(--line);
        }

        .grn-show-page .card-main .card-body {
            padding: .5rem .6rem .7rem;
        }

        /* RINGKASAN NILAI */
        .grn-show-page .summary-hr {
            border-top-color: var(--line);
            opacity: 1;
        }

        .grn-show-page .summary-grand {
            font-size: .95rem;
        }
    </style>

    {{-- ========== DETAIL TABLE STYLES (no overlap, mobile-friendly, thead minimal) ========== --}}
    <style>
        @media (min-width: 992px) {
            .grn-detail-wrapper {
                max-height: 60vh;
                overflow-y: auto;
                overflow-x: hidden;
            }

            .grn-detail-wrapper::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }

            .grn-detail-wrapper::-webkit-scrollbar-thumb {
                background: color-mix(in srgb, var(--muted) 60%, transparent);
                border-radius: 999px;
            }

            .grn-detail-wrapper::-webkit-scrollbar-track {
                background: transparent;
            }
        }

        @media (max-width: 991.98px) {

            /* di mobile/tablet: scroll horizontal kalau kepanjangan, supaya tidak overlap */
            .grn-detail-wrapper {
                max-height: none;
                overflow-x: auto;
                overflow-y: auto;
            }
        }

        .grn-show-page .table thead th {
            background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
            border-bottom-color: var(--line);
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding-top: .55rem;
            padding-bottom: .55rem;
            white-space: nowrap;
        }

        .grn-show-page tbody.small td {
            border-bottom-color: var(--line);
            vertical-align: middle;
            padding-top: .5rem;
            padding-bottom: .5rem;
            font-size: .8rem;
        }

        .grn-show-page tfoot.table-light td {
            border-top-color: var(--line);
            padding-top: .55rem;
            padding-bottom: .55rem;
        }

        .grn-show-page .lot-badge {
            border-radius: 999px;
            font-size: .75rem;
        }

        /* Item cell: pisahkan nama & kode supaya bisa diatur di mobile */
        .grn-show-page .cell-item-name {
            font-size: .84rem;
        }

        .grn-show-page .cell-item-code {
            font-size: .78rem;
        }

        .grn-show-page .lot-extra {
            font-size: .75rem;
        }

        /* THEAD label: full / abbr toggle */
        .grn-show-page .th-full {
            display: inline;
        }

        .grn-show-page .th-abbr {
            display: none;
        }

        /* VALUE TOGGLES (desktop vs mobile) */
        .grn-show-page .val-full {
            display: inline;
        }

        .grn-show-page .val-mobile {
            display: none;
        }

        @media (max-width: 767.98px) {
            .grn-show-page .table thead th {
                font-size: .7rem;
                padding-top: .4rem;
                padding-bottom: .4rem;
            }

            .grn-show-page tbody.small td {
                font-size: .76rem;
                padding-top: .4rem;
                padding-bottom: .4rem;
            }

            /* MOBILE RULES:
                   - hanya tampilkan kode item
                   - nama item disembunyikan
                   - kode dibuat lebih "utama" */
            .grn-show-page .cell-item-name {
                display: none;
            }

            .grn-show-page .cell-item-code {
                font-size: .8rem;
                color: var(--text);
                font-weight: 600;
            }

            /* info saldo LOT disembunyikan biar nggak penuh */
            .grn-show-page .lot-extra {
                display: none;
            }

            /* THEAD diminimalkan: pakai label singkat */
            .grn-show-page .th-full {
                display: none;
            }

            .grn-show-page .th-abbr {
                display: inline;
            }

            /* MOBILE VALUES:
                   - gunakan versi tanpa desimal & tanpa "Rp" di detail barang */
            .grn-show-page .val-full {
                display: none;
            }

            .grn-show-page .val-mobile {
                display: inline;
            }
        }
    </style>
@endpush

@section('content')
    <div class="grn-show-page">
        <div class="page-wrap">
            {{-- HEADER (GFID style, mirip index) --}}
            <div class="show-header d-flex justify-content-between align-items-start mb-3 gap-2">
                <div class="d-flex flex-column gap-1">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h1 class="mb-0 show-header-title">
                            Goods Receipt
                        </h1>

                        {{-- STATUS BADGE --}}
                        @if ($receipt->status === 'posted')
                            <span class="show-header-status bg-success-subtle text-success border border-success-subtle">
                                Posted
                            </span>
                        @elseif ($receipt->status === 'draft')
                            <span class="show-header-status bg-warning-subtle text-warning border border-warning-subtle">
                                Draft
                            </span>
                        @else
                            <span
                                class="show-header-status bg-secondary-subtle text-secondary border border-secondary-subtle">
                                {{ ucfirst($receipt->status) }}
                            </span>
                        @endif

                        <span class="show-header-pill d-none d-sm-inline">
                            {{ $receipt->lines->count() }} baris barang diterima
                        </span>
                    </div>

                    <div class="show-header-subtitle">
                        <span>
                            Kode:
                            <span class="fw-semibold mono">{{ $receipt->code }}</span>
                        </span>
                        @if ($receipt->date)
                            <span class="mx-2">•</span>
                            <span>Tanggal: {{ $receipt->date->format('Y-m-d') }}</span>
                        @endif
                        @if ($receipt->updated_at)
                            <span class="mx-2 d-none d-sm-inline">•</span>
                            <span class="d-none d-sm-inline">
                                Update: {{ $receipt->updated_at->format('Y-m-d H:i') }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="show-header-actions d-flex align-items-center gap-2">
                    <a href="{{ route('purchasing.purchase_receipts.index') }}" class="btn btn-outline-secondary btn-sm">
                        &larr; Kembali
                    </a>

                    @if ($receipt->status === 'draft')
                        <a href="{{ route('purchasing.purchase_receipts.edit', $receipt->id) }}"
                            class="btn btn-primary btn-sm">
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

            {{--
                URUTAN MOBILE:
                1. Informasi Dokumen
                2. Detail Barang Diterima
                3. Ringkasan Nilai

                DESKTOP:
                - Informasi + Ringkasan di atas (2 kolom)
                - Detail full width di bawah
            --}}
            <div class="row g-3 mb-3">
                {{-- 1) INFORMASI DOKUMEN --}}
                <div class="col-12 col-lg-6 order-1 order-lg-1">
                    <div class="card-soft h-100">
                        <div class="card-body">
                            <h6 class="section-title mb-3">
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

                {{-- 2) DETAIL BARANG DITERIMA --}}
                <div class="col-12 order-2 order-lg-3">
                    <div class="card-main">
                        <div class="card-header d-flex justify-content-between align-items-center gap-2">
                            <span class="fw-semibold small text-uppercase">
                                Detail Barang Diterima
                            </span>
                            <span class="small text-muted">
                                Total baris: {{ $receipt->lines->count() }}
                            </span>
                        </div>

                        <div class="card-body">
                            <div class="grn-detail-wrapper">
                                <table class="table table-sm mb-0 align-middle">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="width: 4%;" class="text-center">
                                                <span class="th-full">No</span>
                                                <span class="th-abbr">#</span>
                                            </th>
                                            <th style="width: 22%">
                                                <span class="th-full">Item</span>
                                                <span class="th-abbr">Item</span>
                                            </th>
                                            <th style="width: 16%">
                                                <span class="th-full">LOT</span>
                                                <span class="th-abbr">LOT</span>
                                            </th>
                                            <th style="width: 9%" class="text-end">
                                                <span class="th-full">Qty In</span>
                                                <span class="th-abbr">Qty</span>
                                            </th>
                                            <th style="width: 9%" class="text-end">
                                                <span class="th-full">Qty Reject</span>
                                                <span class="th-abbr">Rej</span>
                                            </th>
                                            <th style="width: 12%" class="text-end">
                                                <span class="th-full">Harga/Unit</span>
                                                <span class="th-abbr">Harga</span>
                                            </th>
                                            <th style="width: 12%" class="text-end">
                                                <span class="th-full">Total</span>
                                                <span class="th-abbr">Total</span>
                                            </th>
                                            <th style="width: 8%">
                                                <span class="th-full">Unit</span>
                                                <span class="th-abbr">U</span>
                                            </th>
                                            <th style="width: 8%">
                                                <span class="th-full">Catatan</span>
                                                <span class="th-abbr">Cat</span>
                                            </th>
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
                                                        <div class="cell-item-name fw-semibold">
                                                            {{ $line->item->name }}
                                                        </div>
                                                        <div class="cell-item-code mono">
                                                            {{ $line->item->code }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>

                                                {{-- LOT --}}
                                                <td>
                                                    @if ($line->lot)
                                                        <div class="badge bg-light border text-body mono lot-badge">
                                                            {{ $line->lot->code }}
                                                        </div>
                                                        <div class="lot-extra text-muted mt-1">
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
                                                    <span class="val-full">
                                                        {{ decimal_id($line->qty_received, 2) }}
                                                    </span>
                                                    <span class="val-mobile">
                                                        {{ decimal_id($line->qty_received, 0) }}
                                                    </span>
                                                </td>

                                                {{-- QTY REJECT --}}
                                                <td class="text-end mono">
                                                    <span class="val-full">
                                                        {{ decimal_id($line->qty_reject, 2) }}
                                                    </span>
                                                    <span class="val-mobile">
                                                        {{ decimal_id($line->qty_reject, 0) }}
                                                    </span>
                                                </td>

                                                {{-- HARGA / UNIT --}}
                                                <td class="text-end mono">
                                                    <span class="val-full">
                                                        {{ rupiah($line->unit_price) }}
                                                    </span>
                                                    <span class="val-mobile">
                                                        {{ number_format($line->unit_price ?? 0, 0, ',', '.') }}
                                                    </span>
                                                </td>

                                                {{-- TOTAL LINE --}}
                                                <td class="text-end mono">
                                                    <span class="val-full">
                                                        {{ rupiah($line->line_total) }}
                                                    </span>
                                                    <span class="val-mobile">
                                                        {{ number_format($line->line_total ?? 0, 0, ',', '.') }}
                                                    </span>
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
                                                    <span class="val-full">
                                                        {{ decimal_id($receipt->lines->sum('qty_received'), 2) }}
                                                    </span>
                                                    <span class="val-mobile">
                                                        {{ decimal_id($receipt->lines->sum('qty_received'), 0) }}
                                                    </span>
                                                </td>
                                                <td class="text-end mono">
                                                    <span class="val-full">
                                                        {{ decimal_id($receipt->lines->sum('qty_reject'), 2) }}
                                                    </span>
                                                    <span class="val-mobile">
                                                        {{ decimal_id($receipt->lines->sum('qty_reject'), 0) }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    {{-- spacer --}}
                                                </td>
                                                <td class="text-end mono">
                                                    <span class="val-full">
                                                        {{ rupiah($receipt->lines->sum('line_total')) }}
                                                    </span>
                                                    <span class="val-mobile">
                                                        {{ number_format($receipt->lines->sum('line_total') ?? 0, 0, ',', '.') }}
                                                    </span>
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

                {{-- 3) RINGKASAN NILAI --}}
                <div class="col-12 col-lg-6 order-3 order-lg-2">
                    <div class="card-soft h-100">
                        <div class="card-body">
                            <h6 class="section-title mb-3">Ringkasan Nilai</h6>

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

                                <hr class="my-2 summary-hr">

                                <dt class="col-sm-5 fw-semibold summary-grand">Grand Total</dt>
                                <dd class="col-sm-7 text-end fw-semibold fs-6 summary-grand">
                                    {{ rupiah($receipt->grand_total ?? 0) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
