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

        .muted-hint {
            color: var(--muted);
            font-size: .78rem;
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

        <form id="sewing-return-form" action="{{ route('production.sewing.returns.store') }}" method="POST">
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
                                        // ✅ SKIP PICKUP VOID
                                        if (!empty($pickup->voided_at)) {
                                            continue;
                                        }

                                        $pickupLines = $pickup->lines ?? collect();

                                        // ✅ totalRemaining sinkron dengan controller (incl progress_adjusted)
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
                                                <div class="text-muted small">
                                                    LOT: <span class="mono">{{ $lot->code }}</span>
                                                </div>
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

                    <div class="muted-hint mt-2 d-none d-md-block">
                        Tip: klik baris untuk isi cepat OK = “Belum”.
                    </div>
                </div>
            </div>

            {{-- Footer: 2 tombol (Batal + Simpan) --}}
            <div class="form-footer d-flex align-items-center">
                <div class="floating-actions">
                    <a href="{{ route('production.sewing.returns.index') }}"
                        class="btn btn-outline-secondary btn-sm btn-floating-cancel">
                        <i class="bi bi-arrow-left"></i>
                    </a>

                    <button type="submit" id="btn-submit-return" class="btn btn-success btn-sm btn-floating-submit"
                        disabled>
                        <i class="bi bi-check2 me-1"></i> Simpan
                        <span class="btn-floating-meta" id="btn-submit-return-meta">Belum ada isi</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rows = Array.from(document.querySelectorAll('.return-row'));
            const filterSelects = Array.from(document.querySelectorAll('.filter-item-code'));

            const clientErrorBox = document.getElementById('client-error-box');
            const clientErrorText = document.getElementById('client-error-text');

            const summaryRowFilled = document.getElementById('summary-row-filled');
            const summaryOk = document.getElementById('summary-ok');
            const summaryReject = document.getElementById('summary-reject');

            const submitBtn = document.getElementById('btn-submit-return');
            const submitMeta = document.getElementById('btn-submit-return-meta');

            const isMobile = () => window.innerWidth <= 767;

            function scrollRowForInput(input) {
                if (!isMobile()) return;
                const row = input.closest('.return-row');
                if (!row) return;
                setTimeout(() => {
                    row.scrollIntoView({
                        block: 'center',
                        behavior: 'smooth'
                    });
                }, 250);
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

            const parseNum = (val) => {
                const n = parseFloat(val);
                return isNaN(n) ? 0 : n;
            };

            let errTimer = null;

            const showClientError = (msg) => {
                if (!clientErrorBox || !clientErrorText) return;
                clientErrorText.textContent = msg;
                clientErrorBox.classList.remove('d-none');

                if (errTimer) clearTimeout(errTimer);
                errTimer = setTimeout(() => hideClientError(), 2200);
            };

            const hideClientError = () => {
                if (!clientErrorBox || !clientErrorText) return;
                clientErrorText.textContent = '';
                clientErrorBox.classList.add('d-none');
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

            function updateSummary() {
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

                if (summaryRowFilled) summaryRowFilled.textContent = String(filled);
                if (summaryOk) summaryOk.textContent = nf.format(okSum);
                if (summaryReject) summaryReject.textContent = nf.format(rjSum);

                if (submitBtn) {
                    submitBtn.disabled = filled <= 0;
                    if (submitMeta) submitMeta.textContent = filled > 0 ? `${filled} baris siap` : 'Belum ada isi';
                }
            }

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

            rows.forEach(row => {
                const okD = row.querySelector('.qty-ok-desktop');
                const okM = row.querySelector('.qty-ok-mobile');
                const rjD = row.querySelector('.qty-reject-desktop');
                const rjM = row.querySelector('.qty-reject-mobile');
                const notes = row.querySelector('.notes-wrapper input[type="text"]');

                let touchMoved = false;

                row.addEventListener('touchstart', function() {
                    touchMoved = false;
                }, {
                    passive: true
                });
                row.addEventListener('touchmove', function() {
                    touchMoved = true;
                }, {
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

                    if (!isMobile()) {
                        const target = okD || okM;
                        if (target) {
                            target.focus();
                            target.select();
                            target.classList.add('qty-input-active');
                        }
                    }
                });

                // OK handlers
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

                // RJ handlers
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

                // Notes
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
