@extends('storefront.layouts.app')

@section('title', 'Keranjang — Greatfit')

@section('footer')@endsection

@push('styles')
<style>
    main { flex: 1 0 auto; display: flex; flex-direction: column; }
    main > .wrap { flex: 1 0 auto; display: flex; flex-direction: column; }
    .wrap { width: min(760px, calc(100% - 32px)); margin: 0 auto; }

    .desktop-breadcrumb { display: none; font-size: 12px; color: var(--mid); font-weight: 500; padding: 18px 0 0; align-items: center; gap: 6px; }
    .desktop-breadcrumb a:hover { color: var(--ink); }
    .page-head { padding: 16px 0 12px; border-bottom: 1px solid var(--line); margin-bottom: 18px; display: flex; align-items: baseline; justify-content: space-between; }
    .page-title { font-size: 15px; font-weight: 900; }
    .page-count { font-size: 11px; color: var(--mid); margin-top: 2px; }
    .back-link { font-size: 10px; font-weight: 800; color: var(--mid); }
    .back-link:hover { color: var(--ink); }
    .cart-check { width: 18px; height: 18px; border-radius: 5px; accent-color: var(--ink); flex-shrink: 0; margin-top: 25px; cursor: pointer; }

    .empty { text-align: center; padding: 60px 0; }
    .empty-icon { width: 56px; height: 56px; border-radius: 50%; background: var(--soft); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
    .empty-title { font-size: 16px; font-weight: 800; margin-bottom: 6px; }
    .empty-sub { font-size: 13px; color: var(--mid); margin-bottom: 24px; }
    .btn-shop { height: 46px; padding: 0 28px; border-radius: 14px; background: var(--ink); color: var(--white); font-size: 13px; font-weight: 800; display: inline-flex; align-items: center; gap: 7px; }

    .cart-list { display: flex; flex-direction: column; gap: 1px; background: var(--line); border: 1px solid var(--line); border-radius: 16px; overflow: hidden; margin-bottom: 20px; }
    .cart-item { background: var(--white); display: flex; align-items: center; gap: 14px; padding: 14px 16px; }
    .ci-img { width: 68px; height: 68px; border-radius: 10px; overflow: hidden; flex-shrink: 0; background: var(--soft); }
    .ci-img img { width: 100%; height: 100%; object-fit: cover; }
    .ci-info { flex: 1; min-width: 0; }
    .ci-name { font-size: 13px; font-weight: 700; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ci-size { font-size: 11px; color: var(--mid); margin-top: 3px; font-weight: 600; }
    .ci-stock { font-size: 10px; color: var(--mid); margin-top: 5px; font-weight: 800; }
    .ci-stock.low { color: #f97316; }
    .ci-stock.out { color: #b91c1c; }
    .ci-price { font-size: 13px; font-weight: 800; margin-top: 6px; }
    .ci-right { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex-shrink: 0; }
    .qty-ctrl { display: flex; align-items: center; gap: 6px; background: var(--soft); border-radius: 999px; padding: 4px 6px; }
    .qty-btn { width: 26px; height: 26px; border-radius: 50%; border: none; background: var(--white); cursor: pointer; font-size: 14px; font-weight: 700; display: grid; place-items: center; color: var(--ink); font-family: inherit; transition: background .15s; }
    .qty-btn:hover { background: var(--line); }
    .qty-btn[disabled] { opacity: .35; cursor: not-allowed; }
    .qty-val { font-size: 13px; font-weight: 800; min-width: 20px; text-align: center; }
    .ci-remove { font-size: 11px; color: var(--mid); background: none; border: none; cursor: pointer; font-family: inherit; font-weight: 600; padding: 2px 0; }
    .ci-remove:hover { color: #c00; }

    .summary { background: var(--soft); border-radius: 16px; padding: 20px; margin-bottom: 24px; }
    .sum-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; font-weight: 600; color: var(--mid); margin-bottom: 10px; }
    .sum-total { display: flex; justify-content: space-between; align-items: center; font-size: 18px; font-weight: 900; border-top: 1px solid var(--line); padding-top: 14px; }

    .checkout-inline { width: 100%; justify-content: center; margin-bottom: 28px; }
    .checkout-btn { height: 46px; min-width: 146px; padding: 0 22px; border-radius: 14px; background: var(--ink); color: var(--white); border: none; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 900; font-family: inherit; box-shadow: 0 10px 24px rgba(0,0,0,.16); cursor: pointer; }
    .checkout-btn.disabled { opacity: .45; pointer-events: none; box-shadow: none; }

    .checkout-bar { display: none; }
    .checkout-mini { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
    .checkout-label { font-size: 10px; color: var(--mid); font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .checkout-total { font-size: 16px; font-weight: 900; white-space: nowrap; }

    .cart-foot { border-top: 1px solid var(--line); margin-top: auto; padding: 20px 0 calc(18px + var(--safe)); }
    .cart-alert { background: #fff7ed; color: #9a3412; border: 1px solid #fed7aa; border-radius: 14px; padding: 11px 13px; font-size: 12px; font-weight: 750; margin-bottom: 12px; }

    @@media (min-width: 720px) {
        .desktop-breadcrumb { display: flex; }
    }
    @@media (max-width: 719px) {
        body.has-checkout-bar { padding-bottom: calc(82px + var(--safe)); }
        .wrap { width: min(520px, calc(100% - 28px)); }
        .page-head { padding: 14px 0 10px; margin-bottom: 16px; }
        .cart-list { margin-bottom: 24px; border-radius: 18px; }
        .cart-item { padding: 16px 14px; gap: 12px; align-items: flex-start; }
        .ci-img { width: 72px; height: 72px; border-radius: 12px; }
        .ci-name { white-space: normal; line-height: 1.32; }
        .summary { padding: 22px; margin-bottom: 26px; border-radius: 18px; }
        body.has-checkout-bar .summary { display: none; }
        .checkout-inline { display: none; }
        .checkout-bar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 120; display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 14px calc(12px + var(--safe)); background: rgba(255,255,255,.97); backdrop-filter: blur(14px); border-top: 1px solid var(--line); box-shadow: 0 -12px 30px rgba(0,0,0,.08); }
    }
</style>
@endpush

@php $navActive = 'cart'; @endphp

@section('footer')@endsection

@section('content')
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
    @if(session('cart_error'))
    <div class="cart-alert">{{ session('cart_error') }}</div>
    @endif

    <div class="cart-list">
        @foreach ($cart as $key => $item)
        @php
            $stock = $cartStock[$key] ?? ['available' => null, 'ok' => true, 'low' => false];
            $available = $stock['available'];
            $isOut = $available !== null && $available <= 0;
            $isOver = !($stock['ok'] ?? true);
            $isLow = $stock['low'] ?? false;
        @endphp
        <div class="cart-item">
            <input type="checkbox" class="cart-check" value="{{ $key }}" data-line-total="{{ $item['price'] * $item['qty'] }}" {{ $isOut || $isOver ? '' : 'checked' }} {{ $isOut ? 'disabled' : '' }} aria-label="Pilih {{ $item['name'] }}">
            <a href="{{ route('storefront.product_detail', $item['slug']) }}" class="ci-img">
                <img src="{{ storefront_img($item['img']) }}" alt="{{ $item['name'] }}" loading="lazy">
            </a>
            <div class="ci-info">
                <div class="ci-name">{{ $item['name'] }}</div>
                <div class="ci-size">@if(!empty($item['color'])){{ $item['color'] }} · @endif Ukuran {{ $item['size'] }}</div>
                @if($available !== null)
                <div class="ci-stock {{ $isOut || $isOver ? 'out' : ($isLow ? 'low' : '') }}">
                    @if($isOut)
                        Stok sedang kosong
                    @elseif($isOver)
                        Stok tersisa {{ $available }} pcs, kurangi jumlah
                    @elseif($isLow)
                        Stok tersisa {{ $available }} pcs
                    @else
                        Stok tersedia
                    @endif
                </div>
                @endif
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
                        <button type="submit" class="qty-btn" {{ $available !== null && $item['qty'] >= $available ? 'disabled' : '' }}>+</button>
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

</div>

{{-- Sticky checkout bar (mobile) — position:fixed so DOM position doesn't matter --}}
@if(!empty($cart))
<div class="checkout-bar">
    <div class="checkout-mini">
        <span class="checkout-label">Total</span>
        <span class="checkout-total js-cart-total">Rp{{ number_format($total, 0, ',', '.') }}</span>
    </div>
    <a href="{{ route('storefront.checkout') }}" class="checkout-btn checkout-link" data-base-url="{{ route('storefront.checkout') }}">Checkout</a>
</div>
@endif
@endsection

@if(!empty($cart))
@push('scripts')
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
            if (!selected.length) { link.setAttribute('href', '#'); link.classList.add('disabled'); return; }
            var q = selected.map(function(c) { return 'items[]=' + encodeURIComponent(c.value); }).join('&');
            link.setAttribute('href', base + '?' + q);
            link.classList.remove('disabled');
        });
    }
    checks.forEach(function(c) { c.addEventListener('change', sync); });
    sync();
})();
</script>
@endpush
@endif

@if(!empty($cart))
@section('body-class', 'has-checkout-bar')
@endif
