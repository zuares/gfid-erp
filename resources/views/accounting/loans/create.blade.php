@extends('layouts.app')

@section('title', 'Accounting • Tambah Pinjaman')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .loan-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.8rem; }
        .loan-field { display:grid; gap:.3rem; } .loan-field-full { grid-column:1/-1; }
        .loan-field span { color:#64748b; font-size:.74rem; font-weight:900; text-transform:uppercase; letter-spacing:.04em; }
        .loan-control { min-height:42px; border-radius:12px; border-color:#dbe3ee; box-shadow:none; }
        .loan-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; }
        .loan-btn { display:inline-flex; align-items:center; min-height:40px; padding:.55rem .95rem; border-radius:999px; border:1px solid #dbe3ee; background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:850; }
        .loan-btn-primary { color:#fff; background:#0f172a; border-color:#0f172a; }
        .loan-help { color:#64748b; background:#f8fafc; border-radius:12px; padding:.75rem .85rem; font-size:.84rem; }
        @media (max-width:768px) { .loan-form-grid { grid-template-columns:1fr; } .loan-field-full { grid-column:auto; } .loan-actions .loan-btn { flex:1; justify-content:center; } }
    </style>
@endpush

@section('content')
    <x-gf.page eyebrow="Accounting" title="Tambah Pinjaman" description="Simpan sebagai draft, cek akun, lalu posting untuk membuat jurnal.">
        <x-slot:actions><div class="loan-actions"><a class="loan-btn" href="{{ route('accounting.loans.index') }}">Daftar Pinjaman</a></div></x-slot:actions>
        <x-gf.panel title="Informasi Pinjaman" subtitle="Jurnal saat posting: debit kas/bank dan kredit utang pinjaman.">
            <form method="POST" action="{{ route('accounting.loans.store') }}">
                @csrf
                <div class="loan-form-grid">
                    <label class="loan-field"><span>Tanggal</span><input class="form-control loan-control" type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required></label>
                    <label class="loan-field"><span>Nominal Pokok</span><input class="form-control loan-control" type="number" name="principal_amount" min="0.01" step="0.01" value="{{ old('principal_amount') }}" required placeholder="0"></label>
                    <label class="loan-field"><span>Pemberi Pinjaman</span><input class="form-control loan-control" type="text" name="lender" maxlength="160" value="{{ old('lender') }}" required placeholder="Bank / pemilik / pihak lain"></label>
                    <label class="loan-field"><span>No. Referensi <small>(opsional)</small></span><input class="form-control loan-control" type="text" name="reference" maxlength="100" value="{{ old('reference') }}" placeholder="Nomor akad / bukti transfer"></label>
                    <label class="loan-field"><span>Uang Masuk ke</span><select class="form-select loan-control" name="cash_account_id" required><option value="">Pilih kas / bank</option>@foreach ($cashAccounts as $account)<option value="{{ $account->id }}" @selected((int) old('cash_account_id') === $account->id)>{{ $account->name }} · {{ $account->code }}</option>@endforeach</select></label>
                    <label class="loan-field"><span>Akun Utang</span><select class="form-select loan-control" name="liability_account_id" required><option value="">Pilih akun utang</option>@foreach ($liabilityAccounts as $account)<option value="{{ $account->id }}" @selected((int) old('liability_account_id', $liabilityAccounts->firstWhere('code', '2103')?->id) === $account->id)>{{ $account->name }} · {{ $account->code }}</option>@endforeach</select></label>
                    <label class="loan-field loan-field-full"><span>Keterangan</span><input class="form-control loan-control" type="text" name="description" maxlength="255" value="{{ old('description') }}" placeholder="Contoh: Pinjaman modal kerja"></label>
                    <label class="loan-field loan-field-full"><span>Catatan <small>(opsional)</small></span><textarea class="form-control loan-control" name="notes" rows="3">{{ old('notes') }}</textarea></label>
                    @if ($errors->any())<div class="alert alert-danger loan-field-full mb-0"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                </div>
                <div class="loan-help mt-3">Pinjaman tidak masuk laba rugi sebagai pendapatan. Ia dicatat sebagai kewajiban sampai dibayar.</div>
                <div class="loan-actions mt-3"><a class="loan-btn" href="{{ route('accounting.loans.index') }}">Batal</a><button class="loan-btn loan-btn-primary" type="submit">Simpan Draft</button></div>
            </form>
        </x-gf.panel>
    </x-gf.page>
@endsection
