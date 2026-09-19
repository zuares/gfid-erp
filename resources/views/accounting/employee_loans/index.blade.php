@extends('layouts.app')

@section('title', 'Accounting • Pinjaman Karyawan')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $labels = ['draft' => 'Draft', 'posted' => 'Posted', 'settled' => 'Lunas', 'void' => 'Void'];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .el-wrap { display: grid; gap: 1rem; }
        .el-actions { display: flex; justify-content: flex-end; align-items: center; gap: .55rem; flex-wrap: wrap; }
        .el-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-height: 40px; padding: .55rem .9rem; border: 1px solid #dbe3ee; border-radius: 999px; background: #fff; color: #0f172a; text-decoration: none; font-size: .82rem; font-weight: 850; transition: .18s ease; }
        .el-btn:hover { border-color: #94a3b8; color: #0f172a; transform: translateY(-1px); }
        .el-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .el-btn span { color: inherit !important; }
        .el-btn-primary:hover { color: #fff; background: #1e293b; border-color: #1e293b; }
        .el-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
        .el-card { position: relative; overflow: hidden; min-height: 112px; border: 1px solid #e2e8f0; border-radius: 16px; background: #fff; padding: 1rem; box-shadow: 0 8px 24px rgba(15, 23, 42, .04); }
        .el-card::after { position: absolute; right: -22px; bottom: -28px; width: 88px; height: 88px; border-radius: 50%; background: rgba(226, 232, 240, .45); content: ''; }
        .el-card-blue::after { background: rgba(219, 234, 254, .75); }
        .el-card-green::after { background: rgba(220, 252, 231, .75); }
        .el-card-amber::after { background: rgba(254, 243, 199, .8); }
        .el-card-slate::after { background: rgba(226, 232, 240, .8); }
        .el-card-top { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .el-card-icon { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 10px; color: #334155; background: #f1f5f9; font-size: .85rem; }
        .el-card-blue .el-card-icon { color: #1d4ed8; background: #eff6ff; }
        .el-card-green .el-card-icon { color: #15803d; background: #f0fdf4; }
        .el-card-amber .el-card-icon { color: #b45309; background: #fffbeb; }
        .el-card-label { color: #64748b; font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: .045em; }
        .el-card-value { position: relative; z-index: 1; margin-top: .55rem; color: #0f172a; font-size: 1.2rem; font-weight: 950; letter-spacing: -.025em; }
        .el-filter { display: grid; grid-template-columns: 1.25fr 1fr 1fr 1fr auto auto; align-items: end; gap: .7rem; }
        .el-filter-field { display: grid; gap: .3rem; min-width: 0; }
        .el-filter-label { color: #64748b; font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; }
        .el-control { min-height: 42px; border-radius: 11px; border-color: #dbe3ee; box-shadow: none; }
        .el-control:focus { border-color: #64748b; box-shadow: 0 0 0 .2rem rgba(15, 23, 42, .07); }
        .el-filter-actions { display: flex; gap: .45rem; }
        .el-table-wrap { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 14px; }
        .el-table { min-width: 760px; margin: 0; }
        .el-table th { color: #64748b; background: #f8fafc; font-size: .68rem; text-transform: uppercase; letter-spacing: .045em; white-space: nowrap; }
        .el-table td { vertical-align: middle; }
        .el-table tbody tr:hover { background: #f8fafc; }
        .el-link { color: #0f172a; font-weight: 850; text-decoration: none; }
        .el-link:hover { color: #2563eb; }
        .el-status { display: inline-flex; align-items: center; gap: .25rem; padding: .25rem .58rem; border-radius: 999px; font-size: .7rem; font-weight: 850; white-space: nowrap; }
        .el-status::before { width: 5px; height: 5px; border-radius: 50%; background: currentColor; content: ''; }
        .el-status-draft { color: #92400e; background: #fef3c7; }
        .el-status-posted { color: #166534; background: #dcfce7; }
        .el-status-settled { color: #075985; background: #e0f2fe; }
        .el-status-void { color: #991b1b; background: #fee2e2; }
        .el-mobile-list { display: none; }
        .el-mobile-card { display: block; color: inherit; text-decoration: none; border: 1px solid #e2e8f0; border-radius: 15px; background: #fff; padding: .9rem; box-shadow: 0 8px 22px rgba(15, 23, 42, .04); }
        .el-mobile-card + .el-mobile-card { margin-top: .65rem; }
        .el-mobile-card:hover { color: inherit; border-color: #94a3b8; }
        .el-mobile-head, .el-mobile-foot { display: flex; align-items: center; justify-content: space-between; gap: .6rem; }
        .el-mobile-name { min-width: 0; color: #0f172a; font-weight: 900; }
        .el-mobile-date, .el-mobile-code, .el-mobile-foot { color: #64748b; font-size: .73rem; }
        .el-mobile-code { margin-top: .12rem; }
        .el-mobile-metrics { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .55rem; margin: .85rem 0; padding: .7rem 0; border-top: 1px solid #eef2f7; border-bottom: 1px solid #eef2f7; }
        .el-mobile-metric-label { display: block; color: #94a3b8; font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; }
        .el-mobile-metric-value { display: block; margin-top: .2rem; color: #0f172a; font-size: .8rem; font-weight: 900; }
        .el-mobile-foot strong { color: #2563eb; font-size: .74rem; }
        @media (max-width: 1050px) { .el-filter { grid-template-columns: repeat(3, minmax(0, 1fr)); } .el-filter-actions { grid-column: 1 / -1; } }
        @media (max-width: 900px) { .el-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 767px) {
            .el-actions, .el-actions .el-btn { width: 100%; }
            .el-filter { grid-template-columns: 1fr 1fr; gap: .65rem; }
            .el-filter-actions { display: grid; grid-template-columns: 1fr 1fr; grid-column: 1 / -1; }
            .el-filter-actions .el-btn { width: 100%; }
            .el-table-wrap { display: none; }
            .el-mobile-list { display: block; }
        }
        @media (max-width: 430px) {
            .el-summary { gap: .55rem; }
            .el-card { min-height: 100px; padding: .78rem; }
            .el-card-value { font-size: 1rem; }
            .el-card-icon { width: 26px; height: 26px; font-size: .75rem; }
            .el-filter { grid-template-columns: 1fr; }
            .el-filter-actions { grid-template-columns: 1fr 1fr; }
            .el-mobile-metrics { gap: .35rem; }
            .el-mobile-metric-value { font-size: .72rem; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page eyebrow="Accounting" title="Pinjaman Karyawan" description="Kelola pencairan, saldo piutang, dan pembayaran karyawan.">
        <x-slot:actions>
            <div class="el-actions">
                <a class="el-btn el-btn-primary" href="{{ route('accounting.employee-loans.create') }}"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Tambah Pinjaman</span></a>
            </div>
        </x-slot:actions>

        <div class="el-wrap">
            @if (session('message'))
                <div class="alert alert-{{ session('status') === 'error' ? 'danger' : 'success' }} mb-0">{{ session('message') }}</div>
            @endif

            <div class="el-summary" aria-label="Ringkasan pinjaman">
                <div class="el-card el-card-blue"><div class="el-card-top"><span class="el-card-label">Total dicairkan</span><span class="el-card-icon"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></span></div><div class="el-card-value">Rp {{ $fmt($summary['total_funded']) }}</div></div>
                <div class="el-card el-card-green"><div class="el-card-top"><span class="el-card-label">Sudah dibayar</span><span class="el-card-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span></div><div class="el-card-value">Rp {{ $fmt($summary['total_repaid']) }}</div></div>
                <div class="el-card el-card-amber"><div class="el-card-top"><span class="el-card-label">Sisa piutang</span><span class="el-card-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span></div><div class="el-card-value">Rp {{ $fmt($summary['outstanding']) }}</div></div>
                <div class="el-card el-card-slate"><div class="el-card-top"><span class="el-card-label">Draft</span><span class="el-card-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span></div><div class="el-card-value">{{ number_format($summary['draft_docs']) }}</div></div>
            </div>

            <x-gf.panel title="Filter pinjaman" subtitle="Persempit daftar berdasarkan karyawan, status, atau tanggal pencairan.">
                <form class="el-filter" method="GET" action="{{ route('accounting.employee-loans.index') }}">
                    <label class="el-filter-field"><span class="el-filter-label">Karyawan</span><select class="form-select el-control" name="employee_id"><option value="">Semua karyawan</option>@foreach ($employees as $employee)<option value="{{ $employee->id }}" @selected((string) request('employee_id') === (string) $employee->id)>{{ $employee->code }} · {{ $employee->name }}</option>@endforeach</select></label>
                    <label class="el-filter-field"><span class="el-filter-label">Status</span><select class="form-select el-control" name="status"><option value="">Semua status</option>@foreach ($labels as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select></label>
                    <label class="el-filter-field"><span class="el-filter-label">Dari tanggal</span><input class="form-control el-control" type="date" name="from" value="{{ request('from') }}"></label>
                    <label class="el-filter-field"><span class="el-filter-label">Sampai tanggal</span><input class="form-control el-control" type="date" name="to" value="{{ request('to') }}"></label>
                    <div class="el-filter-actions"><button class="el-btn el-btn-primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i><span>Terapkan</span></button><a class="el-btn" href="{{ route('accounting.employee-loans.index') }}">Reset</a></div>
                </form>
            </x-gf.panel>

            <x-gf.panel title="Daftar pinjaman karyawan" subtitle="Posting membentuk jurnal Dr Piutang Karyawan — Cr Kas/Bank.">
                <div class="el-table-wrap">
                    <table class="table el-table align-middle">
                        <thead><tr><th>Tanggal</th><th>Karyawan</th><th class="text-end">Pinjaman</th><th class="text-end">Sisa</th><th>Rencana angsuran</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @forelse ($loans as $loan)
                                @php $paid = (float) ($loan->posted_repayment_amount ?? 0); $remaining = max(0, (float) $loan->principal_amount - $paid); @endphp
                                <tr><td>{{ optional($loan->date)->format('d/m/Y') }}</td><td><a class="el-link" href="{{ route('accounting.employee-loans.show', $loan) }}">{{ $loan->employee?->name ?? '-' }}</a><div class="small text-muted">{{ $loan->employee?->code }}</div></td><td class="text-end fw-semibold">Rp {{ $fmt($loan->principal_amount) }}</td><td class="text-end fw-semibold">Rp {{ $fmt($remaining) }}</td><td>{{ $loan->installment_amount ? 'Rp '.$fmt($loan->installment_amount) : '—' }}{{ $loan->installment_count ? ' × '.$loan->installment_count : '' }}</td><td><span class="el-status el-status-{{ $loan->status }}">{{ $labels[$loan->status] ?? $loan->status }}</span></td><td class="text-end"><a class="el-btn" href="{{ route('accounting.employee-loans.show', $loan) }}">Detail <i class="bi bi-arrow-right" aria-hidden="true"></i></a></td></tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-inbox d-block fs-3 mb-2" aria-hidden="true"></i>Belum ada pinjaman karyawan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="el-mobile-list">
                    @forelse ($loans as $loan)
                        @php $paid = (float) ($loan->posted_repayment_amount ?? 0); $remaining = max(0, (float) $loan->principal_amount - $paid); @endphp
                        <a class="el-mobile-card" href="{{ route('accounting.employee-loans.show', $loan) }}"><div class="el-mobile-head"><div><div class="el-mobile-name">{{ $loan->employee?->name ?? '-' }}</div><div class="el-mobile-code">{{ $loan->employee?->code }} · {{ optional($loan->date)->format('d/m/Y') }}</div></div><span class="el-status el-status-{{ $loan->status }}">{{ $labels[$loan->status] ?? $loan->status }}</span></div><div class="el-mobile-metrics"><div><span class="el-mobile-metric-label">Pinjaman</span><strong class="el-mobile-metric-value">Rp {{ $fmt($loan->principal_amount) }}</strong></div><div><span class="el-mobile-metric-label">Sisa</span><strong class="el-mobile-metric-value">Rp {{ $fmt($remaining) }}</strong></div><div><span class="el-mobile-metric-label">Angsuran</span><strong class="el-mobile-metric-value">{{ $loan->installment_amount ? 'Rp '.$fmt($loan->installment_amount) : '—' }}</strong></div></div><div class="el-mobile-foot"><span>{{ $loan->installment_count ? $loan->installment_count.' kali angsuran' : 'Belum ada tenor' }}</span><strong>Lihat detail <i class="bi bi-arrow-right" aria-hidden="true"></i></strong></div></a>
                    @empty
                        <div class="text-center text-muted py-4"><i class="bi bi-inbox d-block fs-3 mb-2" aria-hidden="true"></i>Belum ada pinjaman karyawan.</div>
                    @endforelse
                </div>

                @if ($loans->hasPages())<div class="mt-3">{{ $loans->links() }}</div>@endif
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
