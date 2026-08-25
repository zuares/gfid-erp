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

    // Field legacy untuk kompatibilitas laporan dan PO lama. Tidak ditampilkan
    // sebagai pilihan user; PO baru memakai default material/campuran.
    $itemSuggestType = null;
    $itemSuggestExtra = [];

    // === DATE ===
    $dateRaw = old('date') ?? ($order?->date ?? now()->toDateString());
    $orderDate = $dateRaw instanceof Carbon ? $dateRaw->toDateString() : (string) $dateRaw;

    // === SUPPLIER ===
    // Saat create: tidak ada default supplier (user wajib pilih manual)
    $selectedSupplierId = old('supplier_id', $order?->supplier_id ?? request('supplier_id') ?? null);
    $selectedSupplier = collect($suppliers ?? [])->firstWhere('id', (int) $selectedSupplierId);
    $selectedSupplierLabel = $selectedSupplier
        ? trim(($selectedSupplier->code ? $selectedSupplier->code . ' — ' : '') . $selectedSupplier->name)
        : '';

    // === PAYMENT METHOD ===
    // Filter: exclude DP_APPLY (internal only)
    $visiblePaymentMethods = ($paymentMethods ?? collect())->filter(fn($pm) => $pm->code !== 'DP_APPLY')->values();
    // PO baru wajib dipilih manual; jangan mengarahkan user ke metode tertentu.
    $selectedPaymentMethodId = old('payment_method_id', $order?->payment_method_id ?? null);
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

    // Nilai awal agar halaman Edit PO langsung menampilkan total tersimpan
    // sebelum kalkulasi JavaScript selesai diinisialisasi.
    $initialSubtotal = (float) ($order?->subtotal ?? 0);
    $initialShipping = (float) ($order?->shipping_cost ?? 0);
    $initialGrandTotal = (float) ($order?->grand_total ?? 0);

    $receivedByLineId = $order && $order->exists ? app(\App\Services\Purchasing\PurchaseOrderService::class)->receivedQtyByLineId($order) : [];
@endphp

