@extends('layouts.app')

@section('title', 'Edit Item • ' . ($item->code ?? ''))

@section('content')
<div class="item-crud-page">
    <div class="item-crud-header">
        <div>
            <div class="item-crud-eyebrow"><i class="bi bi-pencil-square me-1"></i>Master Data / Edit Item</div>
            <h1 class="item-crud-title">Edit Item <span class="text-muted">· {{ $item->code }}</span></h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('master.items.show', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1"></i>Lihat</a>
            <a href="{{ route('master.items.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
        </div>
    </div>

    <form action="{{ route('master.items.update', $item) }}" method="POST" data-item-form>
        @csrf
        @method('PUT')
        @include('master.items._form')
    </form>
</div>
@endsection
