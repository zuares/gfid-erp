@extends('layouts.app')

@section('title', 'GFID Design System')

@push('head')
    @vite('resources/js/design-system.jsx')
@endpush

@section('content')
    <div id="react-design-system" aria-label="GFID React design system"></div>
@endsection
