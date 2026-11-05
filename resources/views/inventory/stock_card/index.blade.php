@extends('layouts.app')

@section('title', 'Inventory • Kartu Stok')

@push('head')
    <style>
        .stock-card-page {
            --shp-accent:var(--gf-color-brand,#5b5ce2);
            --shp-accent-2:var(--gf-color-brand-strong,#4142bb);
            --shp-border:var(--gf-color-line,#e4e7ec);
            --shp-border-strong:color-mix(in srgb, var(--gf-color-line,#e4e7ec) 82%, var(--gf-color-brand,#5b5ce2) 18%);
            --shp-muted:var(--gf-color-muted,#667085);
            --sc-content:var(--gf-color-content,var(--text,#101828));
            --sc-surface:var(--gf-color-surface,var(--card,#fff));
            --sc-surface-muted:var(--gf-color-surface-muted,var(--card-soft,#f9fafb));
            --sc-shadow:var(--gf-shadow-sm,0 1px 2px rgba(16,24,40,.04),0 4px 12px rgba(16,24,40,.04));
            --sc-radius-card:var(--gf-radius-card,16px);
            --sc-radius-control:var(--gf-radius-control,10px);
            --in:var(--gf-color-accent,#13b8c4);
            --out:var(--gf-color-warning,#b7791f);
            --summary-final:var(--gf-color-brand,#5b5ce2);
        }

        .page-wrap { max-width:var(--gf-content-max,1280px); margin-inline:auto; padding:1rem var(--gf-content-gutter,.85rem) 4.5rem; background:transparent!important; }

        .stock-card-page > #sc_kpi,
        .stock-card-page > #stockCardFilter,
        .stock-card-page > #sc_table_wrap { width:100%; min-width:0; }

        .card-main {
            background: var(--sc-surface);
            border-radius: var(--sc-radius-card);
            border: 1px solid var(--shp-border);
            box-shadow: var(--sc-shadow);
            overflow:hidden;
        }
        body[data-theme="dark"] .card-main {
            border-color: var(--shp-border);
        }

        .hero-panel {
            position: relative;
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:1rem;
            flex-wrap:wrap;
            padding:.9rem 1rem;
            margin-bottom:1rem;
            background: var(--sc-surface);
            border: 1px solid var(--shp-border);
            box-shadow: var(--sc-shadow);
        }
        body[data-theme="dark"] .hero-panel { background: var(--sc-surface); }
        .hero-copy { min-width: 0; flex: 1 1 520px; }
        .hero-kicker {
            font-size: .66rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--shp-muted);
            margin-bottom: .15rem;
        }
        body[data-theme="dark"] .hero-kicker { color:var(--shp-muted); }
        .hero-title { font-weight: 800; font-size:1.45rem; letter-spacing: -.02em; margin:0; color:var(--sc-content); }
        .hero-desc { color:var(--shp-muted); font-size:.8rem; line-height:1.45; margin-top:.3rem; }
        body[data-theme="dark"] .hero-desc { color:#cbd5e1; }
        .hero-meta {
            display:flex;
            flex-wrap:wrap;
            gap:.32rem;
            margin-top:.45rem;
        }
        .hero-pill {
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            border-radius:999px;
            padding:.28rem .58rem;
            border:1px solid var(--shp-border-strong);
            background: transparent;
            font-size:.72rem;
            color:var(--shp-accent);
            white-space:nowrap;
        }
        body[data-theme="dark"] .hero-pill { background:var(--sc-surface-muted); color:var(--sc-content); }
        .hero-pill strong {
            font-size: .66rem;
            letter-spacing: 0;
            color: var(--shp-muted);
            font-weight: 700;
        }
        body[data-theme="dark"] .hero-pill strong { color: var(--shp-muted); }

        .hero-actions {
            flex: 0 0 auto;
            padding-top: .15rem;
        }

        .filter-panel {
            border-radius: 14px;
            background: var(--sc-surface);
        }
        body[data-theme="dark"] .filter-panel { background:var(--sc-surface); }
        .search-upper {
            text-transform: uppercase;
        }

        .summary-grid {
            display:grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap:.7rem;
            margin-bottom: 1rem;
        }
        .summary-card {
            position: relative;
            overflow: hidden;
            min-height: 108px;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
            gap:.35rem;
            padding:.85rem .9rem .85rem 1rem;
            border-radius: var(--sc-radius-card);
            border: 1px solid var(--shp-border);
            background: var(--sc-surface);
            box-shadow: var(--sc-shadow);
        }
        body[data-theme="dark"] .summary-card {
            background: var(--sc-surface);
            border-color: var(--shp-border);
        }
        .summary-card::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: var(--summary-accent, var(--bs-primary));
        }
        .summary-card--soft { --summary-accent: var(--gf-color-muted,#667085); }
        .summary-card--in { --summary-accent: var(--in); }
        .summary-card--out { --summary-accent: var(--out); }
        .summary-card--final {
            --summary-accent: var(--summary-final);
            background: linear-gradient(180deg, color-mix(in srgb, var(--summary-final) 8%, var(--sc-surface) 92%), var(--sc-surface));
        }
        body[data-theme="dark"] .summary-card--final { background:linear-gradient(180deg, color-mix(in srgb, var(--summary-final) 14%, var(--sc-surface) 86%), var(--sc-surface)); }
        .summary-label {
            font-size: .68rem;
            letter-spacing: .11em;
            text-transform: uppercase;
            color: var(--shp-muted);
        }
        .summary-card-head { display:flex; align-items:center; justify-content:space-between; gap:.5rem; }
        .summary-icon { width:26px; height:26px; display:grid; place-items:center; border-radius:8px; color:var(--summary-accent,var(--shp-accent)); background:color-mix(in srgb, var(--summary-accent,var(--shp-accent)) 12%, var(--sc-surface) 88%); font-size:.78rem; }
        .summary-value {
            font-weight: 850;
            font-size: 1.35rem;
            line-height: 1.1;
            color: var(--shp-accent);
        }
        body[data-theme="dark"] .summary-value { color:var(--sc-content); }
        .summary-sub {
            font-size: .75rem;
            color: var(--shp-muted);
            line-height: 1.35;
        }
        body[data-theme="dark"] .summary-sub { color:var(--shp-muted); }

        .summary-inline-note {
            color: var(--shp-muted);
            font-size: .78rem;
        }
        body[data-theme="dark"] .summary-inline-note { color:var(--shp-muted); }

        .stock-footer-summary {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:1rem;
            flex-wrap:wrap;
            margin-top:.8rem;
            padding:.9rem 1rem;
            border-radius:var(--sc-radius-card);
            border:1px solid var(--shp-border);
            background: linear-gradient(180deg, color-mix(in srgb, var(--summary-final) 7%, var(--sc-surface) 93%), var(--sc-surface));
        }
        body[data-theme="dark"] .stock-footer-summary {
            background: linear-gradient(180deg, color-mix(in srgb, var(--summary-final) 12%, var(--sc-surface) 88%), var(--sc-surface));
            border-color: var(--shp-border);
        }
        .stock-footer-summary .footer-meta {
            display:flex;
            flex-wrap:wrap;
            gap:.9rem 1.2rem;
        }
        .stock-footer-summary .footer-block {
            min-width: 120px;
        }
        .stock-footer-summary .footer-label {
            font-size:.68rem;
            letter-spacing:.11em;
            text-transform:uppercase;
            color:var(--shp-muted);
        }
        .stock-footer-summary .footer-value {
            font-size:1.05rem;
            font-weight:800;
            color:var(--shp-accent);
        }
        body[data-theme="dark"] .stock-footer-summary .footer-value { color:var(--sc-content); }

        .controls { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
        .filter-label { font-size:.8rem; color:var(--shp-muted); margin-bottom:.2rem; display:block; }
        .filter-select { border-radius:var(--sc-radius-control); padding-left:.75rem; padding-right:2rem; font-size:.82rem; }
        .btn-pill { border-radius:var(--sc-radius-control); padding-inline:.78rem; box-shadow:none!important; font-weight:600; }

        .stock-card-page .btn-ship-primary {
            background:var(--shp-accent)!important;
            border-color:var(--shp-accent)!important;
            color:#fff!important;
        }
        .stock-card-page .btn-ship-primary:hover,
        .stock-card-page .btn-ship-primary:focus-visible {
            background:var(--shp-accent-2)!important;
            border-color:var(--shp-accent-2)!important;
        }
        .stock-card-page .btn-ship-outline {
            background:var(--sc-surface)!important;
            border-color:var(--shp-border-strong)!important;
            color:var(--shp-accent)!important;
        }
        .stock-card-page .btn-ship-outline:hover,
        .stock-card-page .btn-ship-outline:focus-visible {
            background:color-mix(in srgb, var(--shp-accent) 7%, var(--sc-surface) 93%)!important;
            border-color:var(--shp-accent)!important;
        }
        
        .table-responsive { max-height: calc(100vh - 220px); overflow-y: auto; }
        .table-list { margin-bottom:0; }
        .table-list thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom-width:1px;
            font-size:.68rem;
            text-transform:none;
            letter-spacing:0;
            color:var(--shp-muted);
            background: var(--sc-surface);
            padding:.52rem .62rem;
            white-space:nowrap;
        }
        body[data-theme="dark"] .table-list thead th {
            background:var(--sc-surface);
            color:var(--shp-muted);
            border-bottom-color:var(--shp-border);
        }
        .table-list tbody td {
            vertical-align:middle;
            border-top-color: var(--shp-border);
            padding:.52rem .62rem;
        }
        body[data-theme="dark"] .table-list tbody td { border-top-color:var(--shp-border); }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }

        .muted { color: var(--shp-muted); }

        .sc-filter-card { padding: .9rem 1rem 1rem; }
        .sc-filter-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.75rem; }
        .sc-filter-title { display:flex; align-items:center; gap:.45rem; color:var(--shp-accent); font-size:.84rem; font-weight:800; }
        .sc-filter-title i { color:var(--summary-final); }
        .sc-filter-help { color:var(--shp-muted); font-size:.72rem; }
        .sc-filter-field { min-width:0; }
        .sc-filter-field--search { flex:1 1 240px; }
        .sc-filter-field--warehouse { flex:1 1 180px; }
        .sc-filter-field--range { flex:1.25 1 245px; }
        .sc-filter-field--compact { flex:0 1 155px; }
        .sc-filter-row { display:flex; flex-wrap:wrap; align-items:end; gap:.65rem; }
        .sc-filter-row .filter-label { margin-bottom:.28rem; }
        .sc-range-control { position:relative; }
        .sc-range-control .form-control { padding-right:2.3rem; cursor:pointer; }
        .sc-range-control > i { position:absolute; right:.72rem; top:50%; transform:translateY(-50%); pointer-events:none; color:var(--shp-muted); }
        .sc-range-control .flatpickr-input[readonly] { background-color:var(--sc-surface); }
        .sc-filter-check { display:flex; align-items:center; gap:.45rem; min-height:38px; padding:.45rem .65rem; border:1px solid var(--shp-border); border-radius:var(--sc-radius-control); color:var(--shp-muted); font-size:.76rem; white-space:nowrap; }
        .sc-filter-check input { margin:0; accent-color:var(--summary-final); }
        .flatpickr-calendar.sc-date-calendar { border-radius:12px; box-shadow:0 14px 35px rgba(15,23,42,.16); }
        /* Date range tetap satu kalender; cegah konfigurasi global/legacy membuka dua bulan. */
        .flatpickr-calendar.sc-date-calendar { width:307px; }
        .sc-date-calendar .flatpickr-months > .flatpickr-month:nth-of-type(n+2),
        .sc-date-calendar .flatpickr-weekdays > .flatpickr-weekdaycontainer:nth-of-type(n+2),
        .sc-date-calendar .flatpickr-days > .dayContainer:nth-of-type(n+2) { display:none; }
        .sc-date-calendar .flatpickr-days .dayContainer { width:307px; min-width:307px; max-width:307px; }

        .gf-datecell {
            display: flex;
            flex-direction: column;
            line-height: 1.18;
            white-space: nowrap;
        }
        .gf-datecell-d {
            color: var(--shp-accent);
            font-size: .78rem;
            font-weight: 650;
            font-variant-numeric: tabular-nums;
        }
        .gf-datecell-sub {
            color: var(--shp-muted);
            font-size: .68rem;
            font-variant-numeric: tabular-nums;
        }
        body[data-theme="dark"] .gf-datecell-d { color:var(--sc-content); }
        body[data-theme="dark"] .gf-datecell-sub { color:var(--shp-muted); }

        .sub-badge {
            background: color-mix(in srgb, var(--shp-accent) 10%, var(--sc-surface) 90%);
            border: 1px solid var(--shp-border);
            border-radius: 999px;
            padding: .14rem .5rem;
            font-size: .78rem;
            white-space: nowrap;
        }

        .stock-card-page .sc-source-badge {
            background:color-mix(in srgb, var(--shp-accent) 9%, var(--sc-surface) 91%)!important;
            border:1px solid color-mix(in srgb, var(--shp-accent) 18%, var(--shp-border) 82%);
            color:var(--shp-accent)!important;
        }

        .qty-cell {
            display: grid;
            grid-template-columns: auto minmax(5.2rem, auto) auto;
            align-items: baseline;
            justify-items: end;
            column-gap: .35rem;
            white-space: nowrap;
            font-variant-numeric: tabular-nums
        }
        .qty-num { min-width: 5.2rem; text-align: right; }
        .qty-unit { color: var(--shp-muted); }
        .qty-balance {
            display: inline-block;
            min-width: 5.2rem;
            text-align: right;
        }
        .qty-balance-zero {
            color: var(--summary-final);
            font-weight: 800;
        }
        .summary-value--zero {
            color: var(--summary-final);
        }
        .qty-in-num { color: var(--in); font-weight: 600; }
        .qty-out-num { color: var(--out); font-weight: 600; }
        .qty-zero { color: var(--shp-muted); }

        .search-clear {
            position: absolute;
            right: .5rem;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: var(--shp-muted);
            padding: .1rem .25rem;
            border-radius: 8px;
        }
        .search-clear:hover { background: rgba(148,163,184,.18); color: inherit; }
        .loading { opacity: .65; pointer-events: none; }

        /* Stock card component contract: primary filters stay visible; secondary filters disclose. */
        .sc-filter-card { padding:1rem; }
        .sc-filter-head { margin-bottom:.85rem; }
        .sc-filter-title { font-size:.9rem; }
        .sc-filter-title i { width:1.2rem; height:1.2rem; display:grid; place-items:center; border-radius:6px; background:color-mix(in srgb, var(--summary-final) 12%, var(--sc-surface) 88%); }
        .sc-filter-primary { display:grid; grid-template-columns:minmax(220px,1.35fr) minmax(170px,1fr) minmax(235px,1.15fr) minmax(140px,.7fr); gap:.7rem; align-items:end; }
        .sc-filter-details { margin-top:.85rem; border-top:1px solid var(--shp-border); }
        .sc-filter-details summary { display:flex; align-items:center; justify-content:space-between; gap:.75rem; min-height:44px; color:var(--shp-accent); cursor:pointer; font-size:.76rem; font-weight:800; list-style:none; }
        .sc-filter-details summary::-webkit-details-marker { display:none; }
        .sc-filter-details summary::after { content:'+'; color:var(--shp-muted); font-size:1rem; font-weight:500; }
        .sc-filter-details[open] summary::after { content:'−'; }
        .sc-filter-details summary:focus-visible { outline:2px solid var(--summary-final); outline-offset:3px; border-radius:4px; }
        .sc-filter-details-label { display:flex; align-items:center; gap:.45rem; }
        .sc-filter-details-label i { color:var(--shp-muted); }
        .sc-filter-active-dot { width:6px; height:6px; border-radius:50%; background:var(--summary-final); }
        .sc-filter-secondary { display:grid; grid-template-columns:repeat(3,minmax(150px,1fr)) auto; gap:.7rem; align-items:end; padding:.1rem 0 .1rem; }
        .sc-filter-secondary .sc-filter-check { min-height:38px; }

        .sc-table-shell { overflow:hidden; }
        .sc-table-toolbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.9rem 1rem; border-bottom:1px solid var(--shp-border); }
        .sc-table-title { color:var(--sc-content); font-size:.9rem; font-weight:800; }
        .sc-table-caption, .sc-table-scroll-hint { color:var(--shp-muted); font-size:.72rem; }
        .sc-table-scroll-hint { display:inline-flex; align-items:center; gap:.35rem; }
        .sc-table-scroll-hint::before { content:'↔'; font-size:.9rem; }
        .sc-table-desktop { max-height:calc(100vh - 330px); overflow:auto; }
        .sc-stock-table { min-width:980px; font-size:.78rem; }
        .sc-stock-table thead th { background:var(--sc-surface); font-size:.66rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase; }
        .sc-stock-table tbody tr { transition:background-color .15s ease; }
        .sc-stock-table tbody tr:hover td { background:color-mix(in srgb, var(--summary-final) 4%, var(--sc-surface) 96%); }
        .sc-stock-table .sc-item-code { color:var(--shp-accent); font-weight:800; }
        .sc-stock-table .sc-source-badge { display:inline-flex; max-width:210px; white-space:normal; line-height:1.25; }
        .sc-mobile-list { display:none; }
        .sc-mutation-card { padding:.9rem; border-bottom:1px solid var(--shp-border); }
        .sc-mutation-card:last-child { border-bottom:0; }
        .sc-mutation-card__top, .sc-mutation-card__meta, .sc-mutation-card__foot { display:flex; align-items:flex-start; justify-content:space-between; gap:.7rem; }
        .sc-mutation-card__top { margin-bottom:.75rem; }
        .sc-mutation-card__source { min-width:0; text-align:right; }
        .sc-mutation-card__source .badge { max-width:190px; white-space:normal; line-height:1.25; text-align:right; }
        .sc-mutation-card__detail { margin-top:.25rem; color:var(--shp-muted); font-size:.68rem; line-height:1.3; }
        .sc-mutation-card__meta { justify-content:flex-start; flex-wrap:wrap; margin-bottom:.8rem; }
        .sc-meta-pair { min-width:0; }
        .sc-meta-pair__label { display:block; color:var(--shp-muted); font-size:.63rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
        .sc-meta-pair__value { display:block; overflow:hidden; color:var(--shp-accent); font-size:.75rem; font-weight:750; text-overflow:ellipsis; white-space:nowrap; }
        .sc-mutation-card__figures { display:grid; grid-template-columns:repeat(3,1fr); gap:.45rem; }
        .sc-figure { min-width:0; padding:.55rem .6rem; border:1px solid var(--shp-border); border-radius:var(--sc-radius-control); background:color-mix(in srgb, var(--sc-surface) 92%, var(--sc-surface-muted) 8%); }
        .sc-figure--in { border-color:color-mix(in srgb, var(--in) 24%, var(--shp-border) 76%); }
        .sc-figure--out { border-color:color-mix(in srgb, var(--out) 24%, var(--shp-border) 76%); }
        .sc-figure--balance { border-color:color-mix(in srgb, var(--summary-final) 28%, var(--shp-border) 72%); }
        .sc-figure__label { display:block; color:var(--shp-muted); font-size:.63rem; font-weight:700; }
        .sc-figure__value { display:block; margin-top:.15rem; color:var(--shp-accent); font-size:.86rem; font-weight:850; }
        .sc-figure--in .sc-figure__value { color:var(--in); }
        .sc-figure--out .sc-figure__value { color:var(--out); }
        .sc-figure--balance .sc-figure__value { color:var(--summary-final); }
        .sc-mutation-card__foot { margin-top:.7rem; color:var(--shp-muted); font-size:.68rem; }
        .sc-empty-state { padding:2.25rem 1rem; text-align:center; }
        .sc-empty-state__icon { width:42px; height:42px; display:grid; place-items:center; margin:0 auto .65rem; border-radius:12px; background:color-mix(in srgb, var(--summary-final) 10%, var(--sc-surface) 90%); color:var(--summary-final); font-size:1.15rem; }
        .sc-empty-state__title { color:var(--shp-accent); font-size:.86rem; font-weight:800; }
        .sc-empty-state__desc { margin-top:.2rem; color:var(--shp-muted); font-size:.74rem; }

        @media(max-width:768px) {
            .page-wrap { padding:.65rem .65rem calc(var(--mobile-bottom-nav-clearance, 70px) + 1rem); }
            .hero-panel { position:relative; padding:.85rem; margin-bottom:.75rem; }
            .hero-title { font-size:1.18rem; }
            .hero-actions { width:100%; padding-top:.35rem; }
            .hero-actions .btn { min-height:42px; flex:1 1 0; }
            .hero-meta { gap:.3rem; }
            .hero-pill { max-width:100%; overflow:hidden; text-overflow:ellipsis; }
            .summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:.55rem; margin-bottom:.75rem; }
            .summary-card { min-height:90px; padding:.72rem .72rem .72rem .85rem; border-radius:12px; }
            .summary-value { font-size:1.12rem; }
            .summary-inline-note { font-size:.7rem; }
            .sc-filter-card { padding:.8rem; }
            .sc-filter-head { align-items:flex-start; flex-direction:column; gap:.18rem; }
            .sc-filter-primary, .sc-filter-secondary { grid-template-columns:1fr; gap:.6rem; }
            .sc-filter-field, .sc-filter-field--search, .sc-filter-field--warehouse, .sc-filter-field--range, .sc-filter-field--compact { flex:1 1 100%; }
            .sc-filter-check { width:100%; min-height:44px; }
            .sc-table-toolbar { align-items:flex-start; flex-direction:column; gap:.25rem; padding:.8rem .9rem; }
            .sc-table-scroll-hint { display:none; }
            .sc-table-desktop { display:none; }
            .sc-mobile-list { display:block; }
            .sc-mutation-card__meta { gap:.65rem 1rem; }
            .sc-meta-pair { flex:1 1 calc(50% - 1rem); }
            .sc-mutation-card__source .badge { max-width:160px; }
            .sc-figure { padding:.52rem .5rem; }
            .sc-figure__value { font-size:.78rem; }
        }

        @media(min-width:769px) {
            .sc-filter-details[open] .sc-filter-secondary { padding-top:.35rem; }
        }

        @media(prefers-reduced-motion:reduce) {
            .sc-stock-table tbody tr { transition:none; }
        }
    </style>
@endpush

@section('content')
    @php
        $itemId = $filters['item_id'] ?? null;
        $qItem = $filters['q_item'] ?? '';
        $warehouseId = $filters['warehouse_id'] ?? null;
        $warehouseLabel = optional($warehouses->firstWhere('id', $warehouseId))->name ?? 'Semua gudang';
        $periodLabel = sprintf(
            '%s - %s',
            !empty($filters['from_date']) ? \Illuminate\Support\Carbon::parse($filters['from_date'])->format('d/m/Y') : 'Awal',
            !empty($filters['to_date']) ? \Illuminate\Support\Carbon::parse($filters['to_date'])->format('d/m/Y') : 'Sekarang'
        );
        $modeLabel = $itemId && $selectedItem ? 'Mode item' : 'Mode semua transaksi';
        $subtitle = $itemId && $selectedItem
            ? 'Riwayat mutasi stok dan saldo akhir untuk item terpilih.'
            : 'Riwayat mutasi stok dan saldo akhir per baris transaksi.';
        $advancedFilterActive = !empty($filters['source_type'])
            || (($filters['sort'] ?? 'desc') === 'asc')
            || !empty($filters['has_cost']);
    @endphp

    <div class="page-wrap stock-card-page">

        {{-- Header --}}
        <div class="hero-panel card-main mb-3">
            <div class="hero-copy">
                <div class="hero-kicker">Inventory / Stock Card</div>
                <h1 class="hero-title">Kartu Stok</h1>
                <div class="hero-desc" id="sc_subtitle">
                    {{ $subtitle }}
                </div>
                <div class="hero-meta">
                    <span class="hero-pill"><strong>{{ $modeLabel }}</strong></span>
                    <span class="hero-pill"><strong>Gudang</strong> {{ $warehouseLabel }}</span>
                    <span class="hero-pill"><strong>Periode</strong> {{ $periodLabel }}</span>
                </div>
                <div class="hero-desc mt-2" id="sc_item_label" @if (!($itemId && $selectedItem)) style="display:none" @endif>
                    Item terpilih: <span class="mono fw-semibold">{{ $selectedItem->code ?? '' }}</span> - {{ $selectedItem->name ?? '' }}
                </div>
            </div>

            <div class="hero-actions controls">
                <a class="btn btn-ship-outline btn-pill btn-sm" href="{{ route('inventory.stock_card.index') }}">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
                @if ($itemId)
                    <a class="btn btn-ship-primary btn-pill btn-sm" href="{{ route('inventory.stock_card.export', request()->query()) }}">
                        <i class="bi bi-file-earmark-excel"></i> Export
                    </a>
                @endif
            </div>
        </div>
        
        {{-- KPI (AJAX replace) --}}
        <div id="sc_kpi">
            @include('inventory.stock_card._kpi')
        </div>

        {{-- Filter operasional --}}
        <form method="GET" action="{{ route('inventory.stock_card.index') }}" id="stockCardFilter" class="mb-3">
            <div class="card-main filter-panel sc-filter-card">
                <div class="sc-filter-head">
                    <div class="sc-filter-title"><i class="bi bi-sliders2"></i> Filter operasional</div>
                    <div class="sc-filter-help">Perubahan filter langsung memperbarui kartu stok</div>
                </div>

                <div class="sc-filter-primary">
                    <div class="sc-filter-field sc-filter-field--search">
                        <label class="filter-label">Cari Item</label>
                        <div class="position-relative">
                            <input type="text" name="q_item" value="{{ $qItem }}" class="form-control filter-select search-upper"
                                id="q_item_input" placeholder="Kode / nama item" autocomplete="off" aria-label="Cari kode atau nama item">
                            <button type="button" class="search-clear" id="q_item_clear"
                                style="{{ $qItem ? '' : 'display:none' }}" aria-label="Hapus pencarian item">✕</button>
                        </div>
                    </div>

                    <input type="hidden" name="item_id" value="{{ $itemId }}">

                    <div class="sc-filter-field sc-filter-field--warehouse">
                        <label class="filter-label">Gudang</label>
                        <select name="warehouse_id" class="form-select filter-select" aria-label="Filter gudang">
                            <option value="">Semua</option>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}" @selected(($filters['warehouse_id'] ?? null) == $w->id)>{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sc-filter-field sc-filter-field--range">
                        <label class="filter-label" for="sc_date_range">Periode mutasi</label>
                        <div class="sc-range-control">
                            <input type="text" id="sc_date_range" class="form-control filter-select" placeholder="Pilih rentang tanggal"
                                autocomplete="off" readonly data-date-range aria-label="Pilih rentang tanggal mutasi">
                            <i class="bi bi-calendar3"></i>
                        </div>
                        <input type="hidden" name="from_date" id="sc_from_date" value="{{ $filters['from_date'] ?? '' }}" data-gf-date="off">
                        <input type="hidden" name="to_date" id="sc_to_date" value="{{ $filters['to_date'] ?? '' }}" data-gf-date="off">
                    </div>

                    <div class="sc-filter-field sc-filter-field--compact">
                        <label class="filter-label">Arah mutasi</label>
                        <select name="direction" class="form-select filter-select" aria-label="Filter arah mutasi">
                            <option value="">Semua arah</option>
                            <option value="in" @selected(($filters['direction'] ?? '') === 'in')>Masuk (IN)</option>
                            <option value="out" @selected(($filters['direction'] ?? '') === 'out')>Keluar (OUT)</option>
                        </select>
                    </div>
                </div>

                <details class="sc-filter-details" @if($advancedFilterActive) open @endif>
                    <summary>
                        <span class="sc-filter-details-label"><i class="bi bi-sliders"></i> Filter lanjutan @if($advancedFilterActive)<span class="sc-filter-active-dot" aria-label="Ada filter lanjutan aktif"></span>@endif</span>
                        <span class="sc-filter-help">Sumber, urutan, nilai biaya</span>
                    </summary>
                    <div class="sc-filter-secondary">
                        <div class="sc-filter-field">
                            <label class="filter-label">Sumber mutasi</label>
                            <select name="source_type" class="form-select filter-select" aria-label="Filter sumber mutasi">
                                @foreach ($availableSourceTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['source_type'] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sc-filter-field">
                            <label class="filter-label">Urutan</label>
                            <select name="sort" class="form-select filter-select" aria-label="Urutkan mutasi">
                                <option value="desc" @selected(($filters['sort'] ?? 'desc') === 'desc')>Terbaru</option>
                                <option value="asc" @selected(($filters['sort'] ?? '') === 'asc')>Terlama</option>
                            </select>
                        </div>

                        <label class="sc-filter-check" title="Tampilkan mutasi yang sudah memiliki nilai biaya">
                            <input type="checkbox" name="has_cost" value="1" @checked($filters['has_cost'] ?? false)>
                            Ada nilai biaya
                        </label>
                    </div>
                </details>
            </div>
        </form>

        {{-- Table (AJAX replace) --}}
        <div id="sc_table_wrap">
            @include('inventory.stock_card._table')
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('stockCardFilter');
            const qInput = document.getElementById('q_item_input');
            const qClear = document.getElementById('q_item_clear');
            const rangeInput = document.getElementById('sc_date_range');
            const fromInput = document.getElementById('sc_from_date');
            const toInput = document.getElementById('sc_to_date');

            const kpiWrap = document.getElementById('sc_kpi');
            const tableWrap = document.getElementById('sc_table_wrap');

            if (!form || !kpiWrap || !tableWrap) return;

            // focus/select all
            if (qInput) {
                qInput.addEventListener('focus', () => qInput.select());
                qInput.addEventListener('click', () => qInput.select());
                qInput.addEventListener('input', () => {
                    const caret = qInput.selectionStart ?? qInput.value.length;
                    const value = qInput.value.toUpperCase();
                    if (qInput.value !== value) {
                        qInput.value = value;
                        qInput.setSelectionRange(caret, caret);
                    }
                });
            }

            const buildUrl = () => {
                const url = new URL(window.location.href);
                const fd = new FormData(form);
                url.search = '';
                for (const [k, v] of fd.entries()) {
                    if (v !== '') url.searchParams.set(k, v);
                }
                url.searchParams.set('ajax', '1');
                return url;
            };

            let timer = null;
            let abortCtrl = null;
            const debounce = (fn, wait = 550) => (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, args), wait);
            };

            const setLoading = (on) => {
                const card = document.getElementById('sc_table_card');
                if (card) card.classList.toggle('loading', on);
            };

            async function fetchAndSwap() {
                const url = buildUrl();
                const keepSearchFocus = document.activeElement === qInput;

                // update visible URL (without ajax)
                const visible = new URL(url);
                visible.searchParams.delete('ajax');
                window.history.replaceState({}, '', visible);

                if (abortCtrl) abortCtrl.abort();
                abortCtrl = new AbortController();

                setLoading(true);

                try {
                    const res = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json'
                        },
                        signal: abortCtrl.signal
                    });
                    if (!res.ok) throw new Error('Request failed');
                    const json = await res.json();

                    if (json.kpi !== undefined) kpiWrap.innerHTML = json.kpi;
                    if (json.table !== undefined) tableWrap.innerHTML = json.table;

                    if (keepSearchFocus && qInput) qInput.focus({
                        preventScroll: true
                    });

                } catch (e) {
                    if (e.name !== 'AbortError') console.error(e);
                } finally {
                    setLoading(false);
                }
            }

            const debouncedFetch = debounce(fetchAndSwap, 600);

            const formatDate = (date) => {
                if (!date || !window.flatpickr) return '';
                return window.flatpickr.formatDate(date, 'Y-m-d');
            };

            const formatRangeLabel = (dates) => {
                if (!window.flatpickr || !dates.length) return '';
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                const fmt = (date) => `${String(date.getDate()).padStart(2, '0')} ${months[date.getMonth()]} ${date.getFullYear()}`;
                return dates.length > 1 ? `${fmt(dates[0])} – ${fmt(dates[1])}` : `${fmt(dates[0])} – …`;
            };

            let datePicker = null;
            if (rangeInput && fromInput && toInput && window.GFID?.initDateRange) {
                datePicker = window.GFID.initDateRange(rangeInput, {
                    defaultDate: [fromInput.value, toInput.value].filter(Boolean),
                    showMonths: 1,
                    altInput: false,
                    allowInput: false,
                    onReady(selectedDates, _, instance) {
                        instance.calendarContainer.classList.add('sc-date-calendar');
                        instance.input.value = formatRangeLabel(selectedDates);
                    },
                    onChange(selectedDates, _, instance) {
                        instance.input.value = formatRangeLabel(selectedDates);
                        fromInput.value = selectedDates[0] ? formatDate(selectedDates[0]) : '';
                        toInput.value = selectedDates[1] ? formatDate(selectedDates[1]) : '';
                        if (selectedDates.length === 2) fetchAndSwap();
                    }
                });
            }

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                fetchAndSwap();
            });

            // instant controls
            form.querySelectorAll('select, input[type="checkbox"]').forEach(el => {
                el.addEventListener('change', () => fetchAndSwap());
            });

            // keyword
            if (qInput) {
                qInput.addEventListener('input', () => {
                    if (qClear) qClear.style.display = qInput.value.trim() ? '' : 'none';
                    debouncedFetch();
                });
                qInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        fetchAndSwap();
                    }
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        qInput.value = '';
                        if (qClear) qClear.style.display = 'none';
                        fetchAndSwap();
                    }
                });
            }

            if (qClear && qInput) {
                qClear.addEventListener('click', () => {
                    qInput.value = '';
                    qClear.style.display = 'none';
                    fetchAndSwap();
                    qInput.focus({
                        preventScroll: true
                    });
                    qInput.select();
                });
            }

            // pagination click -> ajax (delegate)
            document.addEventListener('click', (e) => {
                const a = e.target.closest('#sc_pagination a');
                if (!a) return;
                e.preventDefault();

                const url = new URL(a.href);
                let pageInput = form.querySelector('input[name="page"]');
                if (!pageInput) {
                    pageInput = document.createElement('input');
                    pageInput.type = 'hidden';
                    pageInput.name = 'page';
                    form.appendChild(pageInput);
                }
                pageInput.value = url.searchParams.get('page') || '';

                fetchAndSwap();
            });

        })();
    </script>
@endpush
