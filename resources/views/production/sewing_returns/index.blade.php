@extends('layouts.app')

@section('title', 'Produksi • Sewing Return')

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
                    <h1 class="h5 mb-1">Sewing Return</h1>
                    <div class="help">
                        Daftar hasil jahit yang sudah diposting dari WIP Sewing ke WIP Finishing / REJECT.
                    </div>
                </div>

                <a href="{{ route('production.sewing_pickups.index') }}" class="btn btn-sm btn-outline-secondary">
                    &larr; Kembali ke Sewing Pickup
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
                    <a href="{{ route('production.sewing_returns.index') }}" class="btn btn-sm btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- FLASH MESSAGE --}}
        @if (session('success'))
            <div class="alert alert-success py-2">
                {{ session('success') }}
            </div>
        @endif

        {{-- TABLE --}}
        <div class="card p-3">
            <h2 class="h6 mb-2">Daftar Sewing Return</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 130px;">Code</th>
                            <th style="width: 100px;">Tanggal</th>
                            <th style="width: 160px;">Gudang Sewing</th>
                            <th style="width: 170px;">Operator Jahit</th>
                            <th style="width: 150px;">Lines</th>
                            <th style="width: 150px;">Total OK / Reject</th>
                            <th style="width: 110px;">Status</th>
                            <th style="width: 90px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($returns as $return)
                            @php
                                $totalLines = $return->lines->count();
                                $totalOk = $return->lines->sum('qty_ok');
                                $totalReject = $return->lines->sum('qty_reject');

                                $statusMap = [
                                    'draft' => ['label' => 'DRAFT', 'class' => 'secondary'],
                                    'posted' => ['label' => 'POSTED', 'class' => 'primary'],
                                    'closed' => ['label' => 'CLOSED', 'class' => 'success'],
                                ];

                                $cfg = $statusMap[$return->status] ?? [
                                    'label' => strtoupper($return->status ?? '-'),
                                    'class' => 'secondary',
                                ];
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration + ($returns->currentPage() - 1) * $returns->perPage() }}</td>
                                <td>{{ $return->code }}</td>
                                <td>{{ $return->date?->format('Y-m-d') ?? $return->date }}</td>
                                <td>
                                    {{ $return->warehouse?->code ?? '-' }}
                                    @if ($return->warehouse)
                                        <span class="badge-soft bg-light border text-muted">
                                            {{ $return->warehouse->name }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($return->operator)
                                        {{ $return->operator->code }} — {{ $return->operator->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    {{ $totalLines }} baris
                                </td>
                                <td>
                                    OK: {{ number_format($totalOk, 2, ',', '.') }} /
                                    Rj: {{ number_format($totalReject, 2, ',', '.') }}
                                </td>
                                <td>
                                    <span class="badge bg-{{ $cfg['class'] }}">
                                        {{ $cfg['label'] }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('production.sewing_returns.show', $return) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted small">
                                    Belum ada Sewing Return.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($returns instanceof \Illuminate\Pagination\AbstractPaginator)
                <div class="mt-2">
                    {{ $returns->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
