@extends('layouts.app')

@section('title', 'Tambah Item')

@section('content')
<div class="item-crud-page">
    <div class="item-crud-header">
        <div>
            <div class="item-crud-eyebrow"><i class="bi bi-database me-1"></i>Master Data / Item Baru</div>
            <h1 class="item-crud-title">Tambah Item</h1>
        </div>
        <div>
            <a href="{{ route('master.items.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
        </div>
    </div>
    
    <form action="{{ route('master.items.store') }}" method="POST" data-item-form>
        @csrf
        @include('master.items._form')
    </form>
</div>
@endsection
