@php
    $sfCategories = \App\Models\StorefrontProductCategory::where('is_active', true)
        ->orderBy('sort_order')->orderBy('name')->take(6)->get();
@endphp

{{-- Mobile simple footer ──────────────────────────────────────────────── --}}
<footer class="foot">
    <div class="wrap">
        <div class="foot-brand">
            <img src="{{ asset('images/logo-mark.svg') }}" alt="Greatfit">
            <span class="foot-name">Greatfit</span>
        </div>
        <div class="foot-tagline">Hal kecil yang bikin hari terasa lebih nyaman, lewat outfit harian Greatfit.</div>
        <nav class="foot-links" aria-label="Footer mobile">
            <a href="{{ route('storefront.products') }}">Produk</a>
            <a href="{{ route('storefront.cart') }}">Keranjang</a>
        </nav>
        <div class="foot-bottom">
            <span>© {{ date('Y') }} Greatfit</span>
            <a href="{{ route('login', [], false) }}">Admin</a>
        </div>
    </div>
</footer>

{{-- Desktop full footer ───────────────────────────────────────────────── --}}
<footer class="site-footer">
    <div class="site-footer-inner">
        <div class="sf-top">
            <div>
                <div class="sf-brand">
                    <img src="{{ asset('images/logo-mark.svg') }}" alt="Greatfit">
                    <span class="sf-brand-name">Greatfit</span>
                </div>
                <div class="sf-tagline">Hal kecil yang bikin hari terasa lebih nyaman,<br>lewat outfit harian Greatfit.</div>
            </div>
            <nav class="sf-nav">
                @if($sfCategories->isNotEmpty())
                <div class="sf-col">
                    <h4>Koleksi</h4>
                    <a href="{{ route('storefront.products') }}">Semua Produk</a>
                    @foreach($sfCategories as $cat)
                    <a href="{{ route('storefront.products', ['kategori' => $cat->slug]) }}">{{ $cat->name }}</a>
                    @endforeach
                </div>
                @endif
                <div class="sf-col">
                    <h4>Toko</h4>
                    <a href="{{ route('storefront.home') }}">Home</a>
                    <a href="{{ route('storefront.cart') }}">Keranjang</a>
                    <a href="{{ route('storefront.user') }}">Profil</a>
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
