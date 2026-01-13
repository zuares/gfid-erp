{{-- resources/views/inventory/wip_adjustments/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Inventory • Buat WIP Adjustment')

@push('head')
    <style>
        .page-wrap {
            max-width: 1050px;
            margin-inline: auto;
            padding: .85rem .85rem 4.25rem;
        }

        .card {
            background: var(--card, #fff);
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 18px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .07);
            overflow: hidden;
        }

        .hd {
            padding: 1rem 1.05rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            align-items: flex-start;
        }

        .title {
            font-size: 1.05rem;
            font-weight: 950;
            letter-spacing: .2px;
            color: rgba(15, 23, 42, 1);
        }

        body[data-theme="dark"] .title {
            color: rgba(226, 232, 240, 1);
        }

        .subtitle {
            margin-top: .25rem;
            font-size: .86rem;
            color: rgba(51, 65, 85, 1);
            line-height: 1.25rem;
        }

        body[data-theme="dark"] .subtitle {
            color: rgba(203, 213, 225, 1);
        }

        .bd {
            padding: 1rem 1.05rem;
        }

        .grid {
            display: grid;
            gap: .85rem;
        }

        @media(min-width:920px) {
            .grid.cols-3 {
                grid-template-columns: 1fr 1fr 1fr;
            }

            .grid.cols-2 {
                grid-template-columns: 1fr 1fr;
            }
        }

        .lbl {
            font-size: .82rem;
            font-weight: 900;
            color: rgba(30, 41, 59, 1);
            margin-bottom: .35rem;
            display: block;
        }

        body[data-theme="dark"] .lbl {
            color: rgba(226, 232, 240, 1);
        }

        .in,
        .sel,
        .ta {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, .40);
            border-radius: 14px;
            padding: .68rem .8rem;
            outline: none;
            background: rgba(255, 255, 255, .98);
            color: rgba(15, 23, 42, 1);
        }

        .in:focus,
        .sel:focus,
        .ta:focus {
            border-color: rgba(59, 130, 246, .65);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, .12);
        }

        body[data-theme="dark"] .in,
        body[data-theme="dark"] .sel,
        body[data-theme="dark"] .ta {
            background: rgba(2, 6, 23, .55);
            border-color: rgba(148, 163, 184, .28);
            color: rgba(226, 232, 240, 1);
        }

        body[data-theme="dark"] .in:focus,
        body[data-theme="dark"] .sel:focus,
        body[data-theme="dark"] .ta:focus {
            border-color: rgba(56, 189, 248, .55);
            box-shadow: 0 0 0 4px rgba(56, 189, 248, .12);
        }

        .help {
            margin-top: .35rem;
            font-size: .78rem;
            color: rgba(51, 65, 85, .9);
            line-height: 1.2rem;
        }

        body[data-theme="dark"] .help {
            color: rgba(148, 163, 184, 1);
        }

        .err {
            margin-top: .35rem;
            font-size: .78rem;
            color: rgba(239, 68, 68, 1);
            font-weight: 800;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border-radius: 14px;
            padding: .62rem .9rem;
            border: 1px solid rgba(148, 163, 184, .35);
            background: rgba(255, 255, 255, .96);
            color: rgba(15, 23, 42, 1);
            cursor: pointer;
            text-decoration: none;
            font-weight: 900;
        }

        .btn:hover {
            background: rgba(248, 250, 252, 1);
        }

        body[data-theme="dark"] .btn {
            background: rgba(2, 6, 23, .35);
            color: rgba(226, 232, 240, 1);
            border-color: rgba(148, 163, 184, .28);
        }

        body[data-theme="dark"] .btn:hover {
            background: rgba(2, 6, 23, .55);
        }

        .btn-primary {
            background: rgba(16, 185, 129, .16);
            border-color: rgba(16, 185, 129, .5);
        }

        .btn-danger {
            background: rgba(239, 68, 68, .12);
            border-color: rgba(239, 68, 68, .45);
        }

        .alert {
            padding: .8rem .95rem;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .24);
            background: rgba(248, 250, 252, 1);
            color: rgba(15, 23, 42, 1);
            margin-bottom: .85rem;
        }

        body[data-theme="dark"] .alert {
            background: rgba(2, 6, 23, .38);
            color: rgba(226, 232, 240, 1);
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .22rem .55rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .35);
            font-size: .75rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-variant-numeric: tabular-nums;
        }

        .split {
            display: grid;
            gap: .9rem;
        }

        @media(min-width:980px) {
            .split {
                grid-template-columns: 1.05fr .95fr;
                align-items: start;
            }
        }

        .panel {
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 18px;
            overflow: hidden;
            background: rgba(255, 255, 255, .96);
        }

        body[data-theme="dark"] .panel {
            background: rgba(2, 6, 23, .35);
            border-color: rgba(148, 163, 184, .22);
        }

        .panel-hd {
            padding: .75rem .9rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            align-items: center;
            background: rgba(148, 163, 184, .06);
        }

        body[data-theme="dark"] .panel-hd {
            background: rgba(148, 163, 184, .08);
        }

        .panel-title {
            font-weight: 950;
            color: rgba(15, 23, 42, 1);
        }

        body[data-theme="dark"] .panel-title {
            color: rgba(226, 232, 240, 1);
        }

        .panel-bd {
            padding: .85rem .9rem;
        }

        .list {
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 16px;
            overflow: hidden;
        }

        .rowx {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            padding: .72rem .85rem;
            border-top: 1px solid rgba(148, 163, 184, .16);
            cursor: pointer;
            align-items: flex-start;
        }

        .rowx:first-child {
            border-top: none;
        }

        .rowx:hover {
            background: rgba(59, 130, 246, .06);
        }

        body[data-theme="dark"] .rowx:hover {
            background: rgba(56, 189, 248, .08);
        }

        /* ✅ item jadi focal point */
        .r-title {
            font-weight: 1000;
            color: rgba(15, 23, 42, 1);
        }

        body[data-theme="dark"] .r-title {
            color: rgba(226, 232, 240, 1);
        }

        .r-sub {
            margin-top: .18rem;
            font-size: .78rem;
            color: rgba(51, 65, 85, .9);
        }

        body[data-theme="dark"] .r-sub {
            color: rgba(148, 163, 184, 1);
        }

        .r-right {
            text-align: right;
            min-width: 120px;
        }

        .r-big {
            font-weight: 1000;
        }

        .r-small {
            font-size: .75rem;
            opacity: .9;
        }

        .badges {
            display: flex;
            gap: .35rem;
            flex-wrap: wrap;
            margin-top: .35rem;
        }

        .b-mini {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .18rem .48rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .28);
            background: rgba(148, 163, 184, .08);
            font-size: .72rem;
            font-weight: 900;
        }

        body[data-theme="dark"] .b-mini {
            background: rgba(148, 163, 184, .10);
            border-color: rgba(148, 163, 184, .22);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            border-top: 1px solid rgba(148, 163, 184, .18);
            padding: .6rem .65rem;
            vertical-align: top;
        }

        .table thead th {
            font-size: .75rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: rgba(30, 41, 59, 1);
            background: rgba(148, 163, 184, .08);
        }

        body[data-theme="dark"] .table thead th {
            color: rgba(226, 232, 240, 1);
            background: rgba(148, 163, 184, .10);
        }

        .text-right {
            text-align: right;
        }

        .muted {
            color: rgba(51, 65, 85, .85);
            font-size: .78rem;
        }

        body[data-theme="dark"] .muted {
            color: rgba(148, 163, 184, 1);
        }

        .mini {
            padding: .45rem .55rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .28);
            background: rgba(255, 255, 255, .98);
        }

        body[data-theme="dark"] .mini {
            background: rgba(2, 6, 23, .45);
            color: rgba(226, 232, 240, 1);
            border-color: rgba(148, 163, 184, .22);
        }

        .hidden {
            display: none !important;
        }

        .badge-cut {
            border-color: rgba(59, 130, 246, .55);
            background: rgba(59, 130, 246, .10);
        }

        .badge-item {
            border-color: rgba(16, 185, 129, .55);
            background: rgba(16, 185, 129, .12);
        }

        .badge-warn {
            border-color: rgba(245, 158, 11, .55);
            background: rgba(245, 158, 11, .12);
        }
    </style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $role = $user?->role ?? null;
        $autoApprove = in_array($role, ['owner', 'admin'], true);
    @endphp

    <div class="page-wrap">
        <div class="card">
            <div class="hd">
                <div>
                    <div class="title">Buat WIP Adjustment</div>
                    <div class="subtitle">
                        Pilih gudang WIP → klik daftar kiri untuk menambah line → isi <span class="mono">qty_change</span>
                        (+/-).
                    </div>
                    <div class="help" style="margin-top:.25rem;">
                        @if ($autoApprove)
                            <span class="pill badge-item">✅ Auto Approved (Owner/Admin)</span>
                            <span class="pill badge-warn">Stok langsung berubah</span>
                        @else
                            <span class="pill badge-warn">⏳ Pending</span>
                            <span class="pill">Stok berubah setelah approve</span>
                        @endif
                    </div>
                </div>
                <a class="btn" href="{{ route('inventory.wip_adjustments.index') }}">← Kembali</a>
            </div>

            <div class="bd">
                @if ($errors->any())
                    <div class="alert">
                        <div style="font-weight:950;color:rgba(239,68,68,1);margin-bottom:.35rem;">Ada input yang belum
                            valid:</div>
                        <ul style="margin:0;padding-left:1.15rem;">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="post" action="{{ route('inventory.wip_adjustments.store') }}" id="wadj-form">
                    @csrf

                    {{-- HEADER --}}
                    <div class="grid cols-3">
                        <div>
                            <label class="lbl">Tanggal</label>
                            <input type="date" name="date" class="in"
                                value="{{ old('date', now()->toDateString()) }}">
                            @error('date')
                                <div class="err">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="lbl">Gudang WIP</label>
                            <select name="warehouse_id" id="warehouse_id" class="sel" required>
                                <option value="">— Pilih —</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" data-code="{{ $wh->code }}"
                                        @selected(old('warehouse_id') == $wh->id)>
                                        {{ $wh->code }} — {{ $wh->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="help" id="modeHelp">Pilih gudang untuk memuat daftar.</div>
                            @error('warehouse_id')
                                <div class="err">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="lbl">Cari</label>
                            <input type="text" id="picker_q" class="in"
                                placeholder="Ketik item code / nama / bundle code…">
                            <div class="help">Pencarian realtime (debounce).</div>
                        </div>
                    </div>

                    <div style="height: .85rem;"></div>

                    <div class="grid cols-2">
                        <div>
                            <label class="lbl">Reason</label>
                            <input type="text" name="reason" class="in" value="{{ old('reason') }}"
                                placeholder="contoh: Salah input / selisih QC / revisi">
                            @error('reason')
                                <div class="err">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="lbl">Notes (opsional)</label>
                            <input type="text" name="notes" class="in" value="{{ old('notes') }}"
                                placeholder="Keterangan tambahan…">
                            @error('notes')
                                <div class="err">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div style="height: 1.05rem;"></div>

                    <div class="split">
                        {{-- LEFT: Picker --}}
                        <div class="panel">
                            <div class="panel-hd">
                                <div class="panel-title" id="pickerTitle">Daftar</div>
                                <span class="pill mono" id="pickerBadge">—</span>
                            </div>
                            <div class="panel-bd">
                                <div class="help" id="pickerHint" style="margin-top:0;">Pilih gudang WIP untuk memuat
                                    data.</div>

                                <div class="list hidden" id="pickerList">
                                    <div class="rowx" style="cursor:default;background:rgba(148,163,184,.08);">
                                        <div class="muted" id="pickerHeadLeft">Klik untuk tambah ke Lines</div>
                                        <div class="muted" id="pickerHeadRight">Qty</div>
                                    </div>
                                    <div id="pickerBody"></div>
                                </div>
                            </div>
                        </div>

                        {{-- RIGHT: Lines --}}
                        <div class="panel">
                            <div class="panel-hd">
                                <div class="panel-title">Lines (yang akan disimpan)</div>
                                <div class="pill mono" id="linesCount">0 line</div>
                            </div>
                            <div class="panel-bd" style="padding:0;">
                                <table class="table" id="linesTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Item / Ref</th>
                                            <th class="text-right">Qty Change</th>
                                            <th>Notes</th>
                                            <th class="text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="linesBody">
                                        <tr>
                                            <td colspan="5" class="muted" style="padding:.85rem .9rem;">
                                                Belum ada line. Klik daftar di panel kiri.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="panel-bd"
                                style="padding:.85rem .9rem; border-top:1px solid rgba(148,163,184,.18);">
                                <div class="help" style="margin:0;">
                                    Isi <span class="mono">qty_change</span> (signed): <strong>+</strong> tambah /
                                    <strong>-</strong> kurangi.
                                    <span id="cutHint" class="hidden"><br>Untuk <strong>WIP-CUT</strong>, item adalah
                                        fokus;
                                        <span class="mono">Bundle</span> tampil sebagai badge & akan disimpan sebagai
                                        <span class="mono">cutting_job_bundle_id</span>.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Hidden lines appended on submit --}}
                    <div id="hiddenLines"></div>

                    @error('lines')
                        <div class="err" style="margin-top:.65rem;">{{ $message }}</div>
                    @enderror

                    <div style="height: 1.1rem;"></div>

                    <div style="display:flex;gap:.65rem;justify-content:flex-end;flex-wrap:wrap;">
                        <button type="button" class="btn btn-danger" id="clearLines">🧹 Clear Lines</button>
                        <a class="btn" href="{{ route('inventory.wip_adjustments.index') }}">Batal</a>
                        <button type="submit" class="btn btn-primary">✅ Simpan WIP Adjustment</button>
                    </div>

                    <div class="help" style="margin-top:.85rem;">
                        Catatan: jika role bukan Owner/Admin, dokumen akan <strong>Pending</strong> dan stok berubah saat
                        di-approve.
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const warehouseEl = document.getElementById('warehouse_id');
            const qEl = document.getElementById('picker_q');

            const pickerTitle = document.getElementById('pickerTitle');
            const pickerBadge = document.getElementById('pickerBadge');
            const pickerHint = document.getElementById('pickerHint');
            const pickerList = document.getElementById('pickerList');
            const pickerBody = document.getElementById('pickerBody');

            const pickerHeadLeft = document.getElementById('pickerHeadLeft');
            const pickerHeadRight = document.getElementById('pickerHeadRight');
            const modeHelp = document.getElementById('modeHelp');
            const cutHint = document.getElementById('cutHint');

            const linesBody = document.getElementById('linesBody');
            const linesCount = document.getElementById('linesCount');
            const hiddenLines = document.getElementById('hiddenLines');
            const clearLinesBtn = document.getElementById('clearLines');

            const form = document.getElementById('wadj-form');

            const urlItems = @json(route('inventory.wip_adjustments.items'));
            const urlBundles = @json(route('inventory.wip_adjustments.bundles'));

            let mode = null; // 'bundle' | 'item'
            let warehouseCode = null;

            // lines state:
            // { key, item_id, qty_change, notes, cutting_job_bundle_id?, label, badges?:[] }
            let lines = [];

            let tmr = null;

            function fmt(n) {
                const x = parseFloat(n);
                if (isNaN(x)) return '0.00';
                return x.toFixed(2);
            }

            function esc(s) {
                return String(s ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function setModeFromWarehouse() {
                const opt = warehouseEl.options[warehouseEl.selectedIndex];
                warehouseCode = opt ? (opt.getAttribute('data-code') || '') : '';

                if (!warehouseEl.value) {
                    mode = null;
                    pickerTitle.textContent = 'Daftar';
                    pickerBadge.textContent = '—';
                    pickerBadge.className = 'pill mono';
                    modeHelp.textContent = 'Pilih gudang untuk memuat daftar.';
                    pickerHint.textContent = 'Pilih gudang WIP untuk memuat data.';
                    pickerList.classList.add('hidden');
                    pickerBody.innerHTML = '';
                    cutHint.classList.add('hidden');
                    return;
                }

                if (warehouseCode === 'WIP-CUT') {
                    mode = 'bundle';
                    pickerTitle.textContent = 'Daftar (WIP-CUT)';
                    pickerBadge.textContent = 'BUNDLE';
                    pickerBadge.className = 'pill mono badge-cut';
                    modeHelp.textContent = 'WIP-CUT: pilih berdasarkan bundle (item jadi fokus).';
                    pickerHeadLeft.textContent = 'Klik untuk tambah line';
                    pickerHeadRight.textContent = 'Remaining';
                    cutHint.classList.remove('hidden');
                } else {
                    mode = 'item';
                    pickerTitle.textContent = 'Daftar (Item On Hand)';
                    pickerBadge.textContent = 'ITEM';
                    pickerBadge.className = 'pill mono badge-item';
                    modeHelp.textContent = 'WIP selain WIP-CUT: pilih item dari stok gudang.';
                    pickerHeadLeft.textContent = 'Klik untuk tambah line';
                    pickerHeadRight.textContent = 'On Hand';
                    cutHint.classList.add('hidden');
                }
            }

            async function loadPicker() {
                pickerBody.innerHTML = '';
                pickerList.classList.add('hidden');

                if (!warehouseEl.value || !mode) return;

                const wid = warehouseEl.value;
                const q = (qEl.value || '').trim();

                const url = mode === 'bundle' ?
                    `${urlBundles}?warehouse_id=${encodeURIComponent(wid)}&q=${encodeURIComponent(q)}` :
                    `${urlItems}?warehouse_id=${encodeURIComponent(wid)}&q=${encodeURIComponent(q)}`;

                pickerHint.textContent = 'Memuat data…';

                try {
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();

                    const arr = Array.isArray(data) ? data : [];

                    if (arr.length === 0) {
                        pickerHint.textContent = q ?
                            'Tidak ada data yang cocok.' :
                            (mode === 'bundle' ? 'Tidak ada bundle di WIP-CUT.' :
                                'Tidak ada item dengan stok (qty != 0) di gudang ini.');
                        return;
                    }

                    pickerHint.textContent = 'Klik baris untuk menambah line.';
                    pickerList.classList.remove('hidden');

                    arr.forEach(obj => {
                        const row = document.createElement('div');
                        row.className = 'rowx';

                        if (mode === 'bundle') {
                            // ✅ ITEM jadi focal point, bundle jadi badge
                            const key = `bundle:${obj.id}`;

                            const itemCode = esc(obj.item_code || '');
                            const itemName = esc(obj.item_name || '');
                            const bundleCode = esc(obj.bundle_code || '');

                            const label = `${itemCode} — ${itemName}`;

                            row.innerHTML = `
                                <div>
                                    <div class="r-title">${itemCode} — ${itemName}</div>
                                    <div class="badges">
                                        <span class="b-mini badge-cut">Bundle: <span class="mono">${bundleCode}</span></span>
                                        <span class="b-mini">ID: <span class="mono">${obj.id}</span></span>
                                        <span class="b-mini">ItemID: <span class="mono">${obj.item_id}</span></span>
                                    </div>
                                </div>
                                <div class="r-right">
                                    <div class="r-big mono">${fmt(obj.remaining ?? 0)}</div>
                                    <div class="r-small muted mono">WIP ${fmt(obj.wip_qty ?? 0)} • Picked ${fmt(obj.sewing_picked_qty ?? 0)}</div>
                                </div>
                            `;

                            row.addEventListener('click', () => addLine({
                                key,
                                label,
                                item_id: parseInt(obj.item_id, 10),
                                cutting_job_bundle_id: parseInt(obj.id, 10),
                                qty_change: '',
                                notes: '',
                                badges: [
                                    `Bundle ${bundleCode}`,
                                ]
                            }));
                        } else {
                            const key = `item:${obj.id}`;
                            const code = esc(obj.code || '');
                            const name = esc(obj.name || '');
                            const label = `${code} — ${name}`;

                            row.innerHTML = `
                                <div>
                                    <div class="r-title">${code} — ${name}</div>
                                    <div class="badges">
                                        <span class="b-mini badge-item">Item</span>
                                        <span class="b-mini">ID: <span class="mono">${obj.id}</span></span>
                                    </div>
                                </div>
                                <div class="r-right">
                                    <div class="r-big mono">${fmt(obj.on_hand ?? 0)}</div>
                                    <div class="r-small muted">On Hand</div>
                                </div>
                            `;

                            row.addEventListener('click', () => addLine({
                                key,
                                label,
                                item_id: parseInt(obj.id, 10),
                                qty_change: '',
                                notes: '',
                                badges: []
                            }));
                        }

                        pickerBody.appendChild(row);
                    });

                } catch (e) {
                    console.error(e);
                    pickerHint.textContent = 'Gagal memuat data. Cek route AJAX items/bundles.';
                }
            }

            function addLine(line) {
                if (lines.find(x => x.key === line.key)) return;
                lines.push(line);
                renderLines();
            }

            function removeLine(key) {
                lines = lines.filter(x => x.key !== key);
                renderLines();
            }

            function renderLines() {
                linesCount.textContent = `${lines.length} line`;

                if (lines.length === 0) {
                    linesBody.innerHTML = `
                        <tr>
                            <td colspan="5" class="muted" style="padding:.85rem .9rem;">
                                Belum ada line. Klik daftar di panel kiri.
                            </td>
                        </tr>
                    `;
                    return;
                }

                linesBody.innerHTML = '';

                lines.forEach((l, idx) => {
                    const tr = document.createElement('tr');

                    const badges = [];
                    badges.push(`<span class="pill badge-item">Item</span>`);
                    badges.push(`<span class="pill mono">#${l.item_id}</span>`);

                    if (l.cutting_job_bundle_id) {
                        const btxt = (l.badges && l.badges[0]) ? esc(l.badges[0]) :
                            `Bundle #${l.cutting_job_bundle_id}`;
                        badges.push(`<span class="pill badge-cut">${btxt}</span>`);
                    }

                    tr.innerHTML = `
                        <td class="mono">${idx + 1}</td>
                        <td>
                            <div style="font-weight:950;">${esc(l.label)}</div>
                            <div style="margin-top:.35rem;display:flex;gap:.45rem;flex-wrap:wrap;">
                                ${badges.join('')}
                            </div>
                        </td>
                        <td class="text-right">
                            <input type="number" step="0.001" class="mini mono text-right qty-change"
                                   placeholder="+2 atau -1" value="${esc(l.qty_change)}">
                            <div class="muted" style="margin-top:.25rem;">signed (+/-)</div>
                        </td>
                        <td>
                            <input type="text" class="mini notes" placeholder="opsional…" value="${esc(l.notes || '')}">
                        </td>
                        <td class="text-right">
                            <button type="button" class="btn btn-danger btn-sm remove">Hapus</button>
                        </td>
                    `;

                    const qtyEl = tr.querySelector('.qty-change');
                    const notesEl = tr.querySelector('.notes');
                    const rmBtn = tr.querySelector('.remove');

                    qtyEl.addEventListener('focus', () => qtyEl.select());
                    qtyEl.addEventListener('input', () => {
                        l.qty_change = qtyEl.value;
                    });
                    notesEl.addEventListener('input', () => {
                        l.notes = notesEl.value;
                    });
                    rmBtn.addEventListener('click', () => removeLine(l.key));

                    linesBody.appendChild(tr);
                });
            }

            clearLinesBtn.addEventListener('click', () => {
                lines = [];
                renderLines();
            });

            // Build hidden inputs on submit (sesuai controller)
            form.addEventListener('submit', function(e) {
                hiddenLines.innerHTML = '';

                if (!lines.length) {
                    e.preventDefault();
                    alert('Minimal 1 line harus ditambahkan.');
                    return;
                }

                let outIndex = 0;

                for (const l of lines) {
                    const qty = parseFloat(l.qty_change);
                    if (isNaN(qty) || Math.abs(qty) < 0.000001) continue;

                    const hItem = document.createElement('input');
                    hItem.type = 'hidden';
                    hItem.name = `lines[${outIndex}][item_id]`;
                    hItem.value = String(l.item_id);
                    hiddenLines.appendChild(hItem);

                    const hQty = document.createElement('input');
                    hQty.type = 'hidden';
                    hQty.name = `lines[${outIndex}][qty_change]`;
                    hQty.value = String(qty);
                    hiddenLines.appendChild(hQty);

                    const hNotes = document.createElement('input');
                    hNotes.type = 'hidden';
                    hNotes.name = `lines[${outIndex}][notes]`;
                    hNotes.value = (l.notes || '');
                    hiddenLines.appendChild(hNotes);

                    if (l.cutting_job_bundle_id) {
                        const hBundle = document.createElement('input');
                        hBundle.type = 'hidden';
                        hBundle.name = `lines[${outIndex}][cutting_job_bundle_id]`;
                        hBundle.value = String(l.cutting_job_bundle_id);
                        hiddenLines.appendChild(hBundle);
                    }

                    outIndex++;
                }

                if (outIndex === 0) {
                    e.preventDefault();
                    alert('Minimal 1 line harus punya qty_change (tidak boleh 0).');
                    return;
                }

                if (warehouseCode === 'WIP-CUT') {
                    const hasBad = lines.some(x => !x.cutting_job_bundle_id);
                    if (hasBad) {
                        e.preventDefault();
                        alert('Untuk WIP-CUT, semua line wajib pilih Bundle.');
                        return;
                    }
                }
            });

            warehouseEl.addEventListener('change', () => {
                setModeFromWarehouse();
                loadPicker();
            });

            qEl.addEventListener('input', () => {
                clearTimeout(tmr);
                tmr = setTimeout(loadPicker, 250);
            });

            // init
            setModeFromWarehouse();
            if (warehouseEl.value) loadPicker();
        })();
    </script>
@endpush
