@extends('layouts.app')

@section('title', 'Laporan Laba Rugi per Item')

@push('head')
    <style>
        .page-wrap {
            max-width: 1200px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
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
            border: 1px solid rgba(148, 163, 184, 0.30);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.03);
        }

        .table thead th {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-semibold">
                Laporan Laba Rugi per Item
            </h5>
            <a href="{{ route('sales.invoices.index') }}" class="btn btn-sm btn-outline-secondary">
                ← Ke Sales Invoices
            </a>
        </div>

        {{-- FILTER --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('sales.reports.item_profit') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                            value="{{ $dateFrom }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                            value="{{ $dateTo }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Customer (opsional)</label>
                        <select name="customer_id" class="form-select form-select-sm">
                            <option value="">- Semua Customer -</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>
                                    {{ $c->name }}{{ $c->phone ? ' (' . $c->phone . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2 justify-content-end">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            Tampilkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- INFO HEADER --}}
        <div class="card card-main mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between gap-3">
                <div>
                    <div class="small text-muted">Periode</div>
                    <div class="fw-semibold">
                        {{ $dateFrom ? id_date($dateFrom) : '-' }}
                        s/d
                        {{ $dateTo ? id_date($dateTo) : '-' }}
                    </div>
                </div>
                <div>
                    <div class="small text-muted">Customer</div>
                    <div class="fw-semibold">
                        @if ($selectedCustomer)
                            {{ $selectedCustomer->name }}
                            @if ($selectedCustomer->phone)
                                <span class="text-muted">({{ $selectedCustomer->phone }})</span>
                            @endif
                        @else
                            Semua Customer
                        @endif
                    </div>
                </div>
                <div>
                    <div class="small text-muted">Total Penjualan</div>
                    <div class="fw-bold">
                        Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}
                    </div>
                </div>
                <div>
                    <div class="small text-muted">Total HPP</div>
                    <div class="fw-bold">
                        Rp {{ number_format($summary['total_hpp'], 0, ',', '.') }}
                    </div>
                </div>
                <div>
                    @php
                        $totalMargin = $summary['total_margin'];
                        $totalRevenue = $summary['total_revenue'];
                        $marginPct = $totalRevenue > 0 ? ($totalMargin / $totalRevenue) * 100 : 0;
                    @endphp
                    <div class="small text-muted">Margin Kotor</div>
                    <div class="fw-bold {{ $totalMargin >= 0 ? 'text-success' : 'text-danger' }}">
                        Rp {{ number_format($totalMargin, 0, ',', '.') }}
                        <span class="small text-muted">
                            ({{ number_format($marginPct, 1, ',', '.') }}%)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABEL DETAIL PER ITEM --}}
        <div class="card card-main">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 26%;">Item</th>
                                <th style="width: 10%;" class="text-center">Total Qty</th>
                                <th style="width: 13%;" class="text-end">Penjualan</th>
                                <th style="width: 13%;" class="text-end">HPP</th>
                                <th style="width: 13%;" class="text-end">Margin</th>
                                <th style="width: 10%;" class="text-end">Avg Harga</th>
                                <th style="width: 10%;" class="text-end">Avg HPP</th>
                                <th style="width: 8%;" class="text-end">Margin%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $row->item?->code ?? 'ID:' . $row->item_id }}
                                            —
                                            {{ $row->item?->name ?? '-' }}
                                        </div>
                                        @if ($row->item?->type)
                                            <div class="small text-muted">
                                                {{ $row->item->type }}
                                                @if ($row->item?->category?->name)
                                                    • {{ $row->item->category->name }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{ (float) $row->total_qty }}
                                    </td>
                                    <td class="text-end">
                                        Rp {{ number_format($row->total_revenue, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        Rp {{ number_format($row->total_hpp, 0, ',', '.') }}
                                    </td>
                                    <td
                                        class="text-end fw-semibold {{ $row->total_margin >= 0 ? 'text-success' : 'text-danger' }}">
                                        Rp {{ number_format($row->total_margin, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        Rp {{ number_format($row->avg_price, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        Rp {{ number_format($row->avg_hpp, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end {{ $row->margin_pct >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($row->margin_pct, 1, ',', '.') }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center small text-muted py-4">
                                        Tidak ada data penjualan di periode & filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($rows->isNotEmpty())
                            <tfoot>
                                <tr class="table-light">
                                    <th class="text-end">TOTAL</th>
                                    <th class="text-center">
                                        {{ (float) $summary['total_qty'] }}
                                    </th>
                                    <th class="text-end">
                                        Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}
                                    </th>
                                    <th class="text-end">
                                        Rp {{ number_format($summary['total_hpp'], 0, ',', '.') }}
                                    </th>
                                    <th
                                        class="text-end fw-bold {{ $summary['total_margin'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        Rp {{ number_format($summary['total_margin'], 0, ',', '.') }}
                                    </th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
