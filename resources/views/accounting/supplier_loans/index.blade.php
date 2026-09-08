@extends('layouts.app')

@section('title', 'Accounting • Dana Supplier')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $statusLabels = ['draft' => 'Draft', 'posted' => 'Posted', 'settled' => 'Lunas', 'void' => 'Void'];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .sl-wrap { display:grid; gap:1rem; }
        .sl-actions,.sl-filter { display:flex; align-items:center; gap:.55rem; flex-wrap:wrap; }
        .sl-actions { justify-content:flex-end; }
        .sl-btn { display:inline-flex; align-items:center; min-height:40px; padding:.55rem .9rem; border:1px solid #dbe3ee; border-radius:999px; background:#fff; color:#0f172a; text-decoration:none; font-size:.82rem; font-weight:800; }
        .sl-btn-primary { color:#fff; background:#0f172a; border-color:#0f172a; }
        .sl-control { min-height:40px; border-radius:11px; border-color:#dbe3ee; box-shadow:none; }
        .sl-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; }
        .sl-card { border:1px solid #e2e8f0; border-radius:15px; background:#fff; padding:.9rem 1rem; }
        .sl-label { color:#64748b; font-size:.7rem; font-weight:900; text-transform:uppercase; letter-spacing:.04em; }
        .sl-value { margin-top:.22rem; color:#0f172a; font-size:1.15rem; font-weight:900; }
        .sl-table th { color:#64748b; font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
        .sl-table td { vertical-align:middle; }
        .sl-link { color:#0f172a; font-weight:850; text-decoration:none; }
        .sl-status { display:inline-flex; padding:.22rem .58rem; border-radius:999px; font-size:.72rem; font-weight:850; }
        .sl-status-draft { color:#92400e; background:#fef3c7; } .sl-status-posted { color:#166534; background:#dcfce7; }
        .sl-status-settled { color:#075985; background:#e0f2fe; } .sl-status-void { color:#991b1b; background:#fee2e2; }
        @media(max-width:900px){ .sl-summary{grid-template-columns:repeat(2,minmax(0,1fr));} }
        @media(max-width:600px){ .sl-summary{grid-template-columns:1fr 1fr;} .sl-filter>*{flex:1 1 150px;} .sl-filter .sl-btn{flex:0 0 auto;} }
    </style>
@endpush

@section('content')
    <x-gf.page eyebrow="Accounting" title="Dana Supplier" description="Pencatatan dana yang dipinjamkan ke supplier sebelum barang dikirim.">
        <x-slot:actions><div class="sl-actions"><a class="sl-btn sl-btn-primary" href="{{ route('accounting.supplier-loans.create') }}">+ Dana Supplier</a></div></x-slot:actions>
        <div class="sl-wrap">
            @if (session('message'))
                <div class="alert alert-{{ session('status') === 'error' ? 'danger' : 'success' }} mb-0">{{ session('message') }}</div>
            @endif

            <div class="sl-summary">
                <div class="sl-card"><div class="sl-label">Total Dana Posted</div><div class="sl-value">Rp {{ $fmt($summary['total_funded']) }}</div></div>
                <div class="sl-card"><div class="sl-label">Sudah Kembali</div><div class="sl-value">Rp {{ $fmt($summary['total_repaid']) }}</div></div>
                <div class="sl-card"><div class="sl-label">Sisa Piutang</div><div class="sl-value">Rp {{ $fmt($summary['outstanding']) }}</div></div>
                <div class="sl-card"><div class="sl-label">Draft</div><div class="sl-value">{{ number_format($summary['draft_docs']) }}</div></div>
            </div>

            <x-gf.panel title="Filter" subtitle="Cari berdasarkan supplier, status, dan periode pencairan.">
                <form class="sl-filter" method="GET" action="{{ route('accounting.supplier-loans.index') }}">
                    <select class="form-select sl-control" name="supplier_id"><option value="">Semua supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string)request('supplier_id') === (string)$supplier->id)>{{ $supplier->code }} · {{ $supplier->name }}</option>@endforeach</select>
                    <select class="form-select sl-control" name="status"><option value="">Semua status</option>@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select>
                    <input class="form-control sl-control" type="date" name="from" value="{{ request('from') }}" aria-label="Dari tanggal">
                    <input class="form-control sl-control" type="date" name="to" value="{{ request('to') }}" aria-label="Sampai tanggal">
                    <button class="sl-btn sl-btn-primary" type="submit">Terapkan</button>
                    <a class="sl-btn" href="{{ route('accounting.supplier-loans.index') }}">Reset</a>
                </form>
            </x-gf.panel>

            <x-gf.panel title="Daftar Dana Supplier" subtitle="Transaksi yang sudah POSTED membentuk jurnal dan saldo piutang.">
                <div class="table-responsive"><table class="table sl-table align-middle mb-0">
                    <thead><tr><th>Tanggal</th><th>Supplier</th><th>Referensi</th><th class="text-end">Dana</th><th class="text-end">Sisa</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($loans as $loan)
                        @php $repaid = (float)($loan->posted_repayment_amount ?? 0); $remaining = max(0, (float)$loan->principal_amount - $repaid); @endphp
                        <tr>
                            <td>{{ optional($loan->date)->format('d/m/Y') }}</td>
                            <td><a class="sl-link" href="{{ route('accounting.supplier-loans.show', $loan) }}">{{ $loan->supplier?->name ?? '-' }}</a><div class="small text-muted">{{ $loan->supplier?->code }}</div></td>
                            <td>{{ $loan->reference ?: '—' }}</td>
                            <td class="text-end">Rp {{ $fmt($loan->principal_amount) }}</td>
                            <td class="text-end">Rp {{ $fmt($remaining) }}</td>
                            <td><span class="sl-status sl-status-{{ $loan->status }}">{{ $statusLabels[$loan->status] ?? $loan->status }}</span></td>
                            <td class="text-end"><a class="sl-btn" href="{{ route('accounting.supplier-loans.show', $loan) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada dana supplier.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                @if($loans->hasPages())<div class="mt-3">{{ $loans->links() }}</div>@endif
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
