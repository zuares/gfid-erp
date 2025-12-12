{{-- resources/views/inventory/stock_opnames/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Inventory • Stock Opname')

@php
    use App\Models\StockOpname;
@endphp

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

        .badge-status--counting {
            background: rgba(59, 130, 246, 0.16);
            color: #1d4ed8;
        }

        .badge-status--reviewed {
            background: rgba(234, 179, 8, 0.18);
            color: #854d0e;
        }

        .badge-status--finalized {
            background: rgba(22, 163, 74, 0.18);
            color: #15803d;
        }

        .badge-type {
            font-size: .65rem;
            padding: .12rem .45rem;
            border-radius: 999px;
            font-weight: 600;
        }

        .badge-type--periodic {
            background: rgba(59, 130, 246, 0.15);
            color: #1d4ed8;
        }

        .badge-type--opening {
            background: rgba(249, 115, 22, 0.16);
            color: #c2410c;
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
                    Inventory • Stock Opname
                </h1>
                <p class="text-muted mb-0" style="font-size: .86rem;">
                    Daftar sesi stock opname per gudang. Gunakan untuk cek dan koreksi stok fisik.
                </p>
            </div>
            <div class="text-end">
                <a href="{{ route('inventory.stock_opnames.create') }}" class="btn btn-sm btn-primary">
                    + Sesi Opname Baru
                </a>
            </div>
        </div>

        <div class="card card-main mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('inventory.stock_opnames.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label form-label-sm mb-1">Gudang</label>
                        <select name="warehouse_id" class="form-select form-select-sm">
                            <option value="">Semua gudang</option>
                            @foreach ($warehouses ?? [] as $wh)
                                <option value="{{ $wh->id }}"
                                    {{ (string) $wh->id === (string) request('warehouse_id') ? 'selected' : '' }}>
                                    {{ $wh->code }} — {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label form-label-sm mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua status</option>
                            @php
                                $statusOptions = [
                                    StockOpname::STATUS_DRAFT => 'Draft',
                                    StockOpname::STATUS_COUNTING => 'Counting',
                                    StockOpname::STATUS_REVIEWED => 'Reviewed',
                                    StockOpname::STATUS_FINALIZED => 'Finalized',
                                ];
                            @endphp
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Tipe</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="">Semua tipe</option>
                            @php
                                $typeOptions = [
                                    StockOpname::TYPE_PERIODIC => 'Periodic',
                                    StockOpname::TYPE_OPENING => 'Opening',
                                ];
                            @endphp
                            @foreach ($typeOptions as $value => $label)
                                <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                            value="{{ request('date_from') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Sampai</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                            value="{{ request('date_to') }}">
                    </div>

                    <div class="col-12 col-md-2 d-flex gap-2 mt-2 mt-md-0">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                            Filter
                        </button>
                        <a href="{{ route('inventory.stock_opnames.index') }}" class="btn btn-sm btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-main">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">
                        Daftar Stock Opname
                    </h2>
                    @if (method_exists($opnames, 'total'))
                        <span class="text-muted" style="font-size: .8rem;">
                            {{ $opnames->total() }} dokumen
                        </span>
                    @endif
                </div>

                <div class="table-wrap">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Kode</th>
                                <th>Tipe</th>
                                <th>Tanggal</th>
                                <th>Gudang</th>
                                <th>Status</th>
                                <th class="text-center">Jumlah Item</th>
                                <th>Dibuat Oleh</th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($opnames as $index => $opname)
                                @php
                                    $statusClass = match ($opname->status) {
                                        StockOpname::STATUS_DRAFT => 'badge-status badge-status--draft',
                                        StockOpname::STATUS_COUNTING => 'badge-status badge-status--counting',
                                        StockOpname::STATUS_REVIEWED => 'badge-status badge-status--reviewed',
                                        StockOpname::STATUS_FINALIZED => 'badge-status badge-status--finalized',
                                        default => 'badge-status badge-status--draft',
                                    };

                                    $typeLabel = $opname->type === StockOpname::TYPE_OPENING ? 'Opening' : 'Periodic';
                                    $typeClass =
                                        $opname->type === StockOpname::TYPE_OPENING
                                            ? 'badge-type badge-type--opening'
                                            : 'badge-type badge-type--periodic';
                                @endphp
                                <tr>
                                    <td data-label="#">
                                        {{ ($opnames->currentPage() - 1) * $opnames->perPage() + $index + 1 }}
                                    </td>
                                    <td data-label="Kode">
                                        <span class="fw-semibold">{{ $opname->code }}</span>
                                    </td>
                                    <td data-label="Tipe">
                                        <span class="{{ $typeClass }}">
                                            {{ $typeLabel }}
                                        </span>
                                    </td>
                                    <td data-label="Tanggal">
                                        {{ $opname->date?->format('d M Y') ?? '-' }}
                                    </td>
                                    <td data-label="Gudang">
                                        {{ $opname->warehouse?->code ?? '-' }}<br>
                                        <small class="text-muted">
                                            {{ $opname->warehouse?->name }}
                                        </small>
                                    </td>
                                    <td data-label="Status">
                                        <span class="{{ $statusClass }}">
                                            {{ ucfirst($opname->status) }}
                                        </span>
                                    </td>
                                    <td data-label="Jumlah item" class="text-center">
                                        <span class="fw-semibold">
                                            {{ $opname->lines_count ?? ($opname->lines?->count() ?? 0) }}
                                        </span>
                                    </td>
                                    <td data-label="Dibuat oleh">
                                        {{ $opname->creator?->name ?? '-' }}<br>
                                        <small class="text-muted">
                                            {{ $opname->created_at?->format('d M Y H:i') }}
                                        </small>
                                    </td>
                                    <td data-label="Aksi" class="text-end">
                                        <a href="{{ route('inventory.stock_opnames.show', $opname) }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="mb-2 text-muted">
                                            Belum ada dokumen stock opname.
                                        </div>
                                        <a href="{{ route('inventory.stock_opnames.create') }}"
                                            class="btn btn-sm btn-primary">
                                            + Buat Sesi Opname Pertama
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($opnames, 'links'))
                    <div class="mt-3">
                        {{ $opnames->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
