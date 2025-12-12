{{-- resources/views/inventory/stock_opnames/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Stock Opname • ' . $opname->code)

@php
    use App\Models\StockOpname;
    use App\Models\InventoryAdjustment;

    $isOpening = method_exists($opname, 'isOpening')
        ? $opname->isOpening()
        : $opname->type === StockOpname::TYPE_OPENING;

    /**
     * Ambil adjustment yang sumbernya SO ini.
     * - Jika controller sudah mengirim $adjustment, pakai itu
     * - Kalau belum, query ringan di view (1x) biar tetap jalan
     */
    $adjustment =
        $adjustment ??
        InventoryAdjustment::query()
            ->where('source_type', StockOpname::class)
            ->where('source_id', $opname->id)
            ->latest('id')
            ->first();

    // Ringkasan qty & nilai selisih
    $totalPlusQty = 0;
    $totalMinusQty = 0; // negatif
    $totalPlusValue = 0;
    $totalMinusValue = 0; // negatif

    foreach ($opname->lines as $line) {
        $diff = (float) $line->difference; // accessor model
        $unitCost = (float) $line->effective_unit_cost; // accessor model
        $diffValue = (float) $line->difference_value; // accessor model

        if (abs($diff) < 0.0000001) {
            continue;
        }

        if ($diff > 0) {
            $totalPlusQty += $diff;
            if ($unitCost > 0 && $diffValue !== 0.0) {
                $totalPlusValue += $diffValue;
            }
        } elseif ($diff < 0) {
            $totalMinusQty += $diff; // tetap negatif
            if ($unitCost > 0 && $diffValue !== 0.0) {
                $totalMinusValue += $diffValue; // negatif
            }
        }
    }

    $totalLines = $opname->lines->count();
    $countedLines = $opname->lines->whereNotNull('physical_qty')->count();

    $statusClass = match ($opname->status) {
        'draft' => 'badge-status badge-status--draft',
        'counting' => 'badge-status badge-status--counting',
        'reviewed' => 'badge-status badge-status--reviewed',
        'finalized' => 'badge-status badge-status--finalized',
        default => 'badge-status badge-status--draft',
    };
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
                    rgba(129, 140, 248, 0.14) 0,
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

        .card-section {
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .24);
        }

        .badge-status {
            font-size: .7rem;
            padding: .18rem .5rem;
            border-radius: 999px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
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
            white-space: nowrap;
        }

        .diff-plus {
            color: #16a34a;
        }

        .diff-minus {
            color: #dc2626;
        }

        .badge-counted {
            font-size: .7rem;
            padding: .18rem .5rem;
            border-radius: 999px;
        }

        .badge-counted--yes {
            background: rgba(22, 163, 74, 0.18);
            color: #15803d;
        }

        .badge-counted--no {
            background: rgba(148, 163, 184, 0.2);
            color: #475569;
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
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="{{ route('inventory.stock_opnames.index') }}" class="btn btn-sm btn-link px-0 mb-1">
                    ← Kembali ke daftar
                </a>

                <h1 class="h5 mb-1">
                    Detail Stock Opname • {{ $opname->code }}
                    @if ($isOpening)
                        <span class="badge bg-soft-primary ms-1" style="font-size:.7rem;">Opening Balance</span>
                    @endif

                    @if ($adjustment)
                        <span class="badge bg-soft-secondary ms-1" style="font-size:.7rem;">
                            Adjustment: {{ $adjustment->code }}
                        </span>
                    @endif
                </h1>

                <p class="text-muted mb-0" style="font-size: .86rem;">
                    Ringkasan sesi opname dan perbandingan stok sistem vs fisik (qty &amp; nilai Rupiah).
                </p>
            </div>

            <div class="text-end">
                <div class="mb-2">
                    <span class="{{ $statusClass }}">{{ ucfirst($opname->status) }}</span>
                </div>

                @if (in_array($opname->status, ['draft', 'counting']))
                    <a href="{{ route('inventory.stock_opnames.edit', $opname) }}"
                        class="btn btn-sm btn-outline-primary mb-1">
                        Lanjut Counting
                    </a>
                @endif

                @if ($opname->status === 'reviewed')
                    <form action="{{ route('inventory.stock_opnames.finalize', $opname) }}" method="POST"
                        onsubmit="return confirm('Yakin finalize stock opname ini? Stok gudang akan dikoreksi sesuai hasil fisik.');">
                        @csrf
                        <input type="hidden" name="reason" value="Stock Opname {{ $opname->code }}">
                        <button type="submit" class="btn btn-sm btn-success">
                            @if ($isOpening)
                                Finalize Opening Balance
                            @else
                                Finalize &amp; Buat Adjustment
                            @endif
                        </button>
                    </form>
                @elseif($opname->status === 'finalized')
                    @if ($adjustment)
                        <a href="{{ route('inventory.adjustments.show', $adjustment) }}"
                            class="btn btn-sm btn-outline-secondary">
                            Buka Adjustment →
                        </a>
                    @else
                        <div class="text-muted" style="font-size: .8rem;">
                            Dokumen sudah difinalkan.
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- CARD HEADER --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="pill-label mb-1">Kode Dokumen</div>
                        <div class="fw-semibold text-mono">
                            {{ $opname->code }}
                        </div>

                        <div class="pill-label mt-3 mb-1">Tanggal Opname</div>
                        <div>
                            {{ $opname->date?->format('d M Y') ?? '-' }}
                        </div>

                        @if ($adjustment)
                            <div class="pill-label mt-3 mb-1">Adjustment</div>
                            <div>
                                <a href="{{ route('inventory.adjustments.show', $adjustment) }}"
                                    class="text-decoration-none">
                                    {{ $adjustment->code }}
                                </a>
                                <div class="text-muted" style="font-size:.82rem;">
                                    Status: {{ ucfirst($adjustment->status) }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <div class="pill-label mb-1">Gudang</div>
                        <div class="fw-semibold">
                            {{ $opname->warehouse?->code ?? '-' }}
                        </div>
                        <div class="text-muted" style="font-size: .86rem;">
                            {{ $opname->warehouse?->name }}
                        </div>

                        <div class="pill-label mt-3 mb-1">Status</div>
                        <div>
                            <span class="{{ $statusClass }}">{{ ucfirst($opname->status) }}</span>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="pill-label mb-1">Dibuat Oleh</div>
                        <div>
                            {{ $opname->creator?->name ?? '-' }}<br>
                            <small class="text-muted">
                                {{ $opname->created_at?->format('d M Y H:i') }}
                            </small>
                        </div>

                        @if ($opname->finalized_by)
                            <div class="pill-label mt-3 mb-1">Finalized</div>
                            <div>
                                {{ $opname->finalizer?->name ?? '-' }}<br>
                                <small class="text-muted">
                                    {{ $opname->finalized_at?->format('d M Y H:i') }}
                                </small>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($opname->notes)
                    <div class="card card-section mt-3">
                        <div class="card-body py-2">
                            <div class="pill-label mb-1">Catatan</div>
                            <div style="font-size: .9rem;">
                                {!! nl2br(e($opname->notes)) !!}
                            </div>
                        </div>
                    </div>
                @endif

                {{-- SUMMARY --}}
                <div class="card card-section mt-3">
                    <div class="card-body py-2">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="pill-label mb-1">Ringkasan Selisih (Qty)</div>
                                <div style="font-size: .88rem;">
                                    <div>
                                        Lebih:
                                        <span class="text-mono diff-plus">
                                            +{{ number_format($totalPlusQty, 2) }}
                                        </span>
                                    </div>
                                    <div>
                                        Kurang:
                                        <span class="text-mono diff-minus">
                                            {{ number_format($totalMinusQty, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="pill-label mb-1">Ringkasan Nilai Selisih (Rp)</div>
                                <div style="font-size: .88rem;">
                                    <div>
                                        Lebih:
                                        <span class="text-mono diff-plus">
                                            +Rp {{ number_format($totalPlusValue, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div>
                                        Kurang:
                                        <span class="text-mono diff-minus">
                                            Rp
                                            {{ $totalMinusValue < 0 ? '-' : '' }}{{ number_format(abs($totalMinusValue), 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="pill-label mb-1">Status Counting</div>
                                <div style="font-size: .88rem;">
                                    {{ $countedLines }} / {{ $totalLines }} item sudah diinput.
                                    @if ($opname->status === 'counting')
                                        <br>
                                        <span class="text-muted">
                                            Lanjutkan pengisian di halaman counting sebelum menandai sebagai reviewed.
                                        </span>
                                    @elseif($opname->status === 'reviewed')
                                        <br>
                                        <span class="text-muted">
                                            Data sudah ready untuk finalisasi.
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- TABLE DETAIL --}}
        <div class="card card-main">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">
                        Detail Per Item
                    </h2>
                    <div class="text-muted" style="font-size: .8rem;">
                        {{ $countedLines }} / {{ $totalLines }} item sudah dihitung
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Item</th>
                                <th class="text-end">Qty Sistem</th>
                                <th class="text-end">Qty Fisik</th>
                                <th class="text-end">Selisih Qty</th>
                                <th class="text-end">HPP / Unit</th>
                                <th class="text-end">Nilai Selisih (Rp)</th>
                                <th class="text-center">Status Hitung</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($opname->lines as $index => $line)
                                @php
                                    $system = (float) ($line->system_qty ?? 0);
                                    $physical = (float) ($line->physical_qty ?? 0);
                                    $diff = (float) $line->difference;

                                    $diffClass = $diff > 0 ? 'diff-plus' : ($diff < 0 ? 'diff-minus' : '');
                                    $diffText = $diff > 0 ? '+' . number_format($diff, 2) : number_format($diff, 2);

                                    $counted = !is_null($line->physical_qty) || $line->is_counted;
                                    $countedClass = $counted
                                        ? 'badge-counted badge-counted--yes'
                                        : 'badge-counted badge-counted--no';

                                    $unitCost = (float) $line->effective_unit_cost;
                                    $diffValue = (float) $line->difference_value;
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

                                    <td data-label="Qty sistem" class="text-end text-mono">
                                        {{ number_format($system, 2) }}
                                    </td>

                                    <td data-label="Qty fisik" class="text-end text-mono">
                                        @if (!is_null($line->physical_qty))
                                            {{ number_format($physical, 2) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td data-label="Selisih qty" class="text-end text-mono {{ $diffClass }}">
                                        @if (!is_null($line->physical_qty) && abs($diff) > 0.0000001)
                                            {{ $diffText }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td data-label="HPP / Unit" class="text-end text-mono">
                                        @if ($unitCost > 0)
                                            {{ number_format($unitCost, 2) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td data-label="Nilai selisih (Rp)" class="text-end text-mono {{ $diffClass }}">
                                        @if (!is_null($line->physical_qty) && $unitCost > 0 && abs($diff) > 0.0000001)
                                            {{ $diff > 0 ? '+' : '-' }}Rp
                                            {{ number_format(abs($diffValue), 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td data-label="Status hitung" class="text-center">
                                        <span class="{{ $countedClass }}">
                                            {{ $counted ? 'Sudah' : 'Belum' }}
                                        </span>
                                    </td>

                                    <td data-label="Catatan">
                                        <span style="font-size: .82rem;">
                                            {{ $line->notes }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-3">
                                        <span class="text-muted">
                                            Belum ada item dalam sesi opname ini.
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
