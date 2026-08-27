@extends('layouts.app')

@section('title', 'Slip Payroll • ' . ($moduleLabel ?? ucfirst($module ?? '')))

@push('head')
    <style>
        .pw-wrap {
            max-width: 950px;
            margin: 0 auto;
            padding: .75rem .75rem 2.5rem
        }

        .pw-card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .25);
            border-radius: 14px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06)
        }

        .pw-h {
            padding: .9rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: flex-start
        }

        .pw-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 900
        }

        .pw-sub {
            margin: .25rem 0 0;
            color: var(--muted);
            font-size: .86rem
        }

        .pw-btn {
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

        .pw-b {
            padding: .9rem
        }

        .pw-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0
        }

        .pw-table th {
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

        @media(max-width:640px) {
            .pw-hide-sm {
                display: none
            }
        }
    </style>
@endpush

@section('content')
    @php($qtyLabel = $module === 'sewing' ? 'Qty Ambil' : 'Qty OK')
    <div class="pw-wrap">
        <div class="pw-card">
            <div class="pw-h">
                <div>
                    <h1 class="pw-title">{{ $moduleLabel ?? ucfirst($module) }} • Slip</h1>
                    <div class="pw-sub">
                        {{ $employee?->name ?? '-' }} • Periode {{ id_date($period->period_start) }} –
                        {{ id_date($period->period_end) }}
                    </div>
                </div>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                    <a class="pw-btn" href="{{ route('payroll.piecework.show', ['module' => $module, 'period' => $period]) }}">←
                        Back</a>
                </div>
            </div>

            <div class="pw-b">
                <div class="pw-sub" style="margin-bottom:.7rem">
                    {{ $qtyLabel }}: <b>{{ rtrim(rtrim(number_format((float) $totalQty, 2, '.', ''), '0'), '.') }}</b>
                    • Total Amount: <b>{{ number_format((float) $totalAmount, 0, ',', '.') }}</b>
                </div>

                <table class="pw-table">
                    <thead>
                        <tr>
                            <th class="pw-hide-sm">Category</th>
                            <th>Item</th>
                            <th class="pw-right">{{ $qtyLabel }}</th>
                            <th class="pw-right">Rate</th>
                            <th class="pw-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lines as $l)
                            <tr>
                                <td class="pw-hide-sm">{{ $l->category?->name ?? '-' }}</td>
                                <td>{{ $l->item?->name ?? '-' }}</td>
                                <td class="pw-right">
                                    {{ rtrim(rtrim(number_format((float) $l->total_qty_ok, 2, '.', ''), '0'), '.') }}</td>
                                <td class="pw-right">{{ number_format((float) $l->rate_per_pcs, 0, ',', '.') }}</td>
                                <td class="pw-right" style="font-weight:900">
                                    {{ number_format((float) $l->amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
