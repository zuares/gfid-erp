@extends('storefront.layouts.app')

@section('title', 'Profil Saya — Greatfit')

@push('styles')
<style>
    body { background: var(--white); }
    .wrap { width: min(760px, calc(100% - 32px)); margin: 0 auto; }
    .page { padding: 20px 0 calc(48px + var(--safe)); }

    /* ── PROFILE HERO ── */
    .profile-hero {
        background: linear-gradient(180deg, #ffffff 0%, #fafafa 100%);
        border: 1px solid var(--line); border-radius: 22px;
        padding: 20px; margin-bottom: 12px; color: var(--ink); overflow: hidden;
    }
    .ph-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px; }
    .ph-avatar-wrap { display: flex; align-items: center; gap: 14px; }
    .ph-avatar {
        width: 52px; height: 52px; border-radius: 50%;
        background: var(--ink); color: var(--white);
        display: grid; place-items: center;
        font-family: 'Barlow Condensed', sans-serif;
        font-size: 26px; font-weight: 900; flex-shrink: 0;
    }
    .ph-info-name { font-size: 18px; font-weight: 800; letter-spacing: -.02em; line-height: 1.1; margin-bottom: 4px; }
    .ph-info-phone { font-size: 12.5px; color: var(--mid); font-weight: 600; }
    .ph-greeting { margin-top: 18px; max-width: 520px; }
    .ph-greeting-title { font-size: 22px; font-weight: 900; letter-spacing: -.03em; line-height: 1.12; }
    .ph-greeting-copy { margin-top: 7px; color: var(--mid); font-size: 13px; font-weight: 600; line-height: 1.6; }
    .ph-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 18px; }
    .ph-btn { min-height: 38px; padding: 0 15px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; gap: 7px; font-size: 12px; font-weight: 850; border: 1px solid var(--line); background: var(--white); color: var(--ink); }
    .ph-btn.primary { background: var(--ink); color: var(--white); border-color: var(--ink); }
    .ph-role-pill {
        display: inline-flex; align-items: center; gap: 5px;
        height: 22px; padding: 0 10px; border-radius: 999px;
        font-size: 10px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; margin-top: 7px;
    }
    .ph-role-customer { background: var(--ink); color: var(--white); }
    .ph-role-prospect { background: var(--soft); color: var(--mid); border: 1px solid var(--line); }
    .btn-keluar {
        height: 30px; padding: 0 12px; border-radius: 999px;
        border: 1px solid var(--line); background: var(--white); color: var(--mid);
        font-family: var(--font-body); font-size: 11px; font-weight: 700;
        cursor: pointer; transition: background .15s;
        display: flex; align-items: center; gap: 5px; white-space: nowrap;
    }
    .btn-keluar:hover { background: var(--soft); color: var(--ink); }

    /* ── CTA / NOTICE ── */
    .cta-card {
        background: var(--ink); border-radius: 16px; padding: 20px; margin-bottom: 12px;
        display: flex; align-items: center; justify-content: space-between; gap: 16px;
    }
    .cta-text { flex: 1; }
    .cta-title { font-size: 15px; font-weight: 850; color: var(--white); margin-bottom: 4px; }
    .cta-sub   { font-size: 12.5px; color: rgba(255,255,255,.64); font-weight: 600; line-height: 1.5; }
    .cta-btn {
        display: inline-flex; align-items: center; gap: 6px;
        height: 38px; padding: 0 16px;
        background: var(--white); color: var(--ink);
        font-family: var(--font-body); font-size: 13px; font-weight: 700;
        border-radius: 999px; white-space: nowrap; transition: opacity .15s;
    }
    .cta-btn:hover { opacity: .85; }
    .notice-card {
        display: flex; align-items: center; justify-content: space-between; gap: 14px;
        background: var(--ink); color: var(--white);
        border-radius: 18px; padding: 14px 16px; margin-bottom: 12px;
    }
    .notice-main { display: flex; align-items: center; gap: 11px; min-width: 0; }
    .notice-ic { width: 34px; height: 34px; border-radius: 50%; background: rgba(255,255,255,.12); display: grid; place-items: center; flex: 0 0 auto; }
    .notice-title { font-size: 13px; font-weight: 850; line-height: 1.25; }
    .notice-sub { margin-top: 2px; font-size: 11px; color: rgba(255,255,255,.62); font-weight: 600; line-height: 1.35; }
    .notice-link { min-height: 32px; padding: 0 12px; border-radius: 999px; background: var(--white); color: var(--ink); display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 850; white-space: nowrap; }

    /* ── ORDER HUB ── */
    .order-hub { background: var(--white); border: 1px solid var(--line); border-radius: 18px; overflow: hidden; margin-bottom: 12px; }
    .order-hub-head { padding: 15px 16px 8px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .order-hub-title { font-size: 16px; font-weight: 900; letter-spacing: -.02em; }
    .order-hub-link { display: inline-flex; align-items: center; gap: 5px; color: var(--mid); font-size: 12px; font-weight: 750; white-space: nowrap; }
    .status-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; padding: 8px 12px 14px; }
    .status-card { position: relative; min-width: 0; min-height: 84px; border-radius: 14px; display: grid; place-items: center; align-content: center; gap: 8px; color: var(--ink); transition: background .15s; }
    .status-card:hover { background: var(--soft); }
    .status-ic { width: 32px; height: 32px; display: grid; place-items: center; color: var(--ink); }
    .status-label { font-size: 11px; font-weight: 750; text-align: center; line-height: 1.25; }
    .status-count { position: absolute; top: 8px; right: 14px; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px; background: var(--ink); color: var(--white); font-size: 9px; font-weight: 900; display: grid; place-items: center; }

    /* ── SECTIONS ── */
    .section { background: var(--white); border: 1px solid var(--line); border-radius: 18px; overflow: hidden; margin-bottom: 12px; }
    .section-head { padding: 15px 16px 13px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--line); }
    .section-accordion { background: var(--white); border: 1px solid var(--line); border-radius: 18px; overflow: hidden; margin-bottom: 12px; }
    .section-accordion .section-head { list-style: none; cursor: pointer; }
    .section-accordion .section-head::-webkit-details-marker { display: none; }
    .section-meta { display: inline-flex; align-items: center; gap: 9px; }
    .section-chevron { color: var(--mid); transition: transform .15s; }
    .section-accordion[open] .section-chevron { transform: rotate(90deg); }
    .section-title { font-size: 13px; font-weight: 800; letter-spacing: -.01em; display: flex; align-items: center; gap: 7px; color: var(--ink); }
    .section-title svg { color: var(--mid); }
    .section-action { font-size: 11px; font-weight: 700; color: var(--mid); }
    .section-action:hover { color: var(--ink); }

    /* ── ADDRESS ── */
    .addr-body { padding: 14px 16px; }
    .addr-line { display: flex; align-items: flex-start; gap: 10px; }
    .addr-ic { width: 32px; height: 32px; border-radius: 9px; background: var(--soft); display: grid; place-items: center; color: var(--mid); flex-shrink: 0; }
    .addr-text { flex: 1; }
    .addr-main { font-size: 13px; font-weight: 700; line-height: 1.45; }
    .addr-sub  { font-size: 12px; color: var(--mid); font-weight: 500; margin-top: 2px; line-height: 1.4; }
    .addr-note { margin-top: 10px; padding: 9px 12px; background: var(--soft); border-radius: 10px; font-size: 12px; font-weight: 600; color: var(--mid); display: flex; align-items: flex-start; gap: 6px; }
    .addr-empty { padding: 20px 16px; display: flex; align-items: center; gap: 12px; }
    .addr-empty-ic { width: 38px; height: 38px; border-radius: 10px; background: var(--soft); display: grid; place-items: center; color: var(--mid); flex-shrink: 0; }
    .addr-empty-t { font-size: 13px; font-weight: 700; }
    .addr-empty-s { font-size: 11px; color: var(--mid); font-weight: 500; margin-top: 2px; }
    .orders-empty-link {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 18px; border-radius: 999px;
        background: var(--ink); color: var(--white);
        font-size: 12.5px; font-weight: 700;
    }

    /* ── STATUS BADGES ── */
    .order-badge { display: inline-flex; align-items: center; height: 22px; padding: 0 9px; border-radius: 999px; font-size: 9px; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; flex-shrink: 0; }
    .badge-pending    { background: #fef9c3; color: #854d0e; }
    .badge-processing { background: #dbeafe; color: #1e40af; }
    .badge-shipped    { background: #e0e7ff; color: #3730a3; }
    .badge-delivered  { background: #dcfce7; color: #166534; }
    .badge-cancelled  { background: #fee2e2; color: #991b1b; }

    /* ── QUICK LINKS ── */
    .links-list { display: flex; flex-direction: column; }
    .link-item { padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; font-size: 13px; font-weight: 700; border-bottom: 1px solid var(--line); transition: background .1s; }
    .link-item:last-child { border-bottom: none; }
    .link-item:hover { background: var(--soft); }
    .link-item-l { display: flex; align-items: center; gap: 10px; }
    .link-ic { width: 32px; height: 32px; border-radius: 50%; background: var(--soft); display: grid; place-items: center; color: var(--mid); flex-shrink: 0; }
    .link-item:hover .link-ic { background: var(--line); }
    .link-arr { color: var(--line); }
    .link-item:hover .link-arr { color: var(--mid); }
    .badge-count { background: var(--ink); color: #fff; font-size: 9px; font-weight: 800; padding: 2px 7px; border-radius: 999px; margin-left: 4px; }

    /* ── USER PAGE FOOTER ── */
    .user-foot { background: var(--white); border-top: 1px solid var(--line); }
    .user-foot-inner {
        max-width: 680px; margin: 0 auto; padding: 16px 16px calc(16px + var(--safe));
        display: flex; align-items: center; justify-content: space-between;
    }
    .user-foot-inner span { font-size: 11px; color: var(--mid); font-weight: 500; }
    .user-foot-inner button { background: none; border: none; font-size: 11px; color: var(--mid); font-weight: 700; cursor: pointer; font-family: var(--font-body); }
    .user-foot-inner button:hover { color: var(--ink); }

    @@media (max-width: 719px) {
        .wrap { width: min(520px, calc(100% - 24px)); }
        .profile-hero { padding: 16px; border-radius: 18px; }
        .ph-avatar { width: 48px; height: 48px; font-size: 24px; }
        .ph-info-name { font-size: 17px; }
        .ph-greeting { margin-top: 16px; }
        .ph-greeting-title { font-size: 20px; }
        .ph-greeting-copy { font-size: 12.5px; }
        .ph-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .ph-btn { padding: 0 12px; font-size: 11.5px; }
        .cta-card { align-items: flex-start; flex-direction: column; }
        .cta-btn { width: 100%; justify-content: center; }
        .notice-card { align-items: flex-start; flex-direction: column; }
        .notice-link { width: 100%; }
        .order-hub-title { font-size: 15px; }
        .status-grid { padding: 6px 8px 12px; }
        .status-card { min-height: 78px; gap: 7px; }
        .status-ic { width: 30px; height: 30px; }
        .status-label { font-size: 10.5px; }
        .status-count { top: 7px; right: 8px; }
        .addr-empty { align-items: flex-start !important; flex-direction: column; }
        .addr-empty > a { width: 100%; text-align: center; justify-content: center; display: inline-flex; }
    }
</style>
@endpush

@php $navActive = 'user'; @endphp

@section('footer')
<footer class="user-foot">
    <div class="user-foot-inner">
        <span>© {{ date('Y') }} Greatfit</span>
        <form method="POST" action="{{ route('storefront.logout') }}" style="display:inline">
            @csrf
            <button type="submit">Keluar dari akun</button>
        </form>
    </div>
</footer>
@endsection

@section('content')
@php
    $cartCount    = array_sum(array_column(session('cart', []), 'qty'));
    $totalOrders  = count($orders);
    $addr         = $address;
    $isCustomer   = $role === 'customer';
    $statusCounts = collect($orders)->countBy(fn($order) => $order['status'] ?? 'pending');
@endphp

<div class="wrap">
<div class="page">

{{-- ── PROFILE HERO ── --}}
<div class="profile-hero">
    <div class="ph-top">
        <div class="ph-avatar-wrap">
            <div class="ph-avatar">{{ $customer->initial }}</div>
            <div>
                <div class="ph-info-name">{{ $customer->first_name }}</div>
                <div class="ph-info-phone">{{ $customer->phone_display }}</div>
                <div class="ph-role-pill {{ $isCustomer ? 'ph-role-customer' : 'ph-role-prospect' }}">
                    @if($isCustomer) ✓ Konsumen @else Calon Konsumen @endif
                </div>
            </div>
        </div>
        <form method="POST" action="{{ route('storefront.logout') }}">
            @csrf
            <button type="submit" class="btn-keluar">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Keluar
            </button>
        </form>
    </div>
    <div class="ph-greeting">
        <div class="ph-greeting-title">
            @if($isCustomer)
                Senang lihat kamu lagi, {{ $customer->first_name }}.
            @else
                Halo {{ $customer->first_name }}, selamat datang di Greatfit.
            @endif
        </div>
        <div class="ph-greeting-copy">
            @if($isCustomer)
                Pantau pesanan, simpan alamat, dan lanjutkan belanja dengan lebih cepat dari sini.
            @else
                Lengkapi alamat dan mulai pilih outfit nyaman yang paling pas untuk harimu.
            @endif
        </div>
        <div class="ph-actions">
            <a href="{{ route('storefront.products') }}" class="ph-btn primary">
                {{ $isCustomer ? 'Belanja lagi' : 'Mulai belanja' }}
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
            </a>
            <a href="{{ route('storefront.checkout.address', ['return_to' => url('/user')]) }}" class="ph-btn">
                {{ !empty($addr['detail']) ? 'Ubah alamat' : 'Isi alamat' }}
            </a>
        </div>
    </div>
    <div style="height:2px;"></div>
</div>

{{-- ── CTA BELANJA (prospect only) ── --}}
@if(!$isCustomer)
<div class="cta-card">
    <div class="cta-text">
        <div class="cta-title">Belum pernah order?</div>
        <div class="cta-sub">Temukan produk Greatfit pilihan kamu dan mulai belanja sekarang.</div>
    </div>
    <a href="{{ route('storefront.products') }}" class="cta-btn">
        Belanja
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
    </a>
</div>
@endif

@if(empty($addr['detail']))
<div class="notice-card">
    <div class="notice-main">
        <div class="notice-ic">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4Z"/></svg>
        </div>
        <div>
            <div class="notice-title">Lengkapi alamat pengiriman</div>
            <div class="notice-sub">Checkout berikutnya jadi lebih cepat dan ongkir lebih mudah dihitung.</div>
        </div>
    </div>
    <a href="{{ route('storefront.checkout.address', ['return_to' => url('/user')]) }}" class="notice-link">Atur sekarang</a>
</div>
@endif

{{-- ── ORDER HUB ── --}}
<div class="order-hub">
    <div class="order-hub-head">
        <div class="order-hub-title">Pesanan Saya</div>
        <a href="{{ route('storefront.user.orders') }}" class="order-hub-link">
            Lihat riwayat
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </a>
    </div>
    <div class="status-grid">
        @php
            $statusItems = [
                ['key' => 'pending',    'label' => 'Menunggu', 'icon' => 'wallet'],
                ['key' => 'processing', 'label' => 'Dikemas',  'icon' => 'box'],
                ['key' => 'shipped',    'label' => 'Dikirim',  'icon' => 'truck'],
                ['key' => 'delivered',  'label' => 'Selesai',  'icon' => 'star'],
            ];
        @endphp
        @foreach($statusItems as $status)
        @php $count = (int) ($statusCounts[$status['key']] ?? 0); @endphp
        <a href="{{ route('storefront.user.orders', ['status' => $status['key']]) }}" class="status-card">
            <span class="status-ic">
                @if($status['icon'] === 'wallet')
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16v12H4z"/><path d="M4 7l3-4h10l3 4"/><path d="M16 13h4"/></svg>
                @elseif($status['icon'] === 'box')
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
                @elseif($status['icon'] === 'truck')
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17H5V6h9v11h-1"/><path d="M14 9h4l3 4v4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="16" cy="17" r="2"/></svg>
                @else
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3l-5.6 2.9 1.1-6.2L3 9.6l6.2-.9L12 3Z"/></svg>
                @endif
            </span>
            <span class="status-label">{{ $status['label'] }}</span>
            @if($count > 0)<span class="status-count">{{ $count }}</span>@endif
        </a>
        @endforeach
    </div>
</div>

{{-- ── ALAMAT ── --}}
<details class="section-accordion">
    <summary class="section-head">
        <div class="section-title">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Alamat utama
        </div>
        <span class="section-meta">
            <span class="section-action">{{ !empty($addr['detail']) ? 'Tersimpan' : 'Belum diisi' }}</span>
            <svg class="section-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </span>
    </summary>

    @if(!empty($addr['detail']))
    <div class="addr-body">
        <div class="addr-line">
            <div class="addr-ic">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div class="addr-text">
                <div class="addr-main">{{ $addr['detail'] }}@if(!empty($addr['village_name'])), {{ $addr['village_name'] }}@endif</div>
                <div class="addr-sub">
                    @if(!empty($addr['district_name']))Kec. {{ $addr['district_name'] }}, @endif
                    {{ $addr['city_name'] ?? '' }}@if(!empty($addr['province_name'])), {{ $addr['province_name'] }}@endif
                    @if(!empty($addr['postal_code'])) {{ $addr['postal_code'] }}@endif
                </div>
            </div>
        </div>
        @if(!empty($addr['note']))
        <div class="addr-note">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ $addr['note'] }}
        </div>
        @endif
        <a href="{{ route('storefront.checkout.address', ['return_to' => url('/user')]) }}" class="orders-empty-link" style="margin-top:14px;">Ubah alamat</a>
    </div>
    @else
    <div class="addr-empty" style="justify-content: space-between;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="addr-empty-ic">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div>
                <div class="addr-empty-t">Alamat belum diisi</div>
                <div class="addr-empty-s">Simpan alamat agar checkout berikutnya lebih cepat.</div>
            </div>
        </div>
        <a href="{{ route('storefront.checkout.address', ['return_to' => '/user']) }}"
           style="flex-shrink:0;font-size:12px;font-weight:700;color:var(--ink);border:1.5px solid var(--line);padding:6px 14px;border-radius:999px;white-space:nowrap;transition:border-color .15s;"
           onmouseover="this.style.borderColor='var(--ink)'" onmouseout="this.style.borderColor='var(--line)'">
            Isi Alamat
        </a>
    </div>
    @endif
</details>

{{-- ── QUICK LINKS ── --}}
<div class="section">
    <div class="links-list">
        <a href="{{ route('storefront.products') }}" class="link-item">
            <div class="link-item-l">
                <div class="link-ic">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </div>
                Cari produk
            </div>
            <svg class="link-arr" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="{{ route('storefront.cart') }}" class="link-item">
            <div class="link-item-l">
                <div class="link-ic">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                </div>
                Keranjang saya
                @if($cartCount > 0)<span class="badge-count">{{ $cartCount }}</span>@endif
            </div>
            <svg class="link-arr" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="{{ route('storefront.checkout') }}" class="link-item">
            <div class="link-item-l">
                <div class="link-ic">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 010-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 000-5C13 2 12 7 12 7z"/></svg>
                </div>
                Lanjut checkout
            </div>
            <svg class="link-arr" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </a>
    </div>
</div>

</div>
</div>
@endsection
