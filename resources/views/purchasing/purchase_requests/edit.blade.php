@extends('layouts.app')

@section('title', 'Edit PR — ' . $purchase_request->code)

@section('content')
<div style="max-width:960px; margin-inline:auto; padding-bottom:3rem;">

    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('purchasing.purchase_requests.show', $purchase_request->id) }}"
            class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">← Kembali</a>
        <h1 class="h5 mb-0 fw-bold">Edit PR — {{ $purchase_request->code }}</h1>
    </div>

    @if (!$purchase_request->isDraft())
        <div class="alert alert-warning py-2">
            PR ini berstatus <strong>{{ pr_status_label($purchase_request->status) }}</strong> dan tidak bisa diedit.
        </div>
        <a href="{{ route('purchasing.purchase_requests.show', $purchase_request->id) }}"
            class="btn btn-outline-secondary">← Kembali ke Detail</a>
    @else

        @if ($errors->any())
            <div class="alert alert-danger py-2">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
            action="{{ route('purchasing.purchase_requests.update', $purchase_request->id) }}">
            @csrf
            @method('PUT')

            @include('purchasing.purchase_requests._form', [
                'pr'          => $purchase_request,
                'suppliers'   => $suppliers,
                'items'       => $items,
                'canSeeMoney' => $canSeeMoney,
                'linesData'   => $linesData,
            ])

            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('purchasing.purchase_requests.show', $purchase_request->id) }}"
                    class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>

    @endif

</div>
@endsection
