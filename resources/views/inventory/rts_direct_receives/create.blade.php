@extends('layouts.app')
@section('title', 'RTS • Buat Dadakan')

@push('head')
    <style>
        :root {
            --r: 14px;
            --b: rgba(148, 163, 184, .22);
            --shadow: 0 10px 26px rgba(15, 23, 42, .08), 0 0 0 1px rgba(15, 23, 42, .03);

            --ok: rgba(22, 163, 74, 1);
            --okbg: rgba(22, 163, 74, .10);
            --rj: rgba(220, 38, 38, 1);
            --rjbg: rgba(248, 113, 113, .12);
            --warnbg: rgba(245, 158, 11, .12);

            --line: rgba(148, 163, 184, .28);
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .85rem .85rem 4.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(16, 185, 129, .10) 0,
                    rgba(240, 253, 250, .34) 18%,
                    #f9fafb 55%);
        }

        .card {
            background: var(--card);
            border-radius: var(--r);
            border: 1px solid var(--b);
            box-shadow: var(--shadow);
        }

        .card-section {
            padding: .9rem .95rem;
        }

        .hdr {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .hdr h1 {
            font-size: 1.05rem;
            font-weight: 900;
            margin: 0;
            letter-spacing: -.01em;
        }

        .sub {
            font-size: .82rem;
            color: var(--muted);
            line-height: 1.35;
            margin-top: .15rem;
        }

        .lbl {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .10em;
            font-weight: 900;
            color: var(--muted);
        }

        input[type="date"],
        input[type="number"],
        input[type="text"],
        select,
        textarea {
            width: 100%;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: transparent;
            color: inherit;
            padding: .55rem .6rem;
            outline: none;
        }

        body[data-theme="light"] input[type="date"],
        body[data-theme="light"] input[type="number"],
        body[data-theme="light"] input[type="text"],
        body[data-theme="light"] select,
        body[data-theme="light"] textarea {
            background: rgba(255, 255, 255, .78);
        }

        textarea {
            min-height: 72px;
            resize: vertical;
        }

        .btns {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn {
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .28);
            padding: .45rem .85rem;
            background: transparent;
            color: inherit;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            white-space: nowrap;
        }

        .btn:hover {
            border-color: rgba(45, 212, 191, .55);
            background: rgba(45, 212, 191, .10);
        }

        .btn-primary {
            background: rgba(16, 185, 129, .90);
            border-color: rgba(16, 185, 129, .45);
            color: #fff;
            font-weight: 800;
        }

        body[data-theme="dark"] .btn-primary {
            background: rgba(45, 212, 191, .22);
            border-color: rgba(45, 212, 191, .50);
            color: #e5e7eb;
        }

        .toastish {
            border-radius: var(--r);
            border: 1px solid rgba(245, 158, 11, .30);
            background: var(--warnbg);
            color: rgba(146, 64, 14, 1);
            padding: .45rem .65rem;
            font-size: .85rem;
        }

        .table-wrap {
            overflow: auto;
            border-radius: var(--r);
            border: 1px solid var(--b);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 980px;
        }

        thead th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .10em;
            color: var(--muted);
            font-weight: 900;
            background: rgba(148, 163, 184, .06);
            border-bottom: 1px solid var(--b) !important;
            padding: .6rem .65rem;
            position: sticky;
            top: 0;
            z-index: 2;
            white-space: nowrap;
        }

        tbody td {
            padding: .55rem .65rem;
            border-color: rgba(148, 163, 184, .16) !important;
            vertical-align: top;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .pill {
            border-radius: 999px;
            padding: .18rem .65rem;
            font-size: .72rem;
            font-weight: 900;
            background: rgba(148, 163, 184, .10);
            border: 1px solid rgba(148, 163, 184, .18);
            display: inline-flex;
            gap: .35rem;
            align-items: center;
            white-space: nowrap;
        }

        .pill.ok {
            background: var(--okbg);
            border-color: rgba(22, 163, 74, .26);
            color: #166534;
        }

        .pill.rj {
            background: var(--rjbg);
            border-color: rgba(248, 113, 113, .22);
            color: #b91c1c;
        }

        .qty-input {
            font-weight: 900;
            font-size: .9rem;
            text-align: center;
        }

        .btn-del {
            border-radius: 12px;
            border: 1px solid rgba(239, 68, 68, .35);
            background: rgba(239, 68, 68, .08);
            padding: .35rem .6rem;
            cursor: pointer;
        }

        .btn-del:hover {
            border-color: rgba(239, 68, 68, .60);
            background: rgba(239, 68, 68, .12);
        }

        .mode-box {
            display: none;
        }

        .mode-box.active {
            display: block;
        }

        .muted-hint {
            color: var(--muted);
            font-size: .8rem;
        }

        @media(max-width: 900px) {
            table {
                min-width: 900px;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $modeOld = old('mode', 'normal');
    @endphp

    <div class="page-wrap">

        <div class="card mb-2">
            <div class="card-section">
                <div class="hdr">
                    <div>
                        <h1>RTS • Dadakan</h1>
                        <div class="sub">
                            <span class="pill">Normal</span> ambil dari <b>WIP-FIN</b>.
                            <span class="pill ok">Auto-SR</span> pilih operator → tampil WIP-SEW outstanding →
                            <b>WIP-SEW → WIP-FIN → RTS</b>.
                        </div>
                    </div>
                    <div class="btns">
                        <a class="btn" href="{{ route('rts.direct-receives.index') }}">← List</a>
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
                                <option value="normal" @selected($modeOld === 'normal')>Normal (WIP-FIN → RTS)</option>
                                <option value="auto_sr" @selected($modeOld === 'auto_sr')>Belum Sewing Return (Auto-SR + Direct
                                    RTS)</option>
                            </select>
                            @error('mode')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="muted-hint mt-1">Auto-SR wajib pilih operator.</div>
                        </div>

                        <div class="col-md-2 col-6">
                            <div class="lbl mb-1">Tanggal</div>
                            <input type="date" name="date" value="{{ old('date', $date) }}"
                                class="form-control form-control-sm">
                            @error('date')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-2 col-6">
                            <div class="lbl mb-1">From</div>
                            <input id="fromLabel" class="form-control form-control-sm" value="{{ $fromWarehouse->code }}"
                                disabled>
                            <input type="hidden" id="fromWarehouseId" name="from_warehouse_id"
                                value="{{ old('from_warehouse_id', $fromWarehouse->id) }}">
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
                                    <option value="{{ $op->id }}" @selected(old('operator_id') == $op->id)>
                                        {{ $op->code }} — {{ $op->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('operator_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="muted-hint mt-1" id="operatorHint">
                                Mode normal: operator opsional. Auto-SR: operator wajib.
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="lbl mb-1">Catatan</div>
                        <textarea name="notes" class="form-control form-control-sm">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="client-error-box" class="toastish d-none mt-2">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <span id="client-error-text"></span>
                    </div>
                </div>
            </div>

            {{-- MODE NORMAL --}}
            <div id="mode-normal" class="card mb-2 mode-box">
                <div class="card-section">
                    <div class="hdr">
                        <div>
                            <div class="lbl">Line Items (Normal)</div>
                            <div class="sub">Isi item & qty untuk pindah <b>WIP-FIN → RTS</b>.</div>
                        </div>
                        <button type="button" class="btn" id="btnAddNormal">+ Baris</button>
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
                        <a class="btn" href="{{ route('rts.direct-receives.index') }}">Batal</a>
                        <button class="btn btn-primary" type="submit" id="btnSubmitNormal">Simpan</button>
                    </div>
                </div>
            </div>

            {{-- MODE AUTO SR --}}
            <div id="mode-auto" class="card mb-2 mode-box">
                <div class="card-section">
                    <div class="hdr">
                        <div>
                            <div class="lbl">Auto-SR + Direct RTS</div>
                            <div class="sub">
                                Pilih operator → sistem tampilkan pickup lines outstanding.
                                Isi <b>OK</b> & <b>RJ</b> (tanpa desimal).
                            </div>
                        </div>

                        <div class="btns">
                            <button type="button" class="btn" id="btnReloadAuto">↻ Reload</button>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="pill"><i class="bi bi-list-check"></i> <span id="summary-row-filled">0</span>
                            baris</span>
                        <span class="pill ok"><i class="bi bi-check-circle"></i> OK: <span
                                id="summary-ok">0</span></span>
                        <span class="pill rj"><i class="bi bi-x-circle"></i> RJ: <span id="summary-rj">0</span></span>
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
                                        Pilih operator untuk memuat daftar outstanding.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="btns mt-3 justify-content-end">
                        <a class="btn" href="{{ route('rts.direct-receives.index') }}">Batal</a>
                        <button class="btn btn-primary" id="btnSubmitAuto" type="submit">Simpan</button>
                    </div>
                </div>
            </div>

        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('direct-receive-form');

            const modeSelect = document.getElementById('modeSelect');
            const boxNormal = document.getElementById('mode-normal');
            const boxAuto = document.getElementById('mode-auto');

            const operatorSelect = document.getElementById('operatorSelect');
            const operatorHint = document.getElementById('operatorHint');

            const fromLabel = document.getElementById('fromLabel');
            const fromWarehouseId = document.getElementById('fromWarehouseId');

            const WIP_FIN_ID = {{ (int) $fromWarehouse->id }};
            const WIP_FIN_CODE = @json($fromWarehouse->code);

            const WIP_SEW_ID = {{ (int) $wipSewWarehouse->id }};
            const WIP_SEW_CODE = @json($wipSewWarehouse->code);

            const fetchUrl = @json(route('rts.direct-receives.operator_wip'));

            const items = @json($items->map(fn($i) => ['id' => $i->id, 'code' => $i->code, 'name' => $i->name])->values());

            const errBox = document.getElementById('client-error-box');
            const errText = document.getElementById('client-error-text');

            function showErr(msg) {
                if (!errBox || !errText) return;
                errText.textContent = msg;
                errBox.classList.remove('d-none');
                clearTimeout(showErr._t);
                showErr._t = setTimeout(() => errBox.classList.add('d-none'), 2200);
            }

            function esc(s) {
                return String(s ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", "&#039;");
            }

            function parseIntSafe(v) {
                const n = parseInt(String(v ?? '').replace(/[^\d]/g, ''), 10);
                return isNaN(n) ? 0 : n;
            }

            // ✅ Fix: disable section inputs when not active (prevents "not focusable" required issue)
            function setSectionEnabled(sectionEl, enabled) {
                if (!sectionEl) return;
                sectionEl.querySelectorAll('input, select, textarea, button').forEach(el => {
                    // don't touch submit buttons outside or not in section
                    el.disabled = !enabled;
                    if (!enabled) {
                        el.removeAttribute('required');
                    }
                });
            }

            function applyMode() {
                const m = modeSelect.value || 'normal';

                boxNormal.classList.toggle('active', m === 'normal');
                boxAuto.classList.toggle('active', m === 'auto_sr');

                // ✅ Enable only the active section
                setSectionEnabled(boxNormal, m === 'normal');
                setSectionEnabled(boxAuto, m === 'auto_sr');

                if (m === 'auto_sr') {
                    fromLabel.value = WIP_SEW_CODE;
                    fromWarehouseId.value = String(WIP_SEW_ID);
                    if (operatorHint) operatorHint.textContent = 'Auto-SR: operator wajib dipilih.';
                } else {
                    fromLabel.value = WIP_FIN_CODE;
                    fromWarehouseId.value = String(WIP_FIN_ID);
                    if (operatorHint) operatorHint.textContent = 'Mode normal: operator opsional.';
                }
            }

            modeSelect.addEventListener('change', () => {
                applyMode();
                if (modeSelect.value === 'auto_sr') loadOperatorWip();
            });

            // =========================
            // NORMAL MODE
            // =========================
            const tbodyNormal = document.getElementById('tbodyNormal');
            const btnAddNormal = document.getElementById('btnAddNormal');

            function opts(sel) {
                const o = ['<option value="">Pilih item...</option>'];
                for (const it of items) {
                    const s = String(sel || '') === String(it.id) ? 'selected' : '';
                    o.push(`<option value="${it.id}" ${s}>${esc(it.code)} — ${esc(it.name)}</option>`);
                }
                return o.join('');
            }

            function renumberNormal() {
                Array.from(tbodyNormal.children).forEach((tr, i) => {
                    tr.querySelector('[data-no]').textContent = String(i + 1);
                    tr.querySelector('select').name = `lines[${i}][item_id]`;
                    tr.querySelector('input.qty').name = `lines[${i}][qty]`;
                    tr.querySelector('input.note').name = `lines[${i}][notes]`;
                });
            }

            function refreshDisabledNormal() {
                const selects = Array.from(tbodyNormal.querySelectorAll('select'));
                const selected = new Set(selects.map(s => s.value).filter(Boolean));
                selects.forEach(s => {
                    const cur = s.value;
                    Array.from(s.options).forEach(o => {
                        if (!o.value) return;
                        o.disabled = (o.value !== cur) && selected.has(o.value);
                    });
                });
            }

            function addRowNormal(init = {}) {
                const tr = document.createElement('tr');
                tr.innerHTML = `
            <td class="text-center" data-no style="opacity:.75;">1</td>
            <td><select class="form-select form-select-sm" required>${opts(init.item_id)}</select></td>
            <td class="text-center">
                <input class="form-control form-control-sm qty qty-input" type="number" step="0.01" min="0" value="${init.qty ?? ''}" placeholder="0">
            </td>
            <td><input class="form-control form-control-sm note" type="text" value="${esc(init.notes ?? '')}" placeholder="opsional"></td>
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
            addRowNormal({}); // seed 1 row

            // =========================
            // AUTO SR MODE
            // =========================
            const tbodyAuto = document.getElementById('tbodyAuto');
            const btnReloadAuto = document.getElementById('btnReloadAuto');

            const summaryFilled = document.getElementById('summary-row-filled');
            const summaryOk = document.getElementById('summary-ok');
            const summaryRj = document.getElementById('summary-rj');

            function updateSummaryAuto() {
                let filled = 0,
                    okSum = 0,
                    rjSum = 0;

                Array.from(tbodyAuto.querySelectorAll('tr[data-row="1"]')).forEach(tr => {
                    const ok = parseIntSafe(tr.querySelector('input.ok')?.value || 0);
                    const rj = parseIntSafe(tr.querySelector('input.rj')?.value || 0);
                    if (ok + rj > 0) filled++;
                    okSum += ok;
                    rjSum += rj;
                });

                if (summaryFilled) summaryFilled.textContent = String(filled);
                if (summaryOk) summaryOk.textContent = String(okSum);
                if (summaryRj) summaryRj.textContent = String(rjSum);
            }

            function renumberAutoRows() {
                const rows = Array.from(tbodyAuto.querySelectorAll('tr[data-row="1"]'));
                rows.forEach((tr, i) => {
                    tr.querySelector('[data-no]').textContent = String(i + 1);
                    const idx = i;

                    tr.querySelector('input.h_pl').name = `lines[${idx}][sewing_pickup_line_id]`;
                    tr.querySelector('input.h_item').name = `lines[${idx}][item_id]`;

                    tr.querySelector('input.ok').name = `lines[${idx}][qty_ok]`;
                    tr.querySelector('input.rj').name = `lines[${idx}][qty_reject]`;
                    tr.querySelector('input.note').name = `lines[${idx}][notes]`;
                });
            }

            async function loadOperatorWip() {
                const mode = modeSelect.value || 'normal';
                if (mode !== 'auto_sr') return;

                const opId = operatorSelect.value;
                if (!opId) {
                    tbodyAuto.innerHTML = `
                <tr><td colspan="7" style="padding:1rem;opacity:.75;text-align:center;">
                    Pilih operator untuk memuat daftar outstanding.
                </td></tr>`;
                    updateSummaryAuto();
                    return;
                }

                tbodyAuto.innerHTML = `
            <tr><td colspan="7" style="padding:1rem;opacity:.75;text-align:center;">
                Memuat data...
            </td></tr>`;

                const res = await fetch(`${fetchUrl}?operator_id=${encodeURIComponent(opId)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const json = await res.json();
                const rows = json.rows || [];

                if (!rows.length) {
                    tbodyAuto.innerHTML = `
                <tr><td colspan="7" style="padding:1rem;opacity:.75;text-align:center;">
                    Tidak ada WIP-SEW outstanding untuk operator ini.
                </td></tr>`;
                    updateSummaryAuto();
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
                        Pickup: <span class="mono">${esc(r.pickup_code || '-')}</span> • ${esc(r.pickup_date || '-')}
                    </div>

                    <input class="h_pl" type="hidden" value="${esc(r.pickup_line_id)}">
                    <input class="h_item" type="hidden" value="${esc(r.item_id)}">
                </td>

                <td class="text-center">
                    <span class="mono">${remaining}</span>
                    <div style="opacity:.65;font-size:.75rem">remaining</div>
                </td>

                <td class="text-center">
                    <span class="mono">${wipStock}</span>
                    <div style="opacity:.65;font-size:.75rem">wip</div>
                </td>

                <td class="text-center">
                    <input class="form-control form-control-sm ok qty-input"
                           type="number" step="1" min="0"
                           inputmode="numeric" pattern="\\d*"
                           placeholder="OK">
                </td>

                <td class="text-center">
                    <input class="form-control form-control-sm rj qty-input"
                           type="number" step="1" min="0"
                           inputmode="numeric" pattern="\\d*"
                           placeholder="RJ">
                </td>

                <td>
                    <div style="display:flex;gap:.5rem;align-items:center;">
                        <input class="form-control form-control-sm note" type="text" placeholder="opsional">
                        <button type="button" class="btn-del">Hapus</button>
                    </div>
                </td>
            `;

                    const okEl = tr.querySelector('input.ok');
                    const rjEl = tr.querySelector('input.rj');

                    function clampInt() {
                        let ok = parseIntSafe(okEl.value);
                        let rj = parseIntSafe(rjEl.value);

                        if (ok < 0) ok = 0;
                        if (rj < 0) rj = 0;

                        const total = ok + rj;
                        if (total > limit) {
                            const diff = total - limit;
                            if (ok >= diff) ok -= diff;
                            else {
                                rj = Math.max(0, rj - (diff - ok));
                                ok = 0;
                            }
                        }

                        okEl.value = ok > 0 ? String(ok) : '';
                        rjEl.value = rj > 0 ? String(rj) : '';
                        updateSummaryAuto();
                    }

                    okEl.addEventListener('input', clampInt);
                    rjEl.addEventListener('input', clampInt);

                    tr.querySelector('.btn-del').addEventListener('click', () => {
                        tr.remove();
                        renumberAutoRows();
                        updateSummaryAuto();
                    });

                    tbodyAuto.appendChild(tr);
                });

                renumberAutoRows();
                updateSummaryAuto();
            }

            operatorSelect.addEventListener('change', () => {
                if (modeSelect.value === 'auto_sr') loadOperatorWip();
            });

            btnReloadAuto.addEventListener('click', loadOperatorWip);

            // Submit validation + show "saving..."
            form.addEventListener('submit', function(e) {
                const m = modeSelect.value || 'normal';

                if (m === 'normal') {
                    const rows = Array.from(tbodyNormal.querySelectorAll('tr'));
                    const any = rows.some(tr => (tr.querySelector('select')?.value || '') && parseFloat(tr
                        .querySelector('input.qty')?.value || 0) > 0);
                    if (!any) {
                        e.preventDefault();
                        showErr('Mode Normal: minimal 1 item dengan qty > 0.');
                        return;
                    }
                } else {
                    const opId = operatorSelect.value;
                    if (!opId) {
                        e.preventDefault();
                        showErr('Auto-SR: operator wajib dipilih.');
                        return;
                    }

                    const trs = Array.from(tbodyAuto.querySelectorAll('tr[data-row="1"]'));
                    const any = trs.some(tr => {
                        const ok = parseIntSafe(tr.querySelector('input.ok')?.value || 0);
                        const rj = parseIntSafe(tr.querySelector('input.rj')?.value || 0);
                        return (ok + rj) > 0;
                    });

                    if (!any) {
                        e.preventDefault();
                        showErr('Auto-SR: isi minimal 1 baris (OK atau RJ > 0).');
                        return;
                    }

                    renumberAutoRows();
                }

                // show feedback
                const btn = (m === 'auto_sr') ? document.getElementById('btnSubmitAuto') : document
                    .getElementById('btnSubmitNormal');
                if (btn) {
                    btn.disabled = true;
                    btn.textContent = 'Menyimpan...';
                }
            });

            // init
            applyMode();
            if (modeSelect.value === 'auto_sr') loadOperatorWip();
        });
    </script>
@endpush
