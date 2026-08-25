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
        .bundle-picker-control-panel {
            display: flex;
            flex-direction: column;
            gap: .6rem;
            padding: .75rem;
            margin: -.25rem -.35rem .25rem;
            border: 1px solid rgba(148, 163, 184, .24);
            border-radius: 14px;
            background: #ffffff;
            box-shadow:
                0 12px 28px rgba(15, 23, 42, .12),
                0 0 0 1px rgba(255, 255, 255, .72);
        }

        body[data-theme="dark"] .bundle-picker-control-panel {
            background: #0f172a;
            border-color: rgba(71, 85, 105, .82);
            box-shadow:
                0 14px 30px rgba(0, 0, 0, .55),
                0 0 0 1px rgba(15, 23, 42, .9);
        }

        .pickup-control-meta {
            border-bottom: 1px dashed rgba(148, 163, 184, .36);
            padding-bottom: .65rem;
        }

        .pickup-control-meta .field-block {
            margin-bottom: 0;
        }

        .filter-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: .7rem;
            align-items: flex-start;
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

        .bundle-picker-control-panel .input-group-text,
        .bundle-picker-control-panel .form-control,
        .bundle-picker-control-panel .form-select {
            background-color: #ffffff;
        }

        body[data-theme="dark"] .bundle-picker-control-panel .input-group-text,
        body[data-theme="dark"] .bundle-picker-control-panel .form-control,
        body[data-theme="dark"] .bundle-picker-control-panel .form-select {
            background-color: #0f172a;
            color: #e5e7eb;
            border-color: rgba(71, 85, 105, .82);
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

        .selected-bundle-summary {
            border: 1px solid rgba(37, 99, 235, .22);
            border-radius: 10px;
            background: rgba(239, 246, 255, .72);
            padding: .48rem .58rem;
            display: flex;
            flex-direction: column;
            gap: .36rem;
        }

        body[data-theme="dark"] .selected-bundle-summary {
            background: rgba(30, 41, 59, .78);
            border-color: rgba(96, 165, 250, .28);
        }

        .selected-summary-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .selected-summary-title {
            display: inline-flex;
            align-items: center;
            gap: .28rem;
            font-size: .74rem;
            font-weight: 800;
            color: #1d4ed8;
        }

        body[data-theme="dark"] .selected-summary-title {
            color: #93c5fd;
        }

        .selected-summary-clear {
            border: 1px solid rgba(220, 38, 38, .22);
            border-radius: 999px;
            background: rgba(254, 242, 242, .86);
            color: #dc2626;
            font-size: .66rem;
            font-weight: 900;
            padding: .16rem .46rem;
            line-height: 1.2;
        }

        body[data-theme="dark"] .selected-summary-clear {
            background: rgba(127, 29, 29, .28);
            border-color: rgba(248, 113, 113, .30);
            color: #fca5a5;
        }

        .selected-summary-table-wrap {
            border: 1px solid rgba(148, 163, 184, .24);
            border-radius: 9px;
            overflow: auto;
            max-height: 132px;
            background: #ffffff;
            -webkit-overflow-scrolling: touch;
        }

        body[data-theme="dark"] .selected-summary-table-wrap {
            background: rgba(15, 23, 42, .96);
            border-color: rgba(71, 85, 105, .82);
        }

        .selected-summary-table {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
            font-size: .7rem;
        }

        .selected-summary-table th {
            background: rgba(248, 250, 252, .9);
            color: var(--muted);
            font-size: .58rem;
            font-weight: 900;
            letter-spacing: .06em;
            padding: .28rem .4rem;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(148, 163, 184, .20);
        }

        body[data-theme="dark"] .selected-summary-table th {
            background: rgba(30, 41, 59, .72);
        }

        .selected-summary-table td {
            padding: .3rem .4rem;
            border-bottom: 1px solid rgba(148, 163, 184, .14);
            vertical-align: middle;
        }

        .selected-summary-table tbody tr:last-child td {
            border-bottom: none;
        }

        .selected-summary-table tfoot td {
            background: rgba(248, 250, 252, .92);
            border-top: 1px solid rgba(148, 163, 184, .24);
            color: #0f172a;
            font-weight: 900;
            padding: .34rem .4rem;
        }

        body[data-theme="dark"] .selected-summary-table tfoot td {
            background: rgba(30, 41, 59, .74);
            color: #e5e7eb;
        }

        .selected-summary-table .item-code-cell {
            font-weight: 900;
            color: #1d4ed8;
        }

        body[data-theme="dark"] .selected-summary-table .item-code-cell {
            color: #93c5fd;
        }

        .selected-summary-remove {
            width: 22px;
            height: 22px;
            border: 1px solid rgba(220, 38, 38, .24);
            border-radius: 999px;
            background: rgba(254, 242, 242, .9);
            color: #dc2626;
            font-size: .82rem;
            font-weight: 800;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        body[data-theme="dark"] .selected-summary-remove {
            background: rgba(127, 29, 29, .28);
            color: #fca5a5;
            border-color: rgba(248, 113, 113, .30);
        }

        .selected-summary-empty {
            font-size: .78rem;
            color: var(--muted);
            font-weight: 600;
        }

        .sewing-supply-summary {
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 10px;
            background: #ffffff;
            padding: .5rem .58rem;
            display: flex;
            flex-direction: column;
            gap: .38rem;
        }

        body[data-theme="dark"] .sewing-supply-summary {
            background: rgba(15, 23, 42, .96);
            border-color: rgba(71, 85, 105, .82);
        }

        .sewing-supply-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
            font-size: .72rem;
            font-weight: 900;
        }

        .sewing-supply-title {
            color: #0f172a;
        }

        body[data-theme="dark"] .sewing-supply-title {
            color: #e5e7eb;
        }

        .sewing-supply-status {
            border-radius: 999px;
            padding: .12rem .48rem;
            font-size: .64rem;
            font-weight: 900;
            background: rgba(22, 163, 74, .10);
            color: #15803d;
        }

        .sewing-supply-status.is-danger {
            background: rgba(220, 38, 38, .10);
            color: #dc2626;
        }

        .sewing-supply-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .68rem;
        }

        .sewing-supply-table th,
        .sewing-supply-table td {
            padding: .28rem .35rem;
            border-bottom: 1px solid rgba(148, 163, 184, .14);
            vertical-align: middle;
        }

        .sewing-supply-table th {
            color: var(--muted);
            font-size: .56rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .06em;
            background: rgba(248, 250, 252, .88);
        }

        body[data-theme="dark"] .sewing-supply-table th {
            background: rgba(30, 41, 59, .72);
        }

        .sewing-supply-table tr:last-child td {
            border-bottom: none;
        }

        .supply-ok {
            color: #15803d;
            font-weight: 900;
        }

        .supply-short {
            color: #dc2626;
            font-weight: 900;
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

        .bundle-card-row.summary-focus {
            outline: 2px solid rgba(37, 99, 235, .75);
            outline-offset: 2px;
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

            .bundle-picker-control-panel {
                gap: .55rem;
                margin: -.15rem -.15rem .25rem;
                padding: .65rem;
            }

            .pickup-control-meta {
                padding-bottom: .55rem;
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
                border-radius: 12px;
                border: 1px solid rgba(148, 163, 184, 0.3);
                padding: .48rem .62rem .52rem;
                margin-bottom: .34rem;
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
                gap: .58rem;
                margin-bottom: .12rem;
            }

            .mobile-row-header-left {
                font-size: clamp(.74rem, 2.4vw, .86rem);
                display: flex;
                flex-direction: column;
                gap: .02rem;
                flex: 1;
                min-width: 0;
            }

            .mobile-row-header-topline {
                display: flex;
                align-items: center;
                gap: .4rem;
                min-width: 0;
                flex-wrap: wrap;
            }

            .mobile-row-header-left .row-index {
                font-size: clamp(.62rem, 1.9vw, .72rem);
                color: var(--muted);
                flex-shrink: 0;
            }

            /* ✅ KODE BARANG RESPONSIVE */
            .mobile-row-header-left .item-code {
                font-size: clamp(.84rem, 3.35vw, 1rem) !important;
                font-weight: 800 !important;
                color: #2563eb !important;
                letter-spacing: .12px;

                white-space: normal;
                max-width: 100%;
                overflow: visible;
                text-overflow: clip;
                word-break: break-word;
                flex: 1 1 auto;
            }

            .mobile-row-header-left .item-name {
                font-size: clamp(.72rem, 2.6vw, .84rem) !important;
                color: var(--muted) !important;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .mobile-row-header-right {
                text-align: right;
                font-size: clamp(.66rem, 2.3vw, .78rem);
                width: clamp(128px, 42vw, 180px);
                min-width: clamp(128px, 42vw, 180px);
                flex-shrink: 0;
            }

            .mobile-row-header-right .qty-ready-label {
                font-size: clamp(.56rem, 1.9vw, .66rem) !important;
                font-weight: 600;
                color: var(--muted);
                text-transform: uppercase;
                letter-spacing: .28px;
                margin-bottom: .02rem;
            }

            .mobile-row-header-right .qty-ready-value {
                display: flex;
                justify-content: flex-end;
            }

            .mobile-row-header-right .qty-ready-value .qty-ready-pill {
                background: rgba(37, 99, 235, 0.12) !important;
                color: #1d4ed8 !important;
                padding: .12rem .46rem !important;
                border-radius: 999px !important;
                font-size: clamp(.78rem, 3vw, .9rem) !important;
                font-weight: 800 !important;
                max-width: 100%;
                min-width: 0;
                text-align: center;
                display: inline-block;
            }

            .mobile-row-meta {
                font-size: clamp(.64rem, 2.25vw, .74rem);
                color: var(--muted);
                margin-bottom: .06rem;
                display: flex;
                justify-content: space-between;
                gap: .4rem;
            }

            .mobile-row-meta-left {
                flex: 1;
                min-width: 0;
            }

            .mobile-row-meta-label {
                font-size: clamp(.56rem, 1.9vw, .64rem);
                text-transform: uppercase;
                color: var(--muted);
                opacity: .9;
                letter-spacing: .08em;
            }

            .mobile-row-meta-value {
                font-size: clamp(.64rem, 2.25vw, .74rem);
                word-break: break-word;
            }

            .mobile-row-meta-value .mono {
                font-size: clamp(.72rem, 2.4vw, .82rem);
            }

            .mobile-row-footer-left .pickup-label {
                font-size: clamp(.62rem, 2.2vw, .72rem) !important;
                font-weight: 600 !important;
                color: #2563eb !important;
                margin-bottom: .04rem;
                letter-spacing: .17px;
                text-transform: uppercase;
            }

            .mobile-row-footer-left input.qty-input {
                width: 100% !important;
                min-width: 0;
                font-size: clamp(.82rem, 3vw, .94rem) !important;
                font-weight: 600 !important;
                padding-block: .24rem !important;
                border: 1.4px solid rgba(37, 99, 235, .45) !important;
                border-radius: 8px !important;
                box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .18) !important;
                text-align: center !important;
            }

            @media (max-width: 380px) {
                .mobile-row-header {
                    gap: .5rem;
                }

                .mobile-row-header-right {
                    width: clamp(120px, 44vw, 156px);
                    min-width: clamp(120px, 44vw, 156px);
                }
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
        ->filter(fn($b) => $b->computed_qty_ready > 0 || $b->status === 'cut')
        ->values();

    $totalBundlesReady = $displayBundles->count();
    $totalQtyReady = $displayBundles->sum(fn($b) => (float) $b->computed_qty_ready);

    $itemCodes = $displayBundles->pluck('finishedItem.code')->filter()->unique()->sort()->values();
@endphp

<div class="sewing-pickup-bundle-picker mb-3">
    <div class="card sewing-pickup-bundle-picker-card">
        <div class="card-section">

            <div class="bundle-picker-control-panel">
                {{-- DESKTOP: toggle hanya baris yang ada pickup --}}
                <div class="filter-header">
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
                                Cari item
                            </div>

                            <div class="item-code-select-wrap" id="item-code-select-wrap">
                                <select id="item-code-select" class="form-select form-select-sm">
                                    <option value="">Semua kode item</option>
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
                                placeholder="Kode item / lot.." autocomplete="off">
                        </div>
                    </div>
                </div>

                <div class="selected-bundle-summary" id="selected-bundle-summary">
                    <div class="selected-summary-head">
                        <div class="selected-summary-title">
                            <i class="bi bi-check2-square"></i>
                            <span>Item sudah dipilih</span>
                            <span class="badge bg-primary rounded-pill" id="selected-summary-count">0</span>
                        </div>
                        <button type="button" class="selected-summary-clear d-none" id="selected-summary-clear">
                            Bersihkan semua
                        </button>
                    </div>
                    <div class="selected-summary-table-wrap d-none" id="selected-summary-list">
                        <table class="selected-summary-table">
                            <thead>
                                <tr>
                                    <th style="width:44px;">No</th>
                                    <th>Kode Item</th>
                                    <th class="text-end" style="width:110px;">Qty</th>
                                    <th class="text-end" style="width:80px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="selected-summary-body"></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2">Total</td>
                                    <td class="text-end mono" id="selected-summary-total-qty">0,00</td>
                                    <td class="text-end mono">
                                        <span id="selected-summary-total-items">0</span> ikat
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="selected-summary-empty" id="selected-summary-empty">
                        Belum ada item yang diambil.
                    </div>
                </div>

                {{-- Ringkasan kelengkapan RM: hanya untuk owner (menyingkap kebutuhan & stok). --}}
                @if (auth()->user()?->isOwner())
                <div class="sewing-supply-summary d-none" id="sewing-supply-summary">
                    <div class="sewing-supply-head">
                        <span class="sewing-supply-title">Kelengkapan jahit dari RM</span>
                        <span class="sewing-supply-status" id="sewing-supply-status">Cukup</span>
                    </div>
                    <div class="table-responsive">
                        <table class="sewing-supply-table">
                            <thead>
                                <tr>
                                    <th>Material</th>
                                    <th class="text-end">PCS</th>
                                </tr>
                            </thead>
                            <tbody id="sewing-supply-body"></tbody>
                        </table>
                    </div>
                </div>
                @endif
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
                            <th style="width: 130px;">Tgl Cutting</th>
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

                                $isUnQCed = $b->status === 'cut';

                                if ($qtyRemain <= 0 && !$isUnQCed) {
                                    continue;
                                }

                                $defaultQtyPickup = $oldLine['qty_bundle'] ?? null;

                                if ($defaultQtyPickup === null && $preselectedBundleId == $b->id) {
                                    $defaultQtyPickup = $qtyRemain;
                                }

                                $oldQtyName = 'lines.' . $idx . '.qty_bundle';

                                $cutDateObj = $b->cuttingJob?->date ?? $b->cuttingJob?->created_at;
                                $cutDateLabel = $cutDateObj ? $cutDateObj->format('d/m') : '-';

                                $lotCode = $b->cuttingJob?->lot?->code;
                                $shortBundleCode = preg_replace('/^BND-\d{8}-/', '', $b->bundle_code);
                            @endphp

                            <tr class="bundle-row bundle-card-row row-empty {{ $isUnQCed ? 'opacity-50 is-unqced cursor-pointer' : '' }}" data-row-index="{{ $idx }}"
                                data-qty-ready="{{ $qtyRemain }}" data-bundle-code="{{ $b->bundle_code }}"
                                data-finished-item-id="{{ $b->finished_item_id }}"
                                data-item-code="{{ $b->finishedItem?->code }}"
                                data-item-name="{{ $b->finishedItem?->name }}" data-lot-code="{{ $lotCode }}"
                                data-detail-url="{{ route('production.cutting_jobs.show', $b->cutting_job_id) }}">

                                <td class="d-none">
                                    <input type="hidden" name="lines[{{ $idx }}][bundle_id]"
                                        value="{{ $b->id }}">
                                </td>

                                {{-- DESKTOP --}}
                                <td class="d-none d-md-table-cell td-desktop-only text-center">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        @if ($isUnQCed)
                                            <input type="checkbox" class="form-check-input row-check" disabled title="Belum di QC">
                                        @else
                                            <input type="checkbox" class="form-check-input row-check"
                                                data-row-index="{{ $idx }}">
                                        @endif
                                        <span class="small text-muted">{{ $loop->iteration }}</span>
                                    </div>
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only">
                                    <a href="{{ route('production.cutting_jobs.show', $b->cutting_job_id) }}" target="_blank" class="text-decoration-none text-body">
                                        <span class="fw-semibold">{{ $cutDateLabel }}</span>
                                        <div><span class="badge-soft bg-light border text-muted px-2 py-0 mt-1" style="font-size: 0.65rem; font-family: var(--bs-font-monospace);">#{{ $shortBundleCode }}</span></div>
                                    </a>
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
                                    @if($isUnQCed)
                                        <span class="badge bg-danger">Belum QC</span>
                                    @else
                                        <span class="qty-ready-pill">
                                            {{ number_format($qtyRemain, 2, ',', '.') }}
                                        </span>
                                    @endif
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only text-end">
                                    <input type="number" step="0.01" min="0" inputmode="decimal"
                                        name="lines[{{ $idx }}][qty_bundle]"
                                        class="form-control form-control-sm text-end qty-input @error($oldQtyName) is-invalid @enderror"
                                        value="{{ old($oldQtyName, $defaultQtyPickup) }}"
                                        {{ $isUnQCed ? 'disabled' : '' }}>
                                    @error($oldQtyName)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only text-end">
                                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 btn-pick"
                                        data-row-index="{{ $idx }}" {{ $isUnQCed ? 'disabled' : '' }}>
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
                                        </div>
                                        <div class="mobile-row-header-right">
                                            <div class="qty-ready-label">Qty Ready</div>
                                            <div class="qty-ready-value">
                                                @if($isUnQCed)
                                                    <span class="badge bg-danger">Belum QC</span>
                                                @else
                                                    <span class="qty-ready-pill">
                                                        {{ number_format($qtyRemain, 2, ',', '.') }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="mobile-row-footer-left mt-1">
                                                @if($isUnQCed)
                                                    <div class="mt-2 text-primary small fw-semibold">
                                                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka Detail Cutting
                                                    </div>
                                                @else
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
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- MOBILE META: tgl cutting & qty cutting --}}
                                    <div class="mobile-row-meta">
                                        <div class="mobile-row-meta-left">
                                            <div class="mobile-row-meta-label">Tanggal</div>
                                            <div class="mobile-row-meta-value">
                                                <a href="{{ route('production.cutting_jobs.show', $b->cutting_job_id) }}" target="_blank" class="text-decoration-none text-body">
                                                    {{ $cutDateLabel }} <span class="badge-soft bg-light border text-muted px-1 py-0 ms-1" style="font-size: 0.6rem; font-family: var(--bs-font-monospace);">#{{ $shortBundleCode }}</span>
                                                </a>
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

            const searchInput = document.getElementById('bundle-filter-input');
            const toggleOnlyPicked = document.getElementById('toggle-only-picked');
            const itemCodeSelect = document.getElementById('item-code-select');
            const itemCodeSelectWrap = document.getElementById('item-code-select-wrap');
            const selectedSummaryCount = document.getElementById('selected-summary-count');
            const selectedSummaryList = document.getElementById('selected-summary-list');
            const selectedSummaryBody = document.getElementById('selected-summary-body');
            const selectedSummaryEmpty = document.getElementById('selected-summary-empty');
            const selectedSummaryClear = document.getElementById('selected-summary-clear');
            const selectedSummaryTotalItems = document.getElementById('selected-summary-total-items');
            const selectedSummaryTotalQty = document.getElementById('selected-summary-total-qty');
            const suppliesPayload = document.getElementById('supplies_checklist_payload');
            const sewingSupplySummary = document.getElementById('sewing-supply-summary');
            const sewingSupplyBody = document.getElementById('sewing-supply-body');
            const sewingSupplyStatus = document.getElementById('sewing-supply-status');

            const submitBtn = document.getElementById('btn-submit-main');
            const submitLabel = document.getElementById('btn-submit-label');
            const submitShortcutBtn = document.getElementById('bundle-submit-shortcut');
            const bomSuppliesByItem = @json($bomSuppliesByItem ?? []);
            let supplyShortageCount = 0;

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
            const draftStorageKey = 'gfid:sewing-pickup:create:selected-bundles';
            let restoringDraft = false;

            function getRowBundleId(row) {
                return row?.querySelector('input[name$="[bundle_id]"]')?.value || '';
            }

            function readPickupDraft() {
                try {
                    const parsed = JSON.parse(localStorage.getItem(draftStorageKey) || '{}');
                    return parsed && typeof parsed === 'object' ? parsed : {};
                } catch (e) {
                    return {};
                }
            }

            function savePickupDraft() {
                if (restoringDraft) return;

                const draft = {};
                rowsArr.forEach(row => {
                    const bundleId = getRowBundleId(row);
                    const input = row.querySelector('input.qty-input');
                    const qty = parseFloat(input?.value || '0') || 0;

                    if (bundleId && qty > 0) {
                        draft[bundleId] = qty;
                    }
                });

                try {
                    if (Object.keys(draft).length > 0) {
                        localStorage.setItem(draftStorageKey, JSON.stringify(draft));
                    } else {
                        localStorage.removeItem(draftStorageKey);
                    }
                } catch (e) {
                    // Kalau storage browser penuh/nonaktif, form tetap bisa dipakai normal.
                }
            }

            function restorePickupDraft() {
                const draft = readPickupDraft();
                if (!Object.keys(draft).length) return;

                restoringDraft = true;
                rowsArr.forEach(row => {
                    const bundleId = getRowBundleId(row);
                    if (!bundleId || !Object.prototype.hasOwnProperty.call(draft, bundleId)) return;

                    const qtyInputs = row.querySelectorAll('input.qty-input');
                    if (!qtyInputs.length) return;

                    const existingQty = parseFloat(qtyInputs[0].value || '0') || 0;
                    if (existingQty > 0) return;

                    const qty = parseFloat(draft[bundleId] || '0') || 0;
                    if (qty <= 0) return;

                    qtyInputs.forEach(input => {
                        input.value = qty;
                        clampToReady(input, row);
                    });
                });
                restoringDraft = false;
            }

            function focusSearchInput() {
                if (!searchInput) return;
                setTimeout(() => {
                    try {
                        searchInput.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                            inline: 'nearest'
                        });
                    } catch (e) {
                        // fallback fokus tetap jalan walau browser tidak support scrollIntoView options
                    }

                    setTimeout(() => {
                        try {
                            searchInput.focus({
                                preventScroll: true
                            });
                            searchInput.select();
                        } catch (e) {
                            searchInput.focus();
                        }
                    }, 180);
                }, 80);
            }

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
                const hasShortage = supplyShortageCount > 0;
                // Boleh submit walau shortage — stok boleh minus (allow_negative=true)
                // Shortage tetap ditampilkan sebagai warning, bukan blocker
                const canSubmit = pickedBundles > 0 && totalPickupQty > 0;

                if (submitBtn && submitLabel) {
                    submitBtn.disabled = !canSubmit;
                    submitBtn.classList.toggle('btn-warning', hasShortage && canSubmit);
                    submitBtn.classList.toggle('btn-primary', !hasShortage && canSubmit);
                    submitLabel.textContent = !canSubmit ? 'Belum Ambil'
                        : hasShortage ? 'Pilih Penjahit ⚠'
                        : 'Pilih Penjahit';
                }

                if (submitShortcutBtn) {
                    submitShortcutBtn.disabled = !canSubmit;
                    submitShortcutBtn.classList.toggle('btn-warning', hasShortage && canSubmit);
                    submitShortcutBtn.classList.toggle('btn-primary', !hasShortage && canSubmit);
                    const shortcutText = submitShortcutBtn.querySelector('span:last-child');
                    if (shortcutText) {
                        shortcutText.textContent = hasShortage ? 'Pilih Penjahit ⚠' : 'Pilih Penjahit';
                    }
                }

                const printBtn = document.getElementById('btn-submit-print');
                if (printBtn) printBtn.disabled = !canSubmit;
            }

            function renderSelectedSummary(items) {
                if (selectedSummaryCount) selectedSummaryCount.textContent = items.length.toString();
                if (!selectedSummaryList || !selectedSummaryBody || !selectedSummaryEmpty) return;

                selectedSummaryBody.innerHTML = '';
                selectedSummaryList.classList.toggle('d-none', items.length === 0);
                selectedSummaryEmpty.classList.toggle('d-none', items.length > 0);
                selectedSummaryClear?.classList.toggle('d-none', items.length === 0);

                const totalQty = items.reduce((sum, item) => sum + (item.qtyValue || 0), 0);
                if (selectedSummaryTotalItems) selectedSummaryTotalItems.textContent = items.length.toString();
                if (selectedSummaryTotalQty) selectedSummaryTotalQty.textContent = nf.format(totalQty);

                items.forEach((item, index) => {
                    const tr = document.createElement('tr');
                    tr.dataset.rowIndex = item.rowIndex;

                    const numberTd = document.createElement('td');
                    numberTd.className = 'text-muted';
                    numberTd.textContent = index + 1;

                    const itemTd = document.createElement('td');
                    itemTd.className = 'item-code-cell mono';
                    itemTd.textContent = item.itemCode || 'Item';

                    const qtyTd = document.createElement('td');
                    qtyTd.className = 'text-end mono fw-bold';
                    qtyTd.textContent = item.qtyLabel;

                    const actionTd = document.createElement('td');
                    actionTd.className = 'text-end';

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'selected-summary-remove';
                    removeBtn.dataset.rowIndex = item.rowIndex;
                    removeBtn.textContent = '×';
                    removeBtn.title = 'Hapus item dari pilihan';

                    actionTd.appendChild(removeBtn);
                    tr.append(numberTd, itemTd, qtyTd, actionTd);
                    selectedSummaryBody.appendChild(tr);
                });

            }

            function buildSewingSupplyItems(selectedItems) {
                const byMaterial = {};

                selectedItems.forEach(item => {
                    const supplies = bomSuppliesByItem[item.finishedItemId] || [];
                    supplies.forEach(supply => {
                        const materialId = parseInt(supply.id || 0, 10);
                        if (!materialId) return;

                        const need = (parseFloat(supply.qty || '0') || 0) * (item.qtyValue || 0);
                        if (need <= 0) return;

                        if (!byMaterial[materialId]) {
                            byMaterial[materialId] = {
                                id: materialId,
                                code: supply.code || '',
                                name: supply.name || '',
                                uom: supply.uom || '',
                                qty: 0,
                                issued_qty: 0,
                                totalPieces: 0,
                                issued_pcs: 0,
                                stock_available: parseFloat(supply.stock_available || '0') || 0,
                            };
                        }

                        byMaterial[materialId].qty += need;
                        byMaterial[materialId].issued_qty += need;
                        byMaterial[materialId].totalPieces += item.qtyValue || 0;
                        byMaterial[materialId].issued_pcs += item.qtyValue || 0;
                    });
                });

                return Object.values(byMaterial).map(item => ({
                    ...item,
                    shortage_qty: Math.max((item.qty || 0) - (item.stock_available || 0), 0),
                }));
            }

            function renderSewingSupplySummary(items) {
                supplyShortageCount = items.filter(item => item.shortage_qty > 0.000001).length;

                if (suppliesPayload) {
                    suppliesPayload.value = JSON.stringify({
                        items
                    });
                }

                if (!sewingSupplySummary || !sewingSupplyBody || !sewingSupplyStatus) return;

                sewingSupplySummary.classList.toggle('d-none', items.length === 0);
                sewingSupplyBody.innerHTML = '';

                sewingSupplyStatus.classList.toggle('is-danger', supplyShortageCount > 0);
                sewingSupplyStatus.textContent = supplyShortageCount > 0 ?
                    `${supplyShortageCount} kurang` :
                    'Cukup';

                items.forEach(item => {
                    const tr = document.createElement('tr');

                    const materialTd = document.createElement('td');
                    const codeEl = document.createElement('strong');
                    codeEl.className = 'mono';
                    codeEl.textContent = item.code || 'Material';
                    materialTd.appendChild(codeEl);
                    if (item.name) {
                        const nameEl = document.createElement('div');
                        nameEl.className = 'text-muted';
                        nameEl.textContent = item.name;
                        materialTd.appendChild(nameEl);
                    }

                    const pcsTd = document.createElement('td');
                    pcsTd.className = 'text-end mono fw-bold';
                    pcsTd.textContent = `${nf.format(item.totalPieces || 0)} pcs`;

                    tr.append(materialTd, pcsTd);
                    sewingSupplyBody.appendChild(tr);
                });
            }

            function recalcSummaryAndUI() {
                let pickedBundles = 0;
                let totalPickupQty = 0;
                const selectedItems = [];

                rowsArr.forEach(row => {
                    const input = row.querySelector('input.qty-input');
                    if (!input) return;
                    const current = parseFloat(input.value || '0');
                    if (current > 0) {
                        pickedBundles++;
                        totalPickupQty += current;
                        selectedItems.push({
                            rowIndex: row.dataset.rowIndex || '',
                            bundleCode: row.dataset.bundleCode || '',
                            itemCode: row.dataset.itemCode || '',
                            finishedItemId: row.dataset.finishedItemId || '',
                            qtyValue: current,
                            qtyLabel: nf.format(current),
                        });
                    }
                });

                renderSelectedSummary(selectedItems);
                renderSewingSupplySummary(buildSewingSupplyItems(selectedItems));

                updateSubmitButtons(pickedBundles, totalPickupQty);
                savePickupDraft();
                applyRowVisibility();
            }

            function clearPickedRow(row) {
                if (!row) return;

                const inputs = row.querySelectorAll('input.qty-input');
                inputs.forEach(input => {
                    input.value = '';
                    input.classList.remove('qty-input-active', 'border-warning');
                });

                const checks = row.querySelectorAll('.row-check');
                checks.forEach(check => check.checked = false);

                row.classList.remove('row-picked', 'is-selected', 'summary-focus');
                row.classList.add('row-empty');
            }

            selectedSummaryList?.addEventListener('click', function(e) {
                const trigger = e.target.closest('.selected-summary-remove');
                if (!trigger) return;

                const row = rowsArr.find(item => item.dataset.rowIndex === trigger.dataset.rowIndex);
                if (!row) return;

                clearPickedRow(row);
                recalcSummaryAndUI();
                focusSearchInput();
            });

            selectedSummaryClear?.addEventListener('click', function() {
                rowsArr.forEach(row => clearPickedRow(row));
                recalcSummaryAndUI();
                focusSearchInput();
            });

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

            // PRE-FILTER dari query ?sku= (mis. klik baris di Dashboard Produksi → Prioritas)
            (function applySkuFromQuery() {
                if (!itemCodeSelect) return;
                const params = new URLSearchParams(window.location.search);
                const sku = normalizeText(params.get('sku') || params.get('item_code'));
                if (!sku) return;
                const match = Array.from(itemCodeSelect.options)
                    .some(o => normalizeText(o.value) === sku);
                if (!match) {
                    itemCodeSelect.add(new Option(`${sku} — belum ada stok jahit`, sku));
                }
                itemCodeSelect.value = sku;
                state.activeItemCode = sku;
                recalcSummaryAndUI();
            })();

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
            restorePickupDraft();

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

                    if (picked) {
                        focusSearchInput();
                    }
                }

                function togglePicked() {
                    const nextState = !isPicked();
                    applyFromState(nextState);
                }

                row.addEventListener('click', function(e) {
                    if (e.target.tagName === 'INPUT' || e.target.closest('button') || e.target.tagName === 'A' || e.target.closest('a')) return;
                    
                    if (row.classList.contains('is-unqced')) {
                        window.open(row.dataset.detailUrl, '_blank');
                        return;
                    }

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

                    if (isPicked()) {
                        focusSearchInput();
                    }
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

                        if (isPicked()) {
                            focusSearchInput();
                        }
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

            // Auto fokus ke search saat halaman dibuka
            if (searchInput) {
                focusSearchInput();
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
