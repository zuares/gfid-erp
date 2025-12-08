{{-- resources/views/production/sewing_pickups/_bundle_picker.blade.php --}}

@push('head')
    <style>
        :root {
            --card-radius-lg: 16px;
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding-inline: .9rem;
        }

        /* background lembut */
        body[data-theme="light"] .page-wrap {
            background: linear-gradient(to bottom,
                    #f4f5fb 0,
                    #f7f8fc 30%,
                    #f9fafb 100%);
        }

        .card {
            background: var(--card);
            border-radius: var(--card-radius-lg);
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .card-section {
            padding: 1rem 1.1rem;
        }

        @media (min-width: 768px) {
            .card-section {
                padding: 1.1rem 1.4rem;
            }
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .84rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .14rem .5rem;
            font-size: .7rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        /* ====== HEADER PAGE ====== */
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .header-title {
            display: flex;
            flex-direction: column;
            gap: .1rem;
        }

        .header-title h1 {
            font-size: 1.05rem;
            font-weight: 700;
        }

        .header-subtitle {
            font-size: .8rem;
            color: var(--muted);
        }

        .btn-header-secondary {
            border-radius: 999px;
            padding-inline: .7rem;
            padding-block: .35rem;
            font-size: .8rem;
        }

        .field-block {
            display: flex;
            flex-direction: column;
            gap: .2rem;
        }

        .field-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 600;
            color: var(--muted);
        }

        .field-input-sm {
            font-size: .86rem;
        }

        .field-static {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .42rem .85rem;
            border-radius: 999px;
            background: rgba(248, 250, 252, 0.96);
            border: 1px solid rgba(148, 163, 184, 0.45);
            font-size: .82rem;
            max-width: 100%;
        }

        .field-static .code {
            font-weight: 600;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .field-static .name {
            color: var(--muted);
            font-size: .8rem;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        .summary-chip {
            border-radius: 999px;
            padding: .16rem .7rem;
            font-size: .74rem;
            background: rgba(148, 163, 184, 0.10);
        }

        .summary-selected {
            font-size: .78rem;
            color: var(--muted);
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            align-items: center;
        }

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

        /* ========= SEARCH & COLOR FILTER SECTION ========= */
        .search-filter-section {
            margin-top: .75rem;
            margin-bottom: .5rem;
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }

        .search-filter-top {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem .75rem;
            align-items: center;
            justify-content: space-between;
        }

        .search-label {
            font-size: .75rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
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

        .color-filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
        }

        .color-filter-chip {
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.7);
            padding: .14rem .52rem;
            font-size: .7rem;
            font-weight: 600;
            text-transform: uppercase;
            background: rgba(248, 250, 252, 0.95);
            color: #4b5563;
            cursor: pointer;
            user-select: none;
            display: inline-flex;
            align-items: center;
            gap: .16rem;
            transition:
                background-color .15s ease,
                border-color .15s ease,
                color .15s ease,
                box-shadow .15s ease,
                transform .08s ease;
        }

        .color-filter-chip:hover {
            border-color: rgba(59, 130, 246, .7);
            color: #1d4ed8;
            box-shadow:
                0 2px 6px rgba(15, 23, 42, 0.12);
            transform: translateY(-1px);
        }

        .color-filter-chip.is-active {
            background: linear-gradient(135deg, #0d6efd 0%, #2563eb 60%, #1d4ed8 100%);
            border-color: rgba(37, 99, 235, 1);
            color: #f9fafb;
            box-shadow:
                0 4px 10px rgba(37, 99, 235, .45);
        }

        .color-filter-chip .dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            opacity: .85;
        }

        .dot-blk {
            background: #111827;
        }

        .dot-nvy {
            background: #1e3a8a;
        }

        .dot-mst {
            background: #d97706;
        }

        .dot-abt {
            background: #4b5563;
        }

        .dot-wht {
            background: #e5e7eb;
            box-shadow: 0 0 0 1px rgba(55, 65, 81, .45) inset;
        }

        .dot-bbl {
            background: #60a5fa;
        }

        .selection-toggle-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: .78rem;
            color: var(--muted);
            margin-top: .2rem;
        }

        .selection-toggle-left {
            font-size: .74rem;
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

        /* BUNDLE CARD STATE (desktop & mobile) */
        .bundle-card {
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(255, 255, 255, 0.96);
            box-shadow:
                0 6px 16px rgba(15, 23, 42, 0.10),
                0 0 0 1px rgba(15, 23, 42, 0.02);
            transition:
                background-color .18s ease,
                box-shadow .18s ease,
                border-color .18s ease,
                transform .1s ease;
        }

        .bundle-card:hover {
            transform: translateY(-1px);
            box-shadow:
                0 10px 24px rgba(15, 23, 42, 0.14),
                0 0 0 1px rgba(148, 163, 184, 0.25);
        }

        body[data-theme="dark"] .bundle-card {
            background: rgba(15, 23, 42, 0.96);
            border-color: rgba(51, 65, 85, 0.9);
            box-shadow:
                0 8px 20px rgba(0, 0, 0, 0.55),
                0 0 0 1px rgba(30, 64, 175, 0.6);
        }

        /* STATE: TERPILIH */
        .bundle-card.is-selected {
            border-color: rgba(59, 130, 246, 0.95);
            box-shadow:
                0 0 0 2px rgba(191, 219, 254, 0.98),
                0 16px 32px rgba(37, 99, 235, 0.22);
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.16) 0,
                    rgba(255, 255, 255, 0.98) 50%);
            transform: translateY(-1px);
        }

        body[data-theme="dark"] .bundle-card.is-selected {
            background: radial-gradient(circle at top left,
                    rgba(37, 99, 235, 0.45) 0,
                    rgba(15, 23, 42, 0.96) 55%);
            border-color: rgba(147, 197, 253, 0.95);
            box-shadow:
                0 0 0 2px rgba(59, 130, 246, 0.9),
                0 20px 36px rgba(15, 23, 42, 0.85);
        }

        .bundle-card.is-selected:hover {
            transform: translateY(-2px);
        }

        .row-empty {
            box-shadow: inset 3px 0 0 rgba(148, 163, 184, .35);
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

        /* ============ MOBILE (<= 767.98px) ============ */
        @media (max-width: 767.98px) {

            .page-wrap {
                padding-bottom: 7.5rem;
                overflow-x: hidden;
            }

            body.keyboard-open .page-wrap {
                padding-bottom: 13rem;
            }

            .table-wrap {
                overflow-x: visible;
            }

            .search-filter-section {
                margin-top: .9rem;
                margin-bottom: .7rem;
            }

            .search-filter-top {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input-wrap {
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
                background: rgba(255, 255, 255, 0.96);
                cursor: pointer;
                box-shadow:
                    0 8px 24px rgba(15, 23, 42, 0.10),
                    0 0 0 1px rgba(15, 23, 42, 0.02);
                overflow: hidden;
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

            .card {
                border-radius: 15px;
                box-shadow:
                    0 10px 26px rgba(15, 23, 42, 0.10),
                    0 0 0 1px rgba(15, 23, 42, 0.02);
            }

            .card-section {
                padding: .85rem .9rem;
            }

            .header-row {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-header-secondary {
                width: 100%;
                justify-content: center;
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

            .mobile-check-wrap,
            .mobile-row-meta-bundle {
                display: none !important;
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

            .form-footer {
                position: fixed;
                right: .9rem;
                bottom: 4.2rem;
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
                background: linear-gradient(135deg, #0d6efd 0%, #2563eb 60%, #1d4ed8 100%);
                border: none;
                display: inline-flex;
                align-items: center;
                gap: .35rem;
            }

            .form-footer .btn-outline-secondary {
                font-size: .78rem;
                padding-inline: .7rem;
                padding-block: .3rem;
                background: rgba(248, 250, 252, 0.96);
                border-color: rgba(148, 163, 184, .7);
                display: inline-flex;
                align-items: center;
                gap: .25rem;
            }

            .gudang-section {
                display: none !important;
            }
        }

        @media (min-width: 768px) {
            .td-mobile-extra {
                display: none !important;
            }
        }
    </style>
@endpush

<div class="card mb-3">
    <div class="card-section">
        @php
            $oldLines = old('lines', []);
            $preselectedBundleId = request('bundle_id');

            $displayBundles = $preselectedBundleId ? $bundles->where('id', (int) $preselectedBundleId) : $bundles;

            $displayBundles = $displayBundles
                ->filter(function ($b) {
                    $qtyOk = (float) ($b->qty_cutting_ok ?? 0);
                    $qtyRemain = (float) ($b->qty_remaining_for_sewing ?? $qtyOk);
                    return $qtyRemain > 0;
                })
                ->values();

            $totalBundlesReady = $displayBundles->count();
            $totalQtyReady = $displayBundles->sum(function ($b) {
                $qtyOk = (float) ($b->qty_cutting_ok ?? 0);
                return (float) ($b->qty_remaining_for_sewing ?? $qtyOk);
            });
        @endphp

        {{-- HEADER --}}
        <div class="filter-header mb-2">
            <div class="filter-header-left">
                <div class="filter-header-left-title">
                    <i class="bi bi-funnel text-muted"></i>
                    <h2>Pilih bundles yang mau dijahit</h2>
                </div>
                <div class="summary-selected">
                    <span class="summary-chip">
                        <span id="summary-selected-bundles">0</span> bundle dipilih
                    </span>
                    <span class="summary-chip">
                        Total pickup: <span id="summary-selected-qty">0,00</span> pcs
                    </span>
                    <span class="summary-chip">
                        Ready: {{ number_format($totalQtyReady, 2, ',', '.') }} pcs
                    </span>
                    <span class="summary-chip d-none d-md-inline">
                        Total bundle ready: {{ $totalBundlesReady }}
                    </span>
                </div>
            </div>
        </div>

        {{-- SEARCH + FILTER WARNA --}}
        <div class="search-filter-section">
            <div class="search-filter-top">
                <div>
                    <div class="search-label">
                        Cari Bundles
                    </div>

                </div>
                <div class="input-group input-group-sm search-input-wrap">
                    <span class="input-group-text border-end-0 bg-white">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" id="bundle-filter-input"
                        class="form-control form-control-sm border-start-0 text-uppercase"
                        placeholder="Masukan Kode Barang.. " autocomplete="off">
                </div>
            </div>

            <div class="color-filter-row mt-1">
                <button type="button" class="color-filter-chip" data-color="">
                    Semua
                </button>
                <button type="button" class="color-filter-chip" data-color="BLK">
                    <span class="dot dot-blk"></span> BLK
                </button>
                <button type="button" class="color-filter-chip" data-color="NVY">
                    <span class="dot dot-nvy"></span> NVY
                </button>
                <button type="button" class="color-filter-chip" data-color="MST">
                    <span class="dot dot-mst"></span> MST
                </button>
                <button type="button" class="color-filter-chip" data-color="ABT">
                    <span class="dot dot-abt"></span> ABT
                </button>
                <button type="button" class="color-filter-chip" data-color="WHT">
                    <span class="dot dot-wht"></span> WHT
                </button>
                <button type="button" class="color-filter-chip" data-color="BBL">
                    <span class="dot dot-bbl"></span> BBL
                </button>
            </div>

            <div class="selection-toggle-row">
                <div class="selection-toggle-left">
                    Filter aktif:
                    <span id="active-color-label" class="fw-semibold">Semua warna</span>
                </div>
                <div class="selection-toggle-right">
                    <label class="form-check-label" for="toggle-only-picked">
                        Tampilkan hanya bundle yang sudah diambil
                    </label>
                    <input type="checkbox" class="form-check-input" id="toggle-only-picked">
                </div>
            </div>
        </div>

        @error('lines')
            <div class="alert alert-danger py-1 small mb-2">
                {{ $message }}
            </div>
        @enderror

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

                            $qtyOk = (float) ($b->qty_cutting_ok ?? ($qc?->qty_ok ?? $b->qty_pcs));
                            $qtyRemain = (float) ($b->qty_remaining_for_sewing ?? $qtyOk);

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

                        <tr class="bundle-row bundle-card row-empty" data-row-index="{{ $idx }}"
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
                                    Pick
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
                                            <input type="number" step="0.01" min="0" inputmode="decimal"
                                                class="form-control form-control-sm qty-input @error($oldQtyName) is-invalid @enderror"
                                                value="{{ old($oldQtyName, $defaultQtyPickup) }}"
                                                placeholder="Masukkan ambil jahit">
                                            @error($oldQtyName)
                                                <div class="invalid-feedback">
                                                    {{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mobile-check-wrap">
                                            <input type="checkbox" class="form-check-input row-check"
                                                data-row-index="{{ $idx }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="mobile-row-meta-bundle">
                                    <div class="mobile-row-meta-left">
                                        <div class="mobile-row-meta-label">Bundle</div>
                                        <div class="mobile-row-meta-value">
                                            <span class="mono">{{ $b->bundle_code }}</span>
                                        </div>
                                    </div>
                                    <div class="mobile-row-meta-left text-end">
                                        <div class="mobile-row-meta-label">Lot</div>
                                        <div class="mobile-row-meta-value">
                                            @if ($b->cuttingJob?->lot)
                                                <span class="mono">{{ $b->cuttingJob->lot->code }}</span>
                                                <span class="text-muted small">
                                                    ({{ $b->cuttingJob->lot->item?->code }})
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

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
                                Belum ada bundle hasil QC Cutting dengan qty ready
                                &gt; 0.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.bundle-row');
            const rowsArr = Array.from(rows);
            const summaryBundlesSpan = document.getElementById('summary-selected-bundles');
            const summaryQtySpan = document.getElementById('summary-selected-qty');
            const searchInput = document.getElementById('bundle-filter-input');
            const colorChips = document.querySelectorAll('.color-filter-chip');
            const activeColorLabel = document.getElementById('active-color-label');
            const toggleOnlyPicked = document.getElementById('toggle-only-picked');

            const submitBtn = document.getElementById('btn-submit-main');
            const submitLabel = document.getElementById('btn-submit-label');

            let activeColor = ''; // BLK / NVY / MST / ABT / WHT / BBL
            let showOnlyPicked = false;
            let searchTimer = null;

            // formatter
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

            function setKeyboardOpen(isOpen) {
                if (isOpen) {
                    document.body.classList.add('keyboard-open');
                } else {
                    document.body.classList.remove('keyboard-open');
                }
            }

            function normalizeText(value) {
                return (value || '').toString().trim().toUpperCase();
            }

            function rowHasPickup(row) {
                const qtyInputs = row.querySelectorAll('input.qty-input');
                if (!qtyInputs.length) return false;
                const current = parseFloat(qtyInputs[0].value || '0');
                return current > 0;
            }

            function applyRowVisibility() {
                const term = normalizeText(searchInput ? searchInput.value : '');
                const color = normalizeText(activeColor);

                rowsArr.forEach(function(row) {
                    const bundle = normalizeText(row.dataset.bundleCode);
                    const itemCode = normalizeText(row.dataset.itemCode);
                    const itemName = normalizeText(row.dataset.itemName);
                    const lotCode = normalizeText(row.dataset.lotCode);

                    const haystack = [bundle, itemCode, itemName, lotCode].join(' ');

                    const matchSearch = !term || haystack.includes(term);
                    const matchColor = !color || haystack.includes(color);

                    const pickedOk = !showOnlyPicked || rowHasPickup(row);

                    row.style.display = (matchSearch && matchColor && pickedOk) ? '' : 'none';
                });
            }

            function updateSubmitButtonState(pickedBundles, totalPickupQty) {
                if (!submitBtn || !submitLabel) return;

                const canSubmit = pickedBundles > 0 && totalPickupQty > 0;

                submitBtn.disabled = !canSubmit;
                submitLabel.textContent = canSubmit ? 'Pilih Penjahit' : 'Belum Ambil';
            }

            function updateGlobalSummary() {
                if (!summaryBundlesSpan || !summaryQtySpan) return;

                let pickedBundles = 0;
                let totalPickupQty = 0;

                rowsArr.forEach(function(row) {
                    const qtyInputs = row.querySelectorAll('input.qty-input');
                    if (!qtyInputs.length) return;

                    const current = parseFloat(qtyInputs[0].value || '0');
                    if (current > 0) {
                        pickedBundles += 1;
                        totalPickupQty += current;
                    }
                });

                summaryBundlesSpan.textContent = pickedBundles.toString();
                summaryQtySpan.textContent = nf.format(totalPickupQty);

                updateSubmitButtonState(pickedBundles, totalPickupQty);
                applyRowVisibility();
            }

            // ========== COLOR FILTER HANDLING ==========
            function updateActiveColorLabel() {
                if (!activeColorLabel) return;

                if (!activeColor) {
                    activeColorLabel.textContent = 'Semua warna';
                    return;
                }

                activeColorLabel.textContent = 'Hanya warna ' + activeColor;
            }

            function setActiveColor(color) {
                activeColor = color || '';

                colorChips.forEach(chip => {
                    const chipColor = normalizeText(chip.dataset.color || '');
                    const isActive = chipColor === normalizeText(activeColor);

                    if (!activeColor && chipColor === '') {
                        chip.classList.add('is-active');
                    } else if (isActive) {
                        chip.classList.add('is-active');
                    } else {
                        chip.classList.remove('is-active');
                    }
                });

                updateActiveColorLabel();
                applyRowVisibility();
            }

            colorChips.forEach(chip => {
                chip.addEventListener('click', function() {
                    const clickedColor = normalizeText(this.dataset.color || '');
                    if (clickedColor && clickedColor === normalizeText(activeColor)) {
                        setActiveColor('');
                    } else {
                        setActiveColor(clickedColor);
                    }
                });
            });

            // set awal: "Semua" aktif
            setActiveColor('');

            // ========== TOGGLE ONLY PICKED ==========
            if (toggleOnlyPicked) {
                toggleOnlyPicked.addEventListener('change', function() {
                    showOnlyPicked = !!this.checked;
                    applyRowVisibility();
                });
            }

            // ========== SEARCH HANDLING ==========
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    // force uppercase visual
                    const cursorPos = this.selectionStart;
                    this.value = this.value.toUpperCase();
                    this.setSelectionRange(cursorPos, cursorPos);

                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(() => {
                        applyRowVisibility();
                    }, 120);
                });
            }

            // ========== PER-ROW BEHAVIOR ==========
            rowsArr.forEach(function(row) {
                const qtyReady = parseFloat(row.dataset.qtyReady || '0');
                const qtyInputs = row.querySelectorAll('input.qty-input');
                const pickButtons = row.querySelectorAll('.btn-pick');
                const rowChecks = row.querySelectorAll('.row-check');

                if (!qtyInputs.length) return;

                const desktopInput = qtyInputs[0];
                const mobileInput = qtyInputs.length > 1 ? qtyInputs[1] : null;

                function getCurrentQty() {
                    return parseFloat(desktopInput.value || '0');
                }

                function isPicked() {
                    return getCurrentQty() > 0;
                }

                function syncInputsFromDesktop() {
                    const val = desktopInput.value;
                    if (mobileInput) {
                        mobileInput.value = val;
                    }
                }

                function syncDesktopFromMobile() {
                    if (!mobileInput) return;
                    desktopInput.value = mobileInput.value;
                }

                function updateVisual() {
                    const picked = isPicked();

                    rowChecks.forEach(function(chk) {
                        chk.checked = picked;
                    });

                    if (picked) {
                        row.classList.add('row-picked', 'is-selected');
                        row.classList.remove('row-empty');
                    } else {
                        row.classList.remove('row-picked', 'is-selected');
                        row.classList.add('row-empty');
                    }
                }

                function applyFromState(picked) {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    const nextQty = picked ? qtyReady : 0;

                    desktopInput.value = nextQty > 0 ? nextQty : '';
                    if (mobileInput) {
                        mobileInput.value = desktopInput.value;
                    }

                    updateVisual();
                    updateGlobalSummary();

                    window.scrollTo({
                        top: scrollTop,
                        behavior: 'auto'
                    });
                }

                function togglePicked() {
                    const nextState = !isPicked();
                    applyFromState(nextState);
                }

                row.addEventListener('click', function(e) {
                    if (
                        e.target.tagName === 'INPUT' ||
                        e.target.closest('button')
                    ) {
                        return;
                    }
                    togglePicked();
                });

                pickButtons.forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        applyFromState(true);
                    });
                });

                rowChecks.forEach(function(chk) {
                    chk.addEventListener('change', function(e) {
                        e.stopPropagation();
                        applyFromState(chk.checked);
                    });
                });

                desktopInput.addEventListener('focus', function() {
                    this.select();
                    this.classList.add('qty-input-active');
                });

                desktopInput.addEventListener('blur', function() {
                    this.classList.remove('qty-input-active');
                    syncInputsFromDesktop();
                    updateVisual();
                    updateGlobalSummary();
                });

                desktopInput.addEventListener('input', function() {
                    syncInputsFromDesktop();
                    updateVisual();
                    updateGlobalSummary();
                });

                if (mobileInput) {
                    mobileInput.addEventListener('focus', function() {
                        this.select();
                        this.classList.add('qty-input-active');
                        setKeyboardOpen(true);

                        if (window.innerWidth < 768) {
                            const inputEl = this;
                            setTimeout(function() {
                                try {
                                    inputEl.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'center',
                                        inline: 'nearest'
                                    });
                                } catch (e) {
                                    const rect = inputEl.getBoundingClientRect();
                                    const absoluteTop = rect.top + window.pageYOffset - 140;
                                    window.scrollTo({
                                        top: absoluteTop,
                                        behavior: 'smooth'
                                    });
                                }
                            }, 180);
                        }
                    });

                    mobileInput.addEventListener('blur', function() {
                        this.classList.remove('qty-input-active');
                        setKeyboardOpen(false);
                        syncDesktopFromMobile();
                        updateVisual();
                        updateGlobalSummary();
                    });

                    mobileInput.addEventListener('input', function() {
                        syncDesktopFromMobile();
                        updateVisual();
                        updateGlobalSummary();
                    });
                }

                syncInputsFromDesktop();
                updateVisual();
            });

            const allQtyInputs = document.querySelectorAll('input.qty-input');
            allQtyInputs.forEach(function(inp) {
                inp.addEventListener('focus', function() {
                    if (window.innerWidth < 768) {
                        setKeyboardOpen(true);
                    }
                });
                inp.addEventListener('blur', function() {
                    setKeyboardOpen(false);
                });
            });

            updateGlobalSummary();
            applyRowVisibility();
        });
    </script>
@endpush
