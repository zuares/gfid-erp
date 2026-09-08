@extends('layouts.app')

@section('title', 'Accounting • Detail Pinjaman')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $postedPrincipal = (float) $loan->repayments->where('status', 'posted')->sum('principal_amount');
    $remaining = max(0, (float) $loan->principal_amount - $postedPrincipal);
    $labels = ['draft' => 'Draft', 'posted' => 'Tercatat', 'void' => 'Dibatalkan'];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .loan-detail { display:grid; gap:1rem; } .loan-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; }
        .loan-btn { display:inline-flex; align-items:center; min-height:40px; padding:.55rem .95rem; border-radius:999px; border:1px solid #dbe3ee; background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:850; }
        .loan-btn-primary { color:#fff; background:#0f172a; border-color:#0f172a; } .loan-btn-danger { color:#991b1b; background:#fff5f5; border-color:#fecaca; }
        .loan-status { display:inline-flex; border-radius:999px; padding:.22rem .6rem; font-size:.74rem; font-weight:850; } .loan-status-draft{color:#92400e;background:#fef3c7}.loan-status-posted{color:#166534;background:#dcfce7}.loan-status-void{color:#991b1b;background:#fee2e2}
        .loan-info { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; } .loan-info-card { border:1px solid #e2e8f0; border-radius:14px; background:#fff; padding:.85rem; } .loan-info-label { color:#64748b; font-size:.7rem; font-weight:900; text-transform:uppercase; } .loan-info-value { margin-top:.18rem; color:#0f172a; font-weight:900; }
        .loan-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.7rem; } .loan-field { display:grid; gap:.3rem; } .loan-field span { color:#64748b; font-size:.73rem; font-weight:900; text-transform:uppercase; } .loan-control { min-height:40px; border-radius:11px; box-shadow:none; } .loan-field-full{grid-column:1/-1}
        @media(max-width:768px){.loan-info{grid-template-columns:repeat(2,minmax(0,1fr))}.loan-form-grid{grid-template-columns:1fr}.loan-field-full{grid-column:auto}.loan-actions .loan-btn{flex:1;justify-content:center}}
    </style>
@endpush

@section('content')
    <x-gf.page eyebrow="Accounting" title="Detail Pinjaman" description="{{ $loan->lender }} · {{ $loan->description ?: 'Penerimaan pinjaman' }}">
        <x-slot:actions>
            <div class="loan-actions"><a class="loan-btn" href="{{ route('accounting.loans.index') }}">Daftar Pinjaman</a>
                @if ($loan->status === 'draft')<form method="POST" action="{{ route('accounting.loans.post', $loan) }}">@csrf<button class="loan-btn loan-btn-primary" type="submit">Post Pinjaman</button></form>@endif
                @if ($loan->status === 'posted')<form method="POST" action="{{ route('accounting.loans.void', $loan) }}" onsubmit="return confirm('Void pinjaman ini?')">@csrf<input type="hidden" name="reason" value="Void dari detail"><button class="loan-btn loan-btn-danger" type="submit">Void</button></form>@endif
            </div>
        </x-slot:actions>
        <div class="loan-detail">
            @if (session('message'))<div class="alert alert-{{ session('status') === 'error' ? 'danger' : 'success' }} mb-0">{{ session('message') }}</div>@endif
            <div class="loan-info">
                <div class="loan-info-card"><div class="loan-info-label">Status</div><div class="loan-info-value"><span class="loan-status loan-status-{{ $loan->status }}">{{ $labels[$loan->status] ?? $loan->status }}</span></div></div>
                <div class="loan-info-card"><div class="loan-info-label">Pokok Pinjaman</div><div class="loan-info-value">Rp {{ $fmt($loan->principal_amount) }}</div></div>
                <div class="loan-info-card"><div class="loan-info-label">Sudah Dibayar</div><div class="loan-info-value">Rp {{ $fmt($postedPrincipal) }}</div></div>
                <div class="loan-info-card"><div class="loan-info-label">Sisa Pokok</div><div class="loan-info-value">Rp {{ $fmt($remaining) }}</div></div>
            </div>
            <x-gf.panel title="Ringkasan" subtitle="{{ optional($loan->date)->format('d/m/Y') }}{{ $loan->reference ? ' · '.$loan->reference : '' }}">
                <div class="table-responsive"><table class="table mb-0"><tbody><tr><th>Pemberi pinjaman</th><td>{{ $loan->lender }}</td></tr><tr><th>Uang masuk ke</th><td>{{ $loan->cashAccount?->name }} · {{ $loan->cashAccount?->code }}</td></tr><tr><th>Akun utang</th><td>{{ $loan->liabilityAccount?->name }} · {{ $loan->liabilityAccount?->code }}</td></tr><tr><th>Jurnal</th><td>{{ $loan->journal_id ? '#'.$loan->journal_id : 'Belum dibuat' }}</td></tr></tbody></table></div>
            </x-gf.panel>
            @if ($loan->status === 'posted')
                <x-gf.panel title="Tambah Pembayaran" subtitle="Jurnal: Dr Utang Pokok + Dr Beban Bunga — Cr Kas/Bank.">
                    <form method="POST" action="{{ route('accounting.loans.repayments.store', $loan) }}"><div class="loan-form-grid">@csrf
                        <label class="loan-field"><span>Tanggal</span><input class="form-control loan-control" type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required></label>
                        <label class="loan-field"><span>Pokok Dibayar</span><input class="form-control loan-control" type="number" name="principal_amount" min="0" step="0.01" value="{{ old('principal_amount') }}" required></label>
                        <label class="loan-field"><span>Bunga</span><input class="form-control loan-control" type="number" name="interest_amount" min="0" step="0.01" value="{{ old('interest_amount', 0) }}"></label>
                        <label class="loan-field"><span>Akun Beban Bunga</span><select class="form-select loan-control" name="interest_account_id"><option value="">Pilih jika ada bunga</option>@foreach ($expenseAccounts as $account)<option value="{{ $account->id }}" @selected($account->code === '6208')>{{ $account->name }} · {{ $account->code }}</option>@endforeach</select></label>
                        <label class="loan-field"><span>Dibayar dari</span><select class="form-select loan-control" name="cash_account_id" required>@foreach ($cashAccounts as $account)<option value="{{ $account->id }}" @selected($account->id === $loan->cash_account_id)>{{ $account->name }} · {{ $account->code }}</option>@endforeach</select></label>
                        <label class="loan-field"><span>No. Referensi</span><input class="form-control loan-control" type="text" name="reference" maxlength="100"></label>
                    </div><div class="loan-actions mt-3"><button class="loan-btn loan-btn-primary" type="submit">Simpan Pembayaran Draft</button></div></form>
                </x-gf.panel>
            @endif
            <x-gf.panel title="Riwayat Pembayaran" subtitle="Pembayaran diposting satu per satu agar sisa utang terlacak.">
                <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Tanggal</th><th>Pokok</th><th>Bunga</th><th>Kas/Bank</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                @forelse ($loan->repayments as $repayment)<tr><td>{{ optional($repayment->date)->format('d/m/Y') }}</td><td>Rp {{ $fmt($repayment->principal_amount) }}</td><td>Rp {{ $fmt($repayment->interest_amount) }}</td><td>{{ $repayment->cashAccount?->name }}</td><td><span class="loan-status loan-status-{{ $repayment->status }}">{{ $labels[$repayment->status] ?? $repayment->status }}</span></td><td class="text-end">@if($repayment->status === 'draft')<form method="POST" action="{{ route('accounting.loans.repayments.post', $repayment) }}" class="d-inline">@csrf<button class="loan-btn loan-btn-primary" type="submit">Post</button></form>@elseif($repayment->status === 'posted')<form method="POST" action="{{ route('accounting.loans.repayments.void', $repayment) }}" class="d-inline" onsubmit="return confirm('Void pembayaran ini?')">@csrf<input type="hidden" name="reason" value="Void dari detail"><button class="loan-btn loan-btn-danger" type="submit">Void</button></form>@endif</td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">Belum ada pembayaran.</td></tr>@endforelse
                </tbody></table></div>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
