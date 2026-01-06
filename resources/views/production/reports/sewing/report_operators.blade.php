{{-- resources/views/production/sewing_returns/report_operators.blade.php --}}
@extends('layouts.app')

@section('title', 'Production • Sewing Operator Dashboard')

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

        .card-soft {
            background: color-mix(in srgb, var(--card) 80%, var(--line) 20%);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
        }

        .muted {
            color: var(--muted);
            font-size: .85rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .18rem .6rem;
            font-size: .75rem;
            background: color-mix(in srgb, var(--card) 65%, var(--line) 35%);
        }

        .filters-grid {
            display: grid;
            gap: .75rem;
        }

        @media (min-width: 768px) {
            .filters-grid {
                grid-template-columns: minmax(0, 2fr) repeat(2, minmax(0, 1.1fr)) auto;
                align-items: end;
            }
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            border-radius: 999px;
            padding: .1rem .55rem;
            font-size: .75rem;
            background: color-mix(in srgb, var(--card) 70%, var(--line) 30%);
            color: var(--muted);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3 py-md-4">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">Sewing Operator Dashboard</h1>
                <div class="muted">
                    Overview of pickups, returns, and outstanding quantities per sewing operator.
                </div>
            </div>

            <a href="{{ route('production.sewing.reports.outstanding') }}" class="btn btn-outline-secondary btn-sm">
                View outstanding detail
            </a>
        </div>

        {{-- Filters --}}
        <div class="card p-3 mb-3">
            <form method="GET" class="filters-grid">
                <div class="w-100">
                    <label class="form-label mb-1">Operator</label>
                    <select name="operator_id" class="form-select">
                        <option value="">All operators</option>
                        @foreach ($operators as $op)
                            <option value="{{ $op->id }}" @selected($operatorId == $op->id)>
                                {{ $op->code }} — {{ $op->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-100">
                    <label class="form-label mb-1">Date from</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                </div>

                <div class="w-100">
                    <label class="form-label mb-1">Date to</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        Apply
                    </button>
                    <a href="{{ route('production.reports.operators') }}" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Active filters chips --}}
        @if ($dateFrom || $dateTo || $operatorId)
            <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
                <span class="muted me-1">Active filters:</span>
                @if ($operatorId)
                    @php
                        $op = $operators->firstWhere('id', $operatorId);
                    @endphp
                    @if ($op)
                        <span class="chip">
                            Operator: {{ $op->code }} — {{ $op->name }}
                        </span>
                    @endif
                @endif
                @if ($dateFrom)
                    <span class="chip">
                        From: {{ id_date($dateFrom) }}
                    </span>
                @endif
                @if ($dateTo)
                    <span class="chip">
                        To: {{ id_date($dateTo) }}
                    </span>
                @endif
            </div>
        @endif

        {{-- Summary cards --}}
        @php
            $totalPicked = $rows->sum('total_picked');
            $totalReturnedOk = $rows->sum('total_returned_ok');
            $totalReturnedReject = $rows->sum('total_returned_reject');
            $totalOutstanding = $rows->sum('total_outstanding');
        @endphp

        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">Total picked</div>
                    <div class="mono fs-5">{{ number_format($totalPicked) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">Returned OK</div>
                    <div class="mono fs-5">{{ number_format($totalReturnedOk) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">Returned reject</div>
                    <div class="mono fs-5">{{ number_format($totalReturnedReject) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">Outstanding (not yet returned)</div>
                    <div class="mono fs-5">{{ number_format($totalOutstanding) }}</div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="card p-0">
            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Operator</th>
                            <th class="text-center">Pickups</th>
                            <th class="text-end">Picked</th>
                            <th class="text-end">Returned OK</th>
                            <th class="text-end">Returned reject</th>
                            <th class="text-end">Outstanding</th>
                            <th class="text-end d-none d-md-table-cell">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $row->operator_code }} — {{ $row->operator_name }}
                                    </div>
                                </td>
                                <td class="text-center mono">
                                    {{ $row->total_pickups }}
                                </td>
                                <td class="text-end mono">
                                    {{ number_format($row->total_picked) }}
                                </td>
                                <td class="text-end mono text-success">
                                    {{ number_format($row->total_returned_ok) }}
                                </td>
                                <td class="text-end mono text-danger">
                                    {{ number_format($row->total_returned_reject) }}
                                </td>
                                <td class="text-end mono">
                                    @if ($row->total_outstanding > 0)
                                        <span class="badge-soft mono">
                                            {{ number_format($row->total_outstanding) }}
                                        </span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-end d-none d-md-table-cell">
                                    @if ($row->total_outstanding > 0)
                                        <a href="{{ route('production.sewing.reports.outstanding', [
                                            'operator_id' => $row->operator_id,
                                            'date_from' => $dateFrom,
                                            'date_to' => $dateTo,
                                        ]) }}"
                                            class="btn btn-outline-secondary btn-sm">
                                            View lines
                                        </a>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">
                                    No data found for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile actions hint --}}
            <div class="d-block d-md-none p-2 border-top muted">
                Tap an operator row and use the Outstanding Report to see line-level details.
            </div>
        </div>

    </div>
@endsection
