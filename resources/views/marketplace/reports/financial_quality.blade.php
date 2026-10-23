@extends('layouts.app')

@section('title', 'Marketplace • Pemeriksaan Data Keuangan')

@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $statusLabels = [
        'ready' => 'Siap masuk laporan',
        'incomplete' => 'Data belum lengkap',
        'not_applicable' => 'Belum relevan untuk keuangan',
        'unknown' => 'Belum diperiksa',
    ];
    $settlementLabels = [
        'complete' => 'Payout lengkap',
        'incomplete' => 'Payout belum lengkap',
        'missing' => 'Payout belum tersedia',
        'unknown' => 'Payout belum diperiksa',
    ];
    $issueLabels = [
        'settlement_missing' => 'Payout belum tersedia',
        'settlement_data_incomplete' => 'Data payout belum lengkap',
        'order_items_missing' => 'Order belum memiliki item',
        'item_mapping_or_hpp_incomplete' => 'Produk belum memiliki HPP',
        'settlement_link_incomplete' => 'Link order dan payout tidak cocok',
        'unknown_reason' => 'Masalah belum teridentifikasi',
    ];
    $fieldLabels = [
        'buyer_payment_amount' => 'Nominal pembayaran',
        'commission_fee' => 'Komisi marketplace',
        'service_fee' => 'Biaya layanan',
        'transaction_fee' => 'Biaya transaksi',
        'seller_voucher' => 'Voucher seller',
        'seller_coin_cash_back' => 'Cashback koin seller',
        'actual_shipping_fee' => 'Ongkir aktual',
        'shipping_fee_subsidy' => 'Subsidi ongkir',
        'reverse_shipping_fee' => 'Ongkir retur',
        'activity_fee' => 'Biaya aktivitas',
        'drc_adjustable_refund' => 'Refund atau penyesuaian',
        'escrow_tax' => 'Pajak payout',
        'final_income' => 'Dana cair',
    ];
    $orderStatusLabels = [
        'COMPLETED' => 'Selesai',
        'SHIPPED' => 'Dikirim',
        'READY_TO_SHIP' => 'Siap dikirim',
        'UNPAID' => 'Belum dibayar',
        'CANCELLED' => 'Dibatalkan',
        'IN_CANCEL' => 'Proses pembatalan',
    ];
    $statusClasses = [
        'ready' => 'fq-status-ready',
        'incomplete' => 'fq-status-incomplete',
        'not_applicable' => 'fq-status-neutral',
        'unknown' => 'fq-status-unknown',
    ];
    $result = session('quality_result');
    $resultQueued = (bool) ($result['queued'] ?? false);
    $qualityIssues = (int) ($qualityIssueCount ?? ((int) ($orderCounts['incomplete'] ?? 0) + (int) ($orderCounts['unknown'] ?? 0)));
    $settlementIssues = (int) ($settlementCounts['incomplete'] ?? 0) + (int) ($settlementCounts['unknown'] ?? 0);
    $hasIssues = $qualityIssues > 0 || $settlementIssues > 0;
    $auditedOrders = collect($orderCounts)->sum(fn ($value) => (int) $value);
    $activeFilterCount = collect([
        filled($search),
        filled($storeId),
        filled($orderStatus),
        filled($settlementStatus),
        filled($dateFrom),
        filled($dateTo),
        $status !== '' && ! $defaultIssueQueue,
    ])->filter()->count();
    $dateRangeValue = '';
    if ($dateFrom && $dateTo) {
        $dateRangeValue = date('d M Y', strtotime($dateFrom)) . ' – ' . date('d M Y', strtotime($dateTo));
    } elseif ($dateFrom) {
        $dateRangeValue = date('d M Y', strtotime($dateFrom)) . ' – …';
    } elseif ($dateTo) {
        $dateRangeValue = '… – ' . date('d M Y', strtotime($dateTo));
    }
@endphp

