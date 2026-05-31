{{-- resources/views/production/sewing_pickups/bundles_ready.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Bundles Ready to Pick')

@push('head')
    <style>
        :root {
            --card-radius-lg: 14px;
        }

        .page-wrap {
            max-width: 980px;
            margin-inline: auto;
            padding: .6rem .75rem 6rem;
        }

        /* Background lembut mirip Sewing Pickup */
        body[data-theme="light"] .page-wrap {
            background: linear-gradient(to bottom,
                    #f4f5fb 0,
                    #f7f8fc 30%,
                    #f9fafb 100%);
        }

        .card {
            background: var(--card);
            border-radius: var(--card-radius-lg);
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .card-section {
            padding: .8rem .9rem;
        }

        @media (min-width: 768px) {
            .card-section {
                padding: .9rem 1.1rem;
            }

            .page-wrap {
                padding-top: 1rem;
                padding-bottom: 3.5rem;
            }
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .8rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .table-bundles-ready {
            width: 100%;
        }

        /* ===== HEADER PAGE (tone biru seperti Sewing Pickup) ===== */
        .header-row {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .header-title h1 {
            font-size: 1rem;
            font-weight: 700;
        }

        .header-subtitle {
            font-size: .78rem;
            color: var(--muted);
        }

        .header-icon-circle {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: .6rem;
            background: radial-gradient(circle,
                    rgba(59, 130, 246, 0.22) 0,
                    rgba(59, 130, 246, 0.06) 60%,
                    transparent 100%);
            color: #1d4ed8;
        }

        .btn-header-pill {
            border-radius: 999px;
            padding: .35rem .8rem;
            font-size: .78rem;
            font-weight: 500;
            border-width: 1px;
        }

        .btn-header-muted {
            background: rgba(248, 250, 252, 0.96);
            border-color: rgba(148, 163, 184, 0.5);
            color: #0f172a;
        }

        .btn-header-accent {
            background: rgba(219, 234, 254, 0.96);
            border-color: rgba(59, 130, 246, 0.7);
            color: #1d4ed8;
        }

        /* ===== SUMMARY ===== */
        .summary-row {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            align-items: center;
        }

        .summary-chip {
            border-radius: 999px;
            padding: .12rem .6rem;
            font-size: .72rem;
            display: inline-flex;
            align-items: center;
            gap: .25rem;
        }

        .summary-chip-qty {
            background: rgba(59, 130, 246, 0.12);
            color: #1d4ed8;
            font-weight: 600;
        }

        .summary-chip-count {
            background: rgba(148, 163, 184, 0.18);
            color: #374151;
            font-weight: 600;
        }

        /* ===== TABLE / ROW ===== */
        .bundle-row {
            transition:
                background-color .16s ease,
                box-shadow .18s ease,
                border-color .18s ease,
                transform .08s ease;
            position: relative;
            overflow: hidden;
        }

        .bundle-row td {
            border-top-color: rgba(148, 163, 184, 0.22) !important;
        }

        .bundle-row-base {
            box-shadow: inset 3px 0 0 rgba(59, 130, 246, .35);
            background: rgba(255, 255, 255, 0.98);
            cursor: pointer;
        }

        .bundle-row:hover {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.12) 0,
                    rgba(255, 255, 255, 0.98) 55%);
            box-shadow:
                inset 3px 0 0 rgba(37, 99, 235, 0.95),
                0 0 0 1px rgba(129, 140, 248, 0.35),
                0 10px 22px rgba(15, 23, 42, 0.20);
            transform: translateY(-1px);
        }

        .qty-pill {
            border-radius: 999px;
            padding: .12rem .7rem;
            font-size: .9rem;
            font-weight: 700;
            background: linear-gradient(135deg,
                    rgba(59, 130, 246, 0.26),
                    rgba(129, 140, 248, 0.24));
            color: #0f172a;
            box-shadow:
                0 0 0 1px rgba(148, 163, 184, 0.25),
                0 4px 10px rgba(15, 23, 42, 0.12);
        }

        .qty-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        /* ====== MINI BADGES (Bundle / Lot) ====== */
        .badge-mini {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .08rem .5rem;
            font-size: .68rem;
            border: 1px solid rgba(148, 163, 184, 0.6);
            background: rgba(248, 250, 252, 0.96);
            color: #4b5563;
            gap: .25rem;
            white-space: nowrap;
        }

        .badge-mini span {
            opacity: .7;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .58rem;
        }

        .badge-bundle {
            border-color: rgba(59, 130, 246, 0.7);
            background: rgba(219, 234, 254, 0.9);
            color: #1d4ed8;
        }

        .badge-lot {
            border-color: rgba(129, 140, 248, 0.7);
            background: rgba(224, 231, 255, 0.95);
            color: #4338ca;
        }

        .mobile-muted-soft {
            color: var(--muted);
            font-size: .74rem;
        }

        /* ====== RIPPLE EFFECT ====== */
        .bundle-row::after {
            content: "";
            position: absolute;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.20);
            transform: scale(0);
            opacity: 0;
            pointer-events: none;
            left: var(--ripple-x, 50%);
            top: var(--ripple-y, 50%);
            transition:
                transform .45s ease-out,
                opacity .55s ease-out;
        }

        .bundle-row.ripple-active::after {
            transform: scale(12);
            opacity: 0;
        }

        .bundle-row:active {
            transform: scale(0.985);
            transition: transform .12s ease-out;
        }

        /* ===== MOBILE ===== */
        @media (max-width: 767.98px) {
            .card {
                border-radius: 14px;
            }

            .page-wrap {
                padding-bottom: 6rem;
            }

            .header-row {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-header-pill {
                width: 100%;
                justify-content: center;
            }

            .table-wrap {
                overflow-x: visible;
            }

            .table-bundles-ready {
                border-collapse: separate;
                border-spacing: 0 8px;
                width: 100%;
                table-layout: fixed;
            }

            .table-bundles-ready thead {
                display: none;
            }

            .table-bundles-ready tbody tr {
                display: block;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
                border-radius: 12px;
                border: 1px solid rgba(148, 163, 184, 0.32);
                padding: .6rem .75rem .65rem;
                margin-bottom: .45rem;
                background: rgba(255, 255, 255, 0.98);
                box-shadow:
                    0 8px 20px rgba(15, 23, 42, 0.08),
                    0 0 0 1px rgba(15, 23, 42, 0.02);
                touch-action: pan-y;
            }

            .td-desktop-only {
                display: none !important;
            }

            .td-mobile-only {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }

            .mobile-row-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: .75rem;
                margin-bottom: .2rem;
            }

            .mobile-row-header-left {
                font-size: .82rem;
                display: flex;
                flex-direction: column;
                gap: .06rem;
                min-width: 0;
            }

            .mobile-row-header-topline {
                display: flex;
                align-items: center;
                gap: .35rem;
            }

            .mobile-row-header-left .row-index {
                font-size: .7rem;
                color: var(--muted);
            }

            .mobile-row-header-left .item-code {
                font-size: .96rem;
                font-weight: 700;
                color: #1d4ed8;
                letter-spacing: .08px;
            }

            .mobile-row-header-left .item-name {
                font-size: .78rem;
                color: var(--muted);
            }

            .mobile-row-header-right {
                text-align: right;
                font-size: .76rem;
                min-width: 96px;
            }

            .mobile-row-header-right .qty-label {
                font-size: .64rem;
            }

            .mobile-row-header-right .qty-pill {
                font-size: .96rem;
                padding: .18rem .8rem;
            }

            .mobile-row-meta {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: .35rem;
                margin-top: .12rem;
            }

            .mobile-badge-row {
                display: flex;
                flex-wrap: wrap;
                gap: .25rem;
            }

            .mobile-extra {
                text-align: right;
                font-size: .72rem;
            }

            .mobile-extra .mono {
                font-size: .72rem;
            }

            .btn-pick-inline {
                margin-top: .4rem;
                padding-block: .22rem;
                padding-inline: .7rem;
                font-size: .78rem;
                border-radius: 999px;
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
    <div class="page-wrap py-2 py-md-3">
        @php
            // Hitung hanya bundle yang qty_ready > 0
            $totalBundle = $bundles
                ->filter(function ($b) {
                    $qc = $b->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first();

                    $qtyOk = (float) ($b->qty_cutting_ok ?? ($qc?->qty_ok ?? $b->qty_pcs));
                    $qtyReady = (float) ($b->qty_remaining_for_sewing ?? $qtyOk);

                    return $qtyReady > 0;
                })
                ->count();

            $totalReady = $bundles->sum(function ($b) {
                $qc = $b->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first();

                $qtyOk = (float) ($b->qty_cutting_ok ?? ($qc?->qty_ok ?? $b->qty_pcs));
                $qtyReady = (float) ($b->qty_remaining_for_sewing ?? $qtyOk);

                return $qtyReady > 0 ? $qtyReady : 0;
            });
        @endphp

        {{-- HEADER --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="header-row">
                    <div class="d-flex align-items-center">
                        <div class="header-icon-circle">
                            <i class="bi bi-scissors"></i>
                        </div>
                        <div class="header-title d-flex flex-column gap-1">
                            <h1>Bundles Ready to Pick</h1>
                            <div class="header-subtitle">
                                Hasil QC Cutting yang siap di-pick ke proses jahit.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-md-row gap-2">
                        <a href="{{ route('production.sewing.pickups.index') }}"
                            class="btn btn-sm btn-header-pill btn-header-muted d-flex align-items-center gap-2">
                            <i class="bi bi-list-ul"></i>
                            <span>Daftar Sewing Pickup</span>
                        </a>

                        <a href="{{ route('production.sewing.pickups.create') }}"
                            class="btn btn-sm btn-header-pill btn-header-accent d-flex align-items-center gap-2">
                            <i class="bi bi-plus-circle"></i>
                            <span>Pickup baru (manual)</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- SUMMARY --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="summary-row">
                    <span class="summary-chip summary-chip-qty">
                        Total Qty Ready:
                        <span class="mono">{{ number_format($totalReady, 2, ',', '.') }} pcs</span>
                    </span>

                    <span class="summary-chip summary-chip-count">
                        Bundles Ready:
                        <span class="mono">{{ number_format($totalBundle, 0, ',', '.') }}</span>
                    </span>
                </div>

                <div class="help mt-2">
                    Hanya menampilkan bundle dengan Qty Ready &gt; 0. Tap kartu untuk mulai Sewing Pickup dari bundle
                    tersebut.
                </div>
            </div>
        </div>

        {{-- LIST BUNDLES --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="table-wrap">
                    <table class="table table-sm align-middle mono table-bundles-ready mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Item Jadi</th>
                                <th class="d-none d-md-table-cell">Bundle / Lot</th>
                                <th class="text-end d-none d-md-table-cell">Qty Ready</th>
                                <th style="width: 100px;" class="text-end d-none d-md-table-cell"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $rowNumber = 0; @endphp

                            @foreach ($bundles as $b)
                                @php
                                    $qc = $b->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first();

                                    $qtyOk = (float) ($b->qty_cutting_ok ?? ($qc?->qty_ok ?? $b->qty_pcs));
                                    $qtyReady = (float) ($b->qty_remaining_for_sewing ?? $qtyOk);

                                    if ($qtyReady <= 0) {
                                        continue;
                                    }

                                    $rowNumber++;
                                    $pickUrl = route('production.sewing.pickups.create', ['bundle_id' => $b->id]);

                                    $lot = $b->cuttingJob?->lot;
                                    $finishedItem = $b->finishedItem;
                                    $fabricItem = $lot?->item;
                                @endphp

                                <tr class="bundle-row bundle-row-base" data-href="{{ $pickUrl }}">
                                    {{-- DESKTOP: index & info kecil --}}
                                    <td class="td-desktop-only align-top">
                                        <span class="small text-muted">
                                            {{ $rowNumber }}
                                        </span>
                                    </td>

                                    {{-- DESKTOP: item + detail --}}
                                    <td class="td-desktop-only">
                                        <div>
                                            <strong>{{ $finishedItem?->code ?? '—' }}</strong>
                                            @if ($finishedItem?->name)
                                                <div class="text-muted small">
                                                    {{ $finishedItem->name }}
                                                </div>
                                            @endif
                                        </div>

                                        @if ($fabricItem)
                                            <div class="mobile-muted-soft mt-1">
                                                Kain:
                                                <span class="mono">{{ $fabricItem->code }}</span>
                                                @if ($fabricItem->color)
                                                    • {{ $fabricItem->color }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>

                                    {{-- DESKTOP: bundle & lot badges --}}
                                    <td class="td-desktop-only">
                                        <div class="d-flex flex-wrap gap-1">
                                            @if ($b->bundle_code)
                                                <span class="badge-mini badge-bundle mono">
                                                    <span>Bundle</span>{{ $b->bundle_code }}
                                                </span>
                                            @endif

                                            @if ($lot)
                                                <span class="badge-mini badge-lot mono">
                                                    <span>Lot</span>{{ $lot->code }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- DESKTOP: qty --}}
                                    <td class="td-desktop-only text-end">
                                        <div class="qty-label mb-1">Ready</div>
                                        <span class="qty-pill">
                                            {{ number_format($qtyReady, 2, ',', '.') }}
                                        </span>
                                    </td>

                                    {{-- DESKTOP: tombol pick --}}
                                    <td class="td-desktop-only text-end">
                                        <a href="{{ $pickUrl }}" class="btn btn-sm btn-outline-primary"
                                            onclick="event.stopPropagation();">
                                            Pick
                                        </a>
                                    </td>

                                    {{-- MOBILE CARD --}}
                                    <td class="td-mobile-only" colspan="7">
                                        <div class="mobile-row-header">
                                            <div class="mobile-row-header-left">
                                                <div class="mobile-row-header-topline">
                                                    <span class="row-index">#{{ $rowNumber }}</span>
                                                    <span class="item-code mono">
                                                        {{ $finishedItem?->code ?? '-' }}
                                                    </span>
                                                </div>
                                                @if ($finishedItem?->name)
                                                    <div class="item-name text-truncate">
                                                        {{ $finishedItem->name }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="mobile-row-header-right">
                                                <div class="qty-label mb-1">Ready</div>
                                                <span class="qty-pill">
                                                    {{ number_format($qtyReady, 2, ',', '.') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="mobile-row-meta">
                                            <div class="mobile-badge-row">
                                                @if ($b->bundle_code)
                                                    <span class="badge-mini badge-bundle mono">
                                                        <span>Bundle</span>{{ $b->bundle_code }}
                                                    </span>
                                                @endif

                                                @if ($lot)
                                                    <span class="badge-mini badge-lot mono">
                                                        <span>Lot</span>{{ $lot->code }}
                                                    </span>
                                                @endif
                                            </div>

                                            @if ($fabricItem)
                                                <div class="mobile-extra">
                                                    <span class="mobile-muted-soft">
                                                        Kain:
                                                        <span class="mono">{{ $fabricItem->code }}</span>
                                                        @if ($fabricItem->color)
                                                            • {{ $fabricItem->color }}
                                                        @endif
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="text-end">
                                            <a href="{{ $pickUrl }}"
                                                class="btn btn-outline-primary btn-sm btn-pick-inline"
                                                onclick="event.stopPropagation();">
                                                Pick
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                            @if ($rowNumber === 0)
                                <tr>
                                    <td colspan="7" class="text-center text-muted small py-3">
                                        Tidak ada bundle dengan Qty Ready &gt; 0.
                                    </td>
                                </tr>
                            @endif
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
            const rows = document.querySelectorAll('.bundle-row');

            // Ripple effect lembut (biru)
            rows.forEach(row => {
                row.addEventListener('mousedown', function(e) {
                    const rect = row.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    row.style.setProperty('--ripple-x', `${x}px`);
                    row.style.setProperty('--ripple-y', `${y}px`);

                    row.classList.remove('ripple-active');
                    void row.offsetWidth; // force reflow
                    row.classList.add('ripple-active');
                }, {
                    passive: true
                });
            });

            // Klik row → pergi ke Sewing Pickup bundle
            rows.forEach(function(row) {
                row.addEventListener('click', function(e) {
                    if (e.target.closest('a, button')) {
                        return;
                    }
                    const url = row.dataset.href;
                    if (url) {
                        window.location.href = url;
                    }
                });
            });
        });
    </script>
@endpush
