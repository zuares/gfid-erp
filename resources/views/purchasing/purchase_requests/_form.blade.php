{{--
    Shared form untuk create & edit Purchase Request.

    Props yang harus di-pass:
      $suppliers    — Collection suppliers
      $items        — Collection items
      $canSeeMoney  — bool
      $linesData    — array (kosong untuk create, lines->toArray() untuk edit)
      $pr           — PurchaseRequest|null (untuk edit; null untuk create)
--}}

@push('head')
<style>
    .pr-card {
        background: var(--card);
        border-radius: 18px;
        border: 1px solid var(--line);
        margin-bottom: 1rem;
        overflow: visible;
    }
    .pr-card .card-body {
        padding: 1.25rem 1.5rem 1.35rem;
    }
    .pr-card .card-header {
        padding: .9rem 1.5rem;
    }
    .pr-label {
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--muted);
        margin-bottom: .25rem;
    }
    .pr-field {
        border-radius: 12px;
        font-size: .9rem;
    }
    .pr-lines-table thead th {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--muted);
        white-space: nowrap;
        border-bottom-width: 1px;
    }
    .pr-lines-table tbody td {
        vertical-align: middle;
        padding: .65rem 1rem;
    }
    .pr-col-no { width: 4%; min-width: 32px; }
    .pr-col-qty { width: 12%; min-width: 90px; }
    .pr-col-price { width: 15%; min-width: 110px; }
    .pr-col-notes { width: 22%; }
    .pr-col-action { width: 5%; min-width: 48px; }

    @media (max-width: 768px) {
        .pr-card .card-body { padding: 1rem; }
        .pr-card .card-header { padding: .8rem 1rem; }
        .pr-lines-table thead { display: none; }
        .pr-lines-table tbody tr {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-areas:
                "no no"
                "item item"
                "qty price"
                "linenotes linenotes"
                "action action";
            border: 1px solid var(--line);
            border-radius: 14px;
            margin-bottom: .6rem;
            padding: .5rem;
        }
        .pr-lines-table tbody td { border: none; padding: .25rem .3rem; }
        .pr-td-no      { grid-area: no; font-size: .72rem; color: var(--muted); }
        .pr-td-item    { grid-area: item; }
        .pr-td-qty     { grid-area: qty; }
        .pr-td-price   { grid-area: price; }
        .pr-td-notes   { grid-area: linenotes; }
        .pr-td-action  { grid-area: action; text-align: center; }
    }
</style>
@endpush

{{-- HEADER --}}
<div class="pr-card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-3">
                <div class="pr-label">Tanggal</div>
                <input type="text" name="date"
                    value="{{ old('date', isset($pr) ? $pr->date?->format('d/m/Y') : now()->format('d/m/Y')) }}"
                    class="form-control pr-field gf-date-input @error('date') is-invalid @enderror"
                    data-gf-date autocomplete="off">
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-9">
                <div class="pr-label">Supplier Awal</div>
                <select name="supplier_id" class="form-select pr-field @error('supplier_id') is-invalid @enderror">
                    <option value="">Otomatis per barang / kategori</option>
                    @foreach ($suppliers as $sup)
                        <option value="{{ $sup->id }}"
                            @selected(old('supplier_id', $pr?->supplier_id ?? null) == $sup->id)>
                            {{ $sup->code }} — {{ $sup->name }}
                        </option>
                    @endforeach
                </select>
                @error('supplier_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <div class="pr-label">Catatan <span class="fw-normal text-muted">(opsional)</span></div>
                <textarea name="notes" rows="2"
                    class="form-control pr-field @error('notes') is-invalid @enderror"
                    placeholder="Kebutuhan khusus atau informasi tambahan">{{ old('notes', $pr?->notes ?? '') }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- LINES --}}
