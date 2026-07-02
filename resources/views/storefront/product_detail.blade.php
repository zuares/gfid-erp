@extends('storefront.layouts.app')

@section('title', $product['name'] . ' — Greatfit')

@push('styles')
<style>
    :root { --bg: #f2f2f2; --red: #e53935; }
    html { scroll-behavior: smooth; }
    body { background: var(--bg); padding-bottom: calc(72px + var(--safe)); }

    /* TOAST / FLY */
    .cart-fly { position: fixed; top: 50%; left: 50%; z-index: 499; width: 18px; height: 18px; border-radius: 50%; background: var(--ink); pointer-events: none; transform: translate(-50%, -50%); opacity: 0; animation: flyToCart .75s cubic-bezier(.2,.7,.2,1) forwards; }
    .cart-toast { position: fixed; top: 68px; right: 16px; z-index: 500; display: flex; align-items: center; gap: 10px; max-width: min(320px, calc(100vw - 32px)); padding: 11px 13px; border-radius: 14px; background: var(--ink); color: var(--white); box-shadow: 0 16px 40px rgba(0,0,0,.18); font-size: 12px; font-weight: 800; transform: translateY(-10px); opacity: 0; pointer-events: none; animation: toastIn 2.4s ease forwards; }
    .cart-toast-icon { width: 26px; height: 26px; border-radius: 50%; background: rgba(255,255,255,.14); display: grid; place-items: center; flex-shrink: 0; }
    @@keyframes flyToCart { 0% { opacity: 0; transform: translate(-50%, -50%) scale(.7); } 18% { opacity: 1; } 100% { opacity: 0; transform: translate(calc(50vw - 42px), calc(-50vh + 23px)) scale(.25); } }
    @@keyframes toastIn { 0% { opacity: 0; transform: translateY(-10px); } 12%, 78% { opacity: 1; transform: translateY(0); } 100% { opacity: 0; transform: translateY(-8px); } }

    /* BREADCRUMB */
    .breadcrumb-bar { background: var(--white); border-bottom: 1px solid var(--line); padding: 9px 16px; }
    .breadcrumb { font-size: 11px; color: var(--mid); display: flex; align-items: center; gap: 5px; font-weight: 500; max-width: 1680px; margin: 0 auto; }
    .breadcrumb a:hover { color: var(--ink); }
    .mobile-back { font-size: 12px; color: var(--mid); font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }
    .mobile-back:hover { color: var(--ink); }

    /* SECTION CARDS */
    .section { background: var(--white); margin-bottom: 8px; padding: 16px; }

    /* IMAGE */
    .img-section { background: var(--white); margin-bottom: 8px; position: relative; overflow: hidden; }
    .img-section img { width: 100%; aspect-ratio: 1; object-fit: cover; display: block; }
    .img-badge { position: absolute; top: 14px; left: 14px; z-index: 2; background: var(--ink); color: var(--white); font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: var(--radius-pill); letter-spacing: .04em; }

    /* PRICE CARD */
    .price-section { padding: 14px 16px 16px; }
    .price-row { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 8px; }
    .product-price { font-size: 26px; font-weight: 900; letter-spacing: -.03em; line-height: 1; }
    .product-sold { font-size: 11px; color: var(--mid); font-weight: 500; }
    .product-name { font-size: 15px; font-weight: 700; line-height: 1.4; color: #222; }

    /* VARIANT TAP ROW (mobile) */
    .variant-tap { display: flex; align-items: center; justify-content: space-between; padding: 13px 16px; cursor: pointer; }
    .vt-left { display: flex; flex-direction: column; gap: 2px; }
    .vt-label { font-size: 10px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--mid); }
    .vt-value { font-size: 13px; font-weight: 700; color: var(--ink); }
    .vt-hint { font-size: 13px; font-weight: 500; color: var(--mid); font-style: italic; }
    .vt-arrow { color: var(--mid); flex-shrink: 0; }

    /* VARIANT SECTION (desktop inline) */
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
    .color-btn.color-option { width: auto; height: 38px; min-width: 0; border-radius: var(--radius-pill); padding: 0 12px 0 8px; display: inline-flex; align-items: center; gap: 8px; background: var(--white); border: 1.5px solid var(--line); box-shadow: none; font-family: var(--font-body); font-size: 12px; font-weight: 800; color: var(--ink); }
    .color-btn.color-option:hover { transform: none; border-color: #bbb; }
    .color-btn.color-option.active { background: var(--ink); border-color: var(--ink); color: var(--white); box-shadow: none; }
    .color-dot { width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0; box-shadow: inset 0 0 0 1px rgba(0,0,0,.12); }
    .color-dot.light { box-shadow: inset 0 0 0 1.5px #bbb; }

    /* Size chips */
    .sizes { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 20px; }
    .size-btn { min-width: 46px; height: 36px; padding: 0 10px; border-radius: 8px; border: 1.5px solid var(--line); background: var(--white); font-size: 12px; font-weight: 800; cursor: pointer; display: grid; place-items: center; transition: all .12s; font-family: var(--font-body); color: var(--ink); }
    .size-btn:hover { border-color: #bbb; }
    .size-btn.active { border-color: var(--ink); background: var(--ink); color: var(--white); }

    /* Qty */
    .qty-row { display: flex; align-items: center; justify-content: space-between; }
    .qty-label { font-size: 11px; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--mid); }
    .qty-ctrl { display: inline-flex; align-items: center; border: 1.5px solid var(--line); border-radius: 8px; overflow: hidden; }
    .qty-btn { width: 34px; height: 34px; border: none; background: transparent; cursor: pointer; font-size: 16px; display: grid; place-items: center; color: var(--ink); font-family: var(--font-body); transition: background .1s; user-select: none; }
    .qty-btn:hover { background: var(--soft); }
    .qty-num { min-width: 36px; text-align: center; font-size: 13px; font-weight: 800; border-left: 1.5px solid var(--line); border-right: 1.5px solid var(--line); line-height: 34px; }
    .pick-hint { font-size: 11px; color: var(--red); font-weight: 600; margin-top: 12px; display: none; }
    .stock-note { font-size: 11px; color: var(--mid); font-weight: 800; margin-top: 12px; }
    .stock-note.low { color: #f97316; }
    .stock-note.out { color: var(--red); }

    /* Desktop action buttons */
    .btn-row { display: flex; gap: 8px; margin-top: 20px; }
    .btn-atc { flex: 1; height: 48px; border-radius: 12px; background: #f0f0f0; color: var(--ink); border: 1.5px solid var(--line); font-size: 13px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px; font-family: var(--font-body); transition: background .12s; }
    .btn-atc:hover { background: var(--line); }
    .btn-now { flex: 1.2; height: 48px; border-radius: 12px; background: var(--ink); color: var(--white); border: none; font-size: 13px; font-weight: 800; cursor: pointer; font-family: var(--font-body); transition: opacity .12s; }
    .btn-now:hover { opacity: .85; }

    /* DESCRIPTION */
    .desc-section { padding: 14px 16px; }
    .section-title { font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--mid); margin-bottom: 10px; }
    .desc-text { font-size: 13px; color: #555; line-height: 1.75; font-weight: 500; }

    /* RELATED (mobile) */
    .related-section { padding: 14px 16px 16px; }
    .related-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-top: 12px; }
    .rc { border-radius: 10px; overflow: hidden; background: var(--soft); border: 1px solid var(--line); display: block; transition: opacity .15s; }
    .rc:hover { opacity: .8; }
    .rc img { width: 100%; aspect-ratio: 1; object-fit: cover; }
    .rc-body { padding: 9px 10px; }
    .rc-name { font-size: 12px; font-weight: 700; line-height: 1.3; color: #333; }
    .rc-price { font-size: 14px; font-weight: 900; margin-top: 3px; }

    /* RELATED (desktop) */
    .related-full { background: var(--white); margin-top: 8px; padding: 20px 16px 24px; display: none; }
    .related-full .section-title { margin-bottom: 16px; }
    .related-full-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }

    /* STICKY BAR (mobile) */
    .bar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 150; background: var(--white); border-top: 1px solid var(--line); padding: 10px 16px calc(10px + var(--safe)); }
    .bar-inner { display: flex; align-items: center; gap: 8px; max-width: 600px; margin: 0 auto; }
    .bar-cart { flex: 1; height: 46px; border-radius: 12px; background: var(--soft); color: var(--ink); border: 1.5px solid var(--line); font-size: 13px; font-weight: 800; cursor: pointer; font-family: var(--font-body); display: flex; align-items: center; justify-content: center; gap: 6px; }
    .bar-now { flex: 1.4; height: 46px; border-radius: 12px; background: var(--ink); color: var(--white); border: none; font-size: 13px; font-weight: 800; cursor: pointer; font-family: var(--font-body); }

    /* MODAL / BOTTOM SHEET */
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
    .sheet-action { width: 100%; height: 50px; border-radius: 14px; border: none; font-size: 14px; font-weight: 800; cursor: pointer; font-family: var(--font-body); transition: opacity .12s; background: var(--ink); color: var(--white); }
    .sheet-action:hover { opacity: .85; }
    .sheet-hint { font-size: 11px; color: var(--red); font-weight: 600; margin-top: 8px; display: none; text-align: center; }

    @@media (min-width: 720px) {
        body { padding-bottom: 0; }
        .desktop-breadcrumb { display: flex; align-items: center; gap: 5px; font-size: 11px; color: var(--mid); font-weight: 500; padding: 16px 32px 0; }
        .desktop-breadcrumb a:hover { color: var(--ink); }
        .pd-grid { display: grid; grid-template-columns: minmax(300px, 40%) minmax(0, 1fr); gap: 28px; max-width: 1680px; margin: 0 auto; padding: 16px 32px 56px; align-items: start; width: 100%; }
        .pd-left { position: sticky; top: 68px; }
        .img-section { border-radius: 18px; margin-bottom: 0; overflow: hidden; }
        .img-section img { aspect-ratio: 4/5; object-fit: cover; }
        .pd-right { display: flex; flex-direction: column; gap: 10px; }
        .price-section   { border-radius: 16px; padding: 22px 24px; margin-bottom: 0; }
        .product-price   { font-size: 28px; }
        .variant-section { border-radius: 16px; padding: 22px 24px 26px; margin-bottom: 0; }
        .desc-section    { border-radius: 16px; padding: 20px 24px; margin-bottom: 0; }
        .related-section { display: none; }
        .variant-tap     { display: none; }
        .variant-section { display: block; }
        .bar     { display: none; }
        .overlay { display: none; }
        .sheet   { display: none; }
        .breadcrumb-bar { display: none; }
        .related-full { display: block; padding: 24px 32px 28px; }
        .related-full-grid { grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .rc-name { font-size: 13px; }
        .rc-price { font-size: 15px; }
    }
    @@media (min-width: 720px) and (max-width: 1080px) {
        .desktop-breadcrumb { padding: 18px 36px 0; }
        .pd-grid { grid-template-columns: minmax(280px, 42%) minmax(0, 1fr); gap: 22px; padding: 18px 36px 48px; }
        .price-section   { padding: 20px 22px; }
        .variant-section { padding: 20px 22px 24px; }
        .desc-section    { padding: 18px 22px; }
        .related-full    { padding: 24px 36px 30px; }
    }
    @@media (max-width: 719px) {
        .desktop-breadcrumb { display: none; }
        .pd-grid { display: contents; }
        .pd-left, .pd-right { display: contents; }
        .section { border-radius: 0; }
        .variant-section { display: none; }
        .variant-tap { display: flex; }
    }
</style>
@endpush

@php $navActive = 'products'; @endphp

@section('content')
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
        @if(!empty($product['category_slug']) && !empty($product['category_name']))
        <a href="{{ route('storefront.products', ['kategori' => $product['category_slug']]) }}" class="mobile-back">
            <span>←</span><span>{{ $product['category_name'] }}</span>
        </a>
        @else
        <a href="{{ route('storefront.products') }}" class="mobile-back">
            <span>←</span><span>Semua Produk</span>
        </a>
        @endif
    </div>
</div>

{{-- BREADCRUMB desktop --}}
<div class="desktop-breadcrumb">
    <a href="{{ route('storefront.home') }}">Home</a>
    <span>/</span>
    <a href="{{ route('storefront.products') }}">Produk</a>
    @if(!empty($product['category_slug']) && !empty($product['category_name']))
    <span>/</span>
    <a href="{{ route('storefront.products', ['kategori' => $product['category_slug']]) }}">{{ $product['category_name'] }}</a>
    @endif
    <span>/</span>
    <span>{{ $product['name'] }}</span>
</div>

<div class="pd-grid">
    {{-- LEFT: IMAGE --}}
    <div class="pd-left">
        <div class="img-section">
            @if($product['label'])<span class="img-badge">{{ $product['label'] }}</span>@endif
            <img id="main-product-img" src="{{ storefront_img($product['img']) }}" alt="{{ $product['name'] }}">
        </div>
    </div>

    {{-- RIGHT: INFO CARDS --}}
    <div class="pd-right">

        {{-- PRICE CARD --}}
        <div class="section price-section">
            <div class="price-row">
                <div class="product-price" id="product-price-display">Rp{{ number_format($product['price'], 0, ',', '.') }}</div>
                @if($product['sold'])<div class="product-sold">{{ $product['sold'] }} terjual</div>@endif
            </div>
            <div class="product-name">{{ $product['name'] }}</div>
            @php
                $audColors = ['pria'=>'#1d4ed8','wanita'=>'#be185d','anak'=>'#d97706','olahraga'=>'#15803d','unisex'=>'#6b7280'];
                $audColor  = $audColors[$product['audience'] ?? ''] ?? '#6b7280';
                $isJumbo   = ($product['product_type'] ?? '') === 'jumbo';
            @endphp
            @if(!empty($product['category_name']) || !empty($product['audience_label']) || $isJumbo)
            <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">
                @if($isJumbo)
                <a href="{{ route('storefront.products', ['type' => 'jumbo']) }}"
                   style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#7c3aed;background:#7c3aed14;padding:3px 9px;border-radius:999px;">
                    ✦ Big Size
                </a>
                @endif
                @if(!empty($product['category_name']))
                <a href="{{ route('storefront.products', ['kategori' => $product['category_slug']]) }}"
                   style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--mid);border:1px solid var(--line);padding:3px 9px;border-radius:999px;">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    {{ $product['category_name'] }}
                </a>
                @endif
                @if(!empty($product['audience_label']))
                <a href="{{ route('storefront.products', ['audience' => $product['audience']]) }}"
                   style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:{{ $audColor }};background:{{ $audColor }}14;padding:3px 9px;border-radius:999px;">
                    {{ $product['audience_label'] }}
                </a>
                @endif
            </div>
            @endif
        </div>

        {{-- MOBILE: tappable row --}}
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
            <div class="stock-note" id="desk-stock-note">Pilih warna dan ukuran untuk cek stok</div>
            @if(session('cart_error'))
            <div class="pick-hint" style="display:block">{{ session('cart_error') }}</div>
            @endif

            <form action="{{ route('storefront.cart.add') }}" method="POST" id="desk-cart-form" style="display:none">
                @csrf
                <input type="hidden" name="slug"      value="{{ $slug }}">
                <input type="hidden" name="color"     id="desk-color" value="">
                <input type="hidden" name="size"      id="desk-size"  value="">
                <input type="hidden" name="qty"       id="desk-qty"   value="1">
                <input type="hidden" name="mode"      id="desk-mode"  value="cart">
                <input type="hidden" name="_sf_token" value="{{ $_sfToken ?? '' }}">
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

        {{-- RELATED (mobile) --}}
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

    </div>
</div>

{{-- DESKTOP: Full-width related --}}
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
            <img id="sheet-product-img" src="{{ storefront_img($product['img']) }}" alt="{{ $product['name'] }}">
        </div>
        <div class="sheet-meta">
            <div class="sheet-price" id="sheet-price-display">Rp{{ number_format($product['price'], 0, ',', '.') }}</div>
            <div class="sheet-name">{{ $product['name'] }}</div>
        </div>
        <button class="sheet-close" onclick="closeModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="sheet-body">
        @if(!empty($product['colors']))
        <div class="picker-head" style="margin-top:4px">Warna</div>
        <div class="colors" id="color-btns-modal">
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
        <div class="sizes" id="size-btns-modal">
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
        <div class="stock-note" id="modal-stock-note">Pilih warna dan ukuran untuk cek stok</div>
    </div>
    <div class="sheet-footer">
        <form action="{{ route('storefront.cart.add') }}" method="POST" id="modal-form">
            @csrf
            <input type="hidden" name="slug"      value="{{ $slug }}">
            <input type="hidden" name="color"     id="modal-color" value="">
            <input type="hidden" name="size"      id="modal-size"  value="">
            <input type="hidden" name="qty"       id="modal-qty"   value="1">
            <input type="hidden" name="mode"      id="modal-mode"  value="cart">
            <input type="hidden" name="_sf_token" value="{{ $_sfToken ?? '' }}">
        </form>
        <button type="button" class="sheet-action" id="sheet-action-btn" onclick="modalSubmit()">
            Beli Sekarang
        </button>
        <div class="sheet-hint" id="sheet-hint">Pilih warna dan ukuran terlebih dahulu</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var variantData = @json($product['_variants'] ?? []);
var variantItemData = @json($product['_variant_items'] ?? []);
var basePrice   = {{ $product['_base_price'] ?? $product['price'] }};
var variantMap  = {};
variantData.forEach(function(v) { variantMap[v.name] = v; });

function formatRupiah(n) { return 'Rp' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }

function getVariant(colorName, sizeLabel) {
    var exact = variantData.find(function(v) {
        return v.name === colorName && (v.size_label || '') === (sizeLabel || '');
    });
    if (exact) return exact;
    return variantData.find(function(v) {
        return v.name === colorName && !(v.size_label || '');
    }) || variantMap[colorName];
}

function selectedStock(colorName, sizeLabel) {
    var mapping = getMapping(colorName, sizeLabel);
    if (mapping) return Number(mapping.stock || 0);
    var v = getVariant(colorName, sizeLabel);
    if (!v) return null;
    return Number(v.stock || 0);
}

function getMapping(colorName, sizeLabel) {
    return variantItemData.find(function(m) {
        return String(m.color || '').toLowerCase() === String(colorName || '').toLowerCase()
            && String(m.size || '').toLowerCase() === String(sizeLabel || '').toLowerCase();
    }) || null;
}

function renderStockNote(targetId, colorName, sizeLabel, qty) {
    var el = document.getElementById(targetId);
    if (!el) return true;
    el.className = 'stock-note';

    if (!colorName || !sizeLabel) {
        el.textContent = 'Pilih warna dan ukuran untuk cek stok';
        return true;
    }

    var stock = selectedStock(colorName, sizeLabel);
    if (variantItemData.length && !getMapping(colorName, sizeLabel)) {
        el.textContent = 'Pilihan ini belum tersedia';
        el.classList.add('out');
        return false;
    }
    if (stock === null) {
        el.textContent = 'Stok belum tersedia';
        el.classList.add('out');
        return false;
    }
    if (stock <= 0) {
        el.textContent = 'Stok pilihan ini sedang kosong';
        el.classList.add('out');
        return false;
    }
    if (qty > stock) {
        el.textContent = 'Stok tersisa ' + stock + ' pcs';
        el.classList.add('low');
        return false;
    }

    el.textContent = stock <= 4 ? 'Stok tersisa ' + stock + ' pcs' : 'Tersedia ' + stock + ' pcs';
    if (stock <= 4) el.classList.add('low');
    return true;
}

function swapVariant(colorName) {
    var v = getVariant(colorName, dSize || mSize || '') || variantMap[colorName];
    if (!v) return;
    if (v.img) {
        var mi = document.getElementById('main-product-img');
        var si = document.getElementById('sheet-product-img');
        if (mi) { mi.style.opacity = '0.6'; mi.src = v.img; mi.onload = function() { this.style.opacity='1'; }; }
        if (si) si.src = v.img;
    }
    var mapping = getMapping(colorName, dSize || mSize || '');
    var price = (mapping && mapping.price_override) || v.price_override || basePrice;
    var fmt   = formatRupiah(price);
    var e1 = document.getElementById('product-price-display');
    var e2 = document.getElementById('sheet-price-display');
    if (e1) e1.textContent = fmt;
    if (e2) e2.textContent = fmt;
}

var mColor = '', mSize = '', mQty = 1;
var dColor = '', dSize = '', dQty = 1;
var currentMode = 'cart';

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
    swapVariant(name);
    updateTapRow();
    renderStockNote('modal-stock-note', mColor, mSize, mQty);
}

function modalSize(el, size) {
    document.querySelectorAll('#size-btns-modal .size-btn').forEach(function(b) { b.classList.remove('active'); });
    el.classList.add('active');
    mSize = size;
    document.getElementById('modal-size').value = size;
    if (mColor) swapVariant(mColor);
    updateTapRow();
    renderStockNote('modal-stock-note', mColor, mSize, mQty);
}

function modalQty(delta) {
    mQty = Math.max(1, mQty + delta);
    document.getElementById('modal-qty-display').textContent = mQty;
    document.getElementById('modal-qty').value = mQty;
    renderStockNote('modal-stock-note', mColor, mSize, mQty);
}

function modalSubmit() {
    if (!mColor || !mSize) { document.getElementById('sheet-hint').style.display = 'block'; return; }
    if (!renderStockNote('modal-stock-note', mColor, mSize, mQty)) { return; }
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

function deskColor(el, name) {
    document.querySelectorAll('#color-btns-desk .color-btn').forEach(function(b) { b.classList.remove('active'); });
    el.classList.add('active');
    dColor = name;
    document.getElementById('desk-color').value = name;
    swapVariant(name);
    updateDeskSummary();
    renderStockNote('desk-stock-note', dColor, dSize, dQty);
}

function deskSize(el, size) {
    document.querySelectorAll('#size-btns-desk .size-btn').forEach(function(b) { b.classList.remove('active'); });
    el.classList.add('active');
    dSize = size;
    document.getElementById('desk-size').value = size;
    if (dColor) swapVariant(dColor);
    updateDeskSummary();
    renderStockNote('desk-stock-note', dColor, dSize, dQty);
}

function deskQty(delta) {
    dQty = Math.max(1, dQty + delta);
    document.getElementById('desk-qty-display').textContent = dQty;
    document.getElementById('desk-qty').value = dQty;
    updateDeskSummary();
    renderStockNote('desk-stock-note', dColor, dSize, dQty);
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
    if (!renderStockNote('desk-stock-note', dColor, dSize, dQty)) { return; }
    document.getElementById('desk-mode').value = mode;
    document.getElementById('desk-qty').value = dQty;
    document.getElementById('desk-cart-form').submit();
}

(function() {
    var sheet = document.getElementById('sheet');
    if (!sheet) return;
    var startY = 0;
    sheet.addEventListener('touchstart', function(e) { startY = e.touches[0].clientY; }, { passive: true });
    sheet.addEventListener('touchend', function(e) { if (e.changedTouches[0].clientY - startY > 60) closeModal(); }, { passive: true });
})();

(function() {
    var def = variantData.find(function(v) { return v.is_default; }) || variantData[0];
    if (!def) return;
    var db = document.querySelector('#color-btns-desk .color-btn[title="' + def.name + '"]') || document.querySelector('#color-btns-desk .color-btn');
    if (db) db.click();
    var mb = document.querySelector('#color-btns-modal .color-btn[title="' + def.name + '"]') || document.querySelector('#color-btns-modal .color-btn');
    if (mb) { mb.click(); mColor = def.name; }
})();
</script>
@endpush
