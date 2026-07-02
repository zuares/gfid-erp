@extends('storefront.layouts.app')

@section('title', 'Produk — Greatfit')

@push('styles')
<style>
    .wrap { width: min(1440px, calc(100% - 64px)); margin: 0 auto; }

    /* PAGE HEADER */
    .page-head { padding: 28px 0 22px; margin-bottom: 18px; }
    .breadcrumb { font-size: 11px; color: var(--mid); font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 7px; letter-spacing: .02em; }
    .breadcrumb a:hover { color: var(--ink); }
    .page-kicker { font-size: 10px; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; color: var(--mid); margin-bottom: 8px; }
    .page-title { font-size: clamp(28px, 4vw, 46px); font-weight: 900; letter-spacing: -.04em; line-height: .98; }
    .page-subtitle { max-width: 520px; margin-top: 10px; color: var(--mid); font-size: 13px; font-weight: 650; line-height: 1.65; }
    .page-count { font-size: 12px; color: var(--mid); font-weight: 700; margin-top: 10px; }
    .filter-panel { margin-top: 22px; min-width: 0; padding-top: 2px; }
    .filter-group { min-width: 0; }
    .filter-label { display: block; font-size: 10px; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; color: var(--mid); margin-bottom: 8px; }
    .filter-scroll { display: flex; align-items: center; gap: 7px; overflow-x: auto; padding: 1px; scrollbar-width: none; }
    .filter-scroll::-webkit-scrollbar { display: none; }
    .filter-option { flex: 0 0 auto; min-height: 30px; padding: 0 2px; border-radius: 0; border: 0; background: transparent; color: var(--mid); display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 850; transition: color .15s; position: relative; }
    .filter-option:hover { color: var(--ink); }
    .filter-option.active { color: var(--ink); }
    .filter-option.active::after { content: ""; position: absolute; left: 0; right: 0; bottom: -8px; height: 2px; border-radius: 999px; background: var(--ink); }
    .filter-category { margin-top: 0; padding-bottom: 12px; border-bottom: 1px solid var(--line); }
    .filter-category .filter-option + .filter-option { margin-left: 14px; }
    .filter-secondary { margin-top: 14px; display: flex; flex-wrap: wrap; gap: 18px; align-items: flex-start; }
    .filter-secondary .filter-group { display: flex; align-items: center; gap: 8px; overflow: hidden; }
    .filter-secondary .filter-label { margin: 0; flex: 0 0 auto; }
    .filter-reset { order: 3; min-height: 30px; padding: 0; border: 0; background: transparent; color: var(--mid); display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 850; white-space: nowrap; text-decoration: none; }
    .filter-reset:hover { color: var(--ink); }
    .filter-summary { margin-top: 12px; display: flex; align-items: center; justify-content: space-between; gap: 12px; font-size: 12px; color: var(--mid); font-weight: 750; }
    .filter-summary strong { color: var(--ink); font-weight: 900; }

    /* PRODUCTS GRID */
    .products { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; padding-bottom: 34px; }
    .pc { border-radius: 16px; overflow: hidden; background: var(--white); border: 1px solid var(--line); display: block; transition: transform .2s, box-shadow .2s, border-color .2s; }
    .pc:hover { transform: translateY(-3px); border-color: #dadada; box-shadow: 0 14px 34px rgba(0,0,0,.06); }
    .pc-img { aspect-ratio: 1; position: relative; background: var(--soft); overflow: hidden; }
    .pc-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pc-lbl { position: absolute; top: 10px; left: 10px; z-index: 3; background: var(--ink); color: var(--white); font-size: 10px; font-weight: 800; padding: 4px 8px; border-radius: var(--radius-pill); }
    .pc-lbl.badge-trending { background: #0f172a; }
    .pc-lbl.badge-new      { background: #6366f1; }
    .pc-lbl.badge-jumbo    { background: #7c3aed; }
    .pc-stock-badge { position: absolute; top: 10px; right: 10px; z-index: 3; background: #f97316; color: #fff; font-size: 9px; font-weight: 900; padding: 3px 7px; border-radius: var(--radius-pill); letter-spacing: .03em; }
    .pc-stock-badge.out { background: #111; }
    .pc-body { padding: 12px; }
    .pc-name { font-size: 13px; font-weight: 850; line-height: 1.3; min-height: 34px; }
    .pc-meta { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-top: 7px; }
    .pc-price { font-size: 15px; font-weight: 900; white-space: nowrap; }
    .pc-sold { font-size: 11px; color: var(--mid); }
    .pc-desc { margin-top: 8px; color: var(--mid); font-size: 11px; font-weight: 600; line-height: 1.45; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .pc-options { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--line); }
    .pc-colors { display: flex; align-items: center; gap: 4px; min-width: 0; }
    .pc-dot { width: 13px; height: 13px; border-radius: 50%; box-shadow: inset 0 0 0 1px rgba(0,0,0,.14); }
    .pc-sizes { font-size: 10px; color: var(--mid); font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pc-stock-line { margin-top: 7px; font-size: 10px; color: var(--mid); font-weight: 800; }
    .pc-stock-line.low { color: #f97316; }
    .pc-stock-line.out { color: #b91c1c; }
    .pc-cta { display: flex; align-items: center; justify-content: space-between; gap: 6px; height: 36px; margin-top: 10px; padding: 0 11px 0 12px; border-radius: 10px; background: var(--soft); color: var(--ink); font-size: 11px; font-weight: 900; }
    .pc:hover .pc-cta { background: var(--ink); color: var(--white); }

    @@media (min-width: 720px) {
        .products { grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 72px; }
        .page-head { padding: 38px 0 26px; margin-bottom: 22px; }
        .filter-panel { margin-top: 24px; }
        .pc-body { padding: 14px; }
        .pc-img { aspect-ratio: 1 / 1.08; }
    }
    @@media (min-width: 1280px) {
        .products { grid-template-columns: repeat(4, 1fr); gap: 20px; }
    }
    @@media (min-width: 720px) and (max-width: 1080px) {
        .wrap { width: min(100% - 56px, 920px); }
        .page-head { padding: 34px 0 24px; margin-bottom: 24px; }
        .filter-secondary { gap: 16px; }
        .filter-secondary .filter-group { display: block; }
        .filter-secondary .filter-label { margin-bottom: 8px; }
        .products { grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .pc-desc { margin-top: 10px; }
        .pc-options { margin-top: 12px; padding-top: 12px; }
        .pc-cta { margin-top: 12px; }
    }
    @@media (max-width: 719px) {
        .wrap { width: min(520px, calc(100% - 28px)); }
        .breadcrumb { display: none; }
        .page-head { padding: 26px 0 22px; margin-bottom: 18px; }
        .page-kicker { font-size: 9px; margin-bottom: 7px; }
        .page-title { font-size: 28px; }
        .page-subtitle { margin-top: 8px; font-size: 12px; }
        .filter-panel { margin-top: 18px; }
        .filter-option { min-height: 32px; padding: 0 10px; font-size: 11px; }
        .filter-option.active::after { display: none; }
        .filter-option.active { background: var(--ink); color: var(--white); border-radius: var(--radius-pill); }
        .filter-category { padding-bottom: 10px; }
        .filter-category .filter-option { min-height: 32px; padding: 0 11px; }
        .filter-category .filter-option + .filter-option { margin-left: 0; }
        .filter-secondary { display: grid; grid-template-columns: 1fr; gap: 10px; margin-top: 12px; }
        .filter-secondary .filter-group { display: block; }
        .filter-secondary .filter-label { margin-bottom: 8px; }
        .filter-summary { align-items: flex-start; flex-direction: column; gap: 6px; }
        .pc-body { padding: 12px; }
        .pc-desc { margin-top: 9px; }
        .pc-options { margin-top: 12px; padding-top: 12px; }
        .pc-meta { display: block; }
        .pc-sold { margin-top: 2px; }
    }
</style>
@endpush

@php $navActive = 'products'; @endphp

@section('content')
<div class="wrap">

    <div class="page-head">
        <div class="breadcrumb">
            <a href="{{ route('storefront.home') }}">Home</a>
            <span>/</span>
            <span>Produk</span>
        </div>
        <div class="page-kicker">Katalog Greatfit</div>
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
        <div class="page-subtitle">Pilih outfit yang nyaman dipakai setiap hari, dari potongan simpel sampai ukuran yang lebih fleksibel.</div>
        <div class="page-count">{{ count($products) }} produk tersedia</div>

        @php
            $audienceChipColors = ['pria'=>'#1d4ed8','wanita'=>'#be185d','anak'=>'#d97706','olahraga'=>'#15803d','unisex'=>'#6b7280'];
            $hasActiveFilter = $activeCategory || $activeAudience || $activeType;
            $baseParams = array_filter(['audience' => $activeAudience, 'type' => $activeType]);
        @endphp

        <div class="filter-panel" aria-label="Filter produk">
            @if($categories->isNotEmpty())
            <div class="filter-group filter-category">
                <div class="filter-scroll">
                    <a href="{{ route('storefront.products', $baseParams) }}" class="filter-option{{ !$activeCategory ? ' active' : '' }}">Semua</a>
                    @foreach($categories as $cat)
                    <a href="{{ route('storefront.products', array_filter(['kategori' => $cat->slug, 'audience' => $activeAudience, 'type' => $activeType])) }}"
                       class="filter-option{{ $activeCategory === $cat->slug ? ' active' : '' }}">{{ $cat->name }}</a>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="filter-secondary">
                <div class="filter-group filter-size">
                    <span class="filter-label">Ukuran</span>
                    <div class="filter-scroll">
                        <a href="{{ route('storefront.products', array_filter(['kategori' => $activeCategory, 'audience' => $activeAudience])) }}"
                           class="filter-option{{ !$activeType ? ' active' : '' }}">Semua</a>
                        <a href="{{ route('storefront.products', array_filter(['type' => 'jumbo', 'kategori' => $activeCategory, 'audience' => $activeAudience])) }}"
                           class="filter-option{{ $activeType === 'jumbo' ? ' active' : '' }}">Big Size</a>
                    </div>
                </div>
                <div class="filter-group filter-audience">
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
        @php
            // ── Badge kiri atas (prioritas: label manual > trending > baru) ──
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
                <span class="pc-lbl {{ $badgeClass }}">{{ $badgeText }}</span>
                @endif
                @if($stockStatus === 'out')
                <span class="pc-stock-badge out">Stok Habis</span>
                @elseif($stockStatus === 'low')
                <span class="pc-stock-badge">Stok Terbatas</span>
                @endif
                <img src="{{ storefront_img($p['img']) }}" alt="{{ $p['name'] }}" loading="lazy">
            </div>
            <div class="pc-body">
                @if(!empty($p['audience_label']))
                <div style="margin-bottom:5px;">
                    <span style="font-size:10px;font-weight:800;padding:2px 7px;border-radius:999px;background:{{ $audienceChipColors[$p['audience']] ?? '#6b7280' }}18;color:{{ $audienceChipColors[$p['audience']] ?? '#6b7280' }};">
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
                <div class="pc-stock-line {{ $stockStatus === 'out' ? 'out' : ($stockStatus === 'low' ? 'low' : '') }}">
                    {{ $stockStatus === 'out' ? 'Belum tersedia' : ($stockStatus === 'low' ? 'Tersisa ' . $availableStock . ' pcs' : 'Stok tersedia') }}
                </div>
                <div class="pc-options">
                    <div class="pc-colors">
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

</div>
@endsection
