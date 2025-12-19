{{-- resources/views/production/reports/production_flow_dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Flow')

@push('head')
    <style>
        :root {
            --flow-accent: #0a84ff;
            --flow-soft-bg: rgba(15, 23, 42, 0.02);
        }

        .flow-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 1rem .9rem 4.5rem;
        }

        body[data-theme="light"] .flow-wrap {
            background: radial-gradient(circle at top,
                    rgba(15, 23, 42, 0.03) 0,
                    #f9fafb 55%,
                    #eef2ff 100%);
        }

        body[data-theme="dark"] .flow-wrap {
            background: radial-gradient(circle at top,
                    rgba(15, 23, 42, 0.9) 0,
                    #020617 70%);
        }

        .flow-card {
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, .26);
            background: rgba(255, 255, 255, .9);
            backdrop-filter: blur(10px);
            box-shadow:
                0 18px 40px rgba(15, 23, 42, 0.14),
                0 0 0 1px rgba(148, 163, 184, .12);
        }

        body[data-theme="dark"] .flow-card {
            background: rgba(15, 23, 42, .96);
            border-color: rgba(51, 65, 85, .9);
            box-shadow:
                0 20px 45px rgba(0, 0, 0, 0.7),
                0 0 0 1px rgba(15, 23, 42, .9);
        }

        .flow-card-body {
            padding: 1rem 1.1rem;
        }

        @media (min-width: 768px) {
            .flow-card-body {
                padding: 1.1rem 1.4rem;
            }
        }

        .flow-title {
            font-size: 1.05rem;
            font-weight: 600;
            letter-spacing: .01em;
        }

        .flow-sub {
            font-size: .75rem;
            color: var(--muted-foreground);
        }

        /* Quick actions */
        .flow-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }

        .btn-flow {
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .5);
            padding: .28rem .7rem;
            font-size: .76rem;
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            background: rgba(255, 255, 255, .9);
        }

        .btn-flow-primary {
            border-color: var(--flow-accent);
            background: var(--flow-accent);
            color: #fff;
        }

        body[data-theme="dark"] .btn-flow {
            background: rgba(15, 23, 42, .9);
        }

        .btn-flow i {
            font-size: .85em;
        }

        /* Summary tiles */
        .flow-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .6rem;
            margin-top: .6rem;
        }

        .flow-summary-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .flow-summary-tile {
            border-radius: 14px;
            padding: .75rem .85rem;
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, .22);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
            transition: transform .12s ease-out,
                box-shadow .12s ease-out,
                border-color .12s ease-out,
                background .12s ease-out;
        }

        body[data-theme="dark"] .flow-summary-tile {
            background: rgba(15, 23, 42, .9);
            border-color: rgba(51, 65, 85, .8);
            box-shadow: 0 12px 26px rgba(0, 0, 0, .65);
        }

        .flow-summary-link:focus-visible .flow-summary-tile,
        .flow-summary-link:hover .flow-summary-tile {
            transform: translateY(-1px);
            border-color: rgba(37, 99, 235, .65);
            box-shadow: 0 14px 30px rgba(15, 23, 42, .16);
        }

        .flow-summary-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted-foreground);
            margin-bottom: .1rem;
        }

        .flow-summary-main {
            font-size: 1.28rem;
            font-weight: 700;
        }

        .flow-summary-sub {
            font-size: .74rem;
            color: var(--muted-foreground);
            margin-top: .08rem;
        }

        .flow-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            display: inline-block;
            margin-right: .3rem;
        }

        .dot-cut {
            background: #60a5fa;
        }

        .dot-sew {
            background: #22c55e;
        }

        .dot-fin {
            background: #eab308;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
        }

        /* Stage lists (mobile-first) */
        .flow-section-title {
            font-size: .78rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--muted-foreground);
        }

        .stage-list {
            display: flex;
            flex-direction: column;
            gap: .45rem;
            margin-top: .4rem;
        }

        .stage-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .stage-item {
            border-radius: 12px;
            padding: .55rem .7rem;
            background: rgba(15, 23, 42, 0.015);
            border: 1px solid rgba(148, 163, 184, .3);
            transition: background .12s ease-out, transform .1s ease-out, border-color .12s ease-out;
        }

        body[data-theme="dark"] .stage-item {
            background: rgba(15, 23, 42, .7);
            border-color: rgba(51, 65, 85, .8);
        }

        .stage-link:hover .stage-item,
        .stage-link:focus-visible .stage-item {
            background: rgba(15, 23, 42, 0.02);
            border-color: rgba(148, 163, 184, .6);
            transform: translateY(-1px);
        }

        body[data-theme="dark"] .stage-link:hover .stage-item,
        body[data-theme="dark"] .stage-link:focus-visible .stage-item {
            background: rgba(15, 23, 42, .9);
            border-color: rgba(148, 163, 184, .9);
        }

        .stage-item-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: .8rem;
            margin-bottom: .1rem;
        }

        .stage-item-name {
            font-weight: 600;
        }

        .stage-item-qty {
            font-weight: 600;
            font-size: .82rem;
        }

        .stage-item-sub {
            font-size: .74rem;
            color: var(--muted-foreground);
        }

        /* Aging */
        .badge-age {
            border-radius: 999px;
            padding: .1rem .6rem;
            font-size: .72rem;
        }

        .age-ok {
            background: rgba(16, 185, 129, .16);
            color: #065f46;
        }

        .age-warn {
            background: rgba(245, 158, 11, .16);
            color: #92400e;
        }

        .age-danger {
            background: rgba(239, 68, 68, .16);
            color: #b91c1c;
        }

        body[data-theme="dark"] .age-ok {
            color: #bbf7d0;
        }

        body[data-theme="dark"] .age-warn {
            color: #fed7aa;
        }

        body[data-theme="dark"] .age-danger {
            color: #fecaca;
        }

        /* Layout tweaks */
        @media (max-width: 767.98px) {
            .flow-wrap {
                padding-inline: .85rem;
                padding-bottom: 6rem;
            }

            .flow-actions {
                justify-content: flex-end;
            }

            .flow-summary-main {
                font-size: 1rem;
            }

            .flow-summary-sub {
                font-size: .7rem;
            }

            .flow-summary-tile {
                padding: .6rem .55rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $role = $user->role ?? null;

        $wipCutTotal = $summary['wip_cut_total'] ?? 0;
        $wipSewTotal = $summary['wip_sew_total'] ?? 0;
        $wipFinTotal = $summary['wip_fin_total'] ?? 0;

        $wipCutBundles = $summary['bundle_count_wip_cut'] ?? 0;
        $wipSewBundles = $summary['bundle_count_wip_sew'] ?? 0;
        $wipFinBundles = $summary['bundle_count_wip_fin'] ?? 0;

        $cutItems = collect($wipCutItems ?? [])->take(6);
        $sewItems = collect($wipSewItems ?? [])->take(6);
        $finItems = collect($wipFinItems ?? [])->take(6);
        $aging = collect($agingBundles ?? [])->take(15);
    @endphp

    <div class="flow-wrap">

        {{-- HEADER + QUICK ACTIONS --}}
        <div class="flow-card mb-3">
            <div class="flow-card-body">
                <div class="d-flex justify-content-center align-items-start gap-3">
                    <div>
                        <div class="flow-title mb-1 ">
                            Arus Produksi
                        </div>
                        <div class="flow-sub">
                            {{ now()->format('d M Y • H:i') }}
                        </div>
                    </div>

                    {{-- QUICK ACTIONS --}}

                </div>
            </div>
        </div>

        {{-- SUMMARY TILES (CLICKABLE CARD) --}}
        <div class="flow-summary-grid mb-3">

            {{-- WIP-CUT --}}
            <a href="{{ route('production.cutting_jobs.index') }}" class="flow-summary-link">
                <div class="flow-summary-tile">
                    <div class="flow-summary-label">
                        <span class="flow-dot dot-cut"></span>CUTTINGAN
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <div class="flow-summary-main mono">
                                {{ number_format($wipCutTotal, 0, ',', '.') }} <span class="fs-6">pcs</span>
                            </div>
                            <div class="flow-summary-sub">
                                {{ number_format($wipCutBundles, 0, ',', '.') }} bundle
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </div>
                </div>
            </a>

            {{-- WIP-SEW --}}
            <a href="{{ route('production.sewing_pickups.index') }}" class="flow-summary-link">
                <div class="flow-summary-tile">
                    <div class="flow-summary-label">
                        <span class="flow-dot dot-sew"></span>JAHIT
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <div class="flow-summary-main mono">
                                {{ number_format($wipSewTotal, 0, ',', '.') }} <span class="fs-6">pcs</span>
                            </div>
                            <div class="flow-summary-sub">
                                {{ number_format($wipSewBundles, 0, ',', '.') }} bundle
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </div>
                </div>
            </a>

            {{-- WIP-FIN --}}
            <a href="{{ route('production.finishing_jobs.index') }}" class="flow-summary-link">
                <div class="flow-summary-tile">
                    <div class="flow-summary-label">
                        <span class="flow-dot dot-fin"></span>FINISHING
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <div class="flow-summary-main mono">
                                {{ number_format($wipFinTotal, 0, ',', '.') }} <span class="fs-6">pcs</span>
                            </div>
                            <div class="flow-summary-sub">
                                {{ number_format($wipFinBundles, 0, ',', '.') }} bundle
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </div>
                </div>
            </a>
        </div>

        {{-- PER STAGE – TOP ITEMS --}}
        <div class="flow-card mb-3">
            <div class="flow-card-body">
                <div class="row g-3">

                    {{-- CUT --}}
                    <div class="col-12 col-md-4">
                        <div class="flow-section-title mb-1">
                            SISA POTONG CUTTING
                        </div>
                        <div class="stage-list">
                            @forelse($cutItems as $row)
                                <a href="{{ route('inventory.stocks.items', ['mode' => 'prod']) }}" class="stage-link">
                                    <div class="stage-item">
                                        <div class="stage-item-top">
                                            <span class="stage-item-name">
                                                {{ $row->item_code ?? '-' }}
                                            </span>
                                            <span class="stage-item-qty mono">
                                                {{ number_format($row->qty_wip ?? 0, 0, ',', '.') }}
                                            </span>
                                        </div>
                                        <div class="stage-item-sub">
                                            {{ $row->item_name ?? '' }}
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="stage-item">
                                    <div class="stage-item-top">
                                        <span class="stage-item-name text-muted">
                                            Belum ada WIP-CUT aktif
                                        </span>
                                    </div>
                                    <div class="stage-item-sub">
                                        Bundle akan muncul di sini ketika proses cutting berjalan.
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- SEW --}}
                    <div class="col-12 col-md-4">
                        <div class="flow-section-title mb-1">
                            SISA SEDANG JAHIT
                        </div>
                        <div class="stage-list">
                            @forelse($sewItems as $row)
                                <a href="{{ route('inventory.stocks.items', ['mode' => 'prod']) }}" class="stage-link">
                                    <div class="stage-item">
                                        <div class="stage-item-top">
                                            <span class="stage-item-name">
                                                {{ $row->item_code ?? '-' }}
                                            </span>
                                            <span class="stage-item-qty mono">
                                                {{ number_format($row->qty_wip ?? 0, 0, ',', '.') }}
                                            </span>
                                        </div>
                                        <div class="stage-item-sub">
                                            {{ $row->item_name ?? '' }}
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="stage-item">
                                    <div class="stage-item-top">
                                        <span class="stage-item-name text-muted">
                                            Belum ada WIP-SEW aktif
                                        </span>
                                    </div>
                                    <div class="stage-item-top">
                                        <span class="stage-item-name text-muted">
                                            Belum ada WIP-SEW aktif
                                        </span>
                                    </div>
                                    {{-- <div class="stage-item-sub">
                                        Data akan muncul ketika ada bundle di penjahit (pickup sudah dibuat).
                                    </div> --}}
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- FIN --}}
                    <div class="col-12 col-md-4">
                        <div class="flow-section-title mb-1">
                            BELUM FINISHING
                        </div>
                        <div class="stage-list">
                            @forelse($finItems as $row)
                                <a href="{{ route('inventory.stocks.items', ['mode' => 'prod']) }}" class="stage-link">
                                    <div class="stage-item">
                                        <div class="stage-item-top">
                                            <span class="stage-item-name">
                                                {{ $row->item_code ?? '-' }}
                                            </span>
                                            <span class="stage-item-qty mono">
                                                {{ number_format($row->qty_wip ?? 0, 0, ',', '.') }}
                                            </span>
                                        </div>
                                        <div class="stage-item-sub">
                                            {{ $row->item_name ?? '' }}
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="stage-item">
                                    <div class="stage-item-top">
                                        <span class="stage-item-name text-muted">
                                            Belum ada WIP-FIN aktif
                                        </span>
                                    </div>
                                    <div class="stage-item-sub">
                                        Akan terisi ketika ada Sewing Return yang masuk ke WIP-FIN.
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- AGING BUNDLE --}}
        <div class="flow-card">
            <div class="flow-card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="flow-section-title">
                        Aging Bundle
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge-age age-ok">≤ 2</span>
                        <span class="badge-age age-warn">3–7</span>
                        <span class="badge-age age-danger">&gt; 7</span>
                    </div>
                </div>

                <div class="stage-list">
                    @forelse($aging as $row)
                        @php
                            $age = (int) ($row->age_days ?? 0);
                            $qty = (float) ($row->qty_wip ?? 0);
                            $ageClass = $age > 7 ? 'age-danger' : ($age >= 3 ? 'age-warn' : 'age-ok');
                        @endphp
                        <div class="stage-item">
                            {{-- Fokus ke ITEM --}}
                            <div class="stage-item-top">
                                <div>
                                    <div class="stage-item-name">
                                        {{ $row->item_code ?? '-' }}
                                        @if (!empty($row->item_name))
                                            — {{ $row->item_name }}
                                        @endif
                                    </div>
                                    @if (!empty($row->bundle_code) || !empty($row->cutting_code))
                                        <div class="stage-item-sub mt-1">
                                            Bundle:
                                            {{ $row->bundle_code ?? 'Bundle #' . ($row->bundle_id ?? '?') }}
                                            @if (!empty($row->cutting_code))
                                                • Job: {{ $row->cutting_code }}
                                            @endif
                                        </div>
                                    @endif
                                    @if (!empty($row->lot_code) || !empty($row->lot_item_code))
                                        <div class="stage-item-sub">
                                            LOT: {{ $row->lot_code ?? '-' }}
                                            @if (!empty($row->lot_item_code))
                                                · {{ $row->lot_item_code }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <span class="badge-age {{ $ageClass }} mono">
                                    {{ $age }}h
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="stage-item-sub">
                                    Qty WIP
                                </div>
                                <div class="mono" style="font-size:.82rem;">
                                    {{ number_format($qty, 0, ',', '.') }} pcs
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted small text-center py-1">
                            Tidak ada bundle WIP aktif.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
@endsection
