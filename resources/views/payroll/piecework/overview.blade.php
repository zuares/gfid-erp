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
            display: flex;
            gap: .75rem;
            align-items: flex-start;
            justify-content: space-between;
            margin: .25rem 0 .75rem
        }

        .pw-title {
            margin: 0;
            font-size: 1.08rem;
            font-weight: 900;
            letter-spacing: -.02em
        }

        .pw-sub {
            margin: .2rem 0 0;
            color: var(--muted);
            font-size: .86rem
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
            overflow-x: auto
        }

        .pw-table {
            width: 100%;
            min-width: 760px;
            border-collapse: separate;
            border-spacing: 0
        }

        .pw-table th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            text-align: left;
            padding: .58rem .65rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18)
        }

        .pw-table td {
            padding: .65rem;
            border-bottom: 1px solid rgba(148, 163, 184, .12);
            vertical-align: middle
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
            border-color: rgba(245, 158, 11, .35);
            color: rgb(217, 119, 6)
        }

        .pw-generate {
            margin-top: .75rem
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
            grid-template-columns: 1.1fr 1fr 1fr auto;
            gap: .55rem;
            align-items: end
        }

        .pw-field label {
            display: block;
            margin-bottom: .3rem;
            color: var(--muted);
            font-size: .74rem
        }

        .pw-range-field {
            grid-column: span 2
        }

        @media (max-width: 800px) {
            .pw-generate-form {
                grid-template-columns: 1fr 1fr
            }

            .pw-generate-form .pw-submit {
                grid-column: 1 / -1
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
                grid-template-columns: 1fr
            }

            .pw-range-field {
                grid-column: auto
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
        ];
        $activeModule = $module ?? 'all';
        $tabUrl = fn(string $tab) => route('payroll.piecework.overview', array_filter([
            'module' => $tab === 'all' ? null : $tab,
            'from' => request('from'),
            'to' => request('to'),
        ], fn($value) => $value !== null && $value !== ''));
        $defaultEnd = now()->toDateString();
        $defaultStart = now()->subDays(6)->toDateString();
    @endphp

    <div class="pw-overview-wrap">
        <div class="pw-overview-top">
            <div>
                <h1 class="pw-title">Payroll Borongan</h1>
                <div class="pw-sub">Satu daftar periode untuk Cutting dan Sewing. Detail, finalisasi, dan pembayaran tetap mengikuti modul masing-masing.</div>
            </div>

            <div class="pw-tabs" aria-label="Filter modul payroll">
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

        <div class="pw-card">
            <div class="pw-card-h">
                <div>
                    <strong>Daftar Periode</strong>
                    <div class="pw-sub">{{ $periods->total() }} periode · filter aktif: {{ $moduleLabels[$activeModule] }}</div>
                </div>

                <form class="pw-row" method="GET" action="{{ route('payroll.piecework.overview') }}" id="pw-filter-form">
                    @if ($activeModule !== 'all')
                        <input type="hidden" name="module" value="{{ $activeModule }}">
                    @endif
                    <input type="hidden" name="from" id="pw-filter-from" value="{{ request('from') }}" data-gf-date="off">
                    <input type="hidden" name="to" id="pw-filter-to" value="{{ request('to') }}" data-gf-date="off">
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
                            <th>Modul</th>
                            <th>Periode</th>
                            <th>Basis</th>
                            <th class="pw-right">Operator</th>
                            <th class="pw-right">Qty</th>
                            <th class="pw-right">Total Amount</th>
                            <th>Status</th>
                            <th class="pw-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($periods as $period)
                            @php
                                $periodModule = strtolower($period->module);
                                $isSewing = $periodModule === 'sewing';
                                $totalQty = (float) ($period->lines_total_qty ?? 0);
                                $totalAmount = (float) ($period->lines_total_amount ?? $period->total_amount ?? 0);
                            @endphp
                            <tr>
                                <td>
                                    <span class="pw-chip {{ $periodModule }}">{{ $moduleLabels[$periodModule] ?? ucfirst($periodModule) }}</span>
                                </td>
                                <td>
                                    <div style="font-weight:800">{{ id_date($period->period_start) }} – {{ id_date($period->period_end) }}</div>
                                    <div class="pw-sub">ID #{{ $period->id }}</div>
                                </td>
                                <td>{{ $isSewing ? 'Ambil Jahit' : 'Qty PCS / QC OK' }}</td>
                                <td class="pw-right">{{ number_format((int) ($period->operator_count ?? 0), 0, ',', '.') }}</td>
                                <td class="pw-right">{{ rtrim(rtrim(number_format($totalQty, 2, '.', ''), '0'), '.') }}</td>
                                <td class="pw-right" style="font-weight:800">{{ number_format($totalAmount, 0, ',', '.') }}</td>
                                <td>
                                    @if ($period->status === 'final')
                                        <span class="pw-chip final">FINAL</span>
                                    @else
                                        <span class="pw-chip draft">DRAFT</span>
                                    @endif
                                    @if ($period->paid_at)
                                        <div class="pw-sub">Sudah dibayar</div>
                                    @endif
                                </td>
                                <td class="pw-right">
                                    <a class="pw-btn" href="{{ route('payroll.piecework.show', ['module' => $periodModule, 'period' => $period]) }}">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="padding:1.1rem;color:var(--muted)">Belum ada periode payroll.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pw-card-b">
                {{ $periods->links() }}
            </div>
        </div>

        <details class="pw-card pw-generate" {{ isset($errors) && $errors->any() ? 'open' : '' }}>
            <summary class="pw-card-h">
                <span><strong>＋ Generate Payroll</strong><span class="pw-sub" style="display:inline;margin-left:.4rem">Pilih modul dan rentang tanggal</span></span>
                <span class="pw-chip">Draft</span>
            </summary>
            <div class="pw-card-b">
                @if (isset($errors) && $errors->any())
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="pw-generate-form" method="POST" action="{{ route('payroll.piecework.store_overview') }}">
                    @csrf
                    <div class="pw-field">
                        <label for="overview-module">Modul Payroll</label>
                        <select class="pw-select w-100" id="overview-module" name="module" required>
                            <option value="">Pilih modul...</option>
                            <option value="cutting" @selected(old('module') === 'cutting')>Cutting</option>
                            <option value="sewing" @selected(old('module') === 'sewing')>Sewing</option>
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
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.flatpickr !== 'function') return;

            const localeId = window.flatpickr.l10ns && window.flatpickr.l10ns.id
                ? window.flatpickr.l10ns.id
                : 'default';

            function parse(value) {
                return value ? window.flatpickr.parseDate(value, 'Y-m-d') : null;
            }

            function formatRange(dates) {
                if (!dates.length) return '';

                const format = date => window.flatpickr.formatDate(date, 'j M Y');
                if (dates.length === 1) return format(dates[0]);

                return format(dates[0]) + ' – ' + format(dates[1]);
            }

            function initRange(inputId, fromId, toId) {
                const input = document.getElementById(inputId);
                const from = document.getElementById(fromId);
                const to = document.getElementById(toId);

                if (!input || !from || !to) return;

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
                    },
                });
            }

            initRange('pw-filter-range', 'pw-filter-from', 'pw-filter-to');
            initRange('overview-period-range', 'overview-period-start', 'overview-period-end');
        });
    </script>
@endpush
