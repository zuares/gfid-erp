@extends('layouts.app')

@section('title', 'Edit Item • ' . ($item->code ?? ''))

@section('content')
    <div class="page-wrap">

        <form action="{{ route('items.update', $item) }}" method="POST">
            @csrf
            @method('PUT')

            @include('items._form')

        </form>

    </div>
@endsection
