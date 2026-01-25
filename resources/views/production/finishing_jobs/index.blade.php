{{-- resources/views/production/finishing_jobs/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Finishing')

@push('head')
    <style>
        :root {
            --r: 16px;
            --b: rgba(148, 163, 184, .22);
            --muted: #6b7280;
            --shadow: 0 12px 30px rgba(15, 23, 42, .10), 0 0 0 1px rgba(15, 23, 42, .03);

            --ok-bg: rgba(34, 197, 94, .12);
            --ok-br: rgba(34, 197, 94, .55);
            --ok-tx: #166534;

            --draft-bg: rgba(251, 191, 36, .14);
            --draft-br: rgba(251, 191, 36, .65);
            --draft-tx: #92400e;

            --rj-bg: rgba(244, 114, 182, .16);
            --rj-br: rgba(244, 114, 182, .70);
            --rj-tx: #9d174d;
        }

        .page-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 14px 12px 96px;
        }

        /* background mirip create / cutting style */
        body[data-theme="light"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(34, 197, 94, .14) 0,
                    rgba(244, 114, 182, .10) 28%,
                    rgba(255, 255, 255, 1) 75%);
            border-radius: 18px;
        }

        body[data-theme="dark"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(34, 197, 94, .20) 0,
                    rgba(244, 114, 182, .18) 26%,
                    #020617 68%);
            border-radius: 18px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .card-main {
            background: var(--card);
            border: 1px solid var(--b);
            border-radius: var(--r);
            box-shadow: var(--shadow);
        }

        .header-stack {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-pill {
            border-radius: 999px !important;
            font-weight: 900;
            padding: .55rem 1rem;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: .18rem .65rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .04em;
            text-transform: uppercase;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .status-draft {
            background: var(--draft-bg);
            border-color: var(--draft-br);
            color: var(--draft-tx);
        }

        .status-posted {
            background: var(--ok-bg);
            border-color: var(--ok-br);
            color: var(--ok-tx);
        }

        .status-reject {
            background: var(--rj-bg);
            border-color: var(--rj-br);
            color: var(--rj-tx);
        }

        /* TABLE */
        .table-wrap {
            border: 1px solid rgba(148, 163, 184, .14);
            border-radius: 14px;
            overflow: hidden;
        }

        table.table-jobs thead th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            font-weight: 900;
            border-bottom: 1px solid rgba(148, 163, 184, .14);
            padding-top: .7rem;
            padding-bottom: .7rem;
        }

        table.table-jobs tbody td {
            border-top: 1px solid rgba(148, 163, 184, .10);
            padding-block: .65rem;
            vertical-align: middle;
        }

        .fin-code {
            font-weight: 900;
            letter-spacing: .06em;
        }

        /* MOBILE LIST (tap card) */
        .fin-mobile-list {
            display: grid;
            gap: .65rem;
        }

        .fin-mobile-card {
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .20);
            background: var(--card);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .10), 0 0 0 1px rgba(15, 23, 42, .03);
            overflow: hidden;
            cursor: pointer;
        }

        .fin-mobile-card:active {
            transform: scale(.995);
        }

        .fin-mobile-card-header {
            padding: .75rem .85rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(148, 163, 184, .12);
        }

        .fin-mobile-date-pill {
            display: inline-flex;
            align-items: center;
            padding: .20rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .04em;
            border: 1px solid rgba(148, 163, 184, .25);
            color: var(--muted);
            background: rgba(148, 163, 184, .05);
            white-space: nowrap;
        }

        body[data-theme="dark"] .fin-mobile-date-pill {
            background: rgba(15, 23, 42, .22);
        }

        .fin-mobile-card-body {
            padding: .8rem .85rem .9rem;
            display: grid;
            gap: .45rem;
        }

        .fin-mobile-row-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .fin-mobile-secondary {
            color: var(--muted);
            font-size: .78rem;
            font-weight: 800;
        }

        .fin-mobile-metadata {
            font-size: .78rem;
            font-weight: 900;
            white-space: nowrap;
        }

        @media(max-width:768px) {
            .page-wrap {
                padding: 12px 10px 92px;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $role = $user?->role ?? null;
        $isOperating = $role === 'operating';
    @endphp

    <div class="page-wrap">

        {{-- HEADER CARD --}}
        <div class="card-main p-3 mb-3">
            <div class="header-stack">
                <div>
                    <h1 class="h5 mb-1 fw-semibold">Finishing Jobs</h1>
                    <p class="text-muted small mb-0">
                        Rekap pekerjaan finishing per bundle • status draft / posted & reject
                    </p>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <a href="{{ route('production.finishing_jobs.create') }}" class="btn btn-success btn-pill">
                        + Finishing Baru
                    </a>
                </div>
            </div>
        </div>

        {{-- FLASH --}}
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- LIST CARD --}}
        <div class="card-main p-3">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <h2 class="h6 mb-0">Daftar Finishing</h2>
                <div class="small text-muted">{{ $finishingJobs->total() }} data</div>
            </div>

            @if ($finishingJobs->isEmpty())
                <div class="text-center text-muted small py-4">
                    Belum ada data finishing.
                </div>
            @else
                {{-- DESKTOP TABLE --}}
                <div class="table-wrap d-none d-md-block">
                    <table class="table table-sm align-middle mono table-jobs mb-0">
                        <thead>
                            <tr>
                                <th style="width:130px;">Tanggal</th>
                                <th style="width:170px;">Kode</th>
                                <th style="width:200px;">Status</th>
                                <th style="width:110px;" class="text-end">Bundle</th>
                                <th style="width:160px;" class="text-end">OK / Reject</th>
                                <th>Catatan</th>
                                <th style="width:90px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($finishingJobs as $job)
                                @php
                                    // ✅ tanpa posted_at (sqlite compat)
                                    $isPosted = ($job->status ?? 'draft') === 'posted';
                                    $hasReject = ((int) ($job->total_reject ?? 0)) > 0;
                                @endphp
                                <tr>
                                    <td>{{ optional($job->date)->format('d M Y') ?? '-' }}</td>

                                    <td>
                                        <a href="{{ route('production.finishing_jobs.show', $job) }}"
                                            class="text-decoration-none fin-code">
                                            {{ $job->code }}
                                        </a>
                                    </td>

                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <span class="status-pill {{ $isPosted ? 'status-posted' : 'status-draft' }}">
                                                {{ $isPosted ? 'Posted' : 'Draft' }}
                                            </span>
                                            @if ($hasReject)
                                                <span class="status-pill status-reject">Has Reject</span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="text-end fw-semibold">{{ (int) ($job->bundle_count ?? 0) }}</td>

                                    <td class="text-end">
                                        OK: <span class="fw-semibold">{{ (int) ($job->total_ok ?? 0) }}</span>
                                        <span class="text-muted">/</span>
                                        R: <span
                                            class="fw-semibold text-danger">{{ (int) ($job->total_reject ?? 0) }}</span>
                                    </td>

                                    <td class="small text-muted text-truncate" style="max-width:360px;">
                                        {{ $job->notes }}
                                    </td>

                                    <td class="text-end">
                                        <a href="{{ route('production.finishing_jobs.show', $job) }}"
                                            class="btn btn-sm btn-outline-primary" style="border-radius:999px;">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE LIST --}}
                <div class="d-block d-md-none mono">
                    <div class="fin-mobile-list">
                        @foreach ($finishingJobs as $job)
                            @php
                                // ✅ FIX: pakai status (bukan posted_at)
                                $isPosted = ($job->status ?? 'draft') === 'posted';
                                $hasReject = ((int) ($job->total_reject ?? 0)) > 0;

                                $href = route('production.finishing_jobs.show', $job);
                                $datePill = optional($job->date)->format('Y-m-d') ?? '-';
                            @endphp

                            <div class="fin-mobile-card" data-href="{{ $href }}">
                                <div class="fin-mobile-card-header">
                                    <div class="fin-mobile-date-pill">{{ $datePill }}</div>
                                    <div class="d-flex gap-1 align-items-center">
                                        <span class="status-pill {{ $isPosted ? 'status-posted' : 'status-draft' }}">
                                            {{ $isPosted ? 'Posted' : 'Draft' }}
                                        </span>
                                        @if ($hasReject)
                                            <span class="status-pill status-reject">Reject</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="fin-mobile-card-body">
                                    <div class="fin-mobile-row-line">
                                        <div class="fin-code">{{ $job->code }}</div>
                                        <div class="fin-mobile-metadata text-muted">
                                            {{ (int) ($job->bundle_count ?? 0) }} Bundle
                                        </div>
                                    </div>

                                    <div class="fin-mobile-row-line">
                                        <div class="fin-mobile-secondary">
                                            OK: <strong>{{ (int) ($job->total_ok ?? 0) }}</strong>
                                            &nbsp;•&nbsp;
                                            R: <strong class="text-danger">{{ (int) ($job->total_reject ?? 0) }}</strong>
                                        </div>
                                    </div>

                                    @if (!empty($job->notes))
                                        <div class="fin-mobile-row-line">
                                            <div class="fin-mobile-secondary text-truncate" style="max-width:100%;">
                                                {{ $job->notes }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- PAGINATION --}}
                @if ($finishingJobs->hasPages())
                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small text-muted">
                            Halaman {{ $finishingJobs->currentPage() }} dari {{ $finishingJobs->lastPage() }}
                        </div>
                        <div>
                            {{ $finishingJobs->links() }}
                        </div>
                    </div>
                @endif
            @endif
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Klik seluruh kartu mobile -> ke detail
            document.querySelectorAll('.fin-mobile-card[data-href]').forEach(card => {
                card.addEventListener('click', () => {
                    const href = card.getAttribute('data-href');
                    if (href) window.location.href = href;
                });
            });
        });
    </script>
@endpush
