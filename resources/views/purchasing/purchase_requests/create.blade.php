@extends('layouts.app')

@section('title', 'PR Baru')

@section('content')
<div style="max-width:1080px; margin-inline:auto; padding-bottom:3rem;" class="py-3 px-2 px-md-0">

    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="mb-0">Purchase Request Baru</h2>
            <div class="text-muted small">Catat kebutuhan barang. Supplier dapat ditentukan otomatis saat membuat PO.</div>
        </div>
        <a href="{{ route('purchasing.purchase_requests.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('purchasing.purchase_requests.store') }}">
        @csrf

        @include('purchasing.purchase_requests._form', [
            'pr'          => null,
            'suppliers'   => $suppliers,
            'items'       => $items,
            'canSeeMoney' => $canSeeMoney,
            'linesData'   => $linesData,
        ])

        <div class="d-flex gap-2 justify-content-end mt-3">
            <a href="{{ route('purchasing.purchase_requests.index') }}"
                class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary px-4">Simpan PR</button>
        </div>
    </form>

</div>
@endsection
