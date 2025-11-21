{{-- resources/views/purchasing/purchase_receipts/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Purchasing • Penerimaan Barang (GRN)')

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        thead th {
            background: var(--panel);
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .table-sm td,
        .table-sm th {
            padding-block: .4rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .badge-po {
            border-radius: 999px;
            padding: .15rem .65rem;
            font-size: .75rem;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, .12);
        }

        .item-main {
            font-weight: 600;
        }

        .item-sub {
            font-size: .8rem;
            color: var(--muted);
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .col-price {
                display: none;
            }

            .item-main {
                font-size: .85rem;
            }

            .item-sub {
                font-size: .75rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        /** @var \App\Models\PurchaseOrder|null $order */

        $hasOrder = isset($order) && $order?->id;

        $defaultDate = old('date', now()->toDateString());

        $selectedSupplierId = old(
            'supplier_id',
            $hasOrder ? $order->supplier_id : $selectedSupplierId ?? request('supplier_id'),
        );
        $selectedSupplier = isset($suppliers) ? $suppliers->firstWhere('id', $selectedSupplierId) : null;

        $detailLines = $hasOrder ? $order->lines : $lines ?? collect();
    @endphp

    <div class="page-wrap py-3">

        {{-- HEADER TITLE --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">
                    Penerimaan Barang (GRN)
                </h4>

                @if ($hasOrder)
                    <div class="small text-muted">
                        PO:
                        <span class="badge-po mono">{{ $order->code }}</span>
                        • {{ optional($order->date)->format('d/m/Y') ?? '-' }} •
                        Supplier: {{ optional($order->supplier)->code ?? '-' }}
                        — {{ optional($order->supplier)->name ?? '-' }}
                    </div>
                @else
                    <div class="small text-muted">
                        Pilih supplier terlebih dahulu untuk menampilkan item-item PO yang masih
                        <span class="fw-semibold">draft / belum diterima</span>.
                    </div>
                @endif
            </div>

            <div>
                @if ($hasOrder)
                    <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
                        class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                @else
                    <a href="{{ route('purchasing.purchase_orders.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Ke Daftar PO
                    </a>
                @endif
            </div>
        </div>

        {{-- ALERT VALIDATION (SERVER SIDE) --}}
        @if ($errors->any())
            <div class="alert alert-danger small">
                <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- FILTER SUPPLIER (GET) - hanya kalau tidak dari 1 PO --}}
        @unless ($hasOrder)
            <form method="GET" action="{{ route('purchasing.purchase_receipts.create') }}" class="card mb-3">
                <div class="card-body small">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-5">
                            <label for="supplier_filter" class="form-label small">Supplier</label>
                            <select name="supplier_id" id="supplier_filter" class="form-select form-select-sm">
                                <option value="">- Pilih Supplier -</option>
                                @foreach ($suppliers as $sup)
                                    <option value="{{ $sup->id }}" @selected($selectedSupplierId == $sup->id)>
                                        {{ $sup->code }} — {{ $sup->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label small d-none d-md-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                Tampilkan Item
                            </button>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small d-none d-md-block">&nbsp;</label>
                            <a href="{{ route('purchasing.purchase_receipts.create') }}"
                                class="btn btn-outline-secondary btn-sm w-100">
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        @endunless

        {{-- FORM GRN (POST) --}}
        <form method="post" action="{{ route('purchasing.purchase_receipts.store') }}">
            @csrf

            <input type="hidden" name="purchase_order_id" value="{{ $hasOrder ? $order->id : '' }}">
            <input type="hidden" name="supplier_id" value="{{ $hasOrder ? $order->supplier_id : $selectedSupplierId }}">

            {{-- HEADER FORM --}}
            <div class="card mb-3">
                <div class="card-body small">
                    <div class="row g-3 align-items-end">
                        {{-- TANGGAL --}}
                        <div class="col-12 col-md-3">
                            <label for="date" class="form-label small">Tanggal</label>
                            <input type="hidden" name="date" value="{{ $defaultDate }}">
                            <input type="date" id="date" class="form-control form-control-sm"
                                value="{{ $defaultDate }}" readonly>
                        </div>

                        {{-- SUPPLIER (INFO SAJA) --}}
                        <div class="col-12 col-md-4">
                            <label class="form-label small">Supplier</label>
                            @if ($hasOrder)
                                <input type="text" class="form-control form-control-sm"
                                    value="{{ optional($order->supplier)->code ?? '-' }} — {{ optional($order->supplier)->name ?? '-' }}"
                                    readonly>
                            @elseif ($selectedSupplier)
                                <input type="text" class="form-control form-control-sm"
                                    value="{{ $selectedSupplier->code }} — {{ $selectedSupplier->name }}" readonly>
                            @else
                                <input type="text" class="form-control form-control-sm"
                                    value="Belum ada supplier terpilih" readonly>
                            @endif
                        </div>

                        {{-- GUDANG TUJUAN --}}
                        <div class="col-12 col-md-3">
                            <label class="form-label small">Gudang Tujuan</label>
                            <select name="warehouse_id" class="form-select form-select-sm" required>
                                <option value="">- Pilih Gudang -</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" @selected(old('warehouse_id') == $wh->id)>
                                        {{ $wh->code }} — {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- TOTAL QTY NET (DITERIMA - REJECT) --}}
                        <div class="col-12 col-md-2">
                            <label class="form-label small">Total Qty (Net)</label>
                            <div class="form-control form-control-sm mono bg-light-subtle">
                                <span id="totalReceivedDisplay">0,00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DETAIL BARANG --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold small text-uppercase">
                        Detail Barang Diterima
                    </span>
                    <span class="small text-muted">
                        Centang item yang mau diterima. Saat dicentang, Qty Diterima bisa otomatis = Qty PO.
                    </span>
                </div>

                <div class="card-body p-0">
                    <div class="table-wrap">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 32px;" class="text-center">
                                        <input type="checkbox" id="checkAll">
                                    </th>
                                    <th style="width: 40px;">#</th>
                                    <th>Item</th>
                                    <th class="text-end">Qty PO</th>
                                    <th class="text-end">Qty Diterima</th>
                                    <th class="text-end">Qty Reject</th>
                                    <th class="text-end col-price">Harga/Unit</th>
                                    <th class="text-center">Unit</th>
                                </tr>
                            </thead>
                            <tbody id="grnLinesBody">
                                @forelse ($detailLines as $idx => $line)
                                    @php
                                        $poLine = $line;
                                        $po = $hasOrder ? $order : $line->order ?? null;

                                        $oldQtyReceived = old('qty_received.' . $idx, '');
                                        $oldQtyReject = old('qty_reject.' . $idx, '');
                                        $oldSelected = old('selected.' . $idx);
                                    @endphp
                                    <tr data-line-index="{{ $idx }}" data-qty-po="{{ $poLine->qty }}">
                                        {{-- CHECKBOX --}}
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input line-check"
                                                name="selected[{{ $idx }}]" @checked(!is_null($oldSelected))>
                                        </td>

                                        {{-- INDEX --}}
                                        <td class="mono">
                                            {{ $idx + 1 }}
                                        </td>

                                        {{-- ITEM --}}
                                        <td>
                                            <div class="item-main mono">
                                                {{ optional($poLine->item)->code ?? '-' }}
                                            </div>
                                            <div class="item-sub">
                                                {{ optional($poLine->item)->name ?? '-' }}
                                                @if (!$hasOrder && $po)
                                                    <span class="badge-po ms-1">
                                                        {{ $po->code ?? 'PO?' }}
                                                    </span>
                                                @endif
                                            </div>

                                            <input type="hidden" name="po_line_id[]" value="{{ $poLine->id }}">
                                            <input type="hidden" name="item_id[]" value="{{ $poLine->item_id }}">
                                            <input type="hidden" name="unit_price[]" value="{{ $poLine->unit_price }}">
                                            <input type="hidden" name="unit[]"
                                                value="{{ optional($poLine->item)->unit ?? '' }}">
                                        </td>

                                        {{-- QTY PO --}}
                                        <td class="text-end mono">
                                            {{ number_format($poLine->qty, 2, ',', '.') }}
                                        </td>

                                        {{-- QTY DITERIMA --}}
                                        <td class="text-end">
                                            <input type="number" step="0.01" min="0" name="qty_received[]"
                                                class="form-control form-control-sm text-end mono qty-received-input"
                                                value="{{ $oldQtyReceived }}" placeholder="0,00">
                                            <div class="invalid-feedback small">
                                                Qty Diterima tidak boleh &lt; 0, &gt; Qty PO, atau membuat total &gt; Qty
                                                PO.
                                            </div>
                                        </td>

                                        {{-- QTY REJECT --}}
                                        <td class="text-end">
                                            <input type="number" step="0.01" min="0" name="qty_reject[]"
                                                class="form-control form-control-sm text-end mono qty-reject-input"
                                                value="{{ $oldQtyReject }}" placeholder="0,00">
                                            <div class="invalid-feedback small">
                                                Qty Reject tidak boleh &lt; 0, &gt; Qty PO, atau membuat total &gt; Qty PO.
                                            </div>
                                        </td>

                                        {{-- HARGA / UNIT: TANPA 2 DESIMAL --}}
                                        <td class="text-end mono col-price">
                                            {{ number_format($poLine->unit_price, 0, ',', '.') }}
                                        </td>

                                        {{-- UNIT --}}
                                        <td class="mono text-center">
                                            {{ optional($poLine->item)->unit ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            @if (!$hasOrder && !$selectedSupplierId)
                                                Pilih supplier di atas lalu klik
                                                <span class="fw-semibold">Tampilkan Item</span>.
                                            @elseif (!$hasOrder && $selectedSupplierId)
                                                Tidak ada PO status draft / belum diterima untuk supplier ini.
                                            @else
                                                Tidak ada detail item pada PO ini.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Sistem dapat mengabaikan baris yang tidak dicentang, atau yang Qty Diterima &amp; Qty Reject = 0.
                    </div>
                    <div class="d-flex gap-2">
                        @if ($hasOrder)
                            <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
                                class="btn btn-outline-secondary btn-sm">
                                Batal
                            </a>
                        @else
                            <a href="{{ route('purchasing.purchase_receipts.index') }}"
                                class="btn btn-outline-secondary btn-sm">
                                Batal
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary btn-sm">
                            Simpan Penerimaan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            function parseNumber(val) {
                if (val === null || val === undefined) return 0;
                if (typeof val === 'string') {
                    val = val.replace(',', '.');
                }
                const n = parseFloat(val);
                return isNaN(n) ? 0 : n;
            }

            function formatIdNumber(num) {
                try {
                    return num.toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                } catch (e) {
                    return num.toFixed(2);
                }
            }

            function setInvalid(input) {
                if (!input) return;
                input.classList.add('is-invalid');
            }

            function clearInvalid(input) {
                if (!input) return;
                input.classList.remove('is-invalid');
            }

            function validateRow(row) {
                const qtyPo = parseNumber(row.getAttribute('data-qty-po'));
                const inputRec = row.querySelector('.qty-received-input');
                const inputRej = row.querySelector('.qty-reject-input');

                if (!inputRec || !inputRej) return true;

                let rec = parseNumber(inputRec.value);
                let rej = parseNumber(inputRej.value);

                if (!inputRec.value) rec = 0;
                if (!inputRej.value) rej = 0;

                let ok = true;

                clearInvalid(inputRec);
                clearInvalid(inputRej);

                if (rec < 0 || rec > qtyPo) {
                    setInvalid(inputRec);
                    ok = false;
                }

                if (rej < 0 || rej > qtyPo) {
                    setInvalid(inputRej);
                    ok = false;
                }

                if (rec + rej > qtyPo) {
                    setInvalid(inputRec);
                    setInvalid(inputRej);
                    ok = false;
                }

                return ok;
            }

            // TOTAL = Σ (qty_received - qty_reject) baris yang dicentang & valid
            function recalcTotalReceived() {
                const rows = document.querySelectorAll('#grnLinesBody tr[data-line-index]');
                let total = 0;

                rows.forEach(function(row) {
                    const checkbox = row.querySelector('.line-check');
                    const inputRec = row.querySelector('.qty-received-input');
                    const inputRej = row.querySelector('.qty-reject-input');
                    if (!checkbox || !inputRec || !inputRej) return;

                    if (!checkbox.checked) return;
                    if (!validateRow(row)) return;

                    const rec = parseNumber(inputRec.value || 0);
                    const rej = parseNumber(inputRej.value || 0);
                    const net = rec - rej;
                    if (net > 0) {
                        total += net;
                    }
                });

                const span = document.getElementById('totalReceivedDisplay');
                if (span) {
                    span.textContent = formatIdNumber(total);
                }
            }

            function handleLineCheckboxChange(checkbox) {
                const row = checkbox.closest('tr');
                if (!row) return;

                const qtyPo = parseNumber(row.getAttribute('data-qty-po'));
                const inputRec = row.querySelector('.qty-received-input');
                const inputRej = row.querySelector('.qty-reject-input');

                if (checkbox.checked) {
                    // kalau dua-duanya kosong → default Rec = Qty PO
                    const recEmpty = !inputRec.value;
                    const rejEmpty = !inputRej.value;

                    if (recEmpty && rejEmpty && qtyPo > 0) {
                        inputRec.value = qtyPo.toFixed(2);
                        inputRej.value = '0.00';
                    }
                } else {
                    // uncheck → kosongkan dan clear error
                    if (inputRec) {
                        inputRec.value = '';
                        clearInvalid(inputRec);
                    }
                    if (inputRej) {
                        inputRej.value = '';
                        clearInvalid(inputRej);
                    }
                }

                validateRow(row);
                recalcTotalReceived();
            }

            function ensureRowChecked(row) {
                const checkbox = row.querySelector('.line-check');
                if (!checkbox) return;

                if (!checkbox.checked) {
                    checkbox.checked = true;
                    handleLineCheckboxChange(checkbox);
                }
            }

            function onQtyReceivedInput(input) {
                const row = input.closest('tr');
                if (!row) return;

                ensureRowChecked(row);

                const qtyPo = parseNumber(row.getAttribute('data-qty-po'));
                const inputRej = row.querySelector('.qty-reject-input');

                let rec = parseNumber(input.value);
                if (!input.value) rec = 0;

                if (rec >= 0 && rec <= qtyPo) {
                    const rej = qtyPo - rec;
                    if (inputRej) {
                        inputRej.value = rej.toFixed(2);
                    }
                }

                validateRow(row);
                recalcTotalReceived();
            }

            function onQtyRejectInput(input) {
                const row = input.closest('tr');
                if (!row) return;

                ensureRowChecked(row);

                const qtyPo = parseNumber(row.getAttribute('data-qty-po'));
                const inputRec = row.querySelector('.qty-received-input');

                let rej = parseNumber(input.value);
                if (!input.value) rej = 0;

                if (rej >= 0 && rej <= qtyPo) {
                    const rec = qtyPo - rej;
                    if (inputRec) {
                        inputRec.value = rec.toFixed(2);
                    }
                }

                validateRow(row);
                recalcTotalReceived();
            }

            function toggleRowCheckbox(row) {
                const cb = row.querySelector('.line-check');
                if (!cb) return;
                cb.checked = !cb.checked;
                handleLineCheckboxChange(cb);
            }

            // ===== EVENT HANDLERS =====

            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('qty-received-input')) {
                    onQtyReceivedInput(e.target);
                }
                if (e.target.classList.contains('qty-reject-input')) {
                    onQtyRejectInput(e.target);
                }
            });

            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('line-check')) {
                    handleLineCheckboxChange(e.target);
                }

                if (e.target.id === 'checkAll') {
                    const all = document.querySelectorAll('.line-check');
                    all.forEach(function(cb) {
                        cb.checked = e.target.checked;
                        handleLineCheckboxChange(cb);
                    });
                }
            });

            // Klik baris (kecuali input/checkbox) untuk toggle checklist
            document.addEventListener('click', function(e) {
                const row = e.target.closest('#grnLinesBody tr[data-line-index]');
                if (!row) return;

                // kalau klik di input / select / textarea / checkbox → jangan toggle
                if (
                    e.target.tagName === 'INPUT' ||
                    e.target.tagName === 'SELECT' ||
                    e.target.tagName === 'TEXTAREA' ||
                    e.target.closest('input') // termasuk checkbox
                ) {
                    return;
                }

                toggleRowCheckbox(row);
            });

            // Auto-select isi input ketika fokus (kalau ada angka)
            document.addEventListener('focus', function(e) {
                if (
                    e.target.classList.contains('qty-received-input') ||
                    e.target.classList.contains('qty-reject-input')
                ) {
                    if (e.target.value) {
                        setTimeout(function() {
                            e.target.select();
                        }, 0);
                    }
                }
            }, true);

            // Format ke 2 desimal saat blur: 2 => 2.00, 23 => 23.00
            document.addEventListener('blur', function(e) {
                if (
                    e.target.classList.contains('qty-received-input') ||
                    e.target.classList.contains('qty-reject-input')
                ) {
                    const v = e.target.value;
                    if (v === '' || v === null) return;
                    const n = parseNumber(v);
                    if (!isNaN(n)) {
                        e.target.value = n.toFixed(2);
                    }
                }
            }, true);

            document.addEventListener('DOMContentLoaded', function() {
                const all = document.querySelectorAll('.line-check');
                all.forEach(function(cb) {
                    handleLineCheckboxChange(cb);
                });
                recalcTotalReceived();
            });
        })();
    </script>
@endpush
