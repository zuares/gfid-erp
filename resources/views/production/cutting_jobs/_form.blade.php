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

<form action="{{ route('production.cutting_jobs.store') }}" method="POST" id="cutting-form">
    @csrf

    {{-- dipakai untuk auto hitung kain pakai di backend --}}
    <input type="hidden" name="lot_balance" id="lot_balance" value="{{ old('lot_balance', 0) }}">

    {{-- CARD: PILIH KAIN & LOT (selalu tampil duluan) --}}
    @include('production.cutting_jobs._pick_lot')

    {{-- KONTEN UTAMA: baru muncul setelah LOT disimpan --}}
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
                    Step 2: Input baris bundles (LOT & kain pakai auto)
                </span>
            </div>
            <div class="cutting-card-body">
                <div class="bundles-table-wrap mb-2">
                    <table class="bundles-table table table-sm" id="bundles-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="min-width: 120px;" class="bundle-lot-col">LOT</th>
                                <th style="min-width: 150px;">Item Jadi</th>
                                <th style="min-width: 90px;" class="text-end">Qty (pcs)</th>
                                <th style="min-width: 150px;" class="bundle-notes-header">Catatan</th>
                                <th style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="bundle-rows">
                            {{-- Baris awal (1 row default) akan di-generate oleh JS --}}
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
                            {{-- LOT di-auto oleh JS, user tidak perlu pilih manual --}}
                            <select class="form-select form-select-sm bundle-lot-select"
                                name="bundles[__INDEX__][lot_id]">
                                {{-- options diisi via JS berdasarkan LOT tercentang --}}
                            </select>
                        </td>
                        <td>
                            {{-- ITEM JADI pakai component item-suggest (idName wajib) --}}
                            <x-item-suggest idName="bundles[__INDEX__][finished_item_id]"
                                placeholder="- Pilih Item Jadi -" type="finished_good" />
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
            const fabricSelect = document.getElementById('fabric_item_id');
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

            // helper breakpoint: mobile only
            function isMobile() {
                return window.matchMedia('(max-width: 767.98px)').matches;
            }

            // Build map lotId -> info
            const lotInfoMap = {};
            lotRows.forEach(tr => {
                const lotId = parseInt(tr.dataset.lotId, 10);
                const itemId = parseInt(tr.dataset.itemId, 10);
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
                    if (cb.checked) {
                        ids.push(parseInt(cb.value, 10));
                    }
                });
                return ids;
            }

            function showMainContent() {
                if (!mainContent) return;
                mainContent.classList.remove('d-none');

                // HANYA di mobile: sembunyikan pick LOT
                if (isMobile() && pickLotSection) {
                    pickLotSection.classList.add('d-none');
                }
            }

            function showPickLotSection() {
                if (!pickLotSection) return;

                // HANYA di mobile: balik ke step pilih LOT
                if (isMobile()) {
                    pickLotSection.classList.remove('d-none');
                    if (mainContent) {
                        mainContent.classList.add('d-none');
                    }
                    pickLotSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                } else {
                    // di desktop: cukup scroll ke area LOT, jangan hide form
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

            function filterLotsByFabric() {
                const selectedItemId = parseInt(fabricSelect?.value || '0', 10);

                lotRows.forEach(tr => {
                    const itemId = parseInt(tr.dataset.itemId, 10);
                    if (!selectedItemId || itemId === selectedItemId) {
                        tr.classList.remove('lot-hidden');
                    } else {
                        tr.classList.add('lot-hidden');
                    }
                });
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

            function rebuildLotOptionsForAllRows() {
                const checkedLotIds = getCheckedLots();
                const selects = bundlesTbody.querySelectorAll('.bundle-lot-select');

                selects.forEach(select => {
                    const currentVal = parseInt(select.value || '0', 10);
                    while (select.firstChild) {
                        select.removeChild(select.firstChild);
                    }

                    const optPlaceholder = document.createElement('option');
                    optPlaceholder.value = '';
                    optPlaceholder.textContent = checkedLotIds.length ? '- LOT auto -' :
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

                    if (checkedLotIds.length > 0 && !currentVal) {
                        select.value = checkedLotIds[0];
                    } else if (currentVal && checkedLotIds.includes(currentVal)) {
                        select.value = currentVal;
                    }
                });
            }

            function updateBundleRowIndices() {
                const rows = bundlesTbody.querySelectorAll('.bundle-row');
                rows.forEach((tr, idx) => {
                    const numCell = tr.querySelector('.bundle-index');
                    if (numCell) {
                        numCell.textContent = idx + 1;
                    }
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
                        if (!itemSummary[label]) {
                            itemSummary[label] = 0;
                        }
                        itemSummary[label] += qty;
                    }
                });

                const totalBalance = parseFloat(lotBalanceInput.value || '0');

                while (lotSummaryList.firstChild) {
                    lotSummaryList.removeChild(lotSummaryList.firstChild);
                }

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

                // Ringkasan per item jadi (hanya muncul kalau ada data)
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
                const fabricText =
                    fabricSelect?.options?.[fabricSelect.selectedIndex]?.text?.trim() || '-';
                const lotCount = getCheckedLots().length;
                const balance = parseFloat(lotBalanceInput.value || '0');

                if (currentFabricLabel) {
                    currentFabricLabel.textContent = fabricText;
                }
                if (currentLotCount) {
                    currentLotCount.textContent = `${lotCount} LOT`;
                }
                if (currentLotBalance) {
                    currentLotBalance.textContent = balance.toFixed(2);
                }
            }

            function createBundleRow(autoFocusItem = false) {
                const frag = bundleTemplate.content.cloneNode(true);
                const tr = frag.querySelector('tr');
                const idx = bundleIndexCounter++;

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
                    lotSelect.addEventListener('change', recalcLotSummary);
                }

                const btnRemove = tr.querySelector('.btn-remove-row');
                if (btnRemove) {
                    btnRemove.addEventListener('click', () => {
                        tr.remove();
                        updateBundleRowIndices();
                        recalcLotSummary();
                    });
                }

                bundlesTbody.appendChild(tr);

                if (window.initItemSuggestInputs) {
                    window.initItemSuggestInputs(tr);
                }

                updateBundleRowIndices();
                rebuildLotOptionsForAllRows();
                recalcLotSummary();

                if (autoFocusItem) {
                    const itemCell = tr.querySelector('td:nth-child(3)');
                    const itemInput = itemCell ? itemCell.querySelector('input[type="text"]') : null;
                    if (itemInput) {
                        setTimeout(() => {
                            itemInput.focus();
                            itemInput.click();
                        }, 50);
                    }
                }
            }

            // EVENTS
            fabricSelect?.addEventListener('change', () => {
                if (lotsLocked) return;
                filterLotsByFabric();
            });

            lotCheckboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    if (lotsLocked) return;
                    recalcLotBalanceFromCheckedLots();
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
                recalcLotBalanceFromCheckedLots();
                recalcLotSummary();
            });

            btnUnselectAllLots?.addEventListener('click', () => {
                if (lotsLocked) return;
                lotCheckboxes.forEach(cb => cb.checked = false);
                recalcLotBalanceFromCheckedLots();
                recalcLotSummary();
            });

            btnConfirmLots?.addEventListener('click', () => {
                if (lotsLocked) {
                    showMainContent();
                    return;
                }

                if (!fabricSelect || !fabricSelect.value) {
                    alert('Item kain wajib dipilih terlebih dahulu.');
                    fabricSelect?.focus();
                    return;
                }

                const checked = getCheckedLots();
                if (checked.length === 0) {
                    alert('Pilih minimal satu LOT terlebih dahulu.');
                    return;
                }

                recalcLotBalanceFromCheckedLots();
                rebuildLotOptionsForAllRows();
                recalcLotSummary();
                lockLotSelection();
                updateCurrentLotSummary();
                showMainContent();
            });

            // Tombol "Ubah LOT" di form utama
            btnChangeLots?.addEventListener('click', () => {
                showPickLotSection();
                unlockLotSelection();
            });

            btnAddRow?.addEventListener('click', () => {
                createBundleRow(true);
            });

            // INIT
            filterLotsByFabric();
            recalcLotBalanceFromCheckedLots();
            createBundleRow(false);
        });
    </script>
@endpush
