@extends('layouts.app')

@section('title', 'Beranda')

@push('head')
    @include('dashboard.partials._styles')
@endpush

@section('content')
    <div class="dash">
        <div class="dash-hero">
            <div>
                <h1>{{ $greeting }}, {{ $userName }} 👋</h1>
                <div class="sub">Ini ringkasan kerja hari ini. Klik kartu untuk langsung menuju halamannya.</div>
                <span class="role-chip">
                    <i class="bi bi-person-badge"></i>
                    {{ ['owner' => 'Pemilik', 'admin' => 'Admin', 'operating' => 'Operator'][$role] ?? 'User' }}
                </span>
            </div>
            <div class="hero-date">
                <i class="bi bi-calendar3"></i> {{ $today }}
            </div>
        </div>

        @includeFirst(
            ['dashboard.partials.' . $role, 'dashboard.partials.generic'],
            ['d' => $d]
        )
    </div>
@endsection
