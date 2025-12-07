{{-- resources/views/production/cutting_jobs/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Cutting Job Baru')

@push('head')
    <style>
        .cutting-create-page {
            min-height: 100vh;
        }

        .cutting-create-page .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: 1rem 1rem 3rem;
        }

        body[data-theme="light"] .cutting-create-page .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.12) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 60%);
        }

        .cutting-card {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow:
                0 10px 40px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(148, 163, 184, 0.18);
            margin-bottom: 1rem;
        }

        .cutting-card-header {
            padding: .75rem 1rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.35);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
        }

        .cutting-card-header h5 {
            margin: 0;
            font-size: .95rem;
            font-weight: 600;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .cutting-card-body {
            padding: .9rem 1rem 1rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .1rem .6rem;
            font-size: .7rem;
            border: 1px solid rgba(148, 163, 184, 0.5);
            background: rgba(148, 163, 184, 0.09);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .lot-list-table {
            width: 100%;
            font-size: .8rem;
        }

        .lot-list-table th,
        .lot-list-table td {
            padding: .35rem .45rem;
        }

        .lot-list-table thead th {
            border-bottom: 1px solid rgba(148, 163, 184, 0.5);
        }

        .lot-list-table tbody tr:nth-child(odd) {
            background: rgba(148, 163, 184, 0.05);
        }

        .lot-list-table tbody tr.lot-hidden {
            display: none;
        }

        .bundles-table-wrap {
            overflow-x: auto;
        }

        .bundles-table {
            width: 100%;
            font-size: .82rem;
            border-collapse: separate;
            border-spacing: 0;
        }

        .bundles-table thead th {
            position: sticky;
            top: 0;
            background: var(--card);
            z-index: 1;
            padding: .45rem .5rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.7);
            white-space: nowrap;
        }

        .bundles-table tbody td {
            padding: .35rem .5rem;
            vertical-align: middle;
        }

        .bundles-table tbody tr:nth-child(odd) {
            background: rgba(148, 163, 184, 0.04);
        }

        .bundles-table tfoot td {
            padding: .4rem .5rem;
            border-top: 1px solid rgba(148, 163, 184, 0.5);
        }

        .bundle-row-deleted {
            opacity: .4;
        }

        .lot-summary-list {
            list-style: none;
            padding-left: 0;
            margin: 0;
            font-size: .8rem;
        }

        .lot-summary-list li {
            display: flex;
            justify-content: space-between;
            gap: .5rem;
            padding: .15rem 0;
        }

        .lot-summary-list li.over {
            color: #b91c1c;
            font-weight: 600;
        }

        .btn-pill-sm {
            border-radius: 999px;
            font-size: .78rem;
            padding-inline: .8rem;
            padding-block: .15rem;
        }

        /* LOT di bundles disembunyikan di mobile, tapi tetap ada di DOM */
        @media (max-width: 767.98px) {
            .cutting-card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .bundles-table thead th:nth-child(2),
            .bundles-table tbody td:nth-child(2) {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $fabricItems = $lotStocks->map(fn($row) => $row->lot->item)->unique('id')->values();
    @endphp

    <div class="cutting-create-page">
        <div class="page-wrap">
            {{-- FLASH --}}
            @if (session('success'))
                <div class="alert alert-success py-2 px-3 mb-2">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger py-2 px-3 mb-2">
                    <div class="small fw-semibold mb-1">Terjadi kesalahan:</div>
                    <ul class="mb-0 small">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('production.cutting_jobs.store') }}" method="POST" id="cutting-form">
                @csrf

                {{-- dipakai untuk auto hitung kain pakai di backend --}}
                <input type="hidden" name="lot_balance" id="lot_balance" value="{{ old('lot_balance', 0) }}">

                {{-- CARD: HEADER CUTTING JOB --}}
                <div class="cutting-card">
                    <div class="cutting-card-header">
                        <h5>Info Cutting Job</h5>
                        <span class="badge-soft">
                            Mode: <span class="fw-semibold">Create</span>
                        </span>
                    </div>
                    <div class="cutting-card-body">
                        <div class="row g-2">
                            <div class="col-md-3 col-6">
                                <label class="form-label small">Tanggal</label>
                                <input type="date" name="date" class="form-control form-control-sm"
                                    value="{{ old('date', now()->toDateString()) }}">
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small">Warehouse</label>
                                <select name="warehouse_id" class="form-select form-select-sm">
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}" @selected(old('warehouse_id') == $wh->id)>
                                            {{ $wh->code }} - {{ $wh->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small">Operator Cutting</label>
                                <select name="operator_id" class="form-select form-select-sm">
                                    <option value="">- Pilih Operator -</option>
                                    @foreach ($operators as $op)
                                        <option value="{{ $op->id }}" @selected(old('operator_id') == $op->id)>
                                            {{ $op->code }} - {{ $op->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small">Catatan</label>
                                <input type="text" name="notes" class="form-control form-control-sm"
                                    value="{{ old('notes') }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD: PILIH KAIN & LOT --}}
                <div class="cutting-card">
                    <div class="cutting-card-header">
                        <h5>Kain & LOT</h5>
                        <span class="badge-soft">
                            Step 1: Pilih kain, lalu centang LOT yang akan dipakai
                        </span>
                    </div>
                    <div class="cutting-card-body">
                        {{-- PILIH KAIN (fabric item) --}}
                        <div class="mb-3">
                            <label class="form-label small mb-1">Item Kain</label>
                            <select name="fabric_item_id" id="fabric_item_id" class="form-select form-select-sm">
                                <option value="">- Pilih Kain -</option>
                                @foreach ($fabricItems as $item)
                                    <option value="{{ $item->id }}" @selected(old('fabric_item_id') == $item->id)>
                                        {{ $item->code }} - {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- DAFTAR LOT --}}
                        <div class="mb-2 d-flex justify-content-between align-items-center">
                            <div class="small text-muted">
                                Centang LOT yang akan dipakai untuk job ini.
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-outline-secondary btn-pill-sm"
                                    id="btn-select-all-lots">
                                    Centang semua LOT
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-pill-sm"
                                    id="btn-unselect-all-lots">
                                    Hapus centang
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="lot-list-table table table-sm mb-0 mono">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;"></th>
                                        <th>LOT</th>
                                        <th>Item</th>
                                        <th class="text-end">Saldo</th>
                                        <th>Gudang</th>
                                    </tr>
                                </thead>
                                <tbody id="lot-list-body">
                                    @foreach ($lotStocks as $row)
                                        @php
                                            $lot = $row->lot;
                                            $item = $lot->item;
                                            $wh = $row->warehouse;
                                        @endphp
                                        <tr class="lot-row" data-lot-id="{{ $row->lot_id }}"
                                            data-item-id="{{ $item->id }}" data-balance="{{ $row->qty_balance }}">
                                            <td>
                                                <input type="checkbox" class="form-check-input lot-checkbox"
                                                    name="selected_lots[]" value="{{ $row->lot_id }}">
                                            </td>
                                            <td>{{ $lot->code }}</td>
                                            <td>{{ $item->code }}</td>
                                            <td class="text-end">
                                                {{ number_format($row->qty_balance, 2, ',', '.') }}
                                            </td>
                                            <td>{{ $wh->code ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

                {{-- CARD: BUNDLES --}}
                <div class="cutting-card">
                    <div class="cutting-card-header">
                        <h5>Bundles</h5>
                        <span class="badge-soft">
                            Step 2: Input baris bundles (LOT & kain pakai auto)
                        </span>
                    </div>
                    <div class="cutting-card-body">
                        <div class="bundles-table-wrap mb-2">
                            <table class="bundles-table table table-sm" id="bundles-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th style="min-width: 120px;">LOT</th>
                                        <th style="min-width: 150px;">Item Jadi</th>
                                        <th style="min-width: 90px;" class="text-end">Qty (pcs)</th>
                                        <th style="min-width: 150px;">Catatan</th>
                                        <th style="width: 40px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="bundle-rows">
                                    {{-- Baris awal (1 row default) akan di-generate oleh JS --}}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-pill-sm"
                                                id="btn-add-row">
                                                + Tambah Baris
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- TEMPLATE ROW (hidden) --}}
                        <template id="bundle-row-template">
                            <tr class="bundle-row">
                                <td class="bundle-index mono">1</td>
                                <td>
                                    {{-- LOT di-auto oleh JS, user tidak perlu pilih --}}
                                    <select class="form-select form-select-sm bundle-lot-select"
                                        name="bundles[__INDEX__][lot_id]">
                                        {{-- options diisi via JS berdasarkan LOT tercentang --}}
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm"
                                        name="bundles[__INDEX__][finished_item_id]">
                                        <option value="">- Pilih Item Jadi -</option>
                                        @foreach ($items as $fg)
                                            <option value="{{ $fg->id }}">
                                                {{ $fg->code }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0"
                                        class="form-control form-control-sm text-end bundle-qty-pcs"
                                        name="bundles[__INDEX__][qty_pcs]">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm"
                                        name="bundles[__INDEX__][notes]">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger btn-remove-row">
                                        &times;
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </div>
                </div>

                {{-- CARD: SUMMARY LOT --}}
                <div class="cutting-card">
                    <div class="cutting-card-header">
                        <h5>Ringkasan Kain & Bundles</h5>
                        <span class="badge-soft">
                            Info total kain tersedia & estimasi pemakaian
                        </span>
                    </div>
                    <div class="cutting-card-body">
                        <ul class="lot-summary-list" id="lot-summary-list">
                            <li class="text-muted">
                                <span>Belum ada pemakaian.</span>
                                <span></span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- ACTIONS --}}
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <a href="{{ route('production.cutting_jobs.index') }}" class="btn btn-outline-secondary btn-sm">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm">
                        Simpan Cutting Job
                    </button>
                </div>
            </form>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lotRows = Array.from(document.querySelectorAll('.lot-row'));
            const lotCheckboxes = Array.from(document.querySelectorAll('.lot-checkbox'));
            const fabricSelect = document.getElementById('fabric_item_id');
            const btnSelectAllLots = document.getElementById('btn-select-all-lots');
            const btnUnselectAllLots = document.getElementById('btn-unselect-all-lots');

            const bundlesTbody = document.getElementById('bundle-rows');
            const bundleTemplate = document.getElementById('bundle-row-template');
            const btnAddRow = document.getElementById('btn-add-row');
            const lotSummaryList = document.getElementById('lot-summary-list');
            const lotBalanceInput = document.getElementById('lot_balance');

            // Build map lotId -> info
            const lotInfoMap = {};
            lotRows.forEach(tr => {
                const lotId = parseInt(tr.dataset.lotId, 10);
                const itemId = parseInt(tr.dataset.itemId, 10);
                const balance = parseFloat(tr.dataset.balance ?? '0');
                const code = tr.querySelector('td:nth-child(2)')?.textContent?.trim() ?? '';
                lotInfoMap[lotId] = {
                    lotId,
                    itemId,
                    code,
                    balance
                };
            });

            let bundleIndexCounter = 0;

            function getCheckedLots() {
                const ids = [];
                lotCheckboxes.forEach(cb => {
                    if (cb.checked) {
                        ids.push(parseInt(cb.value, 10));
                    }
                });
                return ids;
            }

            function filterLotsByFabric() {
                const selectedItemId = parseInt(fabricSelect.value || '0', 10);

                lotRows.forEach(tr => {
                    const itemId = parseInt(tr.dataset.itemId, 10);
                    if (!selectedItemId || itemId === selectedItemId) {
                        tr.classList.remove('lot-hidden');
                    } else {
                        tr.classList.add('lot-hidden');
                    }
                });
            }

            function recalcLotBalanceFromCheckedLots() {
                let total = 0;
                lotCheckboxes.forEach(cb => {
                    if (cb.checked) {
                        const row = cb.closest('.lot-row');
                        if (!row) return;
                        const balance = parseFloat(row.dataset.balance || '0');
                        total += balance;
                    }
                });
                lotBalanceInput.value = total.toFixed(2);
            }

            function rebuildLotOptionsForAllRows() {
                const checkedLotIds = getCheckedLots();
                const selects = bundlesTbody.querySelectorAll('.bundle-lot-select');

                selects.forEach(select => {
                    const currentVal = parseInt(select.value || '0', 10);
                    while (select.firstChild) {
                        select.removeChild(select.firstChild);
                    }

                    const optPlaceholder = document.createElement('option');
                    optPlaceholder.value = '';
                    optPlaceholder.textContent = checkedLotIds.length ? '- LOT auto -' :
                        'Tidak ada LOT terpilih';
                    select.appendChild(optPlaceholder);

                    checkedLotIds.forEach(lotId => {
                        const info = lotInfoMap[lotId];
                        if (!info) return;
                        const opt = document.createElement('option');
                        opt.value = lotId;
                        opt.textContent = info.code;
                        select.appendChild(opt);
                    });

                    if (checkedLotIds.length > 0 && !currentVal) {
                        select.value = checkedLotIds[0];
                    } else if (currentVal && checkedLotIds.includes(currentVal)) {
                        select.value = currentVal;
                    }
                });
            }

            function updateBundleRowIndices() {
                const rows = bundlesTbody.querySelectorAll('.bundle-row');
                rows.forEach((tr, idx) => {
                    const numCell = tr.querySelector('.bundle-index');
                    if (numCell) {
                        numCell.textContent = idx + 1;
                    }
                });
            }

            function createBundleRow() {
                const frag = bundleTemplate.content.cloneNode(true);
                const tr = frag.querySelector('tr');
                const idx = bundleIndexCounter++;

                tr.querySelectorAll('[name]').forEach(el => {
                    const name = el.getAttribute('name');
                    if (name && name.includes('__INDEX__')) {
                        el.setAttribute('name', name.replace('__INDEX__', idx));
                    }
                });

                const qtyInput = tr.querySelector('.bundle-qty-pcs');
                if (qtyInput) {
                    qtyInput.addEventListener('input', recalcLotSummary);
                }

                const lotSelect = tr.querySelector('.bundle-lot-select');
                if (lotSelect) {
                    lotSelect.addEventListener('change', recalcLotSummary);
                }

                const btnRemove = tr.querySelector('.btn-remove-row');
                if (btnRemove) {
                    btnRemove.addEventListener('click', () => {
                        tr.remove();
                        updateBundleRowIndices();
                        recalcLotSummary();
                    });
                }

                bundlesTbody.appendChild(tr);
                updateBundleRowIndices();
                rebuildLotOptionsForAllRows();
                recalcLotSummary();
            }

            function recalcLotSummary() {
                const rows = bundlesTbody.querySelectorAll('.bundle-row');
                let totalPcs = 0;
                let validRowCount = 0;

                rows.forEach(tr => {
                    const qtyInput = tr.querySelector('.bundle-qty-pcs');
                    if (!qtyInput) return;
                    const qty = parseFloat(qtyInput.value || '0');
                    if (qty > 0) {
                        totalPcs += qty;
                        validRowCount++;
                    }
                });

                const totalBalance = parseFloat(lotBalanceInput.value || '0');

                while (lotSummaryList.firstChild) {
                    lotSummaryList.removeChild(lotSummaryList.firstChild);
                }

                if (totalBalance <= 0 && totalPcs <= 0) {
                    const li = document.createElement('li');
                    li.classList.add('text-muted');
                    li.textContent = 'Belum ada pemilihan LOT atau qty bundle.';
                    lotSummaryList.appendChild(li);
                    return;
                }

                const li1 = document.createElement('li');
                li1.innerHTML =
                    `<span>Total kain tersedia (dari LOT):</span><span class="mono">${totalBalance.toFixed(2)}</span>`;
                lotSummaryList.appendChild(li1);

                const li2 = document.createElement('li');
                li2.innerHTML =
                    `<span>Total qty pcs bundles:</span><span class="mono">${totalPcs.toFixed(2)}</span>`;
                lotSummaryList.appendChild(li2);

                if (validRowCount > 0) {
                    const perRow = totalBalance / validRowCount;
                    const li3 = document.createElement('li');
                    li3.innerHTML =
                        `<span>Estimasi kain per baris (bagi rata):</span><span class="mono">${perRow.toFixed(2)}</span>`;
                    lotSummaryList.appendChild(li3);
                }

                if (totalPcs > 0 && totalBalance > 0) {
                    const perPcs = totalBalance / totalPcs;
                    const li4 = document.createElement('li');
                    li4.innerHTML =
                        `<span>Estimasi kain per pcs:</span><span class="mono">${perPcs.toFixed(4)}</span>`;
                    lotSummaryList.appendChild(li4);
                }
            }

            // events
            fabricSelect?.addEventListener('change', () => {
                filterLotsByFabric();
            });

            lotCheckboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    rebuildLotOptionsForAllRows();
                    recalcLotBalanceFromCheckedLots();
                    recalcLotSummary();
                });
            });

            btnSelectAllLots?.addEventListener('click', () => {
                lotCheckboxes.forEach(cb => {
                    if (!cb.closest('.lot-row')?.classList.contains('lot-hidden')) {
                        cb.checked = true;
                    }
                });
                rebuildLotOptionsForAllRows();
                recalcLotBalanceFromCheckedLots();
                recalcLotSummary();
            });

            btnUnselectAllLots?.addEventListener('click', () => {
                lotCheckboxes.forEach(cb => cb.checked = false);
                rebuildLotOptionsForAllRows();
                recalcLotBalanceFromCheckedLots();
                recalcLotSummary();
            });

            btnAddRow?.addEventListener('click', () => {
                createBundleRow();
            });

            // INIT
            filterLotsByFabric();
            recalcLotBalanceFromCheckedLots();
            createBundleRow(); // minimal 1 baris
        });
    </script>
@endpush
