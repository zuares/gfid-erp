@extends('layouts.app')

@section('title', 'Ledger • ' . $account->code . ' ' . $account->name)

@push('head')
    <style>
        .lg-wrap {
            max-width: 1180px;
            margin: 0 auto;
            padding: clamp(.75rem, 2vw, 1.5rem) clamp(.75rem, 2vw, 1.25rem) 3rem
        }

        .lg-top {
            position: sticky;
            top: var(--app-navbar-offset, 58px);
            z-index: 30;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: .75rem;
            row-gap: .75rem;
            align-items: center;
            justify-content: space-between;
            margin: .1rem 0 .85rem;
            padding-top: .65rem;
            padding-bottom: .8rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            background: color-mix(in srgb, var(--bg) 92%, transparent);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px)
        }

        .lg-heading {
            min-width: 0
        }

        .lg-kicker {
            margin-bottom: .25rem;
            color: var(--muted);
            font-size: .64rem;
            font-weight: 850;
            letter-spacing: .1em;
            text-transform: uppercase
        }

        .lg-title-line {
            display: flex;
            align-items: center;
            gap: .45rem;
            flex-wrap: wrap;
            margin-top: 0
        }

        .lg-header-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            flex: 0 0 auto;
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 9px;
            color: var(--muted);
            text-decoration: none;
            transition: .18s ease
        }

        .lg-header-back:hover {
            border-color: rgba(100, 116, 139, .45);
            color: var(--text);
            background: rgba(148, 163, 184, .08)
        }

        .lg-title {
            margin: 0;
            font-size: clamp(1.08rem, 2vw, 1.24rem);
            font-weight: 900;
            letter-spacing: -.02em
        }

        .lg-account-code {
            display: inline-flex;
            align-items: center;
            min-height: 1.4rem;
            padding: .1rem .42rem;
            border: 1px solid rgba(148, 163, 184, .3);
            border-radius: 7px;
            color: var(--muted);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: .72rem;
            font-weight: 800
        }

        .lg-sub {
            display: flex;
            align-items: center;
            gap: .35rem;
            flex-wrap: wrap;
            margin: .32rem 0 0;
            color: var(--muted);
            font-size: .76rem
        }

        .lg-status {
            display: inline-flex;
            align-items: center;
            gap: .28rem;
            font-weight: 750
        }

        .lg-status::before {
            width: .42rem;
            height: .42rem;
            border-radius: 50%;
            background: #22c55e;
            content: ''
        }

        .lg-status.inactive::before {
            background: #94a3b8
        }

        .lg-actions {
            display: flex;
            align-items: center;
            gap: .35rem;
            flex-wrap: wrap;
            justify-content: flex-end
        }

        .lg-actions .lg-btn {
            min-height: 36px;
            padding: .38rem .62rem;
            border-radius: 10px;
            font-size: .8rem
        }

        .lg-actions .lg-filter-form {
            display: flex;
            align-items: flex-end;
            gap: .35rem;
            flex-wrap: nowrap;
            width: auto
        }

        .lg-actions .lg-period-field {
            width: 14.5rem
        }

        .lg-actions .lg-filter-form .lg-btn {
            flex: 0 0 auto;
            width: auto;
            white-space: nowrap
        }

        .lg-period {
            color: color-mix(in srgb, var(--muted) 82%, var(--text) 18%)
        }

        .lg-card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 16px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06);
            overflow: hidden
        }

        .lg-summary-strip {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0;
            padding: .85rem .9rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18)
        }

        .lg-metric {
            display: flex;
            flex-direction: column;
            gap: .18rem;
            min-width: 0
        }

        .lg-metric + .lg-metric {
            padding-left: 1rem;
            border-left: 1px solid rgba(148, 163, 184, .18)
        }

        .lg-metric-label {
            color: var(--muted);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase
        }

        .lg-metric-value {
            color: #111827;
            font-size: 1.1rem;
            font-variant-numeric: tabular-nums;
            font-weight: 900;
            letter-spacing: -.02em
        }

        [data-theme="dark"] .lg-metric-value {
            color: #f8fafc
        }

        .lg-filterbar {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            padding: .75rem .9rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            background: color-mix(in srgb, var(--muted) 3%, transparent)
        }

        .lg-filter-title {
            font-size: .82rem;
            font-weight: 850
        }

        .lg-filter-form {
            display: flex;
            align-items: flex-end;
            gap: .5rem;
            flex-wrap: wrap;
            justify-content: flex-end;
            width: 100%
        }

        .lg-field {
            display: flex;
            flex-direction: column;
            gap: .2rem;
            min-width: 8.75rem
        }

        .lg-period-field {
            width: 14.5rem;
            min-width: 0;
            max-width: 100%
        }

        .lg-actions .lg-period-field .lg-in {
            padding: .38rem .52rem;
            font-size: .78rem
        }

        .lg-period-hidden {
            display: none !important
        }

        .lg-field span {
            color: var(--muted);
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase
        }

        .lg-table-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .7rem .9rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18)
        }

        .lg-table-toolbar > div {
            display: flex;
            align-items: baseline;
            gap: .5rem;
            min-width: 0
        }

        .lg-table-toolbar strong {
            font-size: .82rem
        }

        .lg-b {
            padding: .9rem
        }

        .lg-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            min-height: 40px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: transparent;
            color: var(--text);
            padding: .48rem .72rem;
            border-radius: 12px;
            text-decoration: none;
            font-size: .88rem
        }

        .lg-btn.primary {
            border-color: color-mix(in srgb, var(--accent) 52%, rgba(148, 163, 184, .35));
            background: color-mix(in srgb, var(--accent) 10%, var(--card) 90%)
        }

        .lg-btn:hover {
            border-color: color-mix(in srgb, var(--accent) 52%, rgba(148, 163, 184, .35));
            color: var(--accent);
        }

        .lg-btn.primary:hover {
            background: color-mix(in srgb, var(--accent) 16%, var(--card) 84%)
        }

        .lg-quick-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .75rem;
            padding: .9rem 1rem;
            border: 1px solid color-mix(in srgb, var(--accent) 18%, rgba(148, 163, 184, .26));
            border-radius: 16px;
            background: linear-gradient(135deg, color-mix(in srgb, var(--accent-soft) 42%, var(--card) 58%), var(--card));
            box-shadow: 0 8px 22px rgba(15, 23, 42, .045)
        }

        .lg-quick-copy {
            display: flex;
            align-items: flex-start;
            gap: .65rem;
            min-width: 0
        }

        .lg-quick-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 2.1rem;
            width: 2.1rem;
            height: 2.1rem;
            border-radius: 11px;
            background: color-mix(in srgb, var(--accent) 13%, var(--card) 87%);
            color: var(--accent);
            font-size: 1rem
        }

        .lg-quick-title {
            font-size: .86rem;
            font-weight: 900
        }

        .lg-quick-actions {
            display: flex;
            flex: 0 0 auto;
            gap: .5rem;
            flex-wrap: wrap;
            justify-content: flex-end
        }

        .lg-quick-btn {
            white-space: nowrap
        }

        .lg-quick-btn.expense {
            border-color: rgba(239, 68, 68, .28);
            background: rgba(239, 68, 68, .07)
        }

        .lg-quick-btn.expense:hover {
            color: #dc2626;
            background: rgba(239, 68, 68, .12)
        }

        .lg-quick-btn.receipt {
            border-color: rgba(16, 185, 129, .3);
            background: rgba(16, 185, 129, .08)
        }

        .lg-quick-btn.receipt:hover {
            color: #047857;
            background: rgba(16, 185, 129, .14)
        }

        .lg-in {
            border: 1px solid rgba(148, 163, 184, .28);
            background: transparent;
            color: var(--text);
            border-radius: 12px;
            padding: .46rem .6rem;
            font-size: .88rem
        }

        .lg-row {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center
        }

        .lg-bal {
            font-weight: 700;
            letter-spacing: -.01em
        }

        .lg-bal.pos {
            color: rgba(16, 185, 129, 1)
        }

        .lg-bal.neg {
            color: rgba(239, 68, 68, 1)
        }

        .lg-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0
        }

        .lg-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch
        }

        .lg-table th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            text-align: left;
            padding: .55rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18)
        }

        .lg-table td {
            padding: .6rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, .12);
            vertical-align: top
        }

        .lg-right {
            text-align: right;
            white-space: nowrap
        }

        .lg-amount {
            display: inline-block;
            min-width: 5.6rem;
            padding: .18rem .42rem;
            border: 1px solid transparent;
            border-radius: 8px;
            font-variant-numeric: tabular-nums;
            font-weight: 750;
            text-align: right
        }

        .lg-amount.debit {
            color: #15803d;
            background: rgba(34, 197, 94, .10);
            border-color: rgba(34, 197, 94, .23)
        }

        .lg-amount.credit {
            color: #dc2626;
            background: rgba(239, 68, 68, .10);
            border-color: rgba(239, 68, 68, .23)
        }

        [data-theme="dark"] .lg-amount.debit {
            color: #86efac;
            background: rgba(34, 197, 94, .16)
        }

        [data-theme="dark"] .lg-amount.credit {
            color: #fca5a5;
            background: rgba(239, 68, 68, .16)
        }

        .lg-bal,
        .lg-bal.pos,
        .lg-bal.neg {
            color: #111827
        }

        [data-theme="dark"] .lg-bal,
        [data-theme="dark"] .lg-bal.pos,
        [data-theme="dark"] .lg-bal.neg {
            color: #f8fafc
        }

        .lg-muted {
            color: var(--muted);
            font-size: .82rem
        }

        .lg-click-row {
            cursor: pointer
        }

        .lg-click-row:hover {
            background: color-mix(in srgb, var(--accent-soft) 12%, transparent)
        }

        .lg-row-link {
            color: inherit;
            text-decoration: none
        }

        .lg-row-link:hover {
            color: var(--accent);
            text-decoration: underline
        }

        .lg-ref {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin-top: .18rem;
            color: var(--muted);
            font-size: .72rem
        }

        .lg-ref-code {
            display: inline-flex;
            align-items: center;
            padding: .08rem .35rem;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 6px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: .68rem
        }

        .lg-line-id {
            color: var(--muted)
        }

        .lg-created-by {
            display: flex;
            align-items: center;
            gap: .25rem;
            margin-top: .2rem;
            color: var(--muted);
            font-size: .7rem;
            line-height: 1.2
        }

        .lg-created-by i {
            font-size: .68rem;
            opacity: .78
        }

        .lg-created-by-role {
            color: color-mix(in srgb, var(--muted) 78%, var(--text) 22%)
        }

        .lg-transaction-title {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap
        }

        .lg-grn-items {
            margin-top: .42rem;
            padding: .42rem .55rem;
            border-left: 3px solid color-mix(in srgb, var(--accent) 45%, transparent);
            background: color-mix(in srgb, var(--accent-soft) 9%, transparent);
            border-radius: 0 8px 8px 0;
            font-size: .8rem
        }

        .lg-grn-items-title {
            color: var(--muted);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: .15rem
        }

        .lg-grn-items ul {
            margin: 0;
            padding-left: 1.05rem
        }

        .lg-grn-items li {
            padding: .08rem 0
        }

        @media (max-width: 720px) {
            .lg-hide-sm {
                display: none
            }

            .lg-top {
                grid-template-columns: 1fr;
                align-items: stretch;
                gap: .65rem;
                padding-bottom: .7rem
            }

            .lg-top .lg-actions {
                width: 100%
            }

            .lg-actions .lg-btn {
                flex: 1 1 0;
                justify-content: center
            }

            .lg-actions .lg-filter-form {
                order: 3;
                flex: 1 1 100%;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                width: 100%;
                align-items: end
            }

            .lg-actions .lg-period-field {
                width: auto;
                min-width: 0
            }

            .lg-actions .lg-filter-form .lg-btn {
                justify-content: center
            }

            .lg-quick-card {
                align-items: stretch;
                flex-direction: column;
                gap: .75rem;
                padding: .8rem
            }

            .lg-quick-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                width: 100%
            }

            .lg-quick-btn {
                width: 100%;
                min-height: 44px
            }

            .lg-card {
                border-radius: 13px
            }

            .lg-title {
                font-size: 1.05rem
            }

            .lg-summary-strip {
                padding: .75rem
            }

            .lg-metric + .lg-metric {
                padding-left: .75rem
            }

            .lg-filterbar {
                display: block;
                padding: .75rem
            }

            .lg-filter-form {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: .5rem;
                margin-top: 0;
                width: 100%
            }

            .lg-top > .lg-filterbar .lg-filter-form {
                width: 100%
            }

            .lg-filter-form .lg-field {
                min-width: 0
            }

            .lg-filter-form .lg-period-field {
                grid-column: 1;
                width: 100%
            }

            .lg-filter-form .lg-in,
            .lg-filter-form .lg-btn {
                width: 100%;
                min-width: 0
            }

            .lg-filter-form button[type="submit"],
            .lg-filter-form a.lg-btn {
                grid-column: 2;
                width: auto;
                min-width: 0;
                white-space: nowrap
            }

            .lg-actions .lg-filter-form .lg-period-field {
                grid-column: 1;
                width: auto
            }

            .lg-actions .lg-filter-form a.lg-btn {
                grid-column: 2;
                width: auto
            }

            .lg-table-toolbar {
                align-items: flex-start;
                padding: .65rem .75rem
            }

            .lg-table-toolbar > div {
                flex-direction: column;
                gap: .08rem
            }

            .lg-b {
                padding: 0
            }

            .lg-table,
            .lg-table tbody {
                display: block
            }

            .lg-table thead {
                display: none
            }

            .lg-table tbody {
                padding: .65rem
            }

            .lg-table tbody tr {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: .5rem .6rem;
                margin-bottom: .65rem;
                padding: .7rem .75rem;
                border: 1px solid rgba(148, 163, 184, .22);
                border-radius: 12px;
                background: color-mix(in srgb, var(--card) 94%, var(--bg) 6%);
                box-shadow: 0 4px 12px rgba(15, 23, 42, .045)
            }

            .lg-table tbody tr:last-child {
                margin-bottom: 0
            }

            .lg-table tbody tr td {
                display: block;
                min-width: 0;
                padding: 0;
                border: 0
            }

            .lg-table tbody tr td:nth-child(1),
            .lg-table tbody tr td:nth-child(2) {
                grid-column: 1 / -1
            }

            .lg-table tbody tr td:nth-child(1) {
                order: 1;
                grid-column: 1
            }

            .lg-table tbody tr td:nth-child(5) {
                order: 2;
                grid-column: 2;
                justify-self: end;
                width: max-content;
                max-width: 100%;
                padding-left: 0;
                border-left: 0;
                text-align: right
            }

            .lg-table tbody tr td:nth-child(5)::before {
                margin-bottom: .1rem;
                font-size: .62rem;
                font-weight: 700;
                text-align: right
            }

            .lg-table tbody tr td:nth-child(1) {
                font-size: .8rem;
                color: var(--text)
            }

            .lg-datetime {
                display: flex;
                align-items: baseline;
                gap: .35rem;
                white-space: nowrap
            }

            .lg-line-id {
                display: none
            }

            .lg-date {
                display: block;
                font-weight: 800;
                font-variant-numeric: tabular-nums
            }

            .lg-posted-time {
                display: inline;
                margin-top: 0;
                color: var(--muted);
                font-size: .76rem;
                font-weight: 800;
                font-variant-numeric: tabular-nums
            }

            .lg-table tbody tr td:nth-child(2) {
                order: 3;
                padding-bottom: .15rem;
                border-bottom: 1px solid rgba(148, 163, 184, .16)
            }

            .lg-table tbody tr td:nth-child(3)::before,
            .lg-table tbody tr td:nth-child(4)::before,
            .lg-table tbody tr td:nth-child(5)::before {
                display: block;
                margin-bottom: .18rem;
                color: var(--muted);
                font-size: .67rem;
                font-weight: 800;
                letter-spacing: .06em;
                text-align: left;
                text-transform: uppercase
            }

            .lg-table tbody tr td:nth-child(3)::before {
                content: 'Debit'
            }

            .lg-table tbody tr td:nth-child(4)::before {
                content: 'Credit'
            }

            .lg-table tbody tr td:nth-child(5)::before {
                display: none;
                content: none
            }

            .lg-table tbody tr td:nth-child(3),
            .lg-table tbody tr td:nth-child(4),
            .lg-table tbody tr td:nth-child(5) {
                text-align: left
            }

            /* Mobile: tanggal dan saldo berada di baris informasi utama. */
            .lg-table tbody tr td:nth-child(3) {
                order: 4;
                grid-column: 1
            }

            .lg-table tbody tr td:nth-child(4) {
                order: 5;
                grid-column: 2
            }

            .lg-amount {
                display: block;
                min-width: 0;
                width: 100%;
                text-align: right
            }

            .lg-table tbody tr td:nth-child(5) .lg-bal {
                display: block;
                text-align: right;
                font-size: .82rem;
                font-weight: 600;
                letter-spacing: 0;
                opacity: .66
            }

            .lg-table tbody tr td[colspan] {
                grid-column: 1 / -1
            }

            .lg-table + div {
                padding: .65rem .75rem
            }
        }
    </style>
