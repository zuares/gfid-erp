{{-- UNIVERSAL FORM UNTUK CUTTING JOB (CREATE / EDIT) --}}

@php
    $isEdit = $mode === 'edit';

    // dipakai JS untuk saldo LOT
    $lotQty = isset($lotBalance) ? (float) $lotBalance : (isset($qty) ? (float) $qty : 0);

    // default operator = MRF (kalau create), kalau edit pakai operator bundle pertama
    $defaultOperatorId = $isEdit
        ? optional($job->bundles->first())->operator_id
        : optional($operators->firstWhere('code', 'MRF'))->id;

    // tanggal beli lot (fallback ke created_at kalau field lain tidak ada)
    $lotPurchaseDate = $lot?->purchased_at ?? ($lot?->purchase_date ?? ($lot?->received_at ?? $lot?->created_at));

    $lotPurchaseDateLabel = $lotPurchaseDate ? $lotPurchaseDate->format('d/m/Y') : '-';
@endphp

<form action="{{ $isEdit ? route('production.cutting_jobs.update', $job) : route('production.cutting_jobs.store') }}"
    method="post">

    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    {{-- MOBILE: INFORMASI LOT SATU BARIS (Tgl Beli • Nama Bahan • Qty) --}}
    <div class="lot-bar-mobile d-md-none mb-2">
        <span class="mono">
            {{ $lotPurchaseDateLabel }}
        </span>
        <span class="mx-1">•</span>
        <span class="fw-semibold">
            {{ $lot?->item?->name ?? '-' }}
        </span>
        <span class="mx-1">•</span>
        <span class="mono">
            {{ number_format($lotQty, 2, ',', '.') }} Kg
        </span>
    </div>
    {{-- hidden --}}
    <input type="hidden" name="warehouse_id" value="{{ $warehouse?->id }}">
    <input type="hidden" name="lot_id" value="{{ $lot?->id }}">
    <input type="hidden" name="lot_balance" value="{{ $lotQty }}">

    {{-- 🔥 fabric item (item kain) ikut dikirim ke backend --}}
    <input type="hidden" name="fabric_item_id"
        value="{{ old('fabric_item_id', $isEdit ? $job->fabric_item_id : $lot?->item_id ?? null) }}">


    {{-- =========================
         BAGIAN INFORMASI LOT (DESKTOP SAJA)
    ========================== --}}
    <div class="card p-3 mb-3 d-none d-md-block">
        <h2 class="h6 mb-2">Informasi Lot Kain</h2>
        @error('fabric_item_id')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror


        {{-- HEADER: Tgl Beli, Nama Bahan, Qty --}}
        <div class="row g-3 mb-2">
            <div class="col-md-4">
                <div class="help mb-1">Tgl Beli</div>
                <div class="mono">
                    {{ $lotPurchaseDateLabel }}
                </div>
            </div>

            <div class="col-md-4">
                <div class="help mb-1">Nama Bahan</div>
                <div>
                    {{ $lot?->item?->name ?? '-' }}
                </div>
            </div>

            <div class="col-md-4">
                <div class="help mb-1">Qty</div>
                <div class="mono">
                    {{ number_format($lotQty, 2, ',', '.') }} Kg
                </div>
            </div>
        </div>

        {{-- DETAIL LOT (kode & gudang) --}}
        <div class="row g-3">
            <div class="col-md-4 col-12">
                <div class="help mb-1">LOT</div>
                <div class="fw-semibold">{{ $lot?->code ?? '-' }}</div>
                <div class="small text-muted">
                    {{ $lot?->item?->code ?? '-' }}
                </div>
            </div>

            <div class="col-md-4 col-6">
                <div class="help mb-1">Gudang</div>
                <div class="mono">{{ $warehouse?->code }} — {{ $warehouse?->name }}</div>
            </div>

            <div class="col-md-4 col-6">
                <div class="help mb-1">Saldo LOT (perkiraan)</div>
                <div class="mono">
                    {{ number_format($lotQty, 2, ',', '.') }} Kg
                </div>
            </div>
        </div>
    </div>

    {{-- =========================
         HEADER JOB
    ========================== --}}
    <div class="card p-3 mb-3">
        <h2 class="h6 mb-2">Header Cutting Job</h2>

        <div class="row g-3">
            <div class="col-md-3 col-6">
                <label class="form-label">Tanggal</label>
                <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                    value="{{ old('date', $isEdit ? $job->date?->format('Y-m-d') : now()->toDateString()) }}">
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- col-6 supaya di mobile satu baris berdua (tanggal + operator) --}}
            <div class="col-md-3 col-6">
                <label class="form-label">Operator Cutting</label>
                @php
                    $currentOperatorId = old('operator_id', $defaultOperatorId);
                @endphp
                <select name="operator_id" class="form-select @error('operator_id') is-invalid @enderror">
                    <option value="">Pilih operator cutting…</option>
                    @foreach ($operators as $op)
                        <option value="{{ $op->id }}" @selected($currentOperatorId == $op->id)>
                            {{ $op->code }} — {{ $op->name }}
                        </option>
                    @endforeach
                </select>
                @error('operator_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Catatan: disembunyikan di mobile, tampil di md+ --}}
            <div class="col-12 d-none d-md-block">
                <label class="form-label">Catatan</label>
                <textarea name="notes" rows="2" class="form-control">{{ old('notes', $isEdit ? $job->notes : '') }}</textarea>
            </div>
        </div>
    </div>

    {{-- =========================
         BAGIAN OUTPUT BUNDLES
    ========================== --}}
    <div class="card p-3 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-2">
            <div class="mb-2 mb-md-0">
                <h2 class="h6 mb-0">Output Bundles</h2>
            </div>

            {{-- tombol atas hanya untuk desktop --}}
            <div class="w-100 w-md-auto d-none d-md-block">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="add-row-top">
                    + Tambah baris
                </button>
            </div>
        </div>

        <div id="bundle-warning" class="text-danger small mb-2" style="display:none;">
            ⚠️ Total pemakaian kain > saldo LOT
        </div>

        {{-- TABLE (desktop) + data-label (mobile) --}}
        <div class="table-wrap">
            <table class="table table-sm align-middle mono">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th style="width:180px;">Item Jadi</th>
                        <th style="width:110px;">Qty (pcs)</th>

                        {{-- Item Category: hanya tampil di md+ --}}
                        <th class="d-none d-md-table-cell">Item Category</th>

                        {{-- Used: hanya tampil di md+ --}}
                        <th style="width:120px;" class="d-none d-md-table-cell">Used</th>

                        {{-- tombol hapus: hanya tampil di md+ --}}
                        <th style="width:40px;" class="d-none d-md-table-cell"></th>
                    </tr>
                </thead>
                <tbody id="bundle-rows">

                    @foreach ($rows as $i => $row)
                        <tr>
                            {{-- bundle_id (untuk edit) --}}
                            @if (!empty($row['id']))
                                <input type="hidden" name="bundles[{{ $i }}][id]"
                                    value="{{ $row['id'] }}">
                            @endif

                            {{-- # otomatis --}}
                            <td data-label="#">
                                <span class="row-index mono"></span>
                            </td>

                            {{-- Item Jadi --}}
                            <td data-label="Item Jadi">
                                <select name="bundles[{{ $i }}][finished_item_id]"
                                    class="form-select form-select-sm bundle-item-select">
                                    <option value="">Pilih...</option>
                                    @foreach ($items as $fg)
                                        <option value="{{ $fg->id }}"
                                            data-category-name="{{ $fg->category->name ?? '' }}"
                                            data-category-code="{{ $fg->category->code ?? '' }}"
                                            @selected(($row['finished_item_id'] ?? null) == $fg->id)>
                                            {{ $fg->code }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            {{-- Qty (pcs) --}}
                            <td data-label="Qty (pcs)">
                                <input type="number" step="1" min="0" inputmode="numeric"
                                    pattern="\d*" name="bundles[{{ $i }}][qty_pcs]"
                                    class="form-control form-control-sm text-end bundle-qty"
                                    value="{{ isset($row['qty_pcs']) ? (int) $row['qty_pcs'] : '' }}">
                            </td>

                            {{-- Item Category (auto) - sembunyi di mobile --}}
                            <td data-label="Item Category" class="d-none d-md-table-cell">
                                <span class="bundle-cat-display help">
                                    {{ $row['item_category'] ?? '-' }}
                                </span>
                                <input type="hidden" name="bundles[{{ $i }}][item_category]"
                                    class="bundle-cat-input" value="{{ $row['item_category'] ?? '' }}">
                            </td>

                            {{-- Used (hanya tampil di desktop) --}}
                            <td data-label="Used" class="d-none d-md-table-cell">
                                <span class="bundle-qty-used help">-</span>
                            </td>

                            {{-- tombol hapus - sembunyi di mobile --}}
                            <td data-label="" class="d-none d-md-table-cell">
                                <button type="button" class="btn btn-sm btn-link text-danger remove-row">×</button>
                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>

        {{-- tombol bawah khusus mobile --}}
        <div class="mt-2 d-md-none">
            <button type="button" class="btn btn-outline-secondary w-100 btn-sm" id="add-row-bottom">
                + Tambah baris
            </button>
        </div>

        @error('bundles')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    {{-- TOMBOL --}}
    <div class="d-flex justify-content-end mb-4">
        <button class="btn btn-primary">
            {{ $isEdit ? 'Update Cutting Job' : 'Simpan Cutting Job' }}
        </button>
    </div>

</form>

@push('head')
    <style>
        .lot-bar-mobile {
            position: sticky;
            top: 56px;
            /* sesuaikan dengan tinggi navbar-mu */
            z-index: 1020;
            background: var(--card, #fff);
            border-bottom: 1px solid var(--line, #e5e7eb);
            padding: .35rem .75rem;
            font-size: .85rem;
            display: flex;
            align-items: center;
            gap: .25rem;
            white-space: nowrap;
            overflow-x: auto;
        }

        @media (max-width: 767.98px) {

            /* Biar header Output Bundles dan tombol rapi di mobile */
            .card .d-flex.flex-column.flex-md-row {
                gap: .5rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        const bundleRows = document.getElementById('bundle-rows');
        const addRowBtnTop = document.getElementById('add-row-top');
        const addRowBtnBottom = document.getElementById('add-row-bottom');

        const rowCountSpan = document.getElementById('bundle-row-count'); // elemen summary sudah tidak ada, aman
        const perRowSpan = document.getElementById('bundle-per-row');
        const totalQtySpan = document.getElementById('bundle-total-qty');
        const totalUsedSpan = document.getElementById('bundle-total-used');
        const warningEl = document.getElementById('bundle-warning');

        // saldo LOT ke JS
        const lotQty = {{ $lotQty }};

        function attachSelectAllOnFocus(input) {
            input.addEventListener('focus', function() {
                setTimeout(() => this.select(), 0);
            });
            input.addEventListener('mouseup', function(e) {
                e.preventDefault();
            });
        }

        function renumberRows() {
            if (!bundleRows) return;
            const rows = bundleRows.querySelectorAll('tr');
            rows.forEach((tr, idx) => {
                const idxSpan = tr.querySelector('.row-index');
                if (idxSpan) {
                    idxSpan.textContent = idx + 1;
                }
            });
        }

        function recalcAll() {
            if (!bundleRows) return;

            const rows = bundleRows.querySelectorAll('tr');
            const count = rows.length;

            let totalQtyPcs = 0;

            rows.forEach(tr => {
                const qtyInput = tr.querySelector('.bundle-qty');
                if (!qtyInput) return;

                let v = parseInt(qtyInput.value || '0', 10);
                if (isNaN(v) || v < 0) v = 0;

                // normalisasi kembali ke input (kalau user ketik 1.5 jadi 1)
                qtyInput.value = v;

                totalQtyPcs += v;
            });

            const perRow = (count > 0 && lotQty > 0) ? (lotQty / count) : 0;
            const totalUsed = perRow * count;

            if (rowCountSpan) rowCountSpan.textContent = count;
            if (perRowSpan) perRowSpan.textContent = perRow?.toFixed ? perRow.toFixed(2).replace('.', ',') : '';
            if (totalQtySpan) totalQtySpan.textContent = totalQtyPcs.toFixed(2).replace('.', ',');
            if (totalUsedSpan) totalUsedSpan.textContent = totalUsed?.toFixed ? totalUsed.toFixed(2).replace('.', ',') : '';

            if (warningEl) {
                warningEl.style.display = (totalUsed > lotQty + 0.000001) ? 'block' : 'none';
            }

            rows.forEach(tr => {
                const usedSpan = tr.querySelector('.bundle-qty-used');
                if (usedSpan) {
                    usedSpan.textContent = perRow ? perRow.toFixed(2).replace('.', ',') : '-';
                }
            });
        }

        function recalcRowCategory(tr) {
            const select = tr.querySelector('.bundle-item-select');
            const catSpan = tr.querySelector('.bundle-cat-display');
            const catInput = tr.querySelector('.bundle-cat-input');

            if (!select || !select.value) {
                if (catSpan) catSpan.textContent = '-';
                if (catInput) catInput.value = '';
                return;
            }

            const opt = select.selectedOptions[0];
            const catName = opt.getAttribute('data-category-name') ||
                opt.getAttribute('data-category-code') ||
                '-';

            if (catSpan) catSpan.textContent = catName || '-';
            if (catInput) catInput.value = catName || '';
        }

        function attachRowListeners(tr) {
            const select = tr.querySelector('.bundle-item-select');
            const qtyInput = tr.querySelector('.bundle-qty');

            if (select) {
                select.addEventListener('change', function() {
                    recalcRowCategory(tr);
                });
            }

            if (qtyInput) {
                attachSelectAllOnFocus(qtyInput);
                qtyInput.addEventListener('input', function() {
                    recalcAll();
                });
            }

            recalcRowCategory(tr);
        }

        function addRow() {
            if (!bundleRows) return;

            const index = bundleRows.children.length;

            const html = `
<tr>
    <td data-label="#">
        <span class="row-index mono"></span>
    </td>
    <td data-label="Item Jadi">
        <select name="bundles[${index}][finished_item_id]"
                class="form-select form-select-sm bundle-item-select">
            <option value="">Pilih...</option>
            @foreach ($items as $fg)
                <option value="{{ $fg->id }}"
                        data-category-name="{{ $fg->category->name ?? '' }}"
                        data-category-code="{{ $fg->category->code ?? '' }}">
                    {{ $fg->code }}
                </option>
            @endforeach
        </select>
    </td>
    <td data-label="Qty (pcs)">
        <input type="number"
               step="1"
               min="0"
               inputmode="numeric"
               pattern="\\d*"
               name="bundles[${index}][qty_pcs]"
               class="form-control form-control-sm text-end bundle-qty">
    </td>
    <td data-label="Item Category" class="d-none d-md-table-cell">
        <span class="bundle-cat-display help">-</span>
        <input type="hidden"
               name="bundles[${index}][item_category]"
               class="bundle-cat-input"
               value="">
    </td>
    <td data-label="Used" class="d-none d-md-table-cell">
        <span class="bundle-qty-used help">-</span>
    </td>
    <td data-label="" class="d-none d-md-table-cell">
        <button type="button"
                class="btn btn-sm btn-link text-danger remove-row">×</button>
    </td>
</tr>`;

            bundleRows.insertAdjacentHTML('beforeend', html);

            const newRow = bundleRows.lastElementChild;
            attachRowListeners(newRow);
            renumberRows();
            recalcAll();
        }

        // init existing rows (create & edit)
        if (bundleRows) {
            Array.from(bundleRows.querySelectorAll('tr')).forEach(tr => {
                attachRowListeners(tr);
            });
            renumberRows();
            recalcAll();
        }

        if (addRowBtnTop) {
            addRowBtnTop.addEventListener('click', addRow);
        }
        if (addRowBtnBottom) {
            addRowBtnBottom.addEventListener('click', addRow);
        }

        // hapus baris
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-row')) {
                const tr = e.target.closest('tr');
                if (!tr || !bundleRows) return;

                tr.parentNode.removeChild(tr);
                renumberRows();
                recalcAll();
            }
        });
    </script>
@endpush
