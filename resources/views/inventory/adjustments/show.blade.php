@extends('layouts.app')

@section('title', 'Detail Inventory Adjustment • ' . $adjustment->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(96, 165, 250, 0.14) 0,
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

        .badge-status--cancelled {
            background: rgba(248, 113, 113, 0.18);
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

        .diff-plus {
            color: #16a34a;
        }

        .diff-minus {
            color: #dc2626;
        }

        .badge-dir {
            font-size: .7rem;
            padding: .16rem .5rem;
            border-radius: 999px;
        }

        .badge-dir--in {
            background: rgba(22, 163, 74, 0.18);
            color: #15803d;
        }

        .badge-dir--out {
            background: rgba(248, 113, 113, 0.18);
            color: #b91c1c;
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
        }
    </style>
@endpush

@section('content')
    @php
        use Illuminate\Support\Facades\Route as RouteFacade;

        $statusClass = match ($adjustment->status) {
            'draft' => 'badge-status badge-status--draft',
            'pending' => 'badge-status badge-status--pending',
            'approved' => 'badge-status badge-status--approved',
            'cancelled' => 'badge-status badge-status--cancelled',
            default => 'badge-status badge-status--draft',
        };

        $sourceLabel = null;
        $sourceUrl = null;

        if ($adjustment->source_type === \App\Models\StockOpname::class && $adjustment->source_id) {
            $sourceLabel = 'Stock Opname #' . ($adjustment->source?->code ?? $adjustment->source_id);

            // Cek aman: hanya kalau route-nya ada
            if (RouteFacade::has('inventory.stock_opnames.show')) {
                $sourceUrl = route('inventory.stock_opnames.show', $adjustment->source_id);
            }
        } elseif ($adjustment->source_type) {
            $sourceLabel = class_basename($adjustment->source_type) . ' #' . $adjustment->source_id;
        }

        $totalIn = $adjustment->lines->where('direction', 'in')->sum('qty_change');
        $totalOut = $adjustment->lines->where('direction', 'out')->sum('qty_change');
    @endphp

    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-sm btn-link px-0 mb-1">
                    ← Kembali ke daftar
                </a>
                <h1 class="h5 mb-1">
                    Detail Inventory Adjustment • {{ $adjustment->code }}
                </h1>
                <p class="text-muted mb-0" style="font-size: .86rem;">
                    Penyesuaian stok gudang berdasarkan selisih atau koreksi manual.
                </p>
            </div>
            <div class="text-end">
                <div class="mb-2">
                    <span class="{{ $statusClass }}">{{ ucfirst($adjustment->status) }}</span>
                </div>
                <div class="text-muted" style="font-size: .8rem;">
                    Tanggal: {{ $adjustment->date?->format('d M Y') ?? '-' }}
                </div>
            </div>
        </div>

        <div class="card card-main mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="pill-label mb-1">Kode Dokumen</div>
                        <div class="fw-semibold text-mono">
                            {{ $adjustment->code }}
                        </div>

                        <div class="pill-label mt-3 mb-1">Gudang</div>
                        <div class="fw-semibold">
                            {{ $adjustment->warehouse?->code ?? '-' }}
                        </div>
                        <div class="text-muted" style="font-size: .86rem;">
                            {{ $adjustment->warehouse?->name }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="pill-label mb-1">Sumber Dokumen</div>
                        <div style="font-size: .9rem;">
                            @if ($sourceLabel)
                                @if ($sourceUrl)
                                    <a href="{{ $sourceUrl }}">{{ $sourceLabel }}</a>
                                @else
                                    {{ $sourceLabel }}
                                @endif
                            @else
                                <span class="text-muted">Manual / tidak terhubung</span>
                            @endif
                        </div>

                        <div class="pill-label mt-3 mb-1">Status</div>
                        <div>
                            <span class="{{ $statusClass }}">{{ ucfirst($adjustment->status) }}</span>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="pill-label mb-1">Dibuat Oleh</div>
                        <div>
                            {{ $adjustment->creator?->name ?? '-' }}<br>
                            <small class="text-muted">
                                {{ $adjustment->created_at?->format('d M Y H:i') }}
                            </small>
                        </div>

                        @if ($adjustment->approved_by)
                            <div class="pill-label mt-3 mb-1">Approved By</div>
                            <div>
                                {{ $adjustment->approver?->name ?? '-' }}<br>
                                <small class="text-muted">
                                    {{ $adjustment->approved_at?->format('d M Y H:i') }}
                                </small>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($adjustment->reason || $adjustment->notes)
                    <div class="mt-3 row g-3">
                        <div class="col-md-6">
                            @if ($adjustment->reason)
                                <div class="pill-label mb-1">Alasan</div>
                                <div style="font-size: .9rem;">
                                    {{ $adjustment->reason }}
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            @if ($adjustment->notes)
                                <div class="pill-label mb-1">Catatan</div>
                                <div style="font-size: .9rem;">
                                    {!! nl2br(e($adjustment->notes)) !!}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="mt-3 row g-3">
                    <div class="col-md-4">
                        <div class="pill-label mb-1">Ringkasan Qty</div>
                        <div style="font-size: .9rem;">
                            <div>
                                Masuk:
                                <span class="text-mono diff-plus">
                                    +{{ number_format($totalIn, 2) }}
                                </span>
                            </div>
                            <div>
                                Keluar:
                                <span class="text-mono diff-minus">
                                    -{{ number_format($totalOut, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="pill-label mb-1">Info Tambahan</div>
                        <div style="font-size: .86rem;" class="text-muted">
                            Dokumen ini mempengaruhi kartu stok gudang
                            <strong>{{ $adjustment->warehouse?->code }}</strong>.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-main">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">
                        Detail Baris Adjustment
                    </h2>
                    <div class="text-muted" style="font-size: .8rem;">
                        {{ $adjustment->lines->count() }} baris
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Item</th>
                                <th class="text-center">Arah</th>
                                <th class="text-end">Qty Perubahan</th>
                                <th class="text-end">Qty Sebelum</th>
                                <th class="text-end">Qty Sesudah</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($adjustment->lines as $index => $line)
                                @php
                                    $dirClass =
                                        $line->direction === 'in'
                                            ? 'badge-dir badge-dir--in'
                                            : 'badge-dir badge-dir--out';

                                    $qtyChangeText =
                                        $line->direction === 'in'
                                            ? '+' . number_format($line->qty_change, 2)
                                            : '-' . number_format($line->qty_change, 2);
                                @endphp
                                <tr>
                                    <td data-label="#">
                                        {{ $index + 1 }}
                                    </td>
                                    <td data-label="Item">
                                        <div class="fw-semibold">
                                            {{ $line->item?->code ?? '-' }}
                                        </div>
                                        <div class="text-muted" style="font-size: .82rem;">
                                            {{ $line->item?->name ?? '' }}
                                        </div>
                                    </td>
                                    <td data-label="Arah" class="text-center">
                                        <span class="{{ $dirClass }}">
                                            {{ $line->direction === 'in' ? 'Masuk' : 'Keluar' }}
                                        </span>
                                    </td>
                                    <td data-label="Qty perubahan"
                                        class="text-end text-mono {{ $line->direction === 'in' ? 'diff-plus' : 'diff-minus' }}">
                                        {{ $qtyChangeText }}
                                    </td>
                                    <td data-label="Qty sebelum" class="text-end text-mono">
                                        @if (!is_null($line->qty_before))
                                            {{ number_format($line->qty_before, 2) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td data-label="Qty sesudah" class="text-end text-mono">
                                        @if (!is_null($line->qty_after))
                                            {{ number_format($line->qty_after, 2) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td data-label="Catatan">
                                        <span style="font-size: .82rem;">
                                            {{ $line->notes }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-3">
                                        <span class="text-muted">
                                            Belum ada baris dalam dokumen ini.
                                        </span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection
