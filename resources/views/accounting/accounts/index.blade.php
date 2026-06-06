@extends('layouts.app')

@section('title', 'Accounting • Accounts')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $typeLabels = [
        'asset' => 'Aset',
        'liability' => 'Hutang',
        'equity' => 'Modal',
        'revenue' => 'Pendapatan',
        'expense' => 'Biaya',
    ];

    $sum = function ($accounts, $fn) {
        $total = 0.0;
        foreach ($accounts as $account) {
            $total += (float) $fn($account);
        }
        return $total;
    };

    $cashTotal = $sum($accounts, fn ($a) => $a->is_cash ? (float) ($a->balance ?? 0) : 0);
    $expenseTotal = $sum($accounts, fn ($a) => $a->type === 'expense' ? (float) ($a->balance ?? 0) : 0);
    $activeCount = $accounts->where('is_active', true)->count();
    $cashCount = $accounts->where('is_cash', true)->count();
    $jago = (float) optional($accounts->firstWhere('code', '1111'))->balance;
    $mode = $mode ?? request('mode', 'cash_basis');
    $modeLabels = [
        'cash_basis' => 'Cash Basis',
        'all' => 'Semua Akun',
        'technical' => 'Akun Teknis',
    ];
    $modeHelp = [
        'cash_basis' => 'Default ringkas untuk operasional harian: kas/bank, modal, penjualan, HPP, dan biaya.',
        'all' => 'Menampilkan semua akun, termasuk akun teknis untuk jurnal persediaan, piutang, PPN, dan uang muka.',
        'technical' => 'Akun pendukung sistem. Biasanya tidak perlu dipakai untuk input harian cash basis.',
    ];
    $sourceLabels = [
        'grn' => 'GRN Expense',
        'grn_inv' => 'GRN Persediaan',
        'grn_exp' => 'GRN Biaya',
        'purchase_payment' => 'Bayar Supplier',
        'purchase_return_post' => 'Retur Pembelian',
        'purchase_return_inv' => 'Retur Persediaan',
        'purchase_return_exp' => 'Retur Biaya',
        'cash_expense' => 'Pengeluaran Kas',
        'cash_expense_void' => 'Void Pengeluaran',
        'cash_receipt' => 'Penerimaan Kas',
        'cash_receipt_void' => 'Void Penerimaan',
        'opening_balance' => 'Saldo Awal',
        'opening_balance_batch' => 'Saldo Awal Batch',
        'piecework_payroll' => 'Payroll Borongan',
    ];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .coa-page { display: grid; gap: 1rem; }
        .coa-header-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .coa-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            min-height: 40px; padding: .55rem .95rem; border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, .10); background: #fff;
            color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850;
        }
        .coa-btn:hover { color: #0f172a; background: #f8fafc; }
        .coa-btn-primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .coa-btn-primary:hover { background: #1e293b; color: #fff; }
        .coa-kpi-grid {
            display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
        }
        .coa-kpi {
            border: 1px solid rgba(15, 23, 42, .08); border-radius: 12px;
            background: #fff; padding: .85rem .95rem;
        }
        .coa-kpi-label {
            color: #64748b; font-size: .68rem; font-weight: 900;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .coa-kpi-value { margin-top: .18rem; color: #0f172a; font-size: 1.22rem; font-weight: 950; line-height: 1.15; }
        .coa-kpi-value.pos { color: #166534; }
        .coa-kpi-value.neg { color: #b91c1c; }
        .coa-kpi-note { margin-top: .2rem; color: #94a3b8; font-size: .74rem; }
        .coa-mode-tabs {
            display: flex; gap: .45rem; flex-wrap: wrap; align-items: center;
            padding: .35rem; border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 999px; background: #fff;
        }
        .coa-mode-tab {
            display: inline-flex; align-items: center; justify-content: center;
            min-height: 34px; padding: .42rem .8rem; border-radius: 999px;
            color: #475569; text-decoration: none; font-size: .78rem; font-weight: 880;
        }
        .coa-mode-tab:hover { color: #0f172a; background: #f8fafc; }
        .coa-mode-tab.is-active { color: #fff; background: #0f172a; }
        .coa-cash-note {
            display: flex; gap: .6rem; align-items: flex-start;
            border: 1px solid rgba(37, 99, 235, .14); border-radius: 12px;
            background: #eff6ff; color: #1e3a8a; padding: .75rem .85rem;
            font-size: .84rem; font-weight: 720;
        }
        .coa-cash-note b { color: #1e40af; }
        .coa-filter {
            display: grid;
            grid-template-columns: minmax(160px, .9fr) minmax(140px, .7fr) auto auto;
            gap: .55rem; align-items: end;
        }
        .coa-filter .form-select {
            min-height: 40px; border-radius: 999px; border-color: rgba(15, 23, 42, .12);
            font-size: .84rem; font-weight: 760; box-shadow: none;
        }
        .coa-filter-actions { display: flex; gap: .45rem; }
        .coa-table-wrap { max-height: calc(100vh - 340px); overflow: auto; -webkit-overflow-scrolling: touch; }
        .coa-table th, .coa-table td { vertical-align: middle; }
        .coa-code {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 62px; padding: .18rem .5rem; border-radius: 999px;
            background: #f1f5f9; color: #0f172a; font-weight: 900; font-variant-numeric: tabular-nums;
        }
        .coa-name { color: #0f172a; font-weight: 850; text-decoration: none; }
        .coa-name:hover { color: #1d4ed8; }
        .coa-meta { margin-top: .16rem; color: #64748b; font-size: .78rem; }
        .coa-type {
            display: inline-flex; border-radius: 999px; padding: .22rem .6rem;
            background: #f8fafc; border: 1px solid #e2e8f0; color: #475569;
            font-size: .74rem; font-weight: 850; white-space: nowrap;
        }
        .coa-chip-row { display: flex; gap: .32rem; flex-wrap: wrap; margin-top: .32rem; }
        .coa-chip {
            display: inline-flex; align-items: center; border-radius: 999px;
            padding: .17rem .5rem; font-size: .72rem; font-weight: 820;
            border: 1px solid transparent; white-space: nowrap;
        }
        .coa-chip-info { color: #1d4ed8; background: #dbeafe; border-color: #bfdbfe; }
        .coa-chip-ok { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .coa-chip-warn { color: #b45309; background: #fef3c7; border-color: #fde68a; }
        .coa-chip-muted { color: #64748b; background: #f1f5f9; border-color: #e2e8f0; }
        .coa-source-stack { display: flex; flex-wrap: wrap; gap: .28rem; max-width: 260px; }
        .coa-source {
            display: inline-flex; align-items: center; gap: .25rem; border-radius: 999px;
            padding: .15rem .48rem; background: #f8fafc; border: 1px solid #e2e8f0;
            color: #475569; font-size: .68rem; font-weight: 840; white-space: nowrap;
        }
        .coa-source b { color: #0f172a; font-variant-numeric: tabular-nums; }
        .coa-lines {
            display: inline-flex; justify-content: flex-end; min-width: 42px;
            color: #475569; font-weight: 900; font-variant-numeric: tabular-nums;
        }
        .coa-status {
            display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px;
            padding: .22rem .6rem; font-size: .74rem; font-weight: 850; border: 1px solid transparent;
        }
        .coa-status::before { content: ''; width: 7px; height: 7px; border-radius: 999px; background: currentColor; }
        .coa-status-active { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .coa-status-inactive { color: #64748b; background: #f1f5f9; border-color: #e2e8f0; }
        .coa-num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 950; color: #0f172a; white-space: nowrap; }
        .coa-num.pos { color: #166534; }
        .coa-num.neg { color: #b91c1c; }
        .coa-mobile-list { display: none; }
        .coa-empty { text-align: center; color: #64748b; padding: 2.4rem 1rem; }
        @media (max-width: 768px) {
            .gf-master-header { padding: 12px 14px; border-radius: 14px; }
            .gf-master-title { font-size: 18px; }
            .gf-master-desc { font-size: 11.5px; }
            .gf-master-actions { flex: 1 1 100%; }
            .coa-header-actions { justify-content: stretch; }
            .coa-header-actions .coa-btn { flex: 1 1 auto; }
            .coa-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
            .coa-kpi { padding: .7rem .75rem; }
            .coa-kpi-value { font-size: 1.05rem; }
            .coa-mode-tabs { border-radius: 14px; }
            .coa-mode-tab { flex: 1 1 auto; }
            .coa-filter { grid-template-columns: 1fr 1fr; }
            .coa-filter-actions { grid-column: 1 / -1; }
            .coa-filter-actions .coa-btn { flex: 1 1 0; }
            .coa-table-wrap { display: none; }
            .coa-mobile-list { display: grid; gap: .62rem; }
            .coa-mobile-card {
                display: grid; gap: .55rem; padding: .8rem;
                border: 1px solid rgba(15, 23, 42, .08); border-radius: 10px; background: #fff;
                box-shadow: 0 6px 18px rgba(15, 23, 42, .04);
            }
            .coa-mobile-top { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; }
            .coa-mobile-title { min-width: 0; }
            .coa-mobile-balance { color: #0f172a; font-weight: 950; font-variant-numeric: tabular-nums; white-space: nowrap; }
            .coa-mobile-balance.pos { color: #166534; }
            .coa-mobile-balance.neg { color: #b91c1c; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Accounting"
        title="Accounts Cash Basis"
        :description="$modeHelp[$mode] ?? $modeHelp['cash_basis']">
        <x-slot:actions>
            <div class="coa-header-actions">
                <a class="coa-btn coa-btn-primary" href="{{ route('accounting.accounts.create') }}">+ Akun Baru</a>
            </div>
        </x-slot:actions>

        <div class="coa-page">
            <div class="coa-kpi-grid">
                <div class="coa-kpi">
                    <div class="coa-kpi-label">Total Cash/Bank</div>
                    <div class="coa-kpi-value {{ $cashTotal < 0 ? 'neg' : 'pos' }}">Rp {{ $fmt($cashTotal) }}</div>
                    <div class="coa-kpi-note">{{ $fmt($cashCount) }} akun kas/bank</div>
                </div>
                <div class="coa-kpi">
                    <div class="coa-kpi-label">Bank Jago 1111</div>
                    <div class="coa-kpi-value {{ $jago < 0 ? 'neg' : 'pos' }}">Rp {{ $fmt($jago) }}</div>
                    <div class="coa-kpi-note">saldo pusat bisnis</div>
                </div>
                <div class="coa-kpi">
                    <div class="coa-kpi-label">Total Biaya</div>
                    <div class="coa-kpi-value {{ $expenseTotal < 0 ? 'neg' : '' }}">Rp {{ $fmt($expenseTotal) }}</div>
                    <div class="coa-kpi-note">akun biaya yang tampil</div>
                </div>
                <div class="coa-kpi">
                    <div class="coa-kpi-label">{{ $modeLabels[$mode] ?? 'Akun Tampil' }}</div>
                    <div class="coa-kpi-value">{{ $fmt($activeCount) }}</div>
                    <div class="coa-kpi-note">
                        {{ $mode === 'cash_basis' ? 'dari ' . $fmt($allAccountsCount ?? $accounts->count()) . ' akun total' : 'akun aktif yang tampil' }}
                    </div>
                </div>
            </div>

            <div class="coa-mode-tabs" aria-label="Mode akun">
                <a class="coa-mode-tab {{ $mode === 'cash_basis' ? 'is-active' : '' }}"
                    href="{{ route('accounting.accounts.index', ['mode' => 'cash_basis']) }}">
                    Cash Basis
                </a>
                <a class="coa-mode-tab {{ $mode === 'all' ? 'is-active' : '' }}"
                    href="{{ route('accounting.accounts.index', ['mode' => 'all']) }}">
                    Semua Akun
                </a>
                <a class="coa-mode-tab {{ $mode === 'technical' ? 'is-active' : '' }}"
                    href="{{ route('accounting.accounts.index', ['mode' => 'technical']) }}">
                    Akun Teknis
                </a>
            </div>

            @if ($mode === 'cash_basis')
                <div class="coa-cash-note">
                    <span>i</span>
                    <div>
                        <b>Mode cash basis aktif.</b>
                        Akun teknis seperti persediaan, piutang, PPN, uang muka, dan retur supplier disembunyikan dari daftar utama supaya COA lebih ringkas.
                    </div>
                </div>
            @endif

            <x-gf.panel title="Daftar Akun" subtitle="{{ $mode === 'cash_basis' ? 'Akun inti untuk transaksi kas harian.' : 'Filter berdasarkan tipe akun atau status aktif.' }}">
                <form class="coa-filter mb-3" method="GET" action="{{ route('accounting.accounts.index') }}">
                    <input type="hidden" name="mode" value="{{ $mode }}">

                    <select name="type" class="form-select" aria-label="Tipe akun">
                        <option value="">Semua tipe</option>
                        @foreach ($typeLabels as $type => $label)
                            <option value="{{ $type }}" @selected(request('type') === $type)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <select name="active" class="form-select" aria-label="Status akun">
                        <option value="">Semua status</option>
                        <option value="1" @selected(request('active') === '1')>Aktif</option>
                        <option value="0" @selected(request('active') === '0')>Nonaktif</option>
                    </select>

                    <div class="coa-filter-actions">
                        <button class="coa-btn" type="submit">Filter</button>
                        <a class="coa-btn" href="{{ route('accounting.accounts.index', ['mode' => $mode]) }}">Reset</a>
                    </div>
                </form>

                @if ($accounts->isEmpty())
                    <div class="coa-empty">Tidak ada akun.</div>
                @else
                    <div class="coa-table-wrap">
                        <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Akun</th>
                                    <th>Tipe</th>
                                    <th>Kategori</th>
                                    <th>Status</th>
                                    <th>Asal Data</th>
                                    <th class="coa-num">Baris</th>
                                    <th class="coa-num">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($accounts as $account)
                                    @php
                                        $balance = (float) ($account->balance ?? 0);
                                        $isJago = $account->code === '1111';
                                        $isPayable = $account->code === '2102';
                                        $sources = ($journalSources ?? collect())->get($account->id, collect());
                                        $lineCount = (int) (($journalLineCounts ?? collect())->get($account->id, 0));
                                    @endphp
                                    <tr>
                                        <td><span class="coa-code">{{ $account->code }}</span></td>
                                        <td>
                                            <a class="coa-name" href="{{ route('accounting.accounts.ledger', $account) }}">
                                                {{ $account->name }}
                                            </a>
                                            <div class="coa-chip-row">
                                                @if ($account->is_cash)
                                                    <span class="coa-chip coa-chip-info">Cash/Bank</span>
                                                @endif
                                                @if ($isJago)
                                                    <span class="coa-chip coa-chip-ok">Primary</span>
                                                @endif
                                                @if ($isPayable)
                                                    <span class="coa-chip coa-chip-warn">Payroll Payable</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td><span class="coa-type">{{ $typeLabels[$account->type] ?? strtoupper($account->type) }}</span></td>
                                        <td>{{ $account->is_cash ? 'Cash & Bank' : 'Non Cash' }}</td>
                                        <td>
                                            <span class="coa-status {{ $account->is_active ? 'coa-status-active' : 'coa-status-inactive' }}">
                                                {{ $account->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($sources->isEmpty())
                                                <span class="coa-chip coa-chip-muted">Bersih</span>
                                            @else
                                                <div class="coa-source-stack">
                                                    @foreach ($sources->take(3) as $source)
                                                        <span class="coa-source">
                                                            {{ $sourceLabels[$source->source_type] ?? ($source->source_type ?: 'Manual') }}
                                                            <b>{{ $fmt($source->line_count) }}</b>
                                                        </span>
                                                    @endforeach
                                                    @if ($sources->count() > 3)
                                                        <span class="coa-source">+{{ $sources->count() - 3 }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="coa-num"><span class="coa-lines">{{ $fmt($lineCount) }}</span></td>
                                        <td class="coa-num {{ $balance < 0 ? 'neg' : 'pos' }}">Rp {{ $fmt($balance) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="coa-mobile-list">
                        @foreach ($accounts as $account)
                            @php
                                $balance = (float) ($account->balance ?? 0);
                                $isJago = $account->code === '1111';
                                $isPayable = $account->code === '2102';
                                $sources = ($journalSources ?? collect())->get($account->id, collect());
                                $lineCount = (int) (($journalLineCounts ?? collect())->get($account->id, 0));
                            @endphp
                            <div class="coa-mobile-card">
                                <div class="coa-mobile-top">
                                    <div class="coa-mobile-title">
                                        <span class="coa-code">{{ $account->code }}</span>
                                        <div class="mt-2">
                                            <a class="coa-name" href="{{ route('accounting.accounts.ledger', $account) }}">
                                                {{ $account->name }}
                                            </a>
                                            <div class="coa-meta">{{ $typeLabels[$account->type] ?? strtoupper($account->type) }}</div>
                                        </div>
                                    </div>
                                    <div class="coa-mobile-balance {{ $balance < 0 ? 'neg' : 'pos' }}">Rp {{ $fmt($balance) }}</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <span class="coa-status {{ $account->is_active ? 'coa-status-active' : 'coa-status-inactive' }}">
                                        {{ $account->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                    <span class="coa-chip {{ $account->is_cash ? 'coa-chip-info' : 'coa-chip-muted' }}">
                                        {{ $account->is_cash ? 'Cash/Bank' : 'Non Cash' }}
                                    </span>
                                </div>
                                @if ($isJago || $isPayable)
                                    <div class="coa-chip-row">
                                        @if ($isJago)
                                            <span class="coa-chip coa-chip-ok">Primary</span>
                                        @endif
                                        @if ($isPayable)
                                            <span class="coa-chip coa-chip-warn">Payroll Payable</span>
                                        @endif
                                    </div>
                                @endif
                                <div class="coa-chip-row">
                                    <span class="coa-chip coa-chip-muted">{{ $fmt($lineCount) }} baris jurnal</span>
                                    @forelse ($sources->take(2) as $source)
                                        <span class="coa-source">
                                            {{ $sourceLabels[$source->source_type] ?? ($source->source_type ?: 'Manual') }}
                                            <b>{{ $fmt($source->line_count) }}</b>
                                        </span>
                                    @empty
                                        <span class="coa-chip coa-chip-muted">Bersih</span>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection

<style id="coa-clean-ui-patch">
    .coa-card,
    .coa-panel,
    .card {
        border: 1px solid rgba(15, 23, 42, .08) !important;
        border-radius: 20px !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, .06) !important;
        overflow: hidden;
    }

    .coa-page h1,
    .coa-wrap h1,
    .coa-shell h1 {
        font-size: 24px !important;
        font-weight: 800 !important;
        letter-spacing: -.03em;
        margin-bottom: 6px !important;
        color: #0f172a !important;
    }

    .coa-page h2,
    .coa-wrap h2,
    .coa-shell h2,
    .coa-card h2,
    .coa-panel h2 {
        font-size: 17px !important;
        font-weight: 800 !important;
        margin-bottom: 12px !important;
        color: #0f172a !important;
    }

    .coa-page p,
    .coa-wrap p,
    .coa-shell p,
    .coa-card p,
    .coa-panel p,
    .text-muted,
    small {
        font-size: 12px !important;
        line-height: 1.45 !important;
        color: #64748b !important;
    }

    .coa-card .text-muted,
    .coa-panel .text-muted,
    .coa-card .form-text,
    .coa-panel .form-text {
        display: none !important;
    }

    .coa-filter {
        display: grid !important;
        grid-template-columns: minmax(220px, 1.4fr) minmax(150px, .7fr) minmax(150px, .7fr) auto auto;
        gap: 10px !important;
        align-items: end !important;
        padding: 14px !important;
        border: 1px solid rgba(15, 23, 42, .08) !important;
        border-radius: 18px !important;
        background: #f8fafc !important;
        margin-bottom: 16px !important;
    }

    .coa-filter label {
        font-size: 11px !important;
        font-weight: 800 !important;
        color: #475569 !important;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 6px !important;
    }

    .coa-filter input,
    .coa-filter select,
    .form-control,
    .form-select {
        min-height: 42px !important;
        border-radius: 14px !important;
        border: 1px solid rgba(148, 163, 184, .32) !important;
        font-size: 13px !important;
        box-shadow: none !important;
    }

    .coa-btn,
    .btn {
        min-height: 40px !important;
        border-radius: 14px !important;
        font-size: 13px !important;
        font-weight: 800 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        padding: 9px 14px !important;
        line-height: 1 !important;
        white-space: nowrap !important;
    }

    .btn-primary,
    .coa-btn-primary {
        background: #2563eb !important;
        border-color: #2563eb !important;
        color: white !important;
    }

    .coa-tabs,
    .nav-tabs {
        display: flex !important;
        gap: 8px !important;
        border: 0 !important;
        margin-bottom: 16px !important;
        flex-wrap: wrap;
    }

    .coa-tabs a,
    .nav-tabs .nav-link {
        border: 1px solid rgba(15, 23, 42, .08) !important;
        border-radius: 999px !important;
        background: white !important;
        color: #475569 !important;
        font-size: 13px !important;
        font-weight: 800 !important;
        padding: 9px 14px !important;
    }

    .coa-tabs a.active,
    .coa-tabs a.is-active,
    .nav-tabs .nav-link.active {
        background: #dbeafe !important;
        color: #1d4ed8 !important;
        border-color: rgba(37, 99, 235, .22) !important;
    }

    .table thead th {
        background: #f8fafc !important;
        color: #475569 !important;
        font-size: 11px !important;
        font-weight: 900 !important;
        text-transform: uppercase;
        letter-spacing: .04em;
        border-bottom: 1px solid rgba(15, 23, 42, .08) !important;
        white-space: nowrap;
    }

    .table tbody td {
        font-size: 13px !important;
        color: #0f172a !important;
        vertical-align: middle !important;
        border-color: rgba(15, 23, 42, .06) !important;
    }

    .table tbody tr:hover {
        background: #f8fafc !important;
    }

    .badge,
    .coa-badge {
        border-radius: 999px !important;
        padding: 6px 10px !important;
        font-size: 11px !important;
        font-weight: 800 !important;
    }

    .table td:last-child,
    .table th:last-child {
        text-align: right !important;
        white-space: nowrap !important;
    }

    .table td:last-child .btn,
    .table td:last-child .coa-btn,
    .table td:last-child a,
    .table td:last-child form {
        display: inline-flex !important;
        margin-left: 4px !important;
        vertical-align: middle !important;
    }

    @media (max-width: 992px) {
        .coa-filter {
            grid-template-columns: 1fr 1fr !important;
        }
    }

    @media (max-width: 576px) {
        .coa-filter {
            grid-template-columns: 1fr !important;
        }

        .coa-page h1,
        .coa-wrap h1,
        .coa-shell h1 {
            font-size: 21px !important;
        }
    }
</style>

<style id="coa-hide-source-column">
    /* Sembunyikan kolom asal data agar halaman Accounts lebih clean */
    .table th:nth-child(6),
    .table td:nth-child(6) {
        display: none !important;
    }

    .coa-source,
    .coa-source-list,
    .coa-source-wrap,
    .coa-mobile-source,
    .coa-mobile-sources {
        display: none !important;
    }
</style>

<style id="coa-expense-group-style">
    .coa-expense-group-row td {
        background: #f8fafc !important;
        border-top: 1px solid rgba(15, 23, 42, .08) !important;
        border-bottom: 1px solid rgba(15, 23, 42, .08) !important;
    }

    .coa-expense-group {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 4px;
    }

    .coa-expense-group-title {
        font-size: 14px;
        font-weight: 900;
        color: #0f172a;
        margin-bottom: 5px;
    }

    .coa-expense-group-desc {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .coa-expense-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 5px 9px;
        background: #e0f2fe;
        color: #075985;
        font-size: 11px;
        font-weight: 800;
    }

    .coa-expense-group-balance {
        font-size: 14px;
        font-weight: 900;
        color: #b91c1c;
        white-space: nowrap;
    }

    @media (max-width: 768px) {
        .coa-expense-group {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<script id="coa-expense-group-script">
document.addEventListener('DOMContentLoaded', function () {
    const expenseCodes = ['6102', '6103', '6110', '6201', '6202'];
    const importantLabels = ['Transport / Ongkir', 'Gaji Operasional', 'Packing', 'Marketplace'];
    const rows = Array.from(document.querySelectorAll('table tbody tr'));

    let targetRows = [];
    let total = 0;

    rows.forEach(function (row) {
        const firstCell = row.querySelector('td');
        if (!firstCell) return;

        const code = firstCell.innerText.trim().match(/\d{4}/)?.[0];
        if (!expenseCodes.includes(code)) return;

        targetRows.push(row);

        const balanceCell = row.querySelector('td:nth-last-child(2), td:last-child');
        if (balanceCell) {
            const raw = balanceCell.innerText.replace(/[^\d,-]/g, '').replace(/\./g, '').replace(',', '.');
            const value = parseFloat(raw);
            if (!Number.isNaN(value)) total += value;
        }
    });

    if (!targetRows.length) return;

    const firstRow = targetRows[0];
    const colCount = firstRow.children.length || 6;

    const groupRow = document.createElement('tr');
    groupRow.className = 'coa-expense-group-row';
    groupRow.innerHTML = `
        <td colspan="${colCount}">
            <div class="coa-expense-group">
                <div>
                    <div class="coa-expense-group-title">Biaya Operasional</div>
                    <div class="coa-expense-group-desc">
                        ${importantLabels.map(label => `<span class="coa-expense-chip">${label}</span>`).join('')}
                        <span class="coa-expense-chip">+1 lainnya</span>
                    </div>
                </div>
                <div class="coa-expense-group-balance">
                    ${total ? 'Rp ' + new Intl.NumberFormat('id-ID').format(total) : 'Lihat detail'}
                </div>
            </div>
        </td>
    `;

    firstRow.parentNode.insertBefore(groupRow, firstRow);

    targetRows.forEach(function (row) {
        row.style.display = 'none';
    });
});
</script>
