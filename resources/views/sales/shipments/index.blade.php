{{-- resources/views/sales/shipments/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Shipments • Keluar Barang')

@push('head')
    <style>
        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: .9rem .9rem 3.5rem;
        }

        /* ===== BACKDROP ===== */
        body[data-theme="light"] .page-wrap {
            background: #f8fafc;
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(15, 23, 42, 0.9) 0, #020617 65%);
        }

        /* ===== CARD ===== */
        .card-main {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06), 0 0 0 1px rgba(148, 163, 184, 0.05);
        }

        body[data-theme="dark"] .card-main {
            border-color: rgba(30, 64, 175, 0.55);
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.82);
        }

        /* ===== HEADER ===== */
        .title {
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .sub {
            color: #6b7280;
            font-size: .92rem;
        }

        body[data-theme="dark"] .sub {
            color: #9ca3af;
        }

        /* ===== KPI ===== */
        .kpis {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-top: .65rem;
        }

        .kpi {
            display: inline-flex;
            align-items: baseline;
            gap: .45rem;
            border-radius: 999px;
            padding: .28rem .75rem;
            border: 1px solid rgba(148, 163, 184, 0.25);
            background: rgba(248, 250, 252, 0.92);
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

        body[data-theme="dark"] .kpi .lbl {
            color: #6b7280;
        }

        .kpi .val {
            font-weight: 800;
        }

        /* ===== CONTROLS ===== */
        .controls {
            display: flex;
            gap: .5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-label {
            font-size: .8rem;
            color: #6b7280;
        }

        body[data-theme="dark"] .filter-label {
            color: #9ca3af;
        }

        .filter-select {
            border-radius: 999px;
            padding-left: .9rem;
            padding-right: 2rem;
            font-size: .85rem;
        }

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
            padding: .85rem .75rem;
            white-space: nowrap;
        }

        body[data-theme="dark"] .table-list thead th {
            background: rgba(15, 23, 42, 0.98);
            color: #9ca3af;
            border-bottom-color: rgba(30, 64, 175, 0.6);
        }

        .table-list tbody td {
            vertical-align: middle;
            border-top-color: rgba(148, 163, 184, 0.16);
            padding: .85rem .75rem;
        }

        body[data-theme="dark"] .table-list tbody td {
            border-top-color: rgba(51, 65, 85, 0.85);
        }

        .code-link {
            font-weight: 800;
            text-decoration: none;
            color: inherit;
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
            font-weight: 700;
        }

        /* ===== STATUS BADGE ===== */
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
            white-space: nowrap;
        }

        .badge-status::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 999px;
            display: inline-block;
        }

        .st-submitted {
            background: rgba(59, 130, 246, 0.10);
            color: #1d4ed8;
            border-color: rgba(59, 130, 246, 0.30);
        }

        .st-submitted::before {
            background: rgba(59, 130, 246, 0.95);
        }

        .st-posted {
            background: rgba(34, 197, 94, 0.10);
            color: #166534;
            border-color: rgba(34, 197, 94, 0.30);
        }

        .st-posted::before {
            background: rgba(34, 197, 94, 0.95);
        }

        .st-cancelled {
            background: rgba(239, 68, 68, 0.10);
            color: #991b1b;
            border-color: rgba(239, 68, 68, 0.30);
        }

        .st-cancelled::before {
            background: rgba(239, 68, 68, 0.95);
        }

        body[data-theme="dark"] .st-submitted {
            background: rgba(59, 130, 246, 0.20);
            color: #dbeafe;
            border-color: rgba(59, 130, 246, 0.55);
        }

        body[data-theme="dark"] .st-posted {
            background: rgba(34, 197, 94, 0.20);
            color: #dcfce7;
            border-color: rgba(34, 197, 94, 0.55);
        }

        body[data-theme="dark"] .st-cancelled {
            background: rgba(239, 68, 68, 0.18);
            color: #fecaca;
            border-color: rgba(239, 68, 68, 0.55);
        }

        /* ===== EMPTY ===== */
        .empty {
            padding: 2.2rem 1.25rem;
            text-align: center;
            color: #6b7280;
        }

        body[data-theme="dark"] .empty {
            color: #9ca3af;
        }

        .divider {
            height: 1px;
            background: rgba(148, 163, 184, 0.20);
        }

        body[data-theme="dark"] .divider {
            background: rgba(51, 65, 85, 0.85);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        @php
            use Illuminate\Support\Carbon;

            $fmtDate = function ($value, string $format = 'd M Y', string $fallback = '-') {
                if (empty($value)) {
                    return $fallback;
                }
                try {
                    if ($value instanceof \DateTimeInterface) {
                        return $value->format($format);
                    }
                    return Carbon::parse($value)->format($format);
                } catch (\Throwable $e) {
                    return $fallback;
                }
            };

            // controller mengirim $statusFilter; fallback ke request
            $statusFilter = $statusFilter ?? request('status', 'all');

            // KPI halaman
            $pageTotalQty = $shipments->getCollection()->sum('total_qty_calc');
            $pageTotalRp = $shipments->getCollection()->sum('total_rp_calc');
        @endphp

        {{-- TOP BAR --}}
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
            <div>
                <div class="title h4 mb-1">Shipments Keluar Barang</div>
                <div class="sub">Dokumen barang keluar dari gudang siap jual (WH-RTS).</div>

                <div class="kpis">
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

            <div class="controls">
                {{-- FILTER STATUS --}}
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <span class="filter-label">Status</span>
                    <select name="status" class="form-select form-select-sm filter-select" onchange="this.form.submit()">
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All</option>
                        <option value="submitted" {{ $statusFilter === 'submitted' ? 'selected' : '' }}>Submitted</option>
                        <option value="posted" {{ $statusFilter === 'posted' ? 'selected' : '' }}>Posted</option>
                        <option value="cancelled" {{ $statusFilter === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </form>

                <a href="{{ route('sales.shipments.create') }}" class="btn btn-primary btn-pill">
                    <i class="bi bi-upc-scan me-1"></i>
                    Shipment Baru
                </a>
            </div>
        </div>

        {{-- TABLE CARD --}}
        <div class="card card-main">
            <div class="card-body p-0">
                @if ($shipments->count() === 0)
                    <div class="empty">
                        Belum ada shipment.
                        <div class="mt-1">Klik <b>Shipment Baru</b> untuk mulai scan.</div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-list">
                            <thead>
                                <tr>
                                    <th style="width: 52px;">#</th>
                                    <th style="width: 120px;">Tanggal</th>
                                    <th style="width: 210px;">Shipment</th>
                                    <th>Store / Channel</th>
                                    <th class="text-end" style="width: 110px;">Qty</th>
                                    <th class="text-end" style="width: 170px;">Total Rp</th>
                                    <th class="text-end" style="width: 110px;">Kategori</th>
                                    <th style="width: 130px;">Status</th>
                                    <th style="width: 90px;"></th>
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

                                        $isCancelled = !empty($shipment->cancelled_at);

                                        // Status tampil untuk UI
                                        $uiStatus = $isCancelled ? 'cancelled' : $shipment->status ?? 'submitted';

                                        $statusClass = match ($uiStatus) {
                                            'submitted' => 'st-submitted',
                                            'posted' => 'st-posted',
                                            'cancelled' => 'st-cancelled',
                                            default => 'st-submitted',
                                        };

                                        $statusLabel = strtoupper($uiStatus);
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

                                            <div class="muted mt-1">
                                                @if ($isCancelled)
                                                    Cancelled {{ $fmtDate($shipment->cancelled_at, 'd M Y H:i') }}
                                                @elseif (!empty($shipment->posted_at))
                                                    Posted {{ $fmtDate($shipment->posted_at, 'd M Y H:i') }}
                                                @elseif (!empty($shipment->submitted_at))
                                                    Submitted {{ $fmtDate($shipment->submitted_at, 'd M Y H:i') }}
                                                @else
                                                    Draft
                                                @endif
                                            </div>
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

                                        <td class="text-end">
                                            <span class="fw-semibold">{{ number_format($catCount, 0, ',', '.') }}</span>
                                        </td>

                                        <td>
                                            <span class="badge-status {{ $statusClass }}">{{ $statusLabel }}</span>
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

                    <div class="divider"></div>

                    {{-- PAGINATION --}}
                    <div class="p-3">
                        {{ $shipments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
