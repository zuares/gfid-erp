{{-- resources/views/production/cutting_jobs/_form.blade.php --}}

@php
    $fabricItems = $lotStocks->map(fn($row) => $row->lot->item)->unique('id')->values();

    // default warehouse RM RAW MATERIALS
    $defaultWarehouse =
        $warehouses->firstWhere('code', 'RM') ??
        ($warehouses->firstWhere('name', 'RM RAW MATERIALS') ?? $warehouses->first());
    $selectedWarehouseId = $defaultWarehouse?->id;

    // default operator MRF
    $defaultOperatorId = optional($operators->firstWhere('code', 'MRF'))->id;
    $selectedOperatorId = $defaultOperatorId;
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
            }

            .cutting-card-header {
                padding: .6rem .85rem;
                gap: .4rem;
            }

            .cutting-card-header h5 {
                font-size: .82rem;
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
                gap: .36rem;
            }

            .cutting-stepbar-text {
                font-size: .68rem;
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
                grid-template-columns: 18px 1.6fr 0.75fr auto;
                grid-template-rows: auto auto;
                column-gap: .35rem;
                row-gap: .2rem;
                align-items: center;
                border: 1px solid rgba(148, 163, 184, .25);
                border-left: 3px solid rgba(148, 163, 184, .3);
                border-radius: 10px;
                padding: .55rem .55rem .45rem .6rem;
                margin-bottom: .35rem;
                background: var(--card);
                box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
                overflow: visible;
                transition: border-left-color .14s, box-shadow .14s;
            }

            .bundles-table tbody tr.bundle-row.lot-assigned {
                border-left-color: #2563eb;
                box-shadow: 0 3px 10px rgba(37, 99, 235, .1);
            }

            /* Nomor — col 1 row 1 */
            .bundles-table tbody td:nth-child(1) {
                display: flex !important;
                align-items: center;
                justify-content: center;
                grid-column: 1;
                grid-row: 1;
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
                grid-column: 2;
                grid-row: 1;
                min-width: 0;
                border: 0;
                padding: 0;
                overflow: visible;
                position: relative;
            }

            /* Qty — col 3 row 1 */
            .bundles-table tbody td:nth-child(4) {
                grid-column: 3;
                grid-row: 1;
                border: 0;
                padding: 0;
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
                grid-column: 4;
                grid-row: 1;
                display: flex !important;
                align-items: center;
                justify-content: flex-end;
                border: 0;
                padding: 0;
            }

            /* Reset border/padding semua td */
            .bundles-table tbody td {
                border: 0 !important;
                padding: 0;
            }

            .bundles-table .form-control-sm,
            .bundles-table .form-select-sm {
                min-height: 44px;
                border-radius: 10px;
                font-size: .88rem;
                padding: .38rem .52rem;
            }

            .bundle-qty-pcs {
                text-align: left !important;
                font-size: .96rem;
                font-weight: 600;
            }

            .bundles-table tbody tr.bundle-row {
                position: relative;
            }

            .btn-remove-row {
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }

            /* Bundle row padding lebih besar untuk jarak label */
            .bundles-table tbody tr.bundle-row {
                padding: .65rem .55rem .5rem .6rem;
                gap: .4rem;
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
        }
    </style>
@endpush

