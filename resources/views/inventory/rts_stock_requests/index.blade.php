@extends('layouts.app')

@section('title', 'RTS • Permintaan')

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
            background: radial-gradient(circle at top left, rgba(59, 130, 246, .10) 0, rgba(45, 212, 191, .12) 28%, #f9fafb 65%);
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

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
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

        .list {
            display: grid;
            gap: .65rem;
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .30);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06), 0 0 0 1px rgba(15, 23, 42, .03);
            padding: .75rem .8rem;
            transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
        }

        .card.is-clickable {
            cursor: pointer;
        }

        .card.is-clickable:hover {
            transform: translateY(-1px);
            border-color: rgba(45, 212, 191, .45);
            box-shadow: 0 14px 34px rgba(15, 23, 42, .10), 0 0 0 1px rgba(15, 23, 42, .03);
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
            transition: opacity .12s ease, border-color .12s ease;
            white-space: nowrap;
        }

        .btn-mini:hover {
            opacity: 1;
            border-color: rgba(45, 212, 191, .55);
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
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

        .badge {
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

        .badge.ok {
            border-color: rgba(16, 185, 129, .35);
            background: rgba(16, 185, 129, .14);
        }

        .badge.warn {
            border-color: rgba(245, 158, 11, .40);
            background: var(--warn-soft);
        }

        .badge.danger {
            border-color: rgba(239, 68, 68, .40);
            background: var(--danger-soft);
        }

        .empty {
            padding: 1.25rem .9rem;
            text-align: center;
            opacity: .75;
        }

        @media(max-width:980px) {
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
    </style>
@endpush

@section('content')
    @php
        $role = strtolower((string) (auth()->user()?->role ?? ''));
        $canManage = in_array($role, ['owner', 'admin'], true);

        $statusNow = $statusFilter ?? request('status', 'submitted');
        $periodNow = $period ?? request('period', 'week');
        $fmt = fn($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    @endphp

    <div class="page-wrap">
        <div class="header-row">
            <h1 class="title">Permintaan RTS</h1>

            <div class="header-actions">
                @if ($canManage)
                    <a href="{{ route('rts.stock-requests.create') }}" class="btn btn-primary">Buat</a>
                @endif
            </div>
        </div>

        <div class="stats">
            <div class="stat">
                <div class="label">Total</div>
                <div class="value">{{ $stats['total'] ?? 0 }}</div>
            </div>
            <div class="stat">
                <div class="label">Menunggu</div>
                <div class="value">{{ $stats['submitted'] ?? 0 }}</div>
            </div>
            <div class="stat">
                <div class="label">Selesai</div>
                <div class="value">{{ $stats['completed'] ?? 0 }}</div>
            </div>
            <div class="stat">
                <div class="label">Sisa</div>
                <div class="value">{{ number_format((float) ($outstandingQty ?? 0), 2) }}</div>
            </div>
        </div>

        <div class="filters">
            <div class="filter-group">
                @foreach (['all' => 'Semua', 'submitted' => 'Menunggu', 'completed' => 'Selesai'] as $key => $label)
                    <a href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => 1]) }}"
                        class="{{ $statusNow === $key ? 'active' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="filter-group">
                @foreach (['today' => 'Hari ini', 'week' => 'Minggu ini', 'month' => 'Bulan ini', 'all' => 'Semua'] as $pKey => $pLabel)
                    <a href="{{ request()->fullUrlWithQuery(['period' => $pKey, 'page' => 1]) }}"
                        class="{{ $periodNow === $pKey ? 'active' : '' }}">
                        {{ $pLabel }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="list">
            @forelse ($stockRequests as $sr)
                @php
                    $req = (float) ($sr->total_requested_qty ?? 0);
                    $recv = (float) ($sr->total_received_qty ?? 0);
                    $out = max($req - $recv, 0);

                    if ($out <= 0.0000001) {
                        $cls = 'badge ok';
                        $txt = 'OK';
                    } elseif ($out >= $req * 0.5 && $req > 0) {
                        $cls = 'badge danger';
                        $txt = 'Urgent';
                    } else {
                        $cls = 'badge warn';
                        $txt = 'Butuh';
                    }
                @endphp

                <div class="card is-clickable" onclick="window.location='{{ route('rts.stock-requests.show', $sr) }}'">
                    <div class="row-top">
                        <div>
                            <div class="mono code">{{ $sr->code }}</div>
                            <div class="sub">
                                {{ optional($sr->date)->format('d M Y') }}
                                · {{ $sr->sourceWarehouse->code ?? '-' }} → {{ $sr->destinationWarehouse->code ?? '-' }}
                            </div>
                        </div>

                        <div class="top-actions">
                            <span class="{{ $cls }}">{{ $txt }} · <span
                                    class="mono">{{ $fmt($out) }}</span></span>
                            <x-status-pill :status="$sr->status" />
                            <a href="{{ route('rts.stock-requests.show', $sr) }}" class="btn-mini"
                                onclick="event.stopPropagation();">
                                Detail
                            </a>
                        </div>
                    </div>

                    <div class="metrics">
                        <div class="m"><span class="k">Req</span><span class="v mono">{{ $fmt($req) }}</span>
                        </div>
                        <div class="m"><span class="k">Terima</span><span
                                class="v mono">{{ $fmt($recv) }}</span></div>
                        <div class="m"><span class="k">Sisa</span><span class="v mono">{{ $fmt($out) }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card empty">Belum ada permintaan.</div>
            @endforelse
        </div>

        <div style="margin-top:1rem">
            {{ $stockRequests->links() }}
        </div>
    </div>
@endsection
