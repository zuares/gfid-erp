{{-- resources/views/inventory/rts_stock_requests/create.blade.php --}}
@extends('layouts.app')

@section('title', 'RTS • Permintaan Stok')

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-main-strong: rgba(15, 118, 110, 1);
            --rts-main-soft: rgba(45, 212, 191, 0.14);

            --line: rgba(148, 163, 184, 0.28);
            --muted: rgba(100, 116, 139, 1);

            /* ✅ FORM TOKENS (fallback cross theme) */
            --input-bg: var(--background);
            --input-text: rgba(15, 23, 42, .92);
            --input-placeholder: rgba(100, 116, 139, .75);
            --input-border: rgba(148, 163, 184, .60);
            --input-border-hover: rgba(100, 116, 139, .70);
            --input-focus: rgba(45, 212, 191, .28);
            --input-focus-border: rgba(15, 118, 110, .85);
        }

        /* ✅ DARK MODE OVERRIDE */
        body[data-theme="dark"] {
            --muted: rgba(148, 163, 184, 1);

            --input-bg: rgba(2, 6, 23, .55);
            /* slightly translucent */
            --input-text: rgba(226, 232, 240, .92);
            /* slate-200 */
            --input-placeholder: rgba(148, 163, 184, .65);
            --input-border: rgba(148, 163, 184, .35);
            --input-border-hover: rgba(148, 163, 184, .50);
            --input-focus: rgba(45, 212, 191, .16);
            --input-focus-border: rgba(45, 212, 191, .65);
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
            position: relative;
            z-index: 0;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, .08) 0,
                    rgba(45, 212, 191, .10) 28%,
                    #f9fafb 70%);
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(15, 23, 42, .85) 0,
                    rgba(2, 6, 23, 1) 65%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .22);
            box-shadow: 0 8px 24px rgba(15, 23, 42, .06), 0 0 0 1px rgba(15, 23, 42, .02);
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        body[data-theme="dark"] .card {
            border-color: rgba(148, 163, 184, .20);
            box-shadow: 0 16px 36px rgba(0, 0, 0, .35), 0 0 0 1px rgba(0, 0, 0, .20);
        }

        .card-header {
            padding: 1rem 1.1rem .75rem;
            border-bottom: 1px solid rgba(148, 163, 184, .22);
        }

        body[data-theme="dark"] .card-header {
            border-bottom-color: rgba(148, 163, 184, .16);
        }

        .card-body {
            padding: .85rem 1.1rem 1rem;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: flex-start;
        }

        .title {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--text, inherit);
        }

        .sub {
            font-size: .85rem;
            color: var(--muted);
            margin-top: .2rem;
            max-width: 46rem;
        }

        .hint {
            font-size: .78rem;
            color: rgba(148, 163, 184, 1);
            margin-top: .35rem;
        }

        body[data-theme="dark"] .hint {
            color: rgba(148, 163, 184, .95);
        }

        /* ✅ softer bold */
        .sub strong,
        .section-note strong,
        .banner strong {
            font-weight: 650;
            /* softer than 700 */
            color: inherit;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .18rem .6rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .55);
            background: color-mix(in srgb, var(--card) 80%, var(--rts-main-soft));
            font-size: .75rem;
        }

        body[data-theme="dark"] .chip {
            border-color: rgba(148, 163, 184, .35);
            background: color-mix(in srgb, var(--card) 86%, rgba(45, 212, 191, .10));
        }

        .chip .code {
            font-weight: 650;
            font-variant-numeric: tabular-nums;
        }

        .btn-link {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .8rem;
            text-decoration: none;
            color: rgba(100, 116, 139, 1);
        }

        body[data-theme="dark"] .btn-link {
            color: rgba(148, 163, 184, 1);
        }

        .btn-link:hover {
            color: rgba(30, 64, 175, 1);
        }

        body[data-theme="dark"] .btn-link:hover {
            color: rgba(147, 197, 253, 1);
        }

        .banner {
            margin-top: .65rem;
            padding: .6rem .75rem;
            border-radius: 12px;
            border: 1px solid rgba(59, 130, 246, .25);
            background: rgba(59, 130, 246, .06);
            color: rgba(30, 64, 175, 1);
            font-size: .82rem;
        }

        body[data-theme="dark"] .banner {
            border-color: rgba(59, 130, 246, .28);
            background: rgba(59, 130, 246, .08);
            color: rgba(191, 219, 254, 1);
        }

        .banner.warn {
            border-color: rgba(234, 179, 8, .45);
            background: rgba(234, 179, 8, .10);
            color: rgba(133, 77, 14, 1);
        }

        body[data-theme="dark"] .banner.warn {
            border-color: rgba(234, 179, 8, .35);
            background: rgba(234, 179, 8, .10);
            color: rgba(253, 230, 138, 1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: .75rem 1rem;
            margin-top: .65rem;
        }

        .fg {
            display: flex;
            flex-direction: column;
            gap: .25rem;
        }

        .fg label {
            font-size: .8rem;
            font-weight: 550;
            color: rgba(71, 85, 105, 1);
        }

        body[data-theme="dark"] .fg label {
            color: rgba(203, 213, 225, 1);
        }

        /* ✅ INPUT FIX (dark/light readability) */
        .control,
        input[type="date"].control,
        input[type="number"].control,
        textarea.control,
        select.control {
            border-radius: .65rem;
            border: 1px solid var(--input-border);
            padding: .42rem .6rem;
            font-size: .85rem;
            background: var(--input-bg);
            color: var(--input-text);
            outline: none;
        }

        .control::placeholder {
            color: var(--input-placeholder);
        }

        .control:hover {
            border-color: var(--input-border-hover);
        }

        .control:focus {
            border-color: var(--input-focus-border);
            box-shadow: 0 0 0 3px var(--input-focus);
        }

        .control[readonly] {
            background: color-mix(in srgb, var(--input-bg) 92%, rgba(148, 163, 184, .06));
            color: var(--input-text);
            opacity: 1;
        }

        textarea.control {
            min-height: 70px;
            resize: vertical;
        }

        .err {
            font-size: .75rem;
            color: #ef4444;
            margin-top: .1rem;
        }

        .is-err {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 1px rgba(239, 68, 68, .2) !important;
        }

        .section-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            flex-wrap: wrap;
            margin-top: .6rem;
        }

        .section-title {
            font-size: .78rem;
            font-weight: 650;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: rgba(100, 116, 139, 1);
        }

        body[data-theme="dark"] .section-title {
            color: rgba(148, 163, 184, 1);
        }

        .section-note {
            font-size: .78rem;
            color: rgba(100, 116, 139, 1);
            max-width: 46rem;
        }

        body[data-theme="dark"] .section-note {
            color: rgba(148, 163, 184, 1);
        }

        /* ✅ dropdown item-suggest: jangan ketutup */
        .table-wrap {
            margin-top: .55rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            overflow: visible;
            /* ✅ penting */
            position: relative;
            z-index: 1;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
        }

        .table thead {
            background: rgba(15, 23, 42, .035);
        }

        body[data-theme="dark"] .table thead {
            background: rgba(148, 163, 184, .06);
        }

        .table th,
        .table td {
            padding: .5rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, .22);
            vertical-align: middle;
            overflow: visible;
            /* ✅ penting */
        }

        body[data-theme="dark"] .table th,
        body[data-theme="dark"] .table td {
            border-bottom-color: rgba(148, 163, 184, .14);
        }

        .table th {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #6b7280;
            text-align: left;
        }

        body[data-theme="dark"] .table th {
            color: rgba(148, 163, 184, 1);
        }

        .line-row {
            position: relative;
            z-index: 1;
        }

        .line-row.is-open {
            z-index: 999;
            /* ✅ saat dropdown dibuka */
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono";
        }

        .row-no {
            color: #64748b;
            font-weight: 650;
        }

        body[data-theme="dark"] .row-no {
            color: rgba(148, 163, 184, 1);
        }

        .stock-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .16rem .55rem;
            border-radius: 999px;
            border: 1px dashed rgba(148, 163, 184, .8);
            background: rgba(15, 23, 42, .02);
            font-size: .75rem;
            white-space: nowrap;
        }

        body[data-theme="dark"] .stock-pill {
            border-color: rgba(148, 163, 184, .35);
            background: rgba(148, 163, 184, .06);
        }

        .stock-pill .lbl {
            color: rgba(100, 116, 139, 1);
        }

        body[data-theme="dark"] .stock-pill .lbl {
            color: rgba(148, 163, 184, 1);
        }

        .stock-pill .val {
            font-weight: 650;
            color: rgba(15, 23, 42, .9);
        }

        body[data-theme="dark"] .stock-pill .val {
            color: rgba(226, 232, 240, .92);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: .82rem;
            font-weight: 650;
            padding: .38rem .95rem;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--rts-main-strong);
            color: #ecfeff;
            box-shadow: 0 10px 24px rgba(15, 118, 110, .22);
        }

        .btn-primary:hover {
            filter: brightness(.97);
            color: #f0fdfa;
        }

        .btn-outline {
            background: transparent;
            border-color: rgba(148, 163, 184, .85);
            color: rgba(15, 23, 42, .9);
        }

        body[data-theme="dark"] .btn-outline {
            color: rgba(226, 232, 240, .92);
            border-color: rgba(148, 163, 184, .35);
        }

        .btn-outline:hover {
            background: rgba(15, 23, 42, .03);
        }

        body[data-theme="dark"] .btn-outline:hover {
            background: rgba(148, 163, 184, .06);
        }

        .btn-ghost {
            background: transparent;
            border-color: transparent;
            color: rgba(148, 163, 184, 1);
        }

        .btn-ghost:hover {
            color: rgba(248, 113, 113, 1);
        }

        .btn-icon {
            padding-inline: .5rem;
        }

        .actions {
            margin-top: .75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .actions .left,
        .actions .right {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .micro {
            font-size: .78rem;
            color: rgba(100, 116, 139, 1);
        }

        body[data-theme="dark"] .micro {
            color: rgba(148, 163, 184, 1);
        }

        /* =========================
               ✅ ITEM SUGGEST DROPDOWN FIX
               (nama class mungkin beda; ini dibuat general)
            ========================= */
        .item-suggest-wrap {
            position: relative;
            z-index: 10;
        }

        /* input item suggest jika bukan pakai .control */
        .item-suggest-wrap input[type="text"],
        .item-suggest-wrap .js-item-suggest-input {
            width: 100%;
            border-radius: .65rem !important;
            border: 1px solid var(--input-border) !important;
            background: var(--input-bg) !important;
            color: var(--input-text) !important;
            padding: .42rem .6rem !important;
            font-size: .85rem !important;
            outline: none !important;
        }

        .item-suggest-wrap input[type="text"]::placeholder,
        .item-suggest-wrap .js-item-suggest-input::placeholder {
            color: var(--input-placeholder) !important;
        }

        .item-suggest-wrap input[type="text"]:focus,
        .item-suggest-wrap .js-item-suggest-input:focus {
            border-color: var(--input-focus-border) !important;
            box-shadow: 0 0 0 3px var(--input-focus) !important;
        }

        /* dropdown container (sesuaikan jika class kamu beda) */
        .item-suggest-dropdown,
        .item-suggest-wrap .dropdown,
        .item-suggest-wrap .suggest-dropdown,
        .item-suggest-wrap .suggest-panel {
            position: absolute !important;
            left: 0 !important;
            right: 0 !important;
            top: calc(100% + 6px) !important;
            z-index: 9999 !important;
            background: var(--card) !important;
            border: 1px solid rgba(148, 163, 184, .35) !important;
            border-radius: 12px !important;
            box-shadow: 0 18px 38px rgba(15, 23, 42, .20) !important;
            overflow: hidden !important;
        }

        body[data-theme="dark"] .item-suggest-dropdown,
        body[data-theme="dark"] .item-suggest-wrap .dropdown,
        body[data-theme="dark"] .item-suggest-wrap .suggest-dropdown,
        body[data-theme="dark"] .item-suggest-wrap .suggest-panel {
            border-color: rgba(148, 163, 184, .22) !important;
            box-shadow: 0 22px 48px rgba(0, 0, 0, .50) !important;
        }

        .item-suggest-list,
        .item-suggest-dropdown ul,
        .item-suggest-wrap .dropdown ul {
            max-height: 280px !important;
            overflow: auto !important;
        }

        .item-suggest-item,
        .item-suggest-dropdown li,
        .item-suggest-wrap .dropdown li {
            padding: .55rem .7rem !important;
            cursor: pointer !important;
            border-bottom: 1px solid rgba(148, 163, 184, .16) !important;
            color: var(--input-text) !important;
            background: transparent !important;
        }

        .item-suggest-item:hover,
        .item-suggest-dropdown li:hover,
        .item-suggest-wrap .dropdown li:hover {
            background: rgba(45, 212, 191, .10) !important;
        }

        .item-suggest-meta,
        .item-suggest-item small {
            color: var(--muted) !important;
        }

        /* mobile: table jadi cards */
        @media (max-width: 768px) {
            .page-wrap {
                padding-inline: .6rem;
            }

            .card-header,
            .card-body {
                padding-inline: .9rem;
            }

            .form-grid {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }

            .table thead {
                display: none;
            }

            .table-wrap {
                overflow: visible;
                border-radius: 12px;
            }

            .table,
            .table tbody,
            .table tr,
            .table td {
                display: block;
                width: 100%;
            }

            .table tr {
                border-bottom: 1px solid rgba(148, 163, 184, .22);
                padding: .65rem .6rem;
                background: color-mix(in srgb, var(--card) 96%, rgba(15, 23, 42, .02));
            }

            body[data-theme="dark"] .table tr {
                background: color-mix(in srgb, var(--card) 92%, rgba(148, 163, 184, .06));
                border-bottom-color: rgba(148, 163, 184, .14);
            }

            .table tr:last-child {
                border-bottom: none;
            }

            .table td {
                border-bottom: none;
                padding: .22rem 0;
                display: flex;
                justify-content: space-between;
                gap: .7rem;
                overflow: visible;
            }

            .table td::before {
                content: attr(data-label);
                flex: 0 0 42%;
                max-width: 52%;
                color: #64748b;
                font-weight: 500;
            }

            body[data-theme="dark"] .table td::before {
                color: rgba(148, 163, 184, 1);
            }

            .td-no {
                justify-content: flex-start;
                gap: .5rem;
            }

            .td-no::before {
                content: '#';
                flex: 0 0 auto;
                color: #94a3b8;
            }

            /* dropdown tetap tampil di card mode */
            .item-suggest-dropdown,
            .item-suggest-wrap .dropdown,
            .item-suggest-wrap .suggest-dropdown,
            .item-suggest-wrap .suggest-panel {
                position: absolute !important;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $rawDate = old('date', $prefillDate ?? (request('date') ?? now()->toDateString()));
        try {
            $dateValue = \Illuminate\Support\Carbon::parse($rawDate)->toDateString();
        } catch (\Throwable $e) {
            $dateValue = now()->toDateString();
        }

        $isEditSubmitted = isset($prefillRequest) && ($prefillRequest->status ?? null) === 'submitted';
        $notesValue = old('notes', $prefillRequest->notes ?? '');
        $formLines = old('lines', $prefillLines ?? [['item_id' => null, 'qty_request' => null]]);
    @endphp

    <div class="page-wrap">
        <div class="card">
            <div class="card-header">
                <div class="topbar">
                    <div>
                        <a href="{{ route('rts.stock-requests.index') }}" class="btn-link">← Kembali</a>

                        <div class="title mt-1">RTS • Buat / Ubah Permintaan Stok</div>
                        <div class="sub">
                            Isi barang yang ingin diminta dari <strong>WH-PRD</strong> ke <strong>WH-RTS</strong>.
                            Jika permintaan hari ini masih <em>Menunggu PRD</em>, menyimpan halaman ini akan
                            <strong>memperbarui daftar item</strong>.
                        </div>
                        <div class="hint">
                            Alur: RTS buat permintaan → PRD kirim ke Transit → RTS terima dari Transit.
                        </div>

                        @if ($isEditSubmitted)
                            <div class="banner warn">
                                Sedang mengubah permintaan tanggal
                                <span class="mono">{{ $prefillRequest->date?->format('d M Y') }}</span>
                                (status: <strong>Menunggu PRD</strong>). Menyimpan akan mengganti isi permintaan itu.
                            </div>
                        @elseif(isset($prefillRequest))
                            <div class="banner">
                                Form terisi dari permintaan terakhir (tanggal
                                <span class="mono">{{ $prefillRequest->date?->format('d M Y') }}</span>).
                                Silakan sesuaikan.
                            </div>
                        @endif
                    </div>

                    <div class="d-flex flex-col gap-1" style="align-items:flex-end;">
                        <div class="chip">
                            <span class="code">{{ $prdWarehouse->code }}</span>
                            <span>{{ $prdWarehouse->name }}</span>
                        </div>
                        <div style="opacity:.7;">→</div>
                        <div class="chip">
                            <span class="code">{{ $rtsWarehouse->code }}</span>
                            <span>{{ $rtsWarehouse->name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <form id="rts-stock-request-form" method="POST" action="{{ route('rts.stock-requests.store') }}">
                @csrf

                {{-- ✅ backup hidden --}}
                <input type="hidden" name="source_warehouse_id" value="{{ $prdWarehouse->id }}">
                <input type="hidden" name="destination_warehouse_id" value="{{ $rtsWarehouse->id }}">

                <div class="card-body">

                    <div class="form-grid">
                        <div class="fg" style="grid-column: span 3 / span 3;">
                            <label for="date">Tanggal</label>
                            <input type="date" id="date" name="date"
                                class="control @error('date') is-err @enderror" value="{{ $dateValue }}">
                            @error('date')
                                <div class="err">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="fg" style="grid-column: span 4 / span 4;">
                            <label>Gudang Asal</label>
                            <input type="text" class="control"
                                value="{{ $prdWarehouse->code }} — {{ $prdWarehouse->name }}" readonly>
                        </div>

                        <div class="fg" style="grid-column: span 5 / span 5;">
                            <label>Gudang Tujuan</label>
                            <input type="text" class="control"
                                value="{{ $rtsWarehouse->code }} — {{ $rtsWarehouse->name }}" readonly>
                        </div>

                        <div class="fg" style="grid-column: span 12 / span 12;">
                            <label for="notes">Catatan (opsional)</label>
                            <textarea id="notes" name="notes" class="control"
                                placeholder="Contoh: kebutuhan stok untuk promo / isi rak / dsb...">{{ $notesValue }}</textarea>
                            @error('notes')
                                <div class="err">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="section-row">
                        <div>
                            <div class="section-title">Daftar barang</div>
                            <div class="section-note">
                                Pilih FG dan isi jumlah <strong>yang diminta</strong>.
                                Stok PRD hanya informasi (PRD boleh kirim bertahap).
                            </div>
                        </div>
                        <div class="micro">
                            Tip: klik 🔍 untuk lihat stok di semua gudang.
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th style="width: 42%;">Barang (FG)</th>
                                    <th style="width: 26%;">Stok PRD</th>
                                    <th style="width: 18%;">Jumlah diminta</th>
                                    <th style="width: 70px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="lines-body">
                                @php use App\Models\Item; @endphp

                                @foreach ($formLines as $i => $lineData)
                                    @php
                                        $itemId = $lineData['item_id'] ?? null;
                                        $selectedItem = $itemId ? $finishedGoodsItems->firstWhere('id', $itemId) : null;

                                        if (!$selectedItem && $itemId) {
                                            $selectedItem = Item::select('id', 'code', 'name')->find($itemId);
                                        }

                                        $displayValue = $selectedItem
                                            ? trim(($selectedItem->code ?? '') . ' — ' . ($selectedItem->name ?? ''))
                                            : '';
                                    @endphp

                                    <tr class="line-row" data-row-index="{{ $i }}">
                                        <td class="mono td-no" data-label="#">
                                            <span class="row-no">{{ $i + 1 }}</span>
                                        </td>

                                        <td data-label="Barang">
                                            <x-item-suggest :id-name="'lines[' . $i . '][item_id]'" :items="$finishedGoodsItems" type="finished_good"
                                                :extra-params="['warehouse_id' => $prdWarehouse->id]" placeholder="Ketik kode / nama FG" :display-value="$displayValue"
                                                :id-value="(string) ($itemId ?? '')" />
                                            @error('lines.' . $i . '.item_id')
                                                <div class="err">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        <td data-label="Stok PRD">
                                            <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;">
                                                <span class="stock-pill">
                                                    <span class="lbl">Stok:</span>
                                                    <span class="val mono stock-display" data-available="0">-</span>
                                                    <span class="lbl">pcs</span>
                                                </span>

                                                <button type="button" class="btn btn-ghost btn-icon btn-show-summary"
                                                    title="Lihat stok semua gudang"
                                                    data-row-index="{{ $i }}">🔍</button>
                                            </div>
                                        </td>

                                        <td data-label="Jumlah diminta">
                                            <input type="number" min="0" step="1"
                                                name="lines[{{ $i }}][qty_request]"
                                                class="control qty-input js-next-focus @error('lines.' . $i . '.qty_request') is-err @enderror"
                                                data-row-index="{{ $i }}"
                                                value="{{ $lineData['qty_request'] ?? '' }}" placeholder="0">
                                            <div class="err qty-warning" style="display:none;"></div>
                                            @error('lines.' . $i . '.qty_request')
                                                <div class="err">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        <td data-label="Aksi" style="text-align:right;">
                                            <button type="button" class="btn btn-ghost remove-row-btn"
                                                data-row-index="{{ $i }}" title="Hapus baris">✕</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="actions">
                        <div class="left">
                            <button type="button" class="btn btn-outline" id="add-line-btn">+ Tambah baris</button>
                        </div>
                        <div class="right">
                            <button type="submit" class="btn btn-primary">Simpan Permintaan</button>
                        </div>
                    </div>

                    <div class="micro mt-2" style="margin-top:.65rem;">
                        Setelah disimpan, kamu akan diarahkan ke halaman <strong>Detail</strong>.
                    </div>

                </div>
            </form>
        </div>

        {{-- MODAL --}}
        <div class="rts-modal-backdrop" id="stock-summary-backdrop"
            style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:3000;align-items:center;justify-content:center;padding:1rem;">
            <div class="rts-modal-panel"
                style="background:var(--card);border-radius:18px;border:1px solid rgba(148,163,184,.45);max-width:520px;width:100%;max-height:80vh;overflow:hidden;">
                <div
                    style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;padding:.75rem .9rem;border-bottom:1px solid rgba(148,163,184,.35);">
                    <div style="font-weight:650;">Ringkasan stok per gudang</div>
                    <button type="button" id="stock-summary-close"
                        style="width:28px;height:28px;border-radius:999px;border:none;background:transparent;color:rgba(148,163,184,1);font-size:1.2rem;cursor:pointer;">×</button>
                </div>
                <div style="padding:.6rem .9rem;max-height:calc(80vh - 48px);overflow:auto;font-size:.82rem;">
                    <div id="stock-summary-content" style="color:rgba(100,116,139,1);">
                        Klik 🔍 di baris item untuk melihat stok di semua gudang.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@php
    $itemSuggestTemplate = view('components.item-suggest', [
        'idName' => 'lines[__INDEX__][item_id]',
        'items' => $finishedGoodsItems,
        'type' => 'finished_good',
        'extraParams' => ['warehouse_id' => $prdWarehouse->id],
        'placeholder' => 'Ketik kode / nama FG',
        'displayValue' => '',
        'idValue' => '',
    ])->render();
@endphp

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sourceWarehouseId = {{ $prdWarehouse->id }};
            const availableUrl = @json(route('api.stock.available'));
            const summaryUrl = @json(route('api.stock.summary'));
            const itemSuggestTemplate = @json($itemSuggestTemplate);

            const linesBody = document.getElementById('lines-body');
            const addLineBtn = document.getElementById('add-line-btn');

            const backdrop = document.getElementById('stock-summary-backdrop');
            const closeBtn = document.getElementById('stock-summary-close');
            const summaryContent = document.getElementById('stock-summary-content');

            if (!linesBody) return;

            let currentIndex = (function() {
                const lastRow = linesBody.querySelector('tr.line-row:last-child');
                return lastRow ? parseInt(lastRow.getAttribute('data-row-index')) + 1 : 0;
            })();

            function createLineRow(index) {
                const tr = document.createElement('tr');
                tr.classList.add('line-row');
                tr.setAttribute('data-row-index', index);

                const itemSuggestHtml = itemSuggestTemplate.replace(/__INDEX__/g, index);

                tr.innerHTML = `
            <td class="mono td-no" data-label="#">
                <span class="row-no">${index + 1}</span>
            </td>
            <td data-label="Barang">
                ${itemSuggestHtml}
            </td>
            <td data-label="Stok PRD">
                <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;">
                    <span class="stock-pill">
                        <span class="lbl">Stok:</span>
                        <span class="val mono stock-display" data-available="0">-</span>
                        <span class="lbl">pcs</span>
                    </span>
                    <button type="button"
                        class="btn btn-ghost btn-icon btn-show-summary"
                        title="Lihat stok semua gudang"
                        data-row-index="${index}">🔍</button>
                </div>
            </td>
            <td data-label="Jumlah diminta">
                <input type="number" min="0" step="1"
                    name="lines[${index}][qty_request]"
                    class="control qty-input js-next-focus"
                    data-row-index="${index}"
                    placeholder="0">
                <div class="err qty-warning" style="display:none;"></div>
            </td>
            <td data-label="Aksi" style="text-align:right;">
                <button type="button" class="btn btn-ghost remove-row-btn"
                    data-row-index="${index}" title="Hapus baris">✕</button>
            </td>
        `;

                if (window.initItemSuggestInputs) window.initItemSuggestInputs(tr);
                return tr;
            }

            function renumberRows() {
                const rows = linesBody.querySelectorAll('tr.line-row');
                rows.forEach((row, idx) => {
                    const num = row.querySelector('.row-no');
                    if (num) num.textContent = idx + 1;
                });
            }

            function findRowByIndex(rowIndex) {
                return linesBody.querySelector(`tr.line-row[data-row-index="${rowIndex}"]`);
            }

            async function fetchAvailableStock(rowIndex) {
                const row = findRowByIndex(rowIndex);
                if (!row) return;

                const hiddenId = row.querySelector('.js-item-suggest-id');
                const itemId = hiddenId ? hiddenId.value : '';

                const stockSpan = row.querySelector('.stock-display');
                const warningEl = row.querySelector('.qty-warning');

                if (!stockSpan) return;

                if (!itemId) {
                    stockSpan.textContent = '-';
                    stockSpan.dataset.available = '0';
                    if (warningEl) warningEl.style.display = 'none';
                    return;
                }

                stockSpan.textContent = '…';
                stockSpan.dataset.available = '0';
                if (warningEl) warningEl.style.display = 'none';

                try {
                    const url = new URL(availableUrl, window.location.origin);
                    url.searchParams.set('warehouse_id', String(sourceWarehouseId));
                    url.searchParams.set('item_id', String(itemId));

                    const res = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error('Gagal mengambil stok');

                    const data = await res.json();
                    const available = typeof data.available === 'number' ? data.available : parseFloat(data
                        .available || 0);

                    stockSpan.textContent = isNaN(available) ? '-' : String(available);
                    stockSpan.dataset.available = String(isNaN(available) ? 0 : available);
                } catch (e) {
                    console.error(e);
                    stockSpan.textContent = 'ERR';
                    stockSpan.dataset.available = '0';
                    if (warningEl) {
                        warningEl.style.display = 'block';
                        warningEl.textContent = 'Gagal mengambil stok. Coba lagi.';
                    }
                }
            }

            async function showStockSummary(rowIndex) {
                if (!backdrop || !summaryContent) return;

                const row = findRowByIndex(rowIndex);
                const hiddenId = row ? row.querySelector('.js-item-suggest-id') : null;
                const itemId = hiddenId ? hiddenId.value : '';

                backdrop.style.display = 'flex';

                if (!itemId) {
                    summaryContent.innerHTML =
                        `<div style="color:rgba(100,116,139,1)">Pilih item dulu, lalu klik 🔍 lagi.</div>`;
                    return;
                }

                summaryContent.innerHTML = `<div style="color:rgba(100,116,139,1)">Mengambil data stok…</div>`;

                try {
                    const url = new URL(summaryUrl, window.location.origin);
                    url.searchParams.set('item_id', String(itemId));

                    const res = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error('Gagal mengambil summary stok');

                    const data = await res.json();
                    const item = data.item || {};
                    const warehouses = Array.isArray(data.warehouses) ? data.warehouses : [];

                    const itemLabel = (item.code || item.name) ?
                        `${item.code ?? ''}${item.name ? ' — ' + item.name : ''}` :
                        `Item ID: ${itemId}`;

                    if (!warehouses.length) {
                        summaryContent.innerHTML =
                            `<div style="color:rgba(100,116,139,1)">Belum ada data stok untuk item ini.</div>`;
                        return;
                    }

                    const rowsHtml = warehouses.map(w => `
                <tr>
                    <td class="mono">${w.code ?? ''}</td>
                    <td>${w.name ?? ''}</td>
                    <td class="mono" style="text-align:right;">${w.on_hand ?? 0}</td>
                    <td class="mono" style="text-align:right;">${w.reserved ?? 0}</td>
                    <td class="mono" style="text-align:right;font-weight:650;">${w.available ?? 0}</td>
                </tr>
            `).join('');

                    summaryContent.innerHTML = `
                <div style="font-size:.78rem;color:rgba(100,116,139,1);margin-bottom:.25rem;">Item</div>
                <div style="font-weight:650;margin-bottom:.55rem;">${itemLabel}</div>

                <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
                    <thead>
                        <tr style="border-bottom:1px solid rgba(148,163,184,.25);color:#6b7280;text-transform:uppercase;letter-spacing:.06em;font-size:.74rem;">
                            <th style="padding:.35rem .3rem;text-align:left;">Kode</th>
                            <th style="padding:.35rem .3rem;text-align:left;">Gudang</th>
                            <th style="padding:.35rem .3rem;text-align:right;">On hand</th>
                            <th style="padding:.35rem .3rem;text-align:right;">Reserved</th>
                            <th style="padding:.35rem .3rem;text-align:right;">Tersedia</th>
                        </tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            `;
                } catch (e) {
                    console.error(e);
                    summaryContent.innerHTML =
                        `<div style="color:#ef4444;">Gagal mengambil summary stok. Coba lagi.</div>`;
                }
            }

            addLineBtn?.addEventListener('click', function() {
                const row = createLineRow(currentIndex++);
                linesBody.appendChild(row);
                renumberRows();
            });

            // ✅ tambah class is-open agar z-index row naik saat user fokus item-suggest
            linesBody.addEventListener('focusin', function(e) {
                const row = e.target.closest('tr.line-row');
                if (row) row.classList.add('is-open');
            });

            linesBody.addEventListener('focusout', function(e) {
                const row = e.target.closest('tr.line-row');
                if (row) {
                    // delay dikit supaya click dropdown tidak langsung close
                    setTimeout(() => row.classList.remove('is-open'), 120);
                }
            });

            linesBody.addEventListener('change', function(e) {
                const t = e.target;
                if (t.classList.contains('js-item-suggest-id')) {
                    const row = t.closest('tr.line-row');
                    if (row) fetchAvailableStock(row.getAttribute('data-row-index'));
                }
            });

            linesBody.addEventListener('click', function(e) {
                const removeBtn = e.target.closest('.remove-row-btn');
                const summaryBtn = e.target.closest('.btn-show-summary');

                if (removeBtn) {
                    const rowIndex = removeBtn.getAttribute('data-row-index');
                    const row = findRowByIndex(rowIndex);
                    if (!row) return;

                    if (linesBody.querySelectorAll('tr.line-row').length <= 1) {
                        const wrap = row.querySelector('.item-suggest-wrap');
                        if (wrap) {
                            const input = wrap.querySelector('.js-item-suggest-input');
                            const hiddenId = wrap.querySelector('.js-item-suggest-id');
                            const hiddenCat = wrap.querySelector('.js-item-suggest-category');
                            if (input) input.value = '';
                            if (hiddenId) hiddenId.value = '';
                            if (hiddenCat) hiddenCat.value = '';
                        }
                        const qtyInput = row.querySelector('.qty-input');
                        if (qtyInput) qtyInput.value = '';

                        const stockSpan = row.querySelector('.stock-display');
                        if (stockSpan) {
                            stockSpan.textContent = '-';
                            stockSpan.dataset.available = '0';
                        }
                        return;
                    }

                    row.remove();
                    renumberRows();
                    return;
                }

                if (summaryBtn) {
                    const rowIndex = summaryBtn.getAttribute('data-row-index');
                    showStockSummary(rowIndex);
                }
            });

            closeBtn?.addEventListener('click', () => {
                if (backdrop) backdrop.style.display = 'none';
            });

            backdrop?.addEventListener('click', (e) => {
                if (e.target === backdrop) backdrop.style.display = 'none';
            });

            if (window.initItemSuggestInputs) window.initItemSuggestInputs(linesBody);

            (function initExisting() {
                const existingHiddenIds = linesBody.querySelectorAll('.js-item-suggest-id');
                existingHiddenIds.forEach(h => {
                    if (h.value) {
                        const row = h.closest('tr.line-row');
                        if (row) fetchAvailableStock(row.getAttribute('data-row-index'));
                    }
                });
            })();
        });
    </script>
@endpush
