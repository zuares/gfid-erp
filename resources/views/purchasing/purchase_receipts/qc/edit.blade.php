@extends('layouts.app')

@section('title', 'Edit QC — ' . $purchase_receipt->code)

@section('content')
<div class="container py-3">
    <h1 class="h5 fw-bold mb-3">Edit QC — {{ $purchase_receipt->code }}</h1>

    <form method="POST"
        action="{{ route('purchasing.purchase_receipts.qc.update', $purchase_receipt->id) }}">
        @csrf
        @method('PUT')

        @include('purchasing.purchase_receipts.qc._form')
    </form>
</div>
@endsection
