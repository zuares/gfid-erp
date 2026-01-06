{{-- resources/views/production/sewing_pickups/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Pickup ' . $pickup->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 3rem
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .06), 0 0 0 1px rgba(15, 23, 42, .02)
        }

        .card-section {
            padding: .85rem .95rem
        }

        @media(min-width:768px) {
            .card-section {
                padding: 1rem 1.2rem
            }
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"
        }

        .muted {
            color: var(--muted)
        }

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .6rem;
            font-size: .72rem
        }

        .table-wrap {
            overflow-x: auto
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            flex-wrap: wrap
        }

        .header-main {
            min-width: 0
        }

        .header-main h1 {
            font-size: 1rem;
            font-weight: 900;
            margin: 0
        }

        .header-sub {
            margin-top: .25rem;
            font-size: .85rem;
            color: var(--muted)
        }

        .header-actions {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
            justify-content: flex-end
        }

        .status-badge-main {
            font-size: .75rem;
            padding: .35rem .75rem;
            border-radius: 999px
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
            color: #2563eb
        }

        /* Summary */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem
        }

        @media(max-width:767.98px) {
            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }
        }

        .summary-item {
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 12px;
            padding: .65rem .75rem;
            background: rgba(248, 250, 252, .55)
        }

        body[data-theme="dark"] .summary-item {
            background: rgba(15, 23, 42, .35);
            border-color: rgba(148, 163, 184, .25)
        }

        .summary-label {
            font-size: .72rem;
            color: var(--muted);
            letter-spacing: .06em;
            text-transform: uppercase
        }

        .summary-value {
            font-weight: 900;
            margin-top: .15rem
        }

        .overall-progress-wrap {
            margin-top: .85rem
        }

        .overall-progress-label {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            font-size: .75rem;
            color: var(--muted);
            margin-bottom: .35rem
        }

        .overall-progress {
            height: .6rem;
            border-radius: 999px;
            overflow: hidden;
            background: linear-gradient(to right, rgba(226, 232, 240, .95), rgba(209, 213, 219, .98))
        }

        .overall-progress-bar {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 45%, #15803d 100%);
            box-shadow: 0 0 0 1px rgba(22, 163, 74, .35), 0 4px 10px rgba(22, 163, 74, .35);
            transition: width .25s ease-out
        }

        .overall-progress-bar.is-empty {
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 40%, #9ca3af 100%);
            box-shadow: 0 0 0 1px rgba(148, 163, 184, .4), 0 3px 7px rgba(148, 163, 184, .25)
        }

        /* Filter chips */
        .status-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            align-items: center;
            margin-bottom: .5rem
        }

        .status-chip {
            border-radius: 999px;
            padding: .18rem .7rem;
            font-size: .74rem;
            font-weight: 800;
            border: 1px solid rgba(148, 163, 184, .6);
            background: rgba(248, 250, 252, .96);
            color: #4b5563;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            transition: all .15s ease
        }

        .status-chip-count {
            font-variant-numeric: tabular-nums
        }

        .status-chip:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(148, 163, 184, .35), 0 0 0 1px rgba(148, 163, 184, .2)
        }

        .status-chip.active-all {
            border-color: rgba(59, 130, 246, .8);
            background: rgba(239, 246, 255, .98);
            color: #1d4ed8
        }

        .status-chip.active-not-returned {
            border-color: rgba(148, 163, 184, .9);
            background: rgba(249, 250, 251, .98);
            color: #111827
        }

        .status-chip.active-partial {
            border-color: rgba(245, 158, 11, .9);
            background: rgba(255, 251, 235, .98);
            color: #92400e
        }

        .status-chip.active-full {
            border-color: rgba(22, 163, 74, .9);
            background: rgba(240, 253, 244, .98);
            color: #166534
        }

        .status-chip.active-void {
            border-color: rgba(239, 68, 68, .9);
            background: rgba(254, 242, 242, .98);
            color: #b91c1c
        }

        /* Lines */
        .pickup-line-row td {
            border-top-color: rgba(148, 163, 184, .24) !important
        }

        .pickup-status-badge {
            font-size: .7rem;
            border-radius: 999px;
            padding: .14rem .55rem
        }

        .line-progress {
            width: 100%;
            height: .32rem;
            border-radius: 999px;
            background: rgba(229, 231, 235, .95);
            overflow: hidden
        }

        .line-progress-bar {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #22c55e 0%, #16a34a 60%, #15803d 100%)
        }

        .line-progress-bar.partial {
            background: linear-gradient(90deg, #f59e0b 0%, #eab308 60%, #d97706 100%)
        }

        .line-progress-bar.empty {
            background: linear-gradient(90deg, #e5e7eb 0%, #d1d5db 50%)
        }

        /* Void banner */
        .void-banner {
            border-radius: 14px;
            border: 1px solid rgba(239, 68, 68, .35);
            background: linear-gradient(135deg, rgba(239, 68, 68, .16) 0%, rgba(255, 255, 255, 0) 70%);
            padding: .75rem .9rem;
            margin-top: .85rem;
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: flex-start
        }

        body[data-theme="dark"] .void-banner {
            background: linear-gradient(135deg, rgba(239, 68, 68, .22) 0%, rgba(15, 23, 42, 0) 70%);
            border-color: rgba(239, 68, 68, .45)
        }

        .void-title {
            font-weight: 900;
            color: #b91c1c;
            letter-spacing: .04em;
            text-transform: uppercase;
            font-size: .78rem
        }

        .void-meta {
            margin-top: .25rem;
            font-size: .85rem;
            color: rgba(75, 85, 99, .95)
        }

        body[data-theme="dark"] .void-meta {
            color: rgba(226, 232, 240, .9)
        }

        .void-meta .kv {
            display: flex;
            gap: .4rem;
            flex-wrap: wrap
        }

        .void-meta .k {
            color: var(--muted)
        }

        .void-actions {
            display: flex;
            gap: .5rem;
            align-items: center
        }

        /* Mobile */
        @media(max-width:767.98px) {
            .header-actions {
                justify-content: flex-start
            }

            .status-chip-row {
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: .15rem
            }

            .status-chip-row::-webkit-scrollbar {
                height: 4px
            }

            .status-chip-row::-webkit-scrollbar-thumb {
                background: rgba(148, 163, 184, .6);
                border-radius: 999px
            }

            .table-pickup-lines {
                border-collapse: separate;
                border-spacing: 0 10px
            }

            .table-pickup-lines thead {
                display: none
            }

            .table-pickup-lines tbody tr {
                display: block;
                border-radius: 14px;
                border: 1px solid rgba(148, 163, 184, .25);
                padding: .55rem .75rem .65rem;
                margin-bottom: .5rem;
                background: #fff;
                box-shadow: 0 10px 22px rgba(15, 23, 42, .08), 0 0 0 1px rgba(15, 23, 42, .02)
            }

            body[data-theme="dark"] .table-pickup-lines tbody tr {
                background: rgba(15, 23, 42, .96);
                border-color: rgba(30, 64, 175, .55);
                box-shadow: 0 12px 28px rgba(15, 23, 42, .9), 0 0 0 1px rgba(15, 23, 42, .9)
            }

            .table-pickup-lines td {
                display: block;
                border: none !important;
                padding: .08rem 0
            }

            .td-desktop-only {
                display: none !important
            }

            .mobile-card-top {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: .7rem;
                margin-bottom: .2rem
            }

            .mobile-row-index {
                font-size: .7rem;
                font-weight: 800;
                letter-spacing: .12em;
                color: var(--muted)
            }

            .mobile-item-code-chip {
                display: inline-flex;
                align-items: center;
                padding: .26rem .8rem;
                border-radius: 999px;
                font-size: 1.05rem;
                font-weight: 900;
                white-space: nowrap;
                background: rgba(37, 99, 246, .1);
                color: #1d4ed8;
                border: 1px solid rgba(37, 99, 246, .35)
            }

            body[data-theme="dark"] .mobile-item-code-chip {
                background: rgba(37, 99, 235, .3);
                color: #e5edff;
                border-color: rgba(129, 140, 248, .8)
            }

            .mobile-card-right {
                text-align: right;
                min-width: 140px
            }

            .mobile-status-label {
                font-size: .62rem;
                letter-spacing: .16em;
                color: var(--muted);
                margin-bottom: .12rem
            }

            .mobile-status-badge {
                font-size: .7rem;
                border-radius: 999px;
                padding: .16rem .6rem
            }

            .mobile-progress-label {
                font-size: .7rem;
                color: var(--muted)
            }

            .td-mobile-meta {
                font-size: .74rem;
                color: var(--muted);
                margin-top: .1rem
            }

            .mobile-qty-row {
                margin-top: .15rem;
                font-size: .78rem
            }
        }

        @media(min-width:768px) {
            .td-mobile-only {
                display: none !important
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
            'partial' => ['label' => 'PARTIAL', 'class' => 'warning'],
            'completed' => ['label' => 'COMPLETED', 'class' => 'success'],
            'void' => ['label' => 'VOID', 'class' => 'danger'],
        ];

        $cfg = $statusMap[$pickup->status] ?? ['label' => strtoupper($pickup->status ?? '-'), 'class' => 'secondary'];

        $lines = $pickup->lines ?? collect();
        $epsilon = 0.000001;

        // Summary
        $totalBundles = (int) $lines->count();
        $totalQtyPickup = (float) $lines->sum(fn($l) => (float) ($l->qty_bundle ?? 0));
        $totalReturnOk = (float) $lines->sum(fn($l) => (float) ($l->qty_returned_ok ?? 0));
        $totalReturnReject = (float) $lines->sum(fn($l) => (float) ($l->qty_returned_reject ?? 0));
        $totalDirectPick = (float) $lines->sum(fn($l) => (float) ($l->qty_direct_picked ?? 0));
        $totalProgressAdj = (float) $lines->sum(fn($l) => (float) ($l->qty_progress_adjusted ?? 0));

        $totalProcessed = $totalReturnOk + $totalReturnReject + $totalDirectPick + $totalProgressAdj;
        $totalRemaining = max($totalQtyPickup - $totalProcessed, 0);
        $overallPercent = $totalQtyPickup > 0 ? min(100, max(0, ($totalProcessed / $totalQtyPickup) * 100)) : 0;

        // Counts (termasuk void)
        $notReturnedCount = 0;
        $partialReturnedCount = 0;
        $fullReturnedCount = 0;
        $voidLineCount = 0;

        foreach ($lines as $l) {
            if (($l->status ?? null) === 'void') {
                $voidLineCount++;
                continue;
            }

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

        // Header void only if no process at all
        $canVoid = $pickup->status !== 'void' && $totalProcessed <= $epsilon;

        // Safe voided_at text (hindari Carbon::parse)
        $voidedAtText = optional($pickup->voided_at)->format('Y-m-d H:i');
    @endphp

    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card mb-3">
            <div class="card-section">
                <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                    <div class="header-main">
                        <div class="d-flex align-items-center">
                            <div class="header-icon-circle"><i class="bi bi-scissors"></i></div>
                            <div>
                                <h1>Sewing Pickup: {{ $pickup->code }}</h1>
                                <div class="header-sub">
                                    {{ $pickup->date?->format('Y-m-d') ?? $pickup->date }}
                                    • {{ $pickup->warehouse?->code ?? '-' }} — {{ $pickup->warehouse?->name ?? '-' }}
                                    @if ($pickup->operator)
                                        • <span class="mono">{{ $pickup->operator->code }} —
                                            {{ $pickup->operator->name }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($pickup->notes)
                            <div class="mt-2 small muted">“{{ $pickup->notes }}”</div>
                        @endif
                    </div>

                    <div class="header-actions">
                        <span class="badge bg-{{ $cfg['class'] }} status-badge-main">{{ $cfg['label'] }}</span>

                        <a href="{{ route('production.sewing.pickups.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>

                        {{-- Header VOID (opsional) --}}
                        @if ($pickup->status !== 'void')
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                data-bs-target="#modalVoidPickup" {{ $canVoid ? '' : 'disabled' }}>
                                <i class="bi bi-x-octagon me-1"></i> VOID
                            </button>
                        @endif
                    </div>
                </div>

                {{-- VOID banner --}}
                @if ($pickup->status === 'void')
                    <div class="void-banner">
                        <div>
                            <div class="void-title"><i class="bi bi-exclamation-octagon me-1"></i> VOID</div>
                            <div class="void-meta">
                                <div class="kv"><span class="k">Alasan:</span> <span
                                        class="mono">{{ $pickup->void_reason ?? '-' }}</span></div>
                                <div class="kv"><span class="k">Waktu:</span> <span
                                        class="mono">{{ $voidedAtText ?: '-' }}</span></div>
                                <div class="kv"><span class="k">User ID:</span> <span
                                        class="mono">{{ $pickup->voided_by ?? '-' }}</span></div>
                            </div>
                        </div>
                        <div class="void-actions">
                            <span class="badge bg-danger">DOKUMEN TIDAK AKTIF</span>
                        </div>
                    </div>
                @endif

                {{-- Hint ringkas --}}
                @if ($pickup->status !== 'void' && !$canVoid)
                    <div class="mt-2 small muted">
                        Header tidak bisa VOID (sudah ada proses). <span class="mono">Processed:
                            {{ number_format($totalProcessed, 2, ',', '.') }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- SUMMARY --}}
        <div class="card mb-3">
            <div class="card-section">
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Bundles</div>
                        <div class="summary-value mono">{{ number_format($totalBundles, 0, ',', '.') }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Pickup (pcs)</div>
                        <div class="summary-value mono">{{ number_format($totalQtyPickup, 2, ',', '.') }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Return OK</div>
                        <div class="summary-value mono">{{ number_format($totalReturnOk, 2, ',', '.') }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Return RJ</div>
                        <div class="summary-value mono">{{ number_format($totalReturnReject, 2, ',', '.') }}</div>
                    </div>
                </div>

                <div class="overall-progress-wrap">
                    <div class="overall-progress-label">
                        <span>Progress</span>
                        <span class="mono">
                            {{ number_format($overallPercent, 1, ',', '.') }}% •
                            {{ number_format($totalRemaining, 2, ',', '.') }} sisa
                        </span>
                    </div>
                    <div class="overall-progress">
                        <div class="overall-progress-bar {{ $overallPercent <= 0 ? 'is-empty' : '' }}"
                            style="width: {{ $overallPercent }}%;"></div>
                    </div>

                    <div class="mt-2 d-flex flex-wrap gap-2 small muted">
                        <span>Belum: <span class="mono">{{ $notReturnedCount }}</span></span>
                        <span>• Parsial: <span class="mono">{{ $partialReturnedCount }}</span></span>
                        <span>• Penuh: <span class="mono">{{ $fullReturnedCount }}</span></span>
                        <span>• VOID: <span class="mono">{{ $voidLineCount }}</span></span>
                        <span>• DP: <span class="mono">{{ number_format($totalDirectPick, 2, ',', '.') }}</span></span>
                        <span>• Adj: <span class="mono">{{ number_format($totalProgressAdj, 2, ',', '.') }}</span></span>
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
                        <button type="button" class="status-chip active-all" data-status="all" id="chip-status-all">
                            Semua
                        </button>
                        <button type="button" class="status-chip" data-status="not_returned" id="chip-status-not-returned">
                            Belum <span class="status-chip-count mono">{{ $notReturnedCount }}</span>
                        </button>
                        <button type="button" class="status-chip" data-status="partial" id="chip-status-partial">
                            Parsial <span class="status-chip-count mono">{{ $partialReturnedCount }}</span>
                        </button>
                        <button type="button" class="status-chip" data-status="full" id="chip-status-full">
                            Penuh <span class="status-chip-count mono">{{ $fullReturnedCount }}</span>
                        </button>
                        <button type="button" class="status-chip" data-status="void" id="chip-status-void">
                            VOID <span class="status-chip-count mono">{{ $voidLineCount }}</span>
                        </button>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="table table-sm align-middle mono mb-0 table-pickup-lines">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th style="width:130px;">Bundle</th>
                                <th style="width:160px;">Item</th>
                                <th style="width:170px;">Lot</th>
                                <th class="text-end" style="width:110px;">Qty</th>
                                <th class="text-end" style="width:90px;">OK</th>
                                <th class="text-end" style="width:90px;">RJ</th>
                                <th class="text-end" style="width:90px;">DP</th>
                                <th class="text-end" style="width:90px;">Adj</th>
                                <th class="text-end" style="width:120px;">Sisa</th>
                                <th style="width:110px;">Status</th>
                                <th style="width:90px;">Aksi</th>
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

                                    $isVoidLine = ($line->status ?? null) === 'void';

                                    if ($isVoidLine) {
                                        $lineStatusLabel = 'VOID';
                                        $lineStatusClass = 'danger';
                                        $lineProgressClass = 'empty';
                                        $lineStatusKey = 'void';
                                    } else {
                                        if ($processed <= $epsilon) {
                                            $lineStatusLabel = 'Belum';
                                            $lineStatusClass = 'secondary';
                                            $lineProgressClass = 'empty';
                                            $lineStatusKey = 'not_returned';
                                        } elseif ($remaining > $epsilon) {
                                            $lineStatusLabel = 'Parsial';
                                            $lineStatusClass = 'warning';
                                            $lineProgressClass = 'partial';
                                            $lineStatusKey = 'partial';
                                        } else {
                                            $lineStatusLabel = 'Penuh';
                                            $lineStatusClass = 'success';
                                            $lineProgressClass = '';
                                            $lineStatusKey = 'full';
                                        }
                                    }

                                    $usedLine = $returnedOk + $returnedReject + $directPick + $progressAdj;
                                    $canVoidLine = !$isVoidLine && $pickup->status !== 'void' && $usedLine <= $epsilon;

                                    $modalId = 'modalVoidLine-' . $line->id;

                                    // safe formatted timestamp (kalau kamu sudah tambahin field line voided_at)
                                    $lineVoidedAtText = optional($line->voided_at ?? null)->format('Y-m-d H:i');
                                @endphp

                                <tr class="pickup-line-row" data-line-status="{{ $lineStatusKey }}">
                                    <td class="td-desktop-only">{{ $loop->iteration }}</td>

                                    <td class="td-desktop-only">{{ $bundle?->bundle_code ?? '-' }}</td>

                                    <td class="td-desktop-only">
                                        {{ $bundle?->finishedItem?->code ?? '-' }}
                                        @if ($bundle?->finishedItem?->name)
                                            <div class="small muted">{{ $bundle->finishedItem->name }}</div>
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
                                        <div class="mt-1">
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

                                        @if ($isVoidLine)
                                            <div class="small muted mt-1">
                                                <span class="mono">{{ $line->void_reason ?? '-' }}</span>
                                                @if ($lineVoidedAtText)
                                                    • <span class="mono">{{ $lineVoidedAtText }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Desktop action --}}
                                    <td class="td-desktop-only">
                                        @if ($isVoidLine)
                                            <span class="badge bg-danger">VOID</span>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal" data-bs-target="#{{ $modalId }}"
                                                {{ $canVoidLine ? '' : 'disabled' }}>
                                                VOID
                                            </button>
                                        @endif

                                        {{-- Modal VOID Line --}}
                                        <div class="modal fade" id="{{ $modalId }}" tabindex="-1"
                                            aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST"
                                                        action="{{ route('production.sewing.pickups.lines.void', [$pickup, $line]) }}">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">
                                                                <i class="bi bi-x-octagon text-danger me-1"></i> Void Line
                                                                #{{ $line->id }}
                                                            </h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>

                                                        <div class="modal-body">
                                                            <div class="small text-muted mb-2">
                                                                Bundle: <span
                                                                    class="mono">{{ $bundle?->bundle_code ?? '-' }}</span>
                                                                • Qty: <span
                                                                    class="mono">{{ number_format($qtyPickup, 2, ',', '.') }}</span>
                                                                • Item: <span
                                                                    class="mono">{{ $bundle?->finishedItem?->code ?? '-' }}</span>
                                                            </div>

                                                            <label class="form-label small mb-1">Alasan (wajib)</label>
                                                            <input name="reason" required maxlength="150"
                                                                class="form-control"
                                                                placeholder="Contoh: salah bundle / input dobel"
                                                                {{ $canVoidLine ? '' : 'disabled' }}>

                                                            @if (!$canVoidLine)
                                                                <div class="alert alert-warning mt-3 mb-0">
                                                                    Line tidak bisa VOID karena sudah ada proses.
                                                                    <div class="mt-1"><span class="mono">Processed:
                                                                            {{ number_format($usedLine, 2, ',', '.') }}</span>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-outline-secondary"
                                                                data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-danger"
                                                                {{ $canVoidLine ? '' : 'disabled' }}
                                                                onclick="return confirm('Yakin VOID line ini? Stok akan dibalik ke WIP-CUT.')">
                                                                Void Line
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- MOBILE card --}}
                                    <td class="td-mobile-only" colspan="12">
                                        <div class="mobile-card-top">
                                            <div>
                                                <div class="mobile-row-index">#{{ $loop->iteration }}</div>
                                                <div class="mobile-item-code-chip">
                                                    {{ $bundle?->finishedItem?->code ?? '-' }}</div>
                                            </div>
                                            <div class="mobile-card-right">
                                                <div class="mobile-status-label">STATUS</div>
                                                <span
                                                    class="badge mobile-status-badge bg-{{ $lineStatusClass }}">{{ $lineStatusLabel }}</span>

                                                <div class="mt-2">
                                                    <div class="mobile-progress-label">
                                                        {{ number_format($processed, 2, ',', '.') }} /
                                                        {{ number_format($qtyPickup, 2, ',', '.') }}
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

                                            @if ($isVoidLine)
                                                <div class="mt-2">
                                                    <span class="badge bg-danger">VOID</span>
                                                    <span class="mono ms-2">{{ $line->void_reason ?? '-' }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="mobile-qty-row">
                                            Q: <span class="mono">{{ number_format($qtyPickup, 2, ',', '.') }}</span> •
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

                                        @if (!$isVoidLine)
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" data-bs-target="#{{ $modalId }}"
                                                    {{ $canVoidLine ? '' : 'disabled' }}>
                                                    VOID Line
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center text-muted small">Belum ada detail bundle.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>

    {{-- MODAL VOID HEADER (optional) --}}
    @if ($pickup->status !== 'void')
        <div class="modal fade" id="modalVoidPickup" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('production.sewing.pickups.void', $pickup) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-x-octagon text-danger me-1"></i> Void Sewing Pickup
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="small text-muted mb-2">
                                Stok akan dibalik ke <span class="mono">WIP-CUT</span>.
                            </div>

                            <div class="mb-2">
                                <label class="form-label small mb-1">Alasan (wajib)</label>
                                <input name="reason" required maxlength="150" class="form-control"
                                    placeholder="Contoh: salah input bundle / salah operator"
                                    {{ $canVoid ? '' : 'disabled' }}>
                            </div>

                            @if (!$canVoid)
                                <div class="alert alert-warning mb-0">
                                    Header tidak bisa VOID karena sudah ada proses.
                                    <div class="mt-1">Processed: <span
                                            class="mono">{{ number_format($totalProcessed, 2, ',', '.') }}</span></div>
                                </div>
                            @endif
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger" {{ $canVoid ? '' : 'disabled' }}>
                                Ya, Void
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
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
                chips.forEach(chip => chip.classList.remove(
                    'active-all', 'active-not-returned', 'active-partial', 'active-full', 'active-void'
                ));
            }

            function activateChip(chip) {
                const status = chip.dataset.status;
                clearChipStates();
                if (status === 'all') chip.classList.add('active-all');
                else if (status === 'not_returned') chip.classList.add('active-not-returned');
                else if (status === 'partial') chip.classList.add('active-partial');
                else if (status === 'full') chip.classList.add('active-full');
                else if (status === 'void') chip.classList.add('active-void');
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
