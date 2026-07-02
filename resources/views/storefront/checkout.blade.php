@extends('storefront.layouts.checkout')

@section('title', 'Checkout — ' . storefront_setting('branding.brand_name', 'Greatfit'))

@section('nav-right')
{{-- Checkout nav intentionally has no links --}}
@endsection

@push('styles')
<style>
    :root { --bg: #f5f2ee; --line: #e8e4df; --soft: #f0ede8; --action: #16a34a; --action-dark: #12823c; --action-soft: #ecfdf3; }
    body { background: var(--bg); }
    .wrap { width: min(680px, calc(100% - 32px)); margin: 0 auto; }

    .desktop-breadcrumb { display: none; font-size: 12px; color: var(--mid); font-weight: 500; padding: 18px 0 0; align-items: center; gap: 6px; }
    .desktop-breadcrumb a:hover { color: var(--ink); }
    .page-head { padding: 18px 0 14px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; }
    .page-title { font-size: 20px; font-weight: 900; letter-spacing: -.02em; }
    .page-count { font-size: 12px; color: var(--mid); margin-top: 3px; font-weight: 600; }
    .back-link { font-size: 12px; font-weight: 700; color: var(--mid); display: flex; align-items: center; gap: 4px; }
    .back-link:hover { color: var(--ink); }

    .checkout-section { background: var(--white); border: 1px solid var(--line); border-radius: 16px; padding: 16px; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
    .checkout-title { font-size: 10px; font-weight: 900; letter-spacing: .10em; text-transform: uppercase; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; color: var(--mid); }
    .checkout-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 10px 0; border-top: 1px solid var(--line); }
    .checkout-row:first-of-type { border-top: 0; padding-top: 0; }
    .checkout-main { min-width: 0; flex: 1; }
    .checkout-name { font-size: 13px; font-weight: 800; line-height: 1.35; color: var(--ink); }
    .checkout-note { font-size: 11px; color: var(--mid); font-weight: 600; line-height: 1.45; margin-top: 2px; }
    .checkout-chip { height: 28px; padding: 0 12px; border-radius: 999px; background: var(--ink); border: none; display: inline-flex; align-items: center; font-size: 10px; font-weight: 900; color: var(--white); flex-shrink: 0; letter-spacing: .04em; }
    .checkout-chip:hover { opacity: .85; }
    .checkout-alert { background: #fff7ed; color: #9a3412; border: 1px solid #fed7aa; border-radius: 14px; padding: 11px 13px; font-size: 12px; font-weight: 750; margin-bottom: 10px; }
    .address-card { display: flex; gap: 12px; align-items: flex-start; }
    .address-pin { width: 34px; height: 34px; border-radius: 50%; background: var(--soft); display: grid; place-items: center; flex-shrink: 0; }

    .addr-missing { border-color: #f5c6a0; background: #fff8f4; }
    .address-pin-warn { background: #fde8d8; }
    .addr-chip-warn { background: #e05c00 !important; color: #fff !important; border-color: #e05c00 !important; }
    .required-badge { font-size: 9px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; background: #e05c00; color: #fff; padding: 2px 7px; border-radius: 999px; }

    .ship-select { width:100%; text-align:left; border:1px solid var(--line); background:var(--soft); border-radius:12px; min-height:46px; padding:9px 11px; font-family:inherit; cursor:pointer; display:grid; grid-template-columns:18px minmax(0,1fr) auto 14px; align-items:center; column-gap:10px; }
    .ship-select.open .ship-chevron { transform:rotate(180deg); }
    .ship-current { min-width:0; display:flex; align-items:center; gap:6px; white-space:nowrap; overflow:hidden; }
    .ship-placeholder { font-size:13px; font-weight:700; color:var(--mid); }
    .ship-chevron { color:var(--mid); transition:transform .15s; }
    .ship-options { display:none; margin-top:8px; border:1px solid var(--line); border-radius:12px; overflow:hidden; background:var(--white); }
    .ship-options.open { display:block; }
    .ship-opt { width: 100%; text-align: left; background: none; border: none; font-family: inherit; cursor: pointer; display: grid; grid-template-columns: 18px minmax(0, 1fr) auto; align-items: center; column-gap: 10px; padding: 11px; border-top: 1px solid var(--line); }
    .ship-opt:first-child { border-top: 0; }
    .ship-opt.selected .ship-radio { background: var(--action); border-color: var(--action); }
    .ship-opt.selected .ship-radio::after { content: ''; display: block; width: 7px; height: 7px; border-radius: 50%; background: #fff; margin: auto; }
    .ship-radio { width: 18px; height: 18px; border-radius: 50%; border: 2px solid #ccc; flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: all .15s; }
    .ship-main { min-width: 0; display: flex; align-items: center; gap: 6px; white-space: nowrap; overflow: hidden; }
    .ship-name { font-size: 13px; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ship-etd  { font-size: 11px; color: #888; font-weight: 600; white-space: nowrap; flex: 0 0 auto; }
    .ship-cost { font-size: 13px; font-weight: 900; white-space: nowrap; }
    .ship-missing { border-color: #f5c6a0; background: #fff8f4; }

    .pay-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; padding: 4px 0 6px; }
    .pay-opt { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 10px 4px; border-radius: 12px; border: 1.5px solid var(--line); background: var(--soft); cursor: pointer; font-family: inherit; transition: border-color .15s, background .15s; }
    .pay-opt:hover { border-color: #bbb; background: #efefef; }
    .pay-opt.selected { border-color: var(--action); background: var(--action-soft); box-shadow: 0 0 0 1.5px var(--action); }
    .pay-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 900; color: #fff; }
    .pi-qris   { background: #111; }
    .pi-gopay  { background: #00aa5b; }
    .pi-dana   { background: #118ee9; font-size: 16px; }
    .pi-ovo    { background: #4c3494; font-size: 8px; letter-spacing: .04em; }
    .pi-shopee { background: #ee4d2d; font-size: 8px; }
    .pi-bca    { background: #005baa; font-size: 9px; }
    .pi-bri    { background: #003d79; font-size: 9px; }
    .pi-mandiri{ background: #003580; font-size: 15px; }
    .pay-label { font-size: 10px; font-weight: 800; color: var(--ink); text-align: center; line-height: 1.2; }
    .pay-missing { border-color: #f5c6a0; background: #fff8f4; }
    @@media (max-width: 400px) {
        .pay-grid { gap: 6px; }
        .pay-opt { padding: 8px 2px; border-radius: 10px; }
        .pay-icon { width: 34px; height: 34px; border-radius: 8px; }
        .pay-label { font-size: 9px; }
    }

    .summary { background: var(--white); border-radius: 16px; padding: 18px 20px 20px; margin-bottom: 20px; border: 1px solid var(--line); box-shadow: 0 1px 3px rgba(0,0,0,.04); }
    .summary-label { font-size: 10px; font-weight: 900; letter-spacing: .10em; text-transform: uppercase; color: var(--mid); margin-bottom: 14px; }
    .summary-stock { display:block;font-size:10px;font-weight:800;margin-top:3px;color:var(--mid); }
    .summary-stock.low { color:#f97316; }
    .summary-stock.out { color:#b91c1c; }
    .sum-product { display: flex; justify-content: space-between; gap: 14px; align-items: flex-start; margin-bottom: 12px; }
    .sum-product-main { min-width: 0; max-width: 68%; }
    .sum-product-label { font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--mid); margin-bottom: 4px; }
    .sum-product-name { font-size: 13px; font-weight: 800; color: var(--ink); line-height: 1.35; }
    .sum-product-meta { display:block;font-size:11px;font-weight:600;color:var(--mid);margin-top:3px;line-height:1.4; }
    .sum-product-price { font-size:13px;font-weight:800;color:var(--ink);white-space:nowrap; }
    .sum-row { display: flex; justify-content: space-between; gap: 14px; font-size: 13px; font-weight: 600; color: var(--mid); margin-bottom: 10px; }
    .sum-row span:last-child { font-weight: 700; color: var(--ink); }
    .sum-sub { display: block; font-size: 10px; color: var(--mid); font-weight: 600; line-height: 1.35; margin-top: 2px; }
    .sum-divider { border: none; border-top: 1.5px dashed var(--line); margin: 14px 0; }
    .sum-total { display: flex; justify-content: space-between; gap: 14px; align-items: flex-end; background: var(--action); color: var(--white); margin: 16px -8px -8px; padding: 14px 12px; border-radius: 14px; box-shadow: 0 10px 24px rgba(22,163,74,.18); }
    .sum-total-label { font-size: 11px; color: rgba(255,255,255,.76); font-weight: 800; letter-spacing: .08em; text-transform: uppercase; display: block; margin-bottom: 3px; }
    .sum-total-title { font-size: 14px; font-weight: 900; }
    .sum-total-amount { font-size: 20px; font-weight: 900; white-space: nowrap; }

    .order-inline { width: 100%; height: 50px; justify-content: center; margin-bottom: 32px; border: none; cursor: pointer; font-family: inherit; border-radius: 14px; background: var(--ink); color: var(--white); font-size: 14px; font-weight: 900; display: flex; align-items: center; gap: 8px; transition: opacity .15s; }
    .order-inline:hover { opacity: .88; }
    .order-btn { height: 46px; min-width: 150px; padding: 0 22px; border-radius: 14px; background: var(--ink); color: var(--white); border: none; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 900; font-family: inherit; cursor: pointer; transition: opacity .15s; }
    .order-btn:hover { opacity: .88; }
    .order-inactive { background: #ccc9c4 !important; color: #999 !important; box-shadow: none !important; opacity: 1 !important; }
    .order-active   { background: var(--action) !important; color: #fff !important; box-shadow: 0 10px 24px rgba(22,163,74,.22) !important; }

    .order-bar { display: none; }
    .checkout-mini { display: flex; flex-direction: column; gap: 2px; }
    .checkout-label { font-size: 10px; color: var(--mid); font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .checkout-total { font-size: 16px; font-weight: 900; white-space: nowrap; }

    .secure-notice { display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 11px; color: var(--mid); font-weight: 600; padding: 12px 0 28px; }

    @@media (min-width: 720px) {
        .desktop-breadcrumb { display: flex; }
    }
    @@media (max-width: 719px) {
        body { padding-bottom: calc(82px + var(--safe)); }
        .wrap { width: min(520px, calc(100% - 24px)); }
        .page-head { padding: 14px 0 8px; margin-bottom: 14px; }
        .page-title { font-size: 17px; }
        .checkout-section { border-radius: 14px; padding: 14px; margin-bottom: 8px; }
        .order-inline { display: none; }
        .secure-notice { display: none; }
        .order-bar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 120; display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 16px calc(12px + var(--safe)); background: rgba(245,242,238,.97); backdrop-filter: blur(14px); border-top: 1px solid var(--line); box-shadow: 0 -8px 24px rgba(0,0,0,.06); }
    }
</style>
@endpush

@section('content')
<div class="wrap">

    <div class="desktop-breadcrumb">
        <a href="{{ route('storefront.home') }}">Home</a>
        <span>/</span>
        <a href="{{ route('storefront.cart') }}">Keranjang</a>
        <span>/</span>
        <span>Pembayaran</span>
    </div>

    <div class="page-head">
        <div>
            <div class="page-title">Ringkasan Pesanan</div>
            <div class="page-count">{{ array_sum(array_column($cart, 'qty')) }} item · konfirmasi sebelum pesan</div>
        </div>
        <a href="{{ route('storefront.cart') }}" class="back-link">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Keranjang
        </a>
    </div>

    @if(session('order_error'))
    <div class="checkout-alert">{{ session('order_error') }}</div>
    @endif

    {{-- 1. ALAMAT --}}
    <section class="checkout-section @if(empty($address['recipient_name'])) addr-missing @endif">
        <div class="checkout-title">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1116 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Kirim ke
            @if(empty($address['recipient_name']))<span class="required-badge">Wajib</span>@endif
        </div>
        <div class="address-card">
            <div class="checkout-main">
                @if(!empty($address['recipient_name']))
                <div class="checkout-name">{{ $address['recipient_name'] }} · {{ $address['phone'] }}</div>
                <div class="checkout-note">{{ $address['detail'] }}, {{ $address['district_name'] }}, {{ $address['city_name'] }}@if(!empty($address['postal_code'])) {{ $address['postal_code'] }}@endif</div>
                @else
                <div class="checkout-name" style="color:#e05c00;">Alamat belum diisi</div>
                <div class="checkout-note">Tap tombol di kanan untuk mengisi.</div>
                @endif
            </div>
            <a class="checkout-chip @if(empty($address['recipient_name'])) addr-chip-warn @endif" href="{{ route('storefront.checkout.address', array_merge(request()->query(), ['return_to' => request()->fullUrl()])) }}">
                {{ !empty($address['recipient_name']) ? 'Ubah' : 'Isi' }}
            </a>
        </div>
    </section>

    {{-- 2. PENGIRIMAN --}}
    <section class="checkout-section" id="shipping-section">
        <div class="checkout-title">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h4l3 5v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            Pengiriman
            <span id="shipping-loading" style="display:none;font-size:10px;font-weight:600;color:#aaa;letter-spacing:0;">memuat…</span>
            <span id="ship-required-badge" class="required-badge" style="display:none;">Wajib</span>
        </div>
        <div id="shipping-empty">
            <div class="checkout-name" style="color:#aaa;font-size:13px;font-weight:600;">Isi alamat terlebih dahulu</div>
        </div>
        <div id="shipping-error" style="display:none;">
            <div class="checkout-name" style="color:#c00;font-size:13px;">Gagal memuat ongkir</div>
            <div class="checkout-note" id="shipping-error-msg"></div>
        </div>
        <div id="shipping-list" style="display:none;">
            <button type="button" class="ship-select" id="shipping-select" aria-expanded="false">
                <span class="ship-radio"></span>
                <span class="ship-current" id="shipping-current"><span class="ship-placeholder">Pilih pengiriman</span></span>
                <span class="ship-cost" id="shipping-current-cost"></span>
                <svg class="ship-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="ship-options" id="shipping-options"></div>
        </div>
    </section>

    {{-- 3. PEMBAYARAN --}}
    <section class="checkout-section" id="payment-section">
        <div class="checkout-title" style="margin-bottom:4px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            Pembayaran
            <span id="pay-required-badge" class="required-badge" style="display:none;">Wajib</span>
        </div>
        @php
            $ckBrand       = storefront_setting('branding.brand_name', 'Greatfit');
            $ckAccountName = storefront_setting('checkout.account_name') ?: 'a.n. Greatfit Indonesia';
            $ckBcaNo       = storefront_setting('checkout.bca_no') ?: '88600010001';
            $ckBriNo       = storefront_setting('checkout.bri_no') ?: '089001000001303';
            $ckMandiriNo   = storefront_setting('checkout.mandiri_no') ?: '15600012345678';
            $ckPayOn       = fn($key) => storefront_setting("checkout.pay_{$key}", '1') !== '0';
        @endphp
        <div class="pay-grid">
            @if($ckPayOn('qris'))
            <button type="button" class="pay-opt" data-method="QRIS" data-note="Semua e-wallet" data-type="qr" data-qr-label="QRIS {{ $ckBrand }}">
                <span class="pay-icon pi-qris"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="3" height="3"/><rect x="19" y="14" width="2" height="2"/><rect x="14" y="19" width="2" height="2"/><rect x="18" y="18" width="3" height="3"/></svg></span>
                <span class="pay-label">QRIS</span>
            </button>
            @endif
            @if($ckPayOn('gopay'))
            <button type="button" class="pay-opt" data-method="GoPay" data-note="Scan QR GoPay" data-type="qr" data-qr-label="GoPay {{ $ckBrand }}">
                <span class="pay-icon pi-gopay">Go</span><span class="pay-label">GoPay</span>
            </button>
            @endif
            @if($ckPayOn('dana'))
            <button type="button" class="pay-opt" data-method="Dana" data-note="Scan QR Dana" data-type="qr" data-qr-label="Dana {{ $ckBrand }}">
                <span class="pay-icon pi-dana">D</span><span class="pay-label">Dana</span>
            </button>
            @endif
            @if($ckPayOn('ovo'))
            <button type="button" class="pay-opt" data-method="OVO" data-note="Scan QR OVO" data-type="qr" data-qr-label="OVO {{ $ckBrand }}">
                <span class="pay-icon pi-ovo">OVO</span><span class="pay-label">OVO</span>
            </button>
            @endif
            @if($ckPayOn('shopeepay'))
            <button type="button" class="pay-opt" data-method="ShopeePay" data-note="Scan QR ShopeePay" data-type="qr" data-qr-label="ShopeePay {{ $ckBrand }}">
                <span class="pay-icon pi-shopee">Spay</span><span class="pay-label">ShopeePay</span>
            </button>
            @endif
            @if($ckPayOn('bca'))
            <button type="button" class="pay-opt" data-method="Transfer BCA" data-note="{{ $ckAccountName }}" data-type="bank" data-bank-no="{{ $ckBcaNo }}" data-bank-name="{{ $ckAccountName }}">
                <span class="pay-icon pi-bca">BCA</span><span class="pay-label">BCA</span>
            </button>
            @endif
            @if($ckPayOn('bri'))
            <button type="button" class="pay-opt" data-method="Transfer BRI" data-note="{{ $ckAccountName }}" data-type="bank" data-bank-no="{{ $ckBriNo }}" data-bank-name="{{ $ckAccountName }}">
                <span class="pay-icon pi-bri">BRI</span><span class="pay-label">BRI</span>
            </button>
            @endif
            @if($ckPayOn('mandiri'))
            <button type="button" class="pay-opt" data-method="Transfer Mandiri" data-note="{{ $ckAccountName }}" data-type="bank" data-bank-no="{{ $ckMandiriNo }}" data-bank-name="{{ $ckAccountName }}">
                <span class="pay-icon pi-mandiri">M</span><span class="pay-label">Mandiri</span>
            </button>
            @endif
        </div>
    </section>

    {{-- 4. RINGKASAN --}}
    <section class="summary">
        <div class="summary-label">Ringkasan Pembayaran</div>
        @foreach ($cart as $key => $item)
        @php
            $stock = $cartStock[$key] ?? null;
            $available = $stock['available'] ?? null;
            $isOut = $available !== null && $available <= 0;
            $isOver = $stock && !($stock['ok'] ?? true);
            $isLow = $stock['low'] ?? false;
        @endphp
        <div class="sum-product">
            <div class="sum-product-main">
                <div class="sum-product-label">Produk</div>
                <div class="sum-product-name">{{ $item['name'] }}</div>
                <span class="sum-product-meta">@if(!empty($item['color'])){{ $item['color'] }} · @endif Ukuran {{ $item['size'] }} · {{ $item['qty'] }} pcs</span>
                @if($available !== null)
                <span class="summary-stock {{ $isOut || $isOver ? 'out' : ($isLow ? 'low' : '') }}">
                    @if($isOut)
                        Stok sedang kosong
                    @elseif($isOver)
                        Stok tersisa {{ $available }} pcs
                    @elseif($isLow)
                        Tersisa {{ $available }} pcs
                    @else
                        Stok tersedia
                    @endif
                </span>
                @endif
            </div>
            <div class="sum-product-price">Rp{{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}</div>
        </div>
        @endforeach
        <hr class="sum-divider">
        <div class="sum-row">
            <span>
                Pengiriman
                <span class="sum-sub" id="summary-shipping-service"></span>
            </span>
            <span id="summary-ongkir" style="color:var(--mid);">—</span>
        </div>
        <div class="sum-row">
            <span>
                Kode pembayaran
                <span class="sum-sub">Mohon transfer sesuai nominal ini</span>
            </span>
            <span id="summary-unique-code">Rp{{ number_format($uniqueCode ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="sum-total">
            <span>
                <span class="sum-total-label">Total Dibayar</span>
                <span class="sum-total-title">Termasuk ongkir & kode pembayaran</span>
            </span>
            <span class="sum-total-amount" id="summary-total">Rp{{ number_format(($total ?? 0) + ($uniqueCode ?? 0), 0, ',', '.') }}</span>
        </div>
    </section>

    <button type="button" class="order-inline order-inactive">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/><path d="m9.5 16 1.8 1.8 3.7-4"/></svg>
        Buat Pesanan
    </button>

    <div class="secure-notice">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        {{ storefront_setting('checkout.secure_notice') ?: 'Dikonfirmasi langsung oleh tim Greatfit' }}
    </div>

</div>{{-- /.wrap --}}

{{-- STICKY ORDER BAR (mobile, position:fixed) --}}
    <div class="order-bar">
        <div class="checkout-mini">
            <span class="checkout-label">Total Bayar</span>
            <span class="checkout-total" id="bar-total">Rp{{ number_format(($total ?? 0) + ($uniqueCode ?? 0), 0, ',', '.') }}</span>
        </div>
        <button type="button" class="order-btn order-inactive">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/><path d="m9.5 16 1.8 1.8 3.7-4"/></svg>
            Buat Pesanan
        </button>
    </div>

    {{-- KONFIRMASI PESANAN MODAL --}}
    <div id="confirm-overlay" style="display:none;position:fixed;inset:0;z-index:400;background:rgba(0,0,0,.5);" onclick="closeConfirmModal(event)"></div>
<div id="confirm-sheet" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:401;background:#fff;border-radius:20px 20px 0 0;padding:0 0 env(safe-area-inset-bottom,16px);max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:center;padding:12px 0 4px;">
        <div style="width:36px;height:4px;border-radius:999px;background:#d8d8d8;"></div>
    </div>
    <div style="padding:0 20px 24px;">
        <div style="font-size:17px;font-weight:900;margin-bottom:16px;">Selesaikan Pembayaran</div>
        <div id="modal-pay-detail" style="background:var(--soft);border-radius:14px;padding:16px;margin-bottom:16px;text-align:center;"></div>
        <div style="margin-bottom:16px;">
            <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--mid);margin-bottom:8px;">Bukti Pembayaran</div>
            <label id="upload-label" style="display:flex;align-items:center;gap:10px;border:1.5px dashed var(--line);border-radius:12px;padding:12px 14px;cursor:pointer;background:var(--soft);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span id="upload-label-text" style="font-size:13px;font-weight:700;">Pilih foto bukti bayar</span>
                <input type="file" id="bukti-input" accept="image/*" style="display:none;">
            </label>
            <div id="bukti-preview" style="display:none;margin-top:10px;position:relative;">
                <img id="bukti-img" src="" alt="Bukti" style="width:100%;max-height:200px;object-fit:cover;border-radius:10px;border:1px solid var(--line);">
                <button type="button" onclick="removeBukti()" style="position:absolute;top:6px;right:6px;background:rgba(0,0,0,.6);color:#fff;border:none;border-radius:999px;width:26px;height:26px;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-weight:900;">×</button>
                <div id="bukti-upload-status" style="font-size:11px;color:var(--mid);margin-top:4px;text-align:center;"></div>
            </div>
        </div>
        <button type="button" id="modal-wa-btn" style="width:100%;height:50px;border-radius:14px;background:var(--action);color:#fff;border:none;font-size:14px;font-weight:900;font-family:inherit;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:10px;box-shadow:0 10px 24px rgba(22,163,74,.22);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
            Kirim Bukti Pembayaran
        </button>
        <button type="button" onclick="closeConfirmModal()" style="width:100%;height:42px;border-radius:12px;background:transparent;color:var(--mid);border:1.5px solid var(--line);font-size:13px;font-weight:700;font-family:inherit;cursor:pointer;">
            Kembali
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var WA_NUMBER    = '{{ storefront_setting('branding.whatsapp_number', '6281224889319') }}';
    var QRIS_IMAGE   = @json(storefront_media_url(storefront_setting('checkout.qris_image')) ?: '');
    var KG_PER_ITEM  = {{ (float) (storefront_setting('checkout.weight_per_item') ?: 0.5) }};
    // Berat total dihitung server-side dari weight_kg tiap produk katalog
    // (fallback KG_PER_ITEM per item kalau produk belum diisi beratnya).
    var TOTAL_WEIGHT_KG = {{ (float) ($totalWeightKg ?? 0) }};
    var cartData     = @json($cart);
    var addressData  = @json($address ?? []);
    var productTotal = {{ $total ?? 0 }};
    var uniqueCode   = {{ $uniqueCode ?? 0 }};
    var itemQty      = {{ array_sum(array_column($cart ?? [], 'qty')) }};
    var ongkirUrl    = '{{ route('storefront.checkout.ongkir') }}';
    var uploadUrl    = '{{ route('storefront.checkout.upload_bukti') }}';
    var csrfToken    = document.querySelector('meta[name="csrf-token"]').content;
    @if(!empty($address['city_name']))
    var addressCity  = '{{ strtolower($address['city_name']) }}';
    @else
    var addressCity  = null;
    @endif

    var hasAddress       = !!(addressData && addressData.recipient_name);
    var stockOk          = @json(!collect($cartStock ?? [])->contains(fn($row) => !($row['ok'] ?? true)));
    var selectedShipping = null;
    var selectedPayment  = null;
    var _buktiUrl        = null;

    function rupiah(v) { return 'Rp' + Number(v).toLocaleString('id-ID'); }

    function updateTotals() {
        var ongkir = selectedShipping ? selectedShipping.cost : 0;
        var grand  = productTotal + ongkir + uniqueCode;
        var elSum  = document.getElementById('summary-ongkir');
        var elTot  = document.getElementById('summary-total');
        var elBar  = document.getElementById('bar-total');
        var elSvc  = document.getElementById('summary-shipping-service');
        if (elSum) elSum.textContent = selectedShipping ? rupiah(ongkir) : '—';
        if (elSvc) {
            elSvc.textContent = selectedShipping
                ? ((selectedShipping.courier || 'Kurir') + ' · ' + selectedShipping.label + (selectedShipping.etd ? ' · ' + selectedShipping.etd : ''))
                : '';
        }
        if (elTot) elTot.textContent = rupiah(grand);
        if (elBar) elBar.textContent = rupiah(grand);
    }

    function updateOrderBtnState() {
        var ok = hasAddress && !!selectedShipping && !!selectedPayment && stockOk;
        document.querySelectorAll('.order-inline, .order-btn').forEach(function (btn) {
            if (ok) { btn.classList.remove('order-inactive'); btn.classList.add('order-active'); }
            else    { btn.classList.remove('order-active');   btn.classList.add('order-inactive'); }
        });
        var shipSection = document.getElementById('shipping-section');
        var shipBadge   = document.getElementById('ship-required-badge');
        if (shipSection) {
            if (hasAddress && !selectedShipping) {
                shipSection.classList.add('ship-missing');
                if (shipBadge) shipBadge.style.display = 'inline-flex';
            } else {
                shipSection.classList.remove('ship-missing');
                if (shipBadge) shipBadge.style.display = 'none';
            }
        }
        var paySection = document.getElementById('payment-section');
        var payBadge   = document.getElementById('pay-required-badge');
        if (paySection) {
            if (!selectedPayment) {
                paySection.classList.add('pay-missing');
                if (payBadge) payBadge.style.display = 'inline-flex';
            } else {
                paySection.classList.remove('pay-missing');
                if (payBadge) payBadge.style.display = 'none';
            }
        }
    }

    function renderShippingOptions(results) {
        var list  = document.getElementById('shipping-list');
        var empty = document.getElementById('shipping-empty');
        var selectBtn = document.getElementById('shipping-select');
        var optionsBox = document.getElementById('shipping-options');
        var current = document.getElementById('shipping-current');
        var currentCost = document.getElementById('shipping-current-cost');
        if (!list) return;
        if (optionsBox) optionsBox.innerHTML = '';
        if (current) current.innerHTML = '<span class="ship-placeholder">Pilih pengiriman</span>';
        if (currentCost) currentCost.textContent = '';
        var options = [];
        function resolveCost(svc) {
            if (Array.isArray(svc.cost) && svc.cost.length) return Number(svc.cost[0].value || svc.cost[0].cost || 0);
            if (svc.cost && typeof svc.cost === 'object') return Number(svc.cost.value || svc.cost.cost || 0);
            return Number(svc.cost || svc.value || 0);
        }
        function resolveEtd(svc) {
            if (Array.isArray(svc.cost) && svc.cost.length) return svc.cost[0].etd || svc.etd || '';
            if (svc.cost && typeof svc.cost === 'object') return svc.cost.etd || svc.etd || '';
            return svc.etd || '';
        }
        (results || []).forEach(function (courier) {
            (courier.costs || []).forEach(function (svc) {
                var cost = resolveCost(svc);
                if (!Number.isFinite(cost) || cost < 0) return;
                var rawEtd = String(resolveEtd(svc)).replace(/\s*(hari|day|days)$/i, '').trim();
                var courierName = courier.name || courier.code || 'Kurir';
                var label = courierName + ' ' + (svc.service || '');
                options.push({
                    label: label.trim(),
                    rawLabel: label.trim(),
                    courier: courierName,
                    service: svc.service || '',
                    etd: rawEtd ? ('Est. ' + rawEtd + ' hari') : '',
                    cost: cost
                });
            });
        });
        options.sort(function (a, b) { return a.cost - b.cost; });
        var regularOption = null;
        var expressOption = null;
        var cargoOption = null;
        options.forEach(function (opt) {
            var serviceText = ((opt.rawLabel || '') + ' ' + (opt.service || '')).toLowerCase();
            var isCargo = serviceText.indexOf('cargo') !== -1;
            if (isCargo) {
                if (!cargoOption || opt.cost < cargoOption.cost) cargoOption = opt;
                return;
            }
            if (!regularOption) {
                regularOption = opt;
                return;
            }
            if (!expressOption || opt.cost < expressOption.cost) expressOption = opt;
        });
        options = [];
        if (regularOption) {
            regularOption.displayService = 'Reguler';
            regularOption.label = 'Reguler';
            options.push(regularOption);
        }
        if (expressOption) {
            expressOption.displayService = 'Express';
            expressOption.label = 'Express';
            options.push(expressOption);
        }
        if (cargoOption) {
            cargoOption.displayService = 'Cargo';
            cargoOption.label = 'Cargo';
            options.push(cargoOption);
        }
        if (!options.length) {
            if (empty) { empty.style.display = ''; var nameEl = empty.querySelector('.checkout-name'); if (nameEl) nameEl.textContent = 'Ongkir tidak tersedia untuk kota ini'; }
            selectedShipping = null;
            updateTotals();
            var elSum = document.getElementById('summary-ongkir');
            if (elSum) elSum.textContent = 'Tidak tersedia';
            updateOrderBtnState();
            return;
        }
        if (empty) empty.style.display = 'none';
        list.style.display = 'block';
        options.forEach(function (opt) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ship-opt';
            btn.innerHTML = '<span class="ship-radio"></span><span class="ship-main"><span class="ship-name">' + opt.label + '</span><span class="ship-etd">' + opt.etd + '</span></span><span class="ship-cost">' + rupiah(opt.cost) + '</span>';
            btn.addEventListener('click', function () {
                document.querySelectorAll('.ship-opt').forEach(function (b) { b.classList.remove('selected'); });
                btn.classList.add('selected');
                selectedShipping = opt;
                if (current) current.innerHTML = '<span class="ship-name">' + opt.label + '</span><span class="ship-etd">' + opt.etd + '</span>';
                if (currentCost) currentCost.textContent = rupiah(opt.cost);
                if (optionsBox) optionsBox.classList.remove('open');
                if (selectBtn) {
                    selectBtn.classList.remove('open');
                    selectBtn.setAttribute('aria-expanded', 'false');
                }
                updateTotals();
                updateOrderBtnState();
            });
            if (optionsBox) optionsBox.appendChild(btn);
        });
        if (selectBtn && !selectBtn._bound) {
            selectBtn._bound = true;
            selectBtn.addEventListener('click', function () {
                if (!optionsBox) return;
                var open = !optionsBox.classList.contains('open');
                optionsBox.classList.toggle('open', open);
                selectBtn.classList.toggle('open', open);
                selectBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }
        var first = optionsBox ? optionsBox.querySelector('.ship-opt') : null;
        if (first) first.click();
    }

    function loadOngkir(city) {
        var loading = document.getElementById('shipping-loading');
        var errEl   = document.getElementById('shipping-error');
        var errMsg  = document.getElementById('shipping-error-msg');
        var list    = document.getElementById('shipping-list');
        var empty   = document.getElementById('shipping-empty');
        function hideLoading() { if (loading) loading.style.display = 'none'; }
        function showError(msg) {
            hideLoading();
            selectedShipping = null;
            updateTotals();
            var elSum = document.getElementById('summary-ongkir');
            if (elSum) elSum.textContent = 'Tidak tersedia';
            updateOrderBtnState();
            if (errEl) errEl.style.display = '';
            if (errMsg) errMsg.textContent = msg;
        }
        if (loading) loading.style.display = 'inline';
        if (errEl)   errEl.style.display   = 'none';
        if (list)    list.style.display    = 'none';
        if (empty)   empty.style.display   = 'none';
        selectedShipping = null;
        var elSum = document.getElementById('summary-ongkir');
        var elSvc = document.getElementById('summary-shipping-service');
        if (elSum) elSum.textContent = 'Memuat…';
        if (elSvc) elSvc.textContent = '';
        updateTotals();
        if (elSum) elSum.textContent = 'Memuat…';
        updateOrderBtnState();
        var cleanCity  = city.replace(/^(kabupaten|kota)\s+/i, '').trim();
        var weightKg   = TOTAL_WEIGHT_KG > 0 ? TOTAL_WEIGHT_KG : (itemQty * KG_PER_ITEM);
        var weight     = weightKg.toFixed(1);
        var url        = ongkirUrl + '?destination=' + encodeURIComponent(cleanCity) + '&weight=' + weight;
        var controller = new AbortController();
        var timer      = setTimeout(function () { controller.abort(); }, 15000);
        fetch(url, { signal: controller.signal })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                clearTimeout(timer);
                hideLoading();
                if (data.code && data.code !== '200') { showError(data.message || 'Ongkir tidak tersedia.'); return; }
                if (data.error) { showError(data.error); return; }
                renderShippingOptions(data.data ? data.data.results : []);
            })
            .catch(function (err) {
                clearTimeout(timer);
                selectedShipping = null;
                updateTotals();
                var elSum = document.getElementById('summary-ongkir');
                if (elSum) elSum.textContent = 'Gagal memuat';
                updateOrderBtnState();
                showError(err && err.name === 'AbortError' ? 'Waktu habis, coba refresh.' : 'Koneksi gagal, coba refresh.');
            });
    }

    function buildPayDetail() {
        if (!selectedPayment) return '';
        var ongkir = selectedShipping ? selectedShipping.cost : 0;
        var grandTotal = productTotal + ongkir + uniqueCode;
        var totalBlock = '<div style="background:var(--action-soft);border:1.5px solid rgba(22,163,74,.28);border-radius:12px;padding:12px;margin-bottom:12px;">'
            + '<div style="font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:var(--action-dark);margin-bottom:5px;">Total yang harus dibayar</div>'
            + '<div style="font-size:25px;font-weight:900;color:var(--action-dark);line-height:1;">' + rupiah(grandTotal) + '</div>'
            + '<div style="font-size:11px;color:#4b7c5c;font-weight:700;margin-top:6px;">Transfer sesuai nominal agar cepat dicek.</div>'
            + '</div>';
        var t = selectedPayment.type;
        if (t === 'bank') {
            return totalBlock
                 + '<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#888;margin-bottom:6px;">' + selectedPayment.method + '</div>'
                 + '<div style="font-size:26px;font-weight:900;letter-spacing:.05em;" id="modal-bank-no">' + (selectedPayment.bankNo || '—') + '</div>'
                 + '<div style="font-size:12px;color:#888;margin-top:2px;">' + (selectedPayment.bankName || '') + '</div>'
                 + '<button type="button" onclick="copyModalBankNo()" id="modal-copy-btn" style="margin-top:10px;background:#0a0a0a;color:#fff;border:none;border-radius:8px;padding:7px 18px;font-size:12px;font-weight:800;font-family:inherit;cursor:pointer;">Salin Nomor</button>';
        }
        if (t === 'qr') {
            // Pakai gambar QRIS asli dari settings kalau ada; fallback QR dummy.
            var qrSrc;
            if (QRIS_IMAGE) {
                qrSrc = QRIS_IMAGE;
            } else {
                var qrText = 'GFID-' + selectedPayment.method.toUpperCase().replace(/\s/g, '-') + '-DUMMY';
                qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&data=' + encodeURIComponent(qrText);
            }
            return totalBlock
                 + '<div style="font-size:13px;font-weight:800;margin-bottom:8px;">' + (selectedPayment.qrLabel || selectedPayment.method) + '</div>'
                 + '<img src="' + qrSrc + '" style="width:160px;height:160px;border-radius:10px;border:1px solid #ebebeb;display:block;margin:0 auto 8px;object-fit:contain;background:#fff;" alt="QR">'
                 + '<div style="font-size:11px;color:#888;">Scan via aplikasi e-wallet</div>';
        }
        return totalBlock + '<div style="font-size:13px;color:#555;">' + selectedPayment.note + '</div>';
    }

    window.copyModalBankNo = function () {
        var el  = document.getElementById('modal-bank-no');
        var btn = document.getElementById('modal-copy-btn');
        if (!el) return;
        var text = el.textContent.replace(/\s/g, '');
        function onSuccess() { if (btn) { btn.textContent = 'Tersalin ✓'; setTimeout(function () { btn.textContent = 'Salin Nomor'; }, 1800); } }
        function fallback() { var ta = document.createElement('textarea'); ta.value = text; ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0;'; document.body.appendChild(ta); ta.focus(); ta.select(); try { document.execCommand('copy'); onSuccess(); } catch (e) {} document.body.removeChild(ta); }
        if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(text).then(onSuccess).catch(fallback); } else { fallback(); }
    };

    window.closeConfirmModal = function (e) {
        if (e && e.target !== document.getElementById('confirm-overlay')) return;
        document.getElementById('confirm-overlay').style.display = 'none';
        document.getElementById('confirm-sheet').style.display   = 'none';
        document.body.style.overflow = '';
    };

    window.removeBukti = function () {
        document.getElementById('bukti-preview').style.display   = 'none';
        document.getElementById('bukti-input').value             = '';
        document.getElementById('upload-label-text').textContent = 'Pilih foto bukti bayar';
        document.getElementById('bukti-upload-status').textContent = '';
        _buktiUrl = null;
    };

    function buildMessage() {
        var now = new Date();
        var pad = function (n) { return n < 10 ? '0' + n : n; };
        var dateStr  = pad(now.getDate()) + '/' + pad(now.getMonth() + 1) + '/' + now.getFullYear();
        var orderRef = 'GF' + now.getFullYear().toString().slice(-2) + pad(now.getMonth()+1) + pad(now.getDate()) + '-' + Math.floor(Math.random() * 9000 + 1000);
        var sep = '─────────────────────';
        var lines = [];
        lines.push('🧾 *PESANAN BARU — GREATFIT*');
        lines.push('━━━━━━━━━━━━━━━━━━━━');
        lines.push('No. Pesanan : *' + orderRef + '*');
        lines.push('Tanggal     : ' + dateStr);
        lines.push('');
        lines.push('*🛍 DETAIL PRODUK*');
        lines.push(sep);
        var num = 1;
        for (var key in cartData) {
            if (!cartData.hasOwnProperty(key)) continue;
            var item = cartData[key];
            var variant = (item.color ? item.color + ' / ' : '') + 'Ukuran ' + item.size;
            lines.push(num++ + '. *' + item.name + '*');
            lines.push('   ' + variant);
            lines.push('   ' + rupiah(item.price) + ' × ' + item.qty + ' = *' + rupiah(item.price * item.qty) + '*');
        }
        lines.push(sep);
        var ongkir = selectedShipping ? selectedShipping.cost : 0;
        lines.push('Subtotal  : ' + rupiah(productTotal));
        if (selectedShipping) { lines.push('Ongkir    : ' + rupiah(ongkir) + ' (' + selectedShipping.label + ')'); }
        else { lines.push('Ongkir    : _(akan dikonfirmasi)_'); }
        if (uniqueCode > 0) { lines.push('Kode unik : ' + rupiah(uniqueCode)); }
        lines.push('');
        lines.push('💰 *TOTAL BAYAR: ' + rupiah(productTotal + ongkir + uniqueCode) + '*');
        lines.push('━━━━━━━━━━━━━━━━━━━━');
        if (addressData && addressData.recipient_name) {
            lines.push('');
            lines.push('📍 *ALAMAT PENGIRIMAN*');
            lines.push(sep);
            lines.push('Nama    : *' + addressData.recipient_name + '*');
            lines.push('No. HP  : ' + addressData.phone);
            var addr = addressData.detail + ', ' + addressData.village_name + ', ' + addressData.district_name + ', ' + addressData.city_name + ', ' + addressData.province_name;
            if (addressData.postal_code) addr += ' ' + addressData.postal_code;
            lines.push('Alamat  : ' + addr);
            if (addressData.note) lines.push('Catatan : _' + addressData.note + '_');
        }
        if (selectedPayment) {
            lines.push('');
            lines.push('💳 *METODE PEMBAYARAN*');
            lines.push(sep);
            lines.push('*' + selectedPayment.method + '*');
            if (selectedPayment.type === 'bank' && selectedPayment.bankNo) { lines.push('No. Rek : *' + selectedPayment.bankNo + '*'); lines.push(selectedPayment.bankName || ''); }
        }
        lines.push('');
        lines.push('Mohon dikonfirmasi ketersediaan & info selanjutnya. Terima kasih! 🙏');
        return lines.join('\n');
    }

    function openConfirmModal() {
        document.getElementById('modal-pay-detail').innerHTML = buildPayDetail();
        document.getElementById('confirm-overlay').style.display = 'block';
        document.getElementById('confirm-sheet').style.display   = 'block';
        document.body.style.overflow = 'hidden';
        var input = document.getElementById('bukti-input');
        if (input && !input._bound) {
            input._bound = true;
            input.addEventListener('change', function () {
                var file = this.files[0];
                if (!file) return;
                document.getElementById('upload-label-text').textContent = file.name;
                var reader = new FileReader();
                reader.onload = function (e) { document.getElementById('bukti-img').src = e.target.result; document.getElementById('bukti-preview').style.display = 'block'; };
                reader.readAsDataURL(file);
                var status = document.getElementById('bukti-upload-status');
                status.textContent = 'Mengunggah…';
                var fd = new FormData();
                fd.append('file', file);
                fd.append('_token', csrfToken);
                fetch(uploadUrl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (data) { _buktiUrl = data.url || null; status.textContent = _buktiUrl ? '✓ Tersimpan' : 'Gagal unggah'; })
                    .catch(function () { status.textContent = 'Gagal unggah'; });
            });
        }
        document.getElementById('modal-wa-btn').onclick = function () {
            var msg = buildMessage();
            if (_buktiUrl) msg += '\n\nBukti Bayar: ' + _buktiUrl;
            var ongkir = selectedShipping ? selectedShipping.cost : 0;
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('storefront.checkout.place_order') }}';
            var fields = {
                '_token': csrfToken, 'subtotal': productTotal, 'shipping_cost': ongkir,
                'unique_code': uniqueCode,
                'shipping_courier': selectedShipping ? (selectedShipping.courier || selectedShipping.label || '') : '',
                'shipping_service': selectedShipping ? ((selectedShipping.displayService || selectedShipping.service || selectedShipping.label || '') + (selectedShipping.etd ? ' · ' + selectedShipping.etd : '')) : '',
                'payment_method': selectedPayment ? selectedPayment.method : '',
                'payment_proof_url': _buktiUrl || '', 'wa_message': msg,
            };
            for (var name in fields) {
                var input = document.createElement('input');
                input.type = 'hidden'; input.name = name; input.value = fields[name];
                form.appendChild(input);
            }
            document.body.appendChild(form);
            form.submit();
        };
    }

    function showToast(msg, scrollTarget) {
        var t = document.getElementById('addr-toast');
        if (t) t.remove();
        t = document.createElement('div');
        t.id = 'addr-toast';
        t.textContent = msg;
        t.style.cssText = 'position:fixed;bottom:calc(90px + env(safe-area-inset-bottom,0px));left:50%;transform:translateX(-50%);background:#0a0a0a;color:#fff;font-size:13px;font-weight:700;padding:10px 18px;border-radius:999px;z-index:200;white-space:nowrap;box-shadow:0 4px 16px rgba(0,0,0,.25);';
        document.body.appendChild(t);
        setTimeout(function () { t.remove(); }, 2800);
        if (scrollTarget) scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (addressCity) loadOngkir(addressCity);
        document.querySelectorAll('.pay-opt').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.pay-opt').forEach(function (b) { b.classList.remove('selected'); });
                btn.classList.add('selected');
                selectedPayment = { method: btn.dataset.method, note: btn.dataset.note, type: btn.dataset.type || null, bankNo: btn.dataset.bankNo || null, bankName: btn.dataset.bankName || null, qrLabel: btn.dataset.qrLabel || null };
                updateOrderBtnState();
            });
        });
        document.querySelectorAll('.order-inline, .order-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                if (!stockOk) { showToast('Ada stok yang perlu disesuaikan di ringkasan pesanan.', document.querySelector('.summary')); return; }
                if (!hasAddress) { showToast('Isi alamat pengiriman terlebih dahulu.', document.querySelector('.address-card')); return; }
                if (!selectedShipping) { showToast('Pilih ongkos kirim terlebih dahulu.', document.getElementById('shipping-section')); return; }
                if (!selectedPayment) { showToast('Pilih metode pembayaran terlebih dahulu.', document.getElementById('payment-section')); return; }
                openConfirmModal();
            });
        });
        updateOrderBtnState();
    });
})();
</script>
@endpush
