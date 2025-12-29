{{-- resources/views/sales/shipments/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Shipment • ' . $shipment->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: .75rem .75rem 3.5rem;
            min-height: 100vh;
        }

        /* ========= GLOBAL BACKGROUND ========= */
        body[data-theme="light"] .page-wrap {
            background: #f3f4f6;
        }

        body[data-theme="light"] .page-wrap.page-theme-shopee {
            background: #fff7ed;
        }

        body[data-theme="light"] .page-wrap.page-theme-tiktok {
            background: #ecfeff;
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(15, 23, 42, 0.9) 0,
                    #020617 65%);
        }

        body[data-theme="dark"] .page-wrap.page-theme-shopee {
            background: radial-gradient(circle at top left,
                    rgba(148, 27, 19, 0.9) 0,
                    #020617 65%);
        }

        body[data-theme="dark"] .page-wrap.page-theme-tiktok {
            background: radial-gradient(circle at top left,
                    rgba(8, 47, 73, 0.9) 0,
                    #020617 65%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
        }

        body[data-theme="dark"] .card-main {
            border-color: rgba(30, 64, 175, 0.6);
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.8);
        }

        .meta-label {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #6b7280;
        }

        body[data-theme="dark"] .meta-label {
            color: #9ca3af;
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

        .badge-status-submitted {
            background: rgba(59, 130, 246, 0.10);
            color: #1d4ed8;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .badge-status-posted {
            background: rgba(34, 197, 94, 0.10);
            color: #166534;
            border: 1px solid rgba(34, 197, 94, 0.25);
        }

        body[data-theme="dark"] .badge-status-draft {
            background: rgba(251, 191, 36, 0.22);
            color: #fef9c3;
            border-color: rgba(245, 158, 11, 0.65);
        }

        body[data-theme="dark"] .badge-status-submitted {
            background: rgba(59, 130, 246, 0.22);
            color: #bfdbfe;
            border-color: rgba(59, 130, 246, 0.65);
        }

        body[data-theme="dark"] .badge-status-posted {
            background: rgba(34, 197, 94, 0.22);
            color: #bbf7d0;
            border-color: rgba(34, 197, 94, 0.65);
        }

        .summary-pill {
            border-radius: 999px;
            padding: .25rem .8rem;
            font-size: .8rem;
            border: 1px solid rgba(148, 163, 184, 0.3);
            background: rgba(248, 250, 252, 0.96);
        }

        body[data-theme="dark"] .summary-pill {
            background: rgba(15, 23, 42, 0.98);
            border-color: rgba(30, 64, 175, 0.7);
            color: #e5e7eb;
        }

        .store-badge {
            display: inline-flex;
            align-items: center;
            padding: .2rem .6rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .6);
            font-size: .75rem;
        }

        .page-theme-shopee .store-badge {
            border-color: #f97316;
            color: #9a3412;
        }

        .page-theme-tiktok .store-badge {
            border-color: #06b6d4;
            color: #0f766e;
        }

        .info-label {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #9ca3af;
        }

        body[data-theme="dark"] .info-label {
            color: #6b7280;
        }

        /* ========= INFO PILL MINIMAL ========= */
        .info-pill {
            display: inline-flex;
            flex-direction: column;
            justify-content: center;
            padding: .25rem .7rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            font-size: .75rem;
            background: rgba(248, 250, 252, 0.96);
        }

        .info-pill-label {
            font-size: .64rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #9ca3af;
        }

        .info-pill-value {
            font-size: .8rem;
        }

        body[data-theme="dark"] .info-pill {
            background: rgba(15, 23, 42, 0.98);
            border-color: rgba(30, 64, 175, 0.75);
            color: #e5e7eb;
        }

        body[data-theme="dark"] .info-pill-label {
            color: #6b7280;
        }

        /* ========= TABLE ========= */
        .lines-wrapper {
            max-height: 50vh;
            overflow-y: auto;
            overscroll-behavior: contain;
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.7) transparent;
        }

        .lines-wrapper::-webkit-scrollbar {
            width: 6px;
        }

        .lines-wrapper::-webkit-scrollbar-track {
            background: transparent;
        }

        .lines-wrapper::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.7);
            border-radius: 999px;
        }

        .table-lines {
            margin-bottom: 0;
        }

        .table-lines thead th {
            position: sticky;
            top: 0;
            z-index: 6;
            border-bottom-width: 1px;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7280;
            background: rgba(248, 250, 252, 0.98);
        }

        body[data-theme="dark"] .table-lines thead th {
            background: rgba(15, 23, 42, 0.98);
            border-bottom-color: rgba(30, 64, 175, 0.75);
            color: #9ca3af;
        }

        .table-lines tbody td {
            vertical-align: middle;
            border-top-color: rgba(148, 163, 184, 0.18);
            padding-top: .35rem;
            padding-bottom: .35rem;
        }

        body[data-theme="dark"] .table-lines tbody td {
            border-top-color: rgba(51, 65, 85, 0.85);
        }

        .table-lines tbody tr:nth-child(even) {
            background-color: rgba(249, 250, 251, 0.86);
        }

        body[data-theme="dark"] .table-lines tbody tr:nth-child(even) {
            background-color: rgba(15, 23, 42, 0.96);
        }

        .item-code {
            font-weight: 600;
            font-size: .9rem;
        }

        body[data-theme="dark"] .item-code {
            color: #e5e7eb;
        }

        /* ========= RINGKASAN PER KATEGORI ========= */
        .category-summary-table thead th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .category-summary-table tbody td {
            font-size: .82rem;
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    @php
        // totalQty, totalLines, totalHpp, summaryPerCategory sudah dikirim dari controller
        $storeName = $shipment->store->name ?? '';
        $storeCode = $shipment->store->code ?? '';
        $storeKey = strtoupper($storeCode . ' ' . $storeName);

        $scanTheme = 'default';
        if (str_contains($storeKey, 'SHP') || str_contains($storeKey, 'SHOPEE')) {
            $scanTheme = 'shopee';
        } elseif (str_contains($storeKey, 'TTK') || str_contains($storeKey, 'TIKTOK')) {
            $scanTheme = 'tiktok';
        }
    @endphp

    <div class="page-wrap page-theme-{{ $scanTheme }}">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
            <div>
                <div class="meta-label mb-1">
                    Shipment
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h1 class="h5 mb-0">
                        {{ $shipment->code }}
                    </h1>

                    @if ($shipment->status === 'draft')
                        <span class="badge-status badge-status-draft">Draft</span>
                    @elseif ($shipment->status === 'submitted')
                        <span class="badge-status badge-status-submitted">Submitted</span>
                    @else
                        <span class="badge-status badge-status-posted">Posted</span>
                    @endif
                </div>

                <div class="small text-muted">
                    {{ id_date($shipment->date) }}

                    @if ($shipment->store)
                        &bull;
                        {{ $shipment->store->name }}
                        @if ($shipment->store->code ?? false)
                            ({{ strtoupper($shipment->store->code) }})
                        @endif
                    @else
                        &bull; Channel belum diisi
                    @endif
                </div>
            </div>

            <div class="text-end small text-muted">
                <div class="meta-label mb-1">
                    Dibuat oleh
                </div>
                <div class="fw-semibold">
                    {{ $shipment->creator?->name ?? '-' }}
                </div>
                <div>
                    {{ id_datetime($shipment->created_at) }}
                </div>
            </div>
        </div>

        {{-- FLASH --}}
        @if (session('status') === 'error')
            <div class="alert alert-danger js-auto-hide-alert" role="alert">
                {{ session('message') }}
            </div>
        @elseif (session('status') === 'success')
            <div class="alert alert-success js-auto-hide-alert" role="alert">
                {{ session('message') }}
            </div>
        @endif

        {{-- INFO UTAMA (MINIMAL) --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="meta-label">
                        Info Utama
                    </span>
                    @if ($shipment->status === 'draft')
                        <a href="{{ route('sales.shipments.edit', $shipment) }}" class="btn btn-sm btn-outline-primary">
                            Edit &amp; Scan
                        </a>
                    @endif
                </div>

                {{-- Baris 1: Channel + creator (kanan) --}}
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-2">
                    <div>
                        <div class="info-label mb-1">
                            Channel / Store
                        </div>

                        @if ($shipment->store)
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <div class="fw-semibold">
                                    {{ $shipment->store->name }}
                                </div>

                                @if ($shipment->store->code ?? false)
                                    <div class="store-badge">
                                        {{ strtoupper($shipment->store->code) }}
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-muted small">
                                Tidak diisi.
                            </div>
                        @endif
                    </div>

                    <div class="small text-muted text-end">
                        <div class="info-label mb-1">Dibuat / Update</div>
                        <div>
                            {{ id_datetime($shipment->created_at) }}<br>
                            <span class="text-muted">Update: {{ id_datetime($shipment->updated_at) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Baris 2: Catatan --}}
                <div class="mb-2">
                    <div class="info-label mb-1">
                        Catatan
                    </div>

                    @if ($shipment->notes)
                        <div class="small">
                            {!! nl2br(e($shipment->notes)) !!}
                        </div>
                    @else
                        <div class="text-muted small">
                            Tidak ada catatan.
                        </div>
                    @endif
                </div>

                {{-- Baris 3: meta ringkas dalam pill --}}
                <div class="d-flex flex-wrap gap-2">
                    <span class="info-pill">
                        <span class="info-pill-label">Tanggal</span>
                        <span class="info-pill-value">{{ id_date($shipment->date) }}</span>
                    </span>

                    <span class="info-pill">
                        <span class="info-pill-label">Status</span>
                        <span class="info-pill-value text-capitalize">
                            {{ $shipment->status }}
                        </span>
                    </span>

                    <span class="info-pill">
                        <span class="info-pill-label">Total Baris</span>
                        <span class="info-pill-value">
                            {{ number_format($totalLines, 0, ',', '.') }}
                        </span>
                    </span>

                    <span class="info-pill">
                        <span class="info-pill-label">Total Qty</span>
                        <span class="info-pill-value">
                            {{ number_format($totalQty, 0, ',', '.') }}
                        </span>
                    </span>

                    <span class="info-pill">
                        <span class="info-pill-label">Total HPP</span>
                        <span class="info-pill-value">
                            Rp {{ number_format($totalHpp, 0, ',', '.') }}
                        </span>
                    </span>
                </div>
            </div>
        </div>

        {{-- RINGKASAN PER KATEGORI --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="meta-label">Ringkasan per Kategori</span>
                </div>

                @if ($summaryPerCategory->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 category-summary-table">
                            <thead>
                                <tr class="text-muted">
                                    <th style="width: 40px;">#</th>
                                    <th>Kategori</th>
                                    <th class="text-end" style="width: 110px;">Baris</th>
                                    <th class="text-end" style="width: 130px;">Total Qty</th>
                                    <th class="text-end" style="width: 160px;">Total HPP (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($summaryPerCategory as $idx => $cat)
                                    <tr>
                                        <td class="text-muted">{{ $idx + 1 }}</td>
                                        <td>{{ $cat['category_name'] }}</td>
                                        <td class="text-end">
                                            {{ number_format($cat['total_lines'], 0, ',', '.') }}
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($cat['total_qty'], 0, ',', '.') }}
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($cat['total_hpp'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted small">
                        Belum ada data kategori untuk shipment ini.
                    </div>
                @endif
            </div>
        </div>

        {{-- REKAP + LINES (READ ONLY) --}}
        <div class="card card-main">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="meta-label mb-1">
                            Daftar Barang Keluar
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="summary-pill">
                            Baris:
                            <span class="fw-semibold ms-1">{{ number_format($totalLines, 0, ',', '.') }}</span>
                        </div>
                        <div class="summary-pill">
                            Total qty (server):
                            <span class="fw-semibold ms-1">
                                {{ number_format($totalQty, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="summary-pill">
                            Total HPP (server):
                            <span class="fw-semibold ms-1">
                                Rp {{ number_format($totalHpp, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="lines-wrapper">
                    <table class="table align-middle table-lines">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 140px;">Kode</th>
                                <th>Nama Barang</th>
                                <th style="width: 110px;" class="text-end">Qty</th>
                                <th style="width: 130px;" class="text-end">HPP / Unit</th>
                                <th style="width: 150px;" class="text-end">Total HPP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($shipment->lines as $line)
                                <tr>
                                    <td class="text-muted small">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td>
                                        <div class="item-code">
                                            {{ $line->item?->code ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            {{ $line->item?->name ?? '-' }}
                                        </div>
                                        @if ($line->remarks)
                                            <div class="small text-muted">
                                                Catatan: {{ $line->remarks }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($line->qty_scanned, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        Rp {{ number_format($line->unit_hpp ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        Rp {{ number_format($line->total_hpp ?? 0, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada item yang discan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- FOOTER + BUTTONS --}}
                <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center small text-muted gap-2">
                    <div>
                        Dibuat: {{ id_datetime($shipment->created_at) }}<br>
                        Terakhir diupdate: {{ id_datetime($shipment->updated_at) }}<br>
                        Total qty keluar (server):
                        <span class="fw-semibold">
                            {{ number_format($totalQty, 0, ',', '.') }}
                        </span><br>
                        Total HPP (server):
                        <span class="fw-semibold">
                            Rp {{ number_format($totalHpp, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('sales.shipments.index') }}" class="btn btn-theme-outline">
                            &larr; Kembali ke list
                        </a>

                        @if ($shipment->lines->isNotEmpty())
                            <a href="{{ route('sales.shipments.export_lines', $shipment) }}"
                                class="btn btn-theme-outline">
                                Export Barang (CSV)
                            </a>
                        @endif

                        @if ($shipment->status === 'posted')
                            @if (empty($shipment->sales_invoice_id))
                                <a href="{{ route('sales.invoices.create_from_shipment', $shipment) }}"
                                    class="btn btn-theme-outline">
                                    Buat Invoice dari Shipment ini
                                </a>
                            @else
                                <a href="{{ route('sales.invoices.show', $shipment->sales_invoice_id) }}"
                                    class="btn btn-theme-outline">
                                    Lihat Invoice {{ $shipment->invoice?->code ?? '' }}
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const autoAlerts = document.querySelectorAll('.js-auto-hide-alert');
            if (autoAlerts.length) {
                setTimeout(() => {
                    autoAlerts.forEach((el) => {
                        el.style.transition = 'opacity .4s ease';
                        el.style.opacity = '0';
                        setTimeout(() => {
                            if (el && el.parentNode) {
                                el.parentNode.removeChild(el);
                            }
                        }, 450);
                    });
                }, 2600);
            }
        })();
    </script>
@endpush
