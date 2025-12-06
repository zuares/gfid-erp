@extends('layouts.app')

@php
    /** @var \App\Models\SalesInvoice $invoice */

    $pageTitle = 'Edit Invoice • ' . $invoice->code;
    $pageMeta = 'EDIT INVOICE';

    $defaultDate = old('date', optional($invoice->date)->toDateString() ?? now()->toDateString());
    $defaultWarehouseId = old('warehouse_id', $defaultWarehouseId ?? $invoice->warehouse_id);
    $defaultCustomerId = old('customer_id', $defaultCustomerId ?? $invoice->customer_id);
    $defaultStoreId = old('store_id', $defaultStoreId ?? $invoice->store_id);

    // PrefilledLines idealnya dikirim dari controller
    $initialLines = old('items', $prefilledLines ?? []);
@endphp

@section('title', $pageTitle)

@push('head')
    {{-- pakai CSS sama persis dengan create.blade.php, boleh di-include atau copy --}}
    <style>
        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: .75rem .75rem 3.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 26%,
                    #f9fafb 60%);
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(15, 23, 42, 0.9) 0,
                    #020617 65%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            box-shadow:
                0 8px 24px rgba(15, 23, 42, 0.12),
                0 0 0 1px rgba(148, 163, 184, 0.12);
        }

        .meta-label {
            font-size: .7rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #6b7280;
        }

        body[data-theme="dark"] .meta-label {
            color: #9ca3af;
        }

        .summary-pill {
            border-radius: 999px;
            padding: .2rem .75rem;
            font-size: .8rem;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(248, 250, 252, 0.96);
        }

        body[data-theme="dark"] .summary-pill {
            background: rgba(15, 23, 42, 0.98);
            border-color: rgba(30, 64, 175, 0.7);
            color: #e5e7eb;
        }

        .btn-chip {
            border-radius: 999px;
            font-size: .78rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding-inline: 1rem;
            padding-block: .35rem;
        }

        .table-lines thead th {
            border-bottom-width: 1px;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7280;
            background: rgba(248, 250, 252, 0.96);
        }

        body[data-theme="dark"] .table-lines thead th {
            background: rgba(15, 23, 42, 0.98);
            border-bottom-color: rgba(30, 64, 175, 0.75);
            color: #9ca3af;
        }

        .table-lines tbody td {
            vertical-align: middle;
            border-top-color: rgba(148, 163, 184, 0.18);
            padding-top: .35rem;
            padding-bottom: .35rem;
        }

        body[data-theme="dark"] .table-lines tbody td {
            border-top-color: rgba(51, 65, 85, 0.9);
        }

        .lines-wrapper {
            max-height: 420px;
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.7) transparent;
        }

        .lines-wrapper::-webkit-scrollbar {
            width: 6px;
        }

        .lines-wrapper::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.7);
            border-radius: 999px;
        }

        .badge-status {
            border-radius: 999px;
            padding: .18rem .7rem;
            font-size: .72rem;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .badge-status-draft {
            background: rgba(251, 191, 36, 0.10);
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.25);
        }

        .badge-status-unpriced {
            background: rgba(249, 115, 22, 0.12);
            color: #9a3412;
            border: 1px solid rgba(248, 150, 108, 0.6);
        }

        .badge-status-posted {
            background: rgba(34, 197, 94, 0.10);
            color: #166534;
            border: 1px solid rgba(34, 197, 94, 0.25);
        }

        body[data-theme="dark"] .badge-status-draft {
            background: rgba(251, 191, 36, 0.25);
            color: #fef9c3;
            border-color: rgba(245, 158, 11, 0.7);
        }

        body[data-theme="dark"] .badge-status-unpriced {
            background: rgba(248, 150, 108, 0.25);
            color: #ffedd5;
            border-color: rgba(248, 150, 108, 0.8);
        }

        body[data-theme="dark"] .badge-status-posted {
            background: rgba(34, 197, 94, 0.25);
            color: #bbf7d0;
            border-color: rgba(34, 197, 94, 0.8);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="meta-label mb-1">
                    EDIT INVOICE
                </div>
                <h1 class="h4 mb-1">
                    {{ $invoice->code }}
                </h1>

                <div class="d-flex flex-wrap gap-2 align-items-center small text-muted">
                    <span>{{ id_date($invoice->date) }}</span>

                    @if ($invoice->status === 'posted')
                        <span class="badge-status badge-status-posted">Posted</span>
                    @elseif ($invoice->status === 'unpriced')
                        <span class="badge-status badge-status-unpriced">Unpriced</span>
                    @else
                        <span class="badge-status badge-status-draft">Draft</span>
                    @endif
                </div>
            </div>

            <div class="text-end">
                <div class="mb-2">
                    <a href="{{ route('sales.invoices.show', $invoice) }}" class="btn btn-outline-secondary btn-sm">
                        &larr; Detail Invoice
                    </a>
                </div>
                <div class="small text-muted">
                    Dibuat: {{ id_datetime($invoice->created_at) }} <br>
                    Update: {{ id_datetime($invoice->updated_at) }}
                </div>
            </div>
        </div>

        {{-- ALERT STATUS UNPRICED --}}
        @if ($invoice->status === 'unpriced')
            <div class="alert alert-warning small">
                Invoice ini masih <strong>UNPRICED</strong>. Lengkapi harga / diskon lalu simpan lagi sebelum posting.
            </div>
        @endif

        {{-- ERRORS --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- FORM --}}
        <form action="{{ route('sales.invoices.update', $invoice) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- INFO UTAMA --}}
            <div class="card card-main mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="meta-label mb-0">
                            Info Utama
                        </div>

                        <div class="summary-pill">
                            Status:
                            <strong class="ms-1">{{ strtoupper($invoice->status) }}</strong>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small">Tanggal</label>
                            <input type="date" name="date" class="form-control form-control-sm"
                                value="{{ $defaultDate }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Gudang</label>
                            <select name="warehouse_id" class="form-select form-select-sm" required>
                                <option value="">Pilih gudang...</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" @selected($defaultWarehouseId == $wh->id)>
                                        {{ $wh->code }} — {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Customer (opsional)</label>
                            <select name="customer_id" class="form-select form-select-sm">
                                <option value="">Tanpa customer</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}" @selected($defaultCustomerId == $c->id)>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Store / Channel (opsional)</label>
                            <select name="store_id" class="form-select form-select-sm">
                                <option value="">Tanpa store</option>
                                @foreach ($stores as $s)
                                    <option value="{{ $s->id }}" @selected($defaultStoreId == $s->id)>
                                        {{ $s->code }} — {{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Diskon Header (Rp)</label>
                            <input type="number" step="0.01" min="0" name="header_discount"
                                class="form-control form-control-sm"
                                value="{{ old('header_discount', $invoice->discount_total ?? 0) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">PPN (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="tax_percent"
                                class="form-control form-control-sm"
                                value="{{ old('tax_percent', $invoice->tax_percent ?? 0) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Catatan</label>
                            <textarea name="remarks" rows="2" class="form-control form-control-sm">{{ old('remarks', $invoice->remarks ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- LINES --}}
            <div class="card card-main">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="meta-label mb-1">Daftar Item</div>
                            <div class="small text-muted">
                                Ubah qty / harga / diskon. Jika semua harga kosong / 0 → status akan tetap
                                <strong>UNPRICED</strong>.
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm btn-chip" id="btnAddLine">
                            + Tambah Baris
                        </button>
                    </div>

                    <div class="lines-wrapper">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle table-lines mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;"></th>
                                        <th style="width: 260px;">Item</th>
                                        <th style="width: 80px;">Qty</th>
                                        <th style="width: 140px;">Harga /pcs</th>
                                        <th style="width: 120px;">Diskon line</th>
                                        <th style="width: 140px;" class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="invoice-lines-body" data-next-index="{{ count($initialLines) ?: 0 }}">
                                    @forelse ($initialLines as $idx => $line)
                                        @php
                                            $itemIdOld = old("items.$idx.item_id", $line['item_id'] ?? null);
                                            $qtyOld = old("items.$idx.qty", $line['qty'] ?? 1);
                                            $priceOld = old("items.$idx.unit_price", $line['unit_price'] ?? null);
                                            $discOld = old("items.$idx.line_discount", $line['line_discount'] ?? 0);
                                        @endphp
                                        <tr>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-outline-danger btn-sm px-2 py-0"
                                                    onclick="removeLineRow(this)">×</button>
                                            </td>
                                            <td>
                                                <select name="items[{{ $idx }}][item_id]"
                                                    class="form-select form-select-sm item-select" required>
                                                    <option value="">Pilih item...</option>
                                                    @foreach ($items as $item)
                                                        <option value="{{ $item->id }}"
                                                            data-hpp="{{ $item->hpp_unit }}" @selected($itemIdOld == $item->id)>
                                                            {{ $item->code }} — {{ $item->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" min="1"
                                                    name="items[{{ $idx }}][qty]"
                                                    class="form-control form-control-sm qty-input"
                                                    value="{{ $qtyOld }}" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0"
                                                    name="items[{{ $idx }}][unit_price]"
                                                    class="form-control form-control-sm price-input"
                                                    value="{{ $priceOld }}">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0"
                                                    name="items[{{ $idx }}][line_discount]"
                                                    class="form-control form-control-sm discount-input"
                                                    value="{{ $discOld }}">
                                            </td>
                                            <td class="text-end">
                                                <span class="line-subtotal">0</span>
                                            </td>
                                        </tr>
                                    @empty
                                        {{-- kalau controller lupa kirim prefilledLines, kasih 1 baris kosong --}}
                                        <tr>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-outline-danger btn-sm px-2 py-0"
                                                    onclick="removeLineRow(this)">×</button>
                                            </td>
                                            <td>
                                                <select name="items[0][item_id]"
                                                    class="form-select form-select-sm item-select" required>
                                                    <option value="">Pilih item...</option>
                                                    @foreach ($items as $item)
                                                        <option value="{{ $item->id }}"
                                                            data-hpp="{{ $item->hpp_unit }}">
                                                            {{ $item->code }} — {{ $item->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" min="1" name="items[0][qty]"
                                                    class="form-control form-control-sm qty-input" value="1"
                                                    required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0"
                                                    name="items[0][unit_price]"
                                                    class="form-control form-control-sm price-input" value="">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0"
                                                    name="items[0][line_discount]"
                                                    class="form-control form-control-sm discount-input" value="0">
                                            </td>
                                            <td class="text-end">
                                                <span class="line-subtotal">0</span>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            Subtotal & PPN akan dihitung ulang saat disimpan.<br>
                            Margin laporan tetap pakai HPP final dari ProductionCostPeriod aktif.<br>
                            Jika semua harga kosong / 0 → invoice tetap <strong>UNPRICED</strong>.
                        </div>
                        <button type="submit" class="btn btn-primary btn-chip">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    {{-- JS sama persis dengan di create.blade.php --}}
    <script>
        function removeLineRow(btn) {
            const tr = btn.closest('tr');
            if (!tr) return;
            const tbody = tr.parentElement;
            tr.remove();

            if (!tbody.querySelector('tr')) {
                addLineRow();
            }

            recalcSubtotals();
        }

        function recalcSubtotals() {
            const tbody = document.getElementById('invoice-lines-body');
            if (!tbody) return;
            const rows = tbody.querySelectorAll('tr');

            rows.forEach(row => {
                const qty = parseFloat(row.querySelector('.qty-input')?.value || '0');
                const price = parseFloat(row.querySelector('.price-input')?.value || '0');
                const disc = parseFloat(row.querySelector('.discount-input')?.value || '0');
                let subtotal = (qty * price) - disc;
                if (subtotal < 0) subtotal = 0;
                const el = row.querySelector('.line-subtotal');
                if (el) {
                    el.textContent = new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }).format(subtotal);
                }
            });
        }

        function addLineRow() {
            const tbody = document.getElementById('invoice-lines-body');
            if (!tbody) return;

            let nextIndex = parseInt(tbody.dataset.nextIndex || '0', 10);

            const html = `
<tr>
    <td class="text-center align-middle">
        <button type="button" class="btn btn-outline-danger btn-sm px-2 py-0" onclick="removeLineRow(this)">×</button>
    </td>
    <td>
        <select name="items[${nextIndex}][item_id]" class="form-select form-select-sm item-select" required>
            <option value="">Pilih item...</option>
            @foreach ($items as $item)
                <option value="{{ $item->id }}" data-hpp="{{ $item->hpp_unit }}">
                    {{ $item->code }} — {{ $item->name }}
                </option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="number" min="1" name="items[${nextIndex}][qty]" class="form-control form-control-sm qty-input" value="1" required>
    </td>
    <td>
        <input type="number" step="0.01" min="0" name="items[${nextIndex}][unit_price]" class="form-control form-control-sm price-input" value="">
    </td>
    <td>
        <input type="number" step="0.01" min="0" name="items[${nextIndex}][line_discount]" class="form-control form-control-sm discount-input" value="0">
    </td>
    <td class="text-end">
        <span class="line-subtotal">0</span>
    </td>
</tr>`;

            tbody.insertAdjacentHTML('beforeend', html);
            tbody.dataset.nextIndex = nextIndex + 1;
            recalcSubtotals();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const btnAdd = document.getElementById('btnAddLine');
            if (btnAdd) {
                btnAdd.addEventListener('click', function() {
                    addLineRow();
                });
            }

            const tbody = document.getElementById('invoice-lines-body');
            if (tbody) {
                tbody.addEventListener('input', function(e) {
                    if (e.target.matches('.qty-input, .price-input, .discount-input')) {
                        recalcSubtotals();
                    }
                });
            }

            recalcSubtotals();
        });
    </script>
@endpush
