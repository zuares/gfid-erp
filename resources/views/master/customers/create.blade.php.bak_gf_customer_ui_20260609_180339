@extends('layouts.app')

@section('title', 'Customer Baru')

@section('content')
    <div class="page-wrap">
        <h1 class="h4 mb-3">Customer Baru</h1>

        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 small">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('master.customers.store') }}" method="POST" autocomplete="off">
                    @include('master.customers._form', ['customer' => $customer])
                </form>
            </div>
        </div>
    </div>
@endsection
