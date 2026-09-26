@extends('layouts.app')

@section('title', 'Payroll Detail • ' . ($moduleLabel ?? ucfirst($module ?? '')))

@push('head')
    <style>
        .pw-wrap {
            max-width: 1040px;
            margin: 0 auto;
            padding: .75rem .75rem 2.5rem
        }

        .pw-top {
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

        .pw-heading {
            min-width: 0
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
            border-radius: 10px;
            box-shadow: none
        }

        .pw-h {
            padding: .7rem .8rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: center
        }

        .pw-b {
            padding: .8rem
        }

        .pw-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border: 1px solid rgba(148, 163, 184, .35);
            background: transparent;
            color: var(--text);
            padding: .42rem .65rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: .8rem
        }

        .pw-btn.primary {
            border-color: color-mix(in srgb, var(--accent) 40%, rgba(148, 163, 184, .35));
            background: color-mix(in srgb, var(--accent-soft) 18%, var(--card) 82%)
        }

        .pw-btn.danger {
            border-color: rgba(239, 68, 68, .35);
            color: rgba(239, 68, 68, 1)
        }

        .pw-btn.success {
            border-color: rgba(16, 185, 129, .35);
            color: rgba(16, 185, 129, 1)
        }

        .pw-row {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center
        }

        .pw-in,
        .pw-sel {
            border: 1px solid rgba(148, 163, 184, .28);
            background: transparent;
            color: var(--text);
            border-radius: 12px;
            padding: .46rem .6rem;
            font-size: .88rem
        }

        .pw-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0
        }

        .pw-table-wrap {
            overflow-x: auto
        }

        @media (min-width: 769px) {
            .pw-table-wrap {
                max-height: 60vh;
                overflow: auto
            }
        }

        .pw-table thead tr {
            position: sticky;
            top: 0;
            z-index: 3
        }

        .pw-table th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: var(--card);
            box-shadow: 0 1px 0 rgba(148, 163, 184, .22);
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            text-align: left;
            padding: .55rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18)
        }

        .pw-table td {
            padding: .6rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, .12);
            vertical-align: top
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
            font-size: .78rem;
            color: var(--muted)
        }

        .pw-chip.final {
            border-color: rgba(16, 185, 129, .35);
            color: rgba(16, 185, 129, 1)
        }

        .pw-chip.draft {
            border-color: rgba(245, 158, 11, .35);
            color: rgba(245, 158, 11, 1)
        }

        .pw-summary-total {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: .85rem;
            border-top: 2px solid rgba(148, 163, 184, .28)
        }

        .pw-summary-total-label {
            color: var(--muted);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase
        }

        .pw-summary-total-note {
            margin-top: .2rem;
            color: var(--muted);
            font-size: .72rem
        }

        .pw-summary-total-amount {
            color: var(--text);
            font-size: 1.1rem;
            font-weight: 900;
            letter-spacing: -.02em;
            text-align: right;
            white-space: nowrap
        }

        .pw-summary-average {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 1rem;
            padding: .7rem .75rem;
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 8px;
            background: color-mix(in srgb, var(--card) 92%, var(--accent-soft) 8%)
        }

        .pw-summary-average-label {
            color: var(--muted);
            font-size: .72rem;
            font-weight: 800
        }

        .pw-summary-average-note {
            margin-top: .15rem;
            color: var(--muted);
            font-size: .68rem
        }

        .pw-summary-average-value {
            color: var(--text);
            font-size: .9rem;
            font-weight: 850;
            text-align: right;
            white-space: nowrap
        }

        .pw-summary-total-qty {
            margin-top: .18rem;
            color: var(--muted);
            font-size: .72rem;
            text-align: right
        }

        .pw-category-summary {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(148, 163, 184, .18)
        }

        .pw-category-summary-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .35rem
        }

        .pw-category-table tfoot th {
            color: var(--text);
            font-weight: 850;
            border-top: 2px solid rgba(148, 163, 184, .28)
        }

        .pw-daily-status-form {
            display: flex;
            align-items: center;
            gap: .35rem;
            min-width: 218px
        }

        .pw-daily-status {
            min-width: 140px;
            padding: .35rem .48rem;
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 8px;
            background: var(--card);
            color: var(--text);
            font-size: .78rem
        }

        .pw-daily-save {
            padding: .36rem .52rem;
            border: 1px solid color-mix(in srgb, var(--accent) 40%, rgba(148, 163, 184, .35));
            border-radius: 8px;
            background: color-mix(in srgb, var(--accent-soft) 18%, var(--card) 82%);
            color: var(--text);
            font-size: .74rem;
            white-space: nowrap
        }

        .pw-daily-save-state {
            min-width: 4.6rem;
            color: var(--muted);
            font-size: .68rem;
            white-space: nowrap
        }

        .pw-daily-save-state.is-saved {
            color: #15803d
        }

        .pw-daily-save-state.is-error {
            color: #b91c1c
        }

        .pw-daily-day {
            color: var(--text);
            font-size: .78rem;
            font-weight: 750;
            line-height: 1.25
        }

        .pw-daily-date {
            margin-top: .12rem;
            color: var(--muted);
            font-size: .72rem;
            line-height: 1.25
        }

        .pw-daily-table {
            font-size: .82rem
        }

        .pw-pay-form {
            align-items: center
        }

        .pw-pay-account {
            order: 1;
            display: grid;
            gap: .25rem;
            min-width: 210px
        }

        .pw-pay-account .pw-sel {
            width: 100%
        }

        .pw-pay-account-label {
            color: var(--muted);
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .03em;
            text-transform: uppercase
        }

        .pw-loan-panel {
            order: 3;
            flex-basis: 100%;
            margin-top: .25rem;
            padding: .65rem .7rem;
            border: 1px solid rgba(245, 158, 11, .28);
            border-radius: 8px
        }

        .pw-bonus-panel {
            order: 4;
            flex-basis: 100%;
            margin-top: .25rem;
            padding: .65rem .7rem;
            border: 1px solid rgba(16, 185, 129, .28);
            border-radius: 8px
        }

        .pw-bonus-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .65rem;
            margin-top: .4rem
        }

        .pw-bonus-meta {
            min-width: 0;
            font-size: .78rem
        }

        .pw-bonus-select {
            min-width: 145px
        }

        .pw-loan-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .65rem;
            margin-top: .4rem
        }

        .pw-loan-meta {
            min-width: 0;
            font-size: .78rem
        }

        .pw-loan-input {
            width: 145px
        }

        .pw-pay-form > .pw-btn {
            order: 2
        }

        .pw-detail-table {
            min-width: 640px
        }

        .pw-daily-table th {
            white-space: nowrap
        }

        .pw-daily-table td {
            vertical-align: middle
        }

        .pw-daily-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .65rem .8rem;
            border-bottom: 1px solid rgba(148, 163, 184, .14)
        }

        .pw-daily-filter-field {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            color: var(--muted);
            font-size: .74rem;
            font-weight: 700
        }

        .pw-daily-filter {
            min-width: 150px;
            padding: .35rem .5rem;
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 8px;
            background: var(--card);
            color: var(--text);
            font-size: .78rem;
            font-weight: 600
        }

        .pw-daily-filter-count {
            color: var(--muted);
            font-size: .72rem
        }

        .pw-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: .75rem
        }

        @media (min-width: 992px) {
            .pw-grid {
                grid-template-columns: 380px 1fr
            }
        }

        @media (max-width: 640px) {
            .pw-wrap {
                padding: .5rem .55rem 2rem;
                font-size: .82rem
            }

            .pw-hide-sm {
                display: none
            }

            .pw-top {
                flex-direction: column;
                align-items: stretch;
                margin: 0 -.55rem .55rem;
                padding: .45rem .55rem
            }

            .pw-top > .pw-row {
                justify-content: stretch
            }

            .pw-top > .pw-row .pw-btn {
                flex: 1;
                justify-content: center
            }

            .pw-title {
                font-size: .92rem;
                line-height: 1.25
            }

            .pw-sub {
                font-size: .7rem;
                line-height: 1.35
            }

            .pw-btn {
                min-height: 36px;
                padding: .45rem .55rem;
                font-size: .74rem
            }

            .pw-card {
                border-radius: 8px
            }

            .pw-h,
            .pw-b {
                padding: .65rem
            }

            .pw-table th {
                padding: .45rem .42rem;
                font-size: .64rem
            }

            .pw-table td {
                padding: .48rem .42rem;
                font-size: .76rem
            }

            .pw-summary-table {
                min-width: 100%
            }

            .pw-summary-table,
            .pw-summary-table tbody,
            .pw-summary-table tr,
            .pw-summary-table td,
            .pw-category-table,
            .pw-category-table tbody,
            .pw-category-table tr,
            .pw-category-table td {
                display: block;
                width: 100%;
                box-sizing: border-box
            }

            .pw-summary-table thead,
            .pw-category-table thead {
                display: none
            }

            .pw-summary-table tbody tr,
            .pw-category-table tbody tr {
                padding: .7rem .65rem;
                border-bottom: 1px solid rgba(148, 163, 184, .16)
            }

            .pw-summary-table tbody tr:last-child,
            .pw-category-table tbody tr:last-child {
                border-bottom: 0
            }

            .pw-summary-table tbody td,
            .pw-category-table tbody td {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: .75rem;
                padding: .25rem 0;
                border: 0;
                text-align: right
            }

            .pw-summary-table tbody td:first-child,
            .pw-category-table tbody td:first-child {
                display: block;
                padding-bottom: .5rem;
                margin-bottom: .2rem;
                border-bottom: 1px solid rgba(148, 163, 184, .12);
                text-align: left
            }

            .pw-summary-table tbody td:not(:first-child)::before,
            .pw-category-table tbody td:not(:first-child)::before {
                content: attr(data-label);
                flex: 0 0 auto;
                color: var(--muted);
                font-size: .68rem;
                font-weight: 750;
                text-align: left
            }

            .pw-summary-table tbody td:not(:first-child) > *,
            .pw-category-table tbody td:not(:first-child) > * {
                text-align: right
            }

            .pw-category-table {
                min-width: 100%
            }

            .pw-category-table tfoot,
            .pw-category-table tfoot tr,
            .pw-category-table tfoot th {
                display: block;
                width: 100%;
                box-sizing: border-box
            }

            .pw-category-table tfoot tr {
                padding: .65rem;
                border-top: 2px solid rgba(148, 163, 184, .28)
            }

            .pw-category-table tfoot th {
                display: flex;
                justify-content: space-between;
                padding: .18rem 0;
                border: 0;
                text-align: right
            }

            .pw-category-table tfoot th:first-child {
                text-align: left
            }

            .pw-category-table tfoot th:not(:first-child)::before {
                content: attr(data-label);
                color: var(--muted);
                font-size: .68rem;
                font-weight: 750;
                text-align: left
            }

            .pw-daily-table {
                min-width: 0
            }

            .pw-pay-form {
                align-items: stretch
            }

            .pw-pay-form > .pw-sel,
            .pw-pay-form > .pw-btn {
                width: 100%;
                min-height: 38px
            }

            .pw-pay-account {
                order: 1;
                flex: 1 1 0;
                width: auto;
                min-width: 0
            }

            .pw-pay-form > .pw-btn {
                order: 2;
                width: auto;
                min-width: 76px;
                flex: 0 0 auto;
                align-self: end
            }

            .pw-loan-panel {
                order: 3;
                padding: .6rem
            }

            .pw-bonus-panel {
                order: 4;
                padding: .6rem
            }

            .pw-bonus-row {
                align-items: stretch;
                flex-direction: column;
                gap: .3rem
            }

            .pw-bonus-meta {
                font-size: .74rem;
                line-height: 1.35
            }

            .pw-bonus-select {
                width: 100%;
                min-height: 38px
            }

            .pw-loan-row {
                align-items: stretch;
                flex-direction: column;
                gap: .3rem
            }

            .pw-loan-meta {
                font-size: .74rem;
                line-height: 1.35
            }

            .pw-loan-input {
                width: 100%;
                min-height: 38px
            }

            .pw-summary-total-amount {
                margin-top: .35rem;
                font-size: 1rem;
                text-align: left
            }

            .pw-daily-status-form {
                min-width: 0;
                width: 100%
            }

            .pw-daily-status {
                flex: 1;
                min-width: 0
            }

            .pw-daily-toolbar {
                align-items: stretch;
                flex-direction: column;
                gap: .45rem;
                padding: .65rem .75rem
            }

            .pw-daily-filter-field {
                justify-content: space-between
            }

            .pw-daily-filter {
                flex: 1;
                min-width: 0
            }

            .pw-daily-table-wrap {
                overflow: visible
            }

            .pw-daily-table,
            .pw-daily-table tbody,
            .pw-daily-table tr,
            .pw-daily-table td {
                display: block;
                width: 100%;
                box-sizing: border-box
            }

            .pw-daily-table {
                font-size: .8rem
            }

            .pw-daily-table thead {
                display: none
            }

            .pw-daily-table tbody tr {
                padding: .7rem .75rem;
                border-bottom: 1px solid rgba(148, 163, 184, .15)
            }

            .pw-daily-table tbody tr:last-child {
                border-bottom: 0
            }

            .pw-daily-table tbody td {
                display: grid;
                grid-template-columns: 7rem minmax(0, 1fr);
                gap: .65rem;
                align-items: center;
                padding: .32rem 0;
                border: 0;
                text-align: left
            }

            .pw-daily-table tbody td::before {
                content: attr(data-label);
                color: var(--muted);
                font-size: .68rem;
                font-weight: 700;
                letter-spacing: .02em
            }

            .pw-daily-table tbody td.pw-daily-action-cell {
                display: block;
                padding-top: .55rem
            }

            .pw-daily-table tbody td.pw-daily-action-cell::before {
                display: none
            }

            .pw-daily-table .pw-daily-status-form {
                margin: 0;
                width: 100%
            }

            .pw-daily-table .pw-daily-save {
                min-height: 34px
            }

            .pw-daily-table .pw-right {
                text-align: left
            }

            .pw-summary-total {
                align-items: flex-start
            }

            .pw-category-summary-heading {
                flex-direction: column;
                gap: .15rem
            }
        }
    </style>
