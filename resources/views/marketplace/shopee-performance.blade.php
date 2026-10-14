@extends('layouts.app')

@section('title', 'Shopee Performance • Marketplace')

@include('marketplace._shared')

@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.');
    $number = fn ($value) => number_format((float) $value, 0, ',', '.');
    $percent = fn ($value) => number_format(((float) $value) * 100, 2, ',', '.') . '%';
    $maxSales = max(1, (float) $daily->max('sales'));
@endphp

@push('head')
<style>
    .sp-page { max-width: 1320px; margin: 0 auto; padding-bottom: 2rem; }
    .sp-card { border: 1px solid var(--shp-border, rgba(148,163,184,.2)); border-radius: 14px; background: var(--card, #fff); }
    .sp-kpi { min-height: 112px; padding: 1rem; }
    .sp-kpi-label { color: #64748b; font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
    .sp-kpi-value { margin-top: .3rem; color: var(--text, #0f172a); font-size: 1.35rem; font-weight: 900; line-height: 1.15; }
    .sp-kpi-note { margin-top: .28rem; color: #94a3b8; font-size: .7rem; }
    .sp-muted { color: #64748b; font-size: .78rem; }
    .sp-chart { display: flex; align-items: end; gap: .38rem; min-height: 220px; padding: 1.1rem 1rem .55rem; overflow-x: auto; }
    .sp-bar-group { display: flex; flex: 1 0 42px; min-width: 42px; height: 180px; flex-direction: column; justify-content: end; align-items: center; gap: .35rem; }
    .sp-bar { width: min(32px, 70%); min-height: 2px; border-radius: 7px 7px 2px 2px; background: linear-gradient(180deg, #f97316, #ea580c); }
    .sp-bar-label { color: #64748b; font-size: .62rem; white-space: nowrap; transform: rotate(-38deg); transform-origin: center; margin-top: .45rem; }
    .sp-bar-value { color: #94a3b8; font-size: .58rem; white-space: nowrap; }
    .sp-table th { color: #64748b; font-size: .68rem; white-space: nowrap; }
    .sp-table td { font-size: .76rem; vertical-align: middle; }
    .sp-note { border-left: 3px solid #f97316; background: rgba(249,115,22,.07); color: #7c2d12; font-size: .76rem; }
    body[data-theme="dark"] .sp-note { color: #fed7aa; }
    body[data-theme="dark"] .sp-card { background: #0f172a; }
    body[data-theme="dark"] .sp-kpi-value { color: #f8fafc; }
    @media (max-width: 768px) { .sp-kpi { min-height: 96px; } .sp-kpi-value { font-size: 1.05rem; } }
</style>
@endpush

<div class="sp-page">
    <div class="ship-topbar">
        <div>
            <div class="title"><i class="bi bi-shop-window me-1"></i>Shopee Performance</div>
            <div class="sub">Traffic, conversion, voucher, flash sale, dan performa toko dari Brand Portal</div>
        </div>
        <a href="{{ route('marketplace.dashboard.sales') }}" class="btn btn-sm btn-ship-outline btn-pill">
            <i class="bi bi-arrow-left me-1"></i>Dashboard Penjualan
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning py-2 small">{{ session('warning') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
    @endif

    @if (! $hasPrincipal)
        <div class="alert alert-warning small">
            <strong>Principal ID belum tersedia.</strong>
            Tambahkan <code>principal_id</code> di credentials toko Shopee atau <code>SHOPEE_PRINCIPAL_ID</code> di environment, lalu pastikan aplikasi memiliki permission <em>Brand Portal Service</em>.
        </div>
    @endif

    <div class="sp-card p-3 mb-3">
        <form method="GET" action="{{ route('marketplace.shopee-performance') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold">Toko</label>
                <select name="store_id" class="form-select form-select-sm">
                    <option value="">Semua toko Shopee</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" @selected($selectedStoreId === $store->id)>{{ $store->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold">Dari</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $from->toDateString() }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold">Sampai</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $to->toDateString() }}">
            </div>
            <div class="col-12 col-md-auto">
                <button class="btn btn-sm btn-ship-primary btn-pill"><i class="bi bi-funnel me-1"></i>Tampilkan</button>
            </div>
        </form>
        <form method="POST" action="{{ route('marketplace.shopee-performance.sync') }}" class="mt-2">
            @csrf
            <input type="hidden" name="store_id" value="{{ $selectedStoreId }}">
            <input type="hidden" name="date_from" value="{{ $from->toDateString() }}">
            <input type="hidden" name="date_to" value="{{ $to->toDateString() }}">
            <button class="btn btn-sm btn-outline-warning btn-pill" onclick="return confirm('Sync ulang metrik Shopee untuk periode ini?');">
                <i class="bi bi-arrow-repeat me-1"></i>Sync periode
            </button>
        </form>
        <div class="sp-muted mt-2">
            Data API Shopee tidak ditarik untuk hari berjalan. Terakhir diperbarui:
            <strong>{{ $lastFetchedAt ? $lastFetchedAt->format('d M Y H:i') : 'belum ada snapshot' }}</strong>
        </div>
    </div>

    <div class="sp-note rounded p-2 mb-3">
        <i class="bi bi-info-circle me-1"></i>
        <strong>Catatan definisi:</strong> Sales Shopee adalah nilai order paid dan unpaid setelah seller rebate, dan dapat mencakup cancelled serta return/refund. Angka ini bukan penjualan netto akuntansi.
    </div>

    <div class="row g-2 mb-3">
        @foreach ([
            ['label' => 'Sales Shopee', 'value' => $money($summary['sales']), 'note' => 'bukan net sales', 'icon' => 'bi-currency-dollar'],
            ['label' => 'Orders', 'value' => $number($summary['orders']), 'note' => 'placed orders', 'icon' => 'bi-bag-check'],
            ['label' => 'Units Sold', 'value' => $number($summary['units_sold']), 'note' => 'unit terjual', 'icon' => 'bi-box-seam'],
            ['label' => 'Product Views', 'value' => $number($summary['product_views']), 'note' => 'kunjungan halaman produk', 'icon' => 'bi-eye'],
            ['label' => 'Unique Visitors', 'value' => $number($summary['unique_visitors']), 'note' => 'pengunjung unik', 'icon' => 'bi-people'],
            ['label' => 'Order Conversion', 'value' => $percent($summary['order_conversion_rate']), 'note' => 'orders / product clicks', 'icon' => 'bi-funnel'],
            ['label' => 'Item Conversion', 'value' => $percent($summary['item_conversion_rate']), 'note' => 'units / product views', 'icon' => 'bi-cart-check'],
            ['label' => 'Average Basket', 'value' => $money($summary['average_basket_size']), 'note' => 'sales / order', 'icon' => 'bi-basket'],
        ] as $kpi)
            <div class="col-6 col-md-3">
                <div class="sp-card sp-kpi">
                    <div class="d-flex justify-content-between gap-2"><span class="sp-kpi-label">{{ $kpi['label'] }}</span><i class="bi {{ $kpi['icon'] }} text-warning"></i></div>
                    <div class="sp-kpi-value">{{ $kpi['value'] }}</div>
                    <div class="sp-kpi-note">{{ $kpi['note'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="sp-card">
                <div class="p-3 border-bottom">
                    <div class="fw-bold">Sales harian dari Shopee</div>
                    <div class="sp-muted">Periode {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</div>
                </div>
                @if ($daily->isEmpty())
                    <div class="text-center text-muted small py-5">Belum ada snapshot. Klik <strong>Sync periode</strong> untuk menarik data Shopee.</div>
                @else
                    <div class="sp-chart">
                        @foreach ($daily as $row)
                            <div class="sp-bar-group" title="{{ $row['date'] }} · {{ $money($row['sales']) }}">
                                <span class="sp-bar-value">{{ $money($row['sales']) }}</span>
                                <div class="sp-bar" style="height: {{ max(2, min(100, ((float) $row['sales'] / $maxSales) * 100)) }}%;"></div>
                                <span class="sp-bar-label">{{ CarbonCarbon::parse($row['date'])->format('d/m') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="sp-card h-100">
                <div class="p-3 border-bottom">
                    <div class="fw-bold">Ringkasan per toko</div>
                    <div class="sp-muted">Ranking berdasarkan Sales Shopee</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm sp-table mb-0">
                        <thead><tr><th>Toko</th><th class="text-end">Sales</th><th class="text-end">Order</th></tr></thead>
                        <tbody>
                        @forelse ($byStore as $row)
                            <tr><td class="fw-semibold">{{ $row['store_name'] }}</td><td class="text-end">{{ $money($row['sales']) }}</td><td class="text-end">{{ $number($row['orders']) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">Belum ada data.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="sp-card mt-3">
        <div class="p-3 border-bottom">
            <div class="fw-bold">Promosi & funnel</div>
            <div class="sp-muted">Nilai agregat sesuai definisi Shopee API</div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm sp-table mb-0">
                <thead><tr><th>Metrik</th><th class="text-end">Nilai</th><th>Keterangan</th></tr></thead>
                <tbody>
                    <tr><td>Flash Sale Sales</td><td class="text-end">{{ $money($summary['flash_sale_sales']) }}</td><td class="text-muted">paid dan unpaid flash sale</td></tr>
                    <tr><td>Voucher Sales</td><td class="text-end">{{ $money($summary['voucher_sales']) }}</td><td class="text-muted">order menggunakan voucher</td></tr>
                    <tr><td>Voucher Cost</td><td class="text-end">{{ $money($summary['voucher_cost']) }}</td><td class="text-muted">biaya voucher saat checkout</td></tr>
                    <tr><td>Product Clicks</td><td class="text-end">{{ $number($summary['product_clicks']) }}</td><td class="text-muted">klik kartu produk</td></tr>
                    <tr><td>Items per Order</td><td class="text-end">{{ number_format($summary['items_per_order'], 2, ',', '.') }}</td><td class="text-muted">units sold / orders</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
