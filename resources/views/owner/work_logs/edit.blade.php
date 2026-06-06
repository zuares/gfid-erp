@extends('layouts.app')

@section('title', 'Edit Log Pengerjaan')

@include('owner.work_logs._style')

@section('content')
    <x-gf.page eyebrow="Owner" title="Edit Log Pengerjaan" description="{{ $log->title }}">
        <x-slot:actions>
            <div class="owl-actions">
                <a class="owl-btn" href="{{ route('owner.work-logs.show', $log) }}">Detail</a>
                <a class="owl-btn" href="{{ route('owner.work-logs.index') }}">Daftar Log</a>
            </div>
        </x-slot:actions>

        <x-gf.panel title="Form Log" subtitle="Update catatan pengerjaan.">
            <form method="POST" action="{{ route('owner.work-logs.update', $log) }}">
                @method('PUT')
                @include('owner.work_logs._form')

                <div class="owl-actions mt-3">
                    <a class="owl-btn" href="{{ route('owner.work-logs.show', $log) }}">Batal</a>
                    <button class="owl-btn owl-btn-primary" type="submit">Update Log</button>
                </div>
            </form>
        </x-gf.panel>
    </x-gf.page>
@endsection
