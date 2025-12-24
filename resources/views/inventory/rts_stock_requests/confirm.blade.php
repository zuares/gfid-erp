{{-- resources/views/inventory/rts_stock_requests/confirm.blade.php --}}
@extends('layouts.app')

@section('title', 'RTS Receive • ' . $stockRequest->code)

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-soft: rgba(45, 212, 191, .14);
            --danger-soft: rgba(239, 68, 68, .12);
            --warn-soft: rgba(245, 158, 11, .14);
        }

        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: .85rem .85rem 4.5rem;
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
            border: 1px solid rgba(148, 163, 184, .30);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, .06),
                0 0 0 1px rgba(15, 23, 42, .03);
            padding: .8rem .85rem;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .meta {
            font-size: .82rem;
            opacity: .82;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            flex-wrap: wrap;
            margin-bottom: .75rem;
        }

        .title {
            margin: 0;
            font-size: 1.12rem;
            font-weight: 900;
            letter-spacing: -.01em;
        }

        .sub {
            margin-top: .18rem;
        }

        .actions {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-primary {
            background: var(--rts-main);
            border-color: var(--rts-main);
            color: #022c22;
        }

        .btn-outline {
            border: 1px solid rgba(148, 163, 184, .45);
            background: transparent;
        }

        .note {
            border: 1px solid rgba(148, 163, 184, .25);
            border-radius: 12px;
            padding: .65rem .75rem;
            background: rgba(148, 163, 184, .08);
            font-size: .85rem;
            opacity: .92;
            white-space: pre-wrap;
        }

        .line {
            border-top: 1px dashed rgba(148, 163, 184, .35);
            margin: .7rem 0;
        }

        /* =========================
                                                   CLEAN SOFT BADGES
                                                ========================== */
        .badges {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            margin-top: .38rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: .38rem;
            padding: .18rem .55rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 850;
            white-space: nowrap;
            border: 1px solid transparent;
            line-height: 1;
        }

        .badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: currentColor;
            opacity: .75;
        }

        .badge.info {
            background: rgba(148, 163, 184, .14);
            border-color: rgba(148, 163, 184, .30);
            color: rgba(51, 65, 85, 1);
        }

        .badge.ok {
            background: rgba(16, 185, 129, .16);
            border-color: rgba(16, 185, 129, .35);
            color: rgba(4, 120, 87, 1);
        }

        .badge.warn {
            background: rgba(245, 158, 11, .18);
            border-color: rgba(245, 158, 11, .40);
            color: rgba(146, 64, 14, 1);
        }

        .badge.danger {
            background: rgba(239, 68, 68, .16);
            border-color: rgba(239, 68, 68, .40);
            color: rgba(153, 27, 27, 1);
        }

        .badge.muted {
            background: rgba(100, 116, 139, .16);
            border-color: rgba(100, 116, 139, .35);
            color: rgba(51, 65, 85, 1);
        }

        .badge.is-hidden {
            display: none !important;
        }

        /* =========================
                                                   TABLE (DESKTOP) + STACK (MOBILE)
                                                ========================== */
        .table-wrap {
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 12px;
            max-height: 40vh;
            /* ✅ max tinggi daftar item */
            overflow-y: auto;
            /* ✅ scroll vertikal (kanan) */
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            background: rgba(15, 23, 42, .01);
        }

        /* custom scrollbar (WebKit) */
        .table-wrap::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-wrap::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, .04);
            border-radius: 999px;
        }

        .table-wrap::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, .75);
            border-radius: 999px;
        }

        .tbl {
            width: 100%;
            border-collapse: collapse;
            min-width: 860px;
        }

        .tbl th,
        .tbl td {
            padding: .55rem .55rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            vertical-align: top;
            font-size: .9rem;
        }

        .tbl thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: var(--card);
            font-size: .78rem;
            letter-spacing: .02em;
            text-transform: uppercase;
            opacity: 1;
            /* ✅ tidak transparan lagi */
            border-bottom: 1px solid rgba(148, 163, 184, .35);
        }

        .tbl tbody tr:hover {
            background: rgba(148, 163, 184, .05);
        }

        .no {
            width: 44px;
            opacity: .75;
        }

        .td-right {
            text-align: right;
            white-space: nowrap;
        }

        .td-center {
            text-align: center;
            white-space: nowrap;
        }

        .item-cell {
            min-width: 420px;
        }

        .item-code {
            font-weight: 900;
        }

        .item-name {
            margin-top: .10rem;
            font-size: .82rem;
            opacity: .82;
        }

        .num {
            width: 160px;
            max-width: 100%;
            padding: .45rem .55rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: var(--card);
            color: inherit;
        }

        /* center khusus untuk qty receive */
        .num.num-center {
            text-align: center;
        }

        .num.is-disabled {
            opacity: .6;
            cursor: not-allowed;
            pointer-events: none;
        }

        .hint {
            font-size: .78rem;
            opacity: .75;
            margin-top: .22rem;
        }

        .err {
            margin-top: .35rem;
            font-size: .82rem;
            color: rgba(239, 68, 68, 1);
        }

        /* Mobile stacked */
        @media (max-width: 820px) {
            .table-wrap {
                border: none;
                border-radius: 0;
                max-height: none;
                overflow: visible;
            }

            .tbl {
                min-width: 0;
                width: 100%;
            }

            .tbl thead {
                display: none;
            }

            .tbl,
            .tbl tbody,
            .tbl tr,
            .tbl td {
                display: block;
                width: 100%;
            }

            .tbl tr {
                border: 1px solid rgba(148, 163, 184, .22);
                border-radius: 12px;
                padding: .6rem .65rem;
                background: rgba(148, 163, 184, .05);
                margin-bottom: .55rem;
            }

            .tbl td {
                border-bottom: none;
                padding: .22rem 0;
            }

            .tbl td[data-k]::before {
                content: attr(data-k);
                display: inline-block;
                width: 110px;
                font-size: .72rem;
                opacity: .7;
                text-transform: uppercase;
                letter-spacing: .02em;
                margin-right: .5rem;
            }

            .td-right,
            .td-center {
                text-align: left;
            }

            .num {
                width: 190px;
            }

            .item-cell {
                min-width: 0;
            }
        }

        @media (max-width: 980px) {
            .page-wrap {
                padding: .75rem .75rem 5rem;
            }
        }

        /* =========================
                                                   SIMPLE CUSTOM MODAL (NO BOOTSTRAP JS)
                                                ========================== */
        .confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .58);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1.25rem;
        }

        .confirm-overlay.is-open {
            display: flex;
        }

        .confirm-modal {
            max-width: 780px;
            width: 100%;
            background: var(--card, #0f172a);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .35);
            box-shadow: 0 22px 45px rgba(15, 23, 42, .55);
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }

        .confirm-modal-header,
        .confirm-modal-footer {
            padding: .75rem .9rem;
            border-bottom: 1px solid rgba(148, 163, 184, .22);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
        }

        .confirm-modal-header-title {
            font-size: .95rem;
            font-weight: 800;
        }

        .confirm-modal-body {
            padding: .7rem .9rem .85rem;
            font-size: .9rem;
            overflow: auto;
        }

        .confirm-modal-footer {
            border-top: 1px solid rgba(148, 163, 184, .22);
            border-bottom: none;
            justify-content: flex-end;
        }

        .confirm-close-btn {
            border: none;
            background: transparent;
            font-size: 1.1rem;
            line-height: 1;
            padding: .15rem .35rem;
            cursor: pointer;
            opacity: .7;
        }

        .confirm-close-btn:hover {
            opacity: 1;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- =======================
            HEADER
        ======================== --}}
        <div class="header-row">
            <div>
                <h1 class="title mono">{{ $stockRequest->code }} — Receive</h1>
                <div class="meta sub">
                    {{ optional($stockRequest->date)->format('d M Y') }}
                    · TRANSIT → {{ $stockRequest->destinationWarehouse->code ?? 'RTS' }}
                </div>
            </div>

            <div class="actions">
                <x-status-pill :status="$stockRequest->status" />
                <a href="{{ route('rts.stock-requests.show', $stockRequest) }}" class="btn btn-outline">← Kembali</a>
            </div>
        </div>

        {{-- =======================
            TOP ACTIONS + RULE
        ======================== --}}
        <div class="card">
            <div style="display:flex;justify-content:space-between;gap:.75rem;flex-wrap:wrap;align-items:center">
                <div class="note" style="padding:.55rem .65rem;border:none;background:transparent;margin:0">
                    <b>Aturan:</b> Qty receive ≤ <b>Sisa Transit</b> (Kirim - Terima).<br>
                    <span class="meta">Kalau PRD belum kirim, input otomatis nonaktif.</span>
                </div>

                <div class="actions">
                    <button type="button" class="btn btn-outline" id="btnFillAll">Isi Semua = Max</button>
                    <button type="button" class="btn btn-outline" id="btnClearAll">Kosongkan</button>
                </div>
            </div>
        </div>

        <form id="formReceive" method="POST" action="{{ route('rts.stock-requests.finalize', $stockRequest) }}"
            style="margin-top:.85rem">
            @csrf

            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:.6rem;flex-wrap:wrap">
                    <div style="font-weight:900;letter-spacing:-.01em">Daftar Item</div>
                    <div class="meta">{{ $stockRequest->lines->count() }} item</div>
                </div>

                <div class="line"></div>

                <div class="table-wrap">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th class="no">No</th>
                                <th class="item-cell">Item</th>
                                <th class="td-right">Permintaan</th>
                                <th class="td-right">Dikirim</th>
                                <th class="td-center">Jumlah Diterima</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($stockRequest->lines as $i => $line)
                                @php
                                    $req = (float) ($line->qty_request ?? 0);
                                    $disp = (float) ($line->qty_dispatched ?? 0);
                                    $recv = (float) ($line->qty_received ?? 0);
                                    $pick = (float) ($line->qty_picked ?? 0);

                                    $maxReceivable = max($disp - $recv, 0);
                                    $liveTransit = (float) ($liveStocks[$line->id] ?? 0);

                                    // disable jika PRD belum kirim atau sisa transit 0
                                    $isDisabled = $disp <= 0.0000001 || $maxReceivable <= 0.0000001;

                                    // status badge
                                    if ($disp <= 0.0000001) {
                                        $statusCls = 'badge muted';
                                        $statusLbl = 'Belum dikirim';
                                    } elseif ($maxReceivable <= 0.0000001) {
                                        $statusCls = 'badge danger';
                                        $statusLbl = 'Kosong';
                                    } elseif ($liveTransit + 0.0000001 < $maxReceivable) {
                                        $statusCls = 'badge warn';
                                        $statusLbl = 'Live < Sisa';
                                    } else {
                                        $statusCls = 'badge ok';
                                        $statusLbl = 'OK';
                                    }

                                    $old = old("lines.{$line->id}.qty_received", 0);
                                @endphp

                                <tr style="{{ $isDisabled ? 'opacity:.78' : '' }}">
                                    <td class="no td-center" data-k="No">{{ $i + 1 }}</td>

                                    <td class="item-cell" data-k="Item">
                                        <div class="item-code mono">{{ $line->item->code }}</div>
                                        <div class="item-name">{{ $line->item->name }}</div>

                                        {{-- badges: Pickup + Status --}}
                                        <div class="badges" data-badge-group>
                                            <span class="badge info js-hide-zero" data-zero="{{ $pick }}">
                                                <span class="dot"></span> Pickup
                                                <b class="mono">{{ $pick }}</b>
                                            </span>

                                            <span class="{{ $statusCls }}">
                                                <span class="dot"></span> {{ $statusLbl }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="td-right mono" data-k="Req">{{ $req }}</td>
                                    <td class="td-right mono" data-k="Kirim">{{ $disp }}</td>

                                    <td class="td-center" data-k="Qty Receive">
                                        <input class="num num-center js-recv {{ $isDisabled ? 'is-disabled' : '' }}"
                                            type="number" step="0.01" min="0" max="{{ $maxReceivable }}"
                                            name="lines[{{ $line->id }}][qty_received]"
                                            value="{{ $isDisabled ? 0 : $old }}" data-max="{{ $maxReceivable }}"
                                            data-item-code="{{ $line->item->code }}"
                                            data-item-name="{{ $line->item->name }}" {{ $isDisabled ? 'disabled' : '' }}>

                                        <div class="hint">
                                            Max: <b class="mono">{{ $maxReceivable }}</b>
                                        </div>

                                        @error("lines.{$line->id}.qty_received")
                                            <div class="err">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @error('stock')
                    <div class="note"
                        style="margin-top:.85rem;border-color:rgba(239,68,68,.35);background:rgba(239,68,68,.10)">
                        <b>Error stock:</b> {{ $message }}
                    </div>
                @enderror

                <div style="display:flex;gap:.6rem;justify-content:flex-end;flex-wrap:wrap;margin-top:1rem">
                    <a href="{{ route('rts.stock-requests.show', $stockRequest) }}" class="btn btn-outline">Batal</a>
                    <button class="btn btn-primary" type="submit">Simpan Receive</button>
                </div>
            </div>
        </form>
    </div>

    {{-- =======================
        SIMPLE CUSTOM MODAL KONFIRMASI + DETAIL BARANG
    ======================== --}}
    <div id="confirmReceiveOverlay" class="confirm-overlay" aria-hidden="true">
        <div class="confirm-modal">
            <div class="confirm-modal-header">
                <div class="confirm-modal-header-title">
                    Konfirmasi Simpan Receive
                </div>
                <button type="button" class="confirm-close-btn" data-confirm-close>&times;</button>
            </div>
            <div class="confirm-modal-body">
                <p style="margin-bottom:.4rem">
                    Pastikan jumlah yang diterima sudah sesuai dengan fisik barang.
                </p>
                <p class="meta" style="margin-bottom:.5rem">
                    Berikut ringkasan barang yang akan ditandai sebagai <b>diterima</b> untuk permintaan
                    <span class="mono">{{ $stockRequest->code }}</span>:
                </p>

                {{-- diisi dinamis via JS --}}
                <div id="confirmReceiveList"></div>
            </div>
            <div class="confirm-modal-footer">
                <button type="button" class="btn btn-outline" data-confirm-close>Kembali</button>
                <button type="button" class="btn btn-primary" id="btnConfirmReceive">
                    Ya, Simpan Receive
                </button>
            </div>
        </div>
    </div>

    <script>
        (function() {
            // =========================
            // Utility
            // =========================
            function toNum(x) {
                const n = parseFloat(String(x ?? '').replace(',', '.'));
                return Number.isFinite(n) ? n : 0;
            }

            function isZero(n) {
                return Math.abs(n) <= 0.0000001;
            }

            // =========================
            // Auto-hide zero badges (Pickup)
            // =========================
            document.querySelectorAll('.js-hide-zero').forEach(b => {
                const v = toNum(b.getAttribute('data-zero'));
                if (isZero(v)) b.classList.add('is-hidden');
            });

            // =========================
            // Qty receive clamping + fill/clear + auto-select
            // =========================
            const inputs = Array.from(document.querySelectorAll('.js-recv'));

            function clampInput(el) {
                if (el.disabled) return;
                const max = parseFloat(el.dataset.max || el.max || '0') || 0;
                let v = parseFloat(el.value || '0');
                if (Number.isNaN(v) || v < 0) v = 0;
                if (v > max) v = max;
                el.value = (Math.round(v * 100) / 100)
                    .toFixed(2)
                    .replace(/\.00$/, '');
            }

            function selectAll(el) {
                if (!el || el.disabled) return;
                setTimeout(() => {
                    try {
                        el.select();
                        if (el.setSelectionRange) {
                            const len = el.value.length;
                            el.setSelectionRange(0, len);
                        }
                    } catch (e) {}
                }, 10);
            }

            inputs.forEach(i => {
                if (i.disabled) return;
                i.addEventListener('focus', (e) => selectAll(e.target));
                i.addEventListener('click', (e) => {
                    if (document.activeElement === e.target) {
                        selectAll(e.target);
                    }
                });
                i.addEventListener('blur', () => clampInput(i));
                i.addEventListener('change', () => clampInput(i));
            });

            document.getElementById('btnFillAll')?.addEventListener('click', () => {
                inputs.forEach(el => {
                    if (el.disabled) return;
                    const max = parseFloat(el.dataset.max || el.max || '0') || 0;
                    el.value = max > 0 ? max : 0;
                    clampInput(el);
                });
            });

            document.getElementById('btnClearAll')?.addEventListener('click', () => {
                inputs.forEach(el => {
                    if (el.disabled) return;
                    el.value = 0;
                    clampInput(el);
                });
            });

            // =========================
            // Build detail list untuk modal konfirmasi (tanpa kolom Max)
            // =========================
            const listEl = document.getElementById('confirmReceiveList');

            function buildConfirmList() {
                if (!listEl) return;

                // pastikan sudah di-clamp sebelum show
                inputs.forEach(el => clampInput(el));

                const rows = [];
                inputs.forEach(el => {
                    if (el.disabled) return;
                    const qty = toNum(el.value);
                    if (isZero(qty)) return;

                    const code = el.getAttribute('data-item-code') || '';
                    const name = el.getAttribute('data-item-name') || '';

                    rows.push({
                        code,
                        name,
                        qty
                    });
                });

                if (!rows.length) {
                    listEl.innerHTML =
                        '<p class="meta" style="margin-bottom:0">Belum ada qty receive &gt; 0. Jika memang tidak ada barang yang diterima, Anda tetap bisa melanjutkan.</p>';
                    return;
                }

                let html = '';
                html +=
                    '<div style="max-height:260px;overflow:auto;border-radius:10px;border:1px solid rgba(148,163,184,.35);padding:.4rem .5rem;">';
                html += '<table class="table table-sm mb-0" style="font-size:.8rem;">';
                html += '<thead>';
                html += '<tr>';
                html += '<th style="width:40px;">No</th>';
                html += '<th>Item</th>';
                html += '<th class="text-center">Diterima</th>';
                html += '</tr>';
                html += '</thead><tbody>';

                rows.forEach((row, idx) => {
                    html += '<tr>';
                    html += '<td class="text-center">' + (idx + 1) + '</td>';
                    html += '<td>';
                    html += '<div class="mono" style="font-weight:700;">' + row.code + '</div>';
                    html += '<div style="opacity:.8;">' + row.name + '</div>';
                    html += '</td>';
                    html += '<td class="text-center mono" style="font-weight:700;">' + row.qty + '</td>';
                    html += '</tr>';
                });

                html += '</tbody></table></div>';
                listEl.innerHTML = html;
            }

            // =========================
            // Custom modal open/close
            // =========================
            const overlay = document.getElementById('confirmReceiveOverlay');
            const confirmBtn = document.getElementById('btnConfirmReceive');
            const closeBtns = overlay ? overlay.querySelectorAll('[data-confirm-close]') : [];
            const form = document.getElementById('formReceive');

            function openConfirmModal() {
                if (!overlay) return;
                buildConfirmList();
                overlay.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            }

            function closeConfirmModal() {
                if (!overlay) return;
                overlay.classList.remove('is-open');
                document.body.style.overflow = '';
            }

            closeBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    closeConfirmModal();
                });
            });

            if (overlay) {
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) {
                        closeConfirmModal();
                    }
                });
            }

            if (form) {
                let isConfirmed = false;

                form.addEventListener('submit', function(e) {
                    if (isConfirmed) {
                        return;
                    }
                    e.preventDefault();
                    openConfirmModal();
                });

                if (confirmBtn) {
                    confirmBtn.addEventListener('click', function() {
                        closeConfirmModal();
                        isConfirmed = true;
                        form.submit();
                    });
                }
            }
        })();
    </script>
@endsection
