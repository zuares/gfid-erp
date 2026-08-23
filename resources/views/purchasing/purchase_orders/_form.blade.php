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
            .po-td-price  { grid-column: 3; grid-row: 1; display: block !important; width: auto !important; margin: 0 !important; }
            .po-td-total  { display: none !important; }
            .po-lines-no-money .po-td-qty { grid-column: 2; }
            .po-lines-no-money .po-td-action { grid-column: 3; }
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

            .po-supplier-select,
            .po-date-wrap input {
                min-height: 42px !important;
                font-size: 14px !important;
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
                min-width: 30px;
                width: 30px;
            }
        }
    </style>
@endpush

{{-- FOCAL: SUPPLIER + TANGGAL DOKUMEN --}}
<div class="shp-table-card mb-3" data-order-type="{{ $orderType }}">
    <div class="shp-table-head">
        <div class="shp-table-title">Informasi PO</div>
    </div>
    <div style="padding:1.1rem 1.25rem .9rem;">

        <div class="po-focal-fields">
            <div>
                <label for="po-supplier" class="po-label mb-1">Supplier</label>
                <select name="supplier_id" id="po-supplier"
                    class="form-select po-field po-supplier-select @error('supplier_id') is-invalid @enderror"
                    required>
                    <option value="">— Pilih Supplier —</option>
                    @foreach ($suppliers as $sup)
                        <option value="{{ $sup->id }}"
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
                                placeholder="Masukan kode barang" />
                            <div class="line-accounting-badge mt-1" data-line-accounting-badge
                                 style="display:inline-flex;align-items:center;gap:.25rem;padding:.12rem .42rem;border-radius:999px;font-size:.64rem;font-weight:700;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">
                                <i class="bi bi-box-seam"></i><span data-line-accounting-text>HPP</span>
                            </div>
                            <input type="hidden" name="lines[0][allocation]" class="line-alloc-raw" value="hpp">
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

                        @if ($canSeeMoney)
                            <td data-label="Harga" class="po-td-price">
                                <input type="text" class="form-control po-field po-num-display line-price-display"
                                    inputmode="numeric" placeholder="Harga" value="" autocomplete="off">
                                <input type="hidden" name="lines[0][unit_price]" class="line-price-raw" value="">
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
            <span class="mono po-total-live" id="po-live-qty" style="color:var(--primary); font-size:1.05rem;">0</span> qty
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

            const canSeeMoney = @json($canSeeMoney);

            const supplierSelect = document.querySelector('#po-supplier');
            const shippingDisplay = document.querySelector('.shipping-display');
            const shippingRaw = document.querySelector('.shipping-raw');

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
                const accountingBadge = tr.querySelector('[data-line-accounting-badge]');
                const accountingText = tr.querySelector('[data-line-accounting-text]');
                if (!allocRaw || !expAccRaw) return;

                // kalau nanti kamu bikin UI override manual, flag ini bisa dipakai.
                const userEdited = allocRaw.dataset.userEdited === '1';
                if (!force && userEdited) return;

                const meta = await fetchItemMeta(itemId);
                if (!meta) return;

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
                });

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
