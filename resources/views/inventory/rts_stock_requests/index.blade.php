{{-- resources/views/inventory/rts_stock_requests/index.blade.php --}}
@extends('layouts.app')

@section('title', 'RTS • Permintaan Stok')

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-strong: rgba(15, 118, 110, 1);
            --rts-soft: rgba(45, 212, 191, 0.12);
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .85rem .85rem 3rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.08) 0,
                    rgba(45, 212, 191, 0.10) 28%,
                    #f9fafb 70%);
        }

        .title {
            font-size: 1.12rem;
            font-weight: 750;
            margin: 0;
        }

        .sub {
            font-size: .84rem;
            color: rgba(100, 116, 139, 1);
            margin-top: .2rem;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-variant-numeric: tabular-nums;
        }

        /* button */
        .btn-primary {
            background: var(--rts-strong);
            padding: .40rem .95rem;
            border-radius: 999px;
            font-size: .82rem;
            color: #ecfeff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border: none;
        }

        /* stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .65rem;
            margin: .85rem 0;
        }

        .stat {
            background: var(--card);
            border-radius: 12px;
            padding: .65rem .8rem;
            border: 1px solid rgba(148, 163, 184, 0.25);
        }

        .stat .label {
            font-size: .68rem;
            color: rgba(148, 163, 184, 1);
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .stat .val {
            font-size: 1.02rem;
            font-weight: 750;
            margin-top: .18rem;
        }

        /* filters */
        .chip {
            padding: .16rem .58rem;
            font-size: .78rem;
            border-radius: 999px;
            text-decoration: none;
            border: 1px solid rgba(148, 163, 184, .5);
            background: rgba(248, 250, 252, .9);
            display: inline-block;
            margin-right: .25rem;
        }

        .chip-active {
            background: var(--rts-soft);
            color: var(--rts-strong);
            border-color: var(--rts-strong);
        }

        /* desktop table */
        .table-card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .25);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
        }

        .table th,
        .table td {
            padding: .5rem .65rem;
            border-bottom: 1px solid rgba(148, 163, 184, .20);
            vertical-align: top;
        }

        .table thead {
            background: rgba(15, 23, 42, 0.05);
        }

        .code-badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            border-radius: 999px;
            padding: .10rem .55rem;
            font-size: .78rem;
            background: rgba(15, 23, 42, 0.04);
            border: 1px solid rgba(148, 163, 184, 0.4);
            color: rgba(15, 23, 42, 0.86);
        }

        .code-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: var(--rts-strong);
        }

        .badge-status {
            padding: .12rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 650;
            display: inline-flex;
            align-items: center;
        }

        .badge-status.pending {
            background: rgba(59, 130, 246, .12);
            color: rgba(30, 64, 175, 1);
        }

        .badge-status.partial {
            background: rgba(234, 179, 8, .14);
            color: rgba(133, 77, 14, 1);
        }

        .badge-status.completed {
            background: rgba(22, 163, 74, .14);
            color: rgba(22, 101, 52, 1);
        }

        .qty-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .10rem .55rem;
            font-size: .74rem;
            background: rgba(15, 23, 42, 0.03);
            border: 1px solid rgba(148, 163, 184, 0.35);
            margin-top: .28rem;
        }

        .item-line {
            font-size: .8rem;
            font-variant-numeric: tabular-nums;
        }

        .muted {
            color: rgba(148, 163, 184, 1);
            font-size: .75rem;
        }

        .link-detail {
            color: var(--rts-strong);
            text-decoration: none;
            font-size: .78rem;
            font-weight: 650;
        }

        /* mobile cards */
        .mobile-list {
            display: none;
        }

        .card-row {
            background: var(--card);
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .28);
            padding: .75rem .85rem;
            margin-bottom: .65rem;
        }

        .card-head-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .6rem;
            flex-wrap: wrap;
        }

        .card-meta {
            margin-top: .35rem;
            font-size: .78rem;
            color: rgba(100, 116, 139, 1);
        }

        .route {
            margin-top: .25rem;
            font-size: .82rem;
        }

        .card-items {
            margin-top: .55rem;
            font-size: .82rem;
        }

        .btn-row {
            margin-top: .65rem;
            display: flex;
            gap: .5rem;
        }

        .btn-ghost {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: .42rem .85rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .70);
            background: transparent;
            color: #0f172a;
            font-size: .82rem;
            font-weight: 650;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .page-wrap {
                padding-inline: .65rem;
            }

            .table-card {
                display: none;
            }

            .mobile-list {
                display: block;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $statusQueryBase = request()->except('status', 'page');
        $periodQueryBase = request()->except('period', 'page');
    @endphp

    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <div>
                <h1 class="title">RTS • Permintaan Stok</h1>
                <div class="sub">Permintaan barang dari PRD ke RTS.</div>
            </div>

            <a href="{{ route('rts.stock-requests.today') }}" class="btn-primary">
                + Buat / Buka hari ini
            </a>
        </div>

        {{-- STATS --}}
        <div class="stats">
            <div class="stat">
                <div class="label">Total</div>
                <div class="val mono">{{ $stats['total'] ?? 0 }}</div>
            </div>
            <div class="stat">
                <div class="label">Pending</div>
                <div class="val mono">{{ $stats['pending'] ?? 0 }}</div>
            </div>
            <div class="stat">
                <div class="label">Selesai</div>
                <div class="val mono">{{ $stats['completed'] ?? 0 }}</div>
            </div>
            <div class="stat">
                <div class="label">Sisa Qty</div>
                <div class="val mono">{{ (int) ($outstandingQty ?? 0) }}</div>
            </div>
        </div>

        {{-- FILTERS --}}
        <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
            <div>
                @foreach (['all' => 'Semua', 'pending' => 'Pending', 'submitted' => 'Submitted', 'partial' => 'Partial', 'completed' => 'Selesai'] as $key => $label)
                    <a href="{{ route('rts.stock-requests.index', array_merge($statusQueryBase, ['status' => $key])) }}"
                        class="chip {{ $statusFilter === $key ? 'chip-active' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div>
                @foreach (['today' => 'Hari ini', 'week' => 'Minggu ini', 'month' => 'Bulan ini', 'all' => 'Semua'] as $key => $label)
                    <a href="{{ route('rts.stock-requests.index', array_merge($periodQueryBase, ['period' => $key])) }}"
                        class="chip {{ $period === $key ? 'chip-active' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- DESKTOP TABLE --}}
        <div class="table-card">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 90px;">Tanggal</th>
                        <th style="width: 170px;">Kode</th>
                        <th>Rute</th>
                        <th style="width: 320px;">Item</th>
                        <th style="width: 130px;">Status</th>
                        <th style="width: 70px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($stockRequests as $req)
                        @php
                            $lines = $req->lines->take(3);
                            $extra = max($req->lines->count() - 3, 0);

                            $status = $req->status;
                            $statusLabel = match ($status) {
                                'submitted' => 'Menunggu PRD',
                                'partial' => 'Sebagian',
                                'completed' => 'Selesai',
                                default => ucfirst($status),
                            };

                            $badgeClass = match ($status) {
                                'completed' => 'completed',
                                'partial' => 'partial',
                                default => 'pending',
                            };

                            $totalReq = (float) ($req->total_requested_qty ?? $req->lines->sum('qty_request'));
                            $totalIssued = (float) ($req->total_issued_qty ?? $req->lines->sum('qty_issued'));
                            $outstanding = max($totalReq - $totalIssued, 0);
                        @endphp

                        <tr>
                            <td class="mono">
                                {{ $req->date?->format('d M') }}<br>
                                <span class="muted">{{ $req->date?->format('Y') }}</span>
                            </td>

                            <td>
                                <div class="code-badge">
                                    <span class="dot"></span>
                                    <span class="mono">{{ $req->code }}</span>
                                </div>

                                <div class="qty-pill">
                                    <span>Diminta</span> <strong class="mono">{{ (int) $totalReq }}</strong>
                                    @if ($outstanding > 0)
                                        <span class="muted">•</span>
                                        <span>Sisa</span> <strong class="mono">{{ (int) $outstanding }}</strong>
                                    @endif
                                </div>
                            </td>

                            <td>
                                {{ $req->sourceWarehouse?->name ?? '-' }} → {{ $req->destinationWarehouse?->name ?? '-' }}
                            </td>

                            <td>
                                @foreach ($lines as $line)
                                    <div class="item-line">
                                        <span class="mono">{{ $line->item?->code ?? '-' }}</span> :
                                        <strong class="mono">{{ (int) $line->qty_request }}</strong>
                                    </div>
                                @endforeach
                                @if ($extra > 0)
                                    <div class="muted">+{{ $extra }} item</div>
                                @endif
                            </td>

                            <td>
                                <span class="badge-status {{ $badgeClass }}">{{ $statusLabel }}</span>
                            </td>

                            <td>
                                <a href="{{ route('rts.stock-requests.show', $req) }}" class="link-detail">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                Belum ada data permintaan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-3">
                {{ $stockRequests->links() }}
            </div>
        </div>

        {{-- MOBILE CARDS --}}
        <div class="mobile-list">
            @forelse ($stockRequests as $req)
                @php
                    $lines = $req->lines->take(2);
                    $extra = max($req->lines->count() - 2, 0);

                    $status = $req->status;
                    $statusLabel = match ($status) {
                        'submitted' => 'Menunggu PRD',
                        'partial' => 'Sebagian',
                        'completed' => 'Selesai',
                        default => ucfirst($status),
                    };

                    $badgeClass = match ($status) {
                        'completed' => 'completed',
                        'partial' => 'partial',
                        default => 'pending',
                    };

                    $totalReq = (float) ($req->total_requested_qty ?? $req->lines->sum('qty_request'));
                    $totalIssued = (float) ($req->total_issued_qty ?? $req->lines->sum('qty_issued'));
                    $outstanding = max($totalReq - $totalIssued, 0);
                @endphp

                <div class="card-row">
                    <div class="card-head-row">
                        <div class="code-badge">
                            <span class="dot"></span>
                            <span class="mono">{{ $req->code }}</span>
                        </div>

                        <span class="badge-status {{ $badgeClass }}">{{ $statusLabel }}</span>
                    </div>

                    <div class="card-meta">
                        <span class="mono">{{ $req->date?->format('d M Y') ?? '-' }}</span>
                        <span class="muted">•</span>
                        <span class="mono">{{ $req->created_at?->format('H:i') }}</span>
                    </div>

                    <div class="route">
                        {{ $req->sourceWarehouse?->name ?? '-' }} → {{ $req->destinationWarehouse?->name ?? '-' }}
                    </div>

                    <div class="qty-pill">
                        <span>Diminta</span> <strong class="mono">{{ (int) $totalReq }}</strong>
                        @if ($outstanding > 0)
                            <span class="muted">•</span>
                            <span>Sisa</span> <strong class="mono">{{ (int) $outstanding }}</strong>
                        @endif
                    </div>

                    <div class="card-items">
                        @foreach ($lines as $line)
                            <div class="item-line">
                                <span class="mono">{{ $line->item?->code ?? '-' }}</span>
                                <span class="muted">×</span>
                                <strong class="mono">{{ (int) $line->qty_request }}</strong>
                            </div>
                        @endforeach
                        @if ($extra > 0)
                            <div class="muted">+{{ $extra }} item</div>
                        @endif
                    </div>

                    <div class="btn-row">
                        <a href="{{ route('rts.stock-requests.show', $req) }}" class="btn-ghost">Detail</a>
                    </div>
                </div>
            @empty
                <div class="card-row">
                    <div class="text-center muted">Belum ada data permintaan.</div>
                </div>
            @endforelse

            @if (method_exists($stockRequests, 'links'))
                <div class="mt-2">
                    {{ $stockRequests->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
