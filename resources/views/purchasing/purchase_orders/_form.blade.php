{{-- resources/views/purchasing/purchase_orders/_form.blade.php --}}
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
        'closed' => 'Closed',
    ];
    $statusValue = old('status', $order?->status ?? 'draft');

    // === DETAIL LINES ===
    $oldLines = old('lines');
    $usingOldLines = $oldLines !== null;

    if ($usingOldLines) {
        // setelah validation error, pakai apa adanya dari request
        $linesData = $oldLines;
    } elseif (isset($lines)) {
        // dari controller (bisa Collection atau array)
        $linesData = is_array($lines) ? $lines : $lines->toArray();
    } else {
        $linesData = [];
    }
@endphp

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">

            {{-- TANGGAL --}}
            <div class="col-md-3">
                <label class="form-label">Tanggal</label>
                <input type="date" name="date" value="{{ $orderDate }}"
                    class="form-control @error('date') is-invalid @enderror">
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- SUPPLIER --}}
            <div class="col-md-3">
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
            <div class="col-md-3">
                <label class="form-label">Ongkir (Rp)</label>
                <input type="text" name="shipping_cost" value="{{ $shippingCostInput }}"
                    class="form-control text-end @error('shipping_cost') is-invalid @enderror">
                @error('shipping_cost')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- STATUS --}}
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select @error('status') is-invalid @enderror">
                    @foreach ($statusOptions as $key => $label)
                        <option value="{{ $key }}" @selected($statusValue === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </div>
</div>

{{-- DETAIL --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Detail Barang</span>
        <button type="button" id="btn-add-line" class="btn btn-sm btn-outline-primary">
            + Tambah Baris
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-sm mb-0" id="po-lines-table">
            <thead class="table-light">
                <tr>
                    <th style="width:5%">No</th>
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

                        // qty: pakai apa adanya (old() atau DB)
                        $lineQty = $lineQtyRaw;

                        // unit_price:
                        // - kalau dari old('lines') → jangan diformat lagi
                        // - kalau dari DB          → boleh diformat angka()
                        $linePrice = $usingOldLines ? $linePriceRaw : angka($linePriceRaw);

                        // display awal di input (mini → cuma kode cukup)
                        $itemCode = $line['item']['code'] ?? null;
                        $itemDisplay = $itemCode ?? '';
                    @endphp

                    <tr>
                        <td class="text-center align-middle line-index">{{ $loop->iteration }}</td>

                        {{-- ITEM pakai item-suggest (varian mini) --}}
                        <td>
                            <x-item-suggest :items="$items" idName="lines[{{ $i }}][item_id]"
                                :idValue="$lineItemId" :displayValue="$itemDisplay" type="material" variant="mini" :minChars="1" />
                            @error("lines.$i.item_id")
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </td>

                        <td>
                            <input type="text" name="lines[{{ $i }}][qty]" value="{{ $lineQty }}"
                                class="form-control form-control-sm text-end line-qty">
                            @error("lines.$i.qty")
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </td>

                        <td>
                            <input type="text" name="lines[{{ $i }}][unit_price]"
                                value="{{ $linePrice }}" class="form-control form-control-sm text-end line-price">
                            @error("lines.$i.unit_price")
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </td>

                        <td class="text-end align-middle line-total"></td>

                        <td class="text-center">
                            <button type="button"
                                class="btn btn-sm btn-outline-danger btn-remove-line">&times;</button>
                        </td>
                    </tr>

                @empty
                    {{-- DEFAULT 1 ROW --}}
                    <tr>
                        <td class="text-center align-middle line-index">1</td>

                        <td>
                            {{-- <x-item-suggest :items="$items" idName="lines[0][item_id]" type="material" variant="mini"
                                :minChars="1" /> --}}
                            <x-item-suggest idName="lines[0][item_id]" :items="$items" displayMode="code-name"
                                :showName="true" :showCategory="false" {{-- baris kedua: tanpa kategori --}} type="material"
                                placeholder="Cari kode / nama item" placeholder="Masukan Kode Barang" />
                        </td>

                        <td>
                            <input type="text" name="lines[0][qty]"
                                class="form-control form-control-sm text-end line-qty">
                        </td>

                        <td>
                            <input type="text" name="lines[0][unit_price]"
                                class="form-control form-control-sm text-end line-price">
                        </td>

                        <td class="text-end align-middle line-total"></td>

                        <td class="text-center">
                            <button type="button"
                                class="btn btn-sm btn-outline-danger btn-remove-line">&times;</button>
                        </td>
                    </tr>
                @endforelse
            </tbody>

            <tfoot class="table-light">
                <tr>
                    <th colspan="4" class="text-end">Subtotal</th>
                    <th class="text-end" id="po-subtotal-cell"></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.querySelector('#po-lines-table tbody');
            const btnAdd = document.getElementById('btn-add-line');
            const subtotalCell = document.getElementById('po-subtotal-cell');

            function parseNumber(value) {
                if (!value) return 0;
                value = value.toString().trim();

                // hilangkan spasi
                value = value.replace(/\s+/g, '');

                // Kalau ada koma → format Indonesia "1.234,56"
                if (value.indexOf(',') !== -1) {
                    value = value.replace(/\./g, ''); // hilangkan titik ribuan
                    value = value.replace(',', '.'); // koma → titik desimal
                    const n = parseFloat(value);
                    return isNaN(n) ? 0 : n;
                }

                // Kalau tidak ada koma tapi ada titik dan pola ribuan "1.234" / "1.234.567"
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
                    // Update index tampilan
                    const idxCell = tr.querySelector('.line-index');
                    if (idxCell) idxCell.textContent = index + 1;

                    // Update name attribute sesuai index
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

            btnAdd?.addEventListener('click', function() {
                const lastRow = tableBody.querySelector('tr:last-child');
                const newRow = lastRow.cloneNode(true);

                // reset value input qty / price
                newRow.querySelectorAll('.line-qty, .line-price').forEach(function(input) {
                    input.value = '';
                });

                // reset item-suggest (display + hidden id/category)
                newRow.querySelectorAll('.js-item-suggest-input').forEach(function(input) {
                    input.value = '';
                });
                newRow.querySelectorAll('.js-item-suggest-id').forEach(function(hidden) {
                    hidden.value = '';
                });
                newRow.querySelectorAll('.js-item-suggest-category').forEach(function(hidden) {
                    hidden.value = '';
                });

                // reset total
                const totalCell = newRow.querySelector('.line-total');
                if (totalCell) totalCell.textContent = '';

                // supaya initItemSuggestInputs bisa detect ini row baru lagi
                newRow.querySelectorAll('.item-suggest-wrap').forEach(function(wrap) {
                    wrap.removeAttribute('data-suggest-inited');
                });

                tableBody.appendChild(newRow);
                renumberLines();
                recalcAll();

                // INIT item-suggest untuk row baru
                if (window.initItemSuggestInputs) {
                    window.initItemSuggestInputs(newRow);
                }
            });

            tableBody.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-remove-line')) {
                    const rows = tableBody.querySelectorAll('tr');
                    if (rows.length <= 1) {
                        // kalau cuma 1 baris: kosongkan saja
                        const row = rows[0];
                        row.querySelectorAll('.line-qty, .line-price').forEach(function(input) {
                            input.value = '';
                        });
                        row.querySelectorAll('.js-item-suggest-input').forEach(function(input) {
                            input.value = '';
                        });
                        row.querySelectorAll('.js-item-suggest-id, .js-item-suggest-category').forEach(
                            function(
                                hidden) {
                                hidden.value = '';
                            });
                        const totalCell = row.querySelector('.line-total');
                        if (totalCell) totalCell.textContent = '';
                        recalcAll();
                        return;
                    }

                    e.target.closest('tr').remove();
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

            // Inisialisasi pertama kali
            recalcAll();

            // Inisialisasi item-suggest awal (kalau script komponen sudah dimuat)
            if (window.initItemSuggestInputs) {
                window.initItemSuggestInputs();
            }
        });
    </script>
@endpush
