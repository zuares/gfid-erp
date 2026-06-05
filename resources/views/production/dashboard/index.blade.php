{{-- resources/views/production/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Dashboard')

@php
    $tabs = [
        'ringkasan' => 'Ringkasan',
        'siap-jahit' => 'Siap Jahit',
        'sedang-jahit' => 'Sedang Jahit',
        'setor-qc' => 'Setor & QC',
        'reject' => 'Reject',
        'prioritas' => 'Prioritas',
    ];

    // Deskripsi singkat per tab (tampil di bawah judul header).
    $tabDesc = [
        'ringkasan' => 'Ringkasan & alur produksi periode ini.',
        'siap-jahit' => 'Hasil potong yang siap dibagi ke penjahit.',
        'sedang-jahit' => 'Barang yang sedang dikerjakan penjahit.',
        'setor-qc' => 'Hasil jahit yang sudah disetor & lolos cek kualitas.',
        'reject' => 'Barang gagal cek kualitas — cutting & jahit.',
        'prioritas' => 'SKU yang perlu didahulukan produksinya.',
    ];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        /* Lazy-load helpers (khusus dashboard produksi) */
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

        /* Tombol toggle filter — hanya tampil di mobile (lihat media query) */
        .gf-filter-toggle { display: none; }

        /* Select filter pill (penjahit / kategori / sku) — selaras header marketplace */
        .gf-header-select {
            min-height: 38px;
            max-width: 168px;
            border-radius: 999px !important;
            font-size: .78rem;
            font-weight: 700;
            padding-left: .85rem;
            padding-right: 1.9rem;
            border-color: rgba(15, 23, 42, .10);
            box-shadow: none !important;
            text-overflow: ellipsis;
        }

        @media (max-width: 576px) {
            .gf-header-select { max-width: 100%; width: 100%; }
        }

        .prod-empty {
            text-align: center;
            color: var(--gf-muted);
            font-size: .85rem;
            padding: 1.6rem;
        }

        /* Toolbar filter realtime (tab Siap Jahit) */
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

        .sj-check {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .78rem;
            font-weight: 700;
            color: #475569;
            white-space: nowrap;
            cursor: pointer;
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
            .sj-toolbar .form-select {
                flex: 1 1 calc(50% - .25rem);
                min-width: 0;
            }
            .sj-count { margin-left: 0; }
            .gf-hide-mobile { display: none !important; }
            /* Mobile: sembunyikan kartu KPI & teks petunjuk (subtitle) panel tabel */
            [data-dashboard-root] .gf-overview-kpi-grid { display: none !important; }
            [data-dashboard-root] .gf-panel-header .gf-subtext { display: none !important; }

            /* Mobile: header lebih ringkas — sembunyikan eyebrow, sisakan judul + deskripsi */
            .gf-master-eyebrow { display: none !important; }
            .gf-master-desc {
                font-size: 11.5px;
                line-height: 1.35;
                margin-top: 2px !important;
            }

            /* Mobile: judul + deskripsi di kiri, tombol filter sejajar presisi di kanan */
            .gf-master-header { padding: 12px 14px; }
            .gf-master-header-layout {
                flex-wrap: nowrap !important;
                align-items: center !important;
                gap: 10px !important;
            }
            .gf-master-header-copy {
                min-width: 0 !important;
                flex: 1 1 auto;
            }
            .gf-master-title { margin-bottom: 0 !important; }
            .gf-master-actions { flex: 0 0 auto !important; }

            /* Mobile: filter dikompres jadi 1 tombol icon (dropdown saat ditekan) */
            .gf-filter-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                flex: 0 0 auto;
                border-radius: 999px;
                border: 1px solid rgba(15, 23, 42, .12);
                background: #fff;
                color: #0f172a;
                cursor: pointer;
            }
            .gf-dashboard-header-actions {
                position: relative;
                justify-content: flex-end !important;
            }
            /* Saat terbuka, angkat wrapper di atas tab sticky (z-index tab = 1000) */
            .gf-dashboard-header-actions.is-open { z-index: 2000; }
            .gf-dashboard-header-filter {
                display: none !important;
                position: absolute;
                top: calc(100% + .45rem);
                right: 0;
                z-index: 2001;
                grid-template-columns: 1fr !important;
                width: min(88vw, 320px);
                padding: .8rem;
                gap: .55rem;
                background: #fff;
                border: 1px solid rgba(15, 23, 42, .1);
                border-radius: 16px;
                box-shadow: 0 16px 36px rgba(15, 23, 42, .16);
            }
            .gf-dashboard-header-actions.is-open .gf-dashboard-header-filter { display: grid !important; }
            .gf-dashboard-header-filter .gf-header-period-select,
            .gf-dashboard-header-filter .gf-header-date-input,
            .gf-dashboard-header-filter .gf-header-select,
            .gf-dashboard-header-filter .gf-header-icon-btn {
                width: 100% !important;
                max-width: none !important;
            }
            /* kolom tersisa harus pas di layar — hilangkan min-width & padatkan */
            .gf-table-scroll-sticky .gf-clean-table { min-width: 0 !important; font-size: .76rem; }
            .gf-table-scroll-sticky .gf-clean-table th,
            .gf-table-scroll-sticky .gf-clean-table td { padding-left: .4rem; padding-right: .4rem; }
            .gf-table-scroll.gf-table-scroll-sticky { overflow-x: hidden; }
        }

        /* Scroll vertikal + thead sticky (override .gf-table-scroll yg flat) */
        .gf-table-scroll.gf-table-scroll-sticky {
            max-height: calc(100vh - 360px);
            min-height: 220px;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
        }
        .gf-table-scroll-sticky .gf-sticky-table thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #f8fafc;
            box-shadow: inset 0 -1px 0 #e6eaf0;
        }

        /* Badges & chips dipakai partial produksi */
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

        .gf-bar-track {
            height: 8px;
            border-radius: 999px;
            background: rgba(148, 163, 184, .2);
            overflow: hidden;
            min-width: 90px;
        }
        .gf-bar-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #16a34a, #22c55e);
        }

        .gf-funnel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: .7rem;
        }
        .gf-funnel-step {
            border: 1px solid var(--gf-border);
            border-left: 3px solid #94a3b8;
            border-radius: 12px;
            padding: .7rem .8rem;
            background: #fff;
        }
        .gf-funnel-step.accent-blue { border-left-color: #2563eb; }
        .gf-funnel-step.accent-green { border-left-color: #16a34a; }
        .gf-funnel-step.accent-amber { border-left-color: #d97706; }
        .gf-funnel-label {
            font-size: .7rem; color: var(--gf-muted); font-weight: 800;
            text-transform: uppercase; letter-spacing: .03em;
        }
        .gf-funnel-val { font-size: 1.3rem; font-weight: 900; margin: .1rem 0 .4rem; }

        /* ---- Timeline aktivitas (tab Ringkasan) ---- */
        .gf-tl { position: relative; }
        /* tulang punggung (spine) menerus di belakang dot */
        .gf-tl::before {
            content: ''; position: absolute; left: .47rem; top: .6rem; bottom: .6rem;
            width: 2px; background: var(--gf-border);
        }
        .gf-tl-day {
            position: relative; z-index: 1;
            font-size: .72rem; font-weight: 900; color: var(--gf-muted);
            text-transform: uppercase; letter-spacing: .05em;
            margin: 1.35rem 0 .65rem; padding-left: 1.9rem;
        }
        .gf-tl-day::before {
            content: ''; position: absolute; left: .27rem; top: 50%; transform: translateY(-50%);
            width: .42rem; height: .42rem; border-radius: 999px;
            background: #cbd5e1; box-shadow: 0 0 0 4px var(--gf-soft);
        }
        .gf-tl-day:first-child { margin-top: .25rem; }
        .gf-tl-item {
            position: relative; display: flex; gap: .85rem;
            padding: .5rem .65rem .5rem 1.9rem;
            border-radius: 12px;
            transition: background .14s ease;
        }
        .gf-tl-item:hover { background: rgba(148, 163, 184, .10); }
        .gf-tl-dot {
            position: absolute; left: .18rem; top: .85rem; z-index: 2;
            width: .7rem; height: .7rem; border-radius: 999px;
            background: #94a3b8;
            box-shadow: 0 0 0 3px #fff, 0 0 0 4px rgba(148, 163, 184, .35);
        }
        .gf-tl-dot.gf-tl-blue { background: #2563eb; box-shadow: 0 0 0 3px #fff, 0 0 0 4px rgba(37, 99, 235, .25); }
        .gf-tl-dot.gf-tl-amber { background: #d97706; box-shadow: 0 0 0 3px #fff, 0 0 0 4px rgba(217, 119, 6, .25); }
        .gf-tl-dot.gf-tl-green { background: #16a34a; box-shadow: 0 0 0 3px #fff, 0 0 0 4px rgba(22, 163, 74, .25); }
        .gf-tl-body { flex: 1 1 auto; min-width: 0; }
        .gf-tl-main {
            display: flex; justify-content: space-between; align-items: flex-start;
            gap: .6rem; flex-wrap: wrap;
        }
        .gf-tl-who { display: flex; flex-wrap: wrap; align-items: center; gap: .45rem; min-width: 0; }
        .gf-tl-name { color: var(--gf-muted); font-size: .82rem; }
        .gf-tl-qty {
            display: flex; align-items: baseline; gap: .45rem;
            font-size: .82rem; white-space: nowrap; margin-left: auto;
        }
        .gf-tl-qty b { font-size: 1rem; font-weight: 900; }
        .gf-tl-sub {
            display: flex; flex-wrap: wrap; gap: .3rem;
            font-size: .74rem; margin-top: .25rem; color: var(--gf-muted);
        }
        .gf-tl-code { font-variant-numeric: tabular-nums; }
        @media (max-width: 576px) {
            .gf-tl-item { padding-left: 1.7rem; }
            .gf-tl-qty { margin-left: 0; }
        }

        .gf-num { text-align: right; font-variant-numeric: tabular-nums; }
        tr.gf-row-warn > td { background: rgba(245, 158, 11, .06); }

        /* Sel tanggal 2-baris: tanggal + hari (ID) & jam, minimalis */
        .gf-datecell { display: flex; flex-direction: column; line-height: 1.18; }
        .gf-datecell-d { font-weight: 600; color: var(--gf-dark); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .gf-datecell-sub { font-size: .68rem; color: var(--gf-muted); font-variant-numeric: tabular-nums; white-space: nowrap; }

        /* Tab Prioritas — minimalis, sedikit warna */
        .gf-pri-item { display: flex; align-items: center; gap: .7rem; min-width: 230px; }
        .gf-pri-product { font-weight: 700; color: var(--gf-dark); line-height: 1.2; }
        .gf-pri-meta { margin-top: .12rem; color: var(--gf-muted); font-size: .76rem; line-height: 1.2; }
        .gf-pri-stack { display: flex; flex-direction: column; align-items: flex-end; gap: .08rem; line-height: 1.15; }
        .gf-pri-stack b { color: var(--gf-dark); font-size: .96rem; font-variant-numeric: tabular-nums; }
        .gf-pri-stack span,
        .gf-pri-stack small { color: var(--gf-muted); font-size: .7rem; white-space: nowrap; }
        .gf-pri-decision { display: flex; flex-direction: column; align-items: flex-start; gap: .32rem; min-width: 120px; }
        .gf-pri-score { display: flex; align-items: center; gap: .45rem; justify-content: flex-end; }
        .gf-pri-score b { font-variant-numeric: tabular-nums; }
        .gf-pri-bar { width: 44px; height: 4px; border-radius: 99px; background: #eef2f7; overflow: hidden; flex: none; }
        .gf-pri-bar > i { display: block; height: 100%; background: #0f172a; }
        .gf-pri-status { display: inline-flex; align-items: center; gap: .4rem; font-weight: 600; color: #475569; white-space: nowrap; }
        .gf-pri-dot { width: 8px; height: 8px; border-radius: 50%; background: #cbd5e1; flex: none; }
        .gf-pri-kritis .gf-pri-dot { background: #dc2626; }
        .gf-pri-tinggi .gf-pri-dot { background: #f59e0b; }
        .gf-pri-kritis { color: #b91c1c; }
        .gf-pri-reason { max-width: 280px; color: var(--gf-muted); font-size: .8rem; line-height: 1.35; }
        @media (max-width: 576px) {
            [data-pr-toolbar] {
                position: sticky;
                top: 0;
                z-index: 8;
                padding: .55rem 0 .65rem;
                margin-bottom: .45rem;
                background: #fff;
            }
            [data-pr-toolbar] .sj-count {
                flex: 1 1 100%;
                width: 100%;
                padding: .2rem .1rem 0;
                font-size: .72rem;
                white-space: normal;
            }
            [data-pr-table],
            [data-pr-table] tbody,
            [data-pr-table] tr,
            [data-pr-table] td {
                display: block;
                width: 100%;
            }
            [data-pr-table] thead { display: none; }
            [data-pr-table] tbody {
                display: grid;
                gap: .65rem;
            }
            [data-pr-table] tr[data-pr-row] {
                display: grid;
                grid-template-columns: 1fr;
                gap: .18rem;
                padding: .72rem;
                border: 1px solid rgba(15, 23, 42, .08);
                border-radius: 10px;
                background: #fff;
                box-shadow: 0 6px 18px rgba(15, 23, 42, .04);
            }
            [data-pr-table] tr[data-pr-row] > td {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: .85rem;
                border: 0 !important;
                padding: .28rem 0 !important;
                text-align: left;
            }
            [data-pr-table] tr[data-pr-row] > td::before {
                content: attr(data-label);
                flex: 0 0 auto;
                color: var(--gf-muted);
                font-size: .68rem;
                font-weight: 800;
                line-height: 1.2;
                text-transform: uppercase;
                letter-spacing: .03em;
            }
            [data-pr-table] tr[data-pr-row] > td:first-child {
                border-bottom: 1px solid rgba(15, 23, 42, .06) !important;
                margin-bottom: .2rem;
                padding-bottom: .5rem !important;
            }
            [data-pr-table] tr[data-pr-row] > td:first-child::before { content: none; }
            .gf-pri-item { min-width: 0; width: 100%; gap: .5rem; }
            .gf-pri-product,
            .gf-pri-meta { display: none; }
            .gf-pri-stack { align-items: flex-end; }
            .gf-pri-decision { min-width: 86px; }
            .gf-pri-bar { width: 34px; }
            .gf-table-scroll.gf-table-scroll-sticky {
                max-height: none;
                min-height: 0;
                overflow: visible;
            }
        }

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
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Produksi"
        title="Dashboard Produksi"
        :description="$tabDesc[$initialTab] ?? ''">

        <x-slot:actions>
            <div class="gf-dashboard-header-actions" data-filter-wrap>
                <button type="button" class="gf-filter-toggle" data-filter-toggle
                    aria-label="Filter" aria-expanded="false" title="Filter">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z" />
                    </svg>
                </button>
                <form id="filterForm" method="GET" action="{{ route('production.dashboard') }}"
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

                    <select name="operator_id" class="form-select gf-header-select" data-filter aria-label="Penjahit">
                        <option value="">Semua Penjahit</option>
                        @foreach ($operatorOptions as $op)
                            <option value="{{ $op->id }}" @selected($filters['operator_id'] == $op->id)>
                                {{ $op->code }} — {{ $op->name }}</option>
                        @endforeach
                    </select>

                    <select name="category_id" class="form-select gf-header-select" data-filter aria-label="Kategori">
                        <option value="">Semua Kategori</option>
                        @foreach ($categoryOptions as $cat)
                            <option value="{{ $cat->id }}" @selected($filters['category_id'] == $cat->id)>
                                {{ $cat->name }}</option>
                        @endforeach
                    </select>

                    <select name="item_id" class="form-select gf-header-select" data-filter aria-label="Varian (SKU)">
                        <option value="">Semua SKU</option>
                        @foreach ($itemOptions as $it)
                            <option value="{{ $it->id }}" @selected($filters['item_id'] == $it->id)>
                                {{ $it->code }} — {{ $it->name }}</option>
                        @endforeach
                    </select>

                    <a href="{{ route('production.dashboard') }}" class="btn btn-light border gf-header-icon-btn"
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
            const DATA_URL = @json(route('production.dashboard.data'));
            const SLIP_URL = @json(route('production.dashboard.slip'));
            const SERVER_INITIAL = @json($initialTab);
            const KEY = 'prodDashTab';
            const TAB_DESC = @json($tabDesc);
            const descEl = document.querySelector('.gf-master-desc');
            const setDesc = (name) => { if (descEl && TAB_DESC[name]) descEl.textContent = TAB_DESC[name]; };

            const tabBtns = Array.from(document.querySelectorAll('#prodTabs .gf-marketplace-tab'));
            const panes = Array.from(document.querySelectorAll('[data-tab-panel]'));
            const form = document.getElementById('filterForm');
            const periodLabel = document.getElementById('periodLabel');

            // ---- Toggle filter (mobile): tampilkan/sembunyikan dropdown filter ----
            const filterWrap = document.querySelector('[data-filter-wrap]');
            const filterToggle = document.querySelector('[data-filter-toggle]');
            if (filterToggle && filterWrap) {
                filterToggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const open = filterWrap.classList.toggle('is-open');
                    filterToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                });
                // Klik di luar area filter → tutup
                document.addEventListener('click', (e) => {
                    if (filterWrap.classList.contains('is-open') && !filterWrap.contains(e.target)) {
                        filterWrap.classList.remove('is-open');
                        filterToggle.setAttribute('aria-expanded', 'false');
                    }
                });
            }

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
                setDesc(name);
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
                    // Terapkan filter default per-tab (mis. Penjahit default = Ambil Jahit).
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

            // Submit (mis. Enter di input tanggal) → realtime apply tanpa reload
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

            form.querySelectorAll('select[data-filter]').forEach(sel =>
                sel.addEventListener('change', applyFilters));

            const resetLink = form.querySelector('[data-filter-reset]');
            if (resetLink) resetLink.addEventListener('click', (e) => {
                e.preventDefault();
                fromEl.value = resetLink.dataset.from;
                toEl.value = resetLink.dataset.to;
                if (fp) fp.setDate([resetLink.dataset.from, resetLink.dataset.to], false);
                form.querySelectorAll('select[data-filter]').forEach(s => s.value = '');
                if (periodSel) periodSel.value = 'custom';
                detectPeriod();
                applyFilters();
            });

            // ---- Filter realtime tab "Siap Jahit" (client-side, instan) ----
            const idFmt = (n) => (n || 0).toLocaleString('id-ID');

            function applySjFilters(root) {
                if (!root) return;
                const table = root.querySelector('[data-sj-table]');
                if (!table) return;
                const tbody = table.querySelector('tbody');
                const q = (root.querySelector('[data-sj-search]')?.value || '').trim().toLowerCase();
                const grade = root.querySelector('[data-sj-grade]')?.value || '';
                const sort = root.querySelector('[data-sj-sort]')?.value || 'remaining-desc';

                const rows = Array.from(tbody.querySelectorAll('[data-sj-row]'));
                let shown = 0, sumRemaining = 0;
                rows.forEach(r => {
                    let ok = true;
                    if (q && !(r.dataset.search || '').includes(q)) ok = false;
                    if (grade && r.dataset.grade !== grade) ok = false;
                    r.hidden = !ok;
                    if (ok) { shown++; sumRemaining += parseFloat(r.dataset.remaining) || 0; }
                });

                const cmp = {
                    'remaining-desc': (a, b) => (+b.dataset.remaining) - (+a.dataset.remaining),
                    'hpp-desc': (a, b) => (+b.dataset.hpp) - (+a.dataset.hpp),
                    'stok-asc': (a, b) => (+a.dataset.stok) - (+b.dataset.stok),
                    'bundles-desc': (a, b) => (+b.dataset.bundles) - (+a.dataset.bundles),
                    'sku-asc': (a, b) => (a.dataset.sku || '').localeCompare(b.dataset.sku || ''),
                }[sort];
                if (cmp) rows.sort(cmp).forEach(r => tbody.appendChild(r));

                const cnt = root.querySelector('[data-sj-count]');
                if (cnt) cnt.textContent = idFmt(shown) + ' SKU · ' + idFmt(sumRemaining) + ' pcs';
                const empty = root.querySelector('[data-sj-empty]');
                if (empty) empty.hidden = (shown !== 0) || rows.length === 0;
            }

            const SJ_SEL = '[data-sj-search],[data-sj-grade],[data-sj-sort]';
            document.addEventListener('input', (e) => {
                if (!e.target.matches('[data-sj-search]')) return;
                applySjFilters(e.target.closest('[data-tab-panel]'));
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches(SJ_SEL)) return;
                applySjFilters(e.target.closest('[data-tab-panel]'));
            });

            // ---- Klik baris "Siap Jahit" → buka form bagi bundle dengan filter SKU ----
            document.addEventListener('click', (e) => {
                const row = e.target.closest('[data-sj-row][data-sj-href]');
                if (!row) return;
                window.location.href = row.dataset.sjHref;
            });

            // ---- Klik baris "Sedang Jahit" → buka form setor jahit dgn filter penjahit ----
            document.addEventListener('click', (e) => {
                const row = e.target.closest('[data-sd-row][data-sd-href]');
                if (!row) return;
                window.location.href = row.dataset.sdHref;
            });

            // ---- Filter realtime tab "Sedang Jahit" (client-side, instan) ----
            function applySdFilters(root) {
                if (!root) return;
                const table = root.querySelector('[data-sd-table]');
                if (!table) return;
                const tbody = table.querySelector('tbody');
                const q = (root.querySelector('[data-sd-search]')?.value || '').trim().toLowerCase();
                const op = root.querySelector('[data-sd-operator]')?.value || '';
                const sort = root.querySelector('[data-sd-sort]')?.value || 'date-desc';

                const rows = Array.from(tbody.querySelectorAll('[data-sd-row]'));
                let shown = 0, sumOut = 0;
                rows.forEach(r => {
                    let ok = true;
                    if (q && !(r.dataset.search || '').includes(q)) ok = false;
                    if (op && r.dataset.operator !== op) ok = false;
                    r.hidden = !ok;
                    if (ok) { shown++; sumOut += parseFloat(r.dataset.outstanding) || 0; }
                });

                const cmp = {
                    'date-desc': (a, b) => (a.dataset.date < b.dataset.date ? 1 : (a.dataset.date > b.dataset.date ? -1 : 0)),
                    'out-desc': (a, b) => (+b.dataset.outstanding) - (+a.dataset.outstanding),
                    'hpp-desc': (a, b) => (+b.dataset.hpp) - (+a.dataset.hpp),
                    'age-desc': (a, b) => (+b.dataset.age) - (+a.dataset.age),
                    'picked-desc': (a, b) => (+b.dataset.picked) - (+a.dataset.picked),
                }[sort];
                if (cmp) rows.sort(cmp).forEach(r => tbody.appendChild(r));

                const cnt = root.querySelector('[data-sd-count]');
                if (cnt) cnt.textContent = idFmt(shown) + ' baris · ' + idFmt(sumOut) + ' pcs';
                const empty = root.querySelector('[data-sd-empty]');
                if (empty) empty.hidden = (shown !== 0) || rows.length === 0;
            }

            const SD_SEL = '[data-sd-search],[data-sd-operator],[data-sd-sort]';
            document.addEventListener('input', (e) => {
                if (!e.target.matches('[data-sd-search]')) return;
                applySdFilters(e.target.closest('[data-tab-panel]'));
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches(SD_SEL)) return;
                applySdFilters(e.target.closest('[data-tab-panel]'));
            });

            // ---- Filter realtime tab "Setor & QC" (client-side, instan) ----
            function applyQcFilters(root) {
                if (!root) return;
                const table = root.querySelector('[data-qc-table]');
                if (!table) return;
                const tbody = table.querySelector('tbody');
                const q = (root.querySelector('[data-qc-search]')?.value || '').trim().toLowerCase();
                const op = root.querySelector('[data-qc-operator]')?.value || '';
                const sort = root.querySelector('[data-qc-sort]')?.value || 'date-desc';

                const rows = Array.from(tbody.querySelectorAll('[data-qc-row]'));
                let shown = 0, sumOk = 0;
                rows.forEach(r => {
                    let ok = true;
                    if (q && !(r.dataset.search || '').includes(q)) ok = false;
                    if (op && r.dataset.operator !== op) ok = false;
                    r.hidden = !ok;
                    if (ok) { shown++; sumOk += parseFloat(r.dataset.ok) || 0; }
                });

                const cmp = {
                    'date-desc': (a, b) => (a.dataset.date < b.dataset.date ? 1 : -1),
                    'ok-desc': (a, b) => (+b.dataset.ok) - (+a.dataset.ok),
                    'hpp-desc': (a, b) => (+b.dataset.hpp) - (+a.dataset.hpp),
                    'yield-asc': (a, b) => (+a.dataset.yield) - (+b.dataset.yield),
                }[sort];
                if (cmp) rows.sort(cmp).forEach(r => tbody.appendChild(r));

                const cnt = root.querySelector('[data-qc-count]');
                if (cnt) cnt.textContent = idFmt(shown) + ' setoran · ' + idFmt(sumOk) + ' pcs OK';
                const empty = root.querySelector('[data-qc-empty]');
                if (empty) empty.hidden = (shown !== 0) || rows.length === 0;
            }

            const QC_SEL = '[data-qc-search],[data-qc-operator],[data-qc-sort]';
            document.addEventListener('input', (e) => {
                if (!e.target.matches('[data-qc-search]')) return;
                applyQcFilters(e.target.closest('[data-tab-panel]'));
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches(QC_SEL)) return;
                applyQcFilters(e.target.closest('[data-tab-panel]'));
            });

            // ---- Filter realtime tab "Reject" (client-side, instan) ----
            function applyRjFilters(root) {
                if (!root) return;
                const table = root.querySelector('[data-rj-table]');
                if (!table) return;
                const tbody = table.querySelector('tbody');
                const q = (root.querySelector('[data-rj-search]')?.value || '').trim().toLowerCase();
                const stage = root.querySelector('[data-rj-stage]')?.value || '';
                const sort = root.querySelector('[data-rj-sort]')?.value || 'date-desc';

                const rows = Array.from(tbody.querySelectorAll('[data-rj-row]'));
                let shown = 0, sumQty = 0;
                rows.forEach(r => {
                    let ok = true;
                    if (q && !(r.dataset.search || '').includes(q)) ok = false;
                    if (stage && r.dataset.stage !== stage) ok = false;
                    r.hidden = !ok;
                    if (ok) { shown++; sumQty += parseFloat(r.dataset.qty) || 0; }
                });

                const cmp = {
                    'date-desc': (a, b) => (a.dataset.date < b.dataset.date ? 1 : -1),
                    'qty-desc': (a, b) => (+b.dataset.qty) - (+a.dataset.qty),
                    'hpp-desc': (a, b) => (+b.dataset.hpp) - (+a.dataset.hpp),
                }[sort];
                if (cmp) rows.sort(cmp).forEach(r => tbody.appendChild(r));

                const cnt = root.querySelector('[data-rj-count]');
                if (cnt) cnt.textContent = idFmt(shown) + ' kejadian · ' + idFmt(sumQty) + ' pcs';
                const empty = root.querySelector('[data-rj-empty]');
                if (empty) empty.hidden = (shown !== 0) || rows.length === 0;
            }

            const RJ_SEL = '[data-rj-search],[data-rj-stage],[data-rj-sort]';
            document.addEventListener('input', (e) => {
                if (!e.target.matches('[data-rj-search]')) return;
                applyRjFilters(e.target.closest('[data-tab-panel]'));
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches(RJ_SEL)) return;
                applyRjFilters(e.target.closest('[data-tab-panel]'));
            });

            // ---- Filter realtime tab "Ringkasan" → timeline aktivitas ----
            function applyOvFilters(root) {
                if (!root) return;
                const list = root.querySelector('[data-ov-list]');
                if (!list) return;
                const q = (root.querySelector('[data-ov-search]')?.value || '').trim().toLowerCase();
                const type = root.querySelector('[data-ov-type]')?.value || '';

                const rows = Array.from(list.querySelectorAll('[data-ov-row]'));
                let shown = 0, cCut = 0, cPick = 0, cRet = 0;
                rows.forEach(r => {
                    let ok = true;
                    if (q && !(r.dataset.search || '').includes(q)) ok = false;
                    if (type && r.dataset.type !== type) ok = false;
                    r.hidden = !ok;
                    if (ok) {
                        shown++;
                        if (r.dataset.type === 'cutting') cCut++;
                        else if (r.dataset.type === 'pickup') cPick++;
                        else if (r.dataset.type === 'return') cRet++;
                    }
                });

                // Sembunyikan header tanggal yang tidak punya item tampil di bawahnya
                const days = Array.from(list.querySelectorAll('[data-ov-day]'));
                days.forEach(day => {
                    let any = false;
                    let n = day.nextElementSibling;
                    while (n && !n.matches('[data-ov-day]')) {
                        if (n.matches('[data-ov-row]') && !n.hidden) { any = true; break; }
                        n = n.nextElementSibling;
                    }
                    day.hidden = !any;
                });

                const cnt = root.querySelector('[data-ov-count]');
                if (cnt) cnt.textContent = idFmt(shown) + ' kejadian · ' + idFmt(cCut) + ' cutting · '
                    + idFmt(cPick) + ' ambil · ' + idFmt(cRet) + ' setor';
                const empty = root.querySelector('[data-ov-empty]');
                if (empty) empty.hidden = (shown !== 0) || rows.length === 0;
            }

            const OV_SEL = '[data-ov-search],[data-ov-type]';
            document.addEventListener('input', (e) => {
                if (!e.target.matches('[data-ov-search]')) return;
                applyOvFilters(e.target.closest('[data-tab-panel]'));
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches(OV_SEL)) return;
                applyOvFilters(e.target.closest('[data-tab-panel]'));
            });

            // ---- Prioritas ----
            function applyPrFilters(root) {
                if (!root) return;
                const table = root.querySelector('[data-pr-table]');
                if (!table) return;
                const tbody = table.querySelector('tbody');
                const q = (root.querySelector('[data-pr-search]')?.value || '').trim().toLowerCase();
                const grade = root.querySelector('[data-pr-grade]')?.value || '';
                const sort = root.querySelector('[data-pr-sort]')?.value || 'score-desc';

                const rows = Array.from(tbody.querySelectorAll('[data-pr-row]'));
                let shown = 0, urgent = 0;
                rows.forEach(r => {
                    let ok = true;
                    if (q && !(r.dataset.search || '').includes(q)) ok = false;
                    if (grade && r.dataset.grade !== grade) ok = false;
                    r.hidden = !ok;
                    if (ok) {
                        shown++;
                        if (r.dataset.grade === 'Kritis' || r.dataset.grade === 'Tinggi') urgent++;
                    }
                });

                const cmp = {
                    'score-desc': (a, b) => (+b.dataset.score) - (+a.dataset.score),
                    'cover-asc': (a, b) => (+a.dataset.cover) - (+b.dataset.cover),
                    'ads-desc': (a, b) => (+b.dataset.ads) - (+a.dataset.ads),
                }[sort];
                if (cmp) rows.sort(cmp).forEach(r => tbody.appendChild(r));

                const cnt = root.querySelector('[data-pr-count]');
                if (cnt) cnt.textContent = idFmt(shown) + ' SKU produksi sendiri · ' + idFmt(urgent) + ' perlu didahulukan';

                const setKpi = (sel, val) => { const el = root.querySelector(sel); if (el) el.textContent = val; };
                setKpi('[data-pr-kpi-urgent]', idFmt(urgent));
                setKpi('[data-pr-kpi-own]', idFmt(shown));

                const empty = root.querySelector('[data-pr-empty]');
                if (empty) empty.hidden = (shown !== 0) || rows.length === 0;
            }

            const PR_SEL = '[data-pr-search],[data-pr-grade],[data-pr-sort]';
            document.addEventListener('input', (e) => {
                if (!e.target.matches('[data-pr-search]')) return;
                applyPrFilters(e.target.closest('[data-tab-panel]'));
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches(PR_SEL)) return;
                applyPrFilters(e.target.closest('[data-tab-panel]'));
            });

            // Terapkan filter default per-tab setelah HTML tab dimuat.
            function initTabFilters(name, pane) {
                if (name === 'prioritas') applyPrFilters(pane);
            }

            // Tab awal sudah dirender server-side (tidak lewat loadTab),
            // jadi terapkan filter default-nya (mis. Penjahit = Ambil Jahit) di sini.
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
