@extends('storefront.layouts.app')

@section('title', 'Greatfit')

@push('styles')
<style>
    html { scroll-behavior: smooth; scroll-padding-top: 82px; }
    body { overflow-x: hidden; }
    .wrap { width: min(1680px, calc(100% - 64px)); margin: 0 auto; }

    /* HERO — mobile */
    .hero-mobile { margin: 10px 0 0; display: grid; gap: 12px; }
    .hm-content { min-height: 284px; display: flex; flex-direction: column; justify-content: center; padding: 24px 4px 10px; position: relative; overflow: hidden; }
    .hm-label { font-size: 10px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--mid); display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }
    .hm-label::before { content: ''; width: 18px; height: 2px; background: var(--ink); display: block; }
    .hm-title { font-family: var(--font-display); font-size: 70px; font-weight: 800; line-height: .9; letter-spacing: 0; text-transform: uppercase; margin-bottom: 22px; }
    .hm-copy { max-width: 300px; margin: -6px 0 18px; font-size: 13px; color: var(--mid); font-weight: 500; line-height: 1.7; }
    .hm-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .btn-dk { height: 42px; padding: 0 20px; border-radius: var(--radius-pill); background: var(--ink); color: var(--white); font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; transition: opacity .15s; }
    .btn-dk:hover { opacity: .8; }
    .btn-sk { height: 42px; padding: 0 20px; border-radius: var(--radius-pill); background: transparent; color: var(--ink); font-size: 12px; font-weight: 700; border: 1.5px solid var(--line); display: inline-flex; align-items: center; transition: border-color .15s; }
    .btn-sk:hover { border-color: var(--ink); }
    .hm-visual { min-height: 360px; border-radius: 20px; background: var(--ink); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
    .hero-bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center; z-index: 0; opacity: 0; transition: opacity 1.4s ease; }
    .hero-bg.active { opacity: .68; }
    .hm-badge { position: absolute; top: 18px; right: 18px; z-index: 2; width: 62px; height: 62px; border-radius: 50%; background: var(--white); color: var(--ink); display: grid; place-items: center; font-size: 9px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; text-align: center; line-height: 1.4; }
    .hm-card { position: absolute; left: 14px; right: 14px; bottom: 14px; z-index: 2; background: rgba(255,255,255,.95); backdrop-filter: blur(10px); border-radius: 14px; padding: 13px 14px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 20px rgba(0,0,0,.15); }
    .hm-card-t { font-size: 13px; font-weight: 700; }
    .hm-card-s { font-size: 11px; color: var(--mid); margin-top: 3px; font-weight: 500; line-height: 1.45; }
    .hm-card-ic { width: 34px; height: 34px; border-radius: 50%; background: var(--ink); color: var(--white); display: grid; place-items: center; flex-shrink: 0; }

    /* HERO — desktop */
    .hero-desktop { display: none; min-height: calc(100svh - 56px); grid-template-columns: 1fr 1fr; }
    .hd-content { display: flex; flex-direction: column; justify-content: center; padding: 60px 0; }
    .hd-label { font-size: 10px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--mid); display: flex; align-items: center; gap: 8px; margin-bottom: 18px; }
    .hd-label::before { content: ''; width: 18px; height: 2px; background: var(--ink); display: block; }
    .hd-title { font-family: var(--font-display); font-size: 94px; font-weight: 800; line-height: .9; text-transform: uppercase; letter-spacing: 0; margin-bottom: 18px; }
    .hd-copy { max-width: 360px; margin-bottom: 28px; font-size: 14px; color: var(--mid); font-weight: 500; line-height: 1.75; }
    .hd-actions { display: flex; gap: 10px; }
    .hd-visual { background: var(--ink); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
    .hd-photo { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center; z-index: 0; opacity: 0; transition: opacity 1.4s ease; }
    .hd-photo.active { opacity: .68; }
    .hd-badge { position: absolute; top: 28px; right: 28px; z-index: 2; width: 68px; height: 68px; border-radius: 50%; background: var(--white); color: var(--ink); display: grid; place-items: center; font-size: 9px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; text-align: center; line-height: 1.4; }
    .hd-card { position: absolute; bottom: 24px; left: 24px; right: 24px; z-index: 2; background: rgba(255,255,255,.95); backdrop-filter: blur(10px); border-radius: 14px; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 20px rgba(0,0,0,.15); }
    .hd-card-t { font-size: 13px; font-weight: 700; }
    .hd-card-s { font-size: 11px; color: var(--mid); margin-top: 3px; font-weight: 500; line-height: 1.45; }
    .hd-card-ic { width: 34px; height: 34px; border-radius: 50%; background: var(--ink); color: var(--white); display: grid; place-items: center; }

    /* STRIP */
    .strip { background: var(--ink); padding: 11px 0; overflow: hidden; margin-top: 12px; }
    .strip-track { display: flex; gap: 36px; white-space: nowrap; animation: mq 20s linear infinite; }
    .strip-i { display: inline-flex; align-items: center; gap: 8px; font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: rgba(255,255,255,.7); flex-shrink: 0; }
    .strip-dot { width: 3px; height: 3px; border-radius: 50%; background: rgba(255,255,255,.3); }
    @@keyframes mq { from { transform: translateX(0); } to { transform: translateX(-50%); } }

    /* SECTIONS */
    .sec { padding: 32px 0; scroll-margin-top: 82px; }
    .sec-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
    .sec-t { font-size: 16px; font-weight: 800; letter-spacing: 0; }
    .sec-a { font-size: 12px; font-weight: 700; color: var(--mid); white-space: nowrap; }
    .sec-a:hover { color: var(--ink); }
    .sec-a.all-products { min-height: 34px; padding: 0 13px; border: 1px solid var(--ink); border-radius: var(--radius-pill); color: var(--ink); display: inline-flex; align-items: center; gap: 7px; font-size: 11px; }
    .sec-a.all-products:hover { background: var(--ink); color: var(--white); }

    /* CATEGORY SEARCH */
    .cat-search { padding-top: 34px; padding-bottom: 14px; }
    .cat-eyebrow { font-size: 10px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--mid); margin-bottom: 6px; }
    .cat-title { font-size: 18px; font-weight: 800; letter-spacing: 0; line-height: 1.2; }
    .cat-copy { max-width: 360px; margin-top: 7px; font-size: 12px; color: var(--mid); font-weight: 500; line-height: 1.65; }
    .cat-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .cat-card { min-height: 78px; border: 1px solid var(--line); border-radius: 14px; background: var(--white); padding: 12px; display: grid; grid-template-columns: 34px minmax(0, 1fr); align-items: center; column-gap: 10px; transition: background .15s, border-color .15s; }
    .cat-card:hover { border-color: #d2d2d2; background: #fafafa; }
    .cat-ic { width: 34px; height: 34px; border-radius: 50%; background: var(--soft); display: grid; place-items: center; }
    .cat-ic svg { width: 22px; height: 22px; }
    .cat-name { font-size: 11px; font-weight: 800; line-height: 1.3; letter-spacing: .03em; text-transform: uppercase; overflow-wrap: anywhere; }

    /* CHANNELS */
    .shop-channels { padding-top: 12px; }
    .shop-panel { border: 1px solid var(--line); border-radius: 20px; background: #fafafa; padding: 18px; }
    .shop-head { margin-bottom: 16px; }
    .shop-kicker { display: inline-flex; align-items: center; gap: 7px; height: 26px; padding: 0 10px; border-radius: var(--radius-pill); background: var(--ink); color: var(--white); font-size: 9px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; margin-bottom: 12px; }
    .shop-kicker::before { content: ""; width: 5px; height: 5px; border-radius: 50%; background: currentColor; opacity: .75; }
    .shop-title { font-size: 22px; font-weight: 800; letter-spacing: 0; line-height: 1.15; }
    .shop-copy { max-width: 440px; margin-top: 8px; font-size: 12px; color: var(--mid); font-weight: 500; line-height: 1.65; }
    .chs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; }
    .ch { position: relative; min-height: 62px; border-radius: 14px; background: var(--white); border: 1px solid var(--line); padding: 12px; display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: 12px; font-weight: 800; transition: background .15s, border-color .15s; }
    .ch:hover { border-color: #d2d2d2; background: #fafafa; }
    .ch.dk { background: var(--ink); color: var(--white); border-color: var(--ink); }
    .ch.dk::after { content: "Rekomendasi"; position: absolute; top: 8px; right: 9px; font-size: 8px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: rgba(255,255,255,.62); }
    .ch-main { display: flex; align-items: center; gap: 9px; min-width: 0; }
    .ch-mark { width: 30px; height: 30px; border-radius: 50%; background: var(--soft); color: var(--ink); display: grid; place-items: center; font-size: 10px; font-weight: 900; flex: 0 0 auto; }
    .ch.dk .ch-mark { background: rgba(255,255,255,.14); color: var(--white); }
    .ch-name { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ch-note { display: block; margin-top: 3px; font-size: 10px; color: var(--mid); font-weight: 600; }
    .ch.dk .ch-note { color: rgba(255,255,255,.58); }
    .ch-arr { color: var(--mid); flex: 0 0 auto; }
    .ch.dk .ch-arr { color: rgba(255,255,255,.75); }

    /* PRODUCTS */
    .prods { display: grid; grid-template-columns: repeat(2, 1fr); gap: 9px; }
    .pc { border-radius: 16px; overflow: hidden; background: var(--soft); border: 1px solid var(--line); display: block; }
    .pc-img { aspect-ratio: 1; position: relative; background: var(--soft); overflow: hidden; }
    .pc-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pc-tag { position: absolute; top: 9px; left: 9px; z-index: 3; background: var(--ink); color: var(--white); font-size: 9px; font-weight: 800; padding: 3px 7px; border-radius: var(--radius-pill); letter-spacing: .04em; }
    .pc-tag.badge-trending { background: #0f172a; }
    .pc-tag.badge-new      { background: #6366f1; }
    .pc-tag.badge-jumbo    { background: #7c3aed; }
    .pc-stock-badge { position: absolute; top: 9px; right: 9px; z-index: 3; background: #f97316; color: #fff; font-size: 9px; font-weight: 900; padding: 3px 7px; border-radius: var(--radius-pill); letter-spacing: .03em; }
    .pc-stock-badge.out { background: #111; }
    .pc-b { padding: 10px 11px; }
    .pc-n { font-size: 12px; font-weight: 600; line-height: 1.28; color: var(--ink); }
    .pc-p { font-size: 14px; font-weight: 800; margin-top: 5px; }
    .pc-stock-line { margin-top: 5px; font-size: 10px; color: var(--mid); font-weight: 800; }
    .pc-stock-line.low { color: #f97316; }
    .pc-stock-line.out { color: #b91c1c; }
    .pc-mini { display: none; }

    /* VALUES */
    .vals { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: var(--line); border: 1px solid var(--line); border-radius: 16px; overflow: hidden; }
    .val { background: var(--white); padding: 20px 16px; }
    .val-n { font-family: var(--font-display); font-size: 32px; font-weight: 900; color: var(--line); line-height: 1; margin-bottom: 8px; }
    .val-t { font-size: 12px; font-weight: 700; margin-bottom: 5px; }
    .val-d { font-size: 11px; color: var(--mid); font-weight: 500; line-height: 1.65; display: none; }

    /* CLOSING CTA */
    .cta-blk { background: var(--ink); color: var(--white); border-radius: 20px; padding: 40px 20px; text-align: center; }
    .cta-blk-t { font-family: var(--font-display); font-size: 54px; font-weight: 800; text-transform: uppercase; line-height: .92; letter-spacing: 0; margin-bottom: 20px; }
    .cta-blk-row { display: flex; gap: 8px; justify-content: center; }
    .btn-cw { height: 44px; padding: 0 22px; border-radius: var(--radius-pill); background: var(--white); color: var(--ink); font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; }
    .btn-co { height: 44px; padding: 0 22px; border-radius: var(--radius-pill); background: transparent; color: rgba(255,255,255,.65); border: 1px solid rgba(255,255,255,.2); font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; }

    @@media (max-width: 719px) {
        .wrap { width: min(520px, calc(100% - 28px)); }
        html { scroll-padding-top: 96px; }
        .hm-content { min-height: 266px; padding-top: 22px; }
        .hm-label { margin-bottom: 14px; }
        .hm-title { font-size: 66px; margin-bottom: 18px; }
        .hm-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 9px; }
        .btn-dk, .btn-sk { height: 44px; justify-content: center; padding: 0 14px; font-size: 12px; }
        .hm-visual { min-height: 330px; border-radius: 18px; }
        .shop-channels { padding-top: 10px; padding-bottom: 20px; }
        .shop-panel { padding: 16px; border-radius: 18px; }
        .shop-kicker { height: 24px; font-size: 8.5px; margin-bottom: 11px; }
        .shop-title { font-size: 21px; max-width: 260px; }
        .shop-copy { max-width: 280px; font-size: 12px; }
        .ch { min-height: 64px; border-radius: 13px; padding: 11px; }
        .ch.dk { grid-column: 1 / -1; min-height: 70px; }
        .ch-mark { width: 28px; height: 28px; }
        .ch-name { font-size: 11.5px; }
        .sec { padding: 30px 0; scroll-margin-top: 96px; }
        .cat-search { padding-top: 30px; padding-bottom: 14px; }
        .cat-title { font-size: 18px; }
        .cat-grid { gap: 8px; }
        .cat-card { min-height: 66px; border-radius: 12px; background: var(--soft); padding: 10px; }
        .cat-card:active { background: var(--ink); color: var(--white); border-color: var(--ink); transform: scale(.985); }
        .cat-card:active .cat-ic { background: rgba(255,255,255,.14); }
        .prods { gap: 10px; }
        .pc { border-radius: 14px; background: var(--white); }
        .pc-b { padding: 11px; }
        .pc-n { font-size: 12.5px; min-height: 34px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .pc-mini { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 9px; padding-top: 9px; border-top: 1px solid var(--line); font-size: 10px; color: var(--mid); font-weight: 800; }
    }
    @@media (min-width: 720px) {
        .hero-mobile { display: none; }
        .hero-desktop { display: grid; }
        .hd-title { font-size: 96px; }
        .strip { margin-top: 0; }
        .cat-search { padding-top: 44px; }
        .cat-head { display: flex; margin-bottom: 16px; }
        .cat-title { font-size: 22px; }
        .cat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .cat-card { min-height: 104px; padding: 16px; grid-template-columns: 42px minmax(0, 1fr); }
        .cat-ic { width: 42px; height: 42px; }
        .cat-name { font-size: 12px; }
        .prods { grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .chs { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .val-d { display: block; }
        .val { padding: 28px 22px; }
        .cta-blk { padding: 56px 24px; border-radius: 24px; }
        .cta-blk-t { font-size: 72px; }
        .sec { padding: 44px 0; }
    }
</style>
@endpush

@php $navActive = 'home'; @endphp

@section('content')
{{-- HERO MOBILE --}}
<div class="wrap">
    <div class="hero-mobile">
        <div class="hm-content">
            <div class="hm-label">New Collection 2026</div>
            <div class="hm-title">Good Fit,<br>Good Feel.</div>
            <div class="hm-copy">Hal kecil yang bikin hari terasa lebih nyaman.</div>
            <div class="hm-actions">
                <a href="#beli" class="btn-dk">
                    Mulai Belanja
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
                <a href="#kategori" class="btn-sk">Pilih Kategori</a>
            </div>
        </div>
        <div class="hm-visual">
            <img class="hero-bg active" src="https://images.unsplash.com/photo-1660167213901-e2f33a1a7486?ixlib=rb-4.1.0&q=85&fm=jpg&crop=entropy&cs=srgb&w=700&h=920&fit=crop" alt="Outfit sporty">
            <img class="hero-bg" src="https://images.unsplash.com/photo-1756786825067-4b153740e7c2?ixlib=rb-4.1.0&q=85&fm=jpg&crop=entropy&cs=srgb&w=700&h=920&fit=crop" alt="Gaya kasual outdoor">
            <img class="hero-bg" src="https://images.unsplash.com/photo-1774160928808-afdd9b93363b?ixlib=rb-4.1.0&q=85&fm=jpg&crop=entropy&cs=srgb&w=700&h=920&fit=crop" alt="Koleksi Greatfit">
            <div class="hm-badge">New<br>2026</div>
            <a href="{{ route('storefront.products') }}" class="hm-card">
                <div>
                    <div class="hm-card-t">Greatfit Collection</div>
                    <div class="hm-card-s">Hal kecil yang membuat hidup jadi luar biasa.</div>
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
    <div class="hd-content" style="padding-left:max(32px,calc((100vw - 1680px)/2 + 32px));">
        <div class="hd-label">New Collection 2026</div>
        <h1 class="hd-title">Good Fit,<br>Good Feel.</h1>
        <div class="hd-copy">Hal kecil yang bikin hari terasa lebih nyaman.</div>
        <div class="hd-actions">
            <a href="#beli" class="btn-dk">
                Mulai Belanja
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
            <a href="#kategori" class="btn-sk">Pilih Kategori</a>
        </div>
    </div>
    <div class="hd-visual">
        <img class="hd-photo active" src="https://images.unsplash.com/photo-1660167213901-e2f33a1a7486?ixlib=rb-4.1.0&q=85&fm=jpg&crop=entropy&cs=srgb&w=1000&h=1200&fit=crop" alt="Outfit sporty">
        <img class="hd-photo" src="https://images.unsplash.com/photo-1756786825067-4b153740e7c2?ixlib=rb-4.1.0&q=85&fm=jpg&crop=entropy&cs=srgb&w=1000&h=1200&fit=crop" alt="Gaya kasual outdoor">
        <img class="hd-photo" src="https://images.unsplash.com/photo-1774160928808-afdd9b93363b?ixlib=rb-4.1.0&q=85&fm=jpg&crop=entropy&cs=srgb&w=1000&h=1200&fit=crop" alt="Koleksi Greatfit">
        <div class="hd-badge">New<br>2026</div>
        <a href="{{ route('storefront.products') }}" class="hd-card">
            <div>
                <div class="hd-card-t">Greatfit Collection</div>
                <div class="hd-card-s">Hal kecil yang membuat hidup jadi luar biasa.</div>
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
            <span class="strip-i">Little Things <span class="strip-dot"></span></span>
        @endforeach
    </div>
</div>

<div class="wrap">

    {{-- CATEGORY SEARCH --}}
    @if($categories->isNotEmpty())
    <section class="sec cat-search" id="kategori">
        <div class="sec-head cat-head">
            <div>
                <div class="cat-eyebrow">Koleksi</div>
                <div class="cat-title">Cari yang paling pas</div>
                <div class="cat-copy">Mulai dari kategori yang kamu butuhkan.</div>
            </div>
            <a href="{{ route('storefront.products') }}" class="sec-a">Lihat semua</a>
        </div>
        <div class="cat-grid">
            @foreach($categories->take(8) as $cat)
            @php
                $catSlug = strtolower($cat->slug ?? $cat->name);
                $catIcon = match(true) {
                    str_contains($catSlug, 'hoodie')                                                   => 'hoodie',
                    str_contains($catSlug, 'jacket') || str_contains($catSlug, 'jaket')               => 'jacket',
                    str_contains($catSlug, 'pants') || str_contains($catSlug, 'celana') || str_contains($catSlug, 'jogger') => 'pants',
                    str_contains($catSlug, 'shirt') || str_contains($catSlug, 'kaos')                 => 'shirt',
                    default                                                                             => 'tag',
                };
            @endphp
            <a href="{{ route('storefront.products', ['kategori' => $cat->slug]) }}" class="cat-card">
                <span class="cat-ic">
                    @if($catIcon === 'hoodie')
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4l4 3 4-3 4 4-2 4v8H6v-8L4 8l4-4z"/><path d="M9 20v-6h6v6"/></svg>
                    @elseif($catIcon === 'jacket')
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4h6l4 4v12H5V8l4-4z"/><path d="M12 7v13"/><path d="M8 12h2"/><path d="M14 12h2"/></svg>
                    @elseif($catIcon === 'pants')
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4h8l1 16h-4l-1-9-1 9H7L8 4z"/><path d="M9 4v3h6V4"/></svg>
                    @elseif($catIcon === 'shirt')
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4l4 3 4-3 4 5-3 2v9H7v-9L4 9l4-5z"/></svg>
                    @else
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><path d="M7 7h.01"/></svg>
                    @endif
                </span>
                <div class="cat-name">{{ $cat->name }}</div>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- SHOP CHANNELS --}}
    <section class="sec shop-channels" id="beli">
        <div class="shop-panel">
            <div class="shop-head">
                <div class="shop-kicker">Channel Belanja</div>
                <div class="shop-title">Mau belanja lewat mana?</div>
                <div class="shop-copy">Website Greatfit atau marketplace favorit, pilih yang paling nyaman.</div>
            </div>
            <div class="chs">
                @foreach($channels as $ch)
                @php
                    $label     = $ch['label'] ?? 'Store';
                    $isWebsite = (bool) ($ch['dark'] ?? false);
                    $initial   = collect(explode(' ', $label))->map(fn($p) => mb_substr($p, 0, 1))->join('');
                    $note      = $isWebsite ? 'Lihat produk pilihan' : 'Buka marketplace';
                    $chUrl     = $isWebsite ? '#produk' : ($ch['url'] ?? '#');
                @endphp
                <a class="ch {{ $isWebsite ? 'dk' : '' }}" href="{{ $chUrl }}" @if(!$isWebsite) target="_blank" rel="noopener" @endif>
                    <span class="ch-main">
                        <span class="ch-mark">{{ mb_substr($initial ?: $label, 0, 2) }}</span>
                        <span class="ch-text">
                            <span class="ch-name">{{ $label }}</span>
                            <span class="ch-note">{{ $note }}</span>
                        </span>
                    </span>
                    <span class="ch-arr">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7"/><path d="M9 7h8v8"/></svg>
                    </span>
                </a>
                @endforeach
            </div>
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

    {{-- PRODUCTS --}}
    <section class="sec" id="produk">
        <div class="sec-head">
            <div class="sec-t">Produk pilihan</div>
            <a href="{{ route('storefront.products') }}" class="sec-a all-products">
                Lihat semua produk
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
            </a>
        </div>
        <div class="prods">
            @foreach ($products as $p)
            @php
                if (!empty($p['label'])) {
                    $badgeText  = $p['label'];
                    $badgeClass = ($p['product_type'] ?? '') === 'jumbo' ? 'badge-jumbo' : '';
                } elseif (($p['rank_position'] ?? null) && $p['rank_position'] <= 3) {
                    $badgeText  = '🔥 Trending';
                    $badgeClass = 'badge-trending';
                } elseif ($p['is_new_product'] ?? false) {
                    $badgeText  = '✨ Baru';
                    $badgeClass = 'badge-new';
                } else {
                    $badgeText  = null;
                    $badgeClass = '';
                }
                $stockStatus = $p['stock_status'] ?? 'ok';
                $availableStock = (int) ($p['available_stock'] ?? 0);
            @endphp
            <a href="{{ route('storefront.product_detail', $p['slug']) }}" class="pc">
                <div class="pc-img">
                    @if($badgeText)
                    <span class="pc-tag {{ $badgeClass }}">{{ $badgeText }}</span>
                    @endif
                    @if($stockStatus === 'out')
                    <span class="pc-stock-badge out">Stok Habis</span>
                    @elseif($stockStatus === 'low')
                    <span class="pc-stock-badge">Stok Terbatas</span>
                    @endif
                    <img src="{{ storefront_img($p['img']) }}" alt="{{ $p['name'] }}" loading="lazy">
                </div>
                <div class="pc-b">
                    @if(!empty($p['category_name']) || !empty($p['audience_label']))
                    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:3px;">
                        @if(!empty($p['category_name']))
                        <span style="font-size:9px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--mid);">{{ $p['category_name'] }}</span>
                        @endif
                        @if(!empty($p['audience_label']))
                        @php $audColors = ['pria'=>'#1d4ed8','wanita'=>'#be185d','anak'=>'#d97706','olahraga'=>'#15803d','unisex'=>'#6b7280']; @endphp
                        <span style="font-size:9px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:{{ $audColors[$p['audience']] ?? '#6b7280' }};">{{ $p['audience_label'] }}</span>
                        @endif
                    </div>
                    @endif
                    <div class="pc-n">{{ $p['name'] }}</div>
                    <div class="pc-p">Rp{{ number_format($p['price'], 0, ',', '.') }}</div>
                    <div class="pc-stock-line {{ $stockStatus === 'out' ? 'out' : ($stockStatus === 'low' ? 'low' : '') }}">
                        {{ $stockStatus === 'out' ? 'Belum tersedia' : ($stockStatus === 'low' ? 'Tersisa ' . $availableStock . ' pcs' : 'Stok tersedia') }}
                    </div>
                    <div class="pc-mini"><span>Detail</span><span>→</span></div>
                </div>
            </a>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="sec" style="padding-top:0;">
        <div class="cta-blk">
            <div class="cta-blk-t">Ready to<br>Wear Daily.</div>
            <div class="cta-blk-row">
                <a href="{{ route('storefront.products') }}" class="btn-cw">Shop Now</a>
                <a href="#beli" class="btn-co">Marketplace</a>
            </div>
        </div>
    </section>

</div>
@endsection

@push('scripts')
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

    // Smooth scroll to #beli
    document.querySelectorAll('a[href="#beli"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var target = document.getElementById('beli');
            var panel  = target && target.querySelector('.shop-panel');
            var nav    = document.querySelector('.nav');
            if (!target || !panel) return;
            e.preventDefault();
            var top = panel.getBoundingClientRect().top + window.scrollY - (nav ? nav.getBoundingClientRect().height : 0) - 26;
            window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
        });
    });

    // Smooth scroll to #produk
    document.querySelectorAll('a[href="#produk"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var target = document.getElementById('produk');
            var nav    = document.querySelector('.nav');
            if (!target) return;
            e.preventDefault();
            var top = target.getBoundingClientRect().top + window.scrollY - (nav ? nav.getBoundingClientRect().height : 0) - 18;
            window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
        });
    });
})();
</script>
@endpush
