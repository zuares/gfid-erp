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
            .pw-hide-sm {
                display: none
            }

            .pw-top {
                flex-direction: column;
                align-items: stretch
            }

            .pw-top > .pw-row {
                justify-content: stretch
            }

            .pw-top > .pw-row .pw-btn {
                flex: 1;
                justify-content: center
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
        }
    </style>
@endpush

@section('content')
    @php
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
                    @if ($period->status === 'final')
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
                        @if ($period->status !== 'final')
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
                            <span class="pw-chip final">FINAL LOCKED</span>
                        @endif
                    </div>

                    {{-- PAY (only if final & not paid) --}}
                    @if ($period->status === 'final' && !$period->paid_at)
                        <form class="pw-row" method="POST"
                            action="{{ $moduleRoute('pay', ['period' => $period]) }}">
                            @csrf
                            <select class="pw-sel" name="paid_from_account_id" required>
                                <option value="">Bayar dari...</option>
                                @foreach ($cashAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} • {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>

                            <button class="pw-btn success" type="submit"
                                onclick="return confirm('Catat pembayaran? Ini akan melunasi hutang payroll.')">
                                BAYAR
                            </button>
                        </form>
                        <div class="pw-sub" style="margin-top:.5rem">
                            Bayar akan mencatat: Dr Hutang Upah Borongan (2102) / Cr Kas/Bank.
                        </div>
                    @elseif($period->paid_at)
                        <div class="pw-sub">Pembayaran sudah dicatat.</div>
                    @endif

                    <hr style="border:none;border-top:1px solid rgba(148,163,184,.18);margin:1rem 0">

                    {{-- SUMMARY TABLE --}}
                    <div class="pw-table-wrap">
                        <table class="pw-table">
                            <thead>
                                <tr>
                                    <th>Operator</th>
                                    @if ($module === 'daily')
                                        <th class="pw-right">Hadir</th>
                                        <th class="pw-right">Libur</th>
                                        <th class="pw-right">Hari Efektif</th>
                                    @else
                                        <th class="pw-right">{{ $qtyLabel }}</th>
                                    @endif
                                    <th class="pw-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($summaryByEmployee as $s)
                                    <tr>
                                        <td>
                                            <div style="font-weight:800">{{ $s['employee_name'] }}</div>
                                            <div class="pw-row" style="margin-top:.3rem">
                                                <a class="pw-btn"
                                                    href="{{ $moduleRoute('slip', ['period' => $period, 'employee' => $s['employee_id']]) }}">
                                                    Slip
                                                </a>
                                            </div>
                                        </td>
                                        @if ($module === 'daily')
                                            <td class="pw-right">{{ number_format((int) ($s['present_count'] ?? 0), 0, ',', '.') }}</td>
                                            <td class="pw-right">{{ number_format((int) ($s['holiday_count'] ?? 0), 0, ',', '.') }}</td>
                                            <td class="pw-right">
                                                {{ rtrim(rtrim(number_format((float) $s['total_qty'], 2, '.', ''), '0'), '.') }}
                                            </td>
                                        @else
                                            <td class="pw-right">
                                                {{ rtrim(rtrim(number_format((float) $s['total_qty'], 2, '.', ''), '0'), '.') }}
                                            </td>
                                        @endif
                                        <td class="pw-right" style="font-weight:800">
                                            {{ number_format((float) $s['total_amount'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $module === 'daily' ? 5 : 3 }}" style="padding:1rem;color:var(--muted)">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

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
                            <div class="pw-summary-average-value">{{ number_format((float) $averageDailyAmount, 0, ',', '.') }}</div>
                        </div>
                    @endif

                    <div class="pw-summary-total">
                        <div>
                            <div class="pw-summary-total-label">Total Payroll</div>
                            <div class="pw-summary-total-note">{{ $module === 'daily' ? 'Setelah rekap kehadiran seluruh operator' : 'Total seluruh baris payroll' }}</div>
                        </div>
                        <div>
                            <div class="pw-summary-total-amount">{{ number_format((float) $grandTotalAmount, 0, ',', '.') }}</div>
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
                            <table class="pw-table pw-daily-table">
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
                                        <tr data-pw-day="{{ $l->work_date ? \Carbon\Carbon::parse($l->work_date)->dayOfWeek : '' }}">
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
                                                        <button class="pw-daily-save" type="submit">Simpan</button>
                                                    </form>
                                                @endif
                                            </td>
                                            <td data-label="Upah Harian" class="pw-right" style="white-space:nowrap">
                                                {{ number_format((float) ($l->rate_per_day ?: $l->rate_per_pcs), 0, ',', '.') }}
                                            </td>
                                            <td data-label="Total" class="pw-right" style="font-weight:800;white-space:nowrap">
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
                            <table class="pw-table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th class="pw-hide-sm">Category</th>
                                        <th>Item</th>
                                        <th class="pw-right">{{ $qtyLabel }}</th>
                                        <th class="pw-right pw-hide-sm">Rate</th>
                                        <th class="pw-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lines as $l)
                                        <tr>
                                            <td style="font-weight:700">{{ $l->employee?->name ?? '-' }}</td>
                                            <td class="pw-hide-sm">{{ $l->category?->name ?? '-' }}</td>
                                            <td>{{ $l->item?->name ?? '-' }}</td>
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
                                            <td colspan="6" style="padding:1rem;color:var(--muted)">Tidak ada lines.</td>
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
                const count = document.getElementById('pw-daily-filter-count');
                const rows = Array.from(document.querySelectorAll('.pw-daily-table tbody tr[data-pw-day]'));

                if (!filter) return;

                const updateRows = function () {
                    const selectedDay = filter.value;
                    let visibleRows = 0;

                    rows.forEach(function (row) {
                        const visible = selectedDay === 'all' || row.dataset.pwDay === selectedDay;
                        row.hidden = !visible;
                        if (visible) visibleRows += 1;
                    });

                    if (count) {
                        count.textContent = visibleRows + ' baris';
                    }
                };

                filter.addEventListener('change', updateRows);
                updateRows();
            });
        </script>
    @endpush
@endif
