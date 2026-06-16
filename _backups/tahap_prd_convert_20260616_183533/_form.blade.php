{{-- resources/views/purchasing/purchase_orders/_form.blade.php --}}
@php
    use Illuminate\Support\Carbon;

    /** @var \App\Models\PurchaseOrder|null $order */
    $canSeeMoney = auth()->user()?->isOwner() ?? false;

    // =========================
    // ORDER TYPE (Material / FG)
    // =========================
    $orderTypeRaw = old('order_type') ?? ($order?->order_type ?? (request('order_type') ?? 'material'));
    $allowedOrderTypes = ['material', 'finished_good', 'packing', 'asset', 'service', 'jasa', 'lainnya'];
    $orderType = in_array($orderTypeRaw, $allowedOrderTypes, true) ? $orderTypeRaw : 'material';

    $orderTypeOptions = [
        'material'     => 'Bahan Baku (Material)',
        'finished_good'=> 'Barang Jadi (FG)',
        'packing'      => 'Packing (Kemasan)',
        'asset'        => 'Aset',
        'service'      => 'Service',
        'jasa'         => 'Jasa',
        'lainnya'      => 'Lainnya',
    ];

    // === DATE ===
    $dateRaw = old('date') ?? ($order?->date ?? now()->toDateString());
    $orderDate = $dateRaw instanceof Carbon ? $dateRaw->toDateString() : (string) $dateRaw;

    // === SUPPLIER ===
    $defaultSupplierId = $suppliers->first()->id ?? null;
    $selectedSupplierId = old('supplier_id', $order?->supplier_id ?? request('supplier_id') ?? $defaultSupplierId);

    // === PAYMENT METHOD ===
    // Filter: exclude DP_APPLY (internal only)
    $visiblePaymentMethods = ($paymentMethods ?? collect())->filter(fn($pm) => $pm->code !== 'DP_APPLY')->values();
    $defaultPaymentMethodId = $visiblePaymentMethods->first()->id ?? null;
    $selectedPaymentMethodId = old('payment_method_id', $order?->payment_method_id ?? $defaultPaymentMethodId);
    // Label singkat untuk tiap mode
    $pmModeLabel = ['cash' => 'Tunai', 'transfer' => 'Transfer (TF)', 'credit' => 'Hutang / Tempo'];

    // === ONGKIR (display + raw) ===
    $shippingCostDb = (float) ($order?->shipping_cost ?? 0);
    $shippingCostDisplay = old('shipping_cost_display', angka($shippingCostDb));
    $shippingCostRaw = old('shipping_cost', (string) (int) $shippingCostDb);

    // === STATUS ===
    $statusOptions = [
        'draft' => 'Draft',
        'approved' => 'Approved',
        'cancelled' => 'Cancelled',
    ];
    $statusValue = old('status', $order?->status ?? 'draft');

    // Lines
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
        .po-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            overflow: visible;
            margin-bottom: 1rem;
        }

        .po-card .card-body {
            padding: 1.25rem 1.5rem 1.35rem;
        }

        .po-card .card-header {
            padding: .9rem 1.5rem;
        }

        .po-section-title {
            font-weight: 700;
            letter-spacing: -.01em;
        }

        .po-label {
            font-size: .72rem;
            color: var(--muted);
            margin-bottom: .3rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 700;
        }

        .po-field {
            border-radius: 12px;
        }

        .po-lines-table thead th {
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .72rem;
            color: var(--muted);
            padding: .75rem 1rem;
        }

        .po-lines-table tbody td {
            vertical-align: middle;
            padding: .65rem 1rem;
        }

        .po-subtotal-label {
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .72rem;
            color: var(--muted);
        }

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

        .po-meta-wrap {
            display: flex;
            justify-content: flex-end;
            margin-top: 1rem;
            padding: 0 1.5rem 1.5rem;
        }

        .po-meta-card {
            border: 1px solid var(--line);
            background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
            border-radius: 14px;
            padding: 1rem 1.25rem 1.25rem;
            width: min(620px, 100%);
        }

        /* 3-col: Status | Tipe Bayar | Ongkir */
        .po-meta-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: .75rem 1rem;
            margin-bottom: 1rem;
        }

        /* Totals selalu full-width */
        .po-meta-totals {
            border-top: 1px solid var(--line);
            padding-top: .75rem;
        }

        .po-num-display {
            text-align: right;
        }

        .po-num-display::placeholder {
            color: rgba(148, 163, 184, .8);
        }

        .po-total-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            padding: .4rem 0;
            border-top: 1px dashed rgba(148, 163, 184, .22);
        }

        .po-total-line:first-child {
            border-top: 0;
            padding-top: 0;
        }

        .po-total-key {
            font-size: .72rem;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 700;
        }

        .po-total-val {
            font-weight: 800;
            font-size: .9rem;
        }

        .po-total-val.subtle {
            font-weight: 700;
            color: var(--muted);
            font-size: .85rem;
        }

        /* Mobile meta: 2 kolom (Status | Tipe Bayar), Ongkir span-2 */
        @media (max-width: 560px) {
            .po-meta-inputs {
                grid-template-columns: 1fr 1fr;
            }
            .po-meta-inputs .po-meta-ongkir {
                grid-column: span 2;
            }
        }

        @media (max-width: 992px) {
            .po-card .card-body {
                padding: 1rem 1rem 1.1rem;
            }

            .po-card .card-header {
                padding: .8rem 1rem;
            }

            .po-meta-wrap {
                justify-content: stretch;
                padding: 0 1rem 1rem;
            }

            .po-meta-card {
                width: 100%;
            }

            .po-lines-table {
                font-size: .85rem
            }

            .po-lines-table thead {
                display: none
            }

            .po-lines-table tbody {
                display: block
            }

            .po-lines-table tbody tr {
                display: grid;
                grid-template-columns: 1fr 1fr;
                grid-template-areas: "header header" "item item" "qty price" "total total" "action action";
                gap: .25rem .5rem;
                max-width: 620px;
                margin: 0 auto .7rem auto;
                padding: .6rem .7rem .55rem;
                border-radius: 14px;
                border: 1px solid var(--line);
                background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
                box-shadow: 0 8px 18px rgba(15, 23, 42, .08), 0 0 0 1px rgba(148, 163, 184, .12);
            }

            .po-lines-table tbody td {
                border: 0 !important;
                padding: .1rem 0
            }

            .po-lines-table tbody td[data-label]::before {
                content: attr(data-label);
                display: block;
                font-size: .7rem;
                text-transform: uppercase;
                letter-spacing: .08em;
                color: var(--muted);
                margin-bottom: .05rem
            }

            .po-col-no {
                grid-area: header;
                text-align: center;
                font-weight: 600;
                font-size: .9rem;
                color: var(--muted)
            }

            .po-td-item {
                grid-area: item
            }

            .po-td-qty {
                grid-area: qty
            }

            .po-td-price {
                grid-area: price
            }

            .po-td-total {
                grid-area: total;
                text-align: right;
                font-weight: 600;
                font-size: .9rem;
                color: var(--muted)
            }

            .po-td-action {
                grid-area: action;
                text-align: center;
                margin-top: .2rem;
            }

            #po-lines-table tfoot tr {
                display: block
            }

            #po-lines-table tfoot th {
                display: inline-block;
                width: 100%;
                text-align: right;
                padding: .35rem 1.2rem .5rem
            }

            #po-subtotal-cell {
                display: block;
                font-size: 1.1rem;
                font-weight: 800;
                color: var(--text);
            }
        }

        @media (min-width: 993px) {
            .po-col-no {
                width: 5%
            }
        }
    </style>
