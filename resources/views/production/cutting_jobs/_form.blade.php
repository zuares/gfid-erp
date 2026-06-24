{{-- resources/views/production/cutting_jobs/_form.blade.php --}}

@php
    $fabricItems = $lotStocks->map(fn($row) => $row->lot->item)->unique('id')->values();

    // default warehouse RM RAW MATERIALS
    $defaultWarehouse =
        $warehouses->firstWhere('code', 'RM') ??
        ($warehouses->firstWhere('name', 'RM RAW MATERIALS') ?? $warehouses->first());
    $selectedWarehouseId = old('warehouse_id', $defaultWarehouse?->id);

    // default operator MRF
    $defaultOperatorId = optional($operators->firstWhere('code', 'MRF'))->id;
    $selectedOperatorId = old('operator_id', $defaultOperatorId);
@endphp

@push('head')
    <style>
        .cutting-card {
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: var(--card);
            margin-bottom: .75rem;
            overflow: hidden;
        }

        .cutting-card-header {
            padding: .58rem .68rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
        }

        .cutting-card-header h5 {
            color: var(--text);
            font-size: .88rem;
            font-weight: 900;
            letter-spacing: .01em;
            margin: 0;
        }

        .cutting-card-body {
            padding: .62rem .68rem;
            overflow: visible;
            position: relative;
        }

        .badge-soft {
            color: var(--muted);
            font-size: .64rem;
            font-weight: 900;
            border-radius: 999px;
            padding: .12rem .48rem;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: rgba(148, 163, 184, 0.06);
            white-space: nowrap;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .bundles-table-wrap {
            overflow: visible;
            position: relative;
        }

        .bundles-table {
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 0;
            font-size: .8rem;
        }

        .bundles-table thead th {
            color: var(--muted);
            font-size: .64rem;
            font-weight: 900;
            letter-spacing: .05em;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(148, 163, 184, 0.16);
            padding: .38rem .42rem;
        }

        .bundles-table tbody td {
            padding: .34rem .42rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(148, 163, 184, 0.08);
        }

        .bundle-notes-cell {
            min-width: 140px;
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

        .cutting-selected-lot-strip {
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 12px;
            background: rgba(255, 255, 255, .42);
        }

        body[data-theme="dark"] .cutting-selected-lot-strip {
            background: rgba(15, 23, 42, .20);
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
                border-radius: 10px;
                box-shadow: none;
            }

            .cutting-card-header {
                padding: .48rem .56rem;
                gap: .4rem;
            }

            .cutting-card-header h5 {
                font-size: .82rem;
            }

            .cutting-card-body {
                padding: .52rem .56rem;
            }

            .badge-soft {
                font-size: .58rem;
                padding: .08rem .34rem;
            }

            .cutting-selected-lot-strip {
                border-radius: 10px;
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

            .cutting-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .cutting-actions .btn-primary {
                width: 100%;
            }

            .bundles-table-wrap {
                overflow-x: visible;
            }

            .bundles-table,
            .bundles-table tbody,
            .bundles-table tfoot {
                display: block;
                width: 100%;
            }

            .bundles-table thead {
                display: none;
            }

            .bundles-table tbody tr.bundle-row {
                display: block;
                border: 1px solid rgba(148, 163, 184, .18);
                border-radius: 10px;
                padding: .5rem .56rem;
                margin-bottom: .36rem;
                background: var(--card);
            }

            .bundles-table tbody td {
                display: grid;
                grid-template-columns: 72px minmax(0, 1fr);
                align-items: center;
                gap: .45rem;
                border: 0;
                padding: .18rem 0;
            }

            .bundles-table tbody td::before {
                color: var(--muted);
                font-size: .56rem;
                font-weight: 900;
                letter-spacing: .04em;
                line-height: 1;
                text-transform: uppercase;
            }

            .bundles-table tbody td:nth-child(1) {
                display: inline-flex;
                width: auto;
                padding: 0 .35rem .08rem 0;
                color: var(--muted);
                font-size: .68rem;
                font-weight: 900;
            }

            .bundles-table tbody td:nth-child(1)::before,
            .bundles-table tbody td:nth-child(2)::before {
                content: none;
            }

            .bundles-table tbody td:nth-child(3)::before {
                content: "Item";
            }

            .bundles-table tbody td:nth-child(4)::before {
                content: "Qty";
            }

            .bundles-table tbody td:nth-child(5)::before {
                content: "Kain";
            }

            .bundles-table tbody td:nth-child(6)::before {
                content: "Catatan";
            }

            .bundles-table tbody td:nth-child(7) {
                display: flex;
                justify-content: flex-end;
                padding-top: .12rem;
            }

            .bundles-table tbody td:nth-child(7)::before {
                content: none;
            }

            .bundles-table .form-control-sm,
            .bundles-table .form-select-sm {
                min-height: 32px;
                border-radius: 8px;
                font-size: .78rem;
                padding: .28rem .42rem;
            }

            .bundle-qty-pcs,
            .bundle-qty-fabric {
                text-align: center !important;
                font-weight: 900;
            }

            .btn-remove-row {
                width: 28px;
                height: 28px;
                border: 1px solid rgba(220, 38, 38, .18) !important;
                border-radius: 999px;
                background: rgba(254, 242, 242, .72);
                line-height: 1;
                padding: 0 !important;
                text-decoration: none;
            }

            .bundles-table tfoot tr,
            .bundles-table tfoot td {
                display: block;
                width: 100%;
            }

            .bundles-table tfoot td {
                padding: .14rem 0 0;
                border: 0;
            }

            #btn-add-row {
                width: 100%;
            }
        }
    </style>
@endpush

<form action="{{ route('production.cutting_jobs.store') }}" method="POST" id="cutting-form">
    @csrf

    {{-- dipakai untuk ringkasan / estimasi --}}
    <input type="hidden" name="lot_balance" id="lot_balance" value="{{ old('lot_balance', 0) }}">

    {{-- Item kain (auto dari LOT terpilih, diset via JS) --}}
    <select name="fabric_item_id" id="fabric_item_id" class="d-none">
        <option value="">- Pilih Item Kain -</option>
        @foreach ($fabricItems as $item)
            <option value="{{ $item->id }}" @selected(old('fabric_item_id') == $item->id)>
                {{ $item->code }} — {{ $item->name }}
            </option>
        @endforeach
    </select>

    {{-- STEP 1: PILIH KAIN & LOT --}}
    @include('production.cutting_jobs._pick_lot')

    {{-- STEP 2: KONTEN UTAMA (muncul setelah LOT disimpan) --}}
    <div id="cutting-main-content" class="cutting-main-content d-none">
        {{-- RINGKASAN LOT TERPILIH + TOMBOL UBAH LOT --}}
        <div class="cutting-card cutting-selected-lot-strip mb-2">
            <div class="cutting-card-body">
                <div class="cutting-selected-lot-main">
                <div class="small">
                    <div>
                        <span class="cutting-selected-label">Item Kain</span>
                        <span class="cutting-selected-value" id="current-fabric-label">-</span>
                    </div>
                    <div class="mt-1">
                        <span class="text-muted">LOT:</span>
                        <span class="fw-semibold mono" id="current-lot-count">0 LOT</span>
                        <span class="text-muted ms-2">Stok Kain:</span>
                        <span class="fw-semibold mono" id="current-lot-balance">0.00</span>
                        <span class="text-muted">kg</span>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-pill-sm" id="btn-change-lots">
                    Ubah LOT
                </button>
                </div>
            </div>
        </div>

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
        <div class="cutting-card">
            <div class="cutting-card-header">
                <h5>Hasil Cutting</h5>
                <span class="badge-soft">
                    Item jadi & qty
                </span>
            </div>
            <div class="cutting-card-body">
                <div class="bundles-table-wrap mb-2">
                    <table class="bundles-table table table-sm" id="bundles-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="min-width: 120px;" class="bundle-lot-col">LOT</th>
                                <th style="min-width: 140px;">Item Jadi</th>
                                <th style="min-width: 90px;" class="text-end">Qty (pcs)</th>
                                <th style="min-width: 90px;" class="text-end">Kain (kg)</th>
                                <th style="min-width: 150px;" class="bundle-notes-header">Catatan</th>
                                <th style="width: 40px;"></th>
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
                            {{-- LOT dipilih per baris, wajib diisi --}}
                            <select class="form-select form-select-sm bundle-lot-select"
                                name="bundles[__INDEX__][lot_id]">
                                {{-- options diisi via JS berdasarkan LOT tercentang --}}
                            </select>
                        </td>
                        <td>
                            {{-- ITEM JADI pakai component item-suggest (idName wajib) --}}
                            <x-item-suggest idName="bundles[__INDEX__][finished_item_id]"
                                displayName="bundles[__INDEX__][finished_item_display]" placeholder="- Input Item Jadi -"
                                type="finished_good" :extraParams="['lot_id' => null]" />
                        </td>
                        <td>
                            <x-number-input name="bundles[__INDEX__][qty_pcs]" step="0.01" min="0"
                                inputmode="decimal" size="sm" align="end" class="bundle-qty-pcs bundle-qty" />
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
        @if (!app()->isProduction())
            <div class="mt-3">
                <label class="form-check d-inline-flex align-items-center gap-2 small text-muted mb-0">
                    <input type="checkbox" class="form-check-input mt-0" name="dev_rollback" value="1"
                        @checked(old('dev_rollback'))>
                    <span>Mode Developer: test rollback</span>
                </label>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mt-2 cutting-actions">
            <a href="{{ route('production.cutting_jobs.index') }}" class="btn btn-outline-secondary btn-sm">
                Batal
            </a>

            {{-- type="button" karena submit lewat modal --}}
            <button type="button" class="btn btn-primary btn-sm" id="btn-save-cutting">
                Pilih Operator &amp; Simpan
            </button>
        </div>
    </div> {{-- /#cutting-main-content --}}

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
                lotInfoMap[lotId] = {
                    lotId,
                    itemId,
                    code,
                    balance
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

            function showMainContent() {
                if (!mainContent) return;
                mainContent.classList.remove('d-none');

                if (isMobile() && pickLotSection) {
                    pickLotSection.classList.add('d-none');
                }
            }

            function showPickLotSection() {
                if (!pickLotSection) return;

                if (isMobile()) {
                    pickLotSection.classList.remove('d-none');
                    if (mainContent) mainContent.classList.add('d-none');
                    pickLotSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                } else {
                    pickLotSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }

            function lockLotSelection() {
                lotsLocked = true;
                document.body.classList.add('cutting-lots-locked');
            }

            function unlockLotSelection() {
                lotsLocked = false;
                document.body.classList.remove('cutting-lots-locked');
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

            // ⚙️ REBUILD LOT OPTIONS PER ROW, AUTO ROUND-ROBIN
            function rebuildLotOptionsForAllRows() {
                const checkedLotIds = getCheckedLots();
                const rows = Array.from(bundlesTbody.querySelectorAll('.bundle-row'));

                rows.forEach((tr, rowIndex) => {
                    const select = tr.querySelector('.bundle-lot-select');
                    if (!select) return;

                    const prevVal = select.value ? parseInt(select.value, 10) : null;

                    // clear options
                    while (select.firstChild) select.removeChild(select.firstChild);

                    const optPlaceholder = document.createElement('option');
                    optPlaceholder.value = '';
                    optPlaceholder.textContent = checkedLotIds.length ?
                        '- Pilih LOT -' :
                        'Tidak ada LOT terpilih';
                    select.appendChild(optPlaceholder);

                    checkedLotIds.forEach(lotId => {
                        const info = lotInfoMap[lotId];
                        if (!info) return;
                        const opt = document.createElement('option');
                        opt.value = lotId;
                        opt.textContent = info.code;
                        select.appendChild(opt);
                    });

                    if (prevVal && checkedLotIds.includes(prevVal)) {
                        // kalau sebelumnya sudah ada dan masih valid, pakai yang lama
                        select.value = String(prevVal);
                    } else if (checkedLotIds.length > 0) {
                        // AUTO: bagi rata LOT berdasarkan index baris
                        const chosenLotId = checkedLotIds[rowIndex % checkedLotIds.length];
                        select.value = String(chosenLotId);
                    } else {
                        select.value = '';
                    }

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
                                                debouncedSaveDraft();
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

                        // Disable tombol submit
                        const submitBtn = document.getElementById('btn-save-cutting');
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.title = 'Stok kain tidak cukup';
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

            // 🔒 Pastikan semua LOT terpilih punya item kain yang sama
            function enforceSingleFabricForCheckedLots(changedCb = null) {
                const infos = getCheckedLotsWithInfo();

                if (infos.length === 0) {
                    if (fabricSelect) {
                        fabricSelect.value = '';
                    }
                    updateCurrentLotSummary();
                    return true;
                }

                const firstItemId = infos[0].itemId || 0;
                if (!firstItemId) {
                    return true;
                }

                let hasConflict = false;
                infos.forEach(info => {
                    if ((info.itemId || 0) !== firstItemId) {
                        hasConflict = true;
                    }
                });

                if (hasConflict) {
                    if (changedCb) {
                        changedCb.checked = false;
                    } else {
                        lotCheckboxes.forEach(cb => {
                            const lotId = parseInt(cb.value, 10);
                            const info = lotInfoMap[lotId];
                            if (!info) return;
                            if ((info.itemId || 0) !== firstItemId) {
                                cb.checked = false;
                            }
                        });
                    }

                    alert('Semua LOT yang dipilih harus dari item kain yang sama.');
                    recalcLotBalanceFromCheckedLots();
                    recalcLotSummary();
                    updateCurrentLotSummary();
                    return false;
                }

                // Set fabric_item_id dari itemId LOT pertama
                if (fabricSelect) {
                    let foundOption = false;
                    Array.from(fabricSelect.options).forEach(opt => {
                        if (parseInt(opt.value || '0', 10) === firstItemId) {
                            fabricSelect.value = opt.value;
                            foundOption = true;
                        }
                    });

                    if (!foundOption) {
                        const opt = document.createElement('option');
                        opt.value = firstItemId;
                        opt.textContent = infos[0].code ? `${infos[0].code} (auto)` : `Item #${firstItemId}`;
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
                        recalcLotSummary();
                        debouncedSaveDraft();
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

                const lotSelect = tr.querySelector('.bundle-lot-select');
                if (lotSelect) {
                    lotSelect.addEventListener('change', () => {
                        recalcLotSummary();
                        updateRowItemSuggestExtraParams(tr);
                        debouncedSaveDraft();
                    });
                }

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

                // Auto-fill kain dari BOM saat item jadi dipilih (change event bubble dari item-suggest)
                const hiddenItemId = tr.querySelector('[name*="finished_item_id"]');
                if (hiddenItemId) {
                    hiddenItemId.addEventListener('change', () => {
                        autofillFabricQtyForRow(tr);
                        recalcLotSummary();
                        debouncedSaveDraft();
                    });
                }

                // Simpan draft saat notes berubah + Enter → baris berikutnya
                const notesInput = tr.querySelector('input[name*="[notes]"]');
                if (notesInput) {
                    notesInput.addEventListener('input', () => debouncedSaveDraft());
                    notesInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') { e.preventDefault(); goNextRow(); }
                    });
                }

                // Simpan draft saat qty fabric diisi manual
                const qtyFabricInp = tr.querySelector('.bundle-qty-fabric');
                if (qtyFabricInp) {
                    qtyFabricInp.addEventListener('input', () => debouncedSaveDraft());
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

                if (autoFocusItem && itemInput) {
                    setTimeout(() => {
                        itemInput.focus();
                        itemInput.click();
                        scrollRowIntoCenter(tr);
                    }, 50);
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
                    debouncedSaveDraft();
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
                debouncedSaveDraft();
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
                debouncedSaveDraft();
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
                debouncedSaveDraft();
            });

            btnChangeLots?.addEventListener('click', () => {
                showPickLotSection();
                unlockLotSelection();
                debouncedSaveDraft();
            });

            btnAddRow?.addEventListener('click', () => {
                createBundleRow(true);
                debouncedSaveDraft();
            });

            // ==============================
            // DRAFT PERSISTENCE (localStorage)
            // ==============================
            const DRAFT_KEY = 'gfid_cutting_draft_v1';
            let saveDraftTimer = null;

            function debouncedSaveDraft() {
                clearTimeout(saveDraftTimer);
                saveDraftTimer = setTimeout(saveDraft, 400);
            }

            function saveDraft() {
                const checkedLotIds = getCheckedLots();
                const bundles = [];
                bundlesTbody.querySelectorAll('.bundle-row').forEach(tr => {
                    const lotSel       = tr.querySelector('.bundle-lot-select');
                    const itemIdInp    = tr.querySelector('[name*="finished_item_id"]');
                    const itemTextInp  = tr.querySelector('td:nth-child(3) input[type="text"]');
                    const qtyPcsInp    = tr.querySelector('.bundle-qty-pcs');
                    const qtyFabricInp = tr.querySelector('.bundle-qty-fabric');
                    const notesInp     = tr.querySelector('input[name*="[notes]"]');
                    bundles.push({
                        lotId:            lotSel?.value       || '',
                        finishedItemId:   itemIdInp?.value    || '',
                        finishedItemText: itemTextInp?.value  || '',
                        qtyPcs:           qtyPcsInp?.value    || '',
                        qtyFabric:        qtyFabricInp?.value || '',
                        notes:            notesInp?.value     || '',
                    });
                });
                try {
                    localStorage.setItem(DRAFT_KEY, JSON.stringify({
                        checkedLotIds,
                        wasConfirmed: lotsLocked,
                        bundles,
                    }));
                } catch (e) {}
            }

            function clearDraft() {
                localStorage.removeItem(DRAFT_KEY);
            }

            function restoreDraft() {
                let draft;
                try {
                    const raw = localStorage.getItem(DRAFT_KEY);
                    if (!raw) return;
                    draft = JSON.parse(raw);
                } catch (e) { return; }

                if (!draft || !Array.isArray(draft.checkedLotIds) || draft.checkedLotIds.length === 0) return;

                // Centang kembali lot yang sebelumnya dipilih
                draft.checkedLotIds.forEach(id => {
                    const cb = document.querySelector(`.lot-checkbox[value="${id}"]`);
                    if (cb) cb.checked = true;
                });

                recalcLotBalanceFromCheckedLots();
                const ok = enforceSingleFabricForCheckedLots();
                if (!ok) { clearDraft(); return; }
                updateCurrentLotSummary();

                if (!draft.wasConfirmed || !Array.isArray(draft.bundles) || draft.bundles.length === 0) return;

                lockLotSelection();
                rebuildLotOptionsForAllRows();
                showMainContent();

                // Hapus baris default yang dibuat di INIT, reset counter
                bundlesTbody.innerHTML = '';
                bundleIndexCounter = 0;

                draft.bundles.forEach(b => {
                    createBundleRow(false);
                    const tr = bundlesTbody.lastElementChild;
                    if (!tr) return;

                    // LOT
                    const lotSel = tr.querySelector('.bundle-lot-select');
                    if (lotSel && b.lotId) lotSel.value = String(b.lotId);

                    // Item jadi — hidden ID
                    const itemIdInp = tr.querySelector('[name*="finished_item_id"]');
                    if (itemIdInp && b.finishedItemId) itemIdInp.value = String(b.finishedItemId);

                    // Item jadi — teks tampilan (visible input)
                    const itemTextInp = tr.querySelector('td:nth-child(3) input[type="text"]');
                    if (itemTextInp && b.finishedItemText) itemTextInp.value = b.finishedItemText;

                    // Qty pcs
                    const qtyPcsInp = tr.querySelector('.bundle-qty-pcs');
                    if (qtyPcsInp) qtyPcsInp.value = b.qtyPcs || '';

                    // Qty fabric (pakai nilai tersimpan, sudah termasuk BOM calculation)
                    const qtyFabricInp = tr.querySelector('.bundle-qty-fabric');
                    if (qtyFabricInp) qtyFabricInp.value = b.qtyFabric || '';

                    // Catatan
                    const notesInp = tr.querySelector('input[name*="[notes]"]');
                    if (notesInp) notesInp.value = b.notes || '';
                });

                updateBundleRowIndices();
                recalcLotSummary();
            }

            // Hapus draft saat form berhasil di-submit
            document.getElementById('cutting-form')?.addEventListener('submit', () => clearDraft());

            // INIT
            recalcLotBalanceFromCheckedLots();
            updateCurrentLotSummary();
            createBundleRow(false);
            restoreDraft(); // pulihkan draft jika ada
        });
    </script>
@endpush
