{{-- resources/views/purchasing/purchase_orders/_form.blade.php --}}
@php
    use Illuminate\Support\Carbon;

    /** @var \App\Models\PurchaseOrder|null $order */
    $canSeeMoney = auth()->user()?->canSeePurchasePrices() ?? false;

    // =========================
    // ORDER TYPE
    // =========================
    $orderTypeRaw = old('order_type') ?? ($order?->order_type ?? (request('order_type') ?? 'material'));
    $allowedOrderTypes = ['material', 'finished_good', 'packing', 'asset', 'service', 'jasa', 'lainnya'];
    $orderType = in_array($orderTypeRaw, $allowedOrderTypes, true) ? $orderTypeRaw : 'material';

    $orderTypeOptions = [
        'material' => 'Bahan Produksi',
        'packing'  => 'Packaging',
        'service'  => 'Operasional',
        'finished_good' => 'Barang Jadi',
    ];
    $itemSuggestType = in_array($orderType, ['material', 'packing'], true)
        ? 'material'
        : ($orderType === 'finished_good' ? 'finished_good' : null);
    $itemSuggestExtra = match ($orderType) {
        'packing' => ['category_codes' => 'PACK'],
        'material' => ['exclude_category_codes' => 'PACK'],
        default => [],
    };

    // === DATE ===
    $dateRaw = old('date') ?? ($order?->date ?? now()->toDateString());
    $orderDate = $dateRaw instanceof Carbon ? $dateRaw->toDateString() : (string) $dateRaw;

    // === SUPPLIER ===
    // Saat create: tidak ada default supplier (user wajib pilih manual)
    $selectedSupplierId = old('supplier_id', $order?->supplier_id ?? request('supplier_id') ?? null);

    // === PAYMENT METHOD ===
    // Filter: exclude DP_APPLY (internal only)
    $visiblePaymentMethods = ($paymentMethods ?? collect())->filter(fn($pm) => $pm->code !== 'DP_APPLY')->values();
    $defaultPaymentMethodId = $visiblePaymentMethods->firstWhere('mode', 'transfer')?->id
        ?? $visiblePaymentMethods->first()?->id
        ?? null;
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
            border-radius: 16px;
            overflow: visible;
            margin-bottom: .85rem;
        }

        /* Focal card — Jenis PO + Supplier */
        .po-focal {
            border: 1px solid var(--line);
        }

        /* Jenis PO pill buttons */
        .po-type-pill {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .38rem .85rem;
            border-radius: 999px;
            border: 1.5px solid var(--line);
            background: transparent;
            color: var(--body);
            font-size: .82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s;
            white-space: nowrap;
        }
        .po-type-pill:hover {
            border-color: var(--primary, #2563eb);
            color: var(--primary, #2563eb);
            background: color-mix(in srgb, var(--primary, #2563eb) 6%, transparent);
        }
        .po-type-pill--active {
            border-color: var(--primary, #2563eb);
            background: var(--primary, #2563eb);
            color: #fff;
        }
        .po-type-pill--active:hover {
            background: var(--primary, #2563eb);
            color: #fff;
        }

        /* Supplier select lebih menonjol */
        .po-supplier-select {
            font-size: .95rem;
            padding: .55rem .85rem;
        }
        .po-type-scroll {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .po-card .card-body {
            padding: .9rem 1rem;
        }

        .po-card .card-header {
            padding: .75rem .9rem;
        }

        .po-section-title {
            font-weight: 900;
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
            border-radius: 10px;
        }

        .po-toolbar {
            display: flex;
            gap: .35rem;
            flex-wrap: wrap;
            align-items: center;
            padding: .65rem .75rem;
            border-bottom: 1px solid var(--line);
            background: rgba(148, 163, 184, .035);
        }
        .po-toolbar .btn {
            border-radius: 999px;
            font-weight: 800;
            padding: .22rem .65rem;
        }
        .po-toolbar-summary {
            margin-left: auto;
            color: var(--muted);
            font-size: .78rem;
            font-weight: 750;
        }
        .po-total-live { color:#2563eb; font-weight:900; }

        .po-lines-table {
            --bs-table-bg: transparent;
        }

        .po-lines-table thead th {
            text-transform: uppercase;
            letter-spacing: .06em;
            font-size: .68rem;
            color: var(--muted);
            padding: .58rem .65rem;
            background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
            border-bottom-color: var(--line);
        }

        .po-lines-table tbody td {
            vertical-align: middle;
            padding: .62rem .65rem;
            border-bottom-color: var(--line);
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
            font-weight: 900;
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
        .po-row.has-qty {
            background: rgba(37,99,235,.045);
            box-shadow: inset 3px 0 0 rgba(37,99,235,.45);
        }
        .po-row.is-empty {
            opacity: .76;
        }
        .po-row-item-main {
            font-weight: 850;
            line-height: 1.15;
        }
        .po-row-item-sub {
            color: var(--muted);
            font-size: .74rem;
            margin-top: .08rem;
        }
        .po-action-mini {
            border-radius: 999px !important;
            padding: .18rem .48rem;
            font-size: .72rem;
            font-weight: 800;
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
                padding: .75rem;
            }

            .po-card .card-header {
                padding: .7rem .75rem;
            }
            .po-toolbar { padding: .6rem .7rem; }
            .po-toolbar .btn { flex: 1 1 auto; }
            .po-toolbar-summary { width:100%; margin-left:0; text-align:center; }

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
                padding: .6rem .7rem;
                border-radius: 12px;
                border: 1px solid var(--line);
                background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
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
                font-size: .78rem;
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

        @media (max-width: 767.98px) {
            .po-card {
                border-radius: 12px;
                margin-bottom: .5rem;
            }
            .po-focal .card-body {
                padding: .7rem !important;
            }
            /* Jenis PO + Tanggal: pills kiri, tanggal compact di kanan, sama baris */
            .po-focal-top {
                display: flex !important;
                align-items: center;
                gap: .5rem;
                margin-bottom: .5rem !important;
                flex-wrap: nowrap;
            }
            .po-focal-top > div:first-child {
                flex: 1;
                min-width: 0;
                overflow: hidden;
            }
            .po-type-scroll {
                flex-wrap: nowrap !important;
                gap: .35rem !important;
                overflow-x: auto;
                margin-inline: 0 !important;
                padding-bottom: .1rem;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
            }
            .po-date-wrap {
                flex: 0 0 auto;
                min-width: 0 !important;
                width: 108px;
                margin-top: 0 !important;
            }
            .po-date-wrap .po-label { display: none; }
            .po-date-wrap input {
                min-width: 0 !important;
                width: 100%;
                font-size: .8rem !important;
                padding: .3rem .4rem;
                min-height: 34px;
                text-align: center;
            }
            .po-type-scroll {
                flex-wrap: nowrap !important;
                gap: .35rem !important;
                overflow-x: auto;
                margin-inline: 0;
                padding-bottom: .1rem;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
            }
            .po-type-scroll::-webkit-scrollbar { display: none; }
            .po-type-pill {
                flex: 0 0 auto;
                min-height: 34px;
                padding: .28rem .6rem;
                font-size: .76rem;
            }
            .po-supplier-select {
                min-height: 42px;
                font-size: 16px !important;
            }
            /* Detail Barang: input lebih kecil */
            .js-item-suggest-input {
                min-height: 36px;
                font-size: 13px !important;
            }
            .po-td-qty .po-field,
            .po-td-price .po-field {
                min-height: 36px;
                font-size: 13px !important;
                padding: .28rem .4rem;
            }
            .po-field { font-size: 14px !important; }
            .po-label {
                font-size: .64rem;
                margin-bottom: .18rem;
            }
            .po-card .card-header {
                border-bottom: 0 !important;
                padding: .55rem .7rem .25rem;
            }
            .po-section-title { font-size: .92rem; }
            /* Toolbar: sticky, hanya tampil tombol + Barang */
            .po-toolbar {
                position: sticky;
                top: 0;
                z-index: 20;
                margin-inline: -.01rem;
                padding: .38rem .55rem;
                background: color-mix(in srgb, var(--card) 97%, transparent);
                border-top: 1px solid var(--line);
                border-bottom: 1px solid var(--line);
            }
            .po-toolbar .btn {
                min-height: 34px;
                padding-inline: .5rem;
                font-size: .78rem;
            }
            #btn-reset-empty-lines { display: none; }
            #btn-add-line { display: none; }
            .po-toolbar-summary { display: none; }
            /* + Barang bawah: posisi di bawah harga (kolom 2), bukan full-width */
            .d-block.d-lg-none.text-center.py-2 {
                display: flex !important;
                justify-content: flex-end;
                padding: .3rem .5rem .1rem !important;
            }
            #btn-add-line-bottom {
                border-radius: 999px !important;
                font-size: .78rem;
                padding: .28rem .85rem;
                min-height: 32px;
            }
            /* Meta section: 1 baris — [Tipe Bayar] [Ongkir] [= Total] */
            .po-meta-wrap { padding: .05rem .5rem .5rem; }
            .po-meta-card {
                display: flex;
                align-items: center;
                gap: .4rem;
                border-radius: 10px;
                padding: .45rem .55rem;
            }
            /* inputs jadi flex row */
            .po-meta-inputs {
                flex: 1;
                display: flex !important;
                gap: .4rem;
                margin-bottom: 0 !important;
                min-width: 0;
            }
            .po-meta-inputs > div:first-child { display: none !important; } /* status */
            .po-meta-inputs > div:nth-child(2) { flex: 1.4; min-width: 0; } /* tipe bayar */
            .po-meta-ongkir { flex: 1; min-width: 0; grid-column: unset !important; }
            /* sembunyikan label di dalam meta card */
            .po-meta-card .po-label { display: none; }
            /* total: hanya grand total, di kanan */
            .po-meta-totals {
                flex: 0 0 auto;
                border-top: 0 !important;
                padding-top: 0 !important;
                text-align: right;
            }
            .po-total-line:not(:last-child) { display: none !important; }
            .po-total-line:last-child { border-top: 0; padding: 0; flex-direction: column; align-items: flex-end; }
            .po-total-key { font-size: .6rem; color: var(--muted); }
            .po-total-val { font-size: .95rem; font-weight: 900; }
            .po-table-wrapper {
                overflow: visible;
            }
            .po-lines-table {
                display: block;
                width: 100%;
            }
            .po-lines-table thead {
                display: none;
            }
            .po-lines-table tbody {
                display: block;
            }
            .po-lines-table {
                display: block;
                width: 100%;
                table-layout: fixed;
                border-collapse: separate;
                border-spacing: 0 8px;
            }
            .po-lines-table thead {
                display: none;
            }
            .po-lines-table tbody {
                display: block;
            }
            /* ── 2-row card per baris ── */
            .po-lines-table tbody tr {
                display: grid;
                grid-template-columns: 1fr 1fr auto;
                grid-template-rows: auto auto;
                align-items: start;
                column-gap: .4rem;
                row-gap: .3rem;
                width: 100%;
                box-sizing: border-box;
                margin: 0 0 .5rem;
                padding: .5rem .55rem .5rem;
                border-radius: 12px;
                border: 1px solid rgba(148, 163, 184, .30);
                background: var(--card);
                box-shadow:
                    0 4px 14px rgba(15, 23, 42, .07),
                    0 0 0 1px rgba(15, 23, 42, .02);
                overflow: visible;
            }
            body[data-theme="dark"] .po-lines-table tbody tr {
                background: rgba(15, 23, 42, .96);
            }
            .po-lines-table tbody td {
                border: 0 !important;
                padding: 0;
            }
            .po-col-no    { display: none !important; }
            .po-td-item   { grid-column: 1 / span 2; grid-row: 1; min-width: 0; }
            .po-td-action { grid-column: 3; grid-row: 1; position: static !important; display: flex !important; align-items: center; justify-content: flex-end; padding: 0 !important; }
            .po-td-action .btn { min-height: 32px; min-width: 32px; padding: .1rem .4rem; border-radius: 999px !important; font-weight: 800; font-size: .82rem; }
            .po-td-qty    { grid-column: 1; grid-row: 2; display: block !important; width: auto !important; margin: 0 !important; }
            .po-td-price  { grid-column: 2; grid-row: 2; display: block !important; width: auto !important; margin: 0 !important; }
            .po-td-total  { display: none !important; }
            .po-lines-no-money .po-td-qty { grid-column: 1 / span 2; }
            .po-lines-table tbody td[data-label]::before { display: none !important; }
            .po-num-display { text-align: left; font-weight: 800; }
            .po-row.has-qty {
                background: color-mix(in srgb, var(--card) 93%, rgba(37,99,235,.12));
                box-shadow:
                    inset 3px 0 0 rgba(37,99,235,.55),
                    0 8px 24px rgba(15, 23, 42, .08),
                    0 0 0 1px rgba(15, 23, 42, .02);
            }
            .po-row.is-empty {
                opacity: 1;
            }
            .po-meta-wrap {
                padding: .15rem .55rem .7rem;
            }
            .po-meta-card {
                border-radius: 12px;
                padding: .75rem;
            }
            .po-meta-inputs {
                gap: .55rem;
                margin-bottom: .7rem;
            }
            .po-total-line {
                padding: .32rem 0;
            }
            .po-total-key {
                font-size: .66rem;
            }
            .po-total-val {
                font-size: .86rem;
            }
            #btn-add-line-bottom {
                width: calc(100% - 1.1rem);
                min-height: 42px;
                border-radius: 12px !important;
                font-weight: 850;
            }
        }

        @media (min-width: 993px) {
            .po-col-no {
                width: 5%
            }
        }
    </style>
@endpush

{{-- FOCAL: JENIS PO + SUPPLIER + TANGGAL --}}
<div class="card po-card po-focal mb-3" data-order-type="{{ $orderType }}">
    <div class="card-body" style="padding:1.1rem 1.1rem .9rem;">

        {{-- Row atas: Jenis PO (kiri) + Tanggal (kanan) --}}
        <div class="po-focal-top d-flex align-items-start justify-content-between gap-3 mb-3 flex-wrap">
            <div style="min-width:0;flex:1;">
                <div class="po-label mb-2">Jenis PO</div>
                <div class="po-type-scroll" id="po-type-pills">
                    @foreach ($orderTypeOptions as $k => $label)
                        @php
                            $icons = ['material'=>'🧵','packing'=>'📦','service'=>'🔧','finished_good'=>'👕'];
                            $active = $orderType === $k;
                        @endphp
                        <button type="button"
                            class="po-type-pill {{ $active ? 'po-type-pill--active' : '' }}"
                            data-type="{{ $k }}">
                            {{ $icons[$k] ?? '' }} {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="po-date-wrap" style="min-width:140px;">
                <div class="po-label mb-1">Tanggal</div>
                <input type="text" name="date" value="{{ $orderDate }}"
                    class="form-control po-field gf-date-input @error('date') is-invalid @enderror"
                    data-gf-date autocomplete="off" style="min-width:140px;">
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- hidden input order_type --}}
        <input type="hidden" name="order_type" id="po-order-type-hidden" value="{{ $orderType }}">

        {{-- Supplier --}}
        <div class="po-label mb-1">Supplier</div>
        <select name="supplier_id" id="po-supplier"
            class="form-select po-field po-supplier-select @error('supplier_id') is-invalid @enderror"
            required>
            <option value="">— Pilih Supplier —</option>
            @foreach ($suppliers as $sup)
                <option value="{{ $sup->id }}"
                    data-po-types="{{ implode(',', (array) ($sup->po_types ?? [])) }}"
                    @selected((string) $selectedSupplierId === (string) $sup->id)>
                    {{ $sup->code }} — {{ $sup->name }}
                </option>
            @endforeach
        </select>
        @error('supplier_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @if (auth()->user()?->role === 'owner')
            <div class="text-muted mt-1" style="font-size: .75rem;">
                <i class="bi bi-info-circle me-1"></i>
                List supplier dan pengaturannya diambil dari menu <a href="{{ route('master.suppliers.index') }}" target="_blank" class="text-decoration-none fw-bold">Master Supplier</a>.
            </div>
        @endif
    </div>
</div>

{{-- LINES --}}
<div class="card po-card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center"
        style="background: transparent; border-bottom: 1px solid var(--line);">
        <div class="po-section-title">Detail Barang</div>
        <span class="text-muted small d-none d-md-inline">Isi qty barang yang ingin dibeli.</span>
    </div>

    <div class="po-toolbar">
        <button type="button" id="btn-add-line" class="btn btn-sm btn-outline-primary">+ Barang</button>
        <button type="button" id="btn-reset-empty-lines" class="btn btn-sm btn-outline-secondary">Reset Kosong</button>
        <span class="po-toolbar-summary">
            <span class="mono po-total-live" id="po-live-lines">0</span> item
            ·
            <span class="mono po-total-live" id="po-live-qty">0</span> qty
        </span>
    </div>

    <div class="table-responsive po-table-wrapper">
        <table class="table table-sm mb-0 po-lines-table {{ $canSeeMoney ? 'po-lines-has-money' : 'po-lines-no-money' }}" id="po-lines-table">
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

                    <tr class="po-row {{ ((float) ($qtyRaw ?: 0)) > 0 ? 'has-qty' : 'is-empty' }}">
                        <td class="text-center align-middle line-index po-col-no">{{ $loop->iteration }}</td>

                        <td class="po-td-item" data-label="Item">
                            <x-item-suggest :items="$items" idName="lines[{{ $i }}][item_id]"
                                :idValue="$lineItemId" :displayValue="$itemDisplay" :type="$itemSuggestType" :extraParams="$itemSuggestExtra" variant="mini"
                                displayMode="code-name"
                                :minChars="1" />
                            @error("lines.$i.item_id")
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror

                            <input type="hidden" name="lines[{{ $i }}][allocation]" class="line-alloc-raw"
                                value="{{ $alloc }}">
                            <input type="hidden" name="lines[{{ $i }}][expense_account_id]"
                                class="line-expacc-raw" value="{{ $expAcc }}">
                        </td>

                        <td data-label="Qty Beli" class="po-td-qty">
                            <input type="text" class="form-control po-field po-num-display line-qty-display"
                                inputmode="decimal" placeholder="Qty" value="{{ $qtyDisplay }}" autocomplete="off">
                            <input type="hidden" name="lines[{{ $i }}][qty]" class="line-qty-raw"
                                value="{{ $qtyRaw }}">
                            @error("lines.$i.qty")
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </td>

                        @if ($canSeeMoney)
                            <td data-label="Harga" class="po-td-price">
                                <input type="text" class="form-control po-field po-num-display line-price-display"
                                    inputmode="numeric" placeholder="Harga" value="{{ $priceDisplay }}" autocomplete="off">
                                <input type="hidden" name="lines[{{ $i }}][unit_price]" class="line-price-raw"
                                    value="{{ $priceRaw }}">
                                @error("lines.$i.unit_price")
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </td>

                            <td class="text-end align-middle line-total po-td-total mono" data-label="Nilai"></td>
                        @endif

                        <td class="text-center po-td-action">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line"
                                style="border-radius:12px;">
                                &times;
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr class="po-row is-empty">
                        <td class="text-center align-middle line-index po-col-no">1</td>

                        <td class="po-td-item" data-label="Item">
                            <x-item-suggest idName="lines[0][item_id]" :items="$items" displayMode="code-name"
                                :showName="false" :showCategory="false" :type="$itemSuggestType" :extraParams="$itemSuggestExtra"
                                placeholder="Masukan kode barang" />
                            <input type="hidden" name="lines[0][allocation]" class="line-alloc-raw" value="hpp">
                            <input type="hidden" name="lines[0][expense_account_id]" class="line-expacc-raw"
                                value="">
                        </td>

                        <td data-label="Qty Beli" class="po-td-qty">
                            <input type="text" class="form-control po-field po-num-display line-qty-display"
                                inputmode="decimal" placeholder="Qty" value="" autocomplete="off">
                            <input type="hidden" name="lines[0][qty]" class="line-qty-raw" value="">
                        </td>

                        @if ($canSeeMoney)
                            <td data-label="Harga" class="po-td-price">
                                <input type="text" class="form-control po-field po-num-display line-price-display"
                                    inputmode="numeric" placeholder="Harga" value="" autocomplete="off">
                                <input type="hidden" name="lines[0][unit_price]" class="line-price-raw" value="">
                            </td>

                            <td class="text-end align-middle line-total po-td-total mono" data-label="Nilai"></td>
                        @endif

                        <td class="text-center po-td-action">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line"
                                style="border-radius:12px;">
                                &times;
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>

    <div class="d-block d-lg-none text-center py-2">
        <button type="button" id="btn-add-line-bottom" class="btn btn-outline-primary btn-sm"
            style="border-radius:12px;">
            + Barang
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
            const btnResetEmpty = document.getElementById('btn-reset-empty-lines');

            const subtotalCell = document.getElementById('po-subtotal-cell');
            const subtotalMeta = document.getElementById('po-subtotal-meta');
            const shippingMeta = document.getElementById('po-shipping-meta');
            const grandMeta = document.getElementById('po-grand-meta');
            const liveLines = document.getElementById('po-live-lines');
            const liveQty = document.getElementById('po-live-qty');

            const orderTypeSelect = document.getElementById('po-order-type-hidden');
            const currentOrderType = @json($orderType);
            const isEdit = @json((bool) $order?->id);
            const canSeeMoney = @json($canSeeMoney);

            const shippingDisplay = document.querySelector('.shipping-display');
            const shippingRaw = document.querySelector('.shipping-raw');
            const supplierSelect = document.querySelector('select[name="supplier_id"]');

            function filterSuppliersByOrderType(type) {
                if (!supplierSelect) return;

                const options = Array.from(supplierSelect.querySelectorAll('option[value]'));
                let selectedVisible = false;

                options.forEach(opt => {
                    const types = (opt.dataset.poTypes || '').split(',').map(s => s.trim()).filter(Boolean);
                    const visible = types.length === 0 || types.includes(type);
                    opt.hidden = !visible;
                    opt.disabled = !visible;
                    if (visible && opt.selected) selectedVisible = true;
                });

                if (!selectedVisible) {
                    const firstVisible = options.find(opt => !opt.disabled);
                    if (firstVisible) supplierSelect.value = firstVisible.value;
                }
            }

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
                let filledLines = 0;
                let totalQty = 0;

                tableBody.querySelectorAll('tr').forEach(tr => {
                    syncRowRaw(tr);
                    subtotal += recalcRow(tr);

                    const qty = parseFloat(tr.querySelector('.line-qty-raw')?.value || '0') || 0;
                    const itemId = (tr.querySelector('.js-item-suggest-id')?.value || '').toString().trim();
                    const filled = qty > 0.0001 || itemId !== '';
                    tr.classList.toggle('has-qty', qty > 0.0001);
                    tr.classList.toggle('is-empty', !filled);
                    if (qty > 0.0001) {
                        filledLines++;
                        totalQty += qty;
                    }
                });

                const ship = parseFloat(shippingRaw?.value || '0') || 0;
                const grand = Math.max(0, subtotal + ship);

                if (subtotalCell) subtotalCell.textContent = fmtIntId(subtotal);
                if (subtotalMeta) subtotalMeta.textContent = fmtIntId(subtotal);
                if (shippingMeta) shippingMeta.textContent = fmtIntId(ship);
                if (grandMeta) grandMeta.textContent = fmtIntId(grand);
                if (liveLines) liveLines.textContent = fmtIntId(filledLines);
                if (liveQty) liveQty.textContent = fmtQtyId(totalQty).replace(/,00$/, '');
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
            btnResetEmpty?.addEventListener('click', function () {
                const rows = Array.from(tableBody.querySelectorAll('tr'));
                const removable = rows.filter((tr) => {
                    const qty = parseFloat(tr.querySelector('.line-qty-raw')?.value || '0') || 0;
                    const itemId = (tr.querySelector('.js-item-suggest-id')?.value || '').toString().trim();
                    return rows.length > 1 && qty <= 0.0001 && itemId === '';
                });

                removable.forEach((tr) => tr.remove());

                if (!tableBody.querySelector('tr')) {
                    addNewRow();
                    return;
                }

                renumberLines();
                recalcAll();
            });

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

            // Tab: item -> qty -> price (navigasi dalam baris)
            // Enter: tambah baris baru dari manapun
            tableBody.addEventListener('keydown', function(e) {
                const el = e.target;
                const tr = el.closest('tr');
                if (!tr) return;

                const isItem  = el.classList.contains('js-item-suggest-input');
                const isQty   = el.classList.contains('line-qty-display');
                const isPrice = el.classList.contains('line-price-display');

                if (!isItem && !isQty && !isPrice) return;

                if (e.key === 'Tab') {
                    e.preventDefault();
                    if (isItem) {
                        tr.querySelector('.line-qty-display')?.focus();
                    } else if (isQty) {
                        if (canSeeMoney) {
                            tr.querySelector('.line-price-display')?.focus();
                        } else {
                            // tidak ada price, lompat ke item baris berikutnya
                            const nextTr = tr.nextElementSibling;
                            if (nextTr) focusRowItem(nextTr);
                            else addNewRow();
                        }
                    } else if (isPrice) {
                        // price = field terakhir, lompat ke item baris berikutnya
                        const nextTr = tr.nextElementSibling;
                        if (nextTr) focusRowItem(nextTr);
                        else addNewRow();
                    }
                    return;
                }

                if (e.key === 'Enter') {
                    // Jika item suggest sedang terbuka, biarkan dropdown handle dulu
                    const dropdown = tr.querySelector('.item-suggest-dropdown');
                    if (isItem && dropdown && dropdown.children.length > 0) return;

                    e.preventDefault();
                    addNewRow();
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
            // Pill buttons — Jenis PO
            const hiddenOrderType = document.getElementById('po-order-type-hidden');
            function switchOrderType(nextType) {
                filterSuppliersByOrderType(nextType);
                if (nextType === currentOrderType) return;

                if (isEdit) {
                    const ids = Array.from(document.querySelectorAll('.js-item-suggest-id'));
                    const hasFilled = ids.some(el => (el.value || '').toString().trim() !== '');
                    if (hasFilled) {
                        const ok = confirm(
                            'Mengubah Jenis PO akan memuat ulang daftar item. Pastikan item yang sudah dipilih sesuai jenis baru. Lanjut?'
                        );
                        if (!ok) return;
                    }
                }

                const url = new URL(window.location.href);
                url.searchParams.set('order_type', nextType);
                window.location.href = url.toString();
            }

            document.querySelectorAll('.po-type-pill').forEach(btn => {
                btn.addEventListener('click', function() {
                    const nextType = this.dataset.type;
                    if (hiddenOrderType) hiddenOrderType.value = nextType;
                    document.querySelectorAll('.po-type-pill').forEach(b => b.classList.remove('po-type-pill--active'));
                    this.classList.add('po-type-pill--active');
                    switchOrderType(nextType);
                });
            });

            // init supplier filter based on current type
            filterSuppliersByOrderType(currentOrderType);

            // legacy select fallback (hidden but kept for compatibility)
            if (orderTypeSelect) {
                orderTypeSelect.addEventListener('change', function() {
                    switchOrderType(this.value || 'material');
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
