@extends('layouts.app')

@section('title', 'Report • Finishing Item ' . ($item->code ?? ''))

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding-block: .75rem 1.5rem;
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

        .table-wrap {
            overflow-x: auto;
        }

        .badge-item {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .1rem .6rem;
            border-radius: 999px;
            font-size: .78rem;
            background: color-mix(in srgb, var(--card) 70%, var(--line) 30%);
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .table-wrap {
                font-size: .86rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <h1 class="h5 mb-1">Drilldown Finishing • Item</h1>
                    <div class="badge-item mb-2">
                        <span class="mono">{{ $item->code }}</span>
                        <span>— {{ $item->name }} {{ $item->color }}</span>
                    </div>
                    <div class="help">
                        Menampilkan list Finishing Job <strong>posted</strong> yang berisi item ini,
                        lengkap dengan Qty In, OK, dan Reject per job.
                    </div>
                    @if ($dateFrom || $dateTo)
                        <div class="help mt-1">
                            Periode:
                            @if ($dateFrom)
                                <span class="mono">{{ $dateFrom }}</span>
                            @else
                                &minus;
                            @endif
                            &nbsp;s/d&nbsp;
                            @if ($dateTo)
                                <span class="mono">{{ $dateTo }}</span>
                            @else
                                &minus;
                            @endif
                        </div>
                    @endif
                </div>

                <div class="d-flex flex-column align-items-end gap-2">
                    <a href="{{ route('production.finishing_jobs.report_per_item', [
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                        'item_id' => $item->id,
                    ]) }}"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke rekap per item
                    </a>
                    <a href="{{ route('production.finishing_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-list-ul me-1"></i> Semua Finishing Job
                    </a>
                </div>
            </div>
        </div>

        {{-- SUMMARY --}}
        <div class="card p-3 mb-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Total Qty In</div>
                    <div class="h5 mono mb-0">{{ number_format($grandTotalIn) }}</div>
                    <div class="help">Total pcs item ini yang masuk finishing.</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Total OK (FG)</div>
                    <div class="h5 mono text-success mb-0">{{ number_format($grandTotalOk) }}</div>
                    <div class="help">Masuk gudang FG untuk item ini.</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Total Reject</div>
                    <div class="h5 mono text-danger mb-0">{{ number_format($grandTotalReject) }}</div>
                    <div class="help">Masuk gudang REJECT untuk item ini.</div>
                </div>
            </div>
        </div>

        {{-- TABEL DETAIL JOB --}}
        <div class="card p-0 mb-4">
            <div class="px-3 pt-3 pb-2 d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">Finishing Job yang berisi item ini</div>
                    <div class="help">
                        Klik kode Finishing Job untuk drilldown ke detail transaksi.
                    </div>
                </div>
                <div class="help">
                    Total job: {{ $rows->count() }}
                </div>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 1%;">#</th>
                            <th>Kode Finishing</th>
                            <th>Tanggal</th>
                            <th>Dibuat oleh</th>
                            <th class="text-end">Qty In</th>
                            <th class="text-end text-success">OK</th>
                            <th class="text-end text-danger">Reject</th>
                            <th class="text-end">Reject %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $i => $row)
                            @php
                                $job = $jobs[$row->finishing_job_id] ?? null;
                                $rejectPct = $row->total_in > 0 ? ($row->total_reject / $row->total_in) * 100 : 0;
                            @endphp
                            <tr>
                                <td class="text-muted small">{{ $i + 1 }}</td>

                                {{-- KODE FIN --}}
                                <td class="mono">
                                    @if ($job)
                                        <a href="{{ route('production.finishing_jobs.show', $job->id) }}">
                                            {{ $job->code }}
                                        </a>
                                        @if ($job->status === 'draft')
                                            <span class="badge bg-warning-subtle text-warning-emphasis ms-1 small">
                                                draft
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                {{-- TANGGAL --}}
                                <td class="mono">
                                    @if ($job && $job->date)
                                        {{ function_exists('id_date') ? id_date($job->date) : $job->date->format('Y-m-d') }}
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>

                                {{-- USER --}}
                                <td>
                                    @if ($job && $job->createdBy)
                                        <div class="small fw-semibold">
                                            {{ $job->createdBy->name }}
                                        </div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>

                                {{-- QTY IN --}}
                                <td class="text-end mono">
                                    {{ number_format($row->total_in) }}
                                </td>

                                {{-- OK --}}
                                <td class="text-end mono text-success">
                                    {{ number_format($row->total_ok) }}
                                </td>

                                {{-- REJECT --}}
                                <td class="text-end mono text-danger">
                                    {{ number_format($row->total_reject) }}
                                </td>

                                {{-- REJECT % --}}
                                <td class="text-end mono">
                                    {{ $rejectPct > 0 ? number_format($rejectPct, 2) . ' %' : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Belum ada Finishing Job yang berisi item ini untuk filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse

                        @if ($rows->isNotEmpty())
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="4" class="text-end">TOTAL</th>
                            <th class="text-end mono">{{ number_format($grandTotalIn) }}</th>
                            <th class="text-end mono text-success">{{ number_format($grandTotalOk) }}</th>
                            <th class="text-end mono text-danger">{{ number_format($grandTotalReject) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
