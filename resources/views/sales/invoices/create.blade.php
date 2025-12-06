@extends('layouts.app')

@php
    // Context judul
    if (!empty($sourceShipment)) {
        $pageTitle = 'Buat Invoice dari Shipment ' . $sourceShipment->shipment_no;
        $pageMeta = 'BUAT INVOICE DARI SHIPMENT';
    } else {
        $pageTitle = 'Buat Invoice Penjualan';
        $pageMeta = 'INVOICE PENJUALAN BARU';
    }

    // Default values (datang dari controller atau old())
    $defaultDate = old('date', $defaultDate ?? now()->toDateString());
    $defaultWarehouseId = old('warehouse_id', $defaultWarehouseId ?? null);
    $defaultStoreId = old('store_id', $defaultStoreId ?? null);

    // Cari WH-RTS (kalau ada) dari list gudang yang dikirim controller
    $whRts = null;
    if (isset($warehouses)) {
        if ($warehouses instanceof \Illuminate\Support\Collection) {
            $whRts = $warehouses->firstWhere('code', 'WH-RTS');
        } else {
            foreach ($warehouses as $wh) {
                if ($wh->code === 'WH-RTS') {
                    $whRts = $wh;
                    break;
                }
            }
        }
    }

    // Initial lines (prefilledLines dari controller atau 1 baris kosong)
    $initialLines = old('items', $prefilledLines ?? []);
    if (empty($initialLines)) {
        $initialLines = [
            [
                'item_id' => null,
                'qty' => 1,
                'unit_price' => null,
                'line_discount' => 0,
            ],
        ];
    }
@endphp

@section('title', $pageTitle)

