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
    </style>
@endpush

@section('content')
    @php
        $role = auth()->user()?->role;
        $canManage = in_array($role, ['owner', 'admin'], true);

        $reqTotal = (float) $stockRequest->lines->sum('qty_request');
        $recvTotal = (float) $stockRequest->lines->sum('qty_received');
        $outTotal = max($reqTotal - $recvTotal, 0);

        $canReceive = $stockRequest->status === 'submitted';
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
                    <a href="{{ route('rts.stock-requests.confirm', $stockRequest) }}" class="btn btn-primary">Terima</a>
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
                    <div class="k">Terima</div>
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
                            <th class="td-right">Terima</th>
                            <th class="td-right">Sisa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stockRequest->lines as $i => $line)
                            @php
                                $req = (float) ($line->qty_request ?? 0);
                                $recv = (float) ($line->qty_received ?? 0);
                                $out = max($req - $recv, 0);
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
