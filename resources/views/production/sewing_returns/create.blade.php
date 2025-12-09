{{-- resources/views/production/sewing_returns/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Return')

@push('head')
    <style>
        :root {
            --card-radius-lg: 14px;
        }

        .page-wrap {
            max-width: 980px;
            margin-inline: auto;
            padding: .6rem .75rem 6rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(16, 185, 129, 0.12) 0,
                    rgba(240, 253, 250, 0.4) 16%,
                    #f9fafb 48%,
                    #f9fafb 100%);
        }

        .card {
            background: var(--card);
            border-radius: var(--card-radius-lg);
            border: 1px solid rgba(148, 163, 184, 0.16);
            box-shadow:
                0 6px 18px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .card-section {
            padding: .8rem .9rem;
        }

        @media (min-width: 768px) {
            .card-section {
                padding: .9rem 1.1rem;
            }

            .page-wrap {
                padding-top: 1rem;
                padding-bottom: 3.5rem;
            }
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .table-wrap {
            overflow-x: auto;
        }

        /* HEADER */
        .header-row {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .header-title h1 {
            font-size: 1rem;
            font-weight: 700;
        }

        .header-subtitle {
            font-size: .78rem;
            color: var(--muted);
        }

        .header-icon-circle {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: .6rem;
            background: radial-gradient(circle,
                    rgba(22, 163, 74, 0.12) 0,
                    rgba(22, 163, 74, 0.04) 60%,
                    transparent 100%);
            color: #16a34a;
        }

        .btn-header-pill {
            border-radius: 999px;
            padding: .35rem .8rem;
            font-size: .78rem;
            font-weight: 500;
            border-width: 1px;
        }

        .btn-header-muted {
            background: rgba(248, 250, 252, 0.96);
            border-color: rgba(148, 163, 184, 0.5);
            color: #0f172a;
        }

        .btn-header-accent {
            background: rgba(219, 246, 230, 0.85);
            border-color: rgba(22, 163, 74, 0.7);
            color: #166534;
        }

        .field-block {
            display: flex;
            flex-direction: column;
            gap: .18rem;
        }

        .field-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 600;
            color: var(--muted);
        }

        .field-input-sm {
            font-size: .84rem;
        }

        /* SUMMARY */
        .return-summary-header {
            display: flex;
            flex-direction: column;
            gap: .25rem;
        }

        .return-summary-title {
            display: flex;
            align-items: center;
            gap: .45rem;
        }

        .return-summary-title h2 {
            margin: 0;
            font-size: .92rem;
            font-weight: 700;
        }

        .return-summary-title-icon {
            width: 26px;
            height: 26px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(220, 252, 231, 0.9);
            color: #16a34a;
            font-size: .9rem;
        }

        .return-summary-sub {
            font-size: .76rem;
            color: var(--muted);
        }

        .return-summary-sub strong {
            color: #166534;
        }

        .summary-badges-row {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
            margin-top: .15rem;
        }

        .summary-pill {
            border-radius: 999px;
            padding: .1rem .6rem;
            font-size: .7rem;
            background: rgba(148, 163, 184, 0.10);
        }

        .summary-pill-ok {
            background: rgba(22, 163, 74, 0.12);
            color: #166534;
        }

        .summary-pill-reject {
            background: rgba(248, 113, 113, 0.16);
            color: #b91c1c;
        }

        /* ROW */
        .return-row {
            transition: background-color .16s ease, box-shadow .16s ease, border-color .16s ease, transform .08s ease;
        }

        .return-row td {
            border-top-color: rgba(148, 163, 184, 0.22) !important;
        }

        .row-empty {
            box-shadow: inset 3px 0 0 rgba(148, 163, 184, .28);
            background: rgba(255, 255, 255, 0.98);
        }

        .row-filled {
            background: radial-gradient(circle at top left,
                    rgba(34, 197, 94, 0.16) 0,
                    rgba(240, 253, 244, 0.96) 50%);
            box-shadow:
                inset 3px 0 0 rgba(22, 163, 74, 0.95),
                0 0 0 1px rgba(187, 247, 208, 0.9);
        }

        .qty-remaining-pill {
            border-radius: 999px;
            padding: .05rem .5rem;
            font-size: .75rem;
            font-weight: 600;
            background: rgba(22, 163, 74, 0.09);
            color: #15803d;
            border: 1px solid rgba(22, 163, 74, 0.45);
        }

        .qty-input {
            font-weight: 500;
            font-size: .82rem;
            text-align: center;
            transition: font-weight .12s ease, box-shadow .12s ease, border-color .12s ease, background-color .12s ease;
        }

        .qty-input-active {
            font-weight: 600;
            border-color: rgba(22, 163, 74, .75);
            box-shadow: 0 0 0 1px rgba(22, 163, 74, .4);
            background: rgba(240, 253, 244, 0.96);
        }

        .notes-input {
            font-size: .76rem;
        }

        .mobile-muted-soft {
            color: var(--muted);
            font-size: .74rem;
        }

        /* FOOTER BUTTON (global style) */
        .form-footer .btn {
            border-radius: 999px;
        }

        .form-footer .btn-primary {
            position: relative;
            font-weight: 600;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 55%, #15803d 100%);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding-inline: 1.1rem;
            padding-block: .38rem;
            box-shadow:
                0 8px 18px rgba(22, 101, 52, .38),
                0 3px 7px rgba(22, 101, 52, .3);
            transition: transform .08s ease, box-shadow .12s ease, opacity .15s ease;
        }

        .form-footer .btn-primary .btn-icon-badge {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(21, 128, 61, 0.95);
            font-size: .9rem;
            color: #ecfdf3;
        }

        .form-footer .btn-primary .btn-label-main {
            font-size: .82rem;
            line-height: 1.15;
            color: #ecfdf3;
        }

        .form-footer .btn-primary .btn-label-sub {
            font-size: .7rem;
            opacity: .85;
            color: #bbf7d0;
        }

        .form-footer .btn-primary:not(:disabled):active {
            transform: translateY(1px);
            box-shadow:
                0 6px 14px rgba(22, 101, 52, .32),
                0 2px 5px rgba(22, 101, 52, .25);
        }

        .form-footer .btn-primary.is-empty {
            opacity: .7;
            box-shadow:
                0 4px 10px rgba(148, 163, 184, .35),
                0 0 0 1px rgba(148, 163, 184, .2);
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 60%, #9ca3af 100%);
        }

        .form-footer .btn-outline-secondary {
            font-size: .76rem;
            padding-inline: .65rem;
            padding-block: .26rem;
            background: rgba(248, 250, 252, 0.96);
            border-color: rgba(148, 163, 184, .7);
            display: inline-flex;
            align-items: center;
            gap: .22rem;
            box-shadow:
                0 4px 10px rgba(148, 163, 184, .35),
                0 0 0 1px rgba(148, 163, 184, .18);
        }

        @media (max-width: 767.98px) {
            .return-row.is-tap {
                transform: translateY(-1px);
                box-shadow:
                    0 0 0 1px rgba(34, 197, 94, 0.4),
                    0 0 0 5px rgba(34, 197, 94, 0.16);
            }

            .card {
                border-radius: 14px;
            }

            .header-row {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-header-pill {
                width: 100%;
                justify-content: center;
            }

            .table-sewing-return {
                border-collapse: separate;
                border-spacing: 0 10px;
            }

            .table-sewing-return thead {
                display: none;
            }

            /* 🔹 CARD MOBILE – MIRIP SEWING PICKUP, FOCAL: KODE + BELUM SETOR */
            .table-sewing-return tbody tr {
                display: block;
                border-radius: 14px;
                border: 1px solid rgba(148, 163, 184, 0.25);
                padding: .55rem .75rem .65rem;
                margin-bottom: .5rem;
                cursor: pointer;
                background: rgba(255, 255, 255, 0.98);
                box-shadow:
                    0 10px 22px rgba(15, 23, 42, 0.06),
                    0 0 0 1px rgba(15, 23, 42, 0.02);
            }

            body[data-theme="dark"] .table-sewing-return tbody tr {
                background: rgba(15, 23, 42, 0.96);
                border-color: rgba(30, 64, 175, 0.45);
                box-shadow:
                    0 12px 26px rgba(15, 23, 42, 0.8),
                    0 0 0 1px rgba(15, 23, 42, 0.85);
            }

            .table-sewing-return td {
                display: block;
                border: none !important;
                padding: .06rem 0;
            }

            /* TOP AREA: # + KODE BARANG + BELUM SETOR */
            .mobile-row-top {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: .6rem;
                margin-bottom: .25rem;
            }

            .mobile-top-left {
                display: flex;
                flex-direction: column;
                gap: .18rem;
                min-width: 0;
            }

            /* chip # */
            .mobile-top-left .row-index {
                align-self: flex-start;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: .12rem .55rem;
                border-radius: 999px;
                font-size: .7rem;
                font-weight: 600;
                letter-spacing: .08em;
                text-transform: uppercase;
                background: rgba(148, 163, 184, 0.18);
                color: #4b5563;
            }

            body[data-theme="dark"] .mobile-top-left .row-index {
                background: rgba(31, 41, 55, 0.9);
                color: #e5e7eb;
            }

            /* FOCAL: KODE BARANG */
            .mobile-top-left .item-code {
                display: inline-flex;
                align-items: center;
                padding: .26rem .8rem;
                border-radius: 999px;
                font-size: 1.08rem;
                font-weight: 800;
                letter-spacing: .02em;
                white-space: nowrap;
                background: rgba(22, 163, 74, 0.08);
                color: #166534;
                border: 1px solid rgba(22, 163, 74, 0.28);
                box-shadow:
                    0 3px 7px rgba(22, 163, 74, 0.15),
                    0 0 0 1px rgba(22, 163, 74, 0.18);
            }

            body[data-theme="dark"] .mobile-top-left .item-code {
                background: rgba(34, 197, 94, 0.20);
                color: #bbf7d0;
                border-color: rgba(74, 222, 128, 0.5);
                box-shadow: 0 3px 7px rgba(34, 197, 94, 0.22);
            }

            .mobile-top-right {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                text-align: right;
            }

            .qty-remaining-label {
                font-size: .62rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .16em;
                color: var(--muted);
                margin-bottom: .08rem;
            }

            /* FOCAL: BELUM SETOR */
            .mobile-top-right .qty-remaining-pill {
                font-size: 1.18rem;
                font-weight: 800;
                padding: .25rem .7rem;
                border-radius: 999px;

                background: rgba(22, 163, 74, 0.10);
                color: #166534;
                border: 1px solid rgba(22, 163, 74, 0.45);
                box-shadow:
                    0 4px 10px rgba(22, 163, 74, 0.20),
                    0 0 0 1px rgba(74, 222, 128, 0.30);
            }

            body[data-theme="dark"] .mobile-top-right .qty-remaining-pill {
                background: rgba(34, 197, 94, 0.20);
                color: #bbf7d0;
                border-color: rgba(74, 222, 128, 0.55);
                box-shadow: 0 4px 10px rgba(22, 163, 74, 0.28);
            }

            .mobile-meta {
                font-size: .74rem;
                color: var(--muted);
                margin-bottom: .1rem;
            }

            .cell-qty-row {
                display: flex;
                gap: .35rem;
                margin-top: .22rem;
            }

            .cell-notes {
                margin-top: .18rem;
            }

            .td-desktop-only {
                display: none !important;
            }

            .form-footer {
                position: fixed;
                right: .8rem;
                bottom: 4.5rem;
                left: auto;
                z-index: 30;
                display: inline-flex !important;
                flex-direction: row-reverse;
                align-items: center !important;
                gap: .4rem;
                background: transparent;
                margin: 0;
                padding: 0;
                border: none;
            }
        }

        @media (min-width: 768px) {
            .td-mobile-only {
                display: none !important;
            }

            .table-sewing-return tbody tr {
                padding: .45rem .6rem .5rem;
                margin-bottom: .32rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        /** @var \App\Models\SewingPickup|null $selectedPickup */
        $selectedPickupId = old('pickup_id', $pickupId ?? null);
        $defaultDate = old('date', optional($selectedPickup?->date)->format('Y-m-d') ?? now()->format('Y-m-d'));

        $itemCodes = ($lines ?? collect())
            ->map(fn($l) => optional(optional($l->bundle)->finishedItem)->code)
            ->filter()
            ->unique()
            ->values();
    @endphp

    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="header-row">
                    <div class="d-flex align-items-center">
                        <div class="header-icon-circle">
                            <i class="bi bi-clipboard-check"></i>
                        </div>
                        <div class="header-title d-flex flex-column gap-1">
                            <h1>Halaman Setor Jahit</h1>
                            <div class="header-subtitle">
                                Catat hasil jahit yang disetor kembali dari operator.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-md-row gap-2">
                        <a href="{{ route('production.sewing_pickups.create') }}"
                            class="btn btn-sm btn-header-pill btn-header-accent d-flex align-items-center gap-2">
                            <i class="bi bi-box-seam"></i>
                            <span>Ambil Jahit</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <form id="sewing-return-form" action="{{ route('production.sewing_returns.store') }}" method="post">
            @csrf

            {{-- HEADER FORM --}}
            <div class="card mb-2">
                <div class="card-section">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3 col-6">
                            <div class="field-block">
                                <div class="field-label">Tanggal setor</div>
                                <input type="date" name="date"
                                    class="form-control form-control-sm field-input-sm @error('date') is-invalid @enderror"
                                    value="{{ $defaultDate }}">
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <input type="hidden" name="qc_date" value="{{ old('qc_date', $defaultDate) }}">
                            </div>
                        </div>

                        <div class="col-md-5 col-12">
                            <div class="field-block">
                                <div class="field-label">Tanggal + operator ambil jahit</div>
                                <select name="pickup_id" id="pickup_id_select"
                                    class="form-select form-select-sm field-input-sm @error('pickup_id') is-invalid @enderror"
                                    onchange="if(this.value){ window.location='{{ route('production.sewing_returns.create') }}?pickup_id=' + this.value; }">
                                    <option value="">Pilih tanggal ambil...</option>
                                    @foreach ($pickups as $pickup)
                                        @php
                                            $pickupLines = $pickup->lines ?? collect();
                                            $totalRemaining = $pickupLines->sum(function ($line) {
                                                $qtyBundle = (float) ($line->qty_bundle ?? 0);
                                                $returnedOk = (float) ($line->qty_returned_ok ?? 0);
                                                $returnedRej = (float) ($line->qty_returned_reject ?? 0);
                                                return max($qtyBundle - ($returnedOk + $returnedRej), 0);
                                            });
                                        @endphp

                                        @if ($totalRemaining > 0)
                                            <option value="{{ $pickup->id }}"
                                                {{ (int) $selectedPickupId === (int) $pickup->id ? 'selected' : '' }}>
                                                {{ $pickup->operator?->name ?? '(Tanpa operator)' }} —
                                                @php
                                                    try {
                                                        $labelDate = $pickup->date ? id_day($pickup->date) : '-';
                                                    } catch (\Throwable $e) {
                                                        $labelDate = optional($pickup->date)->format('d/m/Y') ?? '-';
                                                    }
                                                @endphp
                                                {{ $labelDate }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('pickup_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                @if ($selectedPickup)
                                    <input type="hidden" name="operator_id" value="{{ $selectedPickup->operator_id }}">
                                @endif
                            </div>
                        </div>

                        @if ($itemCodes->isNotEmpty())
                            <div class="col-md-4 col-12">
                                <div class="field-block">
                                    <div class="field-label">Filter kode item (opsional)</div>
                                    <select class="form-select form-select-sm field-input-sm filter-item-code">
                                        <option value="">Semua item...</option>
                                        @foreach ($itemCodes as $code)
                                            <option value="{{ $code }}">{{ $code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- CLIENT ERROR --}}
            <div id="client-error-box" class="alert alert-warning py-2 small d-none mb-2">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <span id="client-error-text"></span>
            </div>

            {{-- BUNDLES --}}
            <div class="card mb-2">
                <div class="card-section">
                    <div class="return-summary-header mb-2">
                        <div class="return-summary-title">
                            <div class="return-summary-title-icon">
                                <i class="bi bi-clipboard-check"></i>
                            </div>
                            <h2>Daftar ambilan jahit yang perlu disetor</h2>
                        </div>
                        {{-- petunjuk tap card dihilangkan --}}
                        <div class="summary-badges-row">
                            <span class="summary-pill">
                                <span id="summary-row-filled">0</span> baris terisi
                            </span>
                            <span class="summary-pill summary-pill-ok">
                                OK: <span id="summary-ok">0,00</span> pcs
                            </span>
                            <span class="summary-pill summary-pill-reject">
                                Reject: <span id="summary-reject">0,00</span> pcs
                            </span>
                        </div>
                    </div>

                    @error('results')
                        <div class="alert alert-danger py-1 small mb-2">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="table-wrap">
                        <table class="table table-sm align-middle mono table-sewing-return mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40px" class="text-center">#</th>
                                    <th style="width:200px">Tanggal Ambil Jahit</th>
                                    <th style="width:200px">Item</th>
                                    <th style="width:110px" class="text-end">Belum Setor</th>
                                    <th style="width:120px" class="text-center">Setor OK</th>
                                    <th style="width:120px" class="text-center">Reject</th>
                                    <th style="width:200px">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lines as $idx => $line)
                                    @php
                                        /** @var \App\Models\SewingPickupLine $line */
                                        $bundle = $line->bundle;
                                        $pickup = $line->sewingPickup ?? $selectedPickup;
                                        $lot = $bundle?->cuttingJob?->lot;

                                        $qtyBundle = (float) ($line->qty_bundle ?? 0);
                                        $returnedOk = (float) ($line->qty_returned_ok ?? 0);
                                        $returnedRej = (float) ($line->qty_returned_reject ?? 0);
                                        $alreadyReturned = $returnedOk + $returnedRej;
                                        $remaining = max($qtyBundle - $alreadyReturned, 0);

                                        $pickupDateLabel = '';
                                        if ($pickup && $pickup->date) {
                                            try {
                                                $pickupDateLabel = id_day($pickup->date);
                                            } catch (\Throwable $e) {
                                                $pickupDateLabel = optional($pickup->date)->format('d/m/Y') ?? '-';
                                            }
                                        }

                                        $operatorCode = $pickup?->operator?->code ?? '';
                                        $operatorName = $pickup?->operator?->name ?? '';

                                        $oldResult = old('results.' . $idx, []);
                                        $defaultOk = $oldResult['qty_ok'] ?? null;
                                        $defaultReject = $oldResult['qty_reject'] ?? null;
                                        $defaultNotes = $oldResult['notes'] ?? null;

                                        $shouldShowNotes =
                                            ($defaultReject && $defaultReject > 0) ||
                                            ($defaultNotes && $defaultNotes !== '');
                                    @endphp

                                    <tr class="return-row row-empty" data-row-index="{{ $idx }}"
                                        data-remaining="{{ $remaining }}"
                                        data-item-code="{{ $bundle?->finishedItem?->code }}"
                                        data-item-name="{{ $bundle?->finishedItem?->name }}"
                                        data-bundle-code="{{ $bundle?->bundle_code }}"
                                        data-pickup-date="{{ $pickupDateLabel }}" data-operator-code="{{ $operatorCode }}"
                                        data-operator-name="{{ $operatorName }}" data-bundle-qty="{{ $qtyBundle }}"
                                        data-already-returned="{{ $alreadyReturned }}">
                                        <input type="hidden" name="results[{{ $idx }}][sewing_pickup_line_id]"
                                            value="{{ $line->id }}">
                                        <input type="hidden" name="results[{{ $idx }}][bundle_id]"
                                            value="{{ $bundle?->id }}">

                                        <td class="align-top">
                                            <div class="d-none d-md-flex justify-content-center">
                                                <span class="small text-muted">#{{ $loop->iteration }}</span>
                                            </div>
                                            <div class="mobile-row-top d-md-none">
                                                <div class="mobile-top-left">
                                                    <span class="row-index">#{{ $loop->iteration }}</span>
                                                    <span class="item-code">
                                                        {{ $bundle?->finishedItem?->code ?? '-' }}
                                                    </span>
                                                </div>
                                                <div class="mobile-top-right">
                                                    <div class="qty-remaining-label">BELUM SETOR</div>
                                                    <span class="qty-remaining-pill">
                                                        {{ number_format($remaining, 2, ',', '.') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="align-top">
                                            <div class="d-none d-md-block small">
                                                <div class="fw-semibold mb-1">
                                                    {{ $pickupDateLabel ?: '-' }}
                                                </div>
                                                <div class="d-flex flex-wrap align-items-center gap-1 mb-1">
                                                    @if ($pickup && $pickup->operator)
                                                        <span class="badge bg-light border text-muted mono">
                                                            {{ $pickup->operator->code ?? '-' }}
                                                        </span>
                                                    @endif
                                                    @if ($bundle && $bundle->bundle_code)
                                                        <span class="badge bg-light border text-muted mono">
                                                            {{ $bundle->bundle_code }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-muted">
                                                    Pickup: {{ number_format($qtyBundle, 2, ',', '.') }} pcs
                                                    @if ($alreadyReturned > 0)
                                                        • Sudah setor:
                                                        {{ number_format($alreadyReturned, 2, ',', '.') }} pcs
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="d-md-none mobile-muted-soft">
                                                @if ($pickup && $pickup->operator)
                                                    <span class="fw-semibold">
                                                        {{ $pickup->operator->code ?? '-' }}
                                                    </span>
                                                @endif
                                                @if ($pickupDateLabel)
                                                    <span
                                                        class="badge bg-light border text-muted mono">{{ $pickupDateLabel }}</span>
                                                @endif
                                                @if ($bundle && $bundle->bundle_code)
                                                    <span class="badge bg-light border text-muted mono">
                                                        {{ $bundle->bundle_code }}
                                                    </span>
                                                @endif
                                                <div class="mobile-muted-soft mt-1">
                                                    Pickup: {{ number_format($qtyBundle, 2, ',', '.') }} pcs
                                                    @if ($alreadyReturned > 0)
                                                        • Sudah setor:
                                                        {{ number_format($alreadyReturned, 2, ',', '.') }} pcs
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <td class="align-top">
                                            <div class="fw-semibold d-none d-md-block">
                                                {{ $bundle?->finishedItem?->code ?? '-' }}
                                            </div>
                                            <div class="small text-muted d-none d-md-block">
                                                {{ $bundle?->finishedItem?->name ?? '' }}
                                            </div>
                                            @if ($lot)
                                                <div class="small text-muted d-none d-md-block">
                                                    LOT: <span class="mono">{{ $lot->code }}</span>
                                                </div>
                                            @endif

                                            <div class="d-md-none mobile-muted-soft">
                                                {{ $bundle?->finishedItem?->name ?? '' }}
                                                @if ($lot)
                                                    • LOT: <span class="mono">{{ $lot->code }}</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="text-end align-top d-none d-md-table-cell">
                                            <span class="qty-remaining-pill">
                                                {{ number_format($remaining, 2, ',', '.') }}
                                            </span>
                                        </td>

                                        <td class="align-top">
                                            <div class="d-none d-md-block">
                                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                                    name="results[{{ $idx }}][qty_ok]"
                                                    class="form-control form-control-sm qty-input qty-ok-input qty-ok-desktop @error("results.$idx.qty_ok") is-invalid @enderror"
                                                    value="{{ $defaultOk ?? '' }}" placeholder="Jumlah OK">
                                            </div>

                                            <div class="cell-qty-row d-md-none">
                                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                                    name="results[{{ $idx }}][qty_ok]"
                                                    class="form-control form-control-sm qty-input qty-ok-input qty-ok-mobile @error("results.$idx.qty_ok") is-invalid @enderror"
                                                    value="{{ $defaultOk ?? '' }}" placeholder="OK">
                                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                                    name="results[{{ $idx }}][qty_reject]"
                                                    class="form-control form-control-sm qty-input qty-reject-input qty-reject-mobile @error("results.$idx.qty_reject") is-invalid @enderror"
                                                    value="{{ $defaultReject ?? '' }}" placeholder="RJ">
                                            </div>

                                            @error("results.$idx.qty_ok")
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            @error("results.$idx.qty_reject")
                                                <div class="invalid-feedback d-block d-md-none">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        <td class="align-top d-none d-md-table-cell">
                                            <input type="number" step="0.01" min="0" inputmode="decimal"
                                                name="results[{{ $idx }}][qty_reject]"
                                                class="form-control form-control-sm qty-input qty-reject-input qty-reject-desktop @error("results.$idx.qty_reject") is-invalid @enderror"
                                                value="{{ $defaultReject ?? '' }}" placeholder="Qty reject">
                                            @error("results.$idx.qty_reject")
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        <td class="align-top cell-notes">
                                            <div class="notes-wrapper {{ $shouldShowNotes ? '' : 'd-none' }}">
                                                <input type="text" name="results[{{ $idx }}][notes]"
                                                    class="form-control form-control-sm notes-input @error("results.$idx.notes") is-invalid @enderror"
                                                    value="{{ $defaultNotes ?? '' }}"
                                                    placeholder="Catatan reject (opsional)">
                                                @error("results.$idx.notes")
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted small py-3">
                                            @if ($selectedPickupId)
                                                Tidak ada bundles Sewing Pickup (sisa 0 atau belum ada data).
                                            @else
                                                Pilih Sewing Pickup terlebih dahulu untuk melihat bundles.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- SUBMIT --}}
            <div class="d-flex justify-content-between align-items-center mb-4 form-footer">
                <a href="{{ route('production.sewing_returns.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline">Batal</span>
                </a>

                <button type="submit" id="btn-submit-return" class="btn btn-sm btn-primary is-empty" disabled>
                    <span class="btn-icon-badge">
                        <i class="bi bi-check2"></i>
                    </span>
                    <span class="d-flex flex-column text-start">
                        <span class="btn-label-main">Simpan Return</span>
                        <span class="btn-label-sub d-none d-sm-inline" id="btn-submit-return-meta">
                            Belum ada isi
                        </span>
                    </span>
                </button>
            </div>
        </form>
    </div>

    {{-- MODAL KONFIRMASI SEWING RETURN (CLEAN & COMPACT) --}}
    <div class="modal fade" id="confirmReturnModal" tabindex="-1" aria-labelledby="confirmReturnLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-sm modal-md">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="confirmReturnLabel">Konfirmasi Sewing Return</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    {{-- OPERATOR & TANGGAL (HEADER COMPACT) --}}
                    <div class="mb-2">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div>
                                <div class="text-muted small mb-1">
                                    Operator jahit
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-pill text-bg-success mono"
                                        id="confirm-operator-code"></span>
                                    <span class="small fw-semibold" id="confirm-operator-name"></span>
                                </div>
                            </div>
                            <div class="small text-muted text-end">
                                <div>
                                    <span class="text-uppercase" style="letter-spacing:.08em;font-size:.68rem;">Tgl
                                        Ambil</span><br>
                                    <span class="fw-semibold" id="confirm-pickup-label">-</span>
                                </div>
                                <div class="mt-1">
                                    <span class="text-uppercase" style="letter-spacing:.08em;font-size:.68rem;">Tgl
                                        Setor</span><br>
                                    <span class="fw-semibold" id="confirm-return-date">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-2">

                    {{-- langsung summary, tanpa teks petunjuk --}}
                    <div id="confirm-return-summary" class="small pt-1"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="button" id="btn-confirm-return-submit" class="btn btn-sm btn-primary">
                        Ya, Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.return-row');
            const filterItemSelects = document.querySelectorAll('.filter-item-code');
            const clientErrorBox = document.getElementById('client-error-box');
            const clientErrorText = document.getElementById('client-error-text');
            const summaryRowFilled = document.getElementById('summary-row-filled');
            const summaryOk = document.getElementById('summary-ok');
            const summaryReject = document.getElementById('summary-reject');
            const submitBtn = document.getElementById('btn-submit-return');
            const submitMeta = document.getElementById('btn-submit-return-meta');
            const form = document.getElementById('sewing-return-form');

            // Modal konfirmasi
            const confirmModalEl = document.getElementById('confirmReturnModal');
            const confirmSummaryEl = document.getElementById('confirm-return-summary');
            const confirmOperatorCodeEl = document.getElementById('confirm-operator-code');
            const confirmOperatorNameEl = document.getElementById('confirm-operator-name');
            const confirmPickupLabelEl = document.getElementById('confirm-pickup-label');
            const confirmReturnDateEl = document.getElementById('confirm-return-date');
            const confirmBtn = document.getElementById('btn-confirm-return-submit');

            let confirmModal = null;
            if (confirmModalEl && window.bootstrap && bootstrap.Modal) {
                confirmModal = new bootstrap.Modal(confirmModalEl);
            }

            let isConfirmedSubmit = false;

            const isMobile = () => window.innerWidth <= 767;

            function triggerHaptic() {
                if (!('vibrate' in navigator) || !isMobile()) return;
                navigator.vibrate(12);
            }

            function tapGlowOnce(rowEl) {
                if (!isMobile() || !rowEl) return;
                rowEl.classList.add('is-tap');
                setTimeout(() => rowEl.classList.remove('is-tap'), 170);
            }

            let nf;
            try {
                nf = new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            } catch (e) {
                nf = {
                    format: n => (n || 0).toFixed(2)
                };
            }

            const showClientError = msg => {
                if (!clientErrorBox || !clientErrorText) return;
                clientErrorText.textContent = msg;
                clientErrorBox.classList.remove('d-none');
            };

            const hideClientError = () => {
                if (!clientErrorBox || !clientErrorText) return;
                clientErrorText.textContent = '';
                clientErrorBox.classList.add('d-none');
            };

            const parseNum = val => {
                const n = parseFloat(val);
                return isNaN(n) ? 0 : n;
            };

            const escapeHtml = str => {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            function getOk(row) {
                const d = row.querySelector('.qty-ok-desktop');
                const m = row.querySelector('.qty-ok-mobile');
                if (isMobile() && m) return parseNum(m.value);
                if (d && d.value !== '') return parseNum(d.value);
                if (m && m.value !== '') return parseNum(m.value);
                return 0;
            }

            function getReject(row) {
                const d = row.querySelector('.qty-reject-desktop');
                const m = row.querySelector('.qty-reject-mobile');
                if (isMobile() && m) return parseNum(m.value);
                if (d && d.value !== '') return parseNum(d.value);
                if (m && m.value !== '') return parseNum(m.value);
                return 0;
            }

            function setOk(row, value) {
                const d = row.querySelector('.qty-ok-desktop');
                const m = row.querySelector('.qty-ok-mobile');
                const v = value > 0 ? value : '';
                if (d) d.value = v;
                if (m) m.value = v;
            }

            function setReject(row, value) {
                const d = row.querySelector('.qty-reject-desktop');
                const m = row.querySelector('.qty-reject-mobile');
                const v = value > 0 ? value : '';
                if (d) d.value = v;
                if (m) m.value = v;
            }

            function clampToRemaining(row, showError = false) {
                const remaining = parseNum(row.dataset.remaining || '0');
                let ok = getOk(row);
                let reject = getReject(row);

                if (ok < 0) ok = 0;
                if (reject < 0) reject = 0;

                if (ok + reject > remaining) {
                    const diff = ok + reject - remaining;
                    const last = row.dataset.lastChanged || 'ok';
                    if (last === 'reject') {
                        reject = Math.max(0, reject - diff);
                    } else {
                        ok = Math.max(0, ok - diff);
                    }

                    if (showError) {
                        const index = parseInt(row.dataset.rowIndex || '0', 10) + 1;
                        showClientError(
                            `Qty OK + Reject tidak boleh melebihi Belum Setor (baris #${index}). Input sudah disesuaikan.`
                        );
                    }
                } else if (showError) {
                    hideClientError();
                }

                setOk(row, ok);
                setReject(row, reject);
            }

            function updateRowVisual(row) {
                const ok = getOk(row);
                const reject = getReject(row);
                if (ok + reject > 0) {
                    row.classList.add('row-filled');
                    row.classList.remove('row-empty');
                } else {
                    row.classList.remove('row-filled');
                    row.classList.add('row-empty');
                }
            }

            function updateNotesVisibility(row) {
                const reject = getReject(row);
                const notesWrapper = row.querySelector('.notes-wrapper');
                if (!notesWrapper) return;

                if (reject > 0) {
                    notesWrapper.classList.remove('d-none');
                    return;
                }

                const notesInput = notesWrapper.querySelector('input[type="text"]');
                if (!notesInput || notesInput.value === '') {
                    notesWrapper.classList.add('d-none');
                }
            }

            function updateGlobalSummary() {
                if (!summaryRowFilled || !summaryOk || !summaryReject) return;
                let filled = 0,
                    totalOk = 0,
                    totalReject = 0;

                rows.forEach(row => {
                    const ok = getOk(row);
                    const reject = getReject(row);
                    if (ok + reject > 0) filled++;
                    totalOk += ok;
                    totalReject += reject;
                });

                summaryRowFilled.textContent = String(filled);
                summaryOk.textContent = nf.format(totalOk);
                summaryReject.textContent = nf.format(totalReject);

                if (submitBtn) {
                    const hasData = filled > 0;

                    submitBtn.disabled = !hasData;
                    submitBtn.classList.toggle('is-empty', !hasData);

                    if (submitMeta) {
                        submitMeta.textContent = hasData ?
                            `${filled} baris siap disimpan` :
                            'Belum ada baris yang diisi';
                    }
                }
            }

            // filter kode item
            filterItemSelects.forEach(select => {
                select.addEventListener('change', function() {
                    const code = this.value || '';
                    filterItemSelects.forEach(s => {
                        if (s !== select) s.value = code;
                    });

                    rows.forEach(row => {
                        const rowCode = (row.dataset.itemCode || '').trim();
                        row.hidden = code && rowCode !== code;
                    });
                });
            });

            rows.forEach(row => {
                const okDesktop = row.querySelector('.qty-ok-desktop');
                const okMobile = row.querySelector('.qty-ok-mobile');
                const rejectDesktop = row.querySelector('.qty-reject-desktop');
                const rejectMobile = row.querySelector('.qty-reject-mobile');
                const notesWrapper = row.querySelector('.notes-wrapper');
                const itemCodeSpan = row.querySelector('.item-code');

                function scrollToInput(target) {
                    if (!isMobile() || !target) return;
                    const rect = target.getBoundingClientRect();
                    const offset = window.pageYOffset + rect.top - 135;
                    window.scrollTo({
                        top: offset,
                        behavior: 'smooth'
                    });
                }

                function focusOk() {
                    const target = isMobile() ? (okMobile || okDesktop) : (okDesktop || okMobile);
                    if (!target) return;
                    scrollToInput(target);
                    target.focus();
                    target.select();
                    target.classList.add('qty-input-active');
                }

                row.addEventListener('click', function(e) {
                    if (e.target.closest('input, select, textarea, button, a')) return;

                    hideClientError();
                    const remaining = parseNum(row.dataset.remaining || '0');
                    let ok = getOk(row);
                    let reject = getReject(row);

                    if (ok === remaining && reject === 0) {
                        setOk(row, 0);
                        setReject(row, 0);
                        if (notesWrapper) {
                            const ni = notesWrapper.querySelector('input[type="text"]');
                            if (ni) ni.value = '';
                            notesWrapper.classList.add('d-none');
                        }
                    } else {
                        setOk(row, remaining);
                        setReject(row, 0);
                        if (notesWrapper) {
                            const ni = notesWrapper.querySelector('input[type="text"]');
                            if (ni) ni.value = '';
                            notesWrapper.classList.add('d-none');
                        }
                    }

                    row.dataset.lastChanged = 'ok';
                    clampToRemaining(row, false);
                    updateRowVisual(row);
                    updateNotesVisibility(row);
                    updateGlobalSummary();
                    focusOk();
                    triggerHaptic();
                    tapGlowOnce(row);
                });

                if (itemCodeSpan) {
                    itemCodeSpan.style.cursor = 'pointer';
                    itemCodeSpan.addEventListener('click', e => {
                        e.stopPropagation();
                        row.click();
                    });
                }

                [okDesktop, okMobile].forEach(input => {
                    if (!input) return;
                    input.addEventListener('focus', function() {
                        this.select();
                        this.classList.add('qty-input-active');
                        scrollToInput(this);
                    });
                    input.addEventListener('blur', function() {
                        this.classList.remove('qty-input-active');
                        row.dataset.lastChanged = 'ok';
                        clampToRemaining(row, true);
                        updateRowVisual(row);
                        updateGlobalSummary();
                    });
                    input.addEventListener('input', function() {
                        row.dataset.lastChanged = 'ok';
                        clampToRemaining(row, true);
                        updateRowVisual(row);
                        updateGlobalSummary();
                    });
                });

                [rejectDesktop, rejectMobile].forEach(input => {
                    if (!input) return;
                    input.addEventListener('focus', function() {
                        this.select();
                        this.classList.add('qty-input-active');
                        scrollToInput(this);
                    });
                    input.addEventListener('blur', function() {
                        this.classList.remove('qty-input-active');
                        row.dataset.lastChanged = 'reject';
                        clampToRemaining(row, true);
                        updateRowVisual(row);
                        updateNotesVisibility(row);
                        updateGlobalSummary();
                    });
                    input.addEventListener('input', function() {
                        row.dataset.lastChanged = 'reject';
                        clampToRemaining(row, true);
                        updateRowVisual(row);
                        updateNotesVisibility(row);
                        updateGlobalSummary();
                    });
                });

                clampToRemaining(row, false);
                updateRowVisual(row);
                updateNotesVisibility(row);
            });

            updateGlobalSummary();

            // ------- BUILD CONFIRM SUMMARY (MODAL) -------
            function buildConfirmSummary() {
                if (!confirmSummaryEl) return;

                // cari baris pertama yang punya setor
                let firstRow = null;
                rows.forEach(row => {
                    if (!firstRow) {
                        const ok = getOk(row);
                        const reject = getReject(row);
                        if (ok + reject > 0) {
                            firstRow = row;
                        }
                    }
                });
                if (!firstRow && rows.length > 0) {
                    firstRow = rows[0];
                }

                const opCode = firstRow ? (firstRow.dataset.operatorCode || '') : '';
                const opName = firstRow ? (firstRow.dataset.operatorName || '') : '';
                const pickupLabel = firstRow ? (firstRow.dataset.pickupDate || '') : '';

                const dateInput = document.querySelector('input[name="date"]');
                const returnDate = dateInput ? dateInput.value : '';

                if (confirmOperatorCodeEl) {
                    confirmOperatorCodeEl.textContent = opCode || '-';
                    confirmOperatorCodeEl.classList.toggle('d-none', !opCode);
                }
                if (confirmOperatorNameEl) {
                    confirmOperatorNameEl.textContent = opName || '';
                }
                if (confirmPickupLabelEl) {
                    confirmPickupLabelEl.textContent = pickupLabel || '-';
                }
                if (confirmReturnDateEl) {
                    confirmReturnDateEl.textContent = returnDate || '-';
                }

                const lines = [];
                let totalSetor = 0;
                let totalSisa = 0;

                rows.forEach(row => {
                    const ok = getOk(row);
                    const reject = getReject(row);
                    const totalToday = ok + reject;
                    if (totalToday <= 0) return;

                    const bundleQty = parseNum(row.dataset.bundleQty || '0');
                    const alreadyReturned = parseNum(row.dataset.alreadyReturned || '0');
                    const remainingBefore = parseNum(row.dataset.remaining || '0');
                    const remainingAfter = Math.max(remainingBefore - totalToday, 0);

                    const itemCode = row.dataset.itemCode || '-';
                    const itemName = row.dataset.itemName || '';
                    const bundleCode = row.dataset.bundleCode || '';
                    const pickupDate = row.dataset.pickupDate || '';

                    totalSetor += totalToday;
                    totalSisa += remainingAfter;

                    lines.push({
                        itemCode,
                        itemName,
                        bundleCode,
                        pickupDate,
                        bundleQty,
                        alreadyReturned,
                        remainingBefore,
                        totalToday,
                        remainingAfter,
                        ok,
                        reject
                    });
                });

                if (!lines.length) {
                    confirmSummaryEl.innerHTML = `
                        <div class="text-muted">
                            Belum ada baris yang diisi.
                        </div>
                    `;
                    return;
                }

                const headerHtml = `
                    <div class="table-responsive">
                        <table class="table table-sm mb-2" style="font-size:.78rem;">
                            <thead>
                                <tr class="text-muted">
                                    <th style="width:26px;">#</th>
                                    <th style="width:90px;">Kode</th>
                                    <th>Riwayat setor jahit</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                const dateLabel = returnDate || '-';

                const bodyHtml = lines.map((line, idx) => {
                    const kodeLabel = escapeHtml(line.itemCode || '-');
                    const pickupInfo = line.pickupDate ?
                        `Ambil: ${escapeHtml(line.pickupDate)}` :
                        '';

                    const bundleInfo = line.bundleCode ?
                        `Bundle: ${escapeHtml(line.bundleCode)}` :
                        '';

                    const pickupText = nf.format(line.bundleQty) + ' pcs';
                    const prevReturnedText = line.alreadyReturned > 0 ?
                        nf.format(line.alreadyReturned) + ' pcs' :
                        '0,00 pcs';

                    const belumSebelumText = nf.format(line.remainingBefore) + ' pcs';
                    const todayText = nf.format(line.totalToday) + ' pcs';

                    const okRejectDetail = line.reject > 0 ?
                        `OK ${nf.format(line.ok)} • RJ ${nf.format(line.reject)}` :
                        `OK ${nf.format(line.ok)}`;

                    const sisaText = nf.format(line.remainingAfter) + ' pcs';

                    return `
                        <tr>
                            <td class="text-muted mono">#${idx + 1}</td>
                            <td class="mono fw-semibold">
                                ${kodeLabel}
                            </td>
                            <td>

                                <div class="mono">
                                    Pickup: <strong>${pickupText}</strong>
                                </div>
                                <div class="mono" style="font-size:.74rem;">
                                    Sudah setor : ${prevReturnedText}
                                </div>

                                <div class="mono mt-1" style="font-size:.74rem;">
                                    Setor hari ini :
                                    <span class="text-muted">(${okRejectDetail})</span>
                                </div>
                                <div class="mono mt-1 ${line.remainingAfter > 0 ? 'text-warning' : 'text-success'}"
                                    style="font-size:.8rem;">
                                    Sisa belum setor: <strong>${sisaText}</strong>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');

                const footerHtml = `
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center text-muted"
                        style="font-size:.72rem;">
                        <div>
                            <span>${lines.length} baris setor</span>
                        </div>
                        <div class="text-end mono">
                            <div>Total setor hari ini: <strong>${nf.format(totalSetor)}</strong> pcs</div>
                            <div>Sisa belum setor: <strong>${nf.format(totalSisa)}</strong> pcs</div>
                        </div>
                    </div>
                `;

                confirmSummaryEl.innerHTML = headerHtml + bodyHtml + footerHtml;
            }

            // Intersep submit untuk munculkan modal konfirmasi
            if (form && confirmModal && confirmBtn) {
                form.addEventListener('submit', function(e) {
                    if (isConfirmedSubmit) {
                        isConfirmedSubmit = false;
                        return;
                    }

                    e.preventDefault();

                    let hasData = false;
                    rows.forEach(row => {
                        const ok = getOk(row);
                        const reject = getReject(row);
                        if (ok + reject > 0) {
                            hasData = true;
                        }
                    });
                    if (!hasData) {
                        return;
                    }

                    buildConfirmSummary();
                    confirmModal.show();
                });

                confirmBtn.addEventListener('click', function() {
                    isConfirmedSubmit = true;

                    if (submitBtn && submitMeta) {
                        submitBtn.disabled = true;
                        submitMeta.textContent = 'Menyimpan...';
                    }

                    if (confirmModal) {
                        confirmModal.hide();
                    }

                    form.submit();
                });
            }
        });
    </script>
@endpush
