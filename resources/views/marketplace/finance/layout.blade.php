@extends('layouts.app')

@section('title', 'Marketplace Finance')

@section('content')
    <style>
        .finance-page { --finance-ink: #172033; --finance-muted: #64748b; --finance-line: #e7ebf0; --finance-soft: #f8fafc; max-width: 1760px; margin: 0 auto; padding-top: 1.75rem !important; padding-bottom: 2.5rem !important; color: var(--finance-ink); }
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
        @media (max-width: 767.98px) {
            .finance-page .table { min-width: 860px; }
            .finance-page .finance-card .card-body { padding: 1rem; }
            .finance-page { padding-inline: .75rem !important; }
        }
    </style>
    <div class="container-fluid py-4 finance-page">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-1">
            <div>
                <div class="text-muted small">Toko Online</div>
                <h1 class="h3 mb-1">Marketplace Finance</h1>
                <div class="text-muted">Ringkasan transaksi, escrow, settlement, dan rekonsiliasi.</div>
            </div>
            <span class="badge text-bg-light border">Read-only reconciliation</span>
        </div>

        <nav class="finance-nav nav nav-pills gap-2 mb-4" aria-label="Marketplace Finance">
            <a class="nav-link {{ request()->routeIs('marketplace.finance.index') ? 'active' : '' }}" href="{{ route('marketplace.finance.index', request()->except('status')) }}">Overview</a>
            <a class="nav-link {{ request()->routeIs('marketplace.finance.transactions') ? 'active' : '' }}" href="{{ route('marketplace.finance.transactions', request()->except('status')) }}">Transactions</a>
            <a class="nav-link {{ request()->routeIs('marketplace.finance.settlements') ? 'active' : '' }}" href="{{ route('marketplace.finance.settlements', request()->except('status')) }}">Settlements</a>
            <a class="nav-link {{ request()->routeIs('marketplace.finance.reconciliation') ? 'active' : '' }}" href="{{ route('marketplace.finance.reconciliation', request()->except('status')) }}">Reconciliation</a>
            <a class="nav-link {{ request()->routeIs('marketplace.finance.fee-analysis') ? 'active' : '' }}" href="{{ route('marketplace.finance.fee-analysis', request()->except('status')) }}">Fee Analysis</a>
        </nav>

        @include('marketplace.finance._filters')

        @yield('finance-content')
    </div>
@endsection
