@extends('storefront.layouts.app')

@section('title', storefront_setting('branding.brand_name', 'Greatfit'))

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
    .hero-title-line { display: block; }
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
    .shop-panel { border: 1px solid var(--line); border-radius: 20px; background: var(--white); padding: 18px; box-shadow: none; }
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
@php
    $sfHeroLabel      = storefront_setting('hero.label',               'Koleksi Terbaru');
    $sfHeroTitle1     = storefront_setting('hero.title_line1',         'Good Fit,');
    $sfHeroTitle2     = storefront_setting('hero.title_line2',         'Good Feel.');
    $sfHeroCopy       = storefront_setting('hero.copy',                'Hal kecil yang bikin hari terasa lebih nyaman.');
    $sfHeroCtaLabel   = storefront_setting('hero.cta_primary_label',   'Lihat Koleksi');
    $sfHeroCtaUrl     = storefront_setting('hero.cta_primary_url',     '#beli');
    $sfHeroCta2Label  = storefront_setting('hero.cta_secondary_label', 'Pilih Kategori');
    $sfHeroCta2Url    = storefront_setting('hero.cta_secondary_url',   '#kategori');
    $sfHeroBadge      = storefront_setting('hero.badge_text',          '');
    $sfCardTitle      = storefront_setting('hero.card_title',          'Greatfit Collection');
    $sfCardSubtitle   = storefront_setting('hero.card_subtitle',       '10rb+ pelanggan puas');
    $sfBrandName      = storefront_setting('branding.brand_name',      'Greatfit');
    $sfCatEyebrow     = storefront_setting('categories.eyebrow',       'Koleksi');
    $sfCatTitle       = storefront_setting('categories.title',         'Cari yang paling pas');
    $sfCatCopy        = storefront_setting('categories.copy',          'Mulai dari kategori yang kamu butuhkan.');
    $sfCatAllLabel    = storefront_setting('categories.all_label',     'Lihat semua');
    $sfCatLimit       = (int) storefront_setting('categories.limit',   '8');
    $sfCatLimit       = in_array($sfCatLimit, [4, 6, 8, 10, 12], true) ? $sfCatLimit : 8;
    // ── FOTO HERO: daftar dinamis dari hero.images (JSON [{url, focus_desktop, focus_mobile}]) ──
    //    >1 foto = slideshow otomatis; titik fokus per foto di-set via drag di admin.
    $sfFocusOk = fn($v) => is_string($v) && preg_match('/^\d+% \d+%$/', $v) ? $v : null;

    $sfHeroSlots = [];
    $sfHeroDecoded = json_decode((string) storefront_setting('hero.images'), true);
    if (is_array($sfHeroDecoded)) {
        foreach ($sfHeroDecoded as $sfHp) {
            $sfHpUrl = storefront_media_url($sfHp['url'] ?? null);
            if ($sfHpUrl) {
                $sfHeroSlots[] = [
                    'url' => $sfHpUrl,
                    'focus' => $sfFocusOk($sfHp['focus_desktop'] ?? null) ?: $sfFocusOk($sfHp['focus'] ?? null),
                    'focus_mobile' => $sfFocusOk($sfHp['focus_mobile'] ?? null) ?: $sfFocusOk($sfHp['focus'] ?? null),
                ];
            }
        }
    }

    // Fallback legacy: hero.image_1 lama, lalu default bawaan
    if (empty($sfHeroSlots)) {
        $sfHeroImage = storefront_media_url(storefront_setting(
            'hero.image_1',
            'https://images.unsplash.com/photo-1660167213901-e2f33a1a7486?ixlib=rb-4.1.0&q=85&fm=jpg&crop=entropy&cs=srgb&w=1000&h=1200&fit=crop'
        ));
        if ($sfHeroImage) {
            $sfHeroLegacyFocus = $sfFocusOk(storefront_setting('hero.image_1_focus'));
            $sfHeroSlots[] = ['url' => $sfHeroImage, 'focus' => $sfHeroLegacyFocus, 'focus_mobile' => $sfHeroLegacyFocus];
        }
    }

    $sfHeroSlotsMobile = $sfHeroSlots;

    // Gaya hero: split (default) / gradient (foto full-bleed melebur ke warna latar)
    $sfHeroStyle    = storefront_setting('hero.style', 'split') === 'gradient' ? 'gradient' : 'split';
    $sfHeroOverlay  = storefront_setting('hero.overlay_color') ?: '#ffffff';
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $sfHeroOverlay)) { $sfHeroOverlay = '#ffffff'; }
    $sfHeroStrength = (int) (storefront_setting('hero.overlay_strength') ?: 55);
    $sfHeroStrength = max(30, min(90, $sfHeroStrength));
    $sfHeroSolidStop = max($sfHeroStrength - 35, 0);
    $sfHeroGradClass = $sfHeroStyle === 'gradient' ? 'hero-grad' : '';

    // Mode & fokus foto (berlaku utk kedua gaya)
    $sfPhotoFit   = storefront_setting('hero.photo_fit', 'cover') === 'contain' ? 'contain' : 'cover';
    $sfPhotoFocus = storefront_setting('hero.photo_focus', 'center');
    $sfPhotoPos   = [
        'top'    => 'center top',
        'bottom' => 'center bottom',
        'left'   => 'left center',
        'right'  => 'right center',
    ][$sfPhotoFocus] ?? ($sfHeroStyle === 'gradient' ? '72% center' : 'center');
    // Sections
    $sfSectionOrder = array_values(array_filter(array_map('trim',
        explode(',', storefront_setting('sections.order', 'hero,categories,channels,values,products,cta'))
    )));
    $sfViz = fn($n) => storefront_setting("sections.{$n}_visible", '1') !== '0';
    $sfSectionStyle = function ($key, $defaults = []) {
        $num = fn($name, $default) => is_numeric(storefront_setting('sections.' . $key . '_' . $name))
            ? (int) storefront_setting('sections.' . $key . '_' . $name)
            : $default;
        $hex = storefront_setting('sections.' . $key . '_bg');
        $style = storefront_setting('sections.' . $key . '_style', 'default');
        return [
            'pt' => $num('padding_top', $defaults['pt'] ?? null),
            'pb' => $num('padding_bottom', $defaults['pb'] ?? null),
            'mt' => $num('margin_top', $defaults['mt'] ?? 0),
            'mb' => $num('margin_bottom', $defaults['mb'] ?? 0),
            'bg' => is_string($hex) && preg_match('/^#[0-9a-fA-F]{6}$/', $hex) ? $hex : null,
            'style' => in_array($style, ['soft','line','compact','outline','elevated','dark','editorial'], true) ? $style : 'default',
        ];
    };
    $sfSectionCss = function ($key, $styles) {
        $css = '';
        if ($styles['pt'] !== null) $css .= 'padding-top:' . $styles['pt'] . 'px;';
        if ($styles['pb'] !== null) $css .= 'padding-bottom:' . $styles['pb'] . 'px;';
        if ($styles['mt']) $css .= 'margin-top:' . $styles['mt'] . 'px;';
        if ($styles['mb']) $css .= 'margin-bottom:' . $styles['mb'] . 'px;';
        if ($styles['bg']) $css .= 'background:' . $styles['bg'] . ';';
        if ($styles['style'] === 'soft') $css .= 'background:' . ($styles['bg'] ?: 'var(--soft)') . ';border-radius:20px;padding-left:18px;padding-right:18px;';
        if ($styles['style'] === 'line') $css .= "border-top:1px solid var(--line);";
        if ($styles['style'] === 'compact') $css .= "padding-top:20px;padding-bottom:20px;";
        if ($styles['style'] === 'outline') $css .= 'border:1px solid var(--line);border-radius:20px;padding-left:18px;padding-right:18px;';
        if ($styles['style'] === 'elevated') $css .= 'background:' . ($styles['bg'] ?: '#ffffff') . ';border:1px solid var(--line);border-radius:20px;padding-left:18px;padding-right:18px;box-shadow:0 14px 36px rgba(15,23,42,.08);';
        if ($styles['style'] === 'dark') $css .= 'background:var(--ink);color:var(--white);border-radius:20px;padding-left:18px;padding-right:18px;';
        if ($styles['style'] === 'editorial') $css .= 'border-left:3px solid var(--ink);padding-left:22px;';
        return $css;
    };
