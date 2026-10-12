@extends('layouts.app')

@section('title', 'Accounting • Bayar Tabungan Karyawan')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .es-wrap { display: grid; gap: 1rem; max-width: 880px; }
        .es-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
        .es-field { display: grid; gap: .35rem; }
        .es-field-full { grid-column: 1 / -1; }
        .es-field label, .es-field > span { color: #334155; font-size: .76rem; font-weight: 850; }
        .es-control { min-height: 44px; border-radius: 11px; border-color: #dbe3ee; }
        .es-actions { display: flex; gap: .55rem; justify-content: flex-end; flex-wrap: wrap; }
        .es-btn { display: inline-flex; align-items: center; gap: .4rem; min-height: 42px; padding: .6rem 1rem; border: 1px solid #dbe3ee; border-radius: 999px; background: #fff; color: #0f172a; text-decoration: none; font-size: .83rem; font-weight: 850; }
        .es-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .es-help { padding: .85rem 1rem; border-radius: 12px; color: #334155; background: #f8fafc; font-size: .82rem; }
        @media (max-width: 700px) { .es-form-grid { grid-template-columns: 1fr; } .es-field-full { grid-column: auto; } .es-actions .es-btn { flex: 1 1 0; } }
    </style>
@endpush

@section('content')
    <x-gf.page eyebrow="Accounting" title="Bayar Tabungan Karyawan">
        <x-slot:actions><div class="es-actions"><a class="es-btn" href="{{ route('accounting.employee-savings.index') }}"><i class="bi bi-arrow-left"></i> Kembali</a></div></x-slot:actions>
        <div class="es-wrap">
            <x-gf.panel title="Form pembayaran">
                <div class="es-help mb-3">Pembayaran ini mengurangi kewajiban akun <strong>2104 · Tabungan Karyawan</strong>, bukan dicatat sebagai biaya.</div>
                <form method="POST" action="{{ route('accounting.employee-savings.store') }}">
                    @csrf
                    <div class="es-form-grid">
                        <div class="es-field"><span>Karyawan</span><select class="form-select es-control" name="employee_id" required><option value="">Pilih karyawan</option>@foreach ($employees as $employee)<option value="{{ $employee->id }}" @selected((int) old('employee_id') === $employee->id)>{{ $employee->name }} · {{ $employee->code }}</option>@endforeach</select></div>
                        <div class="es-field"><span>Tanggal pembayaran</span><input class="form-control es-control" type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required></div>
                        <div class="es-field"><span>Nominal</span><input class="form-control es-control" type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" placeholder="0" required></div>
                        <div class="es-field"><span>Bayar dari</span><select class="form-select es-control" name="cash_account_id" required><option value="">Pilih kas / bank</option>@foreach ($cashAccounts as $account)<option value="{{ $account->id }}" @selected((int) old('cash_account_id') === $account->id)>{{ $account->name }} · {{ $account->code }}</option>@endforeach</select></div>
                        <div class="es-field es-field-full"><span>No. referensi <small class="text-muted">opsional</small></span><input class="form-control es-control" type="text" name="reference" maxlength="100" value="{{ old('reference') }}" placeholder="Contoh: TRANSFER-001"></div>
                        <div class="es-field es-field-full"><span>Catatan <small class="text-muted">opsional</small></span><textarea class="form-control es-control" name="notes" rows="3" placeholder="Catatan pembayaran...">{{ old('notes') }}</textarea></div>
                    </div>
                    @if ($errors->any())<div class="alert alert-danger mt-3 mb-0">{{ $errors->first() }}</div>@endif
                    <div class="es-actions mt-4 pt-3 border-top"><a class="es-btn" href="{{ route('accounting.employee-savings.index') }}">Batal</a><button class="es-btn es-btn-primary" type="submit"><i class="bi bi-save"></i> Simpan sebagai Draft</button></div>
                </form>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
