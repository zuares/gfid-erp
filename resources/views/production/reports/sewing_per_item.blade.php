@extends('layouts.app')

@section('title', 'Laporan • Performa Jahit per Item')

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

        .table-wrap {
            overflow-x: auto;
        }
    </style>
@endpush

@section('content')
    @php $filters = $filters ?? []; @endphp

    <div class="page-wrap">
        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h1 class="h5 mb-1">Performa Jahit per Item Jadi</h1>
                    <div class="help">
                        Rekap total OK / Reject per SKU berdasarkan Sewing Return.
                        Klik jumlah Sewing Return untuk melihat transaksi detail.
                    </div>
                </div>
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

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Item Jadi</div>
                    <select name="item_id" class="form-select form-select-sm">
                        <option value="">-- Semua Item --</option>
                        @foreach ($items as $it)
                            <option value="{{ $it->id }}"
                                {{ isset($filters['item_id']) && (int) $filters['item_id'] === $it->id ? 'selected' : '' }}>
                                {{ $it->code }}{{ $it->name ? ' — ' . $it->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 d-flex justify-content-end align-items-end gap-2 mt-2">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <a href="{{ route('production.reports.sewing_per_item') }}"
                        class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="card p-3">
            <h2 class="h6 mb-2">Rekap per Item Jadi</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th style="width:180px;">Item</th>
                            <th style="width:130px;">Total OK</th>
                            <th style="width:130px;">Total Reject</th>
                            <th style="width:130px;">Total (OK+Rj)</th>
                            <th style="width:120px;">% Reject</th>
                            <th style="width:140px;"># Operator</th>
                            <th style="width:150px;"># Sewing Return</th>
                            <th style="width:110px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $ok = (float) $row->total_ok;
                                $rej = (float) $row->total_reject;
                                $total = $ok + $rej;
                                $rejPct = $total > 0 ? ($rej / $total) * 100 : 0;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    {{ $row->item_code }}
                                    @if ($row->item_name)
                                        <div class="small text-muted">
                                            {{ $row->item_name }}
                                        </div>
                                    @endif
                                </td>
                                <td>{{ number_format($ok, 2, ',', '.') }}</td>
                                <td class="text-danger">{{ number_format($rej, 2, ',', '.') }}</td>
                                <td>{{ number_format($total, 2, ',', '.') }}</td>
                                <td class="{{ $rejPct > 0 ? 'text-danger' : '' }}">
                                    {{ number_format($rejPct, 2, ',', '.') }}%
                                </td>
                                <td>{{ $row->total_operators }}</td>

                                {{-- 🔗 Drilldown: klik jumlah Sewing Return --}}
                                <td>
                                    <a href="{{ route('production.sewing_returns.index', [
                                        'date_from' => $filters['date_from'] ?? null,
                                        'date_to' => $filters['date_to'] ?? null,
                                        'operator_id' => $filters['operator_id'] ?? null,
                                        'item_id' => $row->finished_item_id ?? null, // optional kalau index support
                                    ]) }}"
                                        class="text-decoration-none">
                                        {{ $row->total_returns }}
                                    </a>
                                </td>

                                {{-- Tombol Detail (opsional, menuju daftar SWR) --}}
                                <td>
                                    <a href="{{ route('production.sewing_returns.index', [
                                        'date_from' => $filters['date_from'] ?? null,
                                        'date_to' => $filters['date_to'] ?? null,
                                        'operator_id' => $filters['operator_id'] ?? null,
                                        'item_id' => $row->finished_item_id ?? null,
                                    ]) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted small">
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
