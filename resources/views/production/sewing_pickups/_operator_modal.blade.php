{{-- resources/views/production/sewing_pickups/_operator_modal.blade.php --}}

@push('head')
    <style>
        /* ── Confirm phase: bundle list ── */
        .sc-tbl { width:100%; border-collapse:collapse; font-size:.8rem; }
        .sc-tbl thead th { font-size:.68rem; font-weight:600; color:var(--muted);
                           text-transform:uppercase; letter-spacing:.04em;
                           padding:.3rem .45rem; border-bottom:1px solid rgba(148,163,184,.25); }
        .sc-tbl tbody tr:nth-child(odd) { background:rgba(148,163,184,.05); }
        .sc-tbl td { padding:.42rem .45rem; vertical-align:middle; }
        .sc-td-no   { width:28px; color:var(--muted); font-size:.7rem; text-align:center; }
        .sc-td-date { white-space:nowrap; color:var(--muted); font-size:.73rem; }
        .sc-td-code { font-size:.92rem; font-weight:900;
                      font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; }
        .sc-td-qty  { text-align:right; white-space:nowrap; font-size:.9rem; font-weight:800; }

        /* ── Supply phase: checklist rows ── */
        .sc-row { display:grid; grid-template-columns:24px 1fr auto; align-items:center; gap:.5rem;
                  padding:.45rem .6rem; border:1px solid rgba(148,163,184,.18); border-radius:10px;
                  margin-bottom:.35rem; transition:background .15s; }
        .sc-row.is-ok    { background:rgba(22,163,74,.06);  border-color:rgba(22,163,74,.25); }
        .sc-row.is-short { background:rgba(239,68,68,.05);  border-color:rgba(239,68,68,.25); }
        .sc-chk   { width:1.1rem; height:1.1rem; cursor:pointer; accent-color:#2563eb; flex-shrink:0; }
        .sc-label { font-size:.78rem; font-weight:700; line-height:1.15; }
        .sc-sub   { font-size:.69rem; color:var(--muted); }
        .sc-input { width:68px; text-align:right; font-weight:800; font-size:.8rem;
                    border-radius:8px; padding:.2rem .35rem; border:1px solid rgba(148,163,184,.4);
                    background:var(--card); color:var(--text); }
        .sc-input:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 2px rgba(37,99,235,.15); }
        .sc-step-hdr { display:flex; justify-content:space-between; align-items:center;
                       margin-bottom:.75rem; }
        .sc-step-main { font-size:1.15rem; font-weight:900; letter-spacing:-.01em;
                        font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; }
    </style>
@endpush

