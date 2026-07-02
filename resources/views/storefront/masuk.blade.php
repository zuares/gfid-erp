<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>Masuk — Greatfit</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Barlow+Condensed:wght@800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --ink: #0a0a0a;
            --mid: #666;
            --line: #e8e8e8;
            --soft: #f5f5f5;
            --white: #fff;
            --safe: env(safe-area-inset-bottom, 0px);
        }
        html { min-height: 100%; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: var(--ink); background: var(--soft);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        a { color: inherit; text-decoration: none; }
        img { display: block; max-width: 100%; }

        /* NAV */
        .nav {
            background: var(--white);
            border-bottom: 1px solid var(--line);
            position: sticky; top: 0; z-index: 100;
        }
        .nav-inner {
            height: 56px; display: flex; align-items: center;
            justify-content: space-between;
            max-width: 1680px; margin: 0 auto; padding: 0 20px;
        }
        .brand { display: flex; align-items: center; gap: 8px; font-weight: 900; font-size: 12px; letter-spacing: .16em; text-transform: uppercase; }
        .brand img { width: 26px; height: 26px; }
        .nav-back {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 700; color: var(--mid);
            transition: color .15s;
        }
        .nav-back:hover { color: var(--ink); }

        /* LAYOUT */
        main {
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 32px 20px calc(48px + var(--safe));
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 2px 16px rgba(0,0,0,.06);
        }

        /* HEADER */
        .lc-head {
            padding: 28px 28px 20px;
            border-bottom: 1px solid var(--line);
        }
        .lc-logo {
            display: flex; align-items: center; gap: 8px;
            font-weight: 900; font-size: 11px; letter-spacing: .16em;
            text-transform: uppercase; margin-bottom: 18px;
        }
        .lc-logo img { width: 22px; height: 22px; }
        .lc-title {
            font-size: 22px; font-weight: 900; letter-spacing: -.04em; line-height: 1.1;
            margin-bottom: 5px;
        }
        .lc-sub { font-size: 13px; color: var(--mid); font-weight: 500; line-height: 1.5; }

        /* TABS */
        .tabs {
            display: grid; grid-template-columns: 1fr 1fr;
            border-bottom: 1px solid var(--line);
        }
        .tab-btn {
            padding: 14px 16px;
            background: none; border: none; border-bottom: 2px solid transparent;
            font-family: inherit; font-size: 12px; font-weight: 800;
            letter-spacing: .04em; text-transform: uppercase;
            color: var(--mid); cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 7px;
            transition: color .15s, border-color .15s;
            margin-bottom: -1px;
        }
        .tab-btn.active { color: var(--ink); border-bottom-color: var(--ink); }
        .tab-btn svg { opacity: .5; }
        .tab-btn.active svg { opacity: 1; }

        /* PANELS */
        .tab-panel { display: none; padding: 24px 28px 28px; }
        .tab-panel.active { display: block; }

        /* FORM */
        .form-group { margin-bottom: 16px; }
        .form-label {
            display: block; font-size: 11px; font-weight: 800;
            letter-spacing: .06em; text-transform: uppercase;
            color: var(--mid); margin-bottom: 7px;
        }
        .form-input-wrap { position: relative; }
        .form-prefix {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            font-size: 14px; font-weight: 700; color: var(--mid);
            pointer-events: none;
            padding-right: 10px;
            border-right: 1px solid var(--line);
            line-height: 1;
        }
        .form-input {
            width: 100%; height: 48px;
            padding: 0 14px 0 68px;
            border: 1.5px solid var(--line); border-radius: 12px;
            font-family: inherit; font-size: 15px; font-weight: 700;
            color: var(--ink); background: var(--white);
            outline: none; transition: border-color .15s;
            -webkit-appearance: none;
        }
        .form-input:focus { border-color: var(--ink); }
        .form-input.err { border-color: #ef4444; }
        .form-hint { margin-top: 6px; font-size: 11px; color: var(--mid); font-weight: 500; }

        /* ERROR */
        .err-box {
            background: #fef2f2; border: 1px solid #fecaca;
            border-radius: 10px; padding: 11px 14px; margin-bottom: 16px;
            font-size: 12px; font-weight: 600; color: #b91c1c;
            display: flex; align-items: flex-start; gap: 8px; line-height: 1.45;
        }
        .err-box svg { flex-shrink: 0; margin-top: 1px; }

        /* BUTTONS */
        .btn-primary {
            width: 100%; height: 48px; border-radius: 12px;
            background: var(--ink); color: var(--white);
            font-family: inherit; font-size: 14px; font-weight: 800;
            border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: opacity .15s; margin-bottom: 0;
        }
        .btn-primary:hover { opacity: .85; }
        .btn-primary:active { opacity: .7; }

        /* ADMIN PANEL */
        .admin-panel-inner {
            display: flex; flex-direction: column; gap: 12px;
        }
        .admin-info {
            background: var(--soft); border-radius: 14px; padding: 16px;
            display: flex; gap: 12px; align-items: flex-start;
        }
        .admin-info-ic {
            width: 36px; height: 36px; border-radius: 10px;
            background: var(--ink); color: var(--white);
            display: grid; place-items: center; flex-shrink: 0;
        }
        .admin-info-t { font-size: 13px; font-weight: 800; margin-bottom: 3px; }
        .admin-info-s { font-size: 12px; color: var(--mid); font-weight: 500; line-height: 1.5; }
        .btn-admin {
            width: 100%; height: 48px; border-radius: 12px;
            background: var(--ink); color: var(--white);
            font-family: inherit; font-size: 14px; font-weight: 800;
            border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            text-decoration: none; transition: opacity .15s;
        }
        .btn-admin:hover { opacity: .85; }
        .btn-admin-features {
            display: flex; flex-direction: column; gap: 8px; margin-top: 4px;
        }
        .btn-af {
            display: flex; align-items: center; gap: 9px;
            font-size: 12px; font-weight: 600; color: var(--mid);
        }
        .btn-af svg { color: var(--mid); flex-shrink: 0; }

        /* FOOTER NOTE */
        .lc-foot {
            padding: 14px 28px;
            border-top: 1px solid var(--line);
            font-size: 11px; color: var(--mid); font-weight: 500;
            text-align: center; line-height: 1.5;
        }
        .lc-foot a { font-weight: 700; color: var(--ink); }

        @@media (min-width: 720px) {
            .nav-inner { padding: 0 32px; }
            main { padding-top: 48px; }
        }
    </style>
</head>
<body>

<header class="nav">
    <div class="nav-inner">
        <a href="{{ route('storefront.home') }}" class="brand">
            <img src="{{ asset('images/logo-mark.svg') }}" alt="Greatfit">
            Greatfit
        </a>
        <a href="{{ route('storefront.home') }}" class="nav-back">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Kembali
        </a>
    </div>
</header>

<main>
    <div class="login-card">

        {{-- HEADER --}}
        <div class="lc-head">
            <div class="lc-logo">
                <img src="{{ asset('images/logo-mark.svg') }}" alt="">
                Greatfit
            </div>
            <h1 class="lc-title">Masuk ke Akun</h1>
            <p class="lc-sub">Konsumen untuk cek pesanan, admin untuk kelola toko.</p>
        </div>

        {{-- TABS --}}
        <div class="tabs">
            <button class="tab-btn active" id="tabKonsumen" onclick="switchTab('konsumen')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Konsumen
            </button>
            <button class="tab-btn" id="tabAdmin" onclick="switchTab('admin')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                Admin
            </button>
        </div>

        {{-- PANEL: KONSUMEN --}}
        <div class="tab-panel active" id="panelKonsumen">
            @if($error)
            <div class="err-box">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                {{ $error }}
            </div>
            @endif

            <form method="POST" action="{{ route('storefront.masuk.post') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="phone">Nomor HP / WhatsApp</label>
                    <div class="form-input-wrap">
                        <span class="form-prefix">+62</span>
                        <input
                            id="phone" name="phone" type="tel" inputmode="numeric"
                            class="form-input {{ $error ? 'err' : '' }}"
                            placeholder="812 3456 7890"
                            value="{{ old('phone') }}"
                            autofocus autocomplete="tel"
                        >
                    </div>
                    <div class="form-hint">Gunakan nomor yang dipakai saat checkout</div>
                </div>

                <button type="submit" class="btn-primary">
                    Lihat Pesanan Saya
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </form>
        </div>

        {{-- PANEL: ADMIN --}}
        <div class="tab-panel" id="panelAdmin">
            <div class="admin-panel-inner">
                <div class="admin-info">
                    <div class="admin-info-ic">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    </div>
                    <div>
                        <div class="admin-info-t">Dashboard Pengelola</div>
                        <div class="admin-info-s">Kelola produk, kategori, pesanan, dan data toko Greatfit.</div>
                    </div>
                </div>

                <div class="btn-admin-features">
                    <div class="btn-af">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Manajemen produk & katalog
                    </div>
                    <div class="btn-af">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                        Kelola & update status pesanan
                    </div>
                    <div class="btn-af">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                        Data customer & visitor CRM
                    </div>
                </div>

                <a href="{{ route('login', [], false) }}" class="btn-admin">
                    Lanjut ke Login Admin
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>

        {{-- FOOTER --}}
        <div class="lc-foot">
            Belum pernah order? <a href="{{ route('storefront.products') }}">Lihat produk kami</a>
        </div>

    </div>
</main>

<script>
function switchTab(tab) {
    var tabs   = ['konsumen', 'admin'];
    tabs.forEach(function(t) {
        document.getElementById('tab' + t.charAt(0).toUpperCase() + t.slice(1)).classList.toggle('active', t === tab);
        document.getElementById('panel' + t.charAt(0).toUpperCase() + t.slice(1)).classList.toggle('active', t === tab);
    });
}
@if($error)
// Kalau ada error, tetap di tab konsumen
@endif
</script>

@include('storefront._tracker')
@include('storefront._mobile_zoom_lock')

</body>
</html>
