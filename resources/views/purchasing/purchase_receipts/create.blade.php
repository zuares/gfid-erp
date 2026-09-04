{{-- resources/views/purchasing/purchase_receipts/create.blade.php --}}
@extends('layouts.app')
@section('title', 'GRN Baru')

@push('head')
<style>
    .shp-wrap { max-width: 1040px; margin-inline: auto; padding: .75rem .75rem 4rem; }
    .shp-topbar {
        position: sticky; top: 0; z-index: 300;
        display: flex; align-items: center; gap: .45rem; flex-wrap: wrap;
        padding: .65rem 1rem; background: var(--card, #fff);
        border-bottom: 1px solid rgba(148,163,184,.18);
    }
    .shp-topbar-code { font-weight: 900; font-size: .95rem; white-space: nowrap; color: #0f172a; }
    .shp-topbar-spacer { flex: 1; min-width: .5rem; }
    .shp-badge {
        border-radius: 7px; padding: .18rem .48rem; font-size: .68rem;
        background: transparent; color: #64748b;
        border: 1px solid rgba(148,163,184,.28); white-space: nowrap;
    }
    .shp-meta-bar {
        display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
        padding-bottom: .85rem; margin-bottom: 1rem; margin-top: .5rem;
        border-bottom: 1px solid rgba(148,163,184,.18);
    }
    .shp-meta-code { font-weight: 900; font-size: 1.05rem; color: #0f172a; letter-spacing: -.01em; }
    .shp-meta-store {
        display: inline-flex; align-items: center; padding: .18rem .6rem;
        border-radius: 999px; border: 1px solid rgba(148,163,184,.6);
        font-size: .75rem; font-weight: 700; color: #475569;
    }

    .card {
        background: var(--card, #fff);
        border: 1px solid rgba(148,163,184,.18);
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 6px rgba(15,23,42,.07);
    }

    .form-control,
    .form-control-sm {
        border-radius: 8px;
        border-color: rgba(148,163,184,.35);
        box-shadow: none !important;
    }
    .form-control:focus,
    .form-control-sm:focus {
        border-color: rgba(71,85,105,.75);
        box-shadow: none !important;
    }

    .mono {
        font-variant-numeric: tabular-nums;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
    }

    /* ── Tag pills (selaras PO show) ── */
    .tag {
        border-radius: 999px;
        padding: .15rem .65rem;
        font-size: .7rem;
        border: 1px solid var(--line);
        background: rgba(148,163,184,.12);
        white-space: nowrap;
    }
    .tag-material  { background:rgba(59,130,246,.08);  color:#1d4ed8; border-color:rgba(59,130,246,.45); }
    .tag-fg        { background:rgba(22,163,74,.10);   color:#15803d; border-color:rgba(22,163,74,.45); }
    .tag-partial   { background:rgba(234,179,8,.12);   color:#92400e; border-color:rgba(234,179,8,.45); }
    .tag-done      { background:rgba(22,163,74,.10);   color:#15803d; border-color:rgba(22,163,74,.35); }
    .tag-draft-grn { background:rgba(239,68,68,.08);   color:#b91c1c; border-color:rgba(239,68,68,.35); }
    .tag-po        { background:rgba(148,163,184,.1);  color:#475569; border-color:rgba(148,163,184,.4); }

    /* ── Button (Selaras Shipment) ── */
    .btn-shp-primary {
        background: #334155 !important;
        border: 1px solid #334155 !important;
        color: #fff !important;
        border-radius: 7px !important;
        letter-spacing: 0;
        text-transform: none;
        box-shadow: none !important;
        padding: .35rem .75rem !important;
        font-size: .85rem !important;
        font-weight: 600 !important;
    }
    .btn-shp-primary:hover {
        background: #1f2937 !important;
        border-color: #1f2937 !important;
    }
    .btn-shp-outline {
        border-radius: 7px !important;
        letter-spacing: 0;
        text-transform: none;
        box-shadow: none !important;
        padding: .35rem .75rem !important;
        font-size: .85rem !important;
        color: #475569 !important;
        background: transparent !important;
        border: 1px solid rgba(148,163,184,.35) !important;
        font-weight: 600 !important;
    }
    .btn-shp-outline:hover {
        background: rgba(148,163,184,.08) !important;
        color: #111827 !important;
    }

    /* ── Filter bar ── */
    .filter-bar {
        background: color-mix(in srgb, var(--card) 94%, var(--bg) 6%);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: .75rem 1rem;
    }
    .filter-bar .form-label {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--muted);
        margin-bottom: .25rem;
    }

    /* ── Counter pills ── */
    .stat-pill {
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: .35rem .75rem;
        background: rgba(148,163,184,.06);
        font-size: .8rem;
    }
    .stat-pill .lbl { color: var(--muted); font-size: .7rem; display:block; line-height:1.2; }
    .stat-pill .val { font-weight: 700; }

    /* ── Table ── */
    thead th {
        background: var(--card, #f9fafb);
        position: sticky; top: 0; z-index: 1;
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #6b7280;
        border-bottom: 1px solid rgba(148,163,184,.18);
        padding-block: .4rem;
        white-space: nowrap;
    }
    .th-sub {
        display: block;
        color: #2563eb;
        font-size: .62rem;
        font-weight: 700;
        letter-spacing: 0;
        text-transform: none;
    }
    .th-sub-reject { color: #b91c1c; }
    .receipt-rule {
        color: var(--muted);
        font-size: .76rem;
        line-height: 1.4;
    }
    .table-sm td { padding-block: .35rem; vertical-align: middle; border-bottom: 1px solid rgba(148,163,184,.14); }
    .table-sm tr:last-child td { border-bottom: none; }

    .item-code { font-weight: 700; font-size: .9rem; color: #334155; }
    .item-name { font-size: .82rem; color: #64748b; margin-top: .15rem; line-height: 1.35; }

    /* Selected row highlight (selaras shipment: state jelas) */
    #grnLinesBody tr:has(.line-check:checked) {
        background: rgba(51,65,85,.045);
        box-shadow: inset 3px 0 0 #334155;
    }

    .progress-mini {
        height: 3px;
        border-radius: 999px;
        background: rgba(148,163,184,.2);
        overflow: hidden;
        margin-top: .3rem;
        width: 80px;
    }
    .progress-mini-bar {
        height: 100%;
        border-radius: 999px;
        background: rgba(234,179,8,.8);
    }

    .qty-input {
        width: 88px;
        text-align: right;
        font-variant-numeric: tabular-nums;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        font-size: .85rem;
    }

    .qty-cell {
        min-width: 112px;
    }
    .qty-unit {
        display: block;
        margin-top: .12rem;
        color: var(--muted);
        font-size: .68rem;
        font-weight: 700;
        line-height: 1;
        text-align: right;
    }
    .qty-stock-preview {
        display: block;
        margin-top: .18rem;
        color: #2563eb;
        font-size: .66rem;
        line-height: 1.1;
        text-align: right;
        white-space: nowrap;
    }
    .unit-stack,
    .qty-stack {
        display: flex;
        flex-direction: column;
        gap: .12rem;
        line-height: 1.15;
    }
    .unit-stack small,
    .qty-stack small {
        color: var(--muted);
        font-size: .68rem;
    }
    .confirm-kpis {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .55rem;
        margin-bottom: .75rem;
    }
    .confirm-kpi {
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: .55rem .7rem;
        background: color-mix(in srgb, var(--card) 94%, var(--bg) 6%);
    }
    .confirm-kpi-label {
        color: var(--muted);
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .confirm-kpi-value {
        display: block;
        margin-top: .12rem;
        font-size: .92rem;
        font-weight: 800;
    }
    .confirm-stock {
        color: #2563eb;
        font-size: .7rem;
        white-space: nowrap;
    }

    .row-hidden-by-filter { display: none !important; }
    .filter-empty-msg { text-align:center; color:var(--muted); padding: 1.5rem; font-size:.88rem; }

    /* ── Header field labels ── */
    .field-lbl {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--muted);
        margin-bottom: .25rem;
    }

    @media (max-width: 767.98px) {
        .shp-wrap { padding-inline: .5rem; padding-bottom: 6.5rem; }
        .shp-topbar { padding: .55rem .75rem; }
        .shp-topbar-code { font-size: 1rem; }
        .shp-meta-code { font-size: 1rem; }

        /* Header form: 1 kolom, field lebih besar & mudah ditekan */
        .field-lbl { font-size: .74rem; }
        .form-control-sm, .form-select-sm { min-height: 42px; font-size: .95rem; }
        .filter-bar .form-control, .filter-bar .form-select { min-height: 42px; }

        /* Tabel → kartu item (selaras shipment) */
        thead { display: none; }
        #grnLinesBody tr {
            display: block;
            border: 1px solid var(--line);
            border-radius: 12px;
            margin-bottom: .6rem;
            padding: .7rem .85rem;
            box-shadow: 0 1px 4px rgba(15,23,42,.05);
        }
        #grnLinesBody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 0;
            padding-block: .3rem;
        }
        #grnLinesBody td[data-label]::before {
            content: attr(data-label);
            font-size: .8rem;
            font-weight: 600;
            color: var(--muted);
            flex-shrink: 0;
            margin-right: .75rem;
        }

        /* Baris item full-width + garis pemisah */
        td.td-item {
            display: block !important;
            padding-bottom: .5rem !important;
            margin-bottom: .35rem;
            border-bottom: 1px dashed rgba(148,163,184,.28) !important;
        }
        td.td-item::before { display: none !important; }
        .item-code { font-size: 1rem; }
        .item-name { font-size: .88rem; }

        /* Sembunyikan kolom bernilai rendah di HP (data tetap ada utk JS) */
        .col-harga,
        #grnLinesBody td[data-label="#"],
        #grnLinesBody td[data-label="Unit"],
        #grnLinesBody td[data-label="Qty PO"] { display: none !important; }

        /* Sisa = penekanan konteks batas terima */
        #grnLinesBody td[data-label="Sisa"] { font-size: .9rem; }
        #grnLinesBody td[data-label="Sisa"]::before { font-weight: 700; color: #334155; }

        /* Input qty lebih besar & mudah diketik */
        #grnLinesBody td[data-label="Diterima"]::before,
        #grnLinesBody td[data-label="Reject"]::before { font-weight: 700; color: #334155; }
        .qty-input { width: 132px; height: 44px; font-size: 1.05rem; }
        .qty-cell { min-width: 150px; }
        .qty-unit, .qty-stock-preview { font-size: .75rem; }

        /* Checkbox pilih lebih besar + label di kiri */
        td[data-label="Pilih"] { justify-content: flex-start; gap: .55rem; }
        .line-check { width: 1.3rem; height: 1.3rem; }

        /* Izinkan footer sticky menempel ke viewport (card default overflow:hidden) */
        .grn-detail-card { overflow: visible; }

        /* Aksi bawah sticky (selaras shipment: tombol full-width mudah dijangkau) */
        .grn-actions {
            position: sticky; bottom: 0; z-index: 60;
            flex-direction: column; align-items: stretch; gap: .55rem;
            background: var(--card, #fff);
            border-top: 1px solid var(--line);
            box-shadow: 0 -3px 12px rgba(15,23,42,.10);
            margin-inline: -.85rem; margin-bottom: -.85rem;
            padding: .7rem .85rem;
            border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;
        }
        .grn-actions #footer-total { text-align: center; }
        .grn-actions .grn-actions-btns { display: flex; gap: .5rem; }
        .grn-actions .grn-actions-btns .btn-shp-primary { flex: 1; min-height: 46px; font-size: .98rem !important; }
        .grn-actions .grn-actions-btns .btn-shp-outline { min-height: 46px; }
    }
</style>
@endpush

@section('content')
@php
    $u = auth()->user();
    $canSeeMoney = $u?->canSeePurchasePrices() ?? false;
    $hasOrder    = isset($order) && $order?->id;

    $defaultDate = old('date', now()->toDateString());

    $selectedSupplierId = old('supplier_id',
        $hasOrder ? $order->supplier_id : $selectedSupplierId ?? request('supplier_id'));

    $detailLines = $hasOrder ? $order->lines : $lines ?? collect();

    if (!$hasOrder && !$selectedSupplierId && $detailLines->isNotEmpty()) {
        $fp = $detailLines->first()?->purchaseOrder ?? null;
        if ($fp) $selectedSupplierId = $fp->supplier_id;
    }

    $selectedSupplier = isset($suppliers) ? $suppliers->firstWhere('id', $selectedSupplierId) : null;

    $defaultWhCode = $defaultWhCode ?? 'RM';
    $defaultWarehouse = $defaultWarehouse
        ?? $warehouses->firstWhere('code', $defaultWhCode)
        ?? $warehouses->firstWhere('code', 'RM');
    if (!$defaultWarehouse) { $defaultWhCode = 'RM'; $defaultWarehouse = $warehouses->first(); }

    $selectedWarehouseId = old('warehouse_id', $selectedWarehouseId ?? $defaultWarehouse?->id ?? '');

    // filter baris yang belum fully received — .values() wajib agar key sequential (0,1,2,…)
    // supaya name="selected[{{ $idx }}]" cocok dengan index po_line_id[]/item_id[] di controller
    $activeLines        = $detailLines->filter(fn($l) => !($l->fully_received ?? false))->values();
    $fullyReceivedCount = $detailLines->count() - $activeLines->count();
    $colCount           = $canSeeMoney ? 9 : 8;
@endphp

<div class="shp-topbar">
    <span class="shp-topbar-code">Goods Receipt Baru</span>
    <span class="shp-badge">Buat Draft</span>
    <span class="shp-topbar-spacer"></span>
    <div class="d-flex gap-2">
        @if ($hasOrder)
            <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
               class="btn btn-shp-outline" style="text-decoration:none">Kembali ke PO</a>
        @else
            <a href="{{ route('purchasing.purchase_receipts.index') }}"
               class="btn btn-shp-outline" style="text-decoration:none">Daftar GRN</a>
        @endif
    </div>
</div>

<div class="shp-wrap">

    {{-- ── SUBTITLE / META ── --}}
    <div class="shp-meta-bar">
        @if ($hasOrder)
            <span class="shp-meta-code">Penerimaan untuk {{ $order->code }}</span>
            <span class="shp-meta-store">{{ $order->supplier?->name ?? '-' }}</span>
            <span class="tag tag-material">PO {{ $order->code }}</span>
        @else
            <span class="shp-meta-code">Pilih item dari PO yang sudah Approved</span>
            <span class="tag tag-material">PO belum dipilih</span>
            @unless ($hasOrder)
                <span class="shp-badge">1 GRN = 1 PO</span>
            @endunless
        @endif
    </div>

    {{-- ── ERRORS ── --}}
    @if ($errors->any())
        <div class="alert alert-danger py-2 small mb-3">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif

    {{-- ── FILTER BAR (hanya mode list) ── --}}
    @unless ($hasOrder)
    <div class="filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <div class="field-lbl">Cari barang / PO</div>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="grn-search" class="form-control"
                           placeholder="Kode, nama, nomor PO…" autocomplete="off">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="field-lbl">Supplier</div>
                <select id="grn-supplier" class="form-select form-select-sm">
                    <option value="">Semua supplier</option>
                    @foreach ($suppliers as $sup)
                        <option value="{{ $sup->id }}" @selected((string)$selectedSupplierId === (string)$sup->id)>
                            {{ $sup->code }} — {{ $sup->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2 align-items-end">
                <div class="stat-pill flex-fill text-center">
                    <span class="lbl">Tampil</span>
                    <span class="val mono" id="cntVisible">0</span>
                </div>
                <div class="stat-pill flex-fill text-center">
                    <span class="lbl">Dipilih</span>
                    <span class="val mono" id="cntSelected">0</span>
                </div>
            </div>
        </div>
        <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
            <span class="field-lbl mb-0" style="font-size:.7rem;">PO dipilih:</span>
            <span class="mono" id="cntPo" style="font-size:.82rem;">—</span>
        </div>
    </div>
    @endunless

    {{-- ── FORM ── --}}
    <form id="grnForm" method="POST" action="{{ route('purchasing.purchase_receipts.store') }}">
        @csrf
        <input type="hidden" name="ignore_duplicate" id="ignore_duplicate" value="0">
        
        @if(session('duplicate_warning'))
            <div class="alert alert-warning py-3 d-flex align-items-start mb-3" style="border-radius:10px;">
                <i class="bi bi-exclamation-triangle-fill me-3 fs-4 mt-1"></i>
                <div>
                    <strong>Peringatan Duplikasi!</strong><br>
                    {{ session('duplicate_warning') }}
                    <div class="mt-2">
                        <button type="button" class="btn btn-warning btn-sm" onclick="document.getElementById('ignore_duplicate').value='1'; document.getElementById('btnConfirm').click(); document.getElementById('grnForm').submit();">
                            <i class="bi bi-check2-all me-1"></i> Ya, Lanjutkan Simpan
                        </button>
                    </div>
                </div>
            </div>
        @endif
        
        <input type="hidden" id="purchase_order_id" name="purchase_order_id"
               value="{{ $hasOrder ? $order->id : '' }}">
        <input type="hidden" id="grn_supplier_id" name="supplier_id"
               value="{{ $hasOrder ? $order->supplier_id : ($selectedSupplierId ?? '') }}">

        {{-- ── HEADER FIELDS ── --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">

                    <div class="col-12 col-sm-4">
                        <div class="field-lbl">Tanggal</div>
                        <input type="text" name="date" id="date"
                               class="form-control form-control-sm @error('date') is-invalid @enderror"
                               value="{{ $defaultDate }}" data-gf-date autocomplete="off">
                        @error('date')<div class="invalid-feedback small">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 col-sm-4">
                        <div class="field-lbl">Gudang Tujuan</div>
                        <select name="warehouse_id" id="warehouse_id"
                                class="form-select form-select-sm @error('warehouse_id') is-invalid @enderror" required>
                            <option value="">— Pilih gudang —</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected((string) $selectedWarehouseId === (string) $warehouse->id)>
                                    {{ $warehouse->code }} — {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_id')<div class="invalid-feedback small">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 col-sm-4">
                        <div class="field-lbl">No. Surat Jalan</div>
                        <input type="text" name="surat_jalan_no" id="surat_jalan_no"
                               class="form-control form-control-sm @error('surat_jalan_no') is-invalid @enderror"
                               value="{{ old('surat_jalan_no') }}"
                               placeholder="Kosongkan → auto SJ-…" maxlength="100">
                        @error('surat_jalan_no')<div class="invalid-feedback small">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 col-sm-4">
                        <div class="field-lbl">Catatan</div>
                        <input type="text" name="notes"
                               class="form-control form-control-sm @error('notes') is-invalid @enderror"
                               value="{{ old('notes') }}" placeholder="Opsional">
                    </div>

                    <div class="col-12 col-sm-4">
                        <div class="field-lbl">Total Masuk Stok</div>
                        <div class="form-control form-control-sm bg-light-subtle mono fw-bold">
                            <span id="totalReceivedDisplay">—</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── TABEL DETAIL ── --}}
        <div class="card mb-4 grn-detail-card">
            <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap py-2">
                <span class="fw-semibold small">Detail Barang</span>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    @if ($fullyReceivedCount > 0)
                        <span class="tag tag-done">✓ {{ $fullyReceivedCount }} sudah selesai</span>
                    @endif
                    {{-- Tombol Terima Semua --}}
                    <button type="button" id="btnReceiveAll"
                            class="btn btn-shp-outline d-none">
                        ⚡ Terima Semua
                    </button>
                    <input type="checkbox" id="checkAll" class="form-check-input" title="Pilih semua">
                </div>
            </div>

            <div class="px-3 pt-2 pb-1 receipt-rule">
                <i class="bi bi-info-circle me-1"></i>
                <strong>Diterima</strong> masuk stok.
                <strong class="text-danger">Reject</strong> tidak masuk stok, tetapi tetap menghabiskan sisa Qty PO.
                Kosongkan reject jika penerimaan hanya sebagian.
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:36px;" class="text-center"></th>
                            <th style="width:32px;" class="text-center">#</th>
                            <th>Item</th>
                            <th class="text-end">Qty PO</th>
                            <th class="text-end">Sisa</th>
                            <th class="text-end" style="width:125px;">Diterima <small class="th-sub">masuk stok</small></th>
                            <th class="text-end" style="width:125px;">Reject <small class="th-sub th-sub-reject">tidak masuk stok</small></th>
                            @if ($canSeeMoney)
                                <th class="text-end col-harga">Harga</th>
                            @endif
                            <th class="text-center" style="width:90px;">Satuan</th>
                        </tr>
                    </thead>
                    <tbody id="grnLinesBody">

                    @forelse ($activeLines as $idx => $line)
                    @php
                        $po              = $hasOrder ? $order : ($line->purchaseOrder ?? null);
                        $hasDraftGrn     = (bool)($line->has_draft_grn ?? false);
                        $isPartial       = (bool)($line->partially_received ?? false);
                        $qtyPo           = (float) $line->qty;
                        $qtyRemaining    = (float)($line->qty_remaining ?? $qtyPo);
                        $qtyReceivedSoFar= (float)($line->qty_received_posted ?? 0);
                        $qtyRejectedSoFar= (float)($line->qty_rejected_posted ?? 0);
                        $qtyAccountedSoFar = $qtyReceivedSoFar + $qtyRejectedSoFar;
                        $pctDone         = $qtyPo > 0 ? min(100, round(($qtyAccountedSoFar / $qtyPo) * 100)) : 0;
                        $lineAllocation  = ($line->allocation ?? ($line->item?->default_allocation ?? 'hpp')) === 'expense' ? 'expense' : 'hpp';
                        $lineExpenseAcc  = $line->expenseAccount ?? null;

                        $poId   = $po?->id;
                        $poCode = $po?->code;
                        $sup    = $po?->supplier ?? null;
                        $supId  = $sup?->id ?? '';
                        $searchStr = mb_strtolower(trim(
                            ($line->item?->code ?? '') . ' ' .
                            ($line->item?->name ?? '') . ' ' .
                            ($poCode ?? '') . ' ' .
                            ($sup?->code ?? '') . ' ' . ($sup?->name ?? '')
                        ));
                    @endphp

                    <tr data-line-index="{{ $idx }}"
                        data-qty-po="{{ $qtyPo }}"
                        data-qty-remaining="{{ $qtyRemaining }}"
                        data-qty-stock-po="{{ (float) $qtyPo * $line->effectiveConversionFactor() }}"
                        data-qty-stock-remaining="{{ (float) $qtyRemaining * $line->effectiveConversionFactor() }}"
                        data-purchase-unit="{{ $line->effectivePurchaseUnit() }}"
                        data-stock-unit="{{ $line->effectiveStockUnit() }}"
                        data-conversion-factor="{{ $line->effectiveConversionFactor() }}"
                        data-po-id="{{ $poId }}"
                        data-po-code="{{ $poCode }}"
                        data-supplier-id="{{ $supId }}"
                        data-search="{{ e($searchStr) }}"
                        class="{{ $hasDraftGrn ? 'opacity-50' : '' }}">

                        {{-- Checkbox --}}
                        <td class="text-center" data-label="Pilih">
                            @if ($hasDraftGrn)
                                <i class="bi bi-hourglass-split text-warning" style="font-size:.85rem;"
                                   title="Ada GRN draft belum diposting"></i>
                            @else
                                <input type="checkbox" class="form-check-input line-check"
                                       name="selected[{{ $idx }}]"
                                       @checked(!is_null(old('selected.' . $idx)))>
                            @endif
                        </td>

                        {{-- No --}}
                        <td class="text-center mono text-muted" style="font-size:.78rem;" data-label="#">
                            {{ $loop->iteration }}
                        </td>

                        {{-- Item --}}
                        <td class="td-item" data-label="Item">
                            <div class="item-code mono">{{ $line->item?->code ?? '-' }}</div>
                            <div class="item-name">
                                {{ $line->item?->name ?? '-' }}
                                @if (!$hasOrder && $po)
                                    &nbsp;<span class="tag tag-po" style="font-size:.65rem;">{{ $poCode }}</span>
                                @endif
                                @if ($hasDraftGrn)
                                    &nbsp;<span class="tag tag-draft-grn">Draft GRN</span>
                                @endif
                                @if ($isPartial)
                                    &nbsp;<span class="tag tag-partial">Sebagian diterima</span>
                                @endif
                            </div>
                            <div class="mt-1">
                                @if ($lineAllocation === 'expense')
                                    <span class="tag" style="font-size:.65rem;background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;">Biaya{{ $lineExpenseAcc ? ' · '.$lineExpenseAcc->code : '' }}</span>
                                @else
                                    <span class="tag" style="font-size:.65rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">Persediaan / HPP</span>
                                @endif
                            </div>
                            @if ($isPartial)
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <div class="progress-mini">
                                        <div class="progress-mini-bar" style="width:{{ $pctDone }}%;"></div>
                                    </div>
                                    <span style="font-size:.7rem;color:var(--muted);">
                                        {{ number_format($qtyAccountedSoFar,2,',','.') }} / {{ number_format($qtyPo,2,',','.') }} ({{ $pctDone }}%)
                                        @if ($qtyRejectedSoFar > 0)
                                            <span class="text-warning">· {{ number_format($qtyRejectedSoFar,2,',','.') }} reject</span>
                                        @endif
                                    </span>
                                </div>
                            @endif

                            <input type="hidden" name="po_line_id[]" value="{{ $line->id }}">
                            <input type="hidden" name="item_id[]"    value="{{ $line->item_id }}">
                            @if ($canSeeMoney)
                                <input type="hidden" name="unit_price[]" value="{{ $line->unit_price }}">
                            @endif
                            <input type="hidden" name="qty_received[]" data-purchase-hidden="received" value="{{ old('qty_received.' . $idx, '') }}">
                            <input type="hidden" name="qty_reject[]" data-purchase-hidden="reject" value="{{ old('qty_reject.' . $idx, '') }}">
                            <input type="hidden" name="unit[]" value="{{ $line->effectivePurchaseUnit() }}">
                        </td>

                        {{-- Qty PO --}}
                        <td class="text-end mono text-muted" style="font-size:.82rem;" data-label="Qty PO">
                            <div class="qty-stack">
                                <span>{{ number_format($qtyPo, 2, ',', '.') }} {{ $line->effectivePurchaseUnit() }}</span>
                                <small>{{ decimal_id((float) $qtyPo * $line->effectiveConversionFactor(), 2) }} {{ $line->effectiveStockUnit() }} stok</small>
                            </div>
                        </td>

                        {{-- Sisa --}}
                        <td class="text-end mono" data-label="Sisa"
                            style="font-weight:{{ $isPartial ? '700' : '400' }};
                                   color:{{ $isPartial ? 'rgba(234,179,8,1)' : 'inherit' }};">
                            <div class="qty-stack">
                                <span>{{ number_format($qtyRemaining, 2, ',', '.') }} {{ $line->effectivePurchaseUnit() }}</span>
                                <small>{{ decimal_id((float) $qtyRemaining * $line->effectiveConversionFactor(), 2) }} {{ $line->effectiveStockUnit() }} stok</small>
                            </div>
                        </td>

                        {{-- Qty Diterima --}}
                        <td class="text-end" data-label="Diterima">
                            <div class="qty-cell">
                                <input type="text" inputmode="decimal" name="stock_qty_received[]"
                                       class="form-control form-control-sm qty-input qty-received-input @error('qty_received.' . $idx) is-invalid @enderror @error('stock_qty_received.' . $idx) is-invalid @enderror"
                                       value="{{ old('stock_qty_received.' . $idx, '') }}"
                                       placeholder="0,00" autocomplete="off"
                                       @if ($hasDraftGrn) disabled @endif>
                                <span class="qty-unit">{{ $line->effectiveStockUnit() }}</span>
                                <span class="qty-stock-preview" data-stock-preview="received">Beli: 0 {{ $line->effectivePurchaseUnit() }}</span>
                            </div>
                        </td>

                        {{-- Qty Reject --}}
                        <td class="text-end" data-label="Reject">
                            <div class="qty-cell">
                                <input type="text" inputmode="decimal" name="stock_qty_reject[]"
                                       class="form-control form-control-sm qty-input qty-reject-input @error('qty_reject.' . $idx) is-invalid @enderror @error('stock_qty_reject.' . $idx) is-invalid @enderror"
                                       value="{{ old('stock_qty_reject.' . $idx, '') }}"
                                       placeholder="0,00" autocomplete="off"
                                       @if ($hasDraftGrn) disabled @endif>
                                <span class="qty-unit">{{ $line->effectiveStockUnit() }}</span>
                                <span class="qty-stock-preview" data-stock-preview="reject">Beli: 0 {{ $line->effectivePurchaseUnit() }}</span>
                            </div>
                        </td>

                        @if ($canSeeMoney)
                            <td class="text-end mono col-harga" data-label="Harga" style="font-size:.82rem;">
                                {{ number_format($line->unit_price, 0, ',', '.') }}
                            </td>
                        @endif

                        {{-- Unit --}}
                        <td class="text-center mono text-muted" style="font-size:.8rem;" data-label="Unit">
                            <div class="unit-stack">
                                <span>Beli: {{ $line->effectivePurchaseUnit() }}</span>
                                <small>Stok: {{ $line->effectiveStockUnit() }}</small>
                            </div>
                        </td>
                    </tr>

                    @empty
                    <tr id="empty-state-row">
                        <td colspan="{{ $colCount }}" class="text-center text-muted py-5">
                            @if ($fullyReceivedCount > 0 && $detailLines->isNotEmpty())
                                <div style="font-size:1.5rem;margin-bottom:.5rem;">✅</div>
                                Semua item sudah diproses.
                            @elseif (!$hasOrder && !$selectedSupplierId)
                                Tidak ada item dari PO Approved.
                            @elseif (!$hasOrder && $selectedSupplierId)
                                Tidak ada item PO Approved untuk supplier ini.
                            @else
                                Tidak ada detail item.
                            @endif
                        </td>
                    </tr>
                    @endforelse

                    </tbody>
                </table>
            </div>

            <div id="filter-empty-msg" class="filter-empty-msg d-none">
                Tidak ada item yang cocok.
            </div>

            <div class="card-footer grn-actions d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div class="text-muted small" id="footer-total">
                    Total masuk stok: <span class="mono fw-bold" id="footerTotalAlt">—</span>
                </div>
                <div class="d-flex gap-2 grn-actions-btns">
                    @if ($hasOrder)
                        <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
                           class="btn btn-shp-outline">Batal</a>
                    @else
                        <a href="{{ route('purchasing.purchase_receipts.index') }}"
                           class="btn btn-shp-outline">Batal</a>
                    @endif
                    <button type="button" id="btnSubmitGrn" class="btn btn-shp-primary">
                        Buat Draft GRN
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>

{{-- ── MODAL KONFIRMASI ── --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px; overflow:hidden;">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold">Konfirmasi GRN</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="confirmSummary"></div>
            </div>
            <div class="modal-footer border-top justify-content-end">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-shp-outline"
                            data-bs-dismiss="modal">Kembali</button>
                    <button type="button" id="btnConfirm" class="btn btn-shp-primary">
                        Buat Draft
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const HAS_ORDER = @json($hasOrder);

    /* ── helpers ── */
    function parseNum(v) {
        if (v === null || v === undefined || v === '') return 0;
        v = String(v).trim().replace(/\s/g, '');
        if (!v) return 0;
        if (v.includes(',')) v = v.replace(/\./g, '').replace(',', '.');
        else if (/^\d{1,3}(\.\d{3}){2,}$/.test(v)) v = v.replace(/\./g, '');
        const n = parseFloat(v);
        return isNaN(n) ? 0 : n;
    }

    function fmtId(n) {
        try { return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        catch { return (n || 0).toFixed(2); }
    }

    function getPurchaseUnit(row) {
        return row?.dataset.purchaseUnit || 'pcs';
    }

    function getStockUnit(row) {
        return row?.dataset.stockUnit || getPurchaseUnit(row);
    }

    function getConversionFactor(row) {
        const factor = parseNum(row?.dataset.conversionFactor || 1);
        return factor > 0 ? factor : 1;
    }

    function formatUnitTotals(totals) {
        return Object.entries(totals)
            .filter(([, qty]) => qty > 0.000001)
            .map(([unit, qty]) => `${fmtId(qty)} ${unit}`)
            .join(' · ') || '—';
    }

    function rows() {
        return Array.from(document.querySelectorAll('#grnLinesBody tr[data-line-index]'));
    }

    function visibleRows() {
        return rows().filter(r => !r.classList.contains('row-hidden-by-filter'));
    }

    function getLimit(row) {
        const rem = parseNum(row.dataset.qtyStockRemaining);
        return rem > 0 ? rem : parseNum(row.dataset.qtyStockPo);
    }

    function updateHiddenPurchaseQty(row) {
        const factor = getConversionFactor(row);
        const rec = parseNum(row.querySelector('.qty-received-input')?.value || 0);
        const rej = parseNum(row.querySelector('.qty-reject-input')?.value || 0);
        const recHidden = row.querySelector('[data-purchase-hidden="received"]');
        const rejHidden = row.querySelector('[data-purchase-hidden="reject"]');
        const recStockHidden = row.querySelector('[data-stock-hidden="received"]');
        const rejStockHidden = row.querySelector('[data-stock-hidden="reject"]');

        if (recHidden) recHidden.value = (rec / factor).toFixed(6);
        if (rejHidden) rejHidden.value = (rej / factor).toFixed(6);
        if (recStockHidden) recStockHidden.value = rec.toFixed(6);
        if (rejStockHidden) rejStockHidden.value = rej.toFixed(6);
    }

    function updateRowStockPreview(row) {
        const factor = getConversionFactor(row);
        const stockUnit = getStockUnit(row);
        const rec = parseNum(row.querySelector('.qty-received-input')?.value || 0);
        const rej = parseNum(row.querySelector('.qty-reject-input')?.value || 0);
        const recPreview = row.querySelector('[data-stock-preview="received"]');
        const rejPreview = row.querySelector('[data-stock-preview="reject"]');

        const purchaseUnit = getPurchaseUnit(row);
        if (recPreview) recPreview.textContent = `Beli: ${fmtId(rec / factor)} ${purchaseUnit}`;
        if (rejPreview) rejPreview.textContent = `Beli: ${fmtId(rej / factor)} ${purchaseUnit}`;
        updateHiddenPurchaseQty(row);
    }

    /* ── validation ── */
    function validateRow(row) {
        const limit  = getLimit(row);
        const inpRec = row.querySelector('.qty-received-input');
        const inpRej = row.querySelector('.qty-reject-input');
        if (!inpRec || !inpRej) return true;

        const rec = parseNum(inpRec.value || 0);
        const rej = parseNum(inpRej.value || 0);

        inpRec.classList.remove('is-invalid');
        inpRej.classList.remove('is-invalid');

        let ok = true;
        if (rec < 0 || rec > limit) { inpRec.classList.add('is-invalid'); ok = false; }
        if (rej < 0 || rej > limit) { inpRej.classList.add('is-invalid'); ok = false; }
        if (rec + rej > limit)      { inpRec.classList.add('is-invalid'); inpRej.classList.add('is-invalid'); ok = false; }
        return ok;
    }

    /* ── total recalc ── */
    function recalcTotal() {
        const totals = {};
        rows().forEach(row => {
            const cb     = row.querySelector('.line-check');
            const inpRec = row.querySelector('.qty-received-input');
            const inpRej = row.querySelector('.qty-reject-input');
            updateRowStockPreview(row);
            if (!cb || !cb.checked || !inpRec || !inpRej) return;
            // Qty diterima adalah satu-satunya qty yang masuk stok.
            // Qty reject tidak boleh dikurangkan dari qty baik.
            const stockIn = parseNum(inpRec.value || 0);
            if (stockIn > 0) {
                const unit = getStockUnit(row);
                totals[unit] = (totals[unit] || 0) + stockIn;
            }
        });
        const fmted = formatUnitTotals(totals);
        const d1 = document.getElementById('totalReceivedDisplay');
        const d2 = document.getElementById('footerTotalAlt');
        if (d1) d1.textContent = fmted;
        if (d2) d2.textContent = fmted;
        updateSelectionSummary();
    }

    /* ── selection summary ── */
    function updateSelectionSummary() {
        const checked = rows().filter(r => r.querySelector('.line-check')?.checked);
        const cntSel = document.getElementById('cntSelected');
        if (cntSel) cntSel.textContent = checked.length;

        const poCodes = [...new Set(checked.map(r => r.dataset.poCode).filter(Boolean))];
        const cntPo = document.getElementById('cntPo');
        if (cntPo) cntPo.textContent = poCodes.length ? poCodes.join(', ') : '—';

        // Show/hide "Terima Semua" button
        const btnAll = document.getElementById('btnReceiveAll');
        if (btnAll) {
            const hasUnchecked = visibleRows().some(r => {
                const cb = r.querySelector('.line-check');
                return cb && !cb.checked && !cb.disabled;
            });
            btnAll.classList.toggle('d-none', !hasUnchecked);
        }
    }

    /* ── filter ── */
    function applyFilter() {
        const kw  = (document.getElementById('grn-search')?.value || '').trim().toLowerCase();
        const sup = String(document.getElementById('grn-supplier')?.value || '');

        let vis = 0;
        rows().forEach(row => {
            const match = (!kw || (row.dataset.search || '').includes(kw))
                       && (!sup || row.dataset.supplierId === sup);
            row.classList.toggle('row-hidden-by-filter', !match);
            if (match) vis++;
        });

        const cntVis = document.getElementById('cntVisible');
        if (cntVis) cntVis.textContent = vis;

        const msg = document.getElementById('filter-empty-msg');
        if (msg) msg.classList.toggle('d-none', vis > 0 || rows().length === 0);

        // uncheck checkAll when filter changes
        const ca = document.getElementById('checkAll');
        if (ca) ca.checked = false;

        updateSelectionSummary();
    }

    /* ── checkbox change ── */
    function onCheckboxChange(cb) {
        const row    = cb.closest('tr');
        if (!row) return;
        const inpRec = row.querySelector('.qty-received-input');
        const inpRej = row.querySelector('.qty-reject-input');

        if (!cb.checked) {
            inpRec.value = '';
            inpRej.value = '';
            inpRec.classList.remove('is-invalid');
            inpRej.classList.remove('is-invalid');
        }
        validateRow(row);
        updateRowStockPreview(row);
        recalcTotal();
    }

    function ensureChecked(row) {
        const cb = row.querySelector('.line-check');
        if (cb && !cb.checked) { cb.checked = true; onCheckboxChange(cb); }
    }

    /* ── qty input handlers ── */
    function onRecInput(inp) {
        const row  = inp.closest('tr'); if (!row) return;
        ensureChecked(row);
        validateRow(row);
        updateRowStockPreview(row);
        recalcTotal();
    }

    function onRejInput(inp) {
        const row  = inp.closest('tr'); if (!row) return;
        ensureChecked(row);
        validateRow(row);
        updateRowStockPreview(row);
        recalcTotal();
    }

    /* ── PO lock (list mode) ── */
    function lockPoOrThrow() {
        if (HAS_ORDER) return true;
        const poInput  = document.getElementById('purchase_order_id');
        const supInput = document.getElementById('grn_supplier_id');
        const checked  = rows().filter(r => r.querySelector('.line-check')?.checked);
        if (!checked.length) throw new Error('Belum ada item yang dicentang.');

        const poIds = [...new Set(checked.map(r => r.dataset.poId).filter(Boolean))];
        if (poIds.length > 1) {
            const codes = [...new Set(checked.map(r => r.dataset.poCode).filter(Boolean))];
            throw new Error('GRN tidak boleh campur beberapa PO.\nPO dipilih: ' + codes.join(', '));
        }

        poInput.value = poIds[0];

        // Sinkronkan supplier_id dari baris yang dipilih
        const supIds = [...new Set(checked.map(r => r.dataset.supplierId).filter(Boolean))];
        if (supIds.length === 1 && supInput) supInput.value = supIds[0];

        return true;
    }

    /* ── collect lines for confirmation ── */
    function collectLines() {
        const lines = []; let allValid = true;
        rows().forEach(row => {
            const cb     = row.querySelector('.line-check');
            const inpRec = row.querySelector('.qty-received-input');
            const inpRej = row.querySelector('.qty-reject-input');
            if (!cb || !cb.checked || !inpRec || !inpRej) return;
            if (!validateRow(row)) { allValid = false; return; }
            const rec = parseNum(inpRec.value || 0);
            const rej = parseNum(inpRej.value || 0);
            if (rec <= 0 && rej <= 0) return;
            const factor = getConversionFactor(row);
            lines.push({
                code: row.querySelector('.item-code')?.textContent?.trim() ?? '-',
                name: row.querySelector('.item-name')?.childNodes?.[0]?.textContent?.trim() ?? '',
                qtyPo: parseNum(row.dataset.qtyStockPo),
                sisa:  parseNum(row.dataset.qtyStockRemaining),
                rec, rej,
                unit: getStockUnit(row),
                purchaseUnit: getPurchaseUnit(row),
                stockUnit: getStockUnit(row),
                factor,
                sisaPurchase: parseNum(row.dataset.qtyRemaining),
                stockRec: rec,
                stockRej: rej,
            });
        });
        return { lines, allValid };
    }

    function buildConfirmTable(lines) {
        if (!lines.length)
            return '<div class="alert alert-warning small mb-0">Tidak ada item dengan qty yang diisi.</div>';

        const totals = {};
        lines.forEach(l => {
            // Reject tidak masuk stok; ringkasan stok hanya menjumlahkan qty baik.
            const stockIn = l.stockRec;
            if (stockIn > 0) totals[l.stockUnit] = (totals[l.stockUnit] || 0) + stockIn;
        });

        let h = `<div class="confirm-kpis">
            <div class="confirm-kpi">
                <span class="confirm-kpi-label">Item dipilih</span>
                <span class="confirm-kpi-value">${lines.length}</span>
            </div>
            <div class="confirm-kpi">
                <span class="confirm-kpi-label">Masuk stok</span>
                <span class="confirm-kpi-value confirm-stock">${formatUnitTotals(totals)}</span>
            </div>
        </div>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead><tr>
                <th>#</th><th>Item</th>
                <th class="text-end">Sisa</th>
                <th class="text-end">Diterima</th>
                <th class="text-end">Masuk stok</th>
                <th class="text-end">Reject</th>
            </tr></thead><tbody>`;
        lines.forEach((l, i) => {
            h += `<tr>
                <td class="mono text-muted">${i+1}</td>
                <td><div class="fw-bold mono">${l.code}</div><div class="text-muted small">${l.name || ''}</div></td>
                <td class="text-end mono"><div>${fmtId(l.sisa)} ${l.unit}</div><small class="text-muted">${fmtId(l.sisaPurchase)} ${l.purchaseUnit} PO</small></td>
                <td class="text-end mono fw-bold">${fmtId(l.rec)} ${l.unit}</td>
                <td class="text-end mono"><span class="confirm-stock">${fmtId(l.stockRec)} ${l.stockUnit}</span></td>
                <td class="text-end mono">${fmtId(l.rej)} ${l.unit}</td>
            </tr>`;
        });
        h += '</tbody></table></div>';
        return h;
    }

    /* ── submit flow ── */
    function openConfirm() {
        const { lines, allValid } = collectLines();
        if (!allValid) { alert('Ada qty yang tidak valid. Mohon periksa kembali.'); return; }
        if (!lines.length) { alert('Belum ada item yang diisi qty-nya.'); return; }
        try { lockPoOrThrow(); } catch (e) { alert(e.message); return; }

        document.getElementById('confirmSummary').innerHTML = buildConfirmTable(lines);
        new bootstrap.Modal(document.getElementById('confirmModal')).show();
    }

    /* ── "Terima Semua" ── */
    function receiveAll() {
        visibleRows().forEach(row => {
            const cb = row.querySelector('.line-check');
            if (!cb || cb.disabled) return;

            const limit = getLimit(row);
            const inpRec = row.querySelector('.qty-received-input');
            const inpRej = row.querySelector('.qty-reject-input');
            cb.checked = true;
            if (inpRec) inpRec.value = limit > 0 ? limit.toFixed(2) : '';
            // Full receipt bukan reject; biarkan field reject kosong.
            if (inpRej) inpRej.value = '';
            updateRowStockPreview(row);
        });

        recalcTotal();
        // open confirm directly
        openConfirm();
    }

    /* ── DOM wired ── */
    document.addEventListener('DOMContentLoaded', () => {
        // filter
        document.getElementById('grn-search')?.addEventListener('input', applyFilter);
        document.getElementById('grn-supplier')?.addEventListener('change', applyFilter);

        // checkAll
        document.getElementById('checkAll')?.addEventListener('change', e => {
            visibleRows().forEach(row => {
                const cb = row.querySelector('.line-check');
                if (!cb || cb.disabled) return;
                cb.checked = e.target.checked;
                onCheckboxChange(cb);
            });
        });

        // submit button
        document.getElementById('btnSubmitGrn')?.addEventListener('click', e => {
            e.preventDefault();
            openConfirm();
        });

        // confirm button
        document.getElementById('btnConfirm')?.addEventListener('click', () => {
            bootstrap.Modal.getInstance(document.getElementById('confirmModal'))?.hide();
            document.getElementById('grnForm').submit();
        });

        // Terima Semua
        document.getElementById('btnReceiveAll')?.addEventListener('click', receiveAll);

        // delegate input events
        document.addEventListener('input', e => {
            if (e.target.classList.contains('qty-received-input')) onRecInput(e.target);
            if (e.target.classList.contains('qty-reject-input'))   onRejInput(e.target);
        });

        document.addEventListener('change', e => {
            if (e.target.classList.contains('line-check')) onCheckboxChange(e.target);
        });

        // click row to toggle checkbox
        document.addEventListener('click', e => {
            const row = e.target.closest('#grnLinesBody tr[data-line-index]');
            if (!row) return;
            const tag = e.target.tagName;
            if (tag === 'INPUT' || tag === 'LABEL' || e.target.closest('input')) return;
            const cb = row.querySelector('.line-check');
            if (!cb || cb.disabled) return;
            cb.checked = !cb.checked;
            onCheckboxChange(cb);
        });

        // focus: select all in qty field
        document.addEventListener('focus', e => {
            if (e.target.classList.contains('qty-received-input') ||
                e.target.classList.contains('qty-reject-input')) {
                if (e.target.value) setTimeout(() => e.target.select(), 0);
            }
        }, true);

        // blur: normalize format
        document.addEventListener('blur', e => {
            if (e.target.classList.contains('qty-received-input') ||
                e.target.classList.contains('qty-reject-input')) {
                const v = e.target.value;
                if (v === '' || v === null) return;
                const n = parseNum(v);
                if (!isNaN(n)) e.target.value = n.toFixed(2);
            }
        }, true);

        // init
        rows().forEach(row => {
            const cb = row.querySelector('.line-check');
            if (cb?.checked) onCheckboxChange(cb);
        });
        applyFilter();
        recalcTotal();
    });

})();
</script>
@endpush
