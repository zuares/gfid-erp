@extends('layouts.app')

@section('title', 'Laporan • Performa Jahit per Operator')

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
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .5rem;
            font-size: .7rem;
        }

        .table-wrap {
            overflow-x: auto;
        }
    </style>
@endpush

@section('content')
    @php
        $filters = $filters ?? [];
    @endphp

    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h1 class="h5 mb-1">Performa Jahit per Operator</h1>
                    <div class="help">
                        Rekap total OK / Reject per operator jahit berdasarkan Sewing Return.
                    </div>
                </div>

                <a href="{{ route('production.sewing_returns.index') }}" class="btn btn-sm btn-outline-secondary">
                    &larr; Kembali ke Sewing Return
                </a>
            </div>

            {{-- FILTERS --}}
            <form method="get" class="row g-2 mt-3">
                <div class="col-md-3 col-6">
                    <div class="help mb-1">Tanggal dari</div>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                        value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Tanggal sampai</div>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                        value="{{ $filters['date_to'] ?? '' }}">
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Operator Jahit</div>
                    <select name="operator_id" class="form-select form-select-sm">
                        <option value="">-- Semua Operator --</option>
                        @foreach ($operators as $op)
                            <option value="{{ $op->id }}"
                                {{ isset($filters['operator_id']) && (int) $filters['operator_id'] === $op->id ? 'selected' : '' }}>
                                {{ $op->code }} — {{ $op->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 col-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">
                        Filter
                    </button>
                    <a href="{{ route('production.sewing_returns.report_operators') }}"
                        class="btn btn-sm btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="card p-3">
            <h2 class="h6 mb-2">Rekap per Operator</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 180px;">Operator</th>
                            <th style="width: 120px;">Total OK</th>
                            <th style="width: 120px;">Total Reject</th>
                            <th style="width: 120px;">Total (OK+Rj)</th>
                            <th style="width: 120px;">% Reject</th>
                            <th style="width: 140px;"># Sewing Return</th>
                            <th style="width: 140px;"># Baris Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $totalOk = (float) $row->total_ok;
                                $totalReject = (float) $row->total_reject;
                                $totalAll = $totalOk + $totalReject;
                                $rejectPct = $totalAll > 0 ? ($totalReject / $totalAll) * 100 : 0;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    {{ $row->operator_code }} — {{ $row->operator_name }}
                                </td>
                                <td>{{ number_format($totalOk, 2, ',', '.') }}</td>
                                <td class="text-danger">{{ number_format($totalReject, 2, ',', '.') }}</td>
                                <td>{{ number_format($totalAll, 2, ',', '.') }}</td>
                                <td>
                                    {{ number_format($rejectPct, 2, ',', '.') }}%
                                </td>
                                <td>{{ $row->total_returns }}</td>
                                <td>{{ $row->total_lines }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted small">
                                    Tidak ada data Sewing Return untuk filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </div>
@endsection
