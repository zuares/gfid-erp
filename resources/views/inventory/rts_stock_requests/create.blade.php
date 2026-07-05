{{-- resources/views/inventory/rts_stock_requests/create.blade.php --}}
@extends('layouts.app')

@section('title', 'RTS • Terima Jadi')

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-main-strong: rgba(15, 118, 110, 1);
            --rts-main-soft: rgba(45, 212, 191, .14);
            --warn-soft: rgba(245, 158, 11, .14);
            --danger-soft: rgba(239, 68, 68, .12);
        }

        .page-wrap {
            max-width: 1050px;
            margin-inline: auto;
            padding: 1rem .9rem 5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, .10) 0,
                    rgba(45, 212, 191, .12) 28%,
                    #f9fafb 65%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .28);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06), 0 0 0 1px rgba(15, 23, 42, .03);
            padding: .95rem;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .muted {
            opacity: .78;
        }

        .small {
            font-size: .85rem;
        }

        .tiny {
            font-size: .78rem;
        }

        .row {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: flex-start;
        }

        .col {
            flex: 1 1 240px;
            min-width: 220px;
        }

        label {
            display: block;
            font-size: .82rem;
            opacity: .78;
            margin-bottom: .25rem;
        }

        input[type="date"],
        input[type="number"],
        textarea,
        input[type="text"] {
            width: 100%;
            padding: .55rem .6rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: transparent;
            color: inherit;
            outline: none;
        }

        textarea {
            min-height: 92px;
            resize: vertical;
        }

        .table-wrap {
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .22);
            background: rgba(148, 163, 184, .06);
            margin-top: .75rem;
            overflow: visible !important;
            position: relative;
        }

        .table-scroll {
            overflow: auto;
            border-radius: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            min-width: 920px;
        }

        th,
        td {
            padding: .55rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, .16);
            vertical-align: middle;
            font-size: .9rem;
            overflow: visible !important;
            position: relative;
        }

        th {
            text-align: left;
            font-size: .78rem;
            opacity: .72;
            white-space: nowrap;
        }

        .col-no {
            width: 46px;
            text-align: center;
        }

        .col-item {
            width: 340px;
        }

        .col-receive {
            width: 140px;
            text-align: right;
        }

        .col-stock {
            width: 150px;
            text-align: right;
        }

        .col-action {
            width: 86px;
            text-align: right;
        }

        .td-num {
            text-align: right;
            white-space: nowrap;
        }

        .line-qty {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }


        .btn-row {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            margin-top: .9rem;
            align-items: center;
        }

        .btns {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .actions-cell {
            display: flex;
            gap: .4rem;
            align-items: center;
            justify-content: flex-end;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            padding: .45rem .75rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: transparent;
            color: inherit;
            text-decoration: none;
            cursor: pointer;
        }

        .btn:hover {
            border-color: rgba(45, 212, 191, .55);
        }

        .btn-primary {
            background: rgba(45, 212, 191, .14);
            border-color: rgba(45, 212, 191, .55);
            font-weight: 800;
        }

        .icon-btn {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: transparent;
            color: inherit;
            cursor: pointer;
            opacity: .9;
        }

        .icon-btn:hover {
            opacity: 1;
            border-color: rgba(45, 212, 191, .55);
        }

        tr.flash td {
            animation: flashBg 900ms ease;
        }

        @keyframes flashBg {
            0% {
                background: rgba(45, 212, 191, .18);
            }

            100% {
                background: transparent;
            }
        }

        .item-suggest-dropdown {
            z-index: 999999 !important;
            position: fixed !important;
            left: 0;
            top: 0;
            width: 320px;
            display: none;
        }

        .toast {
            position: fixed;
            left: 50%;
            bottom: 18px;
            transform: translateX(-50%);
            z-index: 999999;
            background: rgba(15, 23, 42, .92);
            color: #fff;
            padding: .55rem .75rem;
            border-radius: 999px;
            font-size: .82rem;
            box-shadow: 0 14px 30px rgba(0, 0, 0, .25);
            opacity: 0;
            pointer-events: none;
            transition: opacity .18s ease, transform .18s ease;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(-4px);
        }

    
        /* === Shipment-aligned UI override: RTS Stock Requests === */
        :root{
            --shp-accent:#334155;
            --shp-accent-2:#1f2937;
            --shp-border:rgba(148,163,184,.18);
            --shp-border-strong:rgba(148,163,184,.30);
            --shp-muted:#64748b;
        }

        .page-wrap{
            max-width:1040px!important;
            margin-inline:auto!important;
            padding:.75rem .75rem 4rem!important;
            background:transparent!important;
            border-radius:0!important;
        }

        body[data-theme="light"] .page-wrap,
        body[data-theme="dark"] .page-wrap{
            background:transparent!important;
        }

        .card,
        .card-main,
        .gf-card{
            border-radius:8px!important;
            border:1px solid var(--shp-border)!important;
            box-shadow:none!important;
            background:var(--card)!important;
        }

        body[data-theme="dark"] .card,
        body[data-theme="dark"] .card-main,
        body[data-theme="dark"] .gf-card{
            border-color:rgba(51,65,85,.85)!important;
        }

        .ship-topbar{
            position:sticky;
            top:0;
            z-index:300;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:.6rem;
            flex-wrap:wrap;
            padding:.45rem .75rem;
            margin-inline:-.75rem;
            margin-bottom:.65rem;
            background:var(--card,#fff);
            border-bottom:1px solid var(--shp-border);
        }

        body[data-theme="dark"] .ship-topbar{
            background:var(--card,#0f172a);
        }

        .ship-title,
        .title{
            font-weight:750!important;
            font-size:1rem!important;
            letter-spacing:0!important;
            margin:0!important;
            line-height:1.25!important;
        }

        .ship-sub,
        .sub,
        .meta{
            color:var(--shp-muted)!important;
            font-size:.78rem!important;
            opacity:1!important;
        }

        body[data-theme="dark"] .ship-sub,
        body[data-theme="dark"] .sub,
        body[data-theme="dark"] .meta{
            color:#9ca3af!important;
        }

        .ship-kpis,
        .kpis{
            display:flex;
            flex-wrap:wrap;
            gap:.32rem;
            margin-top:.35rem;
        }

        .ship-kpi,
        .kpi{
            display:inline-flex;
            align-items:baseline;
            gap:.45rem;
            border-radius:7px;
            padding:.2rem .48rem;
            border:1px solid rgba(148,163,184,.28);
            background:transparent;
            font-size:.72rem;
        }

        body[data-theme="dark"] .ship-kpi,
        body[data-theme="dark"] .kpi{
            background:rgba(15,23,42,.96);
            border-color:rgba(51,65,85,.85);
        }

        .ship-kpi .lbl,
        .kpi .lbl{
            text-transform:none;
            letter-spacing:0;
            font-size:.66rem;
            color:#94a3b8;
        }

        .ship-kpi .val,
        .kpi .val{
            font-weight:650;
            color:var(--shp-accent);
        }

        body[data-theme="dark"] .ship-kpi .val,
        body[data-theme="dark"] .kpi .val{
            color:#e5e7eb;
        }

        .ship-controls,
        .actions,
        .btns{
            display:flex!important;
            gap:.5rem!important;
            align-items:center!important;
            flex-wrap:wrap!important;
            justify-content:flex-end!important;
        }

        .btn,
        .btn-outline,
        .btn-primary{
            border-radius:7px!important;
            padding:.34rem .78rem!important;
            box-shadow:none!important;
            font-weight:600!important;
            font-size:.82rem!important;
            min-height:32px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            text-decoration:none!important;
        }

        .btn-primary,
        .btn-ship-primary{
            background:var(--shp-accent)!important;
            border-color:var(--shp-accent)!important;
            color:#fff!important;
        }

        .btn-primary:hover,
        .btn-ship-primary:hover{
            background:var(--shp-accent-2)!important;
            border-color:var(--shp-accent-2)!important;
            color:#fff!important;
        }

        .btn-outline,
        .btn-ship-outline{
            color:#475569!important;
            background:transparent!important;
            border:1px solid rgba(148,163,184,.35)!important;
        }

        .btn-outline:hover,
        .btn-ship-outline:hover{
            background:rgba(148,163,184,.08)!important;
            color:#111827!important;
        }

        .header-row{
            position:sticky!important;
            top:0!important;
            z-index:300!important;
            display:flex!important;
            justify-content:space-between!important;
            align-items:center!important;
            gap:.6rem!important;
            flex-wrap:wrap!important;
            padding:.45rem .75rem!important;
            margin-inline:-.75rem!important;
            margin-bottom:.65rem!important;
            background:var(--card,#fff)!important;
            border-bottom:1px solid var(--shp-border)!important;
        }

        body[data-theme="dark"] .header-row{
            background:var(--card,#0f172a)!important;
        }

        .stats{
            gap:.42rem!important;
        }

        .stat{
            border-radius:8px!important;
            box-shadow:none!important;
            background:transparent!important;
            border:1px solid rgba(148,163,184,.22)!important;
            padding:.42rem .55rem!important;
        }

        .stat .k{
            font-size:.68rem!important;
            color:#94a3b8!important;
            opacity:1!important;
        }

        .stat .v{
            font-size:.95rem!important;
            font-weight:700!important;
            color:var(--shp-accent)!important;
        }

        .table-wrap{
            border-radius:8px!important;
            border:1px solid var(--shp-border)!important;
            background:transparent!important;
        }

        .tbl thead th,
        table thead th,
        th{
            font-size:.68rem!important;
            text-transform:none!important;
            letter-spacing:0!important;
            font-weight:650!important;
            color:#64748b!important;
        }

        .tbl th,
        .tbl td,
        th,
        td{
            padding:.52rem .62rem!important;
        }

        .item-code{
            font-weight:700!important;
            letter-spacing:0!important;
        }

        input[type="date"],
        input[type="number"],
        input[type="text"],
        textarea,
        select{
            border-radius:7px!important;
            font-size:.86rem!important;
        }

        @media(max-width:767.98px){
            .page-wrap{
                padding:.5rem .5rem 4rem!important;
            }

            .ship-topbar,
            .header-row{
                margin-inline:-.5rem!important;
                padding:.5rem .65rem!important;
            }

            .ship-title,
            .title{
                font-size:1.05rem!important;
            }

            .ship-sub,
            .sub{
                display:none!important;
            }

            .ship-kpis,
            .kpis{
                display:none!important;
            }

            .ship-controls,
            .actions,
            .btns{
                width:100%!important;
                justify-content:flex-start!important;
            }

            .ship-controls .btn,
            .actions .btn,
            .btns .btn{
                min-height:40px!important;
            }

            .card{
                border-radius:8px!important;
            }
        }

    </style>
@endpush

@section('content')
    @php
        $role = strtolower((string) (auth()->user()?->role ?? ''));
        $canManage = in_array($role, ['owner', 'admin'], true);

        $itemsForJs = $finishedGoodsItems
            ->map(fn($i) => [
                'id' => $i->id,
                'code' => $i->code,
                'name' => $i->name,
                'prd_stock' => (float) ($prdStockByItem[$i->id] ?? 0),
            ])
            ->values()
            ->toArray();

        // ✅ create selalu baru: ambil old('lines') saja
        $oldLinesForJs = old('lines', []);

        // Render item-suggest component template
        $itemSuggestHtml = view('components.item-suggest', [
            'idName' => '__tmp__',
            'categoryName' => null,
            'items' => $finishedGoodsItems,
            'displayValue' => '',
            'idValue' => '',
            'categoryValue' => '',
            'placeholder' => 'Kode / nama barang',
            'type' => 'finished_good',
            'itemCategoryId' => null,
            'minChars' => 1,
            'autofocus' => false,
            'variant' => 'default',
            'displayMode' => 'code-name',
            'showName' => true,
            'showCategory' => false,
            'extraParams' => [],
        ])->render();
    @endphp

    <div class="page-wrap">
        <div class="ship-topbar">
            <div>
                <div class="ship-title">RTS • Terima Jadi</div>
                <div class="ship-sub">
                    Ambil barang jadi dari <b>{{ $prdWarehouse->code }}</b> ke <b>{{ $rtsWarehouse->code }}</b>.
                </div>

                <div class="ship-kpis">
                    <span class="ship-kpi"><span class="lbl">Sumber</span><span class="val">{{ $prdWarehouse->code }}</span></span>
                    <span class="ship-kpi"><span class="lbl">Tujuan</span><span class="val">{{ $rtsWarehouse->code }}</span></span>
                    <span class="ship-kpi"><span class="lbl">Mode</span><span class="val">Request RTS</span></span>
                </div>
            </div>

            <div class="ship-controls">
                <a class="btn btn-ship-outline" href="{{ route('rts.stock-requests.index') }}">← List</a>
            </div>
        </div>

        @if (!$canManage)
            <div class="card" style="margin-top:.85rem">
                <div class="tiny" style="color: rgba(239,68,68,1); font-weight:700;">
                    Akses ditolak. Hanya Owner/Admin yang bisa membuat permintaan RTS.
                </div>
            </div>
        @else
            <form id="rtsCreateForm" method="POST" action="{{ route('rts.stock-requests.store') }}"
                style="margin-top:.9rem">
                @csrf

                <div class="card">
                    <div class="row">
                        <div class="col">
                            <label>Tanggal</label>
                            <input type="date" name="date"
                                value="{{ old('date', $prefillDate ?? now()->toDateString()) }}">
                            @error('date')
                                <div class="tiny" style="color: rgba(239,68,68,1); margin-top:.25rem">{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col">
                            <label>Gudang Sumber (PRD)</label>
                            <input type="hidden" name="source_warehouse_id" value="{{ $prdWarehouse->id }}">
                            <input type="text" value="{{ $prdWarehouse->code }} — {{ $prdWarehouse->name ?? 'PRD' }}"
                                disabled>
                            @error('source_warehouse_id')
                                <div class="tiny" style="color: rgba(239,68,68,1); margin-top:.25rem">{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col">
                            <label>Gudang Tujuan (RTS)</label>
                            <input type="hidden" name="destination_warehouse_id" value="{{ $rtsWarehouse->id }}">
                            <input type="text" value="{{ $rtsWarehouse->code }} — {{ $rtsWarehouse->name ?? 'RTS' }}"
                                disabled>
                            @error('destination_warehouse_id')
                                <div class="tiny" style="color: rgba(239,68,68,1); margin-top:.25rem">{{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                </div>

                <div class="card" style="margin-top:.85rem">
                    <div style="display:flex;justify-content:space-between;gap:.75rem;flex-wrap:wrap;align-items:center">
                        <div>
                            <div style="font-weight:900">Item Terima Jadi</div>
                            <div class="muted small">Pilih item, cek stok WH-PRD, lalu isi qty yang diterima.</div>
                        </div>

                        <div class="btns">
                            <button type="button" class="btn" id="btnAddRow">+ Tambah Baris</button>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="col-no">No</th>
                                        <th class="col-item">Kode Barang</th>
                                        <th class="col-receive">Terima</th>
                                        <th class="col-stock">Stok WH-PRD</th>
                                        <th class="col-action">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="linesTbody"></tbody>
                            </table>
                        </div>
                    </div>

                    @error('lines')
                        <div class="tiny" style="color: rgba(239,68,68,1); margin-top:.5rem">{{ $message }}</div>
                    @enderror

                    @if ($errors->has('lines.*.qty_request') || $errors->has('lines.*.item_id'))
                        <div class="tiny" style="color: rgba(239,68,68,1); margin-top:.5rem">
                            Periksa item & qty. Qty harus &gt; 0 dan item tidak boleh duplikat.
                        </div>
                    @endif
                </div>


                <div class="btn-row">
                    <div class="muted small">Klik Terima Jadi untuk konfirmasi perpindahan stok.</div>
                    <div class="btns">
                        <a class="btn" href="{{ route('rts.stock-requests.index') }}">Batal</a>
                        <button type="submit" class="btn btn-primary">Terima Jadi</button>
                    </div>
                </div>
            </form>

            <div class="modal fade" id="confirmReceiveModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content" style="border-radius:16px;">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title" style="margin:0;">Konfirmasi Terima Jadi</h5>
                                <div class="text-muted mono" style="font-size:.78rem;font-weight:800;margin-top:.15rem;">
                                    <span id="m-kode">—</span>
                                    &nbsp;·&nbsp;
                                    <span id="m-tanggal">—</span>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="p-3 border bg-light" style="border-radius:14px;">
                                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                    <div style="font-weight:900;">Ringkasan</div>
                                    <div class="text-muted" style="font-weight:800;font-size:.82rem;">
                                        <span class="mono">{{ $prdWarehouse->code }}</span>
                                        → <span class="mono">{{ $rtsWarehouse->code }}</span>
                                    </div>
                                </div>
                                <div class="mt-2 d-flex gap-3 flex-wrap" style="font-size:.88rem;font-weight:800;">
                                    <div>Total Terima&nbsp;<span class="mono" id="m-total" style="font-size:1rem;">0</span></div>
                                    <div id="m-short-wrap" class="d-none" style="color:#ef4444;">Stok Kurang&nbsp;<span class="mono" id="m-short">0</span></div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div style="font-weight:900;">Detail Barang Diterima</div>
                                    <div class="text-muted" style="font-weight:800;font-size:.86rem;">
                                        Item: <span class="mono" id="m-items-count">0</span>
                                    </div>
                                </div>

                                <div class="border" style="border-radius:14px; overflow:hidden;">
                                    <div class="px-3 py-2"
                                        style="background:rgba(148,163,184,.06); border-bottom:1px solid rgba(148,163,184,.18); font-size:.72rem; font-weight:900; color:var(--muted); text-transform:uppercase; letter-spacing:.10em;">
                                        <div class="d-grid"
                                            style="grid-template-columns: 44px 1fr 120px 120px; gap:.5rem; align-items:center;">
                                            <div>No</div>
                                            <div>Item</div>
                                            <div class="text-end">Terima</div>
                                            <div class="text-end">Stok PRD</div>
                                        </div>
                                    </div>

                                    <div id="m-items" style="max-height:40vh; overflow:auto; -webkit-overflow-scrolling:touch;">
                                        <div class="px-3 py-2 text-muted">Belum ada item.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 d-none" id="modal-fallback-note">
                                <div class="alert alert-warning mb-0">
                                    Modal tidak bisa ditampilkan karena Bootstrap JS belum ter-load. Tombol <b>Simpan</b>
                                    akan submit langsung.
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-sm btn-success" id="btn-confirm-submit">Simpan</button>
                        </div>
                    </div>
                </div>
            </div>

        @endif
    </div>

    <div id="toast" class="toast"></div>

    <script>
        (function() {
            const items = @json($itemsForJs);
            const oldLines = @json($oldLinesForJs);
            const itemSuggestHtml = @json($itemSuggestHtml);

            const tbody = document.getElementById('linesTbody');
            const form = document.getElementById('rtsCreateForm');
            const toast = document.getElementById('toast');
            function showToast(msg) {
                if (!toast) return;
                toast.textContent = msg;
                toast.classList.add('show');
                clearTimeout(showToast._t);
                showToast._t = setTimeout(() => toast.classList.remove('show'), 1400);
            }

            function getItemById(id) {
                const s = String(id);
                return items.find(x => String(x.id) === s) || null;
            }

            function num(v) {
                const n = parseFloat((v ?? '').toString().replace(',', '.'));
                return isNaN(n) ? 0 : n;
            }

            function fmtQty(v) {
                const n = Math.round(num(v) * 100) / 100;
                return n.toFixed(2).replace(/\.00$/, '').replace(/(\.\d)0$/, '$1');
            }

            function renumber() {
                [...tbody.querySelectorAll('tr')].forEach((tr, idx) => {
                    tr.querySelector('[data-no]').textContent = String(idx + 1);

                    tr.querySelectorAll('[data-name]').forEach(el => {
                        const base = el.getAttribute('data-name');
                        el.setAttribute('name', `lines[${idx}][${base}]`);
                    });

                    const hiddenId = tr.querySelector('.js-item-suggest-id');
                    if (hiddenId) hiddenId.setAttribute('name', `lines[${idx}][item_id]`);
                });
            }

            function clearSuggest(tr) {
                const hiddenId = tr.querySelector('.js-item-suggest-id');
                const inputTxt = tr.querySelector('.js-item-suggest-input');
                const stockCell = tr.querySelector('.js-prd-stock');
                if (hiddenId) hiddenId.value = '';
                if (inputTxt) inputTxt.value = '';
                if (stockCell) {
                    stockCell.textContent = '-';
                    stockCell.classList.add('muted');
                }
                const dd = tr.querySelector('.item-suggest-dropdown');
                if (dd) dd.style.display = 'none';
            }

            function updatePrdStock(tr) {
                const hiddenId = tr.querySelector('.js-item-suggest-id');
                const stockCell = tr.querySelector('.js-prd-stock');
                if (!hiddenId || !stockCell) return;

                const it = getItemById(hiddenId.value);
                if (!it) {
                    stockCell.textContent = '-';
                    stockCell.style.color = '';
                    stockCell.classList.add('muted');
                    return;
                }

                const stock = num(it.prd_stock);
                const qty = num(tr.querySelector('.line-qty')?.value);
                const sisa = stock - qty;

                stockCell.classList.remove('muted');
                if (qty > 0) {
                    stockCell.textContent = fmtQty(sisa);
                    stockCell.style.color = sisa < -0.0000001 ? '#ef4444' : (sisa < 0.0000001 ? '#f59e0b' : '');
                    stockCell.title = `Stok PRD: ${fmtQty(stock)} | Terima: ${fmtQty(qty)} | Sisa: ${fmtQty(sisa)}`;
                } else {
                    stockCell.textContent = fmtQty(stock);
                    stockCell.style.color = '';
                    stockCell.title = '';
                    stockCell.classList.toggle('muted', Math.abs(stock) <= 0.0000001);
                }
            }

            function flashRow(tr) {
                tr.classList.remove('flash');
                void tr.offsetWidth;
                tr.classList.add('flash');
                setTimeout(() => tr.classList.remove('flash'), 950);
            }

            function findRowByItemId(itemId, excludeTr = null) {
                const target = String(itemId);
                const rows = [...tbody.querySelectorAll('tr')];
                for (const tr of rows) {
                    if (excludeTr && tr === excludeTr) continue;
                    const hid = tr.querySelector('.js-item-suggest-id');
                    const val = (hid?.value || '').trim();
                    if (val && String(val) === target) return tr;
                }
                return null;
            }

            function selectedRows() {
                return [...tbody.querySelectorAll('tr')]
                    .map(tr => {
                        const itemId = (tr.querySelector('.js-item-suggest-id')?.value || '').trim();
                        const item = getItemById(itemId);
                        const qty = num(tr.querySelector('.line-qty')?.value);
                        const stock = item ? num(item.prd_stock) : 0;

                        return {
                            tr,
                            itemId,
                            item,
                            qty,
                            stock,
                        };
                    })
                    .filter(row => row.item && row.qty > 0);
            }

            function renderDashboard() {
                const rows = selectedRows();
                const total = rows.reduce((sum, row) => sum + row.qty, 0);
                const shortageCount = rows.filter(row => row.qty > row.stock + 0.0000001).length;

            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function buildRow() {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="mono col-no" data-no style="opacity:.75">1</td>
                    <td class="cell-item"></td>
                    <td class="td-num">
                        <input data-name="qty_request" class="line-qty" type="number" step="0.01" min="0" value="" placeholder="0">
                    </td>
                    <td class="mono js-prd-stock muted td-num">-</td>
                    <td class="col-action">
                        <div class="actions-cell">
                            <button type="button" class="icon-btn btnDel" title="Hapus">✕</button>
                        </div>
                    </td>
                `;

                tr.querySelector('.cell-item').innerHTML = itemSuggestHtml;

                tr.querySelector('.btnDel').addEventListener('click', () => {
                    tr.remove();
                    renumber();
                    renderDashboard();
                });

                tbody.appendChild(tr);

                if (window.initItemSuggestInputs) window.initItemSuggestInputs(tr);

                // ✅ anti duplicate: merge qty ke row existing
                const hiddenId = tr.querySelector('.js-item-suggest-id');
                if (hiddenId) {
                    hiddenId.addEventListener('change', () => {
                        updatePrdStock(tr);
                        renderDashboard();

                        const chosen = (hiddenId.value || '').trim();
                        if (!chosen) return;

                        const existingTr = findRowByItemId(chosen, tr);
                        if (!existingTr) return;

                        const qtyThisEl = tr.querySelector('.line-qty');
                        const qtyThis = num(qtyThisEl?.value);

                        const qtyExistEl = existingTr.querySelector('.line-qty');
                        const qtyExist = num(qtyExistEl?.value);

                        // default add 1 kalau qty kosong
                        const addQty = qtyThis > 0 ? qtyThis : 1;

                        if (qtyExistEl) qtyExistEl.value = (qtyExist + addQty).toFixed(2);
                        updatePrdStock(existingTr);

                        const it = getItemById(chosen);
                        const label = it ? `${(it.code || '').toUpperCase()}` : `Item ${chosen}`;
                        showToast(`✅ ${label}: qty digabung (+${addQty})`);
                        flashRow(existingTr);

                        // clear current row
                        if (qtyThisEl) qtyThisEl.value = '';
                        clearSuggest(tr);
                        renderDashboard();

                        tr.querySelector('.js-item-suggest-input')?.focus();
                    });
                }

                renumber();
                const qtyEl = tr.querySelector('.line-qty');
                qtyEl?.addEventListener('input', () => { updatePrdStock(tr); renderDashboard(); });
                qtyEl?.addEventListener('change', () => { updatePrdStock(tr); renderDashboard(); });
                renderDashboard();
                return tr;
            }

            function setRowData(tr, data = {}) {
                const hiddenId = tr.querySelector('.js-item-suggest-id');
                const inputTxt = tr.querySelector('.js-item-suggest-input');
                const qtyInput = tr.querySelector('.line-qty');

                if (data.item_id && hiddenId && inputTxt) {
                    const it = getItemById(data.item_id);
                    hiddenId.value = String(data.item_id);

                    if (it) {
                        inputTxt.value = `${(it.code || '').toUpperCase()} — ${it.name || ''}`.toUpperCase();
                    } else {
                        inputTxt.value = String(data.item_id);
                    }

                    hiddenId.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }

                if (qtyInput && data.qty_request != null) qtyInput.value = data.qty_request;
                renderDashboard();
            }

            function addRow(data = {}) {
                const tr = buildRow();
                setRowData(tr, data);
            }

            function seedInitialRows() {
                if (Array.isArray(oldLines) && oldLines.length > 0) {
                    oldLines.forEach(l => addRow(l));
                    return;
                }
                addRow({});
            }

            document.getElementById('btnAddRow')?.addEventListener('click', () => addRow({}));

            let confirmed = false;

            function formRows() {
                return [...tbody.querySelectorAll('tr')];
            }

            function cleanupRows() {
                formRows().forEach(tr => {
                    const id = (tr.querySelector('.js-item-suggest-id')?.value || '').trim();
                    const qty = num(tr.querySelector('.line-qty')?.value);
                    if (!id && qty <= 0) tr.remove();
                });

                renumber();
            }

            function validRows() {
                return formRows().filter(tr => {
                    const id = (tr.querySelector('.js-item-suggest-id')?.value || '').trim();
                    const qty = num(tr.querySelector('.line-qty')?.value);
                    return !!id && qty > 0;
                });
            }

            function totalQty(rows) {
                return rows.reduce((sum, tr) => sum + num(tr.querySelector('.line-qty')?.value), 0);
            }

            function submitConfirmed() {
                confirmed = true;
                form?.submit();
            }

            function openConfirm(rows) {
                const total = totalQty(rows);
                const shortageCount = rows.filter(tr => {
                    const id = (tr.querySelector('.js-item-suggest-id')?.value || '').trim();
                    const it = getItemById(id);
                    const qty = num(tr.querySelector('.line-qty')?.value);
                    return it && qty > it.prd_stock + 0.0000001;
                }).length;

                // Populate header
                const dateVal = document.querySelector('[name="date"]')?.value || '';
                const elKode = document.getElementById('m-kode');
                const elTanggal = document.getElementById('m-tanggal');
                if (elKode) elKode.textContent = 'RTS-DRAFT';
                if (elTanggal && dateVal) {
                    const d = new Date(dateVal + 'T00:00:00');
                    elTanggal.textContent = d.toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' });
                }

                // Populate ringkasan
                const elTotal = document.getElementById('m-total');
                const elShort = document.getElementById('m-short');
                const elShortWrap = document.getElementById('m-short-wrap');
                const elCount = document.getElementById('m-items-count');
                const elItems = document.getElementById('m-items');

                if (elTotal) elTotal.textContent = fmtQty(total);
                if (elShort) elShort.textContent = shortageCount;
                if (elShortWrap) elShortWrap.classList.toggle('d-none', shortageCount === 0);
                if (elCount) elCount.textContent = rows.length;

                if (elItems) {
                    elItems.innerHTML = rows.map((tr, idx) => {
                        const id = (tr.querySelector('.js-item-suggest-id')?.value || '').trim();
                        const it = getItemById(id);
                        const qty = num(tr.querySelector('.line-qty')?.value);
                        const code = it ? it.code.toUpperCase() : id;
                        const sisa = it ? it.prd_stock - qty : null;
                        const isShort = sisa !== null && sisa < -0.0000001;
                        const sisaText = sisa !== null ? fmtQty(sisa) : '-';
                        return `<div class="px-3 py-2" style="border-bottom:1px solid rgba(148,163,184,.12);">
                            <div class="d-grid" style="grid-template-columns:44px 1fr 120px 120px;gap:.5rem;align-items:center;">
                                <div class="text-muted" style="font-weight:900;">${idx + 1}</div>
                                <div style="font-weight:900;" class="mono">${escapeHtml(code)}</div>
                                <div class="text-end mono" style="font-weight:900;">${fmtQty(qty)}</div>
                                <div class="text-end mono" style="font-weight:900;${isShort ? 'color:#ef4444;' : ''}">${sisaText}</div>
                            </div>
                        </div>`;
                    }).join('');
                }

                const modalEl = document.getElementById('confirmReceiveModal');
                if (modalEl && window.bootstrap?.Modal) {
                    (new bootstrap.Modal(modalEl)).show();
                } else {
                    // fallback: browser confirm
                    document.getElementById('modal-fallback-note')?.classList.remove('d-none');
                    if (confirm(`Total Terima: ${fmtQty(total)}. Stok akan berpindah dari WH-PRD ke WH-RTS.`)) {
                        submitConfirmed();
                    }
                }
            }

            // Confirm submit button inside modal
            document.getElementById('btn-confirm-submit')?.addEventListener('click', () => {
                const modalEl = document.getElementById('confirmReceiveModal');
                if (modalEl && window.bootstrap?.Modal) {
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                }
                submitConfirmed();
            });

            // submit cleanup + validation
            form?.addEventListener('submit', (e) => {
                if (confirmed) return;

                cleanupRows();
                const rows = validRows();

                if (rows.length < 1) {
                    e.preventDefault();
                    alert('Minimal isi 1 item dengan qty > 0.');
                    return;
                }

                e.preventDefault();
                openConfirm(rows);
            });

            /* =========================================================
               Dropdown FIXED POSITION PATCH
               - pastikan dropdown item-suggest selalu di atas elemen lain
               - kebal overflow container/table
            ========================================================= */
            function patchFixedDropdown(scope = document) {
                scope.querySelectorAll('.item-suggest-wrap').forEach(wrap => {
                    const input = wrap.querySelector('.js-item-suggest-input');
                    const dropdown = wrap.querySelector('.item-suggest-dropdown');
                    if (!input || !dropdown) return;

                    if (dropdown.dataset.fixedPatched === '1') return;
                    dropdown.dataset.fixedPatched = '1';

                    function placeDropdown() {
                        if (dropdown.style.display === 'none') return;

                        const rect = input.getBoundingClientRect();
                        const margin = 6;

                        const maxH = 240;
                        const viewportH = window.innerHeight;

                        let top = rect.bottom + margin;
                        let left = rect.left;
                        let width = rect.width;

                        const spaceBelow = viewportH - rect.bottom - margin;
                        const desiredH = Math.min(maxH, dropdown.scrollHeight || maxH);

                        if (spaceBelow < 120) {
                            const aboveTop = rect.top - margin - desiredH;
                            if (aboveTop > 8) top = aboveTop;
                        }

                        dropdown.style.left = left + 'px';
                        dropdown.style.top = top + 'px';
                        dropdown.style.width = width + 'px';
                        dropdown.style.maxHeight = Math.min(maxH, Math.max(120, spaceBelow)) + 'px';
                    }

                    const obs = new MutationObserver(() => placeDropdown());
                    obs.observe(dropdown, {
                        attributes: true,
                        attributeFilter: ['style', 'class']
                    });

                    const onMove = () => placeDropdown();
                    window.addEventListener('scroll', onMove, true);
                    window.addEventListener('resize', onMove);

                    input.addEventListener('focus', () => setTimeout(placeDropdown, 0));
                    input.addEventListener('input', () => setTimeout(placeDropdown, 0));
                });
            }

            // init
            seedInitialRows();
            setTimeout(() => patchFixedDropdown(document), 0);

        })();
    </script>
@endsection
