{{-- resources/views/inventory/prd_stock_requests/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'PRD • Kirim ke Transit • ' . $stockRequest->code)

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-strong: rgba(15, 118, 110, 1);
            --rts-soft: rgba(45, 212, 191, 0.12);

            --warn: rgba(234, 179, 8, 1);
            --warn-soft: rgba(234, 179, 8, .12);
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: 1rem .9rem 3.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.08) 0,
                    rgba(45, 212, 191, 0.10) 28%,
                    #f9fafb 70%);
        }

        .card {
            background: var(--card);
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.26);
            overflow: hidden;
        }

        .card-head {
            padding: 1rem 1.1rem .9rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.20);
        }

        .card-body {
            padding: 1rem 1.1rem 1.1rem;
        }

        .title {
            font-size: 1.08rem;
            font-weight: 700;
            margin: 0;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-variant-numeric: tabular-nums;
        }

        .row-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .8rem;
            flex-wrap: wrap;
        }

        .code-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .12rem .6rem;
            font-size: .82rem;
            background: rgba(15, 23, 42, 0.03);
            border: 1px solid rgba(148, 163, 184, 0.40);
        }

        .code-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: var(--rts-strong);
        }

        .badge-status {
            padding: .12rem .5rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            border: 1px solid rgba(148, 163, 184, .35);
            background: rgba(15, 23, 42, 0.02);
            line-height: 1.2;
        }

        .badge-status.submitted {
            background: rgba(59, 130, 246, .10);
            color: rgba(30, 64, 175, 1);
            border-color: rgba(59, 130, 246, .20);
        }

        .badge-status.shipped {
            background: rgba(45, 212, 191, .14);
            color: rgba(15, 118, 110, 1);
            border-color: rgba(45, 212, 191, .30);
        }

        .badge-status.partial {
            background: rgba(234, 179, 8, .12);
            color: rgba(133, 77, 14, 1);
            border-color: rgba(234, 179, 8, .25);
        }

        .badge-status.completed {
            background: rgba(22, 163, 74, .12);
            color: rgba(22, 101, 52, 1);
            border-color: rgba(22, 163, 74, .22);
        }

        .pill-row {
            margin-top: .75rem;
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .24rem .7rem;
            font-size: .78rem;
            border: 1px solid rgba(148, 163, 184, .45);
            background: rgba(15, 23, 42, 0.02);
        }

        .pill strong {
            font-weight: 750;
        }

        .pill--sisa {
            border-color: var(--rts-strong);
            background: var(--rts-soft);
            color: var(--rts-strong);
        }

        .pill--warn {
            border-color: rgba(234, 179, 8, .55);
            background: var(--warn-soft);
            color: rgba(133, 77, 14, 1);
        }

        .note {
            margin-top: .65rem;
            font-size: .82rem;
            color: rgba(100, 116, 139, 1);
            border-left: 3px solid rgba(148, 163, 184, .45);
            padding-left: .65rem;
        }

        .table-wrap {
            margin-top: .9rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .24);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: .84rem;
        }

        .table th,
        .table td {
            padding: .44rem .58rem;
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
            font-size: .78rem;
            color: #6b7280;
            margin-top: .10rem;
        }

        .qty-ok {
            color: rgba(22, 101, 52, 1);
        }

        .qty-warn {
            color: rgba(133, 77, 14, 1);
        }

        .error-text {
            font-size: .76rem;
            color: #dc2626;
            margin-top: .12rem;
        }

        .btn-row {
            margin-top: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .7rem;
            flex-wrap: wrap;
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .40rem .95rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.75);
            background: transparent;
            color: #0f172a;
            font-size: .82rem;
            font-weight: 650;
            text-decoration: none;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .44rem 1.05rem;
            border-radius: 999px;
            border: none;
            background: var(--rts-strong);
            color: #e6fffb;
            font-size: .84rem;
            font-weight: 750;
            text-decoration: none;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .75rem;
            }

            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                padding: .65rem .4rem;
                border-bottom: 1px solid rgba(148, 163, 184, .25);
            }

            .table tbody td {
                display: flex;
                justify-content: space-between;
                gap: .6rem;
                padding: .22rem 0;
                border-top: none;
                font-size: .88rem;
            }

            .table tbody td::before {
                content: attr(data-label);
                font-weight: 650;
                color: #64748b;
                flex: 0 0 44%;
                max-width: 55%;
            }

            .btn-row {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .btn-outline,
            .btn-primary {
                justify-content: center;
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $status = $stockRequest->status;

        $statusLabel = match ($status) {
            'submitted' => 'Menunggu',
            'shipped' => 'Transit',
            'partial' => 'Sebagian',
            'completed' => 'Selesai',
            default => ucfirst($status),
        };

        $totalRequested = (float) ($stockRequest->total_requested_qty ?? $stockRequest->lines->sum('qty_request'));
        $totalDispatched = (float) ($stockRequest->total_dispatched_qty ?? $stockRequest->lines->sum('qty_dispatched'));
        $totalPicked = (float) ($stockRequest->total_picked_qty ?? $stockRequest->lines->sum('qty_picked'));

        // PRD sisa = request - dispatched - picked
        $totalOutstanding = max($totalRequested - $totalDispatched - $totalPicked, 0);

        $hasPicked = $totalPicked > 0.0000001;
    @endphp

    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card mb-3">
            <div class="card-head">
                <div class="row-top">
                    <div>
                        <h1 class="title">Kirim ke Transit</h1>

                        <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
                            <span class="code-badge">
                                <span class="dot"></span>
                                <span class="mono">{{ $stockRequest->code }}</span>
                            </span>

                            <span class="badge-status {{ $status }}">{{ $statusLabel }}</span>

                            <span class="mono" style="font-size:.82rem;">
                                {{ $stockRequest->date?->format('d M Y') ?? '-' }}
                            </span>
                        </div>
                    </div>

                    <div class="text-end">
                        <a href="{{ route('prd.stock-requests.index') }}" class="btn-outline">← Kembali</a>
                    </div>
                </div>

                {{-- RINGKASAN --}}
                <div class="pill-row">
                    <span class="pill">
                        Diminta <strong class="mono">{{ (int) $totalRequested }}</strong>
                    </span>

                    <span class="pill">
                        Dikirim <strong class="mono">{{ (int) $totalDispatched }}</strong>
                    </span>

                    @if ($hasPicked)
                        <span class="pill pill--warn">
                            Picked <strong class="mono">{{ (int) $totalPicked }}</strong>
                        </span>
                    @endif

                    <span class="pill pill--sisa">
                        Sisa PRD <strong class="mono">{{ (int) $totalOutstanding }}</strong>
                    </span>
                </div>

                {{-- NOTE (hanya kalau ada) --}}
                @if (!empty($stockRequest->notes))
                    <div class="note">
                        {!! nl2br(e($stockRequest->notes)) !!}
                    </div>
                @endif
            </div>
        </div>

        {{-- FORM --}}
        <form action="{{ route('prd.stock-requests.confirm', $stockRequest) }}" method="POST" novalidate>
            @csrf

            <div class="card">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div style="font-size:.86rem; font-weight:700;">
                            Isi qty yang mau dikirim sekarang
                        </div>
                        <a href="{{ route('prd.stock-requests.show', $stockRequest) }}" class="btn-outline">
                            Lihat Detail
                        </a>
                    </div>

                    <div class="table-wrap">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 42px;">#</th>
                                    <th>Item</th>
                                    <th class="text-end" style="width: 90px;">Diminta</th>
                                    <th class="text-end" style="width: 90px;">Dikirim</th>
                                    <th class="text-end" style="width: 90px;">Picked</th>
                                    <th class="text-end" style="width: 90px;">Sisa</th>
                                    <th class="text-end" style="width: 90px;">Stok</th>
                                    <th class="text-end" style="width: 140px;">Kirim</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stockRequest->lines as $index => $line)
                                    @php
                                        $lineId = $line->id;

                                        $requested = (float) $line->qty_request;
                                        $dispatched = (float) ($line->qty_dispatched ?? 0);
                                        $picked = (float) ($line->qty_picked ?? 0);

                                        $outstanding = max($requested - $dispatched - $picked, 0);

                                        $available = (float) ($liveStocks[$lineId] ?? 0);

                                        // name tetap qty_issued agar cocok controller confirm kamu
                                        $inputName = "lines[{$lineId}][qty_issued]";
                                        $inputValue = old($inputName, 0);
                                        $errorKey = "lines.{$lineId}.qty_issued";
                                    @endphp

                                    <tr>
                                        <td class="mono" data-label="#">{{ $index + 1 }}</td>

                                        <td data-label="Item">
                                            <div class="fw-semibold">
                                                <span class="mono">{{ $line->item?->code ?? '-' }}</span>
                                            </div>
                                            <div class="item-name">{{ $line->item?->name ?? '' }}</div>
                                        </td>

                                        <td class="text-end mono" data-label="Diminta">{{ (int) $requested }}</td>
                                        <td class="text-end mono" data-label="Dikirim">{{ (int) $dispatched }}</td>

                                        <td class="text-end mono" data-label="Picked">
                                            <span class="{{ $picked > 0 ? 'qty-warn' : '' }}">{{ (int) $picked }}</span>
                                        </td>

                                        <td class="text-end mono" data-label="Sisa">
                                            <span
                                                class="{{ $outstanding <= 0 ? 'qty-ok' : '' }}">{{ (int) $outstanding }}</span>
                                        </td>

                                        <td class="text-end mono" data-label="Stok">
                                            <span
                                                class="{{ $available > 0 ? 'qty-ok' : '' }}">{{ (int) $available }}</span>
                                        </td>

                                        <td class="text-end" data-label="Kirim">
                                            <x-number-input name="{{ $inputName }}" :value="$inputValue" mode="integer"
                                                min="0" max="{{ (int) $outstanding }}"
                                                class="text-end mono js-qty-issued" />

                                            @error($errorKey)
                                                <div class="error-text">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="btn-row">
                        <div style="font-size:.8rem; color: rgba(100,116,139,1);">
                            Mutasi: <strong class="mono">PRD → TRANSIT</strong>
                        </div>

                        <button type="submit" class="btn-primary">
                            🚚 Simpan & Kirim
                        </button>
                    </div>

                </div>
            </div>
        </form>

        {{-- HISTORI (tetap kamu boleh pakai yang lama jika masih dibutuhkan) --}}
        @if (($movementHistory->count() ?? 0) > 0)
            <div class="card mt-3">
                <div class="card-body">
                    <div style="font-size:.86rem; font-weight:750; margin-bottom:.6rem;">
                        Histori Perpindahan
                    </div>

                    <div class="table-wrap">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 130px;">Waktu</th>
                                    <th>Item</th>
                                    <th style="width: 160px;">Rute</th>
                                    <th class="text-end" style="width: 90px;">Qty</th>
                                    <th style="width: 220px;">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($historyRows as $row)
                                    @php
                                        $qty = (float) ($row['qty'] ?? 0);
                                    @endphp
                                    <tr>
                                        <td class="mono">
                                            {{ $row['date']?->format('d M Y') ?? '-' }}<br>
                                            <span style="color: rgba(100,116,139,1); font-size:.78rem;">
                                                {{ $row['created_at']?->format('H:i:s') ?? '' }}
                                            </span>
                                        </td>

                                        <td>
                                            <div class="fw-semibold mono">{{ $row['item']?->code ?? '-' }}</div>
                                            <div class="item-name">{{ $row['item']?->name ?? '' }}</div>
                                        </td>

                                        <td class="mono">
                                            {{ $row['from_code'] ?? '?' }} → {{ $row['to_code'] ?? '?' }}
                                        </td>

                                        <td class="text-end mono">{{ (int) abs($qty) }}</td>

                                        <td style="font-size:.82rem; color: rgba(100,116,139,1);">
                                            {{ $row['notes'] ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        @endif

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = Array.from(document.querySelectorAll('.js-qty-issued'));

            inputs.forEach((input) => {
                input.addEventListener('focus', function() {
                    setTimeout(() => this.select(), 10);
                });

                input.addEventListener('keydown', function(e) {
                    const i = inputs.indexOf(this);

                    if (e.key === 'Enter' || e.key === 'ArrowDown') {
                        e.preventDefault();
                        inputs[i + 1]?.focus();
                    }

                    if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        inputs[i - 1]?.focus();
                    }
                });
            });

            if (inputs.length > 0) {
                inputs[0].focus();
                inputs[0].select();
            }
        });
    </script>
@endpush
