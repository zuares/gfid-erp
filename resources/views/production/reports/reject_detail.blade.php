@extends('layouts.app')

@section('title', 'Laporan Reject Detail')

@push('head')
    <style>
        .mono {
            font-family: ui-monospace, Menlo, Consolas;
            font-variant-numeric: tabular-nums;
        }

        .table-wrap {
            overflow-x: auto;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h5">Reject Detail</h1>
        </div>

        {{-- FILTER --}}
        <form method="get" class="card p-3 mb-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control" value="{{ $filters['from'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sampai</label>
                    <input type="date" name="to" class="form-control" value="{{ $filters['to'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Operator</label>
                    <select name="operator_id" class="form-select">
                        <option value="">— semua —</option>
                        @foreach ($operators as $op)
                            <option value="{{ $op->id }}" {{ $filters['operator_id'] == $op->id ? 'selected' : '' }}>
                                {{ $op->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>

        {{-- TABLE --}}
        <div class="card p-3">
            <div class="table-wrap">
                <table class="table table-sm mono align-middle">
                    <thead>
                        <tr>
                            <th>Tgl</th>
                            <th>Stage</th>
                            <th>Item</th>
                            <th>LOT</th>
                            <th>OK</th>
                            <th>Reject</th>
                            <th>Operator</th>
                            <th>Catatan</th>
                            <th>Ref</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->date)->format('Y-m-d') }}</td>

                                <td>
                                    @if ($row->stage === 'cutting')
                                        <span class="badge bg-warning text-dark">Cutting</span>
                                    @else
                                        <span class="badge bg-info text-dark">Sewing</span>
                                    @endif
                                </td>

                                <td>{{ $row->item_code }}</td>

                                <td>
                                    <span class="badge bg-secondary">{{ $row->lot_code }}</span>
                                    <div class="small text-muted">{{ $row->lot_item_name }}</div>
                                </td>

                                <td>{{ number_format($row->qty_ok, 2, ',', '.') }}</td>
                                <td class="text-danger fw-bold">{{ number_format($row->qty_reject, 2, ',', '.') }}</td>
                                <td>{{ $row->operator_name }}</td>
                                <td>{{ $row->notes }}</td>

                                {{-- 🔗 DRILLDOWN --}}
                                <td>
                                    <a href="{{ $row->link_url }}" class="text-decoration-none fw-semibold">
                                        {{ $row->link_code }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted small">
                                    Tidak ada data reject.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
