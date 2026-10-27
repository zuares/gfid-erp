@extends('layouts.app')

@section('title', 'Akuntansi Baru • ' . $module['title'])

@push('head')
    <style>
        .journal-module-page { max-width: 960px; margin-inline: auto; display: grid; gap: 1rem; }
        .journal-module-icon { display: inline-grid; place-items: center; width: 48px; height: 48px; border-radius: 14px; background: #eef2ff; color: #4338ca; font-size: 1.35rem; }
        .journal-module-btn { display: inline-flex; align-items: center; min-height: 38px; padding: .45rem .85rem; border: 1px solid #dbe3ee; border-radius: 10px; background: #fff; color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 700; }
        .journal-module-btn:hover { background: #f8fafc; color: #0f172a; }
        .journal-module-btn.primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .journal-module-btn.primary:hover { background: #1e293b; color: #fff; }
    </style>
@endpush

@section('content')
    <div class="journal-module-page">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div class="d-flex gap-3 align-items-start">
                <span class="journal-module-icon"><i class="bi {{ $module['icon'] }}" aria-hidden="true"></i></span>
                <div>
                    <h4 class="mb-1">{{ $module['title'] }}</h4>
                    <div class="text-muted small">{{ $module['description'] }}</div>
                </div>
            </div>
            <a class="journal-module-btn" href="{{ route('standalone-accounting.journal-menu.index') }}">Kembali ke Menu Jurnal</a>
        </div>

        <div class="alert alert-warning mb-0">
            Form transaksi khusus untuk menu ini belum dihubungkan ke tabel sumber transaksi. Untuk sementara, gunakan jurnal umum dan isi keterangan serta referensi sumber secara lengkap.
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="mb-3">Panduan pencatatan</h6>
                <ul class="mb-0">
                    @foreach ($module['notes'] as $note)
                        <li class="mb-2">{{ $note }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a class="journal-module-btn primary" href="{{ route('standalone-accounting.journals.create') }}">Buat Jurnal Umum</a>
            <a class="journal-module-btn" href="{{ route('standalone-accounting.journals.index') }}">Lihat Jurnal</a>
        </div>
    </div>
@endsection