@endphp

{{-- HERO --}}
@if($sfViz('hero'))
@php
    // Gaya teks & tombol hero (dari Pengaturan Website)
    $sfHex = fn($k, $d) => (is_string($v = storefront_setting($k)) && preg_match('/^#[0-9a-fA-F]{6}$/', $v)) ? $v : $d;
    $sfLabelColor = $sfHex('hero.label_color', '');
    $sfTitleColor = $sfHex('hero.title_color', '');
    $sfCopyColor  = $sfHex('hero.copy_color', '');
    $sfBadgeBg    = $sfHex('hero.badge_bg', '');
    $sfBadgeColor = $sfHex('hero.badge_color', '');
    $sfCtaBg      = $sfHex('hero.cta_bg', '');
    $sfCtaColor   = $sfHex('hero.cta_color', '');
    $sfCta2Color  = $sfHex('hero.cta2_color', '');
    $sfTitleSize  = storefront_setting('hero.title_size', 'm');
    $sfTitleStyleRaw = storefront_setting('hero.title_style', 'solid');
    $sfTitleStyle = in_array($sfTitleStyleRaw, ['two_tone_mask', 'promo_poster', 'clean_sans', 'condensed_impact', 'outline_editorial'], true) ? $sfTitleStyleRaw : 'solid';
    $sfCtaRadius  = ['pill' => '999px', 'rounded' => '12px', 'square' => '6px'][storefront_setting('hero.cta_radius')] ?? null;
    $sfTitleDesktop = ['xs' => '60px', 's' => '72px', 'm' => '96px', 'l' => '112px', 'xl' => '128px'][$sfTitleSize] ?? '96px';
    $sfTitleMobile  = ['xs' => '44px', 's' => '54px', 'm' => '70px', 'l' => '82px', 'xl' => '92px'][$sfTitleSize] ?? '70px';
    $sfPromoTitleDesktop = ['xs' => '44px', 's' => '52px', 'm' => '64px', 'l' => '76px', 'xl' => '88px'][$sfTitleSize] ?? '64px';
    $sfPromoTitleMobile  = ['xs' => '30px', 's' => '36px', 'm' => '44px', 'l' => '52px', 'xl' => '58px'][$sfTitleSize] ?? '44px';

    // Tinggi hero (% tinggi layar); 100 = perilaku default (full screen)
    $sfHeroHeight = (int) (storefront_setting('hero.height') ?: 100);
    $sfHeroHeight = max(40, min(100, $sfHeroHeight));
    $sfHeroHeightMobile = (int) round($sfHeroHeight * 0.76); // proporsi mobile grad (default 76svh)
