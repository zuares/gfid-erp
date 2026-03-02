@extends('layouts.app')
@section('title', 'RTS • Buat Dadakan')

@push('head')
<style>
    :root{
        --r:14px;
        --b: rgba(148, 163, 184, .22);
        --shadow: 0 10px 26px rgba(15,23,42,.08), 0 0 0 1px rgba(15,23,42,.03);
        --line: rgba(148, 163, 184, .28);
    }

    .page-wrap{ max-width:1100px; margin-inline:auto; padding:.85rem .85rem 4.5rem; }

    body[data-theme="light"] .page-wrap{
        background: radial-gradient(circle at top left,
            rgba(16,185,129,.10) 0,
            rgba(240,253,250,.34) 18%,
            #f9fafb 55%);
    }

    .card{
        background: var(--card);
        border-radius: var(--r);
        border: 1px solid var(--b);
        box-shadow: var(--shadow);
    }
    .card-section{ padding:.9rem .95rem; }

    .hdr{
        display:flex;
        justify-content:space-between;
        gap:.75rem;
        flex-wrap:wrap;
        align-items:center;
    }
    .hdr h1{ font-size:1.05rem; font-weight:900; margin:0; letter-spacing:-.01em; }

    .lbl{
        font-size:.68rem;
        text-transform:uppercase;
        letter-spacing:.10em;
        font-weight:900;
        color: var(--muted);
    }

    input[type="date"], input[type="number"], input[type="text"], select, textarea{
        width:100%;
        border-radius:12px;
        border:1px solid rgba(148,163,184,.35);
        background:transparent;
        color:inherit;
        padding:.55rem .6rem;
        outline:none;
    }

    body[data-theme="light"] input[type="date"],
    body[data-theme="light"] input[type="number"],
    body[data-theme="light"] input[type="text"],
    body[data-theme="light"] select,
    body[data-theme="light"] textarea{
        background: rgba(255,255,255,.78);
    }

    textarea{ min-height:72px; resize:vertical; }

    .btns{ display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; }

    .btnx{
        border-radius:999px;
        border:1px solid rgba(148,163,184,.28);
        padding:.45rem .85rem;
        background:transparent;
        color:inherit;
        text-decoration:none;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        gap:.45rem;
        white-space:nowrap;
    }
    .btnx:hover{
        border-color: rgba(45,212,191,.55);
        background: rgba(45,212,191,.10);
    }
    .btnx-primary{
        background: rgba(16,185,129,.90);
        border-color: rgba(16,185,129,.45);
        color:#fff;
        font-weight:800;
    }
    body[data-theme="dark"] .btnx-primary{
        background: rgba(45,212,191,.22);
        border-color: rgba(45,212,191,.50);
        color:#e5e7eb;
    }

    .table-wrap{
        overflow:auto;
        border-radius: var(--r);
        border:1px solid var(--b);
    }
    table{ width:100%; border-collapse:collapse; min-width:980px; }
    thead th{
        font-size:.72rem;
        text-transform:uppercase;
        letter-spacing:.10em;
        color: var(--muted);
        font-weight:900;
        background: rgba(148,163,184,.06);
        border-bottom:1px solid var(--b) !important;
        padding:.6rem .65rem;
        position:sticky;
        top:0;
        z-index:2;
        white-space:nowrap;
    }
    tbody td{
        padding:.55rem .65rem;
        border-color: rgba(148,163,184,.16) !important;
        vertical-align:top;
    }

    .mono{ font-variant-numeric:tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"; }

    .qty-input{ font-weight:900; font-size:.9rem; text-align:center; }

    .btn-del{
        border-radius:12px;
        border:1px solid rgba(239,68,68,.35);
        background: rgba(239,68,68,.08);
        padding:.35rem .6rem;
        cursor:pointer;
    }
    .btn-del:hover{
        border-color: rgba(239,68,68,.60);
        background: rgba(239,68,68,.12);
    }

    .mode-box{ display:none; }
    .mode-box.active{ display:block; }

    @media(max-width: 900px){
        table{ min-width:900px; }
    }
</style>
@endpush

@section('content')
@php
    // ✅ default mode: auto_sr (Belum Sewing Return / WIP-SEW)
    $modeOld = old('mode', 'auto_sr');
@endphp

<div class="page-wrap">

    <div class="card mb-2">
        <div class="card-section">
            <div class="hdr">
                <div>
                    <h1>RTS • Dadakan</h1>
                </div>
                <div class="btns">
                    <a class="btnx" href="{{ route('rts.direct-receives.index') }}">← List</a>
                </div>
            </div>
        </div>
    </div>

    <form id="direct-receive-form" method="POST" action="{{ route('rts.direct-receives.store') }}">
        @csrf

        {{-- Header --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="row g-2 align-items-end">

                    <div class="col-md-4 col-12">
                        <div class="lbl mb-1">Mode</div>
                        <select name="mode" id="modeSelect" class="form-select form-select-sm">
                            <option value="auto_sr" @selected($modeOld === 'auto_sr')>Belum Sewing Return (WIP-SEW → RTS)</option>
                            <option value="normal" @selected($modeOld === 'normal')>Normal (WIP-FIN → RTS)</option>
                        </select>
                        @error('mode')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2 col-6">
                        <div class="lbl mb-1">Tanggal</div>
                        <input type="date" name="date" value="{{ old('date', $date) }}" class="form-control form-control-sm">
                        @error('date')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2 col-6">
                        <div class="lbl mb-1">From</div>
                        <input id="fromLabel" class="form-control form-control-sm" value="{{ $fromWarehouse->code }}" disabled>
                        <input type="hidden" id="fromWarehouseId" name="from_warehouse_id" value="{{ old('from_warehouse_id', $fromWarehouse->id) }}">
                        @error('from_warehouse_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2 col-6">
                        <div class="lbl mb-1">To</div>
                        <input class="form-control form-control-sm" value="{{ $toWarehouse->code }}" disabled>
                        <input type="hidden" name="to_warehouse_id" value="{{ $toWarehouse->id }}">
                        @error('to_warehouse_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2 col-6">
                        <div class="lbl mb-1">Operator</div>
                        <select name="operator_id" id="operatorSelect" class="form-select form-select-sm">
                            <option value="">- pilih operator -</option>
                            @foreach ($operators as $op)
                                @php
                                    $opRole = strtolower((string)($op->role ?? '')); // ✅ aman kalau kolom role tidak ada -> ''
                                @endphp
                                <option value="{{ $op->id }}"
                                    data-role="{{ $opRole }}"
                                    @selected(old('operator_id') == $op->id)>
                                    {{ $op->code }} — {{ $op->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('operator_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mt-3">
                    <div class="lbl mb-1">Catatan</div>
                    <textarea name="notes" class="form-control form-control-sm">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- MODE NORMAL --}}
        <div id="mode-normal" class="card mb-2 mode-box">
            <div class="card-section">
                <div class="hdr">
                    <div class="lbl">Line Items</div>
                    <button type="button" class="btnx" id="btnAddNormal">+ Baris</button>
                </div>

                <div class="table-wrap mt-2">
                    <table class="table table-sm align-middle mono">
                        <thead>
                            <tr>
                                <th style="width:52px" class="text-center">#</th>
                                <th>Item</th>
                                <th style="width:160px" class="text-center">Qty</th>
                                <th>Catatan</th>
                                <th style="width:120px" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyNormal"></tbody>
                    </table>
                </div>

                <div class="btns mt-3 justify-content-end">
                    <a class="btnx" href="{{ route('rts.direct-receives.index') }}">Batal</a>
                    <button type="button" class="btnx btnx-primary" data-bs-toggle="modal" data-bs-target="#confirmModal" data-submit-mode="normal">
                        Simpan
                    </button>
                </div>
            </div>
        </div>

        {{-- MODE AUTO SR --}}
        <div id="mode-auto" class="card mb-2 mode-box">
            <div class="card-section">
                <div class="hdr">
                    <div class="lbl">Auto-SR</div>
                    <div class="btns">
                        <button type="button" class="btnx" id="btnReloadAuto">↻ Reload</button>
                    </div>
                </div>

                <div class="table-wrap mt-2">
                    <table class="table table-sm align-middle mono">
                        <thead>
                            <tr>
                                <th style="width:52px" class="text-center">#</th>
                                <th style="width:420px">Item / Pickup</th>
                                <th style="width:140px" class="text-center">Belum</th>
                                <th style="width:140px" class="text-center">Stok WIP</th>
                                <th style="width:130px" class="text-center">OK</th>
                                <th style="width:130px" class="text-center">RJ</th>
                                <th style="width:260px">Catatan</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyAuto">
                            <tr>
                                <td colspan="7" style="padding:1rem;opacity:.75;text-align:center;">
                                    -
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="btns mt-3 justify-content-end">
                    <a class="btnx" href="{{ route('rts.direct-receives.index') }}">Batal</a>
                    <button type="button" class="btnx btnx-primary" data-bs-toggle="modal" data-bs-target="#confirmModal" data-submit-mode="auto_sr">
                        Simpan
                    </button>
                </div>
            </div>
        </div>

        {{-- CONFIRM MODAL --}}
        <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius:16px; border:1px solid rgba(148,163,184,.22);">
                    <div class="modal-header">
                        <h5 class="modal-title" style="font-weight:900;">Konfirmasi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div style="font-size:.95rem;">
                            Simpan transaksi ini?
                        </div>
                        <div id="modalErr" class="mt-2 d-none" style="color:#b91c1c; font-size:.9rem;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btnx" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btnx btnx-primary" id="btnConfirmSubmit">Ya, Simpan</button>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const form = document.getElementById('direct-receive-form');

    const modeSelect = document.getElementById('modeSelect');
    const boxNormal  = document.getElementById('mode-normal');
    const boxAuto    = document.getElementById('mode-auto');

    const operatorSelect = document.getElementById('operatorSelect');
    const fromLabel = document.getElementById('fromLabel');
    const fromWarehouseId = document.getElementById('fromWarehouseId');

    const WIP_FIN_ID   = {{ (int) $fromWarehouse->id }};
    const WIP_FIN_CODE = @json($fromWarehouse->code);

    const WIP_SEW_ID   = {{ (int) $wipSewWarehouse->id }};
    const WIP_SEW_CODE = @json($wipSewWarehouse->code);

    const fetchUrl = @json(route('rts.direct-receives.operator_wip'));

    const items = @json($items->map(fn($i) => ['id' => $i->id, 'code' => $i->code, 'name' => $i->name])->values());

    // modal
    const confirmModalEl = document.getElementById('confirmModal');
    const btnConfirmSubmit = document.getElementById('btnConfirmSubmit');
    const modalErr = document.getElementById('modalErr');
    let modalSubmitMode = null;

    function esc(s){
        return String(s ?? '')
            .replaceAll('&','&amp;')
            .replaceAll('<','&lt;')
            .replaceAll('>','&gt;')
            .replaceAll('"','&quot;')
            .replaceAll("'", "&#039;");
    }

    function parseIntSafe(v){
        const n = parseInt(String(v ?? '').replace(/[^\d]/g,''), 10);
        return isNaN(n) ? 0 : n;
    }

    function setSectionEnabled(sectionEl, enabled){
        if(!sectionEl) return;
        sectionEl.querySelectorAll('input, select, textarea, button').forEach(el => {
            el.disabled = !enabled;
            if(!enabled) el.removeAttribute('required');
        });
    }

    // ✅ Filter operator options: mode auto_sr => only data-role="sewing"
    function applyOperatorFilter(){
        const m = modeSelect.value || 'auto_sr';

        const opts = Array.from(operatorSelect.querySelectorAll('option'));
        opts.forEach(opt => {
            if(!opt.value) { opt.hidden = false; opt.disabled = false; return; }

            const role = String(opt.dataset.role || '').toLowerCase();

            if(m === 'auto_sr'){
                const isSewing = (role === 'sewing');
                opt.hidden = !isSewing;
                opt.disabled = !isSewing;
                // kalau current selected ternyata bukan sewing, reset
                if(!isSewing && operatorSelect.value === opt.value){
                    operatorSelect.value = '';
                }
            }else{
                opt.hidden = false;
                opt.disabled = false;
            }
        });
    }

    function applyMode(){
        const m = modeSelect.value || 'auto_sr';

        boxNormal.classList.toggle('active', m === 'normal');
        boxAuto.classList.toggle('active', m === 'auto_sr');

        setSectionEnabled(boxNormal, m === 'normal');
        setSectionEnabled(boxAuto, m === 'auto_sr');

        if(m === 'auto_sr'){
            fromLabel.value = WIP_SEW_CODE;
            fromWarehouseId.value = String(WIP_SEW_ID);
        }else{
            fromLabel.value = WIP_FIN_CODE;
            fromWarehouseId.value = String(WIP_FIN_ID);
        }

        applyOperatorFilter();

        if(m === 'auto_sr'){
            loadOperatorWip();
        }
    }

    modeSelect.addEventListener('change', applyMode);

    // =========================
    // NORMAL MODE
    // =========================
    const tbodyNormal = document.getElementById('tbodyNormal');
    const btnAddNormal = document.getElementById('btnAddNormal');

    function optsItems(sel){
        const o = ['<option value="">Pilih item...</option>'];
        for(const it of items){
            const s = String(sel || '') === String(it.id) ? 'selected' : '';
            o.push(`<option value="${it.id}" ${s}>${esc(it.code)} — ${esc(it.name)}</option>`);
        }
        return o.join('');
    }

    function renumberNormal(){
        Array.from(tbodyNormal.children).forEach((tr, i) => {
            tr.querySelector('[data-no]').textContent = String(i+1);
            tr.querySelector('select').name = `lines[${i}][item_id]`;
            tr.querySelector('input.qty').name = `lines[${i}][qty]`;
            tr.querySelector('input.note').name = `lines[${i}][notes]`;
        });
    }

    function refreshDisabledNormal(){
        const selects = Array.from(tbodyNormal.querySelectorAll('select'));
        const selected = new Set(selects.map(s => s.value).filter(Boolean));
        selects.forEach(s => {
            const cur = s.value;
            Array.from(s.options).forEach(o => {
                if(!o.value) return;
                o.disabled = (o.value !== cur) && selected.has(o.value);
            });
        });
    }

    function addRowNormal(init = {}){
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center" data-no style="opacity:.75;">1</td>
            <td><select class="form-select form-select-sm" required>${optsItems(init.item_id)}</select></td>
            <td class="text-center">
                <input class="form-control form-control-sm qty qty-input" type="number" step="0.01" min="0" value="${init.qty ?? ''}" placeholder="0">
            </td>
            <td><input class="form-control form-control-sm note" type="text" value="${esc(init.notes ?? '')}" placeholder="-"></td>
            <td class="text-end"><button type="button" class="btn-del">Hapus</button></td>
        `;
        tr.querySelector('.btn-del').addEventListener('click', () => {
            tr.remove();
            renumberNormal();
            refreshDisabledNormal();
        });
        tr.querySelector('select').addEventListener('change', refreshDisabledNormal);

        tbodyNormal.appendChild(tr);
        renumberNormal();
        refreshDisabledNormal();
    }

    btnAddNormal.addEventListener('click', () => addRowNormal({}));
    addRowNormal({});

    // =========================
    // AUTO SR MODE
    // =========================
    const tbodyAuto = document.getElementById('tbodyAuto');
    const btnReloadAuto = document.getElementById('btnReloadAuto');

    function renumberAutoRows(){
        const rows = Array.from(tbodyAuto.querySelectorAll('tr[data-row="1"]'));
        rows.forEach((tr, i) => {
            tr.querySelector('[data-no]').textContent = String(i+1);
            tr.querySelector('input.h_pl').name = `lines[${i}][sewing_pickup_line_id]`;
            tr.querySelector('input.h_item').name = `lines[${i}][item_id]`;
            tr.querySelector('input.ok').name = `lines[${i}][qty_ok]`;
            tr.querySelector('input.rj').name = `lines[${i}][qty_reject]`;
            tr.querySelector('input.note').name = `lines[${i}][notes]`;
        });
    }

    async function loadOperatorWip(){
        const m = modeSelect.value || 'auto_sr';
        if(m !== 'auto_sr') return;

        const opId = operatorSelect.value;
        if(!opId){
            tbodyAuto.innerHTML = `<tr><td colspan="7" style="padding:1rem;opacity:.75;text-align:center;">-</td></tr>`;
            return;
        }

        tbodyAuto.innerHTML = `<tr><td colspan="7" style="padding:1rem;opacity:.75;text-align:center;">Memuat...</td></tr>`;

        const res = await fetch(`${fetchUrl}?operator_id=${encodeURIComponent(opId)}`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });

        const json = await res.json();
        const rows = json.rows || [];

        if(!rows.length){
            tbodyAuto.innerHTML = `<tr><td colspan="7" style="padding:1rem;opacity:.75;text-align:center;">Kosong</td></tr>`;
            return;
        }

        tbodyAuto.innerHTML = '';

        rows.forEach((r) => {
            const tr = document.createElement('tr');
            tr.dataset.row = "1";

            const remaining = parseIntSafe(r.remaining);
            const wipStock = parseIntSafe(r.wip_stock);
            const limit = Math.min(remaining, wipStock);

            tr.innerHTML = `
                <td class="text-center" data-no style="opacity:.75;">1</td>

                <td>
                    <div style="font-weight:900">${esc(r.item_code)}</div>
                    <div style="opacity:.75;font-size:.82rem">${esc(r.item_name || '')}</div>
                    <div style="opacity:.72;font-size:.78rem;margin-top:.15rem">
                        <span class="mono">${esc(r.pickup_code || '-')}</span> • ${esc(r.pickup_date || '-')}
                    </div>
                    <input class="h_pl" type="hidden" value="${esc(r.pickup_line_id)}">
                    <input class="h_item" type="hidden" value="${esc(r.item_id)}">
                </td>

                <td class="text-center"><span class="mono">${remaining}</span></td>
                <td class="text-center"><span class="mono">${wipStock}</span></td>

                <td class="text-center">
                    <input class="form-control form-control-sm ok qty-input" type="number" step="1" min="0" inputmode="numeric" pattern="\\d*" placeholder="0">
                </td>

                <td class="text-center">
                    <input class="form-control form-control-sm rj qty-input" type="number" step="1" min="0" inputmode="numeric" pattern="\\d*" placeholder="0">
                </td>

                <td>
                    <div style="display:flex;gap:.5rem;align-items:center;">
                        <input class="form-control form-control-sm note" type="text" placeholder="-">
                        <button type="button" class="btn-del">Hapus</button>
                    </div>
                </td>
            `;

            const okEl = tr.querySelector('input.ok');
            const rjEl = tr.querySelector('input.rj');

            function clampInt(){
                let ok = parseIntSafe(okEl.value);
                let rj = parseIntSafe(rjEl.value);

                if(ok < 0) ok = 0;
                if(rj < 0) rj = 0;

                const total = ok + rj;
                if(total > limit){
                    const diff = total - limit;
                    if(ok >= diff) ok -= diff;
                    else{
                        rj = Math.max(0, rj - (diff - ok));
                        ok = 0;
                    }
                }

                okEl.value = ok > 0 ? String(ok) : '';
                rjEl.value = rj > 0 ? String(rj) : '';
            }

            okEl.addEventListener('input', clampInt);
            rjEl.addEventListener('input', clampInt);

            tr.querySelector('.btn-del').addEventListener('click', () => {
                tr.remove();
                renumberAutoRows();
            });

            tbodyAuto.appendChild(tr);
        });

        renumberAutoRows();
    }

    operatorSelect.addEventListener('change', () => {
        if(modeSelect.value === 'auto_sr') loadOperatorWip();
    });
    btnReloadAuto.addEventListener('click', loadOperatorWip);

    // =========================
    // MODAL: decide submit mode
    // =========================
    confirmModalEl.addEventListener('show.bs.modal', function(ev){
        modalErr.classList.add('d-none');
        modalErr.textContent = '';

        const trigger = ev.relatedTarget;
        modalSubmitMode = trigger?.getAttribute('data-submit-mode') || (modeSelect.value || 'auto_sr');
    });

    // =========================
    // SUBMIT validation (with modal)
    // =========================
    form.addEventListener('submit', function(e){
        const m = modeSelect.value || 'auto_sr';
        if(modalErr){
            modalErr.classList.add('d-none');
            modalErr.textContent = '';
        }

        if(m === 'normal'){
            const rows = Array.from(tbodyNormal.querySelectorAll('tr'));
            const any = rows.some(tr => (tr.querySelector('select')?.value || '') && parseFloat(tr.querySelector('input.qty')?.value || 0) > 0);
            if(!any){
                e.preventDefault();
                modalErr.textContent = 'Minimal 1 item dengan qty > 0.';
                modalErr.classList.remove('d-none');
                return;
            }
        }else{
            const opId = operatorSelect.value;
            if(!opId){
                e.preventDefault();
                modalErr.textContent = 'Operator wajib dipilih.';
                modalErr.classList.remove('d-none');
                return;
            }

            const trs = Array.from(tbodyAuto.querySelectorAll('tr[data-row="1"]'));
            const any = trs.some(tr => {
                const ok = parseIntSafe(tr.querySelector('input.ok')?.value || 0);
                const rj = parseIntSafe(tr.querySelector('input.rj')?.value || 0);
                return (ok + rj) > 0;
            });

            if(!any){
                e.preventDefault();
                modalErr.textContent = 'Isi minimal 1 baris (OK atau RJ > 0).';
                modalErr.classList.remove('d-none');
                return;
            }

            renumberAutoRows();
        }

        // lock button
        if(btnConfirmSubmit){
            btnConfirmSubmit.disabled = true;
            btnConfirmSubmit.textContent = 'Menyimpan...';
        }
    });

    // init
    applyMode(); // default: auto_sr
});
</script>
@endpush
