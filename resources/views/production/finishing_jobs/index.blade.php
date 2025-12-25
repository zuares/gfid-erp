{{-- resources/views/production/finishing_jobs/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Finishing')

@push('head')
    <style>
        :root {
            --fin-card-radius: 16px;
            --fin-border: rgba(148, 163, 184, 0.28);
            --fin-muted: #6b7280;
            --fin-accent: #16a34a;
            /* hijau */
            --fin-accent-soft: #bbf7d0;
            /* hijau muda */
            --fin-pink-soft: #ffe4ef;
            /* pink muda */
            --fin-bg-light-1: #f5fdf8;
            --fin-bg-light-2: #fdf2ff;
            --fin-bg-light-3: #ffffff;
        }

        .finishing-index-page {
            min-height: 100vh;
        }

        .finishing-index-page .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: 1rem 1rem 3.5rem;
        }

        /* LIGHT MODE: hijau + pink lembut seperti finishing create */
        body[data-theme="light"] .finishing-index-page .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(34, 197, 94, 0.16) 0,
                    rgba(244, 114, 182, 0.12) 28%,
                    var(--fin-bg-light-2) 52%,
                    var(--fin-bg-light-3) 100%);
        }

        /* DARK MODE: tetap ada aksen hijau/pink tapi lebih gelap */
        body[data-theme="dark"] .finishing-index-page .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(34, 197, 94, 0.22) 0,
                    rgba(244, 114, 182, 0.20) 26%,
                    #020617 65%);
        }

        /* CARD BASE */
        .fin-card {
            background: var(--card);
            border-radius: var(--fin-card-radius);
            border: 1px solid var(--fin-border);
            box-shadow:
                0 14px 35px rgba(15, 23, 42, 0.10),
                0 0 0 1px rgba(15, 23, 42, 0.04);
        }

        body[data-theme="dark"] .fin-card {
            border-color: rgba(51, 65, 85, 0.8);
            box-shadow:
                0 14px 40px rgba(0, 0, 0, 0.75),
                0 0 0 1px rgba(15, 23, 42, 0.9);
        }

        .fin-card-header {
            padding: .9rem 1.1rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
            font-weight: 600;
            font-size: .78rem;
            letter-spacing: .09em;
            color: var(--fin-muted);
            text-transform: uppercase;
        }

        .fin-card-body {
            padding: 1rem 1.1rem 1.1rem;
        }

        /* BADGE */
        .fin-badge {
            display: inline-block;
            padding: .15rem .65rem;
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            border-radius: 999px;
            letter-spacing: .04em;
        }

        .fin-badge-draft {
            background: rgba(251, 191, 36, 0.12);
            border: 1px solid rgba(251, 191, 36, 0.6);
            color: #92400e;
        }

        .fin-badge-posted {
            background: rgba(34, 197, 94, 0.14);
            /* hijau */
            border: 1px solid rgba(34, 197, 94, 0.7);
            color: #166534;
        }

        .fin-badge-reject {
            background: rgba(244, 114, 182, 0.18);
            /* pink */
            border: 1px solid rgba(244, 114, 182, 0.75);
            color: #9d174d;
        }

        /* FILTER LABEL */
        .fin-filter-label {
            font-size: .78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .09em;
            color: var(--fin-muted);
        }

        body[data-theme="dark"] .fin-filter-label {
            color: #e5e7eb;
        }

        /* FOCAL CARD - MOBILE LIST */
        .fin-row {
            border-radius: 16px;
            padding: .9rem .95rem;
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, 0.12),
                0 0 0 1px rgba(15, 23, 42, 0.03);
            transition: transform .16s ease,
                box-shadow .16s ease,
                border-color .16s ease,
                background .16s ease;
        }

        .fin-row+.fin-row {
            margin-top: .6rem;
        }

        .fin-row:hover {
            transform: translateY(-1px);
            box-shadow:
                0 16px 36px rgba(15, 23, 42, 0.20),
                0 0 0 1px rgba(22, 163, 74, 0.35);
            border-color: rgba(22, 163, 74, 0.65);
            background: linear-gradient(to bottom right,
                    rgba(34, 197, 94, 0.05),
                    rgba(244, 114, 182, 0.05));
        }

        .fin-code {
            font-weight: 600;
            font-size: .94rem;
            letter-spacing: .03em;
        }

        .fin-meta {
            font-size: .78rem;
            color: var(--fin-muted);
        }

        body[data-theme="dark"] .fin-meta {
            color: #9ca3af;
        }

        .fin-stat {
            font-size: .8rem;
        }

        .fin-stat span {
            font-weight: 600;
        }

        /* TABLE DESKTOP */
        table.fin-table {
            font-size: .85rem;
        }

        table.fin-table thead th {
            border: none;
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--fin-muted);
            font-weight: 600;
            padding-top: .3rem;
            padding-bottom: .6rem;
        }

        table.fin-table tbody td {
            border-top: 1px solid rgba(148, 163, 184, 0.12);
            padding-block: .55rem;
            vertical-align: middle;
        }

        /* MOBILE TWEAKS */
        @media (max-width: 768px) {
            .finishing-index-page .page-wrap {
                padding-inline: .8rem;
            }

            .fin-card-body {
                padding-inline: .85rem;
                padding-bottom: .95rem;
            }

            .fin-row {
                padding-inline: .85rem;
            }

            .fin-code {
                font-size: .9rem;
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

    <div class="finishing-index-page">
        <div class="page-wrap">

            {{-- HEADER --}}
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h1 class="h5 mb-1 fw-semibold">
                        Finishing Jobs
                    </h1>
                    <div class="text-muted small">
                        Rekap pekerjaan finishing per bundle • fokus status draft / posted & reject
                    </div>
                </div>

                {{-- DESKTOP: tombol + --}}
                <a href="{{ route('production.finishing_jobs.create') }}"
                    class="btn btn-success btn-sm d-none d-md-inline-flex align-items-center">
                    <i class="bi bi-plus-circle me-1"></i>
                    Finishing Baru
                </a>
            </div>

            {{-- FLASH --}}
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- FILTER CARD (hanya non-operating) --}}
            @if (!$isOperating)
                <div class="fin-card mb-3">
                    <div class="fin-card-header d-flex justify-content-between align-items-center">
                        <span class="fin-filter-label">Filter</span>

                        @if ($search || $status || $rejectFlag)
                            <a href="{{ route('production.finishing_jobs.index') }}"
                                class="small text-muted text-decoration-none">
                                Reset
                            </a>
                        @endif
                    </div>
                    <div class="fin-card-body">
                        <form method="GET" action="{{ route('production.finishing_jobs.index') }}" class="row g-2 g-md-3">
                            <div class="col-12 col-md-5">
                                <label class="form-label small mb-1">Cari (kode / catatan)</label>
                                <input type="text" name="search" value="{{ $search }}"
                                    class="form-control form-control-sm" placeholder="FIN-... atau catatan">
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="posted" {{ $status === 'posted' ? 'selected' : '' }}>Posted</option>
                                </select>
                            </div>

                            <div class="col-6 col-md-2">
                                <label class="form-label small mb-1">Reject</label>
                                <select name="reject" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    <option value="yes" {{ $rejectFlag === 'yes' ? 'selected' : '' }}>Ada reject</option>
                                    <option value="no" {{ $rejectFlag === 'no' ? 'selected' : '' }}>Tanpa reject
                                    </option>
                                </select>
                            </div>

                            <div class="col-12 col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-success btn-sm w-100">
                                    <i class="bi bi-search me-1"></i> Terapkan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- MOBILE: tombol + --}}
            <div class="d-md-none mb-3">
                <a href="{{ route('production.finishing_jobs.create') }}" class="btn btn-success btn-sm w-100">
                    <i class="bi bi-plus-circle me-1"></i> Finishing Baru
                </a>
            </div>

            {{-- LIST CARD --}}
            <div class="fin-card">
                <div class="fin-card-header d-flex justify-content-between align-items-center">
                    <span class="fin-filter-label">Daftar Finishing</span>
                    <span class="small text-muted">{{ $finishingJobs->total() }} data</span>
                </div>

                <div class="fin-card-body">
                    @if ($finishingJobs->isEmpty())
                        <div class="text-center text-muted small py-4">
                            Belum ada data finishing.
                        </div>
                    @else
                        {{-- MOBILE: focal card style --}}
                        <div class="d-md-none">
                            @foreach ($finishingJobs as $job)
                                @php
                                    $isPosted = !is_null($job->posted_at);
                                    $hasReject = ($job->total_reject ?? 0) > 0;
                                @endphp

                                <a href="{{ route('production.finishing_jobs.show', $job) }}"
                                    class="text-decoration-none text-reset">
                                    <div class="fin-row">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <div>
                                                <div class="fin-code mb-1">
                                                    {{ $job->code }}
                                                </div>
                                                <div class="fin-meta">
                                                    {{ optional($job->date)->format('d M Y') ?? '-' }}
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="mb-1">
                                                    <span
                                                        class="fin-badge {{ $isPosted ? 'fin-badge-posted' : 'fin-badge-draft' }}">
                                                        {{ $isPosted ? 'Posted' : 'Draft' }}
                                                    </span>
                                                </div>
                                                @if ($hasReject)
                                                    <div>
                                                        <span class="fin-badge fin-badge-reject">
                                                            Has Reject
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <div class="fin-stat">
                                                Bundle: <span>{{ $job->bundle_count ?? 0 }}</span>
                                            </div>
                                            <div class="fin-stat text-end">
                                                OK: <span>{{ $job->total_ok ?? 0 }}</span>
                                                &nbsp;•&nbsp;
                                                R: <span>{{ $job->total_reject ?? 0 }}</span>
                                            </div>
                                        </div>

                                        @if ($job->notes)
                                            <div class="fin-meta mt-2 text-truncate">
                                                {{ $job->notes }}
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        {{-- DESKTOP: tabel --}}
                        <div class="d-none d-md-block">
                            <div class="table-responsive">
                                <table class="table fin-table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 12%">Tanggal</th>
                                            <th style="width: 16%">Kode</th>
                                            <th style="width: 18%">Status</th>
                                            <th style="width: 15%" class="text-end">Bundle</th>
                                            <th style="width: 20%" class="text-end">Qty OK / Reject</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($finishingJobs as $job)
                                            @php
                                                $isPosted = !is_null($job->posted_at);
                                                $hasReject = ($job->total_reject ?? 0) > 0;
                                            @endphp
                                            <tr>
                                                <td>
                                                    {{ optional($job->date)->format('d M Y') ?? '-' }}
                                                </td>
                                                <td>
                                                    <a href="{{ route('production.finishing_jobs.show', $job) }}"
                                                        class="text-decoration-none fin-code">
                                                        {{ $job->code }}
                                                    </a>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <span
                                                            class="fin-badge {{ $isPosted ? 'fin-badge-posted' : 'fin-badge-draft' }}">
                                                            {{ $isPosted ? 'Posted' : 'Draft' }}
                                                        </span>
                                                        @if ($hasReject)
                                                            <span class="fin-badge fin-badge-reject">
                                                                Has Reject
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-semibold">{{ $job->bundle_count ?? 0 }}</span>
                                                </td>
                                                <td class="text-end small">
                                                    OK:
                                                    <span class="fw-semibold">{{ $job->total_ok ?? 0 }}</span>
                                                    &nbsp;/&nbsp;
                                                    R:
                                                    <span
                                                        class="fw-semibold text-danger">{{ $job->total_reject ?? 0 }}</span>
                                                </td>
                                                <td class="small text-muted">
                                                    {{ $job->notes }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- PAGINATION (val
id untuk desktop & mobile) --}}
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

        </div>
    </div>
@endsection
