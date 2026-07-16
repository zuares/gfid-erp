@extends('layouts.app')

@section('title', 'Edit Item • ' . ($item->code ?? ''))

@section('content')
<div class="page-wrap">
    <div class="item-topbar">
        <div>
            <div class="title">Edit Item</div>
            <div class="sub">{{ $item->code }} — {{ $item->name }}</div>
        </div>
        <div class="controls">
            <a href="{{ route('master.items.index') }}" class="btn btn-sm btn-item-outline btn-pill">Kembali</a>
        </div>
    </div>

    @if (session('success'))
        <div class="flash-clean alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-clean alert alert-danger mb-3">{{ session('error') }}</div>
    @endif
    
    <form action="{{ route('master.items.update', $item) }}" method="POST">
        @csrf
        @method('PUT')
        @include('master.items._form')
    </form>
</div>
@endsection
