{{-- resources/views/production/sewing_pickups/_operator_modal.blade.php --}}
@php
$isOwner = auth()->user()?->isOwner()
    || in_array(auth()->user()?->role ?? '', ['admin', 'operating'], true);
// Strict: hanya owner sungguhan yang boleh melihat kolom "Kebutuhan" (kebutuhan + stok RM).
$isOwnerStrict = (bool) (auth()->user()?->isOwner());
@endphp

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
        .sc-supply-cols,
        .sc-row { display:grid; grid-template-columns:22px minmax(100px,1fr) 88px 58px 66px;
                  align-items:center; gap:.4rem; }
        /* Non-owner: tanpa kolom Kebutuhan → 4 kolom, lebih lega. */
        .sc-supply-cols.no-need,
        .sc-row.no-need { grid-template-columns:24px minmax(120px,1fr) 66px 78px; }
        .sc-supply-cols { padding:0 .6rem .25rem; color:var(--muted); font-size:.58rem;
                          font-weight:900; text-transform:uppercase; letter-spacing:.04em; }
        .sc-row {
                  padding:.45rem .6rem; border:1px solid rgba(148,163,184,.18); border-radius:10px;
                  margin-bottom:.35rem; transition:background .15s; }
        .sc-row.is-ok    { background:rgba(22,163,74,.06);  border-color:rgba(22,163,74,.25); }
        .sc-row.is-short { background:rgba(239,68,68,.05);  border-color:rgba(239,68,68,.25); }
        .sc-chk   { width:1.1rem; height:1.1rem; cursor:pointer; accent-color:#2563eb; flex-shrink:0; }
        .sc-label { font-size:.78rem; font-weight:700; line-height:1.15; }
        .sc-sub   { font-size:.66rem; color:var(--muted); }
        .sc-need  { text-align:right; font-size:.72rem; font-weight:800; white-space:nowrap; }
        .sc-stock { display:block; color:var(--muted); font-size:.59rem; font-weight:600; }
        .sc-stock.is-zero { color:#dc2626; }
        .sc-pcs   { text-align:right; font-size:.8rem; font-weight:900; white-space:nowrap; color:#1d4ed8; }
        .sc-followup-note { margin-top:.6rem; padding:.55rem .65rem; border-radius:8px;
                            background:#fff7ed; border:1px solid #fed7aa; color:#9a3412;
                            font-size:.7rem; font-weight:700; }
        .sc-input { width:66px; text-align:right; font-weight:800; font-size:.8rem;
                    border-radius:8px; padding:.2rem .35rem; border:1px solid rgba(148,163,184,.4);
                    background:var(--card); color:var(--text); }
        .sc-input:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 2px rgba(37,99,235,.15); }
        .sc-step-hdr { display:flex; justify-content:space-between; align-items:center;
                       margin-bottom:.75rem; }
        .sc-step-main { font-size:1.15rem; font-weight:900; letter-spacing:-.01em;
                        font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; }
        @media (max-width:575.98px) {
            .sc-supply-cols,
            .sc-row { grid-template-columns:22px minmax(72px,1fr) 66px 48px 62px; gap:.3rem; }
            /* Non-owner (mobile): 4 kolom, kolom Dibawa lebih lebar untuk jari. */
            .sc-supply-cols.no-need,
            .sc-row.no-need { grid-template-columns:22px minmax(92px,1fr) 54px 68px; }
            .sc-supply-cols { padding-left:.35rem; padding-right:.35rem; font-size:.54rem; }
            .sc-row { padding:.55rem .4rem; margin-bottom:.45rem; }
            .sc-label { font-size:.74rem; }
            .sc-sub { font-size:.62rem; }
            .sc-need { font-size:.66rem; }
            .sc-pcs { font-size:.74rem; }
            /* Sasaran sentuh lebih besar & nyaman di mobile */
            .sc-chk { width:1.3rem; height:1.3rem; }
            .sc-input { width:100%; min-width:52px; height:2.1rem; font-size:.9rem; padding:.25rem .3rem; }
        }

        #btn-confirm-submit,
        #btn-confirm-submit * { color: #fff !important; }

        .sp-meta-grid {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:.55rem;
            margin-bottom:.75rem;
        }
        .sp-meta-card {
            border:1px solid rgba(148,163,184,.22);
            background:rgba(148,163,184,.06);
            border-radius:12px;
            padding:.55rem .65rem;
            min-width:0;
        }
        .sp-meta-label {
            font-size:.62rem;
            font-weight:900;
            color:var(--muted);
            text-transform:uppercase;
            letter-spacing:.05em;
            margin-bottom:.28rem;
        }
        .sp-meta-value {
            min-height:31px;
            display:flex;
            align-items:center;
            font-size:.82rem;
            font-weight:900;
            line-height:1.2;
        }
        .sp-meta-sub {
            color:var(--muted);
            font-size:.66rem;
            font-weight:700;
            margin-top:.1rem;
            line-height:1.2;
        }
        #modal_pickup_date {
            border-radius:10px;
            font-size:.82rem;
            font-weight:900;
        }
        @media (max-width:575.98px) {
            .sp-meta-grid { grid-template-columns:1fr; gap:.4rem; }
            .sp-meta-card { padding:.48rem .55rem; }
        }
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

                    <div class="sp-meta-grid">
                        <div class="sp-meta-card">
                            <div class="sp-meta-label">Tanggal Ambil</div>
                            <input type="date" id="modal_pickup_date" class="form-control form-control-sm"
                                required aria-describedby="modal-pickup-date-error">
                            <div id="modal-pickup-date-error" class="invalid-feedback">
                                Tanggal ambil jahit wajib dipilih.
                            </div>
                        </div>
                        <div class="sp-meta-card">
                            <div class="sp-meta-label">WIP Tujuan</div>
                            <div class="sp-meta-value">
                                {{ $wipSewWarehouse?->code ?? 'WIP-SEW' }}
                            </div>
                            <div class="sp-meta-sub">
                                {{ $wipSewWarehouse?->name ?? 'Sedang Jahit' }}
                            </div>
                        </div>
                    </div>

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

            <div class="modal-footer py-2 d-flex justify-content-between align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i>
                    <span class="d-none d-sm-inline ms-1">Batal</span>
                </button>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-step-back" style="display:none">
                        <i class="bi bi-arrow-left"></i>
                        <span class="d-none d-sm-inline ms-1">Kembali</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-simpan-cetak" style="display:none"
                        @disabled(!$isOwner) title="{{ !$isOwner ? 'Hanya owner yang dapat menyimpan' : '' }}">
                        <i class="bi bi-printer"></i>
                        <span class="d-none d-sm-inline ms-1">Simpan & Cetak</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="btn-confirm-submit" disabled style="color:#fff !important"
                        title="{{ !$isOwner ? 'Hanya owner yang dapat menyimpan' : '' }}">
                        <i class="bi bi-arrow-right" id="btn-confirm-icon"></i>
                        <span class="ms-1" id="btn-confirm-label">Lanjut</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const bomSuppliesByItem  = @json($bomSuppliesByItem ?? []);
    const isOwner            = {{ json_encode($isOwner) }};
    // Hanya owner yang melihat kolom "Kebutuhan" (kebutuhan qty + stok RM).
    const isOwnerStrict      = {{ json_encode($isOwnerStrict) }};

    const operatorHidden     = document.getElementById('operator_id_hidden');
    const operatorSelect     = document.getElementById('operator_select_modal');
    const checklistPayload   = document.getElementById('supplies_checklist_payload');
    const modalEl            = document.getElementById('confirmSubmitModal');
    const confirmBtn         = document.getElementById('btn-confirm-submit');
    const confirmIcon        = document.getElementById('btn-confirm-icon');
    const confirmLabel       = document.getElementById('btn-confirm-label');
    const backBtn            = document.getElementById('btn-step-back');
    const simpanCetakBtn     = document.getElementById('btn-simpan-cetak');
    const modalPickupDate    = document.getElementById('modal_pickup_date');

    function setConfirmState(isLast) {
        if (confirmIcon) {
            confirmIcon.className = isLast ? 'bi bi-check2-circle' : 'bi bi-arrow-right';
        }
        if (confirmLabel) {
            confirmLabel.textContent = isLast ? 'Simpan' : 'Lanjut';
        }
        if (simpanCetakBtn) simpanCetakBtn.style.display = isLast ? '' : 'none';
        // non-owner: disable tombol simpan di step terakhir
        if (isLast && !isOwner) {
            confirmBtn.disabled = true;
        }
    }
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
    const formDateInput = form.querySelector('input[name="date"]');

    function syncAndValidatePickupDate() {
        const date = modalPickupDate?.value || '';
        const valid = /^\d{4}-\d{2}-\d{2}$/.test(date);

        modalPickupDate?.classList.toggle('is-invalid', !valid);
        if (!valid) {
            modalPickupDate?.focus();
            return false;
        }

        if (formDateInput) {
            formDateInput.value = date;
            formDateInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        return true;
    }

    if (modalPickupDate && formDateInput) {
        modalPickupDate.value = formDateInput.value || '';
        modalPickupDate.addEventListener('change', () => {
            syncAndValidatePickupDate();
        });
    }

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
        const atLastStep = phase === 'supply' && currentStep === activeSupplyItems.length - 1;
        confirmBtn.disabled = !operatorSelect.value || (atLastStep && !isOwner);
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

        if (modalPickupDate && formDateInput) {
            modalPickupDate.value = formDateInput.value || '';
            modalPickupDate.classList.remove('is-invalid');
        }

        phaseConfirm.style.display = '';
        phaseSupply.style.display  = 'none';
        modalTitle.textContent     = 'Konfirmasi Sewing Pickup';
        backBtn.style.display      = 'none';
        setConfirmState(false);
        confirmBtn.disabled        = !operatorSelect.value;

        // Tanggal ambil dari input form utama
        const dateRaw = modalPickupDate?.value || form.querySelector('input[name="date"]')?.value || '';
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
                    stockAvailable: Number(s.stock_available || 0),
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
        setConfirmState(isLast);
        confirmBtn.disabled    = !operatorSelect.value;

        if (!line.supplies.length) {
            supplyChecklist.innerHTML = `<div class="text-muted small py-2">Tidak ada kelengkapan dari BOM untuk bundle ini.</div>`;
            return;
        }

        // Non-owner: sembunyikan kolom "Kebutuhan" (kebutuhan qty + stok RM) → grid 4 kolom.
        const needCls = isOwnerStrict ? '' : ' no-need';

        supplyChecklist.innerHTML = `
            <div class="sc-supply-cols${needCls}" aria-hidden="true">
                <span></span>
                <span>Material</span>
                ${isOwnerStrict ? '<span class="text-end">Kebutuhan</span>' : ''}
                <span class="text-end">PCS</span>
                <span class="text-end">Dibawa</span>
            </div>
        ` + line.supplies.map((sup, si) => {
            const isOk = sup.issued >= sup.qty - 0.0001;
            const needQty = sup.qty * sup.qtyPerPiece;
            const stockIsZero = sup.stockAvailable <= 0.0001;
            const needCell = isOwnerStrict ? `
                    <div class="sc-need">
                        ${fmt(needQty)} ${esc(sup.uom)}
                        <span class="sc-stock ${stockIsZero ? 'is-zero' : ''}">Stok ${fmt(sup.stockAvailable)}</span>
                    </div>` : '';
            return `
                <div class="sc-row${needCls} ${isOk ? 'is-ok' : ''}" data-si="${si}">
                    <input type="checkbox" class="sc-chk js-chk" data-si="${si}" ${isOk ? 'checked' : ''}>
                    <div>
                        <div class="sc-label">${esc(sup.name)}</div>
                        <div class="sc-sub">${esc(sup.code)}</div>
                    </div>${needCell}
                    <div class="sc-pcs">${fmt(sup.qty)} pcs</div>
                    <input type="number" step="1" min="0" inputmode="numeric"
                           class="sc-input js-inp" data-si="${si}"
                           value="${sup.issued > 0 ? Math.floor(sup.issued) : 0}"
                           placeholder="${fmt(sup.qty)}">
                </div>`;
        }).join('');

        const hasSystemShortage = line.supplies.some(sup =>
            sup.stockAvailable + 0.0001 < (sup.qty * sup.qtyPerPiece)
        );
        if (hasSystemShortage) {
            supplyChecklist.insertAdjacentHTML('beforeend', `
                <div class="sc-followup-note">
                    Stok sistem tidak mencukupi. Isi kolom <b>Dibawa</b> sesuai barang fisik yang benar-benar ada.
                    Selisih akan dibuat sebagai adjustment pending dan kelengkapan dipenuhi otomatis setelah disetujui.
                </div>
            `);
            if (isLast && confirmLabel) confirmLabel.textContent = 'Simpan - Menyusul';
        }

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
        if (!syncAndValidatePickupDate()) return;

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
        if (!syncAndValidatePickupDate()) return;
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
            if (!isOwner) return; // guard: non-owner tidak bisa simpan
            submitForm();
        }
    });

    /* ── tombol Simpan & Cetak → preview popup dulu ── */
    if (simpanCetakBtn) {
        simpanCetakBtn.addEventListener('click', function () {
            if (!syncAndValidatePickupDate()) return;
            openPreviewWindow();
        });
    }

    function openPreviewWindow() {
        /* ── kumpulkan data untuk preview ── */
        const operatorName = operatorSelect.options[operatorSelect.selectedIndex]?.text || '—';
        const dateRaw  = form.querySelector('input[name="date"]')?.value || '';
        let dateFmt = '—';
        if (dateRaw) {
            const [y, m, d] = dateRaw.split('-');
            dateFmt = `${d}/${m}/${y}`;
        }

        /* items */
        const lines    = selectedLines;
        const totalQty = lines.reduce((s, l) => s + Number(l.qty), 0);
        const itemRows = lines.map((l, i) =>
            `<tr>
                <td>${i + 1}</td>
                <td>${esc(l.code)}</td>
                <td class="r">${Number(l.qty).toLocaleString('id-ID')}</td>
            </tr>`
        ).join('');

        /* supplies — aggregate across all bundles */
        const supplyMap = new Map();
        activeSupplyItems.forEach(line => {
            line.supplies.forEach(sup => {
                const key = String(sup.id);
                const cur = supplyMap.get(key) || { code: sup.code, issued: 0 };
                cur.issued += Number(sup.issued);
                supplyMap.set(key, cur);
            });
        });
        const supplyEntries = [...supplyMap.values()];
        const supplySection = supplyEntries.length
            ? `<hr class="div">
               <p class="sec-lbl">Kelengkapan</p>
               <table>
                   <thead><tr><th>#</th><th>Kode</th><th class="r">Qty</th></tr></thead>
                   <tbody>
                       ${supplyEntries.map((s, i) =>
                           `<tr>
                               <td>${i + 1}</td>
                               <td>${esc(s.code)}</td>
                               <td class="r">${Number(s.issued).toLocaleString('id-ID')}</td>
                           </tr>`
                       ).join('')}
                   </tbody>
               </table>`
            : '';

        /* ── bangun HTML popup ── */
        const html = `<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Preview Slip</title>
<style id="ps">@media print { @page { size: 50mm auto; margin: 2mm 3mm; } }</style>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Courier New',Courier,monospace;background:#f3f4f6}
.wrap{max-width:420px;margin:0 auto;padding:1rem}
.bar{display:flex;justify-content:space-between;align-items:center;gap:.5rem;
     background:#fff;border-radius:8px;padding:.6rem .8rem;
     box-shadow:0 1px 4px rgba(0,0,0,.12);margin-bottom:1rem;
     font-family:system-ui,-apple-system,sans-serif}
.bar label{font-size:.8rem;color:#64748b;white-space:nowrap}
.bar select{border:1px solid #d1d5db;border-radius:6px;padding:.25rem .5rem;font-size:.8rem;background:#fff;cursor:pointer}
.btn{background:#2563eb;color:#fff;border:none;border-radius:6px;
     padding:.32rem .9rem;font-size:.82rem;font-weight:600;cursor:pointer;white-space:nowrap}
.btn:hover{background:#1d4ed8}
.slip{background:#fff;border:1px solid #d1d5db;border-radius:8px;
      padding:.9rem .75rem;font-size:8.5px;color:#000;line-height:1.4}
.hdr{text-align:center;margin-bottom:4px}
.title{font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.04em}
.meta{font-size:7.5px;color:#555;margin-top:2px}
.draft{display:inline-block;font-size:6px;color:#f59e0b;border:1px dashed #f59e0b;
       border-radius:3px;padding:0 3px;margin-left:4px;font-weight:700;vertical-align:middle}
.div{border:none;border-top:1px dashed #999;margin:4px 0}
.sec-lbl{font-size:7px;text-transform:uppercase;letter-spacing:.05em;color:#777;margin:5px 0 2px}
table{width:100%;border-collapse:collapse;font-size:9px}
th{font-size:7.5px;text-transform:uppercase;letter-spacing:.03em;
   border-bottom:1px solid #999;padding:1px 2px 3px;font-weight:700}
td{padding:3px 2px;vertical-align:middle;border-bottom:1px dotted #ddd;font-weight:700}
tfoot td{border-top:1px solid #999;border-bottom:none;font-weight:900;padding-top:3px;font-size:9px}
.r{text-align:right}
.ttd{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:8px}
.ttd-box{text-align:center}
.ttd-lbl{font-size:7px;color:#555;margin-bottom:2px}
.ttd-line{border-bottom:1px solid #000;height:18px;margin-bottom:2px}
.ttd-name{font-size:7px;font-weight:700}
.slip-ft{margin-top:5px;font-size:6.5px;color:#aaa;text-align:center}
@media print{.bar{display:none!important}body{background:#fff}
.wrap{max-width:none;padding:0;margin:0}.slip{border:none;border-radius:0;padding:0}}
</style>
</head>
<body>
<div class="wrap">
  <div class="bar">
    <label>Lebar kertas:</label>
    <select id="pw">
      <option value="50mm" selected>50 mm</option>
      <option value="58mm">58 mm</option>
      <option value="80mm">80 mm</option>
      <option value="100mm">100 mm</option>
    </select>
    <button class="btn" id="btnOk">&#128438;&nbsp;Simpan &amp; Cetak</button>
  </div>

  <div class="slip">
    <div class="hdr">
      <div class="title">Serah Terima Jahit <span class="draft">DRAFT</span></div>
      <div class="meta">${dateFmt} &middot; ${esc(operatorName)}</div>
    </div>
    <hr class="div">
    <p class="sec-lbl">Item</p>
    <table>
      <thead><tr><th>#</th><th>Kode</th><th class="r">Qty</th></tr></thead>
      <tbody>${itemRows}</tbody>
      <tfoot><tr>
        <td colspan="2" class="r">Total</td>
        <td class="r">${totalQty.toLocaleString('id-ID')}</td>
      </tr></tfoot>
    </table>
    ${supplySection}
    <hr class="div">
    <div class="ttd">
      <div class="ttd-box">
        <div class="ttd-lbl">Diserahkan oleh</div>
        <div class="ttd-line"></div>
        <div class="ttd-name">( _____________ )</div>
      </div>
      <div class="ttd-box">
        <div class="ttd-lbl">Diterima oleh</div>
        <div class="ttd-line"></div>
        <div class="ttd-name">${esc(operatorName)}</div>
      </div>
    </div>
    <div class="slip-ft">Preview &middot; Belum Disimpan</div>
  </div>
</div>
<script>
  var pw = document.getElementById('pw');
  var ps = document.getElementById('ps');
  pw.addEventListener('change', function() {
    ps.textContent = '@media print { @page { size: ' + this.value + ' auto; margin: 2mm 3mm; } }';
  });
  document.getElementById('btnOk').addEventListener('click', function() {
    if (window.opener) {
      window.opener.postMessage({ action: 'save_and_print', paperWidth: pw.value }, '*');
    }
    window.close();
  });
<\/script>
</body>
</html>`;

        /* ── buka popup ── */
        const popup = window.open('', '_blank', 'width=520,height=700,menubar=no,toolbar=no,location=no,status=no,resizable=yes');
        if (!popup) {
            // fallback: kalau popup diblokir, langsung submit biasa
            alert('Pop-up diblokir browser. Izinkan pop-up untuk halaman ini, atau gunakan tombol Simpan.');
            return;
        }
        popup.document.write(html);
        popup.document.close();

        /* ── terima konfirmasi dari popup ── */
        function handleMsg(e) {
            if (!e.data || e.data.action !== 'save_and_print') return;
            window.removeEventListener('message', handleMsg);
            document.getElementById('print_after_save').value = '1';
            const pwInput = document.getElementById('paper_width');
            if (pwInput) pwInput.value = e.data.paperWidth || '50mm';
            submitForm();
        }
        window.addEventListener('message', handleMsg);
    }

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
                setConfirmState(false);
                confirmBtn.disabled        = !operatorSelect.value;
            }
        }
    });

    /* ── reset saat modal ditutup ── */
    modalEl.addEventListener('hidden.bs.modal', function () {
        operatorSelect.value     = '';
        operatorHidden.value     = '';
        confirmBtn.disabled      = true;
        setConfirmState(false);
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
