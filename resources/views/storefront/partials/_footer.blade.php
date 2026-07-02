@php
    $sfCategories  = \App\Models\StorefrontProductCategory::where('is_active', true)
        ->orderBy('sort_order')->orderBy('name')->take(6)->get();
    $sfBrandName   = storefront_setting('branding.brand_name', 'Greatfit');
    $sfLogoUrl     = storefront_setting('branding.logo_url', '/images/logo-mark.svg');
    $sfLogoSrc     = str_starts_with($sfLogoUrl, 'http') ? $sfLogoUrl : asset(ltrim($sfLogoUrl, '/'));
    $sfFootTagline = storefront_setting('footer.tagline', 'Hal kecil yang bikin hari terasa lebih nyaman, lewat outfit harian Greatfit.');
    $sfCopyright   = storefront_setting('footer.copyright', '© ' . date('Y') . ' ' . $sfBrandName . '. All rights reserved.');
    $sfMadeIn      = storefront_setting('footer.made_in', 'Made with care in Indonesia');
    $sfIgUrl       = storefront_setting('footer.instagram_url', '#');
@endphp

{{-- Mobile simple footer ──────────────────────────────────────────────── --}}
<footer class="foot">
    <div class="wrap">
        <div class="foot-brand">
            <img src="{{ $sfLogoSrc }}" alt="{{ $sfBrandName }}">
            <span class="foot-name">{{ $sfBrandName }}</span>
        </div>
        <div class="foot-tagline">{{ $sfFootTagline }}</div>
        <nav class="foot-links" aria-label="Footer mobile">
            <a href="{{ route('storefront.products') }}">Produk</a>
            <a href="{{ route('storefront.cart') }}">Keranjang</a>
        </nav>
        <div class="foot-bottom">
            <span>{{ $sfCopyright }}</span>
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
                    <img src="{{ $sfLogoSrc }}" alt="{{ $sfBrandName }}">
                    <span class="sf-brand-name">{{ $sfBrandName }}</span>
                </div>
                <div class="sf-tagline">{{ $sfFootTagline }}</div>
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
            <span class="sf-copy">{{ $sfCopyright }}</span>
            <span class="sf-love">{{ $sfMadeIn }}</span>
        </div>
    </div>
</footer>
