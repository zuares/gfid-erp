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
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
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
        // Ambil operator dari bundle pertama
        $firstBundle = $job->bundles->first();
        $bundleOperator = $firstBundle?->operator;

        // Deteksi apakah sudah ada QC Cutting
        $hasQcCutting = isset($hasQcCutting)
            ? $hasQcCutting
            : $job->bundles->contains(function ($b) {
                return $b->qcResults->where('stage', 'cutting')->isNotEmpty();
            });

        // Tentukan status badge
        if ($hasQcCutting) {
            $statusLabel = 'QC CUTTING';
            $statusClass = 'info';
        } else {
            $statusLabel = strtoupper($job->status ?? 'draft');
            $statusClass =
                [
                    'draft' => 'secondary',
                    'cut' => 'primary',
                    'posted' => 'primary',
                    'qc_ok' => 'success',
                    'qc_mixed' => 'warning',
                    'qc_reject' => 'danger',
                ][$job->status] ?? 'secondary';
        }

        $totalBundles = $job->bundles->count();
        $totalQtyPcs = $job->bundles->sum('qty_pcs');
        $totalUsedFabric = $job->bundles->sum('qty_used_fabric');
    @endphp

    <div class="page-wrap">

        {{-- HEADER ATAS --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h1 class="h5 mb-1">Cutting Job: {{ $job->code }}</h1>
                    <div class="help">
                        Tanggal: {{ $job->date?->format('Y-m-d') ?? $job->date }} •
                        Lot: {{ $job->lot?->code ?? '-' }} •
                        Gudang: {{ $job->warehouse?->code ?? '-' }}
                    </div>
                </div>

                <div class="d-flex flex-column align-items-end gap-2">
                    <span class="badge bg-{{ $statusClass }} px-3 py-2">
                        {{ $statusLabel }}
                    </span>

                    <div class="d-flex gap-2">
                        <a href="{{ route('production.cutting_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                            Kembali
                        </a>

                        {{-- Tombol Edit hanya kalau BELUM pernah QC Cutting --}}
                        @if (!$hasQcCutting)
                            <a href="{{ route('production.cutting_jobs.edit', $job) }}"
                                class="btn btn-sm btn-outline-primary">
                                Edit Cutting
                            </a>

                            <a href="{{ route('production.qc.cutting.edit', $job) }}" class="btn btn-sm btn-primary">
                                Kirim ke QC Cutting
                            </a>
                        @else
                            <a href="{{ route('production.qc.cutting.edit', $job) }}" class="btn btn-sm btn-primary">
                                Lihat QC Cutting
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- INFORMASI LOT & OPERATOR --}}
        <div class="card p-3 mb-3">
            <h2 class="h6 mb-2">Informasi Lot & Operator</h2>

            <div class="row g-3">
                <div class="col-md-4 col-12">
                    <div class="help mb-1">LOT</div>
                    <div class="fw-semibold">
                        {{ $job->lot?->code ?? '-' }}
                    </div>
                    <div class="small text-muted">
                        {{ $job->lot?->item?->code ?? '-' }}
                    </div>
                </div>

                <div class="col-md-4 col-6">
                    <div class="help mb-1">Gudang</div>
                    <div class="mono">
                        {{ $job->warehouse?->code }} — {{ $job->warehouse?->name }}
                    </div>
                </div>

                <div class="col-md-4 col-6">
                    <div class="help mb-1">Operator Cutting (dari bundle)</div>
                    <div class="mono">
                        {{ $bundleOperator?->code ? $bundleOperator->code . ' — ' . $bundleOperator->name : '-' }}
                    </div>
                </div>
            </div>

            @if ($job->notes)
                <div class="mt-2 text-muted small">
                    Catatan: {{ $job->notes }}
                </div>
            @endif
        </div>

        {{-- SUMMARY OUTPUT --}}
        <div class="card p-3 mb-3">
            <h2 class="h6 mb-2">Ringkasan Output</h2>

            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="help mb-1">Jumlah Bundle</div>
                    <div class="mono">{{ $totalBundles }}</div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Total Qty (pcs)</div>
                    <div class="mono">
                        {{ number_format($totalQtyPcs, 2, ',', '.') }}
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Total Pemakaian Kain</div>
                    <div class="mono">
                        {{ number_format($totalUsedFabric, 2, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- TABEL BUNDLES --}}
        <div class="card p-3 mb-4">
            <h2 class="h6 mb-2">Detail Bundles</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th style="width:160px;">Bundle Code</th>
                            <th style="width:160px;">Item Jadi</th>
                            <th style="width:110px;">Qty (pcs)</th>
                            <th style="width:140px;">Qty Used Fabric</th>
                            <th style="width:140px;">Operator</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($job->bundles as $row)
                            <tr>
                                <td>{{ $row->bundle_no }}</td>
                                <td>{{ $row->bundle_code }}</td>
                                <td>{{ $row->finishedItem?->code ?? '-' }}</td>
                                <td>{{ number_format($row->qty_pcs, 2, ',', '.') }}</td>
                                <td>{{ number_format($row->qty_used_fabric ?? 0, 2, ',', '.') }}</td>
                                <td>
                                    {{ $row->operator?->code ? $row->operator->code . ' — ' . $row->operator->name : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted small">
                                    Belum ada data bundle.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
