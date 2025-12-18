{{-- resources/views/inventory/rts_stock_requests/create.blade.php --}}
@extends('layouts.app')

@section('title', 'RTS • Buat / Update Permintaan Replenish')

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-main-strong: rgba(15, 118, 110, 1);
            --rts-main-soft: rgba(45, 212, 191, .14);
            --warn-soft: rgba(245, 158, 11, .14);
            --danger-soft: rgba(239, 68, 68, .12);
        }

        .page-wrap {
            max-width: 1050px;
            margin-inline: auto;
            padding: 1rem .9rem 5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, .10) 0,
                    rgba(45, 212, 191, .12) 28%,
                    #f9fafb 65%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .28);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06), 0 0 0 1px rgba(15, 23, 42, .03);
            padding: .95rem;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .muted {
            opacity: .78;
        }

        .small {
            font-size: .85rem;
        }

        .tiny {
            font-size: .78rem;
        }

        .row {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: flex-start;
        }

        .col {
            flex: 1 1 240px;
            min-width: 220px;
        }

        label {
            display: block;
            font-size: .82rem;
            opacity: .78;
            margin-bottom: .25rem;
        }

        input[type="date"],
        input[type="number"],
        textarea,
        input[type="text"] {
            width: 100%;
            padding: .55rem .6rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: transparent;
            color: inherit;
            outline: none;
        }

        textarea {
            min-height: 92px;
            resize: vertical;
        }

        .banner {
            border-radius: 14px;
            padding: .75rem .85rem;
            border: 1px solid rgba(45, 212, 191, .35);
            background: var(--rts-main-soft);
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .16rem .55rem;
            border-radius: 999px;
            font-size: .78rem;
            border: 1px solid rgba(148, 163, 184, .35);
            background: rgba(148, 163, 184, .10);
            white-space: nowrap;
        }

        .pill.warn {
            border-color: rgba(245, 158, 11, .40);
            background: var(--warn-soft);
        }

        .pill.danger {
            border-color: rgba(239, 68, 68, .40);
            background: var(--danger-soft);
        }

        .hint {
            margin-top: .6rem;
            border-radius: 12px;
            padding: .65rem .75rem;
            border: 1px dashed rgba(148, 163, 184, .45);
            background: rgba(148, 163, 184, .06);
        }

        .hint b {
            font-weight: 800;
        }

        .hint ul {
            margin: .35rem 0 0;
            padding-left: 1.05rem;
        }

        .hint li {
            margin: .15rem 0;
        }

        /* =========================
                   TABLE + DROPDOWN SAFETY
                ========================== */
        .table-wrap {
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .22);
            background: rgba(148, 163, 184, .06);
            margin-top: .75rem;
            overflow: visible !important;
            position: relative;
        }

        .table-scroll {
            overflow: auto;
            border-radius: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 820px;
        }

        th,
        td {
            padding: .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, .16);
            vertical-align: top;
            font-size: .9rem;
            overflow: visible !important;
            position: relative;
        }

        th {
            text-align: left;
            font-size: .78rem;
            opacity: .72;
            white-space: nowrap;
        }

        .btn-row {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            margin-top: .9rem;
            align-items: center;
        }

        .btns {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .actions-cell {
            display: flex;
            gap: .4rem;
            align-items: center;
            justify-content: flex-end;
        }

        .icon-btn {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: transparent;
            color: inherit;
            cursor: pointer;
            opacity: .9;
        }

        .icon-btn:hover {
            opacity: 1;
            border-color: rgba(45, 212, 191, .55);
        }

        /* =========================
                   UX: flash highlight row yg kena merge
                ========================== */
        tr.flash td {
            animation: flashBg 900ms ease;
        }

        @keyframes flashBg {
            0% {
                background: rgba(45, 212, 191, .18);
            }

            100% {
                background: transparent;
            }
        }

        /* =========================
                   Dropdown "always on top"
                   - pakai position fixed via JS
                ========================== */
        .item-suggest-dropdown {
            z-index: 999999 !important;
            position: fixed !important;
            left: 0;
            top: 0;
            width: 320px;
            display: none;
        }

        /* Toast */
        .toast {
            position: fixed;
            left: 50%;
            bottom: 18px;
            transform: translateX(-50%);
            z-index: 999999;
            background: rgba(15, 23, 42, .92);
            color: #fff;
            padding: .55rem .75rem;
            border-radius: 999px;
            font-size: .82rem;
            box-shadow: 0 14px 30px rgba(0, 0, 0, .25);
            opacity: 0;
            pointer-events: none;
            transition: opacity .18s ease, transform .18s ease;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(-4px);
        }
    </style>
@endpush

@section('content')
    @php
        $itemsForJs = $finishedGoodsItems
            ->map(fn($i) => ['id' => $i->id, 'code' => $i->code, 'name' => $i->name])
            ->values()
            ->toArray();

        $prefillLinesForJs = $prefillLines ?? [];

        $itemSuggestHtml = view('components.item-suggest', [
            'idName' => '__tmp__',
            'categoryName' => null,
            'items' => $finishedGoodsItems,
            'displayValue' => '',
            'idValue' => '',
            'categoryValue' => '',
            'placeholder' => 'Kode / nama barang',
            'type' => 'finished_good',
            'itemCategoryId' => null,
            'minChars' => 1,
            'autofocus' => false,
            'variant' => 'default',
            'displayMode' => 'code-name',
            'showName' => true,
            'showCategory' => false,
            'extraParams' => [],
        ])->render();
    @endphp

    <div class="page-wrap">
        <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">
            <div>
                <h1 style="font-size:1.25rem;font-weight:800;margin:0">RTS • Buat / Update Permintaan</h1>
                <div class="muted small" style="margin-top:.25rem">
                    Form ini untuk <b>append kebutuhan</b> ke dokumen hari ini (kalau sudah ada).
                </div>
            </div>

            <div class="btns">
                <a class="btn btn-outline" href="{{ route('rts.stock-requests.index') }}">← List</a>
                <a class="btn btn-primary" href="{{ route('rts.stock-requests.today') }}">Hari Ini</a>
            </div>
        </div>

        @if (!empty($prefillRequest))
            <div class="banner" style="margin-top:.85rem">
                <div style="display:flex;justify-content:space-between;gap:.75rem;flex-wrap:wrap;align-items:flex-start">
                    <div>
                        <div class="mono" style="font-weight:900">Lanjutkan dokumen: {{ $prefillRequest->code }}</div>
                        <div class="muted small">
                            Prefill berisi <b>sisa (outstanding)</b>. Submit akan <b>append</b> qty_request ke dokumen ini.
                        </div>
                    </div>
                    <div class="pill warn">Mode: APPEND</div>
                </div>
            </div>
        @endif



        <form id="rtsCreateForm" method="POST" action="{{ route('rts.stock-requests.store') }}" style="margin-top:.9rem">
            @csrf

            <div class="card">
                <div class="row">
                    <div class="col">
                        <label>Tanggal</label>
                        <input type="date" name="date"
                            value="{{ old('date', $prefillDate ?? now()->toDateString()) }}">
                        @error('date')
                            <div class="tiny" style="color: rgba(239,68,68,1); margin-top:.25rem">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col">
                        <label>Gudang Sumber (PRD)</label>
                        <input type="hidden" name="source_warehouse_id" value="{{ $prdWarehouse->id }}">
                        <input type="text" value="{{ $prdWarehouse->code }} — {{ $prdWarehouse->name ?? 'PRD' }}"
                            disabled>
                    </div>

                    <div class="col">
                        <label>Gudang Tujuan (RTS)</label>
                        <input type="hidden" name="destination_warehouse_id" value="{{ $rtsWarehouse->id }}">
                        <input type="text" value="{{ $rtsWarehouse->code }} — {{ $rtsWarehouse->name ?? 'RTS' }}"
                            disabled>
                    </div>
                </div>

                <div style="margin-top:.75rem">
                    <label>Catatan (opsional)</label>
                    <textarea name="notes" placeholder="Contoh: butuh untuk packing sore / order urgent">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="card" style="margin-top:.85rem">
                <div style="display:flex;justify-content:space-between;gap:.75rem;flex-wrap:wrap;align-items:center">
                    <div>
                        <div style="font-weight:900">Item Request</div>
                        <div class="muted small">Isi qty yang ingin kamu <b>tambahkan</b> (append).</div>
                    </div>

                    <div class="btns">
                        <button type="button" class="btn btn-outline" id="btnAddRow">+ Tambah Baris</button>
                        <button type="button" class="btn btn-outline" id="btnFillOutstanding"
                            @if (empty($prefillLinesForJs) || count($prefillLinesForJs) === 0) disabled @endif>
                            Prefill Sisa
                        </button>
                    </div>
                </div>

                <div class="table-wrap">
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:44px">#</th>
                                    <th style="width:360px">Item</th>
                                    <th style="width:150px">Qty ADD</th>
                                    <th>Catatan Line</th>
                                    <th style="width:120px;text-align:right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="linesTbody"></tbody>
                        </table>
                    </div>
                </div>

                @error('lines')
                    <div class="tiny" style="color: rgba(239,68,68,1); margin-top:.5rem">{{ $message }}</div>
                @enderror
            </div>

            <div class="btn-row">
                <div class="muted small">Setelah submit: diarahkan ke halaman detail RTS.</div>
                <div class="btns">
                    <a class="btn btn-outline" href="{{ route('rts.stock-requests.index') }}">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        @if (!empty($prefillRequest))
                            Update (Append) Permintaan
                        @else
                            Kirim Permintaan ke PRD
                        @endif
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        (function() {
            const items = @json($itemsForJs);
            const oldLines = @json(old('lines', null));
            const prefillLines = @json($prefillLinesForJs);
            const itemSuggestHtml = @json($itemSuggestHtml);

            const tbody = document.getElementById('linesTbody');
            const form = document.getElementById('rtsCreateForm');
            const toast = document.getElementById('toast');

            function showToast(msg) {
                if (!toast) return;
                toast.textContent = msg;
                toast.classList.add('show');
                clearTimeout(showToast._t);
                showToast._t = setTimeout(() => toast.classList.remove('show'), 1400);
            }

            function getItemById(id) {
                const s = String(id);
                return items.find(x => String(x.id) === s) || null;
            }

            function num(v) {
                const n = parseFloat((v ?? '').toString().replace(',', '.'));
                return isNaN(n) ? 0 : n;
            }

            function renumber() {
                [...tbody.querySelectorAll('tr')].forEach((tr, idx) => {
                    tr.querySelector('[data-no]').textContent = String(idx + 1);

                    tr.querySelectorAll('[data-name]').forEach(el => {
                        const base = el.getAttribute('data-name');
                        el.setAttribute('name', `lines[${idx}][${base}]`);
                    });

                    const hiddenId = tr.querySelector('.js-item-suggest-id');
                    if (hiddenId) hiddenId.setAttribute('name', `lines[${idx}][item_id]`);

                    const hiddenCat = tr.querySelector('.js-item-suggest-category');
                    if (hiddenCat) hiddenCat.setAttribute('name', `lines[${idx}][item_category_id]`);
                });
            }

            function clearSuggest(tr) {
                const hiddenId = tr.querySelector('.js-item-suggest-id');
                const inputTxt = tr.querySelector('.js-item-suggest-input');
                if (hiddenId) hiddenId.value = '';
                if (inputTxt) inputTxt.value = '';
                const dd = tr.querySelector('.item-suggest-dropdown');
                if (dd) dd.style.display = 'none';
            }

            function flashRow(tr) {
                tr.classList.remove('flash');
                // force reflow
                void tr.offsetWidth;
                tr.classList.add('flash');
                setTimeout(() => tr.classList.remove('flash'), 950);
            }

            function findRowByItemId(itemId, excludeTr = null) {
                const target = String(itemId);
                const rows = [...tbody.querySelectorAll('tr')];
                for (const tr of rows) {
                    if (excludeTr && tr === excludeTr) continue;
                    const hid = tr.querySelector('.js-item-suggest-id');
                    const val = (hid?.value || '').trim();
                    if (val && String(val) === target) return tr;
                }
                return null;
            }

            function buildRow() {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="mono" data-no style="opacity:.75">1</td>
                    <td class="cell-item"></td>
                    <td>
                        <input data-name="qty_request" class="line-qty" type="number" step="0.01" min="0" value="" placeholder="0">
                    </td>
                    <td>
                        <input data-name="notes" type="text" value="" placeholder="opsional">
                    </td>
                    <td style="text-align:right">
                        <div class="actions-cell">
                            <button type="button" class="icon-btn btnDel" title="Hapus">✕</button>
                        </div>
                    </td>
                `;

                tr.querySelector('.cell-item').innerHTML = itemSuggestHtml;

                tr.querySelector('.btnDel').addEventListener('click', () => {
                    tr.remove();
                    renumber();
                });

                tbody.appendChild(tr);

                if (window.initItemSuggestInputs) window.initItemSuggestInputs(tr);

                // MERGE behavior
                const hiddenId = tr.querySelector('.js-item-suggest-id');
                if (hiddenId) {
                    hiddenId.addEventListener('change', () => {
                        const chosen = (hiddenId.value || '').trim();
                        if (!chosen) return;

                        const existingTr = findRowByItemId(chosen, tr);
                        if (!existingTr) return;

                        const qtyThisEl = tr.querySelector('.line-qty');
                        const qtyThis = num(qtyThisEl?.value);

                        const qtyExistEl = existingTr.querySelector('.line-qty');
                        const qtyExist = num(qtyExistEl?.value);

                        // default add 1 kalau qty kosong
                        const addQty = qtyThis > 0 ? qtyThis : 1;

                        if (qtyExistEl) qtyExistEl.value = (qtyExist + addQty).toFixed(2);

                        // notes merge
                        const notesThis = (tr.querySelector('[data-name="notes"]')?.value || '').trim();
                        const notesExistEl = existingTr.querySelector('[data-name="notes"]');
                        const notesExist = (notesExistEl?.value || '').trim();
                        if (notesThis) {
                            if (notesExistEl) notesExistEl.value = notesExist ? (notesExist + ' | ' +
                                notesThis) : notesThis;
                        }

                        // feedback
                        const it = getItemById(chosen);
                        const label = it ? `${(it.code || '').toUpperCase()}` : `Item ${chosen}`;
                        showToast(`✅ ${label}: qty digabung (+${addQty})`);
                        flashRow(existingTr);

                        // clear current row
                        if (qtyThisEl) qtyThisEl.value = '';
                        tr.querySelector('[data-name="notes"]').value = '';
                        clearSuggest(tr);

                        tr.querySelector('.js-item-suggest-input')?.focus();
                    });
                }

                renumber();
                return tr;
            }

            function setRowData(tr, data = {}) {
                const hiddenId = tr.querySelector('.js-item-suggest-id');
                const inputTxt = tr.querySelector('.js-item-suggest-input');
                const qtyInput = tr.querySelector('.line-qty');
                const notesInput = tr.querySelector('[data-name="notes"]');

                if (data.item_id && hiddenId && inputTxt) {
                    const it = getItemById(data.item_id);
                    hiddenId.value = String(data.item_id);

                    if (it) {
                        inputTxt.value = `${(it.code || '').toUpperCase()} — ${it.name || ''}`.toUpperCase();
                    } else {
                        inputTxt.value = String(data.item_id);
                    }

                    hiddenId.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }

                if (qtyInput && data.qty_request != null) qtyInput.value = data.qty_request;
                if (notesInput && data.notes != null) notesInput.value = data.notes;
            }

            function addRow(data = {}) {
                const tr = buildRow();
                setRowData(tr, data);
            }

            function seedInitialRows() {
                if (Array.isArray(oldLines) && oldLines.length > 0) {
                    oldLines.forEach(l => addRow(l));
                    return;
                }
                if (Array.isArray(prefillLines) && prefillLines.length > 0) {
                    prefillLines.forEach(l => addRow(l));
                    return;
                }
                addRow({});
            }

            document.getElementById('btnAddRow')?.addEventListener('click', () => addRow({}));

            document.getElementById('btnFillOutstanding')?.addEventListener('click', () => {
                tbody.innerHTML = '';
                if (Array.isArray(prefillLines) && prefillLines.length > 0) {
                    prefillLines.forEach(l => addRow(l));
                } else {
                    addRow({});
                }
            });

            // submit cleanup
            form?.addEventListener('submit', (e) => {
                const rows = [...tbody.querySelectorAll('tr')];
                rows.forEach(tr => {
                    const id = (tr.querySelector('.js-item-suggest-id')?.value || '').trim();
                    const qty = num(tr.querySelector('.line-qty')?.value);
                    if (!id && qty <= 0) tr.remove();
                });

                renumber();

                const any = [...tbody.querySelectorAll('.js-item-suggest-id')]
                    .some(h => (h.value || '').trim() !== '');
                if (!any) {
                    e.preventDefault();
                    alert('Minimal isi 1 item.');
                }
            });

            /* =========================================================
               Dropdown FIXED POSITION PATCH
               - pastikan dropdown item-suggest selalu di atas elemen lain
               - kebal overflow container/table
            ========================================================= */
            function patchFixedDropdown(scope = document) {
                scope.querySelectorAll('.item-suggest-wrap').forEach(wrap => {
                    const input = wrap.querySelector('.js-item-suggest-input');
                    const dropdown = wrap.querySelector('.item-suggest-dropdown');
                    if (!input || !dropdown) return;

                    if (dropdown.dataset.fixedPatched === '1') return;
                    dropdown.dataset.fixedPatched = '1';

                    function placeDropdown() {
                        if (dropdown.style.display === 'none') return;

                        const rect = input.getBoundingClientRect();
                        const margin = 6;

                        const maxH = 240;
                        const viewportH = window.innerHeight;

                        // default below
                        let top = rect.bottom + margin;
                        let left = rect.left;
                        let width = rect.width;

                        // if not enough space below, show above
                        const spaceBelow = viewportH - rect.bottom - margin;
                        const desiredH = Math.min(maxH, dropdown.scrollHeight || maxH);

                        if (spaceBelow < 120) {
                            const aboveTop = rect.top - margin - desiredH;
                            if (aboveTop > 8) {
                                top = aboveTop;
                            }
                        }

                        dropdown.style.left = left + 'px';
                        dropdown.style.top = top + 'px';
                        dropdown.style.width = width + 'px';
                        dropdown.style.maxHeight = Math.min(maxH, Math.max(120, spaceBelow)) + 'px';
                    }

                    // observe show/hide by monkey patch style.display changes via MutationObserver
                    const obs = new MutationObserver(() => placeDropdown());
                    obs.observe(dropdown, {
                        attributes: true,
                        attributeFilter: ['style', 'class']
                    });

                    // reposition on scroll/resize
                    const onMove = () => placeDropdown();
                    window.addEventListener('scroll', onMove, true);
                    window.addEventListener('resize', onMove);

                    // also on focus
                    input.addEventListener('focus', () => setTimeout(placeDropdown, 0));
                    input.addEventListener('input', () => setTimeout(placeDropdown, 0));
                });
            }

            // init rows first
            seedInitialRows();

            // patch dropdown after initItemSuggestInputs created dropdown content
            setTimeout(() => patchFixedDropdown(document), 0);

            // also patch when adding rows
            const _origAddRow = addRow;
            window.__rtsAddRow = function(data) {
                _origAddRow(data);
                setTimeout(() => patchFixedDropdown(document), 0);
            };
        })();
    </script>
@endsection
