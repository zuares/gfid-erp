@extends('layouts.app')

@section('title', 'Marketplace Finance')

@section('content')
    <style>
        .finance-page .finance-card { border: 1px solid #e5e7eb; border-radius: 14px; }
        .finance-page .finance-number { font-size: 1.35rem; font-weight: 750; letter-spacing: -.02em; }
        .finance-page .table th { white-space: nowrap; font-size: .78rem; text-transform: uppercase; color: #64748b; }
        .finance-page .table td { vertical-align: middle; }
        .finance-page .finance-nav { overflow-x: auto; white-space: nowrap; }
    </style>
    <div class="container-fluid py-4 finance-page">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
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
