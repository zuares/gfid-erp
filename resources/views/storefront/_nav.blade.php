@php
    $navActive    = $navActive ?? '';
    $cartCount    = array_sum(array_column(session('cart', []), 'qty'));
    $customerId   = session('storefront_customer_id');
    $isLoggedIn   = !empty($customerId);
    $custName     = '';
    $custInitial  = '?';
    $custPhone    = '';
    if ($isLoggedIn) {
        $navCustomer = \App\Models\StorefrontCustomer::find($customerId);
        if ($navCustomer) {
            $custName    = $navCustomer->name;
            $custInitial = $navCustomer->initial;
            $custPhone   = $navCustomer->phone_display;
        }
    }
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
            </nav>

            <a href="{{ route('storefront.cart') }}" class="cart-icon cart-wrap @if(session('cart_added')) cart-pop @endif" title="Keranjang">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                @if($cartCount > 0)<span class="cart-badge">{{ $cartCount }}</span>@endif
            </a>

            @if(!$isLoggedIn)
            {{-- TOMBOL MASUK (belum login) --}}
            <a href="{{ route('storefront.login') }}" class="nav-btn-masuk {{ $navActive === 'login' ? 'active' : '' }}">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Masuk
            </a>
            @else
            {{-- USER MENU (sudah login) --}}
            <div class="user-menu-wrap" id="userMenuWrap">
                <button class="user-chip" id="userChipBtn" type="button" aria-expanded="false">
                    <span class="user-avatar">{{ $custInitial }}</span>
                    <span class="user-chip-name">{{ $custName ? explode(' ', $custName)[0] : 'Akun' }}</span>
                    <svg class="user-chevron" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <div class="user-dropdown-head">
                        <div class="ud-name">{{ $custName ?: 'Pelanggan' }}</div>
                        <div class="ud-phone">{{ $custPhone }}</div>
                    </div>
                    <a href="{{ route('storefront.user') }}" class="ud-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Profil Saya
                    </a>
                    <a href="{{ route('storefront.user.orders') }}" class="ud-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                        Pesanan Saya
                    </a>
                    <div class="ud-sep"></div>
                    <form method="POST" action="{{ route('storefront.logout') }}">
                        @csrf
                        <button type="submit" class="ud-item ud-keluar">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</header>
