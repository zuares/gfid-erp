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
        background: var(--card, #fff);
        border-radius: 8px;
        border: 1px solid rgba(148,163,184,.18);
        box-shadow: 0 1px 6px rgba(15,23,42,.07);
        margin-bottom: .85rem;
        overflow: visible;
    }
    body[data-theme="dark"] .pr-card {
        border-color: rgba(30,64,175,.55);
        box-shadow: 0 12px 36px rgba(15,23,42,.8);
    }
    .pr-card .card-body {
        padding: .9rem 1rem;
    }
    .pr-table-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding: .85rem 1.25rem .7rem;
        border-bottom: 1px solid rgba(148,163,184,.14);
    }
    body[data-theme="dark"] .pr-table-head { border-bottom-color: rgba(51,65,85,.8); }
    .pr-table-title {
        color: #9ca3af;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
    }
    .pr-section-copy {
        color: var(--muted);
        font-size: .75rem;
        margin-top: .1rem;
    }
    .pr-focal-fields {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(180px, 240px);
        gap: 1rem;
        align-items: start;
    }
    .pr-card-content { padding: 1.1rem 1.25rem .9rem; }
    .pr-label {
        color: var(--muted);
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .08em;
        margin-bottom: .3rem;
        text-transform: uppercase;
    }
    .pr-field {
        border-radius: 10px;
        font-size: .95rem;
    }
    .pr-card textarea.pr-field { min-height: 72px; }
    .pr-hint {
        color: #94a3b8;
        font-size: .7rem;
        margin-top: .3rem;
    }
    .pr-head-actions { display: flex; align-items: center; gap: .5rem; }
    .pr-head-actions .btn { min-height: 36px; }
    .pr-add-btn {
        background: #2563eb !important;
        border-color: #2563eb !important;
        border-radius: 8px !important;
        color: #fff !important;
        font-weight: 700;
    }
    .pr-add-btn:hover,
    .pr-add-btn:focus-visible {
        background: #1d4ed8 !important;
        border-color: #1d4ed8 !important;
        color: #fff !important;
    }
    .pr-lines-table thead th {
        background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
        border-bottom-color: var(--line);
        color: var(--muted);
        font-size: .68rem;
        letter-spacing: .06em;
        padding: .58rem .65rem;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .pr-lines-table { --bs-table-bg: transparent; }
    .pr-lines-table tbody tr:nth-child(even) { background: rgba(249,250,251,.7); }
    body[data-theme="dark"] .pr-lines-table tbody tr:nth-child(even) { background: rgba(15,23,42,.8); }
    .pr-lines-table tbody td {
        border-bottom-color: var(--line);
        padding: .62rem .65rem;
        vertical-align: top;
    }
    .pr-lines-table .js-item-suggest-input,
    .pr-lines-table .pr-field {
        min-height: 38px;
        padding: .375rem .75rem;
        border-radius: 10px;
        line-height: 1.5;
    }
    .pr-table-wrapper { overflow-x: auto; overflow-y: visible; -webkit-overflow-scrolling: touch; }
    .pr-lines-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding: .75rem 1rem;
        border-top: 1px solid var(--line);
        background: color-mix(in srgb, var(--card) 96%, var(--bg) 4%);
    }
    .pr-lines-summary {
        color: var(--muted);
        font-size: .8rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .pr-total-live { color: #2563eb; font-size: 1.05rem; font-weight: 900; }
    .pr-btn-remove-line {
        width: 32px;
        height: 32px;
        align-items: center;
        background: transparent;
        border: 0;
        border-radius: 10px !important;
        color: #ef4444;
        display: inline-flex;
        justify-content: center;
        padding: 0;
        transition: all .15s;
    }
    .pr-btn-remove-line:hover { background: rgba(239,68,68,.1); color: #dc2626; }
    .pr-col-no { width: 4%; min-width: 32px; }
    .pr-col-qty { width: 12%; min-width: 90px; }
    .pr-col-price { width: 15%; min-width: 110px; }
    .pr-col-notes { width: 22%; }
    .pr-col-action { width: 5%; min-width: 48px; }

    @media (max-width: 768px) {
        .pr-card .card-body,
        .pr-card-content { padding: .75rem; }
        .pr-table-head { padding: .7rem .75rem; }
        .pr-focal-fields { grid-template-columns: 1fr; gap: .75rem; }
        .pr-lines-table thead { display: none; }
        .pr-table-wrapper { overflow-x: visible; }
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
        .pr-lines-footer { justify-content: center; padding: .7rem .75rem; }
    }
</style>
@endpush

{{-- HEADER --}}
<div class="pr-card pr-supplier-card">
    <div class="pr-table-head">
        <div class="pr-table-title">Informasi Purchase Request</div>
    </div>
    <div class="pr-card-content">
        <div class="pr-focal-fields">
            <div>
                <div class="pr-label">Supplier Awal <span class="fw-normal text-muted">(opsional)</span></div>
                <select name="supplier_id" id="pr-supplier" class="form-select pr-field @error('supplier_id') is-invalid @enderror">
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
                <div class="pr-hint"><i class="bi bi-info-circle me-1"></i>Supplier dapat ditentukan otomatis per barang saat PR dikonversi menjadi PO.</div>
            </div>

            <div>
                <div class="pr-label">Tanggal Dokumen</div>
                <input type="text" id="pr-document-date" name="date"
                    value="{{ old('date', isset($pr) ? $pr->date?->format('d/m/Y') : now()->format('d/m/Y')) }}"
                    class="form-control pr-field gf-date-input @error('date') is-invalid @enderror"
                    data-gf-date autocomplete="off">
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

    </div>
</div>

{{-- LINES --}}
<div class="pr-card">
    <div class="pr-table-head">
        <div>
            <div class="pr-table-title">Detail Barang</div>
            <div class="pr-section-copy">Isi item dan jumlah kebutuhan yang ingin diajukan.</div>
        </div>
    </div>

    <div class="table-responsive pr-table-wrapper">
        <table class="table table-sm mb-0 pr-lines-table" id="pr-lines-table">
            <thead>
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

    <div class="pr-lines-footer">
        <button type="button" id="btn-add-pr-line-bottom" class="btn btn-sm pr-add-btn">
            <i class="bi bi-plus-lg me-1"></i>Tambah Barang
        </button>
        <div class="pr-lines-summary">
            <span class="mono pr-total-live" id="pr-live-lines">0</span> item
            <span class="mx-2">·</span>
            <span class="mono pr-total-live" id="pr-live-qty">0</span> qty kebutuhan
        </div>
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
        updateSummary();
    }

    function updateSummary() {
        const rows = Array.from(tbody.querySelectorAll('tr.pr-line-row'));
        let totalQty = 0;

        rows.forEach((tr) => {
            const raw = tr.querySelector('.pr-qty-raw');
            const display = tr.querySelector('.pr-qty-display');
            totalQty += toNum(raw?.value || display?.value || '');
        });

        const itemCount = document.getElementById('pr-live-lines');
        const qtyCount = document.getElementById('pr-live-qty');
        if (itemCount) itemCount.textContent = rows.length.toLocaleString('id-ID');
        if (qtyCount) qtyCount.textContent = totalQty.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
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

    function normalizeText(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[_\-\/]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function itemSignature(item) {
        const text = normalizeText(`${item?.code || ''} ${item?.name || ''}`);
        const colorMap = {
            hitam: 'hitam',
            black: 'hitam',
            blk: 'hitam',
            navy: 'navy',
            biru: 'biru',
            blue: 'biru',
            blu: 'biru',
            putih: 'putih',
            white: 'putih',
            wht: 'putih',
            abu: 'abu',
            grey: 'abu',
            gray: 'abu',
            gry: 'abu',
            cream: 'cream',
            krem: 'cream',
            crm: 'cream',
            maroon: 'maroon',
            merah: 'merah',
            red: 'merah',
            kuning: 'kuning',
            yellow: 'kuning',
            yel: 'kuning',
            orange: 'orange',
            oren: 'orange',
            hijau: 'hijau',
            green: 'hijau',
            grn: 'hijau',
            olive: 'olive',
            army: 'army',
            khaki: 'khaki',
            coklat: 'coklat',
            brown: 'coklat',
            mocca: 'coklat',
            pink: 'pink',
            ungu: 'ungu',
            purple: 'ungu',
            lilac: 'ungu',
            taro: 'ungu',
        };

        const colorToken = Object.keys(colorMap).find(token =>
            new RegExp(`(?:^|\\s)${token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(?:\\s|$)`, 'i').test(text)
        ) || null;

        const color = colorToken ? colorMap[colorToken] : null;
        const sizeMatch = text.match(/\b(10xl|9xl|8xl|7xl|6xl|5xl|4xl|3xl|xxxl|xxl|xl|xs|s|m|l|\d{1,2}l)\b/i);
        const size = sizeMatch ? sizeMatch[1].toLowerCase() : null;

        let base = text;
        if (colorToken) {
            Object.keys(colorMap).forEach(token => {
                base = base.replace(new RegExp(`(?:^|\\s)${token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(?:\\s|$)`, 'ig'), ' ');
            });
        }
        if (size) {
            base = base.replace(/\b(10xl|9xl|8xl|7xl|6xl|5xl|4xl|3xl|xxxl|xxl|xl|xs|s|m|l|\d{1,2}l)\b/ig, ' ');
        }

        base = normalizeText(base);

        return { color, size, base };
    }

    function itemCategoryKind(item) {
        return String(item?.item_category_kind || item?.product_category_kind || '').toLowerCase();
    }

    function sameFamily(target, candidate) {
        if (!target || !candidate) return false;

        if (target.item_category_id && candidate.item_category_id && String(target.item_category_id) === String(candidate.item_category_id)) {
            return true;
        }

        if (target.product_category_id && candidate.product_category_id && String(target.product_category_id) === String(candidate.product_category_id)) {
            return true;
        }

        const targetKind = itemCategoryKind(target);
        const candidateKind = itemCategoryKind(candidate);
        return !!targetKind && targetKind === candidateKind;
    }

    function scoreItemMatch(target, candidate) {
        let score = 0;
        if (target.item_category_id && candidate.item_category_id && String(target.item_category_id) === String(candidate.item_category_id)) {
            score += 80;
        }
        if (target.product_category_id && candidate.product_category_id && String(target.product_category_id) === String(candidate.product_category_id)) {
            score += 60;
        }
        const targetKind = itemCategoryKind(target);
        const candidateKind = itemCategoryKind(candidate);
        if (targetKind && candidateKind && targetKind === candidateKind) {
            score += 40;
        }

        const targetSig = itemSignature(target);
        const candidateSig = itemSignature(candidate);
        if (targetSig.color && candidateSig.color && targetSig.color === candidateSig.color) {
            score += 30;
        }
        if (targetSig.size && candidateSig.size && targetSig.size === candidateSig.size) {
            score += 30;
        }
        if (targetSig.base && candidateSig.base && targetSig.base === candidateSig.base) {
            score += 15;
        }
        return score;
    }

    function findSuggestedPrice(item, items) {
        if (!item) return null;

        const directPrice = Number(item.suggested_purchase_price ?? item.last_purchase_price ?? 0);
        if (Number.isFinite(directPrice) && directPrice > 0) {
            return directPrice;
        }

        let best = null;
        let bestScore = 0;

        items.forEach(candidate => {
            if (String(candidate.id) === String(item.id)) return;
            const price = Number(candidate.suggested_purchase_price ?? candidate.last_purchase_price ?? 0);
            if (!Number.isFinite(price) || price <= 0) return;
            if (!sameFamily(item, candidate)) return;

            const score = scoreItemMatch(item, candidate);
            if (score > bestScore) {
                bestScore = score;
                best = candidate;
            }
        });

        const fallbackPrice = Number(best?.suggested_purchase_price ?? best?.last_purchase_price ?? 0);
        return Number.isFinite(fallbackPrice) && fallbackPrice > 0 ? fallbackPrice : null;
    }

    function getItemLastPrice(tr) {
        const itemId = (tr.querySelector('.js-item-suggest-id')?.value || '').toString().trim();
        if (!itemId) return null;

        const input = tr.querySelector('.js-item-suggest-input');
        if (!input) return null;

        let items = [];
        try {
            items = JSON.parse(input.dataset.items || '[]');
        } catch (e) {
            items = [];
        }

        const item = items.find(it => String(it.id) === String(itemId));
        if (!item) return null;

        return findSuggestedPrice(item, items);
    }

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

    function applyItemLastPrice(tr) {
        if (!hasMoney) return;

        const priceDisp = tr.querySelector('.pr-price-display');
        const priceRaw = tr.querySelector('.pr-price-raw');
        if (!priceDisp || !priceRaw) return;

        if (priceRaw.value && Number(priceRaw.value) > 0) return;

        const lastPrice = getItemLastPrice(tr);
        if (lastPrice == null) return;

        priceDisp.value = fmtPrice(lastPrice);
        priceRaw.value = String(Math.round(lastPrice));
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

    tbody.addEventListener('change', function (e) {
        if (!e.target.classList.contains('js-item-suggest-id')) return;
        const tr = e.target.closest('tr');
        if (!tr) return;

        applyItemLastPrice(tr);
    });

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

        // Re-init item-suggest pada baris baru.
        if (typeof window.initItemSuggestInputs === 'function') {
            window.initItemSuggestInputs(clone);
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

    reindex();

})();
</script>
@endpush
