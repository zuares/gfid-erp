@extends('storefront.layouts.app')

@section('title', 'Pesanan Saya — Greatfit')

@section('footer')@endsection

@push('styles')
<style>
    body { background: var(--soft); }
    .wrap { width: min(760px, calc(100% - 32px)); margin: 0 auto; }
    .page { padding: 0 0 calc(44px + var(--safe)); }
    .tabs-wrap { position: sticky; top: 56px; z-index: 15; background: rgba(255,255,255,.96); border-bottom: 1px solid var(--line); backdrop-filter: blur(12px); }
    .tabs { display: flex; gap: 26px; overflow-x: auto; padding: 0; scrollbar-width: none; }
    .tabs::-webkit-scrollbar { display: none; }
    .tab { position: relative; flex: 0 0 auto; min-height: 48px; color: var(--mid); display: inline-flex; align-items: center; font-size: 13px; font-weight: 800; white-space: nowrap; }
    .tab.active { color: var(--ink); }
    .tab.active::after { content: ""; position: absolute; left: 0; right: 0; bottom: -1px; height: 3px; border-radius: 999px; background: var(--ink); }
    .orders-list { display: grid; gap: 12px; padding-top: 12px; }
    .order-card { background: var(--white); border: 1px solid var(--line); border-radius: 18px; overflow: hidden; }
    .order-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 16px 10px; }
    .store-name { display: flex; align-items: center; gap: 8px; min-width: 0; font-size: 13px; font-weight: 900; }
    .store-badge { height: 22px; padding: 0 7px; border-radius: 6px; background: var(--ink); color: var(--white); display: inline-flex; align-items: center; font-size: 10px; font-weight: 900; letter-spacing: .04em; flex: 0 0 auto; }
    .order-badge { display: inline-flex; align-items: center; height: 24px; padding: 0 9px; border-radius: 999px; font-size: 10px; font-weight: 900; letter-spacing: .03em; white-space: nowrap; }
    .badge-pending    { background:#fef9c3; color:#854d0e; }
    .badge-processing { background:#dbeafe; color:#1e40af; }
    .badge-shipped    { background:#e0e7ff; color:#3730a3; }
    .badge-delivered  { background:#dcfce7; color:#166534; }
    .badge-cancelled  { background:#fee2e2; color:#991b1b; }
    .product-row { display: grid; grid-template-columns: 82px minmax(0, 1fr) auto; gap: 12px; padding: 8px 16px 12px; }
    .product-img { width: 82px; height: 82px; border-radius: 12px; object-fit: cover; background: var(--soft); border: 1px solid var(--line); }
    .product-name { font-size: 13px; font-weight: 850; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .product-variant { margin-top: 4px; color: var(--mid); font-size: 11px; font-weight: 650; }
    .product-more { margin-top: 8px; color: var(--mid); font-size: 11px; font-weight: 750; }
    .product-side { text-align: right; display: flex; flex-direction: column; align-items: flex-end; justify-content: flex-start; gap: 8px; }
    .product-price { font-size: 13px; font-weight: 850; white-space: nowrap; }
    .product-qty { color: var(--mid); font-size: 12px; font-weight: 750; }
    .order-total-row { padding: 0 16px 14px; text-align: right; font-size: 13px; font-weight: 750; }
    .order-total-row strong { font-size: 15px; font-weight: 950; }
    .order-note { margin: 0 16px 14px; border-radius: 12px; background: var(--soft); padding: 12px; display: flex; align-items: center; gap: 10px; color: var(--mid); font-size: 12px; font-weight: 700; }
    .order-note svg { flex: 0 0 auto; color: var(--ink); }
    .order-actions { display: flex; justify-content: flex-end; gap: 8px; padding: 0 16px 16px; }
    .order-btn { min-height: 38px; padding: 0 16px; border-radius: 10px; border: 1px solid var(--line); background: var(--white); color: var(--ink); display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 850; }
    .order-btn.primary { background: var(--ink); border-color: var(--ink); color: var(--white); }
    .empty { background: var(--white); border: 1px solid var(--line); border-radius: 24px; padding: 52px 24px 48px; text-align: center; margin-top: 12px; }
    .empty-illus { width: 72px; height: 72px; border-radius: 20px; background: #E8FF00; display: grid; place-items: center; margin: 0 auto 20px; color: var(--ink); }
    .empty-title { font-size: 17px; font-weight: 900; letter-spacing: -.02em; margin-bottom: 8px; }
    .empty-copy { color: var(--mid); font-size: 13px; font-weight: 500; line-height: 1.55; margin-bottom: 24px; max-width: 260px; margin-left: auto; margin-right: auto; }
    .empty-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
    .empty-btn-primary { display: inline-flex; align-items: center; gap: 7px; min-height: 40px; padding: 0 20px; border-radius: 999px; background: var(--ink); color: var(--white); font-size: 12.5px; font-weight: 850; }
    .empty-btn-secondary { display: inline-flex; align-items: center; gap: 7px; min-height: 40px; padding: 0 18px; border-radius: 999px; border: 1.5px solid var(--line); background: var(--white); color: var(--ink); font-size: 12.5px; font-weight: 800; }
    .empty-btn-secondary:hover { border-color: var(--ink); }
    @@media (max-width: 719px) {
        .wrap { width: min(520px, calc(100% - 16px)); }
        .tabs { gap: 24px; }
    }
    @@media (min-width: 720px) {
        .product-row { grid-template-columns: 76px minmax(0, 1fr) auto; gap: 10px; padding-left: 14px; padding-right: 14px; }
        .product-img { width: 76px; height: 76px; }
        .order-top, .order-total-row, .order-actions { padding-left: 14px; padding-right: 14px; }
        .order-note { margin-left: 14px; margin-right: 14px; }
        .order-btn { flex: 0 0 auto; min-width: 108px; }
    }
</style>
@endpush

@php $navActive = 'orders'; @endphp

@section('content')
@php
    $statusTabs = [
        '' => 'Semua',
        'pending' => 'Menunggu',
        'processing' => 'Dikemas',
        'shipped' => 'Dikirim',
        'delivered' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];
@endphp
<div class="tabs-wrap">
    <div class="wrap">
        <div class="tabs" aria-label="Filter status pesanan">
            @foreach($statusTabs as $key => $label)
            <a href="{{ route('storefront.user.orders', array_filter(['status' => $key])) }}" class="tab {{ $activeStatus === $key ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>
</div>

<div class="wrap">
<div class="page">
    @if(count($orders) > 0)
    <div class="orders-list">
        @foreach($orders as $order)
        @php
            $items = is_array($order['items']) ? $order['items'] : json_decode($order['items'] ?? '[]', true);
            $firstItem = $items[0] ?? [];
            $firstQty = (int) ($firstItem['qty'] ?? 1);
            $firstPrice = (int) ($firstItem['price'] ?? 0);
            $sl = match($order['status'] ?? 'pending') {
                'processing' => ['Dikemas', 'badge-processing'],
                'shipped'    => ['Dikirim', 'badge-shipped'],
                'delivered'  => ['Selesai', 'badge-delivered'],
                'cancelled'  => ['Dibatalkan', 'badge-cancelled'],
                default      => ['Menunggu', 'badge-pending'],
            };
        @endphp
        <article class="order-card">
            <div class="order-top">
                <div class="store-name">
                    <span class="store-badge">GF</span>
                    <span>Greatfit Official</span>
                </div>
                <span class="order-badge {{ $sl[1] }}">{{ $sl[0] }}</span>
            </div>

            <div class="product-row">
                <img class="product-img" src="{{ $firstItem['img'] ?? '' }}" alt="{{ $firstItem['name'] ?? 'Produk Greatfit' }}" onerror="this.style.display='none'">
                <div>
                    <div class="product-name">{{ $firstItem['name'] ?? 'Produk Greatfit' }}</div>
                    <div class="product-variant">
                        {{ $firstItem['color'] ?? 'Warna' }}@if(!empty($firstItem['size'])) / {{ $firstItem['size'] }}@endif
                    </div>
                    @if(count($items) > 1)
                    <div class="product-more">+{{ count($items) - 1 }} produk lainnya</div>
                    @endif
                </div>
                <div class="product-side">
                    <div class="product-price">Rp{{ number_format($firstPrice, 0, ',', '.') }}</div>
                    <div class="product-qty">x{{ $firstQty }}</div>
                </div>
            </div>

            <div class="order-total-row">
                Total {{ count($items) }} produk: <strong>Rp{{ number_format($order['total_amount'] ?? 0, 0, ',', '.') }}</strong>
            </div>

            <div class="order-note">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18v10H3z"/><path d="M7 7V5h10v2"/><path d="M7 17v2h10v-2"/></svg>
                <span>{{ $order['shipping_courier'] ? 'Pengiriman ' . strtoupper($order['shipping_courier']) . ' ' . ($order['shipping_service'] ?? '') : 'Detail pengiriman akan diperbarui setelah pesanan diproses.' }}</span>
            </div>

            <div class="order-actions">
                <a href="{{ route('storefront.products') }}" class="order-btn">Beli lagi</a>
                @if(($order['status'] ?? 'pending') === 'pending')
                <a href="{{ route('storefront.order.success', $order['order_number']) }}" class="order-btn primary">Bayar</a>
                @else
                <a href="{{ route('storefront.order.success', $order['order_number']) }}" class="order-btn primary">Detail</a>
                @endif
            </div>
        </article>
        @endforeach
    </div>
    @else
    @php
        $emptyByFilter = !empty($activeStatus);
        $statusLabels  = ['pending' => 'Menunggu', 'processing' => 'Dikemas', 'shipped' => 'Dikirim', 'delivered' => 'Selesai', 'cancelled' => 'Dibatalkan'];
        $activeLabel   = $statusLabels[$activeStatus] ?? '';
    @endphp
    <div class="empty">
        <div class="empty-illus">
            @if($emptyByFilter)
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            @else
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            @endif
        </div>

        @if($emptyByFilter)
        <div class="empty-title">Tidak ada pesanan "{{ $activeLabel }}"</div>
        <div class="empty-copy">Belum ada pesanan dengan status ini. Cek tab lain atau lihat semua pesananmu.</div>
        <div class="empty-actions">
            <a href="{{ route('storefront.user.orders') }}" class="empty-btn-primary">Lihat semua pesanan</a>
            <a href="{{ route('storefront.products') }}" class="empty-btn-secondary">Belanja lagi</a>
        </div>
        @else
        <div class="empty-title">Belum ada pesanan</div>
        <div class="empty-copy">Semua pesanan yang kamu checkout akan tampil di sini. Yuk, mulai pilih outfit Greatfit-mu!</div>
        <div class="empty-actions">
            <a href="{{ route('storefront.products') }}" class="empty-btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Lihat produk
            </a>
        </div>
        @endif
    </div>
    @endif
</div>
</div>
@endsection
