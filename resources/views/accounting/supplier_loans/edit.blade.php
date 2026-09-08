@extends('layouts.app')

@section('title', 'Accounting • Edit Dana Supplier')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>.sl-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem}.sl-field{display:grid;gap:.3rem}.sl-field-full{grid-column:1/-1}.sl-field span{color:#64748b;font-size:.72rem;font-weight:900;text-transform:uppercase}.sl-control{min-height:42px;border-radius:12px;border-color:#dbe3ee;box-shadow:none}.sl-actions{display:flex;justify-content:flex-end;gap:.5rem;flex-wrap:wrap}.sl-btn{display:inline-flex;align-items:center;min-height:40px;padding:.55rem .95rem;border:1px solid #dbe3ee;border-radius:999px;background:#fff;color:#0f172a;text-decoration:none;font-size:.83rem;font-weight:850}.sl-btn-primary{color:#fff;background:#0f172a;border-color:#0f172a}@media(max-width:768px){.sl-form-grid{grid-template-columns:1fr}.sl-field-full{grid-column:auto}.sl-actions .sl-btn{flex:1;justify-content:center}}</style>
@endpush

@section('content')
    <x-gf.page eyebrow="Accounting" title="Edit Dana Supplier" description="Perubahan hanya tersedia selama transaksi masih DRAFT.">
        <x-slot:actions><div class="sl-actions"><a class="sl-btn" href="{{ route('accounting.supplier-loans.show', $supplierLoan) }}">Detail</a></div></x-slot:actions>
        <x-gf.panel title="Informasi Dana Supplier">
            <form method="POST" action="{{ route('accounting.supplier-loans.update', $supplierLoan) }}">@csrf @method('PUT')
                @include('accounting.supplier_loans._form', ['supplierLoan' => $supplierLoan])
                <div class="sl-actions mt-3"><a class="sl-btn" href="{{ route('accounting.supplier-loans.show', $supplierLoan) }}">Batal</a><button class="sl-btn sl-btn-primary" type="submit">Simpan Perubahan</button></div>
            </form>
        </x-gf.panel>
    </x-gf.page>
@endsection
