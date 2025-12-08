{{-- resources/views/purchasing/purchase_orders/_blade.php --}}
@php
    use Illuminate\Support\Carbon;

    /** @var \App\Models\PurchaseOrder|null $order */

    // === DATE ===
    $dateRaw = old('date') ?? ($order?->date ?? now()->toDateString());
    $orderDate = $dateRaw instanceof Carbon ? $dateRaw->toDateString() : (string) $dateRaw;

    // === SUPPLIER ===
    $defaultSupplierId = $suppliers->first()->id ?? null;
    $selectedSupplierId = old('supplier_id', $order?->supplier_id ?? $defaultSupplierId);

    // === ONGKIR (dari DB), old() dipakai langsung di input ===
    $shippingCostDb = $order?->shipping_cost ?? 0;
    $shippingCostInput = old('shipping_cost', angka($shippingCostDb));

    // === STATUS ===
    $statusOptions = [
        'draft' => 'Draft',
        'approved' => 'Approved',
        'cancelled' => 'Cancelled',
    ];
    $statusValue = old('status', $order?->status ?? 'draft');

    // === DETAIL LINES ===
    $oldLines = old('lines');
    $usingOldLines = $oldLines !== null;

    if ($usingOldLines) {
        $linesData = $oldLines;
    } elseif (isset($lines)) {
        $linesData = is_array($lines) ? $lines : $lines->toArray();
    } else {
        $linesData = [];
    }
@endphp
@push('head')
    <style>
        /* ============================
               FORM PURCHASE – LAYOUT
            ============================ */
        .po-form-card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid var(--line);
        }

        .po-lines-card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid var(--line);
            /* SEBELUMNYA: overflow: hidden; → ini yang motong dropdown */
            overflow: visible;
            /* biar dropdown item-suggest bisa keluar */
        }

        .po-lines-table thead th {
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .75rem;
            color: var(--muted);
        }

        .po-lines-table tbody td {
            vertical-align: middle;
        }

        .po-lines-table tfoot th {
            font-size: .85rem;
        }

        .po-subtotal-label {
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .7rem;
            color: var(--muted);
        }

        /* === BIAR DROPDOWN GA KEPOTONG DI DALAM TABLE === */
        .po-table-wrapper {
            -webkit-overflow-scrolling: touch;
            overflow-x: auto;
            overflow-y: visible;
        }

        .po-lines-table td,
        .po-lines-table th {
            position: relative;
            overflow: visible;
        }

        .item-suggest-wrap {
            position: relative;
            overflow: visible;
        }

        .item-suggest-dropdown {
            z-index: 5000;
        }

        /* ============================
               MOBILE / TABLET TWEAKS (≤ 992px)
            ============================ */
        @media (max-width: 992px) {
            .po-form-card {
                border-radius: 14px;
                padding-inline: .2rem;
            }

            .po-form-card .card-body {
                padding: .85rem .9rem 1rem;
            }

            .po-form-card .form-label {
                font-size: .8rem;
                margin-bottom: .15rem;
            }

            .po-form-card .form-control,
            .po-form-card .form-select {
                font-size: .85rem;
                padding-inline: .55rem;
                padding-block: .35rem;
            }

            .po-lines-card {
                border-radius: 16px;
            }

            .po-lines-card .card-header {
                padding-inline: .9rem;
                padding-block: .6rem;
            }

            .po-lines-card .card-header span {
                font-size: .9rem;
                font-weight: 600;
            }

            .po-lines-card .card-header .btn {
                padding-block: .25rem;
                padding-inline: .6rem;
                font-size: .8rem;
            }

            /* TABLE → CARD PER BARIS */
            .po-lines-table {
                font-size: .85rem;
            }

            .po-lines-table thead {
                display: none;
            }

            .po-lines-table tbody {
                display: block;
            }

            .po-lines-table tbody tr {
                display: grid;
                grid-template-columns: 1fr 1fr;
                grid-template-areas:
                    "header header"
                    "item item"
                    "qty price"
                    "total total"
                    "action action";
                gap: .25rem .5rem;

                max-width: 620px;
                margin: 0 auto .7rem auto;
                padding: .6rem .7rem .55rem;
                border-radius: 14px;
                border: 1px solid var(--line);
                background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
                box-shadow:
                    0 8px 18px rgba(15, 23, 42, 0.08),
                    0 0 0 1px rgba(148, 163, 184, .12);
            }

            .po-lines-table tbody tr:last-child {
                margin-bottom: .8rem;
            }

            .po-lines-table tbody td {
                border: 0 !important;
                padding: .1rem 0;
            }

            .po-lines-table tbody td[data-label]::before {
                content: attr(data-label);
                display: block;
                font-size: .7rem;
                text-transform: uppercase;
                letter-spacing: .08em;
                color: var(--muted);
                margin-bottom: .05rem;
            }

            .po-col-no {
                grid-area: header;
                display: block;
                text-align: center;
                font-weight: 600;
                font-size: .9rem;
                color: var(--muted);
            }

            .po-td-item {
                grid-area: item;
            }

            .po-td-qty {
                grid-area: qty;
            }

            .po-td-price {
                grid-area: price;
            }

            .po-td-total {
                grid-area: total;
                text-align: right;
                font-weight: 500;
                font-size: .9rem;
                color: var(--muted);
            }

            .po-td-action {
                grid-area: action;
                text-align: center;
                margin-top: .2rem;
            }

            .po-lines-table .form-control-sm {
                font-size: .8rem;
                padding-block: .25rem;
                padding-inline: .45rem;
            }

            .po-lines-table .js-item-suggest-input {
                font-size: .85rem;
            }

            .po-lines-table tbody .line-qty,
            .po-lines-table tbody .line-price {
                text-align: center !important;
            }

            .po-lines-table tbody td.po-td-action .btn {
                padding-inline: .8rem;
                padding-block: .3rem;
                font-size: .8rem;
                border-radius: 999px;
            }

            #po-lines-table tfoot tr {
                display: block;
            }

            #po-lines-table tfoot th {
                display: inline-block;
                width: 100%;
                text-align: right;
                padding: .35rem 1.2rem .5rem;
            }

            .po-subtotal-label {
                display: block;
                font-size: .7rem;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: var(--muted);
                margin-bottom: .05rem;
            }

            #po-subtotal-cell {
                display: block;
                font-size: 1.1rem;
                font-weight: 700;
                color: var(--text);
            }
        }

        @media (min-width: 993px) {
            .po-col-no {
                width: 5%;
            }
        }
    </style>
