@extends('layouts.app')

@section('title', 'Chart of Accounts')

@push('head')
    <style>
        .coa-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: .75rem .75rem 2.5rem
        }

        .coa-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            margin: .25rem 0 .75rem
        }

        .coa-title {
            font-size: 1.05rem;
            font-weight: 900;
            letter-spacing: -.02em;
            margin: 0
        }

        .coa-sub {
            margin: .2rem 0 0;
            color: var(--muted);
            font-size: .86rem
        }

        .coa-actions {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center
        }

        .coa-btn {
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

        .coa-btn.primary {
            border-color: color-mix(in srgb, var(--accent) 40%, rgba(148, 163, 184, .35));
            background: color-mix(in srgb, var(--accent-soft) 18%, var(--card) 82%)
        }

        .coa-in,
        .coa-sel {
            border: 1px solid rgba(148, 163, 184, .28);
            background: transparent;
            color: var(--text);
            border-radius: 12px;
            padding: .46rem .6rem;
            font-size: .88rem
        }

        .coa-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: .75rem;
            margin-bottom: .75rem
        }

        @media(min-width: 992px) {
            .coa-grid {
                grid-template-columns: repeat(4, 1fr)
            }
        }

        .coa-kpi {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .25);
            border-radius: 16px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06);
            padding: .8rem .85rem
        }

        .coa-kpi .k {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted)
        }

        .coa-kpi .v {
            margin-top: .25rem;
            font-weight: 900;
            letter-spacing: -.02em;
            font-size: 1.05rem
        }

        .coa-kpi .s {
            margin-top: .2rem;
            color: var(--muted);
            font-size: .82rem
        }

        .coa-kpi .v.pos {
            color: rgba(16, 185, 129, 1)
        }

        .coa-kpi .v.neg {
            color: rgba(239, 68, 68, 1)
        }

        .coa-card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 16px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06);
            overflow: hidden
        }

        table.coa-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0
        }

        .coa-table th {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            padding: .6rem .7rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            text-align: left
        }

        .coa-table td {
            padding: .65rem .7rem;
            border-bottom: 1px solid rgba(148, 163, 184, .12);
            vertical-align: middle
        }

        .coa-code {
            font-weight: 800;
            font-size: .9rem
        }

        .coa-name {
            font-weight: 700
        }

        .coa-name a {
            color: inherit;
            text-decoration: none
        }

        .coa-name a:hover {
            text-decoration: underline
        }

        .coa-type {
            font-size: .72rem;
            padding: .15rem .45rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .25);
            color: var(--muted);
            display: inline-block
        }

        .coa-subline {
            font-size: .78rem;
            color: var(--muted);
            margin-top: .1rem
        }

        .coa-balance {
            text-align: right;
            font-weight: 900;
            letter-spacing: -.02em;
            white-space: nowrap
        }

        .coa-balance.pos {
            color: rgba(16, 185, 129, 1)
        }

        .coa-balance.neg {
            color: rgba(239, 68, 68, 1)
        }

        .coa-chip {
            display: inline-flex;
            align-items: center;
            border: 1px solid rgba(148, 163, 184, .25);
            padding: .15rem .45rem;
            border-radius: 999px;
            font-size: .75rem;
            color: var(--muted)
        }

        .coa-chip.warn {
            border-color: rgba(245, 158, 11, .35);
            color: rgba(245, 158, 11, 1)
        }

        .coa-chip.info {
            border-color: rgba(59, 130, 246, .35);
            color: rgba(59, 130, 246, 1)
        }

        .coa-chip.ok {
            border-color: rgba(16, 185, 129, .35);
            color: rgba(16, 185, 129, 1)
        }

        .coa-rowhint {
            display: flex;
            gap: .35rem;
            flex-wrap: wrap;
            align-items: center;
            margin-top: .25rem
        }

        @media (max-width: 720px) {
            .coa-hide-sm {
                display: none
            }

            .coa-top {
                flex-direction: column;
                align-items: stretch
            }

            .coa-actions {
                justify-content: flex-start
            }
        }
    </style>
@endpush

