{{-- resources/views/inventory/rts_stock_requests/index.blade.php --}}
@extends('layouts.app')

@section('title', 'RTS • Permintaan Replenish')

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
            padding: .85rem .85rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, .10) 0,
                    rgba(45, 212, 191, .12) 28%,
                    #f9fafb 65%);
        }

        /* Header */
        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
            margin-bottom: .75rem;
        }

        .title {
            font-size: 1.15rem;
            font-weight: 900;
            letter-spacing: -.01em;
            margin: 0;
        }

        .header-actions {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        /* Stats (compact) */
        .stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .55rem;
            margin-bottom: .75rem;
        }

        .stat {
            background: var(--card);
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .25);
            padding: .55rem .6rem;
            min-height: 58px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat .label {
            font-size: .72rem;
            opacity: .72;
            line-height: 1.1;
        }

        .stat .value {
            font-size: 1.1rem;
            font-weight: 900;
            margin-top: .12rem;
            line-height: 1.1;
        }

        /* Filters (compact pills) */
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .75rem;
        }

        .filter-group {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .filters a {
            padding: .26rem .55rem;
            border-radius: 999px;
            font-size: .78rem;
            border: 1px solid rgba(148, 163, 184, .35);
            text-decoration: none;
            color: inherit;
            opacity: .86;
            transition: background .12s ease, border-color .12s ease, opacity .12s ease;
            white-space: nowrap;
        }

        .filters a:hover {
            opacity: 1;
        }

        .filters a.active {
            background: var(--rts-soft);
            border-color: var(--rts-main);
            font-weight: 800;
            opacity: 1;
        }

        /* List */
        .list {
            display: grid;
            gap: .65rem;
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .30);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, .06),
                0 0 0 1px rgba(15, 23, 42, .03);
            padding: .75rem .8rem;
            transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease, opacity .12s ease;
        }

        .card.is-clickable {
            cursor: pointer;
        }

        .card.is-clickable:hover {
            transform: translateY(-1px);
            border-color: rgba(45, 212, 191, .45);
            box-shadow:
                0 14px 34px rgba(15, 23, 42, .10),
                0 0 0 1px rgba(15, 23, 42, .03);
        }

        .row-top {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .code {
            font-weight: 900;
            letter-spacing: -.01em;
        }

        .sub {
            font-size: .82rem;
            opacity: .82;
            margin-top: .12rem;
        }

        .top-actions {
            display: flex;
            gap: .45rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-mini {
            padding: .18rem .5rem;
            border-radius: 999px;
            font-size: .78rem;
            border: 1px solid rgba(148, 163, 184, .45);
            background: transparent;
            color: inherit;
            text-decoration: none;
            opacity: .9;
            transition: opacity .12s ease, border-color .12s ease, background .12s ease;
            white-space: nowrap;
        }

        .btn-mini:hover {
            opacity: 1;
            border-color: rgba(45, 212, 191, .55);
        }

        /* Compact metrics row */
        .metrics {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .4rem;
            margin-top: .6rem;
        }

        .m {
            border: 1px dashed rgba(148, 163, 184, .28);
            border-radius: 12px;
            padding: .42rem .5rem;
            display: flex;
            justify-content: space-between;
            gap: .5rem;
            align-items: baseline;
            min-height: 38px;
        }

        .m .k {
            font-size: .72rem;
            opacity: .72;
            white-space: nowrap;
        }

        .m .v {
            font-size: .92rem;
            font-weight: 900;
            white-space: nowrap;
        }

        /* Outstanding badge */
        .out-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .16rem .52rem;
            border-radius: 999px;
            font-size: .78rem;
            border: 1px solid rgba(148, 163, 184, .35);
            background: rgba(148, 163, 184, .10);
            white-space: nowrap;
            font-weight: 800;
        }

        .out-badge.is-ok {
            border-color: rgba(16, 185, 129, .35);
            background: rgba(16, 185, 129, .14);
        }

        .out-badge.is-warn {
            border-color: rgba(245, 158, 11, .40);
            background: var(--warn-soft);
        }

        .out-badge.is-danger {
            border-color: rgba(239, 68, 68, .40);
            background: var(--danger-soft);
        }

        .right-badges {
            display: flex;
            gap: .4rem;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .empty {
            padding: 1.25rem .9rem;
            text-align: center;
            opacity: .75;
        }

        @media (max-width: 980px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .page-wrap {
                padding: .75rem .75rem 5rem;
            }
        }

        @media (max-width: 420px) {
            .title {
                font-size: 1.05rem;
            }

            .stat .value {
                font-size: 1.05rem;
            }

            .m {
                padding: .38rem .45rem;
            }
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
                <h1 class="title">Permintaan Replenish RTS</h1>
                <div class="subtitle">
                    Setelah penerimaan dikonfirmasi, dokumen langsung selesai (completed) dan permintaan baru akan memakai
                    nomor baru.
                </div>
            </div>

            <div class="header-actions">
                <a href="{{ route('rts.stock-requests.today') }}" class="btn btn-primary">
                    Hari Ini
                </a>
                <a href="{{ route('rts.stock-requests.create') }}" class="btn btn-outline">
                    Buat
                </a>
            </div>
        </div>


        {{-- =======================
            STATS (ringkas)
        ======================== --}}
        <div class="stats">
            <div class="stat">
                <div class="label">Total</div>
                <div class="value">{{ $stats['total'] }}</div>
            </div>
            <div class="stat">
                <div class="label">Menunggu PRD</div>
                <div class="value">{{ $stats['submitted'] }}</div>
            </div>
            <div class="stat">
                <div class="label">Dikirim</div>
                <div class="value">{{ $stats['shipped'] }}</div>
            </div>
            <div class="stat">
                <div class="label">Sebagian</div>
                <div class="value">{{ $stats['partial'] }}</div>
            </div>
            <div class="stat">
                <div class="label">Sisa RTS</div>
                <div class="value">{{ number_format($outstandingQty, 2) }}</div>
            </div>
        </div>

        {{-- =======================
            FILTERS (status + periode)
        ======================== --}}
        <div class="filters">
            <div class="filter-group">
                @foreach ([
            'all' => 'Semua',
            'pending' => 'Pending',
            'submitted' => 'Menunggu PRD',
            'shipped' => 'Dikirim',
            'partial' => 'Sebagian',
            'completed' => 'Selesai',
        ] as $key => $label)
                    <a href="{{ request()->fullUrlWithQuery(['status' => $key]) }}"
                        class="{{ $statusFilter === $key ? 'active' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="filter-group">
                @foreach ([
            'today' => 'Hari ini',
            'week' => 'Minggu ini',
            'month' => 'Bulan ini',
            'all' => 'Semua periode',
        ] as $pKey => $pLabel)
                    <a href="{{ request()->fullUrlWithQuery(['period' => $pKey]) }}"
                        class="{{ $period === $pKey ? 'active' : '' }}">
                        {{ $pLabel }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- =======================
            LIST
        ======================== --}}
        <div class="list">
            @forelse ($stockRequests as $sr)
                @php
                    $req = (float) ($sr->total_requested_qty ?? 0);
                    $disp = (float) ($sr->total_dispatched_qty ?? 0);
                    $recv = (float) ($sr->total_received_qty ?? 0);
                    $pick = (float) ($sr->total_picked_qty ?? 0);

                    // RTS perspective: sisa RTS = request - (received + picked)
                    $outRts = max($req - $recv - $pick, 0);
                    $inTransit = max($disp - $recv, 0);

                    $clickUrl = route('rts.stock-requests.show', $sr);

                    // badge sisa
                    if ($outRts <= 0.0000001) {
                        $outCls = 'out-badge is-ok';
                        $outText = 'AMAN';
                    } elseif ($outRts >= $req * 0.5 && $req > 0) {
                        $outCls = 'out-badge is-danger';
                        $outText = 'URGENT';
                    } else {
                        $outCls = 'out-badge is-warn';
                        $outText = 'BUTUH';
                    }
                @endphp

                <div class="card is-clickable" onclick="window.location='{{ $clickUrl }}'">
                    <div class="row-top">
                        <div>
                            <div class="mono code">{{ $sr->code }}</div>
                            <div class="sub">
                                {{ optional($sr->date)->format('d M Y') }}
                                · {{ $sr->sourceWarehouse->code ?? '-' }}
                                → {{ $sr->destinationWarehouse->code ?? '-' }}
                            </div>
                        </div>

                        <div class="top-actions">
                            <span class="{{ $outCls }}">
                                {{ $outText }} · Sisa <span class="mono">{{ $outRts }}</span>
                            </span>
                            <x-status-pill :status="$sr->status" />
                            <a href="{{ route('rts.stock-requests.show', $sr) }}" class="btn-mini"
                                onclick="event.stopPropagation();">
                                Detail
                            </a>
                        </div>
                    </div>

                    <div class="metrics">
                        <div class="m">
                            <span class="k">Req</span>
                            <span class="v mono">{{ $req }}</span>
                        </div>
                        <div class="m">
                            <span class="k">Kirim</span>
                            <span class="v mono">{{ $disp }}</span>
                        </div>
                        <div class="m">
                            <span class="k">Terima</span>
                            <span class="v mono">{{ $recv }}</span>
                        </div>
                        <div class="m">
                            <span class="k">Pickup</span>
                            <span class="v mono">{{ $pick }}</span>
                        </div>
                        <div class="m">
                            <span class="k">Transit</span>
                            <span class="v mono">{{ $inTransit }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card empty">
                    Belum ada permintaan.
                </div>
            @endforelse
        </div>

        {{-- =======================
            PAGINATION
        ======================== --}}
        <div style="margin-top:1rem">
            {{ $stockRequests->links() }}
        </div>

    </div>
@endsection
