@extends('layouts.app')

@section('title', 'Accounting • Dana Supplier')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $labels = ['draft' => 'Draft', 'posted' => 'Posted', 'settled' => 'Lunas', 'void' => 'Void'];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .sl-wrap { display: grid; gap: 1rem; }
        .sl-actions { display: flex; justify-content: flex-end; align-items: center; gap: .55rem; flex-wrap: wrap; }
        .sl-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-height: 40px; padding: .55rem .9rem; border: 1px solid #dbe3ee; border-radius: 999px; background: #fff; color: #0f172a; text-decoration: none; font-size: .82rem; font-weight: 850; transition: .18s ease; }
        .sl-btn:hover { border-color: #94a3b8; color: #0f172a; transform: translateY(-1px); }
        .sl-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .sl-btn span { color: inherit !important; }
        .sl-btn-primary:hover { color: #fff; background: #1e293b; border-color: #1e293b; }
        .sl-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
        .sl-card { position: relative; overflow: hidden; min-height: 112px; border: 1px solid #e2e8f0; border-radius: 16px; background: #fff; padding: 1rem; box-shadow: 0 8px 24px rgba(15, 23, 42, .04); }
        .sl-card::after { position: absolute; right: -22px; bottom: -28px; width: 88px; height: 88px; border-radius: 50%; background: rgba(226, 232, 240, .45); content: ''; }
        .sl-card-blue::after { background: rgba(219, 234, 254, .75); }
        .sl-card-green::after { background: rgba(220, 252, 231, .75); }
        .sl-card-amber::after { background: rgba(254, 243, 199, .8); }
        .sl-card-slate::after { background: rgba(226, 232, 240, .8); }
        .sl-card-top { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .sl-card-icon { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 10px; color: #334155; background: #f1f5f9; font-size: .85rem; }
        .sl-card-blue .sl-card-icon { color: #1d4ed8; background: #eff6ff; }
        .sl-card-green .sl-card-icon { color: #15803d; background: #f0fdf4; }
        .sl-card-amber .sl-card-icon { color: #b45309; background: #fffbeb; }
        .sl-card-label { color: #64748b; font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: .045em; }
        .sl-card-value { position: relative; z-index: 1; margin-top: .55rem; color: #0f172a; font-size: 1.2rem; font-weight: 950; letter-spacing: -.025em; }
        .sl-filter { display: grid; grid-template-columns: 1.25fr 1fr 1fr 1fr auto auto; align-items: end; gap: .7rem; }
        .sl-filter-field { display: grid; gap: .3rem; min-width: 0; }
        .sl-filter-label { color: #64748b; font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; }
        .sl-control { min-height: 42px; border-radius: 11px; border-color: #dbe3ee; box-shadow: none; }
        .sl-control:focus { border-color: #64748b; box-shadow: 0 0 0 .2rem rgba(15, 23, 42, .07); }
        .sl-filter-actions { display: flex; gap: .45rem; }
        .sl-table-wrap { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 14px; }
        .sl-table { min-width: 760px; margin: 0; }
        .sl-table th { color: #64748b; background: #f8fafc; font-size: .68rem; text-transform: uppercase; letter-spacing: .045em; white-space: nowrap; }
        .sl-table td { vertical-align: middle; }
        .sl-table tbody tr:hover { background: #f8fafc; }
        .sl-link { color: #0f172a; font-weight: 850; text-decoration: none; }
        .sl-link:hover { color: #2563eb; }
        .sl-status { display: inline-flex; align-items: center; gap: .25rem; padding: .25rem .58rem; border-radius: 999px; font-size: .7rem; font-weight: 850; white-space: nowrap; }
        .sl-status::before { width: 5px; height: 5px; border-radius: 50%; background: currentColor; content: ''; }
        .sl-status-draft { color: #92400e; background: #fef3c7; }
        .sl-status-posted { color: #166534; background: #dcfce7; }
        .sl-status-settled { color: #075985; background: #e0f2fe; }
        .sl-status-void { color: #991b1b; background: #fee2e2; }
        .sl-mobile-list { display: none; }
        .sl-mobile-card { display: block; color: inherit; text-decoration: none; border: 1px solid #e2e8f0; border-radius: 15px; background: #fff; padding: .9rem; box-shadow: 0 8px 22px rgba(15, 23, 42, .04); }
        .sl-mobile-card + .sl-mobile-card { margin-top: .65rem; }
        .sl-mobile-card:hover { color: inherit; border-color: #94a3b8; }
        .sl-mobile-head, .sl-mobile-foot { display: flex; align-items: center; justify-content: space-between; gap: .6rem; }
        .sl-mobile-name { min-width: 0; color: #0f172a; font-weight: 900; }
        .sl-mobile-code, .sl-mobile-foot { color: #64748b; font-size: .73rem; }
        .sl-mobile-code { margin-top: .12rem; }
        .sl-mobile-metrics { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; margin: .85rem 0; padding: .7rem 0; border-top: 1px solid #eef2f7; border-bottom: 1px solid #eef2f7; }
        .sl-mobile-metric-label { display: block; color: #94a3b8; font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; }
        .sl-mobile-metric-value { display: block; margin-top: .2rem; color: #0f172a; font-size: .8rem; font-weight: 900; }
        .sl-mobile-foot strong { color: #2563eb; font-size: .74rem; }
        @media (max-width: 1050px) { .sl-filter { grid-template-columns: repeat(3, minmax(0, 1fr)); } .sl-filter-actions { grid-column: 1 / -1; } }
        @media (max-width: 900px) { .sl-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 767px) {
            .sl-actions, .sl-actions .sl-btn { width: 100%; }
            .sl-filter { grid-template-columns: 1fr 1fr; gap: .65rem; }
            .sl-filter-actions { display: grid; grid-template-columns: 1fr 1fr; grid-column: 1 / -1; }
            .sl-filter-actions .sl-btn { width: 100%; }
            .sl-table-wrap { display: none; }
            .sl-mobile-list { display: block; }
        }
        @media (max-width: 430px) {
            .sl-summary { gap: .55rem; }
            .sl-card { min-height: 100px; padding: .78rem; }
            .sl-card-value { font-size: 1rem; }
            .sl-card-icon { width: 26px; height: 26px; font-size: .75rem; }
            .sl-filter { grid-template-columns: 1fr; }
            .sl-filter-actions { grid-template-columns: 1fr 1fr; }
            .sl-mobile-metrics { gap: .35rem; }
            .sl-mobile-metric-value { font-size: .72rem; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page eyebrow="Accounting" title="Dana Supplier" description="Kelola pencairan, saldo piutang, dan pengembalian dana supplier.">
        <x-slot:actions>
            <div class="sl-actions">
                <a class="sl-btn sl-btn-primary" href="{{ route('accounting.supplier-loans.create') }}"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Tambah Dana Supplier</span></a>
            </div>
        </x-slot:actions>

        <div class="sl-wrap">
            @if (session('message'))
                <div class="alert alert-{{ session('status') === 'error' ? 'danger' : 'success' }} mb-0">{{ session('message') }}</div>
            @endif

            <div class="sl-summary" aria-label="Ringkasan dana supplier">
                <div class="sl-card sl-card-blue"><div class="sl-card-top"><span class="sl-card-label">Total dicairkan</span><span class="sl-card-icon"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></span></div><div class="sl-card-value">Rp {{ $fmt($summary['total_funded']) }}</div></div>
                <div class="sl-card sl-card-green"><div class="sl-card-top"><span class="sl-card-label">Sudah kembali</span><span class="sl-card-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span></div><div class="sl-card-value">Rp {{ $fmt($summary['total_repaid']) }}</div></div>
                <div class="sl-card sl-card-amber"><div class="sl-card-top"><span class="sl-card-label">Sisa piutang</span><span class="sl-card-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span></div><div class="sl-card-value">Rp {{ $fmt($summary['outstanding']) }}</div></div>
                <div class="sl-card sl-card-slate"><div class="sl-card-top"><span class="sl-card-label">Draft</span><span class="sl-card-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span></div><div class="sl-card-value">{{ number_format($summary['draft_docs']) }}</div></div>
            </div>

            <x-gf.panel title="Filter dana supplier" subtitle="Persempit daftar berdasarkan supplier, status, atau tanggal pencairan.">
                <form class="sl-filter" method="GET" action="{{ route('accounting.supplier-loans.index') }}">
                    <label class="sl-filter-field"><span class="sl-filter-label">Supplier</span><select class="form-select sl-control" name="supplier_id"><option value="">Semua supplier</option>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>{{ $supplier->code }} · {{ $supplier->name }}</option>@endforeach</select></label>
                    <label class="sl-filter-field"><span class="sl-filter-label">Status</span><select class="form-select sl-control" name="status"><option value="">Semua status</option>@foreach ($labels as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select></label>
                    <label class="sl-filter-field"><span class="sl-filter-label">Dari tanggal</span><input class="form-control sl-control" type="date" name="from" value="{{ request('from') }}"></label>
                    <label class="sl-filter-field"><span class="sl-filter-label">Sampai tanggal</span><input class="form-control sl-control" type="date" name="to" value="{{ request('to') }}"></label>
                    <div class="sl-filter-actions"><button class="sl-btn sl-btn-primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i><span>Terapkan</span></button><a class="sl-btn" href="{{ route('accounting.supplier-loans.index') }}">Reset</a></div>
                </form>
            </x-gf.panel>

            <x-gf.panel title="Daftar dana supplier" subtitle="Posting membentuk jurnal Dr Piutang Pinjaman Supplier — Cr Kas/Bank.">
                <div class="sl-table-wrap">
                    <table class="table sl-table align-middle">
                        <thead><tr><th>Tanggal</th><th>Supplier</th><th>Referensi</th><th class="text-end">Dana</th><th class="text-end">Sisa</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @forelse ($loans as $loan)
                                @php $repaid = (float) ($loan->posted_repayment_amount ?? 0); $remaining = max(0, (float) $loan->principal_amount - $repaid); @endphp
                                <tr><td>{{ optional($loan->date)->format('d/m/Y') }}</td><td><a class="sl-link" href="{{ route('accounting.supplier-loans.show', $loan) }}">{{ $loan->supplier?->name ?? '-' }}</a><div class="small text-muted">{{ $loan->supplier?->code }}</div></td><td>{{ $loan->reference ?: '—' }}</td><td class="text-end fw-semibold">Rp {{ $fmt($loan->principal_amount) }}</td><td class="text-end fw-semibold">Rp {{ $fmt($remaining) }}</td><td><span class="sl-status sl-status-{{ $loan->status }}">{{ $labels[$loan->status] ?? $loan->status }}</span></td><td class="text-end"><a class="sl-btn" href="{{ route('accounting.supplier-loans.show', $loan) }}">Detail <i class="bi bi-arrow-right" aria-hidden="true"></i></a></td></tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-inbox d-block fs-3 mb-2" aria-hidden="true"></i>Belum ada dana supplier.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="sl-mobile-list">
                    @forelse ($loans as $loan)
                        @php $repaid = (float) ($loan->posted_repayment_amount ?? 0); $remaining = max(0, (float) $loan->principal_amount - $repaid); @endphp
                        <a class="sl-mobile-card" href="{{ route('accounting.supplier-loans.show', $loan) }}"><div class="sl-mobile-head"><div><div class="sl-mobile-name">{{ $loan->supplier?->name ?? '-' }}</div><div class="sl-mobile-code">{{ $loan->supplier?->code }} · {{ optional($loan->date)->format('d/m/Y') }}</div></div><span class="sl-status sl-status-{{ $loan->status }}">{{ $labels[$loan->status] ?? $loan->status }}</span></div><div class="sl-mobile-metrics"><div><span class="sl-mobile-metric-label">Dana</span><strong class="sl-mobile-metric-value">Rp {{ $fmt($loan->principal_amount) }}</strong></div><div><span class="sl-mobile-metric-label">Sisa</span><strong class="sl-mobile-metric-value">Rp {{ $fmt($remaining) }}</strong></div></div><div class="sl-mobile-foot"><span>{{ $loan->reference ?: 'Tanpa referensi' }}</span><strong>Lihat detail <i class="bi bi-arrow-right" aria-hidden="true"></i></strong></div></a>
                    @empty
                        <div class="text-center text-muted py-4"><i class="bi bi-inbox d-block fs-3 mb-2" aria-hidden="true"></i>Belum ada dana supplier.</div>
                    @endforelse
                </div>

                @if ($loans->hasPages())<div class="mt-3">{{ $loans->links() }}</div>@endif
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
