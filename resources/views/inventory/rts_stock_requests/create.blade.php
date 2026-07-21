{{-- resources/views/inventory/rts_stock_requests/create.blade.php --}}
@extends('layouts.app')

@section('title', 'RTS • Terima Barang')

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
            width: 170px;
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
            --shp-accent-ring:rgba(148,163,184,.18);
            --shp-border:rgba(148,163,184,.18);
            --shp-border-strong:rgba(148,163,184,.30);
            --shp-muted:#64748b;
        }

        /* ── Scan box (selaras dengan Shipment) ── */
        .rts-scan{ margin-top:.75rem; }
        .rts-scan-label{
            font-size:.68rem; text-transform:uppercase; letter-spacing:.1em;
            color:#9ca3af; font-weight:700; margin-bottom:.4rem;
        }
        .rts-scan-wrap{
            display:flex; gap:.5rem; align-items:center;
            background:rgba(148,163,184,.1);
            border:1px solid rgba(148,163,184,.28);
            border-radius:8px; padding:.45rem .55rem;
            transition:border-color .12s, box-shadow .12s;
        }
        .rts-scan-wrap:focus-within{ border-color:var(--shp-accent); box-shadow:0 0 0 3px var(--shp-accent-ring); }
        body[data-theme="dark"] .rts-scan-wrap{ background:rgba(15,23,42,.7); border-color:rgba(51,65,85,.85); }
        .rts-scan-wrap input{
            flex:1; background:transparent; border:none; padding:.2rem .3rem;
            color:inherit; font-size:1rem; font-weight:700;
            font-family:ui-monospace,SFMono-Regular,Menlo,monospace; outline:none; text-transform:uppercase;
        }
        .rts-scan-wrap input::placeholder{ color:#94a3b8; font-weight:400; font-family:inherit; text-transform:none; }
        .rts-scan-btn{
            background:var(--shp-accent); border:none; border-radius:6px; padding:.5rem 1.1rem;
            color:#fff; font-weight:700; font-size:.86rem; cursor:pointer; transition:background .15s; white-space:nowrap;
        }
        .rts-scan-btn:hover{ background:var(--shp-accent-2); }
        .rts-scan-status{ margin-top:.4rem; font-size:.78rem; min-height:1.1rem; color:#94a3b8; }
        .rts-scan-status.ok{ color:#15803d; }
        .rts-scan-status.err{ color:#b91c1c; }

        /* ── Tombol tambah baris (di bawah tabel) ── */
        .rts-addrow-wrap{ margin-top:.7rem; }
        .btn-addrow{
            border-radius:8px;
            border:1px dashed rgba(148,163,184,.55)!important;
            background:transparent!important;
            color:var(--shp-muted)!important;
            font-weight:700; font-size:.85rem;
            padding:.5rem 1rem;
        }
        .btn-addrow:hover{ border-color:var(--shp-accent)!important; color:var(--shp-accent)!important; }
        @media (max-width:767.98px){
            .rts-addrow-wrap{ margin-top:.85rem; }
            .btn-addrow{ width:100%; justify-content:center; padding:.7rem 1rem; }
        }

        /* ── Kolom Stok WH-PRD: muted, bukan focal point ── */
        .js-prd-stock{ font-weight:400!important; font-size:.82rem; opacity:.6; color:var(--shp-muted)!important; }

        /* ── Input Terima: stepper user-friendly ── */
        .qty-stepper{
            display:inline-flex; align-items:stretch;
            border:1px solid rgba(148,163,184,.4); border-radius:9px;
            overflow:hidden; background:var(--card,#fff);
            width:100%; max-width:150px; margin-left:auto;
        }
        body[data-theme="dark"] .qty-stepper{ background:rgba(15,23,42,.6); border-color:rgba(51,65,85,.85); }
        .qty-stepper:focus-within{ border-color:var(--shp-accent); box-shadow:0 0 0 3px var(--shp-accent-ring); }
        .qty-stepper .line-qty{
            flex:1; width:auto!important; min-width:0;
            border:none!important; outline:none; background:transparent;
            text-align:center; font-size:1rem; font-weight:800; font-variant-numeric:tabular-nums;
            padding:.4rem .25rem; color:inherit; box-shadow:none!important; border-radius:0!important;
            -moz-appearance:textfield;
        }
        .qty-stepper .line-qty::-webkit-outer-spin-button,
        .qty-stepper .line-qty::-webkit-inner-spin-button{ -webkit-appearance:none; margin:0; }
        .qty-btn{
            border:none; background:rgba(148,163,184,.14); color:var(--shp-accent);
            width:38px; flex:0 0 38px; font-size:1.2rem; font-weight:700; line-height:1; cursor:pointer;
            display:flex; align-items:center; justify-content:center; user-select:none;
            transition:background .12s;
        }
        .qty-btn:hover{ background:var(--shp-accent); color:#fff; }
        .qty-btn:active{ background:var(--shp-accent-2); color:#fff; }
        body[data-theme="dark"] .qty-btn{ color:#cbd5e1; background:rgba(51,65,85,.5); }
        body[data-theme="dark"] .qty-btn:hover{ background:var(--shp-accent); color:#fff; }
        @media (max-width:767.98px){
            .qty-stepper{ max-width:none; }
            .qty-btn{ width:44px; flex-basis:44px; font-size:1.35rem; }
            .qty-stepper .line-qty{ font-size:1.05rem; padding:.55rem .25rem; }
        }

        /* ── Summary pills ── */
        .rts-summary{ display:flex; gap:.4rem; flex-wrap:wrap; margin-top:.7rem; }
        .rts-pill{
            border-radius:999px; padding:.24rem .8rem; font-size:.78rem;
            border:1px solid rgba(148,163,184,.32); background:rgba(248,250,252,.96); color:inherit; white-space:nowrap;
        }
        body[data-theme="dark"] .rts-pill{ background:rgba(15,23,42,.98); border-color:rgba(51,65,85,.85); color:#e5e7eb; }
        .rts-pill b{ font-size:.9rem; }
        .rts-pill-accent{ border-color:var(--shp-accent)!important; background:var(--shp-accent-bg,rgba(148,163,184,.08))!important; color:var(--shp-accent)!important; font-weight:700; }
        body[data-theme="dark"] .rts-pill-accent{ color:#cbd5e1!important; }
        .rts-pill-danger{ border-color:rgba(239,68,68,.55)!important; background:rgba(239,68,68,.08)!important; color:#b91c1c!important; font-weight:700; }

        /* ── Modal selaras Shipment ── */
        #confirmReceiveModal .modal-content{
            border-radius:12px!important; border:1px solid var(--shp-border-strong); box-shadow:0 24px 60px rgba(15,23,42,.25);
        }
        #confirmReceiveModal .modal-header,
        #confirmReceiveModal .modal-footer{ border-color:var(--shp-border); padding:.85rem 1.15rem; }
        #confirmReceiveModal .modal-title{ font-weight:800; font-size:1rem; }
        #confirmReceiveModal .modal-body{ padding:1.15rem; }

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

        // ✅ create selalu baru: ambil old('lines') saja, atau gunakan data dari query parameters (prefillLines)
        $oldLinesForJs = old('lines', $prefillLines ?? []);

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
                <div class="ship-title">RTS • Terima Barang</div>
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
            <form id="rtsCreateForm" method="POST" action="{{ isset($stockRequest) ? route('rts.stock-requests.update', $stockRequest) : route('rts.stock-requests.store') }}"
                style="margin-top:.9rem">
                @csrf
                @if(isset($stockRequest))
                    @method('PUT')
                @endif

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
                            <div style="font-weight:900">Item Terima Barang</div>
                            <div class="muted small">Pilih item, cek stok WH-PRD, lalu isi qty yang diterima.</div>
                        </div>
                    </div>

                    {{-- Scan box (selaras dengan Shipment) --}}
                    <div class="rts-scan">
                        <div class="rts-scan-label">Scan / Ketik Kode Barang</div>
                        <div class="rts-scan-wrap">
                            <input id="scanInput" type="text" autocomplete="off" autofocus
                                   placeholder="Scan barcode atau ketik kode item lalu Enter"
                                   onkeydown="if(event.key==='Enter'){event.preventDefault();window.__rtsScan()}">
                            <button type="button" class="rts-scan-btn" id="scanBtn" onclick="window.__rtsScan()">Tambah</button>
                        </div>
                        <div class="rts-scan-status" id="scanStatus"></div>

                        <div class="rts-summary">
                            <span class="rts-pill">Item <b id="sumItems">0</b></span>
                            <span class="rts-pill rts-pill-accent">Total Terima <b id="sumTotal">0</b></span>
                            <span class="rts-pill rts-pill-danger d-none" id="sumShortWrap"><b id="sumShort">0</b> Item Minus di PRD</span>
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

                    <div class="rts-addrow-wrap">
                        <button type="button" class="btn btn-addrow" id="btnAddRow">+ Tambah Baris</button>
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
                    <div class="muted small">Klik Terima Barang untuk konfirmasi perpindahan stok.</div>
                    <div class="btns">
                        <a class="btn" href="{{ route('rts.stock-requests.index') }}">Batal</a>
                        <button type="submit" name="action" value="draft" class="btn btn-outline" id="btn-draft">Simpan Draft</button>
                        <button type="submit" name="action" value="complete" class="btn btn-primary" id="btn-complete">Terima Barang</button>
                    </div>
                </div>
            </form>

            <div class="modal fade" id="confirmReceiveModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content" style="border-radius:16px;">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title" style="margin:0;">Konfirmasi Terima Barang</h5>
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
                                    <div id="m-short-wrap" class="d-none" style="color:#ef4444;"><span class="mono" id="m-short">0</span> Item Minus di PRD</div>
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
                            <button type="button" class="btn btn-ship-outline"
                                data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-ship-primary" id="btn-confirm-submit">Simpan</button>
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

                // Selalu muted — bukan focal point. Info kurang stok ada di summary "Stok Kurang".
                stockCell.classList.add('muted');
                stockCell.style.color = '';
                if (qty > 0) {
                    stockCell.textContent = fmtQty(sisa);
                    stockCell.title = `Stok PRD: ${fmtQty(stock)} | Terima: ${fmtQty(qty)} | Sisa: ${fmtQty(sisa)}`;
                } else {
                    stockCell.textContent = fmtQty(stock);
                    stockCell.title = '';
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

                const sItems = document.getElementById('sumItems');
                const sTotal = document.getElementById('sumTotal');
                const sShort = document.getElementById('sumShort');
                const sShortWrap = document.getElementById('sumShortWrap');
                if (sItems) sItems.textContent = rows.length;
                if (sTotal) sTotal.textContent = fmtQty(total);
                if (sShort) sShort.textContent = shortageCount;
                if (sShortWrap) sShortWrap.classList.toggle('d-none', shortageCount === 0);

                if (typeof saveState === 'function') saveState();
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
                        <div class="qty-stepper">
                            <button type="button" class="qty-btn qty-minus" tabindex="-1" aria-label="Kurangi">−</button>
                            <input data-name="qty_request" class="line-qty" type="number" inputmode="numeric" step="1" min="0" value="" placeholder="0">
                            <button type="button" class="qty-btn qty-plus" tabindex="-1" aria-label="Tambah">+</button>
                        </div>
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

                // ✅ FIX: dropdown pakai position:fixed, wajib di-patch per baris baru
                // kalau tidak, dropdown baris 2+ ke-render di luar viewport (seolah tidak muncul)
                patchFixedDropdown(tr);

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

            /* ── Simpan/pulihkan item terscan agar tidak hilang saat reload ── */
            const LS_KEY = 'rts_terima_jadi_lines_v1';

            function saveState() {
                try {
                    const data = [...tbody.querySelectorAll('tr')].map(tr => {
                        const id = (tr.querySelector('.js-item-suggest-id')?.value || '').trim();
                        const qty = num(tr.querySelector('.line-qty')?.value);
                        return id ? { item_id: id, qty_request: qty } : null;
                    }).filter(Boolean);
                    const dateVal = document.querySelector('[name="date"]')?.value || '';
                    localStorage.setItem(LS_KEY, JSON.stringify({ date: dateVal, lines: data }));
                } catch (_) {}
            }

            function loadState() {
                try {
                    const raw = localStorage.getItem(LS_KEY);
                    if (!raw) return null;
                    const obj = JSON.parse(raw);
                    return (obj && Array.isArray(obj.lines)) ? obj : null;
                } catch (_) { return null; }
            }

            function clearState() {
                try { localStorage.removeItem(LS_KEY); } catch (_) {}
            }

            function seedInitialRows() {
                // 1) Prioritas: hasil validasi server (old input)
                if (Array.isArray(oldLines) && oldLines.length > 0) {
                    oldLines.forEach(l => addRow(l));
                    return;
                }
                // 2) Pulihkan dari localStorage (belum sempat submit / ke-reload)
                const saved = loadState();
                if (saved && saved.lines.length > 0) {
                    const dateInput = document.querySelector('[name="date"]');
                    if (dateInput && saved.date) dateInput.value = saved.date;
                    saved.lines.forEach(l => addRow(l));
                    return;
                }
                // 3) Baris kosong default
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
                clearState(); // sudah disimpan ke server → jangan pulihkan lagi
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

                if (e.submitter && e.submitter.value === 'draft') {
                    // Jika draft, izinkan submit langsung (tanpa confirm modal)
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

            /* ─────────── SCAN barcode / kode item ─────────── */
            const scanInput  = document.getElementById('scanInput');
            const scanStatus = document.getElementById('scanStatus');

            function setScanStatus(msg, kind) {
                if (!scanStatus) return;
                scanStatus.textContent = msg || '';
                scanStatus.className = 'rts-scan-status' + (kind ? ' ' + kind : '');
            }

            function findEmptyRow() {
                for (const tr of tbody.querySelectorAll('tr')) {
                    const v = (tr.querySelector('.js-item-suggest-id')?.value || '').trim();
                    if (!v) return tr;
                }
                return null;
            }

            function playTone(type = 'ok') {
                if (!window.AudioContext && !window.webkitAudioContext) return;

                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                const ctx = new AudioCtx();

                const presets = {
                    ok: [
                        { freq: 2500, duration: 0.1, gap: 0 },
                    ],
                    error: [
                        { freq: 300, duration: 0.15, gap: 0 },
                        { freq: 300, duration: 0.2, gap: 0.05 },
                    ],
                };

                const notes = presets[type] || presets.ok;
                let cursor = ctx.currentTime;

                notes.forEach(note => {
                    cursor += Number(note.gap || 0);

                    const oscillator = ctx.createOscillator();
                    const gain = ctx.createGain();

                    oscillator.type = type === 'error' ? 'square' : 'sine';
                    oscillator.frequency.setValueAtTime(note.freq, cursor);

                    gain.gain.setValueAtTime(0.0001, cursor);
                    gain.gain.exponentialRampToValueAtTime(0.6, cursor + 0.01);
                    gain.gain.exponentialRampToValueAtTime(0.0001, cursor + Number(note.duration || 0.06));

                    oscillator.connect(gain);
                    gain.connect(ctx.destination);

                    oscillator.start(cursor);
                    oscillator.stop(cursor + Number(note.duration || 0.06) + 0.025);

                    cursor += Number(note.duration || 0.06);
                });

                window.setTimeout(() => {
                    try { ctx.close(); } catch (e) {}
                }, Math.ceil((cursor - ctx.currentTime) * 1000) + 180);
            }

            window.__rtsScan = function () {
                const code = (scanInput?.value || '').trim();
                if (!code) { scanInput?.focus(); return; }

                const up = code.toUpperCase();
                const it = items.find(x => String(x.code || '').toUpperCase() === up)
                        || items.find(x => String(x.code || '').toUpperCase().startsWith(up));

                if (!it) { 
                    playTone('error');
                    setScanStatus('❌ "' + code + '" tidak ditemukan (hanya barang jadi)', 'err'); 
                    return; 
                }

                const existingTr = findRowByItemId(it.id);
                if (existingTr) {
                    const q = existingTr.querySelector('.line-qty');
                    if (q) q.value = num(q.value) + 1;
                    updatePrdStock(existingTr);
                    flashRow(existingTr);
                } else {
                    const empty = findEmptyRow();
                    if (empty) setRowData(empty, { item_id: it.id, qty_request: 1 });
                    else addRow({ item_id: it.id, qty_request: 1 });
                }

                renderDashboard();
                playTone('ok');
                setScanStatus('✅ ' + String(it.code).toUpperCase() + ' ditambahkan', 'ok');
                scanInput.value = '';
                scanInput.focus();
            };

            /* ── Auto-fokus selalu di input scan (termasuk mobile) ── */
            function focusScan() {
                if (!scanInput) return;
                try { scanInput.focus({ preventScroll: true }); } catch (_) { scanInput.focus(); }
                scanInput.select?.();
            }

            // Balik ke scan saat tap/klik area kosong (bukan input/tombol/dropdown suggest)
            function refocusOnEmpty(e) {
                if (e.target.closest('input, textarea, select, button, a, label, .item-suggest-wrap, .modal, .qty-stepper')) return;
                focusScan();
            }
            document.addEventListener('click', refocusOnEmpty);
            document.addEventListener('touchend', refocusOnEmpty, { passive: true });

            // Mobile: fokus otomatis diblokir sebelum ada gesture → fokus pada interaksi pertama
            const isTouch = window.matchMedia('(hover: none)').matches || 'ontouchstart' in window;
            if (isTouch) {
                const firstFocus = () => {
                    focusScan();
                    document.removeEventListener('touchstart', firstFocus);
                    document.removeEventListener('pointerdown', firstFocus);
                };
                document.addEventListener('touchstart', firstFocus, { once: true, passive: true });
                document.addEventListener('pointerdown', firstFocus, { once: true });
            }

            // Select-all otomatis saat fokus input "Terima" → ketik langsung ganti qty
            tbody.addEventListener('focusin', (e) => {
                const el = e.target;
                if (el && el.classList && el.classList.contains('line-qty')) {
                    setTimeout(() => el.select(), 0);
                }
            });

            // Tombol +/- stepper pada kolom Terima
            tbody.addEventListener('click', (e) => {
                const btn = e.target.closest('.qty-minus, .qty-plus');
                if (!btn) return;
                const tr = btn.closest('tr');
                const inp = tr?.querySelector('.line-qty');
                if (!inp) return;
                const step = btn.classList.contains('qty-plus') ? 1 : -1;
                const next = Math.max(0, num(inp.value) + step);
                inp.value = next;
                inp.dispatchEvent(new Event('input', { bubbles: true }));
            });

            // Simpan juga saat tanggal diubah
            document.querySelector('[name="date"]')?.addEventListener('change', () => saveState());

            // init
            seedInitialRows();
            setTimeout(() => patchFixedDropdown(document), 0);
            setTimeout(focusScan, 60);

        })();
    </script>
@endsection
