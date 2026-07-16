{{-- resources/views/production/cutting_jobs/_form.blade.php --}}

@php
    $fabricItems = $lotStocks->map(fn($row) => $row->lot->item)->unique('id')->values();

    // default warehouse RM RAW MATERIALS
    $defaultWarehouse =
        $warehouses->firstWhere('code', 'RM') ??
        ($warehouses->firstWhere('name', 'RM RAW MATERIALS') ?? $warehouses->first());
    $selectedWarehouseId = $selectedWarehouseId ?? $defaultWarehouse?->id;

    // default operator MRF
    $defaultOperatorId = optional($operators->firstWhere('code', 'MRF'))->id;
    
    if (isset($isEdit) && $isEdit && isset($job) && $job->bundles->count() > 0) {
        $selectedOperatorId = old('operator_id', $job->bundles->first()->operator_id);
    } else {
        $selectedOperatorId = old('operator_id', $defaultOperatorId);
    }
@endphp

@push('head')
    <style>
        .cutting-card {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .35);
            box-shadow: 0 12px 30px rgba(15, 23, 42, .20);
            margin-bottom: 1rem;
            overflow: visible;
        }

        .cutting-card-header {
            padding: .72rem 1rem;
            border-bottom: 1px solid rgba(148, 163, 184, .20);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
        }

        .cutting-card-header h5 {
            color: var(--text);
            font-size: .88rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            margin: 0;
        }

        .cutting-card-body {
            padding: .85rem 1rem 1rem;
            overflow: visible !important;
            position: relative;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .2rem .6rem;
            font-size: .75rem;
            border: 1px solid rgba(148, 163, 184, .35);
            background: rgba(15, 23, 42, .01);
            white-space: nowrap;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .small-muted {
            font-size: .8rem;
            color: var(--muted);
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .15rem .6rem;
            border-radius: 999px;
            font-size: .78rem;
            border: 1px solid var(--line, rgba(148,163,184,.4));
            background: rgba(15, 23, 42, .01);
        }

        .card-soft {
            background: color-mix(in srgb, var(--card) 84%, var(--line) 16%);
        }

        .bundles-table-wrap {
            overflow-x: auto;
            position: relative;
        }

        .bundles-table {
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 0;
            font-size: .8rem;
        }

        /* === TABLE HEADER === */
        .bundles-table thead tr {
            background: rgba(148, 163, 184, .06);
        }

        .bundles-table thead th {
            color: var(--muted);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            border-bottom: 2px solid rgba(148, 163, 184, .18);
            padding: .42rem .5rem;
            white-space: nowrap;
        }

        .bundles-table thead th:first-child {
            border-radius: 8px 0 0 0;
            padding-left: .75rem;
        }

        .bundles-table thead th:last-child {
            border-radius: 0 8px 0 0;
        }

        /* === TABLE BODY ROWS === */
        .bundles-table tbody td {
            padding: .38rem .5rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(148, 163, 184, .07);
            transition: background .1s;
        }

        .bundles-table tbody td:first-child {
            padding-left: .75rem;
        }

        .bundles-table tbody tr.bundle-row {
            border-left: 3px solid transparent;
            transition: border-left-color .14s;
        }

        .bundles-table tbody tr.bundle-row:hover td {
            background: rgba(59, 130, 246, .028);
        }

        .bundles-table tbody tr.bundle-row:hover {
            border-left-color: rgba(37, 99, 235, .45);
        }

        /* Row dengan LOT terisi — accent lebih kuat */
        .bundles-table tbody tr.bundle-row.lot-assigned {
            border-left-color: rgba(37, 99, 235, .35);
        }

        /* === ROW INDEX NUMBER === */
        .bundle-index {
            font-size: .65rem;
            font-weight: 800;
            color: var(--muted);
            opacity: .55;
            width: 20px;
            text-align: center;
        }

        .bundle-notes-cell {
            min-width: 140px;
        }

        .bundle-item-help {
            display: none;
            margin-top: .2rem;
            font-size: .66rem;
            line-height: 1.2;
            color: var(--muted);
        }

        /* === LOT BADGE === */
        .bundle-lot-badge {
            display: inline-block;
            font-size: .68rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
            padding: .14rem .42rem;
            border-radius: 6px;
            background: rgba(37, 99, 235, .07);
            border: 1px solid rgba(37, 99, 235, .2);
            color: #1d4ed8;
            white-space: nowrap;
            letter-spacing: .02em;
        }

        body[data-theme="dark"] .bundle-lot-badge {
            background: rgba(99, 149, 246, .13);
            border-color: rgba(99, 149, 246, .32);
            color: #93c5fd;
        }

        /* === QTY PCS INPUT (desktop) === */
        .bundle-qty-pcs {
            font-weight: 700 !important;
        }

        /* === ADD ROW BUTTON === */
        #btn-add-row {
            width: 100%;
            border: 1.5px dashed rgba(37, 99, 235, .35);
            background: rgba(37, 99, 235, .03);
            color: rgba(37, 99, 235, .75);
            border-radius: 10px;
            font-weight: 600;
            font-size: .8rem;
            padding: .42rem;
            transition: background .14s, border-color .14s, color .14s;
        }

        #btn-add-row:hover {
            background: rgba(37, 99, 235, .07);
            border-color: rgba(37, 99, 235, .55);
            color: #1d4ed8;
        }

        body[data-theme="dark"] #btn-add-row {
            border-color: rgba(99, 149, 246, .35);
            background: rgba(99, 149, 246, .05);
            color: rgba(147, 197, 253, .8);
        }

        /* === REMOVE ROW BUTTON === */
        .btn-remove-row {
            width: 28px;
            height: 28px;
            border: 1px solid rgba(220, 38, 38, .18) !important;
            border-radius: 999px;
            background: rgba(254, 242, 242, .6);
            line-height: 1;
            padding: 0 !important;
            text-decoration: none;
            font-size: .9rem;
            color: rgba(220, 38, 38, .7);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background .12s, border-color .12s;
        }

        .btn-remove-row:hover {
            background: rgba(254, 226, 226, .9);
            border-color: rgba(220, 38, 38, .4) !important;
        }

        /* === SHORTAGE WARNING BAR === */
        .lot-shortage-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
            padding: .48rem .62rem;
            border-radius: 8px;
            background: rgba(220, 38, 38, .06);
            border: 1px solid rgba(220, 38, 38, .22);
            border-left: 3px solid #dc2626;
            margin-bottom: .65rem;
            animation: shortagePulse 2.2s ease-in-out infinite;
        }

        @keyframes shortagePulse {
            0%, 100% { border-left-color: #dc2626; }
            50%       { border-left-color: rgba(220, 38, 38, .35); }
        }

        body[data-theme="dark"] .lot-shortage-bar {
            background: rgba(220, 38, 38, .1);
            border-color: rgba(220, 38, 38, .3);
        }

        .lot-shortage-text {
            font-size: .76rem;
            color: #b91c1c;
            min-width: 0;
            flex: 1;
            line-height: 1.35;
        }

        body[data-theme="dark"] .lot-shortage-text { color: #fca5a5; }

        .lot-shortage-num {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-weight: 700;
        }

        .lot-shortage-btn {
            flex-shrink: 0;
            font-size: .72rem;
            font-weight: 700;
            padding: .28rem .65rem;
            border-radius: 999px;
            background: #dc2626;
            color: #fff;
            border: 0;
            white-space: nowrap;
            transition: background .13s;
        }

        .lot-shortage-btn:hover {
            background: #b91c1c;
            color: #fff;
        }

        .lot-shortage-bom-btn {
            flex-shrink: 0;
            font-size: .72rem;
            font-weight: 700;
            padding: .28rem .65rem;
            border-radius: 999px;
            background: #f59e0b;
            color: #fff;
            border: 0;
            white-space: nowrap;
            transition: background .13s;
        }

        .lot-shortage-bom-btn:hover {
            background: #d97706;
            color: #fff;
        }

        /* === CUTTING CARD HEADER — Hasil Cutting === */
        .cutting-card-bundles .cutting-card-header {
            background: rgba(37, 99, 235, .03);
            border-bottom-color: rgba(37, 99, 235, .1);
        }

        .cutting-card-bundles .cutting-card-header h5 {
            color: var(--text);
        }

        body[data-theme="dark"] .cutting-card-bundles .cutting-card-header {
            background: rgba(37, 99, 235, .08);
        }

        .lot-summary-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }

        .lot-summary-list li {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: .5rem;
            font-size: .8rem;
            padding: .1rem 0;
        }

        .lot-summary-list li span:first-child {
            color: var(--muted);
        }

        .lot-summary-list li span:last-child {
            font-weight: 600;
        }

        .cutting-actions {
            gap: .5rem;
        }

        .cutting-save-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            margin-top: .5rem;
        }

        /* === FLOATING SAVE (mobile only) === */
        .cutting-save-fab-wrap {
            display: none;
            position: fixed;
            right: .9rem;
            bottom: 5.5rem;
            z-index: 40;
        }

        #cutting-save-fab {
            border-radius: 999px;
            border: none;
            padding: .45rem 1.1rem .45rem .85rem;
            font-size: .82rem;
            font-weight: 700;
            background: linear-gradient(135deg, #0d6efd 0%, #2563eb 60%, #1d4ed8 100%);
            color: #f9fafb;
            box-shadow:
                0 12px 24px rgba(15, 23, 42, .35),
                0 0 0 1px rgba(191, 219, 254, .9);
            display: inline-flex;
            align-items: center;
            gap: .38rem;
            white-space: nowrap;
        }

        #cutting-save-fab:active {
            transform: scale(.97);
        }

        .cutting-selected-lot-strip {
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 16px;
            background: color-mix(in srgb, var(--card) 84%, var(--line) 16%);
            box-shadow: 0 12px 30px rgba(15, 23, 42, .15);
        }

        body[data-theme="dark"] .cutting-selected-lot-strip {
            background: color-mix(in srgb, var(--card) 80%, rgba(37, 99, 235, .08));
        }

        .cutting-selected-lot-main {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: .75rem;
            width: 100%;
        }

        .cutting-selected-label {
            display: block;
            color: var(--muted);
            font-size: .58rem;
            font-weight: 900;
            letter-spacing: .04em;
            line-height: 1;
            text-transform: uppercase;
        }

        .cutting-selected-value {
            display: block;
            margin-top: .18rem;
            font-size: .82rem;
            font-weight: 900;
            line-height: 1.2;
            min-width: 0;
        }

        .cutting-stepbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
            margin-bottom: .5rem;
        }

        .cutting-stepbar-text {
            color: var(--muted);
            font-size: .74rem;
            font-weight: 800;
        }

        @media (max-width: 767.98px) {
            .cutting-card {
                border-radius: 14px;
                box-shadow: 0 8px 20px rgba(15, 23, 42, .14);
                margin-bottom: .75rem;
            }

            .cutting-card-header {
                padding: .68rem .85rem;
                gap: .4rem;
                align-items: flex-start;
                flex-direction: column;
            }

            .cutting-card-header h5 {
                font-size: .82rem;
                letter-spacing: .03em;
            }

            .cutting-card-header .badge-soft {
                font-size: .66rem;
                color: var(--muted);
                background: transparent;
                border-color: rgba(148, 163, 184, .22);
            }

            .cutting-card-body {
                padding: .7rem .85rem .85rem;
            }

            .badge-soft {
                font-size: .72rem;
                padding: .15rem .5rem;
            }

            .cutting-selected-lot-strip {
                border-radius: 14px;
                box-shadow: 0 6px 16px rgba(15, 23, 42, .10);
            }

            .cutting-selected-lot-main {
                grid-template-columns: minmax(0, 1fr);
                gap: .48rem;
            }

            .cutting-selected-label {
                font-size: .5rem;
            }

            .cutting-selected-value {
                font-size: .76rem;
                margin-top: .12rem;
            }

            .cutting-stepbar {
                align-items: stretch;
                flex-direction: column;
                gap: .42rem;
                padding: .58rem .68rem;
                margin-bottom: .6rem;
                border: 1px solid rgba(148, 163, 184, .22);
                border-radius: 14px;
                background: color-mix(in srgb, var(--card) 88%, rgba(37, 99, 235, .07));
            }

            .cutting-stepbar-text {
                font-size: .76rem;
                line-height: 1.35;
                color: var(--text);
            }

            .bundles-table-wrap {
                overflow-x: visible;
            }

            /* Ubah ke block layout */
            .bundles-table,
            .bundles-table tbody,
            .bundles-table tfoot {
                display: block;
                width: 100%;
            }

            .bundles-table thead {
                display: none;
            }

            /* ── 2-row card per baris ── */
            .bundles-table tbody tr.bundle-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr) 76px 34px;
                column-gap: .42rem;
                row-gap: 0;
                align-items: center;
                border: 1px solid rgba(148, 163, 184, .25);
                border-left: 3px solid rgba(148, 163, 184, .3);
                border-radius: 12px;
                padding: .44rem .48rem;
                margin-bottom: .38rem;
                background: var(--card);
                box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
                overflow: visible;
                transition: border-left-color .14s, box-shadow .14s;
                position: relative;
            }

            .bundles-table tbody tr.bundle-row.lot-assigned {
                border-left-color: #2563eb;
                box-shadow: 0 3px 10px rgba(37, 99, 235, .1);
            }

            /* Nomor — col 1 row 1 */
            .bundles-table tbody td:nth-child(1) {
                position: absolute;
                top: .48rem;
                left: .52rem;
                display: none !important;
                color: var(--muted);
                font-size: .7rem;
                font-weight: 700;
                border: 0;
                padding: 0;
            }

            /* LOT badge — hidden on mobile */
            .bundles-table tbody td:nth-child(2) {
                display: none !important;
            }

            /* Item suggest — col 2 row 1 */
            .bundles-table tbody td:nth-child(3) {
                grid-column: 1;
                grid-row: 1;
                min-width: 0;
                border: 0;
                padding: 0;
                overflow: visible;
                position: relative;
                display: flex;
                flex-direction: column;
                gap: 0;
            }

            /* Qty — col 3 row 1 */
            .bundles-table tbody td:nth-child(4) {
                grid-column: 2;
                grid-row: 1;
                border: 0;
                padding: 0;
                display: flex;
                flex-direction: column;
                gap: 0;
            }

            .bundles-table tbody td:nth-child(3)::before,
            .bundles-table tbody td:nth-child(4)::before {
                display: none;
                font-size: .62rem;
                font-weight: 800;
                letter-spacing: .05em;
                text-transform: uppercase;
                color: var(--muted);
                line-height: 1;
            }

            .bundles-table tbody td:nth-child(3)::before { content: "Item jadi"; }
            .bundles-table tbody td:nth-child(4)::before { content: "Qty"; }

            .bundle-item-help {
                display: none;
            }

            /* Kain — hidden */
            .bundles-table tbody td:nth-child(5) {
                display: none !important;
            }

            /* Catatan — hidden */
            .bundles-table tbody td:nth-child(6) {
                display: none !important;
            }

            /* Hapus — col 4 row 1 */
            .bundles-table tbody td:nth-child(7) {
                grid-column: 3;
                grid-row: 1;
                display: flex !important;
                align-items: center;
                justify-content: center;
                border: 0;
                padding: 0;
                align-self: center;
            }

            /* Reset border/padding semua td */
            .bundles-table tbody td {
                border: 0 !important;
                padding: 0;
            }

            .bundles-table .form-control-sm,
            .bundles-table .form-select-sm {
                min-height: 40px;
                border-radius: 10px;
                font-size: .84rem;
                padding: .34rem .48rem;
            }

            .bundles-table .item-suggest-wrap {
                width: 100%;
                min-width: 0;
            }

            .bundles-table .js-item-suggest-input {
                font-weight: 800;
                letter-spacing: .01em;
                text-transform: uppercase;
            }

            /* Warna item jadi beda dari warna kain → input merah + baris ditandai */
            .bundles-table .js-item-suggest-input.is-invalid {
                border-color: #dc2626 !important;
                background: rgba(220, 38, 38, .06) !important;
                color: #b91c1c !important;
            }
            .bundles-table tr.bundle-row-color-mismatch td {
                background: rgba(220, 38, 38, .04);
            }
            .bundles-table tr.bundle-row-color-mismatch .js-item-suggest-input {
                box-shadow: 0 0 0 .12rem rgba(220, 38, 38, .18);
            }

            .bundles-table .item-suggest-dropdown {
                min-width: min(92vw, 340px);
                max-height: 48vh;
            }

            .bundle-qty-pcs {
                text-align: center !important;
                font-size: .92rem !important;
                font-weight: 900 !important;
                padding-inline: .25rem !important;
            }

            .btn-remove-row {
                width: 34px;
                height: 34px;
                font-size: 1.05rem;
                background: rgba(254, 242, 242, .9);
            }

            /* Bundle row padding lebih besar untuk jarak label */
            .bundles-table tbody tr.bundle-row {
                align-items: center;
            }

            .bundles-table tfoot tr,
            .bundles-table tfoot td {
                display: block;
                width: 100%;
            }

            .bundles-table tfoot td {
                padding: .2rem 0 0;
                border: 0;
            }

            #btn-add-row {
                min-height: 48px;
                font-size: .92rem;
                border-radius: 12px;
                background: color-mix(in srgb, var(--card) 88%, rgba(37, 99, 235, .08));
            }

            /* Sembunyikan save bar di mobile */
            .cutting-save-bar { display: none; }

            /* Sembunyikan stepbar info button di mobile */
            .cutting-stepbar .btn-outline-secondary {
                display: none;
            }

            /* Ubah LOT button lebih tappable */
            #btn-change-lots {
                min-height: 40px;
                padding-inline: .85rem;
                font-size: .82rem;
            }

            .lot-shortage-bar {
                align-items: stretch;
                flex-direction: column;
            }

            .lot-shortage-btn,
            .lot-shortage-bom-btn {
                width: 100%;
                min-height: 38px;
                justify-content: center;
            }

            .cutting-save-fab-wrap {
                left: .8rem;
                right: .8rem;
                bottom: calc(4.75rem + env(safe-area-inset-bottom, 0px));
                display: none;
            }

            #cutting-save-fab {
                width: 100%;
                justify-content: center;
                min-height: 50px;
                border-radius: 14px;
                font-size: .92rem;
                box-shadow: 0 12px 26px rgba(37, 99, 235, .28);
            }
        }
    </style>
