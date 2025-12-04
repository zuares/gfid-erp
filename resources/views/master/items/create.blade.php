@extends('layouts.app')

@section('title', 'Tambah Item')

@section('content')
    <div class="page-wrap">

        <form action="{{ route('items.store') }}" method="POST">
            @csrf

            @include('items._form')

        </form>

    </div>
@endsection
