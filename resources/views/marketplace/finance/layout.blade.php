@extends('layouts.app')

@section('title', 'Marketplace Finance')

@section('content')
    @php
        $financeRoute = request()->route()?->getName();
        $financeMeta = [
            'marketplace.finance.index' => ['eyebrow' => 'Finance control center', 'title' => 'Overview', 'description' => 'Ringkasan arus transaksi, income, settlement, dan risiko rekonsiliasi marketplace.', 'icon' => 'bi-grid-1x2', 'badge' => 'Read-only overview'],
            'marketplace.finance.transactions' => ['eyebrow' => 'Marketplace ledger', 'title' => 'Transactions', 'description' => 'Telusuri gross, fee, net income, dan posting jurnal per order.', 'icon' => 'bi-receipt', 'badge' => 'Order-level ledger'],
            'marketplace.finance.settlements' => ['eyebrow' => 'Cash-in tracking', 'title' => 'Settlements', 'description' => 'Pantau payout marketplace, alokasi order, dan status posting ke bank atau jurnal.', 'icon' => 'bi-bank', 'badge' => 'Payout monitoring'],
            'marketplace.finance.reconciliation' => ['eyebrow' => 'Finance audit', 'title' => 'Rekonsiliasi per Order', 'description' => 'Validasi gross, fee, dan net income setiap order tanpa bergantung pada payout atau mutasi bank.', 'icon' => 'bi-check2-square', 'badge' => 'Order-only audit'],
            'marketplace.finance.fee-analysis' => ['eyebrow' => 'Cost intelligence', 'title' => 'Fee Analysis', 'description' => 'Bandingkan beban fee berdasarkan tipe, toko, dan order untuk menemukan kebocoran margin.', 'icon' => 'bi-bar-chart-line', 'badge' => 'Aggregated costs'],
        ];
        $activeFinanceMeta = $financeMeta[$financeRoute] ?? $financeMeta['marketplace.finance.index'];
    @endphp
    <style>
        .finance-page { --finance-ink: var(--gf-color-content, #101828); --finance-muted: var(--gf-color-muted, #667085); --finance-line: var(--gf-color-line, #e4e7ec); --finance-soft: var(--gf-color-surface-muted, #f2f4f7); --finance-blue: var(--gf-color-brand-strong, #4142bb); --finance-brand: var(--gf-color-brand, #5b5ce2); --finance-accent: var(--gf-color-accent, #13b8c4); --finance-card-radius: var(--gf-radius-card, 16px); --finance-control-radius: var(--gf-radius-control, 10px); --finance-text-xs: .72rem; --finance-text-sm: .8rem; --finance-text-body: .875rem; container: finance-page / inline-size; width: 100%; max-width: var(--gf-content-max, 1280px); min-width: 0; margin: 0 auto; padding-top: 1.75rem !important; padding-inline: var(--gf-content-gutter, 1rem); padding-bottom: 2.5rem !important; color: var(--finance-ink); font-size: var(--finance-text-body); line-height: 1.45; }
        .finance-page .finance-card { border: 1px solid var(--finance-line); border-radius: var(--finance-card-radius); background: var(--gf-color-surface, #fff); box-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(15, 23, 42, .035)); }
        .finance-page .card-body { padding: 1.15rem 1.25rem; }
        .finance-page .card-header { padding: .85rem 1.25rem; }
        .finance-page .finance-number { font-size: 1.35rem; font-weight: 750; letter-spacing: -.02em; }
        .finance-page .table { --bs-table-hover-bg: #f8fafc; }
        .finance-page .table th { white-space: nowrap; font-size: .68rem; font-weight: 800; letter-spacing: .055em; line-height: 1.25; text-transform: uppercase; color: var(--finance-muted); background: var(--finance-soft); border-bottom-color: var(--finance-line); }
        .finance-page .table td { vertical-align: middle; border-color: #eef1f4; }
        .finance-page .finance-table td { font-size: .8rem; line-height: 1.4; }
        .finance-page .table thead th:first-child { padding-left: 1.25rem; }
        .finance-page .table tbody td:first-child { padding-left: 1.25rem; }
        .finance-page .finance-nav { overflow-x: auto; white-space: nowrap; }
        .finance-page .finance-nav .nav-link { color: var(--finance-muted); border-radius: var(--finance-control-radius); font-weight: 600; }
        .finance-page .finance-nav .nav-link:hover { color: var(--finance-blue); background: #f4f3ff; }
        .finance-page .finance-nav .nav-link.active { color: var(--finance-blue); background: #f4f3ff; }
        .finance-page .finance-nav .nav-link.active i { color: var(--finance-brand); }
        .finance-page .finance-kpi { position: relative; overflow: hidden; min-height: 116px; }
        .finance-page .finance-kpi .card-body { padding: 1rem 1.1rem; }
        .finance-page .finance-kpi::after { content: ""; position: absolute; width: 92px; height: 92px; right: -30px; bottom: -42px; border-radius: 50%; background: currentColor; opacity: .06; }
        .finance-page .finance-kpi-icon { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--finance-control-radius); background: var(--finance-accent, #2563eb); color: #fff; }
        .finance-page .finance-kpi-icon i { color: #fff; }
        .finance-page .finance-kpi--success { --finance-accent: #15803d; color: #15803d; }
        .finance-page .finance-kpi--danger { --finance-accent: #dc2626; color: #dc2626; }
        .finance-page .finance-kpi--warning { --finance-accent: #b45309; color: #b45309; }
        .finance-page .finance-kpi--muted { --finance-accent: #64748b; color: #64748b; }
        .finance-page .finance-caption { color: var(--finance-muted); font-size: .82rem; }
        .finance-page .finance-eyebrow { color: var(--finance-blue); font-size: .7rem; font-weight: 750; letter-spacing: .12em; text-transform: uppercase; }
        .finance-page .finance-page-title { font-size: clamp(1.65rem, 2vw, 2.15rem); font-weight: 780; letter-spacing: -.04em; }
        .finance-page .finance-page-subtitle { color: var(--finance-muted); font-size: .92rem; }
        .finance-page .finance-page-header { position: relative; overflow: hidden; padding: 1.35rem 1.5rem; border: 1px solid #e0e2fb; border-radius: 24px; background: linear-gradient(120deg, #f4f3ff 0%, #fff 52%, #edfcff 100%); box-shadow: var(--gf-shadow-lg, 0 10px 28px rgba(42, 48, 112, .06)); }
        .finance-page .finance-page-header::after { content: ""; position: absolute; width: 180px; height: 180px; right: -55px; top: -84px; border-radius: 50%; background: rgba(91, 92, 226, .1); pointer-events: none; }
        .finance-page .finance-page-header > * { position: relative; z-index: 1; }
        .finance-page .finance-page-header-copy { min-width: 0; }
        .finance-page .finance-page-header-icon { display: inline-grid; width: 28px; height: 28px; margin-right: .4rem; place-items: center; border-radius: 9px; color: var(--finance-blue); background: rgba(255,255,255,.76); vertical-align: -.2rem; }
        .finance-page .finance-page-header .finance-eyebrow { display: flex; align-items: center; }
        .finance-page .finance-page-header .finance-page-title { font-size: clamp(1.65rem, 3vw, 2.35rem); }
        .finance-page .finance-page-header .finance-page-subtitle { max-width: 700px; line-height: 1.5; }
        .finance-page .finance-header-badge { display: inline-flex; align-items: center; gap: .4rem; min-height: 34px; padding: 0 .7rem; border: 1px solid rgba(208,213,221,.82); border-radius: 999px; color: var(--finance-muted); background: rgba(255,255,255,.78); font-size: .7rem; font-weight: 750; white-space: nowrap; }
        .finance-page .finance-header-badge i { color: var(--finance-brand); }
        .finance-page .finance-tabs { padding: .35rem; border: 1px solid var(--finance-line); border-radius: var(--finance-card-radius); background: var(--gf-color-surface, #fff); box-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(15, 23, 42, .03)); }
        .finance-page .finance-tabs .nav-link { display: inline-flex; align-items: center; gap: .45rem; }
        .finance-page .finance-tabs .nav-link i { font-size: .95rem; }
        .finance-page .finance-tabs .nav-link:focus-visible,
        .finance-page .btn:focus-visible,
        .finance-page .form-control:focus-visible,
        .finance-page .form-select:focus-visible,
        .finance-page summary:focus-visible { outline: 3px solid rgba(91, 92, 226, .25); outline-offset: 2px; }
        .finance-page .finance-callout { display: flex; flex-wrap: wrap; gap: .75rem; align-items: flex-start; border: 1px solid #dbeafe; border-radius: 14px; padding: .9rem 1rem; background: #f8fbff; color: #334155; }
        .finance-page .finance-callout > div { min-width: 0; flex: 1 1 16rem; }
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
        .finance-page .btn-primary { --bs-btn-bg: var(--finance-brand); --bs-btn-border-color: var(--finance-brand); --bs-btn-hover-bg: var(--gf-color-brand-strong, #4142bb); --bs-btn-hover-border-color: var(--gf-color-brand-strong, #4142bb); }
        .finance-page .btn-outline-primary { --bs-btn-color: var(--finance-blue); --bs-btn-border-color: #c9c8f4; --bs-btn-hover-color: #fff; --bs-btn-hover-bg: var(--finance-brand); --bs-btn-hover-border-color: var(--finance-brand); }
        .finance-page .form-control,
        .finance-page .form-select,
        .finance-page .input-group-text { border-radius: var(--finance-control-radius); }
        .finance-page .finance-tab-content { min-width: 0; }
        .finance-page .finance-tab-panel { display: grid; gap: 1rem; min-width: 0; }
        .finance-page .finance-overview .row.g-3,
        .finance-page .finance-fee-analysis .row.g-3 { margin-top: 0; margin-bottom: 0; }
        .finance-page .finance-section-card { overflow: hidden; }
        .finance-page .finance-section-card > .card-header { border-bottom: 1px solid var(--finance-line) !important; }
        .finance-page .finance-section-count { display: inline-flex; align-items: center; min-height: 26px; padding: 0 .55rem; border: 1px solid var(--finance-line); border-radius: 999px; color: var(--finance-muted); background: #fff; font-size: .68rem; font-weight: 800; white-space: nowrap; }
        .finance-page .finance-sidebar-layout { display: grid; grid-template-columns: minmax(280px, 296px) minmax(0, 1fr); gap: 1.25rem; align-items: start; }
        .finance-page .finance-sidebar-layout > * { min-width: 0; }
        .finance-page .finance-sidebar { position: sticky; top: calc(var(--app-navbar-offset, 58px) + 1rem); }
        .finance-page .finance-sidebar .finance-card { box-shadow: var(--gf-shadow-sm, 0 8px 24px rgba(15, 23, 42, .05)); }
        .finance-page .finance-sidebar .finance-filter-card { border-color: #dfe1ff; background: linear-gradient(180deg, #fff 0%, #fbfbff 100%); }
        .finance-page .finance-sidebar .finance-filter-summary { padding-inline: 1rem; }
        .finance-page .finance-sidebar .finance-filter-summary .finance-section-meta { white-space: normal; line-height: 1.35; }
        .finance-page .finance-sidebar .finance-filter-body { padding-inline: 1rem; }
        .finance-page .finance-sidebar-field { display: grid; gap: .4rem; margin-bottom: 1rem; }
        .finance-page .finance-sidebar-field--last { margin-bottom: .25rem; }
        .finance-page .finance-sidebar,
        .finance-page .finance-sidebar .finance-filter-card,
        .finance-page .finance-sidebar .finance-filter-body,
        .finance-page .finance-sidebar-field,
        .finance-page .finance-sidebar .finance-filter-search { min-width: 0; box-sizing: border-box; }
        .finance-page .finance-sidebar .form-label { margin: 0; color: #475467; font-size: .7rem; font-weight: 750; }
        .finance-page .finance-sidebar .form-control,
        .finance-page .finance-sidebar .form-select { width: 100%; max-width: 100%; min-height: 42px; box-sizing: border-box; }
        .finance-page .finance-sidebar .btn { min-height: 40px; }
        .finance-page .finance-filter-disclosure { border-radius: var(--finance-card-radius); }
        .finance-page .finance-filter-summary { display: flex; align-items: center; justify-content: space-between; gap: .8rem; padding: 1.05rem 1.15rem; cursor: pointer; list-style: none; }
        .finance-page .finance-filter-summary > .d-flex:first-child { min-width: 0; }
        .finance-page .finance-filter-summary > .d-flex:first-child > span:last-child { min-width: 0; }
        .finance-page .finance-filter-summary .finance-section-title,
        .finance-page .finance-filter-summary .finance-section-meta { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .finance-page .finance-filter-summary::-webkit-details-marker { display: none; }
        .finance-page .finance-filter-summary:focus-visible { outline: 3px solid rgba(91, 92, 226, .25); outline-offset: -3px; }
        .finance-page .finance-filter-icon { display: grid; width: 32px; height: 32px; flex: 0 0 auto; place-items: center; border-radius: 10px; color: var(--finance-blue); background: #eeefff; }
        .finance-page .finance-filter-summary-end { display: inline-flex; align-items: center; gap: .45rem; color: var(--finance-muted); }
        .finance-page .finance-filter-count { display: inline-flex; align-items: center; min-height: 22px; padding: 0 .45rem; border-radius: 999px; color: var(--finance-blue); background: #eeefff; font-size: .62rem; font-weight: 800; white-space: nowrap; }
        .finance-page .finance-filter-chevron { transition: transform .18s ease; }
        .finance-page .finance-filter-disclosure:not([open]) .finance-filter-chevron { transform: rotate(-90deg); }
        .finance-page .finance-filter-body { padding: .25rem 1.15rem 1.25rem; }
        .finance-page .finance-filter-body .form-label { color: #475569; font-size: .74rem; font-weight: 700; }
        .finance-page .finance-filter-search { position: relative; }
        .finance-page .finance-filter-search > i { position: absolute; top: 50%; left: .75rem; z-index: 1; color: #98a2b3; transform: translateY(-50%); pointer-events: none; }
        .finance-page .finance-filter-search .form-control { padding-left: 2.1rem; }
        .finance-page .finance-date-section { display: flex; align-items: center; width: 100%; min-width: 0; min-height: 40px; overflow: hidden; border: 1px solid #dce2e9; border-radius: var(--finance-control-radius); background: #fff; transition: border-color .16s ease, box-shadow .16s ease; }
        .finance-page .finance-date-section:focus-within { border-color: var(--finance-brand); box-shadow: 0 0 0 .2rem rgba(91, 92, 226, .12); }
        .finance-page .finance-date-section > i { flex: 0 0 auto; margin-left: .72rem; color: #98a2b3; pointer-events: none; }
        .finance-page .finance-date-section .rts-date-picker { width: 100%; min-width: 0; min-height: 38px; flex: 1 1 auto; padding: .5rem .72rem; border: 0; outline: 0; color: var(--finance-ink); background: transparent; font-size: .82rem; line-height: 1.3; cursor: pointer; }
        .finance-page .finance-date-section .rts-date-picker::placeholder { color: #98a2b3; }
        .finance-page .finance-date-clear { display: inline-grid; width: 40px; height: 40px; flex: 0 0 auto; margin-right: .2rem; place-items: center; border: 0; border-radius: 8px; color: #98a2b3; background: transparent; cursor: pointer; }
        .finance-page .finance-date-clear:hover { color: var(--finance-blue); background: #eeefff; }
        .finance-page .finance-date-clear:focus-visible { outline: 3px solid rgba(91, 92, 226, .25); outline-offset: 1px; }
        .finance-page .finance-filter-grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(220px, 1fr) minmax(0, 1fr); column-gap: 1.25rem !important; row-gap: 1rem; align-items: end; }
        .finance-page .finance-filter-grid.has-status { grid-template-columns: minmax(0, 1.05fr) minmax(190px, .95fr) minmax(0, 1fr) minmax(145px, .85fr); }
        .finance-page .finance-filter-grid > * { min-width: 0; }
        .finance-page .finance-filter-field { display: grid; min-width: 0; gap: .4rem; padding-inline: .2rem; }
        .finance-page .finance-filter-period-field { min-width: 0; }
        .finance-page .finance-filter-field .form-label { margin: 0; color: var(--finance-muted); font-size: .72rem; font-weight: 800; }
        .finance-page .finance-filter-footer { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 1.15rem; padding-top: .9rem; border-top: 1px solid rgba(208, 213, 221, .72); }
        .finance-page .finance-filter-note { display: inline-flex; align-items: center; gap: .4rem; min-width: 0; color: var(--finance-muted); font-size: .72rem; line-height: 1.4; }
        .finance-page .finance-filter-note i { flex: 0 0 auto; color: var(--finance-brand); }
        .finance-page .finance-filter-reset { display: inline-flex; align-items: center; gap: .35rem; color: var(--finance-blue); font-size: .72rem; font-weight: 800; text-decoration: none; white-space: nowrap; }
        .finance-page .finance-filter-reset:hover { color: var(--finance-brand); text-decoration: underline; text-underline-offset: 3px; }
        .finance-page .finance-filter-card .form-control,
        .finance-page .finance-filter-card .form-select { min-height: 40px; padding: .5rem .72rem; color: var(--finance-ink); font-size: .82rem; line-height: 1.3; }
        .finance-page .finance-filter-card .form-control::placeholder { color: #98a2b3; }
        .finance-page .btn { font-size: .78rem; font-weight: 750; }
        .finance-page .finance-table-wrap { overflow-x: auto; }
        .finance-page .finance-table { min-width: 760px; }
        .finance-page .finance-table td,
        .finance-page .finance-table th { padding-top: .78rem; padding-bottom: .78rem; }
        .finance-page .finance-pagination { display: flex; align-items: center; justify-content: flex-end; gap: .75rem; flex-wrap: wrap; }
        .finance-page .finance-pagination .pagination { margin-bottom: 0; flex-wrap: wrap; gap: .2rem; }
        .finance-page .finance-pagination .page-link { border-radius: 8px; }
        .finance-page .finance-status-dot { display: inline-block; width: .45rem; height: .45rem; margin-right: .3rem; border-radius: 50%; background: currentColor; vertical-align: .08em; }
        .finance-page .finance-mobile-only { display: none; }
        @media (min-width: 768px) and (max-width: 1279.98px) {
            .finance-page .finance-sidebar-layout { display: block; grid-template-columns: none; }
            .finance-page .finance-sidebar { position: static; width: 100%; max-width: none; margin-bottom: 1rem; }
            .finance-page .finance-tab-content { width: 100%; }
            .finance-page .finance-sidebar .card-body { padding: .9rem; }
            .finance-page .finance-filter-grid,
            .finance-page .finance-filter-grid.has-status { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .finance-page .finance-filter-footer { align-items: flex-start; flex-direction: column; }
        }
        @media (max-width: 767.98px) {
            .finance-page { padding-inline: .75rem !important; padding-top: 1rem !important; }
            .finance-page .finance-page-header { padding: 1.1rem; border-radius: 16px; }
            .finance-page .finance-page-header .badge { align-self: flex-start; }
            .finance-page .finance-page-header .finance-header-badge { align-self: flex-start; }
            .finance-page .finance-page-subtitle { max-width: 31rem; font-size: .82rem; line-height: 1.5; }
            .finance-page .finance-tabs { margin-inline: -.1rem; overflow-x: auto; scrollbar-width: none; }
            .finance-page .finance-tabs::-webkit-scrollbar { display: none; }
            .finance-page .finance-tabs .nav-link { min-height: 44px; flex: 0 0 auto; padding-inline: .78rem; }
            .finance-page .finance-card .card-body { padding: 1rem; }
            .finance-page .finance-card .card-header { padding: .85rem 1rem; }
            .finance-page .finance-sidebar-layout { display: block; }
            .finance-page .finance-sidebar { position: static; margin-bottom: 1rem; }
            .finance-page .finance-sidebar .card-body { padding: 1rem; }
            .finance-page .finance-filter-summary { min-height: 64px; padding: .85rem 1rem; }
            .finance-page .finance-filter-body { padding: .2rem 1rem 1rem; }
            .finance-page .finance-filter-grid,
            .finance-page .finance-filter-grid.has-status { display: grid; grid-template-columns: 1fr; gap: .78rem; }
            .finance-page .finance-filter-field { padding-inline: 0; }
            .finance-page .finance-filter-footer { align-items: stretch; flex-direction: column; gap: .75rem; margin-top: 1rem; padding-top: .8rem; }
            .finance-page .finance-filter-note { align-items: flex-start; }
            .finance-page .finance-sidebar .form-control,
            .finance-page .finance-sidebar .form-select,
            .finance-page .finance-sidebar .btn,
            .finance-page .finance-filter-card .form-control,
            .finance-page .finance-filter-card .form-select,
            .finance-page .finance-filter-card .btn { min-height: 44px; }
            .finance-page .finance-filter-card .form-control,
            .finance-page .finance-filter-card .form-select { font-size: 16px; }
            .finance-page .finance-date-section { min-height: 44px; }
            .finance-page .finance-date-section .rts-date-picker { min-height: 42px; padding-inline: .65rem; font-size: 16px; }
            .finance-page .finance-filter-card .card-body { padding: 1rem; }
            .finance-page .finance-filter-card .row { row-gap: .75rem; }
            .finance-page .finance-filter-card .btn { width: 100%; }
            .finance-page .finance-kpi { min-height: 104px; }
            .finance-page .finance-kpi .card-body { padding: .9rem; }
            .finance-page .finance-number { font-size: 1.18rem; }
            .finance-page .finance-callout { padding: .8rem; }
            .finance-page .finance-table-wrap { overflow: visible; }
            .finance-page .finance-table { min-width: 0; border-collapse: separate; border-spacing: 0; background: var(--finance-soft); }
            .finance-page .finance-table tbody { display: grid; gap: .75rem; padding: .75rem; background: var(--finance-soft); }
            .finance-page .finance-table thead { display: none; }
            .finance-page .finance-table,
            .finance-page .finance-table tbody,
            .finance-page .finance-table tr,
            .finance-page .finance-table td { display: block; width: 100%; }
            .finance-page .finance-table tbody tr { margin-bottom: .7rem; padding: .7rem 1rem; border: 1px solid var(--finance-line); border-radius: 14px; background: #fff; box-shadow: 0 4px 14px rgba(15, 23, 42, .045); }
            .finance-page .finance-table tbody tr:last-child { border-bottom: 1px solid var(--finance-line); }
            .finance-page .finance-table tbody td,
            .finance-page .finance-table tbody td:first-child { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; min-width: 0 !important; padding: .42rem 0; border: 0; text-align: right; white-space: normal; }
            .finance-page .finance-table tbody td::before { flex: 0 0 38%; color: var(--finance-muted); font-size: .68rem; font-weight: 800; letter-spacing: .03em; line-height: 1.25; text-align: left; text-transform: uppercase; content: attr(data-label); }
            .finance-page .finance-table tbody td[colspan] { display: block; padding: 0; text-align: center; }
            .finance-page .finance-table tbody td[colspan]::before { display: none; }
            .finance-page .finance-table tbody td:first-child { color: var(--finance-ink); font-weight: 700; }
            .finance-page .finance-table tbody td:first-child::before { color: var(--finance-blue); }
            .finance-page .finance-table tbody td { font-size: .78rem; }
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
        @supports (container-type: inline-size) {
            @container finance-page (max-width: 899.98px) {
                .finance-page .finance-sidebar-layout { display: block; grid-template-columns: none; }
                .finance-page .finance-sidebar { position: static; width: 100%; max-width: none; margin-bottom: 1rem; }
                .finance-page .finance-tab-content { width: 100%; }
            }

            @container finance-page (max-width: 767.98px) {
                .finance-page .finance-page-header { padding: 1.1rem; border-radius: 16px; }
                .finance-page .finance-page-header .finance-header-badge { align-self: flex-start; }
                .finance-page .finance-page-subtitle { max-width: 31rem; font-size: .82rem; line-height: 1.5; }
                .finance-page .finance-tabs { margin-inline: -.1rem; overflow-x: auto; scrollbar-width: none; }
                .finance-page .finance-tabs::-webkit-scrollbar { display: none; }
                .finance-page .finance-tabs .nav-link { min-height: 44px; flex: 0 0 auto; padding-inline: .78rem; }
                .finance-page .finance-card .card-body { padding: 1rem; }
                .finance-page .finance-card .card-header { padding: .85rem 1rem; }
                .finance-page .finance-sidebar { position: static; margin-bottom: 1rem; }
                .finance-page .finance-sidebar .card-body { padding: 1rem; }
                .finance-page .finance-filter-summary { min-height: 64px; padding: .85rem 1rem; }
                .finance-page .finance-filter-body { padding: .2rem 1rem 1rem; }
                .finance-page .finance-filter-grid,
                .finance-page .finance-filter-grid.has-status { grid-template-columns: 1fr; gap: .78rem; }
                .finance-page .finance-filter-field { padding-inline: 0; }
                .finance-page .finance-filter-footer { align-items: stretch; flex-direction: column; gap: .75rem; margin-top: 1rem; padding-top: .8rem; }
                .finance-page .finance-filter-note { align-items: flex-start; }
                .finance-page .finance-sidebar .form-control,
                .finance-page .finance-sidebar .form-select,
                .finance-page .finance-filter-card .form-control,
                .finance-page .finance-filter-card .form-select { min-height: 44px; font-size: 16px; }
                .finance-page .finance-date-section { min-height: 44px; }
                .finance-page .finance-date-section .rts-date-picker { min-height: 42px; padding-inline: .65rem; font-size: 16px; }
                .finance-page .finance-table-wrap { overflow: visible; }
                .finance-page .finance-table { min-width: 0; border-collapse: separate; border-spacing: 0; background: var(--finance-soft); }
                .finance-page .finance-table tbody { display: grid; gap: .75rem; padding: .75rem; background: var(--finance-soft); }
                .finance-page .finance-table thead { display: none; }
                .finance-page .finance-table,
                .finance-page .finance-table tbody,
                .finance-page .finance-table tr,
                .finance-page .finance-table td { display: block; width: 100%; }
                .finance-page .finance-table tbody tr { margin-bottom: .7rem; padding: .7rem 1rem; border: 1px solid var(--finance-line); border-radius: 14px; background: #fff; box-shadow: 0 4px 14px rgba(15, 23, 42, .045); }
                .finance-page .finance-table tbody tr:last-child { border-bottom: 1px solid var(--finance-line); }
                .finance-page .finance-table tbody td,
                .finance-page .finance-table tbody td:first-child { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; min-width: 0 !important; padding: .42rem 0; border: 0; text-align: right; white-space: normal; }
                .finance-page .finance-table tbody td::before { flex: 0 0 38%; color: var(--finance-muted); font-size: .68rem; font-weight: 800; letter-spacing: .03em; line-height: 1.25; text-align: left; text-transform: uppercase; content: attr(data-label); }
                .finance-page .finance-table tbody td[colspan] { display: block; padding: 0; text-align: center; }
                .finance-page .finance-table tbody td[colspan]::before { display: none; }
                .finance-page .finance-table tbody td:first-child { color: var(--finance-ink); font-weight: 700; }
                .finance-page .finance-table tbody td:first-child::before { color: var(--finance-blue); }
                .finance-page .finance-table tbody td { font-size: .78rem; }
                .finance-page .finance-pagination { align-items: flex-start; justify-content: space-between; }
                .finance-page .finance-pagination .pagination { max-width: 100%; }
            }
        }
        /* Keep the existing desktop table responsive rule as a fallback for other tables. */
        @media (max-width: 767.98px) {
            .finance-page .table:not(.finance-table) { min-width: 0; }
        }
    </style>
    <div class="container-fluid py-4 finance-page">
        <header class="finance-page-header d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div class="finance-page-header-copy">
                <div class="finance-eyebrow mb-2"><span class="finance-page-header-icon"><i class="bi {{ $activeFinanceMeta['icon'] }}" aria-hidden="true"></i></span>{{ $activeFinanceMeta['eyebrow'] }}</div>
                <h1 class="finance-page-title mb-1">{{ $activeFinanceMeta['title'] }}</h1>
                <div class="finance-page-subtitle">{{ $activeFinanceMeta['description'] }}</div>
            </div>
            <span class="finance-header-badge"><i class="bi bi-shield-check" aria-hidden="true"></i>{{ $activeFinanceMeta['badge'] }}</span>
        </header>

        <nav class="finance-nav finance-tabs nav nav-pills gap-2 mb-4" aria-label="Marketplace Finance">
            <a class="nav-link {{ request()->routeIs('marketplace.finance.index') ? 'active' : '' }}" @if(request()->routeIs('marketplace.finance.index')) aria-current="page" @endif href="{{ route('marketplace.finance.index', request()->except('status')) }}"><i class="bi bi-grid-1x2" aria-hidden="true"></i>Overview</a>
            <a class="nav-link {{ request()->routeIs('marketplace.finance.transactions') ? 'active' : '' }}" @if(request()->routeIs('marketplace.finance.transactions')) aria-current="page" @endif href="{{ route('marketplace.finance.transactions', request()->except('status')) }}"><i class="bi bi-receipt" aria-hidden="true"></i>Transactions</a>
            <a class="nav-link {{ request()->routeIs('marketplace.finance.settlements') ? 'active' : '' }}" @if(request()->routeIs('marketplace.finance.settlements')) aria-current="page" @endif href="{{ route('marketplace.finance.settlements', request()->except('status')) }}"><i class="bi bi-bank" aria-hidden="true"></i>Settlements</a>
            <a class="nav-link {{ request()->routeIs('marketplace.finance.reconciliation') ? 'active' : '' }}" @if(request()->routeIs('marketplace.finance.reconciliation')) aria-current="page" @endif href="{{ route('marketplace.finance.reconciliation', request()->except('status')) }}"><i class="bi bi-check2-square" aria-hidden="true"></i>Reconciliation</a>
            <a class="nav-link {{ request()->routeIs('marketplace.finance.fee-analysis') ? 'active' : '' }}" @if(request()->routeIs('marketplace.finance.fee-analysis')) aria-current="page" @endif href="{{ route('marketplace.finance.fee-analysis', request()->except('status')) }}"><i class="bi bi-bar-chart-line" aria-hidden="true"></i>Fee Analysis</a>
        </nav>

        @hasSection('finance-sidebar')
            <div class="finance-sidebar-layout">
                <aside class="finance-sidebar" aria-label="{{ $financeRoute === 'marketplace.finance.settlements' ? 'Filter settlement' : 'Filter transaksi' }}">
                    @yield('finance-sidebar')
                </aside>
                <main class="finance-tab-content">
                    @yield('finance-content')
                </main>
            </div>
        @else
            @include('marketplace.finance._filters')
            <main class="finance-tab-content">
                @yield('finance-content')
            </main>
        @endif
    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-finance-date-range-picker]').forEach(function (input) {
                    if (window.GFID && typeof window.GFID.initDateRange === 'function') {
                        const fromInput = document.getElementById(input.dataset.financeDateFrom);
                        const toInput = document.getElementById(input.dataset.financeDateTo);
                        const clearButton = input.closest('.finance-date-section')?.querySelector('[data-finance-date-clear]');
                        const defaultDates = [fromInput?.value, toInput?.value]
                            .filter(Boolean)
                            .map(function (value) {
                                return window.flatpickr?.parseDate ? window.flatpickr.parseDate(value, 'Y-m-d') : value;
                            });
                        let lastRangeValue = [fromInput?.value || '', toInput?.value || ''].join('|');
                        const syncClearButton = function () {
                            if (clearButton) clearButton.hidden = !(fromInput?.value || toInput?.value);
                        };

                        window.GFID.initDateRange(input, {
                            altInput: false,
                            dateFormat: 'j M Y',
                            rangeSeparator: ' – ',
                            position: 'auto left',
                            defaultDate: defaultDates,
                            allowInput: false,
                            onReady: function (_selectedDates, _dateString, instance) {
                                instance.input.classList.add('gf-date-input');
                                instance.calendarContainer.classList.add('gf-date-calendar');
                                syncClearButton();
                            },
                            onChange: function (selectedDates) {
                                if (!fromInput || !toInput || !window.flatpickr) return;
                                fromInput.value = selectedDates[0]
                                    ? window.flatpickr.formatDate(selectedDates[0], 'Y-m-d')
                                    : '';
                                toInput.value = selectedDates[1]
                                    ? window.flatpickr.formatDate(selectedDates[1], 'Y-m-d')
                                    : '';
                                syncClearButton();
                            },
                            onClose: function (selectedDates) {
                                if (!fromInput || !toInput || !window.flatpickr) return;
                                if (selectedDates.length === 1) {
                                    const date = window.flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                                    fromInput.value = date;
                                    toInput.value = date;
                                }
                                const nextRangeValue = [fromInput.value, toInput.value].join('|');
                                if (nextRangeValue !== lastRangeValue) {
                                    lastRangeValue = nextRangeValue;
                                    const form = input.closest('form');
                                    if (form?.requestSubmit) form.requestSubmit();
                                }
                            },
                        });

                        clearButton?.addEventListener('click', function () {
                            const nextRangeValue = '|';
                            input._flatpickr?.clear();
                            if (fromInput) fromInput.value = '';
                            if (toInput) toInput.value = '';
                            syncClearButton();
                            if (nextRangeValue !== lastRangeValue) {
                                lastRangeValue = nextRangeValue;
                                const form = input.closest('form');
                                if (form?.requestSubmit) form.requestSubmit();
                            }
                        });
                    }
                });

                document.querySelectorAll('[data-finance-auto-filter]').forEach(function (form) {
                    let searchTimer;
                    const submit = function () {
                        if (form.dataset.financeSubmitting === 'true') return;
                        form.dataset.financeSubmitting = 'true';
                        if (form.requestSubmit) form.requestSubmit();
                        else form.submit();
                    };

                    form.querySelectorAll('select').forEach(function (select) {
                        select.addEventListener('change', submit);
                    });

                    form.querySelectorAll('input[type="search"]').forEach(function (search) {
                        search.addEventListener('input', function () {
                            window.clearTimeout(searchTimer);
                            searchTimer = window.setTimeout(submit, 500);
                        });
                        search.addEventListener('keydown', function (event) {
                            if (event.key !== 'Enter') return;
                            event.preventDefault();
                            window.clearTimeout(searchTimer);
                            submit();
                        });
                    });
                });

                const financePage = document.querySelector('.finance-page');
                if (financePage && financePage.getBoundingClientRect().width <= 767.98) {
                    document.querySelectorAll('[data-finance-filter]').forEach(function (filter) {
                        filter.removeAttribute('open');
                    });
                }
            });
        </script>
    @endpush
@endsection
