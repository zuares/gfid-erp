<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>Produk — Greatfit</title>
    <meta name="description" content="Koleksi lengkap Greatfit — pakaian nyaman untuk aktivitas harian.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --ink: #0a0a0a;
            --mid: #555;
            --line: #ebebeb;
            --soft: #f5f5f5;
            --white: #fff;
            --safe: env(safe-area-inset-bottom, 0px);
        }
        html { scroll-behavior: smooth; min-height: 100%; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: var(--ink); background: var(--white);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main { flex: 1 0 auto; display: flex; flex-direction: column; }
        main > .wrap { flex: 1 0 auto; display: flex; flex-direction: column; }
        a { color: inherit; text-decoration: none; }
        img { display: block; max-width: 100%; }
        .wrap { width: min(1680px, calc(100% - 64px)); margin: 0 auto; }

        /* NAV */
        .nav {
            position: sticky; top: 0; z-index: 100;
            background: rgba(255,255,255,.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
        }
        .nav-inner { height: 56px; display: flex; align-items: center; justify-content: space-between; max-width: 1680px; margin: 0 auto; padding: 0 20px; }
        .brand { display: flex; align-items: center; gap: 8px; font-weight: 900; font-size: 12px; letter-spacing: .16em; text-transform: uppercase; }
        .brand img { width: 28px; height: 28px; object-fit: contain; }
        .nav-r { display: flex; align-items: center; gap: 16px; }
        .nav-links { display: none; gap: 18px; font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--mid); }
        .nav-links a:hover { color: var(--ink); }
        .nav-links a.active { color: var(--ink); }
        .btn-nav { height: 34px; padding: 0 14px; border-radius: 999px; background: var(--ink); color: var(--white); font-size: 12px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; display: inline-flex; align-items: center; }
        .cart-icon { position: relative; width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: var(--ink); transition: background .15s; }
        .cart-icon:hover { background: var(--soft); }
        .cart-badge { position: absolute; top: -2px; right: -2px; width: 16px; height: 16px; border-radius: 50%; background: var(--ink); color: var(--white); font-size: 9px; font-weight: 800; display: grid; place-items: center; border: 2px solid var(--white); }

        /* PAGE HEADER */
        .page-head { padding: 24px 0 18px; margin-bottom: 16px; }
        .breadcrumb { font-size: 12px; color: var(--mid); font-weight: 500; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
        .breadcrumb a:hover { color: var(--ink); }
        .page-title { font-size: 24px; font-weight: 900; letter-spacing: -.03em; }
        .page-count { font-size: 13px; color: var(--mid); font-weight: 500; margin-top: 4px; }
        .product-rail { display: flex; gap: 8px; overflow-x: auto; padding: 2px 0 2px; margin-top: 16px; scrollbar-width: none; }
        .product-rail::-webkit-scrollbar { display: none; }
        .rail-chip { flex: 0 0 auto; height: 34px; padding: 0 13px; border-radius: 999px; border: 1px solid var(--line); background: var(--soft); display: inline-flex; align-items: center; font-size: 12px; font-weight: 800; color: #222; }
        .rail-chip.dark { background: var(--ink); color: var(--white); border-color: var(--ink); }

        /* PRODUCTS GRID */
        .products { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .pc { border-radius: 16px; overflow: hidden; background: var(--white); border: 1px solid var(--line); display: block; transition: transform .2s, box-shadow .2s; }
        .pc:hover { transform: translateY(-2px); }
        .pc-img { aspect-ratio: 1; position: relative; background: var(--soft); overflow: hidden; }
        .pc-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .pc-lbl { position: absolute; top: 10px; left: 10px; z-index: 3; background: var(--ink); color: var(--white); font-size: 10px; font-weight: 800; padding: 4px 8px; border-radius: 999px; }
        .pc-body { padding: 11px; }
        .pc-name { font-size: 13px; font-weight: 800; line-height: 1.28; min-height: 34px; }
        .pc-meta { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-top: 7px; }
        .pc-price { font-size: 15px; font-weight: 900; white-space: nowrap; }
        .pc-sold { font-size: 11px; color: var(--mid); }
        .pc-desc { margin-top: 8px; color: var(--mid); font-size: 11px; font-weight: 600; line-height: 1.45; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .pc-options { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--line); }
        .pc-colors { display: flex; align-items: center; gap: 4px; min-width: 0; }
        .pc-dot { width: 13px; height: 13px; border-radius: 50%; box-shadow: inset 0 0 0 1px rgba(0,0,0,.14); }
        .pc-sizes { font-size: 10px; color: var(--mid); font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pc-cta { display: flex; align-items: center; justify-content: space-between; gap: 6px; height: 34px; margin-top: 10px; padding: 0 10px 0 12px; border-radius: 10px; background: var(--soft); color: var(--ink); font-size: 11px; font-weight: 900; }
        .pc:hover .pc-cta { background: var(--ink); color: var(--white); }

        /* FOOTER */
        .foot { border-top: 1px solid var(--line); margin-top: auto; padding: 22px 0 18px; }
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

        @media (min-width: 720px) {
            .nav-inner { padding: 0 32px; }
            .nav-links { display: flex; }
            .products { grid-template-columns: repeat(4, 1fr); gap: 14px; }
            .page-head { border-bottom: 1px solid var(--line); padding: 28px 0 20px; margin-bottom: 20px; }
            .product-rail { margin-top: 18px; }
            .foot { display: none; }
            .site-footer { display: block; }
            .channels { gap: 12px; }
            .ch { height: 58px; font-size: 13px; }
        }
        @media (min-width: 1280px) {
            .products { grid-template-columns: repeat(5, 1fr); gap: 16px; }
        }
        @media (min-width: 720px) and (max-width: 1080px) {
            .wrap { width: min(100% - 56px, 920px); }
            .page-head { padding: 34px 0 24px; margin-bottom: 24px; }
            .products { grid-template-columns: repeat(3, 1fr); gap: 16px; }
            .pc { border-radius: 16px; }
            .pc-body { padding: 14px; }
            .pc-desc { margin-top: 10px; }
            .pc-options { margin-top: 12px; padding-top: 12px; }
            .pc-cta { margin-top: 12px; }
        }
        @media (max-width: 719px) {
            .wrap { width: min(520px, calc(100% - 28px)); }
            .nav-r { gap: 8px; }
            .breadcrumb { display: none; }
            .page-head { padding: 28px 0 22px; margin-bottom: 18px; }
            .page-title { font-size: 26px; }
            .product-rail { margin-top: 18px; }
            .products { gap: 12px; }
            .pc { border-radius: 14px; }
            .pc-body { padding: 12px; }
            .pc-desc { margin-top: 9px; }
            .pc-options { margin-top: 12px; padding-top: 12px; }
            .pc-cta { margin-top: 12px; }
            .pc-meta { display: block; }
            .pc-sold { margin-top: 2px; }
            .foot { padding-top: 26px; padding-bottom: 22px; }
        }
    </style>
</head>
<body>

<header class="nav">
    <div class="nav-inner">
        <a href="{{ route('storefront.home') }}" class="brand">
            <img src="{{ asset('images/logo-mark.svg') }}" alt="Greatfit">
            <span>Greatfit</span>
        </a>
        <div class="nav-r">
            <nav class="nav-links">
                <a href="{{ route('storefront.products') }}" class="active">Produk</a>
                <a href="{{ route('storefront.home') }}#beli">Beli</a>
            </nav>
            @php $cartCount = array_sum(array_column(session('cart', []), 'qty')); @endphp
            <a href="#" class="cart-icon" title="Cari" onclick="return false;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            </a>
            <a href="{{ route('storefront.cart') }}" class="cart-icon" title="Keranjang">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                @if($cartCount > 0)<span class="cart-badge">{{ $cartCount }}</span>@endif
            </a>
        </div>
    </div>
</header>

<main>
<div class="wrap">

    <div class="page-head">
        <div class="breadcrumb">
            <a href="{{ route('storefront.home') }}">Home</a>
            <span>/</span>
            <span>Produk</span>
        </div>
        <div class="page-title">Semua Produk</div>
        <div class="page-count">{{ count($products) }} produk tersedia</div>
        <div class="product-rail" aria-label="Sorotan produk">
            <span class="rail-chip dark">Ready stock</span>
            <span class="rail-chip">Bahan nyaman</span>
            <span class="rail-chip">Ukuran S-XXL</span>
            <span class="rail-chip">Warna basic</span>
        </div>
    </div>

    <div class="products">
        @foreach ($products as $p)
        <a href="{{ route('storefront.product_detail', $p['slug']) }}" class="pc">
            <div class="pc-img">
                <span class="pc-lbl">{{ $p['label'] }}</span>
                <img src="{{ storefront_img($p['img']) }}" alt="{{ $p['name'] }}" loading="lazy">
            </div>
            <div class="pc-body">
                <div class="pc-name">{{ $p['name'] }}</div>
                <div class="pc-meta">
                    <div class="pc-price">Rp{{ number_format($p['price'], 0, ',', '.') }}</div>
                    <div class="pc-sold">{{ $p['sold'] }} terjual</div>
                </div>
                <div class="pc-desc">{{ $p['desc'] }}</div>
                <div class="pc-options">
                    <div class="pc-colors" aria-label="Warna tersedia">
                        @foreach(array_slice($p['colors'] ?? [], 0, 4) as $c)
                        <span class="pc-dot" title="{{ $c['name'] }}" style="background:{{ $c['hex'] }}"></span>
                        @endforeach
                    </div>
                    <div class="pc-sizes">{{ implode('/', array_slice($p['sizes'] ?? [], 0, 4)) }}</div>
                </div>
                <div class="pc-cta">
                    <span>Lihat detail</span>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </div>
            </div>
        </a>
        @endforeach
    </div>

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
</main>

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

@include('storefront._mobile_zoom_lock')

</body>
</html>