@endpush

@php $isEdit = $isEdit ?? false; @endphp
<form action="{{ $isEdit ? route('production.cutting_jobs.update', $job) : route('production.cutting_jobs.store') }}" method="POST" id="cutting-form" autocomplete="off">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    {{-- dipakai untuk ringkasan / estimasi --}}
    <input type="hidden" name="lot_balance" id="lot_balance" value="0">

    {{-- Item kain (auto dari LOT terpilih, diset via JS) --}}
    <select name="fabric_item_id" id="fabric_item_id" class="d-none">
        <option value="">- Pilih Item Kain -</option>
        @foreach ($fabricItems as $item)
            <option value="{{ $item->id }}">
                {{ $item->code }} — {{ $item->name }}
            </option>
        @endforeach
    </select>

    {{-- STEP 1: PILIH KAIN & LOT --}}
    @if (isset($isLotsLocked) && $isLotsLocked)
        <div class="cutting-card" id="cutting-lot-locked">
            <div class="cutting-card-header">
                <h5>LOT (Terkunci)</h5>
                <span class="badge-soft">Edit tidak perlu pilih LOT lagi</span>
            </div>
            <div class="cutting-card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                    @foreach ($selectedLotSummaries as $s)
                        <div style="border: 1px solid rgba(148, 163, 184, .2); border-radius: 8px; padding: .5rem .75rem; background: rgba(148, 163, 184, .04);">
                            <div class="mono fw-semibold">{{ $s['code'] }}</div>
                            <div class="muted small">{{ $s['item_code'] }} — {{ $s['item_name'] }}</div>
                            <div class="mt-1 d-flex justify-content-between align-items-center">
                                <span class="mono fw-semibold text-primary">{{ number_format((float) $s['used'], 2, ',', '.') }}</span>
                                <span class="muted small">planned: {{ number_format((float) $s['planned'], 2, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        @include('production.cutting_jobs._pick_lot')
    @endif

    {{-- STEP 2: KONTEN UTAMA (muncul setelah LOT disimpan) --}}
    <div id="cutting-main-content" class="cutting-main-content d-none">
        {{-- Hidden elements dipakai JS untuk tracking state --}}
        <span id="current-fabric-label" style="display:none;"></span>
        <span id="current-lot-count" style="display:none;"></span>
        <span id="current-lot-balance" style="display:none;"></span>
        {{-- btn-change-lots diklik dari step 3 panel di lot picker --}}
        <button type="button" id="btn-change-lots" style="display:none;"></button>

        {{-- BARIS KONTROL ATAS: tombol buka modal info job --}}
        <div class="cutting-stepbar">
            <div class="cutting-stepbar-text">
                Isi item hasil cutting dan qty bundle.
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm btn-pill-sm" data-bs-toggle="modal"
                data-bs-target="#cuttingInfoModal">
                Info Cutting Job
            </button>
        </div>

        {{-- CARD: BUNDLES --}}
        <div class="cutting-card cutting-card-bundles">
            <div class="cutting-card-header">
                <h5>Hasil Cutting</h5>
                <span class="badge-soft">
                    Item jadi & qty
                </span>
            </div>
            <div class="cutting-card-body">
                {{-- SHORTAGE WARNING --}}
                <div id="lot-shortage-warning" class="lot-shortage-bar" style="display:none;">
                    <div class="lot-shortage-text">
                        ⚠️ Stok LOT kurang <span class="lot-shortage-num" id="lot-shortage-amount">0</span> kg dari kebutuhan.
                        Tambah LOT kain untuk melanjutkan.
                        <span id="lot-shortage-bom-status" style="display:block;margin-top:3px;font-size:.7rem;font-weight:700;"></span>
                    </div>
                    <button type="button" id="btn-update-bom-shortage" class="lot-shortage-bom-btn" style="display:none;">
                        Update BOM
                    </button>
                    <button type="button" id="btn-add-lot-shortage" class="lot-shortage-btn">
                        + Tambah LOT
                    </button>
                </div>

                <div class="bundles-table-wrap mb-2">
                    <table class="bundles-table table table-sm align-middle mono" id="bundles-table">
                        <thead>
                            <tr>
                                <th style="width: 36px;">#</th>
                                <th style="min-width: 120px;" class="bundle-lot-col">LOT</th>
                                <th style="min-width: 160px;">Item Jadi</th>
                                <th style="min-width: 90px;" class="text-end">Qty (pcs)</th>
                                <th style="min-width: 90px;" class="text-end">Kain (kg)<div style="font-size:.6rem;font-weight:500;color:#94a3b8;line-height:1.2;">auto: terakhir/BOM</div></th>
                                <th style="min-width: 140px;" class="bundle-notes-header">Catatan</th>
                                <th style="width: 36px;"></th>
                            </tr>
                        </thead>
                        <tbody id="bundle-rows">
                            {{-- Baris awal digenerate via JS --}}
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-pill-sm"
                                        id="btn-add-row">
                                        + Tambah Item
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- TEMPLATE ROW (hidden) --}}
                <template id="bundle-row-template">
                    <tr class="bundle-row">
                        <td class="bundle-index"></td>
                        <td class="bundle-lot-col">
                            {{-- LOT otomatis didistribusi oleh JS (round-robin) --}}
                            <input type="hidden" name="bundles[__INDEX__][id]" class="bundle-id">
                            <input type="hidden" class="bundle-lot-select" name="bundles[__INDEX__][lot_id]" value="">
                            <span class="bundle-lot-badge">—</span>
                        </td>
                        <td>
                            {{-- ITEM JADI pakai component item-suggest (idName wajib) --}}
                            <x-item-suggest idName="bundles[__INDEX__][finished_item_id]"
                                displayName="bundles[__INDEX__][finished_item_display]" placeholder="Cari item jadi"
                                type="finished_good" displayMode="code" :extraParams="[]" />
                            <div class="bundle-item-help">Tap lalu pilih item dari daftar.</div>
                        </td>
                        <td>
                            <x-number-input name="bundles[__INDEX__][qty_pcs]" step="0.01" min="0"
                                inputmode="decimal" size="sm" placeholder="Qty" class="bundle-qty-pcs bundle-qty" />
                        </td>
                        <td>
                            {{-- qty_used_fabric: auto-fill dari pemakaian terakhir (fallback BOM), bisa diedit --}}
                            <input type="text" inputmode="decimal" autocomplete="off"
                                name="bundles[__INDEX__][qty_used_fabric]"
                                class="form-control form-control-sm text-end bundle-qty-fabric"
                                placeholder="auto" />
                            <div class="fabric-usage-info" style="font-size:.68rem;line-height:1.35;color:#94a3b8;margin-top:2px;"></div>
                            <div class="fabric-bom-warning" style="display:none;font-size:.7rem;line-height:1.4;margin-top:3px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:4px 6px;"></div>
                        </td>
                        <td class="bundle-notes-cell">
                            <input type="text" class="form-control form-control-sm" name="bundles[__INDEX__][notes]">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-link text-danger btn-remove-row">
                                &times;
                            </button>
                        </td>
                    </tr>
                </template>
            </div>
        </div>

        {{-- CARD: SUMMARY LOT (desktop + mobile) --}}
        <div class="cutting-card">
            <div class="cutting-card-header">
                <h5>Cek Stok & Pemakaian Kain</h5>
                <span class="badge-soft d-none d-md-inline">
                    Stok tersedia vs rencana pemakaian (dari kolom kain)
                </span>
            </div>
            <div class="cutting-card-body">
                <ul class="lot-summary-list" id="lot-summary-list">
                    <li class="text-muted">
                        <span>Belum ada pemilihan LOT atau qty bundle.</span>
                        <span></span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- ACTIONS --}}
        @if (!app()->isProduction() && auth()->user()?->isDeveloper())
            <div class="mt-3">
                <label class="form-check d-inline-flex align-items-center gap-2 small text-muted mb-0">
                    <input type="checkbox" class="form-check-input mt-0" name="dev_rollback" value="1">
                    <span>Mode Developer: test rollback</span>
                </label>
            </div>
        @endif

        <div class="cutting-save-bar">
            <a href="{{ route('production.cutting_jobs.index') }}" class="btn btn-outline-secondary btn-sm">
                Batal
            </a>
            {{-- type="button" karena submit lewat modal --}}
            <button type="button" class="btn btn-primary btn-sm" id="btn-save-cutting">
                Pilih Operator &amp; Simpan
            </button>
        </div>
    </div> {{-- /#cutting-main-content --}}

    {{-- FLOATING SAVE BUTTON (mobile) --}}
    <div class="cutting-save-fab-wrap d-md-none" id="cutting-save-fab-wrap">
        <button type="button" id="cutting-save-fab">
            <span class="bi bi-person-check-fill" style="font-size:.9rem;"></span>
            Pilih Operator &amp; Simpan
        </button>
    </div>

    {{-- MODAL DIPISAH KE FILE TERSENDIRI --}}
    @include('production.cutting_jobs._modal_confirm')
</form>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let lotRows = Array.from(document.querySelectorAll('.lot-row'));
            let lotCheckboxes = Array.from(document.querySelectorAll('.lot-checkbox'));
            const btnSelectAllLots = document.getElementById('btn-select-all-lots');
            const btnUnselectAllLots = document.getElementById('btn-unselect-all-lots');
            const btnConfirmLots = document.getElementById('btn-confirm-lots');

            const bundlesTbody = document.getElementById('bundle-rows');
            const bundleTemplate = document.getElementById('bundle-row-template');
            const btnAddRow = document.getElementById('btn-add-row');
            const lotSummaryList = document.getElementById('lot-summary-list');
            const lotBalanceInput = document.getElementById('lot_balance');

            const mainContent = document.getElementById('cutting-main-content');
            const pickLotSection = document.getElementById('cutting-pick-lot');

            // ringkasan LOT di atas form
            const currentFabricLabel = document.getElementById('current-fabric-label');
            const currentLotCount = document.getElementById('current-lot-count');
            const currentLotBalance = document.getElementById('current-lot-balance');
            const btnChangeLots = document.getElementById('btn-change-lots');

            // hidden select untuk item kain (diset dari LOT)
            const fabricSelect = document.getElementById('fabric_item_id');

            // BOM data dari controller: { finishedItemId: { fabricItemId: { qty, scrap_pct } } }
            const bomData = @json($bomData ?? []);
            // URL edit BOM per finished item: { finishedItemId: editUrl }
            const bomEditUrls  = @json($bomEditUrls  ?? []);
            // URL quick-update BOM line: { finishedItemId: quickUrl }
            const bomQuickUrls = @json($bomQuickUrls ?? []);
            // Riwayat pemakaian kain aktual terakhir:
            // { finishedItemId: { fabricItemId: { kg_per_pcs, job_code, date, history: [...] } } }
            const lastUsage = @json($lastUsage ?? []);
            // CSRF token untuk PATCH request
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

            function isMobile() {
                return window.matchMedia('(max-width: 767.98px)').matches;
            }

            // map lot_id -> info (termasuk itemId biar bisa kunci 1 item kain)
            let lotInfoMap = {};

            function rebuildLotRuntimeFromDom() {
                lotRows = Array.from(document.querySelectorAll('.lot-row'));
                lotCheckboxes = Array.from(document.querySelectorAll('.lot-checkbox'));
                lotInfoMap = {};

                lotRows.forEach(tr => {
                    const lotId = parseInt(tr.dataset.lotId, 10);
                    const itemId = parseInt(tr.dataset.itemId || '0', 10);
                    const balance = parseFloat(tr.dataset.balance ?? '0');
                    const code = tr.querySelector('.lot-pill-lot')?.textContent?.trim()
                        || tr.querySelector('.lot-code')?.textContent?.trim()
                        || String(lotId);
                    const itemCode = (tr.dataset.itemCode || '').trim();
                    lotInfoMap[lotId] = {
                        lotId,
                        itemId,
                        code,
                        balance,
                        itemCode,
                    };
                });
            }

            rebuildLotRuntimeFromDom();

            let bundleIndexCounter = 0;
            let lotsLocked = false;

            function getCheckedLots() {
                const ids = [];
                lotCheckboxes.forEach(cb => {
                    if (cb.checked) ids.push(parseInt(cb.value, 10));
                });
                return ids;
            }

            function getCheckedLotsWithInfo() {
                const infos = [];
                lotCheckboxes.forEach(cb => {
                    if (!cb.checked) return;
                    const lotId = parseInt(cb.value, 10);
                    const info = lotInfoMap[lotId];
                    if (info) infos.push(info);
                });
                return infos;
            }

            // Ekstrak warna dari kode item kain (e.g. "K7BLK" → "BLK", "FLC280-BLK" → "BLK")
            function extractColorFromItemCode(code) {
                if (!code) return '';
                const m = code.toUpperCase().match(/([A-Z]{2,5})$/);
                return m ? m[1] : '';
            }

            // Item jadi sengaja tidak difilter warna agar hasil suggest tidak kosong.
            function applyColorToAllBundleRows(color) {
                document.querySelectorAll('.bundle-row .item-suggest-wrap').forEach(wrap => {
                    try {
                        const params = JSON.parse(wrap.dataset.extraParams || '{}');
                        delete params.color_code;
                        delete params.lot_id;
                        wrap.dataset.extraParams = JSON.stringify(params);
                    } catch (e) {}
                });
                window.cuttingDefaultColor = '';
            }

            // (prefillBundleColorQuery dihapus — input tidak di-prefill, filter tetap via data-extra-params)
            function prefillBundleColorQuery(color) {
                // tidak mengisi input — server-side filter sudah cukup
            }

            function buildStep3Summary() {
                const infos = getCheckedLotsWithInfo();
                const lotCount = infos.length;
                const balance = parseFloat(lotBalanceInput?.value || '0');
                const itemCode = infos[0]?.itemCode || '';
                return `<strong>${lotCount} LOT</strong> · ${balance.toFixed(2)} kg` + (itemCode ? ` · <span style="font-family:monospace">${itemCode}</span>` : '');
            }

            function showMainContent() {
                if (!mainContent) return;
                mainContent.classList.remove('d-none');

                // Collapse lot picker to step 3 compact view (don't hide it on mobile)
                if (typeof window.lotPickerGoToStep3 === 'function') {
                    window.lotPickerGoToStep3(buildStep3Summary());
                } else if (isMobile() && pickLotSection) {
                    pickLotSection.classList.add('d-none');
                }

                // Sembunyikan seluruh footer picker ketika hasil cutting tampil
                const pickerFooterEl = document.getElementById('lot-picker-footer');
                if (pickerFooterEl) pickerFooterEl.style.display = 'none';

                // Tampilkan FAB mobile
                const saveFab = document.getElementById('cutting-save-fab-wrap');
                if (saveFab) saveFab.style.display = 'flex';

                // Setelah main content muncul, set filter warna ke item suggest
                const infos = getCheckedLotsWithInfo();
                if (infos.length > 0) {
                    const color = extractColorFromItemCode(infos[0].itemCode);
                    if (color) {
                        applyColorToAllBundleRows(color);
                        prefillBundleColorQuery(color);
                    }
                }

                // Scroll to main content
                setTimeout(() => {
                    mainContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 80);
            }

            function showPickLotSection() {
                if (!pickLotSection) return;

                // Show lot picker, restore step 1
                pickLotSection.classList.remove('d-none');
                if (typeof window.lotPickerGoToStep1 === 'function') {
                    window.lotPickerGoToStep1();
                }

                if (isMobile()) {
                    if (mainContent) mainContent.classList.add('d-none');
                }

                // Sembunyikan FAB saat kembali ke picker
                const saveFab = document.getElementById('cutting-save-fab-wrap');
                if (saveFab) saveFab.style.display = 'none';

                pickLotSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function lockLotSelection() {
                lotsLocked = true;
                document.body.classList.add('cutting-lots-locked');
            }

            function unlockLotSelection() {
                lotsLocked = false;
                document.body.classList.remove('cutting-lots-locked');
                window.cuttingDefaultColor = '';
            }

            function recalcLotBalanceFromCheckedLots() {
                let total = 0;
                lotCheckboxes.forEach(cb => {
                    if (cb.checked) {
                        const row = cb.closest('.lot-row');
                        if (!row) return;
                        const balance = parseFloat(row.dataset.balance || '0');
                        total += balance;
                    }
                });
                lotBalanceInput.value = total.toFixed(2);
            }

            // helper: item jadi tidak difilter by lot_id karena LOT adalah bahan baku.
            function updateRowItemSuggestExtraParams(tr) {
                if (!tr) return;
                const wrap = tr.querySelector('.item-suggest-wrap');
                if (!wrap) return;

                let extraParams = {};
                try {
                    extraParams = JSON.parse(wrap.dataset.extraParams || '{}') || {};
                } catch (e) {
                    extraParams = {};
                }

                delete extraParams.lot_id;
                wrap.dataset.extraParams = JSON.stringify(extraParams);
            }

            // ⚙️ AUTO-DISTRIBUSI LOT SEQUENTIAL
            // LOT 1 dipakai sampai saldo habis (dikurangi fabric tiap baris),
            // baru lanjut ke LOT 2, dst.
            function rebuildLotOptionsForAllRows() {
                const checkedLotIds = getCheckedLots();
                const rows = Array.from(bundlesTbody.querySelectorAll('.bundle-row'));

                if (!checkedLotIds.length) {
                    rows.forEach(tr => {
                        const input = tr.querySelector('.bundle-lot-select');
                        const badge = tr.querySelector('.bundle-lot-badge');
                        if (input) input.value = '';
                        if (badge) { badge.textContent = '—'; badge.style.opacity = '0.4'; }
                        tr.classList.remove('lot-assigned');
                        updateRowItemSuggestExtraParams(tr);
                    });
                    return;
                }

                // Saldo per LOT (mutable snapshot)
                const lotRemaining = {};
                checkedLotIds.forEach(id => {
                    lotRemaining[id] = lotInfoMap[id]?.balance ?? 0;
                });

                let ptr = 0; // pointer ke LOT aktif

                rows.forEach(tr => {
                    const input = tr.querySelector('.bundle-lot-select');
                    const badge = tr.querySelector('.bundle-lot-badge');
                    if (!input) return;

                    const fabricUsed = parseFloat(
                        tr.querySelector('.bundle-qty-fabric')?.value || '0'
                    );

                    // Maju ke LOT berikutnya jika saldo habis
                    while (ptr < checkedLotIds.length - 1 &&
                           lotRemaining[checkedLotIds[ptr]] <= 0.001) {
                        ptr++;
                    }

                    const chosenId = checkedLotIds[ptr];
                    input.value = String(chosenId);

                    // Kurangi saldo dengan fabric baris ini
                    if (fabricUsed > 0) {
                        lotRemaining[chosenId] -= fabricUsed;
                    }

                    if (badge) {
                        const info = lotInfoMap[chosenId];
                        badge.textContent = info ? info.code : String(chosenId);
                        badge.style.opacity = '1';
                    }

                    tr.classList.add('lot-assigned');
                    updateRowItemSuggestExtraParams(tr);
                });
            }

            function updateBundleRowIndices() {
                const rows = bundlesTbody.querySelectorAll('.bundle-row');
                rows.forEach((tr, idx) => {
                    const numCell = tr.querySelector('.bundle-index');
                    if (numCell) numCell.textContent = idx + 1;
                });
            }

            function getBundleItemLabel(tr) {
                const itemCell = tr.querySelector('td:nth-child(3)');
                if (!itemCell) return '(belum pilih item)';
                const textInput = itemCell.querySelector('input[type="text"]');
                if (textInput && textInput.value.trim() !== '') {
                    return textInput.value.trim();
                }
                return '(belum pilih item)';
            }

            function recalcLotSummary() {
                const rows = bundlesTbody.querySelectorAll('.bundle-row');
                let totalPcs = 0;
                let validRowCount = 0;
                const itemSummary = {};

                rows.forEach(tr => {
                    const qtyInput = tr.querySelector('.bundle-qty-pcs');
                    if (!qtyInput) return;
                    const qty = parseFloat(qtyInput.value || '0');
                    if (qty > 0) {
                        totalPcs += qty;
                        validRowCount++;

                        const label = getBundleItemLabel(tr);
                        if (!itemSummary[label]) itemSummary[label] = 0;
                        itemSummary[label] += qty;
                    }
                });

                const totalBalance = parseFloat(lotBalanceInput.value || '0');
                const fabricItemId = fabricSelect ? parseInt(fabricSelect.value || '0', 10) : 0;

                while (lotSummaryList.firstChild) lotSummaryList.removeChild(lotSummaryList.firstChild);

                if (totalPcs <= 0 && validRowCount === 0) {
                    const li = document.createElement('li');
                    li.classList.add('text-muted');
                    li.textContent = 'Belum ada pemilihan LOT atau qty bundle.';
                    lotSummaryList.appendChild(li);
                    return;
                }

                // Hitung estimasi standar BOM + total pemakaian dari INPUT kain
                // (input = autofill pemakaian terakhir / BOM, bisa diedit user).
                // Ringkasan & cek stok pakai nilai INPUT — itu yang benar-benar
                // akan dipotong dari stok. Standar BOM tampil sebagai pembanding.
                let totalBomFabric = 0;
                let bomRowCount = 0;
                let totalInputFabric = 0;
                let inputRowCount = 0;
                const bundleRows = bundlesTbody.querySelectorAll('.bundle-row');
                bundleRows.forEach(tr => {
                    const qtyInput = tr.querySelector('.bundle-qty-pcs');
                    const itemIdInput = tr.querySelector('[name*="finished_item_id"]');
                    if (!qtyInput || !itemIdInput) return;
                    const qty = parseFloat(qtyInput.value || '0');
                    const finishedItemId = parseInt(itemIdInput.value || '0', 10);
                    if (qty <= 0 || !finishedItemId || !fabricItemId) return;

                    const bom = bomData[finishedItemId];
                    if (bom && bom[fabricItemId]) {
                        const { qty: bomQty, scrap_pct } = bom[fabricItemId];
                        totalBomFabric += qty * bomQty * (1 + scrap_pct / 100);
                        bomRowCount++;
                    }

                    const fabInput = parseFloat(tr.querySelector('.bundle-qty-fabric')?.value || '0');
                    if (fabInput > 0) {
                        totalInputFabric += fabInput;
                        inputRowCount++;
                    }
                });

                // ── RENDER RINGKAS: 3 angka besar — Stok · Pakai · Sisa ──
                const plannedFabric = inputRowCount > 0 ? totalInputFabric
                                    : (bomRowCount > 0 ? totalBomFabric : 0);
                const sisa    = totalBalance - plannedFabric;
                const isShort = plannedFabric > 0 && (plannedFabric - totalBalance) > 0.05;

                const tile = (label, val, color, bg) =>
                    `<div style="flex:1;min-width:88px;background:${bg};border-radius:10px;padding:8px 10px;text-align:center;">
                        <div style="font-size:.6rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:${color};opacity:.7;">${label}</div>
                        <div class="mono" style="font-size:1.05rem;font-weight:800;color:${color};line-height:1.3;">${val}</div>
                     </div>`;

                const sisaColor = isShort ? '#dc2626' : '#16a34a';
                const sisaBg    = isShort ? '#fef2f2' : '#f0fdf4';

                const liStats = document.createElement('li');
                liStats.style.cssText = 'display:block;padding:0;border:none;background:none;';
                liStats.innerHTML =
                    `<div style="display:flex;gap:8px;flex-wrap:wrap;">`
                    + tile('Stok Kain', `${totalBalance.toFixed(2)} kg`, '#334155', '#f1f5f9')
                    + tile('Pakai', plannedFabric > 0 ? `${plannedFabric.toFixed(2)} kg` : '—', '#1d4ed8', '#eff6ff')
                    + tile('Sisa', plannedFabric > 0 ? `${sisa.toFixed(2)} kg` : '—', sisaColor, sisaBg)
                    + `</div>`;
                lotSummaryList.appendChild(liStats);

                // Satu baris kecil: pcs (+ standar BOM hanya jika beda dari rencana)
                let metaTxt = `${totalPcs.toFixed(0)} pcs`;
                if (bomRowCount > 0 && Math.abs(totalBomFabric - plannedFabric) > 0.0005) {
                    metaTxt += ` · BOM maks ${totalBomFabric.toFixed(2)} kg`;
                }
                const liMeta = document.createElement('li');
                liMeta.style.cssText = 'border:none;padding:5px 2px 0;';
                liMeta.innerHTML = `<span style="font-size:.72rem;color:#94a3b8;">${metaTxt}</span><span></span>`;
                lotSummaryList.appendChild(liMeta);

                // Warning singkat hanya saat kurang
                if (isShort) {
                    const liShort = document.createElement('li');
                    liShort.style.cssText = 'background:#fef2f2;border:none;border-radius:8px;padding:5px 8px;margin-top:4px;';
                    liShort.innerHTML =
                        `<span style="color:#dc2626;font-weight:700;">⚠️ Kurang ${(plannedFabric - totalBalance).toFixed(2)} kg</span>`
                      + `<span style="color:#dc2626;font-size:.72rem;">Tambah LOT / kurangi qty</span>`;
                    lotSummaryList.appendChild(liShort);
                }

                refreshSubmitStateForBom();

                // Chips per item jadi — ringkas, tanpa header
                const labels = Object.keys(itemSummary).filter(label => itemSummary[label] > 0);
                if (labels.length > 0) {
                    labels.sort((a, b) => a.localeCompare(b));
                    const liChips = document.createElement('li');
                    liChips.style.cssText = 'display:block;border:none;padding:6px 0 0;';
                    liChips.innerHTML = labels.map(label =>
                        `<span class="mono" style="display:inline-block;background:#f1f5f9;border-radius:999px;padding:2px 10px;font-size:.72rem;margin:2px 4px 0 0;">${label} ×${itemSummary[label].toFixed(0)}</span>`
                    ).join('');
                    lotSummaryList.appendChild(liChips);
                }

                checkFabricShortage();
            }

            /* ── INLINE SHORTAGE WARNING ────────────────────────────── */
            function checkFabricShortage() {
                if (!lotBalanceInput) return;
                const totalStock = parseFloat(lotBalanceInput.value || '0');
                let totalUsed = 0;
                bundlesTbody.querySelectorAll('.bundle-qty-fabric').forEach(inp => {
                    totalUsed += parseFloat(inp.value || '0');
                });

                const shortage = totalUsed - totalStock;
                const isShort  = shortage > 0.05 && totalUsed > 0 && totalStock > 0;

                const bar      = document.getElementById('lot-shortage-warning');
                const amountEl = document.getElementById('lot-shortage-amount');
                const bomBtn   = document.getElementById('btn-update-bom-shortage');
                if (!bar) return;

                if (isShort) {
                    if (amountEl) amountEl.textContent = shortage.toFixed(2);
                    bar.style.display = '';
                    refreshShortageBomButton();
                } else {
                    bar.style.display = 'none';
                    const statusEl = document.getElementById('lot-shortage-bom-status');
                    if (statusEl) statusEl.textContent = '';
                    if (bomBtn) {
                        bomBtn.style.display = 'none';
                        bomBtn.onclick = null;
                    }
                }

                if (typeof window.updateLotTargetIndicator === 'function') {
                    window.updateLotTargetIndicator();
                }
            }

            window.updateLotTargetIndicator = function() {
                const pickerInd = document.getElementById('lot-target-indicator');
                if (!pickerInd) return;

                let totalUsed = 0;
                const bundlesTbody = document.getElementById('bundle-rows');
                if (bundlesTbody) {
                    bundlesTbody.querySelectorAll('.bundle-qty-fabric').forEach(inp => {
                        totalUsed += parseFloat(inp.value || '0');
                    });
                }

                if (totalUsed <= 0) {
                    pickerInd.style.display = 'none';
                    return;
                }

                let totalStock = 0;
                document.querySelectorAll('.lot-checkbox:checked').forEach(cb => {
                    const card = cb.closest('.lot-card-item');
                    if (card) {
                        totalStock += parseFloat(card.dataset.balance || '0');
                    }
                });

                pickerInd.style.display = '';
                document.getElementById('lot-target-needed').textContent = totalUsed.toFixed(2);
                document.getElementById('lot-target-picked').textContent = totalStock.toFixed(2);
                
                let pct = (totalStock / totalUsed) * 100;
                if (pct > 100) pct = 100;
                const barEl = document.getElementById('lot-target-bar');
                if (barEl) barEl.style.width = pct + '%';
                
                const warnEl = document.getElementById('lot-target-warning');
                const shortage = totalUsed - totalStock;
                const pickedEl = document.getElementById('lot-target-picked');
                
                if (shortage > 0.05) {
                    if (barEl) barEl.style.backgroundColor = '#dc2626';
                    if (pickedEl) pickedEl.style.color = '#dc2626';
                    if (warnEl) {
                        warnEl.style.display = '';
                        const shortSpan = document.getElementById('lot-target-shortage');
                        if (shortSpan) shortSpan.textContent = shortage.toFixed(2);
                    }
                } else {
                    if (barEl) barEl.style.backgroundColor = '#16a34a';
                    if (pickedEl) pickedEl.style.color = '#16a34a';
                    if (warnEl) warnEl.style.display = 'none';
                }
            };

            function getShortageBomCandidates() {
                const fabricItemId = fabricSelect ? parseInt(fabricSelect.value || '0', 10) : 0;
                if (!fabricItemId) return [];

                const totalStock = parseFloat(lotBalanceInput?.value || '0');
                if (totalStock <= 0) return [];

                const candidates = {};
                bundlesTbody.querySelectorAll('.bundle-row').forEach(tr => {
                    const finishedItemId = parseInt(tr.querySelector('[name*="finished_item_id"]')?.value || '0', 10);
                    const qtyPcs = parseFloat(tr.querySelector('.bundle-qty-pcs')?.value || '0');
                    if (!finishedItemId || qtyPcs <= 0) return;

                    const bom = bomData[finishedItemId]?.[fabricItemId];
                    const quickUrl = bomQuickUrls[finishedItemId] ?? null;
                    if (!bom || !quickUrl) return;

                    if (!candidates[finishedItemId]) {
                        candidates[finishedItemId] = {
                            finishedItemId,
                            fabricItemId,
                            quickUrl,
                            scrapPct: Number(bom.scrap_pct || 0),
                            totalQtyPcs: 0,
                            suggestedBomQty: 0,
                            targetTotalKg: 0,
                        };
                    }

                    candidates[finishedItemId].totalQtyPcs += qtyPcs;
                });

                Object.keys(candidates).forEach((key) => {
                    const c = candidates[key];
                    const denominator = c.totalQtyPcs * (1 + c.scrapPct / 100);
                    if (denominator <= 0) {
                        delete candidates[key];
                        return;
                    }

                    c.targetTotalKg = totalStock;
                    c.suggestedBomQty = totalStock / denominator;
                });

                return Object.values(candidates);
            }

            function applyUpdatedBomToRows(candidate, newQty) {
                if (!candidate || !newQty) return;
                const perPcsWithScrap = Number(newQty) * (1 + Number(candidate.scrapPct || 0) / 100);
                bundlesTbody.querySelectorAll('.bundle-row').forEach(rowTr => {
                    const rowItemId = parseInt(rowTr.querySelector('[name*="finished_item_id"]')?.value || '0', 10);
                    if (rowItemId !== candidate.finishedItemId) return;

                    const qtyPcs = parseFloat(rowTr.querySelector('.bundle-qty-pcs')?.value || '0');
                    const fabricInput = rowTr.querySelector('.bundle-qty-fabric');
                    if (fabricInput && qtyPcs > 0) {
                        fabricInput.value = (qtyPcs * perPcsWithScrap).toFixed(4);
                        fabricInput.dataset.auto = '1';
                    }

                    renderFabricUsageInfo(rowTr);
                    checkBomCapForRow(rowTr);
                });
            }

            function refreshShortageBomButton() {
                const btn = document.getElementById('btn-update-bom-shortage');
                const statusEl = document.getElementById('lot-shortage-bom-status');
                if (!btn) return;

                const candidates = getShortageBomCandidates();
                if (!candidates.length) {
                    btn.style.display = 'none';
                    btn.onclick = null;
                    if (statusEl) statusEl.textContent = '';
                    return;
                }

                btn.style.display = '';
                btn.disabled = false;
                btn.textContent = candidates.length === 1 ? 'Update BOM' : 'Cek BOM';

                btn.onclick = async () => {
                    if (candidates.length !== 1) {
                        const rowBtn = bundlesTbody.querySelector('.btn-bom-quick-update');
                        if (rowBtn) {
                            rowBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            rowBtn.focus?.();
                            if (statusEl) statusEl.textContent = 'Ada beberapa item. Update BOM dari baris yang sesuai.';
                        } else {
                            if (statusEl) statusEl.textContent = 'Pilih item jadi dari daftar, lalu isi qty.';
                        }
                        return;
                    }

                    const c = candidates[0];
                    btn.disabled = true;
                    btn.textContent = 'Mengupdate...';
                    if (statusEl) statusEl.textContent = '';
                    try {
                        const resp = await fetch(c.quickUrl, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                material_item_id: c.fabricItemId,
                                qty: c.suggestedBomQty,
                            }),
                        });
                        const json = await resp.json().catch(() => ({}));
                        if (json.success) {
                            if (bomData[c.finishedItemId]?.[c.fabricItemId]) {
                                bomData[c.finishedItemId][c.fabricItemId].qty = json.new_qty;
                            }
                            applyUpdatedBomToRows(c, json.new_qty);
                            recalcLotSummary();
                            btn.disabled = false;
                            btn.textContent = 'BOM Terupdate';
                            if (statusEl) {
                                statusEl.style.color = '#166534';
                                statusEl.textContent = 'BOM bahan utama diupdate ke ' + Number(json.new_qty).toLocaleString('id-ID', { maximumFractionDigits: 8 }) + '. Pemakaian kain sudah disesuaikan.';
                            }
                        } else {
                            btn.disabled = false;
                            btn.textContent = 'Update BOM';
                            if (statusEl) {
                                statusEl.style.color = '#b91c1c';
                                statusEl.textContent = json.message || 'BOM gagal diupdate. Coba lewat tombol Update BOM di baris item.';
                            }
                        }
                    } catch (e) {
                        btn.disabled = false;
                        btn.textContent = 'Update BOM';
                        if (statusEl) {
                            statusEl.style.color = '#b91c1c';
                            statusEl.textContent = 'Koneksi gagal saat update BOM.';
                        }
                    }
                };
            }

            function updateCurrentLotSummary() {
                const lotCount = getCheckedLots().length;
                const balance = parseFloat(lotBalanceInput.value || '0');

                if (currentFabricLabel) {
                    let fabricText = '-';

                    if (fabricSelect && fabricSelect.value) {
                        const opt = fabricSelect.options[fabricSelect.selectedIndex];
                        if (opt && opt.text) {
                            fabricText = opt.text.trim();
                        } else if (lotCount > 0) {
                            fabricText = 'Mengikuti LOT terpilih';
                        }
                    } else if (lotCount > 0) {
                        fabricText = 'Mengikuti LOT terpilih';
                    }

                    currentFabricLabel.textContent = fabricText;
                }

                if (currentLotCount) currentLotCount.textContent = `${lotCount} LOT`;
                if (currentLotBalance) currentLotBalance.textContent = balance.toFixed(2);
            }

            function scrollRowIntoCenter(tr) {
                if (!tr) return;
                setTimeout(() => {
                    tr.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }, 80);
            }

            // Set fabric_item_id dari LOT pertama yang dicentang (tanpa validasi)
            function enforceSingleFabricForCheckedLots(changedCb = null) {
                const checkedCbs = lotCheckboxes.filter(cb => cb.checked);

                if (checkedCbs.length === 0) {
                    if (fabricSelect) fabricSelect.value = '';
                    updateCurrentLotSummary();
                    refreshAllRowsFabric();
                    return true;
                }

                const firstItemId = parseInt(checkedCbs[0].dataset.itemId || '0', 10);

                if (fabricSelect && firstItemId) {
                    let foundOption = false;
                    Array.from(fabricSelect.options).forEach(opt => {
                        if (parseInt(opt.value || '0', 10) === firstItemId) {
                            fabricSelect.value = opt.value;
                            foundOption = true;
                        }
                    });
                    if (!foundOption) {
                        const infos = getCheckedLotsWithInfo();
                        const opt = document.createElement('option');
                        opt.value = firstItemId;
                        opt.textContent = infos[0]?.itemCode ? `${infos[0].itemCode} (auto)` : `Item #${firstItemId}`;
                        opt.selected = true;
                        fabricSelect.appendChild(opt);
                    }
                }

                updateCurrentLotSummary();
                refreshAllRowsFabric(); // kain berubah → autofill/info/cek BOM semua baris ikut segar
                return true;
            }

            // ── Refresh autofill + info + cek BOM semua baris (dipanggil saat kain/LOT berubah).
            //    Nilai yang sudah diedit manual (data-auto="0") TIDAK ditimpa. ──
            function refreshAllRowsFabric() {
                if (!bundlesTbody) return;
                bundlesTbody.querySelectorAll('.bundle-row').forEach(tr => {
                    const inp = tr.querySelector('.bundle-qty-fabric');
                    if (!inp) return;
                    if (inp.dataset.auto !== '0' || !inp.value) {
                        autofillFabricQtyForRow(tr); // sekaligus render info + cek cap
                    } else {
                        renderFabricUsageInfo(tr);
                        checkBomCapForRow(tr);
                    }
                });
                recalcLotSummary();
            }

            // ── Helper: standar BOM maksimal (kg) untuk qty tertentu ──
            function bomMaxKg(finishedItemId, fabricItemId, qtyPcs) {
                const bom = bomData[finishedItemId];
                if (!bom || !bom[fabricItemId] || qtyPcs <= 0) return null;
                const { qty: bomQty, scrap_pct } = bom[fabricItemId];
                return qtyPcs * bomQty * (1 + scrap_pct / 100);
            }

            // ── Hitung & isi qty_used_fabric: prioritas PEMAKAIAN TERAKHIR → BOM ──
            function autofillFabricQtyForRow(tr) {
                const hiddenItemId   = tr.querySelector('[name*="finished_item_id"]');
                const qtyFabricInput = tr.querySelector('.bundle-qty-fabric');
                if (!hiddenItemId || !qtyFabricInput) return;
                const finishedItemId = parseInt(hiddenItemId.value || '0', 10);
                const fabricItemId   = fabricSelect ? parseInt(fabricSelect.value || '0', 10) : 0;
                const qtyPcs         = parseFloat(tr.querySelector('.bundle-qty-pcs')?.value || '0');

                if (finishedItemId && fabricItemId && qtyPcs > 0) {
                    const usage = lastUsage[finishedItemId]?.[fabricItemId];
                    if (usage && usage.kg_per_pcs > 0) {
                        // Pemakaian aktual cutting terakhir
                        qtyFabricInput.value = (qtyPcs * usage.kg_per_pcs).toFixed(4);
                        qtyFabricInput.dataset.auto = '1';
                    } else {
                        const maxKg = bomMaxKg(finishedItemId, fabricItemId, qtyPcs);
                        if (maxKg !== null) {
                            qtyFabricInput.value = maxKg.toFixed(4);
                            qtyFabricInput.dataset.auto = '1';
                        }
                    }
                }

                renderFabricUsageInfo(tr);
                checkBomCapForRow(tr);
            }

            // ── Info kecil: standar BOM vs pemakaian terakhir + riwayat ──
            function renderFabricUsageInfo(tr) {
                const infoEl = tr.querySelector('.fabric-usage-info');
                if (!infoEl) return;
                const finishedItemId = parseInt(tr.querySelector('[name*="finished_item_id"]')?.value || '0', 10);
                const fabricItemId   = fabricSelect ? parseInt(fabricSelect.value || '0', 10) : 0;
                if (!finishedItemId || !fabricItemId) { infoEl.innerHTML = ''; return; }

                const bom   = bomData[finishedItemId]?.[fabricItemId];
                const usage = lastUsage[finishedItemId]?.[fabricItemId];
                const parts = [];
                if (bom) {
                    const bomMax = bom.qty * (1 + bom.scrap_pct / 100);
                    parts.push(`<span title="Standar BOM${bom.scrap_pct > 0 ? ` (${bom.qty.toFixed(4)} + scrap ${bom.scrap_pct}%)` : ''} per pcs" style="cursor:help;">Std <b>${bomMax.toFixed(4)}</b></span>`);
                }
                if (usage) {
                    const histTitle = 'Pemakaian aktual terakhir per pcs:\n' + (usage.history || [])
                        .map(h => `${h.job_code} (${h.date}): ${h.kg_per_pcs} kg/pcs`)
                        .join('\n');
                    parts.push(`<span title="${histTitle.replace(/"/g, '&quot;')}" style="color:#0d9488;cursor:help;">Akt <b>${usage.kg_per_pcs.toFixed(4)}</b></span>`);
                }
                const infoEditUrl = bomEditUrls[finishedItemId] ?? null;
                if (infoEditUrl && (bom || usage)) {
                    parts.push(`<a href="${infoEditUrl}" target="_blank" title="Buka BOM" style="color:#94a3b8;text-decoration:none;">✎</a>`);
                }
                infoEl.innerHTML = parts.join(' · ');
            }

            // ── Cek pemakaian vs cap BOM; blokir simpan + tawarkan Update BOM ──
            function checkBomCapForRow(tr) {
                const warnEl = tr.querySelector('.fabric-bom-warning');
                const qtyFabricInput = tr.querySelector('.bundle-qty-fabric');
                if (!warnEl || !qtyFabricInput) return;

                const finishedItemId = parseInt(tr.querySelector('[name*="finished_item_id"]')?.value || '0', 10);
                const fabricItemId   = fabricSelect ? parseInt(fabricSelect.value || '0', 10) : 0;
                const qtyPcs         = parseFloat(tr.querySelector('.bundle-qty-pcs')?.value || '0');
                const usedKg         = parseFloat(qtyFabricInput.value || '0');
                const maxKg          = bomMaxKg(finishedItemId, fabricItemId, qtyPcs);

                const exceed = maxKg !== null && usedKg > maxKg + 0.0005;

                if (!exceed) {
                    warnEl.style.display = 'none';
                    warnEl.innerHTML = '';
                    qtyFabricInput.style.borderColor = '';
                    tr.dataset.bomExceed = '0';
                    refreshSubmitStateForBom();
                    return;
                }

                tr.dataset.bomExceed = '1';
                qtyFabricInput.style.borderColor = '#dc2626';

                const bom = bomData[finishedItemId][fabricItemId];
                const actualPerPcs = usedKg / qtyPcs;
                // BOM qty baru agar aktual pas di cap (scrap dipertahankan)
                const suggestedBomQty = actualPerPcs / (1 + bom.scrap_pct / 100);
                const quickUrl = bomQuickUrls[finishedItemId] ?? null;
                const editUrl  = bomEditUrls[finishedItemId] ?? null;

                warnEl.innerHTML =
                    `<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">`
                    + `<span style="color:#dc2626;font-weight:700;" title="Standar BOM maksimal untuk ${qtyPcs} pcs">⚠ Maks ${maxKg.toFixed(2)} kg</span>`
                    + (quickUrl ? `<button type="button" class="btn-bom-quick-update" title="Ubah standar BOM jadi ${suggestedBomQty.toFixed(4)} kg/pcs sesuai angka ini" style="padding:1px 10px;border-radius:4px;border:1px solid #d97706;background:#fef3c7;color:#92400e;font-weight:700;font-size:.7rem;cursor:pointer;">Update BOM</button>` : '')
                    + (editUrl ? `<a href="${editUrl}" target="_blank" title="Buka BOM" style="color:#b45309;font-size:.72rem;text-decoration:none;">✎</a>` : '')
                    + `</div>`;
                warnEl.style.display = '';

                const btn = warnEl.querySelector('.btn-bom-quick-update');
                if (btn) {
                    btn.addEventListener('click', async () => {
                        btn.disabled = true;
                        btn.textContent = 'Mengupdate...';
                        try {
                            const resp = await fetch(quickUrl, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    material_item_id: fabricItemId,
                                    qty: suggestedBomQty,
                                }),
                            });
                            const json = await resp.json().catch(() => ({}));
                            if (json.success) {
                                if (bomData[finishedItemId]?.[fabricItemId]) {
                                    bomData[finishedItemId][fabricItemId].qty = json.new_qty;
                                }
                                // Re-check semua baris dengan item yang sama
                                bundlesTbody.querySelectorAll('.bundle-row').forEach(rowTr => {
                                    const rowItemId = parseInt(rowTr.querySelector('[name*="finished_item_id"]')?.value || '0', 10);
                                    if (rowItemId === finishedItemId) {
                                        renderFabricUsageInfo(rowTr);
                                        checkBomCapForRow(rowTr);
                                    }
                                });
                                recalcLotSummary();
                                btn.disabled = false;
                                btn.textContent = 'BOM Terupdate';
                                warnEl.style.display = '';
                                warnEl.innerHTML = '<span style="color:#166534;font-weight:700;">BOM bahan utama sudah diupdate.</span>';
                            } else {
                                btn.disabled = false;
                                btn.textContent = 'Gagal update';
                            }
                        } catch (e) {
                            btn.disabled = false;
                            btn.textContent = 'Gagal update';
                        }
                    });
                }

                refreshSubmitStateForBom();
            }

            // ── Disable tombol simpan selama ada baris melebihi BOM ──
            function hasBomExceedRows() {
                return !!bundlesTbody.querySelector('.bundle-row[data-bom-exceed="1"]');
            }

            function validateBundleItems(markInvalid = false) {
                if (!bundlesTbody) return true;

                let valid = true;
                bundlesTbody.querySelectorAll('.bundle-row').forEach(tr => {
                    const hiddenItem = tr.querySelector('[name*="finished_item_id"]');
                    const itemInput = tr.querySelector('.js-item-suggest-input');
                    const hasItem = !!(hiddenItem && hiddenItem.value);

                    tr.classList.toggle('bundle-row-item-missing', !hasItem);
                    if (itemInput) {
                        if (hasItem) {
                            itemInput.classList.remove('is-invalid');
                        } else if (markInvalid) {
                            itemInput.classList.add('is-invalid');
                        }
                    }

                    if (!hasItem) valid = false;
                });

                return valid;
            }

            // ── Cek warna: item jadi harus sewarna dengan kain ──
            // Kode warna = gugus huruf paling belakang pada kode item
            // (mis. FLC280BLK → BLK, J7BLK → BLK, K5ABT → ABT).
            function wcColorCode(code) {
                if (!code) return '';
                const m = String(code).toUpperCase().trim().match(/[A-Z]+$/);
                return m ? m[0] : '';
            }

            function wcFabricColor() {
                const sel = document.getElementById('fabric_item_id');
                if (!sel || !sel.value) return '';
                const opt = sel.options[sel.selectedIndex];
                const code = (opt ? (opt.textContent || '') : '').split('—')[0].trim();
                return wcColorCode(code);
            }

            // Tandai baris yang warnanya beda (input merah) + return true jika ada mismatch.
            function markColorMismatches() {
                const fabColor = wcFabricColor();
                let anyMismatch = false;
                bundlesTbody.querySelectorAll('.bundle-row').forEach(tr => {
                    const hiddenItem = tr.querySelector('[name*="finished_item_id"]');
                    const itemInput = tr.querySelector('.js-item-suggest-input');
                    if (!itemInput) return;
                    const hasItem = !!(hiddenItem && hiddenItem.value);
                    if (!hasItem || !fabColor) {
                        tr.classList.remove('bundle-row-color-mismatch');
                        return;
                    }
                    const fgColor = wcColorCode(itemInput.value);
                    const mismatch = !!fgColor && fgColor !== fabColor;
                    tr.classList.toggle('bundle-row-color-mismatch', mismatch);
                    if (mismatch) {
                        itemInput.classList.add('is-invalid');
                        itemInput.title = `Warna item jadi (${fgColor}) beda dengan warna kain (${fabColor}).`;
                        anyMismatch = true;
                    } else if (itemInput.title && itemInput.title.startsWith('Warna item jadi')) {
                        itemInput.title = '';
                    }
                });
                return anyMismatch;
            }

            function refreshSubmitStateForBom(markMissingItems = false) {
                const submitBtn = document.getElementById('btn-save-cutting');
                const fabBtn = document.getElementById('cutting-save-fab');

                const hasMissingItem = !validateBundleItems(markMissingItems);
                const hasColorMismatch = markColorMismatches();
                let disabled = false;
                let title = '';
                if (hasMissingItem) {
                    disabled = true;
                    title = 'Pilih item jadi dulu di semua baris hasil cutting.';
                } else if (hasBomExceedRows()) {
                    disabled = true;
                    title = 'Ada pemakaian kain melebihi standar BOM. Update BOM atau turunkan pemakaian dulu.';
                } else if (hasColorMismatch) {
                    disabled = true;
                    title = 'Warna item jadi tidak sama dengan warna kain. Perbaiki dulu.';
                }

                if (submitBtn) {
                    submitBtn.disabled = disabled;
                    submitBtn.title = title;
                }

                if (fabBtn) {
                    fabBtn.disabled = disabled;
                    fabBtn.title = title;
                }
            }

            window.cuttingValidateBundleItems = function (markInvalid = true) {
                const ok = validateBundleItems(markInvalid);
                refreshSubmitStateForBom(markInvalid);

                if (!ok) {
                    const firstInvalid = bundlesTbody.querySelector('.bundle-row .js-item-suggest-input.is-invalid')
                        || bundlesTbody.querySelector('.bundle-row .js-item-suggest-input');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        setTimeout(() => firstInvalid.focus(), 120);
                    }
                }

                return ok;
            };

            function createBundleRow(initialData = null, autoFocusItem = false) {
                const frag = bundleTemplate.content.cloneNode(true);
                const tr = frag.querySelector('tr');
                const idx = bundleIndexCounter++;

                // adjust nama input
                tr.querySelectorAll('[name]').forEach(el => {
                    const nameAttr = el.getAttribute('name');
                    if (nameAttr && nameAttr.includes('__INDEX__')) {
                        el.setAttribute('name', nameAttr.replace('__INDEX__', idx));
                    }
                });

                // Helper: pindah ke baris berikutnya atau buat baris baru
                function goNextRow() {
                    const allRows = Array.from(bundlesTbody.querySelectorAll('.bundle-row'));
                    const idx = allRows.indexOf(tr);
                    if (idx === allRows.length - 1) {
                        createBundleRow(true);
                    } else {
                        allRows[idx + 1]?.querySelector('.bundle-qty-pcs')?.focus();
                    }
                }

                const qtyInput = tr.querySelector('.bundle-qty-pcs');
                if (qtyInput) {
                    qtyInput.addEventListener('input', () => {
                        autofillFabricQtyForRow(tr);
                        rebuildLotOptionsForAllRows(); // redistribusi setelah fabric update
                        recalcLotSummary();
                    });
                    qtyInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') { e.preventDefault(); goNextRow(); }
                    });
                    qtyInput.addEventListener('focus', () => {
                        setTimeout(() => {
                            qtyInput.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }, 100);
                    });
                }

                // LOT otomatis (hidden input) — tidak ada event listener change

                const btnRemove = tr.querySelector('.btn-remove-row');
                if (btnRemove) {
                    btnRemove.addEventListener('click', () => {
                        tr.remove();
                        updateBundleRowIndices();
                        recalcLotSummary();
                        rebuildLotOptionsForAllRows();
                        refreshSubmitStateForBom(true);
                    });
                }

                bundlesTbody.appendChild(tr);

                if (window.initItemSuggestInputs) {
                    window.initItemSuggestInputs(tr);
                }

                if (initialData) {
                    const idInp = tr.querySelector('.bundle-id');
                    if (idInp && initialData.id) idInp.value = initialData.id;

                    const qtyInp = tr.querySelector('.bundle-qty-pcs');
                    if (qtyInp && initialData.qty_pcs) qtyInp.value = initialData.qty_pcs;

                    const fabricInp = tr.querySelector('.bundle-qty-fabric');
                    if (fabricInp && initialData.qty_used_fabric) {
                        fabricInp.value = parseFloat(initialData.qty_used_fabric).toFixed(4);
                        fabricInp.dataset.auto = '0';
                    }

                    const notesInp = tr.querySelector('input[name*="[notes]"]');
                    if (notesInp && initialData.notes) notesInp.value = initialData.notes;

                    const hiddenItem = tr.querySelector('[name*="finished_item_id"]');
                    if (hiddenItem && initialData.finished_item_id) hiddenItem.value = initialData.finished_item_id;

                    const dispItem = tr.querySelector('.js-item-suggest-input');
                    if (dispItem) {
                        const display = (initialData.finished_item_code ? initialData.finished_item_code + ' — ' : '') + 
                                        (initialData.finished_item_name || '');
                        if (display) {
                            dispItem.value = display;
                            dispItem.dataset.value = display;
                        }
                    }
                    
                    const lotIdInp = tr.querySelector('.bundle-lot-id');
                    if (lotIdInp && initialData.lot_id) lotIdInp.value = initialData.lot_id;
                    
                    if (initialData.lot_id) {
                         const lotObj = lotOptionsCache[initialData.lot_id] || (window.lockedLotInfo && window.lockedLotInfo[initialData.lot_id]);
                         if (lotObj) {
                             const badge = tr.querySelector('.bundle-lot-badge');
                             if (badge) badge.textContent = lotObj.code;
                         }
                    }
                }

                // Jangan bawa filter LOT/warna ke item jadi; LOT adalah bahan baku.
                const suggestWrap = tr.querySelector('.item-suggest-wrap');
                if (suggestWrap) {
                    try {
                        const params = JSON.parse(suggestWrap.dataset.extraParams || '{}');
                        delete params.color_code;
                        delete params.lot_id;
                        suggestWrap.dataset.extraParams = JSON.stringify(params);
                    } catch (e) {}
                }

                // Auto-fill kain dari BOM saat item jadi dipilih (change event bubble dari item-suggest)
                const hiddenItemId = tr.querySelector('[name*="finished_item_id"]');
                if (hiddenItemId) {
                    hiddenItemId.addEventListener('change', () => {
                        const itemInput = tr.querySelector('.js-item-suggest-input');
                        if (hiddenItemId.value && itemInput) {
                            itemInput.classList.remove('is-invalid');
                        }
                        autofillFabricQtyForRow(tr);
                        rebuildLotOptionsForAllRows(); // redistribusi setelah item dipilih & BOM diisi
                        recalcLotSummary();
                        refreshSubmitStateForBom(true);

                        if (hiddenItemId.value) {
                            setTimeout(() => {
                                const qty = tr.querySelector('.bundle-qty-pcs');
                                qty?.focus();
                                qty?.select?.();
                            }, 40);
                        }
                    });
                }

                // Simpan draft saat notes berubah + Enter → baris berikutnya
                const notesInput = tr.querySelector('input[name*="[notes]"]');
                if (notesInput) {
                    notesInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') { e.preventDefault(); goNextRow(); }
                    });
                }

                // Qty fabric diedit manual → tandai manual (tidak ditimpa autofill),
                // cek cap BOM + recalc ringkasan
                const qtyFabricInp = tr.querySelector('.bundle-qty-fabric');
                if (qtyFabricInp) {
                    qtyFabricInp.addEventListener('input', () => {
                        qtyFabricInp.dataset.auto = '0';
                        checkBomCapForRow(tr);
                        recalcLotSummary();
                    });
                }

                const itemCell = tr.querySelector('td:nth-child(3)');
                const itemInput = itemCell ? itemCell.querySelector('input[type="text"]') : null;
                if (itemInput) {
                    const handleItemFocus = () => scrollRowIntoCenter(tr);

                    itemInput.addEventListener('focus', handleItemFocus);
                    itemInput.addEventListener('click', handleItemFocus);
                    itemInput.addEventListener('input', () => {
                        handleItemFocus();
                        if (hiddenItemId) hiddenItemId.value = '';
                        itemInput.classList.add('is-invalid');
                        refreshSubmitStateForBom(true);
                    });
                    itemInput.addEventListener('blur', () => {
                        if (!hiddenItemId?.value) {
                            itemInput.classList.add('is-invalid');
                        }
                        refreshSubmitStateForBom(true);
                    });
                    // Enter di item → pindah ke qty pcs
                    itemInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            if (hiddenItemId?.value) {
                                tr.querySelector('.bundle-qty-pcs')?.focus();
                            } else {
                                itemInput.classList.add('is-invalid');
                                refreshSubmitStateForBom(true);
                            }
                        }
                    });
                }

                updateBundleRowIndices();
                rebuildLotOptionsForAllRows();
                recalcLotSummary();
                refreshSubmitStateForBom(true);

                if (autoFocusItem) {
                    setTimeout(() => {
                        const inp = tr.querySelector('.bundle-qty-pcs');
                        if (inp) {
                            scrollRowIntoCenter(tr);
                            inp.focus();
                        }
                    }, 80);
                }
            }

            // =======================
            // EVENT: pilih LOT
            // =======================
            function bindLotCheckboxEvents() {
                lotCheckboxes.forEach(cb => {
                    if (cb.dataset.boundForm === '1') return;
                    cb.dataset.boundForm = '1';

                    cb.addEventListener('change', () => {
                    if (lotsLocked) return;

                    const ok = enforceSingleFabricForCheckedLots(cb);
                    if (!ok) return;

                    recalcLotBalanceFromCheckedLots();
                    rebuildLotOptionsForAllRows();
                    recalcLotSummary();

                    });
                });
            }

            bindLotCheckboxEvents();

            window.cuttingLotsDidRefresh = function () {
                rebuildLotRuntimeFromDom();
                bindLotCheckboxEvents();
                enforceSingleFabricForCheckedLots();
                recalcLotBalanceFromCheckedLots();
                rebuildLotOptionsForAllRows();
                recalcLotSummary();
            };

            btnSelectAllLots?.addEventListener('click', () => {
                if (lotsLocked) return;

                lotCheckboxes.forEach(cb => {
                    if (!cb.closest('.lot-row')?.classList.contains('lot-hidden')) {
                        cb.checked = true;
                    }
                });

                enforceSingleFabricForCheckedLots();
                recalcLotBalanceFromCheckedLots();
                rebuildLotOptionsForAllRows();
                recalcLotSummary();

            });

            btnUnselectAllLots?.addEventListener('click', () => {
                if (lotsLocked) return;

                lotCheckboxes.forEach(cb => cb.checked = false);
                recalcLotBalanceFromCheckedLots();
                recalcLotSummary();
                if (fabricSelect) {
                    fabricSelect.value = '';
                }
                updateCurrentLotSummary();
                rebuildLotOptionsForAllRows();

            });

            btnConfirmLots?.addEventListener('click', () => {
                if (lotsLocked) {
                    // user habis klik "Ubah LOT" lalu balik lagi
                    showMainContent();
                    return;
                }

                const checked = getCheckedLots();
                if (checked.length === 0) {
                    alert('Pilih minimal satu LOT terlebih dahulu.');
                    return;
                }

                const ok = enforceSingleFabricForCheckedLots();
                if (!ok) return;

                recalcLotBalanceFromCheckedLots();
                rebuildLotOptionsForAllRows();
                recalcLotSummary();
                lockLotSelection();
                updateCurrentLotSummary();
                showMainContent();

            });

            btnChangeLots?.addEventListener('click', () => {
                showPickLotSection();
                unlockLotSelection();

            });

            // FAB mobile — delegasi ke btn-save-cutting (buka modal)
            document.getElementById('cutting-save-fab')?.addEventListener('click', () => {
                document.getElementById('btn-save-cutting')?.click();
            });

            // Shortage bar — "Tambah LOT" tombol
            document.getElementById('btn-add-lot-shortage')?.addEventListener('click', () => {
                unlockLotSelection();
                showPickLotSection();
            });

            btnAddRow?.addEventListener('click', () => {
                createBundleRow(true);

            });

            // INIT
            const rowsExisting = @json($rowsExisting ?? []);
            const isLotsLocked = @json($isLotsLocked ?? false);
            window.lockedLotInfo = @json($lockedLotInfo ?? []);
            
            if (isLotsLocked) {
                // If lots are locked, main content is directly shown
                document.getElementById('cutting-main-content').classList.remove('d-none');
            } else {
                recalcLotBalanceFromCheckedLots();
                updateCurrentLotSummary();
            }

            if (rowsExisting && rowsExisting.length > 0) {
                rowsExisting.forEach(row => {
                    // Hanya insert baris kosong jika array ada nilainya
                    // Jika default kosong (bundle_no=1 tapi null id), itu juga valid
                    createBundleRow(row, false);
                });
                updateBundleRowIndices();
                setTimeout(() => {
                    recalcLotSummary();
                    rebuildLotOptionsForAllRows();
                }, 300);
            } else {
                createBundleRow(null, false);
            }
        });
    </script>
@endpush
