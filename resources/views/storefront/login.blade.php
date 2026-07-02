@extends('storefront.layouts.auth')

@section('title', 'Masuk — Greatfit')

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

    .card { background: var(--white); border-radius: var(--radius); border: 1px solid var(--gray-200); width: 100%; max-width: 420px; overflow: hidden; }
    .tabs { display: flex; border-bottom: 1px solid var(--gray-200); }
    .tab-btn { flex: 1; padding: 14px; font-family: var(--font); font-size: 14px; font-weight: 600; color: var(--gray-500); background: none; border: none; cursor: pointer; border-bottom: 3px solid transparent; transition: all .15s; }
    .tab-btn.active { color: var(--black); border-bottom-color: var(--black); background: var(--gray-50); }

    .tab-pane { display: none; padding: 28px 24px; }
    .tab-pane.active { display: block; }
    .pane-title { font-size: 20px; font-weight: 700; color: var(--black); margin-bottom: 6px; }
    .pane-sub { font-size: 13.5px; color: var(--gray-500); margin-bottom: 22px; line-height: 1.5; }

    .alert { padding: 12px 14px; border-radius: 8px; font-size: 13.5px; margin-bottom: 18px; }
    .alert-error { background: #fff0f0; color: #c0392b; border: 1px solid #fad0d0; }
    .alert-info  { background: #f0f7ff; color: #1a6bb5; border: 1px solid #c8dff6; }

    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--gray-700); margin-bottom: 6px; }
    .phone-wrap { display: flex; border: 1.5px solid var(--gray-200); border-radius: 8px; overflow: hidden; transition: border-color .15s; }
    .phone-wrap:focus-within { border-color: var(--black); }
    .phone-prefix { background: var(--gray-100); padding: 0 12px; display: flex; align-items: center; font-size: 14px; color: var(--gray-700); font-weight: 600; white-space: nowrap; border-right: 1.5px solid var(--gray-200); }
    .form-input { flex: 1; padding: 12px 14px; font-family: var(--font); font-size: 15px; color: var(--black); border: none; background: none; outline: none; }
    .form-input::placeholder { color: var(--gray-500); }

    .btn-submit { width: 100%; padding: 14px; background: var(--black); color: var(--white); font-family: var(--font); font-size: 15px; font-weight: 700; border: none; border-radius: 8px; cursor: pointer; transition: background .15s; margin-top: 4px; }
    .btn-submit:hover { background: #333; }

    .divider { text-align: center; margin: 18px 0; position: relative; color: var(--gray-500); font-size: 13px; }
    .divider::before, .divider::after { content: ''; position: absolute; top: 50%; width: 42%; height: 1px; background: var(--gray-200); }
    .divider::before { left: 0; }
    .divider::after  { right: 0; }

    .link-register { display: block; text-align: center; font-size: 14px; color: var(--gray-700); }
    .link-register a { color: var(--black); font-weight: 700; text-decoration: none; }
    .link-register a:hover { text-decoration: underline; }

    .admin-info { background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 8px; padding: 16px; margin-bottom: 18px; }
    .admin-info p { font-size: 13.5px; color: var(--gray-700); line-height: 1.6; }

    .btn-admin { display: block; width: 100%; padding: 13px; background: none; border: 2px solid var(--black); color: var(--black); font-family: var(--font); font-size: 14px; font-weight: 700; border-radius: 8px; text-align: center; text-decoration: none; transition: all .15s; }
    .btn-admin:hover { background: var(--black); color: var(--white); }

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
    <div class="tabs">
        <button class="tab-btn {{ $tab !== 'admin' ? 'active' : '' }}" onclick="switchTab('customer')">Konsumen</button>
        <button class="tab-btn {{ $tab === 'admin' ? 'active' : '' }}" onclick="switchTab('admin')">Admin</button>
    </div>

    <div id="pane-customer" class="tab-pane {{ $tab !== 'admin' ? 'active' : '' }}">
        <div class="pane-title">Masuk ke akun kamu</div>
        <div class="pane-sub">Masukkan nomor WhatsApp yang terdaftar.</div>

        @if(!empty($error))
            <div class="alert alert-error">{{ $error }}</div>
        @endif

        <form method="POST" action="{{ route('storefront.login.post') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Nomor WhatsApp</label>
                <div class="phone-wrap">
                    <span class="phone-prefix">🇮🇩 +62</span>
                    <input type="tel" name="phone" class="form-input" placeholder="812 3456 7890" inputmode="numeric" autofocus value="{{ old('phone', request('phone')) }}">
                </div>
            </div>
            <button type="submit" class="btn-submit">Kirim Kode OTP</button>
        </form>

        <div class="divider">atau</div>
        <p class="link-register">Belum punya akun? <a href="{{ route('storefront.register') }}">Daftar sekarang</a></p>
    </div>

    <div id="pane-admin" class="tab-pane {{ $tab === 'admin' ? 'active' : '' }}">
        <div class="pane-title">Dashboard Admin</div>
        <div class="pane-sub">Khusus untuk pengelola toko Greatfit.</div>
        <div class="admin-info">
            <p>Gunakan akun admin yang sudah terdaftar di sistem. Login admin menggunakan email & password.</p>
        </div>
        <a href="{{ route('login') }}" class="btn-admin">Masuk sebagai Admin</a>
    </div>
</div>

<div class="footer-link">
    <a href="{{ route('storefront.home') }}">← Kembali ke toko</a>
</div>
@endsection

@push('scripts')
<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach((b, i) => {
        b.classList.toggle('active', (i === 0 && tab === 'customer') || (i === 1 && tab === 'admin'));
    });
    document.getElementById('pane-customer').classList.toggle('active', tab === 'customer');
    document.getElementById('pane-admin').classList.toggle('active', tab === 'admin');
}
</script>
@endpush
