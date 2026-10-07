@extends('layouts.app')

@section('title', 'Detail Pembayaran • Marketplace')

@include('marketplace._shared')

@push('head')
<style>
    .payment-detail {
        --payment-line: var(--line, #d4d7e3);
        --payment-muted: var(--muted, #6b7280);
        --payment-card: var(--card, #fff);
        --payment-soft: var(--card-soft, #f9fafb);
        --payment-ink: var(--text, #111827);
        --payment-primary: var(--accent, #2563eb);
        --payment-primary-soft: var(--accent-soft, #dbeafe);
        max-width: 1400px;
        margin-inline: auto;
        color: var(--payment-ink);
    }
    .payment-detail .payment-card { border: 1px solid var(--payment-line); border-radius: 1rem; background: var(--payment-card); box-shadow: 0 .5rem 1.5rem rgba(15, 23, 42, .045); }
    .payment-detail .payment-hero { border: 1px solid color-mix(in srgb, var(--payment-primary) 18%, var(--payment-line)); border-radius: 1rem; background: linear-gradient(135deg, color-mix(in srgb, var(--payment-primary-soft) 52%, var(--payment-card)), var(--payment-card) 68%); box-shadow: 0 .5rem 1.5rem rgba(15, 23, 42, .045); }
    .payment-detail .payment-back { display: inline-flex; align-items: center; gap: .35rem; border: 1px solid var(--payment-line); border-radius: 999px; padding: .35rem .65rem; color: var(--payment-muted); background: color-mix(in srgb, var(--payment-card) 82%, transparent); font-size: .75rem; font-weight: 700; text-decoration: none; }
    .payment-detail .payment-back:hover { color: var(--payment-primary); border-color: var(--payment-primary); }
    .payment-detail .payment-kicker { color: var(--payment-primary); font-size: .68rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .payment-detail .payment-title { color: var(--payment-ink); letter-spacing: -.045em; font-weight: 800; }
    .payment-detail .payment-muted { color: var(--payment-muted); }
    .payment-detail .payment-period { display: inline-flex; align-items: center; gap: .7rem; min-width: 11rem; padding: .8rem .9rem; border: 1px solid var(--payment-line); border-radius: .85rem; background: color-mix(in srgb, var(--payment-card) 84%, transparent); }
    .payment-detail .payment-period-icon { display: inline-flex; align-items: center; justify-content: center; width: 2.25rem; height: 2.25rem; border-radius: .7rem; background: var(--payment-primary-soft); color: var(--payment-primary); }
    .payment-detail .payment-period-label { color: var(--payment-muted); font-size: .65rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .payment-detail .payment-period-value { color: var(--payment-ink); font-size: .9rem; font-weight: 800; }
    .payment-detail .payment-filter { background: var(--payment-soft); }
    .payment-detail .payment-filter .form-label { color: var(--payment-muted); font-size: .74rem; font-weight: 700; margin-bottom: .3rem; }
    .payment-detail .payment-filter .form-select { border-color: var(--payment-line); border-radius: .65rem; background: var(--payment-card); color: var(--payment-ink); font-size: .8rem; }
    .payment-detail .payment-filter .btn { border-radius: .65rem; font-weight: 750; }
    .payment-detail .sales-card { border: 1px solid var(--payment-line); border-radius: 1rem; background: var(--payment-card); box-shadow: 0 .5rem 1.5rem rgba(15, 23, 42, .045); }
    .payment-detail .sales-kpi { min-height: 124px; position: relative; overflow: hidden; }
    .payment-detail .sales-kpi::after { content: ''; position: absolute; right: -2rem; bottom: -2.7rem; width: 7rem; height: 7rem; border-radius: 50%; background: var(--payment-primary); opacity: .055; }
    .payment-detail .sales-kpi-label { color: var(--payment-muted); font-size: .7rem; font-weight: 800; }
    .payment-detail .sales-kpi-icon { position: relative; z-index: 1; display: inline-flex; align-items: center; justify-content: center; width: 2.15rem; height: 2.15rem; border-radius: .7rem; background: var(--payment-primary-soft); color: var(--payment-primary); }
    .payment-detail .sales-kpi-value { color: var(--payment-ink); font-size: 1.35rem; font-weight: 850; letter-spacing: -.04em; }
    .payment-detail .sales-kpi-note { color: var(--payment-muted); font-size: .72rem; }
    .payment-detail .sales-kpi--success::after { background: #16a34a; }
    .payment-detail .sales-kpi--success .sales-kpi-icon { background: #dcfce7; color: #15803d; }
    .payment-detail .sales-kpi--warning::after { background: #ea580c; }
    .payment-detail .sales-kpi--warning .sales-kpi-icon { background: #ffedd5; color: #c2410c; }
    .payment-detail .payment-table-toolbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.25rem; border-bottom: 1px solid var(--payment-line); }
    .payment-detail .payment-table-title { color: var(--payment-ink); font-size: .98rem; font-weight: 800; }
    .payment-detail .payment-table-subtitle { color: var(--payment-muted); font-size: .74rem; }
    .payment-detail .payment-count { border: 1px solid var(--payment-line); border-radius: 999px; padding: .4rem .7rem; color: var(--payment-muted); background: var(--payment-soft); font-size: .7rem; font-weight: 800; white-space: nowrap; }
    .payment-detail .payment-table { --bs-table-bg: var(--payment-card); --bs-table-color: var(--payment-ink); margin-bottom: 0; }
    .payment-detail .payment-table th { padding-top: .75rem; padding-bottom: .75rem; background: var(--payment-soft); border-bottom-color: var(--payment-line); color: var(--payment-muted); font-size: .66rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; white-space: nowrap; }
    .payment-detail .payment-table td { padding-top: .8rem; padding-bottom: .8rem; border-color: color-mix(in srgb, var(--payment-line) 65%, transparent); font-size: .78rem; white-space: nowrap; }
    .payment-detail .payment-table tbody tr:hover { --bs-table-hover-bg: color-mix(in srgb, var(--payment-primary-soft) 42%, var(--payment-card)); }
    .payment-detail .payment-order-link { display: inline-flex; align-items: center; gap: .35rem; color: var(--payment-primary); font-weight: 800; text-decoration: none; }
    .payment-detail .payment-order-link:hover { text-decoration: underline; }
    .payment-detail .payment-order-link i { font-size: .7rem; opacity: .65; }
    .payment-detail .payment-date { color: var(--payment-ink); font-weight: 700; }
    .payment-detail .payment-time { color: var(--payment-muted); font-size: .7rem; }
    .payment-detail .payment-method { display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px; padding: .32rem .55rem; background: var(--payment-soft); color: var(--payment-ink); font-size: .7rem; font-weight: 750; }
    .payment-detail .payment-status { display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px; padding: .32rem .55rem; font-size: .7rem; font-weight: 800; }
    .payment-detail .payment-status::before { content: ''; width: .38rem; height: .38rem; border-radius: 50%; background: currentColor; }
    .payment-detail .payment-status.paid { color: #15803d; background: #dcfce7; }
    .payment-detail .payment-status.pending { color: #b45309; background: #fef3c7; }
    .payment-detail .payment-amount { color: var(--payment-ink); font-weight: 800; }
    .payment-detail .payment-empty { color: var(--payment-muted); padding: 3rem 1rem; }
    @media (max-width: 767.98px) {
        .payment-detail { padding-inline: .75rem !important; }
        .payment-detail .payment-hero { padding: 1rem !important; }
        .payment-detail .payment-period { width: 100%; }
        .payment-detail .payment-table-toolbar { padding-inline: 1rem; }
    }
</style>
@endpush

@section('content')
@php
    $fmt = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $dateLabel = $selectedDate->translatedFormat('d M Y');
    $dashboardQuery = ['date_from' => $selectedDate->toDateString(), 'date_to' => $selectedDate->toDateString(), 'tab' => 'payments'];
    if (!empty($filters['platform'])) $dashboardQuery['platform'] = $filters['platform'];
    if ($filters['store_id']) $dashboardQuery['store_id'] = $filters['store_id'];
    if (!empty($filters['dummy'])) $dashboardQuery['dummy'] = 1;
@endphp

<div class="container-fluid py-4 payment-detail">
    <div class="payment-hero p-4 mb-4">
        <a class="payment-back mb-3" href="{{ route('marketplace.dashboard.sales', $dashboardQuery) }}"><i class="bi bi-arrow-left"></i>Kembali ke dashboard</a>
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <div class="payment-kicker mb-2"><i class="bi bi-wallet2 me-1"></i>Detail pembayaran</div>
                <h1 class="h3 payment-title mb-2">Pembayaran {{ $dateLabel }}</h1>
                <p class="payment-muted mb-0">Rincian nilai setiap transaksi pada tanggal terpilih.</p>
            </div>
            <div class="payment-period">
                <span class="payment-period-icon"><i class="bi bi-calendar3"></i></span>
                <span><span class="d-block payment-period-label">Periode laporan</span><span class="d-block payment-period-value">{{ $dateLabel }}</span></span>
            </div>
        </div>
    </div>

    <form class="card payment-card payment-filter shadow-sm mb-4" method="GET" action="{{ route('marketplace.dashboard.payments.detail', ['date' => $selectedDate->toDateString()]) }}">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-auto me-md-2">
                    <div class="payment-kicker mb-1"><i class="bi bi-sliders me-1"></i>Ruang lingkup</div>
                    <div class="small payment-muted">Sesuaikan toko untuk analisis detail</div>
                </div>
                @if (!empty($filters['dummy']))
                    <input type="hidden" name="dummy" value="1">
                @endif
                @if (!empty($filters['platform']))
                    <input type="hidden" name="platform" value="{{ $filters['platform'] }}">
                @endif
                @if ($stores->isNotEmpty())
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="payment-detail-store">Toko</label>
                        <select id="payment-detail-store" class="form-select form-select-sm" name="store_id">
                            <option value="">Semua toko</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" @selected((string) $filters['store_id'] === (string) $store->id)>{{ $store->name }}{{ $store->channel?->code ? ' · '.ucfirst($store->channel->code) : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-12 col-md-auto"><button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button></div>
            </div>
        </div>
    </form>

    @include('marketplace.dashboard.partials._kpis', [
        'kpiTitle' => 'Detail Pembayaran',
        'kpis' => [
            ['label' => 'Dibayar Pembeli', 'value' => $fmt($summary['total_paid']), 'note' => number_format($summary['orders']).' order pada tanggal ini', 'icon' => 'bi-cash-stack', 'variant' => 'sales-kpi--success'],
            ['label' => 'Average Ticket', 'value' => $fmt($summary['aov']), 'note' => 'rata-rata daya beli / order', 'icon' => 'bi-graph-up-arrow'],
            ['label' => 'Median Ticket', 'value' => $fmt($summary['median_ticket']), 'note' => 'nilai tipikal customer', 'icon' => 'bi-bar-chart-line'],
            ['label' => 'High-value Orders', 'value' => number_format($summary['high_value_orders']), 'note' => '≥ 1,5× average ticket', 'icon' => 'bi-stars', 'variant' => 'sales-kpi--warning'],
        ],
    ])

    <section class="card payment-card payment-table-card">
        <div class="payment-table-toolbar">
            <div>
                <div class="payment-kicker mb-1">Transaction ledger</div>
                <div class="payment-table-title">Daftar transaksi pembayaran</div>
                <div class="payment-table-subtitle">Maksimal 500 transaksi terbaru dengan rincian pembayaran.</div>
            </div>
            <span class="payment-count">{{ number_format($rows->count()) }} transaksi</span>
        </div>
        <div class="card-body p-0">
            @if ($rows->isEmpty())
                <div class="payment-empty text-center"><i class="bi bi-wallet2 d-block fs-3 mb-2"></i>Belum ada transaksi pembayaran pada tanggal ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle payment-table">
                        <thead><tr><th class="ps-3">Order</th><th>Tanggal Order</th><th>Toko / Channel</th><th>Pembeli</th><th>Metode</th><th class="text-end">Subtotal Pesanan</th><th class="text-end">Ongkos Kirim</th><th class="text-end">Voucher Shopee</th><th class="text-end">Voucher Toko</th><th class="text-end">Biaya Layanan</th><th class="text-end pe-3">Dibayar Pembeli</th></tr></thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="ps-3"><a class="payment-order-link" href="{{ route('marketplace.orders.show', ['order' => $row->id]) }}">{{ $row->order_number }} <i class="bi bi-arrow-up-right"></i></a></td>
                                    <td><div class="payment-date">{{ \Carbon\Carbon::parse($row->order_at)->format('d M Y') }}</div><div class="payment-time">{{ \Carbon\Carbon::parse($row->order_at)->format('H:i') }}</div></td>
                                    <td><div class="fw-semibold">{{ $row->store }}</div><div class="small payment-muted">{{ $row->channel }}</div></td>
                                    <td>{{ $row->customer }}</td>
                                    <td><span class="payment-method">{{ ucwords(str_replace('_', ' ', strtolower($row->payment))) }}</span></td>
                                    <td class="text-end">{{ $fmt($row->product_subtotal) }}</td>
                                    <td class="text-end">{{ $fmt($row->shipping_fee) }}</td>
                                    <td class="text-end">{{ $row->voucher_platform > 0 ? '-'.$fmt($row->voucher_platform) : $fmt(0) }}</td>
                                    <td class="text-end">{{ $row->voucher_store > 0 ? '-'.$fmt($row->voucher_store) : $fmt(0) }}</td>
                                    <td class="text-end">{{ $fmt($row->buyer_service_fee) }}</td>
                                    <td class="text-end pe-3"><span class="payment-amount">{{ $fmt($row->total_paid) }}</span></td>
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
