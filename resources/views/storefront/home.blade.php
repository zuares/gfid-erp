<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>Greatfit</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700;800;900&family=Barlow+Condensed:wght@800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --ink: #0a0a0a;
            --mid: #888;
            --line: #e8e8e8;
            --soft: #f4f4f4;
            --white: #fff;
            --safe: env(safe-area-inset-bottom, 0px);
        }
        html { scroll-behavior: smooth; min-height: 100%; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            color: var(--ink); background: var(--white);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            padding-bottom: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        a { color: inherit; text-decoration: none; }
        img { display: block; max-width: 100%; }
        .wrap { width: min(1120px, calc(100% - 28px)); margin: 0 auto; }

        /* NAV */
        .nav {
            position: sticky; top: 0; z-index: 100;
            background: rgba(255,255,255,.96);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--line);
        }
        .nav-inner { height: 56px; display: flex; align-items: center; justify-content: space-between; }
        .brand { display: flex; align-items: center; gap: 8px; font-weight: 900; font-size: 12px; letter-spacing: .16em; text-transform: uppercase; }
        .brand img { width: 28px; height: 28px; object-fit: contain; }
        .nav-r { display: flex; align-items: center; gap: 16px; }
        .nav-links { display: none; gap: 18px; font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--mid); }
        .nav-links a:hover { color: var(--ink); }
        .btn-nav { height: 32px; padding: 0 14px; border-radius: 999px; background: var(--ink); color: var(--white); font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; display: inline-flex; align-items: center; }
        .cart-icon { position: relative; width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: var(--ink); transition: background .15s; }
        .cart-icon:hover { background: var(--soft); }
        .cart-badge { position: absolute; top: -2px; right: -2px; width: 16px; height: 16px; border-radius: 50%; background: var(--ink); color: var(--white); font-size: 9px; font-weight: 800; display: grid; place-items: center; border: 2px solid var(--white); }

        /* HERO — mobile stacked, desktop split */
        .hero-mobile {
            margin: 10px 0 0;
            display: grid;
            gap: 12px;
        }
        .hm-content { min-height: 284px; display: flex; flex-direction: column; justify-content: center; padding: 24px 4px 10px; position: relative; overflow: hidden; }
        .hm-label { font-size: 10px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: var(--mid); display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }
        .hm-label::before { content: ''; width: 18px; height: 2px; background: var(--ink); display: block; }
        .hm-title { font-family: 'Barlow Condensed', sans-serif; font-size: clamp(64px, 19vw, 82px); font-weight: 900; line-height: .86; letter-spacing: -.01em; text-transform: uppercase; margin-bottom: 22px; }
        .hm-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-w { height: 42px; padding: 0 20px; border-radius: 999px; background: var(--white); color: var(--ink); font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }
        .btn-g { height: 42px; padding: 0 20px; border-radius: 999px; background: transparent; color: var(--ink); font-size: 12px; font-weight: 700; border: 1.5px solid var(--line); display: inline-flex; align-items: center; }
        .hm-visual { min-height: 360px; border-radius: 20px; background: var(--ink); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
        .hero-bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 0; opacity: 0; transition: opacity 1.4s ease; }
        .hero-bg.active { opacity: .6; }
        .hm-badge { position: absolute; top: 18px; right: 18px; z-index: 2; width: 62px; height: 62px; border-radius: 50%; background: var(--white); color: var(--ink); display: grid; place-items: center; font-size: 9px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; text-align: center; line-height: 1.4; }
        .hm-card { position: absolute; left: 14px; right: 14px; bottom: 14px; z-index: 2; background: rgba(255,255,255,.95); backdrop-filter: blur(10px); border-radius: 14px; padding: 13px 14px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 20px rgba(0,0,0,.15); }
        .hm-card-t { font-size: 13px; font-weight: 700; }
        .hm-card-s { font-size: 11px; color: var(--mid); margin-top: 2px; }
        .hm-card-ic { width: 34px; height: 34px; border-radius: 50%; background: var(--ink); color: var(--white); display: grid; place-items: center; flex-shrink: 0; }

        /* HERO — desktop split */
        .hero-desktop { display: none; min-height: calc(100svh - 56px); grid-template-columns: 1fr 1fr; }
        .hd-content { display: flex; flex-direction: column; justify-content: center; padding: 60px 0; }
        .hd-label { font-size: 10px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: var(--mid); display: flex; align-items: center; gap: 8px; margin-bottom: 18px; }
        .hd-label::before { content: ''; width: 18px; height: 2px; background: var(--ink); display: block; }
        .hd-title { font-family: 'Barlow Condensed', sans-serif; font-size: clamp(64px, 8vw, 100px); font-weight: 900; line-height: .88; text-transform: uppercase; letter-spacing: -.01em; margin-bottom: 28px; }
        .hd-actions { display: flex; gap: 10px; }
        .btn-dk { height: 46px; padding: 0 24px; border-radius: 999px; background: var(--ink); color: var(--white); font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 7px; transition: opacity .15s; }
        .btn-dk:hover { opacity: .8; }
        .btn-sk { height: 46px; padding: 0 24px; border-radius: 999px; background: transparent; color: var(--ink); border: 1.5px solid var(--line); font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; transition: border-color .15s; }
        .btn-sk:hover { border-color: var(--ink); }
        .hd-visual { background: var(--ink); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
        .hd-photo { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 0; opacity: 0; transition: opacity 1.4s ease; }
        .hd-photo.active { opacity: .6; }
        .hd-badge { position: absolute; top: 28px; right: 28px; z-index: 2; width: 68px; height: 68px; border-radius: 50%; background: var(--white); color: var(--ink); display: grid; place-items: center; font-size: 9px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; text-align: center; line-height: 1.4; }
        .hd-card { position: absolute; bottom: 24px; left: 24px; right: 24px; z-index: 2; background: rgba(255,255,255,.95); backdrop-filter: blur(10px); border-radius: 14px; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 20px rgba(0,0,0,.15); }
        .hd-card-t { font-size: 13px; font-weight: 700; }
        .hd-card-s { font-size: 11px; color: var(--mid); margin-top: 2px; }
        .hd-card-ic { width: 34px; height: 34px; border-radius: 50%; background: var(--ink); color: var(--white); display: grid; place-items: center; }

        /* STRIP */
        .strip { background: var(--ink); padding: 11px 0; overflow: hidden; margin-top: 12px; }
        .strip-track { display: flex; gap: 36px; white-space: nowrap; animation: mq 20s linear infinite; }
        .strip-i { display: inline-flex; align-items: center; gap: 8px; font-size: 10px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: rgba(255,255,255,.7); flex-shrink: 0; }
        .strip-dot { width: 3px; height: 3px; border-radius: 50%; background: rgba(255,255,255,.3); }
        @keyframes mq { from { transform: translateX(0); } to { transform: translateX(-50%); } }

        /* SECTIONS */
        .sec { padding: 28px 0; }
        .sec-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
        .sec-t { font-size: 13px; font-weight: 800; letter-spacing: -.01em; }
        .sec-a { font-size: 12px; font-weight: 700; color: var(--mid); }
        .sec-a:hover { color: var(--ink); }

        /* CHANNELS */
        .sec-beli { position: sticky; top: 56px; z-index: 90; background: rgba(255,255,255,.97); backdrop-filter: blur(10px); border-bottom: 1px solid var(--line); padding: 14px 0 12px; }
        .sec-beli-label { font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--mid); margin-bottom: 10px; }
        .chs { display: grid; grid-template-columns: repeat(4, 1fr); gap: 7px; }
        .ch { height: 40px; border-radius: 10px; background: var(--soft); border: 1px solid var(--line); display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; transition: background .15s; }
        .ch:hover { background: var(--line); }
        .ch.dk { background: var(--ink); color: var(--white); border-color: var(--ink); }

        /* PRODUCTS */
        .prods { display: grid; grid-template-columns: repeat(2, 1fr); gap: 9px; }
        .pc { border-radius: 16px; overflow: hidden; background: var(--soft); border: 1px solid var(--line); display: block; }
        .pc-img { aspect-ratio: 1; position: relative; background: var(--soft); overflow: hidden; }
        .pc-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .pc-tag { position: absolute; top: 9px; left: 9px; z-index: 3; background: var(--ink); color: var(--white); font-size: 9px; font-weight: 800; padding: 3px 7px; border-radius: 999px; letter-spacing: .04em; }
        .pc-b { padding: 10px 11px; }
        .pc-n { font-size: 12px; font-weight: 700; line-height: 1.2; color: var(--ink); }
        .pc-p { font-size: 14px; font-weight: 900; margin-top: 4px; }

        /* VALUES — minimal row */
        .vals { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: var(--line); border: 1px solid var(--line); border-radius: 16px; overflow: hidden; }
        .val { background: var(--white); padding: 20px 16px; }
        .val-n { font-family: 'Barlow Condensed', sans-serif; font-size: 32px; font-weight: 900; color: var(--line); line-height: 1; margin-bottom: 8px; }
        .val-t { font-size: 12px; font-weight: 800; margin-bottom: 4px; }
        .val-d { font-size: 11px; color: var(--mid); font-weight: 500; line-height: 1.55; display: none; }

        /* CLOSING */
        .cta { background: var(--ink); color: var(--white); border-radius: 20px; padding: 40px 20px; text-align: center; }
        .cta-t { font-family: 'Barlow Condensed', sans-serif; font-size: clamp(40px, 10vw, 72px); font-weight: 900; text-transform: uppercase; line-height: .88; margin-bottom: 20px; }
        .cta-row { display: flex; gap: 8px; justify-content: center; }
        .btn-cw { height: 44px; padding: 0 22px; border-radius: 999px; background: var(--white); color: var(--ink); font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; }
        .btn-co { height: 44px; padding: 0 22px; border-radius: 999px; background: transparent; color: rgba(255,255,255,.65); border: 1px solid rgba(255,255,255,.2); font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; }

        /* FOOTER */
        .foot { padding: 22px 0 18px; border-top: 1px solid var(--line); margin-top: 28px; }
        .foot-brand { display: flex; align-items: center; gap: 9px; margin-bottom: 8px; }
        .foot-brand img { width: 26px; height: 26px; object-fit: contain; }
        .foot-name { font-size: 12px; font-weight: 900; letter-spacing: .15em; text-transform: uppercase; }
        .foot-tagline { font-size: 12px; color: var(--mid); font-weight: 600; line-height: 1.55; margin-bottom: 16px; }
        .foot-links { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 16px; }
        .foot-links a { height: 38px; border-radius: 12px; background: var(--soft); border: 1px solid var(--line); display: grid; place-items: center; font-size: 11px; font-weight: 800; color: var(--ink); }
        .foot-bottom { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .foot-bottom span, .foot-bottom a { font-size: 11px; color: var(--mid); font-weight: 600; }
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


        /* DESKTOP */
        @media (min-width: 760px) {
            body { padding-bottom: 0; }
            .hero-mobile { display: none; }
            .hero-desktop { display: grid; }
            .nav-links { display: flex; }
            .strip { margin-top: 0; }
            .prods { grid-template-columns: repeat(4, 1fr); gap: 12px; }
            .chs { gap: 10px; }
            .ch { height: 44px; font-size: 12px; }

            .val-d { display: block; }
            .val { padding: 28px 22px; }
            .cta { padding: 56px 24px; border-radius: 24px; }
            .sec { padding: 40px 0; }
            .foot { display: none; }
            .site-footer { display: block; }
        }
    </style>
</head>
<body>

<header class="nav">
    <div class="wrap nav-inner">
        <a href="{{ route('storefront.home') }}" class="brand">
            <img src="{{ asset('images/logo-mark.svg') }}" alt="Greatfit">
            <span>Greatfit</span>
        </a>
        <div class="nav-r">
            <nav class="nav-links">
                <a href="#products">Products</a>
            </nav>
            @php $cartCount = array_sum(array_column(session('cart', []), 'qty')); @endphp
            <a href="#" class="cart-icon" title="Cari" onclick="return false;" style="color:var(--ink)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            </a>
            <a href="{{ route('storefront.cart') }}" class="cart-icon" title="Keranjang">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                @if($cartCount > 0)<span class="cart-badge">{{ $cartCount }}</span>@endif
            </a>
        </div>
    </div>
</header>

{{-- HERO MOBILE --}}
<div class="wrap">
    <div class="hero-mobile">
        <div class="hm-content">
            <div class="hm-label">New Collection 2026</div>
            <div class="hm-title">Good Fit,<br>Good Feel.</div>
            <div class="hm-actions">
                <a href="#products" class="btn-dk">
                    Products
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
                <a href="#products" class="btn-sk">Lihat Koleksi</a>
            </div>
        </div>
        <div class="hm-visual">
            <img class="hero-bg active" src="https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?w=600&h=760&fit=crop&auto=format&q=80" alt="">
            <img class="hero-bg" src="https://images.unsplash.com/photo-1548690312-e3b507d8c110?w=600&h=760&fit=crop&auto=format&q=80" alt="">
            <img class="hero-bg" src="https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?w=600&h=760&fit=crop&auto=format&q=80" alt="">
            <div class="hm-badge">New<br>2026</div>
            <a href="#products" class="hm-card">
                <div>
                    <div class="hm-card-t">Greatfit Collection</div>
                    <div class="hm-card-s">Comfort & Style</div>
                </div>
                <div class="hm-card-ic">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </div>
            </a>
        </div>
    </div>
</div>

{{-- HERO DESKTOP --}}
<section class="hero-desktop">
    <div class="hd-content" style="padding-left:max(14px,calc((100vw - 1120px)/2 + 14px));">
        <div class="hd-label">New Collection 2026</div>
        <h1 class="hd-title">Good Fit,<br>Good Feel.</h1>
        <div class="hd-actions">
            <a href="#products" class="btn-dk">
                Products
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
            <a href="#products" class="btn-sk">Lihat Koleksi</a>
        </div>
    </div>
    <div class="hd-visual">
        <img class="hd-photo active" src="https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?w=900&h=1100&fit=crop&auto=format&q=80" alt="">
        <img class="hd-photo" src="https://images.unsplash.com/photo-1548690312-e3b507d8c110?w=900&h=1100&fit=crop&auto=format&q=80" alt="">
        <img class="hd-photo" src="https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?w=900&h=1100&fit=crop&auto=format&q=80" alt="">
        <div class="hd-badge">New<br>2026</div>
        <a href="#products" class="hd-card">
            <div>
                <div class="hd-card-t">Greatfit Collection</div>
                <div class="hd-card-s">Comfort & Style</div>
            </div>
            <div class="hd-card-ic">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </div>
        </a>
    </div>
</section>

{{-- STRIP --}}
<div class="strip">
    <div class="strip-track">
        @foreach (range(1,8) as $i)
            <span class="strip-i">Good Fit <span class="strip-dot"></span></span>
            <span class="strip-i">Good Feel <span class="strip-dot"></span></span>
            <span class="strip-i">Greatfit <span class="strip-dot"></span></span>
            <span class="strip-i">Quality First <span class="strip-dot"></span></span>
        @endforeach
    </div>
</div>

{{-- BELI LEWAT — sticky di bawah nav --}}
<div class="sec-beli" id="beli">
    <div class="wrap">
        <div class="sec-beli-label">Beli Lewat</div>
        <div class="chs">
            @foreach($channels as $ch)
            <a class="ch {{ ($ch['dark'] ?? false) ? 'dk' : '' }}" href="{{ $ch['url'] ?? '#' }}" @if(!($ch['dark'] ?? false)) target="_blank" rel="noopener" @endif>{{ $ch['label'] }}</a>
            @endforeach
        </div>
    </div>
</div>

<div class="wrap">

    {{-- PRODUCTS --}}
    <section class="sec" id="products">
        <div class="sec-head">
            <div class="sec-t">Produk</div>
            <a href="{{ route('storefront.products') }}" class="sec-a">Semua →</a>
        </div>
        <div class="prods">
            @foreach ($products as $p)
            <a href="{{ route('storefront.product_detail', $p['slug']) }}" class="pc">
                <div class="pc-img">
                    <span class="pc-tag">{{ $p['label'] }}</span>
                    <img src="{{ storefront_img($p['img']) }}" alt="{{ $p['name'] }}" loading="lazy">
                </div>
                <div class="pc-b">
                    <div class="pc-n">{{ $p['name'] }}</div>
                    <div class="pc-p">Rp{{ number_format($p['price'], 0, ',', '.') }}</div>
                </div>
            </a>
            @endforeach
        </div>
    </section>

    {{-- VALUES --}}
    <section class="sec" style="padding-top:0;">
        <div class="vals">
            <div class="val"><div class="val-n">01</div><div class="val-t">Nyaman</div><div class="val-d">Enak dipakai seharian.</div></div>
            <div class="val"><div class="val-n">02</div><div class="val-t">Presisi</div><div class="val-d">Pas di badan, pas ukurannya.</div></div>
            <div class="val"><div class="val-n">03</div><div class="val-t">Tahan Lama</div><div class="val-d">Material berkualitas.</div></div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="sec" style="padding-top:0;">
        <div class="cta">
            <div class="cta-t">Ready to<br>Wear Daily.</div>
            <div class="cta-row">
                <a href="{{ route('storefront.products') }}" class="btn-cw">Shop Now</a>
                <a href="#beli" class="btn-co">Marketplace</a>
            </div>
        </div>
    </section>

    <footer class="foot">
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
    </footer>

</div>

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


<script>
(function () {
    // Hero slideshow
    var mobile  = document.querySelectorAll('.hero-bg');
    var desktop = document.querySelectorAll('.hd-photo');
    var total   = mobile.length;
    var current = 0;

    if (total >= 2) {
        setInterval(function () {
            mobile[current].classList.remove('active');
            desktop[current] && desktop[current].classList.remove('active');
            current = (current + 1) % total;
            mobile[current].classList.add('active');
            desktop[current] && desktop[current].classList.add('active');
        }, 5000);
    }

})();
</script>

@include('storefront._mobile_zoom_lock')

</body>
</html>
