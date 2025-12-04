@extends('layouts.app')

@section('title', 'Laba Rugi per Channel Marketplace')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
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
                Laba Rugi per Channel Marketplace
            </h5>
            <a href="{{ route('sales.reports.item_profit') }}" class="btn btn-sm btn-outline-secondary">
                ← Laporan per Item
            </a>
        </div>

        {{-- FILTER --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('sales.reports.channel_profit') }}" class="row g-3 align-items-end">
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

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Warehouse (opsional)</label>
                        <select name="warehouse_id" class="form-select form-select-sm">
                            <option value="">- Semua Warehouse -</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected(request('warehouse_id') == $wh->id)>
                                    {{ $wh->code }} — {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
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

                    <div class="col-md-2 d-flex justify-content-end">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            Tampilkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- INFO HEADER --}}
        @php
            $totalInvoices = $summary['total_invoices'] ?? 0;
            $totalQty = $summary['total_qty'] ?? 0;
            $totalRevenue = $summary['total_revenue'] ?? 0;
            $totalHpp = $summary['total_hpp'] ?? 0;
            $totalMargin = $summary['total_margin'] ?? 0;
            $totalMarginPct = $totalRevenue > 0 ? ($totalMargin / $totalRevenue) * 100 : 0;
        @endphp

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
                    <div class="small text-muted">Warehouse</div>
                    <div class="fw-semibold">
                        @if ($selectedWarehouse)
                            {{ $selectedWarehouse->code }} — {{ $selectedWarehouse->name }}
                        @else
                            Semua Warehouse
                        @endif
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
                    <div class="small text-muted">Total Invoice</div>
                    <div class="fw-bold">
                        {{ $totalInvoices }}
                    </div>
                </div>

                <div>
                    <div class="small text-muted">Total Qty</div>
                    <div class="fw-bold">
                        {{ (float) $totalQty }}
                    </div>
                </div>

                <div>
                    <div class="small text-muted">Total Penjualan</div>
                    <div class="fw-bold">
                        Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                    </div>
                </div>

                <div>
                    <div class="small text-muted">Total HPP</div>
                    <div class="fw-bold">
                        Rp {{ number_format($totalHpp, 0, ',', '.') }}
                    </div>
                </div>

                <div>
                    <div class="small text-muted">Margin Kotor</div>
                    <div class="fw-bold {{ $totalMargin >= 0 ? 'text-success' : 'text-danger' }}">
                        Rp {{ number_format($totalMargin, 0, ',', '.') }}
                        <span class="small text-muted">
                            ({{ number_format($totalMarginPct, 1, ',', '.') }}%)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABEL PER CHANNEL --}}
        <div class="card card-main">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Channel</th>
                                <th style="width: 10%;" class="text-center"># Invoice</th>
                                <th style="width: 10%;" class="text-center">Total Qty</th>
                                <th style="width: 15%;" class="text-end">Penjualan</th>
                                <th style="width: 15%;" class="text-end">HPP</th>
                                <th style="width: 15%;" class="text-end">Margin</th>
                                <th style="width: 10%;" class="text-end">Margin%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                @php
                                    $store = $row->store;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            @if ($store)
                                                {{ $store->platform ?? '' }}{{ $store->platform ? ' - ' : '' }}{{ $store->name ?? 'Store #' . $store->id }}
                                            @else
                                                <span class="text-muted">[Store ID: {{ $row->store_id }}]</span>
                                            @endif
                                        </div>
                                        @if ($store?->code ?? false)
                                            <div class="small text-muted">
                                                {{ $store->code }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{ $row->invoice_count }}
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
                                    <td class="text-end {{ $row->margin_pct >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($row->margin_pct, 1, ',', '.') }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center small text-muted py-4">
                                        Tidak ada data invoice marketplace di periode / filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($rows->isNotEmpty())
                            <tfoot>
                                <tr class="table-light">
                                    <th class="text-end">TOTAL</th>
                                    <th class="text-center">{{ $totalInvoices }}</th>
                                    <th class="text-center">{{ (float) $totalQty }}</th>
                                    <th class="text-end">
                                        Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                                    </th>
                                    <th class="text-end">
                                        Rp {{ number_format($totalHpp, 0, ',', '.') }}
                                    </th>
                                    <th class="text-end fw-bold {{ $totalMargin >= 0 ? 'text-success' : 'text-danger' }}">
                                        Rp {{ number_format($totalMargin, 0, ',', '.') }}
                                    </th>
                                    <th class="text-end">
                                        {{ number_format($totalMarginPct, 1, ',', '.') }}%
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
