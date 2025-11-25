@extends('layouts.app')

@section('title', 'Produksi • Sewing Return ' . $return->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .5rem;
            font-size: .7rem;
        }

        .table-wrap {
            overflow-x: auto;
        }
    </style>
@endpush

@section('content')
    @php
        $totalLines = $return->lines->count();
        $totalOk = $return->lines->sum('qty_ok');
        $totalReject = $return->lines->sum('qty_reject');

        $statusMap = [
            'draft' => ['label' => 'DRAFT', 'class' => 'secondary'],
            'posted' => ['label' => 'POSTED', 'class' => 'primary'],
            'closed' => ['label' => 'CLOSED', 'class' => 'success'],
        ];

        $cfg = $statusMap[$return->status] ?? [
            'label' => strtoupper($return->status ?? '-'),
            'class' => 'secondary',
        ];
    @endphp

    <div class="page-wrap">

        {{-- HEADER ATAS --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h1 class="h5 mb-1">Sewing Return: {{ $return->code }}</h1>

                    <div class="help">
                        Tanggal: {{ $return->date?->format('Y-m-d') ?? $return->date }} •
                        Gudang Sewing: {{ $return->warehouse?->code ?? '-' }} —
                        {{ $return->warehouse?->name ?? '-' }}
                    </div>

                    <div class="help mt-1">
                        Operator Jahit:
                        @if ($return->operator)
                            <span class="mono">
                                {{ $return->operator->code }} — {{ $return->operator->name }}
                            </span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </div>

                    @if ($pickup)
                        <div class="help mt-1">
                            Pickup Terkait:
                            <a href="{{ route('production.sewing_pickups.show', $pickup) }}" class="link-primary mono">
                                {{ $pickup->code }}
                            </a>
                        </div>
                    @endif
                </div>

                <div class="d-flex flex-column align-items-end gap-2">
                    <span class="badge bg-{{ $cfg['class'] }} px-3 py-2">
                        {{ $cfg['label'] }}
                    </span>

                    <div class="d-flex gap-2">
                        <a href="{{ route('production.sewing_pickups.index') }}" class="btn btn-sm btn-outline-secondary">
                            Kembali ke Sewing Pickup
                        </a>
                    </div>
                </div>
            </div>

            @if ($return->notes)
                <div class="mt-2 small text-muted">
                    Catatan: {{ $return->notes }}
                </div>
            @endif
        </div>

        {{-- RINGKASAN --}}
        <div class="card p-3 mb-3">
            <h2 class="h6 mb-2">Ringkasan Hasil Jahit</h2>

            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="help mb-1">Jumlah Baris</div>
                    <div class="mono">
                        {{ $totalLines }}
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Total OK (pcs)</div>
                    <div class="mono">
                        {{ number_format($totalOk, 2, ',', '.') }}
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Total Reject (pcs)</div>
                    <div class="mono text-danger">
                        {{ number_format($totalReject, 2, ',', '.') }}
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Gudang Tujuan</div>
                    <div class="small">
                        <span class="mono">WIP-FIN</span>
                        @if ($totalReject > 0)
                            <span class="mx-1">/</span>
                            <span class="mono">REJECT</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- DETAIL BUNDLE --}}
        <div class="card p-3 mb-4">
            <h2 class="h6 mb-2">Detail Bundles</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 150px;">Pickup Code</th>
                            <th style="width: 150px;">Bundle Code</th>
                            <th style="width: 160px;">Item Jadi</th>
                            <th style="width: 180px;">Lot</th>
                            <th style="width: 120px;">Qty Pickup</th>
                            <th style="width: 120px;">Qty OK</th>
                            <th style="width: 120px;">Qty Reject</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($return->lines as $line)
                            @php
                                $pickupLine = $line->sewingPickupLine;
                                $pickupRow = $pickupLine?->sewingPickup;
                                $bundle = $pickupLine?->bundle;
                                $lot = $bundle?->cuttingJob?->lot;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    @if ($pickupRow)
                                        <a href="{{ route('production.sewing_pickups.show', $pickupRow) }}"
                                            class="link-primary">
                                            {{ $pickupRow->code }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $bundle?->bundle_code ?? '-' }}</td>
                                <td>{{ $bundle?->finishedItem?->code ?? '-' }}</td>
                                <td>
                                    @if ($lot)
                                        {{ $lot->item?->code ?? '-' }}
                                        <span class="badge-soft bg-light border text-muted">
                                            {{ $lot->code }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ number_format($pickupLine->qty_bundle ?? 0, 2, ',', '.') }}</td>
                                <td>{{ number_format($line->qty_ok ?? 0, 2, ',', '.') }}</td>
                                <td class="text-danger">{{ number_format($line->qty_reject ?? 0, 2, ',', '.') }}</td>
                                <td class="small">
                                    {{ $line->notes ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted small">
                                    Belum ada detail Sewing Return.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
