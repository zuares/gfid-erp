@extends('layouts.app')

@section('title', 'Detail Pengiriman • Marketplace')

@include('marketplace._shared')

@push('head')
<style>
    .shipping-detail {
        --shipping-line: var(--line, #d4d7e3);
        --shipping-muted: var(--muted, #6b7280);
        --shipping-card: var(--card, #fff);
        --shipping-soft: var(--card-soft, #f9fafb);
        --shipping-ink: var(--text, #111827);
        --shipping-primary: var(--accent, #2563eb);
        --shipping-primary-soft: var(--accent-soft, #dbeafe);
        max-width: 1400px;
        margin-inline: auto;
        color: var(--shipping-ink);
    }
    .shipping-detail .shipping-card,
    .shipping-detail .shipping-kpi-card { border: 1px solid var(--shipping-line); border-radius: 1rem; background: var(--shipping-card); box-shadow: 0 .5rem 1.5rem rgba(15, 23, 42, .045); }
    .shipping-detail .shipping-hero { border: 1px solid color-mix(in srgb, var(--shipping-primary) 18%, var(--shipping-line)); border-radius: 1rem; background: linear-gradient(135deg, color-mix(in srgb, var(--shipping-primary-soft) 52%, var(--shipping-card)), var(--shipping-card) 68%); box-shadow: 0 .5rem 1.5rem rgba(15, 23, 42, .045); }
    .shipping-detail .shipping-back { display: inline-flex; align-items: center; gap: .35rem; border: 1px solid var(--shipping-line); border-radius: 999px; padding: .35rem .65rem; color: var(--shipping-muted); background: color-mix(in srgb, var(--shipping-card) 82%, transparent); font-size: .75rem; font-weight: 700; text-decoration: none; }
    .shipping-detail .shipping-back:hover { color: var(--shipping-primary); border-color: var(--shipping-primary); }
    .shipping-detail .shipping-kicker { color: var(--shipping-primary); font-size: .68rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .shipping-detail .shipping-title { color: var(--shipping-ink); letter-spacing: -.045em; font-weight: 800; }
    .shipping-detail .shipping-muted { color: var(--shipping-muted); }
    .shipping-detail .shipping-period { display: inline-flex; align-items: center; gap: .7rem; min-width: 11rem; padding: .8rem .9rem; border: 1px solid var(--shipping-line); border-radius: .85rem; background: color-mix(in srgb, var(--shipping-card) 84%, transparent); }
    .shipping-detail .shipping-period-icon { display: inline-flex; align-items: center; justify-content: center; width: 2.25rem; height: 2.25rem; border-radius: .7rem; background: var(--shipping-primary-soft); color: var(--shipping-primary); }
    .shipping-detail .shipping-period-label { color: var(--shipping-muted); font-size: .65rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .shipping-detail .shipping-period-value { color: var(--shipping-ink); font-size: .9rem; font-weight: 800; }
    .shipping-detail .shipping-filter { background: var(--shipping-soft); }
    .shipping-detail .shipping-filter .form-label { color: var(--shipping-muted); font-size: .74rem; font-weight: 700; margin-bottom: .3rem; }
    .shipping-detail .shipping-filter .form-select { border-color: var(--shipping-line); border-radius: .65rem; background: var(--shipping-card); color: var(--shipping-ink); font-size: .8rem; }
    .shipping-detail .shipping-filter .btn { border-radius: .65rem; font-weight: 750; }
    .shipping-detail .shipping-kpi-card { min-height: 124px; position: relative; overflow: hidden; }
    .shipping-detail .shipping-kpi-card::after { content: ''; position: absolute; right: -2rem; bottom: -2.7rem; width: 7rem; height: 7rem; border-radius: 50%; background: var(--shipping-primary); opacity: .055; }
    .shipping-detail .shipping-kpi-label { color: var(--shipping-muted); font-size: .7rem; font-weight: 800; }
    .shipping-detail .shipping-kpi-icon { position: relative; z-index: 1; display: inline-flex; align-items: center; justify-content: center; width: 2.15rem; height: 2.15rem; border-radius: .7rem; background: var(--shipping-primary-soft); color: var(--shipping-primary); }
    .shipping-detail .shipping-kpi-value { color: var(--shipping-ink); font-size: 1.35rem; font-weight: 850; letter-spacing: -.04em; }
    .shipping-detail .shipping-kpi-note { color: var(--shipping-muted); font-size: .72rem; }
    .shipping-detail .shipping-kpi-card.ready::after { background: #16a34a; }
    .shipping-detail .shipping-kpi-card.ready .shipping-kpi-icon { background: #dcfce7; color: #15803d; }
    .shipping-detail .shipping-kpi-card.transit::after { background: #2563eb; }
    .shipping-detail .shipping-kpi-card.completed::after { background: #ea580c; }
    .shipping-detail .shipping-kpi-card.completed .shipping-kpi-icon { background: #ffedd5; color: #c2410c; }
    .shipping-detail .shipping-table-toolbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.25rem; border-bottom: 1px solid var(--shipping-line); }
    .shipping-detail .shipping-table-title { color: var(--shipping-ink); font-size: .98rem; font-weight: 800; }
    .shipping-detail .shipping-table-subtitle { color: var(--shipping-muted); font-size: .74rem; }
    .shipping-detail .shipping-count { border: 1px solid var(--shipping-line); border-radius: 999px; padding: .4rem .7rem; color: var(--shipping-muted); background: var(--shipping-soft); font-size: .7rem; font-weight: 800; white-space: nowrap; }
    .shipping-detail .shipping-table { --bs-table-bg: var(--shipping-card); --bs-table-color: var(--shipping-ink); margin-bottom: 0; }
    .shipping-detail .shipping-table th { padding-top: .75rem; padding-bottom: .75rem; background: var(--shipping-soft); border-bottom-color: var(--shipping-line); color: var(--shipping-muted); font-size: .66rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; white-space: nowrap; }
    .shipping-detail .shipping-table td { padding-top: .8rem; padding-bottom: .8rem; border-color: color-mix(in srgb, var(--shipping-line) 65%, transparent); font-size: .78rem; vertical-align: middle; }
    .shipping-detail .shipping-table tbody tr:hover { --bs-table-hover-bg: color-mix(in srgb, var(--shipping-primary-soft) 42%, var(--shipping-card)); }
    .shipping-detail .shipping-order-link { display: inline-flex; align-items: center; gap: .35rem; color: var(--shipping-primary); font-weight: 800; text-decoration: none; }
    .shipping-detail .shipping-order-link:hover { text-decoration: underline; }
    .shipping-detail .shipping-order-link i { font-size: .7rem; opacity: .65; }
    .shipping-detail .shipping-date { color: var(--shipping-ink); font-weight: 700; }
    .shipping-detail .shipping-time { color: var(--shipping-muted); font-size: .7rem; }
    .shipping-detail .shipping-status { display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px; padding: .32rem .55rem; font-size: .7rem; font-weight: 800; white-space: nowrap; }
    .shipping-detail .shipping-status::before { content: ''; width: .38rem; height: .38rem; border-radius: 50%; background: currentColor; }
    .shipping-detail .shipping-status.ready { color: #15803d; background: #dcfce7; }
    .shipping-detail .shipping-status.transit { color: #1d4ed8; background: #dbeafe; }
    .shipping-detail .shipping-status.completed { color: #c2410c; background: #ffedd5; }
    .shipping-detail .shipping-status.other { color: #64748b; background: #f1f5f9; }
    .shipping-detail .shipping-muted-small { color: var(--shipping-muted); font-size: .7rem; }
    .shipping-detail .shipping-empty { color: var(--shipping-muted); padding: 3rem 1rem; }
    @media (max-width: 767.98px) {
        .shipping-detail { padding-inline: .75rem !important; }
        .shipping-detail .shipping-hero { padding: 1rem !important; }
        .shipping-detail .shipping-period { width: 100%; }
        .shipping-detail .shipping-table-toolbar { padding-inline: 1rem; }
    }
</style>
@endpush

@section('content')
@php
    $dateLabel = $selectedDate->translatedFormat('d M Y');
    $dashboardQuery = ['date_from' => $selectedDate->toDateString(), 'date_to' => $selectedDate->toDateString(), 'tab' => 'shipping'];
    if ($filters['store_id']) $dashboardQuery['store_id'] = $filters['store_id'];
    if (!empty($filters['dummy'])) $dashboardQuery['dummy'] = 1;
@endphp

<div class="container-fluid py-4 shipping-detail">
    <div class="shipping-hero p-4 mb-4">
        <a class="shipping-back mb-3" href="{{ route('marketplace.dashboard.sales', $dashboardQuery) }}"><i class="bi bi-arrow-left"></i>Kembali ke dashboard</a>
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <div class="shipping-kicker mb-2"><i class="bi bi-truck me-1"></i>Detail pengiriman</div>
                <h1 class="h3 shipping-title mb-2">Pengiriman {{ $dateLabel }}</h1>
                <p class="shipping-muted mb-0">Daftar order marketplace dan status pengiriman pada tanggal terpilih.</p>
            </div>
            <div class="shipping-period">
                <span class="shipping-period-icon"><i class="bi bi-calendar3"></i></span>
                <span><span class="d-block shipping-period-label">Periode laporan</span><span class="d-block shipping-period-value">{{ $dateLabel }}</span></span>
            </div>
        </div>
    </div>

    <form class="card shipping-card shipping-filter shadow-sm mb-4" method="GET" action="{{ route('marketplace.dashboard.shipping.detail', ['date' => $selectedDate->toDateString()]) }}">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-auto me-md-2">
                    <div class="shipping-kicker mb-1"><i class="bi bi-sliders me-1"></i>Ruang lingkup</div>
                    <div class="small shipping-muted">Sesuaikan toko untuk analisis detail</div>
                </div>
                @if (!empty($filters['dummy']))
                    <input type="hidden" name="dummy" value="1">
                @endif
                @if ($stores->isNotEmpty())
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="shipping-detail-store">Toko</label>
                        <select id="shipping-detail-store" class="form-select form-select-sm" name="store_id">
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

    <div class="row g-3 mb-4">
        @foreach ([
            ['label' => 'Total Order', 'value' => $summary['orders'], 'note' => 'order pada tanggal ini', 'icon' => 'bi-receipt', 'class' => ''],
            ['label' => 'Siap Dikirim', 'value' => $summary['ready_orders'], 'note' => 'status operasional', 'icon' => 'bi-box-arrow-up', 'class' => 'ready'],
            ['label' => 'Dalam Pengiriman', 'value' => $summary['transit_orders'], 'note' => 'status transit', 'icon' => 'bi-truck', 'class' => 'transit'],
            ['label' => 'Selesai', 'value' => $summary['completed_orders'], 'note' => 'order selesai', 'icon' => 'bi-check2-circle', 'class' => 'completed'],
        ] as $kpi)
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="shipping-kpi-card {{ $kpi['class'] }} h-100 p-3">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div><div class="shipping-kpi-label mb-2">{{ $kpi['label'] }}</div><div class="shipping-kpi-value">{{ number_format($kpi['value']) }}</div><div class="shipping-kpi-note mt-1">{{ $kpi['note'] }}</div></div>
                        <span class="shipping-kpi-icon"><i class="bi {{ $kpi['icon'] }}"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <section class="card shipping-card shipping-table-card">
        <div class="shipping-table-toolbar">
            <div>
                <div class="shipping-kicker mb-1">Rincian order</div>
                <div class="shipping-table-title">Daftar pengiriman</div>
                <div class="shipping-table-subtitle">Maksimal 500 order terbaru pada tanggal ini.</div>
            </div>
            <span class="shipping-count">{{ number_format($rows->count()) }} order</span>
        </div>
        <div class="card-body p-0">
            @if ($rows->isEmpty())
                <div class="shipping-empty text-center"><i class="bi bi-truck d-block fs-3 mb-2"></i>Belum ada order pengiriman pada tanggal ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle shipping-table">
                        <thead>
                            <tr>
                                <th class="ps-3">No. Pesanan</th>
                                <th>Waktu Order</th>
                                <th>Pembeli</th>
                                <th>Toko / Channel</th>
                                <th>Status</th>
                                <th>Kurir &amp; Resi</th>
                                <th class="pe-3">Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php
                                    $latestUpdate = $row->delivered_at ?: ($row->shipped_at ?: $row->shipping_arranged_at);
                                @endphp
                                <tr>
                                    <td class="ps-3"><a class="shipping-order-link" href="{{ route('marketplace.orders.show', ['order' => $row->id]) }}">{{ $row->order_number }} <i class="bi bi-arrow-up-right"></i></a></td>
                                    <td><div class="shipping-date">{{ \Carbon\Carbon::parse($row->order_at)->format('d M Y') }}</div><div class="shipping-time">{{ \Carbon\Carbon::parse($row->order_at)->format('H:i') }}</div></td>
                                    <td>{{ $row->customer }}</td>
                                    <td><div class="fw-semibold">{{ $row->store }}</div><div class="shipping-muted-small">{{ $row->channel }}</div></td>
                                    <td><span class="shipping-status {{ $row->status_group }}">{{ $row->status_label }}</span></td>
                                    <td><div>{{ $row->shipping_carrier ?: 'Kurir belum ditentukan' }}</div><div class="shipping-muted-small">{{ $row->shipping_awb_no ? 'Resi '.$row->shipping_awb_no : 'Resi belum tersedia' }}</div></td>
                                    <td class="pe-3">@if ($latestUpdate)<div class="shipping-date">{{ \Carbon\Carbon::parse($latestUpdate)->format('d M Y') }}</div><div class="shipping-time">{{ \Carbon\Carbon::parse($latestUpdate)->format('H:i') }}</div>@else<span class="shipping-muted-small">Belum ada</span>@endif</td>
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