@endpush

@section('content')
    @php
        $isFinalized = $period->isFinalized();
        $qtyLabel = $module === 'daily' ? 'Hari Efektif' : ($module === 'sewing' ? 'Qty Ambil' : 'Qty Payroll');
        $pageLabel = $module === 'daily' ? 'Payroll Harian' : 'Payroll Borongan';
        $moduleRoute = function (string $action, array $parameters = []) use ($module) {
            if ($module === 'daily') {
                if ($action === 'slip') {
                    return route('payroll.daily.slip_preview', $parameters);
                }

                return route("payroll.daily.{$action}", $parameters);
            }

            return route("payroll.piecework.{$action}", ['module' => $module] + $parameters);
        };
        $periodStart = \Carbon\Carbon::parse($period->period_start)->locale('id');
        $periodEnd = \Carbon\Carbon::parse($period->period_end)->locale('id');
        $periodWeek = $periodStart->weekOfMonth;
        $periodMonth = $periodStart->translatedFormat('F Y');
        $periodDateRange = $periodStart->translatedFormat('l, d/m/Y') . ' – ' . $periodEnd->translatedFormat('l, d/m/Y');
    @endphp
    <div class="pw-wrap">
        <div class="pw-top">
            <div class="pw-heading">
                <h1 class="pw-title">{{ $moduleLabel ?? ucfirst($module) }} • {{ $pageLabel }}</h1>
                <div class="pw-sub">
                    Minggu ke-{{ $periodWeek }} · {{ $periodMonth }} · {{ $periodDateRange }} · ID #{{ $period->id }}
                    ·
                    @if ($isFinalized)
                        <span class="pw-chip final">FINAL</span>
                    @else
                        <span class="pw-chip draft">DRAFT</span>
                    @endif
                    @if ($period->paid_at)
                        • Paid {{ \Carbon\Carbon::parse($period->paid_at)->format('d/m/Y H:i') }}
                    @endif
                </div>
            </div>

            <div class="pw-row">
                <a class="pw-btn" href="{{ route('payroll.piecework.overview', ['module' => $module]) }}">Daftar Payroll</a>
                <a class="pw-btn primary" href="{{ route('payroll.piecework.overview', ['module' => $module]) }}">＋ Generate</a>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="pw-grid">
            {{-- LEFT: Summary + Actions --}}
            <div class="pw-card">
                <div class="pw-h">
                    <div style="font-weight:900">Ringkasan</div>
                    <div class="pw-sub">Rekap per operator{{ $module === 'daily' ? ' dan per hari' : '' }}</div>
                </div>

                <div class="pw-b">
                    {{-- ACTIONS --}}
                    <div class="pw-row" style="margin-bottom:.75rem">
                        @if (! $isFinalized)
                            <form method="POST"
                                action="{{ $moduleRoute('finalize', ['period' => $period]) }}">
                                @csrf
                                <button class="pw-btn primary" type="submit"
                                    onclick="return confirm('Finalkan periode ini? Ini akan mencatat: Dr HPP (5101) / Cr Hutang Upah Borongan (2102).')">
                                    FINALIZE
                                </button>
                            </form>

                            <form method="POST"
                                action="{{ $moduleRoute('regenerate', ['period' => $period]) }}">
                                @csrf
                                <button class="pw-btn danger" type="submit"
                                    onclick="return confirm('Regenerate draft ini? Lines akan dihitung ulang.')">
                                    REGENERATE
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ $moduleRoute('unpost', ['period' => $period]) }}">
                                @csrf
                                <button class="pw-btn" type="submit"
                                    onclick="return confirm('{{ $period->paid_at ? 'Pembayaran, potongan pinjaman, dan bonus tabungan akan dibatalkan secara akuntansi. ' : '' }}Unpost payroll ini dan kembalikan ke DRAFT?')">
                                    UNPOST
                                </button>
                            </form>
                        @endif
                    </div>

                    {{-- PAY (only if final & not paid) --}}
                    @if ($isFinalized && !$period->paid_at)
                        <form class="pw-row pw-pay-form" method="POST"
                            action="{{ $moduleRoute('pay', ['period' => $period]) }}">
                            @csrf
                            <label class="pw-pay-account">
                                <span class="pw-pay-account-label">Akun pembayaran hutang</span>
                                <select class="pw-sel" name="paid_from_account_id" required>
                                    <option value="">Pilih akun Kas/Bank...</option>
                                    @foreach ($cashAccounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->code }} • {{ $acc->name }}</option>
                                    @endforeach
                                </select>
                            </label>

                            @if ($employeeLoansByEmployee->isNotEmpty())
                                <div class="pw-loan-panel">
                                    <div style="font-weight:800;font-size:.8rem">Potongan hutang karyawan <span class="pw-sub">(opsional)</span></div>
                                    @foreach ($summaryByEmployee as $summaryEmployee)
                                        @foreach ($employeeLoansByEmployee->get($summaryEmployee['employee_id'], collect()) as $loan)
                                            <label class="pw-loan-row">
                                                <span class="pw-loan-meta">
                                                    {{ $summaryEmployee['employee_name'] }} · {{ $loan->reference ?: 'Pinjaman #'.$loan->id }}
                                                    <span class="pw-sub">Sisa Rp {{ number_format($loan->outstanding_amount, 0, ',', '.') }}</span>
                                                </span>
                                                <input class="pw-in pw-loan-input" type="number" name="loan_deductions[{{ $loan->id }}]"
                                                    min="0" max="{{ $loan->outstanding_amount }}" step="0.01"
                                                    placeholder="0" aria-label="Potongan {{ $summaryEmployee['employee_name'] }}">
                                            </label>
                                        @endforeach
                                    @endforeach
                                </div>
                            @endif

                            @if ($module === 'daily' && $attendanceBonuses->isNotEmpty())
                                <div class="pw-bonus-panel">
                                    <div style="font-weight:800;font-size:.8rem">Bonus kehadiran 10%</div>
                                    @foreach ($attendanceBonuses as $bonus)
                                        <label class="pw-bonus-row">
                                            <span class="pw-bonus-meta">
                                                {{ $bonus['employee_name'] }} · {{ $bonus['present_count'] }} hari hadir
                                                <span class="pw-sub">Bonus Rp {{ number_format($bonus['bonus_amount'], 0, ',', '.') }}</span>
                                            </span>
                                            <select class="pw-sel pw-bonus-select" name="attendance_bonus_destinations[{{ $bonus['employee_id'] }}]">
                                                <option value="savings" selected>Tabungkan</option>
                                                <option value="paid">Bayarkan</option>
                                            </select>
                                        </label>
                                    @endforeach
                                </div>
                            @endif

                            <button class="pw-btn success" type="submit"
                                onclick="return confirm('Catat pembayaran? Ini akan melunasi hutang payroll.')">
                                BAYAR
                            </button>
                        </form>
                    @elseif($period->paid_at)
                        <div class="pw-sub">Pembayaran sudah dicatat.</div>
                    @endif

                    <hr style="border:none;border-top:1px solid rgba(148,163,184,.18);margin:1rem 0">

                    {{-- SUMMARY TABLE --}}
                    <div class="pw-table-wrap">
                        <table class="pw-table pw-summary-table">
                            <thead>
                                <tr>
                                    <th>Operator</th>
                                    @if ($module === 'daily')
                                        <th class="pw-right">Hadir</th>
                                        <th class="pw-right">Libur</th>
                                        <th class="pw-right">Hari Efektif</th>
                                        <th class="pw-right">Bonus</th>
                                    @else
                                        <th class="pw-right">{{ $qtyLabel }}</th>
                                    @endif
                                    <th class="pw-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($summaryByEmployee as $s)
                                    <tr>
                                        <td data-label="Operator">
                                            <div style="font-weight:800">{{ $s['employee_name'] }}</div>
                                            <div class="pw-row" style="margin-top:.3rem">
                                                <a class="pw-btn"
                                                    href="{{ $moduleRoute('slip', ['period' => $period, 'employee' => $s['employee_id']]) }}">
                                                    Slip
                                                </a>
                                            </div>
                                        </td>
                                        @if ($module === 'daily')
                                            <td data-label="Hadir" class="pw-right">{{ number_format((int) ($s['present_count'] ?? 0), 0, ',', '.') }}</td>
                                            <td data-label="Libur" class="pw-right">{{ number_format((int) ($s['holiday_count'] ?? 0), 0, ',', '.') }}</td>
                                            <td data-label="Hari Efektif" class="pw-right">
                                                {{ rtrim(rtrim(number_format((float) $s['total_qty'], 2, '.', ''), '0'), '.') }}
                                            </td>
                                            <td data-label="Bonus" class="pw-right">
                                                {{ number_format((float) ($s['attendance_bonus'] ?? 0), 0, ',', '.') }}
                                            </td>
                                        @else
                                            <td data-label="{{ $qtyLabel }}" class="pw-right">
                                                {{ rtrim(rtrim(number_format((float) $s['total_qty'], 2, '.', ''), '0'), '.') }}
                                            </td>
                                        @endif
                                        <td data-label="Amount" class="pw-right" style="font-weight:800">
                                            {{ number_format((float) $s['total_amount'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $module === 'daily' ? 6 : 3 }}" style="padding:1rem;color:var(--muted)">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($module !== 'daily' && $summaryByCategory->isNotEmpty())
                        <div class="pw-category-summary">
                            <div class="pw-category-summary-heading">
                                <div style="font-weight:900">Ringkasan per Kategori</div>
                                <div class="pw-sub">Total qty dan amount berdasarkan kategori item</div>
                            </div>
                            <div class="pw-table-wrap">
                                <table class="pw-table pw-category-table">
                                    <thead>
                                        <tr>
                                            <th>Kategori</th>
                                            <th class="pw-right">Total Qty</th>
                                            <th class="pw-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($summaryByCategory as $categorySummary)
                                            <tr>
                                                <td data-label="Kategori">
                                                    <div style="font-weight:750">
                                                        {{ $categorySummary['category_name'] ?: ($categorySummary['category_code'] ?: 'Tanpa Kategori') }}
                                                    </div>
                                                    @if ($categorySummary['category_code'] && $categorySummary['category_name'])
                                                        <div class="pw-sub">{{ $categorySummary['category_code'] }}</div>
                                                    @endif
                                                </td>
                                                <td data-label="Total Qty" class="pw-right">
                                                    {{ rtrim(rtrim(number_format((float) $categorySummary['total_qty'], 2, '.', ''), '0'), '.') }}
                                                </td>
                                                <td data-label="Amount" class="pw-right" style="font-weight:800">
                                                    {{ number_format((float) $categorySummary['total_amount'], 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Total</th>
                                            <th data-label="Total Qty" class="pw-right">
                                                {{ rtrim(rtrim(number_format((float) $grandTotalQty, 2, '.', ''), '0'), '.') }}
                                            </th>
                                            <th data-label="Amount" class="pw-right">
                                                {{ number_format((float) $grandTotalAmount, 0, ',', '.') }}
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if (!empty($allowSlipAll))
                        <div class="pw-row" style="margin-top:.75rem">
                            <a class="pw-btn"
                                href="{{ route('payroll.piecework.slip_all', ['module' => $module, 'period' => $period]) }}">Slip
                                All</a>
                        </div>
                    @endif

                    @if ($module === 'daily')
                        <div class="pw-summary-average">
                            <div>
                                <div class="pw-summary-average-label">Rata-rata pengeluaran per hari</div>
                                <div class="pw-summary-average-note">Total payroll dibagi {{ $periodDays }} hari periode</div>
                            </div>
                            <div class="pw-summary-average-value" @if ($module === 'daily') data-pw-daily-average @endif>{{ number_format((float) $averageDailyAmount, 0, ',', '.') }}</div>
                        </div>
                    @endif

                    <div class="pw-summary-total">
                        <div>
                            <div class="pw-summary-total-label">Total Payroll</div>
                            <div class="pw-summary-total-note">{{ $module === 'daily' ? 'Setelah rekap kehadiran seluruh operator' : 'Total seluruh baris payroll' }}</div>
                        </div>
                        <div>
                            <div class="pw-summary-total-amount" @if ($module === 'daily') data-pw-daily-grand-total @endif>{{ number_format((float) $grandTotalAmount, 0, ',', '.') }}</div>
                            <div class="pw-summary-total-qty">{{ $module === 'daily' ? 'Hari efektif: ' : 'Qty: ' }}{{ rtrim(rtrim(number_format((float) $grandTotalQty, 2, '.', ''), '0'), '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT: Detail lines --}}
            <div class="pw-card">
                <div class="pw-h">
                    <div style="font-weight:900">{{ $module === 'daily' ? 'Detail Kehadiran' : 'Detail Lines' }}</div>
                    <div class="pw-sub" style="margin:0">{{ $module === 'daily' ? 'Tandai status kehadiran setiap operator dan tanggal.' : 'Tampil per operator → kategori → item.' }}</div>
                </div>

                <div class="pw-b" style="padding:0">
                    @if ($module === 'daily')
                        @php
                            $dailyEmployees = $lines
                                ->pluck('employee')
                                ->filter()
                                ->unique('id')
                                ->sortBy(fn ($employee) => mb_strtolower((string) $employee->name))
                                ->values();
                        @endphp
                        <div class="pw-daily-toolbar">
                            <label class="pw-daily-filter-field" for="pw-daily-day-filter">
                                <span>Filter Hari</span>
                                <select class="pw-daily-filter" id="pw-daily-day-filter">
                                    <option value="all">Semua Hari</option>
                                    <option value="1">Senin</option>
                                    <option value="2">Selasa</option>
                                    <option value="3">Rabu</option>
                                    <option value="4">Kamis</option>
                                    <option value="5">Jumat</option>
                                    <option value="6">Sabtu</option>
                                    <option value="0">Minggu</option>
                                </select>
                            </label>
                            <label class="pw-daily-filter-field" for="pw-daily-employee-filter">
                                <span>Filter Karyawan</span>
                                <select class="pw-daily-filter" id="pw-daily-employee-filter">
                                    <option value="all">Semua Karyawan</option>
                                    @foreach ($dailyEmployees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <span class="pw-daily-filter-count" id="pw-daily-filter-count">{{ $lines->count() }} baris</span>
                        </div>
                    @endif
                    <div class="pw-table-wrap {{ $module === 'daily' ? 'pw-daily-table-wrap' : '' }}">
                        @if ($module === 'daily')
                            @php
                                $attendanceLabels = [
                                    'pending' => 'Belum diisi',
                                    'hadir' => 'Hadir',
                                    'setengah_hari' => 'Setengah Hari',
                                    'izin' => 'Izin',
                                    'sakit' => 'Sakit',
                                    'libur' => 'Libur',
                                ];
                            @endphp
                            <table class="pw-table pw-daily-table pw-detail-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Operator</th>
                                        <th>Status Kehadiran</th>
                                        <th class="pw-right">Upah Harian</th>
                                        <th class="pw-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lines as $l)
                                        <tr data-pw-day="{{ $l->work_date ? \Carbon\Carbon::parse($l->work_date)->dayOfWeek : '' }}"
                                            data-pw-employee="{{ $l->employee_id }}">
                                            <td data-label="Tanggal" style="white-space:nowrap">
                                                @if ($l->work_date)
                                                    @php $workDate = \Carbon\Carbon::parse($l->work_date)->locale('id'); @endphp
                                                    <div class="pw-daily-day">{{ $workDate->translatedFormat('l') }}</div>
                                                    <div class="pw-daily-date">{{ $workDate->format('d/m/Y') }}</div>
                                                @else
                                                    <span class="pw-daily-date">-</span>
                                                @endif
                                            </td>
                                            <td data-label="Operator" style="font-weight:700">{{ $l->employee?->name ?? '-' }}</td>
                                            <td data-label="Status Kehadiran">
                                                @if ($period->status === 'final' || $period->paid_at)
                                                    <span class="pw-chip">{{ $attendanceLabels[$l->attendance_status] ?? '-' }}</span>
                                                @else
                                                    <form class="pw-daily-status-form" method="POST"
                                                        action="{{ $moduleRoute('daily_line.update', ['period' => $period, 'line' => $l]) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select class="pw-daily-status" name="attendance_status" aria-label="Status kehadiran {{ $l->employee?->name }} {{ $l->work_date?->format('d/m/Y') }}">
                                                            @foreach ($attendanceLabels as $status => $label)
                                                                <option value="{{ $status }}" @selected(($l->attendance_status ?: 'pending') === $status)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="pw-daily-save-state" aria-live="polite"></span>
                                                    </form>
                                                @endif
                                            </td>
                                            <td data-label="Upah Harian" class="pw-right" style="white-space:nowrap">
                                                {{ number_format((float) ($l->rate_per_day ?: $l->rate_per_pcs), 0, ',', '.') }}
                                            </td>
                                            <td data-label="Total" data-pw-daily-amount class="pw-right" style="font-weight:800;white-space:nowrap">
                                                {{ number_format((float) $l->amount, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" style="padding:1rem;color:var(--muted)">Tidak ada detail kehadiran.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        @else
                            <table class="pw-table pw-detail-table">
                                <thead>
                                    <tr>
                                        @if ($module !== 'daily')
                                            <th>Hari / Tanggal</th>
                                        @endif
                                        <th>Employee</th>
                                        <th>Kode Kategori</th>
                                        <th>Kode Barang</th>
                                        <th class="pw-right">{{ $qtyLabel }}</th>
                                        <th class="pw-right pw-hide-sm">Rate</th>
                                        <th class="pw-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lines as $l)
                                        <tr>
                                            @if ($module !== 'daily')
                                                <td style="white-space:nowrap">
                                                    @if ($l->work_date)
                                                        @php $workDate = \Carbon\Carbon::parse($l->work_date)->locale('id'); @endphp
                                                        <div class="pw-daily-day">{{ $workDate->translatedFormat('l') }}</div>
                                                        <div class="pw-daily-date">{{ $workDate->format('d/m/Y') }}</div>
                                                    @else
                                                        <span class="pw-daily-date">-</span>
                                                    @endif
                                                </td>
                                            @endif
                                            <td style="font-weight:700">{{ $l->employee?->name ?? '-' }}</td>
                                            <td>{{ $l->category?->code ?? '-' }}</td>
                                            <td>{{ $l->item?->code ?? '-' }}</td>
                                            <td class="pw-right">
                                                {{ rtrim(rtrim(number_format((float) $l->total_qty_ok, 2, '.', ''), '0'), '.') }}
                                            </td>
                                            <td class="pw-right pw-hide-sm">
                                                {{ number_format((float) $l->rate_per_pcs, 0, ',', '.') }}</td>
                                            <td class="pw-right" style="font-weight:800">
                                                {{ number_format((float) $l->amount, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $module !== 'daily' ? 7 : 6 }}" style="padding:1rem;color:var(--muted)">Tidak ada lines.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@if ($module === 'daily')
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const filter = document.getElementById('pw-daily-day-filter');
                const employeeFilter = document.getElementById('pw-daily-employee-filter');
                const count = document.getElementById('pw-daily-filter-count');
                const rows = Array.from(document.querySelectorAll('.pw-daily-table tbody tr[data-pw-day]'));
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const periodDays = {{ (int) $periodDays }};

                if (!filter || !employeeFilter) return;

                const formatAmount = function (amount) {
                    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(amount || 0));
                };

                const saveAttendance = async function (form, select) {
                    const row = form.closest('tr');
                    const state = form.querySelector('.pw-daily-save-state');
                    const previousValue = select.dataset.previousValue || select.value;
                    const formData = new FormData(form);

                    select.disabled = true;
                    if (state) {
                        state.textContent = 'Menyimpan...';
                        state.classList.remove('is-saved', 'is-error');
                    }

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: formData,
                        });
                        const payload = await response.json().catch(function () { return {}; });

                        if (!response.ok) {
                            throw new Error(payload.message || 'Status kehadiran gagal disimpan.');
                        }

                        select.dataset.previousValue = select.value;
                        if (row && payload.line) {
                            const amountCell = row.querySelector('[data-pw-daily-amount]');
                            if (amountCell) amountCell.textContent = formatAmount(payload.line.amount);
                        }

                        const grandTotal = document.querySelector('[data-pw-daily-grand-total]');
                        const average = document.querySelector('[data-pw-daily-average]');
                        if (grandTotal) grandTotal.textContent = formatAmount(payload.period_total_amount);
                        if (average) average.textContent = formatAmount(Number(payload.period_total_amount || 0) / periodDays);

                        if (state) {
                            state.textContent = 'Tersimpan';
                            state.classList.add('is-saved');
                        }
                    } catch (error) {
                        select.value = previousValue;
                        if (state) {
                            state.textContent = error.message || 'Gagal menyimpan';
                            state.classList.add('is-error');
                        }
                    } finally {
                        select.disabled = false;
                    }
                };

                document.querySelectorAll('.pw-daily-status-form').forEach(function (form) {
                    const select = form.querySelector('.pw-daily-status');
                    if (!select) return;

                    select.dataset.previousValue = select.value;
                    select.addEventListener('change', function () {
                        saveAttendance(form, select);
                    });
                });

                const updateRows = function () {
                    const selectedDay = filter.value;
                    const selectedEmployee = employeeFilter.value;
                    let visibleRows = 0;

                    rows.forEach(function (row) {
                        const visibleDay = selectedDay === 'all' || row.dataset.pwDay === selectedDay;
                        const visibleEmployee = selectedEmployee === 'all' || row.dataset.pwEmployee === selectedEmployee;
                        const visible = visibleDay && visibleEmployee;
                        row.hidden = !visible;
                        if (visible) visibleRows += 1;
                    });

                    if (count) {
                        count.textContent = visibleRows + ' baris';
                    }
                };

                filter.addEventListener('change', updateRows);
                employeeFilter.addEventListener('change', updateRows);
                updateRows();
            });
        </script>
    @endpush
@endif
