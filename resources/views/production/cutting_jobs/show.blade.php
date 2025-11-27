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
        // Ambil operator cutting dari bundle pertama
        $firstBundle = $job->bundles->first();
        $bundleOperator = $firstBundle?->operator;

        // Pakai flag dari controller, kalau tidak ada hitung sendiri
        $hasQcCutting = isset($hasQcCutting)
            ? $hasQcCutting
            : $job->bundles->contains(function ($b) {
                return $b->qcResults->where('stage', 'cutting')->isNotEmpty();
            });

        // Cari satu operator QC (ambil dari qc_results pertama yang ada)
        $qcOperator = null;
        if ($hasQcCutting) {
            foreach ($job->bundles as $b) {
                $qc = $b->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first();

                if ($qc && $qc->operator) {
                    $qcOperator = $qc->operator;
                    break;
                }
            }
        }

        // Ringkasan cutting
        $totalBundles = $job->bundles->count();
        $totalQtyPcs = $job->bundles->sum('qty_pcs');
        $totalUsedFabric = $job->bundles->sum('qty_used_fabric');

        // Ringkasan QC (kalau sudah ada QC)
        $qcTotalOk = 0;
        $qcTotalReject = 0;

        if ($hasQcCutting) {
            foreach ($job->bundles as $b) {
                $qc = $b->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first();

                if ($qc) {
                    $qcTotalOk += $qc->qty_ok ?? 0;
                    $qcTotalReject += $qc->qty_reject ?? 0;
                }
            }
        }

        // Tentukan status badge
        if ($hasQcCutting) {
            $statusMap = [
                'qc_done' => ['label' => 'QC CUTTING SELESAI', 'class' => 'info'],
                'sent_to_qc' => ['label' => 'SEDANG DI QC', 'class' => 'success'],
                'qc_mixed' => ['label' => 'QC MIXED', 'class' => 'warning'],
                'qc_reject' => ['label' => 'QC REJECT', 'class' => 'danger'],
            ];

            $cfg = $statusMap[$job->status] ?? ['label' => 'QC CUTTING', 'class' => 'info'];

            $statusLabel = $cfg['label'];
            $statusClass = $cfg['class'];
        } else {
            $statusLabel = strtoupper($job->status ?? 'draft');
            $statusClass =
                [
                    'draft' => 'secondary',
                    'cut' => 'primary',
                    'cut_sent_to_qc' => 'info',
                    'posted' => 'primary',
                ][$job->status] ?? 'secondary';
        }
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

                        @if ($job->status === 'sent_to_qc')
                            {{-- STATUS: SEDANG MENUNGGU QC --}}
                            <button class="btn btn-sm btn-warning" disabled>
                                Menunggu hasil QC…
                            </button>
                        @elseif (!$hasQcCutting)
                            {{-- BELUM QC --}}
                            <a href="{{ route('production.cutting_jobs.edit', $job) }}"
                                class="btn btn-sm btn-outline-primary">
                                Edit Cutting
                            </a>

                            {{-- Kirim ke QC pakai POST ke send_to_qc --}}
                            <form action="{{ route('production.cutting_jobs.send_to_qc', $job) }}" method="post"
                                class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">
                                    Kirim ke QC Cutting
                                </button>
                            </form>
                        @else
                            {{-- SUDAH ADA QC --}}
                            <a href="{{ route('production.cutting_jobs.show', $job) }}" class="btn btn-sm btn-primary">
                                Hasil QC Cutting
                            </a>
                        @endif
                    </div>

                </div>
            </div>
        </div>

        {{-- INFORMASI LOT & OPERATOR (Cutting & QC) --}}
        <div class="card p-3 mb-3">
            <h2 class="h6 mb-2">Informasi Lot & Operator</h2>

            <div class="row g-3">
                <div class="col-md-3 col-12">
                    <div class="help mb-1">LOT</div>
                    <div class="fw-semibold">
                        {{ $job->lot?->code ?? '-' }}
                    </div>
                    <div class="small text-muted">
                        {{ $job->lot?->item?->code ?? '-' }}
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Gudang</div>
                    <div class="mono">
                        {{ $job->warehouse?->code }} — {{ $job->warehouse?->name }}
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Operator Cutting</div>
                    <div class="mono">
                        {{ $bundleOperator?->code ? $bundleOperator->code . ' — ' . $bundleOperator->name : '-' }}
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Operator QC Cutting</div>
                    <div class="mono">
                        {{ $qcOperator?->code ? $qcOperator->code . ' — ' . $qcOperator->name : '-' }}
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
                    <div class="help mb-1">Total Qty Cutting (pcs)</div>
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

                @if ($hasQcCutting)
                    <div class="col-md-3 col-6">
                        <div class="help mb-1">Total QC (OK / Reject)</div>
                        <div class="mono">
                            OK: {{ number_format($qcTotalOk, 2, ',', '.') }} /
                            Reject: {{ number_format($qcTotalReject, 2, ',', '.') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- TABEL BUNDLES --}}
        <div class="card p-3 mb-4">
            <h2 class="h6 mb-2">Detail Bundles</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        @if ($hasQcCutting)
                            <tr>
                                <th style="width:60px;">#</th>
                                <th style="width:160px;">Bundle Code</th>
                                <th style="width:160px;">Item Jadi</th>
                                <th style="width:110px;">Cutting (Qty)</th>
                                <th style="width:110px;">Cutting (Reject)</th>
                                <th style="width:110px;">Cutting (Ok)</th>
                            </tr>
                        @else
                            <tr>
                                <th style="width:60px;">#</th>
                                <th style="width:160px;">Bundle Code</th>
                                <th style="width:160px;">Item Jadi</th>
                                <th style="width:110px;">Qty (pcs)</th>
                                <th style="width:140px;">Qty Used Fabric</th>
                                <th style="width:140px;">Operator Cutting</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @forelse ($job->bundles as $row)
                            @php
                                $qc = null;
                                if ($hasQcCutting) {
                                    $qc = $row->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first();
                                }
                            @endphp

                            @if ($hasQcCutting)
                                {{-- MODE SUDAH QC --}}

                                <tr>
                                    <td>{{ $row->bundle_no }}</td>
                                    <td>{{ $row->bundle_code }}</td>
                                    <td>{{ $row->finishedItem?->code ?? '-' }}</td>
                                    <td>{{ number_format($row->qty_pcs, 2, ',', '.') }}</td>
                                    <td>{{ $qc ? number_format($qc->qty_reject ?? 0, 2, ',', '.') : '0,00' }}</td>
                                    <td>{{ $qc ? number_format($qc->qty__ok ?? 0, 2, ',', '.') : '0,00' }}</td>
                                </tr>
                            @else
                                {{-- MODE BELUM QC --}}
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
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ $hasQcCutting ? 6 : 6 }}" class="text-center text-muted small">
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
