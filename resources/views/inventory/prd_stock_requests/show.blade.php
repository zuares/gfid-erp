{{-- resources/views/inventory/prd_stock_requests/show.blade.php --}}
@extends('layouts.app')

@section('title', 'PRD • Detail Permintaan RTS ' . $stockRequest->code)

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
            padding: .9rem .9rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.08) 0,
                    rgba(45, 212, 191, 0.10) 28%,
                    #f9fafb 70%);
        }

        .card-main {
            background: var(--card);
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.26);
            overflow: hidden;
        }

        .card-head {
            padding: 1rem 1.1rem .85rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.22);
        }

        .card-body {
            padding: .95rem 1.1rem 1.1rem;
        }

        .page-title {
            font-size: 1.05rem;
            font-weight: 650;
            margin: 0;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-variant-numeric: tabular-nums;
        }

        .badge-status {
            padding: .12rem .5rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            line-height: 1.2;
            border: 1px solid rgba(148, 163, 184, .35);
            background: rgba(15, 23, 42, 0.02);
        }

        .badge-status.submitted {
            background: rgba(59, 130, 246, .10);
            color: rgba(30, 64, 175, 1);
            border-color: rgba(59, 130, 246, .22);
        }

        .badge-status.shipped {
            background: rgba(45, 212, 191, .14);
            color: rgba(15, 118, 110, 1);
            border-color: rgba(45, 212, 191, .30);
        }

        .badge-status.partial {
            background: rgba(234, 179, 8, .12);
            color: rgba(133, 77, 14, 1);
            border-color: rgba(234, 179, 8, .22);
        }

        .badge-status.completed {
            background: rgba(22, 163, 74, .12);
            color: rgba(22, 101, 52, 1);
            border-color: rgba(22, 163, 74, .22);
        }

        .code-badge {
            display: inline-flex;
            align-items: center;
            gap: .32rem;
            border-radius: 999px;
            padding: .1rem .55rem;
            font-size: .8rem;
            background: rgba(15, 23, 42, 0.03);
            border: 1px solid rgba(148, 163, 184, 0.4);
        }

        .code-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: var(--rts-strong);
        }

        .meta-row {
            margin-top: .7rem;
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .28rem .7rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .45);
            background: rgba(15, 23, 42, 0.02);
            font-size: .78rem;
        }

        .pill strong {
            font-weight: 650;
        }

        .pill--sisa {
            border-color: var(--rts-strong);
            background: var(--rts-soft);
            color: var(--rts-strong);
        }

        .btn-row {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-top: .8rem;
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            padding: .38rem .95rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .75);
            background: transparent;
            color: #0f172a;
            font-size: .82rem;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            padding: .40rem 1.0rem;
            border-radius: 999px;
            border: none;
            background: var(--rts-strong);
            color: #e6fffb;
            font-size: .82rem;
            font-weight: 700;
            text-decoration: none;
        }

        .table-card {
            background: var(--card);
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .26);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
        }

        .table th,
        .table td {
            padding: .44rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, .20);
            vertical-align: middle;
        }

        .table thead {
            background: rgba(15, 23, 42, 0.035);
        }

        .table thead th {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #6b7280;
        }

        .item-name {
            font-size: .8rem;
            color: #6b7280;
            margin-top: .12rem;
        }

        .qty-badge {
            display: inline-flex;
            align-items: center;
            gap: .28rem;
            border-radius: 999px;
            padding: .08rem .5rem;
            font-size: .74rem;
            border: 1px solid rgba(148, 163, 184, .45);
            background: rgba(15, 23, 42, 0.02);
        }

        .qty-badge--ok {
            border-color: rgba(22, 163, 74, .35);
            background: rgba(22, 163, 74, .08);
            color: rgba(22, 101, 52, 1);
        }

        .qty-badge--warn {
            border-color: rgba(234, 179, 8, .35);
            background: rgba(234, 179, 8, .10);
            color: rgba(133, 77, 14, 1);
        }

        /* Mobile: ubah table jadi card per line */
        .mobile-list {
            display: none;
        }

        .line-card {
            background: var(--card);
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .26);
            padding: .75rem .85rem;
            margin-bottom: .6rem;
        }

        .line-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .7rem;
        }

        .line-code {
            font-weight: 700;
        }

        .line-sub {
            font-size: .78rem;
            color: rgba(100, 116, 139, 1);
            margin-top: .1rem;
        }

        .line-qty {
            margin-top: .55rem;
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }

        .note {
            margin-top: .5rem;
            font-size: .78rem;
            color: rgba(100, 116, 139, 1);
            border-left: 3px solid rgba(148, 163, 184, .45);
            padding-left: .6rem;
        }

        @media (max-width: 768px) {
            .page-wrap {
                padding-inline: .7rem;
            }

            .table-card {
                display: none;
            }

            .mobile-list {
                display: block;
            }

            .card-head,
            .card-body {
                padding-inline: .85rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $status = $stockRequest->status;

        $statusLabel = match ($status) {
            'submitted' => 'Menunggu PRD',
            'shipped' => 'Transit',
            'partial' => 'Sebagian',
            'completed' => 'Selesai',
            default => ucfirst($status),
        };

        // PRD view: fokus ke dispatched (PRD → Transit)
        $totalRequested = (float) $stockRequest->lines->sum('qty_request');
        $totalDispatched = (float) $stockRequest->lines->sum('qty_dispatched');
        $totalOutstanding = max($totalRequested - $totalDispatched, 0);

        $isProcessable = in_array($status, ['submitted', 'shipped', 'partial']);

        // dadakan indicator (picked > 0 sementara status masih submitted)
        $totalPicked = (float) $stockRequest->lines->sum('qty_picked');
        $isDadakan = $totalPicked > 0 && $status === 'submitted';
    @endphp

    <div class="page-wrap">

        <div class="card-main">
            <div class="card-head">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                    <div>
                        <div class="page-title">Detail Permintaan RTS</div>
                        <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
                            <span class="code-badge">
                                <span class="dot"></span>
                                <span class="mono">{{ $stockRequest->code }}</span>
                            </span>

                            <span class="badge-status {{ $status }}">
                                {{ $statusLabel }}
                            </span>

                            @if ($isDadakan)
                                <span class="badge-status partial">⚡ Dadakan</span>
                            @endif
                        </div>
                    </div>

                    <div class="mono" style="font-size:.82rem;">
                        {{ $stockRequest->date?->format('d M Y') ?? '-' }}
                    </div>
                </div>

                <div class="meta-row">
                    <div class="pill">
                        <span>PRD</span>
                        <strong class="mono">{{ $stockRequest->sourceWarehouse?->code ?? 'WH-PRD' }}</strong>
                    </div>
                    <div class="pill">
                        <span>RTS</span>
                        <strong class="mono">{{ $stockRequest->destinationWarehouse?->code ?? 'WH-RTS' }}</strong>
                    </div>

                    <div class="pill">
                        <span>Diminta</span>
                        <strong class="mono">{{ (int) $totalRequested }}</strong>
                    </div>

                    <div class="pill">
                        <span>Dikirim</span>
                        <strong class="mono">{{ (int) $totalDispatched }}</strong>
                    </div>

                    @if ($totalOutstanding > 0)
                        <div class="pill pill--sisa">
                            <span>Sisa</span>
                            <strong class="mono">{{ (int) $totalOutstanding }}</strong>
                        </div>
                    @endif
                </div>

                <div class="btn-row">
                    <a href="{{ route('prd.stock-requests.index') }}" class="btn-outline">← Kembali</a>

                    @if ($isProcessable)
                        <a href="{{ route('prd.stock-requests.edit', $stockRequest) }}" class="btn-primary">
                            🚚 Proses Kirim
                        </a>
                    @endif
                </div>

                @if (!empty($stockRequest->notes))
                    <div class="note">
                        {!! nl2br(e($stockRequest->notes)) !!}
                    </div>
                @endif
            </div>

            <div class="card-body">
                {{-- DESKTOP TABLE --}}
                <div class="table-card">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Item</th>
                                <th class="text-end" style="width: 90px;">Diminta</th>
                                <th class="text-end" style="width: 110px;">Dikirim</th>
                                <th class="text-end" style="width: 100px;">Sisa</th>
                                <th style="width: 140px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stockRequest->lines as $i => $line)
                                @php
                                    $req = (float) $line->qty_request;
                                    $disp = (float) ($line->qty_dispatched ?? 0);
                                    $out = max($req - $disp, 0);
                                    $lineOk = $out <= 0.0000001 && $req > 0;
                                @endphp

                                <tr>
                                    <td class="mono">{{ $i + 1 }}</td>

                                    <td>
                                        <div class="fw-semibold">
                                            <span class="mono">{{ $line->item?->code ?? '-' }}</span>
                                        </div>
                                        <div class="item-name">{{ $line->item?->name ?? '' }}</div>
                                        @if (!empty($line->notes))
                                            <div class="item-name">Catatan: {{ $line->notes }}</div>
                                        @endif
                                    </td>

                                    <td class="text-end mono">{{ (int) $req }}</td>
                                    <td class="text-end mono">{{ (int) $disp }}</td>
                                    <td class="text-end mono">{{ (int) $out }}</td>

                                    <td>
                                        @if ($lineOk)
                                            <span class="qty-badge qty-badge--ok">✔ Terpenuhi</span>
                                        @elseif ($disp > 0)
                                            <span class="qty-badge qty-badge--warn">◐ Sebagian</span>
                                        @else
                                            <span class="qty-badge">• Belum</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE LIST --}}
                <div class="mobile-list">
                    @foreach ($stockRequest->lines as $i => $line)
                        @php
                            $req = (float) $line->qty_request;
                            $disp = (float) ($line->qty_dispatched ?? 0);
                            $out = max($req - $disp, 0);
                            $lineOk = $out <= 0.0000001 && $req > 0;
                        @endphp

                        <div class="line-card">
                            <div class="line-top">
                                <div>
                                    <div class="line-code mono">{{ $line->item?->code ?? '-' }}</div>
                                    <div class="line-sub">{{ $line->item?->name ?? '' }}</div>
                                </div>

                                <div class="text-end">
                                    @if ($lineOk)
                                        <span class="qty-badge qty-badge--ok">✔</span>
                                    @elseif ($disp > 0)
                                        <span class="qty-badge qty-badge--warn">◐</span>
                                    @else
                                        <span class="qty-badge">•</span>
                                    @endif
                                </div>
                            </div>

                            <div class="line-qty">
                                <span class="qty-badge">Diminta <span class="mono">{{ (int) $req }}</span></span>
                                <span class="qty-badge">Dikirim <span class="mono">{{ (int) $disp }}</span></span>
                                @if ($out > 0)
                                    <span class="qty-badge qty-badge--warn">Sisa <span
                                            class="mono">{{ (int) $out }}</span></span>
                                @endif
                            </div>

                            @if (!empty($line->notes))
                                <div class="note">Catatan: {{ $line->notes }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>

            </div>
        </div>

    </div>
@endsection
