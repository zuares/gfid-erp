{{-- resources/views/sales/shipments/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Shipment • ' . $shipment->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: .75rem .75rem 3.5rem;
            max-height: 100vh;
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

        /* ========= BUTTONS ========= */
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
            transition: background .12s ease, border-color .12s ease, color .12s ease, box-shadow .12s ease;
        }

        .btn-theme-outline {
            background: transparent;
            transition: background .12s ease, border-color .12s ease, color .12s ease;
        }

        .page-theme-default .btn-theme-main {
            background: #2563eb;
            border-color: #2563eb;
            color: #eff6ff;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.30);
        }

        .page-theme-default .btn-theme-main:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }

        .page-theme-default .btn-theme-outline {
            border-color: rgba(148, 163, 184, 0.7);
            color: #4b5563;
        }

        .page-theme-default .btn-theme-outline:hover {
            background: rgba(226, 232, 240, 0.7);
        }

        .page-theme-shopee .btn-theme-main {
            background: #f97316;
            border-color: #f97316;
            color: #fff7ed;
            box-shadow: 0 6px 16px rgba(248, 113, 113, 0.35);
        }

        .page-theme-shopee .btn-theme-main:hover {
            background: #ea580c;
            border-color: #ea580c;
        }

        .page-theme-shopee .btn-theme-outline {
            border-color: rgba(248, 113, 113, 0.9);
            color: #b91c1c;
        }

        .page-theme-shopee .btn-theme-outline:hover {
            background: rgba(254, 226, 226, 0.9);
        }

        .page-theme-tiktok .btn-theme-main {
            background: #0f766e;
            border-color: #0f766e;
            color: #e0f2fe;
            box-shadow: 0 6px 16px rgba(45, 212, 191, 0.4);
        }

        .page-theme-tiktok .btn-theme-main:hover {
            background: #0e7490;
            border-color: #0e7490;
        }

        .page-theme-tiktok .btn-theme-outline {
            border-color: rgba(45, 212, 191, 0.8);
            color: #0f766e;
        }

        .page-theme-tiktok .btn-theme-outline:hover {
            background: rgba(224, 242, 254, 0.9);
        }

        body[data-theme="dark"] .btn-theme-outline {
            color: #e5e7eb;
            border-color: rgba(148, 163, 184, 0.6);
        }

        body[data-theme="dark"] .btn-theme-outline:hover {
            background: rgba(15, 23, 42, 0.9);
        }

        /* ========= SCAN INPUT ========= */
        .scan-input {
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: .12em;
            padding: .7rem .9rem;
            text-transform: uppercase;
            border-radius: 12px;
            border-width: 2px;
        }

        .scan-input::placeholder {
            text-transform: none;
            letter-spacing: normal;
            font-weight: 400;
            color: #9ca3af;
        }

        body[data-theme="dark"] .scan-input::placeholder {
            color: #6b7280;
        }

        .scan-input:focus {
            border-color: rgba(59, 130, 246, 0.9);
            box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.15);
        }

        .scan-meta {
            font-size: .78rem;
            color: #4b5563;
        }

        body[data-theme="dark"] .scan-meta {
            color: #e5e7eb;
        }

        /* ========= INFO UTAMA LAYOUT ========= */
        .info-main-col {
            min-width: 260px;
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

        .scan-panel {
            border-radius: 12px;
            padding: .85rem .9rem;
            background: rgba(248, 250, 252, 0.96);
            border: 1px solid rgba(148, 163, 184, 0.35);
        }

        body[data-theme="dark"] .scan-panel {
            background: rgba(15, 23, 42, 0.98);
            border-color: rgba(30, 64, 175, 0.75);
        }

        @media (min-width: 768px) {
            .info-main-col {
                border-left: 1px solid rgba(148, 163, 184, 0.25);
                padding-left: 1.25rem;
            }

            body[data-theme="dark"] .info-main-col {
                border-left-color: rgba(51, 65, 85, 0.85);
            }
        }

        /* ========= TABLE & SCROLL (KOTAK SCROLL KECIL) ========= */
        .lines-wrapper {
            max-height: 25vh;
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

        @media (max-width: 768px) {
            .lines-wrapper {
                max-height: 320px;
            }
        }

        .table-responsive {
            position: relative;
            margin-bottom: 0;
        }

        .table-lines {
            margin-bottom: 0;
        }

        /* THEAD sticky DI DALAM .lines-wrapper */
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

        /* ========= QTY DISPLAY ========= */
        .qty-display {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 64px;
            padding: .25rem .7rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.55);
            font-weight: 600;
            font-size: .9rem;
            cursor: pointer;
            background: rgba(248, 250, 252, 0.96);
            transition: background .12s ease, transform .12s ease, box-shadow .1s ease;
        }

        .qty-display:hover {
            background: rgba(239, 246, 255, 0.96);
            box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.2);
            transform: translateY(-1px);
        }

        body[data-theme="dark"] .qty-display {
            background: rgba(15, 23, 42, 0.98);
            border-color: rgba(148, 163, 184, 0.65);
            color: #e5e7eb;
        }

        body[data-theme="dark"] .qty-display:hover {
            background: rgba(30, 64, 175, 0.55);
            box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.65);
        }

        .qty-edit-form {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
        }

        .qty-edit-input {
            width: 80px;
            text-align: right;
            padding-right: .4rem;
        }

        .qty-edit-save-btn {
            border-radius: 999px;
            padding-inline: .4rem;
            padding-block: .15rem;
            font-size: .75rem;
            line-height: 1;
        }

        /* ========= LAST SCANNED ========= */
        .last-scanned-row td {
            background: rgba(254, 243, 199, 0.96) !important;
        }

        .last-scanned-row td:first-child {
            border-left: 3px solid rgba(234, 88, 12, 0.9);
        }

        body[data-theme="dark"] .last-scanned-row td {
            background: rgba(30, 64, 175, 0.70) !important;
        }

        body[data-theme="dark"] .last-scanned-row td:first-child {
            border-left-color: #38bdf8;
        }

        @keyframes rowPulseSoft {
            0% {
                box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.35);
            }

            100% {
                box-shadow: 0 0 0 12px rgba(234, 88, 12, 0);
            }
        }

        .last-scanned-pulse {
            animation: rowPulseSoft 0.9s ease-out 1;
        }

        /* ========= TOAST (ATAS TENGAH) ========= */
        .scan-toast {
            position: fixed;
            top: 4rem;
            /* di bawah navbar */
            left: 50%;
            transform: translateX(-50%);
            z-index: 1080;
            min-width: 220px;
            max-width: 320px;
            border-radius: 999px;
            padding: .5rem .9rem;
            font-size: .82rem;
            display: none;
            align-items: center;
            gap: .4rem;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.35);
            pointer-events: none;
        }

        .scan-toast-success {
            background: #16a34a;
            color: #ecfdf5;
        }

        .scan-toast-error {
            background: #b91c1c;
            color: #fee2e2;
        }

        /* ========= BUTTON KE SCAN TERAKHIR ========= */
        .btn-jump-last {
            border-radius: 999px;
            padding: .18rem .75rem;
            font-size: .7rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            border: 1px dashed rgba(148, 163, 184, 0.9);
            background: rgba(248, 250, 252, 0.96);
            color: #4b5563;
            display: inline-flex;
            align-items: center;
            gap: .25rem;
        }

        .btn-jump-last::before {
            content: '⤵';
            font-size: .8rem;
        }

        .btn-jump-last:hover {
            background: rgba(219, 234, 254, 0.96);
        }

        body[data-theme="dark"] .btn-jump-last {
            background: rgba(15, 23, 42, 0.98);
            border-color: rgba(148, 163, 184, 0.9);
            color: #e5e7eb;
        }

        .btn-jump-last-highlight {
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.65);
        }
    </style>