@push('head')
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

        /* Biar feel invoice: angka rata kanan dan tabular */
        .num-cell {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        {{-- HEADER ATAS --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="meta-label mb-1">
                    {{ $pageMeta }}
                </div>
                <h1 class="h4 mb-1">
                    @if (!empty($sourceShipment))
                        Shipment {{ $sourceShipment->shipment_no }}
                    @else
                        Invoice Baru
                    @endif
                </h1>

                @if (!empty($sourceShipment))
                    {{-- Header info: Tanggal, Store, Status --}}
                    <div class="d-flex flex-wrap gap-2 small mt-2">
                        <div>
                            <span class="text-muted">Tanggal:</span>
                            <span class="fw-semibold">{{ id_date($sourceShipment->date) }}</span>
                        </div>
                        <div>
                            <span class="text-muted">Store:</span>
                            <span class="fw-semibold">{{ $sourceShipment->store?->name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-muted">Status Shipment:</span>
                            @php
                                $shipStatus = $sourceShipment->status ?? 'draft';
                            @endphp
                            @if ($shipStatus === 'posted')
                                <span
                                    class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                    Posted
                                </span>
                            @else
                                <span
                                    class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2 py-1">
                                    Draft
                                </span>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="small text-muted mt-1">
                        Isi data invoice penjualan, bisa mode <strong>unpriced</strong> (harga kosong dulu).
                    </div>
                @endif
            </div>

            <div class="text-end">
                <a href="{{ route('sales.invoices.index') }}" class="btn btn-outline-secondary btn-sm">
                    &larr; Daftar Invoice
                </a>
            </div>
        </div>

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
        <form action="{{ route('sales.invoices.store') }}" method="POST">
            @csrf
            @if (!empty($sourceShipment))
                <input type="hidden" name="source_shipment_id" value="{{ $sourceShipment->id }}">
            @endif


            {{-- INFO UTAMA (Tanggal, Gudang, Toko/Channel) --}}
            <div class="card card-main mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="meta-label mb-0">
                            Info Utama
                        </div>

                        <div class="summary-pill">
                            Status awal:
                            <strong class="ms-1">DRAFT / UNPRICED</strong>
                        </div>
                    </div>

                    <div class="row g-3">
                        {{-- TANGGAL --}}
                        <div class="col-md-3">
                            <label class="form-label small">Tanggal</label>
                            <input type="date" name="date" class="form-control form-control-sm"
                                value="{{ $defaultDate }}" required>
                        </div>

                        {{-- GUDANG (WH-RTS kalau ada, locked jika dari shipment) --}}
                        <div class="col-md-3">
                            <label class="form-label small">Gudang</label>

                            @php
                                // id WH-RTS kalau ada
                                $whRtsId = $whRts?->id;
                            @endphp

                            @if (!empty($sourceShipment))
                                @php
                                    // LOCK → selalu pakai WH-RTS kalau ketemu, fallback ke shipment->warehouse_id
                                    $effectiveWarehouseId = $whRtsId ?? ($sourceShipment->warehouse_id ?? null);

                                    $selectedWarehouse = null;
                                    if ($effectiveWarehouseId) {
                                        if ($warehouses instanceof \Illuminate\Support\Collection) {
                                            $selectedWarehouse = $warehouses->firstWhere('id', $effectiveWarehouseId);
                                        } else {
                                            foreach ($warehouses as $wh) {
                                                if ($wh->id == $effectiveWarehouseId) {
                                                    $selectedWarehouse = $wh;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                @endphp

                                <div class="form-control form-control-sm bg-body-tertiary" tabindex="-1"
                                    style="pointer-events: none; opacity: .9;">
                                    @if ($selectedWarehouse)
                                        {{ $selectedWarehouse->code }}
                                    @else
                                        -
                                    @endif
                                </div>

                                <input type="hidden" name="warehouse_id" value="{{ $effectiveWarehouseId }}">
                            @else
                                @php
                                    // Default pilihan = old()/default atau fallback ke WH-RTS
                                    $selectedWarehouseId = $defaultWarehouseId ?: $whRtsId;
                                @endphp
                                <select name="warehouse_id" class="form-select form-select-sm" required>
                                    <option value="">Pilih gudang...</option>
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}" @selected($selectedWarehouseId == $wh->id)>
                                            {{ $wh->code }} — {{ $wh->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @if ($whRts)
                                    <div class="form-text small text-muted">
                                        Default gudang = WH-RTS. Bisa diubah jika perlu.
                                    </div>
                                @endif
                            @endif
                        </div>

                        {{-- TOKO / CHANNEL (LOCKED JIKA DARI SHIPMENT) --}}
                        <div class="col-md-6">
                            <label class="form-label small">Toko / Channel</label>

                            @if (!empty($sourceShipment))
                                @php
                                    // LOCK: selalu ikut store dari shipment
                                    $effectiveStoreId = $sourceShipment->store_id ?? null;

                                    $selectedStore = null;
                                    if ($effectiveStoreId) {
                                        if ($stores instanceof \Illuminate\Support\Collection) {
                                            $selectedStore = $stores->firstWhere('id', $effectiveStoreId);
                                        } else {
                                            foreach ($stores as $st) {
                                                if ($st->id == $effectiveStoreId) {
                                                    $selectedStore = $st;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                @endphp

                                <div class="form-control form-control-sm bg-body-tertiary" tabindex="-1"
                                    style="pointer-events: none; opacity: .9;">
                                    @if ($selectedStore)
                                        {{ $selectedStore->code }} — {{ $selectedStore->name }}
                                    @elseif ($sourceShipment->store)
                                        {{ $sourceShipment->store->code }} — {{ $sourceShipment->store->name }}
                                    @else
                                        -
                                    @endif
                                </div>

                                {{-- Hidden store_id --}}
                                <input type="hidden" name="store_id" value="{{ $effectiveStoreId }}">
                            @else
                                <select name="store_id" class="form-select form-select-sm">
                                    <option value="">Tanpa store</option>
                                    @foreach ($stores as $s)
                                        <option value="{{ $s->id }}" @selected($defaultStoreId == $s->id)>
                                            {{ $s->code }} — {{ $s->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- DAFTAR ITEM --}}
            <div class="card card-main">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="meta-label mb-1">Daftar Item</div>
                            <div class="small text-muted">

                            </div>
                        </div>

                        @if (empty($sourceShipment))
                            <button type="button" class="btn btn-outline-primary btn-sm btn-chip" id="btnAddLine">
                                + Tambah Baris
                            </button>
                        @endif
                    </div>

                    <div class="lines-wrapper">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle table-lines mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th style="width: 260px;">Item</th>
                                        <th style="width: 100px;" class="num-cell">Qty</th>

                                        @if (!empty($sourceShipment))
                                            <th style="width: 140px;" class="num-cell">HPP</th>
                                            <th style="width: 140px;" class="num-cell">Subtotal</th>
                                        @else
                                            <th style="width: 140px;">Harga /pcs</th>
                                            <th style="width: 120px;">Diskon line</th>
                                            <th style="width: 140px;" class="num-cell">Subtotal</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody id="invoice-lines-body" data-next-index="{{ count($initialLines) }}">
                                    @foreach ($initialLines as $idx => $line)
                                        @php
                                            $itemIdOld = old("items.$idx.item_id", $line['item_id'] ?? null);
                                            $qtyOld = old("items.$idx.qty", $line['qty'] ?? 1);
                                            $priceOld = old("items.$idx.unit_price", $line['unit_price'] ?? null);
                                            $discOld = old("items.$idx.line_discount", $line['line_discount'] ?? 0);

                                            $itemObj =
                                                $items instanceof \Illuminate\Support\Collection
                                                    ? $items->firstWhere('id', $itemIdOld)
                                                    : null;

                                            $hppValue = $line['hpp'] ?? ($itemObj->hpp_unit ?? 0);
                                            // subtotal di mode shipment = qty * HPP (display only, non-reaktif)
                                            $subtotalInitial = $qtyOld * $hppValue;
                                        @endphp
                                        <tr>
                                            {{-- # / tombol remove --}}
                                            <td class="text-center align-middle">
                                                @if (empty($sourceShipment))
                                                    <button type="button" class="btn btn-outline-danger btn-sm px-2 py-0"
                                                        onclick="removeLineRow(this)">×</button>
                                                @else
                                                    {{ $loop->iteration }}
                                                @endif
                                            </td>

                                            {{-- ITEM --}}
                                            <td>
                                                <select name="items[{{ $idx }}][item_id]"
                                                    class="form-select form-select-sm item-select"
                                                    @if (!empty($sourceShipment)) disabled @endif required>
                                                    <option value="">Pilih item...</option>
                                                    @foreach ($items as $item)
                                                        <option value="{{ $item->id }}"
                                                            data-hpp="{{ $item->hpp_unit }}" @selected($itemIdOld == $item->id)>
                                                            {{ $item->code }} — {{ $item->name }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                @if (!empty($sourceShipment))
                                                    {{-- tetap kirim item_id ke backend --}}
                                                    <input type="hidden" name="items[{{ $idx }}][item_id]"
                                                        value="{{ $itemIdOld }}">
                                                @endif
                                            </td>

                                            {{-- QTY (desimal angka) --}}
                                            <td class="num-cell">
                                                <input type="number" step="0.01" min="0"
                                                    name="items[{{ $idx }}][qty]"
                                                    class="form-control form-control-sm qty-input text-end"
                                                    value="{{ $qtyOld }}"
                                                    @if (!empty($sourceShipment)) readonly @endif required>
                                            </td>

                                            @if (!empty($sourceShipment))
                                                {{-- HPP (format Rupiah tanpa koma) --}}
                                                <td class="num-cell">
                                                    <span class="hpp-display">
                                                        {{ number_format($hppValue, 0, ',', '.') }}
                                                    </span>
                                                    {{-- Hidden raw value jika backend butuh --}}
                                                    <input type="hidden" name="items[{{ $idx }}][unit_price]"
                                                        value="{{ $hppValue }}">
                                                    <input type="hidden"
                                                        name="items[{{ $idx }}][line_discount]"
                                                        value="{{ $discOld ?? 0 }}">
                                                </td>

                                                {{-- SUBTOTAL (Rupiah tanpa koma, tidak reaktif) --}}
                                                <td class="num-cell">
                                                    <span class="line-subtotal">
                                                        {{ number_format($subtotalInitial, 0, ',', '.') }}
                                                    </span>
                                                </td>
                                            @else
                                                {{-- MODE NORMAL: Harga / Diskon / Subtotal reaktif --}}
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
                                                <td class="num-cell">
                                                    <span class="line-subtotal">0</span>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            Subtotal & PPN akan dihitung ulang saat disimpan.<br>

                        </div>
                        <button type="submit" class="btn btn-primary btn-chip">
                            Simpan Invoice
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        const hasSourceShipment = {{ !empty($sourceShipment) ? 'true' : 'false' }};

        function removeLineRow(btn) {
            if (hasSourceShipment) return; // mode shipment: tidak bisa hapus baris

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
            if (hasSourceShipment) {
                // mode shipment: subtotal tidak reaktif di UI
                return;
            }

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
            if (hasSourceShipment) return; // mode shipment: tidak bisa tambah baris via UI

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
    <td class="num-cell">
        <input type="number" step="0.01" min="0" name="items[${nextIndex}][qty]" class="form-control form-control-sm qty-input text-end" value="1" required>
    </td>
    <td>
        <input type="number" step="0.01" min="0" name="items[${nextIndex}][unit_price]" class="form-control form-control-sm price-input" value="">
    </td>
    <td>
        <input type="number" step="0.01" min="0" name="items[${nextIndex}][line_discount]" class="form-control form-control-sm discount-input" value="0">
    </td>
    <td class="num-cell">
        <span class="line-subtotal">0</span>
    </td>
</tr>`;

            tbody.insertAdjacentHTML('beforeend', html);
            tbody.dataset.nextIndex = nextIndex + 1;
            recalcSubtotals();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const btnAdd = document.getElementById('btnAddLine');
            if (btnAdd && !hasSourceShipment) {
                btnAdd.addEventListener('click', function() {
                    addLineRow();
                });
            }

            const tbody = document.getElementById('invoice-lines-body');
            if (tbody && !hasSourceShipment) {
                tbody.addEventListener('input', function(e) {
                    if (e.target.matches('.qty-input, .price-input, .discount-input')) {
                        recalcSubtotals();
                    }
                });
            }

            // Untuk mode normal, hitung awal; untuk shipment, fungsi akan langsung return.
            recalcSubtotals();
        });
    </script>
@endpush
