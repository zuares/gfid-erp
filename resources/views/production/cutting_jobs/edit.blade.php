@extends('layouts.app')

@section('title', 'Edit Cutting Job')

@section('content')
    <div class="page-wrap">

        @php $mode = 'edit'; @endphp

        @include('production.cutting_jobs._form')

    </div>
@endsection
