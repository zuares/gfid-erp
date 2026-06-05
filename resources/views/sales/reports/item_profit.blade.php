@extends('layouts.app')

@section('title', 'Laporan Laba Rugi per Item')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 3rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 26%,
                    #f9fafb 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            box-shadow:
                0 10px 24px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.03);
        }

        .table-sm th,
        .table-sm td {
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    @php
        $periodeLabel = $dateFrom && $dateTo ? $dateFrom . ' s/d ' . $dateTo : 'Semua periode (invoice posted)';
    @endphp

    <div class="page-wrap">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Laba Rugi per Item</h4>
                <small class="text-muted">
                    Periode: {{ $periodeLabel }}
                </small>
            </div>
        </div>

        {{-- Filter --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('sales.reports.item_profit') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                            value="{{ $dateFrom ?? '' }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                            value="{{ $dateTo ?? '' }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Customer (opsional)</label>
                        <select name="customer_id" class="form-select form-select-sm">
                            <option value="">-- Semua Customer --</option>
                            @foreach ($customers as $cus)
                                <option value="{{ $cus->id }}"
                                    {{ (string) $customerId === (string) $cus->id ? 'selected' : '' }}>
                                    {{ $cus->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            Tampilkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tabel hasil --}}
        <div class="card card-main">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 60px;">#</th>
                                <th>Item</th>
                                <th class="text-end">Qty Terjual</th>
                                <th class="text-end">Penjualan</th>
                                <th class="text-end">HPP / Unit</th>
                                <th class="text-end">Total HPP</th>
                                <th class="text-end">Margin / Unit</th>
                                <th class="text-end">Total Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $idx => $row)
                                <tr>
                                    <td class="text-center">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $row->item->code ?? '-' }} — {{ $row->item->name ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        {{ angka($row->qty, 0) }}
                                    </td>
                                    <td class="text-end">
                                        {{ angka($row->revenue, 0) }}
                                    </td>
                                    <td class="text-end">
                                        {{ angka($row->hpp_unit, 0) }}
                                    </td>
                                    <td class="text-end">
                                        {{ angka($row->hpp_total, 0) }}
                                    </td>
                                    <td class="text-end">
                                        {{ angka($row->margin_unit, 0) }}
                                    </td>
                                    <td class="text-end {{ $row->margin_total < 0 ? 'text-danger' : 'text-success' }}">
                                        {{ angka($row->margin_total, 0) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        Belum ada data penjualan (invoice posted) untuk filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        {{-- Footer total --}}
                        @if (isset($totals) && $rows->count() > 0)
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2" class="text-end">TOTAL</th>
                                    <th class="text-end">
                                        {{ angka($totals->qty, 0) }}
                                    </th>
                                    <th class="text-end">
                                        {{ angka($totals->revenue, 0) }}
                                    </th>
                                    <th class="text-end">
                                        {{-- Kosong: HPP / unit total kurang relevan di sini --}}
                                    </th>
                                    <th class="text-end">
                                        {{ angka($totals->hpp_total, 0) }}
                                    </th>
                                    <th class="text-end">
                                        {{-- Kosong: Margin / unit total kurang relevan --}}
                                    </th>
                                    <th class="text-end {{ $totals->margin_total < 0 ? 'text-danger' : 'text-success' }}">
                                        {{ angka($totals->margin_total, 0) }}
                                    </th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
