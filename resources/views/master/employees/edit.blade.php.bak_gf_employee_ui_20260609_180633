@extends('layouts.app')

@section('title', 'Edit Karyawan')

@section('content')
    <div class="page-wrap">
        <h1 class="h4 mb-3">Edit Karyawan</h1>

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
                <form action="{{ route('master.employees.update', $employee) }}" method="POST" autocomplete="off">
                    @method('PUT')
                    @include('master.employees._form')
                </form>
            </div>
        </div>
    </div>
@endsection
