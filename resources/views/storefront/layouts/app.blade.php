<!doctype html>
<html lang="id" class="{{ config('storefront.theme', '') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>@yield('title', 'Greatfit')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Barlow+Condensed:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ═══════════════════════════════════════════════════════════════════
         * STOREFRONT BASE STYLES
         * Shared across all storefront pages. Page-specific CSS goes in
         * @push('styles') within each view.
         * ════════════════════════════════════════════════════════════════ */

        /* ── Reset ────────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Theme Tokens ──────────────────────────────────────────────
         * Override per-theme by adding a class to <html>.
         * Set STOREFRONT_THEME in .env (e.g. theme-dark, theme-warm).
         * ─────────────────────────────────────────────────────────── */
        :root {
            /* Colors */
            --ink:          #0a0a0a;
            --mid:          #888;
            --line:         #e8e8e8;
            --soft:         #f4f4f4;
            --white:        #fff;
            --accent:       #E8FF00;
            --accent-dark:  #b8c800;
            /* Typography */
            --font-body:    'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --font-display: 'Barlow Condensed', sans-serif;
            /* Spacing & shape */
            --safe:         env(safe-area-inset-bottom, 0px);
            --radius-card:  18px;
            --radius-pill:  999px;
        }

        /* Theme: dark */
        .theme-dark {
            --ink:   #f0f0f0;
            --mid:   #999;
            --line:  #2a2a2a;
            --soft:  #141414;
            --white: #0d0d0d;
        }

        /* Theme: warm */
        .theme-warm {
            --ink:         #1a0f05;
            --mid:         #8a7060;
            --line:        #e8dfd6;
            --soft:        #faf6f2;
            --white:       #fffefb;
            --accent:      #ff6b35;
            --accent-dark: #e05520;
        }

        /* ── Base ─────────────────────────────────────────────────────── */
        html { min-height: 100%; }
        body {
            font-family: var(--font-body);
            color: var(--ink);
            background: var(--white);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex; flex-direction: column;
        }
        main { flex: 1; }
        a    { color: inherit; text-decoration: none; }
        img  { display: block; max-width: 100%; }

        /* ── Nav ──────────────────────────────────────────────────────── */
        .nav {
            position: sticky; top: 0; z-index: 100;
            background: rgba(255,255,255,.96);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--line);
        }
        .nav-inner {
            height: 56px; display: flex; align-items: center;
            justify-content: space-between;
            max-width: 1680px; margin: 0 auto; padding: 0 20px;
        }
        .brand {
            display: inline-flex; align-items: center; gap: 10px;
            min-height: 40px;
            font-weight: 900; font-size: 13px;
            letter-spacing: .18em; line-height: 1;
            text-transform: uppercase;
            color: var(--ink);
        }
        .brand img { width: 32px; height: 32px; object-fit: contain; flex: 0 0 auto; }
        .brand span { transform: translateY(.5px); }
        .nav-r { display: flex; align-items: center; gap: 16px; }
        .nav-links { display: none; gap: 18px; font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--mid); }
        .nav-links a:hover, .nav-links a.active { color: var(--ink); }

        /* Masuk button */
        .nav-btn-masuk {
            display: inline-flex; align-items: center; gap: 5px;
            height: 32px; padding: 0 13px;
            border-radius: var(--radius-pill);
            border: 1.5px solid var(--line);
            background: var(--white); color: var(--ink);
            font-family: var(--font-body);
            font-size: 11px; font-weight: 800;
            letter-spacing: .04em; text-transform: uppercase;
            transition: border-color .15s, background .15s;
            text-decoration: none;
        }
        .nav-btn-masuk:hover { border-color: var(--ink); background: var(--soft); }

        /* Cart icon */
        .cart-icon { position: relative; width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: var(--ink); transition: background .15s; }
        .cart-icon:hover { background: var(--soft); }
        .cart-badge { position: absolute; top: -2px; right: -2px; min-width: 16px; height: 16px; border-radius: var(--radius-pill); padding: 0 3px; background: var(--ink); color: var(--white); font-size: 9px; font-weight: 800; display: grid; place-items: center; border: 2px solid var(--white); }
        .cart-pop { animation: cartPop .4s ease; }
        @@keyframes cartPop { 0%,100%{transform:scale(1)} 40%{transform:scale(1.22)} 70%{transform:scale(.92)} }

        /* User chip */
        .user-menu-wrap { position: relative; }
        .user-chip { display: inline-flex; align-items: center; gap: 7px; height: 34px; padding: 0 10px 0 6px; border-radius: var(--radius-pill); border: 1.5px solid var(--line); background: var(--white); color: var(--ink); font-family: var(--font-body); font-size: 12px; font-weight: 800; cursor: pointer; transition: border-color .15s, background .15s; }
        .user-chip:hover, .user-chip[aria-expanded="true"] { border-color: var(--ink); background: var(--soft); }
        .user-avatar { width: 24px; height: 24px; border-radius: 50%; background: var(--ink); color: var(--white); display: grid; place-items: center; font-size: 11px; font-weight: 900; flex-shrink: 0; }
        .user-chip-name { max-width: 90px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .user-chevron { color: var(--mid); transition: transform .2s; flex-shrink: 0; }
        .user-chip[aria-expanded="true"] .user-chevron { transform: rotate(180deg); }

        /* User dropdown */
        .user-dropdown { display: none; position: absolute; top: calc(100% + 8px); right: 0; min-width: 200px; background: var(--white); border: 1px solid var(--line); border-radius: 14px; box-shadow: 0 8px 24px rgba(0,0,0,.10); overflow: hidden; z-index: 200; }
        .user-dropdown.open { display: block; }
        .user-dropdown-head { padding: 12px 14px 10px; border-bottom: 1px solid var(--line); }
        .ud-name  { font-size: 13px; font-weight: 800; }
        .ud-phone { font-size: 11px; color: var(--mid); font-weight: 500; margin-top: 1px; }
        .ud-item  { display: flex; align-items: center; gap: 9px; padding: 11px 14px; font-size: 13px; font-weight: 700; color: var(--ink); width: 100%; text-align: left; background: none; border: none; font-family: var(--font-body); cursor: pointer; text-decoration: none; transition: background .12s; }
        .ud-item:hover { background: var(--soft); }
        .ud-item svg { color: var(--mid); flex-shrink: 0; }
        .ud-sep { height: 1px; background: var(--line); }
        .ud-keluar { color: #b91c1c; }
        .ud-keluar svg { color: #b91c1c; }
        .ud-keluar:hover { background: #fef2f2; }

        /* ── Footer ───────────────────────────────────────────────────── */
        /* Mobile simple footer */
        .foot { border-top: 1px solid var(--line); margin-top: auto; padding: 20px 0 calc(18px + var(--safe)); background: var(--white); }
        .foot-brand { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
        .foot-brand img { width: 22px; height: 22px; object-fit: contain; }
        .foot-name { font-size: 11px; font-weight: 900; letter-spacing: .16em; text-transform: uppercase; }
        .foot-tagline { max-width: 260px; font-size: 11px; color: var(--mid); font-weight: 600; line-height: 1.5; margin-bottom: 14px; }
        .foot-links { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; margin-bottom: 16px; }
        .foot-links a { min-height: 32px; padding: 0 11px; border-radius: var(--radius-pill); background: var(--soft); border: 1px solid var(--line); display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 900; color: var(--ink); letter-spacing: .04em; text-transform: uppercase; }
        .foot-bottom { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-top: 12px; border-top: 1px solid var(--line); }
        .foot-bottom span { font-size: 10px; color: var(--mid); font-weight: 600; }
        .foot-bottom a { font-size: 10px; color: #aaa; font-weight: 700; }
        /* Desktop footer */
        .site-footer { background: var(--ink); color: rgba(255,255,255,.7); display: none; margin-top: auto; }
        .site-footer-inner { max-width: 1680px; margin: 0 auto; padding: 40px 32px 28px; }
        .sf-top { display: grid; grid-template-columns: 1fr auto; gap: 40px; align-items: start; margin-bottom: 36px; }
        .sf-brand { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .sf-brand img { width: 28px; height: 28px; object-fit: contain; filter: invert(1); }
        .sf-brand-name { font-size: 13px; font-weight: 900; letter-spacing: .15em; text-transform: uppercase; color: #fff; }
        .sf-tagline { font-size: 12px; color: rgba(255,255,255,.45); font-weight: 500; line-height: 1.6; max-width: 280px; }
        .sf-nav { display: flex; gap: 32px; }
        .sf-col h4 { font-size: 10px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: rgba(255,255,255,.4); margin-bottom: 12px; }
        .sf-col a { display: block; font-size: 12px; font-weight: 600; color: rgba(255,255,255,.65); margin-bottom: 8px; text-decoration: none; transition: color .15s; }
        .sf-col a:hover { color: #fff; }
        .sf-bottom { border-top: 1px solid rgba(255,255,255,.08); padding-top: 20px; display: flex; align-items: center; justify-content: space-between; }
        .sf-copy, .sf-love { font-size: 11px; color: rgba(255,255,255,.3); }

        /* ── Responsive ───────────────────────────────────────────────── */
        @@media (max-width: 719px) {
            .nav-inner { padding: 0 14px; }
            .brand { gap: 9px; font-size: 12.5px; letter-spacing: .17em; }
            .brand img { width: 31px; height: 31px; }
            .nav-r { gap: 8px; }
            .user-chip { padding-right: 8px; }
            .user-chip-name { display: none; }
        }
        @@media (min-width: 720px) {
            .nav-inner { padding: 0 32px; }
            .nav-links { display: flex; }
            .foot { display: none; }
            .site-footer { display: block; }
        }
    </style>
    @stack('styles')
</head>
<body class="@yield('body-class')">

@include('storefront._nav', ['navActive' => $navActive ?? ''])

<main>
    @yield('content')
</main>

@section('footer')
    @include('storefront.partials._footer')
@show

@include('storefront._tracker')
@include('storefront._mobile_zoom_lock')

<script>
/* ── User dropdown (shared across all pages) ─────────────────────────── */
(function () {
    var btn  = document.getElementById('userChipBtn');
    var drop = document.getElementById('userDropdown');
    if (!btn || !drop) return;
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = drop.classList.toggle('open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function () {
        drop.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
    });
})();
</script>
@stack('scripts')
</body>
</html>
