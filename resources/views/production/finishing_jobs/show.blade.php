@extends('layouts.app')

@section('title', 'Produksi • Finishing')

@push('head')
    <style>
        /* (pakai style yang sama seperti sebelumnya — singkatkan di sini) */
        .finishing-create-page {
            min-height: 100vh;
        }

        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: 1rem 1rem 4rem;
        }

        .card-main {
            background: var(--card);
            border-radius: 16px;
            padding: 0;
        }

        .card-header-bar {
            padding: .85rem 1.1rem;
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            align-items: center;
            border-bottom: 1px solid rgba(148, 163, 184, .35);
        }

        .badge-soft-info {
            font-size: .7rem;
            padding: .25rem .55rem;
            border-radius: 999px;
        }

        .finishing-table {
            margin-bottom: 0;
        }

        .wip-badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .2rem .5rem;
            border-radius: 999px;
            font-size: .72rem;
        }

        .qty-ok {
            color: #16a34a;
            font-weight: 600;
        }

        .qty-reject {
            color: #b91c1c;
            font-weight: 600;
        }

        .summary-pill {
            border-radius: 999px;
            border: 1px dashed rgba(148, 163, 184, .7);
            padding: .3rem .75rem;
            display: inline-flex;
            gap: .25rem;
            align-items: center;
            background: rgba(15, 23, 42, .02);
        }

        @media (max-width: 767.98px) {
            .col-operator-desktop {
                display: none;
            }

            .meta-stack-mobile {
                display: block;
            }
        }

        @media (min-width: 768px) {
            .meta-stack-mobile {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $totalLines = $job->lines->count();
        $totalOk = $job->lines->sum('qty_ok');
        $totalReject = $job->lines->sum('qty_reject');
        $hasReject = $totalReject > 0;
        $isPosted = $job->status === 'posted';
    @endphp

    <div class="finishing-create-page">
        <div class="page-wrap">

            {{-- FLASH --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card card-main">
                <div class="card-header-bar">
                    <div>
                        <h1 style="margin:0; font-size:1.15rem;">Finishing</h1>
                        <div class="text-muted" style="font-size:.88rem;">
                            Kode <strong>{{ $job->code }}</strong> · {{ $job->date?->format('d M Y') ?? '-' }}
                        </div>
                    </div>

                    <div class="d-flex gap-2 align-items-center">
                        <div class="badge-status {{ $isPosted ? 'badge-status-posted' : 'badge-status-draft' }}">
                            <i class="bi {{ $isPosted ? 'bi-check-circle' : 'bi-pencil-square' }}"></i>
                            <span class="ms-1">{{ strtoupper($job->status) }}</span>
                        </div>

                        @if ($isPosted && !$hasReject)
                            <div class="badge-soft-info">
                                <i class="bi bi-lightning-charge"></i>
                                <span class="ms-1">AUTO-POSTED (0 reject)</span>
                            </div>
                        @elseif ($hasReject)
                            <div class="badge-status badge-status-reject">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span class="ms-1">HAS REJECT</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card-body-main p-3">
                    {{-- RINGKASAN --}}
                    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
                        <div class="section-title mb-0">Ringkasan</div>

                        <div class="summary-pill">
                            <i class="bi bi-collection"></i>
                            <span>Lines:</span>
                            <strong>{{ $totalLines }} baris</strong>
                        </div>

                        <div class="summary-pill">
                            <i class="bi bi-check2-circle"></i>
                            <span>Total OK:</span>
                            <strong>{{ number_format($totalOk, 0, ',', '.') }} pcs</strong>
                        </div>

                        <div class="summary-pill">
                            <i class="bi bi-x-octagon"></i>
                            <span>Total Reject:</span>
                            <strong>{{ number_format($totalReject, 0, ',', '.') }} pcs</strong>
                        </div>

                        @if ($job->notes)
                            <div class="summary-pill"><i
                                    class="bi bi-journal-text"></i><strong>{{ $job->notes }}</strong></div>
                        @endif
                    </div>

                    {{-- TABEL DETAIL --}}
                    <div class="finishing-table-wrap mb-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle finishing-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width:5%;">No</th>
                                        <th style="width:45%;">Item</th>
                                        <th class="text-end" style="width:12%;">Qty IN</th>
                                        <th class="text-end" style="width:12%;">OK</th>
                                        <th class="text-end" style="width:12%;">Reject</th>
                                        <th class="col-operator-desktop" style="width:15%;">Operator &amp; Reject</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($job->lines as $line)
                                        @php
                                            // item fallback
                                            $item =
                                                $line->item ?? ($line->bundle?->finishedItem ?? $line->bundle?->item);
                                            $bundle = $line->bundle;
                                            // sewing operator: prefer relation, fallback to name column, fallback to pickup operator text

                                            $sewingOpModel = $line->sewingOperator ?? null;
                                            $sewingOpName = $sewingOpModel
                                                ? ($sewingOpModel->code ?? '') . ' ' . ($sewingOpModel->name ?? '')
                                                : $line->sewing_operator_name ?? null;
                                            $qtyIn = $line->qty_in ?? ($line->qty_ok ?? 0) + ($line->qty_reject ?? 0);
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>

                                            <td>
                                                <div class="item-label-main">
                                                    @if ($item)
                                                        {{ $item->code }} — {{ $item->name }}
                                                    @else
                                                        <em>Item tidak ditemukan</em>
                                                    @endif
                                                </div>

                                                <div class="meta-stack-mobile mt-1">
                                                    <div class="meta-line"><i class="bi bi-box-seam"></i> Bundle:
                                                        <strong>{{ $bundle?->code ?? '#' . ($bundle->id ?? '-') }}</strong>
                                                    </div>

                                                    <div class="meta-line">
                                                        @if ($sewingOpName)
                                                            <i class="bi bi-person"></i> {{ $sewingOpName }}
                                                        @else
                                                            <i class="bi bi-person-dash"></i> Operator Sewing Return
                                                        @endif
                                                    </div>

                                                    @if ((float) $line->qty_reject > 0)
                                                        <div class="meta-line text-danger"><i
                                                                class="bi bi-exclamation-circle"></i>
                                                            {{ $line->reject_reason ?? 'Reject' }}
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="item-label-sub d-none d-md-block mt-1">
                                                    <i class="bi bi-box-seam"></i> Bundle:
                                                    <strong>{{ $bundle?->code ?? '#' . ($bundle->id ?? '-') }}</strong>
                                                </div>
                                            </td>

                                            <td class="text-end">
                                                <span class="wip-badge">
                                                    <i class="bi bi-arrow-up-circle"></i>
                                                    <span>{{ number_format($qtyIn, 0, ',', '.') }}</span>
                                                    <small> pcs</small>
                                                </span>
                                            </td>

                                            <td class="text-end"><span
                                                    class="qty-ok">{{ number_format($line->qty_ok ?? 0, 0, ',', '.') }}</span>
                                            </td>

                                            <td class="text-end"><span
                                                    class="qty-reject">{{ number_format($line->qty_reject ?? 0, 0, ',', '.') }}</span>
                                            </td>

                                            <td class="col-operator-desktop">
                                                <div class="item-label-sub mb-1">
                                                    @if ($sewingOpName)
                                                        <i class="bi bi-person"></i>
                                                        <strong>{{ $sewingOpName }}</strong>
                                                    @else
                                                        <i class="bi bi-person-dash"></i> <em>Operator Sewing Return</em>
                                                    @endif
                                                </div>

                                                @if ((float) $line->qty_reject > 0)
                                                    <div class="item-label-sub text-danger">
                                                        <i class="bi bi-exclamation-circle"></i>
                                                        {{ $line->reject_reason ?? 'Reject' }}
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">Tidak ada detail
                                                finishing.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- HPP SNAPSHOTS (RM-only) --}}
                    <div class="mb-3">
                        <div class="section-title">HPP Snapshots (RM-only)</div>
                        @if ($rmSnapshots->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Item</th>
                                            <th class="text-end">Qty Basis</th>
                                            <th class="text-end">RM/unit</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rmSnapshots as $s)
                                            <tr>
                                                <td>{{ optional($s->snapshot_date)->format('d M Y') ?? $s->snapshot_date }}
                                                </td>
                                                <td>{{ optional($s->item)->code ?? $s->item_id }}</td>
                                                <td class="text-end">
                                                    {{ number_format($s->qty_basis ?? ($s->qtyBasis ?? 0), 0, ',', '.') }}
                                                </td>
                                                <td class="text-end">
                                                    {{ number_format($s->rm_unit_cost ?? ($s->rmUnitCost ?? 0), 2, ',', '.') }}
                                                </td>
                                                <td>{{ $s->notes ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-muted small">Belum ada snapshot HPP untuk finishing ini.</div>
                        @endif
                    </div>

                    {{-- FOOTER ACTIONS --}}
                    <div class="footer-actions">
                        <a href="{{ route('production.finishing_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> <span class="ms-1">Kembali</span>
                        </a>

                        @if (!$isPosted)
                            <a href="{{ route('production.finishing_jobs.edit', $job->id) }}"
                                class="btn btn-sm btn-success ms-2">
                                <i class="bi bi-pencil-square"></i> Edit
                            </a>
                        @endif

                        @if (!$isPosted && $hasReject)
                            @can('forcePost', $job)
                                <form id="force-post-form"
                                    action="{{ route('production.finishing_jobs.force_post', $job->id) }}" method="POST"
                                    class="d-inline-block ms-2"
                                    onsubmit="return confirm('Post sekarang? Semua reject akan diabaikan. Lanjutkan?')">
                                    @csrf
                                    <button id="btn-force-post" type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-bolt-fill"></i> POST SEKARANG
                                    </button>
                                </form>
                            @else
                                @php
                                    $u = Auth::user();
                                    $allowed = method_exists($u, 'hasRole')
                                        ? $u->hasRole('owner') || $u->hasRole('admin')
                                        : in_array($u->role ?? null, ['owner', 'admin']);
                                @endphp
                                @if ($allowed)
                                    <form id="force-post-form"
                                        action="{{ route('production.finishing_jobs.force_post', $job->id) }}" method="POST"
                                        class="d-inline-block ms-2"
                                        onsubmit="return confirm('Post sekarang? Semua reject akan diabaikan. Lanjutkan?')">
                                        @csrf
                                        <button id="btn-force-post" type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-bolt-fill"></i> POST SEKARANG
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('force-post-form');
            if (!form) return;
            const btn = document.getElementById('btn-force-post');
            form.addEventListener('submit', function() {
                if (btn) {
                    btn.setAttribute('disabled', 'disabled');
                    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';
                }
            });
        });
    </script>
@endpush
