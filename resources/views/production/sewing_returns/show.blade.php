{{-- resources/views/production/sewing_returns/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Return ' . $return->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1000px;
            margin-inline: auto;
            padding: .8rem .8rem 3.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(16, 185, 129, .18) 0,
                    rgba(110, 231, 183, .10) 22%,
                    #f9fafb 58%);
        }

        .card {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .22);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .08), 0 0 0 1px rgba(15, 23, 42, .03);
        }

        .card-section {
            padding: .9rem 1rem;
        }

        @media(min-width:768px) {
            .page-wrap {
                padding: 1.1rem 1rem 3.5rem;
            }

            .card-section {
                padding: 1rem 1.25rem;
            }
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .badge-status {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .12em;
            border-radius: 999px;
            padding: .16rem .7rem;
            font-weight: 800;
        }

        .badge-posted {
            background: rgba(22, 163, 74, .14);
            color: #166534;
            border: 1px solid rgba(22, 163, 74, .45);
        }

        .badge-draft {
            background: rgba(148, 163, 184, .18);
            color: #4b5563;
            border: 1px solid rgba(148, 163, 184, .6);
        }

        .badge-reject {
            background: rgba(248, 113, 113, .16);
            color: #b91c1c;
            border: 1px solid rgba(248, 113, 113, .6);
        }

        .pill {
            border-radius: 999px;
            padding: .12rem .6rem;
            font-size: .72rem;
            font-weight: 800;
        }

        .pill-ok {
            background: rgba(22, 163, 74, .12);
            color: #166534;
            border: 1px solid rgba(22, 163, 74, .4);
        }

        .pill-rj {
            background: rgba(248, 113, 113, .14);
            color: #b91c1c;
            border: 1px solid rgba(248, 113, 113, .5);
        }

        .pill-total {
            background: rgba(59, 130, 246, .08);
            color: #1d4ed8;
            border: 1px solid rgba(59, 130, 246, .4);
        }

        .pill-muted {
            background: rgba(148, 163, 184, .12);
            color: #475569;
        }

        .section-title {
            font-size: .9rem;
            font-weight: 900;
            margin-bottom: .15rem;
        }

        .section-sub {
            font-size: .75rem;
            color: var(--muted);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .45rem .9rem;
            font-size: .8rem;
        }

        @media(min-width:768px) {
            .summary-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        .summary-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .summary-value {
            font-weight: 900;
        }

        .summary-ok {
            color: #166534;
        }

        .summary-rj {
            color: #b91c1c;
        }

        .summary-warn {
            color: #b45309;
        }

        .table thead th {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            border-top: none;
        }

        .table tbody td {
            font-size: .8rem;
        }

        .row-ok {
            background: rgba(240, 253, 244, .96);
        }

        .row-rj {
            background: rgba(254, 242, 242, .96);
        }

        .header-actions {
            display: flex;
            gap: .45rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .btn-pill {
            border-radius: 999px;
            padding: .25rem .75rem;
            font-size: .78rem;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }
    </style>
@endpush

@section('content')
    @php
        $lines = $return->lines ?? collect();
        $hasReject = ((float) ($totalReject ?? 0)) > 0.000001;

        $status = $return->status ?? 'draft';

        try {
            $dateLabel = $return->date
                ? (function_exists('id_day')
                    ? id_day($return->date)
                    : \Illuminate\Support\Carbon::parse($return->date)->format('d/m/Y'))
                : '-';
        } catch (\Throwable $e) {
            $dateLabel = optional($return->date)->format('d/m/Y') ?? '-';
        }

        try {
            $pickupDate = $return->pickup?->date
                ? (function_exists('id_day')
                    ? id_day($return->pickup->date)
                    : \Illuminate\Support\Carbon::parse($return->pickup->date)->format('d/m/Y'))
                : '-';
        } catch (\Throwable $e) {
            $pickupDate = optional($return->pickup?->date)->format('d/m/Y') ?? '-';
        }

        // Per item summary
        $perItem = $lines
            ->groupBy(function ($l) {
                return (int) ($l->item_id ?? (optional(optional($l->sewingPickupLine)->bundle)->finished_item_id ?? 0));
            })
            ->map(function ($group) {
                $first = $group->first();
                $bundle = optional($first->sewingPickupLine)->bundle;
                $item = optional($bundle)->finishedItem;

                return [
                    'code' => $item?->code ?? '-',
                    'name' => $item?->name ?? '',
                    'ok' => (float) $group->sum('qty_ok'),
                    'rj' => (float) $group->sum('qty_reject'),
                ];
            })
            ->sortBy('code')
            ->values();
    @endphp

    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card mb-2">
            <div class="card-section d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <div class="fw-bold mb-1">
                        Sewing Return <span class="mono">{{ $return->code }}</span>
                    </div>

                    <div class="small text-muted">
                        Tanggal setor: <span class="mono">{{ $dateLabel }}</span>
                        @if ($return->warehouse)
                            • Gudang: <span class="mono">{{ $return->warehouse->code }}</span>
                        @endif
                    </div>

                    <div class="small text-muted mt-1">
                        Pickup: <span class="mono">{{ $return->pickup?->code ?? '-' }}</span>
                        • {{ $pickupDate }}
                    </div>

                    <div class="small text-muted mt-1">
                        Operator (pickup):
                        {{ $return->pickup?->operator?->code ?? '-' }}
                        — {{ $return->pickup?->operator?->name ?? '-' }}
                    </div>
                </div>

                <div class="d-flex flex-column align-items-end gap-2">
                    <div class="d-flex gap-1 flex-wrap justify-content-end">
                        <span class="badge-status {{ $status === 'posted' ? 'badge-posted' : 'badge-draft' }}">
                            {{ strtoupper($status) }}
                        </span>
                        @if ($hasReject)
                            <span class="badge-status badge-reject">ADA REJECT</span>
                        @else
                            <span class="pill pill-ok">SEMUA OK</span>
                        @endif
                    </div>

                    <div class="header-actions">
                        <a href="{{ route('production.sewing_returns.index') }}"
                            class="btn btn-outline-secondary btn-sm btn-pill">
                            <i class="bi bi-arrow-left"></i><span>Kembali</span>
                        </a>
                        @if ($return->pickup)
                            <a href="{{ route('production.sewing_pickups.show', $return->pickup) }}"
                                class="btn btn-outline-primary btn-sm btn-pill">
                                <i class="bi bi-box-seam"></i><span>Lihat Pickup</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- SUMMARY (dari controller) --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="section-title">Ringkasan Mutasi</div>
                <div class="section-sub mb-2">
                    Mutasi dari <span class="mono">{{ $return->warehouse?->code ?? 'WIP-SEW' }}</span> → WIP-FIN /
                    REJ-SEW
                    (Remaining sudah termasuk direct pickup).
                </div>

                <div class="summary-grid">
                    <div>
                        <div class="summary-label">Total ambil (pickup)</div>
                        <div class="summary-value mono">{{ number_format($totalPickup, 2, ',', '.') }}</div>
                        <div class="section-sub">Qty bundle dari pickup line.</div>
                    </div>
                    <div>
                        <div class="summary-label">Total setor</div>
                        <div class="summary-value mono">{{ number_format($totalProcessed, 2, ',', '.') }}</div>
                        <div class="section-sub">OK + Reject yang diinput.</div>
                    </div>
                    <div>
                        <div class="summary-label">OK</div>
                        <div class="summary-value summary-ok mono">{{ number_format($totalOk, 2, ',', '.') }}</div>
                        <div class="section-sub">{{ number_format($okPercent, 1, ',', '.') }}%</div>
                    </div>
                    <div>
                        <div class="summary-label">Reject</div>
                        <div class="summary-value summary-rj mono">{{ number_format($totalReject, 2, ',', '.') }}</div>
                        <div class="section-sub">{{ number_format($rejectPercent, 1, ',', '.') }}%</div>
                    </div>
                    <div>
                        <div class="summary-label">Sisa belum setor</div>
                        <div class="summary-value summary-warn mono">{{ number_format($totalRemaining, 2, ',', '.') }}
                        </div>
                        <div class="section-sub">bundle - returned - direct picked.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- PER ITEM --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="section-title">Ringkasan per Item</div>
                <div class="section-sub mb-2">Total OK masuk WIP-FIN dan Reject masuk REJ-SEW per item.</div>

                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:140px;">Item</th>
                            <th>Nama</th>
                            <th class="text-end" style="width:120px;">OK</th>
                            <th class="text-end" style="width:120px;">Reject</th>
                            <th class="text-end" style="width:120px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($perItem as $row)
                            @php $total = (float)$row['ok'] + (float)$row['rj']; @endphp
                            <tr
                                class="{{ $row['rj'] > 0 && $row['ok'] == 0 ? 'row-rj' : ($row['ok'] > 0 && $row['rj'] == 0 ? 'row-ok' : '') }}">
                                <td class="mono fw-bold">{{ $row['code'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td class="text-end"><span
                                        class="pill pill-ok mono">{{ number_format($row['ok'], 2, ',', '.') }}</span></td>
                                <td class="text-end">
                                    @if ($row['rj'] > 0)
                                        <span class="pill pill-rj mono">{{ number_format($row['rj'], 2, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted mono">0,00</span>
                                    @endif
                                </td>
                                <td class="text-end"><span
                                        class="pill pill-total mono">{{ number_format($total, 2, ',', '.') }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted small py-3">Tidak ada data item.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- DETAIL --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="section-title">Detail Baris</div>
                <div class="section-sub mb-2">Per bundle yang disetor dari pickup.</div>

                <table class="table table-sm align-middle mono mb-0">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th style="width:150px;">Item</th>
                            <th>Bundle / LOT</th>
                            <th class="text-end" style="width:120px;">OK</th>
                            <th class="text-end" style="width:120px;">Reject</th>
                            <th class="text-end" style="width:120px;">Total</th>
                            <th style="width:220px;">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lines as $i => $line)
                            @php
                                $pickupLine = $line->sewingPickupLine;
                                $bundle = optional($pickupLine)->bundle;
                                $item = optional($bundle)->finishedItem;
                                $lot = optional(optional($bundle)->cuttingJob)->lot;

                                $ok = (float) $line->qty_ok;
                                $rj = (float) $line->qty_reject;
                                $tot = $ok + $rj;
                            @endphp

                            <tr class="{{ $rj > 0 && $ok == 0 ? 'row-rj' : ($ok > 0 && $rj == 0 ? 'row-ok' : '') }}">
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <div class="fw-bold">{{ $item?->code ?? '-' }}</div>
                                    <div class="small text-muted">{{ $item?->name ?? '' }}</div>
                                </td>
                                <td>
                                    <div class="small">
                                        {{ $bundle?->bundle_code ?? '-' }}
                                        @if ($lot)
                                            • LOT <span class="mono">{{ $lot->code }}</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted mt-1">
                                        Pickup qty_bundle:
                                        <span
                                            class="mono">{{ number_format((float) ($pickupLine->qty_bundle ?? 0), 2, ',', '.') }}</span>
                                    </div>
                                </td>
                                <td class="text-end">{{ number_format($ok, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($rj, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($tot, 2, ',', '.') }}</td>
                                <td>{{ $line->notes ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted small py-3">Tidak ada detail.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($return->notes)
                    <hr class="my-2">
                    <div class="small">
                        <span class="text-muted">Catatan header:</span> {{ $return->notes }}
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection
