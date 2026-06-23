@extends('layouts.app')

@section('title', 'Karyawan Baru')

@section('content')
    <div class="page-wrap">
        <h1 class="h4 mb-3">Karyawan Baru</h1>

        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 small">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('master.employees.store') }}" method="POST" autocomplete="off">
                    @include('master.employees._form')
                </form>
            </div>
        </div>
    </div>
@endsection
