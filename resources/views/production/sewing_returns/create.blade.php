{{-- resources/views/production/sewing_returns/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Return')

@push('head')
    <style>
        :root {
            --r: 14px;
            --b: rgba(148, 163, 184, .22);
            --shadow: 0 10px 26px rgba(15, 23, 42, .08), 0 0 0 1px rgba(15, 23, 42, .03);
            --muted2: rgba(100, 116, 139, .9);

            --ok: rgba(22, 163, 74, 1);
            --okbg: rgba(22, 163, 74, .10);
            --rj: rgba(220, 38, 38, 1);
            --rjbg: rgba(248, 113, 113, .12);
            --warn: rgba(245, 158, 11, 1);
            --warnbg: rgba(245, 158, 11, .14);
        }

        .page-wrap {
            max-width: 1000px;
            margin-inline: auto;
            padding: .75rem .75rem 6.25rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(16, 185, 129, .10) 0,
                    rgba(240, 253, 250, .34) 18%,
                    #f9fafb 55%);
        }

        .card {
            background: var(--card);
            border-radius: var(--r);
            border: 1px solid var(--b);
            box-shadow: var(--shadow);
        }

        .card-section {
            padding: .85rem .9rem;
        }

        @media(min-width:768px) {
            .card-section {
                padding: 1rem 1.15rem;
            }

            .page-wrap {
                padding-bottom: 4rem;
            }
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .hdr {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .hdr h1 {
            font-size: 1.02rem;
            font-weight: 900;
            margin: 0;
            letter-spacing: -.01em;
        }

        .sub {
            font-size: .8rem;
            color: var(--muted);
            line-height: 1.35;
            margin-top: .15rem;
        }

        .lbl {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .10em;
            font-weight: 900;
            color: var(--muted);
        }

        .pill {
            border-radius: 999px;
            padding: .18rem .65rem;
            font-size: .72rem;
            font-weight: 900;
            background: rgba(148, 163, 184, .10);
            border: 1px solid rgba(148, 163, 184, .18);
            display: inline-flex;
            gap: .35rem;
            align-items: center;
        }

        .pill.ok {
            background: var(--okbg);
            border-color: rgba(22, 163, 74, .26);
            color: #166534;
        }

        .pill.rj {
            background: var(--rjbg);
            border-color: rgba(248, 113, 113, .22);
            color: #b91c1c;
        }

        .chip {
            border-radius: 999px;
            padding: .08rem .55rem;
            font-size: .72rem;
            font-weight: 950;
            line-height: 1.05;
            border: 1px solid rgba(148, 163, 184, .35);
            background: rgba(148, 163, 184, .06);
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .chip.belum {
            background: rgba(22, 163, 74, .08);
            border-color: rgba(22, 163, 74, .30);
            color: #15803d;
        }

        .chip.dp {
            background: var(--warnbg);
            border-color: rgba(245, 158, 11, .35);
            color: rgba(146, 64, 14, 1);
        }

        .table-wrap {
            overflow: auto;
            border-radius: var(--r);
            border: 1px solid var(--b);
        }

        .table {
            margin: 0;
        }

        .table thead th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .10em;
            color: var(--muted);
            font-weight: 900;
            background: rgba(148, 163, 184, .06);
            border-bottom: 1px solid var(--b) !important;
            padding: .6rem .65rem;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .table tbody td {
            padding: .55rem .65rem;
            border-color: rgba(148, 163, 184, .16) !important;
            vertical-align: top;
        }

        .return-row {
            transition: background .15s ease, box-shadow .15s ease;
        }

        .return-row.row-empty {
            background: rgba(255, 255, 255, .98);
            box-shadow: inset 3px 0 0 rgba(148, 163, 184, .18);
        }

        .return-row.row-filled {
            background: radial-gradient(circle at top left,
                    rgba(34, 197, 94, .14) 0,
                    rgba(240, 253, 244, .96) 55%);
            box-shadow:
                inset 3px 0 0 rgba(22, 163, 74, .95),
                0 0 0 1px rgba(187, 247, 208, .86);
        }

        .item-title {
            font-weight: 950;
            font-size: .95rem;
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .item-badge {
            display: inline-flex;
            align-items: center;
            padding: .22rem .7rem;
            border-radius: 999px;
            font-size: 1.05rem;
            font-weight: 950;
            white-space: nowrap;
            background: rgba(22, 163, 74, .07);
            color: #166534;
            border: 1px solid rgba(22, 163, 74, .24);
        }

        .mini {
            color: var(--muted2);
            font-size: .78rem;
            line-height: 1.25;
            margin-top: .1rem;
        }

        .mini-name {
            font-weight: 500;
        }

        .qty-input {
            font-weight: 750;
            font-size: .84rem;
            text-align: center;
        }

        .qty-input-active {
            border-color: rgba(22, 163, 74, .60) !important;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, .12) !important;
            background: rgba(240, 253, 244, .96) !important;
        }

        .notes-input {
            font-size: .78rem;
        }

        .stack {
            display: flex;
            flex-direction: column;
            gap: .2rem;
        }

        /* Mobile card rows */
        @media(max-width:767.98px) {
            .table thead {
                display: none;
            }

            .table-wrap {
                border: none;
                overflow: visible;
            }

            .table tbody tr {
                display: block;
                border-radius: var(--r);
                border: 1px solid rgba(148, 163, 184, .22);
                padding: .6rem .75rem .7rem;
                margin-bottom: .55rem;
                box-shadow: var(--shadow);
            }

            .table tbody td {
                display: block;
                border: none !important;
                padding: 0;
            }

            .m-top {
                display: flex;
                justify-content: space-between;
                gap: .75rem;
                align-items: flex-start;
            }

            .qbox {
                text-align: right;
                flex: 0 0 auto;
            }

            .qinline {
                display: inline-flex;
                gap: .25rem;
                flex-wrap: wrap;
                justify-content: flex-end;
                margin-top: .35rem;
            }

            .cell-qty {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: .45rem;
                margin-top: .55rem;
            }

            .notes-wrapper {
                margin-top: .45rem;
            }

            /* Floating 2 buttons (Batal + Simpan) kanan bawah */
            .form-footer {
                position: fixed;
                right: 1rem;
                bottom: 5.2rem;
                left: auto;
                z-index: 50;
                padding: 0;
                background: transparent;
                border: none;
                box-shadow: none;
                justify-content: flex-end !important;
                pointer-events: none;
            }

            .floating-actions {
                display: flex;
                gap: .5rem;
                pointer-events: all;
            }

            .btn-floating-cancel,
            .btn-floating-submit {
                border-radius: 999px !important;
                box-shadow: 0 14px 35px rgba(15, 23, 42, .28);
            }

            .btn-floating-cancel {
                width: 40px;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .btn-floating-submit {
                padding-inline: 1.1rem;
                font-size: .82rem;
                font-weight: 600;
            }

            body[data-theme="light"] .btn-floating-submit {
                background: rgba(22, 163, 74, 1);
                color: #fff;
                border: 1px solid rgba(22, 163, 74, .45);
            }

            body[data-theme="dark"] .btn-floating-submit {
                background: rgba(16, 185, 129, .95);
                color: #fff;
                border: 1px solid rgba(45, 212, 191, .45);
            }

            .btn-floating-meta {
                opacity: .8;
                font-weight: 500;
                margin-left: .25rem;
            }
        }

        .toastish {
            border-radius: var(--r);
            border: 1px solid rgba(245, 158, 11, .30);
            background: rgba(245, 158, 11, .12);
            color: rgba(146, 64, 14, 1);
        }

        /* ===== Submit modal (center + mobile safe) ===== */
        .submit-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, .55);
            backdrop-filter: blur(4px);
            z-index: 1100;
            display: none;
        }

        .submit-modal-backdrop.show {
            display: block;
        }

        .submit-modal-sheet {
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: min(560px, calc(100vw - 1.5rem));
            z-index: 1110;
            display: none;
        }

        .submit-modal-sheet.show {
            display: block;
        }

        .submit-modal-card {
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, .22);
            box-shadow: 0 25px 70px rgba(15, 23, 42, .28);
            background: var(--card);
            overflow: hidden;
        }

        @media (max-width: 767.98px) {
            .submit-modal-sheet {
                width: min(560px, calc(100vw - 1.25rem));
                padding: 0;
            }

            .submit-modal-card {
                max-height: calc(100vh - 7.5rem);
                overflow: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        .submit-modal-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .85rem 1rem .75rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            gap: .75rem;
        }

        .submit-title {
            display: flex;
            align-items: center;
            gap: .6rem;
            font-weight: 950;
            letter-spacing: -.01em;
            margin: 0;
            font-size: 1rem;
        }

        .submit-x {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .25);
            background: rgba(148, 163, 184, .10);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .submit-modal-body {
            padding: .85rem 1rem .9rem;
        }

        .submit-kpis {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .5rem;
            margin-top: .5rem;
        }

        .kpi {
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .20);
            background: rgba(148, 163, 184, .06);
            padding: .65rem .75rem;
        }

        .kpi .k-label {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .10em;
            font-weight: 900;
            color: var(--muted);
            margin-bottom: .2rem;
        }

        .kpi .k-value {
            font-weight: 950;
            font-size: 1.05rem;
        }

        .submit-modal-actions {
            display: flex;
            gap: .5rem;
            padding: .75rem 1rem 1rem;
            border-top: 1px solid rgba(148, 163, 184, .18);
        }

        .submit-btn {
            border-radius: 999px !important;
            font-weight: 800;
            padding: .7rem 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            flex: 1 1 auto;
        }

        .submit-btn.secondary {
            background: rgba(148, 163, 184, .10);
            border: 1px solid rgba(148, 163, 184, .25);
        }

        .submit-btn.primary {
            background: rgba(22, 163, 74, 1);
            border: 1px solid rgba(22, 163, 74, .45);
            color: #fff;
        }

        .submit-btn.primary:disabled {
            opacity: .7;
        }

        .submit-spinner {
            width: 16px;
            height: 16px;
            border-radius: 999px;
            border: 2px solid rgba(255, 255, 255, .55);
            border-top-color: rgba(255, 255, 255, 1);
            animation: spin .8s linear infinite;
            display: none;
        }

        .submit-btn.primary.is-loading .submit-spinner {
            display: inline-block;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ===== Modal item details ===== */
        .modal-items {
            margin-top: .75rem;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .20);
            overflow: hidden;
        }

        .modal-items-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .55rem .75rem;
            background: rgba(148, 163, 184, .06);
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .10em;
            font-weight: 900;
            color: var(--muted);
        }

        .modal-items-body {
            max-height: 36vh;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
        }

        .modal-item-row {
            display: grid;
            grid-template-columns: 1.4fr .8fr;
            gap: .6rem;
            padding: .6rem .75rem;
            border-bottom: 1px solid rgba(148, 163, 184, .14);
            background: rgba(255, 255, 255, .02);
        }

        .modal-item-row:last-child {
            border-bottom: none;
        }

        .modal-item-title {
            font-weight: 950;
            line-height: 1.15;
        }

        .modal-item-sub {
            color: var(--muted2);
            font-size: .78rem;
            margin-top: .12rem;
            line-height: 1.2;
        }

        .modal-item-metrics {
            display: flex;
            flex-direction: column;
            gap: .25rem;
            align-items: flex-end;
            text-align: right;
        }

        .mchip {
            border-radius: 999px;
            padding: .10rem .55rem;
            font-size: .72rem;
            font-weight: 950;
            border: 1px solid rgba(148, 163, 184, .28);
            background: rgba(148, 163, 184, .06);
            display: inline-flex;
            gap: .35rem;
            align-items: center;
        }

        .mchip.ok {
            background: var(--okbg);
            border-color: rgba(22, 163, 74, .30);
            color: #166534;
        }

        .mchip.rj {
            background: var(--rjbg);
            border-color: rgba(248, 113, 113, .25);
            color: #b91c1c;
        }

        .mchip.sisa {
            background: rgba(59, 130, 246, .10);
            border-color: rgba(59, 130, 246, .25);
            color: rgba(30, 64, 175, 1);
        }

        .modal-empty {
            padding: .75rem;
            color: var(--muted);
            text-align: center;
            font-size: .85rem;
        }
    </style>
@endpush

@section('content')
    @php
        $selectedPickupId = old('pickup_id', $pickupId ?? null);
        $defaultDate = old('date', optional($selectedPickup?->date)->format('Y-m-d') ?? now()->format('Y-m-d'));

        $itemCodes = ($lines ?? collect())
            ->map(fn($l) => optional(optional($l->bundle)->finishedItem)->code)
            ->filter()
            ->unique()
            ->values();

        $wipMap = $wipStockByItemId ?? [];

        $fmtDay = function ($d) {
            if (!$d) {
                return '-';
            }
            try {
                return function_exists('id_day') ? id_day($d) : \Illuminate\Support\Carbon::parse($d)->format('d/m/Y');
            } catch (\Throwable $e) {
                return '-';
            }
        };

        // Helper: remaining pickup line (sinkron dengan controller)
        $calcRemaining = function ($line) {
            $qtyBundle = (float) ($line->qty_bundle ?? 0);
            $returnedOk = (float) ($line->qty_returned_ok ?? 0);
            $returnedRej = (float) ($line->qty_returned_reject ?? 0);
            $directPick = (float) ($line->qty_direct_picked ?? 0);
            $progressAdj = (float) ($line->qty_progress_adjusted ?? 0);
            return max($qtyBundle - ($returnedOk + $returnedRej + $directPick + $progressAdj), 0);
        };
    @endphp

    <div class="page-wrap">

        {{-- Header --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="hdr">
                    <div>
                        <h1>Setor Jahit</h1>
                        <div class="sub"></div>
                    </div>

                    <a href="{{ route('production.sewing.pickups.create') }}"
                        class="btn btn-sm btn-outline-success rounded-pill d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam"></i><span>Ambil Jahit</span>
                    </a>
                </div>
            </div>
        </div>

        <form id="sewing-return-form" action="{{ route('production.sewing.returns.store') }}" method="POST" novalidate>
            @csrf

            {{-- Form header --}}
            <div class="card mb-2">
                <div class="card-section">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3 col-6">
                            <div class="lbl mb-1">Tanggal</div>
                            <input type="date" name="date"
                                class="form-control form-control-sm @error('date') is-invalid @enderror"
                                value="{{ $defaultDate }}">
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-5 col-12">
                            <div class="lbl mb-1">Pickup</div>
                            <select name="pickup_id"
                                class="form-select form-select-sm @error('pickup_id') is-invalid @enderror"
                                onchange="if(this.value){ window.location='{{ route('production.sewing.returns.create') }}?pickup_id=' + this.value; }">
                                <option value="">Pilih pickup...</option>

                                @foreach ($pickups as $pickup)
                                    @php
                                        if (!empty($pickup->voided_at)) {
                                            continue;
                                        }

                                        $pickupLines = $pickup->lines ?? collect();
                                        $totalRemaining = $pickupLines->sum(fn($line) => $calcRemaining($line));

                                        $pickupLabelDate = $fmtDay($pickup->date);
                                        $opName = $pickup->operator?->name ?? '(Tanpa operator)';
                                    @endphp

                                    @if ($totalRemaining > 0.000001)
                                        <option value="{{ $pickup->id }}"
                                            {{ (int) $selectedPickupId === (int) $pickup->id ? 'selected' : '' }}>
                                            {{ $opName }} — {{ $pickupLabelDate }}
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

                        @if ($itemCodes->isNotEmpty())
                            <div class="col-md-4 col-12">
                                <div class="lbl mb-1">Item</div>
                                <select class="form-select form-select-sm filter-item-code">
                                    <option value="">Semua item</option>
                                    @foreach ($itemCodes as $code)
                                        <option value="{{ $code }}">{{ $code }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="pill"><i class="bi bi-list-check"></i> <span id="summary-row-filled">0</span>
                            baris</span>
                        <span class="pill ok"><i class="bi bi-check-circle"></i> OK: <span
                                id="summary-ok">0,00</span></span>
                        <span class="pill rj"><i class="bi bi-x-circle"></i> RJ: <span
                                id="summary-reject">0,00</span></span>
                    </div>

                    <div id="client-error-box" class="toastish py-2 px-3 small d-none mt-2">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <span id="client-error-text"></span>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card mb-2">
                <div class="card-section">
                    @error('results')
                        <div class="alert alert-danger py-1 small mb-2">{{ $message }}</div>
                    @enderror

                    <div class="table-wrap">
                        <table class="table table-sm align-middle mono">
                            <thead>
                                <tr>
                                    <th style="width:52px" class="text-center">#</th>
                                    <th>Pickup</th>
                                    <th>Item</th>
                                    <th style="width:220px">Qty</th>
                                    <th style="width:130px" class="text-center">OK</th>
                                    <th style="width:130px" class="text-center">RJ</th>
                                    <th style="width:240px">Catatan</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($lines as $idx => $line)
                                    @php
                                        /** @var \App\Models\SewingPickupLine $line */
                                        $bundle = $line->bundle;
                                        $pickup = $line->sewingPickup ?? $selectedPickup;
                                        $lot = $bundle?->cuttingJob?->lot;

                                        $remainingPickup = (float) ($line->remaining_qty ?? 0);

                                        $itemId = (int) ($bundle?->finishedItem?->id ?? 0);
                                        $wipStock = (float) ($line->wip_stock ?? ($wipMap[$itemId] ?? 0));

                                        $directPicked = (float) ($line->qty_direct_picked ?? 0);
                                        $hasDirect = $directPicked > 0.000001;

                                        $pickupDateLabel = $pickup?->date ? $fmtDay($pickup->date) : '-';
                                        $pickupCode = $pickup?->code ?? '';
                                        $operatorCode = $pickup?->operator?->code ?? '';
                                        $operatorName = $pickup?->operator?->name ?? '';

                                        $oldResult = old('results.' . $idx, []);
                                        $defaultOk = $oldResult['qty_ok'] ?? null;
                                        $defaultReject = $oldResult['qty_reject'] ?? null;
                                        $defaultNotes = $oldResult['notes'] ?? null;

                                        $showNotes =
                                            (float) ($defaultReject ?? 0) > 0.000001 ||
                                            (is_string($defaultNotes) && trim($defaultNotes) !== '');
                                    @endphp

                                    <tr class="return-row row-empty" data-row-index="{{ $idx }}"
                                        data-remaining="{{ $remainingPickup }}" data-item-id="{{ $itemId }}"
                                        data-wip-stock="{{ $wipStock }}"
                                        data-item-code="{{ $bundle?->finishedItem?->code }}"
                                        data-item-name="{{ $bundle?->finishedItem?->name }}"
                                        data-operator-code="{{ $operatorCode }}" data-operator-name="{{ $operatorName }}"
                                        data-pickup-date="{{ $pickupDateLabel }}"
                                        data-direct-picked="{{ $directPicked }}">

                                        <input type="hidden" name="results[{{ $idx }}][sewing_pickup_line_id]"
                                            value="{{ $line->id }}">
                                        <input type="hidden" name="results[{{ $idx }}][bundle_id]"
                                            value="{{ $bundle?->id }}">

                                        {{-- # / Mobile header --}}
                                        <td class="align-top">
                                            <div class="d-none d-md-flex justify-content-center">
                                                <span class="small text-muted">#{{ $loop->iteration }}</span>
                                            </div>

                                            <div class="m-top d-md-none">
                                                <div class="stack">
                                                    <div class="item-title">
                                                        <span
                                                            class="item-badge">{{ $bundle?->finishedItem?->code ?? '-' }}</span>
                                                    </div>

                                                    @if ($bundle?->finishedItem?->name)
                                                        <div class="mini mini-name">{{ $bundle->finishedItem->name }}</div>
                                                    @endif

                                                    <div class="mini">{{ $pickupDateLabel ?: '-' }}</div>
                                                </div>

                                                <div class="qbox">
                                                    <div class="qinline">
                                                        <span class="chip belum">
                                                            Belum <span
                                                                class="mono">{{ number_format($remainingPickup, 2, ',', '.') }}</span>
                                                        </span>
                                                        @if ($hasDirect)
                                                            <span class="chip dp">
                                                                DP <span
                                                                    class="mono">{{ number_format($directPicked, 2, ',', '.') }}</span>
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Pickup (desktop) --}}
                                        <td class="align-top d-none d-md-table-cell">
                                            <div class="fw-semibold">
                                                @if ($pickupCode)
                                                    <span class="mono">{{ $pickupCode }}</span>
                                                    @if ($pickupDateLabel && $pickupDateLabel !== '-')
                                                        • {{ $pickupDateLabel }}
                                                    @endif
                                                @else
                                                    {{ $pickupDateLabel ?: '-' }}
                                                @endif
                                            </div>
                                            @if ($operatorCode)
                                                <div class="text-muted small">{{ $operatorCode }}</div>
                                            @endif
                                        </td>

                                        {{-- Item (desktop) --}}
                                        <td class="align-top d-none d-md-table-cell">
                                            <div class="fw-semibold">{{ $bundle?->finishedItem?->code ?? '-' }}</div>
                                            @if ($bundle?->finishedItem?->name)
                                                <div class="text-muted small">{{ $bundle->finishedItem->name }}</div>
                                            @endif
                                            @if ($lot)
                                                <div class="text-muted small">LOT: <span
                                                        class="mono">{{ $lot->code }}</span></div>
                                            @endif
                                        </td>

                                        {{-- Qty chips (desktop) --}}
                                        <td class="align-top d-none d-md-table-cell">
                                            <div class="d-flex flex-wrap gap-1">
                                                <span class="chip belum">
                                                    Belum <span
                                                        class="mono">{{ number_format($remainingPickup, 2, ',', '.') }}</span>
                                                </span>
                                                @if ($hasDirect)
                                                    <span class="chip dp">
                                                        DP <span
                                                            class="mono">{{ number_format($directPicked, 2, ',', '.') }}</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Inputs --}}
                                        <td class="align-top">
                                            <div class="d-none d-md-block">
                                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                                    name="results[{{ $idx }}][qty_ok]"
                                                    class="form-control form-control-sm qty-input qty-ok-desktop @error("results.$idx.qty_ok") is-invalid @enderror"
                                                    value="{{ $defaultOk ?? '' }}" placeholder="OK">
                                            </div>

                                            <div class="cell-qty d-md-none">
                                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                                    name="results[{{ $idx }}][qty_ok]"
                                                    class="form-control form-control-sm qty-input qty-ok-mobile @error("results.$idx.qty_ok") is-invalid @enderror"
                                                    value="{{ $defaultOk ?? '' }}" placeholder="OK">

                                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                                    name="results[{{ $idx }}][qty_reject]"
                                                    class="form-control form-control-sm qty-input qty-reject-mobile @error("results.$idx.qty_reject") is-invalid @enderror"
                                                    value="{{ $defaultReject ?? '' }}" placeholder="RJ">
                                            </div>

                                            @error("results.$idx.qty_ok")
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            @error("results.$idx.qty_reject")
                                                <div class="invalid-feedback d-block d-md-none">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        {{-- RJ desktop --}}
                                        <td class="align-top d-none d-md-table-cell">
                                            <input type="number" step="0.01" min="0" inputmode="decimal"
                                                name="results[{{ $idx }}][qty_reject]"
                                                class="form-control form-control-sm qty-input qty-reject-desktop @error("results.$idx.qty_reject") is-invalid @enderror"
                                                value="{{ $defaultReject ?? '' }}" placeholder="RJ">
                                            @error("results.$idx.qty_reject")
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        {{-- Notes --}}
                                        <td class="align-top">
                                            <div class="notes-wrapper {{ $showNotes ? '' : 'd-none' }}">
                                                <input type="text" name="results[{{ $idx }}][notes]"
                                                    class="form-control form-control-sm notes-input @error("results.$idx.notes") is-invalid @enderror"
                                                    value="{{ $defaultNotes ?? '' }}" placeholder="Catatan (opsional)">
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
                                                Tidak ada baris yang bisa disetor.
                                            @else
                                                Pilih pickup dulu.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Footer floating --}}
            <div class="form-footer d-flex align-items-center">
                <div class="floating-actions">
                    <a href="{{ route('production.sewing.returns.index') }}"
                        class="btn btn-outline-secondary btn-sm btn-floating-cancel">
                        <i class="bi bi-arrow-left"></i>
                    </a>

                    <button type="button" id="btn-submit-return" class="btn btn-success btn-sm btn-floating-submit"
                        disabled>
                        <i class="bi bi-check2 me-1"></i> Simpan
                        <span class="btn-floating-meta" id="btn-submit-return-meta">Belum ada isi</span>
                    </button>
                </div>
            </div>

            {{-- ===== Friendly Submit Modal (dengan detail item + sisa) ===== --}}
            <div id="submit-modal-backdrop" class="submit-modal-backdrop" aria-hidden="true"></div>

            <div id="submit-modal" class="submit-modal-sheet" role="dialog" aria-modal="true" aria-hidden="true">
                <div class="submit-modal-card">
                    <div class="submit-modal-topbar">
                        <h3 class="submit-title">
                            <span class="pill ok" style="margin:0"><i class="bi bi-check2-circle"></i></span>
                            Konfirmasi Simpan
                        </h3>

                        <button type="button" class="submit-x" id="btn-close-submit-modal" aria-label="Tutup">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="submit-modal-body">
                        <div class="d-flex flex-wrap gap-2">
                            <span class="pill"><i class="bi bi-list-check"></i> <span id="modal-row-filled">0</span>
                                baris</span>
                            <span class="pill ok"><i class="bi bi-check-circle"></i> OK: <span
                                    id="modal-ok">0,00</span></span>
                            <span class="pill rj"><i class="bi bi-x-circle"></i> RJ: <span
                                    id="modal-rj">0,00</span></span>
                        </div>

                        <div class="submit-kpis">
                            <div class="kpi">
                                <div class="k-label">Tanggal</div>
                                <div class="k-value mono" id="modal-date">-</div>
                            </div>
                            <div class="kpi">
                                <div class="k-label">Pickup</div>
                                <div class="k-value" id="modal-pickup">-</div>
                            </div>
                        </div>

                        {{-- detail barang yang disetor + sisa --}}
                        <div class="modal-items" id="modal-items">
                            <div class="modal-items-head">
                                <span>Detail Setor</span>
                                <span class="mono" id="modal-items-count">0</span>
                            </div>
                            <div class="modal-items-body" id="modal-items-body">
                                <div class="modal-empty">Tidak ada item.</div>
                            </div>
                        </div>
                    </div>

                    <div class="submit-modal-actions">
                        <button type="button" class="btn submit-btn secondary" id="btn-cancel-submit">
                            <i class="bi bi-arrow-left"></i> Batal
                        </button>
                        <button type="button" class="btn submit-btn primary" id="btn-confirm-submit">
                            <span class="submit-spinner" aria-hidden="true"></span>
                            <i class="bi bi-check2"></i> Simpan
                        </button>
                    </div>
                </div>
            </div>
            {{-- ===== /Modal ===== --}}
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('sewing-return-form');

            const rows = Array.from(document.querySelectorAll('.return-row'));
            const filterSelects = Array.from(document.querySelectorAll('.filter-item-code'));

            const clientErrorBox = document.getElementById('client-error-box');
            const clientErrorText = document.getElementById('client-error-text');

            const summaryRowFilled = document.getElementById('summary-row-filled');
            const summaryOk = document.getElementById('summary-ok');
            const summaryReject = document.getElementById('summary-reject');

            const submitBtn = document.getElementById('btn-submit-return');
            const submitMeta = document.getElementById('btn-submit-return-meta');

            // modal
            const modalBackdrop = document.getElementById('submit-modal-backdrop');
            const modalSheet = document.getElementById('submit-modal');
            const closeModalBtn = document.getElementById('btn-close-submit-modal');
            const cancelModalBtn = document.getElementById('btn-cancel-submit');
            const confirmModalBtn = document.getElementById('btn-confirm-submit');

            const modalRowFilled = document.getElementById('modal-row-filled');
            const modalOk = document.getElementById('modal-ok');
            const modalRj = document.getElementById('modal-rj');
            const modalDate = document.getElementById('modal-date');
            const modalPickup = document.getElementById('modal-pickup');

            const modalItemsBody = document.getElementById('modal-items-body');
            const modalItemsCount = document.getElementById('modal-items-count');

            const dateInput = form?.querySelector('input[name="date"]');
            const pickupSelect = form?.querySelector('select[name="pickup_id"]');

            const isMobile = () => window.innerWidth <= 767;

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

            const parseNum = (val) => {
                const n = parseFloat(val);
                return isNaN(n) ? 0 : n;
            };

            function escapeHtml(s) {
                return String(s ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            let errTimer = null;
            const showClientError = (msg) => {
                if (!clientErrorBox || !clientErrorText) return;
                clientErrorText.textContent = msg;
                clientErrorBox.classList.remove('d-none');
                if (errTimer) clearTimeout(errTimer);
                errTimer = setTimeout(() => {
                    clientErrorText.textContent = '';
                    clientErrorBox.classList.add('d-none');
                }, 2200);
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

            function rowTotal(row) {
                return getOk(row) + getReject(row);
            }

            function itemId(row) {
                const id = parseInt(row.dataset.itemId || '0', 10);
                return isNaN(id) ? 0 : id;
            }

            function itemWip(row) {
                const v = parseNum(row.dataset.wipStock || '0');
                return v < 0 ? 0 : v;
            }

            function sumUsedOtherRows(itId, excludeRow) {
                let used = 0;
                rows.forEach(r => {
                    if (r === excludeRow) return;
                    if (itemId(r) !== itId) return;
                    used += rowTotal(r);
                });
                return used;
            }

            function clamp(row, showError = false) {
                const remainingPickup = parseNum(row.dataset.remaining || '0');
                let ok = getOk(row);
                let rj = getReject(row);

                if (ok < 0) ok = 0;
                if (rj < 0) rj = 0;

                if (ok + rj > remainingPickup + 0.000001) {
                    const diff = (ok + rj) - remainingPickup;
                    const last = row.dataset.lastChanged || 'ok';
                    if (last === 'reject') rj = Math.max(0, rj - diff);
                    else ok = Math.max(0, ok - diff);

                    if (showError) {
                        const idx = parseInt(row.dataset.rowIndex || '0', 10) + 1;
                        showClientError(`Baris #${idx}: OK+RJ melebihi Belum. Disesuaikan.`);
                    }
                }

                const itId = itemId(row);
                if (itId > 0) {
                    const wip = itemWip(row);
                    const usedOther = sumUsedOtherRows(itId, row);
                    const available = Math.max(wip - usedOther, 0);

                    if (ok + rj > available + 0.000001) {
                        const diff2 = (ok + rj) - available;
                        const last2 = row.dataset.lastChanged || 'ok';
                        if (last2 === 'reject') rj = Math.max(0, rj - diff2);
                        else ok = Math.max(0, ok - diff2);

                        if (showError) {
                            const code = (row.dataset.itemCode || '').trim() || `Item#${itId}`;
                            showClientError(`Stok WIP-SEW ${code} sisa ${nf.format(available)}. Disesuaikan.`);
                        }
                    }
                }

                setOk(row, ok);
                setReject(row, rj);
            }

            function updateRowVisual(row) {
                if (rowTotal(row) > 0) {
                    row.classList.add('row-filled');
                    row.classList.remove('row-empty');
                } else {
                    row.classList.remove('row-filled');
                    row.classList.add('row-empty');
                }
            }

            function updateNotesVisibility(row) {
                const rj = getReject(row);
                const wrap = row.querySelector('.notes-wrapper');
                if (!wrap) return;

                if (rj > 0) {
                    wrap.classList.remove('d-none');
                    return;
                }
                const input = wrap.querySelector('input[type="text"]');
                if (!input || input.value.trim() === '') wrap.classList.add('d-none');
            }

            function computeSummary() {
                let filled = 0,
                    okSum = 0,
                    rjSum = 0;

                rows.forEach(row => {
                    const ok = getOk(row);
                    const rj = getReject(row);
                    if (ok + rj > 0) filled++;
                    okSum += ok;
                    rjSum += rj;
                });

                return {
                    filled,
                    okSum,
                    rjSum
                };
            }

            function updateSummary() {
                const s = computeSummary();

                if (summaryRowFilled) summaryRowFilled.textContent = String(s.filled);
                if (summaryOk) summaryOk.textContent = nf.format(s.okSum);
                if (summaryReject) summaryReject.textContent = nf.format(s.rjSum);

                if (submitBtn) {
                    submitBtn.disabled = s.filled <= 0;
                    if (submitMeta) submitMeta.textContent = s.filled > 0 ? `${s.filled} baris siap` :
                        'Belum ada isi';
                }
            }

            function renderModalItems() {
                if (!modalItemsBody) return;

                // group per item_code (ringkas)
                const map = new Map();

                rows.forEach(row => {
                    const ok = getOk(row);
                    const rj = getReject(row);
                    const total = ok + rj;
                    if (total <= 0) return;

                    const code = (row.dataset.itemCode || '').trim() || '-';
                    const name = (row.dataset.itemName || '').trim() || '';
                    const remainingBefore = parseNum(row.dataset.remaining ||
                        '0'); // "Belum" sebelum setor ini
                    const sisa = Math.max(remainingBefore - total, 0);

                    const key = code + '||' + name;
                    if (!map.has(key)) {
                        map.set(key, {
                            code,
                            name,
                            ok: 0,
                            rj: 0,
                            sisa: 0
                        });
                    }
                    const it = map.get(key);
                    it.ok += ok;
                    it.rj += rj;
                    it.sisa += sisa;
                });

                const items = Array.from(map.values());

                if (modalItemsCount) modalItemsCount.textContent = String(items.length);

                if (items.length === 0) {
                    modalItemsBody.innerHTML = `<div class="modal-empty">Tidak ada item.</div>`;
                    return;
                }

                items.sort((a, b) => (a.code || '').localeCompare(b.code || ''));

                modalItemsBody.innerHTML = items.map(it => {
                    const sub = it.name ? `<div class="modal-item-sub">${escapeHtml(it.name)}</div>` : '';
                    return `
                        <div class="modal-item-row">
                            <div>
                                <div class="modal-item-title">${escapeHtml(it.code || '-')}</div>
                                ${sub}
                            </div>
                            <div class="modal-item-metrics">
                                <span class="mchip ok"><i class="bi bi-check-circle"></i> <span class="mono">${nf.format(it.ok)}</span></span>
                                <span class="mchip rj"><i class="bi bi-x-circle"></i> <span class="mono">${nf.format(it.rj)}</span></span>
                                <span class="mchip sisa"><i class="bi bi-hourglass-split"></i> <span class="mono">${nf.format(it.sisa)}</span></span>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            // ===== MODAL logic =====
            let lastFocus = null;

            function lockScroll(lock) {
                if (lock) {
                    document.documentElement.style.overflow = 'hidden';
                    document.body.style.overflow = 'hidden';
                } else {
                    document.documentElement.style.overflow = '';
                    document.body.style.overflow = '';
                }
            }

            function hideFloatingActions(hide) {
                const footer = document.querySelector('.form-footer');
                if (!footer) return;
                footer.style.opacity = hide ? '0' : '';
                footer.style.pointerEvents = hide ? 'none' : '';
            }

            function openSubmitModal() {
                if (!modalBackdrop || !modalSheet) return;

                const s = computeSummary();
                if (s.filled <= 0) return;

                if (modalRowFilled) modalRowFilled.textContent = String(s.filled);
                if (modalOk) modalOk.textContent = nf.format(s.okSum);
                if (modalRj) modalRj.textContent = nf.format(s.rjSum);

                if (modalDate) modalDate.textContent = (dateInput?.value || '-');
                if (modalPickup) modalPickup.textContent = (pickupSelect?.selectedOptions?.[0]?.textContent
                    ?.trim() || '-');

                renderModalItems();

                lastFocus = document.activeElement;

                modalBackdrop.classList.add('show');
                modalSheet.classList.add('show');
                modalBackdrop.setAttribute('aria-hidden', 'false');
                modalSheet.setAttribute('aria-hidden', 'false');

                lockScroll(true);
                hideFloatingActions(true);

                setTimeout(() => confirmModalBtn?.focus(), 30);
            }

            function closeSubmitModal() {
                if (!modalBackdrop || !modalSheet) return;
                modalBackdrop.classList.remove('show');
                modalSheet.classList.remove('show');
                modalBackdrop.setAttribute('aria-hidden', 'true');
                modalSheet.setAttribute('aria-hidden', 'true');

                lockScroll(false);
                hideFloatingActions(false);

                if (lastFocus && typeof lastFocus.focus === 'function') {
                    setTimeout(() => lastFocus.focus(), 0);
                }
            }

            function setSubmittingState(isSubmitting) {
                if (confirmModalBtn) {
                    confirmModalBtn.disabled = !!isSubmitting;
                    confirmModalBtn.classList.toggle('is-loading', !!isSubmitting);
                }
                if (submitBtn) submitBtn.disabled = true;
            }

            // Bind submit button (ANTI GAGAL)
            if (submitBtn) {
                submitBtn.setAttribute('type', 'button');
                submitBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openSubmitModal();
                });
            }

            closeModalBtn?.addEventListener('click', function(e) {
                e.preventDefault();
                closeSubmitModal();
            });
            cancelModalBtn?.addEventListener('click', function(e) {
                e.preventDefault();
                closeSubmitModal();
            });
            modalBackdrop?.addEventListener('click', function(e) {
                e.preventDefault();
                closeSubmitModal();
            });

            document.addEventListener('keydown', function(e) {
                const isOpen = modalSheet?.classList.contains('show');
                if (!isOpen) return;
                if (e.key === 'Escape') closeSubmitModal();
            });

            confirmModalBtn?.addEventListener('click', function(e) {
                e.preventDefault();
                if (!form) return;

                // hard guard double submit
                if (form.dataset.submitting === '1') return;

                setSubmittingState(true);
                form.dataset.submitting = '1';
                setTimeout(() => form.submit(), 20);
            });

            // prevent enter submits
            form?.addEventListener('keydown', function(e) {
                if (e.key !== 'Enter') return;
                const t = e.target;
                if (!t) return;
                if (t.tagName === 'TEXTAREA') return;
                if (t.tagName === 'INPUT' || t.tagName === 'SELECT') {
                    e.preventDefault();
                    return false;
                }
            });

            // filter item
            filterSelects.forEach(sel => {
                sel.addEventListener('change', function() {
                    const code = this.value || '';
                    filterSelects.forEach(s => {
                        if (s !== sel) s.value = code;
                    });
                    rows.forEach(row => {
                        const rowCode = (row.dataset.itemCode || '').trim();
                        row.hidden = !!(code && rowCode !== code);
                    });
                });
            });

            // row handlers
            rows.forEach(row => {
                const okD = row.querySelector('.qty-ok-desktop');
                const okM = row.querySelector('.qty-ok-mobile');
                const rjD = row.querySelector('.qty-reject-desktop');
                const rjM = row.querySelector('.qty-reject-mobile');
                const notes = row.querySelector('.notes-wrapper input[type="text"]');

                function scrollRowForInput(input) {
                    if (!isMobile()) return;
                    const rr = input.closest('.return-row');
                    if (!rr) return;
                    setTimeout(() => rr.scrollIntoView({
                        block: 'center',
                        behavior: 'smooth'
                    }), 250);
                }

                let touchMoved = false;
                row.addEventListener('touchstart', () => touchMoved = false, {
                    passive: true
                });
                row.addEventListener('touchmove', () => touchMoved = true, {
                    passive: true
                });

                row.addEventListener('click', function(e) {
                    if (touchMoved) {
                        touchMoved = false;
                        return;
                    }
                    if (e.target.closest('input, select, textarea, button, a, label')) return;

                    const remaining = parseNum(row.dataset.remaining || '0');
                    const currentOk = getOk(row);
                    const currentRj = getReject(row);

                    if (currentOk === remaining && currentRj === 0) {
                        setOk(row, 0);
                        setReject(row, 0);
                    } else {
                        setOk(row, remaining);
                        setReject(row, 0);
                    }

                    row.dataset.lastChanged = 'ok';
                    clamp(row, true);
                    updateRowVisual(row);
                    updateNotesVisibility(row);
                    updateSummary();
                });

                [okD, okM].forEach(inp => {
                    if (!inp) return;
                    inp.addEventListener('focus', function() {
                        this.select();
                        this.classList.add('qty-input-active');
                        scrollRowForInput(this);
                    });
                    inp.addEventListener('blur', function() {
                        this.classList.remove('qty-input-active');
                        row.dataset.lastChanged = 'ok';
                        clamp(row, true);
                        updateRowVisual(row);
                        updateSummary();
                    });
                    inp.addEventListener('input', function() {
                        row.dataset.lastChanged = 'ok';
                        clamp(row, false);
                        updateRowVisual(row);
                        updateSummary();
                    });
                });

                [rjD, rjM].forEach(inp => {
                    if (!inp) return;
                    inp.addEventListener('focus', function() {
                        this.select();
                        this.classList.add('qty-input-active');
                        scrollRowForInput(this);
                    });
                    inp.addEventListener('blur', function() {
                        this.classList.remove('qty-input-active');
                        row.dataset.lastChanged = 'reject';
                        clamp(row, true);
                        updateRowVisual(row);
                        updateNotesVisibility(row);
                        updateSummary();
                    });
                    inp.addEventListener('input', function() {
                        row.dataset.lastChanged = 'reject';
                        clamp(row, false);
                        updateRowVisual(row);
                        updateNotesVisibility(row);
                        updateSummary();
                    });
                });

                if (notes) {
                    notes.addEventListener('focus', function() {
                        scrollRowForInput(this);
                    });
                    notes.addEventListener('input', function() {
                        if (this.value.trim() !== '') {
                            const wrap = row.querySelector('.notes-wrapper');
                            if (wrap) wrap.classList.remove('d-none');
                        }
                    });
                    notes.addEventListener('blur', function() {
                        updateNotesVisibility(row);
                    });
                }

                clamp(row, false);
                updateRowVisual(row);
                updateNotesVisibility(row);
            });

            updateSummary();
        });
    </script>
@endpush
