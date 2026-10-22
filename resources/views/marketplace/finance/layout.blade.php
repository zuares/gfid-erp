@extends('layouts.app')

@section('title', 'Marketplace Finance')

@section('content')
    <style>
        .finance-page { --finance-ink: #172033; --finance-muted: #64748b; --finance-line: #e7ebf0; --finance-soft: #f8fafc; --finance-blue: #2563eb; --finance-brand: #5b5ce2; --finance-accent: #13b8c4; width: 100%; max-width: 1760px; margin: 0 auto; padding-top: 1.75rem !important; padding-bottom: 2.5rem !important; color: var(--finance-ink); }
        .finance-page .finance-card { border: 1px solid var(--finance-line); border-radius: 14px; background: #fff; box-shadow: 0 1px 2px rgba(15, 23, 42, .035); }
        .finance-page .card-body { padding: 1.15rem 1.25rem; }
        .finance-page .card-header { padding: .85rem 1.25rem; }
        .finance-page .finance-number { font-size: 1.35rem; font-weight: 750; letter-spacing: -.02em; }
        .finance-page .table { --bs-table-hover-bg: #f8fafc; }
        .finance-page .table th { white-space: nowrap; font-size: .72rem; letter-spacing: .04em; text-transform: uppercase; color: var(--finance-muted); background: var(--finance-soft); border-bottom-color: var(--finance-line); }
        .finance-page .table td { vertical-align: middle; border-color: #eef1f4; }
        .finance-page .table thead th:first-child { padding-left: 1.25rem; }
        .finance-page .table tbody td:first-child { padding-left: 1.25rem; }
        .finance-page .finance-nav { overflow-x: auto; white-space: nowrap; }
        .finance-page .finance-nav .nav-link { color: #64748b; border-radius: 10px; font-weight: 600; }
        .finance-page .finance-nav .nav-link:hover { color: #1d4ed8; background: #eff6ff; }
        .finance-page .finance-nav .nav-link.active { color: #1d4ed8; background: #eff6ff; }
        .finance-page .finance-kpi { position: relative; overflow: hidden; min-height: 116px; }
        .finance-page .finance-kpi .card-body { padding: 1rem 1.1rem; }
        .finance-page .finance-kpi::after { content: ""; position: absolute; width: 92px; height: 92px; right: -30px; bottom: -42px; border-radius: 50%; background: currentColor; opacity: .06; }
        .finance-page .finance-kpi-icon { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; background: var(--finance-accent, #2563eb); color: #fff; }
        .finance-page .finance-kpi-icon i { color: #fff; }
        .finance-page .finance-kpi--success { --finance-accent: #15803d; color: #15803d; }
        .finance-page .finance-kpi--danger { --finance-accent: #dc2626; color: #dc2626; }
        .finance-page .finance-kpi--warning { --finance-accent: #b45309; color: #b45309; }
        .finance-page .finance-kpi--muted { --finance-accent: #64748b; color: #64748b; }
        .finance-page .finance-caption { color: var(--finance-muted); font-size: .82rem; }
        .finance-page .finance-eyebrow { color: var(--finance-blue); font-size: .7rem; font-weight: 750; letter-spacing: .12em; text-transform: uppercase; }
        .finance-page .finance-page-title { font-size: clamp(1.65rem, 2vw, 2.15rem); font-weight: 780; letter-spacing: -.04em; }
        .finance-page .finance-page-subtitle { color: var(--finance-muted); font-size: .92rem; }
        .finance-page .finance-page-header { position: relative; overflow: hidden; padding: 1.35rem 1.5rem; border: 1px solid #e0e2fb; border-radius: 20px; background: linear-gradient(120deg, #f4f3ff 0%, #fff 52%, #edfcff 100%); box-shadow: 0 10px 28px rgba(42, 48, 112, .06); }
        .finance-page .finance-page-header::after { content: ""; position: absolute; width: 180px; height: 180px; right: -55px; top: -84px; border-radius: 50%; background: rgba(91, 92, 226, .1); pointer-events: none; }
        .finance-page .finance-page-header > * { position: relative; z-index: 1; }
        .finance-page .finance-tabs { padding: .35rem; border: 1px solid var(--finance-line); border-radius: 13px; background: #fff; box-shadow: 0 1px 2px rgba(15, 23, 42, .03); }
        .finance-page .finance-tabs .nav-link { display: inline-flex; align-items: center; gap: .45rem; }
        .finance-page .finance-tabs .nav-link i { font-size: .95rem; }
        .finance-page .finance-tabs .nav-link:focus-visible,
        .finance-page .btn:focus-visible,
        .finance-page .form-control:focus-visible,
        .finance-page .form-select:focus-visible,
        .finance-page summary:focus-visible { outline: 3px solid rgba(91, 92, 226, .25); outline-offset: 2px; }
        .finance-page .finance-callout { display: flex; gap: .75rem; align-items: flex-start; border: 1px solid #dbeafe; border-radius: 14px; padding: .9rem 1rem; background: #f8fbff; color: #334155; }
        .finance-page .finance-callout > i { color: #2563eb; font-size: 1.1rem; margin-top: .1rem; }
        .finance-page .finance-callout strong { display: block; font-size: .88rem; color: #1e3a8a; }
        .finance-page .finance-callout span { display: block; font-size: .82rem; margin-top: .15rem; }
        .finance-page .finance-reason { display: inline-flex; margin: .1rem .15rem .1rem 0; padding: .25rem .48rem; border-radius: 999px; background: #fff1f2; color: #be123c; font-size: .72rem; font-weight: 600; }
        .finance-page .finance-reason--neutral { background: #f1f5f9; color: #64748b; }
        .finance-page .finance-journal-count { display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .5rem; border: 1px solid var(--finance-line); border-radius: 999px; color: #475569; font-size: .78rem; font-weight: 650; }
        .finance-page .finance-empty { padding: 2.5rem 1rem; text-align: center; color: var(--finance-muted); }
        .finance-page .finance-empty i { display: block; font-size: 1.8rem; color: #94a3b8; margin-bottom: .6rem; }
        .finance-page .finance-section-title { font-weight: 700; color: var(--finance-ink); }
        .finance-page .finance-section-meta { color: var(--finance-muted); font-size: .8rem; }
        .finance-page .finance-filter-card { background: var(--finance-soft); }
        .finance-page .finance-filter-card .card-body { padding: 1rem 1.15rem; }
        .finance-page .finance-filter-card .form-label { color: #475569; font-size: .78rem; font-weight: 650; }
        .finance-page .finance-filter-card .form-control,
        .finance-page .finance-filter-card .form-select { border-color: #dce2e9; background: #fff; }
        .finance-page .finance-sidebar-layout { display: grid; grid-template-columns: 255px minmax(0, 1fr); gap: 1.25rem; align-items: start; }
        .finance-page .finance-sidebar { position: sticky; top: 1rem; }
        .finance-page .finance-sidebar .finance-card { box-shadow: 0 8px 24px rgba(15, 23, 42, .05); }
        .finance-page .finance-sidebar .form-label { margin-bottom: .35rem; }
        .finance-page .finance-sidebar .form-control,
        .finance-page .finance-sidebar .form-select { min-height: 40px; }
        .finance-page .finance-sidebar .btn { min-height: 40px; }
        .finance-page .finance-table-wrap { overflow-x: auto; }
        .finance-page .finance-table { min-width: 760px; }
        .finance-page .finance-table td,
        .finance-page .finance-table th { padding-top: .78rem; padding-bottom: .78rem; }
        .finance-page .finance-pagination { display: flex; align-items: center; justify-content: flex-end; gap: .75rem; flex-wrap: wrap; }
        .finance-page .finance-pagination .pagination { margin-bottom: 0; flex-wrap: wrap; gap: .2rem; }
        .finance-page .finance-pagination .page-link { border-radius: 8px; }
        .finance-page .finance-status-dot { display: inline-block; width: .45rem; height: .45rem; margin-right: .3rem; border-radius: 50%; background: currentColor; vertical-align: .08em; }
        .finance-page .finance-mobile-only { display: none; }
        @media (min-width: 768px) and (max-width: 1100px) {
            .finance-page .finance-sidebar-layout { grid-template-columns: 220px minmax(0, 1fr); gap: .85rem; }
            .finance-page .finance-sidebar .card-body { padding: .9rem; }
        }
        @media (max-width: 767.98px) {
            .finance-page { padding-inline: .75rem !important; padding-top: 1rem !important; }
            .finance-page .finance-page-header { padding: 1.1rem; border-radius: 16px; }
            .finance-page .finance-page-header .badge { align-self: flex-start; }
            .finance-page .finance-page-subtitle { max-width: 31rem; font-size: .82rem; line-height: 1.5; }
            .finance-page .finance-tabs { margin-inline: -.1rem; overflow-x: auto; scrollbar-width: none; }
            .finance-page .finance-tabs::-webkit-scrollbar { display: none; }
            .finance-page .finance-tabs .nav-link { min-height: 44px; flex: 0 0 auto; padding-inline: .78rem; }
            .finance-page .finance-card .card-body { padding: 1rem; }
            .finance-page .finance-card .card-header { padding: .85rem 1rem; }
            .finance-page .finance-sidebar-layout { display: block; }
            .finance-page .finance-sidebar { position: static; margin-bottom: 1rem; }
            .finance-page .finance-sidebar .card-body { padding: 1rem; }
            .finance-page .finance-sidebar .form-control,
            .finance-page .finance-sidebar .form-select,
            .finance-page .finance-sidebar .btn,
            .finance-page .finance-filter-card .form-control,
            .finance-page .finance-filter-card .form-select,
            .finance-page .finance-filter-card .btn { min-height: 44px; }
            .finance-page .finance-filter-card .card-body { padding: 1rem; }
            .finance-page .finance-filter-card .row { row-gap: .75rem; }
            .finance-page .finance-filter-card .btn { width: 100%; }
            .finance-page .finance-kpi { min-height: 104px; }
            .finance-page .finance-kpi .card-body { padding: .9rem; }
            .finance-page .finance-number { font-size: 1.18rem; }
            .finance-page .finance-callout { padding: .8rem; }
            .finance-page .finance-table-wrap { overflow: visible; }
            .finance-page .finance-table { min-width: 0; }
            .finance-page .finance-table thead { display: none; }
            .finance-page .finance-table,
            .finance-page .finance-table tbody,
            .finance-page .finance-table tr,
            .finance-page .finance-table td { display: block; width: 100%; }
            .finance-page .finance-table tbody tr { padding: .7rem 1rem; border-bottom: 1px solid var(--finance-line); }
            .finance-page .finance-table tbody tr:last-child { border-bottom: 0; }
            .finance-page .finance-table tbody td,
            .finance-page .finance-table tbody td:first-child { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; min-width: 0 !important; padding: .42rem 0; border: 0; text-align: right; white-space: normal; }
            .finance-page .finance-table tbody td::before { flex: 0 0 38%; color: var(--finance-muted); font-size: .68rem; font-weight: 750; letter-spacing: .02em; text-align: left; text-transform: uppercase; content: attr(data-label); }
            .finance-page .finance-table tbody td[colspan] { display: block; padding: 0; text-align: center; }
            .finance-page .finance-table tbody td[colspan]::before { display: none; }
            .finance-page .finance-table tbody td:first-child { color: var(--finance-ink); font-weight: 700; }
            .finance-page .finance-table tbody td:first-child::before { color: var(--finance-blue); }
            .finance-page .finance-table tbody td[style] { min-width: 0 !important; }
            .finance-page .finance-pagination { align-items: flex-start; justify-content: space-between; }
            .finance-page .finance-pagination .pagination { max-width: 100%; }
            .finance-page .finance-mobile-only { display: inline; }
        }
        @media (max-width: 420px) {
            .finance-page .finance-page-title { font-size: 1.55rem; }
            .finance-page .finance-eyebrow { font-size: .62rem; }
            .finance-page .row.g-3 { --bs-gutter-y: .7rem; }
            .finance-page .finance-table tbody tr { padding-inline: .85rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .finance-page *, .finance-page *::before, .finance-page *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
        }
        /* Keep the existing desktop table responsive rule as a fallback for other tables. */
        @media (max-width: 767.98px) {
            .finance-page .table:not(.finance-table) { min-width: 0; }
        }
    </style>
    <div class="container-fluid py-4 finance-page">
        <header class="finance-page-header d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div>
                <div class="finance-eyebrow mb-2">Marketplace / Operasional &amp; Keuangan</div>
                <h1 class="finance-page-title mb-1">Marketplace Finance</h1>
                <div class="finance-page-subtitle">Pantau transaksi, settlement, biaya, dan status rekonsiliasi marketplace.</div>
            </div>
            <span class="badge rounded-pill text-bg-light border px-3 py-2"><i class="bi bi-shield-check me-1 text-success"></i>Read-only reconciliation</span>
        </header>

        <nav class="finance-nav finance-tabs nav nav-pills gap-2 mb-4" aria-label="Marketplace Finance">
            <a class="nav-link {{ request()->routeIs('marketplace.finance.index') ? 'active' : '' }}" href="{{ route('marketplace.finance.index', request()->except('status')) }}"><i class="bi bi-grid-1x2"></i>Overview</a>
            <a class="nav-link {{ request()->routeIs('marketplace.finance.transactions') ? 'active' : '' }}" href="{{ route('marketplace.finance.transactions', request()->except('status')) }}"><i class="bi bi-receipt"></i>Transactions</a>
            <a class="nav-link {{ request()->routeIs('marketplace.finance.settlements') ? 'active' : '' }}" href="{{ route('marketplace.finance.settlements', request()->except('status')) }}"><i class="bi bi-bank"></i>Settlements</a>
            <a class="nav-link {{ request()->routeIs('marketplace.finance.reconciliation') ? 'active' : '' }}" href="{{ route('marketplace.finance.reconciliation', request()->except('status')) }}"><i class="bi bi-check2-square"></i>Reconciliation</a>
            <a class="nav-link {{ request()->routeIs('marketplace.finance.fee-analysis') ? 'active' : '' }}" href="{{ route('marketplace.finance.fee-analysis', request()->except('status')) }}"><i class="bi bi-bar-chart-line"></i>Fee Analysis</a>
        </nav>

        @hasSection('finance-sidebar')
            <div class="finance-sidebar-layout">
                <aside class="finance-sidebar" aria-label="Filter transaksi">
                    @yield('finance-sidebar')
                </aside>
                <main>
                    @yield('finance-content')
                </main>
            </div>
        @else
            @include('marketplace.finance._filters')
            @yield('finance-content')
        @endif
    </div>
@endsection
