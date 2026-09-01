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

        .lg-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 900;
            letter-spacing: -.02em
        }

        .lg-sub {
            margin: .2rem 0 0;
            color: var(--muted);
            font-size: .86rem
        }

        .lg-card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 16px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06);
            overflow: hidden
        }

        .lg-h {
            padding: .85rem .9rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: center
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

        .lg-chip {
            display: inline-flex;
            align-items: center;
            border: 1px solid rgba(148, 163, 184, .25);
            padding: .18rem .48rem;
            border-radius: 999px;
            font-size: .78rem;
            color: var(--muted)
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

        @media (max-width: 720px) {
            .lg-hide-sm {
                display: none
            }

            .lg-top {
                flex-direction: column;
                align-items: stretch
            }
        }
    </style>
@endpush

@section('content')
    <div class="lg-wrap">
        <div class="lg-top">
            <div>
                <h1 class="lg-title">Ledger • {{ $account->code }} {{ $account->name }}</h1>
                <div class="lg-sub">
                    {{ strtoupper($account->type) }}
                    @if ($account->is_cash)
                        • Cash/Bank
                    @endif
                    @if (!$account->is_active)
                        • Nonaktif
                    @endif
                </div>
                @if ($cutoffDate && !$showLegacy)
                    <div class="lg-muted" style="margin-top:.25rem">
                        Sistem baru mulai {{ \Carbon\Carbon::parse($cutoffDate)->format('d/m/Y') }}.
                        Histori legacy tidak dihitung.
                    </div>
                @elseif ($cutoffDate)
                    <div class="lg-muted" style="margin-top:.25rem">Menampilkan seluruh histori jurnal.</div>
                @endif
            </div>

            <div class="lg-row">
                <a class="lg-btn" href="{{ route('accounting.accounts.index') }}">← Accounts</a>
                <a class="lg-btn primary" href="{{ route('accounting.journals.index') }}">Journals</a>
            </div>
        </div>

        {{-- Summary --}}
        <div class="lg-card" style="margin-bottom:.75rem">
            <div class="lg-h">
                <div class="lg-row">
                    <span class="lg-chip">Opening</span>
                    @php $ob = (float)$openingBalance; @endphp
                    <span class="lg-bal {{ $ob < 0 ? 'neg' : 'pos' }}">{{ number_format($ob, 0, ',', '.') }}</span>

                    <span class="lg-chip">Current</span>
                    @php $cb = (float)$currentBalance; @endphp
                    <span class="lg-bal {{ $cb < 0 ? 'neg' : 'pos' }}">{{ number_format($cb, 0, ',', '.') }}</span>
                </div>

                <form class="lg-row" method="GET" action="{{ route('accounting.accounts.ledger', $account) }}">
                    <input class="lg-in" type="date" name="from" value="{{ $from ?? '' }}">
                    <input class="lg-in" type="date" name="to" value="{{ $to ?? '' }}">
                    @if ($showLegacy)
                        <input type="hidden" name="show_legacy" value="1">
                    @endif
                    <button class="lg-btn" type="submit">Filter</button>
                    @if (request()->filled('from') || request()->filled('to') || $showLegacy)
                        <a class="lg-btn" href="{{ route('accounting.accounts.ledger', $account) }}">Reset</a>
                    @endif
                </form>
            </div>

            <div class="lg-b" style="padding:0">
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
                                        <div style="font-weight:700">{{ $l->journal_description ?? '-' }}</div>
                                    </a>
                                    <div class="lg-muted">Line #{{ $l->id }}</div>
                                </td>
                                <td class="lg-right">{{ $d ? number_format($d, 0, ',', '.') : '—' }}</td>
                                <td class="lg-right">{{ $c ? number_format($c, 0, ',', '.') : '—' }}</td>
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
