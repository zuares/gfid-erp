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
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: var(--card);
            margin-bottom: .75rem;
        }

        .cutting-card-header {
            padding: .55rem .75rem .45rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.35);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
        }

        .cutting-card-header h5 {
            font-size: .9rem;
            margin: 0;
        }

        .cutting-card-body {
            padding: .6rem .75rem .6rem;
            overflow: visible;
            position: relative;
        }

        .badge-soft {
            font-size: .7rem;
            border-radius: 999px;
            padding: .08rem .5rem;
            border: 1px solid rgba(148, 163, 184, 0.6);
            background: rgba(148, 163, 184, 0.14);
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

        @media (max-width: 767.98px) {
            .cutting-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .cutting-actions .btn-primary {
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
        <div class="cutting-card mb-2">
            <div class="cutting-card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small">
                    <div>
                        <span class="text-muted">Item kain:</span>
                        <span class="fw-semibold" id="current-fabric-label">-</span>
                    </div>
                    <div>
                        <span class="text-muted">LOT terpilih:</span>
                        <span class="fw-semibold" id="current-lot-count">0 LOT</span>
                        <span class="text-muted ms-2">Total kain:</span>
                        <span class="fw-semibold mono" id="current-lot-balance">0.00</span>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-pill-sm" id="btn-change-lots">
                    Ubah LOT
                </button>
            </div>
        </div>

        {{-- BARIS KONTROL ATAS: tombol buka modal info job --}}
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="small text-muted">
                Step 2: Atur info cutting job & isi bundles.
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm btn-pill-sm" data-bs-toggle="modal"
                data-bs-target="#cuttingInfoModal">
                Info Cutting Job
            </button>
        </div>

        {{-- CARD: BUNDLES --}}
        <div class="cutting-card">
            <div class="cutting-card-header">
                <h5>Bundles</h5>
                <span class="badge-soft">
                    Step 2: Input baris bundles (pilih LOT & item jadi per baris)
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
                                <th style="min-width: 150px;" class="bundle-notes-header">Catatan</th>
                                <th style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="bundle-rows">
                            {{-- Baris awal digenerate via JS --}}
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-pill-sm"
                                        id="btn-add-row">
                                        + Tambah Baris
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
                                placeholder="- Input Item Jadi -" type="finished_good" :extraParams="['lot_id' => null]" />
                        </td>
                        <td>
                            <x-number-input name="bundles[__INDEX__][qty_pcs]" step="0.01" min="0"
                                inputmode="decimal" size="sm" align="end" class="bundle-qty-pcs bundle-qty" />
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
        <div class="d-flex justify-content-between align-items-center mt-3 cutting-actions">
            {{-- type="button" karena submit lewat modal --}}
            <button type="button" class="btn btn-primary btn-sm" id="btn-save-cutting">
                Pilih Operator
            </button>

            <a href="{{ route('production.cutting_jobs.index') }}" class="btn btn-outline-secondary btn-sm">
                Batal
            </a>
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

                while (lotSummaryList.firstChild) lotSummaryList.removeChild(lotSummaryList.firstChild);

                if (totalBalance <= 0 && totalPcs <= 0) {
                    const li = document.createElement('li');
                    li.classList.add('text-muted');
                    li.textContent = 'Belum ada pemilihan LOT atau qty bundle.';
                    lotSummaryList.appendChild(li);
                    return;
                }

                const li1 = document.createElement('li');
                li1.innerHTML =
                    `<span>Total kain tersedia (dari LOT):</span><span class="mono">${totalBalance.toFixed(2)}</span>`;
                lotSummaryList.appendChild(li1);

                const li2 = document.createElement('li');
                li2.innerHTML =
                    `<span>Total qty pcs bundles:</span><span class="mono">${totalPcs.toFixed(2)}</span>`;
                lotSummaryList.appendChild(li2);

                if (validRowCount > 0) {
                    const perRow = totalBalance / validRowCount;
                    const li3 = document.createElement('li');
                    li3.innerHTML =
                        `<span>Estimasi kain per baris (bagi rata):</span><span class="mono">${perRow.toFixed(2)}</span>`;
                    lotSummaryList.appendChild(li3);
                }

                if (totalPcs > 0 && totalBalance > 0) {
                    const perPcs = totalBalance / totalPcs;
                    const li4 = document.createElement('li');
                    li4.innerHTML =
                        `<span>Estimasi kain per pcs:</span><span class="mono">${perPcs.toFixed(4)}</span>`;
                    lotSummaryList.appendChild(li4);
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

                const qtyInput = tr.querySelector('.bundle-qty-pcs');
                if (qtyInput) {
                    qtyInput.addEventListener('input', recalcLotSummary);
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

                const itemCell = tr.querySelector('td:nth-child(3)');
                const itemInput = itemCell ? itemCell.querySelector('input[type="text"]') : null;
                if (itemInput) {
                    const handleItemFocus = () => scrollRowIntoCenter(tr);

                    itemInput.addEventListener('focus', handleItemFocus);
                    itemInput.addEventListener('click', handleItemFocus);
                    itemInput.addEventListener('input', handleItemFocus);
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

            btnAddRow?.addEventListener('click', () => {
                createBundleRow(true);
            });

            // INIT
            recalcLotBalanceFromCheckedLots();
            updateCurrentLotSummary();
            createBundleRow(false);
        });
    </script>
@endpush
