<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>{{ $product['name'] }} — Greatfit</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --ink: #0a0a0a;
            --mid: #777;
            --line: #ebebeb;
            --soft: #f5f5f5;
            --bg:  #f2f2f2;
            --white: #fff;
            --red: #e53935;
            --safe: env(safe-area-inset-bottom, 0px);
        }
        html { scroll-behavior: smooth; min-height: 100%; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            color: var(--ink); background: var(--bg);
            -webkit-font-smoothing: antialiased;
            padding-bottom: calc(72px + var(--safe));
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        a { color: inherit; text-decoration: none; }
        img { display: block; max-width: 100%; }

        /* ─── NAV ─── */
        .nav { position: sticky; top: 0; z-index: 200; background: rgba(255,255,255,.96); backdrop-filter: blur(14px); border-bottom: 1px solid var(--line); }
        .nav-inner { height: 56px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; max-width: 1680px; margin: 0 auto; }
        .brand { display: flex; align-items: center; gap: 8px; font-weight: 900; font-size: 12px; letter-spacing: .16em; text-transform: uppercase; }
        .brand img { width: 28px; height: 28px; object-fit: contain; }
        .nav-links { display: none; gap: 18px; font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--mid); }
        .nav-links a:hover { color: var(--ink); }
        .nav-r { display: flex; align-items: center; gap: 4px; }
        .icon-btn { width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; transition: background .15s; border: none; background: transparent; cursor: pointer; color: var(--ink); flex-shrink: 0; }
        .icon-btn:hover { background: var(--soft); }
        .cart-wrap { position: relative; }
        .cart-badge { position: absolute; top: -1px; right: -1px; width: 15px; height: 15px; border-radius: 50%; background: var(--ink); color: var(--white); font-size: 8px; font-weight: 800; display: grid; place-items: center; border: 2px solid var(--white); }
        .cart-wrap.cart-pop { animation: cartPulse .7s ease both; }
        .cart-toast { position: fixed; top: 68px; right: 16px; z-index: 500; display: flex; align-items: center; gap: 10px; max-width: min(320px, calc(100vw - 32px)); padding: 11px 13px; border-radius: 14px; background: var(--ink); color: var(--white); box-shadow: 0 16px 40px rgba(0,0,0,.18); font-size: 12px; font-weight: 800; transform: translateY(-10px); opacity: 0; pointer-events: none; animation: toastIn 2.4s ease forwards; }
        .cart-toast-icon { width: 26px; height: 26px; border-radius: 50%; background: rgba(255,255,255,.14); display: grid; place-items: center; flex-shrink: 0; }
        .cart-fly { position: fixed; top: 50%; left: 50%; z-index: 499; width: 18px; height: 18px; border-radius: 50%; background: var(--ink); pointer-events: none; transform: translate(-50%, -50%); opacity: 0; animation: flyToCart .75s cubic-bezier(.2,.7,.2,1) forwards; }
        @keyframes cartPulse { 0%, 100% { transform: scale(1); } 35% { transform: scale(1.14); } 65% { transform: scale(.96); } }
        @keyframes toastIn { 0% { opacity: 0; transform: translateY(-10px); } 12%, 78% { opacity: 1; transform: translateY(0); } 100% { opacity: 0; transform: translateY(-8px); } }
        @keyframes flyToCart { 0% { opacity: 0; transform: translate(-50%, -50%) scale(.7); } 18% { opacity: 1; } 100% { opacity: 0; transform: translate(calc(50vw - 42px), calc(-50vh + 23px)) scale(.25); } }

        /* ─── BREADCRUMB ─── */
        .breadcrumb-bar { background: var(--white); border-bottom: 1px solid var(--line); padding: 9px 16px; }
        .breadcrumb { font-size: 11px; color: var(--mid); display: flex; align-items: center; gap: 5px; font-weight: 500; max-width: 1680px; margin: 0 auto; }
        .breadcrumb a:hover { color: var(--ink); }
        .mobile-back { font-size: 12px; color: var(--mid); font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }
        .mobile-back:hover { color: var(--ink); }

        /* ─── SECTION CARDS ─── */
        .section { background: var(--white); margin-bottom: 8px; padding: 16px; }

        /* ─── IMAGE ─── */
        .img-section { background: var(--white); margin-bottom: 8px; position: relative; overflow: hidden; }
        .img-section img { width: 100%; aspect-ratio: 1; object-fit: cover; display: block; }
        .img-badge { position: absolute; top: 14px; left: 14px; z-index: 2; background: var(--ink); color: var(--white); font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 999px; letter-spacing: .04em; }

        /* ─── PRICE CARD ─── */
        .price-section { padding: 14px 16px 16px; }
        .price-row { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 8px; }
        .product-price { font-size: 26px; font-weight: 900; letter-spacing: -.03em; line-height: 1; }
        .product-sold { font-size: 11px; color: var(--mid); font-weight: 500; }
        .product-name { font-size: 15px; font-weight: 700; line-height: 1.4; color: #222; }

        /* ─── VARIANT TAPPABLE ROW (mobile — opens modal) ─── */
        .variant-tap { display: flex; align-items: center; justify-content: space-between; padding: 13px 16px; cursor: pointer; }
        .vt-left { display: flex; flex-direction: column; gap: 2px; }
        .vt-label { font-size: 10px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--mid); }
        .vt-value { font-size: 13px; font-weight: 700; color: var(--ink); }
        .vt-hint { font-size: 13px; font-weight: 500; color: var(--mid); font-style: italic; }
        .vt-arrow { color: var(--mid); flex-shrink: 0; }

        /* ─── VARIANT SECTION (desktop inline) ─── */
        .variant-section { padding: 14px 16px 20px; }
        .picker-head { font-size: 11px; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--mid); margin-bottom: 10px; }
        .variant-summary { display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; background: var(--soft); border-radius: 10px; margin-bottom: 18px; }
        .vs-label { font-size: 11px; color: var(--mid); font-weight: 600; flex-shrink: 0; margin-right: 8px; }
        .vs-value { font-size: 12px; font-weight: 800; color: var(--ink); text-align: right; }
        .vs-hint { font-size: 11px; color: var(--mid); font-weight: 500; font-style: italic; }

        /* Color swatches */
        .colors { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
        .color-btn { width: 32px; height: 32px; border-radius: 50%; border: none; cursor: pointer; flex-shrink: 0; transition: transform .12s, box-shadow .12s; }
        .color-btn.light { box-shadow: inset 0 0 0 1.5px #ccc; }
        .color-btn:hover { transform: scale(1.1); }
        .color-btn.active { box-shadow: 0 0 0 2.5px var(--white), 0 0 0 4.5px var(--ink); }
        .color-btn.color-option { width: auto; height: 38px; min-width: 0; border-radius: 999px; padding: 0 12px 0 8px; display: inline-flex; align-items: center; gap: 8px; background: var(--white); border: 1.5px solid var(--line); box-shadow: none; font-family: inherit; font-size: 12px; font-weight: 800; color: var(--ink); }
        .color-btn.color-option:hover { transform: none; border-color: #bbb; }
        .color-btn.color-option.active { background: var(--ink); border-color: var(--ink); color: var(--white); box-shadow: none; }
        .color-dot { width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0; box-shadow: inset 0 0 0 1px rgba(0,0,0,.12); }
        .color-dot.light { box-shadow: inset 0 0 0 1.5px #bbb; }

        /* Size chips */
        .sizes { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 20px; }
        .size-btn { min-width: 46px; height: 36px; padding: 0 10px; border-radius: 8px; border: 1.5px solid var(--line); background: var(--white); font-size: 12px; font-weight: 800; cursor: pointer; display: grid; place-items: center; transition: all .12s; font-family: inherit; color: var(--ink); }
        .size-btn:hover { border-color: #bbb; }
        .size-btn.active { border-color: var(--ink); background: var(--ink); color: var(--white); }

        /* Qty */
        .qty-row { display: flex; align-items: center; justify-content: space-between; }
        .qty-label { font-size: 11px; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--mid); }
        .qty-ctrl { display: inline-flex; align-items: center; border: 1.5px solid var(--line); border-radius: 8px; overflow: hidden; }
        .qty-btn { width: 34px; height: 34px; border: none; background: transparent; cursor: pointer; font-size: 16px; display: grid; place-items: center; color: var(--ink); font-family: inherit; transition: background .1s; user-select: none; }
        .qty-btn:hover { background: var(--soft); }
        .qty-num { min-width: 36px; text-align: center; font-size: 13px; font-weight: 800; border-left: 1.5px solid var(--line); border-right: 1.5px solid var(--line); line-height: 34px; }
        .pick-hint { font-size: 11px; color: var(--red); font-weight: 600; margin-top: 12px; display: none; }

        /* Desktop action buttons */
        .btn-row { display: flex; gap: 8px; margin-top: 20px; }
        .btn-atc { flex: 1; height: 48px; border-radius: 12px; background: #f0f0f0; color: var(--ink); border: 1.5px solid var(--line); font-size: 13px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px; font-family: inherit; transition: background .12s; }
        .btn-atc:hover { background: var(--line); }
        .btn-now { flex: 1.2; height: 48px; border-radius: 12px; background: var(--ink); color: var(--white); border: none; font-size: 13px; font-weight: 800; cursor: pointer; font-family: inherit; transition: opacity .12s; letter-spacing: .01em; }
        .btn-now:hover { opacity: .85; }

        /* ─── DESCRIPTION ─── */
        .desc-section { padding: 14px 16px; }
        .section-title { font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--mid); margin-bottom: 10px; }
        .desc-text { font-size: 13px; color: #555; line-height: 1.75; font-weight: 500; }

        /* ─── RELATED (mobile card) ─── */
        .related-section { padding: 14px 16px 16px; }
        .related-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-top: 12px; }
        .rc { border-radius: 10px; overflow: hidden; background: var(--soft); border: 1px solid var(--line); display: block; transition: opacity .15s; }
        .rc:hover { opacity: .8; }
        .rc img { width: 100%; aspect-ratio: 1; object-fit: cover; }
        .rc-body { padding: 9px 10px; }
        .rc-name { font-size: 12px; font-weight: 700; line-height: 1.3; color: #333; }
        .rc-price { font-size: 14px; font-weight: 900; margin-top: 3px; }

        /* ─── FULL-WIDTH RELATED (desktop) ─── */
        .related-full { background: var(--white); margin-top: 8px; padding: 20px 16px 24px; display: none; }
        .related-full .section-title { margin-bottom: 16px; }
        .related-full-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }

        /* ─── FOOTER ─── */
        /* mobile minimal footer inside card */
        .foot-section { padding: 18px 16px 12px; }
        .foot-brand { display: flex; align-items: center; gap: 9px; margin-bottom: 8px; }
        .foot-brand img { width: 26px; height: 26px; object-fit: contain; }
        .foot-name { font-size: 12px; font-weight: 900; letter-spacing: .15em; text-transform: uppercase; }
        .foot-tagline { font-size: 12px; color: var(--mid); font-weight: 600; line-height: 1.55; margin-bottom: 16px; }
        .foot-links { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 16px; }
        .foot-links a { height: 38px; border-radius: 12px; background: var(--soft); border: 1px solid var(--line); display: grid; place-items: center; font-size: 11px; font-weight: 800; color: var(--ink); }
        .foot-bottom { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .foot-bottom span, .foot-bottom a { font-size: 11px; color: var(--mid); font-weight: 600; }

        /* proper site footer */
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
        .sf-copy { font-size: 11px; color: rgba(255,255,255,.3); }
        .sf-love { font-size: 11px; color: rgba(255,255,255,.3); }

        /* ─── STICKY BAR (mobile) ─── */
        .bar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 150; background: var(--white); border-top: 1px solid var(--line); padding: 10px 16px calc(10px + var(--safe)); }
        .bar-inner { display: flex; align-items: center; gap: 8px; max-width: 600px; margin: 0 auto; }
        .bar-cart { flex: 1; height: 46px; border-radius: 12px; background: var(--soft); color: var(--ink); border: 1.5px solid var(--line); font-size: 13px; font-weight: 800; cursor: pointer; font-family: inherit; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .bar-now { flex: 1.4; height: 46px; border-radius: 12px; background: var(--ink); color: var(--white); border: none; font-size: 13px; font-weight: 800; cursor: pointer; font-family: inherit; }

        /* ─── MODAL / BOTTOM SHEET ─── */
        .overlay { position: fixed; inset: 0; z-index: 300; background: rgba(0,0,0,.5); opacity: 0; pointer-events: none; transition: opacity .25s; }
        .overlay.open { opacity: 1; pointer-events: all; }
        .sheet { position: fixed; left: 0; right: 0; bottom: 0; z-index: 301; background: var(--white); border-radius: 20px 20px 0 0; transform: translateY(100%); transition: transform .3s cubic-bezier(.32,0,.15,1); max-height: 92vh; display: flex; flex-direction: column; }
        .sheet.open { transform: translateY(0); }
        .sheet-handle { width: 36px; height: 4px; background: var(--line); border-radius: 2px; margin: 12px auto 0; flex-shrink: 0; }
        .sheet-head { display: flex; align-items: center; gap: 12px; padding: 16px 16px 14px; border-bottom: 1px solid var(--line); flex-shrink: 0; }
        .sheet-img { width: 56px; height: 56px; border-radius: 10px; overflow: hidden; flex-shrink: 0; background: var(--soft); }
        .sheet-img img { width: 100%; height: 100%; object-fit: cover; }
        .sheet-meta { flex: 1; min-width: 0; }
        .sheet-price { font-size: 20px; font-weight: 900; letter-spacing: -.03em; line-height: 1; }
        .sheet-name { font-size: 12px; color: var(--mid); font-weight: 500; margin-top: 3px; }
        .sheet-close { width: 32px; height: 32px; border-radius: 50%; border: 1.5px solid var(--line); background: none; cursor: pointer; display: grid; place-items: center; color: var(--mid); flex-shrink: 0; }
        .sheet-body { overflow-y: auto; padding: 16px; flex: 1; }
        .sheet-footer { padding: 12px 16px calc(12px + var(--safe)); border-top: 1px solid var(--line); flex-shrink: 0; }
        .sheet-action { width: 100%; height: 50px; border-radius: 14px; border: none; font-size: 14px; font-weight: 800; cursor: pointer; font-family: inherit; transition: opacity .12s; }
        .sheet-action:hover { opacity: .85; }
        .sheet-action.mode-now { background: var(--ink); color: var(--white); }
        .sheet-action.mode-cart { background: var(--ink); color: var(--white); }
        .sheet-hint { font-size: 11px; color: var(--red); font-weight: 600; margin-top: 8px; display: none; text-align: center; }

        /* ─── DESKTOP ─── */
        @media (min-width: 720px) {
            body { padding-bottom: 0; }
            .nav-links { display: flex; }

            /* Breadcrumb */
            .desktop-breadcrumb { display: flex; align-items: center; gap: 5px; font-size: 11px; color: var(--mid); font-weight: 500; padding: 16px 32px 0; }
            .desktop-breadcrumb a:hover { color: var(--ink); }

            /* Two-col: image 40%, info 60% — fills viewport, capped at 1680px */
            .pd-grid {
                display: grid;
                grid-template-columns: minmax(300px, 40%) minmax(0, 1fr);
                gap: 28px;
                max-width: 1680px;
                margin: 0 auto;
                padding: 16px 32px 56px;
                align-items: start;
                width: 100%;
            }

            /* Sticky image */
            .pd-left { position: sticky; top: 68px; }
            .img-section { border-radius: 18px; margin-bottom: 0; overflow: hidden; }
            .img-section img { aspect-ratio: 4/5; object-fit: cover; }

            /* Right column */
            .pd-right { display: flex; flex-direction: column; gap: 10px; }
            .price-section  { border-radius: 16px; padding: 22px 24px 22px; margin-bottom: 0; }
            .product-price  { font-size: 28px; }
            .variant-section{ border-radius: 16px; padding: 22px 24px 26px; margin-bottom: 0; }
            .desc-section   { border-radius: 16px; padding: 20px 24px; margin-bottom: 0; }
            .related-section{ border-radius: 16px; padding: 20px 24px 24px; margin-bottom: 0; }
            .related-grid   { grid-template-columns: repeat(3, 1fr); }
            .foot-section   { padding: 6px 2px 0; }

            /* Desktop: show inline variant, hide mobile tap row */
            .variant-tap { display: none; }
            .variant-section { display: block; }

            /* Desktop: hide mobile sticky bar and modal */
            .bar { display: none; }
            .overlay, .sheet { display: none; }
            .breadcrumb-bar { display: none; }

            /* Desktop: hide right-col related + minimal footer; show full-width versions */
            .related-section { display: none; }
            .foot-section { display: none; }
            .related-full { display: block; padding: 24px 32px 28px; }
            .related-full-grid { grid-template-columns: repeat(3, 1fr); gap: 16px; }
            .rc-name { font-size: 13px; }
            .rc-price { font-size: 15px; }
            .site-footer { display: block; }
        }
        @media (min-width: 720px) and (max-width: 1080px) {
            .nav-inner { padding: 0 36px; }
            .desktop-breadcrumb { padding: 18px 36px 0; }
            .pd-grid {
                grid-template-columns: minmax(280px, 42%) minmax(0, 1fr);
                gap: 22px;
                padding: 18px 36px 48px;
            }
            .price-section { padding: 20px 22px; }
            .variant-section { padding: 20px 22px 24px; }
            .desc-section { padding: 18px 22px; }
            .related-full { padding: 24px 36px 30px; }
            .site-footer-inner { padding-left: 36px; padding-right: 36px; }
        }
        @media (max-width: 719px) {
            .desktop-breadcrumb { display: none; }
            .pd-grid { display: contents; }
            .pd-left, .pd-right { display: contents; }
            .section { border-radius: 0; }
            .foot-section { display: none; }

            /* Mobile: hide inline variant section, show tap row */
            .variant-section { display: none; }
            .variant-tap { display: flex; }
        }
    </style>
</head>
<body>

{{-- NAV --}}
<header class="nav">
    <div class="nav-inner">
        <a href="{{ route('storefront.home') }}" class="brand">
            <img src="{{ asset('images/logo-mark.svg') }}" alt="Greatfit">
            <span>Greatfit</span>
        </a>
        <nav class="nav-links">
            <a href="{{ route('storefront.products') }}">Produk</a>
            <a href="{{ route('storefront.home') }}#beli">Beli</a>
        </nav>
        <div class="nav-r">
            @php $cartCount = array_sum(array_column(session('cart', []), 'qty')); @endphp
            <button class="icon-btn" title="Cari" onclick="return false;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            </button>
            <a href="{{ route('storefront.cart') }}" class="icon-btn cart-wrap @if(session('cart_added')) cart-pop @endif" title="Keranjang">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                @if($cartCount > 0)<span class="cart-badge">{{ $cartCount }}</span>@endif
            </a>
        </div>
    </div>
</header>

@if(session('cart_added'))
<div class="cart-fly"></div>
<div class="cart-toast">
    <span class="cart-toast-icon">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
    </span>
    <span>Produk masuk keranjang</span>
</div>
@endif

{{-- BREADCRUMB mobile --}}
<div class="breadcrumb-bar">
    <div class="breadcrumb">
        <a href="{{ route('storefront.products') }}" class="mobile-back">
            <span>←</span>
            <span>Semua Produk</span>
        </a>
    </div>
</div>

{{-- BREADCRUMB desktop --}}
<div class="desktop-breadcrumb">
    <a href="{{ route('storefront.home') }}">Home</a>
    <span>/</span>
    <a href="{{ route('storefront.products') }}">Produk</a>
    <span>/</span>
    <span>{{ $product['name'] }}</span>
</div>

<div class="pd-grid">

    {{-- LEFT: IMAGE --}}
    <div class="pd-left">
        <div class="img-section">
            <span class="img-badge">{{ $product['label'] }}</span>
            <img src="{{ storefront_img($product['img']) }}" alt="{{ $product['name'] }}">
        </div>
    </div>

    {{-- RIGHT: INFO CARDS --}}
    <div class="pd-right">

        {{-- PRICE CARD --}}
        <div class="section price-section">
            <div class="price-row">
                <div class="product-price">Rp{{ number_format($product['price'], 0, ',', '.') }}</div>
                <div class="product-sold">{{ $product['sold'] }} terjual</div>
            </div>
            <div class="product-name">{{ $product['name'] }}</div>
        </div>

        {{-- MOBILE: tappable row that opens modal --}}
        <div class="section variant-tap" onclick="openModal('now')">
            <div class="vt-left">
                <span class="vt-label">Pilihan</span>
                <span id="tap-value" class="vt-hint">Pilih warna &amp; ukuran</span>
            </div>
            <span class="vt-arrow">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
            </span>
        </div>

        {{-- DESKTOP: inline variant section --}}
        <div class="section variant-section">
            <div class="variant-summary">
                <span class="vs-label">Dipilih</span>
                <span id="vs-value-desk" class="vs-hint">Pilih warna &amp; ukuran</span>
            </div>

            @if(!empty($product['colors']))
            <div class="picker-head">Warna</div>
            <div class="colors" id="color-btns-desk">
                @foreach ($product['colors'] as $c)
                <button type="button" class="color-btn {{ ($c['light'] ?? false) ? 'light' : '' }}"
                    style="background:{{ $c['hex'] }}" title="{{ $c['name'] }}"
                    onclick="deskColor(this, '{{ $c['name'] }}')"></button>
                @endforeach
            </div>
            @endif

            <div class="picker-head">Ukuran</div>
            <div class="sizes" id="size-btns-desk">
                @foreach ($product['sizes'] as $s)
                <button type="button" class="size-btn" onclick="deskSize(this, '{{ $s }}')">{{ $s }}</button>
                @endforeach
            </div>

            <div class="qty-row">
                <span class="qty-label">Jumlah</span>
                <div class="qty-ctrl">
                    <button class="qty-btn" type="button" onclick="deskQty(-1)">−</button>
                    <div class="qty-num" id="desk-qty-display">1</div>
                    <button class="qty-btn" type="button" onclick="deskQty(1)">+</button>
                </div>
            </div>

            <div class="pick-hint" id="desk-hint">Pilih warna dan ukuran terlebih dahulu</div>
            @if(session('cart_error'))
            <div class="pick-hint" style="display:block">{{ session('cart_error') }}</div>
            @endif

            <form action="{{ route('storefront.cart.add') }}" method="POST" id="desk-cart-form" style="display:none">
                @csrf
                <input type="hidden" name="slug"  value="{{ $slug }}">
                <input type="hidden" name="color" id="desk-color" value="">
                <input type="hidden" name="size"  id="desk-size"  value="">
                <input type="hidden" name="qty"   id="desk-qty"   value="1">
                <input type="hidden" name="mode"  id="desk-mode"  value="cart">
            </form>

            <div class="btn-row">
                <button type="button" class="btn-atc" onclick="deskSubmit('cart')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                    Tambah ke Keranjang
                </button>
                <button type="button" class="btn-now" onclick="deskSubmit('now')">Beli Sekarang</button>
            </div>
        </div>

        {{-- DESCRIPTION --}}
        <div class="section desc-section">
            <div class="section-title">Deskripsi Produk</div>
            <div class="desc-text">{{ $product['desc'] }}</div>
        </div>

        {{-- RELATED (mobile only — desktop version is full-width below) --}}
        @php $related = collect($products)->where('slug', '!=', $slug)->take(3)->values(); @endphp
        @if($related->count())
        <div class="section related-section">
            <div class="section-title">Produk Lainnya</div>
            <div class="related-grid">
                @foreach ($related as $r)
                <a href="{{ route('storefront.product_detail', $r['slug']) }}" class="rc">
                    <img src="{{ storefront_img($r['img']) }}" alt="{{ $r['name'] }}" loading="lazy">
                    <div class="rc-body">
                        <div class="rc-name">{{ $r['name'] }}</div>
                        <div class="rc-price">Rp{{ number_format($r['price'], 0, ',', '.') }}</div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Mobile minimal footer --}}
        <div class="section foot-section">
            <div class="foot-brand">
                <img src="{{ asset('images/logo-mark.svg') }}" alt="Greatfit">
                <span class="foot-name">Greatfit</span>
            </div>
            <div class="foot-tagline">Pakaian olahraga nyaman untuk aktivitas harian.</div>
            <nav class="foot-links" aria-label="Footer mobile">
                <a href="{{ route('storefront.products') }}">Produk</a>
                <a href="{{ route('storefront.cart') }}">Keranjang</a>
                <a href="{{ route('storefront.home') }}#beli">Cara Beli</a>
            </nav>
            <div class="foot-bottom">
                <span>© {{ date('Y') }} Greatfit</span>
                <a href="{{ route('login', [], false) }}">Admin</a>
            </div>
        </div>

    </div>
</div>

{{-- DESKTOP: Full-width related products --}}
@if($related->count())
<section class="related-full">
    <div class="section-title">Produk Lainnya</div>
    <div class="related-full-grid">
        @foreach ($related as $r)
        <a href="{{ route('storefront.product_detail', $r['slug']) }}" class="rc">
            <img src="{{ storefront_img($r['img']) }}" alt="{{ $r['name'] }}" loading="lazy">
            <div class="rc-body">
                <div class="rc-name">{{ $r['name'] }}</div>
                <div class="rc-price">Rp{{ number_format($r['price'], 0, ',', '.') }}</div>
            </div>
        </a>
        @endforeach
    </div>
</section>
@endif

{{-- DESKTOP: Site footer --}}
<footer class="site-footer">
    <div class="site-footer-inner">
        <div class="sf-top">
            <div>
                <div class="sf-brand">
                    <img src="{{ asset('images/logo-mark.svg') }}" alt="Greatfit">
                    <span class="sf-brand-name">Greatfit</span>
                </div>
                <div class="sf-tagline">Pakaian olahraga nyaman<br>untuk aktivitas harian.</div>
            </div>
            <nav class="sf-nav">
                <div class="sf-col">
                    <h4>Koleksi</h4>
                    <a href="{{ route('storefront.products') }}">Semua Produk</a>
                    @if(count($products ?? []) > 6)
                        @foreach ($products as $p)
                        <a href="{{ route('storefront.product_detail', $p['slug']) }}">{{ $p['name'] }}</a>
                        @endforeach
                    @else
                        @foreach (collect($products ?? [])->take(3) as $p)
                        <a href="{{ route('storefront.product_detail', $p['slug']) }}">{{ $p['name'] }}</a>
                        @endforeach
                    @endif
                </div>
                <div class="sf-col">
                    <h4>Toko</h4>
                    <a href="{{ route('storefront.home') }}">Home</a>
                    <a href="{{ route('storefront.cart') }}">Keranjang</a>
                    <a href="{{ route('storefront.home') }}#beli">Cara Beli</a>
                </div>
                <div class="sf-col">
                    <h4>Lainnya</h4>
                    <a href="{{ route('login', [], false) }}">Admin Login</a>
                </div>
            </nav>
        </div>
        <div class="sf-bottom">
            <span class="sf-copy">© {{ date('Y') }} Greatfit. All rights reserved.</span>
            <span class="sf-love">Made with care in Indonesia</span>
        </div>
    </div>
</footer>

{{-- MOBILE STICKY BAR --}}
<nav class="bar">
    <div class="bar-inner">
        <button class="bar-cart" onclick="openModal('cart')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            Keranjang
        </button>
        <button class="bar-now" onclick="openModal('now')">Beli Sekarang</button>
    </div>
</nav>

{{-- MODAL BOTTOM SHEET --}}
<div class="overlay" id="overlay" onclick="closeModal()"></div>
<div class="sheet" id="sheet">
    <div class="sheet-handle"></div>

    <div class="sheet-head">
        <div class="sheet-img">
            <img src="{{ storefront_img($product['img']) }}" alt="{{ $product['name'] }}">
        </div>
        <div class="sheet-meta">
            <div class="sheet-price">Rp{{ number_format($product['price'], 0, ',', '.') }}</div>
            <div class="sheet-name">{{ $product['name'] }}</div>
        </div>
        <button class="sheet-close" onclick="closeModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    </div>

    <div class="sheet-body">

        @if(!empty($product['colors']))
        <div class="picker-head" style="margin-top:4px">Warna</div>
        <div class="colors" id="color-btns-modal" style="margin-bottom:20px">
            @foreach ($product['colors'] as $c)
            <button type="button" class="color-btn color-option" title="{{ $c['name'] }}"
                onclick="modalColor(this, '{{ $c['name'] }}')">
                <span class="color-dot {{ ($c['light'] ?? false) ? 'light' : '' }}" style="background:{{ $c['hex'] }}"></span>
                <span>{{ $c['name'] }}</span>
            </button>
            @endforeach
        </div>
        @endif

        <div class="picker-head">Ukuran</div>
        <div class="sizes" id="size-btns-modal" style="margin-bottom:20px">
            @foreach ($product['sizes'] as $s)
            <button type="button" class="size-btn" onclick="modalSize(this, '{{ $s }}')">{{ $s }}</button>
            @endforeach
        </div>

        <div class="qty-row">
            <span class="qty-label">Jumlah</span>
            <div class="qty-ctrl">
                <button class="qty-btn" type="button" onclick="modalQty(-1)">−</button>
                <div class="qty-num" id="modal-qty-display">1</div>
                <button class="qty-btn" type="button" onclick="modalQty(1)">+</button>
            </div>
        </div>

    </div>

    <div class="sheet-footer">
        <form action="{{ route('storefront.cart.add') }}" method="POST" id="modal-form">
            @csrf
            <input type="hidden" name="slug"  value="{{ $slug }}">
            <input type="hidden" name="color" id="modal-color" value="">
            <input type="hidden" name="size"  id="modal-size"  value="">
            <input type="hidden" name="qty"   id="modal-qty"   value="1">
            <input type="hidden" name="mode"  id="modal-mode"  value="cart">
        </form>
        <button type="button" class="sheet-action mode-now" id="sheet-action-btn" onclick="modalSubmit()">
            Beli Sekarang
        </button>
        <div class="sheet-hint" id="sheet-hint">Pilih warna dan ukuran terlebih dahulu</div>
    </div>
</div>

<script>
/* ─── SHARED STATE ─── */
var mColor = '', mSize = '', mQty = 1;
var dColor = '', dSize = '', dQty = 1;
var currentMode = 'cart';

/* ─── MODAL ─── */
function openModal(mode) {
    currentMode = mode;
    var btn = document.getElementById('sheet-action-btn');
    if (mode === 'now') {
        btn.textContent = 'Beli Sekarang';
    } else {
        btn.innerHTML = '<svg style="vertical-align:middle;margin-right:6px" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>Tambah ke Keranjang';
    }
    document.getElementById('modal-mode').value = mode;
    document.getElementById('overlay').classList.add('open');
    document.getElementById('sheet').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('overlay').classList.remove('open');
    document.getElementById('sheet').classList.remove('open');
    document.body.style.overflow = '';
    document.getElementById('sheet-hint').style.display = 'none';
}

function modalColor(el, name) {
    document.querySelectorAll('#color-btns-modal .color-btn').forEach(function(b) { b.classList.remove('active'); });
    el.classList.add('active');
    mColor = name;
    document.getElementById('modal-color').value = name;
    updateTapRow();
}

function modalSize(el, size) {
    document.querySelectorAll('#size-btns-modal .size-btn').forEach(function(b) { b.classList.remove('active'); });
    el.classList.add('active');
    mSize = size;
    document.getElementById('modal-size').value = size;
    updateTapRow();
}

function modalQty(delta) {
    mQty = Math.max(1, mQty + delta);
    document.getElementById('modal-qty-display').textContent = mQty;
    document.getElementById('modal-qty').value = mQty;
}

function modalSubmit() {
    if (!mColor || !mSize) {
        var hint = document.getElementById('sheet-hint');
        hint.style.display = 'block';
        return;
    }
    document.getElementById('sheet-hint').style.display = 'none';
    document.getElementById('modal-form').submit();
}

function updateTapRow() {
    var el = document.getElementById('tap-value');
    if (!el) return;
    if (mColor || mSize) {
        el.className = 'vt-value';
        var parts = [];
        if (mColor) parts.push(mColor);
        if (mSize)  parts.push('Ukuran ' + mSize);
        parts.push(mQty + ' pcs');
        el.textContent = parts.join(' · ');
    } else {
        el.className = 'vt-hint';
        el.textContent = 'Pilih warna & ukuran';
    }
}

/* ─── DESKTOP INLINE ─── */
function deskColor(el, name) {
    document.querySelectorAll('#color-btns-desk .color-btn').forEach(function(b) { b.classList.remove('active'); });
    el.classList.add('active');
    dColor = name;
    document.getElementById('desk-color').value = name;
    updateDeskSummary();
}

function deskSize(el, size) {
    document.querySelectorAll('#size-btns-desk .size-btn').forEach(function(b) { b.classList.remove('active'); });
    el.classList.add('active');
    dSize = size;
    document.getElementById('desk-size').value = size;
    updateDeskSummary();
}

function deskQty(delta) {
    dQty = Math.max(1, dQty + delta);
    document.getElementById('desk-qty-display').textContent = dQty;
    document.getElementById('desk-qty').value = dQty;
    updateDeskSummary();
}

function updateDeskSummary() {
    var el = document.getElementById('vs-value-desk');
    if (!el) return;
    var parts = [];
    if (dColor) parts.push(dColor);
    if (dSize)  parts.push('Ukuran ' + dSize);
    parts.push(dQty + ' pcs');
    if (dColor || dSize) {
        el.className = 'vs-value';
        el.textContent = parts.join(' · ');
        document.getElementById('desk-hint').style.display = 'none';
    } else {
        el.className = 'vs-hint';
        el.textContent = 'Pilih warna & ukuran';
    }
}

function deskSubmit(mode) {
    if (!dColor || !dSize) {
        var hint = document.getElementById('desk-hint');
        hint.style.display = 'block';
        hint.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }
    document.getElementById('desk-mode').value = mode;
    document.getElementById('desk-qty').value = dQty;
    document.getElementById('desk-cart-form').submit();
}

/* Swipe down to close sheet */
(function() {
    var sheet = document.getElementById('sheet');
    var startY = 0;
    sheet.addEventListener('touchstart', function(e) { startY = e.touches[0].clientY; }, { passive: true });
    sheet.addEventListener('touchend', function(e) {
        var dy = e.changedTouches[0].clientY - startY;
        if (dy > 60) closeModal();
    }, { passive: true });
})();
</script>

@include('storefront._mobile_zoom_lock')

</body>
</html>
