@extends('layouts.app')

@section('title', 'Produksi • External Transfer ' . $transfer->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-family: ui-monospace, Menlo, SFMono-Regular;
            font-variant-numeric: tabular-nums;
        }

        .badge-status {
            border-radius: 999px;
            padding: .15rem .6rem;
            font-size: .7rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-status-sent {
            background: rgba(59, 130, 246, .15);
            color: #2563eb;
            border: 1px solid rgba(59, 130, 246, .45);
        }

        .badge-status-received {
            background: rgba(16, 185, 129, .15);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, .45);
        }

        .badge-status-cancelled {
            background: rgba(239, 68, 68, .15);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, .45);
        }

        .chip {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .1rem .6rem;
            font-size: .75rem;
            border: 1px solid var(--line);
            gap: .3rem;
        }

        .chip-warehouse {
            background: var(--panel);
        }

        .chip-wh-internal {
            background: rgba(16, 185, 129, .06);
            color: #047857;
        }

        .chip-wh-external {
            background: rgba(129, 140, 248, .08);
            color: #4f46e5;
        }

        .table-sm td,
        .table-sm th {
            padding-block: .4rem;
        }

        @media (max-width: 767.98px) {
            .table-wrap {
                overflow-x: auto;
            }
        }

        .summary-label {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .summary-value {
            font-size: 1rem;
            font-weight: 600;
        }
    </style>
@endpush

@section('content')
    @php
        $status = strtoupper($transfer->status ?? '');
        $badgeClass = match ($status) {
            'RECEIVED' => 'badge-status badge-status-received',
            'CANCELLED' => 'badge-status badge-status-cancelled',
            default => 'badge-status badge-status-sent',
        };

        $fromType = $transfer->fromWarehouse?->type;
        $fromExternal = $fromType === 'external';

        $toType = $transfer->toWarehouse?->type;
        $toExternal = $toType === 'external';

        // SUMMARY TOTAL
        $totalLines = $transfer->lines->count();
        $totalLots = $transfer->lines->pluck('lot_id')->filter()->unique()->count();
        $totalQty = (float) $transfer->lines->sum('qty');
    @endphp

    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">
                    External Transfer <span class="mono">{{ $transfer->code }}</span>
                </h4>
                <div class="text-muted small">
                    Tanggal:
                    <span class="mono">{{ $transfer->date?->format('Y-m-d') ?? '-' }}</span>
                </div>
                <div class="text-muted small">
                    Dibuat oleh:
                    <strong>{{ $transfer->creator?->name ?? '-' }}</strong>
                    <span class="mono">
                        ({{ $transfer->created_at?->format('Y-m-d H:i') }})
                    </span>
                </div>
            </div>
            <div class="text-end">
                <span class="{{ $badgeClass }} mono mb-2">
                    {{ $status ?: '-' }}
                </span>
                <div class="mt-2 d-flex flex-column gap-1">
                    @if ($transfer->process === 'cutting' && $status === 'SENT')
                        {{-- Tombol proses di Vendor Cutting --}}
                        <a href="{{ route('production.vendor_cutting.receive', $transfer->id) }}"
                            class="btn btn-success btn-sm">
                            ✂️ Proses di Vendor Cutting
                        </a>
                    @endif

                    <a href="{{ route('inventory.external_transfers.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        {{-- INFO UTAMA --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">

                    {{-- PROSES & OPERATOR --}}
                    <div class="col-12 col-md-4">
                        <div class="small text-muted mb-1">Proses & Operator</div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="chip mono">
                                {{ strtoupper($transfer->process ?? '-') }}
                            </span>
                            @if ($transfer->operator_code)
                                <span class="chip">
                                    OP:
                                    <span class="mono">{{ $transfer->operator_code }}</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- DARI / KE GUDANG --}}
                    <div class="col-12 col-md-5">
                        <div class="small text-muted mb-1">Gudang</div>
                        <div class="small mb-1">
                            <div>
                                <span class="text-muted">Dari:</span>
                                <span class="mono">{{ $transfer->fromWarehouse?->code ?? '-' }}</span>
                                — {{ $transfer->fromWarehouse?->name ?? '-' }}
                            </div>
                            @if ($fromType)
                                <span
                                    class="chip chip-warehouse {{ $fromExternal ? 'chip-wh-external' : 'chip-wh-internal' }}">
                                    {{ $fromExternal ? 'External' : 'Internal' }}
                                </span>
                            @endif
                        </div>
                        <div class="small mt-2">
                            <div>
                                <span class="text-muted">Ke:</span>
                                <span class="mono">{{ $transfer->toWarehouse?->code ?? '-' }}</span>
                                — {{ $transfer->toWarehouse?->name ?? '-' }}
                            </div>
                            @if ($toType)
                                <span
                                    class="chip chip-warehouse {{ $toExternal ? 'chip-wh-external' : 'chip-wh-internal' }}">
                                    {{ $toExternal ? 'External' : 'Internal' }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- CATATAN --}}
                    <div class="col-12 col-md-3">
                        <div class="small text-muted mb-1">Catatan</div>
                        <div class="small">
                            {{ $transfer->notes ?: '-' }}
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- SUMMARY TOTAL --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 text-center text-md-start">
                    <div class="col-4 col-md-3">
                        <div class="summary-label">Total Baris</div>
                        <div class="summary-value mono">
                            {{ $totalLines }}
                        </div>
                    </div>
                    <div class="col-4 col-md-3">
                        <div class="summary-label">Total LOT Unik</div>
                        <div class="summary-value mono">
                            {{ $totalLots }}
                        </div>
                    </div>
                    <div class="col-4 col-md-3">
                        <div class="summary-label">Total Qty Kirim</div>
                        <div class="summary-value mono">
                            {{ number_format($totalQty, 2, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- DETAIL LOT --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="fw-semibold small text-uppercase">Detail LOT</div>
                        <div class="text-muted small">
                            Daftar LOT yang dikirim ke vendor / gudang eksternal.
                        </div>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th style="min-width: 140px;">LOT</th>
                                <th style="min-width: 220px;">Item</th>
                                <th style="width: 120px;" class="text-end">Qty</th>
                                <th style="width: 80px;">Satuan</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transfer->lines as $i => $line)
                                @php
                                    $lot = $line->lot;
                                    $item = $lot?->item;
                                @endphp
                                <tr>
                                    <td class="text-center small">
                                        {{ $i + 1 }}
                                    </td>
                                    <td class="mono small">
                                        {{ $lot?->code ?? '-' }}
                                    </td>
                                    <td>
                                        <div class="mono small">
                                            {{ $item?->code ?? ($line->item_code ?? '-') }}
                                        </div>
                                        <div class="text-muted small">
                                            {{ $item?->name ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($line->qty, 2, ',', '.') }}
                                    </td>
                                    <td class="mono text-center small">
                                        {{ $line->unit ?? ($item->unit ?? 'pcs') }}
                                    </td>
                                    <td class="small">
                                        {{ $line->notes ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted small">
                                        Tidak ada detail LOT.
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
