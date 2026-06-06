@extends('layouts.app')

@section('title', 'Accounting • Tambah Saldo Awal')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .ob-form-page { display: grid; gap: 1rem; }
        .ob-form-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 1rem; align-items: start; }
        .ob-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .ob-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            min-height: 40px; padding: .55rem .95rem; border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, .10); background: #fff;
            color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850;
        }
        .ob-btn:hover { color: #0f172a; background: #f8fafc; }
        .ob-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .ob-btn-primary:hover { color: #fff; background: #1e293b; }
        .ob-field { margin-bottom: .85rem; }
        .ob-label {
            display: block; margin-bottom: .32rem; color: #475569;
            font-size: .78rem; font-weight: 900;
        }
        .ob-field .form-control,
        .ob-field .form-select {
            min-height: 42px; border-radius: 12px; border-color: rgba(15, 23, 42, .12);
            box-shadow: none; font-size: .9rem;
        }
        .ob-help { margin-top: .25rem; color: #94a3b8; font-size: .78rem; }
        .ob-error {
            border: 1px solid #fecaca; background: #fef2f2; color: #991b1b;
            border-radius: 12px; padding: .85rem .95rem; font-size: .86rem;
        }
        .ob-flow {
            display: grid; gap: .55rem;
        }
        .ob-flow-row {
            display: flex; justify-content: space-between; gap: .75rem;
            border: 1px solid #e2e8f0; border-radius: 12px; padding: .7rem .75rem; background: #fff;
        }
        .ob-flow-label { color: #64748b; font-size: .76rem; font-weight: 880; }
        .ob-flow-value { color: #0f172a; font-weight: 950; }
        .ob-note {
            color: #1e3a8a; background: #eff6ff; border: 1px solid rgba(37, 99, 235, .14);
            border-radius: 12px; padding: .8rem .9rem; font-size: .84rem; font-weight: 720;
        }
        @media (max-width: 768px) {
            .gf-master-header { padding: 12px 14px; border-radius: 14px; }
            .gf-master-title { font-size: 18px; }
            .gf-master-desc { font-size: 11.5px; }
            .gf-master-actions { flex: 1 1 100%; }
            .ob-actions { justify-content: stretch; }
            .ob-actions .ob-btn { flex: 1 1 auto; }
            .ob-form-grid { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Accounting"
        title="Tambah Saldo Awal"
        description="Isi posisi awal kas atau bank lalu sistem membuat jurnal debit kas/bank dan kredit modal.">
        <x-slot:actions>
            <div class="ob-actions">
                <a href="{{ route('accounting.opening-balances.index') }}" class="ob-btn">Daftar Saldo Awal</a>
                <a href="{{ route('accounting.journals.index') }}" class="ob-btn">Semua Jurnal</a>
            </div>
        </x-slot:actions>

        <div class="ob-form-page">
            @if ($errors->any())
                <div class="ob-error">
                    <div class="fw-semibold mb-1">Ada data yang perlu dicek:</div>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="ob-form-grid">
                <x-gf.panel title="Form Saldo Awal" subtitle="Gunakan tanggal awal pembukuan atau tanggal mulai memakai sistem.">
                    <form method="POST"
                        action="{{ route('accounting.opening-balances.store') }}"
                        data-gf-confirm
                        data-gf-confirm-title="Posting saldo awal?"
                        data-gf-confirm-text="Sistem akan membuat jurnal posted untuk saldo awal ini."
                        data-gf-confirm-ok="Ya, posting">
                        @csrf

                        <div class="ob-field">
                            <label class="ob-label" for="date">Tanggal Saldo Awal</label>
                            <input id="date" type="text" name="date" class="form-control"
                                value="{{ old('date', now()->toDateString()) }}" placeholder="YYYY-MM-DD" required data-gf-date>
                            <div class="ob-help">Biasanya tanggal awal pembukuan, misalnya 2026-01-01.</div>
                        </div>

                        <div class="ob-field">
                            <label class="ob-label" for="cash_account_id">Akun Kas / Bank</label>
                            <select id="cash_account_id" name="cash_account_id" class="form-select" required>
                                <option value="">Pilih kas/bank</option>
                                @foreach ($cashAccounts as $account)
                                    <option value="{{ $account->id }}" @selected(old('cash_account_id') == $account->id)>
                                        {{ $account->code }} - {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="ob-field">
                            <label class="ob-label" for="equity_account_id">Akun Lawan Modal</label>
                            <select id="equity_account_id" name="equity_account_id" class="form-select" required>
                                @foreach ($equityAccounts as $account)
                                    <option value="{{ $account->id }}" @selected(old('equity_account_id', $defaultEquity) == $account->id)>
                                        {{ $account->code }} - {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="ob-help">Default ke akun modal. Ganti hanya kalau punya akun equity pembuka lain.</div>
                        </div>

                        <div class="ob-field">
                            <label class="ob-label" for="amount">Nominal</label>
                            <input id="amount" type="number" name="amount" class="form-control"
                                value="{{ old('amount') }}" min="0.01" step="0.01" placeholder="0" required>
                        </div>

                        <div class="ob-field">
                            <label class="ob-label" for="description">Keterangan</label>
                            <input id="description" type="text" name="description" class="form-control"
                                value="{{ old('description') }}" placeholder="Saldo awal Bank Jago">
                        </div>

                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                            <a href="{{ route('accounting.opening-balances.index') }}" class="ob-btn">Batal</a>
                            <button class="ob-btn ob-btn-primary" type="submit">Posting Saldo Awal</button>
                        </div>
                    </form>
                </x-gf.panel>

                <x-gf.panel title="Jurnal Yang Dibuat" subtitle="Ringkasnya seperti ini.">
                    <div class="ob-flow">
                        <div class="ob-flow-row">
                            <div>
                                <div class="ob-flow-label">Debit</div>
                                <div class="ob-flow-value">Kas / Bank</div>
                            </div>
                            <div class="ob-flow-label">bertambah</div>
                        </div>
                        <div class="ob-flow-row">
                            <div>
                                <div class="ob-flow-label">Kredit</div>
                                <div class="ob-flow-value">Modal</div>
                            </div>
                            <div class="ob-flow-label">pembuka</div>
                        </div>
                        <div class="ob-note">
                            Untuk input banyak akun sekaligus, lebih cepat pakai menu Batch Input di halaman daftar saldo awal.
                        </div>
                    </div>
                </x-gf.panel>
            </div>
        </div>
    </x-gf.page>
@endsection