@push('head')
    <style>
        /* ══════════════════════════════════════════════════
           ITEMS TABLE CARD (SHP STYLE)
        ══════════════════════════════════════════════════ */
        .shp-table-card {
            background: var(--card, #fff);
            border-radius: 8px;
            border: 1px solid rgba(148,163,184,.18);
            box-shadow: 0 1px 6px rgba(15,23,42,.07);
            margin-top: .85rem;
            overflow: hidden;
        }
        body[data-theme="dark"] .shp-table-card {
            border-color: rgba(30,64,175,.55);
            box-shadow: 0 12px 36px rgba(15,23,42,.8);
        }
        .shp-table-head {
            display: flex;
            align-items: center;
            gap: .65rem;
            flex-wrap: wrap;
            padding: .85rem 1.25rem .7rem;
            border-bottom: 1px solid rgba(148,163,184,.14);
        }
        body[data-theme="dark"] .shp-table-head { border-bottom-color: rgba(51,65,85,.8); }
        .shp-table-title {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #9ca3af;
            font-weight: 700;
        }
        
        .shp-table { margin-bottom: 0; }
        .shp-table thead th {
            position: sticky; top: 0; z-index: 6;
            border-bottom-width: 1px;
            font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; color: #9ca3af;
            background: rgba(248,250,252,.98);
            padding: .55rem .85rem;
            white-space: nowrap;
        }
        body[data-theme="dark"] .shp-table thead th {
            background: rgba(15,23,42,.98);
            border-bottom-color: rgba(30,64,175,.7);
            color: #6b7280;
        }
        .shp-table tbody td {
            vertical-align: middle;
            border-top-color: rgba(148,163,184,.12);
            padding: .6rem .85rem;
        }
        body[data-theme="dark"] .shp-table tbody td { border-top-color: rgba(51,65,85,.65); }
        .shp-table tbody tr:nth-child(even) { background: rgba(249,250,251,.7); }
        body[data-theme="dark"] .shp-table tbody tr:nth-child(even) { background: rgba(15,23,42,.8); }

        .btn-remove-line {
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 10px !important;
            padding: 0;
            font-size: 1.1rem;
            background: transparent;
            color: #ef4444;
            border: none;
            transition: all .15s;
        }
        .btn-remove-line:hover {
            background: rgba(239,68,68,.1);
        }

        .line-unit-hint {
            display: inline-flex;
            align-items: center;
            max-width: 100%;
            margin-top: .22rem;
            padding: .12rem .38rem;
            border: 1px solid rgba(148,163,184,.28);
            border-radius: 5px;
            background: rgba(248,250,252,.78);
            color: var(--muted, #64748b);
            font-size: .64rem;
            font-weight: 800;
            line-height: 1.2;
            white-space: nowrap;
        }

        .line-unit-select {
            width: 100%;
            min-width: 0;
            min-height: 38px;
            padding: .375rem 1.9rem .375rem .75rem;
            border: 1px solid var(--line, rgba(148,163,184,.34));
            border-radius: 10px;
            background-color: var(--card, #fff);
            color: var(--text, #1e293b);
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.5;
        }

        .line-unit-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 .18rem rgba(37,99,235,.12);
            outline: none;
        }

        .line-conversion-hint {
            max-width: 100%;
            margin-top: .12rem;
            overflow: hidden;
            color: #2563eb;
            font-size: .61rem;
            line-height: 1.25;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .line-stock-hint,
        .line-stock-price-hint {
            max-width: 100%;
            margin-top: .12rem;
            overflow: hidden;
            color: #15803d;
            font-size: .61rem;
            line-height: 1.25;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .line-stock-price-hint {
            color: #64748b;
        }

        body[data-theme="dark"] .line-unit-hint {
            border-color: rgba(100,116,139,.42);
            background: rgba(30,41,59,.72);
        }

        body[data-theme="dark"] .line-stock-hint {
            color: #86efac;
        }

        body[data-theme="dark"] .line-stock-price-hint {
            color: #94a3b8;
        }

        .po-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: visible;
            margin-bottom: .85rem;
        }

        /* Focal card — Supplier + Tanggal Dokumen */
        .po-focal {
            border: 1px solid var(--line);
        }

        .po-focal-fields {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(180px, 240px);
            gap: 1rem;
            align-items: start;
        }

        .po-date-wrap {
            width: 100%;
            min-width: 0;
        }

        .po-date-wrap input {
            width: 100%;
        }

        .po-editor-actions {
            display: flex;
            justify-content: flex-end;
            gap: .55rem;
            align-items: center;
        }

        .po-editor-actions .btn {
            min-height: 40px;
        }

        /* Supplier select lebih menonjol */
        .po-supplier-select {
            font-size: .95rem;
            padding: .55rem .85rem;
        }

        .po-supplier-search-wrap {
            position: relative;
            flex: 1 1 auto;
            min-width: 0;
        }

        .po-supplier-search {
            min-height: 38px;
            padding-left: 2.2rem !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }

        .po-supplier-combo {
            display: flex;
            align-items: stretch;
            position: relative;
            overflow: visible;
            border: 1px solid rgba(148,163,184,.42);
            border-radius: 10px;
            background: var(--card, #fff);
            transition: border-color .12s, box-shadow .12s;
        }

        /* Dropdown supplier boleh melewati batas card tanpa terpotong section berikutnya. */
        .po-supplier-card {
            position: relative;
            z-index: 20;
            overflow: visible !important;
        }

        .po-supplier-combo:focus-within {
            border-color: rgba(59,130,246,.75);
            box-shadow: 0 0 0 .2rem rgba(59,130,246,.12);
        }

        .po-supplier-combo.is-invalid {
            border-color: var(--bs-danger, #dc3545);
        }

        .po-supplier-combo .po-supplier-select {
            flex: 0 0 50px;
            width: 50px;
            min-height: 38px;
            padding: .35rem 1.25rem .35rem .35rem;
            border: 0;
            border-left: 1px solid rgba(148,163,184,.25);
            border-radius: 0;
            font-size: 0;
            cursor: pointer;
        }

        .po-supplier-combo .po-supplier-select option {
            font-size: .9rem;
        }

        .po-supplier-suggest-dropdown {
            position: absolute;
            top: calc(100% + 5px);
            left: 0;
            right: 0;
            z-index: 10050;
            display: none;
            max-height: 240px;
            overflow-y: auto;
            padding: .25rem;
            border: 1px solid rgba(148,163,184,.35);
            border-radius: 10px;
            background: var(--card, #fff);
            box-shadow: 0 10px 24px rgba(15,23,42,.14);
        }

        .po-supplier-suggest-option {
            display: block;
            width: 100%;
            padding: .5rem .6rem;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: #334155;
            text-align: left;
            cursor: pointer;
        }

        .po-supplier-suggest-option:hover,
        .po-supplier-suggest-option:focus {
            outline: none;
            background: rgba(59,130,246,.1);
        }

        .po-supplier-suggest-code {
            font-size: .78rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .po-supplier-suggest-name {
            margin-top: .1rem;
            color: #64748b;
            font-size: .72rem;
            line-height: 1.25;
        }

        .po-supplier-suggest-empty {
            padding: .65rem;
            color: #94a3b8;
            font-size: .76rem;
        }

        body[data-theme="dark"] .po-supplier-suggest-dropdown {
            background: #1e293b;
            border-color: rgba(51,65,85,.8);
        }

        body[data-theme="dark"] .po-supplier-suggest-option {
            color: #e2e8f0;
        }

        body[data-theme="dark"] .po-supplier-suggest-name {
            color: #94a3b8;
        }

        .po-supplier-search-icon {
            position: absolute;
            z-index: 2;
            top: 50%;
            left: .8rem;
            color: #94a3b8;
            pointer-events: none;
            transform: translateY(-50%);
        }

        .po-supplier-search-hint {
            margin-top: .3rem;
            color: #94a3b8;
            font-size: .7rem;
        }

        .po-td-item .js-item-suggest-input,
        .po-td-qty .po-field,
        .po-td-price .po-field {
            min-height: 38px;
            padding: .375rem .75rem;
            border-radius: 10px;
            font-size: 1rem;
            line-height: 1.5;
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

        /* Metadata akun tetap ringkas; input angka selalu mulai dari garis yang sama. */
        .po-lines-table tbody td {
            vertical-align: top;
        }

        .po-td-qty,
        .po-td-price,
        .po-td-total,
        .po-td-action {
            vertical-align: top !important;
        }

        .line-accounting-badge {
            display: inline-flex !important;
            align-items: center;
            gap: .25rem;
            min-height: 16px;
            margin-top: .2rem !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            color: #94a3b8 !important;
            box-shadow: none !important;
            font-size: .62rem !important;
            font-weight: 600 !important;
            line-height: 1.1;
        }

        .line-accounting-badge i,
        .line-accounting-badge span {
            color: inherit !important;
            -webkit-text-fill-color: currentColor !important;
        }

        .line-accounting-badge i {
            font-size: .62rem;
        }

        .line-expacc-text {
            margin-top: .2rem !important;
            max-width: 100%;
            color: #94a3b8 !important;
            font-size: .62rem !important;
            font-weight: 500;
            line-height: 1.15;
        }

        .line-expacc-text > span {
            display: flex;
            align-items: center;
            gap: .2rem;
            min-width: 0;
            white-space: nowrap;
        }

        .line-expacc-text .expacc-label-text {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #64748b !important;
            font-weight: 600 !important;
        }

        .line-expacc-text > span,
        .line-expacc-text .btn-edit-expacc {
            color: #94a3b8 !important;
        }

        .line-expacc-text .btn-edit-expacc {
            opacity: .75;
        }

        .line-expacc-hint {
            display: flex;
            align-items: center;
            gap: .2rem;
            margin-bottom: .25rem !important;
            color: #a16207 !important;
            font-size: .62rem !important;
            font-weight: 600 !important;
            line-height: 1.1;
        }

        .line-expacc-wrapper {
            margin-top: .35rem !important;
        }

        .line-expacc-wrapper > div:first-child {
            display: flex;
            align-items: center;
            gap: .2rem;
            margin-bottom: .25rem !important;
            line-height: 1.1;
        }

        .line-expacc-raw {
            min-height: 32px;
        }

        .po-lines-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .75rem 1rem;
            border-top: 1px solid var(--line);
            background: color-mix(in srgb, var(--card) 96%, var(--bg) 4%);
        }

        .po-lines-head {
            display: flex;
            align-items: flex-start !important;
            justify-content: space-between;
            gap: 1rem;
        }

        .po-lines-head > div:first-child {
            min-width: 0;
        }

        .po-lines-head-actions {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-shrink: 0;
        }

        .po-lines-head-actions .btn {
            min-height: 36px;
        }

        .po-add-label-short {
            display: none;
        }

        .po-add-line-btn {
            background: #2563eb !important;
            border-color: #2563eb !important;
            border-radius: 8px !important;
            color: #fff !important;
            -webkit-text-fill-color: #fff;
        }

        .po-add-line-btn,
        .po-add-line-btn i,
        .po-add-line-btn span {
            color: #fff !important;
            -webkit-text-fill-color: #fff !important;
        }

        .po-add-line-btn:hover,
        .po-add-line-btn:focus-visible {
            background: #1d4ed8 !important;
            border-color: #1d4ed8 !important;
            color: #fff !important;
            -webkit-text-fill-color: #fff;
        }

        .po-submit-btn {
            background: #2563eb !important;
            border-color: #2563eb !important;
            border-radius: 8px !important;
            color: #fff !important;
            -webkit-text-fill-color: #fff;
        }

        .po-submit-btn,
        .po-submit-btn i,
        .po-submit-btn span {
            color: #fff !important;
            -webkit-text-fill-color: #fff !important;
        }

        .po-submit-btn:hover,
        .po-submit-btn:focus-visible {
            background: #1d4ed8 !important;
            border-color: #1d4ed8 !important;
            color: #fff !important;
            -webkit-text-fill-color: #fff;
        }

        .po-cancel-btn {
            border-radius: 8px !important;
        }

        .po-reset-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            min-height: 36px;
            white-space: nowrap;
        }

        .po-lines-summary {
            color: var(--muted);
            font-size: .8rem;
            font-weight: 700;
            white-space: nowrap;
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
                grid-template-areas: "header header" "item item" "qty unit" "stock stock" "price total" "action action";
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

            .po-td-unit {
                grid-area: unit;
            }

            .po-td-stock {
                grid-area: stock;
            }

            .po-td-price {
                grid-area: price
            }

            .po-lines-no-money tbody tr {
                grid-template-areas: "header header" "item item" "qty unit" "stock stock" "action action";
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
            .po-focal-fields {
                grid-template-columns: 1fr;
                gap: .75rem;
            }
            .po-date-wrap input {
                min-width: 0 !important;
                width: 100%;
                font-size: .8rem !important;
                padding: .3rem .4rem;
                min-height: 34px;
            }
            .po-supplier-select {
                min-height: 42px;
                font-size: 16px !important;
            }
            /* Detail Barang: semua input memakai rhythm yang sama */
            .po-td-item .js-item-suggest-input,
            .po-td-qty .po-field,
            .po-td-price .po-field {
                min-height: 36px;
                font-size: 13px !important;
                padding: .28rem .4rem;
                border-radius: 10px;
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
            #btn-reset-empty-lines { display: inline-flex; }
            #btn-add-line { display: inline-flex; }
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
            .po-total-line:not(:last-child) { display: flex !important; }
            .po-total-line:nth-child(2) { display: none !important; }
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
            /* ── Input utama satu baris: kode | qty | harga | hapus ── */
            .po-lines-table tbody tr {
                display: grid;
                grid-template-columns: minmax(0, 1.45fr) minmax(54px, .7fr) minmax(72px, 1fr) 32px;
                grid-template-rows: auto;
                align-items: start;
                column-gap: .4rem;
                row-gap: 0;
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
            .po-td-item   { grid-column: 1; grid-row: 1; min-width: 0; }
            .po-td-action { grid-column: 4; grid-row: 1; position: static !important; display: flex !important; align-items: center; justify-content: flex-end; padding: 0 !important; }
            .po-td-action .btn { min-height: 32px; min-width: 32px; padding: .1rem .4rem; border-radius: 999px !important; font-weight: 800; font-size: .82rem; }
            .po-td-qty    { grid-column: 2; grid-row: 1; display: block !important; width: auto !important; margin: 0 !important; }
            .po-td-unit   { grid-column: 2; grid-row: 2; display: block !important; min-width: 0; margin-top: .28rem; }
            .po-td-stock  { grid-column: 1 / 4; grid-row: 3; display: block !important; min-width: 0; padding-top: .18rem !important; }
            .po-td-price  { grid-column: 3; grid-row: 1; display: block !important; width: auto !important; margin: 0 !important; }
            .po-td-total  { display: none !important; }
            .po-lines-no-money .po-td-qty { grid-column: 2; }
            .po-lines-no-money .po-td-action { grid-column: 3; }
            .po-lines-no-money .po-td-stock { grid-column: 1 / 3; }
            .po-lines-table tbody td[data-label]::before { display: none !important; }
            .po-num-display { text-align: left; font-weight: 800; }

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

        @media (max-width: 767.98px) {
            .po-lines-head {
                flex-wrap: wrap;
                gap: .55rem;
                align-items: center !important;
                padding: .6rem .7rem .5rem !important;
            }

            .po-lines-head-actions {
                margin-left: auto;
            }

            .po-lines-summary {
                display: flex;
                align-items: center;
                font-size: .74rem;
            }

            .po-lines-head-actions #btn-reset-empty-lines {
                min-height: 38px;
                white-space: nowrap;
            }

            .po-reset-btn {
                min-height: 34px;
                padding: .3rem .55rem;
                border-radius: 8px !important;
                font-size: .7rem;
            }

            .po-lines-footer {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: .45rem;
                padding: .55rem .65rem;
            }

            .po-lines-footer #btn-add-line {
                width: auto;
                min-height: 36px;
                padding: .4rem .75rem;
                border-radius: 8px !important;
                font-size: .78rem;
            }

            .po-add-label-full {
                display: none;
            }

            .po-add-label-short {
                display: inline;
            }

            .po-meta-card {
                display: block !important;
            }

            .po-meta-inputs {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                gap: .55rem;
                margin-bottom: .7rem !important;
            }

            .po-meta-inputs > div:first-child {
                display: none !important;
            }

            .po-meta-inputs > div:nth-child(2),
            .po-meta-inputs .po-meta-ongkir {
                min-width: 0;
                width: auto;
            }

            .po-meta-card .po-label {
                display: block !important;
                font-size: .62rem;
                margin-bottom: .2rem;
            }

            .po-meta-totals {
                border-top: 1px solid var(--line) !important;
                padding-top: .55rem !important;
                text-align: right;
            }

            .po-total-line:not(:last-child) {
                display: flex !important;
            }

            .po-total-line:nth-child(2) {
                display: none !important;
            }

            .po-total-line:last-child {
                display: flex !important;
                flex-direction: row;
                align-items: baseline;
                justify-content: space-between;
                border-top: 0;
                padding: 0;
            }

            .po-total-key {
                font-size: .62rem;
            }

            .po-total-val {
                font-size: 1rem;
            }

            .po-editor-actions {
                position: sticky;
                bottom: 0;
                z-index: 1000;
                margin-inline: -.5rem;
                padding: .65rem .75rem;
                display: grid;
                grid-template-columns: minmax(0, .85fr) minmax(0, 1.35fr);
                gap: .5rem;
                background: color-mix(in srgb, var(--card) 94%, transparent);
                border-top: 1px solid var(--line);
                box-shadow: 0 -8px 20px rgba(15, 23, 42, .08);
                backdrop-filter: blur(12px);
            }

            .po-editor-actions .btn {
                width: 100%;
                min-height: 42px;
                margin: 0 !important;
                padding-inline: .7rem;
                font-size: .82rem;
                border-radius: 8px !important;
            }

            .po-editor-actions .po-cancel-btn { order: 1; }
            .po-editor-actions .po-submit-btn { order: 2; }

            .shp-wrap {
                padding-inline: .5rem;
                padding-bottom: 8rem;
            }

            .shp-table-card {
                margin-top: .5rem;
                border-radius: 12px;
                box-shadow: 0 2px 10px rgba(15, 23, 42, .05);
            }

            .shp-table-head {
                padding: .7rem .75rem .6rem;
            }

            .po-lines-head > div:first-child > .text-muted,
            .po-focal-fields > div:first-child > .text-muted {
                display: none !important;
            }

            .po-focal-fields {
                gap: .6rem;
            }

            .po-focal-fields > div[style] {
                min-width: 0 !important;
            }

            .po-supplier-search,
            .po-date-wrap input {
                min-height: 42px !important;
                font-size: 14px !important;
            }

            .po-supplier-combo .po-supplier-select {
                min-height: 42px !important;
                font-size: 0 !important;
            }

            .po-lines-table tbody tr {
                margin-bottom: .45rem;
                padding: .6rem !important;
                box-shadow: 0 2px 8px rgba(15, 23, 42, .05);
            }

            .po-td-item .js-item-suggest-input,
            .po-td-qty .po-field,
            .po-td-price .po-field {
                min-height: 40px !important;
                font-size: 14px !important;
                padding: .35rem .5rem;
                border-radius: 10px;
            }

            .line-accounting-badge {
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                font-size: .6rem !important;
            }

            .line-unit-hint {
                font-size: .6rem;
                padding-inline: .32rem;
            }

            .line-unit-select {
                min-height: 40px;
                padding: .35rem 1.35rem .35rem .45rem;
                border-radius: 10px;
                font-size: 12px;
            }

            .line-conversion-hint {
                font-size: .57rem;
            }

            .line-expacc-text,
            .line-expacc-text > span {
                font-size: .64rem !important;
            }

            .line-expacc-wrapper > div:first-child {
                font-size: .64rem !important;
            }

            .line-expacc-raw {
                min-height: 40px !important;
                font-size: 14px !important;
            }

            .po-lines-footer .po-add-line-btn {
                flex: 0 0 auto;
                min-height: 36px;
                padding-inline: .65rem;
            }

            .po-lines-summary {
                justify-content: center;
                margin-left: auto;
                font-size: .7rem;
            }

            .po-lines-summary .po-total-live {
                font-size: .82rem !important;
            }

            .po-meta-inputs select,
            .po-meta-inputs input {
                min-height: 40px;
                font-size: 14px !important;
            }

            .po-editor-actions {
                padding-bottom: calc(.65rem + env(safe-area-inset-bottom));
            }

            /* Mobile: kode, qty, dan harga tetap satu baris dengan konteks yang jelas. */
            .po-lines-table tbody td[data-label]::before {
                display: block !important;
                margin-bottom: .18rem;
                color: var(--muted);
                font-size: .56rem;
                font-weight: 800;
                letter-spacing: .07em;
                line-height: 1;
                text-transform: uppercase;
            }

            .po-td-item::before { content: "Kode Barang" !important; }
            .po-td-qty::before { content: "Qty" !important; }
            .po-td-price::before { content: "Harga" !important; }

            .po-td-action {
                align-self: start;
                align-items: flex-start !important;
                padding-top: .74rem !important;
            }

            .po-td-action .btn-remove-line {
                width: 36px;
                height: 36px;
                min-width: 36px;
                min-height: 36px;
                padding: 0 !important;
                border-radius: 8px !important;
                font-size: 1rem;
            }

            .po-td-item,
            .po-td-qty,
            .po-td-price {
                min-width: 0 !important;
            }

            .po-td-item .item-suggest-wrap,
            .po-td-item .js-item-suggest-input,
            .po-td-qty .po-field,
            .po-td-price .po-field {
                width: 100%;
                min-width: 0;
            }

            .po-td-item .js-item-suggest-input,
            .po-td-qty .po-field,
            .po-td-price .po-field {
                min-height: 40px !important;
                padding: .35rem .5rem;
                border-radius: 10px;
            }

            .po-meta-card {
                padding: .65rem;
            }

            .po-meta-inputs {
                gap: .45rem;
                margin-bottom: .55rem !important;
            }
        }

        @media (max-width: 380px) {
            .po-lines-table tbody tr {
                grid-template-columns: minmax(0, 1.2fr) minmax(48px, .65fr) minmax(64px, .9fr) 30px;
                column-gap: .28rem;
                padding: .45rem;
            }

            .po-td-item .js-item-suggest-input,
            .po-td-qty .po-field,
            .po-td-price .po-field {
                font-size: 13px !important;
            }

            .po-td-action .btn {
                min-width: 34px;
                width: 34px;
                height: 34px;
                min-height: 34px;
            }
        }
    </style>
@endpush

{{-- FOCAL: SUPPLIER + TANGGAL DOKUMEN --}}
<div class="shp-table-card po-supplier-card mb-3" data-order-type="{{ $orderType }}">
    <div class="shp-table-head">
        <div class="shp-table-title">Informasi PO</div>
    </div>
    <div style="padding:1.1rem 1.25rem .9rem;">

        <div class="po-focal-fields">
            <div>
                <label for="po-supplier" class="po-label mb-1">Supplier</label>
                <div class="po-supplier-combo @error('supplier_id') is-invalid @enderror">
                    <div class="po-supplier-search-wrap">
                        <i class="bi bi-search po-supplier-search-icon" aria-hidden="true"></i>
                        <input type="search" id="po-supplier-search"
                            class="form-control po-field po-supplier-search"
                            placeholder="Ketik kode atau nama supplier"
                            value="{{ $selectedSupplierLabel }}"
                            autocomplete="off"
                            spellcheck="false"
                            aria-label="Cari supplier">
                        <div id="po-supplier-suggest-dropdown" class="po-supplier-suggest-dropdown" role="listbox">
                            @foreach ($suppliers as $sup)
                                @php $supplierLabel = trim(($sup->code ? $sup->code . ' — ' : '') . $sup->name); @endphp
                                <button type="button" class="po-supplier-suggest-option" role="option"
                                    data-id="{{ $sup->id }}"
                                    data-label="{{ $supplierLabel }}"
                                    data-code="{{ $sup->code }}"
                                    data-name="{{ $sup->name }}">
                                    <div class="po-supplier-suggest-code">{{ $sup->code ?: 'Supplier' }}</div>
                                    <div class="po-supplier-suggest-name">{{ $sup->name }}</div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <select name="supplier_id" id="po-supplier"
                        class="form-select po-field po-supplier-select @error('supplier_id') is-invalid @enderror"
                        required
                        title="Buka daftar supplier"
                        aria-label="Pilih supplier dari daftar">
                        <option value="">— Pilih Supplier —</option>
                        @foreach ($suppliers as $sup)
                            <option value="{{ $sup->id }}"
                                @selected((string) $selectedSupplierId === (string) $sup->id)>
                                {{ $sup->code }} — {{ $sup->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @error('supplier_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="po-supplier-search-hint">Ketik untuk mencari atau buka daftar supplier di sisi kanan.</div>
                @if (auth()->user()?->role === 'owner')
                    <div class="text-muted mt-1" style="font-size: .75rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        List supplier diambil dari <a href="{{ route('master.suppliers.index') }}" target="_blank" class="text-decoration-none fw-bold">Master Supplier</a>.
                    </div>
                @endif
            </div>

            <div class="po-date-wrap">
                <label for="po-document-date" class="po-label mb-1">Tanggal Dokumen</label>
                <input type="text" id="po-document-date" name="date" value="{{ $orderDate }}"
                    class="form-control po-field gf-date-input @error('date') is-invalid @enderror"
                    data-gf-date autocomplete="off">
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- hidden input order_type --}}
        <input type="hidden" name="order_type" id="po-order-type-hidden" value="{{ $orderType }}">
    </div>
</div>

{{-- LINES --}}
<div class="shp-table-card mb-3">
    <div class="shp-table-head po-lines-head">
        <div>
            <div class="shp-table-title">Detail Barang</div>
            <span class="text-muted" style="font-size: .75rem;">Isi item, qty, dan harga yang ingin dibeli.</span>
        </div>
        <div class="po-lines-head-actions">
            <button type="button" id="btn-reset-empty-lines" class="btn-shp-outline po-reset-btn"
                title="Hapus baris item yang masih kosong">
                <i class="bi bi-eraser"></i>
                <span>Reset Kosong</span>
            </button>
        </div>
    </div>

    <div class="table-responsive po-table-wrapper">
        <table class="table shp-table mb-0 po-lines-table {{ $canSeeMoney ? 'po-lines-has-money' : 'po-lines-no-money' }}" id="po-lines-table">
            <thead>
                <tr>
                    <th class="po-col-no text-center">No</th>
                    <th style="width:34%">Item</th>
                    <th class="text-end" style="width:10%">Qty</th>
                    <th style="width:13%">Satuan Beli</th>
                    <th style="width:16%">Konversi Stok</th>
                    @if ($canSeeMoney)
                        <th class="text-end" style="width:15%">Harga</th>
                        <th class="text-end" style="width:11%">Total</th>
                    @endif
                    <th style="width:4%"></th>
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

                        $purchaseUnit = $line['purchase_unit'] ?? ($line['item']['purchase_unit'] ?? ($line['item']['unit'] ?? 'pcs'));
                        $stockUnit = $line['stock_unit'] ?? ($line['item']['stock_unit'] ?? ($line['item']['unit'] ?? 'pcs'));
                        $conversionFactor = (float) ($line['conversion_factor'] ?? ($line['item']['purchase_conversion_factor'] ?? 1));
                        $conversionFactor = $conversionFactor > 0 ? $conversionFactor : 1;

                        // Pilihan satuan berasal dari master item atau satuan stok.
                        // Snapshot lama tetap ditampilkan agar edit PO tidak mengubah histori
                        // hanya karena konfigurasi master item berubah.
                        $masterPurchaseUnit = trim((string) ($line['item']['purchase_unit'] ?? ($line['item']['unit'] ?? 'pcs'))) ?: 'pcs';
                        $masterStockUnit = trim((string) ($line['item']['stock_unit'] ?? ($line['item']['unit'] ?? 'pcs'))) ?: 'pcs';
                        $masterConversionFactor = (float) ($line['item']['purchase_conversion_factor'] ?? 1);
                        $masterConversionFactor = $masterConversionFactor > 0 ? $masterConversionFactor : 1;
                        $unitOptions = [];
                        $pushUnitOption = function (string $unit, string $stock, float $factor) use (&$unitOptions): void {
                            $unit = trim($unit) ?: 'pcs';
                            $stock = trim($stock) ?: $unit;
                            $factor = $factor > 0 ? $factor : 1;
                            foreach ($unitOptions as $option) {
                                if (strcasecmp($option['unit'], $unit) === 0) {
                                    return;
                                }
                            }
                            $unitOptions[] = ['unit' => $unit, 'stock' => $stock, 'factor' => $factor];
                        };
                        $pushUnitOption((string) $purchaseUnit, (string) $stockUnit, $conversionFactor);
                        $pushUnitOption($masterPurchaseUnit, $masterStockUnit, $masterConversionFactor);
                        $pushUnitOption($masterStockUnit, $masterStockUnit, 1);

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
                        $selectedAcc = collect($expenseAccounts)->firstWhere('id', $expAcc);
                        $allocationLabel = $alloc === 'expense'
                            ? 'Biaya' . ($selectedAcc ? ' · ' . $selectedAcc->code : '')
                            : 'HPP';
                    @endphp

                    <tr class="po-row">
                        <td class="text-center align-middle line-index po-col-no">{{ $loop->iteration }}</td>

                        <td class="po-td-item" data-label="Item">
                            <x-item-suggest :items="$items" idName="lines[{{ $i }}][item_id]"
                                :idValue="$lineItemId" :displayValue="$itemDisplay" :type="$itemSuggestType" :extraParams="$itemSuggestExtra" variant="mini"
                                displayMode="code-name" mobileDisplayMode="code"
                                placeholder="Masukan kode barang" mobilePlaceholder="Kode barang"
                                :minChars="1" />
                            @error("lines.$i.item_id")
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror

                            <div class="line-accounting-badge mt-1" data-line-accounting-badge
                                 style="display:inline-flex;align-items:center;gap:.25rem;padding:.12rem .42rem;border-radius:999px;font-size:.64rem;font-weight:700;background:{{ $alloc === 'expense' ? '#fff7ed' : '#eff6ff' }};color:{{ $alloc === 'expense' ? '#c2410c' : '#1d4ed8' }};border:1px solid {{ $alloc === 'expense' ? '#fed7aa' : '#bfdbfe' }};">
                                <i class="bi {{ $alloc === 'expense' ? 'bi-receipt' : 'bi-box-seam' }}"></i>
                                <span data-line-accounting-text>{{ $allocationLabel }}</span>
                            </div>

                            <input type="hidden" name="lines[{{ $i }}][allocation]" class="line-alloc-raw" value="{{ $alloc }}">
                            <input type="hidden" name="lines[{{ $i }}][purchase_unit]" class="line-purchase-unit-raw" value="{{ $purchaseUnit }}">
                            <input type="hidden" name="lines[{{ $i }}][stock_unit]" class="line-stock-unit-raw" value="{{ $stockUnit }}">
                            <input type="hidden" name="lines[{{ $i }}][conversion_factor]" class="line-conversion-raw" value="{{ $conversionFactor }}">
                            <div class="line-expacc-text mt-1" style="{{ $expAcc ? '' : 'display:none;' }}">
                                @php
                                    $selectedAcc = collect($expenseAccounts)->firstWhere('id', $expAcc);
                                    $accLabel = $selectedAcc ? ($selectedAcc->code . ' - ' . $selectedAcc->name) : '';
                                    
                                    $lineId = $line['id'] ?? null;
                                    $lineRcv = (float) ($receivedByLineId[$lineId] ?? 0);
                                    $isReceived = $lineRcv > 0.0001;
                                @endphp
                                <span class="text-muted" style="font-size: .68rem;">Biaya: <span class="expacc-label-text fw-semibold">{{ $accLabel }}</span>
                                    @if($isReceived)
                                        <span class="ms-1 text-warning" style="font-size: .65rem; font-style: italic;">(Ubah via GRN)</span>
                                    @else
                                        <a href="javascript:void(0)" class="btn-edit-expacc ms-1 text-decoration-none" title="Ubah Akun Biaya"><i class="bi bi-pencil-fill"></i></a>
                                    @endif
                                </span>
                            </div>
                            <div class="line-expacc-wrapper mt-2" style="{{ ($alloc === 'expense' && !$expAcc) ? '' : 'display:none;' }}">
                                <div class="line-expacc-hint">
                                    <i class="bi bi-exclamation-circle"></i> Item Biaya - Pilih Akun
                                </div>
                                <select name="lines[{{ $i }}][expense_account_id]" class="form-select form-select-sm line-expacc-raw" style="border-color: #fcd34d;">
                                    <option value="">— Pilih Akun Biaya —</option>
                                    @foreach ($expenseAccounts as $acc)
                                        <option value="{{ $acc->id }}" data-text="{{ $acc->code }} - {{ $acc->name }}" @selected((string)$expAcc === (string)$acc->id)>
                                            {{ $acc->code }} - {{ $acc->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
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

                        <td data-label="Satuan Beli" class="po-td-unit">
                            <select class="line-unit-select" data-line-unit-select aria-label="Satuan beli">
                                @foreach ($unitOptions as $unitOption)
                                    <option value="{{ $unitOption['unit'] }}"
                                        data-purchase-unit="{{ $unitOption['unit'] }}"
                                        data-stock-unit="{{ $unitOption['stock'] }}"
                                        data-conversion-factor="{{ $unitOption['factor'] }}"
                                        @selected(strcasecmp($unitOption['unit'], (string) $purchaseUnit) === 0)>
                                        {{ $unitOption['unit'] }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        <td data-label="Konversi Stok" class="po-td-stock">
                            <div class="line-conversion-hint" data-line-conversion-hint>{{ decimal_id($conversionFactor, 6) }} {{ $stockUnit }}</div>
                            <div class="line-stock-hint" data-line-stock-hint></div>
                        </td>

                        @if ($canSeeMoney)
                            <td data-label="Harga" class="po-td-price">
                                <input type="text" class="form-control po-field po-num-display line-price-display"
                                    inputmode="numeric" placeholder="Harga / {{ $purchaseUnit }}" value="{{ $priceDisplay }}" autocomplete="off">
                                <input type="hidden" name="lines[{{ $i }}][unit_price]" class="line-price-raw"
                                    value="{{ $priceRaw }}">
                                <div class="line-stock-price-hint" data-line-stock-price-hint></div>
                                @error("lines.$i.unit_price")
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </td>

                            <td class="text-end align-middle line-total po-td-total mono" data-label="Nilai"></td>
                        @endif

                        <td class="text-center po-td-action">
                            <button type="button" class="btn-remove-line" title="Hapus Baris">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr class="po-row">
                        <td class="text-center align-middle line-index po-col-no">1</td>

                        <td class="po-td-item" data-label="Item">
                            <x-item-suggest idName="lines[0][item_id]" :items="$items" displayMode="code-name" mobileDisplayMode="code"
                                :showName="false" :showCategory="false" :type="$itemSuggestType" :extraParams="$itemSuggestExtra"
                                placeholder="Masukan kode barang" mobilePlaceholder="Kode barang" />
                            <div class="line-accounting-badge mt-1" data-line-accounting-badge
                                 style="display:inline-flex;align-items:center;gap:.25rem;padding:.12rem .42rem;border-radius:999px;font-size:.64rem;font-weight:700;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">
                                <i class="bi bi-box-seam"></i><span data-line-accounting-text>HPP</span>
                            </div>
                            <input type="hidden" name="lines[0][allocation]" class="line-alloc-raw" value="hpp">
                            <input type="hidden" name="lines[0][purchase_unit]" class="line-purchase-unit-raw" value="pcs">
                            <input type="hidden" name="lines[0][stock_unit]" class="line-stock-unit-raw" value="pcs">
                            <input type="hidden" name="lines[0][conversion_factor]" class="line-conversion-raw" value="1">
                            <div class="line-expacc-text mt-1" style="display:none;">
                                <span class="text-muted" style="font-size: .68rem;">Biaya: <span class="expacc-label-text fw-semibold"></span>
                                    <a href="javascript:void(0)" class="btn-edit-expacc ms-1 text-decoration-none" title="Ubah Akun Biaya"><i class="bi bi-pencil-fill"></i></a>
                                </span>
                            </div>
                            <div class="line-expacc-wrapper mt-2" style="display:none;">
                                <div class="line-expacc-hint">
                                    <i class="bi bi-exclamation-circle"></i> Item Biaya - Pilih Akun
                                </div>
                                <select name="lines[0][expense_account_id]" class="form-select form-select-sm line-expacc-raw" style="border-color: #fcd34d;">
                                    <option value="">— Pilih Akun Biaya —</option>
                                    @foreach ($expenseAccounts as $acc)
                                        <option value="{{ $acc->id }}" data-text="{{ $acc->code }} - {{ $acc->name }}">
                                            {{ $acc->code }} - {{ $acc->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </td>

                        <td data-label="Qty Beli" class="po-td-qty">
                            <input type="text" class="form-control po-field po-num-display line-qty-display"
                                inputmode="decimal" placeholder="Qty" value="" autocomplete="off">
                            <input type="hidden" name="lines[0][qty]" class="line-qty-raw" value="">
                        </td>

                        <td data-label="Satuan Beli" class="po-td-unit">
                            <select class="line-unit-select" data-line-unit-select aria-label="Satuan beli">
                                <option value="pcs" data-purchase-unit="pcs" data-stock-unit="pcs" data-conversion-factor="1">
                                    pcs
                                </option>
                            </select>
                        </td>

                        <td data-label="Konversi Stok" class="po-td-stock">
                            <div class="line-conversion-hint" data-line-conversion-hint>1 pcs</div>
                            <div class="line-stock-hint" data-line-stock-hint></div>
                        </td>

                        @if ($canSeeMoney)
                            <td data-label="Harga" class="po-td-price">
                                <input type="text" class="form-control po-field po-num-display line-price-display"
                                    inputmode="numeric" placeholder="Harga / pcs" value="" autocomplete="off">
                                <input type="hidden" name="lines[0][unit_price]" class="line-price-raw" value="">
                                <div class="line-stock-price-hint" data-line-stock-price-hint></div>
                            </td>

                            <td class="text-end align-middle line-total po-td-total mono" data-label="Nilai"></td>
                        @endif

                        <td class="text-center po-td-action">
                            <button type="button" class="btn-remove-line" title="Hapus Baris">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>

    <div class="po-lines-footer">
        <button type="button" id="btn-add-line" class="btn-shp-submit po-add-line-btn" title="Tambah barang baru">
            <i class="bi bi-plus-lg me-1"></i>
            <span class="po-add-label-full">Tambah Barang</span>
            <span class="po-add-label-short">Tambah</span>
        </button>
        <div class="po-lines-summary">
            <span class="mono po-total-live" id="po-live-lines" style="color:var(--primary); font-size:1.05rem;">0</span> item
            <span class="mx-2">·</span>
            <span class="mono po-total-live" id="po-live-qty" style="color:var(--primary); font-size:1.05rem;">0</span> qty beli
        </div>
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
                        class="form-select po-field @error('payment_method_id') is-invalid @enderror" required>
                        <option value="">— Pilih Pembayaran —</option>
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
                    <div class="small text-muted mt-1">Jika memilih Transfer, bank dipilih saat pembayaran.</div>
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
                        <div class="po-total-val subtle" id="po-subtotal-meta">{{ number_format($initialSubtotal, 0, ',', '.') }}</div>
                    </div>
                    <div class="po-total-line">
                        <div class="po-total-key">Ongkir</div>
                        <div class="po-total-val subtle" id="po-shipping-meta">{{ number_format($initialShipping, 0, ',', '.') }}</div>
                    </div>
                    <div class="po-total-line">
                        <div class="po-total-key">Grand Total</div>
                        <div class="po-total-val" id="po-grand-meta">{{ number_format($initialGrandTotal, 0, ',', '.') }}</div>
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

            const subtotalMeta = document.getElementById('po-subtotal-meta');
            const subtotalCell = document.getElementById('po-subtotal-cell') || subtotalMeta;
            const shippingMeta = document.getElementById('po-shipping-meta');
            const grandMeta = document.getElementById('po-grand-meta');
            const liveLines = document.getElementById('po-live-lines');
            const liveQty = document.getElementById('po-live-qty');

            const canSeeMoney = @json($canSeeMoney);

            const supplierSelect = document.querySelector('#po-supplier');
            const supplierSearch = document.querySelector('#po-supplier-search');
            const supplierDropdown = document.querySelector('#po-supplier-suggest-dropdown');
            const shippingDisplay = document.querySelector('.shipping-display');
            const shippingRaw = document.querySelector('.shipping-raw');

            const supplierSuggestOptions = supplierDropdown
                ? Array.from(supplierDropdown.querySelectorAll('.po-supplier-suggest-option'))
                : [];

            function supplierOptionLabel(option) {
                return (option?.textContent || '').replace(/\s+/g, ' ').trim();
            }

            function syncSupplierSearchFromSelect() {
                if (!supplierSelect || !supplierSearch) return;
                supplierSearch.value = supplierOptionLabel(supplierSelect.selectedOptions?.[0]);
            }

            function selectSupplierFromSearch() {
                if (!supplierSelect || !supplierSearch) return;

                const typed = supplierSearch.value.trim().toLowerCase();
                const options = Array.from(supplierSelect.options).filter(option => option.value);
                const exact = options.find(option => supplierOptionLabel(option).toLowerCase() === typed);

                if (exact) {
                    if (supplierSelect.value !== exact.value) {
                        supplierSelect.value = exact.value;
                        supplierSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    return;
                }

                // Jangan kirim supplier lama ketika user sudah mengganti teks pencarian.
                if (!typed || !options.some(option => supplierOptionLabel(option).toLowerCase() === typed)) {
                    supplierSelect.value = '';
                }
            }

            function hideSupplierSuggestions() {
                if (supplierDropdown) supplierDropdown.style.display = 'none';
            }

            function renderSupplierSuggestions(query = '') {
                if (!supplierDropdown) return;

                const needle = query.trim().toLowerCase();
                const matches = supplierSuggestOptions.filter(option => {
                    const haystack = [
                        option.dataset.code || '',
                        option.dataset.name || '',
                        option.dataset.label || '',
                    ].join(' ').toLowerCase();
                    return !needle || haystack.includes(needle);
                });

                supplierSuggestOptions.forEach(option => {
                    option.style.display = matches.includes(option) ? '' : 'none';
                });

                let empty = supplierDropdown.querySelector('.po-supplier-suggest-empty');
                if (!matches.length) {
                    if (!empty) {
                        empty = document.createElement('div');
                        empty.className = 'po-supplier-suggest-empty';
                        empty.textContent = 'Supplier tidak ditemukan';
                        supplierDropdown.appendChild(empty);
                    }
                } else if (empty) {
                    empty.remove();
                }

                supplierDropdown.style.display = 'block';
            }

            function chooseSupplierSuggestion(option) {
                if (!supplierSelect || !supplierSearch || !option) return;

                supplierSelect.value = option.dataset.id || '';
                supplierSearch.value = option.dataset.label || '';
                supplierSelect.dispatchEvent(new Event('change', { bubbles: true }));
                hideSupplierSuggestions();
            }

            supplierSelect?.addEventListener('change', syncSupplierSearchFromSelect);
            supplierSearch?.addEventListener('input', function () {
                supplierSelect.value = '';
                renderSupplierSuggestions(this.value);
            });
            supplierSearch?.addEventListener('change', selectSupplierFromSearch);
            supplierSearch?.addEventListener('blur', function () {
                setTimeout(() => {
                    selectSupplierFromSearch();
                    hideSupplierSuggestions();
                }, 120);
            });
            supplierSearch?.addEventListener('focus', function () {
                requestAnimationFrame(() => {
                    this.select();
                    renderSupplierSuggestions('');
                });
            });
            supplierSearch?.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    selectSupplierFromSearch();
                    hideSupplierSuggestions();
                    if (supplierSelect?.value) supplierSearch.blur();
                }
            });
            supplierDropdown?.addEventListener('mousedown', function (event) {
                const option = event.target.closest('.po-supplier-suggest-option');
                if (!option) return;
                event.preventDefault();
                chooseSupplierSuggestion(option);
            });
            document.addEventListener('mousedown', function (event) {
                if (!supplierDropdown?.contains(event.target) && event.target !== supplierSearch) {
                    hideSupplierSuggestions();
                }
            });
            syncSupplierSearchFromSelect();

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
                const conversionRaw = tr.querySelector('.line-conversion-raw');
                const purchaseUnitRaw = tr.querySelector('.line-purchase-unit-raw');
                const stockUnitRaw = tr.querySelector('.line-stock-unit-raw');
                const stockHint = tr.querySelector('[data-line-stock-hint]');
                const stockPriceHint = tr.querySelector('[data-line-stock-price-hint]');

                const qty = parseFloat(qtyRaw?.value || '0') || 0;
                const price = parseFloat(priceRaw?.value || '0') || 0;
                const factorValue = parseFloat(conversionRaw?.value || '1') || 1;
                const factor = factorValue > 0 ? factorValue : 1;
                const stockUnit = stockUnitRaw?.value || 'pcs';
                const stockQty = qty * factor;
                const stockUnitPrice = price / factor;

                let total = stockQty * stockUnitPrice;
                if (total < 0) total = 0;

                if (totalCell) totalCell.textContent = fmtIntId(total);
                if (stockHint) {
                    stockHint.textContent = stockQty > 0.0001
                        ? `Stok: ${fmtQtyId(stockQty)} ${stockUnit}`
                        : '';
                }
                if (stockPriceHint) {
                    stockPriceHint.textContent = price > 0.0001
                        ? `Harga stok: Rp ${fmtIntId(stockUnitPrice)} / ${stockUnit}`
                        : '';
                }
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
                const params = new URLSearchParams({
                    item_id: String(itemId)
                });
                if (supplierId) params.set('supplier_id', String(supplierId));
                const url = `{{ route('purchasing.supplier_price') }}?${params.toString()}`;
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
                const supplierId = supplierSelect?.value || '';
                const itemId = tr.querySelector('.js-item-suggest-id')?.value;
                if (!itemId) return;

                const priceDisp = tr.querySelector('.line-price-display');
                const priceRaw = tr.querySelector('.line-price-raw');
                if (!priceDisp || !priceRaw) return;

                const userEdited = priceDisp.dataset.userEdited === '1';
                if (userEdited) return;
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

            function formatUnitFactor(value) {
                const n = Number(value);
                if (!Number.isFinite(n) || n <= 0) return '1';
                return new Intl.NumberFormat('id-ID', {
                    maximumFractionDigits: 6,
                }).format(n);
            }

            function setLineUnitSnapshot(tr, option) {
                if (!tr || !option) return;

                const purchaseUnit = option.dataset.purchaseUnit || option.value || 'pcs';
                const stockUnit = option.dataset.stockUnit || purchaseUnit;
                const conversionFactor = Number(option.dataset.conversionFactor || 1) > 0
                    ? Number(option.dataset.conversionFactor)
                    : 1;
                const purchaseUnitRaw = tr.querySelector('.line-purchase-unit-raw');
                const stockUnitRaw = tr.querySelector('.line-stock-unit-raw');
                const conversionRaw = tr.querySelector('.line-conversion-raw');
                const conversionHint = tr.querySelector('[data-line-conversion-hint]');
                const priceDisplay = tr.querySelector('.line-price-display');

                if (purchaseUnitRaw) purchaseUnitRaw.value = purchaseUnit;
                if (stockUnitRaw) stockUnitRaw.value = stockUnit;
                if (conversionRaw) conversionRaw.value = String(conversionFactor);
                if (conversionHint) {
                    conversionHint.textContent = `${formatUnitFactor(conversionFactor)} ${stockUnit}`;
                    conversionHint.style.display = '';
                }
                if (priceDisplay && priceDisplay.dataset.userEdited !== '1') {
                    priceDisplay.placeholder = `Harga / ${purchaseUnit}`;
                }
            }

            function setLineUnitOptions(tr, {
                purchaseUnit = 'pcs',
                stockUnit = 'pcs',
                conversionFactor = 1,
                preferredUnit = purchaseUnit,
            } = {}) {
                const select = tr?.querySelector('[data-line-unit-select]');
                if (!select) return;

                const options = [];
                const addOption = (unit, stock, factor) => {
                    unit = (unit || 'pcs').toString().trim() || 'pcs';
                    stock = (stock || unit).toString().trim() || unit;
                    factor = Number(factor) > 0 ? Number(factor) : 1;
                    if (options.some(option => option.unit.toLowerCase() === unit.toLowerCase())) return;
                    options.push({ unit, stock, factor });
                };

                addOption(purchaseUnit, stockUnit, conversionFactor);
                if (purchaseUnit.toLowerCase() !== stockUnit.toLowerCase()) {
                    addOption(stockUnit, stockUnit, 1);
                }

                select.innerHTML = options.map(option => `
                    <option value="${option.unit.replace(/"/g, '&quot;')}"
                        data-purchase-unit="${option.unit.replace(/"/g, '&quot;')}"
                        data-stock-unit="${option.stock.replace(/"/g, '&quot;')}"
                        data-conversion-factor="${option.factor}">
                        ${option.unit}
                    </option>
                `).join('');

                const preferred = options.find(option => option.unit.toLowerCase() === (preferredUnit || '').toString().toLowerCase())
                    || options[0];
                if (preferred) {
                    select.value = preferred.unit;
                    setLineUnitSnapshot(tr, select.selectedOptions[0]);
                }
            }

            async function applyItemMetaToRow(tr, {
                force = false
            } = {}) {
                const itemId = tr.querySelector('.js-item-suggest-id')?.value;
                if (!itemId) return;

                const allocRaw = tr.querySelector('.line-alloc-raw');
                const expAccRaw = tr.querySelector('.line-expacc-raw');
                const accountingBadge = tr.querySelector('[data-line-accounting-badge]');
                const accountingText = tr.querySelector('[data-line-accounting-text]');
                if (!allocRaw || !expAccRaw) return;

                // kalau nanti kamu bikin UI override manual, flag ini bisa dipakai.
                const userEdited = allocRaw.dataset.userEdited === '1';
                if (!force && userEdited) return;

                const meta = await fetchItemMeta(itemId);
                if (!meta) return;

                const purchaseUnit = meta.purchase_unit || meta.unit || 'pcs';
                const stockUnit = meta.stock_unit || meta.unit || 'pcs';
                const conversionFactor = Number(meta.purchase_conversion_factor || 1) > 0
                    ? Number(meta.purchase_conversion_factor)
                    : 1;
                setLineUnitOptions(tr, {
                    purchaseUnit,
                    stockUnit,
                    conversionFactor,
                    preferredUnit: purchaseUnit,
                });

                const alloc = (meta.default_allocation === 'expense') ? 'expense' : 'hpp';
                allocRaw.value = alloc;

                if (accountingBadge && accountingText) {
                    const accountCode = meta.default_expense_account_id
                        ? (expAccRaw.querySelector(`option[value="${meta.default_expense_account_id}"]`)?.dataset.text?.split(' - ')[0] || '')
                        : '';
                    accountingText.textContent = alloc === 'expense'
                        ? `Biaya${accountCode ? ' · ' + accountCode : ''}`
                        : 'HPP';
                    accountingBadge.style.background = alloc === 'expense' ? '#fff7ed' : '#eff6ff';
                    accountingBadge.style.color = alloc === 'expense' ? '#c2410c' : '#1d4ed8';
                    accountingBadge.style.borderColor = alloc === 'expense' ? '#fed7aa' : '#bfdbfe';
                    const icon = accountingBadge.querySelector('i');
                    if (icon) icon.className = `bi ${alloc === 'expense' ? 'bi-receipt' : 'bi-box-seam'}`;
                }

                const wrapper = tr.querySelector('.line-expacc-wrapper');
                const textWrap = tr.querySelector('.line-expacc-text');
                const textLabel = tr.querySelector('.expacc-label-text');

                if (force || !expAccRaw.value) {
                    expAccRaw.value = meta.default_expense_account_id ? String(meta.default_expense_account_id) : '';
                }

                if (meta.default_expense_account_id && meta.default_expense_account_id != '') {
                    if (wrapper) wrapper.style.display = 'none';
                    if (textWrap) textWrap.style.display = '';
                    if (textLabel) {
                        const opt = expAccRaw.querySelector(`option[value="${meta.default_expense_account_id}"]`);
                        textLabel.textContent = opt ? opt.dataset.text : 'Terpilih';
                    }
                } else {
                    if (alloc === 'expense') {
                        if (wrapper) wrapper.style.display = '';
                    } else {
                        if (wrapper) wrapper.style.display = 'none';
                    }
                    if (textWrap) textWrap.style.display = 'none';
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
                newRow.querySelector('.line-purchase-unit-raw')?.setAttribute('value', 'pcs');
                newRow.querySelector('.line-stock-unit-raw')?.setAttribute('value', 'pcs');
                if (newRow.querySelector('.line-purchase-unit-raw')) newRow.querySelector('.line-purchase-unit-raw').value = 'pcs';
                if (newRow.querySelector('.line-stock-unit-raw')) newRow.querySelector('.line-stock-unit-raw').value = 'pcs';
                if (newRow.querySelector('.line-conversion-raw')) newRow.querySelector('.line-conversion-raw').value = '1';
                const newUnitSelect = newRow.querySelector('[data-line-unit-select]');
                if (newUnitSelect) {
                    newUnitSelect.innerHTML = '<option value="pcs" data-purchase-unit="pcs" data-stock-unit="pcs" data-conversion-factor="1">pcs</option>';
                    newUnitSelect.value = 'pcs';
                    setLineUnitSnapshot(newRow, newUnitSelect.selectedOptions[0]);
                }
                if (newRow.querySelector('[data-line-conversion-hint]')) {
                    newRow.querySelector('[data-line-conversion-hint]').textContent = '1 pcs';
                    newRow.querySelector('[data-line-conversion-hint]').style.display = '';
                }
                if (newRow.querySelector('[data-line-stock-hint]')) {
                    newRow.querySelector('[data-line-stock-hint]').textContent = '';
                }
                if (newRow.querySelector('[data-line-stock-price-hint]')) {
                    newRow.querySelector('[data-line-stock-price-hint]').textContent = '';
                }
                if (newRow.querySelector('.line-price-display')) newRow.querySelector('.line-price-display').placeholder = 'Harga / pcs';

                // ✅ reset mapping hidden
                newRow.querySelectorAll('.line-alloc-raw').forEach(inp => {
                    inp.value = 'hpp';
                    inp.dataset.userEdited = '0';
                });
                const newBadge = newRow.querySelector('[data-line-accounting-badge]');
                const newBadgeText = newRow.querySelector('[data-line-accounting-text]');
                if (newBadge && newBadgeText) {
                    newBadgeText.textContent = 'HPP';
                    newBadge.style.background = '#eff6ff';
                    newBadge.style.color = '#1d4ed8';
                    newBadge.style.borderColor = '#bfdbfe';
                    const icon = newBadge.querySelector('i');
                    if (icon) icon.className = 'bi bi-box-seam';
                }
                newRow.querySelectorAll('.line-expacc-wrapper, .line-expacc-text').forEach(wrap => {
                    wrap.style.display = 'none';
                });
                newRow.querySelectorAll('.line-expacc-raw').forEach(inp => {
                    inp.value = '';
                });

                const totalCell = newRow.querySelector('.line-total');
                if (totalCell) totalCell.textContent = '';

                tableBody.appendChild(newRow);
                renumberLines();
                recalcAll();

                focusRowItem(tableBody.querySelector('tr:last-child'));
            }

            btnAddTop?.addEventListener('click', addNewRow);
            btnAddBottom?.addEventListener('click', addNewRow);
            btnResetEmpty?.addEventListener('click', function (event) {
                event.preventDefault();
                const rows = Array.from(tableBody.querySelectorAll('tr'));
                if (rows.length <= 1) {
                    recalcAll();
                    return;
                }

                const isEmptyRow = (tr) => {
                    const itemId = (tr.querySelector('.js-item-suggest-id')?.value || '').toString().trim();
                    const itemText = (tr.querySelector('.js-item-suggest-input')?.value || '').toString().trim();
                    const qty = parseNumber(tr.querySelector('.line-qty-display')?.value || '');
                    const price = parseNumber(tr.querySelector('.line-price-display')?.value || '');

                    return itemId === '' && itemText === '' && qty <= 0.0001 && price <= 0.0001;
                };

                let removable = rows.filter(isEmptyRow);

                // Jangan hapus seluruh tabel: selalu sisakan satu baris kosong.
                if (removable.length === rows.length) {
                    removable = removable.slice(0, -1);
                }

                removable.forEach((tr) => tr.remove());

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
                    const rowUnitSelect = row.querySelector('[data-line-unit-select]');
                    if (rowUnitSelect) {
                        rowUnitSelect.innerHTML = '<option value="pcs" data-purchase-unit="pcs" data-stock-unit="pcs" data-conversion-factor="1">pcs</option>';
                        rowUnitSelect.value = 'pcs';
                        setLineUnitSnapshot(row, rowUnitSelect.selectedOptions[0]);
                    }

                    // ✅ reset mapping
                    row.querySelectorAll('.line-alloc-raw').forEach(inp => inp.value = 'hpp');
                    const rowBadge = row.querySelector('[data-line-accounting-badge]');
                    const rowBadgeText = row.querySelector('[data-line-accounting-text]');
                    if (rowBadge && rowBadgeText) {
                        rowBadgeText.textContent = 'HPP';
                        rowBadge.style.background = '#eff6ff';
                        rowBadge.style.color = '#1d4ed8';
                        rowBadge.style.borderColor = '#bfdbfe';
                        const icon = rowBadge.querySelector('i');
                        if (icon) icon.className = 'bi bi-box-seam';
                    }
                    row.querySelectorAll('.line-expacc-wrapper, .line-expacc-text').forEach(wrap => {
                        wrap.style.display = 'none';
                    });
                    row.querySelectorAll('.line-expacc-raw').forEach(inp => {
                        inp.value = '';
                    });

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
            // Enter: tambah baris baru dari manapun (dan cegah submit form)
            tableBody.addEventListener('keydown', function(e) {
                const el = e.target;
                const tr = el.closest('tr');
                if (!tr) return;

                const isItem  = el.classList.contains('js-item-suggest-input');
                const isQty   = el.classList.contains('line-qty-display');
                const isUnit  = el.classList.contains('line-unit-select');
                const isPrice = el.classList.contains('line-price-display');

                if (e.key === 'Enter') {
                    // Cegah enter submit form
                    e.preventDefault();

                    // Jika item suggest sedang terbuka, biarkan dropdown handle pilih item
                    const dropdown = tr.querySelector('.item-suggest-dropdown');
                    if (isItem && dropdown && dropdown.classList.contains('show')) {
                        // Jangan tambah baris, tunggu item terpilih
                        return;
                    }

                    // Tambah baris baru
                    addNewRow();
                    return;
                }

                if (!isItem && !isQty && !isUnit && !isPrice) return;

                if (e.key === 'Tab') {
                    e.preventDefault();
                    if (isItem) {
                        tr.querySelector('.line-qty-display')?.focus();
                    } else if (isQty) {
                        tr.querySelector('.line-unit-select')?.focus();
                    } else if (isUnit) {
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
            }, true);

            // Mencegah global form submit saat tekan enter di mana saja di dalam tabel
            tableBody.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });

            // focus select
            tableBody.addEventListener('focusin', function(e) {
                const el = e.target;
                if (el.classList.contains('line-price-display')) selectAllLater(el);
                if (el.classList.contains('line-qty-display')) selectAllLater(el);
            }, true);

            // Keep the raw values in sync while typing so mobile submit
            // cannot send a stale qty (usually 0) to the server.
            tableBody.addEventListener('input', function(e) {
                const tr = e.target.closest('tr');
                if (!tr) return;

                if (e.target.classList.contains('line-qty-display')) {
                    syncRowRaw(tr);
                    recalcAll();
                }

                if (e.target.classList.contains('line-price-display')) {
                    e.target.dataset.userEdited = '1';
                    syncRowRaw(tr);
                    recalcAll();
                }
            });

            // Final safety net for direct clicks/taps on the submit button.
            const poForm = tableBody.closest('form');
            poForm?.addEventListener('submit', function() {
                tableBody.querySelectorAll('tr').forEach(syncRowRaw);
                recalcAll();
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
                if (e.target.matches('[data-line-unit-select]')) {
                    const tr = e.target.closest('tr');
                    if (tr) {
                        setLineUnitSnapshot(tr, e.target.selectedOptions[0]);
                        recalcAll();
                    }
                    return;
                }

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

            // ✅ auto update master item expense account when changed
            tableBody.addEventListener('change', async function(e) {
                if (!e.target.classList.contains('line-expacc-raw')) return;
                
                const select = e.target;
                const tr = select.closest('tr');
                if (!tr || !select.value) return;

                const itemId = tr.querySelector('.js-item-suggest-id')?.value;
                if (!itemId) return;

                const wrapper = tr.querySelector('.line-expacc-wrapper');
                const originalBorder = select.style.borderColor;
                select.style.borderColor = '#94a3b8'; // loading state
                
                try {
                    const url = `{{ url('master/items') }}/${itemId}/update-expense-account`;
                    const res = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        },
                        body: JSON.stringify({ expense_account_id: select.value })
                    });
                    
                    const json = await res.json();
                    if (json.ok) {
                        if(wrapper) wrapper.style.display = 'none';
                        const textWrap = tr.querySelector('.line-expacc-text');
                        const textLabel = tr.querySelector('.expacc-label-text');
                        if (textWrap) textWrap.style.display = '';
                        if (textLabel) {
                            const opt = select.querySelector(`option[value="${select.value}"]`);
                            textLabel.textContent = opt ? opt.dataset.text : 'Terpilih';
                        }
                    } else {
                        select.style.borderColor = originalBorder;
                    }
                } catch (err) {
                    select.style.borderColor = originalBorder;
                    console.error('Gagal update akun biaya master:', err);
                }
            });

            // ✅ Edit expense account button
            tableBody.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-edit-expacc');
                if (!btn) return;
                
                const tr = btn.closest('tr');
                if (!tr) return;
                
                const wrapper = tr.querySelector('.line-expacc-wrapper');
                const textWrap = tr.querySelector('.line-expacc-text');
                
                if (wrapper) wrapper.style.display = '';
                if (textWrap) textWrap.style.display = 'none';
            });

            // supplier change -> refresh last price on rows not edited
            supplierSelect?.addEventListener('change', function() {
                tableBody.querySelectorAll('tr').forEach(tr => applyLastPriceToRow(tr, {
                    force: true
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
