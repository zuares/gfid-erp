@extends('layouts.app')

@section('title', 'Ledger • ' . $account->code . ' ' . $account->name)

@push('head')
    <style>
        .lg-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: .75rem .75rem 2.5rem
        }

        .lg-top {
            display: flex;
            gap: .75rem;
            align-items: flex-start;
            justify-content: space-between;
            margin: .25rem 0 .75rem
        }

        .lg-heading {
            min-width: 0
        }

        .lg-kicker {
            color: var(--muted);
            font-size: .68rem;
            font-weight: 850;
            letter-spacing: .12em;
            text-transform: uppercase
        }

        .lg-title-line {
            display: flex;
            align-items: center;
            gap: .55rem;
            flex-wrap: wrap;
            margin-top: .18rem
        }

        .lg-title {
            margin: 0;
            font-size: 1.18rem;
            font-weight: 900;
            letter-spacing: -.02em
        }

        .lg-account-code {
            display: inline-flex;
            align-items: center;
            min-height: 1.55rem;
            padding: .12rem .48rem;
            border: 1px solid rgba(148, 163, 184, .3);
            border-radius: 7px;
            color: var(--muted);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: .76rem;
            font-weight: 800
        }

        .lg-sub {
            margin: .2rem 0 0;
            color: var(--muted);
            font-size: .86rem
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
            gap: .45rem;
            flex-wrap: wrap;
            justify-content: flex-end
        }

        .lg-notice {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            margin-top: .45rem;
            padding: .32rem .5rem;
            border: 1px solid rgba(148, 163, 184, .2);
            border-radius: 7px;
            background: color-mix(in srgb, var(--muted) 6%, transparent);
            color: var(--muted);
            font-size: .76rem
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

        .lg-filter-caption {
            margin-top: .12rem;
            color: var(--muted);
            font-size: .75rem
        }

        .lg-filter-form {
            display: flex;
            align-items: flex-end;
            gap: .5rem;
            flex-wrap: wrap;
            justify-content: flex-end
        }

        .lg-field {
            display: flex;
            flex-direction: column;
            gap: .2rem;
            min-width: 8.75rem
        }

        .lg-field span {
            color: var(--muted);
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase
        }

        .lg-filter-count {
            color: var(--muted);
            font-size: .75rem;
            white-space: nowrap
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
            gap: .4rem;
            border: 1px solid rgba(148, 163, 184, .35);
            background: transparent;
            color: var(--text);
            padding: .48rem .72rem;
            border-radius: 12px;
            text-decoration: none;
            font-size: .88rem
        }

        .lg-btn.primary {
            border-color: color-mix(in srgb, var(--accent) 40%, rgba(148, 163, 184, .35));
            background: color-mix(in srgb, var(--accent-soft) 18%, var(--card) 82%)
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
            font-weight: 900;
            letter-spacing: -.02em
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
                flex-direction: column;
                align-items: stretch
            }

            .lg-top .lg-actions {
                width: 100%
            }

            .lg-actions .lg-btn {
                flex: 1 1 0;
                justify-content: center
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
                grid-template-columns: 1fr 1fr;
                gap: .5rem;
                margin-top: .7rem
            }

            .lg-filter-form .lg-field {
                min-width: 0
            }

            .lg-filter-form .lg-in,
            .lg-filter-form .lg-btn {
                width: 100%;
                min-width: 0
            }

            .lg-filter-form button[type="submit"],
            .lg-filter-form a.lg-btn {
                grid-column: 1 / -1
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
                gap: .55rem .65rem;
                margin-bottom: .65rem;
                padding: .75rem;
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
            .lg-table tbody tr td:nth-child(3) {
                grid-column: 1 / -1
            }

            .lg-table tbody tr td:nth-child(1) {
                font-size: .78rem;
                color: var(--muted)
            }

            .lg-table tbody tr td:nth-child(3) {
                padding-bottom: .15rem;
                border-bottom: 1px solid rgba(148, 163, 184, .16)
            }

            .lg-table tbody tr td:nth-child(4)::before,
            .lg-table tbody tr td:nth-child(5)::before,
            .lg-table tbody tr td:nth-child(6)::before {
                display: block;
                margin-bottom: .18rem;
                color: var(--muted);
                font-size: .67rem;
                font-weight: 800;
                letter-spacing: .06em;
                text-align: left;
                text-transform: uppercase
            }

            .lg-table tbody tr td:nth-child(4)::before {
                content: 'Debit'
            }

            .lg-table tbody tr td:nth-child(5)::before {
                content: 'Credit'
            }

            .lg-table tbody tr td:nth-child(6)::before {
                content: 'Saldo'
            }

            .lg-table tbody tr td:nth-child(4),
            .lg-table tbody tr td:nth-child(5),
            .lg-table tbody tr td:nth-child(6) {
                text-align: left
            }

            .lg-amount {
                display: block;
                min-width: 0;
                width: 100%;
                text-align: right
            }

            .lg-table tbody tr td:nth-child(6) {
                padding-left: .65rem;
                border-left: 1px solid rgba(148, 163, 184, .18)
            }

            .lg-table tbody tr td:nth-child(6) .lg-bal {
                display: block;
                text-align: right
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
                <div class="lg-kicker">Buku besar · Detail akun</div>
                <div class="lg-title-line">
                    <h1 class="lg-title">{{ $account->name }}</h1>
                    <span class="lg-account-code">{{ $account->code }}</span>
                </div>
                <div class="lg-sub">
                    <span class="lg-status {{ $account->is_active ? '' : 'inactive' }}">
                        {{ $account->is_active ? 'Akun aktif' : 'Akun nonaktif' }}
                    </span>
                    <span>· {{ strtoupper($account->type) }}</span>
                    @if ($account->is_cash)
                        • Cash/Bank
                    @endif
                </div>
                @if ($cutoffDate && !$showLegacy)
                    <div class="lg-notice">
                        <span aria-hidden="true">ⓘ</span>
                        <span>Batas sistem {{ \Carbon\Carbon::parse($cutoffDate)->format('d/m/Y') }} · Histori legacy tidak dihitung</span>
                    </div>
                @elseif ($cutoffDate)
                    <div class="lg-notice">
                        <span aria-hidden="true">ⓘ</span>
                        <span>Seluruh histori jurnal sedang ditampilkan</span>
                    </div>
                @endif
            </div>

            <div class="lg-actions">
                <a class="lg-btn" href="{{ route('accounting.accounts.index') }}">← Akun</a>
                <a class="lg-btn" href="{{ route('accounting.accounts.edit', $account) }}">Edit akun</a>
                <a class="lg-btn primary" href="{{ route('accounting.journals.index') }}">Jurnal</a>
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
                    <div class="lg-metric-value">{{ number_format($cb, 0, ',', '.') }}</div>
                </div>
            </div>

            <div class="lg-filterbar">
                <div>
                    <div class="lg-filter-title">Filter tanggal posting</div>
                    <div class="lg-filter-caption">Batasi transaksi berdasarkan tanggal jurnal</div>
                </div>

                <form class="lg-filter-form" method="GET" action="{{ route('accounting.accounts.ledger', $account) }}">
                    <label class="lg-field">
                        <span>Dari</span>
                        <input class="lg-in" type="date" name="from" value="{{ $from ?? '' }}">
                    </label>
                    <label class="lg-field">
                        <span>Sampai</span>
                        <input class="lg-in" type="date" name="to" value="{{ $to ?? '' }}">
                    </label>
                    @if ($showLegacy)
                        <input type="hidden" name="show_legacy" value="1">
                    @endif
                    <button class="lg-btn primary" type="submit">Terapkan filter</button>
                    @if (request()->filled('from') || request()->filled('to') || $showLegacy)
                        <a class="lg-btn" href="{{ route('accounting.accounts.ledger', $account) }}">Reset</a>
                    @endif
                </form>
            </div>

            <div class="lg-b" style="padding:0">
                <div class="lg-table-toolbar">
                    <div>
                        <strong>Aktivitas jurnal</strong>
                        <span class="lg-muted">{{ number_format($lines->total(), 0, ',', '.') }} entri</span>
                    </div>
                    <span class="lg-filter-count">Nominal dalam IDR</span>
                </div>
                <table class="lg-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th class="lg-hide-sm">Ref</th>
                            <th>Keterangan</th>
                            <th class="lg-right">Debit</th>
                            <th class="lg-right">Credit</th>
                            <th class="lg-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $running = (float) $openingBalance;
                        @endphp

                        {{-- Opening row --}}
                        <tr>
                            <td class="lg-muted">
                                {{ request('from') ? \Carbon\Carbon::parse(request('from'))->format('d/m/Y') : '-' }}</td>
                            <td class="lg-hide-sm lg-muted">—</td>
                            <td class="lg-muted">Opening Balance</td>
                            <td class="lg-right lg-muted">—</td>
                            <td class="lg-right lg-muted">—</td>
                            <td class="lg-right lg-bal {{ $running < 0 ? 'neg' : 'pos' }}">
                                {{ number_format($running, 0, ',', '.') }}</td>
                        </tr>

                        @forelse($lines as $l)
                            @php
                                $d = (float) ($l->debit ?? 0);
                                $c = (float) ($l->credit ?? 0);
                                $running += $d - $c;
                                $relatedUrl = $l->related_url ?? route('accounting.journals.show', $l->journal_id);
                                $relatedLabel = $l->related_label ?? 'Buka detail jurnal';
                                $ref = $l->source_type
                                    ? strtoupper($l->source_type) . ($l->source_id ? '#' . $l->source_id : '')
                                    : '-';
                                $postedAt = $l->posted_at
                                    ? \Carbon\Carbon::parse($l->posted_at)->format('H:i')
                                    : null;
                            @endphp
                            <tr class="lg-click-row" data-href="{{ $relatedUrl }}" tabindex="0" role="link"
                                aria-label="{{ $relatedLabel }}">
                                <td>
                                    <div>{{ \Carbon\Carbon::parse($l->date)->format('d/m/Y') }}</div>
                                    @if ($postedAt)
                                        <div class="lg-muted" style="font-size:.76rem">{{ $postedAt }}</div>
                                    @endif
                                </td>
                                <td class="lg-hide-sm lg-muted">
                                    <a class="lg-row-link" href="{{ $relatedUrl }}" tabindex="-1">{{ $ref }}</a>
                                </td>
                                <td>
                                    <a class="lg-row-link" href="{{ $relatedUrl }}" tabindex="-1">
                                        <div style="font-weight:700">{{ $l->grn_supplier_name ?: ($l->journal_description ?? '-') }}</div>
                                    </a>
                                    <div class="lg-muted">Line #{{ $l->id }}</div>
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
                                <td class="lg-right lg-bal {{ $running < 0 ? 'neg' : 'pos' }}">
                                    {{ number_format($running, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding:1rem;color:var(--muted)">Belum ada transaksi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div style="padding:.8rem .9rem">
                    {{ $lines->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
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
