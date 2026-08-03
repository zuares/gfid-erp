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
                                        {{ $op->code }} - {{ $op->name }}@if ($op->role === 'operating') (operating)@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- RINGKASAN JUMLAH PER ITEM (DIBUAT DI ATAS, SETELAH OPERATOR) --}}
                <div class="mb-3 cutting-modal-section cutting-modal-section-agg">
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

                {{-- DETAIL BUNDLES --}}
                <div class="mb-3 cutting-modal-section cutting-modal-section-bundles">
                    <div class="small fw-semibold mb-1">Detail Bundles</div>
                    <div class="table-responsive mb-0">
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
                        @php
                            $defaultDate = isset($isEdit) && $isEdit && isset($job) ? $job->date : now()->toDateString();
                        @endphp
                        <input type="date" name="date" class="form-control form-control-sm"
                            value="{{ old('date', $defaultDate) }}">
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
                        @php
                            $defaultNotes = isset($isEdit) && $isEdit && isset($job) ? $job->notes : '';
                        @endphp
                        <input type="text" name="notes" class="form-control form-control-sm"
                            value="{{ old('notes', $defaultNotes) }}">
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
            border-radius: 16px;
            border: 1px solid rgba(59, 130, 246, 0.2);
            background: linear-gradient(135deg, rgba(239, 246, 255, 1) 0%, rgba(219, 234, 254, 0.4) 100%);
            padding: 1rem;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.08);
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

            /* Modal dijauhkan dari area bawah agar tidak tertutup bottom nav */
            #cuttingInfoModal .modal-dialog {
                max-width: 96%;
                /* Top margin kecil, margin bawah dilindungi dari bottom nav (sekitar 6rem) */
                margin: 1.5rem auto calc(6.5rem + env(safe-area-inset-bottom, 0px));
            }

            /* UI Modal lebih modern, tidak terlalu tinggi */
            #cuttingInfoModal .modal-content {
                border-radius: 20px;
                max-height: 65vh; /* Jangan terlalu tinggi, sisakan ruang */
                display: flex;
                flex-direction: column;
                overflow: hidden;
                border: none;
                box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            }
            
            #cuttingInfoModal .modal-header {
                border-bottom: none;
                padding: 1.25rem 1.25rem 0.5rem;
            }
            
            #cuttingInfoModal .modal-title {
                font-weight: 800;
                font-size: 1.25rem;
            }

            /* Body hanya boleh scroll vertical, fleksibel urutan section */
            #cuttingInfoModal .modal-body {
                padding: 0.5rem 1.25rem 1.25rem;
                overflow-y: auto;
                overflow-x: hidden;
                display: flex;
                flex-direction: column;
                gap: 1rem;
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

            .cutting-modal-section-agg {
                order: 2;
                border-radius: 14px;
                border: none;
                background: #f8fafc;
                padding: .85rem;
            }

            .cutting-modal-section-bundles {
                order: 3;
                border-radius: 14px;
                border: none;
                background: #f8fafc;
                padding: .85rem;
            }

            .cutting-modal-section-summary {
                order: 4;
                border-radius: 14px;
                border: none !important;
                background: #eff6ff !important;
                color: #1e3a8a;
                padding: 1rem !important;
            }
            .cutting-modal-section-summary .fw-semibold {
                color: #1d4ed8;
            }

            .cutting-modal-section-meta {
                order: 5;
            }

            /* Sembunyikan field Catatan di mobile */
            .cutting-notes-modal-field {
                display: none;
            }

            /* Footer dengan tombol besar (modern) */
            #cuttingInfoModal .modal-footer {
                padding: 1rem 1.25rem calc(1.25rem + env(safe-area-inset-bottom));
                border-top: 1px solid rgba(148, 163, 184, 0.15);
                display: flex;
                flex-wrap: nowrap;
                gap: 0.5rem;
            }
            
            #cuttingInfoModal .modal-footer .btn {
                flex: 1;
                border-radius: 14px;
                padding: 0.75rem;
                font-weight: 700;
                font-size: 1rem;
            }
            
            #cuttingInfoModal .modal-footer .btn-light {
                flex: 0 0 30%;
                background: #f1f5f9;
                border: none;
                color: #475569;
            }
            
            #cuttingInfoModal .modal-footer .btn-primary {
                background: linear-gradient(135deg, #3b82f6, #2563eb);
                border: none;
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

                            // Ambil kode lengkap. Tanda minus adalah bagian dari
                            // kode item (contoh: CFP-BLK-XXL), jadi jangan dipotong.
                            // Format code-name desktop dipisahkan dengan em dash.
                            const displayParts = display.split('—');
                            let codeOnly = displayParts.length > 1
                                ? displayParts[displayParts.length - 1].trim()
                                : display.trim();

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
                summaryLotBalance.textContent = balance.toFixed(2) + ' kg';
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

                if (window.cuttingValidateBundleItems && !window.cuttingValidateBundleItems(true)) {
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
            btnModalSaveCutting?.addEventListener('click', function () {
                if (form.dataset.submitted === 'true') return;

                if (window.cuttingValidateBundleItems && !window.cuttingValidateBundleItems(true)) {
                    cuttingInfoModalInstance?.hide?.();
                    return;
                }

                if (!operatorSelect || !operatorSelect.value) {
                    alert('Operator Cutting wajib dipilih sebelum menyimpan.');
                    operatorSelect?.focus();
                    return;
                }

                form.dataset.submitted = 'true';
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menyimpan...';

                form?.submit();
            });
        });
    </script>
@endpush
