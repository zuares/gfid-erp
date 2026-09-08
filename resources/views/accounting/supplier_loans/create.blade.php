@extends('layouts.app')

@section('title', 'Accounting • Tambah Dana Supplier')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .sl-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem}.sl-field{display:grid;gap:.3rem}.sl-field-full{grid-column:1/-1}.sl-field span{color:#64748b;font-size:.72rem;font-weight:900;text-transform:uppercase;letter-spacing:.04em}.sl-control{min-height:42px;border-radius:12px;border-color:#dbe3ee;box-shadow:none}.sl-actions{display:flex;justify-content:flex-end;gap:.5rem;flex-wrap:wrap}.sl-btn{display:inline-flex;align-items:center;min-height:40px;padding:.55rem .95rem;border:1px solid #dbe3ee;border-radius:999px;background:#fff;color:#0f172a;text-decoration:none;font-size:.83rem;font-weight:850}.sl-btn-primary{color:#fff;background:#0f172a;border-color:#0f172a}.sl-help{color:#475569;background:#f8fafc;border-radius:12px;padding:.8rem;font-size:.84rem}@media(max-width:768px){.sl-form-grid{grid-template-columns:1fr}.sl-field-full{grid-column:auto}.sl-actions .sl-btn{flex:1;justify-content:center}}
    </style>
@endpush

@section('content')
    <x-gf.page eyebrow="Accounting" title="Tambah Dana Supplier" description="Catat dana yang dipinjamkan ke supplier sebelum barang diterima.">
        <x-slot:actions><div class="sl-actions"><a class="sl-btn" href="{{ route('accounting.supplier-loans.index') }}">Daftar Dana Supplier</a></div></x-slot:actions>
        <x-gf.panel title="Informasi Dana Supplier" subtitle="Jurnal saat POSTED: Debit Piutang Pinjaman Supplier, Kredit Kas/Bank.">
            <form method="POST" action="{{ route('accounting.supplier-loans.store') }}">@csrf
                @include('accounting.supplier_loans._form')
                <div class="sl-help mt-3">Transaksi ini bukan beban dan belum menjadi persediaan. Jika supplier mengembalikan uang, catat melalui pembayaran kembali. PO dapat dibuat kemudian.</div>
                <div class="sl-actions mt-3"><a class="sl-btn" href="{{ route('accounting.supplier-loans.index') }}">Batal</a><button class="sl-btn sl-btn-primary" type="submit">Simpan Draft</button></div>
            </form>
        </x-gf.panel>
    </x-gf.page>
@endsection
