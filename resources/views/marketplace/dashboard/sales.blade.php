@extends('layouts.app')

@section('title', 'Dashboard Penjualan • Marketplace')

@include('marketplace._shared')

@push('head')
<style>
    .sales-dashboard {
        --sales-line: var(--line, #d4d7e3);
        --sales-muted: var(--muted, #6b7280);
        --sales-card: var(--card, #fff);
        --sales-soft: var(--card-soft, #f9fafb);
        --sales-ink: var(--text, #111827);
        max-width: 1280px;
        margin-inline: auto;
        color: var(--sales-ink);
    }

    .sales-dashboard .sales-card {
        border: 1px solid var(--sales-line);
        border-radius: .75rem;
        background: var(--sales-card);
        box-shadow: 0 .125rem .25rem rgba(15, 23, 42, .035);
    }

    .sales-dashboard .sales-kicker {
        color: var(--accent, #2563eb);
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .sales-dashboard .sales-title { color: var(--sales-ink); letter-spacing: -.035em; }
    .sales-dashboard .sales-subtitle,
    .sales-dashboard .sales-section-subtitle { color: var(--sales-muted); }

    .sales-dashboard .sales-filter-card { background: var(--sales-soft); }
    .sales-dashboard .sales-filter-card .form-label {
        color: var(--sales-muted);
        font-size: .74rem;
        font-weight: 700;
        margin-bottom: .3rem;
    }
    .sales-dashboard .sales-filter-card .form-control,
    .sales-dashboard .sales-filter-card .form-select {
        border-color: var(--sales-line);
        background-color: var(--sales-card);
        color: var(--sales-ink);
        font-size: .8rem;
    }
    .sales-dashboard .sales-filter-card .sales-date-range,
    .sales-dashboard .sales-filter-card .sales-date-range-display {
        min-width: 220px;
        border-color: var(--sales-line);
        background-color: var(--sales-card);
        color: var(--sales-ink);
        font-size: .8rem;
        cursor: pointer;
    }
    .sales-dashboard .sales-filter-card .sales-date-range,
    .sales-dashboard .sales-filter-card .sales-date-range-display,
    .sales-dashboard .sales-filter-card input[type="date"],
    .sales-dashboard .sales-filter-card input[name="date_from"],
    .sales-dashboard .sales-filter-card input[name="date_to"] { display: none !important; }
    .sales-dashboard .sales-period-filter { position: relative; width: min(100%, 560px); z-index: 20; }
    .sales-dashboard .sales-period-filter { order: 3; margin-left: auto; }
    .sales-dashboard .sales-filter-store { order: 2; }
    .sales-dashboard #sales-date-from,
    .sales-dashboard #sales-date-to { display: none !important; }
    .sales-dashboard .sales-period-trigger {
        display: flex; align-items: center; gap: .7rem; width: 100%; min-height: 48px;
        padding: .7rem .85rem; border: 1px solid rgba(148,163,184,.38); border-radius: 12px;
        background: var(--sales-card); color: var(--sales-ink); text-align: left; cursor: pointer;
        transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
    }
    .sales-dashboard .sales-period-trigger:hover,
    .sales-dashboard .sales-period-trigger[aria-expanded="true"] {
        border-color: rgba(234,88,12,.55); box-shadow: 0 7px 18px rgba(15,23,42,.09); transform: translateY(-1px);
    }
    .sales-dashboard .sales-period-trigger-label { color: var(--sales-muted); font-size: .78rem; white-space: nowrap; }
    .sales-dashboard .sales-period-trigger-preset { color: #ea580c; font-size: .82rem; font-weight: 800; white-space: nowrap; }
    .sales-dashboard .sales-period-trigger-summary { color: var(--sales-muted); font-size: .8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sales-dashboard .sales-period-trigger-icon { margin-left: auto; color: #64748b; font-size: 1.05rem; flex: 0 0 auto; }
    .sales-dashboard .sales-period-popover {
        position: absolute; left: 0; top: calc(100% + .55rem); width: min(100vw - 2rem, 680px);
        overflow: hidden; border: 1px solid rgba(148,163,184,.20); border-radius: 16px;
        background: var(--sales-card); box-shadow: 0 18px 44px rgba(15,23,42,.16); z-index: 3000;
    }
    .sales-dashboard .sales-period-popover[hidden] { display: none; }
    .sales-dashboard .sales-period-layout { display: grid; grid-template-columns: 190px minmax(0, 1fr); min-height: 320px; }
    .sales-dashboard .sales-period-menu { padding: .65rem; border-right: 1px solid rgba(148,163,184,.17); background: var(--sales-card); }
    .sales-dashboard .sales-period-option {
        display: flex; align-items: center; justify-content: space-between; width: 100%; min-height: 38px;
        padding: .55rem .65rem; border: 0; border-radius: 9px; background: transparent; color: var(--sales-ink);
        font-size: .78rem; font-weight: 650; text-align: left; cursor: pointer; transition: background .14s ease, color .14s ease, transform .14s ease;
    }
    .sales-dashboard .sales-period-option:hover,
    .sales-dashboard .sales-period-option.is-selected { background: rgba(234,88,12,.09); color: #c2410c; transform: translateX(2px); }
    .sales-dashboard .sales-period-option .arrow { color: #94a3b8; font-size: 1.1rem; line-height: 1; }
    .sales-dashboard .sales-period-divider { height: 1px; margin: .5rem .25rem; background: rgba(148,163,184,.19); }
    .sales-dashboard .sales-period-panel { min-width: 0; padding: 1.15rem 1.25rem; background: color-mix(in srgb, var(--sales-soft) 60%, transparent); }
    .sales-dashboard .sales-period-panel[hidden] { display: none; }
    .sales-dashboard .sales-period-eyebrow { color: #ea580c; font-size: .68rem; font-weight: 850; letter-spacing: .08em; text-transform: uppercase; }
    .sales-dashboard .sales-period-title { margin: .5rem 0 0; color: var(--sales-ink); font-size: 1.2rem; font-weight: 850; letter-spacing: -.02em; }
    .sales-dashboard .sales-period-help { margin: .35rem 0 1.1rem; color: var(--sales-muted); font-size: .82rem; line-height: 1.55; }
    .sales-dashboard .sales-period-input-label { display: block; margin-bottom: .35rem; color: var(--sales-muted); font-size: .75rem; font-weight: 750; }
    .sales-dashboard .sales-period-range-input { width: 100%; min-height: 42px; border: 1px solid rgba(148,163,184,.25); border-radius: 10px; background: var(--sales-card); color: var(--sales-ink); padding: .55rem .7rem; box-shadow: 0 4px 12px rgba(15,23,42,.06); cursor: pointer; }
    .sales-dashboard #sales-date-range { display: none !important; }
    .sales-dashboard .sales-period-filter input.form-control.input.active,
    .sales-dashboard .sales-period-filter input.input.active,
    .sales-dashboard .sales-period-calendar-wrap > input {
        display: none !important;
    }
    .sales-dashboard .sales-period-range-input:focus { outline: 0; border-color: rgba(234,88,12,.6); box-shadow: 0 0 0 .2rem rgba(234,88,12,.13); }
    .sales-dashboard .sales-period-calendar-wrap { margin-top: .65rem; }
    .sales-dashboard .sales-period-calendar-wrap .flatpickr-calendar { box-shadow: none !important; border: 0 !important; width: 100%; }
    .sales-dashboard .sales-period-calendar-wrap .flatpickr-calendar.inline { display: block; }
    .sales-dashboard .sales-period-nav { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .sales-dashboard .sales-period-nav button { width: 30px; height: 30px; border: 0; border-radius: 8px; background: transparent; color: var(--sales-muted); cursor: pointer; }
    .sales-dashboard .sales-period-nav button:hover { background: rgba(148,163,184,.15); color: var(--sales-ink); }
    .sales-dashboard .sales-period-nav strong { color: var(--sales-ink); font-size: .9rem; }
    .sales-dashboard .sales-period-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; margin-top: 1rem; }
    .sales-dashboard .sales-period-grid button { min-height: 42px; border: 1px solid rgba(148,163,184,.19); border-radius: 9px; background: var(--sales-card); color: var(--sales-ink); font-size: .76rem; font-weight: 650; cursor: pointer; transition: all .14s ease; }
    .sales-dashboard .sales-period-grid button:hover,
    .sales-dashboard .sales-period-grid button.is-selected { border-color: rgba(234,88,12,.42); background: rgba(234,88,12,.08); color: #c2410c; transform: translateY(-1px); }
    .sales-dashboard .sales-period-grid button:disabled { cursor: not-allowed; color: #cbd5e1; background: rgba(148,163,184,.07); }
    body[data-theme="dark"] .sales-dashboard .sales-period-panel { background: rgba(15,23,42,.38); }
    body[data-theme="dark"] .sales-dashboard .sales-period-option:hover,
    body[data-theme="dark"] .sales-dashboard .sales-period-option.is-selected,
    body[data-theme="dark"] .sales-dashboard .sales-period-grid button:hover,
    body[data-theme="dark"] .sales-dashboard .sales-period-grid button.is-selected { color: #fdba74; background: rgba(234,88,12,.14); }
    @media (max-width: 640px) {
        .sales-dashboard .sales-period-filter { order: 1; margin-left: 0; }
        .sales-dashboard .sales-filter-store { order: 2; }
        .sales-dashboard .sales-period-popover { width: min(100vw - 1.5rem, 680px); left: 50%; transform: translateX(-50%); }
        .sales-dashboard .sales-period-layout { grid-template-columns: 1fr; }
        .sales-dashboard .sales-period-menu { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .2rem; border-right: 0; border-bottom: 1px solid rgba(148,163,184,.17); }
        .sales-dashboard .sales-period-divider { grid-column: 1 / -1; }
    }
    .sales-dashboard .sales-filter-card .form-control:focus,
    .sales-dashboard .sales-filter-card .form-select:focus {
        border-color: var(--accent, #2563eb);
        box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--accent, #2563eb) 18%, transparent);
    }

    .sales-dashboard .sales-nav { overflow-x: auto; scrollbar-width: none; }
    .sales-dashboard .sales-nav::-webkit-scrollbar { display: none; }
    .sales-dashboard .sales-nav .nav-link {
        border: 0;
        color: var(--sales-muted);
        background: transparent;
        border-radius: .55rem;
        font-size: .8rem;
        font-weight: 650;
        white-space: nowrap;
    }
    .sales-dashboard .sales-nav .nav-link:hover { color: var(--accent, #2563eb); background: var(--accent-soft, #dbeafe); }
    .sales-dashboard .sales-nav .nav-link.active { color: var(--accent, #2563eb); background: var(--accent-soft, #dbeafe); }
    .sales-dashboard .sales-tab-pane.is-hidden { display: none; }

    .sales-dashboard .sales-kpi { min-height: 132px; position: relative; overflow: hidden; }
    .sales-dashboard .sales-kpi::after {
        content: '';
        position: absolute;
        right: -1.8rem;
        bottom: -2.3rem;
        width: 6rem;
        height: 6rem;
        border-radius: 50%;
        background: var(--accent, #2563eb);
        opacity: .06;
    }
    .sales-dashboard .sales-kpi-label { color: var(--sales-muted); font-size: .7rem; font-weight: 750; }
    .sales-dashboard .sales-kpi-icon {
        width: 2rem;
        height: 2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: .55rem;
        background: var(--accent-soft, #dbeafe);
        color: var(--accent, #2563eb);
    }
    .sales-dashboard .sales-kpi-value { color: var(--sales-ink); font-size: 1.4rem; font-weight: 800; letter-spacing: -.03em; }
    .sales-dashboard .sales-kpi-note { color: var(--sales-muted); font-size: .72rem; }
    .sales-dashboard .sales-kpi--success .sales-kpi-icon { background: var(--success-soft, #dcfce7); color: var(--success, #16a34a); }
    .sales-dashboard .sales-kpi--success::after { background: var(--success, #16a34a); }
    .sales-dashboard .sales-kpi--warning .sales-kpi-icon { background: #fff7ed; color: #c2410c; }
    .sales-dashboard .sales-kpi--warning::after { background: #ea580c; }

    .sales-dashboard .sales-section-header { padding: 1rem 1.15rem .85rem; }
    .sales-dashboard .sales-section-title { color: var(--sales-ink); font-size: 1rem; font-weight: 750; }
    .sales-dashboard .sales-section-subtitle { font-size: .75rem; }
    .sales-dashboard .sales-detail-link { color: var(--accent, #2563eb); font-size: .75rem; font-weight: 700; text-decoration: none; }
    .sales-dashboard .sales-detail-link:hover { text-decoration: underline; }
    .sales-dashboard .sales-table { --bs-table-bg: var(--sales-card); --bs-table-color: var(--sales-ink); --bs-table-hover-bg: color-mix(in srgb, var(--accent-soft, #dbeafe) 35%, var(--sales-card)); margin-bottom: 0; }
    .sales-dashboard .sales-table th {
        background: var(--sales-soft);
        border-bottom-color: var(--sales-line);
        color: var(--sales-muted);
        font-size: .68rem;
        font-weight: 750;
        letter-spacing: .04em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .sales-dashboard .sales-table td { border-color: color-mix(in srgb, var(--sales-line) 65%, transparent); font-size: .78rem; }
    .sales-dashboard .sales-order-table { width: 100%; min-width: 0; table-layout: fixed; }
    .sales-dashboard .sales-order-table th,
    .sales-dashboard .sales-order-table td { padding: .62rem .55rem; overflow-wrap: anywhere; }
    .sales-dashboard .sales-order-table th:nth-child(1),
    .sales-dashboard .sales-order-table td:nth-child(1) { width: 20%; }
    .sales-dashboard .sales-order-table th:nth-child(2),
    .sales-dashboard .sales-order-table td:nth-child(2) { width: 16%; }
    .sales-dashboard .sales-order-table th:nth-child(3),
    .sales-dashboard .sales-order-table th:nth-child(4),
    .sales-dashboard .sales-order-table td:nth-child(3),
    .sales-dashboard .sales-order-table td:nth-child(4) { width: 11%; }
    .sales-dashboard .sales-order-table th:nth-child(5),
    .sales-dashboard .sales-order-table td:nth-child(5) { width: 12%; }
    .sales-dashboard .sales-order-table th:nth-child(6),
    .sales-dashboard .sales-order-table td:nth-child(6) { width: 18%; }
    .sales-dashboard .sales-order-table th:nth-child(7),
    .sales-dashboard .sales-order-table td:nth-child(7) { width: 12%; }
    .sales-dashboard .sales-order-table .sales-order-cell { min-width: 0; }
    .sales-dashboard .sales-order-table .sales-order-number { color: var(--sales-ink); font-weight: 750; letter-spacing: -.01em; }
    .sales-dashboard .sales-order-table .sales-order-meta { color: var(--sales-muted); font-size: .68rem; line-height: 1.35; }
    .sales-dashboard .sales-order-table .sales-order-buyer { min-width: 0; font-weight: 600; }
    .sales-dashboard .sales-order-table .sales-order-location { min-width: 0; color: var(--sales-muted); }
    .sales-dashboard .sales-order-table .sales-order-promotion { min-width: 0; line-height: 1.4; }
    .sales-dashboard .sales-order-table .sales-order-total { min-width: 0; }
    .sales-dashboard .sales-product-name {
        display: block;
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sales-dashboard .sales-product-link {
        display: block;
        width: 100%;
        padding: 0;
        border: 0;
        background: transparent;
        color: inherit;
        cursor: pointer;
        text-align: left;
    }
    .sales-dashboard .sales-product-link:hover .sales-product-name,
    .sales-dashboard .sales-product-link:focus-visible .sales-product-name {
        color: var(--accent, #2563eb);
        text-decoration: underline;
    }
    .sales-dashboard .sales-date-link,
    .sales-dashboard .sales-action-link { color: var(--accent, #2563eb); font-weight: 700; text-decoration: none; }
    .sales-dashboard button.sales-date-link { background: transparent; border: 0; cursor: pointer; padding: 0; }
    .sales-dashboard button.sales-action-link { background: transparent; border: 0; cursor: pointer; padding: 0; }
    .sales-dashboard .sales-date-link:hover,
    .sales-dashboard .sales-action-link:hover { text-decoration: underline; }
    .sales-dashboard .sales-clickable-row { cursor: pointer; }
    .sales-dashboard .sales-empty { color: var(--sales-muted); padding: 2.5rem 1rem; }
    .sales-dashboard .sales-badge { background: var(--sales-soft); border: 1px solid var(--sales-line); color: var(--sales-muted); font-size: .7rem; font-weight: 650; }

    body[data-theme="dark"] .sales-dashboard .sales-filter-card,
    body[data-theme="dark"] .sales-dashboard .sales-table th { background: var(--card-soft, #132a45); }
    body[data-theme="dark"] .sales-dashboard .sales-filter-card .form-control,
    body[data-theme="dark"] .sales-dashboard .sales-filter-card .form-select { color: var(--sales-ink); }

    @media (max-width: 767.98px) {
        .sales-dashboard { padding-inline: .75rem !important; }
        .sales-dashboard .sales-table { min-width: 720px; }
        .sales-dashboard .sales-order-table { min-width: 0; }
        .sales-dashboard .sales-kpi { min-height: 118px; }
        .sales-dashboard .sales-kpi-value { font-size: 1.2rem; }
    }
</style>
@endpush

@section('content')
@php
    $fmt = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $dateLabel = fn ($date) => \Carbon\Carbon::parse($date)->format('d M Y');
    $salesTabs = ['sales', 'products', 'payments', 'promotions', 'shipping', 'income', 'orders'];
    $activeTab = in_array(request('tab'), $salesTabs, true) ? request('tab') : 'sales';
    $todayDate = now()->toDateString();
    $yesterdayDate = now()->subDay()->toDateString();
    $activeDatePreset = 'custom';
    $activeDatePresetLabel = 'Rentang tanggal';
    $activeDateSummary = $filters['date_from'].' – '.$filters['date_to'];
    if ($filters['date_from'] === $todayDate && $filters['date_to'] === $todayDate) {
        $activeDatePreset = 'today';
        $activeDatePresetLabel = 'Real-time';
        $activeDateSummary = 'Hari ini';
    } elseif ($filters['date_from'] === $yesterdayDate && $filters['date_to'] === $yesterdayDate) {
        $activeDatePreset = 'yesterday';
        $activeDatePresetLabel = 'Kemarin';
        $activeDateSummary = 'Kemarin';
    } elseif ($filters['date_from'] === now()->subDays(6)->toDateString() && $filters['date_to'] === $todayDate) {
        $activeDatePreset = 'last-7-days';
        $activeDatePresetLabel = '7 hari sebelumnya';
        $activeDateSummary = '7 hari terakhir';
    } elseif ($filters['date_from'] === now()->subDays(29)->toDateString() && $filters['date_to'] === $todayDate) {
        $activeDatePreset = 'last-30-days';
        $activeDatePresetLabel = '30 hari sebelumnya';
        $activeDateSummary = '30 hari terakhir';
    }
    $detailQuery = ['date_from' => $filters['date_from'], 'date_to' => $filters['date_to']];
    if ($filters['store_id']) $detailQuery['store_id'] = $filters['store_id'];
    $promotionTotals = [
        'product_discount' => (float) $promotionDaily->sum('product_discount'),
        'voucher_store' => (float) $promotionDaily->sum('voucher_store'),
        'voucher_platform' => (float) $promotionDaily->sum('voucher_platform'),
        'bundle_discount' => (float) $promotionDaily->sum('bundle_discount'),
    ];
    $shippingOrders = fn (array $statuses) => (int) $shipping
        ->whereIn('status', $statuses)
        ->sum('orders');
    $netProductTotal = max($summary['subtotal'] - $promotionTotals['product_discount'], 0);
    $paymentDetailQuery = ['tab' => 'payments'];
    if ($filters['store_id']) $paymentDetailQuery['store_id'] = $filters['store_id'];
    if (!empty($filters['dummy'])) $paymentDetailQuery['dummy'] = 1;
    $shippingDetailQuery = ['tab' => 'shipping'];
    if ($filters['store_id']) $shippingDetailQuery['store_id'] = $filters['store_id'];
    if (!empty($filters['dummy'])) $shippingDetailQuery['dummy'] = 1;
    $topPaymentMethod = $payments->sortByDesc('buyer_paid')->first();
    $peakPaymentDay = $paymentDaily->sortByDesc('aov')->first();
    $paymentCategoryLabels = ['cod' => 'COD', 'non_cod' => 'Non-COD', 'pay_later' => 'Pay Later'];
    $paymentMix = collect($paymentCategoryLabels)->mapWithKeys(function ($label, $category) use ($payments) {
        return [$category => $payments->firstWhere('category', $category) ?: (object) [
            'category' => $category,
            'orders' => 0,
            'paid_orders' => 0,
            'buyer_paid' => 0,
            'avg_ticket' => 0,
            'order_share' => 0,
        ]];
    });
@endphp

<div class="container-fluid py-4 sales-dashboard">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <div class="text-muted small mb-1">Toko Online / Dashboard Operasional</div>
            <h1 class="h3 sales-title mb-1">Dashboard Penjualan</h1>
            <p class="sales-subtitle mb-0">Ringkasan pesanan dan penjualan marketplace pada periode yang dipilih.</p>
        </div>
        <span class="badge sales-badge rounded-pill px-3 py-2"><i class="bi bi-database-check me-1"></i>Data marketplace</span>
    </div>

    <form class="card sales-card sales-filter-card shadow-sm mb-4" method="GET" action="{{ route('marketplace.dashboard.sales') }}">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end justify-content-end">
                @if (!empty($filters['dummy']))
                    <input type="hidden" name="dummy" value="1">
                @endif
                <input type="hidden" name="tab" id="sales-active-tab" value="{{ $activeTab }}">
                <input type="hidden" name="date_from" id="sales-date-from" value="{{ $filters['date_from'] }}" data-gf-date="off">
                <input type="hidden" name="date_to" id="sales-date-to" value="{{ $filters['date_to'] }}" data-gf-date="off">
                <div class="col-12 col-md-auto sales-period-filter" data-sales-period-filter>
                    <button type="button" class="sales-period-trigger" data-sales-period-trigger aria-expanded="false" aria-controls="sales-period-popover">
                        <span class="sales-period-trigger-label">Periode Data</span>
                        <span class="sales-period-trigger-preset" data-sales-period-trigger-label>{{ $activeDatePresetLabel }}</span>
                        <span class="sales-period-trigger-summary" data-sales-period-trigger-summary>{{ $activeDateSummary }}</span>
                        <i class="bi bi-calendar3 sales-period-trigger-icon" aria-hidden="true"></i>
                    </button>

                    <div id="sales-period-popover" class="sales-period-popover" data-sales-period-popover hidden>
                        <div class="sales-period-layout">
                            <div class="sales-period-menu" role="menu" aria-label="Pilihan periode">
                                <button type="button" class="sales-period-option {{ $activeDatePreset === 'today' ? 'is-selected' : '' }}" data-sales-period-preset="today" data-sales-period-label="Real-time">Real-time</button>
                                <button type="button" class="sales-period-option {{ $activeDatePreset === 'yesterday' ? 'is-selected' : '' }}" data-sales-period-preset="yesterday" data-sales-period-label="Kemarin">Kemarin</button>
                                <button type="button" class="sales-period-option {{ $activeDatePreset === 'last-7-days' ? 'is-selected' : '' }}" data-sales-period-preset="last-7-days" data-sales-period-label="7 hari sebelumnya">7 hari sebelumnya</button>
                                <button type="button" class="sales-period-option {{ $activeDatePreset === 'last-30-days' ? 'is-selected' : '' }}" data-sales-period-preset="last-30-days" data-sales-period-label="30 hari sebelumnya">30 hari sebelumnya</button>
                                <div class="sales-period-divider"></div>
                                <button type="button" class="sales-period-option" data-sales-period-view="day">Per Hari <span class="arrow">›</span></button>
                                <button type="button" class="sales-period-option" data-sales-period-view="week">Per Minggu <span class="arrow">›</span></button>
                                <button type="button" class="sales-period-option" data-sales-period-view="month">Per Bulan <span class="arrow">›</span></button>
                                <button type="button" class="sales-period-option" data-sales-period-view="year">Berdasarkan Tahun <span class="arrow">›</span></button>
                            </div>

                            <div class="sales-period-panel" data-sales-period-panel="default">
                                <div class="sales-period-eyebrow">Periode terpilih</div>
                                <div class="sales-period-title" data-sales-period-detail-label>{{ $activeDateSummary }}</div>
                                <p class="sales-period-help" data-sales-period-detail-help>Pilih preset di sebelah kiri atau klik area filter untuk membuka kalender.</p>
                                <input id="sales-date-range" class="sales-period-range-input" type="text" value="{{ $filters['date_from'] }} to {{ $filters['date_to'] }}" aria-hidden="true" tabindex="-1">
                            </div>

                            <div class="sales-period-panel" data-sales-period-panel="day" hidden>
                                <div class="sales-period-eyebrow">Per Hari</div>
                                <div class="sales-period-title">Pilih hari</div>
                                <p class="sales-period-help">Klik satu tanggal untuk melihat data harian.</p>
                                <div class="sales-period-calendar-wrap"><input id="sales-date-day" type="text" aria-label="Pilih hari"></div>
                            </div>

                            <div class="sales-period-panel" data-sales-period-panel="week" hidden>
                                <div class="sales-period-eyebrow">Per Minggu</div>
                                <div class="sales-period-title">Pilih minggu</div>
                                <p class="sales-period-help">Arahkan kursor ke tanggal, lalu klik salah satu hari.</p>
                                <div class="sales-period-calendar-wrap"><input id="sales-date-week" type="text" aria-label="Pilih minggu"></div>
                            </div>

                            <div class="sales-period-panel" data-sales-period-panel="month" hidden>
                                <div class="sales-period-nav"><button type="button" data-sales-period-month-prev aria-label="Tahun sebelumnya">‹</button><strong data-sales-period-month-year></strong><button type="button" data-sales-period-month-next aria-label="Tahun berikutnya">›</button></div>
                                <div class="sales-period-grid" data-sales-period-month-grid></div>
                            </div>

                            <div class="sales-period-panel" data-sales-period-panel="year" hidden>
                                <div class="sales-period-nav"><button type="button" data-sales-period-year-prev aria-label="Dekade sebelumnya">‹</button><strong data-sales-period-year-range></strong><button type="button" data-sales-period-year-next aria-label="Dekade berikutnya">›</button></div>
                                <div class="sales-period-grid" data-sales-period-year-grid></div>
                            </div>
                        </div>
                    </div>
                </div>
                @if ($stores->isNotEmpty())
                <div class="col-12 col-md-3 sales-filter-store">
                        <label class="form-label" for="sales-store">Toko</label>
                        <select id="sales-store" class="form-select form-select-sm" name="store_id">
                            <option value="">Semua toko</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" @selected((string) $filters['store_id'] === (string) $store->id)>
                                    {{ $store->name }}{{ $store->channel?->code ? ' · '.ucfirst($store->channel->code) : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </div>
    </form>

    <nav class="sales-nav nav nav-pills gap-2 mb-4" aria-label="Dashboard operasional" role="tablist">
        <button class="nav-link {{ $activeTab === 'sales' ? 'active' : '' }}" type="button" role="tab" aria-selected="{{ $activeTab === 'sales' ? 'true' : 'false' }}" data-sales-tab="sales"><i class="bi bi-graph-up-arrow me-1"></i>Penjualan</button>
        <button class="nav-link {{ $activeTab === 'products' ? 'active' : '' }}" type="button" role="tab" aria-selected="{{ $activeTab === 'products' ? 'true' : 'false' }}" data-sales-tab="products"><i class="bi bi-box-seam me-1"></i>Produk</button>
        <button class="nav-link {{ $activeTab === 'payments' ? 'active' : '' }}" type="button" role="tab" aria-selected="{{ $activeTab === 'payments' ? 'true' : 'false' }}" data-sales-tab="payments"><i class="bi bi-wallet2 me-1"></i>Pembayaran</button>
        <button class="nav-link {{ $activeTab === 'promotions' ? 'active' : '' }}" type="button" role="tab" aria-selected="{{ $activeTab === 'promotions' ? 'true' : 'false' }}" data-sales-tab="promotions"><i class="bi bi-percent me-1"></i>Promosi</button>
        <button class="nav-link {{ $activeTab === 'shipping' ? 'active' : '' }}" type="button" role="tab" aria-selected="{{ $activeTab === 'shipping' ? 'true' : 'false' }}" data-sales-tab="shipping"><i class="bi bi-truck me-1"></i>Pengiriman</button>
        <button class="nav-link {{ $activeTab === 'income' ? 'active' : '' }}" type="button" role="tab" aria-selected="{{ $activeTab === 'income' ? 'true' : 'false' }}" data-sales-tab="income"><i class="bi bi-cash-coin me-1"></i>Penghasilan</button>
        <button class="nav-link {{ $activeTab === 'orders' ? 'active' : '' }}" type="button" role="tab" aria-selected="{{ $activeTab === 'orders' ? 'true' : 'false' }}" data-sales-tab="orders"><i class="bi bi-list-ul me-1"></i>Detail Pesanan</button>
    </nav>

    <div class="sales-tab-pane {{ $activeTab === 'sales' ? '' : 'is-hidden' }}" data-sales-pane="sales" role="tabpanel" aria-hidden="{{ $activeTab === 'sales' ? 'false' : 'true' }}">
    @include('marketplace.dashboard.partials._kpis', [
        'kpiTitle' => 'Penjualan',
        'kpis' => [
            ['label' => 'Jumlah Order', 'value' => number_format($summary['orders']), 'note' => 'order aktif', 'icon' => 'bi-receipt'],
            ['label' => 'Nilai Bruto', 'value' => $fmt($summary['subtotal']), 'note' => 'sebelum promosi', 'icon' => 'bi-cash-stack', 'variant' => 'sales-kpi--success'],
            ['label' => 'Total Promosi', 'value' => $fmt($summary['promotion_total']), 'note' => 'diskon dan voucher', 'icon' => 'bi-percent', 'variant' => 'sales-kpi--warning'],
            ['label' => 'Nilai Neto', 'value' => $fmt($summary['net_total']), 'note' => 'setelah promosi', 'icon' => 'bi-graph-down-arrow'],
        ],
    ])
    <section class="card sales-card shadow-sm" aria-labelledby="daily-sales-title">
        <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <div class="sales-kicker mb-1">Ringkasan waktu</div>
                <h2 id="daily-sales-title" class="sales-section-title mb-1">Penjualan per tanggal</h2>
                <div class="sales-section-subtitle">Klik tanggal untuk memfilter dashboard ke tanggal tersebut.</div>
            </div>
            <span class="badge sales-badge rounded-pill px-3 py-2">{{ $daily->count() }} hari aktif</span>
        </div>

        @if ($daily->isEmpty())
            <div class="sales-empty text-center"><i class="bi bi-bar-chart-line d-block fs-3 mb-2"></i>Belum ada penjualan pada periode yang dipilih.</div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle sales-table">
                    <thead>
                        <tr>
                            <th class="ps-3">Tanggal</th>
                            <th class="text-end">Pesanan</th>
                            <th class="text-end">Unit terjual</th>
                            <th class="text-end">Subtotal barang</th>
                            <th class="text-end">AOV</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daily as $row)
                            <tr>
                                <td class="ps-3"><button class="sales-date-link" type="button" data-sales-order-detail-date="{{ $row->day }}">{{ $dateLabel($row->day) }}</button></td>
                                <td class="text-end">{{ number_format($row->orders) }}</td>
                                <td class="text-end">{{ number_format($row->qty) }}</td>
                                <td class="text-end">{{ $fmt($row->subtotal) }}</td>
                                <td class="text-end">{{ $fmt($row->aov) }}</td>
                                <td class="text-end pe-3"><button class="sales-action-link" type="button" data-sales-order-detail-date="{{ $row->day }}">Detail pesanan <i class="bi bi-arrow-right"></i></button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
    </div>

    <div class="sales-tab-pane {{ $activeTab === 'income' ? '' : 'is-hidden' }}" data-sales-pane="income" role="tabpanel" aria-hidden="{{ $activeTab === 'income' ? 'false' : 'true' }}">
        @include('marketplace.dashboard.partials._kpis', [
            'kpiTitle' => 'Penghasilan',
            'kpis' => [
                ['label' => 'Dana Cair', 'value' => $fmt($incomeSummary['final_income']), 'note' => 'payout settlement aktual', 'icon' => 'bi-cash-coin', 'variant' => 'sales-kpi--success'],
                ['label' => 'Pembayaran Pembeli', 'value' => $fmt($incomeSummary['buyer_paid']), 'note' => 'nilai pembayaran pada periode', 'icon' => 'bi-wallet2'],
                ['label' => 'Order Sudah Cair', 'value' => number_format($incomeSummary['settled_orders']), 'note' => 'memiliki settlement final', 'icon' => 'bi-check-circle'],
                ['label' => 'Order Belum Cair', 'value' => number_format($incomeSummary['pending_orders']), 'note' => 'belum memiliki tanggal cair', 'icon' => 'bi-clock-history', 'variant' => 'sales-kpi--warning'],
            ],
        ])
        <section class="card sales-card shadow-sm">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <div class="sales-kicker mb-1">Income trend</div>
                    <h2 class="sales-section-title mb-1">Penghasilan per tanggal order</h2>
                    <div class="sales-section-subtitle">Dana cair hanya menghitung settlement yang sudah memiliki tanggal pencairan.</div>
                </div>
                <span class="badge sales-badge rounded-pill px-3 py-2">{{ $incomeDaily->count() }} hari aktif</span>
            </div>
            @if ($incomeDaily->isEmpty())
                <div class="sales-empty text-center"><i class="bi bi-cash-coin d-block fs-3 mb-2"></i>Belum ada data penghasilan pada periode ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table">
                        <thead>
                            <tr>
                                <th class="ps-3">Tanggal</th>
                                <th class="text-end">Order</th>
                                <th class="text-end">Sudah Cair</th>
                                <th class="text-end">Belum Cair</th>
                                <th class="text-end">Pembayaran Pembeli</th>
                                <th class="text-end pe-3">Dana Cair</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($incomeDaily as $income)
                                <tr>
                                    <td class="ps-3 fw-semibold">{{ $dateLabel($income->day) }}</td>
                                    <td class="text-end">{{ number_format($income->orders) }}</td>
                                    <td class="text-end">{{ number_format($income->settled_orders) }}</td>
                                    <td class="text-end">{{ number_format($income->pending_orders) }}</td>
                                    <td class="text-end">{{ $fmt($income->buyer_paid) }}</td>
                                    <td class="text-end pe-3 fw-semibold">{{ $fmt($income->final_income) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <div class="sales-tab-pane {{ $activeTab === 'orders' ? '' : 'is-hidden' }}" data-sales-pane="orders" role="tabpanel" aria-hidden="{{ $activeTab === 'orders' ? 'false' : 'true' }}">
        @include('marketplace.dashboard.partials._kpis', [
            'kpiTitle' => 'Detail Order',
            'kpis' => [
                ['label' => 'Jumlah Order', 'value' => number_format($summary['orders']), 'note' => 'periode aktif', 'icon' => 'bi-receipt'],
                ['label' => 'Unit Terjual', 'value' => number_format($summary['qty']), 'note' => 'unit marketplace', 'icon' => 'bi-boxes', 'variant' => 'sales-kpi--success'],
                ['label' => 'Nilai Bruto', 'value' => $fmt($summary['subtotal']), 'note' => 'subtotal order', 'icon' => 'bi-cash-stack'],
                ['label' => 'Nilai Neto', 'value' => $fmt($summary['net_total']), 'note' => 'setelah promosi', 'icon' => 'bi-graph-down-arrow'],
            ],
        ])
        <section class="card sales-card shadow-sm">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <div class="sales-kicker mb-1">Order operations</div>
                    <h2 class="sales-section-title mb-1">Detail pesanan</h2>
                </div>
                <span class="badge sales-badge rounded-pill px-3 py-2"><span data-sales-order-count>{{ $orderDetails->count() }}</span> order dimuat</span>
            </div>

            @if ($orderDetails->isEmpty())
                <div class="sales-empty text-center"><i class="bi bi-receipt d-block fs-3 mb-2"></i>Belum ada detail pesanan pada periode ini.</div>
            @else
                <div class="px-3 pb-3">
                    <label class="visually-hidden" for="sales-order-detail-date">Filter tanggal</label>
                    <select id="sales-order-detail-date" class="form-select form-select-sm" style="max-width:260px">
                        <option value="">Semua tanggal pada periode</option>
                        @foreach ($daily as $row)
                            <option value="{{ $row->day }}">{{ $dateLabel($row->day) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table sales-order-table">
                        <thead>
                            <tr>
                                <th class="ps-3 sales-order-cell">No. Pesanan</th>
                                <th>Pelanggan</th>
                                <th>Kota</th>
                                <th>Provinsi</th>
                                <th>Pembayaran</th>
                                <th class="text-end sales-order-promotion">Promosi</th>
                                <th class="text-end pe-3 sales-order-total">Total Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orderDetails as $order)
                                <tr class="sales-clickable-row" data-sales-order-row data-order-day="{{ $order->day }}" data-sales-order-detail-url="{{ route('marketplace.orders.show', ['order' => $order->id]) }}" tabindex="0" role="link" aria-label="Lihat detail pesanan {{ $order->order_number }}">
                                    <td class="ps-3 sales-order-cell">
                                        <div class="sales-order-number">{{ $order->order_number }}</div>
                                        <div class="sales-order-meta">
                                            {{ $order->store_name ?: '-' }} · {{ \Carbon\Carbon::parse($order->order_at)->format('d M Y H:i') }}
                                        </div>
                                    </td>
                                    <td class="sales-order-buyer">{{ $order->buyer }}</td>
                                    <td class="sales-order-location">{{ $order->shipping_city ?: '-' }}</td>
                                    <td class="sales-order-location">{{ $order->shipping_province ?: '-' }}</td>
                                    <td class="text-muted">{{ ucwords(str_replace('_', ' ', strtolower($order->payment))) }}</td>
                                    <td class="text-end sales-order-promotion">
                                        <div>Voucher toko: {{ $fmt($order->voucher_store) }}</div>
                                        <div class="small text-muted">Voucher platform: {{ $fmt($order->voucher_platform) }}</div>
                                        <div class="small text-muted">Paket diskon: {{ $fmt($order->bundle_discount) }}</div>
                                    </td>
                                    <td class="text-end pe-3 sales-order-total">
                                        <div class="fw-semibold">{{ $fmt($order->total_payment) }}</div>
                                        <div class="small text-muted">Subtotal pesanan: {{ $fmt($order->subtotal) }}</div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="sales-empty d-none text-center" data-sales-order-empty>Tidak ada pesanan pada tanggal yang dipilih.</div>
            @endif
        </section>
    </div>

    <div class="sales-tab-pane {{ $activeTab === 'products' ? '' : 'is-hidden' }}" data-sales-pane="products" role="tabpanel" aria-hidden="{{ $activeTab === 'products' ? 'false' : 'true' }}">
        @include('marketplace.dashboard.partials._kpis', [
            'kpiTitle' => 'Produk',
            'kpis' => [
                ['label' => 'Unit Terjual', 'value' => number_format($summary['qty']), 'note' => 'unit marketplace', 'icon' => 'bi-boxes'],
                ['label' => 'Nilai Bruto Produk', 'value' => $fmt($summary['subtotal']), 'note' => 'sebelum diskon produk', 'icon' => 'bi-cash-stack', 'variant' => 'sales-kpi--success'],
                ['label' => 'Diskon Produk', 'value' => $fmt($promotionTotals['product_discount']), 'note' => 'nilai potongan produk', 'icon' => 'bi-tag'],
                ['label' => 'Nilai Neto Produk', 'value' => $fmt($netProductTotal), 'note' => 'setelah diskon produk', 'icon' => 'bi-graph-down-arrow'],
            ],
        ])
        <section class="card sales-card shadow-sm">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <div class="sales-kicker mb-1">Kinerja produk</div>
                    <h2 class="sales-section-title mb-1">Produk terlaris</h2>
                    <div class="sales-section-subtitle">Produk diurutkan berdasarkan nilai penjualan pada periode aktif.</div>
                </div>
                <span class="badge sales-badge rounded-pill px-3 py-2">{{ $products->count() }} produk</span>
            </div>
            @if ($products->isEmpty())
                <div class="sales-empty text-center"><i class="bi bi-box-seam d-block fs-3 mb-2"></i>Belum ada detail produk pada periode ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table">
                        <thead><tr><th class="ps-3">No.</th><th>Produk</th><th>SKU</th><th class="text-end">Qty</th><th class="text-end">Penjualan</th><th class="text-end">Pembayaran Pembeli</th><th class="text-end">AOV</th><th class="text-end pe-3">APC</th></tr></thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td class="ps-3 text-muted fw-semibold">{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">
                                        <button type="button" class="sales-product-link" data-sales-product-name="{{ $product->name }}" data-sales-product-sku="{{ $product->sku }}" title="Lihat pesanan produk: {{ $product->name }}">
                                            <span class="sales-product-name">{{ $product->name }}</span>
                                        </button>
                                    </td>
                                    <td class="text-muted small">{{ $product->sku }}</td>
                                    <td class="text-end">{{ number_format((int) $product->qty) }}</td>
                                    <td class="text-end fw-semibold">{{ $fmt($product->sales) }}</td>
                                    <td class="text-end fw-semibold">{{ $fmt($product->buyer_payment) }}</td>
                                    <td class="text-end" title="Average Order Value: penjualan dibagi jumlah order">{{ $product->orders > 0 ? $fmt($product->sales / $product->orders) : '—' }}</td>
                                    <td class="text-end" title="Average Payment per Customer: pembayaran pembeli dibagi pembeli unik">{{ $product->buyers > 0 ? $fmt($product->buyer_payment / $product->buyers) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <div class="sales-tab-pane {{ $activeTab === 'payments' ? '' : 'is-hidden' }}" data-sales-pane="payments" role="tabpanel" aria-hidden="{{ $activeTab === 'payments' ? 'false' : 'true' }}">
        @include('marketplace.dashboard.partials._kpis', [
            'kpiTitle' => 'Pembayaran',
            'kpis' => [
                ['label' => 'Total Dibayar Pembeli', 'value' => $fmt($paymentSummary['buyer_paid']), 'note' => number_format($paymentSummary['orders']).' order pada periode aktif', 'icon' => 'bi-cash-stack', 'variant' => 'sales-kpi--success'],
                ['label' => 'Average Ticket', 'value' => $fmt($paymentSummary['aov']), 'note' => 'rata-rata daya beli / order', 'icon' => 'bi-graph-up-arrow'],
                ['label' => 'Median Ticket', 'value' => $fmt($paymentSummary['median_ticket']), 'note' => 'nilai tipikal yang dibayar customer', 'icon' => 'bi-bar-chart-line'],
                ['label' => 'High-value Orders', 'value' => number_format($paymentSummary['high_value_orders']), 'note' => '≥ 1,5× average ticket', 'icon' => 'bi-stars', 'variant' => 'sales-kpi--warning'],
            ],
        ])
        <section class="card sales-card shadow-sm mb-3">
            <div class="sales-section-header">
                <div class="sales-kicker mb-1">Purchasing power trend</div>
                <h2 class="sales-section-title mb-1">Daya beli per tanggal</h2>
                <div class="sales-section-subtitle">Lihat perubahan nominal yang dibayar customer, average ticket, dan exposure COD sebagai indikator risiko fulfillment. Klik tanggal untuk drill-down.</div>
            </div>
            @if ($paymentDaily->isEmpty())
                <div class="sales-empty text-center"><i class="bi bi-wallet2 d-block fs-3 mb-2"></i>Belum ada data pembayaran pada periode ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table">
                        <thead><tr><th class="ps-3">Tanggal</th><th class="text-end">Order</th><th class="text-end">COD</th><th class="text-end">Non-COD</th><th class="text-end">Pay Later</th><th class="text-end">Dibayar Pembeli</th><th class="text-end">COD Exposure</th><th class="text-end">Average Ticket</th><th class="text-end pe-3">Aksi</th></tr></thead>
                        <tbody>
                            @foreach ($paymentDaily as $payment)
                                <tr class="sales-clickable-row" data-sales-payment-detail-url="{{ route('marketplace.dashboard.payments.detail', array_merge(['date' => $payment->day], $paymentDetailQuery)) }}" tabindex="0" role="button" aria-label="Lihat detail pembayaran {{ $dateLabel($payment->day) }}">
                                    <td class="ps-3 fw-semibold">{{ $dateLabel($payment->day) }}</td>
                                    <td class="text-end">{{ number_format($payment->orders) }}</td>
                                    <td class="text-end"><div class="fw-semibold">{{ $fmt($payment->cod_amount) }}</div><div class="small text-muted">{{ number_format($payment->cod_orders) }} order</div></td>
                                    <td class="text-end"><div class="fw-semibold">{{ $fmt($payment->non_cod_amount) }}</div><div class="small text-muted">{{ number_format($payment->non_cod_orders) }} order</div></td>
                                    <td class="text-end"><div class="fw-semibold">{{ $fmt($payment->pay_later_amount) }}</div><div class="small text-muted">{{ number_format($payment->pay_later_orders) }} order</div></td>
                                    <td class="text-end fw-semibold">{{ $fmt($payment->buyer_paid) }}</td>
                                    <td class="text-end">{{ number_format($payment->cod_order_share, 1) }}%</td>
                                    <td class="text-end">{{ $fmt($payment->aov) }}</td>
                                    <td class="text-end pe-3"><span class="sales-action-link">Lihat detail <i class="bi bi-arrow-right"></i></span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
        <div class="row g-3">
            <div class="col-12 col-xl-8">
                <section class="card sales-card shadow-sm h-100">
                    <div class="sales-section-header">
                        <div class="sales-kicker mb-1">Payment mix</div>
                        <h2 class="sales-section-title mb-1">Metode pembayaran</h2>
                        <div class="sales-section-subtitle">Kontribusi setiap metode terhadap nominal yang benar-benar dibayar customer.</div>
                    </div>
                    @if ($payments->isEmpty())
                        <div class="sales-empty text-center"><i class="bi bi-credit-card d-block fs-3 mb-2"></i>Belum ada metode pembayaran.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle sales-table">
                                <thead><tr><th class="ps-3">Payment mix</th><th class="text-end">COD</th><th class="text-end">Non-COD</th><th class="text-end pe-3">Pay Later</th></tr></thead>
                                <tbody>
                                    <tr><td class="ps-3 fw-semibold">Order</td>@foreach ($paymentMix as $payment)<td class="text-end">{{ number_format((int) $payment->orders) }}</td>@endforeach</tr>
                                    <tr><td class="ps-3 fw-semibold">Dibayar Pembeli</td>@foreach ($paymentMix as $payment)<td class="text-end fw-semibold">{{ $fmt($payment->buyer_paid) }}</td>@endforeach</tr>
                                    <tr><td class="ps-3 fw-semibold">Share Nominal</td>@foreach ($paymentMix as $payment)<td class="text-end">{{ number_format($paymentSummary['buyer_paid'] > 0 ? ($payment->buyer_paid / $paymentSummary['buyer_paid']) * 100 : 0, 1) }}%</td>@endforeach</tr>
                                    <tr><td class="ps-3 fw-semibold">Average Ticket</td>@foreach ($paymentMix as $payment)<td class="text-end">{{ $fmt($payment->avg_ticket) }}</td>@endforeach</tr>
                                    <tr><td class="ps-3 fw-semibold">Order Share</td>@foreach ($paymentMix as $payment)<td class="text-end">{{ number_format($paymentSummary['orders'] > 0 ? ($payment->orders / $paymentSummary['orders']) * 100 : 0, 1) }}%</td>@endforeach</tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="card sales-card shadow-sm h-100">
                    <div class="sales-section-header">
                        <div class="sales-kicker mb-1">Customer purchasing power</div>
                        <h2 class="sales-section-title mb-1">Daya beli customer</h2>
                        <div class="sales-section-subtitle">Indikator nilai transaksi dan performa pembayaran periode aktif.</div>
                    </div>
                    <div class="p-3 pt-0">
                        <div class="border rounded p-3 mb-2">
                            <div class="small text-muted">COD exposure</div>
                            <div class="h4 mb-0">{{ number_format($paymentSummary['cod_order_share'], 1) }}%</div>
                            <div class="small text-muted">{{ number_format($paymentDaily->sum('cod_orders')) }} dari {{ number_format($paymentSummary['orders']) }} order</div>
                        </div>
                        <div class="border rounded p-3 mb-2">
                            <div class="small text-muted">COD amount exposure</div>
                            <div class="h5 mb-0">{{ $fmt($paymentSummary['cod_amount']) }}</div>
                            <div class="small text-muted">{{ number_format($paymentSummary['cod_amount_share'], 1) }}% dari total dibayar pembeli</div>
                        </div>
                        <div class="border rounded p-3">
                            <div class="small text-muted">Metode dengan nominal terbesar</div>
                            <div class="fw-semibold">{{ $topPaymentMethod ? ($paymentCategoryLabels[$topPaymentMethod->category] ?? $topPaymentMethod->category) : '-' }}</div>
                            <div class="small text-muted">{{ $topPaymentMethod ? $fmt($topPaymentMethod->buyer_paid) : 'Belum ada data' }}</div>
                        </div>
                        @if ($peakPaymentDay)
                            <div class="small text-muted mt-3">Peak average ticket: <strong>{{ $fmt($peakPaymentDay->aov) }}</strong> pada {{ $dateLabel($peakPaymentDay->day) }}</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>

    <div class="sales-tab-pane {{ $activeTab === 'promotions' ? '' : 'is-hidden' }}" data-sales-pane="promotions" role="tabpanel" aria-hidden="{{ $activeTab === 'promotions' ? 'false' : 'true' }}">
        @include('marketplace.dashboard.partials._kpis', [
            'kpiTitle' => 'Promosi',
            'kpis' => [
                ['label' => 'Diskon Produk', 'value' => $fmt($promotionTotals['product_discount']), 'note' => 'nilai potongan produk', 'icon' => 'bi-tag'],
                ['label' => 'Voucher Toko', 'value' => $fmt($promotionTotals['voucher_store']), 'note' => 'voucher seller', 'icon' => 'bi-ticket-perforated', 'variant' => 'sales-kpi--success'],
                ['label' => 'Voucher Platform', 'value' => $fmt($promotionTotals['voucher_platform']), 'note' => 'voucher marketplace', 'icon' => 'bi-shop'],
                ['label' => 'Bundle Deal', 'value' => $fmt($promotionTotals['bundle_discount']), 'note' => 'paket diskon', 'icon' => 'bi-gift', 'variant' => 'sales-kpi--warning'],
            ],
        ])
        <section class="card sales-card shadow-sm">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <div class="sales-kicker mb-1">Dampak promosi</div>
                    <h2 class="sales-section-title mb-1">Promosi per tanggal</h2>
                    <div class="sales-section-subtitle">Total promosi adalah gabungan diskon produk, voucher toko, voucher platform, dan paket diskon.</div>
                </div>
                <span class="badge sales-badge rounded-pill px-3 py-2">{{ number_format($promotionDaily->count()) }} hari tercatat</span>
            </div>
            @if ($promotionDaily->isEmpty())
                <div class="sales-empty text-center"><i class="bi bi-percent d-block fs-3 mb-2"></i>Belum ada order pada periode ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table">
                        <thead>
                            <tr>
                                <th class="ps-3">Tanggal</th>
                                <th class="text-end">Nilai Bruto</th>
                                <th class="text-end">Diskon produk</th>
                                <th class="text-end">Nilai Neto</th>
                                <th class="text-end">Voucher Toko</th>
                                <th class="text-end">Voucher Platform</th>
                                <th class="text-end">Bundle Deal</th>
                                <th class="text-end pe-3">Total Promosi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($promotionDaily as $row)
                                <tr class="sales-clickable-row" data-sales-promotion-detail-url="{{ route('marketplace.dashboard.promotions.detail', ['date' => $row->day]) }}" tabindex="0" role="button" aria-label="Lihat detail promosi {{ $dateLabel($row->day) }}">
                                    <td class="ps-3 fw-semibold">{{ $dateLabel($row->day) }}</td>
                                    <td class="text-end">
                                        <div>{{ $fmt($row->order_before_discount ?? 0) }}</div>
                                    </td>
                                    <td class="text-end">
                                        <div>{{ $fmt($row->product_discount) }}</div>
                                    </td>
                                    <td class="text-end">{{ $fmt(max((float) ($row->order_before_discount ?? 0) - (float) ($row->product_discount ?? 0), 0)) }}</td>
                                    <td class="text-end">
                                        <div>{{ $fmt($row->voucher_store) }}</div>
                                        <div class="small text-muted">{{ number_format((int) ($row->voucher_store_orders ?? 0)) }} order</div>
                                    </td>
                                    <td class="text-end">
                                        <div>{{ $fmt($row->voucher_platform) }}</div>
                                        <div class="small text-muted">{{ number_format((int) ($row->voucher_platform_orders ?? 0)) }} order</div>
                                    </td>
                                    <td class="text-end">
                                        <div>{{ $fmt($row->bundle_discount) }}</div>
                                        <div class="small text-muted">{{ number_format((int) ($row->bundle_discount_orders ?? 0)) }} order</div>
                                    </td>
                                    <td class="text-end pe-3 fw-semibold">{{ $fmt($row->total_promotion) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <div class="sales-tab-pane {{ $activeTab === 'shipping' ? '' : 'is-hidden' }}" data-sales-pane="shipping" role="tabpanel" aria-hidden="{{ $activeTab === 'shipping' ? 'false' : 'true' }}">
        @include('marketplace.dashboard.partials._kpis', [
            'kpiTitle' => 'Pengiriman',
            'kpis' => [
                ['label' => 'Jumlah Order', 'value' => number_format($shipping->sum('orders')), 'note' => 'order terdistribusi', 'icon' => 'bi-receipt'],
                ['label' => 'Siap Dikirim', 'value' => number_format($shippingOrders(['PENDING', 'INVOICE_PENDING', 'READY_TO_SHIP', 'MATCHED'])), 'note' => 'status operasional', 'icon' => 'bi-box-arrow-up', 'variant' => 'sales-kpi--success'],
                ['label' => 'Dalam Pengiriman', 'value' => number_format($shippingOrders(['PROCESSED', 'READY_TO_HANDOVER', 'SHIPPED', 'TO_CONFIRM_RECEIVE'])), 'note' => 'status transit', 'icon' => 'bi-truck'],
                ['label' => 'Selesai', 'value' => number_format($shippingOrders(['COMPLETED', 'SELESAI'])), 'note' => 'order selesai', 'icon' => 'bi-check2-circle', 'variant' => 'sales-kpi--warning'],
                ['label' => 'Gagal', 'value' => number_format($shippingOrders(['FAILED_DELIVERY'])), 'note' => 'pengiriman gagal', 'icon' => 'bi-exclamation-triangle', 'variant' => 'sales-kpi--warning'],
                ['label' => 'Return', 'value' => number_format($shippingOrders(['TO_RETURN', 'RETURNING', 'RETURNED', 'REFUND', 'REFUNDED'])), 'note' => 'return atau refund', 'icon' => 'bi-arrow-return-left', 'variant' => 'sales-kpi--warning'],
            ],
        ])
        <section class="card sales-card shadow-sm">
            <div class="sales-section-header">
                <div class="sales-kicker mb-1">Fulfillment marketplace</div>
                <h2 class="sales-section-title mb-1">Status pengiriman per tanggal</h2>
                <div class="sales-section-subtitle">Jumlah order per tanggal dengan pecahan status yang tersimpan di marketplace.</div>
            </div>
            @if ($shippingDaily->isEmpty())
                <div class="sales-empty text-center"><i class="bi bi-truck d-block fs-3 mb-2"></i>Belum ada data pengiriman pada periode ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table">
                        <thead>
                            <tr>
                                <th class="ps-3">Tanggal</th>
                                <th class="text-end">Total Order</th>
                                <th class="text-end">Siap Dikirim</th>
                                <th class="text-end">Dalam Pengiriman</th>
                                <th class="text-end">Selesai</th>
                                <th class="text-end">Gagal</th>
                                <th class="text-end">Return</th>
                                <th class="text-end pe-3">Status Lainnya</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($shippingDaily as $row)
                                <tr class="sales-clickable-row" data-sales-shipping-detail-url="{{ route('marketplace.dashboard.shipping.detail', array_merge(['date' => $row->day], $shippingDetailQuery)) }}" tabindex="0" role="button" aria-label="Lihat detail pengiriman {{ $dateLabel($row->day) }}">
                                    <td class="ps-3 fw-semibold">{{ $dateLabel($row->day) }}</td>
                                    <td class="text-end">{{ number_format($row->orders) }}</td>
                                    <td class="text-end">{{ number_format($row->ready_orders) }}</td>
                                    <td class="text-end">{{ number_format($row->transit_orders) }}</td>
                                    <td class="text-end">{{ number_format($row->completed_orders) }}</td>
                                    <td class="text-end">{{ number_format($row->failed_orders) }}</td>
                                    <td class="text-end">{{ number_format($row->return_orders) }}</td>
                                    <td class="text-end pe-3">{{ number_format($row->other_orders) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('[data-sales-tab]');
        const panes = document.querySelectorAll('[data-sales-pane]');
        const filterForm = document.querySelector('.sales-filter-card');
        const activeTabInput = document.querySelector('#sales-active-tab');
        const dateFromInput = document.querySelector('#sales-date-from');
        const dateToInput = document.querySelector('#sales-date-to');
        const storeInput = document.querySelector('#sales-store');
        const orderRows = document.querySelectorAll('[data-sales-order-row]');
        const orderDate = document.querySelector('#sales-order-detail-date');
        const orderCount = document.querySelector('[data-sales-order-count]');
        const orderEmpty = document.querySelector('[data-sales-order-empty]');

        function activateTab(target) {
            if (!document.querySelector('[data-sales-tab="' + target + '"]')) {
                target = 'sales';
            }

            tabs.forEach(function (item) {
                const active = item.dataset.salesTab === target;
                item.classList.toggle('active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            panes.forEach(function (pane) {
                const active = pane.dataset.salesPane === target;
                pane.classList.toggle('is-hidden', !active);
                pane.setAttribute('aria-hidden', active ? 'false' : 'true');
            });

            if (activeTabInput) {
                activeTabInput.value = target;
            }

            const url = new URL(window.location.href);
            if (url.searchParams.get('tab') !== target) {
                url.searchParams.set('tab', target);
                window.history.replaceState(null, '', url.toString());
            }
        }

        function filterOrderRows(day) {
            let visible = 0;
            orderRows.forEach(function (row) {
                const show = !day || row.dataset.orderDay === day;
                row.classList.toggle('d-none', !show);
                if (show) visible += 1;
            });
            if (orderCount) orderCount.textContent = visible;
            if (orderEmpty) orderEmpty.classList.toggle('d-none', visible > 0);
        }

        document.querySelectorAll('[data-sales-order-detail-url]').forEach(function (trigger) {
            function openOrderDetail() {
                window.location.href = trigger.dataset.salesOrderDetailUrl;
            }

            trigger.addEventListener('click', openOrderDetail);
            trigger.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openOrderDetail();
                }
            });
        });

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activateTab(tab.dataset.salesTab);
            });
        });

        // Filter periode ala MarketLens: preset + pilihan hari/minggu/bulan/tahun
        // dalam satu popover, dengan submit otomatis setelah pilihan selesai.
        const periodFilter = document.querySelector('[data-sales-period-filter]');
        const periodTrigger = periodFilter?.querySelector('[data-sales-period-trigger]');
        const periodPopover = periodFilter?.querySelector('[data-sales-period-popover]');
        const periodPanels = periodFilter ? periodFilter.querySelectorAll('[data-sales-period-panel]') : [];
        const periodTriggerLabel = periodFilter?.querySelector('[data-sales-period-trigger-label]');
        const periodTriggerSummary = periodFilter?.querySelector('[data-sales-period-trigger-summary]');
        const periodDetailLabel = periodFilter?.querySelector('[data-sales-period-detail-label]');
        const periodDetailHelp = periodFilter?.querySelector('[data-sales-period-detail-help]');
        const monthGrid = periodFilter?.querySelector('[data-sales-period-month-grid]');
        const monthYear = periodFilter?.querySelector('[data-sales-period-month-year]');
        const yearGrid = periodFilter?.querySelector('[data-sales-period-year-grid]');
        const yearRange = periodFilter?.querySelector('[data-sales-period-year-range]');
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
        let monthViewYear = new Date().getFullYear();
        let yearViewStart = Math.floor(new Date().getFullYear() / 10) * 10;

        const dateToYmd = function (date) {
            return [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
        };
        const parseYmd = function (value) {
            if (!value) return null;
            const parts = value.split('-').map(Number);
            return parts.length === 3 ? new Date(parts[0], parts[1] - 1, parts[2]) : null;
        };
        const displayRange = function (from, to) {
            return from && to ? (from === to ? from : from + ' – ' + to) : 'Pilih rentang tanggal';
        };
        const closePeriodPopover = function () {
            if (!periodPopover || !periodTrigger) return;
            periodPopover.hidden = true;
            periodTrigger.setAttribute('aria-expanded', 'false');
        };
        const setPeriodView = function (view) {
            if (!periodFilter) return;
            periodPanels.forEach(function (panel) {
                panel.hidden = panel.dataset.salesPeriodPanel !== view;
            });
            periodFilter.querySelectorAll('[data-sales-period-view]').forEach(function (option) {
                option.classList.toggle('is-selected', option.dataset.salesPeriodView === view);
            });
            if (periodDetailHelp && view === 'default') {
                periodDetailHelp.textContent = 'Pilih preset di sebelah kiri atau tentukan rentang tanggal.';
            }
        };
        const syncPeriodSummary = function (label, from, to) {
            const summary = displayRange(from, to);
            if (periodTriggerLabel) periodTriggerLabel.textContent = label;
            if (periodTriggerSummary) periodTriggerSummary.textContent = summary;
            if (periodDetailLabel) periodDetailLabel.textContent = summary;
            periodFilter?.querySelectorAll('[data-sales-period-preset]').forEach(function (option) {
                option.classList.toggle('is-selected', option.dataset.salesPeriodLabel === label);
            });
        };
        const submitPeriod = function (from, to, label, view) {
            dateFromInput.value = from;
            dateToInput.value = to;
            syncPeriodSummary(label, from, to);
            setPeriodView(view || 'default');
            closePeriodPopover();
            filterForm?.requestSubmit();
        };
        const rangeForPreset = function (preset) {
            const end = new Date();
            const start = new Date(end);
            if (preset === 'yesterday') {
                start.setDate(start.getDate() - 1);
                end.setDate(end.getDate() - 1);
            } else if (preset === 'last-7-days') {
                start.setDate(start.getDate() - 6);
            } else if (preset === 'last-30-days') {
                start.setDate(start.getDate() - 29);
            }
            return [dateToYmd(start), dateToYmd(end)];
        };

        if (periodFilter && filterForm && window.flatpickr) {
            const dateRangePicker = document.querySelector('#sales-date-range');
            const dayPicker = document.querySelector('#sales-date-day');
            const weekPicker = document.querySelector('#sales-date-week');
            const flatpickrLocale = window.flatpickr.l10ns?.id || 'default';

            window.flatpickr(dateRangePicker, {
                mode: 'range', dateFormat: 'Y-m-d', altInput: false,
                locale: flatpickrLocale, disableMobile: true, positionElement: periodTrigger,
                defaultDate: [dateFromInput.value, dateToInput.value].filter(Boolean),
                onChange: function (dates, value, instance) {
                    dateFromInput.value = dates[0] ? instance.formatDate(dates[0], 'Y-m-d') : '';
                    dateToInput.value = dates[1] ? instance.formatDate(dates[1], 'Y-m-d') : '';
                    if (dates.length === 2) submitPeriod(dateFromInput.value, dateToInput.value, 'Rentang tanggal');
                },
            });

            window.flatpickr(dayPicker, {
                inline: true, dateFormat: 'Y-m-d', locale: flatpickrLocale, disableMobile: true,
                defaultDate: dateFromInput.value,
                onChange: function (dates, value, instance) {
                    if (dates.length) submitPeriod(instance.formatDate(dates[0], 'Y-m-d'), instance.formatDate(dates[0], 'Y-m-d'), 'Per Hari', 'day');
                },
            });

            let settingWeek = false;
            window.flatpickr(weekPicker, {
                mode: 'range', inline: true, dateFormat: 'Y-m-d', locale: flatpickrLocale, disableMobile: true,
                defaultDate: [dateFromInput.value, dateToInput.value].filter(Boolean),
                onChange: function (dates, value, instance) {
                    if (!dates.length || settingWeek) return;
                    const start = new Date(dates[dates.length - 1]);
                    start.setDate(start.getDate() - ((start.getDay() + 6) % 7));
                    const end = new Date(start);
                    end.setDate(end.getDate() + 6);
                    settingWeek = true;
                    instance.setDate([start, end], false);
                    settingWeek = false;
                    submitPeriod(dateToYmd(start), dateToYmd(end), 'Per Minggu', 'week');
                },
            });

            periodFilter.querySelectorAll('[data-sales-period-preset]').forEach(function (option) {
                option.addEventListener('mouseenter', function () { setPeriodView('default'); });
                option.addEventListener('click', function () {
                    const range = rangeForPreset(option.dataset.salesPeriodPreset);
                    submitPeriod(range[0], range[1], option.dataset.salesPeriodLabel);
                });
            });
            periodFilter.querySelectorAll('[data-sales-period-view]').forEach(function (option) {
                option.addEventListener('mouseenter', function () { setPeriodView(option.dataset.salesPeriodView); });
                option.addEventListener('click', function () { setPeriodView(option.dataset.salesPeriodView); });
            });

            const applyMonth = function (year, month) {
                submitPeriod(dateToYmd(new Date(year, month, 1)), dateToYmd(new Date(year, month + 1, 0)), 'Per Bulan', 'month');
            };
            const renderMonths = function (year) {
                if (!monthGrid || !monthYear) return;
                monthViewYear = year;
                monthYear.textContent = String(year);
                monthGrid.replaceChildren();
                monthNames.forEach(function (name, month) {
                    const button = document.createElement('button');
                    button.type = 'button'; button.textContent = name;
                    button.className = dateFromInput.value === dateToYmd(new Date(year, month, 1)) ? 'is-selected' : '';
                    button.addEventListener('click', function () { applyMonth(year, month); });
                    monthGrid.appendChild(button);
                });
            };
            const renderYears = function (start) {
                if (!yearGrid || !yearRange) return;
                yearViewStart = start;
                yearRange.textContent = start + ' – ' + (start + 9);
                yearGrid.replaceChildren();
                const currentYear = new Date().getFullYear();
                for (let year = start; year <= start + 9; year += 1) {
                    const button = document.createElement('button');
                    button.type = 'button'; button.textContent = String(year); button.disabled = year > currentYear;
                    button.className = dateFromInput.value === dateToYmd(new Date(year, 0, 1)) ? 'is-selected' : '';
                    if (!button.disabled) button.addEventListener('click', function () { submitPeriod(dateToYmd(new Date(year, 0, 1)), dateToYmd(new Date(year, 11, 31)), 'Berdasarkan Tahun', 'year'); });
                    yearGrid.appendChild(button);
                }
            };
            periodFilter.querySelector('[data-sales-period-month-prev]')?.addEventListener('click', function () { renderMonths(monthViewYear - 1); });
            periodFilter.querySelector('[data-sales-period-month-next]')?.addEventListener('click', function () { renderMonths(monthViewYear + 1); });
            periodFilter.querySelector('[data-sales-period-year-prev]')?.addEventListener('click', function () { renderYears(yearViewStart - 10); });
            periodFilter.querySelector('[data-sales-period-year-next]')?.addEventListener('click', function () { renderYears(yearViewStart + 10); });
            renderMonths(parseYmd(dateFromInput.value)?.getFullYear() || new Date().getFullYear());
            renderYears(Math.floor((parseYmd(dateFromInput.value)?.getFullYear() || new Date().getFullYear()) / 10) * 10);

            periodTrigger?.addEventListener('click', function () {
                const willOpen = periodPopover.hidden;
                periodPopover.hidden = !willOpen;
                periodTrigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                if (willOpen) {
                    setPeriodView('default');
                    dateRangePicker._flatpickr?.open();
                } else {
                    dateRangePicker._flatpickr?.close();
                }
            });
            document.addEventListener('click', function (event) {
                if (!periodFilter.contains(event.target) && !event.target.closest('.flatpickr-calendar')) closePeriodPopover();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closePeriodPopover();
            });
        }

        if (storeInput && filterForm) {
            storeInput.addEventListener('change', function () {
                filterForm.requestSubmit();
            });
        }

        document.querySelectorAll('[data-sales-order-detail-date]').forEach(function (trigger) {
            function openOrderDetail() {
                const day = trigger.dataset.salesOrderDetailDate || '';
                if (orderDate) orderDate.value = day;
                filterOrderRows(day);
                activateTab('orders');
            }

            trigger.addEventListener('click', openOrderDetail);
            if (trigger.matches('tr')) {
                trigger.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openOrderDetail();
                    }
                });
            }
        });

        document.querySelectorAll('[data-sales-promotion-detail-url]').forEach(function (trigger) {
            function openPromotionDetail() {
                const currentQuery = new URLSearchParams(window.location.search);
                const target = new URL(trigger.dataset.salesPromotionDetailUrl, window.location.origin);
                if (currentQuery.get('dummy') === '1') target.searchParams.set('dummy', '1');
                if (currentQuery.get('store_id')) target.searchParams.set('store_id', currentQuery.get('store_id'));
                window.location.href = target.toString();
            }

            trigger.addEventListener('click', openPromotionDetail);
            if (trigger.matches('tr')) {
                trigger.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openPromotionDetail();
                    }
                });
            }
        });

        document.querySelectorAll('[data-sales-payment-detail-url]').forEach(function (trigger) {
            function openPaymentDetail() {
                window.location.href = trigger.dataset.salesPaymentDetailUrl;
            }

            trigger.addEventListener('click', openPaymentDetail);
            trigger.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openPaymentDetail();
                }
            });
        });

        document.querySelectorAll('[data-sales-shipping-detail-url]').forEach(function (trigger) {
            function openShippingDetail() {
                window.location.href = trigger.dataset.salesShippingDetailUrl;
            }

            trigger.addEventListener('click', openShippingDetail);
            trigger.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openShippingDetail();
                }
            });
        });

        document.querySelectorAll('[data-sales-product-name]').forEach(function (trigger) {
            trigger.addEventListener('click', async function () {
                const name = trigger.dataset.salesProductName || '';
                const sku = trigger.dataset.salesProductSku || '';
                const query = new URLSearchParams({
                    name: name,
                    sku: sku,
                    date_from: @json($filters['date_from']),
                    date_to: @json($filters['date_to']),
                });
                @if ($filters['store_id'])
                    query.set('store_id', @json($filters['store_id']));
                @endif
                @if (!empty($filters['dummy']))
                    query.set('dummy', '1');
                @endif

                if (typeof Swal === 'undefined') {
                    window.location.href = @json(route('marketplace.dashboard.products.orders')) + '?' + query.toString();
                    return;
                }

                Swal.fire({
                    title: 'Memuat pesanan…',
                    html: '<div class="py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                });

                try {
                    const response = await fetch(@json(route('marketplace.dashboard.products.orders')) + '?' + query.toString(), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'Gagal memuat pesanan.');

                    const orders = payload.orders || [];
                    const money = function (value) {
                        return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value || 0));
                    };
                    const date = function (value) {
                        if (!value) return '—';
                        return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
                    };
                    const rows = orders.length
                        ? orders.map(function (order) {
                            return '<tr>'
                                + '<td><strong>' + escapeHtml(order.order_number) + '</strong><div class="small text-muted">' + escapeHtml(date(order.order_at)) + '</div></td>'
                                + '<td>' + escapeHtml(order.buyer) + '</td>'
                                + '<td class="text-end">' + Number(order.qty || 0).toLocaleString('id-ID') + '</td>'
                                + '<td class="text-end">' + money(order.sales) + '</td>'
                                + '<td class="text-end">' + money(order.buyer_payment) + '</td>'
                                + '<td><span class="badge text-bg-light">' + escapeHtml(order.order_status) + '</span></td>'
                                + '</tr>';
                        }).join('')
                        : '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada pesanan untuk produk ini pada periode aktif.</td></tr>';

                    Swal.fire({
                        title: 'Pesanan produk',
                        html: '<div class="text-start mb-3"><strong>' + escapeHtml(name) + '</strong>' + (sku && sku !== '-' ? '<div class="small text-muted">SKU: ' + escapeHtml(sku) + '</div>' : '') + '</div>'
                            + '<div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0 text-start"><thead><tr><th>Order</th><th>Pembeli</th><th class="text-end">Qty</th><th class="text-end">Penjualan</th><th class="text-end">Pembayaran Pembeli</th><th>Status</th></tr></thead><tbody>' + rows + '</tbody></table></div>',
                        width: Math.min(window.innerWidth - 32, 1100),
                        confirmButtonText: 'Tutup',
                    });
                } catch (error) {
                    Swal.fire({ icon: 'error', title: 'Pesanan tidak dapat dimuat', text: error.message || 'Terjadi kesalahan.' });
                }
            });
        });

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>'\"]/g, function (character) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character];
            });
        }

        const initialTab = new URLSearchParams(window.location.search).get('tab');
        if (initialTab && document.querySelector('[data-sales-tab="' + initialTab + '"]')) {
            activateTab(initialTab);
        } else {
            activateTab(activeTabInput?.value || 'sales');
        }

        if (orderDate) {
            orderDate.addEventListener('change', function () {
                filterOrderRows(orderDate.value);
            });
        }
    });
</script>
@endpush
