@extends('layouts.app')

@section('title', 'Edit Purchase Order ' . $order->code)

@section('content')
    <div class="container py-3">
        <h1 class="mb-3">Edit Purchase Order {{ $order->code }}</h1>

        <form action="{{ route('purchasing.purchase_orders.update', $order->id) }}" method="POST">
            @csrf
            @method('PUT')

            @include('purchasing.purchase_orders._form')

            <div class="mt-3 d-flex justify-content-between">
                <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}" class="btn btn-outline-secondary">
                    &larr; Batal
                </a>
                <button type="submit" class="btn btn-primary">
                    Update PO
                </button>
            </div>
        </form>
    </div>
@endsection
