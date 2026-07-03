@extends('storefront.layouts.app')

@section('title', storefront_setting('branding.brand_name', 'Greatfit') . ' — Produk')

@push('styles')
<style>
    .wrap { width: min(1440px, calc(100% - 64px)); margin: 0 auto; }

    /* ── PAGE HEADER ────────────────────────────────────────────────── */
    .page-head { padding: 36px 0 0; }
    .page-kicker {
        font-size: 10px; font-weight: 900; letter-spacing: .14em;
        text-transform: uppercase; color: var(--mid);
        display: flex; align-items: center; gap: 8px;
        margin-bottom: 10px;
    }
    .page-kicker::before { content:''; width:18px; height:2px; background:var(--mid); display:block; }
    .page-title {
        font-family: var(--font-display);
        font-size: clamp(44px, 7vw, 88px);
        font-weight: 800; line-height: .88;
        text-transform: uppercase; letter-spacing: -.01em;
        margin-bottom: 12px;
    }
    .page-count {
        font-size: 12px; color: var(--mid); font-weight: 700;
        margin-bottom: 0;
    }

    /* ── FILTER BAR ─────────────────────────────────────────────────── */
    .filter-bar { margin-top: 28px; }

    /* Category row — underline tabs */
    .fcat-row {
        border-bottom: 1px solid var(--line);
        margin-bottom: 0;
    }
    .fcat-scroll {
        display: flex; align-items: center; gap: 0;
        overflow-x: auto; scrollbar-width: none;
        padding-bottom: 0;
    }
    .fcat-scroll::-webkit-scrollbar { display: none; }
    .fcat-opt {
        flex: 0 0 auto;
        height: 40px; padding: 0 16px;
        border: 0; background: transparent;
        font-family: var(--font-body);
        font-size: 12px; font-weight: 800; letter-spacing: .02em;
        color: var(--mid);
        display: inline-flex; align-items: center;
        text-decoration: none;
        position: relative;
        transition: color .15s;
        white-space: nowrap;
    }
    .fcat-opt:hover { color: var(--ink); }
    .fcat-opt.active {
        color: var(--ink);
    }
    .fcat-opt.active::after {
        content:'';
        position: absolute; left: 16px; right: 16px; bottom: -1px;
        height: 2px; border-radius: 999px;
        background: var(--ink);
    }

    /* Secondary row — desktop: labels + pills, mobile: icon + pills only */
    .fsec-row {
        display: flex; align-items: center; gap: 0;
        padding: 14px 0 0;
    }

    /* Desktop label style */
    .fsec-label {
        font-size: 10px; font-weight: 900; letter-spacing: .1em;
        text-transform: uppercase; color: var(--mid);
        flex: 0 0 auto; margin-right: 8px;
    }
    .fsec-group { display: flex; align-items: center; gap: 0; }
    .fsec-divider { width: 1px; height: 20px; background: var(--line); margin: 0 14px; flex: 0 0 auto; }

    .fsec-scroll {
        display: flex; align-items: center; gap: 6px;
        overflow-x: auto; scrollbar-width: none; flex: 1;
        padding: 2px 0;
    }
    .fsec-scroll::-webkit-scrollbar { display: none; }

    .fsec-opt {
        flex: 0 0 auto;
        height: 28px; padding: 0 12px;
        border-radius: 999px;
        border: 1.5px solid var(--line);
        background: transparent;
        font-family: var(--font-body);
        font-size: 11px; font-weight: 800;
        color: var(--mid);
        text-decoration: none;
        display: inline-flex; align-items: center;
        transition: color .15s, border-color .15s, background .15s;
        white-space: nowrap;
    }
    .fsec-opt:hover { border-color: var(--ink); color: var(--ink); }
    .fsec-opt.active { background: var(--ink); border-color: var(--ink); color: var(--white); }
    .fsec-reset {
        flex: 0 0 auto;
        height: 28px; padding: 0 12px;
        border-radius: 999px;
        border: 1.5px solid transparent;
        background: transparent;
        font-family: var(--font-body);
        font-size: 11px; font-weight: 800;
        color: var(--mid);
        text-decoration: none;
        display: inline-flex; align-items: center; gap: 4px;
        transition: color .15s;
        white-space: nowrap;
    }
    .fsec-reset:hover { color: var(--ink); }

    /* Mobile filter icon button (hidden on desktop) */
    .fsec-icon-btn {
        display: none;
        flex: 0 0 auto;
        width: 30px; height: 30px;
        border-radius: 50%;
        border: 1.5px solid var(--line);
        background: var(--white);
        color: var(--mid);
        align-items: center; justify-content: center;
        cursor: default; pointer-events: none; /* just indicator, not toggle */
        position: relative;
        margin-right: 8px;
        flex-shrink: 0;
    }
    .fsec-icon-btn.has-filter {
        border-color: var(--ink);
        background: var(--ink); color: var(--white);
    }
    .fsec-icon-badge {
        position: absolute; top: -5px; right: -5px;
        width: 15px; height: 15px; border-radius: 50%;
        background: var(--accent); color: var(--ink);
        font-size: 8px; font-weight: 900;
        display: grid; place-items: center;
        border: 2px solid var(--white);
    }
    /* Hide size "Semua" on mobile — Big Size works as a toggle */
    .fsec-size-all { /* always visible on desktop */ }

    /* Filter summary line */
    .filter-summary {
        margin-top: 14px; margin-bottom: 22px;
        font-size: 12px; color: var(--mid); font-weight: 700;
    }
    .filter-summary strong { color: var(--ink); font-weight: 900; }

    /* ── PRODUCTS GRID ──────────────────────────────────────────────── */
    .products {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        padding-bottom: 48px;
    }

    /* Card */
    .pc {
        border-radius: 18px; overflow: hidden;
        background: var(--white);
        border: 1px solid var(--line);
        display: block;
        transition: transform .2s, box-shadow .2s, border-color .2s;
    }
    .pc:hover { transform: translateY(-3px); border-color: #d8d8d8; box-shadow: 0 14px 34px rgba(0,0,0,.07); }
    .pc-img { aspect-ratio: 1 / 1.1; position: relative; background: var(--soft); overflow: hidden; }
    .pc-img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .4s ease; }
    .pc:hover .pc-img img { transform: scale(1.03); }
    .pc-lbl {
        position: absolute; top: 10px; left: 10px; z-index: 3;
        background: var(--ink); color: var(--white);
        font-size: 9px; font-weight: 900; letter-spacing: .04em; text-transform: uppercase;
        padding: 4px 9px; border-radius: var(--radius-pill);
    }
    .pc-lbl.badge-trending { background: #0f172a; }
    .pc-lbl.badge-new      { background: #6366f1; }
    .pc-lbl.badge-jumbo    { background: #7c3aed; }
    .pc-stock-badge {
        position: absolute; top: 10px; right: 10px; z-index: 3;
        background: rgba(0,0,0,.72); color: #fff;
        font-size: 9px; font-weight: 900; letter-spacing: .03em;
        padding: 4px 8px; border-radius: var(--radius-pill);
        backdrop-filter: blur(4px);
    }
    .pc-stock-badge.out { background: rgba(10,10,10,.85); }
    .pc-body { padding: 12px 13px 13px; }
    .pc-aud {
        display: inline-block;
        font-size: 9px; font-weight: 900; letter-spacing: .07em; text-transform: uppercase;
        margin-bottom: 5px;
    }
    .pc-name { font-size: 13px; font-weight: 850; line-height: 1.3; }
    .pc-price { font-size: 15px; font-weight: 900; margin-top: 6px; }
    .pc-foot {
        display: flex; align-items: center; justify-content: space-between; gap: 6px;
        margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--line);
    }
    .pc-stock-line { font-size: 10px; color: var(--mid); font-weight: 800; }
    .pc-stock-line.low { color: #f97316; }
    .pc-stock-line.out { color: #b91c1c; }
    .pc-arr {
        width: 26px; height: 26px; border-radius: 50%;
        background: var(--soft); color: var(--ink);
        display: grid; place-items: center;
        flex: 0 0 auto; transition: background .15s;
    }
    .pc:hover .pc-arr { background: var(--ink); color: var(--white); }

    /* ── RESPONSIVE ─────────────────────────────────────────────────── */
    @@media (min-width: 720px) {
        .page-head { padding: 44px 0 0; }
        .filter-bar { margin-top: 32px; }
        .products { grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 72px; }
        .pc-body { padding: 14px 15px 15px; }
        .pc-img { aspect-ratio: 1 / 1.12; }
    }
    @@media (min-width: 1280px) {
        .products { grid-template-columns: repeat(4, 1fr); gap: 20px; }
    }
    @@media (min-width: 720px) and (max-width: 1080px) {
        .wrap { width: min(100% - 56px, 940px); }
        .products { grid-template-columns: repeat(3, 1fr); gap: 16px; }
    }

    @@media (max-width: 719px) {
        .wrap { width: min(520px, calc(100% - 28px)); }
        .page-head { padding: 24px 0 0; }
        .page-title { font-size: 46px; margin-bottom: 8px; }
        .page-kicker { margin-bottom: 8px; }

        /* Mobile: show filter icon (indicator only), hide desktop labels */
        .fsec-icon-btn { display: inline-flex; }
        .fsec-label { display: none; }
        .fsec-divider { display: none; }
        .fsec-row { padding-top: 10px; align-items: center; }

        /* Hide size "Semua" on mobile — Big Size acts as toggle chip */
        .fsec-size-all { display: none; }

        /* Make size group margin work without label */
        .fsec-group:first-of-type { margin-right: 4px; }

        /* Filter summary */
        .filter-summary { margin-top: 12px; margin-bottom: 16px; }

        /* Cards */
        .pc-img { aspect-ratio: 1 / 1; }
        .pc-body { padding: 10px 11px 11px; }
        .pc-name { font-size: 12.5px; }
        .pc-price { font-size: 14px; margin-top: 5px; }
    }
</style>
@endpush

@php
    $navActive   = 'products';
    $sfBrandName = storefront_setting('branding.brand_name', 'Greatfit');
    $activeFilterCount = (int)($activeType === 'jumbo') + (int)(!empty($activeAudience));
    $audienceChipColors = ['pria'=>'#1d4ed8','wanita'=>'#be185d','anak'=>'#d97706','olahraga'=>'#15803d','unisex'=>'#6b7280'];
    $hasActiveFilter = $activeCategory || $activeAudience || $activeType;
@endphp

@section('content')
<div class="wrap">

    {{-- ── PAGE HEADER ──────────────────────────────────────────────── --}}
    <div class="page-head">
        <div class="page-kicker">Katalog {{ $sfBrandName }}</div>
        <div class="page-title">
            @if($activeType === 'jumbo')
                Big Size
                @if($activeCategory && $categories->firstWhere('slug', $activeCategory))
                    <span style="color:var(--mid)">/ {{ $categories->firstWhere('slug', $activeCategory)->name }}</span>
                @endif
            @elseif($activeAudience && isset($audienceOptions[$activeAudience]))
                {{ $audienceOptions[$activeAudience] }}
                @if($activeCategory && $categories->firstWhere('slug', $activeCategory))
                    <span style="color:var(--mid)">/ {{ $categories->firstWhere('slug', $activeCategory)->name }}</span>
                @endif
            @elseif($activeCategory && $categories->firstWhere('slug', $activeCategory))
                {{ $categories->firstWhere('slug', $activeCategory)->name }}
            @else
                Semua<br>Produk
            @endif
        </div>
        <div class="page-count">{{ count($products) }} produk tersedia</div>
    </div>

    {{-- ── FILTER BAR ───────────────────────────────────────────────── --}}
    @php
        $baseParams = array_filter(['audience' => $activeAudience, 'type' => $activeType]);
    @endphp
    <div class="filter-bar">

        {{-- Category tabs --}}
        @if($categories->isNotEmpty())
        <div class="fcat-row">
            <div class="fcat-scroll">
                <a href="{{ route('storefront.products', $baseParams) }}"
                   class="fcat-opt{{ !$activeCategory ? ' active' : '' }}">Semua</a>
                @foreach($categories as $cat)
                <a href="{{ route('storefront.products', array_filter(['kategori' => $cat->slug, 'audience' => $activeAudience, 'type' => $activeType])) }}"
                   class="fcat-opt{{ $activeCategory === $cat->slug ? ' active' : '' }}">{{ $cat->name }}</a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Secondary filters --}}
        <div class="fsec-row">

            {{-- Mobile: filter icon button with active badge --}}
            <button class="fsec-icon-btn{{ $activeFilterCount > 0 ? ' has-filter' : '' }}" type="button" aria-label="Filter">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
                @if($activeFilterCount > 0)
                <span class="fsec-icon-badge">{{ $activeFilterCount }}</span>
                @endif
            </button>

            <div class="fsec-scroll">
                {{-- Desktop label: Ukuran --}}
                <span class="fsec-label">Ukuran</span>

                {{-- Size options --}}
                <div class="fsec-group">
                    <a href="{{ route('storefront.products', array_filter(['kategori' => $activeCategory, 'audience' => $activeAudience])) }}"
                       class="fsec-opt fsec-size-all{{ !$activeType ? ' active' : '' }}">Semua</a>
                    <a href="{{ route('storefront.products', array_filter($activeType === 'jumbo' ? ['kategori' => $activeCategory, 'audience' => $activeAudience] : ['type' => 'jumbo', 'kategori' => $activeCategory, 'audience' => $activeAudience])) }}"
                       class="fsec-opt{{ $activeType === 'jumbo' ? ' active' : '' }}" style="margin-left:6px;">Big Size</a>
                </div>

                {{-- Divider (desktop) / visual gap (mobile) --}}
                <span class="fsec-divider"></span>

                {{-- Desktop label: Untuk --}}
                <span class="fsec-label">Untuk</span>

                {{-- Audience options --}}
                <div class="fsec-group" style="gap:6px;">
                    <a href="{{ route('storefront.products', array_filter(['kategori' => $activeCategory, 'type' => $activeType])) }}"
                       class="fsec-opt{{ !$activeAudience ? ' active' : '' }}">Semua</a>
                    @foreach($audienceOptions as $val => $lbl)
                    <a href="{{ route('storefront.products', array_filter(['audience' => $val, 'kategori' => $activeCategory, 'type' => $activeType])) }}"
                       class="fsec-opt{{ $activeAudience === $val ? ' active' : '' }}">{{ $lbl }}</a>
                    @endforeach
                </div>

                {{-- Reset --}}
                @if($hasActiveFilter)
                <a href="{{ route('storefront.products') }}" class="fsec-reset">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Reset
                </a>
                @endif
            </div>
        </div>
    </div>

    <div class="filter-summary">
        <strong>{{ count($products) }}</strong> produk ditemukan
    </div>

    {{-- ── PRODUCT GRID ─────────────────────────────────────────────── --}}
    <div class="products">
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
            $stockStatus    = $p['stock_status'] ?? 'ok';
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
                <span class="pc-aud" style="color:{{ $audienceChipColors[$p['audience']] ?? '#6b7280' }}">
                    {{ $p['audience_label'] }}
                </span>
                @endif
                <div class="pc-name">{{ $p['name'] }}</div>
                <div class="pc-price">Rp{{ number_format($p['price'], 0, ',', '.') }}</div>
                <div class="pc-foot">
                    <span class="pc-stock-line {{ $stockStatus === 'out' ? 'out' : ($stockStatus === 'low' ? 'low' : '') }}">
                        {{ $stockStatus === 'out' ? 'Stok habis' : ($stockStatus === 'low' ? 'Tersisa ' . $availableStock . ' pcs' : 'Stok tersedia') }}
                    </span>
                    <span class="pc-arr">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </span>
                </div>
            </div>
        </a>
        @endforeach
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    // On mobile the Big Size chip links back to itself when active (toggle off)
    // Nothing else needed — secondary row is always visible
})();
</script>
@endpush