<form action="{{ route('production.cutting_jobs.store') }}" method="POST" id="cutting-form" autocomplete="off">
    @csrf

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
    @include('production.cutting_jobs._pick_lot')

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
                    </div>
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
                                <th style="min-width: 90px;" class="text-end">Kain (kg)</th>
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
                        <td class="bundle-index mono">1</td>
                        <td class="bundle-lot-col">
                            {{-- LOT otomatis didistribusi oleh JS (round-robin) --}}
                            <input type="hidden" class="bundle-lot-select" name="bundles[__INDEX__][lot_id]" value="">
                            <span class="bundle-lot-badge">—</span>
                        </td>
                        <td>
                            {{-- ITEM JADI pakai component item-suggest (idName wajib) --}}
                            <x-item-suggest idName="bundles[__INDEX__][finished_item_id]"
                                displayName="bundles[__INDEX__][finished_item_display]" placeholder="- Item Jadi -"
                                type="finished_good" displayMode="code" :extraParams="['lot_id' => null]" />
                        </td>
                        <td>
                            <x-number-input name="bundles[__INDEX__][qty_pcs]" step="0.01" min="0"
                                inputmode="decimal" size="sm" placeholder="Qty" class="bundle-qty-pcs bundle-qty" />
                        </td>
                        <td>
                            {{-- qty_used_fabric: auto-fill dari BOM, readonly --}}
                            <input type="text" inputmode="decimal" autocomplete="off"
                                name="bundles[__INDEX__][qty_used_fabric]"
                                class="form-control form-control-sm text-end bundle-qty-fabric"
                                placeholder="auto" readonly
                                style="background:var(--bs-secondary-bg,#f8f9fa);color:var(--muted);cursor:default;" />
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

        {{-- CARD: SUMMARY LOT (desktop only) --}}
        <div class="cutting-card d-none d-md-block">
            <div class="cutting-card-header">
                <h5>Ringkasan Kain & Bundles</h5>
                <span class="badge-soft">
                    Info total kain tersedia & estimasi pemakaian
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
            const lotRows = Array.from(document.querySelectorAll('.lot-row'));
            const lotCheckboxes = Array.from(document.querySelectorAll('.lot-checkbox'));
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
            // CSRF token untuk PATCH request
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

            function isMobile() {
                return window.matchMedia('(max-width: 767.98px)').matches;
            }

            // map lot_id -> info (termasuk itemId biar bisa kunci 1 item kain)
            const lotInfoMap = {};
            lotRows.forEach(tr => {
                const lotId = parseInt(tr.dataset.lotId, 10);
                const itemId = parseInt(tr.dataset.itemId || '0', 10);
                const balance = parseFloat(tr.dataset.balance ?? '0');
                const code = tr.querySelector('.lot-code')?.textContent?.trim() ?? '';
                const itemCode = (tr.dataset.itemCode || '').trim();
                lotInfoMap[lotId] = {
                    lotId,
                    itemId,
                    code,
                    balance,
                    itemCode,
                };
            });

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

            // Set color_code di extraParams semua item suggest baris bundle
            function applyColorToAllBundleRows(color) {
                if (!color) return;
                document.querySelectorAll('.bundle-row .item-suggest-wrap').forEach(wrap => {
                    try {
                        const params = JSON.parse(wrap.dataset.extraParams || '{}');
                        params.color_code = color;
                        wrap.dataset.extraParams = JSON.stringify(params);
                    } catch (e) {}
                });
                window.cuttingDefaultColor = color;
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

            // helper: update data-extra-params (lot_id) untuk item-suggest di baris tertentu
            function updateRowItemSuggestExtraParams(tr) {
                if (!tr) return;
                const select = tr.querySelector('.bundle-lot-select');
                const wrap = tr.querySelector('.item-suggest-wrap');
                if (!wrap) return;

                let extraParams = {};
                try {
                    extraParams = JSON.parse(wrap.dataset.extraParams || '{}') || {};
                } catch (e) {
                    extraParams = {};
                }

                const lotId = select ? (select.value || null) : null;
                extraParams.lot_id = lotId && lotId !== '' ? lotId : null;
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

                // Total kain tersedia di LOT
                const li1 = document.createElement('li');
                li1.innerHTML = totalBalance > 0
                    ? `<span>Stok Kain:</span><span class="mono">${totalBalance.toFixed(2)} kg</span>`
                    : `<span>Stok Kain:</span><span class="mono" style="color:#dc2626">0,00 kg (akan minus di RM)</span>`;
                lotSummaryList.appendChild(li1);

                const li2 = document.createElement('li');
                li2.innerHTML =
                    `<span>Total qty pcs bundles:</span><span class="mono">${totalPcs.toFixed(2)}</span>`;
                lotSummaryList.appendChild(li2);

                // Hitung estimasi BOM per bundle dari tabel
                let totalBomFabric = 0;
                let bomRowCount = 0;
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
                });

                if (bomRowCount > 0) {
                    const shortage   = totalBomFabric - totalBalance;
                    const isShort    = shortage > 0.05; // toleransi 50g — di bawah itu dianggap cukup
                    const bomColor   = isShort ? '#dc2626' : '#1d4ed8';

                    const liBom = document.createElement('li');
                    liBom.innerHTML =
                        `<span>Estimasi kain pakai (BOM):</span><span class="mono" style="color:${bomColor};font-weight:700">${totalBomFabric.toFixed(4)} kg</span>`;
                    lotSummaryList.appendChild(liBom);

                    if (isShort) {
                        // Baris warning stok kurang
                        const liShort = document.createElement('li');
                        liShort.style.cssText = 'background:#fef2f2;border-radius:6px;padding:4px 6px;margin-top:2px;';
                        liShort.innerHTML =
                            `<span style="color:#dc2626;font-weight:600;">⚠️ Stok LOT kurang ${shortage.toFixed(4)} kg</span>`
                          + `<span style="color:#dc2626;font-size:.75rem;">Tambah LOT atau kurangi qty</span>`;
                        lotSummaryList.appendChild(liShort);

                        // Tampilkan per-item BOM detail + tombol Edit BOM
                        const affectedItems = [];
                        bundleRows.forEach(tr => {
                            const qtyInput   = tr.querySelector('.bundle-qty-pcs');
                            const itemIdInp  = tr.querySelector('[name*="finished_item_id"]');
                            const itemLabel  = getBundleItemLabel(tr);
                            if (!qtyInput || !itemIdInp) return;
                            const qty            = parseFloat(qtyInput.value || '0');
                            const finishedItemId = parseInt(itemIdInp.value || '0', 10);
                            if (qty <= 0 || !finishedItemId || !fabricItemId) return;
                            const bom = bomData[finishedItemId];
                            if (!bom || !bom[fabricItemId]) return;
                            const { qty: bomQty } = bom[fabricItemId];
                            // dedupe by finishedItemId
                            if (!affectedItems.find(x => x.id === finishedItemId)) {
                                affectedItems.push({ id: finishedItemId, label: itemLabel, bomQty });
                            }
                        });

                        if (affectedItems.length > 0) {
                            const liEditHeader = document.createElement('li');
                            liEditHeader.style.cssText = 'margin-top:4px;font-size:.75rem;color:#92400e;font-weight:600;';
                            liEditHeader.innerHTML = `<span>BOM salah? Koreksi standar kain:</span><span></span>`;
                            lotSummaryList.appendChild(liEditHeader);

                            affectedItems.forEach(item => {
                                const editUrl  = bomEditUrls[item.id]  ?? null;
                                const quickUrl = bomQuickUrls[item.id] ?? null;
                                // Saran BOM: scale down proporsional agar total estimasi = stok tersedia
                                const suggestedQty = totalBalance > 0
                                    ? (item.bomQty * totalBalance / totalBomFabric)
                                    : 0;

                                const liEdit = document.createElement('li');
                                liEdit.style.cssText = 'background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:6px 8px;margin-top:3px;font-size:.77rem;display:flex;flex-direction:column;gap:4px;';

                                // Baris judul + link edit
                                const rowTitle = document.createElement('div');
                                rowTitle.style.cssText = 'display:flex;justify-content:space-between;align-items:center;gap:6px;';
                                rowTitle.innerHTML = `<span style="color:#92400e;font-weight:600;">${item.label}</span>`
                                    + (editUrl
                                        ? `<a href="${editUrl}" target="_blank" style="color:#b45309;font-size:.7rem;text-decoration:underline;">Lihat BOM →</a>`
                                        : '');
                                liEdit.appendChild(rowTitle);

                                // Baris qty sekarang → saran + tombol Terapkan
                                const rowQty = document.createElement('div');
                                rowQty.style.cssText = 'display:flex;align-items:center;gap:8px;flex-wrap:wrap;';
                                rowQty.innerHTML =
                                    `<span style="color:#78350f;">Sekarang: <strong>${item.bomQty.toFixed(4)} kg/pcs</strong></span>`
                                  + `<span style="color:#6b7280;">→</span>`
                                  + `<span style="color:#15803d;">Saran: <strong>${suggestedQty.toFixed(4)} kg/pcs</strong></span>`;

                                if (quickUrl) {
                                    const btnApply = document.createElement('button');
                                    btnApply.type = 'button';
                                    btnApply.textContent = 'Terapkan';
                                    btnApply.style.cssText = 'margin-left:auto;padding:2px 10px;border-radius:4px;border:1px solid #d97706;background:#fef3c7;color:#92400e;font-weight:700;font-size:.72rem;cursor:pointer;white-space:nowrap;';

                                    btnApply.addEventListener('click', async () => {
                                        btnApply.disabled = true;
                                        btnApply.textContent = '⏳';

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
                                                    qty: suggestedQty,
                                                }),
                                            });

                                            const json = await resp.json();
                                            if (json.success) {
                                                // Update bomData in-memory agar estimasi ikut berubah
                                                if (bomData[item.id] && bomData[item.id][fabricItemId]) {
                                                    bomData[item.id][fabricItemId].qty = json.new_qty;
                                                }
                                                // Update qty_used_fabric di semua bundle row yang pakai item ini
                                                bundlesTbody.querySelectorAll('.bundle-row').forEach(rowTr => {
                                                    const rowItemId = parseInt(rowTr.querySelector('[name*="finished_item_id"]')?.value || '0', 10);
                                                    if (rowItemId === item.id) autofillFabricQtyForRow(rowTr);
                                                });
                                                btnApply.textContent = '✓ Diterapkan';
                                                btnApply.style.cssText = 'margin-left:auto;padding:2px 10px;border-radius:4px;border:1px solid #16a34a;background:#dcfce7;color:#15803d;font-weight:700;font-size:.72rem;cursor:default;white-space:nowrap;';
                                                // Recalc agar warning hilang jika cukup
                                                recalcLotSummary();

                                            } else {
                                                btnApply.textContent = '✗ Gagal';
                                                btnApply.disabled = false;
                                                btnApply.style.background = '#fee2e2';
                                                btnApply.style.color = '#dc2626';
                                            }
                                        } catch (e) {
                                            btnApply.textContent = '✗ Error';
                                            btnApply.disabled = false;
                                            btnApply.style.background = '#fee2e2';
                                            btnApply.style.color = '#dc2626';
                                        }
                                    });

                                    rowQty.appendChild(btnApply);
                                }

                                liEdit.appendChild(rowQty);
                                lotSummaryList.appendChild(liEdit);
                            });
                        }

                        // Tampilkan warning saja, jangan disable tombol
                        const submitBtn = document.getElementById('btn-save-cutting');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.title = '';
                        }
                    } else {
                        // Sisa kain setelah pemakaian
                        const sisa = totalBalance - totalBomFabric;
                        const liNote = document.createElement('li');
                        liNote.style.cssText = 'background:#f0fdf4;border-radius:6px;padding:4px 6px;margin-top:2px;';
                        liNote.innerHTML =
                            `<span style="color:#16a34a;font-weight:600;">✓ Stok cukup</span>`
                          + `<span style="color:#16a34a;font-size:.75rem;">Sisa ~${sisa.toFixed(4)} kg</span>`;
                        lotSummaryList.appendChild(liNote);

                        // Re-enable tombol submit
                        const submitBtn = document.getElementById('btn-save-cutting');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.title = '';
                        }
                    }
                } else if (validRowCount > 0 && totalBalance > 0) {
                    const perRow = totalBalance / validRowCount;
                    const li3 = document.createElement('li');
                    li3.innerHTML =
                        `<span>Estimasi kain per baris (bagi rata):</span><span class="mono">${perRow.toFixed(2)}</span>`;
                    lotSummaryList.appendChild(li3);
                } else if (validRowCount > 0 && totalBalance <= 0) {
                    const liWarn = document.createElement('li');
                    liWarn.classList.add('text-muted');
                    liWarn.style.fontSize = '.72rem';
                    liWarn.innerHTML = `<span>⚠️ Tidak ada BOM & saldo LOT 0 — tidak ada pengurangan kain</span><span></span>`;
                    lotSummaryList.appendChild(liWarn);
                }

                const labels = Object.keys(itemSummary).filter(label => itemSummary[label] > 0);
                if (labels.length > 0) {
                    const liHeader = document.createElement('li');
                    liHeader.classList.add('mt-1', 'fw-semibold');
                    liHeader.innerHTML = `<span>Ringkasan per item jadi:</span><span></span>`;
                    lotSummaryList.appendChild(liHeader);

                    labels.sort((a, b) => a.localeCompare(b));
                    labels.forEach(label => {
                        const qty = itemSummary[label];
                        const liItem = document.createElement('li');
                        liItem.innerHTML =
                            `<span class="mono">${label}</span><span class="mono">${qty.toFixed(2)}</span>`;
                        lotSummaryList.appendChild(liItem);
                    });
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
                if (!bar) return;

                if (isShort) {
                    if (amountEl) amountEl.textContent = shortage.toFixed(2);
                    bar.style.display = '';
                } else {
                    bar.style.display = 'none';
                }
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
                return true;
            }

            // ── Hitung & isi qty_used_fabric dari BOM untuk baris tertentu ──
            function autofillFabricQtyForRow(tr) {
                const hiddenItemId   = tr.querySelector('[name*="finished_item_id"]');
                const qtyFabricInput = tr.querySelector('.bundle-qty-fabric');
                if (!hiddenItemId || !qtyFabricInput) return;
                const finishedItemId = parseInt(hiddenItemId.value || '0', 10);
                const fabricItemId   = fabricSelect ? parseInt(fabricSelect.value || '0', 10) : 0;
                const qtyPcs         = parseFloat(tr.querySelector('.bundle-qty-pcs')?.value || '0');
                if (finishedItemId && fabricItemId) {
                    const bom = bomData[finishedItemId];
                    if (bom && bom[fabricItemId]) {
                        const { qty: bomQty, scrap_pct } = bom[fabricItemId];
                        qtyFabricInput.value = (qtyPcs * bomQty * (1 + scrap_pct / 100)).toFixed(4);
                        return;
                    }
                }
                // Tidak ada BOM — biarkan kosong agar user bisa isi manual
            }

            function createBundleRow(autoFocusItem = false) {
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
                        allRows[idx + 1]?.querySelector('td:nth-child(3) input[type="text"]')?.focus();
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
                    });
                }

                bundlesTbody.appendChild(tr);

                if (window.initItemSuggestInputs) {
                    window.initItemSuggestInputs(tr);
                }

                // Set filter warna ke item suggest baris baru
                if (window.cuttingDefaultColor) {
                    const wrap = tr.querySelector('.item-suggest-wrap');
                    if (wrap) {
                        try {
                            const params = JSON.parse(wrap.dataset.extraParams || '{}');
                            params.color_code = window.cuttingDefaultColor;
                            wrap.dataset.extraParams = JSON.stringify(params);
                        } catch (e) {}
                    }
                    // input tidak di-prefill — filter server-side sudah cukup
                }

                // Auto-fill kain dari BOM saat item jadi dipilih (change event bubble dari item-suggest)
                const hiddenItemId = tr.querySelector('[name*="finished_item_id"]');
                if (hiddenItemId) {
                    hiddenItemId.addEventListener('change', () => {
                        autofillFabricQtyForRow(tr);
                        rebuildLotOptionsForAllRows(); // redistribusi setelah item dipilih & BOM diisi
                        recalcLotSummary();
                    });
                }

                // Simpan draft saat notes berubah + Enter → baris berikutnya
                const notesInput = tr.querySelector('input[name*="[notes]"]');
                if (notesInput) {
                    notesInput.addEventListener('input',);
                    notesInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') { e.preventDefault(); goNextRow(); }
                    });
                }

                // Simpan draft saat qty fabric diisi manual
                const qtyFabricInp = tr.querySelector('.bundle-qty-fabric');
                if (qtyFabricInp) {
                    qtyFabricInp.addEventListener('input',);
                }

                const itemCell = tr.querySelector('td:nth-child(3)');
                const itemInput = itemCell ? itemCell.querySelector('input[type="text"]') : null;
                if (itemInput) {
                    const handleItemFocus = () => scrollRowIntoCenter(tr);

                    itemInput.addEventListener('focus', handleItemFocus);
                    itemInput.addEventListener('click', handleItemFocus);
                    itemInput.addEventListener('input', handleItemFocus);
                    // Enter di item → pindah ke qty pcs
                    itemInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            tr.querySelector('.bundle-qty-pcs')?.focus();
                        }
                    });
                }

                updateBundleRowIndices();
                rebuildLotOptionsForAllRows();
                recalcLotSummary();

                if (autoFocusItem) {
                    setTimeout(() => {
                        const inp = tr.querySelector('td:nth-child(3) input[type="text"]');
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
            lotCheckboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    if (lotsLocked) return;

                    const ok = enforceSingleFabricForCheckedLots(cb);
                    if (!ok) return;

                    recalcLotBalanceFromCheckedLots();
                    rebuildLotOptionsForAllRows();
                    recalcLotSummary();

                });
            });

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

            // INIT — mulai bersih, user pilih LOT manual
            recalcLotBalanceFromCheckedLots();
            updateCurrentLotSummary();
            createBundleRow(false);
        });
    </script>
@endpush
