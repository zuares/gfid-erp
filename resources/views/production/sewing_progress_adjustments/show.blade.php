@extends('layouts.app')

@section('title', 'Sewing Progress Adjustment • ' . ($doc->code ?? '-'))

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .85rem .85rem 4rem;
        }

        @media(min-width:768px) {
            .page-wrap {
                padding: 1.1rem 1rem 4rem;
            }
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .cardx {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .22);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .08), 0 0 0 1px rgba(15, 23, 42, .03);
        }

        .cardx .cardx-body {
            padding: .9rem 1rem;
        }

        @media(min-width:768px) {
            .cardx .cardx-body {
                padding: 1rem 1.25rem;
            }
        }

        .btn-pill {
            border-radius: 999px;
            padding: .25rem .75rem;
            font-size: .8rem;
            display: inline-flex;
            gap: .35rem;
            align-items: center;
        }

        .badge-status {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .12em;
            border-radius: 999px;
            padding: .16rem .7rem;
            font-weight: 800;
        }

        .badge-soft {
            background: rgba(59, 130, 246, .10);
            color: #1d4ed8;
            border: 1px solid rgba(59, 130, 246, .35);
        }

        .table thead th {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            border-top: none;
        }

        .table tbody td {
            font-size: .85rem;
        }

        .row-warn {
            background: rgba(254, 243, 199, .55);
        }
    </style>
@endpush

@section('content')
    @php
        $pickup = $doc->pickup;
        $lines = $doc->lines ?? collect();

        try {
            $dateLabel = $doc->date ? \Illuminate\Support\Carbon::parse($doc->date)->format('d/m/Y') : '-';
        } catch (\Throwable $e) {
            $dateLabel = (string) ($doc->date ?? '-');
        }
    @endphp

    <div class="page-wrap">

        <div class="cardx mb-2">
            <div class="cardx-body d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <div class="fw-bold">
                        Sewing Progress Adjustment <span class="mono">{{ $doc->code ?? '-' }}</span>
                    </div>
                    <div class="small text-muted mt-1">
                        Tanggal: <span class="mono">{{ $dateLabel }}</span>
                        @if ($pickup)
                            • Pickup: <span class="mono">{{ $pickup->code }}</span>
                        @endif
                    </div>
                    <div class="small text-muted mt-1">
                        Operator: {{ $pickup?->operator?->code ?? ($doc->operator?->code ?? '-') }}
                        — {{ $pickup?->operator?->name ?? ($doc->operator?->name ?? '-') }}
                    </div>
                    @if (!empty($doc->notes))
                        <div class="small text-muted mt-1">Catatan: {{ $doc->notes }}</div>
                    @endif
                </div>

                <div class="d-flex flex-column align-items-end gap-2">
                    <span class="badge-status badge-soft">NO STOCK MOVEMENT</span>

                    <div class="d-flex gap-2">
                        <a href="{{ route('production.sewing.adjustments.index') }}"
                            class="btn btn-outline-secondary btn-sm btn-pill">
                            <i class="bi bi-arrow-left"></i><span>Kembali</span>
                        </a>

                        <a href="{{ route('production.sewing.adjustments.create', ['pickup_id' => $pickup?->id]) }}"
                            class="btn btn-primary btn-sm btn-pill">
                            <i class="bi bi-plus-lg"></i><span>Adjustment Baru</span>
                        </a>

                        @if ($pickup)
                            <a href="{{ route('production.sewing.pickups.show', $pickup) }}"
                                class="btn btn-outline-primary btn-sm btn-pill">
                                <i class="bi bi-box-seam"></i><span>Lihat Pickup</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="cardx">
            <div class="cardx-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:44px;">#</th>
                                <th style="width:170px;">Item</th>
                                <th>Bundle</th>
                                <th class="text-end" style="width:140px;">Qty Adjust</th>
                                <th style="width:260px;">Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lines as $i => $ln)
                                @php
                                    $pl = $ln->sewingPickupLine;
                                    $bundle = $pl?->bundle;
                                    $item = $bundle?->finishedItem;
                                    $qty = (float) ($ln->qty_adjust ?? 0);
                                @endphp

                                <tr class="{{ $qty > 0 ? 'row-warn' : '' }}">
                                    <td class="text-muted mono">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="mono fw-bold">{{ $item?->code ?? '-' }}</div>
                                        <div class="small text-muted">{{ $item?->name ?? '' }}</div>
                                    </td>
                                    <td class="mono">
                                        {{ $bundle?->bundle_code ?? '#' . ($pl?->cutting_job_bundle_id ?? '-') }}
                                    </td>
                                    <td class="text-end mono fw-bold">{{ number_format($qty, 2, ',', '.') }}</td>
                                    <td>{{ $ln->reason ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Tidak ada detail adjustment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