@endpush

@section('content')
    @php
        $totalQty = $shipment->lines->sum('qty_scanned');
        $totalLines = $shipment->lines->count();
        $lastScannedLineId = session('last_scanned_line_id');

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
        {{-- HEADER MINIMALIS --}}
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

        {{-- FLASH (AUTO HIDE) --}}
        @if (session('status') === 'error')
            <div class="alert alert-danger js-auto-hide-alert" role="alert">
                {{ session('message') }}
            </div>
        @elseif (session('status') === 'success')
            <div class="alert alert-success js-auto-hide-alert" role="alert">
                {{ session('message') }}
            </div>
        @endif

        {{-- INFO UTAMA --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="meta-label">
                        Info Utama
                    </span>
                </div>

                @if ($shipment->status === 'draft')
                    {{-- MODE DRAFT: SCAN KIRI, INFO KANAN --}}
                    <div class="row g-3 align-items-stretch">
                        {{-- KIRI: PANEL SCAN BARANG --}}
                        <div class="col-md-5">
                            <div class="scan-panel h-100 d-flex flex-column justify-content-between">
                                <div class="mb-2">
                                    <div class="info-label mb-1">
                                        Scan Barang Keluar
                                    </div>
                                    <div class="scan-meta mb-1">
                                        Shipment:
                                        <span class="fw-semibold">{{ $shipment->code }}</span>
                                    </div>
                                </div>

                                <form id="scanForm" method="POST"
                                    action="{{ route('sales.shipments.scan_item', $shipment) }}">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label small mb-1">Scan kode / barcode</label>
                                        <input type="text" name="scan_code" class="form-control scan-input"
                                            id="scanInput" placeholder="Fokus di sini lalu scan..." autocomplete="off"
                                            required>
                                    </div>
                                    <div class="scan-meta small">
                                        Setelah scan, Qty akan otomatis bertambah pada baris yang sesuai.
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- KANAN: CHANNEL + CATATAN + TOMBOL KE SCAN TERAKHIR --}}
                        <div class="col-md-7 info-main-col">
                            {{-- Channel / Store --}}
                            <div class="mb-3">
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

                            {{-- Catatan --}}
                            <div>
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

                            {{-- Ke scan terakhir di bawah catatan --}}
                            @if ($lastScannedLineId)
                                <div class="d-flex justify-content-end mt-3">
                                    <button type="button" class="btn btn-sm btn-jump-last" id="btnJumpLast">
                                        Ke scan terakhir
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    {{-- MODE SETELAH SUBMIT/POSTED: TANPA SCAN, INFO DETAIL FULL WIDTH --}}
                    <div class="row">
                        <div class="col-12 info-main-col">
                            {{-- Channel / Store --}}
                            <div class="mb-3">
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

                            {{-- Catatan --}}
                            <div class="mb-3">
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

                            {{-- META DETAIL --}}
                            <div class="row row-cols-1 row-cols-md-2 gy-2 small">
                                <div>
                                    <div class="info-label mb-1">Tanggal</div>
                                    <div>{{ id_date($shipment->date) }}</div>
                                </div>
                                <div>
                                    <div class="info-label mb-1">Status</div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-capitalize">{{ $shipment->status }}</span>
                                        @if ($shipment->status === 'submitted')
                                            <span class="badge-status badge-status-submitted">Submitted</span>
                                        @elseif ($shipment->status === 'posted')
                                            <span class="badge-status badge-status-posted">Posted</span>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <div class="info-label mb-1">Total Baris</div>
                                    <div>{{ number_format($totalLines, 0, ',', '.') }}</div>
                                </div>
                                <div>
                                    <div class="info-label mb-1">Total Qty Scanned</div>
                                    <div>{{ number_format($totalQty, 0, ',', '.') }}</div>
                                </div>
                                <div>
                                    <div class="info-label mb-1">Dibuat oleh</div>
                                    <div>{{ $shipment->creator?->name ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="info-label mb-1">Dibuat / Terakhir diupdate</div>
                                    <div>
                                        {{ id_datetime($shipment->created_at) }}<br>
                                        <span class="text-muted">Update: {{ id_datetime($shipment->updated_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- REKAP + LINES --}}
        <div class="card card-main">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="meta-label mb-1">
                            Daftar Barang Keluar
                        </div>
                        <div class="small text-muted">
                            Klik angka <strong>Qty</strong> untuk mengedit jumlah.
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="summary-pill">
                            Baris:
                            <span class="fw-semibold ms-1" id="summaryTotalLines">{{ $totalLines }}</span>
                        </div>
                        <div class="summary-pill">
                            Total qty (server):
                            <span class="fw-semibold ms-1" id="summaryTotalQty">
                                {{ number_format($totalQty, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="ms-2">
                            <div class="input-group input-group-sm" style="width: 230px;">
                                <span class="input-group-text">Cari</span>
                                <input type="text" id="itemFilterInput" class="form-control"
                                    placeholder="Kode / nama barang">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- KOTAK SCROLL KECIL + HEADER STICKY --}}
                <div class="table-responsive lines-wrapper" id="linesWrapper">
                    <table class="table align-middle table-lines">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 140px;">Kode</th>
                                <th>Nama Barang</th>
                                <th style="width: 140px;" class="text-end">Qty</th>
                            </tr>
                        </thead>
                        <tbody id="linesTbody">
                            @forelse ($shipment->lines as $line)
                                <tr class="{{ $lastScannedLineId == $line->id ? 'last-scanned-row' : '' }}"
                                    data-line-id="{{ $line->id }}">
                                    <td class="text-muted small order-cell">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td>
                                        <div class="item-code">
                                            {{ $line->item?->code ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small item-name">
                                            {{ $line->item?->name ?? '-' }}
                                        </div>
                                        @if ($line->remarks)
                                            <div class="small text-muted">
                                                Catatan: {{ $line->remarks }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="qty-display" data-line-id="{{ $line->id }}"
                                            id="qty-display-{{ $line->id }}">
                                            {{ number_format($line->qty_scanned, 0, ',', '.') }}
                                        </span>

                                        <form action="{{ route('sales.shipments.update_line_qty', $line) }}"
                                            method="POST" class="d-inline qty-edit-form d-none"
                                            data-line-id="{{ $line->id }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="number" name="qty"
                                                class="form-control form-control-sm qty-edit-input" min="0"
                                                value="{{ $line->qty_scanned }}">
                                            <button type="submit" class="btn btn-primary btn-sm qty-edit-save-btn">
                                                ✔
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr class="no-lines-row">
                                    <td colspan="4" class="text-center text-muted py-4">
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
                        <span class="fw-semibold" id="footerTotalQty">
                            {{ number_format($totalQty, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        {{-- Kembali ke list --}}
                        <a href="{{ route('sales.shipments.index') }}" class="btn btn-theme-outline">
                            &larr; Kembali ke list
                        </a>

                        {{-- 🔥 Tombol Invoice (dari Shipment) --}}
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

                        {{-- Aksi status --}}
                        @if ($shipment->status === 'draft')
                            <form action="{{ route('sales.shipments.submit', $shipment) }}" method="POST"
                                onsubmit="return confirm('Submit shipment ini? Setelah submit tidak bisa di-scan lagi.')">
                                @csrf
                                <button type="submit" class="btn btn-theme-main">
                                    Submit Shipment
                                </button>
                            </form>
                        @elseif ($shipment->status === 'submitted')
                            <form action="{{ route('sales.shipments.post', $shipment) }}" method="POST"
                                onsubmit="return confirm('Posting shipment ini dan kurangi stok WH-RTS?')">
                                @csrf
                                <button type="submit" class="btn btn-theme-main">
                                    Posting &amp; Kurangi Stok
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TOAST --}}
    <div id="scanToast" class="scan-toast"></div>
@endsection

@push('scripts')
    <script>
        (function() {
            console.log('GFID shipment scan script loaded');

            const isDraft = {{ $shipment->status === 'draft' ? 'true' : 'false' }};
            let lastScannedLineId = @json($lastScannedLineId);

            const scanInput = document.getElementById('scanInput');
            const scanForm = document.getElementById('scanForm');
            const linesWrapper = document.getElementById('linesWrapper');
            const itemFilterInput = document.getElementById('itemFilterInput');
            const btnJumpLast = document.getElementById('btnJumpLast');
            const linesTbody = document.getElementById('linesTbody');
            const toastEl = document.getElementById('scanToast');
            const summaryTotalLines = document.getElementById('summaryTotalLines');
            const summaryTotalQty = document.getElementById('summaryTotalQty');
            const footerTotalQty = document.getElementById('footerTotalQty');

            /* ===== AUTO HIDE FLASH ALERT ===== */
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
                }, 2600); // 2.6 detik sebelum mulai fade
            }

            function focusScan() {
                if (scanInput) {
                    scanInput.focus();
                    scanInput.select();
                }
            }

            // Uppercase input scan
            if (scanInput) {
                scanInput.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            }

            function playBeep(freq, duration = 0.15, volume = 0.2) {
                try {
                    const Ctx = window.AudioContext || window.webkitAudioContext;
                    if (!Ctx) return;
                    const ctx = new Ctx();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.value = freq;
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    gain.gain.setValueAtTime(volume, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);
                    osc.start();
                    osc.stop(ctx.currentTime + duration);
                } catch (e) {}
            }

            function playSuccessBeep() {
                playBeep(1046);
            }

            function playErrorBeep() {
                playBeep(220, 0.18, 0.25);
            }

            function showToast(type, message) {
                if (!toastEl) return;
                toastEl.className = 'scan-toast ' + (type === 'success' ? 'scan-toast-success' : 'scan-toast-error');
                toastEl.textContent = message;
                toastEl.style.display = 'flex';
                toastEl.style.opacity = '1';

                setTimeout(() => {
                    toastEl.style.transition = 'opacity .3s ease';
                    toastEl.style.opacity = '0';
                    setTimeout(() => {
                        toastEl.style.display = 'none';
                        toastEl.style.opacity = '1';
                        toastEl.style.transition = '';
                    }, 320);
                }, 1300);
            }

            function scrollToRow(lineId, withPulse = true) {
                if (!linesWrapper) return;
                const row = linesWrapper.querySelector('tr[data-line-id="' + lineId + '"]');
                if (!row) return;

                const prev = linesWrapper.querySelectorAll('.last-scanned-row');
                for (let i = 0; i < prev.length; i++) {
                    prev[i].classList.remove('last-scanned-row');
                }
                row.classList.add('last-scanned-row');

                const wrapperRect = linesWrapper.getBoundingClientRect();
                const rowRect = row.getBoundingClientRect();
                const offsetInside = rowRect.top - wrapperRect.top;
                const targetScrollTop = linesWrapper.scrollTop + offsetInside - (linesWrapper.clientHeight * 0.3);

                linesWrapper.scrollTo({
                    top: Math.max(targetScrollTop, 0),
                    behavior: 'smooth'
                });

                if (withPulse) {
                    row.classList.add('last-scanned-pulse');
                    setTimeout(() => row.classList.remove('last-scanned-pulse'), 1200);
                }
            }

            function renumberRows() {
                if (!linesTbody) return;
                let idx = 1;
                const rows = linesTbody.querySelectorAll('tr[data-line-id]');
                for (let i = 0; i < rows.length; i++) {
                    const c = rows[i].querySelector('.order-cell');
                    if (c) c.textContent = idx++;
                }
            }

            function bindQtyClickForRow(row) {
                const lineId = row.getAttribute('data-line-id');
                const qtyEl = row.querySelector('.qty-display');
                const form = row.querySelector('.qty-edit-form[data-line-id="' + lineId + '"]');
                const input = form ? form.querySelector('.qty-edit-input') : null;

                if (!qtyEl || !form || !input) return;
                if (qtyEl.dataset.boundClick === '1') return;

                qtyEl.addEventListener('click', function() {
                    qtyEl.classList.add('d-none');
                    form.classList.remove('d-none');
                    input.focus();
                    input.select();
                });

                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const newQty = input.value.trim();
                    if (newQty === '' || Number(newQty) < 0) {
                        playErrorBeep();
                        showToast('error', 'Qty tidak valid.');
                        return;
                    }

                    const formData = new FormData(form);

                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    }).then(async (res) => {
                        let data = null;
                        try {
                            data = await res.json();
                        } catch (err) {
                            form.submit();
                            return;
                        }

                        if (!res.ok || !data || data.status !== 'ok') {
                            const msg = data && data.message ? data.message : 'Gagal update qty.';
                            playErrorBeep();
                            showToast('error', msg);
                            return;
                        }

                        playSuccessBeep();

                        const totals = data.totals || {};

                        if (data.deleted) {
                            row.remove();
                            renumberRows();
                        } else {
                            const qtyVal = typeof data.qty !== 'undefined' ?
                                data.qty :
                                Number(input.value) || 0;
                            qtyEl.textContent = new Intl.NumberFormat('id-ID').format(qtyVal);
                            form.classList.add('d-none');
                            qtyEl.classList.remove('d-none');
                        }

                        if (typeof totals.total_lines !== 'undefined' && summaryTotalLines) {
                            summaryTotalLines.textContent = totals.total_lines;
                        }
                        if (typeof totals.total_qty !== 'undefined') {
                            const f = new Intl.NumberFormat('id-ID').format(totals.total_qty);
                            if (summaryTotalQty) summaryTotalQty.textContent = f;
                            if (footerTotalQty) footerTotalQty.textContent = f;
                        }

                        showToast('success', data.message || 'Qty berhasil diperbarui.');
                    }).catch(() => {
                        form.submit();
                    });
                });

                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        form.dispatchEvent(new Event('submit', {
                            cancelable: true
                        }));
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        form.classList.add('d-none');
                        qtyEl.classList.remove('d-none');
                    }
                });

                qtyEl.dataset.boundClick = '1';
            }

            // Bind awal untuk semua row
            if (linesTbody) {
                const rows = linesTbody.querySelectorAll('tr[data-line-id]');
                for (let i = 0; i < rows.length; i++) {
                    bindQtyClickForRow(rows[i]);
                }
            }

            // Fokus & scroll awal
            window.addEventListener('load', function() {
                if (isDraft && scanInput) {
                    focusScan();
                }

                if (lastScannedLineId) {
                    scrollToRow(lastScannedLineId, true);

                    if (btnJumpLast) {
                        btnJumpLast.classList.add('btn-jump-last-highlight');
                        setTimeout(() => {
                            btnJumpLast.classList.remove('btn-jump-last-highlight');
                        }, 2200);
                    }
                }
            });

            // Ke scan terakhir (hanya saat draft karena tombol cuma muncul di draft)
            if (btnJumpLast && linesWrapper) {
                btnJumpLast.addEventListener('click', function() {
                    if (!lastScannedLineId) return;
                    scrollToRow(lastScannedLineId, true);
                });
            }

            // Filter cepat
            if (itemFilterInput && linesWrapper) {
                itemFilterInput.addEventListener('input', function() {
                    const term = this.value.toLowerCase().trim();
                    const rows = linesWrapper.querySelectorAll('tbody tr[data-line-id]');

                    for (let i = 0; i < rows.length; i++) {
                        const row = rows[i];
                        const codeEl = row.querySelector('.item-code');
                        const nameEl = row.querySelector('.item-name');

                        const codeText = (codeEl ? codeEl.textContent : '').toLowerCase();
                        const nameText = (nameEl ? nameEl.textContent : '').toLowerCase();

                        if (!term || codeText.includes(term) || nameText.includes(term)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    }
                });
            }

            // SCAN VIA AJAX (hanya draft)
            if (isDraft && scanForm && scanInput && linesTbody) {
                scanForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const code = scanInput.value.trim();
                    if (!code) {
                        playErrorBeep();
                        showToast('error', 'Kode kosong.');
                        focusScan();
                        return;
                    }

                    const formData = new FormData(scanForm);

                    fetch(scanForm.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    }).then(async (res) => {
                        let data = null;
                        try {
                            data = await res.json();
                        } catch (err) {
                            scanForm.submit();
                            return;
                        }

                        if (!res.ok || !data || data.status !== 'ok') {
                            const msg = data && data.message ? data.message : 'Gagal scan barang.';
                            playErrorBeep();
                            showToast('error', msg);
                            scanInput.value = '';
                            focusScan();
                            return;
                        }

                        playSuccessBeep();

                        const line = data.line;
                        const totals = data.totals || {};

                        if (!line || !line.id) {
                            showToast('success', data.message || 'Berhasil scan.');
                        } else {
                            let row = linesTbody.querySelector('tr[data-line-id="' + line.id +
                            '"]');

                            if (!row) {
                                const emptyRow = linesTbody.querySelector('.no-lines-row');
                                if (emptyRow) {
                                    emptyRow.remove();
                                }

                                const updateUrl = line.update_qty_url ? line.update_qty_url : '';

                                row = document.createElement('tr');
                                row.setAttribute('data-line-id', line.id);

                                row.innerHTML =
                                    '<td class="text-muted small order-cell"></td>' +
                                    '<td><div class="item-code"></div></td>' +
                                    '<td>' +
                                    '<div class="small item-name"></div>' +
                                    '<div class="small text-muted remarks-wrap d-none"></div>' +
                                    '</td>' +
                                    '<td class="text-end">' +
                                    '<span class="qty-display" data-line-id="' + line.id +
                                    '" id="qty-display-' + line.id + '"></span>' +
                                    '<form action="' + updateUrl +
                                    '" method="POST" class="d-inline qty-edit-form d-none" data-line-id="' +
                                    line.id + '">' +
                                    '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
                                    '<input type="hidden" name="_method" value="PATCH">' +
                                    '<input type="number" name="qty" class="form-control form-control-sm qty-edit-input" min="0" value="' +
                                    (line.qty_scanned || 0) + '">' +
                                    '<button type="submit" class="btn btn-primary btn-sm qty-edit-save-btn">✔</button>' +
                                    '</form>' +
                                    '</td>';

                                linesTbody.appendChild(row);
                            }

                            const codeEl = row.querySelector('.item-code');
                            const nameEl = row.querySelector('.item-name');
                            const remarksWrap = row.querySelector('.remarks-wrap');
                            const qtyEl = row.querySelector('#qty-display-' + line.id);

                            if (codeEl) codeEl.textContent = line.item_code || '-';
                            if (nameEl) nameEl.textContent = line.item_name || '-';

                            if (remarksWrap) {
                                if (line.remarks) {
                                    remarksWrap.textContent = 'Catatan: ' + line.remarks;
                                    remarksWrap.classList.remove('d-none');
                                } else {
                                    remarksWrap.classList.add('d-none');
                                }
                            }

                            if (qtyEl) {
                                qtyEl.textContent = new Intl.NumberFormat('id-ID').format(line
                                    .qty_scanned || 0);
                            }

                            bindQtyClickForRow(row);
                            renumberRows();

                            if (typeof totals.total_lines !== 'undefined' && summaryTotalLines) {
                                summaryTotalLines.textContent = totals.total_lines;
                            }
                            if (typeof totals.total_qty !== 'undefined') {
                                const f = new Intl.NumberFormat('id-ID').format(totals.total_qty);
                                if (summaryTotalQty) summaryTotalQty.textContent = f;
                                if (footerTotalQty) footerTotalQty.textContent = f;
                            }

                            lastScannedLineId = line.id;
                            scrollToRow(line.id, true);

                            const msgItem = line.item_code ? '+1 ' + line.item_code : '+1 item';
                            showToast('success', data.message || ('Berhasil ' + msgItem));
                        }

                        scanInput.value = '';
                        focusScan();
                    }).catch(() => {
                        scanForm.submit();
                    });
                });
            }
        })();
    </script>
@endpush
