@extends('layouts.app')

@section('title', 'Laporan Laba Rugi per Channel')

@push('head')
    <style>
        .card-report {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .35);
        }
    </style>
@endpush

@section('content')
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Laporan Laba Rugi per Channel (Store)</h4>
                <div class="text-muted small mt-1">
                    @if ($filters['date_from'] || $filters['date_to'])
                        Periode:
                        {{ $filters['date_from'] ? id_date($filters['date_from']) : 'Awal' }}
                        s/d
                        {{ $filters['date_to'] ? id_date($filters['date_to']) : 'Akhir' }}
                    @else
                        Periode: semua
                    @endif
                </div>
            </div>
        </div>

        {{-- Filter --}}
        <div class="card card-report mb-3">
            <div class="card-body">
                <form method="GET" class="row gy-2 gx-3 align-items-end">

                    {{-- Tanggal Dari --}}
                    <div class="col-md-3">
                        <label class="form-label mb-0">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                            value="{{ $filters['date_from'] }}">
                    </div>

                    {{-- Tanggal Sampai --}}
                    <div class="col-md-3">
                        <label class="form-label mb-0">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                            value="{{ $filters['date_to'] }}">
                    </div>

                    {{-- Store --}}
                    <div class="col-md-3">
                        <label class="form-label mb-0">Store</label>
                        <select name="store_id" class="form-select form-select-sm">
                            <option value="">– Semua –</option>
                            @foreach ($stores as $s)
                                <option value="{{ $s->id }}" @selected($filters['store_id'] == $s->id)>
                                    {{ $s->code }} — {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter shipped% --}}
                    <div class="col-md-2">
                        <label class="form-label mb-0">% Dikirim &lt;</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="shipped_below" min="0" max="100" step="1"
                                class="form-control form-control-sm" value="{{ $filters['shipped_below'] ?? '' }}"
                                placeholder="80">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    {{-- Tombol --}}
                    <div class="col-md-1 text-end">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100 mb-1">
                            Filter
                        </button>
                        <a href="{{ route('sales.reports.channel_profit') }}"
                            class="btn btn-sm btn-outline-secondary w-100">
                            Reset
                        </a>
                    </div>

                </form>

            </div>
        </div>

        {{-- Tabel --}}
        <div class="card card-report">
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr class="text-nowrap">
                            <th>Store</th>
                            <th class="text-end">Total Penjualan</th>
                            <th class="text-end">Total HPP</th>
                            <th class="text-end">Total Margin</th>
                            <th class="text-end">Margin %</th>
                            <th class="text-end">Invoice</th>
                            <th class="text-end">Sudah Dikirim</th>
                            <th class="text-end">% Dikirim</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="text-nowrap">
                                <td>{{ $row->store_name }}</td>
                                <td class="text-end">{{ angka($row->total_sales) }}</td>
                                <td class="text-end">{{ angka($row->total_hpp) }}</td>
                                <td class="text-end">{{ angka($row->total_margin) }}</td>
                                <td class="text-end">{{ angka($row->margin_percent, 2) }}%</td>
                                <td class="text-end">{{ $row->invoice_count }}</td>
                                <td class="text-end">{{ $row->invoice_shipped_count }}</td>
                                <td class="text-end">{{ angka($row->shipped_percent, 2) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>

                    @if ($rows->isNotEmpty())
                        <tfoot>
                            <tr class="fw-semibold table-light text-nowrap">
                                <td>GRAND TOTAL</td>
                                <td class="text-end">{{ angka($totals['sales']) }}</td>
                                <td class="text-end">{{ angka($totals['hpp']) }}</td>
                                <td class="text-end">{{ angka($totals['margin']) }}</td>
                                <td class="text-end">{{ angka($totals['margin_percent'], 2) }}%</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