<div class="pr-card">
    <div class="card-header d-flex justify-content-between align-items-center"
        style="background:transparent; border-bottom:1px solid var(--line); padding:.85rem 1rem;">
        <div class="fw-semibold" style="font-size:.95rem;">Detail Barang</div>
        <button type="button" id="btn-add-pr-line" class="btn btn-sm btn-outline-primary" style="border-radius:12px;">
            + Tambah Baris
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-sm mb-0 pr-lines-table" id="pr-lines-table">
            <thead class="table-light">
                <tr>
                    <th class="pr-col-no text-center">No</th>
                    <th>Item</th>
                    <th class="text-end pr-col-qty">Qty</th>
                    @if ($canSeeMoney)
                        <th class="text-end pr-col-price">Harga Est.</th>
                    @endif
                    <th class="pr-col-notes">Catatan Baris</th>
                    <th class="pr-col-action"></th>
                </tr>
            </thead>

            <tbody id="pr-lines-body">
                @forelse ($linesData as $i => $line)
                    @php
                        $lineItemId   = $line['item_id'] ?? null;
                        $lineItemCode = $line['item']['code'] ?? null;
                        $lineItemName = $line['item']['name'] ?? null;
                        $lineItemDisp = $lineItemName ?: $lineItemCode;

                        $qtyRaw     = $line['qty'] ?? '';
                        $qtyDisplay = ($qtyRaw !== '' && $qtyRaw !== null)
                            ? number_format((float) $qtyRaw, 2, ',', '.') : '';

                        $priceRaw     = $line['unit_price'] ?? '';
                        $priceDisplay = ($priceRaw !== '' && $priceRaw !== null)
                            ? number_format((float) $priceRaw, 0, ',', '.') : '';
                    @endphp
                    <tr class="pr-line-row">
                        <td class="text-center align-middle pr-td-no pr-col-no">{{ $loop->iteration }}</td>

                        <td class="pr-td-item">
                            <x-item-suggest
                                :items="$items"
                                idName="lines[{{ $i }}][item_id]"
                                :idValue="$lineItemId"
                                :displayValue="$lineItemDisp"
                                variant="mini"
                                displayMode="name"
                                :minChars="1" />
                            @error("lines.$i.item_id")
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </td>

                        <td class="pr-td-qty" data-label="Qty">
                            <input type="text"
                                class="form-control form-control-sm pr-field pr-num-display pr-qty-display"
                                inputmode="decimal" placeholder="0,00"
                                value="{{ $qtyDisplay }}" autocomplete="off">
                            <input type="hidden" name="lines[{{ $i }}][qty]"
                                class="pr-qty-raw" value="{{ $qtyRaw }}">
                            @error("lines.$i.qty")
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </td>

                        @if ($canSeeMoney)
                            <td class="pr-td-price" data-label="Harga Est.">
                                <input type="text"
                                    class="form-control form-control-sm pr-field pr-num-display pr-price-display"
                                    inputmode="numeric" placeholder="0"
                                    value="{{ $priceDisplay }}" autocomplete="off">
                                <input type="hidden" name="lines[{{ $i }}][unit_price]"
                                    class="pr-price-raw" value="{{ $priceRaw }}">
                                @error("lines.$i.unit_price")
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </td>
                        @endif

                        <td class="pr-td-notes" data-label="Catatan">
                            <input type="text"
                                class="form-control form-control-sm pr-field"
                                name="lines[{{ $i }}][notes]"
                                value="{{ old("lines.$i.notes", $line['notes'] ?? '') }}"
                                placeholder="opsional" maxlength="255" autocomplete="off">
                        </td>

                        <td class="pr-td-action text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger pr-btn-remove-line"
                                style="border-radius:10px;" title="Hapus baris">&times;</button>
                        </td>
                    </tr>
                @empty
                    {{-- Baris kosong awal (create) --}}
                    <tr class="pr-line-row">
                        <td class="text-center align-middle pr-td-no pr-col-no">1</td>

                        <td class="pr-td-item">
                            <x-item-suggest
                                :items="$items"
                                idName="lines[0][item_id]"
                                variant="mini"
                                displayMode="name"
                                placeholder="Cari nama barang"
                                :autofocus="true"
                                :minChars="1" />
                        </td>

                        <td class="pr-td-qty" data-label="Qty">
                            <input type="text"
                                class="form-control form-control-sm pr-field pr-num-display pr-qty-display"
                                inputmode="decimal" placeholder="0,00" autocomplete="off">
                            <input type="hidden" name="lines[0][qty]" class="pr-qty-raw" value="">
                        </td>

                        @if ($canSeeMoney)
                            <td class="pr-td-price" data-label="Harga Est.">
                                <input type="text"
                                    class="form-control form-control-sm pr-field pr-num-display pr-price-display"
                                    inputmode="numeric" placeholder="0" autocomplete="off">
                                <input type="hidden" name="lines[0][unit_price]" class="pr-price-raw" value="">
                            </td>
                        @endif

                        <td class="pr-td-notes" data-label="Catatan">
                            <input type="text"
                                class="form-control form-control-sm pr-field"
                                name="lines[0][notes]"
                                placeholder="opsional" maxlength="255" autocomplete="off">
                        </td>

                        <td class="pr-td-action text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger pr-btn-remove-line"
                                style="border-radius:10px;" title="Hapus baris">&times;</button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="text-center py-2 d-md-none">
        <button type="button" id="btn-add-pr-line-bottom" class="btn btn-sm btn-outline-primary"
            style="border-radius:12px;">+ Tambah Baris</button>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    const tbody       = document.getElementById('pr-lines-body');
    const btnAddTop   = document.getElementById('btn-add-pr-line');
    const btnAddBot   = document.getElementById('btn-add-pr-line-bottom');
    const hasMoney    = {{ $canSeeMoney ? 'true' : 'false' }};

    // ─── INDEKS BARIS ────────────────────────────────────────
    function reindex() {
        const rows = tbody.querySelectorAll('tr.pr-line-row');
        rows.forEach((tr, idx) => {
            // Nomor visual
            const noCell = tr.querySelector('.pr-td-no');
            if (noCell) noCell.textContent = idx + 1;

            // Semua name="lines[X][...]"
            tr.querySelectorAll('[name]').forEach(el => {
                el.name = el.name.replace(/lines\[\d+\]/, `lines[${idx}]`);
            });
        });
    }

    // ─── BERSIHKAN item-suggest STATE ────────────────────────
    function clearItemSuggest(tr) {
        tr.querySelectorAll('.js-item-suggest-input').forEach(i => i.value = '');
        tr.querySelectorAll('.js-item-suggest-id').forEach(h => {
            h.value = '';
            // trigger change supaya komponen tahu
            h.dispatchEvent(new Event('change', { bubbles: true }));
        });
        tr.querySelectorAll('.js-item-suggest-category').forEach(h => h.value = '');
        tr.querySelectorAll('.item-suggest-wrap').forEach(w => {
            w.removeAttribute('data-suggest-inited');
            const drop = w.querySelector('.item-suggest-dropdown');
            if (drop) drop.innerHTML = '';
        });
    }

    // ─── FORMAT ANGKA ID ─────────────────────────────────────
    function toNum(str) {
        if (!str) return 0;
        str = String(str).replace(/\s/g, '');
        if (str.includes(',')) {
            str = str.replace(/\./g, '').replace(',', '.');
        } else if (/^\d{1,3}(\.\d{3})+$/.test(str)) {
            str = str.replace(/\./g, '');
        }
        return parseFloat(str) || 0;
    }

    function fmtQty(n)   { return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function fmtPrice(n) { return n.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }); }

    // ─── SYNC DISPLAY → RAW ──────────────────────────────────
    function syncRaw(tr) {
        const qtyDisp  = tr.querySelector('.pr-qty-display');
        const qtyRaw   = tr.querySelector('.pr-qty-raw');
        if (qtyDisp && qtyRaw) {
            const n = toNum(qtyDisp.value);
            qtyRaw.value = n > 0 ? n : '';
        }

        if (hasMoney) {
            const priceDisp = tr.querySelector('.pr-price-display');
            const priceRaw  = tr.querySelector('.pr-price-raw');
            if (priceDisp && priceRaw) {
                const n = toNum(priceDisp.value);
                priceRaw.value = n > 0 ? n : '';
            }
        }
    }

    // ─── FORMAT SAAT BLUR ────────────────────────────────────
    tbody.addEventListener('blur', function (e) {
        const el = e.target;
        if (el.classList.contains('pr-qty-display')) {
            const n = toNum(el.value);
            el.value = n > 0 ? fmtQty(n) : '';
            const raw = el.closest('tr')?.querySelector('.pr-qty-raw');
            if (raw) raw.value = n > 0 ? n : '';
        }
        if (hasMoney && el.classList.contains('pr-price-display')) {
            const n = toNum(el.value);
            el.value = n > 0 ? fmtPrice(n) : '';
            const raw = el.closest('tr')?.querySelector('.pr-price-raw');
            if (raw) raw.value = n > 0 ? n : '';
        }
    }, true);

    // ─── TAMBAH BARIS ────────────────────────────────────────
    function addLine() {
        const rows = tbody.querySelectorAll('tr.pr-line-row');
        const last = rows[rows.length - 1];
        if (!last) return;

        const clone = last.cloneNode(true);

        // Reset display inputs
        clone.querySelectorAll('.pr-qty-display, .pr-price-display').forEach(i => i.value = '');
        clone.querySelectorAll('.pr-qty-raw, .pr-price-raw').forEach(i => i.value = '');
        clone.querySelectorAll('input[type=text]:not(.pr-num-display):not(.js-item-suggest-input)').forEach(i => {
            if (!i.classList.contains('pr-qty-display') && !i.classList.contains('pr-price-display')) {
                i.value = '';
            }
        });
        // Catatan baris
        const notesInput = clone.querySelector('input[name*="[notes]"]');
        if (notesInput) notesInput.value = '';

        // Bersihkan item suggest
        clearItemSuggest(clone);

        tbody.appendChild(clone);
        reindex();

        // Re-init item-suggest jika ada globalInit
        if (typeof window.initItemSuggest === 'function') {
            window.initItemSuggest(clone);
        }

        // Focus ke item input baru
        clone.querySelector('.js-item-suggest-input')?.focus();
    }

    btnAddTop?.addEventListener('click', addLine);
    btnAddBot?.addEventListener('click', addLine);

    // ─── HAPUS BARIS ─────────────────────────────────────────
    tbody.addEventListener('click', function (e) {
        if (!e.target.classList.contains('pr-btn-remove-line')) return;
        const rows = tbody.querySelectorAll('tr.pr-line-row');
        if (rows.length <= 1) return; // minimal 1 baris
        e.target.closest('tr')?.remove();
        reindex();
    });

    // ─── SYNC RAW SEBELUM SUBMIT ─────────────────────────────
    const form = tbody.closest('form');
    form?.addEventListener('submit', function () {
        tbody.querySelectorAll('tr.pr-line-row').forEach(tr => syncRaw(tr));
    });

})();
</script>
@endpush
