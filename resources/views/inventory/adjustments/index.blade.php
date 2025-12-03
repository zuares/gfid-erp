{{-- resources/views/inventory/inventory_adjustments/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Inventory • Adjustments')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(96, 165, 250, 0.15) 0,
                    rgba(45, 212, 191, 0.10) 26%,
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

        .table-wrap {
            margin-top: .75rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .24);
            overflow: hidden;
        }

        .table thead th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
            background: rgba(15, 23, 42, 0.02);
        }

        .badge-status {
            font-size: .7rem;
            padding: .18rem .5rem;
            border-radius: 999px;
            font-weight: 600;
        }

        .badge-status--draft {
            background: rgba(148, 163, 184, 0.2);
            color: #475569;
        }

        .badge-status--pending {
            background: rgba(234, 179, 8, 0.18);
            color: #854d0e;
        }

        .badge-status--approved {
            background: rgba(22, 163, 74, 0.18);
            color: #15803d;
        }

        .badge-status--void {
            background: rgba(239, 68, 68, 0.18);
            color: #b91c1c;
        }

        .pill-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .table-wrap {
                border-radius: 10px;
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
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h5 mb-1">
                    Inventory • Adjustments
                </h1>
                <p class="text-muted mb-0" style="font-size: .86rem;">
                    Daftar dokumen penyesuaian stok (manual & dari stock opname).
                </p>
            </div>
            <div class="text-end">
                <a href="{{ route('inventory.adjustments.manual.create') }}" class="btn btn-sm btn-primary">
                    + Adjustment Manual
                </a>
            </div>
        </div>

        {{-- FILTER --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label form-label-sm mb-1">Gudang</label>
                        <select name="warehouse_id" class="form-select form-select-sm">
                            <option value="">Semua gudang</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}"
                                    {{ (string) $wh->id === (string) ($filters['warehouse_id'] ?? '') ? 'selected' : '' }}>
                                    {{ $wh->code }} — {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua status</option>
                            @foreach (['draft' => 'Draft', 'pending' => 'Pending', 'approved' => 'Approved', 'void' => 'Void'] as $value => $label)
                                <option value="{{ $value }}"
                                    {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>
                                    {{ $label }}
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

                    <div class="col-md-3">
                        <label class="form-label form-label-sm mb-1">Cari</label>
                        <div class="d-flex gap-2">
                            <input type="text" name="q" class="form-control form-control-sm"
                                placeholder="Kode / alasan / catatan" value="{{ $filters['q'] ?? '' }}">
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                Filter
                            </button>
                            <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-sm btn-outline-secondary">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABEL --}}
        <div class="card card-main">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">
                        Daftar Inventory Adjustment
                    </h2>
                    @if (method_exists($adjustments, 'total'))
                        <span class="text-muted" style="font-size: .8rem;">
                            {{ $adjustments->total() }} dokumen
                        </span>
                    @endif
                </div>

                <div class="table-wrap">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Kode</th>
                                <th>Tanggal</th>
                                <th>Gudang</th>
                                <th>Status</th>
                                <th class="text-center">Jumlah Item</th>
                                <th>Sumber</th>
                                <th>Dibuat / Disetujui</th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($adjustments as $index => $adj)
                                @php
                                    $statusClass = match ($adj->status) {
                                        'draft' => 'badge-status badge-status--draft',
                                        'pending' => 'badge-status badge-status--pending',
                                        'approved' => 'badge-status badge-status--approved',
                                        'void', 'cancelled' => 'badge-status badge-status--void',
                                        default => 'badge-status badge-status--draft',
                                    };

                                    $sourceLabel = 'Manual';
                                    if ($adj->source_type === \App\Models\StockOpname::class) {
                                        $sourceLabel = 'Stock Opname';
                                    }
                                @endphp
                                <tr>
                                    <td data-label="#">
                                        {{ ($adjustments->currentPage() - 1) * $adjustments->perPage() + $index + 1 }}
                                    </td>
                                    <td data-label="Kode">
                                        <span class="fw-semibold">{{ $adj->code }}</span><br>
                                        <small class="text-muted">
                                            {{ $adj->reason }}
                                        </small>
                                    </td>
                                    <td data-label="Tanggal">
                                        {{ optional($adj->date)->format('d M Y') ?? '-' }}
                                    </td>
                                    <td data-label="Gudang">
                                        {{ $adj->warehouse?->code ?? '-' }}<br>
                                        <small class="text-muted">
                                            {{ $adj->warehouse?->name }}
                                        </small>
                                    </td>
                                    <td data-label="Status">
                                        <span class="{{ $statusClass }}">
                                            {{ ucfirst($adj->status) }}
                                        </span>
                                    </td>
                                    <td data-label="Jumlah item" class="text-center">
                                        <span class="fw-semibold">
                                            {{ $adj->lines_count ?? ($adj->lines?->count() ?? 0) }}
                                        </span>
                                    </td>
                                    <td data-label="Sumber">
                                        <span class="badge bg-light text-muted" style="font-size: .72rem;">
                                            {{ $sourceLabel }}
                                        </span>
                                    </td>
                                    <td data-label="Dibuat / Disetujui">
                                        <small>
                                            {{ $adj->creator?->name ?? '-' }}<br>
                                            <span class="text-muted">
                                                {{ $adj->created_at?->format('d M Y H:i') }}
                                            </span>
                                            @if ($adj->approved_by)
                                                <br>
                                                <span class="text-success">
                                                    ✔ {{ $adj->approver?->name ?? '' }}
                                                </span>
                                            @endif
                                        </small>
                                    </td>
                                    <td data-label="Aksi" class="text-end">
                                        <a href="{{ route('inventory.adjustments.show', $adj) }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-3">
                                        <span class="text-muted">
                                            Belum ada dokumen adjustment.
                                        </span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($adjustments, 'links'))
                    <div class="mt-3">
                        {{ $adjustments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
