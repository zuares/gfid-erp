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
            font-weight: 600;
        }

        .page-subtitle {
            font-size: .84rem;
            color: rgba(100, 116, 139, 1);
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New",
                monospace;
            font-variant-numeric: tabular-nums;
        }

        .muted {
            color: rgba(148, 163, 184, 1);
            font-size: .76rem;
        }

        /* STATS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .7rem;
            margin: .9rem 0;
        }

        .stat-card {
            background: var(--card);
            border-radius: 10px;
            padding: .65rem .8rem;
            border: 1px solid rgba(148, 163, 184, 0.25);
        }

        .stat-label {
            font-size: .7rem;
            color: rgba(148, 163, 184, 1);
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 1.02rem;
            font-weight: 600;
            margin-top: .18rem;
            font-variant-numeric: tabular-nums;
        }

        /* FILTER CHIPS */
        .chip {
            padding: .16rem .55rem;
            font-size: .78rem;
            border-radius: 999px;
            text-decoration: none;
            border: 1px solid rgba(148, 163, 184, .5);
            background: rgba(248, 250, 252, .9);
            margin-right: .25rem;
            display: inline-block;
        }

        .chip-active {
            background: var(--rts-main-soft);
            color: var(--rts-main-strong);
            border-color: var(--rts-main-strong);
        }

        /* DESKTOP TABLE */
        .table-card {
            background: var(--card);
            border-radius: 10px;
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
            padding: .45rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, .22);
            vertical-align: top;
        }

        .table thead {
            background: rgba(15, 23, 42, 0.035);
        }

        /* STATUS BADGE */
        .badge-status {
            padding: .12rem .48rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            line-height: 1.2;
        }

        .badge-status.pending {
            background: rgba(59, 130, 246, .10);
            color: rgba(30, 64, 175, 1);
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

        /* CODE + QTY BADGES */
        .code-badge {
            display: inline-flex;
            align-items: center;
            gap: .28rem;
            border-radius: 999px;
            padding: .1rem .5rem;
            font-size: .78rem;
            background: rgba(15, 23, 42, 0.03);
            border: 1px solid rgba(148, 163, 184, 0.4);
        }

        .code-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: var(--rts-main-strong);
        }

        .qty-badges {
            display: inline-flex;
            flex-wrap: wrap;
            gap: .25rem;
            margin-top: .25rem;
        }

        .qty-badge {
            border-radius: 999px;
            padding: .06rem .45rem;
            font-size: .72rem;
            background: rgba(15, 23, 42, 0.02);
            border: 1px solid rgba(148, 163, 184, .5);
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

        .item-line {
            font-size: .8rem;
        }

        .item-line .mono {
            font-size: .78rem;
        }

        .item-sisa {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            margin-left: .35rem;
            font-size: .74rem;
            padding: .02rem .4rem;
            border-radius: 999px;
            background: rgba(45, 212, 191, 0.09);
            color: var(--rts-main-strong);
        }

        .link-detail {
            color: var(--rts-main-strong);
            text-decoration: none;
            font-size: .78rem;
        }

        .link-detail:hover {
            text-decoration: underline;
        }

        /* MOBILE CARDS */
        .mobile-list {
            display: none;
        }

        .rts-card {
            background: var(--card);
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, .32);
            padding: .75rem .85rem;
            margin-bottom: .65rem;
        }

        .rts-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            margin-bottom: .25rem;
        }

        .rts-card-meta {
            font-size: .78rem;
            color: rgba(100, 116, 139, 1);
            margin-bottom: .25rem;
        }

        .rts-card-gudang {
            font-size: .8rem;
            margin-bottom: .2rem;
        }

        .rts-card-gudang span {
            display: inline-block;
        }

        .total-sisa-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .12rem .55rem;
            font-size: .78rem;
            background: var(--rts-main-soft);
            color: var(--rts-main-strong);
            margin-bottom: .2rem;
        }

        .total-sisa-pill span.mono {
            font-weight: 600;
        }

        .rts-card-items {
            font-size: .8rem;
            margin-top: .15rem;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            margin-bottom: .06rem;
        }

        .item-row-left {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
        }

        .badge-sisa {
            border-radius: 999px;
            padding: .03rem .45rem;
            font-size: .74rem;
            background: rgba(45, 212, 191, 0.12);
            color: var(--rts-main-strong);
        }

        .btn-process {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: .42rem .8rem;
            border-radius: 999px;
            border: none;
            background: var(--rts-main-strong);
            color: #ecfeff;
            font-size: .82rem;
            font-weight: 600;
            text-decoration: none;
            margin-top: .45rem;
        }

        .btn-process span.icon {
            font-size: .9rem;
            margin-right: .2rem;
        }

        .btn-detail-ghost {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: .38rem .8rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .7);
            background: transparent;
            color: #0f172a;
            font-size: .8rem;
            font-weight: 500;
            text-decoration: none;
            margin-top: .45rem;
        }

        .btn-detail-ghost:hover,
        .btn-process:hover {
            text-decoration: none;
            filter: brightness(0.98);
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

        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
            <div>
                <div class="page-title">PRD • Permintaan Stok dari RTS</div>
                <div class="page-subtitle mt-1">
                    Fokus ke permintaan yang perlu segera diproses Gudang Produksi.
                </div>
            </div>
        </div>

        {{-- STATS --}}
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Dokumen</div>
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
                @foreach (['all' => 'Semua', 'pending' => 'Pending', 'submitted' => 'Submitted', 'partial' => 'Partial', 'completed' => 'Selesai'] as $key => $label)
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
                        <th style="width: 85px;">Tanggal</th>
                        <th style="width: 200px;">Kode & Qty</th>
                        <th>Gudang</th>
                        <th style="width: 260px;">Item</th>
                        <th style="width: 130px;">Status</th>
                        <th style="width: 90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stockRequests as $req)
                        @php
                            $lines = $req->lines->take(3);
                            $extra = $req->lines->count() - 3;

                            $totalReq = (float) ($req->total_requested_qty ?? 0);
                            $totalIssued = (float) ($req->total_issued_qty ?? 0);
                            $outstanding = max($totalReq - $totalIssued, 0);

                            $statusLabel = match ($req->status) {
                                'submitted' => 'Menunggu Diproses',
                                'partial' => 'Sebagian Terkirim',
                                'completed' => 'Selesai',
                                default => ucfirst($req->status),
                            };
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
                                @if ($totalReq > 0)
                                    <div class="qty-badges">
                                        <span class="qty-badge">
                                            Diminta
                                            <span class="mono">{{ (int) $totalReq }}</span>
                                        </span>
                                        @if ($outstanding > 0)
                                            <span class="qty-badge qty-badge--sisa">
                                                Sisa
                                                <span class="mono">{{ (int) $outstanding }}</span>
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                {{ $req->sourceWarehouse?->name ?? '-' }}<br>
                                <span class="muted">
                                    → {{ $req->destinationWarehouse?->name ?? '-' }}
                                </span>
                            </td>
                            <td>
                                @foreach ($lines as $line)
                                    @php
                                        $lineReq = (float) $line->qty_request;
                                        $lineIssued = (float) $line->qty_issued;
                                        $lineOutstanding = max($lineReq - $lineIssued, 0);
                                    @endphp
                                    <div class="item-line">
                                        <span class="mono">{{ $line->item?->code ?? '-' }}</span> :
                                        <strong>{{ $lineReq }}</strong> pcs
                                        @if ($lineOutstanding > 0)
                                            <span class="item-sisa">
                                                sisa
                                                <span class="mono">{{ $lineOutstanding }}</span>
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                                @if ($extra > 0)
                                    <div class="muted">+{{ $extra }} item lainnya</div>
                                @endif
                            </td>
                            <td>
                                <div
                                    class="badge-status
                                    {{ $req->status === 'completed' ? 'completed' : '' }}
                                    {{ $req->status === 'partial' ? 'partial' : '' }}
                                    {{ in_array($req->status, ['submitted', 'draft']) ? 'pending' : '' }}
                                    {{ $req->status === 'draft' ? 'draft' : '' }}">
                                    {{ $statusLabel }}
                                </div>
                            </td>
                            <td>
                                @if (in_array($req->status, ['submitted', 'partial']))
                                    <a href="{{ route('prd.stock-requests.edit', $req) }}" class="link-detail">
                                        Proses
                                    </a>
                                @else
                                    <a href="{{ route('prd.stock-requests.show', $req) }}" class="link-detail">
                                        Detail
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                Belum ada permintaan RTS.
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

        {{-- MOBILE CARD LIST (MINIMALIS + FOKUS SISA) --}}
        <div class="mobile-list d-md-none">
            @forelse ($stockRequests as $req)
                @php
                    $lines = $req->lines->take(2);
                    $extra = $req->lines->count() - 2;

                    $totalReq = (float) ($req->total_requested_qty ?? 0);
                    $totalIssued = (float) ($req->total_issued_qty ?? 0);
                    $outstanding = max($totalReq - $totalIssued, 0);

                    $statusLabel = match ($req->status) {
                        'submitted' => 'Menunggu',
                        'partial' => 'Sebagian',
                        'completed' => 'Selesai',
                        default => ucfirst($req->status),
                    };

                    $isProcessable = in_array($req->status, ['submitted', 'partial']);
                @endphp

                <div class="rts-card">
                    <div class="rts-card-header">
                        <div class="code-badge">
                            <span class="dot"></span>
                            <span class="mono">{{ $req->code }}</span>
                        </div>
                        <div
                            class="badge-status
                            {{ $req->status === 'completed' ? 'completed' : '' }}
                            {{ $req->status === 'partial' ? 'partial' : '' }}
                            {{ in_array($req->status, ['submitted', 'draft']) ? 'pending' : '' }}
                            {{ $req->status === 'draft' ? 'draft' : '' }}">
                            {{ $statusLabel }}
                        </div>
                    </div>

                    <div class="rts-card-meta">
                        {{ $req->date?->format('d M Y') ?? '-' }} •
                        <span class="mono">{{ $req->created_at?->format('H:i') }}</span>
                    </div>

                    <div class="rts-card-gudang">
                        {{ $req->sourceWarehouse?->name ?? '-' }} →
                        <span class="muted">{{ $req->destinationWarehouse?->name ?? '-' }}</span>
                    </div>

                    @if ($outstanding > 0)
                        <div class="total-sisa-pill">
                            <span>Sisa total</span>
                            <span class="mono">{{ (int) $outstanding }}</span>
                        </div>
                    @endif

                    <div class="rts-card-items">
                        @foreach ($lines as $line)
                            @php
                                $lineReq = (float) $line->qty_request;
                                $lineIssued = (float) $line->qty_issued;
                                $lineOutstanding = max($lineReq - $lineIssued, 0);
                            @endphp
                            <div class="item-row">
                                <div class="item-row-left">
                                    <span class="mono">{{ $line->item?->code ?? '-' }}</span>
                                    <span class="mono">{{ (int) $lineReq }}</span>
                                </div>
                                @if ($lineOutstanding > 0)
                                    <span class="badge-sisa mono">
                                        sisa {{ (int) $lineOutstanding }}
                                    </span>
                                @endif
                            </div>
                        @endforeach

                        @if ($extra > 0)
                            <div class="muted mt-1">+{{ $extra }} item</div>
                        @endif
                    </div>

                    @if ($isProcessable)
                        <a href="{{ route('prd.stock-requests.edit', $req) }}" class="btn-process">
                            <span class="icon">⚡</span>
                            Proses permintaan
                        </a>
                    @else
                        <a href="{{ route('prd.stock-requests.show', $req) }}" class="btn-detail-ghost">
                            Lihat detail
                        </a>
                    @endif
                </div>
            @empty
                <div class="rts-card">
                    <div class="text-center muted">
                        Belum ada permintaan RTS.
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
