@extends('layouts.app')

@section('title', 'Accounting • Tambah Transfer Kas/Bank')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .ct-edit-page { display: grid; gap: 1rem; }
        .ct-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .ct-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; padding: .55rem .95rem; border-radius: 999px; border: 1px solid rgba(15,23,42,.1); background: #fff; color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850; }
        .ct-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .ct-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
        .ct-field { display: grid; gap: .32rem; margin: 0; }
        .ct-field-full { grid-column: 1 / -1; }
        .ct-field > span { color: #64748b; font-size: .75rem; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; }
        .ct-field small { color: #94a3b8; text-transform: none; letter-spacing: 0; }
        .ct-form-control { min-height: 42px; border-radius: 12px; border-color: rgba(15,23,42,.12); box-shadow: none; }
        .ct-form-error { padding: .75rem .85rem; border-radius: 12px; border: 1px solid rgba(239,68,68,.24); background: rgba(239,68,68,.08); color: #991b1b; font-size: .86rem; }
        .ct-form-error ul { margin: .35rem 0 0; padding-left: 1.1rem; }
        .ct-note { color: #1e3a8a; background: #eff6ff; border: 1px solid rgba(37,99,235,.14); border-radius: 12px; padding: .75rem .85rem; font-size: .84rem; }
        @media (max-width: 768px) { .ct-form-grid { grid-template-columns: 1fr; } .ct-field-full { grid-column: auto; } .ct-actions { justify-content: stretch; } .ct-actions .ct-btn { flex: 1; } }
    </style>
@endpush

@section('content')
    <x-gf.page eyebrow="Accounting" title="Tambah Transfer Kas/Bank" description="Simpan sebagai Draft, lalu posting setelah data diperiksa.">
        <x-slot:actions><div class="ct-actions"><a class="ct-btn" href="{{ route('accounting.cash-transfers.index') }}">Daftar Transfer</a></div></x-slot:actions>
        <div class="ct-edit-page">
            <x-gf.panel title="Form Transfer" subtitle="Posting membuat jurnal: debit akun tujuan, kredit akun asal.">
                <div class="ct-note mb-3">Contoh penarikan bank ke kas tunai: pilih Bank pada <b>Dari Kas/Bank</b> dan Kas Tunai pada <b>Ke Kas/Bank</b>.</div>
                <form method="POST" action="{{ route('accounting.cash-transfers.store') }}" data-gf-confirm data-gf-confirm-title="Simpan sebagai Draft?" data-gf-confirm-text="Transfer akan tersimpan sebagai draft dan bisa diposting setelah dicek." data-gf-confirm-ok="Ya, simpan">
                    @csrf
                    @include('accounting.cash_transfers._form')
                    <div class="d-flex justify-content-end gap-2 flex-wrap mt-3"><a class="ct-btn" href="{{ route('accounting.cash-transfers.index') }}">Batal</a><button class="ct-btn ct-btn-primary" type="submit">Simpan Draft</button></div>
                </form>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
