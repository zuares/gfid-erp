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
        /* ── Reset & Theme (same tokens as app.blade.php) ─────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
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
        /* ── Base ─────────────────────────────────────────────────────── */
        html { min-height: 100%; }
        body {
            font-family: var(--font-body);
            color: var(--ink);
            background: var(--soft);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 24px 16px calc(24px + var(--safe));
        }
        a   { color: inherit; text-decoration: none; }
        img { display: block; max-width: 100%; }
    </style>
    @stack('styles')
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>
