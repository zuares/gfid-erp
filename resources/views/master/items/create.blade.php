@extends('layouts.app')

@section('title', 'Tambah Item')

@section('content')
<div class="page-wrap">
    <div class="item-topbar">
        <div>
            <div class="title">Tambah Item</div>
            <div class="sub">Lengkapi data master item baru.</div>
        </div>
        <div class="controls">
            <a href="{{ route('master.items.index') }}" class="btn btn-sm btn-item-outline btn-pill">Kembali</a>
        </div>
    </div>
    
    <form action="{{ route('master.items.store') }}" method="POST">
        @csrf
        @include('master.items._form')
    </form>
</div>
@endsection
