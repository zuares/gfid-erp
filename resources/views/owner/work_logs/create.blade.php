@extends('layouts.app')

@section('title', 'Tambah Log Pengerjaan')

@include('owner.work_logs._style')

@section('content')
    <x-gf.page eyebrow="Owner" title="Tambah Log Pengerjaan" description="Catat pekerjaan web agar riwayat update mudah dilacak.">
        <x-slot:actions>
            <div class="owl-actions">
                <a class="owl-btn" href="{{ route('owner.work-logs.index') }}">Daftar Log</a>
            </div>
        </x-slot:actions>

        <x-gf.panel title="Form Log" subtitle="Isi perubahan penting yang perlu diketahui owner.">
            <form method="POST" action="{{ route('owner.work-logs.store') }}">
                @include('owner.work_logs._form')

                <div class="owl-actions mt-3">
                    <a class="owl-btn" href="{{ route('owner.work-logs.index') }}">Batal</a>
                    <button class="owl-btn owl-btn-primary" type="submit">Simpan Log</button>
                </div>
            </form>
        </x-gf.panel>
    </x-gf.page>
@endsection
