@extends('layouts.app')

@section('title', 'Input QC — ' . $purchase_receipt->code)

@section('content')
<div class="container py-3">
    <h1 class="h5 fw-bold mb-3">Input QC — {{ $purchase_receipt->code }}</h1>

    <form method="POST"
        action="{{ route('purchasing.purchase_receipts.qc.store', $purchase_receipt->id) }}">
        @csrf

        @include('purchasing.purchase_receipts.qc._form')
    </form>
</div>
@endsection
