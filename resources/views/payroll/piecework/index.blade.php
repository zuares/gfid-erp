@extends('layouts.app')

@section('title', 'Payroll • ' . ($moduleLabel ?? ucfirst($module ?? '')))

@push('head')
    <style>
        .pw-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: .75rem .75rem 2.5rem
        }

        .pw-top {
            display: flex;
            gap: .75rem;
            align-items: flex-start;
            justify-content: space-between;
            margin: .25rem 0 .75rem
        }

        .pw-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
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
            gap: .5rem;
            align-items: center;
            justify-content: space-between
        }

        .pw-card-b {
            padding: .9rem
        }

        .pw-actions {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap
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

        .pw-btn.primary {
            border-color: color-mix(in srgb, var(--accent) 40%, rgba(148, 163, 184, .35));
            background: color-mix(in srgb, var(--accent-soft) 18%, var(--card) 82%)
        }

        .pw-btn:hover {
            box-shadow: inset 0 0 0 1px var(--line)
        }

        .pw-row {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center
        }

        .pw-in {
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

        .pw-right {
            text-align: right
        }

        @media (max-width:640px) {
            .pw-hide-sm {
                display: none
            }

            .pw-top {
                flex-direction: column;
                align-items: stretch
            }

            .pw-actions {
                justify-content: flex-start
            }
        }
    </style>
@endpush

@section('content')
    <div class="pw-wrap">
        <div class="pw-top">
            <div>
                <h1 class="pw-title">Payroll • {{ $moduleLabel ?? ucfirst($module) }}</h1>
                <div class="pw-sub">Periode borongan (PCS) — Basis: {{ $module === 'sewing' ? 'Ambil Jahit' : 'QC OK' }} · Final = HPP + Hutang, Bayar = lunasi hutang.</div>
            </div>

            <div class="pw-actions">
                <a class="pw-btn primary" href="{{ route('payroll.piecework.create', ['module' => $module]) }}">＋ Generate</a>
            </div>
        </div>

        <div class="pw-card">
            <div class="pw-card-h">
                <div class="pw-row">
                    <span class="pw-chip">Filter</span>
                </div>

                <form class="pw-row" method="GET" action="{{ route('payroll.piecework.index', ['module' => $module]) }}">
                    <input class="pw-in" type="date" name="from" value="{{ request('from') }}">
                    <input class="pw-in" type="date" name="to" value="{{ request('to') }}">
                    <button class="pw-btn" type="submit">Terapkan</button>
                    @if (request()->filled('from') || request()->filled('to'))
                        <a class="pw-btn" href="{{ route('payroll.piecework.index', ['module' => $module]) }}">Reset</a>
                    @endif
                </form>
            </div>

            <div class="pw-card-b" style="padding:0">
                <table class="pw-table">
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th class="pw-hide-sm">Status</th>
                            <th class="pw-right">Total</th>
                            <th class="pw-right pw-hide-sm">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($periods as $p)
                            <tr>
                                <td>
                                    <div style="font-weight:700">
                                        {{ id_date($p->period_start) }} – {{ id_date($p->period_end) }}
                                    </div>
                                    <div class="pw-sub" style="margin:0">
                                        ID #{{ $p->id }}
                                        @if ($p->paid_at)
                                            • Paid {{ \Carbon\Carbon::parse($p->paid_at)->format('d/m/Y H:i') }}
                                        @endif
                                    </div>
                                </td>
                                <td class="pw-hide-sm">
                                    @if ($p->status === 'final')
                                        <span class="pw-chip final">FINAL</span>
                                    @else
                                        <span class="pw-chip draft">DRAFT</span>
                                    @endif
                                </td>
                                <td class="pw-right">
                                    <div style="font-weight:800">{{ number_format((float) $p->total_amount, 0, ',', '.') }}
                                    </div>
                                </td>
                                <td class="pw-right pw-hide-sm">
                                    <a class="pw-btn"
                                        href="{{ route('payroll.piecework.show', ['module' => $module, 'period' => $p]) }}">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="padding:1rem;color:var(--muted)">Belum ada periode.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div style="padding:.8rem .9rem">
                    {{ $periods->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
