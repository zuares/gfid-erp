{{-- resources/views/production/sewing_returns/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Return')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .84rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .14rem .5rem;
            font-size: .7rem;
            background: color-mix(in srgb, var(--card) 70%, var(--line) 30%);
            color: var(--muted);
        }

        .table-wrap {
            overflow-x: auto;
        }

        /* ====== HEADER ====== */
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }

        /* HEADER FORM STICKY */
        .card-header-sticky {
            position: sticky;
            top: .5rem;
            z-index: 20;
        }

        @media (max-width: 767.98px) {
            .card-header-sticky {
                top: .35rem;
            }
        }

        /* ====== ROW STATE (bundle) ====== */
        .return-row {
            transition:
                background-color .16s ease,
                box-shadow .16s ease,
                border-color .16s ease;
        }

        .return-row td {
            border-top-color: rgba(148, 163, 184, 0.25) !important;
        }

        .row-empty {
            box-shadow:
                inset 3px 0 0 color-mix(in srgb, var(--line) 80%, transparent 20%);
            background: var(--card);
        }

        .row-filled {
            background: color-mix(in srgb,
                    var(--card) 82%,
                    rgba(34, 197, 94, 0.20) 18%);
            box-shadow:
                inset 3px 0 0 rgba(34, 197, 94, 0.9),
                0 0 0 1px color-mix(in srgb, var(--line) 60%, rgba(34, 197, 94, .45) 40%);
        }

        .qty-remaining-pill {
            border-radius: 999px;
            padding: .06rem .55rem;
            font-size: .78rem;
            font-weight: 600;
            background: color-mix(in srgb,
                    var(--card) 65%,
                    rgba(34, 197, 94, 0.22) 35%);
            color: rgb(22, 163, 74);
            border: 1px solid color-mix(in srgb,
                    var(--line) 40%,
                    rgba(34, 197, 94, 0.7) 60%);
        }

        .qty-input {
            font-weight: 500;
            transition:
                font-weight .12s ease,
                box-shadow .12s ease,
                border-color .12s ease,
                background-color .12s ease;
        }

        .qty-input-active {
            font-weight: 700;
            border-color: rgba(34, 197, 94, .75);
            box-shadow: 0 0 0 1px rgba(34, 197, 94, .5);
            background: color-mix(in srgb, var(--card) 85%, rgba(34, 197, 94, .12) 15%);
        }

        .notes-input {
            font-size: .78rem;
        }

        .mobile-muted-soft {
            color: var(--muted);
            font-size: .76rem;
        }

        /* ============ MOBILE (<= 767.98px) ============ */
        @media (max-width: 767.98px) {
            .card {
                border-radius: 12px;
            }

            .page-wrap {
                padding-bottom: 6.5rem;
            }

            .header-row {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-header-secondary {
                width: 100%;
                justify-content: center;
                border-radius: 999px;
                padding-block: .45rem;
                font-size: .82rem;
            }

            .table-sewing-return {
                border-collapse: separate;
                border-spacing: 0 8px;
            }

            .table-sewing-return thead {
                display: none;
            }

            .table-sewing-return tbody tr {
                display: block;
                border-radius: 11px;
                border: 1px solid var(--line);
                padding: .52rem .6rem .55rem;
                margin-bottom: .5rem;
            }

            .table-sewing-return tbody tr:last-child {
                margin-bottom: 0;
            }

            .table-sewing-return td {
                display: block;
                border: none !important;
                padding: .08rem 0;
            }

            .td-desktop-only {
                display: none !important;
            }

            .mobile-row-top {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: .75rem;
                margin-bottom: .12rem;
            }

            .mobile-top-left {
                font-size: .84rem;
                display: flex;
                flex-direction: column;
                gap: .12rem;
            }

            .mobile-top-left .row-index {
                font-size: .72rem;
                color: var(--muted);
            }

            .mobile-top-left .item-code {
                font-weight: 700;
            }

            .mobile-top-right {
                text-align: right;
                font-size: .78rem;
            }

            .mobile-top-right .qty-remaining-label {
                font-size: .7rem;
                text-transform: uppercase;
                color: var(--muted);
                margin-bottom: .08rem;
            }

            .mobile-meta {
                font-size: .76rem;
                color: var(--muted);
                margin-bottom: .08rem;
            }

            .mobile-meta span+span {
                margin-left: .25rem;
            }

            .mobile-muted-soft {
                font-size: .75rem;
                color: var(--muted);
            }

            .cell-qty-row {
                display: flex;
                gap: .35rem;
                margin-top: .18rem;
            }

            .cell-qty-row .form-control {
                flex: 1;
            }

            .cell-notes {
                margin-top: .2rem;
            }

            /* FOOTER: floating kanan bawah */
            .form-footer {
                position: fixed;
                right: .9rem;
                bottom: 2.9rem;
                left: auto;
                z-index: 30;

                display: inline-flex !important;
                flex-direction: row-reverse;
                align-items: center !important;
                gap: .45rem;

                margin: 0;
                padding: 0;

                background: transparent;
                border: none;
            }

            .form-footer .btn {
                width: auto;
                border-radius: 999px;
                padding-inline: .9rem;
                padding-block: .35rem;
                box-shadow:
                    0 10px 20px rgba(15, 23, 42, .25),
                    0 3px 8px rgba(15, 23, 42, .2);
            }

            .form-footer .btn-primary {
                font-weight: 600;
                background: linear-gradient(135deg,
                        #0d6efd 0%,
                        #22c55e 55%,
                        #15803d 100%);
                border: none;
                display: inline-flex;
                align-items: center;
                gap: .35rem;
            }

            .form-footer .btn-outline-secondary {
                font-size: .78rem;
                padding-inline: .7rem;
                padding-block: .3rem;
                background: color-mix(in srgb, var(--card) 80%, #f8fafc 20%);
                border-color: color-mix(in srgb, var(--line) 70%, rgba(148, 163, 184, .9) 30%);
                display: inline-flex;
                align-items: center;
                gap: .25rem;
            }
        }

        /* ============ DESKTOP (>= 768px) ============ */
        @media (min-width: 768px) {
            .td-mobile-only {
                display: none !important;
            }
        }
    </style>
@endpush

@section('content')
    @php
        /** @var \App\Models\SewingPickup|null $selectedPickup */
        $selectedPickupId = old('pickup_id', $pickupId ?? null);
        $defaultDate = old('date', optional($selectedPickup?->date)->format('Y-m-d') ?? now()->format('Y-m-d'));

        $itemCodes = $lines
            ->map(function ($l) {
                return optional(optional($l->bundle)->finishedItem)->code;
            })
            ->filter()
            ->unique()
            ->values();
    @endphp

    <div class="page-wrap py-3 py-md-4">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="header-row">
                <div>
                    <h1 class="h5 mb-1">Sewing Return</h1>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('production.sewing_returns.index') }}"
                        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 btn-header-secondary">
                        <i class="bi bi-arrow-left"></i>
                        <span>Daftar Return</span>
                    </a>
                    <a href="{{ route('production.sewing_pickups.index') }}"
                        class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 btn-header-secondary">
                        <i class="bi bi-basket"></i>
                        <span>Sewing Pickup</span>
                    </a>
                </div>
            </div>
        </div>

        <form id="sewing-return-form" action="{{ route('production.sewing_returns.store') }}" method="post">
            @csrf

            {{-- HEADER FORM (STICKY) --}}
            <div class="card p-3 mb-3 card-header-sticky">
                <div class="row g-3 align-items-end">
                    {{-- Tanggal Return --}}
                    <div class="col-md-3 col-6">
                        <div class="help mb-1">Tanggal Setor</div>
                        <input type="date" name="date"
                            class="form-control form-control-sm @error('date') is-invalid @enderror"
                            value="{{ $defaultDate }}">
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <input type="hidden" name="qc_date" value="{{ old('qc_date', $defaultDate) }}">
                    </div>

                    {{-- Pilih Sewing Pickup (hanya yg masih punya sisa) --}}
                    <div class="col-md-5 col-12">
                        <div class="help mb-1">Tanggal + Operator Ambil Jahit</div>
                        <select name="pickup_id" id="pickup_id_select"
                            class="form-select form-select-sm @error('pickup_id') is-invalid @enderror"
                            onchange="if(this.value){ window.location='{{ route('production.sewing_returns.create') }}?pickup_id=' + this.value; }">
                            <option value="">Pilih Tanggal Ambil...</option>
                            @foreach ($pickups as $pickup)
                                @php
                                    // Hitung total sisa (belum setor) per pickup
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
                                        {{ $pickup->operator?->name ?? '(Tanpa operator)' }}
                                        -
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
                        {{-- 🔥 operator_id langsung ikut SewingPickup --}}
                        @if ($selectedPickup)
                            <input type="hidden" name="operator_id" value="{{ $selectedPickup->operator_id }}">
                        @endif
                        @if (!$selectedPickup)
                            <div class="small text-muted mt-1">
                                Pilih Sewing Pickup untuk menampilkan bundles.
                            </div>
                        @endif

                        {{-- FILTER KODE ITEM: MOBILE (di bawah sewing pickup) --}}
                        @if ($itemCodes->isNotEmpty())
                            <div class="mt-3 d-md-none">
                                <div class="help mb-1">Filter Kode Item (opsional)</div>
                                <select id="filter-item-code" class="form-select form-select-sm">
                                    <option value="">Semua item...</option>
                                    @foreach ($itemCodes as $code)
                                        <option value="{{ $code }}">{{ $code }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    {{-- FILTER KODE ITEM: DESKTOP (kolom ketiga sejajar) --}}
                    @if ($itemCodes->isNotEmpty())
                        <div class="col-md-4 d-none d-md-block">
                            <div class="help mb-1">Filter Kode Item (opsional)</div>
                            <select id="filter-item-code" class="form-select form-select-sm">
                                <option value="">Semua item...</option>
                                @foreach ($itemCodes as $code)
                                    <option value="{{ $code }}">{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            {{-- GLOBAL CLIENT-SIDE ERROR --}}
            <div id="client-error-box" class="alert alert-warning py-2 small d-none mb-3">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <span id="client-error-text"></span>
            </div>

            {{-- BUNDLES --}}
            <div class="card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div>
                        <h2 class="h6 mb-0">Daftar Ambil Jahit</h2>
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
                                <th style="width: 40px;" class="text-center">#</th>
                                <th style="width: 210px;">Tanggal Ambil Jahit</th>
                                <th style="width: 210px;">Item</th>
                                <th style="width: 120px;" class="text-end">
                                    Belum Setor
                                </th>
                                <th style="width: 130px;" class="text-center">
                                    Setor
                                </th>
                                <th style="width: 130px;" class="text-center">
                                    Reject
                                </th>
                                <th style="width: 220px;">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lines as $idx => $line)
                                @php
                                    /** @var \App\Models\SewingPickupLine $line */
                                    $bundle = $line->bundle;
                                    $pickup = $line->sewingPickup ?? $selectedPickup;
                                    $lot = $bundle?->cuttingJob?->lot;

                                    $remaining = (float) ($line->remaining_qty ?? 0);

                                    $pickupDateLabel = '';
                                    if ($pickup && $pickup->date) {
                                        try {
                                            $pickupDateLabel = id_day_datetime($pickup->created_at);
                                        } catch (\Throwable $e) {
                                            $pickupDateLabel = (string) $pickup->date;
                                        }
                                    }

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
                                    data-item-code="{{ $bundle?->finishedItem?->code }}">
                                    {{-- ID line SewingPickupLine --}}
                                    <input type="hidden" name="results[{{ $idx }}][sewing_pickup_line_id]"
                                        value="{{ $line->id }}">
                                    {{-- bundle_id kalau perlu nanti --}}
                                    <input type="hidden" name="results[{{ $idx }}][bundle_id]"
                                        value="{{ $bundle?->id }}">

                                    {{-- INDEX + MOBILE TOP --}}
                                    <td class="align-top">
                                        <div class="d-none d-md-flex justify-content-center">
                                            <span class="small text-muted">{{ $loop->iteration }}</span>
                                        </div>

                                        <div class="mobile-row-top d-md-none">
                                            <div class="mobile-top-left">
                                                <span class="row-index">#{{ $loop->iteration }}</span>
                                                <span class="item-code">
                                                    {{ $bundle?->finishedItem?->code ?? '-' }}
                                                </span>
                                            </div>
                                            <div class="mobile-top-right">
                                                <div class="qty-remaining-label">Belum Setor</div>
                                                <div>
                                                    <span class="qty-remaining-pill">
                                                        {{ number_format($remaining, 2, ',', '.') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- TANGGAL PICKUP + OPERATOR + BUNDLE --}}
                                    <td class="align-top">
                                        {{-- Desktop --}}
                                        <div class="d-none d-md-block">
                                            <div class="fw-bold mb-1">
                                                {{ $pickupDateLabel ?: '-' }}
                                            </div>
                                            <div class="d-flex flex-wrap align-items-center gap-1">
                                                @if ($pickup && $pickup->operator)
                                                    <span class="badge-soft mono">
                                                        {{ $pickup->operator->code ?? '-' }}
                                                    </span>
                                                @endif
                                                @if ($bundle && $bundle->bundle_code)
                                                    <span class="badge-soft mono">
                                                        {{ $bundle->bundle_code }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Mobile --}}
                                        <div class="d-md-none mobile-meta">
                                            @if ($pickupDateLabel)
                                                <span class="fw-bold">{{ $pickupDateLabel }}</span>
                                            @endif
                                            @if ($pickup && $pickup->operator)
                                                <span class="badge-soft mono">
                                                    {{ $pickup->operator->code ?? '-' }}
                                                </span>
                                            @endif
                                            @if ($bundle && $bundle->bundle_code)
                                                <span class="badge-soft mono">
                                                    {{ $bundle->bundle_code }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- ITEM --}}
                                    <td class="align-top">
                                        <div class="fw-bold d-none d-md-block">
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

                                    {{-- SISA JADI (desktop) --}}
                                    <td class="text-end align-top d-none d-md-table-cell">
                                        <span class="qty-remaining-pill">
                                            {{ number_format($remaining, 2, ',', '.') }}
                                        </span>
                                    </td>

                                    {{-- QTY OK JADI --}}
                                    <td class="text-end align-top">
                                        {{-- Desktop --}}
                                        <div class="d-none d-md-block">
                                            <input type="number" step="0.01" min="0" inputmode="decimal"
                                                name="results[{{ $idx }}][qty_ok]"
                                                class="form-control form-control-sm text-end qty-input qty-ok-input qty-ok-desktop @error("results.$idx.qty_ok") is-invalid @enderror"
                                                value="{{ $defaultOk ?? '' }}" placeholder="Jumlah setor">
                                        </div>

                                        {{-- Mobile --}}
                                        <div class="cell-qty-row d-md-none">
                                            <input type="number" step="0.01" min="0" inputmode="decimal"
                                                name="results[{{ $idx }}][qty_ok]"
                                                class="form-control form-control-sm text-end qty-input qty-ok-input qty-ok-mobile @error("results.$idx.qty_ok") is-invalid @enderror"
                                                value="{{ $defaultOk ?? '' }}" placeholder="OK">
                                            <input type="number" step="0.01" min="0" inputmode="decimal"
                                                name="results[{{ $idx }}][qty_reject]"
                                                class="form-control form-control-sm text-end qty-input qty-reject-input qty-reject-mobile @error("results.$idx.qty_reject") is-invalid @enderror"
                                                value="{{ $defaultReject ?? '' }}" placeholder="RJ">
                                        </div>

                                        @error("results.$idx.qty_ok")
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        @error("results.$idx.qty_reject")
                                            <div class="invalid-feedback d-block d-md-none">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    {{-- QTY REJECT (desktop) --}}
                                    <td class="text-end align-top d-none d-md-table-cell">
                                        <input type="number" step="0.01" min="0" inputmode="decimal"
                                            name="results[{{ $idx }}][qty_reject]"
                                            class="form-control form-control-sm text-end qty-input qty-reject-input qty-reject-desktop @error("results.$idx.qty_reject") is-invalid @enderror"
                                            value="{{ $defaultReject ?? '' }}" placeholder="Qty reject">
                                        @error("results.$idx.qty_reject")
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    {{-- CATATAN --}}
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

            {{-- SUBMIT --}}
            <div class="d-flex justify-content-between align-items-center mb-5 form-footer">
                <a href="{{ route('production.sewing_returns.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline">Batal</span>
                </a>

                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-check2-circle"></i>
                    <span class="text-light">Simpan Return</span>
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.return-row');
            const filterItemSelect = document.getElementById('filter-item-code');

            const clientErrorBox = document.getElementById('client-error-box');
            const clientErrorText = document.getElementById('client-error-text');

            const isMobile = () => window.innerWidth <= 767;

            function showClientError(message) {
                if (!clientErrorBox || !clientErrorText) return;
                clientErrorText.textContent = message;
                clientErrorBox.classList.remove('d-none');
            }

            function hideClientError() {
                if (!clientErrorBox || !clientErrorText) return;
                clientErrorBox.classList.add('d-none');
                clientErrorText.textContent = '';
            }

            function parseNum(val) {
                const n = parseFloat(val);
                return isNaN(n) ? 0 : n;
            }

            function getOk(row) {
                const desktop = row.querySelector('.qty-ok-desktop');
                const mobile = row.querySelector('.qty-ok-mobile');
                if (isMobile() && mobile) return parseNum(mobile.value);
                if (desktop && desktop.value !== '') return parseNum(desktop.value);
                if (mobile && mobile.value !== '') return parseNum(mobile.value);
                return 0;
            }

            function getReject(row) {
                const desktop = row.querySelector('.qty-reject-desktop');
                const mobile = row.querySelector('.qty-reject-mobile');
                if (isMobile() && mobile) return parseNum(mobile.value);
                if (desktop && desktop.value !== '') return parseNum(desktop.value);
                if (mobile && mobile.value !== '') return parseNum(mobile.value);
                return 0;
            }

            function setOk(row, value) {
                const desktop = row.querySelector('.qty-ok-desktop');
                const mobile = row.querySelector('.qty-ok-mobile');
                const v = value > 0 ? value : '';
                if (desktop) desktop.value = v;
                if (mobile) mobile.value = v;
            }

            function setReject(row, value) {
                const desktop = row.querySelector('.qty-reject-desktop');
                const mobile = row.querySelector('.qty-reject-mobile');
                const v = value > 0 ? value : '';
                if (desktop) desktop.value = v;
                if (mobile) mobile.value = v;
            }

            // Clamp: OK + Reject tidak boleh melebihi "Belum Setor"
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
                            `Qty OK + Reject tidak boleh melebihi Sisa Jadi (baris #${index}). Input sudah disesuaikan.`
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
                } else {
                    const notesInput = notesWrapper.querySelector('input[type="text"]');
                    if (notesInput && notesInput.value === '') {
                        notesWrapper.classList.add('d-none');
                    }
                }
            }

            // FILTER KODE ITEM
            if (filterItemSelect) {
                filterItemSelect.addEventListener('change', function() {
                    const code = this.value || '';

                    rows.forEach(function(row) {
                        const rowCode = (row.dataset.itemCode || '').trim();
                        row.hidden = code && rowCode !== code;
                    });
                });
            }

            rows.forEach(function(row) {
                const okDesktop = row.querySelector('.qty-ok-desktop');
                const okMobile = row.querySelector('.qty-ok-mobile');
                const rejectDesktop = row.querySelector('.qty-reject-desktop');
                const rejectMobile = row.querySelector('.qty-reject-mobile');
                const notesWrapper = row.querySelector('.notes-wrapper');
                const itemCodeSpan = row.querySelector('.item-code');
                const remaining = parseNum(row.dataset.remaining || '0');

                function focusOk() {
                    const target = isMobile() ? (okMobile || okDesktop) : (okDesktop || okMobile);
                    if (!target) return;
                    target.focus();
                    target.select();
                    target.classList.add('qty-input-active');
                }

                // klik seluruh ROW → toggle OK = remaining / 0
                row.addEventListener('click', function(e) {
                    const target = e.target;

                    if (target.closest('input, select, textarea, button, a')) {
                        return;
                    }

                    hideClientError();

                    let ok = getOk(row);
                    let reject = getReject(row);

                    if (ok === remaining && reject === 0) {
                        setOk(row, 0);
                        setReject(row, 0);
                        if (notesWrapper) {
                            const notesInput = notesWrapper.querySelector('input[type="text"]');
                            if (notesInput) notesInput.value = '';
                            notesWrapper.classList.add('d-none');
                        }
                    } else {
                        setOk(row, remaining);
                        setReject(row, 0);
                        if (notesWrapper) {
                            const notesInput = notesWrapper.querySelector('input[type="text"]');
                            if (notesInput) notesInput.value = '';
                            notesWrapper.classList.add('d-none');
                        }
                    }

                    row.dataset.lastChanged = 'ok';
                    clampToRemaining(row, false);
                    updateRowVisual(row);
                    updateNotesVisibility(row);
                    focusOk();
                });

                // klik kode item di mobile = trigger klik row
                if (itemCodeSpan) {
                    itemCodeSpan.style.cursor = 'pointer';
                    itemCodeSpan.addEventListener('click', function(e) {
                        e.stopPropagation();
                        row.click();
                    });
                }

                // Handler OK
                [okDesktop, okMobile].forEach(function(input) {
                    if (!input) return;

                    input.addEventListener('focus', function() {
                        this.select();
                        this.classList.add('qty-input-active');
                    });
                    input.addEventListener('blur', function() {
                        this.classList.remove('qty-input-active');
                        row.dataset.lastChanged = 'ok';
                        clampToRemaining(row, true);
                        updateRowVisual(row);
                    });
                    input.addEventListener('input', function() {
                        row.dataset.lastChanged = 'ok';
                        clampToRemaining(row, true);
                        updateRowVisual(row);
                    });
                });

                // Handler Reject
                [rejectDesktop, rejectMobile].forEach(function(input) {
                    if (!input) return;

                    input.addEventListener('focus', function() {
                        this.select();
                        this.classList.add('qty-input-active');
                    });
                    input.addEventListener('blur', function() {
                        this.classList.remove('qty-input-active');
                        row.dataset.lastChanged = 'reject';
                        clampToRemaining(row, true);
                        updateRowVisual(row);
                        updateNotesVisibility(row);
                    });
                    input.addEventListener('input', function() {
                        row.dataset.lastChanged = 'reject';
                        clampToRemaining(row, true);
                        updateRowVisual(row);
                        updateNotesVisibility(row);
                    });
                });

                // init awal
                clampToRemaining(row, false);
                updateRowVisual(row);
                updateNotesVisibility(row);
            });
        });
    </script>
@endpush
