{{-- resources/views/purchasing/purchase_receipts/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Purchasing • Penerimaan Barang (GRN)')

@push('head')
    {{-- HEADER & CARD STYLE SELARAS INDEX --}}
    <style>
        .grn-create-page .page-header-title {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .grn-create-page .page-header-subtitle {
            font-size: .82rem;
            color: var(--muted);
        }

        .grn-create-page .page-header-actions .btn-outline-secondary {
            border-radius: 999px;
            padding-inline: 1rem;
        }

        .grn-create-page .btn-primary {
            border-radius: 999px;
            padding-inline: 1rem;
            box-shadow: 0 8px 18px rgba(59, 130, 246, .30);
        }

        .grn-create-page .main-card {
            border-radius: 16px;
            background: color-mix(in srgb, var(--card) 94%, var(--bg) 6%);
            border: 1px solid var(--line);
        }

        .grn-create-page .filter-card {
            border-radius: 16px;
            background: color-mix(in srgb, var(--card) 94%, var(--bg) 6%);
            border: 1px solid var(--line);
        }

        .grn-create-page .filter-card .card-header {
            background: transparent;
            border-bottom-color: var(--line);
        }

        .grn-create-page .filter-card .form-label {
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .grn-create-page .quick-filter-panel {
            border-radius: 14px;
            border: 1px solid var(--line);
            background: color-mix(in srgb, var(--card) 96%, var(--bg) 4%);
            padding: .75rem;
        }

        .grn-create-page .quick-filter-title {
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .grn-create-page .quick-stat {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: .48rem .65rem;
            background: rgba(148, 163, 184, .07);
            min-width: 120px;
        }

        .grn-create-page .quick-stat .lbl {
            display: block;
            font-size: .7rem;
            color: var(--muted);
            line-height: 1.1;
        }

        .grn-create-page .quick-stat .val {
            display: block;
            font-size: .92rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .grn-create-page .row-hidden-by-filter {
            display: none !important;
        }

        .grn-create-page .filter-empty-state {
            border-top: 1px solid var(--line);
            color: var(--muted);
            padding: 1rem;
            text-align: center;
            font-size: .88rem;
        }

        @media (max-width: 767.98px) {
            .grn-create-page .page-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: .35rem;
            }

            .grn-create-page .page-header-actions {
                width: 100%;
                display: flex;
                justify-content: center;
            }

            .grn-create-page .page-header-title {
                font-size: 1.1rem;
            }

            .grn-create-page .page-header-actions .btn-outline-secondary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    {{-- DETAIL TABLE + MOBILE STYLE --}}
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

        .table-wrap {
            overflow-x: auto;
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

        .badge-po {
            border-radius: 999px;
            padding: .15rem .65rem;
            font-size: .75rem;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, .12);
        }

        .badge-draft-grn {
            border-radius: 999px;
            padding: .1rem .5rem;
            font-size: .7rem;
            border: 1px solid rgba(248, 113, 113, .45);
            background: rgba(248, 113, 113, .08);
            color: #b91c1c;
        }

        .badge-type {
            border-radius: 999px;
            padding: .15rem .6rem;
            font-size: .72rem;
            border: 1px solid var(--line);
            background: rgba(34, 197, 94, .08);
            color: rgba(21, 128, 61, 1);
        }

        .badge-type.material {
            background: rgba(59, 130, 246, .08);
            color: rgba(30, 64, 175, 1);
        }

        .item-main {
            font-weight: 600;
        }

        .item-sub {
            font-size: .8rem;
            color: var(--muted);
        }

        .col-price {
            white-space: nowrap;
        }

        .line-muted {
            opacity: .6;
        }

        .info-pill {
            border: 1px solid var(--line);
            background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
            border-radius: 999px;
            padding: .35rem .7rem;
            font-size: .82rem;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .card-header,
            .card-body,
            .card-footer {
                padding-inline: .75rem !important;
            }

            .table-section {
                padding-top: .5rem !important;
            }

            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                border: 1px solid var(--line);
                border-radius: 10px;
                margin-bottom: .5rem;
                padding: .35rem .6rem .45rem;
                background: rgba(15, 23, 42, 0.02);
            }

            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border: 0;
                padding-block: .2rem;
            }

            .table tbody td[data-label]::before {
                content: attr(data-label);
                font-size: .75rem;
                color: var(--muted);
                margin-right: .75rem;
                text-align: left;
            }

            .table tbody td:nth-child(2) {
                display: none;
            }

            .item-main {
                font-size: .9rem;
            }

            .item-sub {
                font-size: .75rem;
            }

            .col-price {
                display: none;
            }

            .supplier-helper {
                text-align: center;
            }

            .grn-create-page .quick-stat {
                min-width: 0;
                flex: 1 1 0;
            }
        }
    </style>
@endpush

@section('content')
    @php
        /** @var \App\Models\PurchaseOrder|null $order */
        $u = auth()->user();
        $canSeeMoney = $u?->isOwner() ?? false;   // hanya owner
        $hasOrder = isset($order) && $order?->id;

        $defaultDate = old('date', now()->toDateString());

        // Supplier aktif (GET)
        $selectedSupplierId = old(
            'supplier_id',
            $hasOrder ? $order->supplier_id : $selectedSupplierId ?? request('supplier_id'),
        );

        // ORDER TYPE (AUTO)
        $selectedOrderType = old(
            'order_type',
            $hasOrder ? $order->order_type ?? 'material' : ($selectedOrderType ?? request('order_type') ?: null),
        );
        $selectedOrderType = in_array($selectedOrderType, ['material', 'finished_good'], true)
            ? $selectedOrderType
            : null;

        $detailLines = $hasOrder ? $order->lines : $lines ?? collect();

        if (!$hasOrder && !$selectedSupplierId && $detailLines->isNotEmpty()) {
            $firstLine = $detailLines->first();
            $firstPo = $firstLine->purchaseOrder ?? ($firstLine->order ?? null);
            if ($firstPo) {
                $selectedSupplierId = $firstPo->supplier_id;
            }
        }

        $selectedSupplier = isset($suppliers) ? $suppliers->firstWhere('id', $selectedSupplierId) : null;

        // DEFAULT WAREHOUSE
        $defaultWhCode = 'RM';
        if ($hasOrder && ($order->order_type ?? null) === 'finished_good') {
            $defaultWhCode = 'WH-RTS';
        }
        if (!$hasOrder && $selectedOrderType === 'finished_good') {
            $defaultWhCode = 'WH-RTS';
        }

        $defaultWarehouse = $warehouses->firstWhere('code', $defaultWhCode);
        if (!$defaultWarehouse) {
            $defaultWhCode = 'RM';
            $defaultWarehouse = $warehouses->firstWhere('code', 'RM');
        }

        $selectedWarehouseId = old('warehouse_id', $defaultWarehouse?->id ?? '');

        // Auto derive order type kalau belum ada (mode list tanpa query)
        if (!$hasOrder && !$selectedOrderType) {
            $selectedOrderType = $defaultWhCode === 'WH-RTS' ? 'finished_good' : 'material';
        }

        $typeLabel = $selectedOrderType === 'finished_good' ? 'Barang Jadi (FG)' : 'Bahan Baku (Material)';
        $typeCss = $selectedOrderType === 'material' ? 'material' : '';
    @endphp

    <div class="container py-3 grn-create-page">
        <div class="page-wrap">
            {{-- HEADER --}}
            <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                <div class="flex-grow-1">
                    <h1 class="mb-1 page-header-title">Goods Receipts • GRN Baru</h1>

                    <div class="page-header-subtitle">
                        @if ($hasOrder)
                            Penerimaan barang untuk <span class="mono">{{ $order->code }}</span>
                            dari <span class="fw-semibold">{{ optional($order->supplier)->name ?? '-' }}</span>.
                            <span class="ms-2 badge-type {{ $typeCss }}">{{ $typeLabel }}</span>
                        @else
                            Penerimaan barang dari Purchase Order berstatus <span class="fw-semibold">approved</span>.
                            Pilih supplier bila ingin mengerucutkan daftar item.
                            <span class="ms-2 badge-type {{ $typeCss }}">{{ $typeLabel }}</span>
                        @endif
                    </div>

                    @unless ($hasOrder)
                        <div class="mt-2">
                            <span class="info-pill d-inline-flex align-items-center gap-2">
                                <i class="bi bi-info-circle"></i>
                                GRN <strong>tidak boleh campur</strong> item dari beberapa PO. Sistem akan otomatis mengunci ke
                                1 PO saat submit.
                            </span>
                        </div>
                    @endunless
                </div>

                <div class="page-header-actions d-flex gap-2 ms-auto">
                    @if ($hasOrder)
                        <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
                            class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke PO
                        </a>
                    @else
                        <a href="{{ route('purchasing.purchase_receipts.index') }}"
                            class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Ke Daftar GRN
                        </a>
                    @endif
                </div>
            </div>

            <hr class="mt-0 mb-3">

            {{-- ALERT VALIDATION --}}
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

            {{-- FILTER (GET) --}}
            @unless ($hasOrder)
                <div class="card filter-card mb-3">
                    <div class="card-body small">
                        <div class="quick-filter-panel">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                <div>
                                    <div class="quick-filter-title">Cari Barang PO</div>
                                    <div class="text-muted">Ketik kode/nama barang atau nomor PO. Hasil langsung tersaring.</div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="{{ route('purchasing.purchase_receipts.create') }}"
                                        class="btn btn-outline-secondary btn-sm">Reset</a>
                                    <a href="{{ route('purchasing.purchase_receipts.index') }}"
                                        class="btn btn-outline-secondary btn-sm">Daftar GRN</a>
                                </div>
                            </div>

                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-lg-5">
                                    <label for="grn-realtime-search" class="form-label mb-1">Cari</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text" id="grn-realtime-search" class="form-control"
                                            placeholder="Kode barang, nama barang, PO..." autocomplete="off">
                                    </div>
                                </div>

                                <div class="col-12 col-md-6 col-lg-4">
                                    <label for="grn-realtime-supplier" class="form-label mb-1">Supplier</label>
                                    <select id="grn-realtime-supplier" class="form-select form-select-sm">
                                        <option value="">Semua Supplier</option>
                                        @foreach ($suppliers as $sup)
                                            <option value="{{ $sup->id }}" @selected((string) $selectedSupplierId === (string) $sup->id)>
                                                {{ $sup->code }} — {{ $sup->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-6 col-lg-3">
                                    <label class="form-label mb-1">Jenis</label>
                                    <div class="form-control form-control-sm bg-light-subtle">
                                        <span class="badge-type {{ $typeCss }}">{{ $typeLabel }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <div class="quick-stat">
                                    <span class="lbl">Baris Tampil</span>
                                    <span class="val mono" id="visibleRowsCount">0</span>
                                </div>
                                <div class="quick-stat">
                                    <span class="lbl">Dipilih</span>
                                    <span class="val mono"><span id="selectedRowsCount">0</span> item</span>
                                </div>
                                <div class="quick-stat flex-grow-1">
                                    <span class="lbl">PO Dipilih</span>
                                    <span class="val mono" id="selectedPoText">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endunless

            {{-- FORM GRN --}}
            <form id="grnForm" method="post" action="{{ route('purchasing.purchase_receipts.store') }}">
                @csrf

                {{-- IMPORTANT: purchase_order_id akan diisi:
                     - mode order: langsung $order->id
                     - mode list: JS auto derive dari baris pertama yang dicentang + validasi tidak campur PO
                --}}
                <input type="hidden" id="purchase_order_id" name="purchase_order_id"
                    value="{{ $hasOrder ? $order->id : '' }}">
                <input type="hidden" name="order_type" value="{{ $selectedOrderType }}">
                <input type="hidden" name="supplier_id"
                    value="{{ $hasOrder ? $order->supplier_id : $selectedSupplierId }}">

                {{-- HEADER GRN --}}
                <div class="card main-card mb-3">
                    <div class="card-body small">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-4">
                                <label for="date" class="form-label small text-uppercase">Tanggal</label>
                                <input type="text" name="date" id="date"
                                    class="form-control form-control-sm gf-date-input @error('date') is-invalid @enderror"
                                    value="{{ $defaultDate }}" data-gf-date autocomplete="off">
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small text-uppercase">Gudang Tujuan</label>
                                <input type="hidden" name="warehouse_id" value="{{ $selectedWarehouseId }}">

                                <select class="form-select form-select-sm" disabled>
                                    @if ($defaultWarehouse)
                                        <option value="{{ $defaultWarehouse->id }}" selected>
                                            {{ $defaultWarehouse->code }} — {{ $defaultWarehouse->name }}
                                        </option>
                                    @else
                                        <option value="">Gudang default tidak ditemukan</option>
                                    @endif
                                </select>

                                <div class="form-text small text-muted">
                                    Default: <strong>{{ $defaultWhCode }}</strong>
                                    @if ($hasOrder)
                                        (karena PO {{ $typeLabel }})
                                    @else
                                        (otomatis)
                                    @endif
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small text-uppercase">Total Qty (Net)</label>
                                <div class="form-control form-control-sm mono bg-light-subtle">
                                    <span id="totalReceivedDisplay">0,00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DETAIL --}}
                <div class="card main-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                        <span class="fw-semibold small text-uppercase">Detail Barang Diterima</span>
                    </div>

                    <div class="card-body p-0 table-section">
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
                                        @if ($canSeeMoney)
                                            <th class="text-end col-price">Harga/Unit</th>
                                        @endif
                                        <th class="text-center">Unit</th>
                                    </tr>
                                </thead>

                                <tbody id="grnLinesBody">
                                    @forelse ($detailLines as $idx => $line)
                                        @php
                                            $poLine = $line;
                                            $po = $hasOrder ? $order : $line->order ?? ($line->purchaseOrder ?? null);

                                            $oldQtyReceived = old('qty_received.' . $idx, '');
                                            $oldQtyReject = old('qty_reject.' . $idx, '');
                                            $oldSelected = old('selected.' . $idx);

                                            $hasDraftGrn = (bool) ($poLine->has_draft_grn ?? false);
                                            $poId = $po?->id;
                                            $poCode = $po?->code;
                                            $supplier = $po?->supplier ?? null;
                                            $supplierId = $supplier?->id ?? '';
                                            $supplierLabel = trim(($supplier?->code ? $supplier->code . ' ' : '') . ($supplier?->name ?? ''));
                                            $itemSearchText = trim(
                                                ($poLine->item?->code ?? '') . ' ' .
                                                ($poLine->item?->name ?? '') . ' ' .
                                                ($poCode ?? '') . ' ' .
                                                $supplierLabel
                                            );
                                        @endphp

                                        <tr data-line-index="{{ $idx }}" data-qty-po="{{ $poLine->qty }}"
                                            data-po-id="{{ $poId }}" data-po-code="{{ $poCode }}"
                                            data-supplier-id="{{ $supplierId }}"
                                            data-search="{{ e(mb_strtolower($itemSearchText)) }}"
                                            class="{{ $hasDraftGrn ? 'line-muted' : '' }}">
                                            <td class="text-center" data-label="Pilih">
                                                @if ($hasDraftGrn)
                                                    <i class="bi bi-exclamation-circle text-danger small"
                                                        title="Sudah pernah dibuat GRN (draft)"></i>
                                                @else
                                                    <input type="checkbox" class="form-check-input line-check"
                                                        name="selected[{{ $idx }}]" @checked(!is_null($oldSelected))>
                                                @endif
                                            </td>

                                            <td class="mono" data-label="#">{{ $idx + 1 }}</td>

                                            <td data-label="Item">
                                                <div class="item-main mono">{{ optional($poLine->item)->code ?? '-' }}
                                                </div>
                                                <div class="item-sub">
                                                    {{ optional($poLine->item)->name ?? '-' }}
                                                    @if (!$hasOrder && $po)
                                                        <span class="badge-po ms-1">{{ $po->code ?? 'PO?' }}</span>
                                                    @endif
                                                    @if ($hasDraftGrn)
                                                        <span class="badge-draft-grn ms-1">Sudah pernah dibuat GRN
                                                            (draft)
                                                        </span>
                                                    @endif
                                                </div>

                                                <input type="hidden" name="po_line_id[]" value="{{ $poLine->id }}">
                                                <input type="hidden" name="item_id[]" value="{{ $poLine->item_id }}">
                                                @if ($canSeeMoney)
                                                    <input type="hidden" name="unit_price[]"
                                                        value="{{ $poLine->unit_price }}">
                                                @endif
                                                <input type="hidden" name="unit[]"
                                                    value="{{ optional($poLine->item)->unit ?? '' }}">
                                            </td>

                                            <td class="text-end mono" data-label="Qty PO">
                                                {{ number_format($poLine->qty, 2, ',', '.') }}
                                            </td>

                                            <td class="text-end" data-label="Qty Diterima">
                                                <input type="text" inputmode="decimal"
                                                    name="qty_received[]"
                                                    class="form-control form-control-sm text-end mono qty-received-input"
                                                    value="{{ $oldQtyReceived }}" placeholder="0,00"
                                                    autocomplete="off"
                                                    @if ($hasDraftGrn) disabled @endif>
                                                <div class="invalid-feedback small">
                                                    Qty Diterima tidak boleh &lt; 0, &gt; Qty PO, atau membuat total &gt;
                                                    Qty PO.
                                                </div>
                                            </td>

                                            <td class="text-end" data-label="Qty Reject">
                                                <input type="text" inputmode="decimal" name="qty_reject[]"
                                                    class="form-control form-control-sm text-end mono qty-reject-input"
                                                    value="{{ $oldQtyReject }}" placeholder="0,00"
                                                    autocomplete="off"
                                                    @if ($hasDraftGrn) disabled @endif>
                                                <div class="invalid-feedback small">
                                                    Qty Reject tidak boleh &lt; 0, &gt; Qty PO, atau membuat total &gt; Qty
                                                    PO.
                                                </div>
                                            </td>

                                            @if ($canSeeMoney)
                                                <td class="text-end mono col-price" data-label="Harga/Unit">
                                                    {{ number_format($poLine->unit_price, 0, ',', '.') }}
                                                </td>
                                            @endif

                                            <td class="mono text-center" data-label="Unit">
                                                {{ optional($poLine->item)->unit ?? '-' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $canSeeMoney ? 8 : 7 }}" class="text-center text-muted py-4">
                                                @if (!$hasOrder && !$selectedSupplierId)
                                                    Tidak ada item dari Purchase Order berstatus approved.
                                                @elseif (!$hasOrder && $selectedSupplierId)
                                                    Tidak ada item PO berstatus approved untuk supplier ini.
                                                @else
                                                    Tidak ada detail item pada PO ini.
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div id="filter-empty-state" class="filter-empty-state d-none">
                            Tidak ada barang yang cocok dengan filter ini.
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end align-items-center flex-wrap gap-2">
                        <div class="d-flex gap-2">
                            @if ($hasOrder)
                                <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
                                    class="btn btn-outline-secondary btn-sm">Batal</a>
                            @else
                                <a href="{{ route('purchasing.purchase_receipts.index') }}"
                                    class="btn btn-outline-secondary btn-sm">Batal</a>
                            @endif

                            <button type="button" id="btnSubmitGrn" class="btn btn-primary btn-sm">
                                Simpan Penerimaan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- MODAL KONFIRMASI --}}
        <div class="modal fade" id="confirmGrnModal" tabindex="-1" aria-labelledby="confirmGrnModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title" id="confirmGrnModalLabel">Konfirmasi Penerimaan Barang</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <p class="small text-muted mb-2">Mohon cek kembali detail barang yang akan disimpan sebagai
                            penerimaan (GRN).</p>
                        <div id="confirmGrnSummary" class="small"></div>
                    </div>

                    <div class="modal-footer d-flex justify-content-between align-items-center">
                        <div class="small text-muted">Jika sudah sesuai, klik <span class="fw-semibold">Ya, simpan</span>.
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                data-bs-dismiss="modal">Kembali</button>
                            <button type="button" id="btnConfirmSubmit" class="btn btn-primary btn-sm">Ya,
                                simpan</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const HAS_ORDER = @json($hasOrder);

            function parseNumber(val) {
                if (val === null || val === undefined) return 0;
                if (typeof val !== 'string') return isNaN(val) ? 0 : Number(val);
                val = val.trim().replace(/\s/g, '');
                if (!val) return 0;
                // Format Indo: titik = ribuan, koma = desimal  →  "1.234,67" atau "25,67"
                if (val.includes(',')) {
                    val = val.replace(/\./g, '').replace(',', '.');
                } else if (/^\d{1,3}(\.\d{3}){2,}$/.test(val)) {
                    // multi-grup titik tanpa koma: "1.234.567" → ribuan
                    val = val.replace(/\./g, '');
                }
                // single ".XXX" tanpa koma diperlakukan sebagai desimal ("5.5", "25.67")
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
                    return (num || 0).toFixed(2);
                }
            }

            function normalizeText(value) {
                return String(value || '').trim().toLowerCase();
            }

            function rows() {
                return Array.from(document.querySelectorAll('#grnLinesBody tr[data-line-index]'));
            }

            function isRowVisible(row) {
                return !row.classList.contains('row-hidden-by-filter');
            }

            function updateVisibleNumbers() {
                let visibleNo = 1;
                rows().forEach(function(row) {
                    const noCell = row.querySelector('td[data-label="#"]');
                    if (!noCell) return;
                    if (isRowVisible(row)) {
                        noCell.textContent = visibleNo++;
                    }
                });

                const visibleRowsCount = document.getElementById('visibleRowsCount');
                if (visibleRowsCount) visibleRowsCount.textContent = String(visibleNo - 1);

                const emptyState = document.getElementById('filter-empty-state');
                if (emptyState) emptyState.classList.toggle('d-none', rows().length === 0 || visibleNo > 1);
            }

            function updateSelectionSummary() {
                const selectedRows = rows().filter(function(row) {
                    const cb = row.querySelector('.line-check');
                    return cb && cb.checked;
                });

                const selectedRowsCount = document.getElementById('selectedRowsCount');
                if (selectedRowsCount) selectedRowsCount.textContent = String(selectedRows.length);

                const poCodes = Array.from(new Set(selectedRows.map(function(row) {
                    return row.getAttribute('data-po-code') || '';
                }).filter(Boolean)));

                const selectedPoText = document.getElementById('selectedPoText');
                if (selectedPoText) {
                    if (poCodes.length === 0) {
                        selectedPoText.textContent = '-';
                    } else if (poCodes.length === 1) {
                        selectedPoText.textContent = poCodes[0];
                    } else {
                        selectedPoText.textContent = poCodes.length + ' PO';
                    }
                }
            }

            function applyRealtimeFilter() {
                const keyword = normalizeText(document.getElementById('grn-realtime-search')?.value);
                const supplierId = String(document.getElementById('grn-realtime-supplier')?.value || '');

                rows().forEach(function(row) {
                    const haystack = row.getAttribute('data-search') || '';
                    const rowSupplierId = String(row.getAttribute('data-supplier-id') || '');
                    const matchKeyword = !keyword || haystack.includes(keyword);
                    const matchSupplier = !supplierId || rowSupplierId === supplierId;

                    row.classList.toggle('row-hidden-by-filter', !(matchKeyword && matchSupplier));
                });

                const checkAll = document.getElementById('checkAll');
                if (checkAll) checkAll.checked = false;

                updateVisibleNumbers();
                updateSelectionSummary();
            }

            function setInvalid(input) {
                if (input) input.classList.add('is-invalid');
            }

            function clearInvalid(input) {
                if (input) input.classList.remove('is-invalid');
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
                    if (net > 0) total += net;
                });

                const span = document.getElementById('totalReceivedDisplay');
                if (span) span.textContent = formatIdNumber(total);
                updateSelectionSummary();
            }

            function handleLineCheckboxChange(checkbox) {
                const row = checkbox.closest('tr');
                if (!row) return;

                const qtyPo = parseNumber(row.getAttribute('data-qty-po'));
                const inputRec = row.querySelector('.qty-received-input');
                const inputRej = row.querySelector('.qty-reject-input');

                if (checkbox.checked) {
                    const recEmpty = !inputRec.value;
                    const rejEmpty = !inputRej.value;
                    if (recEmpty && rejEmpty && qtyPo > 0) {
                        inputRec.value = qtyPo.toFixed(2);
                        inputRej.value = '0.00';
                    }
                } else {
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
                    if (inputRej) inputRej.value = rej.toFixed(2);
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
                    if (inputRec) inputRec.value = rec.toFixed(2);
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

            function collectSelectedLines() {
                const rows = document.querySelectorAll('#grnLinesBody tr[data-line-index]');
                const result = [];
                let allValid = true;

                rows.forEach(function(row) {
                    const checkbox = row.querySelector('.line-check');
                    const inputRec = row.querySelector('.qty-received-input');
                    const inputRej = row.querySelector('.qty-reject-input');
                    const unitCell = row.querySelector('td[data-label="Unit"]');

                    if (!checkbox || !inputRec || !inputRej) return;
                    if (!checkbox.checked) return;

                    if (!validateRow(row)) {
                        allValid = false;
                        return;
                    }

                    const qtyPo = parseNumber(row.getAttribute('data-qty-po'));
                    const rec = parseNumber(inputRec.value || 0);
                    const rej = parseNumber(inputRej.value || 0);
                    if (rec <= 0 && rej <= 0) return;

                    const itemMain = row.querySelector('.item-main')?.textContent?.trim() ?? '-';
                    const itemSub = row.querySelector('.item-sub')?.childNodes?.[0]?.textContent?.trim() ?? '';
                    const unit = unitCell ? unitCell.textContent.trim() : '';

                    result.push({
                        item: itemMain,
                        name: itemSub,
                        qtyPo,
                        qtyRec: rec,
                        qtyRej: rej,
                        unit
                    });
                });

                return {
                    lines: result,
                    allValid
                };
            }

            function buildConfirmationTable(lines) {
                if (!lines.length) {
                    return '<div class="alert alert-warning small mb-0">Tidak ada item yang dicentang dengan Qty Diterima / Reject.</div>';
                }

                let html = '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr>' +
                    '<th style="width:40px;">#</th><th>Item</th>' +
                    '<th class="text-end">Qty PO</th><th class="text-end">Diterima</th><th class="text-end">Reject</th>' +
                    '<th class="text-center">Unit</th></tr></thead><tbody>';

                lines.forEach(function(line, idx) {
                    html += '<tr><td class="mono">' + (idx + 1) + '</td>' +
                        '<td><div class="mono">' + line.item + '</div><div class="text-muted small">' + (line
                            .name || '') + '</div></td>' +
                        '<td class="text-end mono">' + formatIdNumber(line.qtyPo) + '</td>' +
                        '<td class="text-end mono">' + formatIdNumber(line.qtyRec) + '</td>' +
                        '<td class="text-end mono">' + formatIdNumber(line.qtyRej) + '</td>' +
                        '<td class="text-center mono">' + (line.unit || '-') + '</td></tr>';
                });

                html += '</tbody></table></div>';
                return html;
            }

            // ==========================================================
            // IMPORTANT: derive purchase_order_id from selected rows (list mode)
            // - prevent mixing multiple PO in one GRN
            // ==========================================================
            function deriveAndLockPurchaseOrderIdOrThrow() {
                if (HAS_ORDER) return true;

                const poIdInput = document.getElementById('purchase_order_id');
                const rows = document.querySelectorAll('#grnLinesBody tr[data-line-index]');
                const selectedPoIds = [];

                rows.forEach(row => {
                    const cb = row.querySelector('.line-check');
                    if (!cb || !cb.checked) return;
                    const poId = row.getAttribute('data-po-id');
                    if (poId) selectedPoIds.push(String(poId));
                });

                const uniq = Array.from(new Set(selectedPoIds)).filter(Boolean);

                if (uniq.length === 0) {
                    throw new Error('Belum ada item yang dicentang.');
                }

                if (uniq.length > 1) {
                    // cari kode PO untuk membantu user
                    const codes = new Set();
                    rows.forEach(row => {
                        const cb = row.querySelector('.line-check');
                        if (!cb || !cb.checked) return;
                        const code = row.getAttribute('data-po-code');
                        if (code) codes.add(code);
                    });

                    const hint = codes.size ? `\nPO terpilih: ${Array.from(codes).join(', ')}` : '';
                    throw new Error('GRN tidak boleh campur item dari beberapa PO. Pilih item dari 1 PO saja.' + hint);
                }

                poIdInput.value = uniq[0];
                return true;
            }

            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('qty-received-input')) onQtyReceivedInput(e.target);
                if (e.target.classList.contains('qty-reject-input')) onQtyRejectInput(e.target);
            });

            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('line-check')) handleLineCheckboxChange(e.target);

                if (e.target.id === 'checkAll') {
                    const all = rows()
                        .filter(isRowVisible)
                        .map(row => row.querySelector('.line-check'))
                        .filter(Boolean);
                    all.forEach(function(cb) {
                        cb.checked = e.target.checked;
                        handleLineCheckboxChange(cb);
                    });
                }
            });

            document.addEventListener('click', function(e) {
                const row = e.target.closest('#grnLinesBody tr[data-line-index]');
                if (!row) return;

                if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName ===
                    'TEXTAREA' || e.target.closest('input')) {
                    return;
                }

                const checkbox = row.querySelector('.line-check');
                if (!checkbox || checkbox.disabled) return;

                toggleRowCheckbox(row);
            });

            document.addEventListener('focus', function(e) {
                if (e.target.classList.contains('qty-received-input') || e.target.classList.contains(
                        'qty-reject-input')) {
                    if (e.target.value) setTimeout(() => e.target.select(), 0);
                }
            }, true);

            document.addEventListener('blur', function(e) {
                if (e.target.classList.contains('qty-received-input') || e.target.classList.contains(
                        'qty-reject-input')) {
                    const v = e.target.value;
                    if (v === '' || v === null) return;
                    const n = parseNumber(v);
                    if (!isNaN(n)) e.target.value = n.toFixed(2);
                }
            }, true);

            document.addEventListener('DOMContentLoaded', function() {
                const btnSubmit = document.getElementById('btnSubmitGrn');
                const btnConfirmSubmit = document.getElementById('btnConfirmSubmit');
                const form = document.getElementById('grnForm');
                const summaryEl = document.getElementById('confirmGrnSummary');
                const modalEl = document.getElementById('confirmGrnModal');
                const realtimeSearch = document.getElementById('grn-realtime-search');
                const realtimeSupplier = document.getElementById('grn-realtime-supplier');

                if (realtimeSearch) realtimeSearch.addEventListener('input', applyRealtimeFilter);
                if (realtimeSupplier) realtimeSupplier.addEventListener('change', applyRealtimeFilter);

                if (btnSubmit && summaryEl && modalEl && form && btnConfirmSubmit) {
                    btnSubmit.addEventListener('click', function(e) {
                        e.preventDefault();

                        const {
                            lines,
                            allValid
                        } = collectSelectedLines();

                        if (!allValid) {
                            alert(
                                'Masih ada input Qty Diterima / Reject yang tidak valid. Mohon periksa kembali.'
                                );
                            return;
                        }

                        if (!lines.length) {
                            alert('Belum ada item yang dicentang dengan Qty Diterima / Reject.');
                            return;
                        }

                        // derive purchase_order_id + prevent mixing PO
                        try {
                            deriveAndLockPurchaseOrderIdOrThrow();
                        } catch (err) {
                            alert(err.message || 'Gagal menyiapkan GRN.');
                            return;
                        }

                        summaryEl.innerHTML = buildConfirmationTable(lines);

                        const modal = new bootstrap.Modal(modalEl);
                        modal.show();

                        btnConfirmSubmit.onclick = function() {
                            modal.hide();
                            form.submit();
                        };
                    });
                }

                document.querySelectorAll('.line-check').forEach(cb => handleLineCheckboxChange(cb));
                applyRealtimeFilter();
                recalcTotalReceived();
            });
        })();
    </script>
@endpush
