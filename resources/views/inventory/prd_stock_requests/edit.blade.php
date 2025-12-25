{{-- resources/views/inventory/prd_stock_requests/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'PRD • Proses Permintaan RTS • ' . $stockRequest->code)

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-soft: rgba(45, 212, 191, .14);

            --info-soft: rgba(59, 130, 246, .12);
            --warn-soft: rgba(245, 158, 11, .18);
            --danger-soft: rgba(239, 68, 68, .16);
            --ok-soft: rgba(16, 185, 129, .16);

            --priority-soft: rgba(245, 158, 11, .14);
            --priority-border: rgba(245, 158, 11, .45);
        }

        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: .9rem .9rem 4.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, .10) 0,
                    rgba(45, 212, 191, .12) 28%,
                    #f9fafb 65%);
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .meta {
            font-size: .82rem;
            opacity: .82;
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .30);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, .06),
                0 0 0 1px rgba(15, 23, 42, .03);
            padding: .85rem .9rem;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
            margin-bottom: .75rem;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: .55rem;
            flex-wrap: wrap;
        }

        .title-block {
            display: flex;
            flex-direction: column;
            gap: .15rem;
        }

        .title {
            margin: 0;
            font-size: 1.06rem;
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
            justify-content: flex-end;
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

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding-inline: .6rem;
            padding-block: .3rem;
            border-radius: 999px;
            font-size: .8rem;
            white-space: nowrap;
        }

        .btn-back span {
            transform: translateY(-0.5px);
        }

        .line {
            border-top: 1px dashed rgba(148, 163, 184, .35);
            margin: .7rem 0;
        }

        /* ===== badges (soft, clean) ===== */
        .badges {
            display: flex;
            gap: .38rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: .34rem;
            padding: .16rem .5rem;
            border-radius: 999px;
            font-size: .74rem;
            font-weight: 850;
            border: 1px solid transparent;
            white-space: nowrap;
            line-height: 1;
        }

        .badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: currentColor;
            opacity: .72;
        }

        .badge.info {
            background: rgba(148, 163, 184, .14);
            border-color: rgba(148, 163, 184, .30);
            color: rgba(51, 65, 85, 1);
        }

        .badge.ok {
            background: var(--ok-soft);
            border-color: rgba(16, 185, 129, .35);
            color: rgba(4, 120, 87, 1);
        }

        .badge.warn {
            background: var(--warn-soft);
            border-color: rgba(245, 158, 11, .40);
            color: rgba(146, 64, 14, 1);
        }

        .badge.danger {
            background: var(--danger-soft);
            border-color: rgba(239, 68, 68, .40);
            color: rgba(153, 27, 27, 1);
        }

        .badge.is-hidden {
            display: none !important;
        }

        /* ===== summary stats ===== */
        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .55rem;
        }

        .stat {
            background: rgba(148, 163, 184, .06);
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 12px;
            padding: .55rem .6rem;
        }

        .stat .k {
            font-size: .72rem;
            opacity: .72;
            line-height: 1.1;
        }

        .stat .v {
            margin-top: .12rem;
            font-size: 1.12rem;
            font-weight: 900;
            line-height: 1.1;
        }

        /* badges row in summary: single line scroll */
        .badge-scroll {
            margin-top: .65rem;
            display: flex;
            gap: .4rem;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding-bottom: .1rem;
        }

        .badge-scroll::-webkit-scrollbar {
            display: none;
        }

        /* ===== table (NO SCROLL MOBILE) ===== */
        .table-wrap {
            overflow: visible;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 12px;
        }

        table.tbl {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .tbl th,
        .tbl td {
            padding: .62rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            vertical-align: top;
            font-size: .92rem;
            overflow: visible;
        }

        .tbl thead th {
            background: var(--card);
            font-size: .78rem;
            letter-spacing: .02em;
            text-transform: uppercase;
            opacity: .75;
            border-bottom: 1px solid rgba(148, 163, 184, .26);
        }

        .tbl tbody tr:hover {
            background: rgba(148, 163, 184, .05);
        }

        .col-no {
            width: 54px;
            text-align: center;
            opacity: .85;
        }

        .col-send {
            width: 128px;
            text-align: right;
        }

        .item-code {
            font-weight: 900;
        }

        .item-name {
            margin-top: .10rem;
            font-size: .82rem;
            opacity: .82;
            line-height: 1.2;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
        }

        .item-badges {
            margin-top: .42rem;
            display: flex;
            flex-wrap: wrap;
            gap: .38rem;
            max-width: 100%;
        }

        .item-note {
            margin-top: .4rem;
            font-size: .82rem;
            line-height: 1.25;
            opacity: .9;
            padding: .45rem .55rem;
            border-radius: 12px;
            background: rgba(148, 163, 184, .10);
            border: 1px dashed rgba(148, 163, 184, .28);
        }

        .item-note .lbl {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .03em;
            opacity: .75;
            margin-bottom: .18rem;
        }

        /* input */
        .num {
            width: 100%;
            max-width: 128px;
            padding: .48rem .55rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: var(--card);
            color: inherit;
            text-align: right;
        }

        .num[disabled] {
            opacity: .55;
            cursor: not-allowed;
        }

        .err {
            margin-top: .35rem;
            font-size: .82rem;
            color: rgba(239, 68, 68, 1);
            text-align: right;
        }

        /* highlight prioritas row */
        .tbl tr.is-priority {
            background: var(--priority-soft);
        }

        .tbl tr.is-priority:hover {
            background: rgba(245, 158, 11, .18);
        }

        .tbl tr.is-priority td:first-child {
            border-left: 4px solid var(--priority-border);
        }

        .top-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
            margin-top: .5rem;
        }

        @media (max-width: 980px) {
            .page-wrap {
                padding: .75rem .75rem 5rem;
            }

            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .col-send {
                width: 112px;
            }

            .num {
                max-width: 112px;
            }
        }

        @media (max-width: 640px) {
            .header-row {
                align-items: flex-start;
            }

            .title {
                font-size: 1rem;
            }

            .meta {
                font-size: .78rem;
            }

            .btn-back {
                padding-inline: .5rem;
                padding-block: .25rem;
                font-size: .78rem;
            }

            .top-actions {
                justify-content: flex-start;
            }

            .tbl th,
            .tbl td {
                padding: .5rem .5rem;
                font-size: .86rem;
            }

            .item-name {
                -webkit-line-clamp: 3;
            }
        }

        @media (max-width: 520px) {
            .col-no {
                width: 46px;
            }

            .col-send {
                width: 104px;
            }

            .num {
                max-width: 104px;
            }

            .badge {
                font-size: .72rem;
                padding: .15rem .45rem;
            }

            .badge .dot {
                width: 7px;
                height: 7px;
            }

            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
@endpush

@section('content')
    @php
        $userRole = auth()->user()->role ?? null;
        $isOperating = $userRole === 'operating';

        $reqTotal = (float) $stockRequest->lines->sum('qty_request');
        $dispTotal = (float) $stockRequest->lines->sum('qty_dispatched');
        $recvTotal = (float) $stockRequest->lines->sum('qty_received');
        $pickTotal = (float) $stockRequest->lines->sum('qty_picked');

        $maxKirimTotal = (float) $stockRequest->lines->sum(function ($l) {
            $req = (float) ($l->qty_request ?? 0);
            $disp = (float) ($l->qty_dispatched ?? 0);
            $recv = (float) ($l->qty_received ?? 0);
            $pick = (float) ($l->qty_picked ?? 0);
            return max($req - $disp - $recv - $pick, 0);
        });

        $inTransit = max($dispTotal - $recvTotal, 0);
        $outRts = max($reqTotal - $recvTotal - $pickTotal, 0);
    @endphp

    <div class="page-wrap">

        {{-- HEADER (semua role) --}}
        <div class="header-row">
            <div class="header-left">
                {{-- Tombol kembali ke list PRD --}}
                <a href="{{ route('prd.stock-requests.index') }}" class="btn btn-outline btn-back">
                    ← <span>Kembali</span>
                </a>

                <div class="title-block">
                    <h1 class="title mono">
                        {{ $stockRequest->code }}
                        @if ($isOperating)
                            — Proses Kirim
                        @else
                            — Proses PRD
                        @endif
                    </h1>
                    <div class="meta">
                        {{ optional($stockRequest->date)->format('d M Y') }}
                        · {{ $stockRequest->sourceWarehouse->code ?? 'WH-PRD' }}
                        → <span class="mono">WH-TRANSIT</span>
                    </div>
                </div>
            </div>

            <div class="actions">
                <x-status-pill :status="$stockRequest->status" />

                @unless ($isOperating)
                    <a href="{{ route('prd.stock-requests.show', $stockRequest) }}" class="btn btn-outline">
                        Detail
                    </a>
                @endunless
            </div>
        </div>

        {{-- QUICK + SUMMARY: non-operating saja (supaya operator lebih simpel) --}}
        @unless ($isOperating)
            {{-- QUICK ACTIONS --}}
            <div class="top-actions">
                <button type="button" class="btn btn-outline" id="btnFillAll">Isi Semua</button>
                <button type="button" class="btn btn-outline" id="btnClearAll">Kosongkan</button>
            </div>

            {{-- SUMMARY --}}
            <div class="card" style="margin-top:.6rem">
                <div class="stats">
                    <div class="stat">
                        <div class="k">Total Request</div>
                        <div class="v mono">{{ $reqTotal }}</div>
                    </div>
                    <div class="stat">
                        <div class="k">Sudah Kirim</div>
                        <div class="v mono">{{ $dispTotal }}</div>
                    </div>
                    <div class="stat">
                        <div class="k">Sisa Kirim</div>
                        <div class="v mono">{{ $maxKirimTotal }}</div>
                    </div>
                </div>

                <div class="badge-scroll">
                    <span class="badge info js-hide-zero" data-zero="{{ $inTransit }}"><span class="dot"></span>Transit
                        <b class="mono">{{ $inTransit }}</b></span>
                    <span class="badge info js-hide-zero" data-zero="{{ $outRts }}"><span class="dot"></span>Sisa RTS
                        <b class="mono">{{ $outRts }}</b></span>
                    <span class="badge info js-hide-zero" data-zero="{{ $pickTotal }}"><span class="dot"></span>Pickup
                        <b class="mono">{{ $pickTotal }}</b></span>
                </div>
            </div>
        @endunless

        {{-- FORM (OPERATING: fokus utama di sini) --}}
        <form method="POST" action="{{ route('prd.stock-requests.confirm', $stockRequest) }}" style="margin-top:.85rem">
            @csrf

            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:.6rem;flex-wrap:wrap">
                    <div style="font-weight:900;letter-spacing:-.01em">
                        Input Qty Kirim PRD → Transit
                    </div>
                    <div class="meta">{{ $stockRequest->lines->count() }} item</div>
                </div>

                <div class="line"></div>

                <div class="table-wrap">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th class="col-no">No</th>
                                <th>Item</th>
                                <th class="col-send">Kirim</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($stockRequest->lines as $i => $line)
                                @php
                                    $req = (float) ($line->qty_request ?? 0);
                                    $disp = (float) ($line->qty_dispatched ?? 0);
                                    $recv = (float) ($line->qty_received ?? 0);
                                    $pick = (float) ($line->qty_picked ?? 0);

                                    $maxKirim = max($req - $disp - $recv - $pick, 0);
                                    $disabled = $maxKirim <= 0.0000001;

                                    $isPriority = !$disabled && $req > 0 && $maxKirim >= $req * 0.5;

                                    $old = old("lines.{$line->id}.qty_issued", null);
                                    $val = $disabled ? 0 : (is_null($old) ? 0 : $old);

                                    if ($disabled) {
                                        $stCls = 'badge ok';
                                        $stLbl = 'Tuntas';
                                    } elseif ($isPriority) {
                                        $stCls = 'badge warn';
                                        $stLbl = 'Prioritas';
                                    } else {
                                        $stCls = 'badge info';
                                        $stLbl = 'Proses';
                                    }

                                    // catatan per line (support notes kolom lama & kolom notes baru)
                                    $note = trim((string) ($line->notes ?? ($line->note ?? '')));
                                @endphp

                                <tr class="{{ !$isOperating && $isPriority ? 'is-priority' : '' }}">
                                    <td class="col-no">{{ $i + 1 }}</td>

                                    <td>
                                        <div class="item-code mono">{{ $line->item->code }}</div>
                                        <div class="item-name">{{ $line->item->name }}</div>

                                        {{-- BADGE DI ITEM --}}
                                        <div class="item-badges">
                                            {{-- Status kecil (Tuntas / Prioritas / Proses) HANYA untuk non-operating --}}
                                            @unless ($isOperating)
                                                <span class="{{ $stCls }}">
                                                    <span class="dot"></span>{{ $stLbl }}
                                                </span>
                                            @endunless

                                            {{-- Req & Disp tetap tampil untuk semua role --}}
                                            <span class="badge info">
                                                <span class="dot"></span>Permintaan
                                                <b class="mono">{{ $req }}</b>
                                            </span>
                                            <span class="badge info js-hide-zero" data-zero="{{ $disp }}">
                                                <span class="dot"></span>Disp
                                                <b class="mono">{{ $disp }}</b>
                                            </span>
                                        </div>

                                        {{-- CATATAN --}}
                                        @if ($note !== '')
                                            <div class="item-note">
                                                <div class="lbl">Catatan</div>
                                                <div>{{ $note }}</div>
                                            </div>
                                        @endif
                                    </td>

                                    <td class="col-send">
                                        <input class="num js-issue js-selectall" type="number" step="0.01"
                                            min="0" max="{{ $maxKirim }}"
                                            name="lines[{{ $line->id }}][qty_issued]" value="{{ $val }}"
                                            data-max="{{ $maxKirim }}" {{ $disabled ? 'disabled' : '' }}
                                            inputmode="decimal" autocomplete="off">

                                        @error("lines.{$line->id}.qty_issued")
                                            <div class="err">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @error('stock')
                    <div
                        style="margin-top:.85rem;border:1px solid rgba(239,68,68,.35);background:rgba(239,68,68,.10);border-radius:12px;padding:.65rem .75rem;">
                        <b style="color:rgba(153,27,27,1)">Error stock:</b> {{ $message }}
                    </div>
                @enderror

                <div style="display:flex;gap:.6rem;justify-content:flex-end;flex-wrap:wrap;margin-top:1rem">
                    @unless ($isOperating)
                        <a href="{{ route('prd.stock-requests.index') }}" class="btn btn-outline">
                            Batal
                        </a>
                    @endunless

                    <button class="btn btn-primary" type="submit"
                        onclick="return confirm('Proses dispatch PRD → Transit sekarang?')">
                        Proses Kirim
                    </button>
                </div>
            </div>
        </form>

    </div>

    <script>
        (function() {
            const inputs = Array.from(document.querySelectorAll('.js-issue'));

            function clampInput(el) {
                if (el.disabled) return;
                const max = parseFloat(el.dataset.max || el.max || '0') || 0;
                let v = parseFloat(el.value || '0');
                if (Number.isNaN(v) || v < 0) v = 0;
                if (v > max) v = max;
                el.value = (Math.round(v * 100) / 100).toFixed(2).replace(/\.00$/, '');
            }

            function toNum(x) {
                const n = parseFloat(String(x ?? '').replace(',', '.'));
                return Number.isFinite(n) ? n : 0;
            }

            function isZero(n) {
                return Math.abs(n) <= 0.0000001;
            }

            // hide zero badges (summary + Disp badge)
            document.querySelectorAll('.js-hide-zero').forEach(b => {
                const v = toNum(b.getAttribute('data-zero'));
                if (isZero(v)) b.classList.add('is-hidden');
            });

            // auto select all text on focus/click (biar gampang overwrite angka)
            function selectAll(el) {
                try {
                    el.focus();
                    // delay kecil biar aman di mobile safari
                    setTimeout(() => {
                        try {
                            el.select();
                        } catch (e) {}
                        try {
                            const len = (el.value || '').length;
                            el.setSelectionRange(0, len);
                        } catch (e) {}
                    }, 0);
                } catch (e) {}
            }

            inputs.forEach(i => {
                i.addEventListener('blur', () => clampInput(i));
                i.addEventListener('change', () => clampInput(i));

                i.addEventListener('focus', () => selectAll(i));
                i.addEventListener('click', () => selectAll(i));
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
        })();
    </script>
@endsection
