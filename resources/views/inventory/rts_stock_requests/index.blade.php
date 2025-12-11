{{-- resources/views/inventory/rts_stock_requests/index.blade.php --}}
@extends('layouts.app')

@section('title', 'RTS • Permintaan Stok')

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
            padding: .75rem .75rem 3rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.08) 0,
                    rgba(45, 212, 191, 0.1) 28%,
                    #f9fafb 70%);
        }

        .page-title {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .page-subtitle {
            font-size: .85rem;
            color: rgba(100, 116, 139, 1);
        }

        .btn-primary {
            background: var(--rts-main-strong);
            padding: .38rem .9rem;
            border-radius: 999px;
            font-size: .82rem;
            color: #fff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            box-shadow: 0 8px 24px rgba(15, 118, 110, 0.3);
        }

        .btn-primary:hover {
            background: rgba(4, 120, 87, 1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
            margin: .9rem 0;
        }

        .stat-card {
            background: var(--card);
            border-radius: 12px;
            padding: .7rem .85rem;
            border: 1px solid rgba(148, 163, 184, 0.25);
        }

        .stat-label {
            font-size: .7rem;
            color: rgba(148, 163, 184, 1);
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 1.05rem;
            font-weight: 600;
            margin-top: .2rem;
            font-variant-numeric: tabular-nums;
        }

        .chip {
            padding: .18rem .6rem;
            font-size: .78rem;
            border-radius: 999px;
            text-decoration: none;
            border: 1px solid rgba(148, 163, 184, .5);
            background: rgba(248, 250, 252, .9);
            margin-right: .3rem;
        }

        .chip-active {
            background: var(--rts-main-soft);
            color: var(--rts-main-strong);
            border-color: var(--rts-main-strong);
        }

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
            border-bottom: 1px solid rgba(148, 163, 184, .22);
            vertical-align: top;
        }

        .table thead {
            background: rgba(15, 23, 42, 0.05);
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular;
            font-variant-numeric: tabular-nums;
        }

        .muted {
            color: rgba(148, 163, 184, 1);
            font-size: .75rem;
        }

        .badge-status {
            padding: .12rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: .3rem;
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

        .item-line {
            font-size: .8rem;
            font-variant-numeric: tabular-nums;
        }

        .link-detail {
            color: var(--rts-main-strong);
            text-decoration: none;
            font-size: .78rem;
        }

        .qty-pill {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            border-radius: 999px;
            padding: .1rem .5rem;
            font-size: .74rem;
            background: rgba(15, 23, 42, 0.03);
            color: rgba(30, 64, 175, 1);
            margin-top: .28rem;
        }

        .qty-pill span.mono {
            font-size: .76rem;
        }

        .code-badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            border-radius: 999px;
            padding: .12rem .6rem;
            font-size: .78rem;
            background: rgba(15, 23, 42, 0.04);
            border: 1px solid rgba(148, 163, 184, 0.4);
            color: rgba(15, 23, 42, 0.86);
        }

        .code-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: var(--rts-main-strong);
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .table-card {
                border-radius: 10px;
            }

            .table {
                min-width: 780px;
            }

            .page-wrap {
                padding-inline: .5rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="flex items-center justify-between mb-3 gap-2">
            <div>
                <div class="page-title">RTS • Permintaan Stok</div>
                <div class="page-subtitle mt-1">
                    Permintaan barang dari Gudang Produksi ke Gudang RTS.
                </div>
            </div>

            {{-- Tombol cepat: buka dokumen hari ini kalau sudah ada --}}
            <a href="{{ route('rts.stock-requests.today') }}" class="btn-primary">
                + Tambah permintaan
            </a>
        </div>

        {{-- STATS --}}
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Dokumen</div>
                <div class="stat-value mono">{{ $stats['total'] }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Pending</div>
                <div class="stat-value mono">{{ $stats['pending'] }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Selesai</div>
                <div class="stat-value mono">{{ $stats['completed'] }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Qty Belum Keluar</div>
                <div class="stat-value mono">{{ $outstandingQty }}</div>
            </div>
        </div>

        {{-- FILTERS --}}
        @php
            $statusQueryBase = request()->except('status', 'page');
            $periodQueryBase = request()->except('period', 'page');
        @endphp

        <div class="flex justify-between mb-3 flex-wrap gap-2">
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

        {{-- TABLE --}}
        <div class="table-card">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 85px;">Tanggal</th>
                        <th style="width: 150px;">Kode</th>
                        <th>Gudang</th>
                        <th style="width: 260px;">Item Diminta</th>
                        <th style="width: 170px;">Peminta</th>
                        <th style="width: 130px;">Status</th>
                        <th style="width: 60px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($stockRequests as $req)
                        @php
                            $lines = $req->lines->take(3);
                            $extra = $req->lines->count() - 3;
                            $status = $req->status;
                            $statusLabel = match ($status) {
                                'submitted' => 'Menunggu PRD',
                                'partial' => 'Sebagian Keluar',
                                'completed' => 'Selesai',
                                default => ucfirst($status),
                            };

                            $totalReq = (float) ($req->total_requested_qty ?? 0);
                            $totalIssued = (float) ($req->total_issued_qty ?? 0);
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

                                @if ($totalReq > 0)
                                    <div class="qty-pill">
                                        <span class="mono">{{ (int) $totalReq }}</span> diminta
                                        @if ($outstanding > 0)
                                            • <span class="mono">{{ (int) $outstanding }}</span> sisa
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td>
                                {{ $req->sourceWarehouse?->name }} → {{ $req->destinationWarehouse?->name }}
                            </td>

                            <td>
                                @foreach ($lines as $line)
                                    <div class="item-line">
                                        {{ $line->item?->code }} :
                                        <strong>{{ (float) $line->qty_request }}</strong> pcs
                                    </div>
                                @endforeach

                                @if ($extra > 0)
                                    <div class="muted">+{{ $extra }} item lainnya</div>
                                @endif
                            </td>

                            <td>
                                {{ $req->requestedBy?->name }}<br>
                                <span class="muted mono">{{ $req->created_at->format('d M H:i') }}</span>
                            </td>

                            <td>
                                <div
                                    class="badge-status
                                    {{ $status === 'completed' ? 'completed' : '' }}
                                    {{ $status === 'partial' ? 'partial' : '' }}
                                    {{ in_array($status, ['submitted', 'draft']) ? 'pending' : '' }}">
                                    {{ $statusLabel }}
                                </div>
                            </td>

                            <td>
                                <a href="{{ route('rts.stock-requests.show', $req) }}" class="link-detail">Detail</a>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-sm text-slate-500 py-4">
                                Belum ada data permintaan RTS.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-3">
                {{ $stockRequests->links() }}
            </div>
        </div>

    </div>
@endsection
