@extends('layouts.app')

@section('title', 'Laporan Pengiriman')

@push('head')
    <style>
        .shipment-report-page {
            min-height: 100vh;
        }

        .shipment-report-page .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: 1rem 1rem 3.5rem;
        }

        body[data-theme="light"] .shipment-report-page .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 24%,
                    #f9fafb 60%);
        }

        body[data-theme="dark"] .shipment-report-page .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.25) 0,
                    rgba(45, 212, 191, 0.18) 22%,
                    #020617 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .25);
            box-shadow:
                0 10px 28px rgba(15, 23, 42, .12),
                0 0 0 1px rgba(15, 23, 42, .05);
        }

        .card-section {
            padding: 1rem 1rem 0.75rem;
        }

        .card-section+.card-section {
            border-top: 1px solid rgba(148, 163, 184, .20);
        }

        .report-badge {
            font-size: .72rem;
            padding: .1rem .4rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .55);
            background: color-mix(in srgb, var(--card) 85%, var(--bg) 15%);
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .summary-number {
            font-size: 1.15rem;
            font-weight: 600;
        }

        .summary-label {
            font-size: .8rem;
            color: var(--muted);
        }

        .summary-card {
            background: color-mix(in srgb, var(--card) 94%, var(--bg) 6%);
            border-radius: 10px;
            padding: .65rem .75rem;
            border: 1px solid rgba(148, 163, 184, .38);
        }

        /* Tabel desktop */
        @media (min-width: 768px) {
            .table-report thead th {
                font-size: .75rem;
                text-transform: uppercase;
                letter-spacing: .06em;
                color: var(--muted);
                border-bottom: 1px solid rgba(148, 163, 184, .35);
            }

            .table-report tbody td {
                font-size: .86rem;
                vertical-align: middle;
            }

            .table-report tbody tr:nth-child(odd) {
                background: color-mix(in srgb, var(--card) 97%, var(--bg) 3%);
            }

            .table-report tbody tr:nth-child(even) {
                background: color-mix(in srgb, var(--card) 99%, var(--bg) 1%);
            }
        }

        /* Mobile list style */
        @media (max-width: 767.98px) {
            .report-table-desktop {
                display: none;
            }

            .report-mobile-item {
                background: color-mix(in srgb, var(--card) 96%, var(--bg) 4%);
                border-radius: 10px;
                border: 1px solid rgba(148, 163, 184, .35);
                padding: .65rem .7rem;
                font-size: .84rem;
            }

            .report-mobile-item+.report-mobile-item {
                margin-top: .55rem;
            }

            .report-mobile-label {
                font-size: .7rem;
                text-transform: uppercase;
                letter-spacing: .06em;
                color: var(--muted);
            }

            .report-mobile-value {
                font-size: .84rem;
            }
        }

        @media (min-width: 768px) {
            .report-mobile-list {
                display: none;
            }
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .1rem .5rem;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .status-draft {
            border: 1px dashed rgba(148, 163, 184, .85);
            color: rgba(148, 163, 184, 1);
            background: rgba(15, 23, 42, .02);
        }

        .status-submitted {
            border: 1px solid rgba(234, 179, 8, .9);
            color: rgba(234, 179, 8, 1);
            background: rgba(254, 249, 195, .16);
        }

        .status-posted {
            border: 1px solid rgba(22, 163, 74, .95);
            color: rgba(22, 163, 74, 1);
            background: rgba(220, 252, 231, .16);
        }
    </style>
@endpush

@section('content')
    <div class="shipment-report-page">
        <div class="page-wrap">

            <div class="mb-3 d-flex align-items-center justify-content-between gap-2">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h1 class="h5 mb-0">Laporan Pengiriman</h1>
                        <span class="report-badge">Sales • Shipment</span>
                    </div>
                </div>

                <div class="text-end d-none d-md-block">
                    <a href="{{ route('sales.shipments.index') }}" class="btn btn-sm btn-outline-secondary">
                        &larr; Daftar shipment
                    </a>
                </div>
            </div>

            <div class="card-main">

                {{-- FILTER --}}
                <div class="card-section">
                    <form method="GET" action="{{ route('sales.shipments.report') }}"
                        class="row g-2 g-md-3 align-items-end">
                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm mb-1">Dari</label>
                            <input type="date" name="date_from" class="form-control form-control-sm"
                                value="{{ $filters['date_from'] }}">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm mb-1">Sampai</label>
                            <input type="date" name="date_to" class="form-control form-control-sm"
                                value="{{ $filters['date_to'] }}">
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm mb-1">Store</label>
                            <select name="store_id" class="form-select form-select-sm">
                                <option value="">Semua store</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}"
                                        {{ $filters['store_id'] == $store->id ? 'selected' : '' }}>
                                        {{ $store->code }} — {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label form-label-sm mb-1">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ $filters['status'] === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-1 d-flex justify-content-md-end mt-1 mt-md-0">
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                Tampil
                            </button>
                        </div>
                    </form>
                </div>

                {{-- SUMMARY --}}
                <div class="card-section">
                    <div class="row g-2 g-md-3">
                        <div class="col-4 col-md-3">
                            <div class="summary-card">
                                <div class="summary-label">Shipment</div>
                                <div class="summary-number">
                                    {{ number_format($summary['total_shipments']) }}
                                </div>
                            </div>
                        </div>
                        <div class="col-4 col-md-3">
                            <div class="summary-card">
                                <div class="summary-label">Total qty</div>
                                <div class="summary-number">
                                    {{ number_format($summary['total_qty']) }}
                                </div>
                            </div>
                        </div>
                        <div class="col-4 col-md-4">
                            <div class="summary-card">
                                <div class="summary-label">Total HPP</div>
                                <div class="summary-number">
                                    Rp {{ number_format($summary['total_hpp'], 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TABLE / LIST --}}
                <div class="card-section">
                    @if ($rows->isEmpty())
                        <div class="text-center text-muted small py-3">
                            Tidak ada data untuk periode ini.
                        </div>
                    @else
                        {{-- Desktop table --}}
                        <div class="report-table-desktop">
                            <div class="table-responsive">
                                <table class="table table-sm table-report mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 130px;">Tanggal</th>
                                            <th style="width: 120px;">Kode</th>
                                            <th>Store</th>
                                            <th class="text-center" style="width: 80px;">Status</th>
                                            <th class="text-end" style="width: 90px;">Lines</th>
                                            <th class="text-end" style="width: 110px;">Qty</th>
                                            <th class="text-end" style="width: 160px;">Total HPP</th>
                                            <th style="width: 70px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rows as $row)
                                            @php
                                                /** @var \App\Models\Shipment $shipment */
                                                $shipment = $row->shipment;
                                                $store = $shipment->store;
                                                $statusClass = match ($shipment->status) {
                                                    'draft' => 'status-draft',
                                                    'submitted' => 'status-submitted',
                                                    'posted' => 'status-posted',
                                                    default => 'status-draft',
                                                };
                                            @endphp
                                            <tr>
                                                <td>{{ optional($shipment->date)->format('Y-m-d') }}</td>
                                                <td>{{ $shipment->code }}</td>
                                                <td>
                                                    @if ($store)
                                                        <div class="fw-semibold">{{ $store->code }}</div>
                                                        <div class="text-muted small">{{ $store->name }}</div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="status-pill {{ $statusClass }}">
                                                        {{ strtoupper($shipment->status) }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    {{ number_format($row->total_lines) }}
                                                </td>
                                                <td class="text-end">
                                                    {{ number_format($row->total_qty) }}
                                                </td>
                                                <td class="text-end">
                                                    Rp {{ number_format($row->total_hpp, 0, ',', '.') }}
                                                </td>
                                                <td class="text-end">
                                                    <a href="{{ route('sales.shipments.show', $shipment) }}"
                                                        class="btn btn-xs btn-outline-secondary">
                                                        Detail
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Mobile card list --}}
                        <div class="report-mobile-list">
                            @foreach ($rows as $row)
                                @php
                                    $shipment = $row->shipment;
                                    $store = $shipment->store;
                                    $statusClass = match ($shipment->status) {
                                        'draft' => 'status-draft',
                                        'submitted' => 'status-submitted',
                                        'posted' => 'status-posted',
                                        default => 'status-draft',
                                    };
                                @endphp
                                <div class="report-mobile-item">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div>
                                            <div class="fw-semibold">{{ $shipment->code }}</div>
                                            <div class="text-muted small">
                                                {{ optional($shipment->date)->format('Y-m-d') }}
                                            </div>
                                        </div>
                                        <span class="status-pill {{ $statusClass }}">
                                            {{ strtoupper($shipment->status) }}
                                        </span>
                                    </div>

                                    <div class="mb-1">
                                        <div class="report-mobile-label">Store</div>
                                        <div class="report-mobile-value">
                                            @if ($store)
                                                {{ $store->code }} &mdash; {{ $store->name }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between mt-1">
                                        <div>
                                            <div class="report-mobile-label">Lines</div>
                                            <div class="report-mobile-value">
                                                {{ number_format($row->total_lines) }}
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="report-mobile-label">Qty</div>
                                            <div class="report-mobile-value">
                                                {{ number_format($row->total_qty) }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <div>
                                            <div class="report-mobile-label">Total HPP</div>
                                            <div class="report-mobile-value">
                                                Rp {{ number_format($row->total_hpp, 0, ',', '.') }}
                                            </div>
                                        </div>
                                        <a href="{{ route('sales.shipments.show', $shipment) }}"
                                            class="btn btn-xs btn-outline-secondary">
                                            Detail
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
@endsection
