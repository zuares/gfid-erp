@extends('layouts.app')

@section('title', 'Slip All • ' . ($moduleLabel ?? ucfirst($module ?? '')))

@push('head')
    <style>
        .pw-wrap {
            max-width: 1100px;
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

        .pw-emp {
            padding: .8rem;
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 14px;
            margin-bottom: .75rem
        }

        .pw-emp-top {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap
        }

        .pw-emp-name {
            font-weight: 900
        }

        .pw-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: .5rem
        }

        .pw-table th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            text-align: left;
            padding: .45rem .5rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18)
        }

        .pw-table td {
            padding: .5rem .5rem;
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
    <div class="pw-wrap">
        <div class="pw-card">
            <div class="pw-h">
                <div>
                    <h1 class="pw-title">Slip All • {{ $moduleLabel ?? ucfirst($module) }}</h1>
                    <div class="pw-sub">Periode {{ id_date($period->period_start) }} – {{ id_date($period->period_end) }}
                    </div>
                </div>
                <a class="pw-btn" href="{{ route('payroll.piecework.show', ['module' => $module, 'period' => $period]) }}">←
                    Back</a>
            </div>

            <div class="pw-b">
                <div class="pw-sub" style="margin-bottom:.8rem">
                    Grand Qty: <b>{{ rtrim(rtrim(number_format((float) $grandTotalQty, 2, '.', ''), '0'), '.') }}</b>
                    • Grand Amount: <b>{{ number_format((float) $grandTotalAmount, 0, ',', '.') }}</b>
                </div>

                @foreach ($byEmployee as $row)
                    <div class="pw-emp">
                        <div class="pw-emp-top">
                            <div>
                                <div class="pw-emp-name">{{ $row['employee']?->name ?? '-' }}</div>
                                <div class="pw-sub" style="margin:0">
                                    Qty:
                                    <b>{{ rtrim(rtrim(number_format((float) $row['total_qty'], 2, '.', ''), '0'), '.') }}</b>
                                    • Amount: <b>{{ number_format((float) $row['total_amount'], 0, ',', '.') }}</b>
                                </div>
                            </div>
                            <a class="pw-btn"
                                href="{{ route('payroll.piecework.slip', ['module' => $module, 'period' => $period, 'employee' => $row['employee']?->id]) }}">Slip</a>
                        </div>

                        <table class="pw-table">
                            <thead>
                                <tr>
                                    <th class="pw-hide-sm">Category</th>
                                    <th>Item</th>
                                    <th class="pw-right">Qty</th>
                                    <th class="pw-right pw-hide-sm">Rate</th>
                                    <th class="pw-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($row['lines'] as $l)
                                    <tr>
                                        <td class="pw-hide-sm">{{ $l->category?->name ?? '-' }}</td>
                                        <td>{{ $l->item?->name ?? '-' }}</td>
                                        <td class="pw-right">
                                            {{ rtrim(rtrim(number_format((float) $l->total_qty_ok, 2, '.', ''), '0'), '.') }}
                                        </td>
                                        <td class="pw-right pw-hide-sm">
                                            {{ number_format((float) $l->rate_per_pcs, 0, ',', '.') }}</td>
                                        <td class="pw-right" style="font-weight:900">
                                            {{ number_format((float) $l->amount, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
