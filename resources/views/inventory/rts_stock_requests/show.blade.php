@extends('layouts.app')

@section('title', 'RTS • ' . $stockRequest->code)

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-soft: rgba(45, 212, 191, .14);
            --warn-soft: rgba(245, 158, 11, .14);
            --danger-soft: rgba(239, 68, 68, .12);
        }

        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: .85rem .85rem 4.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, .10) 0, rgba(45, 212, 191, .12) 28%, #f9fafb 65%);
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(15, 23, 42, 0.9) 0, #020617 65%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .30);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06), 0 0 0 1px rgba(15, 23, 42, .03);
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

        .line {
            border-top: 1px dashed rgba(148, 163, 184, .35);
            margin: .7rem 0;
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

        .table-wrap {
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 12px;
        }

        .tbl {
            width: 100%;
            border-collapse: collapse;
            min-width: 720px;
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

        .td-right {
            text-align: right;
            white-space: nowrap;
        }

        .td-center {
            text-align: center;
            white-space: nowrap;
        }

        .no {
            width: 44px;
            opacity: .75;
        }

        .item-cell {
            min-width: 220px;
        }

        .item-code {
            font-weight: 900;
        }

        .item-name {
            margin-top: .12rem;
            font-size: .82rem;
            opacity: .82;
        }

        @media(max-width:980px) {
            .page-wrap {
                padding: .75rem .75rem 5rem;
            }

            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .tbl {
                min-width: 0;
                width: 100%;
            }
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
        $role = auth()->user()?->role;
        $canManage = in_array($role, ['owner', 'admin'], true);

        $reqTotal = (float) $stockRequest->lines->sum('qty_request');
        $recvTotal = (float) $stockRequest->lines->sum('qty_received');
        $pickTotal = (float) $stockRequest->lines->sum('qty_picked');
        $outTotal = max($reqTotal - $recvTotal - $pickTotal, 0);

        $canReceive = in_array($stockRequest->status, ['submitted', 'shipped', 'partial'], true) && $outTotal > 0.0000001;
    @endphp

    <div class="page-wrap">

        <div class="header-row">
            <div>
                <h1 class="title mono">{{ $stockRequest->code }}</h1>
                <div class="meta sub">
                    {{ optional($stockRequest->date)->format('d M Y') }}
                    · {{ $stockRequest->sourceWarehouse->code ?? '-' }} →
                    {{ $stockRequest->destinationWarehouse->code ?? '-' }}
                </div>
            </div>

            <div class="actions">
                <x-status-pill :status="$stockRequest->status" />
                <a href="{{ route('rts.stock-requests.index') }}" class="btn btn-outline">← List</a>
                @if ($canManage && $canReceive)
                    <a href="{{ route('rts.stock-requests.confirm', $stockRequest) }}" class="btn btn-primary">Terima Jadi</a>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="stats">
                <div class="stat">
                    <div class="k">Req</div>
                    <div class="v mono">{{ rtrim(rtrim(number_format($reqTotal, 2, '.', ''), '0'), '.') }}</div>
                </div>
                <div class="stat">
                    <div class="k">Terima Jadi</div>
                    <div class="v mono">{{ rtrim(rtrim(number_format($recvTotal, 2, '.', ''), '0'), '.') }}</div>
                </div>
                <div class="stat">
                    <div class="k">Sisa</div>
                    <div class="v mono">{{ rtrim(rtrim(number_format($outTotal, 2, '.', ''), '0'), '.') }}</div>
                </div>
            </div>

            @if ($stockRequest->notes)
                <div class="line"></div>
                <div class="note">{{ $stockRequest->notes }}</div>
            @endif
        </div>

        <div class="card" style="margin-top:.85rem">
            <div style="display:flex;justify-content:space-between;align-items:baseline;gap:.6rem;flex-wrap:wrap">
                <div style="font-weight:900;letter-spacing:-.01em">Item</div>
                <div class="meta">{{ $stockRequest->lines->count() }}</div>
            </div>

            <div class="line"></div>

            <div class="table-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th class="no">No</th>
                            <th class="item-cell">Item</th>
                            <th class="td-right">Req</th>
                            <th class="td-right">Terima Jadi</th>
                            <th class="td-right">Sisa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stockRequest->lines as $i => $line)
                            @php
                                $req = (float) ($line->qty_request ?? 0);
                                $recv = (float) ($line->qty_received ?? 0);
                                $pick = (float) ($line->qty_picked ?? 0);
                                $out = max($req - $recv - $pick, 0);
                            @endphp
                            <tr>
                                <td class="no td-center">{{ $i + 1 }}</td>
                                <td class="item-cell">
                                    <div class="item-code mono">{{ $line->item->code }}</div>
                                    <div class="item-name">{{ $line->item->name }}</div>
                                </td>
                                <td class="td-right mono">{{ rtrim(rtrim(number_format($req, 2, '.', ''), '0'), '.') }}
                                </td>
                                <td class="td-right mono">{{ rtrim(rtrim(number_format($recv, 2, '.', ''), '0'), '.') }}
                                </td>
                                <td class="td-right mono">{{ rtrim(rtrim(number_format($out, 2, '.', ''), '0'), '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