@endpush


{{-- ============================
     HEADER FORM (DATE / SUPPLIER / ONGKIR / STATUS)
============================ --}}
<div class="card mb-3 po-form-card">
    <div class="card-body">
        <div class="row g-3">

            {{-- TANGGAL --}}
            <div class="col-12 col-md-3">
                <label class="form-label">Tanggal</label>
                <input type="date" name="date" value="{{ $orderDate }}"
                    class="form-control @error('date') is-invalid @enderror">
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- SUPPLIER --}}
            <div class="col-12 col-md-3">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                    @foreach ($suppliers as $sup)
                        <option value="{{ $sup->id }}" @selected($selectedSupplierId == $sup->id)>
                            {{ $sup->code }} — {{ $sup->name }}
                        </option>
                    @endforeach
                </select>
                @error('supplier_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- ONGKIR --}}
            <div class="col-6 col-md-3">
                <label class="form-label">Ongkir (Rp)</label>

                <input type="text" name="shipping_cost" value="{{ $shippingCostInput }}" inputmode="decimal"
                    class="form-control form-control-sm text-end @error('shipping_cost') is-invalid @enderror"
                    autocomplete="off">

                @error('shipping_cost')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>



            {{-- STATUS (READONLY: select disabled + hidden input) --}}
            <div class="col-6 col-md-3">
                <label class="form-label">Status</label>

                {{-- hidden yang dikirim ke server --}}
                <input type="hidden" name="status" value="{{ $statusValue }}">

                {{-- Select hanya untuk tampilan --}}
                <select class="form-select" disabled>
                    @foreach ($statusOptions as $key => $label)
                        <option value="{{ $key }}" @selected($statusValue === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                @error('status')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </div>
</div>

{{-- ============================
     DETAIL BARANG
============================ --}}
<div class="card po-lines-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Detail Barang</span>
        <button type="button" id="btn-add-line" class="btn btn-sm btn-outline-primary">
            + Tambah Baris
        </button>
    </div>

    <div class="table-responsive po-table-wrapper">
        <table class="table table-sm mb-0 po-lines-table" id="po-lines-table">
            <thead class="table-light">
                <tr>
                    <th class="po-col-no text-center">No</th>
                    <th style="width:40%">Item</th>
                    <th class="text-end" style="width:15%">Qty</th>
                    <th class="text-end" style="width:20%">Harga</th>
                    <th class="text-end" style="width:15%">Total</th>
                    <th style="width:5%"></th>
                </tr>
            </thead>

            <tbody>
                @forelse ($linesData as $i => $line)
                    @php
                        $lineQtyRaw = $line['qty'] ?? '';
                        $linePriceRaw = $line['unit_price'] ?? '';
                        $lineItemId = $line['item_id'] ?? ($line['item']['id'] ?? null);

                        $lineQty = $lineQtyRaw;
                        $linePrice = $usingOldLines ? $linePriceRaw : angka($linePriceRaw);

                        $itemCode = $line['item']['code'] ?? null;
                        $itemDisplay = $itemCode ?? '';
                    @endphp

                    <tr>
                        {{-- HEADER (mobile) / No (desktop) --}}
                        <td class="text-center align-middle line-index po-col-no">{{ $loop->iteration }}</td>

                        {{-- ITEM --}}
                        <td class="po-td-item" data-label="Item">
                            <x-item-suggest :items="$items" idName="lines[{{ $i }}][item_id]"
                                :idValue="$lineItemId" :displayValue="$itemDisplay" type="material" variant="mini" :minChars="1" />
                            @error("lines.$i.item_id")
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </td>

                        {{-- Qty --}}
                        <td data-label="Qty" class="po-td-qty">
                            <x-number-input name="lines[{{ $i }}][qty]" :value="$lineQty" htmlType="text"
                                inputmode="decimal" size="sm" align="end" class="line-qty" />
                            @error("lines.$i.qty")
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </td>

                        {{-- Harga --}}
                        <td data-label="Harga (Rp)" class="po-td-price">
                            <x-number-input name="lines[{{ $i }}][unit_price]" :value="$linePrice"
                                htmlType="text" inputmode="decimal" size="sm" align="end" class="line-price" />
                            @error("lines.$i.unit_price")
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </td>

                        {{-- TOTAL --}}
                        <td class="text-end align-middle line-total po-td-total" data-label="Total (Rp)"></td>

                        {{-- ACTION --}}
                        <td class="text-center po-td-action">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line">
                                {{-- Mobile / tablet: teks, Desktop: ikon --}}
                                <span class="d-inline d-lg-none">Hapus baris</span>
                                <span class="d-none d-lg-inline">&times;</span>
                            </button>
                        </td>
                    </tr>

                @empty
                    {{-- DEFAULT 1 ROW --}}
                    <tr>
                        <td class="text-center align-middle line-index po-col-no">1</td>

                        <td class="po-td-item" data-label="Item">
                            <x-item-suggest idName="lines[0][item_id]" :items="$items" displayMode="code-name"
                                :showName="true" :showCategory="false" type="material"
                                placeholder="Masukan kode / nama barang" />
                        </td>

                        <td data-label="Qty" class="po-td-qty">
                            <x-number-input name="lines[0][qty]" htmlType="text" inputmode="decimal" size="sm"
                                align="end" class="line-qty" />
                        </td>

                        <td data-label="Harga (Rp)" class="po-td-price">
                            <x-number-input name="lines[0][unit_price]" htmlType="text" inputmode="decimal"
                                size="sm" align="end" class="line-price" />
                        </td>

                        <td class="text-end align-middle line-total po-td-total" data-label="Total (Rp)"></td>

                        <td class="text-center po-td-action">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line">
                                <span class="d-inline d-lg-none">Hapus baris</span>
                                <span class="d-none d-lg-inline">&times;</span>
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>

            <tfoot class="table-light">
                <tr>
                    <th colspan="4" class="text-end po-subtotal-label">Subtotal</th>
                    <th class="text-end" id="po-subtotal-cell"></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Tombol tambah baris di bawah (khusus mobile / tablet) --}}
    <div class="d-block d-lg-none text-center py-2">
        <button type="button" id="btn-add-line-bottom" class="btn btn-outline-primary btn-sm">
            + Tambah Baris
        </button>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.querySelector('#po-lines-table tbody');
            const btnAddTop = document.getElementById('btn-add-line');
            const btnAddBottom = document.getElementById('btn-add-line-bottom');
            const subtotalCell = document.getElementById('po-subtotal-cell');
            const shippingInput = document.querySelector('input[name="shipping_cost"]');

            function parseNumber(value) {
                if (!value) return 0;
                value = value.toString().trim();
                value = value.replace(/\s+/g, '');

                // ada koma → treat sebagai pemisah desimal, titik jadi ribuan
                if (value.indexOf(',') !== -1) {
                    value = value.replace(/\./g, '');
                    value = value.replace(',', '.');
                    const n = parseFloat(value);
                    return isNaN(n) ? 0 : n;
                }

                // pola 1.000 / 10.000 dst
                if (/^\d{1,3}(\.\d{3})+$/.test(value)) {
                    value = value.replace(/\./g, '');
                    const n = parseFloat(value);
                    return isNaN(n) ? 0 : n;
                }

                const n = parseFloat(value);
                return isNaN(n) ? 0 : n;
            }

            function formatNumberId(value) {
                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0,
                }).format(isNaN(value) ? 0 : value);
            }

            function formatInputOnBlur(el) {
                const num = parseNumber(el.value);
                if (!num) {
                    el.value = '';
                    return;
                }
                el.value = formatNumberId(num);
            }

            function recalcRow(tr) {
                const qtyInput = tr.querySelector('.line-qty');
                const priceInput = tr.querySelector('.line-price');
                const totalCell = tr.querySelector('.line-total');

                const qty = parseNumber(qtyInput?.value);
                const price = parseNumber(priceInput?.value);

                let total = qty * price;
                if (total < 0) total = 0;

                if (totalCell) {
                    totalCell.textContent = formatNumberId(total);
                }

                return total;
            }

            function recalcAll() {
                let subtotal = 0;
                tableBody.querySelectorAll('tr').forEach(function(tr) {
                    subtotal += recalcRow(tr);
                });

                if (subtotalCell) {
                    subtotalCell.textContent = formatNumberId(subtotal);
                }
            }

            function renumberLines() {
                const rows = tableBody.querySelectorAll('tr');
                rows.forEach(function(tr, index) {
                    const idxCell = tr.querySelector('.line-index');
                    if (idxCell) idxCell.textContent = index + 1;

                    tr.querySelectorAll('input, select').forEach(function(el) {
                        const name = el.getAttribute('name');
                        if (!name) return;
                        el.setAttribute(
                            'name',
                            name.replace(/lines\[\d+\]/, 'lines[' + index + ']')
                        );
                    });
                });
            }

            function addNewRow() {
                const lastRow = tableBody.querySelector('tr:last-child');
                const newRow = lastRow.cloneNode(true);

                // reset qty & harga
                newRow.querySelectorAll('.line-qty, .line-price').forEach(function(input) {
                    input.value = '';
                });

                // reset item-suggest
                newRow.querySelectorAll('.js-item-suggest-input').forEach(function(input) {
                    input.value = '';
                });
                newRow.querySelectorAll('.js-item-suggest-id').forEach(function(hidden) {
                    hidden.value = '';
                });
                newRow.querySelectorAll('.js-item-suggest-category').forEach(function(hidden) {
                    hidden.value = '';
                });

                const totalCell = newRow.querySelector('.line-total');
                if (totalCell) totalCell.textContent = '';

                newRow.querySelectorAll('.item-suggest-wrap').forEach(function(wrap) {
                    wrap.removeAttribute('data-suggest-inited');
                });

                tableBody.appendChild(newRow);
                renumberLines();
                recalcAll();

                if (window.initItemSuggestInputs) {
                    window.initItemSuggestInputs(newRow);
                }
            }

            btnAddTop?.addEventListener('click', addNewRow);
            btnAddBottom?.addEventListener('click', addNewRow);

            tableBody.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-remove-line') ||
                    e.target.closest('.btn-remove-line')) {

                    const btn = e.target.closest('.btn-remove-line');
                    if (!btn) return;

                    const rows = tableBody.querySelectorAll('tr');
                    if (rows.length <= 1) {
                        const row = rows[0];
                        row.querySelectorAll('.line-qty, .line-price').forEach(function(input) {
                            input.value = '';
                        });
                        row.querySelectorAll('.js-item-suggest-input').forEach(function(input) {
                            input.value = '';
                        });
                        row.querySelectorAll('.js-item-suggest-id, .js-item-suggest-category').forEach(
                            function(hidden) {
                                hidden.value = '';
                            });
                        const totalCell = row.querySelector('.line-total');
                        if (totalCell) totalCell.textContent = '';
                        recalcAll();
                        return;
                    }

                    btn.closest('tr').remove();
                    renumberLines();
                    recalcAll();
                }
            });

            tableBody.addEventListener('input', function(e) {
                if (
                    e.target.classList.contains('line-qty') ||
                    e.target.classList.contains('line-price')
                ) {
                    recalcAll();
                }
            });

            tableBody.addEventListener('blur', function(e) {
                if (
                    e.target.classList.contains('line-qty') ||
                    e.target.classList.contains('line-price')
                ) {
                    formatInputOnBlur(e.target);
                }
            }, true);

            if (shippingInput) {
                shippingInput.addEventListener('blur', function() {
                    formatInputOnBlur(shippingInput);
                });
            }

            recalcAll();

            if (window.initItemSuggestInputs) {
                window.initItemSuggestInputs();
            }
        });
    </script>
@endpush
