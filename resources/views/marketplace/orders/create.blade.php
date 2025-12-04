@extends('layouts.app')

@section('title', 'Tambah Marketplace Order')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 26%,
                    #f9fafb 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.30);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.03);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <div class="mb-3">
            <a href="{{ route('marketplace.orders.index') }}" class="btn btn-sm btn-outline-secondary">
                ← Kembali ke list
            </a>
        </div>

        <form action="{{ route('marketplace.orders.store') }}" method="POST">
            @csrf

            {{-- HEADER --}}
            <div class="card card-main mb-3">
                <div class="card-header fw-semibold">
                    Header Order
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Store</label>
                            <select name="store_id" class="form-select form-select-sm" required>
                                <option value="">- Pilih Store -</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>
                                        [{{ $store->channel->code }}] {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">No. Order Marketplace</label>
                            <input type="text" name="external_order_id" class="form-control form-control-sm"
                                value="{{ old('external_order_id') }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">No. Invoice (opsional)</label>
                            <input type="text" name="external_invoice_no" class="form-control form-control-sm"
                                value="{{ old('external_invoice_no') }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Tanggal Order</label>
                            <input type="datetime-local" name="order_date" class="form-control form-control-sm"
                                value="{{ old('order_date', now()->format('Y-m-d\TH:i')) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Nama Buyer</label>
                            <input type="text" name="buyer_name" class="form-control form-control-sm"
                                value="{{ old('buyer_name') }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Telp/HP Buyer</label>
                            <input type="text" name="buyer_phone" class="form-control form-control-sm"
                                value="{{ old('buyer_phone') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Alamat Pengiriman</label>
                            <textarea name="shipping_address" rows="2" class="form-control form-control-sm" placeholder="Alamat lengkap">{{ old('shipping_address') }}</textarea>
                        </div>

                        {{-- CUSTOMER QUICK SEARCH --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Customer (opsional)</label>

                            <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id') }}">

                            <div class="input-group input-group-sm mb-1">
                                <input type="text" id="customer_search_input" class="form-control form-control-sm"
                                    placeholder="Cari nama / telepon customer..." autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="window.open('{{ route('customers.index') }}', '_blank')">
                                    List Customers
                                </button>
                            </div>

                            <div class="small text-muted" id="customer_selected_info">
                                @if (old('customer_id'))
                                    Customer terpilih (ID: {{ old('customer_id') }})
                                @else
                                    Belum ada customer yang dipilih. Kalau dikosongkan, sistem akan auto-create dari
                                    nama/telepon buyer.
                                @endif
                            </div>

                            <div class="mt-1" id="customer_suggest_box" style="display:none;">
                                <div class="list-group list-group-flush border rounded-3 small"
                                    style="max-height: 180px; overflow-y:auto;"></div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold small">Catatan (opsional)</label>
                            <textarea name="remarks" rows="2" class="form-control form-control-sm" placeholder="Catatan internal">{{ old('remarks') }}</textarea>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ITEMS --}}
            <div class="card card-main mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Items</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-line">
                        + Tambah Baris
                    </button>
                </div>
                <div class="card-body p-0">
                    @error('lines')
                        <div class="alert alert-danger m-2 py-1 px-2 small">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" id="order-lines-table">
                            <thead>
                                <tr class="text-muted">
                                    <th style="width: 15%">External SKU</th>
                                    <th>Nama Item (snapshot)</th>
                                    <th style="width: 10%" class="text-center">Qty</th>
                                    <th style="width: 15%" class="text-end">Harga</th>
                                    <th style="width: 15%" class="text-end">Subtotal</th>
                                    <th style="width: 5%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $oldLines = old('lines', [
                                        ['external_sku' => null, 'item_name' => null, 'qty' => 1, 'price' => 0],
                                        ['external_sku' => null, 'item_name' => null, 'qty' => 1, 'price' => 0],
                                    ]);
                                @endphp

                                @foreach ($oldLines as $i => $line)
                                    <tr>
                                        <td>
                                            <input type="text" name="lines[{{ $i }}][external_sku]"
                                                class="form-control form-control-sm"
                                                value="{{ $line['external_sku'] ?? '' }}" placeholder="SKU toko">
                                        </td>
                                        <td>
                                            <input type="text" name="lines[{{ $i }}][item_name]"
                                                class="form-control form-control-sm"
                                                value="{{ $line['item_name'] ?? '' }}"
                                                placeholder="Nama item di marketplace">
                                        </td>
                                        <td class="text-center">
                                            <input type="number" name="lines[{{ $i }}][qty]"
                                                class="form-control form-control-sm text-center js-line-qty"
                                                value="{{ $line['qty'] ?? 1 }}" min="0" step="1">
                                        </td>
                                        <td class="text-end">
                                            <input type="number" name="lines[{{ $i }}][price]"
                                                class="form-control form-control-sm text-end js-line-price"
                                                value="{{ $line['price'] ?? 0 }}" min="0" step="1">
                                        </td>
                                        <td class="text-end">
                                            <span class="js-line-subtotal">0</span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line">
                                                &times;
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach

                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-end">Subtotal Items</th>
                                    <th class="text-end">
                                        <span id="order-subtotal-display">0</span>
                                    </th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button class="btn btn-primary px-4">
                    Simpan Order
                </button>
            </div>
        </form>

    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const table = document.getElementById('order-lines-table');
            const btnAdd = document.getElementById('btn-add-line');
            const subtotalDisplay = document.getElementById('order-subtotal-display');

            if (!table || !btnAdd) return;

            function getNextIndex() {
                const rows = table.querySelectorAll('tbody tr');
                return rows.length;
            }

            btnAdd.addEventListener('click', function() {
                const index = getNextIndex();
                const tbody = table.querySelector('tbody');

                const tpl = `
                <tr>
                    <td>
                        <input type="text"
                               name="lines[${index}][external_sku]"
                               class="form-control form-control-sm"
                               placeholder="SKU toko">
                    </td>
                    <td>
                        <input type="text"
                               name="lines[${index}][item_name]"
                               class="form-control form-control-sm"
                               placeholder="Nama item di marketplace">
                    </td>
                    <td class="text-center">
                        <input type="number"
                               name="lines[${index}][qty]"
                               class="form-control form-control-sm text-center js-line-qty"
                               value="1"
                               min="0"
                               step="1">
                    </td>
                    <td class="text-end">
                        <input type="number"
                               name="lines[${index}][price]"
                               class="form-control form-control-sm text-end js-line-price"
                               value="0"
                               min="0"
                               step="1">
                    </td>
                    <td class="text-end">
                        <span class="js-line-subtotal">0</span>
                    </td>
                    <td class="text-center">
                        <button type="button"
                                class="btn btn-sm btn-outline-danger btn-remove-line">
                            &times;
                        </button>
                    </td>
                </tr>
            `;

                tbody.insertAdjacentHTML('beforeend', tpl);
                recalcAll();
            });

            table.addEventListener('click', function(e) {
                if (e.target.closest('.btn-remove-line')) {
                    const row = e.target.closest('tr');
                    row?.remove();
                    recalcAll();
                }
            });

            table.addEventListener('input', function(e) {
                if (e.target.classList.contains('js-line-qty') ||
                    e.target.classList.contains('js-line-price')) {
                    const row = e.target.closest('tr');
                    recalcRow(row);
                    recalcAll();
                }
            });

            function recalcRow(row) {
                if (!row) return;
                const qtyInput = row.querySelector('.js-line-qty');
                const priceInput = row.querySelector('.js-line-price');
                const subtotalSpan = row.querySelector('.js-line-subtotal');

                const qty = parseFloat(qtyInput?.value || '0') || 0;
                const price = parseFloat(priceInput?.value || '0') || 0;
                const subtotal = qty * price;

                subtotalSpan.textContent = formatRupiah(subtotal);
            }

            function recalcAll() {
                const rows = table.querySelectorAll('tbody tr');
                let total = 0;

                rows.forEach(row => {
                    const qtyInput = row.querySelector('.js-line-qty');
                    const priceInput = row.querySelector('.js-line-price');
                    const qty = parseFloat(qtyInput?.value || '0') || 0;
                    const price = parseFloat(priceInput?.value || '0') || 0;
                    total += qty * price;
                });

                if (subtotalDisplay) {
                    subtotalDisplay.textContent = formatRupiah(total);
                }
            }

            function formatRupiah(value) {
                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(value);
            }

            // initial calc
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(recalcRow);
            recalcAll();
        })();

        // QUICK SEARCH CUSTOMER
        (function() {
            const input = document.getElementById('customer_search_input');
            const hiddenId = document.getElementById('customer_id');
            const info = document.getElementById('customer_selected_info');
            const suggestBox = document.getElementById('customer_suggest_box');
            const listContainer = suggestBox ? suggestBox.querySelector('.list-group') : null;

            if (!input || !hiddenId || !info || !suggestBox || !listContainer) return;

            let timer = null;

            input.addEventListener('input', function() {
                const q = input.value.trim();

                clearTimeout(timer);
                if (q.length < 2) {
                    suggestBox.style.display = 'none';
                    listContainer.innerHTML = '';
                    return;
                }

                timer = setTimeout(() => fetchSuggest(q), 250);
            });

            function fetchSuggest(q) {
                const url = `{{ route('api.customers.suggest') }}?q=${encodeURIComponent(q)}`;

                fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(json => {
                        const items = json.data || [];
                        listContainer.innerHTML = '';

                        if (!items.length) {
                            suggestBox.style.display = 'none';
                            return;
                        }

                        items.forEach(c => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'list-group-item list-group-item-action';
                            btn.innerHTML = `
                            <div class="fw-semibold">${c.name}</div>
                            <div class="small text-muted">
                                ${(c.phone ?? '')} ${c.email ? ' • ' + c.email : ''}
                            </div>
                        `;
                            btn.addEventListener('click', () => {
                                hiddenId.value = c.id;
                                info.textContent =
                                    `Customer terpilih: ${c.name}${c.phone ? ' (' + c.phone + ')' : ''}`;
                                suggestBox.style.display = 'none';
                                listContainer.innerHTML = '';
                            });
                            listContainer.appendChild(btn);
                        });

                        suggestBox.style.display = 'block';
                    })
                    .catch(err => {
                        console.error(err);
                    });
            }
        })();
    </script>
@endpush
