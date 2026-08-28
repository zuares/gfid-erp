@extends('layouts.app')

@section('title', 'Payroll Borongan')

@push('head')
    <style>
        .pw-overview-wrap {
            max-width: 1180px;
            margin: 0 auto;
            padding: .75rem .75rem 2.5rem
        }

        .pw-overview-top {
            position: sticky;
            top: 0;
            z-index: 300;
            display: flex;
            gap: .75rem;
            align-items: flex-start;
            justify-content: space-between;
            padding: .45rem .75rem;
            margin: 0 -.75rem .65rem;
            background: var(--card);
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            box-shadow: none
        }

        .pw-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 750;
            letter-spacing: 0
        }

        .pw-sub {
            margin: 0;
            color: var(--muted);
            font-size: .78rem
        }

        .pw-card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .25);
            border-radius: 14px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06)
        }

        .pw-card-h {
            padding: .8rem .9rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            display: flex;
            gap: .75rem;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap
        }

        .pw-card-b {
            padding: .9rem
        }

        .pw-actions,
        .pw-row {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center
        }

        .pw-btn,
        .pw-in,
        .pw-select {
            border: 1px solid rgba(148, 163, 184, .35);
            background: transparent;
            color: var(--text);
            border-radius: 11px;
            padding: .48rem .7rem;
            font-size: .88rem
        }

        .pw-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            text-decoration: none
        }

        .pw-btn.primary {
            border-color: color-mix(in srgb, var(--accent) 40%, rgba(148, 163, 184, .35));
            background: color-mix(in srgb, var(--accent-soft) 18%, var(--card) 82%)
        }

        .pw-in,
        .pw-select {
            min-height: 36px
        }

        .pw-date-range {
            min-width: 230px;
            cursor: pointer
        }

        .pw-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .65rem;
            margin: .75rem 0
        }

        .pw-kpi-card {
            min-width: 0;
            padding: .7rem .75rem;
            border: 1px solid rgba(148, 163, 184, .2);
            border-radius: 11px;
            background: color-mix(in srgb, var(--card) 94%, var(--line) 6%)
        }

        .pw-kpi-label {
            color: var(--muted);
            font-size: .68rem;
            font-weight: 750
        }

        .pw-kpi-value {
            margin-top: .22rem;
            color: var(--text);
            font-size: 1.05rem;
            font-weight: 900;
            font-variant-numeric: tabular-nums;
            white-space: nowrap
        }

        .pw-kpi-card.amount .pw-kpi-value {
            color: var(--accent)
        }

        .pw-periods-card.is-loading {
            opacity: .58;
            pointer-events: none
        }

        .pw-select option {
            color: #111827
        }

        .pw-tabs {
            display: inline-flex;
            gap: .25rem;
            padding: .25rem;
            border: 1px solid rgba(148, 163, 184, .25);
            border-radius: 12px;
            background: color-mix(in srgb, var(--card) 85%, var(--line) 15%)
        }

        .pw-tab {
            border-radius: 9px;
            padding: .38rem .65rem;
            color: var(--muted);
            text-decoration: none;
            font-size: .84rem
        }

        .pw-tab.active {
            color: var(--text);
            background: var(--card);
            box-shadow: 0 1px 4px rgba(15, 23, 42, .12)
        }

        .pw-table-wrap {
            max-height: 560px;
            overflow: auto
        }

        .pw-mobile-periods {
            display: none
        }

        .pw-period-card {
            padding: .8rem;
            border: 1px solid rgba(148, 163, 184, .2);
            border-radius: 12px;
            background: color-mix(in srgb, var(--card) 94%, var(--line) 6%)
        }

        .pw-period-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-bottom: .65rem
        }

        .pw-period-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .5rem;
            margin-top: .8rem
        }

        .pw-period-stats > div {
            padding: .55rem .6rem;
            border: 1px solid rgba(148, 163, 184, .16);
            border-radius: 9px;
            background: color-mix(in srgb, var(--card) 88%, var(--line) 12%)
        }

        .pw-period-stats span {
            display: block;
            margin-bottom: .15rem;
            color: var(--muted);
            font-size: .68rem
        }

        .pw-period-stats strong {
            display: block;
            font-size: .82rem;
            font-variant-numeric: tabular-nums;
            white-space: nowrap
        }

        .pw-period-stat-wide {
            grid-column: 1 / -1
        }

        .pw-period-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-top: .75rem;
            padding-top: .65rem;
            border-top: 1px solid rgba(148, 163, 184, .16)
        }

        .pw-period-paid {
            color: var(--muted);
            font-size: .7rem
        }

        .pw-mobile-empty {
            padding: .9rem;
            color: var(--muted);
            font-size: .8rem
        }

        .pw-table {
            width: 100%;
            min-width: 900px;
            border-collapse: separate;
            border-spacing: 0
        }

        .pw-table th {
            position: sticky;
            top: 0;
            z-index: 2;
            padding: .62rem .65rem;
            font-size: .66rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            text-align: left;
            background: var(--card);
            box-shadow: 0 1px 0 rgba(148, 163, 184, .22);
            border-bottom: 1px solid rgba(148, 163, 184, .18)
        }

        .pw-table thead tr {
            position: sticky;
            top: 0;
            z-index: 3
        }

        .pw-table td {
            padding: .62rem .65rem;
            font-size: .8rem;
            line-height: 1.35;
            border-bottom: 1px solid rgba(148, 163, 184, .12);
            vertical-align: middle
        }

        .pw-table tbody tr:hover {
            background: color-mix(in srgb, var(--accent-soft) 8%, transparent)
        }

        .pw-period-row,
        .pw-period-card {
            cursor: pointer
        }

        .pw-period-row:focus-visible,
        .pw-period-card:focus-visible {
            outline: 2px solid color-mix(in srgb, var(--accent) 60%, transparent);
            outline-offset: -2px
        }

        .pw-period-cell {
            min-width: 190px
        }

        .pw-period-week {
            margin-bottom: .2rem;
            color: var(--accent);
            font-size: .72rem;
            font-weight: 800
        }

        .pw-period-week .pw-sub {
            margin: 0;
            color: var(--muted);
            font-size: inherit
        }

        .pw-period-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem
        }

        .pw-period-main {
            color: var(--muted);
            font-size: .76rem;
            font-weight: 650;
            white-space: nowrap
        }

        .pw-period-meta {
            margin-top: .12rem;
            color: var(--muted);
            font-size: .69rem
        }

        .pw-basis {
            color: var(--muted);
            font-size: .75rem;
            white-space: nowrap
        }

        .pw-number {
            font-variant-numeric: tabular-nums;
            white-space: nowrap
        }

        .pw-amount {
            font-size: .82rem;
            font-weight: 850
        }

        .pw-status {
            min-width: 84px
        }

        .pw-status .pw-sub {
            font-size: .68rem;
            white-space: nowrap
        }

        .pw-action .pw-btn {
            padding: .36rem .58rem;
            border-radius: 9px;
            font-size: .76rem;
            white-space: nowrap
        }

        .pw-action-wrap,
        .pw-period-card-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .35rem;
            flex-wrap: wrap
        }

        .pw-action-wrap form,
        .pw-period-card-actions form {
            margin: 0
        }

        .pw-btn.danger {
            border-color: rgba(239, 68, 68, .3);
            color: rgb(220, 38, 38)
        }

        .pw-right {
            text-align: right
        }

        .pw-chip {
            display: inline-flex;
            align-items: center;
            border: 1px solid rgba(148, 163, 184, .25);
            padding: .18rem .48rem;
            border-radius: 999px;
            font-size: .76rem;
            color: var(--muted)
        }

        .pw-chip.cutting {
            border-color: rgba(59, 130, 246, .35);
            color: rgb(37, 99, 235)
        }

        .pw-chip.sewing {
            border-color: rgba(168, 85, 247, .35);
            color: rgb(126, 34, 206)
        }

        .pw-chip.final {
            border-color: rgba(16, 185, 129, .35);
            color: rgb(5, 150, 105)
        }

        .pw-chip.draft {
            border-color: rgba(148, 163, 184, .3);
            color: var(--muted);
            background: rgba(148, 163, 184, .08);
            font-size: .68rem;
            font-weight: 700
        }

        .pw-generate {
            margin-top: .85rem;
            margin-bottom: 1.25rem
        }

        .pw-generate-summary {
            min-height: 54px;
            padding: .62rem .85rem
        }

        .pw-generate-heading {
            display: flex;
            align-items: center;
            gap: .65rem;
            min-width: 0
        }

        .pw-generate-icon {
            display: inline-grid;
            place-items: center;
            width: 28px;
            height: 28px;
            flex: 0 0 28px;
            border: 1px solid color-mix(in srgb, var(--accent) 32%, rgba(148, 163, 184, .3));
            border-radius: 10px;
            color: var(--accent);
            background: color-mix(in srgb, var(--accent-soft) 22%, var(--card) 78%)
        }

        .pw-generate-copy {
            min-width: 0
        }

        .pw-generate-title {
            display: block;
            font-size: .86rem;
            line-height: 1.2
        }

        .pw-generate-copy .pw-sub {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap
        }

        .pw-generate-meta {
            display: inline-flex;
            align-items: center;
            gap: .55rem
        }

        .pw-generate-chevron {
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1;
            transition: transform .18s ease
        }

        .pw-generate[open] .pw-generate-chevron {
            transform: rotate(180deg)
        }

        .pw-generate-body {
            padding: .8rem .85rem .9rem
        }

        .pw-generate-note {
            display: flex;
            align-items: flex-start;
            gap: .4rem;
            margin-bottom: .75rem;
            color: var(--muted);
            font-size: .74rem
        }

        .pw-generate-note i {
            color: var(--accent)
        }

        .pw-generate summary {
            cursor: pointer;
            list-style: none
        }

        .pw-generate summary::-webkit-details-marker {
            display: none
        }

        .pw-generate-form {
            display: grid;
            grid-template-columns: 200px minmax(0, 1fr) 150px;
            column-gap: 1.5rem;
            row-gap: .8rem;
            padding: .05rem 0 0;
            align-items: end
        }

        .pw-field {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: .4rem
        }

        .pw-generate-form .pw-in,
        .pw-generate-form .pw-select {
            width: 100%;
            min-width: 0;
            height: 36px;
            box-sizing: border-box
        }

        .pw-field label {
            display: block;
            margin: 0;
            color: var(--muted);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .02em
        }

        .pw-range-field {
            grid-column: auto
        }

        .pw-generate-form .pw-submit {
            min-height: 34px;
            width: auto;
            min-width: 128px;
            justify-self: end;
            padding: .38rem .72rem;
            border-color: var(--accent);
            border-radius: 9px;
            background: var(--accent);
            color: #fff;
            font-size: .72rem;
            font-weight: 750;
            white-space: nowrap;
            justify-content: center;
            box-shadow: 0 3px 8px color-mix(in srgb, var(--accent) 16%, transparent);
            transition: background-color .18s ease, border-color .18s ease, box-shadow .18s ease, transform .18s ease
        }

        .pw-generate-form .pw-submit:hover,
        .pw-generate-form .pw-submit:focus-visible {
            border-color: var(--accent);
            background: var(--accent);
            color: #fff;
            box-shadow: 0 3px 8px color-mix(in srgb, var(--accent) 16%, transparent)
        }

        .pw-generate-form .pw-submit:active {
            transform: translateY(1px)
        }

        @media (max-width: 800px) {
            .pw-generate-form {
                grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr);
                gap: .85rem
            }

            .pw-range-field {
                grid-column: span 1
            }

            .pw-generate-form .pw-submit {
                grid-column: 1 / -1;
                width: 100%;
                justify-self: stretch
            }
        }

        @media (max-width: 767.98px) {
            .pw-period-stats {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr)
            }

            .pw-period-stat-wide {
                grid-column: auto
            }

            .pw-table-wrap {
                display: none
            }

            .pw-mobile-periods {
                display: grid;
                gap: .6rem;
                padding: .65rem
            }

            .pw-card > .pw-card-b {
                padding: .75rem
            }

            .pw-card-h {
                padding: .75rem
            }

            .pw-overview-top {
                margin-inline: -.5rem;
                padding-inline: .65rem
            }

            .pw-period-main {
                font-size: .77rem;
                white-space: normal
            }
        }

        @media (max-width: 640px) {
            .pw-overview-top {
                flex-direction: column;
                align-items: stretch
            }

            .pw-tabs {
                width: 100%
            }

            .pw-tab {
                flex: 1;
                text-align: center
            }

            .pw-generate-form {
                grid-template-columns: minmax(0, 1fr);
                gap: .75rem
            }

            .pw-range-field {
                grid-column: auto
            }

            .pw-generate-body {
                padding: .75rem
            }

            .pw-generate-form .pw-date-range {
                min-width: 0
            }

            .pw-generate-heading {
                flex: 1 1 auto;
                gap: .5rem;
                min-width: 0
            }

            .pw-generate-summary {
                align-items: flex-start;
                gap: .6rem
            }

            .pw-generate-copy {
                min-width: 0
            }

            .pw-generate-title,
            .pw-generate-copy .pw-sub {
                max-width: none;
                white-space: normal;
                overflow: visible;
                text-overflow: clip
            }

            .pw-generate-meta {
                flex: 0 0 auto
            }

            .pw-card-h > .pw-row {
                width: 100%;
                min-width: 0
            }

            .pw-card-h > .pw-row .pw-date-range {
                flex: 1 1 100%;
                width: 100%;
                min-width: 0
            }

            .pw-card-h > .pw-row .pw-btn {
                flex: 1 1 auto;
                justify-content: center
            }

            .pw-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: .45rem
            }

            .pw-kpi-card {
                padding: .55rem .6rem
            }

            .pw-kpi-value {
                font-size: .9rem
            }
        }
    </style>
