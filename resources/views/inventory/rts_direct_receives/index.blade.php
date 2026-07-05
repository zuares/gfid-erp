@extends('layouts.app')
@section('title', 'RTS • Dadakan')

@push('head')
    <style>
        :root {
            --r: 16px;
            --b: rgba(148, 163, 184, .22);
            --muted: #6b7280;
            --shadow: 0 12px 30px rgba(15, 23, 42, .10), 0 0 0 1px rgba(15, 23, 42, .03);
        }

        .page-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 14px 12px 96px;
        }

        body[data-theme="light"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, .12) 0,
                    rgba(45, 212, 191, .10) 28%,
                    rgba(255, 255, 255, 1) 75%);
            border-radius: 18px;
        }

        body[data-theme="dark"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, .22) 0,
                    rgba(45, 212, 191, .16) 26%,
                    #020617 68%);
            border-radius: 18px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .card-main {
            background: var(--card);
            border: 1px solid var(--b);
            border-radius: var(--r);
            box-shadow: var(--shadow);
        }

        .header-stack {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-pill {
            border-radius: 999px !important;
            font-weight: 900;
            padding: .55rem 1rem;
        }

        .search-input {
            padding: .5rem .7rem;
            border: 1px solid var(--b);
            border-radius: 10px;
            background: var(--card);
            color: inherit;
        }

        /* TABLE */
        .table-wrap {
            border: 1px solid rgba(148, 163, 184, .14);
            border-radius: 14px;
            overflow: hidden;
        }

        table.table-rts {
            width: 100%;
            border-collapse: collapse;
        }

        table.table-rts thead th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            font-weight: 900;
            border-bottom: 1px solid rgba(148, 163, 184, .14);
            padding: .7rem .6rem;
            text-align: left;
        }

        table.table-rts tbody td {
            border-top: 1px solid rgba(148, 163, 184, .10);
            padding: .65rem .6rem;
            vertical-align: middle;
        }

        .rts-code {
            font-weight: 900;
            letter-spacing: .06em;
        }

        .rts-row {
            cursor: pointer;
            transition: background-color .12s ease;
        }

        .rts-row:hover {
            background: color-mix(in srgb, var(--card) 84%, #3b82f6 6%);
        }

        /* chips item */
        .item-chips {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
        }

        .item-chip {
            display: inline-flex;
            align-items: baseline;
            gap: .25rem;
            font-size: .72rem;
            line-height: 1.1;
            padding: .2rem .45rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.14);
            border: 1px solid rgba(148, 163, 184, 0.18);
            white-space: nowrap;
        }

        .item-chip b {
            font-weight: 700;
            letter-spacing: .02em;
        }

        .item-chip .q {
            color: var(--muted);
        }

        .item-chip-more {
            background: transparent;
            color: var(--muted);
        }

        .route-pill {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            font-size: .72rem;
            font-weight: 800;
            padding: .15rem .55rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .25);
            color: var(--muted);
            white-space: nowrap;
        }

        /* MOBILE CARDS */
        .rts-mobile-list {
            display: grid;
            gap: .65rem;
        }

        .rts-mobile-card {
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .20);
            background: var(--card);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .10), 0 0 0 1px rgba(15, 23, 42, .03);
            overflow: hidden;
            cursor: pointer;
            padding: .8rem .85rem;
            display: grid;
            gap: .45rem;
        }

        .rts-mobile-card:active {
            transform: scale(.995);
        }

        .rts-mobile-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .rts-date-pill {
            display: inline-flex;
            align-items: center;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 900;
            border: 1px solid rgba(148, 163, 184, .25);
            color: var(--muted);
        }

        .rts-meta {
            color: var(--muted);
            font-size: .78rem;
            font-weight: 800;
        }

        @media(max-width:768px) {
            .page-wrap {
                padding: 12px 10px 92px;
            }
        }

        /* === GreatFit / Shipments aligned header override === */
        :root{
            --shp-accent:#334155;
            --shp-accent-2:#1f2937;
            --shp-border:rgba(148,163,184,.18);
            --shp-border-strong:rgba(148,163,184,.30);
            --shp-muted:#64748b;
        }

        .page-wrap{
            max-width:1040px!important;
            margin-inline:auto;
            padding:.75rem .75rem 4rem!important;
            background:transparent!important;
            border-radius:0!important;
        }

        body[data-theme="light"] .page-wrap,
        body[data-theme="dark"] .page-wrap{
            background:transparent!important;
        }

        .card-main{
            border-radius:8px!important;
            border:1px solid var(--shp-border)!important;
            box-shadow:none!important;
            background:var(--card)!important;
        }

        body[data-theme="dark"] .card-main{
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

        .title{
            font-weight:750;
            font-size:1rem;
            letter-spacing:0;
            margin:0;
        }

        .sub{
            color:var(--shp-muted);
            font-size:.78rem;
        }

        body[data-theme="dark"] .sub{
            color:#9ca3af;
        }

        .kpis{
            display:flex;
            flex-wrap:wrap;
            gap:.32rem;
            margin-top:.35rem;
        }

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

        body[data-theme="dark"] .kpi{
            background:rgba(15,23,42,.96);
            border-color:rgba(51,65,85,.85);
        }

        .kpi .lbl{
            text-transform:none;
            letter-spacing:0;
            font-size:.66rem;
            color:#94a3b8;
        }

        .kpi .val{
            font-weight:650;
            color:var(--shp-accent);
        }

        body[data-theme="dark"] .kpi .val{
            color:#e5e7eb;
        }

        .controls{
            display:flex;
            gap:.5rem;
            align-items:center;
            flex-wrap:wrap;
            justify-content:flex-end;
        }

        .btn-pill{
            border-radius:7px!important;
            padding-inline:.78rem!important;
            box-shadow:none!important;
            font-weight:600!important;
        }

        .btn-ship-primary{
            background:var(--shp-accent)!important;
            border-color:var(--shp-accent)!important;
            color:#fff!important;
        }

        .btn-ship-primary:hover{
            background:var(--shp-accent-2)!important;
            border-color:var(--shp-accent-2)!important;
            color:#fff!important;
        }

        .btn-ship-outline{
            color:#475569!important;
            background:transparent!important;
            border:1px solid rgba(148,163,184,.35)!important;
        }

        .btn-ship-outline:hover{
            background:rgba(148,163,184,.08)!important;
            color:#111827!important;
        }

        .search-input{
            min-height:32px!important;
            border-radius:7px!important;
            font-size:.82rem!important;
            padding:.35rem .65rem!important;
            min-width:220px;
        }

        table.table-rts thead th{
            font-size:.68rem!important;
            text-transform:none!important;
            letter-spacing:0!important;
            font-weight:650!important;
            color:#64748b!important;
            padding:.52rem .62rem!important;
        }

        table.table-rts tbody td{
            padding:.52rem .62rem!important;
        }

        .rts-code{
            font-weight:700!important;
            letter-spacing:0!important;
        }

        .route-pill,
        .item-chip{
            border-radius:7px!important;
        }

        @media(max-width:768px){
            .page-wrap{
                padding:.5rem .5rem 4rem!important;
            }

            .ship-topbar{
                margin-inline:-.5rem;
                padding:.5rem .65rem;
            }

            .title{
                font-size:1.05rem;
            }

            .sub{
                display:none;
            }

            .kpis{
                display:none;
            }

            .controls{
                width:100%;
                align-items:stretch;
                justify-content:flex-start;
            }

            .controls form{
                width:100%;
                display:flex;
                gap:.45rem;
            }

            .search-input{
                flex:1 1 auto;
                min-width:0;
                min-height:40px!important;
            }

            .controls .btn{
                min-height:40px;
            }

            .controls > a.btn{
                width:100%;
            }

            .card-main{
                border-radius:8px!important;
            }
        }

    </style>
@endpush

@section('content')
    @php
        // chip detail barang per RTS (group lines by item code, qty = qty)
        $rtsItems = function ($r) {
            $lines = $r->lines ?? collect();
            return $lines
                ->groupBy(fn($l) => optional($l->item)->code ?: '—')
                ->map(fn($g) => [
                    'code' => optional($g->first()->item)->code ?: '—',
                    'name' => optional($g->first()->item)->name ?: '',
                    'qty' => (float) $g->sum(fn($l) => (float) ($l->qty ?? 0)),
                ])
                ->sortByDesc('qty')
                ->values();
        };

        $pageTotalQty = 0;
        foreach ($rows as $row) {
            $pageTotalQty += (float) $rtsItems($row)->sum('qty');
        }
    @endphp

    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="ship-topbar">
            <div>
                <div class="title">RTS • Dadakan</div>
                <div class="sub">Penerimaan barang jadi langsung ke RTS.</div>

                <div class="kpis">
                    <span class="kpi">
                        <span class="lbl">Total</span>
                        <span class="val">{{ number_format($rows->total(), 0, ',', '.') }}</span>
                    </span>
                    <span class="kpi">
                        <span class="lbl">Halaman</span>
                        <span class="val">{{ number_format($rows->count(), 0, ',', '.') }}</span>
                    </span>
                    <span class="kpi">
                        <span class="lbl">Qty</span>
                        <span class="val">{{ number_format($pageTotalQty, 0, ',', '.') }}</span>
                    </span>
                    <span class="kpi">
                        <span class="lbl">Gudang</span>
                        <span class="val">RTS</span>
                    </span>
                </div>
            </div>

            <div class="controls">
                <form method="GET" class="d-flex gap-2 align-items-center flex-wrap m-0">
                    <input name="q" value="{{ $q }}" placeholder="Cari code / notes" class="search-input">
                    <button class="btn btn-sm btn-ship-outline btn-pill">Cari</button>
                </form>

                <a class="btn btn-sm btn-ship-primary btn-pill" href="{{ route('rts.direct-receives.create') }}">
                    + Buat Dadakan
                </a>
            </div>
        </div>

        {{-- LIST CARD --}}
        <div class="card-main p-3">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <h2 class="h6 mb-0">Daftar Dadakan</h2>
                <div class="small text-muted">{{ $rows->total() }} data</div>
            </div>

            @if ($rows->isEmpty())
                <div class="text-center text-muted small py-4">Belum ada data.</div>
            @else
                {{-- DESKTOP TABLE --}}
                <div class="table-wrap d-none d-md-block">
                    <table class="table-rts mono">
                        <thead>
                            <tr>
                                <th style="width:170px;">Dadakan</th>
                                <th style="width:170px;">Rute</th>
                                <th>Barang</th>
                                <th style="width:120px;text-align:right;">Total</th>
                                <th style="width:90px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $r)
                                @php
                                    $items = $rtsItems($r);
                                    $totalQty = $items->sum('qty');
                                    $href = route('rts.direct-receives.show', $r);
                                @endphp
                                <tr class="rts-row" data-href="{{ $href }}">
                                    <td>
                                        <div class="rts-code">{{ $r->code }}</div>
                                        <div class="text-muted" style="font-size:.74rem;">
                                            {{ \Illuminate\Support\Carbon::parse($r->date)->format('d M Y') }}
                                            @if ($r->operator)
                                                • {{ $r->operator->name }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="route-pill">
                                            {{ $r->fromWarehouse->code ?? '-' }} → {{ $r->toWarehouse->code ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($items->isEmpty())
                                            <span class="text-muted small">-</span>
                                        @else
                                            <div class="item-chips">
                                                @foreach ($items->take(4) as $it)
                                                    <span class="item-chip" title="{{ $it['name'] }}">
                                                        <b>{{ $it['code'] }}</b>
                                                        <span class="q">{{ number_format($it['qty'], 0, ',', '.') }}</span>
                                                    </span>
                                                @endforeach
                                                @if ($items->count() > 4)
                                                    <span class="item-chip item-chip-more">+{{ $items->count() - 4 }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td style="text-align:right;font-weight:900;">
                                        {{ number_format($totalQty, 0, ',', '.') }} <span class="text-muted" style="font-size:.72rem;">pcs</span>
                                    </td>
                                    <td style="text-align:right;">
                                        <a class="btn btn-sm btn-outline-primary" style="border-radius:999px;"
                                            href="{{ $href }}" onclick="event.stopPropagation();">Detail</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE CARDS --}}
                <div class="d-block d-md-none mono">
                    <div class="rts-mobile-list">
                        @foreach ($rows as $r)
                            @php
                                $items = $rtsItems($r);
                                $totalQty = $items->sum('qty');
                                $href = route('rts.direct-receives.show', $r);
                            @endphp
                            <div class="rts-mobile-card" data-href="{{ $href }}">
                                <div class="rts-mobile-row">
                                    <div class="rts-date-pill">
                                        {{ \Illuminate\Support\Carbon::parse($r->date)->format('d M Y') }}
                                    </div>
                                    <span class="route-pill">
                                        {{ $r->fromWarehouse->code ?? '-' }} → {{ $r->toWarehouse->code ?? '-' }}
                                    </span>
                                </div>

                                <div class="rts-mobile-row">
                                    <div class="rts-code">{{ $r->code }}</div>
                                    <div class="rts-meta">{{ number_format($totalQty, 0, ',', '.') }} pcs</div>
                                </div>

                                @if ($items->isNotEmpty())
                                    <div class="item-chips">
                                        @foreach ($items->take(4) as $it)
                                            <span class="item-chip" title="{{ $it['name'] }}">
                                                <b>{{ $it['code'] }}</b>
                                                <span class="q">{{ number_format($it['qty'], 0, ',', '.') }}</span>
                                            </span>
                                        @endforeach
                                        @if ($items->count() > 4)
                                            <span class="item-chip item-chip-more">+{{ $items->count() - 4 }}</span>
                                        @endif
                                    </div>
                                @endif

                                @if ($r->operator)
                                    <div class="rts-meta">OP: {{ $r->operator->name }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-3">{{ $rows->links() }}</div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.rts-mobile-card[data-href], .rts-row[data-href]').forEach(el => {
                el.addEventListener('click', (e) => {
                    if (e.target.closest('a,button')) return;
                    const href = el.getAttribute('data-href');
                    if (href) window.location.href = href;
                });
            });
        });
    </script>
@endpush
