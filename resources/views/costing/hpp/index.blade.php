@extends('layouts.app')

@section('title', 'HPP (COGS) • Per Item')

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
            border: 1px solid rgba(148, 163, 184, 0.28);
            box-shadow:
                0 10px 24px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.03);
        }

        .table-wrap {
            margin-top: .75rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .20);
            overflow: hidden;
        }

        .table thead th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
            background: rgba(15, 23, 42, 0.02);
        }

        .text-mono {
            font-variant-numeric: tabular-nums;
        }

        .badge-hpp {
            font-size: .7rem;
            padding: .18rem .5rem;
            border-radius: 999px;
            font-weight: 600;
            background: rgba(34, 197, 94, 0.10);
            color: #15803d;
        }

        .badge-status-active,
        .badge-status-history {
            font-size: .7rem;
            padding: .18rem .6rem;
            border-radius: 999px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: .25rem;
        }

        .badge-status-active {
            background: rgba(34, 197, 94, 0.14);
            color: #15803d;
        }

        .badge-status-history {
            background: rgba(148, 163, 184, 0.14);
            color: #64748b;
            font-weight: 500;
        }

        .status-icon {
            font-size: .9rem;
            line-height: 1;
        }

        .btn-xs {
            --bs-btn-padding-y: .12rem;
            --bs-btn-padding-x: .4rem;
            --bs-btn-font-size: .72rem;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                border-bottom: 1px solid rgba(148, 163, 184, .25);
                padding: .35rem .75rem;
            }

            .table tbody tr:last-child {
                border-bottom: none;
            }

            .table tbody td {
                display: flex;
                justify-content: space-between;
                gap: .75rem;
                padding: .15rem 0;
                border-top: none;
                font-size: .85rem;
            }

            .table tbody td::before {
                content: attr(data-label);
                font-weight: 500;
                color: #64748b;
            }

            .table-wrap {
                border: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h5 mb-1">
                    HPP (COGS) • Per Item
                </h1>
                <p class="text-muted mb-0" style="font-size: .86rem;">
                    Generate HPP dari periode produksi & payroll, simpan sebagai snapshot per item, dan tandai HPP aktif
                    yang akan dipakai di modul Sales Invoice.
                </p>
            </div>
        </div>

        {{-- FORM GENERATE HPP --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <h2 class="h6 mb-2">
                    Generate HPP Baru
                </h2>
                <p class="text-muted mb-3" style="font-size: .8rem;">
                    Pilih item finished goods dan periode tanggal. Sistem akan membaca data Cutting + Payroll Cutting &
                    Sewing, lalu menyimpan snapshot HPP terbaru.
                </p>

                <form action="{{ route('costing.hpp.generate') }}" method="POST" class="row g-3 align-items-end">
                    @csrf

                    {{-- Item FG --}}
                    <div class="col-md-4">
                        <label class="form-label form-label-sm mb-1">
                            Item Finished Goods <span class="text-danger">*</span>
                        </label>
                        <select name="item_id" class="form-select form-select-sm" required>
                            <option value="">Pilih item…</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}"
                                    {{ old('item_id', $filters['item_id'] ?? null) == $item->id ? 'selected' : '' }}>
                                    {{ $item->code }}
                                </option>
                            @endforeach
                        </select>
                        @error('item_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Gudang (opsional, misal WH-RTS) --}}
                    <div class="col-md-3">
                        <label class="form-label form-label-sm mb-1">
                            Gudang (opsional)
                        </label>
                        <select name="warehouse_id" class="form-select form-select-sm">
                            <option value="">Tanpa gudang</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}"
                                    {{ old('warehouse_id', $filters['warehouse_id'] ?? null) == $wh->id ? 'selected' : '' }}>
                                    {{ $wh->code }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Periode --}}
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">
                            Dari Tanggal <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                            value="{{ old('date_from', $filters['date_from'] ?? '') }}" required>
                        @error('date_from')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">
                            Sampai <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                            value="{{ old('date_to', $filters['date_to'] ?? '') }}" required>
                        @error('date_to')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Notes (opsional) --}}
                    <div class="col-12">
                        <label class="form-label form-label-sm mb-1">
                            Catatan (opsional)
                        </label>
                        <input type="text" name="notes" class="form-control form-control-sm"
                            placeholder="Contoh: HPP periode gaji minggu ke-1 Desember">
                    </div>

                    <div class="col-12 d-flex justify-content-between align-items-center mt-1 flex-wrap gap-2">
                        <div class="text-muted" style="font-size: .78rem;">
                            Snapshot HPP akan tersimpan dan bisa dikunci sebagai HPP aktif untuk perhitungan COGS &
                            penjualan.
                        </div>
                        <div>
                            <button type="submit" class="btn btn-sm btn-primary">
                                Generate HPP dari Periode Ini
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- FILTER & HISTORY SNAPSHOT --}}
        <div class="card card-main">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <h2 class="h6 mb-0">
                        History HPP • Snapshot per Item
                    </h2>
                    @if (method_exists($snapshots, 'total'))
                        <span class="text-muted" style="font-size: .8rem;">
                            {{ $snapshots->total() }} snapshot
                        </span>
                    @endif
                </div>

                {{-- Filter --}}
                <form method="GET" class="row g-2 align-items-end mb-2">
                    <div class="col-md-4">
                        <label class="form-label form-label-sm mb-1">Item</label>
                        <select name="item_id" class="form-select form-select-sm">
                            <option value="">Semua item</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}"
                                    {{ (string) $item->id === (string) ($filters['item_id'] ?? '') ? 'selected' : '' }}>
                                    {{ $item->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label form-label-sm mb-1">Gudang</label>
                        <select name="warehouse_id" class="form-select form-select-sm">
                            <option value="">Semua gudang</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}"
                                    {{ (string) $wh->id === (string) ($filters['warehouse_id'] ?? '') ? 'selected' : '' }}>
                                    {{ $wh->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Dari</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                            value="{{ $filters['date_from'] ?? '' }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Sampai</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                            value="{{ $filters['date_to'] ?? '' }}">
                    </div>

                    <div class="col-md-1 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                            Filter
                        </button>
                    </div>
                </form>

                {{-- Tabel --}}
                <div class="table-wrap">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Tanggal</th>
                                <th>Item</th>
                                <th>Gudang</th>
                                <th class="text-end">RM</th>
                                <th class="text-end">Cutting</th>
                                <th class="text-end">Sewing</th>
                                <th class="text-end">Finishing</th>
                                <th class="text-end">Packaging</th>
                                <th class="text-end">Overhead</th>
                                <th class="text-end">Total HPP</th>
                                <th>Status</th>
                                <th style="width: 130px;">Aksi</th>
                                <th>Pembuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($snapshots as $index => $snap)
                                <tr>
                                    <td data-label="#">
                                        {{ ($snapshots->currentPage() - 1) * $snapshots->perPage() + $index + 1 }}
                                    </td>

                                    <td data-label="Tanggal" class="text-mono">
                                        {{ id_date($snap->snapshot_date) }}
                                    </td>

                                    <td data-label="Item">
                                        {{ $snap->item?->code ?? '-' }}
                                    </td>

                                    <td data-label="Gudang">
                                        {{ $snap->warehouse?->code ?? '-' }}
                                    </td>

                                    <td data-label="RM" class="text-end text-mono">
                                        {{ number_format($snap->rm_unit_cost ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td data-label="Cutting" class="text-end text-mono">
                                        {{ number_format($snap->cutting_unit_cost ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td data-label="Sewing" class="text-end text-mono">
                                        {{ number_format($snap->sewing_unit_cost ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td data-label="Finishing" class="text-end text-mono">
                                        {{ number_format($snap->finishing_unit_cost ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td data-label="Packaging" class="text-end text-mono">
                                        {{ number_format($snap->packaging_unit_cost ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td data-label="Overhead" class="text-end text-mono">
                                        {{ number_format($snap->overhead_unit_cost ?? 0, 2, ',', '.') }}
                                    </td>

                                    @php
                                        $totalHpp =
                                            $snap->total_unit_cost ??
                                            ($snap->rm_unit_cost ?? 0) +
                                                ($snap->cutting_unit_cost ?? 0) +
                                                ($snap->sewing_unit_cost ?? 0) +
                                                ($snap->finishing_unit_cost ?? 0) +
                                                ($snap->packaging_unit_cost ?? 0) +
                                                ($snap->overhead_unit_cost ?? 0);
                                    @endphp

                                    <td data-label="Total HPP" class="text-end text-mono">
                                        <span class="badge-hpp">
                                            {{ number_format($totalHpp, 2, ',', '.') }}
                                        </span>
                                    </td>

                                    <td data-label="Status">
                                        @if ($snap->is_active)
                                            <span class="badge-status-active"
                                                title="HPP aktif ini yang akan dipakai di modul Sales Invoice">
                                                <span class="status-icon" aria-hidden="true">✔</span>
                                                <span>HPP Aktif</span>
                                            </span>
                                        @else
                                            <span class="badge-status-history" title="Snapshot HPP (history)">
                                                <span class="status-icon" aria-hidden="true">⏱</span>
                                                <span>History</span>
                                            </span>
                                        @endif
                                    </td>

                                    <td data-label="Aksi">
                                        @if (!$snap->is_active)
                                            <form action="{{ route('costing.hpp.set_active', $snap) }}" method="POST"
                                                onsubmit="return confirm('Set snapshot ini sebagai HPP aktif untuk item ini?');">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-outline-success w-100">
                                                    Jadikan HPP Aktif
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-success fw-semibold" style="font-size: .8rem;">
                                                Dipakai di Sales
                                            </span>
                                        @endif
                                    </td>

                                    <td data-label="Pembuat">
                                        {{ $snap->creator?->name ?? '-' }}<br>
                                        <small class="text-muted">
                                            {{ id_datetime($snap->created_at) }}
                                        </small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="text-center py-3">
                                        <span class="text-muted">
                                            Belum ada snapshot HPP yang tersimpan.
                                        </span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($snapshots, 'links'))
                    <div class="mt-3">
                        {{ $snapshots->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
