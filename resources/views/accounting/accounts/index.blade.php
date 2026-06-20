@extends('layouts.app')

@section('title', 'Chart of Accounts')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $typeLabel = [
        'asset'     => ['label' => 'Aset',       'color' => '#1d4ed8', 'bg' => '#dbeafe'],
        'liability' => ['label' => 'Hutang',      'color' => '#b91c1c', 'bg' => '#fee2e2'],
        'equity'    => ['label' => 'Modal',       'color' => '#7c3aed', 'bg' => '#ede9fe'],
        'revenue'   => ['label' => 'Pendapatan',  'color' => '#166534', 'bg' => '#dcfce7'],
        'expense'   => ['label' => 'Biaya',       'color' => '#92400e', 'bg' => '#fef3c7'],
    ];

    // Group accounts by type in COA order
    $typeOrder = ['asset', 'liability', 'equity', 'revenue', 'expense'];
    $filterType = request('type', '');
    $grouped = $accounts->groupBy('type');
@endphp

@push('head')
<style>
.coa-wrap { max-width: 900px; margin: 0 auto; display: grid; gap: 1.5rem; }

/* Header */
.coa-topbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.coa-title  { font-size: 1.35rem; font-weight: 950; color: #0f172a; letter-spacing: -.025em; }
.coa-add-btn {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .5rem 1rem; border-radius: 999px;
    background: #0f172a; color: #fff; font-size: .84rem; font-weight: 850;
    text-decoration: none; border: none; cursor: pointer;
}
.coa-add-btn:hover { background: #1e293b; color: #fff; }

/* Type filter pills */
.coa-filter-pills { display: flex; gap: .4rem; flex-wrap: wrap; }
.coa-pill {
    display: inline-flex; align-items: center; padding: .38rem .85rem;
    border-radius: 999px; font-size: .78rem; font-weight: 850;
    text-decoration: none; border: 1.5px solid transparent;
    color: #475569; background: #fff; border-color: #e2e8f0;
    transition: all .12s;
}
.coa-pill:hover  { background: #f8fafc; color: #0f172a; }
.coa-pill.active { background: #0f172a; color: #fff; border-color: #0f172a; }

/* Group block */
.coa-group { display: grid; gap: 0; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; background: #fff; }
.coa-group-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: .65rem 1rem; background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.coa-group-label {
    display: inline-flex; align-items: center; gap: .5rem;
    font-size: .8rem; font-weight: 900; letter-spacing: .04em; text-transform: uppercase;
}
.coa-group-badge {
    display: inline-flex; padding: .2rem .55rem; border-radius: 999px;
    font-size: .72rem; font-weight: 900;
}
.coa-group-total { font-size: .9rem; font-weight: 950; color: #0f172a; font-variant-numeric: tabular-nums; }

/* Account row — full row is a link */
.coa-row {
    display: grid;
    grid-template-columns: 64px 1fr auto auto;
    gap: 0 1rem;
    align-items: center;
    padding: .75rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    text-decoration: none;
    color: inherit;
    transition: background .1s;
}
.coa-row:last-child { border-bottom: none; }
.coa-row:hover { background: #f8fafc; }
.coa-row:hover .coa-row-name { color: #1d4ed8; }

.coa-row-code {
    font-size: .78rem; font-weight: 900; color: #64748b;
    font-variant-numeric: tabular-nums; letter-spacing: .02em;
}
.coa-row-name  { font-size: .88rem; font-weight: 850; color: #0f172a; }
.coa-row-meta  { font-size: .73rem; color: #94a3b8; margin-top: .1rem; }
.coa-row-txn   {
    font-size: .75rem; font-weight: 850; color: #94a3b8;
    font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap;
}
.coa-row-balance {
    font-size: .9rem; font-weight: 950; color: #0f172a;
    font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; min-width: 110px;
}
.coa-row-balance.pos { color: #166534; }
.coa-row-balance.neg { color: #b91c1c; }
.coa-inactive .coa-row-name { color: #94a3b8; text-decoration: line-through; }

/* chevron hint */
.coa-row::after {
    content: '›'; color: #cbd5e1; font-size: 1.1rem; line-height: 1;
    grid-column: -1; grid-row: 1 / -1; align-self: center;
    margin-left: .25rem;
}

/* Summary KPIs */
.coa-kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.coa-kpi {
    border: 1px solid #e2e8f0; border-radius: 14px; background: #fff;
    padding: .85rem 1rem;
}
.coa-kpi-label { font-size: .7rem; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; }
.coa-kpi-val   { font-size: 1.15rem; font-weight: 950; color: #0f172a; margin-top: .15rem; font-variant-numeric: tabular-nums; }
.coa-kpi-val.pos { color: #166534; }
.coa-kpi-val.neg { color: #b91c1c; }

@media (max-width: 640px) {
    .coa-row { grid-template-columns: 52px 1fr auto; }
    .coa-row-txn { display: none; }
    .coa-kpis { grid-template-columns: repeat(2, 1fr); }
    .coa-kpis .coa-kpi:last-child { display: none; }
    .coa-row-balance { min-width: 80px; font-size: .82rem; }
}
</style>
@endpush

@section('content')
<div class="container py-4">
<div class="coa-wrap">

    {{-- Top bar --}}
    <div class="coa-topbar">
        <span class="coa-title">Chart of Accounts</span>
        <a class="coa-add-btn" href="{{ route('accounting.accounts.create') }}">+ Akun Baru</a>
    </div>

    {{-- KPI strip --}}
    @php
        $totalAset     = $accounts->where('type','asset')->sum(fn($a) => (float)($a->balance ?? 0));
        $totalLiab     = $accounts->where('type','liability')->sum(fn($a) => (float)($a->balance ?? 0));
        $totalRevenue  = $accounts->where('type','revenue')->sum(fn($a) => (float)($a->balance ?? 0));
        $totalExpense  = $accounts->where('type','expense')->sum(fn($a) => (float)($a->balance ?? 0));
        $totalCash     = $accounts->where('is_cash', true)->sum(fn($a) => (float)($a->balance ?? 0));
    @endphp
    <div class="coa-kpis">
        <div class="coa-kpi">
            <div class="coa-kpi-label">Kas & Bank</div>
            <div class="coa-kpi-val {{ $totalCash < 0 ? 'neg' : 'pos' }}">Rp {{ $fmt($totalCash) }}</div>
        </div>
        <div class="coa-kpi">
            <div class="coa-kpi-label">Total Aset</div>
            <div class="coa-kpi-val {{ $totalAset < 0 ? 'neg' : 'pos' }}">Rp {{ $fmt($totalAset) }}</div>
        </div>
        <div class="coa-kpi">
            <div class="coa-kpi-label">Hutang</div>
            <div class="coa-kpi-val {{ $totalLiab > 0 ? 'neg' : '' }}">Rp {{ $fmt($totalLiab) }}</div>
        </div>
    </div>

    {{-- Type filter pills --}}
    <div class="coa-filter-pills">
        <a class="coa-pill {{ $filterType === '' ? 'active' : '' }}"
           href="{{ route('accounting.accounts.index') }}">Semua</a>
        @foreach ($typeOrder as $t)
            @if (isset($typeLabel[$t]))
                <a class="coa-pill {{ $filterType === $t ? 'active' : '' }}"
                   href="{{ route('accounting.accounts.index', ['type' => $t]) }}">
                    {{ $typeLabel[$t]['label'] }}
                </a>
            @endif
        @endforeach
    </div>

    {{-- Account groups --}}
    @forelse ($typeOrder as $groupType)
        @php
            $groupAccounts = ($grouped[$groupType] ?? collect());
            if ($filterType && $filterType !== $groupType) continue;
            if ($groupAccounts->isEmpty()) continue;
            $meta = $typeLabel[$groupType] ?? ['label' => strtoupper($groupType), 'color' => '#475569', 'bg' => '#f1f5f9'];
            $groupTotal = $groupAccounts->sum(fn($a) => (float)($a->balance ?? 0));
        @endphp
        <div class="coa-group">
            <div class="coa-group-header">
                <span class="coa-group-label">
                    <span class="coa-group-badge"
                          style="color:{{ $meta['color'] }};background:{{ $meta['bg'] }}">
                        {{ $meta['label'] }}
                    </span>
                    <span style="font-size:.78rem;color:#94a3b8;font-weight:700">
                        {{ $groupAccounts->count() }} akun
                    </span>
                </span>
                <span class="coa-group-total {{ $groupTotal < 0 ? 'neg' : '' }}"
                      style="{{ $groupTotal < 0 ? 'color:#b91c1c' : 'color:#166534' }}">
                    Rp {{ $fmt($groupTotal) }}
                </span>
            </div>

            @foreach ($groupAccounts as $account)
                @php
                    $bal      = (float)($account->balance ?? 0);
                    $txnCount = (int)($journalLineCounts[$account->id] ?? 0);
                @endphp
                <a class="coa-row {{ !$account->is_active ? 'coa-inactive' : '' }}"
                   href="{{ route('accounting.accounts.ledger', $account) }}">
                    <span class="coa-row-code">{{ $account->code }}</span>
                    <span>
                        <div class="coa-row-name">{{ $account->name }}</div>
                        @if (!$account->is_active)
                            <div class="coa-row-meta">Nonaktif</div>
                        @elseif ($account->is_cash)
                            <div class="coa-row-meta">Kas / Bank</div>
                        @endif
                    </span>
                    <span class="coa-row-txn">
                        @if ($txnCount > 0)
                            {{ number_format($txnCount) }} transaksi
                        @else
                            —
                        @endif
                    </span>
                    <span class="coa-row-balance {{ $bal < 0 ? 'neg' : ($bal > 0 ? 'pos' : '') }}">
                        Rp {{ $fmt($bal) }}
                    </span>
                </a>
            @endforeach
        </div>
    @empty
        <p class="text-muted text-center py-4">Tidak ada akun.</p>
    @endforelse

</div>
</div>
@endsection
