{{-- resources/views/inventory/rts_stock_requests/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Stock Request RTS • ' . $stockRequest->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 3rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 26%,
                    #f9fafb 60%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            box-shadow:
                0 8px 24px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .card-header {
            padding: 1rem 1.25rem .75rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
        }

        .card-body {
            padding: .75rem 1.25rem 1rem;
        }

        .section-title {
            font-size: .9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border-radius: 999px;
            padding: .2rem .7rem;
            font-size: .78rem;
            font-weight: 500;
        }

        .badge-status span {
            font-variant-numeric: tabular-nums;
        }

        .badge-status.pending {
            background: rgba(59, 130, 246, 0.12);
            color: rgba(30, 64, 175, 1);
        }

        .badge-status.partial {
            background: rgba(234, 179, 8, 0.12);
            color: rgba(133, 77, 14, 1);
        }

        .badge-status.completed {
            background: rgba(34, 197, 94, 0.12);
            color: rgba(22, 101, 52, 1);
        }

        .badge-warehouse {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .15rem .5rem;
            border-radius: 999px;
            font-size: .75rem;
            border: 1px solid rgba(148, 163, 184, 0.55);
            background: color-mix(in srgb, var(--card) 80%, rgba(59, 130, 246, .12));
        }

        .badge-warehouse span.code {
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help-text {
            font-size: .75rem;
            color: rgba(148, 163, 184, 1);
        }

        .table-wrap {
            margin-top: .75rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
        }

        .table thead {
            background: color-mix(in srgb, var(--card) 80%, rgba(15, 23, 42, 0.06));
        }

        .table th,
        .table td {
            padding: .45rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
            vertical-align: top;
        }

        .table th {
            text-align: left;
            font-weight: 600;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
        }

        .badge-over {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            border-radius: 999px;
            padding: .15rem .6rem;
            font-size: .75rem;
            background: rgba(248, 250, 252, 0.9);
            color: rgba(124, 45, 18, 1);
            border: 1px dashed rgba(248, 113, 113, 0.7);
        }

        .badge-over-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(248, 113, 113, 1);
        }

        .line-notes {
            font-size: .75rem;
            color: rgba(100, 116, 139, 1);
            margin-top: .2rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .8rem;
            color: rgba(100, 116, 139, 1);
            text-decoration: none;
            margin-bottom: .35rem;
        }

        .back-link:hover {
            color: rgba(30, 64, 175, 1);
        }

        .btn-edit-today {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .3rem .85rem;
            border-radius: 999px;
            font-size: .8rem;
            text-decoration: none;
            border: 1px solid rgba(45, 212, 191, 0.8);
            background: rgba(45, 212, 191, 0.16);
            color: rgba(15, 118, 110, 1);
            box-shadow: 0 10px 25px rgba(15, 118, 110, 0.28);
            font-weight: 500;
        }

        .btn-edit-today:hover {
            background: rgba(45, 212, 191, 0.24);
        }

        @media (max-width: 768px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .card-header {
                padding-inline: .9rem;
            }

            .card-body {
                padding-inline: .9rem;
            }

            .table-wrap {
                border-radius: 10px;
                overflow-x: auto;
            }

            .table {
                min-width: 780px;
            }

            .btn-edit-today {
                box-shadow: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="mb-2 flex items-center justify-between gap-2">
            <a href="{{ route('rts.stock-requests.index') }}#today" class="back-link">
                ← Kembali ke daftar RTS
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="section-title">Stock Request • RTS ➝ PRD</div>
                        <div class="mt-1 text-sm text-slate-700 dark:text-slate-200">
                            <span class="mono font-semibold">{{ $stockRequest->code }}</span>
                            <span class="mx-1">•</span>
                            <span>{{ $stockRequest->date?->format('d M Y') }}</span>
                        </div>
                        <div class="mt-1 help-text">
                            {{ $stockRequest->requestedBy?->name ?? '—' }}
                            ·
                            <span class="mono">{{ $stockRequest->created_at?->format('d M Y H:i') ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-2">
                        @if ($stockRequest->date && $stockRequest->date->isToday())
                            <a href="{{ route('rts.stock-requests.create', ['date' => $stockRequest->date->toDateString()]) }}"
                                class="btn-edit-today">
                                ✏️ Ubah permintaan hari ini
                            </a>
                        @endif

                        @php
                            $status = $stockRequest->status;
                            $statusLabel = match ($status) {
                                'submitted' => 'Menunggu PRD',
                                'partial' => 'Sebagian keluar',
                                'completed' => 'Selesai',
                                default => ucfirst($status ?? 'Draft'),
                            };

                            // total sisa semua line (display, tanpa negatif)
                            $totalRemaining = $stockRequest->lines->sum(function ($line) {
                                $req = (float) ($line->qty_request ?? 0);
                                $issued = $line->qty_issued !== null ? (float) $line->qty_issued : 0;
                                $diff = $req - $issued;
                                return $diff > 0 ? $diff : 0;
                            });
                        @endphp

                        <div
                            class="badge-status
                            {{ $status === 'completed' ? 'completed' : '' }}
                            {{ $status === 'partial' ? 'partial' : '' }}
                            {{ in_array($status, ['submitted', 'draft', null], true) ? 'pending' : '' }}">
                            <span class="inline-block w-2 h-2 rounded-full"
                                style="background:
                                    {{ $status === 'completed'
                                        ? 'rgba(34,197,94,1)'
                                        : ($status === 'partial'
                                            ? 'rgba(234,179,8,1)'
                                            : 'rgba(59,130,246,1)') }};
                                "></span>
                            <span>{{ $statusLabel }}</span>
                        </div>

                        <div class="flex items-center gap-1 mt-1">
                            <div class="badge-warehouse">
                                <span class="code">{{ $stockRequest->sourceWarehouse?->code }}</span>
                                <span>{{ $stockRequest->sourceWarehouse?->name }}</span>
                            </div>
                            <span style="font-size:.9rem; opacity:.7;">↓</span>
                            <div class="badge-warehouse">
                                <span class="code">{{ $stockRequest->destinationWarehouse?->code }}</span>
                                <span>{{ $stockRequest->destinationWarehouse?->name }}</span>
                            </div>
                        </div>

                        @if ($totalRemaining > 0)
                            <div class="mt-1 text-xs text-rose-600 mono">
                                Sisa total: {{ $totalRemaining }} pcs
                            </div>
                        @else
                            <div class="mt-1 text-xs text-emerald-600 mono">
                                Permintaan terpenuhi
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if ($stockRequest->notes)
                    <div class="mb-3">
                        <div class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500 mb-1">
                            Catatan
                        </div>
                        <div class="text-sm text-slate-800 dark:text-slate-100 whitespace-pre-line">
                            {{ $stockRequest->notes }}
                        </div>
                    </div>
                @endif

                {{-- SUMMARY OVER-REQUEST (singkat) --}}
                @if (!empty($summary['has_over_request']))
                    <div class="mb-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        {{ $summary['over_lines_count'] }} baris over-request ·
                        selisih {{ $summary['over_qty_total'] }} pcs dibanding stok PRD (snapshot).
                    </div>
                @endif

                <div class="flex items-center justify-between gap-2 mb-2">
                    <div class="section-title">Detail Item</div>
                    <div class="help-text">
                        Sisa = Request - Keluar PRD
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 32px;">#</th>
                                <th>Item FG</th>
                                <th style="width: 14%;">Qty Request</th>
                                <th style="width: 18%;">Qty Keluar PRD</th>
                                <th style="width: 16%;">Sisa</th>
                                <th style="width: 18%;">Stok PRD (snapshot)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stockRequest->lines as $index => $line)
                                @php
                                    $qtyRequest = (float) ($line->qty_request ?? 0);
                                    $snapshot =
                                        $line->stock_snapshot_at_request !== null
                                            ? (float) $line->stock_snapshot_at_request
                                            : null;
                                    $qtyIssued = $line->qty_issued !== null ? (float) $line->qty_issued : null;
                                    $isOverRequest = $snapshot !== null && $qtyRequest > $snapshot;

                                    // Sisa = request - issued (kalau belum ada issue, pakai request)
                                    $diff = $qtyIssued !== null ? $qtyRequest - $qtyIssued : $qtyRequest;
                                    $displayDiff = $diff < 0 ? 0 : $diff;
                                @endphp
                                <tr>
                                    <td class="mono align-top">
                                        {{ $index + 1 }}
                                    </td>
                                    <td>
                                        <div class="font-semibold text-sm">
                                            {{ $line->item?->code ?? '—' }}
                                            <span class="text-slate-500">—</span>
                                            {{ $line->item?->name ?? '—' }}
                                        </div>

                                        @if ($line->notes)
                                            <div class="line-notes">
                                                {{ $line->notes }}
                                            </div>
                                        @endif

                                        @if ($isOverRequest)
                                            <div class="mt-1">
                                                <span class="badge-over"
                                                    title="Qty request lebih besar dari stok snapshot saat request">
                                                    <span class="badge-over-dot"></span>
                                                    Over-request
                                                    <span class="mono">
                                                        ({{ $qtyRequest }} &gt; {{ $snapshot }})
                                                    </span>
                                                </span>
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Qty Request --}}
                                    <td class="mono align-top">
                                        {{ $qtyRequest }}
                                        <span class="text-slate-400 text-[.7rem]">pcs</span>
                                    </td>

                                    {{-- Qty Keluar PRD --}}
                                    <td class="mono align-top">
                                        @if ($qtyIssued !== null)
                                            {{ $qtyIssued }}
                                            <span class="text-slate-400 text-[.7rem]">pcs</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>

                                    {{-- Sisa (Req - Keluar) --}}
                                    <td class="mono align-top">
                                        <span
                                            class="{{ $displayDiff > 0 ? 'text-rose-600 font-semibold' : 'text-emerald-600' }}">
                                            {{ $displayDiff }}
                                        </span>
                                        <span class="text-slate-400 text-[.7rem]">pcs</span>
                                    </td>

                                    {{-- Stok PRD snapshot --}}
                                    <td class="mono align-top">
                                        @if ($snapshot !== null)
                                            {{ $snapshot }}
                                            <span class="text-slate-400 text-[.7rem]">pcs</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-sm text-slate-500 py-4">
                                        Tidak ada detail item.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                    <div>
                        Terakhir update:
                        <span class="mono">
                            {{ $stockRequest->updated_at?->format('d M Y H:i') ?? '—' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
