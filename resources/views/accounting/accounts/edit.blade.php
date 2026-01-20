@extends('layouts.app')

@section('title', 'Edit Account')

@section('content')
    <div class="container" style="max-width:650px">
        <h4 class="mb-3">Edit Account</h4>

        <form method="POST" action="{{ route('accounting.accounts.update', $account) }}">
            @csrf
            @method('PUT')

            @include('accounting.accounts.partials.form', ['account' => $account])

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('accounting.accounts.show', $account) }}" class="btn btn-light">Cancel</a>
                <button class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
@endsection