@section('content')
    @php
        $sum = function ($accounts, $fn) {
            $t = 0.0;
            foreach ($accounts as $a) {
                $t += (float) $fn($a);
            }
            return $t;
        };

        $cashTotal = $sum($accounts, fn($a) => $a->is_cash ? (float) ($a->balance ?? 0) : 0);
        $liabTotal = $sum($accounts, fn($a) => $a->type === 'liability' ? (float) ($a->balance ?? 0) : 0);
        $jago = (float) optional($accounts->firstWhere('code', '1111'))->balance;
        $hutangBorongan = (float) optional($accounts->firstWhere('code', '2102'))->balance;
    @endphp

    <div class="coa-wrap">
        <div class="coa-top">
            <div>
                <h1 class="coa-title">Chart of Accounts</h1>
                <div class="coa-sub">Saldo real-time dari jurnal (void tidak dihitung). Klik akun untuk buka Ledger.</div>
            </div>

            <form class="coa-actions" method="GET" action="{{ route('accounting.accounts.index') }}">
                <select name="type" class="coa-sel">
                    <option value="">All Types</option>
                    @foreach (['asset', 'liability', 'equity', 'revenue', 'expense'] as $t)
                        <option value="{{ $t }}" @selected(request('type') === $t)>{{ strtoupper($t) }}</option>
                    @endforeach
                </select>

                <select name="active" class="coa-sel">
                    <option value="">All</option>
                    <option value="1" @selected(request('active') === '1')>Active</option>
                    <option value="0" @selected(request('active') === '0')>Inactive</option>
                </select>

                <button class="coa-btn" type="submit">Filter</button>
                @if (request()->filled('type') || request()->filled('active'))
                    <a class="coa-btn" href="{{ route('accounting.accounts.index') }}">Reset</a>
                @endif

                <a class="coa-btn primary" href="{{ route('accounting.accounts.create') }}">＋ New</a>
            </form>
        </div>

        {{-- KPI Summary --}}
        <div class="coa-grid">
            <div class="coa-kpi">
                <div class="k">Total Cash/Bank</div>
                <div class="v {{ $cashTotal < 0 ? 'neg' : 'pos' }}">{{ number_format($cashTotal, 0, ',', '.') }}</div>
                <div class="s">Kas + semua bank</div>
            </div>

            <div class="coa-kpi">
                <div class="k">Bank Jago (1111)</div>
                <div class="v {{ $jago < 0 ? 'neg' : 'pos' }}">{{ number_format($jago, 0, ',', '.') }}</div>
                <div class="s">Saldo pusat bisnis</div>
            </div>

            <div class="coa-kpi">
                <div class="k">Hutang Borongan (2102)</div>
                <div class="v {{ $hutangBorongan < 0 ? 'neg' : 'pos' }}">{{ number_format($hutangBorongan, 0, ',', '.') }}
                </div>
                <div class="s">Naik saat FINAL, turun saat BAYAR</div>
            </div>

            <div class="coa-kpi">
                <div class="k">Total Liability</div>
                <div class="v {{ $liabTotal < 0 ? 'neg' : 'pos' }}">{{ number_format($liabTotal, 0, ',', '.') }}</div>
                <div class="s">Total hutang</div>
            </div>
        </div>

        {{-- Table --}}
        <div class="coa-card">
            <table class="coa-table">
                <thead>
                    <tr>
                        <th style="width:90px">Kode</th>
                        <th>Akun</th>
                        <th class="coa-hide-sm" style="width:110px">Tipe</th>
                        <th class="coa-hide-sm" style="width:130px">Kategori</th>
                        <th class="coa-hide-sm" style="width:90px">Status</th>
                        <th style="text-align:right;width:160px">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $acc)
                        @php
                            $balance = (float) ($acc->balance ?? 0);
                            $isKeyCash = $acc->is_cash;
                            $isPayable = $acc->code === '2102';
                            $isJago = $acc->code === '1111';
                        @endphp

                        <tr>
                            <td class="coa-code">{{ $acc->code }}</td>

                            <td>
                                <div class="coa-name">
                                    <a href="{{ route('accounting.accounts.ledger', $acc) }}">
                                        {{ $acc->name }}
                                    </a>
                                </div>

                                <div class="coa-rowhint">
                                    @if ($isKeyCash)
                                        <span class="coa-chip info">Cash/Bank</span>
                                    @endif
                                    @if ($isJago)
                                        <span class="coa-chip ok">Primary</span>
                                    @endif
                                    @if ($isPayable)
                                        <span class="coa-chip warn">Payroll Payable</span>
                                    @endif
                                </div>
                            </td>

                            <td class="coa-hide-sm">
                                <span class="coa-type">{{ strtoupper($acc->type) }}</span>
                            </td>

                            <td class="coa-hide-sm">
                                {{ $acc->is_cash ? 'Cash & Bank' : 'Non Cash' }}
                            </td>

                            <td class="coa-hide-sm">
                                {{ $acc->is_active ? 'Active' : 'Inactive' }}
                            </td>

                            <td class="coa-balance {{ $balance < 0 ? 'neg' : 'pos' }}">
                                {{ number_format($balance, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:1rem;color:var(--muted)">
                                Tidak ada akun.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
