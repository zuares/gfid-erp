{{-- resources/views/inventory/adjustments/manual_create.blade.php --}}
@extends('layouts.app')

@section('title', 'Adjustment Manual')

@push('head')
    <style>
        .page-wrap {
            max-width: 1000px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(56, 189, 248, 0.12) 0,
                    rgba(129, 140, 248, 0.12) 26%,
                    #f9fafb 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .25);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, .06),
                0 0 0 1px rgba(15, 23, 42, .03);
        }

        .table-wrap {
            margin-top: .5rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .24);
            overflow: hidden;
        }

        .table thead th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
            background: rgba(15, 23, 42, 0.02);
        }

        .pill-label {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
        }

        .text-mono {
            font-variant-numeric: tabular-nums;
        }

        .diff-plus {
            color: #16a34a;
        }

        .diff-minus {
            color: #dc2626;
        }

        .card-main .card-body { padding: .8rem .9rem; }
        .page-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; margin-bottom: .75rem; }
        .page-title { margin: 0; font-size: 1rem; font-weight: 900; letter-spacing: -.01em; }
        .status-chip { display: inline-flex; align-items: center; border-radius: 999px; padding: .18rem .52rem; border: 1px solid rgba(148, 163, 184, .22); background: rgba(148, 163, 184, .06); color: #64748b; font-size: .68rem; font-weight: 900; }
        .status-chip.is-owner { color: #2563eb; border-color: rgba(37, 99, 235, .20); background: rgba(37, 99, 235, .06); }
        .form-box, .filter-box { border: 1px solid rgba(148, 163, 184, .18); border-radius: 12px; padding: .6rem; background: rgba(148, 163, 184, .05); }
        body[data-theme="dark"] .form-box, body[data-theme="dark"] .filter-box { background: rgba(15, 23, 42, .32); }
        .filter-row { --bs-gutter-x: .45rem; --bs-gutter-y: .42rem; }
        .pill-label { display: block; margin-bottom: .18rem; font-size: .62rem; text-transform: none; letter-spacing: 0; color: #94a3b8; font-weight: 900; line-height: 1.05; white-space: nowrap; }
        .form-control-sm, .form-select-sm { min-height: 36px; border-radius: 10px; padding: .34rem .45rem; font-size: .8rem; }
        .btn-pill { border-radius: 999px; font-weight: 900; }
        .mini-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .4rem; margin: .55rem 0 .35rem; }
        .mini-kpi { border: 1px solid rgba(148, 163, 184, .18); border-radius: 10px; padding: .4rem .45rem; background: rgba(255, 255, 255, .45); min-width: 0; }
        body[data-theme="dark"] .mini-kpi { background: rgba(15, 23, 42, .20); }
        .mini-kpi .lbl { display: block; color: #64748b; font-size: .58rem; font-weight: 900; line-height: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .mini-kpi .val { display: block; margin-top: .16rem; font-size: .86rem; line-height: 1; font-weight: 950; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .mini-kpi.is-main { border-color: rgba(37, 99, 235, .20); background: rgba(37, 99, 235, .06); }
        .mini-kpi.is-main .val { color: #2563eb; }
        .table-wrap { overflow-x: auto; overflow-y: auto; max-height: 560px; }
        .item-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: .1rem .4rem; font-size: .62rem; font-weight: 900; border: 1px solid rgba(37, 99, 235, .18); color: #2563eb; background: rgba(37, 99, 235, .06); }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .card-main .card-body { padding: .65rem; }
            .page-head { margin-bottom: .55rem; }
            .form-box, .filter-box { padding: .5rem; border-radius: 12px; }
            .mini-kpis { gap: .32rem; }
            .mini-kpi { padding: .36rem .38rem; }
            .mini-kpi .val { font-size: .78rem; }

            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                border-bottom: 1px solid rgba(148, 163, 184, .25);
                padding: .35rem .75rem;
            }

            .table tbody tr:last-child {
                border-bottom: none;
            }

            .table tbody td {
                display: flex;
                justify-content: space-between;
                gap: .75rem;
                padding: .15rem 0;
                border-top: none;
                font-size: .85rem;
            }

            .table tbody td::before {
                content: attr(data-label);
                font-weight: 500;
                color: #64748b;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $isOwner = $user && ($user->role ?? null) === 'owner';
    @endphp

    <div class="page-wrap">
        <div class="card card-main">
            <div class="card-body">
                <div class="page-head">
                    <div>
                        <h1 class="page-title">Adjustment Manual</h1>
                        <span class="status-chip {{ $isOwner ? 'is-owner' : '' }}">
                            {{ $isOwner ? 'Langsung koreksi stok' : 'Menunggu Owner' }}
                        </span>
                    </div>
                    <div>
                        <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-sm btn-outline-secondary btn-pill">
                            ← Kembali
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('inventory.adjustments.manual.store') }}" id="adj-form">
                    @csrf

                    {{-- HEADER --}}
                    <div class="form-box mb-2">
                    <div class="row g-2">
                        <div class="col-7 col-lg-3">
                            <label class="form-label form-label-sm">Gudang</label>
                            <select name="warehouse_id" id="warehouse_id" class="form-select form-select-sm" required>
                                <option value="">Pilih gudang…</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}"
                                        {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                        {{ $wh->code }} — {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-5 col-lg-2">
                            <label class="form-label form-label-sm">Tanggal</label>
                            <input type="date" class="form-control form-control-sm" name="date"
                                value="{{ old('date', now()->toDateString()) }}" required>
                            @error('date')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-lg-3">
                            <label class="form-label form-label-sm">Alasan</label>
                            <input type="text" name="reason" class="form-control form-control-sm"
                                value="{{ old('reason') }}" placeholder="Koreksi rak A1">
                            @error('reason')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label form-label-sm">Catatan</label>
                            <input type="text" name="notes" class="form-control form-control-sm"
                                placeholder="Opsional" value="{{ old('notes') }}">
                            @error('notes')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    </div>

                    {{-- TOOLBAR --}}
                    <div class="filter-box mb-2">
                        <div class="row align-items-end filter-row">
                            <div class="col-12 col-lg-4">
                                <label class="pill-label">Item</label>
                                <div id="manual-adjustment-item-suggest">
                                    <x-item-suggest idName="quick_item_id" :idValue="old('quick_item_id')" :displayValue="''"
                                        placeholder="Kode / nama barang" :autofocus="true" :autoSelectFirst="false"
                                        :maxResults="3" />
                                </div>
                            </div>
                            <div class="col-5 col-lg-2">
                                <label class="pill-label">Fisik</label>
                                <input type="number" id="quick-physical-qty" class="form-control form-control-sm text-end"
                                    step="0.01" min="0" inputmode="decimal" placeholder="Qty">
                            </div>
                            <div class="col-3 col-lg-2">
                                <label class="pill-label">&nbsp;</label>
                                <button type="button" class="btn btn-sm btn-primary btn-pill w-100" id="add-search-item">
                                    Tambah
                                </button>
                            </div>
                        </div>
                        <div class="mini-kpis">
                            <div class="mini-kpi">
                                <span class="lbl">Item</span>
                                <span class="val text-mono" id="kpi-items">0</span>
                            </div>
                            <div class="mini-kpi">
                                <span class="lbl">Diubah</span>
                                <span class="val text-mono" id="kpi-changed">0</span>
                            </div>
                            <div class="mini-kpi is-main">
                                <span class="lbl">Selisih</span>
                                <span class="val text-mono" id="kpi-diff">0.00</span>
                            </div>
                            <div class="mini-kpi">
                                <span class="lbl">Item Baru</span>
                                <span class="val text-mono" id="kpi-new">0</span>
                            </div>
                        </div>
                    </div>

                    <div class="filter-box mb-2">
                        <div class="row align-items-end filter-row">
                            <div class="col-7 col-lg-4">
                                <label class="pill-label">Cari di tabel</label>
                                <input type="text" id="item-search" class="form-control form-control-sm" placeholder="Kode / nama">
                            </div>
                            <div class="col-5 col-lg-3">
                                <label class="pill-label">Tampilan</label>
                                <select id="show_changed_only" class="form-select form-select-sm">
                                    <option value="0">Semua item</option>
                                    <option value="1">Ada selisih</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- TABLE --}}
                    <div class="table-wrap mb-2">
                        <table class="table table-sm mb-0 align-middle" id="lines-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Item</th>
                                    <th class="text-end" style="width: 120px;">Stok</th>
                                    <th class="text-end" style="width: 140px;">Fisik</th>
                                    <th class="text-end" style="width: 120px;">Selisih</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- render via JS --}}
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="text-muted" id="items-hint" style="font-size: .78rem;">
                            Pilih gudang dulu.
                        </div>
                        <div class="text-muted" id="summary-change" style="font-size: .78rem;">
                            Selisih: 0.00
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm btn-pill" id="clear-all">
                            Reset Qty Fisik
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm btn-pill">
                            Simpan Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="quickItemModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius:16px;">
                    <div class="modal-header py-2">
                        <h5 class="modal-title mb-0">Item Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-5">
                                <label class="form-label form-label-sm">Kode</label>
                                <input type="text" id="quick-item-code" class="form-control form-control-sm text-mono" autocomplete="off">
                            </div>
                            <div class="col-7">
                                <label class="form-label form-label-sm">Nama</label>
                                <input type="text" id="quick-item-name" class="form-control form-control-sm" autocomplete="off">
                            </div>
                            <div class="col-4">
                                <label class="form-label form-label-sm">Satuan</label>
                                <input type="text" id="quick-item-unit" class="form-control form-control-sm" value="pcs" autocomplete="off">
                            </div>
                            <div class="col-8">
                                <label class="form-label form-label-sm">Role</label>
                                <select id="quick-item-role" class="form-select form-select-sm">
                                    @foreach (($itemRoles ?? collect()) as $role)
                                        <option value="{{ $role->id }}" @selected($role->code === 'RM')>
                                            {{ $role->code }} — {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label form-label-sm">Kategori</label>
                                <select id="quick-item-category" class="form-select form-select-sm">
                                    <option value="">Tanpa kategori</option>
                                    @foreach (($itemCategories ?? collect()) as $category)
                                        <option value="{{ $category->id }}">
                                            {{ $category->code }} — {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="text-danger small mt-2 d-none" id="quick-item-error"></div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-sm btn-primary btn-pill" id="quick-item-save">Buat & Tambah</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const warehouseSelect = document.getElementById('warehouse_id');
            const tbody = document.querySelector('#lines-table tbody');
            const itemsHint = document.getElementById('items-hint');
            const itemSearch = document.getElementById('item-search');
            const showChangedOnly = document.getElementById('show_changed_only');
            const summaryChange = document.getElementById('summary-change');
            const form = document.getElementById('adj-form');
            const clearAllBtn = document.getElementById('clear-all');
            const addSearchBtn = document.getElementById('add-search-item');
            const quickPhysicalQty = document.getElementById('quick-physical-qty');
            const quickSuggestWrap = document.getElementById('manual-adjustment-item-suggest');
            const quickItemId = quickSuggestWrap?.querySelector('.js-item-suggest-id');
            const quickItemText = quickSuggestWrap?.querySelector('.js-item-suggest-input');
            const kpiItems = document.getElementById('kpi-items');
            const kpiChanged = document.getElementById('kpi-changed');
            const kpiDiff = document.getElementById('kpi-diff');
            const kpiNew = document.getElementById('kpi-new');
            const quickModalEl = document.getElementById('quickItemModal');
            const quickCode = document.getElementById('quick-item-code');
            const quickName = document.getElementById('quick-item-name');
            const quickUnit = document.getElementById('quick-item-unit');
            const quickRole = document.getElementById('quick-item-role');
            const quickCategory = document.getElementById('quick-item-category');
            const quickError = document.getElementById('quick-item-error');
            const quickSave = document.getElementById('quick-item-save');

            const itemsUrl = @json(route('inventory.adjustments.items_for_warehouse'));
            const quickItemUrl = @json(route('inventory.adjustments.items.quick_store'));

            let warehouseItems = []; // {id, code, name, on_hand}
            let searchTimer = null;

            function fmt(n) {
                const x = parseFloat(n);
                if (isNaN(x)) return '0.00';
                return x.toFixed(2);
            }

            function buildRow(item, idx) {
                const onHand = parseFloat(item.on_hand) || 0;

                const tr = document.createElement('tr');
                tr.dataset.code = (item.code || '').toLowerCase();
                tr.dataset.name = (item.name || '').toLowerCase();
                tr.dataset.idx = String(idx);
                tr.dataset.notInWarehouse = item.not_in_warehouse ? '1' : '0';

                tr.innerHTML = `
            <td data-label="#">${idx + 1}</td>

            <td data-label="Item">
                <div class="fw-semibold text-mono">${item.code ?? ''} ${item.not_in_warehouse ? '<span class="item-badge">baru</span>' : ''}</div>
                <div class="text-muted" style="font-size:.82rem;">${item.name ?? ''}</div>

                <input type="hidden" class="row-item-id" value="${item.id}">
            </td>

            <td data-label="Stok saat ini" class="text-end text-mono">
                <span class="on-hand" data-on-hand="${onHand}">${fmt(onHand)}</span>
            </td>

            <td data-label="Qty fisik" class="text-end">
                <input type="number"
                       class="form-control form-control-sm text-end physical-input"
                       step="0.01" min="0" inputmode="decimal" placeholder="Fisik">
                <input type="hidden" class="qty-change-input" value="">
            </td>

            <td data-label="Selisih" class="text-end text-mono">
                <span class="diff-display">0.00</span>
            </td>

            <td data-label="Catatan">
                <input type="text" class="form-control form-control-sm notes-input" placeholder="Opsional">
            </td>
        `;

                // events
                const physical = tr.querySelector('.physical-input');
                physical.addEventListener('focus', () => physical.select());
                physical.addEventListener('blur', () => {
                    if (physical.value !== '' && !isNaN(parseFloat(physical.value))) {
                        physical.value = fmt(physical.value);
                    }
                });
                physical.addEventListener('input', () => {
                    recalcRow(tr);
                    applyFilter();
                    updateSummary();
                });

                return tr;
            }

            function recalcRow(tr) {
                const onHandSpan = tr.querySelector('.on-hand');
                const physicalInp = tr.querySelector('.physical-input');
                const diffSpan = tr.querySelector('.diff-display');
                const qtyChangeInp = tr.querySelector('.qty-change-input');

                const onHand = parseFloat(onHandSpan.dataset.onHand || '0') || 0;
                const physical = parseFloat(physicalInp.value);

                diffSpan.classList.remove('diff-plus', 'diff-minus');

                if (isNaN(physical)) {
                    diffSpan.textContent = '0.00';
                    qtyChangeInp.value = '';
                    tr.dataset.changed = '0';
                    return;
                }

                const diff = physical - onHand; // signed
                qtyChangeInp.value = diff.toFixed(2);

                tr.dataset.changed = (Math.abs(diff) > 0.000001) ? '1' : '0';

                let text = diff.toFixed(2);
                if (diff > 0) {
                    text = '+' + text;
                    diffSpan.classList.add('diff-plus');
                }
                if (diff < 0) {
                    diffSpan.classList.add('diff-minus');
                }
                diffSpan.textContent = text;
            }

            function renderRows() {
                tbody.innerHTML = '';

                warehouseItems.forEach((item, idx) => {
                    tbody.appendChild(buildRow(item, idx));
                });

                applyFilter();
                updateSummary();
            }

            function applyFilter() {
                const term = (itemSearch.value || '').trim().toLowerCase();
                const changedOnly = showChangedOnly.value === '1';

                tbody.querySelectorAll('tr').forEach(tr => {
                    const code = tr.dataset.code || '';
                    const name = tr.dataset.name || '';
                    const changed = tr.dataset.changed === '1';

                    let ok = true;

                    if (term) ok = (code.includes(term) || name.includes(term));
                    if (ok && changedOnly) ok = changed;

                    tr.style.display = ok ? '' : 'none';
                });
            }

            function updateSummary() {
                let total = 0;
                let changed = 0;
                let newItems = 0;

                tbody.querySelectorAll('tr').forEach(tr => {
                    if (tr.dataset.changed === '1') changed++;
                    if (tr.dataset.notInWarehouse === '1') newItems++;
                });

                tbody.querySelectorAll('.qty-change-input').forEach(inp => {
                    const v = parseFloat(inp.value);
                    if (!isNaN(v)) total += v;
                });

                let text = fmt(total);
                if (total > 0) text = '+' + text;

                summaryChange.textContent = 'Selisih: ' + text;
                if (kpiItems) kpiItems.textContent = String(warehouseItems.length);
                if (kpiChanged) kpiChanged.textContent = String(changed);
                if (kpiDiff) kpiDiff.textContent = text;
                if (kpiNew) kpiNew.textContent = String(newItems);
            }

            function snapshotInputs() {
                const state = {};
                tbody.querySelectorAll('tr').forEach(tr => {
                    const id = tr.querySelector('.row-item-id')?.value;
                    if (!id) return;
                    state[id] = {
                        physical: tr.querySelector('.physical-input')?.value || '',
                        notes: tr.querySelector('.notes-input')?.value || '',
                    };
                });
                return state;
            }

            function restoreInputs(state) {
                tbody.querySelectorAll('tr').forEach(tr => {
                    const id = tr.querySelector('.row-item-id')?.value;
                    if (!id || !state[id]) return;
                    const physical = tr.querySelector('.physical-input');
                    const notes = tr.querySelector('.notes-input');
                    if (physical) physical.value = state[id].physical || '';
                    if (notes) notes.value = state[id].notes || '';
                    recalcRow(tr);
                });
            }

            function mergeItems(items) {
                const byId = new Map(warehouseItems.map(item => [String(item.id), item]));
                (items || []).forEach(item => {
                    const key = String(item.id);
                    if (byId.has(key)) {
                        byId.set(key, { ...byId.get(key), ...item });
                    } else {
                        byId.set(key, item);
                    }
                });
                warehouseItems = Array.from(byId.values()).sort((a, b) => String(a.code || '').localeCompare(String(b.code || '')));
            }

            function focusItemByTerm(term) {
                const needle = (term || '').trim().toLowerCase();
                if (!needle) return false;

                const rows = Array.from(tbody.querySelectorAll('tr'));
                const row = rows.find(tr => {
                    return (tr.dataset.code || '').includes(needle)
                        || (tr.dataset.name || '').includes(needle);
                });

                if (!row) return false;

                row.style.display = '';
                row.scrollIntoView({ block: 'center', behavior: 'smooth' });
                const input = row.querySelector('.physical-input');
                if (input) {
                    setTimeout(() => {
                        input.focus();
                        input.select();
                    }, 200);
                }

                return true;
            }

            function fetchSearchItems(term, focusAfter = false) {
                const warehouseId = warehouseSelect.value || '';
                if (!warehouseId) {
                    itemsHint.textContent = 'Pilih gudang dulu.';
                    return Promise.resolve([]);
                }
                if (term.length < 2) {
                    itemsHint.textContent = 'Ketik minimal 2 huruf kode/nama.';
                    return Promise.resolve([]);
                }

                itemsHint.textContent = 'Mencari item…';
                return fetch(itemsUrl + '?warehouse_id=' + encodeURIComponent(warehouseId) + '&q=' + encodeURIComponent(term))
                    .then(res => {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    })
                    .then(data => {
                        const state = snapshotInputs();
                        mergeItems(Array.isArray(data) ? data : []);
                        renderRows();
                        restoreInputs(state);
                        applyFilter();
                        updateSummary();
                        itemsHint.textContent = data.length ? 'Item ditemukan. Isi Fisik untuk menambahkan.' : 'Item tidak ditemukan.';
                        if (focusAfter && data.length) focusItemByTerm(term);
                        return data;
                    })
                    .catch(err => {
                        console.error(err);
                        itemsHint.textContent = 'Gagal mencari item.';
                        return [];
                    });
            }

            function resetQuickItemError() {
                if (!quickError) return;
                quickError.textContent = '';
                quickError.classList.add('d-none');
            }

            function showQuickItemError(message) {
                if (!quickError) return;
                quickError.textContent = message || 'Data belum bisa disimpan.';
                quickError.classList.remove('d-none');
            }

            function addItemToTable(item) {
                const state = snapshotInputs();
                mergeItems([item]);
                renderRows();
                restoreInputs(state);
                itemSearch.value = '';
                applyFilter();
                updateSummary();
                focusItemByTerm(item.code || item.name || '');
            }

            function addSuggestedItemToTable() {
                const warehouseId = warehouseSelect.value || '';
                const id = quickItemId?.value || '';

                if (!warehouseId) {
                    itemsHint.textContent = 'Pilih gudang dulu.';
                    return;
                }

                if (!id) {
                    itemsHint.textContent = 'Pilih item dulu.';
                    quickItemText?.focus();
                    return;
                }

                itemsHint.textContent = 'Menambahkan item…';

                fetch(itemsUrl + '?warehouse_id=' + encodeURIComponent(warehouseId) + '&item_id=' + encodeURIComponent(id))
                    .then(res => {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    })
                    .then(data => {
                        const item = Array.isArray(data) ? data[0] : null;
                        if (!item) {
                            itemsHint.textContent = 'Item tidak ditemukan.';
                            return;
                        }

                        addItemToTable(item);

                        const row = Array.from(tbody.querySelectorAll('tr')).find(tr => {
                            return tr.querySelector('.row-item-id')?.value === String(item.id);
                        });

                        if (row && quickPhysicalQty?.value !== '') {
                            const physical = row.querySelector('.physical-input');
                            if (physical) {
                                physical.value = quickPhysicalQty.value;
                                recalcRow(row);
                                updateSummary();
                            }
                        }

                        if (quickItemId) quickItemId.value = '';
                        if (quickItemText) quickItemText.value = '';
                        if (quickPhysicalQty) quickPhysicalQty.value = '';
                        itemsHint.textContent = 'Item ditambahkan.';
                        setTimeout(() => quickItemText?.focus(), 250);
                    })
                    .catch(err => {
                        console.error(err);
                        itemsHint.textContent = 'Gagal menambahkan item.';
                    });
            }

            quickModalEl?.addEventListener('shown.bs.modal', () => {
                resetQuickItemError();
                if (quickCode) {
                    quickCode.value = (itemSearch.value || '').trim().toUpperCase();
                    quickCode.focus();
                    quickCode.select();
                }
            });

            quickCode?.addEventListener('input', () => {
                const start = quickCode.selectionStart;
                const end = quickCode.selectionEnd;
                quickCode.value = quickCode.value.toUpperCase();
                try { quickCode.setSelectionRange(start, end); } catch (_) {}
            });

            quickSave?.addEventListener('click', async () => {
                resetQuickItemError();

                const code = (quickCode?.value || '').trim().toUpperCase();
                const name = (quickName?.value || '').trim();
                const unit = (quickUnit?.value || 'pcs').trim();

                if (!code || !name) {
                    showQuickItemError('Kode dan nama wajib diisi.');
                    return;
                }

                quickSave.disabled = true;
                quickSave.textContent = 'Menyimpan…';

                try {
                    const res = await fetch(quickItemUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            code,
                            name,
                            unit,
                            item_role_id: quickRole?.value || null,
                            item_category_id: quickCategory?.value || null,
                        }),
                    });

                    const json = await res.json();
                    if (!res.ok || !json.ok) {
                        const errors = json.errors || {};
                        const first = Object.values(errors).flat()[0] || json.message || 'Data belum bisa disimpan.';
                        showQuickItemError(first);
                        return;
                    }

                    addItemToTable(json.item);
                    itemsHint.textContent = 'Item baru dibuat. Isi Fisik untuk adjustment.';
                    bootstrap.Modal.getOrCreateInstance(quickModalEl).hide();

                    if (quickName) quickName.value = '';
                    if (quickUnit) quickUnit.value = 'pcs';
                } catch (err) {
                    console.error(err);
                    showQuickItemError('Gagal membuat item baru.');
                } finally {
                    quickSave.disabled = false;
                    quickSave.textContent = 'Buat & Tambah';
                }
            });

            function loadItemsForWarehouse(warehouseId) {
                if (!warehouseId) {
                    warehouseItems = [];
                    tbody.innerHTML = '';
                    itemsHint.textContent = 'Pilih gudang dulu.';
                    summaryChange.textContent = 'Selisih: 0.00';
                    updateSummary();
                    return;
                }

                itemsHint.textContent = 'Memuat item…';

                fetch(itemsUrl + '?warehouse_id=' + encodeURIComponent(warehouseId))
                    .then(res => {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    })
                    .then(data => {
                        warehouseItems = Array.isArray(data) ? data : [];
                        itemsHint.textContent = warehouseItems.length ?
                            'Isi Fisik. Cari kode untuk tambah item lain.' :
                            'Belum ada stok. Cari kode untuk tambah item.';
                        renderRows();
                    })
                    .catch(err => {
                        console.error(err);
                        warehouseItems = [];
                        tbody.innerHTML = '';
                        itemsHint.textContent = 'Gagal memuat item. Coba reload halaman.';
                        summaryChange.textContent = 'Selisih: 0.00';
                        updateSummary();
                    });
            }

            // ✅ KRUSIAL: hanya kirim baris yang berubah (qty_change != 0)
            form.addEventListener('submit', function() {
                let outIndex = 0;

                tbody.querySelectorAll('tr').forEach(tr => {
                    // hapus input "named" dari submit sebelumnya (kalau user submit ulang setelah validation error)
                    tr.querySelectorAll('[data-named="1"]').forEach(el => el.remove());

                    const changed = tr.dataset.changed === '1';
                    const qtyChange = parseFloat(tr.querySelector('.qty-change-input').value || '0');

                    if (!changed || isNaN(qtyChange) || Math.abs(qtyChange) < 0.000001) {
                        return;
                    }

                    const itemId = tr.querySelector('.row-item-id').value;
                    const notes = tr.querySelector('.notes-input').value || '';

                    // create hidden named inputs
                    const h1 = document.createElement('input');
                    h1.type = 'hidden';
                    h1.name = `lines[${outIndex}][item_id]`;
                    h1.value = itemId;
                    h1.dataset.named = '1';

                    const h2 = document.createElement('input');
                    h2.type = 'hidden';
                    h2.name = `lines[${outIndex}][qty_change]`;
                    h2.value = qtyChange.toFixed(2);
                    h2.dataset.named = '1';

                    const h3 = document.createElement('input');
                    h3.type = 'hidden';
                    h3.name = `lines[${outIndex}][notes]`;
                    h3.value = notes;
                    h3.dataset.named = '1';

                    tr.appendChild(h1);
                    tr.appendChild(h2);
                    tr.appendChild(h3);

                    outIndex++;
                });
            });

            // reset qty fisik
            clearAllBtn.addEventListener('click', function() {
                tbody.querySelectorAll('tr').forEach(tr => {
                    const inp = tr.querySelector('.physical-input');
                    const notes = tr.querySelector('.notes-input');
                    inp.value = '';
                    notes.value = '';
                    recalcRow(tr);
                });
                applyFilter();
                updateSummary();
            });

            warehouseSelect.addEventListener('change', () => loadItemsForWarehouse(warehouseSelect.value || null));
            itemSearch.addEventListener('focus', () => setTimeout(() => itemSearch.select(), 0));
            itemSearch.addEventListener('input', () => {
                const start = itemSearch.selectionStart;
                const end = itemSearch.selectionEnd;
                itemSearch.value = itemSearch.value.toUpperCase();
                try { itemSearch.setSelectionRange(start, end); } catch (_) {}

                applyFilter();
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => fetchSearchItems((itemSearch.value || '').trim()), 350);
            });
            itemSearch.addEventListener('keydown', (e) => {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                clearTimeout(searchTimer);
                fetchSearchItems((itemSearch.value || '').trim(), true);
            });
            addSearchBtn.addEventListener('click', () => {
                clearTimeout(searchTimer);
                addSuggestedItemToTable();
            });
            quickPhysicalQty?.addEventListener('focus', () => quickPhysicalQty.select());
            quickPhysicalQty?.addEventListener('keydown', (e) => {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                addSuggestedItemToTable();
            });
            showChangedOnly.addEventListener('change', applyFilter);

            // initial load (kalau old('warehouse_id') kebaca)
            if (warehouseSelect.value) {
                loadItemsForWarehouse(warehouseSelect.value);
            }
        })();
    </script>
@endpush
