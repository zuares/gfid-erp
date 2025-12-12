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

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

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
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h1 class="h5 mb-1">Adjustment Manual</h1>
                        <p class="text-muted mb-1" style="font-size: .86rem;">
                            Pilih gudang → isi <strong>Qty Fisik</strong>. Sistem hitung <strong>Selisih (±)</strong> dan
                            hanya mengirim baris yang benar-benar berubah.
                        </p>

                        @if ($isOwner)
                            <div class="alert alert-info py-2 px-3 mb-0" style="font-size: .8rem;">
                                Sebagai <strong>Owner</strong>, dokumen akan langsung <strong>Approved</strong> dan stok
                                langsung dikoreksi.
                            </div>
                        @else
                            <div class="alert alert-warning py-2 px-3 mb-0" style="font-size: .8rem;">
                                Dokumen akan berstatus <strong>Pending</strong>. Stok belum berubah sampai disetujui Owner.
                            </div>
                        @endif
                    </div>
                    <div>
                        <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-sm btn-outline-secondary">
                            ← Kembali
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('inventory.adjustments.manual.store') }}" id="adj-form">
                    @csrf

                    {{-- HEADER --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
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
                            <div class="form-text">Setelah pilih gudang, daftar item & stok akan muncul.</div>
                            @error('warehouse_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label form-label-sm">Tanggal</label>
                            <input type="date" class="form-control form-control-sm" name="date"
                                value="{{ old('date', now()->toDateString()) }}" required>
                            @error('date')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label form-label-sm">Alasan</label>
                            <input type="text" name="reason" class="form-control form-control-sm"
                                value="{{ old('reason') }}" placeholder="Contoh: Koreksi rak A1">
                            @error('reason')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label form-label-sm">Catatan</label>
                            <textarea name="notes" rows="2" class="form-control form-control-sm"
                                placeholder="Penjelasan tambahan (opsional)">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- TOOLBAR --}}
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="pill-label">Daftar Item di Gudang</div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="width: 230px;">
                                <span class="input-group-text">🔍</span>
                                <input type="text" id="item-search" class="form-control" placeholder="Cari kode / nama…">
                            </div>
                            <div class="form-check form-check-sm">
                                <input class="form-check-input" type="checkbox" value="1" id="show_changed_only">
                                <label class="form-check-label" for="show_changed_only" style="font-size: .78rem;">
                                    Hanya yang ada selisih
                                </label>
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
                                    <th class="text-end" style="width: 130px;">Stok Saat Ini</th>
                                    <th class="text-end" style="width: 150px;">Qty Fisik</th>
                                    <th class="text-end" style="width: 130px;">Selisih (±)</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- render via JS --}}
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-muted" id="items-hint" style="font-size: .78rem;">
                            Pilih gudang terlebih dahulu untuk memuat daftar item.
                        </div>
                        <div class="text-muted" id="summary-change" style="font-size: .78rem;">
                            Total perubahan: 0.00
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clear-all">
                            Reset Qty Fisik
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            Simpan Adjustment
                        </button>
                    </div>
                </form>
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

            const itemsUrl = @json(route('inventory.adjustments.items_for_warehouse'));

            let warehouseItems = []; // {id, code, name, on_hand}

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

                tr.innerHTML = `
            <td data-label="#">${idx + 1}</td>

            <td data-label="Item">
                <div class="fw-semibold text-mono">${item.code ?? ''}</div>
                <div class="text-muted" style="font-size:.82rem;">${item.name ?? ''}</div>

                <input type="hidden" class="row-item-id" value="${item.id}">
            </td>

            <td data-label="Stok saat ini" class="text-end text-mono">
                <span class="on-hand" data-on-hand="${onHand}">${fmt(onHand)}</span>
            </td>

            <td data-label="Qty fisik" class="text-end">
                <input type="number"
                       class="form-control form-control-sm text-end physical-input"
                       step="0.01" min="0" placeholder="0.00">
                <input type="hidden" class="qty-change-input" value="">
            </td>

            <td data-label="Selisih" class="text-end text-mono">
                <span class="diff-display">0.00</span>
            </td>

            <td data-label="Catatan">
                <input type="text" class="form-control form-control-sm notes-input" placeholder="Catatan (opsional)">
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
                const changedOnly = showChangedOnly.checked;

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

                tbody.querySelectorAll('.qty-change-input').forEach(inp => {
                    const v = parseFloat(inp.value);
                    if (!isNaN(v)) total += v;
                });

                let text = fmt(total);
                if (total > 0) text = '+' + text;

                summaryChange.textContent = 'Total perubahan: ' + text;
            }

            function loadItemsForWarehouse(warehouseId) {
                if (!warehouseId) {
                    warehouseItems = [];
                    tbody.innerHTML = '';
                    itemsHint.textContent = 'Pilih gudang terlebih dahulu untuk memuat daftar item.';
                    summaryChange.textContent = 'Total perubahan: 0.00';
                    return;
                }

                itemsHint.textContent = 'Memuat daftar item…';

                fetch(itemsUrl + '?warehouse_id=' + encodeURIComponent(warehouseId))
                    .then(res => {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    })
                    .then(data => {
                        warehouseItems = Array.isArray(data) ? data : [];
                        itemsHint.textContent = warehouseItems.length ?
                            'Isi Qty Fisik. Sistem akan hitung selisih otomatis.' :
                            'Tidak ada item dengan stok di gudang ini.';
                        renderRows();
                    })
                    .catch(err => {
                        console.error(err);
                        warehouseItems = [];
                        tbody.innerHTML = '';
                        itemsHint.textContent = 'Gagal memuat item. Coba reload halaman.';
                        summaryChange.textContent = 'Total perubahan: 0.00';
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
            itemSearch.addEventListener('input', applyFilter);
            showChangedOnly.addEventListener('change', applyFilter);

            // initial load (kalau old('warehouse_id') kebaca)
            if (warehouseSelect.value) {
                loadItemsForWarehouse(warehouseSelect.value);
            }
        })();
    </script>
@endpush
