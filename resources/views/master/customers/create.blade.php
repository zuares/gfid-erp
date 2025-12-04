@extends('layouts.app')

@section('title', 'Tambah Customer')

@push('head')
    {{-- pakai style sama dengan index --}}
@endpush

@section('content')
    <div class="page-wrap">

        <div class="mb-3">
            <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">
                ← Kembali
            </a>
        </div>

        <form action="{{ route('customers.store') }}" method="POST">
            @csrf

            @include('customers._form')

        </form>
    </div>
@endsection
