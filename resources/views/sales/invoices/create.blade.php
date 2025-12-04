@extends('layouts.app')

@section('title', 'Sales Invoice Baru')

@push('head')
    <style>
        .page-wrap {
            max-width: 1200px;
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

        .table-items thead th {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
        }

        .table-items input[type="text"],
        .table-items input[type="number"] {
            font-size: .82rem;
        }

        .btn-icon {
            padding: .22rem .5rem;
            font-size: .8rem;
            line-height: 1;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- Back --}}
        <div class="mb-3">
            <a href="{{ route('sales.invoices.index') }}" class="btn btn-sm btn-outline-secondary">
                ← Kembali ke List Invoice
            </a>
        </div>

        <form action="{{ route('sales.invoices.store') }}" method="POST" id="si-form">
            @csrf

            {{-- HEADER --}}
            <div class="card card-main mb-3">
                <div class="card-header fw-semibold">
                    Sales Invoice Baru
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        {{-- Tanggal --}}
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Tanggal Invoice</label>
                            <input type="date" name="date"
                                class="form-control form-control-sm @error('date') is-invalid @enderror"
                                value="{{ old('date', now()->toDateString()) }}" required>
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Warehouse --}}
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Warehouse</label>
                            <select name="warehouse_id"
                                class="form-select form-select-sm @error('warehouse_id') is-invalid @enderror" required>
                                <option value="">- Pilih Warehouse -</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" @selected(old('warehouse_id') == $wh->id)>
                                        {{ $wh->code }} — {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- (optional) Kode invoice display (auto di backend) --}}
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">No. Invoice</label>
                            <input type="text" class="form-control form-control-sm" value="(Auto generate)" disabled>
                        </div>

                        {{-- Customer QUICK SEARCH --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Customer</label>

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
                                    Belum ada customer yang dipilih. Jika dikosongkan, data buyer bisa diisi manual (versi
                                    lanjutan).
                                @endif
                            </div>

                            <div class="mt-1" id="customer_suggest_box" style="display:none;">
                                <div class="list-group list-group-flush border rounded-3 small"
                                    style="max-height: 180px; overflow-y:auto;"></div>
                            </div>
                        </div>

                        {{-- Catatan --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Catatan</label>
                            <textarea name="remarks" rows="2" class="form-control form-control-sm">{{ old('remarks') }}</textarea>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ITEMS TABLE --}}
            <div class="card card-main mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Items</span>
                    <button type="button" class="btn btn-sm btn-primary" id="btn-add-line">
                        + Tambah Item
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 table-items" id="items-table">
                            <thead>
                                <tr>
                                    <th style="width: 32%;">Item</th>
                                    <th style="width: 10%;" class="text-center">Qty</th>
                                    <th style="width: 14%;" class="text-end">Harga</th>
                                    <th style="width: 14%;" class="text-end">Diskon</th>
                                    <th style="width: 14%;" class="text-end">Subtotal</th>
                                    <th style="width: 8%;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- baris akan di-generate via JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Footer: total --}}
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Minimal isi 1 item dengan qty > 0.
                    </div>
                    <div class="text-end">
                        <div class="small text-muted mb-1">Subtotal</div>
                        <div class="fw-bold fs-6" id="subtotal_display">
                            Rp 0
                        </div>
                        <input type="hidden" name="subtotal" id="subtotal_input" value="0">
                    </div>
                </div>
            </div>

            {{-- SUBMIT --}}
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    Simpan Invoice
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        /**
         * QUICK SEARCH CUSTOMER
         * memakai endpoint: route('api.customers.suggest')
         */
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
                            ${c.phone ?? ''} ${c.email ? ' • ' + c.email : ''}
                        </div>
                    `;
                            btn.addEventListener('click', () => {
                                hiddenId.value = c.id;
                                info.textContent =
                                    `Customer terpilih: ${c.name}${c.phone ? ' (' + c.phone + ')' : ''}`;
                                suggestBox.style.display = 'none';
                                listContainer.innerHTML = '';
                                input.value = '';
                            });
                            listContainer.appendChild(btn);
                        });

                        suggestBox.style.display = 'block';
                    })
                    .catch(err => console.error(err));
            }
        })();
    </script>

    <script>
        /**
         * ITEMS TABLE:
         * - Tambah/hapus baris
         * - Hitung subtotal per line dan total header
         * - Item search (sederhana) lewat /api/items/suggest (silakan sesuaikan route-mu)
         */
        (function() {
            const tableBody = document.querySelector('#items-table tbody');
            const btnAdd = document.getElementById('btn-add-line');
            const subtotalDisplay = document.getElementById('subtotal_display');
            const subtotalInput = document.getElementById('subtotal_input');

            let lineIndex = 0;

            if (btnAdd) {
                btnAdd.addEventListener('click', () => addLine());
            }

            // tambahkan 1–2 baris awal
            addLine();
            addLine();

            function addLine() {
                const idx = lineIndex++;
                const tr = document.createElement('tr');
                tr.dataset.index = idx;

                tr.innerHTML = `
            <td>
                <input type="hidden" name="lines[${idx}][item_id]" class="item-id-input">
                <input type="text"
                       class="form-control form-control-sm item-search-input"
                       placeholder="Cari item (kode / nama)..."
                       autocomplete="off">
                <div class="small text-muted item-selected-label mt-1"></div>
                <div class="item-suggest-box mt-1" style="display:none;">
                    <div class="list-group list-group-flush border rounded-3 small"
                         style="max-height: 180px; overflow-y:auto;"></div>
                </div>
            </td>
            <td class="text-center">
                <input type="number"
                       step="0.0001"
                       min="0"
                       name="lines[${idx}][qty]"
                       class="form-control form-control-sm text-center qty-input"
                       value="0">
            </td>
            <td class="text-end">
                <input type="number"
                       step="0.01"
                       min="0"
                       name="lines[${idx}][unit_price]"
                       class="form-control form-control-sm text-end price-input"
                       value="0">
            </td>
            <td class="text-end">
                <input type="number"
                       step="0.01"
                       min="0"
                       name="lines[${idx}][line_discount]"
                       class="form-control form-control-sm text-end discount-input"
                       value="0">
            </td>
            <td class="text-end">
                <input type="text"
                       readonly
                       class="form-control form-control-sm text-end subtotal-line-input"
                       value="0">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-icon btn-remove-line">
                    ✕
                </button>
            </td>
        `;

                tableBody.appendChild(tr);

                attachLineEvents(tr);
                recalcTotals();
            }

            function attachLineEvents(tr) {
                const qtyInput = tr.querySelector('.qty-input');
                const priceInput = tr.querySelector('.price-input');
                const discountInput = tr.querySelector('.discount-input');
                const removeBtn = tr.querySelector('.btn-remove-line');
                const itemSearchInput = tr.querySelector('.item-search-input');

                [qtyInput, priceInput, discountInput].forEach(input => {
                    if (!input) return;
                    input.addEventListener('input', () => recalcLine(tr));
                });

                if (removeBtn) {
                    removeBtn.addEventListener('click', () => {
                        tr.remove();
                        recalcTotals();
                    });
                }

                // item search
                if (itemSearchInput) {
                    let timer = null;
                    itemSearchInput.addEventListener('input', function() {
                        const q = itemSearchInput.value.trim();

                        clearTimeout(timer);
                        if (q.length < 2) {
                            hideItemSuggest(tr);
                            return;
                        }

                        timer = setTimeout(() => fetchItemSuggest(tr, q), 250);
                    });
                }
            }

            function recalcLine(tr) {
                const qty = parseFloat(tr.querySelector('.qty-input')?.value || '0');
                const price = parseFloat(tr.querySelector('.price-input')?.value || '0');
                const discount = parseFloat(tr.querySelector('.discount-input')?.value || '0');

                let lineTotal = (qty * price) - discount;
                if (lineTotal < 0) lineTotal = 0;

                const subtotalInputLine = tr.querySelector('.subtotal-line-input');
                if (subtotalInputLine) {
                    subtotalInputLine.value = Math.round(lineTotal).toLocaleString('id-ID');
                }

                recalcTotals();
            }

            function recalcTotals() {
                let subtotal = 0;

                document.querySelectorAll('.subtotal-line-input').forEach(input => {
                    // input line berisi string formatted, lebih aman ambil qty & price lagi
                });

                document.querySelectorAll('#items-table tbody tr').forEach(tr => {
                    const qty = parseFloat(tr.querySelector('.qty-input')?.value || '0');
                    const price = parseFloat(tr.querySelector('.price-input')?.value || '0');
                    const discount = parseFloat(tr.querySelector('.discount-input')?.value || '0');
                    let lineTotal = (qty * price) - discount;
                    if (lineTotal < 0) lineTotal = 0;
                    subtotal += lineTotal;
                });

                subtotalInput.value = subtotal.toFixed(2);
                subtotalDisplay.textContent = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');
            }

            function fetchItemSuggest(tr, q) {
                const box = tr.querySelector('.item-suggest-box');
                const list = box?.querySelector('.list-group');
                if (!box || !list) return;

                const url = `/api/items/suggest?q=${encodeURIComponent(q)}&limit=20`; // sesuaikan dengan route API-mu

                fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(json => {
                        const items = json.data || json; // sesuaikan structure response-mu
                        list.innerHTML = '';

                        if (!items.length) {
                            box.style.display = 'none';
                            return;
                        }

                        items.forEach(it => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'list-group-item list-group-item-action';
                            btn.innerHTML = `
                        <div class="fw-semibold">${it.code ?? ''} — ${it.name}</div>
                        <div class="small text-muted">
                            ${it.item_category ?? ''} ${it.type ? '• ' + it.type : ''}
                        </div>
                    `;
                            btn.addEventListener('click', () => {
                                selectItem(tr, it);
                                box.style.display = 'none';
                                list.innerHTML = '';
                            });
                            list.appendChild(btn);
                        });

                        box.style.display = 'block';
                    })
                    .catch(err => console.error(err));
            }

            function hideItemSuggest(tr) {
                const box = tr.querySelector('.item-suggest-box');
                const list = box?.querySelector('.list-group');
                if (!box || !list) return;
                box.style.display = 'none';
                list.innerHTML = '';
            }

            function selectItem(tr, it) {
                const itemIdInput = tr.querySelector('.item-id-input');
                const label = tr.querySelector('.item-selected-label');
                const searchInput = tr.querySelector('.item-search-input');

                if (itemIdInput) itemIdInput.value = it.id;
                if (label) label.textContent = `${it.code ?? ''} — ${it.name}`;
                if (searchInput) searchInput.value = '';
            }

        })();
    </script>
@endpush
