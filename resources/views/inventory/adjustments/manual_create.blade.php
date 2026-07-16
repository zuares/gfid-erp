@extends('layouts.app')

@section('title', 'Adjustment Manual')

@push('head')
<style>
    :root{
        --adj-accent:#334155;
        --adj-accent-2:#1f2937;
        --adj-border:rgba(148,163,184,.18);
        --adj-muted:#64748b;
    }

    /* ── Topbar ─────────────────────────────────────────────── */
    .adj-topbar{
        position:sticky;
        top:0;
        z-index:300;
        display:flex;
        align-items:center;
        gap:.45rem;
        flex-wrap:wrap;
        padding:.45rem .75rem;
        background:var(--card,#fff);
        border-bottom:1px solid var(--adj-border);
    }
    body[data-theme="dark"] .adj-topbar{ background:var(--card,#0f172a); }
    .adj-topbar-title{ font-weight:750; font-size:.95rem; letter-spacing:0; white-space:nowrap; }
    .adj-topbar-spacer{ flex:1; min-width:.5rem; }
    .adj-badge{
        border-radius:7px; padding:.16rem .44rem;
        font-size:.68rem; letter-spacing:0; text-transform:none;
        border:1px solid rgba(148,163,184,.28);
        color:var(--adj-muted); background:transparent;
        display:inline-flex; align-items:center; gap:.3rem;
        white-space:nowrap;
    }
    .adj-badge::before{ content:''; width:7px; height:7px; border-radius:999px; background:rgba(100,116,139,.95); display:inline-block; }
    .adj-badge.is-owner{ color:#1d4ed8; border-color:rgba(59,130,246,.30); }
    .adj-badge.is-owner::before{ background:rgba(59,130,246,.95); }
    .btn-adj-outline{
        color:#475569!important; background:transparent!important;
        border:1px solid rgba(148,163,184,.35)!important;
        border-radius:7px!important; font-size:.74rem!important;
        padding:.28rem .62rem!important; box-shadow:none!important;
        text-decoration:none; white-space:nowrap;
    }
    .btn-adj-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }
    .btn-adj-primary{
        background:var(--adj-accent)!important; border-color:var(--adj-accent)!important;
        color:#fff!important; border-radius:7px!important; font-weight:600!important;
        box-shadow:none!important;
    }
    .btn-adj-primary:hover{ background:var(--adj-accent-2)!important; border-color:var(--adj-accent-2)!important; color:#fff!important; }

    /* ── Card ────────────────────────────────────────────────── */
    .adj-card{
        background:var(--card);
        border-radius:8px;
        border:1px solid var(--adj-border);
        box-shadow:none;
    }
    body[data-theme="dark"] .adj-card{ border-color:rgba(51,65,85,.85); box-shadow:none; }
    .adj-card-body{ padding:.85rem; }
    .adj-section-label{
        font-size:.66rem; font-weight:700; text-transform:uppercase;
        letter-spacing:.04em; color:#94a3b8; margin-bottom:.55rem;
        display:flex; align-items:center; gap:.35rem;
    }
    .adj-section-label::after{ content:''; flex:1; height:1px; background:rgba(148,163,184,.20); }
    body[data-theme="dark"] .adj-section-label::after{ background:rgba(51,65,85,.85); }

    /* ── Form ────────────────────────────────────────────────── */
    .adj-form-row{ --bs-gutter-x:.5rem; --bs-gutter-y:.42rem; }
    .adj-field-label{
        display:block; font-size:.68rem; font-weight:600;
        color:#64748b; margin-bottom:.2rem; white-space:nowrap;
    }
    body[data-theme="dark"] .adj-field-label{ color:#9ca3af; }
    .form-control-sm,.form-select-sm{
        border-radius:8px; border-color:rgba(148,163,184,.35);
        box-shadow:none!important; font-size:.82rem; min-height:34px;
    }
    .form-control-sm:focus,.form-select-sm:focus{
        border-color:rgba(71,85,105,.75); box-shadow:none!important;
    }

    /* ── KPI mini ────────────────────────────────────────────── */
    .adj-kpi-row{ display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.4rem; margin-top:.6rem; }
    .adj-kpi{
        border:1px solid rgba(148,163,184,.18); border-radius:8px;
        padding:.4rem .5rem; background:transparent; min-width:0;
    }
    body[data-theme="dark"] .adj-kpi{ background:rgba(15,23,42,.20); }
    .adj-kpi .lbl{ display:block; font-size:.58rem; font-weight:700; color:#94a3b8; line-height:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .adj-kpi .val{ display:block; margin-top:.14rem; font-size:.88rem; font-weight:800; line-height:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-variant-numeric:tabular-nums; }
    .adj-kpi.is-main .val{ color:#16a34a; }

    /* ── Table ───────────────────────────────────────────────── */
    .adj-table-scroll{
        max-height:calc(100vh - 320px);
        overflow-y:auto;
        overflow-x:hidden;
        overscroll-behavior:contain;
    }
    .adj-table{ margin-bottom:0; }
    .adj-table thead th{
        position:sticky; top:0; z-index:2;
        font-size:.66rem; text-transform:uppercase; letter-spacing:.03em;
        color:#6b7280; background:var(--card,#fff);
        padding:.42rem .5rem; white-space:nowrap; border-bottom-width:1px;
    }
    body[data-theme="dark"] .adj-table thead th{
        background:rgba(15,23,42,.98); color:#9ca3af;
        border-bottom-color:rgba(30,64,175,.6);
    }
    .adj-table tbody td{
        vertical-align:middle; padding:.38rem .5rem;
        border-top-color:rgba(148,163,184,.16);
    }
    body[data-theme="dark"] .adj-table tbody td{ border-top-color:rgba(51,65,85,.85); }
    .text-mono{ font-variant-numeric:tabular-nums; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; }
    .diff-plus{ color:#16a34a; }
    .diff-minus{ color:#dc2626; }
    .item-badge{
        display:inline-flex; align-items:center; border-radius:7px;
        padding:.1rem .35rem; font-size:.6rem; font-weight:700;
        border:1px solid rgba(34,197,94,.25); color:#166534;
        background:rgba(34,197,94,.06); margin-left:.3rem;
    }

    /* ── Footer bar ─────────────────────────────────────────── */
    .adj-footer{
        display:flex; justify-content:space-between; align-items:center;
        padding:.6rem 0 0; gap:.5rem; flex-wrap:wrap;
    }
    .adj-footer-hint{ font-size:.76rem; color:#94a3b8; }

    /* ── Divider ────────────────────────────────────────────── */
    .adj-divider{ height:1px; background:rgba(148,163,184,.20); margin:.65rem 0; }
    body[data-theme="dark"] .adj-divider{ background:rgba(51,65,85,.85); }

    /* ── Empty ──────────────────────────────────────────────── */
    .adj-empty{ padding:2rem 1rem; text-align:center; color:#94a3b8; font-size:.84rem; }

    @media(max-width:768px){
        .adj-topbar{ padding:.5rem; }
        .adj-topbar-title{ font-size:1rem; }
        .adj-kpi-row{ grid-template-columns:repeat(2,1fr); gap:.32rem; }
        .adj-card-body{ padding:.65rem; }
        .adj-table-scroll{ max-height:none; overflow:visible; }
        .adj-table thead{ display:none; }
        .adj-table,.adj-table tbody,.adj-table tr,.adj-table td{ display:block; width:100%; }
        .adj-table tbody tr{ padding:.55rem; border-top:1px solid rgba(148,163,184,.16); }
        .adj-table tbody td{ border:0; padding:0; }
        .adj-table tbody td.mobile-hide{ display:none; }
        .adj-row-main{ display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; }
        .adj-row-meta{ display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; margin-top:.3rem; color:#64748b; font-size:.76rem; }
    }
</style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $isOwner = $user && ($user->role ?? null) === 'owner';
    @endphp

    {{-- TOPBAR --}}
    <div class="adj-topbar">
        <span class="adj-topbar-title">Adjustment Manual</span>
        <span class="adj-badge {{ $isOwner ? 'is-owner' : '' }}">
            {{ $isOwner ? 'Langsung koreksi stok' : 'Menunggu Owner' }}
        </span>
        <span class="adj-topbar-spacer"></span>
        <a href="{{ route('inventory.adjustments.index') }}" class="btn-adj-outline">Daftar Adjustment</a>
    </div>

    <form method="POST" action="{{ route('inventory.adjustments.manual.store') }}" id="adj-form">
        @csrf

        {{-- STEP 1: Header --}}
        <div class="adj-card" style="margin-top:.65rem;">
            <div class="adj-card-body">
                <div class="adj-section-label">Informasi Adjustment</div>
                <div class="row adj-form-row">
                    <div class="col-6 col-lg-3">
                        <label class="adj-field-label">Gudang</label>
                        <select name="warehouse_id" id="warehouse_id" class="form-select form-select-sm" required>
                            <option value="">Pilih gudang…</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->code }} — {{ $wh->name }}</option>
                            @endforeach
                        </select>
                        @error('warehouse_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="adj-field-label">Tanggal</label>
                        <input type="date" class="form-control form-control-sm" name="date" value="{{ old('date', now()->toDateString()) }}" required>
                        @error('date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-lg-3">
                        <label class="adj-field-label">Alasan</label>
                        <input type="text" name="reason" class="form-control form-control-sm" value="{{ old('reason') }}" placeholder="Koreksi rak A1">
                        @error('reason') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="adj-field-label">Catatan</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Opsional" value="{{ old('notes') }}">
                        @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- STEP 2: Tambah Item --}}
        <div class="adj-card" style="margin-top:.5rem;">
            <div class="adj-card-body">
                <div class="adj-section-label">Tambah Item</div>
                <div class="row adj-form-row align-items-end">
                    <div class="col-12 col-lg-5">
                        <label class="adj-field-label">Cari Item</label>
                        <div id="manual-adjustment-item-suggest">
                            <x-item-suggest idName="quick_item_id" :idValue="old('quick_item_id')" :displayValue="''"
                                placeholder="Kode / nama barang" :autofocus="true" :autoSelectFirst="false"
                                :maxResults="3" />
                        </div>
                    </div>
                    <div class="col-4 col-lg-2">
                        <label class="adj-field-label">Qty Fisik</label>
                        <input type="number" id="quick-physical-qty" class="form-control form-control-sm text-end" step="0.01" min="0" inputmode="decimal" placeholder="0">
                    </div>
                    <div class="col-4 col-lg-2">
                        <label class="adj-field-label">&nbsp;</label>
                        <button type="button" class="btn btn-sm btn-adj-primary w-100" id="add-search-item">Tambah</button>
                    </div>
                    <div class="col-4 col-lg-3">
                        <label class="adj-field-label">&nbsp;</label>
                        <button type="button" class="btn btn-sm btn-adj-outline w-100" data-bs-toggle="modal" data-bs-target="#quickItemModal">+ Item Baru</button>
                    </div>
                </div>

                <div class="adj-kpi-row">
                    <div class="adj-kpi"><span class="lbl">Item</span><span class="val text-mono" id="kpi-items">0</span></div>
                    <div class="adj-kpi"><span class="lbl">Diubah</span><span class="val text-mono" id="kpi-changed">0</span></div>
                    <div class="adj-kpi is-main"><span class="lbl">Selisih</span><span class="val text-mono" id="kpi-diff">0.00</span></div>
                    <div class="adj-kpi"><span class="lbl">Item Baru</span><span class="val text-mono" id="kpi-new">0</span></div>
                </div>
            </div>
        </div>

        {{-- STEP 3: Tabel --}}
        <div class="adj-card" style="margin-top:.5rem;">
            <div class="adj-card-body" style="padding:.5rem .6rem .6rem;">
                <div class="d-flex justify-content-between align-items-center" style="margin-bottom:.4rem;">
                    <div class="adj-section-label" style="margin:0;">Daftar Item</div>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="text" id="item-search" class="form-control form-control-sm" placeholder="Cari kode / nama" style="width:160px;font-size:.76rem;height:28px;">
                        <select id="show_changed_only" class="form-select form-select-sm" style="width:120px;font-size:.76rem;height:28px;">
                            <option value="0">Semua item</option>
                            <option value="1">Ada selisih</option>
                        </select>
                    </div>
                </div>

                <div class="adj-table-scroll">
                    <table class="table table-sm align-middle adj-table" id="lines-table">
                        <thead>
                            <tr>
                                <th style="width:36px;">#</th>
                                <th>Item</th>
                                <th class="text-end" style="width:100px;">Stok</th>
                                <th class="text-end" style="width:120px;">Fisik</th>
                                <th class="text-end" style="width:100px;">Selisih</th>
                                <th style="width:140px;">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- render via JS --}}
                        </tbody>
                    </table>
                </div>

                <div class="adj-divider"></div>

                <div class="adj-footer">
                    <span class="adj-footer-hint" id="items-hint">Pilih gudang dulu.</span>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="adj-footer-hint" id="summary-change">Selisih: 0.00</span>
                        <button type="button" class="btn btn-sm btn-adj-outline" id="clear-all">Reset</button>
                        <button type="submit" class="btn btn-sm btn-adj-primary">Simpan Adjustment</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- MODAL: Item Baru --}}
    <div class="modal fade" id="quickItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:8px;border:1px solid var(--adj-border);box-shadow:none;">
                <div class="modal-header py-2" style="border-bottom-color:rgba(148,163,184,.18);">
                    <h6 class="modal-title mb-0" style="font-weight:700;font-size:.88rem;">Item Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row adj-form-row">
                        <div class="col-5">
                            <label class="adj-field-label">Kode</label>
                            <input type="text" id="quick-item-code" class="form-control form-control-sm text-mono" autocomplete="off">
                        </div>
                        <div class="col-7">
                            <label class="adj-field-label">Nama</label>
                            <input type="text" id="quick-item-name" class="form-control form-control-sm" autocomplete="off">
                        </div>
                        <div class="col-4">
                            <label class="adj-field-label">Satuan</label>
                            <input type="text" id="quick-item-unit" class="form-control form-control-sm" value="pcs" autocomplete="off">
                        </div>
                        <div class="col-8">
                            <label class="adj-field-label">Role</label>
                            <select id="quick-item-role" class="form-select form-select-sm">
                                @foreach (($itemRoles ?? collect()) as $role)
                                    <option value="{{ $role->id }}" @selected($role->code === 'RM')>{{ $role->code }} — {{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="adj-field-label">Kategori</label>
                            <select id="quick-item-category" class="form-select form-select-sm">
                                <option value="">Tanpa kategori</option>
                                @foreach (($itemCategories ?? collect()) as $category)
                                    <option value="{{ $category->id }}">{{ $category->code }} — {{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="text-danger small mt-2 d-none" id="quick-item-error"></div>
                </div>
                <div class="modal-footer py-2" style="border-top-color:rgba(148,163,184,.18);">
                    <button type="button" class="btn btn-sm btn-adj-outline" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-sm btn-adj-primary" id="quick-item-save">Buat & Tambah</button>
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

            const itemsUrl = @json(route('inventory.adjustments.items_for_warehouse', [], false));
            const quickItemUrl = @json(route('inventory.adjustments.items.quick_store', [], false));

            let warehouseItems = [];
            let searchTimer = null;

            function fmt(n) {
                const x = parseFloat(n);
                if (isNaN(x)) return '0.00';
                return x.toFixed(2);
            }

            function buildRow(item, idx) {
                const onHand = parseFloat(item.on_hand) || 0;
                const lotId = item.lot_id || '';
                const lotCode = item.lot_code || '';

                const tr = document.createElement('tr');
                tr.dataset.code = (item.code || '').toLowerCase();
                tr.dataset.name = (item.name || '').toLowerCase();
                tr.dataset.idx = String(idx);
                tr.dataset.notInWarehouse = item.not_in_warehouse ? '1' : '0';

                tr.innerHTML = `
            <td class="text-muted small mobile-hide">${idx + 1}</td>
            <td>
                <div class="adj-row-main">
                    <div>
                        <span class="text-mono fw-semibold" style="font-size:.86rem;">${item.code ?? ''}</span>
                        ${item.not_in_warehouse ? '<span class="item-badge">baru</span>' : ''}
                        ${lotCode ? `<span class="item-badge bg-secondary text-white border-secondary">Lot: ${lotCode}</span>` : ''}
                        <div style="font-size:.78rem;color:#6b7280;margin-top:.1rem;">${item.name ?? ''}</div>
                    </div>
                    <span class="diff-display mobile-hide text-mono" style="font-size:.8rem;">0.00</span>
                </div>
                <div class="adj-row-meta mobile-hide">
                    <span>Stok: <strong class="text-mono"><span class="on-hand" data-on-hand="${onHand}">${fmt(onHand)}</span></strong></span>
                </div>
                <input type="hidden" class="row-item-id" value="${item.id}">
                <input type="hidden" class="row-lot-id" value="${lotId}">
            </td>
            <td class="text-end mobile-hide text-mono">
                <span class="on-hand desktop-only" data-on-hand="${onHand}">${fmt(onHand)}</span>
            </td>
            <td class="text-end">
                <input type="number" class="form-control form-control-sm text-end physical-input" style="width:90px;display:inline-block;" step="0.01" min="0" inputmode="decimal" placeholder="Fisik">
                <input type="hidden" class="qty-change-input" value="">
            </td>
            <td class="text-end mobile-hide text-mono">
                <span class="diff-display">0.00</span>
            </td>
            <td class="mobile-hide">
                <input type="text" class="form-control form-control-sm notes-input" style="font-size:.76rem;" placeholder="Opsional">
            </td>
        `;

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
                const diffSpans = tr.querySelectorAll('.diff-display');
                const qtyChangeInp = tr.querySelector('.qty-change-input');

                const onHand = parseFloat(onHandSpan?.dataset.onHand || '0') || 0;
                const physical = parseFloat(physicalInp.value);

                diffSpans.forEach(s => s.classList.remove('diff-plus', 'diff-minus'));

                if (isNaN(physical)) {
                    diffSpans.forEach(s => s.textContent = '0.00');
                    qtyChangeInp.value = '';
                    tr.dataset.changed = '0';
                    return;
                }

                const diff = physical - onHand;
                qtyChangeInp.value = diff.toFixed(2);
                tr.dataset.changed = (Math.abs(diff) > 0.000001) ? '1' : '0';

                let text = diff.toFixed(2);
                if (diff > 0) { text = '+' + text; diffSpans.forEach(s => s.classList.add('diff-plus')); }
                if (diff < 0) { diffSpans.forEach(s => s.classList.add('diff-minus')); }
                diffSpans.forEach(s => s.textContent = text);
            }

            function renderRows() {
                tbody.innerHTML = '';
                warehouseItems.forEach((item, idx) => { tbody.appendChild(buildRow(item, idx)); });
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
                let total = 0, changed = 0, newItems = 0;
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
                    const lotId = tr.querySelector('.row-lot-id')?.value || '';
                    if (!id) return;
                    const key = id + '_' + lotId;
                    state[key] = {
                        physical: tr.querySelector('.physical-input')?.value || '',
                        notes: tr.querySelector('.notes-input')?.value || '',
                    };
                });
                return state;
            }

            function restoreInputs(state) {
                tbody.querySelectorAll('tr').forEach(tr => {
                    const id = tr.querySelector('.row-item-id')?.value;
                    const lotId = tr.querySelector('.row-lot-id')?.value || '';
                    const key = id + '_' + lotId;
                    if (!id || !state[key]) return;
                    const physical = tr.querySelector('.physical-input');
                    const notes = tr.querySelector('.notes-input');
                    if (physical) physical.value = state[key].physical || '';
                    if (notes) notes.value = state[key].notes || '';
                    recalcRow(tr);
                });
            }

            function mergeItems(items) {
                const byId = new Map(warehouseItems.map(item => [String(item.id) + '_' + (item.lot_id||''), item]));
                (items || []).forEach(item => {
                    const key = String(item.id) + '_' + (item.lot_id||'');
                    if (byId.has(key)) { byId.set(key, { ...byId.get(key), ...item }); }
                    else { byId.set(key, item); }
                });
                warehouseItems = Array.from(byId.values()).sort((a, b) => String(a.code || '').localeCompare(String(b.code || '')));
            }

            function focusItemByTerm(term) {
                const needle = (term || '').trim().toLowerCase();
                if (!needle) return false;
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const row = rows.find(tr => (tr.dataset.code || '').includes(needle) || (tr.dataset.name || '').includes(needle));
                if (!row) return false;
                row.style.display = '';
                row.scrollIntoView({ block: 'center', behavior: 'smooth' });
                const input = row.querySelector('.physical-input');
                if (input) setTimeout(() => { input.focus(); input.select(); }, 200);
                return true;
            }

            function fetchSearchItems(term, focusAfter = false) {
                const warehouseId = warehouseSelect.value || '';
                if (!warehouseId) { itemsHint.textContent = 'Pilih gudang dulu.'; return Promise.resolve([]); }
                if (term.length < 2) { itemsHint.textContent = 'Ketik minimal 2 huruf.'; return Promise.resolve([]); }
                itemsHint.textContent = 'Mencari item…';
                return fetch(itemsUrl + '?warehouse_id=' + encodeURIComponent(warehouseId) + '&q=' + encodeURIComponent(term))
                    .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                    .then(data => {
                        const state = snapshotInputs();
                        mergeItems(Array.isArray(data) ? data : []);
                        renderRows(); restoreInputs(state); applyFilter(); updateSummary();
                        itemsHint.textContent = data.length ? 'Item ditemukan. Isi Fisik untuk menambahkan.' : 'Item tidak ditemukan.';
                        if (focusAfter && data.length) focusItemByTerm(term);
                        return data;
                    })
                    .catch(err => { console.error(err); itemsHint.textContent = 'Gagal mencari item.'; return []; });
            }

            function resetQuickItemError() { if (quickError) { quickError.textContent = ''; quickError.classList.add('d-none'); } }
            function showQuickItemError(msg) { if (quickError) { quickError.textContent = msg || 'Data belum bisa disimpan.'; quickError.classList.remove('d-none'); } }

            function addItemToTable(item) {
                const state = snapshotInputs();
                mergeItems([item]); renderRows(); restoreInputs(state);
                itemSearch.value = ''; applyFilter(); updateSummary();
                focusItemByTerm(item.code || item.name || '');
            }

            function addSuggestedItemToTable() {
                const warehouseId = warehouseSelect.value || '';
                const id = quickItemId?.value || '';
                if (!warehouseId) { itemsHint.textContent = 'Pilih gudang dulu.'; return; }
                if (!id) { itemsHint.textContent = 'Pilih item dulu.'; quickItemText?.focus(); return; }
                itemsHint.textContent = 'Menambahkan item…';
                fetch(itemsUrl + '?warehouse_id=' + encodeURIComponent(warehouseId) + '&item_id=' + encodeURIComponent(id))
                    .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                    .then(data => {
                        const item = Array.isArray(data) ? data[0] : null;
                        if (!item) { itemsHint.textContent = 'Item tidak ditemukan.'; return; }
                        addItemToTable(item);
                        const row = Array.from(tbody.querySelectorAll('tr')).find(tr => tr.querySelector('.row-item-id')?.value === String(item.id));
                        if (row && quickPhysicalQty?.value !== '') {
                            const physical = row.querySelector('.physical-input');
                            if (physical) { physical.value = quickPhysicalQty.value; recalcRow(row); updateSummary(); }
                        }
                        if (quickItemId) quickItemId.value = '';
                        if (quickItemText) quickItemText.value = '';
                        if (quickPhysicalQty) quickPhysicalQty.value = '';
                        itemsHint.textContent = 'Item ditambahkan.';
                        setTimeout(() => quickItemText?.focus(), 250);
                    })
                    .catch(err => { console.error(err); itemsHint.textContent = 'Gagal menambahkan item.'; });
            }

            quickModalEl?.addEventListener('shown.bs.modal', () => {
                resetQuickItemError();
                if (quickCode) { quickCode.value = (itemSearch.value || '').trim().toUpperCase(); quickCode.focus(); quickCode.select(); }
            });
            quickCode?.addEventListener('input', () => {
                const s = quickCode.selectionStart, e = quickCode.selectionEnd;
                quickCode.value = quickCode.value.toUpperCase();
                try { quickCode.setSelectionRange(s, e); } catch (_) {}
            });
            quickSave?.addEventListener('click', async () => {
                resetQuickItemError();
                const code = (quickCode?.value || '').trim().toUpperCase();
                const name = (quickName?.value || '').trim();
                const unit = (quickUnit?.value || 'pcs').trim();
                if (!code || !name) { showQuickItemError('Kode dan nama wajib diisi.'); return; }
                quickSave.disabled = true; quickSave.textContent = 'Menyimpan…';
                try {
                    const res = await fetch(quickItemUrl, {
                        method: 'POST',
                        headers: { 'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||'','X-Requested-With':'XMLHttpRequest' },
                        body: JSON.stringify({ code, name, unit, item_role_id: quickRole?.value||null, item_category_id: quickCategory?.value||null }),
                    });
                    const json = await res.json();
                    if (!res.ok || !json.ok) { const errors = json.errors||{}; showQuickItemError(Object.values(errors).flat()[0]||json.message||'Data belum bisa disimpan.'); return; }
                    addItemToTable(json.item);
                    itemsHint.textContent = 'Item baru dibuat.';
                    bootstrap.Modal.getOrCreateInstance(quickModalEl).hide();
                    if (quickName) quickName.value = '';
                    if (quickUnit) quickUnit.value = 'pcs';
                } catch (err) { console.error(err); showQuickItemError('Gagal membuat item baru.'); }
                finally { quickSave.disabled = false; quickSave.textContent = 'Buat & Tambah'; }
            });

            function loadItemsForWarehouse(warehouseId) {
                if (!warehouseId) { warehouseItems = []; tbody.innerHTML = ''; itemsHint.textContent = 'Pilih gudang dulu.'; summaryChange.textContent = 'Selisih: 0.00'; updateSummary(); return; }
                itemsHint.textContent = 'Memuat item…';
                fetch(itemsUrl + '?warehouse_id=' + encodeURIComponent(warehouseId))
                    .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                    .then(data => {
                        warehouseItems = Array.isArray(data) ? data : [];
                        itemsHint.textContent = warehouseItems.length ? 'Isi Qty Fisik untuk setiap item.' : 'Belum ada stok. Cari kode untuk tambah item.';
                        renderRows();
                    })
                    .catch(err => { console.error(err); warehouseItems = []; tbody.innerHTML = ''; itemsHint.textContent = 'Gagal memuat item.'; summaryChange.textContent = 'Selisih: 0.00'; updateSummary(); });
            }

            form.addEventListener('submit', function() {
                let outIndex = 0;
                tbody.querySelectorAll('tr').forEach(tr => {
                    tr.querySelectorAll('[data-named="1"]').forEach(el => el.remove());
                    const changed = tr.dataset.changed === '1';
                    const qtyChange = parseFloat(tr.querySelector('.qty-change-input').value || '0');
                    if (!changed || isNaN(qtyChange) || Math.abs(qtyChange) < 0.000001) return;
                    const itemId = tr.querySelector('.row-item-id').value;
                    const lotId = tr.querySelector('.row-lot-id')?.value || '';
                    const notes = tr.querySelector('.notes-input').value || '';
                    
                    const fields = [['item_id', itemId], ['qty_change', qtyChange.toFixed(2)], ['notes', notes]];
                    if (lotId) fields.push(['lot_id', lotId]);
                    
                    fields.forEach(([name, value]) => {
                        const h = document.createElement('input');
                        h.type = 'hidden'; h.name = `lines[${outIndex}][${name}]`; h.value = value; h.dataset.named = '1';
                        tr.appendChild(h);
                    });
                    outIndex++;
                });
            });

            clearAllBtn.addEventListener('click', function() {
                tbody.querySelectorAll('tr').forEach(tr => {
                    const inp = tr.querySelector('.physical-input');
                    const notes = tr.querySelector('.notes-input');
                    inp.value = ''; notes.value = ''; recalcRow(tr);
                });
                applyFilter(); updateSummary();
            });

            warehouseSelect.addEventListener('change', () => loadItemsForWarehouse(warehouseSelect.value || null));
            itemSearch.addEventListener('focus', () => setTimeout(() => itemSearch.select(), 0));
            itemSearch.addEventListener('input', () => {
                const s = itemSearch.selectionStart, e = itemSearch.selectionEnd;
                itemSearch.value = itemSearch.value.toUpperCase();
                try { itemSearch.setSelectionRange(s, e); } catch (_) {}
                applyFilter(); clearTimeout(searchTimer);
                searchTimer = setTimeout(() => fetchSearchItems((itemSearch.value || '').trim()), 350);
            });
            itemSearch.addEventListener('keydown', (e) => { if (e.key !== 'Enter') return; e.preventDefault(); clearTimeout(searchTimer); fetchSearchItems((itemSearch.value || '').trim(), true); });
            addSearchBtn.addEventListener('click', () => { clearTimeout(searchTimer); addSuggestedItemToTable(); });
            quickPhysicalQty?.addEventListener('focus', () => quickPhysicalQty.select());
            quickPhysicalQty?.addEventListener('keydown', (e) => { if (e.key !== 'Enter') return; e.preventDefault(); addSuggestedItemToTable(); });
            showChangedOnly.addEventListener('change', applyFilter);

            if (warehouseSelect.value) loadItemsForWarehouse(warehouseSelect.value);
        })();
    </script>
@endpush
