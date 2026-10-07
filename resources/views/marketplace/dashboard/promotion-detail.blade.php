@extends('layouts.app')

@section('title', 'Detail Promosi • Marketplace')

@include('marketplace._shared')

@push('head')
<style>
    .promotion-detail {
        --promotion-line: var(--line, #d4d7e3);
        --promotion-muted: var(--muted, #6b7280);
        --promotion-card: var(--card, #fff);
        --promotion-soft: var(--card-soft, #f9fafb);
        --promotion-ink: var(--text, #111827);
        --promotion-primary: var(--accent, #2563eb);
        --promotion-primary-soft: var(--accent-soft, #dbeafe);
        max-width: 1400px;
        margin-inline: auto;
        color: var(--promotion-ink);
    }
    .promotion-detail .promotion-card {
        border: 1px solid var(--promotion-line);
        border-radius: 1rem;
        background: var(--promotion-card);
        box-shadow: 0 .5rem 1.5rem rgba(15, 23, 42, .045);
    }
    .promotion-detail .promotion-hero {
        border: 1px solid color-mix(in srgb, var(--promotion-primary) 18%, var(--promotion-line));
        border-radius: 1rem;
        background: linear-gradient(135deg, color-mix(in srgb, var(--promotion-primary-soft) 52%, var(--promotion-card)), var(--promotion-card) 68%);
        box-shadow: 0 .5rem 1.5rem rgba(15, 23, 42, .045);
    }
    .promotion-detail .promotion-back {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border: 1px solid var(--promotion-line);
        border-radius: 999px;
        padding: .35rem .65rem;
        color: var(--promotion-muted);
        background: color-mix(in srgb, var(--promotion-card) 82%, transparent);
        font-size: .75rem;
        font-weight: 700;
        text-decoration: none;
    }
    .promotion-detail .promotion-back:hover { color: var(--promotion-primary); border-color: var(--promotion-primary); }
    .promotion-detail .promotion-kicker {
        color: var(--promotion-primary);
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
    }
    .promotion-detail .promotion-title { color: var(--promotion-ink); letter-spacing: -.045em; font-weight: 800; }
    .promotion-detail .promotion-muted { color: var(--promotion-muted); }
    .promotion-detail .promotion-period {
        display: inline-flex;
        align-items: center;
        gap: .7rem;
        min-width: 11rem;
        padding: .8rem .9rem;
        border: 1px solid var(--promotion-line);
        border-radius: .85rem;
        background: color-mix(in srgb, var(--promotion-card) 84%, transparent);
    }
    .promotion-detail .promotion-period-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: .7rem;
        background: var(--promotion-primary-soft);
        color: var(--promotion-primary);
    }
    .promotion-detail .promotion-period-label { color: var(--promotion-muted); font-size: .65rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .promotion-detail .promotion-period-value { color: var(--promotion-ink); font-size: .9rem; font-weight: 800; }
    .promotion-detail .promotion-filter { background: var(--promotion-soft); }
    .promotion-detail .promotion-filter .form-label { color: var(--promotion-muted); font-size: .74rem; font-weight: 700; margin-bottom: .3rem; }
    .promotion-detail .promotion-filter .form-select { border-color: var(--promotion-line); border-radius: .65rem; background: var(--promotion-card); color: var(--promotion-ink); font-size: .8rem; }
    .promotion-detail .promotion-filter .btn { border-radius: .65rem; font-weight: 750; }
    .promotion-detail .sales-card { border: 1px solid var(--promotion-line); border-radius: 1rem; background: var(--promotion-card); box-shadow: 0 .5rem 1.5rem rgba(15, 23, 42, .045); }
    .promotion-detail .sales-kpi { min-height: 124px; position: relative; overflow: hidden; transition: transform .18s ease, box-shadow .18s ease; }
    .promotion-detail .sales-kpi::after { content: ''; position: absolute; right: -2rem; bottom: -2.7rem; width: 7rem; height: 7rem; border-radius: 50%; background: var(--promotion-primary); opacity: .055; }
    .promotion-detail .sales-kpi:hover { transform: translateY(-2px); box-shadow: 0 .75rem 1.75rem rgba(15, 23, 42, .09); }
    .promotion-detail .sales-kpi-label { color: var(--promotion-muted); font-size: .7rem; font-weight: 800; letter-spacing: .02em; }
    .promotion-detail .sales-kpi-icon { position: relative; z-index: 1; display: inline-flex; align-items: center; justify-content: center; width: 2.15rem; height: 2.15rem; border-radius: .7rem; background: var(--promotion-primary-soft); color: var(--promotion-primary); }
    .promotion-detail .sales-kpi-value { color: var(--promotion-ink); font-size: 1.35rem; font-weight: 850; letter-spacing: -.04em; }
    .promotion-detail .sales-kpi-note { color: var(--promotion-muted); font-size: .72rem; }
    .promotion-detail .sales-kpi--success::after { background: #16a34a; }
    .promotion-detail .sales-kpi--success .sales-kpi-icon { background: #dcfce7; color: #15803d; }
    .promotion-detail .sales-kpi--warning::after { background: #ea580c; }
    .promotion-detail .sales-kpi--warning .sales-kpi-icon { background: #ffedd5; color: #c2410c; }
    .promotion-detail .promotion-table-toolbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.25rem; border-bottom: 1px solid var(--promotion-line); }
    .promotion-detail .promotion-table-title { color: var(--promotion-ink); font-size: .98rem; font-weight: 800; }
    .promotion-detail .promotion-table-subtitle { color: var(--promotion-muted); font-size: .74rem; }
    .promotion-detail .promotion-count { border: 1px solid var(--promotion-line); border-radius: 999px; padding: .4rem .7rem; color: var(--promotion-muted); background: var(--promotion-soft); font-size: .7rem; font-weight: 800; white-space: nowrap; }
    .promotion-detail .promotion-table { --bs-table-bg: var(--promotion-card); --bs-table-color: var(--promotion-ink); margin-bottom: 0; }
    .promotion-detail .promotion-table th { padding-top: .75rem; padding-bottom: .75rem; background: var(--promotion-soft); border-bottom-color: var(--promotion-line); color: var(--promotion-muted); font-size: .66rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; white-space: nowrap; }
    .promotion-detail .promotion-table td { padding-top: .8rem; padding-bottom: .8rem; border-color: color-mix(in srgb, var(--promotion-line) 65%, transparent); font-size: .78rem; white-space: nowrap; }
    .promotion-detail .promotion-table tbody tr { transition: background-color .15s ease; }
    .promotion-detail .promotion-table tbody tr:hover { --bs-table-hover-bg: color-mix(in srgb, var(--promotion-primary-soft) 42%, var(--promotion-card)); }
    .promotion-detail .promotion-order-link { display: inline-flex; align-items: center; gap: .35rem; color: var(--promotion-primary); font-weight: 800; letter-spacing: -.01em; text-decoration: none; }
    .promotion-detail .promotion-order-link:hover { text-decoration: underline; }
    .promotion-detail .promotion-order-link i { font-size: .7rem; opacity: .65; transition: transform .15s ease; }
    .promotion-detail .promotion-order-link:hover i { transform: translateX(2px); }
    .promotion-detail .promotion-date { color: var(--promotion-ink); font-weight: 700; }
    .promotion-detail .promotion-time { color: var(--promotion-muted); font-size: .7rem; }
    .promotion-detail .promotion-payment { display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px; padding: .32rem .55rem; background: var(--promotion-soft); color: var(--promotion-ink); font-size: .7rem; font-weight: 750; }
    .promotion-detail .promotion-payment::before { content: ''; width: .38rem; height: .38rem; border-radius: 50%; background: #16a34a; }
    .promotion-detail .promotion-amount { color: var(--promotion-ink); font-weight: 800; }
    .promotion-detail .promotion-voucher-breakdown { color: var(--promotion-muted); font-size: .68rem; }
    .promotion-detail .promotion-empty { color: var(--promotion-muted); padding: 3rem 1rem; }
    @media (max-width: 767.98px) {
        .promotion-detail { padding-inline: .75rem !important; }
        .promotion-detail .promotion-hero { padding: 1rem !important; }
        .promotion-detail .promotion-period { width: 100%; }
        .promotion-detail .promotion-table-toolbar { padding-inline: 1rem; }
    }
</style>
@endpush

@section('content')
@php
    $fmt = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $dateLabel = $selectedDate->translatedFormat('d M Y');
    $dashboardQuery = ['date_from' => $selectedDate->toDateString(), 'date_to' => $selectedDate->toDateString()];
    if ($filters['store_id']) $dashboardQuery['store_id'] = $filters['store_id'];
    if (!empty($filters['dummy'])) $dashboardQuery['dummy'] = 1;
@endphp

<div class="container-fluid py-4 promotion-detail">
    <div class="promotion-hero p-4 mb-4">
        <div>
            <a class="promotion-back mb-3" href="{{ route('marketplace.dashboard.sales', $dashboardQuery) }}">
                <i class="bi bi-arrow-left"></i>Kembali ke dashboard
            </a>
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
                <div>
                    <h1 class="h3 promotion-title mb-2">Promosi {{ $dateLabel }}</h1>
                </div>
                <div class="promotion-period">
                    <span class="promotion-period-icon"><i class="bi bi-calendar3"></i></span>
                    <span>
                        <span class="d-block promotion-period-label">Periode laporan</span>
                        <span class="d-block promotion-period-value">{{ $dateLabel }}</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <form class="card promotion-card promotion-filter shadow-sm mb-4" method="GET" action="{{ route('marketplace.dashboard.promotions.detail', ['date' => $selectedDate->toDateString()]) }}">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-auto me-md-2">
                    <div class="promotion-table-title">Filter</div>
                </div>
                @if (!empty($filters['dummy']))
                    <input type="hidden" name="dummy" value="1">
                @endif
                @if ($stores->isNotEmpty())
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="promotion-detail-store">Toko</label>
                        <select id="promotion-detail-store" class="form-select form-select-sm" name="store_id">
                            <option value="">Semua toko</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" @selected((string) $filters['store_id'] === (string) $store->id)>
                                    {{ $store->name }}{{ $store->channel?->code ? ' · '.ucfirst($store->channel->code) : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-12 col-md-auto">
                    <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                </div>
            </div>
        </div>
    </form>

    @include('marketplace.dashboard.partials._kpis', [
        'kpiTitle' => 'Detail Promosi',
        'kpis' => [
            ['label' => 'GMV', 'value' => $fmt($summary['subtotal']), 'icon' => 'bi-cash-stack', 'variant' => 'sales-kpi--success'],
            ['label' => 'Total Promosi', 'value' => $fmt($summary['promotion_total']), 'icon' => 'bi-percent', 'variant' => 'sales-kpi--warning'],
            ['label' => 'Diskon Produk', 'value' => $fmt($summary['product_discount']), 'icon' => 'bi-tag'],
            ['label' => 'Voucher Toko', 'value' => $fmt($summary['voucher_store']), 'icon' => 'bi-ticket-perforated'],
            ['label' => 'Voucher Platform', 'value' => $fmt($summary['voucher_platform']), 'icon' => 'bi-shop'],
            ['label' => 'Paket Diskon', 'value' => $fmt($summary['bundle_discount']), 'icon' => 'bi-gift'],
        ],
    ])

    <section class="card promotion-card promotion-table-card">
        <div class="promotion-table-toolbar">
            <div>
                <div class="promotion-table-title">Transaksi terdampak promosi</div>
            </div>
            <span class="promotion-count">{{ number_format($rows->count()) }} transaksi</span>
        </div>
        <div class="card-body p-0">
            @if ($rows->isEmpty())
                <div class="promotion-empty text-center"><i class="bi bi-percent d-block fs-3 mb-2"></i>Belum ada pesanan dengan promosi pada tanggal ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle promotion-table">
                        <thead>
                            <tr>
                                <th class="ps-3">No. Pesanan</th>
                                <th>Tanggal</th>
                                <th>Pelanggan &amp; Toko</th>
                                <th>Pembayaran</th>
                                <th class="text-end">Diskon Produk</th>
                                <th class="text-end">Voucher</th>
                                <th class="text-end">Paket Diskon</th>
                                <th class="text-end pe-3">Total Promosi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="ps-3">
                                        <a class="promotion-order-link" href="{{ route('marketplace.orders.show', ['order' => $row->order_id]) }}" aria-label="Lihat detail pesanan {{ $row->order_number }}">
                                            {{ $row->order_number }} <i class="bi bi-arrow-up-right"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="promotion-date">{{ \Carbon\Carbon::parse($row->order_at)->format('d M Y') }}</div>
                                        <div class="promotion-time">{{ \Carbon\Carbon::parse($row->order_at)->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $row->customer }}</div>
                                        <div class="small promotion-muted">{{ $row->store }}</div>
                                    </td>
                                    <td><span class="promotion-payment">{{ $row->payment }}</span></td>
                                    <td class="text-end"><span class="promotion-amount">{{ $fmt($row->product_discount) }}</span></td>
                                    <td class="text-end">
                                        <div class="promotion-amount">{{ $fmt($row->voucher_store + $row->voucher_platform) }}</div>
                                        <div class="promotion-voucher-breakdown">Toko {{ $fmt($row->voucher_store) }} · Platform {{ $fmt($row->voucher_platform) }}</div>
                                    </td>
                                    <td class="text-end"><span class="promotion-amount">{{ $fmt($row->bundle_discount) }}</span></td>
                                    <td class="text-end pe-3"><span class="promotion-amount">{{ $fmt($row->total_promotion) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
