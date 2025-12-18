{{-- resources/views/inventory/prd_stock_requests/show.blade.php --}}
@extends('layouts.app')

@section('title', 'PRD • Detail Permintaan RTS • ' . $stockRequest->code)

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-soft: rgba(45, 212, 191, .14);
            --warn-soft: rgba(245, 158, 11, .18);
            --danger-soft: rgba(239, 68, 68, .16);
            --ok-soft: rgba(16, 185, 129, .16);
            --info-soft: rgba(148, 163, 184, .14);
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

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .30);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, .06),
                0 0 0 1px rgba(15, 23, 42, .03);
            padding: .85rem .9rem;
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

        .btn-outline {
            border: 1px solid rgba(148, 163, 184, .45);
            background: transparent;
        }

        .btn-primary {
            background: var(--rts-main);
            border-color: var(--rts-main);
            color: #022c22;
        }

        .line {
            border-top: 1px dashed rgba(148, 163, 184, .35);
            margin: .7rem 0;
        }

        /* =========================
                                   SUMMARY (PRD OPERASIONAL)
                                ========================== */
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

        .summary-bottom {
            margin-top: .65rem;
            display: flex;
            justify-content: space-between;
            gap: .6rem;
            flex-wrap: wrap;
            align-items: center;
        }

        /* badges */
        .badges {
            display: flex;
            gap: .4rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: .38rem;
            padding: .18rem .55rem;
            border-radius: 999px;
            font-size: .75rem;
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
            background: var(--info-soft);
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

        /* =========================
                                   ITEMS TABLE (DESKTOP + MOBILE KEEP TABLE)
                                ========================== */
        .table-wrap {
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 12px;
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
            opacity: .75;
            border-bottom: 1px solid rgba(148, 163, 184, .26);
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
            min-width: 340px;
        }

        .item-code {
            font-weight: 900;
        }

        .item-name {
            margin-top: .12rem;
            font-size: .82rem;
            opacity: .82;
        }

        .item-badges {
            margin-top: .4rem;
        }

        /* hide-only-on-mobile helper */
        .col-hide-m {
            /* default: visible */
        }

        /* =========================
                                   HISTORY TABLE
                                ========================== */
        .hist {
            width: 100%;
            border-collapse: collapse;
            min-width: 980px;
        }

        .hist th,
        .hist td {
            padding: .6rem .55rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            vertical-align: top;
            font-size: .9rem;
        }

        .hist thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: var(--card);
            font-size: .78rem;
            letter-spacing: .02em;
            text-transform: uppercase;
            opacity: .75;
            border-bottom: 1px solid rgba(148, 163, 184, .26);
            white-space: nowrap;
        }

        .badge-dir {
            display: inline-flex;
            align-items: center;
            gap: .38rem;
            padding: .18rem .55rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .30);
            font-size: .75rem;
            font-weight: 850;
            background: rgba(148, 163, 184, .12);
            white-space: nowrap;
        }

        .badge-dir.in {
            border-color: rgba(16, 185, 129, .35);
            background: rgba(16, 185, 129, .16);
            color: rgba(4, 120, 87, 1);
        }

        .badge-dir.out {
            border-color: rgba(239, 68, 68, .40);
            background: rgba(239, 68, 68, .16);
            color: rgba(153, 27, 27, 1);
        }

        /* =========================
                                   RESPONSIVE
                                ========================== */
        @media (max-width: 980px) {
            .page-wrap {
                padding: .75rem .75rem 5rem;
            }

            /* summary: 2 kolom */
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        /* MOBILE: keep table (no card stacking) */
        @media (max-width: 780px) {

            /* summary: biar PRD banget & gak rame */
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .summary-bottom {
                flex-direction: column;
                align-items: stretch;
                gap: .5rem;
            }

            /* badges jadi 1 baris scroll */
            .badges {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: .15rem;
                gap: .45rem;
                mask-image: linear-gradient(to right, rgba(0, 0, 0, 1) 90%, rgba(0, 0, 0, 0));
            }

            .badges::-webkit-scrollbar {
                height: 6px;
            }

            .badges::-webkit-scrollbar-thumb {
                background: rgba(148, 163, 184, .35);
                border-radius: 999px;
            }

            /* items table: tetap table & ringkas */
            .table-wrap {
                border: 1px solid rgba(148, 163, 184, .18);
                border-radius: 12px;
                overflow: auto;
            }

            .tbl {
                min-width: 0;
                width: 100%;
            }

            .tbl th,
            .tbl td {
                padding: .48rem .5rem;
                font-size: .88rem;
            }

            .tbl thead th {
                font-size: .72rem;
            }

            /* sembunyikan kolom non-esensial (mobile) */
            .col-hide-m {
                display: none !important;
            }

            .item-cell {
                min-width: 0;
            }

            .item-name {
                display: none;
                /* mobile: cukup code + badges */
            }

            .item-badges .badge {
                padding: .14rem .45rem;
                font-size: .72rem;
            }

            /* history: tetap table & ringkas */
            .hist {
                min-width: 0;
                width: 100%;
            }

            .hist th,
            .hist td {
                padding: .5rem .5rem;
                font-size: .86rem;
            }

            /* history: hide jam + catatan supaya clean */
            .hist .col-hide-m {
                display: none !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="header-row">
            <div>
                <h1 class="title mono">{{ $stockRequest->code }} — Detail PRD</h1>
                <div class="meta sub">
                    {{ optional($stockRequest->date)->format('d M Y') }}
                    · {{ $stockRequest->sourceWarehouse->code ?? '-' }}
                    → {{ $stockRequest->destinationWarehouse->code ?? '-' }}
                </div>
            </div>

            <div class="actions">
                <x-status-pill :status="$stockRequest->status" />
                <a href="{{ route('prd.stock-requests.index') }}" class="btn btn-outline">← List</a>

                @if ($stockRequest->status !== 'completed')
                    <a href="{{ route('prd.stock-requests.edit', $stockRequest) }}" class="btn btn-primary">
                        Proses / Kirim
                    </a>
                @endif
            </div>
        </div>

        {{-- SUMMARY --}}
        @php
            $reqTotal = (float) $stockRequest->lines->sum('qty_request');
            $dispTotal = (float) $stockRequest->lines->sum('qty_dispatched');
            $recvTotal = (float) $stockRequest->lines->sum('qty_received');
            $pickTotal = (float) $stockRequest->lines->sum('qty_picked');

            // Operasional PRD: Sisa Kirim PRD = req - (kirim + pickup)
            $sisaKirimPrd = max($reqTotal - $dispTotal - $pickTotal, 0);

            $inTransit = max($dispTotal - $recvTotal, 0);
            $outRts = max($reqTotal - $recvTotal - $pickTotal, 0);

            if ($sisaKirimPrd <= 0.0000001) {
                $sCls = 'badge ok';
                $sLbl = 'Tuntas';
            } elseif ($sisaKirimPrd >= $reqTotal * 0.5 && $reqTotal > 0) {
                $sCls = 'badge danger';
                $sLbl = 'Prioritas';
            } else {
                $sCls = 'badge warn';
                $sLbl = 'Sisa Kirim';
            }
        @endphp

        <div class="card" style="margin-top:.75rem">
            <div class="stats">
                <div class="stat">
                    <div class="k">Total Request</div>
                    <div class="v mono">{{ $reqTotal }}</div>
                </div>

                <div class="stat">
                    <div class="k">Sisa Kirim PRD</div>
                    <div class="v mono">{{ $sisaKirimPrd }}</div>
                </div>

                <div class="stat">
                    <div class="k">Sudah Kirim</div>
                    <div class="v mono">{{ $dispTotal }}</div>
                </div>

                <div class="stat">
                    <div class="k">Transit</div>
                    <div class="v mono">{{ $inTransit }}</div>
                </div>
            </div>

            <div class="summary-bottom">
                <div class="badges">
                    <span class="{{ $sCls }}"><span class="dot"></span>{{ $sLbl }} <b
                            class="mono">{{ $sisaKirimPrd }}</b></span>

                    <span class="badge info js-hide-zero" data-zero="{{ $outRts }}">
                        <span class="dot"></span>Sisa RTS <b class="mono">{{ $outRts }}</b>
                    </span>

                    <span class="badge info js-hide-zero" data-zero="{{ $pickTotal }}">
                        <span class="dot"></span>Pickup <b class="mono">{{ $pickTotal }}</b>
                    </span>

                    <span class="badge info js-hide-zero" data-zero="{{ $recvTotal }}">
                        <span class="dot"></span>Terima RTS <b class="mono">{{ $recvTotal }}</b>
                    </span>
                </div>


            </div>

            @if (!empty($stockRequest->notes))
                <div class="line"></div>
                <div class="meta" style="white-space:pre-wrap">{{ $stockRequest->notes }}</div>
            @endif
        </div>

        {{-- ITEMS --}}
        <div class="card" style="margin-top:.85rem">
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
                            <th class="td-right">Req</th>
                            <th class="td-right col-hide-m">Kirim</th>
                            <th class="td-right col-hide-m">Terima</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stockRequest->lines as $i => $line)
                            @php
                                $req = (float) ($line->qty_request ?? 0);
                                $disp = (float) ($line->qty_dispatched ?? 0);
                                $recv = (float) ($line->qty_received ?? 0);
                                $pick = (float) ($line->qty_picked ?? 0);

                                $sisaKirimLine = max($req - $disp - $pick, 0);
                                $inTransitLine = max($disp - $recv, 0);
                                $outRtsLine = max($req - $recv - $pick, 0);

                                if ($sisaKirimLine <= 0.0000001) {
                                    $lCls = 'badge ok';
                                    $lLbl = 'Tuntas';
                                } elseif ($sisaKirimLine >= $req * 0.5 && $req > 0) {
                                    $lCls = 'badge danger';
                                    $lLbl = 'Prioritas';
                                } else {
                                    $lCls = 'badge warn';
                                    $lLbl = 'Sisa Kirim';
                                }
                            @endphp

                            <tr>
                                <td class="no td-center">{{ $i + 1 }}</td>

                                <td class="item-cell">
                                    <div class="item-code mono">{{ $line->item->code }}</div>
                                    <div class="item-name">{{ $line->item->name }}</div>

                                    <div class="badges item-badges">
                                        <span class="{{ $lCls }}">
                                            <span class="dot"></span>{{ $lLbl }} <b
                                                class="mono">{{ $sisaKirimLine }}</b>
                                        </span>

                                        <span class="badge info js-hide-zero" data-zero="{{ $pick }}">
                                            <span class="dot"></span>Pick <b class="mono">{{ $pick }}</b>
                                        </span>

                                        <span class="badge info js-hide-zero" data-zero="{{ $inTransitLine }}">
                                            <span class="dot"></span>Transit <b class="mono">{{ $inTransitLine }}</b>
                                        </span>

                                        <span class="badge info js-hide-zero" data-zero="{{ $outRtsLine }}">
                                            <span class="dot"></span>Sisa RTS <b class="mono">{{ $outRtsLine }}</b>
                                        </span>
                                    </div>
                                </td>

                                <td class="td-right mono">{{ $req }}</td>
                                <td class="td-right mono col-hide-m">{{ $disp }}</td>
                                <td class="td-right mono col-hide-m">{{ $recv }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- HISTORY --}}
        <div class="card" style="margin-top:.85rem">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
                <div>
                    <div style="font-weight:900;letter-spacing:-.01em">History Mutasi</div>
                </div>
            </div>

            <div class="line"></div>

            <div class="table-wrap">
                <table class="hist">
                    <thead>
                        <tr>
                            <th style="width:110px">Tanggal</th>
                            <th style="width:90px" class="col-hide-m">Jam</th>
                            <th style="width:130px">Gudang</th>
                            <th style="width:160px">Item</th>
                            <th style="width:110px">Arah</th>
                            <th style="width:90px" class="td-right">Qty</th>
                            <th class="col-hide-m">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movementHistory as $m)
                            @php
                                $qty = (float) ($m->qty_change ?? 0);
                                $dir = $qty >= 0 ? 'IN' : 'OUT';
                                $abs = abs($qty);

                                $date = $m->date ? \Carbon\Carbon::parse($m->date) : null;
                                $time = $m->created_at ? \Carbon\Carbon::parse($m->created_at) : null;

                                $dirCls = $dir === 'IN' ? 'badge-dir in' : 'badge-dir out';
                            @endphp
                            <tr>
                                <td class="mono">{{ $date?->format('d M Y') ?? '-' }}</td>
                                <td class="mono meta col-hide-m">{{ $time?->format('H:i:s') ?? '' }}</td>

                                <td>
                                    <div class="mono" style="font-weight:900">{{ $m->warehouse->code ?? '-' }}</div>
                                    <div class="meta">{{ $m->warehouse->name ?? '' }}</div>
                                </td>

                                <td>
                                    <div class="mono" style="font-weight:900">{{ $m->item->code ?? '-' }}</div>
                                    <div class="meta">{{ $m->item->name ?? '' }}</div>
                                </td>

                                <td>
                                    <span class="{{ $dirCls }}"><span
                                            class="dot"></span>{{ $dir }}</span>
                                </td>

                                <td class="td-right mono">{{ $abs }}</td>

                                <td class="col-hide-m">
                                    <div style="white-space:pre-wrap">{{ $m->notes ?? '' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="meta" style="text-align:center;opacity:.75;padding:1.25rem">
                                    Belum ada history mutasi untuk dokumen ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        (function() {
            function toNum(x) {
                const n = parseFloat(String(x ?? '').replace(',', '.'));
                return Number.isFinite(n) ? n : 0;
            }

            function isZero(n) {
                return Math.abs(n) <= 0.0000001;
            }

            // auto-hide zero badges (data-zero)
            document.querySelectorAll('.js-hide-zero').forEach(b => {
                const v = toNum(b.getAttribute('data-zero'));
                if (isZero(v)) b.classList.add('is-hidden');
            });
        })();
    </script>
@endsection
