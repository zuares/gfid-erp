{{-- resources/views/production/sewing_pickups/_operator_modal.blade.php --}}

@push('head')
    <style>
        .sewing-modal-summary .mono {
            font-variant-numeric: tabular-nums;
        }
    </style>
@endpush

{{-- Modal --}}
<div class="modal fade" id="confirmSubmitModal" tabindex="-1" aria-labelledby="confirmSubmitLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title mb-0">Konfirmasi Sewing Pickup</h5>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                {{-- OPERATOR FOCAL POINT (pakai komponen) --}}
                <x-modal-confirm-operator title="Operator Jahit" label="Pilih Operator" :required="true"
                    :name="null" {{-- tidak kirim langsung ke backend, pakai hidden --}} selectId="operator_select_modal" :operators="$operators"
                    :selected="null"
                    description="Pilih <strong>Operator Jahit</strong> untuk semua bundle yang diambil." />

                {{-- DETAIL PICKUP --}}
                <div class="mb-3">
                    <div class="small fw-semibold mb-1">Detail Pickup Jahit</div>

                    <div class="table-responsive mb-2">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Item (kode)</th>
                                    <th class="text-end">Qty (pcs)</th>
                                </tr>
                            </thead>
                            <tbody id="sewing-summary-rows">
                                <tr>
                                    <td colspan="3" class="text-muted small">Belum ada bundle yang diambil.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- SUMMARY --}}
                <div class="mb-0 p-2 rounded border bg-light small sewing-modal-summary">
                    <div class="d-flex justify-content-between">
                        <span>Tanggal Ambil</span>
                        <span class="fw-semibold" id="sewing-summary-date">-</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Total qty pickup (pcs)</span>
                        <span class="fw-semibold mono" id="sewing-summary-total">0.00</span>
                    </div>
                </div>

            </div>

            <div class="modal-footer py-2">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-sm btn-primary" id="btn-confirm-submit" disabled>
                    Ya, Simpan
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const operatorHidden = document.getElementById("operator_id_hidden"); // hidden input di form utama
            const operatorSelect = document.getElementById("operator_select_modal");

            const modalEl = document.getElementById("confirmSubmitModal");
            const confirmBtn = document.getElementById("btn-confirm-submit");

            const rows = document.querySelectorAll(".bundle-row");
            const form = document.getElementById("sewing-pickup-form");

            const tblBody = document.getElementById("sewing-summary-rows");
            const summaryDate = document.getElementById("sewing-summary-date");
            const summaryTotal = document.getElementById("sewing-summary-total");

            let modal = new bootstrap.Modal(modalEl);

            operatorSelect.addEventListener("change", () => {
                const val = operatorSelect.value;
                operatorHidden.value = val;
                confirmBtn.disabled = val === "";
            });

            function escapeHtml(str) {
                return str?.replace(/[&<>"']/g, m => ({
                    "&": "&amp;",
                    "<": "&lt;",
                    ">": "&gt;",
                    '"': "&quot;",
                    "'": "&#039;"
                } [m])) ?? "";
            }

            // Build summary content
            function buildSummary() {
                const dateInput = document.querySelector("input[name='date']");
                summaryDate.textContent = dateInput?.value || "-";

                let list = [];
                rows.forEach(row => {
                    const input = row.querySelector("input.qty-input");
                    if (!input) return;
                    const qty = parseFloat(input.value || 0);
                    if (qty <= 0) return;

                    const code = row.dataset.itemCode || "-";

                    list.push({
                        code,
                        qty
                    });
                });

                tblBody.innerHTML = "";
                let total = 0;

                if (!list.length) {
                    tblBody.innerHTML = `
                        <tr>
                            <td colspan="3" class="text-muted small">Belum ada bundle yang diambil.</td>
                        </tr>
                    `;
                } else {
                    list.forEach((line, idx) => {
                        total += line.qty;
                        tblBody.innerHTML += `
                            <tr>
                                <td>${idx + 1}</td>
                                <td><span class="mono">${escapeHtml(line.code)}</span></td>
                                <td class="text-end mono">${line.qty.toFixed(2)}</td>
                            </tr>
                        `;
                    });
                }

                summaryTotal.textContent = total.toFixed(2);
            }

            // Intercept submit
            form.addEventListener("submit", function(e) {
                if (operatorHidden.value && modal?._isShown) return;

                e.preventDefault();

                let hasQty = false;
                rows.forEach(r => {
                    let input = r.querySelector("input.qty-input");
                    if (parseFloat(input?.value || 0) > 0) {
                        hasQty = true;
                    }
                });

                if (!hasQty) return;

                buildSummary();
                modal.show();
            });

            confirmBtn.addEventListener("click", function() {
                if (!operatorHidden.value) return;
                form.submit();
            });

        });
    </script>
@endpush
