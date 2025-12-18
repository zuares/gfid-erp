@extends('layouts.app')

@section('title', 'PRD • Proses Permintaan RTS')

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-soft: rgba(45, 212, 191, .14);
            --warn-soft: rgba(245, 158, 11, .18);
            --danger-soft: rgba(239, 68, 68, .16);
            --info-soft: rgba(59, 130, 246, .12);
            --ok-soft: rgba(16, 185, 129, .16);
        }

        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: .9rem .9rem 4rem;
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

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
            margin-bottom: .75rem;
        }

        .title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 900;
            letter-spacing: -.01em;
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

        /* stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .65rem;
            margin-bottom: .9rem;
        }

        .stat {
            padding: .65rem .7rem;
            border-radius: 12px;
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .25);
        }

        .stat .k {
            font-size: .72rem;
            opacity: .72;
        }

        .stat .v {
            margin-top: .14rem;
            font-size: 1.15rem;
            font-weight: 900;
        }

        /* filters */
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            margin-bottom: .9rem;
            align-items: center;
            justify-content: space-between;
        }

        .filter-group {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .filters a {
            padding: .26rem .6rem;
            border-radius: 999px;
            font-size: .8rem;
            border: 1px solid rgba(148, 163, 184, .35);
            text-decoration: none;
            color: inherit;
            opacity: .88;
            transition: background .12s ease, border-color .12s ease, opacity .12s ease;
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

        /* list cards */
        .list {
            display: grid;
            gap: .75rem;
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .30);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, .06),
                0 0 0 1px rgba(15, 23, 42, .03);
            padding: .85rem .9rem;
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
            gap: 1rem;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .right-actions {
            display: flex;
            gap: .5rem;
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
        }

        .btn-mini:hover {
            opacity: 1;
            border-color: rgba(45, 212, 191, .55);
        }

        .meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem .9rem;
            font-size: .85rem;
            opacity: .92;
            margin-top: .55rem;
            align-items: center;
        }

        .meta-row b {
            font-weight: 900;
        }

        /* soft badges + auto-hide zero */
        .badges {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            margin-left: auto;
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

        .empty {
            padding: 2rem 1rem;
            text-align: center;
            opacity: .75;
        }

        @media (max-width: 900px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="header-row">
            <div>
                <h1 class="title">Proses Permintaan RTS (PRD)</h1>
                <div class="meta">Klik card untuk proses kirim ke Transit. Tombol Detail untuk history.</div>
            </div>

            <div class="header-actions">
                <a class="btn btn-outline" href="{{ route('rts.stock-requests.index') }}">Lihat sisi RTS</a>
            </div>
        </div>

        {{-- STATS --}}
        <div class="stats">
            <div class="stat">
                <div class="k">Total</div>
                <div class="v">{{ $stats['total'] }}</div>
            </div>
            <div class="stat">
                <div class="k">Menunggu PRD</div>
                <div class="v">{{ $stats['submitted'] }}</div>
            </div>
            <div class="stat">
                <div class="k">Dikirim</div>
                <div class="v">{{ $stats['shipped'] }}</div>
            </div>
            <div class="stat">
                <div class="k">Sebagian</div>
                <div class="v">{{ $stats['partial'] }}</div>
            </div>
            <div class="stat">
                <div class="k">Outstanding PRD</div>
                <div class="v">{{ number_format($outstandingQty, 2) }}</div>
            </div>
        </div>

        {{-- FILTERS --}}
        <div class="filters">
            <div class="filter-group">
                @foreach ([
            'all' => 'Semua',
            'pending' => 'Pending',
            'submitted' => 'Menunggu',
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
            'all' => 'Semua',
        ] as $pKey => $pLabel)
                    <a href="{{ request()->fullUrlWithQuery(['period' => $pKey]) }}"
                        class="{{ $period === $pKey ? 'active' : '' }}">
                        {{ $pLabel }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- LIST --}}
        <div class="list">
            @forelse ($stockRequests as $sr)
                @php
                    $req = (float) ($sr->total_requested_qty ?? 0);
                    $disp = (float) ($sr->total_dispatched_qty ?? 0);
                    $recv = (float) ($sr->total_received_qty ?? 0);
                    $pick = (float) ($sr->total_picked_qty ?? 0);

                    // PRD ops: yang masih harus dipenuhi PRD untuk dikirim (outstanding PRD versi controller kamu)
                    $outPrd = max($req - $disp - $pick, 0);

                    // indikator lain (opsional, auto-hide zero)
                    $inTransit = max($disp - $recv, 0);
                    $outRts = max($req - $recv - $pick, 0);

                    $isCompleted = $sr->status === 'completed';
                    $clickUrl = $isCompleted
                        ? route('prd.stock-requests.show', $sr)
                        : route('prd.stock-requests.edit', $sr);

                    // label operasional
                    if ($outPrd <= 0.0000001) {
                        $outCls = 'badge ok';
                        $outLbl = 'Tuntas';
                    } elseif ($outPrd >= $req * 0.5 && $req > 0) {
                        $outCls = 'badge danger';
                        $outLbl = 'Prioritas';
                    } else {
                        $outCls = 'badge warn';
                        $outLbl = 'Sisa Kirim';
                    }
                @endphp

                <div class="card is-clickable" onclick="window.location='{{ $clickUrl }}'">
                    <div class="row-top">
                        <div>
                            <div class="meta mb-2" style="font-weight:900">
                                {{ optional($sr->date)->format('d M Y') }}
                            </div>
                            <div class="mono">{{ $sr->code }}</div>
                        </div>

                        <div class="right-actions">
                            <x-status-pill :status="$sr->status" />
                            <a href="{{ route('prd.stock-requests.show', $sr) }}" class="btn-mini"
                                onclick="event.stopPropagation();">
                                Detail
                            </a>
                        </div>
                    </div>

                    <div class="meta-row">
                        <span>REQ <b class="mono">{{ $req }}</b></span>
                        <span>Kirim <b class="mono">{{ $disp }}</b></span>
                        <span>Pickup <b class="mono">{{ $pick }}</b></span>
                        <span>Terima <b class="mono">{{ $recv }}</b></span>

                        <div class="badges">
                            <span class="badge info js-hide-zero" data-zero="{{ $inTransit }}">
                                <span class="dot"></span>Transit <b class="mono">{{ $inTransit }}</b>
                            </span>
                            <span class="badge info js-hide-zero" data-zero="{{ $outRts }}">
                                <span class="dot"></span>Sisa RTS <b class="mono">{{ $outRts }}</b>
                            </span>
                            <span class="{{ $outCls }}">
                                <span class="dot"></span>{{ $outLbl }} <b class="mono">{{ $outPrd }}</b>
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card empty">
                    Belum ada permintaan RTS untuk diproses PRD.
                </div>
            @endforelse
        </div>

        {{-- PAGINATION --}}
        <div style="margin-top:1.25rem">
            {{ $stockRequests->links() }}
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

            document.querySelectorAll('.js-hide-zero').forEach(b => {
                const v = toNum(b.getAttribute('data-zero'));
                if (isZero(v)) b.classList.add('is-hidden');
            });
        })();
    </script>
@endsection
