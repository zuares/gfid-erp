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

    .sales-dashboard .sales-filter-card {
        background: color-mix(in srgb, var(--sales-card) 92%, var(--sales-bg) 8%);
        box-shadow: 0 6px 18px rgba(15, 23, 42, .07);
    }
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
    .sales-dashboard .sales-nav-shell {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: 1.5rem;
    }
    .sales-dashboard .sales-nav {
        flex: 1 1 auto;
        min-width: 0;
        padding: .25rem;
        border: 1px solid var(--sales-line);
        border-radius: 12px;
        background: color-mix(in srgb, var(--sales-card) 90%, var(--sales-bg) 10%);
    }
    .sales-dashboard .sales-nav-comparison {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        gap: .2rem;
        margin-left: auto;
        padding: .2rem;
        border-left: 1px solid var(--sales-line);
    }
    .sales-dashboard .sales-nav-comparison-label {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .45rem .5rem .45rem .65rem;
        color: var(--sales-muted);
        font-size: .61rem;
        font-weight: 800;
        letter-spacing: .055em;
        line-height: 1;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .sales-dashboard .sales-nav-comparison-label i { color: var(--sales-accent); font-size: .7rem; }
    .sales-dashboard .sales-comparison-tab {
        border: 0;
        border-radius: 7px;
        padding: .45rem .65rem;
        color: var(--sales-muted);
        background: transparent;
        font-size: .72rem;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
        transition: background .16s ease, color .16s ease, box-shadow .16s ease;
    }
    .sales-dashboard .sales-comparison-tab i { font-size: .68rem; }
    .sales-dashboard .sales-comparison-tab:hover { color: var(--sales-accent); background: var(--sales-accent-soft); }
    .sales-dashboard .sales-comparison-tab.active {
        color: var(--sales-accent);
        background: var(--sales-accent-soft);
        box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--sales-accent) 12%, var(--sales-line) 88%);
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

    .sales-dashboard .sales-kpi { min-height: 148px; position: relative; overflow: hidden; }
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
    .sales-dashboard .sales-kpi-label { max-width: calc(100% - 2.5rem); color: var(--sales-muted); font-size: .7rem; font-weight: 750; line-height: 1.2; }
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
    .sales-dashboard .sales-kpi-primary { min-width: 0; }
    .sales-dashboard .sales-kpi-value { overflow: hidden; color: var(--sales-ink); font-size: clamp(1rem, 1.2vw, 1.2rem); font-weight: 800; letter-spacing: -.025em; line-height: 1.15; text-overflow: ellipsis; white-space: nowrap; }
    .sales-dashboard .sales-kpi-group-divider {
        display: flex;
        align-items: center;
        gap: .65rem;
        margin-top: .15rem;
        color: var(--sales-muted);
        font-size: .62rem;
        font-weight: 800;
        letter-spacing: .1em;
        line-height: 1;
        text-transform: uppercase;
    }
    .sales-dashboard .sales-kpi-group-divider::after {
        content: '';
        height: 1px;
        flex: 1 1 auto;
        background: var(--sales-line);
    }
    .sales-dashboard .sales-kpi-group-label { white-space: nowrap; }
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
    .sales-dashboard .sales-kpi-comparison { min-width: 0; justify-content: flex-start; padding: .22rem 0 0; border: 0; border-top: 1px solid var(--sales-line); background: transparent; font-size: .56rem; }
    .sales-dashboard .sales-kpi-comparisons { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: stretch; gap: .35rem .65rem; }
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
    .sales-dashboard .sales-compare-value {
        display: inline-flex;
        align-items: center;
        font-size: .72rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
    }
    .sales-dashboard .sales-compare-value.is-good { color: var(--success, #16a34a); }
    .sales-dashboard .sales-compare-value.is-bad { color: var(--danger, #dc2626); }
    .sales-dashboard .sales-compare-value.is-neutral { color: var(--sales-muted); }
    .sales-dashboard .sales-phase-legend { display: flex; flex-wrap: wrap; align-items: center; gap: .55rem; color: var(--sales-muted); font-size: .68rem; font-weight: 650; }
    .sales-dashboard .sales-phase-legend span { display: inline-flex; align-items: center; gap: .25rem; white-space: nowrap; }
    .sales-dashboard .sales-phase-dot { width: .48rem; height: .48rem; border-radius: 50%; display: inline-block; }
    .sales-dashboard .sales-phase-dot--early { background: #60a5fa; }
    .sales-dashboard .sales-phase-dot--mid { background: #f59e0b; }
    .sales-dashboard .sales-phase-dot--late { background: #f87171; }
    .sales-dashboard .sales-period-phase-badge { display: inline-flex; align-items: center; gap: .2rem; margin-top: .18rem; padding: .14rem .4rem; border-radius: 999px; font-size: .58rem; font-weight: 750; line-height: 1.1; white-space: nowrap; }
    .sales-dashboard .sales-period-phase-badge .sales-phase-dot { width: .4rem; height: .4rem; }
    .sales-dashboard .sales-period-phase-range { opacity: .78; font-weight: 650; }
    .sales-dashboard .sales-period-phase-badge--early { color: #1d4ed8; background: #dbeafe; }
    .sales-dashboard .sales-period-phase-badge--mid { color: #b45309; background: #fef3c7; }
    .sales-dashboard .sales-period-phase-badge--late { color: #b91c1c; background: #fee2e2; }
    .sales-dashboard .sales-payment-table tr.sales-payment-phase--early > td:first-child { border-left: 3px solid #60a5fa; background: color-mix(in srgb, #60a5fa 7%, transparent); }
    .sales-dashboard .sales-payment-table tr.sales-payment-phase--mid > td:first-child { border-left: 3px solid #f59e0b; background: color-mix(in srgb, #f59e0b 7%, transparent); }
    .sales-dashboard .sales-payment-table tr.sales-payment-phase--late > td:first-child { border-left: 3px solid #f87171; background: color-mix(in srgb, #f87171 7%, transparent); }
    .sales-dashboard .sales-filter-scope {
        display: flex;
        align-items: flex-end;
        gap: .45rem;
        padding: .5rem;
        border: 1px solid color-mix(in srgb, var(--sales-line) 82%, transparent);
        border-radius: 12px;
        background: color-mix(in srgb, var(--sales-soft) 76%, var(--sales-card) 24%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.42);
    }
    .sales-dashboard .sales-filter-field { min-width: 0; }
    .sales-dashboard .sales-filter-platform { width: 145px; }
    .sales-dashboard .sales-filter-store { width: 235px; }
    .sales-dashboard .sales-filter-comparison { width: 175px; }
    .sales-dashboard .sales-filter-label {
        display: flex;
        align-items: center;
        gap: .3rem;
        margin: 0 0 .28rem .1rem;
        color: var(--sales-muted);
        font-size: .61rem;
        font-weight: 800;
        letter-spacing: .055em;
        line-height: 1;
        text-transform: uppercase;
    }
    .sales-dashboard .sales-filter-label i { color: var(--sales-accent); font-size: .7rem; }
    .sales-dashboard .sales-filter-field .form-select {
        min-height: 34px;
        border-color: color-mix(in srgb, var(--sales-line) 88%, transparent);
        border-radius: 8px;
        background-color: var(--sales-card);
        color: var(--sales-ink);
        font-size: .72rem;
        font-weight: 650;
        box-shadow: none;
        transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
    }
    .sales-dashboard .sales-filter-field .form-select:hover { border-color: color-mix(in srgb, var(--sales-accent) 42%, var(--sales-line) 58%); }
    .sales-dashboard .sales-filter-field .form-select:focus {
        border-color: var(--sales-accent);
        background-color: var(--sales-card);
        box-shadow: 0 0 0 .18rem color-mix(in srgb, var(--sales-accent) 14%, transparent);
    }
    .sales-dashboard .sales-period-filter .gf-period-trigger {
        min-height: 58px;
        padding: .55rem .75rem;
        border-color: var(--sales-line);
        border-radius: 12px;
        background: var(--sales-card);
        box-shadow: 0 6px 16px rgba(15, 23, 42, .06);
    }
    .sales-dashboard .sales-period-filter .gf-period-trigger:hover,
    .sales-dashboard .sales-period-filter .gf-period-trigger[aria-expanded="true"] {
        border-color: color-mix(in srgb, var(--sales-accent) 48%, var(--sales-line) 52%);
        box-shadow: 0 8px 20px rgba(15, 23, 42, .1);
        transform: translateY(-1px);
    }
    .sales-dashboard .sales-kpi--success .sales-kpi-icon { background: var(--success-soft, #dcfce7); color: var(--success, #16a34a); border-color: color-mix(in srgb, var(--success, #16a34a) 16%, var(--sales-line) 84%); }
    .sales-dashboard .sales-kpi--success::after { background: var(--success, #16a34a); }
    .sales-dashboard .sales-kpi--warning .sales-kpi-icon { background: var(--danger-soft, #fee2e2); color: var(--danger, #dc2626); border-color: color-mix(in srgb, var(--danger, #dc2626) 16%, var(--sales-line) 84%); }
    .sales-dashboard .sales-kpi--warning::after { background: var(--danger, #dc2626); }

    .sales-dashboard .sales-section-header { padding: 1rem 1.15rem .85rem; }
    .sales-dashboard .sales-section-title { color: var(--sales-ink); font-size: 1rem; font-weight: 750; }
    .sales-dashboard .sales-funding-section {
        margin-bottom: 1rem !important;
        overflow: hidden;
    }
    .sales-dashboard .sales-funding-section .sales-section-header {
        min-height: 5.25rem;
    }
    .sales-dashboard .sales-funding-section .sales-table th,
    .sales-dashboard .sales-funding-section .sales-table td {
        padding: .68rem .7rem;
    }
    .sales-dashboard .sales-funding-section .sales-table tbody tr:last-child td {
        border-bottom: 0;
    }
    .sales-dashboard .sales-cost-section {
        border-color: color-mix(in srgb, #f59e0b 28%, var(--sales-line) 72%);
        background: color-mix(in srgb, #fffbeb 34%, var(--sales-card) 66%);
    }
    .sales-dashboard .sales-cost-section .sales-section-header {
        background: linear-gradient(180deg, color-mix(in srgb, #f59e0b 8%, var(--sales-card) 92%), var(--sales-card));
        border-bottom: 1px solid color-mix(in srgb, #f59e0b 18%, var(--sales-line) 82%);
    }
    .sales-dashboard .sales-cost-section .sales-kicker,
    .sales-dashboard .sales-cost-section .sales-period-current .sales-period-label { color: #b45309; }
    .sales-dashboard .sales-cost-section .sales-badge {
        color: #b45309;
        background: color-mix(in srgb, #f59e0b 12%, var(--sales-card) 88%);
        border-color: color-mix(in srgb, #f59e0b 30%, var(--sales-line) 70%);
    }
    .sales-dashboard .sales-cost-section .sales-period-current { background: color-mix(in srgb, #f59e0b 9%, var(--sales-card) 91%); }
    body[data-theme="dark"] .sales-dashboard .sales-cost-section .sales-kicker,
    body[data-theme="dark"] .sales-dashboard .sales-cost-section .sales-period-current .sales-period-label,
    body[data-theme="dark"] .sales-dashboard .sales-cost-section .sales-badge { color: #fbbf24; }
    .sales-dashboard .sales-funding-table {
        width: 100%;
        table-layout: fixed;
    }
    .sales-dashboard .sales-funding-table th:first-child,
    .sales-dashboard .sales-funding-table td:first-child {
        width: 30%;
    }
    .sales-dashboard .sales-funding-table th:not(:first-child),
    .sales-dashboard .sales-funding-table td:not(:first-child) {
        width: 17.5%;
    }
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
    .sales-dashboard .sales-promotion-table th {
        font-size: clamp(.48rem, .07vw + .46rem, .56rem);
        letter-spacing: .01em;
        line-height: 1.15;
        padding: .42rem .24rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: clip;
        vertical-align: middle;
    }
    .sales-dashboard .sales-promotion-table td {
        font-size: clamp(.62rem, .14vw + .58rem, .7rem);
        padding: .5rem .3rem;
        white-space: normal;
        overflow-wrap: anywhere;
    }
    .sales-dashboard .sales-promotion-table td .small {
        font-size: clamp(.52rem, .1vw + .48rem, .62rem);
        }
    .sales-dashboard .sales-promotion-table {
        width: 100%;
        min-width: 0;
        table-layout: fixed;
    }
    .sales-dashboard .sales-index-column,
    .sales-dashboard .sales-index-cell { width: 3.5rem; min-width: 3.5rem; text-align: center; }
    .sales-dashboard .sales-index-cell { color: var(--sales-muted); font-variant-numeric: tabular-nums; font-weight: 650; }
    .sales-dashboard .sales-product-item-index { padding-left: .5rem !important; text-align: center; }
    .sales-dashboard .sales-product-item-number { display: inline-block; transform: translateX(.65rem); }
    .sales-dashboard .sales-daily-table { width: 100%; min-width: 0; table-layout: fixed; }
    .sales-dashboard .sales-daily-col-toggle { width: 3.5rem; }
    .sales-dashboard .sales-daily-col-date { width: 9%; }
    .sales-dashboard .sales-daily-col-count { width: 6%; }
    .sales-dashboard .sales-daily-col-units { width: 6%; }
    .sales-dashboard .sales-daily-col-unit-order { width: 6%; }
    .sales-dashboard .sales-daily-col-metric { width: auto; }
    .sales-dashboard .sales-daily-table th,
    .sales-dashboard .sales-daily-table td { padding: .42rem .24rem; }
    .sales-dashboard .sales-daily-table th { font-size: clamp(.5rem, .1vw + .47rem, .62rem); line-height: 1.12; white-space: normal; overflow-wrap: anywhere; }
    .sales-dashboard .sales-daily-table td { font-size: clamp(.58rem, .12vw + .54rem, .68rem); white-space: normal; overflow-wrap: anywhere; }
    .sales-dashboard .sales-daily-table .sales-table-metric { white-space: nowrap; }
    .sales-dashboard .sales-daily-table .sales-table-metric .small { font-size: clamp(.46rem, .08vw + .43rem, .56rem); line-height: 1.15; }
    .sales-dashboard .sales-daily-toggle { display: inline-flex; width: 1.65rem; height: 1.65rem; align-items: center; justify-content: center; border: 1px solid var(--sales-line); border-radius: 50%; background: var(--sales-card); color: var(--sales-muted); padding: 0; transition: background-color .16s ease, color .16s ease, border-color .16s ease, transform .16s ease; }
    .sales-dashboard .sales-daily-toggle:hover,
    .sales-dashboard .sales-daily-toggle:focus-visible { border-color: var(--sales-accent); background: var(--sales-accent-soft); color: var(--sales-accent); outline: none; }
    .sales-dashboard .sales-daily-toggle i { transition: transform .16s ease; }
    .sales-dashboard .sales-daily-toggle[aria-expanded="true"] { border-color: var(--sales-accent); background: var(--sales-accent-soft); color: var(--sales-accent); }
    .sales-dashboard .sales-daily-toggle[aria-expanded="true"] i { transform: rotate(90deg); }
    .sales-dashboard .sales-daily-row > td { vertical-align: middle; }
    .sales-dashboard .sales-daily-row--expanded > td { background: color-mix(in srgb, var(--sales-accent-soft) 22%, var(--sales-card) 78%); border-bottom-color: transparent; }
    .sales-dashboard .sales-daily-store-detail > td { background: color-mix(in srgb, var(--sales-accent-soft) 18%, var(--sales-card) 82%); border: 0 !important; padding: 0; }
    .sales-dashboard .sales-daily-store-shell { padding: .42rem 0 .5rem; }
    .sales-dashboard .sales-daily-store-table { width: 100%; min-width: 0; table-layout: fixed; margin: 0; }
    .sales-dashboard .sales-daily-store-table td { padding: .42rem .24rem; border: 0 !important; font-size: clamp(.56rem, .11vw + .52rem, .65rem); white-space: normal; overflow-wrap: anywhere; }
    .sales-dashboard .sales-daily-store-table tbody tr:hover td { background: color-mix(in srgb, var(--sales-accent-soft) 30%, var(--sales-card) 70%); }
    .sales-dashboard .sales-daily-store-spacer { padding-inline: 0 !important; }
    .sales-dashboard .sales-daily-store-name { overflow: hidden; color: var(--sales-ink); font-weight: 750; white-space: nowrap; text-overflow: ellipsis; }
    .sales-dashboard .sales-daily-store-name-inner { display: inline-flex; max-width: 100%; align-items: center; gap: .28rem; overflow: hidden; text-overflow: ellipsis; vertical-align: middle; }
    .sales-dashboard .sales-daily-store-name-inner i { flex: 0 0 auto; color: var(--sales-accent); font-size: .7rem; }
    .sales-dashboard .sales-daily-store-name-inner span { overflow: hidden; text-overflow: ellipsis; }
    .sales-dashboard .sales-daily-store-table .sales-table-metric { white-space: nowrap; }
    .sales-dashboard .sales-daily-store-table .sales-table-metric .small { font-size: clamp(.44rem, .07vw + .41rem, .53rem); line-height: 1.15; }
    .sales-dashboard .sales-daily-table .sales-compare-line { margin-top: .18rem; gap: .2rem; font-size: .52rem; }
    .sales-dashboard .sales-daily-table .sales-compare-line i { font-size: .5rem; }
    .sales-dashboard .sales-daily-table .sales-compare-line strong { font-size: .54rem; }
    .sales-dashboard .sales-trend-section { overflow: hidden; }
    .sales-dashboard .sales-trend-body { padding: 0 .85rem .85rem; }
    .sales-dashboard .sales-trend-layout { display: grid; grid-template-columns: minmax(0, 1.65fr) minmax(230px, .75fr); gap: .75rem; }
    .sales-dashboard .sales-trend-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .55rem; margin-bottom: .75rem; }
    .sales-dashboard .sales-trend-summary-card { display: flex; min-width: 0; align-items: center; gap: .55rem; padding: .62rem .68rem; border: 1px solid color-mix(in srgb, var(--sales-line) 72%, transparent); border-radius: .65rem; background: color-mix(in srgb, var(--sales-soft) 35%, var(--sales-card) 65%); }
    .sales-dashboard .sales-trend-summary-icon { display: inline-flex; flex: 0 0 1.85rem; width: 1.85rem; height: 1.85rem; align-items: center; justify-content: center; border-radius: .52rem; background: var(--sales-accent-soft); color: var(--sales-accent); font-size: .8rem; }
    .sales-dashboard .sales-trend-summary-content { min-width: 0; }
    .sales-dashboard .sales-trend-summary-label { overflow: hidden; color: var(--sales-muted); font-size: .57rem; font-weight: 800; letter-spacing: .035em; text-overflow: ellipsis; text-transform: uppercase; white-space: nowrap; }
    .sales-dashboard .sales-trend-summary-value { overflow: hidden; margin-top: .1rem; color: var(--sales-ink); font-size: .76rem; font-weight: 850; text-overflow: ellipsis; white-space: nowrap; }
    .sales-dashboard .sales-trend-summary-delta { display: inline-flex; margin-top: .08rem; font-size: .57rem; font-weight: 800; }
    .sales-dashboard .sales-trend-summary-delta.good { color: #15803d; }
    .sales-dashboard .sales-trend-summary-delta.bad { color: #b91c1c; }
    .sales-dashboard .sales-trend-summary-delta.neutral { color: var(--sales-muted); }
    .sales-dashboard .sales-trend-panel,
    .sales-dashboard .sales-trend-insight { min-width: 0; border-radius: .75rem; background: color-mix(in srgb, var(--sales-soft) 42%, var(--sales-card) 58%); }
    .sales-dashboard .sales-trend-panel { padding: .7rem .75rem .55rem; }
    .sales-dashboard .sales-trend-toolbar { display: flex; align-items: center; justify-content: space-between; gap: .65rem; margin-bottom: .45rem; }
    .sales-dashboard .sales-trend-label { color: var(--sales-muted); font-size: .61rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .sales-dashboard .sales-trend-select { min-width: 145px; border: 1px solid var(--sales-line); border-radius: .45rem; background: var(--sales-card); color: var(--sales-ink); padding: .28rem .45rem; font-size: .68rem; font-weight: 750; }
    .sales-dashboard .sales-trend-total { color: var(--sales-ink); font-size: .85rem; font-weight: 850; white-space: nowrap; }
    .sales-dashboard .sales-trend-compare { margin-left: .25rem; color: var(--sales-muted); font-size: .61rem; font-weight: 750; white-space: nowrap; }
    .sales-dashboard .sales-trend-compare.good { color: #15803d; }
    .sales-dashboard .sales-trend-compare.bad { color: #b91c1c; }
    .sales-dashboard .sales-trend-chart { min-height: 215px; border-radius: .65rem; background: linear-gradient(180deg, color-mix(in srgb, var(--sales-card) 92%, var(--sales-soft) 8%), var(--sales-card)); }
    .sales-dashboard .sales-trend-chart svg { display: block; width: 100%; height: 215px; overflow: visible; }
    .sales-dashboard .sales-trend-chart .trend-grid { stroke: color-mix(in srgb, var(--sales-line) 75%, transparent); stroke-width: 1; vector-effect: non-scaling-stroke; }
    .sales-dashboard .sales-trend-chart .trend-axis-label { fill: var(--sales-muted); font-size: 11px; font-weight: 700; }
    .sales-dashboard .sales-trend-chart .trend-area { fill: color-mix(in srgb, var(--sales-accent-soft) 55%, transparent); }
    .sales-dashboard .sales-trend-chart .trend-line { fill: none; stroke: var(--sales-accent); stroke-linecap: round; stroke-linejoin: round; stroke-width: 3; vector-effect: non-scaling-stroke; }
    .sales-dashboard .sales-trend-chart .trend-line.previous { stroke: var(--sales-muted); stroke-dasharray: 5 5; stroke-width: 1.8; opacity: .72; }
    .sales-dashboard .sales-trend-chart .trend-point { fill: var(--sales-accent); stroke: var(--sales-card); stroke-width: 2; vector-effect: non-scaling-stroke; }
    .sales-dashboard .sales-trend-chart .trend-point.previous { fill: var(--sales-muted); }
    .sales-dashboard .sales-trend-legend { display: flex; flex-wrap: wrap; gap: .7rem; margin-top: .35rem; color: var(--sales-muted); font-size: .61rem; font-weight: 750; }
    .sales-dashboard .sales-trend-legend span { display: inline-flex; align-items: center; gap: .25rem; }
    .sales-dashboard .sales-trend-legend i { width: .45rem; height: .45rem; border-radius: 50%; background: var(--sales-accent); }
    .sales-dashboard .sales-trend-legend i.previous { background: var(--sales-muted); }
    .sales-dashboard .sales-trend-insights { display: grid; align-content: start; gap: .45rem; }
    .sales-dashboard .sales-trend-insight { padding: .7rem .75rem; }
    .sales-dashboard .sales-trend-insight-label { color: var(--sales-muted); font-size: .59rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .sales-dashboard .sales-trend-insight-value { margin-top: .2rem; color: var(--sales-ink); font-size: .82rem; font-weight: 850; }
    .sales-dashboard .sales-trend-insight-meta { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-top: .15rem; color: var(--sales-muted); font-size: .61rem; font-weight: 700; }
    .sales-dashboard .sales-trend-insight-meta strong { color: var(--sales-ink); font-weight: 850; }
    .sales-dashboard .sales-trend-insight--alert { border: 1px solid color-mix(in srgb, #dc2626 18%, var(--sales-line) 82%); background: color-mix(in srgb, #fee2e2 30%, var(--sales-card) 70%); }
    .sales-dashboard .sales-trend-insight--alert .sales-trend-insight-label,
    .sales-dashboard .sales-trend-insight--alert .sales-trend-insight-value { color: #b91c1c; }
    .sales-dashboard .sales-trend-header-meta { display: flex; align-items: center; justify-content: flex-end; gap: .45rem; }
    .sales-dashboard .sales-trend-period { color: var(--sales-muted); font-size: .65rem; font-weight: 700; white-space: nowrap; }
    .sales-dashboard .sales-trend-empty { padding: 2rem 1rem; color: var(--sales-muted); font-size: .72rem; text-align: center; }
    .sales-dashboard .sales-product-table { min-width: 1265px; table-layout: fixed; }
    .sales-dashboard .sales-product-table th,
    .sales-dashboard .sales-product-table td { white-space: nowrap; }
    .sales-dashboard .sales-product-table .sales-product-name { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sales-dashboard .sales-product-table.sales-promotion-table { width: 100%; min-width: 0; table-layout: fixed; }
    .sales-dashboard .sales-product-table.sales-promotion-table th { font-size: clamp(.48rem, .08vw + .45rem, .6rem); line-height: 1.15; padding: .42rem .24rem; white-space: normal; overflow-wrap: anywhere; }
    .sales-dashboard .sales-product-table.sales-promotion-table td { font-size: clamp(.6rem, .12vw + .56rem, .7rem); padding: .48rem .24rem; }
    .sales-dashboard .sales-product-table.sales-promotion-table thead tr:first-child th {
        background: var(--sales-soft);
        color: var(--sales-ink);
        font-size: .58rem;
        font-weight: 850;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .sales-dashboard .sales-product-table.sales-promotion-table thead tr:nth-child(2) th {
        color: var(--sales-muted);
        font-size: .55rem;
        font-weight: 800;
    }
    .sales-dashboard .sales-product-table.sales-promotion-table .sales-product-table-group-head {
        border-left: 1px solid var(--sales-line);
        border-right: 1px solid var(--sales-line);
        text-align: center;
    }
    .sales-dashboard .sales-product-table-wrap { position: relative; }
    .sales-dashboard .sales-product-table th:first-child,
    .sales-dashboard .sales-product-table td:first-child {
        position: sticky;
        left: 0;
        z-index: 3;
        background: var(--sales-card);
    }
    .sales-dashboard .sales-product-table th:nth-child(2),
    .sales-dashboard .sales-product-table td:nth-child(2) {
        position: sticky;
        left: 4%;
        z-index: 3;
        background: var(--sales-card);
        box-shadow: 8px 0 10px -12px rgba(15, 23, 42, .65);
    }
    .sales-dashboard .sales-product-table thead th { z-index: 5; }
    .sales-dashboard .sales-product-table .sales-product-group-row > td:first-child,
    .sales-dashboard .sales-product-table .sales-product-group-row > td:nth-child(2) { background: var(--sales-soft); }
    .sales-dashboard .sales-product-table .sales-product-marketplace-row > td:first-child,
    .sales-dashboard .sales-product-table .sales-product-marketplace-row > td:nth-child(2) { background: color-mix(in srgb, var(--sales-accent-soft) 24%, var(--sales-card) 76%); }
    .sales-dashboard .sales-product-table .sales-product-internal-row > td:first-child,
    .sales-dashboard .sales-product-table .sales-product-internal-row > td:nth-child(2) { background: color-mix(in srgb, var(--sales-soft) 72%, var(--sales-card) 28%); }
    .sales-dashboard .sales-product-group-row td { background: var(--sales-soft); border-top: 2px solid var(--sales-line); color: var(--sales-ink); padding-block: .62rem; }
    .sales-dashboard .sales-product-category-toggle { display: flex; width: 100%; align-items: center; gap: .55rem; border: 0; background: transparent; color: inherit; padding: 0; text-align: left; }
    .sales-dashboard .sales-product-category-toggle:hover { color: var(--accent, #2563eb); }
    .sales-dashboard .sales-product-category-toggle i { color: var(--accent, #2563eb); transition: transform .18s ease; }
    .sales-dashboard .sales-product-category-toggle[aria-expanded="true"] i { transform: rotate(90deg); }
    .sales-dashboard .sales-product-marketplace-toggle { display: flex; width: 100%; align-items: center; gap: .5rem; border: 0; background: transparent; color: inherit; padding: 0; text-align: left; }
    .sales-dashboard .sales-product-marketplace-toggle:hover { color: var(--accent, #2563eb); }
    .sales-dashboard .sales-product-marketplace-toggle i { color: var(--accent, #2563eb); transition: transform .18s ease; }
    .sales-dashboard .sales-product-marketplace-toggle[aria-expanded="true"] i { transform: rotate(90deg); }
    .sales-dashboard .sales-product-marketplace-row td { background: color-mix(in srgb, var(--sales-accent-soft) 24%, var(--sales-card) 76%); border-top: 1px solid color-mix(in srgb, var(--sales-accent) 18%, var(--sales-line) 82%); }
    .sales-dashboard .sales-product-marketplace-row .sales-product-marketplace-toggle { min-height: 2.15rem; }
    .sales-dashboard .sales-product-marketplace-title { display: flex; min-width: 0; align-items: center; gap: .4rem; }
    .sales-dashboard .sales-product-marketplace-thumb { position: relative; z-index: 1; display: inline-flex; flex: 0 0 2rem; width: 2rem; height: 2rem; align-items: center; justify-content: center; border: 1px solid color-mix(in srgb, var(--sales-accent) 18%, var(--sales-line) 82%); border-radius: 7px; background: var(--sales-soft); color: var(--sales-muted); }
    .sales-dashboard .sales-product-marketplace-thumb img { width: 100%; height: 100%; border-radius: 6px; object-fit: cover; transition: transform .18s ease, box-shadow .18s ease; }
    .sales-dashboard .sales-product-marketplace-thumb:hover,
    .sales-dashboard .sales-product-marketplace-thumb:focus-within { z-index: 20; }
    .sales-dashboard .sales-product-marketplace-thumb:hover img,
    .sales-dashboard .sales-product-marketplace-thumb:focus-within img { position: relative; z-index: 2; transform: scale(3); box-shadow: 0 8px 20px rgba(15, 23, 42, .22); }
    .sales-dashboard .sales-product-marketplace-thumb .sales-product-image-fallback { display: inline-flex; align-items: center; justify-content: center; font-size: .85rem; }
    .sales-dashboard .sales-product-image-preview-title { position: absolute; top: calc(100% + .35rem); left: 0; z-index: 3; width: 190px; padding: .35rem .45rem; border: 1px solid var(--sales-line); border-radius: 6px; background: var(--sales-card); box-shadow: 0 8px 18px rgba(15, 23, 42, .16); color: var(--sales-ink); font-size: .64rem; font-weight: 700; line-height: 1.25; opacity: 0; pointer-events: none; transform: translateY(-2px); transition: opacity .15s ease, transform .15s ease; visibility: hidden; white-space: normal; }
    .sales-dashboard .sales-product-marketplace-thumb:hover .sales-product-image-preview-title,
    .sales-dashboard .sales-product-marketplace-thumb:focus-within .sales-product-image-preview-title { opacity: 1; transform: translateY(0); visibility: visible; }
    .sales-dashboard .sales-product-marketplace-code { flex: 0 0 auto; padding: .2rem .4rem; border: 1px solid color-mix(in srgb, var(--sales-accent) 20%, var(--sales-line) 80%); border-radius: 5px; background: var(--sales-accent-soft); color: var(--sales-accent); font-size: .58rem; font-weight: 800; letter-spacing: .025em; line-height: 1.1; }
    .sales-dashboard .sales-product-count-badge { display: inline-flex; flex: 0 0 auto; align-items: center; padding: .18rem .35rem; border: 1px solid color-mix(in srgb, var(--sales-line) 90%, transparent); border-radius: 5px; background: color-mix(in srgb, var(--sales-soft) 80%, var(--sales-card) 20%); color: var(--sales-muted); font-size: .57rem; font-weight: 800; line-height: 1.1; white-space: nowrap; }
    .sales-dashboard .sales-product-marketplace-title .sales-product-group-meta { margin-left: .1rem; }
    .sales-dashboard .sales-product-hierarchy {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .45rem .8rem;
        padding: 0 1.15rem .8rem;
        color: var(--sales-muted);
        font-size: .64rem;
        font-weight: 700;
    }
    .sales-dashboard .sales-product-hierarchy span { display: inline-flex; align-items: center; gap: .3rem; }
    .sales-dashboard .sales-product-hierarchy i { color: var(--sales-accent); font-size: .7rem; }
    .sales-dashboard .sales-product-internal-row td { background: color-mix(in srgb, var(--sales-soft) 72%, var(--sales-card) 28%); }
    .sales-dashboard .sales-product-internal-cell { position: relative; padding-left: 3rem !important; }
    .sales-dashboard .sales-product-internal-cell::before { content: ''; position: absolute; left: 1.7rem; top: -.5rem; bottom: -.5rem; border-left: 1px solid color-mix(in srgb, var(--sales-accent) 24%, var(--sales-line) 76%); }
    .sales-dashboard .sales-product-internal-cell .sales-product-link { display: flex; align-items: center; gap: .45rem; }
    .sales-dashboard .sales-product-internal-code { display: block; max-width: 100%; overflow: hidden; padding: .2rem .4rem; border: 1px solid color-mix(in srgb, var(--sales-line) 90%, transparent); border-radius: 5px; background: var(--sales-card); color: var(--sales-ink); font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: .64rem; font-weight: 750; letter-spacing: .01em; text-overflow: ellipsis; white-space: nowrap; }
    .sales-dashboard .sales-product-variant-marker { display: inline-block; width: .38rem; height: .38rem; margin-left: 1.45rem; border: 1px solid color-mix(in srgb, var(--sales-accent) 35%, var(--sales-line) 65%); border-radius: 50%; background: var(--sales-accent-soft); }
    .sales-dashboard .sales-product-internal-index { padding-left: 1.45rem !important; color: var(--sales-muted); }
    .sales-dashboard .sales-product-group-title { font-size: .72rem; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; }
    .sales-dashboard .sales-product-group-meta { color: var(--sales-muted); font-size: .68rem; font-weight: 600; letter-spacing: 0; text-transform: none; }
    .sales-dashboard .sales-product-analysis-section { overflow: hidden; }
    .sales-dashboard .sales-product-analysis-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: .65rem;
        padding: 0 1.15rem 1rem;
    }
    .sales-dashboard .sales-product-analysis-kpi {
        min-width: 0;
        padding: .72rem .78rem;
        border: 1px solid color-mix(in srgb, var(--sales-line) 72%, transparent);
        border-radius: 10px;
        background: color-mix(in srgb, var(--sales-soft) 68%, var(--sales-card) 32%);
    }
    .sales-dashboard .sales-product-analysis-kpi-label {
        display: block;
        overflow: hidden;
        color: var(--sales-muted);
        font-size: .58rem;
        font-weight: 800;
        letter-spacing: .045em;
        line-height: 1.2;
        text-overflow: ellipsis;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .sales-dashboard .sales-product-analysis-kpi-value {
        display: block;
        margin-top: .35rem;
        overflow: hidden;
        color: var(--sales-ink);
        font-size: .95rem;
        font-weight: 800;
        letter-spacing: -.02em;
        line-height: 1.15;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sales-dashboard .sales-product-analysis-kpi-value.is-positive { color: var(--success, #16a34a); }
    .sales-dashboard .sales-product-analysis-kpi-value.is-warning { color: var(--warning, #d97706); }
    .sales-dashboard .sales-product-analysis-kpi-value.is-danger { color: var(--danger, #dc2626); }
    .sales-dashboard .sales-product-analysis-subsection {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 1rem;
        padding: .8rem 1.15rem .65rem;
        border-top: 1px solid var(--sales-line);
    }
    .sales-dashboard .sales-product-analysis-subsection-title {
        margin: 0;
        color: var(--sales-ink);
        font-size: .82rem;
        font-weight: 800;
    }
    .sales-dashboard .sales-product-analysis-subsection .sales-kicker { font-size: .58rem; }
    .sales-dashboard .sales-product-analysis-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: .35rem;
    }
    .sales-dashboard .sales-product-analysis-legend .analysis-status-badge { min-width: auto; }
    .sales-dashboard .sales-product-analysis-tables { padding: 0 1.15rem 1rem; }
    .sales-dashboard .sales-product-analysis-table { width: 100%; table-layout: fixed; }
    .sales-dashboard .sales-product-analysis-table th,
    .sales-dashboard .sales-product-analysis-table td { padding: .55rem .62rem; }
    .sales-dashboard .sales-product-analysis-table th { font-size: .61rem; }
    .sales-dashboard .sales-product-analysis-table td { font-size: .72rem; }
    .sales-dashboard .sales-product-analysis-table .analysis-label { color: var(--sales-muted); font-weight: 650; }
    .sales-dashboard .sales-product-analysis-table .analysis-value { color: var(--sales-ink); font-weight: 800; font-variant-numeric: tabular-nums; }
    .sales-dashboard .sales-product-analysis-table .analysis-value.is-positive { color: var(--success, #16a34a); }
    .sales-dashboard .sales-product-analysis-table .analysis-value.is-warning { color: var(--warning, #d97706); }
    .sales-dashboard .sales-product-analysis-table .analysis-value.is-danger { color: var(--danger, #dc2626); }
    .sales-dashboard .sales-product-analysis-table .analysis-value.is-muted { color: var(--sales-muted); }
    .sales-dashboard .sales-product-comparison-table { min-width: 980px; }
    .sales-dashboard .sales-product-comparison-table th,
    .sales-dashboard .sales-product-comparison-table td { white-space: nowrap; }
    .sales-dashboard .sales-payment-comparison-table,
    .sales-dashboard .sales-funding-table { min-width: 760px; }
    .sales-dashboard .sales-product-comparison-table th,
    .sales-dashboard .sales-payment-comparison-table th,
    .sales-dashboard .sales-funding-table th { padding: .72rem .72rem .64rem; font-size: .61rem; vertical-align: bottom; }
    .sales-dashboard .sales-product-comparison-table td,
    .sales-dashboard .sales-payment-comparison-table td,
    .sales-dashboard .sales-funding-table td { padding: .68rem .72rem; font-size: .72rem; }
    .sales-dashboard .sales-product-comparison-table th,
    .sales-dashboard .sales-product-comparison-table td,
    .sales-dashboard .sales-payment-comparison-table th,
    .sales-dashboard .sales-payment-comparison-table td,
    .sales-dashboard .sales-funding-table th,
    .sales-dashboard .sales-funding-table td { vertical-align: middle; }
    .sales-dashboard .sales-product-comparison-table .sales-period-current,
    .sales-dashboard .sales-payment-comparison-table .sales-period-current,
    .sales-dashboard .sales-funding-table .sales-period-current { background: color-mix(in srgb, var(--sales-accent-soft) 32%, var(--sales-card) 68%); }
    .sales-dashboard .sales-product-comparison-table .sales-period-current .sales-period-label,
    .sales-dashboard .sales-payment-comparison-table .sales-period-current .sales-period-label,
    .sales-dashboard .sales-funding-table .sales-period-current .sales-period-label { color: var(--sales-accent); }
    .sales-dashboard .sales-sales-comparison-table { min-width: 760px; }
    .sales-dashboard .sales-comparison-section .sales-section-subtitle { display: block; margin-top: .22rem; color: var(--sales-muted); font-size: .68rem; font-weight: 550; }
    .sales-dashboard .sales-sales-comparison-table th,
    .sales-dashboard .sales-sales-comparison-table td { white-space: nowrap; }
    .sales-dashboard .sales-sales-comparison-table th { padding: .72rem .72rem .64rem; font-size: .61rem; vertical-align: bottom; }
    .sales-dashboard .sales-sales-comparison-table td { padding: .68rem .72rem; font-size: .72rem; vertical-align: middle; }
    .sales-dashboard .sales-sales-comparison-table .sales-period-current { background: color-mix(in srgb, var(--sales-accent-soft) 32%, var(--sales-card) 68%); }
    .sales-dashboard .sales-sales-comparison-table .sales-period-current .sales-period-label { color: var(--sales-accent); }
    .sales-dashboard .sales-period-label { display: block; color: var(--sales-ink); font-size: .65rem; font-weight: 800; letter-spacing: .035em; line-height: 1.1; text-transform: uppercase; }
    .sales-dashboard .sales-period-range { display: block; margin-top: .25rem; color: var(--sales-muted); font-size: .56rem; font-weight: 600; line-height: 1.15; }
    .sales-dashboard .sales-comparison-metric { color: var(--sales-ink); font-weight: 750; }
    .sales-dashboard .sales-comparison-cell { display: inline-flex; flex-direction: column; align-items: flex-end; justify-content: center; gap: .15rem; }
    .sales-dashboard .sales-comparison-value-row { display: inline-flex; align-items: baseline; justify-content: flex-end; gap: .34rem; font-variant-numeric: tabular-nums; }
    .sales-dashboard .sales-comparison-delta { display: inline-flex; align-items: center; justify-content: center; min-width: 3.25rem; gap: .08rem; padding: .11rem .2rem; border: 1px solid transparent; border-radius: 4px; font-size: .5rem; font-weight: 750; line-height: 1; text-align: center; white-space: nowrap; }
    .sales-dashboard .sales-comparison-delta i { font-size: .55rem; }
    .sales-dashboard .sales-comparison-delta.is-up { color: var(--success, #16a34a); background: color-mix(in srgb, var(--success, #16a34a) 10%, var(--sales-card) 90%); border-color: color-mix(in srgb, var(--success, #16a34a) 18%, var(--sales-line) 82%); }
    .sales-dashboard .sales-comparison-delta.is-down { color: var(--danger, #dc2626); background: color-mix(in srgb, var(--danger, #dc2626) 9%, var(--sales-card) 91%); border-color: color-mix(in srgb, var(--danger, #dc2626) 18%, var(--sales-line) 82%); }
    .sales-dashboard .sales-comparison-delta.is-neutral { color: var(--sales-muted); background: color-mix(in srgb, var(--sales-muted) 8%, var(--sales-card) 92%); border-color: color-mix(in srgb, var(--sales-muted) 15%, var(--sales-line) 85%); }
    .sales-dashboard .sales-comparison-difference { font-size: .47rem; font-weight: 650; line-height: 1; opacity: .68; white-space: nowrap; }
    .sales-dashboard .sales-comparison-difference.is-up { color: var(--success, #16a34a); }
    .sales-dashboard .sales-comparison-difference.is-down { color: var(--danger, #dc2626); }
    .sales-dashboard .sales-comparison-difference.is-neutral { color: var(--sales-muted); }
    .sales-dashboard .sales-sales-comparison-row { cursor: pointer; }
    .sales-dashboard .sales-sales-comparison-row > td { transition: background-color .16s ease; }
    .sales-dashboard .sales-sales-comparison-row:hover > td,
    .sales-dashboard .sales-sales-comparison-row.is-expanded > td { background: color-mix(in srgb, var(--sales-accent-soft) 25%, var(--sales-card) 75%); }
    .sales-dashboard .sales-comparison-metric-toggle { display: inline-flex; max-width: 100%; align-items: center; gap: .42rem; border: 0; border-radius: .4rem; background: transparent; color: inherit; padding: .15rem .25rem; font: inherit; text-align: left; }
    .sales-dashboard .sales-comparison-metric-toggle:hover { background: color-mix(in srgb, var(--sales-accent-soft) 72%, transparent); color: var(--sales-accent); }
    .sales-dashboard .sales-comparison-metric-toggle:focus-visible { outline: 2px solid color-mix(in srgb, var(--sales-accent) 52%, transparent); outline-offset: 2px; }
    .sales-dashboard .sales-comparison-metric-toggle i { flex: 0 0 auto; color: var(--sales-accent); font-size: .68rem; transition: transform .16s ease; }
    .sales-dashboard .sales-comparison-metric-toggle[aria-expanded="true"] i { transform: rotate(90deg); }
    .sales-dashboard .sales-sales-comparison-detail > td { padding: 0 !important; border: 0 !important; background: color-mix(in srgb, var(--sales-accent-soft) 14%, var(--sales-card) 86%); }
    .sales-dashboard .sales-comparison-chart-detail { padding: .7rem .85rem .75rem; }
    .sales-dashboard .sales-comparison-chart-title { display: flex; align-items: center; justify-content: space-between; gap: .6rem; margin-bottom: .25rem; color: var(--sales-muted); font-size: .61rem; font-weight: 750; }
    .sales-dashboard .sales-comparison-chart-title strong { color: var(--sales-ink); font-weight: 850; }
    .sales-dashboard .sales-comparison-chart { min-height: 150px; border-radius: .55rem; background: color-mix(in srgb, var(--sales-card) 92%, var(--sales-soft) 8%); }
    .sales-dashboard .sales-comparison-chart svg { display: block; width: 100%; height: 150px; }
    .sales-dashboard .sales-comparison-chart .comparison-chart-grid { stroke: color-mix(in srgb, var(--sales-line) 74%, transparent); stroke-width: 1; vector-effect: non-scaling-stroke; }
    .sales-dashboard .sales-comparison-chart .comparison-chart-label { fill: var(--sales-muted); font-size: 10px; font-weight: 700; }
    .sales-dashboard .sales-comparison-chart .comparison-chart-line { fill: none; stroke: var(--sales-accent); stroke-linecap: round; stroke-linejoin: round; stroke-width: 2.5; vector-effect: non-scaling-stroke; }
    .sales-dashboard .sales-comparison-chart .comparison-chart-point { fill: var(--sales-accent); stroke: var(--sales-card); stroke-width: 2; vector-effect: non-scaling-stroke; }
    .sales-dashboard .sales-table-section-row td {
        padding: .58rem .75rem .38rem;
        border-bottom: 0;
        background: color-mix(in srgb, var(--sales-soft) 62%, var(--sales-card) 38%);
        color: var(--sales-accent);
        font-size: .6rem;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
    }
    .sales-dashboard .sales-category-table-wrap { width: 100%; overflow: hidden; }
    .sales-dashboard .sales-category-summary-table { width: 100%; min-width: 0; table-layout: fixed; }
    .sales-dashboard .sales-category-summary-table th,
    .sales-dashboard .sales-category-summary-table td { white-space: normal; overflow-wrap: anywhere; }
    .sales-dashboard .sales-category-summary-table thead th { overflow-wrap: normal; word-break: normal; hyphens: none; }
    .sales-dashboard .sales-category-summary-table th { padding: .65rem .55rem; color: var(--sales-muted); font-size: .61rem; font-weight: 800; letter-spacing: .025em; vertical-align: middle; }
    .sales-dashboard .sales-category-summary-table td { padding: .62rem .55rem; font-size: .7rem; vertical-align: middle; }
    .sales-dashboard .sales-category-summary-table th,
    .sales-dashboard .sales-category-summary-table td { text-align: start; }
    .sales-dashboard .sales-category-summary-table thead tr:first-child th {
        background: var(--sales-soft);
        color: var(--sales-ink);
        font-size: .58rem;
        font-weight: 850;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .sales-dashboard .sales-category-summary-table thead tr:first-child th.sales-category-table-group-head {
        border-left: 1px solid var(--sales-line);
        border-right: 1px solid var(--sales-line);
        text-align: center;
    }
    .sales-dashboard .sales-category-summary-table thead tr:nth-child(2) th {
        color: var(--sales-muted);
        font-size: .55rem;
        font-weight: 750;
        line-height: 1.15;
    }
    .sales-dashboard .sales-category-summary-table thead th:nth-child(n+3),
    .sales-dashboard .sales-category-summary-table tbody td:nth-child(n+3) { text-align: end; }
    .sales-dashboard .sales-category-summary-table thead tr:nth-child(2) th { text-align: center; }
    .sales-dashboard .sales-category-summary-table tbody td:nth-child(n+3) { color: var(--sales-ink); font-weight: 500; font-variant-numeric: tabular-nums; }
    .sales-dashboard .sales-category-summary-table tbody td:first-child { color: var(--sales-muted); font-variant-numeric: tabular-nums; text-align: start; }
    .sales-dashboard .sales-category-summary-table .sales-category-name { overflow: hidden; text-overflow: ellipsis; }
    .sales-dashboard .sales-category-summary-table > tbody > tr.sales-category-comparison-row { cursor: pointer; transition: background-color .16s ease, box-shadow .16s ease; }
    .sales-dashboard .sales-category-summary-table > tbody > tr.sales-category-comparison-row:hover > td { background-color: var(--bs-table-hover-bg) !important; }
    .sales-dashboard .sales-category-summary-table > tbody > tr.sales-category-comparison-row.is-expanded > td,
    .sales-dashboard .sales-category-summary-table > tbody > tr.sales-category-comparison-row:focus-visible > td { background-color: color-mix(in srgb, var(--sales-accent-soft) 55%, var(--sales-card) 45%) !important; }
    .sales-dashboard .sales-category-summary-table > tbody > tr.sales-category-comparison-detail:hover > td { background: color-mix(in srgb, var(--sales-accent-soft) 14%, var(--sales-card) 86%) !important; }
    .sales-dashboard .sales-category-comparison-toggle { display: flex; width: 100%; min-height: 2.2rem; align-items: center; gap: .45rem; border: 0; border-radius: .45rem; background: transparent; color: inherit; padding: .35rem .45rem; text-align: left; transition: background-color .16s ease, color .16s ease; }
    .sales-dashboard .sales-category-comparison-toggle span { min-width: 0; overflow: hidden; text-overflow: ellipsis; }
    .sales-dashboard .sales-category-comparison-toggle:hover { background: color-mix(in srgb, var(--sales-accent-soft) 70%, transparent); color: var(--accent, #2563eb); }
    .sales-dashboard .sales-category-comparison-toggle:focus-visible { outline: 2px solid color-mix(in srgb, var(--sales-accent) 55%, transparent); outline-offset: 2px; }
    .sales-dashboard .sales-category-comparison-toggle i { flex: 0 0 1rem; width: 1rem; color: var(--accent, #2563eb); transition: transform .18s ease; }
    .sales-dashboard .sales-category-comparison-toggle[aria-expanded="true"] i { transform: rotate(90deg); }
    .sales-dashboard .sales-category-comparison-detail > td { padding: 0 !important; background: color-mix(in srgb, var(--sales-accent-soft) 14%, var(--sales-card) 86%); }
    .sales-dashboard .sales-category-comparison-detail-card { padding: .85rem 1rem 1rem; border-top: 1px solid color-mix(in srgb, var(--sales-accent) 18%, var(--sales-line) 82%); }
    .sales-dashboard .sales-category-detail-header { flex-wrap: nowrap !important; }
    .sales-dashboard .sales-category-detail-header > div:first-child { min-width: 0; }
    .sales-dashboard .sales-category-detail-header .sales-section-title { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sales-dashboard .sales-category-detail-badge { max-width: 42%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sales-dashboard .sales-category-comparison-detail-card .sales-kicker { font-size: .6rem; }
    .sales-dashboard .sales-category-comparison-detail-card .sales-section-title { font-size: .9rem; }
    .sales-dashboard .sales-category-comparison-detail-card .sales-product-comparison-table { min-width: 760px; }
    .sales-dashboard .sales-category-comparison-detail-card .sales-product-comparison-table th,
    .sales-dashboard .sales-category-comparison-detail-card .sales-product-comparison-table td { white-space: nowrap; }
    .sales-dashboard .sales-product-analysis-matrix-wrap { padding: 0 1.15rem 1.15rem; }
    .sales-dashboard .sales-product-analysis-matrix { width: 100%; table-layout: fixed; }
    .sales-dashboard .sales-product-analysis-matrix th,
    .sales-dashboard .sales-product-analysis-matrix td { padding: .58rem .62rem; }
    .sales-dashboard .sales-product-analysis-matrix th { font-size: .61rem; }
    .sales-dashboard .sales-product-analysis-matrix td { font-size: .72rem; }
    .sales-dashboard .sales-product-analysis-matrix .analysis-status { font-weight: 800; }
    .sales-dashboard .sales-product-analysis-matrix .analysis-status-badge,
    .sales-dashboard .sales-product-analysis-legend .analysis-status-badge {
        display: inline-flex;
        align-items: center;
        min-width: 4.8rem;
        justify-content: center;
        padding: .23rem .45rem;
        border-radius: 999px;
        font-size: .6rem;
        font-weight: 800;
        letter-spacing: .02em;
        text-transform: uppercase;
    }
    .sales-dashboard .analysis-status-badge--scale { color: #166534; background: #dcfce7; }
    .sales-dashboard .analysis-status-badge--protect { color: #1d4ed8; background: #dbeafe; }
    .sales-dashboard .analysis-status-badge--grow { color: #a16207; background: #fef3c7; }
    .sales-dashboard .analysis-status-badge--review { color: #b91c1c; background: #fee2e2; }
    .sales-dashboard .sales-product-analysis-matrix .analysis-number { color: var(--sales-ink); font-weight: 800; font-variant-numeric: tabular-nums; }
    .sales-dashboard .sales-product-analysis-matrix .analysis-share { color: var(--sales-muted); font-size: .65rem; font-variant-numeric: tabular-nums; }
    .sales-dashboard .sales-product-analysis-matrix tr[data-sales-analysis-row] { cursor: pointer; transition: background-color .16s ease; }
    .sales-dashboard .sales-product-analysis-matrix tr[data-sales-analysis-row]:hover > td,
    .sales-dashboard .sales-product-analysis-matrix tr[data-sales-analysis-row].is-expanded > td { background-color: var(--bs-table-hover-bg) !important; }
    .sales-dashboard .sales-product-analysis-matrix-toggle { display: inline-flex; align-items: center; gap: .35rem; border: 0; background: transparent; color: inherit; padding: .2rem .3rem; text-align: left; }
    .sales-dashboard .sales-product-analysis-matrix-toggle i { color: var(--sales-accent); transition: transform .18s ease; }
    .sales-dashboard .sales-product-analysis-matrix-toggle[aria-expanded="true"] i { transform: rotate(90deg); }
    .sales-dashboard .sales-product-analysis-matrix-toggle:focus-visible { outline: 2px solid color-mix(in srgb, var(--sales-accent) 55%, transparent); outline-offset: 2px; border-radius: 5px; }
    .sales-dashboard .sales-product-analysis-detail > td { padding: 0 !important; background: color-mix(in srgb, var(--sales-accent-soft) 12%, var(--sales-card) 88%); }
    .sales-dashboard .sales-product-analysis-detail-card { padding: .8rem 1rem 1rem; border-top: 1px solid color-mix(in srgb, var(--sales-accent) 18%, var(--sales-line) 82%); }
    .sales-dashboard .sales-product-analysis-detail-table { min-width: 1120px; }
    .sales-dashboard .sales-product-analysis-detail-table th { font-size: .58rem; }
    .sales-dashboard .sales-product-analysis-detail-table td { font-size: .67rem; }
    .sales-dashboard .sales-product-analysis-detail-title { display: block; max-width: 220px; overflow: hidden; color: var(--sales-ink); font-weight: 750; text-overflow: ellipsis; white-space: nowrap; }
    .sales-dashboard .sales-product-analysis-detail-code { display: inline-block; max-width: 150px; overflow: hidden; padding: .18rem .35rem; border: 1px solid color-mix(in srgb, var(--sales-line) 90%, transparent); border-radius: 5px; background: var(--sales-card); color: var(--sales-ink); font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: .58rem; font-weight: 750; text-overflow: ellipsis; white-space: nowrap; }
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
        .sales-dashboard .sales-trend-body { padding-inline: .65rem; }
        .sales-dashboard .sales-trend-layout { grid-template-columns: 1fr; }
        .sales-dashboard .sales-trend-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sales-dashboard .sales-trend-toolbar { align-items: flex-start; flex-direction: column; }
        .sales-dashboard .sales-trend-select { width: 100%; }
        .sales-dashboard .sales-trend-header-meta { align-items: flex-start; flex-direction: column; }
        .sales-dashboard .sales-promotion-table {
            min-width: 0;
            width: 100%;
            table-layout: fixed;
        }
        .sales-dashboard .sales-promotion-table th {
            padding: .38rem .22rem;
            font-size: .58rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: clip;
        }
        .sales-dashboard .sales-promotion-table td {
            padding: .38rem .22rem;
            font-size: .58rem;
            white-space: normal;
            overflow-wrap: anywhere;
        }
        .sales-dashboard .sales-promotion-table th:first-child,
        .sales-dashboard .sales-promotion-table td:first-child {
            width: 10%;
        }
        .sales-dashboard .sales-promotion-table td .small {
            font-size: .48rem;
            white-space: nowrap;
        }
        .sales-dashboard .sales-order-table { min-width: 0; }
        .sales-dashboard .sales-category-table-wrap { overflow: visible; }
        .sales-dashboard .sales-category-summary-table { display: block; min-width: 0 !important; }
        .sales-dashboard .sales-category-summary-table thead { display: none; }
        .sales-dashboard .sales-category-summary-table tbody { display: block; }
        .sales-dashboard .sales-category-summary-table tr.sales-category-comparison-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .35rem .8rem; margin-bottom: .55rem; padding: .7rem .75rem; border: 1px solid var(--sales-line); border-radius: .7rem; background: var(--sales-card); }
        .sales-dashboard .sales-category-summary-table tr.sales-category-comparison-row > td { display: flex; min-width: 0; flex-direction: column; align-items: flex-start; padding: .25rem 0 !important; border: 0; text-align: left !important; }
        .sales-dashboard .sales-category-summary-table tr.sales-category-comparison-row > td:first-child { display: none; }
        .sales-dashboard .sales-category-summary-table tr.sales-category-comparison-row > td[data-label]::before { content: attr(data-label); margin-bottom: .2rem; color: var(--sales-muted); font-size: .58rem; font-weight: 750; letter-spacing: .02em; }
        .sales-dashboard .sales-category-summary-table tr.sales-category-comparison-row > td:nth-child(2) { grid-column: 1 / -1; }
        .sales-dashboard .sales-category-summary-table tr.sales-category-comparison-detail { display: block; }
        .sales-dashboard .sales-category-summary-table tr.sales-category-comparison-detail[hidden] { display: none !important; }
        .sales-dashboard .sales-category-summary-table tr.sales-category-comparison-detail > td { display: block; width: 100%; }
        .sales-dashboard .sales-kpi { min-height: 142px; }
        .sales-dashboard .sales-kpi-value { font-size: 1.2rem; }
        .sales-dashboard .sales-period-filter { order: 1; width: 100%; margin-left: 0; }
        .sales-dashboard .sales-filter-scope { order: 2; width: 100%; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); align-items: end; }
        .sales-dashboard .sales-filter-platform,
        .sales-dashboard .sales-filter-store,
        .sales-dashboard .sales-filter-comparison { width: auto; }
        .sales-dashboard .sales-nav-shell { align-items: stretch; flex-direction: column; }
        .sales-dashboard .sales-nav-comparison { margin-left: .25rem; }
        .sales-dashboard .sales-nav-comparison-label { padding-left: .35rem; }
        .sales-dashboard .sales-product-analysis-subsection { align-items: flex-start; flex-direction: column; }
        .sales-dashboard .sales-product-analysis-legend { justify-content: flex-start; }
        .sales-dashboard .sales-product-analysis-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); padding-inline: .75rem; }
        .sales-dashboard .sales-product-analysis-tables,
        .sales-dashboard .sales-product-analysis-matrix-wrap { padding-inline: .75rem; overflow-x: auto; }
        .sales-dashboard .sales-product-analysis-table,
        .sales-dashboard .sales-product-analysis-matrix { min-width: 650px; }
        .sales-dashboard .sales-product-analysis-matrix { display: block; min-width: 0 !important; }
        .sales-dashboard .sales-product-analysis-matrix thead { display: none; }
        .sales-dashboard .sales-product-analysis-matrix tbody { display: block; }
        .sales-dashboard .sales-product-analysis-matrix tr[data-sales-analysis-row] {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .35rem .8rem;
            margin-bottom: .55rem;
            padding: .7rem .75rem;
            border: 1px solid var(--sales-line);
            border-radius: .7rem;
            background: var(--sales-card);
        }
        .sales-dashboard .sales-product-analysis-matrix tr[data-sales-analysis-row] > td {
            display: flex;
            min-width: 0;
            flex-direction: column;
            align-items: flex-start;
            padding: .25rem 0 !important;
            border: 0;
            text-align: left !important;
        }
        .sales-dashboard .sales-product-analysis-matrix tr[data-sales-analysis-row] > td:first-child {
            grid-column: 1 / -1;
        }
        .sales-dashboard .sales-product-analysis-matrix tr[data-sales-analysis-row] > td[data-label]::before {
            content: attr(data-label);
            margin-bottom: .2rem;
            color: var(--sales-muted);
            font-size: .58rem;
            font-weight: 750;
            letter-spacing: .02em;
        }
        .sales-dashboard .sales-product-analysis-matrix tr.sales-product-analysis-detail { display: block; }
        .sales-dashboard .sales-product-analysis-matrix tr.sales-product-analysis-detail[hidden] { display: none !important; }
        .sales-dashboard .sales-product-analysis-matrix tr.sales-product-analysis-detail > td { display: block; width: 100%; }
    }
</style>
@endpush

@section('content')
@php
    $fmt = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $fmtHpp = function ($value) {
        $formatted = number_format((float) $value, 2, ',', '.');
        $formatted = rtrim(rtrim($formatted, '0'), ',');

        return 'Rp '.$formatted;
    };
    $dateLabel = fn ($date) => \Carbon\Carbon::parse($date)->format('d M Y');
    $dateRangeLabel = fn ($from, $to) => $dateLabel($from).' – '.$dateLabel($to);
    $pct = fn ($value, $total) => $total > 0 ? number_format(((float) $value / (float) $total) * 100, 1, ',', '.') : '0,0';
    $salesTabs = ['sales', 'products', 'payments', 'promotions', 'shipping', 'income', 'orders'];
    $activeTab = in_array(request('tab'), $salesTabs, true) ? request('tab') : 'sales';
    $comparisonMode = $filters['comparison_mode'] ?? 'month';
    $comparisonModeLabel = $comparisonMode === 'month' ? 'Bulan lalu' : 'Periode lalu';
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
    if ($filters['platform']) $detailQuery['platform'] = $filters['platform'];
    if ($filters['store_id']) $detailQuery['store_id'] = $filters['store_id'];
    $platformCodes = $filters['platform_codes'] ?? ($filters['platform'] ? [$filters['platform']] : []);
    $visibleStores = $filters['platform']
        ? $stores->filter(fn ($store) => in_array(strtoupper((string) ($store->channel->code ?? '')), $platformCodes, true))
        : $stores;
    $canImportMarketplace = auth()->check() && auth()->user()->canAccessModule('imports');
    $importOrderQuery = $filters['store_id'] ? ['store_id' => $filters['store_id']] : [];
    $paymentDetailQuery = ['tab' => 'payments'];
    if ($filters['platform']) $paymentDetailQuery['platform'] = $filters['platform'];
    if ($filters['store_id']) $paymentDetailQuery['store_id'] = $filters['store_id'];
    if (!empty($filters['dummy'])) $paymentDetailQuery['dummy'] = 1;
    $shippingDetailQuery = ['tab' => 'shipping'];
    if ($filters['platform']) $shippingDetailQuery['platform'] = $filters['platform'];
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
    $marketplaceProductCount = function ($rows) {
        return collect($rows)
            ->map(function ($product) {
                $externalItemId = trim((string) ($product->external_item_id ?? ''));
                $marketplaceName = trim((string) ($product->marketplace_name ?? $product->name ?? ''));

                return $externalItemId !== ''
                    ? 'external:'.$externalItemId
                    : ($marketplaceName !== '' ? 'name:'.$marketplaceName : null);
            })
            ->filter()
            ->unique()
            ->count();
    };
    $marketplaceVariantCount = function ($rows) {
        return collect($rows)
            ->map(function ($product) {
                $externalItemId = trim((string) ($product->external_item_id ?? ''));
                $internalItemId = (int) ($product->internal_item_id ?? 0);
                $sku = trim((string) ($product->sku ?? ''));
                $marketplaceName = trim((string) ($product->marketplace_name ?? $product->name ?? ''));

                return $internalItemId > 0
                    ? 'marketplace:'.$externalItemId.'|internal:'.$internalItemId
                    : 'marketplace:'.$externalItemId.'|sku:'.$sku.'|name:'.$marketplaceName;
            })
            ->filter(fn ($key) => trim((string) $key) !== 'marketplace:|sku:|name:')
            ->unique()
            ->count();
    };
    $soldVariantCount = function ($rows) {
        return collect($rows)
            ->map(function ($product) {
                $internalItemId = (int) ($product->internal_item_id ?? 0);
                if ($internalItemId > 0) {
                    return 'internal:'.$internalItemId;
                }

                $externalItemId = trim((string) ($product->external_item_id ?? ''));
                $sku = trim((string) ($product->sku ?? ''));
                $marketplaceName = trim((string) ($product->marketplace_name ?? $product->name ?? ''));

                return 'marketplace:'.$externalItemId.'|sku:'.$sku.'|name:'.$marketplaceName;
            })
            ->filter(fn ($key) => trim((string) $key) !== 'marketplace:|sku:|name:')
            ->unique()
            ->count();
    };
    $topProductCount = $marketplaceProductCount($products);
    $productAnalysisProducts = $products->values();
    $productAnalysisNetSales = (float) $productAnalysisProducts->sum('net_sales');
    $productAnalysisBuyerPayment = (float) $productAnalysisProducts->sum('buyer_payment');
    $productAnalysisAdProducts = $productAnalysisProducts->filter(fn ($product) => $product->ad_spend_matched ?? false);
    $productAnalysisAdSpend = (float) $productAnalysisAdProducts->sum('ad_spend');
    $productAnalysisAdSales = (float) $productAnalysisAdProducts->sum('ad_sales');
    $productAnalysisAdConversions = (int) $productAnalysisAdProducts->sum('ad_conversions');
    $productAnalysisMappedCount = $productAnalysisProducts->filter(fn ($product) => (int) ($product->internal_item_id ?? 0) > 0)->count();
    $productAnalysisCostedProducts = $productAnalysisProducts->filter(fn ($product) => $product->gross_profit !== null);
    // Gunakan sumber KPI harian yang sama dengan tab Penjualan agar
    // payout, COGS, iklan, dan laba bersih selalu konsisten lintas section.
    $productAnalysisHpp = (float) collect($daily)->sum('cogs');
    $productAnalysisEstimatedPayout = (float) collect($daily)->sum('estimated_payout');
    $productAnalysisGrossProfitPayout = (float) collect($daily)->sum('gross_profit');
    $productAnalysisAdSpend = (float) collect($daily)->sum('ad_spend');
    $productAnalysisNetProfit = (float) collect($daily)->sum('net_profit');
    $productAnalysisGrossMarginPayout = $productAnalysisEstimatedPayout > 0
        ? ($productAnalysisGrossProfitPayout / $productAnalysisEstimatedPayout) * 100
        : 0;
    $productAnalysisNetMargin = $productAnalysisEstimatedPayout > 0
        ? ($productAnalysisNetProfit / $productAnalysisEstimatedPayout) * 100
        : 0;
    $productAnalysisHppCoverage = $productAnalysisProducts->count() > 0
        ? ($productAnalysisCostedProducts->count() / $productAnalysisProducts->count()) * 100
        : 0;
    $productAnalysisContributionProfit = (float) $productAnalysisCostedProducts->sum('contribution_profit');
    $productAnalysisContributionMargin = $productAnalysisNetSales > 0 ? ($productAnalysisContributionProfit / $productAnalysisNetSales) * 100 : 0;
    $productAnalysisAdSalesCoverage = $productAnalysisNetSales > 0 ? ($productAnalysisAdSales / $productAnalysisNetSales) * 100 : 0;
    $productAnalysisCategoryCount = $productAnalysisProducts->map(fn ($product) => trim((string) ($product->category_name ?? '')) ?: 'Tanpa kategori')->unique()->count();
    $productAnalysisTop10Sales = (float) $productAnalysisProducts->sortByDesc('net_sales')->take(10)->sum('net_sales');
    $productAnalysisSalesMedian = (float) ($productAnalysisProducts->pluck('net_sales')->median() ?? 0);
    $productAnalysisMarginValues = $productAnalysisProducts
        ->filter(fn ($product) => $product->contribution_margin !== null)
        ->map(fn ($product) => (float) $product->contribution_margin)
        ->values();
    $productAnalysisContributionMarginMedian = $productAnalysisMarginValues->isNotEmpty()
        ? (float) ($productAnalysisMarginValues->median() ?? 0)
        : 0;
    $productAnalysisMatrix = collect([
        ['key' => 'scale', 'label' => 'Scale', 'class' => 'scale'],
        ['key' => 'protect', 'label' => 'Protect', 'class' => 'protect'],
        ['key' => 'grow', 'label' => 'Grow', 'class' => 'grow'],
        ['key' => 'review', 'label' => 'Review', 'class' => 'review'],
    ])->mapWithKeys(fn ($row) => [$row['key'] => $row + ['products' => collect()] ]);
    foreach ($productAnalysisProducts as $product) {
        $productSales = (float) ($product->net_sales ?? 0);
        $highSales = $productSales >= $productAnalysisSalesMedian;
        $highContribution = $product->contribution_margin !== null
            && $productAnalysisContributionMarginMedian > 0
            && (float) $product->contribution_margin >= $productAnalysisContributionMarginMedian;
        $matrixKey = $highSales
            ? ($highContribution ? 'scale' : 'protect')
            : ($highContribution ? 'grow' : 'review');
        $productAnalysisMatrix[$matrixKey]['products']->push($product);
    }
    $productAnalysisMatrixRows = $productAnalysisMatrix->map(function ($row) use ($productAnalysisNetSales, $productAnalysisAdSales, $marketplaceProductCount) {
        $products = $row['products'];
        $sales = (float) $products->sum('net_sales');
        $adSpend = (float) $products->filter(fn ($product) => $product->ad_spend_matched ?? false)->sum('ad_spend');
        $adSales = (float) $products->filter(fn ($product) => $product->ad_spend_matched ?? false)->sum('ad_sales');
        $contributionProfit = (float) $products->filter(fn ($product) => $product->contribution_profit !== null)->sum('contribution_profit');
        $soldVariantKeys = $products
            ->map(function ($product) {
                $internalItemId = (int) ($product->internal_item_id ?? 0);
                if ($internalItemId > 0) {
                    return 'internal:'.$internalItemId;
                }

                $externalItemId = trim((string) ($product->external_item_id ?? ''));
                $marketplaceName = trim((string) ($product->marketplace_name ?? $product->name ?? ''));

                return $externalItemId !== ''
                    ? 'external:'.$externalItemId
                    : ($marketplaceName !== '' ? 'name:'.$marketplaceName : null);
            })
            ->filter()
            ->unique()
            ->values();
        $row['count'] = $marketplaceProductCount($products);
        $row['variants_sold'] = $soldVariantKeys->count();
        $row['sales'] = $sales;
        $row['sales_share'] = $productAnalysisNetSales > 0 ? ($sales / $productAnalysisNetSales) * 100 : 0;
        $row['ad_spend'] = $adSpend;
        $row['ad_sales'] = $adSales;
        $row['roas'] = $adSpend > 0 ? $adSales / $adSpend : 0;
        $row['contribution_profit'] = $contributionProfit;
        $row['contribution_margin'] = $sales > 0 ? ($contributionProfit / $sales) * 100 : null;
        $row['ad_share'] = $productAnalysisAdSales > 0 ? ($adSales / $productAnalysisAdSales) * 100 : 0;
        $row['products'] = $products->sortByDesc('net_sales')->values();

        return $row;
    })->values();
    $incomeSettlementRate = $incomeSummary['orders'] > 0 ? ($incomeSummary['settled_orders'] / $incomeSummary['orders']) * 100 : 0;
    $promotionRate = $summary['subtotal'] > 0 ? ($summary['promotion_total'] / $summary['subtotal']) * 100 : 0;
    $adSpendDaily = collect($adSpendDaily ?? []);
    $adOrdersDaily = collect($adOrdersDaily ?? []);
    $adSpendTotal = (float) ($adSpendTotal ?? $adSpendDaily->sum());
    $paymentDailyByDay = collect($paymentDaily)->keyBy(fn ($row) => (string) data_get($row, 'day'));
    $comparisonMonthData = $comparisonMonth['data'] ?? null;
    $comparisonMonthPreviousData = $comparisonMonthPrevious['data'] ?? null;
    $comparisonMonthPreviousTwoData = $comparisonMonthPreviousTwo['data'] ?? null;
    $comparisonPeriodData = $comparisonPeriod['data'] ?? null;
    $comparisonPeriodPreviousData = $comparisonPeriodPrevious['data'] ?? null;
    $comparisonPeriodPreviousTwoData = $comparisonPeriodPreviousTwo['data'] ?? null;
    $activeComparison = $comparisonMode === 'month' ? $comparisonMonth : $comparisonPeriod;
    $previousMonthSummary = data_get($comparisonMonthData, 'summary', []);
    $previousPeriodSummary = data_get($comparisonPeriodData, 'summary', []);
    $salesKpiMetrics = function ($rows) {
        $rows = collect($rows);

        return [
            'gross_sales' => (float) $rows->sum(fn ($row) => (float) data_get($row, 'subtotal', 0)),
            'net_sales' => (float) $rows->sum(fn ($row) => (float) data_get($row, 'net_total', 0)),
            'orders' => (int) $rows->sum(fn ($row) => (int) data_get($row, 'orders', 0)),
            'qty' => (int) $rows->sum(fn ($row) => (int) data_get($row, 'qty', 0)),
            'payout' => (float) $rows->sum(fn ($row) => (float) data_get($row, 'estimated_payout', 0)),
            'cogs' => (float) $rows->sum(fn ($row) => (float) data_get($row, 'cogs', 0)),
            'gross_profit' => (float) $rows->sum(fn ($row) => (float) data_get($row, 'gross_profit', 0)),
            'ad_spend' => (float) $rows->sum(fn ($row) => (float) data_get($row, 'ad_spend', 0)),
            'net_profit' => (float) $rows->sum(fn ($row) => (float) data_get($row, 'net_profit', 0)),
        ];
    };
    $currentSalesKpi = $salesKpiMetrics($daily);
    $previousMonthSalesKpi = $salesKpiMetrics(data_get($comparisonMonthData, 'daily', []));
    $previousPeriodSalesKpi = $salesKpiMetrics(data_get($comparisonPeriodData, 'daily', []));
    $trendRow = function ($row) {
        $day = (string) data_get($row, 'day', '');

        return [
            'day' => $day,
            'label' => $day !== '' ? \Carbon\Carbon::parse($day)->format('d M') : '—',
            'net_sales' => (float) data_get($row, 'net_total', 0),
            'estimated_payout' => (float) data_get($row, 'estimated_payout', 0),
            'net_profit' => (float) data_get($row, 'net_profit', 0),
            'ad_spend' => (float) data_get($row, 'ad_spend', 0),
        ];
    };
    $trendCurrentRows = collect($daily)->sortBy('day')->map($trendRow)->values();
    $trendPreviousRows = collect(data_get($activeComparison, 'data.daily', []))->sortBy('day')->map($trendRow)->values();
    $trendPeak = $trendCurrentRows->sortByDesc(fn ($row) => $row['net_sales'])->first();
    $trendLow = $trendCurrentRows->sortBy(fn ($row) => $row['net_sales'])->first();
    $trendRisk = $trendCurrentRows
        ->filter(fn ($row) => $row['net_profit'] < 0)
        ->sortByDesc(fn ($row) => $row['ad_spend'])
        ->first();
    $trendRiskLabel = $trendRisk ? 'Risiko laba' : 'Iklan tertinggi';
    $trendRisk ??= $trendCurrentRows->sortByDesc(fn ($row) => $row['ad_spend'])->first();
    $trendComparisonLabel = $comparisonMode === 'month' ? 'Bulan lalu' : 'Periode lalu';
    $trendDelta = function ($current, $previous) {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous === 0.0) {
            return [
                'label' => $current === 0.0 ? '0,0%' : 'Baru',
                'tone' => $current > 0 ? 'good' : 'neutral',
            ];
        }

        $delta = (($current - $previous) / abs($previous)) * 100;

        return [
            'label' => ($delta >= 0 ? '↑ ' : '↓ ').number_format(abs($delta), 1, ',', '.').'%',
            'tone' => $delta >= 0 ? 'good' : 'bad',
        ];
    };
    $trendCurrentTotals = [
        'net_sales' => (float) $trendCurrentRows->sum('net_sales'),
        'estimated_payout' => (float) $trendCurrentRows->sum('estimated_payout'),
        'net_profit' => (float) $trendCurrentRows->sum('net_profit'),
        'ad_spend' => (float) $trendCurrentRows->sum('ad_spend'),
    ];
    $trendPreviousTotals = [
        'net_sales' => (float) $trendPreviousRows->sum('net_sales'),
        'estimated_payout' => (float) $trendPreviousRows->sum('estimated_payout'),
        'net_profit' => (float) $trendPreviousRows->sum('net_profit'),
        'ad_spend' => (float) $trendPreviousRows->sum('ad_spend'),
    ];
    $trendSummary = collect([
        ['label' => 'Net Sales', 'key' => 'net_sales', 'icon' => 'bi-graph-up-arrow'],
        ['label' => 'Est Penghasilan', 'key' => 'estimated_payout', 'icon' => 'bi-wallet2'],
        ['label' => 'Laba Bersih', 'key' => 'net_profit', 'icon' => 'bi-bar-chart-line'],
        ['label' => 'Iklan', 'key' => 'ad_spend', 'icon' => 'bi-megaphone'],
    ])->map(function ($item) use ($trendCurrentTotals, $trendPreviousTotals, $trendDelta, $fmt) {
        $delta = $trendDelta($trendCurrentTotals[$item['key']], $trendPreviousTotals[$item['key']]);

        return $item + [
            'value' => $fmt($trendCurrentTotals[$item['key']]),
            'delta' => $delta['label'],
            'tone' => $delta['tone'],
        ];
    });
    $trendNegativeDays = $trendCurrentRows->filter(fn ($row) => $row['net_profit'] < 0)->count();
    $trendAverageNetSales = $trendCurrentRows->count() > 0 ? $trendCurrentTotals['net_sales'] / $trendCurrentRows->count() : 0;
    $trendBestProfit = $trendCurrentRows->sortByDesc(fn ($row) => $row['net_profit'])->first();
    $trendWorstProfit = $trendCurrentRows->sortBy(fn ($row) => $row['net_profit'])->first();
    $trendPayload = [
        'current' => $trendCurrentRows->all(),
        'previous' => $trendPreviousRows->all(),
        'comparison_label' => $trendComparisonLabel,
    ];
    $previousMonthPaymentSummary = data_get($comparisonMonthData, 'paymentSummary', []);
    $previousPeriodPaymentSummary = data_get($comparisonPeriodData, 'paymentSummary', []);
    $previousMonthPaymentDaily = collect(data_get($comparisonMonthData, 'paymentDaily', []));
    $previousPeriodPaymentDaily = collect(data_get($comparisonPeriodData, 'paymentDaily', []));
    $previousMonthIncomeSummary = data_get($comparisonMonthData, 'incomeSummary', []);
    $previousPeriodIncomeSummary = data_get($comparisonPeriodData, 'incomeSummary', []);
    $previousMonthShippingKpi = data_get($comparisonMonthData, 'shippingKpi', []);
    $previousPeriodShippingKpi = data_get($comparisonPeriodData, 'shippingKpi', []);
    $previousMonthProducts = collect(data_get($comparisonMonthData, 'products', []));
    $previousPeriodProducts = collect(data_get($comparisonPeriodData, 'products', []));
    $previousMonthActiveCatalog = data_get($comparisonMonthData, 'activeMarketplaceCatalog', []);
    $previousPeriodActiveCatalog = data_get($comparisonPeriodData, 'activeMarketplaceCatalog', []);
    $previousMonthPreviousActiveCatalog = data_get($comparisonMonthPreviousData, 'activeMarketplaceCatalog', []);
    $previousPeriodPreviousActiveCatalog = data_get($comparisonPeriodPreviousData, 'activeMarketplaceCatalog', []);
    $previousMonthPreviousTwoActiveCatalog = data_get($comparisonMonthPreviousTwoData, 'activeMarketplaceCatalog', []);
    $previousPeriodPreviousTwoActiveCatalog = data_get($comparisonPeriodPreviousTwoData, 'activeMarketplaceCatalog', []);
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
    $productComparisonMetrics = function ($rows, $activeCatalog = []) use ($marketplaceProductCount, $marketplaceVariantCount, $soldVariantCount) {
        $rows = collect($rows);
        $orderKeys = $rows
            ->flatMap(fn ($product) => preg_split('/,/', (string) ($product->order_keys ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($key) => trim((string) $key))
            ->filter()
            ->unique()
            ->values();
        $orders = $orderKeys->count();
        $sales = (float) $rows->sum('sales');
        $netSales = (float) $rows->sum(fn ($product) => (float) ($product->net_sales ?? $product->sales ?? 0));
        $buyerPayment = (float) $rows->sum('buyer_payment');
        $costedProducts = $rows->filter(fn ($product) => $product->gross_profit !== null);
        $hpp = (float) $costedProducts->sum(fn ($product) => (float) ($product->hpp_total ?? ((float) ($product->hpp ?? 0) * (int) ($product->qty ?? 0))));
        $estimatedPayout = (float) $rows->sum('estimated_payout');
        $grossProfit = (float) $costedProducts->sum('gross_profit');
        $adProducts = $rows->filter(fn ($product) => $product->ad_spend_matched ?? false);
        $adSpend = (float) $adProducts->sum('ad_spend');
        $adSales = (float) $adProducts->sum('ad_sales');
        $adConversions = (int) $adProducts->sum('ad_conversions');
        $contributionProfit = (float) $costedProducts->sum(fn ($product) => (float) ($product->contribution_profit ?? ((float) ($product->gross_profit ?? 0) - (float) ($product->ad_spend ?? 0))));
        $mappedProducts = $rows->filter(fn ($product) => (int) ($product->internal_item_id ?? 0) > 0)->count();

        return [
            'active_products' => (int) data_get($activeCatalog, 'products', 0),
            'active_variants' => (int) data_get($activeCatalog, 'variants', 0),
            'unsold_variants' => max((int) data_get($activeCatalog, 'variants', 0) - $marketplaceVariantCount($rows), 0),
            'products' => $marketplaceProductCount($rows),
            'variants' => $marketplaceVariantCount($rows),
            'variants_sold' => $soldVariantCount($rows),
            'orders' => $orders,
            'qty' => (int) $rows->sum('qty'),
            'sales' => $sales,
            'net_sales' => $netSales,
            'buyer_payment' => $buyerPayment,
            'estimated_payout' => $estimatedPayout,
            'aov_sales' => $orders > 0 ? $sales / $orders : 0,
            'aov_payment' => $orders > 0 ? $buyerPayment / $orders : 0,
            'hpp' => $hpp,
            'cogs' => $hpp,
            'gross_profit_payout' => $estimatedPayout - $hpp,
            'gross_profit' => $grossProfit,
            'gross_margin' => $netSales > 0 ? ($grossProfit / $netSales) * 100 : null,
            'contribution_profit' => $contributionProfit,
            'net_profit' => $estimatedPayout - $hpp - $adSpend,
            'contribution_margin' => $netSales > 0 ? ($contributionProfit / $netSales) * 100 : null,
            'hpp_coverage' => $rows->count() > 0 ? ($costedProducts->count() / $rows->count()) * 100 : 0,
            'ad_spend' => $adSpend,
            'ad_sales' => $adSales,
            'acos' => $adSales > 0 ? ($adSpend / $adSales) * 100 : null,
            'roas' => $adSpend > 0 ? $adSales / $adSpend : null,
            'cpa' => $adConversions > 0 ? $adSpend / $adConversions : null,
            'mapping_rate' => $rows->count() > 0 ? ($mappedProducts / $rows->count()) * 100 : 0,
        ];
    };
    $productComparisonPeriods = $comparisonMode === 'month'
        ? [
            ['label' => 'Aktif', 'from' => $filters['date_from'], 'to' => $filters['date_to'], 'products' => $products, 'catalog' => $activeMarketplaceCatalog],
            ['label' => 'Bulan -1', 'from' => $comparisonMonth['from'] ?? null, 'to' => $comparisonMonth['to'] ?? null, 'products' => $previousMonthProducts, 'catalog' => $previousMonthActiveCatalog],
            ['label' => 'Bulan -2', 'from' => $comparisonMonthPrevious['from'] ?? null, 'to' => $comparisonMonthPrevious['to'] ?? null, 'products' => collect(data_get($comparisonMonthPreviousData, 'products', [])), 'catalog' => $previousMonthPreviousActiveCatalog],
            ['label' => 'Bulan -3', 'from' => $comparisonMonthPreviousTwo['from'] ?? null, 'to' => $comparisonMonthPreviousTwo['to'] ?? null, 'products' => collect(data_get($comparisonMonthPreviousTwoData, 'products', [])), 'catalog' => $previousMonthPreviousTwoActiveCatalog],
        ]
        : [
            ['label' => 'Aktif', 'from' => $filters['date_from'], 'to' => $filters['date_to'], 'products' => $products, 'catalog' => $activeMarketplaceCatalog],
            ['label' => 'Periode -1', 'from' => $comparisonPeriod['from'] ?? null, 'to' => $comparisonPeriod['to'] ?? null, 'products' => $previousPeriodProducts, 'catalog' => $previousPeriodActiveCatalog],
            ['label' => 'Periode -2', 'from' => $comparisonPeriodPrevious['from'] ?? null, 'to' => $comparisonPeriodPrevious['to'] ?? null, 'products' => collect(data_get($comparisonPeriodPreviousData, 'products', [])), 'catalog' => $previousPeriodPreviousActiveCatalog],
            ['label' => 'Periode -3', 'from' => $comparisonPeriodPreviousTwo['from'] ?? null, 'to' => $comparisonPeriodPreviousTwo['to'] ?? null, 'products' => collect(data_get($comparisonPeriodPreviousTwoData, 'products', [])), 'catalog' => $previousPeriodPreviousTwoActiveCatalog],
        ];
    $productComparisonPeriods = collect($productComparisonPeriods)->map(function ($period) use ($productComparisonMetrics) {
        $period['metrics'] = $productComparisonMetrics($period['products'], $period['catalog'] ?? []);
        unset($period['products']);

        return $period;
    })->all();
    $productComparisonRows = [
        ['group' => 'Katalog aktif', 'label' => 'Produk Aktif Marketplace', 'key' => 'active_products', 'format' => $numberDisplay],
        ['group' => 'Katalog aktif', 'label' => 'Variant Aktif Marketplace', 'key' => 'active_variants', 'format' => $numberDisplay],
        ['group' => 'Katalog aktif', 'label' => 'Variant Tidak Terjual', 'key' => 'unsold_variants', 'format' => $numberDisplay],
        ['group' => 'Volume transaksi', 'label' => 'Produk Terjual Marketplace', 'key' => 'products', 'format' => $numberDisplay],
        ['group' => 'Volume transaksi', 'label' => 'Variant Terjual Marketplace', 'key' => 'variants', 'format' => $numberDisplay],
        ['group' => 'Volume transaksi', 'label' => 'Variant Terjual Unik', 'key' => 'variants_sold', 'format' => $numberDisplay],
        ['group' => 'Volume transaksi', 'label' => 'Order Produk', 'key' => 'orders', 'format' => $numberDisplay],
        ['group' => 'Volume transaksi', 'label' => 'Unit Terjual', 'key' => 'qty', 'format' => $numberDisplay],
        ['group' => 'Pendapatan', 'label' => 'Penjualan Produk', 'key' => 'sales', 'format' => $currencyDisplay],
        ['group' => 'Pendapatan', 'label' => 'Penjualan Netto', 'key' => 'net_sales', 'format' => $currencyDisplay],
        ['group' => 'Pendapatan', 'label' => 'AOV Penjualan Netto', 'key' => 'aov_sales', 'format' => $currencyDisplay],
        ['group' => 'Pendapatan', 'label' => 'Pembayaran Pembeli', 'key' => 'buyer_payment', 'format' => $currencyDisplay],
        ['group' => 'Pendapatan', 'label' => 'AOV Pembayaran', 'key' => 'aov_payment', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas', 'label' => 'Estimasi Penghasilan', 'key' => 'estimated_payout', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas', 'label' => 'COGS (HPP)', 'key' => 'cogs', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas', 'label' => 'Laba Kotor', 'key' => 'gross_profit_payout', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas', 'label' => 'Laba Bersih', 'key' => 'net_profit', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas & iklan', 'label' => 'Kontribusi Pasca Iklan', 'key' => 'contribution_profit', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas & iklan', 'label' => 'Margin Kontribusi', 'key' => 'contribution_margin', 'format' => $percentDisplay, 'delta_mode' => 'points'],
        ['group' => 'Profitabilitas & iklan', 'label' => 'Biaya Iklan', 'key' => 'ad_spend', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas & iklan', 'label' => 'Penjualan Atribusi Iklan', 'key' => 'ad_sales', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas & iklan', 'label' => 'ACOS', 'key' => 'acos', 'format' => $percentDisplay, 'delta_mode' => 'points'],
        ['group' => 'Profitabilitas & iklan', 'label' => 'ROAS Blended', 'key' => 'roas', 'format' => $multipleDisplay],
        ['group' => 'Profitabilitas & iklan', 'label' => 'CPA', 'key' => 'cpa', 'format' => $currencyDisplay],
        ['group' => 'Kualitas data', 'label' => 'Coverage Mapping Internal', 'key' => 'mapping_rate', 'format' => $percentDisplay, 'delta_mode' => 'points'],
        ['group' => 'Kualitas data', 'label' => 'Coverage HPP', 'key' => 'hpp_coverage', 'format' => $percentDisplay, 'delta_mode' => 'points'],
    ];
    $salesComparisonMetrics = function ($rows, $data = []) use ($salesKpiMetrics) {
        $metrics = $salesKpiMetrics($rows);
        $orders = max(1, $metrics['orders']);
        $adSpend = (float) data_get($data, 'adSpendTotal', $metrics['ad_spend']);
        $adSales = (float) data_get($data, 'adSalesTotal', 0);
        $impressions = (int) data_get($data, 'adImpressionsTotal', 0);
        $clicks = (int) data_get($data, 'adClicksTotal', 0);
        $adOrders = (int) data_get($data, 'adOrdersTotal', 0);
        $promotionRows = collect(data_get($data, 'promotionDaily', []));
        $buyerPaid = (float) data_get($data, 'paymentSummary.buyer_paid', 0);
        $metrics['ad_spend'] = $adSpend;

        return $metrics + [
            'avg_units_per_order' => $metrics['orders'] > 0 ? (float) collect($rows)->sum(fn ($row) => (float) data_get($row, 'qty', 0)) / $orders : 0,
            'buyer_paid' => $buyerPaid,
            'aov_buyer_paid' => $metrics['orders'] > 0 ? $buyerPaid / $orders : null,
            'net_margin' => $metrics['net_sales'] > 0 ? ($metrics['net_profit'] / $metrics['net_sales']) * 100 : null,
            'ad_sales' => $adSales,
            'acos' => $adSales > 0 ? ($adSpend / $adSales) * 100 : null,
            'roas' => $adSpend > 0 ? $adSales / $adSpend : null,
            'cpa' => $adOrders > 0 ? $adSpend / $adOrders : null,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => $impressions > 0 ? ($clicks / $impressions) * 100 : null,
            'cvr' => $clicks > 0 ? ($adOrders / $clicks) * 100 : null,
            'voucher_seller' => (float) $promotionRows->sum('voucher_store'),
            'voucher_platform' => (float) $promotionRows->sum('voucher_platform'),
            'bundle_discount' => (float) $promotionRows->sum('bundle_discount'),
            'combo_hemat' => (float) $promotionRows->sum('combo_hemat'),
        ];
    };
    $salesComparisonSources = $comparisonMode === 'month'
        ? [
            ['label' => 'Aktif', 'from' => $filters['date_from'], 'to' => $filters['date_to'], 'daily' => $daily, 'data' => ['promotionDaily' => $promotionDaily, 'paymentSummary' => $paymentSummary, 'adSpendTotal' => $adSpendTotal, 'adSalesTotal' => $adSalesTotal, 'adImpressionsTotal' => $adImpressionsTotal, 'adClicksTotal' => $adClicksTotal, 'adOrdersTotal' => $adOrdersTotal]],
            ['label' => 'Bulan -1', 'from' => $comparisonMonth['from'] ?? null, 'to' => $comparisonMonth['to'] ?? null, 'daily' => data_get($comparisonMonthData, 'daily', []), 'data' => $comparisonMonthData],
            ['label' => 'Bulan -2', 'from' => $comparisonMonthPrevious['from'] ?? null, 'to' => $comparisonMonthPrevious['to'] ?? null, 'daily' => data_get($comparisonMonthPreviousData, 'daily', []), 'data' => $comparisonMonthPreviousData],
            ['label' => 'Bulan -3', 'from' => $comparisonMonthPreviousTwo['from'] ?? null, 'to' => $comparisonMonthPreviousTwo['to'] ?? null, 'daily' => data_get($comparisonMonthPreviousTwoData, 'daily', []), 'data' => $comparisonMonthPreviousTwoData],
        ]
        : [
            ['label' => 'Aktif', 'from' => $filters['date_from'], 'to' => $filters['date_to'], 'daily' => $daily, 'data' => ['promotionDaily' => $promotionDaily, 'paymentSummary' => $paymentSummary, 'adSpendTotal' => $adSpendTotal, 'adSalesTotal' => $adSalesTotal, 'adImpressionsTotal' => $adImpressionsTotal, 'adClicksTotal' => $adClicksTotal, 'adOrdersTotal' => $adOrdersTotal]],
            ['label' => 'Periode -1', 'from' => $comparisonPeriod['from'] ?? null, 'to' => $comparisonPeriod['to'] ?? null, 'daily' => data_get($comparisonPeriodData, 'daily', []), 'data' => $comparisonPeriodData],
            ['label' => 'Periode -2', 'from' => $comparisonPeriodPrevious['from'] ?? null, 'to' => $comparisonPeriodPrevious['to'] ?? null, 'daily' => data_get($comparisonPeriodPreviousData, 'daily', []), 'data' => $comparisonPeriodPreviousData],
            ['label' => 'Periode -3', 'from' => $comparisonPeriodPreviousTwo['from'] ?? null, 'to' => $comparisonPeriodPreviousTwo['to'] ?? null, 'daily' => data_get($comparisonPeriodPreviousTwoData, 'daily', []), 'data' => $comparisonPeriodPreviousTwoData],
        ];
    $salesComparisonPeriods = collect($salesComparisonSources)->map(function ($period) use ($salesComparisonMetrics) {
        $period['metrics'] = $salesComparisonMetrics($period['daily'], $period['data'] ?? []);
        unset($period['daily'], $period['data']);

        return $period;
    })->all();
    $salesComparisonRows = [
        ['group' => 'Volume transaksi', 'label' => 'Pesanan', 'key' => 'orders', 'format' => $numberDisplay],
        ['group' => 'Volume transaksi', 'label' => 'Unit Terjual', 'key' => 'qty', 'format' => $numberDisplay],
        ['group' => 'Volume transaksi', 'label' => 'Unit / Order', 'key' => 'avg_units_per_order', 'format' => fn ($value) => number_format((float) $value, 2, ',', '.')],
        ['group' => 'Pendapatan', 'label' => 'Gross Sales', 'key' => 'gross_sales', 'format' => $currencyDisplay],
        ['group' => 'Pendapatan', 'label' => 'Net Sales', 'key' => 'net_sales', 'format' => $currencyDisplay],
        ['group' => 'Pendapatan', 'label' => 'Pembayaran Pembeli', 'key' => 'buyer_paid', 'format' => $currencyDisplay],
        ['group' => 'Pendapatan', 'label' => 'AOV Pembayaran', 'key' => 'aov_buyer_paid', 'format' => fn ($value) => $value === null ? '—' : $currencyDisplay($value)],
        ['group' => 'Pendapatan', 'label' => 'Est Penghasilan', 'key' => 'payout', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas', 'label' => 'COGS', 'key' => 'cogs', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas', 'label' => 'Laba Kotor', 'key' => 'gross_profit', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas', 'label' => 'Laba Bersih', 'key' => 'net_profit', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas', 'label' => 'Margin Bersih', 'key' => 'net_margin', 'format' => fn ($value) => $value === null ? '—' : $percentDisplay($value)],
        ['group' => 'Iklan', 'label' => 'Iklan', 'key' => 'ad_spend', 'format' => $currencyDisplay],
        ['group' => 'Iklan', 'label' => 'Sales Iklan', 'key' => 'ad_sales', 'format' => $currencyDisplay],
        ['group' => 'Iklan', 'label' => 'ACOS', 'key' => 'acos', 'format' => fn ($value) => $value === null ? '—' : $percentDisplay($value)],
        ['group' => 'Iklan', 'label' => 'ROAS', 'key' => 'roas', 'format' => fn ($value) => $value === null ? '—' : $multipleDisplay($value)],
        ['group' => 'Iklan', 'label' => 'CPA', 'key' => 'cpa', 'format' => fn ($value) => $value === null ? '—' : $currencyDisplay($value)],
        ['group' => 'Iklan', 'label' => 'Dilihat', 'key' => 'impressions', 'format' => $numberDisplay],
        ['group' => 'Iklan', 'label' => 'Klik', 'key' => 'clicks', 'format' => $numberDisplay],
        ['group' => 'Iklan', 'label' => 'CTR', 'key' => 'ctr', 'format' => fn ($value) => $value === null ? '—' : $percentDisplay($value)],
        ['group' => 'Iklan', 'label' => 'CVR', 'key' => 'cvr', 'format' => fn ($value) => $value === null ? '—' : $percentDisplay($value)],
        ['group' => 'Kontribusi Promosi', 'label' => 'Voucher Seller', 'key' => 'voucher_seller', 'format' => $currencyDisplay],
        ['group' => 'Kontribusi Promosi', 'label' => 'Voucher Platform', 'key' => 'voucher_platform', 'format' => $currencyDisplay],
        ['group' => 'Kontribusi Promosi', 'label' => 'Paket Diskon', 'key' => 'bundle_discount', 'format' => $currencyDisplay],
        ['group' => 'Kontribusi Promosi', 'label' => 'Kombo Hemat', 'key' => 'combo_hemat', 'format' => $currencyDisplay],
    ];
    $activeProductKpi = $productComparisonMetrics($products, $activeMarketplaceCatalog);
    $previousMonthProductKpi = $productComparisonMetrics($previousMonthProducts, $previousMonthActiveCatalog);
    $previousPeriodProductKpi = $productComparisonMetrics($previousPeriodProducts, $previousPeriodActiveCatalog);
    $categoryProductMetrics = function ($rows, $activeCatalog = []) {
        $rows = collect($rows);
        $marketplaceProductKeys = $rows
            ->map(function ($product) {
                $externalItemId = trim((string) ($product->external_item_id ?? ''));
                $marketplaceName = trim((string) ($product->marketplace_name ?? $product->name ?? ''));

                return $externalItemId !== ''
                    ? 'external:'.$externalItemId
                    : ($marketplaceName !== '' ? 'name:'.$marketplaceName : null);
            })
            ->filter()
            ->unique()
            ->values();
        $soldVariantKeys = $rows
            ->map(function ($product) {
                $internalItemId = (int) ($product->internal_item_id ?? 0);
                if ($internalItemId > 0) {
                    return 'internal:'.$internalItemId;
                }

                $externalItemId = trim((string) ($product->external_item_id ?? ''));
                $marketplaceName = trim((string) ($product->marketplace_name ?? $product->name ?? ''));

                return $externalItemId !== ''
                    ? 'external:'.$externalItemId
                    : ($marketplaceName !== '' ? 'name:'.$marketplaceName : null);
            })
            ->filter()
            ->unique()
            ->values();
        $orderKeys = $rows
            ->flatMap(fn ($product) => preg_split('/,/', (string) ($product->order_keys ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($key) => trim((string) $key))
            ->filter()
            ->unique()
            ->values();
        $sales = (float) $rows->sum('sales');
        $netSales = (float) $rows->sum(fn ($product) => (float) ($product->net_sales ?? $product->sales ?? 0));
        $orders = $orderKeys->count();
        $costedProducts = $rows->filter(fn ($product) => $product->gross_profit !== null);
        $adProducts = $rows->filter(fn ($product) => $product->ad_spend_matched ?? false);
        $hpp = (float) $costedProducts->sum(fn ($product) => (float) ($product->hpp_total ?? ((float) ($product->hpp ?? 0) * (int) ($product->qty ?? 0))));
        $estimatedPayout = (float) $rows->sum('estimated_payout');
        $grossProfit = (float) $costedProducts->sum('gross_profit');
        $adSpend = (float) $adProducts->sum('ad_spend');
        $adSales = (float) $adProducts->sum('ad_sales');
        $adConversions = (int) $adProducts->sum('ad_conversions');
        $contributionProfit = (float) $costedProducts->sum(fn ($product) => (float) ($product->contribution_profit ?? ((float) ($product->gross_profit ?? 0) - (float) ($product->ad_spend ?? 0))));
        $mappedProducts = $rows->filter(fn ($product) => (int) ($product->internal_item_id ?? 0) > 0)->count();

        return [
            'active_products' => (int) data_get($activeCatalog, 'products', 0),
            'active_variants' => (int) data_get($activeCatalog, 'variants', 0),
            'unsold_variants' => max((int) data_get($activeCatalog, 'variants', 0) - $rows->count(), 0),
            'products' => $marketplaceProductKeys->count(),
            'variants' => $rows->count(),
            'variants_sold' => $soldVariantKeys->count(),
            'qty' => (int) $rows->sum('qty'),
            'orders' => $orders,
            'sales' => $sales,
            'net_sales' => $netSales,
            'buyer_payment' => (float) $rows->sum('buyer_payment'),
            'estimated_payout' => $estimatedPayout,
            'aov_sales' => $orders > 0 ? $sales / $orders : 0,
            'aov_payment' => $orders > 0 ? ((float) $rows->sum('buyer_payment')) / $orders : 0,
            'hpp' => $hpp,
            'cogs' => $hpp,
            'gross_profit_payout' => $estimatedPayout - $hpp,
            'gross_profit' => $grossProfit,
            'gross_margin' => $netSales > 0 ? ($grossProfit / $netSales) * 100 : null,
            'contribution_profit' => $contributionProfit,
            'net_profit' => $estimatedPayout - $hpp - $adSpend,
            'contribution_margin' => $netSales > 0 ? ($contributionProfit / $netSales) * 100 : null,
            'hpp_coverage' => $rows->count() > 0 ? ($costedProducts->count() / $rows->count()) * 100 : 0,
            'ad_spend' => $adSpend,
            'ad_sales' => $adSales,
            'acos' => $adSales > 0 ? ($adSpend / $adSales) * 100 : null,
            'roas' => $adSpend > 0 ? $adSales / $adSpend : null,
            'cpa' => $adConversions > 0 ? $adSpend / $adConversions : null,
            'mapping_rate' => $rows->count() > 0 ? ($mappedProducts / $rows->count()) * 100 : 0,
        ];
    };
    $categoryComparisonSourcePeriods = $comparisonMode === 'month'
        ? [
            ['key' => 'active', 'label' => 'Aktif', 'from' => $filters['date_from'], 'to' => $filters['date_to'], 'products' => $products, 'catalog' => $activeMarketplaceCatalog],
            ['key' => 'previous', 'label' => 'Bulan -1', 'from' => $comparisonMonth['from'] ?? null, 'to' => $comparisonMonth['to'] ?? null, 'products' => $previousMonthProducts, 'catalog' => $previousMonthActiveCatalog],
            ['key' => 'previous_2', 'label' => 'Bulan -2', 'from' => $comparisonMonthPrevious['from'] ?? null, 'to' => $comparisonMonthPrevious['to'] ?? null, 'products' => collect(data_get($comparisonMonthPreviousData, 'products', [])), 'catalog' => $previousMonthPreviousActiveCatalog],
            ['key' => 'previous_3', 'label' => 'Bulan -3', 'from' => $comparisonMonthPreviousTwo['from'] ?? null, 'to' => $comparisonMonthPreviousTwo['to'] ?? null, 'products' => collect(data_get($comparisonMonthPreviousTwoData, 'products', [])), 'catalog' => $previousMonthPreviousTwoActiveCatalog],
        ]
        : [
            ['key' => 'active', 'label' => 'Aktif', 'from' => $filters['date_from'], 'to' => $filters['date_to'], 'products' => $products, 'catalog' => $activeMarketplaceCatalog],
            ['key' => 'previous', 'label' => 'Periode -1', 'from' => $comparisonPeriod['from'] ?? null, 'to' => $comparisonPeriod['to'] ?? null, 'products' => $previousPeriodProducts, 'catalog' => $previousPeriodActiveCatalog],
            ['key' => 'previous_2', 'label' => 'Periode -2', 'from' => $comparisonPeriodPrevious['from'] ?? null, 'to' => $comparisonPeriodPrevious['to'] ?? null, 'products' => collect(data_get($comparisonPeriodPreviousData, 'products', [])), 'catalog' => $previousPeriodPreviousActiveCatalog],
            ['key' => 'previous_3', 'label' => 'Periode -3', 'from' => $comparisonPeriodPreviousTwo['from'] ?? null, 'to' => $comparisonPeriodPreviousTwo['to'] ?? null, 'products' => collect(data_get($comparisonPeriodPreviousTwoData, 'products', [])), 'catalog' => $previousPeriodPreviousTwoActiveCatalog],
        ];
    $categoryComparisonPeriods = collect($categoryComparisonSourcePeriods)
        ->map(fn ($period) => collect($period)->except('products')->all())
        ->values();
    $categoryNames = collect($categoryComparisonSourcePeriods)
        ->flatMap(fn ($period) => collect($period['products'])->map(fn ($product) => trim((string) ($product->category_name ?? '')) ?: 'Tanpa kategori'))
        ->merge(collect($categoryComparisonSourcePeriods)
            ->flatMap(fn ($period) => array_keys((array) data_get($period, 'catalog.by_category', []))))
        ->unique()
        ->values();
    $categoryComparisonRows = $categoryNames
        ->map(function ($categoryName) use ($categoryComparisonSourcePeriods, $categoryProductMetrics, $productAnalysisNetSales) {
            $periods = collect($categoryComparisonSourcePeriods)->mapWithKeys(function ($period) use ($categoryName, $categoryProductMetrics) {
                $categoryProducts = collect($period['products'])->filter(fn ($product) => (trim((string) ($product->category_name ?? '')) ?: 'Tanpa kategori') === $categoryName);
                $categoryCatalog = collect(data_get($period, 'catalog.by_category', []))->get($categoryName, []);

                return [$period['key'] => [
                    'label' => $period['label'],
                    'from' => $period['from'],
                    'to' => $period['to'],
                    'metrics' => $categoryProductMetrics($categoryProducts, $categoryCatalog),
                ]];
            });
            $activeMetrics = $periods->get('active')['metrics'];
            $previousMetrics = $periods->get('previous')['metrics'];
            $activeNetSales = (float) $activeMetrics['net_sales'];
            $previousNetSales = (float) $previousMetrics['net_sales'];
            $delta = $activeNetSales - $previousNetSales;

            return [
                'name' => $categoryName,
                'periods' => $periods->all(),
                'delta' => $delta,
                'delta_percent' => $previousNetSales > 0 ? ($delta / $previousNetSales) * 100 : null,
                'share' => $productAnalysisNetSales > 0 ? ($activeNetSales / $productAnalysisNetSales) * 100 : 0,
            ];
        })
        ->sortByDesc(fn ($row) => (float) ($row['periods']['active']['metrics']['net_sales'] ?? 0))
        ->values();
    $categoryProductComparisonRows = [
        ['group' => 'Katalog aktif', 'label' => 'Produk Aktif Marketplace', 'key' => 'active_products', 'format' => $numberDisplay],
        ['group' => 'Katalog aktif', 'label' => 'Variant Aktif Marketplace', 'key' => 'active_variants', 'format' => $numberDisplay],
        ['group' => 'Katalog aktif', 'label' => 'Variant Tidak Terjual', 'key' => 'unsold_variants', 'format' => $numberDisplay],
        ['group' => 'Volume transaksi', 'label' => 'Produk Terjual Marketplace', 'key' => 'products', 'format' => $numberDisplay],
        ['group' => 'Volume transaksi', 'label' => 'Variant Terjual Marketplace', 'key' => 'variants', 'format' => $numberDisplay],
        ['group' => 'Volume transaksi', 'label' => 'Variant Terjual Unik', 'key' => 'variants_sold', 'format' => $numberDisplay],
        ['group' => 'Volume transaksi', 'label' => 'Order Produk', 'key' => 'orders', 'format' => $numberDisplay],
        ['group' => 'Volume transaksi', 'label' => 'Unit Terjual', 'key' => 'qty', 'format' => $numberDisplay],
        ['group' => 'Pendapatan', 'label' => 'Penjualan Produk', 'key' => 'sales', 'format' => $currencyDisplay],
        ['group' => 'Pendapatan', 'label' => 'Penjualan Netto', 'key' => 'net_sales', 'format' => $currencyDisplay],
        ['group' => 'Pendapatan', 'label' => 'AOV Penjualan Netto', 'key' => 'aov_sales', 'format' => $currencyDisplay],
        ['group' => 'Pendapatan', 'label' => 'Pembayaran Pembeli', 'key' => 'buyer_payment', 'format' => $currencyDisplay],
        ['group' => 'Pendapatan', 'label' => 'AOV Pembayaran', 'key' => 'aov_payment', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas', 'label' => 'Estimasi Penghasilan', 'key' => 'estimated_payout', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas', 'label' => 'COGS (HPP)', 'key' => 'cogs', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas', 'label' => 'Laba Kotor', 'key' => 'gross_profit_payout', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas', 'label' => 'Laba Bersih', 'key' => 'net_profit', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas & iklan', 'label' => 'Kontribusi Pasca Iklan', 'key' => 'contribution_profit', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas & iklan', 'label' => 'Margin Kontribusi', 'key' => 'contribution_margin', 'format' => $percentDisplay, 'delta_mode' => 'points'],
        ['group' => 'Profitabilitas & iklan', 'label' => 'Biaya Iklan', 'key' => 'ad_spend', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas & iklan', 'label' => 'Penjualan Atribusi Iklan', 'key' => 'ad_sales', 'format' => $currencyDisplay],
        ['group' => 'Profitabilitas & iklan', 'label' => 'ACOS', 'key' => 'acos', 'format' => $percentDisplay, 'delta_mode' => 'points'],
        ['group' => 'Profitabilitas & iklan', 'label' => 'ROAS Blended', 'key' => 'roas', 'format' => $multipleDisplay],
        ['group' => 'Profitabilitas & iklan', 'label' => 'CPA', 'key' => 'cpa', 'format' => $currencyDisplay],
        ['group' => 'Kualitas data', 'label' => 'Coverage Mapping Internal', 'key' => 'mapping_rate', 'format' => $percentDisplay, 'delta_mode' => 'points'],
        ['group' => 'Kualitas data', 'label' => 'Coverage HPP', 'key' => 'hpp_coverage', 'format' => $percentDisplay, 'delta_mode' => 'points'],
    ];
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
        $period['metrics']['ad_cpa'] = $period['metrics']['ad_orders'] > 0
            ? $period['metrics']['ad_spend'] / $period['metrics']['ad_orders']
            : 0;
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
                ['label' => 'CTR', 'key' => 'ad_ctr', 'format' => $percentDisplay, 'delta_mode' => 'points'],
                ['label' => 'CVR', 'key' => 'ad_cvr', 'format' => $percentDisplay, 'delta_mode' => 'points'],
                ['label' => 'Biaya Iklan', 'key' => 'ad_spend', 'format' => $currencyDisplay],
                ['label' => 'CPA Iklan', 'key' => 'ad_cpa', 'format' => $currencyDisplay],
                ['label' => 'Rasio Iklan / Sales Iklan', 'key' => 'ad_sales_rate', 'format' => $percentDisplay, 'delta_mode' => 'points'],
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
                ['label' => 'Kontribusi Sales', 'key' => 'platform_rate', 'format' => $percentDisplay, 'delta_mode' => 'points'],
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
                ['label' => 'Kontribusi Sales', 'key' => 'voucher_seller_rate', 'format' => $percentDisplay, 'delta_mode' => 'points'],
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
                ['label' => 'Kontribusi Sales', 'key' => 'bundle_discount_rate', 'format' => $percentDisplay, 'delta_mode' => 'points'],
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
                ['label' => 'Kontribusi Sales', 'key' => 'combo_hemat_rate', 'format' => $percentDisplay, 'delta_mode' => 'points'],
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
    $comparisonCellMeta = function ($currentValue, $comparisonValue, callable $formatter, string $differenceMode = 'relative'): ?array {
        if (!is_numeric($currentValue) || !is_numeric($comparisonValue)) {
            return null;
        }

        $currentValue = (float) $currentValue;
        $comparisonValue = (float) $comparisonValue;
        $difference = $currentValue - $comparisonValue;
        $comparisonBase = abs($comparisonValue);
        $percentageLabel = $comparisonBase > 0
            ? number_format(abs(($difference / $comparisonBase) * 100), 1, ',', '.').'%'
            : ($currentValue === 0.0 ? '0,0%' : 'Baru');
        $tone = abs($difference) < 0.00001
            ? 'is-neutral'
            : ($difference > 0 ? 'is-up' : 'is-down');

        return [
            'arrow' => $difference > 0 ? 'bi-arrow-up-right' : ($difference < 0 ? 'bi-arrow-down-right' : 'bi-arrow-left-right'),
            'arrow_title' => $difference > 0 ? 'Aktif lebih tinggi' : ($difference < 0 ? 'Aktif lebih rendah' : 'Nilai sama'),
            'percentage' => $percentageLabel,
            'difference' => '('.($difference > 0 ? '+' : ($difference < 0 ? '−' : '±')).($differenceMode === 'points'
                ? number_format(abs($difference), 1, ',', '.').' pt'
                : $formatter(abs($difference))).')',
            'tone' => $tone,
        ];
    };
    $paymentDatePhase = function ($date): string {
        $day = (int) \Carbon\Carbon::parse($date)->day;

        return $day <= 10 ? 'early' : ($day <= 20 ? 'mid' : 'late');
    };
    $paymentPeriodPhases = function ($from, $to) use ($paymentDatePhase): array {
        if (!$from || !$to) return [];
        $phases = [];
        $cursor = \Carbon\Carbon::parse($from)->startOfDay();
        $end = \Carbon\Carbon::parse($to)->startOfDay();
        while ($cursor->lte($end)) {
            $phases[$paymentDatePhase($cursor)] = true;
            $cursor->addDay();
        }
        $labels = [
            'early' => ['label' => 'Awal bulan', 'range' => '1–10'],
            'mid' => ['label' => 'Pertengahan', 'range' => '11–20'],
            'late' => ['label' => 'Akhir bulan', 'range' => '21+'],
        ];

        return collect(array_keys($phases))->map(fn ($key) => ['key' => $key, ...$labels[$key]])->all();
    };
    $paymentComparisonPeriods = $comparisonMode === 'month'
        ? [
            ['label' => 'Aktif', 'from' => $filters['date_from'], 'to' => $filters['date_to'], 'summary' => $paymentSummary, 'daily' => $paymentDaily],
            ['label' => 'Bulan -1', 'from' => $comparisonMonth['from'] ?? null, 'to' => $comparisonMonth['to'] ?? null, 'summary' => data_get($comparisonMonthData, 'paymentSummary', []), 'daily' => data_get($comparisonMonthData, 'paymentDaily', [])],
            ['label' => 'Bulan -2', 'from' => $comparisonMonthPrevious['from'] ?? null, 'to' => $comparisonMonthPrevious['to'] ?? null, 'summary' => data_get($comparisonMonthPreviousData, 'paymentSummary', []), 'daily' => data_get($comparisonMonthPreviousData, 'paymentDaily', [])],
            ['label' => 'Bulan -3', 'from' => $comparisonMonthPreviousTwo['from'] ?? null, 'to' => $comparisonMonthPreviousTwo['to'] ?? null, 'summary' => data_get($comparisonMonthPreviousTwoData, 'paymentSummary', []), 'daily' => data_get($comparisonMonthPreviousTwoData, 'paymentDaily', [])],
        ]
        : [
            ['label' => 'Aktif', 'from' => $filters['date_from'], 'to' => $filters['date_to'], 'summary' => $paymentSummary, 'daily' => $paymentDaily],
            ['label' => 'Periode -1', 'from' => $comparisonPeriod['from'] ?? null, 'to' => $comparisonPeriod['to'] ?? null, 'summary' => data_get($comparisonPeriodData, 'paymentSummary', []), 'daily' => data_get($comparisonPeriodData, 'paymentDaily', [])],
            ['label' => 'Periode -2', 'from' => $comparisonPeriodPrevious['from'] ?? null, 'to' => $comparisonPeriodPrevious['to'] ?? null, 'summary' => data_get($comparisonPeriodPreviousData, 'paymentSummary', []), 'daily' => data_get($comparisonPeriodPreviousData, 'paymentDaily', [])],
            ['label' => 'Periode -3', 'from' => $comparisonPeriodPreviousTwo['from'] ?? null, 'to' => $comparisonPeriodPreviousTwo['to'] ?? null, 'summary' => data_get($comparisonPeriodPreviousTwoData, 'paymentSummary', []), 'daily' => data_get($comparisonPeriodPreviousTwoData, 'paymentDaily', [])],
        ];
    $paymentComparisonPeriods = collect($paymentComparisonPeriods)->map(function ($period) use ($paymentPeriodPhases) {
        $summary = (array) ($period['summary'] ?? []);
        $daily = collect($period['daily'] ?? []);
        $period['phases'] = $paymentPeriodPhases($period['from'] ?? null, $period['to'] ?? null);
        $period['metrics'] = [
            'orders' => (int) data_get($summary, 'orders', 0),
            'buyer_paid' => (float) data_get($summary, 'buyer_paid', 0),
            'aov' => (float) data_get($summary, 'aov', 0),
            'seller_net_sales' => (float) $daily->sum('seller_net_sales'),
            'seller_aov' => (float) data_get($summary, 'orders', 0) > 0
                ? ((float) $daily->sum('seller_net_sales') / (float) data_get($summary, 'orders', 0))
                : 0,
            'cod_order_share' => (float) data_get($summary, 'cod_order_share', 0),
            'non_cod_order_share' => (float) data_get($summary, 'orders', 0) > 0
                ? ((float) $daily->sum('non_cod_orders') / (float) data_get($summary, 'orders', 0)) * 100
                : 0,
            'pay_later_order_share' => (float) data_get($summary, 'orders', 0) > 0
                ? ((float) $daily->sum('pay_later_orders') / (float) data_get($summary, 'orders', 0)) * 100
                : 0,
        ];
        unset($period['summary'], $period['daily']);

        return $period;
    })->all();
    $paymentComparisonRows = [
        ['label' => 'Orders', 'key' => 'orders', 'formatter' => $numberDisplay],
        ['label' => 'Buyer Paid', 'key' => 'buyer_paid', 'formatter' => $currencyDisplay],
        ['label' => 'AOV Buyer Paid', 'key' => 'aov', 'formatter' => $currencyDisplay],
        ['label' => 'Seller Net Sales', 'key' => 'seller_net_sales', 'formatter' => $currencyDisplay],
        ['label' => 'AOV Seller Net', 'key' => 'seller_aov', 'formatter' => $currencyDisplay],
        ['label' => 'COD %', 'key' => 'cod_order_share', 'formatter' => $percentDisplay, 'delta_mode' => 'points'],
        ['label' => 'Non-COD %', 'key' => 'non_cod_order_share', 'formatter' => $percentDisplay, 'delta_mode' => 'points'],
        ['label' => 'Pay Later %', 'key' => 'pay_later_order_share', 'formatter' => $percentDisplay, 'delta_mode' => 'points'],
    ];
    $paymentOrderShare = function ($summary, $daily, string $field): float {
        $orders = (int) data_get($summary, 'orders', 0);

        return $orders > 0 ? ((float) collect($daily)->sum($field) / $orders) * 100 : 0;
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
            <div class="row g-2 align-items-end justify-content-between">
                @if (!empty($filters['dummy']))
                    <input type="hidden" name="dummy" value="1">
                @endif
                <input type="hidden" name="tab" id="sales-active-tab" value="{{ $activeTab }}">
                <input type="hidden" name="comparison_mode" id="sales-comparison-mode" value="{{ $comparisonMode }}">
                <div class="col-12 col-md-auto sales-filter-scope" role="group" aria-label="Filter analitik">
                    @if ($platforms->isNotEmpty())
                        <div class="sales-filter-field sales-filter-platform">
                            <label class="sales-filter-label" for="sales-platform"><i class="bi bi-layers" aria-hidden="true"></i>Platform</label>
                            <select id="sales-platform" class="form-select form-select-sm" name="platform">
                                <option value="">Semua platform</option>
                                @foreach ($platforms as $platform)
                                    <option value="{{ $platform['code'] }}" @selected($filters['platform'] === $platform['code'])>{{ $platform['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if ($visibleStores->isNotEmpty())
                        <div class="sales-filter-field sales-filter-store">
                            <label class="sales-filter-label" for="sales-store"><i class="bi bi-shop" aria-hidden="true"></i>Toko</label>
                            <select id="sales-store" class="form-select form-select-sm" name="store_id">
                                <option value="">Semua toko</option>
                                @foreach ($visibleStores as $store)
                                    <option value="{{ $store->id }}" @selected((string) $filters['store_id'] === (string) $store->id)>
                                        {{ $store->name }}{{ $store->channel?->code ? ' · '.ucfirst($store->channel->code) : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
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
            </div>
        </div>
    </form>

    <div class="sales-nav-shell">
        <nav class="sales-nav nav nav-pills gap-2" aria-label="Dashboard operasional" role="tablist">
        <button class="nav-link {{ $activeTab === 'sales' ? 'active' : '' }}" type="button" role="tab" aria-selected="{{ $activeTab === 'sales' ? 'true' : 'false' }}" data-sales-tab="sales"><i class="bi bi-graph-up-arrow me-1"></i>Penjualan</button>
        <button class="nav-link {{ $activeTab === 'products' ? 'active' : '' }}" type="button" role="tab" aria-selected="{{ $activeTab === 'products' ? 'true' : 'false' }}" data-sales-tab="products"><i class="bi bi-box-seam me-1"></i>Produk</button>
        <button class="nav-link {{ $activeTab === 'payments' ? 'active' : '' }}" type="button" role="tab" aria-selected="{{ $activeTab === 'payments' ? 'true' : 'false' }}" data-sales-tab="payments"><i class="bi bi-wallet2 me-1"></i>Pembayaran</button>
        <button class="nav-link {{ $activeTab === 'promotions' ? 'active' : '' }}" type="button" role="tab" aria-selected="{{ $activeTab === 'promotions' ? 'true' : 'false' }}" data-sales-tab="promotions"><i class="bi bi-percent me-1"></i>Promosi</button>
        <button class="nav-link {{ $activeTab === 'shipping' ? 'active' : '' }}" type="button" role="tab" aria-selected="{{ $activeTab === 'shipping' ? 'true' : 'false' }}" data-sales-tab="shipping"><i class="bi bi-truck me-1"></i>Pengiriman</button>
        <button class="nav-link {{ $activeTab === 'income' ? 'active' : '' }}" type="button" role="tab" aria-selected="{{ $activeTab === 'income' ? 'true' : 'false' }}" data-sales-tab="income"><i class="bi bi-cash-coin me-1"></i>Penghasilan</button>
        <button class="nav-link {{ $activeTab === 'orders' ? 'active' : '' }}" type="button" role="tab" aria-selected="{{ $activeTab === 'orders' ? 'true' : 'false' }}" data-sales-tab="orders"><i class="bi bi-list-ul me-1"></i>Detail Pesanan</button>
        <div class="sales-nav-comparison" role="group" aria-label="Perbandingan periode">
            <span class="sales-nav-comparison-label"><i class="bi bi-arrow-left-right" aria-hidden="true"></i>Bandingkan</span>
            <button type="button" class="sales-comparison-tab {{ $comparisonMode === 'period' ? 'active' : '' }}" data-comparison-mode="period" aria-pressed="{{ $comparisonMode === 'period' ? 'true' : 'false' }}"><i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>Periode lalu</button>
            <button type="button" class="sales-comparison-tab {{ $comparisonMode === 'month' ? 'active' : '' }}" data-comparison-mode="month" aria-pressed="{{ $comparisonMode === 'month' ? 'true' : 'false' }}"><i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>Bulan lalu</button>
        </div>
        </nav>
    </div>

    <div class="sales-tab-pane {{ $activeTab === 'sales' ? '' : 'is-hidden' }}" data-sales-pane="sales" role="tabpanel" aria-hidden="{{ $activeTab === 'sales' ? 'false' : 'true' }}">
    @include('marketplace.dashboard.partials._kpis', [
        'kpiTitle' => 'Penjualan',
        'kpis' => [
            ['group' => 'Pendapatan', 'label' => 'Gross Sales', 'value' => $fmt($currentSalesKpi['gross_sales']), 'note' => number_format($currentSalesKpi['orders']).' pesanan', 'icon' => 'bi-cash-stack', 'variant' => 'sales-kpi--success', 'comparisons' => $kpiComparisons($currentSalesKpi['gross_sales'], $previousMonthSalesKpi['gross_sales'], $previousPeriodSalesKpi['gross_sales'], $currencyDisplay)],
            ['group' => 'Pendapatan', 'label' => 'Net Sales', 'value' => $fmt($currentSalesKpi['net_sales']), 'note' => 'setelah diskon seller', 'icon' => 'bi-graph-down-arrow', 'comparisons' => $kpiComparisons($currentSalesKpi['net_sales'], $previousMonthSalesKpi['net_sales'], $previousPeriodSalesKpi['net_sales'], $currencyDisplay)],
            ['group' => 'Pendapatan', 'label' => 'Est Penghasilan', 'value' => $fmt($currentSalesKpi['payout']), 'note' => 'dasar laba kotor', 'icon' => 'bi-wallet2', 'comparisons' => $kpiComparisons($currentSalesKpi['payout'], $previousMonthSalesKpi['payout'], $previousPeriodSalesKpi['payout'], $currencyDisplay)],
            ['group' => 'Profitabilitas', 'label' => 'COGS (HPP)', 'value' => $fmt($currentSalesKpi['cogs']), 'note' => 'biaya produk terjual', 'icon' => 'bi-box-seam', 'comparisons' => $kpiComparisons($currentSalesKpi['cogs'], $previousMonthSalesKpi['cogs'], $previousPeriodSalesKpi['cogs'], $currencyDisplay, 'relative', false)],
            ['group' => 'Profitabilitas', 'label' => 'Laba Kotor', 'value' => $fmt($currentSalesKpi['gross_profit']), 'note' => 'payout − COGS', 'icon' => 'bi-graph-up-arrow', 'variant' => 'sales-kpi--success', 'comparisons' => $kpiComparisons($currentSalesKpi['gross_profit'], $previousMonthSalesKpi['gross_profit'], $previousPeriodSalesKpi['gross_profit'], $currencyDisplay)],
            ['group' => 'Profitabilitas', 'label' => 'Laba Bersih', 'value' => $fmt($currentSalesKpi['net_profit']), 'note' => 'laba kotor − iklan', 'icon' => 'bi-bar-chart-line', 'variant' => 'sales-kpi--success', 'comparisons' => $kpiComparisons($currentSalesKpi['net_profit'], $previousMonthSalesKpi['net_profit'], $previousPeriodSalesKpi['net_profit'], $currencyDisplay)],
        ],
    ])
    @if ($trendCurrentRows->isNotEmpty())
        <section class="card sales-card sales-trend-section shadow-sm mb-3" aria-labelledby="sales-trend-title">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <div class="sales-kicker mb-1">Analisis tren</div>
                    <h2 id="sales-trend-title" class="sales-section-title mb-1">Tren Kinerja &amp; Anomali</h2>
                </div>
                <div class="sales-trend-header-meta">
                    <span class="badge sales-badge rounded-pill px-3 py-2"><i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>{{ $trendComparisonLabel }}</span>
                    <span class="sales-trend-period">{{ $dateRangeLabel($filters['date_from'], $filters['date_to']) }}</span>
                </div>
            </div>
            <div class="sales-trend-body">
                <div class="sales-trend-summary" aria-label="Ringkasan indikator tren">
                    @foreach ($trendSummary as $item)
                        <div class="sales-trend-summary-card">
                            <span class="sales-trend-summary-icon"><i class="bi {{ $item['icon'] }}" aria-hidden="true"></i></span>
                            <div class="sales-trend-summary-content">
                                <div class="sales-trend-summary-label">{{ $item['label'] }}</div>
                                <div class="sales-trend-summary-value">{{ $item['value'] }}</div>
                                <span class="sales-trend-summary-delta {{ $item['tone'] }}">{{ $item['delta'] }} vs {{ $trendComparisonLabel }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="sales-trend-layout">
                    <div class="sales-trend-panel">
                        <div class="sales-trend-toolbar">
                            <div>
                                <div class="sales-trend-label">Indikator</div>
                                <div class="sales-trend-total" data-sales-trend-total>{{ $fmt($trendCurrentRows->sum('net_sales')) }}</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="sales-trend-compare" data-sales-trend-compare>{{ $trendComparisonLabel }}</span>
                                <select class="sales-trend-select" data-sales-trend-metric aria-label="Pilih indikator tren">
                                    <option value="net_sales">Net Sales</option>
                                    <option value="estimated_payout">Est Penghasilan</option>
                                    <option value="net_profit">Laba Bersih</option>
                                    <option value="ad_spend">Iklan</option>
                                </select>
                            </div>
                        </div>
                        <div class="sales-trend-chart" data-sales-trend-chart-container>
                            <svg viewBox="0 0 1000 260" role="img" aria-label="Grafik tren kinerja" data-sales-trend-chart></svg>
                        </div>
                        <div class="sales-trend-legend" aria-label="Legenda grafik">
                            <span><i aria-hidden="true"></i>Periode berjalan</span>
                            <span><i class="previous" aria-hidden="true"></i>{{ $trendComparisonLabel }}</span>
                        </div>
                    </div>
                    <div class="sales-trend-insights">
                        <div class="sales-trend-insight">
                            <div class="sales-trend-insight-label">Hari terbaik</div>
                            <div class="sales-trend-insight-value">{{ $trendBestProfit ? $fmt($trendBestProfit['net_profit']) : '—' }}</div>
                            <div class="sales-trend-insight-meta"><span>{{ $trendBestProfit['label'] ?? '—' }}</span><strong>Laba bersih</strong></div>
                        </div>
                        <div class="sales-trend-insight">
                            <div class="sales-trend-insight-label">Hari terlemah</div>
                            <div class="sales-trend-insight-value">{{ $trendWorstProfit ? $fmt($trendWorstProfit['net_profit']) : '—' }}</div>
                            <div class="sales-trend-insight-meta"><span>{{ $trendWorstProfit['label'] ?? '—' }}</span><strong>{{ $trendWorstProfit && $trendWorstProfit['net_profit'] < 0 ? 'Laba negatif' : 'Perlu dipantau' }}</strong></div>
                        </div>
                        <div class="sales-trend-insight {{ $trendNegativeDays > 0 ? 'sales-trend-insight--alert' : '' }}">
                            <div class="sales-trend-insight-label">Anomali laba</div>
                            <div class="sales-trend-insight-value">{{ $trendNegativeDays }} hari</div>
                            <div class="sales-trend-insight-meta"><span>{{ $trendNegativeDays > 0 ? 'Laba bersih negatif' : 'Tidak ada laba negatif' }}</span><strong>{{ $trendCurrentRows->count() }} hari aktif</strong></div>
                        </div>
                        <div class="sales-trend-insight">
                            <div class="sales-trend-insight-label">Rata-rata Net Sales</div>
                            <div class="sales-trend-insight-value">{{ $fmt($trendAverageNetSales) }}</div>
                            <div class="sales-trend-insight-meta"><span>Per hari aktif</span><strong>{{ $trendCurrentRows->count() }} hari</strong></div>
                        </div>
                    </div>
                </div>
            </div>
            <script type="application/json" id="sales-trend-data">@json($trendPayload)</script>
        </section>
    @endif
    @if ($activeComparison)
        <section class="card sales-card sales-comparison-section shadow-sm mb-3" aria-labelledby="sales-period-comparison-title">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <div class="sales-kicker mb-1">Perbandingan periode</div>
                    <h2 id="sales-period-comparison-title" class="sales-section-title mb-1">Perbandingan kinerja penjualan</h2>
                    <div class="sales-section-subtitle d-block">Aktif sebagai baseline, dengan perubahan terhadap setiap periode pembanding.</div>
                </div>
                <span class="badge sales-badge rounded-pill px-3 py-2"><i class="bi bi-columns-gap me-1" aria-hidden="true"></i>{{ count($salesComparisonPeriods) }} periode</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle sales-table sales-sales-comparison-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3" scope="col">Metrik</th>
                            @foreach ($salesComparisonPeriods as $periodIndex => $period)
                                <th class="text-end {{ $periodIndex === 0 ? 'sales-period-current' : '' }}" scope="col">
                                    <span class="sales-period-label">{{ $period['label'] }}</span>
                                    <span class="sales-period-range">{{ $period['from'] && $period['to'] ? $dateRangeLabel($period['from'], $period['to']) : '—' }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php $salesComparisonGroup = null; @endphp
                        @foreach ($salesComparisonRows as $row)
                            @if (($row['group'] ?? null) !== null && $row['group'] !== $salesComparisonGroup)
                                <tr class="sales-table-section-row" role="presentation">
                                    <td colspan="{{ 1 + count($salesComparisonPeriods) }}">{{ $row['group'] }}</td>
                                </tr>
                                @php $salesComparisonGroup = $row['group']; @endphp
                            @endif
                            @php
                                $comparisonDetailId = 'sales-comparison-detail-'.$loop->index;
                                $comparisonChartUnit = in_array($row['key'], ['net_margin', 'acos', 'ctr', 'cvr'], true)
                                    ? 'percent'
                                    : (in_array($row['key'], ['roas'], true) ? 'multiple' : (in_array($row['key'], ['orders', 'qty', 'impressions', 'clicks'], true) ? 'number' : 'currency'));
                                $comparisonChartData = collect($salesComparisonPeriods)->reverse()->values()->map(function ($period) use ($row, $dateRangeLabel) {
                                    $value = $period['metrics'][$row['key']] ?? null;

                                    return [
                                        'label' => $period['label'],
                                        'range' => $period['from'] && $period['to'] ? $dateRangeLabel($period['from'], $period['to']) : '—',
                                        'value' => $value,
                                        'display' => $value === null ? '—' : $row['format']($value),
                                    ];
                                })->values()->all();
                            @endphp
                            <tr class="sales-sales-comparison-row" data-sales-comparison-row data-sales-comparison-detail-id="{{ $comparisonDetailId }}">
                                <td class="ps-3 sales-comparison-metric">
                                    <button type="button" class="sales-comparison-metric-toggle" data-sales-comparison-toggle aria-expanded="false" aria-controls="{{ $comparisonDetailId }}" aria-label="Lihat tren {{ $row['label'] }}">
                                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                        <span>{{ $row['label'] }}</span>
                                    </button>
                                </td>
                                @foreach ($salesComparisonPeriods as $periodIndex => $period)
                                    @php $comparisonValue = $period['metrics'][$row['key']] ?? null; @endphp
                                    @php
                                        $currentValue = $salesComparisonPeriods[0]['metrics'][$row['key']] ?? null;
                                        $comparisonArrow = null;
                                        $comparisonArrowTitle = null;
                                        $comparisonPercentageLabel = null;
                                        $comparisonDifferenceLabel = null;
                                        $comparisonDeltaTone = 'is-neutral';
                                        if ($periodIndex > 0 && is_numeric($currentValue) && is_numeric($comparisonValue)) {
                                            $comparisonArrow = (float) $currentValue > (float) $comparisonValue
                                                ? 'bi-arrow-up-right'
                                                : ((float) $currentValue < (float) $comparisonValue ? 'bi-arrow-down-right' : 'bi-arrow-left-right');
                                            $comparisonDeltaTone = (float) $currentValue > (float) $comparisonValue
                                                ? 'is-up'
                                                : ((float) $currentValue < (float) $comparisonValue ? 'is-down' : 'is-neutral');
                                            $comparisonArrowTitle = (float) $currentValue > (float) $comparisonValue
                                                ? 'Aktif lebih tinggi'
                                                : ((float) $currentValue < (float) $comparisonValue ? 'Aktif lebih rendah' : 'Nilai sama');
                                            $comparisonBase = abs((float) $comparisonValue);
                                            $comparisonPercentageLabel = $comparisonBase > 0
                                                ? number_format(abs((((float) $currentValue - (float) $comparisonValue) / $comparisonBase) * 100), 1, ',', '.').'%'
                                                : ((float) $currentValue === 0.0 ? '0,0%' : 'Baru');
                                            $comparisonDifference = (float) $currentValue - (float) $comparisonValue;
                                            $comparisonDifferencePrefix = $comparisonDifference > 0 ? '+' : ($comparisonDifference < 0 ? '−' : '±');
                                            $comparisonDifferenceValue = in_array($row['key'], ['net_margin', 'acos', 'ctr', 'cvr'], true)
                                                ? number_format(abs($comparisonDifference), 1, ',', '.').' pt'
                                                : $row['format'](abs($comparisonDifference));
                                            $comparisonDifferenceLabel = '('.$comparisonDifferencePrefix.$comparisonDifferenceValue.')';
                                        }
                                    @endphp
                                    <td class="text-end {{ $periodIndex === 0 ? 'sales-period-current' : '' }}">
                                        <span class="sales-comparison-cell">
                                            <span class="sales-comparison-value-row">
                                                {{ $comparisonValue === null ? '—' : $row['format']($comparisonValue) }}
                                                @if ($comparisonArrow)
                                                    <span class="sales-comparison-delta {{ $comparisonDeltaTone }}" title="{{ $comparisonArrowTitle }} dibanding {{ $period['label'] }}">
                                                        <i class="bi {{ $comparisonArrow }}" aria-hidden="true"></i>{{ $comparisonPercentageLabel }}
                                                    </span>
                                                @endif
                                            </span>
                                            @if ($comparisonDifferenceLabel)
                                                <span class="sales-comparison-difference {{ $comparisonDeltaTone }}">{{ $comparisonDifferenceLabel }}</span>
                                            @endif
                                        </span>
                                    </td>
                                @endforeach
                            </tr>
                            <tr id="{{ $comparisonDetailId }}" class="sales-sales-comparison-detail" hidden>
                                <td colspan="{{ 1 + count($salesComparisonPeriods) }}">
                                    <div class="sales-comparison-chart-detail">
                                        <div class="sales-comparison-chart-title"><strong>{{ $row['label'] }}</strong><span>Perbandingan antarperiode</span></div>
                                        <div class="sales-comparison-chart">
                                            <svg viewBox="0 0 900 180" role="img" aria-label="Grafik {{ $row['label'] }} per periode" data-sales-comparison-chart data-sales-comparison-chart-unit="{{ $comparisonChartUnit }}"></svg>
                                        </div>
                                        <script type="application/json" data-sales-comparison-chart-data>@json($comparisonChartData)</script>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
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
                    <colgroup>
                        <col class="sales-daily-col-toggle">
                        <col class="sales-daily-col-date">
                        <col class="sales-daily-col-count">
                        <col class="sales-daily-col-units">
                        <col class="sales-daily-col-unit-order">
                        @for ($metricColumn = 0; $metricColumn < 8; $metricColumn++)
                            <col class="sales-daily-col-metric">
                        @endfor
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col" class="sales-index-column"><i class="bi bi-chevron-right" aria-hidden="true"></i><span class="visually-hidden">Rincian toko</span></th>
                            <th>Tanggal</th>
                            <th class="text-end">Pesanan</th>
                            <th class="text-end">Unit Terjual</th>
                            <th class="text-end">Unit / Order</th>
                            <th class="text-end">Gross Sales</th>
                            <th class="text-end">Net Sales</th>
                            <th class="text-end">Di Bayar</th>
                            <th class="text-end">Est Penghasilan</th>
                            <th class="text-end">COGS (HPP)</th>
                            <th class="text-end">Laba Kotor</th>
                            <th class="text-end">Iklan</th>
                            <th class="text-end">Laba Bersih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daily as $row)
                            @php
                                $dailyPayment = $paymentDailyByDay->get((string) $row->day);
                                $dailyBuyerPaid = (float) data_get($dailyPayment, 'buyer_paid', 0);
                                $dailyOrders = (int) ($row->orders ?? 0);
                                $dailyAov = fn ($value) => $dailyOrders > 0 ? $fmt((float) $value / $dailyOrders) : '—';
                            @endphp
                            <tr class="sales-daily-row">
                                <td class="sales-index-cell">
                                    <button type="button" class="sales-daily-toggle" data-sales-store-toggle="{{ $row->day }}" aria-expanded="false" aria-controls="sales-store-detail-{{ $row->day }}" aria-label="Buka rincian toko {{ $dateLabel($row->day) }}">
                                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                    </button>
                                </td>
                                <td><button class="sales-date-link" type="button" data-sales-order-detail-date="{{ $row->day }}">{{ $dateLabel($row->day) }}</button></td>
                                <td class="text-end">{{ number_format($row->orders) }}</td>
                                <td class="text-end">{{ number_format($row->qty) }}</td>
                                <td class="text-end">{{ number_format($row->avg_units_per_order, 2, ',', '.') }}</td>
                                <td class="text-end sales-table-metric"><div>{{ $fmt($row->subtotal) }}</div><div class="small text-muted">({{ $dailyAov($row->subtotal) }})</div></td>
                                <td class="text-end sales-table-metric"><div>{{ $fmt($row->net_total) }}</div><div class="small text-muted">({{ $dailyAov($row->net_total) }})</div></td>
                                <td class="text-end sales-table-metric"><div>{{ $fmt($dailyBuyerPaid) }}</div><div class="small text-muted">({{ $dailyAov($dailyBuyerPaid) }})</div></td>
                                <td class="text-end sales-table-metric"><div>{{ $fmt($row->estimated_payout) }}</div><div class="small text-muted">({{ $dailyAov($row->estimated_payout) }})</div></td>
                                <td class="text-end sales-table-metric"><div>{{ $fmt($row->cogs) }}</div><div class="small text-muted">({{ $dailyAov($row->cogs) }})</div></td>
                                <td class="text-end fw-semibold sales-table-metric"><div>{{ $fmt($row->gross_profit) }}</div><div class="small text-muted">({{ $dailyAov($row->gross_profit) }})</div></td>
                                <td class="text-end sales-table-metric"><div>{{ $fmt($row->ad_spend) }}</div><div class="small text-muted">({{ $dailyAov($row->ad_spend) }})</div></td>
                                <td class="text-end fw-semibold sales-table-metric"><div>{{ $fmt($row->net_profit) }}</div><div class="small text-muted">({{ $dailyAov($row->net_profit) }})</div></td>
                            </tr>
                            <tr id="sales-store-detail-{{ $row->day }}" class="sales-daily-store-detail" data-sales-store-items="{{ $row->day }}" hidden>
                                <td colspan="13">
                                    @php $storeRows = $storeDaily->get((string) $row->day, collect()); @endphp
                                    @if ($storeRows->isEmpty())
                                        <div class="small text-muted text-center py-2">Belum ada rincian toko.</div>
                                    @else
                                        <div class="sales-daily-store-shell">
                                            <table class="table table-sm align-middle sales-table sales-daily-store-table">
                                                <colgroup>
                                                    <col class="sales-daily-col-toggle">
                                                    <col class="sales-daily-col-date">
                                                    <col class="sales-daily-col-count">
                                                    <col class="sales-daily-col-units">
                                                    <col class="sales-daily-col-unit-order">
                                                    @for ($metricColumn = 0; $metricColumn < 8; $metricColumn++)
                                                        <col class="sales-daily-col-metric">
                                                    @endfor
                                                </colgroup>
                                                <tbody>
                                                    @foreach ($storeRows as $storeRow)
                                                        @php
                                                            $storeOrders = (int) ($storeRow->orders ?? 0);
                                                            $storeAov = fn ($value) => $storeOrders > 0 ? $fmt((float) $value / $storeOrders) : '—';
                                                        @endphp
                                                        <tr>
                                                        <td class="sales-daily-store-spacer" aria-hidden="true"></td>
                                                        <td class="sales-daily-store-name" title="{{ $storeRow->store_name }}">
                                                            <span class="sales-daily-store-name-inner"><i class="bi bi-shop" aria-hidden="true"></i><span>{{ $storeRow->store_name }}</span></span>
                                                        </td>
                                                        <td class="text-end">{{ number_format($storeRow->orders) }}</td>
                                                        <td class="text-end">{{ number_format($storeRow->qty) }}</td>
                                                        <td class="text-end">{{ number_format($storeRow->avg_units_per_order, 2, ',', '.') }}</td>
                                                        <td class="text-end sales-table-metric"><div>{{ $fmt($storeRow->subtotal) }}</div><div class="small text-muted">({{ $storeAov($storeRow->subtotal) }})</div></td>
                                                        <td class="text-end sales-table-metric"><div>{{ $fmt($storeRow->net_total) }}</div><div class="small text-muted">({{ $storeAov($storeRow->net_total) }})</div></td>
                                                        <td class="text-end sales-table-metric"><div>{{ $fmt($storeRow->buyer_paid) }}</div><div class="small text-muted">({{ $storeAov($storeRow->buyer_paid) }})</div></td>
                                                        <td class="text-end sales-table-metric"><div>{{ $fmt($storeRow->estimated_payout) }}</div><div class="small text-muted">({{ $storeAov($storeRow->estimated_payout) }})</div></td>
                                                        <td class="text-end sales-table-metric"><div>{{ $fmt($storeRow->cogs) }}</div><div class="small text-muted">({{ $storeAov($storeRow->cogs) }})</div></td>
                                                        <td class="text-end fw-semibold sales-table-metric"><div>{{ $fmt($storeRow->gross_profit) }}</div><div class="small text-muted">({{ $storeAov($storeRow->gross_profit) }})</div></td>
                                                        <td class="text-end sales-table-metric"><div>{{ $fmt($storeRow->ad_spend) }}</div><div class="small text-muted">({{ $storeAov($storeRow->ad_spend) }})</div></td>
                                                        <td class="text-end fw-semibold sales-table-metric"><div>{{ $fmt($storeRow->net_profit) }}</div><div class="small text-muted">({{ $storeAov($storeRow->net_profit) }})</div></td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </td>
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
            'kpiColumnClass' => 'col-xl',
            'kpis' => [
                ['label' => 'Penjualan Netto', 'value' => $currencyDisplay($activeProductKpi['net_sales']), 'note' => 'setelah diskon & promo seller', 'icon' => 'bi-graph-down-arrow', 'variant' => 'sales-kpi--success', 'comparisons' => $kpiComparisons($activeProductKpi['net_sales'], $previousMonthProductKpi['net_sales'], $previousPeriodProductKpi['net_sales'], $currencyDisplay)],
                ['label' => 'Pembayaran Pembeli', 'value' => $currencyDisplay($activeProductKpi['buyer_payment']), 'note' => 'nilai dibayar pembeli', 'icon' => 'bi-wallet2', 'comparisons' => $kpiComparisons($activeProductKpi['buyer_payment'], $previousMonthProductKpi['buyer_payment'], $previousPeriodProductKpi['buyer_payment'], $currencyDisplay)],
                ['label' => 'Order Produk', 'value' => number_format($activeProductKpi['orders']), 'note' => 'order unik', 'icon' => 'bi-receipt', 'comparisons' => $kpiComparisons($activeProductKpi['orders'], $previousMonthProductKpi['orders'], $previousPeriodProductKpi['orders'], $numberDisplay)],
                ['label' => 'Unit Terjual', 'value' => number_format($activeProductKpi['qty']), 'note' => 'unit pada periode', 'icon' => 'bi-stack', 'comparisons' => $kpiComparisons($activeProductKpi['qty'], $previousMonthProductKpi['qty'], $previousPeriodProductKpi['qty'], $numberDisplay)],
                ['label' => 'Margin Kontribusi', 'value' => $activeProductKpi['contribution_margin'] === null ? '—' : $percentDisplay($activeProductKpi['contribution_margin']), 'note' => 'setelah HPP dan iklan', 'icon' => 'bi-pie-chart', 'comparisons' => $kpiComparisons($activeProductKpi['contribution_margin'] ?? 0, $previousMonthProductKpi['contribution_margin'] ?? null, $previousPeriodProductKpi['contribution_margin'] ?? null, $percentDisplay, 'points')],
            ],
        ])
        @if ($activeComparison)
            <section class="card sales-card sales-comparison-section shadow-sm mb-3">
                <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-2">
                    <div>
                        <div class="sales-kicker mb-1">Perbandingan periode</div>
                        <h2 class="sales-section-title mb-1">Perbandingan kinerja produk</h2>
                        <div class="sales-section-subtitle">Aktif sebagai baseline, dengan perubahan terhadap setiap periode pembanding.</div>
                    </div>
                    <span class="badge sales-badge rounded-pill px-3 py-2"><i class="bi bi-columns-gap me-1" aria-hidden="true"></i>{{ count($productComparisonPeriods) }} periode</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table sales-product-comparison-table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Metrik</th>
                                @foreach ($productComparisonPeriods as $periodIndex => $period)
                                    <th class="text-end {{ $periodIndex === 0 ? 'sales-period-current' : '' }}">
                                        <span class="sales-period-label">{{ $period['label'] }}</span>
                                        <span class="sales-period-range">{{ $period['from'] && $period['to'] ? $dateRangeLabel($period['from'], $period['to']) : '—' }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $comparisonGroup = null;
                            @endphp
                            @foreach ($productComparisonRows as $row)
                                @if (($row['group'] ?? null) !== null && $row['group'] !== $comparisonGroup)
                                    <tr class="sales-table-section-row" role="presentation">
                                        <td colspan="{{ 1 + count($productComparisonPeriods) }}">{{ $row['group'] }}</td>
                                    </tr>
                                    @php
                                        $comparisonGroup = $row['group'];
                                    @endphp
                                @endif
                                <tr>
                                    <td class="ps-3 fw-semibold">{{ $row['label'] }}</td>
                                    @foreach ($productComparisonPeriods as $periodIndex => $period)
                                        @php
                                            $comparisonValue = $period['metrics'][$row['key']] ?? null;
                                            $currentValue = $productComparisonPeriods[0]['metrics'][$row['key']] ?? null;
                                            $comparisonMeta = $periodIndex > 0
                                                ? $comparisonCellMeta($currentValue, $comparisonValue, $row['format'], $row['delta_mode'] ?? 'relative')
                                                : null;
                                        @endphp
                                        <td class="text-end {{ $periodIndex === 0 ? 'sales-period-current' : '' }}">
                                            <span class="sales-comparison-cell">
                                                <span class="sales-comparison-value-row">
                                                    {{ $comparisonValue === null ? '—' : $row['format']($comparisonValue) }}
                                                    @if ($comparisonMeta)
                                                        <span class="sales-comparison-delta {{ $comparisonMeta['tone'] }}" title="{{ $comparisonMeta['arrow_title'] }} dibanding {{ $period['label'] }}">
                                                            <i class="bi {{ $comparisonMeta['arrow'] }}" aria-hidden="true"></i>{{ $comparisonMeta['percentage'] }}
                                                        </span>
                                                    @endif
                                                </span>
                                                @if ($comparisonMeta)
                                                    <span class="sales-comparison-difference {{ $comparisonMeta['tone'] }}">{{ $comparisonMeta['difference'] }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
        <section class="card sales-card shadow-sm mb-3">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <div class="sales-kicker mb-1">Kinerja kategori</div>
                    <h2 class="sales-section-title mb-1">Kinerja Penjualan per Kategori Item</h2>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <span class="badge sales-badge rounded-pill px-3 py-2">{{ number_format($categoryComparisonRows->count()) }} kategori</span>
                    <span class="badge sales-badge rounded-pill px-3 py-2">{{ number_format($activeProductKpi['products']) }} produk terjual</span>
                    <span class="badge sales-badge rounded-pill px-3 py-2">{{ number_format(data_get($activeMarketplaceCatalog, 'products', 0)) }} produk aktif</span>
                    <span class="badge sales-badge rounded-pill px-3 py-2">{{ number_format(data_get($activeMarketplaceCatalog, 'variants', 0)) }} variant aktif</span>
                </div>
            </div>
            @if ($categoryComparisonRows->isEmpty())
                <div class="sales-empty text-center">Belum ada penjualan per kategori pada periode ini.</div>
            @else
                <div class="sales-category-table-wrap">
                    <table class="table table-sm align-middle sales-table sales-category-summary-table mb-0">
                        <colgroup>
                            <col style="width: 4%">
                            <col style="width: 19%">
                            <col style="width: 6.5%">
                            <col style="width: 6.5%">
                            <col style="width: 7%">
                            <col style="width: 5%">
                            <col style="width: 4%">
                            <col style="width: 8%">
                            <col style="width: 10%">
                            <col style="width: 9%">
                            <col style="width: 7%">
                            <col style="width: 6%">
                            <col style="width: 8%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="ps-3" rowspan="2">No.</th>
                                <th rowspan="2">Kategori Item</th>
                                <th class="sales-category-table-group-head" colspan="3">Katalog Aktif</th>
                                <th class="sales-category-table-group-head" colspan="2">Transaksi</th>
                                <th class="sales-category-table-group-head" colspan="2">Pendapatan</th>
                                <th class="sales-category-table-group-head" colspan="4">Profitabilitas</th>
                            </tr>
                            <tr>
                                <th>Produk Aktif</th>
                                <th>Variant Aktif</th>
                                <th>Variant Tidak Terjual</th>
                                <th>Order</th>
                                <th>Unit</th>
                                <th>Kontribusi Penjualan</th>
                                <th>Penjualan Netto</th>
                                <th>Estimasi Penghasilan</th>
                                <th>Laba Kotor</th>
                                <th>Margin Kontribusi</th>
                                <th class="pe-3">Laba Bersih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categoryComparisonRows as $categoryRow)
                                @php
                                    $categoryKey = 'sales-category-comparison-'.$loop->index;
                                    $activeCategoryMetrics = $categoryRow['periods']['active']['metrics'];
                                @endphp
                                <tr class="sales-category-comparison-row" data-sales-category-comparison-row="{{ $categoryKey }}" tabindex="0" aria-controls="{{ $categoryKey }}-detail">
                                    <td class="ps-3 text-muted" data-label="No.">{{ $loop->iteration }}</td>
                                    <td class="fw-semibold sales-category-name" data-label="Kategori Item" title="{{ $categoryRow['name'] }}">
                                        <button type="button" class="sales-category-comparison-toggle" data-sales-category-comparison-toggle="{{ $categoryKey }}" aria-expanded="false" aria-controls="{{ $categoryKey }}-detail" aria-label="Buka detail kategori {{ $categoryRow['name'] }}">
                                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                            <span>{{ $categoryRow['name'] }}</span>
                                        </button>
                                    </td>
                                    <td data-label="Produk Aktif">{{ number_format($activeCategoryMetrics['active_products']) }}</td>
                                    <td data-label="Variant Aktif">{{ number_format($activeCategoryMetrics['active_variants']) }}</td>
                                    <td data-label="Variant Tidak Terjual">{{ number_format($activeCategoryMetrics['unsold_variants']) }}</td>
                                    <td data-label="Order">{{ number_format($activeCategoryMetrics['orders']) }}</td>
                                    <td data-label="Unit">{{ number_format($activeCategoryMetrics['qty']) }}</td>
                                    <td class="text-end" data-label="Kontribusi Penjualan">{{ $percentDisplay($categoryRow['share']) }}</td>
                                    <td class="text-end" data-label="Penjualan Netto">{{ $fmt($activeCategoryMetrics['net_sales']) }}</td>
                                    <td class="text-end" data-label="Estimasi Penghasilan">{{ $fmt($activeCategoryMetrics['estimated_payout']) }}</td>
                                    <td class="text-end" data-label="Laba Kotor">{{ $fmt($activeCategoryMetrics['gross_profit_payout']) }}</td>
                                    <td class="text-end" data-label="Margin Kontribusi">{{ $activeCategoryMetrics['contribution_margin'] === null ? '—' : $percentDisplay($activeCategoryMetrics['contribution_margin']) }}</td>
                                    <td class="text-end pe-3" data-label="Laba Bersih">{{ $fmt($activeCategoryMetrics['net_profit']) }}</td>
                                </tr>
                                <tr id="{{ $categoryKey }}-detail" class="sales-category-comparison-detail" data-sales-category-comparison-items="{{ $categoryKey }}" hidden>
                                    <td colspan="13">
                                        <div class="sales-category-comparison-detail-card">
                                            <div class="sales-category-detail-header d-flex align-items-start justify-content-between gap-2 mb-2">
                                                <div>
                                                    <div class="sales-kicker mb-1">Product comparison</div>
                                                    <h3 class="sales-section-title mb-0">Perbandingan kinerja produk</h3>
                                                </div>
                                                <span class="badge sales-badge sales-category-detail-badge rounded-pill px-3 py-2" title="{{ $categoryRow['name'] }}">{{ $categoryRow['name'] }}</span>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover align-middle sales-table sales-product-comparison-table mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th class="ps-3">Metrik</th>
                                                            @foreach ($categoryComparisonPeriods as $periodIndex => $period)
                                                                <th class="text-end {{ $periodIndex === 0 ? 'sales-period-current' : '' }}">
                                                                    <span class="sales-period-label">{{ $period['label'] }}</span>
                                                                    <span class="sales-period-range">{{ $period['from'] && $period['to'] ? $dateRangeLabel($period['from'], $period['to']) : '—' }}</span>
                                                                </th>
                                                            @endforeach
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php
                                                            $categoryComparisonGroup = null;
                                                        @endphp
                                                        @foreach ($categoryProductComparisonRows as $comparisonRow)
                                                            @if (($comparisonRow['group'] ?? null) !== null && $comparisonRow['group'] !== $categoryComparisonGroup)
                                                                <tr class="sales-table-section-row" role="presentation">
                                                                    <td colspan="{{ 1 + $categoryComparisonPeriods->count() }}">{{ $comparisonRow['group'] }}</td>
                                                                </tr>
                                                                @php
                                                                    $categoryComparisonGroup = $comparisonRow['group'];
                                                                @endphp
                                                            @endif
                                                            <tr>
                                                                <td class="ps-3 fw-semibold">{{ $comparisonRow['label'] }}</td>
                                                                @foreach ($categoryComparisonPeriods as $periodIndex => $period)
                                                                    @php
                                                                        $categoryComparisonValue = $categoryRow['periods'][$period['key']]['metrics'][$comparisonRow['key']] ?? null;
                                                                        $categoryCurrentValue = $categoryRow['periods']['active']['metrics'][$comparisonRow['key']] ?? null;
                                                                        $categoryComparisonMeta = $periodIndex > 0
                                                                            ? $comparisonCellMeta($categoryCurrentValue, $categoryComparisonValue, $comparisonRow['format'], $comparisonRow['delta_mode'] ?? 'relative')
                                                                            : null;
                                                                    @endphp
                                                                    <td class="text-end {{ $periodIndex === 0 ? 'sales-period-current' : '' }}">
                                                                        <span class="sales-comparison-cell">
                                                                            <span class="sales-comparison-value-row">
                                                                                {{ $categoryComparisonValue === null ? '—' : $comparisonRow['format']($categoryComparisonValue) }}
                                                                                @if ($categoryComparisonMeta)
                                                                                    <span class="sales-comparison-delta {{ $categoryComparisonMeta['tone'] }}" title="{{ $categoryComparisonMeta['arrow_title'] }} dibanding {{ $period['label'] }}">
                                                                                        <i class="bi {{ $categoryComparisonMeta['arrow'] }}" aria-hidden="true"></i>{{ $categoryComparisonMeta['percentage'] }}
                                                                                    </span>
                                                                                @endif
                                                                            </span>
                                                                            @if ($categoryComparisonMeta)
                                                                                <span class="sales-comparison-difference {{ $categoryComparisonMeta['tone'] }}">{{ $categoryComparisonMeta['difference'] }}</span>
                                                                            @endif
                                                                        </span>
                                                                    </td>
                                                                @endforeach
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
        @php
            $productAnalysisUnsoldVariants = max(
                (int) data_get($activeMarketplaceCatalog, 'variants', 0) - (int) ($activeProductKpi['variants'] ?? 0),
                0
            );
        @endphp
        <section class="card sales-card sales-product-analysis-section shadow-sm mb-3">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <div class="sales-kicker mb-1">Inteligensi portofolio</div>
                    <h2 class="sales-section-title mb-1">Analitik Portofolio Produk</h2>
                </div>
                <span class="badge sales-badge rounded-pill px-3 py-2">{{ number_format($productAnalysisCategoryCount) }} kategori · {{ number_format($topProductCount) }} produk terjual</span>
            </div>
            <div class="sales-product-analysis-grid">
                <div class="sales-product-analysis-kpi">
                    <span class="sales-product-analysis-kpi-label">Produk Terjual</span>
                    <span class="sales-product-analysis-kpi-value">{{ number_format($topProductCount) }}</span>
                </div>
                <div class="sales-product-analysis-kpi">
                    <span class="sales-product-analysis-kpi-label">Penjualan Netto</span>
                    <span class="sales-product-analysis-kpi-value is-positive">{{ $fmt($productAnalysisNetSales) }}</span>
                </div>
                <div class="sales-product-analysis-kpi">
                    <span class="sales-product-analysis-kpi-label">Est. Penghasilan</span>
                    <span class="sales-product-analysis-kpi-value is-positive">{{ $fmt($productAnalysisEstimatedPayout) }}</span>
                </div>
                <div class="sales-product-analysis-kpi">
                    <span class="sales-product-analysis-kpi-label">Laba Kotor</span>
                    <span class="sales-product-analysis-kpi-value {{ $productAnalysisGrossProfitPayout >= 0 ? 'is-positive' : 'is-danger' }}">{{ $fmt($productAnalysisGrossProfitPayout) }}</span>
                </div>
                <div class="sales-product-analysis-kpi">
                    <span class="sales-product-analysis-kpi-label">Laba Bersih</span>
                    <span class="sales-product-analysis-kpi-value {{ $productAnalysisNetProfit >= 0 ? 'is-positive' : 'is-danger' }}">{{ $fmt($productAnalysisNetProfit) }}</span>
                </div>
                <div class="sales-product-analysis-kpi">
                    <span class="sales-product-analysis-kpi-label">Margin Bersih</span>
                    <span class="sales-product-analysis-kpi-value {{ $productAnalysisNetMargin >= 20 ? 'is-positive' : 'is-warning' }}">{{ $productAnalysisEstimatedPayout > 0 ? $percentDisplay($productAnalysisNetMargin) : '—' }}</span>
                </div>
            </div>
            <div class="row g-3 sales-product-analysis-tables">
                <div class="col-lg-6">
                    <table class="table table-sm align-middle sales-table sales-product-analysis-table">
                        <thead><tr><th colspan="2">Skala &amp; kesehatan</th><th class="text-end">Nilai</th></tr></thead>
                        <tbody>
                            <tr><td class="analysis-label" colspan="2">Produk terjual</td><td class="text-end analysis-value">{{ number_format($topProductCount) }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">Variant tidak terjual</td><td class="text-end analysis-value">{{ number_format($productAnalysisUnsoldVariants) }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">Kategori terjual</td><td class="text-end analysis-value">{{ number_format($productAnalysisCategoryCount) }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">Penjualan Netto</td><td class="text-end analysis-value">{{ $productAnalysisNetSales > 0 ? $fmt($productAnalysisNetSales) : '—' }}</td></tr>
                            <tr>
                                <td class="analysis-label" colspan="2">Pembayaran Pembeli</td>
                                <td class="text-end analysis-value">
                                    <div>{{ $productAnalysisBuyerPayment > 0 ? $fmt($productAnalysisBuyerPayment) : '—' }}</div>
                                    @if ($productAnalysisNetSales > 0)
                                        <small class="text-muted">{{ $percentDisplay(($productAnalysisBuyerPayment / $productAnalysisNetSales) * 100) }} dari penjualan netto</small>
                                    @endif
                                </td>
                            </tr>
                            <tr><td class="analysis-label" colspan="2">Unit per Order</td><td class="text-end analysis-value">{{ $activeProductKpi['orders'] > 0 ? number_format($activeProductKpi['qty'] / $activeProductKpi['orders'], 2, ',', '.') : '—' }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">AOV Penjualan Netto</td><td class="text-end analysis-value">{{ $activeProductKpi['orders'] > 0 ? $fmt($activeProductKpi['aov_sales']) : '—' }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">AOV Pembayaran</td><td class="text-end analysis-value">{{ $activeProductKpi['orders'] > 0 ? $fmt($activeProductKpi['aov_payment']) : '—' }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">Coverage Mapping Internal</td><td class="text-end analysis-value">{{ $productAnalysisProducts->count() > 0 ? $percentDisplay(($productAnalysisMappedCount / $productAnalysisProducts->count()) * 100) : '—' }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">Coverage HPP</td><td class="text-end analysis-value">{{ $productAnalysisProducts->count() > 0 ? $percentDisplay($productAnalysisHppCoverage) : '—' }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">COGS (HPP)</td><td class="text-end analysis-value">{{ $productAnalysisHpp > 0 ? $fmtHpp($productAnalysisHpp) : '—' }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">Laba Bersih</td><td class="text-end analysis-value {{ $productAnalysisNetProfit >= 0 ? 'is-positive' : 'is-danger' }}">{{ $fmt($productAnalysisNetProfit) }}</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-lg-6">
                    <table class="table table-sm align-middle sales-table sales-product-analysis-table">
                        <thead><tr><th colspan="2">Kinerja Iklan</th><th class="text-end">Nilai</th></tr></thead>
                        <tbody>
                            <tr><td class="analysis-label" colspan="2">Produk Teratribusi Iklan</td><td class="text-end analysis-value">{{ number_format($productAnalysisAdProducts->count()) }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">Penjualan Atribusi Iklan</td><td class="text-end analysis-value">{{ $fmt($productAnalysisAdSales) }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">Coverage Penjualan Atribusi</td><td class="text-end analysis-value">{{ $productAnalysisAdSales > 0 && $productAnalysisNetSales > 0 ? $percentDisplay($productAnalysisAdSalesCoverage) : '—' }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">Biaya Iklan</td><td class="text-end analysis-value is-danger">{{ $fmt($productAnalysisAdSpend) }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">ACOS Blended</td><td class="text-end analysis-value">{{ $productAnalysisAdSales > 0 ? $percentDisplay(($productAnalysisAdSpend / $productAnalysisAdSales) * 100) : '—' }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">ROAS Blended</td><td class="text-end analysis-value">{{ $productAnalysisAdSpend > 0 ? $multipleDisplay($productAnalysisAdSales / $productAnalysisAdSpend) : '—' }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">CPA Blended</td><td class="text-end analysis-value">{{ $productAnalysisAdConversions > 0 ? $fmt($productAnalysisAdSpend / $productAnalysisAdConversions) : '—' }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">Konversi Iklan</td><td class="text-end analysis-value">{{ $productAnalysisAdConversions > 0 ? number_format($productAnalysisAdConversions) : '—' }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">Biaya Iklan / Produk Teratribusi</td><td class="text-end analysis-value">{{ $productAnalysisAdProducts->count() > 0 ? $fmt($productAnalysisAdSpend / $productAnalysisAdProducts->count()) : '—' }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">Kontribusi Pasca Iklan</td><td class="text-end analysis-value {{ $productAnalysisContributionProfit >= 0 ? 'is-positive' : 'is-danger' }}">{{ $fmt($productAnalysisContributionProfit) }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">Margin Kontribusi</td><td class="text-end analysis-value {{ $productAnalysisContributionMargin >= 20 ? 'is-positive' : 'is-warning' }}">{{ $productAnalysisContributionMargin !== 0 ? $percentDisplay($productAnalysisContributionMargin) : '—' }}</td></tr>
                            <tr><td class="analysis-label" colspan="2">Biaya Iklan / Penjualan Netto</td><td class="text-end analysis-value">{{ $productAnalysisAdSpend > 0 && $productAnalysisNetSales > 0 ? $percentDisplay(($productAnalysisAdSpend / $productAnalysisNetSales) * 100) : '—' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="sales-product-analysis-subsection">
                <div>
                    <div class="sales-kicker mb-1">Matriks keputusan</div>
                    <h3 class="sales-product-analysis-subsection-title">Prioritas tindakan portofolio</h3>
                </div>
                <div class="sales-product-analysis-legend" aria-label="Status matriks keputusan">
                    <span class="analysis-status-badge analysis-status-badge--scale">Scale</span>
                    <span class="analysis-status-badge analysis-status-badge--protect">Protect</span>
                    <span class="analysis-status-badge analysis-status-badge--grow">Grow</span>
                    <span class="analysis-status-badge analysis-status-badge--review">Review</span>
                </div>
            </div>
            <div class="sales-product-analysis-matrix-wrap">
                <table class="table table-sm align-middle sales-table sales-product-analysis-matrix">
                    <thead><tr><th style="width: 16%">Segmentasi</th><th class="text-end">Produk</th><th class="text-end">Variant</th><th class="text-end">Penjualan Netto</th><th class="text-end">Share</th><th class="text-end">Kontribusi</th><th class="text-end">Margin</th><th class="text-end">Penjualan Iklan</th><th class="text-end">ROAS</th><th class="text-end">Biaya Iklan</th></tr></thead>
                    <tbody>
                        @foreach ($productAnalysisMatrixRows as $matrixRow)
                            @php
                                $matrixKey = 'sales-product-analysis-'.$loop->index;
                                $matrixProducts = collect($matrixRow['products'] ?? []);
                                $matrixMarketplaceGroups = $matrixProducts
                                    ->groupBy(function ($product) {
                                        $marketplaceCode = trim((string) ($product->external_item_id ?? ''));

                                        return $marketplaceCode !== ''
                                            ? 'code:'.$marketplaceCode
                                            : 'title:'.(trim((string) ($product->marketplace_name ?: $product->name)) ?: 'Produk tanpa nama');
                                    })
                                    ->sortByDesc(fn ($group) => (float) $group->sum('net_sales'))
                                    ->values();
                            @endphp
                            <tr class="sales-product-analysis-matrix-row" data-sales-analysis-row="{{ $matrixKey }}" tabindex="0" aria-controls="{{ $matrixKey }}-detail">
                                <td class="analysis-status" data-label="Segmentasi">
                                    <button type="button" class="sales-product-analysis-matrix-toggle" data-sales-analysis-toggle="{{ $matrixKey }}" aria-expanded="false" aria-controls="{{ $matrixKey }}-detail" aria-label="Buka detail produk segmentasi {{ $matrixRow['label'] }}">
                                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                        <span class="analysis-status-badge analysis-status-badge--{{ $matrixRow['class'] }}">{{ $matrixRow['label'] }}</span>
                                    </button>
                                </td>
                                <td class="text-end analysis-number" data-label="Produk">{{ number_format($matrixRow['count']) }}</td>
                                <td class="text-end analysis-number" data-label="Variant">{{ number_format($matrixRow['variants_sold']) }}</td>
                                <td class="text-end analysis-number" data-label="Penjualan Netto">{{ $fmt($matrixRow['sales']) }}</td>
                                <td class="text-end analysis-share" data-label="Share">{{ $percentDisplay($matrixRow['sales_share']) }}</td>
                                <td class="text-end analysis-number" data-label="Kontribusi">{{ $fmt($matrixRow['contribution_profit']) }}</td>
                                <td class="text-end analysis-share" data-label="Margin">{{ $matrixRow['contribution_margin'] !== null ? $percentDisplay($matrixRow['contribution_margin']) : '—' }}</td>
                                <td class="text-end analysis-number" data-label="Penjualan Iklan">{{ $fmt($matrixRow['ad_sales']) }}</td>
                                <td class="text-end analysis-number" data-label="ROAS">{{ $matrixRow['ad_spend'] > 0 ? $multipleDisplay($matrixRow['roas']) : '—' }}</td>
                                <td class="text-end analysis-number" data-label="Biaya Iklan">{{ $fmt($matrixRow['ad_spend']) }}</td>
                            </tr>
                            <tr id="{{ $matrixKey }}-detail" class="sales-product-analysis-detail" data-sales-analysis-items="{{ $matrixKey }}" hidden>
                                <td colspan="10">
                                    <div class="sales-product-analysis-detail-card">
                                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                            <div>
                                                <div class="sales-kicker mb-1">Detail segmentasi</div>
                                                <h3 class="sales-section-title mb-0">Produk {{ $matrixRow['label'] }}</h3>
                                            </div>
                                            <span class="badge sales-badge rounded-pill px-3 py-2">{{ number_format($matrixMarketplaceGroups->count()) }} kode marketplace · {{ number_format($matrixProducts->count()) }} variant</span>
                                        </div>
                                        @if ($matrixProducts->isEmpty())
                                            <div class="sales-empty text-center py-3">Belum ada produk pada segmentasi ini.</div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover align-middle sales-table sales-product-analysis-detail-table mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th class="ps-3">Produk</th>
                                                            <th>Kode Produk</th>
                                                            <th>Kode Marketplace</th>
                                                            <th class="text-end">HPP / Unit</th>
                                                            <th class="text-end">Order</th>
                                                            <th class="text-end">Unit</th>
                                                            <th class="text-end">Penjualan Netto</th>
                                                            <th class="text-end">Pembayaran</th>
                                                            <th class="text-end">Kontribusi</th>
                                                            <th class="text-end">Margin</th>
                                                            <th class="text-end">Biaya Iklan</th>
                                                            <th class="text-end pe-3">ROAS</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($matrixMarketplaceGroups as $marketplaceProducts)
                                                            @php
                                                                $marketplaceProducts = $marketplaceProducts->values();
                                                                $marketplaceProduct = $marketplaceProducts->first();
                                                                $analysisProductTitle = trim((string) ($marketplaceProduct->marketplace_name ?: $marketplaceProduct->name)) ?: 'Produk tanpa nama';
                                                                $analysisProductImage = trim((string) ($marketplaceProduct->image_url ?? ''));
                                                                $analysisInternalCodes = $marketplaceProducts
                                                                    ->map(function ($product) {
                                                                        $code = trim((string) ($product->sku ?? ''));

                                                                        return $code !== '' && $code !== '-'
                                                                            ? $code
                                                                            : 'ID '.($product->internal_item_id ?? '—');
                                                                    })
                                                                    ->unique()
                                                                    ->values();
                                                                $analysisInternalCode = $analysisInternalCodes->count() > 1
                                                                    ? $analysisInternalCodes->count().' kode internal'
                                                                    : ($analysisInternalCodes->first() ?: '—');
                                                                $analysisInternalCodeTitle = $analysisInternalCodes->implode(', ');
                                                                $analysisMarketplaceCode = trim((string) ($marketplaceProduct->external_item_id ?? '')) ?: '—';
                                                                $analysisOrderKeys = $marketplaceProducts
                                                                    ->flatMap(fn ($product) => preg_split('/,/', (string) ($product->order_keys ?? ''), -1, PREG_SPLIT_NO_EMPTY))
                                                                    ->map(fn ($key) => trim((string) $key))
                                                                    ->filter()
                                                                    ->unique();
                                                                $analysisQty = (int) $marketplaceProducts->sum('qty');
                                                                $analysisHppTotal = (float) $marketplaceProducts->sum(fn ($product) => (float) ($product->hpp ?? 0) * (int) ($product->qty ?? 0));
                                                                $analysisNetSales = (float) $marketplaceProducts->sum('net_sales');
                                                                $analysisBuyerPayment = (float) $marketplaceProducts->sum('buyer_payment');
                                                                $analysisContributionProfit = (float) $marketplaceProducts->filter(fn ($product) => $product->contribution_profit !== null)->sum('contribution_profit');
                                                                $analysisAdProducts = $marketplaceProducts->filter(fn ($product) => $product->ad_spend_matched ?? false);
                                                                $analysisAdSpend = (float) $analysisAdProducts->sum('ad_spend');
                                                                $analysisAdSales = (float) $analysisAdProducts->sum('ad_sales');
                                                            @endphp
                                                            <tr>
                                                                <td class="ps-3">
                                                                    <div class="d-flex align-items-center gap-2 min-w-0">
                                                                        <span class="sales-product-marketplace-thumb" tabindex="0" aria-label="{{ $analysisProductTitle }}">
                                                                            @if ($analysisProductImage !== '')
                                                                                <img src="{{ $analysisProductImage }}" alt="{{ $analysisProductTitle }}" loading="lazy" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                                                                                <span class="sales-product-image-fallback" hidden><i class="bi bi-image" aria-hidden="true"></i></span>
                                                                            @else
                                                                                <span class="sales-product-image-fallback"><i class="bi bi-image" aria-hidden="true"></i></span>
                                                                            @endif
                                                                            <span class="sales-product-image-preview-title">{{ $analysisProductTitle }}</span>
                                                                        </span>
                                                                        <span class="min-w-0">
                                                                            <span class="sales-product-analysis-detail-title" title="{{ $analysisProductTitle }}">{{ $analysisProductTitle }}</span>
                                                                            <span class="small text-muted">{{ number_format($marketplaceProducts->count()) }} variant internal</span>
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td><span class="sales-product-analysis-detail-code" title="{{ $analysisInternalCodeTitle }}">{{ $analysisInternalCode }}</span></td>
                                                                <td><span class="sales-product-analysis-detail-code" title="{{ $analysisMarketplaceCode }}">{{ $analysisMarketplaceCode }}</span></td>
                                                                <td class="text-end">{{ $analysisHppTotal > 0 && $analysisQty > 0 ? $fmtHpp($analysisHppTotal / $analysisQty) : '—' }}</td>
                                                                <td class="text-end">{{ number_format($analysisOrderKeys->count()) }}</td>
                                                                <td class="text-end">{{ number_format($analysisQty) }}</td>
                                                                <td class="text-end fw-semibold">{{ $fmt($analysisNetSales) }}</td>
                                                                <td class="text-end fw-semibold">{{ $fmt($analysisBuyerPayment) }}</td>
                                                                <td class="text-end">{{ $fmt($analysisContributionProfit) }}</td>
                                                                <td class="text-end">{{ $analysisNetSales > 0 ? $percentDisplay(($analysisContributionProfit / $analysisNetSales) * 100) : '—' }}</td>
                                                                <td class="text-end">{{ $analysisAdProducts->isNotEmpty() ? $fmt($analysisAdSpend) : '—' }}</td>
                                                                <td class="text-end pe-3">{{ $analysisAdSpend > 0 ? $multipleDisplay($analysisAdSales / $analysisAdSpend) : '—' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <section class="card sales-card shadow-sm">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <div class="sales-kicker mb-1">Daftar kinerja produk</div>
                    <h2 class="sales-section-title mb-1">Analisis Produk Terjual Marketplace</h2>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <span class="badge sales-badge rounded-pill px-3 py-2">{{ number_format($productAnalysisCategoryCount) }} kategori</span>
                    <span class="badge sales-badge rounded-pill px-3 py-2">{{ number_format($topProductCount) }} produk</span>
                    <span class="badge sales-badge rounded-pill px-3 py-2">{{ number_format($activeProductKpi['variants']) }} variant</span>
                </div>
            </div>
            <div class="sales-product-hierarchy" aria-label="Hierarki detail produk">
                <span><i class="bi bi-collection" aria-hidden="true"></i>Kategori item</span>
                <span><i class="bi bi-shop" aria-hidden="true"></i>Kode marketplace</span>
                <span><i class="bi bi-diagram-3" aria-hidden="true"></i>Variant internal</span>
            </div>
            @if ($products->isEmpty())
                <div class="sales-empty text-center"><i class="bi bi-box-seam d-block fs-3 mb-2"></i>Belum ada detail produk pada periode ini.</div>
            @else
                <div class="table-responsive sales-product-table-wrap">
                    <table class="table table-sm table-hover align-middle sales-table sales-product-table sales-promotion-table">
                        <colgroup>
                            <col style="width: 4%">
                            <col style="width: 24%">
                            <col style="width: 7%">
                            <col style="width: 4%">
                            <col style="width: 4%">
                            <col style="width: 8%">
                            <col style="width: 8%">
                            <col style="width: 9%">
                            <col style="width: 7%">
                            <col style="width: 7%">
                            <col style="width: 5%">
                            <col style="width: 5%">
                            <col style="width: 7%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col" class="sales-index-column" rowspan="2">No.</th>
                                <th rowspan="2">Produk / Variant</th>
                                <th class="sales-product-table-group-head" colspan="6">Kinerja Penjualan</th>
                                <th class="sales-product-table-group-head" colspan="5">Kinerja Iklan</th>
                            </tr>
                            <tr>
                                <th class="text-end" title="Variant: HPP terakhir per unit · Marketplace/kategori: total HPP berdasarkan unit">HPP</th>
                                <th class="text-end">Order</th>
                                <th class="text-end">Unit</th>
                                <th class="text-end">Penjualan</th>
                                <th class="text-end">Penjualan Netto</th>
                                <th class="text-end">Pembayaran</th>
                                <th class="text-end">Biaya Iklan</th>
                                <th class="text-end">Penjualan Iklan</th>
                                <th class="text-end">ACOS</th>
                                <th class="text-end">ROAS</th>
                                <th class="text-end pe-3">CPA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $productGroups = $products
                                    ->groupBy(fn ($product) => $product->category_name ?: 'Tanpa kategori')
                                    ->sortByDesc(fn ($group) => (float) $group->sum('sales'));
                            @endphp
                            @foreach ($productGroups as $categoryName => $categoryProducts)
                                @php
                                    $categoryNumber = $loop->iteration;
                                    $categoryKey = 'sales-product-category-'.$loop->index;
                                    $categoryProducts = $categoryProducts->values();
                                    $uniqueOrderKeys = fn ($rows) => collect($rows)
                                        ->flatMap(fn ($product) => preg_split('/,/', (string) ($product->order_keys ?? ''), -1, PREG_SPLIT_NO_EMPTY))
                                        ->filter()
                                        ->unique()
                                        ->values();
                                    $marketplaceGroups = $categoryProducts
                                        ->groupBy(function ($product) {
                                            $productCode = trim((string) ($product->external_item_id ?? ''));

                                            return $productCode !== ''
                                                ? 'code:'.$productCode
                                                : 'title:'.(trim((string) ($product->marketplace_name ?: $product->name)) ?: 'Produk tanpa nama');
                                        })
                                        ->sortByDesc(fn ($group) => (float) $group->sum('sales'))
                                        ->values();
                                    $categoryItemIds = $marketplaceGroups->keys()->map(fn ($index) => $categoryKey.'-marketplace-'.$index)->implode(' ');
                                    $category = $categoryProducts->first();
                                    $categoryCode = trim((string) ($category->category_code ?? ''));
                                    $categoryOrders = $uniqueOrderKeys($categoryProducts)->count();
                                    $categoryQty = (int) $categoryProducts->sum('qty');
                                    $categorySales = (float) $categoryProducts->sum('sales');
                                    $categoryNetSales = (float) $categoryProducts->sum('net_sales');
                                    $categoryBuyerPayment = (float) $categoryProducts->sum('buyer_payment');
                                    $categoryContributionProfit = (float) $categoryProducts->filter(fn ($product) => $product->contribution_profit !== null)->sum('contribution_profit');
                                    $categoryContributionMargin = $categoryNetSales > 0 ? ($categoryContributionProfit / $categoryNetSales) * 100 : null;
                                    $categoryAdProducts = $categoryProducts->filter(fn ($product) => $product->ad_spend_matched ?? false);
                                    $categorySpend = (float) $categoryAdProducts->sum('ad_spend');
                                    $categoryAdSales = (float) $categoryAdProducts->sum('ad_sales');
                                    $categoryAdConversions = (int) $categoryAdProducts->sum('ad_conversions');
                                    $categoryHppTotal = (float) $categoryProducts->sum(fn ($product) => (float) ($product->hpp ?? 0) * (int) ($product->qty ?? 0));
                                @endphp
                                <tr class="sales-product-group-row">
                                    <td class="sales-index-cell" aria-label="Kategori {{ $categoryNumber }}">{{ $categoryNumber }}</td>
                                    <td>
                                        <button type="button" class="sales-product-category-toggle" data-sales-product-category-toggle="{{ $categoryKey }}" aria-expanded="false" aria-controls="{{ $categoryItemIds }}">
                                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                            <span>
                                                <span class="sales-product-group-title">{{ $categoryCode !== '' ? $categoryCode.' · ' : '' }}{{ $categoryName }}</span>
                                                <span class="sales-product-group-meta d-inline-flex align-items-center gap-1">
                                                    <span class="sales-product-count-badge">{{ number_format($marketplaceGroups->count()) }} MP</span>
                                                    <span class="sales-product-count-badge">{{ number_format($categoryProducts->count()) }} item</span>
                                                </span>
                                            </span>
                                        </button>
                                    </td>
                                    <td class="text-end fw-semibold"><div>{{ $categoryHppTotal > 0 ? $fmtHpp($categoryHppTotal) : '—' }}</div><div class="small text-muted">Total HPP</div></td>
                                    <td class="text-end">{{ number_format($categoryOrders) }}</td>
                                    <td class="text-end">{{ number_format($categoryQty) }}</td>
                                    <td class="text-end fw-semibold">{{ $fmt($categorySales) }}</td>
                                    <td class="text-end fw-semibold"><div>{{ $fmt($categoryNetSales) }}</div><div class="small text-muted">M {{ $categoryContributionMargin !== null ? $percentDisplay($categoryContributionMargin) : '—' }}</div></td>
                                    <td class="text-end fw-semibold">{{ $fmt($categoryBuyerPayment) }}</td>
                                    <td class="text-end text-danger fw-semibold">{{ $fmt($categorySpend) }}</td>
                                    <td class="text-end text-danger fw-semibold">{{ $fmt($categoryAdSales) }}</td>
                                    <td class="text-end">{{ $categoryAdSales > 0 ? $percentDisplay(($categorySpend / $categoryAdSales) * 100) : '—' }}</td>
                                    <td class="text-end">{{ $categorySpend > 0 ? $multipleDisplay($categoryAdSales / $categorySpend) : '—' }}</td>
                                    <td class="text-end pe-3">{{ $categoryAdConversions > 0 ? $fmt($categorySpend / $categoryAdConversions) : '—' }}</td>
                                </tr>
                                @foreach ($marketplaceGroups as $marketplaceProducts)
                                    @php
                                        $marketplaceNumber = $loop->iteration;
                                        $marketplaceKey = $categoryKey.'-marketplace-'.$loop->index;
                                        $marketplaceProducts = $marketplaceProducts->values();
                                        $marketplaceItemIds = $marketplaceProducts->keys()->map(fn ($index) => $marketplaceKey.'-items-'.$index)->implode(' ');
                                        $marketplace = $marketplaceProducts->first();
                                        $marketplaceImage = trim((string) ($marketplace->image_url ?? ''));
                                        $marketplaceCode = trim((string) ($marketplace->external_item_id ?? ''));
                                        $marketplaceTitle = trim((string) ($marketplace->marketplace_name ?: $marketplace->name)) ?: 'Produk tanpa nama';
                                        $marketplaceOrders = $uniqueOrderKeys($marketplaceProducts)->count();
                                        $marketplaceQty = (int) $marketplaceProducts->sum('qty');
                                        $marketplaceSales = (float) $marketplaceProducts->sum('sales');
                                        $marketplaceNetSales = (float) $marketplaceProducts->sum('net_sales');
                                        $marketplaceBuyerPayment = (float) $marketplaceProducts->sum('buyer_payment');
                                        $marketplaceContributionProfit = (float) $marketplaceProducts->filter(fn ($product) => $product->contribution_profit !== null)->sum('contribution_profit');
                                        $marketplaceContributionMargin = $marketplaceNetSales > 0 ? ($marketplaceContributionProfit / $marketplaceNetSales) * 100 : null;
                                        $marketplaceAdProducts = $marketplaceProducts->filter(fn ($product) => $product->ad_spend_matched ?? false);
                                        $marketplaceSpend = (float) $marketplaceAdProducts->sum('ad_spend');
                                        $marketplaceAdSales = (float) $marketplaceAdProducts->sum('ad_sales');
                                        $marketplaceAdConversions = (int) $marketplaceAdProducts->sum('ad_conversions');
                                        $marketplaceHppTotal = (float) $marketplaceProducts->sum(fn ($product) => (float) ($product->hpp ?? 0) * (int) ($product->qty ?? 0));
                                    @endphp
                                    <tr id="{{ $marketplaceKey }}" class="sales-product-marketplace-row" data-sales-product-category-items="{{ $categoryKey }}" hidden>
                                        <td class="sales-index-cell sales-product-item-index" aria-label="Marketplace {{ $categoryNumber }}.{{ $marketplaceNumber }}"><span class="sales-product-item-number">{{ $categoryNumber }}.{{ $marketplaceNumber }}</span></td>
                                        <td class="fw-semibold">
                                            <button type="button" class="sales-product-marketplace-toggle" data-sales-product-marketplace-toggle="{{ $marketplaceKey }}" aria-expanded="false" aria-controls="{{ $marketplaceItemIds }}">
                                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                                <span class="sales-product-marketplace-title">
                                                    <span class="sales-product-marketplace-thumb" tabindex="0" aria-label="{{ $marketplaceTitle }}">
                                                        @if ($marketplaceImage !== '')
                                                            <img src="{{ $marketplaceImage }}" alt="{{ $marketplaceTitle }}" loading="lazy" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                                                            <span class="sales-product-image-fallback" hidden><i class="bi bi-image" aria-hidden="true"></i></span>
                                                        @else
                                                            <span class="sales-product-image-fallback"><i class="bi bi-image" aria-hidden="true"></i></span>
                                                        @endif
                                                        <span class="sales-product-image-preview-title">{{ $marketplaceTitle }}</span>
                                                    </span>
                                                    @if ($marketplaceCode !== '')
                                                        <span class="sales-product-marketplace-code" title="Kode produk marketplace">{{ $marketplaceCode }}</span>
                                                    @endif
                                                    <span class="sales-product-count-badge">{{ number_format($marketplaceProducts->count()) }} varian</span>
                                                </span>
                                            </button>
                                        </td>
                                        <td class="text-end fw-semibold"><div>{{ $marketplaceHppTotal > 0 ? $fmtHpp($marketplaceHppTotal) : '—' }}</div><div class="small text-muted">Total HPP</div></td>
                                        <td class="text-end">{{ number_format($marketplaceOrders) }}</td>
                                        <td class="text-end">{{ number_format($marketplaceQty) }}</td>
                                        <td class="text-end fw-semibold">{{ $fmt($marketplaceSales) }}</td>
                                        <td class="text-end fw-semibold"><div>{{ $fmt($marketplaceNetSales) }}</div><div class="small text-muted">M {{ $marketplaceContributionMargin !== null ? $percentDisplay($marketplaceContributionMargin) : '—' }}</div></td>
                                        <td class="text-end fw-semibold">{{ $fmt($marketplaceBuyerPayment) }}</td>
                                        <td class="text-end text-danger fw-semibold">{{ $fmt($marketplaceSpend) }}</td>
                                        <td class="text-end text-danger fw-semibold">{{ $fmt($marketplaceAdSales) }}</td>
                                        <td class="text-end">{{ $marketplaceAdSales > 0 ? $percentDisplay(($marketplaceSpend / $marketplaceAdSales) * 100) : '—' }}</td>
                                        <td class="text-end">{{ $marketplaceSpend > 0 ? $multipleDisplay($marketplaceAdSales / $marketplaceSpend) : '—' }}</td>
                                        <td class="text-end pe-3">{{ $marketplaceAdConversions > 0 ? $fmt($marketplaceSpend / $marketplaceAdConversions) : '—' }}</td>
                                    </tr>
                                    @foreach ($marketplaceProducts as $product)
                                        <tr id="{{ $marketplaceKey }}-items-{{ $loop->index }}" class="sales-product-internal-row" data-sales-product-category-items="{{ $categoryKey }}" data-sales-product-marketplace-items="{{ $marketplaceKey }}" hidden>
                                            <td class="sales-index-cell sales-product-item-index sales-product-internal-index" aria-label="Variant internal"><span class="sales-product-variant-marker" aria-hidden="true"></span></td>
                                            <td class="fw-semibold sales-product-internal-cell">
                                                @php
                                                    $internalProductCode = trim((string) ($product->sku ?? ''));
                                                    $internalProductCode = $internalProductCode !== '' && $internalProductCode !== '-'
                                                        ? $internalProductCode
                                                        : 'ID '.($product->internal_item_id ?? '—');
                                                @endphp
                                                <button type="button" class="sales-product-link" data-sales-product-name="{{ $product->name }}" data-sales-product-sku="{{ $product->sku }}" data-sales-product-internal-item-id="{{ $product->internal_item_id ?? '' }}" title="Lihat pesanan item internal: {{ $internalProductCode }}">
                                                    <span class="sales-product-internal-code" title="{{ $internalProductCode }}">{{ $internalProductCode }}</span>
                                                </button>
                                            </td>
                                            <td class="text-end fw-semibold"><div>{{ (float) ($product->hpp ?? 0) > 0 ? $fmtHpp($product->hpp) : '—' }}</div><div class="small text-muted">HPP/unit</div></td>
                                            <td class="text-end">{{ number_format((int) $product->orders) }}</td>
                                            <td class="text-end">{{ number_format((int) $product->qty) }}</td>
                                            <td class="text-end fw-semibold">{{ $fmt($product->sales) }}</td>
                                            <td class="text-end fw-semibold"><div>{{ $fmt($product->net_sales) }}</div><div class="small text-muted">M {{ $product->contribution_margin !== null ? $percentDisplay($product->contribution_margin) : '—' }}</div></td>
                                            <td class="text-end fw-semibold">{{ $fmt($product->buyer_payment) }}</td>
                                            <td class="text-end {{ ($product->ad_spend ?? 0) > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ ($product->ad_spend_matched ?? false) ? $fmt($product->ad_spend) : '—' }}</td>
                                            <td class="text-end text-danger {{ ($product->ad_sales ?? 0) > 0 ? 'fw-semibold' : 'text-muted' }}">{{ ($product->ad_spend_matched ?? false) ? $fmt($product->ad_sales) : '—' }}</td>
                                            <td class="text-end">{{ ($product->ad_spend_matched ?? false) && ($product->ad_sales ?? 0) > 0 ? $percentDisplay(($product->ad_spend / $product->ad_sales) * 100) : '—' }}</td>
                                            <td class="text-end">{{ ($product->ad_spend_matched ?? false) && ($product->ad_spend ?? 0) > 0 ? $multipleDisplay($product->ad_sales / $product->ad_spend) : '—' }}</td>
                                            <td class="text-end pe-3">{{ ($product->ad_spend_matched ?? false) && ($product->ad_conversions ?? 0) > 0 ? $fmt($product->ad_spend / $product->ad_conversions) : '—' }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
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
                ['label' => 'Orders', 'value' => number_format($paymentSummary['orders']), 'note' => 'total order pembayaran', 'icon' => 'bi-receipt', 'comparisons' => $kpiComparisons($paymentSummary['orders'], $previousMonthPaymentSummary['orders'] ?? null, $previousPeriodPaymentSummary['orders'] ?? null, $numberDisplay)],
                ['label' => 'Total Dibayar Pembeli', 'value' => $fmt($paymentSummary['buyer_paid']), 'note' => number_format($paymentSummary['orders']).' orders', 'icon' => 'bi-cash-stack', 'variant' => 'sales-kpi--success', 'comparisons' => $kpiComparisons($paymentSummary['buyer_paid'], $previousMonthPaymentSummary['buyer_paid'] ?? null, $previousPeriodPaymentSummary['buyer_paid'] ?? null, $currencyDisplay)],
                ['label' => 'AOV Buyer Paid', 'value' => $fmt($paymentSummary['aov']), 'note' => 'Buyer Paid per order', 'icon' => 'bi-graph-up-arrow', 'comparisons' => $kpiComparisons($paymentSummary['aov'], $previousMonthPaymentSummary['aov'] ?? null, $previousPeriodPaymentSummary['aov'] ?? null, $currencyDisplay)],
                ['label' => 'Seller Net Sales', 'value' => $fmt($paymentDaily->sum('seller_net_sales')), 'note' => 'setelah diskon seller', 'icon' => 'bi-graph-down-arrow', 'variant' => 'sales-kpi--success', 'comparisons' => $kpiComparisons($paymentDaily->sum('seller_net_sales'), $previousMonthPaymentDaily->sum('seller_net_sales'), $previousPeriodPaymentDaily->sum('seller_net_sales'), $currencyDisplay)],
                ['label' => 'COD Exposure', 'value' => number_format($paymentSummary['cod_order_share'], 1).'%', 'note' => number_format($paymentDaily->sum('cod_orders')).' COD orders', 'icon' => 'bi-shield-exclamation', 'variant' => 'sales-kpi--warning', 'comparisons' => $kpiComparisons($paymentSummary['cod_order_share'], $previousMonthPaymentSummary['cod_order_share'] ?? null, $previousPeriodPaymentSummary['cod_order_share'] ?? null, $percentDisplay, 'points', false)],
                ['label' => 'Non-COD %', 'value' => $percentDisplay($paymentOrderShare($paymentSummary, $paymentDaily, 'non_cod_orders')), 'note' => number_format($paymentDaily->sum('non_cod_orders')).' orders', 'icon' => 'bi-credit-card-2-front', 'comparisons' => $kpiComparisons($paymentOrderShare($paymentSummary, $paymentDaily, 'non_cod_orders'), $paymentOrderShare($previousMonthPaymentSummary, $previousMonthPaymentDaily, 'non_cod_orders'), $paymentOrderShare($previousPeriodPaymentSummary, $previousPeriodPaymentDaily, 'non_cod_orders'), $percentDisplay, 'points')],
                ['label' => 'Pay Later %', 'value' => $percentDisplay($paymentOrderShare($paymentSummary, $paymentDaily, 'pay_later_orders')), 'note' => number_format($paymentDaily->sum('pay_later_orders')).' orders', 'icon' => 'bi-clock-history', 'comparisons' => $kpiComparisons($paymentOrderShare($paymentSummary, $paymentDaily, 'pay_later_orders'), $paymentOrderShare($previousMonthPaymentSummary, $previousMonthPaymentDaily, 'pay_later_orders'), $paymentOrderShare($previousPeriodPaymentSummary, $previousPeriodPaymentDaily, 'pay_later_orders'), $percentDisplay, 'points')],
            ],
        ])
        @if ($activeComparison)
            <section class="card sales-card sales-comparison-section shadow-sm mb-3">
                <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-2">
                    <div>
                        <div class="sales-kicker mb-1">Perbandingan periode</div>
                        <h2 class="sales-section-title mb-1">Perbandingan pembayaran</h2>
                        <div class="sales-section-subtitle">Aktif sebagai baseline, dengan perubahan terhadap setiap periode pembanding.</div>
                    </div>
                    <span class="badge sales-badge rounded-pill px-3 py-2"><i class="bi bi-columns-gap me-1" aria-hidden="true"></i>{{ count($paymentComparisonPeriods) }} periode</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table sales-payment-comparison-table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Metrik</th>
                                @foreach ($paymentComparisonPeriods as $periodIndex => $period)
                                    <th class="text-end {{ $periodIndex === 0 ? 'sales-period-current' : '' }}">
                                        <span class="sales-period-label">{{ $period['label'] }}</span>
                                        <span class="sales-period-range">{{ $period['from'] && $period['to'] ? $dateRangeLabel($period['from'], $period['to']) : '—' }}</span>
                                        @foreach ($period['phases'] as $phase)
                                            <span class="sales-period-phase-badge sales-period-phase-badge--{{ $phase['key'] }}" title="{{ $phase['label'] }}: tanggal {{ $phase['range'] }}">
                                                <i class="sales-phase-dot sales-phase-dot--{{ $phase['key'] }}"></i>
                                                {{ $phase['label'] }}
                                                <span class="sales-period-phase-range">{{ $phase['range'] }}</span>
                                            </span>
                                        @endforeach
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($paymentComparisonRows as $row)
                                <tr>
                                    <td class="ps-3 fw-semibold">{{ $row['label'] }}</td>
                                    @foreach ($paymentComparisonPeriods as $periodIndex => $period)
                                        @php
                                            $comparisonValue = $period['metrics'][$row['key']] ?? null;
                                            $currentValue = $paymentComparisonPeriods[0]['metrics'][$row['key']] ?? null;
                                            $comparisonMeta = $periodIndex > 0
                                                ? $comparisonCellMeta($currentValue, $comparisonValue, $row['formatter'], $row['delta_mode'] ?? 'relative')
                                                : null;
                                        @endphp
                                        <td class="text-end {{ $periodIndex === 0 ? 'sales-period-current' : '' }}">
                                            <span class="sales-comparison-cell">
                                                <span class="sales-comparison-value-row">
                                                    {{ $row['formatter']($comparisonValue ?? 0) }}
                                                    @if ($comparisonMeta)
                                                        <span class="sales-comparison-delta {{ $comparisonMeta['tone'] }}" title="{{ $comparisonMeta['arrow_title'] }} dibanding {{ $period['label'] }}">
                                                            <i class="bi {{ $comparisonMeta['arrow'] }}" aria-hidden="true"></i>{{ $comparisonMeta['percentage'] }}
                                                        </span>
                                                    @endif
                                                </span>
                                                @if ($comparisonMeta)
                                                    <span class="sales-comparison-difference {{ $comparisonMeta['tone'] }}">{{ $comparisonMeta['difference'] }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
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
                                    <tr><td class="ps-3 fw-semibold">AOV Buyer Paid</td>@foreach ($paymentMix as $payment)<td class="text-end">{{ $fmt($payment->avg_ticket) }}</td>@endforeach</tr>
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
                            <div class="small text-muted mt-3">Peak AOV Buyer Paid: <strong>{{ $fmt($peakPaymentDay->aov) }}</strong> · {{ $dateLabel($peakPaymentDay->day) }}</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
        <section class="card sales-card shadow-sm mb-3">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <div class="sales-kicker mb-1">Purchasing power trend</div>
                    <h2 class="sales-section-title mb-1">Daya beli per tanggal</h2>
                </div>
                <div class="sales-phase-legend" aria-label="Fase periode">
                    <span><i class="sales-phase-dot sales-phase-dot--early"></i>Awal bulan · 1–10</span>
                    <span><i class="sales-phase-dot sales-phase-dot--mid"></i>Pertengahan · 11–20</span>
                    <span><i class="sales-phase-dot sales-phase-dot--late"></i>Akhir bulan · 21+</span>
                </div>
            </div>
            @if ($paymentDaily->isEmpty())
                <div class="sales-empty text-center"><i class="bi bi-wallet2 d-block fs-3 mb-2"></i>Belum ada data pembayaran pada periode ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table sales-promotion-table sales-payment-table">
                        <thead><tr><th class="ps-3">Date</th><th class="text-end">Buyer Paid</th><th class="text-end">COD</th><th class="text-end">Non-COD</th><th class="text-end">Pay Later</th><th class="text-end">Buyer Shipping</th><th class="text-end">Service Fee</th><th class="text-end">Protection</th><th class="text-end">Seller Net Sales</th><th class="text-end">Voucher Platform</th></tr></thead>
                        <tbody>
                            @foreach ($paymentDaily as $payment)
                                @php
                                    $paymentCell = function ($amount) use ($payment, $fmt) {
                                        $nominalPct = $payment->buyer_paid > 0 ? ($amount / $payment->buyer_paid) * 100 : 0;

                                        return '<div class="fw-semibold">'.$fmt($amount).'</div><div class="small text-muted">'.number_format($nominalPct, 1, ',', '.').'%</div>';
                                    };
                                    $paymentPhase = $paymentDatePhase($payment->day);
                                @endphp
                                <tr class="sales-clickable-row sales-payment-phase--{{ $paymentPhase }}" data-sales-payment-detail-url="{{ route('marketplace.dashboard.payments.detail', array_merge(['date' => $payment->day], $paymentDetailQuery)) }}" tabindex="0" role="button" aria-label="Lihat detail pembayaran {{ $dateLabel($payment->day) }}">
                                    <td class="fw-semibold">{{ $dateLabel($payment->day) }}</td>
                                    <td class="text-end"><div class="fw-semibold">{{ $fmt($payment->buyer_paid) }}</div><div class="small text-muted">{{ number_format($payment->orders) }} order</div></td>
                                    <td class="text-end">{!! $paymentCell($payment->cod_amount) !!}</td>
                                    <td class="text-end">{!! $paymentCell($payment->non_cod_amount) !!}</td>
                                    <td class="text-end">{!! $paymentCell($payment->pay_later_amount) !!}</td>
                                    <td class="text-end"><div>{{ $fmt($payment->buyer_shipping) }}</div><div class="small text-muted">{{ number_format($payment->buyer_shipping_orders) }} order</div></td>
                                    <td class="text-end"><div>{{ $fmt($payment->buyer_service_fee) }}</div><div class="small text-muted">{{ number_format($payment->buyer_service_fee_orders) }} order</div></td>
                                    <td class="text-end"><div>{{ $fmt($payment->product_protection) }}</div><div class="small text-muted">{{ number_format($payment->product_protection_orders) }} order</div></td>
                                    <td class="text-end"><div class="fw-semibold">{{ $fmt($payment->seller_net_sales) }}</div><div class="small text-muted">{{ number_format($payment->seller_net_sales_orders) }} order</div></td>
                                    <td class="text-end"><div>{{ $fmt($payment->voucher_platform) }}</div><div class="small text-muted">{{ number_format($payment->voucher_platform_orders) }} order</div></td>
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
            <section class="card sales-card sales-comparison-section sales-funding-section {{ $fundingSection['title'] === 'Biaya Iklan' ? 'sales-cost-section' : '' }} shadow-sm">
                <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <div class="sales-kicker mb-1">{{ $fundingSection['kicker'] }}</div>
                        <h2 class="sales-section-title mb-1">{{ $fundingSection['title'] }}</h2>
                        <div class="sales-section-subtitle">Aktif sebagai baseline, dengan perubahan terhadap setiap periode pembanding.</div>
                    </div>
                    <span class="badge sales-badge rounded-pill px-3 py-2"><i class="bi bi-columns-gap me-1" aria-hidden="true"></i>{{ count($platformPromotionPeriods) }} periode</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table sales-funding-table">
                        <thead>
                            <tr>
                                <th class="ps-3">Metrik</th>
                                @foreach ($platformPromotionPeriods as $periodIndex => $period)
                                    <th class="text-end {{ $periodIndex === 0 ? 'sales-period-current' : '' }}">
                                        <span class="sales-period-label">{{ $period['label'] }}</span>
                                        <span class="sales-period-range">{{ $period['from'] && $period['to'] ? $dateRangeLabel($period['from'], $period['to']) : '—' }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($fundingSection['rows'] as $row)
                                <tr>
                                    <td class="ps-3 fw-semibold">{{ $row['label'] }}</td>
                                    @foreach ($platformPromotionPeriods as $periodIndex => $period)
                                        @php
                                            $comparisonValue = $period['metrics'][$row['key']] ?? null;
                                            $currentValue = $platformPromotionPeriods[0]['metrics'][$row['key']] ?? null;
                                            $comparisonMeta = $periodIndex > 0
                                                ? $comparisonCellMeta($currentValue, $comparisonValue, $row['format'], $row['delta_mode'] ?? 'relative')
                                                : null;
                                        @endphp
                                        <td class="text-end {{ $periodIndex === 0 ? 'sales-period-current' : '' }} {{ str_contains($row['label'], 'Total') ? 'fw-semibold' : '' }}">
                                            <span class="sales-comparison-cell">
                                                <span class="sales-comparison-value-row">
                                                    {{ $row['format']($comparisonValue ?? 0) }}
                                                    @if ($comparisonMeta)
                                                        <span class="sales-comparison-delta {{ $comparisonMeta['tone'] }}" title="{{ $comparisonMeta['arrow_title'] }} dibanding {{ $period['label'] }}">
                                                            <i class="bi {{ $comparisonMeta['arrow'] }}" aria-hidden="true"></i>{{ $comparisonMeta['percentage'] }}
                                                        </span>
                                                    @endif
                                                </span>
                                                @if ($comparisonMeta)
                                                    <span class="sales-comparison-difference {{ $comparisonMeta['tone'] }}">{{ $comparisonMeta['difference'] }}</span>
                                                @endif
                                            </span>
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
                    <table class="table table-sm table-hover align-middle sales-table sales-promotion-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th class="text-end" title="Penjualan Bruto">Penjualan Bruto</th>
                                <th class="text-end">Diskon Produk</th>
                                <th class="text-end" title="Voucher Toko">V Toko</th>
                                <th class="text-end" title="Voucher Platform">V Platform</th>
                                <th class="text-end">Paket Diskon</th>
                                <th class="text-end">Kombo Hemat</th>
                                <th class="text-end" title="Total Promosi">Total Promo</th>
                                <th class="text-end" title="Penjualan Neto">Penjualan Neto</th>
                                <th class="text-end" title="Biaya Iklan">Iklan</th>
                                <th class="text-end" title="Return on Ad Spend terhadap Penjualan Neto">ROAS</th>
                                <th class="text-end" title="Advertising Cost of Sales terhadap Penjualan Neto">ACOS</th>
                                <th class="text-end pe-3" title="Biaya Iklan per Konversi">CPA Iklan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($promotionDaily as $row)
                                @php
                                    $dailyAdSpend = (float) $adSpendDaily->get((string) $row->day, 0);
                                    $dailyAdOrders = (int) $adOrdersDaily->get((string) $row->day, 0);
                                    $dailyNetSales = max(
                                        (float) ($row->order_before_discount ?? 0)
                                            - (float) ($row->product_discount ?? 0)
                                            - (float) ($row->voucher_store ?? 0)
                                            - (float) ($row->bundle_discount ?? 0)
                                            - (float) ($row->combo_hemat ?? 0),
                                        0,
                                    );
                                    $dailyRoas = $dailyAdSpend > 0 ? $dailyNetSales / $dailyAdSpend : 0;
                                    $dailyAcos = $dailyNetSales > 0 ? ($dailyAdSpend / $dailyNetSales) * 100 : 0;
                                    $dailyCpa = $dailyAdOrders > 0 ? $dailyAdSpend / $dailyAdOrders : 0;
                                @endphp
                                <tr class="sales-clickable-row" data-sales-promotion-detail-url="{{ route('marketplace.dashboard.promotions.detail', ['date' => $row->day]) }}" tabindex="0" role="button" aria-label="Lihat detail promosi {{ $dateLabel($row->day) }}">
                                    <td class="ps-3 fw-semibold">{{ $dateLabel($row->day) }}</td>
                                    <td class="text-end">
                                        <div>{{ $fmt($row->order_before_discount ?? 0) }}</div>
                                        <div class="small text-muted">{{ number_format((int) ($row->order_count ?? 0)) }} order</div>
                                    </td>
                                    <td class="text-end">
                                        <div>{{ $fmt($row->product_discount) }}</div>
                                        <div class="small text-muted">{{ number_format((int) ($row->product_discount_orders ?? 0)) }} order</div>
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
                                    <td class="text-end fw-semibold">
                                        <div>{{ $fmt($row->total_promotion) }}</div>
                                        <div class="small text-muted">{{ number_format((int) ($row->promotion_orders ?? 0)) }} order</div>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        <div>{{ $fmt($dailyNetSales) }}</div>
                                    </td>
                                    <td class="text-end">
                                        <div>{{ $fmt($dailyAdSpend) }}</div>
                                    </td>
                                    <td class="text-end">
                                        <div>{{ $multipleDisplay($dailyRoas) }}</div>
                                    </td>
                                    <td class="text-end">
                                        <div>{{ $percentDisplay($dailyAcos) }}</div>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div>{{ $fmt($dailyCpa) }}</div>
                                    </td>
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
        const platformInput = document.querySelector('#sales-platform');
        const orderRows = document.querySelectorAll('[data-sales-order-row]');
        const comparisonModeInput = document.querySelector('#sales-comparison-mode');
        const orderDate = document.querySelector('#sales-order-detail-date');
        const orderCount = document.querySelector('[data-sales-order-count]');
        const orderEmpty = document.querySelector('[data-sales-order-empty]');
        const trendDataElement = document.querySelector('#sales-trend-data');
        const trendChart = document.querySelector('[data-sales-trend-chart]');
        const trendMetric = document.querySelector('[data-sales-trend-metric]');
        const trendTotal = document.querySelector('[data-sales-trend-total]');
        const trendCompare = document.querySelector('[data-sales-trend-compare]');

        function escapeTrendText(value) {
            return String(value ?? '').replace(/[&<>'"]/g, function (character) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    "'": '&#039;',
                    '"': '&quot;'
                }[character];
            });
        }

        function formatTrendCurrency(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.round(Number(value) || 0));
        }

        function formatTrendAxis(value) {
            const absolute = Math.abs(Number(value) || 0);
            if (absolute >= 1000000000) return 'Rp ' + (value / 1000000000).toFixed(1).replace('.', ',') + ' M';
            if (absolute >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1).replace('.', ',') + ' jt';
            if (absolute >= 1000) return 'Rp ' + (value / 1000).toFixed(1).replace('.', ',') + ' rb';

            return 'Rp ' + Math.round(value);
        }

        function renderSalesTrend() {
            if (!trendDataElement || !trendChart || !trendMetric) return;

            let payload;
            try {
                payload = JSON.parse(trendDataElement.textContent || '{}');
            } catch (error) {
                return;
            }

            const metric = trendMetric.value || 'net_sales';
            const current = Array.isArray(payload.current) ? payload.current : [];
            const previous = Array.isArray(payload.previous) ? payload.previous : [];
            const pointCount = Math.max(current.length, previous.length, 1);
            const currentValues = current.map((row) => Number(row?.[metric]) || 0);
            const previousValues = previous.map((row) => Number(row?.[metric]) || 0);
            const allValues = currentValues.concat(previousValues);
            const minValue = Math.min(0, ...allValues);
            const maxValue = Math.max(0, ...allValues);
            const valueRange = Math.max(maxValue - minValue, 1);
            const width = 1000;
            const height = 260;
            const left = 88;
            const right = 16;
            const top = 18;
            const bottom = 34;
            const chartWidth = width - left - right;
            const chartHeight = height - top - bottom;
            const x = (index) => left + (pointCount === 1 ? chartWidth / 2 : (index / (pointCount - 1)) * chartWidth);
            const y = (value) => top + ((maxValue - value) / valueRange) * chartHeight;
            const makePoints = (values) => values.map((value, index) => [x(index), y(value), value]);
            const makePath = (points) => points.map((point, index) => (index === 0 ? 'M ' : ' L ') + point[0].toFixed(2) + ' ' + point[1].toFixed(2)).join('');
            const currentPoints = makePoints(currentValues);
            const previousPoints = makePoints(previousValues);
            const gridValues = [maxValue, minValue + valueRange / 2, minValue];
            let markup = '';

            gridValues.forEach(function (value) {
                const yPosition = y(value).toFixed(2);
                markup += '<line class="trend-grid" x1="' + left + '" x2="' + (width - right) + '" y1="' + yPosition + '" y2="' + yPosition + '"></line>';
                markup += '<text class="trend-axis-label" x="0" y="' + (Number(yPosition) + 4) + '">' + escapeTrendText(formatTrendAxis(value)) + '</text>';
            });

            if (currentPoints.length > 1) {
                const areaPath = makePath(currentPoints) + ' L ' + currentPoints[currentPoints.length - 1][0].toFixed(2) + ' ' + (height - bottom) + ' L ' + currentPoints[0][0].toFixed(2) + ' ' + (height - bottom) + ' Z';
                markup += '<path class="trend-area" d="' + areaPath + '"></path>';
            }
            if (previousPoints.length > 1) markup += '<path class="trend-line previous" d="' + makePath(previousPoints) + '"></path>';
            if (currentPoints.length > 1) markup += '<path class="trend-line" d="' + makePath(currentPoints) + '"></path>';

            currentPoints.forEach(function (point, index) {
                const label = current[index]?.label || '';
                markup += '<circle class="trend-point" cx="' + point[0].toFixed(2) + '" cy="' + point[1].toFixed(2) + '" r="3.5"><title>' + escapeTrendText(label + ' · ' + formatTrendCurrency(point[2])) + '</title></circle>';
            });
            previousPoints.forEach(function (point, index) {
                const label = previous[index]?.label || payload.comparison_label || '';
                markup += '<circle class="trend-point previous" cx="' + point[0].toFixed(2) + '" cy="' + point[1].toFixed(2) + '" r="2.5"><title>' + escapeTrendText(label + ' · ' + formatTrendCurrency(point[2])) + '</title></circle>';
            });

            [0, Math.floor((pointCount - 1) / 2), pointCount - 1].filter((value, index, values) => values.indexOf(value) === index).forEach(function (index) {
                const label = current[index]?.label || '';
                if (label) markup += '<text class="trend-axis-label" text-anchor="middle" x="' + x(index).toFixed(2) + '" y="' + (height - 8) + '">' + escapeTrendText(label) + '</text>';
            });

            trendChart.innerHTML = markup;

            const currentTotal = currentValues.reduce((total, value) => total + value, 0);
            const previousTotal = previousValues.reduce((total, value) => total + value, 0);
            if (trendTotal) trendTotal.textContent = formatTrendCurrency(currentTotal);
            if (trendCompare) {
                trendCompare.classList.remove('good', 'bad');
                if (previous.length && previousTotal !== 0) {
                    const delta = ((currentTotal - previousTotal) / Math.abs(previousTotal)) * 100;
                    trendCompare.textContent = (delta >= 0 ? '↑ ' : '↓ ') + Math.abs(delta).toFixed(1).replace('.', ',') + '% vs ' + (payload.comparison_label || 'pembanding');
                    trendCompare.classList.add(delta >= 0 ? 'good' : 'bad');
                } else {
                    trendCompare.textContent = previous.length ? 'Tidak ada perubahan dasar' : 'Belum ada pembanding';
                }
            }
        }

        renderSalesTrend();
        if (trendMetric) trendMetric.addEventListener('change', renderSalesTrend);

        function formatComparisonAxis(value, unit) {
            if (unit === 'percent') return (Number(value) || 0).toFixed(1).replace('.', ',') + '%';
            if (unit === 'multiple') return (Number(value) || 0).toFixed(2).replace('.', ',') + 'x';
            if (unit === 'number') return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.round(Number(value) || 0));

            return formatTrendAxis(value);
        }

        function renderSalesComparisonChart(detail) {
            const chart = detail.querySelector('[data-sales-comparison-chart]');
            const dataElement = detail.querySelector('[data-sales-comparison-chart-data]');
            if (!chart || !dataElement) return;

            let rows;
            try {
                rows = JSON.parse(dataElement.textContent || '[]');
            } catch (error) {
                rows = [];
            }

            const unit = chart.dataset.salesComparisonChartUnit || 'currency';
            const values = rows.map((row) => row.value === null ? null : Number(row.value));
            const validValues = values.filter((value) => Number.isFinite(value));
            if (!validValues.length) {
                chart.innerHTML = '<text class="comparison-chart-label" x="450" y="94" text-anchor="middle">Belum ada data</text>';
                return;
            }

            const width = 900;
            const height = 180;
            const left = 72;
            const right = 18;
            const top = 16;
            const bottom = 38;
            const chartWidth = width - left - right;
            const chartHeight = height - top - bottom;
            const minValue = Math.min(0, ...validValues);
            const maxValue = Math.max(0, ...validValues);
            const valueRange = Math.max(maxValue - minValue, 1);
            const x = (index) => left + (rows.length === 1 ? chartWidth / 2 : (index / (rows.length - 1)) * chartWidth);
            const y = (value) => top + ((maxValue - value) / valueRange) * chartHeight;
            const points = values.map((value, index) => value === null || !Number.isFinite(value) ? null : [x(index), y(value), value]);
            const validPoints = points.filter(Boolean);
            const path = validPoints.map((point, index) => (index === 0 ? 'M ' : ' L ') + point[0].toFixed(2) + ' ' + point[1].toFixed(2)).join('');
            const gridValues = [maxValue, minValue + valueRange / 2, minValue];
            let markup = '';

            gridValues.forEach(function (value) {
                const yPosition = y(value).toFixed(2);
                markup += '<line class="comparison-chart-grid" x1="' + left + '" x2="' + (width - right) + '" y1="' + yPosition + '" y2="' + yPosition + '"></line>';
                markup += '<text class="comparison-chart-label" x="2" y="' + (Number(yPosition) + 4) + '">' + escapeTrendText(formatComparisonAxis(value, unit)) + '</text>';
            });
            if (validPoints.length > 1) markup += '<path class="comparison-chart-line" d="' + path + '"></path>';

            points.forEach(function (point, index) {
                if (!point) return;
                const row = rows[index] || {};
                markup += '<circle class="comparison-chart-point" cx="' + point[0].toFixed(2) + '" cy="' + point[1].toFixed(2) + '" r="4"><title>' + escapeTrendText((row.label || '') + ' · ' + (row.display || '—')) + '</title></circle>';
            });
            rows.forEach(function (row, index) {
                markup += '<text class="comparison-chart-label" text-anchor="middle" x="' + x(index).toFixed(2) + '" y="' + (height - 10) + '">' + escapeTrendText(row.label || '') + '</text>';
            });
            chart.innerHTML = markup;
        }

        document.querySelectorAll('[data-sales-comparison-row]').forEach(function (row) {
            const trigger = row.querySelector('[data-sales-comparison-toggle]');
            const detailId = row.dataset.salesComparisonDetailId || '';
            const detail = detailId ? document.getElementById(detailId) : null;
            if (!trigger || !detail) return;

            function toggleComparisonDetail() {
                const expanded = trigger.getAttribute('aria-expanded') === 'true';
                const nextExpanded = !expanded;
                trigger.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
                detail.hidden = !nextExpanded;
                row.classList.toggle('is-expanded', nextExpanded);
                if (nextExpanded) renderSalesComparisonChart(detail);
            }

            trigger.addEventListener('click', function (event) {
                event.stopPropagation();
                toggleComparisonDetail();
            });
            row.addEventListener('click', function (event) {
                if (!event.target.closest('button')) toggleComparisonDetail();
            });
        });

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

        if (platformInput && filterForm) {
            platformInput.addEventListener('change', function () {
                filterForm.requestSubmit();
            });
        }

        document.querySelectorAll('[data-comparison-mode]').forEach(function (comparisonTab) {
            comparisonTab.addEventListener('click', function () {
                if (!comparisonModeInput || !filterForm) return;

                comparisonModeInput.value = comparisonTab.dataset.comparisonMode;
                filterForm.requestSubmit();
            });
        });

        document.querySelectorAll('[data-sales-store-toggle]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                const detailId = trigger.getAttribute('aria-controls');
                const detail = detailId ? document.getElementById(detailId) : null;
                const expanded = trigger.getAttribute('aria-expanded') === 'true';
                const nextExpanded = !expanded;
                trigger.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
                trigger.setAttribute('aria-label', (nextExpanded ? 'Tutup' : 'Buka') + ' rincian toko ' + (trigger.dataset.salesStoreToggle || ''));
                const summaryRow = trigger.closest('.sales-daily-row');
                if (summaryRow) summaryRow.classList.toggle('sales-daily-row--expanded', nextExpanded);
                if (detail) detail.hidden = !nextExpanded;
            });
        });

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
                if (currentQuery.get('platform')) target.searchParams.set('platform', currentQuery.get('platform'));
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

        document.querySelectorAll('[data-sales-product-category-toggle]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                const key = trigger.dataset.salesProductCategoryToggle || '';
                const expanded = trigger.getAttribute('aria-expanded') === 'true';
                trigger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                document.querySelectorAll('[data-sales-product-category-items="' + key + '"]').forEach(function (row) {
                    row.hidden = expanded;
                });
                if (expanded) {
                    document.querySelectorAll('[data-sales-product-marketplace-items]').forEach(function (row) {
                        if (row.dataset.salesProductCategoryItems === key) row.hidden = true;
                    });
                    document.querySelectorAll('[data-sales-product-marketplace-toggle]').forEach(function (marketplaceTrigger) {
                        const marketplaceKey = marketplaceTrigger.dataset.salesProductMarketplaceToggle || '';
                        if (marketplaceKey.indexOf(key + '-') === 0) marketplaceTrigger.setAttribute('aria-expanded', 'false');
                    });
                }
            });
        });

        document.querySelectorAll('[data-sales-category-comparison-toggle]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                const key = trigger.dataset.salesCategoryComparisonToggle || '';
                const expanded = trigger.getAttribute('aria-expanded') === 'true';
                const nextExpanded = !expanded;
                const categoryRow = trigger.closest('[data-sales-category-comparison-row]');
                const categoryName = trigger.querySelector('span')?.textContent?.trim() || 'kategori';
                trigger.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
                trigger.setAttribute('aria-label', (nextExpanded ? 'Tutup' : 'Buka') + ' detail kategori ' + categoryName);
                if (categoryRow) categoryRow.classList.toggle('is-expanded', nextExpanded);
                document.querySelectorAll('[data-sales-category-comparison-items="' + key + '"]').forEach(function (row) {
                    row.hidden = !nextExpanded;
                });
            });
        });

        document.querySelectorAll('[data-sales-category-comparison-row]').forEach(function (row) {
            const trigger = row.querySelector('[data-sales-category-comparison-toggle]');
            if (!trigger) return;
            row.addEventListener('click', function (event) {
                if (event.target.closest('button, a')) return;
                trigger.click();
            });
            row.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                event.preventDefault();
                trigger.click();
            });
        });

        document.querySelectorAll('[data-sales-analysis-toggle]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                const key = trigger.dataset.salesAnalysisToggle || '';
                const expanded = trigger.getAttribute('aria-expanded') === 'true';
                const nextExpanded = !expanded;
                const matrixRow = trigger.closest('[data-sales-analysis-row]');
                const segment = trigger.querySelector('.analysis-status-badge')?.textContent?.trim() || 'segmentasi';
                trigger.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
                trigger.setAttribute('aria-label', (nextExpanded ? 'Tutup' : 'Buka') + ' detail produk segmentasi ' + segment);
                if (matrixRow) matrixRow.classList.toggle('is-expanded', nextExpanded);
                document.querySelectorAll('[data-sales-analysis-items="' + key + '"]').forEach(function (row) {
                    row.hidden = !nextExpanded;
                });
            });
        });

        document.querySelectorAll('[data-sales-analysis-row]').forEach(function (row) {
            const trigger = row.querySelector('[data-sales-analysis-toggle]');
            if (!trigger) return;
            row.addEventListener('click', function (event) {
                if (event.target.closest('button, a')) return;
                trigger.click();
            });
            row.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                event.preventDefault();
                trigger.click();
            });
        });

        document.querySelectorAll('[data-sales-product-marketplace-toggle]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                const key = trigger.dataset.salesProductMarketplaceToggle || '';
                const expanded = trigger.getAttribute('aria-expanded') === 'true';
                trigger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                document.querySelectorAll('[data-sales-product-marketplace-items="' + key + '"]').forEach(function (row) {
                    row.hidden = expanded;
                });
            });
        });

        document.querySelectorAll('[data-sales-product-name]').forEach(function (trigger) {
            trigger.addEventListener('click', async function () {
                const name = trigger.dataset.salesProductName || '';
                const sku = trigger.dataset.salesProductSku || '';
                const internalItemId = trigger.dataset.salesProductInternalItemId || '';
                const query = new URLSearchParams({
                    name: name,
                    sku: sku,
                    date_from: @json($filters['date_from']),
                    date_to: @json($filters['date_to']),
                });
                if (internalItemId) query.set('internal_item_id', internalItemId);
                @if ($filters['store_id'])
                    query.set('store_id', @json($filters['store_id']));
                @endif
                @if ($filters['platform'])
                    query.set('platform', @json($filters['platform']));
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
