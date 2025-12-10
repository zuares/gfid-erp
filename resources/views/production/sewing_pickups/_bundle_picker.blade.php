{{-- resources/views/production/sewing_pickups/_bundle_picker.blade.php --}}

@push('head')
    <style>
        .sewing-pickup-bundle-picker {
            /* wrapper khusus partial ini */
        }

        .sewing-pickup-bundle-picker-card {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        body[data-theme="light"] .sewing-pickup-bundle-picker-card {
            background: #ffffff;
        }

        body[data-theme="dark"] .sewing-pickup-bundle-picker-card {
            border-color: rgba(51, 65, 85, 0.9);
            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.8),
                0 0 0 1px rgba(15, 23, 42, 0.9);
        }

        .sewing-pickup-bundle-picker-card .card-section {
            padding: 1rem 1.1rem;
            display: flex;
            flex-direction: column;
            gap: .6rem;
        }

        @media (min-width: 768px) {
            .sewing-pickup-bundle-picker-card .card-section {
                padding: 1.1rem 1.4rem;
            }
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .14rem .5rem;
            font-size: .7rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        /* ====== HEADER / RINGKASAN ====== */
        .filter-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: .7rem;
            align-items: flex-start;
        }

        .filter-header-left {
            display: flex;
            flex-direction: column;
            gap: .25rem;
        }

        .filter-header-left-title {
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        .filter-header-left-title h2 {
            margin: 0;
            font-size: .96rem;
            font-weight: 700;
        }

        .summary-inline {
            font-size: .78rem;
            color: var(--muted);
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            align-items: baseline;
        }

        .summary-inline-label {
            font-weight: 600;
            color: #4b5563;
        }

        body[data-theme="dark"] .summary-inline-label {
            color: #e5e7eb;
        }

        .summary-inline-dot {
            opacity: .7;
        }

        /* ========= SEARCH + FILTER SECTION ========= */
        .search-filter-section {
            margin-top: .2rem;
            margin-bottom: .4rem;
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }

        .search-filter-top {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem .75rem;
            align-items: flex-end;
            justify-content: space-between;
        }

        .search-label {
            font-size: .75rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .search-left {
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }

        .item-code-select-wrap {
            max-width: 260px;
            position: relative;
        }

        .item-code-select-wrap .form-select {
            font-size: .8rem;
            font-weight: 600;
            border-radius: .45rem;
            border-color: rgba(37, 99, 235, 0.35);
            background-color: var(--card);
            box-shadow:
                0 0 0 0 rgba(191, 219, 254, 0),
                0 0 0 0 rgba(37, 99, 235, 0);
            transition: box-shadow .2s ease, border-color .2s ease, background-color .2s ease;
        }

        .item-code-select-wrap .form-select:focus {
            border-color: rgba(37, 99, 235, 0.7);
            box-shadow:
                0 0 0 1px rgba(191, 219, 254, 0.85),
                0 4px 10px rgba(37, 99, 235, 0.15);
        }

        body[data-theme="dark"] .item-code-select-wrap .form-select {
            background-color: rgba(15, 23, 42, 0.96);
            border-color: rgba(129, 140, 248, 0.7);
        }

        @keyframes select-soft-pulse {
            0% {
                box-shadow:
                    0 0 0 1px rgba(191, 219, 254, 0.9),
                    0 6px 14px rgba(37, 99, 235, 0.25);
            }

            100% {
                box-shadow:
                    0 0 0 1px rgba(191, 219, 254, 0.5),
                    0 3px 8px rgba(37, 99, 235, 0.14);
            }
        }

        .item-code-select-wrap.focal-pulse .form-select {
            animation: select-soft-pulse .6s ease-out 2;
        }

        .search-input-wrap {
            max-width: 320px;
            width: 100%;
        }

        .search-input-wrap .form-control {
            font-size: .8rem;
            letter-spacing: .6px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .input-group-sm .input-group-text {
            font-size: .8rem;
        }

        .selection-toggle-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            font-size: .78rem;
            color: var(--muted);
        }

        .selection-toggle-right {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            font-size: .76rem;
        }

        .selection-toggle-right .form-check-input {
            cursor: pointer;
        }

        /* ========== TABLE / BUNDLE ROW ========== */
        .bundle-row {
            transition:
                background-color .16s ease,
                box-shadow .16s ease,
                border-color .16s ease,
                transform .1s ease;
        }

        .bundle-row td {
            border-top-color: rgba(148, 163, 184, 0.25) !important;
        }

        .bundle-card-row {
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(255, 255, 255, 0.96);
            box-shadow:
                0 6px 16px rgba(15, 23, 42, 0.10),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .bundle-card-row:hover {
            transform: translateY(-1px);
            box-shadow:
                0 10px 24px rgba(15, 23, 42, 0.14),
                0 0 0 1px rgba(148, 163, 184, 0.25);
        }

        body[data-theme="dark"] .bundle-card-row {
            background: rgba(15, 23, 42, 0.96);
            border-color: rgba(51, 65, 85, 0.9);
            box-shadow:
                0 8px 20px rgba(0, 0, 0, 0.55),
                0 0 0 1px rgba(30, 64, 175, 0.6);
        }

        .bundle-card-row.is-selected {
            border-color: rgba(59, 130, 246, 0.85);
            box-shadow:
                0 0 0 1px rgba(191, 219, 254, 0.9),
                0 14px 26px rgba(37, 99, 235, 0.18);
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.08) 0,
                    rgba(255, 255, 255, 0.98) 55%);
            transform: translateY(-1px);
        }

        body[data-theme="dark"] .bundle-card-row.is-selected {
            background: radial-gradient(circle at top left,
                    rgba(37, 99, 235, 0.3) 0,
                    rgba(15, 23, 42, 0.96) 55%);
            border-color: rgba(147, 197, 253, 0.9);
            box-shadow:
                0 0 0 1px rgba(59, 130, 246, 0.8),
                0 18px 32px rgba(15, 23, 42, 0.9);
        }

        .row-empty {
            box-shadow: inset 3px 0 0 rgba(148, 163, 184, .3);
        }

        .row-picked {
            box-shadow: inset 3px 0 0 rgba(37, 99, 235, .9);
        }

        .qty-ready-pill {
            border-radius: 999px;
            padding: .08rem .58rem;
            font-size: .78rem;
            font-weight: 600;
            background: rgba(13, 110, 253, 0.08);
            color: #0d6efd;
            display: inline-block;
            max-width: 100%;
        }

        .qty-input {
            font-weight: 500;
            transition: font-weight .12s ease, box-shadow .12s ease, border-color .12s ease;
        }

        .qty-input-active {
            font-weight: 600;
            border-color: rgba(37, 99, 235, .75);
            box-shadow: 0 0 0 1px rgba(37, 99, 235, .4);
        }

        .qty-input.border-warning {
            border-color: #eab308 !important;
        }

        /* FLOATING ACTION (MOBILE): SUBMIT SHORTCUT */
        .bundle-actions-fab {
            position: fixed;
            right: .9rem;
            bottom: 7.2rem;
            z-index: 40;
            display: none;
        }

        .bundle-submit-fab {
            border-radius: 999px;
            border: none;
            padding: .4rem 1rem;
            font-size: .82rem;
            font-weight: 600;
            background: linear-gradient(135deg, #0d6efd 0%, #2563eb 60%, #1d4ed8 100%);
            color: #f9fafb;
            box-shadow:
                0 12px 24px rgba(15, 23, 42, .35),
                0 0 0 1px rgba(191, 219, 254, .9);
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .bundle-submit-fab:disabled {
            opacity: .6;
            cursor: not-allowed;
            box-shadow:
                0 6px 16px rgba(15, 23, 42, .25),
                0 0 0 1px rgba(148, 163, 184, .7);
        }

        /* ============ MOBILE (<= 767.98px) ============ */
        @media (max-width: 767.98px) {

            .sewing-pickup-bundle-picker-card .card-section {
                padding: .85rem .9rem;
                gap: .5rem;
            }

            .filter-header {
                order: 0;
            }

            .search-filter-section {
                order: 1;
            }

            .table-wrap {
                order: 2;
                overflow-x: visible;
            }

            .search-filter-top {
                flex-direction: column;
                align-items: stretch;
                gap: .6rem;
            }

            .search-input-wrap,
            .item-code-select-wrap {
                max-width: 100%;
            }

            .table-sewing-pickup {
                width: 100%;
                table-layout: fixed;
                border-collapse: separate;
                border-spacing: 0 8px;
            }

            .table-sewing-pickup thead {
                display: none;
            }

            .table-sewing-pickup tbody tr {
                display: block;
                width: 100%;
                box-sizing: border-box;
                border-radius: 14px;
                border: 1px solid rgba(148, 163, 184, 0.3);
                padding: .7rem .8rem .75rem;
                margin-bottom: .45rem;
                background: #ffffff;
                cursor: pointer;
                box-shadow:
                    0 8px 24px rgba(15, 23, 42, 0.10),
                    0 0 0 1px rgba(15, 23, 42, 0.02);
                overflow: hidden;
            }

            body[data-theme="dark"] .table-sewing-pickup tbody tr {
                background: rgba(15, 23, 42, 0.96);
            }

            .table-sewing-pickup tbody tr:last-child {
                margin-bottom: 2.75rem;
            }

            .table-sewing-pickup td {
                display: block;
                border: none !important;
                padding: .1rem 0;
            }

            .td-mobile-extra {
                padding: 0 !important;
            }

            .td-desktop-only {
                display: none !important;
            }

            .mobile-row-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: .75rem;
                margin-bottom: .22rem;
            }

            .mobile-row-header-left {
                font-size: .82rem;
                display: flex;
                flex-direction: column;
                gap: .06rem;
                flex: 1;
                min-width: 0;
            }

            .mobile-row-header-topline {
                display: flex;
                align-items: center;
                gap: .4rem;
                min-width: 0;
            }

            .mobile-row-header-left .row-index {
                font-size: .72rem;
                color: var(--muted);
                flex-shrink: 0;
            }

            .mobile-row-header-left .item-code {
                font-size: 1.02rem !important;
                font-weight: 800 !important;
                color: #2563eb !important;
                letter-spacing: .12px;
                white-space: nowrap;
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .mobile-row-header-left .item-name {
                font-size: .78rem !important;
                color: var(--muted) !important;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .mobile-row-header-right {
                text-align: right;
                font-size: .78rem;
                min-width: 120px;
                flex-shrink: 0;
            }

            .mobile-row-header-right .qty-ready-label {
                font-size: .65rem !important;
                font-weight: 600;
                color: var(--muted);
                text-transform: uppercase;
                letter-spacing: .28px;
                margin-bottom: .04rem;
            }

            .mobile-row-header-right .qty-ready-value {
                display: flex;
                justify-content: flex-end;
            }

            .mobile-row-header-right .qty-ready-value .qty-ready-pill {
                background: rgba(37, 99, 235, 0.12) !important;
                color: #1d4ed8 !important;
                padding: .18rem .58rem !important;
                border-radius: 999px !important;
                font-size: .94rem !important;
                font-weight: 800 !important;
                max-width: 100%;
                min-width: 0;
                text-align: center;
                display: inline-block;
            }

            .mobile-row-meta {
                font-size: .74rem;
                color: var(--muted);
                margin-bottom: .18rem;
                display: flex;
                justify-content: space-between;
                gap: .5rem;
            }

            .mobile-row-meta-left {
                flex: 1;
                min-width: 0;
            }

            .mobile-row-meta-label {
                font-size: .68rem;
                text-transform: uppercase;
                color: var(--muted);
                opacity: .9;
                letter-spacing: .08em;
            }

            .mobile-row-meta-value {
                font-size: .78rem;
                word-break: break-word;
            }

            .mobile-row-meta-value .mono {
                font-size: .8rem;
            }

            .mobile-row-footer-left .pickup-label {
                font-size: .74rem !important;
                font-weight: 600 !important;
                color: #2563eb !important;
                margin-bottom: .1rem;
                letter-spacing: .17px;
                text-transform: uppercase;
            }

            .mobile-row-footer-left input.qty-input {
                font-size: .94rem !important;
                font-weight: 600 !important;
                padding-block: .35rem !important;
                border: 1.4px solid rgba(37, 99, 235, .45) !important;
                border-radius: 10px !important;
                box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .18) !important;
                text-align: center !important;
            }

            .mobile-row-footer-left input.qty-input:focus {
                border-color: #2563eb !important;
                box-shadow: 0 0 0 2px rgba(37, 99, 235, .3) !important;
            }

            .gudang-section {
                display: none !important;
            }

            .bundle-actions-fab {
                display: flex;
            }
        }

        @media (min-width: 768px) {
            .td-mobile-extra {
                display: none !important;
            }

            .bundle-actions-fab {
                display: none !important;
            }
        }
    </style>
@endpush
@php
    $oldLines = old('lines', []);
    $preselectedBundleId = request('bundle_id');

    // filter dulu kalau ada bundle_id spesifik
    $displayBundles = $preselectedBundleId ? $bundles->where('id', (int) $preselectedBundleId) : $bundles;

    // hanya bundle dengan qty_ready_for_sewing > 0
    $displayBundles = $displayBundles
        ->map(function ($b) {
            $b->computed_qty_ready = (float) $b->qty_ready_for_sewing;
            return $b;
        })
        ->filter(fn($b) => $b->computed_qty_ready > 0)
        ->values();

    $totalBundlesReady = $displayBundles->count();
    $totalQtyReady = $displayBundles->sum(fn($b) => (float) $b->computed_qty_ready);

    $itemCodes = $displayBundles->pluck('finishedItem.code')->filter()->unique()->sort()->values();
@endphp

<div class="sewing-pickup-bundle-picker mb-3">
    <div class="card sewing-pickup-bundle-picker-card">
        <div class="card-section">

            {{-- HEADER + SUMMARY --}}
            <div class="filter-header">
                <div class="filter-header-left">
                    <div class="filter-header-left-title">
                        <i class="bi bi-funnel text-muted"></i>
                        <h2>Daftar ambil jahit</h2>
                    </div>

                    <div class="summary-inline">
                        <span class="summary-inline-label">Dipilih:</span>
                        <span id="summary-selected-bundles">0</span>

                        <span class="summary-inline-dot">•</span>

                        <span class="summary-inline-label">Pickup:</span>
                        <span><span id="summary-selected-qty">0,00</span> pcs</span>

                        <span class="summary-inline-dot d-none d-md-inline">•</span>

                        <span class="summary-inline-label d-none d-md-inline">Ready:</span>
                        <span class="d-none d-md-inline">
                            {{ number_format($totalQtyReady, 2, ',', '.') }} pcs
                        </span>
                    </div>
                </div>

                {{-- DESKTOP: toggle hanya baris yang ada pickup --}}
                <div class="selection-toggle-row d-none d-md-flex">
                    <div class="selection-toggle-right">
                        <label class="form-check-label" for="toggle-only-picked">
                            Tampilkan hanya baris dengan pickup
                        </label>
                        <input type="checkbox" class="form-check-input" id="toggle-only-picked">
                    </div>
                </div>
            </div>

            {{-- FILTER (select kode barang + search) --}}
            <div class="search-filter-section">
                <div class="search-filter-top">
                    <div class="search-left">
                        <div class="search-label">
                            Cari bundles
                        </div>

                        <div class="item-code-select-wrap" id="item-code-select-wrap">
                            <select id="item-code-select" class="form-select form-select-sm">
                                <option value="">Semua kode barang</option>
                                @foreach ($itemCodes as $code)
                                    <option value="{{ $code }}">{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="input-group input-group-sm search-input-wrap">
                        <span class="input-group-text border-end-0 bg-white">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" id="bundle-filter-input"
                            class="form-control form-control-sm border-start-0 text-uppercase"
                            placeholder="Kode / bundle / lot.." autocomplete="off">
                    </div>
                </div>
            </div>

            @error('lines')
                <div class="alert alert-danger py-1 small mb-2">
                    {{ $message }}
                </div>
            @enderror

            {{-- LIST BUNDLES --}}
            <div class="table-wrap">
                <table class="table table-sm align-middle mono table-sewing-pickup mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="text-center">#</th>
                            <th style="width: 130px;">Bundle</th>
                            <th style="width: 160px;">Item Jadi</th>
                            <th style="width: 140px;">Lot</th>
                            <th style="width: 110px;" class="text-end">Cutting</th>
                            <th style="width: 110px;" class="text-end">Ready</th>
                            <th style="width: 130px;" class="text-end">Pickup</th>
                            <th style="width: 80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($displayBundles as $idx => $b)
                            @php
                                $qc = $b->qcResults
                                    ? $b->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first()
                                    : null;

                                $oldLine = $oldLines[$idx] ?? null;

                                // sekarang ambil dari accessor / computed
                                $qtyRemain = (float) ($b->computed_qty_ready ?? $b->qty_ready_for_sewing);

                                if ($qtyRemain <= 0) {
                                    continue;
                                }

                                $defaultQtyPickup = $oldLine['qty_bundle'] ?? null;

                                if ($defaultQtyPickup === null && $preselectedBundleId == $b->id) {
                                    $defaultQtyPickup = $qtyRemain;
                                }

                                $oldQtyName = 'lines.' . $idx . '.qty_bundle';

                                $cutDateObj =
                                    $b->cuttingJob?->cutting_date ??
                                    ($b->cuttingJob?->cut_date ?? $b->cuttingJob?->created_at);
                                $cutDateLabel = $cutDateObj ? $cutDateObj->format('d/m/Y') : '-';

                                $lotCode = $b->cuttingJob?->lot?->code;
                            @endphp

                            <tr class="bundle-row bundle-card-row row-empty" data-row-index="{{ $idx }}"
                                data-qty-ready="{{ $qtyRemain }}" data-bundle-code="{{ $b->bundle_code }}"
                                data-item-code="{{ $b->finishedItem?->code }}"
                                data-item-name="{{ $b->finishedItem?->name }}" data-lot-code="{{ $lotCode }}">

                                <td class="d-none">
                                    <input type="hidden" name="lines[{{ $idx }}][bundle_id]"
                                        value="{{ $b->id }}">
                                </td>

                                {{-- DESKTOP --}}
                                <td class="d-none d-md-table-cell td-desktop-only text-center">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <input type="checkbox" class="form-check-input row-check"
                                            data-row-index="{{ $idx }}">
                                        <span class="small text-muted">{{ $loop->iteration }}</span>
                                    </div>
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only">
                                    <span class="fw-semibold">{{ $b->bundle_code }}</span>
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only">
                                    <span class="fw-bold">
                                        {{ $b->finishedItem?->code ?? '-' }}
                                    </span>
                                    <div class="small text-muted">
                                        {{ $b->finishedItem?->name ?? '' }}
                                    </div>
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only">
                                    {{ $b->cuttingJob?->lot?->item?->code ?? '-' }}
                                    @if ($b->cuttingJob?->lot)
                                        <span class="badge-soft bg-light border text-muted ms-1">
                                            {{ $b->cuttingJob->lot->code }}
                                        </span>
                                    @endif
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only text-end">
                                    {{ number_format($b->qty_pcs, 2, ',', '.') }}
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only text-end">
                                    <span class="qty-ready-pill">
                                        {{ number_format($qtyRemain, 2, ',', '.') }}
                                    </span>
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only text-end">
                                    <input type="number" step="0.01" min="0" inputmode="decimal"
                                        name="lines[{{ $idx }}][qty_bundle]"
                                        class="form-control form-control-sm text-end qty-input @error($oldQtyName) is-invalid @enderror"
                                        value="{{ old($oldQtyName, $defaultQtyPickup) }}">
                                    @error($oldQtyName)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only text-end">
                                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 btn-pick"
                                        data-row-index="{{ $idx }}">
                                        Max
                                    </button>
                                </td>

                                {{-- MOBILE CARD --}}
                                <td class="td-mobile-extra" colspan="8">
                                    <div class="mobile-row-header">
                                        <div class="mobile-row-header-left">
                                            <div class="mobile-row-header-topline">
                                                <span class="row-index">#{{ $loop->iteration }}</span>
                                                <span class="item-code mono">
                                                    {{ $b->finishedItem?->code ?? '-' }}
                                                </span>
                                            </div>
                                            @if ($b->finishedItem?->name)
                                                <div class="item-name text-truncate">
                                                    {{ $b->finishedItem?->name }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="mobile-row-header-right">
                                            <div class="qty-ready-label">Qty Ready</div>
                                            <div class="qty-ready-value">
                                                <span class="qty-ready-pill">
                                                    {{ number_format($qtyRemain, 2, ',', '.') }}
                                                </span>
                                            </div>

                                            <div class="mobile-row-footer-left mt-1">
                                                <div class="pickup-label">
                                                    Pickup (maks {{ number_format($qtyRemain, 2, ',', '.') }})
                                                </div>
                                                <input type="number" step="0.01" min="0"
                                                    inputmode="decimal"
                                                    class="form-control form-control-sm qty-input @error($oldQtyName) is-invalid @enderror"
                                                    value="{{ old($oldQtyName, $defaultQtyPickup) }}"
                                                    placeholder="Isi pickup">
                                                @error($oldQtyName)
                                                    <div class="invalid-feedback">
                                                        {{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    {{-- MOBILE META: tgl cutting & qty cutting --}}
                                    <div class="mobile-row-meta">
                                        <div class="mobile-row-meta-left">
                                            <div class="mobile-row-meta-label">Tgl Cutting</div>
                                            <div class="mobile-row-meta-value">
                                                {{ $cutDateLabel }}
                                            </div>
                                        </div>
                                        <div class="mobile-row-meta-left text-end">
                                            <div class="mobile-row-meta-label">Cutting</div>
                                            <div class="mobile-row-meta-value">
                                                {{ number_format($b->qty_pcs, 2, ',', '.') }} pcs
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted small py-3">
                                    Belum ada bundle hasil QC Cutting dengan qty ready &gt; 0.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

{{-- FLOATING ACTION (MOBILE): SUBMIT SHORTCUT --}}
<div class="bundle-actions-fab d-md-none" aria-label="Aksi sewing pickup">
    <button type="button" id="bundle-submit-shortcut" class="bundle-submit-fab">
        <span class="bi bi-person-check"></span>
        <span class="text-white">Pilih Penjahit</span>
    </button>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rowsArr = Array.from(document.querySelectorAll('.bundle-row'));

            const summaryBundlesSpan = document.getElementById('summary-selected-bundles');
            const summaryQtySpan = document.getElementById('summary-selected-qty');
            const searchInput = document.getElementById('bundle-filter-input');
            const toggleOnlyPicked = document.getElementById('toggle-only-picked');
            const itemCodeSelect = document.getElementById('item-code-select');
            const itemCodeSelectWrap = document.getElementById('item-code-select-wrap');

            const submitBtn = document.getElementById('btn-submit-main');
            const submitLabel = document.getElementById('btn-submit-label');
            const submitShortcutBtn = document.getElementById('bundle-submit-shortcut');

            let state = {
                activeItemCode: '',
                showOnlyPickedDesktop: false,
            };

            let searchTimer = null;

            const isMobile = () => window.innerWidth < 768;

            let nf;
            try {
                nf = new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            } catch (e) {
                nf = {
                    format: num => (num || 0).toFixed(2)
                };
            }

            const normalizeText = v => (v || '').toString().trim().toUpperCase();

            function rowHasPickup(row) {
                const input = row.querySelector('input.qty-input');
                const current = parseFloat(input?.value || '0');
                return current > 0;
            }

            function clampToReady(input, row) {
                if (!row) return;
                const max = parseFloat(row.dataset.qtyReady || '0') || 0;
                let val = parseFloat(input.value || '0') || 0;

                if (val > max) {
                    val = max;
                    input.value = max > 0 ? max : '';
                    input.classList.add('border-warning');
                    setTimeout(() => input.classList.remove('border-warning'), 400);
                }

                if (val < 0) {
                    input.value = '';
                }
            }

            function applyRowVisibility() {
                const term = normalizeText(searchInput?.value || '');
                const itemCodeFilter = normalizeText(state.activeItemCode);

                rowsArr.forEach(row => {
                    const bundle = normalizeText(row.dataset.bundleCode);
                    const itemCode = normalizeText(row.dataset.itemCode);
                    const itemName = normalizeText(row.dataset.itemName);
                    const lotCode = normalizeText(row.dataset.lotCode);

                    const haystack = [bundle, itemCode, itemName, lotCode].join(' ');
                    const matchSearch = !term || haystack.includes(term);
                    const matchItemCode = !itemCodeFilter || itemCode === itemCodeFilter;
                    const inCart = rowHasPickup(row);

                    let visible = matchSearch && matchItemCode;

                    if (!isMobile()) {
                        const pickedOk = !state.showOnlyPickedDesktop || inCart;
                        visible = visible && pickedOk;
                    }

                    row.style.display = visible ? '' : 'none';
                });
            }

            function updateSubmitButtons(pickedBundles, totalPickupQty) {
                const canSubmit = pickedBundles > 0 && totalPickupQty > 0;

                if (submitBtn && submitLabel) {
                    submitBtn.disabled = !canSubmit;
                    submitLabel.textContent = canSubmit ? 'Pilih Penjahit' : 'Belum Ambil';
                }

                if (submitShortcutBtn) {
                    submitShortcutBtn.disabled = !canSubmit;
                }
            }

            function recalcSummaryAndUI() {
                let pickedBundles = 0;
                let totalPickupQty = 0;

                rowsArr.forEach(row => {
                    const input = row.querySelector('input.qty-input');
                    if (!input) return;
                    const current = parseFloat(input.value || '0');
                    if (current > 0) {
                        pickedBundles++;
                        totalPickupQty += current;
                    }
                });

                if (summaryBundlesSpan) summaryBundlesSpan.textContent = pickedBundles.toString();
                if (summaryQtySpan) summaryQtySpan.textContent = nf.format(totalPickupQty);

                updateSubmitButtons(pickedBundles, totalPickupQty);
                applyRowVisibility();
            }

            // DESKTOP toggle only picked
            toggleOnlyPicked?.addEventListener('change', function() {
                state.showOnlyPickedDesktop = !!this.checked;
                applyRowVisibility();
            });

            // SUBMIT SHORTCUT
            if (submitShortcutBtn && submitBtn) {
                submitShortcutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!this.disabled) submitBtn.click();
                });
            }

            // FILTER KODE BARANG
            itemCodeSelect?.addEventListener('change', function() {
                state.activeItemCode = this.value || '';
                recalcSummaryAndUI();
            });

            // FOCAL POINT SELECT di mobile
            if (isMobile() && itemCodeSelectWrap) {
                setTimeout(() => {
                    itemCodeSelectWrap.classList.add('focal-pulse');
                    setTimeout(
                        () => itemCodeSelectWrap.classList.remove('focal-pulse'),
                        1200
                    );
                }, 250);
            }

            // SEARCH
            searchInput?.addEventListener('input', function() {
                const cursorPos = this.selectionStart;
                this.value = this.value.toUpperCase();
                this.setSelectionRange(cursorPos, cursorPos);

                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    recalcSummaryAndUI();
                }, 120);
            });

            // PER-ROW BEHAVIOR
            rowsArr.forEach(row => {
                const qtyReady = parseFloat(row.dataset.qtyReady || '0');
                const qtyInputs = row.querySelectorAll('input.qty-input');
                const pickButtons = row.querySelectorAll('.btn-pick');
                const rowChecks = row.querySelectorAll('.row-check');

                if (!qtyInputs.length) return;

                const desktopInput = qtyInputs[0];
                const mobileInput = qtyInputs[1] || null;

                const getCurrentQty = () => parseFloat(desktopInput.value || '0');
                const isPicked = () => getCurrentQty() > 0;

                function syncInputsFromDesktop() {
                    if (mobileInput) mobileInput.value = desktopInput.value;
                }

                function syncDesktopFromMobile() {
                    if (mobileInput) desktopInput.value = mobileInput.value;
                }

                function updateVisual() {
                    const picked = isPicked();
                    rowChecks.forEach(chk => chk.checked = picked);

                    row.classList.toggle('row-picked', picked);
                    row.classList.toggle('is-selected', picked);
                    row.classList.toggle('row-empty', !picked);
                }

                function applyFromState(picked) {
                    const nextQty = picked ? qtyReady : 0;
                    desktopInput.value = nextQty > 0 ? nextQty : '';
                    if (mobileInput) mobileInput.value = desktopInput.value;

                    updateVisual();
                    recalcSummaryAndUI();
                }

                function togglePicked() {
                    const nextState = !isPicked();
                    applyFromState(nextState);
                }

                row.addEventListener('click', function(e) {
                    if (e.target.tagName === 'INPUT' || e.target.closest('button')) return;
                    togglePicked();
                });

                pickButtons.forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        applyFromState(true);
                    });
                });

                rowChecks.forEach(chk => {
                    chk.addEventListener('change', function(e) {
                        e.stopPropagation();
                        applyFromState(this.checked);
                    });
                });

                desktopInput.addEventListener('focus', function() {
                    this.select();
                    this.classList.add('qty-input-active');
                });

                desktopInput.addEventListener('blur', function() {
                    this.classList.remove('qty-input-active');
                    clampToReady(this, row);
                    syncInputsFromDesktop();
                    updateVisual();
                    recalcSummaryAndUI();
                });

                desktopInput.addEventListener('input', function() {
                    syncInputsFromDesktop();
                    updateVisual();
                    recalcSummaryAndUI();
                });

                if (mobileInput) {
                    mobileInput.addEventListener('focus', function() {
                        this.select();
                        this.classList.add('qty-input-active');

                        if (isMobile()) {
                            const inputEl = this;
                            setTimeout(function() {
                                try {
                                    inputEl.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'center',
                                        inline: 'nearest'
                                    });
                                } catch (e) {}
                            }, 180);
                        }
                    });

                    mobileInput.addEventListener('blur', function() {
                        this.classList.remove('qty-input-active');
                        clampToReady(this, row);
                        syncDesktopFromMobile();
                        updateVisual();
                        recalcSummaryAndUI();
                    });

                    mobileInput.addEventListener('input', function() {
                        syncDesktopFromMobile();
                        updateVisual();
                        recalcSummaryAndUI();
                    });
                }

                // inisialisasi awal (kalau ada old value)
                clampToReady(desktopInput, row);
                syncInputsFromDesktop();
                updateVisual();
            });

            // Auto fokus ke search di desktop
            if (!isMobile() && searchInput) {
                searchInput.focus();
            }

            // Scroll ke input pertama yang error (kalau ada)
            const firstInvalid = document.querySelector('.qty-input.is-invalid');
            if (firstInvalid) {
                setTimeout(() => {
                    firstInvalid.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    firstInvalid.focus();
                }, 200);
            }

            // INIT
            recalcSummaryAndUI();
        });
    </script>
@endpush
