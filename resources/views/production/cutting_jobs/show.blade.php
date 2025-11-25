@extends('layouts.app')

@section('title', 'Produksi • Cutting Job ' . $job->code)

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
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .2rem .6rem;
            font-size: .75rem;
        }

        @media (max-width: 767.98px) {
            .table-wrap {
                overflow-x: auto;
            }
        }
    </style>
@endpush

@section('content')
    @php
        // ambil operator dari bundle pertama (kalau ada)
        $firstBundle = $job->bundles->first();
        $bundleOperator = $firstBundle?->operator;
        $statusClass =
            [
                'draft' => 'secondary',
                'cut' => 'primary',
                'qc_ok' => 'success',
                'qc_mixed' => 'warning',
                'qc_reject' => 'danger',
            ][$job->status] ?? 'secondary';

        $totalBundles = $job->total_bundles ?: $job->bundles->count();
        $totalQtyPcs = $job->total_qty_pcs ?: $job->bundles->sum('qty_pcs');
        $totalUsedFab = $job->bundles->sum('qty_used_fabric');
    @endphp

    <div class="page-wrap">

        {{-- HEADER ATAS --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center gap-3">
                <div>
                    <h1 class="h5 mb-1">Cutting Job: {{ $job->code }}</h1>
                    <div class="help">
                        Tanggal: {{ $job->date?->format('Y-m-d') }} •
                        Lot: {{ $job->lot?->code ?? '-' }} •
                        Gudang: {{ $job->warehouse?->code ?? '-' }} •
                        Operator: {{ $bundleOperator?->code ?? '-' }}
                        @if ($bundleOperator?->name)
                            — {{ $bundleOperator->name }}
                        @endif
                    </div>

                    <div class="help mt-1">
                        Total bundles: <span class="mono">{{ $totalBundles }}</span> •
                        Total qty (pcs): <span class="mono">{{ number_format($totalQtyPcs, 2, ',', '.') }}</span> •
                        Total kain pakai: <span class="mono">{{ number_format($totalUsedFab, 2, ',', '.') }}</span>
                    </div>
                </div>

                <div class="d-flex flex-column align-items-end gap-2">
                    <span class="badge bg-{{ $statusClass }} px-3 py-2">
                        {{ strtoupper($job->status) }}
                    </span>

                    <div class="d-flex gap-2">
                        <a href="{{ route('production.cutting_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                            Kembali
                        </a>

                        <a href="{{ route('production.cutting_jobs.edit', $job) }}" class="btn btn-sm btn-outline-primary">
                            Edit Cutting
                        </a>

                        {{-- Kirim ke QC Cutting --}}
                        <a href="{{ route('production.qc.cutting.edit', $job) }}" class="btn btn-sm btn-primary">
                            Kirim ke QC Cutting
                        </a>
                        {{--
                            NOTE:
                            Kalau nama route QC beda, sesuaikan:
                            misal: route('production.qc.cutting.edit', $job)
                                  atau route('production.qc.cutting.show', $job)
                        --}}
                    </div>
                </div>
            </div>
        </div>

        {{-- INFORMASI LOT --}}
        <div class="card p-3 mb-3">
            <h2 class="h6 mb-2">Informasi Lot Kain</h2>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="help mb-1">LOT</div>
                    <div class="fw-semibold">{{ $job->lot?->code ?? '-' }}</div>
                    <div class="small text-muted">
                        {{ $job->lot?->item?->code ?? ($job->lot?->item?->sku ?? '') }}
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="help mb-1">Gudang</div>
                    <div class="mono">
                        {{ $job->warehouse?->code ?? '-' }}
                        @if ($job->warehouse?->name)
                            — {{ $job->warehouse->name }}
                        @endif
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="help mb-1">Operator Cutting</div>
                    <div class="mono">
                        {{ $bundleOperator?->code ?? '-' }}
                        @if ($bundleOperator?->name)
                            — {{ $bundleOperator->name }}
                        @endif
                    </div>
                </div>
            </div>

            @if ($job->notes)
                <div class="mt-2 text-muted small">
                    Catatan: {{ $job->notes }}
                </div>
            @endif
        </div>

        {{-- BUNDLES --}}
        <div class="card p-3 mb-4">
            <h2 class="h6 mb-2">Output Bundles</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th style="width:140px;">Bundle</th>
                            <th style="width:160px;">Item Jadi</th>
                            <th class="text-end" style="width:110px;">Qty (pcs)</th>
                            <th class="text-end" style="width:130px;">Qty Kain</th>
                            <th style="width:150px;">Operator</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($job->bundles as $row)
                            <tr>
                                <td>{{ $row->bundle_no }}</td>
                                <td>{{ $row->bundle_code }}</td>
                                <td>{{ $row->finishedItem?->code ?? '-' }}</td>
                                <td class="text-end">
                                    {{ number_format($row->qty_pcs, 2, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($row->qty_used_fabric, 2, ',', '.') }}
                                </td>
                                <td>
                                    @php $op = $row->operator ?: $bundleOperator; @endphp
                                    {{ $op?->code ?? '-' }}
                                    @if ($op?->name)
                                        — {{ $op->name }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Belum ada bundle untuk job ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </div>
@endsection
