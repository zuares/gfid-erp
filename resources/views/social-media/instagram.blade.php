@extends('layouts.app')

@section('title', 'Instagram • Koneksi')

@section('content')
<div class="container py-4" style="max-width: 980px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h4 mb-1">Instagram Platform</h1>
            <p class="text-muted mb-0">Hubungkan akun Instagram Business atau Creator melalui Instagram Login.</p>
        </div>
        <a href="{{ route('social-media.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Social Media
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h2 class="h6 mb-2">Business Login for Instagram</h2>
                    <p class="text-muted small mb-2">
                        Tahap ini hanya menyimpan koneksi akun dan token terenkripsi. Fitur publish,
                        komentar, messaging, dan insights akan ditambahkan setelah koneksi dasar tervalidasi.
                    </p>
                    <div class="small text-muted">
                        Scope yang diminta:
                        <code>{{ implode(', ', $scopes) ?: 'belum dikonfigurasi' }}</code>
                    </div>
                </div>
                @if($configured)
                    <a href="{{ route('social-media.instagram.connect') }}" class="btn btn-primary text-nowrap">
                        <i class="bi bi-instagram me-1"></i>Hubungkan Instagram
                    </a>
                @else
                    <span class="badge text-bg-warning text-nowrap">Belum dikonfigurasi</span>
                @endif
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent"><strong>Koneksi tersimpan</strong></div>
        <div class="card-body">
            @forelse($connections as $connection)
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom py-3">
                    <div>
                        <div class="fw-semibold">{{ $connection->username ? '@'.$connection->username : 'Instagram account' }}</div>
                        <div class="small text-muted">
                            ID: {{ $connection->instagram_user_id }} · Status: {{ $connection->status }}
                        </div>
                    </div>
                    <div class="small text-muted text-end">
                        Token berlaku sampai<br>
                        <strong>{{ optional($connection->token_expires_at)->format('d M Y H:i') ?: '—' }}</strong>
                    </div>
                </div>
            @empty
                <div class="text-muted small">Belum ada akun Instagram yang terhubung.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