{{-- Modal --}}
<div class="modal fade" id="confirmSubmitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md">
        <div class="modal-content">

            <div class="modal-header py-2">
                <h5 class="modal-title mb-0" id="modal-phase-title">Konfirmasi Sewing Pickup</h5>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                {{-- ══ PHASE 1: Konfirmasi ══ --}}
                <div id="phase-confirm">

                    {{-- Operator --}}
                    <x-modal-confirm-operator title="Operator Jahit" label="Pilih Operator" :required="true"
                        :name="null" selectId="operator_select_modal" :operators="$operators"
                        :selected="null"
                        description="Pilih <strong>Operator Jahit</strong> untuk semua bundle yang diambil." />

                    {{-- Bundle list (diisi JS) --}}
                    <div class="mt-3">
                        <div class="small fw-bold text-muted mb-1">Yang Diambil</div>
                        <div id="confirm-bundle-list"></div>
                    </div>

                </div>

                {{-- ══ PHASE 2: Kelengkapan Jahit step-by-step ══ --}}
                <div id="phase-supply" style="display:none">
                    <div class="sc-step-hdr">
                        <span class="sc-step-main" id="sc-step-title">—</span>
                        <span class="badge rounded-pill text-bg-secondary" id="sc-step-prog">0 / 0</span>
                    </div>
                    <div id="sewing-supply-checklist"></div>
                </div>

            </div>

            <div class="modal-footer py-2 gap-1">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-sm btn-outline-secondary" id="btn-step-back" style="display:none">← Kembali</button>
                <button class="btn btn-sm btn-primary" id="btn-confirm-submit" disabled>Lanjut →</button>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const bomSuppliesByItem  = @json($bomSuppliesByItem ?? []);

    const operatorHidden     = document.getElementById('operator_id_hidden');
    const operatorSelect     = document.getElementById('operator_select_modal');
    const checklistPayload   = document.getElementById('supplies_checklist_payload');
    const modalEl            = document.getElementById('confirmSubmitModal');
    const confirmBtn         = document.getElementById('btn-confirm-submit');
    const backBtn            = document.getElementById('btn-step-back');
    const rows               = document.querySelectorAll('.bundle-row');
    const form               = document.getElementById('sewing-pickup-form');

    const phaseConfirm       = document.getElementById('phase-confirm');
    const phaseSupply        = document.getElementById('phase-supply');
    const modalTitle         = document.getElementById('modal-phase-title');
    const bundleListEl       = document.getElementById('confirm-bundle-list');
    const supplyChecklist    = document.getElementById('sewing-supply-checklist');
    const stepTitle          = document.getElementById('sc-step-title');
    const stepProg           = document.getElementById('sc-step-prog');

    const modal = new bootstrap.Modal(modalEl);

    /* ── state ── */
    let phase            = 'confirm';   // 'confirm' | 'supply'
        let selectedLines    = [];          // [{bundleId, code, qty, finishedItemId}]
        let activeSupplyItems = [];         // [{bundleId, code, qty, finishedItemId, supplies:[...]}]
    let currentStep      = 0;

    /* ── helpers ── */
    function esc(s) {
        return (s ?? '').replace(/[&<>"']/g, m =>
            ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
    }
    function fmt(n) {
        const v = Number(n || 0);
        return Number.isFinite(v) ? v.toLocaleString('id-ID', {maximumFractionDigits:4}) : '0';
    }

    /* ── operator sync ── */
    operatorSelect.addEventListener('change', () => {
        operatorHidden.value = operatorSelect.value;
        confirmBtn.disabled  = !operatorSelect.value;
    });

    /* ── ambil baris yang ada qty ── */
    function getSelectedLines() {
        return [...rows].flatMap(row => {
            const qty = parseFloat(row.querySelector('input.qty-input')?.value || 0);
            return qty > 0
                ? [{
                    bundleId: row.dataset.bundleId || row.querySelector('input[name*="[bundle_id]"]')?.value || '',
                    code: row.dataset.itemCode || '-',
                    qty,
                    finishedItemId: row.dataset.finishedItemId || '',
                }]
                : [];
        });
    }

    /* ══ PHASE 1: tampilkan konfirmasi ══ */
    function showConfirm(lines) {
        phase         = 'confirm';
        selectedLines = lines;

        phaseConfirm.style.display = '';
        phaseSupply.style.display  = 'none';
        modalTitle.textContent     = 'Konfirmasi Sewing Pickup';
        backBtn.style.display      = 'none';
        confirmBtn.textContent     = 'Lanjut →';
        confirmBtn.disabled        = !operatorSelect.value;

        // Tanggal ambil dari input form utama
        const dateRaw = form.querySelector('input[name="date"]')?.value || '';
        let dateFormatted = '';
        if (dateRaw) {
            const [y, m, d] = dateRaw.split('-');
            dateFormatted = `${d}/${m}/${y}`;
        }

        bundleListEl.innerHTML = `
            <table class="sc-tbl">
                <thead>
                    <tr>
                        <th class="sc-td-no">No</th>
                        <th>Tanggal</th>
                        <th>Kode Barang</th>
                        <th class="text-end">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    ${lines.map((l, i) => `
                        <tr>
                            <td class="sc-td-no">${i + 1}</td>
                            <td class="sc-td-date">${dateFormatted}</td>
                            <td class="sc-td-code">${esc(l.code)}</td>
                            <td class="sc-td-qty">${fmt(l.qty)} pcs</td>
                        </tr>`).join('')}
                </tbody>
            </table>`;
    }

    /* ══ PHASE 2: init supply steps ══ */
    function startSupplyPhase() {
        activeSupplyItems = selectedLines.map(line => {
            const fid  = String(line.finishedItemId);
            const bom  = bomSuppliesByItem[fid] || [];
            const qty  = Number(line.qty || 0);

            return {
                bundleId:       line.bundleId,
                code:           line.code,
                qty,
                finishedItemId:  line.finishedItemId,
                supplies:       bom.map(s => ({
                    id:          s.id,
                    code:        s.code || '-',
                    name:        s.name || '',
                    uom:         s.uom || '',
                    qty:         qty,
                    qtyPerPiece: Number(s.qty || 0),
                    issued:      0,
                })),
            };
        }).filter(line => line.supplies.length);

        if (!activeSupplyItems.length) {
            // Tidak ada BOM → langsung submit tanpa supply checklist
            submitForm();
            return;
        }

        phase       = 'supply';
        currentStep = 0;
        phaseConfirm.style.display = 'none';
        phaseSupply.style.display  = '';
        modalTitle.textContent     = 'Kelengkapan Jahit';
        renderStep(0);
    }

    /* ── render satu step supply ── */
    function renderStep(idx) {
        const line  = activeSupplyItems[idx];
        const total = activeSupplyItems.length;

        // Title: tampilkan daftar item, potong jika terlalu panjang
        const titleText = line.code.length > 40
            ? line.code.substring(0, 38) + '…'
            : line.code;
        stepTitle.textContent = `${titleText}  ·  ${fmt(line.qty)} pcs`;
        stepProg.style.display = '';
        stepProg.textContent = `${idx + 1} / ${total}`;

        backBtn.style.display = '';   // selalu tampil di phase supply (untuk kembali ke confirm juga)
        const isLast = idx === total - 1;
        confirmBtn.textContent = isLast ? 'Simpan' : 'Lanjut →';
        confirmBtn.disabled    = !operatorSelect.value;

        if (!line.supplies.length) {
            supplyChecklist.innerHTML = `<div class="text-muted small py-2">Tidak ada kelengkapan dari BOM untuk bundle ini.</div>`;
            return;
        }

        supplyChecklist.innerHTML = line.supplies.map((sup, si) => {
            const isOk = sup.issued >= sup.qty - 0.0001;
            return `
                <div class="sc-row ${isOk ? 'is-ok' : ''}" data-si="${si}">
                    <input type="checkbox" class="sc-chk js-chk" data-si="${si}" ${isOk ? 'checked' : ''}>
                    <div>
                        <div class="sc-label">${esc(sup.name)}</div>
                        <div class="sc-sub">Butuh ${fmt(sup.qty)} pcs</div>
                    </div>
                    <input type="number" step="1" min="0" inputmode="numeric"
                           class="sc-input js-inp" data-si="${si}"
                           value="${sup.issued > 0 ? Math.floor(sup.issued) : 0}"
                           placeholder="${fmt(sup.qty)}">
                </div>`;
        }).join('');

        /* helper: sync visual state dari sup.issued */
        function syncRow(row, sup) {
            const chk  = row.querySelector('.js-chk');
            const inp  = row.querySelector('.js-inp');
            const isOk = sup.issued >= sup.qty - 0.0001;
            row.classList.toggle('is-ok',    isOk);
            row.classList.toggle('is-short', sup.issued > 0.0001 && !isOk);
            if (chk) chk.checked = isOk;
            if (inp) inp.value = sup.issued > 0 ? Math.floor(sup.issued) : 0;
        }

        /* klik baris → toggle isi penuh / kosong */
        supplyChecklist.querySelectorAll('.sc-row').forEach(row => {
            row.addEventListener('click', function (e) {
                if (e.target.classList.contains('js-inp')) return; // biarkan input ketik bebas
                const sup = activeSupplyItems[currentStep]?.supplies?.[Number(this.dataset.si)];
                if (!sup) return;
                const isOk = sup.issued >= sup.qty - 0.0001;
                sup.issued = isOk ? 0 : sup.qty;  // toggle
                syncRow(this, sup);
            });
        });

        /* input manual → auto-checklist jika terpenuhi */
        supplyChecklist.querySelectorAll('.js-inp').forEach(inp => {
            inp.addEventListener('input', function () {
                const sup = activeSupplyItems[currentStep]?.supplies?.[Number(this.dataset.si)];
                if (!sup) return;
                sup.issued = Math.max(parseFloat(this.value || '0') || 0, 0);
                const row = this.closest('.sc-row');
                const chk = row?.querySelector('.js-chk');
                const isOk = sup.issued >= sup.qty - 0.0001;
                row?.classList.toggle('is-ok',    isOk);
                row?.classList.toggle('is-short', sup.issued > 0.0001 && !isOk);
                if (chk) chk.checked = isOk;
            });
        });

        /* checkbox diklik langsung (fallback) */
        supplyChecklist.querySelectorAll('.js-chk').forEach(chk => {
            chk.addEventListener('change', function (e) {
                e.stopPropagation(); // sudah ditangani row click
            });
        });
    }

    /* ── aggregate & submit ── */
    function submitForm() {
        const agg = new Map();
        const bundlePayload = [];
        activeSupplyItems.forEach(line => {
            const bundleSupplies = [];
            line.supplies.forEach(sup => {
                const key = String(sup.id);
                const cur = agg.get(key) || { id: sup.id, code: sup.code, name: sup.name, uom: sup.uom, issued_pcs: 0, issued_qty: 0, issuedPieces: 0, issuedQty: 0 };
                cur.issued_pcs += sup.issued;
                cur.issued_qty += sup.issued * sup.qtyPerPiece;
                cur.issuedPieces = cur.issued_pcs;
                cur.issuedQty    = cur.issued_qty;
                agg.set(key, cur);

                bundleSupplies.push({
                    id: sup.id,
                    code: sup.code,
                    name: sup.name,
                    uom: sup.uom,
                    required_pcs: sup.qty,
                    issued_pcs: sup.issued,
                    qty_per_piece: sup.qtyPerPiece,
                    required_qty: sup.qty * sup.qtyPerPiece,
                    issued_qty: sup.issued * sup.qtyPerPiece,
                });
            });

            bundlePayload.push({
                bundle_id: line.bundleId,
                code: line.code,
                qty: line.qty,
                finished_item_id: line.finishedItemId,
                supplies: bundleSupplies,
            });
        });
        if (checklistPayload) {
            checklistPayload.value = JSON.stringify({
                checked_at: new Date().toISOString(),
                items: [...agg.values()],
                bundles: bundlePayload,
            });
        }
        form.submit();
    }

    /* ── form submit → buka modal phase 1 ── */
    form.addEventListener('submit', function (e) {
        if (modal._isShown) return;
        e.preventDefault();
        const list = getSelectedLines();
        if (!list.length) return;
        showConfirm(list);
        modal.show();
    });

    /* ── tombol utama (Lanjut / Simpan) ── */
    confirmBtn.addEventListener('click', function () {
        if (!operatorSelect.value) return;

        if (phase === 'confirm') {
            startSupplyPhase();
            return;
        }

        // phase supply
        if (currentStep < activeSupplyItems.length - 1) {
            currentStep++;
            renderStep(currentStep);
        } else {
            submitForm();
        }
    });

    /* ── tombol Kembali ── */
    backBtn.addEventListener('click', function () {
        if (phase === 'supply') {
            if (currentStep > 0) {
                currentStep--;
                renderStep(currentStep);
            } else {
                // kembali ke phase confirm
                phaseConfirm.style.display = '';
                phaseSupply.style.display  = 'none';
                modalTitle.textContent     = 'Konfirmasi Sewing Pickup';
                phase                      = 'confirm';
                backBtn.style.display      = 'none';
                confirmBtn.textContent     = 'Lanjut →';
                confirmBtn.disabled        = !operatorSelect.value;
            }
        }
    });

    /* ── reset saat modal ditutup ── */
    modalEl.addEventListener('hidden.bs.modal', function () {
        operatorSelect.value     = '';
        operatorHidden.value     = '';
        confirmBtn.disabled      = true;
        confirmBtn.textContent   = 'Lanjut →';
        backBtn.style.display    = 'none';
        phase                    = 'confirm';
        currentStep              = 0;
        activeSupplyItems        = [];
        phaseConfirm.style.display = '';
        phaseSupply.style.display  = 'none';
        modalTitle.textContent     = 'Konfirmasi Sewing Pickup';
    });
});
</script>
@endpush
