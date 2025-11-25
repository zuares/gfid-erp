@extends('layouts.app')

@section('title', 'Produksi • Cutting Jobs')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .2rem .6rem;
            font-size: .75rem;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Cutting Jobs</h1>

            <a href="{{ route('production.cutting_jobs.create') }}" class="btn btn-primary">
                + Cutting Job Baru
            </a>
        </div>

        <div class="card p-3">
            <div class="table-responsive">
                <table class="table table-sm align-middle mono">
                    <thead>
                        <tr>
                            <th style="width:120px;">Tanggal</th>
                            <th>Kode</th>
                            <th>LOT</th>
                            <th>Gudang</th>
                            <th style="width:120px;">Bundles</th>
                            <th style="width:120px;">Status</th>
                            <th style="width:90px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jobs as $job)
                            <tr>
                                <td>{{ $job->date }}</td>
                                <td>{{ $job->code }}</td>

                                <td>
                                    {{ $job->lot?->code ?? '-' }}
                                    <div class="small text-muted">
                                        {{ $job->lot?->item?->code }}
                                    </div>
                                </td>

                                <td>
                                    {{ $job->warehouse?->code }}
                                </td>

                                <td>
                                    {{ $job->bundles()->count() }}
                                </td>

                                <td>
                                    @php
                                        $cls =
                                            [
                                                'draft' => 'secondary',
                                                'cut' => 'primary',
                                                'qc_ok' => 'success',
                                                'qc_mixed' => 'warning',
                                                'qc_reject' => 'danger',
                                            ][$job->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $cls }}">{{ strtoupper($job->status) }}</span>
                                </td>

                                <td>
                                    <a href="{{ route('production.cutting_jobs.show', $job) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        Lihat
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

            <div class="mt-2">
                {{ $jobs->links() }}
            </div>
        </div>

    </div>
@endsection
