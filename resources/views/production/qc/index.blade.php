{{-- resources/views/production/qc/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • QC Overview')

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

        .nav-qc .nav-link {
            border-radius: 999px;
            padding-inline: 1rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .17rem .5rem;
            font-size: .7rem;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h5 mb-1">QC Overview</h1>
                    <div class="help">
                        Monitoring QC untuk Cutting, Sewing, dan Packing.
                    </div>
                </div>
            </div>

            {{-- TAB STAGE --}}
            <ul class="nav nav-pills nav-qc mt-3">
                <li class="nav-item">
                    <a class="nav-link {{ $stage === 'cutting' ? 'active' : '' }}"
                        href="{{ route('production.qc.index', ['stage' => 'cutting']) }}">
                        QC Cutting
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $stage === 'sewing' ? 'active' : '' }}"
                        href="{{ route('production.qc.index', ['stage' => 'sewing']) }}">
                        QC Sewing
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $stage === 'packing' ? 'active' : '' }}"
                        href="{{ route('production.qc.index', ['stage' => 'packing']) }}">
                        QC Packing
                    </a>
                </li>
            </ul>
        </div>

        {{-- ISI TABEL PER STAGE --}}
        <div class="card p-3">

            {{-- =======================
                 TAB QC CUTTING
            ======================== --}}
            @if ($stage === 'cutting')
                <h2 class="h6 mb-2">Daftar QC Cutting</h2>

                <div class="table-wrap">
                    <table class="table table-sm align-middle mono">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 110px;">Tanggal</th>
                                <th style="width: 200px;">Lot</th>
                                <th style="width: 160px;">Bundles (Qty)</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 90px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($records as $job)
                                @php
                                    $totalBundles = $job->bundles->count();
                                    $totalQty = $job->bundles->sum('qty_pcs');

                                    $rawStatus = $job->status ?? '-';

                                    // mapping status CuttingJob versi terbaru
                                    $map = [
                                        'draft' => ['DRAFT', 'secondary'],
                                        'cut' => ['CUT', 'primary'],
                                        'sent_to_qc' => ['BELUM QC', 'warning'],
                                        'qc_done' => ['QC DONE', 'success'],
                                        'qc_ok' => ['QC OK', 'success'],
                                        'qc_mixed' => ['QC MIXED', 'warning'],
                                        'qc_reject' => ['QC REJECT', 'danger'],
                                    ];

                                    $cfg = $map[$rawStatus] ?? [strtoupper($rawStatus), 'secondary'];
                                    [$statusLabel, $statusClass] = $cfg;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration + ($records->currentPage() - 1) * $records->perPage() }}</td>
                                    <td>{{ $job->date?->format('Y-m-d') ?? $job->date }}</td>
                                    <td>
                                        {{-- Nama item Lot + badge kecil kode LOT --}}
                                        {{ $job->lot?->item?->code ?? '-' }}
                                        @if ($job->lot)
                                            <span class="badge-soft bg-light border text-muted">
                                                {{ $job->lot->code }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $totalBundles }} bundle /
                                        {{ number_format($totalQty, 2, ',', '.') }} pcs
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('production.qc.cutting.edit', $job) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            QC
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted small">
                                        Belum ada data QC Cutting.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($records instanceof \Illuminate\Pagination\AbstractPaginator)
                    <div class="mt-2">
                        {{ $records->links() }}
                    </div>
                @endif

                {{-- =======================
                 TAB QC SEWING
            ======================== --}}
            @elseif ($stage === 'sewing')
                <h2 class="h6 mb-2">Daftar QC Sewing</h2>

                <div class="table-wrap">
                    <table class="table table-sm align-middle mono">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 130px;">Return Code</th>
                                <th style="width: 110px;">Tanggal</th>
                                <th style="width: 140px;">Pickup Code</th>
                                <th style="width: 160px;">Gudang Sewing</th>
                                <th style="width: 170px;">Operator Jahit</th>
                                <th style="width: 130px;">Bundles</th>
                                <th style="width: 130px;">Qty OK</th>
                                <th style="width: 130px;">Qty Reject</th>
                                <th style="width: 110px;">Status</th>
                                <th style="width: 90px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($records as $ret)
                                @php
                                    $lines = $ret->lines;
                                    $totalBundles = $lines->count();
                                    $qtyOk = $lines->sum('qty_ok');
                                    $qtyReject = $lines->sum('qty_reject');

                                    $firstLine = $lines->first();
                                    $pickupLine = $firstLine?->pickupLine;
                                    $pickup = $pickupLine?->pickup;
                                    $warehouse = $pickup?->warehouse;

                                    $statusMap = [
                                        'draft' => ['DRAFT', 'secondary'],
                                        'posted' => ['POSTED', 'primary'],
                                        'closed' => ['CLOSED', 'success'],
                                    ];
                                    $cfg = $statusMap[$ret->status] ?? [strtoupper($ret->status ?? '-'), 'secondary'];
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration + ($records->currentPage() - 1) * $records->perPage() }}</td>
                                    <td>{{ $ret->code }}</td>
                                    <td>{{ $ret->date?->format('Y-m-d') ?? $ret->date }}</td>
                                    <td>{{ $pickup?->code ?? '-' }}</td>
                                    <td>
                                        {{ $warehouse?->code ?? '-' }}
                                        @if ($warehouse)
                                            <span class="badge-soft bg-light border text-muted">
                                                {{ $warehouse->name }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($ret->operator)
                                            {{ $ret->operator->code }} — {{ $ret->operator->name }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $totalBundles }} bundle</td>
                                    <td>{{ number_format($qtyOk, 2, ',', '.') }}</td>
                                    <td>{{ number_format($qtyReject, 2, ',', '.') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $cfg[1] }}">
                                            {{ $cfg[0] }}
                                        </span>
                                    </td>
                                    <td>
                                        @if (Route::has('production.sewing_returns.show'))
                                            <a href="{{ route('production.sewing_returns.show', $ret) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                Detail
                                            </a>
                                        @elseif ($pickup && Route::has('production.sewing_pickups.show'))
                                            <a href="{{ route('production.sewing_pickups.show', $pickup) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                Pickup
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted small">
                                        Belum ada data QC Sewing.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($records instanceof \Illuminate\Pagination\AbstractPaginator)
                    <div class="mt-2">
                        {{ $records->links() }}
                    </div>
                @endif

                {{-- =======================
                 TAB QC PACKING
            ======================== --}}
            @elseif ($stage === 'packing')
                <h2 class="h6 mb-2">Daftar QC Packing</h2>
                <p class="small text-muted mb-0">
                    Modul QC Packing belum diimplementasikan. Nanti bisa mengikuti pola QC Sewing dengan model
                    <code>PackingReturn</code> atau sejenisnya.
                </p>
            @endif
        </div>

    </div>
@endsection