@endpush

@section('content')
    @php
        $moduleLabels = [
            'all' => 'Semua Modul',
            'cutting' => 'Cutting',
            'sewing' => 'Sewing',
            'daily' => 'Harian',
        ];
        $activeModule = $module ?? 'all';
        $tabUrl = fn(string $tab) => route('payroll.piecework.overview', array_filter([
            'module' => $tab === 'all' ? null : $tab,
            'from' => request('from'),
            'to' => request('to'),
        ], fn($value) => $value !== null && $value !== ''));
        $moduleRoute = function (string $module, string $action, $period) {
            if ($module === 'daily') {
                return route("payroll.daily.{$action}", ['period' => $period]);
            }

            return route("payroll.piecework.{$action}", ['module' => $module, 'period' => $period]);
        };
        $defaultEnd = now()->toDateString();
        $defaultStart = now()->subDays(6)->toDateString();
    @endphp

    <div class="pw-overview-wrap">
        <div class="pw-overview-top">
            <div>
                <h1 class="pw-title">Payroll</h1>
                <div class="pw-sub">Satu daftar periode untuk Cutting, Sewing, dan Harian. Detail, finalisasi, dan pembayaran tetap mengikuti modul masing-masing.</div>
            </div>

            <div class="pw-tabs" id="pw-module-tabs" aria-label="Filter modul payroll">
                @foreach ($moduleLabels as $tab => $label)
                    <a class="pw-tab {{ $activeModule === $tab ? 'active' : '' }}" href="{{ $tabUrl($tab) }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <details class="pw-card pw-generate" {{ isset($errors) && $errors->any() ? 'open' : '' }}>
            <summary class="pw-card-h pw-generate-summary">
                <span class="pw-generate-heading">
                    <span class="pw-generate-icon" aria-hidden="true"><i class="bi bi-magic"></i></span>
                    <span class="pw-generate-copy">
                        <strong class="pw-generate-title">Generate Payroll</strong>
                        <span class="pw-sub">Pilih modul dan satu rentang periode</span>
                    </span>
                </span>
                <span class="pw-generate-meta">
                    <span class="pw-chip">Draft</span>
                    <span class="pw-generate-chevron" aria-hidden="true">⌄</span>
                </span>
            </summary>
            <div class="pw-card-b pw-generate-body">
                <div class="pw-generate-note">
                    <i class="bi bi-info-circle"></i>
                    <span>Periode yang dipilih akan dibuat sebagai draft dan bisa diperiksa sebelum difinalkan.</span>
                </div>
                @if (isset($errors) && $errors->any())
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="pw-generate-form" method="POST" action="{{ route('payroll.piecework.store_overview') }}"
                    id="pw-generate-form" data-daily-action="{{ route('payroll.daily.store') }}">
                    @csrf
                    <div class="pw-field">
                        <label for="overview-module">Modul Payroll</label>
                        <select class="pw-select w-100" id="overview-module" name="module" required>
                            <option value="">Pilih modul...</option>
                            <option value="cutting" @selected(old('module') === 'cutting')>Cutting</option>
                            <option value="sewing" @selected(old('module') === 'sewing')>Sewing</option>
                            <option value="daily" @selected(old('module') === 'daily')>Harian</option>
                        </select>
                    </div>
                    <div class="pw-field pw-range-field">
                        <label for="overview-period-range">Rentang Periode</label>
                        <input type="hidden" name="period_start" id="overview-period-start" value="{{ old('period_start', $defaultStart) }}" data-gf-date="off">
                        <input type="hidden" name="period_end" id="overview-period-end" value="{{ old('period_end', $defaultEnd) }}" data-gf-date="off">
                        <input class="pw-in pw-date-range w-100" id="overview-period-range" type="text"
                            value="" placeholder="Pilih tanggal mulai – tanggal akhir"
                            autocomplete="off" readonly data-gf-date="off" required>
                    </div>
                    <button class="pw-btn primary pw-submit" type="submit">Generate Draft</button>
                </form>
            </div>
        </details>

        <div class="pw-kpi-grid" id="pw-filter-kpis" aria-live="polite">
            <div class="pw-kpi-card">
                <div class="pw-kpi-label">Total Periode</div>
                <div class="pw-kpi-value">{{ number_format((int) ($kpis['period_count'] ?? 0), 0, ',', '.') }}</div>
            </div>
            <div class="pw-kpi-card">
                <div class="pw-kpi-label">Total Qty</div>
                <div class="pw-kpi-value">{{ rtrim(rtrim(number_format((float) ($kpis['total_qty'] ?? 0), 2, ',', '.'), '0'), ',') }}</div>
            </div>
            <div class="pw-kpi-card amount">
                <div class="pw-kpi-label">Total Payroll</div>
                <div class="pw-kpi-value">{{ number_format((float) ($kpis['total_amount'] ?? 0), 0, ',', '.') }}</div>
            </div>
            <div class="pw-kpi-card">
                <div class="pw-kpi-label">Sudah Dibayar</div>
                <div class="pw-kpi-value">{{ number_format((int) ($kpis['paid_count'] ?? 0), 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="pw-card pw-periods-card" id="pw-periods-card">
            <div class="pw-card-h">
                <div>
                    <strong>Daftar Periode</strong>
                    <div class="pw-sub">{{ $kpis['period_count'] ?? $periods->total() }} periode · filter aktif: {{ $moduleLabels[$activeModule] }}</div>
                </div>

                <form class="pw-row" method="GET" action="{{ route('payroll.piecework.overview') }}" id="pw-filter-form">
                    @if ($activeModule !== 'all')
                        <input type="hidden" name="module" value="{{ $activeModule }}">
                    @endif
                    <input type="hidden" name="from" id="pw-filter-from" value="{{ $filterFrom }}" data-gf-date="off">
                    <input type="hidden" name="to" id="pw-filter-to" value="{{ $filterTo }}" data-gf-date="off">
                    <input class="pw-in pw-date-range" type="text" id="pw-filter-range"
                        value="" placeholder="Pilih rentang tanggal" aria-label="Rentang tanggal"
                        autocomplete="off" readonly data-gf-date="off">
                    <button class="pw-btn" type="submit">Terapkan</button>
                    @if (request()->filled('from') || request()->filled('to'))
                        <a class="pw-btn" href="{{ $tabUrl($activeModule) }}">Reset</a>
                    @endif
                </form>
            </div>

            <div class="pw-table-wrap">
                <table class="pw-table">
                    <thead>
                        <tr>
                            <th>Periode / Operator</th>
                            <th>Modul / Basis</th>
                            <th class="pw-right">Qty</th>
                            <th class="pw-right">Total Amount</th>
                            <th class="pw-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($periods as $period)
                            @php
                                $periodModule = strtolower($period->module);
                                $isSewing = $periodModule === 'sewing';
                                $isDaily = $periodModule === 'daily';
                                $periodStart = \Carbon\Carbon::parse($period->period_start)->locale('id');
                                $periodEnd = \Carbon\Carbon::parse($period->period_end)->locale('id');
                                $periodWeek = $periodStart->weekOfMonth;
                                $periodMonth = $periodStart->translatedFormat('F Y');
                                $periodDateRange = $periodStart->translatedFormat('l, d/m/Y') . ' – ' . $periodEnd->translatedFormat('l, d/m/Y');
                                $operatorCount = (int) ($period->operator_count ?? 0);
                                $totalQty = (float) ($period->lines_total_qty ?? 0);
                                $totalAmount = (float) ($period->lines_total_amount ?? $period->total_amount ?? 0);
                            @endphp
                            <tr class="pw-period-row" data-pw-detail-url="{{ $moduleRoute($periodModule, 'show', $period) }}" tabindex="0">
                                <td class="pw-period-cell">
                                    <div class="pw-period-heading">
                                        <div class="pw-period-week">Minggu ke-{{ $periodWeek }} <span class="pw-sub" style="display:inline">· {{ $periodMonth }}</span></div>
                                        @if ($period->status === 'final')
                                            <span class="pw-chip final">FINAL</span>
                                        @else
                                            <span class="pw-chip draft">DRAFT</span>
                                        @endif
                                    </div>
                                    <div class="pw-period-main">{{ $periodDateRange }}</div>
                                    <div class="pw-period-meta">ID #{{ $period->id }} · {{ number_format($operatorCount, 0, ',', '.') }} operator @if ($period->paid_at) · Sudah dibayar @endif</div>
                                </td>
                                <td class="pw-basis">
                                    <span class="pw-chip {{ $periodModule }}">{{ $moduleLabels[$periodModule] ?? ucfirst($periodModule) }}</span>
                                    <div class="pw-period-meta">{{ $isDaily ? 'Hari hadir' : ($isSewing ? 'Ambil Jahit' : 'Qty PCS / QC OK') }}</div>
                                </td>
                                <td class="pw-right pw-number">{{ rtrim(rtrim(number_format($totalQty, 2, '.', ''), '0'), '.') }}</td>
                                <td class="pw-right pw-number pw-amount">{{ number_format($totalAmount, 0, ',', '.') }}</td>
                                <td class="pw-right pw-action">
                                    <div class="pw-action-wrap">
                                        <a class="pw-btn" href="{{ $moduleRoute($periodModule, 'show', $period) }}">Detail</a>
                                        @if ($period->status === 'draft' && ! $period->paid_at)
                                            <form method="POST" action="{{ $moduleRoute($periodModule, 'destroy', $period) }}"
                                                onsubmit="return confirm('Hapus payroll periode ini? Data detail payroll juga akan dihapus.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="pw-btn danger" type="submit">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding:1.1rem;color:var(--muted)">Belum ada periode payroll.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pw-mobile-periods">
                @forelse ($periods as $period)
                    @php
                        $periodModule = strtolower($period->module);
                        $isSewing = $periodModule === 'sewing';
                        $isDaily = $periodModule === 'daily';
                        $periodStart = \Carbon\Carbon::parse($period->period_start)->locale('id');
                        $periodEnd = \Carbon\Carbon::parse($period->period_end)->locale('id');
                        $periodWeek = $periodStart->weekOfMonth;
                        $periodMonth = $periodStart->translatedFormat('F Y');
                        $periodDateRange = $periodStart->translatedFormat('l, d/m/Y') . ' – ' . $periodEnd->translatedFormat('l, d/m/Y');
                        $operatorCount = (int) ($period->operator_count ?? 0);
                        $totalQty = (float) ($period->lines_total_qty ?? 0);
                        $totalAmount = (float) ($period->lines_total_amount ?? $period->total_amount ?? 0);
                    @endphp
                    <article class="pw-period-card" data-pw-detail-url="{{ $moduleRoute($periodModule, 'show', $period) }}" tabindex="0">
                        <div class="pw-period-card-top">
                            <span class="pw-chip {{ $periodModule }}">{{ $moduleLabels[$periodModule] ?? ucfirst($periodModule) }}</span>
                            @if ($period->status === 'final')
                                <span class="pw-chip final">FINAL</span>
                            @else
                                <span class="pw-chip draft">DRAFT</span>
                            @endif
                        </div>
                        <div class="pw-period-week">Minggu ke-{{ $periodWeek }} <span class="pw-sub">· {{ $periodMonth }}</span></div>
                        <div class="pw-period-main">{{ $periodDateRange }}</div>
                        <div class="pw-period-meta">ID #{{ $period->id }} · {{ number_format($operatorCount, 0, ',', '.') }} operator · {{ $isDaily ? 'Hari hadir' : ($isSewing ? 'Ambil Jahit' : 'Qty PCS / QC OK') }} @if ($period->paid_at) · Sudah dibayar @endif</div>
                        <div class="pw-period-stats">
                            <div>
                                <span>Qty</span>
                                <strong>{{ rtrim(rtrim(number_format($totalQty, 2, '.', ''), '0'), '.') }}</strong>
                            </div>
                            <div>
                                <span>Total Amount</span>
                                <strong>{{ number_format($totalAmount, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                        <div class="pw-period-card-footer">
                            @if ($period->paid_at)
                                <span class="pw-period-paid">Sudah dibayar</span>
                            @else
                                <span></span>
                            @endif
                            <div class="pw-period-card-actions">
                                <a class="pw-btn" href="{{ $moduleRoute($periodModule, 'show', $period) }}">Detail</a>
                                @if ($period->status === 'draft' && ! $period->paid_at)
                                    <form method="POST" action="{{ $moduleRoute($periodModule, 'destroy', $period) }}"
                                        onsubmit="return confirm('Hapus payroll periode ini? Data detail payroll juga akan dihapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="pw-btn danger" type="submit">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="pw-mobile-empty">Belum ada periode payroll.</div>
                @endforelse
            </div>

            <div class="pw-card-b">
                {{ $periods->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function bindDetailRows(root) {
                (root || document).querySelectorAll('[data-pw-detail-url]').forEach(function (element) {
                    if (element.dataset.pwDetailBound === '1') return;
                    element.dataset.pwDetailBound = '1';

                    const navigateToDetail = function () {
                        window.location.href = element.dataset.pwDetailUrl;
                    };

                    element.addEventListener('click', function (event) {
                        if (event.target.closest('a, button, form, input, select, textarea, label')) return;
                        navigateToDetail();
                    });

                    element.addEventListener('keydown', function (event) {
                        if (event.key !== 'Enter' && event.key !== ' ') return;
                        event.preventDefault();
                        navigateToDetail();
                    });
                });
            }

            bindDetailRows(document);

            const generateForm = document.getElementById('pw-generate-form');
            const generateModule = document.getElementById('overview-module');
            if (generateForm && generateModule) {
                const overviewAction = generateForm.action;
                const dailyAction = generateForm.dataset.dailyAction;
                const syncGenerateAction = function () {
                    generateForm.action = generateModule.value === 'daily' ? dailyAction : overviewAction;
                };

                generateModule.addEventListener('change', syncGenerateAction);
                syncGenerateAction();
            }

            const localeId = Object.assign(
                {},
                (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.id) || {},
                { firstDayOfWeek: 1 }
            );

            function parse(value) {
                return window.flatpickr && value ? window.flatpickr.parseDate(value, 'Y-m-d') : null;
            }

            function formatRange(dates) {
                if (!dates.length || typeof window.flatpickr !== 'function') return '';

                const format = date => window.flatpickr.formatDate(date, 'd/m/Y');
                if (dates.length === 1) return format(dates[0]);

                return format(dates[0]) + ' – ' + format(dates[1]);
            }

            function initRange(inputId, fromId, toId, onRangeChange) {
                if (typeof window.flatpickr !== 'function') return;

                const input = document.getElementById(inputId);
                const from = document.getElementById(fromId);
                const to = document.getElementById(toId);

                if (!input || !from || !to) return;
                if (input._flatpickr) input._flatpickr.destroy();

                const defaults = [parse(from.value), parse(to.value)].filter(Boolean);

                window.flatpickr(input, {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    locale: localeId,
                    altInput: false,
                    allowInput: false,
                    disableMobile: true,
                    defaultDate: defaults,
                    onReady(selectedDates, _, instance) {
                        instance.input.value = formatRange(selectedDates);
                    },
                    onChange(selectedDates, _, instance) {
                        if (selectedDates.length === 0) {
                            from.value = '';
                            to.value = '';
                        } else if (selectedDates.length === 1) {
                            const value = window.flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                            from.value = value;
                            to.value = value;
                        } else {
                            from.value = window.flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                            to.value = window.flatpickr.formatDate(selectedDates[1], 'Y-m-d');
                        }

                        instance.input.value = formatRange(selectedDates);
                        if (typeof onRangeChange === 'function') onRangeChange(selectedDates);
                    },
                });
            }

            function formUrl(form) {
                const url = new URL(form.action, window.location.origin);
                new FormData(form).forEach(function (value, key) {
                    if (value !== '') url.searchParams.set(key, value);
                });
                return url.toString();
            }

            function refreshOverview(url, pushHistory) {
                const currentCard = document.getElementById('pw-periods-card');
                if (currentCard) currentCard.classList.add('is-loading');

                return fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                    },
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('Gagal memuat filter payroll.');
                        return response.text();
                    })
                    .then(function (html) {
                        const documentParser = new DOMParser();
                        const nextDocument = documentParser.parseFromString(html, 'text/html');
                        const nextKpis = nextDocument.getElementById('pw-filter-kpis');
                        const currentKpis = document.getElementById('pw-filter-kpis');
                        const nextTabs = nextDocument.getElementById('pw-module-tabs');
                        const currentTabs = document.getElementById('pw-module-tabs');
                        const nextCard = nextDocument.getElementById('pw-periods-card');
                        const activeCard = document.getElementById('pw-periods-card');
                        const oldRange = document.getElementById('pw-filter-range');

                        if (oldRange && oldRange._flatpickr) oldRange._flatpickr.destroy();
                        if (nextKpis && currentKpis) currentKpis.replaceWith(nextKpis);
                        if (nextTabs && currentTabs) currentTabs.replaceWith(nextTabs);
                        if (nextCard && activeCard) activeCard.replaceWith(nextCard);

                        bindDetailRows(document);
                        bindRealtimeControls();
                        initRange('pw-filter-range', 'pw-filter-from', 'pw-filter-to', function (selectedDates) {
                            if (selectedDates.length === 2) scheduleFilterRefresh();
                        });

                        if (pushHistory) window.history.pushState({}, '', url);
                    })
                    .catch(function () {
                        window.location.assign(url);
                    })
                    .finally(function () {
                        const refreshedCard = document.getElementById('pw-periods-card');
                        if (refreshedCard) refreshedCard.classList.remove('is-loading');
                    });
            }

            function bindRealtimeControls() {
                document.querySelectorAll('#pw-module-tabs a').forEach(function (link) {
                    if (link.dataset.realtimeBound === '1') return;
                    link.dataset.realtimeBound = '1';
                    link.addEventListener('click', function (event) {
                        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                        event.preventDefault();
                        refreshOverview(link.href, true);
                    });
                });

                const filterForm = document.getElementById('pw-filter-form');
                if (filterForm && filterForm.dataset.realtimeBound !== '1') {
                    filterForm.dataset.realtimeBound = '1';
                    filterForm.addEventListener('submit', function (event) {
                        event.preventDefault();
                        refreshOverview(formUrl(filterForm), true);
                    });
                }
            }

            let filterRefreshTimer;
            function scheduleFilterRefresh() {
                window.clearTimeout(filterRefreshTimer);
                filterRefreshTimer = window.setTimeout(function () {
                    const filterForm = document.getElementById('pw-filter-form');
                    if (filterForm) refreshOverview(formUrl(filterForm), true);
                }, 150);
            }

            bindRealtimeControls();
            initRange('pw-filter-range', 'pw-filter-from', 'pw-filter-to', function (selectedDates) {
                if (selectedDates.length === 2) scheduleFilterRefresh();
            });
            initRange('overview-period-range', 'overview-period-start', 'overview-period-end');
            window.addEventListener('popstate', function () {
                refreshOverview(window.location.href, false);
            });
        });
    </script>
@endpush
