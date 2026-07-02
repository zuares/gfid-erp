@extends('storefront.layouts.auth')

@section('title', 'Daftar — Greatfit')

@push('styles')
<style>
    :root {
        --black: var(--ink);
        --gray-50: var(--soft);
        --gray-100: #f0f0f0;
        --gray-200: #e0e0e0;
        --gray-500: var(--mid);
        --gray-700: #444;
        --radius: 12px;
        --font: var(--font-body);
        --font-condensed: var(--font-display);
    }

    .brand { text-decoration: none; display: flex; align-items: center; gap: 10px; margin-bottom: 28px; }
    .brand-mark { width: 38px; height: 38px; flex-shrink: 0; }
    .brand-name { font-family: var(--font-condensed); font-size: 30px; font-weight: 700; color: var(--black); letter-spacing: -0.5px; line-height: 1; }
    .brand-name span { color: var(--accent-dark); }

    .card { background: var(--white); border-radius: var(--radius); border: 1px solid var(--gray-200); width: 100%; max-width: 420px; padding: 32px 28px; }
    .card-title { font-size: 22px; font-weight: 700; color: var(--black); margin-bottom: 6px; }
    .card-sub { font-size: 14px; color: var(--gray-500); margin-bottom: 24px; line-height: 1.5; }

    .alert { padding: 12px 14px; border-radius: 8px; font-size: 13.5px; margin-bottom: 18px; }
    .alert-error { background: #fff0f0; color: #c0392b; border: 1px solid #fad0d0; }
    .alert-info  { background: #f0f7ff; color: #1a6bb5; border: 1px solid #c8dff6; }

    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--gray-700); margin-bottom: 6px; }
    .form-hint { font-size: 12px; color: var(--gray-500); margin-top: 5px; }
    .form-input-wrap { border: 1.5px solid var(--gray-200); border-radius: 8px; overflow: hidden; transition: border-color .15s; }
    .form-input-wrap:focus-within { border-color: var(--black); }
    .form-input { width: 100%; padding: 12px 14px; font-family: var(--font); font-size: 15px; color: var(--black); border: none; background: none; outline: none; }
    .form-input::placeholder { color: var(--gray-500); }
    .phone-wrap { display: flex; border: 1.5px solid var(--gray-200); border-radius: 8px; overflow: hidden; transition: border-color .15s; }
    .phone-wrap:focus-within { border-color: var(--black); }
    .phone-prefix { background: var(--gray-100); padding: 0 12px; display: flex; align-items: center; font-size: 14px; color: var(--gray-700); font-weight: 600; white-space: nowrap; border-right: 1.5px solid var(--gray-200); }

    .btn-submit { width: 100%; padding: 14px; background: var(--black); color: var(--white); font-family: var(--font); font-size: 15px; font-weight: 700; border: none; border-radius: 8px; cursor: pointer; transition: background .15s; margin-top: 4px; }
    .btn-submit:hover { background: #333; }

    .divider { text-align: center; margin: 18px 0; position: relative; color: var(--gray-500); font-size: 13px; }
    .divider::before, .divider::after { content: ''; position: absolute; top: 50%; width: 42%; height: 1px; background: var(--gray-200); }
    .divider::before { left: 0; }
    .divider::after  { right: 0; }

    .link-login { display: block; text-align: center; font-size: 14px; color: var(--gray-700); }
    .link-login a { color: var(--black); font-weight: 700; text-decoration: none; }
    .link-login a:hover { text-decoration: underline; }

    .terms { font-size: 12px; color: var(--gray-500); text-align: center; margin-top: 16px; line-height: 1.6; }

    .footer-link { margin-top: 20px; font-size: 13px; color: var(--gray-500); text-align: center; }
    .footer-link a { color: var(--gray-700); text-decoration: none; font-weight: 500; }
</style>
@endpush

@section('content')
<a href="{{ route('storefront.home') }}" class="brand">
    <img src="{{ asset('images/logo-mark.svg') }}" alt="Greatfit" class="brand-mark">
    <div class="brand-name">GREAT<span>FIT</span></div>
</a>

<div class="card">
    <div class="card-title">Buat akun baru</div>
    <div class="card-sub">Daftar untuk menyimpan pesanan & mempercepat checkout.</div>

    @if(!empty($error))
        <div class="alert alert-error">{{ $error }}</div>
    @endif

    @if(!empty($info))
        <div class="alert alert-info">{{ $info }}</div>
    @endif

    <form method="POST" action="{{ route('storefront.register.post') }}">
        @csrf

        <div class="form-group">
            <label class="form-label">Nama lengkap</label>
            <div class="form-input-wrap">
                <input type="text" name="name" class="form-input" placeholder="Nama kamu" value="{{ old('name') }}" autofocus required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Nomor WhatsApp</label>
            <div class="phone-wrap">
                <span class="phone-prefix">🇮🇩 +62</span>
                <input type="tel" name="phone" class="form-input" placeholder="812 3456 7890" inputmode="numeric" value="{{ old('phone', $prefillPhone) }}" required>
            </div>
            <div class="form-hint">OTP verifikasi dikirim via WhatsApp ke nomor ini.</div>
        </div>

        <div class="form-group">
            <label class="form-label">Email <span style="color:var(--gray-500);font-weight:400">(opsional)</span></label>
            <div class="form-input-wrap">
                <input type="email" name="email" class="form-input" placeholder="email@kamu.com" value="{{ old('email') }}">
            </div>
        </div>

        <button type="submit" class="btn-submit">Daftar & Kirim OTP</button>
        <p class="terms">Dengan mendaftar, kamu menyetujui syarat & ketentuan Greatfit.</p>
    </form>

    <div class="divider">sudah punya akun?</div>
    <p class="link-login"><a href="{{ route('storefront.login') }}">Masuk ke akun kamu</a></p>
</div>

<div class="footer-link">
    <a href="{{ route('storefront.home') }}">← Kembali ke toko</a>
</div>
@endsection
