{{-- resources/views/sales/shipments/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Shipments • Keluar Barang')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 3.5rem;
        }

        /* ===== LIGHT BG (clean) ===== */
        body[data-theme="light"] .page-wrap {
            background: #f8fafc;
        }

        /* ===== CARD ===== */
        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06), 0 0 0 1px rgba(148, 163, 184, 0.06);
        }

        /* ===== HEADER ===== */
        .title {
            font-weight: 700;
            letter-spacing: -.01em;
        }

        .sub {
            color: #6b7280;
            font-size: .9rem;
        }

        body[data-theme="dark"] .sub {
            color: #9ca3af;
        }

        /* ===== KPI (minimal) ===== */
        .kpi {
            display: inline-flex;
            align-items: baseline;
            gap: .45rem;
            border: 1px solid rgba(148, 163, 184, 0.28);
            background: rgba(248, 250, 252, 0.9);
            border-radius: 999px;
            padding: .28rem .75rem;
            font-size: .82rem;
        }

        body[data-theme="dark"] .kpi {
            background: rgba(15, 23, 42, 0.96);
            border-color: rgba(30, 64, 175, 0.7);
        }

        .kpi .lbl {
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .64rem;
            color: #9ca3af;
        }

        .kpi .val {
            font-weight: 700;
        }

        /* ===== BUTTON ===== */
        .btn-pill {
            border-radius: 999px;
            padding-inline: 1rem;
        }

        /* ===== TABLE ===== */
        .table-list {
            margin-bottom: 0;
        }

        .table-list thead th {
            border-bottom-width: 1px;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #6b7280;
            background: rgba(148, 163, 184, 0.06);
        }

        body[data-theme="dark"] .table-list thead th {
            background: rgba(15, 23, 42, 0.96);
            color: #9ca3af;
            border-bottom-color: rgba(30, 64, 175, 0.6);
        }

        .table-list tbody td {
            vertical-align: middle;
            border-top-color: rgba(148, 163, 184, 0.18);
            padding-top: .7rem;
            padding-bottom: .7rem;
        }

        body[data-theme="dark"] .table-list tbody td {
            border-top-color: rgba(51, 65, 85, 0.85);
        }

        .code-link {
            font-weight: 700;
            text-decoration: none;
        }

        .code-link:hover {
            text-decoration: underline;
        }

        .muted {
            font-size: .82rem;
            color: #6b7280;
        }

        body[data-theme="dark"] .muted {
            color: #9ca3af;
        }

        .store-name {
            font-weight: 600;
        }

        /* ===== STATUS ===== */
        .badge-status {
            border-radius: 999px;
            padding: .18rem .7rem;
            font-size: .72rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .badge-status::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 999px;
            display: inline-block;
        }

        .st-draft {
            background: rgba(245, 158, 11, 0.10);
            color: #92400e;
            border-color: rgba(245, 158, 11, 0.30);
        }

        .st-draft::before {
            background: rgba(245, 158, 11, 0.95);
        }

        .st-submitted {
            background: rgba(34, 197, 94, 0.10);
            color: #166534;
            border-color: rgba(34, 197, 94, 0.30);
        }

        .st-submitted::before {
            background: rgba(34, 197, 94, 0.95);
        }

        .st-posted {
            background: rgba(37, 99, 235, 0.10);
            color: #1e3a8a;
            border-color: rgba(37, 99, 235, 0.30);
        }

        .st-posted::before {
            background: rgba(37, 99, 235, 0.95);
        }

        body[data-theme="dark"] .st-draft {
            background: rgba(245, 158, 11, 0.22);
            color: #fef9c3;
            border-color: rgba(245, 158, 11, 0.55);
        }

        body[data-theme="dark"] .st-submitted {
            background: rgba(34, 197, 94, 0.22);
            color: #dcfce7;
            border-color: rgba(34, 197, 94, 0.55);
        }

        body[data-theme="dark"] .st-posted {
            background: rgba(37, 99, 235, 0.22);
            color: #dbeafe;
            border-color: rgba(37, 99, 235, 0.55);
        }

        /* ===== EMPTY STATE ===== */
        .empty {
            padding: 2rem 1.25rem;
            text-align: center;
            color: #6b7280;
        }

        body[data-theme="dark"] .empty {
            color: #9ca3af;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        @php
            use Illuminate\Support\Carbon;

            /**
             * Format date/time safely.
             * - Accepts Carbon|DateTime|string|null
             * - Returns '-' if invalid/empty
             */
            $fmtDate = function ($value, string $format = 'd M Y', string $fallback = '-') {
                if (empty($value)) {
                    return $fallback;
                }

                try {
                    if ($value instanceof \DateTimeInterface) {
                        return $value->format($format);
                    }

                    // string / int timestamp / etc
                    return Carbon::parse($value)->format($format);
                } catch (\Throwable $e) {
                    return $fallback;
                }
            };

            // Controller index() idealnya sudah set:
            // total_qty_calc, total_rp_calc, category_count_calc
            $pageTotalQty = $shipments->getCollection()->sum('total_qty_calc');
            $pageTotalRp = $shipments->getCollection()->sum('total_rp_calc');
        @endphp

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
            <div>
                <div class="title h4 mb-1">Shipments Keluar Barang</div>
                <div class="sub">Rekap dokumen barang keluar dari gudang siap jual.</div>

                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="kpi">
                        <span class="lbl">Total</span>
                        <span class="val">{{ number_format($shipments->total(), 0, ',', '.') }}</span>
                    </span>
                    <span class="kpi">
                        <span class="lbl">Halaman</span>
                        <span class="val">{{ number_format($shipments->count(), 0, ',', '.') }}</span>
                    </span>
                    <span class="kpi">
                        <span class="lbl">Qty</span>
                        <span class="val">{{ number_format($pageTotalQty, 0, ',', '.') }}</span>
                    </span>
                    <span class="kpi">
                        <span class="lbl">Rp</span>
                        <span class="val">Rp {{ number_format($pageTotalRp, 0, ',', '.') }}</span>
                    </span>
                </div>
            </div>

            <a href="{{ route('sales.shipments.create') }}" class="btn btn-primary btn-pill">
                <i class="bi bi-upc-scan me-1"></i>
                Shipment Baru
            </a>
        </div>

        {{-- TABLE CARD --}}
        <div class="card card-main">
            <div class="card-body p-0">
                @if ($shipments->count() === 0)
                    <div class="empty">
                        Belum ada shipment. Klik <span class="fw-semibold">Shipment Baru</span> untuk mulai scan.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-list">
                            <thead>
                                <tr>
                                    <th style="width: 44px;">#</th>
                                    <th style="width: 120px;">Tanggal</th>
                                    <th style="width: 190px;">Shipment</th>
                                    <th>Store / Channel</th>
                                    <th class="text-end" style="width: 110px;">Qty</th>
                                    <th class="text-end" style="width: 160px;">Total Rp</th>
                                    <th class="text-end" style="width: 105px;">Kategori</th>
                                    <th style="width: 130px;">Status</th>
                                    <th style="width: 84px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($shipments as $shipment)
                                    @php
                                        $storeName = $shipment->store->name ?? '-';
                                        $storeCode = $shipment->store->code ?? '';
                                        $channelLabel = $storeCode ? strtoupper($storeCode) : null;

                                        $qty = (int) ($shipment->total_qty_calc ?? 0);
                                        $totalRp = (float) ($shipment->total_rp_calc ?? 0);

                                        $catCount = (int) ($shipment->category_count_calc ?? 0);

                                        $status = $shipment->status ?? 'draft';
                                        $statusClass =
                                            $status === 'draft'
                                                ? 'st-draft'
                                                : ($status === 'submitted'
                                                    ? 'st-submitted'
                                                    : ($status === 'posted'
                                                        ? 'st-posted'
                                                        : 'st-submitted'));
                                        $statusLabel = strtoupper($status);
                                    @endphp

                                    <tr>
                                        <td class="text-muted small">
                                            {{ ($shipments->currentPage() - 1) * $shipments->perPage() + $loop->iteration }}
                                        </td>

                                        <td class="small">
                                            {{ $fmtDate($shipment->date, 'd M Y') }}
                                        </td>

                                        <td>
                                            <a class="code-link" href="{{ route('sales.shipments.show', $shipment) }}">
                                                {{ $shipment->code }}
                                            </a>

                                            @if (!empty($shipment->posted_at))
                                                <div class="muted">
                                                    Posted {{ $fmtDate($shipment->posted_at, 'd M Y H:i') }}
                                                </div>
                                            @elseif (!empty($shipment->submitted_at))
                                                <div class="muted">
                                                    Submitted {{ $fmtDate($shipment->submitted_at, 'd M Y H:i') }}
                                                </div>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="store-name">{{ $storeName }}</div>
                                            @if ($channelLabel)
                                                <div class="muted">{{ $channelLabel }}</div>
                                            @endif
                                        </td>

                                        <td class="text-end">
                                            <span class="fw-semibold">{{ number_format($qty, 0, ',', '.') }}</span>
                                        </td>

                                        <td class="text-end">
                                            <span class="fw-semibold">Rp {{ number_format($totalRp, 0, ',', '.') }}</span>
                                        </td>

                                        {{-- KATEGORI: hanya jumlah --}}
                                        <td class="text-end">
                                            <span class="fw-semibold">{{ number_format($catCount, 0, ',', '.') }}</span>
                                        </td>

                                        <td>
                                            <span class="badge-status {{ $statusClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>

                                        <td class="text-end">
                                            <a href="{{ route('sales.shipments.show', $shipment) }}"
                                                class="btn btn-sm btn-outline-primary btn-pill">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- PAGINATION --}}
                    <div class="p-3">
                        {{ $shipments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
