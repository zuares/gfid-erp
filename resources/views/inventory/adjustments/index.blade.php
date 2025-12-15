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
            white-space: nowrap;
        }

        .badge-status {
            font-size: .7rem;
            padding: .18rem .5rem;
            border-radius: 999px;
            font-weight: 600;
            white-space: nowrap;
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

        .text-mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }

        .diff-plus {
            color: #16a34a;
        }

        .diff-minus {
            color: #dc2626;
        }

        .mini-summary {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            margin-top: .35rem;
        }

        .mini-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .18rem .5rem;
            border-radius: 999px;
            font-size: .74rem;
            border: 1px solid rgba(148, 163, 184, .22);
            background: rgba(2, 6, 23, .02);
            color: rgba(51, 65, 85, 1);
            white-space: nowrap;
        }

        body[data-theme="dark"] .mini-pill {
            background: rgba(15, 23, 42, .35);
            border-color: rgba(51, 65, 85, .7);
            color: rgba(226, 232, 240, 1);
        }

        .mini-pill .dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            display: inline-block;
        }

        .dot-in {
            background: rgba(22, 163, 74, .75);
        }

        .dot-out {
            background: rgba(239, 68, 68, .75);
        }

        .dot-net {
            background: rgba(59, 130, 246, .75);
        }

        .dot-warn {
            background: rgba(234, 179, 8, .85);
        }

        .muted-help {
            font-size: .72rem;
            color: #64748b;
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
                padding: .45rem .75rem;
            }

            .table tbody tr:last-child {
                border-bottom: none;
            }

            .table tbody td {
                display: flex;
                justify-content: space-between;
                gap: .75rem;
                padding: .18rem 0;
                border-top: none;
                font-size: .88rem;
            }

            .table tbody td::before {
                content: attr(data-label);
                font-weight: 500;
                color: #64748b;
            }

            .mini-summary {
                justify-content: flex-start;
            }
        }
    </style>
@endpush

