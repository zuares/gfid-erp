@extends('layouts.app')

@section('title', 'Accounting • Edit Transfer Kas/Bank')

@push('head')
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
        @media (max-width: 768px) { .ct-form-grid { grid-template-columns: 1fr; } .ct-field-full { grid-column: auto; } .ct-actions { justify-content: stretch; } .ct-actions .ct-btn { flex: 1; } }
    </style>
@endpush

@section('content')
    <x-gf.page eyebrow="Accounting" title="Edit Transfer Kas/Bank" description="Hanya transfer Draft yang bisa diubah sebelum diposting.">
        <x-slot:actions><div class="ct-actions"><a class="ct-btn" href="{{ route('accounting.cash-transfers.show', $cashTransfer) }}">Detail</a><a class="ct-btn" href="{{ route('accounting.cash-transfers.index') }}">Daftar Transfer</a></div></x-slot:actions>
        <div class="ct-edit-page">
            <x-gf.panel title="Form Transfer" subtitle="Update data draft lalu posting dari halaman detail.">
                <form method="POST" action="{{ route('accounting.cash-transfers.update', $cashTransfer) }}" data-gf-confirm data-gf-confirm-title="Update draft?" data-gf-confirm-text="Perubahan transfer draft akan disimpan." data-gf-confirm-ok="Ya, update">
                    @csrf @method('PUT')
                    @include('accounting.cash_transfers._form', ['cashTransfer' => $cashTransfer])
                    <div class="d-flex justify-content-end gap-2 flex-wrap mt-3"><a class="ct-btn" href="{{ route('accounting.cash-transfers.show', $cashTransfer) }}">Batal</a><button class="ct-btn ct-btn-primary" type="submit">Update Draft</button></div>
                </form>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
