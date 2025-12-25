{{-- resources/views/production/cutting_jobs/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Cutting Jobs')

@push('head')
    <style>
        .cutting-overview-page {
            min-height: 100vh;
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: 1rem 1rem 3rem;
        }

        body[data-theme="light"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.12) 0,
                    rgba(45, 212, 191, 0.08) 28%,
                    #f9fafb 60%);
        }

        body[data-theme="dark"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.25) 0,
                    rgba(45, 212, 191, 0.15) 26%,
                    #020617 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow:
                0 12px 30px rgba(15, 23, 42, 0.12),
                0 0 0 1px rgba(148, 163, 184, 0.08);
        }

        body[data-theme="dark"] .card-main {
            border-color: rgba(30, 64, 175, 0.55);
            box-shadow:
                0 16px 40px rgba(0, 0, 0, 0.75),
                0 0 0 1px rgba(15, 23, 42, 0.8);
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

        .btn-primary {
            border-radius: 999px;
            padding-inline: 1rem;
        }

        .header-stack {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .75rem;
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

        /* ============================
                   MOBILE: CUTTING JOB LIST
                ============================ */
        @media (max-width: 767.98px) {
            .cut-mobile-secondary {
                font-size: .75rem;
                color: var(--muted);
            }

            .chip-soft {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: .08rem .5rem;
                border: 1px solid rgba(148, 163, 184, 0.55);
                background: rgba(15, 23, 42, 0.02);
                font-size: .72rem;
                max-width: 100%;
                white-space: nowrap;
                text-overflow: ellipsis;
                overflow: hidden;
            }

            .chip-soft .sub {
                opacity: .8;
                margin-left: .25rem;
            }

            .cut-mobile-list {
                display: flex;
                flex-direction: column;
                gap: .6rem;
            }

            .cut-mobile-card {
                border-radius: 16px;
                padding: .7rem .8rem;
                background:
                    radial-gradient(circle at top left,
                        rgba(148, 163, 184, 0.22) 0,
                        color-mix(in srgb, var(--card) 92%, var(--line) 8%) 52%);
                border: 1px solid color-mix(in srgb, var(--line) 75%, transparent 25%);
                box-shadow:
                    0 10px 25px rgba(15, 23, 42, 0.18),
                    0 0 0 1px rgba(15, 23, 42, 0.03);
                cursor: pointer;
                transition: transform 90ms ease-out, box-shadow 90ms ease-out, background 120ms ease-out;
            }

            body[data-theme="dark"] .cut-mobile-card {
                box-shadow:
                    0 14px 40px rgba(0, 0, 0, 0.78),
                    0 0 0 1px rgba(15, 23, 42, 0.7);
            }

            .cut-mobile-card:hover {
                transform: translateY(-1px);
                box-shadow:
                    0 14px 32px rgba(15, 23, 42, 0.22),
                    0 0 0 1px rgba(15, 23, 42, 0.06);
            }

            .cut-mobile-card:active {
                transform: translateY(1px);
                box-shadow:
                    0 6px 16px rgba(15, 23, 42, 0.25),
                    0 0 0 1px rgba(15, 23, 42, 0.09);
            }

            .cut-mobile-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: .5rem;
                margin-bottom: .35rem;
            }

            .cut-mobile-date-pill {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: .08rem .6rem;
                font-size: .75rem;
                font-weight: 600;
                background: color-mix(in srgb, var(--card) 92%, var(--line) 8%);
                border: 1px solid color-mix(in srgb, var(--line) 80%, transparent 20%);
            }

            .cut-mobile-status-pill {
                font-size: .7rem;
                border-radius: 999px;
                padding: .12rem .6rem;
            }

            .cut-mobile-card-body {
                display: flex;
                flex-direction: column;
                gap: .22rem;
                font-size: .78rem;
            }

            .cut-mobile-row-line {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: .5rem;
                flex-wrap: wrap;
            }

            .cut-mobile-metadata {
                font-size: .76rem;
                font-weight: 600;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $role = $user?->role ?? null;
        $isOperating = $role === 'operating';

        // Untuk operator, hanya tampilkan job yang BELUM dicek QC
        // (status bukan qc_ok / qc_done / qc_mixed / qc_reject)
        if ($isOperating) {
            $displayJobs = $jobs->filter(function ($job) {
                return !in_array($job->status, ['qc_ok', 'qc_done', 'qc_mixed', 'qc_reject']);
            });
        } else {
            $displayJobs = $jobs;
        }
    @endphp

    <div class="cutting-overview-page">
        <div class="page-wrap">
            @php
                $pageTotal = $displayJobs->count();
                $pageDraft = $displayJobs->where('status', 'draft')->count();
                $pageCut = $displayJobs->where('status', 'cut')->count();
                $pageQcDone = $displayJobs->whereIn('status', ['qc_ok', 'qc_mixed', 'qc_reject', 'qc_done'])->count();
            @endphp

            {{-- HEADER CARD --}}
            <div class="card-main p-3 mb-3">
                <div class="header-stack">
                    <div>
                        <h1 class="h5 mb-1">
                            Cutting Jobs
                            @if ($isOperating)
                                <span class="badge-soft ms-1">
                                    <span class="badge-label">Mode</span>
                                    <span class="badge-value ms-1">Belum dicek QC</span>
                                </span>
                            @endif
                        </h1>
                        <p class="text-muted small mb-0">
                            @if ($isOperating)
                                Menampilkan hanya cutting job yang belum selesai QC cutting.
                            @else
                                Daftar cutting job produksi yang telah dibuat.
                            @endif
                        </p>
                    </div>

                    <div>
                        <a href="{{ route('production.cutting_jobs.create') }}" class="btn btn-primary">
                            + Cutting Job Baru
                        </a>
                    </div>
                </div>
            </div>

            {{-- LIST JOBS --}}
            <div class="card-main p-3">
                <h2 class="h6 mb-2">Daftar Cutting</h2>

                {{-- DESKTOP: TABEL --}}
                <div class="table-wrap d-none d-md-block">
                    <table class="table table-sm align-middle mono table-jobs">
                        <thead>
                            <tr>
                                <th style="width: 120px;">Tanggal</th>
                                <th>Operator</th>
                                <th>Item Kain</th>
                                <th>Gudang</th>
                                <th style="width: 110px;" class="text-end">Iket</th>
                                <th style="width: 130px;">Status</th>
                                <th style="width: 90px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($displayJobs as $job)
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

                                    $bundleCount = $job->bundles_count ?? $job->bundles()->count();
                                @endphp

                                <tr>
                                    {{-- Tanggal --}}
                                    <td>
                                        {{ $job->date?->format('d M Y') ?? $job->date }}
                                    </td>

                                    {{-- Operator cutting (siapa yang cutting) --}}
                                    <td>
                                        {{ $job->operator?->name ?? '-' }}
                                    </td>

                                    {{-- Item kain dari LOT (nama item, bukan kode LOT) --}}
                                    <td>
                                        @if ($job->lot && $job->lot->item)
                                            {{ $job->lot->item->name ?? '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    {{-- Gudang --}}
                                    <td>
                                        {{ $job->warehouse?->code ?? '-' }}
                                        <div class="small text-muted">
                                            {{ $job->warehouse?->name ?? '' }}
                                        </div>
                                    </td>

                                    {{-- Jumlah iket (bundles) --}}
                                    <td class="text-end">
                                        {{ $bundleCount }}
                                    </td>

                                    {{-- Status --}}
                                    <td>
                                        <span class="status-pill {{ $cfg['class'] }}" title="{{ $cfg['hint'] }}">
                                            {{ $cfg['label'] }}
                                        </span>
                                    </td>

                                    {{-- Aksi --}}
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
                                        @if ($isOperating)
                                            Tidak ada cutting job yang menunggu QC.
                                        @else
                                            Belum ada cutting job.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE: CARD LIST --}}
                <div class="d-block d-md-none mono">
                    @if ($displayJobs->isEmpty())
                        <div class="text-center text-muted small py-3">
                            @if ($isOperating)
                                Tidak ada cutting job yang menunggu QC.
                            @else
                                Belum ada cutting job.
                            @endif
                        </div>
                    @else
                        <div class="cut-mobile-list">
                            @foreach ($displayJobs as $job)
                                @php
                                    $status = $job->status ?? 'draft';

                                    $statusMap = [
                                        'draft' => [
                                            'label' => 'Draft',
                                            'class' => 'status-draft',
                                        ],
                                        'cut' => [
                                            'label' => 'Belum Cek QC',
                                            'class' => 'status-cut',
                                        ],
                                        'cut_sent_to_qc' => [
                                            'label' => 'Kirim QC',
                                            'class' => 'status-sent-to-qc',
                                        ],
                                        'sent_to_qc' => [
                                            'label' => 'Sedang Di Cek QC',
                                            'class' => 'status-sent-to-qc',
                                        ],
                                        'qc_ok' => [
                                            'label' => 'QC OK',
                                            'class' => 'status-qc-ok',
                                        ],
                                        'qc_done' => [
                                            'label' => 'QC Selesai',
                                            'class' => 'status-qc-ok',
                                        ],
                                        'qc_mixed' => [
                                            'label' => 'QC Mixed',
                                            'class' => 'status-qc-mixed',
                                        ],
                                        'qc_reject' => [
                                            'label' => 'QC Reject',
                                            'class' => 'status-qc-reject',
                                        ],
                                    ];

                                    $cfg = $statusMap[$status] ?? [
                                        'label' => ucfirst($status),
                                        'class' => 'status-draft',
                                    ];

                                    $bundleCount = $job->bundles_count ?? $job->bundles()->count();
                                @endphp

                                <div class="cut-mobile-card" data-href="{{ route('production.cutting_jobs.show', $job) }}">
                                    <div class="cut-mobile-card-header">
                                        <div class="cut-mobile-date-pill">
                                            {{ $job->date?->format('Y-m-d') ?? $job->date }}
                                        </div>
                                        <div>
                                            <span class="cut-mobile-status-pill status-pill {{ $cfg['class'] }}">
                                                {{ $cfg['label'] }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="cut-mobile-card-body">
                                        {{-- Baris 1: Operator cutting --}}
                                        <div class="cut-mobile-row-line">
                                            <div class="cut-mobile-secondary">
                                                @if ($job->operator)
                                                    Operator:
                                                    <strong>{{ $job->operator->name }}</strong>
                                                @else
                                                    Operator: <span class="text-muted">-</span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Baris 2: Item kain (dari LOT) --}}
                                        <div class="cut-mobile-row-line">
                                            <div>
                                                @if ($job->lot && $job->lot->item)
                                                    <span class="chip-soft">
                                                        {{ $job->lot->item->name ?? '-' }}
                                                    </span>
                                                @else
                                                    <span class="cut-mobile-secondary">
                                                        Item kain tidak diketahui
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Baris 3: Gudang & iket --}}
                                        <div class="cut-mobile-row-line">
                                            <div class="cut-mobile-secondary">
                                                {{ $job->warehouse?->code ?? '-' }}
                                                @if ($job->warehouse?->name)
                                                    • {{ $job->warehouse->name }}
                                                @endif
                                            </div>
                                            <div class="cut-mobile-metadata text-muted">
                                                {{ $bundleCount }} Iket
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- PAGINATION (SAMA UNTUK DESKTOP & MOBILE) --}}
                <div class="mt-3">
                    {{ $jobs->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // MOBILE: klik card → detail cutting job
            document.querySelectorAll('.cut-mobile-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    const href = this.getAttribute('data-href');
                    if (href) {
                        window.location.href = href;
                    }
                });
            });
        });
    </script>
@endpush
