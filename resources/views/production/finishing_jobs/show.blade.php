{{-- resources/views/production/finishing_jobs/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Finishing Job ' . $job->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding-bottom: 1rem;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .84rem;
        }

        .badge-status {
            border-radius: 999px;
            padding: .14rem .6rem;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .05em;
        }

        .badge-status-draft {
            background: rgba(255, 163, 31, .15);
            color: #b35a00;
        }

        .badge-status-posted {
            background: rgba(16, 185, 129, .15);
            color: #0f5132;
        }

        .table-wrap {
            overflow-x: auto;
        }

        @media (max-width: 768px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .table-wrap {
                font-size: .85rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- FLASH --}}
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show mb-3">
                {{ session('status') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- ===========================
         HEADER
    ============================ --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">

                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h1 class="h5 mb-0">
                            Finishing Job <span class="mono">{{ $job->code }}</span>
                        </h1>

                        @if ($job->status === 'posted')
                            <span class="badge-status badge-status-posted">POSTED</span>
                        @else
                            <span class="badge-status badge-status-draft">DRAFT</span>
                        @endif
                    </div>

                    <div class="help">
                        Tanggal:
                        <span class="mono">
                            {{ function_exists('id_date') ? id_date($job->date) : $job->date->format('Y-m-d') }}
                        </span>

                        @if ($job->createdBy)
                            · Dibuat oleh:
                            <span class="mono">{{ $job->createdBy->name }}</span>
                        @endif
                    </div>

                    @if ($job->notes)
                        <div class="small mt-2">
                            <span class="fw-semibold">Catatan:</span>
                            {!! nl2br(e($job->notes)) !!}
                        </div>
                    @endif
                </div>

                {{-- ACTION BUTTONS --}}
                <div class="d-flex flex-column align-items-end gap-2">

                    <div class="d-flex gap-2">
                        <a href="{{ route('production.finishing_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>

                        @if ($job->status === 'draft')
                            <a href="{{ route('production.finishing_jobs.edit', $job->id) }}"
                                class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil-square me-1"></i> Edit Draft
                            </a>
                        @endif
                    </div>

                    {{-- POST --}}
                    @if ($job->status === 'draft')
                        <form action="{{ route('production.finishing_jobs.post', $job->id) }}" method="post"
                            onsubmit="return confirm('Posting akan memindahkan stok: WIP-FIN → FG + REJECT.\nLanjutkan?');">
                            @csrf
                            <button class="btn btn-sm btn-success mt-1">
                                <i class="bi bi-check2-circle me-1"></i>
                                Posting & Update Stok
                            </button>
                        </form>

                        {{-- UNPOST --}}
                    @else
                        <form action="{{ route('production.finishing_jobs.unpost', $job->id) }}" method="post"
                            onsubmit="return confirm('Unpost akan membalik stok FG + REJECT → WIP-FIN.\nPastikan stok FG/REJECT masih tersedia.\nLanjutkan?');">
                            @csrf
                            <button class="btn btn-sm btn-outline-danger mt-1">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>
                                Unpost & Balikkan Stok
                            </button>
                        </form>

                        <div class="help mt-1">
                            Stok sudah dipindahkan ketika posting.
                        </div>
                    @endif
                </div>

            </div>
        </div>


        {{-- ===========================
         SUMMARY
    ============================ --}}
        @php
            $totalIn = $job->lines->sum('qty_in');
            $totalOk = $job->lines->sum('qty_ok');
            $totalReject = $job->lines->sum('qty_reject');
        @endphp

        <div class="card p-3 mb-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Total Qty In</div>
                    <div class="h5 mono">{{ number_format($totalIn) }}</div>
                    <div class="help">Masuk proses finishing.</div>
                </div>

                <div class="col-md-4">
                    <div class="small text-muted mb-1">Total OK (FG)</div>
                    <div class="h5 mono text-success">{{ number_format($totalOk) }}</div>
                    <div class="help">Akan masuk FG saat posting.</div>
                </div>

                <div class="col-md-4">
                    <div class="small text-muted mb-1">Total Reject</div>
                    <div class="h5 mono text-danger">{{ number_format($totalReject) }}</div>
                    <div class="help">Masuk gudang REJECT.</div>
                </div>
            </div>
        </div>


        {{-- ===========================
         HPP RM-ONLY DARI FINISHING INI
    ============================ --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <div class="fw-semibold">HPP RM-only dari Finishing ini</div>
                    <div class="help">
                        Snapshot otomatis tipe <code>auto_hpp_rm_only_finishing</code> per item FG.
                    </div>
                </div>

                @if ($rmSnapshots->isNotEmpty())
                    <div class="help">
                        Total snapshot: {{ $rmSnapshots->count() }}
                    </div>
                @endif
            </div>

            @if ($rmSnapshots->isEmpty())
                <div class="help">
                    Belum ada snapshot RM-only yang tercatat untuk finishing job ini.
                    Snapshot akan dibuat otomatis setelah QC Finishing disimpan.
                </div>
            @else
                <div class="table-wrap">
                    <table class="table table-sm align-middle mono mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 140px;">Tanggal</th>
                                <th>Item FG</th>
                                <th class="text-end" style="width: 120px;">Qty Basis</th>
                                <th class="text-end" style="width: 140px;">RM/Unit</th>
                                <th class="text-end" style="width: 160px;">Total RM</th>
                                <th style="width: 110px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rmSnapshots as $snap)
                                @php
                                    $rmUnit = (float) $snap->rm_unit_cost;
                                    $qty = (float) $snap->qty_basis;
                                    $total = $rmUnit * $qty;
                                @endphp
                                <tr>
                                    <td>
                                        @if ($snap->snapshot_date instanceof \Illuminate\Support\Carbon)
                                            {{ $snap->snapshot_date->format('d/m/Y') }}
                                        @else
                                            {{ \Illuminate\Support\Carbon::parse($snap->snapshot_date)->format('d/m/Y') }}
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $snap->item?->code ?? 'ITEM ?' }}</div>
                                        <div class="help">
                                            {{ $snap->item?->name ?? '' }}
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($qty, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($rmUnit, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($total, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        @if ($snap->is_active)
                                            <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1">
                                                Historis
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>


        {{-- ===========================
         DETAIL GRID
    ============================ --}}
        <div class="card p-0 mb-4">
            <div class="px-3 pt-3 pb-2 d-flex justify-content-between">
                <div>
                    <div class="fw-semibold">Detail Bundle</div>
                    <div class="help">Hasil finishing per bundle.</div>
                </div>

                <div class="help">
                    Total baris: {{ $job->lines->count() }}
                </div>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Bundle</th>
                            <th>Item</th>
                            <th>Operator</th>
                            <th class="text-end">Qty In</th>
                            <th class="text-end text-success">OK</th>
                            <th class="text-end text-danger">Reject</th>
                            <th>Alasan</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($job->lines as $i => $line)
                            @php
                                $bundle = $line->bundle;
                                $cutJob = $bundle?->cuttingJob;
                                $item = $bundle?->finishedItem ?? $line->item; // barang jadi pasti ada
                            @endphp

                            <tr>
                                <td class="text-muted small">{{ $i + 1 }}</td>

                                {{-- BUNDLE --}}
                                <td class="mono">
                                    @if ($bundle && $cutJob)
                                        <a href="{{ route('production.cutting_jobs.show', $cutJob->id) }}">
                                            {{ $bundle->bundle_code }}
                                        </a>
                                    @else
                                        {{ $bundle->bundle_code ?? 'BND-' . $bundle->id }}
                                    @endif
                                </td>

                                {{-- ITEM (FINISHED ITEM) --}}
                                <td>
                                    @if ($item)
                                        <div class="small fw-semibold">{{ $item->code }}</div>
                                        <div class="small text-muted">
                                            {{ $item->name }} · {{ $item->color }}
                                        </div>
                                    @else
                                        <span class="text-muted small">Item tidak ditemukan</span>
                                    @endif
                                </td>

                                {{-- OPERATOR --}}
                                <td>
                                    @if ($line->operator)
                                        <div class="small fw-semibold">
                                            {{ $line->operator->code }} — {{ $line->operator->name }}
                                        </div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>

                                <td class="text-end mono">{{ number_format($line->qty_in) }}</td>
                                <td class="text-end mono text-success">{{ number_format($line->qty_ok) }}</td>
                                <td class="text-end mono text-danger">{{ number_format($line->qty_reject) }}</td>

                                {{-- REJECT REASON --}}
                                <td>
                                    @if ($line->qty_reject > 0)
                                        @if ($line->reject_reason)
                                            <div class="small fw-semibold text-danger">{{ $line->reject_reason }}</div>
                                        @endif
                                        @if ($line->reject_notes)
                                            <div class="small text-muted">{!! nl2br(e($line->reject_notes)) !!}</div>
                                        @endif
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    @if ($job->lines->isNotEmpty())
                        <tfoot>
                            <tr class="table-light">
                                <th colspan="4" class="text-end">TOTAL</th>
                                <th class="text-end mono">{{ number_format($totalIn) }}</th>
                                <th class="text-end mono text-success">{{ number_format($totalOk) }}</th>
                                <th class="text-end mono text-danger">{{ number_format($totalReject) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>


        {{-- FOOTNOTE --}}
        <div class="help mb-4">
            Dibuat:
            <span class="mono">
                {{ function_exists('id_datetime') ? id_datetime($job->created_at) : $job->created_at->format('Y-m-d H:i') }}
            </span>
            · Diupdate:
            <span class="mono">
                {{ function_exists('id_datetime') ? id_datetime($job->updated_at) : $job->updated_at->format('Y-m-d H:i') }}
            </span>
        </div>

    </div>
@endsection
