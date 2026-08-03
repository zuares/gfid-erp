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

    .oauth-card {
        margin-top: 18px;
        padding: 16px;
        border-radius: 14px;
        border: 1px solid var(--gray-200);
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    }

    .oauth-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }

    .oauth-title {
        font-size: 13px;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: var(--gray-500);
    }

    .oauth-sub {
        font-size: 13px;
        color: var(--gray-700);
        line-height: 1.5;
        margin-top: 4px;
    }

    .oauth-grid {
        display: grid;
        gap: 10px;
    }

    .oauth-provider {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid var(--gray-200);
        background: var(--white);
        text-decoration: none;
        color: var(--ink);
        transition: transform .15s, box-shadow .15s, border-color .15s;
    }

    .oauth-provider:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 24px rgba(15,23,42,.08);
        border-color: #cbd5e1;
    }

    .oauth-provider-icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        border: 1px solid var(--gray-200);
        flex-shrink: 0;
    }

    .oauth-provider-copy {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .oauth-provider-title {
        font-size: 14px;
        font-weight: 800;
        color: var(--black);
    }

    .oauth-provider-desc {
        font-size: 12px;
        color: var(--gray-500);
    }

    .oauth-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .34rem .7rem;
        border-radius: 999px;
        background: #ecfeff;
        color: #155e75;
        border: 1px solid #cffafe;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
        white-space: nowrap;
    }

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
        @php
            $oauthProviders = collect(config('services.oauth.providers', []))
                ->mapWithKeys(function (array $provider, string $key) {
                    $enabled = filled($provider['client_id'] ?? null) && filled($provider['client_secret'] ?? null);

                    return [
                        $key => [
                            'enabled' => $enabled,
                            'label' => match ($key) {
                                'google' => 'Google',
                                'github' => 'GitHub',
                                default => ucfirst($key),
                            },
                            'description' => match ($key) {
                                'google' => 'Masuk dengan Google untuk belanja di toko Greatfit.',
                                'github' => 'Masuk dengan GitHub untuk akses pelanggan yang sudah diundang.',
                                default => 'Masuk dengan provider OAuth ini.',
                            },
                            'icon' => match ($key) {
                                'google' => 'google',
                                'github' => 'github',
                                default => 'oauth',
                            },
                        ],
                    ];
                })
                ->filter(fn ($provider) => $provider['enabled'] ?? false);
        @endphp

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

        <div class="oauth-card">
            <div class="oauth-head">
                <div>
                    <div class="oauth-title">Login cepat</div>
                    <div class="oauth-sub">Kalau kamu punya akun Google atau GitHub, kamu bisa masuk tanpa OTP.</div>
                </div>
                <span class="oauth-badge"><i class="bi bi-shield-check"></i> Storefront only</span>
            </div>

            @if ($oauthProviders->isNotEmpty())
                <div class="oauth-grid">
                    @foreach ($oauthProviders as $providerKey => $provider)
                        <a href="{{ route('auth.oauth.redirect', ['provider' => $providerKey]) }}" class="oauth-provider">
                            <span class="oauth-provider-icon">
                                @if ($providerKey === 'google')
                                    <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                                        <path fill="#EA4335" d="M12 10.2v3.95h5.62c-.25 1.34-1.47 3.94-5.62 3.94-3.39 0-6.15-2.81-6.15-6.27S8.61 5.55 12 5.55c1.93 0 3.22.82 3.96 1.53l2.69-2.59C16.93 2.92 14.71 2 12 2 6.48 2 2 6.48 2 12s4.48 10 10 10c5.76 0 9.58-4.05 9.58-9.75 0-.66-.07-1.16-.15-1.67H12z"/>
                                        <path fill="#4285F4" d="M3.45 7.38l3.15 2.31C7.46 7.37 9.5 5.55 12 5.55c1.93 0 3.22.82 3.96 1.53l2.69-2.59C16.93 2.92 14.71 2 12 2 8.1 2 4.73 4.19 3.45 7.38z"/>
                                        <path fill="#FBBC05" d="M12 22c2.66 0 4.88-.87 6.5-2.37l-3-2.47c-.84.57-1.98.97-3.5.97-4.11 0-6.32-2.57-6.98-3.92l-3.08 2.36C3.19 19.83 7.17 22 12 22z"/>
                                        <path fill="#34A853" d="M21.58 12.25c0-.67-.07-1.17-.15-1.68H12v3.95h5.62c-.27 1.42-1.53 3.33-3.5 4.64l3 2.47C18.87 19.13 21.58 16.32 21.58 12.25z"/>
                                    </svg>
                                @elseif ($providerKey === 'github')
                                    <i class="bi bi-github" style="font-size: 1.1rem;"></i>
                                @else
                                    <i class="bi bi-shield-lock"></i>
                                @endif
                            </span>
                            <span class="oauth-provider-copy">
                                <span class="oauth-provider-title">Lanjut dengan {{ $provider['label'] }}</span>
                                <span class="oauth-provider-desc">{{ $provider['description'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @else
                <div style="font-size:14px;color:#475569;line-height:1.55;">
                    OAuth belum aktif. Isi env Google atau GitHub supaya tombol login cepat muncul di sini.
                </div>
            @endif
        </div>

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
