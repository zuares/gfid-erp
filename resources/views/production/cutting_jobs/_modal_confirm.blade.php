{{-- resources/views/production/cutting_jobs/_modal_confirm.blade.php --}}

{{-- MODAL: INFO + KONFIRMASI CUTTING JOB --}}
<div class="modal fade" id="cuttingInfoModal" tabindex="-1" aria-labelledby="cuttingInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="cuttingInfoModalLabel">Konfirmasi Cutting Job</h5>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                {{-- BLOK UTAMA: OPERATOR (FOCAL POINT) --}}
                <div class="operator-focus-card mb-3">
                    <div class="small text-uppercase fw-semibold text-primary mb-2">
                        Operator Cutting
                    </div>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small mb-1">Pilih Operator <span
                                    class="text-danger">*</span></label>
                            <select name="operator_id" class="form-select form-select-sm" id="modal-operator-id">
                                <option value="">- Pilih Operator -</option>
                                @foreach ($operators as $op)
                                    <option value="{{ $op->id }}" @selected($selectedOperatorId == $op->id)>
                                        {{ $op->code }} - {{ $op->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- DETAIL BUNDLES (FOCAL POINT DI MOBILE) --}}
                <div class="mb-3 cutting-modal-section cutting-modal-section-bundles">
                    <div class="small fw-semibold mb-1">Detail Bundles</div>
                    <div class="table-responsive mb-2">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Item (kode)</th>
                                    <th class="text-end">Qty (pcs)</th>
                                </tr>
                            </thead>
                            <tbody id="summary-bundle-rows">
                                <tr>
                                    <td colspan="3" class="text-muted small">
                                        Belum ada qty bundle yang diisi.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- RINGKASAN JUMLAH PER ITEM --}}
                    <div class="small fw-semibold mb-1">Ringkasan per Item</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Item (kode)</th>
                                    <th class="text-end">Total Qty (pcs)</th>
                                </tr>
                            </thead>
                            <tbody id="summary-bundle-agg-rows">
                                <tr>
                                    <td colspan="2" class="text-muted small">
                                        Belum ada data.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- RINGKASAN TOTAL --}}
                <div
                    class="mb-3 p-2 rounded border bg-light small cutting-modal-summary cutting-modal-section cutting-modal-section-summary">
                    <div class="d-flex justify-content-between">
                        <span>Item Kain</span>
                        <span class="fw-semibold" id="summary-fabric">-</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Jumlah LOT terpilih</span>
                        <span class="fw-semibold" id="summary-lot-count">0</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Total kain tersedia (dari LOT)</span>
                        <span class="fw-semibold mono" id="summary-lot-balance">0.00</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Total qty bundles (pcs)</span>
                        <span class="fw-semibold mono" id="summary-bundle-pcs">0.00</span>
                    </div>
                </div>

                {{-- FORM INFO JOB LAINNYA --}}
                <div class="row g-3 cutting-modal-section cutting-modal-section-meta">
                    <div class="col-md-4 col-6">
                        <label class="form-label small">Tanggal</label>
                        <input type="date" name="date" class="form-control form-control-sm"
                            value="{{ old('date', now()->toDateString()) }}">
                    </div>
                    <div class="col-md-4 col-6">
                        <label class="form-label small">Warehouse</label>
                        {{-- Disabled select untuk tampilan, hidden input untuk kirim ke backend --}}
                        <select class="form-select form-select-sm" name="warehouse_id_display" disabled>
                            @if ($defaultWarehouse)
                                <option value="{{ $selectedWarehouseId }}">
                                    {{ $defaultWarehouse->code }} - {{ $defaultWarehouse->name }}
                                </option>
                            @else
                                <option value="">- Tidak ada warehouse -</option>
                            @endif
                        </select>
                        <input type="hidden" name="warehouse_id" value="{{ $selectedWarehouseId }}">
                    </div>
                    <div class="col-12 cutting-notes-modal-field">
                        <label class="form-label small">Catatan</label>
                        <input type="text" name="notes" class="form-control form-control-sm"
                            value="{{ old('notes') }}">
                    </div>
                </div>
            </div>

            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="btn-modal-save-cutting">
                    Simpan
                </button>
            </div>
        </div>
    </div>
</div>

