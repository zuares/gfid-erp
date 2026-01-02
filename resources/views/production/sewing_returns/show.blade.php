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
                grid-template-columns: repeat(6, minmax(0, 1fr));
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

        // total direct pickup (unik per pickup line)
        $totalDirectPick = $lines
            ->pluck('sewingPickupLine')
            ->filter()
            ->unique('id')
            ->sum(function ($pl) {
                return (float) ($pl->qty_direct_picked ?? 0);
            });

        /**
         * Ringkasan per item:
         * - SETOR = sum(qty_ok)
         * - Reject = sum(qty_reject)
         * - Dadakan = sum(qty_direct_picked) unik per pickup line
         * - Belum Setor = pickup - (setor + reject + dadakan) per pickup line, lalu dijumlah
         */
        $perItem = $lines
            ->groupBy(function ($l) {
                return (int) ($l->item_id ?? (optional(optional($l->sewingPickupLine)->bundle)->finished_item_id ?? 0));
            })
            ->map(function ($group) {
                $first = $group->first();
                $bundle = optional($first->sewingPickupLine)->bundle;
                $item = optional($bundle)->finishedItem;

                $setor = (float) $group->sum('qty_ok'); // ✅ SETOR (jahit OK)
                $rj = (float) $group->sum('qty_reject');

                $pickupLines = $group->pluck('sewingPickupLine')->filter()->unique('id');

                $directPick = $pickupLines->sum(function ($pl) {
                    return (float) ($pl->qty_direct_picked ?? 0);
                });

                $remaining = $pickupLines->sum(function ($pl) {
                    $qtyBundle = (float) ($pl->qty_bundle ?? 0);
                    $returnedOk = (float) ($pl->qty_returned_ok ?? 0);
                    $returnedRej = (float) ($pl->qty_returned_reject ?? 0);
                    $directPick = (float) ($pl->qty_direct_picked ?? 0);

                    return max($qtyBundle - ($returnedOk + $returnedRej + $directPick), 0);
                });

                return [
                    'code' => $item?->code ?? '-',
                    'name' => $item?->name ?? '',
                    'setor' => $setor,
                    'rj' => $rj,
                    'direct_pick' => $directPick,
                    'remaining' => $remaining,
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
                        <span class="mono">{{ $dateLabel }}</span>
                        @if ($return->warehouse)
                            • Gudang: <span class="mono">{{ $return->warehouse->code }}</span>
                        @endif
                    </div>

                    <div class="small text-muted mt-1">
                        Pickup: <span class="mono">{{ $return->pickup?->code ?? '-' }}</span> • {{ $pickupDate }}
                    </div>

                    <div class="small text-muted mt-1">
                        Operator: {{ $return->pickup?->operator?->code ?? '-' }}
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
                            <span class="pill pill-ok">SEMUA AMAN</span>
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

        {{-- SUMMARY --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="section-title mb-2">Ringkasan</div>

                <div class="summary-grid">
                    <div>
                        <div class="summary-label">Pickup</div>
                        <div class="summary-value mono">{{ number_format($totalPickup, 2, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="summary-label">Diproses (Setor + Reject)</div>
                        <div class="summary-value mono">{{ number_format($totalProcessed, 2, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="summary-label">Setor</div>
                        <div class="summary-value summary-ok mono">{{ number_format($totalOk, 2, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="summary-label">Reject</div>
                        <div class="summary-value summary-rj mono">{{ number_format($totalReject, 2, ',', '.') }}</div>
                        <div class="section-sub">{{ number_format($rejectPercent, 1, ',', '.') }}%</div>
                    </div>
                    <div>
                        <div class="summary-label">Dadakan</div>
                        <div class="summary-value summary-warn mono">{{ number_format($totalDirectPick, 2, ',', '.') }}
                        </div>
                    </div>
                    <div>
                        <div class="summary-label">Belum Setor</div>
                        <div class="summary-value summary-warn mono">{{ number_format($totalRemaining, 2, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- PER ITEM --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="section-title mb-2">Ringkasan per Item</div>

                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:44px;">#</th>
                            <th style="width:140px;">Item</th>
                            <th>Nama</th>
                            <th class="text-end" style="width:120px;">Setor</th>
                            <th class="text-end" style="width:120px;">Reject</th>
                            <th class="text-end" style="width:130px;">Dadakan</th>
                            <th class="text-end" style="width:130px;">Belum Setor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($perItem as $idx => $row)
                            <tr
                                class="{{ $row['rj'] > 0 && $row['setor'] == 0 ? 'row-rj' : ($row['setor'] > 0 && $row['rj'] == 0 ? 'row-ok' : '') }}">
                                <td class="text-muted mono">{{ $idx + 1 }}</td>

                                <td class="mono fw-bold">{{ $row['code'] }}</td>
                                <td>{{ $row['name'] }}</td>

                                <td class="text-end">
                                    <span class="pill pill-ok mono">{{ number_format($row['setor'], 2, ',', '.') }}</span>
                                </td>

                                <td class="text-end">
                                    @if ($row['rj'] > 0)
                                        <span class="pill pill-rj mono">{{ number_format($row['rj'], 2, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted mono">0,00</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <span
                                        class="pill pill-total mono">{{ number_format($row['direct_pick'], 2, ',', '.') }}</span>
                                </td>

                                <td class="text-end">
                                    <span
                                        class="pill pill-total mono">{{ number_format($row['remaining'], 2, ',', '.') }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted small py-3">Tidak ada data item.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- DETAIL --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="section-title mb-2">Detail Bundle</div>

                <table class="table table-sm align-middle mono mb-0">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th style="width:150px;">Item</th>
                            <th>Bundle / LOT</th>
                            <th class="text-end" style="width:110px;">Setor</th>
                            <th class="text-end" style="width:110px;">Reject</th>
                            <th class="text-end" style="width:110px;">Dadakan</th>
                            <th class="text-end" style="width:110px;">Belum Setor</th>
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

                                $setor = (float) $line->qty_ok; // ✅ SETOR (jahit OK)
                                $rj = (float) $line->qty_reject;

                                $qtyBundle = (float) ($pickupLine->qty_bundle ?? 0);
                                $returnedOkAll = (float) ($pickupLine->qty_returned_ok ?? 0);
                                $returnedRejAll = (float) ($pickupLine->qty_returned_reject ?? 0);
                                $directPickAll = (float) ($pickupLine->qty_direct_picked ?? 0);

                                $directPick = $directPickAll;
                                $remainingRow = max(
                                    $qtyBundle - ($returnedOkAll + $returnedRejAll + $directPickAll),
                                    0,
                                );
                            @endphp

                            <tr class="{{ $rj > 0 && $setor == 0 ? 'row-rj' : ($setor > 0 && $rj == 0 ? 'row-ok' : '') }}">
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
                                        Pickup: <span class="mono">{{ number_format($qtyBundle, 2, ',', '.') }}</span>
                                    </div>
                                </td>

                                <td class="text-end">{{ number_format($setor, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($rj, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($directPick, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($remainingRow, 2, ',', '.') }}</td>

                                <td>{{ $line->notes ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted small py-3">Tidak ada detail.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($return->notes)
                    <hr class="my-2">
                    <div class="small">
                        <span class="text-muted">Catatan:</span> {{ $return->notes }}
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection
