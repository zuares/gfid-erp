@extends('layouts.app')

@section('title', 'Journals')

@push('head')
    <style>
        .jx-wrap {
            max-width: 1240px;
            margin: 0 auto;
            padding: .7rem .7rem 2.1rem
        }

        .jx-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
            margin-bottom: .55rem
        }

        .jx-title {
            font-size: 1.02rem;
            font-weight: 780;
            letter-spacing: -.02em;
            margin: 0
        }

        .jx-actions {
            display: flex;
            gap: .45rem;
            flex-wrap: wrap
        }

        .jx-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            min-height: 40px;
            padding: .42rem .7rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: color-mix(in srgb, var(--card) 88%, transparent 12%);
            text-decoration: none;
            color: var(--text);
            font-size: .84rem;
            white-space: nowrap
        }

        .jx-btn:hover {
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, .35)
        }

        .jx-filter-card {
            margin-bottom: .8rem;
            padding: .85rem;
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 14px;
            background: var(--card);
            box-shadow: 0 8px 22px rgba(15, 23, 42, .045)
        }

        .jx-filter-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .7rem;
            margin-bottom: .75rem
        }

        .jx-filter-title {
            display: flex;
            align-items: center;
            gap: .42rem;
            margin: 0;
            color: var(--text);
            font-size: .9rem;
            font-weight: 760
        }

        .jx-filter-title i {
            color: var(--accent, #2563eb)
        }

        .jx-filter-note {
            margin: .16rem 0 0;
            color: var(--muted);
            font-size: .76rem
        }

        .jx-cutoff-note {
            display: inline-flex;
            align-items: center;
            gap: .32rem;
            flex-shrink: 0;
            padding: .3rem .52rem;
            border-radius: 999px;
            color: color-mix(in srgb, var(--text) 68%, transparent 32%);
            background: color-mix(in srgb, var(--accent-soft) 38%, transparent 62%);
            font-size: .72rem;
            font-weight: 700
        }

        .jx-filter {
            display: grid;
            grid-template-columns: minmax(280px, 2fr) minmax(180px, 1.35fr) minmax(150px, 1fr) minmax(220px, 1.65fr);
            gap: .7rem;
            align-items: end
        }

        .jx-date-range {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .55rem
        }

        .jx-filter-field {
            display: grid;
            gap: .3rem;
            min-width: 0
        }

        .jx-filter-label {
            color: var(--muted);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase
        }

        .jx-in,
        .jx-sel {
            width: 100%;
            min-height: 42px;
            padding: .48rem .62rem;
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 12px;
            background: var(--card);
            color: var(--text);
            font-size: .88rem;
            box-shadow: none
        }

        .jx-in:focus,
        .jx-sel:focus {
            border-color: color-mix(in srgb, var(--accent, #2563eb) 62%, transparent 38%);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-soft) 62%, transparent 38%);
            outline: 0
        }

        .jx-filter-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .7rem;
            margin-top: .7rem;
            padding-top: .7rem;
            border-top: 1px solid rgba(148, 163, 184, .16)
        }

        .jx-filter-option {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            min-height: 38px;
            color: var(--muted);
            font-size: .78rem;
            cursor: pointer
        }

        .jx-filter-option input {
            width: 1rem;
            height: 1rem;
            accent-color: var(--accent, #2563eb)
        }

        .jx-filter-actions {
            display: flex;
            justify-content: flex-end;
            gap: .45rem
        }

        .jx-card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 14px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06)
        }

        .jx-table {
            width: 100%;
            border-collapse: collapse
        }

        .jx-table th,
        .jx-table td {
            padding: .56rem .6rem;
            vertical-align: middle
        }

        .jx-table thead th {
            font-size: .7rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: color-mix(in srgb, var(--text) 60%, transparent 40%);
            border-bottom: 1px solid rgba(148, 163, 184, .25);
            background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
            white-space: nowrap
        }

        .jx-table tbody tr {
            border-bottom: 1px solid rgba(148, 163, 184, .16)
        }

        .jx-table tbody tr:hover {
            background: color-mix(in srgb, var(--accent-soft) 10%, transparent 90%)
        }

        .jx-desc {
            display: flex;
            flex-direction: column;
            gap: .1rem;
            min-width: 210px
        }

        .jx-desc .t {
            font-weight: 650;
            line-height: 1.15
        }

        .jx-desc .m {
            font-size: .83rem;
            color: var(--muted);
            display: flex;
            gap: .35rem;
            flex-wrap: wrap;
            align-items: center
        }

        .jx-chip {
            display: inline-flex;
            align-items: center;
            padding: .14rem .46rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .30);
            font-size: .75rem;
            color: color-mix(in srgb, var(--text) 70%, transparent 30%);
            background: color-mix(in srgb, var(--card) 86%, transparent 14%)
        }

        .jx-chip.ok {
            border-color: rgba(34, 197, 94, .35);
            color: rgba(22, 163, 74, 1);
            background: rgba(34, 197, 94, .08)
        }

        .jx-chip.warn {
            border-color: rgba(245, 158, 11, .35);
            color: rgba(180, 83, 9, 1);
            background: rgba(245, 158, 11, .08)
        }

        .jx-chip.bad {
            border-color: rgba(239, 68, 68, .35);
            color: rgba(185, 28, 28, 1);
            background: rgba(239, 68, 68, .08)
        }

        .jx-money {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums
        }

        .jx-mini {
            font-size: .83rem;
            color: var(--muted)
        }

        .jx-view {
            text-align: right;
            white-space: nowrap
        }

        .jx-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .34rem .52rem;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: transparent;
            text-decoration: none;
            color: var(--text);
            font-size: .88rem
        }

        .jx-link:hover {
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, .35)
        }

        @media (max-width:1024px) {
            .jx-filter {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }

            .jx-date-range,
            .jx-filter-field:last-child {
                grid-column: 1 / -1
            }
        }

        @media (max-width:576px) {
            .jx-wrap {
                padding-right: .55rem;
                padding-left: .55rem
            }

            .jx-top {
                align-items: flex-start;
                flex-direction: column
            }

            .jx-actions {
                width: 100%
            }

            .jx-actions .jx-btn {
                flex: 1 1 0;
                min-width: 0
            }

            .jx-filter-card {
                padding: .7rem
            }

            .jx-filter-head {
                flex-direction: column;
                gap: .45rem
            }

            .jx-cutoff-note {
                align-self: flex-start
            }

            .jx-filter {
                grid-template-columns: 1fr;
                gap: .62rem
            }

            .jx-date-range,
            .jx-filter-field:last-child {
                grid-column: auto
            }

            .jx-filter-footer {
                align-items: stretch;
                flex-direction: column;
                gap: .5rem
            }

            .jx-filter-actions {
                width: 100%
            }

            .jx-filter-actions .jx-btn {
                flex: 1 1 0
            }

            .jx-hide-sm {
                display: none
            }

            .jx-desc {
                min-width: 0
            }

            .jx-table th,
            .jx-table td {
                padding: .52rem .52rem
            }
        }

        /* Ledger-aligned header and compact filter */
        .jx-wrap {
            max-width: 1180px;
            padding: clamp(.75rem, 2vw, 1.5rem) clamp(.75rem, 2vw, 1.25rem) 3rem
        }

        .jx-top {
            position: sticky;
            top: var(--app-navbar-offset, 58px);
            z-index: 30;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: .7rem;
            align-items: center;
            margin: .1rem 0 .85rem;
            padding: .65rem 0 .8rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            background: color-mix(in srgb, var(--bg) 92%, transparent);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px)
        }

        .jx-heading,
        .jx-title-row {
            min-width: 0
        }

        .jx-kicker {
            margin-bottom: .2rem;
            color: var(--muted);
            font-size: .64rem;
            font-weight: 850;
            letter-spacing: .1em;
            text-transform: uppercase
        }

        .jx-title-row {
            display: flex;
            align-items: baseline;
            gap: .5rem;
            flex-wrap: wrap
        }

        .jx-title {
            font-size: clamp(1.08rem, 2vw, 1.24rem);
            font-weight: 900;
            letter-spacing: -.02em
        }

        .jx-count {
            color: var(--muted);
            font-size: .76rem;
            font-variant-numeric: tabular-nums
        }

        .jx-actions {
            align-items: center;
            gap: .35rem
        }

        .jx-actions .jx-btn,
        .jx-filter .jx-btn {
            min-height: 36px;
            padding: .36rem .58rem;
            border-radius: 10px;
            font-size: .78rem
        }

        .jx-btn.primary {
            border-color: color-mix(in srgb, var(--accent) 52%, rgba(148, 163, 184, .35));
            background: color-mix(in srgb, var(--accent) 10%, var(--card) 90%)
        }

        .jx-top > .jx-filterbar {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: .55rem;
            padding: .65rem 0 0;
            border-top: 1px solid rgba(148, 163, 184, .14);
            border-bottom: 0;
            background: transparent
        }

        .jx-filterbar .jx-cutoff-note {
            flex: 0 0 auto;
            font-size: .68rem
        }

        .jx-filter {
            display: grid;
            grid-template-columns: minmax(14.5rem, 1.15fr) minmax(125px, .85fr) minmax(115px, .75fr) minmax(180px, 1.2fr) auto auto;
            align-items: end;
            gap: .45rem;
            width: 100%
        }

        .jx-date-range-field {
            width: 14.5rem;
            max-width: 100%
        }

        .jx-filter-field {
            min-width: 0
        }

        .jx-in,
        .jx-sel {
            min-height: 36px;
            padding: .36rem .55rem;
            border-radius: 10px;
            font-size: .8rem
        }

        .jx-filter-option {
            min-height: 36px;
            gap: .35rem;
            font-size: .7rem;
            white-space: nowrap
        }

        .jx-filter-option input {
            width: .9rem;
            height: .9rem
        }

        .jx-filter .jx-btn {
            width: auto;
            white-space: nowrap
        }

        @media (max-width:720px) {
            .jx-top {
                grid-template-columns: 1fr;
                gap: .6rem;
                padding-bottom: .7rem
            }

            .jx-actions {
                width: 100%
            }

            .jx-actions .jx-btn {
                flex: 1 1 0;
                min-width: 0;
                justify-content: center
            }

            .jx-top > .jx-filterbar {
                display: block;
                padding: .6rem 0 0
            }

            .jx-filterbar .jx-cutoff-note {
                display: inline-flex;
                margin-bottom: .45rem
            }

            .jx-filter {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                gap: .45rem
            }

            .jx-date-range-field {
                width: auto
            }

            .jx-date-range-field,
            .jx-search-field,
            .jx-filter-option {
                grid-column: 1 / -1
            }

            .jx-filter .jx-btn {
                width: 100%;
                justify-content: center
            }

            .jx-action-text {
                display: none
            }

            .jx-card {
                border-radius: 13px
            }

            .jx-table,
            .jx-table tbody {
                display: block
            }

            .jx-table thead {
                display: none
            }

            .jx-table tbody tr {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: .42rem .6rem;
                margin: .6rem;
                padding: .7rem .75rem;
                border: 1px solid rgba(148, 163, 184, .22);
                border-radius: 12px;
                background: color-mix(in srgb, var(--card) 94%, var(--bg) 6%);
                box-shadow: 0 4px 12px rgba(15, 23, 42, .045)
            }

            .jx-table tbody tr:last-child {
                margin-bottom: .6rem
            }

            .jx-table tbody tr td {
                display: block;
                min-width: 0;
                padding: 0;
                border: 0
            }

            .jx-table tbody tr td:nth-child(1) {
                grid-column: 1;
                grid-row: 1;
                color: var(--text);
                font-size: .78rem;
                font-weight: 800;
                font-variant-numeric: tabular-nums
            }

            .jx-table tbody tr td:nth-child(2) {
                grid-column: 1 / -1;
                grid-row: 2;
                padding-top: .1rem;
                border-top: 1px solid rgba(148, 163, 184, .16)
            }

            .jx-table tbody tr td:nth-child(3),
            .jx-table tbody tr td:nth-child(4) {
                display: none
            }

            .jx-table tbody tr td:nth-child(5) {
                grid-column: 2;
                grid-row: 1;
                text-align: right
            }

            .jx-table tbody tr td[colspan] {
                grid-column: 1 / -1;
                grid-row: auto;
                padding: .8rem
            }

            .jx-desc {
                min-width: 0
            }

            .jx-desc .t {
                display: -webkit-box;
                overflow: hidden;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 2
            }

            .jx-desc .m {
                gap: .28rem;
                font-size: .72rem
            }

            .jx-chip {
                font-size: .68rem
            }
        }
    </style>
@endpush

@section('content')
    @php
        /**
         * Map source_type -> label awam
         * Tambahin sendiri sesuai modul kamu.
         */
        $typeLabel = function (?string $t) {
            return match ($t) {
                'cash_expense' => 'Pengeluaran',
                'cash_expense_void' => 'Void Pengeluaran',
                'cash_receipt' => 'Penerimaan',
                'cash_receipt_void' => 'Void Penerimaan',
                'cash_transfer' => 'Transfer Kas/Bank',
                'cash_transfer_void' => 'Void Transfer Kas/Bank',
                'opening_balance' => 'Saldo Awal',
                'opening_balance_void' => 'Void Saldo Awal',
                // contoh masa depan:
                'shipment' => 'Pengiriman',
                'shipment_void' => 'Void Pengiriman',

                default => $t ?: 'Lainnya',
            };
        };
    @endphp

    <div class="jx-wrap">

        <div class="jx-top">
            <div class="jx-heading">
                <div class="jx-kicker">Akuntansi</div>
                <div class="jx-title-row">
                    <h1 class="jx-title">Jurnal</h1>
                    <span class="jx-count">{{ number_format($journals->total(), 0, ',', '.') }} entri</span>
                </div>
            </div>

            <div class="jx-actions">
                @if (Route::has('accounting.accounts.index'))
                    <a class="jx-btn" href="{{ route('accounting.accounts.index') }}"><i class="bi bi-wallet2" aria-hidden="true"></i><span>Akun</span></a>
                @endif
                @if (Route::has('accounting.cash-expenses.index'))
                    <a class="jx-btn" href="{{ route('accounting.cash-expenses.index') }}"><i class="bi bi-arrow-down-circle" aria-hidden="true"></i><span>Pengeluaran</span></a>
                @endif
                @if (Route::has('accounting.cash-transfers.index'))
                    <a class="jx-btn" href="{{ route('accounting.cash-transfers.index') }}"><i class="bi bi-arrow-left-right" aria-hidden="true"></i><span>Transfer</span></a>
                @endif
                @if (Route::has('accounting.opening-balances.index'))
                    <a class="jx-btn" href="{{ route('accounting.opening-balances.index') }}"><i class="bi bi-wallet2" aria-hidden="true"></i><span>Saldo awal</span></a>
                @endif
            </div>

            <div class="jx-filterbar">
                @if (($cutoff['active'] ?? false) && !request()->filled('from'))
                    <span class="jx-cutoff-note"><i class="bi bi-info-circle" aria-hidden="true"></i> Cutoff {{ $cutoff['date'] }}</span>
                @endif

                <form id="jx-filter-form" method="GET" action="{{ route('accounting.journals.index') }}" class="jx-filter">
                    <div class="jx-filter-field jx-date-range-field">
                        <input id="jx-date-range" class="jx-in" type="text" placeholder="Pilih periode" autocomplete="off"
                            data-from="{{ request('from') }}" data-to="{{ request('to') }}">
                    </div>

                    <div class="jx-filter-field">
                        <select id="jx-source-type" class="jx-sel" name="source_type" aria-label="Sumber">
                            <option value="">Semua sumber</option>
                            @foreach ($sourceTypes as $st)
                                <option value="{{ $st }}" @selected(request('source_type') === $st)>
                                    {{ $sourceTypeOptions->get($st, $typeLabel($st)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="jx-filter-field">
                        <select id="jx-status" class="jx-sel" name="status" aria-label="Status">
                            <option value="">Semua status</option>
                            <option value="final" @selected(request('status') === 'final')>Final</option>
                            <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                            <option value="voided" @selected(request('status') === 'voided')>Void</option>
                        </select>
                    </div>

                    <div class="jx-filter-field jx-search-field">
                        <input id="jx-q" class="jx-in" type="search" name="q" value="{{ request('q') }}" placeholder="Cari jurnal atau akun" aria-label="Cari jurnal atau akun">
                    </div>

                    <label class="jx-filter-option" for="jx-show-legacy">
                        <input id="jx-show-legacy" type="checkbox" name="show_legacy" value="1" @checked(request()->boolean('show_legacy'))>
                        Sebelum cutoff
                    </label>

                    @if (request()->query())
                        <a class="jx-btn" href="{{ route('accounting.journals.index') }}" aria-label="Reset filter"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i><span class="jx-action-text">Reset</span></a>
                    @endif
                </form>
            </div>
        </div>

        <div class="jx-card">
            <div class="table-responsive">
                <table class="jx-table">
                    <thead>
                        <tr>
                            <th style="width:110px;">Tanggal</th>
                            <th>Catatan</th>
                            <th style="width:220px;" class="jx-hide-sm">Masuk</th>
                            <th style="width:220px;" class="jx-hide-sm">Keluar</th>
                            <th style="width:74px;" class="jx-view"></th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($journals as $j)
                            @php
                                $lines = $j->lines ?? collect();

                                $debits = $lines->filter(fn($l) => (float) $l->debit > 0);
                                $credits = $lines->filter(fn($l) => (float) $l->credit > 0);

                                $sumDebit = (float) $debits->sum('debit');
                                $sumCredit = (float) $credits->sum('credit');

                                $topDebit = $debits->sortByDesc('debit')->first();
                                $topCredit = $credits->sortByDesc('credit')->first();

                                $debitCount = $debits->count();
                                $creditCount = $credits->count();

                                $statusLabel = $j->posted_at ? 'FINAL' : 'DRAFT';
                                $statusClass = $j->posted_at ? 'ok' : 'warn';

                                $isVoided = !is_null($j->voided_at ?? null);

                                $sourceText = $typeLabel($j->source_type) . ($j->source_id ? ' #' . $j->source_id : '');
                            @endphp

                            <tr>
                                <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($j->date)->locale('id')->translatedFormat('l, d/m/Y') }}
                                </td>

                                <td>
                                    <div class="jx-desc">
                                        <div class="t">{{ $j->description ?: '-' }}</div>

                                        <div class="m">
                                            <span class="jx-chip {{ $statusClass }}">{{ $statusLabel }}</span>

                                            @if ($isVoided)
                                                <span class="jx-chip bad">VOID</span>
                                            @endif

                                            <span class="jx-chip">{{ $sourceText }}</span>

                                            <span class="jx-mini">Rp {{ number_format($sumDebit, 0, ',', '.') }}</span>
                                        </div>

                                        {{-- Mobile ringkas --}}
                                        <div class="m d-sm-none" style="margin-top:.1rem;">
                                            <span class="jx-mini">
                                                M: {{ $topDebit?->account?->name ?? '-' }}@if ($debitCount > 1)
                                                    +{{ $debitCount - 1 }}
                                                @endif
                                            </span>
                                            <span class="jx-mini">
                                                K: {{ $topCredit?->account?->name ?? '-' }}@if ($creditCount > 1)
                                                    +{{ $creditCount - 1 }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="jx-hide-sm">
                                    @if ($topDebit)
                                        <div class="d-flex justify-content-between gap-2">
                                            <div class="text-truncate">
                                                <span class="fw-semibold">{{ $topDebit->account->name ?? 'Akun' }}</span>
                                                @if ($debitCount > 1)
                                                    <span class="jx-mini">+{{ $debitCount - 1 }}</span>
                                                @endif
                                            </div>
                                            <div class="jx-money">{{ number_format($topDebit->debit, 0, ',', '.') }}</div>
                                        </div>
                                    @else
                                        <span class="jx-mini">-</span>
                                    @endif
                                </td>

                                <td class="jx-hide-sm">
                                    @if ($topCredit)
                                        <div class="d-flex justify-content-between gap-2">
                                            <div class="text-truncate">
                                                <span class="fw-semibold">{{ $topCredit->account->name ?? 'Akun' }}</span>
                                                @if ($creditCount > 1)
                                                    <span class="jx-mini">+{{ $creditCount - 1 }}</span>
                                                @endif
                                            </div>
                                            <div class="jx-money">{{ number_format($topCredit->credit, 0, ',', '.') }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="jx-mini">-</span>
                                    @endif
                                </td>

                                <td class="jx-view">
                                    <a class="jx-link" href="{{ route('accounting.journals.show', $j) }}">Detail</a>
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                {{ $journals->links() }}
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const picker = document.getElementById('jx-date-range');
                const form = document.getElementById('jx-filter-form');

                if (!picker || !form || !window.GFID?.initDateRange) return;

                let selectedFrom = picker.dataset.from || '';
                let selectedTo = picker.dataset.to || '';
                let datePickerReady = false;
                let searchTimer;

                const submitFilters = () => form?.requestSubmit();

                form.addEventListener('submit', () => {
                    form.querySelectorAll('.jx-period-submit').forEach((field) => field.remove());

                    [['from', selectedFrom], ['to', selectedTo]].forEach(([name, value]) => {
                        const field = document.createElement('input');
                        field.type = 'hidden';
                        field.name = name;
                        field.value = value;
                        field.className = 'jx-period-submit';
                        form.appendChild(field);
                    });
                });

                window.GFID.initDateRange(picker, {
                    mode: 'range',
                    showMonths: 1,
                    altInput: false,
                    dateFormat: 'd M Y',
                    altFormat: 'd M Y',
                    rangeSeparator: ' – ',
                    defaultDate: [selectedFrom, selectedTo].filter(Boolean),
                    onChange: (selectedDates) => {
                        selectedFrom = selectedDates[0]
                            ? window.flatpickr.formatDate(selectedDates[0], 'Y-m-d')
                            : '';
                        selectedTo = selectedDates[1]
                            ? window.flatpickr.formatDate(selectedDates[1], 'Y-m-d')
                            : '';

                        if (datePickerReady && (selectedDates.length === 2 || selectedDates.length === 0)) {
                            submitFilters();
                        }
                    }
                });

                window.setTimeout(() => { datePickerReady = true; }, 0);

                form?.querySelectorAll('select, input[type="checkbox"]').forEach((field) => {
                    field.addEventListener('change', submitFilters);
                });

                const search = document.getElementById('jx-q');
                search?.addEventListener('input', () => {
                    window.clearTimeout(searchTimer);
                    searchTimer = window.setTimeout(submitFilters, 450);
                });

                search?.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        window.clearTimeout(searchTimer);
                        submitFilters();
                    }
                });
            });
        </script>
    @endsection
