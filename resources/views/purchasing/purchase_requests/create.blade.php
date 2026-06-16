@extends('layouts.app')

@section('title', 'PR Baru')

@section('content')
<div style="max-width:960px; margin-inline:auto; padding-bottom:3rem;">

    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('purchasing.purchase_requests.index') }}"
            class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">← Kembali</a>
        <h1 class="h5 mb-0 fw-bold">Purchase Request Baru</h1>
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

        <div class="d-flex gap-2 justify-content-end mt-2">
            <a href="{{ route('purchasing.purchase_requests.index') }}"
                class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan PR</button>
        </div>
    </form>

</div>
@endsection