@endpush

@section('content')
    <div class="lg-wrap">
        <div class="lg-top">
            <div class="lg-heading">
                <div class="lg-kicker">Buku besar</div>
                <div class="lg-title-line">
                    <a class="lg-header-back" href="{{ route('accounting.accounts.index') }}" title="Kembali ke akun" aria-label="Kembali ke akun">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    </a>
                    <h1 class="lg-title">{{ $account->name }}</h1>
                    <span class="lg-account-code">{{ $account->code }}</span>
                </div>
                <div class="lg-sub">
                    <span class="lg-status {{ $account->is_active ? '' : 'inactive' }}">
                        {{ $account->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                    <span>· {{ ucfirst($account->type) }}</span>
                    @if ($account->is_cash)
                        <span>· Kas/Bank</span>
                    @endif
                    @if ($cutoffDate && !$showLegacy)
                        <span class="lg-period">· Mulai {{ \Carbon\Carbon::parse($cutoffDate)->format('d/m/Y') }}</span>
                    @elseif ($cutoffDate)
                        <span class="lg-period">· Semua histori</span>
                    @endif
                </div>
            </div>

            <div class="lg-actions">
                <a class="lg-btn" href="{{ route('accounting.accounts.edit', $account) }}"><i class="bi bi-pencil" aria-hidden="true"></i><span>Edit</span></a>
                @if ($account->code === '2104' && Route::has('accounting.employee-savings.create'))
                    <a class="lg-btn primary" href="{{ route('accounting.employee-savings.create') }}">Bayar tabungan</a>
                @endif
                <a class="lg-btn primary" href="{{ route('accounting.journals.index') }}"><i class="bi bi-journal-text" aria-hidden="true"></i><span>Jurnal</span></a>
                @if ($account->is_cash && $account->is_active)
                    <a class="lg-btn lg-quick-btn expense" href="{{ route('accounting.cash-expenses.create', ['cash_account_id' => $account->id]) }}">
                        <i class="bi bi-arrow-down-circle" aria-hidden="true"></i><span>Pengeluaran</span>
                    </a>
                    <a class="lg-btn lg-quick-btn receipt" href="{{ route('accounting.cash-receipts.create', ['cash_account_id' => $account->id]) }}">
                        <i class="bi bi-arrow-up-circle" aria-hidden="true"></i><span>Penerimaan</span>
                    </a>
                @endif
                <form class="lg-filter-form" method="GET" action="{{ route('accounting.accounts.ledger', $account) }}">
                    <label class="lg-field lg-period-field">
                        <input class="lg-in" type="text" id="lg-period-picker"
                            placeholder="Pilih periode" autocomplete="off">
                        <input type="hidden" class="lg-period-hidden" name="from" id="lg-period-from" value="{{ $from ?? '' }}">
                        <input type="hidden" class="lg-period-hidden" name="to" id="lg-period-to" value="{{ $to ?? '' }}">
                    </label>
                    @if ($showLegacy)
                        <input type="hidden" name="show_legacy" value="1">
                    @endif
                    @if (request()->filled('from') || request()->filled('to') || $showLegacy)
                        <a class="lg-btn" href="{{ route('accounting.accounts.ledger', $account) }}">Reset</a>
                    @endif
                </form>
            </div>
        </div>

        {{-- Summary --}}
        <div class="lg-card" style="margin-bottom:.75rem">
            <div class="lg-summary-strip">
                <div class="lg-metric">
                    <div class="lg-metric-label">Saldo awal</div>
                    @php $ob = (float)$openingBalance; @endphp
                    <div class="lg-metric-value">{{ number_format($ob, 0, ',', '.') }}</div>
                </div>

                <div class="lg-metric">
                    <div class="lg-metric-label">Saldo saat ini</div>
                    @php $cb = (float)$currentBalance; @endphp
                    <div class="lg-metric-value">Rp {{ number_format($cb, 0, ',', '.') }}</div>
                </div>
            </div>

            <div class="lg-b" style="padding:0">
                <div class="lg-table-toolbar">
                    <div>
                        <strong>Transaksi</strong>
                        <span class="lg-muted">{{ number_format($lines->total(), 0, ',', '.') }} entri</span>
                    </div>
                </div>
                <div class="lg-table-wrap">
                <table class="lg-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Transaksi</th>
                            <th class="lg-right">Debit</th>
                            <th class="lg-right">Credit</th>
                            <th class="lg-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lines as $l)
                            @php
                                $d = (float) ($l->debit ?? 0);
                                $c = (float) ($l->credit ?? 0);
                                $rowBalance = (float) $closingBalance
                                    - (float) ($l->cumulative_net ?? 0)
                                    + ($d - $c);
                                $relatedUrl = $l->related_url ?? route('accounting.journals.show', $l->journal_id);
                                $relatedLabel = $l->related_label ?? 'Buka detail jurnal';
                                $sourceLabels = [
                                    'purchase_payment' => 'Pembayaran PO',
                                    'cash_expense' => 'Pengeluaran',
                                    'cash_receipt' => 'Penerimaan',
                                    'opening_balance' => 'Saldo awal',
                                    'opening_balance_batch' => 'Saldo awal',
                                    'grn' => 'Penerimaan barang',
                                    'grn_inv' => 'Penerimaan barang',
                                    'grn_exp' => 'Penerimaan barang',
                                ];
                                $ref = ($sourceLabels[$l->source_type] ?? 'Jurnal')
                                    . ($l->source_id ? ' #' . $l->source_id : '');
                                $fullDescription = trim((string) ($l->grn_supplier_name ?: ($l->journal_description ?? '-')));
                                $fullDescription = preg_replace('/\s+via\s+.+$/i', '', $fullDescription) ?: '-';
                                $description = \Illuminate\Support\Str::limit($fullDescription, 72);
                                $creatorName = trim((string) ($l->creator_name ?? '')) ?: 'Sistem';
                                $creatorRole = strtolower(trim((string) ($l->creator_role ?? '')));
                                $creatorRoleLabels = [
                                    'owner' => 'Owner',
                                    'admin' => 'Admin',
                                    'accounting' => 'Accounting',
                                    'operating' => 'Operasional',
                                    'nta' => 'NTA',
                                ];
                                $creatorRoleLabel = $creatorRoleLabels[$creatorRole] ?? ($creatorRole ? ucfirst($creatorRole) : null);
                                $postedAt = $l->posted_at
                                    ? \Carbon\Carbon::parse($l->posted_at)->format('H:i')
                                    : null;
                            @endphp
                            <tr class="lg-click-row" data-href="{{ $relatedUrl }}" tabindex="0" role="link"
                                aria-label="{{ $relatedLabel }}">
                                <td>
                                    <div class="lg-datetime">
                                        <span class="lg-date">{{ \Carbon\Carbon::parse($l->date)->locale('id')->translatedFormat('l, d/m/Y') }}</span>
                                    @if ($postedAt)
                                            <span class="lg-posted-time">{{ $postedAt }}</span>
                                    @endif
                                    </div>
                                    <div class="lg-created-by" title="Diinput oleh {{ $creatorName }}{{ $creatorRoleLabel ? ' · ' . $creatorRoleLabel : '' }}">
                                        <i class="bi bi-person" aria-hidden="true"></i>
                                        <span>Diinput oleh {{ $creatorName }}</span>
                                        @if ($creatorRoleLabel)
                                            <span class="lg-created-by-role">· {{ $creatorRoleLabel }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <a class="lg-row-link" href="{{ $relatedUrl }}" tabindex="-1" title="{{ $fullDescription }}">
                                        <div class="lg-transaction-title" style="font-weight:700">{{ $description }}</div>
                                    </a>
                                    <div class="lg-ref">
                                        <span class="lg-ref-code">{{ $ref }}</span>
                                        <span class="lg-line-id">Baris #{{ $l->id }}</span>
                                    </div>
                                    @if ($l->grn_items instanceof \Illuminate\Support\Collection && $l->grn_items->isNotEmpty())
                                        <div class="lg-grn-items">
                                            <div class="lg-grn-items-title">Item GRN</div>
                                            <ul>
                                                @foreach ($l->grn_items as $grnItem)
                                                    <li>
                                                        {{ $grnItem->item?->name ?? ('Item #' . $grnItem->item_id) }}
                                                        <span class="lg-muted">
                                                            • {{ decimal_id($grnItem->qty_received, 2) }}
                                                            {{ $grnItem->effectivePurchaseUnit() }}
                                                        </span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </td>
                                <td class="lg-right">
                                    @if ($d)
                                        <span class="lg-amount debit">{{ number_format($d, 0, ',', '.') }}</span>
                                    @else
                                        <span class="lg-muted">—</span>
                                    @endif
                                </td>
                                <td class="lg-right">
                                    @if ($c)
                                        <span class="lg-amount credit">{{ number_format($c, 0, ',', '.') }}</span>
                                    @else
                                        <span class="lg-muted">—</span>
                                    @endif
                                </td>
                                <td class="lg-right lg-bal {{ $rowBalance < 0 ? 'neg' : 'pos' }}">
                                    {{ number_format($rowBalance, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding:1rem;color:var(--muted)">Belum ada transaksi.</td>
                            </tr>
                        @endforelse

                        @if ($lines->count())
                            <tr>
                                <td class="lg-muted">
                                    {{ request('from') ? \Carbon\Carbon::parse(request('from'))->locale('id')->translatedFormat('l, d/m/Y') : '-' }}</td>
                                <td class="lg-muted">Saldo awal</td>
                                <td class="lg-right lg-muted">—</td>
                                <td class="lg-right lg-muted">—</td>
                                <td class="lg-right lg-bal {{ $openingBalance < 0 ? 'neg' : 'pos' }}">
                                    {{ number_format($openingBalance, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                </div>

                <div style="padding:.8rem .9rem">
                    {{ $lines->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const periodPicker = document.getElementById('lg-period-picker');
            const periodFrom = document.getElementById('lg-period-from');
            const periodTo = document.getElementById('lg-period-to');
            let periodReady = false;

            window.setTimeout(() => { periodReady = true; }, 0);

            if (periodPicker && periodFrom && periodTo && window.GFID?.initDateRange) {
                window.GFID.initDateRange(periodPicker, {
                    mode: 'range',
                    showMonths: 1,
                    altFormat: 'd M Y',
                    rangeSeparator: ' – ',
                    defaultDate: [periodFrom.value, periodTo.value].filter(Boolean),
                    onChange: (selectedDates) => {
                        periodFrom.value = selectedDates[0]
                            ? window.flatpickr.formatDate(selectedDates[0], 'Y-m-d')
                            : '';
                        periodTo.value = selectedDates[1]
                            ? window.flatpickr.formatDate(selectedDates[1], 'Y-m-d')
                            : '';

                        if (periodReady && selectedDates.length === 2) {
                            periodPicker.form?.requestSubmit();
                        }
                    }
                });
            }
        });

        document.querySelectorAll('.lg-click-row').forEach((row) => {
            row.addEventListener('click', (event) => {
                if (event.target.closest('a')) return;
                window.location.href = row.dataset.href;
            });

            row.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                event.preventDefault();
                window.location.href = row.dataset.href;
            });
        });
    </script>
@endsection
