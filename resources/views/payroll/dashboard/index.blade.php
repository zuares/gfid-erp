{{-- resources/views/payroll/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Payroll • Dashboard')

@php
    $tabs = [
        'keseluruhan' => 'Keseluruhan',
        'penjahit' => 'Penjahit',
        'cutting' => 'Cutting',
    ];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        /* Lazy-load helpers (dashboard payroll) */
        .prod-tab-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            color: var(--gf-muted);
            font-size: .85rem;
            padding: 2.4rem 1rem;
        }

        .prod-tab-spinner {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid rgba(148, 163, 184, .35);
            border-top-color: #2563eb;
            animation: prodspin .7s linear infinite;
        }

        @keyframes prodspin {
            to { transform: rotate(360deg); }
        }

        .prod-filter-busy { opacity: .55; pointer-events: none; }

        .prod-empty {
            text-align: center;
            color: var(--gf-muted);
            font-size: .85rem;
            padding: 1.6rem;
        }

        /* Toolbar filter realtime */
        .sj-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            align-items: center;
            margin-bottom: .9rem;
        }

        .sj-toolbar .form-control,
        .sj-toolbar .form-select {
            min-height: 36px;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 600;
            border-color: rgba(15, 23, 42, .12);
            box-shadow: none;
        }

        .sj-toolbar .sj-search {
            flex: 1 1 220px;
            min-width: 180px;
            max-width: 330px;
        }

        .sj-toolbar .form-select {
            width: auto;
            padding-right: 1.9rem;
        }

        .sj-count {
            margin-left: auto;
            font-size: .78rem;
            font-weight: 800;
            color: #475569;
            white-space: nowrap;
        }

        @media (max-width: 576px) {
            .sj-toolbar .sj-search { flex: 1 1 100%; max-width: none; }
            .sj-count { margin-left: 0; }
            .gf-hide-mobile { display: none !important; }
            .gf-table-scroll-sticky .gf-clean-table { min-width: 0 !important; font-size: .76rem; }
            .gf-table-scroll-sticky .gf-clean-table th,
            .gf-table-scroll-sticky .gf-clean-table td { padding-left: .4rem; padding-right: .4rem; }
            .gf-table-scroll.gf-table-scroll-sticky { overflow: visible; }
        }

        /* Scroll vertikal + thead sticky */
        .gf-table-scroll.gf-table-scroll-sticky {
            max-height: none;
            min-height: 220px;
            overflow: visible;
            -webkit-overflow-scrolling: touch;
        }
        .gf-table-scroll-sticky .gf-sticky-table thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            background: #f8fafc !important;
            box-shadow: inset 0 -1px 0 #e6eaf0;
        }

        .gf-table-scroll-sticky .gf-sticky-table .gf-sticky-thead,
        .gf-table-scroll-sticky .gf-sticky-table .gf-sticky-head-row {
            position: sticky;
            top: 0;
            z-index: 30;
            background: #f8fafc;
        }

        .gf-table-scroll-sticky .gf-sticky-table .gf-sticky-head-row th {
            position: sticky;
            top: 0;
            z-index: 33;
        }

        .pj-head-filters {
            display: grid;
            grid-template-columns: minmax(180px, 1.25fr) minmax(112px, .6fr) minmax(150px, .8fr) auto;
            align-items: center;
            gap: .42rem;
            width: min(100%, 760px);
        }

        .gf-head-control {
            min-height: 32px;
            width: 100%;
            border-radius: 7px;
            border-color: rgba(148, 163, 184, .26);
            background-color: #fff;
            color: #334155;
            font-size: .76rem;
            font-weight: 750;
            box-shadow: none !important;
        }

        .gf-head-control:focus {
            border-color: rgba(37, 99, 235, .48);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .10) !important;
        }

        .gf-head-count {
            color: #64748b !important;
            font-size: .74rem !important;
            font-weight: 850 !important;
            text-align: right;
            text-transform: none !important;
            letter-spacing: 0 !important;
            white-space: nowrap;
        }

        .gf-sort-th {
            border: 0;
            background: transparent;
            color: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: flex-start;
            gap: .28rem;
            width: 100%;
            padding: 0;
            font: inherit;
            font-weight: inherit;
            letter-spacing: inherit;
            text-transform: inherit;
            text-align: inherit;
            cursor: pointer;
        }

        .gf-num .gf-sort-th {
            justify-content: flex-end;
        }

        .gf-sort-th::after {
            content: "↕";
            color: #94a3b8;
            font-size: .78em;
            line-height: 1;
        }

        th[aria-sort="ascending"] .gf-sort-th::after {
            content: "↑";
            color: #334155;
        }

        th[aria-sort="descending"] .gf-sort-th::after {
            content: "↓";
            color: #334155;
        }

        .gf-sort-th:hover {
            color: #334155;
        }

        th[aria-sort="ascending"],
        th[aria-sort="descending"] {
            color: #334155 !important;
            background: #eef2f7 !important;
        }

        .gf-doc-code {
            display: block;
            margin-top: .16rem;
            color: #94a3b8;
            font-size: .68rem;
            font-weight: 750;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .gf-sticky-table tbody tr[data-pj-row] {
            cursor: pointer;
        }

        .gf-sticky-table tbody tr.is-selected td {
            background: rgba(37, 99, 235, .07) !important;
            box-shadow: inset 3px 0 0 #2563eb;
        }

        /* Badges & chips dipakai partial */
        .gf-badge {
            display: inline-block;
            font-size: .68rem;
            font-weight: 800;
            padding: .14rem .5rem;
            border-radius: 999px;
            white-space: nowrap;
        }
        .gf-badge-red { background: rgba(239, 68, 68, .14); color: #b91c1c; }
        .gf-badge-amber { background: rgba(245, 158, 11, .16); color: #b45309; }
        .gf-badge-blue { background: rgba(37, 99, 235, .14); color: #1d4ed8; }
        .gf-badge-green { background: rgba(34, 197, 94, .16); color: #166534; }
        .gf-badge-muted { background: rgba(148, 163, 184, .16); color: #64748b; }

        .gf-chip {
            display: inline-flex;
            align-items: baseline;
            gap: .25rem;
            font-size: .74rem;
            padding: .12rem .45rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, .12);
            border: 1px solid rgba(148, 163, 184, .2);
        }

        .gf-num { text-align: right; font-variant-numeric: tabular-nums; }

        /* Sel tanggal 2-baris */
        .gf-datecell { display: flex; flex-direction: column; line-height: 1.18; }
        .gf-datecell-d { font-weight: 600; color: var(--gf-dark); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .gf-datecell-sub { font-size: .68rem; color: var(--gf-muted); font-variant-numeric: tabular-nums; white-space: nowrap; }

        /* Tombol Cetak Slip */
        .gf-slip-btn { display: inline-flex; align-items: center; gap: .35rem; padding: .4rem .85rem;
            border-radius: 999px; background: #0f172a; color: #fff; font-weight: 600; font-size: .8rem;
            text-decoration: none; border: 1px solid #0f172a; white-space: nowrap; }
        .gf-slip-btn::before { content: "🖨"; font-size: .85em; }
        .gf-slip-btn:hover { background: #1e293b; color: #fff; }
        /* Footer tabel (tempat tombol Cetak Slip) */
        .gf-table-foot { display: flex; align-items: center; justify-content: flex-end; gap: .75rem;
            margin-top: .85rem; padding-top: .75rem; border-top: 1px solid #eef0f4; }
        .gf-table-foot-hint { font-size: .8rem; color: #94a3b8; }

        /* Payroll skin: diselaraskan dengan halaman shipment edit */
        .payroll-shipment-skin {
            max-width: 1100px;
            margin-inline: auto;
            padding: 0 .75rem 5rem;
            min-width: 0;
            width: 100%;
        }

        .payroll-shipment-skin > *,
        .payroll-shipment-skin .gf-marketplace-dashboard,
        .payroll-shipment-skin .gf-marketplace-tab-panel,
        .payroll-shipment-skin .gf-panel,
        .payroll-shipment-skin .gf-panel-body,
        .payroll-shipment-skin .gf-overview-kpi-grid,
        .payroll-shipment-skin .gf-table-scroll {
            min-width: 0;
            max-width: 100%;
        }

        body[data-theme="light"] .payroll-shipment-skin {
            background: #f3f4f6;
        }

        body[data-theme="dark"] .payroll-shipment-skin {
            background: #020617;
        }

        .payroll-shipment-skin .gf-master-header {
            position: static;
            top: 0;
            z-index: 310;
            margin-inline: -.75rem;
            padding: .5rem .85rem;
            border: 0;
            border-bottom: 1px solid rgba(148, 163, 184, .22);
            border-radius: 0;
            background: rgba(248, 250, 252, .97);
            box-shadow: none;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

        body[data-theme="dark"] .payroll-shipment-skin .gf-master-header {
            background: rgba(2, 6, 23, .96);
            border-bottom-color: rgba(71, 85, 105, .72);
        }

        .payroll-shipment-skin .gf-master-header-layout {
            gap: .6rem;
            align-items: center;
        }

        .payroll-shipment-skin .gf-master-header-copy {
            display: flex;
            align-items: center;
            gap: .45rem;
            flex-wrap: wrap;
            min-width: 180px;
        }

        .payroll-shipment-skin .gf-master-eyebrow {
            margin: 0;
            border-radius: 999px;
            padding: .15rem .62rem;
            border: 1px solid rgba(148, 163, 184, .32);
            background: rgba(148, 163, 184, .08);
            color: #475569;
            font-size: .68rem;
            letter-spacing: .08em;
        }

        .payroll-shipment-skin .gf-master-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 900;
            letter-spacing: .01em;
            color: #111827;
            white-space: nowrap;
        }

        body[data-theme="dark"] .payroll-shipment-skin .gf-master-title {
            color: #e5e7eb;
        }

        .payroll-shipment-skin .gf-master-desc {
            display: none;
        }

        .payroll-shipment-skin .gf-master-actions {
            flex: 1 1 620px;
        }

        .payroll-shipment-skin .gf-dashboard-header-actions,
        .payroll-shipment-skin .gf-dashboard-header-filter {
            width: 100%;
        }

        .payroll-shipment-skin .gf-dashboard-header-filter {
            display: grid;
            grid-template-columns: 92px minmax(190px, 240px) 54px;
            align-items: center;
            justify-content: flex-end;
            gap: .45rem;
        }

        .payroll-shipment-skin .gf-dashboard-header-filter [data-date-from],
        .payroll-shipment-skin .gf-dashboard-header-filter [data-date-to] {
            display: none !important;
        }

        .payroll-shipment-skin .gf-dashboard-header-filter .form-control,
        .payroll-shipment-skin .gf-dashboard-header-filter .form-select,
        .payroll-shipment-skin .gf-header-period-select,
        .payroll-shipment-skin .gf-header-date-input {
            min-height: 34px;
            border-radius: 999px !important;
            border-color: rgba(148, 163, 184, .32);
            background: rgba(248, 250, 252, .96);
            color: #334155;
            font-size: .76rem;
            font-weight: 750;
            padding-top: .22rem;
            padding-bottom: .22rem;
            padding-left: .7rem;
            box-shadow: none !important;
            min-width: 0;
        }

        .payroll-shipment-skin .gf-header-date-input {
            width: 100%;
            max-width: none;
            font-variant-numeric: tabular-nums;
        }

        .payroll-shipment-skin .gf-header-period-select {
            width: 100%;
            max-width: none;
            padding-right: 1.55rem;
        }

        .payroll-shipment-skin .gf-header-icon-btn {
            min-height: 34px;
            width: 54px;
            border-radius: 999px;
            padding-inline: .55rem;
            font-size: .75rem;
            font-weight: 800;
            color: #64748b;
            background: transparent;
        }

        .payroll-shipment-skin .gf-marketplace-dashboard {
            gap: .75rem;
        }

        .payroll-shipment-skin .gf-marketplace-clean-ui .gf-marketplace-sticky-head {
            position: static;
            top: 52px;
            z-index: 260;
            margin: 0 -.1rem;
            padding: .55rem 0 .5rem;
            background: linear-gradient(180deg, rgba(243, 244, 246, .98), rgba(243, 244, 246, .92));
            border-bottom: 0;
        }

        .payroll-shipment-skin .gf-marketplace-tabs {
            width: max-content;
            max-width: 100%;
            border-radius: 999px;
            padding: .22rem;
            background: rgba(255, 255, 255, .96);
            border-color: rgba(148, 163, 184, .22);
            box-shadow: none;
        }

        .payroll-shipment-skin .gf-marketplace-tab {
            border-radius: 999px;
            padding: .4rem .85rem;
            color: #64748b;
            font-size: .76rem;
            font-weight: 850;
        }

        .payroll-shipment-skin .gf-marketplace-tab.is-active {
            background: #334155;
            color: #fff;
            box-shadow: none;
        }

        .payroll-shipment-skin .gf-overview-kpi-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: .65rem;
            margin-top: .1rem;
            width: 100%;
        }

        .payroll-shipment-skin .gf-overview-kpi-card {
            min-height: 92px;
            border-radius: 8px;
            border: 1px solid rgba(148, 163, 184, .16);
            background: var(--card, #fff);
            box-shadow: none;
            padding: .82rem .95rem;
        }

        .payroll-shipment-skin .gf-overview-kpi-card-strong {
            border-color: rgba(51, 65, 85, .24);
            background: #fff;
        }

        .payroll-shipment-skin .gf-overview-kpi-label {
            font-size: .64rem;
            letter-spacing: .08em;
            color: #94a3b8;
        }

        .payroll-shipment-skin .gf-overview-kpi-value {
            margin-top: .24rem;
            color: #334155;
            font-size: 1.3rem;
            font-weight: 900;
            line-height: 1.05;
        }

        .payroll-shipment-skin .gf-overview-kpi-note {
            margin-top: .25rem;
            font-size: .73rem;
            color: #64748b;
        }

        .payroll-shipment-skin .gf-panel {
            border-radius: 8px;
            border-color: rgba(148, 163, 184, .18);
            box-shadow: none;
            background: var(--card, #fff);
            overflow: visible;
        }

        .payroll-shipment-skin .gf-marketplace-tab-panel,
        .payroll-shipment-skin .gf-panel-body {
            overflow: visible;
        }

        .payroll-shipment-skin .gf-panel-header {
            padding: .72rem .85rem;
            border-bottom-color: rgba(148, 163, 184, .12);
            background: transparent;
        }

        .payroll-shipment-skin .gf-panel-title {
            color: #334155;
            font-size: .95rem;
            font-weight: 900;
        }

        .payroll-shipment-skin .gf-subtext {
            display: none;
        }

        .payroll-shipment-skin .gf-panel-body {
            padding: .75rem .85rem .85rem;
        }

        .payroll-shipment-skin .sj-toolbar {
            margin-bottom: .7rem;
            gap: .45rem;
        }

        .payroll-shipment-skin .sj-toolbar .form-control,
        .payroll-shipment-skin .sj-toolbar .form-select {
            min-height: 34px;
            border-radius: 7px;
            border-color: rgba(148, 163, 184, .26);
            background: rgba(248, 250, 252, .82);
            color: #334155;
            font-size: .78rem;
        }

        .payroll-shipment-skin .sj-count {
            color: #64748b;
            font-size: .75rem;
        }

        .payroll-shipment-skin .gf-table-scroll.gf-table-scroll-sticky {
            max-height: none;
            min-height: 260px;
            width: 100%;
            border: 1px solid rgba(148, 163, 184, .14);
            border-radius: 8px;
            background: #fff;
            overflow: visible;
        }

        .payroll-shipment-skin .gf-clean-table {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

            .payroll-shipment-skin .gf-clean-table thead th {
                background: #f8fafc;
                color: #64748b;
            font-size: .7rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .04em;
            border-bottom: 1px solid rgba(148, 163, 184, .16);
            padding: .55rem .65rem;
        }

        .payroll-shipment-skin .gf-clean-table tbody td {
            color: #334155;
            font-size: .82rem;
            border-bottom-color: rgba(148, 163, 184, .10);
            padding: .55rem .65rem;
            vertical-align: middle;
        }

        .payroll-shipment-skin .table-hover tbody tr:hover td {
            background: rgba(148, 163, 184, .06);
        }

        .payroll-shipment-skin .gf-chip,
        .payroll-shipment-skin .gf-badge {
            border-radius: 7px;
        }

        .payroll-shipment-skin .gf-chip {
            background: rgba(148, 163, 184, .10);
            border-color: rgba(148, 163, 184, .18);
            color: #334155;
            font-size: .72rem;
        }

        .payroll-shipment-skin .gf-badge-green,
        .payroll-shipment-skin .gf-badge-blue {
            background: rgba(51, 65, 85, .08);
            color: #334155;
        }

        .payroll-shipment-skin .gf-badge-amber {
            background: rgba(245, 158, 11, .08);
            color: #92400e;
        }

        .payroll-shipment-skin .gf-total-row td {
            background: #f8fafc;
            border-top: 1px solid rgba(148, 163, 184, .18);
            box-shadow: none;
        }

        .payroll-shipment-skin .gf-table-foot {
            justify-content: flex-end;
            margin-top: .65rem;
            padding-top: .65rem;
            border-top-color: rgba(148, 163, 184, .14);
        }

        .payroll-shipment-skin .gf-slip-btn {
            border-radius: 999px;
            background: #334155;
            border-color: #334155;
            box-shadow: none;
            font-size: .76rem;
            font-weight: 850;
        }

        .payroll-shipment-skin .gf-table-foot-hint {
            margin-right: auto;
            color: #64748b;
            font-weight: 700;
        }

        .payroll-shipment-skin .gf-slip-btn:hover {
            background: #1f2937;
            border-color: #1f2937;
        }

        @media (max-width: 860px) {
            .payroll-shipment-skin {
                padding: 0 .5rem 5rem;
                overflow-x: clip;
            }

            .payroll-shipment-skin .gf-master-header {
                margin-inline: -.5rem;
                padding: .5rem;
            }

            .payroll-shipment-skin .gf-master-header-copy {
                min-width: 0;
                flex: 1 1 100%;
            }

            .payroll-shipment-skin .gf-master-eyebrow {
                display: none;
            }

            .payroll-shipment-skin .gf-master-title {
                font-size: 1.02rem;
            }

            .payroll-shipment-skin .gf-master-actions {
                flex: 1 1 100%;
                width: 100%;
            }

            .payroll-shipment-skin .gf-dashboard-header-filter {
                display: grid;
                grid-template-columns: 78px 1fr 42px;
                gap: .35rem;
            }

            .payroll-shipment-skin .gf-header-date-input,
            .payroll-shipment-skin .gf-header-period-select,
            .payroll-shipment-skin .gf-header-icon-btn {
                width: 100%;
                max-width: none;
            }

            .payroll-shipment-skin .gf-header-date-input { grid-column: 2 / 3; }
            .payroll-shipment-skin .gf-header-period-select { grid-column: 1 / 2; }
            .payroll-shipment-skin .gf-header-icon-btn {
                grid-column: 3 / 4;
                width: 42px;
                overflow: hidden;
                color: transparent;
                position: relative;
            }
            .payroll-shipment-skin .gf-header-icon-btn::after {
                content: "↺";
                color: #64748b;
                position: absolute;
                inset: 0;
                display: grid;
                place-items: center;
                font-size: .95rem;
            }
            .payroll-shipment-skin .gf-marketplace-clean-ui .gf-marketplace-sticky-head {
                top: 96px;
                margin-inline: 0;
                padding-top: .45rem;
                overflow: hidden;
            }

            .payroll-shipment-skin .gf-marketplace-tabs {
                width: 100%;
            }

            .payroll-shipment-skin .gf-marketplace-tab {
                flex: 1 0 auto;
                text-align: center;
            }

            .payroll-shipment-skin .gf-overview-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: .45rem;
            }

            .payroll-shipment-skin .gf-overview-kpi-card {
                min-height: 84px;
                padding: .68rem .72rem;
            }

            .payroll-shipment-skin .gf-overview-kpi-value {
                font-size: 1.08rem;
                overflow-wrap: anywhere;
            }

            .payroll-shipment-skin .gf-panel-header,
            .payroll-shipment-skin .gf-panel-body {
                padding-left: .7rem;
                padding-right: .7rem;
            }

            .payroll-shipment-skin .gf-panel-header {
                align-items: stretch;
                flex-direction: column;
            }

            .payroll-shipment-skin .gf-panel-actions {
                width: 100%;
            }

            .payroll-shipment-skin .pj-head-filters {
                grid-template-columns: 1fr 1fr;
                width: 100%;
            }

            .payroll-shipment-skin .pj-head-search,
            .payroll-shipment-skin .gf-head-count {
                grid-column: 1 / -1;
            }

            .payroll-shipment-skin .gf-head-count {
                text-align: left;
                white-space: normal;
            }

            .payroll-shipment-skin .sj-toolbar {
                display: grid;
                grid-template-columns: 1fr 1fr;
                align-items: stretch;
            }

            .payroll-shipment-skin .sj-toolbar .sj-search {
                grid-column: 1 / -1;
                max-width: none;
                min-width: 0;
            }

            .payroll-shipment-skin .sj-toolbar .form-select {
                width: 100%;
                min-width: 0;
            }

            .payroll-shipment-skin .sj-count {
                grid-column: 1 / -1;
                white-space: normal;
            }

            .payroll-shipment-skin .gf-table-scroll.gf-table-scroll-sticky {
                max-height: none;
                min-height: 0;
                overflow: visible;
            }

            .payroll-shipment-skin .gf-clean-table {
                min-width: 720px !important;
                font-size: .78rem;
            }

            .payroll-shipment-skin .gf-table-foot {
                align-items: stretch;
                flex-direction: column;
            }

            .payroll-shipment-skin .gf-slip-btn {
                justify-content: center;
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <x-gf.page
        class="payroll-shipment-skin"
        eyebrow="Payroll"
        title="Dashboard Payroll"
        description="Borongan jahit &amp; cutting · upah per operator">

        <x-slot:actions>
            <div class="gf-dashboard-header-actions">
                <form id="filterForm" method="GET" action="{{ route('payroll.dashboard') }}"
                    class="gf-dashboard-header-filter" data-dashboard-filter>
                    <input type="hidden" name="date_from" value="{{ $filters['date_from'] }}" data-date-from>
                    <input type="hidden" name="date_to" value="{{ $filters['date_to'] }}" data-date-to>

                    <select class="form-select gf-header-period-select" data-period aria-label="Periode">
                        <option value="custom">Custom</option>
                        <option value="7">7 Hari</option>
                        <option value="30">30 Hari</option>
                        <option value="month">Bulan Ini</option>
                    </select>

                    <input type="text" class="form-control gf-header-date-input" autocomplete="off"
                        data-date-range aria-label="Rentang tanggal"
                        value="{{ $filters['date_from'] }} — {{ $filters['date_to'] }}">

                    <a href="{{ route('payroll.dashboard') }}" class="btn btn-light border gf-header-icon-btn"
                        data-filter-reset data-from="{{ $defaults['date_from'] }}" data-to="{{ $defaults['date_to'] }}"
                        title="Reset filter">Reset</a>
                </form>
            </div>
        </x-slot:actions>

        <div class="gf-marketplace-dashboard gf-marketplace-clean-ui" data-dashboard-root>
            {{-- TABS --}}
            <div class="gf-marketplace-sticky-head">
                <div class="gf-marketplace-tabs" role="tablist" id="prodTabs">
                    @foreach ($tabs as $key => $label)
                        <button type="button" class="gf-marketplace-tab {{ $key === $initialTab ? 'is-active' : '' }}"
                            data-tab-target="{{ $key }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            {{-- PANES (lazy) --}}
            @foreach ($tabs as $key => $label)
                <section class="gf-marketplace-tab-panel" data-tab-panel="{{ $key }}"
                    data-loaded="{{ $key === $initialTab ? '1' : '0' }}" @if($key !== $initialTab) hidden @endif>
                    @if ($key === $initialTab)
                        @include($initialPartial)
                    @else
                        <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
                    @endif
                </section>
            @endforeach
        </div>
    </x-gf.page>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const DATA_URL = @json(route('payroll.dashboard.data'));
            const SLIP_URL = @json(route('payroll.dashboard.slip'));
            const SERVER_INITIAL = @json($initialTab);
            const KEY = 'payrollDashTab';

            const tabBtns = Array.from(document.querySelectorAll('#prodTabs .gf-marketplace-tab'));
            const panes = Array.from(document.querySelectorAll('[data-tab-panel]'));
            const form = document.getElementById('filterForm');
            const periodLabel = document.getElementById('periodLabel');

            const paneByName = (name) => panes.find(p => p.dataset.tabPanel === name);
            const activeName = () =>
                (tabBtns.find(b => b.classList.contains('is-active'))?.dataset.tabTarget) || SERVER_INITIAL;

            const loadingHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
            const errorHTML = (name) =>
                '<div class="prod-empty">Gagal memuat data. ' +
                '<button type="button" class="btn btn-sm btn-light border rounded-pill" data-retry="' + name + '">Coba lagi</button></div>';

            function currentFilters() {
                const fd = new FormData(form);
                const obj = {};
                for (const [k, v] of fd.entries())
                    if (v !== '' && v != null) obj[k] = v;
                return obj;
            }

            function buildUrl(tab) {
                const params = new URLSearchParams(currentFilters());
                params.set('tab', tab);
                return DATA_URL + '?' + params.toString();
            }

            function activate(name) {
                tabBtns.forEach(b => b.classList.toggle('is-active', b.dataset.tabTarget === name));
                panes.forEach(p => p.hidden = (p.dataset.tabPanel !== name));
            }

            async function loadTab(name, { force = false } = {}) {
                const pane = paneByName(name);
                if (!pane) return;
                if (pane.dataset.loaded === '1' && !force) return;

                pane.dataset.loaded = '0';
                pane.innerHTML = loadingHTML;
                try {
                    const res = await fetch(buildUrl(name), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const json = await res.json();
                    pane.innerHTML = json.html;
                    pane.dataset.loaded = '1';
                    if (json.meta?.period_label && periodLabel) periodLabel.textContent = json.meta.period_label;
                    if (typeof initTabFilters === 'function') initTabFilters(name, pane);
                } catch (e) {
                    pane.innerHTML = errorHTML(name);
                }
            }

            function syncUrl() {
                const params = new URLSearchParams(currentFilters());
                params.set('tab', activeName());
                history.replaceState(null, '', location.pathname + '?' + params.toString());
            }

            async function applyFilters() {
                panes.forEach(p => {
                    if (p.dataset.tabPanel !== activeName()) {
                        p.dataset.loaded = '0';
                        p.innerHTML = loadingHTML;
                    }
                });
                form.classList.add('prod-filter-busy');
                syncUrl();
                await loadTab(activeName(), { force: true });
                form.classList.remove('prod-filter-busy');
            }

            tabBtns.forEach(b => b.addEventListener('click', () => {
                const name = b.dataset.tabTarget;
                activate(name);
                try { localStorage.setItem(KEY, name); } catch (e) {}
                syncUrl();
                loadTab(name);
            }));

            document.addEventListener('click', (e) => {
                const r = e.target.closest('[data-retry]');
                if (r) loadTab(r.dataset.retry, { force: true });
            });

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                applyFilters();
            });

            // ---- Filter realtime: flatpickr range + periode + select ----
            const fromEl = form.querySelector('[data-date-from]');
            const toEl = form.querySelector('[data-date-to]');
            const rangeEl = form.querySelector('[data-date-range]');
            const periodSel = form.querySelector('[data-period]');

            let fp = null;
            const ymd = (d) => (fp && d instanceof Date) ? fp.formatDate(d, 'Y-m-d') : d;

            if (rangeEl && window.GFID && window.GFID.initDateRange) {
                fp = window.GFID.initDateRange(rangeEl, {
                    defaultDate: [fromEl.value, toEl.value],
                    onClose: (sel) => {
                        if (sel.length === 2) {
                            fromEl.value = ymd(sel[0]);
                            toEl.value = ymd(sel[1]);
                            if (periodSel) periodSel.value = 'custom';
                            applyFilters();
                        }
                    }
                });
            }

            function detectPeriod() {
                if (!periodSel) return;
                const today = new Date();
                const minus = (n) => { const x = new Date(); x.setDate(x.getDate() - n); return x; };
                const tStr = ymd(today);
                let val = 'custom';
                if (toEl.value === tStr && fromEl.value === ymd(minus(6))) val = '7';
                else if (toEl.value === tStr && fromEl.value === ymd(minus(29))) val = '30';
                else if (toEl.value === tStr && fromEl.value === ymd(new Date(today.getFullYear(), today.getMonth(), 1))) val = 'month';
                periodSel.value = val;
            }
            detectPeriod();

            if (periodSel) periodSel.addEventListener('change', () => {
                const v = periodSel.value;
                if (v === 'custom') return;
                const today = new Date();
                let from;
                if (v === '7') { from = new Date(); from.setDate(from.getDate() - 6); }
                else if (v === '30') { from = new Date(); from.setDate(from.getDate() - 29); }
                else { from = new Date(today.getFullYear(), today.getMonth(), 1); }
                fromEl.value = ymd(from);
                toEl.value = ymd(today);
                if (fp) fp.setDate([from, today], false);
                applyFilters();
            });

            const resetLink = form.querySelector('[data-filter-reset]');
            if (resetLink) resetLink.addEventListener('click', (e) => {
                e.preventDefault();
                fromEl.value = resetLink.dataset.from;
                toEl.value = resetLink.dataset.to;
                if (fp) fp.setDate([resetLink.dataset.from, resetLink.dataset.to], false);
                if (periodSel) periodSel.value = 'custom';
                detectPeriod();
                applyFilters();
            });

            const idFmt = (n) => (n || 0).toLocaleString('id-ID');
            const idRp = (n) => 'Rp ' + idFmt(Math.round(n || 0));

            function gfCompareText(a, b, key) {
                return (a.dataset[key] || '').localeCompare(b.dataset[key] || '', 'id', {
                    numeric: true,
                    sensitivity: 'base'
                });
            }

            function gfApplySortState(root, headerSelector, sortValue) {
                root.querySelectorAll(headerSelector).forEach(th => {
                    const active = th.dataset.pjSortKey && sortValue.startsWith(th.dataset.pjSortKey + '-');
                    th.setAttribute('aria-sort', active
                        ? (sortValue.endsWith('-asc') ? 'ascending' : 'descending')
                        : 'none');
                });
            }

            // Tombol "Cetak Slip": tampil saat satu operator + modul terpilih.
            function gfUpdateSlip(linkEl, operatorCode, module, basis = '') {
                if (!linkEl) return;
                const foot = linkEl.closest('.gf-table-foot');
                const hint = foot?.querySelector('.gf-table-foot-hint');
                const syncHint = () => {
                    if (!hint || !foot) return;
                    hint.hidden = false;
                };
                if (operatorCode && operatorCode !== '-' && module) {
                    const p = new URLSearchParams(currentFilters());
                    p.delete('operator_id');
                    p.set('module', module);
                    p.set('operator', operatorCode);
                    if (basis) p.set('basis', basis);
                    else p.delete('basis');
                    linkEl.textContent = module === 'cutting' ? 'Slip Potong' : (basis === 'ambil' ? 'Slip Ambil' : 'Slip Setor');
                    linkEl.href = SLIP_URL + '?' + p.toString();
                    linkEl.hidden = false;
                    syncHint();
                } else {
                    linkEl.hidden = true;
                    linkEl.removeAttribute('href');
                    syncHint();
                }
            }

            function pjSelectOperator(root, row) {
                if (!root || !row) return;
                root.dataset.pjOperator = row.dataset.operator || '';
                root.dataset.pjOperatorLabel = row.dataset.operatorLabel || row.dataset.operator || '';
                root.querySelectorAll('[data-pj-row]').forEach(r => {
                    r.classList.toggle('is-selected', r === row);
                });
                const hint = root.querySelector('[data-pj-slip-hint]');
                if (hint && root.dataset.pjOperatorLabel) {
                    hint.textContent = root.dataset.pjOperatorLabel;
                }
                gfUpdateSlip(root.querySelector('[data-pj-slip-setor]'), root.dataset.pjOperator, 'sewing', 'setor');
                gfUpdateSlip(root.querySelector('[data-pj-slip-ambil]'), root.dataset.pjOperator, 'sewing', 'ambil');
            }

            // ---- Tab "Keseluruhan" (gabungan jahit + cutting) ----
            function applyKsFilters(root) {
                if (!root) return;
                const table = root.querySelector('[data-ks-table]');
                if (!table) return;
                const tbody = table.querySelector('tbody');
                const q = (root.querySelector('[data-ks-search]')?.value || '').trim().toLowerCase();
                const role = root.querySelector('[data-ks-role]')?.value || '';
                const operator = root.querySelector('[data-ks-operator]')?.value || '';
                const kind = root.querySelector('[data-ks-kind]')?.value || '';
                const sort = root.querySelector('[data-ks-sort]')?.value || 'date-desc';

                const rows = Array.from(tbody.querySelectorAll('[data-ks-row]'));
                let shown = 0, sumQty = 0, sumProj = 0, sumJahit = 0, sumCutting = 0; const ops = new Set();
                rows.forEach(r => {
                    let ok = true;
                    if (q && !(r.dataset.search || '').includes(q)) ok = false;
                    if (role && r.dataset.role !== role) ok = false;
                    if (operator && r.dataset.operator !== operator) ok = false;
                    if (kind && r.dataset.kind !== kind) ok = false;
                    r.hidden = !ok;
                    if (ok) {
                        shown++;
                        ops.add(r.dataset.operator);
                        sumQty += parseFloat(r.dataset.qty) || 0;
                        sumProj += parseFloat(r.dataset.proj) || 0;
                        const amt = parseFloat(r.dataset.amount) || 0;
                        if (r.dataset.module === 'sewing') sumJahit += amt;
                        else if (r.dataset.module === 'cutting') sumCutting += amt;
                    }
                });

                const cmp = {
                    'date-desc': (a, b) => (a.dataset.date < b.dataset.date ? 1 : -1),
                    'qty-desc': (a, b) => (+b.dataset.qty) - (+a.dataset.qty),
                    'amount-desc': (a, b) => (+b.dataset.proj) - (+a.dataset.proj),
                }[sort];
                if (cmp) rows.sort(cmp).forEach(r => tbody.appendChild(r));

                const cnt = root.querySelector('[data-ks-count]');
                if (cnt) cnt.textContent = idFmt(shown) + ' transaksi · ' + idFmt(ops.size) + ' operator · ' + idRp(sumProj);
                const footQty = root.querySelector('[data-ks-foot-qty]');
                if (footQty) footQty.textContent = idFmt(sumQty);
                const footAmt = root.querySelector('[data-ks-foot-amount]');
                if (footAmt) footAmt.textContent = idRp(sumProj);

                const setKpi = (sel, val) => { const el = root.querySelector(sel); if (el) el.textContent = val; };
                setKpi('[data-ks-kpi-total]', idRp(sumJahit + sumCutting));
                setKpi('[data-ks-kpi-jahit]', idRp(sumJahit));
                setKpi('[data-ks-kpi-cutting]', idRp(sumCutting));
                setKpi('[data-ks-kpi-operator]', idFmt(ops.size));
                setKpi('[data-ks-kpi-tx]', idFmt(shown));

                const module = role === 'Jahit' ? 'sewing' : (role === 'Cutting' ? 'cutting' : '');
                gfUpdateSlip(root.querySelector('[data-ks-slip]'), operator, module, module === 'sewing' ? 'ambil' : '');

                const empty = root.querySelector('[data-ks-empty]');
                if (empty) empty.hidden = (shown !== 0) || rows.length === 0;
            }

            const KS_SEL = '[data-ks-search],[data-ks-role],[data-ks-operator],[data-ks-kind],[data-ks-sort]';
            document.addEventListener('input', (e) => {
                if (!e.target.matches('[data-ks-search]')) return;
                applyKsFilters(e.target.closest('[data-tab-panel]'));
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches(KS_SEL)) return;
                applyKsFilters(e.target.closest('[data-tab-panel]'));
            });

            // ---- Tab "Penjahit" ----
            function applyPjFilters(root) {
                if (!root) return;
                const table = root.querySelector('[data-pj-table]');
                if (!table) return;
                const tbody = table.querySelector('tbody');
                const q = (root.querySelector('[data-pj-search]')?.value || '').trim().toLowerCase();
                const operator = root.querySelector('[data-pj-operator]')?.value || '';
                const type = root.querySelector('[data-pj-type]')?.value || '';
                const sort = root.dataset.pjSort || root.querySelector('[data-pj-sort]')?.value || 'date-desc';

                const rows = Array.from(tbody.querySelectorAll('[data-pj-row]'));
                let shown = 0, sumAmount = 0, sumProj = 0, sumQty = 0, sumOk = 0, sumReject = 0; const ops = new Set();
                rows.forEach(r => {
                    let ok = true;
                    if (q && !(r.dataset.search || '').includes(q)) ok = false;
                    if (operator && r.dataset.operator !== operator) ok = false;
                    if (type && r.dataset.type !== type) ok = false;
                    r.hidden = !ok;
                    if (ok) {
                        shown++;
                        ops.add(r.dataset.operator);
                        sumQty += parseFloat(r.dataset.qty) || 0;
                        sumAmount += parseFloat(r.dataset.amount) || 0;
                        sumProj += parseFloat(r.dataset.proj) || 0;
                        if (r.dataset.type === 'Setor') {
                            sumOk += parseFloat(r.dataset.qty) || 0;
                            sumReject += parseFloat(r.dataset.reject) || 0;
                        }
                    }
                });

                const cmp = {
                    'date-desc': (a, b) => gfCompareText(b, a, 'date'),
                    'date-asc': (a, b) => gfCompareText(a, b, 'date'),
                    'operator-asc': (a, b) => gfCompareText(a, b, 'operator') || gfCompareText(a, b, 'operatorName'),
                    'operator-desc': (a, b) => gfCompareText(b, a, 'operator') || gfCompareText(b, a, 'operatorName'),
                    'type-asc': (a, b) => gfCompareText(a, b, 'type'),
                    'type-desc': (a, b) => gfCompareText(b, a, 'type'),
                    'sku-asc': (a, b) => gfCompareText(a, b, 'sku'),
                    'sku-desc': (a, b) => gfCompareText(b, a, 'sku'),
                    'product-asc': (a, b) => gfCompareText(a, b, 'product'),
                    'product-desc': (a, b) => gfCompareText(b, a, 'product'),
                    'qty-desc': (a, b) => (+b.dataset.qty) - (+a.dataset.qty),
                    'qty-asc': (a, b) => (+a.dataset.qty) - (+b.dataset.qty),
                    'amount-desc': (a, b) => (+b.dataset.proj) - (+a.dataset.proj),
                    'amount-asc': (a, b) => (+a.dataset.proj) - (+b.dataset.proj),
                    'reject-desc': (a, b) => (+b.dataset.reject) - (+a.dataset.reject),
                    'reject-asc': (a, b) => (+a.dataset.reject) - (+b.dataset.reject),
                    'rate-desc': (a, b) => (+b.dataset.rate) - (+a.dataset.rate),
                    'rate-asc': (a, b) => (+a.dataset.rate) - (+b.dataset.rate),
                }[sort];
                if (cmp) rows.sort(cmp).forEach(r => tbody.appendChild(r));
                gfApplySortState(root, '[data-pj-sort-key]', sort);

                const cnt = root.querySelector('[data-pj-count]');
                if (cnt) cnt.textContent = idFmt(shown) + ' transaksi · ' + idFmt(ops.size) + ' penjahit · ' + idRp(sumProj);
                const footQty = root.querySelector('[data-pj-foot-qty]');
                if (footQty) footQty.textContent = idFmt(sumQty);
                const footAmt = root.querySelector('[data-pj-foot-amount]');
                if (footAmt) footAmt.textContent = idRp(sumProj);

                const setKpi = (sel, val) => { const el = root.querySelector(sel); if (el) el.textContent = val; };
                setKpi('[data-pj-kpi-penjahit]', idFmt(ops.size));
                setKpi('[data-pj-kpi-tx]', idFmt(shown));
                setKpi('[data-pj-kpi-ok]', idFmt(sumOk));
                setKpi('[data-pj-kpi-upah]', idRp(sumAmount));
                setKpi('[data-pj-kpi-reject]', idFmt(sumReject));

                const selectedOperator = operator || root.dataset.pjOperator || '';
                gfUpdateSlip(root.querySelector('[data-pj-slip-setor]'), selectedOperator, 'sewing', 'setor');
                gfUpdateSlip(root.querySelector('[data-pj-slip-ambil]'), selectedOperator, 'sewing', 'ambil');

                const empty = root.querySelector('[data-pj-empty]');
                if (empty) empty.hidden = (shown !== 0) || rows.length === 0;
            }

            const PJ_SEL = '[data-pj-search],[data-pj-operator],[data-pj-type],[data-pj-sort]';
            document.addEventListener('input', (e) => {
                if (!e.target.matches('[data-pj-search]')) return;
                applyPjFilters(e.target.closest('[data-tab-panel]'));
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches(PJ_SEL)) return;
                const pane = e.target.closest('[data-tab-panel]');
                if (e.target.matches('[data-pj-sort]') && pane) delete pane.dataset.pjSort;
                applyPjFilters(pane);
            });
            document.addEventListener('click', (e) => {
                const th = e.target.closest('[data-pj-sort-key]');
                if (!th) return;
                const pane = th.closest('[data-tab-panel]');
                if (!pane) return;
                const key = th.dataset.pjSortKey;
                const current = pane.dataset.pjSort || pane.querySelector('[data-pj-sort]')?.value || 'date-desc';
                const nextDir = current === key + '-asc' ? 'desc' : current === key + '-desc' ? 'asc' : (key === 'date' ? 'desc' : 'asc');
                pane.dataset.pjSort = key + '-' + nextDir;
                applyPjFilters(pane);
            });
            document.addEventListener('click', (e) => {
                if (e.target.closest('[data-pj-sort-key]')) return;
                const row = e.target.closest('[data-pj-row]');
                if (!row) return;
                pjSelectOperator(row.closest('[data-tab-panel]'), row);
            });

            // ---- Tab "Cutting" ----
            function applyCgFilters(root) {
                if (!root) return;
                const table = root.querySelector('[data-cg-table]');
                if (!table) return;
                const tbody = table.querySelector('tbody');
                const q = (root.querySelector('[data-cg-search]')?.value || '').trim().toLowerCase();
                const operator = root.querySelector('[data-cg-operator]')?.value || '';
                const sort = root.querySelector('[data-cg-sort]')?.value || 'date-desc';

                const rows = Array.from(tbody.querySelectorAll('[data-cg-row]'));
                let shown = 0, sumQty = 0, sumOk = 0, sumReject = 0, sumAmount = 0; const ops = new Set();
                rows.forEach(r => {
                    let ok = true;
                    if (q && !(r.dataset.search || '').includes(q)) ok = false;
                    if (operator && r.dataset.operator !== operator) ok = false;
                    r.hidden = !ok;
                    if (ok) {
                        shown++;
                        ops.add(r.dataset.operator);
                        sumQty += parseFloat(r.dataset.qty) || 0;
                        sumOk += parseFloat(r.dataset.ok) || 0;
                        sumReject += parseFloat(r.dataset.reject) || 0;
                        sumAmount += parseFloat(r.dataset.amount) || 0;
                    }
                });

                const cmp = {
                    'date-desc': (a, b) => (a.dataset.date < b.dataset.date ? 1 : -1),
                    'qty-desc': (a, b) => (+b.dataset.qty) - (+a.dataset.qty),
                    'amount-desc': (a, b) => (+b.dataset.amount) - (+a.dataset.amount),
                    'reject-desc': (a, b) => (+b.dataset.reject) - (+a.dataset.reject),
                }[sort];
                if (cmp) rows.sort(cmp).forEach(r => tbody.appendChild(r));

                const cnt = root.querySelector('[data-cg-count]');
                if (cnt) cnt.textContent = idFmt(shown) + ' transaksi · ' + idFmt(ops.size) + ' pemotong · ' + idRp(sumAmount);
                const footQty = root.querySelector('[data-cg-foot-qty]');
                if (footQty) footQty.textContent = idFmt(sumQty);
                const footAmt = root.querySelector('[data-cg-foot-amount]');
                if (footAmt) footAmt.textContent = idRp(sumAmount);

                const setKpi = (sel, val) => { const el = root.querySelector(sel); if (el) el.textContent = val; };
                setKpi('[data-cg-kpi-operator]', idFmt(ops.size));
                setKpi('[data-cg-kpi-tx]', idFmt(shown));
                setKpi('[data-cg-kpi-ok]', idFmt(sumOk));
                setKpi('[data-cg-kpi-upah]', idRp(sumAmount));
                setKpi('[data-cg-kpi-reject]', idFmt(sumReject));

                gfUpdateSlip(root.querySelector('[data-cg-slip]'), operator, 'cutting');

                const empty = root.querySelector('[data-cg-empty]');
                if (empty) empty.hidden = (shown !== 0) || rows.length === 0;
            }

            const CG_SEL = '[data-cg-search],[data-cg-operator],[data-cg-sort]';
            document.addEventListener('input', (e) => {
                if (!e.target.matches('[data-cg-search]')) return;
                applyCgFilters(e.target.closest('[data-tab-panel]'));
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches(CG_SEL)) return;
                applyCgFilters(e.target.closest('[data-tab-panel]'));
            });

            // Terapkan filter default per-tab setelah HTML tab dimuat.
            function initTabFilters(name, pane) {
                if (name === 'keseluruhan') applyKsFilters(pane);
                else if (name === 'penjahit') applyPjFilters(pane);
                else if (name === 'cutting') applyCgFilters(pane);
            }

            initTabFilters(SERVER_INITIAL, paneByName(SERVER_INITIAL));

            try {
                const saved = localStorage.getItem(KEY);
                if (saved && saved !== SERVER_INITIAL && paneByName(saved)) {
                    activate(saved);
                    syncUrl();
                    loadTab(saved);
                }
            } catch (e) {}
        });
    </script>
@endpush
