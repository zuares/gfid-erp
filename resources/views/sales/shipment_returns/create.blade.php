{{-- resources/views/sales/shipment_returns/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Retur Shipment Baru')

@push('head')
<style>
    .gf-ret-page {
        max-width: 1100px;
        margin: 0 auto;
        padding: 18px 14px 40px;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    /* ── HEAD ── */
    .gf-ret-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        margin-bottom: 14px;
        padding: 18px 20px;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 58%, #f1f5f9 100%);
        box-shadow: 0 16px 42px rgba(15,23,42,.07);
        flex-wrap: wrap;
    }

    .gf-ret-head-left {
        display: flex;
        align-items: center;
        gap: 13px;
        min-width: 0;
    }

    .gf-ret-head-icon {
        width: 48px; height: 48px; flex: 0 0 48px;
        border-radius: 17px;
        display: inline-flex; align-items: center; justify-content: center;
        color: #fff;
        background: linear-gradient(135deg, #0f172a, #334155);
        box-shadow: 0 14px 28px rgba(15,23,42,.18);
        font-size: 1.28rem;
    }

    .gf-ret-eyebrow {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 5px 10px; border-radius: 999px;
        background: #f1f5f9; border: 1px solid #e2e8f0;
        color: #334155; font-size: .72rem; font-weight: 900;
        margin-bottom: 7px; text-decoration: none;
        transition: background .15s;
    }
    .gf-ret-eyebrow:hover { background: #e2e8f0; color: #1e293b; }

    .gf-ret-title {
        color: #0f172a; font-size: 1.34rem; font-weight: 950;
        letter-spacing: -.05em; line-height: 1.1; margin: 0;
    }

    .gf-ret-subtitle {
        color: #64748b; font-size: .83rem; font-weight: 600; margin-top: 3px;
    }

    .gf-ret-head-actions {
        display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end;
    }

    .gf-ret-head-actions .btn {
        border-radius: 999px; font-weight: 850; font-size: .82rem;
    }

    .gf-ret-head-actions .btn-primary {
        color: #fff;
        background: linear-gradient(135deg, #0f172a, #334155);
        border-color: transparent;
        box-shadow: 0 12px 24px rgba(15,23,42,.10);
    }

    /* ── PANEL ── */
    .gf-panel {
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        background: #ffffff;
        box-shadow: 0 16px 42px rgba(15,23,42,.07);
        overflow: hidden;
        margin-bottom: 12px;
    }

    .gf-panel-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 18px;
        border-bottom: 1px solid #f1f5f9;
        background: linear-gradient(135deg,#ffffff,#f8fafc);
    }

    .gf-panel-title {
        font-size: .72rem; font-weight: 900;
        text-transform: uppercase; letter-spacing: .05em;
        color: #334155;
    }

    .gf-panel-body { padding: 16px 18px; }

    /* ── FORM INPUTS ── */
    .gf-form-label {
        font-size: .72rem; font-weight: 900;
        color: #334155; margin-bottom: 4px;
        text-transform: uppercase; letter-spacing: .03em;
    }

    .gf-form-label.required::after { content: ' *'; color: #dc2626; }

    .form-control, .form-select {
        border-radius: 14px;
        border-color: #e2e8f0;
        font-size: .86rem;
        min-height: 38px;
        color: #0f172a;
        font-weight: 600;
    }
    .form-control:focus, .form-select:focus {
        border-color: rgba(37,99,235,.55);
        box-shadow: 0 0 0 .22rem rgba(37,99,235,.1);
    }
    .form-control::placeholder { color: #94a3b8; font-weight: 500; }

    /* ── SCAN INPUT ── */
    .gf-scan-input {
        font-size: 1.12rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        border-radius: 16px !important;
        border: 2px solid #e2e8f0 !important;
        padding: .72rem 1rem;
        min-height: 52px !important;
        background: #fafafa;
        transition: border-color .15s, box-shadow .15s;
    }
    .gf-scan-input::placeholder {
        text-transform: none; letter-spacing: normal;
        font-weight: 400; color: #94a3b8;
    }
    .gf-scan-input:focus {
        border-color: rgba(37,99,235,.7) !important;
        box-shadow: 0 0 0 .22rem rgba(37,99,235,.12) !important;
        background: #fff;
    }

    /* ── TABLE ── */
    .gf-clean-table { width: 100%; border-collapse: collapse; }

    .gf-clean-table thead th {
        background: linear-gradient(135deg,#f8fafc,#f1f5f9);
        padding: 9px 14px;
        font-size: .68rem; font-weight: 900;
        text-transform: uppercase; letter-spacing: .06em;
        color: #64748b;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }

    .gf-clean-table tbody td {
        padding: 9px 14px;
        border-bottom: 1px solid #f1f5f9;
        font-size: .86rem; color: #0f172a;
        vertical-align: middle;
    }

    .gf-clean-table tbody tr:last-child td { border-bottom: none; }
    .gf-clean-table tbody tr:hover td { background: #fafbff; }

    .lines-scroll-wrap {
        max-height: 36vh;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: rgba(148,163,184,.6) transparent;
    }
    .lines-scroll-wrap::-webkit-scrollbar { width: 5px; }
    .lines-scroll-wrap::-webkit-scrollbar-thumb { background: rgba(148,163,184,.6); border-radius: 999px; }

    /* ── QTY BADGE ── */
    .gf-qty-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 52px; padding: .22rem .7rem;
        border-radius: 999px;
        border: 1.5px solid #e2e8f0;
        background: #f8fafc;
        font-weight: 900; font-size: .9rem; color: #0f172a;
        letter-spacing: -.01em;
    }

    /* ── HIGHLIGHT LAST SCAN ── */
    .last-scan-row td { background: rgba(254,243,199,.85) !important; }
    .last-scan-row td:first-child { border-left: 3px solid #f97316; }

    @keyframes rowPulse {
        0%   { box-shadow: 0 0 0 0 rgba(249,115,22,.4); }
        100% { box-shadow: 0 0 0 10px rgba(249,115,22,0); }
    }
    .row-pulse { animation: rowPulse .75s ease-out 1; }

    /* ── SUMMARY PILLS ── */
    .gf-pill {
        display: inline-flex; align-items: center; gap: 5px;
        border-radius: 999px; padding: .22rem .82rem;
        font-size: .78rem; font-weight: 700;
        border: 1px solid #e2e8f0;
        background: linear-gradient(135deg,#ffffff,#f8fafc);
        color: #334155;
    }

    .gf-pill-val { font-weight: 950; color: #0f172a; }

    /* ── TOAST ── */
    .gf-toast {
        position: fixed; top: 4rem; left: 50%; transform: translateX(-50%);
        z-index: 1080; min-width: 200px; max-width: 340px;
        border-radius: 999px; padding: .45rem 1rem;
        font-size: .82rem; font-weight: 700;
        display: none; align-items: center; gap: .4rem;
        box-shadow: 0 12px 30px rgba(15,23,42,.25);
        pointer-events: none;
    }
    .gf-toast-ok  { background: #16a34a; color: #ecfdf5; }
    .gf-toast-err { background: #b91c1c; color: #fee2e2; }

    /* ── SHIPMENT STATUS BADGE ── */
    .gf-badge-found {
        display: inline-flex; align-items: center; gap: .35rem;
        border-radius: 999px; padding: .2rem .75rem;
        background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.25);
        color: #166534; font-size: .75rem; font-weight: 700;
    }
    .gf-badge-notfound {
        display: inline-flex; align-items: center; gap: .35rem;
        border-radius: 999px; padding: .2rem .75rem;
        background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.2);
        color: #991b1b; font-size: .75rem; font-weight: 700;
    }

    /* ── EMPTY STATE ── */
    .gf-empty {
        padding: 2.5rem 1rem; text-align: center;
        color: #94a3b8; font-size: .85rem; font-weight: 600;
    }
    .gf-empty i { font-size: 1.6rem; display: block; margin-bottom: .5rem; opacity: .4; }
</style>
@endpush

@section('content')
<div class="gf-ret-page">

    {{-- HEAD --}}
    <div class="gf-ret-head">
        <div class="gf-ret-head-left">
            <div class="gf-ret-head-icon">
                <i class="bi bi-arrow-return-left"></i>
            </div>
            <div>
                <a href="{{ route('sales.shipment_returns.index') }}" class="gf-ret-eyebrow">
                    <i class="bi bi-arrow-left"></i> Retur Penjualan
                </a>
                <h1 class="gf-ret-title">Retur Shipment Baru</h1>
                <div class="gf-ret-subtitle">Scan barang yang dikembalikan, lalu simpan</div>
            </div>
        </div>
        <div class="gf-ret-head-actions">
            <a href="{{ route('sales.shipment_returns.index') }}" class="btn btn-outline-secondary btn-sm">
                Batal
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-3" style="border-radius:16px; font-size:.85rem;">
            @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
        </div>
    @endif

    <form action="{{ route('sales.shipment_returns.store') }}" method="POST" id="retForm" autocomplete="off">
        @csrf
        <div id="hiddenLines"></div>

        {{-- PANEL 1 : Info Header --}}
        <div class="gf-panel">
            <div class="gf-panel-header">
                <span class="gf-panel-title">Info Retur</span>
            </div>
            <div class="gf-panel-body">
                <div class="row g-3">

                    {{-- No Pesanan --}}
                    <div class="col-md-4">
                        <label class="gf-form-label">No Pesanan</label>
                        <div class="d-flex gap-2">
                            <input type="text" id="orderNumberInput" name="order_number"
                                class="form-control"
                                placeholder="Kode shipment asal"
                                value="{{ old('order_number', $shipment?->code) }}"
                                style="text-transform:uppercase;">
                            <button type="button" id="btnLookup"
                                class="btn btn-outline-secondary btn-sm px-3"
                                style="border-radius:14px; white-space:nowrap;">
                                Cari
                            </button>
                        </div>
                        <div id="shipmentStatus" class="mt-2" style="display:none;"></div>
                        <input type="hidden" name="shipment_id" id="shipmentIdHidden"
                            value="{{ old('shipment_id', $shipment?->id) }}">
                    </div>

                    {{-- Store --}}
                    <div class="col-md-4">
                        <label class="gf-form-label required">Store</label>
                        <select name="store_id" id="storeSelect" class="form-select" required>
                            <option value="">Pilih store…</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}"
                                    @selected(old('store_id', $shipment?->store_id) == $store->id)>
                                    {{ $store->code ?? '-' }} — {{ $store->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tanggal --}}
                    <div class="col-md-2">
                        <label class="gf-form-label required">Tanggal</label>
                        <input type="date" name="date" class="form-control"
                            value="{{ old('date', now()->toDateString()) }}" required>
                    </div>

                    {{-- Alasan --}}
                    <div class="col-md-2">
                        <label class="gf-form-label">Alasan</label>
                        <input type="text" name="reason" class="form-control"
                            placeholder="cancel, salah kirim…"
                            value="{{ old('reason') }}">
                    </div>

                </div>
            </div>
        </div>

        {{-- PANEL 2 : Scan + Table --}}
        <div class="gf-panel">
            <div class="gf-panel-header">
                <span class="gf-panel-title">Scan Barang</span>
                <div class="d-flex gap-2 align-items-center">
                    <span class="gf-pill">
                        Baris <span class="gf-pill-val ms-1" id="sumLines">0</span>
                    </span>
                    <span class="gf-pill">
                        Total qty <span class="gf-pill-val ms-1" id="sumQty">0</span>
                    </span>
                </div>
            </div>
            <div class="gf-panel-body">

                {{-- Scan input --}}
                <div class="mb-3">
                    <input type="text" id="scanInput"
                        class="form-control gf-scan-input"
                        placeholder="Scan kode barcode / ketik kode barang, Enter…"
                        tabindex="1">
                </div>

                {{-- Table --}}
                <div class="lines-scroll-wrap">
                    <table class="gf-clean-table">
                        <thead>
                            <tr>
                                <th style="width:36px;">#</th>
                                <th style="width:130px;">Kode</th>
                                <th>Nama Barang</th>
                                <th style="width:100px; text-align:right;">Qty</th>
                                <th style="width:44px;"></th>
                            </tr>
                        </thead>
                        <tbody id="linesTbody">
                            <tr class="no-lines-row">
                                <td colspan="5">
                                    <div class="gf-empty">
                                        <i class="bi bi-box-seam"></i>
                                        Belum ada item — mulai scan di atas
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        {{-- FOOTER --}}
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('sales.shipment_returns.index') }}"
                class="btn btn-outline-secondary btn-sm" style="border-radius:999px;">
                ← Kembali
            </a>
            <button type="submit" class="btn btn-primary btn-sm" id="btnSubmit"
                disabled style="border-radius:999px; padding:.45rem 1.3rem; font-weight:850;
                background:linear-gradient(135deg,#0f172a,#334155); border:none;
                box-shadow:0 12px 24px rgba(15,23,42,.15);">
                <i class="bi bi-check2-circle me-1"></i> Simpan Retur
            </button>
        </div>

    </form>
</div>

<div id="gfToast" class="gf-toast"></div>
@endsection

@push('scripts')
<script>
(function () {
    const lines       = {}; // item_id → {item_id, code, name, qty}
    const scanInput   = document.getElementById('scanInput');
    const tbody       = document.getElementById('linesTbody');
    const hiddenLines = document.getElementById('hiddenLines');
    const sumLines    = document.getElementById('sumLines');
    const sumQty      = document.getElementById('sumQty');
    const btnSubmit   = document.getElementById('btnSubmit');
    const toastEl     = document.getElementById('gfToast');
    const orderInput  = document.getElementById('orderNumberInput');
    const shipIdHid   = document.getElementById('shipmentIdHidden');
    const statusDiv   = document.getElementById('shipmentStatus');
    const storeSelect = document.getElementById('storeSelect');

    /* ── audio ── */
    function beep(freq, dur = .14, vol = .18) {
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            const ctx = new Ctx(), osc = ctx.createOscillator(), g = ctx.createGain();
            osc.type = 'sine'; osc.frequency.value = freq;
            osc.connect(g); g.connect(ctx.destination);
            g.gain.setValueAtTime(vol, ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(.001, ctx.currentTime + dur);
            osc.start(); osc.stop(ctx.currentTime + dur);
        } catch(e) {}
    }
    const beepOk  = () => beep(1046);
    const beepErr = () => beep(220, .18, .22);

    /* ── toast ── */
    let toastT = null;
    function toast(type, msg) {
        toastEl.className = 'gf-toast ' + (type === 'ok' ? 'gf-toast-ok' : 'gf-toast-err');
        toastEl.textContent = msg;
        toastEl.style.display = 'flex'; toastEl.style.opacity = '1';
        clearTimeout(toastT);
        toastT = setTimeout(() => {
            toastEl.style.transition = 'opacity .3s';
            toastEl.style.opacity = '0';
            setTimeout(() => { toastEl.style.display = 'none'; toastEl.style.transition = ''; }, 320);
        }, 1500);
    }

    /* ── helpers ── */
    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function updateSummary() {
        const keys = Object.keys(lines);
        const total = keys.reduce((s, k) => s + lines[k].qty, 0);
        sumLines.textContent = keys.length;
        sumQty.textContent   = total;
        btnSubmit.disabled   = keys.length === 0;
    }

    function rebuildHidden() {
        hiddenLines.innerHTML = '';
        let i = 0;
        for (const k of Object.keys(lines)) {
            const l = lines[k];
            hiddenLines.innerHTML +=
                `<input type="hidden" name="lines[${i}][item_id]" value="${l.item_id}">` +
                `<input type="hidden" name="lines[${i}][qty]"     value="${l.qty}">`;
            i++;
        }
    }

    function renumber() {
        let n = 1;
        tbody.querySelectorAll('tr[data-item-id]').forEach(r => {
            const c = r.querySelector('.row-num'); if (c) c.textContent = n++;
        });
    }

    function highlight(itemId) {
        tbody.querySelectorAll('.last-scan-row').forEach(r => r.classList.remove('last-scan-row'));
        const row = tbody.querySelector(`tr[data-item-id="${itemId}"]`);
        if (!row) return;
        row.classList.add('last-scan-row', 'row-pulse');
        setTimeout(() => row.classList.remove('row-pulse'), 800);
        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /* ── upsert row ── */
    function upsertRow(item) {
        const key = String(item.id);
        const isNew = !lines[key];

        if (isNew) lines[key] = { item_id: item.id, code: item.code, name: item.name ?? '', qty: 1 };
        else        lines[key].qty += 1;

        const empty = tbody.querySelector('.no-lines-row');
        if (empty) empty.remove();

        let row = tbody.querySelector(`tr[data-item-id="${key}"]`);

        if (!row) {
            row = document.createElement('tr');
            row.setAttribute('data-item-id', key);
            row.innerHTML =
                `<td class="text-muted small row-num"></td>` +
                `<td><span style="font-weight:700;font-family:monospace;font-size:.88rem;">${esc(item.code)}</span></td>` +
                `<td style="color:#475569;">${esc(item.name ?? '')}</td>` +
                `<td style="text-align:right;"><span class="gf-qty-badge" id="qb-${key}">1</span></td>` +
                `<td style="text-align:center;">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-del"
                        data-key="${key}"
                        style="border-radius:8px;padding:2px 8px;font-size:.78rem;">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>`;

            row.querySelector('.btn-del').addEventListener('click', function () {
                const k = this.dataset.key;
                delete lines[k];
                row.remove();
                if (!Object.keys(lines).length) {
                    tbody.innerHTML =
                        `<tr class="no-lines-row"><td colspan="5">
                            <div class="gf-empty">
                                <i class="bi bi-box-seam"></i>
                                Belum ada item — mulai scan di atas
                            </div>
                        </td></tr>`;
                }
                renumber(); rebuildHidden(); updateSummary();
            });

            tbody.appendChild(row);
        } else {
            const qb = row.querySelector(`#qb-${key}`);
            if (qb) qb.textContent = lines[key].qty;
        }

        renumber(); rebuildHidden(); updateSummary(); highlight(key);
    }

    /* ── scan ── */
    function doScan(code) {
        code = code.trim().toUpperCase();
        if (!code) return;

        fetch(`/api/v1/items/suggest?q=${encodeURIComponent(code)}&type=finished_good&limit=5`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            const items = data.data ?? data ?? [];
            const found = items.find(i => (i.code ?? '').toUpperCase() === code);
            if (found) {
                beepOk();
                upsertRow(found);
                const isNew = lines[String(found.id)].qty === 1;
                toast('ok', isNew ? `+1  ${found.code}` : `+1  ${found.code}  (total ${lines[String(found.id)].qty})`);
            } else {
                beepErr();
                toast('err', `"${code}" tidak ditemukan`);
            }
        })
        .catch(() => { beepErr(); toast('err', 'Gagal lookup item.'); });
    }

    scanInput.addEventListener('input', function () { this.value = this.value.toUpperCase(); });
    scanInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const v = this.value.trim();
            if (v) { doScan(v); this.value = ''; }
            this.focus();
        }
    });

    window.addEventListener('load', () => scanInput.focus());

    /* ── shipment lookup ── */
    function lookupShipment() {
        const code = orderInput.value.trim().toUpperCase();
        orderInput.value = code;
        if (!code) { shipIdHid.value = ''; statusDiv.style.display = 'none'; return; }

        fetch(`{{ route('sales.api.shipments.lookup') }}?code=${encodeURIComponent(code)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data?.id) {
                shipIdHid.value = data.id;
                statusDiv.innerHTML =
                    `<span class="gf-badge-found"><i class="bi bi-check-circle-fill"></i>
                    ${esc(data.code)} — ${esc(data.store_code ?? '')} ${esc(data.store_name ?? '')}</span>`;
                statusDiv.style.display = '';
                if (!storeSelect.value && data.store_id) storeSelect.value = data.store_id;
            } else {
                shipIdHid.value = '';
                statusDiv.innerHTML =
                    `<span class="gf-badge-notfound"><i class="bi bi-exclamation-circle"></i>
                    Tidak ditemukan</span>`;
                statusDiv.style.display = '';
            }
        })
        .catch(() => { shipIdHid.value = ''; statusDiv.style.display = 'none'; });
    }

    document.getElementById('btnLookup').addEventListener('click', lookupShipment);
    orderInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); lookupShipment(); }
    });

    @if ($shipment)
    shipIdHid.value = '{{ $shipment->id }}';
    statusDiv.innerHTML =
        `<span class="gf-badge-found"><i class="bi bi-check-circle-fill"></i>
        Terhubung ke: {{ $shipment->code }}</span>`;
    statusDiv.style.display = '';
    @endif

    updateSummary();
})();
</script>
@endpush
