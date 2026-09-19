@extends('layouts.app')

@section('title', 'Accounting • Tambah Dana Supplier')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .sl-wrap { display: grid; gap: 1rem; }
        .sl-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
        .sl-field { display: grid; gap: .35rem; min-width: 0; }
        .sl-field-full { grid-column: 1 / -1; }
        .sl-field-label { color: #334155; font-size: .74rem; font-weight: 900; letter-spacing: .01em; }
        .sl-field-label b { color: #dc2626; }
        .sl-field-label em { color: #94a3b8; font-size: .68rem; font-style: normal; font-weight: 700; }
        .sl-control { min-height: 44px; border-radius: 11px; border-color: #dbe3ee; box-shadow: none; }
        .sl-control:focus { border-color: #64748b; box-shadow: 0 0 0 .2rem rgba(15, 23, 42, .07); }
        .sl-money { display: flex; align-items: center; min-width: 0; border: 1px solid #dbe3ee; border-radius: 11px; background: #fff; overflow: hidden; }
        .sl-money > span { padding: 0 .75rem; color: #64748b; font-size: .82rem; font-weight: 900; }
        .sl-money .sl-control { flex: 1; min-width: 0; border: 0; border-left: 1px solid #eef2f7; border-radius: 0; }
        .sl-money:focus-within { border-color: #64748b; box-shadow: 0 0 0 .2rem rgba(15, 23, 42, .07); }
        .sl-actions { display: flex; justify-content: flex-end; align-items: center; gap: .55rem; flex-wrap: wrap; }
        .sl-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-height: 42px; padding: .6rem 1rem; border: 1px solid #dbe3ee; border-radius: 999px; background: #fff; color: #0f172a; text-decoration: none; font-size: .83rem; font-weight: 850; }
        .sl-btn:hover { border-color: #94a3b8; color: #0f172a; }
        .sl-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .sl-btn-primary:hover { color: #fff; background: #1e293b; border-color: #1e293b; }
        .sl-btn span { color: inherit !important; }
        .sl-form-actions { padding-top: 1rem; border-top: 1px solid #eef2f7; }
        .sl-help { display: flex; align-items: flex-start; gap: .55rem; color: #475569; background: #f8fafc; border: 1px solid #eef2f7; border-radius: 12px; padding: .8rem; font-size: .8rem; line-height: 1.5; }
        .sl-help i { color: #64748b; margin-top: .1rem; }
        @media (max-width: 760px) {
            .sl-form-grid { grid-template-columns: 1fr; }
            .sl-field-full { grid-column: auto; }
            .sl-actions .sl-btn { flex: 1 1 0; }
            .sl-form-actions { margin: 1.25rem 0 0; padding: 1rem 0 0; gap: .5rem; }
            .sl-form-actions .sl-btn { height: 44px; min-height: 44px; padding: 0 .75rem; border-radius: 12px; font-size: .8rem; line-height: 1; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page eyebrow="Accounting" title="Tambah Dana Supplier" description="Catat dana yang dipinjamkan ke supplier sebelum barang diterima.">
        <x-slot:actions>
            <div class="sl-actions"><a class="sl-btn" href="{{ route('accounting.supplier-loans.index') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i><span>Daftar Dana Supplier</span></a></div>
        </x-slot:actions>

        <div class="sl-wrap">
            <x-gf.panel title="Form Dana Supplier" subtitle="Data disimpan sebagai DRAFT dan belum membentuk jurnal sampai diposting.">
                <form method="POST" action="{{ route('accounting.supplier-loans.store') }}">
                    @csrf
                    @include('accounting.supplier_loans._form')
                    <div class="sl-help mt-3"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Transaksi ini bukan beban dan belum menjadi persediaan. Jika supplier mengembalikan uang, catat melalui pembayaran kembali.</span></div>
                    <div class="sl-actions sl-form-actions mt-3"><a class="sl-btn" href="{{ route('accounting.supplier-loans.index') }}">Batal</a><button class="sl-btn sl-btn-primary" type="submit"><i class="bi bi-save" aria-hidden="true"></i><span>Simpan Draft</span></button></div>
                </form>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
