@extends('layouts.app')

@section('title', 'Tambah Item')

@section('content')
    <div class="page-wrap">

        <form action="{{ route('master.items.store') }}" method="POST">
            @csrf

            @include('master.items._form')

        </form>

    </div>
@endsection
