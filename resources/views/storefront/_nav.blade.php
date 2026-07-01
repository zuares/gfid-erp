@php
    $navActive = $navActive ?? '';
    $cartCount = array_sum(array_column(session('cart', []), 'qty'));
@endphp

<header class="nav">
    <div class="nav-inner">
        <a href="{{ route('storefront.home') }}" class="brand">
            <img src="{{ asset('images/logo-mark.svg') }}" alt="Greatfit">
            <span>Greatfit</span>
        </a>
        <div class="nav-r">
            <nav class="nav-links">
                <a href="{{ route('storefront.products') }}" class="{{ $navActive === 'products' ? 'active' : '' }}">Produk</a>
                <a href="{{ route('storefront.home') }}#beli" class="{{ $navActive === 'buy' ? 'active' : '' }}">Beli</a>
            </nav>
            <a href="#" class="cart-icon" title="Cari" onclick="return false;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            </a>
            <a href="{{ route('storefront.cart') }}" class="cart-icon cart-wrap @if(session('cart_added')) cart-pop @endif" title="Keranjang">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                @if($cartCount > 0)<span class="cart-badge">{{ $cartCount }}</span>@endif
            </a>
        </div>
    </div>
</header>
