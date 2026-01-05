{{-- resources/views/production/sewing_pickups/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Pickup ' . $pickup->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: 0.75rem 0.75rem 3rem;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .06), 0 0 0 1px rgba(15, 23, 42, .02);
        }

        .card-section {
            padding: .85rem .95rem;
        }

        @media (min-width: 768px) {
            .card-section {
                padding: 1rem 1.2rem;
            }
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .6rem;
            font-size: .72rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .header-main {
            min-width: 0;
        }

        .header-main h1 {
            font-size: 1rem;
            font-weight: 700;
        }

        .header-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .5rem;
        }

        .status-badge-main {
            font-size: .78rem;
            padding-inline: .9rem;
        }

        .header-icon-circle {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: .6rem;
            background: radial-gradient(circle, rgba(59, 130, 246, .15) 0, rgba(59, 130, 246, .04) 60%, transparent 100%);
            color: #2563eb;
        }

        .overall-progress-wrap {
            margin-top: .55rem;
        }

        .overall-progress-label {
            display: flex;
            justify-content: space-between;
            font-size: .76rem;
            color: var(--muted);
            margin-bottom: .25rem;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .overall-progress {
            height: .65rem;
            border-radius: 999px;
            overflow: hidden;
            background: linear-gradient(to right, rgba(226, 232, 240, .95), rgba(209, 213, 219, .98));
        }

        .overall-progress-bar {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 45%, #15803d 100%);
            box-shadow: 0 0 0 1px rgba(22, 163, 74, .35), 0 4px 10px rgba(22, 163, 74, .35);
            transition: width .25s ease-out;
        }

        .overall-progress-bar.is-empty {
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 40%, #9ca3af 100%);
            box-shadow: 0 0 0 1px rgba(148, 163, 184, .4), 0 3px 7px rgba(148, 163, 184, .25);
        }

        .summary-pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: .3rem;
            margin-top: .35rem;
        }

        .summary-pill {
            border-radius: 999px;
            padding: .1rem .55rem;
            font-size: .7rem;
            background: rgba(148, 163, 184, .12);
        }

        .summary-pill-warn {
            background: rgba(245, 158, 11, .14);
            color: #92400e;
        }

        .summary-pill-status {
            background: rgba(59, 130, 246, .08);
            color: #1d4ed8;
        }

        .pickup-line-row td {
            border-top-color: rgba(148, 163, 184, .24) !important;
        }

        .pickup-status-badge {
            font-size: .7rem;
            border-radius: 999px;
            padding: .14rem .55rem;
        }

        .line-progress-wrap {
            margin-top: .18rem;
        }

        .line-progress {
            width: 100%;
            height: .32rem;
            border-radius: 999px;
            background: rgba(229, 231, 235, .95);
            overflow: hidden;
        }

        .line-progress-bar {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #22c55e 0%, #16a34a 60%, #15803d 100%);
        }

        .line-progress-bar.partial {
            background: linear-gradient(90deg, #f59e0b 0%, #eab308 60%, #d97706 100%);
        }

        .line-progress-bar.empty {
            background: linear-gradient(90deg, #e5e7eb 0%, #d1d5db 50%);
        }

        .status-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: .35rem;
        }

        .status-chip-label {
            font-size: .75rem;
            color: var(--muted);
            margin-right: .1rem;
        }

        .status-chip {
            border-radius: 999px;
            padding: .18rem .7rem;
            font-size: .74rem;
            font-weight: 600;
            border: 1px solid rgba(148, 163, 184, .6);
            background: rgba(248, 250, 252, .96);
            color: #4b5563;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease, color .15s ease, transform .08s ease;
        }

        .status-chip-count {
            font-variant-numeric: tabular-nums;
        }

        .status-chip:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(148, 163, 184, .35), 0 0 0 1px rgba(148, 163, 184, .2);
        }

        .status-chip.active-all {
            border-color: rgba(59, 130, 246, .8);
            background: radial-gradient(circle at top left, rgba(59, 130, 246, .15) 0, rgba(239, 246, 255, .96) 50%);
            color: #1d4ed8;
            box-shadow: 0 0 0 1px rgba(191, 219, 254, .9), 0 8px 18px rgba(59, 130, 246, .35);
        }

        .status-chip.active-not-returned {
            border-color: rgba(148, 163, 184, .9);
            background: radial-gradient(circle at top left, rgba(148, 163, 184, .16) 0, rgba(249, 250, 251, .98) 50%);
            color: #111827;
        }

        .status-chip.active-partial {
            border-color: rgba(245, 158, 11, .9);
            background: radial-gradient(circle at top left, rgba(250, 204, 21, .20) 0, rgba(255, 251, 235, .98) 55%);
            color: #92400e;
            box-shadow: 0 0 0 1px rgba(250, 204, 21, .7), 0 8px 18px rgba(250, 204, 21, .25);
        }

        .status-chip.active-full {
            border-color: rgba(22, 163, 74, .9);
            background: radial-gradient(circle at top left, rgba(22, 163, 74, .22) 0, rgba(240, 253, 244, .98) 55%);
            color: #166534;
            box-shadow: 0 0 0 1px rgba(74, 222, 128, .8), 0 8px 18px rgba(22, 163, 74, .35);
        }

        .void-box {
            border-radius: 14px;
            border: 1px dashed rgba(239, 68, 68, .55);
            background: rgba(239, 68, 68, .06);
            padding: .75rem .9rem;
        }

        .void-title {
            font-weight: 800;
            color: #b91c1c;
            letter-spacing: .03em;
        }

        .void-meta {
            margin-top: .25rem;
            font-size: .85rem;
            color: rgba(75, 85, 99, .95);
        }

        @media (max-width: 767.98px) {
            .card {
                border-radius: 12px;
            }

            .page-wrap {
                padding-inline: .6rem;
            }

            .header-row {
                flex-direction: column;
                align-items: stretch;
            }

            .header-actions {
                align-items: stretch;
            }

            .status-badge-main {
                align-self: flex-start;
            }

            .status-chip-row {
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: .15rem;
                margin-bottom: .45rem;
            }

            .status-chip-row::-webkit-scrollbar {
                height: 4px;
            }

            .status-chip-row::-webkit-scrollbar-thumb {
                background: rgba(148, 163, 184, .6);
                border-radius: 999px;
            }

            .table-pickup-lines {
                border-collapse: separate;
                border-spacing: 0 10px;
            }

            .table-pickup-lines thead {
                display: none;
            }

            .table-pickup-lines tbody tr {
                display: block;
                border-radius: 14px;
                border: 1px solid rgba(148, 163, 184, .25);
                padding: .55rem .75rem .65rem;
                margin-bottom: .5rem;
                background: #ffffff;
                box-shadow: 0 10px 22px rgba(15, 23, 42, .08), 0 0 0 1px rgba(15, 23, 42, .02);
            }

            body[data-theme="dark"] .table-pickup-lines tbody tr {
                background: rgba(15, 23, 42, .96);
                border-color: rgba(30, 64, 175, .55);
                box-shadow: 0 12px 28px rgba(15, 23, 42, .9), 0 0 0 1px rgba(15, 23, 42, .9);
            }

            .table-pickup-lines td {
                display: block;
                border: none !important;
                padding: .08rem 0;
            }

            .td-desktop-only {
                display: none !important;
            }

            .mobile-card-top {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: .7rem;
                margin-bottom: .2rem;
            }

            .mobile-card-left {
                display: flex;
                flex-direction: column;
                gap: .16rem;
                min-width: 0;
            }

            .mobile-row-index {
                font-size: .7rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: .12em;
                color: var(--muted);
            }

            .mobile-item-code-chip {
                display: inline-flex;
                align-items: center;
                padding: .26rem .8rem;
                border-radius: 999px;
                font-size: 1.05rem;
                font-weight: 800;
                letter-spacing: .03em;
                white-space: nowrap;
                background: rgba(37, 99, 246, .1);
                color: #1d4ed8;
                border: 1px solid rgba(37, 99, 246, .35);
                box-shadow: 0 3px 7px rgba(37, 99, 246, .16), 0 0 0 1px rgba(191, 219, 254, .7);
            }

            body[data-theme="dark"] .mobile-item-code-chip {
                background: rgba(37, 99, 235, .3);
                color: #e5edff;
                border-color: rgba(129, 140, 248, .8);
            }

            .mobile-card-right {
                text-align: right;
                min-width: 140px;
            }

            .mobile-status-label {
                font-size: .62rem;
                text-transform: uppercase;
                letter-spacing: .16em;
                color: var(--muted);
                margin-bottom: .12rem;
            }

            .mobile-status-badge {
                font-size: .7rem;
                border-radius: 999px;
                padding: .16rem .6rem;
            }

            .mobile-progress-wrap {
                margin-top: .32rem;
            }

            .mobile-progress-label {
                font-size: .7rem;
                color: var(--muted);
            }

            .td-mobile-meta {
                font-size: .74rem;
                color: var(--muted);
                margin-top: .1rem;
            }

            .td-mobile-meta .mono {
                font-size: .78rem;
            }

            .mobile-qty-row {
                margin-top: .15rem;
                font-size: .78rem;
            }
        }

        @media (min-width: 768px) {
            .td-mobile-only {
                display: none !important;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $statusMap = [
            'draft' => ['label' => 'DRAFT', 'class' => 'secondary'],
            'posted' => ['label' => 'POSTED', 'class' => 'primary'],
            'closed' => ['label' => 'CLOSED', 'class' => 'success'],

            // ✅ tambahan sesuai data kamu
            'partial' => ['label' => 'PARTIAL', 'class' => 'warning'],
            'completed' => ['label' => 'COMPLETED', 'class' => 'success'],
            'void' => ['label' => 'VOID', 'class' => 'danger'],
        ];

        $cfg = $statusMap[$pickup->status] ?? [
            'label' => strtoupper($pickup->status ?? '-'),
            'class' => 'secondary',
        ];

        $lines = $pickup->lines ?? collect();
        $epsilon = 0.000001;

        // Ringkasan
        $totalBundles = (int) $lines->count();
        $totalQtyPickup = (float) $lines->sum(fn($l) => (float) ($l->qty_bundle ?? 0));
        $totalReturnOk = (float) $lines->sum(fn($l) => (float) ($l->qty_returned_ok ?? 0));
        $totalReturnReject = (float) $lines->sum(fn($l) => (float) ($l->qty_returned_reject ?? 0));
        $totalDirectPick = (float) $lines->sum(fn($l) => (float) ($l->qty_direct_picked ?? 0));
        $totalProgressAdj = (float) $lines->sum(fn($l) => (float) ($l->qty_progress_adjusted ?? 0));

        $totalProcessed = $totalReturnOk + $totalReturnReject + $totalDirectPick + $totalProgressAdj;
        $totalRemaining = max($totalQtyPickup - $totalProcessed, 0);

        $overallPercent = $totalQtyPickup > 0 ? min(100, max(0, ($totalProcessed / $totalQtyPickup) * 100)) : 0;

        // Count status bundle
        $notReturnedCount = 0;
        $partialReturnedCount = 0;
        $fullReturnedCount = 0;

        foreach ($lines as $l) {
            $qtyPickup = (float) ($l->qty_bundle ?? 0);
            $processed =
                (float) ($l->qty_returned_ok ?? 0) +
                (float) ($l->qty_returned_reject ?? 0) +
                (float) ($l->qty_direct_picked ?? 0) +
                (float) ($l->qty_progress_adjusted ?? 0);

            $remain = max($qtyPickup - $processed, 0);

            if ($processed <= $epsilon) {
                $notReturnedCount++;
            } elseif ($remain > $epsilon) {
                $partialReturnedCount++;
            } else {
                $fullReturnedCount++;
            }
        }

        // ✅ aturan: tombol aktif hanya kalau belum ada proses lain
        $canVoid = $pickup->status !== 'void' && $totalProcessed <= $epsilon;
    @endphp

    <div class="page-wrap">
        {{-- HEADER --}}
        <div class="card mb-3">
            <div class="card-section">
                <div class="header-row">
                    <div class="header-main">
                        <div class="d-flex align-items-center mb-1">
                            <div class="header-icon-circle">
                                <i class="bi bi-scissors"></i>
                            </div>
                            <div>
                                <h1 class="mb-1">Sewing Pickup: {{ $pickup->code }}</h1>
                                <div class="help">
                                    Tanggal: {{ $pickup->date?->format('Y-m-d') ?? $pickup->date }} •
                                    Gudang Jahit: {{ $pickup->warehouse?->code ?? '-' }} —
                                    {{ $pickup->warehouse?->name ?? '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="help mt-1">
                            Operator Jahit:
                            @if ($pickup->operator)
                                <span class="mono">
                                    {{ $pickup->operator->code }} — {{ $pickup->operator->name }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>

                        @if ($pickup->notes)
                            <div class="mt-2 small text-muted">
                                Catatan: {{ $pickup->notes }}
                            </div>
                        @endif
                    </div>

                    <div class="header-actions">
                        <span class="badge bg-{{ $cfg['class'] }} status-badge-main">
                            {{ $cfg['label'] }}
                        </span>

                        <a href="{{ route('production.sewing.pickups.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>

                {{-- ✅ VOID INFO --}}
                @if ($pickup->status === 'void')
                    <div class="void-box mt-3">
                        <div class="void-title">
                            <i class="bi bi-exclamation-octagon me-1"></i> DOKUMEN VOID
                        </div>
                        <div class="void-meta">
                            <div>Alasan: <span class="mono">{{ $pickup->void_reason ?? '-' }}</span></div>
                            <div>Waktu: <span
                                    class="mono">{{ $pickup->voided_at ? \Carbon\Carbon::parse($pickup->voided_at)->format('Y-m-d H:i') : '-' }}</span>
                            </div>
                            <div>User: <span class="mono">{{ $pickup->voided_by ?? '-' }}</span></div>
                        </div>
                    </div>
                @endif

                {{-- ✅ TOMBOL VOID (selalu tampil jika belum void, disabled jika tidak bisa) --}}
                @if ($pickup->status !== 'void')
                    <form method="POST" action="{{ route('production.sewing.pickups.void', $pickup) }}"
                        class="d-flex flex-wrap gap-2 mt-3 align-items-center">
                        @csrf

                        <input name="reason" required maxlength="150" class="form-control form-control-sm"
                            style="min-width: 240px;" placeholder="Alasan VOID (wajib)" {{ $canVoid ? '' : 'disabled' }}>

                        <button type="submit" class="btn btn-sm btn-outline-danger" {{ $canVoid ? '' : 'disabled' }}
                            onclick="return confirm('Yakin VOID pickup ini? Stok akan dibalik ke WIP-CUT.')">
                            <i class="bi bi-x-octagon me-1"></i> VOID
                        </button>

                        @if (!$canVoid)
                            <span class="help">
                                Tidak bisa VOID karena sudah ada proses (OK/RJ/DP/Adj).
                                Total diproses: <span
                                    class="mono">{{ number_format($totalProcessed, 2, ',', '.') }}</span>
                            </span>
                        @endif
                    </form>
                @endif
            </div>
        </div>

        {{-- SUMMARY --}}
        <div class="card mb-3">
            <div class="card-section">
                <h2 class="h6 mb-2">Ringkasan Pickup & Progress Setor</h2>

                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="help mb-1">Jumlah Bundle</div>
                        <div class="mono">{{ number_format($totalBundles, 0, ',', '.') }}</div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="help mb-1">Total Pickup (pcs)</div>
                        <div class="mono">{{ number_format($totalQtyPickup, 2, ',', '.') }}</div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="help mb-1">Return OK (pcs)</div>
                        <div class="mono">{{ number_format($totalReturnOk, 2, ',', '.') }}</div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="help mb-1">Return Reject (pcs)</div>
                        <div class="mono">{{ number_format($totalReturnReject, 2, ',', '.') }}</div>
                    </div>
                </div>

                <div class="summary-pill-row">
                    <span class="summary-pill summary-pill-warn">
                        Dadakan: <span class="mono">{{ number_format($totalDirectPick, 2, ',', '.') }}</span>
                    </span>
                    <span class="summary-pill summary-pill-warn">
                        Adj Progress: <span class="mono">{{ number_format($totalProgressAdj, 2, ',', '.') }}</span>
                    </span>
                    <span class="summary-pill summary-pill-status">
                        Belum setor (pcs): <span class="mono">{{ number_format($totalRemaining, 2, ',', '.') }}</span>
                    </span>
                </div>

                <div class="overall-progress-wrap">
                    <div class="overall-progress-label">
                        <span>Progress Setor Jahit</span>
                        <span class="mono">
                            {{ number_format($overallPercent, 1, ',', '.') }}%
                            @if ($totalQtyPickup > 0)
                                ({{ number_format($totalProcessed, 2, ',', '.') }} /
                                {{ number_format($totalQtyPickup, 2, ',', '.') }} pcs)
                            @endif
                        </span>
                    </div>
                    <div class="overall-progress">
                        <div class="overall-progress-bar {{ $overallPercent <= 0 ? 'is-empty' : '' }}"
                            style="width: {{ $overallPercent }}%;"></div>
                    </div>

                    <div class="summary-pill-row">
                        <span class="summary-pill summary-pill-status">Belum setor: {{ $notReturnedCount }} bundle</span>
                        <span class="summary-pill summary-pill-status">Parsial: {{ $partialReturnedCount }} bundle</span>
                        <span class="summary-pill summary-pill-status">Sudah penuh: {{ $fullReturnedCount }} bundle</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- DETAIL BUNDLES --}}
        <div class="card mb-4">
            <div class="card-section">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <h2 class="h6 mb-0">Detail Bundles</h2>

                    <div class="status-chip-row" id="status-chip-row">
                        <span class="status-chip-label d-none d-md-inline">Filter:</span>

                        <button type="button" class="status-chip active-all" data-status="all"
                            id="chip-status-all">Semua</button>

                        <button type="button" class="status-chip" data-status="not_returned" id="chip-status-not-returned">
                            Belum Setor <span class="status-chip-count mono">{{ $notReturnedCount }}</span>
                        </button>

                        <button type="button" class="status-chip" data-status="partial" id="chip-status-partial">
                            Parsial <span class="status-chip-count mono">{{ $partialReturnedCount }}</span>
                        </button>

                        <button type="button" class="status-chip" data-status="full" id="chip-status-full">
                            Sudah Penuh <span class="status-chip-count mono">{{ $fullReturnedCount }}</span>
                        </button>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="table table-sm align-middle mono mb-0 table-pickup-lines">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 130px;">Bundle</th>
                                <th style="width: 160px;">Item Jadi</th>
                                <th style="width: 170px;">Lot</th>
                                <th class="text-end" style="width: 110px;">Qty</th>
                                <th class="text-end" style="width: 110px;">OK</th>
                                <th class="text-end" style="width: 110px;">RJ</th>
                                <th class="text-end" style="width: 110px;">DP</th>
                                <th class="text-end" style="width: 120px;">Adj</th>
                                <th class="text-end" style="width: 120px;">Sisa</th>
                                <th style="width: 110px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lines as $line)
                                @php
                                    $bundle = $line->bundle;
                                    $lot = $bundle?->cuttingJob?->lot;

                                    $qtyPickup = (float) ($line->qty_bundle ?? 0);
                                    $returnedOk = (float) ($line->qty_returned_ok ?? 0);
                                    $returnedReject = (float) ($line->qty_returned_reject ?? 0);
                                    $directPick = (float) ($line->qty_direct_picked ?? 0);
                                    $progressAdj = (float) ($line->qty_progress_adjusted ?? 0);

                                    $processed = $returnedOk + $returnedReject + $directPick + $progressAdj;
                                    $remaining = max($qtyPickup - $processed, 0);

                                    $percentLine =
                                        $qtyPickup > 0 ? min(100, max(0, ($processed / $qtyPickup) * 100)) : 0;

                                    if ($processed <= $epsilon) {
                                        $lineStatusLabel = 'Belum Setor';
                                        $lineStatusClass = 'secondary';
                                        $lineProgressClass = 'empty';
                                        $lineStatusKey = 'not_returned';
                                    } elseif ($remaining > $epsilon) {
                                        $lineStatusLabel = 'Parsial';
                                        $lineStatusClass = 'warning';
                                        $lineProgressClass = 'partial';
                                        $lineStatusKey = 'partial';
                                    } else {
                                        $lineStatusLabel = 'Sudah Penuh';
                                        $lineStatusClass = 'success';
                                        $lineProgressClass = '';
                                        $lineStatusKey = 'full';
                                    }
                                @endphp

                                <tr class="pickup-line-row" data-line-status="{{ $lineStatusKey }}">
                                    <td class="td-desktop-only">{{ $loop->iteration }}</td>

                                    <td class="td-desktop-only">
                                        {{ $bundle?->bundle_code ?? '-' }}
                                    </td>

                                    <td class="td-desktop-only">
                                        {{ $bundle?->finishedItem?->code ?? '-' }}
                                        @if ($bundle?->finishedItem?->name)
                                            <div class="small text-muted">{{ $bundle->finishedItem->name }}</div>
                                        @endif
                                    </td>

                                    <td class="td-desktop-only">
                                        @if ($lot)
                                            {{ $lot->item?->code ?? '-' }}
                                            <span class="badge-soft bg-light border text-muted">{{ $lot->code }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td class="td-desktop-only text-end">{{ number_format($qtyPickup, 2, ',', '.') }}</td>
                                    <td class="td-desktop-only text-end">{{ number_format($returnedOk, 2, ',', '.') }}
                                    </td>
                                    <td class="td-desktop-only text-end">{{ number_format($returnedReject, 2, ',', '.') }}
                                    </td>
                                    <td class="td-desktop-only text-end">{{ number_format($directPick, 2, ',', '.') }}
                                    </td>
                                    <td class="td-desktop-only text-end">{{ number_format($progressAdj, 2, ',', '.') }}
                                    </td>

                                    <td class="td-desktop-only text-end">
                                        {{ number_format($remaining, 2, ',', '.') }}
                                        <div class="line-progress-wrap">
                                            <div class="line-progress">
                                                <div class="line-progress-bar {{ $lineProgressClass }}"
                                                    style="width: {{ $percentLine }}%;"></div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="td-desktop-only">
                                        <span class="badge pickup-status-badge bg-{{ $lineStatusClass }}">
                                            {{ $lineStatusLabel }}
                                        </span>
                                    </td>

                                    {{-- MOBILE --}}
                                    <td class="td-mobile-only" colspan="11">
                                        <div class="mobile-card-top">
                                            <div class="mobile-card-left">
                                                <span class="mobile-row-index">#{{ $loop->iteration }}</span>
                                                <span
                                                    class="mobile-item-code-chip">{{ $bundle?->finishedItem?->code ?? '-' }}</span>
                                            </div>
                                            <div class="mobile-card-right">
                                                <div class="mobile-status-label">STATUS</div>
                                                <span
                                                    class="badge mobile-status-badge bg-{{ $lineStatusClass }}">{{ $lineStatusLabel }}</span>

                                                <div class="mobile-progress-wrap">
                                                    <div class="mobile-progress-label">
                                                        {{ number_format($processed, 2, ',', '.') }} /
                                                        {{ number_format($qtyPickup, 2, ',', '.') }} pcs
                                                    </div>
                                                    <div class="line-progress">
                                                        <div class="line-progress-bar {{ $lineProgressClass }}"
                                                            style="width: {{ $percentLine }}%;"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="td-mobile-meta">
                                            @if ($bundle?->finishedItem?->name)
                                                <div>{{ $bundle->finishedItem->name }}</div>
                                            @endif
                                            @if ($lot)
                                                <div>LOT: <span class="mono">{{ $lot->code }}</span></div>
                                            @endif
                                            @if ($bundle?->bundle_code)
                                                <div>Bundle: <span class="mono">{{ $bundle->bundle_code }}</span></div>
                                            @endif
                                        </div>

                                        <div class="mobile-qty-row">
                                            Pickup: <span
                                                class="mono">{{ number_format($qtyPickup, 2, ',', '.') }}</span> •
                                            OK: <span class="mono">{{ number_format($returnedOk, 2, ',', '.') }}</span>
                                            •
                                            RJ: <span
                                                class="mono">{{ number_format($returnedReject, 2, ',', '.') }}</span> •
                                            DP: <span class="mono">{{ number_format($directPick, 2, ',', '.') }}</span>
                                            •
                                            Adj: <span
                                                class="mono">{{ number_format($progressAdj, 2, ',', '.') }}</span> •
                                            Sisa: <span class="mono">{{ number_format($remaining, 2, ',', '.') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted small">
                                        Belum ada detail bundle untuk pickup ini.
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rows = Array.from(document.querySelectorAll('.pickup-line-row'));
            const chips = Array.from(document.querySelectorAll('.status-chip'));
            let activeStatus = 'all';

            function applyFilter() {
                rows.forEach(row => {
                    const status = row.dataset.lineStatus || '';
                    row.style.display = (activeStatus === 'all' || status === activeStatus) ? '' : 'none';
                });
            }

            function clearChipStates() {
                chips.forEach(chip => chip.classList.remove('active-all', 'active-not-returned', 'active-partial',
                    'active-full'));
            }

            function activateChip(chip) {
                const status = chip.dataset.status;
                clearChipStates();
                if (status === 'all') chip.classList.add('active-all');
                else if (status === 'not_returned') chip.classList.add('active-not-returned');
                else if (status === 'partial') chip.classList.add('active-partial');
                else if (status === 'full') chip.classList.add('active-full');
            }

            chips.forEach(chip => {
                chip.addEventListener('click', function() {
                    const status = this.dataset.status;
                    if (activeStatus === status && status !== 'all') {
                        activeStatus = 'all';
                        const chipAll = document.getElementById('chip-status-all');
                        if (chipAll) activateChip(chipAll);
                    } else {
                        activeStatus = status;
                        activateChip(this);
                    }
                    applyFilter();
                });
            });

            applyFilter();
        });
    </script>
@endpush
