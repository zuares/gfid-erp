{{-- resources/views/inventory/rts_stock_requests/index.blade.php --}}
@extends('layouts.app')

@section('title', 'PRD • Proses Permintaan RTS')

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

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(15, 23, 42, .9) 0,
                    #020617 65%);
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

        .subtitle {
            font-size: .8rem;
            opacity: .8;
            margin-top: .15rem;
        }

        .header-actions {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-outline {
            border: 1px solid rgba(148, 163, 184, .45);
            background: transparent;
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
            justify-content: flex-end;
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

        /* Versi lebih simpel untuk operator: 3 kolom */
        .metrics-operating {
            grid-template-columns: repeat(3, minmax(0, 1fr));
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

        /* Outstanding PRD badge */
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
    @php
        $userRole = auth()->user()->role ?? null;
        $isOperating = $userRole === 'operating';

        // Status yang tampil tergantung role
        $statusOptions = $isOperating
            ? [
                'submitted' => 'Menunggu',
                'shipped' => 'Dikirim',
                'completed' => 'Selesai',
            ]
            : [
                'all' => 'Semua',
                'pending' => 'Pending',
                'submitted' => 'Menunggu',
                'shipped' => 'Dikirim',
                'partial' => 'Sebagian',
                'completed' => 'Selesai',
            ];

        // Default untuk operating kalau tidak ada status di query = 'submitted' (Menunggu)
        $currentStatus = $statusFilter;
        if ($isOperating && empty($currentStatus)) {
            $currentStatus = 'submitted';
        }

        $periodOptions = [
            'today' => 'Hari ini',
            'week' => 'Minggu ini',
            'month' => 'Bulan ini',
            'all' => 'Semua periode',
        ];
    @endphp

    <div class="page-wrap">

        {{-- =======================
            HEADER
        ======================== --}}
        <div class="header-row">
            <div>
                <h1 class="title">Proses Permintaan RTS (PRD)</h1>
            </div>

            {{-- Tombol ini disembunyikan untuk role operating --}}
            @unless ($isOperating)
                <div class="header-actions">
                    <a href="{{ route('rts.stock-requests.index') }}" class="btn btn-outline">
                        Lihat sisi RTS
                    </a>
                </div>
            @endunless
        </div>

        {{-- =======================
            STATS (ringkas) - hide utk operating
        ======================== --}}
        @unless ($isOperating)
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
                    <div class="label">Outstanding PRD</div>
                    <div class="value">{{ number_format($outstandingQty, 2) }}</div>
                </div>
            </div>
        @endunless

        {{-- =======================
            FILTERS (status + periode)
            Hanya non-operating
        ======================== --}}
        @unless ($isOperating)
            <div class="filters">
                <div class="filter-group">
                    @foreach ($statusOptions as $key => $label)
                        <a href="{{ request()->fullUrlWithQuery(['status' => $key]) }}"
                            class="{{ $currentStatus === $key ? 'active' : '' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                <div class="filter-group">
                    @foreach ($periodOptions as $pKey => $pLabel)
                        <a href="{{ request()->fullUrlWithQuery(['period' => $pKey]) }}"
                            class="{{ $period === $pKey ? 'active' : '' }}">
                            {{ $pLabel }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endunless

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

                    // PRD perspective: sisa yang harus dikirim oleh PRD ke Transit
                    $outPrd = max($req - $disp - $pick, 0);

                    // indikator lain
                    $inTransit = max($disp - $recv, 0);

                    $isCompleted = $sr->status === 'completed';
                    $clickUrl = $isCompleted
                        ? route('prd.stock-requests.show', $sr)
                        : route('prd.stock-requests.edit', $sr);

                    if ($outPrd <= 0.0000001) {
                        $outCls = 'out-badge is-ok';
                        $outText = 'TUNTAS';
                    } elseif ($outPrd >= $req * 0.5 && $req > 0) {
                        $outCls = 'out-badge is-danger';
                        $outText = 'PRIORITAS';
                    } else {
                        $outCls = 'out-badge is-warn';
                        $outText = 'SISA KIRIM';
                    }
                @endphp

                <div class="card is-clickable" onclick="window.location='{{ $clickUrl }}'">
                    <div class="row-top">
                        <div>
                            <div class="mono code">{{ $sr->code }}</div>
                            <div class="sub">
                                {{ optional($sr->date)->format('d M Y') }}
                                @if ($sr->sourceWarehouse || $sr->destinationWarehouse)
                                    · {{ $sr->sourceWarehouse->code ?? '-' }}
                                    → {{ $sr->destinationWarehouse->code ?? '-' }}
                                @endif
                            </div>
                        </div>

                        <div class="top-actions">
                            <span class="{{ $outCls }}">
                                {{ $outText }} · <span class="mono">{{ $outPrd }}</span>
                            </span>
                            <x-status-pill :status="$sr->status" />
                            <a href="{{ route('prd.stock-requests.show', $sr) }}" class="btn-mini"
                                onclick="event.stopPropagation();">
                                Detail
                            </a>
                        </div>
                    </div>

                    {{-- Metrics: beda antara operating vs non-operating --}}
                    <div class="metrics {{ $isOperating ? 'metrics-operating' : '' }}">
                        {{-- Selalu tampil --}}
                        <div class="m">
                            <span class="k">Permintaan</span>
                            <span class="v mono">{{ $req }}</span>
                        </div>

                        <div class="m">
                            <span class="k">Dikirim</span>
                            <span class="v mono">{{ $disp }}</span>
                        </div>

                        @if ($isOperating)
                            {{-- Operating: simpel, hanya 3 angka kunci --}}
                            <div class="m">
                                <span class="k">Sisa Kirim</span>
                                <span class="v mono">{{ $outPrd }}</span>
                            </div>
                        @else
                            {{-- Non-operating: full metrics --}}
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
                        @endif
                    </div>
                </div>
            @empty
                <div class="card empty">
                    Belum ada permintaan RTS untuk diproses PRD.
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