@push('head')
    <style>
        .cutting-modal-summary .mono {
            font-variant-numeric: tabular-nums;
        }

        .operator-focus-card {
            border-radius: 12px;
            border: 1px solid rgba(59, 130, 246, 0.35);
            background: linear-gradient(to right,
                    rgba(59, 130, 246, 0.08),
                    rgba(59, 130, 246, 0.02));
            padding: .75rem .9rem;
        }

        body[data-theme="dark"] .operator-focus-card {
            background: linear-gradient(to right,
                    rgba(37, 99, 235, 0.35),
                    rgba(15, 23, 42, 0.9));
            border-color: rgba(96, 165, 250, 0.7);
        }

        /* ============================================================
               MOBILE MODAL IMPROVEMENTS — CLEAN VERSION
               ============================================================ */
        @media (max-width: 767.98px) {

            /* Modal tidak full lebar + dinaikkan sedikit dari bawah */
            #cuttingInfoModal .modal-dialog {
                max-width: 92%;
                margin: 1rem auto 2.25rem;
                /* bottom margin agak besar supaya footer naik */
            }

            /* Modal lebih pendek, tidak mepet atas, tidak overflow X */
            #cuttingInfoModal .modal-content {
                border-radius: 16px;
                max-height: 78vh;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }

            /* Body hanya boleh scroll vertical, fleksibel urutan section */
            #cuttingInfoModal .modal-body {
                padding: .75rem .9rem;
                overflow-y: auto;
                overflow-x: hidden;
                display: flex;
                flex-direction: column;
                gap: .75rem;
            }

            #cuttingInfoModal .modal-body>* {
                max-width: 100%;
            }

            #cuttingInfoModal .table-responsive {
                overflow-x: hidden;
            }

            #cuttingInfoModal table {
                width: 100%;
                max-width: 100%;
            }

            /* Urutan section di mobile */
            .operator-focus-card {
                order: 1;
            }

            .cutting-modal-section-bundles {
                order: 2;
                border-radius: 12px;
                border: 1px solid rgba(148, 163, 184, 0.4);
                background: var(--card, #fff);
                padding: .5rem .6rem;
            }

            .cutting-modal-section-summary {
                order: 3;
            }

            .cutting-modal-section-meta {
                order: 4;
            }

            /* Sembunyikan field Catatan di mobile */
            .cutting-notes-modal-field {
                display: none;
            }

            /* Footer dinaikkan sedikit + aman dari bottom bar */
            #cuttingInfoModal .modal-footer {
                padding-top: .4rem;
                padding-right: .9rem;
                padding-left: .9rem;
                padding-bottom: 1.1rem;
                /* agak besar supaya tidak ketutup UI browser */
            }

            @supports (padding-bottom: env(safe-area-inset-bottom)) {
                #cuttingInfoModal .modal-footer {
                    padding-bottom: calc(1.1rem + env(safe-area-inset-bottom));
                }
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('cutting-form');
            const mainContent = document.getElementById('cutting-main-content');

            const btnSaveCutting = document.getElementById('btn-save-cutting');
            const modalEl = document.getElementById('cuttingInfoModal');
            const btnModalSaveCutting = document.getElementById('btn-modal-save-cutting');

            const summaryFabric = document.getElementById('summary-fabric');
            const summaryLotCount = document.getElementById('summary-lot-count');
            const summaryLotBalance = document.getElementById('summary-lot-balance');
            const summaryBundlePcs = document.getElementById('summary-bundle-pcs');
            const summaryBundleRows = document.getElementById('summary-bundle-rows');
            const summaryBundleAggRows = document.getElementById('summary-bundle-agg-rows');

            const fabricSelect = document.getElementById('fabric_item_id');
            const lotBalanceInput = document.getElementById('lot_balance');
            const bundlesTbody = document.getElementById('bundle-rows');
            const lotCheckboxes = Array.from(document.querySelectorAll('.lot-checkbox'));
            const operatorSelect = document.getElementById('modal-operator-id');

            let cuttingInfoModalInstance = null;
            if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                cuttingInfoModalInstance = new bootstrap.Modal(modalEl);

                modalEl.addEventListener('shown.bs.modal', () => {
                    operatorSelect?.focus();
                });
            }

            function getCheckedLotsForModal() {
                const ids = [];
                lotCheckboxes.forEach(cb => {
                    if (cb.checked) {
                        ids.push(parseInt(cb.value, 10));
                    }
                });
                return ids;
            }

            function recalcTotalPcsForModal() {
                const rows = bundlesTbody.querySelectorAll('.bundle-row');
                let totalPcs = 0;
                rows.forEach(tr => {
                    const qtyInput = tr.querySelector('.bundle-qty-pcs');
                    if (!qtyInput) return;
                    const qty = parseFloat(qtyInput.value || '0');
                    if (qty > 0) totalPcs += qty;
                });
                return totalPcs;
            }

            function collectBundleDetails() {
                const rows = bundlesTbody.querySelectorAll('.bundle-row');
                const details = [];

                rows.forEach((tr, idx) => {
                    const qtyInput = tr.querySelector('.bundle-qty-pcs');
                    if (!qtyInput) return;

                    const qty = parseFloat(qtyInput.value || '0');
                    if (qty <= 0) return;

                    const itemCell = tr.querySelector('td:nth-child(3)');
                    let label = '(belum pilih item)';

                    if (itemCell) {
                        const textInput = itemCell.querySelector('input[type="text"]');
                        if (textInput && textInput.value.trim() !== '') {
                            const display = textInput.value.trim();

                            // Ambil hanya kode barang (sebelum "—" atau "-")
                            let codeOnly = display.split('—')[0];
                            codeOnly = codeOnly.split('-')[0];
                            codeOnly = codeOnly.trim();

                            label = codeOnly || display;
                        }
                    }

                    details.push({
                        index: idx + 1,
                        label,
                        qty
                    });
                });

                return details;
            }

            function updateModalSummary() {
                if (!summaryFabric) return;

                const fabricText =
                    fabricSelect?.options?.[fabricSelect.selectedIndex]?.text?.trim() || '-';
                const lotCount = getCheckedLotsForModal().length;
                const balance = parseFloat(lotBalanceInput.value || '0');
                const totalPcs = recalcTotalPcsForModal();

                summaryFabric.textContent = fabricText;
                summaryLotCount.textContent = String(lotCount);
                summaryLotBalance.textContent = balance.toFixed(2);
                summaryBundlePcs.textContent = totalPcs.toFixed(2);

                const details = collectBundleDetails();

                // Isi tabel detail per baris
                if (summaryBundleRows) {
                    while (summaryBundleRows.firstChild) {
                        summaryBundleRows.removeChild(summaryBundleRows.firstChild);
                    }

                    if (details.length === 0) {
                        const tr = document.createElement('tr');
                        const td = document.createElement('td');
                        td.colSpan = 3;
                        td.className = 'text-muted small';
                        td.textContent = 'Belum ada qty bundle yang diisi.';
                        tr.appendChild(td);
                        summaryBundleRows.appendChild(tr);
                    } else {
                        details.forEach(d => {
                            const tr = document.createElement('tr');

                            const tdIndex = document.createElement('td');
                            tdIndex.textContent = d.index;
                            tr.appendChild(tdIndex);

                            const tdLabel = document.createElement('td');
                            tdLabel.textContent = d.label;
                            tr.appendChild(tdLabel);

                            const tdQty = document.createElement('td');
                            tdQty.className = 'text-end mono';
                            tdQty.textContent = d.qty.toFixed(2);
                            tr.appendChild(tdQty);

                            summaryBundleRows.appendChild(tr);
                        });
                    }
                }

                // Isi tabel ringkasan per item (grouping)
                if (summaryBundleAggRows) {
                    while (summaryBundleAggRows.firstChild) {
                        summaryBundleAggRows.removeChild(summaryBundleAggRows.firstChild);
                    }

                    if (details.length === 0) {
                        const tr = document.createElement('tr');
                        const td = document.createElement('td');
                        td.colSpan = 2;
                        td.className = 'text-muted small';
                        td.textContent = 'Belum ada data.';
                        tr.appendChild(td);
                        summaryBundleAggRows.appendChild(tr);
                    } else {
                        const aggMap = {};
                        details.forEach(d => {
                            if (!aggMap[d.label]) {
                                aggMap[d.label] = 0;
                            }
                            aggMap[d.label] += d.qty;
                        });

                        Object.keys(aggMap).sort().forEach(label => {
                            const tr = document.createElement('tr');

                            const tdLabel = document.createElement('td');
                            tdLabel.textContent = label;
                            tr.appendChild(tdLabel);

                            const tdQty = document.createElement('td');
                            tdQty.className = 'text-end mono';
                            tdQty.textContent = aggMap[label].toFixed(2);
                            tr.appendChild(tdQty);

                            summaryBundleAggRows.appendChild(tr);
                        });
                    }
                }
            }

            // Klik tombol SIMPAN → buka modal + isi ringkasan
            btnSaveCutting?.addEventListener('click', (e) => {
                e.preventDefault();

                // Kalau mainContent masih hidden, berarti LOT belum dikonfirmasi
                if (mainContent && mainContent.classList.contains('d-none')) {
                    alert('Selesaikan pemilihan kain & LOT terlebih dahulu.');
                    return;
                }

                updateModalSummary();

                if (cuttingInfoModalInstance) {
                    cuttingInfoModalInstance.show();
                } else if (modalEl) {
                    modalEl.classList.add('show');
                    modalEl.style.display = 'block';
                    operatorSelect?.focus();
                }
            });

            // Tombol di modal yang benar-benar submit form
            btnModalSaveCutting?.addEventListener('click', () => {
                if (!operatorSelect || !operatorSelect.value) {
                    alert('Operator Cutting wajib dipilih sebelum menyimpan.');
                    operatorSelect?.focus();
                    return;
                }

                form?.submit();
            });
        });
    </script>
@endpush
