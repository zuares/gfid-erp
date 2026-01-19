@extends('layouts.app')

@section('title', 'New Account')

@section('content')
    <div class="container" style="max-width:650px">
        <h4 class="mb-3">New Account</h4>

        <form method="POST" action="{{ route('accounting.accounts.store') }}">
            @csrf

            @include('accounting.accounts._form')

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('accounting.accounts.index') }}" class="btn btn-light">Cancel</a>
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
@endsection
