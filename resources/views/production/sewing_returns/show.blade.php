{{-- resources/views/production/sewing_returns/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Return ' . $return->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1000px;
            margin-inline: auto;
            padding: .75rem .75rem 3.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(16, 185, 129, 0.18) 0,
                    rgba(110, 231, 183, 0.1) 22%,
                    #f9fafb 58%);
        }

        .card {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(15, 23, 42, 0.03);
        }

        .card-section {
            padding: .85rem .95rem;
        }

        @media (min-width: 768px) {
            .page-wrap {
                padding: 1.1rem 1rem 3.5rem;
            }

            .card-section {
                padding: 1rem 1.25rem;
            }
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .badge-soft {
            border-radius: 999px;
            padding: .16rem .58rem;
            font-size: .72rem;
        }

        .badge-status {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .12em;
            border-radius: 999px;
            padding: .16rem .72rem;
            font-weight: 700;
        }

        .badge-status-posted {
            background: rgba(22, 163, 74, 0.12);
            color: #166534;
            border: 1px solid rgba(22, 163, 74, 0.5);
        }

        .badge-status-draft {
            background: rgba(148, 163, 184, 0.18);
            color: #4b5563;
            border: 1px solid rgba(148, 163, 184, 0.7);
        }

        .badge-status-reject {
            background: rgba(248, 113, 113, 0.14);
            color: #b91c1c;
            border: 1px solid rgba(248, 113, 113, 0.65);
        }

        .badge-flow {
            background: rgba(59, 130, 246, 0.08);
            color: #1d4ed8;
            border: 1px solid rgba(59, 130, 246, 0.4);
            font-size: .7rem;
            border-radius: 999px;
            padding: .16rem .7rem;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: flex-start;
        }

        .header-main {
            display: flex;
            gap: .6rem;
            align-items: center;
        }

        .header-icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle,
                    rgba(22, 163, 74, 0.18) 0,
                    rgba(22, 163, 74, 0.05) 55%,
                    transparent 100%);
            color: #16a34a;
        }

        .header-title {
            display: flex;
            flex-direction: column;
            gap: .12rem;
        }

        .header-title h1 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }

        .header-sub {
            font-size: .78rem;
            color: var(--muted);
        }

        .header-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem .4rem;
            font-size: .74rem;
            color: var(--muted);
        }

        .header-meta-row .mono {
            font-size: .8rem;
        }

        .pill-meta {
            border-radius: 999px;
            padding: .1rem .6rem;
            background: rgba(148, 163, 184, 0.1);
            font-size: .72rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .45rem .9rem;
            font-size: .8rem;
        }

        @media (min-width: 768px) {
            .summary-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .summary-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .summary-value {
            font-weight: 600;
        }

        .summary-value-ok {
            color: #166534;
        }

        .summary-value-reject {
            color: #b91c1c;
        }

        .summary-value-warning {
            color: #b45309;
        }

        .summary-sub {
            font-size: .7rem;
            color: var(--muted);
        }

        .table-wrap {
            overflow-x: auto;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            border-top: none;
            border-bottom-color: rgba(148, 163, 184, 0.35);
        }

        .table tbody td {
            font-size: .8rem;
            border-top-color: rgba(148, 163, 184, 0.2);
        }

        .row-ok-dominant {
            background: rgba(240, 253, 244, 0.95);
        }

        .row-reject-dominant {
            background: rgba(254, 242, 242, 0.96);
        }

        body[data-theme="dark"] .row-ok-dominant {
            background: rgba(22, 163, 74, 0.18);
        }

        body[data-theme="dark"] .row-reject-dominant {
            background: rgba(248, 113, 113, 0.15);
        }

        .qty-pill-ok {
            border-radius: 999px;
            padding: .08rem .5rem;
            font-size: .76rem;
            background: rgba(22, 163, 74, 0.12);
            color: #166534;
            border: 1px solid rgba(22, 163, 74, 0.4);
        }

        .qty-pill-reject {
            border-radius: 999px;
            padding: .08rem .5rem;
            font-size: .76rem;
            background: rgba(248, 113, 113, 0.16);
            color: #b91c1c;
            border: 1px solid rgba(248, 113, 113, 0.5);
        }

        .qty-pill-total {
            border-radius: 999px;
            padding: .08rem .5rem;
            font-size: .76rem;
            background: rgba(59, 130, 246, 0.06);
            color: #1d4ed8;
            border: 1px solid rgba(59, 130, 246, 0.35);
        }

        .section-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            margin-bottom: .4rem;
        }

        .section-title-row h2 {
            margin: 0;
            font-size: .88rem;
            font-weight: 700;
        }

        .section-sub {
            font-size: .75rem;
            color: var(--muted);
        }

        .chip-small {
            border-radius: 999px;
            padding: .06rem .55rem;
            font-size: .7rem;
            background: rgba(148, 163, 184, 0.16);
        }

        .btn-back {
            border-radius: 999px;
            padding: .2rem .7rem;
            font-size: .78rem;
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            background: rgba(248, 250, 252, 0.96);
            border: 1px solid rgba(148, 163, 184, 0.7);
        }

        .btn-link-pickup {
            border-radius: 999px;
            padding: .2rem .7rem;
            font-size: .78rem;
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            background: rgba(219, 234, 254, 0.96);
            border: 1px solid rgba(59, 130, 246, 0.7);
            color: #1d4ed8;
        }

        .btn-link-pickup:hover {
            color: #1e40af;
        }

        @media (max-width: 767.98px) {
            .header-row {
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
                display: flex;
                justify-content: space-between;
                gap: .5rem;
            }

            .btn-back,
            .btn-link-pickup {
                width: auto;
                padding-inline: .85rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        /** @var \App\Models\SewingReturn $return */
        $lines = $return->lines ?? collect();

        $hasReject = $totalReject > 0;

        // Group per item (WIP-FIN / REJ-SEW summary)
        $perItem = $lines
            ->groupBy(function ($line) {
                return optional(optional($line->sewingPickupLine)->bundle)->finished_item_id;
            })
            ->map(function ($group) {
                $line = $group->first();
                $bundle = optional($line->sewingPickupLine)->bundle;
                $item = optional($bundle)->finishedItem;

                return [
                    'item_id' => $item->id ?? null,
                    'item_code' => $item->code ?? '-',
                    'item_name' => $item->name ?? '',
                    'total_ok' => (float) $group->sum('qty_ok'),
                    'total_reject' => (float) $group->sum('qty_reject'),
                ];
            })
            ->sortBy('item_code')
            ->values();

        try {
            $dateLabel = $return->date ? id_day($return->date) : '-';
        } catch (\Throwable $e) {
            $dateLabel = optional($return->date)->format('d/m/Y') ?? '-';
        }

        // Label tanggal pickup (ambil dari header pickup)
        try {
            $pickupDateLabel = $return->pickup?->date ? id_day($return->pickup->date) : '-';
        } catch (\Throwable $e) {
            $pickupDateLabel = optional($return->pickup?->date)->format('d/m/Y') ?? '-';
        }
    @endphp

    <div class="page-wrap">
        {{-- HEADER --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="header-row">
                    <div class="header-main">
                        <div class="header-icon-circle">
                            <i class="bi bi-clipboard-check"></i>
                        </div>
                        <div class="header-title">
                            <h1>Sewing Return {{ $return->code }}</h1>
                            <div class="header-sub">
                                Setor hasil jahit dari pickup
                                <span class="mono">{{ $return->pickup?->code ?? '-' }}</span>
                            </div>

                            <div class="header-meta-row mt-1">
                                <span class="pill-meta mono">
                                    {{ $dateLabel }}
                                </span>

                                @if ($return->warehouse)
                                    <span class="pill-meta">
                                        Gudang: <span class="mono">{{ $return->warehouse->code }}</span>
                                        — {{ $return->warehouse->name }}
                                    </span>
                                @endif

                                @if ($return->pickup?->operator)
                                    <span class="pill-meta">
                                        Operator ambil:
                                        <span class="mono">{{ $return->pickup->operator->code }}</span>
                                        — {{ $return->pickup->operator->name }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="header-actions d-flex flex-column align-items-end gap-2">
                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                            {{-- Status posted/draft --}}
                            @php
                                $status = $return->status ?? 'posted';
                            @endphp
                            <span
                                class="badge-status {{ $status === 'posted' ? 'badge-status-posted' : 'badge-status-draft' }}">
                                {{ strtoupper($status) }}
                            </span>

                            {{-- Badge ada reject / semua OK --}}
                            @if ($hasReject)
                                <span class="badge-status badge-status-reject">
                                    ADA REJECT
                                </span>
                            @else
                                <span class="badge-soft bg-success-subtle text-success">
                                    Semua OK
                                </span>
                            @endif
                        </div>

                        {{-- Badge flow mutasi --}}
                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                            <span class="badge-flow">
                                <i class="bi bi-arrow-right"></i>
                                <span class="mono">WIP-SEW</span>
                                <span class="mx-1">➜</span>
                                <span class="mono">WIP-FIN</span>
                                <span class="mx-1">+</span>
                                <span class="mono">REJ-SEW</span>
                            </span>
                        </div>

                        <div class="d-flex flex-wrap gap-2 justify-content-end mt-1">
                            <a href="{{ route('production.sewing_returns.index') }}" class="btn btn-sm btn-back">
                                <i class="bi bi-arrow-left"></i>
                                <span class="d-none d-sm-inline">Kembali ke daftar</span>
                                <span class="d-inline d-sm-none">Kembali</span>
                            </a>

                            @if ($return->pickup)
                                <a href="{{ route('production.sewing_pickups.show', $return->pickup) }}"
                                    class="btn btn-sm btn-link-pickup">
                                    <i class="bi bi-box-seam"></i>
                                    <span>Lihat Pickup</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- RINGKASAN ANGKA (PAKAI DATA DARI CONTROLLER) --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="section-title-row mb-2">
                    <div>
                        <h2>Ringkasan mutasi WIP</h2>
                        <div class="section-sub">
                            Dari gudang <span class="mono">{{ $return->warehouse?->code ?? 'WIP-SEW' }}</span>
                            (pickup {{ $pickupDateLabel }})
                            ke WIP-FIN &amp; REJ-SEW.
                        </div>
                    </div>
                    <div class="d-none d-md-block">
                        <span class="chip-small mono">
                            {{ $lines->count() }} baris return
                        </span>
                    </div>
                </div>

                <div class="summary-grid">
                    <div>
                        <div class="summary-label">Total ambil (pickup)</div>
                        <div class="summary-value mono">
                            {{ number_format($totalPickup, 2, ',', '.') }} pcs
                        </div>
                        <div class="summary-sub">
                            Qty di Sewing Pickup (qty_bundle).
                        </div>
                    </div>
                    <div>
                        <div class="summary-label">Total setor (OK + Reject)</div>
                        <div class="summary-value mono">
                            {{ number_format($totalProcessed, 2, ',', '.') }} pcs
                        </div>
                        <div class="summary-sub">
                            Mutasi keluar dari WIP-SEW.
                        </div>
                    </div>
                    <div>
                        <div class="summary-label">Masuk WIP-FIN (OK)</div>
                        <div class="summary-value summary-value-ok mono">
                            {{ number_format($totalOk, 2, ',', '.') }} pcs
                        </div>
                        <div class="summary-sub">
                            {{ number_format($okPercent, 1, ',', '.') }}% dari setoran.
                        </div>
                    </div>
                    <div>
                        <div class="summary-label">Masuk REJ-SEW (Reject)</div>
                        <div class="summary-value summary-value-reject mono">
                            {{ number_format($totalReject, 2, ',', '.') }} pcs
                        </div>
                        <div class="summary-sub">
                            {{ number_format($rejectPercent, 1, ',', '.') }}% dari setoran.
                        </div>
                    </div>
                    <div>
                        <div class="summary-label">Sisa belum setor</div>
                        <div class="summary-value summary-value-warning mono">
                            {{ number_format($totalRemaining, 2, ',', '.') }} pcs
                        </div>
                        <div class="summary-sub">
                            Berdasarkan saldo di Sewing Pickup Line.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- PER ITEM: WIP-FIN vs REJ-SEW --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="section-title-row">
                    <div>
                        <h2>Ringkasan per item</h2>
                        <div class="section-sub">
                            Total masuk WIP-FIN dan REJ-SEW per item jadi.
                        </div>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th style="width:40px" class="text-center">#</th>
                                <th style="width:140px;">Item</th>
                                <th>Nama</th>
                                <th style="width:120px;" class="text-end">WIP-FIN (OK)</th>
                                <th style="width:120px;" class="text-end">REJ-SEW</th>
                                <th style="width:120px;" class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($perItem as $i => $row)
                                @php
                                    $totalItem = $row['total_ok'] + $row['total_reject'];
                                    $rowClass =
                                        $row['total_reject'] > 0 && $row['total_ok'] == 0
                                            ? 'row-reject-dominant'
                                            : ($row['total_ok'] > 0 && $row['total_reject'] == 0
                                                ? 'row-ok-dominant'
                                                : '');
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="text-center text-muted">
                                        #{{ $i + 1 }}
                                    </td>
                                    <td class="mono">
                                        {{ $row['item_code'] ?? '-' }}
                                    </td>
                                    <td>
                                        <div class="small">
                                            {{ $row['item_name'] ?: '—' }}
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="qty-pill-ok mono">
                                            {{ number_format($row['total_ok'], 2, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @if ($row['total_reject'] > 0)
                                            <span class="qty-pill-reject mono">
                                                {{ number_format($row['total_reject'], 2, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-muted mono">
                                                0,00
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="qty-pill-total mono">
                                            {{ number_format($totalItem, 2, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted small py-3">
                                        Belum ada detail return.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- DETAIL PER BARIS RETURN --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="section-title-row">
                    <div>
                        <h2>Detail baris Sewing Return</h2>
                        <div class="section-sub">
                            Per bundle hasil ambil jahit, termasuk pickup, bundle, dan LOT.
                        </div>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="table table-sm align-middle mono">
                        <thead>
                            <tr>
                                <th style="width:36px" class="text-center">#</th>
                                <th style="width:130px;">Item</th>
                                <th>Bundle</th>
                                <th style="width:120px;" class="text-end">OK</th>
                                <th style="width:120px;" class="text-end">Reject</th>
                                <th style="width:120px;" class="text-end">Total</th>
                                <th style="width:200px;">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lines as $i => $line)
                                @php
                                    /** @var \App\Models\SewingReturnLine $line */
                                    $pickupLine = $line->sewingPickupLine;
                                    $bundle = optional($pickupLine)->bundle;
                                    $item = optional($bundle)->finishedItem;
                                    $cuttingJob = optional($bundle)->cuttingJob;
                                    $lot = optional($cuttingJob)->lot;

                                    $ok = (float) $line->qty_ok;
                                    $reject = (float) $line->qty_reject;
                                    $total = $ok + $reject;

                                    $rowClass =
                                        $reject > 0 && $ok == 0
                                            ? 'row-reject-dominant'
                                            : ($ok > 0 && $reject == 0
                                                ? 'row-ok-dominant'
                                                : '');
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="text-center text-muted">
                                        #{{ $i + 1 }}
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $item?->code ?? '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $item?->name ?? '' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            @if ($bundle?->bundle_code)
                                                <span class="badge-soft bg-light border text-muted">
                                                    {{ $bundle->bundle_code }}
                                                </span>
                                            @endif

                                            @if ($lot)
                                                <span class="badge-soft bg-light border text-muted ms-1">
                                                    LOT {{ $lot->code }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="small text-muted mt-1">
                                            Pickup:
                                            <span class="mono">
                                                {{ number_format($pickupLine->qty_bundle ?? 0, 2, ',', '.') }} pcs
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="qty-pill-ok">
                                            {{ number_format($ok, 2, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @if ($reject > 0)
                                            <span class="qty-pill-reject">
                                                {{ number_format($reject, 2, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                0,00
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="qty-pill-total">
                                            {{ number_format($total, 2, ',', '.') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($line->notes)
                                            <div class="small">
                                                {{ $line->notes }}
                                            </div>
                                        @else
                                            <span class="small text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted small py-3">
                                        Tidak ada detail baris return.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($return->notes)
                    <hr class="my-2">
                    <div class="small">
                        <span class="text-muted">Catatan header:</span>
                        <span>{{ $return->notes }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
