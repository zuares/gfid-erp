@extends('layouts.app')

@section('title', 'Dashboard Penjualan • Marketplace')

@include('marketplace._shared')

@push('head')
<style>
    @media (min-width: 768px) {
        .app-main .page-wrap:has(.sales-dashboard) { max-width: none; }
    }

    .sales-dashboard {
        --sales-bg: var(--bg, #f4f5fb);
        --sales-line: var(--line, #d4d7e3);
        --sales-muted: var(--muted, #6b7280);
        --sales-card: var(--card, #fff);
        --sales-soft: var(--card-soft, #f9fafb);
        --sales-ink: var(--text, #111827);
        --sales-accent: var(--accent, #2563eb);
        --sales-accent-soft: var(--accent-soft, #dbeafe);
        max-width: 1280px;
        margin-inline: auto;
        color: var(--sales-ink);
    }

    .sales-dashboard .sales-card {
        border: 1px solid var(--sales-line);
        border-radius: 14px;
        background: var(--sales-card);
        box-shadow: 0 18px 45px rgba(15, 23, 42, .12), 0 1px 0 rgba(15, 23, 42, .04);
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
    .sales-dashboard .sales-section-subtitle { display: none; }

    .sales-dashboard .sales-filter-card { background: color-mix(in srgb, var(--sales-card) 85%, var(--sales-bg) 15%); }
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
    .sales-dashboard .sales-filter-card .form-control:focus,
    .sales-dashboard .sales-filter-card .form-select:focus {
        border-color: var(--accent, #2563eb);
        box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--accent, #2563eb) 18%, transparent);
    }
    .sales-dashboard .sales-period-filter { order: 3; margin-left: auto; }
    .sales-dashboard .sales-filter-store { order: 2; }

    .sales-dashboard .sales-nav { overflow-x: auto; scrollbar-width: none; }
    .sales-dashboard .sales-nav::-webkit-scrollbar { display: none; }
    .sales-dashboard .sales-nav {
        padding: .25rem;
        border: 1px solid var(--sales-line);
        border-radius: 12px;
        background: color-mix(in srgb, var(--sales-card) 90%, var(--sales-bg) 10%);
    }
    .sales-dashboard .sales-nav .nav-link {
        border: 0;
        color: var(--sales-muted);
        background: transparent;
        border-radius: 8px;
        font-size: .8rem;
        font-weight: 650;
        padding: .55rem .75rem;
        white-space: nowrap;
        transition: background .16s ease, color .16s ease, box-shadow .16s ease;
    }
    .sales-dashboard .sales-nav .nav-link:hover { color: var(--sales-accent); background: var(--sales-accent-soft); }
    .sales-dashboard .sales-nav .nav-link.active {
        color: var(--sales-accent);
        background: var(--sales-accent-soft);
        box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--sales-accent) 12%, var(--sales-line) 88%);
    }
    .sales-dashboard .sales-tab-pane.is-hidden { display: none; }

    .sales-dashboard .sales-kpi { min-height: 124px; position: relative; overflow: hidden; }
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
        background: var(--sales-accent-soft);
        color: var(--sales-accent);
        border: 1px solid color-mix(in srgb, var(--sales-accent) 12%, var(--sales-line) 88%);
    }
    .sales-dashboard .sales-kpi-value { color: var(--sales-ink); font-size: clamp(1rem, 1.2vw, 1.2rem); font-weight: 800; letter-spacing: -.025em; line-height: 1.15; white-space: nowrap; }
    .sales-dashboard .sales-kpi-comparison,
    .sales-dashboard .sales-compare-line {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .3rem;
        margin-top: 0;
        color: var(--sales-muted);
        font-size: .56rem;
        line-height: 1;
        letter-spacing: -.01em;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .sales-dashboard .sales-kpi-comparison {
        width: 7.4rem;
        flex-shrink: 0;
        padding: .18rem 0 .18rem .55rem;
        border: 0;
        border-left: 1px solid var(--sales-line);
        background: transparent;
        font-size: .56rem;
    }
    .sales-dashboard .sales-kpi-comparisons { display: flex; flex-direction: column; align-items: stretch; gap: .12rem; flex-shrink: 0; }
    .sales-dashboard .sales-compare-line i { font-size: .58rem; }
    .sales-dashboard .sales-compare-context { font-weight: 600; }
    .sales-dashboard .sales-kpi-comparison strong,
    .sales-dashboard .sales-compare-line strong { font-weight: 800; }
    .sales-dashboard .sales-kpi-comparison.is-good strong,
    .sales-dashboard .sales-compare-line.is-good strong { color: var(--success, #16a34a); }
    .sales-dashboard .sales-kpi-comparison.is-bad strong,
    .sales-dashboard .sales-compare-line.is-bad strong { color: var(--danger, #dc2626); }
    .sales-dashboard .sales-kpi-comparison.is-bad { border-color: var(--sales-line); }
    .sales-dashboard .sales-kpi-comparison.is-neutral strong,
    .sales-dashboard .sales-compare-line.is-neutral strong { color: var(--sales-muted); }
    .sales-dashboard .sales-kpi-comparison.is-neutral { background: transparent; }
    .sales-dashboard .sales-compare-period-note {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        color: var(--sales-muted);
        font-size: .7rem;
        font-weight: 650;
    }
    .sales-dashboard .sales-filter-comparison { min-width: 150px; }
    .sales-dashboard .sales-filter-comparison .form-label { margin-bottom: .28rem; }
    .sales-dashboard .sales-filter-comparison .form-select { min-height: 32px; font-size: .72rem; font-weight: 650; }
    .sales-dashboard .sales-kpi--success .sales-kpi-icon { background: var(--success-soft, #dcfce7); color: var(--success, #16a34a); border-color: color-mix(in srgb, var(--success, #16a34a) 16%, var(--sales-line) 84%); }
    .sales-dashboard .sales-kpi--success::after { background: var(--success, #16a34a); }
    .sales-dashboard .sales-kpi--warning .sales-kpi-icon { background: var(--danger-soft, #fee2e2); color: var(--danger, #dc2626); border-color: color-mix(in srgb, var(--danger, #dc2626) 16%, var(--sales-line) 84%); }
    .sales-dashboard .sales-kpi--warning::after { background: var(--danger, #dc2626); }

    .sales-dashboard .sales-section-header { padding: 1rem 1.15rem .85rem; }
    .sales-dashboard .sales-section-title { color: var(--sales-ink); font-size: 1rem; font-weight: 750; }
    .sales-dashboard .sales-detail-link { color: var(--accent, #2563eb); font-size: .75rem; font-weight: 700; text-decoration: none; }
    .sales-dashboard .sales-detail-link:hover { text-decoration: underline; }
    .sales-dashboard .sales-table { --bs-table-bg: var(--sales-card); --bs-table-color: var(--sales-ink); --bs-table-hover-bg: color-mix(in srgb, var(--accent-soft, #dbeafe) 35%, var(--sales-card)); margin-bottom: 0; }
    .sales-dashboard .sales-table th {
        background: color-mix(in srgb, var(--sales-card) 85%, var(--sales-bg) 15%);
        border-bottom-color: var(--sales-line);
        color: var(--sales-ink);
        font-size: .68rem;
        font-weight: 750;
        letter-spacing: .04em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .sales-dashboard .sales-table td { border-color: color-mix(in srgb, var(--sales-line) 65%, transparent); font-size: .78rem; }
    .sales-dashboard .sales-index-column,
    .sales-dashboard .sales-index-cell { width: 3.5rem; min-width: 3.5rem; text-align: center; }
    .sales-dashboard .sales-index-cell { color: var(--sales-muted); font-variant-numeric: tabular-nums; font-weight: 650; }
    .sales-dashboard .sales-daily-table { min-width: 980px; }
    .sales-dashboard .sales-daily-table .sales-table-metric { white-space: nowrap; }
    .sales-dashboard .sales-daily-table .sales-compare-line { margin-top: .18rem; gap: .2rem; font-size: .52rem; }
    .sales-dashboard .sales-daily-table .sales-compare-line i { font-size: .5rem; }
    .sales-dashboard .sales-daily-table .sales-compare-line strong { font-size: .54rem; }
    .sales-dashboard .sales-product-table { min-width: 1265px; table-layout: fixed; }
    .sales-dashboard .sales-product-table th,
    .sales-dashboard .sales-product-table td { white-space: nowrap; }
    .sales-dashboard .sales-product-table .sales-product-name { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sales-dashboard .sales-income-table { min-width: 1080px; }
    .sales-dashboard .sales-income-table th,
    .sales-dashboard .sales-income-table td { white-space: nowrap; }
    .sales-dashboard .sales-income-table thead tr:first-child th {
        background: var(--sales-soft);
        color: var(--sales-ink);
        font-size: .67rem;
        letter-spacing: .02em;
        text-transform: none;
    }
    .sales-dashboard .sales-income-table thead tr:nth-child(2) th {
        font-size: .62rem;
        color: var(--sales-muted);
    }
    .sales-dashboard .sales-income-table .income-group-start { border-left: 1px solid var(--sales-line); }
    .sales-dashboard .sales-income-table .income-value { font-weight: 700; color: var(--sales-ink); }
    .sales-dashboard .sales-income-table .income-percent { color: var(--sales-muted); font-size: .72rem; }
    .sales-dashboard .sales-order-table { width: 100%; min-width: 0; table-layout: fixed; }
    .sales-dashboard .sales-order-table th,
    .sales-dashboard .sales-order-table td { padding: .62rem .55rem; overflow-wrap: anywhere; }
    .sales-dashboard .sales-order-table th:nth-child(1),
    .sales-dashboard .sales-order-table td:nth-child(1) { width: 20%; }
    .sales-dashboard .sales-order-table th:nth-child(2),
    .sales-dashboard .sales-order-table td:nth-child(2) { width: 14%; }
    .sales-dashboard .sales-order-table th:nth-child(3),
    .sales-dashboard .sales-order-table th:nth-child(4),
    .sales-dashboard .sales-order-table td:nth-child(3),
    .sales-dashboard .sales-order-table td:nth-child(4) { width: 10%; }
    .sales-dashboard .sales-order-table th:nth-child(5),
    .sales-dashboard .sales-order-table td:nth-child(5) { width: 11%; }
    .sales-dashboard .sales-order-table th:nth-child(6),
    .sales-dashboard .sales-order-table td:nth-child(6) { width: 10%; }
    .sales-dashboard .sales-order-table th:nth-child(7),
    .sales-dashboard .sales-order-table td:nth-child(7) { width: 17%; }
    .sales-dashboard .sales-order-table th:nth-child(8),
    .sales-dashboard .sales-order-table td:nth-child(8) { width: 8%; }
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
    .sales-dashboard .sales-badge { background: var(--sales-accent-soft); border: 1px solid color-mix(in srgb, var(--sales-accent) 12%, var(--sales-line) 88%); color: var(--sales-accent); font-size: .7rem; font-weight: 700; }

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
        .sales-dashboard .sales-period-filter { order: 1; width: 100%; margin-left: 0; }
        .sales-dashboard .sales-filter-store { order: 2; }
    }
</style>
@endpush

@section('content')
@php
    $fmt = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $dateLabel = fn ($date) => \Carbon\Carbon::parse($date)->format('d M Y');
    $dateRangeLabel = fn ($from, $to) => $dateLabel($from).' – '.$dateLabel($to);
    $pct = fn ($value, $total) => $total > 0 ? number_format(((float) $value / (float) $total) * 100, 1, ',', '.') : '0,0';
    $salesTabs = ['sales', 'products', 'payments', 'promotions', 'shipping', 'income', 'orders'];
    $activeTab = in_array(request('tab'), $salesTabs, true) ? request('tab') : 'sales';
    $comparisonMode = $filters['comparison_mode'] ?? 'period';
    $comparisonModeLabel = $comparisonMode === 'month' ? 'Bln sama' : 'Periode';
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
    $canImportMarketplace = auth()->check() && auth()->user()->canAccessModule('imports');
    $importOrderQuery = $filters['store_id'] ? ['store_id' => $filters['store_id']] : [];
    $paymentDetailQuery = ['tab' => 'payments'];
    if ($filters['store_id']) $paymentDetailQuery['store_id'] = $filters['store_id'];
    if (!empty($filters['dummy'])) $paymentDetailQuery['dummy'] = 1;
    $shippingDetailQuery = ['tab' => 'shipping'];
    if ($filters['store_id']) $shippingDetailQuery['store_id'] = $filters['store_id'];
    if (!empty($filters['dummy'])) $shippingDetailQuery['dummy'] = 1;
    $shippingPct = fn ($value) => $shippingKpi['total'] > 0
        ? number_format(((float) $value / $shippingKpi['total']) * 100, 1, ',', '.').'%' : '0,0%';
    $shippingExceptionPct = number_format($shippingKpi['exception_rate'], 1, ',', '.').'%';
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
    $globalAov = (float) $summary['aov'];
    $topProductCount = $products->count();
    $topProductQty = (int) $products->sum('qty');
    $topProductSales = (float) $products->sum('sales');
    $topProductBuyers = (int) $products->sum('buyers');
    $incomeSettlementRate = $incomeSummary['orders'] > 0 ? ($incomeSummary['settled_orders'] / $incomeSummary['orders']) * 100 : 0;
    $promotionRate = $summary['subtotal'] > 0 ? ($summary['promotion_total'] / $summary['subtotal']) * 100 : 0;
    $adSpendDaily = collect($adSpendDaily ?? []);
    $adSpendTotal = (float) ($adSpendTotal ?? $adSpendDaily->sum());
    $comparisonMonthData = $comparisonMonth['data'] ?? null;
    $comparisonMonthPreviousData = $comparisonMonthPrevious['data'] ?? null;
    $comparisonMonthPreviousTwoData = $comparisonMonthPreviousTwo['data'] ?? null;
    $comparisonPeriodData = $comparisonPeriod['data'] ?? null;
    $comparisonPeriodPreviousData = $comparisonPeriodPrevious['data'] ?? null;
    $comparisonPeriodPreviousTwoData = $comparisonPeriodPreviousTwo['data'] ?? null;
    $comparisonMonthLabel = $comparisonMonth
        ? $dateLabel($comparisonMonth['from']).' – '.$dateLabel($comparisonMonth['to'])
        : 'bulan lalu';
    $comparisonPeriodLabel = $comparisonPeriod
        ? $dateLabel($comparisonPeriod['from']).' – '.$dateLabel($comparisonPeriod['to'])
        : 'periode lalu';
    $activeComparison = $comparisonMode === 'month' ? $comparisonMonth : $comparisonPeriod;
    $activeComparisonLabel = $activeComparison
        ? $dateLabel($activeComparison['from']).' – '.$dateLabel($activeComparison['to'])
        : ($comparisonMode === 'month' ? 'bulan yang sama, tanggal sama' : 'periode lalu');
    $previousMonthSummary = data_get($comparisonMonthData, 'summary', []);
    $previousPeriodSummary = data_get($comparisonPeriodData, 'summary', []);
    $previousMonthPaymentSummary = data_get($comparisonMonthData, 'paymentSummary', []);
    $previousPeriodPaymentSummary = data_get($comparisonPeriodData, 'paymentSummary', []);
    $previousMonthIncomeSummary = data_get($comparisonMonthData, 'incomeSummary', []);
    $previousPeriodIncomeSummary = data_get($comparisonPeriodData, 'incomeSummary', []);
    $previousMonthShippingKpi = data_get($comparisonMonthData, 'shippingKpi', []);
    $previousPeriodShippingKpi = data_get($comparisonPeriodData, 'shippingKpi', []);
    $previousMonthProducts = collect(data_get($comparisonMonthData, 'products', []));
    $previousPeriodProducts = collect(data_get($comparisonPeriodData, 'products', []));
    $previousMonthTopProductCount = $previousMonthProducts->count();
    $previousPeriodTopProductCount = $previousPeriodProducts->count();
    $previousMonthTopProductQty = (int) $previousMonthProducts->sum('qty');
    $previousPeriodTopProductQty = (int) $previousPeriodProducts->sum('qty');
    $previousMonthTopProductSales = (float) $previousMonthProducts->sum('sales');
    $previousPeriodTopProductSales = (float) $previousPeriodProducts->sum('sales');
    $previousMonthTopProductBuyers = (int) $previousMonthProducts->sum('buyers');
    $previousPeriodTopProductBuyers = (int) $previousPeriodProducts->sum('buyers');
    $previousMonthIncomeSettlementRate = ($previousMonthIncomeSummary['orders'] ?? 0) > 0
        ? (($previousMonthIncomeSummary['settled_orders'] ?? 0) / $previousMonthIncomeSummary['orders']) * 100 : 0;
    $previousPeriodIncomeSettlementRate = ($previousPeriodIncomeSummary['orders'] ?? 0) > 0
        ? (($previousPeriodIncomeSummary['settled_orders'] ?? 0) / $previousPeriodIncomeSummary['orders']) * 100 : 0;
    $previousMonthPromotionRate = ($previousMonthSummary['subtotal'] ?? 0) > 0
        ? (($previousMonthSummary['promotion_total'] ?? 0) / $previousMonthSummary['subtotal']) * 100 : 0;
    $previousPeriodPromotionRate = ($previousPeriodSummary['subtotal'] ?? 0) > 0
        ? (($previousPeriodSummary['promotion_total'] ?? 0) / $previousPeriodSummary['subtotal']) * 100 : 0;
    $numberDisplay = fn ($value) => number_format((float) $value, 0, ',', '.');
    $currencyDisplay = fn ($value) => $fmt($value);
    $percentDisplay = fn ($value) => number_format((float) $value, 1, ',', '.').'%';
    $multipleDisplay = fn ($value) => number_format((float) $value, 2, ',', '.').'x';
    $platformPromotionMetrics = function ($rows, $periodSummary, $promotionOrders = null) {
        $rows = collect($rows);
        $voucherSeller = (float) $rows->sum('voucher_store');
        $voucherPlatform = (float) $rows->sum('voucher_platform');
        $bundleDiscount = (float) $rows->sum('bundle_discount');
        $comboHemat = (float) $rows->sum('combo_hemat');
        $voucherSellerSales = (float) $rows->sum('voucher_store_sales');
        $voucherPlatformSales = (float) $rows->sum('voucher_platform_sales');
        $bundleDiscountSales = (float) $rows->sum('bundle_discount_sales');
        $comboHematSales = (float) $rows->sum('combo_hemat_sales');
        $voucherSellerOrders = (int) $rows->sum('voucher_store_orders');
        $voucherPlatformOrders = (int) $rows->sum('voucher_platform_orders');
        $bundleDiscountOrders = (int) $rows->sum('bundle_discount_orders');
        $comboHematOrders = (int) $rows->sum('combo_hemat_orders');
        $total = $voucherPlatform;
        $gmv = (float) data_get($periodSummary, 'subtotal', 0);

        return [
            'sales' => $gmv,
            'voucher_seller' => $voucherSeller,
            'voucher_seller_sales' => $voucherSellerSales,
            'voucher_seller_orders' => $voucherSellerOrders,
            'voucher_seller_average' => $voucherSellerOrders > 0 ? $voucherSeller / $voucherSellerOrders : 0,
            'voucher_platform' => $voucherPlatform,
            'voucher_platform_sales' => $voucherPlatformSales,
            'voucher_platform_orders' => $voucherPlatformOrders,
            'voucher_platform_average' => $voucherPlatformOrders > 0 ? $voucherPlatform / $voucherPlatformOrders : 0,
            'bundle_discount' => $bundleDiscount,
            'bundle_discount_sales' => $bundleDiscountSales,
            'bundle_discount_orders' => $bundleDiscountOrders,
            'bundle_discount_average' => $bundleDiscountOrders > 0 ? $bundleDiscount / $bundleDiscountOrders : 0,
            'combo_hemat' => $comboHemat,
            'combo_hemat_sales' => $comboHematSales,
            'combo_hemat_orders' => $comboHematOrders,
            'combo_hemat_average' => $comboHematOrders > 0 ? $comboHemat / $comboHematOrders : 0,
            'platform_total' => $total,
            'platform_rate' => $gmv > 0 ? ($voucherPlatformSales / $gmv) * 100 : 0,
            'voucher_seller_rate' => $gmv > 0 ? ($voucherSellerSales / $gmv) * 100 : 0,
            'bundle_discount_rate' => $gmv > 0 ? ($bundleDiscountSales / $gmv) * 100 : 0,
            'combo_hemat_rate' => $gmv > 0 ? ($comboHematSales / $gmv) * 100 : 0,
            'orders' => (int) ($promotionOrders ?? $rows->sum('promotion_orders')),
        ];
    };
    $promotionComparisonPeriods = $comparisonMode === 'month'
        ? [
            ['label' => 'Aktif', 'from' => $filters['date_from'], 'to' => $filters['date_to'], 'data' => ['daily' => $promotionDaily, 'summary' => $summary, 'orders' => $promotionOrders, 'ad_spend' => $adSpendTotal, 'ad_impressions' => $adImpressionsTotal, 'ad_clicks' => $adClicksTotal, 'ad_orders' => $adOrdersTotal, 'ad_sales' => $adSalesTotal, 'ad_ctr' => $adCtr, 'ad_cvr' => $adCvr]],
            ['label' => 'Bulan -1', 'from' => $comparisonMonth['from'] ?? null, 'to' => $comparisonMonth['to'] ?? null, 'data' => ['daily' => data_get($comparisonMonthData, 'promotionDaily', []), 'summary' => data_get($comparisonMonthData, 'summary', []), 'orders' => data_get($comparisonMonthData, 'promotionOrders', 0), 'ad_spend' => data_get($comparisonMonthData, 'adSpendTotal', 0), 'ad_impressions' => data_get($comparisonMonthData, 'adImpressionsTotal', 0), 'ad_clicks' => data_get($comparisonMonthData, 'adClicksTotal', 0), 'ad_orders' => data_get($comparisonMonthData, 'adOrdersTotal', 0), 'ad_sales' => data_get($comparisonMonthData, 'adSalesTotal', 0), 'ad_ctr' => data_get($comparisonMonthData, 'adCtr', 0), 'ad_cvr' => data_get($comparisonMonthData, 'adCvr', 0)]],
            ['label' => 'Bulan -2', 'from' => $comparisonMonthPrevious['from'] ?? null, 'to' => $comparisonMonthPrevious['to'] ?? null, 'data' => ['daily' => data_get($comparisonMonthPreviousData, 'promotionDaily', []), 'summary' => data_get($comparisonMonthPreviousData, 'summary', []), 'orders' => data_get($comparisonMonthPreviousData, 'promotionOrders', 0), 'ad_spend' => data_get($comparisonMonthPreviousData, 'adSpendTotal', 0), 'ad_impressions' => data_get($comparisonMonthPreviousData, 'adImpressionsTotal', 0), 'ad_clicks' => data_get($comparisonMonthPreviousData, 'adClicksTotal', 0), 'ad_orders' => data_get($comparisonMonthPreviousData, 'adOrdersTotal', 0), 'ad_sales' => data_get($comparisonMonthPreviousData, 'adSalesTotal', 0), 'ad_ctr' => data_get($comparisonMonthPreviousData, 'adCtr', 0), 'ad_cvr' => data_get($comparisonMonthPreviousData, 'adCvr', 0)]],
            ['label' => 'Bulan -3', 'from' => $comparisonMonthPreviousTwo['from'] ?? null, 'to' => $comparisonMonthPreviousTwo['to'] ?? null, 'data' => ['daily' => data_get($comparisonMonthPreviousTwoData, 'promotionDaily', []), 'summary' => data_get($comparisonMonthPreviousTwoData, 'summary', []), 'orders' => data_get($comparisonMonthPreviousTwoData, 'promotionOrders', 0), 'ad_spend' => data_get($comparisonMonthPreviousTwoData, 'adSpendTotal', 0), 'ad_impressions' => data_get($comparisonMonthPreviousTwoData, 'adImpressionsTotal', 0), 'ad_clicks' => data_get($comparisonMonthPreviousTwoData, 'adClicksTotal', 0), 'ad_orders' => data_get($comparisonMonthPreviousTwoData, 'adOrdersTotal', 0), 'ad_sales' => data_get($comparisonMonthPreviousTwoData, 'adSalesTotal', 0), 'ad_ctr' => data_get($comparisonMonthPreviousTwoData, 'adCtr', 0), 'ad_cvr' => data_get($comparisonMonthPreviousTwoData, 'adCvr', 0)]],
        ]
        : [
        [
            'label' => 'Aktif',
            'from' => $filters['date_from'],
            'to' => $filters['date_to'],
            'data' => ['daily' => $promotionDaily, 'summary' => $summary, 'orders' => $promotionOrders, 'ad_spend' => $adSpendTotal, 'ad_impressions' => $adImpressionsTotal, 'ad_clicks' => $adClicksTotal, 'ad_orders' => $adOrdersTotal, 'ad_sales' => $adSalesTotal, 'ad_ctr' => $adCtr, 'ad_cvr' => $adCvr],
        ],
        [
            'label' => 'Periode -1',
            'from' => $comparisonPeriod['from'] ?? null,
            'to' => $comparisonPeriod['to'] ?? null,
            'data' => ['daily' => data_get($comparisonPeriodData, 'promotionDaily', []), 'summary' => $previousPeriodSummary, 'orders' => data_get($comparisonPeriodData, 'promotionOrders', 0), 'ad_spend' => data_get($comparisonPeriodData, 'adSpendTotal', 0), 'ad_impressions' => data_get($comparisonPeriodData, 'adImpressionsTotal', 0), 'ad_clicks' => data_get($comparisonPeriodData, 'adClicksTotal', 0), 'ad_orders' => data_get($comparisonPeriodData, 'adOrdersTotal', 0), 'ad_sales' => data_get($comparisonPeriodData, 'adSalesTotal', 0), 'ad_ctr' => data_get($comparisonPeriodData, 'adCtr', 0), 'ad_cvr' => data_get($comparisonPeriodData, 'adCvr', 0)],
        ],
        [
            'label' => 'Periode -2',
            'from' => $comparisonPeriodPrevious['from'] ?? null,
            'to' => $comparisonPeriodPrevious['to'] ?? null,
            'data' => ['daily' => data_get($comparisonPeriodPreviousData, 'promotionDaily', []), 'summary' => data_get($comparisonPeriodPreviousData, 'summary', []), 'orders' => data_get($comparisonPeriodPreviousData, 'promotionOrders', 0), 'ad_spend' => data_get($comparisonPeriodPreviousData, 'adSpendTotal', 0), 'ad_impressions' => data_get($comparisonPeriodPreviousData, 'adImpressionsTotal', 0), 'ad_clicks' => data_get($comparisonPeriodPreviousData, 'adClicksTotal', 0), 'ad_orders' => data_get($comparisonPeriodPreviousData, 'adOrdersTotal', 0), 'ad_sales' => data_get($comparisonPeriodPreviousData, 'adSalesTotal', 0), 'ad_ctr' => data_get($comparisonPeriodPreviousData, 'adCtr', 0), 'ad_cvr' => data_get($comparisonPeriodPreviousData, 'adCvr', 0)],
        ],
        [
            'label' => 'Periode -3',
            'from' => $comparisonPeriodPreviousTwo['from'] ?? null,
            'to' => $comparisonPeriodPreviousTwo['to'] ?? null,
            'data' => ['daily' => data_get($comparisonPeriodPreviousTwoData, 'promotionDaily', []), 'summary' => data_get($comparisonPeriodPreviousTwoData, 'summary', []), 'orders' => data_get($comparisonPeriodPreviousTwoData, 'promotionOrders', 0), 'ad_spend' => data_get($comparisonPeriodPreviousTwoData, 'adSpendTotal', 0), 'ad_impressions' => data_get($comparisonPeriodPreviousTwoData, 'adImpressionsTotal', 0), 'ad_clicks' => data_get($comparisonPeriodPreviousTwoData, 'adClicksTotal', 0), 'ad_orders' => data_get($comparisonPeriodPreviousTwoData, 'adOrdersTotal', 0), 'ad_sales' => data_get($comparisonPeriodPreviousTwoData, 'adSalesTotal', 0), 'ad_ctr' => data_get($comparisonPeriodPreviousTwoData, 'adCtr', 0), 'ad_cvr' => data_get($comparisonPeriodPreviousTwoData, 'adCvr', 0)],
        ],
    ];
    $platformPromotionPeriods = collect($promotionComparisonPeriods)->map(function ($period) use ($platformPromotionMetrics) {
        $period['metrics'] = $platformPromotionMetrics($period['data']['daily'], $period['data']['summary'], $period['data']['orders']);
        $period['metrics']['ad_spend'] = (float) ($period['data']['ad_spend'] ?? 0);
        $period['metrics']['ad_impressions'] = (int) ($period['data']['ad_impressions'] ?? 0);
        $period['metrics']['ad_clicks'] = (int) ($period['data']['ad_clicks'] ?? 0);
        $period['metrics']['ad_orders'] = (int) ($period['data']['ad_orders'] ?? 0);
        $period['metrics']['ad_sales'] = (float) ($period['data']['ad_sales'] ?? 0);
        $period['metrics']['ad_ctr'] = (float) ($period['data']['ad_ctr'] ?? 0);
        $period['metrics']['ad_cvr'] = (float) ($period['data']['ad_cvr'] ?? 0);
        $period['metrics']['ad_roas'] = $period['metrics']['ad_spend'] > 0
            ? $period['metrics']['ad_sales'] / $period['metrics']['ad_spend']
            : 0;
        $period['metrics']['ad_sales_rate'] = $period['metrics']['ad_sales'] > 0
            ? ($period['metrics']['ad_spend'] / $period['metrics']['ad_sales']) * 100
            : 0;
        unset($period['data']);

        return $period;
    })->all();
    $currentPromotionMetrics = $platformPromotionPeriods[0]['metrics'];
    $previousMonthPromotionMetrics = $platformPromotionMetrics(
        data_get($comparisonMonthData, 'promotionDaily', []),
        $previousMonthSummary,
        data_get($comparisonMonthData, 'promotionOrders', 0),
    );
    $previousPeriodPromotionMetrics = $platformPromotionMetrics(
        data_get($comparisonPeriodData, 'promotionDaily', []),
        $previousPeriodSummary,
        data_get($comparisonPeriodData, 'promotionOrders', 0),
    );
    $promotionFundingSections = [
        [
            'kicker' => 'Paid media efficiency',
            'title' => 'Biaya Iklan',
            'rows' => [
                ['label' => 'Sales Iklan', 'key' => 'ad_sales', 'format' => $currencyDisplay],
                ['label' => 'Order Iklan', 'key' => 'ad_orders', 'format' => $numberDisplay],
                ['label' => 'ROAS', 'key' => 'ad_roas', 'format' => $multipleDisplay],
                ['label' => 'CTR', 'key' => 'ad_ctr', 'format' => $percentDisplay],
                ['label' => 'CVR', 'key' => 'ad_cvr', 'format' => $percentDisplay],
                ['label' => 'Biaya Iklan', 'key' => 'ad_spend', 'format' => $currencyDisplay],
                ['label' => 'Rasio Iklan / Sales Iklan', 'key' => 'ad_sales_rate', 'format' => $percentDisplay],
            ],
        ],
        [
            'kicker' => 'Platform promotion',
            'title' => 'Promosi Platform',
            'rows' => [
                ['label' => 'Sales Promo', 'key' => 'voucher_platform_sales', 'format' => $currencyDisplay],
                ['label' => 'Order Promo', 'key' => 'voucher_platform_orders', 'format' => $numberDisplay],
                ['label' => 'Voucher Platform', 'key' => 'voucher_platform', 'format' => $currencyDisplay],
                ['label' => 'Rata-rata Promo', 'key' => 'voucher_platform_average', 'format' => $currencyDisplay],
                ['label' => 'Kontribusi Sales', 'key' => 'platform_rate', 'format' => $percentDisplay],
            ],
        ],
        [
            'kicker' => 'Seller voucher',
            'title' => 'Voucher Seller',
            'rows' => [
                ['label' => 'Sales Promo', 'key' => 'voucher_seller_sales', 'format' => $currencyDisplay],
                ['label' => 'Order Promo', 'key' => 'voucher_seller_orders', 'format' => $numberDisplay],
                ['label' => 'Voucher Seller', 'key' => 'voucher_seller', 'format' => $currencyDisplay],
                ['label' => 'Rata-rata Promo', 'key' => 'voucher_seller_average', 'format' => $currencyDisplay],
                ['label' => 'Kontribusi Sales', 'key' => 'voucher_seller_rate', 'format' => $percentDisplay],
            ],
        ],
        [
            'kicker' => 'Seller package promotion',
            'title' => 'Paket Diskon',
            'rows' => [
                ['label' => 'Sales Promo', 'key' => 'bundle_discount_sales', 'format' => $currencyDisplay],
                ['label' => 'Order Promo', 'key' => 'bundle_discount_orders', 'format' => $numberDisplay],
                ['label' => 'Paket Diskon', 'key' => 'bundle_discount', 'format' => $currencyDisplay],
                ['label' => 'Rata-rata Promo', 'key' => 'bundle_discount_average', 'format' => $currencyDisplay],
                ['label' => 'Kontribusi Sales', 'key' => 'bundle_discount_rate', 'format' => $percentDisplay],
            ],
        ],
        [
            'kicker' => 'Seller combo promotion',
            'title' => 'Kombo Hemat',
            'rows' => [
                ['label' => 'Sales Promo', 'key' => 'combo_hemat_sales', 'format' => $currencyDisplay],
                ['label' => 'Order Promo', 'key' => 'combo_hemat_orders', 'format' => $numberDisplay],
                ['label' => 'Kombo Hemat', 'key' => 'combo_hemat', 'format' => $currencyDisplay],
                ['label' => 'Rata-rata Promo', 'key' => 'combo_hemat_average', 'format' => $currencyDisplay],
                ['label' => 'Kontribusi Sales', 'key' => 'combo_hemat_rate', 'format' => $percentDisplay],
            ],
        ],
    ];
    $compareMetric = function ($current, $previous, callable $formatter, string $mode = 'relative', bool $higherIsBetter = true) {
        if ($previous === null) return null;
        $current = (float) $current;
        $previous = (float) $previous;
        $delta = $mode === 'points'
            ? $current - $previous
            : ($previous != 0.0 ? (($current - $previous) / abs($previous)) * 100 : null);
        if ($delta === null) {
            return [
                'previous_label' => $formatter($previous),
                'delta_label' => $current == 0.0 ? '0,0%' : 'baru',
                'tone' => $current > 0.0 ? 'is-good' : 'is-neutral',
            ];
        }
        $isNeutral = abs($delta) < 0.05;
        $tone = $isNeutral ? 'is-neutral' : ((($delta > 0) === $higherIsBetter) ? 'is-good' : 'is-bad');
        $suffix = $mode === 'points' ? ' pp' : '%';

        return [
            'previous_label' => $formatter($previous),
            'delta_label' => ($delta > 0 ? '+' : '').number_format($delta, 1, ',', '.').$suffix,
            'tone' => $tone,
        ];
    };
    $kpiComparisons = function ($current, $monthPrevious, $periodPrevious, callable $formatter, string $mode = 'relative', bool $higherIsBetter = true) use ($compareMetric, $comparisonMode, $comparisonModeLabel) {
        $previous = $comparisonMode === 'month' ? $monthPrevious : $periodPrevious;

        return [
            ['label' => $comparisonModeLabel, 'value' => $compareMetric($current, $previous, $formatter, $mode, $higherIsBetter)],
        ];
    };
@endphp

<div class="container-fluid py-4 sales-dashboard">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <div class="text-muted small mb-1">Toko Online / Dashboard Operasional</div>
            <h1 class="h3 sales-title mb-1">Dashboard Penjualan</h1>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if ($canImportMarketplace)
                <a class="btn btn-primary btn-sm" href="{{ route('imports.marketplace.create', $importOrderQuery) }}">
                    <i class="bi bi-upload me-1"></i>Import Pesanan
                </a>
            @endif
            <span class="badge sales-badge rounded-pill px-3 py-2"><i class="bi bi-database-check me-1"></i>Data marketplace</span>
        </div>
    </div>

    <form id="sales-filter-form" class="card sales-card sales-filter-card shadow-sm mb-4" method="GET" action="{{ route('marketplace.dashboard.sales') }}">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end justify-content-end">
                @if (!empty($filters['dummy']))
                    <input type="hidden" name="dummy" value="1">
                @endif
                <input type="hidden" name="tab" id="sales-active-tab" value="{{ $activeTab }}">
                <x-gf.period-picker
                    id="sales-period-picker"
                    form-id="sales-filter-form"
                    date-from="{{ $filters['date_from'] }}"
                    date-to="{{ $filters['date_to'] }}"
                    input-id-prefix="sales-date"
                    active-preset="{{ $activeDatePreset }}"
                    active-preset-label="{{ $activeDatePresetLabel }}"
                    summary="{{ $activeDateSummary }}"
                    class="col-12 col-md-auto sales-period-filter"
                />
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
                <div class="col-12 col-md-auto sales-filter-comparison">
                    <label class="form-label" for="sales-comparison-mode">Bandingkan</label>
                    <select id="sales-comparison-mode" class="form-select form-select-sm" name="comparison_mode">
                        <option value="period" @selected($comparisonMode === 'period')>Periode lalu</option>
                        <option value="month" @selected($comparisonMode === 'month')>Bln sama · tanggal sama</option>
                    </select>
                </div>
            </div>
        </div>
    </form>

    @if ($comparisonMonth || $comparisonPeriod)
        <div class="sales-compare-period-note mb-3" title="{{ $comparisonModeLabel }}: {{ $activeComparisonLabel }}">
            <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
            Perbandingan: <span>{{ $comparisonModeLabel }}</span>
        </div>
    @endif

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
            ['label' => 'Penjualan', 'value' => $fmt($summary['subtotal']), 'icon' => 'bi-cash-stack', 'variant' => 'sales-kpi--success', 'comparisons' => $kpiComparisons($summary['subtotal'], $previousMonthSummary['subtotal'] ?? null, $previousPeriodSummary['subtotal'] ?? null, $currencyDisplay)],
            ['label' => 'Nilai Neto', 'value' => $fmt($summary['net_total']), 'icon' => 'bi-graph-down-arrow', 'comparisons' => $kpiComparisons($summary['net_total'], $previousMonthSummary['net_total'] ?? null, $previousPeriodSummary['net_total'] ?? null, $currencyDisplay)],
            ['label' => 'Order', 'value' => number_format($summary['orders']), 'icon' => 'bi-receipt', 'comparisons' => $kpiComparisons($summary['orders'], $previousMonthSummary['orders'] ?? null, $previousPeriodSummary['orders'] ?? null, $numberDisplay)],
            ['label' => 'AOV Neto', 'value' => $fmt($globalAov), 'note' => number_format($summary['buyers']).' pembeli', 'icon' => 'bi-bar-chart-line', 'comparisons' => $kpiComparisons($globalAov, $previousMonthSummary['aov'] ?? null, $previousPeriodSummary['aov'] ?? null, $currencyDisplay)],
        ],
    ])
    <section class="card sales-card shadow-sm" aria-labelledby="daily-sales-title">
        <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <div class="sales-kicker mb-1">Ringkasan waktu</div>
                <h2 id="daily-sales-title" class="sales-section-title mb-1">Penjualan per tanggal</h2>
            </div>
            <span class="badge sales-badge rounded-pill px-3 py-2">{{ $daily->count() }} hari aktif</span>
        </div>

        @if ($daily->isEmpty())
            <div class="sales-empty text-center"><i class="bi bi-bar-chart-line d-block fs-3 mb-2"></i>Belum ada penjualan pada periode yang dipilih.</div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle sales-table sales-daily-table">
                    <thead>
                        <tr>
                            <th scope="col" class="sales-index-column">No.</th>
                            <th>Tanggal</th>
                            <th class="text-end">Jumlah Pesanan</th>
                            <th class="text-end">Jumlah Unit Terjual</th>
                            <th class="text-end">Rata-rata Unit/Pesanan</th>
                            <th class="text-end">GMV</th>
                            <th class="text-end">AOV Neto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daily as $row)
                            <tr class="sales-clickable-row" data-sales-order-detail-date="{{ $row->day }}" tabindex="0" role="button">
                                <td class="sales-index-cell" aria-label="Urutan {{ $loop->iteration }}">{{ $loop->iteration }}</td>
                                <td><button class="sales-date-link" type="button" data-sales-order-detail-date="{{ $row->day }}">{{ $dateLabel($row->day) }}</button></td>
                                <td class="text-end">{{ number_format($row->orders) }}</td>
                                <td class="text-end">{{ number_format($row->qty) }}</td>
                                <td class="text-end">{{ number_format($row->avg_units_per_order, 2, ',', '.') }}</td>
                                <td class="text-end">{{ $fmt($row->subtotal) }}</td>
                                <td class="text-end">{{ $fmt($row->aov) }}</td>
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
                ['label' => 'Pembayaran Pembeli', 'value' => $fmt($incomeSummary['buyer_paid']), 'note' => number_format($incomeSummary['orders']).' orders', 'icon' => 'bi-wallet2', 'comparisons' => $kpiComparisons($incomeSummary['buyer_paid'], $previousMonthIncomeSummary['buyer_paid'] ?? null, $previousPeriodIncomeSummary['buyer_paid'] ?? null, $currencyDisplay)],
                ['label' => 'Dana Cair', 'value' => $fmt($incomeSummary['final_income']), 'note' => number_format($incomeSummary['settled_orders']).' order settled', 'icon' => 'bi-cash-coin', 'variant' => 'sales-kpi--success', 'comparisons' => $kpiComparisons($incomeSummary['final_income'], $previousMonthIncomeSummary['final_income'] ?? null, $previousPeriodIncomeSummary['final_income'] ?? null, $currencyDisplay)],
                ['label' => 'Belum Cair', 'value' => $fmt($incomeSummary['pending_buyer_paid']), 'note' => number_format($incomeSummary['pending_orders']).' order pending', 'icon' => 'bi-hourglass-split', 'comparisons' => $kpiComparisons($incomeSummary['pending_buyer_paid'], $previousMonthIncomeSummary['pending_buyer_paid'] ?? null, $previousPeriodIncomeSummary['pending_buyer_paid'] ?? null, $currencyDisplay, 'relative', false)],
                ['label' => 'Settlement Rate', 'value' => number_format($incomeSettlementRate, 1).'%','note' => 'berdasarkan jumlah order', 'icon' => 'bi-check2-circle', 'comparisons' => $kpiComparisons($incomeSettlementRate, $previousMonthIncomeSettlementRate, $previousPeriodIncomeSettlementRate, $percentDisplay, 'points')],
            ],
        ])
        <section class="card sales-card shadow-sm">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <div class="sales-kicker mb-1">Income trend</div>
                    <h2 class="sales-section-title mb-1">Penghasilan per tanggal order</h2>
                </div>
                <span class="badge sales-badge rounded-pill px-3 py-2">{{ $incomeDaily->count() }} hari aktif</span>
            </div>
            @if ($incomeDaily->isEmpty())
                <div class="sales-empty text-center"><i class="bi bi-cash-coin d-block fs-3 mb-2"></i>Belum ada data penghasilan pada periode ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table sales-income-table">
                        <thead>
                            <tr>
                                <th scope="col" class="sales-index-column" rowspan="2">No.</th>
                                <th rowspan="2">Tanggal</th>
                                <th class="text-end" colspan="3">Order</th>
                                <th class="text-end income-group-start" colspan="3">Sudah Cair</th>
                                <th class="text-end income-group-start" colspan="3">Belum Cair</th>
                                <th class="text-end income-group-start pe-3" colspan="2">Dana Cair</th>
                            </tr>
                            <tr>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Nilai</th>
                                <th class="text-end">%</th>
                                <th class="text-end income-group-start">Qty</th>
                                <th class="text-end">Nilai</th>
                                <th class="text-end">%</th>
                                <th class="text-end income-group-start">Qty</th>
                                <th class="text-end">Nilai</th>
                                <th class="text-end">%</th>
                                <th class="text-end income-group-start pe-3">Nilai</th>
                                <th class="text-end pe-3">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($incomeDaily as $income)
                                <tr>
                                    <td class="sales-index-cell" aria-label="Urutan {{ $loop->iteration }}">{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $dateLabel($income->day) }}</td>
                                    <td class="text-end income-value">{{ number_format($income->orders) }}</td>
                                    <td class="text-end income-value">{{ $fmt($income->buyer_paid) }}</td>
                                    <td class="text-end income-percent">{{ $pct($income->orders, $incomeSummary['orders']) }}%</td>
                                    <td class="text-end income-group-start income-value">{{ number_format($income->settled_orders) }}</td>
                                    <td class="text-end income-value">{{ $fmt($income->final_income) }}</td>
                                    <td class="text-end income-percent">{{ $pct($income->settled_orders, $incomeSummary['orders']) }}%</td>
                                    <td class="text-end income-group-start income-value">{{ number_format($income->pending_orders) }}</td>
                                    <td class="text-end income-value">{{ $fmt($income->pending_buyer_paid) }}</td>
                                    <td class="text-end income-percent">{{ $pct($income->pending_orders, $incomeSummary['orders']) }}%</td>
                                    <td class="text-end income-group-start pe-3 income-value">{{ $fmt($income->final_income) }}</td>
                                    <td class="text-end pe-3 income-percent">{{ $pct($income->final_income, $incomeSummary['final_income']) }}%</td>
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
            'kpiTitle' => 'Detail Pesanan',
            'kpis' => [
                ['label' => 'Total Order', 'value' => number_format($summary['orders']), 'icon' => 'bi-receipt', 'comparisons' => $kpiComparisons($summary['orders'], $previousMonthSummary['orders'] ?? null, $previousPeriodSummary['orders'] ?? null, $numberDisplay)],
                ['label' => 'Total Pembayaran', 'value' => $fmt($paymentSummary['buyer_paid']), 'icon' => 'bi-wallet2', 'variant' => 'sales-kpi--success', 'comparisons' => $kpiComparisons($paymentSummary['buyer_paid'], $previousMonthPaymentSummary['buyer_paid'] ?? null, $previousPeriodPaymentSummary['buyer_paid'] ?? null, $currencyDisplay)],
                ['label' => 'AOV Neto', 'value' => $fmt($summary['aov']), 'note' => 'per order', 'icon' => 'bi-bar-chart-line', 'comparisons' => $kpiComparisons($summary['aov'], $previousMonthSummary['aov'] ?? null, $previousPeriodSummary['aov'] ?? null, $currencyDisplay)],
                ['label' => 'Total Promosi', 'value' => $fmt($summary['promotion_total']), 'icon' => 'bi-percent', 'variant' => 'sales-kpi--warning', 'comparisons' => $kpiComparisons($summary['promotion_total'], $previousMonthSummary['promotion_total'] ?? null, $previousPeriodSummary['promotion_total'] ?? null, $currencyDisplay, 'relative', false)],
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
                                <th>Status</th>
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
                                    <td><span class="badge sales-badge">{{ ucwords(str_replace('_', ' ', strtolower($order->status ?: 'Belum ditentukan'))) }}</span></td>
                                    <td class="text-end sales-order-promotion">
                                        <div class="fw-semibold">{{ $fmt($order->promotion_total) }}</div>
                                        <div class="small text-muted">Voucher toko: {{ $fmt($order->voucher_store) }}</div>
                                        <div class="small text-muted">Voucher platform: {{ $fmt($order->voucher_platform) }}</div>
                                        <div class="small text-muted">Paket: {{ $fmt($order->bundle_discount) }}</div>
                                        <div class="small text-muted">Kombo: {{ $fmt($order->combo_hemat ?? 0) }}</div>
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
                ['label' => 'Produk Teratas', 'value' => number_format($topProductCount), 'note' => 'produk pada daftar Top 8', 'icon' => 'bi-box-seam', 'comparisons' => $kpiComparisons($topProductCount, $previousMonthTopProductCount, $previousPeriodTopProductCount, $numberDisplay)],
                ['label' => 'Unit Terjual', 'value' => number_format($topProductQty), 'note' => 'dari produk teratas', 'icon' => 'bi-stack', 'comparisons' => $kpiComparisons($topProductQty, $previousMonthTopProductQty, $previousPeriodTopProductQty, $numberDisplay)],
                ['label' => 'Penjualan Produk', 'value' => $fmt($topProductSales), 'note' => 'kontribusi Top 8', 'icon' => 'bi-cash-stack', 'variant' => 'sales-kpi--success', 'comparisons' => $kpiComparisons($topProductSales, $previousMonthTopProductSales, $previousPeriodTopProductSales, $currencyDisplay)],
                ['label' => 'Pembeli Produk', 'value' => number_format($topProductBuyers), 'note' => 'akumulasi produk teratas', 'icon' => 'bi-people', 'comparisons' => $kpiComparisons($topProductBuyers, $previousMonthTopProductBuyers, $previousPeriodTopProductBuyers, $numberDisplay)],
            ],
        ])
        <section class="card sales-card shadow-sm">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <div class="sales-kicker mb-1">Kinerja produk</div>
                    <h2 class="sales-section-title mb-1">Produk terlaris</h2>
                </div>
                <span class="badge sales-badge rounded-pill px-3 py-2">{{ $products->count() }} produk</span>
            </div>
            @if ($products->isEmpty())
                <div class="sales-empty text-center"><i class="bi bi-box-seam d-block fs-3 mb-2"></i>Belum ada detail produk pada periode ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table sales-product-table">
                        <colgroup>
                            <col style="width: 3.25rem">
                            <col style="width: 13.5rem">
                            <col style="width: 7rem">
                            <col style="width: 7rem">
                            <col style="width: 7rem">
                            <col style="width: 6.5rem">
                            <col style="width: 10rem">
                            <col style="width: 11rem">
                            <col style="width: 9.5rem">
                            <col style="width: 9.5rem">
                        </colgroup>
                        <thead><tr><th scope="col" class="sales-index-column">No.</th><th>Produk</th><th>SKU</th><th class="text-end">Order</th><th class="text-end">Pembeli</th><th class="text-end">Qty</th><th class="text-end">Penjualan</th><th class="text-end">Pembayaran Pembeli</th><th class="text-end">AOV Neto</th><th class="text-end pe-3">APC</th></tr></thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td class="sales-index-cell" aria-label="Urutan {{ $loop->iteration }}">{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">
                                        <button type="button" class="sales-product-link" data-sales-product-name="{{ $product->name }}" data-sales-product-sku="{{ $product->sku }}" title="Lihat pesanan produk: {{ $product->name }}">
                                            <span class="sales-product-name">{{ $product->name }}</span>
                                        </button>
                                    </td>
                                    <td class="text-muted small">{{ $product->sku }}</td>
                                    <td class="text-end">{{ number_format((int) $product->orders) }}</td>
                                    <td class="text-end">{{ number_format((int) $product->buyers) }}</td>
                                    <td class="text-end">{{ number_format((int) $product->qty) }}</td>
                                    <td class="text-end fw-semibold">{{ $fmt($product->sales) }}</td>
                                    <td class="text-end fw-semibold">{{ $fmt($product->buyer_payment) }}</td>
                                    <td class="text-end">{{ $product->orders > 0 ? $fmt($product->buyer_payment / $product->orders) : '—' }}</td>
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
                ['label' => 'Total Dibayar Pembeli', 'value' => $fmt($paymentSummary['buyer_paid']), 'note' => number_format($paymentSummary['orders']).' orders', 'icon' => 'bi-cash-stack', 'variant' => 'sales-kpi--success', 'comparisons' => $kpiComparisons($paymentSummary['buyer_paid'], $previousMonthPaymentSummary['buyer_paid'] ?? null, $previousPeriodPaymentSummary['buyer_paid'] ?? null, $currencyDisplay)],
                ['label' => 'AOV Neto', 'value' => $fmt($paymentSummary['aov']), 'note' => 'per order', 'icon' => 'bi-graph-up-arrow', 'comparisons' => $kpiComparisons($paymentSummary['aov'], $previousMonthPaymentSummary['aov'] ?? null, $previousPeriodPaymentSummary['aov'] ?? null, $currencyDisplay)],
                ['label' => 'Median Ticket', 'value' => $fmt($paymentSummary['median_ticket']), 'note' => 'nilai tengah order', 'icon' => 'bi-bar-chart-line', 'comparisons' => $kpiComparisons($paymentSummary['median_ticket'], $previousMonthPaymentSummary['median_ticket'] ?? null, $previousPeriodPaymentSummary['median_ticket'] ?? null, $currencyDisplay)],
                ['label' => 'COD Exposure', 'value' => number_format($paymentSummary['cod_order_share'], 1).'%', 'note' => number_format($paymentDaily->sum('cod_orders')).' COD orders', 'icon' => 'bi-shield-exclamation', 'variant' => 'sales-kpi--warning', 'comparisons' => $kpiComparisons($paymentSummary['cod_order_share'], $previousMonthPaymentSummary['cod_order_share'] ?? null, $previousPeriodPaymentSummary['cod_order_share'] ?? null, $percentDisplay, 'points', false)],
            ],
        ])
        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-8">
                <section class="card sales-card shadow-sm h-100">
                    <div class="sales-section-header">
                        <div class="sales-kicker mb-1">Payment mix</div>
                        <h2 class="sales-section-title mb-1">Metode pembayaran</h2>
                    </div>
                    @if ($payments->isEmpty())
                        <div class="sales-empty text-center"><i class="bi bi-credit-card d-block fs-3 mb-2"></i>Belum ada metode pembayaran.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle sales-table">
                                <thead><tr><th class="ps-3">Metrik</th><th class="text-end">COD</th><th class="text-end">Non-COD</th><th class="text-end pe-3">Pay Later</th></tr></thead>
                                <tbody>
                                    <tr><td class="ps-3 fw-semibold">Order</td>@foreach ($paymentMix as $payment)<td class="text-end">{{ number_format((int) $payment->orders) }}</td>@endforeach</tr>
                                    <tr><td class="ps-3 fw-semibold">Dibayar Pembeli</td>@foreach ($paymentMix as $payment)<td class="text-end fw-semibold">{{ $fmt($payment->buyer_paid) }}</td>@endforeach</tr>
                                    <tr><td class="ps-3 fw-semibold">Share Nominal</td>@foreach ($paymentMix as $payment)<td class="text-end">{{ number_format($paymentSummary['buyer_paid'] > 0 ? ($payment->buyer_paid / $paymentSummary['buyer_paid']) * 100 : 0, 1) }}%</td>@endforeach</tr>
                                    <tr><td class="ps-3 fw-semibold">AOV Neto</td>@foreach ($paymentMix as $payment)<td class="text-end">{{ $fmt($payment->avg_ticket) }}</td>@endforeach</tr>
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
                        <h2 class="sales-section-title mb-1">Daya beli &amp; exposure</h2>
                    </div>
                    <div class="p-3 pt-0">
                        <div class="border rounded p-3 mb-2">
                            <div class="small text-muted">COD Order Exposure</div>
                            <div class="h4 mb-0">{{ number_format($paymentSummary['cod_order_share'], 1) }}%</div>
                            <div class="small text-muted">{{ number_format($paymentDaily->sum('cod_orders')) }} / {{ number_format($paymentSummary['orders']) }} orders</div>
                        </div>
                        <div class="border rounded p-3 mb-2">
                            <div class="small text-muted">COD Amount Exposure</div>
                            <div class="h5 mb-0">{{ $fmt($paymentSummary['cod_amount']) }}</div>
                            <div class="small text-muted">{{ number_format($paymentSummary['cod_amount_share'], 1) }}% buyer paid</div>
                        </div>
                        <div class="border rounded p-3">
                            <div class="small text-muted">Top Payment Method</div>
                            <div class="fw-semibold">{{ $topPaymentMethod ? ($paymentCategoryLabels[$topPaymentMethod->category] ?? $topPaymentMethod->category) : '-' }}</div>
                            <div class="small text-muted">{{ $topPaymentMethod ? $fmt($topPaymentMethod->buyer_paid) : 'Belum ada data' }}</div>
                        </div>
                        @if ($peakPaymentDay)
                            <div class="small text-muted mt-3">Peak AOV Neto: <strong>{{ $fmt($peakPaymentDay->aov) }}</strong> · {{ $dateLabel($peakPaymentDay->day) }}</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
        <section class="card sales-card shadow-sm mb-3">
            <div class="sales-section-header">
                <div class="sales-kicker mb-1">Purchasing power trend</div>
                <h2 class="sales-section-title mb-1">Daya beli per tanggal</h2>
            </div>
            @if ($paymentDaily->isEmpty())
                <div class="sales-empty text-center"><i class="bi bi-wallet2 d-block fs-3 mb-2"></i>Belum ada data pembayaran pada periode ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table">
                        <thead><tr><th class="ps-3">No.</th><th>Tanggal</th><th class="text-end">Orders</th><th class="text-end">COD</th><th class="text-end">Non-COD</th><th class="text-end">Pay Later</th><th class="text-end">Buyer Paid</th><th class="text-end">COD Exposure</th><th class="text-end pe-3">AOV Neto</th></tr></thead>
                        <tbody>
                            @foreach ($paymentDaily as $payment)
                                <tr class="sales-clickable-row" data-sales-payment-detail-url="{{ route('marketplace.dashboard.payments.detail', array_merge(['date' => $payment->day], $paymentDetailQuery)) }}" tabindex="0" role="button" aria-label="Lihat detail pembayaran {{ $dateLabel($payment->day) }}">
                                    <td class="ps-3 text-muted">{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $dateLabel($payment->day) }}</td>
                                    <td class="text-end">{{ number_format($payment->orders) }}</td>
                                    <td class="text-end"><div class="fw-semibold">{{ $fmt($payment->cod_amount) }}</div><div class="small text-muted">{{ number_format($payment->cod_orders) }} order</div></td>
                                    <td class="text-end"><div class="fw-semibold">{{ $fmt($payment->non_cod_amount) }}</div><div class="small text-muted">{{ number_format($payment->non_cod_orders) }} order</div></td>
                                    <td class="text-end"><div class="fw-semibold">{{ $fmt($payment->pay_later_amount) }}</div><div class="small text-muted">{{ number_format($payment->pay_later_orders) }} order</div></td>
                                    <td class="text-end fw-semibold">{{ $fmt($payment->buyer_paid) }}</td>
                                    <td class="text-end">{{ number_format($payment->cod_order_share, 1) }}%</td>
                                    <td class="text-end pe-3">{{ $fmt($payment->aov) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <div class="sales-tab-pane {{ $activeTab === 'promotions' ? '' : 'is-hidden' }}" data-sales-pane="promotions" role="tabpanel" aria-hidden="{{ $activeTab === 'promotions' ? 'false' : 'true' }}">
        @include('marketplace.dashboard.partials._kpis', [
            'kpiTitle' => 'Promosi',
            'kpis' => [
                ['label' => 'Voucher Platform', 'value' => $fmt($currentPromotionMetrics['voucher_platform']), 'icon' => 'bi-shop', 'comparisons' => $kpiComparisons($currentPromotionMetrics['voucher_platform'], $previousMonthPromotionMetrics['voucher_platform'], $previousPeriodPromotionMetrics['voucher_platform'], $currencyDisplay, 'relative', false)],
                ['label' => 'Voucher Seller', 'value' => $fmt($currentPromotionMetrics['voucher_seller']), 'icon' => 'bi-ticket-perforated', 'comparisons' => $kpiComparisons($currentPromotionMetrics['voucher_seller'], $previousMonthPromotionMetrics['voucher_seller'], $previousPeriodPromotionMetrics['voucher_seller'], $currencyDisplay, 'relative', false)],
                ['label' => 'Paket Diskon', 'value' => $fmt($currentPromotionMetrics['bundle_discount']), 'icon' => 'bi-gift', 'comparisons' => $kpiComparisons($currentPromotionMetrics['bundle_discount'], $previousMonthPromotionMetrics['bundle_discount'], $previousPeriodPromotionMetrics['bundle_discount'], $currencyDisplay, 'relative', false)],
                ['label' => 'Kombo Hemat', 'value' => $fmt($currentPromotionMetrics['combo_hemat']), 'icon' => 'bi-boxes', 'comparisons' => $kpiComparisons($currentPromotionMetrics['combo_hemat'], $previousMonthPromotionMetrics['combo_hemat'], $previousPeriodPromotionMetrics['combo_hemat'], $currencyDisplay, 'relative', false)],
            ],
        ])
        @foreach ($promotionFundingSections as $fundingSection)
            <section class="card sales-card shadow-sm mb-3">
                <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <div class="sales-kicker mb-1">{{ $fundingSection['kicker'] }}</div>
                        <h2 class="sales-section-title mb-1">{{ $fundingSection['title'] }}</h2>
                    </div>
                    <span class="badge sales-badge rounded-pill px-3 py-2">4 periode</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table">
                        <thead>
                            <tr>
                                <th class="ps-3">Metrik</th>
                                @foreach ($platformPromotionPeriods as $period)
                                    <th class="text-end">
                                        {{ $period['label'] }}
                                        <div class="small fw-normal text-muted">{{ $period['from'] && $period['to'] ? $dateRangeLabel($period['from'], $period['to']) : '—' }}</div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($fundingSection['rows'] as $row)
                                <tr>
                                    <td class="ps-3 fw-semibold">{{ $row['label'] }}</td>
                                    @foreach ($platformPromotionPeriods as $period)
                                        <td class="text-end {{ str_contains($row['label'], 'Total') ? 'fw-semibold' : '' }}">
                                            {{ $row['format']($period['metrics'][$row['key']]) }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
        <section class="card sales-card shadow-sm">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <h2 class="sales-section-title mb-1">Promosi per tanggal</h2>
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
                                <th>Tanggal</th>
                                <th class="text-end">GMV</th>
                                <th class="text-end">Biaya Iklan</th>
                                <th class="text-end">Diskon Produk</th>
                                <th class="text-end">Penjualan Neto</th>
                                <th class="text-end">Voucher Toko</th>
                                <th class="text-end">Voucher Platform</th>
                                <th class="text-end">Paket Diskon</th>
                                <th class="text-end">Kombo Hemat</th>
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
                                        <div>{{ $fmt($adSpendDaily->get((string) $row->day, 0)) }}</div>
                                    </td>
                                    <td class="text-end">
                                        <div>{{ $fmt($row->product_discount) }}</div>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        <div>{{ $fmt(max(
                                            (float) ($row->order_before_discount ?? 0)
                                                - (float) ($row->voucher_store ?? 0)
                                                - (float) ($row->bundle_discount ?? 0)
                                                - (float) ($row->combo_hemat ?? 0),
                                            0,
                                        )) }}</div>
                                    </td>
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
                                    <td class="text-end">
                                        <div>{{ $fmt($row->combo_hemat ?? 0) }}</div>
                                        <div class="small text-muted">{{ number_format((int) ($row->combo_hemat_orders ?? 0)) }} order</div>
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
                ['label' => 'Total Order', 'value' => number_format($shippingKpi['total']), 'note' => 'basis pengiriman', 'icon' => 'bi-receipt', 'comparisons' => $kpiComparisons($shippingKpi['total'], $previousMonthShippingKpi['total'] ?? null, $previousPeriodShippingKpi['total'] ?? null, $numberDisplay)],
                ['label' => 'Selesai', 'value' => number_format($shippingKpi['completed']), 'note' => $shippingPct($shippingKpi['completed']).' dari total', 'icon' => 'bi-check2-circle', 'variant' => 'sales-kpi--success', 'comparisons' => $kpiComparisons($shippingKpi['completed'], $previousMonthShippingKpi['completed'] ?? null, $previousPeriodShippingKpi['completed'] ?? null, $numberDisplay)],
                ['label' => 'Dalam Pengiriman', 'value' => number_format($shippingKpi['transit']), 'note' => $shippingPct($shippingKpi['transit']).' dari total', 'icon' => 'bi-truck', 'comparisons' => $kpiComparisons($shippingKpi['transit'], $previousMonthShippingKpi['transit'] ?? null, $previousPeriodShippingKpi['transit'] ?? null, $numberDisplay)],
                ['label' => 'Tingkat Eksepsi', 'value' => $shippingExceptionPct, 'note' => number_format($shippingKpi['exception']).' gagal/return', 'icon' => 'bi-exclamation-diamond', 'variant' => 'sales-kpi--warning', 'comparisons' => $kpiComparisons($shippingKpi['exception_rate'], $previousMonthShippingKpi['exception_rate'] ?? null, $previousPeriodShippingKpi['exception_rate'] ?? null, $percentDisplay, 'points', false)],
            ],
        ])
        <section class="card sales-card shadow-sm">
            <div class="sales-section-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h2 class="sales-section-title mb-0">Pengiriman per tanggal</h2>
                <span class="badge sales-badge rounded-pill px-3 py-2">{{ number_format($shippingKpi['total']) }} order</span>
            </div>
            @if ($shippingDaily->isEmpty())
                <div class="sales-empty text-center"><i class="bi bi-truck d-block fs-3 mb-2"></i>Belum ada data</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table">
                        <thead>
                            <tr>
                                <th scope="col" class="sales-index-column">No.</th>
                                <th>Tanggal</th>
                                <th class="text-end">Total Order</th>
                                <th class="text-end">Siap Dikirim</th>
                                <th class="text-end">Dalam Pengiriman</th>
                                <th class="text-end">Selesai</th>
                                <th class="text-end">Gagal</th>
                                <th class="text-end">Return</th>
                                <th class="text-end pe-3">Eksepsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($shippingDaily as $row)
                                @php
                                    $exceptionCount = (int) $row->failed_orders + (int) $row->return_orders;
                                @endphp
                                <tr class="sales-clickable-row" data-sales-shipping-detail-url="{{ route('marketplace.dashboard.shipping.detail', array_merge(['date' => $row->day], $shippingDetailQuery)) }}" tabindex="0" role="button" aria-label="Lihat detail pengiriman {{ $dateLabel($row->day) }}">
                                    <td class="sales-index-cell" aria-label="Urutan {{ $loop->iteration }}">{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $dateLabel($row->day) }}</td>
                                    <td class="text-end">{{ number_format($row->orders) }}</td>
                                    <td class="text-end">{{ number_format($row->ready_orders) }}</td>
                                    <td class="text-end">{{ number_format($row->transit_orders) }}</td>
                                    <td class="text-end">{{ number_format($row->completed_orders) }}</td>
                                    <td class="text-end">{{ number_format($row->failed_orders) }}</td>
                                    <td class="text-end">{{ number_format($row->return_orders) }}</td>
                                    <td class="text-end pe-3"><div>{{ number_format($exceptionCount) }}</div><div class="small text-muted">{{ $row->orders > 0 ? number_format(($exceptionCount / $row->orders) * 100, 1, ',', '.') : '0,0' }}%</div></td>
                                </tr>
                            @endforeach
                        </tbody>
                        @php
                            $periodTotal = (int) $shippingDaily->sum('orders');
                            $periodReady = (int) $shippingDaily->sum('ready_orders');
                            $periodTransit = (int) $shippingDaily->sum('transit_orders');
                            $periodCompleted = (int) $shippingDaily->sum('completed_orders');
                            $periodFailed = (int) $shippingDaily->sum('failed_orders');
                            $periodReturn = (int) $shippingDaily->sum('return_orders');
                            $periodException = $periodFailed + $periodReturn;
                        @endphp
                        <tfoot>
                            <tr class="fw-semibold">
                                <td class="ps-3"></td>
                                <td>Total</td>
                                <td class="text-end">{{ number_format($periodTotal) }}</td>
                                <td class="text-end">{{ number_format($periodReady) }}</td>
                                <td class="text-end">{{ number_format($periodTransit) }}</td>
                                <td class="text-end">{{ number_format($periodCompleted) }}</td>
                                <td class="text-end">{{ number_format($periodFailed) }}</td>
                                <td class="text-end">{{ number_format($periodReturn) }}</td>
                                <td class="text-end pe-3"><div>{{ number_format($periodException) }}</div><div class="small text-muted">{{ $periodTotal > 0 ? number_format(($periodException / $periodTotal) * 100, 1, ',', '.') : '0,0' }}%</div></td>
                            </tr>
                        </tfoot>
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
        const storeInput = document.querySelector('#sales-store');
        const comparisonModeInput = document.querySelector('#sales-comparison-mode');
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


        if (storeInput && filterForm) {
            storeInput.addEventListener('change', function () {
                filterForm.requestSubmit();
            });
        }

        if (comparisonModeInput && filterForm) {
            comparisonModeInput.addEventListener('change', function () {
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
