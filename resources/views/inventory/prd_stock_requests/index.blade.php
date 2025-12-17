{{-- resources/views/inventory/prd_stock_requests/index.blade.php --}}
@extends('layouts.app')

@section('title', 'PRD • Permintaan Stok dari RTS')

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-main-strong: rgba(15, 118, 110, 1);
            --rts-main-soft: rgba(45, 212, 191, 0.14);
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .9rem .9rem 3rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.08) 0,
                    rgba(45, 212, 191, 0.1) 28%,
                    #f9fafb 70%);
        }

        .page-title {
            font-size: 1.1rem;
            font-weight: 650;
            margin-bottom: .2rem;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New",
                monospace;
            font-variant-numeric: tabular-nums;
        }

        /* STATS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .7rem;
            margin: .8rem 0 1rem;
        }

        .stat-card {
            background: var(--card);
            border-radius: 12px;
            padding: .7rem .85rem;
            border: 1px solid rgba(148, 163, 184, 0.22);
        }

        .stat-label {
            font-size: .68rem;
            color: rgba(100, 116, 139, 1);
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 1.05rem;
            font-weight: 650;
            margin-top: .18rem;
            font-variant-numeric: tabular-nums;
        }

        /* FILTER CHIPS */
        .chip {
            padding: .18rem .6rem;
            font-size: .78rem;
            border-radius: 999px;
            text-decoration: none;
            border: 1px solid rgba(148, 163, 184, .45);
            background: rgba(248, 250, 252, .85);
            margin-right: .25rem;
            display: inline-block;
        }

        .chip-active {
            background: var(--rts-main-soft);
            color: var(--rts-main-strong);
            border-color: var(--rts-main-strong);
        }

        /* TABLE */
        .table-card {
            background: var(--card);
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .22);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: .84rem;
        }

        .table th,
        .table td {
            padding: .55rem .65rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            vertical-align: top;
        }

        .table thead {
            background: rgba(15, 23, 42, 0.03);
        }

        .table thead th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(100, 116, 139, 1);
        }

        /* STATUS BADGE */
        .badge-status {
            padding: .14rem .5rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            line-height: 1.1;
        }

        .badge-status.pending {
            background: rgba(59, 130, 246, .10);
            color: rgba(30, 64, 175, 1);
        }

        .badge-status.shipped {
            background: rgba(45, 212, 191, .14);
            color: rgba(15, 118, 110, 1);
        }

        .badge-status.partial {
            background: rgba(234, 179, 8, .12);
            color: rgba(133, 77, 14, 1);
        }

        .badge-status.completed {
            background: rgba(22, 163, 74, .12);
            color: rgba(22, 101, 52, 1);
        }

        .badge-status.draft {
            background: rgba(148, 163, 184, 0.18);
            color: #475569;
        }

        /* CODE + MINI BADGES */
        .code-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .12rem .55rem;
            font-size: .8rem;
            background: rgba(15, 23, 42, 0.03);
            border: 1px solid rgba(148, 163, 184, 0.35);
        }

        .code-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: var(--rts-main-strong);
        }

        .badge-mini-dadakan {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .06rem .45rem;
            border-radius: 999px;
            font-size: .68rem;
            border: 1px solid rgba(234, 179, 8, .55);
            background: rgba(234, 179, 8, .14);
            color: rgba(133, 77, 14, 1);
            line-height: 1.1;
        }

        /* QTY BADGES */
        .qty-badges {
            display: inline-flex;
            flex-wrap: wrap;
            gap: .25rem;
            margin-top: .35rem;
        }

        .qty-badge {
            border-radius: 999px;
            padding: .08rem .5rem;
            font-size: .72rem;
            background: rgba(15, 23, 42, 0.02);
            border: 1px solid rgba(148, 163, 184, .45);
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            color: #0f172a;
        }

        .qty-badge--sisa {
            background: var(--rts-main-soft);
            border-color: var(--rts-main-strong);
            color: var(--rts-main-strong);
        }

        /* ACTION */
        .link-action {
            color: var(--rts-main-strong);
            text-decoration: none;
            font-size: .8rem;
            font-weight: 650;
        }

        .link-action:hover {
            text-decoration: underline;
        }

        /* MOBILE */
        .mobile-list {
            display: none;
        }

        .rts-card {
            background: var(--card);
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .28);
            padding: .85rem .9rem;
            margin-bottom: .7rem;
        }

        .rts-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .6rem;
            margin-bottom: .55rem;
        }

        .rts-card-meta {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-size: .8rem;
            color: rgba(100, 116, 139, 1);
            margin-top: .25rem;
        }

        .btn-process {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: .48rem .9rem;
            border-radius: 999px;
            border: none;
            background: var(--rts-main-strong);
            color: #ecfeff;
            font-size: .85rem;
            font-weight: 650;
            text-decoration: none;
            margin-top: .65rem;
        }

        .btn-detail {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: .44rem .9rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .6);
            background: transparent;
            color: #0f172a;
            font-size: .85rem;
            font-weight: 600;
            text-decoration: none;
            margin-top: .6rem;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .table-card {
                display: none;
            }

            .mobile-list {
                display: block;
            }

            .page-wrap {
                padding-inline: .7rem;
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

        <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
            <div>
                <div class="page-title">PRD • Permintaan Stok dari RTS</div>
            </div>
        </div>

        {{-- STATS --}}
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total</div>
                <div class="stat-value mono">{{ $stats['total'] ?? 0 }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Pending</div>
                <div class="stat-value mono">{{ $stats['pending'] ?? 0 }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Selesai</div>
                <div class="stat-value mono">{{ $stats['completed'] ?? 0 }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Outstanding Qty</div>
                <div class="stat-value mono">{{ (int) ($outstandingQty ?? 0) }}</div>
            </div>
        </div>

        {{-- FILTERS --}}
        <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
            <div>
                @foreach ([
            'all' => 'Semua',
            'pending' => 'Pending',
            'submitted' => 'Submitted',
            'shipped' => 'Shipped',
            'partial' => 'Partial',
            'completed' => 'Selesai',
        ] as $key => $label)
                    <a href="{{ route('prd.stock-requests.index', array_merge($statusQueryBase, ['status' => $key])) }}"
                        class="chip {{ $statusFilter === $key ? 'chip-active' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div>
                @foreach (['today' => 'Hari ini', 'week' => 'Minggu ini', 'month' => 'Bulan ini', 'all' => 'Semua'] as $key => $label)
                    <a href="{{ route('prd.stock-requests.index', array_merge($periodQueryBase, ['period' => $key])) }}"
                        class="chip {{ $period === $key ? 'chip-active' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- DESKTOP TABLE --}}
        <div class="table-card d-none d-md-block">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 92px;">Tanggal</th>
                        <th style="width: 320px;">Dokumen</th>
                        <th style="width: 210px;">Qty</th>
                        <th style="width: 140px;">Status</th>
                        <th style="width: 90px;" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stockRequests as $req)
                        @php
                            $totalReq = (float) ($req->total_requested_qty ?? 0);
                            $totalDispatched = (float) ($req->total_dispatched_qty ?? 0);
                            $outstanding = max($totalReq - $totalDispatched, 0);

                            $totalPicked = (float) ($req->total_picked_qty ?? $req->lines->sum('qty_picked'));
                            $isDadakan = $totalPicked > 0 && $req->status === 'submitted';

                            $statusLabel = match ($req->status) {
                                'submitted' => 'Submitted',
                                'shipped' => 'Transit',
                                'partial' => 'Partial',
                                'completed' => 'Selesai',
                                'draft' => 'Draft',
                                default => ucfirst($req->status),
                            };

                            $badgeClass = match ($req->status) {
                                'completed' => 'completed',
                                'partial' => 'partial',
                                'shipped' => 'shipped',
                                'submitted' => 'pending',
                                'draft' => 'draft',
                                default => 'pending',
                            };

                            $isProcessable = in_array($req->status, ['submitted', 'shipped', 'partial']);
                        @endphp

                        <tr>
                            <td class="mono">
                                {{ $req->date?->format('d M') ?? '-' }}<br>
                                <span style="color:rgba(100,116,139,1);font-size:.76rem;">
                                    {{ $req->date?->format('Y') ?? '' }}
                                </span>
                            </td>

                            <td>
                                <div class="code-badge">
                                    <span class="dot"></span>
                                    <span class="mono">{{ $req->code }}</span>
                                    @if ($isDadakan)
                                        <span class="badge-mini-dadakan">⚡ Dadakan</span>
                                    @endif
                                </div>
                                <div class="qty-badges">
                                    <span class="qty-badge">Diminta <span
                                            class="mono">{{ (int) $totalReq }}</span></span>
                                    <span class="qty-badge">Dikirim <span
                                            class="mono">{{ (int) $totalDispatched }}</span></span>
                                    @if ($totalPicked > 0)
                                        <span class="qty-badge">Picked <span
                                                class="mono">{{ (int) $totalPicked }}</span></span>
                                    @endif
                                    @if ($outstanding > 0)
                                        <span class="qty-badge qty-badge--sisa">Sisa <span
                                                class="mono">{{ (int) $outstanding }}</span></span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="mono" style="font-weight:650;">
                                    {{ $req->lines_count ?? $req->lines->count() }} item
                                </div>
                                <div style="color:rgba(100,116,139,1);font-size:.78rem;">
                                    {{ $req->sourceWarehouse?->code ?? 'PRD' }} →
                                    {{ $req->destinationWarehouse?->code ?? 'RTS' }}
                                </div>
                            </td>

                            <td>
                                <div class="badge-status {{ $badgeClass }}">
                                    {{ $statusLabel }}
                                </div>
                            </td>

                            <td class="text-end">
                                @if ($isProcessable)
                                    <a href="{{ route('prd.stock-requests.edit', $req) }}" class="link-action">Proses</a>
                                @else
                                    <a href="{{ route('prd.stock-requests.show', $req) }}" class="link-action">Detail</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4" style="color:rgba(100,116,139,1);">
                                Belum ada permintaan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if (method_exists($stockRequests, 'links'))
                <div class="p-3">
                    {{ $stockRequests->links() }}
                </div>
            @endif
        </div>

        {{-- MOBILE CARD LIST --}}
        <div class="mobile-list d-md-none">
            @forelse ($stockRequests as $req)
                @php
                    $totalReq = (float) ($req->total_requested_qty ?? 0);
                    $totalDispatched = (float) ($req->total_dispatched_qty ?? 0);
                    $outstanding = max($totalReq - $totalDispatched, 0);

                    $totalPicked = (float) ($req->total_picked_qty ?? $req->lines->sum('qty_picked'));
                    $isDadakan = $totalPicked > 0 && $req->status === 'submitted';

                    $statusLabel = match ($req->status) {
                        'submitted' => 'Submitted',
                        'shipped' => 'Transit',
                        'partial' => 'Partial',
                        'completed' => 'Selesai',
                        'draft' => 'Draft',
                        default => ucfirst($req->status),
                    };

                    $badgeClass = match ($req->status) {
                        'completed' => 'completed',
                        'partial' => 'partial',
                        'shipped' => 'shipped',
                        'submitted' => 'pending',
                        'draft' => 'draft',
                        default => 'pending',
                    };

                    $isProcessable = in_array($req->status, ['submitted', 'shipped', 'partial']);
                @endphp

                <div class="rts-card">
                    <div class="rts-card-top">
                        <div>
                            <div class="code-badge">
                                <span class="dot"></span>
                                <span class="mono">{{ $req->code }}</span>
                                @if ($isDadakan)
                                    <span class="badge-mini-dadakan">⚡ Dadakan</span>
                                @endif
                            </div>
                            <div class="rts-card-meta">
                                <span class="mono">{{ $req->date?->format('d M Y') ?? '-' }}</span>
                                <span>•</span>
                                <span class="mono">{{ $req->lines_count ?? $req->lines->count() }} item</span>
                            </div>
                        </div>

                        <div class="badge-status {{ $badgeClass }}">
                            {{ $statusLabel }}
                        </div>
                    </div>

                    <div class="qty-badges">
                        <span class="qty-badge">Diminta <span class="mono">{{ (int) $totalReq }}</span></span>
                        <span class="qty-badge">Dikirim <span class="mono">{{ (int) $totalDispatched }}</span></span>
                        @if ($totalPicked > 0)
                            <span class="qty-badge">Picked <span class="mono">{{ (int) $totalPicked }}</span></span>
                        @endif
                        @if ($outstanding > 0)
                            <span class="qty-badge qty-badge--sisa">Sisa <span
                                    class="mono">{{ (int) $outstanding }}</span></span>
                        @endif
                    </div>

                    @if ($isProcessable)
                        <a href="{{ route('prd.stock-requests.edit', $req) }}" class="btn-process">🚚 Proses Kirim</a>
                    @else
                        <a href="{{ route('prd.stock-requests.show', $req) }}" class="btn-detail">Lihat Detail</a>
                    @endif
                </div>
            @empty
                <div class="rts-card">
                    <div class="text-center" style="color:rgba(100,116,139,1);">
                        Belum ada permintaan.
                    </div>
                </div>
            @endforelse

            @if (method_exists($stockRequests, 'links'))
                <div class="mt-3">
                    {{ $stockRequests->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
