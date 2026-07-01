<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>Keranjang — Greatfit</title>
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
        html { min-height: 100%; }
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
        .wrap { width: min(760px, calc(100% - 32px)); margin: 0 auto; }

        /* NAV */
        .nav { position: sticky; top: 0; z-index: 100; background: rgba(255,255,255,.96); backdrop-filter: blur(14px); border-bottom: 1px solid var(--line); }
        .nav-inner { height: 56px; display: flex; align-items: center; justify-content: space-between; max-width: 1680px; margin: 0 auto; padding: 0 20px; }
        .brand { display: flex; align-items: center; gap: 8px; font-weight: 900; font-size: 12px; letter-spacing: .16em; text-transform: uppercase; }
        .brand img { width: 28px; height: 28px; object-fit: contain; }
        .nav-r { display: flex; align-items: center; gap: 16px; }
        .nav-links { display: none; gap: 18px; font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--mid); }
        .nav-links a:hover { color: var(--ink); }
        .cart-icon { position: relative; width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: var(--ink); transition: background .15s; }
        .cart-icon:hover { background: var(--soft); }
        .cart-badge { position: absolute; top: -2px; right: -2px; width: 16px; height: 16px; border-radius: 50%; background: var(--ink); color: var(--white); font-size: 9px; font-weight: 800; display: grid; place-items: center; border: 2px solid var(--white); }

        /* PAGE HEADER */
        .desktop-breadcrumb { display: none; font-size: 12px; color: var(--mid); font-weight: 500; padding: 18px 0 0; align-items: center; gap: 6px; }
        .desktop-breadcrumb a:hover { color: var(--ink); }
        .page-head { padding: 16px 0 12px; border-bottom: 1px solid var(--line); margin-bottom: 18px; display: flex; align-items: baseline; justify-content: space-between; }
        .page-title { font-size: 15px; font-weight: 900; }
        .page-count { font-size: 11px; color: var(--mid); margin-top: 2px; }
        .back-link { font-size: 10px; font-weight: 800; color: var(--mid); }
        .back-link:hover { color: var(--ink); }
        .cart-check { width: 18px; height: 18px; border-radius: 5px; accent-color: var(--ink); flex-shrink: 0; margin-top: 25px; cursor: pointer; }

        /* EMPTY */
        .empty { text-align: center; padding: 60px 0; }
        .empty-icon { width: 56px; height: 56px; border-radius: 50%; background: var(--soft); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
        .empty-title { font-size: 16px; font-weight: 800; margin-bottom: 6px; }
        .empty-sub { font-size: 13px; color: var(--mid); margin-bottom: 24px; }
        .btn-shop { height: 46px; padding: 0 28px; border-radius: 14px; background: var(--ink); color: var(--white); font-size: 13px; font-weight: 800; display: inline-flex; align-items: center; gap: 7px; }

        /* CART ITEMS */
        .cart-list { display: flex; flex-direction: column; gap: 1px; background: var(--line); border: 1px solid var(--line); border-radius: 16px; overflow: hidden; margin-bottom: 20px; }
        .cart-item { background: var(--white); display: flex; align-items: center; gap: 14px; padding: 14px 16px; }
        .ci-img { width: 68px; height: 68px; border-radius: 10px; overflow: hidden; flex-shrink: 0; background: var(--soft); }
        .ci-img img { width: 100%; height: 100%; object-fit: cover; }
        .ci-info { flex: 1; min-width: 0; }
        .ci-name { font-size: 13px; font-weight: 700; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ci-size { font-size: 11px; color: var(--mid); margin-top: 3px; font-weight: 600; }
        .ci-price { font-size: 13px; font-weight: 800; margin-top: 6px; }
        .ci-right { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex-shrink: 0; }
        .qty-ctrl { display: flex; align-items: center; gap: 6px; background: var(--soft); border-radius: 999px; padding: 4px 6px; }
        .qty-btn { width: 26px; height: 26px; border-radius: 50%; border: none; background: var(--white); cursor: pointer; font-size: 14px; font-weight: 700; display: grid; place-items: center; color: var(--ink); font-family: inherit; transition: background .15s; }
        .qty-btn:hover { background: var(--line); }
        .qty-val { font-size: 13px; font-weight: 800; min-width: 20px; text-align: center; }
        .ci-remove { font-size: 11px; color: var(--mid); background: none; border: none; cursor: pointer; font-family: inherit; font-weight: 600; padding: 2px 0; }
        .ci-remove:hover { color: #c00; }

        /* SUMMARY */
        .summary { background: var(--soft); border-radius: 16px; padding: 20px; margin-bottom: 24px; }
        .sum-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; font-weight: 600; color: var(--mid); margin-bottom: 10px; }
        .sum-total { display: flex; justify-content: space-between; align-items: center; font-size: 18px; font-weight: 900; border-top: 1px solid var(--line); padding-top: 14px; }

        /* CHECKOUT BUTTON (inline, desktop) */
        .checkout-inline { width: 100%; justify-content: center; margin-bottom: 28px; }
        .checkout-btn { height: 46px; min-width: 146px; padding: 0 22px; border-radius: 14px; background: var(--ink); color: var(--white); border: none; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 900; font-family: inherit; box-shadow: 0 10px 24px rgba(0,0,0,.16); cursor: pointer; }
        .checkout-btn.disabled { opacity: .45; pointer-events: none; box-shadow: none; }

        /* CHECKOUT BAR (sticky bottom, mobile) */
        .checkout-bar { display: none; }
        .checkout-mini { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .checkout-label { font-size: 10px; color: var(--mid); font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .checkout-total { font-size: 16px; font-weight: 900; white-space: nowrap; }

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
        .sf-copy, .sf-love { font-size: 11px; color: rgba(255,255,255,.3); }

        @media (min-width: 720px) {
            .nav-inner { padding: 0 32px; }
            .nav-links { display: flex; }
            .desktop-breadcrumb { display: flex; }
            .foot { display: none; }
            .site-footer { display: block; }
        }
        @media (max-width: 719px) {
            body.has-checkout-bar { padding-bottom: calc(82px + var(--safe)); }
            .wrap { width: min(520px, calc(100% - 28px)); }
            .nav-inner { height: 54px; }
            .page-head { padding: 14px 0 10px; margin-bottom: 16px; }
            .cart-list { margin-bottom: 24px; border-radius: 18px; }
            .cart-item { padding: 16px 14px; gap: 12px; align-items: flex-start; }
            .ci-img { width: 72px; height: 72px; border-radius: 12px; }
            .ci-name { white-space: normal; line-height: 1.32; }
            .summary { padding: 22px; margin-bottom: 26px; border-radius: 18px; }
            body.has-checkout-bar .summary { display: none; }
            .checkout-inline { display: none; }
            .checkout-bar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 120; display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 14px calc(12px + var(--safe)); background: rgba(255,255,255,.97); backdrop-filter: blur(14px); border-top: 1px solid var(--line); box-shadow: 0 -12px 30px rgba(0,0,0,.08); }
            .foot { display: none; }
        }
    </style>
</head>
<body class="{{ !empty($cart) ? 'has-checkout-bar' : '' }}">

<header class="nav">
    <div class="nav-inner">
        <a href="{{ route('storefront.home') }}" class="brand">
            <img src="{{ asset('images/logo-mark.svg') }}" alt="Greatfit">
            <span>Greatfit</span>
        </a>
        <div class="nav-r">
            <nav class="nav-links">
                <a href="{{ route('storefront.products') }}">Produk</a>
                <a href="{{ route('storefront.home') }}#beli">Beli</a>
            </nav>
            @php $cartCount = array_sum(array_column(session('cart', []), 'qty')); @endphp
            <a href="{{ route('storefront.cart') }}" class="cart-icon" title="Keranjang">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                @if($cartCount > 0)<span class="cart-badge">{{ $cartCount }}</span>@endif
            </a>
        </div>
    </div>
</header>

<main>
<div class="wrap">

    <div class="desktop-breadcrumb">
        <a href="{{ route('storefront.home') }}">Home</a>
        <span>/</span>
        <span>Keranjang</span>
    </div>

    <div class="page-head">
        <div>
            <div class="page-title">Keranjang</div>
            @if(count($cart) > 0)
            <div class="page-count">{{ array_sum(array_column($cart, 'qty')) }} item</div>
            @endif
        </div>
        <a href="{{ route('storefront.products') }}" class="back-link">← Lanjut Belanja</a>
    </div>

    @if(empty($cart))
    <div class="empty">
        <div class="empty-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        </div>
        <div class="empty-title">Keranjang masih kosong</div>
        <div class="empty-sub">Tambah produk untuk mulai berbelanja</div>
        <a href="{{ route('storefront.products') }}" class="btn-shop">
            Lihat Produk
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
    </div>
    @else
    @php $itemCount = array_sum(array_column($cart, 'qty')); @endphp

    <div class="cart-list">
        @foreach ($cart as $key => $item)
        <div class="cart-item">
            <input type="checkbox" class="cart-check" value="{{ $key }}" data-line-total="{{ $item['price'] * $item['qty'] }}" checked aria-label="Pilih {{ $item['name'] }}">
            <a href="{{ route('storefront.product_detail', $item['slug']) }}" class="ci-img">
                <img src="{{ storefront_img($item['img']) }}" alt="{{ $item['name'] }}" loading="lazy">
            </a>
            <div class="ci-info">
                <div class="ci-name">{{ $item['name'] }}</div>
                <div class="ci-size">@if(!empty($item['color'])){{ $item['color'] }} · @endif Ukuran {{ $item['size'] }}</div>
                <div class="ci-price">Rp{{ number_format($item['price'], 0, ',', '.') }}</div>
            </div>
            <div class="ci-right">
                <div class="qty-ctrl">
                    <form action="{{ route('storefront.cart.update') }}" method="POST" style="display:contents;">
                        @csrf
                        <input type="hidden" name="key" value="{{ $key }}">
                        <input type="hidden" name="qty" value="{{ $item['qty'] - 1 }}">
                        <button type="submit" class="qty-btn">−</button>
                    </form>
                    <span class="qty-val">{{ $item['qty'] }}</span>
                    <form action="{{ route('storefront.cart.update') }}" method="POST" style="display:contents;">
                        @csrf
                        <input type="hidden" name="key" value="{{ $key }}">
                        <input type="hidden" name="qty" value="{{ $item['qty'] + 1 }}">
                        <button type="submit" class="qty-btn">+</button>
                    </form>
                </div>
                <form action="{{ route('storefront.cart.remove') }}" method="POST" style="display:contents;">
                    @csrf
                    <input type="hidden" name="key" value="{{ $key }}">
                    <button type="submit" class="ci-remove">Hapus</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    <div class="summary">
        <div class="sum-row">
            <span>Total Produk ({{ $itemCount }} item)</span>
            <span>Rp{{ number_format($total, 0, ',', '.') }}</span>
        </div>
        <div class="sum-total">
            <span>Total</span>
            <span class="js-cart-total">Rp{{ number_format($total, 0, ',', '.') }}</span>
        </div>
    </div>

    <a href="{{ route('storefront.checkout') }}" class="btn-shop checkout-inline checkout-link" data-base-url="{{ route('storefront.checkout') }}">
        Checkout
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </a>
    @endif

    <footer class="foot">
        <div class="foot-brand">
            <img src="{{ asset('images/logo-mark.svg') }}" alt="Greatfit">
            <span class="foot-name">Greatfit</span>
        </div>
        <div class="foot-tagline">Pakaian olahraga nyaman untuk aktivitas harian.</div>
        <nav class="foot-links">
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

@if(!empty($cart))
<div class="checkout-bar">
    <div class="checkout-mini">
        <span class="checkout-label">Total</span>
        <span class="checkout-total js-cart-total">Rp{{ number_format($total, 0, ',', '.') }}</span>
    </div>
    <a href="{{ route('storefront.checkout') }}" class="checkout-btn checkout-link" data-base-url="{{ route('storefront.checkout') }}">Checkout</a>
</div>
@endif

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
                    @foreach (collect($products ?? [])->take(3) as $p)
                    <a href="{{ route('storefront.product_detail', $p['slug']) }}">{{ $p['name'] }}</a>
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

<script>
(function() {
    var checks = Array.prototype.slice.call(document.querySelectorAll('.cart-check'));
    if (!checks.length) return;

    var links    = Array.prototype.slice.call(document.querySelectorAll('.checkout-link'));
    var totalEls = Array.prototype.slice.call(document.querySelectorAll('.js-cart-total'));

    function rupiah(v) { return 'Rp' + v.toLocaleString('id-ID'); }

    function sync() {
        var selected = checks.filter(function(c) { return c.checked; });
        var total    = selected.reduce(function(s, c) { return s + Number(c.dataset.lineTotal || 0); }, 0);

        totalEls.forEach(function(el) { el.textContent = rupiah(total); });

        links.forEach(function(link) {
            var base = link.dataset.baseUrl || link.getAttribute('href');
            if (!selected.length) {
                link.setAttribute('href', '#');
                link.classList.add('disabled');
                return;
            }
            var q = selected.map(function(c) { return 'items[]=' + encodeURIComponent(c.value); }).join('&');
            link.setAttribute('href', base + '?' + q);
            link.classList.remove('disabled');
        });
    }

    checks.forEach(function(c) { c.addEventListener('change', sync); });
    sync();
})();
</script>

@include('storefront._tracker')
@include('storefront._mobile_zoom_lock')

</body>
</html>
