@extends('layouts.app')

@section('title', 'Produksi • Cutting Jobs')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding-bottom: 3rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.12) 0,
                    rgba(45, 212, 191, 0.08) 28%,
                    #f9fafb 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow:
                0 12px 30px rgba(15, 23, 42, 0.12),
                0 0 0 1px rgba(148, 163, 184, 0.08);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .22rem .7rem;
            font-size: .75rem;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: rgba(15, 23, 42, 0.02);
        }

        .badge-label {
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .68rem;
            color: #9ca3af;
        }

        .badge-value {
            font-weight: 600;
            font-size: .9rem;
        }

        .status-pill {
            border-radius: 999px;
            padding: .15rem .6rem;
            font-size: .72rem;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .status-draft {
            background: rgba(148, 163, 184, 0.15);
            color: #4b5563;
        }

        .status-cut {
            background: rgba(37, 99, 235, 0.12);
            color: #1d4ed8;
        }

        .status-qc-ok {
            background: rgba(22, 163, 74, 0.12);
            color: #15803d;
        }

        .status-qc-mixed {
            background: rgba(245, 158, 11, 0.12);
            color: #92400e;
        }

        .status-qc-reject {
            background: rgba(239, 68, 68, 0.12);
            color: #b91c1c;
        }

        .status-sent-to-qc {
            background: rgba(8, 145, 178, 0.12);
            color: #0f766e;
        }

        .table-jobs thead th {
            border-bottom-width: 1px;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7280;
            background: rgba(15, 23, 42, 0.02);
        }

        .table-jobs tbody td {
            vertical-align: middle;
            border-top-color: rgba(148, 163, 184, 0.18);
        }

        .code-link a {
            text-decoration: none;
        }

        .code-link a:hover {
            text-decoration: underline;
        }

        .btn-primary {
            border-radius: 999px;
            padding-inline: 1rem;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .header-stack {
                flex-direction: column;
                align-items: stretch !important;
                gap: .75rem;
            }

            .header-stack>div:first-child {
                order: 1;
            }

            .header-stack>div:last-child {
                order: 2;
                display: flex;
                justify-content: flex-start;
            }

            .header-kpis {
                margin-top: .4rem;
            }

            .table-wrap {
                overflow-x: auto;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        @php
            $pageTotal = $jobs->count();
            $pageDraft = $jobs->where('status', 'draft')->count();
            $pageCut = $jobs->where('status', 'cut')->count();
            $pageQcDone = $jobs->whereIn('status', ['qc_ok', 'qc_mixed', 'qc_reject', 'qc_done'])->count();
        @endphp

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3 header-stack">
            <div>
                <h1 class="h4 mb-1">Cutting Jobs</h1>
                <p class="text-muted small mb-0">
                    Daftar job cutting per lot sebelum dikirim ke proses QC & Sewing.
                </p>

                {{-- KPI kecil --}}
                <div class="d-flex flex-wrap gap-2 mt-2 header-kpis">
                    <span class="badge-soft">
                        <span class="badge-label">Job di halaman ini</span>
                        <span class="badge-value ms-1">{{ $pageTotal }}</span>
                    </span>
                    <span class="badge-soft">
                        <span class="badge-label">Belum QC</span>
                        <span class="badge-value ms-1">{{ $pageCut + $pageDraft }}</span>
                    </span>
                    <span class="badge-soft">
                        <span class="badge-label">Sudah QC</span>
                        <span class="badge-value ms-1">{{ $pageQcDone }}</span>
                    </span>
                </div>
            </div>

            <div>
                <a href="{{ route('production.cutting_jobs.create') }}" class="btn btn-primary">
                    + Cutting Job Baru
                </a>
            </div>
        </div>

        {{-- LIST JOBS --}}
        <div class="card-main p-3">
            <div class="table-wrap">
                <table class="table table-sm align-middle mono table-jobs">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Tanggal</th>
                            <th>Kode Job</th>
                            <th>LOT</th>
                            <th>Gudang</th>
                            <th style="width: 110px;" class="text-end">Bundles</th>
                            <th style="width: 130px;">Status</th>
                            <th style="width: 90px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jobs as $job)
                            @php
                                $status = $job->status ?? 'draft';

                                $statusMap = [
                                    'draft' => [
                                        'label' => 'DRAFT',
                                        'class' => 'status-draft',
                                        'hint' => 'Belum proses cutting',
                                    ],
                                    'cut' => [
                                        'label' => 'CUTTING',
                                        'class' => 'status-cut',
                                        'hint' => 'Sudah cutting, belum QC',
                                    ],
                                    'cut_sent_to_qc' => [
                                        'label' => 'KIRIM QC',
                                        'class' => 'status-sent-to-qc',
                                        'hint' => 'Menunggu QC cutting',
                                    ],
                                    'sent_to_qc' => [
                                        'label' => 'KIRIM QC',
                                        'class' => 'status-sent-to-qc',
                                        'hint' => 'Menunggu QC cutting',
                                    ],
                                    'qc_ok' => [
                                        'label' => 'QC OK',
                                        'class' => 'status-qc-ok',
                                        'hint' => 'QC selesai, hasil OK',
                                    ],
                                    'qc_done' => [
                                        'label' => 'QC SELESAI',
                                        'class' => 'status-qc-ok',
                                        'hint' => 'QC selesai',
                                    ],
                                    'qc_mixed' => [
                                        'label' => 'QC MIXED',
                                        'class' => 'status-qc-mixed',
                                        'hint' => 'Ada OK & reject',
                                    ],
                                    'qc_reject' => [
                                        'label' => 'QC REJECT',
                                        'class' => 'status-qc-reject',
                                        'hint' => 'Banyak reject',
                                    ],
                                ];

                                $cfg = $statusMap[$status] ?? [
                                    'label' => strtoupper($status),
                                    'class' => 'status-draft',
                                    'hint' => '',
                                ];
                                $bundleCount = $job->bundles()->count();
                            @endphp

                            <tr>
                                <td>
                                    {{ $job->date?->format('d M Y') ?? $job->date }}
                                </td>

                                <td class="code-link">
                                    <a href="{{ route('production.cutting_jobs.show', $job) }}">
                                        {{ $job->code }}
                                    </a>
                                </td>

                                <td>
                                    {{ $job->lot?->code ?? '-' }}
                                    <div class="small text-muted">
                                        {{ $job->lot?->item?->code ?? '' }}
                                    </div>
                                </td>

                                <td>
                                    {{ $job->warehouse?->code ?? '-' }}
                                    <div class="small text-muted">
                                        {{ $job->warehouse?->name ?? '' }}
                                    </div>
                                </td>

                                <td class="text-end">
                                    {{ $bundleCount }}
                                </td>

                                <td>
                                    <span class="status-pill {{ $cfg['class'] }}" title="{{ $cfg['hint'] }}">
                                        {{ $cfg['label'] }}
                                    </span>
                                </td>

                                <td class="text-end">
                                    <a href="{{ route('production.cutting_jobs.show', $job) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Belum ada cutting job.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $jobs->links() }}
            </div>
        </div>
    </div>
@endsection
