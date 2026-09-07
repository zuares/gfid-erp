@extends('layouts.app')

@section('title', 'Accounting • Input Saldo Awal Hutang Supplier')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .sap-form { max-width:900px; display:grid; gap:1rem; }
        .sap-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.85rem; }
        .sap-field label { display:block; margin-bottom:.3rem; color:#475569; font-size:.76rem; font-weight:850; }
        .sap-field .form-control,.sap-field .form-select { min-height:42px; border-color:rgba(15,23,42,.12); box-shadow:none; }
        .sap-help { color:#64748b; font-size:.78rem; line-height:1.5; }
        .sap-actions { display:flex; gap:.55rem; justify-content:flex-end; flex-wrap:wrap; }
        .sap-btn { display:inline-flex; align-items:center; justify-content:center; min-height:40px; padding:.55rem .95rem; border-radius:999px; border:1px solid rgba(15,23,42,.1); background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:850; }
        .sap-btn-primary { color:#fff; background:#0f172a; border-color:#0f172a; }
        @media(max-width:700px){ .sap-grid{grid-template-columns:1fr;} }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Accounting"
        title="Input Saldo Awal Hutang Supplier"
        description="Jurnal akan langsung diposting sebagai Debit akun lawan dan Kredit Hutang Dagang.">
        <x-slot:actions>
            <a href="{{ route('accounting.supplier-ap-openings.index') }}" class="sap-btn">← Kembali</a>
        </x-slot:actions>

        <div class="sap-form">
            @if ($errors->any())
                <div class="alert alert-danger mb-0">
                    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <x-gf.panel title="Detail saldo awal" subtitle="Satu baris dapat mewakili satu invoice atau kelompok hutang dari supplier.">
                <form method="POST" action="{{ route('accounting.supplier-ap-openings.store') }}" class="sap-grid">
                    @csrf
                    <div class="sap-field">
                        <label for="supplier_id">Supplier *</label>
                        <select id="supplier_id" name="supplier_id" class="form-select" required>
                            <option value="">Pilih supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', request('supplier_id')) === (string) $supplier->id)>
                                    {{ $supplier->name }}{{ $supplier->code ? ' · '.$supplier->code : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sap-field">
                        <label for="amount">Nominal hutang *</label>
                        <input id="amount" type="text" name="amount" class="form-control" value="{{ old('amount') }}" placeholder="Contoh: 10.000.000" required>
                    </div>
                    <div class="sap-field">
                        <label for="date">Tanggal saldo awal *</label>
                        <input id="date" type="date" name="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" required>
                        <div class="sap-help mt-1">Tanggal jurnal dan batas awal posisi hutang.</div>
                    </div>
                    <div class="sap-field">
                        <label for="invoice_date">Tanggal invoice</label>
                        <input id="invoice_date" type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date') }}">
                        <div class="sap-help mt-1">Dipakai untuk aging; kosong akan memakai tanggal saldo awal.</div>
                    </div>
                    <div class="sap-field">
                        <label for="due_date">Jatuh tempo</label>
                        <input id="due_date" type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                    </div>
                    <div class="sap-field">
                        <label for="reference_no">Nomor invoice / referensi</label>
                        <input id="reference_no" type="text" name="reference_no" class="form-control" value="{{ old('reference_no') }}" maxlength="100" placeholder="Contoh: INV-LAMA-001">
                    </div>
                    <div class="sap-field">
                        <label for="ap_account_id">Akun Hutang Dagang *</label>
                        <select id="ap_account_id" name="ap_account_id" class="form-select" required>
                            @foreach ($accounts->where('type', 'liability') as $account)
                                <option value="{{ $account->id }}" @selected((string) old('ap_account_id', $defaultAp?->id) === (string) $account->id)>{{ $account->code }} · {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sap-field">
                        <label for="offset_account_id">Akun lawan *</label>
                        <select id="offset_account_id" name="offset_account_id" class="form-select" required>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" @selected((string) old('offset_account_id', $defaultOffset?->id) === (string) $account->id)>{{ $account->code }} · {{ $account->name }} ({{ $account->type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sap-field" style="grid-column:1/-1">
                        <label for="notes">Catatan</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Keterangan tambahan">{{ old('notes') }}</textarea>
                    </div>
                    <div style="grid-column:1/-1" class="sap-actions">
                        <a href="{{ route('accounting.supplier-ap-openings.index') }}" class="sap-btn">Batal</a>
                        <button type="submit" class="sap-btn sap-btn-primary" data-gf-confirm-title="Posting saldo awal?" data-gf-confirm-text="Jurnal opening balance akan langsung dibuat dan muncul di AP Report.">Posting Saldo Awal</button>
                    </div>
                </form>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