@section('content')
    @php
        // fallback aman kalau controller belum passing
        $adjustmentSummaries = $adjustmentSummaries ?? [];

        $sourceLabel = function ($adj) {
            if ($adj->source_type === \App\Models\StockOpname::class) {
                return 'Stock Opname';
            }
            return 'Manual';
        };
    @endphp

    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h5 mb-1">Inventory • Adjustments</h1>
                <p class="text-muted mb-0" style="font-size: .86rem;">
                    Penyesuaian stok (manual & dari stock opname). Nilai (Rp) hanya muncul setelah stok dieksekusi/approved.
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
                            <option value="all" {{ ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' }}>
                                Semua status
                            </option>
                            @foreach (['draft' => 'Draft', 'pending' => 'Pending', 'approved' => 'Approved', 'void' => 'Void'] as $value => $label)
                                <option value="{{ $value }}"
                                    {{ ($filters['status'] ?? 'all') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Sumber</label>
                        <select name="source_type" class="form-select form-select-sm">
                            <option value="all" {{ ($filters['source_type'] ?? 'all') === 'all' ? 'selected' : '' }}>
                                Semua sumber
                            </option>
                            <option value="stock_opname"
                                {{ ($filters['source_type'] ?? 'all') === 'stock_opname' ? 'selected' : '' }}>
                                Stock Opname
                            </option>
                            <option value="manual" {{ ($filters['source_type'] ?? 'all') === 'manual' ? 'selected' : '' }}>
                                Manual
                            </option>
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
                            <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
                            <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-sm btn-outline-secondary">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>

                <div class="mt-2 muted-help">
                    <span class="badge-status badge-status--pending me-1"></span>
                    Pending = stok belum dikoreksi (nilai Rp belum final).
                </div>
            </div>
        </div>

        {{-- TABEL --}}
        <div class="card card-main">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h2 class="h6 mb-0">Daftar Inventory Adjustment</h2>
                    <div class="text-end">
                        @if (method_exists($adjustments, 'total'))
                            <div class="text-muted" style="font-size: .8rem;">
                                {{ $adjustments->total() }} dokumen
                            </div>
                        @endif
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 44px;">#</th>
                                <th>Kode & Ringkasan</th>
                                <th>Tanggal</th>
                                <th>Gudang</th>
                                <th>Status</th>
                                <th class="text-center">Item</th>
                                <th>Sumber</th>
                                <th>Dibuat / Disetujui</th>
                                <th style="width: 88px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($adjustments as $index => $adj)
                                @php
                                    // dd($adj);
                                    $statusClass = match ($adj->status) {
                                        'draft' => 'badge-status badge-status--draft',
                                        'pending' => 'badge-status badge-status--pending',
                                        'approved' => 'badge-status badge-status--approved',
                                        'void', 'cancelled' => 'badge-status badge-status--void',
                                        default => 'badge-status badge-status--draft',
                                    };

                                    $s = $adjustmentSummaries[$adj->id] ?? [
                                        'in_qty' => 0,
                                        'out_qty' => 0,
                                        'in_value_fmt' => 'Rp 0',
                                        'out_value_fmt' => 'Rp 0',
                                        'net_value_fmt' => 'Rp 0',
                                        'net_value' => 0,
                                        'has_value' => false,
                                    ];

                                    $netClass = ((float) ($s['net_value'] ?? 0)) >= 0 ? 'diff-plus' : 'diff-minus';
                                    $source = $sourceLabel($adj);
                                @endphp

                                <tr>
                                    <td data-label="#">
                                        {{ ($adjustments->currentPage() - 1) * $adjustments->perPage() + $index + 1 }}
                                    </td>

                                    <td data-label="Kode">
                                        <div class="fw-semibold">{{ $adj->code }}</div>
                                        <div class="text-muted" style="font-size:.82rem;">
                                            {{ $adj->reason ?: '—' }}
                                        </div>

                                        {{-- Ringkasan cepat (Qty + Nilai) --}}
                                        <div class="mini-summary">
                                            <span class="mini-pill">
                                                <span class="dot dot-in"></span>
                                                <span
                                                    class="text-mono diff-plus">+{{ number_format((float) ($s['in_qty'] ?? 0), 2) }}</span>
                                            </span>
                                            <span class="mini-pill">
                                                <span class="dot dot-out"></span>
                                                <span
                                                    class="text-mono diff-minus">-{{ number_format((float) ($s['out_qty'] ?? 0), 2) }}</span>
                                            </span>

                                            @if (($s['has_value'] ?? false) && $adj->status === 'approved')
                                                <span class="mini-pill">
                                                    <span class="dot dot-net"></span>
                                                    <span
                                                        class="text-mono {{ $netClass }}">{{ $s['net_value_fmt'] }}</span>
                                                </span>
                                            @else
                                                <span class="mini-pill">
                                                    <span class="dot dot-warn"></span>
                                                    <span class="text-muted">Nilai belum final</span>
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td data-label="Tanggal">
                                        {{ optional($adj->date)->format('d M Y') ?? '-' }}
                                    </td>

                                    <td data-label="Gudang">
                                        {{ $adj->warehouse?->code ?? '-' }}<br>
                                        <small class="text-muted">{{ $adj->warehouse?->name }}</small>
                                    </td>

                                    <td data-label="Status">
                                        <span class="{{ $statusClass }}">{{ ucfirst($adj->status) }}</span>
                                    </td>

                                    <td data-label="Jumlah item" class="text-center">
                                        <span class="fw-semibold">
                                            {{ $adj->lines_count ?? ($adj->lines?->count() ?? 0) }}
                                        </span>
                                    </td>

                                    <td data-label="Sumber">
                                        <span class="badge bg-light text-muted" style="font-size: .72rem;">
                                            {{ $source }}
                                        </span>
                                    </td>

                                    <td data-label="Dibuat / Disetujui">
                                        <small>
                                            {{ $adj->creator?->name ?? '-' }}<br>
                                            <span class="text-muted">{{ $adj->created_at?->format('d M Y H:i') }}</span>
                                            @if ($adj->approved_by)
                                                <br>
                                                <span class="text-success">✔ {{ $adj->approver?->name ?? '' }}</span>
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
                                        <span class="text-muted">Belum ada dokumen adjustment.</span>
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
