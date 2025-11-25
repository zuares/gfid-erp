{{-- UNIVERSAL FORM UNTUK CUTTING JOB (CREATE / EDIT) --}}

@php
    $isEdit = $mode === 'edit';
    // dipakai JS untuk saldo LOT
    $lotQty = isset($lotBalance) ? (float) $lotBalance : (isset($qty) ? (float) $qty : 0);
@endphp

<form action="{{ $isEdit ? route('production.cutting_jobs.update', $job) : route('production.cutting_jobs.store') }}"
    method="post">

    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    {{-- =========================
         BAGIAN INFORMASI LOT
    ========================== --}}
    <div class="card p-3 mb-3">
        <h2 class="h6 mb-2">Informasi Lot Kain</h2>

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
                    {{ number_format($lotQty, 2, ',', '.') }}
                </div>
            </div>
        </div>

        {{-- hidden --}}
        <input type="hidden" name="warehouse_id" value="{{ $warehouse?->id }}">
        <input type="hidden" name="lot_id" value="{{ $lot?->id }}">
        <input type="hidden" name="lot_balance" value="{{ $lotQty }}">
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

            <div class="col-md-3 col-12">
                <label class="form-label">Operator Cutting</label>
                <select name="operator_id" class="form-select @error('operator_id') is-invalid @enderror">
                    <option value="">Pilih operator cutting…</option>
                    @foreach ($operators as $op)
                        <option value="{{ $op->id }}" @selected(old('operator_id', $isEdit ? optional($job->bundles->first())->operator_id : null) == $op->id)>
                            {{ $op->code }} — {{ $op->name }}
                        </option>
                    @endforeach
                </select>
                @error('operator_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label class="form-label">Catatan</label>
                <textarea name="notes" rows="2" class="form-control">{{ old('notes', $isEdit ? $job->notes : '') }}</textarea>
            </div>
        </div>
    </div>

    {{-- =========================
         BAGIAN OUTPUT BUNDLES
    ========================== --}}
    <div class="card p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h2 class="h6 mb-0">Output Bundles</h2>
                <div class="help">
                    # diisi otomatis. Pemakaian kain hanya tampilan (dibagi rata dari saldo LOT),
                    nilai final dihitung di server.
                </div>
            </div>

            <button type="button" class="btn btn-sm btn-outline-secondary" id="add-row">
                + Tambah baris
            </button>
        </div>

        {{-- SUMMARY --}}
        <div class="d-flex flex-wrap gap-3 align-items-center mb-1 small">
            <div class="help mb-0">
                Saldo LOT: <span class="mono">{{ number_format($lotQty, 2, ',', '.') }}</span>
            </div>
            <div class="help mb-0">
                Baris: <span class="mono" id="bundle-row-count">0</span>
            </div>
            <div class="help mb-0">
                Total pcs: <span class="mono" id="bundle-total-qty">0,00</span>
            </div>
            <div class="help mb-0">
                Used/baris: <span class="mono" id="bundle-per-row">0,00</span>
            </div>
            <div class="help mb-0">
                Total pemakaian: <span class="mono" id="bundle-total-used">0,00</span>
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
                        <th>Item Category</th>
                        <th style="width:120px;">Used</th>
                        <th style="width:40px;"></th>
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
                                <input type="number" step="1" min="0" inputmode="numeric" pattern="\d*"
                                    name="bundles[{{ $i }}][qty_pcs]"
                                    class="form-control form-control-sm text-end bundle-qty"
                                    value="{{ isset($row['qty_pcs']) ? (int) $row['qty_pcs'] : '' }}">
                            </td>

                            {{-- Item Category (auto) --}}
                            <td data-label="Item Category">
                                <span class="bundle-cat-display help">
                                    {{ $row['item_category'] ?? '-' }}
                                </span>
                                <input type="hidden" name="bundles[{{ $i }}][item_category]"
                                    class="bundle-cat-input" value="{{ $row['item_category'] ?? '' }}">
                            </td>

                            {{-- Used (hanya tampil) --}}
                            <td data-label="Used">
                                <span class="bundle-qty-used help">-</span>
                            </td>

                            {{-- tombol hapus --}}
                            <td data-label="">
                                <button type="button" class="btn btn-sm btn-link text-danger remove-row">×</button>
                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>
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

@push('scripts')
    <script>
        const bundleRows = document.getElementById('bundle-rows');
        const addRowBtn = document.getElementById('add-row');

        const rowCountSpan = document.getElementById('bundle-row-count');
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
            if (perRowSpan) perRowSpan.textContent = perRow.toFixed(2).replace('.', ',');
            if (totalQtySpan) totalQtySpan.textContent = totalQtyPcs.toFixed(2).replace('.', ',');
            if (totalUsedSpan) totalUsedSpan.textContent = totalUsed.toFixed(2).replace('.', ',');

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
    <td data-label="Item Category">
        <span class="bundle-cat-display help">-</span>
        <input type="hidden"
               name="bundles[${index}][item_category]"
               class="bundle-cat-input"
               value="">
    </td>
    <td data-label="Used">
        <span class="bundle-qty-used help">-</span>
    </td>
    <td data-label="">
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

        if (addRowBtn) {
            addRowBtn.addEventListener('click', addRow);
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
