{{-- resources/views/production/sewing_pickups/_operator_modal.blade.php --}}

@push('head')
    <style>
        .sewing-modal-summary .mono {
            font-variant-numeric: tabular-nums;
        }

        .sewing-supply-checklist {
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 12px;
            padding: .75rem;
            background: rgba(59, 130, 246, .035);
        }

        .sewing-supply-checklist-title {
            display: flex;
            justify-content: space-between;
            gap: .5rem;
            align-items: center;
            font-size: .82rem;
            font-weight: 700;
            margin-bottom: .45rem;
        }

        .sewing-supply-check-item {
            display: flex;
            align-items: flex-start;
            gap: .55rem;
            padding: .48rem .52rem;
            border-radius: 10px;
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .22);
        }

        .sewing-supply-check-item.is-short {
            background: rgba(239, 68, 68, .06);
            border-color: rgba(239, 68, 68, .38);
        }

        .sewing-supply-check-item + .sewing-supply-check-item {
            margin-top: .4rem;
        }

        .sewing-supply-check-item .form-check-input {
            width: 1.15rem;
            height: 1.15rem;
            margin-top: .12rem;
            flex-shrink: 0;
        }

        .sewing-supply-issue-input {
            max-width: 120px;
            font-size: .78rem;
            font-weight: 800;
            text-align: right;
            border-radius: 10px;
        }

        .sewing-supply-code {
            font-size: .82rem;
            font-weight: 800;
            line-height: 1.15;
        }

        .sewing-supply-name {
            font-size: .74rem;
            color: var(--muted);
            line-height: 1.25;
        }

        .sewing-supply-qty {
            font-size: .72rem;
            color: var(--muted);
        }

        .sewing-supply-shortage {
            font-size: .72rem;
            font-weight: 700;
            color: #dc2626;
        }

        @media (max-width: 767.98px) {
            .sewing-supply-checklist {
                padding: .65rem;
            }

            .sewing-supply-check-item {
                min-height: 48px;
            }
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

                {{-- KELENGKAPAN JAHIT: disembunyikan dulu, dibuka setelah klik Selanjutnya --}}
                <div class="mt-3 sewing-supply-checklist d-none" id="sewing-supply-step">
                    <div class="sewing-supply-checklist-title">
                        <span>Kelengkapan Jahit</span>
                        <span class="badge rounded-pill text-bg-light border" id="sewing-supply-check-count">0/0</span>
                    </div>
                    <div id="sewing-supply-checklist">
                        <div class="text-muted small">Belum ada kelengkapan jahit dari BOM.</div>
                    </div>
                </div>

            </div>

            <div class="modal-footer py-2">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-sm btn-primary" id="btn-confirm-submit" disabled>
                    Selanjutnya
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const bomSuppliesByItem = @json($bomSuppliesByItem ?? []);

            const operatorHidden = document.getElementById("operator_id_hidden"); // hidden input di form utama
            const operatorSelect = document.getElementById("operator_select_modal");
            const checklistPayload = document.getElementById("supplies_checklist_payload");

            const modalEl = document.getElementById("confirmSubmitModal");
            const confirmBtn = document.getElementById("btn-confirm-submit");

            const rows = document.querySelectorAll(".bundle-row");
            const form = document.getElementById("sewing-pickup-form");

            const tblBody = document.getElementById("sewing-summary-rows");
            const summaryDate = document.getElementById("sewing-summary-date");
            const summaryTotal = document.getElementById("sewing-summary-total");
            const supplyStep = document.getElementById("sewing-supply-step");
            const supplyChecklist = document.getElementById("sewing-supply-checklist");
            const supplyCheckCount = document.getElementById("sewing-supply-check-count");

            let modal = new bootstrap.Modal(modalEl);
            let modalStep = 'summary';
            let activeSupplyItems = [];

            function syncConfirmState() {
                const val = operatorSelect.value;
                operatorHidden.value = val;
                confirmBtn.disabled = val === "";
                if (supplyCheckCount) {
                    const complete = activeSupplyItems.filter(item => Number(item.issuedPieces || 0) + 0.000001 >= Number(item.totalPieces || 0)).length;
                    supplyCheckCount.textContent = `${complete}/${activeSupplyItems.length}`;
                    supplyCheckCount.classList.toggle("text-bg-success", activeSupplyItems.length > 0 && complete === activeSupplyItems.length);
                    supplyCheckCount.classList.toggle("text-bg-warning", activeSupplyItems.length > 0 && complete < activeSupplyItems.length);
                    supplyCheckCount.classList.toggle("text-bg-light", activeSupplyItems.length === 0);
                }
            }

            operatorSelect.addEventListener("change", () => {
                syncConfirmState();
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

            function formatQty(num) {
                const value = Number(num || 0);
                if (!Number.isFinite(value) || value <= 0) return "0";
                return value.toLocaleString("id-ID", {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 4
                });
            }

            function materialQtyFromPieces(item, pieces) {
                return Math.max(Number(pieces || 0), 0) * Number(item.qtyPerPiece || 0);
            }

            function buildSupplyChecklist(selectedLines) {
                const aggregate = new Map();

                selectedLines.forEach(line => {
                    const supplies = bomSuppliesByItem[line.finishedItemId] || [];
                    supplies.forEach(supply => {
                        const key = String(supply.id || supply.code);
                        const current = aggregate.get(key) || {
                            id: supply.id,
                            code: supply.code || "-",
                            name: supply.name || "",
                            uom: supply.uom || "",
                            qty: 0,
                            totalPieces: 0,
                            stockAvailable: Number(supply.stock_available || 0),
                        };
                        current.qty += (Number(line.qty || 0) * Number(supply.qty || 0));
                        current.totalPieces += Number(line.qty || 0);
                        current.stockAvailable = Number(supply.stock_available || current.stockAvailable || 0);
                        aggregate.set(key, current);
                    });
                });

                activeSupplyItems = Array.from(aggregate.values())
                    .map(item => {
                        const qtyPerPiece = Number(item.totalPieces || 0) > 0
                            ? Number(item.qty || 0) / Number(item.totalPieces || 0)
                            : 0;
                        const stockPieces = qtyPerPiece > 0
                            ? Number(item.stockAvailable || 0) / qtyPerPiece
                            : 0;
                        const issuedPieces = 0; // biarkan operator isi manual

                        return {
                            ...item,
                            qtyPerPiece,
                            stockPieces,
                            issuedPieces,
                            issuedQty: 0,
                            shortagePieces: Number(item.totalPieces || 0),
                            shortage: Number(item.qty || 0),
                        };
                    })
                    .sort((a, b) => String(a.code).localeCompare(String(b.code)));

                if (!supplyChecklist) return;

                if (!activeSupplyItems.length) {
                    supplyChecklist.innerHTML = `<div class="text-muted small">Tidak ada kelengkapan jahit dari BOM untuk bundle ini.</div>`;
                    syncConfirmState();
                    return;
                }

                supplyChecklist.innerHTML = activeSupplyItems.map((item, idx) => {
                    const issuedPieces = Number(item.issuedPieces || 0);
                    const currentShortPieces = Math.max(Number(item.totalPieces || 0) - issuedPieces, 0);
                    const isShort = currentShortPieces > 0.000001;

                    return `
                        <label class="sewing-supply-check-item ${isShort ? 'is-short' : ''}">
                            <span class="flex-grow-1">
                                <span class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="mono sewing-supply-code">${escapeHtml(item.code)}</span>
                                    <span class="sewing-supply-qty">Butuh ${formatQty(item.totalPieces)} pcs</span>
                                    <span class="sewing-supply-qty">Stok cukup ${formatQty(item.stockPieces)} pcs</span>
                                    ${isShort ? `<span class="sewing-supply-shortage">Kurang ${formatQty(currentShortPieces)} pcs</span>` : ''}
                                </span>
                                <span class="sewing-supply-name">${escapeHtml(item.name)}</span>
                                <span class="d-block sewing-supply-qty">BOM: ${formatQty(item.qty)} ${escapeHtml(item.uom)}</span>
                            </span>
                            <span>
                                <span class="d-block text-muted small mb-1">Dibawa (pcs)</span>
                                <input type="number" step="1" min="0" inputmode="numeric"
                                    class="form-control form-control-sm sewing-supply-issue-input js-sewing-supply-issued"
                                    data-index="${idx}" value="${issuedPieces > 0 ? Math.floor(issuedPieces) : ''}" placeholder="0">
                            </span>
                        </label>
                    `;
                }).join("");

                supplyChecklist.querySelectorAll(".js-sewing-supply-issued").forEach(input => {
                    input.addEventListener("input", function () {
                        const idx = Number(this.dataset.index || -1);
                        const item = activeSupplyItems[idx];
                        if (!item) return;

                        item.issuedPieces = Math.max(parseFloat(this.value || "0") || 0, 0);
                        item.issuedQty = materialQtyFromPieces(item, item.issuedPieces);
                        item.issued_qty = item.issuedQty;
                        item.issued_pcs = item.issuedPieces;

                        const shortagePieces = Math.max(Number(item.totalPieces || 0) - Number(item.issuedPieces || 0), 0);
                        item.shortagePieces = shortagePieces;
                        item.shortage = materialQtyFromPieces(item, shortagePieces);

                        const wrap = this.closest(".sewing-supply-check-item");
                        const shortageEl = wrap?.querySelector(".sewing-supply-shortage");
                        wrap?.classList.toggle("is-short", shortagePieces > 0.000001);
                        if (shortageEl) {
                            if (shortagePieces > 0.000001) {
                                shortageEl.textContent = `Kurang ${formatQty(shortagePieces)} pcs`;
                            } else {
                                shortageEl.remove();
                            }
                        } else if (shortagePieces > 0.000001) {
                            const line = wrap?.querySelector(".d-flex.align-items-center");
                            line?.insertAdjacentHTML("beforeend", `<span class="sewing-supply-shortage">Kurang ${formatQty(shortagePieces)} pcs</span>`);
                        }
                        syncConfirmState();
                    });
                });

                syncConfirmState();
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
                    const finishedItemId = row.dataset.finishedItemId || "";

                    list.push({
                        code,
                        qty,
                        finishedItemId,
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
                buildSupplyChecklist(list);
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

                modalStep = 'summary';
                supplyStep?.classList.add('d-none');
                confirmBtn.textContent = 'Selanjutnya';
                buildSummary();
                modal.show();
            });

            confirmBtn.addEventListener("click", function() {
                if (!operatorHidden.value) return;

                if (modalStep === 'summary') {
                    modalStep = 'supplies';
                    supplyStep?.classList.remove('d-none');
                    confirmBtn.textContent = 'Simpan';
                    supplyStep?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    syncConfirmState();
                    return;
                }

                if (checklistPayload) {
                    checklistPayload.value = JSON.stringify({
                        checked_at: new Date().toISOString(),
                        items: activeSupplyItems.map(item => ({
                            ...item,
                            issued_qty: Number(item.issuedQty || item.issued_qty || 0),
                            issued_pcs: Number(item.issuedPieces || item.issued_pcs || 0),
                        })),
                    });
                }
                form.submit();
            });

        });
    </script>
@endpush
