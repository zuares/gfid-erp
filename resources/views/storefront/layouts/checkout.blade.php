<!doctype html>
<html lang="id" class="{{ config('storefront.theme', '') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Greatfit')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Barlow+Condensed:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ── Reset ─────────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Theme Tokens ─────────────────────────────────────────────── */
        :root {
            --ink:          #0a0a0a;
            --mid:          #888;
            --line:         #e8e8e8;
            --soft:         #f4f4f4;
            --white:        #fff;
            --accent:       #E8FF00;
            --accent-dark:  #b8c800;
            --font-body:    'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --font-display: 'Barlow Condensed', sans-serif;
            --safe:         env(safe-area-inset-bottom, 0px);
            --radius-card:  18px;
            --radius-pill:  999px;
        }
        .theme-dark {
            --ink: #f0f0f0; --mid: #999; --line: #2a2a2a; --soft: #141414; --white: #0d0d0d;
        }
        .theme-warm {
            --ink: #1a0f05; --mid: #8a7060; --line: #e8dfd6; --soft: #faf6f2; --white: #fffefb;
            --accent: #ff6b35; --accent-dark: #e05520;
        }

        /* ── Base ──────────────────────────────────────────────────────── */
        html { min-height: 100%; }
        body {
            font-family: var(--font-body);
            color: var(--ink);
            background: var(--soft);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main { flex: 1 0 auto; display: flex; flex-direction: column; }
        main > .wrap { flex: 1 0 auto; display: flex; flex-direction: column; }
        a   { color: inherit; text-decoration: none; }
        img { display: block; max-width: 100%; }

        /* ── Checkout Nav ─────────────────────────────────────────────── */
        .ck-nav { position: sticky; top: 0; z-index: 100; background: rgba(255,255,255,.96); backdrop-filter: blur(12px); border-bottom: 1px solid var(--line); }
        .ck-nav-inner { height: 52px; display: flex; align-items: center; justify-content: space-between; max-width: 1680px; margin: 0 auto; padding: 0 20px; }
        .brand { display: inline-flex; align-items: center; gap: 10px; min-height: 40px; font-weight: 900; font-size: 13px; letter-spacing: .18em; line-height: 1; text-transform: uppercase; color: var(--ink); }
        .brand img { width: 32px; height: 32px; object-fit: contain; flex: 0 0 auto; }
        .brand span { transform: translateY(.5px); }
        @@media (max-width: 719px) {
            .brand { gap: 9px; font-size: 12.5px; letter-spacing: .17em; }
            .brand img { width: 31px; height: 31px; }
        }
        @@media (min-width: 720px) { .ck-nav-inner { padding: 0 32px; } }
    </style>
    @stack('styles')
</head>
<body class="@yield('body-class')">

<header class="ck-nav">
    <div class="ck-nav-inner">
        <a href="{{ route('storefront.home') }}" class="brand">
            <img src="{{ asset('images/logo-mark.svg') }}" alt="Greatfit">
            <span>GREATFIT</span>
        </a>
        @yield('nav-right')
    </div>
</header>

<main>
    @yield('content')
</main>

@stack('scripts')
@include('storefront._tracker')
@include('storefront._mobile_zoom_lock')
</body>
</html>
