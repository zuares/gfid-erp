{{-- resources/views/sales/shipment_returns/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Sales • Retur Shipment')

@push('head')
    <style>
        :root {
            --ret-main: rgba(59, 130, 246, 1);
            --ret-soft: rgba(59, 130, 246, .12);
        }

        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: 1rem .9rem 4.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, .10) 0,
                    rgba(45, 212, 191, .08) 30%,
                    #f9fafb 70%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid var(--line);
            box-shadow:
                0 18px 45px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        body[data-theme="light"] .card {
            background: #ffffff;
        }

        .card-header-bar {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            justify-content: space-between;
            align-items: center;
            padding: .9rem 1rem;
            border-bottom: 1px solid rgba(148, 163, 184, .35);
        }

        .card-title-main {
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .btn-main {
            border-radius: 999px;
            border: 1px solid transparent;
            padding: .45rem 1rem;
            font-size: .88rem;
            font-weight: 500;
            background: linear-gradient(135deg, var(--ret-main), #22c55e);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .btn-main span.icon {
            font-size: 1rem;
        }

        .btn-main:hover {
            filter: brightness(1.05);
            color: #ffffff;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table.retur-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .9rem;
        }

        table.retur-table th,
        table.retur-table td {
            padding: .55rem .65rem;
            border-bottom: 1px solid rgba(148, 163, 184, .28);
            white-space: nowrap;
        }

        table.retur-table th {
            text-align: left;
            font-size: .78rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--muted);
        }

        table.retur-table tbody tr:hover {
            background: rgba(15, 23, 42, .03);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .badge-soft {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            font-size: .7rem;
            padding: .15rem .6rem;
            font-weight: 500;
        }

        .badge-draft {
            background: rgba(148, 163, 184, .18);
            color: #475569;
        }

        .badge-submitted {
            background: rgba(59, 130, 246, .18);
            color: #1d4ed8;
        }

        .badge-posted {
            background: rgba(16, 185, 129, .18);
            color: #047857;
        }

        .badge-linked-shipment {
            background: rgba(234, 179, 8, .14);
            color: #92400e;
        }

        .link-clean {
            color: inherit;
            text-decoration: none;
        }

        .link-clean:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .card-header-bar {
                align-items: flex-start;
            }

            table.retur-table th,
            table.retur-table td {
                padding: .5rem .45rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="card">
            <div class="card-header-bar">
                <div>
                    <div class="card-title-main">Retur Shipment</div>
                    <div style="font-size:.85rem; color:var(--muted); margin-top:.1rem;">
                        Daftar retur pengiriman dari store kembali ke WH-RTS.
                    </div>
                </div>

                <a href="{{ route('sales.shipment_returns.create') }}" class="btn-main">
                    <span class="icon">＋</span>
                    <span>Retur Shipment Baru</span>
                </a>
            </div>

            <div class="table-wrap">
                <table class="retur-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Tgl</th>
                            <th>Store</th>
                            <th>Shipment Asal</th>
                            <th>Status</th>
                            <th>Total Qty</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($returns as $ret)
                            <tr>
                                <td class="mono">
                                    <a href="{{ route('sales.shipment_returns.show', $ret) }}" class="link-clean">
                                        {{ $ret->code }}
                                    </a>
                                </td>
                                <td class="mono">{{ optional($ret->date)->format('d M Y') }}</td>
                                <td>
                                    @if ($ret->store)
                                        <div class="mono" style="font-size:.8rem;">
                                            {{ $ret->store->code ?? '-' }}
                                        </div>
                                        <div style="font-size:.8rem;">
                                            {{ $ret->store->name ?? '-' }}
                                        </div>
                                    @else
                                        <span style="color:var(--muted); font-size:.8rem;">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($ret->shipment)
                                        <span class="badge-soft badge-linked-shipment mono">
                                            {{ $ret->shipment->code }}
                                        </span>
                                    @else
                                        <span style="color:var(--muted); font-size:.78rem;">Manual</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $status = $ret->status;
                                    @endphp
                                    @if ($status === 'draft')
                                        <span class="badge-soft badge-draft">Draft</span>
                                    @elseif ($status === 'submitted')
                                        <span class="badge-soft badge-submitted">Submitted</span>
                                    @elseif ($status === 'posted')
                                        <span class="badge-soft badge-posted">Posted</span>
                                    @else
                                        <span class="badge-soft" style="background:rgba(148,163,184,.18); color:#475569;">
                                            {{ ucfirst($status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="mono">
                                    {{ number_format((int) $ret->total_qty) }}
                                </td>
                                <td style="text-align:right;">
                                    <a href="{{ route('sales.shipment_returns.show', $ret) }}"
                                        style="font-size:.8rem; color:var(--accent); text-decoration:none;">
                                        Detail →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding:1rem; font-size:.86rem; color:var(--muted);">
                                    Belum ada retur shipment.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($returns instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="px-3 py-2">
                    {{ $returns->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
