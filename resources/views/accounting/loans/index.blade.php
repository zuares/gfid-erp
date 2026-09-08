@extends('layouts.app')

@section('title', 'Accounting • Pinjaman')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $labels = ['draft' => 'Draft', 'posted' => 'Tercatat', 'void' => 'Dibatalkan'];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .loan-page { display: grid; gap: 1rem; }
        .loan-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; }
        .loan-btn { display:inline-flex; align-items:center; gap:.4rem; min-height:40px; padding:.55rem .95rem; border-radius:999px; border:1px solid #dbe3ee; background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:850; }
        .loan-btn-primary { color:#fff; background:#0f172a; border-color:#0f172a; }
        .loan-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; }
        .loan-kpi { border:1px solid #e2e8f0; border-radius:14px; background:#fff; padding:.9rem; }
        .loan-kpi-label { color:#64748b; font-size:.7rem; font-weight:900; text-transform:uppercase; letter-spacing:.05em; }
        .loan-kpi-value { margin-top:.2rem; color:#0f172a; font-size:1.2rem; font-weight:950; }
        .loan-filter { display:grid; grid-template-columns:1fr 1fr 1fr auto; gap:.55rem; align-items:end; }
        .loan-filter .form-control,.loan-filter .form-select { min-height:40px; border-radius:999px; box-shadow:none; }
        .loan-table td,.loan-table th { vertical-align:middle; }
        .loan-link { color:#0f172a; font-weight:900; text-decoration:none; }
        .loan-muted { color:#64748b; font-size:.78rem; }
        .loan-status { display:inline-flex; border-radius:999px; padding:.22rem .6rem; font-size:.74rem; font-weight:850; }
        .loan-status-draft { color:#92400e; background:#fef3c7; } .loan-status-posted { color:#166534; background:#dcfce7; } .loan-status-void { color:#991b1b; background:#fee2e2; }
        @media (max-width:768px) { .loan-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } .loan-filter { grid-template-columns:1fr 1fr; } .loan-filter .form-select { grid-column:1/-1; } .loan-filter .loan-actions { grid-column:1/-1; } }
    </style>
@endpush

@section('content')
    <x-gf.page eyebrow="Accounting" title="Pinjaman" description="Kelola penerimaan pinjaman dan pembayaran pokok/bunga dengan jurnal otomatis.">
        <x-slot:actions><div class="loan-actions"><a class="loan-btn loan-btn-primary" href="{{ route('accounting.loans.create') }}">+ Tambah Pinjaman</a></div></x-slot:actions>
        <div class="loan-page">
            <div class="loan-kpis">
                <div class="loan-kpi"><div class="loan-kpi-label">Total Pokok</div><div class="loan-kpi-value">Rp {{ $fmt($summary['total_amount']) }}</div></div>
                <div class="loan-kpi"><div class="loan-kpi-label">Sudah Tercatat</div><div class="loan-kpi-value">Rp {{ $fmt($summary['posted_amount']) }}</div></div>
                <div class="loan-kpi"><div class="loan-kpi-label">Draft</div><div class="loan-kpi-value">{{ $summary['draft_docs'] }}</div></div>
                <div class="loan-kpi"><div class="loan-kpi-label">Dibatalkan</div><div class="loan-kpi-value">{{ $summary['void_docs'] }}</div></div>
            </div>
            <x-gf.panel title="Daftar Pinjaman" subtitle="Posting membuat jurnal Dr Kas/Bank — Cr Utang Pinjaman.">
                <form class="loan-filter mb-3" method="GET" action="{{ route('accounting.loans.index') }}">
                    <select class="form-select" name="status"><option value="">Semua status</option>@foreach ($labels as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select>
                    <input class="form-control" type="date" name="from" value="{{ request('from') }}" aria-label="Dari tanggal">
                    <input class="form-control" type="date" name="to" value="{{ request('to') }}" aria-label="Sampai tanggal">
                    <div class="loan-actions"><button class="loan-btn" type="submit">Filter</button><a class="loan-btn" href="{{ route('accounting.loans.index') }}">Reset</a></div>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover loan-table mb-0">
                        <thead><tr><th>Tanggal</th><th>Pemberi Pinjaman</th><th>Utang</th><th class="text-end">Pokok</th><th class="text-end">Sisa</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse ($loans as $loan)
                            @php $paid = (float) ($loan->posted_principal_paid ?? 0); $remaining = max(0, (float) $loan->principal_amount - $paid); @endphp
                            <tr>
                                <td>{{ optional($loan->date)->format('d/m/Y') }}</td>
                                <td><a class="loan-link" href="{{ route('accounting.loans.show', $loan) }}">{{ $loan->lender }}</a><div class="loan-muted">{{ $loan->description ?: 'Penerimaan pinjaman' }}{{ $loan->reference ? ' · '.$loan->reference : '' }}</div></td>
                                <td>{{ $loan->liabilityAccount?->name }}<div class="loan-muted">{{ $loan->liabilityAccount?->code }}</div></td>
                                <td class="text-end fw-bold">Rp {{ $fmt($loan->principal_amount) }}</td><td class="text-end fw-bold">Rp {{ $fmt($remaining) }}</td>
                                <td><span class="loan-status loan-status-{{ $loan->status }}">{{ $labels[$loan->status] ?? $loan->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">Belum ada data pinjaman.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $loans->links() }}</div>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