@endphp
<style>
    /* ── Mode & fokus foto hero (dari Pengaturan Website) ── */
    .hero-desktop .hd-photo,
    .hero-mobile .hero-bg {
        object-fit: {{ $sfPhotoFit }};
        object-position: {{ $sfPhotoPos }};
    }

    /* ── Tinggi hero (dari Pengaturan Website) ── */
    @if($sfHeroHeight < 100)
    .hero-desktop { min-height: calc({{ $sfHeroHeight }}svh - 56px); }
    .hero-mobile.hero-grad { min-height: {{ $sfHeroHeightMobile }}svh !important; }
    @endif

    /* ── Gaya teks & tombol hero (dari Pengaturan Website) ── */
    @if($sfLabelColor) .hd-label, .hm-label { color: {{ $sfLabelColor }}; } .hd-label::before, .hm-label::before { background: {{ $sfLabelColor }}; } @endif
    @if($sfTitleColor) .hd-title, .hm-title { color: {{ $sfTitleColor }}; } @endif
    @if($sfCopyColor)  .hd-copy, .hm-copy   { color: {{ $sfCopyColor }}; } @endif
    @if($sfBadgeBg || $sfBadgeColor)
    .hd-badge, .hm-badge { {!! $sfBadgeBg ? "background: {$sfBadgeBg};" : '' !!} {!! $sfBadgeColor ? "color: {$sfBadgeColor};" : '' !!} }
    @endif
    @if($sfCtaBg || $sfCtaColor)
    .hero-desktop .btn-dk, .hero-mobile .btn-dk { {!! $sfCtaBg ? "background: {$sfCtaBg};" : '' !!} {!! $sfCtaColor ? "color: {$sfCtaColor};" : '' !!} }
    @endif
    @if($sfCta2Color)
    .hero-desktop .btn-sk, .hero-mobile .btn-sk { color: {{ $sfCta2Color }}; border-color: {{ $sfCta2Color }}; }
    @endif
    @if($sfCtaRadius)
    .hero-desktop .btn-dk, .hero-mobile .btn-dk,
    .hero-desktop .btn-sk, .hero-mobile .btn-sk { border-radius: {{ $sfCtaRadius }}; }
    @endif
    .hd-title { font-size: {{ $sfTitleDesktop }}; }
    .hm-title { font-size: {{ $sfTitleMobile }}; }
    @if($sfTitleStyle === 'two_tone_mask')
    .hd-title, .hm-title { color: {{ $sfTitleColor ?: 'var(--ink)' }}; }
    .hd-title .hero-title-line:nth-child(2),
    .hm-title .hero-title-line:nth-child(2) {
        color: transparent;
        background: linear-gradient(90deg, {{ $sfTitleColor ?: 'var(--ink)' }} 0 52%, var(--mid) 52% 100%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        -webkit-text-stroke: .012em {{ $sfTitleColor ?: 'var(--ink)' }};
        text-shadow: none;
    }
    @endif
    @if($sfTitleStyle === 'promo_poster')
    .hd-title,
    .hm-title {
        font-family: var(--font-body);
        font-weight: 900;
        text-transform: none;
        letter-spacing: 0;
        line-height: 1.16;
        color: {{ $sfTitleColor ?: 'var(--ink)' }};
    }
    .hd-title { font-size: {{ $sfPromoTitleDesktop }}; max-width: 560px; margin-bottom: 20px; }
    .hm-title { font-size: {{ $sfPromoTitleMobile }}; max-width: 340px; margin-bottom: 18px; }
    .hd-title::after,
    .hm-title::after {
        content: "";
        display: block;
        width: min(320px, 100%);
        height: 2px;
        background: currentColor;
        margin-top: 24px;
    }
    .hd-copy { max-width: 520px; margin-top: 0; font-size: 20px; line-height: 1.55; color: {{ $sfCopyColor ?: 'var(--ink)' }}; }
    .hm-copy { max-width: 320px; margin-top: 0; font-size: 15px; line-height: 1.6; color: {{ $sfCopyColor ?: 'var(--ink)' }}; }
    .hd-actions .btn-dk,
    .hm-actions .btn-dk { min-height: 48px; padding: 0 26px; font-size: 17px; }
    .hd-actions .btn-sk,
    .hm-actions .btn-sk { min-height: 48px; padding: 0 22px; font-size: 14px; }
    @endif
    @if($sfTitleStyle === 'clean_sans')
    .hd-title,
    .hm-title {
        font-family: var(--font-body);
        font-weight: 900;
        text-transform: none;
        line-height: 1.04;
        letter-spacing: 0;
        color: {{ $sfTitleColor ?: 'var(--ink)' }};
    }
    @endif
    @if($sfTitleStyle === 'condensed_impact')
    .hd-title,
    .hm-title {
        font-family: var(--font-display);
        font-weight: 900;
        text-transform: uppercase;
        line-height: .82;
        letter-spacing: .01em;
        color: {{ $sfTitleColor ?: 'var(--ink)' }};
    }
    @endif
    @if($sfTitleStyle === 'outline_editorial')
    .hd-title,
    .hm-title {
        color: transparent;
        -webkit-text-fill-color: transparent;
        -webkit-text-stroke: .018em {{ $sfTitleColor ?: 'var(--ink)' }};
        text-transform: uppercase;
        letter-spacing: .01em;
    }
    .hd-title .hero-title-line:nth-child(1),
    .hm-title .hero-title-line:nth-child(1) {
        color: {{ $sfTitleColor ?: 'var(--ink)' }};
        -webkit-text-fill-color: currentColor;
        -webkit-text-stroke: 0;
    }
    @endif
</style>
@if($sfHeroStyle === 'gradient')
<style>
    /* ── HERO GAYA GRADASI (dari Pengaturan Website) ── */
    /* Desktop: foto full-bleed, gradasi {{ $sfHeroOverlay }} dari kiri */
    .hero-desktop.hero-grad { position: relative; grid-template-columns: 1fr; }
    .hero-desktop.hero-grad .hd-visual { position: absolute; inset: 0; background: {{ $sfHeroOverlay }}; }
    .hero-desktop.hero-grad .hd-photo.active { opacity: 1; }
    .hero-desktop.hero-grad .hd-visual::after {
        content: ''; position: absolute; inset: 0; z-index: 1;
        background: linear-gradient(90deg, {{ $sfHeroOverlay }} {{ $sfHeroSolidStop }}%, {{ $sfHeroOverlay }}00 {{ $sfHeroStrength }}%);
    }
    .hero-desktop.hero-grad .hd-content { position: relative; z-index: 2; max-width: 620px; }
    .hero-desktop.hero-grad .hd-badge,
    .hero-desktop.hero-grad .hd-card { z-index: 2; }
    .hero-desktop.hero-grad .hd-card { left: 48%; }

    /* Mobile: foto full-screen, gradasi naik dari bawah.
       DIBUNGKUS media query agar tidak menimpa display:none di desktop. */
    @@media (max-width: 767.98px) {
        .hero-mobile.hero-grad { position: relative; display: block; min-height: 76svh; border-radius: 18px; overflow: hidden; background: {{ $sfHeroOverlay }}; }
        .hero-mobile.hero-grad .hm-visual { position: absolute; inset: 0; height: 100%; aspect-ratio: auto; border-radius: 0; }
        .hero-mobile.hero-grad .hero-bg.active { opacity: 1; }
        .hero-mobile.hero-grad .hm-visual::after {
            content: ''; position: absolute; inset: 0; z-index: 1;
            background: linear-gradient(180deg, {{ $sfHeroOverlay }}00 32%, {{ $sfHeroOverlay }} 86%);
        }
        .hero-mobile.hero-grad .hm-content { position: absolute; left: 16px; right: 16px; bottom: 96px; z-index: 2; }
        .hero-mobile.hero-grad .hm-badge,
        .hero-mobile.hero-grad .hm-card { z-index: 2; }
    }
    @@media (min-width: 768px) {
        .hero-mobile.hero-grad { display: none; }
    }
</style>
@endif
{{-- HERO MOBILE --}}
<div class="wrap">
    <div class="hero-mobile {{ $sfHeroGradClass }}">
        <div class="hm-content">
            <div class="hm-label">{{ $sfHeroLabel }}</div>
            <div class="hm-title"><span class="hero-title-line">{{ $sfHeroTitle1 }}</span><span class="hero-title-line">{{ $sfHeroTitle2 }}</span></div>
            <div class="hm-copy">{{ $sfHeroCopy }}</div>
            <div class="hm-actions">
                <a href="{{ $sfHeroCtaUrl }}" class="btn-dk">
                    {{ $sfHeroCtaLabel }}
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
                <a href="{{ $sfHeroCta2Url }}" class="btn-sk">{{ $sfHeroCta2Label }}</a>
            </div>
        </div>
        <div class="hm-visual">
            @foreach($sfHeroSlotsMobile as $i => $slot)
            <img class="hero-bg {{ $i === 0 ? 'active' : '' }}" src="{{ $slot['url'] }}"
                 @if($slot['focus_mobile'] ?? $slot['focus'] ?? null) style="object-position: {{ $slot['focus_mobile'] ?? $slot['focus'] }};" @endif
                 alt="Foto {{ $i+1 }} {{ $sfBrandName }}">
            @endforeach
            @if($sfHeroBadge)
            <div class="hm-badge">{{ $sfHeroBadge }}</div>
            @else
            <div class="hm-badge">New<br>2026</div>
            @endif
            <a href="{{ route('storefront.products') }}" class="hm-card">
                <div>
                    <div class="hm-card-t">{{ $sfCardTitle }}</div>
                    <div class="hm-card-s">{{ $sfCardSubtitle }}</div>
                </div>
                <div class="hm-card-ic">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </div>
            </a>
        </div>
    </div>
</div>

{{-- HERO DESKTOP --}}
<section class="hero-desktop {{ $sfHeroGradClass }}">
    <div class="hd-content" style="padding-left:max(32px,calc((100vw - 1680px)/2 + 32px));">
        <div class="hd-label">{{ $sfHeroLabel }}</div>
        <h1 class="hd-title"><span class="hero-title-line">{{ $sfHeroTitle1 }}</span><span class="hero-title-line">{{ $sfHeroTitle2 }}</span></h1>
        <div class="hd-copy">{{ $sfHeroCopy }}</div>
        <div class="hd-actions">
            <a href="{{ $sfHeroCtaUrl }}" class="btn-dk">
                {{ $sfHeroCtaLabel }}
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
            <a href="{{ $sfHeroCta2Url }}" class="btn-sk">{{ $sfHeroCta2Label }}</a>
        </div>
    </div>
    <div class="hd-visual">
        @foreach($sfHeroSlots as $i => $slot)
        <img class="hd-photo {{ $i === 0 ? 'active' : '' }}" src="{{ $slot['url'] }}"
             @if($slot['focus']) style="object-position: {{ $slot['focus'] }};" @endif
             alt="Foto {{ $i+1 }} {{ $sfBrandName }}">
        @endforeach
        @if($sfHeroBadge)
        <div class="hd-badge">{{ $sfHeroBadge }}</div>
        @else
        <div class="hd-badge">New<br>2026</div>
        @endif
        <a href="{{ route('storefront.products') }}" class="hd-card">
            <div>
                <div class="hd-card-t">{{ $sfCardTitle }}</div>
                <div class="hd-card-s">{{ $sfCardSubtitle }}</div>
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
            <span class="strip-i">{{ $sfBrandName }} <span class="strip-dot"></span></span>
            <span class="strip-i">Little Things <span class="strip-dot"></span></span>
        @endforeach
    </div>
</div>
@endif {{-- end hero --}}

<div class="wrap">
@foreach($sfSectionOrder as $sfSec)

{{-- ─── CATEGORIES ──────────────────────────────────────────────── --}}
@if($sfSec === 'categories' && $sfViz('categories') && $categories->isNotEmpty())
    @php $sfSecStyle = $sfSectionStyle('categories', ['pt' => null, 'pb' => null]); @endphp
    <section class="sec cat-search" id="kategori" data-sf-sec="categories" style="{{ $sfSectionCss('categories', $sfSecStyle) }}">
        <div class="sec-head cat-head">
            <div>
                <div class="cat-eyebrow">{{ $sfCatEyebrow }}</div>
                <div class="cat-title">{{ $sfCatTitle }}</div>
                <div class="cat-copy">{{ $sfCatCopy }}</div>
            </div>
            <a href="{{ route('storefront.products') }}" class="sec-a">{{ $sfCatAllLabel }}</a>
        </div>
        <div class="cat-grid">
            @foreach($categories->take($sfCatLimit) as $cat)
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
@endif {{-- end categories --}}

{{-- ─── CHANNELS ────────────────────────────────────────────────── --}}
@if($sfSec === 'channels' && $sfViz('channels'))
    @php $sfSecStyle = $sfSectionStyle('channels', ['pt' => null, 'pb' => null]); @endphp
    <section class="sec shop-channels" id="beli" data-sf-sec="channels" style="{{ $sfSectionCss('channels', $sfSecStyle) }}">
        <div class="shop-panel">
            <div class="shop-head">
                <div class="shop-kicker">Channel Belanja</div>
                <div class="shop-title">Mau belanja lewat mana?</div>
                <div class="shop-copy">Website {{ $sfBrandName }} atau marketplace favorit, pilih yang paling nyaman.</div>
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
@endif {{-- end channels --}}

{{-- ─── VALUES ──────────────────────────────────────────────────── --}}
@if($sfSec === 'values' && $sfViz('values'))
    @php
        $sfVals = [];
        foreach ([1,2,3] as $vi) {
            $sfVals[] = [
                'n' => storefront_setting("values.{$vi}_number", str_pad($vi, 2, '0', STR_PAD_LEFT)),
                't' => storefront_setting("values.{$vi}_title",  ['Nyaman','Presisi','Tahan Lama'][$vi-1]),
                'd' => storefront_setting("values.{$vi}_desc",   ''),
            ];
        }
    @endphp
    @php $sfSecStyle = $sfSectionStyle('values', ['pt' => 0, 'pb' => null]); @endphp
    <section class="sec" style="{{ $sfSectionCss('values', $sfSecStyle) }}" data-sf-sec="values">
        <div class="vals">
            @foreach($sfVals as $val)
            <div class="val">
                <div class="val-n">{{ $val['n'] }}</div>
                <div class="val-t">{{ $val['t'] }}</div>
                @if($val['d'])<div class="val-d">{{ $val['d'] }}</div>@endif
            </div>
            @endforeach
        </div>
    </section>
@endif {{-- end values --}}

{{-- ─── PRODUCTS ────────────────────────────────────────────────── --}}
@if($sfSec === 'products' && $sfViz('products'))
    @php $sfSecStyle = $sfSectionStyle('products', ['pt' => null, 'pb' => null]); @endphp
    <section class="sec" id="produk" data-sf-sec="products" style="{{ $sfSectionCss('products', $sfSecStyle) }}">
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
@endif {{-- end products --}}

{{-- ─── CTA ─────────────────────────────────────────────────────── --}}
@if($sfSec === 'cta' && $sfViz('cta'))
    @php $sfSecStyle = $sfSectionStyle('cta', ['pt' => 0, 'pb' => null]); @endphp
    <section class="sec" style="{{ $sfSectionCss('cta', $sfSecStyle) }}" data-sf-sec="cta">
        <div class="cta-blk">
            <div class="cta-blk-t">Ready to<br>Wear Daily.</div>
            <div class="cta-blk-row">
                <a href="{{ route('storefront.products') }}" class="btn-cw">Shop Now</a>
                <a href="#beli" class="btn-co">Marketplace</a>
            </div>
        </div>
    </section>
@endif {{-- end cta --}}

@endforeach {{-- end $sfSectionOrder loop --}}
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Hero slideshow — mobile & desktop punya set foto sendiri,
    // masing-masing berputar sesuai jumlah fotonya.
    function cyclePhotos(els) {
        if (els.length < 2) return;
        var current = 0;
        setInterval(function () {
            els[current].classList.remove('active');
            current = (current + 1) % els.length;
            els[current].classList.add('active');
        }, 5000);
    }
    cyclePhotos(document.querySelectorAll('.hero-bg'));
    cyclePhotos(document.querySelectorAll('.hd-photo'));

    // Live preview dari Admin > Pengaturan Website.
    // Hanya aktif saat halaman home ditampilkan di iframe preview admin.
    function validHex(value) {
        return /^#[0-9a-fA-F]{6}$/.test(value || '') ? value : '';
    }

    function setText(selector, value) {
        document.querySelectorAll(selector).forEach(function (el) {
            el.textContent = value || '';
        });
    }

    function setTitle(draft) {
        document.querySelectorAll('.hd-title, .hm-title').forEach(function (el) {
            el.innerHTML = '';
            var line1 = document.createElement('span');
            var line2 = document.createElement('span');
            line1.className = 'hero-title-line';
            line2.className = 'hero-title-line';
            line1.textContent = draft.title_line1 || '';
            line2.textContent = draft.title_line2 || '';
            el.append(line1, line2);
        });
    }

    function setBadge(value) {
        var text = (value || '').trim();
        document.querySelectorAll('.hd-badge, .hm-badge').forEach(function (el) {
            if (text) {
                el.textContent = text;
            } else {
                el.innerHTML = 'New<br>2026';
            }
        });
    }

    function setCta(selector, label, url) {
        document.querySelectorAll(selector).forEach(function (link) {
            var svg = link.querySelector('svg');
            link.textContent = label || '';
            if (svg) link.appendChild(svg);
            link.setAttribute('href', url || '#');
        });
    }

    function setPhotos(photos, activeIndex) {
        photos = Array.isArray(photos) ? photos.filter(function (p) { return p && p.url; }) : [];
        if (!photos.length) return;
        activeIndex = Math.min(Math.max(parseInt(activeIndex || 0, 10) || 0, 0), photos.length - 1);

        [
            ['.hd-visual', 'hd-photo'],
            ['.hm-visual', 'hero-bg']
        ].forEach(function (pair) {
            var container = document.querySelector(pair[0]);
            var cls = pair[1];
            if (!container) return;
            container.querySelectorAll('.' + cls).forEach(function (img) { img.remove(); });

            photos.slice().reverse().forEach(function (photo, index) {
                var sourceIndex = photos.length - 1 - index;
                var img = document.createElement('img');
                img.className = cls + (sourceIndex === activeIndex ? ' active' : '');
                img.src = photo.url;
                var focus = cls === 'hero-bg'
                    ? (photo.focus_mobile || photo.focus)
                    : (photo.focus_desktop || photo.focus);
                if (/^\d+% \d+%$/.test(focus || '')) img.style.objectPosition = focus;
                container.insertBefore(img, container.firstChild);
            });
        });
    }

    function applyHeroDraft(draft) {
        if (!draft || typeof draft !== 'object') return;

        setText('.hd-label, .hm-label', draft.label);
        setTitle(draft);
        setText('.hd-copy, .hm-copy', draft.copy);
        setBadge(draft.badge_text);
        setCta('.hero-desktop .btn-dk, .hero-mobile .btn-dk', draft.cta_primary_label, draft.cta_primary_url);
        setCta('.hero-desktop .btn-sk, .hero-mobile .btn-sk', draft.cta_secondary_label, draft.cta_secondary_url);
        setText('.hd-card-t, .hm-card-t', draft.card_title);
        setText('.hd-card-s, .hm-card-s', draft.card_subtitle);
        setPhotos(draft.images, draft.active_photo_index);

        var isGrad = draft.style === 'gradient';
        document.querySelectorAll('.hero-desktop, .hero-mobile').forEach(function (hero) {
            hero.classList.toggle('hero-grad', isGrad);
        });

        var overlay = validHex(draft.overlay_color) || '#ffffff';
        var strength = parseInt(draft.overlay_strength || '55', 10);
        strength = Math.min(90, Math.max(30, isNaN(strength) ? 55 : strength));
        var solid = Math.max(strength - 35, 0);
        var height = parseInt(draft.height || '100', 10);
        height = Math.min(100, Math.max(40, isNaN(height) ? 100 : height));
        var titleSize = draft.title_size || 'm';
        var titleStyle = ['two_tone_mask', 'promo_poster', 'clean_sans', 'condensed_impact', 'outline_editorial'].indexOf(draft.title_style) >= 0 ? draft.title_style : 'solid';
        var radius = ({ pill: '999px', rounded: '12px', square: '6px' }[draft.cta_radius] || '');
        var fit = draft.photo_fit === 'contain' ? 'contain' : 'cover';

        var css = ''
            + '.hero-desktop .hd-photo,.hero-mobile .hero-bg{object-fit:' + fit + '}'
            + '.hd-label,.hm-label{color:' + (validHex(draft.label_color) || 'var(--mid)') + '}'
            + '.hd-label::before,.hm-label::before{background:' + (validHex(draft.label_color) || 'var(--ink)') + '}'
            + '.hd-title,.hm-title{color:' + (validHex(draft.title_color) || 'var(--ink)') + '}'
            + '.hd-copy,.hm-copy{color:' + (validHex(draft.copy_color) || 'var(--mid)') + '}'
            + '.hd-badge,.hm-badge{background:' + (validHex(draft.badge_bg) || 'var(--white)') + ';color:' + (validHex(draft.badge_color) || 'var(--ink)') + '}'
            + '.hero-desktop .btn-dk,.hero-mobile .btn-dk{background:' + (validHex(draft.cta_bg) || 'var(--ink)') + ';color:' + (validHex(draft.cta_color) || 'var(--white)') + '}'
            + '.hero-desktop .btn-sk,.hero-mobile .btn-sk{color:' + (validHex(draft.cta2_color) || 'var(--ink)') + ';border-color:' + (validHex(draft.cta2_color) || 'var(--line)') + '}';
        if (radius) {
            css += '.hero-desktop .btn-dk,.hero-mobile .btn-dk,.hero-desktop .btn-sk,.hero-mobile .btn-sk{border-radius:' + radius + '}';
        }

        var solidDesktop = ({ xs: '60px', s: '72px', m: '96px', l: '112px', xl: '128px' }[titleSize] || '96px');
        var solidMobile = ({ xs: '44px', s: '54px', m: '70px', l: '82px', xl: '92px' }[titleSize] || '70px');
        css += '.hd-title{font-size:' + solidDesktop + '}.hm-title{font-size:' + solidMobile + '}';
        if (titleStyle === 'two_tone_mask') {
            var titleColor = validHex(draft.title_color) || 'var(--ink)';
            css += '.hero-title-line{display:block}'
                + '.hd-title .hero-title-line:nth-child(2),.hm-title .hero-title-line:nth-child(2){color:transparent;background:linear-gradient(90deg,' + titleColor + ' 0 52%,var(--mid) 52% 100%);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;-webkit-text-stroke:.012em ' + titleColor + ';text-shadow:none}';
        } else if (titleStyle === 'promo_poster') {
            var promoColor = validHex(draft.title_color) || 'var(--ink)';
            var promoCopyColor = validHex(draft.copy_color) || 'var(--ink)';
            var promoDesktop = ({ xs: '44px', s: '52px', m: '64px', l: '76px', xl: '88px' }[titleSize] || '64px');
            var promoMobile = ({ xs: '30px', s: '36px', m: '44px', l: '52px', xl: '58px' }[titleSize] || '44px');
            css += '.hero-title-line{display:block;color:inherit;background:none;-webkit-background-clip:initial;background-clip:initial;-webkit-text-fill-color:currentColor;-webkit-text-stroke:0}'
                + '.hd-title,.hm-title{font-family:var(--font-body);font-weight:900;text-transform:none;letter-spacing:0;line-height:1.16;color:' + promoColor + '}'
                + '.hd-title{font-size:' + promoDesktop + ';max-width:560px;margin-bottom:20px}'
                + '.hm-title{font-size:' + promoMobile + ';max-width:340px;margin-bottom:18px}'
                + '.hd-title::after,.hm-title::after{content:"";display:block;width:min(320px,100%);height:2px;background:currentColor;margin-top:24px}'
                + '.hd-copy{max-width:520px;margin-top:0;font-size:20px;line-height:1.55;color:' + promoCopyColor + '}'
                + '.hm-copy{max-width:320px;margin-top:0;font-size:15px;line-height:1.6;color:' + promoCopyColor + '}'
                + '.hd-actions .btn-dk,.hm-actions .btn-dk{min-height:48px;padding:0 26px;font-size:17px}'
                + '.hd-actions .btn-sk,.hm-actions .btn-sk{min-height:48px;padding:0 22px;font-size:14px}';
        } else if (titleStyle === 'clean_sans') {
            var cleanColor = validHex(draft.title_color) || 'var(--ink)';
            css += '.hero-title-line{display:block;color:inherit;background:none;-webkit-background-clip:initial;background-clip:initial;-webkit-text-fill-color:currentColor;-webkit-text-stroke:0}'
                + '.hd-title,.hm-title{font-family:var(--font-body);font-weight:900;text-transform:none;line-height:1.04;letter-spacing:0;color:' + cleanColor + '}';
        } else if (titleStyle === 'condensed_impact') {
            var impactColor = validHex(draft.title_color) || 'var(--ink)';
            css += '.hero-title-line{display:block;color:inherit;background:none;-webkit-background-clip:initial;background-clip:initial;-webkit-text-fill-color:currentColor;-webkit-text-stroke:0}'
                + '.hd-title,.hm-title{font-family:var(--font-display);font-weight:900;text-transform:uppercase;line-height:.82;letter-spacing:.01em;color:' + impactColor + '}';
        } else if (titleStyle === 'outline_editorial') {
            var outlineColor = validHex(draft.title_color) || 'var(--ink)';
            css += '.hero-title-line{display:block;background:none;-webkit-background-clip:initial;background-clip:initial}'
                + '.hd-title,.hm-title{color:transparent;-webkit-text-fill-color:transparent;-webkit-text-stroke:.018em ' + outlineColor + ';text-transform:uppercase;letter-spacing:.01em}'
                + '.hd-title .hero-title-line:nth-child(1),.hm-title .hero-title-line:nth-child(1){color:' + outlineColor + ';-webkit-text-fill-color:currentColor;-webkit-text-stroke:0}';
        } else {
            css += '.hero-title-line{display:block;color:inherit;background:none;-webkit-background-clip:initial;background-clip:initial;-webkit-text-fill-color:currentColor;-webkit-text-stroke:0;text-shadow:inherit}';
        }
        if (height < 100) css += '.hero-desktop{min-height:calc(' + height + 'svh - 56px)}.hero-mobile.hero-grad{min-height:' + Math.round(height * 0.76) + 'svh!important}';

        if (isGrad) {
            css += '.hero-desktop.hero-grad{position:relative;grid-template-columns:1fr}'
                + '.hero-desktop.hero-grad .hd-visual{position:absolute;inset:0;background:' + overlay + '}'
                + '.hero-desktop.hero-grad .hd-photo.active{opacity:1}'
                + '.hero-desktop.hero-grad .hd-visual::after{content:"";position:absolute;inset:0;z-index:1;background:linear-gradient(90deg,' + overlay + ' ' + solid + '%,' + overlay + '00 ' + strength + '%)}'
                + '.hero-desktop.hero-grad .hd-content{position:relative;z-index:2;max-width:620px}'
                + '.hero-desktop.hero-grad .hd-badge,.hero-desktop.hero-grad .hd-card{z-index:2}'
                + '.hero-desktop.hero-grad .hd-card{left:48%}'
                + '@media (max-width:767.98px){'
                + '.hero-mobile.hero-grad{position:relative;display:block;min-height:76svh;border-radius:18px;overflow:hidden;background:' + overlay + '}'
                + '.hero-mobile.hero-grad .hm-visual{position:absolute;inset:0;height:100%;aspect-ratio:auto;border-radius:0}'
                + '.hero-mobile.hero-grad .hero-bg.active{opacity:1}'
                + '.hero-mobile.hero-grad .hm-visual::after{content:"";position:absolute;inset:0;z-index:1;background:linear-gradient(180deg,' + overlay + '00 32%,' + overlay + ' 86%)}'
                + '.hero-mobile.hero-grad .hm-content{position:absolute;left:16px;right:16px;bottom:96px;z-index:2}'
                + '.hero-mobile.hero-grad .hm-badge,.hero-mobile.hero-grad .hm-card{z-index:2}'
                + '}@media (min-width:768px){.hero-mobile.hero-grad{display:none}}';
        }

        var style = document.getElementById('gfid-hero-preview-style');
        if (!style) {
            style = document.createElement('style');
            style.id = 'gfid-hero-preview-style';
            document.head.appendChild(style);
        }
        style.textContent = css;
    }

    window.addEventListener('message', function (event) {
        if (event.origin !== window.location.origin) return;
        if (!event.data || event.data.type !== 'gfid:hero-preview') return;
        applyHeroDraft(event.data.settings);
    });

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
