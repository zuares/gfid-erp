@extends('layouts.app')

@section('title', 'Edit Customer • ' . $customer->name)

@section('content')
    <div class="page-wrap">

        <div class="mb-3 d-flex justify-content-between">
            <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">
                ← Kembali
            </a>
        </div>

        <form action="{{ route('customers.update', $customer) }}" method="POST">
            @csrf
            @method('PUT')

            @include('customers._form')

        </form>
    </div>
@endsection