@push('head')
<style>
    .fq-page {
        --fq-ink: var(--gf-color-content, #101828);
        --fq-muted: var(--gf-color-muted, #667085);
        --fq-subtle: #98a2b3;
        --fq-border: var(--gf-color-line, #e4e7ec);
        --fq-surface: var(--gf-color-surface, #ffffff);
        --fq-surface-soft: var(--gf-color-surface-muted, #f2f4f7);
        --fq-brand: var(--gf-color-brand, #5b5ce2);
        --fq-brand-dark: var(--gf-color-brand-strong, #4142bb);
        --fq-success: var(--gf-color-success, #14945a);
        --fq-warning: var(--gf-color-warning, #b7791f);
        --fq-danger: var(--gf-color-danger, #d6455d);
        --fq-radius-card: var(--gf-radius-card, 16px);
        --fq-radius-control: var(--gf-radius-control, 10px);
        --fq-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16, 24, 40, .04), 0 6px 18px rgba(16, 24, 40, .04));
        max-width: var(--gf-content-max, 1280px);
        margin: 0 auto;
        padding: 1rem var(--gf-content-gutter, 1rem) 4rem;
        color: var(--fq-ink);
    }
    .fq-page *, .fq-page *::before, .fq-page *::after { box-sizing: border-box; }
    .fq-breadcrumb { display: flex; align-items: center; gap: .45rem; margin-bottom: .8rem; color: var(--fq-subtle); font-size: .7rem; font-weight: 650; letter-spacing: .01em; }
    .fq-breadcrumb a { color: var(--fq-muted); text-decoration: none; }
    .fq-breadcrumb a:hover { color: var(--fq-brand); }
    .fq-breadcrumb i { font-size: .63rem; }
    .fq-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: .85rem; padding: 1.35rem 1.45rem; overflow: hidden; border: 1px solid rgba(91, 92, 226, .18); border-radius: 22px; background: linear-gradient(135deg, #f7f7ff 0%, #ffffff 58%, #effcfd 100%); box-shadow: var(--fq-shadow); }
    .fq-header-copy { min-width: 0; }
    .fq-header-meta { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .7rem; }
    .fq-header-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .28rem .5rem; border: 1px solid var(--fq-border); border-radius: 999px; color: var(--fq-muted); background: rgba(255,255,255,.78); font-size: .66rem; font-weight: 700; }
    .fq-eyebrow { margin-bottom: .3rem; color: var(--fq-brand); font-size: .68rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
    .fq-title { margin: 0; color: var(--fq-ink); font-size: clamp(1.25rem, 2vw, 1.65rem); font-weight: 760; letter-spacing: -.025em; }
    .fq-description { max-width: 680px; margin: .35rem 0 0; color: var(--fq-muted); font-size: .82rem; line-height: 1.55; }
    .fq-btn { border-radius: var(--fq-radius-control); padding: .48rem .7rem; font-size: .75rem; font-weight: 700; box-shadow: none !important; }
    .fq-btn:focus-visible, .fq-action:focus-visible, .fq-action-fix:focus-visible { outline: 3px solid rgba(91, 92, 226, .28); outline-offset: 2px; }
    .fq-btn-primary { color: #fff !important; background: var(--fq-brand) !important; border-color: var(--fq-brand) !important; }
    .fq-btn-primary:hover { background: var(--fq-brand-dark) !important; border-color: var(--fq-brand-dark) !important; }
    .fq-card { background: var(--fq-surface); border: 1px solid var(--fq-border); border-radius: var(--fq-radius-card); box-shadow: var(--fq-shadow); }
    .fq-workflow { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .75rem 1rem; align-items: center; margin-bottom: .85rem; padding: .9rem 1rem; border: 1px solid #dbe4ff; border-radius: var(--fq-radius-card); background: linear-gradient(90deg, #f4f6ff, #f7ffff); }
    .fq-workflow-label { color: var(--fq-brand-dark); font-size: .7rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .fq-workflow-steps { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; }
    .fq-workflow-step { display: inline-flex; align-items: center; gap: .3rem; color: var(--fq-ink); font-size: .7rem; font-weight: 750; }
    .fq-workflow-step b { display: grid; width: 21px; height: 21px; place-items: center; border-radius: 50%; color: #fff; background: var(--fq-brand); font-size: .62rem; }
    .fq-workflow-arrow { color: #98a2b3; font-size: .7rem; }
    .fq-health { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: .85rem; padding: .85rem .95rem; border: 1px solid; border-radius: var(--fq-radius-card); }
    .fq-health-attention { color: #7a3e00; background: #fffbeb; border-color: #f5d98a; }
    .fq-health-good { color: #176044; background: #f0fdf7; border-color: #b7ead4; }
    .fq-health-main { display: flex; align-items: flex-start; gap: .65rem; }
    .fq-health-icon { display: grid; width: 28px; height: 28px; flex: 0 0 28px; place-items: center; border-radius: 8px; background: rgba(255,255,255,.72); font-size: .9rem; }
    .fq-health-title { margin: 0 0 .12rem; font-size: .78rem; font-weight: 800; }
    .fq-health-copy { margin: 0; color: inherit; opacity: .84; font-size: .72rem; line-height: 1.45; }
    .fq-health-copy strong { font-weight: 800; }
    .fq-health-time { color: inherit; opacity: .75; font-size: .68rem; white-space: nowrap; }
    .fq-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .65rem; margin-bottom: .85rem; }
    .fq-kpi { position: relative; min-height: 98px; padding: .82rem .9rem; overflow: hidden; }
    .fq-kpi::after { position: absolute; right: -16px; bottom: -26px; width: 72px; height: 72px; border: 10px solid rgba(36,87,166,.06); border-radius: 50%; content: ''; }
    .fq-kpi-label { display: flex; align-items: center; gap: .35rem; color: var(--fq-muted); font-size: .7rem; font-weight: 700; }
    .fq-kpi-icon { display: grid; width: 22px; height: 22px; place-items: center; border-radius: 6px; color: var(--fq-brand); background: #eef4ff; font-size: .72rem; }
    .fq-kpi-value { display: block; margin-top: .5rem; color: var(--fq-ink); font-size: 1.35rem; font-weight: 780; letter-spacing: -.035em; line-height: 1; }
    .fq-kpi-note { display: block; margin-top: .35rem; color: var(--fq-subtle); font-size: .65rem; }
    .fq-kpi-warning .fq-kpi-icon { color: #a15c00; background: #fff4d6; }
    .fq-kpi-danger .fq-kpi-icon { color: #b42318; background: #feeceb; }
    .fq-filter-card { margin-bottom: .85rem; }
    .fq-filter-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .78rem .9rem; border-bottom: 1px solid var(--fq-border); }
    .fq-filter-title-wrap { display: flex; align-items: center; gap: .55rem; }
    .fq-filter-icon { display: grid; width: 26px; height: 26px; place-items: center; border-radius: 7px; color: var(--fq-brand); background: #eef4ff; font-size: .78rem; }
    .fq-filter-title { margin: 0; font-size: .8rem; font-weight: 760; }
    .fq-filter-tools { display: flex; align-items: center; gap: .55rem; }
    .fq-filter-count { padding: .24rem .45rem; border: 1px solid #d8e4fa; border-radius: 999px; color: var(--fq-brand); background: #f5f8ff; font-size: .66rem; font-weight: 750; white-space: nowrap; }
    .fq-filter-reset { color: var(--fq-muted); font-size: .68rem; font-weight: 750; text-decoration: none; white-space: nowrap; }
    .fq-filter-reset:hover { color: var(--fq-brand-dark); }
    .fq-filter-body { padding: .85rem .9rem .9rem; }
    .fq-filter-form { display: grid; grid-template-columns: repeat(8, minmax(0, 1fr)); gap: .65rem; align-items: end; }
    .fq-field { min-width: 0; }
    .fq-field-label { display: block; margin-bottom: .3rem; color: var(--fq-muted); font-size: .66rem; font-weight: 750; }
    .fq-field .form-control, .fq-field .form-select { height: 35px; min-width: 0; border-color: #d9dee8; border-radius: 7px; color: var(--fq-ink); font-size: .73rem; box-shadow: none; }
    .fq-field .form-control:focus, .fq-field .form-select:focus { border-color: #91afe0; box-shadow: 0 0 0 3px rgba(36,87,166,.1); }
    .fq-field-search { grid-column: span 2; }
    .fq-field-range { grid-column: span 2; }
    .fq-range-control { position: relative; }
    .fq-range-control .fq-range-icon { position: absolute; top: 50%; right: .7rem; z-index: 1; color: var(--fq-brand); pointer-events: none; transform: translateY(-50%); }
    .fq-range-control .form-control { padding-right: 2.15rem; cursor: pointer; }
    .fq-audit-card { margin-bottom: .85rem; overflow: hidden; }
    .fq-refresh { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .8rem; padding: .85rem .9rem; background: var(--fq-surface-soft); }
    .fq-refresh-copy { display: flex; align-items: flex-start; gap: .55rem; }
    .fq-refresh-copy i { margin-top: .1rem; color: var(--fq-brand); }
    .fq-refresh-title { margin: 0 0 .1rem; color: var(--fq-ink); font-size: .73rem; font-weight: 760; }
    .fq-refresh-note { margin: 0; color: var(--fq-muted); font-size: .67rem; }
    .fq-refresh-actions { display: flex; gap: .4rem; }
    .fq-main-grid { display: flex; flex-direction: column; gap: .85rem; align-items: stretch; }
    .fq-queue-card { order: 2; width: 100%; min-width: 0; }
    .fq-queue-head { display: flex; align-items: center; justify-content: space-between; gap: .7rem; padding: .8rem .9rem; border-bottom: 1px solid var(--fq-border); }
    .fq-queue-heading { display: flex; align-items: center; gap: .55rem; }
    .fq-queue-title { margin: 0; font-size: .84rem; font-weight: 780; }
    .fq-queue-subtitle { margin: .15rem 0 0; color: var(--fq-muted); font-size: .68rem; }
    .fq-queue-total { color: var(--fq-muted); font-size: .7rem; white-space: nowrap; }
    .fq-table-wrap { max-height: 660px; overflow-x: hidden; overflow-y: auto; }
    .fq-table { width: 100%; min-width: 0; margin: 0; table-layout: fixed; font-size: .73rem; font-variant-numeric: tabular-nums; }
    .fq-table th:nth-child(1), .fq-table td:nth-child(1) { width: 27%; }
    .fq-table th:nth-child(2), .fq-table td:nth-child(2) { width: 23%; }
    .fq-table th:nth-child(3), .fq-table td:nth-child(3) { width: 12%; }
    .fq-table th:nth-child(4), .fq-table td:nth-child(4) { width: 26%; }
    .fq-table th:nth-child(5), .fq-table td:nth-child(5) { width: 12%; }
    .fq-table thead th { position: sticky; top: 0; z-index: 2; padding: .58rem .65rem; border-bottom: 1px solid var(--fq-border); color: var(--fq-muted); background: #fbfcfe; font-size: .64rem; font-weight: 800; letter-spacing: .025em; text-transform: uppercase; white-space: nowrap; }
    .fq-table tbody td { padding: .68rem .65rem; border-top: 1px solid #f0f2f5; vertical-align: middle; }
    .fq-table tbody tr:hover { background: #fbfdff; }
    .fq-order { overflow-wrap: anywhere; color: var(--fq-ink); font-weight: 780; }
    .fq-order-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem .45rem; margin-top: .2rem; }
    .fq-store { color: var(--fq-ink); font-weight: 650; }
    .fq-muted { color: var(--fq-muted); font-size: .66rem; }
    .fq-status { display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .42rem; border: 1px solid transparent; border-radius: 999px; font-size: .64rem; font-weight: 760; line-height: 1; white-space: nowrap; }
    .fq-status::before { width: 5px; height: 5px; border-radius: 50%; background: currentColor; content: ''; }
    .fq-status-ready { color: #16704c; background: #ecfdf3; border-color: #b9e8cf; }
    .fq-status-incomplete { color: #986000; background: #fff8e5; border-color: #f4d890; }
    .fq-status-neutral { color: #667085; background: #f5f6f8; border-color: #e1e5eb; }
    .fq-status-unknown { color: #475467; background: #f2f4f7; border-color: #d0d5dd; }
    .fq-status-missing { color: #b42318; background: #fef3f2; border-color: #f5c4c0; }
    .fq-status-stack { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem; }
    .fq-valid { color: #16704c; font-weight: 750; }
    .fq-invalid { color: #a15c00; font-weight: 750; }
    .fq-issue-main { display: block; color: var(--fq-ink); font-weight: 650; }
    .fq-issue-missing { display: -webkit-box; margin-top: .24rem; overflow: hidden; color: var(--fq-muted); -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
    .fq-action { color: var(--fq-brand); font-weight: 750; text-decoration: none; white-space: nowrap; }
    .fq-action:hover { color: var(--fq-brand-dark); }
    .fq-row-actions { display: flex; flex-direction: column; align-items: flex-start; gap: .28rem; }
    .fq-action-fix { padding: .25rem .48rem; border: 1px solid #c9d9f4; border-radius: 6px; color: var(--fq-brand); background: #f5f8ff; font: inherit; font-size: .68rem; cursor: pointer; }
    .fq-action-fix:hover { border-color: #91afe0; background: #eef4ff; }
    .fq-action-muted { color: var(--fq-muted); font-size: .65rem; }
    .fq-actions-cell { text-align: left !important; }
    .fq-empty { padding: 3rem 1rem !important; text-align: center; }
    .fq-empty-icon { display: grid; width: 40px; height: 40px; margin: 0 auto .7rem; place-items: center; border-radius: 12px; color: #16704c; background: #ecfdf3; font-size: 1rem; }
    .fq-empty-title { margin: 0 0 .25rem; color: var(--fq-ink); font-size: .8rem; font-weight: 780; }
    .fq-empty-copy { max-width: 420px; margin: 0 auto; color: var(--fq-muted); font-size: .7rem; line-height: 1.5; }
    .fq-pagination { padding: .7rem .85rem; border-top: 1px solid var(--fq-border); }
    .fq-sidebar { display: grid; grid-template-columns: repeat(3, minmax(0, 340px)); justify-content: start; order: 1; width: 100%; gap: .85rem; }
    .fq-card-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .78rem .9rem; border-bottom: 1px solid var(--fq-border); }
    .fq-card-title { margin: 0; color: var(--fq-ink); font-size: .8rem; font-weight: 760; }
    .fq-card-meta { color: var(--fq-muted); font-size: .7rem; }
    .fq-summary-list { display: grid; gap: .65rem; padding: .85rem .9rem; }
    .fq-summary-row { display: flex; align-items: center; justify-content: space-between; gap: .65rem; }
    .fq-summary-label { display: flex; align-items: center; gap: .4rem; color: var(--fq-muted); font-size: .7rem; }
    .fq-summary-dot { width: 7px; height: 7px; border-radius: 50%; background: #98a2b3; }
    .fq-summary-dot.ready { background: #2eaf78; }
    .fq-summary-dot.warning { background: #d98b00; }
    .fq-summary-dot.danger { background: #d92d20; }
    .fq-summary-value { color: var(--fq-ink); font-size: .76rem; font-weight: 780; }
    .fq-progress { height: 6px; margin: .1rem .9rem .85rem; overflow: hidden; border-radius: 99px; background: #edf0f4; }
    .fq-progress-bar { height: 100%; border-radius: inherit; background: #2eaf78; }
    .fq-progress-note { padding: 0 .9rem .85rem; color: var(--fq-muted); font-size: .65rem; }
    .fq-issues { padding: .65rem .9rem .85rem; }
    .fq-issue { display: flex; align-items: center; justify-content: space-between; gap: .65rem; padding: .55rem 0; border-bottom: 1px solid #f0f2f5; }
    .fq-issue:last-child { border-bottom: 0; }
    .fq-issue-name { color: var(--fq-muted); font-size: .69rem; }
    .fq-issue-count { min-width: 25px; padding: .2rem .35rem; border-radius: 999px; color: #986000; background: #fff8e5; font-size: .64rem; font-weight: 780; text-align: center; }
    .fq-boundary { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem .75rem; margin-bottom: .85rem; padding: .55rem .75rem; border: 1px solid var(--fq-border); border-radius: 12px; color: var(--fq-muted); background: var(--fq-surface-soft); font-size: .66rem; }
    .fq-boundary strong { color: var(--fq-ink); font-weight: 800; }
    .fq-boundary-segment { display: inline-flex; align-items: center; gap: .3rem; }
    .fq-boundary-segment + .fq-boundary-segment::before { color: #98a2b3; content: '·'; }
    @media (max-width: 1180px) {
        .fq-filter-form { grid-template-columns: repeat(6, minmax(0, 1fr)); }
        .fq-field-search { grid-column: span 2; }
        .fq-field-range { grid-column: span 2; }
    }
    @media (max-width: 900px) {
        .fq-header { display: block; }
        .fq-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .fq-main-grid { display: flex; }
        .fq-sidebar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .fq-filter-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .fq-field-search, .fq-field-range { grid-column: 1 / -1; }
    }
    @media (max-width: 640px) {
        .fq-page { padding: .8rem .55rem 3rem; }
        .fq-header { padding: 1rem; border-radius: 18px; }
        .fq-workflow { grid-template-columns: 1fr; gap: .55rem; }
        .fq-health { align-items: flex-start; flex-direction: column; }
        .fq-health-time { margin-left: 2.35rem; }
        .fq-kpis { gap: .45rem; }
        .fq-kpi { min-height: 88px; padding: .68rem; }
        .fq-kpi-value { font-size: 1.15rem; }
        .fq-filter-form { grid-template-columns: 1fr 1fr; gap: .55rem; }
        .fq-field-search, .fq-field-range, .fq-field-wide { grid-column: 1 / -1; }
        .fq-refresh { align-items: flex-start; flex-direction: column; }
        .fq-refresh-actions, .fq-refresh-actions form, .fq-refresh-actions .fq-btn { width: 100%; }
        .fq-sidebar { grid-template-columns: 1fr; }
        .fq-table { min-width: 0; }
        .fq-table thead { display: none; }
        .fq-table, .fq-table tbody, .fq-table tr, .fq-table td { display: block; width: 100%; }
        .fq-table tbody tr { padding: .8rem .85rem; border-top: 1px solid var(--fq-border); }
        .fq-table tbody tr:first-child { border-top: 0; }
        .fq-table tbody td { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; padding: .3rem 0; border: 0; text-align: right !important; }
        .fq-table tbody td::before { flex: 0 0 38%; color: var(--fq-muted); content: attr(data-label); font-size: .64rem; font-weight: 750; text-align: left; }
        .fq-table tbody td:first-child { display: block; padding-top: 0; text-align: left !important; }
        .fq-table tbody td:first-child::before { display: none; }
        .fq-table tbody td:last-child { display: block; padding-top: .55rem; text-align: left !important; }
        .fq-table tbody td:last-child::before { display: none; }
        .fq-row-actions { align-items: stretch; }
        .fq-row-actions .fq-action, .fq-row-actions .fq-action-fix { display: block; width: 100%; text-align: center; }
        .fq-empty { padding: 2rem 1rem !important; }
    }
    .fq-spin { display: inline-block; animation: fq-spin-anim 1.2s linear infinite; }
    @keyframes fq-spin-anim { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .fq-health-danger { color: #991b1b !important; background: #fef2f2 !important; border-color: #fca5a5 !important; }
    @media (prefers-reduced-motion: reduce) {
        .fq-spin { animation: none; }
        *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rangeInput = document.getElementById('fq-date-range');
    const fromInput = document.getElementById('fq-date-from');
    const toInput = document.getElementById('fq-date-to');
    const filterForm = document.getElementById('fq-filter-form');
    const searchInput = document.getElementById('fq-search');

    const submitFilters = function () {
        if (!filterForm) return;
        if (typeof filterForm.requestSubmit === 'function') {
            filterForm.requestSubmit();
            return;
        }
        filterForm.submit();
    };

    if (filterForm) {
        filterForm.querySelectorAll('select').forEach(function (select) {
            select.addEventListener('change', submitFilters);
        });

        let searchTimer;
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                window.clearTimeout(searchTimer);
                if (searchInput.value.length > 0 && searchInput.value.length < 2) return;
                searchTimer = window.setTimeout(submitFilters, 450);
            });
            searchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    window.clearTimeout(searchTimer);
                    submitFilters();
                }
            });
        }
    }

    if (rangeInput && fromInput && toInput && typeof window.flatpickr === 'function') {
        const formatRange = function (dates) {
            if (dates.length === 2) {
                return window.flatpickr.formatDate(dates[0], 'd M Y') + ' – ' + window.flatpickr.formatDate(dates[1], 'd M Y');
            }
            return dates.length === 1 ? window.flatpickr.formatDate(dates[0], 'd M Y') + ' – …' : '';
        };

        window.flatpickr(rangeInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            defaultDate: [fromInput.value, toInput.value].filter(Boolean),
            allowInput: false,
            disableMobile: true,
            locale: window.flatpickr.l10ns && window.flatpickr.l10ns.id ? window.flatpickr.l10ns.id : 'default',
            onReady: function (selectedDates, _, instance) {
                if (selectedDates.length) instance.input.value = formatRange(selectedDates);
            },
            onChange: function (selectedDates, _, instance) {
                if (!selectedDates.length) {
                    fromInput.value = '';
                    toInput.value = '';
                    instance.input.value = '';
                    submitFilters();
                    return;
                }

                fromInput.value = window.flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                toInput.value = selectedDates.length === 2
                    ? window.flatpickr.formatDate(selectedDates[1], 'Y-m-d')
                    : '';
                instance.input.value = formatRange(selectedDates);
                if (selectedDates.length === 2) submitFilters();
            },
        });
    }

    // Realtime Polling for Audit Queue & Status
    (function () {
        const banner = document.getElementById('fq-audit-banner');
        if (!banner) return;

        const pollUrl = banner.dataset.pollUrl;
        if (!pollUrl) return;

        const titleEl = document.getElementById('fq-audit-title');
        const copyEl = document.getElementById('fq-audit-copy');
        const staleEl = document.getElementById('fq-stale-warning');

        let pollInterval = setInterval(checkAuditStatus, 2500);

        function checkAuditStatus() {
            fetch(pollUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.status === 'processing') {
                    if (titleEl) titleEl.textContent = 'Sedang Memeriksa Data Keuangan';
                    if (copyEl && data.message) copyEl.textContent = data.message;
                    if (staleEl) staleEl.style.display = 'none';
                } else if (data.status === 'queued') {
                    if (data.is_stale && staleEl) {
                        staleEl.style.display = 'block';
                    }
                } else if (data.status === 'completed') {
                    clearInterval(pollInterval);
                    if (window.Swal) {
                        window.Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: data.message || 'Audit kualitas data selesai!',
                            showConfirmButton: false,
                            timer: 2600,
                            timerProgressBar: true
                        });
                    }
                    setTimeout(function () {
                        window.location.reload();
                    }, 1200);
                } else if (data.status === 'failed') {
                    clearInterval(pollInterval);
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'error',
                            title: 'Audit Gagal',
                            text: data.message || data.error || 'Terjadi kesalahan saat audit data kualitas.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#334155'
                        });
                    }
                    if (banner) {
                        banner.className = 'fq-health fq-health-danger';
                        if (titleEl) titleEl.textContent = 'Audit Kualitas Data Gagal';
                        if (copyEl) copyEl.textContent = data.message || data.error;
                    }
                }
            })
            .catch(function (err) {
                console.error('Audit poll error:', err);
            });
        }
    })();
});
</script>
@endpush

@section('content')
<div class="fq-page">
    <nav class="fq-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('marketplace.orders') }}">Marketplace</a>
        <i class="bi bi-chevron-right"></i>
        <span>Pemeriksaan data keuangan</span>
    </nav>

    <header class="fq-header">
        <div class="fq-header-copy">
            <div class="fq-eyebrow">Audit marketplace</div>
            <h1 class="fq-title">Pemeriksaan Data Keuangan Marketplace</h1>
            <p class="fq-description">Validasi payout, mapping SKU, dan HPP sebelum masuk laporan.</p>
            <div class="fq-header-meta" aria-label="Komponen yang diperiksa">
                <span class="fq-header-chip"><i class="bi bi-receipt"></i> Order selesai</span>
                <span class="fq-header-chip"><i class="bi bi-wallet2"></i> Payout</span>
                <span class="fq-header-chip"><i class="bi bi-box-seam"></i> Mapping SKU</span>
                <span class="fq-header-chip"><i class="bi bi-calculator"></i> HPP</span>
            </div>
        </div>
    </header>

    <section class="fq-workflow" aria-labelledby="workflow-title">
        <div id="workflow-title" class="fq-workflow-label">Alur</div>
        <div class="fq-workflow-steps" aria-label="Urutan proses quality check">
            <span class="fq-workflow-step"><b>1</b> Sinkronkan</span><i class="bi bi-chevron-right fq-workflow-arrow" aria-hidden="true"></i>
            <span class="fq-workflow-step"><b>2</b> Periksa</span><i class="bi bi-chevron-right fq-workflow-arrow" aria-hidden="true"></i>
            <span class="fq-workflow-step"><b>3</b> Perbaiki</span><i class="bi bi-chevron-right fq-workflow-arrow" aria-hidden="true"></i>
            <span class="fq-workflow-step"><b>4</b> Rekonsiliasi</span><i class="bi bi-chevron-right fq-workflow-arrow" aria-hidden="true"></i>
            <span class="fq-workflow-step"><b>5</b> Posting &amp; tutup</span>
        </div>
    </section>

    <div class="fq-boundary" role="note" aria-label="Batas pencatatan akuntansi">
        <span class="fq-boundary-segment"><strong>Shipments</strong> stock-out &amp; HPP</span>
        <span class="fq-boundary-segment"><strong>Finance Marketplace</strong> revenue, fee &amp; payout</span>
        <span class="fq-boundary-segment"><strong>Accounting</strong> posting setelah rekonsiliasi</span>
    </div>

    @if ($activeAudit && in_array($activeAudit['status'] ?? null, ['queued', 'processing'], true))
        <div id="fq-audit-banner" class="fq-health fq-health-attention" role="status" data-poll-url="{{ route('marketplace.reports.financial-quality.status', ['store_id' => $storeId ?: 'all']) }}">
            <div class="fq-health-main">
                <div class="fq-health-icon"><i class="bi bi-arrow-repeat fq-spin"></i></div>
                <div>
                    <p class="fq-health-title" id="fq-audit-title">{{ ($activeAudit['status'] ?? '') === 'processing' ? 'Sedang Memeriksa Data Keuangan' : 'Audit Masuk Antrean' }}</p>
                    <p class="fq-health-copy" id="fq-audit-copy">{{ $activeAudit['message'] ?? 'Audit kualitas sedang diproses di antrean worker marketplace-quality...' }}</p>
                    <div id="fq-stale-warning" class="mt-2" style="display: none;">
                        <span class="badge bg-warning text-dark me-2"><i class="bi bi-clock-history me-1"></i>Worker belum memproses</span>
                        <form method="POST" action="{{ route('marketplace.reports.financial-quality.process-now') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="store_id" value="{{ $storeId ?: 'all' }}">
                            <button type="submit" class="btn btn-sm btn-primary py-0 px-2" style="font-size: 0.72rem;">
                                <i class="bi bi-play-fill me-1"></i>Proses Sekarang (Langsung)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <span class="fq-health-time" id="fq-audit-time">{{ now()->format('d M Y H:i') }}</span>
        </div>
    @elseif ($activeAudit && ($activeAudit['status'] ?? null) === 'failed')
        <div class="fq-health fq-health-danger" role="alert">
            <div class="fq-health-main">
                <div class="fq-health-icon text-danger bg-white"><i class="bi bi-x-circle"></i></div>
                <div>
                    <p class="fq-health-title">Audit Kualitas Data Gagal</p>
                    <p class="fq-health-copy">{{ $activeAudit['message'] ?? $activeAudit['error'] }}</p>
                </div>
            </div>
            <span class="fq-health-time">{{ isset($activeAudit['failed_at']) ? date('d M Y H:i', strtotime($activeAudit['failed_at'])) : now()->format('d M Y H:i') }}</span>
        </div>
    @elseif ($result)
        <div class="fq-health {{ $resultQueued || ($result['dry_run'] ?? false) || !empty($result['error']) ? (!empty($result['error']) ? 'fq-health-danger' : 'fq-health-attention') : 'fq-health-good' }}" role="status" @if($resultQueued) id="fq-audit-banner" data-poll-url="{{ route('marketplace.reports.financial-quality.status', ['store_id' => $storeId ?: 'all']) }}" @endif>
            <div class="fq-health-main">
                <div class="fq-health-icon"><i class="bi {{ !empty($result['error']) ? 'bi-x-circle text-danger' : ($resultQueued ? 'bi-hourglass-split fq-spin' : (($result['dry_run'] ?? false) ? 'bi-search' : 'bi-check-lg')) }}"></i></div>
                <div>
                    <p class="fq-health-title" id="fq-audit-title">{{ !empty($result['error']) ? 'Terjadi Kesalahan' : ($resultQueued ? 'Audit masuk antrean' : (($result['dry_run'] ?? false) ? 'Audit dry-run selesai' : 'Audit status berhasil disimpan')) }}</p>
                    <p class="fq-health-copy" id="fq-audit-copy">{{ $result['message'] }}</p>
                    @if($resultQueued)
                        <div id="fq-stale-warning" class="mt-2" style="display: none;">
                            <span class="badge bg-warning text-dark me-2"><i class="bi bi-clock-history me-1"></i>Worker belum memproses</span>
                            <form method="POST" action="{{ route('marketplace.reports.financial-quality.process-now') }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="store_id" value="{{ $storeId ?: 'all' }}">
                                <button type="submit" class="btn btn-sm btn-primary py-0 px-2" style="font-size: 0.72rem;">
                                    <i class="bi bi-play-fill me-1"></i>Proses Sekarang (Langsung)
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
            <span class="fq-health-time" id="fq-audit-time">{{ now()->format('d M Y H:i') }}</span>
        </div>
    @elseif ($hasIssues)
        <div class="fq-health fq-health-attention" role="status">
            <div class="fq-health-main">
                <div class="fq-health-icon"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <p class="fq-health-title">Perlu ditinjau sebelum posting</p>
            <p class="fq-health-copy"><strong>{{ $fmt($qualityIssues) }} order belum lengkap</strong> · <strong>{{ $fmt($settlementIssues) }} payout bermasalah</strong></p>
                </div>
            </div>
            @if ($lastCheckedAt)<span class="fq-health-time">Audit {{ date('d M Y H:i', strtotime($lastCheckedAt)) }}</span>@endif
        </div>
    @else
        <div class="fq-health fq-health-good" role="status">
            <div class="fq-health-main">
                <div class="fq-health-icon"><i class="bi bi-check2-circle"></i></div>
                <div>
                    <p class="fq-health-title">Tidak ada issue yang terdeteksi</p>
            <p class="fq-health-copy">Data siap digunakan untuk laporan dan accounting.</p>
                </div>
            </div>
            @if ($lastCheckedAt)<span class="fq-health-time">Audit {{ date('d M Y H:i', strtotime($lastCheckedAt)) }}</span>@endif
        </div>
    @endif

    @if ($errors->has('store_id'))
        <div class="alert alert-danger py-2 small">{{ $errors->first('store_id') }}</div>
    @endif

    <section class="fq-kpis" aria-label="Ringkasan pemeriksaan">
        <div class="fq-card fq-kpi">
            <span class="fq-kpi-label"><span class="fq-kpi-icon"><i class="bi bi-inbox"></i></span>Perlu diperbaiki</span>
            <strong class="fq-kpi-value">{{ $fmt($orders->total()) }}</strong>
            <span class="fq-kpi-note">{{ $orders->total() ? 'Issue aktif' : 'Tidak ada issue' }}</span>
        </div>
        <div class="fq-card fq-kpi">
            <span class="fq-kpi-label"><span class="fq-kpi-icon"><i class="bi bi-check2-circle"></i></span>Siap masuk laporan</span>
            <strong class="fq-kpi-value">{{ $fmt($orderCounts['ready'] ?? 0) }}</strong>
            <span class="fq-kpi-note">{{ $fmt($auditedOrders) }} order diperiksa</span>
        </div>
        <div class="fq-card fq-kpi fq-kpi-danger">
            <span class="fq-kpi-label"><span class="fq-kpi-icon"><i class="bi bi-wallet2"></i></span>Payout bermasalah</span>
            <strong class="fq-kpi-value">{{ $fmt($settlementIssues) }}</strong>
            <span class="fq-kpi-note">Belum siap</span>
        </div>
        <div class="fq-card fq-kpi fq-kpi-warning">
            <span class="fq-kpi-label"><span class="fq-kpi-icon"><i class="bi bi-hourglass-split"></i></span>Menunggu order selesai</span>
            <strong class="fq-kpi-value">{{ $fmt($waitingOrderCount ?? 0) }}</strong>
            <span class="fq-kpi-note">Belum final</span>
        </div>
    </section>

    <section class="fq-card fq-filter-card" aria-labelledby="filter-title">
        <div class="fq-filter-head">
            <div class="fq-filter-title-wrap">
                <span class="fq-filter-icon"><i class="bi bi-sliders2"></i></span>
                <div>
                    <h2 id="filter-title" class="fq-filter-title">Filter data</h2>
                </div>
            </div>
            <div class="fq-filter-tools">
                @if ($activeFilterCount)<span class="fq-filter-count">{{ $activeFilterCount }} aktif</span><a class="fq-filter-reset" href="{{ route('marketplace.reports.financial-quality') }}">Bersihkan</a>@endif
            </div>
        </div>
        <div class="fq-filter-body">
            <form id="fq-filter-form" method="GET" class="fq-filter-form" aria-label="Filter data, otomatis diterapkan">
                <div class="fq-field fq-field-range">
                    <label class="fq-field-label" for="fq-date-range">Periode order</label>
                    <div class="fq-range-control">
                        <input id="fq-date-range" class="form-control" type="text" value="{{ $dateRangeValue }}" placeholder="Pilih periode" data-gf-date="off" autocomplete="off" aria-label="Pilih periode order">
                        <i class="bi bi-calendar3 fq-range-icon"></i>
                    </div>
                    <input id="fq-date-from" type="hidden" name="date_from" value="{{ $dateFrom }}" data-gf-date="off">
                    <input id="fq-date-to" type="hidden" name="date_to" value="{{ $dateTo }}" data-gf-date="off">
                </div>
                <div class="fq-field">
                    <label class="fq-field-label" for="fq-store">Toko</label>
                    <select id="fq-store" name="store_id" class="form-select"><option value="">Semua toko</option>@foreach ($stores as $store)<option value="{{ $store->id }}" @selected((int) $storeId === (int) $store->id)>{{ $store->name }}</option>@endforeach</select>
                </div>
                <div class="fq-field">
                    <label class="fq-field-label" for="fq-order-status">Status order</label>
                    <select id="fq-order-status" name="order_status" class="form-select"><option value="">Semua status</option>@foreach ($orderStatuses as $value)<option value="{{ $value }}" @selected($orderStatus === $value)>{{ $value }}</option>@endforeach</select>
                </div>
                <div class="fq-field">
                    <label class="fq-field-label" for="fq-quality">Status data</label>
                    <select id="fq-quality" name="status" class="form-select"><option value="">Semua status data</option>@foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select>
                </div>
                <div class="fq-field">
                    <label class="fq-field-label" for="fq-settlement">Status payout</label>
                    <select id="fq-settlement" name="settlement_status" class="form-select"><option value="">Semua status payout</option><option value="complete" @selected($settlementStatus === 'complete')>Payout lengkap</option><option value="incomplete" @selected($settlementStatus === 'incomplete')>Payout belum lengkap</option><option value="missing" @selected($settlementStatus === 'missing')>Payout belum tersedia</option><option value="unknown" @selected($settlementStatus === 'unknown')>Payout belum diperiksa</option></select>
                </div>
                <div class="fq-field fq-field-search">
                    <label class="fq-field-label" for="fq-search">Cari order</label>
                    <input id="fq-search" class="form-control" type="search" name="q" value="{{ $search }}" placeholder="Order, booking, buyer" autocomplete="off">
                </div>
            </form>
        </div>
    </section>

    <section id="fq-audit-actions" class="fq-card fq-audit-card" aria-labelledby="audit-title">
        <div class="fq-refresh">
            <div class="fq-refresh-copy">
                <i class="bi bi-2-circle"></i>
                <div>
                    <p id="audit-title" class="fq-refresh-title">Audit kualitas</p>
                    <p class="fq-refresh-note">{{ $storeId ? 'Toko terpilih' : 'Semua toko' }} · Payout · Mapping · HPP</p>
                </div>
            </div>
            <div class="fq-refresh-actions">
                <form method="POST" action="{{ route('marketplace.reports.financial-quality.refresh') }}" class="d-flex gap-2">
                    @csrf
                    <input type="hidden" name="store_id" value="{{ $storeId ?: 'all' }}">
                    <button name="dry_run" value="0" class="btn btn-sm fq-btn fq-btn-primary"><i class="bi bi-play-circle me-1"></i>Jalankan audit</button>
                </form>
            </div>
        </div>
    </section>

    <section class="fq-main-grid">
        <div class="fq-card fq-queue-card">
            <div class="fq-queue-head">
                <div>
                    <div class="fq-queue-heading"><i class="bi bi-list-check text-primary"></i><h2 class="fq-queue-title">Data yang perlu diperbaiki</h2></div>
                    <p class="fq-queue-subtitle">Order selesai dengan issue finansial.</p>
                </div>
                <span class="fq-queue-total">{{ $fmt($orders->total()) }} hasil · halaman {{ $orders->currentPage() }}</span>
            </div>
            <div class="fq-table-wrap">
                <table class="table fq-table">
                    <thead><tr><th>Order &amp; toko</th><th>Status pemeriksaan</th><th>HPP</th><th>Masalah utama</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @forelse ($orders as $order)
                        @php
                            $qualityStatus = $order->financial_data_status ?: 'unknown';
                            $flags = $order->settlement?->data_quality_flags ?? [];
                            $missing = $flags['blocking_missing_fields'] ?? $flags['missing_financial_fields'] ?? [];
                            $itemCount = $order->items->count();
                            $validItems = $order->items->filter(fn ($item) => ($item->data_status ?? null) === 'valid' && (float) ($item->hpp_snapshot ?? 0) > 0)->count();
                            $settlementStatusRow = $order->settlement?->data_status ?: ($order->settlement ? 'unknown' : 'missing');
                            $orderStatusKey = strtoupper((string) $order->order_status);
                            $issueReason = $order->financial_issue_reason ?: ($settlementStatusRow === 'missing' ? 'settlement_missing' : null);
                            $issueText = $issueLabels[$issueReason] ?? ($issueReason ? str_replace('_', ' ', $issueReason) : null);
                            $missingText = collect(is_array($missing) ? $missing : [])->map(fn ($field) => $fieldLabels[$field] ?? str_replace('_', ' ', $field))->take(2)->implode(', ');
                            $missingCount = is_array($missing) ? count($missing) : 0;
                        @endphp
                        <tr>
                            <td data-label="Order & toko"><div class="fq-order">{{ $order->channel_order_id ?: $order->external_order_id }}</div><div class="fq-order-meta"><span class="fq-store">{{ $order->store?->name ?: '-' }}</span><span class="fq-muted">{{ strtoupper($order->store?->channel?->code ?? '-') }}</span><span class="fq-muted">{{ $orderStatusLabels[$orderStatusKey] ?? ($order->order_status ?: 'Status belum tersedia') }} · {{ optional($order->ordered_at)->format('d M Y H:i') ?: '-' }}</span></div></td>
                            <td data-label="Status"><div class="fq-status-stack"><span class="fq-status {{ $statusClasses[$qualityStatus] ?? 'fq-status-unknown' }}">{{ $statusLabels[$qualityStatus] ?? 'Unknown' }}</span><span class="fq-status {{ $settlementStatusRow === 'complete' ? 'fq-status-ready' : ($settlementStatusRow === 'missing' ? 'fq-status-missing' : 'fq-status-incomplete') }}">{{ $settlementLabels[$settlementStatusRow] ?? 'Payout belum diperiksa' }}</span></div></td>
                            <td data-label="HPP"><span class="{{ $validItems === $itemCount && $itemCount > 0 ? 'fq-valid' : 'fq-invalid' }}">{{ $itemCount > 0 ? $validItems . '/' . $itemCount . ' siap' : 'Belum ada item' }}</span></td>
                            <td data-label="Masalah utama" class="fq-muted"><span class="fq-issue-main">{{ $issueText ?: 'Tidak ada masalah utama' }}</span>@if ($missingText)<span class="fq-issue-missing"><i class="bi bi-info-circle me-1"></i>Data kurang: {{ $missingText }}{{ $missingCount > 2 ? '…' : '' }}</span>@endif</td>
                            <td data-label="Aksi" class="fq-actions-cell">
                                <div class="fq-row-actions">
                                    <form method="POST" action="{{ route('marketplace.reports.financial-quality.orders.repair', $order) }}" onsubmit="return confirm('Perbaiki ulang mapping/HPP dan tarik payout order ini bila tersedia?')">
                                        @csrf
                                        <button type="submit" class="fq-action-fix" aria-label="Perbaiki otomatis" title="Perbaiki otomatis"><i class="bi bi-magic me-1"></i>Perbaiki</button>
                                    </form>
                                    <a href="{{ route('marketplace.issues', ['q' => $order->channel_order_id ?: $order->external_order_id, 'store_id' => $order->store_id, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="fq-action" aria-label="Mapping manual" title="Mapping manual">Mapping <i class="bi bi-arrow-up-right ms-1"></i></a>
                                    <a href="{{ route('marketplace.orders.show', $order) }}" class="fq-action-muted" aria-label="Lihat detail order" title="Lihat detail order">Detail</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="fq-empty"><div class="fq-empty-icon"><i class="bi bi-check2"></i></div><p class="fq-empty-title">Tidak ada issue</p><p class="fq-empty-copy">Semua data sesuai filter sudah siap.</p></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())<div class="fq-pagination">{{ $orders->links() }}</div>@endif
        </div>

        <aside class="fq-sidebar">
            <div class="fq-card">
                <div class="fq-card-head"><h2 class="fq-card-title">Ringkasan status data</h2><span class="fq-card-meta">{{ $fmt($auditedOrders) }} order</span></div>
                <div class="fq-summary-list">
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot ready"></i>Siap masuk laporan</span><strong class="fq-summary-value">{{ $fmt($orderCounts['ready'] ?? 0) }}</strong></div>
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot warning"></i>Data belum lengkap</span><strong class="fq-summary-value">{{ $fmt($orderCounts['incomplete'] ?? 0) }}</strong></div>
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot"></i>Menunggu order selesai</span><strong class="fq-summary-value">{{ $fmt($waitingOrderCount ?? 0) }}</strong></div>
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot"></i>Belum diperiksa</span><strong class="fq-summary-value">{{ $fmt($orderCounts['unknown'] ?? 0) }}</strong></div>
                </div>
                <div class="fq-progress"><div class="fq-progress-bar" style="width: {{ $auditedOrders > 0 ? min(100, (($orderCounts['ready'] ?? 0) / $auditedOrders) * 100) : 0 }}%"></div></div>
                <div class="fq-progress-note">{{ $auditedOrders > 0 ? $fmt(round((($orderCounts['ready'] ?? 0) / $auditedOrders) * 100)) . '% order siap' : 'Belum ada order diaudit' }}</div>
            </div>

            <div class="fq-card">
                <div class="fq-card-head"><h2 class="fq-card-title">Ringkasan payout</h2></div>
                <div class="fq-summary-list">
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot ready"></i>Payout lengkap</span><strong class="fq-summary-value">{{ $fmt($settlementCounts['complete'] ?? 0) }}</strong></div>
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot danger"></i>Payout belum lengkap</span><strong class="fq-summary-value">{{ $fmt($settlementCounts['incomplete'] ?? 0) }}</strong></div>
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot"></i>Payout belum diperiksa</span><strong class="fq-summary-value">{{ $fmt($settlementCounts['unknown'] ?? 0) }}</strong></div>
                </div>
                <div class="fq-issues">
                    @forelse ($issueBreakdown->take(4) as $issue)
                        <div class="fq-issue"><span class="fq-issue-name">{{ $issueLabels[$issue->reason] ?? str_replace('_', ' ', $issue->reason) }}</span><span class="fq-issue-count">{{ $fmt($issue->total) }}</span></div>
                    @empty
                        <div class="fq-muted">Belum ada masalah data.</div>
                    @endforelse
                </div>
            </div>

            <div class="fq-card">
                <div class="fq-card-head"><h2 class="fq-card-title">Kesiapan posting akuntansi</h2><span class="fq-card-meta">{{ $fmt($eligibleOrderTotal) }} order final</span></div>
                <div class="fq-summary-list">
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot ready"></i>Siap posting</span><strong class="fq-summary-value">{{ $fmt($postingReadyCount) }}</strong></div>
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot danger"></i>Tertahan sebelum posting</span><strong class="fq-summary-value">{{ $fmt($postingBlockedCount) }}</strong></div>
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot"></i>Menunggu order final</span><strong class="fq-summary-value">{{ $fmt($waitingOrderCount ?? 0) }}</strong></div>
                </div>
                <div class="fq-progress"><div class="fq-progress-bar" style="width: {{ $postingReadyPercentage }}%"></div></div>
                <div class="fq-progress-note">{{ $eligibleOrderTotal > 0 ? $postingReadyPercentage . '% order final siap posting' : 'Belum ada order final' }}</div>
            </div>

        </aside>
    </section>
</div>
@endsection
