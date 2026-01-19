@extends('layouts.app')

@section('title', 'Journal')

@push('head')
    <style>
        .jd-wrap {
            max-width: 980px;
            margin: 0 auto;
            padding: .75rem .75rem 2.5rem;
        }

        .jd-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .75rem;
        }

        .jd-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -.02em;
        }

        .jd-sub {
            font-size: .9rem;
            color: var(--muted);
        }

        .jd-actions {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .jd-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .45rem .65rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: transparent;
            text-decoration: none;
            color: var(--text);
            font-size: .92rem;
        }

        .jd-btn:hover {
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, .35);
        }

        .jd-card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 14px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06);
        }

        .jd-body {
            padding: .85rem .9rem;
        }

        .jd-row {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            align-items: center;
        }

        .jd-chip {
            display: inline-flex;
            align-items: center;
            padding: .18rem .55rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .30);
            font-size: .78rem;
            background: color-mix(in srgb, var(--card) 86%, transparent 14%);
            color: color-mix(in srgb, var(--text) 70%, transparent 30%);
        }

        .jd-chip.ok {
            border-color: rgba(34, 197, 94, .35);
            color: rgba(22, 163, 74, 1);
            background: rgba(34, 197, 94, .08);
        }

        .jd-chip.bad {
            border-color: rgba(239, 68, 68, .35);
            color: rgba(185, 28, 28, 1);
            background: rgba(239, 68, 68, .08);
        }

        .jd-chip.warn {
            border-color: rgba(245, 158, 11, .35);
            color: rgba(180, 83, 9, 1);
            background: rgba(245, 158, 11, .08);
        }

        .jd-k {
            color: var(--muted);
            font-size: .82rem;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .jd-v {
            font-weight: 600;
        }

        .jd-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .75rem;
            margin-top: .75rem;
        }

        @media (max-width: 576px) {
            .jd-grid {
                grid-template-columns: 1fr;
            }
        }

        .jd-table {
            width: 100%;
            border-collapse: collapse;
        }

        .jd-table th,
        .jd-table td {
            padding: .65rem .7rem;
            vertical-align: middle;
        }

        .jd-table thead th {
            font-size: .75rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: color-mix(in srgb, var(--text) 60%, transparent 40%);
            border-bottom: 1px solid rgba(148, 163, 184, .25);
            background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
            white-space: nowrap;
        }

        .jd-table tbody tr {
            border-bottom: 1px solid rgba(148, 163, 184, .18);
        }

        .jd-money {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .jd-mini {
            font-size: .86rem;
            color: var(--muted);
        }
    </style>
@endpush

@section('content')
    <div class="jd-wrap">
        @php
            $lines = $journal->lines ?? collect();
            $debits = $lines->filter(fn($l) => (float) $l->debit > 0);
            $credits = $lines->filter(fn($l) => (float) $l->credit > 0);

            $sumDebit = (float) $debits->sum('debit');
            $sumCredit = (float) $credits->sum('credit');
            $balanced = abs($sumDebit - $sumCredit) <= 0.01;

            $topDebit = $debits->sortByDesc('debit')->first();
            $topCredit = $credits->sortByDesc('credit')->first();
        @endphp

        <div class="jd-head">
            <div>
                <h4 class="jd-title">{{ $journal->description ?: 'Journal' }}</h4>
                <div class="jd-sub">
                    {{ \Illuminate\Support\Carbon::parse($journal->date)->format('Y-m-d') }}
                    • {{ $journal->source_type }}{{ $journal->source_id ? ' #' . $journal->source_id : '' }}
                    • ID #{{ $journal->id }}
                </div>
            </div>

            <div class="jd-actions">
                <a class="jd-btn" href="{{ route('accounting.journals.index') }}">Back</a>
                @if (!empty($sourceUrl))
                    <a class="jd-btn" href="{{ $sourceUrl }}">{{ $sourceLabel ?? 'Source' }}</a>
                @endif
            </div>
        </div>

        <div class="jd-card mb-3">
            <div class="jd-body">
                <div class="jd-row">
                    <span
                        class="jd-chip {{ $journal->posted_at ? 'ok' : 'warn' }}">{{ $journal->posted_at ? 'POSTED' : 'DRAFT' }}</span>
                    <span class="jd-chip {{ $balanced ? 'ok' : 'bad' }}">{{ $balanced ? 'BALANCED' : 'NOT BAL' }}</span>
                    <span class="jd-chip">Rp {{ number_format($sumDebit, 0, ',', '.') }}</span>
                </div>

                <div class="jd-grid">
                    <div>
                        <div class="jd-k">Debit →</div>
                        <div class="jd-v">
                            {{ $topDebit?->account?->name ?? '-' }}
                            @if ($debits->count() > 1)
                                <span class="jd-mini">+{{ $debits->count() - 1 }}</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="jd-k">Credit →</div>
                        <div class="jd-v">
                            {{ $topCredit?->account?->name ?? '-' }}
                            @if ($credits->count() > 1)
                                <span class="jd-mini">+{{ $credits->count() - 1 }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="jd-card">
            <div class="table-responsive">
                <table class="jd-table">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th style="width:160px;" class="jd-money">Debit</th>
                            <th style="width:160px;" class="jd-money">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lines as $line)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $line->account->name ?? 'Unknown Account' }}</div>
                                    <div class="jd-mini">{{ $line->account->code ?? '-' }} •
                                        {{ $line->account->type ?? '-' }}</div>
                                </td>
                                <td class="jd-money">
                                    {{ $line->debit > 0 ? number_format($line->debit, 0, ',', '.') : '-' }}</td>
                                <td class="jd-money">
                                    {{ $line->credit > 0 ? number_format($line->credit, 0, ',', '.') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="jd-money">Total</th>
                            <th class="jd-money">{{ number_format($sumDebit, 0, ',', '.') }}</th>
                            <th class="jd-money">{{ number_format($sumCredit, 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
