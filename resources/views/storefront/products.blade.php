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
        .filter-panel { margin-top: 18px; min-width: 0; }
        .filter-group { min-width: 0; }
        .filter-label { display: block; font-size: 10px; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; color: var(--mid); margin-bottom: 8px; }
        .filter-scroll { display: flex; align-items: center; gap: 6px; overflow-x: auto; padding: 1px; scrollbar-width: none; }
        .filter-scroll::-webkit-scrollbar { display: none; }
        .filter-option { flex: 0 0 auto; min-height: 32px; padding: 0 11px; border-radius: 8px; border: 1px solid transparent; background: transparent; color: var(--mid); display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; transition: background .15s, border-color .15s, color .15s; }
        .filter-option:hover { background: var(--soft); color: var(--ink); }
        .filter-option.active { background: var(--ink); color: var(--white); border-color: var(--ink); }
        .filter-category { margin-top: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--line); }
        .filter-category .filter-option { min-height: 30px; padding: 0 4px; border-radius: 0; background: transparent; border: 0; color: var(--mid); position: relative; }
        .filter-category .filter-option + .filter-option { margin-left: 14px; }
        .filter-category .filter-option.active { color: var(--ink); background: transparent; }
        .filter-category .filter-option.active::after { content: ""; position: absolute; left: 0; right: 0; bottom: -13px; height: 2px; border-radius: 99px; background: var(--ink); }
        .filter-secondary { margin-top: 12px; display: grid; grid-template-columns: minmax(0, 1fr) auto auto; gap: 12px; align-items: center; }
        .filter-secondary .filter-group { display: flex; align-items: center; gap: 8px; overflow: hidden; }
        .filter-secondary .filter-label { margin: 0; flex: 0 0 auto; }
        .filter-size { justify-self: end; }
        .filter-reset { min-height: 32px; padding: 0 2px; border: 0; background: transparent; color: var(--mid); display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; white-space: nowrap; }
        .filter-reset:hover { color: var(--ink); }
        .filter-summary { margin-top: 10px; display: flex; align-items: center; justify-content: space-between; gap: 12px; font-size: 12px; color: var(--mid); font-weight: 700; }
        .filter-summary strong { color: var(--ink); font-weight: 900; }

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
            .products { grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 72px; }
            .page-head { border-bottom: 1px solid var(--line); padding: 28px 0 20px; margin-bottom: 20px; }
            .filter-panel { margin-top: 20px; }
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
            .filter-secondary { grid-template-columns: 1fr; gap: 10px; align-items: start; }
            .filter-secondary .filter-group { display: block; }
            .filter-secondary .filter-label { margin-bottom: 8px; }
            .filter-size { justify-self: stretch; }
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
            .filter-panel { margin-top: 18px; }
            .filter-option { min-height: 32px; padding: 0 10px; font-size: 11px; }
            .filter-category { margin-top: 12px; padding-bottom: 10px; }
            .filter-category .filter-option { padding: 0 2px; }
            .filter-category .filter-option + .filter-option { margin-left: 13px; }
            .filter-category .filter-option.active::after { bottom: -11px; }
            .filter-secondary { grid-template-columns: 1fr; gap: 10px; align-items: start; margin-top: 12px; }
            .filter-secondary .filter-group { display: block; }
            .filter-secondary .filter-label { margin-bottom: 8px; }
            .filter-size { justify-self: stretch; }
            .filter-summary { align-items: flex-start; flex-direction: column; gap: 6px; }
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

@php
    $navActive = 'products';
@endphp
@include('storefront._nav')

