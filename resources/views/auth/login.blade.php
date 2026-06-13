@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="container py-5" style="max-width: 420px;">
        <h1 class="h4 mb-4 text-center">Login GFID</h1>

        <form method="POST" action="{{ route('login.post', [], false) }}">
            @csrf

            <div class="mb-3">
                <label for="employee_code" class="form-label">Employee Code</label>
                <input type="text" id="employee_code" name="employee_code" value="{{ old('employee_code') }}"
                    class="form-control @error('employee_code') is-invalid @enderror" autofocus>
                @error('employee_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password"
                    class="form-control @error('password') is-invalid @enderror">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Ingat saya</label>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                Masuk
            </button>
        </form>
    </div>
@endsection