@endpush

{{-- HEADER --}}
<div class="card po-card mb-3" data-order-type="{{ $orderType }}">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-3">
                <div class="po-label">Tanggal</div>
                <input type="text" name="date" value="{{ $orderDate }}"
                    class="form-control po-field gf-date-input @error('date') is-invalid @enderror"
                    data-gf-date autocomplete="off">
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-3">
                <div class="po-label">Jenis PO</div>
                <select name="order_type" id="po-order-type"
                    class="form-select po-field @error('order_type') is-invalid @enderror">
                    @foreach ($orderTypeOptions as $k => $label)
                        <option value="{{ $k }}" @selected($orderType === $k)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('order_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <div class="po-label">Supplier</div>
                <select name="supplier_id" class="form-select po-field @error('supplier_id') is-invalid @enderror">
                    @foreach ($suppliers as $sup)
                        <option value="{{ $sup->id }}" @selected((string) $selectedSupplierId === (string) $sup->id)>
                            {{ $sup->code }} — {{ $sup->name }}
                        </option>
                    @endforeach
                </select>
                @error('supplier_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

{{-- LINES --}}
<div class="card po-card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center"
        style="background: transparent; border-bottom: 1px solid var(--line); padding: .85rem 1rem;">
        <div class="po-section-title">Detail Barang</div>
        <button type="button" id="btn-add-line" class="btn btn-sm btn-outline-primary" style="border-radius:12px;">
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
                    @if ($canSeeMoney)
                        <th class="text-end" style="width:20%">Harga</th>
                        <th class="text-end" style="width:15%">Total</th>
                    @endif
                    <th style="width:5%"></th>
                </tr>
            </thead>

            <tbody>
                @forelse ($linesData as $i => $line)
                    @php
                        $lineItemId = $line['item_id'] ?? ($line['item']['id'] ?? null);

                        $itemCode = $line['item']['code'] ?? null;
                        $itemName = $line['item']['name'] ?? null;
                        $itemDisplay = trim(($itemCode ?? '') . ($itemName ? ' — ' . $itemName : ''));

                        $qtyRaw = $line['qty'] ?? '';
                        $qtyDisplay =
                            $qtyRaw === '' || $qtyRaw === null ? '' : number_format((float) $qtyRaw, 2, ',', '.');

                        $priceRaw = $line['unit_price'] ?? '';
                        $priceDisplay =
                            $priceRaw === '' || $priceRaw === null ? '' : number_format((float) $priceRaw, 0, ',', '.');

                        // ✅ NEW: allocation + expense account (hidden, auto)
                        // fallback: hpp
                        $alloc = old(
                            "lines.$i.allocation",
                            $line['allocation'] ?? ($line['item']['default_allocation'] ?? 'hpp'),
                        );
                        $expAcc = old(
                            "lines.$i.expense_account_id",
                            $line['expense_account_id'] ?? ($line['item']['default_expense_account_id'] ?? ''),
                        );
                    @endphp

                    <tr>
                        <td class="text-center align-middle line-index po-col-no">{{ $loop->iteration }}</td>

                        <td class="po-td-item" data-label="Item">
                            <x-item-suggest :items="$items" idName="lines[{{ $i }}][item_id]"
                                :idValue="$lineItemId" :displayValue="$itemDisplay" type="{{ $orderType === 'packing' ? 'material' : $orderType }}" variant="mini"
                                displayMode="{{ $orderType === 'packing' ? 'name' : 'code' }}"
                                :minChars="1" />
                            @error("lines.$i.item_id")
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror

                            <input type="hidden" name="lines[{{ $i }}][allocation]" class="line-alloc-raw"
                                value="{{ $alloc }}">
                            <input type="hidden" name="lines[{{ $i }}][expense_account_id]"
                                class="line-expacc-raw" value="{{ $expAcc }}">
                        </td>

                        <td data-label="Qty" class="po-td-qty">
                            <input type="text" class="form-control po-field po-num-display line-qty-display"
                                inputmode="decimal" placeholder="0,00" value="{{ $qtyDisplay }}" autocomplete="off">
                            <input type="hidden" name="lines[{{ $i }}][qty]" class="line-qty-raw"
                                value="{{ $qtyRaw }}">
                            @error("lines.$i.qty")
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </td>

                        @if ($canSeeMoney)
                            <td data-label="Harga (Rp)" class="po-td-price">
                                <input type="text" class="form-control po-field po-num-display line-price-display"
                                    inputmode="numeric" placeholder="0" value="{{ $priceDisplay }}" autocomplete="off">
                                <input type="hidden" name="lines[{{ $i }}][unit_price]" class="line-price-raw"
                                    value="{{ $priceRaw }}">
                                @error("lines.$i.unit_price")
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </td>

                            <td class="text-end align-middle line-total po-td-total" data-label="Total (Rp)"></td>
                        @endif

                        <td class="text-center po-td-action">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line"
                                style="border-radius:12px;">
                                <span class="d-inline d-lg-none">Hapus baris</span>
                                <span class="d-none d-lg-inline">&times;</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center align-middle line-index po-col-no">1</td>

                        <td class="po-td-item" data-label="Item">
                            <x-item-suggest idName="lines[0][item_id]" :items="$items" displayMode="{{ $orderType === 'packing' ? 'name' : 'code-name' }}"
                                :showName="true" :showCategory="false" type="{{ $orderType === 'packing' ? 'material' : $orderType }}"
                                placeholder="Masukan kode / nama barang" />
                            <input type="hidden" name="lines[0][allocation]" class="line-alloc-raw" value="hpp">
                            <input type="hidden" name="lines[0][expense_account_id]" class="line-expacc-raw"
                                value="">
                        </td>

                        <td data-label="Qty" class="po-td-qty">
                            <input type="text" class="form-control po-field po-num-display line-qty-display"
                                inputmode="decimal" placeholder="0,00" value="" autocomplete="off">
                            <input type="hidden" name="lines[0][qty]" class="line-qty-raw" value="">
                        </td>

                        @if ($canSeeMoney)
                            <td data-label="Harga (Rp)" class="po-td-price">
                                <input type="text" class="form-control po-field po-num-display line-price-display"
                                    inputmode="numeric" placeholder="0" value="" autocomplete="off">
                                <input type="hidden" name="lines[0][unit_price]" class="line-price-raw" value="">
                            </td>

                            <td class="text-end align-middle line-total po-td-total" data-label="Total (Rp)"></td>
                        @endif

                        <td class="text-center po-td-action">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line"
                                style="border-radius:12px;">
                                <span class="d-inline d-lg-none">Hapus baris</span>
                                <span class="d-none d-lg-inline">&times;</span>
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>

            <tfoot class="table-light">
                @if ($canSeeMoney)
                    <tr>
                        <th colspan="4" class="text-end po-subtotal-label">Subtotal</th>
                        <th class="text-end" id="po-subtotal-cell"></th>
                        <th></th>
                    </tr>
                @endif
            </tfoot>
        </table>
    </div>

    <div class="d-block d-lg-none text-center py-2">
        <button type="button" id="btn-add-line-bottom" class="btn btn-outline-primary btn-sm"
            style="border-radius:12px;">
            + Tambah Baris
        </button>
    </div>

    {{-- META --}}
    <div class="po-meta-wrap">
        <div class="po-meta-card">

            {{-- Baris input: Status | Tipe Pembayaran | Ongkir --}}
            <div class="po-meta-inputs">
                <div>
                    <div class="po-label">Status</div>
                    <input type="hidden" name="status" value="{{ $statusValue }}">
                    <select class="form-select po-field" disabled>
                        @foreach ($statusOptions as $key => $label)
                            <option value="{{ $key }}" @selected($statusValue === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <div class="po-label">Tipe Pembayaran</div>
                    <select name="payment_method_id" id="po-payment-method"
                        class="form-select po-field @error('payment_method_id') is-invalid @enderror">
                        @foreach ($visiblePaymentMethods as $pm)
                            <option value="{{ $pm->id }}" data-mode="{{ $pm->mode }}"
                                @selected((string) $selectedPaymentMethodId === (string) $pm->id)>
                                {{ $pmModeLabel[$pm->mode] ?? $pm->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('payment_method_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if ($canSeeMoney)
                    <div class="po-meta-ongkir">
                        <div class="po-label">Ongkir (Rp)</div>
                        <input type="text"
                            class="form-control po-field po-num-display shipping-display @error('shipping_cost') is-invalid @enderror"
                            inputmode="numeric" placeholder="0" value="{{ $shippingCostDisplay }}" autocomplete="off">
                        <input type="hidden" name="shipping_cost" class="shipping-raw" value="{{ $shippingCostRaw }}">
                        @error('shipping_cost')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
            </div>

            {{-- Ringkasan total --}}
            @if ($canSeeMoney)
                <div class="po-meta-totals">
                    <div class="po-total-line">
                        <div class="po-total-key">Subtotal Items</div>
                        <div class="po-total-val subtle" id="po-subtotal-meta">0</div>
                    </div>
                    <div class="po-total-line">
                        <div class="po-total-key">Ongkir</div>
                        <div class="po-total-val subtle" id="po-shipping-meta">0</div>
                    </div>
                    <div class="po-total-line">
                        <div class="po-total-key">Grand Total</div>
                        <div class="po-total-val" id="po-grand-meta">0</div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.querySelector('#po-lines-table tbody');
            const btnAddTop = document.getElementById('btn-add-line');
            const btnAddBottom = document.getElementById('btn-add-line-bottom');

            const subtotalCell = document.getElementById('po-subtotal-cell');
            const subtotalMeta = document.getElementById('po-subtotal-meta');
            const shippingMeta = document.getElementById('po-shipping-meta');
            const grandMeta = document.getElementById('po-grand-meta');

            const orderTypeSelect = document.getElementById('po-order-type');
            const currentOrderType = @json($orderType);
            const isEdit = @json((bool) $order?->id);
            const canSeeMoney = @json($canSeeMoney);

            const shippingDisplay = document.querySelector('.shipping-display');
            const shippingRaw = document.querySelector('.shipping-raw');
            const supplierSelect = document.querySelector('select[name="supplier_id"]');

            // =========================
            // Helpers
            // =========================
            function parseNumber(value) {
                if (!value) return 0;
                value = value.toString().trim().replace(/\s+/g, '');

                if (value.includes(',') && value.includes('.')) {
                    value = value.replace(/\./g, '').replace(',', '.');
                } else if (/^\d{1,3}(\.\d{3})+$/.test(value)) {
                    value = value.replace(/\./g, '');
                } else if (value.includes(',')) {
                    value = value.replace(',', '.');
                }
                const n = parseFloat(value);
                return isNaN(n) ? 0 : n;
            }

            function fmtIntId(n) {
                return new Intl.NumberFormat('id-ID', {
                    maximumFractionDigits: 0
                }).format(Math.round(n || 0));
            }

            function fmtQtyId(n) {
                const fixed = (isNaN(n) ? 0 : n).toFixed(2);
                return fixed.replace('.', ',');
            }

            function selectAllLater(el) {
                requestAnimationFrame(() => {
                    try {
                        el.focus();
                    } catch (e) {}
                    try {
                        el.select();
                    } catch (e) {}
                });
            }

            // =========================
            // Sync & calc row
            // =========================
            function syncRowRaw(tr) {
                const qtyDisp = tr.querySelector('.line-qty-display');
                const qtyRaw = tr.querySelector('.line-qty-raw');
                const priceDisp = tr.querySelector('.line-price-display');
                const priceRaw = tr.querySelector('.line-price-raw');

                if (qtyDisp && qtyRaw) {
                    const q = parseNumber(qtyDisp.value);
                    qtyRaw.value = (isNaN(q) ? 0 : q).toFixed(2);
                }
                if (priceDisp && priceRaw) {
                    const p = parseNumber(priceDisp.value);
                    priceRaw.value = String(Math.round(isNaN(p) ? 0 : p));
                }
            }

            function recalcRow(tr) {
                const qtyRaw = tr.querySelector('.line-qty-raw');
                const priceRaw = tr.querySelector('.line-price-raw');
                const totalCell = tr.querySelector('.line-total');

                const qty = parseFloat(qtyRaw?.value || '0') || 0;
                const price = parseFloat(priceRaw?.value || '0') || 0;

                let total = qty * price;
                if (total < 0) total = 0;

                if (totalCell) totalCell.textContent = fmtIntId(total);
                return total;
            }

            function recalcAll() {
                let subtotal = 0;

                tableBody.querySelectorAll('tr').forEach(tr => {
                    syncRowRaw(tr);
                    subtotal += recalcRow(tr);
                });

                const ship = parseFloat(shippingRaw?.value || '0') || 0;
                const grand = Math.max(0, subtotal + ship);

                if (subtotalCell) subtotalCell.textContent = fmtIntId(subtotal);
                if (subtotalMeta) subtotalMeta.textContent = fmtIntId(subtotal);
                if (shippingMeta) shippingMeta.textContent = fmtIntId(ship);
                if (grandMeta) grandMeta.textContent = fmtIntId(grand);
            }

            function renumberLines() {
                const rows = tableBody.querySelectorAll('tr');
                rows.forEach((tr, idx) => {
                    const idxCell = tr.querySelector('.line-index');
                    if (idxCell) idxCell.textContent = idx + 1;

                    tr.querySelectorAll('input, select').forEach(el => {
                        const name = el.getAttribute('name');
                        if (!name) return;
                        el.setAttribute('name', name.replace(/lines\[\d+\]/, 'lines[' + idx + ']'));
                    });
                });
            }

            function resetItemSuggest(tr) {
                tr.querySelectorAll('.js-item-suggest-input').forEach(i => i.value = '');
                tr.querySelectorAll('.js-item-suggest-id').forEach(h => h.value = '');
                tr.querySelectorAll('.js-item-suggest-category').forEach(h => h.value = '');
                tr.querySelectorAll('.item-suggest-wrap').forEach(w => w.removeAttribute('data-suggest-inited'));
                if (window.initItemSuggestInputs) window.initItemSuggestInputs(tr);
            }

            // =========================
            // Last price (existing)
            // =========================
            async function fetchLastPrice(supplierId, itemId) {
                if (!canSeeMoney) return null;
                const url =
                    `{{ route('purchasing.supplier_price') }}?supplier_id=${encodeURIComponent(supplierId)}&item_id=${encodeURIComponent(itemId)}`;
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) return null;

                const json = await res.json().catch(() => null);
                if (!json || json.last_price == null) return null;

                const n = Number(json.last_price);
                return isNaN(n) ? null : n;
            }

            async function applyLastPriceToRow(tr, {
                force = false
            } = {}) {
                const supplierId = supplierSelect?.value;
                const itemId = tr.querySelector('.js-item-suggest-id')?.value;
                if (!supplierId || !itemId) return;

                const priceDisp = tr.querySelector('.line-price-display');
                const priceRaw = tr.querySelector('.line-price-raw');
                if (!priceDisp || !priceRaw) return;

                const userEdited = priceDisp.dataset.userEdited === '1';
                if (!force && userEdited) return;
                if (!force && priceRaw.value && Number(priceRaw.value) > 0) return;

                const last = await fetchLastPrice(supplierId, itemId);
                if (last == null || last <= 0) return;

                priceDisp.value = fmtIntId(last);
                priceRaw.value = String(Math.round(last));
                priceDisp.dataset.userEdited = '0';

                syncRowRaw(tr);
                recalcAll();
            }

            // =========================
            // ✅ NEW: Item meta mapping (allocation + expense_account_id)
            // =========================
            async function fetchItemMeta(itemId) {
                const url =
                    `{{ route('master.items.meta') }}?item_id=${encodeURIComponent(itemId)}`;
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) return null;

                const json = await res.json().catch(() => null);
                if (!json || json.ok !== true) return null;

                return json;
            }

            async function applyItemMetaToRow(tr, {
                force = false
            } = {}) {
                const itemId = tr.querySelector('.js-item-suggest-id')?.value;
                if (!itemId) return;

                const allocRaw = tr.querySelector('.line-alloc-raw');
                const expAccRaw = tr.querySelector('.line-expacc-raw');
                if (!allocRaw || !expAccRaw) return;

                // kalau nanti kamu bikin UI override manual, flag ini bisa dipakai.
                const userEdited = allocRaw.dataset.userEdited === '1';
                if (!force && userEdited) return;

                const meta = await fetchItemMeta(itemId);
                if (!meta) return;

                const alloc = (meta.default_allocation === 'expense') ? 'expense' : 'hpp';
                allocRaw.value = alloc;

                if (alloc === 'expense') {
                    if (force || !expAccRaw.value) {
                        expAccRaw.value = meta.default_expense_account_id ? String(meta
                            .default_expense_account_id) : '';
                    }
                } else {
                    if (force) expAccRaw.value = '';
                }

                recalcAll();
            }

            // =========================
            // Row add/remove
            // =========================
            function focusRowItem(tr) {
                tr?.querySelector('.js-item-suggest-input')?.focus();
            }

            function addNewRow() {
                const lastRow = tableBody.querySelector('tr:last-child');
                const newRow = lastRow.cloneNode(true);

                resetItemSuggest(newRow);

                newRow.querySelectorAll('.line-qty-display, .line-price-display').forEach(inp => inp.value = '');
                newRow.querySelectorAll('.line-price-display').forEach(inp => inp.dataset.userEdited = '0');
                newRow.querySelectorAll('.line-qty-raw, .line-price-raw').forEach(inp => inp.value = '');

                // ✅ reset mapping hidden
                newRow.querySelectorAll('.line-alloc-raw').forEach(inp => {
                    inp.value = 'hpp';
                    inp.dataset.userEdited = '0';
                });
                newRow.querySelectorAll('.line-expacc-raw').forEach(inp => inp.value = '');

                const totalCell = newRow.querySelector('.line-total');
                if (totalCell) totalCell.textContent = '';

                tableBody.appendChild(newRow);
                renumberLines();
                recalcAll();

                focusRowItem(tableBody.querySelector('tr:last-child'));
            }

            btnAddTop?.addEventListener('click', addNewRow);
            btnAddBottom?.addEventListener('click', addNewRow);

            tableBody.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-remove-line');
                if (!btn) return;

                const rows = tableBody.querySelectorAll('tr');
                if (rows.length <= 1) {
                    const row = rows[0];
                    row.querySelectorAll('.line-qty-display, .line-price-display').forEach(inp => inp
                        .value = '');
                    row.querySelectorAll('.line-qty-raw, .line-price-raw').forEach(inp => inp.value = '');
                    row.querySelectorAll('.js-item-suggest-input').forEach(inp => inp.value = '');
                    row.querySelectorAll('.js-item-suggest-id, .js-item-suggest-category').forEach(h => h
                        .value = '');

                    // ✅ reset mapping
                    row.querySelectorAll('.line-alloc-raw').forEach(inp => inp.value = 'hpp');
                    row.querySelectorAll('.line-expacc-raw').forEach(inp => inp.value = '');

                    const totalCell = row.querySelector('.line-total');
                    if (totalCell) totalCell.textContent = '';

                    recalcAll();
                    focusRowItem(row);
                    return;
                }

                btn.closest('tr')?.remove();
                renumberLines();
                recalcAll();
            });

            // Enter: item -> qty -> price -> new row
            tableBody.addEventListener('keydown', function(e) {
                if (e.key !== 'Enter') return;

                const el = e.target;
                const tr = el.closest('tr');
                if (!tr) return;

                const isItem = el.classList.contains('js-item-suggest-input');
                const isQty = el.classList.contains('line-qty-display');
                const isPrice = el.classList.contains('line-price-display');

                if (isItem) {
                    e.preventDefault();
                    tr.querySelector('.line-qty-display')?.focus();
                    return;
                }
                if (isQty) {
                    e.preventDefault();
                    if (canSeeMoney) {
                        tr.querySelector('.line-price-display')?.focus();
                    } else {
                        addNewRow();
                    }
                    return;
                }
                if (isPrice) {
                    e.preventDefault();
                    addNewRow();
                    return;
                }
            }, true);

            // focus select
            tableBody.addEventListener('focusin', function(e) {
                const el = e.target;
                if (el.classList.contains('line-price-display')) selectAllLater(el);
                if (el.classList.contains('line-qty-display')) selectAllLater(el);
            }, true);

            // userEdited price flag
            tableBody.addEventListener('input', function(e) {
                if (e.target.classList.contains('line-price-display')) e.target.dataset.userEdited = '1';
            });

            // blur format
            tableBody.addEventListener('focusout', function(e) {
                const el = e.target;

                if (el.classList.contains('line-qty-display')) {
                    const txt = el.value.toString().trim();
                    if (txt === '') return;
                    const n = parseNumber(txt);
                    el.value = fmtQtyId(n);
                    const tr = el.closest('tr');
                    if (tr) {
                        syncRowRaw(tr);
                        recalcAll();
                    }
                    return;
                }

                if (el.classList.contains('line-price-display')) {
                    const tr = el.closest('tr');
                    const txt = el.value.toString().trim();

                    if (txt === '') {
                        el.dataset.userEdited = '0';
                        tr?.querySelector('.line-price-raw') && (tr.querySelector('.line-price-raw').value =
                            '');
                        recalcAll();
                        return;
                    }

                    const n = parseNumber(txt);
                    el.value = fmtIntId(n);
                    if (tr) {
                        syncRowRaw(tr);
                        recalcAll();
                    }
                    return;
                }
            }, true);

            // ✅ item picked -> apply meta mapping + last price
            tableBody.addEventListener('change', async function(e) {
                if (!e.target.classList.contains('js-item-suggest-id')) return;
                const tr = e.target.closest('tr');
                if (!tr) return;

                const priceDisp = tr.querySelector('.line-price-display');
                const priceRaw = tr.querySelector('.line-price-raw');
                if (priceDisp && priceRaw && (!priceRaw.value || Number(priceRaw.value) <= 0)) {
                    priceDisp.dataset.userEdited = '0';
                }

                await applyItemMetaToRow(tr, {
                    force: false
                });
                applyLastPriceToRow(tr, {
                    force: false
                });
            });

            // supplier change -> refresh last price on rows not edited
            supplierSelect?.addEventListener('change', function() {
                tableBody.querySelectorAll('tr').forEach(tr => applyLastPriceToRow(tr, {
                    force: false
                }));
            });

            // shipping format
            if (shippingDisplay && shippingRaw) {
                shippingDisplay.addEventListener('focusin', () => selectAllLater(shippingDisplay));
                shippingDisplay.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') e.preventDefault();
                });

                shippingDisplay.addEventListener('focusout', function() {
                    const txt = shippingDisplay.value.toString().trim();
                    if (txt === '') {
                        shippingDisplay.value = '';
                        shippingRaw.value = '';
                        recalcAll();
                        return;
                    }
                    const n = parseNumber(txt);
                    shippingDisplay.value = fmtIntId(n);
                    shippingRaw.value = String(Math.round(n || 0));
                    recalcAll();
                }, true);

                const initTxt = shippingDisplay.value.toString().trim();
                if (initTxt !== '') {
                    const n = parseNumber(initTxt);
                    shippingDisplay.value = fmtIntId(n);
                    shippingRaw.value = String(Math.round(n || 0));
                }
            }

            // order type change => reload
            if (orderTypeSelect) {
                orderTypeSelect.addEventListener('change', function() {
                    const nextType = orderTypeSelect.value || 'material';
                    if (nextType === currentOrderType) return;

                    if (isEdit) {
                        const ids = Array.from(document.querySelectorAll('.js-item-suggest-id'));
                        const hasFilled = ids.some(el => (el.value || '').toString().trim() !== '');
                        if (hasFilled) {
                            const ok = confirm(
                                'Mengubah Jenis PO akan memuat ulang daftar item. Pastikan item yang sudah dipilih sesuai jenis baru. Lanjut?'
                            );
                            if (!ok) {
                                orderTypeSelect.value = currentOrderType;
                                return;
                            }
                        }
                    }

                    const url = new URL(window.location.href);
                    url.searchParams.set('order_type', nextType);
                    window.location.href = url.toString();
                });
            }

            // init item suggest
            if (window.initItemSuggestInputs) window.initItemSuggestInputs();

            // init userEdited flag
            tableBody.querySelectorAll('tr').forEach(tr => {
                const priceDisp = tr.querySelector('.line-price-display');
                if (priceDisp && !priceDisp.dataset.userEdited) priceDisp.dataset.userEdited = '0';
            });

            // init apply meta + price for existing rows
            (async function initExisting() {
                const rows = Array.from(tableBody.querySelectorAll('tr'));
                for (const tr of rows) {
                    const itemId = tr.querySelector('.js-item-suggest-id')?.value;
                    if (!itemId) continue;

                    await applyItemMetaToRow(tr, {
                        force: false
                    });

                    const priceRaw = tr.querySelector('.line-price-raw')?.value;
                    if (!priceRaw || Number(priceRaw) <= 0) {
                        const priceDisp = tr.querySelector('.line-price-display');
                        if (priceDisp) priceDisp.dataset.userEdited = '0';
                        applyLastPriceToRow(tr, {
                            force: false
                        });
                    }
                }
                renumberLines();
                recalcAll();
                focusRowItem(tableBody.querySelector('tr'));
            })();
        });
    </script>
@endpush