<main>
<div class="wrap">

    <div class="page-head">
        <div class="breadcrumb">
            <a href="{{ route('storefront.home') }}">Home</a>
            <span>/</span>
            <span>Produk</span>
        </div>
        <div class="page-title">
            @if($activeType === 'jumbo')
                Koleksi Big Size
                @if($activeCategory && $categories->firstWhere('slug', $activeCategory))
                    — {{ $categories->firstWhere('slug', $activeCategory)->name }}
                @endif
            @elseif($activeAudience && isset($audienceOptions[$activeAudience]))
                Koleksi {{ $audienceOptions[$activeAudience] }}
                @if($activeCategory && $categories->firstWhere('slug', $activeCategory))
                    — {{ $categories->firstWhere('slug', $activeCategory)->name }}
                @endif
            @elseif($activeCategory && $categories->firstWhere('slug', $activeCategory))
                {{ $categories->firstWhere('slug', $activeCategory)->name }}
            @else
                Semua Produk
            @endif
        </div>
        <div class="page-count">{{ count($products) }} produk tersedia</div>
        @php
        $audienceChipColors = [
            'pria'     => '#1d4ed8',
            'wanita'   => '#be185d',
            'anak'     => '#d97706',
            'olahraga' => '#15803d',
            'unisex'   => '#6b7280',
        ];
        $hasActiveFilter = $activeCategory || $activeAudience || $activeType;
        $baseParams = array_filter([
            'audience' => $activeAudience,
            'type'     => $activeType,
        ]);
        @endphp

        <div class="filter-panel" aria-label="Filter produk">
            @if($categories->isNotEmpty())
            <div class="filter-group filter-category">
                <div class="filter-scroll">
                    <a href="{{ route('storefront.products', $baseParams) }}"
                       class="filter-option{{ !$activeCategory ? ' active' : '' }}">Semua</a>
                    @foreach($categories as $cat)
                    <a href="{{ route('storefront.products', array_filter(['kategori' => $cat->slug, 'audience' => $activeAudience, 'type' => $activeType])) }}"
                       class="filter-option{{ $activeCategory === $cat->slug ? ' active' : '' }}">{{ $cat->name }}</a>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="filter-secondary">
                <div class="filter-group">
                    <span class="filter-label">Untuk</span>
                    <div class="filter-scroll">
                        <a href="{{ route('storefront.products', array_filter(['kategori' => $activeCategory, 'type' => $activeType])) }}"
                           class="filter-option{{ !$activeAudience ? ' active' : '' }}">Semua</a>
                        @foreach($audienceOptions as $val => $lbl)
                        <a href="{{ route('storefront.products', array_filter(['audience' => $val, 'kategori' => $activeCategory, 'type' => $activeType])) }}"
                           class="filter-option{{ $activeAudience === $val ? ' active' : '' }}">{{ $lbl }}</a>
                        @endforeach
                    </div>
                </div>

                <div class="filter-group filter-size">
                    <span class="filter-label">Ukuran</span>
                    <div class="filter-scroll">
                        <a href="{{ route('storefront.products', array_filter(['kategori' => $activeCategory, 'audience' => $activeAudience])) }}"
                           class="filter-option{{ !$activeType ? ' active' : '' }}">Semua</a>
                        <a href="{{ route('storefront.products', array_filter(['type' => 'jumbo', 'kategori' => $activeCategory, 'audience' => $activeAudience])) }}"
                           class="filter-option{{ $activeType === 'jumbo' ? ' active' : '' }}">Big Size</a>
                    </div>
                </div>

                @if($hasActiveFilter)
                <a href="{{ route('storefront.products') }}" class="filter-reset">Reset</a>
                @endif
            </div>
        </div>

        <div class="filter-summary">
            <span><strong>{{ count($products) }}</strong> produk ditemukan</span>
        </div>
    </div>

    <div class="products">
        @foreach ($products as $p)
        <a href="{{ route('storefront.product_detail', $p['slug']) }}" class="pc">
            <div class="pc-img">
                @if(!empty($p['label']))
                @if(($p['product_type'] ?? '') === 'jumbo')
                <span class="pc-lbl" style="background:#7c3aed;">{{ $p['label'] }}</span>
                @else
                <span class="pc-lbl">{{ $p['label'] }}</span>
                @endif
                @endif
                <img src="{{ storefront_img($p['img']) }}" alt="{{ $p['name'] }}" loading="lazy">
            </div>
            <div class="pc-body">
                @if(!empty($p['audience_label']))
                <div style="margin-bottom:5px;">
                    <span style="font-size:10px;font-weight:800;padding:2px 7px;border-radius:999px;
                                 background:{{ $audienceChipColors[$p['audience']] ?? '#6b7280' }}18;
                                 color:{{ $audienceChipColors[$p['audience']] ?? '#6b7280' }};">
                        {{ $p['audience_label'] }}
                    </span>
                </div>
                @endif
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
                    @foreach($categories as $cat)
                    <a href="{{ route('storefront.products', ['kategori' => $cat->slug]) }}">{{ $cat->name }}</a>
                    @endforeach
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

@include('storefront._tracker')
@include('storefront._mobile_zoom_lock')

</body>
</html>
